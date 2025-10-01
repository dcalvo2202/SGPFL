<?php
// Iniciar output buffering para capturar cualquier salida
ob_start();

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// VERIFICAR AUTENTICACIÓN ANTES QUE NADA
include("../../login/check.php");

// OBTENER DATOS REALES DEL USUARIO AUTENTICADO
$user_id = $mySessionController->getVar("usuario");
$user_name = $mySessionController->getVar("nombre"); 
$user_rol = $mySessionController->getVar("rol");

// Incluir configuración BD DESPUÉS del check para evitar sobrescritura de variables
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');

// Verificar que sea estudiante (rol 4 según tu BD)
if ($user_rol != 4) {
    respond_json(false, 'Solo estudiantes pueden crear propuestas TFG');
}

// Verificar autenticación completa
if (!$user_id || !$user_name) {
    respond_json(false, 'Usuario no autenticado correctamente');
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
    if (isset($_FILES['proposal_file']) && $_FILES['proposal_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proposal_file'];
        
        // Validar tipo de archivo
        if ($file['type'] !== 'application/pdf') {
            respond_json(false, 'Solo se permiten archivos PDF');
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
    
    // Si hay archivo PDF, leerlo como BLOB
    if (isset($_FILES['proposal_file']) && $_FILES['proposal_file']['error'] === UPLOAD_ERR_OK) {
        $document_data = file_get_contents($_FILES['proposal_file']['tmp_name']);
        $file_name = $_FILES['proposal_file']['name'];
        $mime_type = $_FILES['proposal_file']['type'];
        $file_size = $_FILES['proposal_file']['size'];
    }
    
    $tfg_sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente de Revisión', NOW(), NOW())";
    
    $tfg_stmt = $conn->prepare($tfg_sql);
    if (!$tfg_stmt) {
        $conn->rollback();
        respond_json(false, 'Error interno del servidor (TFG)');
    }

    $tfg_stmt->bind_param("sssssssi", $user_id, $title, $disciplines, $description, $document_data, $file_name, $mime_type, $file_size);
    
    if (!$tfg_stmt->execute()) {
        $conn->rollback();
        respond_json(false, 'Error al guardar la propuesta TFG');
    }

    $tfg_id = $conn->insert_id;
    $tfg_stmt->close();

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

    // Confirmar transacción
    $conn->commit();
    $conn->close();

    // Limpiar todos los niveles de output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
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