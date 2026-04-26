<?php
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendDefenseAgreementMail')) {
    function sendDefenseAgreementMail(int $proyecto_id): array
    {
        include __DIR__ . '/../../../inc/db/bdcommon.inc';

        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn->connect_error) {
            return ['success' => false, 'message' => 'Error de conexión a la base de datos.'];
        }

        $conn->set_charset("utf8mb4");
        $base_path = realpath(__DIR__ . '/../../../');

        $stmt = $conn->prepare("
            SELECT adp.proyecto_id,
                   adp.codigo_acuerdo,
                   DATE(adp.fecha_defensa) AS fecha_defensa,
                   adp.correo_destino,
                   adp.archivo_ruta,
                   adp.archivo_nombre,
                   p.nombre AS proyecto_nombre
            FROM acuerdo_defensa_publica adp
            INNER JOIN proyecto_aprobado p ON p.id_aprobado = adp.proyecto_id
            WHERE adp.proyecto_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $conn->close();
            return ['success' => false, 'message' => 'No se pudo preparar la consulta del acuerdo.'];
        }

        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $agreement = $result->fetch_assoc();
        $stmt->close();

        if (!$agreement) {
            $conn->close();
            return ['success' => false, 'message' => 'No se encontró el acuerdo registrado para este proyecto.'];
        }

        $correo_destino = trim($agreement['correo_destino'] ?? '');
        if ($correo_destino === '') {
            $conn->close();
            return ['success' => false, 'message' => 'El acuerdo no tiene correo destino registrado.'];
        }

        $codigo_acuerdo = $agreement['codigo_acuerdo'] ?? '';
        $fecha_defensa  = $agreement['fecha_defensa'] ?? '';
        $project_name   = $agreement['proyecto_nombre'] ?? 'Proyecto no identificado';

        $attachmentAbsolutePath = $base_path . '/' . ltrim($agreement['archivo_ruta'], '/');

        /**
         * CONFIGURACIÓN SMTP FUNCIONAL
         * Reemplaza estos datos por una cuenta que SÍ puedas usar hoy.
         */
        $smtpHost   = 'smtp.gmail.com';   // Si usas Gmail
        $smtpPort   = 587;
        $smtpUser   = 'TU_CORREO_REAL@gmail.com';
        $smtpPass   = 'TU_APP_PASSWORD';
        $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = $smtpPort;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->CharSet    = 'UTF-8';

            // Para depurar, súbelo a 2 temporalmente
            $mail->SMTPDebug  = 0;
            $mail->Debugoutput = 'error_log';

            $mail->setFrom($smtpUser, 'SGPFL');
            $mail->addAddress($correo_destino);

            $mail->Subject = 'Acuerdo de defensa pública - ' . $codigo_acuerdo;
            $mail->isHTML(true);
            $mail->Body = '
            <html>
            <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa; }
                .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 13px; color: #555; }
                .footer img { width: 120px; vertical-align: middle; margin-right: 10px; }
                .footer td { vertical-align: top; }
                .divider { border-left: 2px solid #999; width: 1px; }
                a { color: #0056b3; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
            </head>
            <body>
            <div class="container">
                <p>Estimada Secretaría de Dirección,</p>

                <p>La Comisión de Trabajos Finales de Graduación ha registrado un <strong>acuerdo para programar la defensa pública</strong> en el Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL).</p>

                <p><strong>Detalles del acuerdo:</strong></p>
                <ul>
                    <li><strong>Proyecto:</strong> ' . htmlspecialchars($project_name) . '</li>
                    <li><strong>Código del acuerdo:</strong> ' . htmlspecialchars($codigo_acuerdo) . '</li>
                    <li><strong>Fecha de defensa:</strong> ' . htmlspecialchars($fecha_defensa) . '</li>
                    <li><strong>Fecha de emisión:</strong> ' . date("d/m/Y H:i") . '</li>
                </ul>

                <p>Se remite esta notificación como respaldo institucional del acuerdo registrado.</p>

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
            </html>';

            $mail->AltBody =
                "Acuerdo de defensa pública registrado.\n" .
                "Proyecto: {$project_name}\n" .
                "Código del acuerdo: {$codigo_acuerdo}\n" .
                "Fecha de defensa: {$fecha_defensa}\n";

            if (is_file($attachmentAbsolutePath)) {
                $attachmentName = !empty($agreement['archivo_nombre'])
                    ? $agreement['archivo_nombre']
                    : basename($attachmentAbsolutePath);
                $mail->addAttachment($attachmentAbsolutePath, $attachmentName);
            }

            $mail->send();

            $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = 1 WHERE proyecto_id = ?");
            if ($stmtUpdate) {
                $stmtUpdate->bind_param("i", $proyecto_id);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            $conn->close();

            return [
                'success' => true,
                'message' => 'Correo enviado correctamente a la secretaría.'
            ];
        } catch (Exception $e) {
            $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = 0 WHERE proyecto_id = ?");
            if ($stmtUpdate) {
                $stmtUpdate->bind_param("i", $proyecto_id);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            $errorMessage = $e->getMessage();
            error_log('HU-016 PHPMailer error: ' . $errorMessage);

            $conn->close();

            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo. Detalle: ' . $errorMessage
            ];
        } catch (\Throwable $e) {
            $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = 0 WHERE proyecto_id = ?");
            if ($stmtUpdate) {
                $stmtUpdate->bind_param("i", $proyecto_id);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            $errorMessage = $e->getMessage();
            error_log('HU-016 error general de correo: ' . $errorMessage);

            $conn->close();

            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo. Detalle: ' . $errorMessage
            ];
        }
    }
}
?>