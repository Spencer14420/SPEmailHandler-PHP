# SP PHP Email Handler

A PHP package for handling contact form submissions. Features include:

- **Email delivery**: Sends messages to both the recipient mailbox and confirmation emails to the sender.
- **Captcha verification**: Supports Google reCAPTCHA, Cloudflare Turnstile, or custom captchas.
- **CSRF protection**: Validates requests using anti-CSRF tokens.
- **Error handling**: Returns JSON responses with appropriate HTTP status codes and error messages.

## Requirements

- PHP 7.4 or higher
- A configured `config.php` file with required variables

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

## Configuration

Create a `config.php` file with the following variables:

```php
<?php
$mailboxEmail = 'your-mailbox@example.com'; // Required
//$fromEmail = 'no-reply@example.com'; // Optional, default: $mailboxEmail`
//$replyToEmail = 'support@example.com'; // Optional, defaults to `$mailboxEmail`
//$siteDomain = 'example.com'; // Optional, defaults to `$_SERVER['HTTP_HOST']`
//$siteName = 'Example'; // Optional, derived from `$siteDomain` if not set
//$captchaToken = ''; // Optional, if a CAPTCHA is used set this to the POST variable containing the CAPTCHA token
//$captchaSecret = 'your-recaptcha-secret'; // Optional, if captcha is not used
//$captchaVerifyURL = 'https://www.google.com/recaptcha/api/siteverify'; // Optional
//$checkCsrf = true; // Enable or disable CSRF protection
//$csrfToken = ''; // Set this from the POST request
```
