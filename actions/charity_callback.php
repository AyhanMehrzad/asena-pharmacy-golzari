<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/gateway.php';

if (!isset($_GET['Authority']) || !isset($_GET['Status'])) {
    die("Invalid request");
}

$authority = $_GET['Authority'];
$status    = $_GET['Status'];

if (!isset($_SESSION['pending_donation']) || $_SESSION['pending_donation']['authority'] !== $authority) {
    $_SESSION['charity_error'] = "تراکنش یافت نشد یا منقضی شده است.";
    header('Location: ../charity.php');
    exit;
}

$donation = $_SESSION['pending_donation'];

if ($status !== 'OK') {
    $_SESSION['charity_error'] = "پرداخت توسط کاربر لغو شد یا ناموفق بود.";
    
    // Log failed
    $stmt = $pdo->prepare("INSERT INTO donations (user_id, donor_name, campaign_id, amount, status, payment_reference) VALUES (?, ?, ?, ?, 'failed', ?)");
    $stmt->execute([$donation['user_id'], $donation['donor_name'], $donation['campaign_id'], $donation['amount'], $authority]);
    
    unset($_SESSION['pending_donation']);
    header('Location: ../charity.php');
    exit;
}

$gateway = new ZarinPalGateway();
$verify  = $gateway->verifyPayment($donation['amount'], $authority);

if ($verify['success']) {
    $ref_id = $verify['ref_id'];
    
    // Insert donation
    $stmt = $pdo->prepare("INSERT INTO donations (user_id, donor_name, campaign_id, amount, status, payment_reference) VALUES (?, ?, ?, ?, 'successful', ?)");
    $stmt->execute([$donation['user_id'], $donation['donor_name'], $donation['campaign_id'], $donation['amount'], $ref_id]);
    
    // Update campaign
    if ($donation['campaign_id']) {
        $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
        $stmt->execute([$donation['amount'], $donation['campaign_id']]);
    }
    
    // Send SMS Thank You
    if (!empty($donation['user_id'])) {
        require_once '../includes/SmsService.php';
        $user_stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $user_stmt->execute([$donation['user_id']]);
        $donor = $user_stmt->fetch(PDO::FETCH_ASSOC);
        if ($donor && !empty($donor['phone'])) {
            $sms = new SmsService();
            $sms->sendCharityThankYou($donor['phone'], $donation['amount']);
        }
    }
    
    $_SESSION['charity_success'] = "پرداخت با موفقیت انجام شد. از حمایت شما سپاسگزاریم! کد رهگیری: " . $ref_id;
    unset($_SESSION['pending_donation']);
    header('Location: ../charity.php');
    exit;
} else {
    // Log failed
    $stmt = $pdo->prepare("INSERT INTO donations (user_id, donor_name, campaign_id, amount, status, payment_reference) VALUES (?, ?, ?, ?, 'failed', ?)");
    $stmt->execute([$donation['user_id'], $donation['donor_name'], $donation['campaign_id'], $donation['amount'], $authority]);

    $_SESSION['charity_error'] = "خطا در تایید پرداخت: " . $verify['error'];
    unset($_SESSION['pending_donation']);
    header('Location: ../charity.php');
    exit;
}
