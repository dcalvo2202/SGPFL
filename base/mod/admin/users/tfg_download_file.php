<?php
// Iniciar buffer de salida para capturar cualquier output no deseado
ob_start();

// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

try {
    // OBTENER USUARIO REAL AUTENTICADO
    $current_user_id = $mySessionController->getVar("usuario");
    $current_user_name = $mySessionController->getVar("nombre");
    $current_user_rol = $mySessionController->getVar("rol");

    if (!$current_user_id) {
        ob_end_clean(); // Limpiar buffer antes de enviar headers
        http_response_code(401);
        throw new Exception("Usuario no autenticado.");
    }

    include __DIR__ . '/../../../inc/db/bdcommon.inc';

    // Conectar a la base de datos
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        ob_end_clean();
        http_response_code(500);
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    function build_readable_tfg_file_name(string $original_name): string
    {
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $ext = 'pdf';
        }
        return 'Archivo_TFG_' . date('Ymd') . '.' . $ext;
    }

    // Validar ID del archivo
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        ob_end_clean();
        http_response_code(400);
        throw new Exception("Solicitud inválida. ID no proporcionado.");
    }

    // Consultar información del archivo y permisos
    $sql = "SELECT id, uploaded_by, file_name, mime_type, file_size, file_data FROM tfg_files WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        ob_end_clean();
        http_response_code(500);
        throw new Exception("Error en la consulta.");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        ob_end_clean();
        http_response_code(404);
        throw new Exception("Archivo no encontrado.");
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (empty($row['file_data'])) {
        ob_end_clean();
        http_response_code(404);
        throw new Exception("El archivo no existe en la base de datos.");
    }

    // LIMPIAR TODO EL BUFFER ANTES DE ENVIAR HEADERS
    ob_end_clean();
    
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Configurar headers para descarga
    $readable_name = build_readable_tfg_file_name($row['file_name'] ?? 'documento');
    header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . (int)$row['file_size']);
    header('Content-Disposition: attachment; filename="' . $readable_name . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Enviar el contenido del archivo BLOB
    echo $row['file_data'];
    exit;

} catch (Exception $e) {
    // Limpiar buffer en caso de error
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit;
}
?>