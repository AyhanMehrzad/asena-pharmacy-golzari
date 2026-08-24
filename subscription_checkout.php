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
    $_SESSION['profile_error'] = "لطفاً پیش از خرید اشتراک، آدرس منزل و شهر خود را در پروفایل تکمیل کنید تا امکان ارسال مرسولات فراهم باشد.";
    header('Location: profile.php');
    exit;
}

$plans = [
    '3_months' => ['name' => 'اشتراک ۳ ماهه پایه', 'price' => 2500000, 'months' => 3],
    '6_months' => ['name' => 'اشتراک ۶ ماهه ویژه', 'price' => 2100000, 'months' => 6],
    '12_months' => ['name' => 'اشتراک ۱۲ ماهه VIP', 'price' => 1850000, 'months' => 12]
];

$plan_id = $_GET['plan'] ?? $_POST['plan'] ?? '';
$is_custom = ($plan_id === 'custom');
$custom_items_raw = $_POST['custom_items'] ?? $_GET['custom_items'] ?? '';
$custom_products = [];
$custom_months = (int)($_POST['interval_months'] ?? $_GET['interval_months'] ?? 3);
if ($custom_months < 1) $custom_months = 3;

$selected_plan = null;

if ($is_custom && !empty($custom_items_raw)) {
    $decoded = json_decode($custom_items_raw, true);
    if (!empty($decoded) && is_array($decoded)) {
        $p_ids = array_map('intval', array_keys($decoded));
        if (!empty($p_ids)) {
            $placeholders = implode(',', array_fill(0, count($p_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($p_ids);
            $db_prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $custom_subtotal = 0;
            $custom_discount = 0;
            
            foreach ($db_prods as $p) {
                $qty = (int)($decoded[$p['id']] ?? 1);
                if ($qty < 1) $qty = 1;
                $p['qty'] = $qty;
                
                // 15% discount for custom subscriber box
                $unit_discounted = round($p['price'] * 0.85);
                $p['unit_price'] = $unit_discounted;
                
                $custom_subtotal += $p['price'] * $qty;
                $custom_discount += ($p['price'] - $unit_discounted) * $qty;
                
                $custom_products[] = $p;
            }
            
            $custom_final_price = $custom_subtotal - $custom_discount;
            if ($custom_final_price > 0) {
                $selected_plan = [
                    'name' => 'باکس اشتراک اختصاصی (' . count($custom_products) . ' قلم)',
                    'price' => $custom_final_price,
                    'months' => $custom_months,
                    'items' => $custom_products,
                    'subtotal' => $custom_subtotal,
                    'discount' => $custom_discount
                ];
            }
        }
    }
}

if (!$selected_plan && isset($plans[$plan_id])) {
    $selected_plan = $plans[$plan_id];
}

if (!$selected_plan) {
    header('Location: subscriptions.php');
    exit;
}

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $gateway = new ZarinPalGateway();
    $callback_url = get_app_base_url() . '/actions/complete_payment.php';

    // Fetch user details for ZarinPal metadata
    $user = $currentUser;

    $metadata = [];
    if (!empty($user['email'])) $metadata['email'] = $user['email'];
    if (!empty($user['phone'])) $metadata['mobile'] = (string)$user['phone'];

    $result = $gateway->requestPayment(
        $selected_plan['price'],
        'خرید ' . $selected_plan['name'] . ' آسنا',
        $callback_url,
        $metadata
    );

    if (!$result['success']) {
        $error = 'خطا در اتصال به درگاه پرداخت: ' . $result['error'];
    } else {
        $_SESSION['pending_order'] = [
            'type'         => 'subscription',
            'plan_id'      => $selected_plan['months'] ?? 3,
            'plan_name'    => $selected_plan['name'],
            'total_amount' => $selected_plan['price'],
            'authority'    => $result['authority'],
            'created_at'   => time(),
            'is_custom'    => $is_custom ? 1 : 0,
            'items'        => $custom_products
        ];
        header('Location: ' . $result['payment_url']);
        exit;
    }
}

$current_page = 'subscriptions';
include 'includes/header.php';
?>

<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-primary-container/10 blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-secondary-container/10 blur-[150px]"></div>
</div>

<main class="py-16 lg:py-24 max-w-xl mx-auto px-margin-mobile">
    <div class="bg-white/95 backdrop-blur-xl border border-outline-variant/30 rounded-[2.5rem] p-8 lg:p-10 shadow-2xl text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-secondary-container to-amber-500"></div>
        <div class="w-16 h-16 bg-secondary-container/15 text-secondary-container rounded-2xl flex items-center justify-center mx-auto mb-4 border border-secondary-container/30">
            <span class="material-symbols-outlined text-3xl">verified</span>
        </div>
        
        <h1 class="text-2xl font-bold text-primary mb-2">تایید و فعال‌سازی اشتراک</h1>
        <p class="text-xs text-on-surface-variant mb-6">لطفا جزئیات پکیج اشتراکی خود را پیش از اتصال به درگاه بانکی بررسی کنید.</p>

        <?php if(isset($error)): ?>
        <div class="bg-error/10 text-error px-4 py-3 rounded-2xl mb-6 font-bold text-xs border border-error/20">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Invoice Details Box -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-5 text-right mb-6 space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-outline-variant/30 text-xs">
                <span class="text-on-surface-variant font-bold">نوع برنامه:</span>
                <span class="text-primary font-bold bg-primary/10 px-3 py-1 rounded-full"><?php echo htmlspecialchars($selected_plan['name']); ?></span>
            </div>

            <!-- If custom items selected, list preview -->
            <?php if(!empty($custom_products)): ?>
                <div class="space-y-2 pb-3 border-b border-outline-variant/30">
                    <span class="text-[11px] font-bold text-on-surface-variant block mb-1">اقلام انتخابی پکیج شما:</span>
                    <?php foreach($custom_products as $cp): ?>
                        <div class="flex items-center justify-between text-xs bg-surface-container-low p-2 rounded-xl">
                            <span class="font-bold text-on-surface truncate"><?= htmlspecialchars($cp['name']) ?> (<?= $cp['qty'] ?> عدد)</span>
                            <span class="font-mono text-primary font-bold shrink-0"><?= number_format($cp['unit_price'] * $cp['qty']) ?> ت</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-center text-xs">
                <span class="text-on-surface-variant">تخفیف ویژه اشتراک:</span>
                <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md">۱۵٪ تخفیف ثابت + ارسال رایگان</span>
            </div>

            <div class="flex justify-between items-center text-xs">
                <span class="text-on-surface-variant">آدرس تحویل دوره‌ای:</span>
                <span class="text-on-surface font-bold truncate max-w-[220px]"><?= htmlspecialchars($currentUser['city'] . '، ' . $currentUser['address']) ?></span>
            </div>

            <div class="flex justify-between items-center pt-3 border-t border-outline-variant/30">
                <span class="text-sm font-bold text-primary">مبلغ هر نوبت ارسال:</span>
                <span class="text-xl font-bold text-secondary-container font-mono">
                    <?= number_format($selected_plan['price']) ?> <span class="text-xs text-on-surface-variant font-normal">تومان</span>
                </span>
            </div>
        </div>

        <!-- Checkout Form -->
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan_id); ?>">
            <?php if($is_custom): ?>
                <input type="hidden" name="custom_items" value="<?php echo htmlspecialchars($custom_items_raw); ?>">
                <input type="hidden" name="interval_months" value="<?php echo $custom_months; ?>">
            <?php endif; ?>
            <button type="submit" class="w-full bg-secondary-container hover:bg-[#ea580c] text-white py-4 rounded-2xl font-bold shadow-xl transition-all flex items-center justify-center gap-2 active:scale-95 text-sm">
                <span class="material-symbols-outlined text-lg">payment</span>
                <span>پرداخت و شروع اشتراک</span>
            </button>
        </form>

        <a href="subscriptions.php" class="inline-block mt-4 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            ← بازگشت و تغییر پکیج
        </a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
