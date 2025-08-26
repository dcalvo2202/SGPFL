<?php
class HelloWorldCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function testHelloWorld(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Hello World');
    }
}