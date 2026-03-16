<?php
/**
 * HU-029: Endpoint AJAX para obtener la lista de conversaciones del usuario
 * GET: (no requiere parámetros)
 * Retorna: lista de conversaciones con último mensaje y no leídos
 */
header('Content-Type: application/json; charset=utf-8');

include("mod/login/check.php");
require_once __DIR__ . '/inc/chat_functions.php';

$current_user_id = $mySessionController->getVar("usuario");
if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
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

// Sincronizar chats de proyectos donde participa el usuario
// Esto asegura que nuevos miembros/asesores se agreguen automáticamente
syncUserProjectChats($conn, $current_user_id);

$conversations = getUserConversations($conn, $current_user_id);
$total_unread = getTotalUnreadMessages($conn, $current_user_id);

// Escapar HTML
foreach ($conversations as &$conv) {
    if (isset($conv['last_message'])) {
        $conv['last_message'] = htmlspecialchars(mb_substr($conv['last_message'], 0, 80));
    }
    $conv['display_name'] = htmlspecialchars($conv['display_name']);
}

$conn->close();

echo json_encode([
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
