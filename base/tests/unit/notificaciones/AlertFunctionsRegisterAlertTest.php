<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakeAlertDb.php';
require_once __DIR__ . '/../../../inc/alert_functions.php';

final class AlertFunctionsRegisterAlertTest extends TestCase
{
    public function testRegisterAlertGuardaLaAlertaYRetornaTrue(): void
    {
        $conn = new FakeMysqli();

        $result = registerAlert(
            $conn,
            '111710169',
            'Asunto de prueba',
            'Mensaje de prueba',
            'Documento Final',
            'Alta',
            'document',
            25
        );

        $this->assertTrue($result);
        $this->assertCount(1, $conn->alerts);
        $this->assertSame('111710169', $conn->alerts[0]['user_id']);
        $this->assertSame('Asunto de prueba', $conn->alerts[0]['subject']);
        $this->assertSame('Documento Final', $conn->alerts[0]['alert_type']);
        $this->assertSame('Alta', $conn->alerts[0]['priority']);
        $this->assertSame('document', $conn->alerts[0]['related_entity_type']);
        $this->assertSame(25, $conn->alerts[0]['related_entity_id']);
    }

    public function testRegisterAlertRetornaFalseSiFallaPrepare(): void
    {
        $conn = new FakeMysqli();
        $conn->prepareFailPatterns = ['INSERT INTO user_alerts'];

        $result = registerAlert(
            $conn,
            '111710169',
            'Asunto',
            'Mensaje',
            'Documento Final',
            'Alta',
            'document',
            10
        );

        $this->assertFalse($result);
        $this->assertCount(0, $conn->alerts);
    }

    public function testRegisterAlertToRoleCuentaSoloLasInsercionesExitosas(): void
    {
        $conn = new FakeMysqli();
        $conn->roleUsers = [
            2 => ['111710169', '800810596', '205610158'],
        ];
        $conn->failAlertUsers = ['800810596'];

        $count = registerAlertToRole(
            $conn,
            2,
            'Nuevo Documento Final TFG Recibido',
            'Mensaje de prueba',
            'Documento Final',
            'Alta',
            'document',
            99
        );

        $this->assertSame(2, $count);
        $this->assertCount(2, $conn->alerts);
        $this->assertSame('111710169', $conn->alerts[0]['user_id']);
        $this->assertSame('205610158', $conn->alerts[1]['user_id']);
    }
}