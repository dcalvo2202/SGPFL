<?php
/**
 * HU-012: Descarga de documentos de solicitud de Asesor Externo
 * 
 * Permite descargar el CV o cédula de una solicitud pendiente
 * Solo accesible por Gestor Académico (2) o Administrador (1)
 */

// Cargar sesión
include_once __DIR__ . '/lib/mysession/mySession.class.php';
include_once __DIR__ . '/lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");
$current_user_rol = $mySessionController->getVar("rol");

// Control de acceso
if (!$current_user_id || ($current_user_rol != 2 && $current_user_rol != 1)) {
    http_response_code(403);
    die('Acceso denegado.');
}

// Cargar BD
include_once __DIR__ . '/inc/db/bdcommon.inc';

// Validar parámetros
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($id <= 0 || !in_array($type, ['cv', 'id_copy'])) {
    http_response_code(400);
    die('Parámetros inválidos.');
}

try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión.');
    }
    $conn->set_charset('utf8');

    // Determinar columnas según tipo
    if ($type === 'cv') {
        $col_data = 'cv_document';
        $col_name = 'cv_file_name';
        $col_mime = 'cv_mime_type';
        $col_size = 'cv_file_size';
    } else {
        $col_data = 'id_copy_document';
        $col_name = 'id_copy_file_name';
        $col_mime = 'id_copy_mime_type';
        $col_size = 'id_copy_file_size';
    }

    $sql = "SELECT {$col_data} AS file_data, {$col_name} AS file_name, {$col_mime} AS mime_type, {$col_size} AS file_size 
            FROM external_advisor_profile_requests WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Documento no encontrado.');
    }

    $doc = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    // Enviar archivo
    header('Content-Type: ' . $doc['mime_type']);
    header('Content-Disposition: inline; filename="' . $doc['file_name'] . '"');
    header('Content-Length: ' . $doc['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    echo $doc['file_data'];
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en descargar_documento_asesor.php: ' . $e->getMessage());
    die('Error al descargar el documento.');
}
