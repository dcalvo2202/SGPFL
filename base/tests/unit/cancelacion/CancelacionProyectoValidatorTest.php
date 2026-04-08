<?php

use PHPUnit\Framework\TestCase;

class CancelacionProyectoValidatorTest extends TestCase
{
    private CancelacionProyectoValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CancelacionProyectoValidator();
    }

    public function testValidaEntradaCorrecta(): void
    {
        $errores = $this->validator->validarEntrada(10, 'Incumplimiento prolongado', 5);

        $this->assertEmpty($errores);
    }

    public function testFallaSiProyectoIdEsInvalido(): void
    {
        $errores = $this->validator->validarEntrada(0, 'Motivo válido', 5);

        $this->assertContains('ID de proyecto no válido.', $errores);
    }

    public function testFallaSiMotivoEstaVacio(): void
    {
        $errores = $this->validator->validarEntrada(1, '', 5);

        $this->assertContains('Motivo es requerido (máx. 500 caracteres).', $errores);
    }

    public function testFallaSiSesionEsInvalida(): void
    {
        $errores = $this->validator->validarEntrada(1, 'Motivo válido', 0);

        $this->assertContains('Sesión inválida.', $errores);
    }

    public function testProyectoYaCanceladoNoEsCancelable(): void
    {
        $errores = $this->validator->validarProyectoCancelable([
            'estado' => 'CANCELADO'
        ]);

        $this->assertContains('El proyecto ya está CANCELADO.', $errores);
    }

    public function testValidaReglaDeSeisMesesCuandoNoHanPasado(): void
    {
        $hoy = new DateTime('2026-04-07');
        $errores = $this->validator->validarSeisMeses('2026-02-01', $hoy);

        $this->assertContains(
            'No se puede cancelar: el proyecto registra avances en los últimos 6 meses (Art. 73 RGPEA).',
            $errores
        );
    }

    public function testValidaReglaDeSeisMesesCuandoSiHanPasado(): void
    {
        $hoy = new DateTime('2026-04-07');
        $errores = $this->validator->validarSeisMeses('2025-09-01', $hoy);

        $this->assertEmpty($errores);
    }
}