<?php
session_start();
require_once '../php/Connect.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_jurados'])) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Sessão do jurado não encontrada.'
    ]);

    exit();
}

$idJurado = $_SESSION['id_jurados'];

try {
    $stmt = $pdo->prepare("
        UPDATE Jurados
        SET avaliacoes_finalizadas = 1
        WHERE id_jurados = ?
    ");

    $stmt->execute([$idJurado]);

    echo json_encode([
        'status' => 'sucesso'
    ]);
    exit();

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao finalizar as avaliações.'
    ]);
    exit();
}
?>