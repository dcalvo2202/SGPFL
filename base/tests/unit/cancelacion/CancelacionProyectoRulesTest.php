<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../service/cancelaciones/CancelacionProyectoRules.php';

class CancelacionProyectoRulesTest extends TestCase
{
    public function testValidarEntradaCorrecta(): void
    {
        $errores = CancelacionProyectoRules::validarEntrada(10, 'Inactividad prolongada', 3);

        $this->assertEmpty($errores);
    }

    public function testValidarEntradaFallaPorProyectoInvalido(): void
    {
        $errores = CancelacionProyectoRules::validarEntrada(0, 'Motivo válido', 3);

        $this->assertContains('ID de proyecto no válido.', $errores);
    }

    public function testValidarEntradaFallaPorMotivoVacio(): void
    {
        $errores = CancelacionProyectoRules::validarEntrada(5, '', 3);

        $this->assertContains('Motivo es requerido (máx. 500 caracteres).', $errores);
    }

    public function testValidarEntradaFallaPorSesionInvalida(): void
    {
        $errores = CancelacionProyectoRules::validarEntrada(5, 'Motivo válido', 0);

        $this->assertContains('Sesión inválida.', $errores);
    }

    public function testValidarSeisMesesFallaSiNoHanPasadoSeisMeses(): void
    {
        $hoy = new DateTime('2026-04-07');
        $errores = CancelacionProyectoRules::validarSeisMeses('2026-02-10', $hoy);

        $this->assertContains(
            'No se puede cancelar: el proyecto registra avances en los últimos 6 meses (Art. 73 RGPEA).',
            $errores
        );
    }

    public function testValidarSeisMesesPasaSiYaPasaronSeisMeses(): void
    {
        $hoy = new DateTime('2026-04-07');
        $errores = CancelacionProyectoRules::validarSeisMeses('2025-08-01', $hoy);

        $this->assertEmpty($errores);
    }
}

?>