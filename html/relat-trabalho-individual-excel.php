<?php
require_once '../php/Connect.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

if (!$trabalho) die("Trabalho não encontrado.");

$sql_jurado = "SELECT nome FROM Jurados WHERE id_jurados = :id_jurado";
$stmt_j = $pdo->prepare($sql_jurado);
$stmt_j->execute(['id_jurado' => $id_jurado]);
$jurado = $stmt_j->fetch(PDO::FETCH_ASSOC);

if (!$jurado) die("Jurado não encontrado.");

$sql_avaliacoes = "SELECT criterio, nota, comentario FROM Avaliacoes 
                   WHERE id_trabalho = :id_trabalho AND id_jurado = :id_jurado 
                   ORDER BY criterio";

$stmt2 = $pdo->prepare($sql_avaliacoes);
$stmt2->execute(['id_trabalho' => $id_trabalho, 'id_jurado' => $id_jurado]);
$avaliacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$criterios = [
    1 => "Criatividade e Inovação",
    2 => "Relevância da pesquisa",
    3 => "Conhecimento científico fundamentado e contextualização do problema abordado",
    4 => "Impacto para a construção de uma sociedade que promova ciência, cidadania e convivência democratica: o conhecimento a servico da vida coletiva",
    5 => "Metodologia científica conectada com os objetivos, resultados e conclusões",
    6 => "Clareza e objetividade na linguagem apresentada",
    7 => "Banner",
    8 => "Caderno de campo",
    9 => "Processo participativo e solidário"
];

$avaliacoesIndexadas = [];
foreach ($avaliacoes as $av) {
    $avaliacoesIndexadas[$av['criterio']] = $av;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'RELATÓRIO INDIVIDUAL DE AVALIAÇÃO');
$sheet->mergeCells('A1:C1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
$sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF198754');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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
    'font' => ['bold' => true, 'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE]],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF499472']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
];
$sheet->getStyle('A8:C8')->applyFromArray($headerStyle);

$row = 9;
foreach ($criterios as $num => $nome) {
    $sheet->setCellValue("A$row", $nome);
    $nota = isset($avaliacoesIndexadas[$num]) ? number_format((float)$avaliacoesIndexadas[$num]['nota'], 2, ',', '.') : '';
    $sheet->setCellValue("B$row", $nota);
    $sheet->setCellValue("C$row", isset($avaliacoesIndexadas[$num]) ? $avaliacoesIndexadas[$num]['comentario'] : '');
    
    $sheet->getStyle("A$row:C$row")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    $sheet->getStyle("A$row")->getAlignment()->setWrapText(true);
    $sheet->getStyle("C$row")->getAlignment()->setWrapText(true);
    $sheet->getStyle("B$row")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B$row")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    $row++;
}

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
