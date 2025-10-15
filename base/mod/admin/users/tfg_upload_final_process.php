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
include_once(__DIR__ . '/../../../inc/tfg_final_functions.php');

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

// Función para responder con JSON (reutilizada de tfg_upload_process.php)
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

    // Validar archivo PDF
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'No se recibió el archivo o hubo un error en la subida';
        if (isset($_FILES['document']['error'])) {
            switch ($_FILES['document']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'El archivo excede el tamaño máximo permitido (20 MB)';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'El archivo se subió parcialmente';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No se seleccionó ningún archivo';
                    break;
            }
        }
        respond_json(false, $error_message);
    }

    $file = $_FILES['document'];
    
    // Validar tipo de archivo (MIME type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($mime_type !== 'application/pdf') {
        respond_json(false, 'Solo se permiten archivos PDF. Tipo detectado: ' . $mime_type);
    }
    
    // Validar extensión
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'pdf') {
        respond_json(false, 'El archivo debe tener extensión .pdf');
    }
    
    // Validar tamaño (máximo 20MB = 20,971,520 bytes)
    if ($file['size'] > 20 * 1024 * 1024) {
        respond_json(false, 'El archivo excede el tamaño máximo de 20 MB');
    }
    
    // Validar tamaño mínimo (al menos 100KB para ser un documento real)
    if ($file['size'] < 100 * 1024) {
        respond_json(false, 'El archivo es demasiado pequeño. Debe ser un documento completo.');
    }
    
    // NOTA: No se valida la estructura automáticamente (capítulos, formato APA, firma del tutor)
    // Esta validación es MANUAL por la CTFG al revisar el documento descargado (Tabla 4)
    
    // Guardar el documento final
    $save_result = saveFinalDocument(
        $proposal_id,
        $file,
        $user_id,
        $project_status
    );
    
    if (!$save_result['success']) {
        respond_json(false, $save_result['message']);
    }
    
    // Notificar a la CTFG
    $notification_sent = notifyCTFGNewDocument($proposal_id, $user_id);
    
    if (!$notification_sent) {
        error_log("Advertencia: No se pudo enviar notificación a la CTFG para propuesta $proposal_id");
    }
    
    // Responder con éxito
    respond_json(true, 'Documento final subido exitosamente. La CTFG ha sido notificada para su revisión.', [
        'document_id' => $save_result['document_id'],
        'notification_sent' => $notification_sent
    ]);
    
} catch (Exception $e) {
    error_log("Error en tfg_upload_final_process.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond_json(false, 'Error al procesar el documento: ' . $e->getMessage());
}
