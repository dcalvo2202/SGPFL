<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakeAlertDb.php';
require_once __DIR__ . '/../../../inc/alert_functions.php';

final class AlertFunctionsCommitteeTest extends TestCase
{
    public function testGetOfficialCommitteeUserIdsByProposalIdRetornaSoloUsuariosUnicosConLogin(): void
    {
        $conn = new FakeMysqli();

        $conn->committeeByProposal = [
            77 => [
                'tutor' => '800870458',
                'asesor_1' => '503020651',
                'asesor_2' => '503020651',
            ],
        ];

        $conn->loginRoles = [
            '800870458' => 5,
            '503020651' => 3,
        ];

        $result = getOfficialCommitteeUserIdsByProposalId($conn, 77);

        $this->assertSame(['800870458', '503020651'], $result);
    }

    public function testIsCoveredByGeneralFinalDocumentAlertEsTrueSoloParaRoles2Y3(): void
    {
        $conn = new FakeMysqli();
        $conn->loginRoles = [
            '111710169' => 2,
            '503020651' => 3,
            '800870458' => 5,
            '504410118' => 4,
        ];

        $this->assertTrue(isCoveredByGeneralFinalDocumentAlert($conn, '111710169'));
        $this->assertTrue(isCoveredByGeneralFinalDocumentAlert($conn, '503020651'));
        $this->assertFalse(isCoveredByGeneralFinalDocumentAlert($conn, '800870458'));
        $this->assertFalse(isCoveredByGeneralFinalDocumentAlert($conn, '504410118'));
        $this->assertFalse(isCoveredByGeneralFinalDocumentAlert($conn, '999999999'));
    }

    public function testRegisterFinalDocumentCommitteeAlertOmiteUsuariosCubiertosPorAlertaGeneral(): void
    {
        $conn = new FakeMysqli();

        // Similar al comité 9 del base.sql:
        // 800870458 = rol 5
        // 503020651 = rol 3
        // 503230754 = rol 3
        $conn->committeeByProposal = [
            500 => [
                'tutor' => '800870458',
                'asesor_1' => '503020651',
                'asesor_2' => '503230754',
            ],
        ];

        $conn->loginRoles = [
            '800870458' => 5,
            '503020651' => 3,
            '503230754' => 3,
        ];

        $count = registerFinalDocumentCommitteeAlert(
            $conn,
            500,
            'LARISSA SEGURA ARGUELLO',
            'Desarrollo de expediente clínico digital',
            123
        );

        $this->assertSame(1, $count);
        $this->assertCount(1, $conn->alerts);
        $this->assertSame('800870458', $conn->alerts[0]['user_id']);
        $this->assertSame('Nuevo Documento Final TFG Recibido', $conn->alerts[0]['subject']);
        $this->assertSame('Documento Final', $conn->alerts[0]['alert_type']);
        $this->assertSame(123, $conn->alerts[0]['related_entity_id']);
        $this->assertStringContainsString('Entrega: Documento Final', $conn->alerts[0]['message']);
    }

    public function testRegisterFinalDocumentCommitteeAlertRetornaCeroSiNoHayComite(): void
    {
        $conn = new FakeMysqli();

        $count = registerFinalDocumentCommitteeAlert(
            $conn,
            999,
            'Estudiante',
            'Proyecto X',
            45
        );

        $this->assertSame(0, $count);
        $this->assertCount(0, $conn->alerts);
    }
}