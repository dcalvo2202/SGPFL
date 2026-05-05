<?php
if (!function_exists('sendDefenseAgreementMail')) {
    function sendDefenseAgreementMail(int $proyecto_id): array
    {
        include __DIR__ . '/../../../inc/db/bdcommon.inc';
        require_once __DIR__ . '/../../../config.inc';
        require_once __DIR__ . '/../../../inc/email_template_helper.php';

        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn->connect_error) {
            return ['success' => false, 'message' => 'Error de conexión a la base de datos.'];
        }

        $conn->set_charset("utf8mb4");

        $stmt = $conn->prepare("
            SELECT adp.proyecto_id,
                   adp.codigo_acuerdo,
                   DATE(adp.fecha_defensa) AS fecha_defensa,
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

        $codigo_acuerdo = $agreement['codigo_acuerdo'] ?? '';
        $fecha_defensa  = $agreement['fecha_defensa'] ?? '';
        $project_name   = $agreement['proyecto_nombre'] ?? 'Proyecto no identificado';

        $correo_destino = 'rodri100ro@gmail.com';
        // $correo_destino = SYSTEM_EMAIL_REPLY_TO; // Production
        $from_email = SYSTEM_EMAIL_FROM;

        $subject = "Acuerdo de defensa pública - " . $codigo_acuerdo;

        $email_content = "
            <p>La Comisión de Trabajos Finales de Graduación ha registrado un <strong>acuerdo para programar la defensa pública</strong> en el SGPFL.</p>

            <p><strong>Detalles del acuerdo:</strong></p>
            <ul>
                <li><strong>Proyecto:</strong> " . htmlspecialchars($project_name) . "</li>
                <li><strong>Código del acuerdo:</strong> " . htmlspecialchars($codigo_acuerdo) . "</li>
                <li><strong>Fecha de defensa:</strong> " . htmlspecialchars($fecha_defensa) . "</li>
                <li><strong>Fecha de emisión:</strong> " . date("d/m/Y H:i") . "</li>
            </ul>
        ";

        $message = wrapEmailBody($email_content, "Estimada Secretaría de Dirección,");

        $headers = "From: " . SYSTEM_EMAIL_FROM_NAME . " <" . SYSTEM_EMAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . SYSTEM_EMAIL_REPLY_TO . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";

        $ok = @mail($correo_destino, $subject, $message, $headers);

        error_log('HU-016 mail() destino: ' . $correo_destino);
        error_log('HU-016 mail() remitente: ' . $from_email);
        error_log('HU-016 mail() asunto: ' . $subject);
        error_log('HU-016 mail() resultado: ' . ($ok ? 'OK' : 'FALLO'));

        $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = ? WHERE proyecto_id = ?");
        if ($stmtUpdate) {
            $mailFlag = $ok ? 1 : 0;
            $stmtUpdate->bind_param("ii", $mailFlag, $proyecto_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        $conn->close();

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Correo enviado correctamente.'
                : 'No se pudo enviar el correo desde el servidor local.'
        ];
    }
}
?>