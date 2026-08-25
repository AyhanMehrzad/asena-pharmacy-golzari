<?php
require_once '../includes/db.php';

// Route Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$docCheck = $stmt->fetch();
if (!$docCheck || $docCheck['role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}
$doctorName = $docCheck['name'] ?: 'پزشک گرامی';

// Also fetch doctor profile info
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorProfile = $stmt->fetch();

// If they have the role but no profile (e.g. manually assigned via user management), create a default profile
if (!$doctorProfile) {
    $stmt = $pdo->prepare("INSERT INTO doctors (user_id, name, specialty, price) VALUES (?, ?, 'پزشک عمومی', 150000)");
    $stmt->execute([$_SESSION['user_id'], $doctorName]);
    
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctorProfile = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>پنل پزشکان آسنا - ASENA Doctor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <script src="../assets/js/tailwind-config.js"></script>
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
<div id="doctor-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleDoctorSidebar()"></div>

<!-- SideNavBar -->
<aside id="doctor-sidebar" class="fixed inset-y-0 right-0 w-64 bg-tertiary dark:bg-tertiary-container flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">medical_services</span>
                </div>
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">پنل پزشکان</p>
                </div>
            </div>
            <button onclick="toggleDoctorSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    <nav class="flex-1 px-4 mt-4 space-y-1">
        <?php 
        $page = $currentPage ?? 'dashboard';
        
        $navItems = [
            'dashboard' => ['icon' => 'dashboard', 'title' => 'پیشخوان پزشک', 'url' => 'index.php'],
        ];

        foreach ($navItems as $key => $item):
            $isActive = ($page === $key);
            $classes = $isActive 
                ? "flex items-center gap-3 px-4 py-3 text-secondary-container font-bold border-r-4 border-secondary-container bg-white/5 transition-all"
                : "flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
        ?>
        <a class="<?= $classes ?>" href="<?= $item['url'] ?>">
            <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
            <span class="font-label-lg text-label-lg"><?= $item['title'] ?></span>
        </a>
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
        <div class="flex items-center gap-2 lg:gap-6">
            <button onclick="toggleDoctorSidebar()" class="lg:hidden w-10 h-10 flex shrink-0 items-center justify-center rounded-lg hover:bg-surface-container transition-colors text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="h-8 w-[1px] bg-outline-variant mx-2"></div>
            <div class="flex items-center gap-3 pl-2">
                <div class="text-left">
                    <p class="font-label-lg text-label-lg text-on-surface leading-tight">دکتر <?= htmlspecialchars($doctorName) ?></p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($doctorProfile['specialty']) ?></p>
                </div>
                <div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden bg-primary-container text-white flex items-center justify-center">
                    <?php if(!empty($doctorProfile['image_url'])): ?>
                        <img src="<?= htmlspecialchars(str_starts_with($doctorProfile['image_url'], 'http') ? $doctorProfile['image_url'] : '../' . ltrim($doctorProfile['image_url'], '/')) ?>" class="w-full h-full object-cover" alt="Profile">
                    <?php else: ?>
                        <span class="material-symbols-outlined">person</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

<script>
function toggleDoctorSidebar() {
    const sidebar = document.getElementById('doctor-sidebar');
    const backdrop = document.getElementById('doctor-backdrop');
    
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
