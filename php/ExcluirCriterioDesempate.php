<?php
session_start();

if (!isset($_SESSION['id_admin'], $_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once __DIR__ . '/Connect.php';

function voltarParaDesempate(string $mensagem): void
{
    header(
        'Location: ../html/admin-config.php?tab=desempate&msg=' .
            rawurlencode($mensagem)
    );
    exit();
}

// A exclusão deve ser enviada por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    voltarParaDesempate('metodo_invalido');
}

// Verifica o token de segurança.
if (
    !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !hash_equals(
        (string) $_SESSION['csrf_token'],
        (string) $_POST['csrf_token']
    )
) {
    voltarParaDesempate('token_invalido');
}

// Valida o ID da regra.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    voltarParaDesempate('desempate_id_invalido');
}

try {
    $pdo->beginTransaction();

    // Verifica se a regra existe e bloqueia o registro durante a operação.
    $stmt = $pdo->prepare(
        'SELECT id
         FROM Criterios_desempate
         WHERE id = ?
         FOR UPDATE'
    );

    $stmt->execute([$id]);

    if ($stmt->fetchColumn() === false) {
        $pdo->rollBack();
        voltarParaDesempate('desempate_nao_encontrado');
    }

    // Exclui a regra selecionada.
    $stmt = $pdo->prepare(
        'DELETE FROM Criterios_desempate WHERE id = ?'
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'A regra não foi removida do banco de dados.'
        );
    }

    // Recupera as regras restantes na ordem configurada.
    $idsRestantes = $pdo->query(
        'SELECT id
         FROM Criterios_desempate
         ORDER BY ordem_prioridade ASC, id ASC'
    )->fetchAll(PDO::FETCH_COLUMN);

    // Reorganiza as prioridades restantes, começando em 1.
    if (!empty($idsRestantes)) {
        $maxPrioridade = (int) $pdo->query(
            'SELECT COALESCE(MAX(ordem_prioridade), 0)
             FROM Criterios_desempate'
        )->fetchColumn();

        // Evita conflitos temporários com um possível índice UNIQUE.
        $deslocamento = $maxPrioridade + count($idsRestantes) + 1;

        $stmtTemporario = $pdo->prepare(
            'UPDATE Criterios_desempate
             SET ordem_prioridade = ordem_prioridade + ?'
        );

        $stmtTemporario->execute([$deslocamento]);

        $stmtOrdem = $pdo->prepare(
            'UPDATE Criterios_desempate
             SET ordem_prioridade = ?
             WHERE id = ?'
        );

        foreach ($idsRestantes as $indice => $idRegra) {
            $stmtOrdem->execute([
                $indice + 1,
                (int) $idRegra
            ]);
        }
    }

    $pdo->commit();

    voltarParaDesempate('desempate_excluido');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Mantém os detalhes técnicos apenas no log do servidor.
    error_log(
        'Erro ao excluir critério de desempate: ' .
            $e->getMessage()
    );

    voltarParaDesempate('erro_excluir_desempate');
}
