<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class LoginPageCest
{
    public function loginPageLoads(FunctionalTester $I): void
    {
        $I->amOnPage('/login.php');
        $I->seeElement('#user');
        $I->seeElement('#pass');
        $I->seeElement('#saveForm');
    }
}
