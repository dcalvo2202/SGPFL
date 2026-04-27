<?php
session_start();
require_once __DIR__ . '/../includes.php';

use Service\GoogleCalendarService;

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$googleCalendarService = new GoogleCalendarService($conn);
$authUrl = $googleCalendarService->getAuthUrl();

header('Location: ' . $authUrl);
exit;

exit;
?>
