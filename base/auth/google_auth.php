<?php
require_once __DIR__ . '/../mod/login/check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$current_user_id = $mySessionController->getVar('usuario');
$conn = new mysqli($db_host, $usuario, $clave, $db);
$conn->set_charset('utf8');

$googleCalendarService = new GoogleCalendarService($conn);
$authUrl = $googleCalendarService->getAuthUrl();

header('Location: ' . $authUrl);
exit;
