<?php
declare(strict_types=1);
namespace spencer14420\PhpEmailHandler;

use PHPUnit\Framework\TestCase;
use spencer14420\PhpEmailHandler\EmailHandler;
use PHPMailer\PHPMailer\PHPMailer;
use spencer14420\PhpEmailHandler\CaptchaVerifier;
use spencer14420\SpAntiCsrf\AntiCsrf;
use Exception;

final class EmailHandlerTest extends TestCase
{
   public function testConstructorValidConfigFile(): void 
   {
        $configFile = __DIR__ . '/test_config.php';
        file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com";');

        $emailHandler = new EmailHandler($configFile);
        $this->assertInstanceOf(EmailHandler::class, $emailHandler);

        unlink($configFile);
   }

   public function testConstructorInvalidConfigFile(): void 
   {
        $this->expectException(\Error::class);
        $configFile = __DIR__ . '/invalid_config.php';
        $emailHandler = new EmailHandler($configFile);
   }

   public function testValidateAndSetEmail(): void 
   {
        $configFile = __DIR__ . '/test_config.php';
        file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com";');

        $emailHandler = new EmailHandler($configFile);
        $method = new \ReflectionMethod(EmailHandler::class, 'validateAndSetEmail');
        $method->setAccessible(true);

        $result = $method->invoke($emailHandler, 'valid@mail.com', 'Test Email');
        $this->assertEquals('valid@mail.com', $result);

        $this->expectException(Exception::class);
        $method->invoke($emailHandler, 'invalidemail', 'Test Email');

        unlink($configFile);
   }

   public function testJsonErrorResponse(): void
   {
       $this->expectOutputString(json_encode(['status' => 'error', 'message' => 'Test error']));
       $configFile = __DIR__ . '/test_config.php';
       file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com";');
       
       $emailHandler = new EmailHandler($configFile);
       $method = new \ReflectionMethod(EmailHandler::class, 'jsonErrorResponse');
       $method->setAccessible(true);

       $this->expectException(Exception::class);
       $method->invoke($emailHandler, 'Test error', 500);
   }

    public function testVerifyCaptcha(): void
    {
        $captchaVerifier = $this->createMock(CaptchaVerifier::class);
        $captchaVerifier->expects($this->once())
            ->method('verify')
            ->with($this->anything(), $this->anything());

        $configFile = __DIR__ . '/test_config.php';
        file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com"; $captchaSecret = "captchaSecret";');
        
        $emailHandler = new EmailHandler($configFile);
        $method = new \ReflectionMethod(EmailHandler::class, 'verifyCaptcha');
        $method->setAccessible(true);
        $method->invoke($emailHandler);
    }

    public function testVerifyCsrf(): void
    {
        $csrfVerifier = $this->createMock(AntiCsrf::class);
        $csrfVerifier->expects($this->once())
            ->method('tokenIsValid')
            ->with($this->anything())
            ->willReturn(true);
        
        $configFile = __DIR__ . '/test_config.php';
        file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com"; $replyToEmail = "replyto@mail.com"; $csrfToken = "csrfToken";');
        
        $emailHandler = new EmailHandler($configFile);
        $method = new \ReflectionMethod(EmailHandler::class, 'verifyCsrf');
        $method->setAccessible(true);
        $method->invoke($emailHandler);
    }

    public function testSendEmail(): void
    {
        $phpMailer = $this->createMock(PHPMailer::class);
        $phpMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $configFile = __DIR__ . '/test_config.php';
        file_put_contents($configFile, '<?php $mailboxEmail = "test@mail.com"; $fromEmail = "from@mail.com";');
        
        $emailHandler = new EmailHandler($configFile);
        $method = new \ReflectionMethod(EmailHandler::class, 'sendEmail');
        $method->setAccessible(true);
        $method->invoke($emailHandler, $phpMailer, 'from@mail.com', 'to@mail.com', 'Subject', 'Body', 'replyTo@mail.com');
    }
}
