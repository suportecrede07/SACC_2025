<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

if(!empty($_GET['id'])){
    require_once '../php/Connect.php';
    $id = $_GET['id'] ?? null;
    
    $stmt = $pdo -> prepare("DELETE FROM Escolas WHERE id_escolas = ?");
    $stmt -> execute([$id]);
    header("Location: ../html/admin-escolas.php");
}
?>