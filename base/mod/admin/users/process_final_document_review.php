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

include_once(__DIR__ . '/../../../inc/tfg_final_functions.php');

// Verificar autenticación y rol (CTFG - rol 3)
if (!$reviewer_id || $user_rol != 3) {
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
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
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
    if ($result_current->num_rows === 0) {
        throw new Exception("No se encontró el documento final original con ID " . $document_id);
    }
    $current_doc = $result_current->fetch_assoc();
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

    // 3. Insertar la nueva versión en tfg_files
    $sql_new_file = "INSERT INTO tfg_files (file_name, mime_type, file_size, file_data, storage_path, uploaded_by, version, document_type) 
                     VALUES (?, ?, ?, ?, NULL, ?, ?, 'Documento Final TFG')";
    $stmt_new_file = $conn->prepare($sql_new_file);
    if (!$stmt_new_file) throw new Exception("Error preparando la inserción del nuevo archivo: " . $conn->error);
    
    $null_data = null;
    $stmt_new_file->bind_param("ssibsi", 
        $current_doc['file_name'], 
        $current_doc['mime_type'], 
        $current_doc['file_size'], 
        $null_data,
        $current_doc['submitted_by'], 
        $next_version
    );
    $stmt_new_file->send_long_data(3, $current_doc['file_data']);
    if (!$stmt_new_file->execute()) {
        throw new Exception("Error al crear la nueva versión del archivo: " . $stmt_new_file->error);
    }
    $new_file_id = $conn->insert_id;
    $stmt_new_file->close();

    // 4. Actualizar tfg_final_documents para apuntar a la nueva versión y cambiar el estado
    $stmt_update = $conn->prepare("UPDATE tfg_final_documents SET status = ?, file_id = ? WHERE id = ?");
    if (!$stmt_update) throw new Exception("Error preparando la actualización del documento: " . $conn->error);
    $stmt_update->bind_param("sii", $db_status, $new_file_id, $document_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el documento final: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 5. Insertar registro en tfg_document_reviews (HU-020)
    // Guardar historial de revisión con observaciones
    $review_type = 'Revision CTFG';
    $stmt_review = $conn->prepare("INSERT INTO tfg_document_reviews 
        (document_id, file_version, reviewer_id, review_type, status, observations, corrections_count) 
        VALUES (?, ?, ?, ?, ?, ?, 0)");
    if (!$stmt_review) throw new Exception("Error preparando inserción de revisión: " . $conn->error);
    $stmt_review->bind_param("iissss", $document_id, $next_version, $reviewer_id, $review_type, $db_status, $comments);
    if (!$stmt_review->execute()) {
        throw new Exception("Error al guardar el historial de revisión: " . $stmt_review->error);
    }
    $stmt_review->close();

    // Si todo va bien, confirmar la transacción
    $conn->commit();
    
    // ===============================
    // RESPUESTA
    // ===============================
    
    echo json_encode([
        'success' => true, 
        'message' => 'El estado del documento ha sido actualizado y se ha creado una nueva versión.',
        'document_id' => $document_id,
        'status' => $new_status, // Devolver el estado del frontend para el correo
        'comments' => $comments
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
