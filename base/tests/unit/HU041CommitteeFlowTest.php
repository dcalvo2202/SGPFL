<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/hu041_committee_rules.php';
require_once dirname(__DIR__, 2) . '/inc/hu041_upload_validation.php';
require_once dirname(__DIR__, 2) . '/inc/hu041_document_helpers.php';

final class HU041CommitteeFlowTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->temporaryFiles = [];
    }

    public function testAceptaComiteCompletoConTresPersonasDistintas(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
            $this->committeeRequestRow(2, '1002', 'Asesor 1', '504410118'),
            $this->committeeRequestRow(3, '1003', 'Asesor 2', '504410118'),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('504410118', $groups[0]['student_id']);
        $this->assertTrue($groups[0]['can_approve']);
        $this->assertSame('', $groups[0]['validation_message']);
        $this->assertCount(1, $groups[0]['roles']['Tutor']);
        $this->assertCount(1, $groups[0]['roles']['Asesor 1']);
        $this->assertCount(1, $groups[0]['roles']['Asesor 2']);
    }

    public function testRechazaComiteSinTutor(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(2, '1002', 'Asesor 1', '504410118'),
            $this->committeeRequestRow(3, '1003', 'Asesor 2', '504410118'),
        ]);

        $this->assertCount(1, $groups);
        $this->assertFalse($groups[0]['can_approve']);
        $this->assertSame(
            'Falta completar el comité: debe tener un Tutor, un Asesor 1 y un Asesor 2.',
            $groups[0]['validation_message']
        );
    }

    public function testRechazaComiteSinAsesorDos(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
            $this->committeeRequestRow(2, '1002', 'Asesor 1', '504410118'),
        ]);

        $this->assertCount(1, $groups);
        $this->assertFalse($groups[0]['can_approve']);
        $this->assertSame(
            'Falta completar el comité: debe tener un Tutor, un Asesor 1 y un Asesor 2.',
            $groups[0]['validation_message']
        );
    }

    public function testRechazaComiteConPersonaDuplicadaEnVariosRoles(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
            $this->committeeRequestRow(2, '1001', 'Asesor 1', '504410118'),
            $this->committeeRequestRow(3, '1003', 'Asesor 2', '504410118'),
        ]);

        $this->assertCount(1, $groups);
        $this->assertFalse($groups[0]['can_approve']);
        $this->assertSame(
            'Una misma persona no puede ocupar más de un rol dentro del mismo comité.',
            $groups[0]['validation_message']
        );
    }

    public function testAgrupaSolicitudesDeDiferentesEstudiantes(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118', 'CARLOS DANIEL'),
            $this->committeeRequestRow(2, '1002', 'Asesor 1', '504410118', 'CARLOS DANIEL'),
            $this->committeeRequestRow(3, '1003', 'Asesor 2', '504410118', 'CARLOS DANIEL'),

            $this->committeeRequestRow(4, '2001', 'Tutor', '504430777', 'JOSE DOMINGO'),
            $this->committeeRequestRow(5, '2002', 'Asesor 1', '504430777', 'JOSE DOMINGO'),
        ]);

        $this->assertCount(2, $groups);

        $first = hu041_get_group_by_student($groups, '504410118');
        $second = hu041_get_group_by_student($groups, '504430777');

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertTrue($first['can_approve']);
        $this->assertFalse($second['can_approve']);
    }

    public function testRetornaNullSiNoExisteGrupoDelEstudiante(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
        ]);

        $this->assertNull(hu041_get_group_by_student($groups, '999999999'));
    }

    public function testValidaDecisionAprobada(): void
    {
        $this->assertDoesNotThrow(function (): void {
            hu041_validate_advisor_decision_input(10, 'Aprobado', '');
        });
    }

    public function testRechazaIdSolicitudInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID de solicitud inválido.');

        hu041_validate_advisor_decision_input(0, 'Aprobado', '');
    }

    public function testRechazaDecisionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Decisión inválida.');

        hu041_validate_advisor_decision_input(10, 'Pendiente', '');
    }

    public function testRechazaMotivoCortoAlRechazarSolicitud(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Debe especificar un motivo de rechazo (mínimo 10 caracteres).');

        hu041_validate_advisor_decision_input(10, 'Rechazado', 'Corto');
    }

    public function testAceptaEstadoPendienteDeRevisionConYSinTilde(): void
    {
        $this->assertTrue(hu041_is_request_pending_status('En Revision'));
        $this->assertTrue(hu041_is_request_pending_status('En Revisión'));
    }

    public function testRechazaSolicitudYaProcesada(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Esta solicitud ya fue procesada anteriormente');

        hu041_validate_request_pending_status('Aprobado');
    }

    public function testRechazaAprobacionSinEstudianteVinculado(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La solicitud aprobada debe estar asociada a un estudiante.');

        hu041_validate_approval_has_linked_student([
            'id' => 10,
            'linked_student_id' => '',
        ]);
    }

    public function testAceptaAprobacionConEstudianteVinculado(): void
    {
        $this->assertDoesNotThrow(function (): void {
            hu041_validate_approval_has_linked_student([
                'id' => 10,
                'linked_student_id' => '504410118',
            ]);
        });
    }

    public function testRechazaAccionDeComiteSinEstudiante(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Debe indicar la solicitud de comite a procesar.');

        hu041_validate_committee_panel_action('approve_committee_request', '');
    }

    public function testRechazaAccionDeComiteInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción de comité inválida.');

        hu041_validate_committee_panel_action('delete_committee_request', '504410118');
    }

    public function testRechazaComiteConMotivoCorto(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Debe indicar un motivo de rechazo de al menos 10 caracteres.');

        hu041_validate_committee_panel_action(
            'reject_committee_request',
            '504410118',
            'Corto'
        );
    }

    public function testValidaTargetDeComiteAprobable(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
            $this->committeeRequestRow(2, '1002', 'Asesor 1', '504410118'),
            $this->committeeRequestRow(3, '1003', 'Asesor 2', '504410118'),
        ]);

        $this->assertDoesNotThrow(function () use ($groups): void {
            hu041_validate_target_committee_group($groups[0]);
        });
    }

    public function testRechazaTargetDeComiteIncompleto(): void
    {
        $groups = hu041_group_committee_requests([
            $this->committeeRequestRow(1, '1001', 'Tutor', '504410118'),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se puede aprobar: Falta completar el comité');

        hu041_validate_target_committee_group($groups[0]);
    }

    public function testValidaArchivoPdfCorrecto(): void
    {
        $path = $this->createTemporaryFile(
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
            'pdf'
        );

        $file = $this->makeUploadedFileArray($path, 'curriculum.pdf');

        $result = validate_uploaded_file(
            $file,
            ['application/pdf'],
            ['pdf'],
            5,
            'el Currículum',
            false
        );

        $this->assertSame('curriculum.pdf', $result['name']);
        $this->assertSame('application/pdf', $result['mime']);
        $this->assertGreaterThan(0, $result['size']);
        $this->assertStringStartsWith('%PDF', $result['content']);
    }

    public function testRechazaArchivoSinExtensionPermitida(): void
    {
        $path = $this->createTemporaryFile(
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
            'exe'
        );

        $file = $this->makeUploadedFileArray($path, 'archivo.exe');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('el Currículum debe tener extensión: pdf');

        validate_uploaded_file(
            $file,
            ['application/pdf'],
            ['pdf'],
            5,
            'el Currículum',
            false
        );
    }

    public function testRechazaArchivoVacio(): void
    {
        $path = $this->createTemporaryFile('', 'pdf');
        $file = $this->makeUploadedFileArray($path, 'vacio.pdf');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('el Currículum está vacío o no es válido');

        validate_uploaded_file(
            $file,
            ['application/pdf'],
            ['pdf'],
            5,
            'el Currículum',
            false
        );
    }

    public function testRechazaArchivoConMimeNoPermitido(): void
    {
        $path = $this->createTemporaryFile('contenido de texto plano', 'pdf');
        $file = $this->makeUploadedFileArray($path, 'falso.pdf');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('el Currículum no es un archivo válido');

        validate_uploaded_file(
            $file,
            ['application/pdf'],
            ['pdf'],
            5,
            'el Currículum',
            false
        );
    }

    public function testBuildInClauseGeneraPlaceholdersYTipos(): void
    {
        $result = buildInClause(['504410118', '504430777', '118440202']);

        $this->assertSame('?,?,?', $result['placeholders']);
        $this->assertSame('sss', $result['types']);
    }

    public function testBuildInClauseConListaVacia(): void
    {
        $result = buildInClause([]);

        $this->assertSame('', $result['placeholders']);
        $this->assertSame('', $result['types']);
    }

    public function testFormatoLegibleConvierteMimeTypesComunes(): void
    {
        $this->assertSame('PDF', formatoLegible('application/pdf'));
        $this->assertSame(
            'DOCX',
            formatoLegible('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
        );
        $this->assertSame(
            'XLSX',
            formatoLegible('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        );
        $this->assertSame('PNG', formatoLegible('image/png'));
    }

    public function testFormatoLegibleConMimeVacio(): void
    {
        $this->assertSame('DESCONOCIDO', formatoLegible(null));
        $this->assertSame('DESCONOCIDO', formatoLegible(''));
    }

    /**
     * @return array<string, mixed>
     */
    private function committeeRequestRow(
        int $id,
        string $applicantId,
        string $committeeRole,
        string $studentId,
        string $studentName = 'CARLOS DANIEL LOPEZ CHEVEZ'
    ): array {
        return [
            'id' => $id,
            'applicant_id' => $applicantId,
            'full_name' => 'PERSONA ' . $applicantId,
            'committee_role' => $committeeRole,
            'postulation_type' => $committeeRole === 'Tutor' ? 'Tutor' : 'Asesor Interno',
            'linked_student_id' => $studentId,
            'linked_student_name' => $studentName,
            'created_at' => '2026-05-01 10:00:00',
        ];
    }

    private function createTemporaryFile(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hu041_');

        if ($path === false) {
            $this->fail('No se pudo crear archivo temporal para la prueba.');
        }

        $pathWithExtension = $path . '.' . $extension;
        rename($path, $pathWithExtension);

        file_put_contents($pathWithExtension, $content);

        $this->temporaryFiles[] = $pathWithExtension;

        return $pathWithExtension;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeUploadedFileArray(string $path, string $name): array
    {
        return [
            'name' => $name,
            'type' => '',
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
        ];
    }

    private function assertDoesNotThrow(callable $callback): void
    {
        try {
            $callback();
            $this->assertTrue(true);
        } catch (Throwable $throwable) {
            $this->fail('No se esperaba excepción, pero se recibió: ' . $throwable->getMessage());
        }
    }
}