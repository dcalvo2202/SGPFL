<?php
/**
 * Script para validar token y cambiar contraseña del asesor externo
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config.inc';
require_once __DIR__ . '/../../inc/db/db.php';

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    error_log("Error: Datos incompletos en reset_password_advisor");
    exit;
}

$token = trim($data['token']);
$new_password = trim($data['new_password']);

// Validar contraseña
if (strlen($new_password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres']);
    error_log("Error: Contraseña muy corta - reset_password_advisor");
    exit;
}

try {
    // Diagnóstico de conexión activa (BD/host/puerto)
    $db_info = seleccion("SELECT DATABASE() AS db_name, @@hostname AS db_host, @@port AS db_port");
    if (!empty($db_info)) {
        error_log("Diagnóstico DB activa: " . $db_info[0]['db_name'] . "@" . $db_info[0]['db_host'] . ":" . $db_info[0]['db_port']);
    }

    // Validar token
    $sql = "SELECT user_id, email, expires_at, used FROM password_recovery_tokens 
            WHERE token = ? AND type = 'external_advisor'";

    $result = seleccion_segura($sql, [$token]);

    if (empty($result)) {
        throw new Exception("Token inválido");
    }

    $token_data = $result[0];
    $token_email = $token_data['email'];
    $expires_at = $token_data['expires_at'];
    $is_used = $token_data['used'];

    // Verificar si fue usado
    if ($is_used) {
        throw new Exception("Este token ya fue utilizado");
    }

    // Verificar si expiró
    if (strtotime($expires_at) < time()) {
        throw new Exception("El token ha expirado. Por favor solicita un nuevo link");
    }

    // VALIDAR: Obtener el user_id REAL desde sis_login usando el email
    // NO confiar en el user_id del token (puede estar desincronizado)
    $verify_sql = "SELECT sl.id 
                   FROM sis_login sl
                   INNER JOIN external_advisor_profile_requests eapr 
                       ON sl.id = eapr.applicant_id
                   WHERE eapr.email = ? 
                     AND eapr.status = 'Aprobado'";
    
    $verify_result = seleccion_segura($verify_sql, [$token_email]);

    if (empty($verify_result)) {
        error_log("Error: No se encontró usuario aprobado para email: " . $token_email);
        throw new Exception("No se encontró el usuario asociado a este correo electrónico");
    }

    // Usar el ID REAL de la consulta validada
    $user_id = $verify_result[0]['id'];
    error_log("Usuario validado por email - ID correcto: " . $user_id);

    // Hashear la nueva contraseña usando password_hash()
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Diagnóstico previo al update
    $before_rows = seleccion_segura("SELECT id, HEX(id) AS id_hex, pass FROM sis_login WHERE id = ?", [$user_id]);
    error_log("Diagnóstico previo sis_login filas exactas para user_id " . $user_id . ": " . count($before_rows));
    if (!empty($before_rows)) {
        $before_hash = $before_rows[0]['pass'];
        error_log("Diagnóstico previo hash (primeros 25): " . substr($before_hash, 0, 25));
        error_log("Diagnóstico previo id_hex: " . $before_rows[0]['id_hex']);
    }

    // Actualizar contraseña en sis_login
    $update_sql = "UPDATE sis_login SET pass = ? WHERE id = ?";

    $update_result = ejecutar_query($update_sql, [$hashed_password, $user_id]);
    if (!$update_result['success']) {
        throw new Exception("Error al actualizar la contraseña: " . $update_result['error']);
    }

    $affected_rows = (int)$update_result['affected_rows'];
    error_log("Filas afectadas en UPDATE sis_login: " . $affected_rows);

    if ($affected_rows === 0) {
        throw new Exception("La contraseña no se actualizó (usuario no encontrado o contraseña igual)");
    }

    // Verificación explícita post-update
    $verify_sql = "SELECT pass FROM sis_login WHERE id = ?";
    $verify_result = seleccion_segura($verify_sql, [$user_id]);

    if (empty($verify_result) || !isset($verify_result[0]['pass'])) {
        throw new Exception("No se pudo verificar la contraseña actualizada");
    }

    $stored_hash = $verify_result[0]['pass'];
    $verify_ok = password_verify($new_password, $stored_hash);
    error_log("Verificación post-update con password_verify: " . ($verify_ok ? "OK" : "FALLÓ"));

    // Diagnóstico posterior al update
    $after_rows = seleccion_segura("SELECT id, HEX(id) AS id_hex, pass FROM sis_login WHERE id = ?", [$user_id]);
    error_log("Diagnóstico posterior sis_login filas exactas para user_id " . $user_id . ": " . count($after_rows));
    if (!empty($after_rows)) {
        $after_hash = $after_rows[0]['pass'];
        error_log("Diagnóstico posterior hash (primeros 25): " . substr($after_hash, 0, 25));
        error_log("Diagnóstico posterior id_hex: " . $after_rows[0]['id_hex']);
    }

    // Buscar posibles IDs equivalentes por TRIM (espacios ocultos)
    $trim_rows = seleccion_segura("SELECT id, HEX(id) AS id_hex FROM sis_login WHERE TRIM(id) = ?", [$user_id]);
    error_log("Diagnóstico filas por TRIM(id) para user_id " . $user_id . ": " . count($trim_rows));

    if (!$verify_ok) {
        throw new Exception("La verificación de la contraseña actualizada falló");
    }

    // Marcar token como usado
    $mark_used_sql = "UPDATE password_recovery_tokens SET used = 1, used_at = NOW() WHERE token = ?";

    $mark_result = ejecutar_query($mark_used_sql, [$token]);
    if (!$mark_result['success']) {
        throw new Exception("Error al marcar token como usado: " . $mark_result['error']);
    }

    error_log("Token marcado como usado: " . $token);

    // Limpiar otros tokens sin usar del usuario
    $cleanup_sql = "DELETE FROM password_recovery_tokens 
                    WHERE user_id = ? AND type = 'external_advisor' AND used = 0 AND token != ?";

    $cleanup_result = ejecutar_query($cleanup_sql, [$user_id, $token]);
    if (!$cleanup_result['success']) {
        throw new Exception("Error al limpiar tokens: " . $cleanup_result['error']);
    }

    error_log("Tokens obsoletos limpiados para usuario: " . $user_id);

    // Log de la acción
    $log_sql = "INSERT INTO sis_log (id_user, date_bi, action_type, action_result, detail) VALUES (?, NOW(), 'PASSWORD_RECOVERY', 'SUCCESS', ?)";
    $detail = "Cambio de contraseña - Recuperación por asesor externo";

    $log_result = ejecutar_query($log_sql, [$user_id, $detail]);
    if (!$log_result['success']) {
        throw new Exception("Error al registrar log: " . $log_result['error']);
    }

    error_log("Log de cambio de contraseña registrado para usuario: " . $user_id);

    // Obtener email del asesor externo
    $confirm_email_sql = "SELECT email FROM external_advisor_profile_requests WHERE applicant_id = ?";
    $email_result = seleccion_segura($confirm_email_sql, [$user_id]);

    if (!empty($email_result)) {
        $email_data = $email_result[0];
        $advisor_email = $email_data['email'];
        error_log("Email encontrado para usuario: " . $advisor_email);
        
        $confirm_message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
                    .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 30px; border-radius: 8px; }
                    .header { text-align: center; border-bottom: 2px solid #28a745; padding-bottom: 20px; margin-bottom: 20px; }
                    .header h2 { color: #28a745; margin: 0; }
                    .success-box { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
                    .footer { text-align: center; border-top: 1px solid #eeeeee; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #999999; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>✓ Contraseña Actualizada</h2>
                    </div>
                    <div class='success-box'>
                        <p><strong>Tu contraseña ha sido cambiada exitosamente.</strong></p>
                        <p>Ahora puedes iniciar sesión con tu nueva contraseña.</p>
                    </div>
                    <p>Si no realizaste este cambio, contacta inmediatamente a: escinf@una.cr</p>
                    <div class='footer'>
                        <p>Sistema Gestor de Proyectos Finales de Licenciatura - Escuela de Informática, UNA</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SYSTEM_EMAIL_FROM_NAME . " <" . SYSTEM_EMAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . SYSTEM_EMAIL_REPLY_TO . "\r\n";
        
        $mail_sent = @mail($advisor_email, "Contraseña Actualizada - SGPFL", $confirm_message, $headers);
        error_log("Email de confirmación enviado a " . $advisor_email . ": " . ($mail_sent ? "Éxito" : "Falló"));
    } else {
        error_log("Advertencia: No se encontró email para usuario: " . $user_id);
    }

    error_log("Proceso de reset de contraseña completado exitosamente para usuario: " . $user_id);
    
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Contraseña actualizada exitosamente. Redirigiéndote al login...'
    ]);

} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Error al resetear contraseña: " . $error_message);
    error_log("Token: " . $token);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Determinar HTTP status code basado en el error
    if (strpos($error_message, 'Token') !== false) {
        $http_code = 401;
    } elseif (strpos($error_message, 'no encontrado') !== false) {
        $http_code = 404;
    } else {
        $http_code = 500;
    }
    
    http_response_code($http_code);
    echo json_encode([
        'success' => false, 
        'message' => 'Ocurrió un error al actualizar la contraseña: ' . $error_message
    ]);
}
?>
