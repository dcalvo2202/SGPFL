
<?php
/**
 * HU-037: Sistema de Alertas Internas
 * Funciones para gestionar alertas/notificaciones internas del sistema
 * 
 * IMPORTANTE: Este sistema es INTERNO, no envía correos electrónicos.
 */

/**
 * Registra una alerta para un usuario específico
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario destinatario
 * @param string $subject Asunto de la alerta
 * @param string $message Mensaje detallado
 * @param string $alert_type Tipo de alerta (debe coincidir con ENUM de la BD)
 * @param string $priority Prioridad: 'Alta', 'Media', 'Baja'
 * @param string|null $related_entity_type Tipo de entidad relacionada (ej: 'proposal', 'document')
 * @param int|null $related_entity_id ID de la entidad relacionada
 * @return bool True si se registró correctamente
 */
function registerAlert($conn, $user_id, $subject, $message, $alert_type = 'Informativa', $priority = 'Media', $related_entity_type = null, $related_entity_id = null) {
    $sql = "INSERT INTO user_alerts (user_id, subject, message, alert_type, priority, related_entity_type, related_entity_id, sent_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparando alerta: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssssssi", $user_id, $subject, $message, $alert_type, $priority, $related_entity_type, $related_entity_id);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Error registrando alerta para usuario $user_id: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

/**
 * Registra una alerta para todos los usuarios de un rol específico
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $rol_id ID del rol
 * @param string $subject Asunto de la alerta
 * @param string $message Mensaje detallado
 * @param string $alert_type Tipo de alerta
 * @param string $priority Prioridad
 * @param string|null $related_entity_type Tipo de entidad relacionada
 * @param int|null $related_entity_id ID de la entidad relacionada
 * @return int Número de alertas registradas
 */
function registerAlertToRole($conn, $rol_id, $subject, $message, $alert_type = 'Informativa', $priority = 'Media', $related_entity_type = null, $related_entity_id = null) {
    // Obtener usuarios del rol - El rol está en sis_login.id_roll, no en sis_user
    $sql_users = "SELECT l.id FROM sis_login l WHERE l.id_roll = ?";
    $stmt_users = $conn->prepare($sql_users);
    if (!$stmt_users) {
        error_log("Error obteniendo usuarios del rol $rol_id: " . $conn->error);
        return 0;
    }
    
    $stmt_users->bind_param("i", $rol_id);
    $stmt_users->execute();
    $result = $stmt_users->get_result();
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        if (registerAlert($conn, $row['id'], $subject, $message, $alert_type, $priority, $related_entity_type, $related_entity_id)) {
            $count++;
        }
    }
    
    $stmt_users->close();
    return $count;
}

/**
 * HU-032
 * Obtiene los usuarios del comité oficial asignado a una propuesta,
 * usando la relación proyecto_aprobado -> comite.
 *
 * @param mysqli $conn
 * @param int $proposal_id
 * @return array IDs de usuario únicos del comité
 */
function getOfficialCommitteeUserIdsByProposalId($conn, $proposal_id) {
    $proposal_id = (int)$proposal_id;
    if ($proposal_id <= 0) {
        return [];
    }

    $sql = "SELECT c.tutor, c.asesor_1, c.asesor_2
            FROM proyecto_aprobado pa
            INNER JOIN comite c ON c.Id = pa.comite_id
            WHERE pa.proposal_id = ?
            ORDER BY pa.id_aprobado DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("HU-032: Error preparando consulta de comité oficial: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        error_log("HU-032: No se encontró comité oficial para proposal_id {$proposal_id}");
        return [];
    }

    $candidate_ids = array_filter([
        $row['tutor'] ?? null,
        $row['asesor_1'] ?? null,
        $row['asesor_2'] ?? null
    ]);

    $committee_ids = [];

    // Verificar que el usuario tenga cuenta en sis_login para poder recibir alertas
    $sql_user = "SELECT 1 FROM sis_login WHERE id = ? LIMIT 1";
    $stmt_user = $conn->prepare($sql_user);

    if (!$stmt_user) {
        error_log("HU-032: Error preparando validación de usuarios del comité: " . $conn->error);
        return array_values(array_unique($candidate_ids));
    }

    foreach ($candidate_ids as $user_id) {
        $stmt_user->bind_param("s", $user_id);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result();
        if ($user_result && $user_result->fetch_row()) {
            $committee_ids[] = $user_id;
        }
    }

    $stmt_user->close();

    return array_values(array_unique($committee_ids));
}

/**
 * HU-032
 * Verifica si el usuario ya está cubierto por la alerta general
 * de documento final (Gestor o CTFG).
 *
 * @param mysqli $conn
 * @param string $user_id
 * @return bool
 */
function isCoveredByGeneralFinalDocumentAlert($conn, $user_id) {
    $sql = "SELECT id_roll
            FROM sis_login
            WHERE id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("HU-032: Error preparando validación de rol para {$user_id}: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return false;
    }

    $rol_id = (int)$row['id_roll'];

    // Ya recibe la alerta general por rol:
    // 2 = Gestor Académico
    // 3 = CTFG
    return in_array($rol_id, [2, 3], true);
}

/**
 * HU-032
 * Registra alertas para los miembros del comité oficial
 * cuando el estudiante sube formalmente el Documento Final.
 *
 * Regla especial:
 * - Si el usuario ya recibe la alerta general por ser Gestor (rol 2)
 *   o CTFG (rol 3), NO se le envía la alerta del comité para evitar
 *   duplicidad en la campana.
 *
 * @param mysqli $conn
 * @param int $proposal_id
 * @param string $student_name
 * @param string $proposal_title
 * @param int $document_id
 * @return int Cantidad de alertas enviadas
 */
function registerFinalDocumentCommitteeAlert($conn, $proposal_id, $student_name, $proposal_title, $document_id) {
    $committee_ids = getOfficialCommitteeUserIdsByProposalId($conn, $proposal_id);

    if (empty($committee_ids)) {
        error_log("HU-032: No hay miembros de comité para notificar en proposal_id {$proposal_id}");
        return 0;
    }

    $subject = "Nuevo Documento Final TFG Recibido";
    $message = "El estudiante ha realizado una entrega formal en el sistema.\n\n";
    $message .= "Estudiante: {$student_name}\n";
    $message .= "Título: {$proposal_title}\n";
    $message .= "Entrega: Documento Final\n\n";
    $message .= "El documento ya fue registrado y está disponible para seguimiento.";

    $count = 0;

    foreach ($committee_ids as $committee_user_id) {
        // Opción B:
        // Si ya recibe la alerta general por ser Gestor o CTFG,
        // no enviar la alerta HU-032 para evitar duplicidad.
        if (isCoveredByGeneralFinalDocumentAlert($conn, $committee_user_id)) {
            error_log("HU-032: Usuario {$committee_user_id} omitido por ya recibir alerta general (rol 2/3)");
            continue;
        }

        if (registerAlert(
            $conn,
            $committee_user_id,
            $subject,
            $message,
            'Documento Final',
            'Alta',
            'document',
            $document_id
        )) {
            $count++;
        }
    }

    return $count;
}

/**
 * HU-032
 * Verifica si ya existe una alerta igual reciente para evitar duplicados.
 *
 * @param mysqli $conn
 * @param string $user_id
 * @param string $subject
 * @param string $alert_type
 * @param string|null $related_entity_type
 * @param int|null $related_entity_id
 * @param int $seconds Ventana de tiempo en segundos
 * @return bool
 */
function existsRecentDuplicateCommitteeAlert($conn, $user_id, $subject, $alert_type, $related_entity_type = null, $related_entity_id = null, $seconds = 60) {
    $seconds = max(1, (int)$seconds);
    $cutoff = date('Y-m-d H:i:s', time() - $seconds);

    $sql = "SELECT 1
            FROM user_alerts
            WHERE user_id = ?
              AND subject = ?
              AND alert_type = ?
              AND (
                    (related_entity_type = ?)
                    OR (related_entity_type IS NULL AND ? IS NULL)
                  )
              AND (
                    (related_entity_id = ?)
                    OR (related_entity_id IS NULL AND ? IS NULL)
                  )
              AND sent_at >= ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("HU-032: Error preparando validación de duplicado: " . $conn->error);
        return false;
    }

    $stmt->bind_param(
        "sssssiss",
        $user_id,
        $subject,
        $alert_type,
        $related_entity_type,
        $related_entity_type,
        $related_entity_id,
        $related_entity_id,
        $cutoff
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->fetch_row();
    $stmt->close();

    return (bool)$exists;
}

/**
 * Obtiene las alertas de un usuario
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario
 * @param int $limit Número máximo de alertas a obtener
 * @param bool $unread_only Solo alertas no leídas
 * @return array Lista de alertas
 */
function getUserAlerts($conn, $user_id, $limit = 50, $unread_only = false) {
    $sql = "SELECT id, subject, message, alert_type, priority, related_entity_type, related_entity_id, read_at, sent_at 
            FROM user_alerts 
            WHERE user_id = ?";
    
    if ($unread_only) {
        $sql .= " AND read_at IS NULL";
    }
    
    $sql .= " ORDER BY sent_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error obteniendo alertas: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("si", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    $stmt->close();
    return $alerts;
}

/**
 * Cuenta las alertas no leídas de un usuario
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario
 * @return int Número de alertas no leídas
 */
function getUnreadAlertCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM user_alerts WHERE user_id = ? AND read_at IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['count'];
}

/**
 * Obtiene las alertas recientes no leídas (para mostrar en badge/notificaciones)
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario
 * @param int $limit Número máximo de alertas
 * @return array Lista de alertas recientes
 */
function getRecentUnreadAlerts($conn, $user_id, $limit = 5) {
    return getUserAlerts($conn, $user_id, $limit, true);
}

/**
 * Marca una alerta como leída
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param int $alert_id ID de la alerta
 * @param string $user_id ID del usuario (para verificación de seguridad)
 * @return bool True si se marcó correctamente
 */
function markAlertAsRead($conn, $alert_id, $user_id) {
    $sql = "UPDATE user_alerts SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("is", $alert_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Marca todas las alertas de un usuario como leídas
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario
 * @return int Número de alertas marcadas
 */
function markAllAlertsAsRead($conn, $user_id) {
    $sql = "UPDATE user_alerts SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

// ============================================================================
// FUNCIONES HELPER PARA TIPOS ESPECÍFICOS DE ALERTAS
// ============================================================================

/**
 * Alerta cuando se envía una nueva propuesta de TFG
 * Notifica a Gestores (rol 2) y CTFG (rol 3)
 */
function registerProposalSubmittedAlert($conn, $student_name, $proposal_title, $proposal_id) {
    $subject = "Nueva Propuesta TFG Recibida";
    $message = "Se ha recibido una nueva propuesta de Trabajo Final de Graduación.\n\n";
    $message .= "Estudiante: $student_name\n";
    $message .= "Título: $proposal_title\n\n";
    $message .= "La propuesta está pendiente de revisión.";
    
    $count = 0;
    // Notificar a Gestores (rol 2)
    $count += registerAlertToRole($conn, 2, $subject, $message, 'Nueva Propuesta', 'Media', 'proposal', $proposal_id);
    // Notificar a CTFG (rol 3)
    $count += registerAlertToRole($conn, 3, $subject, $message, 'Nueva Propuesta', 'Media', 'proposal', $proposal_id);
    
    return $count;
}

/**
 * Alerta cuando una propuesta cumple con los requisitos de subdirección
 * Notifica al estudiante
 */
function registerProposalApprovedAlert($conn, $student_user_id, $proposal_title, $proposal_id) {
    $subject = "¡Tu Propuesta de TFG cumple con los requisitos!";
    $message = "Nos complace informarte que tu propuesta de TFG \"$proposal_title\" cumple con los requisitos de subdirección.\n";
    $message .= "Proximamente va a ser revisado por la CTFG.\n";
    $message .= "Recuerda estar atento a las notificaciones del sistema.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Informativa', 'Media', 'proposal', $proposal_id);
}

/**
 * Alerta cuando una propuesta es aprovada por la CTFG
 */
function registerProposalApprovedByCTFGAlert($conn, $student_user_id, $proposal_title, $proposal_id) {
    $subject = "¡Tu Propuesta de TFG ha sido Aprobada por la CTFG!";
    $message = "Tu propuesta de TFG \"$proposal_title\" ha sido aprobada por la Comisión TFG.\n";
    $message .= "Puedes comenzar a trabajar en tu proyecto y subir los documentos correspondientes.\n";
    $message .= "Recuerda revisar las fechas límite y requisitos para cada etapa del proceso.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Propuesta Aprobada', 'Alta', 'proposal', $proposal_id);
}


/**
 * Alerta cuando una propuesta es rechazada
 * Notifica al estudiante
 */
function registerProposalRejectedAlert($conn, $student_user_id, $proposal_title, $proposal_id, $reason = '') {
    $subject = "Tu Propuesta de TFG Requiere Correcciones";
    $message = "Tu propuesta de TFG \"$proposal_title\" ha sido revisada y requiere correcciones.\n\n";
    if (!empty($reason)) {
        $message .= "Observaciones: $reason\n\n";
    }
    $message .= "Por favor, revisa los comentarios y realiza las correcciones necesarias.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Propuesta Rechazada', 'Alta', 'proposal', $proposal_id);
}

/**
 * Alerta cuando se sube un documento final
 * Notifica a Gestores, CTFG y Asesor Externo si aplica
 */
function registerFinalDocumentAlert($conn, $student_name, $proposal_title, $document_id, $advisor_user_id = null) {
    $subject = "Nuevo Documento Final TFG Recibido";
    $message = "Se ha recibido un documento final de Trabajo Final de Graduación.\n\n";
    $message .= "Estudiante: $student_name\n";
    $message .= "Título: $proposal_title\n\n";
    $message .= "El documento está pendiente de revisión.";
    
    $count = 0;
    // Notificar a Gestores (rol 2)
    $count += registerAlertToRole($conn, 2, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    // Notificar a CTFG (rol 3)
    $count += registerAlertToRole($conn, 3, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    
    // Notificar al asesor externo si existe
    if ($advisor_user_id) {
        registerAlert($conn, $advisor_user_id, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
        $count++;
    }
    
    return $count;
}

/**
 * Alerta cuando el asesor externo aprueba un documento
 * Notifica al estudiante
 */
function registerAdvisorApprovedAlert($conn, $student_user_id, $proposal_title, $document_id) {
    $subject = "Tu Asesor ha Aprobado tu Documento";
    $message = "Tu asesor externo ha revisado y aprobado tu documento de TFG \"$proposal_title\".\n\n";
    $message .= "El documento pasará ahora a revisión por el CTFG.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Asesor Aprobado', 'Alta', 'document', $document_id);
}

/**
 * Alerta cuando el asesor externo solicita correcciones
 * Notifica al estudiante
 */
function registerAdvisorRejectedAlert($conn, $student_user_id, $proposal_title, $document_id, $comments = '') {
    $subject = "Tu Asesor Solicita Correcciones";
    $message = "Tu asesor externo ha revisado tu documento de TFG \"$proposal_title\" y solicita correcciones.\n\n";
    if (!empty($comments)) {
        $message .= "Comentarios: $comments\n\n";
    }
    $message .= "Por favor, realiza las correcciones y vuelve a subir el documento.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Asesor Rechazado', 'Alta', 'document', $document_id);
}

/**
 * Alerta cuando se solicita corrección al documento final
 * Notifica al estudiante
 */
function registerCorrectionRequestedAlert($conn, $student_user_id, $proposal_title, $document_id, $comments = '') {
    $subject = "Se Solicitan Correcciones a tu Documento Final";
    $message = "El CTFG ha revisado tu documento final de TFG \"$proposal_title\" y solicita correcciones.\n\n";
    if (!empty($comments)) {
        $message .= "Observaciones: $comments\n\n";
    }
    $message .= "Por favor, realiza las correcciones y vuelve a subir el documento.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Correccion Solicitada', 'Alta', 'document', $document_id);
}

/**
 * Alerta de prórroga concedida o vencimiento próximo
 * Notifica al estudiante
 */
function registerExtensionAlert($conn, $student_user_id, $proposal_title, $new_deadline, $proposal_id) {
    $subject = "Prórroga Concedida para tu TFG";
    $message = "Se ha concedido una prórroga para tu Trabajo Final de Graduación \"$proposal_title\".\n\n";
    $message .= "Nueva fecha límite: $new_deadline\n\n";
    $message .= "Recuerda entregar tu trabajo antes de la fecha indicada.";
    
    return registerAlert($conn, $student_user_id, $subject, $message, 'Prorroga', 'Media', 'proposal', $proposal_id);
}

/**
 * Alerta cuando la solicitud de asesor externo es aprobada
 * Notifica al asesor externo (que ahora tiene cuenta en el sistema)
 */
function registerExternalAdvisorApprovedAlert($conn, $advisor_user_id, $advisor_name, $expiration_date) {
    $subject = "¡Tu Solicitud de Asesor Externo ha sido Aprobada!";
    $message = "Estimado/a $advisor_name,\n\n";
    $message .= "Nos complace informarte que tu solicitud para ser Asesor Externo ha sido APROBADA.\n\n";
    $message .= "Ya puedes acceder al sistema con tus credenciales.\n";
    $message .= "Vigencia: Hasta $expiration_date o hasta el cierre del TFG asignado.\n\n";
    $message .= "Bienvenido/a al sistema.";
    
    return registerAlert($conn, $advisor_user_id, $subject, $message, 'Sistema', 'Alta', null, null);
}

/**
 * Alerta cuando el estudiante sube una corrección del documento final
 * Notifica a Gestores y CTFG
 */
function registerCorrectionSubmittedAlert($conn, $student_name, $proposal_title, $document_id) {
    $subject = "Corrección de Documento Final Recibida";
    $message = "Se ha recibido una corrección del documento final de TFG.\n\n";
    $message .= "Estudiante: $student_name\n";
    $message .= "Título: $proposal_title\n\n";
    $message .= "El documento corregido está pendiente de revisión.";
    
    $count = 0;
    // Notificar a Gestores (rol 2)
    $count += registerAlertToRole($conn, 2, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    // Notificar a CTFG (rol 3)
    $count += registerAlertToRole($conn, 3, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    
    return $count;
}

/**
 * Alerta cuando se sube un documento final por primera vez
 * Notifica a Gestores, CTFG
 */
function registerFinalDocumentSubmittedAlert($conn, $student_name, $proposal_title, $document_id) {
    $subject = "Nuevo Documento Final TFG Recibido";
    $message = "Se ha recibido un nuevo documento final de Trabajo Final de Graduación.\n\n";
    $message .= "Estudiante: $student_name\n";
    $message .= "Título: $proposal_title\n\n";
    $message .= "El documento está pendiente de revisión inicial.";
    
    $count = 0;
    // Notificar a Gestores (rol 2)
    $count += registerAlertToRole($conn, 2, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    // Notificar a CTFG (rol 3)
    $count += registerAlertToRole($conn, 3, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
    
    return $count;
}

/**
 * Notifica a un estudiante que ha sido agregado a un grupo TFG
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $member_id ID del estudiante agregado
 * @param string $leader_name Nombre del líder del proyecto
 * @param string $project_title Título del proyecto TFG
 * @param int $project_id ID del proyecto
 * @return bool True si se registró correctamente
 */
function registerGroupMemberAddedAlert($conn, $member_id, $leader_name, $project_title, $project_id) {
    $subject = "Has sido agregado a un grupo TFG";
    $message = "El estudiante {$leader_name} te ha agregado como miembro del proyecto TFG: \"{$project_title}\". ";
    $message .= "Ahora formas parte de este grupo y puedes acceder al historial de documentos del proyecto.";
    
    return registerAlert($conn, $member_id, $subject, $message, 'Informativa', 'Media', 'project', $project_id);
}

/**
 * Notifica a un estudiante que se le ha asignado un asesor externo
 * 
 * @param mysqli $conn Conexión a la base de datos
 * @param string $student_id ID del estudiante
 * @param string $advisor_name Nombre del asesor externo
 * @param string $advisor_email Email del asesor externo
 * @return bool True si se registró correctamente
 */
function registerAdvisorAssignedToStudentAlert($conn, $student_id, $advisor_name, $advisor_email) {
    $subject = "Se te ha asignado un Asesor Externo";
    $message = "Se te ha asignado un Asesor Externo para tu proyecto TFG.\n";
    $message .= "Nombre del Asesor: {$advisor_name}";
    $message .= "Email de contacto: {$advisor_email}\n";
    $message .= "Tu asesor externo podrá visualizar los documentos de tu TFG.";
    
    return registerAlert($conn, $student_id, $subject, $message, 'Informativa', 'Alta', null, null);


/**
 * HU-005
 * 
 * Registra una alerta de vencimiento de plazo para entrega de documento final.
 * @param mysqli $conn Conexión a la base de datos
 * @param string $user_id ID del usuario destinatario
 * @param string $project_title Título del proyecto
 * @param string $deadline Fecha límite (YYYY-MM-DD)
 * @param int $days_remaining Días restantes para el vencimiento
 * @param int $project_id ID del proyecto
 * @return bool True si se registró correctamente
 */
function registerDeadlineAlert($conn, $user_id, $project_title, $deadline, $days_remaining, $project_id) {
    $subject = "¡Atención! Plazo de entrega próximo a vencer";
    $message = "El plazo para entregar el documento final del proyecto \"{$project_title}\" vence el día {$deadline} ({$days_remaining} días restantes).\n";
    $message .= "Por favor, asegúrate de cumplir con la entrega antes de la fecha límite.";
    return registerAlert($conn, $user_id, $subject, $message, 'Vencimiento Plazo', 'Alta', 'project', $project_id);
}

}
