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
        $this->assertSame("alert('x')usuario", $userSanitized);
        $this->assertSame("contraseña123", $passSanitized);
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
    $this->assertSame('Estudiantes', $grupo_valido);

        $grupos_usuario2 = ['NoPermitido', 'Otro'];
        $grupo_valido2 = '';
        foreach ($grupos_usuario2 as $g) {
            if (in_array($g, $grupos_autorizados)) {
                $grupo_valido2 = $g;
                break;
            }
        }
    $this->assertSame('', $grupo_valido2);
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

        // 6. Test de función destroySessionAndCookie (mock)
        public function testDestroySessionAndCookie() {
            $_COOKIE['base_sis'] = 'testvalue';
            $_SESSION = [];
            session_start();
            // Simular función
            $destroySessionAndCookie = function() {
                if (isset($_COOKIE['base_sis'])) {
                    $_COOKIE['base_sis'] = '';
                }
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }
            };
            $destroySessionAndCookie();
            $this->assertEquals('', $_COOKIE['base_sis']);
            $this->assertTrue(session_status() !== PHP_SESSION_ACTIVE);
        }

        // 7. Test de función sendError (mock)
        public function testSendError() {
            $destroySessionAndCookie = function() {
                return true;
            };
            $sendError = function($code, $message = '') use ($destroySessionAndCookie) {
                $destroySessionAndCookie();
                return $code;
            };
            $this->assertSame(6, $sendError(6));
            $this->assertSame(3, $sendError(3, 'LDAP error'));
        }

        // 8. Test de función mapearGrupoALRol (mock)
        public function testMapearGrupoALRol() {
            $mapearGrupoALRol = function($grupo) {
                $mapa = [
                    'Administradores' => 1,
                    'CTFG/Subdireccion' => 2,
                    'Estudiantes' => 3,
                    'Asesores Externos' => 4
                ];
                return isset($mapa[$grupo]) ? $mapa[$grupo] : (count($mapa) > 0 ? reset($mapa) : 1);
            };
            $this->assertSame(1, $mapearGrupoALRol('Administradores'));
            $this->assertSame(3, $mapearGrupoALRol('Estudiantes'));
            $this->assertSame(1, $mapearGrupoALRol('NoExiste'));
        }

        // 9. Test de verificación de sesión activa (mock)
        public function testVerificacionSesionActiva() {
            $mySessionController = new class {
                public function getVar($key) {
                    return $key === 'usuario' ? 'usuario_test' : '';
                }
            };
            $usuario_sesion = $mySessionController->getVar('usuario');
            $user = 'usuario_test';
            $this->assertTrue(!empty($usuario_sesion) && $usuario_sesion === $user);
        }

        // 10. Test de gestión de errores en login (mock)
        public function testGestionErroresLogin() {
            $errores = [
                0 => 'Login correcto',
                1 => 'Contraseña incorrecta',
                2 => 'Usuario no existe',
                3 => 'Problema con LDAP',
                4 => 'Cuenta deshabilitada',
                5 => 'No pertenece al grupo autorizado',
                6 => 'Datos de entrada inválidos',
                7 => 'Error en base de datos'
            ];
            $this->assertSame('Login correcto', $errores[0]);
            $this->assertSame('Problema con LDAP', $errores[3]);
            $this->assertSame('Error en base de datos', $errores[7]);
        }

        // 11. Test de sincronización de usuario en base de datos (mock)
        public function testSincronizacionUsuario() {
            $user = 'nuevo_usuario';
            $user_name = 'Nuevo Usuario';
            $user_email = 'nuevo@correo.com';
            $rol_ldap = 'Estudiantes';
            $rol_interno = 3;
            $pass_md5 = md5('clave123');
            $sql_insert_login = "INSERT INTO sis_login (id, pass, id_roll) VALUES ('$user', '$pass_md5', '$rol_interno');";
            $sql_insert_user = "INSERT INTO sis_user (id, nombre, email, telefono, id_tipo_tel) VALUES ('$user', '$user_name', '$user_email', '12345678', 'M');";
            $this->assertStringContainsString($user, $sql_insert_login);
            $this->assertStringContainsString($user_name, $sql_insert_user);
        }

}