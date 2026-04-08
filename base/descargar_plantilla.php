<?php
/**
 * HU-022: Descarga de plantillas oficiales
 *
 * Endpoint único de descarga. Verifica sesión y permisos antes
 * de enviar el BLOB al navegador, siguiendo el mismo patrón que
 * tfg_download.php y descargar_archivo.php del sistema.
 *
 * Acceso:
 *   - Estudiante (4): solo plantillas activas
 *   - Gestor (2) / Admin (1): activas e inactivas
 *   - Otros roles autenticados: solo activas
 */

// ── Sesión ──────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

// Verificar autenticación
if (!$current_user_id) {
    http_response_code(401);
    die('No autorizado.');
}

// ── Parámetros ───────────────────────────────────────────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(400);
    die('Parámetro inválido.');
}

// ── Base de datos ────────────────────────────────────────────
require_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/plantillas_functions.php';

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    // Obtener plantilla (la función ya aplica el filtro de activo según el rol)
    $plantilla = getPlantillaParaDescarga($conn, $id, $current_user_rol);
    $conn->close();

    if (!$plantilla || empty($plantilla['archivo'])) {
        http_response_code(404);
        die('Plantilla no encontrada.');
    }

    // ── Enviar archivo al navegador ──────────────────────────
    header('Content-Type: '        . $plantilla['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($plantilla['file_name']) . '"');
    header('Content-Length: '      . $plantilla['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    echo $plantilla['archivo'];
    exit;

} catch (Exception $e) {
    error_log('HU-022 descargar_plantilla.php: ' . $e->getMessage());
    http_response_code(500);
    die('Error al procesar la descarga.');
}
