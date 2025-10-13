<?php
/**
 * Descarga de Documentos Finales de TFG
 * Permite a la CTFG y al estudiante propietario descargar el PDF
 */

// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

// OBTENER DATOS DEL USUARIO
$user_id = $mySessionController->getVar("usuario");
$user_rol = $mySessionController->getVar("rol");

// Verificar autenticación
if (!$user_id) {
    header('HTTP/1.0 401 Unauthorized');
    die('No autorizado');
}

// Validar parámetro ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('Parámetro inválido');
}

$document_id = intval($_GET['id']);

// Incluir configuración de BD
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $conn->set_charset("utf8");
    
    // Obtener información del documento y verificar permisos
    $sql = "SELECT 
                fd.id,
                fd.submitted_by,
                f.file_name,
                f.mime_type,
                f.file_size,
                f.file_data
            FROM tfg_final_documents fd
            INNER JOIN tfg_files f ON fd.file_id = f.id
            WHERE fd.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header('HTTP/1.0 404 Not Found');
        die('Documento no encontrado');
    }
    
    $document = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar permisos:
    // - CTFG (rol 3) puede descargar cualquier documento
    // - Estudiante (rol 4) solo puede descargar sus propios documentos
    // - Otros roles no tienen acceso
    
    $has_permission = false;
    
    if ($user_rol == 3) {
        // CTFG tiene acceso a todos los documentos
        $has_permission = true;
    } elseif ($user_rol == 4 && $document['submitted_by'] === $user_id) {
        // Estudiante puede descargar su propio documento
        $has_permission = true;
    }
    
    if (!$has_permission) {
        $conn->close();
        header('HTTP/1.0 403 Forbidden');
        die('No tienes permisos para descargar este documento');
    }
    
    // Preparar headers para descarga
    header('Content-Type: ' . $document['mime_type']);
    header('Content-Disposition: inline; filename="' . $document['file_name'] . '"');
    header('Content-Length: ' . $document['file_size']);
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar el contenido del archivo
    echo $document['file_data'];
    
    $conn->close();
    exit;
    
} catch (Exception $e) {
    error_log("Error en tfg_final_download.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    die('Error al procesar la descarga');
}
