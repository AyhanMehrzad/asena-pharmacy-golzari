<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/gateway.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: charity.php');
    exit;
}

$amount = (int)($_POST['amount'] ?? 0);
$campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
$is_anonymous = isset($_POST['is_anonymous']);
$donor_name = trim($_POST['donor_name'] ?? '');

if ($amount < 1000) {
    $_SESSION['charity_error'] = "حداقل مبلغ حمایت ۱,۰۰۰ تومان می‌باشد.";
    header('Location: charity.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$is_anonymous && $user_id && empty($donor_name)) {
    // Try to get user name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if ($u) $donor_name = $u['name'];
}
if ($is_anonymous) {
    $donor_name = 'ناشناس';
}

$gateway = new ZarinPalGateway();
$callback_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
              . '://' . $_SERVER['HTTP_HOST']
              . '/petshop/actions/charity_callback.php';

$metadata = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if ($u['email']) $metadata['email'] = $u['email'];
    if ($u['phone']) $metadata['mobile'] = $u['phone'];
}

$description = 'کمک حمایتی به آسنا';
if ($campaign_id) {
    $stmt = $pdo->prepare("SELECT title FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $camp = $stmt->fetch();
    if ($camp) {
        $description .= ' - ' . $camp['title'];
    }
}

$result = $gateway->requestPayment(
    $amount,
    $description,
    $callback_url,
    $metadata
);

if (!$result['success']) {
    $_SESSION['charity_error'] = 'خطا در اتصال به درگاه پرداخت: ' . $result['error'];
    header('Location: charity.php');
    exit;
}

$_SESSION['pending_donation'] = [
    'user_id' => $user_id,
    'donor_name' => $donor_name,
    'campaign_id' => $campaign_id,
    'amount' => $amount,
    'authority' => $result['authority']
];

header('Location: ' . $result['payment_url']);
exit;
