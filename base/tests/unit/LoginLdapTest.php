<?php
use PHPUnit\Framework\TestCase;

// Solo incluir AuthLdap si el archivo existe, para evitar error en tests independientes
$authLdapPath = __DIR__ . '/../../lib/AuthLdap/class.AuthLdap.php';
if (file_exists($authLdapPath)) {
    require_once $authLdapPath;
}

class LoginLdapTest extends TestCase {

    // 1. Test de sanitización de entradas
    public function testSanitizacionEntradas() {
        $user = "<script>alert('x')</script>usuario";
        $pass = "   contraseña123  ";
    // Sanitizar eliminando etiquetas HTML completas
    $userSanitized = strip_tags(trim($user));
    $passSanitized = trim($pass);
    $this->assertNotEmpty($userSanitized);
    $this->assertEquals("alert('x')usuario", $userSanitized);
    $this->assertEquals("contraseña123", $passSanitized);
    }

    // 2. Test de función checkPass (mock)
    public function testCheckPassEscenarios() {
        // Solo ejecutar si la clase AuthLdap está definida
        if (class_exists('AuthLdap')) {
            $ldap = $this->getMockBuilder(AuthLdap::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['checkPass'])
                ->getMock();
            // Usuario correcto
            $ldap->method('checkPass')->willReturnOnConsecutiveCalls(true, false, false, false);
            $this->assertTrue($ldap->checkPass('usuario1', 'pass1'));
            // Usuario deshabilitado (simulado como false)
            $this->assertFalse($ldap->checkPass('usuario_deshabilitado', 'pass2'));
            // Usuario inexistente
            $this->assertFalse($ldap->checkPass('noexiste', 'pass3'));
            // Contraseña incorrecta
            $this->assertFalse($ldap->checkPass('usuario1', 'malpass'));
        } else {
            $this->markTestSkipped('AuthLdap no está disponible, se omite testCheckPassEscenarios.');
        }
    }

    // 3. Test de función de grupo autorizado
    public function testGrupoAutorizado() {
        $grupos_autorizados = ['Administradores', 'CTFG/Subdireccion', 'Estudiantes', 'Asesores Externos'];
        $grupos_usuario = ['Estudiantes', 'OtroGrupo'];
        $grupo_valido = '';
        foreach ($grupos_usuario as $g) {
            if (in_array($g, $grupos_autorizados)) {
                $grupo_valido = $g;
                break;
            }
        }
        $this->assertEquals('Estudiantes', $grupo_valido);

        $grupos_usuario2 = ['NoPermitido', 'Otro'];
        $grupo_valido2 = '';
        foreach ($grupos_usuario2 as $g) {
            if (in_array($g, $grupos_autorizados)) {
                $grupo_valido2 = $g;
                break;
            }
        }
        $this->assertEquals('', $grupo_valido2);
    }

    // 4. Test de inserción en base de datos (mock)
    public function testInsercionBaseDeDatos() {
        // Simular función transaccion
        $transaccion = function($sql) {
            if (strpos($sql, 'ERROR') !== false) return false;
            return true;
        };
        $this->assertTrue($transaccion("INSERT INTO sis_login ..."));
        $this->assertFalse($transaccion("INSERT INTO sis_login ... ERROR"));
    }

    // 5. Test de logging de sincronización
    public function testLoggingSincronizacion() {
        $logfile = __DIR__ . '/ldap_sync_test.log';
        if (file_exists($logfile)) unlink($logfile);
        $user = 'testuser';
        $user_name = 'Test User';
        $user_email = 'test@correo.com';
        $rol_ldap = 'Estudiantes';
        $sync_msg = date('Y-m-d H:i:s') . " | Nuevo usuario sincronizado | ID: $user | Nombre: $user_name | Email: $user_email | Rol: $rol_ldap\n";
        file_put_contents($logfile, $sync_msg, FILE_APPEND);
        $this->assertFileExists($logfile);
        $contenido = file_get_contents($logfile);
        $this->assertStringContainsString($user, $contenido);
        unlink($logfile);
    }
}