<?php
/**
 * Procesador de respuesta a solicitudes de prórroga
 * Aprueba o rechaza solicitudes desde detalle_prorroga.php
 */
include(__DIR__ . "/../../login/check.php");
require_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';
require_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
require_once __DIR__ . '/ProrrogaLogic.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$current_user_id = $mySessionController->getVar("usuario");

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../panel_aprobarProrroga.php");
    exit;
}

// Obtener datos del formulario
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

// Validaciones básicas
if (empty($request_id) || !in_array($accion, ['aprobar', 'rechazar'])) {
    $mySessionController->save('prorroga_error', 'Datos inválidos.');
    header("Location: ../../../panel_aprobarProrroga.php");
    exit;
}

// Convertir acción a estado
$status = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';

try {
    $prorrogaLogic = new ProrrogaLogic();
    $resultado = $prorrogaLogic->responderSolicitud($request_id, $status, $current_user_id, $comentario);
    
    if ($resultado['success']) {
        $mySessionController->save('prorroga_success', $resultado['message']);
    } else {
        $mySessionController->save('prorroga_error', $resultado['message']);
    }
} catch (Exception $e) {
    $mySessionController->save('prorroga_error', 'Error al procesar la respuesta: ' . $e->getMessage());
}

// Redirigir al detalle o al listado
header("Location: ../../../detalle_prorroga.php?id=" . $request_id);
exit;
