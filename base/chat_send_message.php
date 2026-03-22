<?php
/**
 * HU-029: Endpoint AJAX para enviar un mensaje
 * POST: conversation_id, message_text
 * O para iniciar nueva conversación: user_id, message_text
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

$message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';
$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$target_user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

// Validar mensaje
if (strlen($message_text) < CHAT_MIN_MESSAGE_LENGTH) {
    echo json_encode(['success' => false, 'error' => 'El mensaje no puede estar vacío']);
    exit;
}
if (strlen($message_text) > CHAT_MAX_MESSAGE_LENGTH) {
    echo json_encode(['success' => false, 'error' => 'El mensaje es demasiado largo (máx. ' . CHAT_MAX_MESSAGE_LENGTH . ' caracteres)']);
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

// Si no hay conversation_id pero hay user_id, crear/obtener conversación individual
if ($conversation_id === 0 && !empty($target_user_id)) {
    $conversation_id = getOrCreateIndividualConversation($conn, $current_user_id, $target_user_id);
    if (!$conversation_id) {
        $conn->close();
        echo json_encode(['success' => false, 'error' => 'No se pudo crear la conversación']);
        exit;
    }
}

if ($conversation_id === 0) {
    $conn->close();
    echo json_encode(['success' => false, 'error' => 'Conversación no especificada']);
    exit;
}

// Enviar mensaje
$msg_id = sendMessage($conn, $conversation_id, $current_user_id, $message_text);

if ($msg_id) {
    // Obtener info del mensaje recién creado para retornar
    $sender_name = $mySessionController->getVar("nombre");
    $sender_rol = $mySessionController->getVar("rol");
    $rol_info = getRolBadgeInfo((int)$sender_rol);
    
    $conn->close();
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $msg_id,
            'conversation_id' => $conversation_id,
            'sender_id' => $current_user_id,
            'sender_name' => $sender_name,
            'sender_roll' => $rol_info['name'],
            'sender_roll_id' => (int)$sender_rol,
            'message_text' => htmlspecialchars($message_text),
            'sent_at' => date('Y-m-d H:i:s'),
            'is_own' => true
        ],
        'conversation_id' => $conversation_id
    ]);
} else {
    $conn->close();
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el mensaje']);
}
