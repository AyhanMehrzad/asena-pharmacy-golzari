<?php
/**
 * ASENA Payment Gateway — Dual Mode: ZarinPal Production & Local Mock Simulator
 */

define('ZARINPAL_MERCHANT_ID', getenv('ZARINPAL_MERCHANT_ID') ?: '00000000-0000-0000-0000-000000000000');
define('ZARINPAL_SANDBOX', (bool)(getenv('ZARINPAL_SANDBOX') ?: true));

class ZarinPalGateway
{
    private string $merchant_id;
    private string $request_url;
    private string $verify_url;
    private string $payment_base;
    private bool $is_mock;

    public function __construct()
    {
        $this->merchant_id = ZARINPAL_MERCHANT_ID;
        $this->is_mock = ZARINPAL_SANDBOX || empty($this->merchant_id) || $this->merchant_id === '00000000-0000-0000-0000-000000000000';
        
        $base = ZARINPAL_SANDBOX ? 'sandbox.zarinpal.com' : 'payment.zarinpal.com';
        $this->request_url  = "https://{$base}/pg/v4/payment/request.json";
        $this->verify_url   = "https://{$base}/pg/v4/payment/verify.json";
        $this->payment_base = "https://{$base}/pg/StartPay/";
    }

    /**
     * Request a payment authority token.
     */
    public function requestPayment(
        int    $amount_tomans,
        string $description,
        string $callback_url,
        array  $metadata = []
    ): array {
        // If in Mock / Sandbox Mode or no merchant ID configured:
        if ($this->is_mock) {
            $authority = 'ASENA-MOCK-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 16));
            
            // Build dynamic base path
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
            $subDir = str_replace($docRoot, '', $appRoot);
            $baseUrl = $scheme . '://' . $host . $subDir;

            $mockUrl = $baseUrl . '/mock_payment_gateway.php?' . http_build_query([
                'authority' => $authority,
                'amount'    => $amount_tomans,
                'desc'      => $description,
                'callback'  => $callback_url
            ]);

            return [
                'success'     => true,
                'authority'   => $authority,
                'payment_url' => $mockUrl,
            ];
        }

        // Real Production ZarinPal
        $payload = [
            'merchant_id'  => $this->merchant_id,
            'amount'       => $amount_tomans * 10, // Tomans → Rials
            'description'  => mb_substr($description, 0, 255),
            'callback_url' => $callback_url,
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->post($this->request_url, $payload);

        if (($response['data']['code'] ?? null) === 100) {
            $authority = $response['data']['authority'];
            return [
                'success'     => true,
                'authority'   => $authority,
                'payment_url' => $this->payment_base . $authority,
            ];
        }

        return [
            'success' => false,
            'error'   => $response['errors']['message']
                         ?? "کد خطا: " . ($response['data']['code'] ?? 'نامشخص'),
        ];
    }

    /**
     * Verify a returned payment authority.
     */
    public function verifyPayment(int $amount_tomans, string $authority): array
    {
        // Mock Gateway Verification
        if (str_starts_with($authority, 'ASENA-MOCK-') || $this->is_mock) {
            return [
                'success'          => true,
                'ref_id'           => 'TRX-' . rand(10000000, 99999999),
                'already_verified' => false,
            ];
        }

        // Real Production Verification
        $payload = [
            'merchant_id' => $this->merchant_id,
            'amount'      => $amount_tomans * 10,
            'authority'   => $authority,
        ];

        $response = $this->post($this->verify_url, $payload);
        $code     = $response['data']['code'] ?? null;

        if ($code === 100 || $code === 101) {
            return [
                'success'          => true,
                'ref_id'           => (string)($response['data']['ref_id'] ?? ''),
                'already_verified' => ($code === 101),
            ];
        }

        return [
            'success' => false,
            'error'   => $response['errors']['message']
                         ?? "پرداخت تأیید نشد (کد: {$code}).",
        ];
    }

    /** Internal HTTP POST with JSON payload. */
    private function post(string $url, array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch   = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || !$result) {
            error_log("ZarinPal cURL error: {$error}");
            return ['errors' => ['message' => "خطا در اتصال به درگاه پرداخت."]];
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ZarinPal invalid JSON (HTTP {$status}): {$result}");
            return ['errors' => ['message' => "پاسخ نامعتبر از درگاه."]];
        }

        return $decoded;
    }
}
