# SP PHP Email Handler

A PHP package for handling contact form submissions. Features include:

- **Email delivery**: Sends messages to both the recipient mailbox and confirmation emails to the sender.
- **Captcha verification**: Supports Google reCAPTCHA, Cloudflare Turnstile, or custom captchas.
- **CSRF protection**: Validates requests using anti-CSRF tokens.
- **JSON error responses**: Provides standardized error messages with appropriate HTTP status codes.

## Requirements

- PHP 7.4 or higher
- A configured `config.php` file with required variables (see "Configuration" below)

All required libraries are bundled with this package, including:

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) for email sending
- [spencer14420\SpAntiCsrf](https://github.com/spencer14420/spanticcsrf) for CSRF protection

## Installation

1. Install the package via Composer:

   ```bash
   composer require spencer14420/sp-php-email-handler
   ```

2. Include the `autoload.php` file in your project:
   ```php
   require_once 'vendor/autoload.php';
   ```

## Usage

### Initialization

```php
use spencer14420\PhpEmailHandler\EmailHandler;

$emailHandler = new EmailHandler('/path/to/config.php');
```

### Handling Requests

```php
$emailHandler->handleRequest();
```

### Example Contact Form

```html
<form action="/path/to/email-handler.php" method="POST">
  <input type="hidden" name="csrfToken" value="your-csrf-token" />
  <input type="email" name="email" placeholder="Your Email" required />
  <input type="text" name="name" placeholder="Your Name" required />
  <textarea name="message" placeholder="Your Message" required></textarea>
  <button type="submit">Send</button>
</form>
```

### Example `email-handler.php`

```php
require_once 'vendor/autoload.php';

use spencer14420\PhpEmailHandler\EmailHandler;

try {
    $emailHandler = new EmailHandler('/path/to/config.php');
    $emailHandler->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
```

## Configuration

Create a `config.php` file with the following variables:

```php
#### REQUIRED ####

$mailboxEmail = 'your-mailbox@example.com';
// The email address where messages from the contact form will be sent.
// This must be provided and must be a valid email address.


#### OPTIONAL ####

$fromEmail = 'no-reply@example.com';
// The email address used as the "From" address in outgoing emails.
// If not provided, it defaults to the `$mailboxEmail`.

$replyToEmail = 'support@example.com';
// The email address used as the "Reply-To" address in outgoing emails.
// If not provided, it defaults to the `$mailboxEmail`.

$siteDomain = 'example.com';
// The domain name of the website (e.g., example.com).
// If not provided, it defaults to the server's host name (`$_SERVER['HTTP_HOST']`).

$siteName = 'Example';
// The name of the website, used in email subjects and greetings.
// If not provided, it is derived from `$siteDomain` (e.g., "Example" for "example.com").

$captchaToken = '';
// If using a CAPTCHA, this should be set to the POST variable containing the CAPTCHA token.
// If CAPTCHA is not used, leave this empty.

$captchaSecret = '';
// The secret key for verifying CAPTCHA responses, required if CAPTCHA is enabled.
// If CAPTCHA is not used, leave this empty.

$captchaVerifyURL = '';
// The URL used to verify the CAPTCHA response.
// e.g. https://www.google.com/recaptcha/api/siteverify

$checkCsrf = false;
// Set to `true` to enable CSRF protection or `false` to disable it.

$csrfToken = '';
// The CSRF token from the POST request. Required if `$checkCsrf` is set to `true`.
```
