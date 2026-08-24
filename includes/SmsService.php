<?php

class SmsService {
    private $username;
    private $password;
    private $from;

    // TODO: Replace these placeholders with the actual Body IDs from your Melipayamak panel
    const BODY_ID_OTP = '12345'; // کد تایید
    const BODY_ID_BOOKING = '12345'; // رزرو نوبت
    const BODY_ID_SHIPPING = '12345'; // ارسال سفارش
    const BODY_ID_SUBSCRIPTION = '12345'; // بسته اشتراک
    const BODY_ID_CHARITY = '12345'; // خیریه

    public function __construct() {
        $this->username = '09146676978'; // Provided username
        $this->password = 'NZ456QM9L'; // Using the password provided
        $this->from = '2170007653'; // Sender line
    }

    private function sendPatternRequest($phone, $bodyId, array $textVariables) {
        // MeliPayamak REST API expects multiple pattern variables to be joined by a semicolon
        $textString = implode(';', $textVariables);

        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'from' => $this->from,
            'to' => $phone,
            'text' => $textString,
            'bodyId' => $bodyId
        ];
        
        $url = "https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("SmsService cURL Error: " . $err);
            return false;
        }

        $result = json_decode($response, true);
        
        // Typical success check for MeliPayamak (RetStatus is 1 for success)
        if (isset($result['RetStatus']) && $result['RetStatus'] == 1) {
             return true;
        }
        
        error_log("SmsService Pattern API Error: " . $response);
        return false;
    }

    const BODY_ID_RESCHEDULE = '12345'; // تغییر زمان نوبت (فورس ماژور)

    public function sendOtp($phone, $code) {
        return $this->sendPatternRequest($phone, self::BODY_ID_OTP, [$code]);
    }
    
    public function sendBookingConfirmation($phone, $date, $time) {
        return $this->sendPatternRequest($phone, self::BODY_ID_BOOKING, [$date, $time]);
    }

    public function sendAppointmentReschedule($phone, $doctorName, $petName, $newDate, $newTime, $reason = '') {
        // Sends pattern SMS, or direct SMS fallback
        $text = "کاربر گرامی آسنا، زمان نوبت ویزیت پت شما ($petName) با دکتر $doctorName به علت «" . ($reason ?: 'موارد فورس‌ماژور و هماهنگی مجدد مطب') . "» به تاریخ $newDate ساعت $newTime تغییر یافت.";
        
        $patternSent = $this->sendPatternRequest($phone, self::BODY_ID_RESCHEDULE, [$doctorName, $petName, $newDate, $newTime]);
        if (!$patternSent) {
            $this->sendDirectSms($phone, $text);
        }
        return true;
    }

    public function sendDirectSms($phone, $text) {
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'from'     => $this->from,
            'to'       => $phone,
            'text'     => $text,
            'isflash'  => false
        ];
        $url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    public function sendShippingUpdate($phone, $orderId) {
        return $this->sendPatternRequest($phone, self::BODY_ID_SHIPPING, [$orderId]);
    }
    
    public function sendSubscriptionSent($phone) {
        return $this->sendPatternRequest($phone, self::BODY_ID_SUBSCRIPTION, ['ماهانه']);
    }
    
    public function sendCharityThankYou($phone, $amount) {
        $formattedAmount = number_format($amount);
        return $this->sendPatternRequest($phone, self::BODY_ID_CHARITY, [$formattedAmount]);
    }
}
