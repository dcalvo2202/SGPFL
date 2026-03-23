<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class AccessAuditHUAuditoriaCest
{
    private const LOGIN_ENDPOINT = 'http://localhost/base/mod/login/ajax_login.php';

    public function registraIntentoFallidoEnAuditoria(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $userId = $this->grabRealUserId($db);

        $beforeFailId = $this->grabMaxLogId($db, 'LOGIN', 'FAIL');

        $response = $this->postLogin($userId, 'clave_incorrecta_' . $this->uniqueSuffix());
        $responseCode = $this->extractLoginResponseCode($response);

        $I->assertTrue(
            in_array($responseCode, ['1', '2', '3', '5', '8'], true),
            'Respuesta inesperada en login fallido: ' . $response
        );

        $afterFailId = $this->grabMaxLogId($db, 'LOGIN', 'FAIL');
        $I->assertGreaterThan(
            $beforeFailId,
            $afterFailId,
            'No se registró un nuevo evento LOGIN/FAIL en sis_log.'
        );

        $db->close();
    }

    public function cadaCincoIntentosGeneraAlertaYNotificaAdmins(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $userId = $this->grabRealUserId($db);

        $adminIds = $this->grabAdminIds($db);
        $I->assertNotEmpty($adminIds, 'No existen usuarios admin (rol 1) para validar notificaciones.');

        $beforeSecurityAlertId = $this->grabMaxLogId($db, 'SECURITY_ALERT', 'ALERT');
        $beforeUserAlertId = $this->grabMaxUserAlertId($db);

        $triggered = false;
        $lastResponse = '';
        $lastResponseCode = '';

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $lastResponse = trim($this->postLogin($userId, 'clave_incorrecta_' . $this->uniqueSuffix()));
            $lastResponseCode = $this->extractLoginResponseCode($lastResponse);
            if ($lastResponseCode === '8') {
                $triggered = true;
                break;
            }
        }

        $I->assertTrue(
            $triggered,
            'No se disparó la alerta (código 8) en hasta 10 intentos. Última respuesta: ' . $lastResponse
        );

        $newSecurityAlerts = $this->countNewSecurityAlertsForUser($db, $beforeSecurityAlertId, $userId);
        $I->assertGreaterThan(
            0,
            $newSecurityAlerts,
            'No se registró SECURITY_ALERT/ALERT en sis_log después del umbral.'
        );

        $newAlertsPerAdmin = $this->grabDistinctAdminsWithNewSystemAlerts($db, $beforeUserAlertId);

        foreach ($adminIds as $adminId) {
            $I->assertTrue(
                in_array($adminId, $newAlertsPerAdmin, true),
                'El admin con ID ' . $adminId . ' no recibió notificación interna de alerta.'
            );
        }

        $db->close();
    }

    private function connectDb(): mysqli
    {
        require __DIR__ . '/../../inc/db/bdcommon.inc';

        $db = new mysqli($db_host, $usuario, $clave, $db);
        if ($db->connect_error) {
            throw new RuntimeException('Error conectando a BD para pruebas funcionales: ' . $db->connect_error);
        }

        $db->set_charset('utf8');
        return $db;
    }

    private function grabRealUserId(mysqli $db): string
    {
        $sql = "SELECT id FROM sis_login ORDER BY id_roll ASC, id ASC LIMIT 1";
        $res = $db->query($sql);
        if (!$res) {
            throw new RuntimeException('No fue posible consultar sis_login para obtener usuario real.');
        }

        $row = $res->fetch_assoc();
        if (!$row || !isset($row['id']) || trim((string)$row['id']) === '') {
            throw new RuntimeException('No hay usuarios en sis_login para ejecutar la prueba.');
        }

        return (string)$row['id'];
    }

    private function grabAdminIds(mysqli $db): array
    {
        $ids = [];
        $stmt = $db->prepare('SELECT id FROM sis_login WHERE id_roll = ?');
        $rol = 1;
        $stmt->bind_param('i', $rol);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $ids[] = (string)$row['id'];
        }

        $stmt->close();
        return $ids;
    }

    private function grabMaxLogId(mysqli $db, string $actionType, string $actionResult): int
    {
        $stmt = $db->prepare('SELECT COALESCE(MAX(id_bi), 0) AS max_id FROM sis_log WHERE action_type = ? AND action_result = ?');
        $stmt->bind_param('ss', $actionType, $actionResult);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($res['max_id']) ? (int)$res['max_id'] : 0;
    }

    private function grabMaxUserAlertId(mysqli $db): int
    {
        $res = $db->query('SELECT COALESCE(MAX(id), 0) AS max_id FROM user_alerts');
        if (!$res) {
            return 0;
        }

        $row = $res->fetch_assoc();
        return isset($row['max_id']) ? (int)$row['max_id'] : 0;
    }

    private function countNewSecurityAlertsForUser(mysqli $db, int $beforeId, string $userId): int
    {
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS total
             FROM sis_log
             WHERE id_bi > ?
               AND id_user = ?
               AND action_type = 'SECURITY_ALERT'
               AND action_result = 'ALERT'"
        );
        $stmt->bind_param('is', $beforeId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    private function grabDistinctAdminsWithNewSystemAlerts(mysqli $db, int $beforeUserAlertId): array
    {
        $ids = [];
        $sql = "SELECT DISTINCT user_id
                FROM user_alerts
                WHERE id > ?
                  AND alert_type = 'Sistema'
                  AND subject LIKE 'Alerta de seguridad:%intentos fallidos%'";

        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $beforeUserAlertId);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $ids[] = (string)$row['user_id'];
        }

        $stmt->close();
        return $ids;
    }

    private function postLogin(string $user, string $pass): string
    {
        $data = http_build_query([
            'user' => $user,
            'pass' => $pass,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                            "User-Agent: CodeceptionFunctionalTest/1.0\r\n" .
                            'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);

        $result = @file_get_contents(self::LOGIN_ENDPOINT, false, $context);
        if ($result === false) {
            throw new RuntimeException('No se pudo invocar ajax_login.php para la prueba funcional.');
        }

        return (string)$result;
    }

    private function extractLoginResponseCode(string $rawResponse): string
    {
        $plain = trim(strip_tags($rawResponse));
        if (preg_match('/([0-9])\s*$/', $plain, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function uniqueSuffix(): string
    {
        return date('YmdHis') . '-' . (string) random_int(1000, 9999);
    }
}
