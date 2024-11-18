<?php

declare(strict_types=1);

namespace spencer14420\PhpEmailHandler;

use PHPMailer\PHPMailer\PHPMailer;
use spencer14420\PhpEmailHandler\CaptchaVerifier;

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

    public function __construct(string $configFile)
    {
        require_once $configFile;

        // Validate email variables
        $this->validateEmailVar($mailboxEmail);
        $this->setDefaultEmailIfEmpty($fromEmail, $mailboxEmail);
        $this->validateEmailVar($fromEmail);
        $this->setDefaultEmailIfEmpty($replyToEmail, $mailboxEmail);
        $this->validateEmailVar($replyToEmail);

        // Set the properties
        $this->mailboxEmail = $mailboxEmail;
        $this->fromEmail = $fromEmail;
        $this->replyToEmail = $replyToEmail;
        $this->siteDomain = isset($siteDomain) && !empty($siteDomain) ? $siteDomain : $_SERVER['HTTP_HOST'];
        $this->siteName = isset($siteName) && !empty($siteName) ? $siteName : ucfirst(explode('.', $this->siteDomain)[0]);
        $this->captchaToken = $captchaToken;
        $this->captchaSecret = isset($captchaSecret) && !empty($captchaSecret) ? $captchaSecret : "";
        $this->captchaVerifyURL = isset($captchaVerifyURL) && !empty($captchaVerifyURL) && filter_var($captchaVerifyURL, FILTER_VALIDATE_URL) ? $captchaVerifyURL : "";
    }

    private function validateEmailVar(string $emailVar): void
    {
        if (!isset($emailVar) || empty($emailVar) || !filter_var($emailVar, FILTER_VALIDATE_EMAIL)) {
            $this->jsonErrorResponse("Error: Server configuration error.", 500);
        }
    }

    private function setDefaultEmailIfEmpty(string &$emailVar, string $defaultEmail): void
    {
        if (!isset($emailVar) || empty($emailVar)) {
            $emailVar = $defaultEmail;
        }
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
            $captchaVerifier = new CaptchaVerifier($this->captchaSecret, $this->captchaVerifyURL);
            $captchaVerifier->verify($this->captchaToken, $_SERVER['REMOTE_ADDR']);
        } catch (\Exception $e) {
            $this->jsonErrorResponse($e->getMessage(), 403);
        }
    }

    private function sendEmail(
        PHPMailer $email,
        string $from,
        string $to,
        string $subject,
        string $body,
        string $replyTo = null
    ): void {
        $email->setFrom($from, $this->siteName);
        $email->addAddress($to);
        $email->Subject = $subject;
        $email->Body = $body;

        if ($replyTo) {
            $email->addReplyTo($replyTo);
        }

        if (!$email->send()) {
            $this->jsonErrorResponse("Error: " . $email->ErrorInfo, 500);
        }
    }


    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErrorResponse("Error: Method not allowed", 405);
        }

        $this->verifyCaptcha();

        // Sanitize user inputs
        $email = filter_var($_POST["email"] ?? "", FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars($_POST["message"] ?? "");
        $name = htmlspecialchars($_POST["name"] ?? "somebody");

        //Errors
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
            "Dear $name ($email),\n\nYour message has been received.",
            $this->replyToEmail
        );

        echo json_encode(['status' => 'success']);
    }
}