<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

include_once __DIR__ . '/../../../lib/mysession/mySession.class.php';
include_once __DIR__ . '/../../../lib/mysession/mySession.conf.php';

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$user_id = $mySessionController->getVar("usuario");
$user_rol = (int)$mySessionController->getVar("rol");

if (!$user_id || ($user_rol !== 3 && $user_rol !== 2)) {
    echo json_encode(['success' => false, 'message' => 'No autenticado o sin permisos']);
    exit;
}

$proyecto_id = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : 0;
if ($proyecto_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Proyecto inválido']);
    exit;
}

include_once __DIR__ . '/defense_agreement_mail_helper.php';

$result = sendDefenseAgreementMail($proyecto_id);

echo json_encode($result);
exit;
?>