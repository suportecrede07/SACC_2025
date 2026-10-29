<?php
session_start();

// Garante que o administrador esteja autenticado
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once __DIR__ . '/Connect.php';

// Verifica se o ID foi informado corretamente
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: ../html/admin-config.php?tab=criterios&msg=id_invalido');
    exit();
}

try {
    $pdo->beginTransaction();

    // Busca o critério
    $stmt = $pdo->prepare(
        "SELECT id, ativo
         FROM Criterios_avaliacao
         WHERE id = ?
         FOR UPDATE"
    );
    $stmt->execute([$id]);

    $criterio = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o critério existe
    if (!$criterio) {
        $pdo->rollBack();

        header('Location: ../html/admin-config.php?tab=criterios&msg=criterio_nao_encontrado');
        exit();
    }

    // Só permite excluir critérios inativos
    if ((int) $criterio['ativo'] !== 0) {
        $pdo->rollBack();

        header('Location: ../html/admin-config.php?tab=criterios&msg=criterio_precisa_inativar');
        exit();
    }

    // Verifica se existem avaliações vinculadas ao critério
    $stmtAvaliacoes = $pdo->prepare(
        "SELECT COUNT(*)
         FROM Avaliacoes
         WHERE criterio = ?"
    );
    $stmtAvaliacoes->execute([$id]);

    $totalAvaliacoes = (int) $stmtAvaliacoes->fetchColumn();

    // Preserva o histórico de avaliações
    if ($totalAvaliacoes > 0) {
        $pdo->rollBack();

        header('Location: ../html/admin-config.php?tab=criterios&msg=criterio_com_avaliacoes');
        exit();
    }

    // Remove regras de desempate associadas ao critério
    $stmtDesempate = $pdo->prepare(
        "DELETE FROM Criterios_desempate
         WHERE id_criterio_avaliacao = ?"
    );
    $stmtDesempate->execute([$id]);

    // Exclui o critério inativo
    $stmtExcluir = $pdo->prepare(
        "DELETE FROM Criterios_avaliacao
         WHERE id = ? AND ativo = 0"
    );
    $stmtExcluir->execute([$id]);

    $pdo->commit();

    header('Location: ../html/admin-config.php?tab=criterios&msg=criterio_excluido');
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao excluir critério avaliativo: ' . $e->getMessage());

    header('Location: ../html/admin-config.php?tab=criterios&msg=erro_excluir_criterio');
    exit();
}
?>