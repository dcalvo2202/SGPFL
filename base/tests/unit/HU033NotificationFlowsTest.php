<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/notificaciones/FakeAlertDb.php';
require_once __DIR__ . '/../../inc/alert_functions.php';

final class HU033NotificationFlowsTest extends TestCase
{
    public function testRegisterProposalSubmittedAlertNotificaAGestoresYCTFG(): void
    {
        $conn = new FakeMysqli();

        $conn->roleUsers = [
            2 => ['111710169', '800810596'],
            3 => ['503020651', '503230754'],
        ];

        $count = registerProposalSubmittedAlert(
            $conn,
            'LARISSA SEGURA ARGUELLO',
            'Desarrollo de expediente clínico digital',
            200
        );

        $this->assertSame(4, $count);
        $this->assertCount(4, $conn->alerts);

        foreach ($conn->alerts as $alert) {
            $this->assertSame('Nueva Propuesta TFG Recibida', $alert['subject']);
            $this->assertSame('Nueva Propuesta', $alert['alert_type']);
            $this->assertSame('Media', $alert['priority']);
            $this->assertSame('proposal', $alert['related_entity_type']);
            $this->assertSame(200, $alert['related_entity_id']);
            $this->assertStringContainsString('Estudiante: LARISSA SEGURA ARGUELLO', $alert['message']);
            $this->assertStringContainsString('Título: Desarrollo de expediente clínico digital', $alert['message']);
            $this->assertStringContainsString('pendiente de revisión', $alert['message']);
        }
    }

    public function testRegisterGroupMemberAddedAlertNotificaAlMiembroAgregado(): void
    {
        $conn = new FakeMysqli();

        $result = registerGroupMemberAddedAlert(
            $conn,
            '503550224',
            'LARISSA SEGURA ARGUELLO',
            'Desarrollo de expediente clínico digital',
            55
        );

        $this->assertTrue($result);
        $this->assertCount(1, $conn->alerts);

        $alert = $conn->alerts[0];
        $this->assertSame('503550224', $alert['user_id']);
        $this->assertSame('Has sido agregado a un grupo TFG', $alert['subject']);
        $this->assertSame('Informativa', $alert['alert_type']);
        $this->assertSame('Media', $alert['priority']);
        $this->assertSame('project', $alert['related_entity_type']);
        $this->assertSame(55, $alert['related_entity_id']);
        $this->assertStringContainsString('LARISSA SEGURA ARGUELLO', $alert['message']);
        $this->assertStringContainsString('Desarrollo de expediente clínico digital', $alert['message']);
    }

    public function testRegisterFinalDocumentSubmittedAlertNotificaAGestoresYCTFGConPrioridadAlta(): void
    {
        $conn = new FakeMysqli();

        $conn->roleUsers = [
            2 => ['111710169', '800810596'],
            3 => ['503020651', '503230754'],
        ];

        $count = registerFinalDocumentSubmittedAlert(
            $conn,
            'LARISSA SEGURA ARGUELLO',
            'Desarrollo de expediente clínico digital',
            321
        );

        $this->assertSame(4, $count);
        $this->assertCount(4, $conn->alerts);

        foreach ($conn->alerts as $alert) {
            $this->assertSame('Nuevo Documento Final TFG Recibido', $alert['subject']);
            $this->assertSame('Documento Final', $alert['alert_type']);
            $this->assertSame('Alta', $alert['priority']);
            $this->assertSame('document', $alert['related_entity_type']);
            $this->assertSame(321, $alert['related_entity_id']);
            $this->assertStringContainsString('Estudiante: LARISSA SEGURA ARGUELLO', $alert['message']);
            $this->assertStringContainsString('El documento está pendiente de revisión inicial.', $alert['message']);
        }
    }

    public function testRegisterFinalDocumentCommitteeAlertOmiteUsuariosCubiertosYNotificaSoloAlComiteValido(): void
    {
        $conn = new FakeMysqli();

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

        $alert = $conn->alerts[0];
        $this->assertSame('800870458', $alert['user_id']);
        $this->assertSame('Nuevo Documento Final TFG Recibido', $alert['subject']);
        $this->assertSame('Documento Final', $alert['alert_type']);
        $this->assertSame('Alta', $alert['priority']);
        $this->assertSame('document', $alert['related_entity_type']);
        $this->assertSame(123, $alert['related_entity_id']);
        $this->assertStringContainsString('Entrega: Documento Final', $alert['message']);
    }

    public function testRegisterCorrectionSubmittedAlertNotificaAGestoresYCTFG(): void
    {
        $conn = new FakeMysqli();

        $conn->roleUsers = [
            2 => ['111710169', '800810596'],
            3 => ['503020651', '503230754'],
        ];

        $count = registerCorrectionSubmittedAlert(
            $conn,
            'LARISSA SEGURA ARGUELLO',
            'Desarrollo de expediente clínico digital',
            654
        );

        $this->assertSame(4, $count);
        $this->assertCount(4, $conn->alerts);

        foreach ($conn->alerts as $alert) {
            $this->assertSame('Corrección de Documento Final Recibida', $alert['subject']);
            $this->assertSame('Documento Final', $alert['alert_type']);
            $this->assertSame('Alta', $alert['priority']);
            $this->assertSame('document', $alert['related_entity_type']);
            $this->assertSame(654, $alert['related_entity_id']);
            $this->assertStringContainsString('Estudiante: LARISSA SEGURA ARGUELLO', $alert['message']);
            $this->assertStringContainsString('corregido está pendiente de revisión', $alert['message']);
        }
    }
}