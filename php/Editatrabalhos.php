<?php
require_once __DIR__ . '/restricaoDeUsuario.php';

$stmt = $pdo->prepare("
    SELECT 
        titulo,
        id_escolas,
        id_jurados,
        id_areas,
        id_categoria,
        ordem
    FROM Trabalhos
    WHERE id_trabalhos = ?
");
$stmt->execute([$id]);
$trabalho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabalho) {
    die('Trabalho não encontrado!');
}

$stmtEscolas = $pdo->query("SELECT id_escolas, nome FROM Escolas ORDER BY nome");
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Trabalho</title>
    <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
    <script src="../boostrap/JS/jquery.min.js"></script>
    <script src="../boostrap/JS/bootstrap.bundle.min.js"></script>
</head>
<body class="container mt-4">
    <h2>Editar Trabalho</h2>
    <form action="../php/Atualizatrabalho.php" method="POST" id="formEditaTrabalho">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label for="titulo">Título</label>
        <input type="text" class="form-control" name="titulo" required value="<?= htmlspecialchars($trabalho['titulo']) ?>">

        <label for="escola">Escola</label>
        <select name="escola" class="form-control" required>
            <option value="">Selecione a Escola</option>
            <?php foreach($escolas as $escola): ?>
                <option value="<?= $escola['id_escolas'] ?>" <?= $trabalho['id_escolas'] == $escola['id_escolas'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($escola['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="categoria">Categoria</label>
        <select name="categoria" class="form-control" required>
            <option value="">Selecione a Categoria</option>
            <option value="1" <?= $trabalho['id_categoria'] == 1 ? 'selected' : '' ?>>I - Ensino Médio</option>
            <option value="2" <?= $trabalho['id_categoria'] == 2 ? 'selected' : '' ?>>II - Ensino Médio - Ações Afirmativas e CEJAs EM</option>
            <option value="3" <?= $trabalho['id_categoria'] == 3 ? 'selected' : '' ?>>III - Pesquisa Júnior</option>
            <option value="4" <?= $trabalho['id_categoria'] == 4 ? 'selected' : '' ?>>IV - PcD</option>
        </select>

        <label for="area">Área</label>
        <select name="area" class="form-control">
            <option value="">Selecione a Área</option>
            <option value="1" <?= $trabalho['id_areas'] == 1 ? 'selected' : '' ?>>Linguagens, Códigos e suas Tecnologias - LC</option>
            <option value="2" <?= $trabalho['id_areas'] == 2 ? 'selected' : '' ?>>Matemática e suas Tecnologias - MT</option>
            <option value="3" <?= $trabalho['id_areas'] == 3 ? 'selected' : '' ?>>Ciências da Natureza, Educação Ambiental e Engenharias - CN</option>
            <option value="4" <?= $trabalho['id_areas'] == 4 ? 'selected' : '' ?>>Ciências Humanas e Sociais Aplicadas - CH</option>
            <option value="5" <?= $trabalho['id_areas'] == 5 ? 'selected' : '' ?>>Robótica, Automação e Aplicação das TIC</option>
            <option value="6" <?= $trabalho['id_areas'] == 6 ? 'selected' : '' ?>>Ensino Fundamental</option>
            <option value="7" <?= $trabalho['id_areas'] == 7 ? 'selected' : '' ?>>Ensino Médio</option>
        </select>

        <label for="titulo">Ordem do trabalho</label>
        <input type="text" class="form-control" name="ordem" required value="<?= htmlspecialchars($trabalho['ordem']) ?>">

        <input type="submit" class="btn btn-success mt-3" value="Atualizar">
        <a href="../html/admin-trabalhos.php" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</body>
</html>
