<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class TfgMailHU026Cest
{
    private const ESTUDIANTE_ID = '118440202';
    private const ESTUDIANTE_PASS = 'secret123';

    public function envioCorreoDocumentoFinalRespondeSuccess(FunctionalTester $I): void
    {
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        $I->sendAjaxPostRequest('/mod/admin/users/send_tfg_mail.php', [
            'tipo' => 'Documento Final TFG',
        ]);

        $I->seeInSource('"success":true');
        $I->seeInSource('Correo enviado');

        $source = $I->grabPageSource();
        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);
        $I->assertStringNotContainsString('Acceso no autorizado', $source);
    }

    public function envioCorreoPropuestaRespondeSuccess(FunctionalTester $I): void
    {
        $this->loginAs($I, self::ESTUDIANTE_ID, self::ESTUDIANTE_PASS);

        $I->sendAjaxPostRequest('/mod/admin/users/send_tfg_mail.php', [
            'tipo' => 'Propuesta TFG',
        ]);

        $I->seeInSource('"success":true');
        $I->seeInSource('Correo enviado');

        $source = $I->grabPageSource();
        $I->assertStringNotContainsString('Fatal error', $source);
        $I->assertStringNotContainsString('Parse error', $source);
        $I->assertStringNotContainsString('Acceso no autorizado', $source);
    }

    private function loginAs(FunctionalTester $I, string $userId, string $password): void
    {
        $I->amOnPage('/login.php');
        $I->fillField('#user', $userId);
        $I->fillField('#pass', $password);
        $I->click('#saveForm');
    }
}