<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_regra       = trim($_POST['nome_regra'] ?? '');
    $origem_dado      = trim($_POST['origem_dado'] ?? '');
    $ordem_prioridade = isset($_POST['ordem_prioridade']) && $_POST['ordem_prioridade'] !== '' ? (int)$_POST['ordem_prioridade'] : 1;
    $direcao          = (isset($_POST['direcao']) && $_POST['direcao'] === 'ASC') ? 'ASC' : 'DESC';
    $ativo            = 1;

    if (empty($nome_regra)) {
        die('Preencha o título da regra de desempate.');
    }
    if (empty($origem_dado)) {
        die('Selecione qual dado será usado para o desempate.');
    }

    // Identifica se a origem é um critério de jurado (crit_X) ou um campo dinâmico (campo_X)
    $id_criterio_avaliacao = null;
    $id_campo_formulario   = null;

    if (str_starts_with($origem_dado, 'crit_')) {
        $id_criterio_avaliacao = (int)str_replace('crit_', '', $origem_dado);
    } elseif (str_starts_with($origem_dado, 'campo_')) {
        $id_campo_formulario = (int)str_replace('campo_', '', $origem_dado);
    }

    try {
        $sql = "INSERT INTO Criterios_desempate (ordem_prioridade, nome_regra, id_criterio_avaliacao, id_campo_formulario, direcao, ativo) 
                VALUES (:ordem_prioridade, :nome_regra, :id_criterio_avaliacao, :id_campo_formulario, :direcao, :ativo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ordem_prioridade'      => $ordem_prioridade,
            ':nome_regra'            => $nome_regra,
            ':id_criterio_avaliacao' => $id_criterio_avaliacao,
            ':id_campo_formulario'   => $id_campo_formulario,
            ':direcao'               => $direcao,
            ':ativo'                 => $ativo
        ]);

        header("Location: ../html/admin-config.php?tab=desempate&msg=desempate_salvo");
        exit();

    } catch (Exception $e) {
        die('Erro ao adicionar regra de desempate: ' . $e->getMessage());
    }
} else {
    die('Método inválido.');
}
?>
