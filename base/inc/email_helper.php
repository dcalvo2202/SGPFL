<?php
/**
 * Funciones helper para el envío de correos electrónicos
 * Proporciona configuración centralizada del remitente
 */

if (!defined('SYSTEM_EMAIL_FROM')) {
    define('SYSTEM_EMAIL_FROM', 'noreply@una.cr');
}
if (!defined('SYSTEM_EMAIL_FROM_NAME')) {
    define('SYSTEM_EMAIL_FROM_NAME', 'SGPFL - Escuela de Informática');
}
if (!defined('SYSTEM_EMAIL_REPLY_TO')) {
    define('SYSTEM_EMAIL_REPLY_TO', 'escinf@una.cr');
}

/**
 * Genera los headers estándar para correos del sistema
 * @param string|null $customFrom Personalizar el remitente (opcional)
 * @return string Headers generados
 */
function getStandardEmailHeaders($customFrom = null) {
    $fromEmail = $customFrom ?: SYSTEM_EMAIL_FROM;
    $fromName = SYSTEM_EMAIL_FROM_NAME;
    $replyTo = SYSTEM_EMAIL_REPLY_TO;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return $headers;
}

/**
 * Envía un correo usando la configuración centralizada
 * @param string $to Destinatario
 * @param string $subject Asunto
 * @param string $body Cuerpo del mensaje (HTML)
 * @param string|null $customFrom Remitente personalizado (opcional)
 * @return bool True si se envió, false si falló
 */
function sendEmail($to, $subject, $body, $customFrom = null) {
    $headers = getStandardEmailHeaders($customFrom);
    return mail($to, $subject, $body, $headers);
}

/**
 * Envía un correo con copia oculta para monitoreo
 * @param string $to Destinatario principal
 * @param string $subject Asunto
 * @param string $body Cuerpo del mensaje (HTML)
 * @param string|null $customFrom Remitente personalizado
 * @param string|null $bcc Correo para copia oculta (opcional)
 * @return bool True si se envió, false si falló
 */
function sendEmailWithBcc($to, $subject, $body, $customFrom = null, $bcc = null) {
    $headers = getStandardEmailHeaders($customFrom);
    
    if ($bcc) {
        $headers .= "Bcc: {$bcc}\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}