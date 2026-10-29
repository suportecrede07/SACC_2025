<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../php/Connect.php';
require_once __DIR__ . '/../php/CalculoRanking.php';
require_once __DIR__ . '/../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id_escola']) || !is_numeric($_GET['id_escola'])) {
    http_response_code(400);
    exit('O parâmetro id_escola é obrigatório e deve ser numérico.');
}

$idEscola = (int) $_GET['id_escola'];

// Os dados de identificação da escola são carregados separadamente porque
// o cabeçalho deve aparecer mesmo quando a escola não possui trabalhos.
$stmtEscola = $pdo->prepare(
    'SELECT e.nome, ce.categoria_da_escola AS modalidade
     FROM Escolas e
     LEFT JOIN Categoria_escolas ce ON ce.id = e.id_categoria_escola
     WHERE e.id_escolas = ?'
);
$stmtEscola->execute([$idEscola]);
$escola = $stmtEscola->fetch(PDO::FETCH_ASSOC);

if (!$escola) {
    http_response_code(404);
    exit('Escola não encontrada.');
}

// Os critérios exibidos são os mesmos critérios ativos considerados pelo
// cálculo centralizado, na ordem configurada no sistema.
$stmtCriterios = $pdo->query(
    'SELECT id, nome, peso, ordem
     FROM Criterios_avaliacao
     WHERE ativo = 1
     ORDER BY ordem ASC, id ASC'
);
$criterios = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);

if (!$criterios) {
    http_response_code(500);
    exit('Não há critérios de avaliação ativos para gerar este relatório.');
}

// Recupera a ordem original dos trabalhos da escola. O relatório detalha
// avaliações, não é um ranking, portanto não reordena as linhas por nota.
$stmtTrabalhos = $pdo->prepare(
    'SELECT id_trabalhos
     FROM Trabalhos
     WHERE id_escolas = ?
     ORDER BY ordem ASC, id_trabalhos ASC'
);
$stmtTrabalhos->execute([$idEscola]);
$idsTrabalhos = array_map('intval', $stmtTrabalhos->fetchAll(PDO::FETCH_COLUMN));

// Usa o arquivo central para calcular médias ponderadas, notas finais e
// considerar somente critérios ativos. O relatório aproveita as médias e
// as notas de cada jurado retornadas pela função central. A exibição abaixo
// continua na ordem cadastrada dos trabalhos, não na ordem do ranking.
$classificacaoCentral = [];
if ($idsTrabalhos) {
    try {
        $classificacaoCentral = obterClassificacaoTrabalhos($pdo, null, null, true);
    } catch (Throwable $e) {
        error_log('Erro ao gerar relatório por escola: ' . $e->getMessage());
        http_response_code(500);
        exit('Não foi possível calcular as avaliações. Verifique os critérios ativos e as configurações do ranking.');
    }
}

$trabalhosPorId = [];
foreach ($classificacaoCentral as $trabalhoCentral) {
    if ((int) ($trabalhoCentral['id_escola'] ?? 0) !== $idEscola) {
        continue;
    }
    $trabalhosPorId[(int) $trabalhoCentral['id_trabalho']] = $trabalhoCentral;
}

function relatorioEscolaBase64Image(string $path): string
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

function relatorioEscolaFormatarNota($nota): string
{
    return $nota === null || $nota === ''
        ? '-'
        : number_format((float) $nota, 2, '.', '');
}

$imgCearaCientifico = relatorioEscolaBase64Image(
    __DIR__ . '/../assets/img/crede-ceara-cientifico-estado.png'
);

$nomeEscola = (string) ($escola['nome'] ?? 'Escola');
$modalidade = (string) ($escola['modalidade'] ?? '');

ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório por Escola</title>
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
        .mensagem {
            padding: 10px;
            text-align: center;
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

    <div class="text-center" style="background-color:#198754; color:#fff; padding:6px;">
        <div class="header-title">RELATÓRIO POR ESCOLA</div>
        <div class="sub-title">ESCOLA: <?= htmlspecialchars(trim($modalidade . ' ' . $nomeEscola), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Título</th>
                    <th rowspan="2">Área</th>
                    <?php foreach ($criterios as $criterio): ?>
                        <th colspan="2"><?= htmlspecialchars((string) $criterio['nome'], ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                    <th colspan="2">Total individual</th>
                    <th rowspan="2">Nota final</th>
                </tr>
                <tr>
                    <?php foreach ($criterios as $_criterio): ?>
                        <th>Jurado 1</th>
                        <th>Jurado 2</th>
                    <?php endforeach; ?>
                    <th>Jurado 1</th>
                    <th>Jurado 2</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$idsTrabalhos): ?>
                    <tr>
                        <td colspan="<?= 5 + (count($criterios) * 2) ?>" class="mensagem">Esta escola não possui trabalhos cadastrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($idsTrabalhos as $idTrabalho): ?>
                        <?php
                        $dadosTrabalho = $trabalhosPorId[$idTrabalho] ?? null;
                        if ($dadosTrabalho === null) {
                            continue;
                        }

                        // Os índices 1 e 2 representam os dois jurados considerados
                        // pela função central; os IDs reais estão em cada estrutura.
                        $jurado1 = $dadosTrabalho['jurados_detalhes'][1] ?? [];
                        $jurado2 = $dadosTrabalho['jurados_detalhes'][2] ?? [];
                        $notasJurado1 = $jurado1['criterios'] ?? [];
                        $notasJurado2 = $jurado2['criterios'] ?? [];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $dadosTrabalho['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($dadosTrabalho['area'] ?? 'Sem área'), ENT_QUOTES, 'UTF-8') ?></td>

                            <?php foreach ($criterios as $criterio): ?>
                                <?php $idCriterio = (int) $criterio['id']; ?>
                                <td><?= array_key_exists($idCriterio, $notasJurado1) ? relatorioEscolaFormatarNota($notasJurado1[$idCriterio]) : '-' ?></td>
                                <td><?= array_key_exists($idCriterio, $notasJurado2) ? relatorioEscolaFormatarNota($notasJurado2[$idCriterio]) : '-' ?></td>
                            <?php endforeach; ?>

                            <td><?= relatorioEscolaFormatarNota($jurado1['media_ponderada'] ?? null) ?></td>
                            <td><?= relatorioEscolaFormatarNota($jurado2['media_ponderada'] ?? null) ?></td>
                            <td><?= relatorioEscolaFormatarNota($dadosTrabalho['media_final'] ?? null) ?></td>
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

$dompdf->stream('relatorio_escola_' . $idEscola . '.pdf', [
    'Attachment' => false,
]);
exit;
