<?php
require_once __DIR__ . '/../mod/login/check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$current_user_id = $mySessionController->getVar('usuario');
$_ga_host = isset($db_host) ? $db_host : 'localhost';
$_ga_user = isset($usuario) ? $usuario : 'root';
$_ga_pass = isset($clave)   ? $clave   : '';
$_ga_db   = isset($db)      ? $db      : 'base_db';
$conn = new mysqli($_ga_host, $_ga_user, $_ga_pass, $_ga_db);
$conn->set_charset('utf8');

$googleCalendarService = new GoogleCalendarService($conn);
$authUrl = $googleCalendarService->getAuthUrl();

header('Location: ' . $authUrl);
exit;