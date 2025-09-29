<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
<<<<<<< HEAD
    http_response_code(503); // Service Unavailable
    die("Error de conexión con el servicio.");
}

// COMENTAR O ELIMINAR ESTA LÍNEA EN PRODuCCIÓN.
$user_id = $_SESSION['id'] ?? 'estudiante001'; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400); // Bad Request
=======
    http_response_code(503);
    die("Error de conexión con el servicio.");
}

$user_id = $_SESSION['id'] ?? '112170040'; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
>>>>>>> HU-002
    die("Solicitud inválida. ID no proporcionado.");
}

$sql = "SELECT file_name, mime_type, file_size, document FROM tfg_proposals WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
<<<<<<< HEAD
    http_response_code(500); // Internal Server Error
=======
    http_response_code(500);
>>>>>>> HU-002
    die("Error interno al preparar la consulta.");
}

$stmt->bind_param("is", $id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
<<<<<<< HEAD
    http_response_code(404); // Not Found
=======
    http_response_code(404);
>>>>>>> HU-002
    die("Archivo no encontrado o no tienes permiso para acceder a él.");
}

$stmt->bind_result($file_name, $mime_type, $file_size, $document);
$stmt->fetch();
$stmt->close();
$conn->close();

<<<<<<< HEAD
// Enviar los headers correctos para la descarga/visualización
header('Content-Type: ' . ($mime_type ?: 'application/octet-stream'));
header('Content-Length: ' . (int)$file_size);
header('Content-Disposition: inline; filename="' . basename(str_replace('"', '', $file_name)) . '"');
=======
// Limpiar el buffer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Headers mejorados para mejor visualización
header('Content-Type: ' . ($mime_type ?: 'application/octet-stream'));
header('Content-Length: ' . (int)$file_size);

// Para PDFs, usar inline para que se vean en el navegador
if ($mime_type === 'application/pdf') {
    header('Content-Disposition: inline; filename="' . basename(str_replace('"', '', $file_name)) . '"');
} else {
    header('Content-Disposition: attachment; filename="' . basename(str_replace('"', '', $file_name)) . '"');
}

header('Cache-Control: private, max-age=3600');
header('Pragma: cache');
>>>>>>> HU-002

// Enviar el contenido del archivo
echo $document;
exit;
?>