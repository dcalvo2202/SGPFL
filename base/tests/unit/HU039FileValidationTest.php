<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/hu039_file_validators.php';

final class HU039FileValidationTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    // =========================================================
    // HU-002: Subida de propuesta TFG
    // Reglas: PDF/DOCX, máximo 10 MB
    // =========================================================

    public function testHu002AceptaPdfValido(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu002AceptaDocxValido(): void
    {
        $this->skipIfZipArchiveIsMissing();

        $path = $this->createDocxFile();

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.docx', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $result['mime']
        );
    }

    public function testHu002RechazaArchivoMayorA10Mb(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile(
                'propuesta.pdf',
                $path,
                10 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('10MB', $result['message']);
    }

    public function testHu002RechazaExtensionNoPermitida(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.exe', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    public function testHu002RechazaPdfFalso(): void
    {
        $path = $this->createTextFile('Esto no es un PDF real.');

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.pdf', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('no es un archivo válido', $result['message']);
    }

    // =========================================================
    // HU-014: Subida de documento final TFG
    // Reglas: PDF, máximo 20 MB, mínimo 100 KB
    // =========================================================

    public function testHu014AceptaPdfValido(): void
    {
        $path = $this->createPdfFile(150 * 1024);

        $result = hu039_validate_final_document_file(
            $this->makeUploadedFile('documento-final.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu014RechazaDocumentoMayorA20Mb(): void
    {
        $path = $this->createPdfFile(150 * 1024);

        $result = hu039_validate_final_document_file(
            $this->makeUploadedFile(
                'documento-final.pdf',
                $path,
                20 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('20MB', $result['message']);
    }

    public function testHu014RechazaDocx(): void
    {
        $path = $this->createTextFile('Contenido cualquiera.');

        $result = hu039_validate_final_document_file(
            $this->makeUploadedFile('documento-final.docx', $path, 150 * 1024),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    public function testHu014RechazaArchivoMenorA100Kb(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_final_document_file(
            $this->makeUploadedFile('documento-final.pdf', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('demasiado pequeño', $result['message']);
    }

    public function testHu014RechazaPdfFalso(): void
    {
        $path = $this->createTextFile(str_repeat('No es PDF.', 20000));

        $result = hu039_validate_final_document_file(
            $this->makeUploadedFile('documento-final.pdf', $path, 150 * 1024),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('no es un archivo válido', $result['message']);
    }

    // =========================================================
    // HU-020: Subida de correcciones del documento final
    // Reglas: mismas que HU-014
    // =========================================================

    public function testHu020AceptaPdfCorregidoValido(): void
    {
        $path = $this->createPdfFile(150 * 1024);

        $result = hu039_validate_correction_file(
            $this->makeUploadedFile('correccion.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu020RechazaArchivoNoPdf(): void
    {
        $path = $this->createPngFile();

        $result = hu039_validate_correction_file(
            $this->makeUploadedFile('correccion.png', $path, 150 * 1024),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    public function testHu020RechazaPdfMayorA20Mb(): void
    {
        $path = $this->createPdfFile(150 * 1024);

        $result = hu039_validate_correction_file(
            $this->makeUploadedFile(
                'correccion.pdf',
                $path,
                20 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('20MB', $result['message']);
    }

    // =========================================================
    // HU-011: Registro de perfil académico de asesor externo
    // Reglas:
    // CV: PDF/DOCX <= 5 MB
    // Cédula: PDF/JPG/PNG <= 2 MB
    // Carta: PDF <= 5 MB
    // =========================================================

    public function testHu011AceptaCvPdfValido(): void
    {
        $path = $this->createPdfFile(80 * 1024);

        $result = hu039_validate_advisor_cv_file(
            $this->makeUploadedFile('cv.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu011AceptaCvDocxValido(): void
    {
        $this->skipIfZipArchiveIsMissing();

        $path = $this->createDocxFile();

        $result = hu039_validate_advisor_cv_file(
            $this->makeUploadedFile('cv.docx', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $result['mime']
        );
    }

    public function testHu011RechazaCvMayorA5Mb(): void
    {
        $path = $this->createPdfFile(80 * 1024);

        $result = hu039_validate_advisor_cv_file(
            $this->makeUploadedFile(
                'cv.pdf',
                $path,
                5 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('5MB', $result['message']);
    }

    public function testHu011RechazaCvDocxFalso(): void
    {
        $path = $this->createTextFile('No soy un DOCX real.');

        $result = hu039_validate_advisor_cv_file(
            $this->makeUploadedFile('cv.docx', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('DOCX válido', $result['message']);
    }

    public function testHu011AceptaCedulaPdfValida(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_advisor_id_copy_file(
            $this->makeUploadedFile('cedula.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu011AceptaCedulaPngValida(): void
    {
        $path = $this->createPngFile();

        $result = hu039_validate_advisor_id_copy_file(
            $this->makeUploadedFile('cedula.png', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('image/png', $result['mime']);
    }

    public function testHu011RechazaCedulaMayorA2Mb(): void
    {
        $path = $this->createPngFile();

        $result = hu039_validate_advisor_id_copy_file(
            $this->makeUploadedFile(
                'cedula.png',
                $path,
                2 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('2MB', $result['message']);
    }

    public function testHu011RechazaCedulaDocx(): void
    {
        $path = $this->createTextFile('Documento no permitido.');

        $result = hu039_validate_advisor_id_copy_file(
            $this->makeUploadedFile('cedula.docx', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    public function testHu011AceptaCartaPdfValida(): void
    {
        $path = $this->createPdfFile(80 * 1024);

        $result = hu039_validate_advisor_cover_letter_file(
            $this->makeUploadedFile('carta.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu011RechazaCartaNoPdf(): void
    {
        $path = $this->createPngFile();

        $result = hu039_validate_advisor_cover_letter_file(
            $this->makeUploadedFile('carta.png', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    // =========================================================
    // HU-006: Solicitud de prórroga
    // Reglas: soporte PDF opcional, si existe debe ser PDF <= 20 MB
    // =========================================================

    public function testHu006AceptaPdfSoporteValido(): void
    {
        $path = $this->createPdfFile(60 * 1024);

        $result = hu039_validate_prorroga_support_file(
            $this->makeUploadedFile('respaldo-prorroga.pdf', $path),
            false
        );

        $this->assertTrue($result['valid']);
        $this->assertSame('application/pdf', $result['mime']);
    }

    public function testHu006RechazaSoporteNoPdf(): void
    {
        $path = $this->createPngFile();

        $result = hu039_validate_prorroga_support_file(
            $this->makeUploadedFile('respaldo-prorroga.png', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('extensión', $result['message']);
    }

    public function testHu006RechazaPdfSoporteMayorA20Mb(): void
    {
        $path = $this->createPdfFile(60 * 1024);

        $result = hu039_validate_prorroga_support_file(
            $this->makeUploadedFile(
                'respaldo-prorroga.pdf',
                $path,
                20 * 1024 * 1024 + 1
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('20MB', $result['message']);
    }

    public function testHu006RechazaPdfFalso(): void
    {
        $path = $this->createTextFile('No soy un PDF.');

        $result = hu039_validate_prorroga_support_file(
            $this->makeUploadedFile('respaldo-prorroga.pdf', $path),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('no es un archivo válido', $result['message']);
    }

    // =========================================================
    // Casos generales HU-039
    // =========================================================

    public function testHu039RechazaArchivoVacio(): void
    {
        $path = $this->createTextFile('');

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.pdf', $path, 0),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('vacío', $result['message']);
    }

    public function testHu039RechazaErrorDeSubida(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile(
                'propuesta.pdf',
                $path,
                null,
                UPLOAD_ERR_INI_SIZE
            ),
            false
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('servidor', $result['message']);
    }

    public function testHu039ModoProduccionRequiereSubidaHttpReal(): void
    {
        $path = $this->createPdfFile(50 * 1024);

        $result = hu039_validate_proposal_file(
            $this->makeUploadedFile('propuesta.pdf', $path),
            true
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('subida HTTP válida', $result['message']);
    }

    // =========================================================
    // Helpers de prueba
    // =========================================================

    private function makeUploadedFile(
        string $name,
        string $tmpPath,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientType = ''
    ): array {
        return [
            'name' => $name,
            'type' => $clientType,
            'tmp_name' => $tmpPath,
            'error' => $error,
            'size' => $size ?? filesize($tmpPath),
        ];
    }

    private function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hu039_');

        if ($path === false) {
            $this->fail('No se pudo crear archivo temporal para la prueba.');
        }

        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function createPdfFile(int $minimumBytes): string
    {
        $header = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
        $body = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $body .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";

        $footer = "trailer\n<< /Root 1 0 R >>\n%%EOF\n";

        $current = strlen($header . $body . $footer);
        $paddingSize = max(0, $minimumBytes - $current);

        $padding = str_repeat("% HU-039 padding\n", (int)ceil($paddingSize / 17));

        return $this->createTempFile($header . $body . $padding . $footer);
    }

    private function createTextFile(string $content): string
    {
        return $this->createTempFile($content);
    }

    private function createPngFile(): string
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

        return $this->createTempFile(base64_decode($pngBase64));
    }

    private function createDocxFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hu039_docx_');

        if ($path === false) {
            $this->fail('No se pudo crear DOCX temporal para la prueba.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            $this->fail('No se pudo abrir ZipArchive para crear DOCX temporal.');
        }

        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
            '</Types>'
        );

        $zip->addFromString(
            'word/document.xml',
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body><w:p><w:r><w:t>Documento de prueba HU-039</w:t></w:r></w:p></w:body>' .
            '</w:document>'
        );

        $zip->close();

        $this->tempFiles[] = $path;

        return $path;
    }

    private function skipIfZipArchiveIsMissing(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('La extensión ZIP no está habilitada; se omiten pruebas DOCX.');
        }
    }
}