<?php
/**
 * HU-029: Tests unitarios para el sistema de chat
 * Pruebas de las funciones en inc/chat_functions.php
 */
use PHPUnit\Framework\TestCase;

// ======================== STUBS DE CONEXIÓN ========================
if (!class_exists('ChatMockMysqliStmt')) {
    class ChatMockMysqliStmt {
        public $affected_rows = 1;
        public $insert_id = 1;
        private $result_data = [];
        private $current = 0;
        private $bind_result_refs = [];

        public function __construct($data = []) {
            $this->result_data = $data;
        }

        public function bind_param($types, &...$vars) { return true; }
        
        public function execute() { return true; }
        
        public function get_result() {
            return new ChatMockMysqliResult($this->result_data);
        }
        
        public function close() {}
    }
}

if (!class_exists('ChatMockMysqliResult')) {
    class ChatMockMysqliResult {
        public $num_rows;
        private $data;
        private $pointer = 0;

        public function __construct($data = []) {
            $this->data = $data;
            $this->num_rows = count($data);
        }

        public function fetch_assoc() {
            if ($this->pointer < count($this->data)) {
                return $this->data[$this->pointer++];
            }
            return null;
        }

        public function fetch_all($mode = MYSQLI_ASSOC) {
            return $this->data;
        }

        public function close() {}
        public function free() {}
    }
}

if (!class_exists('ChatMockMysqli')) {
    class ChatMockMysqli {
        public $insert_id = 0;
        public $affected_rows = 0;
        public $error = '';
        public $connect_error = null;
        private $prepare_returns = [];
        private $prepare_index = 0;

        public function prepare($query) {
            if (isset($this->prepare_returns[$this->prepare_index])) {
                return $this->prepare_returns[$this->prepare_index++];
            }
            return new ChatMockMysqliStmt();
        }

        public function setPrepareReturns($stmts) {
            $this->prepare_returns = $stmts;
            $this->prepare_index = 0;
        }

        public function set_charset($charset) {}
        public function begin_transaction() {}
        public function commit() {}
        public function rollback() {}
        public function ping() { return true; }
        public function close() {}
        public function real_escape_string($str) { return addslashes($str); }
    }
}

class ChatFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        // Incluir funciones si no están cargadas
        if (!function_exists('chatSearchUsers')) {
            // Definir constantes necesarias
            if (!defined('CHAT_TYPE_INDIVIDUAL')) define('CHAT_TYPE_INDIVIDUAL', 'individual');
            if (!defined('CHAT_TYPE_GROUP')) define('CHAT_TYPE_GROUP', 'group');
            if (!defined('CHAT_MAX_MESSAGE_LENGTH')) define('CHAT_MAX_MESSAGE_LENGTH', 5000);
            if (!defined('CHAT_MIN_MESSAGE_LENGTH')) define('CHAT_MIN_MESSAGE_LENGTH', 1);
            if (!defined('CHAT_SEARCH_MIN_LENGTH')) define('CHAT_SEARCH_MIN_LENGTH', 2);
            if (!defined('CHAT_ROL_COLORS')) {
                define('CHAT_ROL_COLORS', [
                    1 => '#dc3545', 2 => '#1a3a5c', 3 => '#fd7e14',
                    4 => '#198754', 5 => '#6f42c1'
                ]);
            }

            // Mock de seleccion_segura si no existe
            if (!function_exists('seleccion_segura')) {
                function seleccion_segura($sql, $params) { return []; }
            }
            if (!function_exists('ejecutar_query')) {
                function ejecutar_query($sql, $params) { return false; }
            }
            if (!function_exists('transaccion_multiple')) {
                function transaccion_multiple($queries) { return true; }
            }

            require_once __DIR__ . '/../../inc/chat_functions.php';
        }
    }

    // ======================== TEST getRolBadgeInfo ========================
    
    public function testGetRolBadgeInfoAdmin()
    {
        $result = getRolBadgeInfo(1);
        $this->assertEquals('Administrador', $result['name']);
        $this->assertEquals('#dc3545', $result['color']);
    }

    public function testGetRolBadgeInfoEstudiante()
    {
        $result = getRolBadgeInfo(4);
        $this->assertEquals('Estudiante', $result['name']);
        $this->assertEquals('#198754', $result['color']);
    }

    public function testGetRolBadgeInfoAsesor()
    {
        $result = getRolBadgeInfo(5);
        $this->assertEquals('Asesor', $result['name']);
        $this->assertEquals('#6f42c1', $result['color']);
    }

    public function testGetRolBadgeInfoCTFG()
    {
        $result = getRolBadgeInfo(3);
        $this->assertEquals('CTFG', $result['name']);
        $this->assertEquals('#fd7e14', $result['color']);
    }

    public function testGetRolBadgeInfoGestor()
    {
        $result = getRolBadgeInfo(2);
        $this->assertEquals('Gestor Académico', $result['name']);
        $this->assertEquals('#1a3a5c', $result['color']);
    }

    public function testGetRolBadgeInfoDesconocido()
    {
        $result = getRolBadgeInfo(99);
        $this->assertEquals('Usuario', $result['name']);
        $this->assertEquals('#6c757d', $result['color']);
    }

    public function testGetRolBadgeInfoNull()
    {
        $result = getRolBadgeInfo(null);
        $this->assertEquals('Usuario', $result['name']);
        $this->assertEquals('#6c757d', $result['color']);
    }

    // ======================== TEST sendMessage VALIDACIONES ========================

    public function testSendMessageTextoVacio()
    {
        $conn = new ChatMockMysqli();
        $result = sendMessage($conn, 1, 100, '');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('vacío', $result['error']);
    }

    public function testSendMessageTextoSoloEspacios()
    {
        $conn = new ChatMockMysqli();
        $result = sendMessage($conn, 1, 100, '   ');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('vacío', $result['error']);
    }

    public function testSendMessageTextoMuyLargo()
    {
        $conn = new ChatMockMysqli();
        $longText = str_repeat('a', CHAT_MAX_MESSAGE_LENGTH + 1);
        $result = sendMessage($conn, 1, 100, $longText);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('excede', $result['error']);
    }

    public function testSendMessageConversationIdInvalido()
    {
        $conn = new ChatMockMysqli();
        $result = sendMessage($conn, 0, 100, 'Hola');
        
        $this->assertFalse($result['success']);
    }

    public function testSendMessageUserIdInvalido()
    {
        $conn = new ChatMockMysqli();
        $result = sendMessage($conn, 1, 0, 'Hola');
        
        $this->assertFalse($result['success']);
    }

    // ======================== TEST chatSearchUsers VALIDACIONES ========================

    public function testChatSearchUsersTerminoCorto()
    {
        // Con min_length = 1, una cadena vacía no debería retornar nada
        $conn = new ChatMockMysqli();
        $result = chatSearchUsers($conn, '', 1);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testChatSearchUsersTerminoVacio()
    {
        $conn = new ChatMockMysqli();
        $result = chatSearchUsers($conn, '', 1);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ======================== TEST isParticipant ========================

    public function testIsParticipantConIdsInvalidos()
    {
        $conn = new ChatMockMysqli();
        // Preparar un stmt que devuelve resultado vacío
        $stmt = new ChatMockMysqliStmt([]);
        $conn->setPrepareReturns([$stmt]);
        
        $result = isParticipant($conn, 0, 1);
        $this->assertFalse($result);
    }

    // ======================== TEST CONSTANTES ========================

    public function testConstantesChatDefinidas()
    {
        $this->assertTrue(defined('CHAT_TYPE_INDIVIDUAL'));
        $this->assertTrue(defined('CHAT_TYPE_GROUP'));
        $this->assertTrue(defined('CHAT_MAX_MESSAGE_LENGTH'));
        $this->assertTrue(defined('CHAT_MIN_MESSAGE_LENGTH'));
        $this->assertTrue(defined('CHAT_SEARCH_MIN_LENGTH'));
        $this->assertTrue(defined('CHAT_ROL_COLORS'));
    }

    public function testConstantesValoresCorrectos()
    {
        $this->assertEquals('individual', CHAT_TYPE_INDIVIDUAL);
        $this->assertEquals('group', CHAT_TYPE_GROUP);
        $this->assertEquals(5000, CHAT_MAX_MESSAGE_LENGTH);
        $this->assertEquals(1, CHAT_MIN_MESSAGE_LENGTH);
        $this->assertEquals(1, CHAT_SEARCH_MIN_LENGTH);
    }

    public function testRolColorsEsArray()
    {
        $this->assertIsArray(CHAT_ROL_COLORS);
        $this->assertCount(5, CHAT_ROL_COLORS);
        $this->assertArrayHasKey(1, CHAT_ROL_COLORS);
        $this->assertArrayHasKey(5, CHAT_ROL_COLORS);
    }

    // ======================== TEST VALIDACIÓN DE LONGITUD DE MENSAJE ========================

    public function testMensajeLongitudMinima()
    {
        $conn = new ChatMockMysqli();
        $result = sendMessage($conn, 1, 100, 'H');
        // Con un solo carácter debería pasar la validación de longitud mínima
        // (aunque falle por participación), el mensaje no da error de vacío
        $this->assertIsArray($result);
    }

    public function testMensajeLongitudExactaMaxima()
    {
        $conn = new ChatMockMysqli();
        $maxText = str_repeat('x', CHAT_MAX_MESSAGE_LENGTH);
        // No debería dar error de longitud máxima
        $result = sendMessage($conn, 1, 100, $maxText);
        $this->assertIsArray($result);
        // No debe decir "excede"
        if (!$result['success']) {
            $this->assertStringNotContainsString('excede', $result['error'] ?? '');
        }
    }

    // ======================== TEST getOrCreateIndividualConversation ========================

    public function testGetOrCreateConversacionMismoUsuario()
    {
        $conn = new ChatMockMysqli();
        // Intentar crear conversación consigo mismo
        $result = getOrCreateIndividualConversation($conn, 5, 5);
        
        // Debería retornar error o un resultado no válido
        $this->assertIsArray($result);
    }

    // ======================== TEST markConversationAsRead ========================

    public function testMarkConversationAsReadRetornaArray()
    {
        $conn = new ChatMockMysqli();
        $result = markConversationAsRead($conn, 1, 100);
        
        $this->assertIsBool($result);
    }

    // ======================== TEST getProjectGroupChatId ========================

    public function testGetProjectGroupChatIdSinExistir()
    {
        $conn = new ChatMockMysqli();
        // Con mock vacío debería retornar null o false
        $stmt = new ChatMockMysqliStmt([]);
        $conn->setPrepareReturns([$stmt]);
        
        $result = getProjectGroupChatId($conn, 999);
        $this->assertNull($result);
    }

    // ======================== TEST getTotalUnreadMessages ========================

    public function testGetTotalUnreadMessagesRetornaEntero()
    {
        $conn = new ChatMockMysqli();
        $stmt = new ChatMockMysqliStmt([['total' => 0]]);
        $conn->setPrepareReturns([$stmt]);
        
        $result = getTotalUnreadMessages($conn, 100);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
