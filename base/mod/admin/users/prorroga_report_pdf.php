<?php
declare(strict_types=1);

/**
 * Renderizado PDF para HU-009: reporte de proyectos prorrogados.
 *
 * Responsabilidad única:
 * - transformar la data consolidada del reporte en un PDF legible
 * - no consultar base de datos
 * - no validar sesión/roles
 * - no manejar routing/controladores
 */

if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../../../fpdf186/fpdf.php';
}

function pr_pdf_encode(?string $value, string $default = 'No disponible'): string
{
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        $text = $default;
    }

    return utf8_decode($text);
}

function pr_pdf_value($value, string $default = 'No disponible'): string
{
    if ($value === null) {
        return $default;
    }

    if (is_string($value)) {
        $value = trim($value);
        return $value !== '' ? $value : $default;
    }

    return (string)$value;
}

function pr_pdf_limit(string $value, int $maxLength = 55): string
{
    $text = trim($value);
    if ($text === '') {
        return 'No disponible';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3, 'UTF-8') . '...';
    }

    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, $maxLength - 3) . '...';
}

class ProrrogaReportPdf extends FPDF
{
    private string $reportTitle;
    private string $generatedAt;
    private ?string $logoPath;

    public function __construct(string $reportTitle = 'Reporte de proyectos en prórroga', ?string $logoPath = null)
    {
        parent::__construct('L', 'mm', 'A4');

        $this->reportTitle = $reportTitle;
        $this->generatedAt = date('d/m/Y H:i');
        $this->logoPath = $logoPath ?: __DIR__ . '/../../../img/Logo-UNA-Rojo_FondoTransparente.png';

        $this->SetTitle(pr_pdf_encode($this->reportTitle));
        $this->SetAuthor('Sistema TFG');
        $this->SetAutoPageBreak(true, 12);
        $this->SetMargins(10, 15, 10);
    }

    public function Header(): void
    {
        if ($this->logoPath && is_file($this->logoPath)) {
            $this->Image($this->logoPath, 10, 8, 18);
        }

        $this->SetY(10);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(3, 73, 145);
        $this->Cell(0, 7, pr_pdf_encode($this->reportTitle), 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 5, pr_pdf_encode('Generado el ' . $this->generatedAt), 0, 1, 'C');

        $this->Ln(2);
        $this->SetDrawColor(3, 73, 145);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(5);
    }

    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(110, 110, 110);
        $this->Cell(0, 5, pr_pdf_encode('Página ' . $this->PageNo()), 0, 0, 'C');
    }

    public function sectionTitle(string $title): void
    {
        $this->Ln(1);
        $this->SetFillColor(3, 73, 145);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, pr_pdf_encode($title), 0, 1, 'L', true);
        $this->Ln(1);
    }

    /**
     * @param array<int, array{label:string,value:string}> $rows
     */
    public function infoGrid(array $rows, float $labelWidth = 42.0): void
    {
        $valueWidth = 138.0 - $labelWidth;

        foreach ($rows as $row) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(45, 45, 45);
            $this->Cell($labelWidth, 6, pr_pdf_encode($row['label'] . ':'), 0, 0, 'L');

            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(30, 30, 30);
            $this->MultiCell($valueWidth, 6, pr_pdf_encode($row['value']), 0, 'L');
        }

        $this->Ln(1);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, float> $widths
     * @param array<int, array<int, string>> $rows
     * @param array<int, bool> $highlightRows
     */
    public function tableWithWrappedRows(array $headers, array $widths, array $rows, array $highlightRows = []): void
    {
        $this->SetFillColor(229, 236, 246);
        $this->SetDrawColor(190, 198, 209);
        $this->SetTextColor(35, 35, 35);
        $this->SetFont('Arial', 'B', 8);

        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 8, pr_pdf_encode($header), 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Arial', '', 7.5);

        foreach ($rows as $rowIndex => $row) {
            $isHighlighted = $highlightRows[$rowIndex] ?? false;
            $fillColor = $isHighlighted ? [255, 243, 205] : [255, 255, 255];

            $lineCounts = [];
            foreach ($row as $colIndex => $text) {
                $lineCounts[] = $this->estimateLineCount($widths[$colIndex], $text);
            }

            $maxLines = max($lineCounts);
            $rowHeight = max(8, 4.5 * $maxLines);

            if ($this->GetY() + $rowHeight > 190) {
                $this->AddPage();

                $this->SetFillColor(229, 236, 246);
                $this->SetDrawColor(190, 198, 209);
                $this->SetTextColor(35, 35, 35);
                $this->SetFont('Arial', 'B', 8);

                foreach ($headers as $i => $header) {
                    $this->Cell($widths[$i], 8, pr_pdf_encode($header), 1, 0, 'C', true);
                }
                $this->Ln();

                $this->SetFont('Arial', '', 7.5);
            }

            $x = $this->GetX();
            $y = $this->GetY();

            foreach ($row as $colIndex => $text) {
                $w = $widths[$colIndex];

                $this->SetXY($x, $y);
                $this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                $this->Rect($x, $y, $w, $rowHeight, 'DF');

                $this->MultiCell($w, 4.5, pr_pdf_encode($text), 1, 'L', true);

                $x += $w;
                $this->SetXY($x, $y);
            }

            $this->SetXY(10, $y + $rowHeight);
        }

        $this->Ln(2);
    }

    public function notesBox(array $notes): void
    {
        if (empty($notes)) {
            return;
        }

        $this->SetFillColor(246, 248, 251);
        $this->SetDrawColor(215, 221, 230);
        $this->SetTextColor(35, 35, 35);
        $this->SetFont('Arial', '', 8);

        $text = "Notas del reporte:\n";
        foreach ($notes as $note) {
            $text .= '- ' . $note . "\n";
        }

        $this->MultiCell(0, 5, pr_pdf_encode(trim($text)), 1, 'L', true);
        $this->Ln(1);
    }

    private function estimateLineCount(float $width, string $text): int
    {
        $plain = str_replace("\r", '', $text);
        $parts = explode("\n", $plain);
        $lines = 0;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                $lines++;
                continue;
            }

            $approx = (int)ceil(strlen(utf8_decode($part)) / max(1, (($width - 2) / 1.8)));
            $lines += max(1, $approx);
        }

        return max(1, $lines);
    }
}

/**
 * @param array<string,mixed> $report
 */
function buildProrrogaReportPdf(array $report, array $options = []): ProrrogaReportPdf
{
    $pdf = new ProrrogaReportPdf(
        $options['title'] ?? 'Reporte de proyectos en prórroga',
        $options['logo_path'] ?? null
    );

    $pdf->AddPage();

    $meta = $report['meta'] ?? [];
    $filas = $report['filas'] ?? [];
    $resumen = $meta['resumen'] ?? [];
    $filtros = $meta['filtros'] ?? [];
    $notas = $meta['notas'] ?? [];

    $pdf->sectionTitle('1. Resumen del reporte');
    $pdf->infoGrid([
        ['label' => 'HU', 'value' => pr_pdf_value($meta['codigo_hu'] ?? null)],
        ['label' => 'Fecha de generación', 'value' => pr_pdf_value($meta['fecha_generacion'] ?? null)],
        ['label' => 'Filtros aplicados', 'value' => pr_pdf_value($filtros['descripcion'] ?? null)],
        ['label' => 'Total de proyectos', 'value' => pr_pdf_value($resumen['total_proyectos'] ?? 0)],
        ['label' => 'Con múltiples prórrogas', 'value' => pr_pdf_value($resumen['proyectos_con_multiples_prorrogas'] ?? 0)],
        ['label' => 'Con una prórroga', 'value' => pr_pdf_value($resumen['proyectos_con_una_prorroga'] ?? 0)],
    ]);

    $pdf->sectionTitle('2. Detalle de proyectos prorrogados');

    if (empty($filas)) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->MultiCell(0, 6, pr_pdf_encode('No se encontraron proyectos para los filtros seleccionados.'), 0, 'L');
        $pdf->Ln(1);
    } else {
        $headers = [
            'Identificador',
            'Proyecto',
            'Estado',
            'Estudiantes',
            'Comité Asesor',
            'Prórrogas',
            'Última solicitud',
            'Detalle',
            'Múltiples'
        ];

        $widths = [25, 38, 18, 48, 56, 15, 25, 32, 20];

        $rows = [];
        $highlightRows = [];

        foreach ($filas as $fila) {
            $rows[] = [
                pr_pdf_limit(pr_pdf_value($fila['identificador'] ?? null), 25),
                pr_pdf_limit(
                    pr_pdf_value($fila['nombre_proyecto'] ?? null) .
                    "\nCreación: " . pr_pdf_value($fila['fecha_creacion'] ?? null) .
                    "\nFinalización: " . pr_pdf_value($fila['fecha_finalizacion'] ?? null),
                    120
                ),
                pr_pdf_limit(pr_pdf_value($fila['estado'] ?? null), 20),
                pr_pdf_value($fila['estudiantes'] ?? null),
                pr_pdf_value($fila['comite_asesor']['texto_resumen'] ?? null),
                pr_pdf_value((string)($fila['cantidad_prorrogas_activas'] ?? 0)),
                pr_pdf_value($fila['ultima_fecha_solicitud'] ?? null),
                pr_pdf_value($fila['detalle_fechas_solicitud'] ?? null),
                pr_pdf_value($fila['marca_multiples_prorrogas'] ?? null),
            ];

            $highlightRows[] = !empty($fila['resaltar_multiples_prorrogas']);
        }

        $pdf->tableWithWrappedRows($headers, $widths, $rows, $highlightRows);
    }

    $pdf->sectionTitle('3. Notas');
    $pdf->notesBox(is_array($notas) ? $notas : []);

    return $pdf;
}

/**
 * @param array<string,mixed> $report
 */
function outputProrrogaReportPdf(array $report, string $destination = 'I', string $fileName = 'reporte_prorrogas.pdf'): void
{
    $pdf = buildProrrogaReportPdf($report);
    $pdf->Output($destination, $fileName);
}