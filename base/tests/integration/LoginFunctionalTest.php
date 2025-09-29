<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

class LoginFunctionalTest extends \PHPUnit\Framework\TestCase
{
    protected $webDriver;

    public function setUp(): void
    {
        $host = 'http://10.251.34.229:4444/wd/hub'; // Cambia si Selenium está en otro host/puerto
        $this->webDriver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
    }

    public function testSimple()
    {
        $this->webDriver->get('https://www.google.com');
        $this->assertStringContainsString('Google', $this->webDriver->getTitle());
    }
    /*public function testLogin()
    {
        $this->webDriver->get('http://localhost/base/login.php');
        $this->webDriver->findElement(WebDriverBy::id('user'))->sendKeys('205610158');
        $this->webDriver->findElement(WebDriverBy::id('pass'))->sendKeys('secret123');
        $this->webDriver->executeScript('Do_Login();');
        // Puedes esperar y verificar el resultado, por ejemplo:
        sleep(2);
        $this->assertStringContainsString('Bienvenido', $this->webDriver->getPageSource());
    }*/

    public function tearDown(): void
    {
        $this->webDriver->quit();
    }
}