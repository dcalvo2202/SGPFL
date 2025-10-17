<?php
header('Content-Type: application/json');

// Verificar método de petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar sesión/autenticación si es necesario
include_once(__DIR__ . '/../../../lib/mysession/mySession.class.php');
include_once(__DIR__ . '/../../../lib/mysession/mySession.conf.php');
$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$user_id = $mySessionController->getVar("usuario");
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Incluye solo la lógica de envío de correo
include_once(__DIR__ . '/tfg_update_document.php');

echo json_encode(['success' => true, 'message' => 'Correo enviado']);
exit;
?>