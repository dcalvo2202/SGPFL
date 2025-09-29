<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../../../inc/db/bdcommon.inc';

// --- Validación de Entrada (simplificada para el ejemplo) ---
$title = trim($_POST['title'] ?? '');
$disciplines = trim($_POST['disciplines'] ?? '');
if ($title === '' || $disciplines === '') die("Datos incompletos.");
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) die("Error al subir el archivo.");

$conn = new mysqli($db_host, $usuario, $clave, $db);
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

// COMENTAR O ELIMINAR ESTA LÍNEA EN PRODUCCIÓN.
$user_id = $_SESSION['id'] ?? 'estudiante001';

// --- Lectura de Archivo ---
$tmp_path = $_FILES['document']['tmp_name'];
$fileContent = file_get_contents($tmp_path);
if ($fileContent === false) die("No se pudo leer el archivo temporal.");
$file_name = basename($_FILES['document']['name']);
$mime_type = mime_content_type($tmp_path);
$file_size = (int)$_FILES['document']['size'];

/*
// --- Inserción Segura en la Base de Datos ---
$sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, document, file_name, mime_type, file_size, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Revisión')";
        
$stmt = $conn->prepare($sql);
// Parche aplicado: Verificar que la preparación fue exitosa.
if (!$stmt) {
    $err = $conn->error;
    $conn->close();
    die("Error al preparar la inserción: " . $err);
}

$null = NULL; // Variable para bind_param
$stmt->bind_param("sssbssi", $user_id, $title, $disciplines, $null, $file_name, $mime_type, $file_size);
$stmt->send_long_data(3, $fileContent);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    $target = '../../../panel_estudiante.php'; // Fallback
    if (isset($base_url) && $base_url !== '') {
        $target = rtrim($base_url, '/') . '/panel_estudiante.php';
    }
    echo '<script>alert("¡Propuesta enviada correctamente!"); window.location.href = "'.$target.'";</script>';
    exit;
} else {
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    die("Error al guardar la propuesta: " . $err);
}
*/
try {
    // Iniciar transacción
    $conn->begin_transaction();

    // 1. Insertar en tfg_proposals
    $sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, document, file_name, mime_type, file_size, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Revisión')";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la inserción: " . $conn->error);
    }

    $null = NULL;
    $stmt->bind_param("sssbssi", $user_id, $title, $disciplines, $null, $file_name, $mime_type, $file_size);
    $stmt->send_long_data(3, $fileContent);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar la propuesta: " . $stmt->error);
    }

    $proposal_id = $conn->insert_id;
    $stmt->close();

    // 2. Insertar en tfg_proposal_history
    $sql = "INSERT INTO tfg_proposal_history (proposal_id, document, file_name, mime_type, file_size, status)
            VALUES (?, ?, ?, ?, ?, 'Pendiente de Revisión')";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar el historial: " . $conn->error);
    }

    $stmt->bind_param("ibssi", $proposal_id, $null, $file_name, $mime_type, $file_size);
    $stmt->send_long_data(1, $fileContent);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el historial: " . $stmt->error);
    }

    // Confirmar transacción
    $conn->commit();

    // Redireccionar
    $target = '../../../panel_subir_propuesta_tfg.php'; // Fallback
    if (isset($base_url) && $base_url !== '') {
        $target = rtrim($base_url, '/') . '/panel_subir_propuesta_tfg.php';
    }
    echo '<script>alert("¡Propuesta enviada correctamente!"); window.location.href = "'.$target.'";</script>';
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>