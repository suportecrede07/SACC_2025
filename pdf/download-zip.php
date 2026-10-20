<?php
$nomeZip = basename($_GET['file'] ?? '');
$caminhoZip = sys_get_temp_dir() . '/' . $nomeZip;

if (!empty($nomeZip) && file_exists($caminhoZip)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $nomeZip . '"');
    header('Content-Length: ' . filesize($caminhoZip));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($caminhoZip);
    @unlink($caminhoZip);
    exit;
} else {
    echo "Arquivo não encontrado ou expirado.";
}