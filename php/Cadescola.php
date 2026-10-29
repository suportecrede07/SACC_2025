<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';
require_once __DIR__ . '/SincronizarCamposDinamicos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método inválido.');
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$categoriaEscola = filter_var($_POST['categoriaEscola'] ?? null, FILTER_VALIDATE_INT);
$idMunicipio = filter_var($_POST['municipio'] ?? null, FILTER_VALIDATE_INT);
$camposEnviados = $_POST['campos_dinamicos'] ?? [];

$municipios = [
    1 => 'Caridade',
    2 => 'Canindé',
    3 => 'Paramoti',
    4 => 'General Sampaio',
    5 => 'Santa Quitéria',
    6 => 'Itatira',
];

$municipio = ($idMunicipio !== false && $idMunicipio !== null)
    ? ($municipios[$idMunicipio] ?? null)
    : null;

if ($nome === '') {
    http_response_code(400);
    exit('O nome da escola é obrigatório.');
}

if ($municipio === null) {
    http_response_code(400);
    exit('O município informado é inválido.');
}

if ($categoriaEscola === false || $categoriaEscola === null || $categoriaEscola < 1) {
    http_response_code(400);
    exit('A modalidade da instituição é obrigatória.');
}

if (!is_array($camposEnviados)) {
    http_response_code(400);
    exit('Os campos adicionais foram enviados em formato inválido.');
}

/** Normaliza e valida um valor conforme o tipo cadastrado do campo dinâmico. */
$normalizarCampoDinamico = static function ($valor, string $tipoCampo, string $nomeCampo): ?string {
    if (is_array($valor) || is_object($valor)) {
        throw new InvalidArgumentException("O campo '{$nomeCampo}' recebeu um valor inválido.");
    }

    $valor = trim((string) ($valor ?? ''));
    if ($valor === '') {
        return null;
    }

    switch ($tipoCampo) {
        case 'numero':
            $valorNumerico = str_replace(',', '.', $valor);
            if (!is_numeric($valorNumerico)) {
                throw new InvalidArgumentException("O campo '{$nomeCampo}' deve conter um número válido.");
            }
            return $valorNumerico;

        case 'data':
            $data = DateTime::createFromFormat('!Y-m-d', $valor);
            $errosData = DateTime::getLastErrors();
            if (
                $data === false ||
                ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0)) ||
                $data->format('Y-m-d') !== $valor
            ) {
                throw new InvalidArgumentException("O campo '{$nomeCampo}' deve conter uma data válida.");
            }
            return $valor;

        case 'sim_nao':
            if (!in_array($valor, ['0', '1'], true)) {
                throw new InvalidArgumentException("O campo '{$nomeCampo}' deve ser Sim ou Não.");
            }
            return $valor;

        case 'texto':
        default:
            if (function_exists('mb_strlen') && mb_strlen($valor, 'UTF-8') > 255) {
                throw new InvalidArgumentException("O campo '{$nomeCampo}' ultrapassa o limite de 255 caracteres.");
            }
            if (!function_exists('mb_strlen') && strlen($valor) > 255) {
                throw new InvalidArgumentException("O campo '{$nomeCampo}' ultrapassa o limite de 255 caracteres.");
            }
            return $valor;
    }
};

try {
    // A categoria precisa existir; os demais atributos continuam nas colunas-base da escola.
    $stmtCategoria = $pdo->prepare('SELECT id FROM Categoria_escolas WHERE id = ? LIMIT 1');
    $stmtCategoria->execute([$categoriaEscola]);
    if ($stmtCategoria->fetchColumn() === false) {
        throw new InvalidArgumentException('A modalidade selecionada não existe.');
    }

    // Cria/garante as colunas SQL para todos os campos de escola cadastrados.
    // A sincronização ocorre antes da transação, pois ALTER TABLE provoca commit implícito no MySQL.
    $resultadoSincronizacao = sincronizarColunasDinamicasEscola($pdo);
    $todosCamposEscola = $resultadoSincronizacao['campos'] ?? [];

    // Lista autorizada: somente campos ativos vinculados ao cadastro de escolas.
    $camposAtivos = [];
    foreach ($todosCamposEscola as $idCampo => $campo) {
        if ((int) ($campo['ativo'] ?? 0) === 1) {
            $camposAtivos[(int) $idCampo] = $campo;
        }
    }

    // Rejeita campos que foram removidos, desativados ou que pertencem ao formulário de trabalhos.
    foreach (array_keys($camposEnviados) as $idCampoEnviado) {
        $idCampoEnviado = filter_var($idCampoEnviado, FILTER_VALIDATE_INT);
        if ($idCampoEnviado === false || !isset($camposAtivos[(int) $idCampoEnviado])) {
            throw new InvalidArgumentException('Um dos campos adicionais não está ativo para escolas. Atualize a página e tente novamente.');
        }
    }

    $valoresDinamicos = [];
    $valoresPorCampo = [];
    foreach ($camposAtivos as $idCampo => $campo) {
        $valorOriginal = $camposEnviados[$idCampo] ?? null;
        $valorNormalizado = $normalizarCampoDinamico(
            $valorOriginal,
            (string) $campo['tipo_campo'],
            (string) $campo['nome_campo']
        );

        if ((int) $campo['obrigatorio'] === 1 && $valorNormalizado === null) {
            throw new InvalidArgumentException("O campo '{$campo['nome_campo']}' é obrigatório.");
        }

        $valoresPorCampo[$idCampo] = $valorNormalizado;
        if ($valorNormalizado !== null) {
            $valoresDinamicos[$idCampo] = $valorNormalizado;
        }
    }

    $pdo->beginTransaction();

    // Monta o INSERT incluindo a coluna SQL correspondente a cada campo dinâmico ativo.
    $colunasInsert = ['`nome`', '`municipio`', '`id_categoria_escola`'];
    $valoresInsert = [$nome, $municipio, $categoriaEscola];
    foreach ($camposAtivos as $idCampo => $campo) {
        $colunaSql = (string) ($campo['coluna_sql'] ?? '');
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $colunaSql)) {
            throw new RuntimeException('O nome de uma coluna dinâmica não é válido.');
        }
        $colunasInsert[] = '`' . $colunaSql . '`';
        $valoresInsert[] = $valoresPorCampo[$idCampo] ?? null;
    }

    $placeholders = implode(', ', array_fill(0, count($valoresInsert), '?'));
    $sqlEscola = 'INSERT INTO `Escolas` (' . implode(', ', $colunasInsert) . ') VALUES (' . $placeholders . ')';
    $stmtEscola = $pdo->prepare($sqlEscola);
    $stmtEscola->execute($valoresInsert);
    $idEscola = (int) $pdo->lastInsertId();

    // Mantém Valores_campos para compatibilidade com formulários e ranking atuais.
    if ($valoresDinamicos) {
        $stmtValor = $pdo->prepare(
            'INSERT INTO Valores_campos (id_campo, id_registro, valor) VALUES (?, ?, ?)'
        );
        foreach ($valoresDinamicos as $idCampo => $valor) {
            $stmtValor->execute([$idCampo, $idEscola, (string) $valor]);
        }
    }

    $pdo->commit();
    header('Location: ../html/admin-dashboard.php?msg=escola_cadastrada');
    exit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao cadastrar escola: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    exit($e instanceof InvalidArgumentException
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Erro ao cadastrar a escola. Verifique o log do servidor.');
}
