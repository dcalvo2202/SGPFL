<?php
use PHPUnit\Framework\TestCase;

class ExternalAdvisorProfileHU011Test extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string)$content;
    }

    public function testRegistroProcessValidaTiposYLimitesDeArchivosHu011(): void
    {
        $src = $this->readSource('registro_process.php');

        // Currículum: PDF/DOCX <= 5MB
        $this->assertStringContainsString("['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']", $src);
        $this->assertStringContainsString("['pdf', 'docx']", $src);
        $this->assertStringContainsString("'el Currículum'", $src);
        $this->assertMatchesRegularExpression('/validate_uploaded_file\s*\(\s*\$_FILES\[\'cv_document\'\][\s\S]*?,\s*5\s*,\s*\'el Currículum\'/u', $src);

        // Cédula: PDF/JPG/PNG <= 2MB
        $this->assertStringContainsString("['application/pdf', 'image/jpeg', 'image/png']", $src);
        $this->assertStringContainsString("['pdf', 'jpg', 'jpeg', 'png']", $src);
        $this->assertStringContainsString("'la fotocopia de cédula'", $src);
        $this->assertMatchesRegularExpression('/validate_uploaded_file\s*\(\s*\$_FILES\[\'id_copy_document\'\][\s\S]*?,\s*2\s*,\s*\'la fotocopia de cédula\'/u', $src);
    }

    public function testRegistroProcessRegistraEstadoEnRevisionYBloqueaReenvioNoEditable(): void
    {
        $src = $this->readSource('registro_process.php');

        // Bloqueo para solicitudes ya enviadas/aprobadas (no modificables)
        $this->assertStringContainsString("in_array(", $src);
        $this->assertStringContainsString("['En Revisión', 'Aprobado']", $src);

        // Estado inicial requerido por HU-011
        $this->assertStringContainsString("status = 'En Revisión'", $src);
        $this->assertStringContainsString("'En Revisión', NOW(), NOW()", $src);

        // Mensaje de confirmación de estado en revisión
        $this->assertStringContainsString("quedó en revisión", $src);
    }

    public function testRegistroProcessNotificaSubdireccionConDatosClaveHu011(): void
    {
        $src = $this->readSource('registro_process.php');

        $this->assertStringContainsString('Notificación: Solicitud de Asesor Externo en revisión - SGPFL', $src);
        $this->assertStringContainsString("panel_subdireccion.php", $src);

        // Datos requeridos en la salida/notificación
        $this->assertStringContainsString("<li><strong>Nombre:</strong>", $src);
        $this->assertStringContainsString("<li><strong>Institución:</strong>", $src);
        $this->assertStringContainsString("<li><strong>Especialización:</strong>", $src);
        $this->assertStringContainsString("<li><strong>Estado:</strong> <strong>En Revisión</strong>", $src);
    }

    public function testAsesorExternoSoloLecturaYSoloConVinculoAprobado(): void
    {
        $src = $this->readSource('historial_documentos.php');

        // Solo lectura para asesor externo
        $this->assertStringContainsString('solo lectura', $src);

        // Acceso a documentos solo si el asesor está aprobado
        $this->assertStringContainsString("AND ear.status = 'Aprobado'", $src);

        // Acción disponible en historial: descarga (sin edición)
        $this->assertStringContainsString('Descargar', $src);
        $this->assertStringNotContainsString('Editar', $src);
        $this->assertStringNotContainsString('Modificar', $src);
    }

    public function testVinculacionUsaMiembrosDeProyectoActivo(): void
    {
        $src = $this->readSource('inc/student_functions.php');

        $this->assertStringContainsString('function getGroupMembersByStudentId', $src);
        $this->assertStringContainsString("pm.status = 'Activo'", $src);
        $this->assertStringContainsString('function linkAdvisorToGroupMembers', $src);
    }

    public function testSubdireccionVisualizaSolicitudesPendientesParaValidacion(): void
    {
        $panelSubdir = $this->readSource('panel_subdireccion.php');
        $panelRevision = $this->readSource('panel_revisar_asesor_externo.php');

        // Notificación a secretaría/subdirección sobre pendientes
        $this->assertStringContainsString('external_advisor_profile_requests', $panelSubdir);
        $this->assertStringContainsString("Perfil Académico de Asesor Externo", $panelSubdir);

        // Listado para validación de solicitudes pendientes
        $this->assertStringContainsString("WHERE (ear.status = 'En Revision' OR ear.status = 'En Revisión')", $panelRevision);
        $this->assertStringContainsString('Revisión de Solicitudes - Asesor Externo', $panelRevision);
    }
}
