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

$cart_types       = $_SESSION['cart_types'] ?? [];
$cart_frequencies = $_SESSION['cart_frequency'] ?? [];
$checkout_type    = $_GET['type'] ?? 'all'; // 'autoship', 'standard', or 'all'

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
    $p_id            = $prod['id'];
    $item_type       = $cart_types[$p_id] ?? 'standard';
    
    // Filter if specific checkout requested
    if ($checkout_type === 'autoship' && $item_type !== 'autoship') continue;
    if ($checkout_type === 'standard' && $item_type === 'autoship') continue;

    $qty             = (int)($cart_items[$p_id] ?? 0);
    $price           = (int)$prod['price'];
    
    if ($item_type === 'autoship') {
        $auto_pct = !empty($prod['autoship_discount']) ? (int)$prod['autoship_discount'] : 15;
        $effective_price = round($price * (1 - ($auto_pct / 100)));
    } else {
        $effective_price = $prod['discount_price'] ? (int)$prod['discount_price'] : $price;
    }
    
    $total_price    += $price * $qty;
    $total_discount += ($price - $effective_price) * $qty;

    $pending_items[] = [
        'product_id'            => $prod['id'],
        'product_name_snapshot' => $prod['name'],
        'qty'                   => $qty,
        'unit_price'            => $effective_price,
        'is_autoship'           => ($item_type === 'autoship') ? 1 : 0,
        'frequency'             => $cart_frequencies[$p_id] ?? '1_month'
    ];
}

$final_total = $total_price - $total_discount;

if ($final_total <= 0 || empty($pending_items)) {
    header('Location: cart.php');
    exit;
}

$duration_months = (int)($_GET['duration'] ?? 3);
if (!in_array($duration_months, [3, 6, 12])) $duration_months = 3;
$payment_model = ($_GET['model'] ?? 'monthly') === 'upfront' ? 'upfront' : 'monthly';

// If autoship upfront payment, calculate total with 5% extra discount
if ($checkout_type === 'autoship' && $payment_model === 'upfront') {
    $payable_today = round(($final_total * $duration_months) * 0.95);
    $order_desc = "خرید یک‌جا اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم)";
} elseif ($checkout_type === 'autoship') {
    $payable_today = $final_total;
    $order_desc = "پرداخت نوبت ۱ از اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم)";
} else {
    $payable_today = $final_total;
    $order_desc = "خرید از فروشگاه و داروخانه آسنا — " . count($pending_items) . " محصول";
}

// ── Request payment authority from ZarinPal / Mock Gateway ────────────────────
$gateway      = new ZarinPalGateway();
$callback_url = get_app_base_url() . '/actions/complete_payment.php';

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
    $payable_today,
    $order_desc,
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
    'type'            => 'cart',
    'checkout_type'   => $checkout_type,
    'items'           => $pending_items,
    'total_amount'    => $payable_today,
    'per_delivery'    => $final_total,
    'duration_months' => $duration_months,
    'payment_model'   => $payment_model,
    'authority'       => $result['authority'],
    'created_at'      => time(),
];

// Redirect user to ZarinPal payment page
header('Location: ' . $result['payment_url']);
exit;
