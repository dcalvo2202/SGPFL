<?php
/**
 * HU-022: Eliminación de plantillas oficiales
 *
 * Recibe el ID por POST, verifica permisos y elimina la plantilla.
 * Usa excepciones en lugar de exit/die; responde JSON sanitizado.
 */

// ── Sesión ──────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

header('Content-Type: application/json; charset=utf-8');

/** Responde JSON de error con código HTTP opcional. */
function responderError(string $mensaje, int $httpCode = 200): void {
    if (ob_get_level()) ob_end_clean();
    if ($httpCode !== 200) {
        http_response_code($httpCode);
    }
    echo json_encode([
        'ok'    => false,
        'error' => htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'),
    ]);
        return;
}

try {
    // ── Control de acceso ────────────────────────────────────
    if (!$current_user_id) {
        responderError('No autorizado.', 401);
    }

    if (!in_array($current_user_rol, [1, 2])) {
        responderError('No tiene permisos para esta acción.', 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderError('Método no permitido.', 405);
    }

    // ── Validar ID ───────────────────────────────────────────
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id || $id <= 0) {
        responderError('ID inválido.');
    }

    // ── Eliminar ─────────────────────────────────────────────
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    require_once __DIR__ . '/inc/plantillas_functions.php';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    $ok = eliminarPlantilla($conn, $id);
    $conn->close();

    if (!$ok) {
        responderError('No se encontró la plantilla o ya fue eliminada.');
    }

    echo json_encode(['ok' => true]);
        return;

} catch (Exception $e) {
    error_log('HU-022 plantilla_delete_process.php: ' . $e->getMessage());
    if (ob_get_level()) ob_end_clean();
    responderError('Error interno del servidor.', 500);
}
