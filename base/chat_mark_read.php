<?php
/**
 * HU-029: Endpoint AJAX para marcar una conversación como leída
 * POST: conversation_id
 */
header('Content-Type: application/json; charset=utf-8');

include("mod/login/check.php");
require_once __DIR__ . '/inc/chat_functions.php';

$current_user_id = $mySessionController->getVar("usuario");
if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;

if ($conversation_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Conversación no especificada']);
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

$result = markConversationAsRead($conn, $conversation_id, $current_user_id);
$total_unread = getTotalUnreadMessages($conn, $current_user_id);

$conn->close();

echo json_encode([
    'success' => $result,
    'total_unread' => $total_unread
]);
