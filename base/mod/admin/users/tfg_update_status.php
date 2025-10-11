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

    // 1. Get current proposal data
    $stmt = $conn->prepare("SELECT p.document, p.file_name, p.mime_type, p.file_size, p.title, u.email, u.nombre 
                       FROM tfg_proposals p 
                       JOIN sis_user u ON p.user_id = u.id 
                       WHERE p.id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $proposal = $result->fetch_assoc();

    if (!$proposal) {
        throw new Exception("No se encontró la propuesta especificada.");
    }

    // 2. Insert into history
    $sql = "INSERT INTO tfg_proposal_history 
            (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Usar el ID del revisor obtenido de la sesión
    $stmt->bind_param("ibssisss", 
        $_POST['id'], 
        $proposal['document'], 
        $proposal['file_name'],
        $proposal['mime_type'],
        $proposal['file_size'],
        $_POST['status'],
        $reviewer_id, // <-- CAMBIO CLAVE: ID real del revisor
        $_POST['comments']
    );
    $stmt->send_long_data(1, $proposal['document']);
    $stmt->execute();

    // 3. Update main table
    $stmt = $conn->prepare("UPDATE tfg_proposals SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['id']);
    $stmt->execute();

    // 4. Send email notification
    $to = $proposal['email'];
    $subject = "Actualización de estado - Propuesta TFG";
    $message = "Estimado/a " . $proposal['nombre'] . ",\n\n";
    $message .= "Su propuesta de TFG \"" . $proposal['title'] . "\" ha sido revisada.\n\n";
    $message .= "Nuevo estado: " . $_POST['status'] . "\n";
    if (!empty($_POST['comments'])) {
        $message .= "Comentarios: " . $_POST['comments'] . "\n";
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
    echo json_encode(['success' => true, 'message' => 'Estado actualizado ' . $email_status]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}