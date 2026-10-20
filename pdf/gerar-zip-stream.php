<?php
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(600); // Mantém um tempo limite maior para lidar com o volume completo de arquivos

require_once '../php/Connect.php';
require_once 'gerar-pdf-string.php';

header('Content-Type: application/json');

try {
    $trabalhos = $pdo->query("
      SELECT 
        t.id_trabalhos,
        t.titulo,
        e.nome AS escola,
        ce.categoria_da_escola
      FROM Trabalhos t
      LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
      LEFT JOIN categoria_escolas ce ON e.id_categoria_escola = ce.id
      ORDER BY t.ordem ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($trabalhos)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum trabalho encontrado.']);
        exit;
    }

    $nomeZip = "Fichas_Avaliacao_Escolas_" . time() . ".zip";
    $caminhoZip = sys_get_temp_dir() . '/' . $nomeZip;

    $zip = new ZipArchive();

    if ($zip->open($caminhoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($trabalhos as $t) {
            $idTrabalho = $t['id_trabalhos'];
            $nomeEscola = $t['escola'] ?? '';
            $categoriaEscola = $t['categoria_da_escola'] ?? '';
            $tituloTrabalho = $t['titulo'] ?? 'Trabalho';

            if (empty($nomeEscola)) {
                continue;
            }

            $stmtJurados = $pdo->prepare("
                SELECT j.id_jurados, j.nome 
                FROM Jurados j
                INNER JOIN jurado_trabalho jt ON j.id_jurados = jt.id_jurado
                WHERE jt.id_trabalho = :id_trabalho
                ORDER BY j.nome
            ");
            $stmtJurados->execute(['id_trabalho' => $idTrabalho]);
            $juradosDoTrabalho = $stmtJurados->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($juradosDoTrabalho)) {
                $escolaSanitizada = preg_replace('/[^A-Za-z0-9\-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $nomeEscola));
                $categoriaSanitizada = !empty($categoriaEscola) ? preg_replace('/[^A-Za-z0-9\-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $categoriaEscola)) . '_' : '';
                $trabalhoSanitizado = preg_replace('/[^A-Za-z0-9\-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', substr($tituloTrabalho, 0, 25)));
                $nomePastaEscola = $categoriaSanitizada . $escolaSanitizada;

                foreach ($juradosDoTrabalho as $j) {
                    $juradoSanitizado = preg_replace('/[^A-Za-z0-9\-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $j['nome']));
                    $nomeArquivoPdf = "Trabalho_" . $idTrabalho . "_" . $trabalhoSanitizado . "_Jurado_" . $juradoSanitizado . ".pdf";
                    $caminhoNoZip = "pasta/" . $nomePastaEscola . "/" . $nomeArquivoPdf;

                    $pdfConteudoReal = obterPdfComoString($pdo, $idTrabalho, $j['id_jurados']);
                    if (!empty($pdfConteudoReal)) {
                        $zip->addFromString($caminhoNoZip, $pdfConteudoReal);
                    }
                }
            }
        }

        $zip->close();

        if (file_exists($caminhoZip) && filesize($caminhoZip) > 22) {
            $urlDownload = "../pdf/download-zip.php?file=" . urlencode($nomeZip);
            echo json_encode(['success' => true, 'downloadUrl' => $urlDownload]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Erro ao criar o arquivo ZIP.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}