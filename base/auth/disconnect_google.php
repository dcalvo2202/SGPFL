<?php
session_start();
require_once __DIR__ . '/../includes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);
$googleCalendarService->disconnectUser($_SESSION['id_user']);

$_SESSION['success'] = 'Google Calendar desconectado correctamente';
header('Location: ../perfil.php');
exit;
?>