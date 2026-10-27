<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $formulario = trim($_POST['formulario'] ?? '');
    $nome_campo = trim($_POST['nome_campo'] ?? '');
    $tipo_campo = trim($_POST['tipo_campo'] ?? '');
    $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;

    if (empty($id)) {
        die('ID do campo é obrigatório para atualização.');
    }
    if (empty($formulario)) {
        die('Selecione o formulário em que o campo vai aparecer.');
    }
    if (empty($nome_campo)) {
        die('Preencha o nome do campo.');
    }
    if (empty($tipo_campo)) {
        die('Selecione o tipo de resposta do campo.');
    }

    try {
        $sql = "UPDATE Campos_formulario 
                SET formulario = :formulario, 
                    nome_campo = :nome_campo, 
                    tipo_campo = :tipo_campo, 
                    obrigatorio = :obrigatorio 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':formulario'  => $formulario,
            ':nome_campo'  => $nome_campo,
            ':tipo_campo'  => $tipo_campo,
            ':obrigatorio' => $obrigatorio,
            ':id'          => $id
        ]);

        header("Location: ../html/admin-config.php?tab=campos&msg=campo_atualizado");
        exit();

    } catch (Exception $e) {
        die('Erro ao atualizar campo dinâmico: ' . $e->getMessage());
    }
} else {
    die('Método inválido.');
}
?>
