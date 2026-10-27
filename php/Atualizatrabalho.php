<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Connect.php';

$id = $_POST['id'] ?? null;
$titulo = trim($_POST['titulo'] ?? '');
$escola = $_POST['escola'] ?? null;
$categoria = $_POST['categoria'] ?? null;
$area = $_POST['area'] ?? null;
$ordem = $_POST['ordem'] ?? null;

if (!$id || !$titulo || !$escola || !$categoria) {
    die("Campos obrigatórios não preenchidos.");
}

try {

    $stmt = $pdo->prepare("
        UPDATE Trabalhos
        SET
            titulo = :titulo,
            id_escolas = :escola,
            id_categoria = :categoria,
            id_areas = :area,
            ordem = :ordem
        WHERE id_trabalhos = :id
    ");

    $stmt->execute([
        ':titulo' => $titulo,
        ':escola' => $escola,
        ':categoria' => $categoria,
        ':area' => $area ?: null,
        ':ordem' => $ordem !== '' ? $ordem : null,
        ':id' => $id
    ]);

    // Processa os campos dinâmicos recebidos para o trabalho
    if (isset($_POST['campos_dinamicos']) && is_array($_POST['campos_dinamicos'])) {
        $stmtCheck = $pdo->prepare("SELECT id FROM Valores_campos WHERE id_campo = ? AND id_registro = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO Valores_campos (id_campo, id_registro, valor) VALUES (?, ?, ?)");
        $stmtUpdate = $pdo->prepare("UPDATE Valores_campos SET valor = ? WHERE id = ?");
        $stmtDelete = $pdo->prepare("DELETE FROM Valores_campos WHERE id = ?");

        foreach ($_POST['campos_dinamicos'] as $id_campo => $valor) {
            $id_campo = (int)$id_campo;
            $stmtCheck->execute([$id_campo, $id]);
            $registroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($valor !== '' && $valor !== null) {
                $valor = trim($valor);
                if ($registroExistente) {
                    $stmtUpdate->execute([$valor, $registroExistente['id']]);
                } else {
                    $stmtInsert->execute([$id_campo, $id, $valor]);
                }
            } else {
                if ($registroExistente) {
                    $stmtDelete->execute([$registroExistente['id']]);
                }
            }
        }
    }

    header("Location: ../html/admin-trabalhos.php?status=sucesso");
    exit;

} catch (PDOException $e) {
    error_log("Erro ao atualizar trabalho: " . $e->getMessage());
    die("Erro ao atualizar o trabalho: " . $e->getMessage());
}