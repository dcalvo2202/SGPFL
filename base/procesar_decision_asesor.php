<?php
/**
 * HU-012: Procesamiento de decisión sobre solicitud de Asesor Externo
 * 
 * Maneja la aprobación o rechazo de solicitudes:
 * - Aprobado: Crea usuario en sis_login y sis_user con rol 5, establece vigencia
 * - Rechazado: Registra motivo y permite hasta 2 reintentos
 * 
 * Envía correo al solicitante y notifica a la CTFG.
 */

header('Content-Type: application/json');

// Cargar sesión
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");

// Control de acceso: solo Gestor Académico (2) o Administrador (1)
if (!$current_user_id || ($current_user_rol != 2 && $current_user_rol != 1)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// Cargar BD
include_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/config.inc';

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';

// Configuración
$MAX_REJECTION_ATTEMPTS = 2; // Máximo 2 reintentos después del primer rechazo

// Validar datos de entrada
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$decision = isset($_POST['decision']) ? trim($_POST['decision']) : '';
$comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido.']);
    exit;
}

if (!in_array($decision, ['Aprobado', 'Rechazado'])) {
    echo json_encode(['success' => false, 'message' => 'Decisión inválida.']);
    exit;
}

if ($decision === 'Rechazado' && strlen($comentarios) < 10) {
    echo json_encode(['success' => false, 'message' => 'Debe especificar un motivo de rechazo (mínimo 10 caracteres).']);
    exit;
}

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos.');
    }
    $conn->set_charset('utf8');

    // Obtener datos de la solicitud
    $stmt = $conn->prepare("SELECT * FROM external_advisor_profile_requests WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No se encontró la solicitud.');
    }

    $solicitud = $result->fetch_assoc();
    $stmt->close();

    // Verificar que esté en estado "En Revision" o "En Revisión" (con o sin tilde)
    $estado_actual = $solicitud['status'];
    $estados_validos = ['En Revision', 'En Revisión'];
    if (!in_array($estado_actual, $estados_validos)) {
        throw new Exception('Esta solicitud ya fue procesada anteriormente (Estado actual: ' . $estado_actual . ').');
    }

    // Iniciar transacción
    $conn->begin_transaction();

    if ($decision === 'Aprobado') {
        // ============================
        // APROBAR: Crear usuario
        // ============================
        
        // Verificar que no exista ya el usuario
        $check_stmt = $conn->prepare("SELECT id FROM sis_login WHERE id = ?");
        $check_stmt->bind_param('s', $solicitud['applicant_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception('Ya existe un usuario con esa cédula en el sistema.');
        }
        $check_stmt->close();

        // Generar contraseña temporal (primeros 4 dígitos de cédula + año actual)
        $temp_password = substr($solicitud['applicant_id'], 0, 4) . date('Y');
        $hashed_password = md5($temp_password);

        // Insertar en sis_login (rol 5 = Asesor Externo)
        $rol_asesor = 5;
        $stmt_login = $conn->prepare("INSERT INTO sis_login (id, pass, id_roll) VALUES (?, ?, ?)");
        $stmt_login->bind_param('ssi', $solicitud['applicant_id'], $hashed_password, $rol_asesor);
        
        if (!$stmt_login->execute()) {
            error_log("ERROR sis_login: " . $stmt_login->error);
            throw new Exception('Error al crear credenciales de acceso: ' . $stmt_login->error);
        }
        error_log("ÉXITO sis_login: Usuario {$solicitud['applicant_id']} creado con rol 5");
        $stmt_login->close();

        // Insertar en sis_user
        $stmt_user = $conn->prepare("INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES (?, ?, ?, ?, ?)");
        $nombre_upper = strtoupper($solicitud['full_name']);
        $telefono = $solicitud['telefono'] ?: null;
        $id_tipo_tel = $solicitud['id_tipo_tel'] ?: null;
        $stmt_user->bind_param('sssss', $solicitud['applicant_id'], $nombre_upper, $solicitud['email'], $telefono, $id_tipo_tel);
        
        if (!$stmt_user->execute()) {
            error_log("ERROR sis_user: " . $stmt_user->error);
            throw new Exception('Error al crear perfil de usuario: ' . $stmt_user->error);
        }
        error_log("ÉXITO sis_user: Perfil de {$nombre_upper} creado");
        $stmt_user->close();

        // Calcular fecha de vigencia (1 año por defecto, o hasta cierre del TFG si está vinculado)
        // Por defecto: 1 año desde la aprobación
        $vigencia_default = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        // Actualizar estado de la solicitud con fecha de vigencia
        $stmt_update = $conn->prepare("UPDATE external_advisor_profile_requests 
                                        SET status = 'Aprobado', 
                                            admin_comments = ?, 
                                            reviewed_by = ?, 
                                            reviewed_at = NOW(),
                                            approval_expires_at = ?
                                        WHERE id = ?");
        $stmt_update->bind_param('sssi', $comentarios, $current_user_id, $vigencia_default, $id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();

        // ============================
        // ENVIAR CORREO AL ASESOR
        // ============================
        $subject = 'Solicitud Aprobada - Asesor Externo SGPFL';
        $message_body = "
        <html><head><meta charset='UTF-8'></head><body>
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Estimado/a <strong>{$solicitud['full_name']}</strong>,</p>
            <p>Nos complace informarle que su solicitud de registro como <strong>Asesor Externo</strong> ha sido <strong style='color: #198754;'>APROBADA</strong>.</p>
            <p><strong>Sus credenciales de acceso son:</strong></p>
            <ul>
                <li><strong>Usuario:</strong> {$solicitud['applicant_id']}</li>
                <li><strong>Contraseña temporal:</strong> {$temp_password}</li>
            </ul>
            <p><strong>Vigencia de la aprobación:</strong> Hasta " . date('d/m/Y', strtotime($vigencia_default)) . " o hasta el cierre del TFG asignado.</p>
            <p>Por seguridad, le recomendamos cambiar su contraseña después del primer inicio de sesión.</p>
            <p>Puede acceder al sistema en: <a href='{$base_url}login.php'>{$base_url}login.php</a></p>
            <p style='margin-top:20px;'>Atentamente,<br>Subdirección - Escuela de Informática<br>Universidad Nacional de Costa Rica</p>
            <p style='font-size:12px; color:#777;'>" . date('d/m/Y H:i') . "</p>
        </div>
        </body></html>";

        $headers = "From: no-reply@una.cr\r\n";
        $headers .= "Reply-To: no-reply@una.cr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";

        @mail($solicitud['email'], $subject, $message_body, $headers);

        // ============================
        // NOTIFICAR A LA CTFG (rol 3)
        // ============================
        try {
            $stmt_ctfg = $conn->prepare("SELECT u.email, u.nombre FROM sis_user u 
                                          INNER JOIN sis_login l ON u.id = l.id 
                                          WHERE l.id_roll = 3");
            $stmt_ctfg->execute();
            $result_ctfg = $stmt_ctfg->get_result();
            
            $subject_ctfg = 'Nuevo Asesor Externo Aprobado - SGPFL';
            $message_ctfg = "
            <html><head><meta charset='UTF-8'></head><body>
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                <p>Se ha aprobado un nuevo <strong>Asesor Externo</strong> y está disponible para asignación en proyectos TFG.</p>
                <h3 style='color: #034991;'>Datos del Asesor:</h3>
                <ul>
                    <li><strong>Nombre:</strong> {$solicitud['full_name']}</li>
                    <li><strong>Cédula:</strong> {$solicitud['applicant_id']}</li>
                    <li><strong>Email:</strong> {$solicitud['email']}</li>
                    <li><strong>Institución:</strong> {$solicitud['institution']}</li>
                    <li><strong>Especialización:</strong> {$solicitud['specialization']}</li>
                    <li><strong>Vigencia hasta:</strong> " . date('d/m/Y', strtotime($vigencia_default)) . "</li>
                </ul>
                <p>Este asesor ahora puede ser vinculado a proyectos TFG desde el panel de gestión.</p>
                <p style='margin-top:20px;'>Atentamente,<br>Sistema SGPFL</p>
                <p style='font-size:12px; color:#777;'>" . date('d/m/Y H:i') . "</p>
            </div>
            </body></html>";

            while ($ctfg_member = $result_ctfg->fetch_assoc()) {
                @mail($ctfg_member['email'], $subject_ctfg, $message_ctfg, $headers);
            }
            $stmt_ctfg->close();
            
            error_log("NOTIFICACIÓN CTFG: Asesor Externo {$solicitud['full_name']} aprobado y notificado a la CTFG.");
            
        } catch (Exception $e) {
            error_log("Error al notificar a CTFG: " . $e->getMessage());
        }
        
        error_log("APROBACIÓN EXITOSA: Asesor Externo {$solicitud['full_name']} ({$solicitud['applicant_id']}) - Usuario creado en sis_login y sis_user");

        echo json_encode([
            'success' => true, 
            'message' => "Solicitud aprobada. Se creó el usuario {$solicitud['applicant_id']} y se notificó a la CTFG."
        ]);

    } else {
        // ============================
        // RECHAZAR: Registrar motivo
        // ============================
        
        // Verificar límite de reintentos
        $current_rejections = intval($solicitud['rejection_count']);
        
        if ($current_rejections >= $MAX_REJECTION_ATTEMPTS) {
            throw new Exception("Esta solicitud ya alcanzó el límite máximo de {$MAX_REJECTION_ATTEMPTS} rechazos. No puede volver a enviarse.");
        }
        
        $new_rejection_count = $current_rejections + 1;
        $remaining_attempts = $MAX_REJECTION_ATTEMPTS - $new_rejection_count;
        
        // Actualizar estado de la solicitud
        $stmt_update = $conn->prepare("UPDATE external_advisor_profile_requests 
                                        SET status = 'Rechazado', 
                                            admin_comments = ?, 
                                            reviewed_by = ?, 
                                            reviewed_at = NOW(),
                                            rejection_count = ?
                                        WHERE id = ?");
        $stmt_update->bind_param('ssii', $comentarios, $current_user_id, $new_rejection_count, $id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();

        // ============================
        // ENVIAR CORREO DE RECHAZO
        // ============================
        $reintentos_msg = $remaining_attempts > 0 
            ? "<p>Puede corregir los documentos y volver a enviar su solicitud (<strong>{$remaining_attempts} intento(s) restante(s)</strong>).</p>"
            : "<p style='color: #dc3545;'><strong>Ha agotado todos los intentos de reenvío.</strong> Si desea registrarse como Asesor Externo, deberá contactar directamente a la Subdirección.</p>";
        
        $subject = 'Solicitud Rechazada - Asesor Externo SGPFL';
        $message_body = "
        <html><head><meta charset='UTF-8'></head><body>
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Estimado/a <strong>{$solicitud['full_name']}</strong>,</p>
            <p>Lamentamos informarle que su solicitud de registro como <strong>Asesor Externo</strong> ha sido <strong style='color: #dc3545;'>RECHAZADA</strong>.</p>
            <p><strong>Motivo:</strong></p>
            <blockquote style='border-left: 3px solid #dc3545; padding-left: 15px; color: #555;'>
                " . nl2br(htmlspecialchars($comentarios)) . "
            </blockquote>
            {$reintentos_msg}
            <p>Para reenviar su solicitud, visite: <a href='{$base_url}registro_asesor_externo.php'>{$base_url}registro_asesor_externo.php</a></p>
            <p style='margin-top:20px;'>Atentamente,<br>Subdirección - Escuela de Informática<br>Universidad Nacional de Costa Rica</p>
            <p style='font-size:12px; color:#777;'>" . date('d/m/Y H:i') . "</p>
        </div>
        </body></html>";

        $headers = "From: no-reply@una.cr\r\n";
        $headers .= "Reply-To: no-reply@una.cr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";

        @mail($solicitud['email'], $subject, $message_body, $headers);

        $msg = $remaining_attempts > 0 
            ? "Solicitud rechazada. El solicitante puede reenviar ({$remaining_attempts} intento(s) restante(s))."
            : "Solicitud rechazada definitivamente. El solicitante agotó todos los intentos.";

        echo json_encode([
            'success' => true, 
            'message' => $msg
        ]);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    error_log('Error en procesar_decision_asesor.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
