<?php
/**
 * Generador simple de PDF para el Manual Técnico Formal SGPFL.
 * Convierte el Markdown formal a un PDF textual usando FPDF embebido en el repo.
 */
require_once __DIR__ . '/../fpdf186/fpdf.php';

$source = __DIR__ . '/MANUAL_TECNICO_FORMAL_SGPFL.md';
$output = __DIR__ . '/MANUAL_TECNICO_FORMAL_SGPFL.pdf';

if (!file_exists($source)) {
    fwrite(STDERR, "No existe el archivo fuente: {$source}\n");
    exit(1);
}

class ManualPdf extends FPDF
{
    public string $title = 'Manual Técnico SGPFL';

    public function Header(): void
    {
        if ($this->PageNo() === 1) {
            return;
        }
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(0, 8, $this->encode($this->title), 0, 1, 'C');
        $this->SetDrawColor(180, 180, 180);
        $this->Line(15, 18, 195, 18);
        $this->Ln(4);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 10, $this->encode('Página ') . $this->PageNo(), 0, 0, 'C');
    }

    public function encode(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }

    public function addWrapped(string $text, string $style = '', int $size = 10, float $lineHeight = 5.2): void
    {
        $this->SetFont('Arial', $style, $size);
        $this->SetTextColor(35, 35, 35);
        $this->MultiCell(0, $lineHeight, $this->encode($text));
    }
}

function cleanMarkdownLine(string $line): string
{
    $line = rtrim($line);
    $line = preg_replace('/\*\*(.*?)\*\*/', '$1', $line);
    $line = preg_replace('/`([^`]*)`/', '$1', $line);
    return $line ?? '';
}

$lines = file($source, FILE_IGNORE_NEW_LINES);
$pdf = new ManualPdf('P', 'mm', 'Letter');
$pdf->SetMargins(15, 18, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->title = 'Manual Técnico SGPFL';
$pdf->AliasNbPages();
$pdf->AddPage();

// Portada formal.
$pdf->SetTextColor(20, 55, 90);
$pdf->SetFont('Arial', 'B', 22);
$pdf->Ln(28);
$pdf->MultiCell(0, 11, $pdf->encode('Manual Técnico'), 0, 'C');
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 16);
$pdf->MultiCell(0, 9, $pdf->encode('Sistema Gestor de Trabajos Finales de Graduación'), 0, 'C');
$pdf->Ln(12);
$pdf->SetFont('Arial', '', 13);
$pdf->SetTextColor(40, 40, 40);
$pdf->MultiCell(0, 8, $pdf->encode('Universidad Nacional de Costa Rica'), 0, 'C');
$pdf->MultiCell(0, 8, $pdf->encode('Escuela de Informática'), 0, 'C');
$pdf->Ln(18);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 7, $pdf->encode('Sistema: SGPFL'), 0, 'C');
$pdf->MultiCell(0, 7, $pdf->encode('Versión del documento: 1.0'), 0, 'C');
$pdf->MultiCell(0, 7, $pdf->encode('Fecha: 2026-05-06'), 0, 'C');
$pdf->Ln(20);
$pdf->SetFont('Arial', 'I', 10);
$pdf->MultiCell(0, 6, $pdf->encode('Documento generado a partir de la documentación técnica versionada del repositorio.'), 0, 'C');

$pdf->AddPage();
$inCode = false;
$skipInitialTitle = true;

foreach ($lines as $rawLine) {
    $line = cleanMarkdownLine($rawLine);

    if (str_starts_with(trim($line), '```')) {
        $inCode = !$inCode;
        $pdf->Ln(1.5);
        continue;
    }

    if ($inCode) {
        if (trim($line) !== '') {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('Courier', '', 8);
            $pdf->MultiCell(0, 4.2, $pdf->encode($line), 0, 'L', true);
        }
        continue;
    }

    $trim = trim($line);
    if ($trim === '---') {
        $pdf->Ln(2);
        continue;
    }
    if ($trim === '') {
        $pdf->Ln(1.8);
        continue;
    }

    if (preg_match('/^# (.+)$/', $trim, $m)) {
        if ($skipInitialTitle) {
            $skipInitialTitle = false;
            continue;
        }
        $pdf->AddPage();
        $pdf->SetTextColor(20, 55, 90);
        $pdf->addWrapped($m[1], 'B', 16, 7.5);
        $pdf->SetTextColor(35, 35, 35);
        $pdf->Ln(2);
        continue;
    }

    if (preg_match('/^## (.+)$/', $trim, $m)) {
        $pdf->Ln(2);
        $pdf->SetTextColor(35, 70, 110);
        $pdf->addWrapped($m[1], 'B', 13, 6.5);
        $pdf->SetTextColor(35, 35, 35);
        continue;
    }

    if (preg_match('/^### (.+)$/', $trim, $m)) {
        $pdf->Ln(1.5);
        $pdf->SetTextColor(45, 85, 125);
        $pdf->addWrapped($m[1], 'B', 11, 5.8);
        $pdf->SetTextColor(35, 35, 35);
        continue;
    }

    if (preg_match('/^- \[([ xX])\] (.+)$/', $trim, $m)) {
        $mark = strtolower($m[1]) === 'x' ? '[x]' : '[ ]';
        $pdf->addWrapped($mark . ' ' . $m[2], '', 10, 5.1);
        continue;
    }

    if (str_starts_with($trim, '- ')) {
        $pdf->addWrapped(chr(149) . ' ' . substr($trim, 2), '', 10, 5.1);
        continue;
    }

    if (preg_match('/^\d+\.\s+(.+)$/', $trim, $m)) {
        $pdf->addWrapped($trim, '', 10, 5.1);
        continue;
    }

    if (str_contains($trim, '|')) {
        if (preg_match('/^\|?\s*:?-{3,}:?/', $trim)) {
            continue;
        }
        $cells = array_map('trim', explode('|', trim($trim, '|')));
        $text = implode('  |  ', array_filter($cells, static fn($c) => $c !== ''));
        if ($text !== '') {
            $pdf->SetFillColor(248, 248, 248);
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->MultiCell(0, 4.7, $pdf->encode($text), 0, 'L', true);
        }
        continue;
    }

    if (str_starts_with($trim, '> ')) {
        $pdf->SetTextColor(70, 70, 70);
        $pdf->addWrapped(substr($trim, 2), 'I', 10, 5.2);
        $pdf->SetTextColor(35, 35, 35);
        continue;
    }

    $pdf->addWrapped($trim, '', 10, 5.2);
}

$pdf->Output('F', $output);
echo "PDF generado: {$output}\n";
