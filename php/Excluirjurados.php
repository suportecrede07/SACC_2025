<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

if(!empty($_GET['id'])){
    require_once '../php/Connect.php';
    $id = $_GET['id'] ?? null;

    $stmt1 = $pdo->prepare("DELETE FROM Avaliacoes WHERE id_jurado = ?");
    $stmt1->execute([$id]);
    
    $stmt2 = $pdo -> prepare("DELETE FROM Jurados WHERE id_jurados = ?");
    $stmt2 -> execute([$id]);
    header("Location: ../html/admin-jurados.php"); 
}