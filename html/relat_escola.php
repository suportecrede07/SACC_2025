<?php
// tratamento de erros 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// importação do banco e da biblioteca do dompdf
require_once '../php/Connect.php';
require_once '../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
// verificação do id fornecido
if (!isset($_GET['id_escola']) || !is_numeric($_GET['id_escola'])) die("id_escola não foi fornecido.");

$id_escola = (int) $_GET['id_escola'];

$sql = "SELECT
        t.id_trabalhos AS id_trabalho,
        t.titulo,
        j.id_jurados,
        j.usuario AS nome_jurado,
        a.nome_area AS area,
        av.criterio,
        av.nota
    FROM Trabalhos t
    LEFT JOIN Avaliacoes av ON av.id_trabalho = t.id_trabalhos
    LEFT JOIN Jurados j ON j.id_jurados = av.id_jurado
    LEFT JOIN Areas a ON t.id_areas = a.id_area
    WHERE t.id_escolas = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_escola]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$dados) die("Verifique se o id_escola existe no banco de dados, ou se a escola possue trabalhos atrelados a ela.");

$trabalhos = [];

// Pesos dos critérios
$pesos = [
  1 => 1,
  2 => 1,
  3 => 1.5,
  4 => 1,
  5 => 2,
  6 => 1,
  7 => 1,
  8 => 1,
  9 => 0.5
];

// Função para calcular média ponderada simples
function calculaMediaPonderada(array $notas, array $pesos): ?float
{
  $somaNotas = 0;
  $somaPesos = 0;
  foreach ($pesos as $criterio => $peso) {
    if (isset($notas[$criterio])) {
      $somaNotas += $notas[$criterio] * $peso;
      $somaPesos += $peso;
    }
  }
  if ($somaPesos === 0) return null;
  return ($somaNotas / $somaPesos) * 10;
}

$trabalhos = [];
$notasPorTrabalho = [];

foreach ($dados as $row) {
  $id_trabalho = $row['id_trabalho'] ?? $row['id_trabalhos'] ?? null;
  if (!$id_trabalho) continue;

  if (!isset($trabalhos[$id_trabalho])) {
    $trabalhos[$id_trabalho] = [
      'id_trabalhos' => $id_trabalho,
      'titulo' => $row['titulo'],
      'area' => $row['area'] ?? 'Sem área'
    ];
  }

  if ($row['id_jurados'] && $row['criterio'] && $row['nota'] !== null) {
    $notasPorTrabalho[$id_trabalho][$row['id_jurados']][$row['criterio']] = (float) $row['nota'];
  }
}

$dadosCalculados = [];

foreach ($trabalhos as $trabalho) {
  $id_trabalho = $trabalho['id_trabalhos'];
  $notasPorJurado = $notasPorTrabalho[$id_trabalho] ?? [];

  $jurados = array_keys($notasPorJurado);
  sort($jurados);

  $mediaJurado1 = isset($jurados[0]) ? calculaMediaPonderada($notasPorJurado[$jurados[0]], $pesos) : null;
  $mediaJurado2 = isset($jurados[1]) ? calculaMediaPonderada($notasPorJurado[$jurados[1]], $pesos) : null;

  if ($mediaJurado1 !== null && $mediaJurado2 !== null) {
    $notaFinal = ($mediaJurado1 + $mediaJurado2) / 2;
  } elseif ($mediaJurado1 !== null) {
    $notaFinal = $mediaJurado1;
  } elseif ($mediaJurado2 !== null) {
    $notaFinal = $mediaJurado2;
  } else {
    $notaFinal = null;
  }

  $dadosCalculados[$trabalho['titulo']] = [
    'id_trabalho' => $id_trabalho,
    'titulo' => $trabalho['titulo'],
    'area' => $trabalho['area'],
    'nota_final' => $notaFinal,
    'notas_por_jurado' => $notasPorJurado,
  ];
}

$criterios = [
  1 => "Criatividade",
  2 => "Relevância",
  3 => "Conhecimento",
  4 => "Impacto",
  5 => "Metodologia",
  6 => "Clareza",
  7 => "Banner",
  8 => "Caderno",
  9 => "Participação"
];

function toBase64Image($path)
{
  if (!file_exists($path)) return '';
  $type = pathinfo($path, PATHINFO_EXTENSION);
  $data = file_get_contents($path);
  if ($data === false) {
    return '';
  }
  $base64 = base64_encode($data);
  return "data:image/$type;base64,$base64";
}

$stmtEsc = $pdo->prepare("SELECT nome FROM Escolas WHERE id_escolas = ?");
$stmtEsc->execute([$id_escola]);
$result = $stmtEsc->fetch(PDO::FETCH_ASSOC);
$userName = $result ? $result['nome'] : 'Escola';

$imgCearaCientifico = toBase64Image(__DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png');
$imgCrede7 = toBase64Image(__DIR__ . '/../assets/img/crede7.png');
$imgCeara = toBase64Image(__DIR__ . '/../assets/img/ceara.png');
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Relatório por Escola</title>
  <style>
    table tbody td {
      word-wrap: break-word;
      word-break: break-word;

      padding: 3px;
      border: 1px solid #000;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }

    body {
      font-family: 'DejaVu Sans', sans-serif;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 2px;
      text-align: center;
      word-wrap: break-word;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    table th,
    table td {
      border: 1px solid #000;
      padding: 4px;
      text-align: center;
    }

    th {
      background-color: #d1e7dd;
    }

    td {
      padding: 0;
    }

    .nav {
      color: #000;
      text-align: center;
      margin-bottom: 15px;
      padding: 0px 0;
    }

    .nav p{
      margin-top: 0px;
    }

    .cabecalho {
      text-align: center;
      margin-top: 5px;
    }

    .cabecalho p{
      margin-bottom: 5px;
    }

    .logos {
      margin-top: 60px;
      text-align: center;
    }

    .logos img {
      height: 55px;
      margin: 0 25px;
    }

    .rodape {
      justify-content: first baseline;
      margin-top: 20px;
      height: 150px;
    }

    .rodape div {
      justify-content: space-between;
      text-align: center;
      flex-direction: row !important;
      padding: 10px;
    }

    .rodape div img {
      max-width: 100px;
    }

    .table-responsive {
      overflow: hidden;
    }

    thead {
      display: table-header-group;
    }

    tfoot {
      display: table-row-group;
    }

    tr {
      page-break-inside: avoid;
    }

    td:first-child {
      max-width: 120px;
      word-wrap: break-word;
      white-space: normal;
    }
  </style>

  <link rel="stylesheet" href="../boostrap/CSS/bootstrap.min.css">
</head>

<body>

  <div class="cabecalho">
    <img src="<?= $imgCearaCientifico ?>" alt="Ceará Científico" class="img-fluid" style="height: 80px;">
    <p><b>ETAPA REGIONAL - 2025</b></p>
  </div>

  <nav class="nav">
    <div style="font-size: 15px;">
      <p><b>PLANILHA DE AVALIAÇÃO DE <?= $userName ?></b></p>
    </div>
  </nav>
  <?php
  $sql_jurados = "SELECT DISTINCT j.usuario 
                FROM Jurados j
                INNER JOIN Avaliacoes av ON av.id_jurado = j.id_jurados
                INNER JOIN Trabalhos t ON t.id_trabalhos = av.id_trabalho
                WHERE t.id_escolas = :id_escola
                ORDER BY j.usuario ASC";
  $stmt_jurados = $pdo->prepare($sql_jurados);
  $stmt_jurados->execute(['id_escola' => $id_escola]);
  $jurados = $stmt_jurados->fetchAll(PDO::FETCH_COLUMN);
  ?>
  <div class="container-fluid mb-5">
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-secondary text-center align-middle" style="font-size: 8px;">
          <tr>
            <th rowspan="2">Título</th>
            <th rowspan="2">Área</th>
            <?php foreach ($criterios as $nome): ?>
              <th colspan="2"><?= $nome ?></th>
            <?php endforeach; ?>
            <th colspan="2">Total individual</th>
            <th rowspan="2">Nota final</th>
          </tr>
          <tr>
            <?php foreach ($criterios as $criterio): ?>
              <th>Jurado 1</th>
              <th>Jurado 2</th>
            <?php endforeach; ?>
            <th>Jurado 1</th>
            <th>Jurado 2</th>
          </tr>
        </thead>

        <tbody class="text-center align-middle" style="font-size: 9px;">
          <?php foreach ($dadosCalculados as $titulo => $dadosTrabalho): ?>
            <tr>
              <td><?= htmlspecialchars($titulo) ?></td>
              <td><?= htmlspecialchars($dadosTrabalho['area']) ?></td>

              <?php foreach ($criterios as $id_criterio => $nome_criterio): ?>
                <?php for ($i = 0; $i < 2; $i++): ?>
                  <td>
                    <?php
                    $juradoIds = array_keys($dadosTrabalho['notas_por_jurado']);
                    sort($juradoIds);
                    $juradoId = $juradoIds[$i] ?? null;
                    if ($juradoId !== null && isset($dadosTrabalho['notas_por_jurado'][$juradoId][$id_criterio])) {
                      echo number_format($dadosTrabalho['notas_por_jurado'][$juradoId][$id_criterio], 2, '.', '');
                    } else {
                      echo "-";
                    }
                    ?>
                  </td>
                <?php endfor; ?>
              <?php endforeach; ?>

              <?php for ($i = 0; $i < 2; $i++): ?>
                <td>
                  <?php
                  $juradoIds = array_keys($dadosTrabalho['notas_por_jurado']);
                  sort($juradoIds);
                  $juradoId = $juradoIds[$i] ?? null;
                  if ($juradoId !== null) {
                    $media = calculaMediaPonderada($dadosTrabalho['notas_por_jurado'][$juradoId], $pesos);
                    echo number_format($media, 2, '.', '');
                  } else {
                    echo "-";
                  }
                  ?>
                </td>
              <?php endfor; ?>

              <td>
                <?= $dadosTrabalho['nota_final'] !== null ? number_format($dadosTrabalho['nota_final'], 2, '.', '') : "-" ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- <div class="logos">

    <img src=<?= $imgCrede7 ?>>

    <img src=<?= $imgCeara ?>>

  </div> -->
</body>

</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(720, 570, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0,0,0));

$dompdf->stream("relatorio_escola_{$_GET['id_escola']}_.pdf", [
  "Attachment" => false
]);
exit;
