<?php
/**
 * Procesador de solicitudes de prórroga
 * Recibe el formulario de panel_solicitudProrroga.php
 */
include(__DIR__ . "/../login/check.php");
require_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
require_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
require_once __DIR__ . '/ProrrogaLogic.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");
$base_url = $mySessionController->getVar("cds_domain") . $mySessionController->getVar("cds_locate");

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../panel_solicitudProrroga.php");
    exit;
}

// Obtener datos del formulario
$proposal_id = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;
$extension_number = isset($_POST['extension_number']) ? (int)$_POST['extension_number'] : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

// Validaciones básicas
if (empty($proposal_id) || empty($extension_number) || empty($motivo)) {
    $mySessionController->save('prorroga_error', 'Todos los campos son obligatorios.');
    header("Location: ../../../panel_solicitudProrroga.php");
    exit;
}

// Validar longitud del motivo
if (strlen($motivo) > 200) {
    $mySessionController->save('prorroga_error', 'El motivo no puede exceder 200 caracteres.');
    header("Location: ../../../panel_solicitudProrroga.php");
    exit;
}

try {
    $prorrogaLogic = new ProrrogaLogic();
    $resultado = $prorrogaLogic->crearSolicitud($proposal_id, $current_user_id, $extension_number, $motivo);
    
    if ($resultado['success']) {
        $mySessionController->save('prorroga_success', $resultado['message']);
    } else {
        $mySessionController->save('prorroga_error', $resultado['message']);
    }
} catch (Exception $e) {
    $mySessionController->save('prorroga_error', 'Error al procesar la solicitud: ' . $e->getMessage());
}

header("Location: ../../../panel_solicitudProrroga.php");
exit;
