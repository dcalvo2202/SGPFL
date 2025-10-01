<?php

// Activar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Log de debug con usuario real
error_log("=== TFG Upload - Usuario Real ===");
error_log("Usuario ID: " . $user_id);
error_log("Nombre: " . $user_name);
error_log("Rol: " . $user_rol);

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

    // DEBUG: Mostrar todos los datos POST que están llegando
    error_log("=== DEBUG POST DATA ===");
    error_log("Raw POST: " . print_r($_POST, true));
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            error_log("Campo '$key': ARRAY (" . count($value) . " elementos)");
        } else {
            error_log("Campo '$key': '" . $value . "' (length: " . strlen($value) . ")");
        }
    }

    // Validar datos requeridos - permitir tanto 'description' como 'project_description'
    $required_fields = ['title', 'project_type_id'];
    foreach ($required_fields as $field) {
        $value = $_POST[$field] ?? '';
        $trimmed = trim($value);
        error_log("Validando campo '$field': isset=" . (isset($_POST[$field]) ? 'SI' : 'NO') . ", valor='" . $value . "', trimmed='" . $trimmed . "', empty=" . (empty($trimmed) ? 'SI' : 'NO'));
        
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            respond_json(false, "El campo '$field' es requerido");
        }
    }

    // Validar descripción (puede ser 'description' o 'project_description')
    $description_value = '';
    if (isset($_POST['description']) && trim($_POST['description']) !== '') {
        $description_value = trim($_POST['description']);
    } elseif (isset($_POST['project_description']) && trim($_POST['project_description']) !== '') {
        $description_value = trim($_POST['project_description']);
    }
    
    if (empty($description_value)) {
        respond_json(false, "El campo de descripción (description o project_description) es requerido");
    }

    error_log("Descripción encontrada: '" . $description_value . "' (length: " . strlen($description_value) . ")");

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
    
    error_log("Variables BD después de recarga: host=$db_host, usuario=$usuario, clave=" . (empty($clave) ? 'VACIA' : 'SET') . ", db=$db");
    error_log("Variables usuario: user_id=$user_id, user_name=$user_name, user_rol=$user_rol");
    
    $conn = new mysqli($db_host, $usuario, $clave, $db);
    if ($conn->connect_error) {
        error_log("Error de conexión: " . $conn->connect_error);
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
        error_log("Error preparando query TFG: " . $conn->error);
        respond_json(false, 'Error interno del servidor (TFG)');
    }

    $tfg_stmt->bind_param("sssssssi", $user_id, $title, $disciplines, $description, $document_data, $file_name, $mime_type, $file_size);
    
    if (!$tfg_stmt->execute()) {
        $conn->rollback();
        error_log("Error ejecutando TFG: " . $tfg_stmt->error);
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
        error_log("Error preparando query proyecto: " . $conn->error);
        respond_json(false, 'Error interno del servidor (Proyecto)');
    }

    $project_stmt->bind_param("ii", $tfg_id, $project_type_id);
    
    if (!$project_stmt->execute()) {
        $conn->rollback();
        error_log("Error ejecutando proyecto: " . $project_stmt->error);
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
                            error_log("Error agregando miembro: " . $additional_member_stmt->error);
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

    // Log de éxito
    error_log("TFG creado exitosamente - Usuario: $user_id, TFG ID: $tfg_id, Proyecto ID: $project_id");

    // Respuesta exitosa
    respond_json(true, 'Propuesta TFG creada exitosamente', [
        'tfg_id' => $tfg_id,
        'project_id' => $project_id,
        'title' => $title,
        'user_authenticated' => true,
        'user_id' => $user_id,
        'user_name' => $user_name,
        'file_uploaded' => !empty($pdf_path)
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    error_log("Excepción en TFG upload: " . $e->getMessage());
    respond_json(false, 'Error interno del servidor: ' . $e->getMessage());
}
?>