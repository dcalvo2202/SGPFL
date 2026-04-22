<?php
/**
 * HU-012 / HU-041: Procesamiento de decisión sobre solicitudes de comité asesor.
 */

header('Content-Type: application/json');

include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';
include_once __DIR__ . '/inc/db/bdcommon.inc';
require_once __DIR__ . '/config.inc';
require_once __DIR__ . '/inc/hu041_committee_audit.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");

if (!$current_user_id || ($current_user_rol != 2 && $current_user_rol != 3 && $current_user_rol != 1)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$base_url = rtrim($cds_domain, '/') . '/' . trim($cds_locate, '/') . '/';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$decision = isset($_POST['decision']) ? trim($_POST['decision']) : '';
$comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de solicitud inválido.']);
    exit;
}
if (!in_array($decision, ['Aprobado', 'Rechazado'], true)) {
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

    $stmt = $conn->prepare("SELECT * FROM external_advisor_profile_requests WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('No se encontró la solicitud.');
    }
    $solicitud = $result->fetch_assoc();
    $stmt->close();

    $estado_actual = $solicitud['status'] ?? '';
    if (!in_array($estado_actual, ['En Revision', 'En Revisión'], true)) {
        throw new Exception('Esta solicitud ya fue procesada anteriormente (Estado actual: ' . $estado_actual . ').');
    }

    $conn->begin_transaction();

    if ($decision === 'Aprobado') {
        $postulation_type = $solicitud['postulation_type'] ?? 'Asesor Externo';
        $temp_password = '';
        $linked_students_info = [];
        $linked_student_id = trim((string)($solicitud['linked_student_id'] ?? ''));
        $login_exists = false;
        $user_exists = false;

        $check_stmt = $conn->prepare("SELECT id FROM sis_login WHERE id = ?");
        $check_stmt->bind_param('s', $solicitud['applicant_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $login_exists = ($check_result->num_rows > 0);
        $check_stmt->close();

        if (!$login_exists) {
            $temp_password = substr($solicitud['applicant_id'], 0, 4) . date('Y');
            $hashed_password = md5($temp_password);

            $rol_asesor = 5;
            $stmt_login = $conn->prepare("INSERT INTO sis_login (id, pass, id_roll) VALUES (?, ?, ?)");
            $stmt_login->bind_param('ssi', $solicitud['applicant_id'], $hashed_password, $rol_asesor);
            if (!$stmt_login->execute()) {
                throw new Exception('Error al crear credenciales de acceso: ' . $stmt_login->error);
            }
            $stmt_login->close();
        }

        $nombre_upper = strtoupper($solicitud['full_name']);
        $telefono = $solicitud['telefono'] ?: null;
        $id_tipo_tel = $solicitud['id_tipo_tel'] ?: null;

        $check_user_stmt = $conn->prepare("SELECT id FROM sis_user WHERE id = ?");
        $check_user_stmt->bind_param('s', $solicitud['applicant_id']);
        $check_user_stmt->execute();
        $check_user_result = $check_user_stmt->get_result();
        $user_exists = ($check_user_result->num_rows > 0);
        $check_user_stmt->close();

        if ($user_exists) {
            $stmt_user = $conn->prepare("UPDATE sis_user SET nombre = ?, email = ?, telefono = ?, id_tipo_tel = ? WHERE id = ?");
            $stmt_user->bind_param('sssss', $nombre_upper, $solicitud['email'], $telefono, $id_tipo_tel, $solicitud['applicant_id']);
        } else {
            $stmt_user = $conn->prepare("INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES (?, ?, ?, ?, ?)");
            $stmt_user->bind_param('sssss', $solicitud['applicant_id'], $nombre_upper, $solicitud['email'], $telefono, $id_tipo_tel);
        }
        if (!$stmt_user->execute()) {
            $user_action = $user_exists ? 'actualizar' : 'crear';
            throw new Exception('Error al ' . $user_action . ' perfil de usuario: ' . $stmt_user->error);
        }
        $stmt_user->close();

        if ($linked_student_id === '') {
            throw new Exception('La solicitud aprobada debe estar asociada a un estudiante.');
        }

        require_once __DIR__ . '/inc/student_functions.php';
        $link_result = linkAdvisorToGroupMembers($conn, $id, $linked_student_id);
        if (empty($link_result['success'])) {
            throw new Exception('No se pudo asociar la solicitud aprobada con el estudiante/grupo asignado.');
        }
        $linked_students_info = $link_result['group_members'] ?? [];

        $vigencia_default = date('Y-m-d H:i:s', strtotime('+1 year'));
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

        hu041_register_audit($conn, $current_user_id, 'REQUEST_APPROVED', 'solicitud_comite', $id, [
            'applicant_id' => $solicitud['applicant_id'],
            'postulation_type' => $postulation_type,
            'committee_role' => $solicitud['committee_role'] ?? null,
        ]);

        $conn->commit();

        try {
            require_once __DIR__ . '/inc/alert_functions.php';
            registerExternalAdvisorApprovedAlert(
                $conn,
                $solicitud['applicant_id'],
                $solicitud['full_name'],
                date('d/m/Y', strtotime($vigencia_default))
            );
            foreach ($linked_students_info as $estudiante) {
                registerAdvisorAssignedToStudentAlert(
                    $conn,
                    $estudiante['id'],
                    $solicitud['full_name'],
                    $solicitud['email']
                );
            }
        } catch (Exception $alertEx) {
            error_log("HU-041: Error registrando alerta (no crítico): " . $alertEx->getMessage());
        }

        $estudiantes_lista_html = '';
        if (!empty($linked_students_info)) {
            $estudiantes_lista_html = '<p><strong>Estudiantes asignados a su asesoría:</strong></p><ul>';
            foreach ($linked_students_info as $est) {
                $tipo = (($est['is_primary'] ?? 0) == 1) ? ' (Principal)' : '';
                $estudiantes_lista_html .= "<li>{$est['nombre']} - {$est['email']}{$tipo}</li>";
            }
            $estudiantes_lista_html .= '</ul>';
        }

        $credentials_html = '';
        if ($temp_password !== '') {
            $credentials_html = "<p><strong>Sus credenciales de acceso son:</strong></p>
            <ul>
                <li><strong>Usuario:</strong> {$solicitud['applicant_id']}</li>
                <li><strong>Contraseña temporal:</strong> {$temp_password}</li>
            </ul>";
        }

        $subject = 'Solicitud Aprobada - Comité Asesor SGPFL';
        $message_body = "
        <html><head><meta charset='UTF-8'></head><body>
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Estimado/a <strong>{$solicitud['full_name']}</strong>,</p>
            <p>Su solicitud para integrar comité asesor ha sido <strong style='color: #198754;'>APROBADA</strong>.</p>
            <p><strong>Rol aprobado:</strong> " . htmlspecialchars($solicitud['committee_role'] ?? '-') . "</p>
            {$credentials_html}
            {$estudiantes_lista_html}
            <p><strong>Vigencia de la aprobación:</strong> Hasta " . date('d/m/Y', strtotime($vigencia_default)) . " o hasta el cierre del TFG asignado.</p>
            " . ($temp_password !== '' ? '<p>Por seguridad, le recomendamos cambiar su contraseña después del primer inicio de sesión.</p>' : '') . "
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

        $linked_count = count($linked_students_info);
        $base_message = (!$login_exists)
            ? "Solicitud aprobada. Se creó el usuario {$solicitud['applicant_id']}."
            : "Solicitud aprobada. Se reutilizó el usuario existente {$solicitud['applicant_id']}.";
        $grupo_msg = $linked_count > 1
            ? " Se vinculó automáticamente con $linked_count estudiantes del grupo TFG."
            : ($linked_count === 1 ? " Se vinculó con el estudiante asignado." : "");

        echo json_encode([
            'success' => true,
            'message' => $base_message . $grupo_msg,
            'linked_students_count' => $linked_count,
        ]);
    } else {
        $current_rejections = intval($solicitud['rejection_count']);
        $new_rejection_count = $current_rejections + 1;

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

        hu041_register_audit($conn, $current_user_id, 'REQUEST_REJECTED', 'solicitud_comite', $id, [
            'applicant_id' => $solicitud['applicant_id'],
            'postulation_type' => $solicitud['postulation_type'] ?? null,
            'committee_role' => $solicitud['committee_role'] ?? null,
            'reason' => $comentarios,
        ]);

        $conn->commit();

        $reintentos_msg = "<p>Puede corregir los documentos y volver a enviar su solicitud cuando lo considere necesario.</p>";

        $subject = 'Solicitud Rechazada - Comité Asesor SGPFL';
        $message_body = "
        <html><head><meta charset='UTF-8'></head><body>
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Estimado/a <strong>{$solicitud['full_name']}</strong>,</p>
            <p>Su solicitud para integrar comité asesor ha sido <strong style='color: #dc3545;'>RECHAZADA</strong>.</p>
            <p><strong>Motivo:</strong></p>
            <blockquote style='border-left: 3px solid #dc3545; padding-left: 15px; color: #555;'>
                " . nl2br(htmlspecialchars($comentarios)) . "
            </blockquote>
            {$reintentos_msg}
            <p>Para reenviar su solicitud, visite: <a href='{$base_url}registro.php'>{$base_url}registro.php</a></p>
            <p style='margin-top:20px;'>Atentamente,<br>Subdirección - Escuela de Informática<br>Universidad Nacional de Costa Rica</p>
            <p style='font-size:12px; color:#777;'>" . date('d/m/Y H:i') . "</p>
        </div>
        </body></html>";

        $headers = "From: no-reply@una.cr\r\n";
        $headers .= "Reply-To: no-reply@una.cr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        @mail($solicitud['email'], $subject, $message_body, $headers);

        $msg = "Solicitud rechazada. El solicitante puede reenviar la solicitud cuando lo considere necesario.";

        echo json_encode([
            'success' => true,
            'message' => $msg,
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
