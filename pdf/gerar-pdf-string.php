<?php
// html/gerar-pdf-string.php

require_once '../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Função para gerar o conteúdo binário bruto do PDF usando Dompdf
 * sem interromper scripts pai com die(), exit; ou stream().
 */
function obterPdfComoString($pdo, $id_trabalho, $id_jurado) {
    // 1. Busca os dados do Trabalho
    $sql = "SELECT 
                t.titulo, 
                e.nome AS escola, 
                c.nome_categoria AS categoria,
                a.nome_area AS area
            FROM Trabalhos t
            LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
            LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
            LEFT JOIN Areas a ON t.id_areas = a.id_area
            WHERE t.id_trabalhos = :id_trabalho";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_trabalho' => $id_trabalho]);
    $trabalho = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trabalho) {
        return "%PDF-1.4 ... Erro: Trabalho $id_trabalho nao encontrado.";
    }

    // 2. Busca os dados do Jurado
    $sql_jurado = "SELECT nome FROM Jurados WHERE id_jurados = :id_jurado";
    $stmt_j = $pdo->prepare($sql_jurado);
    $stmt_j->execute(['id_jurado' => $id_jurado]);
    $jurado = $stmt_j->fetch(PDO::FETCH_ASSOC);

    if (!$jurado) {
        return "%PDF-1.4 ... Erro: Jurado $id_jurado nao encontrado.";
    }

    // 3. Busca as Avaliações
    $sql_avaliacoes = "SELECT criterio, nota, comentario FROM Avaliacoes 
                       WHERE id_trabalho = :id_trabalho AND id_jurado = :id_jurado 
                       ORDER BY criterio";
    $stmt2 = $pdo->prepare($sql_avaliacoes);
    $stmt2->execute(['id_trabalho' => $id_trabalho, 'id_jurado' => $id_jurado]);
    $avaliacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $criterios = [
        1 => "Criatividade e Inovação",
        2 => "Relevância da pesquisa",
        3 => "Conhecimento científico fundamentado e contextualização do problem abordado",
        4 => "Impacto para a construção de uma sociedade que promova Ciência, Cidadania e Convivência Democrática: o conhecimento a serviço da vida coletiva",
        5 => "Materiais e métodos conectados com os objetivos, resultados e conclusões",
        6 => "Clareza e objetividade na linguagem apresentada",
        7 => "Banner",
        8 => "Caderno de campo",
        9 => "Processo participativo e solidário"
    ];

    $avaliacoesIndexadas = [];
    foreach ($avaliacoes as $av) {
        $avaliacoesIndexadas[$av['criterio']] = $av;
    }

    // Função interna para conversão de imagens locais para Base64
    $toBase64 = function($path) {
        if (!file_exists($path)) return '';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        if ($data === false) return '';
        return "data:image/$type;base64," . base64_encode($data);
    };

    // Ajuste dos caminhos das imagens apontando a partir do diretório atual deste arquivo
    $imgCearaCientifico = $toBase64(__DIR__.'/../assets/img/crede-ceara-cientifico-estado.png');

    // Inicia a captura do HTML do layout da ficha
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Avaliação</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            .text-center { text-align: center; }
            .header-title { font-size: 14px; font-weight: bold; }
            .sub-title { font-size: 11px; font-weight: bold; margin: 2px 0; }
            table thead th { background-color: #499472; color: white; font-weight: 700; padding: 10px; border: 1px solid #3e8e41; text-align: left; }
            table tbody td { vertical-align: top; word-wrap: break-word; word-break: break-word; white-space: pre-wrap; max-width: 400px; padding: 8px; border: 1px solid #ddd; }
            table { width: 100%; border-collapse: collapse; }
        </style>
    </head>
    <body>
        <div class="text-center">
            <?php if (!empty($imgCearaCientifico)): ?>
                <img src="<?= $imgCearaCientifico ?>" style="height: 80px;"><br>
            <?php endif; ?>
            <p><b>ETAPA REGIONAL - 2026</b></p>
        </div>

        <div class="text-center" style="background-color:#198754; color:#fff; padding:6px; margin-bottom: 25px;">
            <div class="header-title">RELATÓRIO INDIVIDUAL DE AVALIAÇÃO</div>
            <div class="sub-title">JURADO: <?= htmlspecialchars($jurado['nome']) ?></div>
            <div class="sub-title">TÍTULO: <?= htmlspecialchars($trabalho['titulo']) ?></div>
            <div class="sub-title">ESCOLA: <?= htmlspecialchars($trabalho['escola']) ?></div>
            <div class="sub-title">CATEGORIA: <?= htmlspecialchars($trabalho['categoria']) ?> | ÁREA: <?= htmlspecialchars($trabalho['area']) ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Critério</th>
                    <th>Avaliação</th>
                    <th>Comentário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($criterios as $num => $nome): ?>
                    <tr>
                        <td style="font-size: 10px;"><b><?= htmlspecialchars($nome) ?></b></td>
                        <td><?= isset($avaliacoesIndexadas[$num]) ? number_format($avaliacoesIndexadas[$num]['nota'], 2, ',', '.') : '0,00' ?></td>
                        <td style="font-size: 8px;"><?= isset($avaliacoesIndexadas[$num]) ? nl2br(htmlspecialchars($avaliacoesIndexadas[$num]['comentario'])) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Retorna a string binária do PDF puro (Não executa stream ou exit)
    return $dompdf->output(); 
}
