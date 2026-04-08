<?php
/**
 * HU-022: Descarga de plantillas oficiales
 *
 * Verifica sesión y permisos antes de enviar el BLOB al navegador.
 * Usa excepciones en lugar de die/exit para manejo de errores consistente.
 */

// ── Sesión ──────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

try {
    // ── Autenticación ────────────────────────────────────────
    if (!$current_user_id) {
        http_response_code(401);
        throw new RuntimeException('No autorizado.');
    }

    // ── Validar parámetro ────────────────────────────────────
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id <= 0) {
        http_response_code(400);
        throw new RuntimeException('Parámetro inválido.');
    }

    // ── Base de datos ────────────────────────────────────────
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    require_once __DIR__ . '/inc/plantillas_functions.php';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    $plantilla = getPlantillaParaDescarga($conn, $id, $current_user_rol);
    $conn->close();

    if (!$plantilla || empty($plantilla['archivo'])) {
        http_response_code(404);
        throw new RuntimeException('Plantilla no encontrada.');
    }

    // ── Enviar archivo al navegador ──────────────────────────
    // Sanitizar file_name para evitar header injection
    $safe_filename  = preg_replace('/[^\w\-\.]/u', '_', basename($plantilla['file_name']));
    $content_length = strlen($plantilla['archivo']);

    header('Content-Type: '        . $plantilla['mime_type']);
    header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
    header('Content-Length: '      . $content_length);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    echo $plantilla['archivo'];
    exit; // Terminar ejecución después de enviar el archivo

} catch (RuntimeException $e) {
    // Errores de negocio: ya se envió el http_response_code correcto
    if (ob_get_level()) ob_end_clean();
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
} catch (Exception $e) {
    error_log('HU-022 descargar_plantilla.php: ' . $e->getMessage());
    if (!headers_sent()) http_response_code(500);
    if (ob_get_level()) ob_end_clean();
    echo 'Error al procesar la descarga.';
    exit;
}