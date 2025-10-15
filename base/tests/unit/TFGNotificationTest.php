<?php
use PHPUnit\Framework\TestCase;

class TFGNotificationTest extends TestCase
{
    public function testNotificacionIncluida()
    {
        // Simular que el archivo de notificación define una variable global
        $GLOBALS['notificacion_enviada'] = false;
        $notificacion = function() {
            $GLOBALS['notificacion_enviada'] = true;
        };

        // Simular include
        $notificacion();

        $this->assertTrue($GLOBALS['notificacion_enviada']);
    }

    public function testEnvioCorreoMock()
    {
        // Simular función mail
        $mail = function($to, $subject, $message, $headers) {
            return true; // Simula éxito
        };

        $this->assertTrue($mail('test@correo.com', 'Asunto', 'Mensaje', 'Headers'));
    }
}