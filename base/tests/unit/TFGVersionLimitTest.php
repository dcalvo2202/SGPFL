<?php
// filepath: c:\xampp\htdocs\base\tests\unit\TFGVersionLimitTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../inc/tfg_final_functions.php'; // Ajusta la ruta si es necesario

class TFGVersionLimitTest extends TestCase
{
    public function testLimiteVersionesTFGFiles()
    {
        $mockConn = $this->createMock(mysqli::class);

        $mockStmtSelect = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteFile = $this->createMock(mysqli_stmt::class);

        $mockResult = $this->createMock(mysqli_result::class);

        // Simular 5 versiones existentes
        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5], null
            );

        $mockConn->method('commit')->willReturn(true);
        $mockConn->method('rollback')->willReturn(true);

        $mockConn->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function($sql) use ($mockStmtSelect, $mockStmtDeleteFile) {
                if (strpos($sql, 'SELECT id FROM tfg_files') !== false) {
                    return $mockStmtSelect;
                }
                if (strpos($sql, 'DELETE FROM tfg_files') !== false) {
                    return $mockStmtDeleteFile;
                }
                return false;
            });

        $mockStmtSelect->expects($this->once())->method('bind_param')->with('ss', 'user123', 'Documento Final TFG');
        $mockStmtSelect->expects($this->once())->method('execute');
        $mockStmtSelect->expects($this->once())->method('get_result')->willReturn($mockResult);
        $mockStmtSelect->expects($this->once())->method('close');

        $mockStmtDeleteFile->expects($this->once())->method('bind_param')->with('i', 1);
        $mockStmtDeleteFile->expects($this->once())->method('execute');
        $mockStmtDeleteFile->expects($this->once())->method('close');

        // Llama a la función real con los mocks y datos de prueba
        limitarVersionesTFGFiles(
            $mockConn,
            'user123',           // $user_id
            'Documento Final TFG' // $document_type
        );

        $this->assertTrue(true);
    }
}