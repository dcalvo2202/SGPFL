<?php
/**
 * HU-036 - Descarga del resumen consolidado por estudiante.
 *
 * Responsabilidad única de este archivo:
 * - validar parámetros de entrada
 * - validar sesión / rol autorizado
 * - construir el resumen consolidado
 * - generar la descarga del PDF
 *
 * No renderiza paneles HTML ni consulta datos fuera del summary service.
 */

declare(strict_types=1);

ob_start();

include('../../login/check.php');
require_once __DIR__ . '/student_summary_service.php';
require_once __DIR__ . '/student_summary_pdf.php';

/**
 * Carga la configuración de BD evitando colisiones con variables globales
 * como $usuario usadas por la sesión.
 *
 * @return array{host:string,user:string,pass:string,name:string}
 */
function ss_load_db_config(string $path): array
{
    $db_host = null;
    $usuario = null;
    $clave = null;
    $db = null;

    require $path;

    return [
        'host' => (string)$db_host,
        'user' => (string)$usuario,
        'pass' => (string)$clave,
        'name' => (string)$db,
    ];
}

/**
 * Convierte un nombre libre en un nombre de archivo seguro.
 */
function ss_slug_filename(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'estudiante';
    }

    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?? 'estudiante';
    $value = trim($value, '_-');

    return $value !== '' ? $value : 'estudiante';
}

try {
    $currentUserId = $mySessionController->getVar('usuario');
    $currentUserRole = (int)$mySessionController->getVar('rol');

    if (!$currentUserId) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(401);
        exit('No autorizado.');
    }

    // Secretaría / Gestor Académico y administrador.
    $allowedRoles = [1, 2];
    if (!in_array($currentUserRole, $allowedRoles, true)) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(403);
        exit('No tiene permisos para descargar este resumen.');
    }

    $studentIdRaw = $_GET['student_id'] ?? $_GET['id'] ?? null;
    if ($studentIdRaw === null || !preg_match('/^\d+$/', (string)$studentIdRaw)) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(400);
        exit('Parametro student_id invalido.');
    }

    $studentId = (int)$studentIdRaw;
    if ($studentId <= 0) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code(400);
        exit('Parametro student_id invalido.');
    }

    $dbConfig = ss_load_db_config(__DIR__ . '/../../../inc/db/bdcommon.inc');

    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['pass'],
        $dbConfig['name']
    );

    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexion a la base de datos: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8');

    $summary = buildStudentSummary($conn, $studentId);

    $student = $summary['student'] ?? [];
    $studentName = (string)($student['nombre'] ?? 'estudiante');
    $studentCode = (string)($student['id'] ?? $studentId);

    $fileName = sprintf(
        'resumen_consolidado_%s_%s.pdf',
        ss_slug_filename($studentName),
        ss_slug_filename($studentCode)
    );

    $conn->close();

    // Limpieza total antes de que FPDF envíe headers y contenido binario.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    outputStudentSummaryPdf($summary, 'D', $fileName);
    exit;
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    error_log('Error en download_student_summary_pdf.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar el resumen consolidado.';
    exit;
}
