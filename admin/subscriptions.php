<?php
require_once '../includes/db.php';

// Handle status updates will now be in subscription_details.php

$currentPage = 'user_subscriptions';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Fetch user_subscriptions
$stmt = $pdo->query("SELECT o.*, u.name as user_name FROM user_subscriptions o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50");
$user_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Helper to translate status
function translate_status($status) {
    $map = [
        'active' => 'فعال',
        'ended' => 'پایان یافته',
        'cancelled' => 'لغو شده'
    ];
    return $map[$status] ?? $status;
}

// Date Formatter for Jalali
$fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
?>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Header Section -->
    <header class="flex justify-between items-center mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary">مدیریت اشتراک‌ها</h2>
            <p class="text-on-surface-variant font-body-md mt-1">مدیریت دوره‌های ارسال و وضعیت اشتراک کاربران</p>
        </div>
        <div class="flex gap-4">
            <a href="export_user_subscriptions.php" class="flex items-center gap-2 bg-secondary-container text-on-secondary-container px-6 py-2 rounded-lg font-label-lg font-bold shadow-sm hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined">download</span>
                خروجی CSV (اشتراک‌ها)
            </a>
        </div>
    </header>

    <!-- Orders Table -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden border border-outline-variant/30">
        <div class="p-6 border-b border-outline-variant bg-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="font-title-lg text-title-lg text-primary">لیست تمامی اشتراک‌ها</h3>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-lowest border-b border-outline-variant">
                    <tr>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">شناسه اشتراک</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">مشتری</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">مبلغ کل (تومان)</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">وضعیت</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">زمان ارسال بعدی</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">تاریخ خرید</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    <?php if(empty($user_subscriptions)): ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-on-surface-variant">هیچ اشتراکی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach($user_subscriptions as $subscription): 
                            $is_ended = $subscription['status'] === 'ended';
                            $is_cancelled = $subscription['status'] === 'cancelled';
                            $row_class = 'hover:bg-surface-container-low transition-colors group cursor-pointer relative';
                            if ($is_ended || $is_cancelled) {
                                $row_class .= ' opacity-60 pointer-events-none grayscale';
                            }
                        ?>
                        <tr class="<?= $row_class ?>" onclick="window.location='subscription_details.php?id=<?= $subscription['id'] ?>'">
                            <td class="px-6 py-4 font-bold text-primary" dir="ltr">
                                #SUB-<?= $subscription['id'] ?>
                                <?php if($is_cancelled): ?>
                                    <div class="absolute inset-0 flex justify-center items-center pointer-events-none z-10">
                                        <span class="bg-error-container text-error text-xl font-black px-6 py-2 rounded-xl rotate-[-10deg] shadow-lg border-2 border-error/50">لغو شده</span>
                                    </div>
                                <?php elseif($is_ended): ?>
                                    <div class="absolute inset-0 flex justify-center items-center pointer-events-none z-10">
                                        <span class="bg-surface-variant text-on-surface-variant text-xl font-black px-6 py-2 rounded-xl rotate-[-10deg] shadow-lg border-2 border-outline-variant">پایان یافته</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($subscription['user_name']) ?></td>
                            <td class="px-6 py-4 font-bold"><?= number_format($subscription['amount']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold inline-block <?php 
                                    switch($subscription['status']) {
                                        case 'active': echo 'bg-primary-fixed text-on-primary-fixed-variant'; break;
                                        case 'ended': echo 'bg-surface-variant text-on-surface-variant'; break;
                                        case 'cancelled': echo 'bg-error-container text-error'; break;
                                    }
                                ?>">
                                    <?= translate_status($subscription['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-secondary-container persian-number">
                                <?= $subscription['next_delivery_date'] ? $fmt->format(new DateTime($subscription['next_delivery_date'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant text-sm font-body-md persian-number" dir="ltr">
                                <p><?= $fmt->format(new DateTime($subscription['created_at'])) ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
