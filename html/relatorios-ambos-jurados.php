<?php
require_once '../php/Connect.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;

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

// Imagens
$imgCearaCientifico = toBase64Image('../assets/img/crede-ceara-cientifico-estado.png');

$imgLogo = toBase64Image('../assets/img/b76f995f-d85d-4d51-bf6b-47dd645dad78.png');

// Mapas de critérios e pesos
$mapa_criterios = [
  1 => 'criatividade',
  2 => 'relevancia',
  3 => 'conhecimento',
  4 => 'impacto',
  5 => 'metodologia',
  6 => 'clareza',
  7 => 'banner',
  8 => 'caderno',
  9 => 'processo',
  10 => 'total'
];

$criterios = [
  'criatividade' => 'Criatividade',
  'relevancia' => 'Relevância',
  'conhecimento' => 'Conhecimento',
  'impacto' => 'Impacto',
  'metodologia' => 'Metodologia',
  'clareza' => 'Clareza',
  'banner' => 'Banner',
  'caderno' => 'Caderno',
  'processo' => 'Processo'
];

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

// Filtros via URL
$catId = isset($_GET['catId']) ? intval($_GET['catId']) : 0;
$areaId = isset($_GET['areaId']) ? intval($_GET['areaId']) : 0;

// Consulta SQL
$sql = "
SELECT 
    t.id_trabalhos,
    t.titulo,
    e.nome AS escola,
    j.id_jurados,
    j.usuario AS user_jurado,
    av.criterio,
    av.nota,
    c.nome_categoria AS categoria,
    a.nome_area AS area
FROM Trabalhos t
LEFT JOIN Escolas e ON t.id_escolas = e.id_escolas
LEFT JOIN Avaliacoes av ON av.id_trabalho = t.id_trabalhos
LEFT JOIN Jurados j ON j.id_jurados = av.id_jurado
LEFT JOIN Categorias c ON t.id_categoria = c.id_categoria
LEFT JOIN Areas a ON t.id_areas = a.id_area
WHERE 1
";

$params = [];
if ($catId > 0) {
  $sql .= " AND t.id_categoria = ?";
  $params[] = $catId;
}
if ($areaId > 0) {
  $sql .= " AND t.id_areas = ?";
  $params[] = $areaId;
}

$sql .= " ORDER BY t.id_trabalhos, av.criterio, j.id_jurados";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estrutura de dados
$trabalhos = [];
$jurados_por_criterio = [];
foreach ($dados as $linha) {
  $id = $linha['id_trabalhos'];
  $id_jurado = $linha['id_jurados'];
  $user_jurado = $linha['user_jurado'] ?? 'Sem jurado';
  $nota = $linha['nota'] ?? 0;

  $criterio_id = $linha['criterio'];
  $criterio = $mapa_criterios[$criterio_id] ?? null;

  $trabalhos[$id]['titulo'] = $linha['titulo'];
  $trabalhos[$id]['escola'] = $linha['escola'];
  $trabalhos[$id]['categoria'] = $linha['categoria'] ?? 'Sem categoria';
  $trabalhos[$id]['area'] = $linha['area'] ?? 'Sem área';

  if ($criterio && $criterio != 'total') {
    $trabalhos[$id]['notas'][$criterio][$id_jurado] = $nota;
    $trabalhos[$id]['pesos'][$criterio] = $pesos[$criterio_id] ?? 1;
    $jurados_por_criterio[$criterio][$id_jurado] = $user_jurado;
  }
}

// HTML
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
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }

    .footer-images img,
    .header-image {
      max-width: 130px;
      display: inline-block;
      margin: 0 10px;
    }
  </style>

</head>

<body>
  <div class="text-center">
    <img src="<?= $imgCearaCientifico ?>" style="height:80px;">
    <p><b>ETAPA REGIONAL - 2026</b></p>
  </div>

  <?php
  $primeiro_trabalho = reset($trabalhos);
  $categoria = $primeiro_trabalho['categoria'] ?? 'Sem categoria';
  $area = $primeiro_trabalho['area'] ?? 'Sem área';
  ?>

  <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
    <div class="header-title">PLANILHA DE AVALIAÇÃO DOS JURADOS</div>
    <div class="sub-title">CATEGORIA: <?= htmlspecialchars($categoria) ?></div>
    <div class="sub-title">ÁREA: <?= htmlspecialchars($area) ?></div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th rowspan="2">Escola</th>
          <th rowspan="2">Título</th>
          <?php foreach ($criterios as $c => $label): ?>
            <th colspan="<?= count($jurados_por_criterio[$c] ?? [1, 2]) ?>"><?= $label ?></th>
          <?php endforeach; ?>
          <th rowspan="2">Nota Final</th>
        </tr>
        <tr>
          <?php foreach ($criterios as $c => $label):
            $jurados = $jurados_por_criterio[$c] ?? [0 => 'Jurado 1', 1 => 'Jurado 2'];
            foreach ($jurados as $id_j => $nome_j): ?>
              <th><?= htmlspecialchars($nome_j) ?></th>
          <?php endforeach;
          endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($trabalhos as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['escola']) ?></td>
            <td><?= htmlspecialchars($t['titulo']) ?></td>
            <?php
            $soma_ponderada = 0;
            $soma_pesos = 0;

            foreach ($criterios as $c => $label) {
              $notas_criterio = $t['notas'][$c] ?? [];
              $peso = $t['pesos'][$c] ?? 1;

              $jurados = $jurados_por_criterio[$c] ?? [0 => 'Jurado 1', 1 => 'Jurado 2'];

              foreach ($jurados as $id_j => $nome_j) {
                $nota = $notas_criterio[$id_j] ?? 0;
                echo "<td>$nota</td>";
                $soma_ponderada += ($nota * $peso);
                $soma_pesos += $peso;
              }
            }

            $nota_final = $soma_pesos ? number_format(($soma_ponderada / $soma_pesos) * 10, 2) : 0;
            ?>
            <td><?= $nota_final ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="footer-images">
    <?php if ($imgLogo): ?><img src="<?= $imgLogo ?>"><?php endif; ?>
  </div>
</body>

</html>

<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(720, 570, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));

$dompdf->stream("relatorio_jurados.pdf", ["Attachment" => false]);
?>