<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../php/Connect.php';
require_once __DIR__ . '/../php/CalculoRanking.php';
require_once __DIR__ . '/../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id_categoria']) || !is_numeric($_GET['id_categoria'])) {
    http_response_code(400);
    exit('O parâmetro id_categoria é obrigatório e deve ser numérico.');
}

$id_categoria = (int) $_GET['id_categoria'];
$id_areas = null;

if (isset($_GET['id_areas']) && $_GET['id_areas'] !== '') {
    if (!is_numeric($_GET['id_areas'])) {
        http_response_code(400);
        exit('O parâmetro id_areas deve ser numérico.');
    }
    $id_areas = (int) $_GET['id_areas'];
}

// Obtém os nomes usados no cabeçalho do PDF, mesmo quando não há trabalhos avaliados.
$stmtCategoria = $pdo->prepare(
    'SELECT nome_categoria FROM Categorias WHERE id_categoria = ?'
);
$stmtCategoria->execute([$id_categoria]);
$categoriaNome = (string) ($stmtCategoria->fetchColumn() ?: '');

$areaNome = '';
if ($id_areas !== null) {
    $stmtArea = $pdo->prepare('SELECT nome_area FROM Areas WHERE id_area = ?');
    $stmtArea->execute([$id_areas]);
    $areaNome = (string) ($stmtArea->fetchColumn() ?: '');
}

// Usa exclusivamente o cálculo centralizado em php/CalculoRanking.php, compartilhado com o dashboard.
$dados = [];
$erroRanking = null;
try {
    $dados = obterClassificacaoTrabalhos($pdo, $id_categoria, $id_areas);
} catch (Throwable $e) {
    error_log('Erro ao gerar ranking do PDF: ' . $e->getMessage());
    $erroRanking = 'Não foi possível calcular a classificação. Verifique os critérios ativos e seus pesos.';
}

// Os detalhes do trabalho (escola, modalidade, categoria e área) já são retornados
// por obterClassificacaoTrabalhos(), em CalculoRanking.php.

function rankingPdfBase64Image(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $data = file_get_contents($path);
    if ($data === false) {
        return '';
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $mime = 'image/jpeg';
            break;
        case 'gif':
            $mime = 'image/gif';
            break;
        case 'svg':
            $mime = 'image/svg+xml';
            break;
        default:
            $mime = 'image/png';
            break;
    }

    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$imgCearaCientifico = rankingPdfBase64Image(
    __DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png'
);
$imgCrede7 = rankingPdfBase64Image(__DIR__ . '/../assets/img/crede7.png');
$imgCeara = rankingPdfBase64Image(__DIR__ . '/../assets/img/ceara.png');

ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Relatório Ranking Geral</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      padding: 10px;
    }
    .text-center { text-align: center; }
    .table-container { margin-top: 10px; }
    table {
      border-collapse: collapse;
      font-size: 9px;
      width: 100%;
    }
    th, td {
      border: 1px solid #000;
      padding: 4px;
      text-align: center;
    }
    th {
      background-color: #499472;
      color: #fff;
      font-size: 10px;
    }
    .header-title { font-size: 14px; font-weight: bold; }
    .sub-title { font-size: 11px; font-weight: bold; margin: 2px 0; }
    .header-image { max-width: 130px; display: inline-block; margin: 12px 10px; }
    .mensagem { padding: 10px; text-align: center; }
  </style>
</head>
<body>
  <div class="text-center">
    <?php if ($imgCearaCientifico !== ''): ?>
      <img src="<?= htmlspecialchars($imgCearaCientifico, ENT_QUOTES, 'UTF-8') ?>" style="height:80px;" alt="Ceará Científico">
    <?php endif; ?>
    <p><b>ETAPA REGIONAL - 2026</b></p>
  </div>

  <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
    <div class="header-title">RESULTADO FINAL</div>
    <div class="sub-title">CATEGORIA: <?= htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8') ?></div>
    <?php if ($categoriaNome !== 'Pesquisa Júnior' && $areaNome !== ''): ?>
      <div class="sub-title">ÁREA: <?= htmlspecialchars($areaNome, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
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
      <tbody>
        <?php if ($erroRanking !== null): ?>
          <tr><td colspan="5" class="mensagem"><?= htmlspecialchars($erroRanking, ENT_QUOTES, 'UTF-8') ?></td></tr>
        <?php elseif (!$dados): ?>
          <tr><td colspan="5" class="mensagem">Nenhum trabalho com avaliações válidas foi encontrado para os filtros selecionados.</td></tr>
        <?php else: ?>
          <?php foreach ($dados as $trab): ?>
            <?php
              // Todos estes valores vêm da função central de classificação.
              $modalidade = trim((string) ($trab['Modalidade'] ?? ''));
              $nomeEscola = trim((string) ($trab['escola'] ?? 'Não informada'));
              $escolaExibida = trim($modalidade . ' ' . $nomeEscola);
              $criterioDesempate = $trab['criterio_desempate'] ?? null;
              $textoDesempate = is_array($criterioDesempate)
                  ? trim((string) ($criterioDesempate['criterio'] ?? '-'))
                  : trim((string) ($criterioDesempate ?? '-'));
              if ($textoDesempate === '') {
                  $textoDesempate = '-';
              }
            ?>
            <tr>
              <td><?= (int) $trab['posicao'] ?></td>
              <td><?= htmlspecialchars($escolaExibida, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $trab['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= number_format((float) $trab['media_final'], 2, ',', '') ?></td>
              <td><?= $textoDesempate !== '' && $textoDesempate !== '-'
                    ? htmlspecialchars($textoDesempate, ENT_QUOTES, 'UTF-8')
                    : '-' ?></td>
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

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(720, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 10, [0, 0, 0]);

$dompdf->stream('Ranking_geral.pdf', ['Attachment' => false]);
?>
