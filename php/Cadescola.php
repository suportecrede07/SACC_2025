<?php

require_once '../php/Connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método inválido.');
}

$nome = trim($_POST['nome'] ?? '');
$idFocalizado = $_POST['focalizada'] ?? null;
$focalizada = ($idFocalizado == '1') ? 'Focalizada' : null;
$IDEB = $_POST['IDEB'] ?? null;
$idIde = $_POST['ide'] ?? null;
$ide = ($idIde == '1') ? 'Sim' : null;

$municipios = [
'1' => 'Caridade',
'2' => 'Canindé',
'3' => 'Paramoti',
'4' => 'General Sampaio',
'5' => 'Santa Quitéria',
'6' => 'Itatira'
];
$idMunicipio = $_POST['municipio'] ?? null;
$municipio = $municipios[$idMunicipio] ?? null;
    
if ($IDEB === '') {
    $IDEB = null;
}

if (empty($nome)) {
    die('O nome da escola é obrigatório.');
}

if (empty($idMunicipio)) {
    die('O município é obrigatório.');
}

try {

    $sql = "INSERT INTO Escolas 
            (nome, focalizada, ide, municipio, IDEB, total_trabalhos)
            VALUES (?, ?, ?, ?, ?, 0)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $nome,
        $focalizada,
        $ide,
        $municipio,
        $IDEB
    ]);

    header('Location: ../html/admin-dashboard.php?msg=escola_cadastrada');
    exit();

} catch (PDOException $e) {

    die('Erro ao cadastrar escola: ' . $e->getMessage());

}
?>
