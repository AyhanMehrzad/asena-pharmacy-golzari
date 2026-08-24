<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user pets
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pet documents
$stmt = $pdo->prepare("SELECT d.*, p.name as pet_name FROM pet_documents d JOIN user_pets p ON d.pet_id = p.id WHERE d.user_id = ? ORDER BY d.uploaded_at DESC");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, d.specialty as doctor_specialty, d.image_url as doctor_image 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 4
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order history
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user subscriptions
$stmt = $pdo->prepare("
    SELECT * FROM user_subscriptions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach deliveries to each subscription
if (!empty($user_subscriptions)) {
    $sub_ids   = array_column($user_subscriptions, 'id');
    $ph        = implode(',', array_fill(0, count($sub_ids), '?'));
    $delStmt   = $pdo->prepare("SELECT * FROM subscription_deliveries WHERE subscription_id IN ($ph) ORDER BY delivery_month ASC");
    $delStmt->execute($sub_ids);
    $all_dels  = $delStmt->fetchAll(PDO::FETCH_ASSOC);
    $dels_by_sub = [];
    foreach ($all_dels as $del) {
        $dels_by_sub[$del['subscription_id']][] = $del;
    }
    
    foreach ($user_subscriptions as &$sub) {
        $sub['deliveries'] = $dels_by_sub[$sub['id']] ?? [];
    }
}


// Attach order items to each order
if (!empty($orders)) {
    $order_ids   = array_column($orders, 'id');
    $order_ph    = implode(',', array_fill(0, count($order_ids), '?'));
    $itemsStmt   = $pdo->prepare("
        SELECT oi.*, p.image_url, p.category, p.brand, p.target_animal, p.pharmacy_tag, p.is_autoship 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id IN ($order_ph)
    ");
    $itemsStmt->execute($order_ids);
    $all_items   = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    $items_by_order = [];
    foreach ($all_items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
    foreach ($orders as &$order) {
        $order['items'] = $items_by_order[$order['id']] ?? [];
    }
    unset($order);
}

// Fetch active subscriptions
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_subscriptions 
    WHERE user_id = ? AND status = 'active'
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Date Formatter for Jalali
$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
$fmtDateTime = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY - HH:mm');
$fmtDateText = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY');
?>
<?php require_once 'includes/header.php'; ?>
<style>
    .persian-number {
        font-feature-settings: "ss01", "ss02", "ss03", "ss04";
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
</style>

<!-- Mobile Backdrop -->
<div id="profile-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleProfileSidebar()"></div>

<!-- SideNavBar -->
<aside id="profile-sidebar" class="fixed right-0 top-0 lg:top-16 bottom-0 w-64 p-6 flex flex-col bg-surface-container-lowest border-l border-outline-variant z-[70] lg:z-40 transition-transform duration-300 translate-x-full lg:translate-x-0">
<div class="mb-10 flex justify-between items-center">
<div>
<h2 class="text-lg font-bold text-primary">پنل کاربری</h2>
<p class="text-xs text-on-surface-variant">خدمات حرفه‌ای حیوانات خانگی</p>
</div>
<button onclick="toggleProfileSidebar()" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-full bg-surface-container hover:bg-error/10 hover:text-error transition-colors">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<nav class="flex-1 flex flex-col gap-1">
<a class="flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold transition-all shadow-md" href="profile.php">
<span class="material-symbols-outlined">dashboard</span>
<span class="text-sm">پیشخوان</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#appointments-section">
<span class="material-symbols-outlined">calendar_today</span>
<span class="text-sm">نوبت‌های من</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#subscriptions-section">
<span class="material-symbols-outlined">event_repeat</span>
<span class="text-sm">اشتراک‌های فعال</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#orders-section">
<span class="material-symbols-outlined">history</span>
<span class="text-sm">تاریخچه سفارشات</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#pets-section">
<span class="material-symbols-outlined">pets</span>
<span class="text-sm">حیوانات من</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="wishlist.php">
<span class="material-symbols-outlined">favorite</span>
<span class="text-sm">علاقه‌مندی‌ها</span>
</a>
<?php if(isset($user['role']) && $user['role'] === 'admin'): ?>
<a class="flex items-center gap-3 px-4 py-3 text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all font-bold" href="admin/index.php">
<span class="material-symbols-outlined">admin_panel_settings</span>
<span class="text-sm">پنل مدیریت سایت</span>
</a>
<?php endif; ?>
</nav>
<div class="pt-6 border-t border-outline-variant flex flex-col gap-1">
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="profile_settings.php">
<span class="material-symbols-outlined">settings</span>
<span class="text-sm">تنظیمات</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="user_tickets.php">
<span class="material-symbols-outlined">help</span>
<span class="text-sm">پشتیبانی و تیکت‌ها</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all" href="logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-sm">خروج</span>
</a>
</div>
</aside>
<!-- Main Content -->
<main class="lg:mr-64 mr-0 mt-16 p-4 md:p-8 min-h-screen transition-all duration-300">
<div class="max-w-[1200px] mx-auto space-y-6 md:space-y-8">

<!-- Mobile Header Toggle -->
<div class="lg:hidden flex justify-between items-center bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm mb-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center font-bold text-lg">
            <?php echo mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8'); ?>
        </div>
        <div>
            <h1 class="font-bold text-primary text-sm">پنل کاربری شما</h1>
            <p class="text-[11px] text-on-surface-variant">مشاهده و مدیریت اطلاعات</p>
        </div>
    </div>
    <button onclick="toggleProfileSidebar()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
        <span class="material-symbols-outlined">menu_open</span>
    </button>
</div>
<?php if ($success): ?>
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white p-6 sm:p-8 rounded-3xl shadow-xl shadow-emerald-700/20 relative overflow-hidden animate-fade-in border border-white/20 mb-8">
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white flex-shrink-0 shadow-inner border border-white/30">
                    <span class="material-symbols-outlined text-3xl text-emerald-100">verified</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-emerald-400/30 text-emerald-100 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-300/30">تراکنش بانکی موفق</span>
                        <span class="text-xs text-emerald-100 font-medium">🎉 سفارش شما با موفقیت ثبت شد</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-snug"><?php echo htmlspecialchars($success); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-2.5 self-end md:self-center">
                <a href="#orders-section" class="bg-white text-emerald-800 hover:bg-emerald-50 px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 active:scale-95">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    مشاهده فاکتور
                </a>
                <a href="pharmacy.php" class="bg-white/15 hover:bg-white/25 text-white px-4 py-2.5 rounded-xl font-bold text-xs transition-all border border-white/20 active:scale-95">
                    داروخانه
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <a href="profile_settings.php" class="block bg-error/10 text-error p-4 rounded-2xl flex items-center gap-3 border border-error/20 hover:bg-error/20 transition-colors cursor-pointer group mb-6">
        <span class="material-symbols-outlined group-hover:scale-110 transition-transform">error</span>
        <span class="font-bold text-sm flex-1"><?php echo htmlspecialchars($error); ?></span>
        <span class="material-symbols-outlined">chevron_left</span>
    </a>
<?php endif; ?>
<!-- Profile Overview Card (Intelligent Dashboard style) -->
<section class="glass-card rounded-2xl p-8 border border-outline-variant shadow-lg flex flex-col lg:flex-row justify-between items-center gap-8 relative overflow-hidden">
<div class="absolute top-0 right-0 w-32 h-32 bg-primary-container/5 rounded-full -mr-16 -mt-16"></div>
<div class="flex items-center gap-6 relative z-10">
<div class="w-20 h-20 rounded-2xl bg-primary-container flex items-center justify-center text-white font-bold text-3xl shadow-inner">
                    <?php echo mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8'); ?>
                </div>
<div>
<h2 class="text-2xl font-bold text-on-surface"><?php echo htmlspecialchars($user['name'] ?? 'کاربر مهمان'); ?> عزیز، خوش آمدید</h2>
<p class="text-sm text-on-surface-variant">
    <?php if(count($pets) > 0): ?>
        والدِ <span class="font-bold text-primary"><?php echo htmlspecialchars(implode(' و ', array_column($pets, 'name'))); ?></span> • 
    <?php endif; ?>
    عضو سطح طلایی
</p>
</div>
</div>
<div class="flex gap-4 items-center relative z-10">
<div class="flex flex-col items-center bg-white px-6 py-4 rounded-2xl border border-outline-variant shadow-sm min-w-[140px]">
<p class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold mb-1">امتیاز وفاداری</p>
<p class="text-2xl font-bold text-secondary persian-number"><?php echo number_format($user['loyalty_points'] ?? 0); ?></p>
</div>
<div class="flex flex-col items-center bg-primary-container text-white px-6 py-4 rounded-2xl shadow-md min-w-[180px]">
<p class="text-[10px] uppercase tracking-wider opacity-80 font-bold mb-1">نوبت بعدی</p>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-sm">calendar_month</span>
<?php if (!empty($appointments)): ?>
    <p class="text-base font-bold persian-number"><?php 
        $next_date = new DateTime($appointments[0]['appointment_date'] . ' ' . $appointments[0]['appointment_time']);
        echo $fmtDateTime->format($next_date); 
    ?></p>
<?php else: ?>
    <p class="text-base font-bold">ندارید</p>
<?php endif; ?>
</div>
</div>
</div>
<a href="booking.php" class="bg-primary-container text-white px-8 py-3.5 rounded-xl font-bold text-sm hover:bg-primary transition-all active:scale-95 shadow-lg shadow-primary-container/20 flex items-center gap-2">
                رزرو نوبت جدید
            </a>
</section>
<!-- Grid Layout -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
<!-- Right Column: Appointments & History -->
<div class="lg:col-span-8 space-y-8">
<!-- Upcoming Appointments (High Fidelity) -->
<div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
<h3 class="text-lg font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined">medical_information</span>
                            نوبت‌های پیش رو
                        </h3>
<button class="text-sm font-bold text-primary-container hover:underline">مشاهده همه</button>
</div>
<div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
<?php if (empty($appointments)): ?>
    <div class="col-span-full text-center py-8 text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">calendar_month</span>
        <p class="font-bold">شما هیچ نوبت رزرو شده‌ای ندارید.</p>
        <a href="booking.php" class="text-primary-container hover:underline text-sm mt-2 inline-block">برای رزرو نوبت کلیک کنید</a>
    </div>
<?php else: ?>
    <?php foreach ($appointments as $apt): ?>
        <div class="group border border-outline-variant p-5 rounded-2xl flex flex-col gap-4 hover:border-primary-container hover:shadow-xl transition-all duration-300">
        <div class="flex gap-4">
        <div class="relative">
        <img alt="<?php echo htmlspecialchars($apt['doctor_name']); ?>" class="w-16 h-16 rounded-xl object-cover" src="<?php echo htmlspecialchars($apt['doctor_image'] ?? 'https://via.placeholder.com/150'); ?>"/>
        <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-status-active border-2 border-white rounded-full"></span>
        </div>
        <div class="flex-1">
        <div class="flex justify-between items-start">
        <h4 class="text-base font-bold text-primary"><?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
        <div class="flex items-center gap-0.5 text-status-warning">
        <span class="text-[10px] bg-primary-container/10 text-primary-container px-2 py-0.5 rounded-full font-bold"><?php echo htmlspecialchars($apt['status'] == 'pending' ? 'در انتظار' : 'تایید شده'); ?></span>
        </div>
        </div>
        <p class="text-xs text-on-surface-variant font-medium"><?php echo htmlspecialchars($apt['doctor_specialty']); ?></p>
        <?php if (!empty($apt['pet_type'])): ?>
            <div class="mt-2 flex gap-1">
            <span class="text-[10px] bg-secondary/10 text-secondary px-2 py-0.5 rounded-full font-bold">حیوان: <?php echo htmlspecialchars($apt['pet_type']); ?></span>
            </div>
        <?php endif; ?>
        </div>
        </div>
        <div class="bg-surface-container-low p-3 rounded-xl flex justify-between items-center persian-number text-xs font-bold">
        <div class="flex items-center gap-1.5 text-on-surface-variant">
        <span class="material-symbols-outlined text-base">calendar_today</span>
        <span><?php 
            echo $fmtDateText->format(new DateTime($apt['appointment_date'])); 
        ?></span>
        </div>
        <div class="flex items-center gap-1.5 text-on-surface-variant">
        <span class="material-symbols-outlined text-base">schedule</span>
        <span>ساعت <?php echo substr($apt['appointment_time'], 0, 5); ?></span>
        </div>
        </div>
        <?php if ($apt['status'] == 'approved'): ?>
            <button class="w-full bg-primary-container text-white py-2.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:brightness-110 transition-all mb-2">
                <span class="material-symbols-outlined text-lg">videocam</span>
                ورود به اتاق مشاوره
            </button>
            <button type="button" onclick="openRatingModal('doctor', <?= $apt['doctor_id'] ?>, 'دکتر <?= addslashes($apt['doctor_name']) ?>')" class="w-full bg-secondary-container/10 border border-secondary-container/30 text-secondary-container py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 hover:bg-secondary-container hover:text-white transition-all mb-2">
                <span class="material-symbols-outlined text-base">star</span>
                ثبت نظر و امتیاز به پزشک (+۵ امتیاز)
            </button>
        <?php else: ?>
            <div class="mb-2">
                <button onclick="document.getElementById('clinic_addr_<?= $apt['id'] ?>').classList.toggle('hidden')" class="w-full border-2 border-primary-container text-primary-container py-2 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:bg-primary-container hover:text-white transition-all">
                <span class="material-symbols-outlined text-lg">map</span>
                                                مشاهده آدرس کلینیک
                </button>
                <div id="clinic_addr_<?= $apt['id'] ?>" class="hidden mt-2 p-3 bg-surface-container-low text-on-surface text-sm rounded-lg border border-outline-variant/30 text-center font-bold">
                    تهران، ونک، خیابان ملاصدرا، پلاک ۱۲، کلینیک دامپزشکی پت‌شاپ
                </div>
            </div>
        <?php endif; ?>
        
        <?php if(in_array($apt['status'], ['pending', 'در انتظار', 'approved'])): ?>
            <form action="actions/profile_action.php" method="POST" onsubmit="return confirm('آیا از لغو این نوبت اطمینان دارید؟');" class="m-0">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="cancel_appointment">
                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                <button type="submit" class="w-full bg-error/10 text-error py-2.5 rounded-xl text-sm font-bold hover:bg-error hover:text-white transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">cancel</span>
                    لغو نوبت
                </button>
            </form>
        <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</div>
<!-- My Subscriptions -->
<div id="subscriptions-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden mb-8 scroll-mt-24">
<div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-secondary-container/10 via-primary-container/5 to-transparent">
<h3 class="text-base font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined text-secondary-container">event_repeat</span>
برنامه‌های اشتراک هوشمند و Autoship
</h3>
<a href="subscriptions.php" class="text-xs font-bold text-secondary-container hover:underline bg-white px-3 py-1.5 rounded-xl border border-secondary-container/30 shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-sm">add_circle</span>
خرید پلن جدید
</a>
</div>
<div class="overflow-x-auto">
<table class="w-full text-right text-sm">
<thead>
<tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
<th class="px-6 py-4">پلن اشتراک</th>
<th class="px-6 py-4">مبلغ (تومان)</th>
<th class="px-6 py-4">وضعیت</th>
<th class="px-6 py-4">تاریخ خرید</th>
<th class="px-6 py-4">زمان ارسال بعدی</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/50">
<?php if (empty($user_subscriptions)): ?>
    <tr>
        <td colspan="5" class="px-6 py-8 text-center text-on-surface-variant font-bold">شما در حال حاضر اشتراک فعالی ندارید.</td>
    </tr>
<?php else: ?>
    <?php foreach ($user_subscriptions as $sub): ?>
    <tr class="hover:bg-surface-container-low/50 transition-colors group">
        <td class="px-6 py-4 font-bold text-primary"><?php echo htmlspecialchars($sub['plan_name']); ?></td>
        <td class="px-6 py-4 font-bold persian-number"><?php echo number_format($sub['amount']); ?></td>
        <td class="px-6 py-4">
            <span class="px-3 py-1 text-xs font-bold rounded-full <?php
                if($sub['status'] == 'active') echo 'bg-primary-container/20 text-primary-container';
                elseif($sub['status'] == 'ended') echo 'bg-surface-variant text-on-surface-variant';
                elseif($sub['status'] == 'cancelled') echo 'bg-error/20 text-error';
                else echo 'bg-surface-container text-on-surface';
            ?>">
                <?php 
                    $status_map = ['active'=>'فعال', 'ended'=>'پایان یافته', 'cancelled'=>'لغو شده'];
                    echo $status_map[$sub['status']] ?? $sub['status']; 
                ?>
            </span>
        </td>
        <td class="px-6 py-4 text-on-surface-variant persian-number text-xs" dir="ltr">
            <?php echo $fmtDateTime->format(new DateTime($sub['created_at'])); ?>
        </td>
        <td class="px-6 py-4 font-bold text-secondary-container persian-number">
            <?php echo $sub['next_delivery_date'] ? $fmtDateText->format(new DateTime($sub['next_delivery_date'])) : 'نامشخص'; ?>
        </td>
    </tr>
    <?php if ($sub['status'] === 'active'): ?>
    <!-- User Self-Service Action Bar (Reschedule / Postpone / Cancel) -->
    <tr class="bg-surface-container-low/30 border-b border-outline-variant/20">
        <td colspan="5" class="px-6 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="font-bold text-on-surface-variant">مدیریت نوبت تحویل:</span>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Reschedule Form -->
                    <form action="actions/subscription_action.php" method="POST" class="inline-flex items-center gap-1 m-0">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reschedule_delivery">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <input type="date" name="new_date" min="<?php echo date('Y-m-d'); ?>" required class="p-1 px-2 rounded-lg border border-outline-variant text-xs outline-none bg-white">
                        <button type="submit" class="bg-primary text-white px-2.5 py-1 rounded-lg font-bold hover:bg-primary-container transition-all">تغییر تاریخ</button>
                    </form>
                    
                    <!-- Skip / Postpone -->
                    <form action="actions/subscription_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از به تعویق انداختن این نوبت ارسال به مدت ۳۰ روز اطمینان دارید؟');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="skip_delivery">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <input type="hidden" name="skip_days" value="30">
                        <button type="submit" class="bg-surface-container-high hover:bg-surface-container-highest text-primary px-3 py-1 rounded-lg font-bold transition-all border border-outline-variant/40">به تعویق انداختن (+۳۰ روز)</button>
                    </form>

                    <!-- Cancel Anytime -->
                    <form action="actions/subscription_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از لغو این اشتراک اطمینان دارید؟');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="cancel_subscription">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <button type="submit" class="text-error hover:bg-error/10 px-2.5 py-1 rounded-lg font-bold transition-colors">لغو اشتراک</button>
                    </form>
                </div>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($sub['deliveries'])): ?>
    <tr class="bg-surface-container-lowest border-b border-outline-variant/30">
        <td colspan="5" class="p-4">
            <div class="space-y-2 pl-4 max-w-2xl text-right">
                <h4 class="font-bold text-primary text-xs mb-2">زمان‌بندی ارسال‌ها:</h4>
                <?php foreach($sub['deliveries'] as $del): ?>
                    <div class="flex flex-col md:flex-row md:items-center justify-between bg-surface-container-low border border-outline-variant/20 p-2.5 rounded-lg text-xs gap-3">
                        <div class="flex items-center gap-3">
                            <span class="font-black text-on-surface-variant persian-number bg-white px-2 py-1 rounded-md shadow-sm">ماه <?php echo $del['delivery_month']; ?></span>
                            <span class="text-outline persian-number"><?php echo $del['scheduled_date'] ? $fmtDateText->format(new DateTime($del['scheduled_date'])) : ''; ?></span>
                            
                            <?php 
                            $statusText = '';
                            switch($del['status']) {
                                case 'pending': $statusText = '<span class="text-status-warning bg-status-warning/10 px-2 py-0.5 rounded-full font-bold">آماده‌سازی نوبت</span>'; break;
                                case 'processing': $statusText = '<span class="text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full font-bold">در حال پردازش انبار</span>'; break;
                                case 'shipped': $statusText = '<span class="text-primary-fixed bg-primary-fixed-dim/20 px-2 py-0.5 rounded-full font-bold">ارسال شده</span>'; break;
                                case 'delivered': $statusText = '<span class="text-status-active bg-status-active/10 px-2 py-0.5 rounded-full font-bold">تحویل داده شده</span>'; break;
                                case 'not_received': $statusText = '<span class="text-error bg-error/10 px-2 py-0.5 rounded-full font-bold">گزارش عدم دریافت</span>'; break;
                            }
                            echo $statusText;
                            ?>

                            <?php if (($del['payment_status'] ?? 'paid') === 'paid'): ?>
                                <span class="text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-bold text-[10px] inline-flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                    تسویه شده
                                </span>
                            <?php else: ?>
                                <span class="text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full font-bold text-[10px] inline-flex items-center gap-0.5" title="پیش از موعد ارسال پیامک پرداخت ارسال می‌شود">
                                    <span class="material-symbols-outlined text-[12px]">payments</span>
                                    پرداخت ماهانه در موعد (پیامک یادآوری)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($del['status'] === 'shipped'): ?>
                            <div class="flex items-center gap-2 bg-secondary-container/30 px-3 py-1.5 rounded-lg">
                                <span class="font-bold text-primary mr-2">بسته این ماه را دریافت کردید؟</span>
                                <form action="actions/subscription_action.php" method="POST" class="m-0 inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="confirm_delivery">
                                    <input type="hidden" name="delivery_id" value="<?php echo $del['id']; ?>">
                                    <button type="submit" name="received" value="1" class="bg-status-active text-white px-3 py-1 rounded-md shadow-sm hover:opacity-90 font-bold transition-opacity">بله</button>
                                    <button type="submit" name="received" value="0" class="bg-error text-white px-3 py-1 rounded-md shadow-sm hover:opacity-90 font-bold transition-opacity">خیر</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
<!-- Order History (Modern Card-Based Design) -->
<div id="orders-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden scroll-mt-24">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-surface-container-low to-transparent">
        <h3 class="text-base font-bold text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary-container">shopping_bag</span>
            سفارشات و فاکتورهای من
        </h3>
        <span class="text-xs font-bold text-on-surface-variant bg-white px-3 py-1 rounded-full border border-outline-variant/40 persian-number"><?= count($orders) ?> سفارش ثبت شده</span>
    </div>

    <div class="p-6 space-y-6">
        <?php if(empty($orders)): ?>
            <div class="text-center py-12 text-on-surface-variant space-y-3">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-surface-container flex items-center justify-center text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-3xl">remove_shopping_cart</span>
                </div>
                <p class="font-bold text-sm">شما تاکنون هیچ سفارشی ثبت نکرده‌اید.</p>
                <a href="pharmacy.php" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary-container transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm">local_pharmacy</span>
                    مشاهده داروخانه و پت‌شاپ
                </a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <?php 
                $status_bg = 'bg-blue-50 text-blue-800 border-blue-200';
                $status_dot = 'bg-blue-600';
                $status_label = 'در حال پردازش';
                
                switch($order['status']) {
                    case 'pending_payment': 
                        $status_bg = 'bg-amber-50 text-amber-800 border-amber-200'; 
                        $status_dot = 'bg-amber-500';
                        $status_label = 'در انتظار پرداخت'; 
                        break;
                    case 'processing': 
                        $status_bg = 'bg-blue-50 text-blue-800 border-blue-200'; 
                        $status_dot = 'bg-blue-600';
                        $status_label = 'در حال پردازش در انبار'; 
                        break;
                    case 'shipped': 
                        $status_bg = 'bg-indigo-50 text-indigo-800 border-indigo-200'; 
                        $status_dot = 'bg-indigo-600';
                        $status_label = 'تحویل به پیک و پست'; 
                        break;
                    case 'delivered': 
                        $status_bg = 'bg-emerald-50 text-emerald-800 border-emerald-200'; 
                        $status_dot = 'bg-emerald-600';
                        $status_label = 'تحویل داده شده'; 
                        break;
                    case 'cancelled': 
                        $status_bg = 'bg-red-50 text-red-800 border-red-200'; 
                        $status_dot = 'bg-red-600';
                        $status_label = 'لغو شده'; 
                        break;
                }
                ?>
                <!-- Single Order Card -->
                <div class="border border-outline-variant/60 rounded-2xl p-5 bg-white hover:border-primary/40 transition-all space-y-4 shadow-sm hover:shadow-md">
                    <!-- Order Top Meta Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-outline-variant/30">
                        <div class="flex flex-wrap items-center gap-2.5 text-xs">
                            <span class="font-bold text-primary font-mono text-sm">#PC-<?= $order['id'] ?></span>
                            <span class="text-outline-variant">•</span>
                            <span class="text-on-surface-variant persian-number flex items-center gap-1 font-mono">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <?= $fmtDateText->format(new DateTime($order['created_at'])) ?>
                            </span>
                            <?php if (!empty($order['gateway_ref_id'])): ?>
                                <span class="text-outline-variant">•</span>
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-md font-mono text-[11px] border border-slate-200" title="شماره پیگیری پرداخت">
                                    کد رهگیری: <?= htmlspecialchars($order['gateway_ref_id']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-3 self-end sm:self-auto">
                            <!-- Status Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $status_bg ?>">
                                <span class="w-2 h-2 rounded-full <?= $status_dot ?> animate-pulse"></span>
                                <?= $status_label ?>
                            </span>
                            
                            <!-- Cancel Button if applicable -->
                            <?php if(in_array($order['status'], ['pending_payment', 'processing'])): ?>
                                <form action="actions/profile_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از لغو این سفارش اطمینان دارید؟');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="text-error hover:bg-error/10 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors">
                                        لغو سفارش
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Grid inside Order -->
                    <?php if (!empty($order['items'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach($order['items'] as $item): ?>
                                <?php 
                                    $is_pharma = (str_contains($item['category'] ?? '', 'دارو') || str_contains($item['category'] ?? '', 'مکمل') || !empty($item['pharmacy_tag']));
                                    $item_img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'assets/images/toy-mouse.jpg';
                                ?>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-surface-container-low/40 border border-outline-variant/30 hover:bg-surface-container-low transition-colors">
                                    <a href="product_details.php?id=<?= $item['product_id'] ?>" class="w-14 h-14 rounded-xl overflow-hidden bg-white shrink-0 border border-outline-variant/40 block hover:opacity-90 transition-opacity">
                                        <img src="<?= $item_img ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>">
                                    </a>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                            <?php if($is_pharma): ?>
                                                <span class="bg-secondary-container/15 text-secondary-container px-1.5 py-0.5 rounded text-[10px] font-bold">💊 دارویی</span>
                                            <?php else: ?>
                                                <span class="bg-primary/10 text-primary px-1.5 py-0.5 rounded text-[10px] font-bold">🛍️ پت‌شاپ</span>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($item['is_autoship'])): ?>
                                                <span class="bg-status-active/15 text-status-active px-1.5 py-0.5 rounded text-[10px] font-bold">🔄 تحویل دوره‌ای</span>
                                            <?php endif; ?>
                                        </div>

                                        <a href="product_details.php?id=<?= $item['product_id'] ?>" class="text-xs font-bold text-primary hover:text-primary-container transition-colors truncate block">
                                            <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                        </a>

                                        <div class="mt-1 flex items-center justify-between gap-2 flex-wrap text-xs">
                                            <span class="text-on-surface-variant font-mono text-[11px]">
                                                <?= $item['quantity'] ?> عدد × <?= number_format($item['price_at_purchase']) ?> تومان
                                            </span>
                                            <button type="button" onclick="openRatingModal('product', <?= $item['product_id'] ?>, '<?= addslashes($item['product_name_snapshot']) ?>')" class="text-[11px] font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">star</span>
                                                امتیاز به کالا
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Order Footer with Total Amount -->
                    <div class="flex items-center justify-between pt-3 border-t border-outline-variant/20 text-xs">
                        <span class="text-on-surface-variant">مبلغ کل فاکتور:</span>
                        <span class="font-bold text-sm text-primary font-mono">
                            <span class="text-base text-emerald-700"><?= number_format($order['total_amount']) ?></span> تومان
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div>
<!-- Left Column: Subscriptions & Records -->
<div class="lg:col-span-4 space-y-8">
<!-- Subscriptions (Visual Autoship Widget) -->
<div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-gradient-to-r from-surface-container-low to-transparent">
        <h3 class="text-sm font-bold text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary-container">cached</span>
            اشتراک‌های فعال
        </h3>
        <span class="text-[11px] font-bold text-secondary-container bg-secondary-container/10 px-2 py-0.5 rounded-full border border-secondary-container/20 persian-number"><?= count($subscriptions) ?> فعال</span>
    </div>
    <div class="p-5 space-y-4">
        <?php if(empty($subscriptions)): ?>
            <div class="text-center py-4 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">all_inclusive</span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    با اشتراک‌های دوره‌ای، محصولات پت با <strong>۱۵٪ تخفیف مداوم</strong> و <strong>ارسال منظم</strong> تامین می‌شوند.
                </p>
                <a href="subscriptions.php" class="w-full inline-flex items-center justify-center gap-2 bg-secondary-container hover:bg-[#ea580c] text-white py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    خرید و فعال‌سازی اشتراک
                </a>
            </div>
        <?php else: ?>
            <?php foreach($subscriptions as $sub): ?>
                <div class="p-3.5 border border-outline-variant/60 rounded-2xl flex items-center gap-3 hover:border-secondary-container/50 transition-all group bg-white shadow-sm">
                    <div class="w-12 h-12 bg-secondary-container/15 text-secondary-container rounded-xl flex items-center justify-center shrink-0 border border-secondary-container/20">
                        <span class="material-symbols-outlined text-2xl">all_inclusive</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <h4 class="text-xs font-bold text-primary truncate"><?= htmlspecialchars($sub['plan_name']) ?></h4>
                            <span class="bg-status-active/15 text-status-active text-[10px] font-bold px-2 py-0.2 rounded-full shrink-0">فعال</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant font-mono"><?= number_format($sub['amount']) ?> تومان</p>
                        <div class="mt-1 flex items-center justify-between text-[10px]">
                            <span class="text-secondary-container font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">calendar_month</span>
                                نوبت: <?= $sub['next_delivery_date'] ? $fmtDateText->format(new DateTime($sub['next_delivery_date'])) : 'به‌زودی' ?>
                            </span>
                            <a href="#subscriptions-section" class="text-primary hover:underline font-bold">مدیریت</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="subscriptions.php" class="w-full border border-dashed border-outline-variant hover:border-secondary-container text-on-surface-variant hover:text-secondary-container py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all bg-surface-container-low/30 hover:bg-white">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                افزودن اشتراک جدید
            </a>
        <?php endif; ?>
    </div>
</div>
<!-- Pets Section -->
<div id="pets-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden mb-8 scroll-mt-24">
<div class="px-6 py-4 border-b border-outline-variant bg-white flex items-center justify-between">
<h3 class="text-sm font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined text-primary">pets</span>
حیوانات خانگی من
</h3>
</div>
<div class="p-6 space-y-4">
<?php if(empty($pets)): ?>
    <p class="text-sm text-on-surface-variant">هنوز حیوان خانگی ثبت نکرده‌اید.</p>
<?php else: ?>
    <?php foreach($pets as $pet): ?>
    <div class="flex items-center gap-4 p-3 border border-outline-variant rounded-xl hover:border-primary-container transition-all">
        <div class="w-12 h-12 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container">
            <span class="material-symbols-outlined"><?php echo $pet['type'] == 'گربه' ? 'cat' : 'dog'; ?></span>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-on-surface"><?php echo htmlspecialchars($pet['name']); ?></h4>
            <p class="text-[11px] text-on-surface-variant">
                <?php echo htmlspecialchars($pet['type']); ?> 
                <?php if(!empty($pet['race'])) echo ' • ' . htmlspecialchars($pet['race']); ?>
                <?php if(!empty($pet['gender'])) echo ' • ' . htmlspecialchars($pet['gender']); ?>
                <?php if(!empty($pet['age'])) echo ' • سن: ' . htmlspecialchars($pet['age']); ?>
            </p>
        </div>
        <div class="flex gap-2">
            <span onclick="openEditPetModal(<?php echo $pet['id']; ?>, '<?php echo addslashes(htmlspecialchars($pet['name'])); ?>', '<?php echo addslashes(htmlspecialchars($pet['type'])); ?>', '<?php echo addslashes(htmlspecialchars($pet['race'])); ?>', '<?php echo addslashes(htmlspecialchars($pet['gender'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($pet['age'] ?? '')); ?>')" class="material-symbols-outlined text-on-surface-variant cursor-pointer hover:text-primary">edit</span>
            <form action="actions/profile_action.php" method="POST" onsubmit="return confirm('آیا از حذف این حیوان خانگی اطمینان دارید؟');" class="inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="delete_pet">
                <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                <button type="submit" class="material-symbols-outlined text-on-surface-variant cursor-pointer hover:text-error bg-transparent border-0 p-0 m-0 flex items-center">delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<!-- Add New Pet Button/Form Area -->
<div class="pt-2">
<button onclick="document.getElementById('addPetModal').classList.remove('hidden')" class="w-full border-2 border-dashed border-outline-variant text-on-surface-variant py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-white hover:border-primary-container hover:text-primary transition-all group">
<span class="material-symbols-outlined group-hover:scale-110 transition-transform">add_circle</span>
                افزودن حیوان جدید
            </button>
</div>
</div>
</div><div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-outline-variant bg-white flex justify-between items-center">
<h3 class="text-lg font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined">description</span>
                            سوابق پزشکی <?php echo count($pets) > 0 ? htmlspecialchars(implode(' و ', array_column($pets, 'name'))) : ''; ?>
                        </h3>
<button onclick="document.getElementById('addDocModal').classList.remove('hidden')" class="text-sm font-bold text-primary flex items-center gap-1 hover:underline">
    <span class="material-symbols-outlined text-sm">add</span> آپلود
</button>
</div>
<div class="p-6 space-y-4">
<?php if(empty($documents)): ?>
    <p class="text-sm text-on-surface-variant">هیچ سندی آپلود نشده است.</p>
<?php else: ?>
    <?php foreach($documents as $doc): ?>
    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="group p-4 bg-surface-container-low rounded-2xl flex items-center justify-between cursor-pointer hover:bg-white hover:shadow-md border border-transparent hover:border-primary-container transition-all">
    <div class="flex items-center gap-4">
    <div class="p-3 bg-status-active/10 text-status-active rounded-xl group-hover:scale-105 transition-transform">
    <span class="material-symbols-outlined">description</span>
    </div>
    <div>
    <h4 class="text-sm font-bold text-on-surface"><?php echo htmlspecialchars($doc['title']); ?> - <?php echo htmlspecialchars($doc['pet_name']); ?></h4>
    <p class="text-[11px] text-on-surface-variant font-medium persian-number mt-0.5">آپلود شده در: <?php echo date('Y/m/d', strtotime($doc['uploaded_at'])); ?></p>
    </div>
    </div>
    <span class="material-symbols-outlined text-on-surface-variant group-hover:-translate-x-1 transition-transform">download</span>
    </a>
    <?php endforeach; ?>
<?php endif; ?>
<a href="download_all.php" class="w-full bg-primary-container text-white py-4 rounded-2xl font-bold text-sm flex items-center justify-center gap-3 hover:shadow-xl transition-all shadow-lg shadow-primary-container/20">
<span class="material-symbols-outlined">download</span>
                            دریافت پرونده کامل سلامت (ZIP)
                        </a>
</div>
</div>
</div>
</div>
</div>
</main>

<!-- Add Pet Modal -->
<div id="addPetModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <button onclick="document.getElementById('addPetModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">ثبت حیوان جدید</h2>
        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_pet">
            <div>
                <label class="block text-sm font-bold mb-1">نام حیوان</label>
                <input type="text" name="pet_name" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نوع حیوان</label>
                <select name="pet_type" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                    <option value="">انتخاب کنید...</option>
                    <option value="سگ">سگ</option>
                    <option value="گربه">گربه</option>
                    <option value="پرنده">پرنده</option>
                    <option value="جونده">جونده</option>
                    <option value="سایر">سایر</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نژاد (اختیاری)</label>
                <input type="text" name="pet_race" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-1">جنسیت</label>
                    <select name="pet_gender" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                        <option value="">نامشخص</option>
                        <option value="نر">نر</option>
                        <option value="ماده">ماده</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">سن</label>
                    <input type="text" name="pet_age" placeholder="مثال: ۲ سال" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold mt-4 hover:bg-primary transition-colors">ثبت مشخصات</button>
        </form>
    </div>
</div>

<!-- Edit Pet Modal -->
<div id="editPetModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <button onclick="document.getElementById('editPetModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">ویرایش حیوان خانگی</h2>
        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_pet">
            <input type="hidden" name="pet_id" id="edit_pet_id" value="">
            <div>
                <label class="block text-sm font-bold mb-1">نام حیوان</label>
                <input type="text" name="pet_name" id="edit_pet_name" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نوع حیوان</label>
                <select name="pet_type" id="edit_pet_type" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                    <option value="">انتخاب کنید...</option>
                    <option value="سگ">سگ</option>
                    <option value="گربه">گربه</option>
                    <option value="پرنده">پرنده</option>
                    <option value="جونده">جونده</option>
                    <option value="سایر">سایر</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نژاد (اختیاری)</label>
                <input type="text" name="pet_race" id="edit_pet_race" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-1">جنسیت</label>
                    <select name="pet_gender" id="edit_pet_gender" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                        <option value="">نامشخص</option>
                        <option value="نر">نر</option>
                        <option value="ماده">ماده</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">سن</label>
                    <input type="text" name="pet_age" id="edit_pet_age" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold mt-4 hover:bg-primary transition-colors">ذخیره تغییرات</button>
        </form>
    </div>
</div>

<!-- Add Document Modal -->
<div id="addDocModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <button onclick="document.getElementById('addDocModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">آپلود سند جدید</h2>
        <form action="actions/profile_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="upload_document">
            <div>
                <label class="block text-sm font-bold mb-1">حیوان مربوطه</label>
                <select name="pet_id" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                    <option value="">انتخاب کنید...</option>
                    <?php foreach($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>"><?php echo htmlspecialchars($pet['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">عنوان سند (مانند: واکسن هاری)</label>
                <input type="text" name="doc_title" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">انتخاب فایل (PDF, JPG, PNG)</label>
                <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full border border-outline-variant rounded-lg p-2 text-sm">
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold mt-4 hover:bg-primary transition-colors">آپلود فایل</button>
        </form>
    </div>
</div>
<!-- Floating Chat Button -->
<a href="chat.php" class="fixed bottom-8 left-8 w-14 h-14 bg-primary-container text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50 group">
<span class="material-symbols-outlined text-[28px]">chat_bubble</span>
<span class="absolute left-16 bg-white text-primary-container px-4 py-2 rounded-xl shadow-xl border border-outline-variant font-bold text-sm opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
        پشتیبانی آنلاین آسنا
    </span>
</a>
<!-- Rating & Review Micro-Modal (Non-intrusive) -->
<div id="ratingModal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button onclick="closeRatingModal()" class="absolute top-6 left-6 text-on-surface-variant hover:text-error transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">rate_review</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-primary line-clamp-1" id="ratingModalTitle">ثبت نظر و امتیاز</h3>
                <p class="text-xs text-status-active font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    خریدار تاییدشده • ۵ امتیاز وفاداری هدیه
                </p>
            </div>
        </div>

        <form id="ratingForm" onsubmit="submitRating(event)" class="space-y-4">
            <input type="hidden" id="ratingTargetType" name="target_type" value="product">
            <input type="hidden" id="ratingTargetId" name="target_id" value="0">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <!-- Interactive 5-Star Selector -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">امتیاز شما به این مورد:</label>
                <div class="flex items-center justify-center gap-2 py-2 text-status-warning" id="starSelector">
                    <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" onclick="setRatingStars(<?php echo $i; ?>)" class="star-btn transition-transform hover:scale-125 focus:outline-none cursor-pointer" data-val="<?php echo $i; ?>">
                        <span class="material-symbols-outlined text-3xl select-none" id="star-icon-<?php echo $i; ?>">star</span>
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="ratingScore" name="rating" value="5">
                <p class="text-center text-xs text-on-surface-variant font-bold mt-1" id="ratingScoreText">عالی (۵ ستاره)</p>
            </div>

            <!-- Comment Box -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">تجربه یا نظر شما (اختیاری):</label>
                <textarea name="comment" id="ratingComment" rows="3" placeholder="کیفیت، اثربخشی، بسته‌بندی یا نحوه پاسخگویی..." class="w-full text-xs p-3 border border-outline-variant rounded-xl outline-none focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <button type="submit" id="ratingSubmitBtn" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm hover:bg-primary-container transition-all flex items-center justify-center gap-2">
                <span>ثبت امتیاز و دریافت ۵ امتیاز هدیه</span>
            </button>
        </form>
    </div>
</div>

<script>
    function openRatingModal(type, id, title) {
        document.getElementById('ratingTargetType').value = type;
        document.getElementById('ratingTargetId').value = id;
        document.getElementById('ratingModalTitle').textContent = 'امتیاز به ' + title;
        setRatingStars(5);
        document.getElementById('ratingComment').value = '';
        document.getElementById('ratingModal').classList.remove('hidden');
    }

    function closeRatingModal() {
        document.getElementById('ratingModal').classList.add('hidden');
    }

    function setRatingStars(score) {
        document.getElementById('ratingScore').value = score;
        const labels = { 1: 'خیلی ضعیف (۱ ستاره)', 2: 'ضعیف (۲ ستاره)', 3: 'متوسط (۳ ستاره)', 4: 'خوب (۴ ستاره)', 5: 'عالی (۵ ستاره)' };
        document.getElementById('ratingScoreText').textContent = labels[score] || (score + ' ستاره');
        
        for (let i = 1; i <= 5; i++) {
            const icon = document.getElementById('star-icon-' + i);
            if (i <= score) {
                icon.textContent = 'star';
                icon.classList.add('text-status-warning');
                icon.classList.remove('text-outline-variant');
            } else {
                icon.textContent = 'star';
                icon.classList.remove('text-status-warning');
                icon.classList.add('text-outline-variant');
            }
        }
    }

    async function submitRating(e) {
        e.preventDefault();
        const btn = document.getElementById('ratingSubmitBtn');
        const form = document.getElementById('ratingForm');
        const formData = new FormData(form);
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> در حال ثبت...';
        
        try {
            const resp = await fetch('actions/review_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.status === 'success') {
                alert(data.message);
                closeRatingModal();
                location.reload();
            } else {
                alert(data.message || 'خطا در ثبت امتیاز');
            }
        } catch (err) {
            alert('خطا در برقراری ارتباط با سرور.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>ثبت امتیاز و دریافت ۵ امتیاز هدیه</span>';
        }
    }

    function openEditPetModal(id, name, type, race, gender, age) {
        document.getElementById('edit_pet_id').value = id;
        document.getElementById('edit_pet_name').value = name;
        document.getElementById('edit_pet_type').value = type;
        document.getElementById('edit_pet_race').value = race;
        document.getElementById('edit_pet_gender').value = gender;
        document.getElementById('edit_pet_age').value = age;
        document.getElementById('editPetModal').classList.remove('hidden');
    }

    window.addEventListener('load', () => {
        document.querySelectorAll('.glass-card, .rounded-2xl').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.6s cubic-bezier(0.22, 1, 0.36, 1)';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    function toggleProfileSidebar() {
        const sidebar = document.getElementById('profile-sidebar');
        const backdrop = document.getElementById('profile-backdrop');
        
        if (sidebar.classList.contains('translate-x-full')) {
            sidebar.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        }
    }
</script>
</body></html>