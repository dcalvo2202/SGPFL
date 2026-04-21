<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class ExternalAdvisorProfileHU011Cest
{
    private const REAL_ADVISOR_ID = '402200272';
    private const REAL_ADVISOR_NAME = 'MARIA JESUS ALVARADO HERNANDEZ';
    private const REAL_ADVISOR_EMAIL = 'rodrigo.urena.castillo@est.una.ac.cr';
    private const REAL_ADVISOR_PHONE = '88888888';
    private const REAL_PHONE_TYPE = 'M'; // Móvil
    private const REAL_INSTITUTION = 'UTN';
    private const REAL_SPECIALIZATION = 'Ingeniería en sistemas';
    private const REAL_LINKED_STUDENT_ID = '503550224'; // Miguel Ángel Rodríguez Arias

    public function registroPageShowsHu011Fields(FunctionalTester $I): void
    {
        $I->amOnPage('/registro.php');

        $I->see('Solicitud de Integrante de Comité Asesor');
        $I->see('Su solicitud quedará en estado');
        $I->see('En Revisión');

        $I->seeElement('input[name="full_name"]');
        $I->seeElement('input[name="institution"]');
        $I->seeElement('input[name="specialization"]');
        $I->seeElement('input[name="cv_document"]');
        $I->seeElement('input[name="id_copy_document"]');
        $I->seeElement('input[name="cover_letter_document"]');
        $I->seeElement('input[name="linked_student_id"]');

        $I->see('Tamaño máximo: 5 MB');
        $I->see('Tamaño máximo: 2 MB');
    }

    public function rechazoCuandoNoHayEstudianteVinculado(FunctionalTester $I): void
    {
        $this->submitRequestWithFiles(
            $I,
            self::REAL_ADVISOR_ID,
            self::REAL_ADVISOR_EMAIL,
            ''
        );

        $I->see('No se pudo enviar');
        $I->seeInSource('Debe seleccionar un estudiante a asesorar');
    }

    public function envioValidoYReenvioBloqueadoSoloLectura(FunctionalTester $I): void
    {
        $applicantId = self::REAL_ADVISOR_ID;
        $email = self::REAL_ADVISOR_EMAIL;

        // Primer envío exitoso
        $this->submitValidRequest($I, $applicantId, $email);
        $I->see('Solicitud enviada');
        $I->seeInSource('en revisi');

        // Segundo intento con mismo ID debe rechazarse (estado bloqueado: En Revisión)
        // Esto valida que NO es editable una vez iniciado (solo lectura)
        $this->submitRequestWithFiles(
            $I,
            $applicantId,  // Mismo ID debe fallar
            'rodrigo.urena.castillo+' . $this->uniqueSuffix() . '@est.una.ac.cr',  // Email distinto
            self::REAL_LINKED_STUDENT_ID
        );

        // No debe ver "Solicitud enviada" (re-envío bloqueado)
        $I->dontSee('Solicitud enviada');
        $I->see('No se pudo enviar');  // Error al intentar reenviar
    }

    private function submitValidRequest(FunctionalTester $I, string $applicantId, string $email): void
    {
        $this->submitRequestWithFiles($I, $applicantId, $email, self::REAL_LINKED_STUDENT_ID);
    }

    private function submitRequestWithFiles(
        FunctionalTester $I,
        string $applicantId,
        string $email,
        string $linkedStudentId
    ): void {
        $I->amOnPage('/registro.php');

        $I->fillField('input[name="applicant_id"]', $applicantId);
        $I->fillField('input[name="full_name"]', self::REAL_ADVISOR_NAME);
        $I->fillField('input[name="email"]', $email);
        $I->fillField('input[name="telefono"]', self::REAL_ADVISOR_PHONE);
        $I->fillField('input[name="institution"]', self::REAL_INSTITUTION);
        $I->fillField('input[name="specialization"]', self::REAL_SPECIALIZATION);
        $I->fillField('input[name="linked_student_id"]', $linkedStudentId);

        if ($this->hasTipoTelOption($I, self::REAL_PHONE_TYPE)) {
            $I->selectOption('select[name="id_tipo_tel"]', self::REAL_PHONE_TYPE);
        } else {
            $idTipoTel = $this->grabFirstTipoTelOrEmpty($I);
            if ($idTipoTel !== '') {
                $I->selectOption('select[name="id_tipo_tel"]', $idTipoTel);
            }
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

    private function hasTipoTelOption(FunctionalTester $I, string $optionValue): bool
    {
        try {
            $allValues = $I->grabMultiple('#inp-tipo-tel option', 'value');
            return in_array($optionValue, $allValues, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
