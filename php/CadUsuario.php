<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-dashboard.php');
    exit();
}

$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';
$nivel_permissao = $_POST['Nivel_permissao'] ?? '';

if ($usuario === '' || $senha === '' || $nivel_permissao === '') {
    die('Preencha todos os campos.');
}

try {

    // Verifica se o usuário já existe
    $stmt = $pdo->prepare("SELECT id_admin FROM administracao WHERE usuario = ?");
    $stmt->execute([$usuario]);

    if ($stmt->fetch()) {
        die('Este usuário já está cadastrado.');
    }

    // Criptografa a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere o usuário
    $stmt = $pdo->prepare("
        INSERT INTO administracao 
        (usuario, senha, Nivel_permissao)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $usuario,
        $senha_hash,
        $nivel_permissao
    ]);

    header('Location: ../html/admin-dashboard.php?msg=usuario_sucesso');
    exit();

} catch (PDOException $e) {

    die('Erro ao cadastrar usuário: ' . $e->getMessage());
}