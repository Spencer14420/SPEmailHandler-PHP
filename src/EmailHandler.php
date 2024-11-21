<?php

declare(strict_types=1);

namespace spencer14420\PhpEmailHandler;

use PHPMailer\PHPMailer\PHPMailer;
use spencer14420\PhpEmailHandler\CaptchaVerifier;
use spencer14420\SpAntiCsrf\AntiCsrf;

class EmailHandler
{
    private $mailboxEmail;
    private $fromEmail;
    private $replyToEmail;
    private $siteDomain;
    private $siteName;
    private $captchaToken;
    private $captchaSecret;
    private $captchaVerifyURL;
    private $captchaVerifier;
    private $checkCsrf;
    private $csrfToken;

    public function __construct(string $configFile)
    {
        require_once $configFile;

        // Set default and validate email variables
        $this->mailboxEmail = $this->validateAndSetEmail($mailboxEmail, '$mailboxEmail');
        $this->fromEmail = $this->validateAndSetEmail($fromEmail, '$fromEmail', $this->mailboxEmail);
        $this->replyToEmail = $this->validateAndSetEmail($replyToEmail, '$replyToEmail', $this->mailboxEmail);

        // Set the properties
        $this->siteDomain = $siteDomain ?? filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL);
        $this->siteName = $siteName ?? ucfirst(explode('.', $this->siteDomain)[0]); // Capitalized domain without TLD
        $this->captchaToken = $captchaToken ?? null;
        $this->captchaSecret = $captchaSecret ?? null;
        $this->captchaVerifyURL = filter_var($captchaVerifyURL, FILTER_VALIDATE_URL) ?: null;
        $this->checkCsrf = $checkCsrf ?? false;
        $this->csrfToken = $csrfToken ?? null;
        $this->captchaVerifier = new CaptchaVerifier($this->captchaSecret, $this->captchaVerifyURL);
    }

    private function validateAndSetEmail(string $emailVar, string $emailVarName = "A configuration variable", ?string $defaultEmail = null): string
    {
        if (empty($emailVar) && $defaultEmail) {
            $emailVar = $defaultEmail;
        }
        
        if (empty($emailVar) || !filter_var($emailVar, FILTER_VALIDATE_EMAIL)) {
            $this->jsonErrorResponse("Server error: {$emailVarName} is not set or is invalid.");
        }
        
        return $emailVar;
    }

    private function jsonErrorResponse(string $message = "An error occurred. Please try again later.", int $code = 500): void
    {
        http_response_code($code);
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    private function verifyCaptcha(): void
    {
        try {
            $this->captchaVerifier->verify($this->captchaToken, $_SERVER['REMOTE_ADDR']);
        } catch (\Exception $e) {
            $this->jsonErrorResponse($e->getMessage(), 403);
        }
    }

    private function verifyCsrf(): void
    {
        if (!$this->checkCsrf) {
            return;
        }

        if (empty($this->csrfToken)) {
            $this->jsonErrorResponse('Server error: $csrfToken does not exist or is not set.');
        }

        $csrfVerifier = new AntiCsrf();
        if (!$csrfVerifier->tokenIsValid($this->csrfToken)) {
            $this->jsonErrorResponse("Error: There was an issue with your session. Please refresh the page and try again.", 403);
        }
    }

    private function sendEmail(
        PHPMailer $email,
        string $from,
        string $to,
        string $subject,
        string $body,
        ?string $replyTo = null
    ): void {
        $email->setFrom($from, $this->siteName);
        $email->addAddress($to);
        $email->Subject = $subject;
        $email->Body = $body;

        if ($replyTo) {
            $email->addReplyTo($replyTo);
        }

        if (!$email->send()) {
            $this->jsonErrorResponse("Error: " . $email->ErrorInfo);
        }
    }


    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErrorResponse("Error: Method not allowed", 405);
        }

        $this->verifyCaptcha();
        $this->verifyCsrf();

        // Sanitize user inputs
        $email = filter_var($_POST["email"] ?? "", FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars($_POST["message"] ?? "");
        $name = htmlspecialchars($_POST["name"] ?? "somebody");

        if (empty($email) || empty($message)) {
            $this->jsonErrorResponse("Error: Missing required fields.", 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonErrorResponse("Error: Invalid email address.", 422);
        }

        // Prepare and send the main email to the mailbox
        $this->sendEmail(
            new PHPMailer(),
            $this->fromEmail,
            $this->mailboxEmail,
            "Message from $name via $this->siteDomain",
            "From: {$name} ({$email})\n\nMessage:\n" . wordwrap($message, 70),
            $email
        );

        // Prepare and send the confirmation email to the sender
        $this->sendEmail(
            new PHPMailer(),
            $this->fromEmail,
            $email,
            "Your message to $this->siteName has been received",
            "Dear $name ($email),\n\nYour message (shown below) has been received. We will get back to you as soon as possible.\n\nSincerely,\n$this->siteName\n\nPlease note: This message was sent to the email address provided in our contact form. If you did not enter your email, please disregard this message.\n\nYour message:\n$message",
            $this->replyToEmail
        );

        echo json_encode(['status' => 'success']);
    }
}