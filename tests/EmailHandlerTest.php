<?php
declare(strict_types=1);
namespace spencer14420\PhpEmailHandler;

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;
use spencer14420\SpAntiCsrf\AntiCsrf;

final class EmailHandlerTest extends TestCase
{
    private $configFile;

    protected function setUp(): void
    {
        $this->configFile = __DIR__ . '/testConfig.php';
        file_put_contents($this->configFile, '<?php
            $mailboxEmail = "mailbox@example.com";
            $fromEmail = "from@example.com";
            $replyToEmail = "replyto@example.com";
            $siteDomain = "example.com";
            $siteName = "Example";
            $captchaToken = "testCaptchaToken";
            $captchaSecret = "testCaptchaSecret";
            $captchaVerifyURL = "https://www.google.com/recaptcha/api/siteverify";
            $checkCsrf = true;
            $csrfToken = "testCsrfToken";
        ');
    }

    protected function tearDown(): void
    {
        unlink($this->configFile);
    }

    public function testClassConstructor(): void
    {
        $emailHandler = new EmailHandler($this->configFile);
        $this->assertInstanceOf(EmailHandler::class, $emailHandler);
    }

    public function testValidateAndSetEmail(): void
    {
        $emailHandler = new EmailHandler($this->configFile);
        $reflection = new \ReflectionClass($emailHandler);
        $method = $reflection->getMethod('validateAndSetEmail');
        $method->setAccessible(true);

        $validEmail = 'test@example.com';
        $this->assertEquals($validEmail, $method->invokeArgs($emailHandler, [$validEmail, 'testEmail']));

        $this->expectOutputString(json_encode(['status' => 'error', 'message' => 'Server error: testEmail is not set or is invalid.']));
        $method->invokeArgs($emailHandler, ['', 'testEmail']);
    }

    public function testJsonErrorResponse(): void
    {
        $emailHandler = new EmailHandler($this->configFile);
        $reflection = new \ReflectionClass($emailHandler);
        $method = $reflection->getMethod('jsonErrorResponse');
        $method->setAccessible(true);

        $this->expectOutputString(json_encode(['status' => 'error', 'message' => 'Test error message']));
        $method->invokeArgs($emailHandler, ['Test error message']);
    }

    public function testVerifyCaptcha(): void
    {
        $captchaVerifierMock = $this->createMock(CaptchaVerifier::class);
        $captchaVerifierMock->expects($this->once())
            ->method('verify')
            ->will($this->throwException(new \Exception('Captcha verification failed')));

        $emailHandler = new EmailHandler($this->configFile);
        $reflection = new \ReflectionClass($emailHandler);
        $property = $reflection->getProperty('captchaVerifier');
        $property->setAccessible(true);
        $property->setValue($emailHandler, $captchaVerifierMock);

        $method = $reflection->getMethod('verifyCaptcha');
        $method->setAccessible(true);

        $this->expectOutputString(json_encode(['status' => 'error', 'message' => 'Captcha verification failed']));
        $method->invoke($emailHandler);
    }

    public function testVerifyCsrf(): void
    {
        $csrfVerifierMock = $this->createMock(AntiCsrf::class);
        $csrfVerifierMock->expects($this->once())
            ->method('tokenIsValid')
            ->willReturn(false);

        $emailHandler = new EmailHandler($this->configFile);
        $reflection = new \ReflectionClass($emailHandler);
        $property = $reflection->getProperty('checkCsrf');
        $property->setAccessible(true);
        $property->setValue($emailHandler, true);

        $property = $reflection->getProperty('csrfToken');
        $property->setAccessible(true);
        $property->setValue($emailHandler, 'invalidToken');

        $method = $reflection->getMethod('verifyCsrf');
        $method->setAccessible(true);

        $this->expectOutputString(json_encode(['status' => 'error', 'message' => 'Error: There was an issue with your session. Please refresh the page and try again.']));
        $method->invoke($emailHandler);
    }

    public function testSendEmail(): void
    {
        $emailMock = $this->createMock(PHPMailer::class);
        $emailMock->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $emailHandler = new EmailHandler($this->configFile);
        $reflection = new \ReflectionClass($emailHandler);
        $method = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invokeArgs($emailHandler, [$emailMock, 'from@example.com', 'to@example.com', 'Test Subject', 'Test Body']);
    }

    public function testHandleRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['email'] = 'test@example.com';
        $_POST['message'] = 'Test message';
        $_POST['name'] = 'Test Name';

        $emailHandler = new EmailHandler($this->configFile);
        $this->expectOutputString(json_encode(['status' => 'success']));
        $emailHandler->handleRequest();
    }
}
