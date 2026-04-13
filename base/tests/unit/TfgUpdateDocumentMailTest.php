<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

define('TFG_UPDATE_DOCUMENT_LOAD_ONLY', true);

require_once __DIR__ . '/../../mod/admin/users/tfg_update_document.php';

final class TfgUpdateDocumentMailTest extends TestCase
{
    public function testSendTfgNotificationEmailsInvocaMailerParaEstudianteYSecretaria(): void
    {
        $calls = [];

        $fakeMailer = function (
            string $to,
            string $subject,
            string $message,
            string $headers
        ) use (&$calls): bool {
            $calls[] = [
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
            ];

            return true;
        };

        $result = sendTfgNotificationEmails(
            'gabriel.test@est.una.ac.cr',
            'gabriel.test@gmail.com',
            'Asunto de prueba',
            '<p>Mensaje estudiante</p>',
            '<p>Mensaje secretaría</p>',
            "From: pruebas@example.com\r\nContent-Type:text/html;charset=UTF-8\r\n",
            $fakeMailer
        );

        $this->assertTrue($result['student']);
        $this->assertTrue($result['secretary']);
        $this->assertTrue($result['all_sent']);

        $this->assertCount(2, $calls);

        $this->assertSame('gabriel.test@est.una.ac.cr', $calls[0]['to']);
        $this->assertSame('Asunto de prueba', $calls[0]['subject']);
        $this->assertSame('<p>Mensaje estudiante</p>', $calls[0]['message']);

        $this->assertSame('gabriel.test@gmail.com', $calls[1]['to']);
        $this->assertSame('Asunto de prueba', $calls[1]['subject']);
        $this->assertSame('<p>Mensaje secretaría</p>', $calls[1]['message']);
    }

    public function testSendTfgNotificationEmailsRetornaFalseEnAllSentSiFallaUnoDeLosEnvios(): void
    {
        $calls = [];

        $fakeMailer = function (
            string $to,
            string $subject,
            string $message,
            string $headers
        ) use (&$calls): bool {
            $calls[] = $to;

            if ($to === 'gabriel.test@gmail.com') {
                return false;
            }

            return true;
        };

        $result = sendTfgNotificationEmails(
            'gabriel.test@est.una.ac.cr',
            'gabriel.test@gmail.com',
            'Asunto de prueba',
            '<p>Mensaje estudiante</p>',
            '<p>Mensaje secretaría</p>',
            "From: pruebas@example.com\r\nContent-Type:text/html;charset=UTF-8\r\n",
            $fakeMailer
        );

        $this->assertTrue($result['student']);
        $this->assertFalse($result['secretary']);
        $this->assertFalse($result['all_sent']);

        $this->assertCount(2, $calls);
        $this->assertSame('gabriel.test@est.una.ac.cr', $calls[0]);
        $this->assertSame('gabriel.test@gmail.com', $calls[1]);
    }

    public function testSendTfgNotificationEmailsRecibeCorreosControladosSinUsarUsuariosReales(): void
    {
        $capturedRecipients = [];

        $fakeMailer = function (
            string $to,
            string $subject,
            string $message,
            string $headers
        ) use (&$capturedRecipients): bool {
            $capturedRecipients[] = $to;
            return true;
        };

        sendTfgNotificationEmails(
            'mi_correo_pruebas@est.una.ac.cr',
            'mi_correo_pruebas@gmail.com',
            'HU-026',
            '<p>Estudiante</p>',
            '<p>Secretaría</p>',
            "From: pruebas@example.com\r\nContent-Type:text/html;charset=UTF-8\r\n",
            $fakeMailer
        );

        $this->assertSame(
            ['mi_correo_pruebas@est.una.ac.cr', 'mi_correo_pruebas@gmail.com'],
            $capturedRecipients
        );
    }
}