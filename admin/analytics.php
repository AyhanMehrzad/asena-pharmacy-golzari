<?php
$currentPage = 'analytics';
require_once 'includes/admin_header.php';

// Fetch Revenue
$stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders");
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Fetch Appointments Count
$stmt = $pdo->query("SELECT COUNT(*) as total_appts FROM appointments");
$appts = $stmt->fetch(PDO::FETCH_ASSOC)['total_appts'] ?? 0;

// Fetch Users Count
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'] ?? 0;

// Calculate Return Rate (Users with > 1 order)
$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as returning_users FROM orders WHERE user_id IN (SELECT user_id FROM orders GROUP BY user_id HAVING COUNT(*) > 1)");
$returning_users = $stmt->fetch(PDO::FETCH_ASSOC)['returning_users'] ?? 0;
$return_rate = $users > 0 ? round(($returning_users / $users) * 100, 1) : 0;

// Fetch Pet Distribution
$stmt = $pdo->query("SELECT type, COUNT(*) as count FROM user_pets GROUP BY type");
$pet_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pets = 0;
foreach ($pet_distribution as $pet) {
    $total_pets += $pet['count'];
}

// Monthly revenue for current year (real data)
$stmt = $pdo->query("
    SELECT MONTH(created_at) as month_num, SUM(total_amount) as revenue
    FROM orders
    WHERE YEAR(created_at) = YEAR(NOW()) AND status NOT IN ('cancelled', 'pending_payment')
    GROUP BY MONTH(created_at)
    ORDER BY month_num ASC
");
$monthly_revenue_raw = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $monthly_revenue_raw[(int)$row['month_num']] = (int)$row['revenue'];
}
$months_fa  = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',
               7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];
$max_rev    = max(array_merge([1], array_values($monthly_revenue_raw)));

$colors = ['bg-primary', 'bg-secondary-container', 'bg-tertiary-fixed', 'bg-secondary'];
?>

<!-- Analytics Dashboard Content -->
<div class="p-8 space-y-8 max-w-7xl mx-auto">
    <!-- Header Actions -->
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary">تحلیل و گزارشات جامع</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">آخرین بروزرسانی: همین لحظه</p>
        </div>
        <div class="flex gap-3">
            <button class="flex items-center gap-2 bg-white border border-outline-variant px-4 py-2 rounded-lg font-label-lg text-label-lg text-primary hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                همه زمان‌ها
            </button>
            <button onclick="window.print()" class="flex items-center gap-2 bg-secondary-container text-white px-4 py-2 rounded-lg font-label-lg text-label-lg hover:opacity-90 active:scale-95 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[20px]">file_download</span>
                چاپ گزارش
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bento-card p-6 bg-white rounded-xl border border-outline-variant shadow-sm flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="p-2 bg-primary-fixed rounded-lg text-primary">
                    <span class="material-symbols-outlined">payments</span>
                </span>
            </div>
            <p class="font-label-lg text-label-lg text-on-surface-variant mt-2">درآمد کل</p>
            <h3 class="font-headline-md text-headline-md text-primary" dir="ltr"><?= number_format($revenue) ?> <span class="text-sm">تومان</span></h3>
        </div>

        <div class="bento-card p-6 bg-white rounded-xl border border-outline-variant shadow-sm flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="p-2 bg-secondary-fixed rounded-lg text-secondary">
                    <span class="material-symbols-outlined">event_available</span>
                </span>
            </div>
            <p class="font-label-lg text-label-lg text-on-surface-variant mt-2">کل نوبت‌های ثبت شده</p>
            <h3 class="font-headline-md text-headline-md text-primary"><?= number_format($appts) ?></h3>
        </div>

        <div class="bento-card p-6 bg-white rounded-xl border border-outline-variant shadow-sm flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="p-2 bg-tertiary-fixed rounded-lg text-tertiary">
                    <span class="material-symbols-outlined">person_add</span>
                </span>
            </div>
            <p class="font-label-lg text-label-lg text-on-surface-variant mt-2">مشتریان پلتفرم</p>
            <h3 class="font-headline-md text-headline-md text-primary"><?= number_format($users) ?></h3>
        </div>

        <div class="bento-card p-6 bg-white rounded-xl border border-outline-variant shadow-sm flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="p-2 bg-outline-variant rounded-lg text-on-surface-variant">
                    <span class="material-symbols-outlined">loyalty</span>
                </span>
                <?php if($return_rate > 0): ?>
                <span class="text-status-active font-label-sm text-label-sm bg-green-50 px-2 py-1 rounded-full flex items-center gap-1">
                    <?= $return_rate ?>٪ <span class="material-symbols-outlined text-[14px]">check_circle</span>
                </span>
                <?php endif; ?>
            </div>
            <p class="font-label-lg text-label-lg text-on-surface-variant mt-2">نرخ بازگشت مشتری (تکرار خرید)</p>
            <h3 class="font-headline-md text-headline-md text-primary"><?= $return_rate ?>٪</h3>
        </div>
    </div>

    <!-- Main Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Segments -->
        <div class="lg:col-span-1 bento-card bg-white p-8 rounded-xl border border-outline-variant shadow-sm flex flex-col h-full">
            <h4 class="font-title-lg text-title-lg text-primary mb-2">توزیع حیوانات خانگی</h4>
            <p class="font-body-md text-body-md text-on-surface-variant mb-8">بر اساس نوع حیوانات ثبت شده</p>
            <div class="relative flex-1 flex items-center justify-center min-h-[150px]">
                <div class="w-40 h-40 rounded-full border-[12px] border-primary border-t-secondary-container border-l-tertiary-fixed border-b-secondary relative">
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="font-headline-md text-headline-md text-primary"><?= number_format($total_pets) ?></span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">کل حیوانات</span>
                    </div>
                </div>
            </div>
            <div class="space-y-3 mt-8">
                <?php foreach($pet_distribution as $index => $pet): 
                    $percent = $total_pets > 0 ? round(($pet['count'] / $total_pets) * 100) : 0;
                    $color = $colors[$index % count($colors)];
                ?>
                <div class="flex justify-between items-center text-body-md">
                    <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full <?= $color ?>"></span> <?= htmlspecialchars($pet['type']) ?></span>
                    <span class="font-bold"><?= $percent ?>٪</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Real Revenue Chart -->
        <div class="lg:col-span-2 bento-card bg-white p-8 rounded-xl border border-outline-variant shadow-sm">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h4 class="font-title-lg text-title-lg text-primary">درآمد ماهانه (سال جاری)</h4>
                    <p class="font-body-md text-body-md text-on-surface-variant">بر اساس سفارش‌های تکمیل‌شده</p>
                </div>
                <span class="text-label-sm text-on-surface-variant bg-surface-container px-3 py-1 rounded-full">
                    <?= number_format(array_sum($monthly_revenue_raw)) ?> تومان کل
                </span>
            </div>
            <div class="h-64 flex items-end justify-between gap-2 px-2 border-b border-outline-variant pb-2">
                <?php for ($m = 1; $m <= 12; $m++):
                    $rev    = $monthly_revenue_raw[$m] ?? 0;
                    $pct    = $max_rev > 0 ? max(4, round(($rev / $max_rev) * 100)) : 4;
                    $active = $rev > 0;
                ?>
                <div class="flex-1 flex flex-col items-center gap-2 group relative">
                    <!-- Tooltip -->
                    <?php if ($active): ?>
                    <div class="absolute bottom-full mb-2 hidden group-hover:block bg-on-surface text-surface text-[10px] px-2 py-1 rounded whitespace-nowrap z-10">
                        <?= number_format($rev) ?> تومان
                    </div>
                    <?php endif; ?>
                    <div class="w-full flex items-end justify-center h-full">
                        <div class="w-4 <?= $active ? 'bg-primary' : 'bg-outline-variant/30' ?> rounded-t-sm transition-all duration-500"
                             style="height: <?= $pct ?>%"></div>
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant text-[9px] text-center"><?= $months_fa[$m] ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
