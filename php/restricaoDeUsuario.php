<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Connect.php'; 

if (!isset($_SESSION['id_admin'])) {
    header('Location: login_adm.php');
    exit;
}

if ($_SESSION['Nivel_permissao'] === 1) {
    echo 'Este usuario não apresenta nivel de administrador';
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo 'ID não fornecido';
    exit;
} 