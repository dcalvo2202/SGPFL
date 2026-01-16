<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../inc/tfg_proposal_functions.php';

class TFGProposalHistoryLimitTest extends TestCase
{
    public function testLimiteVersionesTFGProposalHistory()
    {
        $mockConn = $this->createMock(mysqli::class);

        $mockStmtSelectProposals = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteHistory = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteRegistered = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteProposal = $this->createMock(mysqli_stmt::class);

        $mockResult = $this->createMock(mysqli_result::class);

        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10], ['id' => 11], ['id' => 12], ['id' => 13], ['id' => 14], null
            );

        $mockConn->method('commit')->willReturn(true);
        $mockConn->method('rollback')->willReturn(true);

        $mockConn->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnCallback(function($sql) use ($mockStmtSelectProposals, $mockStmtDeleteHistory, $mockStmtDeleteRegistered, $mockStmtDeleteProposal) {
                if (strpos($sql, 'SELECT id') !== false && strpos($sql, 'FROM tfg_proposals') !== false) {
                    return $mockStmtSelectProposals;
                }
                if (strpos($sql, 'DELETE FROM tfg_proposal_history') !== false) {
                    return $mockStmtDeleteHistory;
                }
                if (strpos($sql, 'DELETE FROM registered_projects') !== false) {
                    return $mockStmtDeleteRegistered;
                }
                if (strpos($sql, 'DELETE FROM tfg_proposals') !== false) {
                    return $mockStmtDeleteProposal;
                }
                return false;
            });

        $mockStmtSelectProposals->expects($this->once())->method('bind_param')->with('s', 'user123');
        $mockStmtSelectProposals->expects($this->once())->method('execute');
        $mockStmtSelectProposals->expects($this->once())->method('get_result')->willReturn($mockResult);
        $mockStmtSelectProposals->expects($this->once())->method('close');

        $mockStmtDeleteHistory->expects($this->once())->method('bind_param')->with('i', 10);
        $mockStmtDeleteHistory->expects($this->once())->method('execute');
        $mockStmtDeleteHistory->expects($this->once())->method('close');

        $mockStmtDeleteRegistered->expects($this->once())->method('bind_param')->with('i', 10);
        $mockStmtDeleteRegistered->expects($this->once())->method('execute');
        $mockStmtDeleteRegistered->expects($this->once())->method('close');

        $mockStmtDeleteProposal->expects($this->once())->method('bind_param')->with('i', 10);
        $mockStmtDeleteProposal->expects($this->once())->method('execute');
        $mockStmtDeleteProposal->expects($this->once())->method('close');

        limitarVersionesYAgregarHistorial($mockConn, 'user123');

        $this->assertTrue(true);
    }
}