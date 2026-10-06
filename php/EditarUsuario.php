<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-usuarios.php');
    exit;
}

$usuarioAtual = $_POST['usuario_atual'] ?? '';
$novoUsuario = $_POST['novo_usuario'] ?? '';
$novaSenha = $_POST['nova_senha'] ?? '';
$novoNivel = $_POST['novo_nivel'] ?? '';

if (empty($usuarioAtual) || empty($novoUsuario) ||empty($novaSenha) || $novoNivel === '') {
    die('Todos os campos são obrigatórios.');
}

$stmt = $pdo->prepare("
    SELECT usuario
    FROM administracao
    WHERE usuario = ?
    AND usuario <> ?
");

$stmt->execute([
    $novoUsuario,
    $usuarioAtual
]);

if ($stmt->fetch()) {
    die('Esse usuário já existe.');
}


$senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
    UPDATE administracao
    SET
        usuario = ?,
        senha = ?,
        Nivel_permissao = ?
    WHERE usuario = ?
");

$stmt->execute([
    $novoUsuario,
    $senhaHash,
    $novoNivel,
    $usuarioAtual
]);

header('Location: ../html/admin-dashboard.php');
exit;