<?php
session_start();

// Garante que o administrador esteja autenticado
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once __DIR__ . '/Connect.php';

// Verifica se o ID foi passado via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM Criterios_avaliacao WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../html/admin-config.php?tab=criterios&msg=criterio_excluido');
        exit();
    } catch (Exception $e) {
        die('Erro ao excluir critério avaliativo: ' . $e->getMessage());
    }
} else {
    header('Location: ../html/admin-config.php?tab=criterios');
    exit();
}
?>
