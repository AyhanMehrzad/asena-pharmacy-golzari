<?php
require_once __DIR__ . '/db.php';

// Monthly Loyalty Points Check & Role Refresh
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT last_monthly_points_date, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_pts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_pts) {
        $_SESSION['user_role'] = $user_pts['role'];
        $current_month = date('Y-m');
        $last_month = $user_pts['last_monthly_points_date'] ? date('Y-m', strtotime($user_pts['last_monthly_points_date'])) : '';
        
        if ($current_month !== $last_month) {
            $update_stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 20, last_monthly_points_date = CURDATE() WHERE id = ?");
            $update_stmt->execute([$_SESSION['user_id']]);
        }
    }
}

// Calculate cart items count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa" style="">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'داروخانه آنلاین و پت شاپ تخصصی آسنا'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'داروخانه آنلاین و تخصصی حیوانات خانگی و دامپزشکی آسنا - تامین مستقیم داروها، واکسن‌ها، مکمل‌ها و ملزومات انواع حیوانات خانگی و دام با تاییدیه دامپزشکی'; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'داروخانه آنلاین و پت شاپ تخصصی آسنا'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'مرجع معتبر خرید آنلاین داروهای دامپزشکی، واکسن و ملزومات پت با زنجیره سرد و پشتیبانی ۲۴/۷ دکتر داروساز'; ?>">
    <link href="assets/css/material-symbols.css" rel="stylesheet">
    <link href="assets/css/vazirmatn.css" rel="stylesheet">
    <link href="assets/css/geist.css" rel="stylesheet">
    
    <!-- Custom & Tailwind CSS -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <script src="assets/js/tailwind-config.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-background text-on-background overflow-x-hidden">
    <!-- Header Section -->
    <header class="bg-primary shadow-md sticky top-0 z-50 transition-all rounded-3xl mb-8 w-[96%] max-w-[1600px] mx-auto mt-4 lg:mt-6">
        <div class="flex justify-between items-center w-full px-4 lg:px-8 py-3 lg:py-4 flex-row">
            
            <!-- Right side: Links and Search (Desktop) / Hamburger (Mobile) -->
            <div class="flex items-center gap-4 lg:gap-8 flex-1">
                <!-- Mobile Hamburger Button -->
                <button type="button" onclick="toggleMobileMenu()" class="lg:hidden text-white p-2 hover:bg-white/10 rounded-full transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">menu</span>
                </button>

                <!-- Desktop Links -->
                <div class="hidden lg:flex gap-8 flex-row shrink-0">
                    <a class="text-white text-sm font-medium hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'index.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="index.php">خانه</a>
                    <a class="text-white text-sm font-medium hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'pharmacy.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?> flex items-center gap-1.5" href="pharmacy.php">
                        <span>داروخانه تخصصی</span>
                        <span class="bg-secondary-container text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">تخصصی</span>
                    </a>
                    <a class="text-white text-sm font-medium hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'shop.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="shop.php">فروشگاه</a>
                    <a class="text-white text-sm font-medium hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'booking.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="booking.php">کلینیک</a>
                    <a class="text-white text-sm font-medium hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'subscriptions.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="subscriptions.php">اشتراک خودکار</a>
                </div>
                
                <!-- Desktop Search -->
                <div class="hidden lg:flex items-center bg-white/10 rounded-full px-4 py-2 text-white gap-2 w-full max-w-md">
                    <form action="shop.php" method="GET" class="flex items-center w-full">
                        <button type="submit" class="material-symbols-outlined text-lg bg-transparent border-none outline-none text-white cursor-pointer flex items-center justify-center p-0">search</button>
                        <input name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder-white/60 text-white mr-2" placeholder="جستجو در محصولات و خدمات..." type="text">
                    </form>
                </div>
            </div>

            <!-- Left side: Icons and Logo -->
            <div class="flex items-center gap-3 lg:gap-6 shrink-0">
                <div class="hidden lg:flex items-center gap-3">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin/index.php" class="bg-secondary-container text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all">پنل مدیریت</a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor'): ?>
                            <a href="doctor/index.php" class="bg-white text-primary px-6 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all">پنل پزشک</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="bg-secondary-container text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all">ورود / ثبت‌نام</a>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-1 lg:gap-3">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'; ?>" class="material-symbols-outlined text-white p-2 hover:bg-white/10 rounded-full transition-colors hidden sm:flex">person</a>
                    
                    <a href="cart.php" class="relative material-symbols-outlined text-white p-2 hover:bg-white/10 rounded-full transition-colors flex">
                        shopping_cart
                        <?php if($cart_count > 0): ?>
                            <span class="absolute top-0 right-0 bg-secondary-container text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <h1 class="text-xl lg:text-2xl font-bold text-white tracking-tight">ASENA</h1>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden opacity-0 transition-opacity duration-300">
        <div id="mobile-menu-panel" class="absolute top-0 right-0 h-full w-4/5 max-w-sm bg-surface-container-lowest shadow-2xl translate-x-full transition-transform duration-300 flex flex-col">
            <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center bg-primary text-white">
                <h2 class="text-xl font-bold">منوی کاربری</h2>
                <button type="button" onclick="toggleMobileMenu()" class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 flex flex-col gap-6">
                <!-- Mobile Search -->
                <form action="shop.php" method="GET" class="flex items-center w-full bg-surface-container rounded-xl px-4 py-3">
                    <button type="submit" class="material-symbols-outlined text-lg text-primary bg-transparent border-none outline-none cursor-pointer flex items-center justify-center p-0">search</button>
                    <input name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder-on-surface-variant text-on-surface mr-3 font-medium" placeholder="جستجو..." type="text">
                </form>

                <!-- Mobile Links -->
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="index.php">
                        <span class="material-symbols-outlined text-outline">home</span> خانه
                    </a>
                    <a class="flex items-center gap-4 text-primary font-bold p-3 rounded-xl bg-secondary-container/10 border border-secondary-container/30 transition-colors" href="pharmacy.php">
                        <span class="material-symbols-outlined text-secondary-container">local_pharmacy</span> داروخانه تخصصی
                    </a>
                    <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="shop.php">
                        <span class="material-symbols-outlined text-outline">storefront</span> فروشگاه
                    </a>
                    <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="booking.php">
                        <span class="material-symbols-outlined text-outline">medical_services</span> کلینیک
                    </a>
                    <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="subscriptions.php">
                        <span class="material-symbols-outlined text-outline">autorenew</span> اشتراک خودکار
                    </a>
                </nav>

                <div class="h-px w-full bg-outline-variant/20 my-2"></div>

                <!-- Auth Buttons for Mobile -->
                <div class="flex flex-col gap-3">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="flex items-center justify-center gap-2 bg-surface-container-high text-primary px-6 py-4 rounded-xl text-sm font-bold shadow-sm">
                            <span class="material-symbols-outlined">person</span> حساب کاربری
                        </a>
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin/index.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-4 rounded-xl text-sm font-bold shadow-md">
                                پنل مدیریت
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor'): ?>
                            <a href="doctor/index.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-4 rounded-xl text-sm font-bold shadow-md">
                                پنل پزشک
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-4 rounded-xl text-sm font-bold shadow-md">
                            <span class="material-symbols-outlined">login</span> ورود / ثبت‌نام
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const panel = document.getElementById('mobile-menu-panel');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                // Trigger reflow
                void menu.offsetWidth;
                menu.classList.remove('opacity-0');
                panel.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
            } else {
                menu.classList.add('opacity-0');
                panel.classList.add('translate-x-full');
                document.body.style.overflow = '';
                
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 300);
            }
        }
    </script>
