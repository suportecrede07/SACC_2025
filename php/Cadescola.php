<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../php/Connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método inválido.');
}

$nome = trim($_POST['nome'] ?? '');
$idFocalizado = $_POST['focalizada'] ?? null;
$focalizada = ($idFocalizado == '1') ? 'Focalizada' : null;
$IDEB = isset($_POST['IDEB']) && $_POST['IDEB'] !== '' ? $_POST['IDEB'] : null;
$total_trabalhos = isset($_POST['total_trabalhos']) && $_POST['total_trabalhos'] !== '' ? $_POST['total_trabalhos'] : 0;
$idIde = $_POST['ide'] ?? null;
$ide = ($idIde == '1') ? 'Sim' : null;
$categoriaEscola = $_POST['categoriaEscola'] ?? null;

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

if (empty($nome)) {
    die('O nome da escola é obrigatório.');
}

if (empty($idMunicipio)) {
    die('O município é obrigatório.');
}

try {

    $sql = "INSERT INTO Escolas 
            (nome, focalizada, ide, municipio, IDEB, total_trabalhos, id_categoria_escola)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $nome,
        $focalizada,
        $ide,
        $municipio,
        $IDEB,
        $total_trabalhos,
        $categoriaEscola
    ]);

    // Recupera o ID da escola recém-cadastrada
    $id_escola = $pdo->lastInsertId();

    // Grava as respostas dos campos dinâmicos na tabela Valores_campos
    if (!empty($_POST['campos_dinamicos']) && is_array($_POST['campos_dinamicos']) && !empty($id_escola)) {
        $stmtCampos = $pdo->prepare("INSERT INTO Valores_campos (id_campo, id_registro, valor) VALUES (?, ?, ?)");
        foreach ($_POST['campos_dinamicos'] as $id_campo => $valor) {
            if ($valor !== '' && $valor !== null) {
                $stmtCampos->execute([
                    (int)$id_campo,
                    (int)$id_escola,
                    trim($valor)
                ]);
            }
        }
    }

    header('Location: ../html/admin-dashboard.php?msg=escola_cadastrada');
    exit();

} catch (PDOException $e) {

    die('Erro ao cadastrar escola: ' . $e->getMessage());

}
?>
