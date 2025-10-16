<?php
// =============================== INICIALIZACIÓN Y CONFIGURACIÓN ===============================

// Iniciar output buffering para capturar cualquier salida
ob_start();

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// =============================== AUTENTICACIÓN Y SESIÓN ===============================

// VERIFICAR AUTENTICACIÓN ANTES QUE NADA
include("../../login/check.php");

// OBTENER DATOS REALES DEL USUARIO AUTENTICADO
$user_id = $mySessionController->getVar("usuario");
$user_name = $mySessionController->getVar("nombre"); 
$user_rol = $mySessionController->getVar("rol");

// Incluir configuración BD DESPUÉS del check para evitar sobrescritura de variables
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');

// Verificar autenticación básica (solo requiere user_id)
if (!$user_id) {
    respond_json(false, 'Usuario no autenticado correctamente');
}

// Verificar que sea estudiante (rol 4 según tu BD)
if ($user_rol != 4) {
    respond_json(false, 'Solo estudiantes pueden crear propuestas TFG');
}

// Configurar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Función para responder con JSON
function respond_json($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond_json(false, 'Método no permitido');
    }

    // Validar datos requeridos
    $required_fields = ['title', 'project_type_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            respond_json(false, "El campo '$field' es requerido");
        }
    }

    // Validar descripción
    $description_value = '';
    if (isset($_POST['description']) && trim($_POST['description']) !== '') {
        $description_value = trim($_POST['description']);
    } elseif (isset($_POST['project_description']) && trim($_POST['project_description']) !== '') {
        $description_value = trim($_POST['project_description']);
    }
    
    if (empty($description_value)) {
        respond_json(false, "La descripción del proyecto es requerida");
    }

    // Obtener y validar datos del formulario
    $title = trim($_POST['title']);
    $description = $description_value; // Usar la descripción ya validada
    $project_type_id = intval($_POST['project_type_id']);
    $keywords = trim($_POST['keywords'] ?? '');
    $project_description = trim($_POST['project_description'] ?? $description);

    // Validaciones básicas
    if (strlen($title) < 5) {
        respond_json(false, 'El título debe tener al menos 5 caracteres');
    }

    if (strlen($description) < 20) {
        respond_json(false, 'La descripción debe tener al menos 20 caracteres');
    }

    if ($project_type_id <= 0) {
        respond_json(false, 'Debe seleccionar un tipo de proyecto válido');
    }

    // Validar archivo PDF (opcional)
    $pdf_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document'];
        
        // Validar tipo de archivo
        if ($file['type'] !== 'application/pdf' && $file['type'] !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            respond_json(false, 'Solo se permiten archivos PDF o DOCX');
        }
        
        // Validar tamaño (máximo 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            respond_json(false, 'El archivo no puede exceder 10MB');
        }
        
        // Crear directorio de uploads si no existe
        $upload_dir = __DIR__ . '/../../../uploads/tfg_proposals/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = $user_id . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
        $pdf_path = $upload_dir . $unique_filename;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $pdf_path)) {
            respond_json(false, 'Error al guardar el archivo PDF');
        }
        
        // Guardar solo la ruta relativa en la BD
        $pdf_path = 'uploads/tfg_proposals/' . $unique_filename;
    }

    // Conectar a la base de datos - RECARGAR variables para evitar conflictos
    // Guardar variables de usuario antes de recargar configuración BD
    $saved_user_id = $user_id;
    $saved_user_name = $user_name;
    $saved_user_rol = $user_rol;
    
    // Recargar configuración de BD para asegurar variables correctas
    include(__DIR__ . '/../../../inc/db/bdcommon.inc');
    
    // Restaurar variables de usuario
    $user_id = $saved_user_id;
    $user_name = $saved_user_name;
    $user_rol = $saved_user_rol;
    
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        respond_json(false, 'Error de conexión a la base de datos: ' . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    $conn->autocommit(false);

    // 1. Insertar propuesta TFG con estructura correcta de la tabla
    // La tabla real tiene: id, user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, admin_comments, reviewed_by, reviewed_at, created_at, updated_at
    
    // Preparar datos para la inserción
    $disciplines = $_POST['disciplines'] ?? 'Sin especificar';
    $document_data = null;
    $file_name = '';
    $mime_type = '';
    $file_size = 0;
    $null_blob = null; // Variable auxiliar para bind_param
    
    // Si hay archivo PDF, leerlo como BLOB
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        // Leer el archivo DESDE LA RUTA DONDE SE MOVIÓ (no desde tmp_name)
        if ($pdf_path && file_exists(__DIR__ . '/../../../' . $pdf_path)) {
            $document_data = file_get_contents(__DIR__ . '/../../../' . $pdf_path);
        } else {
            $document_data = null;
        }
        
        $file_name = $_FILES['document']['name'];
        $mime_type = $_FILES['document']['type'];
        $file_size = $_FILES['document']['size'];
    }
    
    $tfg_sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente de Revisión', NOW(), NOW())";
    
    $tfg_stmt = $conn->prepare($tfg_sql);
    if (!$tfg_stmt) {
        $conn->rollback();
        respond_json(false, 'Error interno del servidor (TFG): ' . $conn->error);
    }

    // IMPORTANTE: Para BLOB, primero bind_param con NULL, luego send_long_data
    // Usar el nombre de archivo único generado, no el original
    $tfg_stmt->bind_param("ssssbssi", $user_id, $title, $disciplines, $description, $null_blob, $unique_filename, $mime_type, $file_size);
    
    // Enviar el BLOB por separado si existe
    if ($document_data !== null && strlen($document_data) > 0) {
        $tfg_stmt->send_long_data(4, $document_data); // 4 es la posición del BLOB (empieza en 0)
    } else {
        error_log("TFG Upload - ADVERTENCIA: document_data está vacío o NULL");
    }
    
    if (!$tfg_stmt->execute()) {
        $conn->rollback();
        respond_json(false, 'Error al guardar la propuesta TFG: ' . $tfg_stmt->error);
    }

    $tfg_id = $conn->insert_id;
    $tfg_stmt->close();

    // =============================== INSERCIÓN EN HISTORIAL DE VERSIONES ===============================

    //include_once(__DIR__ . '/../../../inc/tfg_proposal_functions.php');
    //limitarVersionesYAgregarHistorial($conn, $tfg_id, $user_id, $unique_filename, $mime_type, $file_size, $document_data, $null_blob);

    /* Contar cuántas versiones existen para esta propuesta
    $stmt = $conn->prepare("SELECT id FROM tfg_proposal_history WHERE proposal_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $tfg_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $version_ids = [];
    while ($row = $result->fetch_assoc()) {
        $version_ids[] = $row['id'];
    }
    $stmt->close();

    // Si ya hay 5 versiones, eliminar la más antigua
    if (count($version_ids) >= 5) {
        $oldest_id = $version_ids[0];
        $stmt = $conn->prepare("DELETE FROM tfg_proposal_history WHERE id = ?");
        $stmt->bind_param("i", $oldest_id);
        $stmt->execute();
        $stmt->close();
    }*/

    // Insertar la versión inicial en el historial
    $history_sql = "INSERT INTO tfg_proposal_history (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'Pendiente de Revisión', ?, 'Versión inicial subida por el estudiante', NOW())";
    $history_stmt = $conn->prepare($history_sql);
    if ($history_stmt) {
        $history_stmt->bind_param("ibssis", $tfg_id, $null_blob, $unique_filename, $mime_type, $file_size, $user_id);
         // Enviar el BLOB por separado si existe, igual que en la inserción principal
        if ($document_data !== null && strlen($document_data) > 0) {
            $history_stmt->send_long_data(1, $document_data); // El índice 1 corresponde al segundo '?' (document)
        }
        
        if (!$history_stmt->execute()) {
            throw new Exception("Error al insertar en historial: " . $history_stmt->error);
        }
        $history_stmt->close();
    } else {
        throw new Exception("Error al preparar la consulta de historial: " . $conn->error);
    }
    
    // =============================== CREACIÓN DE PROYECTO Y MIEMBROS ===============================

    // 2. Crear proyecto asociado con estructura correcta
    // registered_projects tiene: id, tfg_proposal_id, project_type_id, status, start_date, end_date, final_grade, supervisor_id, created_at, updated_at
    $project_sql = "INSERT INTO registered_projects (tfg_proposal_id, project_type_id, status, created_at, updated_at) 
                    VALUES (?, ?, 'Registrado', NOW(), NOW())";
    
    $project_stmt = $conn->prepare($project_sql);
    if (!$project_stmt) {
        $conn->rollback();
        respond_json(false, 'Error interno del servidor (Proyecto)');
    }

    $project_stmt->bind_param("ii", $tfg_id, $project_type_id);
    
    if (!$project_stmt->execute()) {
        $conn->rollback();
        respond_json(false, 'Error al crear el proyecto asociado');
    }

    $project_id = $conn->insert_id;
    $project_stmt->close();

    // 3. Agregar al usuario autenticado como líder del proyecto
    $member_sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                  VALUES (?, ?, 'Líder', NOW())";
    
    $member_stmt = $conn->prepare($member_sql);
    if ($member_stmt) {
        $member_stmt->bind_param("is", $project_id, $user_id);
        $member_stmt->execute();
        $member_stmt->close();
    }

    // 4. Agregar miembros adicionales del grupo (si los hay)
    if (!empty($_POST['group_members'])) {
        $members = json_decode($_POST['group_members'], true);
        if (is_array($members)) {
            $additional_member_sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                                    VALUES (?, ?, 'Miembro', NOW())";
            
            $additional_member_stmt = $conn->prepare($additional_member_sql);
            if ($additional_member_stmt) {
                foreach ($members as $member) {
                    if (!empty($member['user_id']) && $member['user_id'] !== $user_id) {
                        $additional_member_stmt->bind_param("is", $project_id, $member['user_id']);
                        
                        if (!$additional_member_stmt->execute()) {
                            $conn->rollback();
                            respond_json(false, 'Error al agregar miembro adicional');
                        }
                    }
                }
                $additional_member_stmt->close();
            }
        }
    }

    // =============================== CONFIRMAR TRANSACCIÓN ===============================
    $conn->commit();
    $conn->close();

    // Limpiar todos los niveles de output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // =============================== NOTIFICACIONES ===============================
    
    // Enviar notificación al estudiante y a la secretaría académica
    include __DIR__ . '/tfg_update_document.php';


    // Obtener ruta base desde configuración para redirigir
    require_once(__DIR__ . '/../../../config.inc');
    $redirect_url = $cds_domain . $cds_locate . 'panel_estudiante.php';
    
    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuesta Enviada</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .loader {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .swal2-border-radius {
            border-radius: 20px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        }
        .swal2-confirm-btn {
            padding: 12px 30px !important;
            font-size: 1rem !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
    <div class="loader"></div>
    <script>
        Swal.fire({
            icon: 'success',
            title: '<strong style="color: #034991;">¡Propuesta Enviada Exitosamente!</strong>',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #28a745;"></i>
                    <p style="font-size: 1.1rem; margin-top: 15px; color: #333;">
                        Su propuesta de Trabajo Final de Graduación ha sido registrada correctamente.
                    </p>
                    <p style="font-size: 0.95rem; color: #666; margin-top: 10px;">
                        <i class="bi bi-info-circle"></i> 
                        El estado de su propuesta es: <strong>Pendiente de Revisión</strong>
                    </p>
                    <p style="font-size: 0.9rem; color: #999; margin-top: 15px;">
                        Será redirigido a su panel en unos segundos...
                    </p>
                </div>
            `,
            confirmButtonText: '<i class="bi bi-arrow-right-circle"></i> Ir al Panel',
            confirmButtonColor: '#034991',
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: 4000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            },
            customClass: {
                popup: 'swal2-border-radius',
                confirmButton: 'swal2-confirm-btn'
            }
        }).then(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        });
    </script>
</body>
</html>
<?php
    exit;

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    respond_json(false, 'Error: ' . $e->getMessage());
}
?>