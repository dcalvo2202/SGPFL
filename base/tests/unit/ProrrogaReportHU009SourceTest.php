<?php
use PHPUnit\Framework\TestCase;

class ProrrogaReportHU009SourceTest extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string) $content;
    }

    public function testProrrogaReportQueriesConsolidaProrrogasYFiltroPorAnio(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_queries.php');

        $this->assertStringContainsString('function obtenerProyectosProrrogados', $src);
        $this->assertStringContainsString('function contarProyectosProrrogados', $src);
        $this->assertStringContainsString('FROM proyecto_aprobado pa', $src);
        $this->assertStringContainsString('FROM tfg_extension_requests er', $src);
        $this->assertStringContainsString("WHERE er.status = 'aprobada'", $src);
        $this->assertStringContainsString('COUNT(*) AS cantidad_prorrogas_aprobadas', $src);
        $this->assertStringContainsString('MAX(er.request_date) AS ultima_fecha_solicitud', $src);
        $this->assertStringContainsString('YEAR(erf.request_date) = ?', $src);
        $this->assertStringContainsString('WHERE pa.estado = ?', $src);
        $this->assertStringContainsString('resaltar_multiples_prorrogas', $src);
    }

    public function testProrrogaReportQueriesValidaFiltrosDeEstadoAnioYSede(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_queries.php');

        $this->assertStringContainsString('private function validarFiltros', $src);
        $this->assertStringContainsString('El año indicado no es válido.', $src);
        $this->assertStringContainsString('El estado no puede ir vacío.', $src);
        $this->assertStringContainsString('El filtro por sede aún no puede implementarse', $src);
    }

    public function testProrrogaReportServiceDefineEstructuraBaseResumenYNotas(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_service.php');

        $this->assertStringContainsString('function construirDataReporte', $src);
        $this->assertStringContainsString("'titulo' => 'Reporte de proyectos en prórroga'", $src);
        $this->assertStringContainsString("'codigo_hu' => 'HU-009'", $src);
        $this->assertStringContainsString("'filtros' => [", $src);
        $this->assertStringContainsString("'resumen' => [", $src);
        $this->assertStringContainsString("'notas' => [", $src);
        $this->assertStringContainsString("'columnas' => [", $src);
        $this->assertStringContainsString("'filas' => \$filas", $src);
        $this->assertStringContainsString("'proyectos_con_multiples_prorrogas' => \$multiplesProrrogas", $src);
        $this->assertStringContainsString("'proyectos_con_una_prorroga' => max(0, \$total - \$multiplesProrrogas)", $src);
        $this->assertStringContainsString('Solo se incluyen proyectos con estado "Prorrogado".', $src);
        $this->assertStringContainsString('La cantidad de prórrogas se calcula a partir de solicitudes aprobadas del proyecto.', $src);
    }

    public function testProrrogaReportServiceMarcaMultiplesProrrogasYFormateaDatosClave(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_service.php');

        $this->assertStringContainsString("'cantidad_prorrogas_activas' => \$cantidadProrrogas", $src);
        $this->assertStringContainsString("'resaltar_multiples_prorrogas' => \$resaltarMultiples", $src);
        $this->assertStringContainsString("'marca_multiples_prorrogas' => \$resaltarMultiples ? 'Sí' : 'No'", $src);
        $this->assertStringContainsString("'comite_asesor' => [", $src);
        $this->assertStringContainsString("'texto_resumen' => \$this->construirResumenComite(", $src);
        $this->assertStringContainsString('private function formatearFecha', $src);
        $this->assertStringContainsString('private function formatearFechaHora', $src);
        $this->assertStringContainsString('private function formatearListado', $src);
        $this->assertStringContainsString('preg_replace(\'/\\s*\\|\\s*/\', "\\n", $texto)', $src);
    }

    public function testProrrogaReportPdfIncluyeSeccionesClaveDeHu009(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_pdf.php');

        $this->assertStringContainsString('class ProrrogaReportPdf extends FPDF', $src);
        $this->assertStringContainsString('1. Resumen del reporte', $src);
        $this->assertStringContainsString('2. Detalle de proyectos prorrogados', $src);
        $this->assertStringContainsString('3. Notas', $src);
        $this->assertStringContainsString('Notas del reporte:', $src);
        $this->assertStringContainsString('function buildProrrogaReportPdf', $src);
        $this->assertStringContainsString('function outputProrrogaReportPdf', $src);
        $this->assertStringContainsString('Reporte de proyectos en prórroga', $src);
    }

    public function testProrrogaReportPdfDefineTablaConColumnasYResaltado(): void
    {
        $src = $this->readSource('mod/admin/users/prorroga_report_pdf.php');

        $this->assertStringContainsString("'Identificador'", $src);
        $this->assertStringContainsString("'Proyecto'", $src);
        $this->assertStringContainsString("'Estado'", $src);
        $this->assertStringContainsString("'Estudiantes'", $src);
        $this->assertStringContainsString("'Comité Asesor'", $src);
        $this->assertStringContainsString("'Prórrogas'", $src);
        $this->assertStringContainsString("'Última solicitud'", $src);
        $this->assertStringContainsString("'Detalle'", $src);
        $this->assertStringContainsString("'Múltiples'", $src);
        $this->assertStringContainsString('$highlightRows[] = !empty($fila[\'resaltar_multiples_prorrogas\']);', $src);
        $this->assertStringContainsString('$widths = [25, 38, 18, 48, 56, 15, 25, 32, 20];', $src);
    }

    public function testDownloadReporteProrrogasUsaFiltrosYGeneraDescarga(): void
    {
        $src = $this->readSource('mod/admin/users/descargar_reporte_prorrogas.php');

        $this->assertStringContainsString("\$_GET['anio']", $src);
        $this->assertStringContainsString("\$_GET['estado']", $src);
        $this->assertStringContainsString("\$_GET['sede']", $src);
        $this->assertStringContainsString('Parametro anio invalido.', $src);
        $this->assertStringContainsString('HU-009 solo permite generar reportes con estado Prorrogado.', $src);
        $this->assertStringContainsString('$builder = new ProrrogaReportPdfData();', $src);
        $this->assertStringContainsString("\$builder->construirDataReporte(\$anio, \$estado, \$sede)", $src);
        $this->assertStringContainsString('outputProrrogaReportPdf($report, \'D\', $fileName);', $src);
        $this->assertStringContainsString("require_once __DIR__ . '/prorroga_report_service.php';", $src);
        $this->assertStringContainsString("require_once __DIR__ . '/prorroga_report_pdf.php';", $src);
    }

    public function testDownloadReporteProrrogasProtegeAccesoPorSesionYRol(): void
    {
        $src = $this->readSource('mod/admin/users/descargar_reporte_prorrogas.php');

        $this->assertStringContainsString("include('../../login/check.php');", $src);
        $this->assertStringContainsString("getVar('usuario')", $src);
        $this->assertStringContainsString("getVar('rol')", $src);
        $this->assertStringContainsString('$allowedRoles = [1, 2];', $src);
        $this->assertStringContainsString('in_array($currentUserRole, $allowedRoles, true)', $src);
        $this->assertStringContainsString('http_response_code(401);', $src);
        $this->assertStringContainsString('http_response_code(403);', $src);
        $this->assertStringContainsString('No tiene permisos para descargar este reporte.', $src);
        $this->assertStringContainsString("ob_start();", $src);
        $this->assertStringContainsString("while (ob_get_level() > 0)", $src);
    }
}