<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

$stmt = $pdo->prepare("SELECT * FROM Escolas WHERE id_escolas = ?");
$stmt->execute([$id]);
$escola = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$escola) {
    echo 'Escola não encontrada';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Escola</title>
    <link href="../boostrap/CSS/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-4">
    <h2>Editar Escola</h2>

    <form method="POST" action="Atualizaescolas.php">
        <input type="hidden" name="id" value="<?= $escola['id_escolas'] ?>">
        
        <label class="form-label mt-2">Modalidade</label>
        <select name="Modalidade" class="form-control">
            <?php
            $modalidades = [
                '1' => 'EEEP',
                '2' => 'EEMTI',
                '3' => 'EEM',
                '4' => 'EEMPC',
                '5' => 'CEJA',
                '6' => 'INDÍGINA',
                '7' => 'EMEFTI',
                '8' => 'COLÉGIO',
                '9' => 'EMEF',
                '10' => 'EMEIF',
                '11' => 'EMTI'
            ];
            foreach ($modalidades as $key => $value) {
                $selected = ($escola['id_categoria_escola'] === $key) ? 'selected' : '';
                echo "<option value='$key' $selected>$value</option>";
            }
            ?>
        </select>
        
        <label for="nome" class="form-label">Nome da Instituição</label>
        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($escola['nome']) ?>" required>

        <label class="form-label mt-2">Município</label>
        <select name="municipio" class="form-control">
            <?php
            $municipios = [
                '1' => 'Caridade',
                '2' => 'Canindé',
                '3' => 'Paramoti',
                '4' => 'General Sampaio',
                '5' => 'Santa Quitéria',
                '6' => 'Itatira'
            ];
            foreach ($municipios as $key => $value) {
                $selected = ($escola['municipio'] === $value) ? 'selected' : '';
                echo "<option value='$key' $selected>$value</option>";
            }
            ?>
        </select>

        <label class="form-label mt-2">Tipo</label>
        <select name="focalizada" class="form-control">
            <option value="" <?= empty($escola['focalizada']) ? 'selected' : '' ?>>Selecione...</option>
            <option value="1" <?= ($escola['focalizada'] === 'Focalizada') ? 'selected' : '' ?>>Focalizada</option>
        </select>

        <label class="form-label mt-2">IDE Médio</label>
        <select name="ide" class="form-control">
            <option value="" <?= empty($escola['ide']) ? 'selected' : '' ?>>Selecione...</option>
            <option value="1" <?= ($escola['ide'] === 'Sim') ? 'selected' : '' ?>>Sim</option>
        </select>

        <div id="campo-IDEB">
            <label for="instituação-digitação" class="form-label mt-2">Digite o IDEB</label>
            <input type="number" class="form-control" style="display: flex" step="0.1" value="<?= htmlspecialchars($escola['IDEB']) ?>" name="IDEB">
        </div>

        <div id="campo-total_trabalhos">
            <label for="instituação-digitação" class="form-label mt-2">Digite o numero de trabalhos na etapa escolar</label>
            <input type="number" class="form-control" style="display: flex" step="0.1" value="<?= htmlspecialchars($escola['total_trabalhos']) ?>" name="total_trabalhos">
        </div>

        <input type="submit" value="Atualizar" class="btn btn-success mt-3">
        <a href="../html/admin-escolas.php" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</body>

</html>