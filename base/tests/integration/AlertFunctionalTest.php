<?php
/**
 * HU-037: Prueba Funcional e Integración del Historial de Alertas
 * 
 * Esta prueba recrea el comportamiento real de inserción, consulta 
 * y lectura de las notificaciones internas usando la base de datos oficial 
 * de configuración local. 
 */

use PHPUnit\Framework\TestCase;

// Importamos el archivo de configuración real y las rutinas de la HU-037
require_once __DIR__ . '/../../inc/alert_functions.php';

class AlertFunctionalTest extends TestCase
{
    private $conn;
    private string $usuarioTest = 'TEST_ALERTA_USR';
    private string $rolAdminID = '999';

    protected function setUp(): void
    {
        // 1. Establecer conexión real a la base de datos 
        include __DIR__ . '/../../inc/db/bdcommon.inc';
        
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            $this->fail('Hubo un error al intentar conectarse a la Base de Datos para las pruebas funcionales de HU-037: ' . $this->conn->connect_error);
        }

        // 2. Limpiar el terreno
        $this->limpiarDatosDePrueba();

        // 3. Crear el usuario ficticio necesario para recibir alertas
        $this->conn->query("INSERT IGNORE INTO sis_login (id, pass, id_roll) VALUES ('{$this->usuarioTest}', '123123', 4)");
        $this->conn->query("INSERT IGNORE INTO sis_user (id, nombre, email) VALUES ('{$this->usuarioTest}', 'Estudiante Test Alerta', 'alerta@test.com')");
    }

    protected function tearDown(): void
    {
        // Limpiamos los datos generados durante el test para no ensuciar la BD
        $this->limpiarDatosDePrueba();
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Helper para dejar limpia la base de datos antes y después.
     */
    private function limpiarDatosDePrueba()
    {
        if (!$this->conn || $this->conn->connect_error) return;
        
        // Cuidado de borrar en cascada
        $this->conn->query("DELETE FROM user_alerts WHERE user_id = '{$this->usuarioTest}'");
        
        // Borramos al usuario de prueba 
        $this->conn->query("DELETE FROM sis_user WHERE id = '{$this->usuarioTest}'");
        $this->conn->query("DELETE FROM sis_login WHERE id = '{$this->usuarioTest}'");
    }

    /**
     * @test
     * HU-037: Probamos la canalización completa del envío, recepción, 
     * conteo y cambio de estado de una notificación.
     */
    public function testCicloDeVidaDeUnaAlerta()
    {
        // ---------------------------------------------------------
        // Paso 1: Registrar alertas directas al usuario
        // ---------------------------------------------------------
        $exito1 = registerAlert(
            $this->conn, 
            $this->usuarioTest, 
            'Aprobación de Anteproyecto', 
            'Su documento principal fue revisado por el Gestor Académico.', 
            'Éxito', 
            'Alta', 
            'proposal', 
            1
        );
        $this->assertTrue($exito1, 'Debe lograr insertar la Alerta de tipo Éxito vía SQL');

        $exito2 = registerAlert(
            $this->conn, 
            $this->usuarioTest, 
            'Revisión Pendiente', 
            'Tiene que subir su documento en 2 días.', 
            'Advertencia', 
            'Media'
        );
        $this->assertTrue($exito2, 'Debe lograr insertar la segunda Alerta (Advertencia)');

        // ---------------------------------------------------------
        // Paso 2: Comprobar la Bandeja Integrada (Unread Count)
        // ---------------------------------------------------------
        $alertasNoLeidas = getUnreadAlertCount($this->conn, $this->usuarioTest);
        $this->assertEquals(2, $alertasNoLeidas, 'El usuario debe registrar exactamente 2 notificaciones nuevas (campanita)');

        // ---------------------------------------------------------
        // Paso 3: Retornar los mensajes de la Bandeja (Panel del Menú)
        // ---------------------------------------------------------
        $historialAlertas = getUserAlerts($this->conn, $this->usuarioTest, 10, false);
        $this->assertIsArray($historialAlertas, 'El historial debe devolver la información en arreglo para el ciclo del frontend');
        $this->assertCount(2, $historialAlertas, 'Deberían aparecer las dos alertas en array.');

        // ---------------------------------------------------------
        // Paso 4: Marcar una alerta como Leída
        // ---------------------------------------------------------
        // Tomamos la primera alerta más reciente en el arreglo para leerla
        $primerAlertaLeida = $historialAlertas[0]['id'];
        $marcaExitosa = markAlertAsRead($this->conn, $primerAlertaLeida, $this->usuarioTest);
        $this->assertTrue($marcaExitosa, 'La operación UPDATE para cambiar el flag is_read = 1 funcionó en la base de datos');

        // Validamos la campana otra vez 
        $alertasNoLeidasActualizado = getUnreadAlertCount($this->conn, $this->usuarioTest);
        $this->assertEquals(1, $alertasNoLeidasActualizado, 'La campana debe descontar 1 notificación tras leerla');

        // ---------------------------------------------------------
        // Paso 5: Marcar todas como Leídas
        // ---------------------------------------------------------
        $marcadoGeneralExitoso = markAllAlertsAsRead($this->conn, $this->usuarioTest);
        $this->assertNotFalse($marcadoGeneralExitoso, 'El botón de "Marcar todas como leídas" actualiza la base de datos exitosamente');

        $ceroTotal = getUnreadAlertCount($this->conn, $this->usuarioTest);
        $this->assertEquals(0, $ceroTotal, 'El contador general debería haber bajado a cero');
    }

    /**
     * @test
     * HU-037: Disparo de Alertas a nivel de Roles (Broadcast a Grupo). 
     * Esto busca al menos certificar que la consulta SQL no de crash, 
     * aunque depende de cuántos usuarios existan en `sis_login` para ese rol puntual.
     */
    public function testMultiCastAlertasPorRol()
    {
        // Enviar a todos los Administradores (id_roll = 1) no debe detonar caídas.
        $exitoBroadcast = registerAlertToRole(
            $this->conn,
            1,
            'Mantenimiento Mensual',
            'La base de datos de los Históricos quedará inactiva el domingo a medianoche',
            'Error',
            'Alta'
        );

        $this->assertNotFalse($exitoBroadcast, 'El envío masivo a un rol retorna filas afectadas o verdadero');

        // Limpieza de alertas enviadas a administradores post-test 
        // Eliminamos las que digan 'Mantenimiento Mensual' que mandamos para ensayo
        $this->conn->query("DELETE FROM user_alerts WHERE subject = 'Mantenimiento Mensual'");
    }
}