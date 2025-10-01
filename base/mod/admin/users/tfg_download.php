<?php
// filepath: c:\xampp\htdocs\SGPFL\base\mod\admin\users\tfg_download.php

// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

// OBTENER USUARIO REAL AUTENTICADO
$current_user_id = $mySessionController->getVar("usuario");
$current_user_name = $mySessionController->getVar("nombre");
$current_user_rol = $mySessionController->getVar("rol");

// Verificar autenticación
if (!$current_user_id) {
    http_response_code(401);
    die("Usuario no autenticado.");
}

// Log de acceso
error_log("TFG Download - Usuario: $current_user_id ($current_user_name), Rol: $current_user_rol");

include __DIR__ . '/../../../inc/db/bdcommon.inc';

// Conectar a la base de datos
$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die("Error de conexión: " . $conn->connect_error);
}

// Validar ID del archivo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die("Solicitud inválida. ID no proporcionado.");
}

// Consultar información del archivo con permisos
$sql = "SELECT id, title, proposal_file_path, user_id, user_name
        FROM tfg_proposals 
        WHERE id = ? AND proposal_file_path IS NOT NULL";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die("Error en la consulta.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die("Archivo no encontrado.");
}

$row = $result->fetch_assoc();
$stmt->close();

// VERIFICAR PERMISOS DE ACCESO
$can_download = false;

// El propietario siempre puede descargar
if ($row['user_id'] === $current_user_id) {
    $can_download = true;
    error_log("Acceso permitido: Propietario del archivo");
}
// Administradores y gestores pueden descargar todo
elseif ($current_user_rol == 1 || $current_user_rol == 2) {
    $can_download = true;
    error_log("Acceso permitido: Usuario con rol administrativo ($current_user_rol)");
}
// CTFG puede descargar para revisión
elseif ($current_user_rol == 3) {
    $can_download = true;
    error_log("Acceso permitido: Usuario CTFG para revisión");
}

if (!$can_download) {
    error_log("Acceso DENEGADO - Usuario: $current_user_id, Archivo propietario: " . $row['user_id']);
    http_response_code(403);
    die("No tienes permisos para descargar este archivo.");
}

$conn->close();

// Construir ruta completa del archivo
$base_path = __DIR__ . '/../../../';
$file_path = $base_path . $row['proposal_file_path'];

// Verificar que el archivo existe
if (!file_exists($file_path)) {
    error_log("Archivo físico no encontrado: " . $file_path);
    http_response_code(404);
    die("El archivo físico no existe en el servidor.");
}

// Obtener información del archivo
$file_size = filesize($file_path);
$file_name = basename($file_path);
$mime_type = mime_content_type($file_path);

// Log de descarga exitosa
error_log("Descarga exitosa - Usuario: $current_user_id, Archivo: " . $row['title']);

// Configurar headers para descarga
header('Content-Type: ' . ($mime_type ?: 'application/octet-stream'));
header('Content-Length: ' . (int)$file_size);
header('Content-Disposition: inline; filename="' . basename(str_replace('"', '', $file_name)) . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Leer y enviar el archivo
if ($file_size > 0) {
    $handle = fopen($file_path, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        http_response_code(500);
        die("Error al leer el archivo.");
    }
} else {
    http_response_code(404);
    die("El archivo está vacío.");
}
?>