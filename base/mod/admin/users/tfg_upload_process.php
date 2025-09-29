<?php
<<<<<<< HEAD
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
=======
// Activar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Log de debug
error_log("=== INICIO PROCESAMIENTO TFG ===");
error_log("POST: " . print_r($_POST, true));
error_log("FILES: " . print_r($_FILES, true));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Rutas
$base_path = realpath(__DIR__ . '/../../../');
include $base_path . '/inc/db/bdcommon.inc';

// Usuario actual
$user_id = $_SESSION['id'] ?? '112170040';
error_log("Usuario actual: $user_id");

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

// Obtener datos del formulario
$title = trim($_POST['title'] ?? '');
$disciplines = trim($_POST['disciplines'] ?? '');
$project_type_id = (int)($_POST['project_type_id'] ?? 0);
$project_description = trim($_POST['project_description'] ?? '');
$accept_terms = isset($_POST['accept_terms']);

error_log("Datos recibidos - Title: '$title', Type: $project_type_id, Accept: " . ($accept_terms ? 'SI' : 'NO'));

// Función para mostrar error
function showErrorAndReturn($message) {
    error_log("ERROR: $message");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= addslashes($message) ?>',
                confirmButtonColor: '#CD1719'
            }).then(() => {
                window.history.back();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Función para redireccionar con éxito
function redirectWithSuccess() {
    error_log("=== PROCESAMIENTO EXITOSO ===");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Propuesta TFG enviada y grupo creado exitosamente',
                confirmButtonColor: '#034991',
                timer: 2000,
                timerProgressBar: true,
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '../../../panel_estudiante.php?success=tfg_created';
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Validaciones básicas
if (empty($title)) {
    showErrorAndReturn("El título es obligatorio");
}

if (empty($disciplines)) {
    showErrorAndReturn("Las disciplinas son obligatorias");
}

if (!$project_type_id) {
    showErrorAndReturn("Debe seleccionar un tipo de proyecto");
}

if (empty($project_description)) {
    showErrorAndReturn("La descripción es obligatoria");
}

if (!$accept_terms) {
    showErrorAndReturn("Debe aceptar los términos y condiciones");
}

// Validar archivo
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $error_code = $_FILES['document']['error'] ?? 'desconocido';
    showErrorAndReturn("Error al subir el archivo. Código: $error_code");
}

$file_info = $_FILES['document'];
$file_name = basename($file_info['name']);
$file_size = $file_info['size'];
$tmp_path = $file_info['tmp_name'];

// Validar tipo de archivo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $tmp_path);
finfo_close($finfo);

$allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
if (!in_array($mime_type, $allowed_types)) {
    showErrorAndReturn("Solo se permiten archivos PDF y DOCX. Tipo detectado: $mime_type");
}

// Validar tamaño
$max_size = 10 * 1024 * 1024; // 10MB
if ($file_size > $max_size) {
    showErrorAndReturn("El archivo es muy grande. Máximo 10MB.");
}

// Leer contenido del archivo
$document_content = file_get_contents($tmp_path);
if ($document_content === false) {
    showErrorAndReturn("No se pudo leer el archivo");
}

error_log("Archivo procesado - Name: $file_name, Size: $file_size, MIME: $mime_type");

// Conexión a BD
try {
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    error_log("Conexión a BD exitosa");
} catch (Exception $e) {
    showErrorAndReturn("Error de base de datos: " . $e->getMessage());
}

// Verificar que el usuario actual existe
$check_user_sql = "SELECT id FROM sis_user WHERE id = ?";
$check_stmt = $conn->prepare($check_user_sql);
$check_stmt->bind_param("s", $user_id);
$check_stmt->execute();
$user_result = $check_stmt->get_result();

if ($user_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    showErrorAndReturn("Su usuario no existe en la base de datos. ID: $user_id");
}
$check_stmt->close();

// Procesar miembros
$members_data = [
    ['user_id' => $user_id, 'role' => 'Líder']
];

if (isset($_POST['members']) && is_array($_POST['members'])) {
    foreach ($_POST['members'] as $member_id) {
        $member_id = trim($member_id);
        if (!empty($member_id) && $member_id !== $user_id) {
            // Verificar que el miembro existe
            $member_check_sql = "SELECT id FROM sis_user WHERE id = ?";
            $member_stmt = $conn->prepare($member_check_sql);
            $member_stmt->bind_param("s", $member_id);
            $member_stmt->execute();
            $member_result = $member_stmt->get_result();
            
            if ($member_result->num_rows === 0) {
                $member_stmt->close();
                $conn->close();
                showErrorAndReturn("El usuario $member_id no existe en la base de datos");
            }
            $member_stmt->close();
            
            $members_data[] = ['user_id' => $member_id, 'role' => 'Miembro'];
        }
    }
}

error_log("Miembros a insertar: " . count($members_data));

// INICIAR TRANSACCIÓN
$conn->begin_transaction();

// ===== INICIO DE LA SECCIÓN ACTUALIZADA =====
try {
    // 1. INSERTAR PROPUESTA TFG
    $tfg_sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente de Revisión')";
    
    $tfg_stmt = $conn->prepare($tfg_sql);
    if (!$tfg_stmt) {
        throw new Exception("Error preparando TFG: " . $conn->error);
    }
    
    $tfg_stmt->bind_param("sssssssi", $user_id, $title, $disciplines, $project_description, $document_content, $file_name, $mime_type, $file_size);
    
    if (!$tfg_stmt->execute()) {
        throw new Exception("Error insertando TFG: " . $tfg_stmt->error);
    }
    
    $tfg_id = $conn->insert_id;
    $tfg_stmt->close();
    
    error_log("TFG insertado con ID: $tfg_id");
    
    // 2. INSERTAR PROYECTO REGISTRADO (SIN project_title NI description)
    $project_sql = "INSERT INTO registered_projects (tfg_proposal_id, project_type_id, status) 
                    VALUES (?, ?, 'Registrado')";
    
    $project_stmt = $conn->prepare($project_sql);
    if (!$project_stmt) {
        throw new Exception("Error preparando proyecto: " . $conn->error);
    }
    
    // SOLO dos parámetros: tfg_proposal_id y project_type_id
    $project_stmt->bind_param("ii", $tfg_id, $project_type_id);
    
    if (!$project_stmt->execute()) {
        throw new Exception("Error insertando proyecto: " . $project_stmt->error);
    }
    
    $project_id = $conn->insert_id;
    $project_stmt->close();
    
    error_log("Proyecto registrado con ID: $project_id");
    
    // 3. INSERTAR MIEMBROS DEL PROYECTO (sin cambios)
    $member_sql = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)";
    $member_stmt = $conn->prepare($member_sql);
    
    if (!$member_stmt) {
        throw new Exception("Error preparando miembros: " . $conn->error);
    }
    
    foreach ($members_data as $member) {
        $member_stmt->bind_param("iss", $project_id, $member['user_id'], $member['role']);
        
        if (!$member_stmt->execute()) {
            throw new Exception("Error insertando miembro " . $member['user_id'] . ": " . $member_stmt->error);
        }
        
        error_log("Miembro insertado: " . $member['user_id'] . " como " . $member['role']);
    }
    
    $member_stmt->close();
    
    // 4. INSERTAR EN HISTORIAL (opcional)
    $history_sql = "INSERT INTO project_history (project_id, user_id, action_type, new_value, comments) 
                    VALUES (?, ?, 'Creado', ?, 'Proyecto creado desde formulario TFG')";
    
    $history_stmt = $conn->prepare($history_sql);
    if ($history_stmt) {
        $action_value = "Proyecto '$title' creado con " . count($members_data) . " miembros";
        $history_stmt->bind_param("iss", $project_id, $user_id, $action_value);
        $history_stmt->execute();
        $history_stmt->close();
        error_log("Historial registrado para proyecto ID: $project_id");
    }
    
    // CONFIRMAR TRANSACCIÓN
    $conn->commit();
    $conn->close();
    
    error_log("=== TRANSACCIÓN COMPLETADA EXITOSAMENTE ===");
    error_log("TFG ID: $tfg_id, Proyecto ID: $project_id, Miembros: " . count($members_data));
    
    // Redireccionar con éxito
    redirectWithSuccess();
    
} catch (Exception $e) {
    // REVERTIR TRANSACCIÓN EN CASO DE ERROR
    $conn->rollback();
    $conn->close();
    
    error_log("ERROR EN TRANSACCIÓN: " . $e->getMessage());
    showErrorAndReturn("Error al procesar la propuesta: " . $e->getMessage());
}

>>>>>>> HU-002
?>