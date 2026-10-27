<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Suporta tanto nome_criterio quanto nome
    $nome_criterio = trim($_POST['nome_criterio'] ?? $_POST['nome'] ?? '');
    $desc_criterio_avaliativo = trim($_POST['desc_criterio_avaliativo'] ?? $_POST['descricao'] ?? '');
    $peso = isset($_POST['peso']) && $_POST['peso'] !== '' ? (float)$_POST['peso'] : 1.00;
    $nota_minima = isset($_POST['nota_minima']) && $_POST['nota_minima'] !== '' ? (float)$_POST['nota_minima'] : 0.00;
    $nota_maxima = isset($_POST['nota_maxima']) && $_POST['nota_maxima'] !== '' ? (float)$_POST['nota_maxima'] : 10.00;
    $ordem = isset($_POST['ordem']) && $_POST['ordem'] !== '' ? (int)$_POST['ordem'] : 1;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome_criterio)) {
        die('Preencha o campo nome.');
    }
    if (empty($desc_criterio_avaliativo)) {
        die('Preencha o campo de descrição.');
    }

    try {
        $sql = "INSERT INTO Criterios_avaliacao (nome, descricao, peso, nota_minima, nota_maxima, ordem, ativo)
                VALUES (:nome, :descricao, :peso, :nota_minima, :nota_maxima, :ordem, :ativo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome_criterio,
            ':descricao' => $desc_criterio_avaliativo,
            ':peso' => $peso,
            ':nota_minima' => $nota_minima,
            ':nota_maxima' => $nota_maxima,
            ':ordem' => $ordem,
            ':ativo' => $ativo
        ]);

        header("Location: ../html/admin-config.php?tab=criterios&msg=criterio_salvo");
        exit();

    } catch (Exception $e) {
        die('Erro ao cadastrar critério avaliativo: ' . $e->getMessage());
    }
}
?>