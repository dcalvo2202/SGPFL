<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Envío de Correo</h2>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

$to = "rodri100ro@gmail.com";
$subject = "Test SGPFL - " . date('H:i:s');
$message = "<b>Este es un correo de prueba</b> enviado a las " . date('H:i:s');
$headers = "From: rodri100ro@gmail.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";

echo "<p>Intentando enviar correo...</p>";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color:green;'><strong>✓ mail() retornó TRUE</strong></p>";
    echo "<p>Revisa la bandeja de entrada en: $to</p>";
    echo "<p>Debug log: C:\\xampp\\tmp\\sendmail_debug.log</p>";
} else {
    echo "<p style='color:red;'><strong>✗ mail() retornó FALSE</strong></p>";
    echo "<p>Revisa los logs en C:\\xampp\\tmp\\</p>";
}

echo "<hr>";
echo "<h3>Configuración PHP:</h3>";
echo "<pre>";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "</pre>";
?>
