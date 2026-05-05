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

// Incluir configuración BD y funciones helper
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');
include_once(__DIR__ . '/../../../inc/upload_helpers.php');
include_once(__DIR__ . '/../../../inc/constants.php');

// Verificar autenticación básica (solo requiere user_id)
if (!$user_id) {
    respond_json(false, 'Usuario no autenticado correctamente');
}

// Verificar que sea estudiante (rol 4 según tu BD)
if ($user_rol != 4) {
    respond_json(false, 'Solo estudiantes pueden crear propuestas TFG');
}

// =============================== VERIFICAR SI TIENE PROPUESTA EN ESTADO BLOQUEANTE ===============================
try {
    $conn_block_check = new mysqli($db_host, $usuario, $clave, $db);
    if (!$conn_block_check->connect_error) {
        $conn_block_check->set_charset("utf8");
        
        // Buscar la propuesta más reciente del estudiante
        $sql_block = "SELECT id, status, title FROM tfg_proposals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt_block = $conn_block_check->prepare($sql_block);
        $stmt_block->bind_param("s", $user_id);
        $stmt_block->execute();
        $result_block = $stmt_block->get_result();
        
        if ($result_block->num_rows > 0) {
            $proposal_data = $result_block->fetch_assoc();
            $existing_status = $proposal_data['status'];
            
            // Si está en un estado bloqueante, rechazar la subida
            if (in_array($existing_status, TFG_BLOCKING_STATUSES)) {
                $stmt_block->close();
                $conn_block_check->close();
                respond_json(false, 'Ya tienes una propuesta en estado "' . $existing_status . '". No puedes subir otra propuesta hasta que sea rechazada.');
            }
        }
        
        $stmt_block->close();
        $conn_block_check->close();
    }
} catch (Exception $e) {
    error_log("Error al verificar estado de propuesta: " . $e->getMessage());
}

// Configurar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

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

    // Validar archivos PDF/DOCX (múltiples archivos)
    $uploaded_files = [];
    $total_file_size = 0;
    $combined_document_data = '';
    $first_file_name = '';
    $first_original_file_name = '';
    $first_mime_type = '';
    
    // Crear directorio de uploads una sola vez
    $upload_dir = ensureUploadDirectory(__DIR__ . '/../../../uploads/tfg_proposals/');
    
    // Verificar si hay archivos subidos (nuevo formato con múltiples archivos)
    if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $files_count = count($_FILES['documents']['name']);
        
        for ($i = 0; $i < $files_count; $i++) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                // Procesar archivo usando función helper
                $file_info = [
                    'name' => $_FILES['documents']['name'][$i],
                    'type' => $_FILES['documents']['type'][$i],
                    'size' => $_FILES['documents']['size'][$i],
                    'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                    'error' => $_FILES['documents']['error'][$i]
                ];
                
                $processed_file = processProposalFile($file_info, $upload_dir, $user_id, $i);
                $uploaded_files[] = $processed_file;
                $total_file_size += $processed_file['size'];
                
                // Usar el primer archivo como documento principal
                if ($i === 0) {
                    $first_file_name = $processed_file['unique_name'];
                    $first_original_file_name = $processed_file['name'];
                    $first_mime_type = $processed_file['type'];
                    $combined_document_data = $processed_file['content'];
                }
            }
        }
        
        // Validar tamaño total (máximo 10MB)
        if ($total_file_size > 10 * 1024 * 1024) {
            respond_json(false, 'El tamaño total de los archivos excede 10MB');
        }
    }
    // Compatibilidad con formato antiguo (un solo archivo)
    elseif (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $processed_file = processProposalFile($_FILES['document'], $upload_dir, $user_id);
        $uploaded_files[] = $processed_file;
        
        $first_file_name = $processed_file['unique_name'];
        $first_original_file_name = $processed_file['name'];
        $first_mime_type = $processed_file['type'];
        $total_file_size = $processed_file['size'];
        $combined_document_data = $processed_file['content'];
    }

    // Validar que realmente se haya subido al menos un documento válido
    if (count($uploaded_files) === 0) {
        respond_json(false, 'Debe subir al menos un documento de propuesta válido en formato PDF o DOCX');
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

    // =============================== DESACTIVAR MIEMBROS DE PROPUESTAS ANTERIORES RECHAZADAS ===============================
    // Antes de crear nuevos registros, desactivar los miembros de proyectos asociados
    // a propuestas anteriores rechazadas de este usuario para evitar duplicados.
    try {
        $sql_deactivate = "UPDATE project_members pm
                           INNER JOIN registered_projects rp ON pm.project_id = rp.id
                           INNER JOIN tfg_proposals tp ON rp.tfg_proposal_id = tp.id
                           SET pm.status = 'Inactivo'
                           WHERE tp.user_id = ? AND pm.status = 'Activo'
                           AND tp.status NOT IN ('Pendiente de Revision', 'En Revisión', 'Cumple requisitos', 'Aprobado')";
        $stmt_deactivate = $conn->prepare($sql_deactivate);
        if ($stmt_deactivate) {
            $stmt_deactivate->bind_param("s", $user_id);
            $stmt_deactivate->execute();
            $deactivated_count = $stmt_deactivate->affected_rows;
            if ($deactivated_count > 0) {
                error_log("TFG Upload - Miembros desactivados de propuestas anteriores rechazadas: $deactivated_count");
            }
            $stmt_deactivate->close();
        }
    } catch (Exception $e) {
        error_log("Error al desactivar miembros anteriores: " . $e->getMessage());
        // No es crítico, continuar con la inserción
    }

    // 1. Insertar propuesta TFG con estructura correcta de la tabla
    // La tabla real tiene: id, user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, admin_comments, reviewed_by, reviewed_at, created_at, updated_at
    
    // Preparar datos para la inserción
    $disciplines = $_POST['disciplines'] ?? 'Sin especificar';
    $null_blob = null; // Variable auxiliar para bind_param
    
    // Usar datos del primer archivo subido (o valores vacíos si no hay archivos)
    $document_data = !empty($combined_document_data) ? $combined_document_data : null;
    // Guardar el nombre ORIGINAL que subió el usuario (no el nombre único del filesystem)
    $file_name = !empty($first_original_file_name) ? $first_original_file_name : '';
    $mime_type = !empty($first_mime_type) ? $first_mime_type : '';
    $file_size = $total_file_size;
    
    $tfg_sql = "INSERT INTO tfg_proposals (user_id, title, disciplines, project_description, document, file_name, mime_type, file_size, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $tfg_stmt = $conn->prepare($tfg_sql);
    if (!$tfg_stmt) {
        $conn->rollback();
        respond_json(false, 'Error interno del servidor (TFG): ' . $conn->error);
    }

    // IMPORTANTE: Para BLOB, primero bind_param con NULL, luego send_long_data
    // El archivo físico se guarda con nombre único, pero en BD guardamos el nombre original del usuario
    // Status se pasa como parámetro para evitar problemas de encoding con caracteres especiales
    $initial_status = TFG_STATUS_PENDING; // 'Pendiente de Revisión' desde constants.php
    $tfg_stmt->bind_param("ssssbssis", $user_id, $title, $disciplines, $description, $null_blob, $file_name, $mime_type, $file_size, $initial_status);
    
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
    
    // =============================== INSERTAR ARCHIVOS ADICIONALES ===============================
    // Si hay múltiples archivos, guardarlos en tfg_files vinculados a esta propuesta
    if (count($uploaded_files) > 1) {
        // La versión del lote es el número total de propuestas del estudiante (incluye la recién insertada)
        $stmt_ver = $conn->prepare("SELECT COUNT(*) AS cnt FROM tfg_proposals WHERE user_id = ?");
        $stmt_ver->bind_param("s", $user_id);
        $stmt_ver->execute();
        $proposal_version = (int)$stmt_ver->get_result()->fetch_assoc()['cnt'];
        $stmt_ver->close();

        $additional_files_saved = saveAdditionalFiles($conn, $uploaded_files, $user_id, 'Propuesta TFG', $tfg_id, 'proposal', $proposal_version);
        error_log("Archivos adicionales guardados: $additional_files_saved");
    }

    // =============================== INSERCIÓN EN HISTORIAL DE VERSIONES ===============================

    // Limitar versiones a 5 y eliminar la más antigua si es necesario
    include_once(__DIR__ . '/../../../inc/tfg_proposal_functions.php');
    limitarVersionesYAgregarHistorial($conn, $user_id);
        
    // Insertar la versión inicial en el historial
    $history_sql = "INSERT INTO tfg_proposal_history (proposal_id, document, file_name, mime_type, file_size, status, reviewed_by, comments, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'Pendiente de Revision', ?, 'Versión inicial subida por el estudiante', NOW())";
    $history_stmt = $conn->prepare($history_sql);
    if ($history_stmt) {
        $history_stmt->bind_param("ibssis", $tfg_id, $null_blob, $file_name, $mime_type, $file_size, $user_id);
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
    $added_members = []; // Lista de miembros agregados para notificarles después
    $members = [];
    if (!empty($_POST['members']) && is_array($_POST['members'])) {
        foreach ($_POST['members'] as $member_id) {
            $member_id = trim((string)$member_id);
            if ($member_id !== '') {
                $members[] = ['user_id' => $member_id];
            }
        }
    }

    if (!empty($members)) {
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
                    $added_members[] = $member['user_id']; // Guardar para notificar
                }
            }
            $additional_member_stmt->close();
        }
    }

    // =============================== CONFIRMAR TRANSACCIÓN ===============================
    $conn->commit();
    
    // =============================== HU-037: REGISTRAR ALERTAS ===============================
    // Notificar a Gestores y CTFG sobre la nueva propuesta
    $alert_count = 0;
    try {
        require_once(__DIR__ . '/../../../inc/alert_functions.php');
        require_once(__DIR__ . '/../../../inc/db/bdcommon.inc');
        
        // Crear nueva conexión para alertas (la anterior puede estar en estado inconsistente)
        $conn_alert = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn_alert->connect_error) {
            error_log("HU-037: Error de conexión para alertas: " . $conn_alert->connect_error);
        } else {
            $conn_alert->set_charset("utf8");
            $alert_count = registerProposalSubmittedAlert($conn_alert, $user_name, $title, $tfg_id);
            error_log("HU-037: Alertas de propuesta enviadas: $alert_count para propuesta $tfg_id");
            
            // Notificar a los miembros agregados al grupo
            if (!empty($added_members)) {
                foreach ($added_members as $member_id) {
                    registerGroupMemberAddedAlert($conn_alert, $member_id, $user_name, $title, $project_id);
                }
                error_log("HU-037: Notificaciones enviadas a " . count($added_members) . " miembros del grupo");
            }
            
            $conn_alert->close();
        }
    } catch (Exception $alertEx) {
        // Las alertas son secundarias, no deben interrumpir el flujo principal
        error_log("HU-037: Error registrando alertas (no crítico): " . $alertEx->getMessage());
    }
    
    $conn->close();

    // Limpiar todos los niveles de output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }


    // Obtener ruta base desde configuración para redirigir
    require_once(__DIR__ . '/../../../config.inc');
    $redirect_url = $cds_domain . $cds_locate . 'Panel_SubirTFG.php';
    
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
                        El estado de su propuesta es: <strong>Pendiente de Revision</strong>
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
            timer: 7000,
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
            // Lanzar el fetch sin esperar la respuesta para enviar las notificaciones al estudiante y la secretaria.
            fetch('send_tfg_mail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'tipo=Propuesta TFG'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('Email result:', data);
            })
            .catch(function(err) {
                console.error('Email error:', err);
            });
            // Redirigir inmediatamente
            window.location.href = '<?php echo $redirect_url; ?>';
        });
    </script>
</body>
</html>
<?php
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackEx) {
            error_log('TFG Upload - rollback falló: ' . $rollbackEx->getMessage());
        }

        try {
            $conn->close();
        } catch (Throwable $closeEx) {
            error_log('TFG Upload - cierre de conexión falló: ' . $closeEx->getMessage());
        }
    }
    respond_json(false, 'Error: ' . $e->getMessage());
}
?>