<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../php/Connect.php';
require_once __DIR__ . '/../php/CalculoRanking.php';
require_once __DIR__ . '/../dompdf/autoload.inc.php';

use Dompdf\Dompdf;

function toBase64Image(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $data = file_get_contents($path);
    if ($data === false) {
        return '';
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
    $mime = $mimeTypes[$extension] ?? 'application/octet-stream';

    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

// Imagens utilizadas no cabeçalho.
$imgCearaCientifico = toBase64Image(__DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png');
$imgCrede7 = toBase64Image(__DIR__ . '/../assets/img/crede7.png');
$imgCeara = toBase64Image(__DIR__ . '/../assets/img/ceara.png');

// Usa a lista centralizada de critérios ativos e sua ordem configurada.
try {
    $criteriosConfigurados = obterCriteriosAvaliacaoAtivos($pdo);
} catch (Throwable $e) {
    error_log('Erro ao carregar critérios do relatório dos jurados: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar os critérios de avaliação.');
}

$criterios = [];
foreach ($criteriosConfigurados as $criterio) {
    $criterios[(int) $criterio['id']] = (string) $criterio['nome'];
}

if (!$criterios) {
    http_response_code(500);
    exit('Não há critérios de avaliação ativos para gerar este relatório.');
}

// Filtros via URL.
$catId = isset($_GET['catId']) && is_numeric($_GET['catId']) ? (int) $_GET['catId'] : 0;
$areaId = isset($_GET['areaId']) && is_numeric($_GET['areaId']) ? (int) $_GET['areaId'] : 0;

// Recupera as avaliações dos critérios ativos. A ordem apresentada continua
// sendo a ordem dos trabalhos cadastrada, não a ordem do ranking.
$sql = "
SELECT
    t.id_trabalhos,
    t.titulo,
    t.ordem,
    e.nome AS escola,
    j.id_jurados,
    j.usuario AS user_jurado,
    av.criterio,
    av.nota,
    c.nome_categoria AS categoria,
    a.nome_area AS area,
    ce.categoria_da_escola
FROM Trabalhos t
LEFT JOIN Escolas e ON e.id_escolas = t.id_escolas
LEFT JOIN Avaliacoes av ON av.id_trabalho = t.id_trabalhos
LEFT JOIN Jurados j ON j.id_jurados = av.id_jurado
LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
LEFT JOIN Areas a ON t.id_areas = a.id_area
LEFT JOIN categoria_escolas ce ON e.id_categoria_escola = ce.id
LEFT JOIN Criterios_avaliacao ca ON ca.id = av.criterio AND ca.ativo = 1
WHERE 1 = 1
";

$params = [];
if ($catId > 0) {
    $sql .= ' AND t.id_categoria = ?';
    $params[] = $catId;
}
if ($areaId > 0) {
    $sql .= ' AND t.id_areas = ?';
    $params[] = $areaId;
}

$sql .= ' ORDER BY t.ordem ASC, t.id_trabalhos ASC, ca.ordem ASC, ca.id ASC, j.id_jurados ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza as notas por trabalho, critério e jurado; notas ausentes não viram zero.
$trabalhos = [];
$juradosPorCriterio = [];
foreach ($dados as $linha) {
    $idTrabalho = (int) $linha['id_trabalhos'];
    $idJurado = $linha['id_jurados'] !== null ? (int) $linha['id_jurados'] : null;
    $idCriterio = $linha['criterio'] !== null ? (int) $linha['criterio'] : null;

    if (!isset($trabalhos[$idTrabalho])) {
        $trabalhos[$idTrabalho] = [
            'titulo' => (string) ($linha['titulo'] ?? ''),
            'escola' => (string) ($linha['escola'] ?? ''),
            'Modalidade' => (string) ($linha['categoria_da_escola'] ?? ''),
            'categoria' => (string) ($linha['categoria'] ?? 'Sem categoria'),
            'area' => (string) ($linha['area'] ?? 'Sem área'),
            'notas' => [],
        ];
    }

    // Só associa notas que pertencem a critérios ativos e jurados identificados.
    if (
        $idCriterio !== null &&
        isset($criterios[$idCriterio]) &&
        $idJurado !== null &&
        $linha['nota'] !== null
    ) {
        $trabalhos[$idTrabalho]['notas'][$idCriterio][$idJurado] = (float) $linha['nota'];
        $juradosPorCriterio[$idCriterio][$idJurado] = (string) ($linha['user_jurado'] ?? 'Sem jurado');
    }
}

// Usa a mesma nota final do dashboard e dos relatórios de classificação.
// A função central considera os dois primeiros jurados por ID e aplica os
// pesos dos critérios ativos. Seu resultado é indexado sem alterar a ordem
// de apresentação deste relatório informativo.
$notasFinaisPorTrabalho = [];
try {
    $classificacaoCentral = obterClassificacaoTrabalhos(
        $pdo,
        $catId > 0 ? $catId : null,
        $areaId > 0 ? $areaId : null,
        true
    );

    foreach ($classificacaoCentral as $trabalhoCentral) {
        $notasFinaisPorTrabalho[(int) $trabalhoCentral['id_trabalho']] = $trabalhoCentral['media_final'] ?? null;
    }
} catch (Throwable $e) {
    error_log('Erro ao calcular notas finais do relatório dos jurados: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível calcular as notas finais. Verifique os critérios e as regras de desempate configuradas.');
}

// HTML.
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório dos Jurados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 10px;
        }
        .text-center {
            text-align: center;
        }
        .table-container {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        table {
            border-collapse: collapse;
            font-size: 9px;
            width: 100%;
            max-width: 1200px;
        }
        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        th {
            background-color: #499472;
            color: white;
            font-size: 10px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
        }
        .sub-title {
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
        }
        .footer-images {
            text-align: center;
            margin-top: 20px;
        }
        .footer-images img,
        .header-image {
            max-width: 130px;
            display: inline-block;
            margin: 12px 10px;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <?php if ($imgCearaCientifico !== ''): ?>
            <img src="<?= htmlspecialchars($imgCearaCientifico, ENT_QUOTES, 'UTF-8') ?>" style="height:80px;" alt="Ceará Científico">
        <?php endif; ?>
        <p><b>ETAPA REGIONAL - 2026</b></p>
    </div>

    <?php
    $primeiroTrabalho = reset($trabalhos);
    $categoria = $primeiroTrabalho['categoria'] ?? 'Sem categoria';
    $area = $primeiroTrabalho['area'] ?? 'Sem área';
    ?>

    <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
        <div class="header-title">PLANILHA DE AVALIAÇÃO DOS JURADOS</div>
        <div class="sub-title">CATEGORIA: <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($categoria !== 'Pesquisa Júnior'): ?>
            <div class="sub-title">ÁREA: <?= htmlspecialchars($area, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <?php
    $totalColunas = 3; // Escola, título e nota final.
    foreach ($criterios as $idCriterio => $_nomeCriterio) {
        $totalColunas += count($juradosPorCriterio[$idCriterio] ?? [0 => 'Jurado 1', 1 => 'Jurado 2']);
    }
    ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Escola</th>
                    <th rowspan="2">Título</th>
                    <?php foreach ($criterios as $idCriterio => $nomeCriterio): ?>
                        <?php $juradosDesteCriterio = $juradosPorCriterio[$idCriterio] ?? [0 => 'Jurado 1', 1 => 'Jurado 2']; ?>
                        <th colspan="<?= count($juradosDesteCriterio) ?>"><?= htmlspecialchars($nomeCriterio, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2">Nota Final</th>
                </tr>
                <tr>
                    <?php foreach ($criterios as $idCriterio => $_nomeCriterio): ?>
                        <?php
                        $juradosDesteCriterio = $juradosPorCriterio[$idCriterio] ?? [0 => 'Jurado 1', 1 => 'Jurado 2'];
                        foreach ($juradosDesteCriterio as $nomeJurado):
                        ?>
                            <th><?= htmlspecialchars((string) $nomeJurado, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$trabalhos): ?>
                    <tr>
                        <td colspan="<?= $totalColunas ?>">Nenhum trabalho encontrado para os filtros selecionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trabalhos as $idTrabalho => $trabalho): ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($trabalho['Modalidade'] . ' ' . $trabalho['escola']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <?php foreach ($criterios as $idCriterio => $_nomeCriterio): ?>
                                <?php
                                $notasCriterio = $trabalho['notas'][$idCriterio] ?? [];
                                $juradosDesteCriterio = $juradosPorCriterio[$idCriterio] ?? [0 => 'Jurado 1', 1 => 'Jurado 2'];
                                foreach ($juradosDesteCriterio as $idJurado => $_nomeJurado):
                                    $nota = $notasCriterio[$idJurado] ?? null;
                                ?>
                                    <td><?= $nota === null ? '-' : htmlspecialchars(number_format((float) $nota, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php $notaFinal = $notasFinaisPorTrabalho[(int) $idTrabalho] ?? null; ?>
                            <td><?= $notaFinal === null ? '-' : number_format((float) $notaFinal, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', false);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(720, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 10, [0, 0, 0]);
$dompdf->stream('relatorio_jurados.pdf', ['Attachment' => false]);
exit;
