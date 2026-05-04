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
    http_response_code(401);
    die('No autorizado');
}

// Validar parámetro ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
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

    function build_readable_final_name(string $title, string $original_extension = 'pdf'): string
    {
        $clean_title = preg_replace('/[^\w\s\-áéíóúñÁÉÍÓÚÑ]/u', '', $title);
        $clean_title = preg_replace('/\s+/', '_', trim($clean_title));
        $clean_title = mb_substr($clean_title, 0, 40);
        $ext = $original_extension ?: 'pdf';
        return 'Trabajo_Final_' . $clean_title . '.' . $ext;
    }

    // Obtener información del documento y verificar permisos
    $sql = "SELECT 
                fd.id,
                fd.submitted_by,
                fd.proposal_id,
                p.title AS proposal_title,
                f.file_name,
                f.mime_type,
                f.file_size,
                f.file_data
            FROM tfg_final_documents fd
            INNER JOIN tfg_files f ON fd.file_id = f.id
            LEFT JOIN tfg_proposals p ON fd.proposal_id = p.id
            WHERE fd.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        http_response_code(404);
        die('Documento no encontrado');
    }

    $document = $result->fetch_assoc();
    $stmt->close();

    // Verificar permisos:
    // - CTFG (rol 3) puede descargar cualquier documento
    // - Estudiante (rol 4) solo puede descargar sus propios documentos
    $has_permission = false;
    if ($user_rol == 3) {
        $has_permission = true;
    } elseif ($user_rol == 4 && $document['submitted_by'] === $user_id) {
        $has_permission = true;
    }

    if (!$has_permission) {
        $conn->close();
        http_response_code(403);
        die('No tienes permisos para descargar este documento');
    }

    // Verificar que el archivo existe en la base de datos
    if (empty($document['file_data'])) {
        $conn->close();
        http_response_code(404);
        die("El archivo no existe en la base de datos.");
    }

    // Preparar headers para descarga (forzar descarga)
    $original_ext = pathinfo($document['file_name'] ?? '', PATHINFO_EXTENSION);
    $readable_name = build_readable_final_name($document['proposal_title'] ?? 'Documento', $original_ext);
    header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . (int)$document['file_size']);
    header('Content-Disposition: attachment; filename="' . $readable_name . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    // Enviar el contenido del archivo
    echo $document['file_data'];

    $conn->close();
    exit;

} catch (Exception $e) {
    error_log("Error en tfg_final_download.php: " . $e->getMessage());
    http_response_code(500);
    die('Error al procesar la descarga');
}
?>