<?php
/**
 * HU-029: Endpoint AJAX para obtener mensajes de una conversación
 * GET: conversation_id, [after_id] (para polling), [limit], [offset]
 */
header('Content-Type: application/json; charset=utf-8');

include("mod/login/check.php");
require_once __DIR__ . '/inc/chat_functions.php';

$current_user_id = $mySessionController->getVar("usuario");
if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$after_id = isset($_GET['after_id']) ? (int)$_GET['after_id'] : null;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

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

$messages = getConversationMessages($conn, $conversation_id, $current_user_id, $limit, $offset, $after_id);

// Escapar HTML de los mensajes para seguridad XSS
foreach ($messages as &$msg) {
    $msg['message_text'] = htmlspecialchars($msg['message_text']);
    $msg['sender_name'] = htmlspecialchars($msg['sender_name']);
}

// Obtener info de la conversación
$conv_info = getConversationInfo($conn, $conversation_id, $current_user_id);

$conn->close();

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'conversation' => $conv_info ? [
        'id' => $conv_info['id'],
        'type' => $conv_info['conversation_type'],
        'display_name' => $conv_info['display_name'] ?? '',
        'project_id' => $conv_info['project_id']
    ] : null
]);
