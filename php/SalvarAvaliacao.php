<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../php/Connect.php';

if (!isset($_SESSION['id_jurados'])) {
    die("Acesso negado.");
}

$id_jurado = $_SESSION['id_jurados'];
$id_trabalho = $_POST['id_trabalho'] ?? null;

if (!$id_trabalho) {
    die("ID do trabalho inválido.");
}

try {
    $stmtCheck = $pdo->prepare("
        SELECT t.id_categoria, t.id_areas, j.avaliacoes_finalizadas
        FROM Trabalhos t
        JOIN Jurados j ON j.id_jurados = ?
        WHERE t.id_trabalhos = ?
    ");
    $stmtCheck->execute([$id_jurado, $id_trabalho]);
    $infoCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($infoCheck) {
        $chaveCheck = ($infoCheck['id_categoria'] ?? 0) . '_' . ($infoCheck['id_areas'] ?? 0);
        $finalizadasCheck = array_filter(explode(',', (string)$infoCheck['avaliacoes_finalizadas']));
        if (in_array($chaveCheck, $finalizadasCheck) || $infoCheck['avaliacoes_finalizadas'] === '1') {
            die("Esta área já foi finalizada e suas avaliações não podem mais ser alteradas.");
        }
    }

    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare("
        INSERT INTO Avaliacoes (id_trabalho, id_jurado, criterio, nota, comentario)
        VALUES (?, ?, ?, ?, ?)
    ");

    // Identifica todos os critérios passados via POST (ex: criterio1, criterio2, etc.)
    $chavesCriterios = [];
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'criterio') === 0) {
            $numCrit = substr($key, 8);
            if (is_numeric($numCrit)) {
                $chavesCriterios[] = (int)$numCrit;
            }
        }
    }
    sort($chavesCriterios);

    if (empty($chavesCriterios)) {
        throw new Exception("Nenhum critério foi enviado para avaliação.");
    }

    foreach ($chavesCriterios as $idCrit) {
        $valorRaw = $_POST["criterio$idCrit"] ?? null;
        $nota = ($valorRaw !== null && $valorRaw !== '') ? floatval(str_replace(',', '.', $valorRaw)) : null;
        $comentario = $_POST["comentario$idCrit"] ?? null;

        if ($nota === null || $nota < 0 || $nota > 10) {
            throw new Exception("Nota inválida no critério $idCrit");
        }

        $stmtInsert->execute([$id_trabalho, $id_jurado, $idCrit, $nota, $comentario]);
    }

    $pdo->commit();
    header("Location: ../html/jurado-dashboard.php?msg=avaliado");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao salvar avaliação: " . $e->getMessage());
}
?>
