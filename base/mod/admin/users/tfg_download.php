<?php
// VERIFICAR AUTENTICACIÓN
include("../../login/check.php");

try {
    // OBTENER USUARIO REAL AUTENTICADO
    $current_user_id = $mySessionController->getVar("usuario");
    $current_user_name = $mySessionController->getVar("nombre");
    $current_user_rol = $mySessionController->getVar("rol");

    if (!$current_user_id) {
        http_response_code(401);
        throw new Exception("Usuario no autenticado.");
    }

    include __DIR__ . '/../../../inc/db/bdcommon.inc';

    // Conectar a la base de datos
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        http_response_code(500);
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

function build_readable_proposal_name(string $title, string $original_extension = 'pdf'): string
{
    $clean_title = preg_replace('/[^\w\s\-áéíóúñÁÉÍÓÚÑ]/u', '', $title);
    $clean_title = preg_replace('/\s+/', '_', trim($clean_title));
    $clean_title = mb_substr($clean_title, 0, 40);
    $ext = $original_extension ?: 'pdf';
    return 'Propuesta_TFG_' . $clean_title . '.' . $ext;
}

    // Validar ID del archivo
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        throw new Exception("Solicitud inválida. ID no proporcionado.");
    }

    // Consultar información del archivo y permisos
    $sql = "SELECT id, user_id, title, file_name, mime_type, file_size, document FROM tfg_proposals WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        throw new Exception("Error en la consulta.");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        throw new Exception("Archivo no encontrado.");
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    // VERIFICAR PERMISOS DE ACCESO
    $can_download = false;
    if ($row['user_id'] === $current_user_id) {
        // Dueño de la propuesta
        $can_download = true;
    } elseif (in_array($current_user_rol, [1, 2, 3, 5])) {
        // Admin, Gestor, CTFG, Asesor externo/interno
        $can_download = true;
    } elseif ($current_user_rol == 4) {
        // Estudiante miembro del grupo: verificar via project_members
        $stmt_pm = $conn->prepare(
            "SELECT 1 FROM registered_projects rp
             INNER JOIN project_members pm ON pm.project_id = rp.id
             WHERE rp.tfg_proposal_id = ? AND pm.user_id = ? AND pm.status = 'Activo'
             LIMIT 1"
        );
        if ($stmt_pm) {
            $stmt_pm->bind_param("is", $id, $current_user_id);
            $stmt_pm->execute();
            $can_download = $stmt_pm->get_result()->num_rows > 0;
            $stmt_pm->close();
        }
    }

    if (!$can_download) {
        http_response_code(403);
        throw new Exception("No tienes permisos para descargar este archivo.");
    }

    $conn->close();

    if (empty($row['document'])) {
        http_response_code(404);
        throw new Exception("El archivo no existe en la base de datos.");
    }

    // Configurar headers para descarga
    $original_ext = pathinfo($row['file_name'] ?? '', PATHINFO_EXTENSION);
    $readable_name = build_readable_proposal_name($row['title'] ?? 'Propuesta', $original_ext);
    header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . (int)$row['file_size']);
    header('Content-Disposition: attachment; filename="' . $readable_name . '"');
    header('Cache-Control: no-cache, must-revalidate');
    // header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    // Enviar el contenido del archivo BLOB
    echo $row['document'];
    exit;

} catch (Exception $e) {
    // Puedes personalizar el mensaje de error aquí
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit;
}
?>