<?php
require_once __DIR__ . '/../mod/login/check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$current_user_id = $mySessionController->getVar('usuario');
$conn = new mysqli($db_host, $usuario, $clave, $db);
$conn->set_charset('utf8');

if (!isset($_GET['code'])) {
    $mySessionController->save('error', 'Error en la autenticación de Google Calendar');
    header('Location: ../perfil.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);

if ($googleCalendarService->handleAuthCallback($_GET['code'], $current_user_id)) {
    $mySessionController->save('success', 'Google Calendar conectado correctamente');
} else {
    $mySessionController->save('error', 'Error al conectar Google Calendar');
}

header('Location: ../perfil.php');
exit;
