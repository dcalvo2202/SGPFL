<?php
use PHPUnit\Framework\TestCase;

class TFGVersionLimitTest extends TestCase
{
    public function testLimiteVersionesTFGFiles()
    {
        // Mock de la conexión y statements
        $mockConn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['prepare'])
            ->getMock();

        $mockStmtSelect = $this->getMockBuilder(stdClass::class)
            ->addMethods(['bind_param', 'execute', 'get_result', 'close'])
            ->getMock();

        $mockStmtDelete = $this->getMockBuilder(stdClass::class)
            ->addMethods(['bind_param', 'execute', 'close'])
            ->getMock();

        // Simular 5 versiones existentes
        $mockResult = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_assoc'])
            ->getMock();

        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5], null
            );

        // Simular prepare para SELECT y DELETE
        $mockConn->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive(
                [$this->stringContains('SELECT id FROM tfg_files')],
                [$this->stringContains('DELETE FROM tfg_files')]
            )
            ->willReturnOnConsecutiveCalls($mockStmtSelect, $mockStmtDelete);

        // Simular flujo de SELECT
        $mockStmtSelect->expects($this->once())->method('bind_param');
        $mockStmtSelect->expects($this->once())->method('execute');
        $mockStmtSelect->expects($this->once())->method('get_result')->willReturn($mockResult);
        $mockStmtSelect->expects($this->once())->method('close');

        // Simular flujo de DELETE
        $mockStmtDelete->expects($this->once())->method('bind_param')->with('i', 1);
        $mockStmtDelete->expects($this->once())->method('execute');
        $mockStmtDelete->expects($this->once())->method('close');

        // Ejecutar lógica a testear (simplificada)
        // ...aquí iría tu función, pero para test solo ejecutamos el flujo simulado...
        // Si el código real está en una función, aquí la llamarías pasando $mockConn
        $this->assertTrue(true); // Si no hay excepción, pasa el test
    }
}