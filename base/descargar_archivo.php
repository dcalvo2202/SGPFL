<?php
/**
 * HU-027: Descarga de documentos desde el archivo histórico
 * 
 * Descomprime y entrega documentos archivados.
 * Registra el acceso para auditoría (Art. 68 RGPEA).
 */

include(dirname(__FILE__) . "/lib/mysession/mySession.class.php");
include(dirname(__FILE__) . "/lib/mysession/mySession.conf.php");
require_once('inc/constants.php');
require_once('inc/archive_functions.php');

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$rol = $mySessionController->getVar("rol");
$current_user_id = $mySessionController->getVar("usuario");

// Verificar autenticación
if (!$rol) {
    http_response_code(401);
    die('No autorizado');
}

// Verificar permisos: solo Admin, CTFG y Gestor Académico
$allowed_roles = [ROL_ADMIN, ROL_CTFG, ROL_GESTOR];

if (!in_array($rol, $allowed_roles)) {
    http_response_code(403);
    die('Acceso denegado');
}

// Validar parámetros
$type = $_GET['type'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || !in_array($type, ['proposal', 'file'])) {
    http_response_code(400);
    die('Parámetros inválidos');
}

// Conexión a base de datos (incluir después de variables de sesión)
$conn = new mysqli('localhost', 'root', '', 'base_db');
$conn->set_charset("utf8mb4");

try {
    if ($type === 'proposal') {
        // Descargar documento principal de propuesta archivada
        $doc = getArchivedDocument($conn, $id, $current_user_id);
        
        if (!$doc || empty($doc['document'])) {
            http_response_code(404);
            die('Documento no encontrado');
        }
        
        // Generar nombre de archivo basado en el título del proyecto
        $extension = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
        $safe_title = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]/u', '', $doc['title']);
        $safe_title = substr(trim($safe_title), 0, 100); // Limitar longitud
        $download_name = $safe_title . '.' . $extension;
        
        // Entregar archivo
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Length: ' . strlen($doc['document']));
        header('Cache-Control: no-cache, must-revalidate');
        
        echo $doc['document'];
        
    } elseif ($type === 'file') {
        // Descargar archivo adicional
        $stmt = $conn->prepare("
            SELECT file_data, file_name, mime_type, is_compressed
            FROM tfg_files_archive
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            die('Archivo no encontrado');
        }
        
        $file = $result->fetch_assoc();
        $stmt->close();
        
        // Registrar acceso
        logArchiveAccess($conn, $current_user_id, 'DOWNLOAD_FILE', null, null, "file_id: $id");
        
        // Descomprimir si es necesario
        $data = $file['file_data'];
        if ($file['is_compressed'] && !empty($data)) {
            $data = decompressData($data);
        }
        
        // Entregar archivo - usar nombre original del archivo
        $download_name = basename($file['file_name']);
        $download_name = preg_replace('/[^\w\-.áéíóúñÁÉÍÓÚÑ]/u', '_', $download_name);
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: no-cache, must-revalidate');
        
        echo $data;
    }
    
} catch (Exception $e) {
    error_log("Error descargando archivo archivado: " . $e->getMessage());
    http_response_code(500);
    die('Error al procesar la solicitud');
}

$conn->close();
