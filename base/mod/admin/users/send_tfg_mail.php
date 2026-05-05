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

error_log("send_tfg_mail.php llamado - tipo: " . ($_POST['tipo'] ?? 'NULO'));
error_log("send_tfg_mail.php user_id: " . $user_id);

if (!$user_id) {
    error_log("send_tfg_mail.php - NO AUTENTICADO");
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

error_log("send_tfg_mail.php - Incluyendo tfg_update_document.php");

// Incluye solo la lógica de envío de correo
include_once(__DIR__ . '/tfg_update_document.php');

error_log("send_tfg_mail.php - Proceso completado");

echo json_encode(['success' => true, 'message' => 'Correo enviado']);
exit;
?>