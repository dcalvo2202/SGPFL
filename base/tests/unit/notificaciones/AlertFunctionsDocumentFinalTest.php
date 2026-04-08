<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakeAlertDb.php';
require_once __DIR__ . '/../../../inc/alert_functions.php';

final class AlertFunctionsDocumentFinalTest extends TestCase
{
    public function testRegisterFinalDocumentSubmittedAlertNotificaAGestoresYCTFG(): void
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
            $this->assertStringContainsString('El documento está pendiente de revisión inicial.', $alert['message']);
        }
    }

    public function testExistsRecentDuplicateCommitteeAlertRetornaTrueSiExisteUnaIgualReciente(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts[] = [
            'id' => 1,
            'user_id' => '800870458',
            'subject' => 'Nuevo Documento Final TFG Recibido',
            'message' => 'Mensaje',
            'alert_type' => 'Documento Final',
            'priority' => 'Alta',
            'related_entity_type' => 'document',
            'related_entity_id' => 123,
            'read_at' => null,
            'sent_at' => date('Y-m-d H:i:s', time() - 15),
        ];

        $result = existsRecentDuplicateCommitteeAlert(
            $conn,
            '800870458',
            'Nuevo Documento Final TFG Recibido',
            'Documento Final',
            'document',
            123,
            60
        );

        $this->assertTrue($result);
    }

    public function testExistsRecentDuplicateCommitteeAlertRetornaFalseSiLaAlertaEsVieja(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts[] = [
            'id' => 1,
            'user_id' => '800870458',
            'subject' => 'Nuevo Documento Final TFG Recibido',
            'message' => 'Mensaje',
            'alert_type' => 'Documento Final',
            'priority' => 'Alta',
            'related_entity_type' => 'document',
            'related_entity_id' => 123,
            'read_at' => null,
            'sent_at' => date('Y-m-d H:i:s', time() - 120),
        ];

        $result = existsRecentDuplicateCommitteeAlert(
            $conn,
            '800870458',
            'Nuevo Documento Final TFG Recibido',
            'Documento Final',
            'document',
            123,
            60
        );

        $this->assertFalse($result);
    }
}