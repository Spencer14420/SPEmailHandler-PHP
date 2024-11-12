<?php
namespace spencer14420\PhpEmailHandler;

class EmailHandler
{
    private $mailboxEmail;
    private $fromEmail;
    private $replyToEmail;
    private $siteDomain;
    private $siteName;

    public function __construct($configFile)
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
    }

    private function validateEmailVar($emailVar)
    {
        if (!isset($emailVar) || empty($emailVar) || !filter_var($emailVar, FILTER_VALIDATE_EMAIL)) {
            $this->jsonErrorResponse("Error: Server configuration error.", 500);
        }
    }

    private function setDefaultEmailIfEmpty(&$emailVar, $defaultEmail)
    {
        if (!isset($emailVar) || empty($emailVar)) {
            $emailVar = $defaultEmail;
        }
    }

    private function jsonErrorResponse($message = "An error occurred. Please try again later.", $code = 500)
    {
        http_response_code($code);
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErrorResponse("Error: Method not allowed", 405);
        }

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
        $headers = "From: {$this->siteName} <{$this->fromEmail}>\r\nReply-To: $email";
        $body = "From: {$name} ({$email})\n\nMessage:\n" . wordwrap($message, 70);
        $messageSent = mail($this->mailboxEmail, "Message from {$name} via {$this->siteDomain}", $body, $headers);

        if (!$messageSent) {
            $this->jsonErrorResponse("Failed to send the message. Please try again later.", 500);
        }

        // Prepare and send the confirmation email to the sender
        $headers = "From: {$this->siteName} <{$this->fromEmail}>\r\nReply-To: {$this->replyToEmail}";
        $confirmationMessage = "Dear {$name} ({$email}),\n\nYour message (shown below) has been received. We will get back to you as soon as possible.\n\nSincerely,\n{$this->siteName}\n\nPlease note: This message was sent to the email address provided in our contact form. If you did not enter your email, please disregard this message.\n\nYour message:\n" . wordwrap($message, 70);
        mail($email, "Your message to {$this->siteName} has been received", $confirmationMessage, $headers);

        echo json_encode(['status' => 'success']);
    }
}