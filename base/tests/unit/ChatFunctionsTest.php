<?php
/**
 * HU-029: Tests unitarios del sistema de Chat y Mensajería Interna
 * Pruebas de las funciones base en inc/chat_functions.php
 *
 * Nombre del test                          Descripción                                             Resultado esperado
 * ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 * Búsqueda de usuarios exitosa             chatSearchUsers() encuentra usuarios por nombre         Retorna array con perfiles
 * Búsqueda corta retorna vacío             Termino de búsqueda menor a min length retorna vacío    Retorna array vacío
 * Obtener chat individual existente        Si ya existe, getOrCreateIndividualConversation         Retorna ID numérico (ej. 5)
 * Crear chat individual nuevo              Si no existe, hace INSERTs y retorna nuevo ID           Retorna insert_id (ej. 10)
 * Verifica participante presente           isParticipant() devuelve true si el flag está activo    Retorna true
 * Enviar mensaje texto vacío               sendMessage() bloquea strings vacíos / solo espacios    Retorna false
 * Enviar mensaje no participante           sendMessage() bloquea si el remitente no está en chat   Retorna false
 */

use PHPUnit\Framework\TestCase;

if (!defined('CHAT_SEARCH_MIN_LENGTH')) {
    define('CHAT_SEARCH_MIN_LENGTH', 3);
}

require_once __DIR__ . '/../../inc/chat_functions.php';

// =========================================================================
// STUBS para eludir restricciones de retorno en PHPUnit 11 y mysqli
// =========================================================================
if (!class_exists('ChatStubResult')) {
    class ChatStubResult {
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

if (!class_exists('ChatStubStmt')) {
    class ChatStubStmt {
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

if (!class_exists('ChatStubConn')) {
    class ChatStubConn {
        public string $error = '';
        public int $insert_id = 10;
        private array $stmts;

        public function __construct(array $stmts = []) {
            $this->stmts = $stmts;
        }

        public function prepare($sql) {
            if (!empty($this->stmts)) {
                $stmt = array_shift($this->stmts);
                return $stmt === false ? false : $stmt;
            }
            return new ChatStubStmt(new ChatStubResult([]));
        }

        public function begin_transaction() {}
        public function rollback() {}
        public function commit() {}
    }
}

class ChatFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        ini_set('error_log', '/dev/null');
    }

    // =====================================================================
    // HU-029: Búsqueda de Usuarios (chatSearchUsers)
    // =====================================================================

    public function testChatSearchUsersSuccess()
    {
        $stmt = new ChatStubStmt(new ChatStubResult([
            ['id' => 'user2', 'nombre' => 'María López', 'email' => 'maria@test.com', 'roll_name' => 'Estudiante', 'id_roll' => 4]
        ]));
        $conn = new ChatStubConn([$stmt]);

        $users = chatSearchUsers($conn, 'Maria', 'user1', 10);

        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertEquals('María López', $users[0]['nombre']);
    }

    public function testChatSearchUsersShortTermReturnsEmpty()
    {
        $conn = new ChatStubConn([]); 

        $users = chatSearchUsers($conn, 'Ma', 'user1');

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    public function testChatSearchUsersPrepareFails()
    {
        $conn = new ChatStubConn([false]);

        $users = chatSearchUsers($conn, 'Maria', 'user1');

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    // =====================================================================
    // HU-029: Conversación Individual 
    // =====================================================================

    public function testGetIndividualConversationExists()
    {
        $stmtExist = clone new ChatStubStmt(new ChatStubResult([['id' => 5]]));
        $conn = new ChatStubConn([$stmtExist]);

        $conv_id = getOrCreateIndividualConversation($conn, 'u1', 'u2');

        $this->assertSame(5, $conv_id);
    }

    public function testCreateIndividualConversationNew()
    {
        // En lugar de una clase anónima que extiende mysqli y choca, usamos ChatStubConn 
        // añadiendo una comprobación ad-hoc en prepare o simplemente mandando lo necesario
        
        $stmtCheck = clone new ChatStubStmt(new ChatStubResult([])); // No existe el chat
        $stmtInsertConv = clone new ChatStubStmt(); // Se inserta
        $stmtPartic1 = clone new ChatStubStmt(); // Participante 1
        $stmtPartic2 = clone new ChatStubStmt(); // Participante 2

        $conn = new ChatStubConn([$stmtCheck, $stmtInsertConv, $stmtPartic1, $stmtPartic2]);
        $conn->insert_id = 15; // Set the simulated insert ID

        $conv_id = getOrCreateIndividualConversation($conn, 'u1', 'u2');

        $this->assertSame(15, $conv_id);
    }

    // =====================================================================
    // HU-029: Envío de Mensajes
    // =====================================================================

    public function testSendMessageEmptyValidations()
    {
        $conn = clone new ChatStubConn([]);

        $this->assertFalse(sendMessage($conn, 1, 'u1', ''));
        $this->assertFalse(sendMessage($conn, 1, 'u1', '   '));
    }

    public function testSendMessageNotParticipant()
    {
        $stmtPart = clone new ChatStubStmt(new ChatStubResult([]));
        $conn = clone new ChatStubConn([$stmtPart]);

        $result = sendMessage($conn, 1, 'u1', 'Hola Mundo');

        $this->assertFalse($result);
    }

    // =====================================================================
    // HU-029: isParticipant 
    // =====================================================================
    
    public function testIsParticipantTrue()
    {
        $stmt = clone new ChatStubStmt(new ChatStubResult([['id' => 1]]));
        $conn = clone new ChatStubConn([$stmt]);
        
        $this->assertTrue(isParticipant($conn, 1, 'userX'));
    }
}
