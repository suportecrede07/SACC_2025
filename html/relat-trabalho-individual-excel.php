<?php
require_once '../php/Connect.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (
    !isset($_GET['id_trabalho']) || !is_numeric($_GET['id_trabalho']) ||
    !isset($_GET['id_jurado']) || !is_numeric($_GET['id_jurado'])
) {
    die("Parâmetros ausentes ou inválidos.");
}

$id_trabalho = (int) $_GET['id_trabalho'];
$id_jurado = (int) $_GET['id_jurado'];

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
    die("Trabalho não encontrado.");
}

$sql_jurado = "SELECT nome FROM Jurados WHERE id_jurados = :id_jurado";
$stmt_j = $pdo->prepare($sql_jurado);
$stmt_j->execute(['id_jurado' => $id_jurado]);
$jurado = $stmt_j->fetch(PDO::FETCH_ASSOC);

if (!$jurado) {
    die("Jurado não encontrado.");
}

// Critérios ativos definidos nas Configurações, na ordem cadastrada.
$sql_criterios = "SELECT id, nome, peso, ordem
                  FROM Criterios_avaliacao
                  WHERE ativo = 1
                  ORDER BY ordem ASC, id ASC";
$stmt_criterios = $pdo->query($sql_criterios);
$criterios = $stmt_criterios->fetchAll(PDO::FETCH_ASSOC);

// Busca as avaliações somente para os critérios ativos.
$sql_avaliacoes = "SELECT av.criterio, av.nota, av.comentario
                   FROM Avaliacoes av
                   INNER JOIN Criterios_avaliacao ca
                       ON ca.id = av.criterio
                      AND ca.ativo = 1
                   WHERE av.id_trabalho = :id_trabalho
                     AND av.id_jurado = :id_jurado
                   ORDER BY ca.ordem ASC, ca.id ASC";

$stmt2 = $pdo->prepare($sql_avaliacoes);
$stmt2->execute([
    'id_trabalho' => $id_trabalho,
    'id_jurado' => $id_jurado
]);
$avaliacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Indexa as avaliações pelo ID do critério. O ID serve para relacionar a nota;
// a ordem visual vem da coluna Criterios_avaliacao.ordem.
$avaliacoesIndexadas = [];
foreach ($avaliacoes as $av) {
    $avaliacoesIndexadas[(int) $av['criterio']] = $av;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'RELATÓRIO INDIVIDUAL DE AVALIAÇÃO');
$sheet->mergeCells('A1:C1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB(Color::COLOR_WHITE);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF198754');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->setCellValue('A3', 'JURADO:');
$sheet->setCellValue('B3', $jurado['nome']);
$sheet->setCellValue('A4', 'TÍTULO:');
$sheet->setCellValue('B4', $trabalho['titulo']);
$sheet->setCellValue('A5', 'ESCOLA:');
$sheet->setCellValue('B5', $trabalho['escola']);
$sheet->setCellValue('A6', 'CATEGORIA:');
$sheet->setCellValue('B6', $trabalho['categoria'] . ' | ÁREA: ' . $trabalho['area']);

$sheet->getStyle('A3:A6')->getFont()->setBold(true);

$sheet->setCellValue('A8', 'Critério');
$sheet->setCellValue('B8', 'Avaliação');
$sheet->setCellValue('C8', 'Comentário');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF499472']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A8:C8')->applyFromArray($headerStyle);

$row = 9;
foreach ($criterios as $criterio) {
    $idCriterio = (int) $criterio['id'];
    $nomeCriterio = (string) $criterio['nome'];

    $sheet->setCellValue("A$row", $nomeCriterio);
    $nota = isset($avaliacoesIndexadas[$idCriterio])
        ? number_format((float) $avaliacoesIndexadas[$idCriterio]['nota'], 2, ',', '.')
        : '';
    $comentario = isset($avaliacoesIndexadas[$idCriterio])
        ? (string) ($avaliacoesIndexadas[$idCriterio]['comentario'] ?? '')
        : '';

    $sheet->setCellValue("B$row", $nota);
    $sheet->setCellValue("C$row", $comentario);

    $sheet->getStyle("A$row:C$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A$row")->getAlignment()->setWrapText(true);
    $sheet->getStyle("C$row")->getAlignment()->setWrapText(true);
    $sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B$row")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $row++;
}

// Mantém as mesmas larguras e configurações visuais da planilha original.
foreach (range('A', 'C') as $col) {
    if ($col === 'A') {
        $sheet->getColumnDimension($col)->setWidth(45);
    } elseif ($col === 'C') {
        $sheet->getColumnDimension($col)->setWidth(55);
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="avaliacao_trabalho_' . $id_trabalho . '_jurado_' . $id_jurado . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
