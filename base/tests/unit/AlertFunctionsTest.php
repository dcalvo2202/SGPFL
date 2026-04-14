<?php
/**
 * HU-037: Tests unitarios del sistema de Alertas Internas y Visor de Historial
 * Pruebas de las funciones base en inc/alert_functions.php
 *
 * Nombre del test                          Descripción                                             Resultado esperado
 * ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 * Registro exitoso                         registerAlert() se ejecuta correctamente                Retorna true
 * Fallo en prepare de registro             Si mysqli no puede hacer prepare, falla                 Retorna false
 * Registro a nivel de rol                  registerAlertToRole() itera e inserta a usuarios        Retorna la cantidad (ej. 2)
 * Historial devuelve arreglo               getUserAlerts() devuelve histórico ordenado             Retorna array
 * Historial vacío devuelve arreglo vacío   getUserAlerts() para usuario sin alertas                Retorna array vacío
 * Cuenta alertas no leídas                 getUnreadAlertCount() retorna valor correcto            Retorna int (conteo)
 * Marca alerta leída                       markAlertAsRead() ejecuta update con usuario            Retorna true
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../inc/alert_functions.php';

// =========================================================================
// STUBS para eludir restricciones de retorno en PHPUnit 11 y mysqli
// =========================================================================
if (!class_exists('AlertStubResult')) {
    class AlertStubResult {
        public int $num_rows;
        private array $rows;
        private int $index = 0;

        public function __construct(array $rows = []) {
            $this->rows = $rows;
            $this->num_rows = count($rows);
        }

        public function fetch_assoc() {
            if ($this->index < count($this->rows)) {
                return $this->rows[$this->index++];
            }
            return null;
        }

        public function fetch_row() {
            $row = $this->fetch_assoc();
            return $row ? array_values($row) : null;
        }

        public function close() {}
    }
}

if (!class_exists('AlertStubStmt')) {
    class AlertStubStmt {
        private $result;
        public string $error = '';

        public function __construct($result = null) {
            $this->result = $result;
        }

        public function bind_param($types, &...$vars) { return true; }
        public function execute() { return true; }
        public function get_result() { return $this->result; }
        public function close() {}
    }
}

if (!class_exists('AlertStubConn')) {
    class AlertStubConn {
        public string $error = '';
        private array $stmts;

        public function __construct(array $stmts = []) {
            $this->stmts = $stmts;
        }

        public function prepare($sql) {
            if (!empty($this->stmts)) {
                $stmt = array_shift($this->stmts);
                return $stmt === false ? false : $stmt;
            }
            return new AlertStubStmt(new AlertStubResult([]));
        }
    }
}

class AlertFunctionsTest extends TestCase
{
    // =====================================================================
    // HU-037: Envío y Registro de Alertas (registerAlert)
    // =====================================================================

    public function testRegisterAlertSuccess()
    {
        $stmt = new AlertStubStmt(new AlertStubResult([]));
        $conn = new AlertStubConn([$stmt]);

        $result = registerAlert($conn, '101', 'Test Subject', 'Test Message', 'Informativa', 'Media', 'proposal', 10);

        $this->assertTrue($result, "Debe retornar true cuando prepare y execute son exitosos.");
    }

    public function testRegisterAlertFailsOnPrepare()
    {
        $conn = new AlertStubConn([false]);

        // Evitar que escriba al error_log real durante el test
        ini_set('error_log', '/dev/null');

        $result = registerAlert($conn, '101', 'Test', 'Msg');

        $this->assertFalse($result, "Debe retornar false si el STMT falla al prepararse.");
    }

    // =====================================================================
    // HU-037: Alertas Masivas por Rol (registerAlertToRole)
    // =====================================================================

    public function testRegisterAlertToRoleSuccess()
    {
        // 1 STMT para obtener usuarios del rol
        $usuariosRolStmt = new AlertStubStmt(new AlertStubResult([
            ['id' => 'u1'],
            ['id' => 'u2']
        ]));

        // 2 STMTs, una para cada inserción por registerAlert
        $insertStmt1 = new AlertStubStmt(new AlertStubResult([]));
        $insertStmt2 = new AlertStubStmt(new AlertStubResult([]));

        $conn = new AlertStubConn([
            $usuariosRolStmt,
            $insertStmt1,
            $insertStmt2
        ]);

        $count = registerAlertToRole($conn, 2, 'Subject Rol', 'Msg Rol');

        $this->assertEquals(2, $count, "Debería haber registrado 2 alertas para los 2 usuarios del rol.");
    }

    // =====================================================================
    // HU-037: Extracción de Historial (getUserAlerts)
    // =====================================================================

    public function testGetUserAlertsReturnsArray()
    {
        $stmt = new AlertStubStmt(new AlertStubResult([
            ['id' => 1, 'subject' => 'A1', 'message' => 'M1', 'alert_type' => 'Info'],
            ['id' => 2, 'subject' => 'A2', 'message' => 'M2', 'alert_type' => 'Danger']
        ]));
        $conn = new AlertStubConn([$stmt]);

        $alerts = getUserAlerts($conn, 'userX', 5);

        $this->assertIsArray($alerts);
        $this->assertCount(2, $alerts);
        $this->assertEquals('A1', $alerts[0]['subject']);
    }

    public function testGetUserAlertsEmptyReturnsEmptyArray()
    {
        $stmt = new AlertStubStmt(new AlertStubResult([]));
        $conn = new AlertStubConn([$stmt]);

        $alerts = getUserAlerts($conn, 'userX', 5);

        $this->assertIsArray($alerts);
        $this->assertEmpty($alerts);
    }

    // =====================================================================
    // HU-037: Contadores y Marcado (getUnreadAlertCount / markAlertAsRead)
    // =====================================================================

    public function testGetUnreadAlertCountReturnsCorrectNumber()
    {
        $stmt = new AlertStubStmt(new AlertStubResult([
            ['count' => 12]
        ]));
        $conn = new AlertStubConn([$stmt]);

        $count = getUnreadAlertCount($conn, 'userX');

        $this->assertSame(12, $count);
    }

    public function testMarkAlertAsReadSuccess()
    {
        $stmt = new AlertStubStmt(new AlertStubResult([]));
        $conn = new AlertStubConn([$stmt]);

        $result = markAlertAsRead($conn, 404, 'userY');

        $this->assertTrue($result, "Asumimos éxito si no hay problemas con prepare/execute.");
    }
}
