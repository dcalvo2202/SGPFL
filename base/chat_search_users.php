<?php
/**
 * HU-029: Endpoint AJAX para buscar usuarios por nombre
 * Retorna JSON con usuarios que coinciden, sin importar el rol
 * 
 * GET: ?term=nombre_parcial
 */
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
include("mod/login/check.php");
require_once __DIR__ . '/inc/chat_functions.php';

$current_user_id = $mySessionController->getVar("usuario");
if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < CHAT_SEARCH_MIN_LENGTH) {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

// Conexión a BD
require_once __DIR__ . '/inc/db/bdcommon.inc';
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}
$conn->set_charset("utf8");

$users = chatSearchUsers($conn, $term, $current_user_id);
$conn->close();

echo json_encode(['success' => true, 'users' => $users]);
