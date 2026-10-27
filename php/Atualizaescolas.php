<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../php/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome'] ?? '');
    $modalidade = $_POST['Modalidade'] ?? null;

    $municipios = [
        '1' => 'Caridade',
        '2' => 'Canindé',
        '3' => 'Paramoti',
        '4' => 'General Sampaio',
        '5' => 'Santa Quitéria',
        '6' => 'Itatira'
    ];
    $idMunicipio = $_POST['municipio'] ?? null;
    $municipio = $municipios[$idMunicipio] ?? 'Desconhecido';

    if (empty($id) || empty($nome)) {
        die('ID e Nome da escola são obrigatórios!');
    }

    try {
        // Atualiza os dados principais da instituição
        $stmt = $pdo->prepare("UPDATE Escolas SET nome = ?, municipio = ?, id_categoria_escola = ? WHERE id_escolas = ?");
        $stmt->execute([$nome, $municipio, $modalidade, $id]);

        // Processa os campos dinâmicos recebidos
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
                    // Se o campo ficou vazio e já existia registro, remove
                    if ($registroExistente) {
                        $stmtDelete->execute([$registroExistente['id']]);
                    }
                }
            }
        }

        header('Location: ../html/admin-escolas.php?msg=atualizado');
        exit();
    } catch (PDOException $e) {
        die('Erro ao atualizar escola: ' . $e->getMessage());
    }
} else {
    die('Método inválido');
}