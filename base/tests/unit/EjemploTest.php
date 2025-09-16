<?php
use PHPUnit\Framework\TestCase;

class EjemploTest extends TestCase
{
    public function testSuma()
    {
        $this->assertEquals(4, 2 + 2);
    }
}