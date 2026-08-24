<?php

$data = [
    'username' => '09146676978',
    'password' => 'NZ456QM9L',
    'to' => '09146676978', // Send to self
    'from' => '2170007653',
    'text' => 'تست سیستم',
    'isFlash' => false
];

echo "Testing standard SendSMS with JSON...\n";
$url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "cURL Error: " . $err . "\n";
} else {
    echo "Response: " . $response . "\n";
}
