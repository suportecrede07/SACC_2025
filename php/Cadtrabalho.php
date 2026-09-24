<?php

require_once '../php/Connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $titulo = trim($_POST['titulo'] ?? '');
    $id_escolas = $_POST['escola'] ?? null;
    $id_jurados = $_POST['id_jurados'] ?? null;
    $id_area = $_POST['area'] ?? null;
    $id_categoria = $_POST['categoria'] ?? null;

    try {

        $stmt = $pdo->prepare("
            SELECT e.id_categoria_escola, ce.categoria_da_escola
            FROM Escolas e
            INNER JOIN Categoria_escolas ce ON e.id_categoria_escola = ce.id
            WHERE e.id_escolas = ?
        ");
        $stmt->execute([$id_escolas]);
        $escola = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$escola) {
            die('Escola não encontrada.');
        }

        if (empty($escola['id_categoria_escola'])) {
            die('A escola não possui uma modalidade cadastrada.');
        }

        $modalidade = (int)$escola['id_categoria_escola'];

        $categoriasPermitidas = [
            1 => [1, 4], // EEEP
            2 => [1, 4], // EEMTI
            3 => [1, 4], // EEM
            4 => [2, 1, 4], // EEMPC
            5 => [1, 4], // CEJA
            6 => [2, 4], // INDÍGENA
            7 => [3, 4]  // MUNICIPAL
        ];

        if (!isset($categoriasPermitidas[$modalidade])) {
            die('Modalidade da escola não configurada.');
        }

        if (!in_array((int)$id_categoria, $categoriasPermitidas[$modalidade], true)) {
            die(
                'Erro: a categoria selecionada não é permitida para a modalidade ' .
                $escola['categoria_da_escola'] . '.'
            );
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM Trabalhos
            WHERE id_escolas = ? AND id_areas = ?
        ");
        $stmt->execute([$id_escolas, $id_area]);

        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            die('Esta escola já possui um trabalho cadastrado nesta área.');
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM Trabalhos
            WHERE id_escolas = ? AND LOWER(TRIM(titulo)) = LOWER(TRIM(?))
        ");
        $stmt->execute([$id_escolas, $titulo]);

        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            die('Este trabalho já está cadastrado para esta escola em outra área.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO Trabalhos
            (titulo, id_escolas, id_jurados, id_areas, id_categoria)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $titulo,
            $id_escolas,
            $id_jurados,
            $id_area,
            $id_categoria
        ]);

        $pdo->commit();

        header('Location: ../html/admin-dashboard.php?msg=sucesso');
        exit();

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die("Erro ao cadastrar Trabalho: " . $e->getMessage());
    }
}

?>