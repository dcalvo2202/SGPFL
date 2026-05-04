<?php
// ===============================
// CONTEXTO Y SEGURIDAD
// ===============================
header('Content-Type: application/json');

include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$reviewer_id = $mySessionController->getVar("usuario"); // Definido una sola vez
$user_rol = $mySessionController->getVar("rol");

// Incluir la configuración de la base de datos
include __DIR__ . '/../../../inc/db/bdcommon.inc';
require_once __DIR__ . '/../../../config.inc';

include_once(__DIR__ . '/../../../inc/tfg_final_functions.php');
require_once(__DIR__ . '/../../../inc/archive_functions.php');

// Verificar autenticación y rol (CTFG, gestor academico - rol 3, 2)
if (!$reviewer_id || ($user_rol != 3 && $user_rol != 2)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos insuficientes.']);
    exit;
}

// ===============================
// RECEPCIÓN Y VALIDACIÓN DE DATOS
// ===============================
$document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
// $reviewer_id = $_SESSION['usuario']; // Esta línea es redundante, se elimina

if ($document_id <= 0 || empty($new_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos. Faltan parámetros esenciales.']);
    exit;
}

if ($new_status === 'Correcciones Requeridas' && empty($comments)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Es obligatorio agregar comentarios al solicitar correcciones.']);
    exit;
}

$allowed_statuses = ['Aprobado para Defensa', 'Correcciones Requeridas'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El estado proporcionado no es válido.']);
    exit;
}

// ===============================
// LÓGICA DE BASE DE DATOS
// ===============================
try {
    // Usar bdcommon.inc para la conexión para evitar conflicto de variables
    include __DIR__ . '/../../../inc/db/bdcommon.inc';
    // Si se está ejecutando en un entorno de prueba, usar la conexión mockeada
    if (isset($GLOBALS['__mysqli_mock'])) {
        $conn = $GLOBALS['__mysqli_mock'];
    // Si no, crear una nueva conexión y trabajar normalmente
    } else {
        $conn = new mysqli($db_host, $usuario, $clave, $db);
    }
    if (property_exists($conn, 'connect_error') && $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // "Traducir" el estado del frontend al estado esperado por la BD
    $db_status = '';
    if ($new_status === 'Aprobado para Defensa') {
        $db_status = 'Aprobado';
    } elseif ($new_status === 'Correcciones Requeridas') {
        $db_status = 'Rechazado';
    } else {
        throw new Exception('Estado interno no válido.');
    }

    // Iniciar transacción
    $conn->begin_transaction();

    // 1. Obtener la información del documento y archivo actual
    $sql_current_doc = "SELECT fd.proposal_id, fd.file_id, fd.submitted_by, f.file_name, f.mime_type, f.file_size, f.file_data 
                        FROM tfg_final_documents fd
                        JOIN tfg_files f ON fd.file_id = f.id
                        WHERE fd.id = ?";
    $stmt_current = $conn->prepare($sql_current_doc);
    if (!$stmt_current) throw new Exception("Error preparando la consulta del documento actual: " . $conn->error);
    $stmt_current->bind_param("i", $document_id);
    $stmt_current->execute();
    $result_current = $stmt_current->get_result();
    $current_doc = $result_current ? $result_current->fetch_assoc() : null;
    if (!$current_doc) {
        throw new Exception("No se encontró el documento final original con ID " . $document_id);
    }
    $stmt_current->close();

    // 2. Determinar la nueva versión
    $sql_version = "SELECT MAX(version) AS max_version FROM tfg_files WHERE uploaded_by = ? AND document_type = 'Documento Final TFG'";
    $stmt_version = $conn->prepare($sql_version);
    if (!$stmt_version) throw new Exception("Error preparando la consulta de versión: " . $conn->error);
    $stmt_version->bind_param("s", $current_doc['submitted_by']);
    $stmt_version->execute();
    $result_version = $stmt_version->get_result()->fetch_assoc();
    $next_version = ($result_version['max_version'] ?? 0) + 1;
    $stmt_version->close();

    // 3. Actualizar tfg_final_documents para apuntar a la nueva versión y cambiar el estado
    $stmt_update = $conn->prepare("UPDATE tfg_final_documents SET status = ? WHERE id = ?");
    if (!$stmt_update) throw new Exception("Error preparando la actualización del documento: " . $conn->error);
    $stmt_update->bind_param("si", $db_status, $document_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el documento final: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 4. Obtener el contador actual de correcciones para este documento
    $sql_count = "SELECT COUNT(*) as count FROM tfg_document_reviews 
                  WHERE document_id = ? AND review_type = 'Revision CTFG'";
    $stmt_count = $conn->prepare($sql_count);
    if (!$stmt_count) throw new Exception("Error preparando consulta de conteo: " . $conn->error);
    $stmt_count->bind_param("i", $document_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result()->fetch_assoc();
    $corrections_count = $result_count['count'] ?? 0;
    $stmt_count->close();

    // 5. Insertar registro en tfg_document_reviews (HU-020)
    // Guardar historial de revisión con observaciones del CTFG
    $review_type = 'Revision CTFG';
    $new_corrections_count = $corrections_count + 1;
    
    $stmt_review = $conn->prepare("INSERT INTO tfg_document_reviews 
        (document_id, file_version, reviewer_id, review_type, status, corrections_summary, corrections_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_review) throw new Exception("Error preparando inserción de revisión: " . $conn->error);
    $stmt_review->bind_param("iissssi", $document_id, $next_version, $reviewer_id, $review_type, $db_status, $comments, $new_corrections_count);
    if (!$stmt_review->execute()) {
        throw new Exception("Error al guardar el historial de revisión: " . $stmt_review->error);
    }
    $stmt_review->close();

    // Si todo va bien, confirmar la transacción
    $conn->commit();
    
    // ===============================
    // HU-037: REGISTRAR ALERTA INTERNA
    // ===============================
    try {
        require_once __DIR__ . '/../../../inc/alert_functions.php';
        $student_user_id = $current_doc['submitted_by'];
        
        // Obtener título de la propuesta
        $sql_title = "SELECT title FROM tfg_proposals WHERE id = ?";
        $stmt_title = $conn->prepare($sql_title);
        $stmt_title->bind_param("i", $current_doc['proposal_id']);
        $stmt_title->execute();
        $title_result = $stmt_title->get_result()->fetch_assoc();
        $proposal_title = $title_result['title'] ?? 'Tu TFG';
        $stmt_title->close();

        // Obtener todos los miembros activos del proyecto (lider y miembros)
        $recipient_ids = [];
        $recipient_ids[$student_user_id] = true;

        $sql_project = "SELECT id FROM registered_projects WHERE tfg_proposal_id = ?";
        $stmt_project = $conn->prepare($sql_project);
        if ($stmt_project) {
            $stmt_project->bind_param("i", $current_doc['proposal_id']);
            $stmt_project->execute();
            $project_result = $stmt_project->get_result()->fetch_assoc();
            $project_id = $project_result['id'] ?? null;
            $stmt_project->close();

            if ($project_id) {
                $sql_members = "SELECT user_id FROM project_members WHERE project_id = ? AND status = 'Activo'";
                $stmt_members = $conn->prepare($sql_members);
                if ($stmt_members) {
                    $stmt_members->bind_param("i", $project_id);
                    $stmt_members->execute();
                    $members_result = $stmt_members->get_result();
                    while ($members_result && ($member = $members_result->fetch_assoc())) {
                        $recipient_ids[$member['user_id']] = true;
                    }
                    $stmt_members->close();
                }
            }
        }

        if ($db_status === 'Aprobado') {
            // Documento aprobado para defensa
            $subject = "¡Tu Documento Final ha sido Aprobado para Defensa!";
            $message = "Tu documento final de TFG \"$proposal_title\" ha sido aprobado y está listo para la defensa.";
            foreach (array_keys($recipient_ids) as $recipient_id) {
                registerAlert($conn, $recipient_id, $subject, $message, 'Documento Final', 'Alta', 'document', $document_id);
            }
        } else {
            // Correcciones requeridas
            foreach (array_keys($recipient_ids) as $recipient_id) {
                registerCorrectionRequestedAlert($conn, $recipient_id, $proposal_title, $document_id, $comments);
            }
        }
    } catch (Exception $alertEx) {
        error_log("HU-037: Error registrando alerta (no crítico): " . $alertEx->getMessage());
    }
    
    // ===============================
    // ENVIAR CORREO AL ESTUDIANTE (HU-020)
    // ===============================
    $email_sent = false;
    $email_error = '';
    
    try {
        error_log("HU-020: Iniciando envío de correo para documento $document_id, estado: $db_status");
        
        // Obtener información del estudiante
        $sql_student = "SELECT u.email, u.nombre, tp.title 
                        FROM sis_user u 
                        INNER JOIN tfg_proposals tp ON tp.user_id = u.id
                        WHERE u.id = ? AND tp.id = ?";
        $stmt_student = $conn->prepare($sql_student);
        if (!$stmt_student) {
            throw new Exception("Error preparando consulta estudiante: " . $conn->error);
        }
        $stmt_student->bind_param("si", $current_doc['submitted_by'], $current_doc['proposal_id']);
        $stmt_student->execute();
        $student_info = $stmt_student->get_result()->fetch_assoc();
        $stmt_student->close();
        
        error_log("HU-020: Datos estudiante - Email: " . ($student_info['email'] ?? 'NULL') . ", Nombre: " . ($student_info['nombre'] ?? 'NULL'));
        
        if ($student_info && !empty($student_info['email'])) {
            $student_email = $student_info['email'];
            $student_name = $student_info['nombre'];
            $project_title = $student_info['title'];
            
            // Configuración del correo
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: " . SYSTEM_EMAIL_FROM_NAME . " <" . SYSTEM_EMAIL_FROM . ">\r\n";
            $headers .= "Reply-To: " . SYSTEM_EMAIL_REPLY_TO . "\r\n";
            
            $subject = "Notificación de Revisión de Documento Final de TFG";
            $status_display = ($new_status === 'Aprobado para Defensa') ? 'Aprobado para Defensa' : 'Correcciones Requeridas';
            $comments_display = !empty($comments) ? $comments : 'No se proporcionaron comentarios adicionales.';
            
            $base_url = "https://localhost/base/";
            $historial_url = $base_url . "historial_documentos.php";
            
            $message_body = '
            <html>
            <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fafafa; }
                .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 13px; color: #555; }
                .footer img { width: 120px; vertical-align: middle; margin-right: 10px; }
                .footer td { vertical-align: top; }
                .divider { border-left: 2px solid #999; width: 1px; }
                a { color: #0056b3; text-decoration: none; }
                a:hover { text-decoration: underline; }
                .comments-box { background-color: #f0f0f0; border-left: 4px solid #0056b3; padding: 10px 15px; margin-top: 10px; }
                .status-approved { color: #198754; font-weight: bold; }
                .status-rejected { color: #dc3545; font-weight: bold; }
            </style>
            </head>
            <body>
            <div class="container">
                <p>Estimado/a <strong>' . htmlspecialchars($student_name) . '</strong>,</p>

                <p>Le informamos que la <strong>Comisión de Trabajos Finales de Graduación (CTFG)</strong> ha revisado su documento final.</p>

                <p><strong>Detalles de la revisión:</strong></p>
                <ul>
                    <li><strong>Proyecto:</strong> ' . htmlspecialchars($project_title) . '</li>
                    <li><strong>Fecha de revisión:</strong> ' . date("d/m/Y H:i") . '</li>
                    <li><strong>Resultado:</strong> <span class="' . ($db_status === 'Aprobado' ? 'status-approved' : 'status-rejected') . '">' . htmlspecialchars($status_display) . '</span></li>
                </ul>

                <p><strong>Comentarios de la comisión:</strong></p>
                <div class="comments-box">
                    <p>' . nl2br(htmlspecialchars($comments_display)) . '</p>
                </div>';
            
            // Si fue rechazado, indicar que puede subir correcciones
            if ($db_status === 'Rechazado') {
                $message_body .= '
                <p style="margin-top: 15px; padding: 10px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>Nota:</strong> Puede subir una versión corregida de su documento desde el panel de estudiante 
                    atendiendo las observaciones indicadas.
                </p>';
            } else {
                $message_body .= '
                <p style="margin-top: 15px; padding: 10px; background-color: #d4edda; border-left: 4px solid #198754;">
                    <strong>¡Felicidades!</strong> Su documento ha sido aprobado. 
                    Pronto recibirá información sobre los siguientes pasos para la defensa de su TFG.
                </p>';
            }
            
            $message_body .= '
                <p>Puede consultar el historial de su TFG ingresando al sistema:</p>
                <p><a href="' . htmlspecialchars($historial_url) . '">' . htmlspecialchars($historial_url) . '</a></p>

                <div class="footer">
                <table>
                    <tr>
                    <td><img src="' . rtrim($cds_domain ?? '', '/') . '/base/img/logo.webp" alt="Escuela de Informática" style="width:120px;"></td>
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
            
            // Enviar correo
            error_log("HU-020: Intentando enviar correo a: $student_email");
            error_log("HU-020: Subject: $subject");
            
            // Capturar errores de mail()
            $old_error_reporting = error_reporting(E_ALL);
            $mail_sent = mail($student_email, $subject, $message_body, $headers);
            error_reporting($old_error_reporting);
            
            $email_sent = $mail_sent;
            
            if ($mail_sent) {
                error_log("HU-020: ✓ Correo enviado exitosamente a: $student_email (Estado: $status_display)");
            } else {
                $email_error = error_get_last();
                error_log("HU-020: ✗ Error al enviar correo a: $student_email - " . ($email_error['message'] ?? 'Sin detalle de error'));
            }
        } else {
            error_log("HU-020: No se pudo obtener email del estudiante o está vacío");
        }
    } catch (Exception $mail_error) {
        $email_error = $mail_error->getMessage();
        error_log("HU-020: Excepción en envío de correo: " . $email_error);
    }
    
    // ===============================
    // HU-027: ARCHIVAR DOCUMENTO FINAL
    // ===============================
    $archived = false;
    $archive_message = '';
    
    if ($db_status === 'Aprobado') {
        // Primero archivar el documento final
        $doc_archive_result = archiveFinalDocument($conn, $document_id, $current_doc['proposal_id'], $reviewer_id);
        
        // Luego archivar la propuesta completa al histórico como "Concluido"
        $archive_result = archiveProposal($conn, $current_doc['proposal_id'], 'Concluido', $reviewer_id);
        $archived = $archive_result['success'];
        $archive_message = $archive_result['message'];
        
        if (!$archived) {
            error_log("HU-027 Warning: No se pudo archivar propuesta {$current_doc['proposal_id']}: " . $archive_message);
        }
        if (!$doc_archive_result['success']) {
            error_log("HU-027 Warning: No se pudo archivar documento final {$document_id}: " . $doc_archive_result['message']);
        }
    }
    
    // HU-027: Si fue RECHAZADO, archivar como "Cancelado" 
    // Nota: No eliminamos el registro físicamente para preservar auditoría y evitar conflictos de FK
    if ($db_status === 'Rechazado') {
        // Archivar el documento final rechazado
        $doc_archive_result = archiveFinalDocument($conn, $document_id, $current_doc['proposal_id'], $reviewer_id);
        
        if ($doc_archive_result['success']) {
            // El registro de tfg_final_documents se mantiene con status='Rechazado' para auditoría
            // El estudiante puede subir un nuevo documento que creará un nuevo registro en tfg_final_documents
            $archived = true;
            $archive_message = 'Documento archivado. El estudiante puede subir un nuevo documento final.';
            error_log("HU-027: Documento final $document_id rechazado y archivado. Estudiante puede subir nueva versión.");
        } else {
            error_log("HU-027 Warning: No se pudo archivar documento final rechazado {$document_id}: " . $doc_archive_result['message']);
        }
    }
    
    // ===============================
    // RESPUESTA
    // ===============================
    
    echo json_encode([
        'success' => true, 
        'message' => 'El estado del documento ha sido actualizado.' . ($archived ? ' El proyecto ha sido archivado en el histórico.' : ''),
        'document_id' => $document_id,
        'status' => $new_status, // Devolver el estado del frontend para el correo
        'comments' => $comments,
        'archived' => $archived,
        'archive_message' => $archive_message
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    http_response_code(500);
    error_log("Error en process_final_document_review.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la operación: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
