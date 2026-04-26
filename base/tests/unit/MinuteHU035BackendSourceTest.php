<?php

use PHPUnit\Framework\TestCase;

class MinuteHU035BackendSourceTest extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string) $content;
    }

    public function testCommitteeMinuteProjectRepositoryFiltraProyectosPorMiembroDelComite(): void
    {
        $src = $this->readSource('mod/admin/users/CommitteeMinuteProjectRepository.php');

        $this->assertStringContainsString('class CommitteeMinuteProjectRepository', $src);
        $this->assertStringContainsString('function findProjectsByCommitteeMember', $src);
        $this->assertStringContainsString('FROM proyecto_aprobado p', $src);
        $this->assertStringContainsString('INNER JOIN comite c', $src);
        $this->assertStringContainsString('c.tutor = ?', $src);
        $this->assertStringContainsString('c.asesor_1 = ?', $src);
        $this->assertStringContainsString('c.asesor_2 = ?', $src);
        $this->assertStringContainsString("CASE", $src);
        $this->assertStringContainsString("'TUTOR'", $src);
        $this->assertStringContainsString("'ASESOR_1'", $src);
        $this->assertStringContainsString("'ASESOR_2'", $src);
        $this->assertStringContainsString('ORDER BY p.fecha_creacion DESC, p.nombre ASC', $src);
    }

    public function testCommitteeMinuteParticipantsRepositoryTraeTutorAsesoresYEstudiantes(): void
    {
        $src = $this->readSource('mod/admin/users/CommitteeMinuteParticipantsRepository.php');

        $this->assertStringContainsString('class CommitteeMinuteParticipantsRepository', $src);
        $this->assertStringContainsString('function findParticipantsByProjectId', $src);
        $this->assertStringContainsString("c.tutor AS user_id", $src);
        $this->assertStringContainsString("'TUTOR' AS participant_role", $src);
        $this->assertStringContainsString("c.asesor_1 AS user_id", $src);
        $this->assertStringContainsString("'ASESOR_1' AS participant_role", $src);
        $this->assertStringContainsString("c.asesor_2 AS user_id", $src);
        $this->assertStringContainsString("'ASESOR_2' AS participant_role", $src);
        $this->assertStringContainsString('FROM proyecto_aprobado_estudiantes pae', $src);
        $this->assertStringContainsString("'ESTUDIANTE' AS participant_role", $src);
        $this->assertStringContainsString('INNER JOIN sis_user u', $src);
        $this->assertStringContainsString("FIELD(participants.participant_role, 'TUTOR', 'ASESOR_1', 'ASESOR_2', 'ESTUDIANTE')", $src);
    }

    public function testCommitteeMinuteListRepositoryListaMinutasOrdenadasPorFechaDeSesion(): void
    {
        $src = $this->readSource('mod/admin/users/CommitteeMinuteListRepository.php');

        $this->assertStringContainsString('class CommitteeMinuteListRepository', $src);
        $this->assertStringContainsString('function findMinutesByProjectId', $src);
        $this->assertStringContainsString('FROM project_minutes pm', $src);
        $this->assertStringContainsString('LEFT JOIN project_minute_attendees pma', $src);
        $this->assertStringContainsString('SUM(CASE WHEN pma.attended = 1 THEN 1 ELSE 0 END) AS attended_count', $src);
        $this->assertStringContainsString('COUNT(pma.id) AS total_participants', $src);
        $this->assertStringContainsString('pm.session_date DESC', $src);
        $this->assertStringContainsString('pm.created_at DESC', $src);
        $this->assertStringContainsString('pm.id DESC', $src);
    }

    public function testMinuteUploadProcessValidaFechaArchivoAsistentesYDuplicados(): void
    {
        $src = $this->readSource('mod/admin/users/minute_upload_process.php');

        $this->assertStringContainsString("include(\"../../login/check.php\");", $src);
        $this->assertStringContainsString("require_once __DIR__ . '/CommitteeMinuteProjectRepository.php';", $src);
        $this->assertStringContainsString("require_once __DIR__ . '/CommitteeMinuteParticipantsRepository.php';", $src);
        $this->assertStringContainsString('const MINUTE_MAX_FILE_SIZE_BYTES = 20971520;', $src);
        $this->assertStringContainsString('function validate_session_date', $src);
        $this->assertStringContainsString('function uploaded_file_error_message', $src);
        $this->assertStringContainsString('function minuteAlreadyExists', $src);
        $this->assertStringContainsString('Debe marcar al menos un asistente.', $src);
        $this->assertStringContainsString('La minuta debe estar en formato PDF.', $src);
        $this->assertStringContainsString('El archivo cargado no corresponde a un PDF válido.', $src);
        $this->assertStringContainsString('Ya existe una minuta registrada para este proyecto en esa fecha de sesión.', $src);
        $this->assertStringContainsString('INSERT INTO project_minutes', $src);
        $this->assertStringContainsString('INSERT INTO project_minute_attendees', $src);
        $this->assertStringContainsString('MINUTE_UPLOAD', $src);
        $this->assertStringContainsString('La minuta se registró correctamente.', $src);
    }

    public function testMinuteUploadProcessValidaPertenenciaAlComiteYAsistentesValidos(): void
    {
        $src = $this->readSource('mod/admin/users/minute_upload_process.php');

        $this->assertStringContainsString('findProjectsByCommitteeMember($current_user_id)', $src);
        $this->assertStringContainsString('No tiene permiso para registrar minutas en ese proyecto.', $src);
        $this->assertStringContainsString('findParticipantsByProjectId($project_id)', $src);
        $this->assertStringContainsString('El proyecto no tiene participantes válidos para registrar asistencia.', $src);
        $this->assertStringContainsString('Se detectaron asistentes no válidos para el proyecto seleccionado.', $src);
        $this->assertStringContainsString('$participant_roles[(string)$participant[\'user_id\']] = (string)$participant[\'participant_role\'];', $src);
        $this->assertStringContainsString('array_diff($selected_attendees, array_keys($participant_roles))', $src);
    }

    public function testMinuteDownloadProtegeAccesoYEntregaArchivoPdf(): void
    {
        $src = $this->readSource('mod/admin/users/minute_download.php');

        $this->assertStringContainsString("include(\"../../login/check.php\");", $src);
        $this->assertStringContainsString("require_once __DIR__ . '/CommitteeMinuteProjectRepository.php';", $src);
        $this->assertStringContainsString("\$_GET['minute_id']", $src);
        $this->assertStringContainsString('Debe indicar una minuta válida para descargar.', $src);
        $this->assertStringContainsString('FROM project_minutes pm', $src);
        $this->assertStringContainsString('findProjectsByCommitteeMember($current_user_id)', $src);
        $this->assertStringContainsString('No tiene permiso para descargar minutas de ese proyecto.', $src);
        $this->assertStringContainsString('La minuta solicitada no existe.', $src);
        $this->assertStringContainsString('La minuta no tiene contenido disponible para descarga.', $src);
        $this->assertStringContainsString('MINUTE_DOWNLOAD', $src);
        $this->assertStringContainsString("header('Content-Type: ' . \$mime_type);", $src);
        $this->assertStringContainsString("header('Content-Disposition: attachment;", $src);
        $this->assertStringContainsString('echo $file_data;', $src);
    }
}