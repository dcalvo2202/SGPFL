<?php
/**
 * HU-029: Prueba Funcional e Integración del Sistema de Mensajería
 * 
 * Esta prueba valida de forma real la comunicación con la base de datos (base_db)
 * invocando directamente las funciones centrales documentadas en la historia de 
 * usuario HU-029: buscar usuarios, crear chats, y el envío/lectura de mensajes.
 */

use PHPUnit\Framework\TestCase;

// Importar la configuración de base de datos y funciones de mensajería
require_once __DIR__ . '/../../inc/chat_functions.php';

class ChatFunctionalTest extends TestCase
{
    private $conn;
    private string $u1 = 'STU_PRUEBA_9991';
    private string $u2 = 'ASE_PRUEBA_9992';

    protected function setUp(): void
    {
        // 1. Establecer conexión real a la base de datos de pruebas configurada en bdcommon.inc
        include __DIR__ . '/../../inc/db/bdcommon.inc';
        
        $this->conn = new mysqli($db_host, $usuario, $clave, $db);
        if ($this->conn->connect_error) {
            $this->fail('Hubo un error al intentar conectarse a la Base de Datos para las pruebas funcionales: ' . $this->conn->connect_error);
        }

        // 2. Limpiar rastros de pruebas pasadas abortadas
        $this->limpiarDatosDePrueba();

        // 3. Crear dos cuentas de prueba reales en la base de datos para la comunicación
        //    Usuario Estudiante
        $this->conn->query("INSERT IGNORE INTO sis_login (id, pass, id_roll) VALUES ('{$this->u1}', '123123', 4)");
        $this->conn->query("INSERT IGNORE INTO sis_user (id, nombre, email) VALUES ('{$this->u1}', 'Estudiante Test Funcional', 'test1@testfuncional.com')");
        
        //    Usuario Asesor
        $this->conn->query("INSERT IGNORE INTO sis_login (id, pass, id_roll) VALUES ('{$this->u2}', '123123', 5)");
        $this->conn->query("INSERT IGNORE INTO sis_user (id, nombre, email) VALUES ('{$this->u2}', 'Asesor Test Funcional', 'test2@testfuncional.com')");
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
     * Helper para eliminar los chats y usuarios ficticios generados.
     */
    private function limpiarDatosDePrueba()
    {
        if (!$this->conn || $this->conn->connect_error) return;
        
        // Borramos los mensajes en cascada (aunque la BD tiene ON DELETE CASCADE, somos explícitos por precaución)
        $this->conn->query("DELETE m FROM chat_messages m JOIN chat_participants p ON m.conversation_id = p.conversation_id WHERE p.user_id IN ('{$this->u1}', '{$this->u2}')");
        $this->conn->query("DELETE FROM chat_participants WHERE user_id IN ('{$this->u1}', '{$this->u2}')");
        $this->conn->query("DELETE FROM chat_conversations WHERE created_by IN ('{$this->u1}', '{$this->u2}')");
        
        // Borramos las credenciales 
        $this->conn->query("DELETE FROM sis_user WHERE id IN ('{$this->u1}', '{$this->u2}')");
        $this->conn->query("DELETE FROM sis_login WHERE id IN ('{$this->u1}', '{$this->u2}')");
    }

    /**
     * @test
     * HU-029: Verificamos el flujo base de iniciar y entablar una conversación,
     * mandar mensajes y verificar su estatus (Leído / No leído).
     */
    public function testFlujoRealDeMensajeriaIndividual()
    {
        // ---------------------------------------------------------
        // Paso 1: Validar que el estudiante puede hallar al asesor
        // ---------------------------------------------------------
        $busqueda = chatSearchUsers($this->conn, 'Asesor Test Funcional', $this->u1, 20);
        $this->assertIsArray($busqueda, 'La búsqueda inicial debe retornar una estructura de tipo Arreglo');
        $this->assertGreaterThanOrEqual(1, count($busqueda), 'Debe hallar sin problemas a nuestro usuario Asesor de pruebas');
        $this->assertEquals($this->u2, $busqueda[0]['id'], 'El ID localizado debe coincidir estructuralmente');

        // ---------------------------------------------------------
        // Paso 2: Que ambos empiecen el chat y la BD los registre
        // ---------------------------------------------------------
        $idConv = getOrCreateIndividualConversation($this->conn, $this->u1, $this->u2);
        $this->assertIsNumeric($idConv, 'Una conversación exitosa debe arrojar su ID correspondiente');
        $this->assertGreaterThan(0, $idConv, 'Obtuvimos un ID válido y positivo de sala individual');

        // Confirmar nivel de permiso a sala individual
        $this->assertTrue(isParticipant($this->conn, $idConv, $this->u1), 'El Estudiante debe estar anotado en la sala');
        $this->assertTrue(isParticipant($this->conn, $idConv, $this->u2), 'El Asesor debe estar anotado en la sala');

        // ---------------------------------------------------------
        // Paso 3: Intercambio de envíos y lecturas (Send Message)
        // ---------------------------------------------------------
        $mensajeUno = 'Hola estimado Asesor, le envío mi esquema de prueba.';
        $mensajeDos = 'Me falta adjuntar los planos finales.';
        
        $exitoUno = sendMessage($this->conn, $idConv, $this->u1, $mensajeUno);
        $exitoDos = sendMessage($this->conn, $idConv, $this->u1, $mensajeDos);
        
        $this->assertNotFalse($exitoUno, 'El mensaje #1 debe depositarse con éxito y retornar un ID numérico');
        $this->assertNotFalse($exitoDos, 'El mensaje #2 debe depositarse con éxito y retornar un ID numérico');

        // ---------------------------------------------------------
        // Paso 4: El Asesor revisa su bandeja (deben estar sin leer)
        // ---------------------------------------------------------
        // Asumiendo que la función de leer mensajes por ID funciona
        $msgsRecibidos = getConversationMessages($this->conn, $idConv, $this->u2, 10, 0);
        $this->assertIsArray($msgsRecibidos, 'El buzón de historial responde de forma estructurada');
        // Debe traer al menos los 2, el orden base suele ser DESC o ASC según diseño, pero validamos que contengan los textos.
        $textosRecuperados = array_column($msgsRecibidos, 'message_text');
        
        $this->assertContains($mensajeUno, $textosRecuperados, 'El primer mensaje está registrado en DB');
        $this->assertContains($mensajeDos, $textosRecuperados, 'El segundo mensaje está registrado en DB');

        // ---------------------------------------------------------
        // Paso 5: Validamos notificaciones no leídas
        // ---------------------------------------------------------
        $sinLeer = getTotalUnreadMessages($this->conn, $this->u2);
        $this->assertGreaterThanOrEqual(2, $sinLeer, 'El asesor debió recibir notificación de al menos los dos mensajes no leídos');

        // ---------------------------------------------------------
        // Paso 6: El Asesor marca la conversación como leída
        // ---------------------------------------------------------
        $leido = markConversationAsRead($this->conn, $idConv, $this->u2);
        $this->assertTrue($leido, 'Marcar todos como leídos debe arrojar positivo');
        
        $sinLeerActualizado = getTotalUnreadMessages($this->conn, $this->u2);
        $this->assertEquals(0, $sinLeerActualizado, 'Ya no debe tener marcadores no leídos del estudiante (pasan a estar verificados)');
    }

    /**
     * @test
     * HU-029: Verificamos intentos fallidos (seguridad/filtros).
     * Por ejemplo, textos vacíos o intentos de colarse en chats ajenos.
     */
    public function testPrevencionDeFallasEInyeccionesEnChat()
    {
        // Validar búsqueda vacía (< 1 caracteres)
        $busquedaCorta = chatSearchUsers($this->conn, '', $this->u1);
        $this->assertEmpty($busquedaCorta, 'Las búsquedas tan cortas deben retornar vacío para no sobrecargar el backend');

        // Obtener el chat funcional
        $idConv = getOrCreateIndividualConversation($this->conn, $this->u1, $this->u2);

        // Validar filtro de contenido vacío
        $envioBlanco = sendMessage($this->conn, $idConv, $this->u1, "    ");
        $this->assertFalse($envioBlanco, 'No se puede enviar un mensaje compuesto solamente por espacios');

        // Validar permisos de salas cerradas
        $terceroMisterioso = 'USUARIO_EXTERNO_007';
        $envioHackeado = sendMessage($this->conn, $idConv, $terceroMisterioso, "Hola intruso");
        $this->assertFalse($envioHackeado, 'Un tercer usuario que no esté listado en `chat_participants` no puede incrustar mensajes');
    }
}