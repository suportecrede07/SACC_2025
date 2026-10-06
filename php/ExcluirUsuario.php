<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-dashboard.php');
    exit();
}

$id_admin = $_POST['id_admin'] ?? null;

if (!$id_admin) {
    die('Usuário não selecionado.');
}

try {
    if (isset($_SESSION['id_admin']) && $_SESSION['id_admin'] == $id_admin) {
        die('Você não pode excluir o usuário que está conectado.');
    }

    $stmt = $pdo->prepare("
        DELETE FROM administracao
        WHERE id_admin = ?
    ");

    $stmt->execute([$id_admin]);

    if ($stmt->rowCount() === 0) {
        die('Usuário não encontrado.');
    }

    header('Location: ../html/admin-dashboard.php?msg=usuario_excluido');
    exit();

} catch (PDOException $e) {

    die('Erro ao excluir usuário: ' . $e->getMessage());
}