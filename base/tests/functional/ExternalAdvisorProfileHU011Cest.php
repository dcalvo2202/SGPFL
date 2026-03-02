<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class ExternalAdvisorProfileHU011Cest
{
    public function registroPageShowsHu011Fields(FunctionalTester $I): void
    {
        $I->amOnPage('/registro.php');

        $I->see('Solicitud de Registro - Asesor Externo');
        $I->see('Su solicitud quedará en estado');
        $I->see('En Revisión');

        $I->seeElement('input[name="full_name"]');
        $I->seeElement('input[name="institution"]');
        $I->seeElement('input[name="specialization"]');
        $I->seeElement('input[name="cv_document"]');
        $I->seeElement('input[name="id_copy_document"]');
        $I->seeElement('input[name="linked_student_id"]');

        $I->see('Tamaño máximo: 5 MB');
        $I->see('Tamaño máximo: 2 MB');
    }

    public function rechazoCuandoNoHayEstudianteVinculado(FunctionalTester $I): void
    {
        $suffix = $this->uniqueSuffix();

        $this->submitRequestWithFiles(
            $I,
            'HU011-' . $suffix,
            'hu011-noselinkea-' . $suffix . '@example.com',
            ''
        );

        $I->see('No se pudo enviar');
        $I->seeInSource('Debe seleccionar un estudiante a asesorar');
    }

    public function envioValidoYReenvioBloqueadoSoloLectura(FunctionalTester $I): void
    {
        $suffix = $this->uniqueSuffix();
        $applicantId = 'HU011-' . $suffix;
        $email = 'hu011-' . $suffix . '@example.com';

        // Primer envío exitoso
        $this->submitValidRequest($I, $applicantId, $email);
        $I->see('Solicitud enviada');
        $I->seeInSource('en revisi');

        // Segundo intento con mismo ID debe rechazarse (estado bloqueado: En Revisión)
        // Esto valida que NO es editable una vez iniciado (solo lectura)
        $this->submitRequestWithFiles(
            $I,
            $applicantId,  // Mismo ID debe fallar
            'hu011-' . $this->uniqueSuffix() . '@example.com',  // Email distinto
            'ST-HU011-001'
        );

        // No debe ver "Solicitud enviada" (re-envío bloqueado)
        $I->dontSee('Solicitud enviada');
        $I->see('No se pudo enviar');  // Error al intentar reenviar
    }

    private function submitValidRequest(FunctionalTester $I, string $applicantId, string $email): void
    {
        $this->submitRequestWithFiles($I, $applicantId, $email, 'ST-HU011-001');
    }

    private function submitRequestWithFiles(
        FunctionalTester $I,
        string $applicantId,
        string $email,
        string $linkedStudentId
    ): void {
        $I->amOnPage('/registro.php');

        $I->fillField('input[name="applicant_id"]', $applicantId);
        $I->fillField('input[name="full_name"]', 'Asesor Externo Prueba HU011');
        $I->fillField('input[name="email"]', $email);
        $I->fillField('input[name="telefono"]', '88888888');
        $I->fillField('input[name="institution"]', 'Organización Externa de Prueba');
        $I->fillField('input[name="specialization"]', 'Arquitectura de Software');
        $I->fillField('input[name="linked_student_id"]', $linkedStudentId);

        $idTipoTel = $this->grabFirstTipoTelOrEmpty($I);
        if ($idTipoTel !== '') {
            $I->selectOption('select[name="id_tipo_tel"]', $idTipoTel);
        }

        $I->attachFile('input[name="cv_document"]', 'sample_cv.pdf');
        $I->attachFile('input[name="id_copy_document"]', 'sample_id.pdf');
        $I->click('button[type="submit"]');
    }

    private function uniqueSuffix(): string
    {
        return date('YmdHis') . '-' . (string) random_int(1000, 9999);
    }

    private function grabFirstTipoTelOrEmpty(FunctionalTester $I): string
    {
        try {
            $value = $I->grabAttributeFrom('#inp-tipo-tel option:first-child', 'value');
            return is_string($value) ? $value : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
