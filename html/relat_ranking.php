<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
require_once '../php/Connect.php';
require_once '../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id_categoria']) || !is_numeric($_GET['id_categoria']) || !is_numeric($_GET['id_areas'] ?? 0)) die("id_categoria ou id_areas não foi encontrado.");

$id_categoria = (int) $_GET['id_categoria'];
$id_areas = null;
if (isset($_GET['id_areas']) && is_numeric($_GET['id_areas'])) {
  $id_areas = (int) $_GET['id_areas'];
}

$sql = "SELECT t.id_trabalhos,
 t.titulo,
  e.nome AS escola,
   e.focalizada,
    e.ide,
      e.IDEB,
        e.total_trabalhos,
     c.nome_categoria AS categoria,
      a.nome_area AS area 
        FROM Trabalhos t 
        LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas 
        LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria 
        LEFT JOIN Areas a ON t.id_areas = a.id_area 
         WHERE t.id_categoria = :categoria";

$params = [':categoria' => $id_categoria];

if (!empty($id_areas)) {
  $sql .= " AND t.id_areas = :area";
  $params[':area'] = $id_areas;
}
$sql .= " ORDER BY t.id_trabalhos DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoriaNome = $trabalhos[0]['categoria'] ?? '';
$areaNome = $trabalhos[0]['area'] ?? '';


$idsTrabalhos = array_column($trabalhos, 'id_trabalhos');
$avaliacoes = [];
if (count($idsTrabalhos) > 0) {
  $in = str_repeat('?,', count($idsTrabalhos) - 1) . '?';
  $sqlNotas =
    "SELECT id_trabalho,
  id_jurado,
  criterio,
  nota FROM Avaliacoes
  WHERE id_trabalho IN ($in)";
  $stmtNotas = $pdo->prepare($sqlNotas);
  $stmtNotas->execute($idsTrabalhos);
  $avaliacoes = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);
}
$notasPorTrabalho = [];
foreach ($avaliacoes as $av) {
  $notasPorTrabalho[$av['id_trabalho']][$av['id_jurado']][$av['criterio']] = (float)$av['nota'];
}

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

$calculaMediaPonderada = function ($notas, $pesos) {
  $somaNotas = 0;
  $somaPesos = 0;
  foreach ($pesos as $crit => $peso) {
    $nota = $notas[$crit] ?? null;
    if ($nota !== null) {
      $somaNotas += $nota * $peso;
      $somaPesos += $peso;
    }
  }
  return $somaPesos > 0 ? ($somaNotas / $somaPesos) * 10 : null;
};

$dados = [];
foreach ($trabalhos as $row) {
  $id_trabalho = $row['id_trabalhos'];
  $notasPorJurado = $notasPorTrabalho[$id_trabalho] ?? [];

  $jurados = array_keys($notasPorJurado);
  sort($jurados);

  $mediaJurado1 = isset($jurados[0]) ? $calculaMediaPonderada($notasPorJurado[$jurados[0]], $pesos) : null;
  $mediaJurado2 = isset($jurados[1]) ? $calculaMediaPonderada($notasPorJurado[$jurados[1]], $pesos) : null;

  if ($mediaJurado1 !== null && $mediaJurado2 !== null) {
    $notaFinal = ($mediaJurado1 + $mediaJurado2) / 2;
  } elseif ($mediaJurado1 !== null) {
    $notaFinal = $mediaJurado1;
  } elseif ($mediaJurado2 !== null) {
    $notaFinal = $mediaJurado2;
  } else {
    $notaFinal = null;
  }

  $criteriosMedios = [];
  foreach ($pesos as $idx => $_) {
    $crit = $idx + 1;
    $nota1 = isset($jurados[0]) ? ($notasPorJurado[$jurados[0]][$crit] ?? null) : null;
    $nota2 = isset($jurados[1]) ? ($notasPorJurado[$jurados[1]][$crit] ?? null) : null;

    if ($nota1 !== null && $nota2 !== null) {
      $criteriosMedios[$crit] = ($nota1 + $nota2) / 2;
    } elseif ($nota1 !== null) {
      $criteriosMedios[$crit] = $nota1;
    } elseif ($nota2 !== null) {
      $criteriosMedios[$crit] = $nota2;
    } else {
      $criteriosMedios[$crit] = null;
    }
  }

  $dados[] = [
    'id_trabalho' => $id_trabalho,
    'titulo' => $row['titulo'],
    'escola' => $row['escola'],
    'focalizada' => strtolower($row['focalizada'] ?? '') === 'focalizada',
    'ide' => strtolower($row['ide'] ?? '') === 'sim',
    'IDEB' => $row['IDEB'],
    'total_trabalhos' => $row['total_trabalhos'],
    'categoria' => $row['categoria'],
    'area' => $row['area'],
    'nota_final' => $notaFinal,
    'criterios' => $criteriosMedios,
    'criterio_desempate' => null,
  ];
}



function comparaTrabalhos($a, $b, $criteriosDesempate)
{
  if ($a['nota_final'] > $b['nota_final']) return -1;
  if ($a['nota_final'] < $b['nota_final']) return 1;

  foreach ($criteriosDesempate as $crit) {
    $notaA = $a['criterios'][$crit] ?? 0;
    $notaB = $b['criterios'][$crit] ?? 0;
    if ($notaA > $notaB) return -1;
    if ($notaA < $notaB) return 1;
  }

  if ($a['total_trabalhos'] > $b['total_trabalhos']) return -1;
  if ($a['total_trabalhos'] < $b['total_trabalhos']) return 1;

  if ($a['IDEB'] > $b['IDEB']) return -1;
  if ($a['IDEB'] < $b['IDEB']) return 1;

  /*

  if ($a['focalizada'] && !$b['focalizada']) return -1;
  if (!$a['focalizada'] && $b['focalizada']) return 1;

  if ($a['ide'] && !$b['ide']) return -1;
  if (!$a['ide'] && $b['ide']) return 1;
  */


  return 0;
}
function criterioDesempateUsado($a, $b, $criteriosDesempate)
{
  foreach ($criteriosDesempate as $index => $crit) {
    $notaA = $a['criterios'][$crit] ?? 0;
    $notaB = $b['criterios'][$crit] ?? 0;
    if ($notaA != $notaB) {
      return [
        'indice' => $index + 1,
        'criterio' => "Critério #" . ($index + 1),
      ];
    }
  }

  if ($a['focalizada'] !== $b['focalizada']) {
    return ['indice' => 'Focalizada', 'criterio' => 'Escola focalizada'];
  }

  if ($a['ide'] !== $b['ide']) {
    return ['indice' => 'IDE', 'criterio' => 'Escola com IDE'];
  }

  return null;
}

$criteriosDesempate = range(1, 9);
usort($dados, function ($a, $b) use ($criteriosDesempate) {
  return comparaTrabalhos($a, $b, $criteriosDesempate);
});


for ($i = 0; $i < count($dados) - 1; $i++) {
  $atual = $dados[$i];
  $proximo = $dados[$i + 1];

  if (abs($atual['nota_final'] - $proximo['nota_final']) < 0.0001) {
    $criterioUsado = criterioDesempateUsado($atual, $proximo, $criteriosDesempate);
    if ($criterioUsado !== null) {
      $dados[$i]['criterio_desempate'] = $criterioUsado;
      $dados[$i + 1]['criterio_desempate'] = null;
    }
  }
}

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
$imgCearaCientifico = toBase64Image(__DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png');
$imgCrede7 = toBase64Image(__DIR__ . '/../assets/img/crede7.png');
$imgCeara = toBase64Image(__DIR__ . '/../assets/img/ceara.png');
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <title>Relatório Ranking Geral</title>
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
  </style>
</head>

<body>
  <div class="text-center">
    <img src="<?= $imgCearaCientifico ?>" style="height:80px;">
    <p><b>ETAPA REGIONAL - 2026</b></p>
  </div>

  <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
    <div class="header-title">RESULTADO FINAL (RANKING)</div>
    <div class="sub-title">CATEGORIA: <?= htmlspecialchars($categoriaNome) ?></div>
    <div class="sub-title">ÁREA: <?= htmlspecialchars($areaNome) ?></div>
  </div>

  <div class="table-container">
    <table>
      <thead>
          <tr>
            <th>Classificação</th>
            <th>Escola</th>
            <th>Título</th>
            <th>Nota final</th>
            <th>Critério de Desempate</th>
          </tr>
        </thead>
        <tbody class="text-center align-middle" style="font-size: 10px;">
          <?php
          $escolas = $pdo->query("SELECT id_escolas, nome FROM Escolas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
          $categorias = $pdo->query("SELECT id_categoria, nome_categoria FROM Categorias ORDER BY nome_categoria")->fetchAll(PDO::FETCH_ASSOC);
          $areas = $pdo->query("SELECT id_area, nome_area FROM Areas ORDER BY nome_area")->fetchAll(PDO::FETCH_ASSOC);
          $trabalhos = $pdo->query("SELECT id_trabalhos, titulo FROM Trabalhos ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

          $criteriosDesempate = [1, 2, 3, 4, 5, 6, 7, 8, 9];
          $sql = "SELECT 
          t.id_trabalhos,
           t.titulo,
            e.nome AS escola,
             e.focalizada,
              e.ide,
               c.nome_categoria AS categoria,
                a.nome_area AS area 
            FROM Trabalhos t 
            LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas 
            LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria 
            LEFT JOIN Areas a ON t.id_areas = a.id_area 
            WHERE 1=1";
          $sql_avaliacoes = "SELECT id_jurado, criterio, nota FROM Avaliacoes WHERE id_trabalho = :id_trabalho";
          $stmt_av = $pdo->prepare($sql_avaliacoes);
          $stmt_av->execute([':id_trabalho' => $id_trabalho]);
          $avaliacoes = $stmt_av->fetchAll(PDO::FETCH_ASSOC);

          $focalizada = strtolower($row['focalizada'] ?? '') === 'focalizada' ? true : false;
          $ide = strtolower($row['ide'] ?? '') === 'sim' ? true : false;


          ?>
          <?php foreach ($dados as $index => $trab): ?>

            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($trab['escola']) ?></td>
              <td><?= htmlspecialchars($trab['titulo']) ?></td>
              <td>
                <?= $trab['nota_final'] !== null ? number_format($trab['nota_final'], 2, ',', '') : '-' ?>
              </td>
              <?php
              if (isset($trab['criterio_desempate']) && $trab['criterio_desempate'] !== null) {
                $crit = $trab['criterio_desempate'];
                echo '<td>' . htmlspecialchars($crit['criterio']) . '</td>';
              } else {
                echo '<td> - </td>';
              }
              ?>         
              </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </div>
  <div class="footer-images">
    <img src="<?= $imgCrede7 ?>">
    <img src="<?= $imgCeara ?>">
  </div>
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

$dompdf->stream("Ranking_geral.pdf", ["Attachment" => false]);
?>