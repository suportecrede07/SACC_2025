<?php
session_start();

if (!isset($_SESSION['id_admin'], $_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once __DIR__ . '/Connect.php';

function voltarParaCampos(string $mensagem): void
{
    header('Location: ../html/admin-config.php?tab=campos&msg=' . rawurlencode($mensagem));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    voltarParaCampos('campo_metodo_invalido');
}

if (
    !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    voltarParaCampos('campo_token_invalido');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    voltarParaCampos('campo_id_invalido');
}

try {
    // Primeiro identifica o formulário para saber se há uma coluna SQL a remover.
    $stmtCampo = $pdo->prepare(
        'SELECT id, formulario FROM Campos_formulario WHERE id = ? LIMIT 1'
    );
    $stmtCampo->execute([$id]);
    $campo = $stmtCampo->fetch(PDO::FETCH_ASSOC);

    if (!$campo) {
        voltarParaCampos('campo_nao_encontrado');
    }

    // Exclui o cadastro e os valores associados. O commit ocorre antes do ALTER
    // porque ALTER TABLE causa commit implícito no MySQL.
    $pdo->beginTransaction();

    $stmtValores = $pdo->prepare(
        'DELETE FROM Valores_campos WHERE id_campo = ?'
    );
    $stmtValores->execute([$id]);

    $stmtExcluir = $pdo->prepare(
        'DELETE FROM Campos_formulario WHERE id = ?'
    );
    $stmtExcluir->execute([$id]);

    if ($stmtExcluir->rowCount() !== 1) {
        throw new RuntimeException('O campo dinâmico não foi removido de Campos_formulario.');
    }

    $pdo->commit();

    // Os campos de trabalho não possuem coluna em Escolas.
    // Campos de escola usam o nome determinístico campo_dinamico_ID.
    if ($campo['formulario'] === 'escola') {
        $nomeColuna = 'campo_dinamico_' . (int) $id;

        $stmtTabela = $pdo->query(
            "SELECT TABLE_NAME
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER('Escolas')
             LIMIT 1"
        );
        $nomeTabela = $stmtTabela->fetchColumn();

        if ($nomeTabela !== false) {
            $stmtColuna = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?'
            );
            $stmtColuna->execute([$nomeTabela, $nomeColuna]);

            if ((int) $stmtColuna->fetchColumn() > 0) {
                // Nome de coluna construído somente com o ID inteiro do campo.
                $tabelaSegura = str_replace('`', '``', (string) $nomeTabela);
                $colunaSegura = str_replace('`', '``', $nomeColuna);
                $pdo->exec("ALTER TABLE `{$tabelaSegura}` DROP COLUMN `{$colunaSegura}`");
            }
        }
    }

    voltarParaCampos('campo_excluido');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // A configuração pode já ter sido removida, pois o ALTER TABLE vem depois
    // do commit. Diferencia esse caso para não dizer que o campo continua ativo.
    if (isset($campo) && is_array($campo) && ($campo['formulario'] ?? '') === 'escola') {
        try {
            $stmtVerifica = $pdo->prepare(
                'SELECT COUNT(*) FROM Campos_formulario WHERE id = ?'
            );
            $stmtVerifica->execute([$id]);
            if ((int) $stmtVerifica->fetchColumn() === 0) {
                error_log('Campo dinâmico removido, mas houve falha ao remover a coluna SQL: ' . $e->getMessage());
                voltarParaCampos('campo_excluido_coluna_pendente');
            }
        } catch (Throwable $erroVerificacao) {
            error_log('Falha ao verificar a exclusão do campo dinâmico: ' . $erroVerificacao->getMessage());
        }
    }

    error_log('Erro ao excluir campo dinâmico: ' . $e->getMessage());
    voltarParaCampos('erro_excluir_campo');
}
