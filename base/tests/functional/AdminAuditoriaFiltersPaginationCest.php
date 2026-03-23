<?php

declare(strict_types=1);

namespace Tests\functional;

use mysqli;
use RuntimeException;
use Tests\Support\FunctionalTester;

final class AdminAuditoriaFiltersPaginationCest
{
    private const TEST_PASSWORD = 'TmpAudit#2026';

    private string $adminId = '';
    private string $originalAdminPass = '';
    private string $marker = '';

    public function _before(FunctionalTester $I): void
    {
        $this->marker = 'AUDIT_TEST_' . date('YmdHis') . '_' . (string) random_int(1000, 9999);

        $db = $this->connectDb();
        [$this->adminId, $this->originalAdminPass] = $this->grabAdminCredentials($db);
        $this->setAdminPassword($db, $this->adminId, self::TEST_PASSWORD);
        $db->close();
    }

    public function _after(FunctionalTester $I): void
    {
        $db = $this->connectDb();

        if ($this->adminId !== '' && $this->originalAdminPass !== '') {
            $this->restoreAdminPassword($db, $this->adminId, $this->originalAdminPass);
        }
        $db->close();
    }

    public function vistaAuditoriaMuestraFiltrosYPaginacion(FunctionalTester $I): void
    {
        $db = $this->connectDb();
        $this->seedAuditLogs($db, $this->adminId, 'LOGIN', 'FAIL', 12);
        $db->close();

        $this->loginAsAdmin($I);

        $I->amOnPage('/admin_auditoria.php');
        $I->see('Auditoría de Accesos');
        $I->see('Filtro por resultado');
        $I->see('Mostrando 10 por página');
        $I->see('Página 1 de');
        $I->see('Anterior');
        $I->see('Siguiente');
        $I->see('Aplicar fechas');
        $I->seeLink('Limpiar filtros');
    }

    public function filtroResultadoYFechaFuncionaYPermiteLimpiar(FunctionalTester $I): void
    {
        $today = date('Y-m-d');

        $db = $this->connectDb();
        $this->seedAuditLogs($db, $this->adminId, 'SECURITY_ALERT', 'ALERT', 1);
        $db->close();

        $this->loginAsAdmin($I);

        $I->amOnPage('/admin_auditoria.php?resultado=ALERT&fecha_desde=' . $today . '&fecha_hasta=' . $today);
        $I->see('Auditoría de Accesos');
        $I->seeInCurrentUrl('resultado=ALERT');
        $I->seeInCurrentUrl('fecha_desde=' . $today);
        $I->seeInCurrentUrl('fecha_hasta=' . $today);
        $I->see('ALERTA');

        $clearHref = $I->grabAttributeFrom("//a[contains(normalize-space(.), 'Limpiar filtros')]", 'href');
        $I->assertEquals('admin_auditoria.php', $clearHref, 'El botón Limpiar filtros no reinicia todos los filtros.');
    }

    private function loginAsAdmin(FunctionalTester $I): void
    {
        $I->amOnPage('/login.php');
        $I->submitForm('form', [
            'user' => $this->adminId,
            'pass' => self::TEST_PASSWORD,
        ]);

        $I->amOnPage('/admin_auditoria.php');
        $I->see('Auditoría de Accesos');
    }

    private function connectDb(): mysqli
    {
        require __DIR__ . '/../../inc/db/bdcommon.inc';

        $conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($conn->connect_error) {
            throw new RuntimeException('Error de conexión BD en test funcional: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8');
        return $conn;
    }

    private function grabAdminCredentials(mysqli $db): array
    {
        $stmt = $db->prepare('SELECT id, pass FROM sis_login WHERE id_roll = ? ORDER BY id ASC LIMIT 1');
        $role = 1;
        $stmt->bind_param('i', $role);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res || !isset($res['id'], $res['pass'])) {
            throw new RuntimeException('No se encontró administrador real (rol 1) para la prueba funcional.');
        }

        return [(string)$res['id'], (string)$res['pass']];
    }

    private function setAdminPassword(mysqli $db, string $adminId, string $plainPassword): void
    {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE sis_login SET pass = ? WHERE id = ?');
        $stmt->bind_param('ss', $newHash, $adminId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            throw new RuntimeException('No fue posible ajustar contraseña temporal de admin para la prueba.');
        }
    }

    private function restoreAdminPassword(mysqli $db, string $adminId, string $originalHash): void
    {
        $stmt = $db->prepare('UPDATE sis_login SET pass = ? WHERE id = ?');
        $stmt->bind_param('ss', $originalHash, $adminId);
        $stmt->execute();
        $stmt->close();
    }

    private function seedAuditLogs(mysqli $db, string $userId, string $actionType, string $actionResult, int $count): void
    {
        $stmt = $db->prepare(
            'INSERT INTO sis_log (id_user, date_bi, action_type, action_result, ip_address, device_info, detail)
             VALUES (?, NOW(), ?, ?, ?, ?, ?)'
        );

        $ip = '127.0.0.1';
        $device = 'Codeception-Functional';

        for ($index = 0; $index < $count; $index++) {
            $detail = $this->marker . ' #' . ($index + 1);
            $stmt->bind_param('ssssss', $userId, $actionType, $actionResult, $ip, $device, $detail);
            $stmt->execute();
        }

        $stmt->close();
    }

}
