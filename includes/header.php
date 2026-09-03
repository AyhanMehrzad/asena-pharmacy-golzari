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

// Smart SEO title & description fallbacks based on active page
$seo_defaults = [
    'index.php' => [
        'title' => 'پلتفرم کلینیک دامپزشکی و پت‌شاپ آنلاین آسنا | نوبت‌دهی و خرید ملزومات پت',
        'desc'  => 'کلینیک دامپزشکی و پت‌شاپ تخصصی آسنا؛ نوبت‌دهی آنلاین ویزیت پزشک، خرید غذای خشک و مکمل‌های سگ و گربه، و تحویل خودکار دوره‌ای با بهترین قیمت.'
    ],
    'shop.php' => [
        'title' => 'پت‌شاپ آنلاین آسنا | خرید غذای خشک، کنسرو، مکمل و ملزومات سگ و گربه',
        'desc'  => 'خرید اینترنتی انواع غذای سگ و گربه، لوازم بهداشتی، خاک گربه، تشویقی و مکمل‌های درمانی پت با ضمانت اصالت کالا و ارسال سریع در آسنا.'
    ],
    'booking.php' => [
        'title' => 'نوبت‌دهی آنلاین کلینیک دامپزشکی آسنا | رزرو ویزیت متخصص سگ، گربه و پرندگان',
        'desc'  => 'رزرو آنلاین نوبت دکتر دامپزشک؛ ویزیت عمومی و تخصصی، واکسیناسیون، جراحی، دندانپزشکی و چکاپ دوره‌ای پت با مجرب‌ترین کادر دامپزشکی.'
    ],
    'subscriptions.php' => [
        'title' => 'سفارش دوره‌ای و تحویل خودکار ملزومات پت (Autoship) | تخفیف ویژه آسنا',
        'desc'  => 'دیگر نگران تمام شدن غذای پت خود نباشید! با سیستم ارسال دوره‌ای و خودکار آسنا، سفارشات ماهانه شما با تخفیف دائمی و بدون نیاز به سفارش مجدد ارسال می‌شود.'
    ],
    'charity.php' => [
        'title' => 'پویش‌های حمایت و درمان حیوانات بی‌سرپرست | نقاهتگاه و درمانگاه آسنا',
        'desc'  => 'مشارکت در درمان، واکسیناسیون، عقیم‌سازی و تامین غذای حیوانات آسیب‌دیده و بی‌سرپرست با گزارش‌دهی شفاف و لحظه‌ای در سامانه خیریه آسنا.'
    ],
    'login.php' => [
        'title' => 'ورود و ثبت‌نام در سامانه آسنا | دسترسی به پرونده پزشکی و سفارشات پت',
        'desc'  => 'ورود به پنل کاربری آسنا جهت مدیریت نوبت‌های ویزیت دامپزشکی، پیگیری سفارشات پت‌شاپ و دسترسی به پرونده سلامت و واکسیناسیون حیوان خانگی.'
    ]
];

$default_seo = $seo_defaults[$current_page] ?? [
    'title' => 'کلینیک دامپزشکی و پت‌شاپ آنلاین آسنا',
    'desc'  => 'مرجع تخصصی خدمات دامپزشکی، نوبت‌دهی آنلاین و خرید ملزومات پت با تحویل دوره‌ای'
];

$effective_title = isset($page_title) ? $page_title : $default_seo['title'];
$effective_desc = isset($page_description) ? $page_description : $default_seo['desc'];

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$effective_canonical = isset($canonical_url) ? $canonical_url : "$proto://$host" . strtok($_SERVER['REQUEST_URI'], '?');
$effective_og_image = isset($og_image) ? (strpos($og_image, 'http') === 0 ? $og_image : "$proto://$host/" . ltrim($og_image, '/')) : "$proto://$host/assets/images/og-asena.png";
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa" data-edition="standard">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover" name="viewport">
    <title><?php echo htmlspecialchars($effective_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="google-site-verification" content="google82c161050c864f06">
    <link rel="canonical" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <link rel="alternate" hreflang="fa-IR" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <meta name="geo.region" content="IR">
    <meta name="geo.placename" content="Iran">

    <!-- Safari / Apple & PWA Mobile App Support -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/favicon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/assets/images/favicon-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#002d72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ASENA">
    <meta name="application-name" content="ASENA Company">

    <!-- Preload Critical Font for Core Web Vitals (LCP) -->
    <link rel="preload" href="/assets/fonts/Dxxo8j6PP2D_kU2muijlGMWWMmk.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- Open Graph / Facebook / Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ASENA | کلینیک و پت‌شاپ تخصصی">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:title" content="<?php echo htmlspecialchars($effective_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($effective_canonical); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($effective_og_image); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($effective_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($effective_og_image); ?>">

    <?php if (isset($page_schema) && !empty($page_schema)): ?>
    <!-- Page Specific Schema.org JSON-LD -->
    <script type="application/ld+json">
    <?php echo $page_schema; ?>
    </script>
    <?php else: ?>
    <!-- Default Organization & Breadcrumb Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "خانه",
          "item": "<?php echo $proto . '://' . $host; ?>/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "<?php echo htmlspecialchars($effective_title); ?>",
          "item": "<?php echo htmlspecialchars($effective_canonical); ?>"
        }
      ]
    }
    </script>
    <?php endif; ?>

    <!-- Fonts & Icons -->
    <link href="assets/css/material-symbols.css" rel="stylesheet">
    <link href="assets/css/vazirmatn.css" rel="stylesheet">
    <link href="assets/css/geist.css" rel="stylesheet">
    
    <!-- Custom & Tailwind CSS -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <script src="assets/js/tailwind-config.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/paw-loader.css">
    <script src="assets/js/paw-loader.js" defer></script>
</head>
<body class="bg-background text-on-background overflow-x-hidden">
<?php
$top_notif = null;
if (function_exists('get_curated_recommendations')) {
    $notifs = get_curated_recommendations($pdo, 'notification', 1);
    if (!empty($notifs)) {
        $top_notif = $notifs[0];
    }
}
?>
<?php if (!empty($top_notif)): ?>
<!-- Top Floating Notification Bar -->
<div id="topNotificationBar" class="bg-gradient-to-r from-secondary-container via-[#ea580c] to-secondary-container text-white py-2 px-4 text-xs font-bold shadow-sm relative z-50">
    <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 overflow-hidden">
            <span class="material-symbols-outlined text-base animate-bounce">campaign</span>
            <?php if (!empty($top_notif['custom_badge'])): ?>
                <span class="bg-white/20 px-2 py-0.5 rounded-full text-[11px] font-black shrink-0"><?= htmlspecialchars($top_notif['custom_badge']) ?></span>
            <?php endif; ?>
            <span class="truncate"><?= htmlspecialchars($top_notif['custom_title'] ?: $top_notif['product_name']) ?></span>
            <?php if (!empty($top_notif['custom_subtitle'])): ?>
                <span class="hidden md:inline font-normal opacity-90 text-[11px]">— <?= htmlspecialchars($top_notif['custom_subtitle']) ?></span>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="product_details.php?id=<?= (int)$top_notif['product_id'] ?>" class="bg-white text-secondary-container hover:bg-white/90 px-3 py-1 rounded-lg text-[11px] font-black transition-all shadow-sm flex items-center gap-1">
                <span>مشاهده و خرید</span>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
            <button type="button" onclick="document.getElementById('topNotificationBar').remove()" class="text-white/80 hover:text-white p-0.5">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

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
