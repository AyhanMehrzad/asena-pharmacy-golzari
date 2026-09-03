<?php
require_once '../includes/db.php';

// Route Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$adminCheck = $stmt->fetch();
if (!$adminCheck || $adminCheck['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
$adminName = $adminCheck['name'] ?? 'مدیر سیستم';
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>پنل مدیریت آسنا - ASENA Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "surface-variant": "#e2e2e2",
                    "surface-container-high": "#e8e8e8",
                    "secondary-container": "#fd8100",
                    "tertiary": "#001f31",
                    "on-primary-container": "#7a97e2",
                    "on-tertiary-fixed": "#001e2f",
                    "primary": "#001a48",
                    "on-error": "#ffffff",
                    "outline-variant": "#c4c6d2",
                    "outline": "#747782",
                    "primary-fixed-dim": "#b1c5ff",
                    "tertiary-fixed": "#cae6ff",
                    "on-error-container": "#93000a",
                    "on-secondary-fixed": "#301400",
                    "surface-tint": "#3d5ca2",
                    "surface-container-lowest": "#ffffff",
                    "status-paused": "#757575",
                    "error": "#ba1a1a",
                    "tertiary-container": "#133449",
                    "surface": "#f9f9f9",
                    "inverse-surface": "#2f3131",
                    "on-secondary": "#ffffff",
                    "secondary": "#954a00",
                    "surface-dim": "#dadada",
                    "primary-container": "#002d72",
                    "secondary-fixed": "#ffdcc6",
                    "on-secondary-fixed-variant": "#723700",
                    "on-primary": "#ffffff",
                    "on-surface-variant": "#444651",
                    "status-warning": "#FFC60A",
                    "on-secondary-container": "#5d2c00",
                    "surface-container": "#eeeeee",
                    "on-tertiary": "#ffffff",
                    "secondary-fixed-dim": "#ffb785",
                    "on-background": "#1a1c1c",
                    "tertiary-fixed-dim": "#abcae5",
                    "surface-alt": "#F8F9FA",
                    "on-surface": "#1a1c1c",
                    "on-primary-fixed": "#001946",
                    "status-active": "#2E7D32",
                    "on-tertiary-container": "#7f9db6",
                    "surface-container-highest": "#e2e2e2",
                    "inverse-primary": "#b1c5ff",
                    "error-container": "#ffdad6",
                    "on-tertiary-fixed-variant": "#2c4a60",
                    "on-primary-fixed-variant": "#224489",
                    "inverse-on-surface": "#f0f1f1",
                    "surface-container-low": "#f3f3f4",
                    "background": "#f9f9f9",
                    "surface-bright": "#f9f9f9",
                    "primary-fixed": "#dae2ff"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "fontFamily": {
                    "body-lg": ["Geist"],
                    "label-lg": ["Geist"],
                    "body-md": ["Geist"],
                    "headline-lg-mobile": ["Geist"],
                    "title-lg": ["Geist"],
                    "headline-lg": ["Geist"],
                    "headline-md": ["Geist"],
                    "label-sm": ["Geist"],
                    "display-lg": ["Geist"]
            }
          },
        },
      }
    </script>
    <style>
        body { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .stat-card-shadow {
            box-shadow: 0px 4px 12px rgba(0, 45, 114, 0.08);
        }
        .rtl { direction: rtl; }
    </style>
</head>
<body class="bg-surface text-on-surface selection:bg-secondary-container/30">

<!-- Mobile Backdrop -->
<div id="admin-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleAdminSidebar()"></div>

<!-- SideNavBar -->
<aside id="admin-sidebar" class="fixed inset-y-0 right-0 w-64 bg-tertiary dark:bg-tertiary-container flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">pets</span>
                </div>
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">کنسول مدیریت</p>
                </div>
            </div>
            <button onclick="toggleAdminSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <a href="../blog_editor.php" class="w-full bg-gradient-to-r from-blue-600 to-primary hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-2.5 px-3 rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-xs my-2">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>+ ایجاد و نگارش مقاله جدید</span>
        </a>
    </div>

    <nav class="flex-1 px-3 mt-2 space-y-4">
        <?php 
        $currentFile = basename($_SERVER['PHP_SELF']);
        
        // Auto-detect active page key intelligently based on current file and $currentPage
        $activeKey = $currentPage ?? '';
        if ($currentFile === 'subscriptions.php' || $currentFile === 'subscription_details.php' || $activeKey === 'user_subscriptions') {
            $activeKey = 'subscriptions';
        } elseif ($currentFile === 'index.php' || $currentFile === 'dashboard.php') {
            $activeKey = 'dashboard';
        } elseif ($currentFile === 'orders.php' || $currentFile === 'export_orders.php') {
            $activeKey = 'orders';
        } elseif ($currentFile === 'inventory.php') {
            $activeKey = 'inventory';
        } elseif ($currentFile === 'clinic_management.php') {
            $activeKey = 'clinic';
        } elseif ($currentFile === 'user_management.php' || $currentFile === 'user_details.php') {
            $activeKey = 'users';
        } elseif ($currentFile === 'tickets.php') {
            $activeKey = 'tickets';
        } elseif ($currentFile === 'campaigns.php') {
            $activeKey = 'campaigns';
        } elseif ($currentFile === 'donations.php') {
            $activeKey = 'donations';
        } elseif ($currentFile === 'calendar_notes.php') {
            $activeKey = 'calendar_notes';
        } elseif ($currentFile === 'analytics.php') {
            $activeKey = 'analytics';
        } elseif ($currentFile === 'sms_settings.php') {
            $activeKey = 'sms_settings';
        } elseif ($currentFile === 'blogs.php') {
            $activeKey = 'blogs';
        } elseif ($currentFile === 'guide.php') {
            $activeKey = 'guide';
        }

        $navSections = [
            'مدیریت و پیشخوان' => [
                'dashboard' => ['icon' => 'dashboard', 'title' => 'پیشخوان مدیریت', 'url' => 'index.php'],
                'analytics' => ['icon' => 'analytics', 'title' => 'تحلیل و آمار', 'url' => 'analytics.php'],
                'calendar_notes' => ['icon' => 'calendar_month', 'title' => 'تقویم کاری و یادداشت‌ها', 'url' => 'calendar_notes.php'],
            ],
            'فروشگاه و اشتراک' => [
                'orders' => ['icon' => 'local_shipping', 'title' => 'سفارشات و ارسال', 'url' => 'orders.php'],
                'inventory' => ['icon' => 'inventory_2', 'title' => 'انبار و فروشگاه', 'url' => 'inventory.php'],
                'subscriptions' => ['icon' => 'event_repeat', 'title' => 'مدیریت اشتراک‌ها (Autoship)', 'url' => 'subscriptions.php'],
                'recommendations' => ['icon' => 'auto_awesome', 'title' => 'پیشنهادات و بنرها', 'url' => 'recommendations.php'],
            ],
            'کلینیک و خدمات' => [
                'clinic' => ['icon' => 'medical_services', 'title' => 'مدیریت کلینیک و پزشکان', 'url' => 'clinic_management.php'],
                'donations' => ['icon' => 'volunteer_activism', 'title' => 'گزارش کمک‌های خیریه', 'url' => 'donations.php'],
            ],
            'کاربران و پشتیبانی' => [
                'users' => ['icon' => 'group', 'title' => 'مدیریت کاربران', 'url' => 'user_management.php'],
                'tickets' => ['icon' => 'support_agent', 'title' => 'تیکت و پشتیبانی', 'url' => 'tickets.php'],
                'campaigns' => ['icon' => 'campaign', 'title' => 'مدیریت کمپین‌ها', 'url' => 'campaigns.php'],
                'sms_settings' => ['icon' => 'sms', 'title' => 'تنظیمات پیامک و اعلان', 'url' => 'sms_settings.php'],
            ],
            'محتوا و راهنما' => [
                'blogs' => ['icon' => 'edit_note', 'title' => 'مدیریت وبلاگ و مقالات', 'url' => 'blogs.php'],
                'blog' => ['icon' => 'auto_stories', 'title' => 'مشاهده پایگاه دانش', 'url' => '../knowledge_base.php', 'external' => true],
                'guide' => ['icon' => 'menu_book', 'title' => 'راهنمای پنل ادمین', 'url' => 'guide.php'],
            ]
        ];

        foreach ($navSections as $sectionTitle => $items):
        ?>
        <div class="space-y-1">
            <div class="px-3 pt-2 pb-1 text-[11px] font-extrabold uppercase tracking-wider text-on-tertiary-container/50 flex items-center gap-1.5">
                <span><?= $sectionTitle ?></span>
                <div class="flex-1 h-[1px] bg-white/5"></div>
            </div>
            <?php foreach ($items as $key => $item): 
                $isActive = ($activeKey === $key);
                $classes = $isActive 
                    ? "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-secondary-container font-bold border-r-4 border-secondary-container bg-white/10 shadow-sm transition-all"
                    : "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
            ?>
            <a class="<?= $classes ?>" href="<?= $item['url'] ?>" <?= !empty($item['external']) ? 'target="_blank"' : '' ?>>
                <span class="material-symbols-outlined text-[20px] <?= $isActive ? 'text-secondary-container' : '' ?>"><?= $item['icon'] ?></span>
                <span class="text-xs font-bold leading-tight"><?= $item['title'] ?></span>
                <?php if (!empty($item['external'])): ?>
                    <span class="material-symbols-outlined text-xs mr-auto opacity-70">north_east</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-white/10">
        <a href="../index.php" class="w-full bg-secondary-container text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:translate-x-1 duration-200">
            <span class="material-symbols-outlined">storefront</span>
            <span class="font-label-lg text-label-lg">بازگشت به سایت</span>
        </a>
        
        <div class="mt-4 space-y-1">
            <a class="flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all" href="../logout.php">
                <span class="material-symbols-outlined text-error">logout</span>
                <span class="font-label-sm text-label-sm">خروج از حساب</span>
            </a>
        </div>
    </div>
</aside>

<!-- Main Content Wrapper -->
<main class="lg:mr-64 mr-0 min-h-screen transition-all duration-300">
    <!-- TopAppBar -->
    <header class="sticky top-0 z-40 flex justify-between items-center h-16 px-4 lg:px-6 bg-surface dark:bg-surface-dim shadow-sm border-b border-outline-variant/20">
        <div class="flex items-center gap-2 lg:gap-6 flex-1">
            <button onclick="toggleAdminSidebar()" class="lg:hidden w-10 h-10 flex shrink-0 items-center justify-center rounded-lg hover:bg-surface-container transition-colors text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="relative w-full max-w-sm hidden md:block">
                <span class="absolute inset-y-0 right-3 flex items-center text-outline">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input class="w-full pr-10 pl-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="جستجو در پرونده‌ها، موجودی یا تراکنش‌ها..." type="text"/>
            </div>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="../blog_editor.php" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-sm transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ ایجاد مقاله جدید</span>
            </a>
            <a href="blogs.php" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800 hover:bg-primary hover:text-white transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">edit_note</span>
                <span>مدیریت مقالات</span>
            </a>
            <a href="../knowledge_base.php" target="_blank" class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">auto_stories</span>
                <span>پایگاه دانش</span>
                <span class="material-symbols-outlined text-xs">north_east</span>
            </a>
            <a href="guide.php" class="hidden lg:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">help_outline</span>
                <span>راهنما</span>
            </a>
            <div class="h-8 w-[1px] bg-outline-variant mx-1"></div>
            <div class="flex items-center gap-3 pl-2">
                <div class="text-left">
                    <p class="font-label-lg text-label-lg text-on-surface leading-tight"><?= htmlspecialchars($adminName) ?></p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant">مدیر سیستم</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center overflow-hidden">
                    <span class="material-symbols-outlined">shield_person</span>
                </div>
            </div>
        </div>
    </header>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('admin-backdrop');
    
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
