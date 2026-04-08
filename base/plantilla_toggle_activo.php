<?php
/**
 * HU-022: Cambiar visibilidad de una plantilla (activo/oculta)
 *
 * Recibe { id, activo } por POST y llama a toggleActivoPlantilla().
 * Usa excepciones en lugar de exit/die; responde JSON sanitizado.
 */

// ── Sesión ───────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

header('Content-Type: application/json; charset=utf-8');

/** Responde JSON de error con código HTTP opcional. */
function responderError(string $mensaje, int $httpCode = 200): void {
    if ($httpCode !== 200) {
        http_response_code($httpCode);
    }
    echo json_encode([
        'ok'    => false,
        'error' => htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'),
    ]);
}

try {
    // ── Control de acceso ────────────────────────────────────
    if (!$current_user_id) {
        responderError('No autorizado.', 401);
        return;
    }

    if (!in_array($current_user_rol, [1, 2])) {
        responderError('No tiene permisos para esta acción.', 403);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderError('Método no permitido.', 405);
        return;
    }

    // ── Validar parámetros ───────────────────────────────────
    $id     = filter_input(INPUT_POST, 'id',     FILTER_VALIDATE_INT);
    $activo = filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_INT);

    if (!$id || $id <= 0 || !in_array($activo, [0, 1], true)) {
        responderError('Parámetros inválidos.');
        return;
    }

    // ── Actualizar visibilidad ───────────────────────────────
    require_once __DIR__ . '/inc/db/bdcommon.inc';
    require_once __DIR__ . '/inc/plantillas_functions.php';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexión: ' . $conn->connect_error);
    }
    if (!$conn->set_charset('utf8mb4')) {
        throw new RuntimeException('Error al configurar el cotejamiento UTF-8.');
    }

    $ok = toggleActivoPlantilla($conn, $id, $activo);
    $conn->close();

    if (!$ok) {
        responderError('No se encontró la plantilla o no hubo cambios.');
        return;
    }

    echo json_encode(['ok' => true, 'activo' => $activo]);

} catch (Exception $e) {
    error_log('HU-022 plantilla_toggle_activo.php: ' . $e->getMessage());
    responderError('Error interno del servidor.', 500);
}
