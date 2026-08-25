<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$currentPage = 'user_subscriptions';

// Handle Quick Dispatch Action
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'quick_dispatch') {
        $sub_id = (int)$_POST['subscription_id'];
        $advance_days = (int)($_POST['advance_days'] ?? 30);
        $tracking_note = trim($_POST['tracking_note'] ?? 'تحویل به پیک / شرکت پست');

        if ($sub_id > 0) {
            $subStmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE id = ?");
            $subStmt->execute([$sub_id]);
            $sub = $subStmt->fetch(PDO::FETCH_ASSOC);

            if ($sub && $sub['status'] === 'active') {
                $current_next = $sub['next_delivery_date'] ?: date('Y-m-d');
                $diff_days = round((strtotime($current_next) - strtotime(date('Y-m-d'))) / 86400);

                if ($diff_days > 2) {
                    $flash_error = "ثبت ارسال غیرفعال است. ارسال مرسوله فقط از ۲ روز مانده به موعد تحویل فعال می‌شود (موعد ارسال: $current_next - $diff_days روز باقی مانده).";
                } else {
                    $new_next = date('Y-m-d', strtotime("+$advance_days days", strtotime($current_next)));

                    // 1. Record delivery in subscription_deliveries
                    try {
                        $countDelStmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_deliveries WHERE subscription_id = ?");
                        $countDelStmt->execute([$sub_id]);
                        $delivery_month = (int)$countDelStmt->fetchColumn() + 1;

                        $insDel = $pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, ?, ?, 'shipped')");
                        $insDel->execute([$sub_id, $delivery_month, $current_next]);
                    } catch (Exception $e) {}

                    // 2. Update next delivery date
                    $updSub = $pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?");
                    $updSub->execute([$new_next, $sub_id]);

                    // 3. Log action
                    try {
                        $insLog = $pdo->prepare("INSERT INTO subscription_logs (subscription_id, action, note) VALUES (?, 'dispatch', ?)");
                        $insLog->execute([$sub_id, "مرسوله ارسال شد. نوبت بعدی برای $new_next تنظیم شد. ($tracking_note)"]);
                    } catch (Exception $e) {}

                    $flash_success = "ارسال مرسوله اشتراک #SUB-$sub_id با موفقیت ثبت شد و نوبت بعدی برای $new_next زمان‌بندی گردید.";
                }
            } else {
                $flash_error = "اشتراک مورد نظر یافت نشد یا در وضعیت فعال نیست.";
            }
        }
    } elseif ($action === 'resolve_incident') {
        $sub_id = (int)$_POST['subscription_id'];
        $resolution_type = $_POST['resolution_type'] ?? 'delivered';
        $admin_note = trim($_POST['admin_note'] ?? 'مشکل با هماهنگی مشتری حل شد');

        if ($sub_id > 0) {
            if ($resolution_type === 'resend') {
                $upd = $pdo->prepare("UPDATE subscription_deliveries SET status = 'shipped' WHERE subscription_id = ? AND status = 'not_received'");
                $upd->execute([$sub_id]);
                $logMsg = "بسته جایگزین مجدداً ارسال شد: $admin_note";
            } else {
                $upd = $pdo->prepare("UPDATE subscription_deliveries SET status = 'delivered' WHERE subscription_id = ? AND status = 'not_received'");
                $upd->execute([$sub_id]);
                $logMsg = "تحویل بسته به مشتری تایید و گزارش عدم دریافت رفع گردید: $admin_note";
            }

            try {
                $insLog = $pdo->prepare("INSERT INTO subscription_logs (subscription_id, action, note) VALUES (?, 'incident_resolved', ?)");
                $insLog->execute([$sub_id, $logMsg]);
            } catch (Exception $e) {}

            $flash_success = "گزارش عدم دریافت اشتراک #SUB-$sub_id با موفقیت حل و فصل و از وضعیت هشدار خارج شد.";
        }
    }
}

require_once 'includes/admin_header.php';

// Date Formatters (Jalali Persian)
$fmtDateTime = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
$fmtDateOnly = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
$fmtDayName  = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'EEEE');
$fmtDayMonth = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM');

// Filter & Search Parameters
$filter = $_GET['filter'] ?? 'all';
$custom_date = $_GET['d'] ?? '';
$search = trim($_GET['search'] ?? '');

$todayStr = date('Y-m-d');
$tomorrowStr = date('Y-m-d', strtotime('+1 day'));
$weekEndStr = date('Y-m-d', strtotime('+7 days'));

// Accurate Metrics
$todayCountStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subscriptions WHERE DATE(next_delivery_date) = ? AND status = 'active'");
$todayCountStmt->execute([$todayStr]);
$todayCount = (int)$todayCountStmt->fetchColumn();

$tomorrowCountStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subscriptions WHERE DATE(next_delivery_date) = ? AND status = 'active'");
$tomorrowCountStmt->execute([$tomorrowStr]);
$tomorrowCount = (int)$tomorrowCountStmt->fetchColumn();

// Specific Issues Metrics: User Not-Received reports + Overdue Dispatches
$notReceivedCount = (int)$pdo->query("SELECT COUNT(DISTINCT subscription_id) FROM subscription_deliveries WHERE status = 'not_received'")->fetchColumn();

$overdueStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subscriptions WHERE DATE(next_delivery_date) < ? AND status = 'active'");
$overdueStmt->execute([$todayStr]);
$overdueDispatchCount = (int)$overdueStmt->fetchColumn();

$issuesStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) 
    FROM user_subscriptions o 
    LEFT JOIN subscription_deliveries d ON d.subscription_id = o.id 
    WHERE (d.status = 'not_received') 
       OR (o.status = 'active' AND DATE(o.next_delivery_date) < ?)
");
$issuesStmt->execute([$todayStr]);
$totalIssuesCount = (int)$issuesStmt->fetchColumn();

$weekCountStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subscriptions WHERE DATE(next_delivery_date) BETWEEN ? AND ? AND status = 'active'");
$weekCountStmt->execute([$todayStr, $weekEndStr]);
$weekCount = (int)$weekCountStmt->fetchColumn();

$allActiveCount = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active'")->fetchColumn();

// 7-Day Rolling Calendar Strip Data
$rollingDays = [];
for ($i = 0; $i < 7; $i++) {
    $dateObj = new DateTime("+$i days");
    $rawDate = $dateObj->format('Y-m-d');
    $timestamp = $dateObj->getTimestamp();

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subscriptions WHERE DATE(next_delivery_date) = ? AND status = 'active'");
    $cntStmt->execute([$rawDate]);
    $count = (int)$cntStmt->fetchColumn();

    $label = ($i === 0) ? 'امروز' : (($i === 1) ? 'فردا' : $fmtDayName->format($timestamp));

    $rollingDays[] = [
        'raw_date' => $rawDate,
        'label' => $label,
        'persian_date' => $fmtDayMonth->format($timestamp),
        'count' => $count,
        'is_today' => ($i === 0),
        'is_tomorrow' => ($i === 1)
    ];
}

// Build SQL Query based on active filter
$whereClauses = [];
$params = [];

if ($filter === 'today') {
    $whereClauses[] = "DATE(o.next_delivery_date) = ?";
    $params[] = $todayStr;
} elseif ($filter === 'tomorrow') {
    $whereClauses[] = "DATE(o.next_delivery_date) = ?";
    $params[] = $tomorrowStr;
} elseif ($filter === 'issues' || $filter === 'overdue') {
    $whereClauses[] = "(
        (o.status = 'active' AND DATE(o.next_delivery_date) < '$todayStr') 
        OR 
        ((SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id AND d.status = 'not_received') > 0)
    )";
} elseif ($filter === 'week') {
    $whereClauses[] = "DATE(o.next_delivery_date) BETWEEN ? AND ?";
    $params[] = $todayStr;
    $params[] = $weekEndStr;
} elseif ($filter === 'date' && !empty($custom_date)) {
    $whereClauses[] = "DATE(o.next_delivery_date) = ?";
    $params[] = $custom_date;
}

if (!empty($search)) {
    $whereClauses[] = "(u.name LIKE ? OR u.phone LIKE ? OR o.plan_name LIKE ? OR o.id = ?)";
    $sParam = "%$search%";
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = (int)str_replace('#SUB-', '', $search);
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT o.*, u.name as user_name, u.phone as user_phone, u.address as user_address,
           (SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id AND d.status = 'not_received') as not_received_count,
           (SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id AND d.status = 'shipped') as shipped_count,
           (SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id AND d.status = 'delivered') as delivered_count,
           (SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id) as total_deliveries_count,
           (SELECT MIN(scheduled_date) FROM subscription_deliveries d WHERE d.subscription_id = o.id) as first_delivery_date,
           (SELECT MAX(scheduled_date) FROM subscription_deliveries d WHERE d.subscription_id = o.id) as last_delivery_date
    FROM user_subscriptions o 
    JOIN users u ON o.user_id = u.id 
    $whereSql 
    ORDER BY 
        CASE 
            WHEN (SELECT COUNT(*) FROM subscription_deliveries d WHERE d.subscription_id = o.id AND d.status = 'not_received') > 0 THEN 1
            WHEN o.status = 'active' AND DATE(o.next_delivery_date) < '$todayStr' THEN 2
            WHEN o.status = 'active' AND DATE(o.next_delivery_date) = '$todayStr' THEN 3
            WHEN o.status = 'active' AND DATE(o.next_delivery_date) = '$tomorrowStr' THEN 4
            ELSE 5
        END,
        o.next_delivery_date ASC, 
        o.created_at DESC 
    LIMIT 100
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$user_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function translate_status($status) {
    $map = [
        'active' => 'فعال',
        'ended' => 'پایان یافته',
        'cancelled' => 'لغو شده'
    ];
    return $map[$status] ?? $status;
}
?>

<div class="p-8 max-w-[1400px] mx-auto">
    
    <!-- Flash Messages -->
    <?php if(!empty($flash_success)): ?>
    <div class="mb-6 p-4 bg-status-active/10 border border-status-active/20 text-status-active rounded-2xl flex items-center gap-3 font-bold text-sm shadow-sm">
        <span class="material-symbols-outlined text-xl">check_circle</span>
        <span><?= htmlspecialchars($flash_success) ?></span>
    </div>
    <?php endif; ?>
    <?php if(!empty($flash_error)): ?>
    <div class="mb-6 p-4 bg-error/10 border border-error/20 text-error rounded-2xl flex items-center gap-3 font-bold text-sm shadow-sm">
        <span class="material-symbols-outlined text-xl">error</span>
        <span><?= htmlspecialchars($flash_error) ?></span>
    </div>
    <?php endif; ?>

    <!-- Header Section -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-secondary-container">local_shipping</span>
                تقویم و مدیریت نوبت‌های ارسال
            </h2>
            <p class="text-on-surface-variant font-body-md mt-1 text-xs sm:text-sm">
                برنامه‌ریزی نوبت‌های تحویل، مدیریت گزارش‌های عدم دریافت کاربر و تخصیص بسته‌ها
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="inventory.php?tab=autoship" class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-lg">inventory</span>
                انبار اختصاصی اشتراک‌ها
            </a>
            <a href="export_orders.php" class="flex items-center gap-2 bg-secondary-container text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm hover:bg-secondary transition-all">
                <span class="material-symbols-outlined text-lg">download</span>
                خروجی لیست ارسال
            </a>
        </div>
    </header>

    <!-- Dispatch Metrics Bento Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today Dispatches -->
        <a href="?filter=today" class="p-6 rounded-2xl shadow-sm border transition-all flex items-center gap-4 group cursor-pointer <?= $filter === 'today' ? 'bg-primary text-white border-primary shadow-lg ring-2 ring-primary/20' : 'bg-white border-outline-variant/30 hover:border-primary/50' ?>">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $filter === 'today' ? 'bg-white/20 text-white' : 'bg-secondary-container/15 text-secondary-container group-hover:scale-110 transition-transform' ?>">
                <span class="material-symbols-outlined text-2xl">rocket_launch</span>
            </div>
            <div>
                <p class="text-xs font-bold <?= $filter === 'today' ? 'text-white/80' : 'text-on-surface-variant' ?>">ارسال‌های امروز</p>
                <p class="text-2xl font-bold <?= $filter === 'today' ? 'text-white' : 'text-primary' ?> persian-number"><?= number_format($todayCount) ?> <span class="text-xs font-normal">بسته</span></p>
            </div>
        </a>

        <!-- Tomorrow Dispatches -->
        <a href="?filter=tomorrow" class="p-6 rounded-2xl shadow-sm border transition-all flex items-center gap-4 group cursor-pointer <?= $filter === 'tomorrow' ? 'bg-primary text-white border-primary shadow-lg ring-2 ring-primary/20' : 'bg-white border-outline-variant/30 hover:border-primary/50' ?>">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $filter === 'tomorrow' ? 'bg-white/20 text-white' : 'bg-primary-container/10 text-primary group-hover:scale-110 transition-transform' ?>">
                <span class="material-symbols-outlined text-2xl">event_upcoming</span>
            </div>
            <div>
                <p class="text-xs font-bold <?= $filter === 'tomorrow' ? 'text-white/80' : 'text-on-surface-variant' ?>">ارسال‌های فردا (آماده‌سازی)</p>
                <p class="text-2xl font-bold <?= $filter === 'tomorrow' ? 'text-white' : 'text-primary' ?> persian-number"><?= number_format($tomorrowCount) ?> <span class="text-xs font-normal">بسته</span></p>
            </div>
        </a>

        <!-- Action Required / Issues -->
        <a href="?filter=issues" class="p-6 rounded-2xl shadow-sm border transition-all flex items-center gap-4 group cursor-pointer <?= in_array($filter, ['issues', 'overdue']) ? 'bg-error text-white border-error shadow-lg ring-2 ring-error/20' : 'bg-white border-outline-variant/30 hover:border-error/50' ?>">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= in_array($filter, ['issues', 'overdue']) ? 'bg-white/20 text-white' : 'bg-error/10 text-error group-hover:scale-110 transition-transform' ?>">
                <span class="material-symbols-outlined text-2xl">report_problem</span>
            </div>
            <div>
                <p class="text-xs font-bold <?= in_array($filter, ['issues', 'overdue']) ? 'text-white/80' : 'text-on-surface-variant' ?>">عدم دریافت / معوق</p>
                <p class="text-2xl font-bold <?= in_array($filter, ['issues', 'overdue']) ? 'text-white' : 'text-error' ?> persian-number"><?= number_format($totalIssuesCount) ?> <span class="text-xs font-normal">مورد نیازمند اقدام</span></p>
            </div>
        </a>

        <!-- Total 7 Days Outlook -->
        <a href="?filter=week" class="p-6 rounded-2xl shadow-sm border transition-all flex items-center gap-4 group cursor-pointer <?= $filter === 'week' ? 'bg-primary text-white border-primary shadow-lg ring-2 ring-primary/20' : 'bg-white border-outline-variant/30 hover:border-primary/50' ?>">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $filter === 'week' ? 'bg-white/20 text-white' : 'bg-tertiary-fixed flex text-tertiary group-hover:scale-110 transition-transform' ?>">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
            <div>
                <p class="text-xs font-bold <?= $filter === 'week' ? 'text-white/80' : 'text-on-surface-variant' ?>">ارسال‌های ۷ روز آینده</p>
                <p class="text-2xl font-bold <?= $filter === 'week' ? 'text-white' : 'text-primary' ?> persian-number"><?= number_format($weekCount) ?> <span class="text-xs font-normal">بسته</span></p>
            </div>
        </a>
    </div>

    <!-- 📅 7-DAY ROLLING DISPATCH CALENDAR STRIP -->
    <section class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30 mb-8">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-outline-variant/20">
            <h3 class="text-sm font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container text-xl">calendar_view_week</span>
                تقویم زمان‌بندی ارسال روزهای جاری (انتخاب روز برای فیلتر)
            </h3>
            <span class="text-xs text-on-surface-variant">بر اساس تاریخ تحویل بعدی مشتری</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <?php foreach($rollingDays as $day): ?>
                <?php 
                    $isSelected = ($filter === 'date' && $custom_date === $day['raw_date']) || 
                                  ($filter === 'today' && $day['is_today']) || 
                                  ($filter === 'tomorrow' && $day['is_tomorrow']);
                ?>
                <a href="?filter=date&d=<?= $day['raw_date'] ?>" 
                   class="p-4 rounded-2xl border text-center transition-all flex flex-col justify-between relative group cursor-pointer <?= $isSelected ? 'bg-primary text-white border-primary shadow-md ring-2 ring-primary/20 scale-[1.02]' : ($day['count'] > 0 ? 'bg-surface-container-low border-outline-variant/40 hover:border-primary' : 'bg-white border-outline-variant/20 hover:bg-surface-container-lowest') ?>">
                    
                    <div>
                        <span class="text-[11px] font-bold block mb-1 <?= $isSelected ? 'text-white/90' : ($day['is_today'] ? 'text-secondary-container' : 'text-on-surface-variant') ?>">
                            <?= $day['label'] ?>
                        </span>
                        <p class="text-sm font-bold persian-number mb-2"><?= $day['persian_date'] ?></p>
                    </div>

                    <div class="mt-auto pt-2 border-t <?= $isSelected ? 'border-white/20' : 'border-outline-variant/20' ?>">
                        <?php if($day['count'] > 0): ?>
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold persian-number <?= $isSelected ? 'bg-white text-primary' : 'bg-secondary-container text-white' ?>">
                                <?= $day['count'] ?> ارسال
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] text-on-surface-variant/60 block py-1">بدون نوبت</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Subscriptions List Section with Filters & Search -->
    <section class="bg-white rounded-3xl shadow-sm overflow-hidden border border-outline-variant/30">
        
        <!-- Table Toolbar -->
        <div class="p-6 border-b border-outline-variant/20 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-surface-container-lowest">
            
            <!-- Quick Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 lg:pb-0">
                <a href="?filter=all" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'all' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                    همه نوبت‌ها (<?= $allActiveCount ?>)
                </a>
                <a href="?filter=today" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1 <?= $filter === 'today' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                    <span>امروز</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $filter === 'today' ? 'bg-white text-primary' : 'bg-secondary-container text-white' ?>"><?= $todayCount ?></span>
                </a>
                <a href="?filter=tomorrow" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1 <?= $filter === 'tomorrow' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                    <span>فردا</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $filter === 'tomorrow' ? 'bg-white text-primary' : 'bg-primary text-white' ?>"><?= $tomorrowCount ?></span>
                </a>
                <a href="?filter=issues" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 <?= in_array($filter, ['issues', 'overdue']) ? 'bg-error text-white shadow-md' : 'bg-error/10 hover:bg-error/20 text-error' ?>">
                    <span class="material-symbols-outlined text-[14px]">warning</span>
                    <span>عدم دریافت / معوق</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-error text-white"><?= $totalIssuesCount ?></span>
                </a>
                <a href="?filter=week" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $filter === 'week' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                    ۷ روز آینده (<?= $weekCount ?>)
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" class="flex items-center gap-2 min-w-[280px]">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <?php if(!empty($custom_date)): ?>
                    <input type="hidden" name="d" value="<?= htmlspecialchars($custom_date) ?>">
                <?php endif; ?>
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                    <input type="text" name="search" id="subSearchInput" value="<?= htmlspecialchars($search) ?>" placeholder="جستجوی نام، موبایل، شناسه..." class="w-full pr-10 pl-8 py-2 bg-white rounded-xl border border-outline-variant/40 text-xs outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    <?php if(!empty($search) || $filter !== 'all'): ?>
                        <a href="subscriptions.php" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-error transition-colors" title="ریست فیلترها">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl font-bold text-xs hover:bg-primary-container transition-all shrink-0">
                    جستجو
                </button>
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-lowest border-b border-outline-variant/20 text-xs text-on-surface-variant">
                    <tr>
                        <th class="px-6 py-4 font-bold">شناسه و پلن</th>
                        <th class="px-6 py-4 font-bold">مشتری و آدرس ارسال</th>
                        <th class="px-6 py-4 font-bold">مبلغ دوره</th>
                        <th class="px-6 py-4 font-bold">وضعیت اشتراک</th>
                        <th class="px-6 py-4 font-bold">وضعیت نوبت تحویل و گزارش‌ها</th>
                        <th class="px-6 py-4 font-bold text-center">اقدام سریع تحویل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-xs">
                    <?php if(empty($user_subscriptions)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">local_shipping</span>
                                    <p class="font-bold text-sm">هیچ نوبت ارسالی در این بازه زمانی یافت نشد.</p>
                                    <a href="subscriptions.php" class="text-xs text-primary font-bold hover:underline mt-1">مشاهده همه اشتراک‌ها</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($user_subscriptions as $subscription): 
                            $is_active = ($subscription['status'] === 'active');
                            $nextDate = $subscription['next_delivery_date'];
                            
                            $hasNotReceivedReport = ((int)$subscription['not_received_count'] > 0);
                            $isDueToday = ($nextDate === $todayStr);
                            $isDueTomorrow = ($nextDate === $tomorrowStr);
                            $isOverdue = ($nextDate && $nextDate < $todayStr && $is_active);

                            $days_remaining = null;
                            $can_dispatch = false;

                            if ($nextDate) {
                                $nowTime = strtotime($todayStr);
                                $targetTime = strtotime($nextDate);
                                $days_remaining = round(($targetTime - $nowTime) / 86400);

                                // Dispatch button enabled 2 days or less before scheduled time
                                if ($days_remaining <= 2) {
                                    $can_dispatch = true;
                                }
                            }

                            // User Autoship Preference & Timeline Calculations
                            $freq_key = $subscription['delivery_frequency'] ?? '1_month';
                            $freq_label = format_autoship_frequency($freq_key);
                            $user_pref_days = get_autoship_frequency_days($freq_key);

                            // Duration calculation
                            $duration_months = (int)($subscription['duration_months'] ?? 0);
                            if ($duration_months <= 0) {
                                if (str_contains($subscription['plan_name'], '6')) {
                                    $duration_months = 6;
                                } elseif (str_contains($subscription['plan_name'], '12') || str_contains($subscription['plan_name'], 'سال')) {
                                    $duration_months = 12;
                                } elseif (str_contains($subscription['plan_name'], '3')) {
                                    $duration_months = 3;
                                } else {
                                    $duration_months = max(1, (int)($subscription['total_deliveries_count'] ?? 1));
                                }
                            }

                            // Timeline start and end dates
                            $start_raw = !empty($subscription['first_delivery_date']) ? $subscription['first_delivery_date'] : $subscription['created_at'];
                            $start_dt = new DateTime($start_raw);
                            $start_persian = $fmtDateOnly->format($start_dt);

                            if (!empty($subscription['last_delivery_date'])) {
                                $end_dt = new DateTime($subscription['last_delivery_date']);
                            } else {
                                $end_dt = (clone $start_dt)->modify("+{$duration_months} months");
                            }
                            $end_persian = $fmtDateOnly->format($end_dt);
                            $timeline_label = "از $start_persian تا $end_persian ($duration_months ماهه)";

                            $completed_deliveries = (int)$subscription['shipped_count'] + (int)$subscription['delivered_count'];
                            $total_deliveries = max(1, (int)$subscription['total_deliveries_count']);
                        ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors group <?= $hasNotReceivedReport ? 'bg-error/5 border-r-4 border-r-error' : '' ?>">
                            <!-- ID & Plan & Autoship Selection Declaration -->
                            <td class="px-6 py-4">
                                <a href="subscription_details.php?id=<?= $subscription['id'] ?>" class="font-bold text-primary hover:underline block text-sm mb-1">
                                    #SUB-<?= $subscription['id'] ?>
                                </a>
                                <span class="bg-primary/5 text-primary px-2 py-0.5 rounded-md font-bold text-[11px] inline-block mb-1.5">
                                    <?= htmlspecialchars($subscription['plan_name']) ?>
                                </span>

                                <!-- User Autoship Frequency Declaration -->
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 px-2 py-0.5 rounded-md text-[10px] font-bold shadow-xs" title="بازه ارسال انتخابی کاربر">
                                        <span class="material-symbols-outlined text-[12px] text-amber-600">autorenew</span>
                                        <span>بازه:</span>
                                        <strong><?= htmlspecialchars($freq_label) ?></strong>
                                    </span>
                                </div>

                                <!-- From When to When Timeline -->
                                <div class="mt-1 text-[10px] text-on-surface-variant flex items-center gap-1" title="بازه زمانی اشتراک">
                                    <span class="material-symbols-outlined text-[12px] text-primary/70">date_range</span>
                                    <span>از</span>
                                    <strong class="text-on-surface persian-number"><?= $start_persian ?></strong>
                                    <span>تا</span>
                                    <strong class="text-on-surface persian-number"><?= $end_persian ?></strong>
                                    <span class="text-primary font-bold">(<?= $duration_months ?> ماهه)</span>
                                </div>

                                <!-- Delivery Step Counter -->
                                <div class="mt-0.5 text-[10px] text-on-surface-variant/80 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px] text-secondary-container">local_shipping</span>
                                    <span>ارسال شده:</span>
                                    <strong class="text-primary persian-number"><?= $completed_deliveries ?></strong> از <strong class="text-primary persian-number"><?= $total_deliveries ?></strong> نوبت
                                </div>
                            </td>

                            <!-- Customer & Contact -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface text-sm"><?= htmlspecialchars($subscription['user_name']) ?></p>
                                <p class="text-on-surface-variant font-mono text-[11px]" dir="ltr"><?= htmlspecialchars($subscription['user_phone'] ?: '-') ?></p>
                                <?php if(!empty($subscription['user_address'])): ?>
                                    <p class="text-[10px] text-on-surface-variant/80 line-clamp-1 max-w-xs mt-0.5">📍 <?= htmlspecialchars($subscription['user_address']) ?></p>
                                <?php endif; ?>
                            </td>

                            <!-- Amount -->
                            <td class="px-6 py-4 font-bold text-primary persian-number text-sm">
                                <?= number_format($subscription['amount']) ?> تومان
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold inline-block <?php 
                                    switch($subscription['status']) {
                                        case 'active': echo 'bg-status-active/10 text-status-active border border-status-active/20'; break;
                                        case 'ended': echo 'bg-surface-variant text-on-surface-variant'; break;
                                        case 'cancelled': echo 'bg-error/10 text-error'; break;
                                    }
                                ?>">
                                    <?= translate_status($subscription['status']) ?>
                                </span>
                            </td>

                            <!-- Next Delivery Date & Action Tag -->
                            <td class="px-6 py-4">
                                <?php if($subscription['next_delivery_date']): ?>
                                    <p class="font-bold persian-number text-sm text-primary mb-1">
                                        <?= $fmtDateOnly->format(new DateTime($subscription['next_delivery_date'])) ?>
                                    </p>
                                    <div>
                                        <?php if($hasNotReceivedReport): ?>
                                            <span class="inline-flex items-center gap-1 bg-error text-white px-2 py-0.5 rounded text-[10px] font-bold shadow-sm animate-pulse">
                                                <span class="material-symbols-outlined text-[12px]">report_problem</span>
                                                گزارش عدم دریافت مشتری 🚨
                                            </span>
                                        <?php elseif($isDueToday): ?>
                                            <span class="inline-flex items-center gap-1 bg-secondary-container text-white px-2 py-0.5 rounded text-[10px] font-bold animate-pulse">
                                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                نوبت ارسال امروز 🚀
                                            </span>
                                        <?php elseif($isDueTomorrow): ?>
                                            <span class="inline-flex items-center gap-1 bg-primary text-white px-2 py-0.5 rounded text-[10px] font-bold">
                                                <span class="material-symbols-outlined text-[12px]">event</span>
                                                نوبت ارسال فردا 🚚
                                            </span>
                                        <?php elseif($isOverdue): ?>
                                            <span class="inline-flex items-center gap-1 bg-amber-600 text-white px-2 py-0.5 rounded text-[10px] font-bold">
                                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                نوبت معوق (تاخیر در ارسال) ⏰
                                            </span>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant text-[11px] font-medium">
                                                <?= $fmtDayName->format(new DateTime($subscription['next_delivery_date'])) ?>
                                                <span class="text-primary font-bold">(<?= $days_remaining ?> روز مانده)</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-on-surface-variant">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Quick Dispatch & Incident Resolution Actions -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if($hasNotReceivedReport): ?>
                                        <button type="button" 
                                                onclick="openResolveModal(<?= $subscription['id'] ?>, '<?= addslashes($subscription['user_name']) ?>', '<?= addslashes($subscription['plan_name']) ?>')"
                                                class="px-3.5 py-1.5 bg-error hover:bg-red-700 text-white rounded-xl font-bold text-xs transition-all shadow-sm flex items-center gap-1.5 active:scale-95 cursor-pointer"
                                                title="حل مشکل و رفع گزارش عدم دریافت">
                                            <span class="material-symbols-outlined text-base">task_alt</span>
                                            حل مشکل
                                        </button>
                                    <?php elseif($is_active): ?>
                                        <?php if($can_dispatch): ?>
                                            <button type="button" 
                                                    onclick="openDispatchModal(<?= $subscription['id'] ?>, '<?= addslashes($subscription['user_name']) ?>', '<?= addslashes($subscription['plan_name']) ?>', '<?= $subscription['next_delivery_date'] ?>', <?= $user_pref_days ?>, '<?= addslashes($freq_label) ?>', '<?= addslashes($timeline_label) ?>')"
                                                    class="px-3.5 py-1.5 bg-secondary-container hover:bg-secondary text-white rounded-xl font-bold text-xs transition-all shadow-sm flex items-center gap-1.5 active:scale-95 cursor-pointer"
                                                    title="ثبت تحویل بسته به پیک">
                                                <span class="material-symbols-outlined text-base">moped</span>
                                                ثبت ارسال
                                            </button>
                                        <?php else: ?>
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-high text-on-surface-variant/80 text-[11px] font-bold border border-outline-variant/30 select-none cursor-not-allowed" 
                                                 title="امکان ثبت ارسال فقط از ۲ روز مانده به موعد ارسال فعال می‌شود">
                                                <span class="material-symbols-outlined text-sm text-on-surface-variant">lock_clock</span>
                                                <span><?= $days_remaining ?> روز مانده</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="subscription_details.php?id=<?= $subscription['id'] ?>" class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-xl transition-colors" title="مشاهده جزئیات">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Quick Dispatch Modal -->
<div id="dispatchModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-bold text-primary flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-secondary-container">local_shipping</span>
                ثبت ارسال و تنظیم نوبت بعدی
            </h3>
            <button onclick="closeDispatchModal()" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="quick_dispatch">
            <input type="hidden" name="subscription_id" id="dispatchSubId" value="">

            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/30 text-xs space-y-1.5">
                <p><span class="text-on-surface-variant">مشتری:</span> <strong class="text-primary" id="dispatchCustomerName">-</strong></p>
                <p><span class="text-on-surface-variant">پلن:</span> <strong class="text-on-surface" id="dispatchPlanName">-</strong></p>
                <p><span class="text-on-surface-variant">تاریخ نوبت جاری:</span> <strong class="text-secondary-container persian-number" id="dispatchDate">-</strong></p>
                <div class="pt-1.5 border-t border-outline-variant/20 space-y-1">
                    <p class="flex items-center justify-between">
                        <span class="text-on-surface-variant">بازه انتخابی مشتری:</span>
                        <strong class="text-amber-800 bg-amber-100/80 px-2 py-0.5 rounded border border-amber-200" id="dispatchUserPref">-</strong>
                    </p>
                    <p class="flex items-center justify-between">
                        <span class="text-on-surface-variant">بازه زمانی کل اشتراک:</span>
                        <strong class="text-primary persian-number" id="dispatchTimeline">-</strong>
                    </p>
                </div>
            </div>

            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-on-surface">فاصله زمانی تا نوبت ارسال بعدی:</label>
                    <span class="text-[10px] bg-secondary-container/10 text-secondary-container font-bold px-2 py-0.5 rounded-full" id="dispatchPrefNotice">پیش‌فرض: بر اساس انتخاب کاربر</span>
                </div>
                <select name="advance_days" id="dispatchAdvanceDays" class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none focus:border-primary font-bold">
                    <option value="14">۲ هفته آینده (+۱۴ روز)</option>
                    <option value="30">۱ ماه آینده (+۳۰ روز)</option>
                    <option value="60">۲ ماه آینده (+۶۰ روز)</option>
                    <option value="90">۳ ماه آینده (+۹۰ روز)</option>
                    <option value="7">۱ هفته آینده (+۷ روز)</option>
                </select>
                <p class="text-[10px] text-on-surface-variant">
                    💡 بازه انتخابی کاربر به‌صورت پیش‌فرض در لیست بالا انتخاب شده است. در صورت تمایل می‌توانید فاصله دیگری تعیین فرمایید.
                </p>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface">یادداشت ارسال یا کد رهگیری (اختیاری):</label>
                <input type="text" name="tracking_note" placeholder="مثال: تحویل پیک شهری شماره ۱۲" class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none focus:border-primary">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeDispatchModal()" class="px-4 py-2.5 rounded-xl font-bold text-xs text-on-surface-variant hover:bg-surface-container transition-colors">انصراف</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-xs bg-secondary-container hover:bg-secondary text-white shadow-sm transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">check</span>
                    تایید ارسال بسته
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Incident Resolution Modal -->
<div id="resolveModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant/20 flex justify-between items-center bg-error/10 text-error">
            <h3 class="font-bold flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined">report_problem</span>
                حل مشکل و رفع گزارش عدم دریافت
            </h3>
            <button onclick="closeResolveModal()" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="resolve_incident">
            <input type="hidden" name="subscription_id" id="resolveSubId" value="">

            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/30 text-xs space-y-1">
                <p><span class="text-on-surface-variant">مشتری:</span> <strong class="text-primary" id="resolveCustomerName">-</strong></p>
                <p><span class="text-on-surface-variant">پلن:</span> <strong class="text-on-surface" id="resolvePlanName">-</strong></p>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface">اقدام انجام شده جهت رفع مشکل:</label>
                <select name="resolution_type" class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none focus:border-primary">
                    <option value="delivered">✅ تحویل بسته تایید شد (هماهنگی با مشتری/پیک انجام شد)</option>
                    <option value="resend">📦 ارسال مجدد بسته جایگزین (تغییر وضعیت به در مسیر ارسال)</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface">توضیحات و یادداشت پیگیری ادمین:</label>
                <textarea name="admin_note" rows="2" placeholder="مثال: با مشتری تلفنی صحبت شد و بسته توسط پیک مجدداً تحویل گردید." class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none focus:border-primary resize-none"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeResolveModal()" class="px-4 py-2.5 rounded-xl font-bold text-xs text-on-surface-variant hover:bg-surface-container transition-colors">انصراف</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-xs bg-status-active hover:opacity-90 text-white shadow-sm transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                    ثبت رفع مشکل
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDispatchModal(subId, customerName, planName, curDate, prefDays, prefText, timelineText) {
    document.getElementById('dispatchSubId').value = subId;
    document.getElementById('dispatchCustomerName').textContent = customerName;
    document.getElementById('dispatchPlanName').textContent = planName;
    document.getElementById('dispatchDate').textContent = curDate || 'امروز';
    document.getElementById('dispatchUserPref').textContent = prefText || 'هر ۱ ماه (۳۰ روز)';
    document.getElementById('dispatchTimeline').textContent = timelineText || '-';

    const select = document.getElementById('dispatchAdvanceDays');
    if (select && prefDays) {
        select.value = String(prefDays);
        if (!select.value) {
            select.value = "30";
        }
    }
    
    const modal = document.getElementById('dispatchModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDispatchModal() {
    const modal = document.getElementById('dispatchModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openResolveModal(subId, customerName, planName) {
    document.getElementById('resolveSubId').value = subId;
    document.getElementById('resolveCustomerName').textContent = customerName;
    document.getElementById('resolvePlanName').textContent = planName;
    
    const modal = document.getElementById('resolveModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeResolveModal() {
    const modal = document.getElementById('resolveModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
