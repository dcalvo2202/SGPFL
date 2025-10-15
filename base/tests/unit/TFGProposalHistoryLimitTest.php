<?php
use PHPUnit\Framework\TestCase;

class TFGProposalHistoryLimitTest extends TestCase
{
    public function testLimiteVersionesTFGProposalHistory()
    {
        $mockConn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['prepare'])
            ->getMock();

        $mockStmtSelect = $this->getMockBuilder(stdClass::class)
            ->addMethods(['bind_param', 'execute', 'get_result', 'close'])
            ->getMock();

        $mockStmtDelete = $this->getMockBuilder(stdClass::class)
            ->addMethods(['bind_param', 'execute', 'close'])
            ->getMock();

        $mockResult = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_assoc'])
            ->getMock();

        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10], ['id' => 11], ['id' => 12], ['id' => 13], ['id' => 14], null
            );

        $mockConn->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive(
                [$this->stringContains('SELECT id FROM tfg_proposal_history')],
                [$this->stringContains('DELETE FROM tfg_proposal_history')]
            )
            ->willReturnOnConsecutiveCalls($mockStmtSelect, $mockStmtDelete);

        $mockStmtSelect->expects($this->once())->method('bind_param');
        $mockStmtSelect->expects($this->once())->method('execute');
        $mockStmtSelect->expects($this->once())->method('get_result')->willReturn($mockResult);
        $mockStmtSelect->expects($this->once())->method('close');

        $mockStmtDelete->expects($this->once())->method('bind_param')->with('i', 10);
        $mockStmtDelete->expects($this->once())->method('execute');
        $mockStmtDelete->expects($this->once())->method('close');

        $this->assertTrue(true);
    }
}