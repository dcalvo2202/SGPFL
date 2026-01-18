<?php
use PHPUnit\Framework\TestCase;

if (!class_exists('DummyMysqli')) {
    class DummyMysqli {
        public function prepare($query) {
        }

        public function set_charset($charset) {
        }

        public function begin_transaction() {
        }

        public function commit() {
        }

        public function rollback() {
        }

        public function ping() {
            return true;
        }

        public function close() {
        }
    }
}

class FinalDocumentReviewTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock de sesión
        $GLOBALS['session_data'] = [
            'usuario' => 'ctfg_user',
            'rol' => 3
        ];
        // Mock de mySession
        if (!class_exists('mySession')) {
            eval('class mySession {
                public static function getIstance($conf) { return new self(); }
                public function getVar($key) { return $GLOBALS["session_data"][$key] ?? null; }
            }');
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['session_data']);
    }

    public function testFlujoAprobacionDocumentoFinal()
    {
        // Simular $_POST
        $_POST = [
            'document_id' => 1,
            'status' => 'Aprobado para Defensa',
            'comments' => 'Aprobado sin observaciones.'
        ];

        // Mock de mysqli y métodos
        $mockConn = $this->getMockBuilder(DummyMysqli::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare', 'set_charset', 'begin_transaction', 'commit'])
            ->getMock();

        // 1) Documento actual
        $mockStmtCurrent = $this->createMock(mysqli_stmt::class);
        $mockResultCurrent = $this->createMock(mysqli_result::class);
        $mockResultCurrent->method('fetch_assoc')->willReturn([
            'proposal_id' => 10,
            'file_id' => 100,
            'submitted_by' => 'student1',
            'file_name' => 'TFG.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 123456,
            'file_data' => 'PDFDATA'
        ]);
        $mockStmtCurrent->method('get_result')->willReturn($mockResultCurrent);
        $mockStmtCurrent->expects($this->once())->method('bind_param');
        $mockStmtCurrent->expects($this->once())->method('execute');
        $mockStmtCurrent->expects($this->once())->method('close');

        // 2) Version
        $mockStmtVersion = $this->createMock(mysqli_stmt::class);
        $mockResultVersion = $this->createMock(mysqli_result::class);
        $mockResultVersion->method('fetch_assoc')->willReturn(['max_version' => 2]);
        $mockStmtVersion->method('get_result')->willReturn($mockResultVersion);
        $mockStmtVersion->expects($this->once())->method('bind_param');
        $mockStmtVersion->expects($this->once())->method('execute');
        $mockStmtVersion->expects($this->once())->method('close');

        // 3) Update estado
        $mockStmtUpdate = $this->createMock(mysqli_stmt::class);
        $mockStmtUpdate->expects($this->once())->method('bind_param');
        $mockStmtUpdate->expects($this->once())->method('execute')->willReturn(true);
        $mockStmtUpdate->expects($this->once())->method('close');

        // 4) Conteo revisiones
        $mockStmtCount = $this->createMock(mysqli_stmt::class);
        $mockResultCount = $this->createMock(mysqli_result::class);
        $mockResultCount->method('fetch_assoc')->willReturn(['count' => 0]);
        $mockStmtCount->method('get_result')->willReturn($mockResultCount);
        $mockStmtCount->expects($this->once())->method('bind_param');
        $mockStmtCount->expects($this->once())->method('execute');
        $mockStmtCount->expects($this->once())->method('close');

        // 5) Insert review
        $mockStmtReview = $this->createMock(mysqli_stmt::class);
        $mockStmtReview->expects($this->once())->method('bind_param');
        $mockStmtReview->expects($this->once())->method('execute')->willReturn(true);
        $mockStmtReview->expects($this->once())->method('close');

        $mockConn->method('prepare')->willReturnOnConsecutiveCalls(
            $mockStmtCurrent,
            $mockStmtVersion,
            $mockStmtUpdate,
            $mockStmtCount,
            $mockStmtReview
        );

        $mockConn->expects($this->once())->method('set_charset')->with('utf8');
        $mockConn->expects($this->once())->method('begin_transaction');
        $mockConn->expects($this->once())->method('commit');

        // Mock de bdcommon.inc y tfg_final_functions.php
        // Simular include de bdcommon.inc y la variable $conn
        $GLOBALS['db_host'] = 'localhost';
        $GLOBALS['usuario'] = 'user';
        $GLOBALS['clave'] = 'pass';
        $GLOBALS['db'] = 'testdb';

        // Inyectar conexión para el script (evita instanciar mysqli real)
        $GLOBALS['__mysqli_mock'] = $mockConn;

        // Capturar salida
        ob_start();
        include __DIR__ . '/../../mod/admin/users/process_final_document_review.php';
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response, 'La salida no es JSON');
        $this->assertTrue($response['success']);
        $this->assertEquals('Aprobado para Defensa', $response['status']);
        $this->assertEquals('Aprobado sin observaciones.', $response['comments']);
        $this->assertStringContainsString('actualizado', $response['message']);
    }
}
