<?php
/**
 * HU-029: Endpoint AJAX para obtener miembros de una conversación grupal
 * GET: conversation_id
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
if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Conversación no especificada']);
    exit;
}

require_once __DIR__ . '/inc/db/bdcommon.inc';
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}
$conn->set_charset("utf8");

$conversation = getConversationInfo($conn, $conversation_id, $current_user_id);
if (!$conversation) {
    $conn->close();
    echo json_encode(['success' => false, 'error' => 'No autorizado o conversación no existe']);
    exit;
}

if (($conversation['conversation_type'] ?? '') !== 'group') {
    $conn->close();
    echo json_encode(['success' => false, 'error' => 'La conversación no es grupal']);
    exit;
}

$members = getConversationParticipantsInfo($conn, $conversation_id);
$conn->close();

echo json_encode([
    'success' => true,
    'conversation_id' => $conversation_id,
    'display_name' => $conversation['display_name'] ?? 'Chat de Proyecto',
    'members' => $members
]);
