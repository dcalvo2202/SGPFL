<?php
require_once __DIR__ . '/../mod/login/check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$current_user_id = $mySessionController->getVar('usuario');
$conn = new mysqli($db_host, $usuario, $clave, $db);
$conn->set_charset('utf8');

$googleCalendarService = new GoogleCalendarService($conn);
$googleCalendarService->disconnectUser($current_user_id);

$mySessionController->save('success', 'Google Calendar desconectado correctamente');
header('Location: ../perfil.php');
exit;