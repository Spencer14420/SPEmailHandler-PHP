<?php
namespace spencer14420\PhpEmailHandler;

class CaptchaVerifier
{
    private $secret;
    private $verifyUrl;

    public function __construct(string $secret, string $verifyUrl)
    {
        $this->secret = $secret;
        $this->verifyUrl = $verifyUrl;
    }

    public function verify(string $token, string $remoteIp): void
    {
        if (empty($this->secret) || empty($this->verifyUrl)) {
            return; // Skip verification if CAPTCHA is not configured
        }

        $data = [
            "secret" => $this->secret,
            "response" => $token,
            "remoteip" => $remoteIp,
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->verifyUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $this->jsonErrorResponse(
                "Error: Could not verify CAPTCHA due to a network error.",
                403
            );
        }

        $responseData = json_decode($response, true);
        curl_close($curl);

        if (!empty($responseData['error-codes'])) {
            $this->jsonErrorResponse(
                "Error: CAPTCHA verification failed.",
                403,
                ['captchaErrors' => $responseData['error-codes']]
            );
        }
    }

    private function jsonErrorResponse(string $message, int $code, array $additionalData = []): void
    {
        http_response_code($code);
        $response = array_merge(['status' => 'error', 'message' => $message], $additionalData);
        echo json_encode($response);
        exit;
    }
}
