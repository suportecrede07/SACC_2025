<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe e sanitiza os dados enviados pelo modal do campo dinâmico
    $formulario  = trim($_POST['formulario'] ?? '');
    $tipo_campo  = trim($_POST['tipo_campo'] ?? '');
    $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;
    $ativo       = 1; // Campo novo cadastrado como ativo por padrão
    // Pega o nome do campo vindo do formulário
    $nome_campo = $_POST['nome_campo'] ?? ''; 

    // Gera um identificador removendo espaços, acentos e deixando minúsculo
    $identificador = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $nome_campo)));

    // Adiciona um número único no final para garantir que nunca vai repetir
    $identificador = $identificador . '_' . uniqid(); 

    // Agora você usa essa variável $identificador no seu SQL de INSERT

    // Validações básicas
    if (empty($formulario)) {
        die('Selecione o formulário em que o campo vai aparecer.');
    }
    if (empty($nome_campo)) {
        die('Preencha o nome do campo.');
    }
    if (empty($tipo_campo)) {
        die('Selecione o tipo de resposta do campo.');
    }
    

   try {
        // O campo 'identificador' foi adicionado no SQL
        $sql = "INSERT INTO Campos_formulario (formulario, nome_campo, identificador, tipo_campo, obrigatorio, ativo)
                VALUES (:formulario, :nome_campo, :identificador, :tipo_campo, :obrigatorio, :ativo)";
        $stmt = $pdo->prepare($sql);
        
        // A variável $identificador foi adicionada ao array de execução
        $stmt->execute([
            ':formulario'    => $formulario,
            ':nome_campo'    => $nome_campo,
            ':identificador' => $identificador, 
            ':tipo_campo'    => $tipo_campo,
            ':obrigatorio'   => $obrigatorio,
            ':ativo'         => $ativo
        ]);

        header("Location: ../html/admin-config.php?tab=campos&msg=campo_salvo");
        exit();

    } catch (Exception $e) {
        die('Erro ao cadastrar campo dinâmico: ' . $e->getMessage());
    }
}
?>