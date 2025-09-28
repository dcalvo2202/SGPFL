<?php
use PHPUnit\Framework\TestCase;

class AjaxLoginIntegrationTest extends TestCase
{
    private $backupPost;

    protected function setUp(): void
    {
        // Respaldar $_POST
        $this->backupPost = $_POST;
        $_POST['user'] = 'rrodrigo123';
        $_POST['pass'] = 'secret123';
        // Limpiar sesión si es necesario
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    protected function tearDown(): void
    {
        // Restaurar $_POST
        $_POST = $this->backupPost;
    }

    public function testAjaxLoginFlow()
    {
        require_once __DIR__ . '/../../inc/db/db.php';
        $cwd = getcwd();
        chdir(__DIR__ . '/../../'); // Cambiar a la raíz del proyecto
        $obLevel = ob_get_level();
        ob_start();
        try {
            include 'mod/login/ajax_login.php';
            $output = ob_get_clean();
        } finally {
            // Cierra cualquier buffer extra abierto por el código probado
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            chdir($cwd); // Restaurar directorio original
        }

        // El valor esperado es 0 si todo salió bien
        $this->assertEquals('0', trim($output), 'El login AJAX no devolvió 0 (éxito)');
        // Validar que la sesión se haya creado correctamente
        if (class_exists('mySession')) {
            global $_MYSESSION_CONF;
            $mySessionController = mySession::getIstance($_MYSESSION_CONF);
            $this->assertEquals('rrodrigo123', $mySessionController->getVar('usuario'));
            $this->assertEquals('Rodrigo', $mySessionController->getVar('nombre'));
        }
    }
}