<?php
/**
 * test_sms.php — Comprehensive Diagnostic Test for Melipayamak Gateway
 *
 * Runs phone normalization tests, sends a test pattern OTP, and prints full diagnostic logs.
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../includes/SmsService.php';

echo "=========================================================\n";
echo "      ASENA PLATFORM — MELIPAYAMAK SMS DIAGNOSTIC TOOL   \n";
echo "=========================================================\n\n";

// 1. Phone Normalizer Tests
echo "--- 1. Testing Phone Number Normalization ---\n";
$testCases = [
    "۰۹۱۴۶۶۷۶۹۷۸"         => "09146676978 (Persian Digits)",
    "٠٩١٤٦٦٧٦٩٧٨"         => "09146676978 (Arabic Digits)",
    "+989146676978"        => "09146676978 (+98 International)",
    "00989146676978"       => "09146676978 (0098 International)",
    "989146676978"         => "09146676978 (98 Prefix)",
    "9146676978"           => "09146676978 (10-digit without 0)",
    "0914 667 6978"        => "09146676978 (Spaces)",
    "0914-667-6978"        => "09146676978 (Hyphens)"
];

foreach ($testCases as $input => $label) {
    $normalized = SmsService::normalizePhone($input);
    echo "  [TEST] Input: '$input' -> Output: '$normalized' ($label)\n";
}
echo "\n";

// 2. Target Phone & Test Parameters
$targetPhone = $_GET['phone'] ?? '09146676978';
$targetPhone = SmsService::normalizePhone($targetPhone);
$testCode    = (string)mt_rand(100000, 999999);

echo "--- 2. Sending Pattern OTP Request ---\n";
echo "  Target Phone : $targetPhone\n";
echo "  Generated OTP: $testCode\n";
echo "  Pattern Body : " . SmsService::getBodyId('otp') . "\n\n";

$sms = new SmsService();
$result = $sms->sendOtp($targetPhone, $testCode);

echo "--- 3. Transmission Result ---\n";
echo "  Success Status : " . ($result ? "TRUE (Delivered / Accepted)" : "FALSE (Failed)") . "\n";
echo "  Gateway Summary: " . $sms->getLastError() . "\n\n";

echo "--- 4. Full Diagnostic Log ---\n";
$log = $sms->getLastLog();
if (!empty($log)) {
    echo "  Action         : " . ($log['action'] ?? 'N/A') . "\n";
    echo "  Endpoint URL   : " . ($log['url'] ?? 'N/A') . "\n";
    echo "  HTTP Status    : " . ($log['http_code'] ?? 'N/A') . "\n";
    echo "  Latency        : " . ($log['duration_ms'] ?? 0) . " ms\n";
    echo "  cURL Error     : " . (!empty($log['curl_error']) ? $log['curl_error'] : "None") . "\n";
    echo "  Request Payload: " . json_encode($log['payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "  Raw Response   : " . ($log['raw_response'] ?? 'N/A') . "\n";
    echo "  RetStatus Code : " . ($log['ret_status'] ?? 'N/A') . "\n";
    echo "  Value Code     : " . ($log['value'] ?? 'N/A') . "\n";
    echo "  Diagnosis      : " . ($log['interpretation'] ?? 'N/A') . "\n";
} else {
    echo "  No log available.\n";
}

echo "\n=========================================================\n";
