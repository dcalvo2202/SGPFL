<?php
require_once __DIR__ . '/../mod/login/check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$current_user_id = $mySessionController->getVar('usuario');
$_gc_host = isset($db_host) ? $db_host : 'localhost';
$_gc_user = isset($usuario) ? $usuario : 'root';
$_gc_pass = isset($clave)   ? $clave   : '';
$_gc_db   = isset($db)      ? $db      : 'base_db';
$conn = new mysqli($_gc_host, $_gc_user, $_gc_pass, $_gc_db);
$conn->set_charset('utf8');

if (!isset($_GET['code'])) {
    header('Location: ../perfil.php?gc_flash=1&gc_status=error&gc_msg=' . urlencode('Error en la autenticación de Google Calendar'));
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);

if ($googleCalendarService->handleAuthCallback($_GET['code'], $current_user_id)) {
    header('Location: ../perfil.php?gc_flash=1&gc_status=success&gc_msg=' . urlencode('Google Calendar conectado correctamente'));
} else {
    header('Location: ../perfil.php?gc_flash=1&gc_status=error&gc_msg=' . urlencode('Error al conectar Google Calendar'));
}
exit;
