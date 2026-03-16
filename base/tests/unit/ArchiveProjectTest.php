<?php
/**
 * HU-027: Tests unitarios para archivar proyectos concluidos o cancelados
 * Pruebas de las funciones en inc/archive_functions.php
 *
 * Nombre del test                          Descripción                                             Resultado esperado
 * ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 * Razón inválida rechazada                 archiveProposal rechaza razones distintas a             success=false, mensaje 'inválida'
 *                                          'Concluido' / 'Cancelado'
 * Razón válida inicia transacción          archiveProposal con razón 'Concluido' llama             begin_transaction se ejecuta
 *                                          begin_transaction antes de cualquier consulta
 * Propuesta no encontrada hace rollback    Cuando la propuesta no existe en BD se lanza            success=false, rollback, sin commit
 *                                          excepción y se revierte la transacción
 * Compresión conserva los datos            compressData sobre un texto devuelve data               Los tres campos del array tienen
 *                                          no nula y tamaños coherentes                            valores positivos
 * String vacío comprime sin error          compressData('') devuelve data=null y                   original_size=0, compressed_size=0
 *                                          sizes en cero
 * Descompresión recupera original          decompressData(compressData(texto)['data'])             Texto idéntico al original
 *                                          devuelve el texto original
 * Descompresión de vacío devuelve vacío    decompressData('') devuelve string vacío                '' === resultado
 * Histórico retorna array                  getArchivedProposals devuelve array cuando              assertIsArray($result)
 *                                          la BD no tiene filas
 * Histórico excluye blob de documento      Las filas del histórico no incluyen la clave            assertArrayNotHasKey('document')
 *                                          'document' (campo BLOB)
 * Filtro por razón incluye parámetro       getArchivedProposals con filter archive_reason          bind_param recibe el valor del filtro
 *                                          añade el valor al prepared statement
 * Documento archivado no existente         getArchivedDocument cuando no hay fila en BD            retorna null
 *                                          retorna null
 * Documento archivado se descomprime       getArchivedDocument con is_compressed=1                 document distinto al blob original
 *                                          llama a decompressData                                   (descomprimido)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../inc/archive_functions.php';

if (!class_exists('ArchiveStubResult')) {
    class ArchiveStubResult {
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

        public function close() {}
    }
}

if (!class_exists('ArchiveStubStmt')) {
    class ArchiveStubStmt {
        private $result;

        public function __construct($result = null) {
            $this->result = $result;
        }

        public function bind_param($types, &...$vars) { return true; }
        public function execute() { return true; }
        public function get_result() { return $this->result; }
        public function close() {}
        public function send_long_data($param_num, $data) {}
    }
}

if (!class_exists('ArchiveStubConn')) {
    class ArchiveStubConn {
        public int $begin_count = 0;
        public int $rollback_count = 0;
        public int $commit_count = 0;
        public int $insert_id = 1;
        public string $error = '';
        private array $stmts;

        public function __construct(array $stmts = []) {
            $this->stmts = $stmts;
        }

        public function prepare($sql) {
            if (!empty($this->stmts)) {
                return array_shift($this->stmts);
            }
            return new ArchiveStubStmt(new ArchiveStubResult([]));
        }

        public function begin_transaction() { $this->begin_count++; }
        public function rollback() { $this->rollback_count++; }
        public function commit() { $this->commit_count++; }
    }
}

class ArchiveProjectTest extends TestCase
{
    // =====================================================================
    // compressData
    // =====================================================================

    public function testCompressDataConStringNormal()
    {
        $original = 'Documento TFG con contenido de prueba para verificar compresion gzip';

        $result = compressData($original);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('original_size', $result);
        $this->assertArrayHasKey('compressed_size', $result);
        $this->assertNotNull($result['data']);
        $this->assertEquals(strlen($original), $result['original_size']);
        $this->assertGreaterThan(0, $result['compressed_size']);
    }

    public function testCompressDataConStringVacio()
    {
        $result = compressData('');

        $this->assertNull($result['data']);
        $this->assertEquals(0, $result['original_size']);
        $this->assertEquals(0, $result['compressed_size']);
    }

    public function testCompressDataConNull()
    {
        $result = compressData(null);

        $this->assertNull($result['data']);
        $this->assertEquals(0, $result['original_size']);
        $this->assertEquals(0, $result['compressed_size']);
    }

    // =====================================================================
    // decompressData
    // =====================================================================

    public function testDecompressDataStringVacio()
    {
        $result = decompressData('');

        $this->assertEquals('', $result);
    }

    public function testDecompressDataRecuperaDatosOriginales()
    {
        $original = 'Contenido completo de una propuesta TFG con caracteres especiales: ñ, á, é, ü';

        $compressed = compressData($original);
        $decompressed = decompressData($compressed['data']);

        $this->assertEquals($original, $decompressed);
    }

    public function testCompressionEsIdempotente()
    {
        $text = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 20);

        $compressed = compressData($text);
        $decompressed = decompressData($compressed['data']);

        $this->assertEquals($text, $decompressed);
        $this->assertLessThan($compressed['original_size'], $compressed['compressed_size'],
            'La compresion deberia reducir el tamanio para texto repetitivo');
    }

    // =====================================================================
    // archiveProposal – validación de razón
    // =====================================================================

    public function testArchiveProposalConRazonInvalidaRetornaError()
    {
        $conn = $this->createMock(mysqli::class);
        // No debe iniciar transacción con razón inválida
        $conn->expects($this->never())->method('begin_transaction');

        $result = archiveProposal($conn, 1, 'EnProceso');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('inválida', $result['message']);
    }

    public function testArchiveProposalRechazaRazonVacia()
    {
        $conn = $this->createMock(mysqli::class);
        $conn->expects($this->never())->method('begin_transaction');

        $result = archiveProposal($conn, 1, '');

        $this->assertFalse($result['success']);
    }

    public function testArchiveProposalAceptaRazonConcluido()
    {
        $stmt = new ArchiveStubStmt(new ArchiveStubResult([]));
        $conn = new ArchiveStubConn([$stmt]);

        $result = archiveProposal($conn, 999, 'Concluido');

        $this->assertFalse($result['success']);
        $this->assertSame(1, $conn->begin_count);
        $this->assertSame(1, $conn->rollback_count);
        $this->assertSame(0, $conn->commit_count);
    }

    public function testArchiveProposalAceptaRazonCancelado()
    {
        $stmt = new ArchiveStubStmt(new ArchiveStubResult([]));
        $conn = new ArchiveStubConn([$stmt]);

        $result = archiveProposal($conn, 999, 'Cancelado');

        $this->assertFalse($result['success']);
        $this->assertSame(1, $conn->begin_count);
        $this->assertSame(1, $conn->rollback_count);
    }

    // =====================================================================
    // archiveProposal – propuesta no encontrada
    // =====================================================================

    public function testArchiveProposalPropuestaNoEncontradaHaceRollback()
    {
        $stmt = new ArchiveStubStmt(new ArchiveStubResult([]));
        $conn = new ArchiveStubConn([$stmt]);

        $result = archiveProposal($conn, 999, 'Concluido');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no encontrada', $result['message']);
        $this->assertSame(1, $conn->begin_count);
        $this->assertSame(1, $conn->rollback_count);
        $this->assertSame(0, $conn->commit_count);
    }

    // =====================================================================
    // getArchivedProposals
    // =====================================================================

    public function testGetArchivedProposalsRetornaArray()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(null);

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        $conn->method('prepare')->willReturn($mockStmt);

        $result = getArchivedProposals($conn);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetArchivedProposalsExcluyeDocumentBlob()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturnOnConsecutiveCalls(
            [
                'id'             => 1,
                'title'          => 'Proyecto de Prueba',
                'archive_reason' => 'Concluido',
                'document'       => 'datos_binarios_blob',
                'user_name'      => 'Estudiante Demo'
            ],
            null
        );

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        $conn->method('prepare')->willReturn($mockStmt);

        $result = getArchivedProposals($conn);

        $this->assertCount(1, $result);
        // El BLOB no debe exponerse en la lista
        $this->assertArrayNotHasKey('document', $result[0]);
    }

    public function testGetArchivedProposalsConFiltroArchiveReason()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(null);

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);

        // Verificar que bind_param recibe 'sii' (razón + limit + offset)
        $mockStmt->expects($this->once())
            ->method('bind_param')
            ->with(
                $this->stringContains('s'),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $conn->method('prepare')->willReturn($mockStmt);

        $result = getArchivedProposals($conn, ['archive_reason' => 'Cancelado']);

        $this->assertIsArray($result);
    }

    public function testGetArchivedProposalsConFiltroBusqueda()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(null);

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);

        // Con filtro de búsqueda, el tipo debe incluir 'ss' (dos LIKE)
        $mockStmt->expects($this->once())
            ->method('bind_param')
            ->with($this->stringContains('ss'), $this->anything(), $this->anything(), $this->anything());

        $conn->method('prepare')->willReturn($mockStmt);

        $result = getArchivedProposals($conn, ['search' => 'Proyecto TFG']);

        $this->assertIsArray($result);
    }

    // =====================================================================
    // getArchivedDocument
    // =====================================================================

    public function testGetArchivedDocumentNoExistenteRetornaNull()
    {
        $stmtLog = new ArchiveStubStmt(new ArchiveStubResult([]));
        $stmtDoc = new ArchiveStubStmt(new ArchiveStubResult([]));
        $conn = new ArchiveStubConn([$stmtLog, $stmtDoc]);

        // Preparar $_SERVER para logArchiveAccess
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $result = getArchivedDocument($conn, 999, 'user_test');

        $this->assertNull($result);
    }

    public function testGetArchivedDocumentExistenteSinCompresiónRetornaDatos()
    {
        $stmtLog = new ArchiveStubStmt(new ArchiveStubResult([]));
        $stmtDoc = new ArchiveStubStmt(new ArchiveStubResult([
            [
                'document'      => 'contenido_del_pdf',
                'file_name'     => 'tfg_final.pdf',
                'mime_type'     => 'application/pdf',
                'is_compressed' => 0,
                'title'         => 'Trabajo Final de Grado'
            ]
        ]));
        $conn = new ArchiveStubConn([$stmtLog, $stmtDoc]);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $result = getArchivedDocument($conn, 1, 'user_test');

        $this->assertNotNull($result);
        $this->assertEquals('tfg_final.pdf', $result['file_name']);
        $this->assertEquals('contenido_del_pdf', $result['document']);
    }

    public function testGetArchivedDocumentComprimidoSeDescomprime()
    {
        $original_text = 'Contenido real del documento TFG que fue comprimido con gzip';
        $compressed    = gzencode($original_text, 9);

        $stmtLog = new ArchiveStubStmt(new ArchiveStubResult([]));
        $stmtDoc = new ArchiveStubStmt(new ArchiveStubResult([
            [
                'document'      => $compressed,
                'file_name'     => 'tfg.pdf',
                'mime_type'     => 'application/pdf',
                'is_compressed' => 1,
                'title'         => 'TFG Comprimido'
            ]
        ]));
        $conn = new ArchiveStubConn([$stmtLog, $stmtDoc]);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $result = getArchivedDocument($conn, 1, 'user_test');

        $this->assertNotNull($result);
        // El documento debe haberse descomprimido
        $this->assertEquals($original_text, $result['document']);
        $this->assertNotEquals($compressed, $result['document']);
    }
}
