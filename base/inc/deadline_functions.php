<?php
// Funciones para alertas de vencimiento de entrega de documentos finales

require_once __DIR__ . '/../config.inc';
require_once __DIR__ . '/email_template_helper.php';


/**
 * Obtiene los proyectos próximos a vencer según los umbrales definidos.
 * Devuelve un array de proyectos con usuario, fecha límite y días restantes.
 */
function getProjectsNearDeadline($conn) {
    $thresholds = constant('DEADLINE_THRESHOLDS');
    $placeholders = implode(',', array_fill(0, count($thresholds), '?'));
    $sql = "
        SELECT p.id_aprobado AS project_id, pae.estudiante_id AS user_id, p.fecha_finalizacion AS fecha_base,
               (SELECT GROUP_CONCAT(DATE(pr.response_date)) FROM tfg_extension_requests pr WHERE pr.proposal_id = p.proposal_id AND pr.status = 'aprobada') AS prorrogas,
               DATEDIFF(
                   COALESCE(
                       (SELECT MAX(pr.response_date) FROM tfg_extension_requests pr WHERE pr.proposal_id = p.proposal_id AND pr.status = 'aprobada'),
                       p.fecha_finalizacion
                   ),
                   CURDATE()
               ) AS dias_restantes
        FROM proyecto_aprobado p
        INNER JOIN proyecto_aprobado_estudiantes pae ON pae.id_aprobado = p.id_aprobado
        WHERE p.fecha_finalizacion IS NOT NULL
        HAVING dias_restantes IN ($placeholders)
    ";
    $stmt = $conn->prepare($sql);
    foreach ($thresholds as $k => $v) {
        $stmt->bindValue($k + 1, $v, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcula la fecha límite real considerando prórrogas aprobadas.
 * $fecha_base: string (YYYY-MM-DD)
 * $prorrogas: string (fechas separadas por coma)
 * return: string (YYYY-MM-DD)
 */
function calculateRealDeadline($fecha_base, $prorrogas) {
    if (!$prorrogas) return $fecha_base;
    $fechas = explode(',', $prorrogas);
    $max = $fecha_base;
    foreach ($fechas as $f) {
        if (strtotime($f) > strtotime($max)) {
            $max = $f;
        }
    }
    return $max;
}

/**
 * Envía un correo de alerta de vencimiento.
 * $to: email destino
 * $data: array con claves 'nombre', 'proyecto', 'fecha_limite', 'dias_restantes'
 */
function sendDeadlineEmail($to, $data) {
    $to = 'rodri100ro@gmail.com';
    // $to = 'rodri100ro@gmail.com'; // Production: use original $to parameter
    if (!constant('DEADLINE_EMAIL_ENABLED')) return false;

    $subject = "Recordatorio de vencimiento del plazo de entrega - SGPFL";

    $email_content = "
        <p>
        Le recordamos que el plazo para la entrega del documento final de su Trabajo Final de Graduación
        se encuentra próximo a vencer.
        </p>

        <div style='border-left: 4px solid #b91c1c; background-color: #fff1f2; padding: 12px 14px; margin: 14px 0;'>
        Este es un recordatorio automático del sistema SGPFL para evitar atrasos en el proceso de cierre académico.
        </div>

        <p><strong>Detalle del recordatorio:</strong></p>
        <table style='width: 100%; border-collapse: collapse; margin-top: 12px;'>
        <tr>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px; width: 210px; font-weight: bold; background-color: #f3f4f6;'>Proyecto</td>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px;'>" . htmlspecialchars($data['proyecto'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px; width: 210px; font-weight: bold; background-color: #f3f4f6;'>Fecha límite de entrega</td>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px;'>" . htmlspecialchars($data['fecha_limite'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px; width: 210px; font-weight: bold; background-color: #f3f4f6;'>Días restantes</td>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px;'><strong>" . (int)$data['dias_restantes'] . "</strong></td>
        </tr>
        <tr>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px; width: 210px; font-weight: bold; background-color: #f3f4f6;'>Fecha de emisión</td>
        <td style='border: 1px solid #d9d9d9; padding: 8px 10px;'>" . date('d/m/Y H:i') . "</td>
        </tr>
        </table>

        <p style='margin-top:16px;'>
        Le solicitamos tomar las previsiones necesarias y realizar la entrega dentro del plazo indicado para cumplir
        con los procedimientos académicos y administrativos correspondientes.
        </p>

        <p>
        En caso de requerir apoyo, favor comunicarse por los medios oficiales de la Escuela de Informática.
        </p>

        <p style='font-size:12px; color:#777;'>Este correo fue generado automáticamente por el SGPFL. Por favor no responda este mensaje.</p>
    ";

    $body = wrapEmailBody($email_content, "Estimado/a estudiante <strong>" . htmlspecialchars($data['nombre'], ENT_QUOTES, 'UTF-8') . "</strong>,");

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . constant('DEADLINE_FROM_NAME') . " <" . constant('DEADLINE_FROM_EMAIL') . ">\r\n";
    $headers .= "Reply-To: " . constant('DEADLINE_FROM_EMAIL') . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $mailSent = mail($to, $subject, $body, $headers);
    if (!$mailSent) {
        $lastError = error_get_last();
        error_log('[deadline] mail() falló: ' . ($lastError['message'] ?? 'sin detalle'));
    }

    return $mailSent;
}

/**
 * Verifica si ya se envió una alerta para este usuario/proyecto/días.
 */
function hasAlertBeenSent($conn, $user_id, $project_id, $days) {
    $sql = "SELECT 1 FROM deadline_alerts_sent WHERE user_id = ? AND project_id = ? AND days_threshold = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $project_id, $days]);
    return $stmt->fetchColumn() ? true : false;
}

/**
 * Registra que se envió una alerta para este usuario/proyecto/días.
 */
function markAlertAsSent($conn, $user_id, $project_id, $days) {
    $sql = "INSERT INTO deadline_alerts_sent (user_id, project_id, days_threshold, sent_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $project_id, $days]);
}

