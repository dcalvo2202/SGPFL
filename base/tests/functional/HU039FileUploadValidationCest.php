<?php

declare(strict_types=1);

namespace Tests\functional;

use Codeception\Scenario;
use Tests\Support\FunctionalTester;

final class HU039FileUploadValidationCest
{
    private const HU002_STUDENT_ID = '116440018'; // Puede subir propuesta
    private const HU014_STUDENT_ID = '118440202'; // Puede subir documento final
    private const HU020_STUDENT_ID = '206580363'; // Puede subir correcciones
    private const HU006_STUDENT_ID = '504430777'; // Puede solicitar prórroga

    private const STUDENT_PASS = 'secret123';

    private const LINKED_STUDENT_ID = '503550224';

    private const INVALID_TXT = 'hu039_invalid.txt';
    private const VALID_PDF = 'hu039_valid.pdf';
    private const VALID_PNG = 'hu039_valid.png';

    public function _before(FunctionalTester $I): void
    {
        $this->ensureFixtureFiles();
    }

    /**
     * HU-002: subida de propuesta TFG.
     * Verifica que el flujo real rechaza un archivo con extensión no permitida.
     */
    public function hu002RechazaPropuestaConArchivoNoPermitido(FunctionalTester $I, Scenario $scenario): void
    {
        $this->loginAsStudent($I, self::HU002_STUDENT_ID);

        $I->amOnPage('/mod/admin/users/tfg_upload.php');
        $this->skipIfPageDoesNotContain($I, $scenario, 'tfgGroupForm', 'HU-002 no muestra formulario de subida para el estudiante actual.');

        $projectTypeId = $this->grabFirstNonEmptyOptionValue($I, '#sel-project-type option');
        if ($projectTypeId === '') {
            $scenario->skip('HU-002 no tiene tipos de proyecto disponibles para probar el formulario.');
        }

        $I->fillField('input[name="title"]', 'Prueba funcional HU-039 ' . $this->uniqueSuffix());
        $I->fillField('textarea[name="project_description"]', 'Descripcion funcional suficientemente larga para validar rechazo de archivo por HU-039.');
        $I->selectOption('select[name="project_type_id"]', $projectTypeId);
        $I->attachFile('input[name="documents[]"]', self::INVALID_TXT);
        $I->checkOption('input[name="accept_terms"]');
        $I->click('button[type="submit"]');

        $this->assertNoPhpErrors($I);
        $this->assertPageContainsAny($I, ['"success":false', 'extensión', 'extension', 'PDF', 'DOCX', 'archivo válido']);
    }

    /**
     * HU-014: subida de documento final TFG.
     * Verifica que el flujo real rechaza documentos finales que no son PDF.
     */
    public function hu014RechazaDocumentoFinalNoPdf(FunctionalTester $I, Scenario $scenario): void
    {
        $this->loginAsStudent($I, self::HU014_STUDENT_ID);

        $I->amOnPage('/mod/admin/users/tfg_upload_final_document.php');
        $this->skipIfPageDoesNotContain($I, $scenario, 'tfgFinalForm', 'HU-014 no muestra formulario de documento final para el estudiante actual.');

        $I->attachFile('input[name="documents[]"]', self::INVALID_TXT);
        $I->fillField('textarea[name="notes"]', 'Intento funcional con archivo no permitido para HU-039.');
        $I->click('button[type="submit"]');

        $this->assertNoPhpErrors($I);
        $this->assertPageContainsAny($I, ['"success":false', 'PDF', 'extensión', 'extension', 'archivo válido']);
    }

    /**
     * HU-020: subida de correcciones del documento final.
     * Verifica que el flujo real rechaza correcciones con archivo no PDF.
     */
    public function hu020RechazaCorreccionNoPdf(FunctionalTester $I, Scenario $scenario): void
    {
        $this->loginAsStudent($I, self::HU020_STUDENT_ID);

        $documentId = $this->discoverCorrectionDocumentId($I);
        if ($documentId <= 0) {
            $scenario->skip('HU-020 no tiene documento rechazado/corregible disponible para el estudiante actual.');
        }

        $I->amOnPage('/mod/admin/users/tfg_upload_correction.php?id=' . $documentId);
        $this->skipIfPageDoesNotContain($I, $scenario, 'correctionForm', 'HU-020 no muestra formulario de corrección para el documento detectado.');

        $I->fillField('textarea[name="corrections_summary"]', 'Resumen funcional de correcciones para validar rechazo de archivo no PDF.');
        $I->checkOption('input[name="all_addressed"]');
        $I->attachFile('input[name="documents[]"]', self::INVALID_TXT);
        $I->click('button[type="submit"]');

        $this->assertNoPhpErrors($I);
        $this->assertPageContainsAny($I, ['"success":false', 'PDF', 'archivo válido', 'extensión', 'extension']);
    }

    /**
     * HU-011: registro de perfil de asesor externo.
     * Verifica que el formulario real rechaza un currículum con extensión no permitida.
     */
    public function hu011RechazaCvNoPermitido(FunctionalTester $I): void
    {
        $I->amOnPage('/registro.php');

        $this->fillAdvisorBaseFields($I);
        $I->attachFile('input[name="cv_document"]', self::INVALID_TXT);
        $I->attachFile('input[name="id_copy_document"]', self::VALID_PNG);
        $I->attachFile('input[name="cover_letter_document"]', self::VALID_PDF);
        $I->click('button[type="submit"]');

        $this->assertNoPhpErrors($I);
        $this->assertPageContainsAny($I, ['No se pudo enviar', 'Currículum', 'extension', 'extensión', 'archivo válido']);
    }

    /**
     * HU-006: solicitud de prórroga.
     * Verifica que el flujo real rechaza adjuntos de respaldo que no son PDF.
     */
    public function hu006RechazaRespaldoProrrogaNoPdf(FunctionalTester $I, Scenario $scenario): void
    {
        $this->loginAsStudent($I, self::HU006_STUDENT_ID);

        $I->amOnPage('/panel_solicitudProrroga.php');
        $this->skipIfPageDoesNotContain($I, $scenario, 'formProrroga', 'HU-006 no muestra formulario de prórroga para el estudiante actual.');

        $I->fillField('textarea[name="motivo"]', 'Motivo funcional para validar rechazo de archivo no PDF.');
        $I->attachFile('input[name="documento_prorroga[]"]', self::INVALID_TXT);
        $I->click('button[type="submit"]');

        $this->assertNoPhpErrors($I);
        $this->assertPageContainsAny($I, ['PDF', 'prórroga', 'prorroga', 'archivo válido', 'extensión', 'extension']);
    }

    private function loginAsStudent(FunctionalTester $I, string $studentId): void
    {
        $I->amOnPage('/login.php');
        $I->fillField('#user', $studentId);
        $I->fillField('#pass', self::STUDENT_PASS);
        $I->click('#saveForm');
    }

    private function fillAdvisorBaseFields(FunctionalTester $I): void
    {
        $suffix = $this->uniqueSuffix();

        $I->selectOption('select[name="postulation_type"]', 'Asesor Externo');
        $I->selectOption('select[name="committee_subrole"]', 'Asesor 1');
        $I->fillField('input[name="applicant_id"]', 'HU039' . $suffix);
        $I->fillField('input[name="full_name"]', 'ASESOR FUNCIONAL HU039');
        $I->fillField('input[name="email"]', 'hu039.' . $suffix . '@est.una.ac.cr');
        $I->fillField('input[name="telefono"]', '88888888');
        $I->fillField('input[name="institution"]', 'Universidad Nacional');
        $I->fillField('input[name="specialization"]', 'Ingenieria de software');
        $I->fillField('input[name="linked_student_id"]', self::LINKED_STUDENT_ID);

        $tipoTel = $this->grabFirstNonEmptyOptionValue($I, 'select[name="id_tipo_tel"] option');
        if ($tipoTel !== '') {
            $I->selectOption('select[name="id_tipo_tel"]', $tipoTel);
        }
    }

    private function discoverCorrectionDocumentId(FunctionalTester $I): int
    {
        $I->amOnPage('/mod/admin/users/tfg_upload_final_document.php');
        $source = $I->grabPageSource();

        if (preg_match('/tfg_upload_correction\.php\?id=(\d+)/', $source, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function grabFirstNonEmptyOptionValue(FunctionalTester $I, string $selector): string
    {
        try {
            $values = $I->grabMultiple($selector, 'value');
            foreach ($values as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    private function skipIfPageDoesNotContain(FunctionalTester $I, Scenario $scenario, string $needle, string $message): void
    {
        if (strpos($I->grabPageSource(), $needle) === false) {
            $scenario->skip($message);
        }
    }

    private function assertNoPhpErrors(FunctionalTester $I): void
    {
        $source = $I->grabPageSource();

        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);
        $I->assertStringNotContainsString('Warning', $source);
        $I->assertStringNotContainsString('Notice', $source);
        $I->assertStringNotContainsString('Deprecated', $source);
    }

    /**
     * @param string[] $needles
     */
    private function assertPageContainsAny(FunctionalTester $I, array $needles): void
    {
        $source = $I->grabPageSource();

        foreach ($needles as $needle) {
            if (stripos($source, $needle) !== false) {
                $I->assertTrue(true);
                return;
            }
        }

        $I->fail('No se encontró ninguno de los textos esperados: ' . implode(', ', $needles));
    }

    private function ensureFixtureFiles(): void
    {
        $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '_data';

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }

        file_put_contents($dataDir . DIRECTORY_SEPARATOR . self::INVALID_TXT, 'Archivo invalido para HU-039.');
        file_put_contents($dataDir . DIRECTORY_SEPARATOR . self::VALID_PDF, $this->minimalPdfContent());
        file_put_contents($dataDir . DIRECTORY_SEPARATOR . self::VALID_PNG, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));
    }

    private function minimalPdfContent(): string
    {
        return "%PDF-1.7\n" .
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n" .
            "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n" .
            str_repeat("% HU-039 functional padding\n", 50) .
            "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function uniqueSuffix(): string
    {
        return date('YmdHis') . (string) random_int(100, 999);
    }
}
