<?php
require_once __DIR__ . "/email-config.php";

header('Content-Type: application/json');

// Function to send a JSON error response
function jsonErrorResponse($message = "An error occurred. Please try again later.", $code = 500) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Function to validate that an email variable is set and properly formatted
function validateEmailVar($emailVar) {
    if (!isset($emailVar) || empty($emailVar) || !filter_var($emailVar, FILTER_VALIDATE_EMAIL)) {
        jsonErrorResponse("Error: Server configuration error.", 500);
    }
}

// Function to set a default email if the email variable is not set or is empty
function setDefaultEmailIfEmpty(&$emailVar, $defaultEmail) {
    if (!isset($emailVar) || empty($emailVar)) {
        $emailVar = $defaultEmail;
    }
}

// Validate email variables
validateEmailVar($mailboxEmail);
setDefaultEmailIfEmpty($fromEmail, $mailboxEmail);
validateEmailVar($fromEmail);
setDefaultEmailIfEmpty($replyToEmail, $mailboxEmail);
validateEmailVar($replyToEmail);

// Set defaults for $siteDomain and $siteName if they are not set
if (!isset($siteDomain) || empty($siteDomain)) {
    $siteDomain = $_SERVER['HTTP_HOST'];
}
if (!isset($siteName) || empty($siteName)) {
    $siteName = ucfirst(explode('.', $siteDomain)[0]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErrorResponse("Error: Method not allowed", 405);
}

// Sanitize user inputs
$email = filter_var($_POST["email"] ?? "", FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars($_POST["message"] ?? "");
$name = htmlspecialchars($_POST["name"] ?? "somebody");

if (empty($email) || empty($message)) {
    jsonErrorResponse("Error: Missing required fields.", 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonErrorResponse("Error: Invalid email address.", 422);
}

// Prepare and send the main email to the mailbox
$headers = "From: {$siteName} <{$fromEmail}>\r\nReply-To: $email";
$body = "From: {$name} ({$email})\n\nMessage:\n" . wordwrap($message, 70);
$messageSent = mail($mailboxEmail, "Message from {$name} via {$siteDomain}", $body, $headers);

if (!$messageSent) {
    jsonErrorResponse("Failed to send the message. Please try again later.", 500);
}

// Prepare and send the confirmation email to the sender
$headers = "From: {$siteName} <{$fromEmail}>\r\nReply-To: $replyToEmail";
$confirmationMessage = "Dear {$name} ({$email}),\n\nYour message (shown below) has been received. We will get back to you as soon as possible.\n\nSincerely,\n{$siteName}\n\nPlease note: This message was sent to the email address provided in our contact form. If you did not enter your email, please disregard this message.\n\nYour message:\n" . wordwrap($message, 70);
mail($email, "Your message to {$siteName} has been received", $confirmationMessage, $headers);

echo json_encode(['status' => 'success']);
