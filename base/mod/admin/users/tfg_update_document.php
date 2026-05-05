<?php
// =============================== AUTENTICACIÓN Y SESIÓN ===============================
include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
include __DIR__ . '/../../../lang/lang.es';
require_once __DIR__ . '/../../../config.inc';
require_once __DIR__ . '/../../../inc/email_template_helper.php';
date_default_timezone_set('America/Costa_Rica');

try {
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $user_id = $mySessionController->getVar("usuario");
    $user_rol = $mySessionController->getVar("rol");

    if (!$user_id || ($user_rol != 4 && $user_rol != 3 && $user_rol != 2)) {
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

    error_log("tfg_update_document.php - Starting - user_id: $user_id, user_info: " . ($user_info ? 'found' : 'NULL'));

    if (!$user_info) {
        http_response_code(404);
        throw new Exception('Usuario no encontrado');
    }

    // =============================== NOTIFICACIÓN POR CORREO ===============================
    // Si no se envía el tipo, se asume 'Documento' que redirecciona al mensaje por defecto
    $tipo = $_POST['tipo'] ?? 'Documento';
    
    error_log("tfg_update_document.php - tipo recibido: $tipo");

    $base_url = "https://localhost/base/";
    $historial_url = $base_url . "historial_documentos.php";
    $historial_url_secretaria = $base_url . "panel_subdireccion.php";
    
    $secretaria_email = defined('SYSTEM_EMAIL_REPLY_TO') ? SYSTEM_EMAIL_REPLY_TO : 'rodri100ro@gmail.com';
    
    // Configuración del correo
    $headers = "From: " . (defined('SYSTEM_EMAIL_FROM_NAME') ? SYSTEM_EMAIL_FROM_NAME : 'SGPFL') . " <" . (defined('SYSTEM_EMAIL_FROM') ? SYSTEM_EMAIL_FROM : 'noreply@una.cr') . ">\r\n";
    $headers .= "Reply-To: " . (defined('SYSTEM_EMAIL_REPLY_TO') ? SYSTEM_EMAIL_REPLY_TO : 'rodri100ro@gmail.com') . "\r\n";
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // =============================== LÓGICA DE REVISIÓN CTFG (ROL 3) ===============================
    if ($tipo === 'Documento Final TFG' && $user_rol == 3) {
        $review_status = $_POST['status'] ?? 'No especificado';
        $review_comments = !empty($_POST['comments']) ? $_POST['comments'] : 'No se proporcionaron comentarios.';

        $subject = "Notificación de Revisión de Documento Final de TFG";

        $email_content = "
            <p>Le informamos que la <strong>Comisión de Trabajos Finales de Graduación (CTFG)</strong> ha revisado su documento final.</p>

            <p><strong>Detalles de la revisión:</strong></p>
            <ul>
                <li><strong>Fecha de revisión:</strong> " . date("d/m/Y H:i") . "</li>
                <li><strong>Estado:</strong> " . htmlspecialchars($review_status) . "</li>
            </ul>

            <p><strong>Comentarios de la CTFG:</strong></p>
            <div style='background-color: #f0f0f0; border-left: 4px solid #0056b3; padding: 10px 15px; margin-top: 10px;'>
                <p>" . nl2br(htmlspecialchars($review_comments)) . "</p>
            </div>

            <p>Puede consultar el historial de su TFG ingresando al sistema:</p>
            <p><a href='" . htmlspecialchars($historial_url) . "'>" . htmlspecialchars($historial_url) . "</a></p>
        ";

        $message_estudiante = wrapEmailBody($email_content, "Estimado/a estudiante,");
    }

    // Estructura del mensaje para el documento final
    else if ($tipo === 'Documento Final TFG') {
        $subject = "Confirmación de carga de Trabajo Final de Graduación en el SGPFL";

        $email_content = "
            <p>Le informamos que su archivo de <strong>Trabajo Final de Graduación (TFG)</strong> ha sido <strong>subido exitosamente</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>, administrado por la Escuela de Informática de la Universidad Nacional.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> " . date("d/m/Y H:i") . "</li>
            <li><strong>Autor:</strong> " . htmlspecialchars($user_info["nombre"]) . "</li>
            </ul>

            <p>Su documento final será revisado por la Comisión de Trabajos Finales de Graduación (CTFG). Recibirá notificaciones sobre el avance del proceso a través de este sistema.</p>

            <p>Puede consultar el historial de documentos y el estado de su TFG ingresando al siguiente enlace:</p>
            <p><a href='" . htmlspecialchars($historial_url) . "'>" . htmlspecialchars($historial_url) . "</a></p>

            <p>Agradecemos su dedicación y el uso del sistema. Para cualquier duda o inconveniente, puede comunicarse con la coordinación del TFG.</p>
        ";

        $message_estudiante = wrapEmailBody($email_content, "Estimado/a estudiante <strong>" . htmlspecialchars($user_info["nombre"]) . "</strong>,");

        $subject = "Notificación de carga de Trabajo Final de Graduación en el SGPFL";

        $email_content_secretaria = "
            <p>Le informamos que el/la estudiante <strong>" . htmlspecialchars($user_info["nombre"]) . "</strong> ha subido su <strong>Trabajo Final de Graduación (TFG)</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> " . date("d/m/Y H:i") . "</li>
            <li><strong>Estudiante:</strong> " . htmlspecialchars($user_info["nombre"]) . "</li>
            </ul>

            <p>Puede acceder al sistema para revisar el documento y continuar con el proceso administrativo en el siguiente enlace:</p>
            <p><a href='" . htmlspecialchars($historial_url_secretaria) . "'>" . htmlspecialchars($historial_url_secretaria) . "</a></p>

            <p>Para cualquier consulta, puede comunicarse con el administrador del sistema.</p>
        ";

        $message_secretaria = wrapEmailBody($email_content_secretaria, "Estimada/o Secretaría/o de Subdirección,");
    } 
    // Estructura del mensaje para el propuesta de TFG
    else if($tipo === 'Propuesta TFG') {
        $subject = "Confirmación de carga de propuesta de TFG en el SGPFL";

        $email_content_estudiante = "
            <p>Le informamos que su archivo de <strong>Propuesta de Trabajo Final de Graduación (TFG)</strong> ha sido ingresado exitosamente en el 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>, administrado por la Escuela de Informática de la Universidad Nacional.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> " . date("d/m/Y H:i") . "</li>
            <li><strong>Autor:</strong> " . htmlspecialchars($user_info["nombre"]) . "</li>
            </ul>

            <p>Su propuesta será revisada por la Comisión de Trabajos Finales de Graduación (CTFG). Recibirá notificaciones sobre el avance del proceso a través de este sistema.</p>

            <p>Puede consultar el historial de versiones y el estado de su propuesta ingresando al siguiente enlace:</p>
            <p><a href='" . htmlspecialchars($historial_url) . "'>" . htmlspecialchars($historial_url) . "</a></p>

            <p>Agradecemos su atención y el uso del sistema. Para cualquier duda o inconveniente, puede comunicarse con la coordinación del TFG.</p>
        ";

        $message_estudiante = wrapEmailBody($email_content_estudiante, "Estimado/a estudiante <strong>" . htmlspecialchars($user_info["nombre"]) . "</strong>,");

        $subject = "Notificación de carga de propuesta de TFG en el SGPFL";

        $email_content_secretaria = "
            <p>Le informamos que el/la estudiante <strong>" . htmlspecialchars($user_info["nombre"]) . "</strong> ha subido una <strong>propuesta de Trabajo Final de Graduación (TFG)</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> " . date("d/m/Y H:i") . "</li>
            <li><strong>Estudiante:</strong> " . htmlspecialchars($user_info["nombre"]) . "</li>
            </ul>

            <p>Puede acceder al sistema para revisar la propuesta y continuar con el proceso administrativo en el siguiente enlace:</p>
            <p><a href='" . htmlspecialchars($historial_url_secretaria) . "'>" . htmlspecialchars($historial_url_secretaria) . "</a></p>

            <p>Para cualquier consulta, puede comunicarse con el administrador del sistema.</p>
        ";

        $message_secretaria = wrapEmailBody($email_content_secretaria, "Estimada/o Secretaría/o de Subdirección,");
    }
    // Estructura del mensaje para otros tipos de documentos
    else{
        $subject = "Notificación de carga de documento en el SGPFL";

        $email_content = "
            <p>Se informa que la persona <strong>" . htmlspecialchars($user_info["nombre"]) . "</strong> ha realizado la carga de un documento en el 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha y hora de carga:</strong> " . date("d/m/Y H:i") . "</li>
            <li><strong>Usuario:</strong> " . htmlspecialchars($user_info["nombre"]) . "</li>
            </ul>

            <p>Puede acceder al sistema para consultar el documento y continuar con el proceso correspondiente en el siguiente enlace:</p>
            <p><a href='" . htmlspecialchars($base_url) . "'>" . htmlspecialchars($base_url) . "</a></p>

            <p>Para cualquier consulta, puede comunicarse con la coordinación de TFG.</p>
        ";

        $message_general = wrapEmailBody($email_content, "Estimado/a Usuario/a,");

        $message_estudiante = $message_general;
        $message_secretaria = $message_general;
    }

    // Enviar correos
    $test_email = 'rodri100ro@gmail.com';
    
    error_log("tfg_update_document.php - Enviando correo - tipo: $tipo, test_email: $test_email");
    error_log("tfg_update_document.php - Subject: $subject");
    
    $mail1 = @mail($test_email, $subject, $message_estudiante, $headers);
    error_log("tfg_update_document.php - mail1 resultado: " . ($mail1 ? 'OK' : 'FALLO'));
    
    // Para la secretaría
    $mail2 = @mail($test_email, $subject, $message_secretaria, $headers);
    error_log("tfg_update_document.php - mail2 resultado: " . ($mail2 ? 'OK' : 'FALLO'));

    if ($mail1 && $mail2) {
        error_log("Notificación enviada a estudiante y secretaría.");
    }
} catch (Exception $e) {
    error_log("Error enviando notificación: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) $conn->close();
}
// =============================== FIN DEL SCRIPT ===============================