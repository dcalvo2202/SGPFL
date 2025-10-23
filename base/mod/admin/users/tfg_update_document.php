<?php
// =============================== AUTENTICACIÓN Y SESIÓN ===============================
include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
include('lang/lang.es');
date_default_timezone_set('America/Costa_Rica');

try {
    $mySessionController = mySession::getIstance($_MYSESSION_CONF);
    $user_id = $mySessionController->getVar("usuario");
    $user_rol = $mySessionController->getVar("rol");

    if (!$user_id || !in_array($user_rol, [3, 4])) {
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
    // Si no se envía el tipo, se asume 'Documento' que redirecciona al mensaje por defecto
    $tipo = $_POST['tipo'] ?? 'Documento';

    $base_url = "https://localhost/base/";
    $historial_url = $base_url . "historial_documentos.php";
    $historial_url_secretaria = $base_url . "panel_subdireccion.php";
    
    //$estudiante_email = $user_info['email'];
    $secretaria_email = "rodri100ro@gmail.com";
    
    // Configuración del correo
    $headers = "From: rodri100ro@gmail.com\r\n";
    $headers .= "Reply-To: rodri100ro@gmail.com\r\n";
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // =============================== LÓGICA DE REVISIÓN CTFG (ROL 3) ===============================
    if ($tipo === 'Documento Final TFG' && $user_rol == 3) {
        $review_status = $_POST['status'] ?? 'No especificado';
        $review_comments = !empty($_POST['comments']) ? $_POST['comments'] : 'No se proporcionaron comentarios.';

        $subject = "Notificación de Revisión de Documento Final de TFG";

        $message_estudiante = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa; }
            .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 13px; color: #555; }
            .footer img { width: 120px; vertical-align: middle; margin-right: 10px; }
            .footer td { vertical-align: top; }
            .divider { border-left: 2px solid #999; width: 1px; }
            a { color: #0056b3; text-decoration: none; }
            a:hover { text-decoration: underline; }
            .comments-box { background-color: #f0f0f0; border-left: 4px solid #0056b3; padding: 10px 15px; margin-top: 10px; }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimado/a estudiante,</p>

            <p>Le informamos que la <strong>Comisión de Trabajos Finales de Graduación (CTFG)</strong> ha revisado su documento final.</p>

            <p><strong>Detalles de la revisión:</strong></p>
            <ul>
                <li><strong>Fecha de revisión:</strong> ' . date("d/m/Y H:i") . '</li>
                <li><strong>Resultado:</strong> <strong>' . htmlspecialchars($review_status) . '</strong></li>
            </ul>

            <p><strong>Comentarios de la comisión:</strong></p>
            <div class="comments-box">
                <p>' . nl2br(htmlspecialchars($review_comments)) . '</p>
            </div>

            <p>Puede consultar el historial de su TFG ingresando al sistema:</p>
            <p><a href="' . htmlspecialchars($historial_url) . '">' . htmlspecialchars($historial_url) . '</a></p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';
    }

    // Estructura del mensaje para el documento final
    else if ($tipo === 'Documento Final TFG') {
        $subject = "Confirmación de carga de Trabajo Final de Graduación en el SGPFL";

        $message_estudiante = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            }
            .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            }
            .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 13px;
            color: #555;
            }
            .footer img {
            width: 120px;
            vertical-align: middle;
            margin-right: 10px;
            }
            .footer td {
            vertical-align: top;
            }
            .divider {
            border-left: 2px solid #999;
            width: 1px;
            }
            a {
            color: #0056b3;
            text-decoration: none;
            }
            a:hover {
            text-decoration: underline;
            }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimado/a estudiante <strong>' . htmlspecialchars($user_info["nombre"]) . '</strong>,</p>

            <p>Le informamos que su archivo de <strong>Trabajo Final de Graduación (TFG)</strong> ha sido <strong>subido exitosamente</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>, administrado por la Escuela de Informática de la Universidad Nacional.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> ' . date("d/m/Y H:i") . '</li>
            <li><strong>Autor:</strong> ' . htmlspecialchars($user_info["nombre"]) . '</li>
            </ul>

            <p>Su documento final será revisado por la Comisión de Trabajos Finales de Graduación (CTFG). Recibirá notificaciones sobre el avance del proceso a través de este sistema.</p>

            <p>Puede consultar el historial de documentos y el estado de su TFG ingresando al siguiente enlace:</p>
            <p><a href="' . htmlspecialchars($historial_url) . '">' . htmlspecialchars($historial_url) . '</a></p>

            <p>Agradecemos su dedicación y el uso del sistema. Para cualquier duda o inconveniente, puede comunicarse con la coordinación del TFG.</p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';

        $subject = "Notificación de carga de Trabajo Final de Graduación en el SGPFL";

        $message_secretaria = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            }
            .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            }
            .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 13px;
            color: #555;
            }
            .footer img {
            width: 120px;
            vertical-align: middle;
            margin-right: 10px;
            }
            .footer td {
            vertical-align: top;
            }
            .divider {
            border-left: 2px solid #999;
            width: 1px;
            }
            a {
            color: #0056b3;
            text-decoration: none;
            }
            a:hover {
            text-decoration: underline;
            }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimada/o Secretaría/o de Subdirección,</p>

            <p>Le informamos que el/la estudiante <strong>' . htmlspecialchars($user_info["nombre"]) . '</strong> ha subido su <strong>Trabajo Final de Graduación (TFG)</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> ' . date("d/m/Y H:i") . '</li>
            <li><strong>Estudiante:</strong> ' . htmlspecialchars($user_info["nombre"]) . '</li>
            </ul>

            <p>Puede acceder al sistema para revisar el documento y continuar con el proceso administrativo en el siguiente enlace:</p>
            <p><a href="' . htmlspecialchars($historial_url_secretaria) . '">' . htmlspecialchars($historial_url_secretaria) . '</a></p>

            <p>Para cualquier consulta, puede comunicarse con el administrador del sistema.</p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';
    } 
    // Estructura del mensaje para el propuesta de TFG
    else if($tipo === 'Propuesta TFG') {
        // Mensaje para el estudiante
        $subject = "Confirmación de carga de propuesta de TFG en el SGPFL";

        $message_estudiante = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            }
            .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            }
            .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 13px;
            color: #555;
            }
            .footer img {
            width: 120px;
            vertical-align: middle;
            margin-right: 10px;
            }
            .footer td {
            vertical-align: top;
            }
            .divider {
            border-left: 2px solid #999;
            width: 1px;
            }
            a {
            color: #0056b3;
            text-decoration: none;
            }
            a:hover {
            text-decoration: underline;
            }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimado/a estudiante <strong>' . htmlspecialchars($user_info["nombre"]) . '</strong>,</p>

            <p>Le informamos que su archivo de <strong>Propuesta de Trabajo Final de Graduación (TFG)</strong> ha sido ingresado exitosamente en el 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>, administrado por la Escuela de Informática de la Universidad Nacional.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> ' . date("d/m/Y H:i") . '</li>
            <li><strong>Autor:</strong> ' . htmlspecialchars($user_info["nombre"]) . '</li>
            </ul>

            <p>Su propuesta será revisada por la Comisión de Trabajos Finales de Graduación (CTFG). Recibirá notificaciones sobre el avance del proceso a través de este sistema.</p>

            <p>Puede consultar el historial de versiones y el estado de su propuesta ingresando al siguiente enlace:</p>
            <p><a href="' . htmlspecialchars($historial_url) . '">' . htmlspecialchars($historial_url) . '</a></p>

            <p>Agradecemos su atención y el uso del sistema. Para cualquier duda o inconveniente, puede comunicarse con la coordinación del TFG.</p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';

        $subject = "Notificación de carga de propuesta de TFG en el SGPFL";

        $message_secretaria = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            }
            .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            }
            .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 13px;
            color: #555;
            }
            .footer img {
            width: 120px;
            vertical-align: middle;
            margin-right: 10px;
            }
            .footer td {
            vertical-align: top;
            }
            .divider {
            border-left: 2px solid #999;
            width: 1px;
            }
            a {
            color: #0056b3;
            text-decoration: none;
            }
            a:hover {
            text-decoration: underline;
            }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimada/o Secretaría/o de Subdirección,</p>

            <p>Le informamos que el/la estudiante <strong>' . htmlspecialchars($user_info["nombre"]) . '</strong> ha subido una <strong>propuesta de Trabajo Final de Graduación (TFG)</strong> al 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha de carga:</strong> ' . date("d/m/Y H:i") . '</li>
            <li><strong>Estudiante:</strong> ' . htmlspecialchars($user_info["nombre"]) . '</li>
            </ul>

            <p>Puede acceder al sistema para revisar la propuesta y continuar con el proceso administrativo en el siguiente enlace:</p>
            <p><a href="' . htmlspecialchars($historial_url_secretaria) . '">' . htmlspecialchars($historial_url_secretaria) . '</a></p>

            <p>Para cualquier consulta, puede comunicarse con el administrador del sistema.</p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';
    }
    // Estructura del mensaje para otros tipos de documentos
    else{
        $subject = "Notificación de carga de documento en el SGPFL";

        $message_general = '
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            }
            .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            }
            .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 13px;
            color: #555;
            }
            .footer img {
            width: 120px;
            vertical-align: middle;
            margin-right: 10px;
            }
            .footer td {
            vertical-align: top;
            }
            .divider {
            border-left: 2px solid #999;
            width: 1px;
            }
            a {
            color: #0056b3;
            text-decoration: none;
            }
            a:hover {
            text-decoration: underline;
            }
        </style>
        </head>
        <body>
        <div class="container">
            <p>Estimado/a Usuario/a,</p>

            <p>Se informa que la persona <strong>' . htmlspecialchars($user_info["nombre"]) . '</strong> ha realizado la carga de un documento en el 
            <strong>Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)</strong>.</p>

            <p><strong>Detalles del registro:</strong></p>
            <ul>
            <li><strong>Fecha y hora de carga:</strong> ' . date("d/m/Y H:i") . '</li>
            <li><strong>Usuario:</strong> ' . htmlspecialchars($user_info["nombre"]) . '</li>
            </ul>

            <p>Puede acceder al sistema para consultar el documento y continuar con el proceso correspondiente en el siguiente enlace:</p>
            <p><a href="' . htmlspecialchars($base_url) . '">' . htmlspecialchars($base_url) . '</a></p>

            <p>Para cualquier consulta, puede comunicarse con la coordinación de TFG.</p>

            <div class="footer">
            <table>
                <tr>
                <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
                <td class="divider"></td>
                <td>
                    <strong>Escuela de Informática</strong><br>
                    Tel: <strong>(506) 2562-6363</strong> &nbsp;·&nbsp; Fax: <strong>(506) 2562-6384</strong><br>
                    <a href="mailto:escinf@una.cr">escinf@una.cr</a><br>
                    Universidad Nacional · Campus Presbítero Benjamín Núñez<br>
                    Heredia, Costa Rica
                </td>
                </tr>
            </table>
            <p style="margin-top:10px; font-size:12px; color:#777;">' . date("d/m/Y") . '</p>
            </div>
        </div>
        </body>
        </html>
        ';

        $message_estudiante = $message_general;
        $message_secretaria = $message_general;
    }

    // Enviar correos

    // Para el estudiante
    $mail1 = mail($secretaria_email, $subject, $message_estudiante, $headers);
    
    // Para la secretaría
    $mail2 = mail($secretaria_email, $subject, $message_secretaria, $headers);

    error_log("Notificación enviada a estudiante y secretaría.");
} catch (Exception $e) {
    error_log("Error enviando notificación: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) $conn->close();
}
// =============================== FIN DEL SCRIPT ===============================