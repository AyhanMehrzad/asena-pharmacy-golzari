<?php
/**
 * SmsService.php â€” Universal Meli Payamak SMS Gateway for ASENA Platform
 *
 * Supports:
 * 1. Modern Meli Payamak Console REST API (Token-based: /api/send/otp, /api/send/shared, /api/send/simple)
 * 2. Classic Meli Payamak REST API (BaseServiceNumber, SendSMS, GetCredit)
 * 3. Classic Meli Payamak SOAP API (SendByBaseNumber, SendSimpleSMS2, GetCredit)
 * 4. Multi-layer configuration priority (Database site_settings -> .env/getenv -> constants -> defaults)
 */

class SmsService {
    private $apiKey;
    private $username;
    private $password;
    private $from;
    private $pdo;
    private static $lastError = '';
    private static $lastResponse = null;

    // Pattern Body IDs from Melipayamak panel (Can be overridden via DB, .env or constants)
    const BODY_ID_OTP            = '518597'; // ع©ط¯ طھط§غŒغŒط¯ ظˆط±ظˆط¯/ط«ط¨طھ ظ†ط§ظ…/ظپط±ط§ظ…ظˆط´غŒ ط±ظ…ط²
    const BODY_ID_BOOKING        = '528861'; // طھط§غŒغŒط¯ ط±ط²ط±ظˆ ظ†ظˆط¨طھ ط¨ظ‡ ع©ط§ط±ط¨ط±
    const BODY_ID_RESCHEDULE     = '528862'; // طھط؛غŒغŒط± ط²ظ…ط§ظ† ظ†ظˆط¨طھ
    const BODY_ID_SHIPPING       = '528863'; // ط§ط±ط³ط§ظ„ ط³ظپط§ط±ط´ ط¨ظ‡ ط®ط±غŒط¯ط§ط±
    const BODY_ID_SUBSCRIPTION   = '528864'; // ظپط¹ط§ظ„â€Œط³ط§ط²غŒ ط¨ط³طھظ‡ ط§ط´طھط±ط§ع©
    const BODY_ID_CHARITY        = '528865'; // ظ‚ط¯ط±ط¯ط§ظ†غŒ ط®غŒط±غŒظ‡
    const BODY_ID_ADMIN_ORDER    = '528866'; // ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ظ…ط¯غŒط±
    const BODY_ID_DOCTOR_BOOKING = '528867'; // ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ظ†ظˆط¨طھ ط¬ط¯غŒط¯ ط¨ظ‡ ظ¾ط²ط´ع©

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
        if (!$this->pdo && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
        }

        // 1. API Key (Console Token or Web Service Key)
        $this->apiKey = $this->resolveConfig('melipayamak_api_key', 'MELIPAYAMAK_API_KEY', 'd3cbc1e6-79e8-4a25-910e-35e86370cad0');

        // 2. Panel Username
        $this->username = $this->resolveConfig('melipayamak_username', 'MELIPAYAMAK_USERNAME', '09146676978');

        // 3. Panel Password / ApiKey
        $this->password = $this->resolveConfig('melipayamak_password', 'MELIPAYAMAK_PASSWORD', 'NZ456QM9L');

        // 4. Sender Line Number
        $this->from = $this->resolveConfig('melipayamak_from', 'MELIPAYAMAK_FROM', '2170007653');
    }

    /**
     * Resolve configuration value from DB -> getenv -> defined constant -> default
     */
    private function resolveConfig(string $dbKey, string $envKey, string $default): string {
        if ($this->pdo && function_exists('get_setting')) {
            $dbVal = get_setting($this->pdo, $dbKey, null);
            if (!empty($dbVal)) return trim((string)$dbVal);
        }
        $envVal = getenv($envKey);
        if (!empty($envVal)) return trim((string)$envVal);
        if (defined($envKey) && !empty(constant($envKey))) return trim((string)constant($envKey));
        return $default;
    }

    /**
     * Get effective body ID for a pattern type
     */
    public static function getBodyId($type, ?PDO $pdo = null) {
        $type = strtolower(trim($type));
        $dbKey = 'melipayamak_body_id_' . $type;
        $envKey = 'MELIPAYAMAK_BODY_ID_' . strtoupper($type);

        if ($pdo && function_exists('get_setting')) {
            $dbVal = get_setting($pdo, $dbKey, null);
            if (!empty($dbVal)) return trim((string)$dbVal);
        }

        $envVal = getenv($envKey);
        if (!empty($envVal)) return trim((string)$envVal);

        $constMap = [
            'otp'            => self::BODY_ID_OTP,
            'booking'        => self::BODY_ID_BOOKING,
            'shipping'       => self::BODY_ID_SHIPPING,
            'subscription'   => self::BODY_ID_SUBSCRIPTION,
            'charity'        => self::BODY_ID_CHARITY,
            'reschedule'     => self::BODY_ID_RESCHEDULE,
            'admin_order'    => self::BODY_ID_ADMIN_ORDER,
            'doctor_booking' => self::BODY_ID_DOCTOR_BOOKING,
        ];

        return $constMap[$type] ?? '12345';
    }

    /**
     * Normalize Iranian phone number to standard 09... format
     */
    public static function normalizePhone($phone) {
        $phone = trim((string)$phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);
        if (strpos($phone, '+98') === 0) {
            $phone = '0' . substr($phone, 3);
        } elseif (strpos($phone, '0098') === 0) {
            $phone = '0' . substr($phone, 4);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }
        return $phone;
    }

    public static function getLastError() {
        return self::$lastError;
    }

    public static function getLastResponse() {
        return self::$lastResponse;
    }

    /**
     * Send OTP / Verification code
     */
    public function sendOtp($phone, $code) {
        $phone = self::normalizePhone($phone);
        if (empty($phone) || strlen($phone) < 10) {
            self::$lastError = 'ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ع¯غŒط±ظ†ط¯ظ‡ ظ†ط§ظ…ط¹طھط¨ط± ط§ط³طھ.';
            return false;
        }

        $code = (string)$code;
        $bodyId = self::getBodyId('otp', $this->pdo);

        // 1. Try Console OTP API
        if (!empty($this->apiKey)) {
            $url = "https://console.melipayamak.com/api/send/otp/{$this->apiKey}";
            // Try standard OTP JSON payload
            $res = $this->postJson($url, ['to' => $phone, 'text' => $code]);
            if ($res['ok']) return true;

            // Try shared-style OTP JSON payload
            if (!empty($bodyId) && $bodyId !== '12345') {
                $res2 = $this->postJson($url, ['to' => $phone, 'bodyId' => (int)$bodyId, 'args' => [$code]]);
                if ($res2['ok']) return true;
            }
        }

        // 2. Try Pattern Request (Console Shared -> Classic REST BaseServiceNumber -> Classic SOAP)
        $patternSent = $this->sendPatternRequest($phone, $bodyId, [$code]);
        if ($patternSent) {
            return true;
        }

        // 3. Fallback: Direct SMS
        $text = "ع©ط¯ طھط§غŒغŒط¯ ط´ظ…ط§ ط¯ط± ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§: $code\nasena.company";
        return $this->sendDirectSms($phone, $text);
    }

    /**
     * Send Booking Confirmation to User
     */
    public function sendBookingConfirmation($phone, $date, $time) {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('booking', $this->pdo);
        $sent = $this->sendPatternRequest($phone, $bodyId, [$date, $time]);
        if (!$sent) {
            $text = "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒطŒ ظ†ظˆط¨طھ ظˆغŒط²غŒطھ ط´ظ…ط§ ط¯ط± ط¢ط³ظ†ط§ ط¨ط±ط§غŒ طھط§ط±غŒط® $date ط³ط§ط¹طھ $time ط¨ط§ ظ…ظˆظپظ‚غŒطھ طھط§غŒغŒط¯ ط´ط¯.\nasena.company";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send Appointment Reschedule Alert
     */
    public function sendAppointmentReschedule($phone, $doctorName, $petName, $newDate, $newTime, $reason = '') {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('reschedule', $this->pdo);
        $patternSent = $this->sendPatternRequest($phone, $bodyId, [$doctorName, $petName, $newDate, $newTime]);
        if (!$patternSent) {
            $text = "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒ ط¢ط³ظ†ط§طŒ ط²ظ…ط§ظ† ظ†ظˆط¨طھ ظˆغŒط²غŒطھ ظ¾طھ ط´ظ…ط§ ($petName) ط¨ط§ ط¯ع©طھط± $doctorName ط¨ظ‡ ط¹ظ„طھ آ«" . ($reason ?: 'ظ…ظˆط§ط±ط¯ ظپظˆط±ط³â€Œظ…ط§عکظˆط± ظˆ ظ‡ظ…ط§ظ‡ظ†ع¯غŒ ظ…ط¬ط¯ط¯ ظ…ط·ط¨') . "آ» ط¨ظ‡ طھط§ط±غŒط® $newDate ط³ط§ط¹طھ $newTime طھط؛غŒغŒط± غŒط§ظپطھ.\nasena.company";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send Shipping / Order status update to buyer
     */
    public function sendShippingUpdate($phone, $orderId) {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('shipping', $this->pdo);
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$orderId]);
        if (!$sent) {
            $text = "ط³ظپط§ط±ط´ ط´ظ…ط§ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ $orderId ط¯ط± ط¢ط³ظ†ط§ ظ¾ط±ط¯ط§ط²ط´ ظˆ طھط­ظˆغŒظ„ ظˆط§ط­ط¯ ط§ط±ط³ط§ظ„ ط´ط¯.\nasena.company";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send Subscription Confirmation to user
     */
    public function sendSubscriptionSent($phone, $planName = 'ظ…ط§ظ‡ط§ظ†ظ‡') {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('subscription', $this->pdo);
        $sent = $this->sendPatternRequest($phone, $bodyId, [$planName]);
        if (!$sent) {
            $text = "ط§ط´طھط±ط§ع© $planName ط´ظ…ط§ ط¯ط± ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ظپط¹ط§ظ„ ع¯ط±ط¯غŒط¯.\nasena.company";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send Charity Donation Thank You
     */
    public function sendCharityThankYou($phone, $amount) {
        $phone = self::normalizePhone($phone);
        $formattedAmount = number_format((float)$amount);
        $bodyId = self::getBodyId('charity', $this->pdo);
        $sent = $this->sendPatternRequest($phone, $bodyId, [$formattedAmount]);
        if (!$sent) {
            $text = "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒطŒ ط§ط² ط­ظ…ط§غŒطھ ط§ط±ط²ط´ظ…ظ†ط¯ ط´ظ…ط§ ط¨ظ‡ ظ…ط¨ظ„ط؛ $formattedAmount طھظˆظ…ط§ظ† ط¨ظ‡ ظ¾ظˆغŒط´ ط®غŒط±غŒظ‡ ط­غŒظˆط§ظ†ط§طھ ط¢ط³ظ†ط§ ط³ظ¾ط§ط³ع¯ط²ط§ط±غŒظ….\nasena.company";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send New Order Notification to Admin(s)
     */
    public function sendAdminNewOrderAlert($phones, $orderId, $totalAmount) {
        if (empty($phones)) return false;

        $phoneList = is_array($phones) ? $phones : preg_split('/[,\s;]+/', (string)$phones);
        $formattedAmount = number_format((float)$totalAmount);
        $bodyId = self::getBodyId('admin_order', $this->pdo);

        $atLeastOneSent = false;
        foreach ($phoneList as $p) {
            $p = self::normalizePhone($p);
            if (empty($p)) continue;

            $sent = $this->sendPatternRequest($p, $bodyId, [(string)$orderId, $formattedAmount]);
            if (!$sent) {
                $text = "ظ…ط¯غŒط± ع¯ط±ط§ظ…غŒطŒ ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ $orderId ط¨ط§ ظ…ط¨ظ„ط؛ $formattedAmount طھظˆظ…ط§ظ† ط¯ط± ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§ ط«ط¨طھ ط´ط¯.\nasena.company";
                $this->sendDirectSms($p, $text);
            }
            $atLeastOneSent = true;
        }

        return $atLeastOneSent;
    }

    /**
     * Send New Appointment Notification to Doctor
     */
    public function sendDoctorNewAppointmentAlert($phone, $doctorName, $petName, $date, $time) {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) return false;

        $doctorName = $doctorName ?: 'ظ‡ظ…ع©ط§ط± ع¯ط±ط§ظ…غŒ';
        $petName    = $petName ?: 'ظ¾طھ ط¨غŒظ…ط§ط±';
        $bodyId     = self::getBodyId('doctor_booking', $this->pdo);

        $sent = $this->sendPatternRequest($phone, $bodyId, [$doctorName, $petName, $date, $time]);
        if (!$sent) {
            $text = "ط¯ع©طھط± $doctorName ع¯ط±ط§ظ…غŒطŒ ظ†ظˆط¨طھ ط¬ط¯غŒط¯ ط¨ط±ط§غŒ ظ¾طھ ($petName) ط¯ط± طھط§ط±غŒط® $date ط³ط§ط¹طھ $time ط¯ط± ط¢ط³ظ†ط§ ط«ط¨طھ ط´ط¯.\nasena.company";
            $this->sendDirectSms($phone, $text);
        }

        return true;
    }

    /**
     * Send Pattern / Shared Service Request (Multi-tier: Console Shared -> Classic REST -> Classic SOAP)
     */
    public function sendPatternRequest($phone, $bodyId, array $textVariables) {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) return false;

        // Skip invalid/dummy pattern
        if ($bodyId === '12345' || empty($bodyId)) {
            self::$lastError = 'ط´ظ†ط§ط³ظ‡ ظ¾طھط±ظ† طھظ†ط¸غŒظ… ظ†ط´ط¯ظ‡ ط§ط³طھ.';
            return false;
        }

        // Tier 1: Modern Console Shared REST API
        if (!empty($this->apiKey)) {
            $url = "https://console.melipayamak.com/api/send/shared/{$this->apiKey}";
            $res = $this->postJson($url, ['to' => $phone, 'bodyId' => (int)$bodyId, 'args' => array_values($textVariables)]);
            if (!empty($res['ok'])) {
                self::$lastResponse = $res;
                return true;
            }
        }

        // Tier 2: Classic REST API (BaseServiceNumber)
        $textString = implode(';', $textVariables);
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'from'     => $this->from,
            'to'       => $phone,
            'text'     => $textString,
            'bodyId'   => $bodyId
        ];
        $url = "https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber";
        $res = $this->postForm($url, $data);
        self::$lastResponse = $res;

        if ($res['ok']) {
            $json = json_decode($res['body'], true);
            if (isset($json['RetStatus']) && ($json['RetStatus'] == 1 || (is_numeric($json['Value']) && (float)$json['Value'] > 100))) {
                return true;
            }
            if (isset($json['Value']) && is_numeric($json['Value']) && (float)$json['Value'] > 100) {
                return true;
            }
        }

        // Tier 3: Classic SOAP API (SendByBaseNumber2)
        $soapRes = $this->sendSoapBaseNumber($phone, $bodyId, $textString);
        if ($soapRes['ok']) {
            self::$lastResponse = $soapRes;
            return true;
        }

        self::$lastError = 'ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ط¨ط§ ظ¾طھط±ظ† ط®ط¯ظ…ط§طھغŒ ظ†ط§ظ…ظˆظپظ‚ ط¨ظˆط¯: ' . ($res['body'] ?? $soapRes['error'] ?? 'ط®ط·ط§غŒ ظ†ط§ط´ظ†ط§ط®طھظ‡');
        return false;
    }

    /**
     * Send Direct / Simple SMS (Used for general alerts and fallback)
     */
    public function sendDirectSms($phone, $text) {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) return false;

        // Tier 1: Console Simple SMS
        if (!empty($this->apiKey)) {
            $url = "https://console.melipayamak.com/api/send/simple/{$this->apiKey}";
            $res = $this->postJson($url, ['to' => $phone, 'from' => $this->from, 'text' => $text]);
            if (!empty($res['ok'])) {
                self::$lastResponse = $res;
                return true;
            }
        }

        // Tier 2: Classic REST SendSMS
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'from'     => $this->from,
            'to'       => $phone,
            'text'     => $text,
            'isflash'  => false
        ];
        $url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";
        $res = $this->postForm($url, $data);
        self::$lastResponse = $res;

        if ($res['ok']) {
            $json = json_decode($res['body'], true);
            if (isset($json['RetStatus']) && $json['RetStatus'] == 1) {
                return true;
            }
            if (isset($json['Value']) && is_numeric($json['Value']) && (float)$json['Value'] > 100) {
                return true;
            }
        }

        // Tier 3: Classic SOAP SendSimpleSMS2
        $soapRes = $this->sendSoapSimple($phone, $text);
        if ($soapRes['ok']) {
            self::$lastResponse = $soapRes;
            return true;
        }

        self::$lastError = 'ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ظ…ط³طھظ‚غŒظ… ظ†ط§ظ…ظˆظپظ‚ ط¨ظˆط¯: ' . ($res['body'] ?? $soapRes['error'] ?? 'ط®ط·ط§غŒ ظ†ط§ظ…ط´ط®طµ');
        return false;
    }

    /**
     * Diagnose gateway connectivity & check balance
     */
    public function checkCredit() {
        // 1. Try Classic REST GetCredit
        $url = "https://rest.payamak-panel.com/api/SendSMS/GetCredit";
        $res = $this->postForm($url, ['username' => $this->username, 'password' => $this->password]);
        if ($res['ok']) {
            $json = json_decode($res['body'], true);
            if (isset($json['Value']) && is_numeric($json['Value']) && (float)$json['Value'] >= 0) {
                return ['ok' => true, 'credit' => (float)$json['Value'], 'source' => 'REST'];
            }
        }

        // 2. Try SOAP GetCredit
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetCredit xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($this->username) . '</username>
      <password>' . htmlspecialchars($this->password) . '</password>
    </GetCredit>
  </soap:Body>
</soap:Envelope>';

        $ch = curl_init('http://api.payamak-panel.com/post/Send.asmx');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/GetCredit"',
                'Content-Length: ' . strlen($xml)
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err && preg_match('/<GetCreditResult>([-\d\.]+)<\/GetCreditResult>/', (string)$response, $m)) {
            $val = (float)$m[1];
            if ($val >= 0) {
                return ['ok' => true, 'credit' => $val, 'source' => 'SOAP'];
            }
            return ['ok' => false, 'code' => $val, 'error' => self::translateErrorCode($val), 'raw' => $response];
        }

        return ['ok' => false, 'error' => 'ط§ظ…ع©ط§ظ† ط¯ط±غŒط§ظپطھ ظ…ظˆط¬ظˆط¯غŒ ط§ط² ط³ط±ظˆط± ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع© ظˆط¬ظˆط¯ ظ†ط¯ط§ط±ط¯.', 'raw' => $res['body'] ?? $err];
    }

    /**
     * Map Melipayamak numerical error code to descriptive Persian text
     */
    public static function translateErrorCode($code) {
        $code = (int)$code;
        switch ($code) {
            case 0:
            case -111:
                return 'ظ†ط§ظ… ع©ط§ط±ط¨ط±غŒ غŒط§ ط±ظ…ط² ط¹ط¨ظˆط± ط§ط´طھط¨ط§ظ‡ ط§ط³طھ.';
            case -108:
                return 'ط¢ط¯ط±ط³ IP ط´ظ…ط§ ط¨ظ‡ ط¹ظ„طھ ط¯ط±ط®ظˆط§ط³طھâ€Œظ‡ط§غŒ ظ†ط§ظ…ظˆظپظ‚ ظ…ع©ط±ط± ظ…ط³ط¯ظˆط¯ ظ…ظˆظ‚طھ ط´ط¯ظ‡ ط§ط³طھ.';
            case -109:
                return 'ط§ظ„ط²ط§ظ… ط¨ظ‡ طھط¹ط±غŒظپ IP ظ…ط¬ط§ط² ط¯ط± ظ¾ظ†ظ„ ظ…ظ„غŒ ظ¾غŒط§ظ…ع© (ظ…ظ†ظˆغŒ طھظ†ط¸غŒظ…ط§طھ -> ط¯ط³طھط±ط³غŒ ظˆط¨â€Œط³ط±ظˆغŒط³).';
            case -110:
                return 'ط§ظ„ط²ط§ظ… ط¨ظ‡ ط§ط³طھظپط§ط¯ظ‡ ط§ط² ApiKey ط§ط®طھطµط§طµغŒ ط¨ظ‡ ط¬ط§غŒ ط±ظ…ط² ط¹ط¨ظˆط± ط¯ط± ظˆط¨â€Œط³ط±ظˆغŒط³.';
            case 35:
                return 'ط´ظ…ط§ط±ظ‡ ع¯غŒط±ظ†ط¯ظ‡ ط¯ط± ظ„غŒط³طھ ط³غŒط§ظ‡ ظ…ط®ط§ط¨ط±ط§طھ ط§ط³طھ (ط¹ط¯ظ… ط¯ط±غŒط§ظپطھ ظ¾غŒط§ظ…ع© طھط¨ظ„غŒط؛ط§طھغŒ). ط§ط±ط³ط§ظ„ ط¨ط§غŒط¯ ط­طھظ…ط§ظ‹ ط¨ط§ ط§ظ„ع¯ظˆغŒ ط®ط¯ظ…ط§طھغŒ (Pattern) ط§ظ†ط¬ط§ظ… ط´ظˆط¯.';
            default:
                return "ع©ط¯ ط®ط·ط§غŒ ط³ط§ظ…ط§ظ†ظ‡ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©: $code";
        }
    }

    private function postJson($url, array $data) {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        $isSuccess = ($httpCode >= 200 && $httpCode < 300) && (!isset($decoded['status']) || (strpos((string)$decoded['status'], 'ط®ط·ط§') === false && strpos((string)$decoded['status'], 'ظ…ط¹طھط¨ط± ظ†غŒط³طھ') === false));

        return ['ok' => $isSuccess, 'http_code' => $httpCode, 'data' => $decoded, 'body' => $response, 'error' => $err];
    }

    private function postForm($url, array $data) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 300) && empty($err);
        return ['ok' => $isSuccess, 'http_code' => $httpCode, 'body' => $response, 'error' => $err];
    }

    private function sendSoapBaseNumber($phone, $bodyId, $textString) {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendByBaseNumber2 xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($this->username) . '</username>
      <password>' . htmlspecialchars($this->password) . '</password>
      <text>' . htmlspecialchars($textString) . '</text>
      <to>' . htmlspecialchars($phone) . '</to>
      <bodyId>' . (int)$bodyId . '</bodyId>
    </SendByBaseNumber2>
  </soap:Body>
</soap:Envelope>';

        $ch = curl_init('http://api.payamak-panel.com/post/Send.asmx');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/SendByBaseNumber2"',
                'Content-Length: ' . strlen($xml)
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err && preg_match('/<SendByBaseNumber2Result>([-\d]+)<\/SendByBaseNumber2Result>/', (string)$response, $m)) {
            $val = (float)$m[1];
            if ($val > 100) {
                return ['ok' => true, 'rec_id' => $val, 'raw' => $response];
            }
        }
        return ['ok' => false, 'error' => $err ?: 'ظ¾ط§ط³ط® ظ†ط§ظ…ظˆظپظ‚ ظˆط¨â€Œط³ط±ظˆغŒط³ SOAP', 'raw' => $response];
    }

    private function sendSoapSimple($phone, $text) {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendSimpleSMS2 xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($this->username) . '</username>
      <password>' . htmlspecialchars($this->password) . '</password>
      <to>' . htmlspecialchars($phone) . '</to>
      <from>' . htmlspecialchars($this->from) . '</from>
      <text>' . htmlspecialchars($text) . '</text>
      <isflash>false</isflash>
    </SendSimpleSMS2>
  </soap:Body>
</soap:Envelope>';

        $ch = curl_init('http://api.payamak-panel.com/post/Send.asmx');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/SendSimpleSMS2"',
                'Content-Length: ' . strlen($xml)
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err && preg_match('/<SendSimpleSMS2Result>([-\d]+)<\/SendSimpleSMS2Result>/', (string)$response, $m)) {
            $val = (float)$m[1];
            if ($val > 100) {
                return ['ok' => true, 'rec_id' => $val, 'raw' => $response];
            }
        }
        return ['ok' => false, 'error' => $err ?: 'ظ¾ط§ط³ط® ظ†ط§ظ…ظˆظپظ‚ ظˆط¨â€Œط³ط±ظˆغŒط³ SOAP SendSimpleSMS2', 'raw' => $response];
    }
}

