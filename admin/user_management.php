<?php
$currentPage = 'users';
require_once 'includes/admin_header.php';

// Fetch users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Statistics
$totalUsers = count($users);
$adminUsers = count(array_filter($users, fn($u) => $u['role'] === 'admin'));

// Premium Users (having active subscription)
$subStmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active'");
$premiumUsers = $subStmt->fetchColumn();

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
            <p class="font-body-md text-body-md text-on-surface-variant">بررسی و مدیریت کاربران ثبت‌نامی و سطوح دسترسی</p>
        </div>
    </div>

    <!-- Bento Grid Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">کل کاربران</p>
                <p class="font-headline-md text-headline-md text-primary"><?= number_format($totalUsers) ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined">workspace_premium</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">کاربران دارای اشتراک</p>
                <p class="font-headline-md text-headline-md text-secondary"><?= number_format($premiumUsers) ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-tertiary-fixed flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined">verified_user</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">تعداد مدیران</p>
                <p class="font-headline-md text-headline-md text-tertiary"><?= number_format($adminUsers) ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-outline-variant/30 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-error-container flex items-center justify-center text-error">
                <span class="material-symbols-outlined">person_off</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant">کاربران مسدود شده</p>
                <p class="font-headline-md text-headline-md text-error">۰</p>
            </div>
        </div>
    </div>

    <!-- Main Layout: User Table & Activity Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Registered User List -->
        <div class="lg:col-span-8 bg-white rounded-3xl shadow-sm border border-outline-variant/30 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center">
                <h3 class="font-title-lg text-title-lg">لیست کاربران</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead>
                        <tr class="bg-surface-container-lowest border-b border-outline-variant/10">
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant">پروفایل کاربر</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant">شماره تماس</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant">نقش</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant">امتیاز وفاداری</th>
                            <th class="px-6 py-4 font-label-lg text-on-surface-variant">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-surface-alt transition-colors group cursor-pointer" onclick="window.location.href='user_details.php?id=<?= $user['id'] ?>'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-primary-container/10 flex items-center justify-center flex-shrink-0 text-primary">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-label-lg text-primary"><?= htmlspecialchars($user['name'] ?: 'کاربر جدید') ?></p>
                                        </div>
                                        <p class="font-label-sm text-on-surface-variant text-[11px]"><?= htmlspecialchars($user['email'] ?: 'بدون ایمیل') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-body-md" dir="ltr"><?= htmlspecialchars($user['phone']) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-[12px]">مدیر سیستم</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-sm text-[12px]">کاربر عادی</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-secondary font-bold">
                                <?= number_format($user['loyalty_points']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="user_details.php?id=<?= $user['id'] ?>" class="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:bg-surface-container-highest transition-colors" title="مشاهده جزئیات">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Account Activity Logs (Sidebar Layout) -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/30 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-title-lg text-title-lg">فعالیت‌های اخیر سیستم</h3>
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
                            <p class="font-label-lg text-primary"><?= $log['title'] ?> <span class="font-body-md text-on-surface-variant">توسط <?= $log['user'] ?></span></p>
                            <p class="font-label-sm text-on-surface-variant opacity-60"><?= timeAgo($log['timestamp']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Subscription Quick Actions Card -->
            <div class="bg-primary-container text-white rounded-3xl p-6 relative overflow-hidden">
                <div class="relative z-10">
                    <h4 class="font-title-lg text-title-lg mb-2">مدیریت اشتراک‌ها</h4>
                    <p class="font-body-md mb-6 opacity-80">مدیریت سرویس‌های دوره‌ای و اتوشیپ برای کاربران ویژه.</p>
                    <div class="grid grid-cols-1 gap-3">
                        <a href="orders.php" class="text-center py-2 bg-secondary-container text-white rounded-lg font-label-lg transition-all hover:scale-105 block">بررسی سفارشات شارژ خودکار</a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
