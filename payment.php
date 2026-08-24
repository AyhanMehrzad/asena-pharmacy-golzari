<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/gateway.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userStmt = $pdo->prepare("SELECT email, phone, city, address FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (empty(trim((string)$currentUser['city'])) || empty(trim((string)$currentUser['address']))) {
    $_SESSION['profile_error'] = "لطفاً پیش از خرید، آدرس منزل و شهر خود را در پروفایل تکمیل کنید تا امکان ارسال مرسولات فراهم باشد.";
    header('Location: profile.php');
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// ── Calculate real totals from DB ─────────────────────────────────────────────
$ids          = array_keys($cart_items);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt         = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$db_products  = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price    = 0;
$total_discount = 0;
$pending_items  = [];

foreach ($db_products as $prod) {
    $qty             = (int)($cart_items[$prod['id']] ?? 0);
    $price           = (int)$prod['price'];
    $effective_price = $prod['discount_price'] ? (int)$prod['discount_price'] : $price;
    $total_price    += $price * $qty;
    $total_discount += ($price - $effective_price) * $qty;

    $pending_items[] = [
        'product_id'            => $prod['id'],
        'product_name_snapshot' => $prod['name'],
        'qty'                   => $qty,
        'unit_price'            => $effective_price,
    ];
}

$final_total = $total_price - $total_discount;

if ($final_total <= 0 || empty($pending_items)) {
    header('Location: cart.php');
    exit;
}

// ── Request payment authority from ZarinPal ───────────────────────────────────
$gateway      = new ZarinPalGateway();
$callback_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
              . '://' . $_SERVER['HTTP_HOST']
              . '/petshop/actions/complete_payment.php';

// Fetch user details for ZarinPal metadata
$user = $currentUser;

$metadata = [];
if (!empty($user['email'])) {
    $metadata['email'] = $user['email'];
}
if (!empty($user['phone'])) {
    $metadata['mobile'] = (string)$user['phone'];
}

$result = $gateway->requestPayment(
    $final_total,
    'خرید از فروشگاه پت‌شاپ — ' . count($pending_items) . ' محصول',
    $callback_url,
    $metadata
);

if (!$result['success']) {
    $_SESSION['profile_error'] = 'خطا در اتصال به درگاه پرداخت: ' . $result['error'];
    header('Location: cart.php');
    exit;
}

// ── Store pending order snapshot — authority ties everything together ──────────
$_SESSION['pending_order'] = [
    'type'         => 'cart',
    'items'        => $pending_items,
    'total_amount' => $final_total,
    'authority'    => $result['authority'],
    'created_at'   => time(),
];

// Redirect user to ZarinPal payment page
header('Location: ' . $result['payment_url']);
exit;
