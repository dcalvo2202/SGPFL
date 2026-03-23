<?php
use PHPUnit\Framework\TestCase;

class LoginAccessAuditHU024SourceTest extends TestCase
{
    private function readSource(string $relativePath): string
    {
        $path = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($path, "No existe el archivo esperado: {$relativePath}");

        $content = file_get_contents($path);
        $this->assertNotFalse($content, "No se pudo leer: {$relativePath}");

        return (string) $content;
    }

    public function testCredencialesRealesDelEscenarioExistenEnBaseSqlConHashDeSecret123(): void
    {
        $sql = $this->readSource('base.sql');
        $secret123Hash = md5('secret123');

        $fixtures = [
            ['id' => '105710421', 'rol' => '5'], // Asesor
            ['id' => '503230754', 'rol' => '3'], // CTFG/Comisión
            ['id' => '118440202', 'rol' => '4'], // Estudiante
            ['id' => '116440018', 'rol' => '4'], // Estudiante sin propuesta
            ['id' => '110600492', 'rol' => '3'], // Comisión
            ['id' => '111710169', 'rol' => '2'], // Gestor académico
            ['id' => '205610158', 'rol' => '1'], // Administrador
        ];

        foreach ($fixtures as $fixture) {
            $expected = "INSERT INTO `sis_login` VALUES ('{$fixture['id']}', '{$secret123Hash}', '{$fixture['rol']}');";
            $this->assertStringContainsString(
                $expected,
                $sql,
                "No se encontró el usuario {$fixture['id']} con hash de secret123 y rol {$fixture['rol']} en base.sql"
            );
        }
    }

    public function testHu024RegistraIpYDispositivoEnCodigoYEsquema(): void
    {
        $loginSrc = $this->readSource('mod/login/ajax_login.php');
        $sql = $this->readSource('base.sql');

        $this->assertStringContainsString("function getClientIpAddress()", $loginSrc);
        $this->assertStringContainsString("function getClientDeviceInfo()", $loginSrc);
        $this->assertStringContainsString("INSERT INTO sis_log (id_user, date_bi, action_type, action_result, ip_address, device_info, detail)", $loginSrc);
        $this->assertStringContainsString("'LOGIN', 'SUCCESS'", $loginSrc);
        $this->assertStringContainsString("'LOGIN', 'FAIL'", $loginSrc);

        $this->assertStringContainsString("`ip_address` varchar(45)", $sql);
        $this->assertStringContainsString("`device_info` varchar(255)", $sql);
        $this->assertStringContainsString("PROCEDURE `insert_access_log`", $sql);
        $this->assertStringContainsString("p_ip_address", $sql);
        $this->assertStringContainsString("p_device_info", $sql);
    }

    public function testHu024AlertaIntentosFallidosYNoOmiteAuditoriaEnSalidasTempranas(): void
    {
        $src = $this->readSource('mod/login/ajax_login.php');

        $this->assertStringContainsString("define('LOGIN_FAILED_ATTEMPT_WINDOW_MINUTES', 15)", $src);
        $this->assertStringContainsString("define('LOGIN_FAILED_ATTEMPT_THRESHOLD', 5)", $src);
        $this->assertStringContainsString("function shouldRaiseFailedLoginAlert", $src);
        $this->assertStringContainsString("action_result = 'FAIL'", $src);
        $this->assertStringContainsString("registrarAuditoriaAcceso(", $src);
        $this->assertStringContainsString("'SECURITY_ALERT', 'ALERT'", $src);
        $this->assertStringContainsString('if (in_array((int)$out, [1, 2, 5], true) && shouldRaiseFailedLoginAlert', $src);

        $this->assertStringContainsString('if (!$fp) {', $src);
        $this->assertStringContainsString("servidor LDAP no disponible (socket)", $src);
        $this->assertStringContainsString("sendError(3)", $src);

        $this->assertStringContainsString('if (empty($grupos_usuario))', $src);
        $this->assertStringContainsString("usuario LDAP sin grupos autorizados asociados", $src);
        $this->assertStringContainsString("sendError(5)", $src);

        $this->assertStringContainsString("attempted_user=", $src);
        $this->assertStringContainsString("null,", $src);
    }

    public function testHu024LogsInalterablesYRetencionCincoAniosEnBaseSql(): void
    {
        $sql = $this->readSource('base.sql');

        $this->assertStringContainsString("CREATE TRIGGER `trg_sis_log_no_update` BEFORE UPDATE ON `sis_log`", $sql);
        $this->assertStringContainsString("no se permite UPDATE", $sql);
        $this->assertStringContainsString("CREATE TRIGGER `trg_sis_log_no_delete` BEFORE DELETE ON `sis_log`", $sql);
        $this->assertStringContainsString("no se permite DELETE", $sql);

        $this->assertStringContainsString("PROCEDURE `purge_old_sis_log`", $sql);
        $this->assertStringContainsString("DATE_SUB(NOW(), INTERVAL 5 YEAR)", $sql);
        $this->assertStringContainsString("EVENT `evt_purge_old_sis_log`", $sql);
        $this->assertStringContainsString("ON SCHEDULE EVERY 1 DAY", $sql);
        $this->assertStringContainsString("CALL `purge_old_sis_log`(@deleted_rows)", $sql);
    }
}
