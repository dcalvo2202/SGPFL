<?php
use PHPUnit\Framework\TestCase;

class StudentSummaryHU036SourceTest extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string) $content;
    }

    public function testStudentSummaryServiceDefineEstructuraBaseYValidacionDeEstudiante(): void
    {
        $src = $this->readSource('mod/admin/users/student_summary_service.php');

        $this->assertStringContainsString('function buildStudentSummary', $src);
        $this->assertStringContainsString('"student" => null', $src);
        $this->assertStringContainsString('"proposals" => []', $src);
        $this->assertStringContainsString('"project" => null', $src);
        $this->assertStringContainsString('"timeline" => null', $src);
        $this->assertStringContainsString('"documents" => []', $src);
        $this->assertStringContainsString('"reviews" => []', $src);
        $this->assertStringContainsString('"legacy" => [', $src);
        $this->assertStringContainsString('"important_dates" => []', $src);
        $this->assertStringContainsString('throw new Exception("El estudiante no existe.")', $src);
    }

    public function testStudentSummaryServiceOrdenaFechasImportantesCronologicamente(): void
    {
        $src = $this->readSource('mod/admin/users/student_summary_service.php');

        $this->assertStringContainsString('usort($dates', $src);
        $this->assertStringContainsString('strtotime($a["date"]) <=> strtotime($b["date"])', $src);
        $this->assertStringContainsString('$summary["important_dates"] = $dates', $src);
    }

    public function testStudentSummaryPdfIncluyeSeccionesClaveDeHu036(): void
    {
        $src = $this->readSource('mod/admin/users/student_summary_pdf.php');

        $this->assertStringContainsString('Resumen consolidado del estudiante', $src);
        $this->assertStringContainsString('Resumen general del TFG', $src);
        $this->assertStringContainsString('Documentos entregados', $src);
        $this->assertStringContainsString('Observaciones y revisiones', $src);
        $this->assertStringContainsString('Fechas clave', $src);
        $this->assertStringContainsString('function buildStudentSummaryPdf', $src);
        $this->assertStringContainsString('function outputStudentSummaryPdf', $src);
    }

    public function testDownloadStudentSummaryPdfUsaParametroStudentIdYGeneraDescarga(): void
    {
        $src = $this->readSource('mod/admin/users/download_student_summary_pdf.php');

        $this->assertStringContainsString("\$_GET['student_id']", $src);
        $this->assertStringContainsString("\$_GET['id']", $src);
        $this->assertStringContainsString('Parametro student_id invalido.', $src);
        $this->assertStringContainsString('buildStudentSummary($conn, $studentId)', $src);
        $this->assertStringContainsString('outputStudentSummaryPdf(', $src);
        $this->assertStringContainsString("'D'", $src);
        $this->assertStringContainsString('require_once __DIR__ . \'/student_summary_service.php\'', $src);
        $this->assertStringContainsString('require_once __DIR__ . \'/student_summary_pdf.php\'', $src);
    }

    public function testDownloadStudentSummaryPdfProtegeAccesoPorSesionYRol(): void
    {
        $src = $this->readSource('mod/admin/users/download_student_summary_pdf.php');

        $this->assertStringContainsString("include('../../login/check.php');", $src);
        $this->assertStringContainsString("getVar('usuario')", $src);
        $this->assertStringContainsString("getVar('rol')", $src);
        $this->assertStringContainsString('$allowedRoles = [1, 2];', $src);
        $this->assertStringContainsString('in_array($currentUserRole, $allowedRoles, true)', $src);
        $this->assertStringContainsString('http_response_code(401);', $src);
        $this->assertStringContainsString('http_response_code(403);', $src);
        $this->assertStringContainsString('No tiene permisos para descargar este resumen.', $src);
    }

    public function testPanelStudentSummaryIncluyeBusquedaSeleccionYDescarga(): void
    {
        $src = $this->readSource('panel_student_summary.php');

        $this->assertStringContainsString('Resumen consolidado por estudiante', $src);
        $this->assertStringContainsString('id="student-search-term"', $src);
        $this->assertStringContainsString('id="btn-search-student"', $src);
        $this->assertStringContainsString('id="results-body"', $src);
        $this->assertStringContainsString('id="download-summary-link"', $src);
        $this->assertStringContainsString('download_student_summary_pdf.php', $src);
        $this->assertStringContainsString('search_users.php', $src);
        $this->assertStringContainsString('Volver al Panel Principal', $src);
    }
}