<?php
use Sabberworm\CSS\Value\Size;
session_start();
require_once '../php/Connect.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario_primeiro_acesso'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (empty($usuario) || empty($nova_senha) || empty($confirma_senha)) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Preencha todos os campos.'
        ]);
        exit();
    }

    if ($nova_senha !== $confirma_senha) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'As senhas não coincidem ou não atendem aos critérios.'
        ]);
        exit();

    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM Jurados WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $usuario]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Usuário não encontrado.'
            ]);
            exit();
        }
        $stmt = $pdo->prepare("
            UPDATE Jurados
            SET senha_cadastrada = ?, primeiro_acesso = 1
            WHERE id_jurados = ?
        ");

        $stmt->execute([
            $nova_senha,
            $user['id_jurados']
        ]);

        $_SESSION['id_jurados'] = $user['id_jurados'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['senha_cadastrada'] = $nova_senha;

        echo json_encode([
            'status' => 'sucesso',
            'redirect' => 'jurado-dashboard.php'
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao salvar a nova senha.'
        ]);
        exit();
    }

} else {

    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método de requisição inválido.'
    ]);

    exit();

}
?>