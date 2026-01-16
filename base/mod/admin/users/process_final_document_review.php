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
    // RESPUESTA
    // ===============================
    
    echo json_encode([
        'success' => true, 
        'message' => 'El estado del documento ha sido actualizado.',
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
