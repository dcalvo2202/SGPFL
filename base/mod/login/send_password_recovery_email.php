<?php
/**
 * Script para enviar correo de recuperación de contraseña
 * Genera un token y lo envía por correo
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Inicialización
require_once __DIR__ . '/../../config.inc';
require_once __DIR__ . '/../../inc/db/db.php';
require_once __DIR__ . '/../../lib/mysession/mySession.conf.php';
require_once __DIR__ . '/../../inc/email_template_helper.php';

// Obtener datos JSON del Request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['user_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$email = trim($data['email']);
$user_type = trim($data['user_type']);

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido']);
    exit;
}

// Validar que sea asesor externo
if ($user_type !== 'external_advisor') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de usuario inválido']);
    exit;
}

try {
    // Buscar el asesor externo por email
    $sql = "SELECT applicant_id, full_name, email, status 
            FROM external_advisor_profile_requests 
            WHERE email = ? AND status = 'Aprobado'";
    
    $result = seleccion_segura($sql, [$email]);
    
    if (empty($result)) {
        // No registrar el resultado exacto por seguridad, pero informar
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No encontramos un asesor externo registrado con este correo']);
        exit;
    }

    $advisor_id = $result[0]['applicant_id'];
    $advisor_name = $result[0]['full_name'];

    // Verificar que tenga usuario en sis_login, si no crear automáticamente
    $check_user = seleccion_segura("SELECT id FROM sis_login WHERE id = ?", [$advisor_id]);
    if (empty($check_user)) {
        // Crear automáticamente el usuario en sis_login
        // Generar contraseña temporal (primeros 4 dígitos de cédula + año)
        $temp_password = substr($advisor_id, 0, 4) . date('Y');
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        $rol_asesor = 5; // Rol para asesor externo
        
        $insert_login_result = ejecutar_query(
            "INSERT INTO sis_login (id, pass, id_roll) VALUES (?, ?, ?)",
            [$advisor_id, $hashed_password, $rol_asesor]
        );
        
        if ($insert_login_result['success'] === false) {
            error_log("Error creando sis_login para asesor $advisor_id: " . $insert_login_result['error']);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al preparar el usuario en el sistema']);
            exit;
        }
    }

    // Verificar que tenga perfil en sis_user, si no crear
    $check_user_profile = seleccion_segura("SELECT id FROM sis_user WHERE id = ?", [$advisor_id]);
    if (empty($check_user_profile)) {
        // Crear perfil en sis_user
        $nombre_upper = strtoupper($email);
        $insert_user_result = ejecutar_query(
            "INSERT INTO sis_user (id, nombre, email) VALUES (?, ?, ?)",
            [$advisor_id, $advisor_name, $email]
        );
        
        if ($insert_user_result['success'] === false) {
            error_log("Error creando sis_user para asesor $advisor_id: " . $insert_user_result['error']);
            // No es crítico, continuar de todas formas
        }
    }

    // Generar token único
    $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
    $expires_at = date('Y-m-d H:i:s', time() + 3600); // Expira en 1 hora

    // Verificar e insertar token en BD
    // Primero, crear la tabla si no existe (mediante función de inicialización)
    require_once __DIR__ . '/../../inc/db/init_password_recovery.php';
    if (function_exists('init_password_recovery_table')) {
        @init_password_recovery_table();
    }

    // Insertar o actualizar el token (eliminar tokens previos sin usar)
    $delete_old = "DELETE FROM password_recovery_tokens 
                   WHERE user_id = ? AND type = 'external_advisor' AND used = 0";
    ejecutar_query($delete_old, [$advisor_id]);

    $insert_token = "INSERT INTO password_recovery_tokens 
                     (user_id, email, token, type, expires_at) 
                     VALUES (?, ?, ?, 'external_advisor', ?)";
    
    ejecutar_query($insert_token, [$advisor_id, $email, $token, $expires_at]);

    // Construir URL de recuperación
    $recovery_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/mod/login/cambiar_contrasena_asesor.php?token=' . urlencode($token);

    // Preparar correo
    $subject = "Recuperación de Contraseña - SGPFL";
    
    $email_content = "
    <p>Hemos recibido una solicitud para resetear la contraseña de tu cuenta como Asesor Externo en el Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL).</p>
    
    <div style='text-align: center; margin: 30px 0;'>
        <a href='$recovery_url' style='display: inline-block; padding: 12px 30px; background-color: #dc3545; color: #ffffff !important; text-decoration: none; border-radius: 4px; font-weight: bold;'>Cambiar Mi Contraseña</a>
    </div>
    
    <p>O copia y pega este enlace en tu navegador:</p>
    <p style='word-break: break-all; background-color: #f5f5f5; padding: 10px; border-radius: 4px;'><small>$recovery_url</small></p>
    
    <div style='background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 4px; margin: 20px 0; color: #856404;'>
        <strong>Importante:</strong>
        <ul style='margin: 10px 0; padding-left: 20px;'>
            <li>Este enlace expirará en <strong>1 hora</strong></li>
            <li>El enlace solo se puede usar una sola vez</li>
            <li>Si no solicitaste este cambio, ignora este correo</li>
        </ul>
    </div>
    
    <p>Si tienes problemas al acceder, contacta al equipo de soporte de la Escuela de Informática:</p>
    <p><strong>Email:</strong> escinf@una.cr</p>
";

$message = wrapEmailBody($email_content, "Hola <strong>$advisor_name</strong>,");

    // Headers para HTML
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SYSTEM_EMAIL_FROM_NAME . " <" . SYSTEM_EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . SYSTEM_EMAIL_REPLY_TO . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Enviar correo
    // $mail_sent = mail($email, $subject, $message, $headers);
    $mail_sent = mail('rodri100ro@gmail.com', $subject, $message, $headers);

    if ($mail_sent) {
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Correo de recuperación enviado exitosamente. Por favor revisa tu correo.'
        ]);
    } else {
        // El correo no se envió, pero no revelamos detalles técnicos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo enviar el correo. Por favor intenta más tarde o contacta al soporte.'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en recuperación de contraseña: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ocurrió un error procesando tu solicitud. Por favor intenta más tarde.'
    ]);
}
?>
