<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nome_criterio = trim($_POST['nome_criterio'] ?? $_POST['nome'] ?? '');
    $desc_criterio_avaliativo = trim($_POST['desc_criterio_avaliativo'] ?? $_POST['descricao'] ?? '');
    $peso = isset($_POST['peso']) && $_POST['peso'] !== '' ? (float)$_POST['peso'] : 1.00;
    $nota_minima = isset($_POST['nota_minima']) && $_POST['nota_minima'] !== '' ? (float)$_POST['nota_minima'] : 0.00;
    $nota_maxima = isset($_POST['nota_maxima']) && $_POST['nota_maxima'] !== '' ? (float)$_POST['nota_maxima'] : 10.00;
    $ordem = isset($_POST['ordem']) && $_POST['ordem'] !== '' ? (int)$_POST['ordem'] : 1;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($id)) {
        die('ID do critério é obrigatório para atualização.');
    }
    if (empty($nome_criterio)) {
        die('Preencha o campo nome.');
    }
    if (empty($desc_criterio_avaliativo)) {
        die('Preencha o campo de descrição.');
    }

    try {
        $sql = "UPDATE Criterios_avaliacao 
                SET nome = :nome, 
                    descricao = :descricao, 
                    peso = :peso, 
                    nota_minima = :nota_minima, 
                    nota_maxima = :nota_maxima, 
                    ordem = :ordem, 
                    ativo = :ativo 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome_criterio,
            ':descricao' => $desc_criterio_avaliativo,
            ':peso' => $peso,
            ':nota_minima' => $nota_minima,
            ':nota_maxima' => $nota_maxima,
            ':ordem' => $ordem,
            ':ativo' => $ativo,
            ':id' => $id
        ]);

        header("Location: ../html/admin-config.php?tab=criterios&msg=criterio_atualizado");
        exit();

    } catch (Exception $e) {
        die('Erro ao atualizar critério avaliativo: ' . $e->getMessage());
    }
} else {
    die('Método inválido.');
}
?>
