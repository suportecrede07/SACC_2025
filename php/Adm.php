<?php
session_start();
require_once '../php/Connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM Administracao WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $login_sucesso = false;
            $is_hash = (strpos($user['senha'], '$') === 0);
            if ($is_hash) {
                if (password_verify($senha, $user['senha'])) {
                    $login_sucesso = true;
                }
            } else {
                if ($senha === $user['senha']) {
                    $login_sucesso = true;
                    $novo_hash = password_hash($senha, PASSWORD_BCRYPT);
                    $update_stmt = $pdo->prepare("UPDATE Administracao SET senha = :novo_hash WHERE id_admin = :id");
                    $update_stmt->execute([
                        ':novo_hash' => $novo_hash,
                        ':id' => $user['id_admin']
                    ]);
                }
            }

            if ($login_sucesso) {
                $_SESSION['id_admin'] = $user['id_admin'];
                $_SESSION['usuario'] = $user['usuario'];
                header('Location: ../html/admin-dashboard.php');
                exit();
            }
        }

        $_SESSION['login_error'] = 'Usuário e/ou senha incorretos!';
        header('Location: ../html/login_adm.php');
        exit();

    } catch (PDOException $e) {
        $error = 'Erro interno ao tentar fazer login.';
    }
}
