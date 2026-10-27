<?php
session_start();

if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once __DIR__ . '/Connect.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM Criterios_desempate WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../html/admin-config.php?tab=desempate&msg=desempate_excluido');
        exit();
    } catch (Exception $e) {
        die('Erro ao excluir regra de desempate: ' . $e->getMessage());
    }
} else {
    header('Location: ../html/admin-config.php?tab=desempate');
    exit();
}
?>
