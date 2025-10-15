<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../inc/tfg_proposal_functions.php';

class TFGProposalHistoryLimitTest extends TestCase
{
    public function testLimiteVersionesTFGProposalHistory()
    {
        $mockConn = $this->createMock(mysqli::class);

        $mockStmtSelect = $this->createMock(mysqli_stmt::class);
        $mockStmtDelete = $this->createMock(mysqli_stmt::class);
        $mockStmtInsert = $this->createMock(mysqli_stmt::class);

        // CORRECCIÓN AQUÍ:
        $mockResult = $this->createMock(mysqli_result::class);

        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10], ['id' => 11], ['id' => 12], ['id' => 13], ['id' => 14], null
            );

        $mockConn->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function($sql) use ($mockStmtSelect, $mockStmtDelete, $mockStmtInsert) {
                if (strpos($sql, 'SELECT id FROM tfg_proposal_history') !== false) {
                    return $mockStmtSelect;
                }
                if (strpos($sql, 'DELETE FROM tfg_proposal_history') !== false) {
                    return $mockStmtDelete;
                }
                if (strpos($sql, 'INSERT INTO tfg_proposal_history') !== false) {
                    return $mockStmtInsert;
                }
                return null;
            });

        $mockStmtSelect->expects($this->once())->method('bind_param')->with('i', 123);
        $mockStmtSelect->expects($this->once())->method('execute');
        $mockStmtSelect->expects($this->once())->method('get_result')->willReturn($mockResult);
        $mockStmtSelect->expects($this->once())->method('close');

        $mockStmtDelete->expects($this->once())->method('bind_param')->with('i', 10);
        $mockStmtDelete->expects($this->once())->method('execute');
        $mockStmtDelete->expects($this->once())->method('close');

        $mockStmtInsert->expects($this->once())->method('bind_param');
        $mockStmtInsert->expects($this->any())->method('send_long_data');
        $mockStmtInsert->expects($this->once())->method('execute')->willReturn(true);
        $mockStmtInsert->expects($this->once())->method('close');

        limitarVersionesYAgregarHistorial(
            $mockConn,
            123, // $tfg_id
            456, // $user_id
            'archivo.pdf', // $unique_filename
            'application/pdf', // $mime_type
            1000, // $file_size
            'contenido', // $document_data
            null // $null_blob
        );

        $this->assertTrue(true);
    }
}