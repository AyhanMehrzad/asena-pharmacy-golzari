<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$subscription_id = (int)($_GET['id'] ?? 0);
if (!$subscription_id) {
    header("Location: subscriptions.php");
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'mark_shipped' && isset($_POST['delivery_id'])) {
            $delivery_id = (int)$_POST['delivery_id'];
            $stmt = $pdo->prepare("UPDATE subscription_deliveries SET status = 'shipped' WHERE id = ? AND subscription_id = ?");
            $stmt->execute([$delivery_id, $subscription_id]);
            $success = "وضعیت مرسوله به «در مسیر ارسال» تغییر یافت.";
            
            // Send SMS to user
            require_once '../includes/SmsService.php';
            $u_stmt = $pdo->prepare("SELECT u.phone FROM users u JOIN user_subscriptions s ON u.id = s.user_id WHERE s.id = ?");
            $u_stmt->execute([$subscription_id]);
            $u = $u_stmt->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['phone'])) {
                $sms = new SmsService();
                $sms->sendSubscriptionSent($u['phone']);
            }
        }
        
        if ($action === 'cancel_subscription') {
            $stmt = $pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$subscription_id]);
            $success = "اشتراک با موفقیت لغو شد.";
        }
        
        if ($action === 'end_subscription') {
            $stmt = $pdo->prepare("UPDATE user_subscriptions SET status = 'ended' WHERE id = ?");
            $stmt->execute([$subscription_id]);
            $success = "اشتراک پایان یافت.";
        }
    }
}

// Fetch subscription details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name, u.phone, u.email, u.city, u.postal_code, u.address 
    FROM user_subscriptions s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$subscription_id]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subscription) {
    header("Location: subscriptions.php");
    exit;
}

// Fetch deliveries
$stmt = $pdo->prepare("SELECT * FROM subscription_deliveries WHERE subscription_id = ? ORDER BY delivery_month ASC");
$stmt->execute([$subscription_id]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'user_subscriptions';
require_once 'includes/admin_header.php';

$fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
$dateOnlyFmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');

function translate_delivery_status($status) {
    $map = [
        'pending' => 'در انتظار',
        'shipped' => 'ارسال شده',
        'delivered' => 'دریافت شده',
        'not_received' => 'دریافت نشده (گزارش کاربر)'
    ];
    return $map[$status] ?? $status;
}
?>

<div class="p-8 max-w-[1200px] mx-auto">
    <!-- Header -->
    <header class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-4">
            <a href="subscriptions.php" class="bg-surface-container w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-variant transition-colors">
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary persian-number">جزئیات اشتراک #SUB-<?= $subscription['id'] ?></h2>
                <p class="text-on-surface-variant font-body-md mt-1">مشاهده اطلاعات گیرنده و دوره‌های ارسال</p>
            </div>
        </div>
        
        <?php if ($subscription['status'] === 'active'): ?>
        <div class="flex gap-4">
            <form method="POST" class="m-0" onsubmit="return confirm('آیا از پایان دادن به این اشتراک اطمینان دارید؟');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="end_subscription">
                <button type="submit" class="bg-surface-variant text-on-surface px-6 py-2 rounded-lg font-label-lg font-bold hover:bg-outline-variant/30 transition-colors">
                    پایان اشتراک
                </button>
            </form>
            <form method="POST" class="m-0" onsubmit="return confirm('آیا از لغو کردن این اشتراک اطمینان دارید؟');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel_subscription">
                <button type="submit" class="bg-error text-on-error px-6 py-2 rounded-lg font-label-lg font-bold shadow-md shadow-error/20 hover:opacity-90 transition-opacity">
                    لغو اشتراک
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="px-6 py-2 rounded-xl font-black text-xl <?= $subscription['status'] === 'cancelled' ? 'bg-error-container text-error border-2 border-error/30' : 'bg-surface-variant text-on-surface-variant border-2 border-outline-variant' ?>">
            <?= $subscription['status'] === 'cancelled' ? 'اشتراک لغو شده است' : 'اشتراک پایان یافته است' ?>
        </div>
        <?php endif; ?>
    </header>

    <?php if(isset($success)): ?>
    <div class="bg-status-active/20 text-status-active px-4 py-3 rounded-xl mb-6 font-bold text-sm">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Details -->
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-outline-variant/30 p-6">
            <h3 class="font-title-lg text-title-lg text-primary mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined">person</span> اطلاعات مشتری
            </h3>
            
            <div class="space-y-4 text-body-md">
                <div>
                    <p class="text-on-surface-variant text-sm">نام گیرنده</p>
                    <p class="font-bold"><?= htmlspecialchars($subscription['user_name']) ?></p>
                </div>
                <div>
                    <p class="text-on-surface-variant text-sm">شماره تماس</p>
                    <p class="font-bold persian-number" dir="ltr"><?= htmlspecialchars($subscription['phone']) ?></p>
                </div>
                <div>
                    <p class="text-on-surface-variant text-sm">ایمیل</p>
                    <p class="font-bold" dir="ltr"><?= htmlspecialchars($subscription['email']) ?: '-' ?></p>
                </div>
                <div class="pt-4 border-t border-outline-variant/30">
                    <p class="text-on-surface-variant text-sm">شهر و کد پستی</p>
                    <p class="font-bold persian-number"><?= htmlspecialchars($subscription['city']) ?> <?= $subscription['postal_code'] ? ' - ' . htmlspecialchars($subscription['postal_code']) : '' ?></p>
                </div>
                <div>
                    <p class="text-on-surface-variant text-sm">آدرس پستی</p>
                    <p class="font-bold persian-number leading-relaxed"><?= nl2br(htmlspecialchars($subscription['address'])) ?: 'آدرسی ثبت نشده است.' ?></p>
                </div>
            </div>
        </div>

        <!-- Deliveries Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-outline-variant/30 p-6">
            <h3 class="font-title-lg text-title-lg text-primary mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined">local_shipping</span> زمان‌بندی ارسال مرسولات
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead class="bg-surface-container-lowest border-b border-outline-variant">
                        <tr>
                            <th class="px-4 py-3 font-label-lg text-on-surface-variant">ماه</th>
                            <th class="px-4 py-3 font-label-lg text-on-surface-variant">تاریخ مقرر ارسال</th>
                            <th class="px-4 py-3 font-label-lg text-on-surface-variant">وضعیت فعلی</th>
                            <th class="px-4 py-3 font-label-lg text-on-surface-variant">عملیات مدیریت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <?php foreach($deliveries as $del): ?>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-4 font-bold persian-number">ماه <?= $del['delivery_month'] ?></td>
                            <td class="px-4 py-4 font-bold text-secondary-container persian-number">
                                <?= $del['scheduled_date'] ? $dateOnlyFmt->format(new DateTime($del['scheduled_date'])) : '-' ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold inline-block <?php 
                                    switch($del['status']) {
                                        case 'pending': echo 'bg-status-warning/20 text-status-warning'; break;
                                        case 'shipped': echo 'bg-primary-fixed text-on-primary-fixed-variant'; break;
                                        case 'delivered': echo 'bg-status-active/20 text-status-active'; break;
                                        case 'not_received': echo 'bg-error-container text-error'; break;
                                    }
                                ?>">
                                    <?= translate_delivery_status($del['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($del['status'] === 'pending' && $subscription['status'] === 'active'): ?>
                                <form method="POST" class="m-0" onsubmit="return confirm('آیا از ارسال مرسوله ماه <?= $del['delivery_month'] ?> اطمینان دارید؟ این عمل به کاربر اطلاع می‌دهد.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="mark_shipped">
                                    <input type="hidden" name="delivery_id" value="<?= $del['id'] ?>">
                                    <button type="submit" class="bg-primary text-white text-xs font-bold px-4 py-1.5 rounded-lg shadow-sm hover:opacity-90 transition-opacity">
                                        ثبت ارسال
                                    </button>
                                </form>
                                <?php elseif ($del['status'] === 'shipped'): ?>
                                    <span class="text-xs text-on-surface-variant">منتظر تایید کاربر</span>
                                <?php elseif ($del['status'] === 'delivered'): ?>
                                    <span class="text-xs text-status-active font-bold">توسط کاربر دریافت شد</span>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-variant">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($deliveries)): ?>
                            <tr><td colspan="4" class="px-4 py-4 text-center text-on-surface-variant">دوره‌ای یافت نشد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
