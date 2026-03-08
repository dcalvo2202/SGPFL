<?php
// HU-020: Procesamiento de correcciones del documento TFG
header('Content-Type: application/json');

// IMPORTANTE: Incluir BD PRIMERO y guardar variables antes de cargar sesión
include __DIR__ . '/../../../inc/db/bdcommon.inc';
$db_user = $usuario;  // Guardar 'root'
$db_pass = $clave;    // Guardar ''
$db_name = $db;       // Guardar 'base_db'

include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");
$user_rol = $mySessionController->getVar("rol");

// Verificar autenticación y rol (Estudiante - rol 4)
if (!$current_user_id || $user_rol != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo estudiantes pueden subir correcciones.']);
    exit;
}

// ===============================
// VALIDACIÓN DE DATOS
// ===============================
$document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
$corrections_summary = isset($_POST['corrections_summary']) ? trim($_POST['corrections_summary']) : '';
$all_addressed = isset($_POST['all_addressed']) ? true : false;

if ($document_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de documento inválido.']);
    exit;
}

if (empty($corrections_summary)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El resumen de correcciones es obligatorio.']);
    exit;
}

// Validar límite de 500 palabras
$word_count = str_word_count($corrections_summary);
if ($word_count > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "El resumen excede el límite de 500 palabras (actualmente: $word_count palabras)."]);
    exit;
}

if (!$all_addressed) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe confirmar que ha atendido todas las observaciones.']);
    exit;
}

// Validar archivos PDF (múltiples o singular)
include_once __DIR__ . '/../../../inc/upload_helpers.php';

$uploaded_files = [];
$file = null;
$mime_type = '';

// Nuevo formato: múltiples archivos con documents[]
if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
    $files_count = count($_FILES['documents']['name']);
    $max_size = 10 * 1024 * 1024; // 10 MB por archivo
    $allowed_mime = ['application/pdf'];
    
    for ($i = 0; $i < $files_count; $i++) {
        if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['documents']['tmp_name'][$i];
            $fname = $_FILES['documents']['name'][$i];
            $fsize = $_FILES['documents']['size'][$i];
            
            if ($fsize > $max_size) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El archivo \"$fname\" excede el tamaño máximo de 10 MB."]);
                exit;
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fmime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            
            if (!in_array($fmime, $allowed_mime)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "El archivo \"$fname\" no es un PDF válido."]);
                exit;
            }
            
            $uploaded_files[] = [
                'name' => $fname,
                'type' => $fmime,
                'size' => $fsize,
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK
            ];
        } elseif ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Error al subir uno de los archivos.']);
            exit;
        }
    }
}
// Compatibilidad: formato antiguo con un solo archivo (name="document")
elseif (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
    $max_size = 10 * 1024 * 1024;
    if ($_FILES['document']['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El archivo excede el tamaño máximo de 10 MB.']);
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fmime = finfo_file($finfo, $_FILES['document']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($fmime, ['application/pdf'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
        exit;
    }
    $uploaded_files[] = [
        'name' => $_FILES['document']['name'],
        'type' => $fmime,
        'size' => $_FILES['document']['size'],
        'tmp_name' => $_FILES['document']['tmp_name'],
        'error' => UPLOAD_ERR_OK
    ];
}

if (empty($uploaded_files)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Por favor intente nuevamente.']);
    exit;
}

// Usar el primer archivo como documento principal
$file = $uploaded_files[0];
$mime_type = $file['type'];

// ===============================
// PROCESAMIENTO EN BD
// ===============================
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    // 1. Verificar que el documento pertenece al estudiante y está rechazado
    $sql_verify = "SELECT fd.id, fd.proposal_id, fd.status, fd.file_id, fd.submitted_by,
                          f.file_name, f.mime_type, f.file_size, f.version
                   FROM tfg_final_documents fd
                   INNER JOIN tfg_files f ON fd.file_id = f.id
                   WHERE fd.id = ? AND fd.submitted_by = ? AND fd.status = 'Rechazado'";
    
    $stmt_verify = $conn->prepare($sql_verify);
    if (!$stmt_verify) throw new Exception("Error preparando verificación: " . $conn->error);
    $stmt_verify->bind_param("is", $document_id, $current_user_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    
    if ($result_verify->num_rows === 0) {
        throw new Exception("No se encontró el documento o no tiene permisos para editarlo.");
    }
    
    $doc_data = $result_verify->fetch_assoc();
    $stmt_verify->close();
    
    // 2. Verificar límite de correcciones (máximo 3 intentos por defecto)
    $max_corrections = 3;
    $sql_count = "SELECT COUNT(*) as count FROM tfg_document_reviews 
                  WHERE document_id = ? AND review_type = 'Correccion Estudiante'";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $document_id);
    $stmt_count->execute();
    $corrections_count = $stmt_count->get_result()->fetch_assoc()['count'];
    $stmt_count->close();
    
    if ($corrections_count >= $max_corrections) {
        throw new Exception("Ha alcanzado el límite máximo de $max_corrections correcciones. Contacte a la CTFG.");
    }
    
    // 3. Obtener la siguiente versión
    $next_version = $doc_data['version'] + 1;
    
    // 4. Leer el archivo PDF principal
    $file_content = file_get_contents($file['tmp_name']);
    if ($file_content === false) {
        throw new Exception("Error al leer el archivo PDF.");
    }
    
    $file_name_val = $file['name'];
    $file_size_val = $file['size'];
    
    // 5. Insertar nueva versión en tfg_files
    $sql_file = "INSERT INTO tfg_files 
                 (file_name, mime_type, file_size, file_data, storage_path, uploaded_by, version, document_type) 
                 VALUES (?, ?, ?, ?, NULL, ?, ?, 'Documento Final TFG')";
    
    $stmt_file = $conn->prepare($sql_file);
    if (!$stmt_file) throw new Exception("Error preparando inserción de archivo: " . $conn->error);
    
    // Bind parameters: s=string, i=int, b=blob, d=double (para version que es float)
    $null_blob = null;
    $doc_type_label = 'Documento Final TFG';
    $stmt_file->bind_param("ssibsds", 
        $file_name_val,
        $mime_type,
        $file_size_val,
        $null_blob,
        $current_user_id,
        $next_version,
        $doc_type_label
    );
    
    // Enviar el contenido del BLOB (índice 3 = 4º parámetro, basado en 0)
    $stmt_file->send_long_data(3, $file_content);
    
    if (!$stmt_file->execute()) {
        throw new Exception("Error al guardar el archivo: " . $stmt_file->error);
    }
    
    $new_file_id = $conn->insert_id;
    $stmt_file->close();
    
    // 6. Actualizar tfg_final_documents con el nuevo file_id y cambiar status a "Pendiente de Revision"
    $new_status = 'Pendiente de Revision';
    $sql_update_doc = "UPDATE tfg_final_documents 
                       SET file_id = ?, status = ?, submitted_at = NOW() 
                       WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql_update_doc);
    if (!$stmt_update) throw new Exception("Error preparando actualización: " . $conn->error);
    $stmt_update->bind_param("isi", $new_file_id, $new_status, $document_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el documento: " . $stmt_update->error);
    }
    $stmt_update->close();
    
    // 6.5 Guardar archivos adicionales si hay más de uno
    if (count($uploaded_files) > 1) {
        $additional_saved = saveAdditionalFiles(
            $conn,
            $uploaded_files,
            $current_user_id,
            'Correccion TFG Anexo'
        );
        error_log("HU-020: Archivos adicionales de corrección guardados: $additional_saved");
    }
    
    // 7. Insertar registro en tfg_document_reviews (respuesta del estudiante)
    $review_type = 'Correccion Estudiante';
    $sql_review = "INSERT INTO tfg_document_reviews 
                   (document_id, file_version, reviewer_id, review_type, status, corrections_summary, corrections_count) 
                   VALUES (?, ?, NULL, ?, ?, ?, ?)";
    
    $stmt_review = $conn->prepare($sql_review);
    if (!$stmt_review) throw new Exception("Error preparando inserción de revisión: " . $conn->error);
    
    $new_corrections_count = $corrections_count + 1;
    $stmt_review->bind_param("iisssi", 
        $document_id, 
        $next_version, 
        $review_type, 
        $new_status, 
        $corrections_summary,
        $new_corrections_count
    );
    
    if (!$stmt_review->execute()) {
        throw new Exception("Error al guardar el historial de corrección: " . $stmt_review->error);
    }
    $stmt_review->close();
    
    // Confirmar transacción
    $conn->commit();
    
    // ===============================
    // HU-037: REGISTRAR ALERTA INTERNA
    // ===============================
    try {
        require_once __DIR__ . '/../../../inc/alert_functions.php';
        
        // Obtener nombre del estudiante y título para la alerta
        $sql_info = "SELECT u.nombre, tp.title 
                     FROM sis_user u 
                     INNER JOIN tfg_proposals tp ON tp.user_id = u.id
                     WHERE u.id = ? AND tp.id = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("si", $current_user_id, $current_doc['proposal_id']);
        $stmt_info->execute();
        $info_result = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();
        
        if ($info_result) {
            registerCorrectionSubmittedAlert(
                $conn, 
                $info_result['nombre'], 
                $info_result['title'], 
                $document_id
            );
        }
    } catch (Exception $alertEx) {
        error_log("HU-037: Error registrando alerta (no crítico): " . $alertEx->getMessage());
    }
    
    $conn->close();
    
    // ===============================
    // ENVIAR NOTIFICACIÓN A CTFG (HU-020)
    // ===============================
    try {
        // Obtener información adicional para el email
        $conn_mail = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $conn_mail->set_charset("utf8");
        
        $sql_mail = "SELECT tp.title, u.nombre, u.email 
                     FROM tfg_final_documents fd
                     INNER JOIN tfg_proposals tp ON fd.proposal_id = tp.id
                     INNER JOIN sis_user u ON fd.submitted_by = u.id
                     WHERE fd.id = ?";
        
        $stmt_mail = $conn_mail->prepare($sql_mail);
        $stmt_mail->bind_param("i", $document_id);
        $stmt_mail->execute();
        $mail_data = $stmt_mail->get_result()->fetch_assoc();
        $stmt_mail->close();
        $conn_mail->close();
        
        // Aquí podrías integrar con send_tfg_mail.php o enviar email directamente
        // Por ahora solo loguear la notificación
        error_log("NOTIFICACIÓN CTFG: Estudiante {$mail_data['nombre']} subió corrección #{$new_corrections_count} del documento '{$mail_data['title']}' (versión {$next_version})");
        
    } catch (Exception $e) {
        // No fallar si el email falla, solo logear
        error_log("Error al enviar notificación: " . $e->getMessage());
    }
    
    // ===============================
    // RESPUESTA EXITOSA
    // ===============================
    echo json_encode([
        'success' => true,
        'message' => 'Correcciones enviadas exitosamente. La CTFG será notificada.',
        'document_id' => $document_id,
        'version' => $next_version,
        'corrections_count' => $new_corrections_count
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        $conn->close();
    }
    
    http_response_code(500);
    error_log("Error en tfg_upload_correction_process.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
