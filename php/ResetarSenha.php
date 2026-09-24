<?php
session_start();
require_once '../php/Connect.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idJurado = $_POST['id_jurados'] ?? null;
    if (empty($idJurado)) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'ID do jurado não informado.'
        ]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id_jurados FROM Jurados WHERE id_jurados = ? LIMIT 1");
        $stmt->execute([$idJurado]);

        $jurado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$jurado) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Jurado não encontrado.'
            ]);
            exit();
        }

        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $senhaTemporaria = '';

        for ($i = 0; $i < 6; $i++) {
            $senhaTemporaria .= $caracteres[random_int(0, strlen($caracteres) - 1)];
}

        $stmt = $pdo->prepare("
            UPDATE Jurados
            SET senha = ?, primeiro_acesso = 0
            WHERE id_jurados = ?
        ");

        $stmt->execute([
            $senhaTemporaria,
            $idJurado
        ]);
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Senha resetada com sucesso.',
            'senha_temporaria' => $senhaTemporaria
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao resetar a senha.'
        ]);
        exit();
    }

} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Método de requisição inválido.'
    ]);
    exit();
}
?>