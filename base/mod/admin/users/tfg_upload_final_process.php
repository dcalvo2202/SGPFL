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

// Incluir configuración BD y funciones helper
include_once(__DIR__ . '/../../../inc/db/bdcommon.inc');
include_once(__DIR__ . '/../../../inc/tfg_final_functions.php');
include_once(__DIR__ . '/../../../inc/upload_helpers.php');

// Verificar autenticación básica
if (!$user_id) {
    respond_json(false, 'Usuario no autenticado correctamente');
}

// Verificar que sea estudiante (rol 4)
if ($user_rol != 4) {
    respond_json(false, 'Solo estudiantes pueden subir documentos finales');
}

// Configurar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond_json(false, 'Método no permitido');
    }

    // Validar datos requeridos
    if (!isset($_POST['proposal_id']) || !isset($_POST['project_status'])) {
        respond_json(false, 'Datos incompletos en el formulario');
    }

    $proposal_id = intval($_POST['proposal_id']);
    $project_status = $_POST['project_status'];
    $notes = trim($_POST['notes'] ?? '');

    // Validar proposal_id
    if ($proposal_id <= 0) {
        respond_json(false, 'ID de propuesta inválido');
    }

    // Verificar nuevamente que puede subir (por si cambió algo mientras llenaba el formulario)
    $upload_check = canUploadFinalDocument($user_id);
    if (!$upload_check['can_upload']) {
        respond_json(false, $upload_check['message']);
    }

    // Validar que el proposal_id coincide con el del usuario
    if ($upload_check['proposal_id'] !== $proposal_id) {
        respond_json(false, 'No autorizado para subir documento para esta propuesta');
    }

    // Validar archivos PDF usando funciones helper
    $uploaded_files = [];
    $first_file = null;
    
    // Verificar si hay archivos subidos (nuevo formato con múltiples archivos)
    if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $uploaded_files = processMultipleFiles($_FILES['documents'], function($file_info) {
            return validateFinalDocumentFile($file_info, 20, 100);
        });
        
        if (!empty($uploaded_files)) {
            $first_file = $uploaded_files[0];
        }
    }
    // Compatibilidad con formato antiguo (un solo archivo)
    elseif (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $first_file = validateFinalDocumentFile($_FILES['document'], 20, 100);
        $uploaded_files[] = $first_file;
    } else {
        $error_message = 'No se recibió el archivo o hubo un error en la subida';
        if (isset($_FILES['document']['error'])) {
            $error_message = getUploadErrorMessage($_FILES['document']['error']);
        }
        respond_json(false, $error_message);
    }
    
    if (empty($uploaded_files) || $first_file === null) {
        respond_json(false, 'No se recibieron archivos válidos');
    }
    
    // NOTA: No se valida la estructura automáticamente (capítulos, formato APA, firma del tutor)
    // Esta validación es MANUAL por la CTFG al revisar el documento descargado (Tabla 4)
    
    // Guardar el documento final principal
    $save_result = saveFinalDocument(
        $proposal_id,
        $first_file,
        $user_id,
        $project_status
    );
    
    if (!$save_result['success']) {
        respond_json(false, $save_result['message']);
    }
    
    // Guardar archivos adicionales si hay más de uno
    $additional_files_saved = 0;
    if (count($uploaded_files) > 1) {
        require(__DIR__ . '/../../../inc/db/bdcommon.inc');
        $conn = new mysqli($db_host, $usuario, $clave, $db);
        
        if (!$conn->connect_error) {
            $conn->set_charset("utf8");
            $additional_files_saved = saveAdditionalFiles(
                $conn, 
                $uploaded_files, 
                $user_id, 
                'Documento Final TFG Anexo'
            );
            $conn->close();
        }
    }
    
    // Notificar a la CTFG
    $notification_sent = notifyCTFGNewDocument($proposal_id, $user_id);
    
    if (!$notification_sent) {
        error_log("Advertencia: No se pudo enviar notificación a la CTFG para propuesta $proposal_id");
    }
    
    // ===============================
    // HU-037: REGISTRAR ALERTA INTERNA
    // ===============================
    $alert_count = 0;
    try {
        require_once __DIR__ . '/../../../inc/alert_functions.php';
        require_once __DIR__ . '/../../../inc/db/bdcommon.inc';
        
        $conn_alert = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn_alert->connect_error) {
            error_log("HU-037: Error de conexión para alertas: " . $conn_alert->connect_error);
        } else {
            $conn_alert->set_charset("utf8");
            
            // Obtener título de la propuesta
            $sql_title = "SELECT title FROM tfg_proposals WHERE id = ?";
            $stmt_title = $conn_alert->prepare($sql_title);
            $stmt_title->bind_param("i", $proposal_id);
            $stmt_title->execute();
            $title_result = $stmt_title->get_result()->fetch_assoc();
            $proposal_title = $title_result['title'] ?? 'TFG';
            $stmt_title->close();
            
            // Obtener el document_id del resultado o usar el proposal_id
            $doc_id = isset($save_result['document_id']) ? $save_result['document_id'] : $proposal_id;
            
            $alert_count = registerFinalDocumentSubmittedAlert(
                $conn_alert, 
                $user_name, 
                $proposal_title, 
                $doc_id
            );
            
            error_log("HU-037: Alertas de documento final enviadas: $alert_count");
            
            $conn_alert->close();
        }
    } catch (Exception $alertEx) {
        error_log("HU-037: Error registrando alerta (no crítico): " . $alertEx->getMessage());
    }
    
    // Responder con éxito
    $message = 'Documento(s) final(es) subido(s) exitosamente. La CTFG ha sido notificada para su revisión.';
    if ($additional_files_saved > 0) {
        $message .= " Se guardaron $additional_files_saved archivo(s) adicional(es).";
    }
    
    respond_json(true, $message, [
        'document_id' => $save_result['document_id'],
        'notification_sent' => $notification_sent,
        'total_files' => count($uploaded_files),
        'additional_files' => $additional_files_saved
    ]);
    
} catch (Exception $e) {
    error_log("Error en tfg_upload_final_process.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond_json(false, 'Error al procesar el documento: ' . $e->getMessage());
}
