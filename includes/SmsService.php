<?php
/**
 * SmsService.php — Universal Meli Payamak SMS Gateway for ASENA Platform
 *
 * Supports:
 * 1. Classic Meli Payamak REST API (BaseServiceNumber, SendSMS, GetCredit)
 * 2. Modern Meli Payamak Console REST API (/api/send/shared, /api/send/otp, /api/send/simple)
 * 3. Classic Meli Payamak SOAP API (SendByBaseNumber2, SendSimpleSMS2, GetCredit)
 * 4. Multi-layer configuration priority (Database site_settings -> .env/getenv -> constants -> defaults)
 * 5. Automatic username sanitization (stripping leading 0) and API Key credential binding
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
    const BODY_ID_OTP            = '518597'; // کد تایید ورود/ثبت نام/فراموشی رمز (login/signup asena.company)
    const BODY_ID_BOOKING        = '528861'; // تایید رزرو نوبت به کاربر (تایید رزرو نوبت آسنا)
    const BODY_ID_RESCHEDULE     = '528862'; // تغییر زمان نوبت ویزیت
    const BODY_ID_SHIPPING       = '528863'; // ارسال سفارش
    const BODY_ID_SUBSCRIPTION   = '528864'; // فعال‌سازی اشتراک
    const BODY_ID_CHARITY        = '528865'; // تشکر واریز خیریه
    const BODY_ID_ADMIN_ORDER    = '528866'; // سفارش جدید به مدیر
    const BODY_ID_DOCTOR_BOOKING = '528867'; // نوبت جدید به پزشک

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
        if (!$this->pdo && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
        }

        // 1. API Key (Web Service ApiKey or Console Token)
        $this->apiKey = $this->resolveConfig('melipayamak_api_key', 'MELIPAYAMAK_API_KEY', 'd3cbc1e6-79e8-4a25-910e-35e86370cad0');

        // 2. Panel Username (e.g. 09146676978 or 9146676978)
        $this->username = $this->resolveConfig('melipayamak_username', 'MELIPAYAMAK_USERNAME', '09146676978');

        // 3. Panel Password / ApiKey
        $this->password = $this->resolveConfig('melipayamak_password', 'MELIPAYAMAK_PASSWORD', 'd3cbc1e6-79e8-4a25-910e-35e86370cad0');

        // 4. Sender Line Number (Default user line: 50004001914667)
        $this->from = $this->resolveConfig('melipayamak_from', 'MELIPAYAMAK_FROM', '50004001914667');
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
     * Get effective username for Melipayamak web services (must be without leading 0 for mobile numbers)
     */
    public function getEffectiveUsername(): string {
        $u = trim((string)$this->username);
        if (preg_match('/^0(9\d{9})$/', $u, $m)) {
            return $m[1]; // e.g. 9146676978
        }
        return $u;
    }

    /**
     * Get effective password for Melipayamak web services (ApiKey is required by Melipayamak code -110)
     */
    public function getEffectivePassword(): string {
        if (!empty($this->apiKey)) {
            return trim((string)$this->apiKey);
        }
        return trim((string)$this->password);
    }

    /**
     * Get effective body ID for a pattern type
     */
    public static function getBodyId($type, ?PDO $pdo = null) {
        $type = strtolower(trim((string)$type));
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

        return $constMap[$type] ?? '518597';
    }

    /**
     * Normalize Iranian phone number to standard 09... format
     * Supports Persian/Arabic digits, international prefixes (+98, 0098, 98), dashes, and spaces
     */
    public static function normalizePhone($phone) {
        $phone = trim((string)$phone);
        // 1. Convert Persian / Arabic numerals to standard English digits
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $phone = str_replace($persian, $english, $phone);
        $phone = str_replace($arabic, $english, $phone);

        // 2. Remove all non-digits
        $phone = preg_replace('/[^\d]/', '', $phone);

        // 3. Handle prefixes
        if (strpos($phone, '0098') === 0) {
            $phone = '0' . substr($phone, 4);
        } elseif (strpos($phone, '98') === 0 && strlen($phone) >= 12) {
            $phone = '0' . substr($phone, 2);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }
        return $phone;
    }

    /**
     * Normalize OTP digits (converts Persian/Arabic numerals to 0-9)
     */
    public static function normalizeOtp($otp) {
        $otp = trim((string)$otp);
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $otp = str_replace($persian, $english, $otp);
        $otp = str_replace($arabic, $english, $otp);
        return preg_replace('/[^\d]/', '', $otp);
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
            self::$lastError = 'شماره موبایل گیرنده نامعتبر است.';
            return false;
        }

        $code = (string)$code;
        $bodyId = self::getBodyId('otp', $this->pdo);

        // Send via Multi-Tier Pattern Engine
        $sent = $this->sendPatternRequest($phone, $bodyId, [$code]);
        if ($sent) {
            return true;
        }

        // Fallback: Direct SMS
        $text = "کاربرگرامی کد تایید شما : $code می باشد. با تشکر. ASENA";
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
            $text = "کاربر گرامی، نوبت ویزیت شما در آسنا برای تاریخ $date ساعت $time با موفقیت تایید شد. asena";
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
            $text = "کاربر گرامی آسنا، زمان نوبت ویزیت پت شما ($petName) با دکتر $doctorName به تاریخ $newDate ساعت $newTime تغییر یافت. asena";
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
            $text = "سفارش شما به شماره $orderId در آسنا پردازش و تحویل واحد ارسال شد. asena";
            return $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    /**
     * Send Subscription Confirmation to user
     */
    public function sendSubscriptionSent($phone, $planName = 'ماهانه') {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('subscription', $this->pdo);
        $sent = $this->sendPatternRequest($phone, $bodyId, [$planName]);
        if (!$sent) {
            $text = "اشتراک $planName شما در سامانه آسنا با موفقیت فعال گردید. asena";
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
            $text = "کاربر گرامی، از حمایت ارزشمند شما به مبلغ $formattedAmount تومان به پویش خیریه حیوانات آسنا سپاسگزاریم. asena";
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
                $text = "مدیر گرامی، سفارش جدید به شماره $orderId با مبلغ $formattedAmount تومان در سامانه آسنا ثبت شد. asena";
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

        $doctorName = $doctorName ?: 'همکار گرامی';
        $petName    = $petName ?: 'پت بیمار';
        $bodyId     = self::getBodyId('doctor_booking', $this->pdo);

        $sent = $this->sendPatternRequest($phone, $bodyId, [$doctorName, $petName, $date, $time]);
        if (!$sent) {
            $text = "دکتر $doctorName گرامی، نوبت جدید برای پت ($petName) در تاریخ $date ساعت $time در آسنا ثبت شد. asena";
            $this->sendDirectSms($phone, $text);
        }

        return true;
    }

    /**
     * Send Pattern / Shared Service Request
     * Multi-tier: Classic REST BaseServiceNumber -> Console Shared -> Classic SOAP SendByBaseNumber2
     */
    public function sendPatternRequest($phone, $bodyId, array $textVariables) {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) return false;

        if (empty($bodyId) || $bodyId === '12345') {
            self::$lastError = 'شناسه پترن تنظیم نشده است.';
            return false;
        }

        $textString = implode(';', array_values($textVariables));
        $effectiveUser = $this->getEffectiveUsername();
        $effectivePass = $this->getEffectivePassword();

        // Tier 1: Classic REST API (BaseServiceNumber) - Fastest & Most Compatible
        $url = "https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber";
        $payload = [
            'username' => $effectiveUser,
            'password' => $effectivePass,
            'text'     => $textString,
            'to'       => $phone,
            'bodyId'   => (int)$bodyId
        ];
        $res = $this->postJson($url, $payload);
        self::$lastResponse = $res;

        if ($res['ok'] && !empty($res['data'])) {
            $json = $res['data'];
            $retStatus = (int)($json['RetStatus'] ?? 0);
            $strRet = strtolower((string)($json['StrRetStatus'] ?? ''));
            $val = $json['Value'] ?? '';

            if ($retStatus === 1 || $strRet === 'ok' || (is_numeric($val) && (float)$val > 1000)) {
                return true;
            }
        }

        // Tier 2: Console Shared REST API (for console tokens)
        if (!empty($this->apiKey)) {
            $cUrl = "https://console.melipayamak.com/api/send/shared/{$this->apiKey}";
            $cRes = $this->postJson($cUrl, ['to' => $phone, 'bodyId' => (int)$bodyId, 'args' => array_values($textVariables)]);
            if (!empty($cRes['ok'])) {
                self::$lastResponse = $cRes;
                return true;
            }
        }

        // Tier 3: Classic SOAP API (SendByBaseNumber2)
        $soapRes = $this->sendSoapBaseNumber($phone, $bodyId, $textString);
        if ($soapRes['ok']) {
            self::$lastResponse = $soapRes;
            return true;
        }

        $rawErr = $res['raw'] ?? $soapRes['error'] ?? 'خطای نامشخص';
        self::$lastError = 'ارسال پیامک با پترن خدماتی ناموفق بود: ' . $rawErr;
        return false;
    }

    /**
     * Send Direct / Simple SMS (Used for general alerts and fallback)
     */
    public function sendDirectSms($phone, $text) {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) return false;

        $effectiveUser = $this->getEffectiveUsername();
        $effectivePass = $this->getEffectivePassword();

        // Tier 1: Classic REST SendSMS
        $payload = [
            'username' => $effectiveUser,
            'password' => $effectivePass,
            'from'     => $this->from,
            'to'       => $phone,
            'text'     => $text,
            'isflash'  => false
        ];
        $url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";
        $res = $this->postJson($url, $payload);
        self::$lastResponse = $res;

        if ($res['ok'] && !empty($res['data'])) {
            $json = $res['data'];
            $retStatus = (int)($json['RetStatus'] ?? 0);
            $val = $json['Value'] ?? '';
            if ($retStatus === 1 || (is_numeric($val) && (float)$val > 1000)) {
                return true;
            }
        }

        // Tier 2: Console Simple SMS
        if (!empty($this->apiKey)) {
            $url = "https://console.melipayamak.com/api/send/simple/{$this->apiKey}";
            $cRes = $this->postJson($url, ['to' => $phone, 'from' => $this->from, 'text' => $text]);
            if (!empty($cRes['ok'])) {
                self::$lastResponse = $cRes;
                return true;
            }
        }

        // Tier 3: Classic SOAP SendSimpleSMS2
        $soapRes = $this->sendSoapSimple($phone, $text);
        if ($soapRes['ok']) {
            self::$lastResponse = $soapRes;
            return true;
        }

        self::$lastError = 'ارسال پیامک مستقیم ناموفق بود: ' . ($res['raw'] ?? $soapRes['error'] ?? 'خطای نامشخص');
        return false;
    }

    /**
     * Diagnose gateway connectivity & check balance
     */
    public function checkCredit() {
        $effectiveUser = $this->getEffectiveUsername();
        $effectivePass = $this->getEffectivePassword();

        // 1. Try Classic REST GetCredit
        $url = "https://rest.payamak-panel.com/api/SendSMS/GetCredit";
        $res = $this->postJson($url, ['username' => $effectiveUser, 'password' => $effectivePass]);
        if ($res['ok'] && isset($res['data']['Value'])) {
            $val = (float)$res['data']['Value'];
            if ($val >= 0 && (int)($res['data']['RetStatus'] ?? 1) === 1) {
                return ['ok' => true, 'credit' => $val, 'source' => 'REST'];
            }
            if (isset($res['data']['RetStatus'])) {
                return ['ok' => false, 'code' => $res['data']['RetStatus'], 'error' => self::translateErrorCode($res['data']['RetStatus']), 'raw' => $res['raw']];
            }
        }

        // 2. Try SOAP GetCredit
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetCredit xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($effectiveUser) . '</username>
      <password>' . htmlspecialchars($effectivePass) . '</password>
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

        return ['ok' => false, 'error' => 'امکان دریافت موجودی از سرور ملی‌پیامک وجود ندارد.', 'raw' => $res['raw'] ?? $err];
    }

    /**
     * Map Melipayamak numerical error code to descriptive Persian text
     */
    public static function translateErrorCode($code) {
        $code = (int)$code;
        switch ($code) {
            case 0:
            case -111:
                return 'نام کاربری یا رمز عبور اشتباه است.';
            case -108:
                return 'آدرس IP سرور مسدود موقت شده است.';
            case -109:
                return 'الزام به تعریف IP مجاز در پنل ملی پیامک (منوی توسعه‌دهندگان -> تنظیمات وب‌سرویس).';
            case -110:
                return 'الزام به استفاده از ApiKey اختصاصی به جای رمز عبور در وب‌سرویس.';
            case 35:
                return 'شماره گیرنده در لیست سیاه مخابرات است یا ساختار داده نامعتبر است.';
            default:
                return "کد وضعیت سامانه ملی‌پیامک: $code";
        }
    }

    private function postJson($url, array $data) {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT        => 9,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        $isSuccess = ($httpCode >= 200 && $httpCode < 300) && !empty($decoded) && (!isset($decoded['status']) || (strpos((string)$decoded['status'], 'خطا') === false && strpos((string)$decoded['status'], 'معتبر نیست') === false));

        return ['ok' => $isSuccess, 'http_code' => $httpCode, 'data' => $decoded, 'raw' => $response, 'error' => $err];
    }

    private function sendSoapBaseNumber($phone, $bodyId, $textString) {
        $effectiveUser = $this->getEffectiveUsername();
        $effectivePass = $this->getEffectivePassword();

        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendByBaseNumber2 xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($effectiveUser) . '</username>
      <password>' . htmlspecialchars($effectivePass) . '</password>
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
            if ($val > 1000) {
                return ['ok' => true, 'rec_id' => $val, 'raw' => $response];
            }
        }
        return ['ok' => false, 'error' => $err ?: 'پاسخ ناموفق وب‌سرویس SOAP', 'raw' => $response];
    }

    private function sendSoapSimple($phone, $text) {
        $effectiveUser = $this->getEffectiveUsername();
        $effectivePass = $this->getEffectivePassword();

        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendSimpleSMS2 xmlns="http://tempuri.org/">
      <username>' . htmlspecialchars($effectiveUser) . '</username>
      <password>' . htmlspecialchars($effectivePass) . '</password>
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
            if ($val > 1000) {
                return ['ok' => true, 'rec_id' => $val, 'raw' => $response];
            }
        }
        return ['ok' => false, 'error' => $err ?: 'پاسخ ناموفق وب‌سرویس SOAP SendSimpleSMS2', 'raw' => $response];
    }
}
