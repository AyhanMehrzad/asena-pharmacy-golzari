<?php
$currentPage = 'donations';
require_once 'includes/admin_header.php';

// Fetch Donations
$stmt = $pdo->query("
    SELECT d.*, c.title as campaign_title 
    FROM donations d 
    LEFT JOIN campaigns c ON d.campaign_id = c.id 
    ORDER BY d.created_at DESC
");
$donations = $stmt->fetchAll();

$totalDonations = count($donations);
$successfulDonations = count(array_filter($donations, fn($d) => $d['status'] === 'successful'));
$totalAmount = array_sum(array_column(array_filter($donations, fn($d) => $d['status'] === 'successful'), 'amount'));

?>

<!-- Main Dashboard Canvas -->
<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Page Header & Quick Action -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-1">گزارش کمک‌های مردمی</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">مشاهده و طبقه‌بندی پرداخت‌های حمایتی</p>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between">
            <div>
                <p class="text-label-sm text-on-surface-variant mb-1">کل تراکنش‌ها</p>
                <p class="text-display-lg font-bold text-primary"><?= $totalDonations ?></p>
            </div>
            <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[32px]">receipt_long</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between">
            <div>
                <p class="text-label-sm text-status-active font-bold mb-1">تراکنش‌های موفق</p>
                <p class="text-display-lg font-bold text-status-active"><?= $successfulDonations ?></p>
            </div>
            <div class="w-12 h-12 bg-status-active/10 rounded-lg flex items-center justify-center text-status-active">
                <span class="material-symbols-outlined text-[32px]">check_circle</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between group hover:border-secondary transition-colors">
            <div>
                <p class="text-label-sm text-secondary font-bold mb-1">مجموع کمک‌های دریافتی</p>
                <p class="text-display-sm font-bold text-secondary"><?= number_format($totalAmount) ?> تومان</p>
            </div>
            <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[32px]">savings</span>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-low border-b border-outline-variant/50">
                    <tr>
                        <th class="px-6 py-4 text-sm font-bold text-primary">کد پیگیری / آیدی</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">نام نیکوکار</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">کمپین / مصرف</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">مبلغ (تومان)</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">وضعیت</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">تاریخ پرداخت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($donations as $don): ?>
                    <tr class="hover:bg-surface-container-lowest transition-colors group">
                        <td class="px-6 py-4 font-mono text-sm text-on-surface-variant">
                            <?= htmlspecialchars($don['payment_reference'] ?: '#'.$don['id']) ?>
                        </td>
                        <td class="px-6 py-4 font-bold text-primary">
                            <?= htmlspecialchars($don['donor_name'] ?: 'ناشناس') ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full bg-secondary-container/10 text-secondary-container text-[11px] font-bold">
                                <?= htmlspecialchars($don['campaign_title'] ?: 'کمک عمومی') ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-primary">
                            <?= number_format($don['amount']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($don['status'] === 'successful'): ?>
                                <span class="text-status-active text-sm font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">done</span> موفق</span>
                            <?php elseif ($don['status'] === 'pending'): ?>
                                <span class="text-status-warning text-sm font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> در انتظار</span>
                            <?php else: ?>
                                <span class="text-error text-sm font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">close</span> ناموفق</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-on-surface-variant" dir="ltr">
                            <?= substr($don['created_at'], 0, 16) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
