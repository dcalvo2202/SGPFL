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

header('Content-Type: application/json');

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

    $proposal_id = $_POST['id'];
    $review_status = $_POST['status'];
    $comments = isset($_POST['comments']) ? $_POST['comments'] : '';

    // 1. Get current proposal data
    $stmt = $conn->prepare("SELECT p.document, p.file_name, p.mime_type, p.file_size, p.title, u.email, u.nombre 
                       FROM tfg_proposals p 
                       JOIN sis_user u ON p.user_id = u.id 
                       WHERE p.id = ?");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $proposal = $result->fetch_assoc();

    if (!$proposal) {
        throw new Exception("No se encontró la propuesta especificada.");
    }

    // 2. Update main table
    $main_table_status = '';
    if ($review_status === 'Cumple Requisitos') {
        $main_table_status = 'Aprobado';  // Cambio: "Cumple Requisitos" → "Aprobado"
    } else if ($review_status === 'No Cumple Requisitos') {
        $main_table_status = 'Rechazado';  // Cambio: "No Cumple Requisitos" → "Rechazado"
    } else {
        // Fallback for any other status that might be used
        $main_table_status = $review_status;
    }

    $sql_update = "UPDATE tfg_proposals 
                   SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_comments = ? 
                   WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssi", $review_status, $reviewer_id, $comments, $proposal_id);
    $stmt_update->execute();

    // 3. Si la propuesta fue APROBADA, crear automáticamente el timeline del proyecto
    if ($main_table_status === 'Aprobado') {
        // Calcular deadline: 1 año (12 meses) desde la fecha de aprobación
        $sql_timeline = "INSERT INTO tfg_project_timeline 
                        (proposal_id, approval_date, original_deadline, status) 
                        VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 'Vigente')";
        $stmt_timeline = $conn->prepare($sql_timeline);
        $stmt_timeline->bind_param("i", $proposal_id);
        $stmt_timeline->execute();
        $stmt_timeline->close();
        
        error_log("Timeline creado automáticamente para propuesta ID: " . $proposal_id);
    }

    // 4. Send email notification
    $to = "calvoss2002@gmail.com";//$proposal['email'];
    $subject = "Actualización de estado - Propuesta TFG";
    $message = "Estimado/a " . $proposal['nombre'] . ",\n\n";
    $message .= "Su propuesta de TFG \"" . $proposal['title'] . "\" ha sido revisada.\n\n";
    $message .= "Nuevo estado: " . $main_table_status . "\n";
    if (!empty($comments)) {
        $message .= "Comentarios: " . $comments . "\n";
    }
    
    // Agregar información del timeline si fue aprobada
    if ($main_table_status === 'Aprobado') {
        $message .= "\n¡Su propuesta ha sido aprobada!\n";
        $message .= "A partir de hoy, tiene 12 meses (1 año) para completar su TFG.\n";
        $message .= "Fecha límite: " . date('d/m/Y', strtotime('+12 months')) . "\n";
        $message .= "\nPuede subir su documento final desde el panel de estudiante.\n";
    }
    
    $message .= "\nPuede revisar su propuesta en el panel de estudiante.\n\n";
    $message .= "Saludos,\nEscuela de Informática - UNA";

    $headers = "From: calvoss2002@gmail.com\r\n";
    $headers .= "Reply-To: calvoss2002@gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if (mail($to, $subject, $message, $headers)) {
        error_log("Correo enviado exitosamente a: " . $to);
        $email_status = "y notificación enviada";
    } else {
        error_log("Error enviando correo a: " . $to);
        $email_status = "pero falló la notificación";
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente ' . $email_status]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en tfg_update_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_history)) $stmt_history->close();
    if (isset($stmt_update)) $stmt_update->close();
    $conn->close();
}