<?php
/**
 * HU-029: Sistema de Comunicación Interna (Chat tipo Teams)
 * Funciones de negocio para el sistema de mensajería
 * 
 * Permite comunicación entre cualquier usuario registrado en la BD,
 * sin importar el rol. Incluye chats individuales y grupales de proyecto.
 */

require_once __DIR__ . '/constants.php';

// =============================== BÚSQUEDA DE USUARIOS ===============================

/**
 * Busca usuarios por nombre en la BD (tipo Teams: escribes el nombre y aparecen)
 * Retorna todos los usuarios que coincidan, sin importar el rol, excepto el usuario actual
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $search_term Término de búsqueda
 * @param string $current_user_id ID del usuario que busca (se excluye de resultados)
 * @param int $limit Límite de resultados
 * @return array Lista de usuarios encontrados con id, nombre y rol
 */
function chatSearchUsers($conn, $search_term, $current_user_id, $limit = 20) {
    $search_term = trim($search_term);
    if (strlen($search_term) < CHAT_SEARCH_MIN_LENGTH) {
        return [];
    }
    
    // Buscar por nombre, email o ID (mismo patrón que tfg_upload search_users.php)
    $sql = "SELECT u.id, u.nombre, u.email, r.roll_name, l.id_roll
            FROM sis_user u
            INNER JOIN sis_login l ON u.id = l.id
            INNER JOIN sis_rolls r ON l.id_roll = r.id_roll
            WHERE u.id != ?
              AND (u.nombre LIKE ? OR u.email LIKE ? OR u.id LIKE ?)
            ORDER BY u.nombre ASC
            LIMIT ?";
    
    $like_term = '%' . $search_term . '%';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("chatSearchUsers - Error preparando query: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ssssi", $current_user_id, $like_term, $like_term, $like_term, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id'        => $row['id'],
            'nombre'    => $row['nombre'],
            'email'     => $row['email'] ?? '',
            'roll_name' => $row['roll_name'],
            'id_roll'   => (int)$row['id_roll']
        ];
    }
    
    $stmt->close();
    return $users;
}

// =============================== CONVERSACIONES ===============================

/**
 * Obtiene o crea una conversación individual entre dos usuarios.
 * Si ya existe, retorna la existente. Si no, la crea.
 * 
 * @param mysqli $conn Conexión a la BD
 * @param string $user1_id ID del primer usuario
 * @param string $user2_id ID del segundo usuario
 * @return int|false ID de la conversación o false si falla
 */
function getOrCreateIndividualConversation($conn, $user1_id, $user2_id) {
    // Buscar si ya existe una conversación individual entre estos dos usuarios
    $sql = "SELECT cc.id 
            FROM chat_conversations cc
            INNER JOIN chat_participants cp1 ON cc.id = cp1.conversation_id AND cp1.user_id = ?
            INNER JOIN chat_participants cp2 ON cc.id = cp2.conversation_id AND cp2.user_id = ?
            WHERE cc.conversation_type = 'individual'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("getOrCreateIndividualConversation - Error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ss", $user1_id, $user2_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }
    $stmt->close();
    
    // No existe, crear nueva conversación
    $conn->begin_transaction();
    try {
        // Crear conversación
        $sql_conv = "INSERT INTO chat_conversations (conversation_type, created_by) VALUES ('individual', ?)";
        $stmt_conv = $conn->prepare($sql_conv);
        $stmt_conv->bind_param("s", $user1_id);
        $stmt_conv->execute();
        $conv_id = $conn->insert_id;
        $stmt_conv->close();
        
        // Agregar ambos participantes
        $sql_part = "INSERT INTO chat_participants (conversation_id, user_id) VALUES (?, ?)";
        $stmt_part = $conn->prepare($sql_part);
        
        $stmt_part->bind_param("is", $conv_id, $user1_id);
        $stmt_part->execute();
        
        $stmt_part->bind_param("is", $conv_id, $user2_id);
        $stmt_part->execute();
        $stmt_part->close();
        
        $conn->commit();
        return $conv_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("getOrCreateIndividualConversation - Error creando: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea un chat grupal automático para un proyecto aprobado.
 * Agrega todos los estudiantes del proyecto como participantes.
 * Si el proyecto ya tiene chat grupal, retorna el existente.
 * 
 * @param mysqli $conn Conexión a la BD
 * @param int $project_id ID del proyecto aprobado
 * @param string $created_by ID del usuario que crea (puede ser 'system')
 * @return int|false ID de la conversación grupal o false si falla
 */
function createProjectGroupChat($conn, $project_id, $created_by = 'system') {
    // Verificar si ya existe un chat grupal para este proyecto
    $existing = getProjectGroupChatId($conn, $project_id);
    if ($existing) {
        return $existing;
    }
    
    // Obtener nombre del proyecto
    $sql_name = "SELECT nombre FROM proyecto_aprobado WHERE id_aprobado = ?";
    $stmt_name = $conn->prepare($sql_name);
    if (!$stmt_name) {
        error_log("createProjectGroupChat - Error: " . $conn->error);
        return false;
    }
    $stmt_name->bind_param("i", $project_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    
    if (!($project = $result_name->fetch_assoc())) {
        $stmt_name->close();
        error_log("createProjectGroupChat - Proyecto $project_id no encontrado");
        return false;
    }
    $project_name = $project['nombre'];
    $stmt_name->close();
    
    // Obtener estudiantes del proyecto
    $students = getProjectStudents($conn, $project_id);
    if (empty($students)) {
        error_log("createProjectGroupChat - No hay estudiantes para proyecto $project_id");
        return false;
    }
    
    // Si el created_by es 'system', usar el primer estudiante
    if ($created_by === 'system') {
        $created_by = $students[0];
    }
    
    $conn->begin_transaction();
    try {
        // Crear conversación grupal
        $sql_conv = "INSERT INTO chat_conversations (conversation_type, project_id, group_name, created_by) 
                     VALUES ('group', ?, ?, ?)";
        $stmt_conv = $conn->prepare($sql_conv);
        $stmt_conv->bind_param("iss", $project_id, $project_name, $created_by);
        $stmt_conv->execute();
        $conv_id = $conn->insert_id;
        $stmt_conv->close();
        
        // Agregar estudiantes como participantes
        $sql_part = "INSERT INTO chat_participants (conversation_id, user_id) VALUES (?, ?)";
        $stmt_part = $conn->prepare($sql_part);
        
        foreach ($students as $student_id) {
            $stmt_part->bind_param("is", $conv_id, $student_id);
            $stmt_part->execute();
        }
        $stmt_part->close();
        
        // Agregar asesores del comité si existen
        $advisors = getProjectAdvisors($conn, $project_id);
        if (!empty($advisors)) {
            $sql_adv = "INSERT IGNORE INTO chat_participants (conversation_id, user_id) VALUES (?, ?)";
            $stmt_adv = $conn->prepare($sql_adv);
            foreach ($advisors as $advisor_id) {
                $stmt_adv->bind_param("is", $conv_id, $advisor_id);
                $stmt_adv->execute();
            }
            $stmt_adv->close();
        }
        
        $conn->commit();
        return $conv_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("createProjectGroupChat - Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el ID del chat grupal de un proyecto (si existe)
 * 
 * @param mysqli $conn Conexión
 * @param int $project_id ID del proyecto aprobado
 * @return int|false ID de conversación o false
 */
function getProjectGroupChatId($conn, $project_id) {
    $sql = "SELECT id FROM chat_conversations WHERE project_id = ? AND conversation_type = 'group' LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }
    $stmt->close();
    return false;
}

/**
 * Obtiene los IDs de estudiantes de un proyecto aprobado
 */
function getProjectStudents($conn, $project_id) {
    $students = [];
    $sql = "SELECT estudiante_id FROM proyecto_aprobado_estudiantes WHERE id_aprobado = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $students;
    
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row['estudiante_id'];
    }
    $stmt->close();
    return $students;
}

/**
 * Obtiene los IDs de asesores/tutores del comité asociado a un proyecto
 */
function getProjectAdvisors($conn, $project_id) {
    $advisors = [];
    $sql = "SELECT c.tutor, c.asesor_1, c.asesor_2 
            FROM comite c 
            INNER JOIN proyecto_aprobado pa ON pa.comite_id = c.Id
            WHERE pa.id_aprobado = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $advisors;
    
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Agregar solo si no están vacíos
        if (!empty($row['tutor'])) $advisors[] = $row['tutor'];
        if (!empty($row['asesor_1'])) $advisors[] = $row['asesor_1'];
        if (!empty($row['asesor_2'])) $advisors[] = $row['asesor_2'];
    }
    $stmt->close();
    
    // También buscar supervisor en registered_projects
    $sql2 = "SELECT rp.supervisor_id FROM registered_projects rp
             INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
             INNER JOIN proyecto_aprobado pa ON pa.proposal_id = tp.id
             WHERE pa.id_aprobado = ? AND rp.supervisor_id IS NOT NULL";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param("i", $project_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($row2 = $result2->fetch_assoc()) {
            if (!empty($row2['supervisor_id']) && !in_array($row2['supervisor_id'], $advisors)) {
                $advisors[] = $row2['supervisor_id'];
            }
        }
        $stmt2->close();
    }
    
    return array_unique($advisors);
}

/**
 * Agrega un asesor automáticamente al chat grupal de un proyecto.
 * Llamar cuando se asigne un asesor a un comité/proyecto.
 * 
 * @param mysqli $conn Conexión
 * @param int $project_id ID del proyecto aprobado
 * @param string $advisor_id ID del asesor
 * @return bool true si se agregó correctamente
 */
function addAdvisorToProjectChat($conn, $project_id, $advisor_id) {
    $conv_id = getProjectGroupChatId($conn, $project_id);
    if (!$conv_id) {
        // Si no existe chat grupal, crearlo (incluirá al asesor automáticamente)
        $conv_id = createProjectGroupChat($conn, $project_id);
        if (!$conv_id) return false;
        
        // Verificar si el asesor ya fue incluido en la creación
        if (isParticipant($conn, $conv_id, $advisor_id)) {
            return true;
        }
    }
    
    return addParticipantToConversation($conn, $conv_id, $advisor_id);
}

/**
 * Agrega un nuevo estudiante al chat grupal de un proyecto.
 * Llamar cuando se agrega un estudiante al grupo/proyecto.
 * 
 * @param mysqli $conn Conexión
 * @param int $project_id ID del proyecto aprobado
 * @param string $student_id ID del estudiante
 * @return bool true si se agregó correctamente
 */
function addStudentToProjectChat($conn, $project_id, $student_id) {
    $conv_id = getProjectGroupChatId($conn, $project_id);
    if (!$conv_id) {
        // Si no existe chat grupal, crearlo
        $conv_id = createProjectGroupChat($conn, $project_id, $student_id);
        return $conv_id !== false;
    }
    
    return addParticipantToConversation($conn, $conv_id, $student_id);
}

/**
 * Agrega un participante a una conversación (INSERT IGNORE para evitar duplicados)
 */
function addParticipantToConversation($conn, $conversation_id, $user_id) {
    // Primero verificar si ya existe pero inactivo
    $sql_check = "SELECT id, is_active FROM chat_participants WHERE conversation_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) return false;
    
    $stmt_check->bind_param("is", $conversation_id, $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt_check->close();
        // Si existe pero inactivo, reactivar
        if ($row['is_active'] == 0) {
            $sql_update = "UPDATE chat_participants SET is_active = 1, joined_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $row['id']);
            $result = $stmt_update->execute();
            $stmt_update->close();
            return $result;
        }
        return true; // Ya existe y está activo
    }
    $stmt_check->close();
    
    // No existe, crear
    $sql = "INSERT INTO chat_participants (conversation_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("is", $conversation_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Verifica si un usuario es participante activo de una conversación
 */
function isParticipant($conn, $conversation_id, $user_id) {
    $sql = "SELECT id FROM chat_participants WHERE conversation_id = ? AND user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("is", $conversation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Sincroniza el chat grupal de un proyecto: agrega participantes faltantes
 * (estudiantes nuevos y asesores). Útil para llamar periódicamente o
 * cuando se accede al chat.
 * 
 * @param mysqli $conn Conexión
 * @param int $project_id ID del proyecto aprobado
 * @return bool
 */
function syncProjectGroupChat($conn, $project_id) {
    $conv_id = getProjectGroupChatId($conn, $project_id);
    if (!$conv_id) {
        // Si no hay chat, crearlo
        $conv_id = createProjectGroupChat($conn, $project_id);
        return $conv_id !== false;
    }
    
    // Obtener participantes actuales
    $current_participants = getConversationParticipantIds($conn, $conv_id);
    
    // Obtener estudiantes del proyecto
    $students = getProjectStudents($conn, $project_id);
    foreach ($students as $student_id) {
        if (!in_array($student_id, $current_participants)) {
            addParticipantToConversation($conn, $conv_id, $student_id);
        }
    }
    
    // Obtener asesores del proyecto
    $advisors = getProjectAdvisors($conn, $project_id);
    foreach ($advisors as $advisor_id) {
        if (!in_array($advisor_id, $current_participants)) {
            addParticipantToConversation($conn, $conv_id, $advisor_id);
        }
    }
    
    return true;
}

/**
 * Obtiene los IDs de participantes activos de una conversación
 */
function getConversationParticipantIds($conn, $conversation_id) {
    $ids = [];
    $sql = "SELECT user_id FROM chat_participants WHERE conversation_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $ids;
    
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['user_id'];
    }
    $stmt->close();
    return $ids;
}

// =============================== MENSAJES ===============================

/**
 * Envía un mensaje en una conversación
 * 
 * @param mysqli $conn Conexión
 * @param int $conversation_id ID de la conversación
 * @param string $sender_id ID del remitente
 * @param string $message_text Texto del mensaje
 * @return int|false ID del mensaje creado o false si falla
 */
function sendMessage($conn, $conversation_id, $sender_id, $message_text) {
    // Validar que el usuario es participante
    if (!isParticipant($conn, $conversation_id, $sender_id)) {
        error_log("sendMessage - Usuario $sender_id no es participante de conversación $conversation_id");
        return false;
    }
    
    // Validar longitud del mensaje
    $message_text = trim($message_text);
    if (strlen($message_text) < CHAT_MIN_MESSAGE_LENGTH || strlen($message_text) > CHAT_MAX_MESSAGE_LENGTH) {
        return false;
    }
    
    $sql = "INSERT INTO chat_messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("sendMessage - Error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iss", $conversation_id, $sender_id, $message_text);
    if ($stmt->execute()) {
        $msg_id = $conn->insert_id;
        $stmt->close();
        
        // Actualizar timestamp de la conversación
        $sql_update = "UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $conversation_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Marcar como leído para el remitente
        markConversationAsRead($conn, $conversation_id, $sender_id);
        
        return $msg_id;
    }
    
    $stmt->close();
    return false;
}

/**
 * Obtiene los mensajes de una conversación con información del remitente y su rol
 * 
 * @param mysqli $conn Conexión
 * @param int $conversation_id ID de la conversación
 * @param string $current_user_id ID del usuario actual (para verificar acceso)
 * @param int $limit Cantidad de mensajes
 * @param int $offset Offset para paginación
 * @param int|null $after_id Solo mensajes con ID mayor que este (para polling)
 * @return array Lista de mensajes
 */
function getConversationMessages($conn, $conversation_id, $current_user_id, $limit = 50, $offset = 0, $after_id = null) {
    // Verificar que el usuario es participante
    if (!isParticipant($conn, $conversation_id, $current_user_id)) {
        return [];
    }
    
    $messages = [];
    
    if ($after_id !== null) {
        $sql = "SELECT m.id, m.conversation_id, m.sender_id, m.message_text, m.sent_at, m.edited_at, m.is_deleted,
                       u.nombre AS sender_name, r.roll_name AS sender_roll, l.id_roll AS sender_roll_id
                FROM chat_messages m
                INNER JOIN sis_user u ON m.sender_id = u.id
                INNER JOIN sis_login l ON m.sender_id = l.id
                INNER JOIN sis_rolls r ON l.id_roll = r.id_roll
                WHERE m.conversation_id = ? AND m.id > ? AND m.is_deleted = 0
                ORDER BY m.sent_at ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ii", $conversation_id, $after_id);
    } else {
        $sql = "SELECT m.id, m.conversation_id, m.sender_id, m.message_text, m.sent_at, m.edited_at, m.is_deleted,
                       u.nombre AS sender_name, r.roll_name AS sender_roll, l.id_roll AS sender_roll_id
                FROM chat_messages m
                INNER JOIN sis_user u ON m.sender_id = u.id
                INNER JOIN sis_login l ON m.sender_id = l.id
                INNER JOIN sis_rolls r ON l.id_roll = r.id_roll
                WHERE m.conversation_id = ? AND m.is_deleted = 0
                ORDER BY m.sent_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("iii", $conversation_id, $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['sender_roll_id'] = (int)$row['sender_roll_id'];
        $row['is_own'] = ($row['sender_id'] === $current_user_id);
        $messages[] = $row;
    }
    $stmt->close();
    
    // Si no es polling (after_id), invertir para orden cronológico
    if ($after_id === null) {
        $messages = array_reverse($messages);
    }
    
    return $messages;
}

/**
 * Obtiene las conversaciones del usuario actual con último mensaje y conteo de no leídos
 * 
 * @param mysqli $conn Conexión
 * @param string $user_id ID del usuario
 * @return array Lista de conversaciones
 */
function getUserConversations($conn, $user_id) {
    $sql = "SELECT cc.id, cc.conversation_type, cc.project_id, cc.group_name, cc.updated_at,
                   cp.last_read_at,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id 
                    AND cm.is_deleted = 0 
                    AND cm.sent_at > COALESCE(cp.last_read_at, '1970-01-01')
                    AND cm.sender_id != ?) AS unread_count,
                   (SELECT cm2.message_text FROM chat_messages cm2 
                    WHERE cm2.conversation_id = cc.id AND cm2.is_deleted = 0
                    ORDER BY cm2.sent_at DESC LIMIT 1) AS last_message,
                   (SELECT cm3.sent_at FROM chat_messages cm3 
                    WHERE cm3.conversation_id = cc.id AND cm3.is_deleted = 0
                    ORDER BY cm3.sent_at DESC LIMIT 1) AS last_message_at,
                   (SELECT cm4.sender_id FROM chat_messages cm4 
                    WHERE cm4.conversation_id = cc.id AND cm4.is_deleted = 0
                    ORDER BY cm4.sent_at DESC LIMIT 1) AS last_sender_id
            FROM chat_conversations cc
            INNER JOIN chat_participants cp ON cc.id = cp.conversation_id AND cp.user_id = ? AND cp.is_active = 1
            ORDER BY COALESCE(
                (SELECT MAX(cm5.sent_at) FROM chat_messages cm5 WHERE cm5.conversation_id = cc.id AND cm5.is_deleted = 0),
                cc.updated_at
            ) DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("getUserConversations - Error: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ss", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $row['unread_count'] = (int)$row['unread_count'];
        
        // Para chats individuales, obtener info del otro participante
        if ($row['conversation_type'] === 'individual') {
            $other_user = getOtherParticipant($conn, $row['id'], $user_id);
            $row['other_user'] = $other_user;
            $row['display_name'] = $other_user ? $other_user['nombre'] : 'Usuario Desconocido';
        } else {
            $row['display_name'] = $row['group_name'] ?: 'Chat de Proyecto';
            $row['participant_count'] = countActiveParticipants($conn, $row['id']);
        }
        
        $conversations[] = $row;
    }
    $stmt->close();
    
    return $conversations;
}

/**
 * Obtiene la información del otro participante en un chat individual
 */
function getOtherParticipant($conn, $conversation_id, $current_user_id) {
    $sql = "SELECT u.id, u.nombre, r.roll_name, l.id_roll
            FROM chat_participants cp
            INNER JOIN sis_user u ON cp.user_id = u.id
            INNER JOIN sis_login l ON cp.user_id = l.id
            INNER JOIN sis_rolls r ON l.id_roll = r.id_roll
            WHERE cp.conversation_id = ? AND cp.user_id != ? AND cp.is_active = 1
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param("is", $conversation_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user = $result->fetch_assoc();
    if ($user) {
        $user['id_roll'] = (int)$user['id_roll'];
    }
    $stmt->close();
    return $user;
}

/**
 * Cuenta participantes activos de una conversación
 */
function countActiveParticipants($conn, $conversation_id) {
    $sql = "SELECT COUNT(*) AS cnt FROM chat_participants WHERE conversation_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'];
}

/**
 * Marca una conversación como leída por un usuario
 */
function markConversationAsRead($conn, $conversation_id, $user_id) {
    $sql = "UPDATE chat_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("is", $conversation_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Obtiene el total de mensajes no leídos del usuario en todas sus conversaciones
 * 
 * @param mysqli $conn Conexión
 * @param string $user_id ID del usuario
 * @return int Total de mensajes no leídos
 */
function getTotalUnreadMessages($conn, $user_id) {
    $sql = "SELECT COALESCE(SUM(unread), 0) AS total FROM (
                SELECT COUNT(*) AS unread
                FROM chat_messages cm
                INNER JOIN chat_participants cp ON cm.conversation_id = cp.conversation_id
                WHERE cp.user_id = ? 
                  AND cp.is_active = 1
                  AND cm.is_deleted = 0
                  AND cm.sender_id != ?
                  AND cm.sent_at > COALESCE(cp.last_read_at, '1970-01-01')
            ) AS sub";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    
    $stmt->bind_param("ss", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)$row['total'];
}

/**
 * Obtiene info detallada de una conversación
 */
function getConversationInfo($conn, $conversation_id, $current_user_id) {
    $sql = "SELECT cc.*, cp.last_read_at
            FROM chat_conversations cc
            INNER JOIN chat_participants cp ON cc.id = cp.conversation_id
            WHERE cc.id = ? AND cp.user_id = ? AND cp.is_active = 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param("is", $conversation_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();
    
    if (!$info) return null;
    
    if ($info['conversation_type'] === 'individual') {
        $other = getOtherParticipant($conn, $conversation_id, $current_user_id);
        $info['other_user'] = $other;
        $info['display_name'] = $other ? $other['nombre'] : 'Usuario';
    } else {
        $info['display_name'] = $info['group_name'] ?: 'Chat de Proyecto';
        $info['participants'] = getConversationParticipantsInfo($conn, $conversation_id);
    }
    
    return $info;
}

/**
 * Obtiene info de todos los participantes de una conversación (con nombre y rol)
 */
function getConversationParticipantsInfo($conn, $conversation_id) {
    $sql = "SELECT u.id, u.nombre, r.roll_name, l.id_roll
            FROM chat_participants cp
            INNER JOIN sis_user u ON cp.user_id = u.id
            INNER JOIN sis_login l ON cp.user_id = l.id
            INNER JOIN sis_rolls r ON l.id_roll = r.id_roll
            WHERE cp.conversation_id = ? AND cp.is_active = 1
            ORDER BY u.nombre";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $participants = [];
    while ($row = $result->fetch_assoc()) {
        $row['id_roll'] = (int)$row['id_roll'];
        $participants[] = $row;
    }
    $stmt->close();
    return $participants;
}

/**
 * Obtiene el nombre del rol con su color para el badge
 * 
 * @param int $roll_id ID del rol
 * @return array [roll_name, color]
 */
function getRolBadgeInfo($roll_id) {
    $colors = CHAT_ROL_COLORS;
    $names = [
        ROL_ADMIN      => 'Administrador',
        ROL_GESTOR     => 'Gestor Académico',
        ROL_CTFG       => 'CTFG',
        ROL_ESTUDIANTE => 'Estudiante',
        ROL_ASESOR     => 'Asesor'
    ];
    
    return [
        'name'  => $names[$roll_id] ?? 'Desconocido',
        'color' => $colors[$roll_id] ?? '#6c757d'
    ];
}

/**
 * Verifica y crea chats grupales para todos los proyectos existentes que no tengan chat
 * Útil para inicialización del sistema o migración
 * 
 * @param mysqli $conn Conexión
 * @return int Cantidad de chats creados
 */
function ensureAllProjectGroupChats($conn) {
    $sql = "SELECT pa.id_aprobado
            FROM proyecto_aprobado pa
            LEFT JOIN chat_conversations cc ON pa.id_aprobado = cc.project_id AND cc.conversation_type = 'group'
            WHERE cc.id IS NULL";
    
    $result = $conn->query($sql);
    if (!$result) return 0;
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        if (createProjectGroupChat($conn, (int)$row['id_aprobado'])) {
            $count++;
        }
    }
    return $count;
}
