<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakeAlertDb.php';
require_once __DIR__ . '/../../../inc/alert_functions.php';

final class AlertFunctionsReadStateTest extends TestCase
{
    public function testGetUserAlertsPuedeFiltrarSoloNoLeidasYOrdenaPorFechaDescendente(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts = [
            [
                'id' => 1,
                'user_id' => '111710169',
                'subject' => 'A1',
                'message' => 'M1',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 10,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => '111710169',
                'subject' => 'A2',
                'message' => 'M2',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 11,
                'read_at' => '2026-04-08 10:10:00',
                'sent_at' => '2026-04-08 10:05:00',
            ],
            [
                'id' => 3,
                'user_id' => '111710169',
                'subject' => 'A3',
                'message' => 'M3',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 12,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:20:00',
            ],
        ];

        $alerts = getUserAlerts($conn, '111710169', 10, true);

        $this->assertCount(2, $alerts);
        $this->assertSame(3, $alerts[0]['id']);
        $this->assertSame(1, $alerts[1]['id']);
    }

    public function testGetUnreadAlertCountCuentaCorrectamente(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts = [
            [
                'id' => 1,
                'user_id' => '111710169',
                'subject' => 'A1',
                'message' => 'M1',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 10,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => '111710169',
                'subject' => 'A2',
                'message' => 'M2',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 11,
                'read_at' => '2026-04-08 11:00:00',
                'sent_at' => '2026-04-08 10:10:00',
            ],
            [
                'id' => 3,
                'user_id' => '111710169',
                'subject' => 'A3',
                'message' => 'M3',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 12,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:20:00',
            ],
        ];

        $count = getUnreadAlertCount($conn, '111710169');

        $this->assertSame(2, $count);
    }

    public function testMarkAlertAsReadMarcaSoloLaAlertaIndicada(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts = [
            [
                'id' => 10,
                'user_id' => '111710169',
                'subject' => 'A1',
                'message' => 'M1',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 20,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:00:00',
            ],
            [
                'id' => 11,
                'user_id' => '111710169',
                'subject' => 'A2',
                'message' => 'M2',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 21,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:05:00',
            ],
        ];

        $result = markAlertAsRead($conn, 10, '111710169');

        $this->assertTrue($result);
        $this->assertNotNull($conn->alerts[0]['read_at']);
        $this->assertNull($conn->alerts[1]['read_at']);
    }

    public function testMarkAllAlertsAsReadRetornaCantidadMarcada(): void
    {
        $conn = new FakeMysqli();

        $conn->alerts = [
            [
                'id' => 1,
                'user_id' => '111710169',
                'subject' => 'A1',
                'message' => 'M1',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 10,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => '111710169',
                'subject' => 'A2',
                'message' => 'M2',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 11,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:05:00',
            ],
            [
                'id' => 3,
                'user_id' => '503020651',
                'subject' => 'A3',
                'message' => 'M3',
                'alert_type' => 'Documento Final',
                'priority' => 'Alta',
                'related_entity_type' => 'document',
                'related_entity_id' => 12,
                'read_at' => null,
                'sent_at' => '2026-04-08 10:10:00',
            ],
        ];

        $affected = markAllAlertsAsRead($conn, '111710169');

        $this->assertSame(2, $affected);
        $this->assertNotNull($conn->alerts[0]['read_at']);
        $this->assertNotNull($conn->alerts[1]['read_at']);
        $this->assertNull($conn->alerts[2]['read_at']);
    }
}