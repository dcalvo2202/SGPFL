<?php
/**
 * HU-029: Endpoint AJAX para obtener la lista de conversaciones del usuario
 * GET: (no requiere parámetros)
 * Retorna: lista de conversaciones con último mensaje y no leídos
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

include("mod/login/check.php");
require_once __DIR__ . '/inc/chat_functions.php';

function respondJson($payload) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$current_user_id = $mySessionController->getVar("usuario");
if (!$current_user_id) {
    respondJson(['success' => false, 'error' => 'No autenticado']);
}

// Conexión a BD
require_once __DIR__ . '/inc/db/bdcommon.inc';
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    respondJson(['success' => false, 'error' => 'Error de conexión']);
}
$conn->set_charset("utf8");

// Sincronizar chats de proyectos donde participa el usuario
// Esto asegura que nuevos miembros/asesores se agreguen automáticamente
syncUserProjectChats($conn, $current_user_id);

$conversations = getUserConversations($conn, $current_user_id);
$total_unread = getTotalUnreadMessages($conn, $current_user_id);

// Escapar HTML
foreach ($conversations as &$conv) {
    if (isset($conv['last_message'])) {
        $last_message = (string)($conv['last_message'] ?? '');
        $preview = function_exists('mb_substr')
            ? mb_substr($last_message, 0, 80)
            : substr($last_message, 0, 80);
        $conv['last_message'] = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
    }
    $conv['display_name'] = htmlspecialchars((string)($conv['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
}

$conn->close();

respondJson([
    'success' => true,
    'conversations' => $conversations,
    'total_unread' => $total_unread
]);

/**
 * Sincroniza los chats de proyecto del usuario.
 * Busca proyectos donde el usuario es miembro y crea/actualiza los chats grupales.
 */
function syncUserProjectChats($conn, $user_id) {
    // Proyectos donde el usuario es estudiante
    $sql = "SELECT DISTINCT pae.id_aprobado 
            FROM proyecto_aprobado_estudiantes pae
            LEFT JOIN chat_conversations cc ON cc.project_id = pae.id_aprobado AND cc.conversation_type = 'group'
            WHERE pae.estudiante_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $project_id = (int)$row['id_aprobado'];
        syncProjectGroupChat($conn, $project_id);
    }
    $stmt->close();
    
    // Proyectos donde el usuario es asesor (comité)
    $sql2 = "SELECT pa.id_aprobado 
             FROM proyecto_aprobado pa
             INNER JOIN comite c ON pa.comite_id = c.Id
             WHERE c.tutor = ? OR c.asesor_1 = ? OR c.asesor_2 = ?";
    
    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) return;
    
    $stmt2->bind_param("sss", $user_id, $user_id, $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    while ($row = $result2->fetch_assoc()) {
        $project_id = (int)$row['id_aprobado'];
        syncProjectGroupChat($conn, $project_id);
    }
    $stmt2->close();
}
