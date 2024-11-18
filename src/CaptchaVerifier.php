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

    public function verify(string $token, string $remoteIp): bool
    {
        if (empty($this->secret) || empty($this->verifyUrl)) {
            return true; // Skip verification if CAPTCHA is not configured
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
            curl_close($curl);
            throw new \Exception("CAPTCHA verification failed due to a network error: " . curl_error($curl));
        }

        $responseData = json_decode($response, true);
        curl_close($curl);

        if (!empty($responseData['error-codes'])) {
            throw new \Exception("CAPTCHA verification failed: " . implode(", ", $responseData['error-codes']));
        }

        return isset($responseData['success']) && $responseData['success'] === true;
    }
}
