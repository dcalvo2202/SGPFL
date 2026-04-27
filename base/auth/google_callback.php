<?php
session_start();
require_once __DIR__ . '/../includes.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['code'])) {
    $_SESSION['error'] = 'Error en la autenticación de Google Calendar';
    header('Location: ../perfil.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);

if ($googleCalendarService->handleAuthCallback($_GET['code'], $_SESSION['id_user'])) {
    $_SESSION['success'] = 'Google Calendar conectado correctamente';
} else {
    $_SESSION['error'] = 'Error al conectar Google Calendar';
}

header('Location: ../perfil.php');
exit;
