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
    '3_months' => ['name' => 'اشتراک ۳ ماهه', 'price' => 2500000],
    '6_months' => ['name' => 'اشتراک ۶ ماهه', 'price' => 2100000],
    '12_months' => ['name' => 'اشتراک ۱۲ ماهه', 'price' => 1850000]
];

$plan_id = $_GET['plan'] ?? '';

if (!isset($plans[$plan_id])) {
    header('Location: subscriptions.php');
    exit;
}

$selected_plan = $plans[$plan_id];

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $gateway = new ZarinPalGateway();
    $callback_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                  . '://' . $_SERVER['HTTP_HOST']
                  . '/petshop/actions/complete_payment.php';

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
            'plan_id'      => $plan_id,
            'plan_name'    => $selected_plan['name'],
            'total_amount' => $selected_plan['price'],
            'authority'    => $result['authority'],
            'created_at'   => time(),
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

<main class="py-24 max-w-xl mx-auto px-margin-mobile">
    <div class="bg-white/80 backdrop-blur-xl border border-white/40 rounded-[2rem] p-10 shadow-2xl text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-secondary-container"></div>
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-primary text-4xl">verified</span>
        </div>
        
        <h1 class="text-headline-lg font-bold text-primary mb-2">تایید خرید اشتراک</h1>
        <p class="text-on-surface-variant mb-10">لطفا جزئیات پلن انتخابی خود را پیش از پرداخت بررسی کنید.</p>

        <?php if(isset($error)): ?>
        <div class="bg-error-container text-error px-4 py-3 rounded-xl mb-6 font-bold text-sm">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 text-right mb-8">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-outline-variant/30">
                <span class="text-on-surface-variant font-bold">پلن انتخابی:</span>
                <span class="text-title-lg font-bold text-primary"><?php echo $selected_plan['name']; ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-on-surface-variant font-bold">مبلغ قابل پرداخت:</span>
                <div class="text-left">
                    <span class="text-headline-md font-bold text-secondary-container persian-number"><?php echo number_format($selected_plan['price']); ?></span>
                    <span class="text-xs text-on-surface-variant">تومان</span>
                </div>
            </div>
        </div>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <button type="submit" class="w-full py-4 bg-primary text-white rounded-xl font-bold shadow-lg hover:bg-primary-container hover:shadow-2xl transition-all flex justify-center items-center gap-2">
                <span class="material-symbols-outlined">credit_card</span>
                پرداخت و فعال‌سازی
            </button>
        </form>
        
        <a href="subscriptions.php" class="block mt-6 text-on-surface-variant text-sm font-bold hover:text-primary transition-colors">بازگشت به لیست پلن‌ها</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
