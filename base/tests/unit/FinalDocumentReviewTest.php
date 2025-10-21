<?php
use PHPUnit\Framework\TestCase;

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
            $mockStmtCurrent = $this->createMock(mysqli_stmt::class);
            $mockResultCurrent = $this->createMock(mysqli_result::class);
            $mockResultCurrent->method('fetch_assoc')->willReturnOnConsecutiveCalls(
                [
                    'proposal_id' => 10,
                    'file_id' => 100,
                    'submitted_by' => 'student1',
                    'file_name' => 'TFG.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 123456,
                    'file_data' => 'PDFDATA'
                ],
                null
            );
            $mockStmtCurrent->method('get_result')->willReturn($mockResultCurrent);

            $mockStmtCurrent->expects($this->once())->method('bind_param');
            $mockStmtCurrent->expects($this->once())->method('execute');
            $mockStmtCurrent->expects($this->once())->method('close');

            // Mock versión
            $mockStmtVersion = $this->createMock(mysqli_stmt::class);
            $mockResultVersion = $this->createMock(mysqli_result::class);
            $mockResultVersion->method('fetch_assoc')->willReturn(['max_version' => 2]);
            $mockStmtVersion->method('get_result')->willReturn($mockResultVersion);
            $mockStmtVersion->expects($this->once())->method('bind_param');
            $mockStmtVersion->expects($this->once())->method('execute');
            $mockStmtVersion->expects($this->once())->method('close');

            // Mock insertar nueva versión
            $mockStmtNewFile = $this->createMock(mysqli_stmt::class);
            $mockStmtNewFile->expects($this->once())->method('bind_param');
            $mockStmtNewFile->expects($this->once())->method('send_long_data');
            $mockStmtNewFile->expects($this->once())->method('execute')->willReturn(true);
            $mockStmtNewFile->expects($this->once())->method('close');

            // Mock actualizar documento final
            $mockStmtUpdate = $this->createMock(mysqli_stmt::class);
            $mockStmtUpdate->expects($this->once())->method('bind_param');
            $mockStmtUpdate->expects($this->once())->method('execute')->willReturn(true);
            $mockStmtUpdate->expects($this->once())->method('close');

            // Usar withConsecutive y willReturnOnConsecutiveCalls para prepare
            $mockConn->method('prepare')->willReturnOnConsecutiveCalls(
                $mockStmtCurrent,
                $mockStmtVersion,
                $mockStmtNewFile,
                $mockStmtUpdate
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

        // Sobrescribir new mysqli para devolver el mock
        $GLOBALS['__mysqli_mock'] = $mockConn;
        if (!function_exists('mysqli_override')) {
            eval('namespace { class mysqli { public function __construct($h,$u,$p,$d) { return $GLOBALS["__mysqli_mock"]; } } }');
        }

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
