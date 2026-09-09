<?php

require_once '../php/Connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = $_POST['id_escolas'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $focalizado = ['1' => 'Focalizada'];
    $idFocalizado = $_POST['focalizada'] ?? null;
    $focalizado = $focalizado[$idFocalizado] ?? null;
    $ide = ['1' => 'Sim'];
    $idIde = $_POST['ide'] ?? null;
    $ide = $ide[$idIde] ?? null;
    $IDEB = $_POST['IDEB'] ?? null;
    $municipio = ['1' => 'Caridade', '2' => 'Canindé', '3' => 'Paramoti', '4' => 'General Sampaio', '5' => 'Santa Quitéria', '6' => 'Itatira'];
    $idMunicipio = $_POST['municipio'] ?? null;
    $municipio = $municipio[$idMunicipio] ?? 'Desconhecido';

    if(empty($id) || empty($nome)){
        die('ID e Nome da escola são obrigatórios!');
    }

    $stmt = $pdo -> prepare("UPDATE Escolas SET nome = ?, focalizada = ?, ide = ?, municipio = ?, IDEB = ? WHERE id_escolas = ?");
    try{
        $stmt -> execute([$nome,$focalizado,$ide,$municipio,$IDEB,$id]);
        header('Location: ../html/admin-escolas.php?msg=atualizado');
        exit();
    }catch(PDOException $e){
        die('Erro ao atualizar escola' . $e -> getMessage());
    }
} else {
    die('Método inválido');
}
?>