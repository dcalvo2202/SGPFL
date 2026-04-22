<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

include_once(__DIR__ . '/../../../lib/mysession/mySession.class.php');
include_once(__DIR__ . '/../../../lib/mysession/mySession.conf.php');

$mySessionController = mySession::getIstance($_MYSESSION_CONF);
$user_id = $mySessionController->getVar("usuario");

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

include_once(__DIR__ . '/defense_agreement_mail_helper.php');

$result = sendDefenseAgreementMail($_POST);

echo json_encode($result);
exit;
?>