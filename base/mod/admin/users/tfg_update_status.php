<?php
// Incluir el sistema de sesión personalizado
include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';

// Iniciar el controlador de sesión
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$reviewer_id = $mySessionController->getVar("usuario");
$user_rol = $mySessionController->getVar("rol");

// Incluir la configuración de la base de datos
include __DIR__ . '/../../../inc/db/bdcommon.inc';
require_once __DIR__ . '/../../../config.inc';
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json');

/**
 * Sincroniza el hito principal del proyecto aprobado con Google Calendar.
 * No lanza excepciones al flujo principal.
 */
function syncProjectTimelineApprovalWithGoogleCalendar(mysqli $conn, int $proposalId, string $projectName, string $targetDate): void
{
    try {
        if (!class_exists('Service\\GoogleCalendarService')) {
            return;
        }

        $users = [];
        $sqlUsers = "
            SELECT DISTINCT users.user_id
            FROM (
                SELECT p.user_id
                FROM tfg_proposals p
                WHERE p.id = ?

                UNION

                SELECT pm.user_id
                FROM registered_projects rp
                INNER JOIN project_members pm ON pm.project_id = rp.id
                WHERE rp.tfg_proposal_id = ?
                  AND pm.status = 'Activo'
            ) AS users
            WHERE users.user_id IS NOT NULL
        ";

        $stmtUsers = $conn->prepare($sqlUsers);
        if (!$stmtUsers) {
            return;
        }

        $stmtUsers->bind_param('ii', $proposalId, $proposalId);
        $stmtUsers->execute();
        $rsUsers = $stmtUsers->get_result();
        while ($rsUsers && ($row = $rsUsers->fetch_assoc())) {
            $userId = (string) ($row['user_id'] ?? '');
            if ($userId !== '') {
                $users[] = $userId;
            }
        }
        $stmtUsers->close();

        if (empty($users)) {
            return;
        }

        $googleCalendarService = new Service\GoogleCalendarService($conn);
        $timelineData = [
            'milestone_name' => 'Fecha límite de entrega del TFG',
            'target_date' => $targetDate,
            'project_name' => $projectName,
            'description' => 'Hito generado al aprobar la propuesta del proyecto.',
            'status' => 'Vigente'
        ];

        foreach ($users as $userId) {
            if (!$googleCalendarService->isSyncEnabled($userId)) {
                continue;
            }

            $googleCalendarService->syncProjectTimeline($userId, $proposalId, $timelineData);
        }
    } catch (Throwable $e) {
        error_log('tfg_update_status.php Google Calendar timeline sync error: ' . $e->getMessage());
    }
}

// --- Validación de seguridad ---
// Solo Administradores (rol 1) y Gestores Académicos (rol 2) pueden revisar
if (!$reviewer_id || !in_array($user_rol, [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

try {
    $conn->begin_transaction();

    $proposal_id = (int)$_POST['id'];
    $review_status = trim((string)$_POST['status']);
    $comments = isset($_POST['comments']) ? (string)$_POST['comments'] : '';
    $timeline_target_date = null;

    // Validar valores contra el ENUM de la BD (deben coincidir exactamente)
    $allowed_statuses = ['Pendiente de Revision', 'Cumple requisitos', 'No cumple requisitos', 'Aprobado', 'Rechazado'];
    if (!in_array($review_status, $allowed_statuses, true)) {
        throw new Exception('Estado inválido.');
    }

    // Si se rechaza, exigir observaciones
    if ($review_status === 'No cumple requisitos' && trim($comments) === '') {
        throw new Exception('Debe ingresar observaciones para rechazar la propuesta.');
    }

    // 1. Get current proposal data
    $stmt = $conn->prepare("SELECT p.user_id, p.document, p.file_name, p.mime_type, p.file_size, p.title, u.email, u.nombre 
                       FROM tfg_proposals p 
                       JOIN sis_user u ON p.user_id = u.id 
                       WHERE p.id = ?");
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn->error);
    }
    $stmt->bind_param("i", $proposal_id);
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $proposal = $result->fetch_assoc();

    if (!$proposal) {
        throw new Exception("No se encontró la propuesta especificada.");
    }

    // 2. Update main table
    // La BD guarda el ENUM original; para mostrar al usuario usamos un texto más claro.
    $display_status = $review_status;
    if ($review_status === 'Cumple requisitos') {
        $display_status = 'Aprobado';
    } elseif ($review_status === 'No cumple requisitos') {
        $display_status = 'Rechazado';
    }

    $sql_update = "UPDATE tfg_proposals 
                   SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_comments = ? 
                   WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        throw new Exception('Error preparando UPDATE: ' . $conn->error);
    }
    $stmt_update->bind_param("sssi", $review_status, $reviewer_id, $comments, $proposal_id);
    if (!$stmt_update->execute()) {
        throw new Exception('Error ejecutando UPDATE: ' . $stmt_update->error);
    }

    // Actualizar historial existente (sin insertar nuevas filas)
    // Se actualiza el registro más reciente del historial para este proposal_id.
    $history_sql = "UPDATE tfg_proposal_history
                    SET status = ?, reviewed_by = ?, comments = ?
                    WHERE proposal_id = ?
                    ORDER BY created_at DESC, id DESC
                    LIMIT 1";
    $stmt_history = $conn->prepare($history_sql);
    if (!$stmt_history) {
        throw new Exception('Error preparando UPDATE historial: ' . $conn->error);
    }
    $stmt_history->bind_param("sssi", $review_status, $reviewer_id, $comments, $proposal_id);
    if (!$stmt_history->execute()) {
        throw new Exception('Error ejecutando UPDATE historial: ' . $stmt_history->error);
    }
    if ($stmt_history->affected_rows < 1) {
        // No existe historial previo; por requerimiento NO insertamos aquí.
        error_log('Aviso: No se encontró registro en tfg_proposal_history para proposal_id=' . $proposal_id);
    }
    $stmt_history->close();

    // 3. Si la propuesta fue APROBADA, crear automáticamente el timeline del proyecto
    if ($review_status === 'Cumple requisitos') {
        // Calcular deadline: 1 año (12 meses) desde la fecha de aprobación
        // Evitar fallo por registro duplicado (UNIQUE proposal_id)
        $sql_timeline = "INSERT IGNORE INTO tfg_project_timeline 
                        (proposal_id, approval_date, original_deadline, status) 
                        VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 'Vigente')";
        $stmt_timeline = $conn->prepare($sql_timeline);
        if (!$stmt_timeline) {
            throw new Exception('Error preparando timeline: ' . $conn->error);
        }
        $stmt_timeline->bind_param("i", $proposal_id);
        if (!$stmt_timeline->execute()) {
            throw new Exception('Error creando timeline: ' . $stmt_timeline->error);
        }
        $stmt_timeline->close();

        $timeline_target_date = date('Y-m-d', strtotime('+12 months'));
        
        error_log("Timeline creado automáticamente para propuesta ID: " . $proposal_id);
    }

    // 4. Send email notification
    require_once __DIR__ . '/../../../inc/email_template_helper.php';
    // $to = $proposal['email']; // Production
    $to = "rodri100ro@gmail.com";
    $subject = "Actualización de estado - Propuesta TFG";
    
    $email_content = "<p>Su propuesta de TFG <strong>\"" . htmlspecialchars($proposal['title']) . "\"</strong> ha sido revisada.</p>";
    $email_content .= "<p><strong>Nuevo estado:</strong> " . htmlspecialchars($display_status) . "</p>";
    if (!empty($comments)) {
        $email_content .= "<p><strong>Comentarios:</strong> " . nl2br(htmlspecialchars($comments)) . "</p>";
    }
    
    // Agregar información del timeline si fue aprobada
    if ($review_status === 'Cumple requisitos') {
        $email_content .= "<p style='color: #198754;'><strong>¡Su propuesta ha sido aprobada!</strong></p>";
        $email_content .= "<p>A partir de hoy, tiene <strong>12 meses (1 año)</strong> para completar su TFG.</p>";
        $email_content .= "<p><strong>Fecha límite:</strong> " . date('d/m/Y', strtotime('+12 months')) . "</p>";
        $email_content .= "<p>Puede subir su documento final desde el panel de estudiante.</p>";
    }
    
    $email_content .= "<p>Puede revisar su propuesta en el panel de estudiante.</p>";
    $email_content .= "<p style='margin-top: 20px;'>Saludos,<br>Escuela de Informática - UNA</p>";
    
    $message = wrapEmailBody($email_content, "Estimado/a <strong>" . htmlspecialchars($proposal['nombre']) . "</strong>,");

    $headers = "From: " . SYSTEM_EMAIL_FROM_NAME . " <" . SYSTEM_EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . SYSTEM_EMAIL_REPLY_TO . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    
    if (mail($to, $subject, $message, $headers)) {
        error_log("Correo enviado exitosamente a: " . $to);
        $email_status = "y notificación enviada";
    } else {
        error_log("Error enviando correo a: " . $to);
        $email_status = "pero falló la notificación";
    }

    $conn->commit();

    if ($review_status === 'Cumple requisitos' && $timeline_target_date !== null) {
        syncProjectTimelineApprovalWithGoogleCalendar(
            $conn,
            $proposal_id,
            (string) ($proposal['title'] ?? ''),
            $timeline_target_date
        );
    }
    
    // =============================== HU-037: REGISTRAR ALERTAS INTERNAS ===============================
    try {
        require_once __DIR__ . '/../../../inc/alert_functions.php';
        $student_user_id = $proposal['user_id'];
        
        if ($review_status === 'Cumple requisitos') {
            registerProposalApprovedAlert($conn, $student_user_id, $proposal['title'], $proposal_id);
        } elseif ($review_status === 'No cumple requisitos') {
            registerProposalRejectedAlert($conn, $student_user_id, $proposal['title'], $proposal_id, $comments);
        }
    } catch (Exception $alertEx) {
        error_log("HU-037: Error registrando alerta (no crítico): " . $alertEx->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente ' . $email_status]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en tfg_update_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_update)) $stmt_update->close();
    $conn->close();
}