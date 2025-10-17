<?php
// =============================== AUTENTICACIÓN Y SESIÓN ===============================
include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';

try {
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $user_id = $mySessionController->getVar("usuario");
    $user_rol = $mySessionController->getVar("rol");

    if (!$user_id || $user_rol != 4) {
        http_response_code(403);
        throw new Exception('Acceso no autorizado');
    }

    // =============================== CONEXIÓN A BASE DE DATOS ===============================
    include __DIR__ . '/../../../inc/db/bdcommon.inc';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        error_log('No se pudo crear la conexión a la base de datos: ' . $conn->connect_error);
        exit;
    }

    // =============================== OBTENER DATOS DEL USUARIO ===============================
    $stmt_user = $conn->prepare("SELECT u.email, u.nombre FROM sis_user u WHERE u.id = ?");
    $stmt_user->bind_param("s", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_info = $result_user->fetch_assoc();
    $stmt_user->close();

    if (!$user_info) {
        http_response_code(404);
        throw new Exception('Usuario no encontrado');
    }

    // =============================== NOTIFICACIÓN POR CORREO ===============================
    $base_url = "https://localhost/base/";
    $historial_url = $base_url . "historial_documentos.php";
    $historial_url_secretaria = $base_url . "panel_subdireccion.php";
    $secretaria_email = "rodri100ro@gmail.com";
    $subject = "Nueva versión de documento de TFG subida";

    // Mensaje para el estudiante
    $message_estudiante = "Estimado/a " . $user_info['nombre'] . ",\n\n";
    $message_estudiante .= "Se ha subido una nueva versión de su documento de TFG.\n";
    $message_estudiante .= "Fecha: " . date('d/m/Y H:i') . "\n";
    $message_estudiante .= "Autor: " . $user_info['nombre'] . "\n";
    $message_estudiante .= "Puede revisar el historial de versiones en el sistema:\n";
    $message_estudiante .= $historial_url . "\n\n";
    $message_estudiante .= "Saludos,\nEscuela de Informática - UNA";

    // Mensaje para la secretaría
    $message_secretaria = "Estimada/o Secretaria/o,\n\n";
    $message_secretaria .= "Se ha registrado la carga de una nueva versión de un documento de TFG en el sistema.\n";
    $message_secretaria .= "Fecha: " . date('d/m/Y H:i') . "\n";
    $message_secretaria .= "Autor: " . $user_info['nombre'] . "\n";
    $message_secretaria .= "Puede revisar el historial de versiones en la plataforma administrativa:\n";
    $message_secretaria .= $historial_url_secretaria . "\n\n";
    $message_secretaria .= "Saludos,\nSistema SGPFL - UNA";

    $headers = "From: rodri100ro@gmail.com\r\n";
    $headers .= "Reply-To: rodri100ro   @gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $mail1 = mail($secretaria_email, $subject, $message_estudiante, $headers);
    $mail2 = mail($secretaria_email, $subject, $message_secretaria, $headers);

    error_log("Notificación enviada a estudiante y secretaría.");
} catch (Exception $e) {
    error_log("Error enviando notificación: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) $conn->close();
}
// =============================== FIN DEL SCRIPT ===============================