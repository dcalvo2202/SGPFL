<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

function sendDefenseAgreementMail(array $data): array {
    include __DIR__ . '/../../../inc/db/bdcommon.inc';

    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Error de conexión a BD'];
    }

    $proyecto_id = (int)($data['proyecto_id'] ?? 0);

    if ($proyecto_id <= 0) {
        $conn->close();
        return ['success' => false, 'message' => 'Proyecto inválido'];
    }

    $stmt = $conn->prepare("
        SELECT adp.codigo_acuerdo,
               DATE(adp.fecha_defensa) AS fecha_defensa,
               adp.correo_destino,
               p.nombre AS proyecto_nombre
        FROM acuerdo_defensa_publica adp
        INNER JOIN proyecto_aprobado p ON p.id_aprobado = adp.proyecto_id
        WHERE adp.proyecto_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'No se pudo preparar la consulta'];
    }

    $stmt->bind_param("i", $proyecto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agreement = $result->fetch_assoc();
    $stmt->close();

    if (!$agreement) {
        $conn->close();
        return ['success' => false, 'message' => 'No se encontró el acuerdo'];
    }

    $correo_destino = trim($agreement['correo_destino'] ?? '');
    $codigo_acuerdo = $agreement['codigo_acuerdo'] ?? '';
    $fecha_defensa  = $agreement['fecha_defensa'] ?? '';
    $project_name   = $agreement['proyecto_nombre'] ?? 'Proyecto no identificado';

    if ($correo_destino === '') {
        $conn->close();
        return ['success' => false, 'message' => 'Correo destino vacío'];
    }

    $subject = "Acuerdo de defensa pública - " . $codigo_acuerdo;

    $message = '
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
    </style>
    </head>
    <body>
    <div class="container">
        <p>Estimada Secretaría de Dirección,</p>

        <p>La Comisión de Trabajos Finales de Graduación ha registrado un <strong>acuerdo para programar la defensa pública</strong> en el SGPFL.</p>

        <p><strong>Detalles del acuerdo:</strong></p>
        <ul>
            <li><strong>Proyecto:</strong> ' . htmlspecialchars($project_name) . '</li>
            <li><strong>Código del acuerdo:</strong> ' . htmlspecialchars($codigo_acuerdo) . '</li>
            <li><strong>Fecha de defensa:</strong> ' . htmlspecialchars($fecha_defensa) . '</li>
            <li><strong>Fecha de emisión:</strong> ' . date("d/m/Y H:i") . '</li>
        </ul>

        <div class="footer">
        <table>
            <tr>
            <td><img src="http://www.escinf.una.ac.cr/templates/zt_zizia/images/logo.png" alt="Escuela de Informática"></td>
            <td class="divider"></td>
            <td>
                <strong>Escuela de Informática</strong><br>
                Tel: <strong>(506) 2562-6363</strong> · Fax: <strong>(506) 2562-6384</strong><br>
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

    $headers = "From: malcolm.chaves.obando@est.una.ac.cr\r\n";
    $headers .= "Reply-To: malcolm.chaves.obando@est.una.ac.cr\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";

    $ok = @mail($correo_destino, $subject, $message, $headers);
    error_log('HU-016 mail() destino: ' . $correo_destino);
    error_log('HU-016 mail() asunto: ' . $subject);
    error_log('HU-016 mail() resultado: ' . ($ok ? 'OK' : 'FALLO'));

    if ($ok) {
        $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = 1 WHERE proyecto_id = ?");
        if ($stmtUpdate) {
            $stmtUpdate->bind_param("i", $proyecto_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }
    } else {
        $stmtUpdate = $conn->prepare("UPDATE acuerdo_defensa_publica SET enviado_correo = 0 WHERE proyecto_id = ?");
        if ($stmtUpdate) {
            $stmtUpdate->bind_param("i", $proyecto_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }
    }

    $conn->close();

    return [
        'success' => $ok,
        'message' => $ok
            ? 'Correo enviado correctamente.'
            : 'No se pudo enviar el correo desde el servidor local.'
    ];
}
?>