<?php
declare(strict_types=1);

/**
 * Renderizado PDF para HU-036: resumen consolidado por estudiante.
 *
 * Responsabilidad única de este archivo:
 * - transformar el arreglo consolidado del estudiante en un PDF legible
 * - no consultar base de datos
 * - no validar sesión/roles
 * - no manejar routing/controladores
 */

if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../../../fpdf186/fpdf.php';
}

/**
 * Convierte texto UTF-8 a la codificación esperada por FPDF.
 */
function ss_pdf_encode(?string $value, string $default = 'No registrado'): string
{
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        $text = $default;
    }

    return utf8_decode($text);
}

/**
 * Retorna un valor imprimible sin valores vacíos o nulos.
 */
function ss_pdf_value($value, string $default = 'No registrado'): string
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

/**
 * Formatea una fecha/hora para salida legible.
 */
function ss_pdf_format_datetime(?string $value, string $default = 'No registrado'): string
{
    $raw = trim((string)($value ?? ''));
    if ($raw === '') {
        return $default;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $raw;
    }

    return date('d/m/Y H:i', $timestamp);
}

/**
 * Formatea una fecha para salida legible.
 */
function ss_pdf_format_date(?string $value, string $default = 'No registrado'): string
{
    $raw = trim((string)($value ?? ''));
    if ($raw === '') {
        return $default;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $raw;
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Formatea bytes a tamaño legible.
 */
function ss_pdf_format_bytes($bytes): string
{
    if (!is_numeric($bytes)) {
        return 'No registrado';
    }

    $size = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;

    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    return number_format($size, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
}

/**
 * Trunca texto largo para celdas pequeñas.
 */
function ss_pdf_limit(string $value, int $maxLength = 55): string
{
    $text = trim($value);
    if ($text === '') {
        return 'No registrado';
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

/**
 * PDF del resumen consolidado.
 */
class StudentSummaryPdf extends FPDF
{
    /** @var string */
    private $reportTitle;

    /** @var string */
    private $generatedAt;

    /** @var string */
    private $logoPath;

    public function __construct(string $reportTitle = 'Resumen consolidado del estudiante', ?string $logoPath = null)
    {
        parent::__construct('P', 'mm', 'A4');

        $this->reportTitle = $reportTitle;
        $this->generatedAt = date('d/m/Y H:i');
        $this->logoPath = $logoPath ?: __DIR__ . '/../../../img/Logo-UNA-Rojo_FondoTransparente.png';

        $this->SetTitle(ss_pdf_encode($this->reportTitle));
        $this->SetAuthor('Sistema TFG');
        $this->SetAutoPageBreak(true, 18);
        $this->SetMargins(15, 18, 15);
    }

    public function Header(): void
    {
        if (is_file($this->logoPath)) {
            $this->Image($this->logoPath, 15, 11, 18);
        }

        $this->SetY(11);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(3, 73, 145);
        $this->Cell(0, 7, ss_pdf_encode($this->reportTitle), 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 5, ss_pdf_encode('Generado el ' . $this->generatedAt), 0, 1, 'C');

        $this->Ln(2);
        $this->SetDrawColor(3, 73, 145);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(5);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(110, 110, 110);
        $this->Cell(0, 5, ss_pdf_encode('Pagina ' . $this->PageNo()), 0, 0, 'C');
    }

    public function sectionTitle(string $title): void
    {
        $this->Ln(2);
        $this->SetFillColor(3, 73, 145);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, ss_pdf_encode($title), 0, 1, 'L', true);
        $this->Ln(1);
    }

    /**
     * @param array<int, array{label:string,value:string}> $rows
     */
    public function infoGrid(array $rows, float $labelWidth = 48.0): void
    {
        $valueWidth = 180.0 - $labelWidth;

        foreach ($rows as $row) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(45, 45, 45);
            $this->Cell($labelWidth, 7, ss_pdf_encode($row['label'] . ':'), 0, 0, 'L');

            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(30, 30, 30);
            $x = $this->GetX();
            $y = $this->GetY();
            $this->MultiCell($valueWidth, 7, ss_pdf_encode($row['value']), 0, 'L');

            if ($this->GetY() === $y) {
                $this->SetXY($x + $valueWidth, $y + 7);
            }
        }

        $this->Ln(1);
    }

    public function noteBox(string $text): void
    {
        $this->SetFillColor(246, 248, 251);
        $this->SetDrawColor(215, 221, 230);
        $this->SetTextColor(35, 35, 35);
        $this->SetFont('Arial', '', 9);
        $this->MultiCell(0, 6, ss_pdf_encode($text), 1, 'L', true);
        $this->Ln(1);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, float> $widths
     * @param array<int, array<int, string>> $rows
     */
    public function simpleTable(array $headers, array $widths, array $rows): void
    {
        $this->SetFillColor(229, 236, 246);
        $this->SetDrawColor(190, 198, 209);
        $this->SetTextColor(35, 35, 35);
        $this->SetFont('Arial', 'B', 8);

        foreach ($headers as $index => $header) {
            $this->Cell($widths[$index], 7, ss_pdf_encode($header), 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Arial', '', 8);
        $fill = false;

        foreach ($rows as $row) {
            $this->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 253 : 255);
            foreach ($row as $index => $value) {
                $this->Cell($widths[$index], 7, ss_pdf_encode($value), 1, 0, 'L', true);
            }
            $this->Ln();
            $fill = !$fill;
        }

        $this->Ln(1);
    }
}

/**
 * Crea la instancia del PDF con el contenido del resumen.
 *
 * @param array<string, mixed> $summary
 */
function buildStudentSummaryPdf(array $summary, array $options = []): StudentSummaryPdf
{
    $pdf = new StudentSummaryPdf(
        $options['title'] ?? 'Resumen consolidado del estudiante',
        $options['logo_path'] ?? null
    );

    $pdf->AddPage();

    $student = $summary['student'] ?? [];
    $proposals = $summary['proposals'] ?? [];
    $project = $summary['project'] ?? [];
    $timeline = $summary['timeline'] ?? [];
    $documents = $summary['documents'] ?? [];
    $reviews = $summary['reviews'] ?? [];
    $legacy = $summary['legacy'] ?? [];
    $importantDates = $summary['important_dates'] ?? [];

    $mainProposal = $proposals[0] ?? [];
    $mainLegacy = ($legacy['approved_projects'] ?? [])[0] ?? [];

    $pdf->sectionTitle('1. Datos del estudiante');
    $pdf->infoGrid([
        ['label' => 'Identificacion', 'value' => ss_pdf_value($student['id'] ?? null)],
        ['label' => 'Nombre completo', 'value' => ss_pdf_value($student['nombre'] ?? null)],
        ['label' => 'Correo', 'value' => ss_pdf_value($student['email'] ?? null)],
        ['label' => 'Rol', 'value' => ss_pdf_value($student['roll_name'] ?? null)],
    ]);

    $pdf->sectionTitle('2. Resumen general del TFG');
    $pdf->infoGrid([
        ['label' => 'Titulo del proyecto', 'value' => ss_pdf_value($mainProposal['title'] ?? null)],
        ['label' => 'Relacion del estudiante', 'value' => ss_pdf_value($mainProposal['relationship_to_student'] ?? null)],
        ['label' => 'Tipo de proyecto', 'value' => ss_pdf_value($mainProposal['project_type_name'] ?? null)],
        ['label' => 'Estado de la propuesta', 'value' => ss_pdf_value($mainProposal['proposal_status'] ?? null)],
        ['label' => 'Estado del proyecto registrado', 'value' => ss_pdf_value($mainProposal['registered_project_status'] ?? ($project['status'] ?? null))],
        ['label' => 'Estado temporal del proyecto', 'value' => ss_pdf_value($timeline['status'] ?? null)],
        ['label' => 'Identificador institucional', 'value' => ss_pdf_value($mainLegacy['identificador'] ?? null)],
    ]);

    if (!empty($project['members']) && is_array($project['members'])) {
        $pdf->sectionTitle('3. Integrantes del proyecto');

        $rows = [];
        foreach ($project['members'] as $member) {
            $rows[] = [
                ss_pdf_limit(ss_pdf_value($member['user_name'] ?? null), 38),
                ss_pdf_limit(ss_pdf_value($member['user_email'] ?? null), 46),
                ss_pdf_limit(ss_pdf_value($member['role'] ?? null), 18),
                ss_pdf_limit(ss_pdf_value($member['status'] ?? null), 15),
                ss_pdf_limit(ss_pdf_format_datetime($member['joined_at'] ?? null), 16),
            ];
        }

        $pdf->simpleTable(
            ['Nombre', 'Correo', 'Rol', 'Estado', 'Ingreso'],
            [42, 58, 25, 20, 25],
            $rows
        );
    }

    $pdf->sectionTitle('4. Propuesta');
    $pdf->infoGrid([
        ['label' => 'Titulo', 'value' => ss_pdf_value($mainProposal['title'] ?? null)],
        ['label' => 'Disciplinas', 'value' => ss_pdf_value($mainProposal['disciplines'] ?? null)],
        ['label' => 'Archivo de propuesta', 'value' => ss_pdf_value($mainProposal['proposal_file_name'] ?? null)],
        ['label' => 'Estado', 'value' => ss_pdf_value($mainProposal['proposal_status'] ?? null)],
        ['label' => 'Revisado por', 'value' => ss_pdf_value($mainProposal['reviewer_name'] ?? null)],
        ['label' => 'Fecha de revision', 'value' => ss_pdf_format_datetime($mainProposal['reviewed_at'] ?? null)],
    ]);

    if (!empty($mainProposal['project_description'])) {
        $pdf->noteBox('Descripcion: ' . ss_pdf_value($mainProposal['project_description']));
    }

    if (!empty($mainProposal['admin_comments'])) {
        $pdf->noteBox('Observacion administrativa: ' . ss_pdf_value($mainProposal['admin_comments']));
    }

    $pdf->sectionTitle('5. Documentos entregados');
    if (!empty($documents)) {
        $rows = [];
        foreach ($documents as $document) {
            $rows[] = [
                ss_pdf_limit(ss_pdf_value($document['document_type'] ?? null), 25),
                ss_pdf_limit(ss_pdf_value($document['file_name'] ?? null), 50),
                ss_pdf_limit(ss_pdf_value(isset($document['version']) ? 'v' . $document['version'] : null), 8),
                ss_pdf_limit(ss_pdf_format_bytes($document['file_size'] ?? null), 12),
                ss_pdf_limit(ss_pdf_value($document['status'] ?? null), 22),
                ss_pdf_limit(ss_pdf_format_datetime($document['submitted_at'] ?? null), 18),
            ];
        }

        $pdf->simpleTable(
            ['Tipo', 'Archivo', 'Version', 'Tamano', 'Estado', 'Entregado'],
            [26, 68, 16, 18, 30, 22],
            $rows
        );
    } else {
        $pdf->noteBox('No se registran documentos finales entregados.');
    }

    $pdf->sectionTitle('6. Observaciones y revisiones');
    if (!empty($reviews)) {
        foreach ($reviews as $review) {
            $pdf->infoGrid([
                ['label' => 'Tipo de revision', 'value' => ss_pdf_value($review['review_type'] ?? null)],
                ['label' => 'Estado', 'value' => ss_pdf_value($review['status'] ?? null)],
                ['label' => 'Revisor', 'value' => ss_pdf_value($review['reviewer_name'] ?? null)],
                ['label' => 'Fecha de revision', 'value' => ss_pdf_format_datetime($review['reviewed_at'] ?? null)],
                ['label' => 'Version del archivo', 'value' => ss_pdf_value($review['file_version'] ?? null)],
                ['label' => 'Cantidad de correcciones', 'value' => ss_pdf_value($review['corrections_count'] ?? null)],
            ]);

            if (!empty($review['corrections_summary'])) {
                $pdf->noteBox('Resumen de correcciones: ' . ss_pdf_value($review['corrections_summary']));
            }
        }
    } else {
        $pdf->noteBox('No se registran revisiones del documento final.');
    }

    $pdf->sectionTitle('7. Fechas clave');
    if (!empty($importantDates)) {
        $rows = [];
        foreach ($importantDates as $item) {
            $rawDate = ss_pdf_value($item['date'] ?? null, '');
            $formattedDate = strpos($rawDate, ':') !== false
                ? ss_pdf_format_datetime($rawDate)
                : ss_pdf_format_date($rawDate);

            $rows[] = [
                ss_pdf_limit($formattedDate, 22),
                ss_pdf_limit(ss_pdf_value($item['type'] ?? null), 95),
            ];
        }

        $pdf->simpleTable(
            ['Fecha', 'Evento'],
            [38, 142],
            $rows
        );
    } else {
        $pdf->noteBox('No se registran fechas clave para este estudiante.');
    }

    if (!empty($legacy['approved_projects']) || !empty($legacy['notes']) || !empty($legacy['cancellations'])) {
        $pdf->sectionTitle('8. Informacion historica y complementaria');

        if (!empty($legacy['approved_projects'])) {
            foreach ($legacy['approved_projects'] as $legacyProject) {
                $pdf->infoGrid([
                    ['label' => 'Identificador', 'value' => ss_pdf_value($legacyProject['identificador'] ?? null)],
                    ['label' => 'Proyecto', 'value' => ss_pdf_value($legacyProject['nombre'] ?? null)],
                    ['label' => 'Estado', 'value' => ss_pdf_value($legacyProject['estado'] ?? null)],
                    ['label' => 'Tutor', 'value' => ss_pdf_value($legacyProject['tutor_name'] ?? null)],
                    ['label' => 'Asesor 1', 'value' => ss_pdf_value($legacyProject['asesor_1_name'] ?? null)],
                    ['label' => 'Asesor 2', 'value' => ss_pdf_value($legacyProject['asesor_2_name'] ?? null)],
                    ['label' => 'Fecha de creacion', 'value' => ss_pdf_format_datetime($legacyProject['fecha_creacion'] ?? null)],
                    ['label' => 'Fecha de finalizacion', 'value' => ss_pdf_format_datetime($legacyProject['fecha_finalizacion'] ?? null)],
                ]);
            }
        }

        if (!empty($legacy['notes'])) {
            foreach ($legacy['notes'] as $note) {
                $text = sprintf(
                    'Nota: %s | Etapa: %s | Fecha: %s | Registrada por: %s',
                    ss_pdf_value($note['titulo'] ?? null),
                    ss_pdf_value($note['etapa_proyecto'] ?? null),
                    ss_pdf_format_datetime($note['creado_en'] ?? null),
                    ss_pdf_value($note['creado_por_nombre'] ?? null)
                );
                $pdf->noteBox($text);

                if (!empty($note['notas'])) {
                    $pdf->noteBox('Detalle: ' . ss_pdf_value($note['notas']));
                }
            }
        }

        if (!empty($legacy['cancellations'])) {
            foreach ($legacy['cancellations'] as $cancellation) {
                $text = sprintf(
                    'Cancelacion registrada el %s por %s. Motivo: %s',
                    ss_pdf_format_datetime($cancellation['fecha_cancelacion'] ?? null),
                    ss_pdf_value($cancellation['usuario_nombre'] ?? null),
                    ss_pdf_value($cancellation['motivo'] ?? null)
                );
                $pdf->noteBox($text);

                if (!empty($cancellation['observaciones'])) {
                    $pdf->noteBox('Observaciones: ' . ss_pdf_value($cancellation['observaciones']));
                }
            }
        }
    }

    return $pdf;
}

/**
 * Genera y envia / guarda el PDF segun el modo indicado por FPDF.
 *
 * @param array<string, mixed> $summary
 */
function outputStudentSummaryPdf(array $summary, string $destination = 'I', ?string $fileName = null, array $options = []): void
{
    $student = $summary['student'] ?? [];
    $studentId = preg_replace('/[^0-9A-Za-z_-]/', '_', (string)($student['id'] ?? 'estudiante'));
    $safeFileName = $fileName ?: 'resumen_estudiante_' . $studentId . '.pdf';

    $pdf = buildStudentSummaryPdf($summary, $options);
    $pdf->Output($destination, $safeFileName);
}
