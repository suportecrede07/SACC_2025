<?php
session_start();

// Verifica se o admin está logado
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['usuario'])) {
    header('Location: ../html/login_adm.php');
    exit();
}

require_once 'Connect.php';

// Verifica se o ID foi passado via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Prepara e executa a exclusão na tabela Campos_formulario
        $stmt = $pdo->prepare("DELETE FROM Campos_formulario WHERE id = ?");
        $stmt->execute([$id]);

        // Redireciona de volta para a página de configurações na aba de campos dinâmicos com sucesso
        header('Location: ../html/admin-config.php?tab=campos&sucesso=excluido');
        exit();
    } catch (Exception $e) {
        // Em caso de erro, redireciona com erro (opcional)
        header('Location: ../html/admin-config.php?tab=campos&erro=falha_exclusao');
        exit();
    }
} else {
    // Se não houver ID, redireciona de volta
    header('Location: ../html/admin-config.php?tab=campos');
    exit();
}