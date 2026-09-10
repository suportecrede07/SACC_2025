<?php
session_start();
require_once 'Connect.php'; // Certifique-se que o caminho está correto

// Verifica se está logado e se passou o id do trabalho
if (!isset($_SESSION['id_jurados']) || !isset($_GET['id_trabalho'])) {
    echo json_encode(['error' => 'Acesso negado ou parâmetros faltando.']);
    exit();
}

$idJurado = $_SESSION['id_jurados'];
$idTrabalho = $_GET['id_trabalho'];

try {
    // Busca na tabela 'avaliacoes' como visto na sua imagem do phpMyAdmin
    $stmt = $pdo->prepare("SELECT criterio, nota, comentario FROM avaliacoes WHERE id_trabalho = ? AND id_jurado = ?");
    $stmt->execute([$idTrabalho, $idJurado]);
    
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = [];

    // Mapeando cada linha do banco de dados para a estrutura esperado no Javascript (criterio1, criterio2, etc)
    foreach ($avaliacoes as $av) {
        $criterioNum = $av['criterio'];
        $response["criterio" . $criterioNum] = [
            'nota' => $av['nota'],
            'comentario' => $av['comentario']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar dados no banco: ' . $e->getMessage()]);
}
?>