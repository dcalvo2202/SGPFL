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

    $subject = "[SGPFL - Universidad Nacional] Notificación oficial de vencimiento de plazo";

    $body = '
<html>
  <body style="margin:0; padding:0; background-color:#eef2f6; font-family:Georgia, Times New Roman, serif; color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f6; padding:32px 0;">
      <tr>
        <td align="center">
          <table width="760" cellpadding="0" cellspacing="0" border="0" style="width:760px; max-width:760px; background:#ffffff; border:1px solid #cfd8e3;">
            <tr>
              <td style="padding:28px 36px 12px 36px; border-bottom:4px solid #b91c1c;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td valign="top" style="font-size:13px; line-height:1.6; color:#374151;">
                      <div style="font-size:18px; font-weight:bold; color:#111827;">
                        Universidad Nacional
                      </div>
                      <div>Sistema de Gestión de Proyectos Finales de Graduación</div>
                      <div>Comunicación oficial automatizada</div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr> 
              <td style="padding:36px;">
                <div style="font-size:22px; font-weight:bold; color:#111827; margin-bottom:18px;">
                  Notificación oficial de vencimiento de plazo
                </div>

                <p style="margin:0 0 18px 0; font-size:16px; line-height:1.8;">
                  Estimado/a señor/a <strong>' . htmlspecialchars($data['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>:
                </p>

                <p style="margin:0 0 18px 0; font-size:15px; line-height:1.9; text-align:justify;">
                  Por este medio se le informa que el plazo establecido para la entrega del documento final del proyecto
                  <strong>' . htmlspecialchars($data['proyecto'], ENT_QUOTES, 'UTF-8') . '</strong>
                  vencerá el día <strong>' . htmlspecialchars($data['fecha_limite'], ENT_QUOTES, 'UTF-8') . '</strong>.
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0; border:1px solid #d1d5db; background:#f9fafb;">
                  <tr>
                    <td colspan="2" style="padding:14px 18px; background:#e5e7eb; font-size:14px; font-weight:bold; color:#111827;">
                      Detalle de la notificación
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:14px 18px; width:220px; font-size:14px; font-weight:bold; border-top:1px solid #d1d5db;">
                      Proyecto
                    </td>
                    <td style="padding:14px 18px; font-size:14px; border-top:1px solid #d1d5db;">
                      ' . htmlspecialchars($data['proyecto'], ENT_QUOTES, 'UTF-8') . '
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:14px 18px; width:220px; font-size:14px; font-weight:bold; border-top:1px solid #d1d5db;">
                      Fecha límite de entrega
                    </td>
                    <td style="padding:14px 18px; font-size:14px; border-top:1px solid #d1d5db;">
                      ' . htmlspecialchars($data['fecha_limite'], ENT_QUOTES, 'UTF-8') . '
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:14px 18px; width:220px; font-size:14px; font-weight:bold; border-top:1px solid #d1d5db;">
                      Días restantes
                    </td>
                    <td style="padding:14px 18px; font-size:14px; border-top:1px solid #d1d5db;">
                      ' . (int)$data['dias_restantes'] . '
                    </td>
                  </tr>
                </table>

                <p style="margin:0 0 18px 0; font-size:15px; line-height:1.9; text-align:justify;">
                  Se le solicita tomar las previsiones necesarias y realizar la entrega dentro del plazo indicado, a fin de cumplir con los procedimientos académicos y administrativos correspondientes.
                </p>

                <p style="margin:0 0 28px 0; font-size:15px; line-height:1.9; text-align:justify;">
                  En caso de requerir información adicional, favor comunicarse por los medios oficiales establecidos por la unidad académica.
                </p>

                <p style="margin:0; font-size:15px; line-height:1.8;">
                  Atentamente,
                </p>

                <p style="margin:10px 0 0 0; font-size:15px; line-height:1.8;">
                  <strong>Sistema de Gestión de Proyectos Finales de Graduación</strong><br>
                  Universidad Nacional
                </p>
              </td>
            </tr>

            <tr>
              <td style="padding:18px 36px; background:#f3f4f6; border-top:1px solid #d1d5db; font-size:12px; color:#4b5563; line-height:1.7;">
                Este correo corresponde a una notificación automática generada por el sistema institucional SGPFL.
                Por favor, no responda directamente a este mensaje.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>';

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

