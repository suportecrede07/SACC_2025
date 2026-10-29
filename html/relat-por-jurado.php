<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../dompdf/vendor/autoload.php';
require_once '../php/Connect.php';
require_once '../php/CalculoRanking.php';

use Dompdf\Dompdf;

$id_jurado = isset($_GET['id_jurado']) ? intval($_GET['id_jurado']) : 0;
if ($id_jurado <= 0) die("ID do jurado inválido.");

// O cabeçalho exibe apenas uma área, como no relatório original.
// Quando houver mais de uma associação, prioriza uma associação com área
// preenchida e escolhe uma única área de forma determinística.
$juradoQuery = $pdo->prepare(
    "SELECT j.nome, ar.nome_area
     FROM Jurados j
     LEFT JOIN jurados_categorias_areas jcr
        ON jcr.id_jurados = j.id_jurados
     LEFT JOIN Areas ar
        ON ar.id_area = jcr.id_area
     WHERE j.id_jurados = ?
     ORDER BY (ar.nome_area IS NULL) ASC, ar.nome_area ASC
     LIMIT 1"
);
$juradoQuery->execute([$id_jurado]);
$jurado = $juradoQuery->fetch(PDO::FETCH_ASSOC);
if (!$jurado) {
    http_response_code(404);
    exit('Jurado não encontrado.');
}

// Carrega somente critérios ativos e na ordem definida nas Configurações.
$criterios = obterCriteriosAvaliacaoAtivos($pdo);
if (!$criterios) {
    http_response_code(500);
    exit('Não há critérios de avaliação ativos para gerar este relatório.');
}
if (!array_filter($criterios, static fn(array $criterio): bool => (float) $criterio['peso'] > 0)) {
    http_response_code(500);
    exit('Não há critérios ativos com peso maior que zero para calcular as notas.');
}

$criteriosPorId = [];
foreach ($criterios as &$criterio) {
    $criterio['id'] = (int) $criterio['id'];
    $criterio['peso'] = (float) $criterio['peso'];
    $criteriosPorId[$criterio['id']] = $criterio;
}
unset($criterio);

// Busca apenas avaliações de critérios ativos. O ID é usado para relacionar
// cada nota ao critério certo; a ordem de apresentação vem de Criterios_avaliacao.ordem.
$avaliacoesQuery = $pdo->prepare(
    "SELECT
        T.id_trabalhos,
        T.titulo,
        E.nome AS escola,
        A.criterio,
        A.nota,
        ce.categoria_da_escola
     FROM Avaliacoes A
     JOIN Trabalhos T ON A.id_trabalho = T.id_trabalhos
     JOIN Escolas E ON T.id_escolas = E.id_escolas
     LEFT JOIN categoria_escolas ce ON E.id_categoria_escola = ce.id
     JOIN Criterios_avaliacao CA
        ON CA.id = A.criterio AND CA.ativo = 1
     WHERE A.id_jurado = ?
     ORDER BY T.ordem ASC, T.id_trabalhos ASC, CA.ordem ASC, CA.id ASC"
);
$avaliacoesQuery->execute([$id_jurado]);
$avaliacoesRaw = $avaliacoesQuery->fetchAll(PDO::FETCH_ASSOC);

// Agrupa as notas por trabalho sem depender de IDs fixos de critérios.
$trabalhos = [];
foreach ($avaliacoesRaw as $row) {
    $idTrabalho = (int) $row['id_trabalhos'];
    $idCriterio = (int) $row['criterio'];

    // Proteção adicional para não considerar critérios inativos ou não carregados.
    if (!isset($criteriosPorId[$idCriterio])) {
        continue;
    }

    if (!isset($trabalhos[$idTrabalho])) {
        $trabalhos[$idTrabalho] = [
            'titulo' => $row['titulo'],
            'escola' => $row['escola'],
            'Modalidade' => $row['categoria_da_escola'] ?? '',
            'notas' => [],
            'nota_final' => null
        ];
    }

    // A nota fica indexada pelo ID do critério, e a média é calculada pela
    // mesma função usada na classificação centralizada.
    $trabalhos[$idTrabalho]['notas'][$idCriterio] = (float) $row['nota'];
}

// Mantém a fórmula original: soma(nota * peso) / soma dos pesos
// dos critérios ativos que possuem nota, multiplicada por 10.
foreach ($trabalhos as &$trabalho) {
    $somaNotas = 0.0;
    $somaPesos = 0.0;

    foreach ($criterios as $criterio) {
        $idCriterio = (int) $criterio['id'];
        $peso = (float) $criterio['peso'];

        if ($peso <= 0 || !array_key_exists($idCriterio, $trabalho['notas'])) {
            continue;
        }

        $somaNotas += (float) $trabalho['notas'][$idCriterio] * $peso;
        $somaPesos += $peso;
    }

    $trabalho['nota_final'] = $somaPesos > 0
        ? ($somaNotas / $somaPesos) * 10
        : null;
}
unset($trabalho);

function imgBase64(string $path): string
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

$logo1 = imgBase64(__DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png');
$logo2 = imgBase64(__DIR__ . '/../assets/img/crede7.png');
$logo3 = imgBase64(__DIR__ . '/../assets/img/ceara.png');

ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Relatório do Jurado</title>
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
      margin: 12 10px;
    }

    .assinatura {
      margin-top: 50px;
      margin-bottom: 40px;
      text-align: center;
    }
  </style>
</head>

<body>

  <div class="text-center">
    <?php if ($logo1 !== ''): ?>
      <img src="<?= htmlspecialchars($logo1, ENT_QUOTES, 'UTF-8') ?>" style="height:80px;" alt="Ceará Científico">
    <?php endif; ?>
    <p><b>ETAPA REGIONAL - 2026</b></p>
  </div>

  <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
    <div class="header-title">PLANILHA DE AVALIAÇÃO POR JURADO</div>
    <div class="sub-title">JURADO: <?= htmlspecialchars((string) $jurado['nome'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php if (!empty($jurado['nome_area'])): ?>
      <div class="sub-title">ÁREA: <?= htmlspecialchars((string) $jurado['nome_area'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
      <div class="sub-title">Categoria: Pesquisa Júnior</div>
    <?php endif; ?>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Escola</th>
          <th>Título</th>
          <?php foreach ($criterios as $criterio): ?>
            <th><?= htmlspecialchars($criterio['nome']) ?></th>
          <?php endforeach; ?>
          <th>Nota Final</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($trabalhos)): ?>
          <tr>
            <td colspan="<?= count($criterios) + 3 ?>">Nenhuma avaliação encontrada para este jurado.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($trabalhos as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['Modalidade']) . " " . htmlspecialchars($t['escola']) ?></td>
              <td><?= htmlspecialchars($t['titulo']) ?></td>
              <?php foreach ($criterios as $criterio): ?>
                <?php $idCriterio = (int) $criterio['id']; ?>
                <td><?= array_key_exists($idCriterio, $t['notas']) ? number_format($t['notas'][$idCriterio], 2) : '-' ?></td>
              <?php endforeach; ?>
              <td><strong><?= $t['nota_final'] !== null ? number_format($t['nota_final'], 2) : '-' ?></strong></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="assinatura">
    <hr style="width: 40%;">
    <p>Avaliador(a)</p>
  </div>
</body>

</html>

<?php
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

date_default_timezone_set('America/Fortaleza');

$dataServidor = null;
if (class_exists(IntlDateFormatter::class)) {
    $formatter = new IntlDateFormatter(
        'pt_BR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'America/Fortaleza',
        IntlDateFormatter::GREGORIAN,
        "d 'de' MMMM 'de' yyyy"
    );
    $dataServidor = $formatter->format(new DateTime());
}
if ($dataServidor === null || $dataServidor === false) {
    $dataServidor = date('d/m/Y');
}

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont('Helvetica', 'normal');

$pageWidth = $canvas->get_width();
$pageHeight = $canvas->get_height();

$fontSize = 10;
$marginBottom = 30;

$dataText = "Canindé, $dataServidor";
$dataTextWidth = $dompdf->getFontMetrics()->getTextWidth($dataText, $font, $fontSize);
$xData = ($pageWidth / 2) - ($dataTextWidth / 2);
$y = $pageHeight - $marginBottom;

$pageText = "Página {PAGE_NUM} de {PAGE_COUNT}";
$pageTextWidth = $dompdf->getFontMetrics()->getTextWidth($pageText, $font, $fontSize);
$xPageNum = $pageWidth - $pageTextWidth - 40;

$canvas->page_text($xData, $y, $dataText, $font, $fontSize, [0, 0, 0]);
$canvas->page_text($xPageNum, $y, $pageText, $font, $fontSize, [0, 0, 0]);

$dompdf->stream('relatorio_jurado_' . $id_jurado . '.pdf', ['Attachment' => false]);
