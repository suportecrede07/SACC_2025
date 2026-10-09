
<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php';

if (
    !isset($_SESSION['id_admin']) ||
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['Nivel_permissao'])
) {
    header('Location: login_adm.php');
    exit;
}

if ((int)$_SESSION['Nivel_permissao'] === 1) {
    echo 'Este usuário não apresenta nível de administrador';
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo 'ID não fornecido';
    exit;
}