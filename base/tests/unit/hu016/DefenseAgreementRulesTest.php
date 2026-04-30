<?php

declare(strict_types=1);

namespace Tests\Unit\Hu016;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/DefenseAgreementRules.php';

final class DefenseAgreementRulesTest extends TestCase
{
    private DefenseAgreementRules $rules;

    protected function setUp(): void
    {
        $this->rules = new DefenseAgreementRules();
    }

    public function testPermiteAccesoCuandoUsuarioEsCTFG(): void
    {
        $result = $this->rules->canAccess('503230754', 3);

        $this->assertTrue($result);
    }

    public function testRechazaAccesoCuandoNoHayUsuario(): void
    {
        $result = $this->rules->canAccess(null, 3);

        $this->assertFalse($result);
    }

    public function testRechazaAccesoCuandoRolNoEsCTFG(): void
    {
        $result = $this->rules->canAccess('503230754', 4);

        $this->assertFalse($result);
    }

    public function testValidaProyectoYPropuestaCorrectos(): void
    {
        $result = $this->rules->validateProjectData(12, 25);

        $this->assertTrue($result['valid']);
        $this->assertSame('OK', $result['message']);
    }

    public function testRechazaProyectoInvalido(): void
    {
        $result = $this->rules->validateProjectData(0, 25);

        $this->assertFalse($result['valid']);
        $this->assertSame('Proyecto o propuesta inválidos.', $result['message']);
    }

    public function testRechazaPropuestaInvalida(): void
    {
        $result = $this->rules->validateProjectData(12, 0);

        $this->assertFalse($result['valid']);
        $this->assertSame('Proyecto o propuesta inválidos.', $result['message']);
    }

    public function testValidaFechasCorrectas(): void
    {
        $result = $this->rules->validateDates('2026-04-29', '2026-06-08');

        $this->assertTrue($result['valid']);
        $this->assertSame('OK', $result['message']);
    }

    public function testRechazaFechaConFormatoIncorrecto(): void
    {
        $result = $this->rules->validateDates('29-04-2026', '2026-06-08');

        $this->assertFalse($result['valid']);
        $this->assertSame('Fechas inválidas.', $result['message']);
    }

    public function testRechazaFechaInexistente(): void
    {
        $result = $this->rules->validateDates('2026-02-31', '2026-06-08');

        $this->assertFalse($result['valid']);
        $this->assertSame('Fechas inválidas.', $result['message']);
    }

    public function testRechazaCodigoAcuerdoVacio(): void
    {
        $result = $this->rules->validateRequiredFields('', 'malcolm.chaves.obando@est.una.ac.cr');

        $this->assertFalse($result['valid']);
        $this->assertSame('El código del acuerdo es obligatorio.', $result['message']);
    }

    public function testRechazaCorreoDestinoVacio(): void
    {
        $result = $this->rules->validateRequiredFields('UNA-CTFG-EI-ACUE-001-2026', '');

        $this->assertFalse($result['valid']);
        $this->assertSame('El correo destino es obligatorio.', $result['message']);
    }

    public function testValidaCamposObligatoriosCorrectos(): void
    {
        $result = $this->rules->validateRequiredFields(
            'UNA-CTFG-EI-ACUE-001-2026',
            'malcolm.chaves.obando@est.una.ac.cr'
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('OK', $result['message']);
    }

    public function testRechazaCuandoNoSeAdjuntaArchivo(): void
    {
        $result = $this->rules->validateUploadedFile(
            false,
            UPLOAD_ERR_NO_FILE,
            0,
            ''
        );

        $this->assertFalse($result['valid']);
        $this->assertSame('Debe adjuntar el PDF del acuerdo.', $result['message']);
    }

    public function testRechazaArchivoMayorA10MB(): void
    {
        $result = $this->rules->validateUploadedFile(
            true,
            UPLOAD_ERR_OK,
            11 * 1024 * 1024,
            'application/pdf'
        );

        $this->assertFalse($result['valid']);
        $this->assertSame('El archivo excede el tamaño máximo de 10 MB.', $result['message']);
    }

    public function testRechazaArchivoQueNoEsPDF(): void
    {
        $result = $this->rules->validateUploadedFile(
            true,
            UPLOAD_ERR_OK,
            1024,
            'text/plain'
        );

        $this->assertFalse($result['valid']);
        $this->assertSame('Solo se permiten archivos PDF.', $result['message']);
    }

    public function testAceptaArchivoPDFValido(): void
    {
        $result = $this->rules->validateUploadedFile(
            true,
            UPLOAD_ERR_OK,
            1024,
            'application/pdf'
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('OK', $result['message']);
    }

    public function testPermiteRegistrarCuandoNoExisteAcuerdoPrevio(): void
    {
        $result = $this->rules->canReplaceAgreement(
            false,
            '2026-05-09',
            '2026-04-29'
        );

        $this->assertTrue($result);
    }

    public function testPermiteReemplazarCuandoFaltanMasDeQuinceDias(): void
    {
        $result = $this->rules->canReplaceAgreement(
            true,
            '2026-05-16',
            '2026-04-29'
        );

        $this->assertTrue($result);
    }

    public function testRechazaReemplazoCuandoFaltanExactamenteQuinceDias(): void
    {
        $result = $this->rules->canReplaceAgreement(
            true,
            '2026-05-14',
            '2026-04-29'
        );

        $this->assertFalse($result);
    }

    public function testRechazaReemplazoCuandoFaltanMenosDeQuinceDias(): void
    {
        $result = $this->rules->canReplaceAgreement(
            true,
            '2026-05-09',
            '2026-04-29'
        );

        $this->assertFalse($result);
    }
}
?>