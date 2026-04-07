<?php
// Funciones para alertas de vencimiento de entrega de documentos finales

require_once __DIR__ . '/../config.inc';


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
    if (!constant('DEADLINE_EMAIL_ENABLED')) return false;

    $subject = "[SGPFL] Alerta: Plazo de entrega próximo a vencer";

    $body = "<html><body>"
        . "<h2>Estimado/a {$data['nombre']},</h2>"
        . "<p>El plazo para entregar el documento final del proyecto <b>{$data['proyecto']}</b> vence el <b>{$data['fecha_limite']}</b> ({$data['dias_restantes']} días restantes).</p>"
        . "<p>Por favor, asegúrese de cumplir con la entrega antes de la fecha límite.</p>"
        . "<br><small>Este es un mensaje automático del sistema SGPFL.</small>"
        . "</body></html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . constant('DEADLINE_FROM_EMAIL') . "\r\n";
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

