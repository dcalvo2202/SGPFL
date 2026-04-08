<?php
/**
 * HU-022: Eliminación de plantillas oficiales
 *
 * Recibe el ID por POST, verifica permisos y elimina la plantilla.
 * Responde JSON para SweetAlert2 en el panel.
 * Solo accesible por Gestor (2) y Administrador (1).
 */

// ── Sesión ──────────────────────────────────────────────────
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id  = $mySessionController->getVar('usuario');
$current_user_rol = (int)$mySessionController->getVar('rol');

header('Content-Type: application/json; charset=utf-8');

// ── Control de acceso ────────────────────────────────────────
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

if (!in_array($current_user_rol, [1, 2])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tiene permisos para esta acción.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ── Validar ID ───────────────────────────────────────────────
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
    exit;
}

// ── Eliminar ─────────────────────────────────────────────────
require_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/inc/plantillas_functions.php';

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');

    $ok = eliminarPlantilla($conn, $id);
    $conn->close();

    if (!$ok) {
        echo json_encode(['ok' => false, 'error' => 'No se encontró la plantilla o ya fue eliminada.']);
        exit;
    }

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    error_log('HU-022 plantilla_delete_process.php: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
}
