<?php
$currentPage = 'users';
require_once 'includes/admin_header.php';

// Search & Role Filters
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? 'all');

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($role_filter !== 'all' && in_array($role_filter, ['admin', 'doctor', 'user'])) {
    $whereClauses[] = "role = ?";
    $params[] = $role_filter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Counts for Badges & Cards
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$adminUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$doctorUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$regularUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// Premium Users (having active subscription)
$subStmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active'");
$premiumUsers = (int)$subStmt->fetchColumn();

// Activity logs
$logs = [];

// 1. Fetch latest users
$stmtUsers = $pdo->query("SELECT id, name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
while ($row = $stmtUsers->fetch()) {
    $logs[] = [
        'icon' => 'person_add',
        'color' => 'bg-status-active',
        'title' => 'ثبت نام کاربر جدید',
        'user' => $row['name'] ?: 'کاربر جدید',
        'timestamp' => strtotime($row['created_at'])
    ];
}

// 2. Fetch latest orders
$stmtOrders = $pdo->query("SELECT o.id, o.created_at, u.name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
while ($row = $stmtOrders->fetch()) {
    $logs[] = [
        'icon' => 'shopping_cart',
        'color' => 'bg-secondary-container',
        'title' => 'ثبت سفارش جدید',
        'user' => $row['name'] ?: 'کاربر نامشخص',
        'timestamp' => strtotime($row['created_at'])
    ];
}

// 3. Fetch latest appointments
$stmtApps = $pdo->query("SELECT a.id, a.created_at, u.name FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
while ($row = $stmtApps->fetch()) {
    $logs[] = [
        'icon' => 'calendar_today',
        'color' => 'bg-primary-container',
        'title' => 'رزرو نوبت جدید',
        'user' => $row['name'] ?: 'کاربر نامشخص',
        'timestamp' => strtotime($row['created_at'])
    ];
}

// Sort by timestamp descending
usort($logs, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Take top 5 logs
$logs = array_slice($logs, 0, 5);

// Helper function for Persian time ago
function timeAgo($timestamp) {
    $seconds = time() - $timestamp;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    
    if ($seconds <= 60) return "همین الان";
    else if ($minutes <= 60) return "$minutes دقیقه پیش";
    else if ($hours <= 24) return "$hours ساعت پیش";
    else return "$days روز پیش";
}
?>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Header Section -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-1">مدیریت کاربران</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">بررسی، فیلتر نقش‌ها و جستجوی کاربران سیستم</p>
        </div>
    </div>

    <!-- Bento Grid Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <a href="?role=all" class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">کل کاربران</p>
                <p class="font-headline-md text-headline-md text-primary"><?= number_format($totalUsers) ?></p>
            </div>
        </a>
        
        <a href="?role=doctor" class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-full bg-secondary-container/15 flex items-center justify-center text-secondary-container">
                <span class="material-symbols-outlined">stethoscope</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">پزشکان و دامپزشکان</p>
                <p class="font-headline-md text-headline-md text-secondary-container"><?= number_format($doctorUsers) ?></p>
            </div>
        </a>
        
        <a href="?role=admin" class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-full bg-tertiary-fixed flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined">verified_user</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">تعداد مدیران</p>
                <p class="font-headline-md text-headline-md text-tertiary"><?= number_format($adminUsers) ?></p>
            </div>
        </a>
        
        <a href="?role=user" class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface">
                <span class="material-symbols-outlined">person</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">کاربران عادی</p>
                <p class="font-headline-md text-headline-md text-on-surface"><?= number_format($regularUsers) ?></p>
            </div>
        </a>
    </div>

    <!-- Main Layout: User Table & Activity Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Registered User List with Filters & Search -->
        <div class="lg:col-span-8 bg-white rounded-3xl shadow-sm border border-outline-variant/30 overflow-hidden flex flex-col">
            
            <!-- Table Header Controls: Search & Filter Tabs -->
            <div class="p-6 border-b border-outline-variant/20 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 bg-surface-container-lowest">
                
                <!-- Role Filter Tabs (admin-doctor-user) -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 md:pb-0">
                    <a href="?role=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $role_filter === 'all' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                        همه (<?= $totalUsers ?>)
                    </a>
                    <a href="?role=admin<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 <?= $role_filter === 'admin' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                        <span>مدیران (<?= $adminUsers ?>)</span>
                        <span>🛡️</span>
                    </a>
                    <a href="?role=doctor<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 <?= $role_filter === 'doctor' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                        <span>پزشکان (<?= $doctorUsers ?>)</span>
                        <span>🩺</span>
                    </a>
                    <a href="?role=user<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 <?= $role_filter === 'user' ? 'bg-primary text-white shadow-md' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant' ?>">
                        <span>کاربران عادی (<?= $regularUsers ?>)</span>
                        <span>👤</span>
                    </a>
                </div>

                <!-- Search Bar -->
                <form method="GET" class="flex items-center gap-2 min-w-[280px]">
                    <input type="hidden" name="role" value="<?= htmlspecialchars($role_filter) ?>">
                    <div class="relative flex-1">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                        <input type="text" 
                               name="search" 
                               id="userSearchInput" 
                               value="<?= htmlspecialchars($search) ?>" 
                               oninput="liveFilterUsers(this.value)"
                               placeholder="جستجوی نام، موبایل، ایمیل..." 
                               class="w-full pr-10 pl-8 py-2 bg-white rounded-xl border border-outline-variant/40 text-xs outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <?php if(!empty($search)): ?>
                            <a href="?role=<?= htmlspecialchars($role_filter) ?>" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-error transition-colors" title="پاک کردن جستجو">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl font-bold text-xs hover:bg-primary-container transition-all shrink-0">
                        جستجو
                    </button>
                </form>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-right" id="usersTable">
                    <thead>
                        <tr class="bg-surface-container-lowest border-b border-outline-variant/10">
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant text-xs">پروفایل کاربر</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant text-xs">شماره تماس</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant text-xs">نقش دسترسی</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant text-xs">امتیاز وفاداری</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant text-xs">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <?php if(empty($users)): ?>
                        <tr id="emptyRow">
                            <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">person_search</span>
                                    <p class="font-bold text-sm">هیچ کاربری با این مشخصات یافت نشد.</p>
                                    <a href="user_management.php" class="text-xs text-primary font-bold hover:underline mt-1">مشاهده همه کاربران</a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-surface-alt transition-colors group cursor-pointer user-row" 
                                data-search="<?= htmlspecialchars(mb_strtolower($user['name'] . ' ' . $user['phone'] . ' ' . $user['email'])) ?>"
                                onclick="window.location.href='user_details.php?id=<?= $user['id'] ?>'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-primary-container/10 flex items-center justify-center flex-shrink-0 text-primary font-bold text-sm">
                                            <?= mb_substr($user['name'] ?: 'ک', 0, 1, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <p class="font-label-lg text-primary font-bold text-sm"><?= htmlspecialchars($user['name'] ?: 'کاربر جدید') ?></p>
                                            <p class="font-label-sm text-on-surface-variant text-[11px]"><?= htmlspecialchars($user['email'] ?: 'بدون ایمیل') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-body-md text-xs" dir="ltr"><?= htmlspecialchars($user['phone'] ?: '-') ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="px-3 py-1 rounded-full bg-primary text-white font-bold text-[11px] flex items-center gap-1 w-fit shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">shield</span>
                                            مدیر سیستم
                                        </span>
                                    <?php elseif ($user['role'] === 'doctor'): ?>
                                        <span class="px-3 py-1 rounded-full bg-secondary-container/15 text-secondary-container font-bold text-[11px] flex items-center gap-1 w-fit border border-secondary-container/20">
                                            <span class="material-symbols-outlined text-[14px]">stethoscope</span>
                                            پزشک / دامپزشک
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-bold text-[11px] flex items-center gap-1 w-fit">
                                            <span class="material-symbols-outlined text-[14px]">person</span>
                                            کاربر عادی
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-secondary font-bold text-sm">
                                    <?= number_format($user['loyalty_points']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="user_details.php?id=<?= $user['id'] ?>" class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest hover:text-primary transition-colors" title="مشاهده جزئیات">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Account Activity Logs (Sidebar Layout) -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/30 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-title-lg text-title-lg text-primary font-bold">فعالیت‌های اخیر سیستم</h3>
                </div>
                <div class="space-y-6 relative">
                    <!-- Timeline Line -->
                    <div class="absolute right-[11px] top-2 bottom-2 w-0.5 bg-outline-variant/30"></div>
                    
                    <?php foreach ($logs as $log): ?>
                    <div class="relative flex gap-4 pr-8">
                        <div class="absolute right-0 top-1 w-6 h-6 rounded-full <?= $log['color'] ?> border-4 border-white z-10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-[10px]"><?= $log['icon'] ?></span>
                        </div>
                        <div class="flex-1">
                            <p class="font-label-lg text-primary text-xs font-bold"><?= $log['title'] ?> <span class="text-on-surface-variant font-normal">توسط <?= $log['user'] ?></span></p>
                            <p class="font-label-sm text-on-surface-variant text-[10px] opacity-70"><?= timeAgo($log['timestamp']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Subscription Quick Actions Card -->
            <div class="bg-primary-container text-white rounded-3xl p-6 relative overflow-hidden shadow-md">
                <div class="relative z-10">
                    <h4 class="font-title-lg text-title-lg mb-2 font-bold">مدیریت اشتراک‌ها</h4>
                    <p class="font-body-md text-xs mb-6 opacity-80 leading-relaxed">مدیریت سرویس‌های دوره‌ای، پلن‌های اشتراک و تحویل خودکار (Autoship).</p>
                    <div class="grid grid-cols-1 gap-3">
                        <a href="orders.php" class="text-center py-2.5 bg-secondary-container hover:bg-secondary text-white rounded-xl font-bold text-xs transition-all shadow-sm block">
                            بررسی سفارشات شارژ خودکار
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
function liveFilterUsers(query) {
    const term = query.toLowerCase().trim();
    const rows = document.querySelectorAll('.user-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (text.includes(term)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
