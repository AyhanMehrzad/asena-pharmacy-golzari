<?php
include 'includes/header.php';

// Fetch top 12 latest products for the high-density grid
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 12");
$stmt->execute();
$premium_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user wishlist if logged in
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishlist_stmt->execute([$_SESSION['user_id']]);
    $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch active campaigns
$campaign_stmt = $pdo->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
$active_campaigns = $campaign_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top 5 donors
$donor_stmt = $pdo->query("
    SELECT donor_name, SUM(amount) as total_donated 
    FROM donations 
    WHERE status = 'successful' AND donor_name != 'ناشناس'
    GROUP BY donor_name 
    ORDER BY total_donated DESC 
    LIMIT 5
");
$top_donors = $donor_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="w-full max-w-container-max mx-auto space-y-24">
        <!-- Hero Software-Style Interface -->
        <section class="relative min-h-[650px] lg:h-[800px] h-auto py-16 lg:py-0 rounded-[2.5rem] overflow-hidden workstation-module border-none">
            <div class="absolute inset-0 bg-[#f8fafc]">
                <div class="blurred-orb w-[800px] h-[800px] bg-primary-container -top-64 -right-64"></div>
                <div class="blurred-orb w-[600px] h-[600px] bg-secondary-container -bottom-32 -left-32 opacity-10">
                </div>
            </div>
            <div class="relative h-full flex items-center px-6 lg:px-20">
                <div class="w-full lg:w-1/2 space-y-8 z-10" id="hero-content">
                    <div id="hero-badge"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-outline-variant/30 rounded-full text-xs font-bold text-primary shadow-sm">
                        <span id="hero-badge-icon" class="material-symbols-outlined text-sm text-secondary-container">autorenew</span>
                        <span id="hero-badge-text">سیستم تحویل خودکار (Autoship)</span>
                    </div>
                    <h1 id="hero-title" class="text-4xl md:text-6xl lg:text-8xl font-bold text-primary leading-[1.2] lg:leading-[1] tracking-tight">
                        اشتراک هوشمند؛<br />همیشه در دسترس
                    </h1>
                    <p id="hero-desc" class="text-lg lg:text-xl text-on-surface-variant font-light max-w-lg leading-relaxed">
                        برنامه غذایی و دارویی پت شما هرگز متوقف نمی‌شود. با فعال‌سازی اشتراک، از تخفیف دائمی و اولویت در
                        خدمات بهره‌مند شوید.
                    </p>
                    <div class="flex items-center gap-4 pt-4">
                        <a id="hero-link" href="subscriptions.php"
                            class="inline-block text-center bg-primary-container text-white px-10 py-5 rounded-2xl font-bold shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                            شروع تجربه اشتراک
                        </a>
                        <div class="flex -space-x-3 space-x-reverse items-center pr-6">
                            <div class="w-12 h-12 rounded-full border-4 border-white bg-slate-200"></div>
                            <div class="w-12 h-12 rounded-full border-4 border-white bg-slate-300"></div>
                            <div
                                class="w-12 h-12 rounded-full border-4 border-white bg-primary-container flex items-center justify-center text-white text-[10px] font-bold">
                                ۵۰۰+</div>
                        </div>
                    </div>
                </div>
                <div id="hero-bg" class="absolute inset-0 md:left-0 md:top-0 h-full w-full md:w-1/2 opacity-20 md:opacity-100 bg-cover bg-center mask-fade-l md:mr-auto transition-all duration-700"
                    style='background-image: url("assets/images/cat-hero.jpg");'>
                </div>
            </div>
            <!-- Redesigned Slider Controls -->
            <!-- Arrow Buttons -->
            <div class="absolute inset-y-0 left-4 right-4 lg:left-8 lg:right-8 flex items-center justify-between pointer-events-none z-30">
                <button onclick="nextSlide()"
                    class="pointer-events-auto w-16 h-16 rounded-full backdrop-blur-md bg-white/20 border border-white/30 shadow-2xl flex items-center justify-center text-primary hover:bg-white hover:scale-110 transition-all">
                    <span class="material-symbols-outlined text-4xl font-bold">chevron_right</span>
                </button>
                <button onclick="prevSlide()"
                    class="pointer-events-auto w-16 h-16 rounded-full backdrop-blur-md bg-white/20 border border-white/30 shadow-2xl flex items-center justify-center text-primary hover:bg-white hover:scale-110 transition-all">
                    <span class="material-symbols-outlined text-4xl font-bold">chevron_left</span>
                </button>
            </div>
            <!-- Pagination Pills -->
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-30 flex items-center gap-3" id="hero-pills">
                <button onclick="goToSlide(0)" class="w-8 h-2 rounded-full bg-primary-container scale-125 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(1)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(2)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(3)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
            </div>
        </section>
        
        <!-- Cycle Section - Rail Density (Functional & Clickable) -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            <div class="lg:col-span-3 flex flex-col justify-center p-10 bg-surface-container-low rounded-[2rem] space-y-4">
                <h2 class="text-3xl font-bold text-primary">چرخه مراقبت هوشمند</h2>
                <p class="text-sm text-on-surface-variant leading-relaxed">خدمات یکپارچه برای سلامت همیشگی پت شما که به صورت ۲۴ ساعته مانیتور می‌شود.</p>
                <div class="flex gap-2 pt-4">
                    <div class="w-3 h-3 rounded-full bg-primary-container"></div>
                    <div class="w-3 h-3 rounded-full bg-outline-variant/30"></div>
                    <div class="w-3 h-3 rounded-full bg-outline-variant/30"></div>
                </div>
            </div>
            <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- 1. Checkup -->
                <a href="booking.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">stethoscope</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>چک‌آپ دوره‌ای</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">یادآوری هوشمند معاینات بر اساس سن و نژاد دقیق پت شما.</p>
                </a>

                <!-- 2. Nutrition -->
                <a href="subscriptions.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">nutrition</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>تغذیه اختصاصی</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">آنالیز تخصصی رژیم غذایی بر اساس فعالیت‌های روزانه.</p>
                </a>

                <!-- 3. Vaccination -->
                <a href="pharmacy.php?tag=vaccines" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">vaccines</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>واکسیناسیون</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">مدیریت خودکار پرونده سلامت دیجیتال و هشدارهای واکسن.</p>
                </a>

                <!-- 4. Online Pharmacy -->
                <a href="pharmacy.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block border-2 border-secondary-container/30 bg-gradient-to-b from-white to-orange-50/30">
                    <div class="w-16 h-16 bg-secondary-container/10 text-secondary-container rounded-2xl flex items-center justify-center mb-6 group-hover:bg-secondary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">medication</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-secondary-container flex items-center justify-between">
                        <span>داروخانه آنلاین</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">تامین و ارسال سریع نسخه‌های تخصصی درب منزل با زنجیره سرد.</p>
                </a>
            </div>
        </section>

        <!-- Pet Pharmacy Featured Section (Prominent Spotlight for Pharmacy Clients) -->
        <section class="bg-gradient-to-r from-primary via-primary-container to-[#001a48] text-white rounded-[2.5rem] p-8 lg:p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 border-b border-white/10 pb-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-secondary-container text-white rounded-full text-xs font-bold mb-3 shadow-md">
                            <span class="material-symbols-outlined text-sm">local_pharmacy</span>
                            داروخانه تخصصی حیوانات و ملزومات پزشکی
                        </div>
                        <h2 class="text-2xl lg:text-4xl font-bold tracking-tight">داروخانه آنلاین آسنا؛ همراه سلامت تمام گونه‌ها</h2>
                        <p class="text-sm text-white/75 mt-1 max-w-xl">تامین مستقیم داروهای کمیاب، واکسن‌ها، ضد انگل‌ها و مراقبت‌های ویژه برای اسب، دام، طیور، سگ و گربه.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="pharmacy.php" class="bg-secondary-container text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#ea580c] transition-all shadow-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                            مشاهده تمام داروهای تخصصی
                        </a>
                    </div>
                </div>

                <!-- Species Quick Navigation Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <a href="pharmacy.php?animal=dog" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐕</span>
                        <span class="text-xs font-bold">داروهای سگ</span>
                    </a>
                    <a href="pharmacy.php?animal=cat" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐈</span>
                        <span class="text-xs font-bold">داروهای گربه</span>
                    </a>
                    <a href="pharmacy.php?animal=horse" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐎</span>
                        <span class="text-xs font-bold">داروهای اسب</span>
                    </a>
                    <a href="pharmacy.php?animal=cow" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐄</span>
                        <span class="text-xs font-bold">داروهای دام</span>
                    </a>
                    <a href="pharmacy.php?animal=chick" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐥</span>
                        <span class="text-xs font-bold">داروهای طیور</span>
                    </a>
                    <a href="pharmacy.php?tag=vaccines" class="bg-secondary-container/20 hover:bg-secondary-container/40 border border-secondary-container/40 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-secondary-container text-white flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">vaccines</span>
                        </span>
                        <span class="text-xs font-bold text-secondary-container">واکسن و سرم</span>
                    </a>
                </div>
            </div>
        </section>
        <!-- Subscription Plans -->
        <section class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto">
                <!-- 3-Month Plan -->
                <div
                    class="bg-primary-container rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 text-white flex flex-col space-y-6 md:space-y-10 shadow-xl hover:-translate-y-2 transition-transform">
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۳ ماهه</div>
                        <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                            <div class="w-4 h-4 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold">اشتراک پایه</h3>
                        <ul class="space-y-4 text-white/70 text-sm">
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>۱۵٪
                                تخفیف دائمی</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>ارسال
                                رایگان</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>پشتیبانی
                                اولویت‌دار</li>
                        </ul>
                    </div>
                    <a href="subscriptions.php"
                        class="block text-center w-full bg-white text-primary-container py-4 rounded-2xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto">انتخاب
                        اشتراک</a>
                </div>
                <!-- 6-Month Plan -->
                <div
                    class="bg-primary-container rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 text-white flex flex-col space-y-6 md:space-y-10 shadow-2xl border-4 border-secondary-container relative transform scale-100 md:scale-105 z-10 mt-4 md:mt-0">
                    <div
                        class="absolute -top-5 left-1/2 -translate-x-1/2 bg-secondary-container text-white px-8 py-2 rounded-full text-xs font-bold shadow-lg">
                        بهترین ارزش</div>
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۶ ماهه</div>
                        <div class="w-12 h-12 rounded-full border-2 border-white flex items-center justify-center">
                            <div class="w-6 h-6 rounded-full bg-white"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-3xl font-bold">اشتراک ویژه</h3>
                        <ul class="space-y-4 text-white/90 text-sm">
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>تمامی
                                مزایای پایه</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>تشویقی‌های
                                اختصاصی ماهانه</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>مشاوره
                                رایگان تغذیه</li>
                        </ul>
                    </div>
                    <a href="subscriptions.php"
                        class="block text-center w-full bg-secondary-container text-white py-5 rounded-2xl font-bold shadow-lg hover:shadow-2xl transition-all mt-auto">انتخاب
                        اشتراک ویژه</a>
                </div>
                <!-- 12-Month Plan -->
                <div
                    class="bg-primary-container rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 text-white flex flex-col space-y-6 md:space-y-10 shadow-xl hover:-translate-y-2 transition-transform">
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۱۲ ماهه</div>
                        <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                            <div class="w-4 h-4 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold">اشتراک طلایی</h3>
                        <ul class="space-y-4 text-white/70 text-sm">
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>تمامی
                                مزایای ویژه</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>باکس
                                هدیه پرمیوم سالانه</li>
                            <li class="flex items-center gap-3"><span
                                    class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>چک‌آپ
                                رایگان در منزل</li>
                        </ul>
                    </div>
                    <a href="subscriptions.php"
                        class="w-full block text-center bg-white text-primary-container py-4 rounded-2xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto">انتخاب
                        اشتراک</a>
                </div>
            </div>
        </section>
        <!-- AI Clinical Assistant & Support -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center" id="support-section">
            <div class="lg:col-span-4 space-y-8 p-10 workstation-module rounded-[2.5rem] h-full flex flex-col justify-center border-none relative overflow-hidden">
                <div class="absolute -right-20 -bottom-20 opacity-5">
                    <span class="material-symbols-outlined text-[300px]">cruelty_free</span>
                </div>
                <div class="inline-flex items-center gap-3 px-4 py-2 bg-primary-container/10 text-primary-container rounded-lg font-bold text-xs uppercase tracking-wider w-fit z-10">
                    <span class="material-symbols-outlined text-sm">support_agent</span>
                    پشتیبانی یکپارچه
                </div>
                <h2 class="text-4xl font-bold text-primary leading-tight z-10">
                    لئو (هوش مصنوعی) یا<br />پشتیبانی انسانی؟
                </h2>
                <p class="text-lg text-on-surface-variant font-light leading-relaxed z-10">
                    برای تشخیص فوری مشکلات جسمی، می‌توانید عکس حیوان خود را برای <b>لئو</b> بفرستید. یا برای پیگیری سفارشات با <b>پشتیبانی انسانی</b> صحبت کنید.
                </p>
                <div class="space-y-4 z-10 pt-4">
                    <div class="bg-surface-container rounded-2xl p-2 flex items-center justify-between shadow-sm relative">
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-primary-container/10 to-transparent pointer-events-none transition-all" id="mode-bg"></div>
                        <button onclick="setChatMode('ai')" id="btn-mode-ai" class="flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold bg-primary-container text-white shadow-md transition-all">
                            <span class="material-symbols-outlined">smart_toy</span>
                            لئو (هوش مصنوعی)
                        </button>
                        <button onclick="setChatMode('admin')" id="btn-mode-admin" class="flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold text-on-surface-variant hover:text-primary transition-all">
                            <span class="material-symbols-outlined">support_agent</span>
                            پشتیبانی انسانی
                        </button>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-8 workstation-module rounded-[2.5rem] overflow-hidden flex flex-col h-[650px] border-none shadow-xl bg-surface-container-lowest">
                <!-- Chat Header -->
                <div class="bg-white border-b border-outline-variant/20 px-6 py-4 flex items-center justify-between shadow-sm z-10">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-14 h-14 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container border-2 border-primary-container" id="chat-avatar">
                                <span class="material-symbols-outlined text-3xl">cruelty_free</span> <!-- Lion/Animal Icon -->
                            </div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-primary" id="chat-title">لئو (Leo)</h3>
                            <p class="text-xs text-on-surface-variant flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                آنلاین - آماده تشخیص
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                </div>
                
                <!-- Chat Body -->
                <div class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar bg-surface-container-lowest relative" id="chat-messages">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm flex flex-col items-center justify-center z-20">
                        <span class="material-symbols-outlined text-6xl text-primary-container mb-4">lock</span>
                        <h3 class="text-xl font-bold text-primary mb-2">نیاز به ورود</h3>
                        <p class="text-sm text-on-surface-variant mb-6">برای استفاده از سیستم پشتیبانی، لطفا وارد حساب کاربری خود شوید.</p>
                        <a href="login.php" class="bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:-translate-y-1 transition-transform">ورود / ثبت‌نام</a>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-center mb-8">
                        <div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">امروز</div>
                    </div>
                    <!-- Messages will be injected here via JS -->
                    <?php endif; ?>
                </div>
                
                <!-- Loading Indicator -->
                <div id="chat-typing" class="px-6 py-2 bg-surface-container-lowest hidden items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <div class="flex gap-1">
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <span>لئو در حال تایپ است...</span>
                </div>

                <!-- Image Preview Overlay -->
                <div id="image-preview-container" class="hidden px-6 py-4 bg-surface-container border-t border-outline-variant/20 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img id="image-preview" src="" class="w-16 h-16 object-cover rounded-lg shadow-sm border border-outline-variant/30">
                        <div class="text-xs font-bold text-primary">تصویر ضمیمه شد</div>
                    </div>
                    <button type="button" onclick="clearImage()" class="w-8 h-8 bg-error/10 text-error rounded-full flex items-center justify-center hover:bg-error hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>

                <!-- Chat Footer (Input Bar) -->
                <div class="p-4 bg-white border-t border-outline-variant/20 z-10 <?php echo !isset($_SESSION['user_id']) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <form id="chat-form" class="flex items-center gap-3 relative" onsubmit="sendChatMessage(event)">
                        <input type="file" id="chat-image-input" class="hidden" accept="image/*" onchange="handleImageSelect(this)">
                        <button type="button" onclick="document.getElementById('chat-image-input').click()" class="w-12 h-12 rounded-full hover:bg-primary-container/10 text-on-surface-variant hover:text-primary-container flex items-center justify-center transition-colors shrink-0">
                            <span class="material-symbols-outlined text-2xl">attach_file</span>
                        </button>
                        
                        <div class="flex-1 relative">
                            <input id="chat-input" class="w-full bg-surface-container-low border-none rounded-full pl-14 pr-6 py-4 focus:ring-2 focus:ring-primary-container transition-all text-sm font-medium" placeholder="پیام خود را بنویسید..." type="text" autocomplete="off" />
                            <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full hover:bg-black/5 flex items-center justify-center text-on-surface-variant transition-colors">
                                <span class="material-symbols-outlined">sentiment_satisfied</span>
                            </button>
                        </div>
                        
                        <button type="submit" id="chat-send-btn" class="w-14 h-14 bg-primary-container text-white rounded-full hover:scale-105 hover:bg-primary transition-all flex items-center justify-center shadow-lg shrink-0">
                            <span class="material-symbols-outlined text-2xl -ml-1">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <!-- Charity Section -->
        <section class="bg-primary-container rounded-[2rem] md:rounded-[3.5rem] overflow-hidden relative text-white shadow-2xl p-6 md:p-12 lg:p-24">
            <div class="paw-pattern absolute inset-0"></div>
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center text-center lg:text-right">
                <div class="space-y-6 md:space-y-10 flex flex-col items-center lg:items-start">
                    <div class="flex items-center gap-3 text-secondary-container font-bold bg-white/10 w-fit px-6 py-2 rounded-full border border-white/10 backdrop-blur-md">
                        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" 1;'>volunteer_activism</span>
                        مسئولیت اجتماعی آسنا
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-bold leading-tight">
                        حمایت از گربه‌های خیابان؛<br />با هر خرید شما
                    </h2>
                    <p class="text-xl text-white/70 font-light leading-relaxed">
                        ما بخشی از سود هر تراکنش را مستقیماً صرف تامین غذا و درمان سارقان قلب خیابان‌ها می‌کنیم.
                    </p>
                    <div class="flex flex-wrap justify-center lg:justify-start gap-8 md:gap-12 pt-4">
                        <div class="space-y-1">
                            <div class="text-3xl font-bold text-secondary-container">۱۲.۵ تن</div>
                            <div class="text-xs text-white/50 uppercase font-bold tracking-widest">غذای توزیع شده</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-3xl font-bold text-secondary-container">۳,۸۰۰+</div>
                            <div class="text-xs text-white/50 uppercase font-bold tracking-widest">فرشته درمان شده</div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 pt-4 w-full sm:w-auto">
                        <a href="charity.php" class="inline-block bg-secondary-container text-white px-10 py-5 rounded-2xl font-bold shadow-xl hover:scale-105 transition-transform text-center">مشاهده کمپین‌ها و حمایت</a>
                    </div>
                </div>
                
                <div class="glass-card !bg-white/10 !border-white/10 rounded-[2.5rem] p-1.5 shadow-2xl relative">
                    <?php if(empty($active_campaigns)): ?>
                    <div class="bg-white rounded-[2.4rem] overflow-hidden p-10 text-center">
                        <h3 class="text-2xl font-bold text-primary">کمپین فعالی وجود ندارد</h3>
                        <p class="text-on-surface-variant mt-4">منتظر کمپین‌های جدید باشید.</p>
                    </div>
                    <?php else: ?>
                    
                    <div class="swiper-container charity-index-slider relative rounded-[2.4rem] overflow-hidden bg-white">
                        <div class="swiper-wrapper">
                            <?php foreach($active_campaigns as $camp): 
                                $percent = $camp['goal_amount'] > 0 ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                            ?>
                            <div class="swiper-slide">
                                <div class="h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($camp['image_url'] ?: 'https://placehold.co/800x600?text=Campaign'); ?>');"></div>
                                <div class="p-10 space-y-8 bg-white text-right">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-2xl font-bold text-primary line-clamp-1"><?php echo htmlspecialchars($camp['title']); ?></h3>
                                        <span class="bg-emerald-500 text-white text-[10px] px-3 py-1 rounded-full font-bold animate-pulse">فعال</span>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden flex items-stretch p-0.5">
                                            <div class="bg-gradient-to-l from-secondary-container to-orange-400 rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                        <div class="flex justify-between text-xs font-bold text-on-surface-variant">
                                            <span class="">هدف: <?php echo number_format($camp['goal_amount']); ?> تومان</span>
                                            <span class="text-primary"><?php echo number_format($camp['current_amount']); ?> جمع شده</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination !bottom-4"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Top Donors Removed (Moved to Community Heroes) -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6 pb-24">
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">restaurant</span>
                </div>
                <a href="shop.php?q=غذای خشک" class="text-sm font-bold text-primary before:absolute before:inset-0">غذای خشک</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">medication_liquid</span>
                </div>
                <a href="shop.php?category=مکمل دارویی" class="text-sm font-bold text-primary before:absolute before:inset-0">مکمل و دارو</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">toys</span>
                </div>
                <a href="shop.php?category=اسباب‌بازی" class="text-sm font-bold text-primary before:absolute before:inset-0">اسباب‌بازی</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">cleaning_services</span>
                </div>
                <a href="shop.php?category=لوازم بهداشتی" class="text-sm font-bold text-primary before:absolute before:inset-0">بهداشتی</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">house</span>
                </div>
                <a href="shop.php?q=جای خواب" class="text-sm font-bold text-primary before:absolute before:inset-0">جای خواب</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-primary-container text-white">
                <div
                    class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center group-hover:bg-white group-hover:text-primary-container transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">more_horiz</span>
                </div>
                <a href="shop.php" class="text-sm font-bold text-white before:absolute before:inset-0">مشاهده همه</a>
            </div>
        </section>
        <!-- Premium Products Section (Dynamic High Density) -->
        <section class="space-y-12 pb-12">
            <div class="flex items-end justify-between">
                <div class="space-y-4">
                    <h2 class="text-4xl font-bold text-primary tracking-tight">محصولات ویژه</h2>
                    <p class="text-on-surface-variant font-light text-lg">پیشنهادات استثنایی و پرفروش‌ترین‌ها</p>
                </div>
                <a href="shop.php" class="bg-primary/5 text-primary px-6 py-3 rounded-full font-bold flex items-center gap-2 hover:bg-primary hover:text-white transition-all shadow-sm">
                    مشاهده کل فروشگاه
                    <span class="material-symbols-outlined">arrow_left_alt</span>
                </a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($premium_products as $product): ?>
                <div class="bg-white rounded-3xl p-4 shadow-lg hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
                    <!-- Badge -->
                    <?php if($product['discount_price']): ?>
                    <div class="absolute top-4 left-4 bg-secondary-container text-on-secondary-container text-[10px] px-2 py-1 rounded-full z-10 font-bold">تخفیف ویژه</div>
                    <?php endif; ?>
                    
                    <!-- Wishlist Button -->
                    <?php $in_wishlist = in_array($product['id'], $user_wishlist); ?>
                    <button type="button" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)" class="absolute top-4 right-4 z-10 w-8 h-8 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-on-surface hover:text-error transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' <?php echo $in_wishlist ? '1' : '0'; ?>; color: <?php echo $in_wishlist ? '#dc2626' : 'inherit'; ?>;">favorite</span>
                    </button>

                    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 overflow-hidden relative">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <!-- Quick add to cart overlay -->
                        <div class="absolute inset-x-0 bottom-0 p-2 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center z-20">
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" class="bg-primary text-white w-full py-2 rounded-xl text-xs font-bold flex justify-center items-center gap-1 hover:bg-primary-container">
                                <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                                افزودن به سبد
                            </button>
                        </div>
                        
                        <!-- Mobile Quick Add to Cart -->
                        <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" class="lg:hidden absolute bottom-3 left-3 z-30 w-9 h-9 bg-primary/90 backdrop-blur-md text-white rounded-full flex items-center justify-center shadow-lg active:scale-95 transition-transform border border-white/20">
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                        </button>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <p class="text-[10px] text-on-surface-variant mb-1 line-clamp-1">
                            <?php echo htmlspecialchars($product['category']); ?>
                            <?php if(!empty($product['brand'])) echo ' • <span class="text-primary font-bold">' . htmlspecialchars($product['brand']) . '</span>'; ?>
                        </p>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>"><h3 class="text-sm font-bold text-on-surface mb-2 line-clamp-2 hover:text-primary transition-colors cursor-pointer leading-tight"><?php echo htmlspecialchars($product['name']); ?></h3></a>
                        <div class="mt-auto flex justify-between items-center">
                            <div class="flex flex-col">
                                <?php if($product['discount_price']): ?>
                                <span class="text-[10px] text-on-surface-variant line-through mb-0.5"><?php echo number_format($product['price']); ?> تومان</span>
                                <span class="text-sm font-bold text-primary"><?php echo number_format($product['discount_price']); ?> تومان</span>
                                <?php else: ?>
                                <span class="text-sm font-bold text-primary"><?php echo number_format($product['price']); ?> تومان</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Community Heroes -->
        <section class="bg-white py-24 rounded-[4rem] border border-outline-variant/10 text-center space-y-12">
            <div class="space-y-4">
                <h2 class="text-4xl font-bold text-primary">قهرمانان جامعه آسنا</h2>
                <p class="text-on-surface-variant">ما به داشتن چنین همراهانی افتخار می‌کنیم</p>
            </div>
            <div class="flex flex-wrap justify-center gap-16">
                <?php if(!empty($top_donors)): ?>
                    <?php foreach($top_donors as $index => $donor): 
                        $medal_icon = 'stars';
                        $medal_color = 'bg-primary-container';
                        $border_color = 'border-primary-container';
                        if ($index === 0) {
                            $medal_icon = 'workspace_premium';
                            $medal_color = 'bg-amber-400';
                            $border_color = 'border-amber-400';
                        } elseif ($index === 1) {
                            $medal_icon = 'military_tech';
                            $medal_color = 'bg-slate-300';
                            $border_color = 'border-slate-300';
                        } elseif ($index === 2) {
                            $medal_icon = 'military_tech';
                            $medal_color = 'bg-amber-600';
                            $border_color = 'border-amber-600';
                        }
                    ?>
                    <div class="flex flex-col items-center gap-4 group cursor-pointer">
                        <div class="relative w-28 h-28">
                            <div class="absolute inset-0 rounded-full border-4 <?php echo $border_color; ?> p-1 group-hover:scale-110 transition-transform">
                                <div class="w-full h-full rounded-full bg-cover bg-center"
                                    style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($donor['donor_name']); ?>&background=random&color=fff&size=150");'>
                                </div>
                            </div>
                            <div class="absolute -bottom-1 -right-1 <?php echo $medal_color; ?> text-white w-10 h-10 rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                                <?php if($index < 3): ?>
                                    <span class="material-symbols-outlined text-lg"><?php echo $medal_icon; ?></span>
                                <?php else: ?>
                                    <span class="text-sm font-bold">#<?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-primary text-lg"><?php echo htmlspecialchars($donor['donor_name']); ?></div>
                            <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest"><?php echo number_format($donor['total_donated']); ?> تومان</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="flex flex-col items-center justify-center gap-4 group cursor-pointer" onclick="window.location.href='charity.php'">
                    <div
                        class="w-28 h-28 rounded-full border-2 border-dashed border-outline-variant flex items-center justify-center group-hover:border-primary-container transition-all">
                        <span
                            class="material-symbols-outlined text-4xl text-outline-variant group-hover:text-primary-container">add</span>
                    </div>
                    <div class="font-bold text-on-surface-variant group-hover:text-primary-container">شما هم بپیوندید
                    </div>
                </div>
            </div>
        </section>
    </main>

<!-- Interaction Layer -->
    <div class="fixed bottom-6 left-6 md:bottom-12 md:left-12 flex flex-col gap-3 md:gap-4 z-50">
        <a href="chat.php"
            class="w-12 h-12 md:w-16 md:h-16 bg-white border border-outline-variant text-primary rounded-2xl shadow-2xl flex items-center justify-center hover:scale-110 hover:bg-primary hover:text-white transition-all group relative">
            <span class="material-symbols-outlined text-xl md:text-3xl">chat_bubble</span>
            <span
                class="absolute left-14 md:left-20 bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl">پشتیبانی
                آنلاین</span>
        </a>
        <a href="https://maps.google.com/?q=Tehran" target="_blank"
            class="w-12 h-12 md:w-16 md:h-16 bg-secondary-container text-white rounded-2xl shadow-2xl flex items-center justify-center hover:scale-110 transition-all group relative">
            <span class="material-symbols-outlined text-xl md:text-3xl">location_on</span>
            <span
                class="absolute left-14 md:left-20 bg-white text-primary border border-outline-variant px-4 py-2 rounded-xl text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl">مسیریابی کلینیک</span>
        </a>
    </div>

<script>
// --- Chat System Logic ---
let chatMode = 'ai';
let currentTicketId = null;
let lastMessageId = 0;
let chatPollingInterval = null;

function setChatMode(mode) {
    chatMode = mode;
    
    // Update UI Buttons
    const btnAi = document.getElementById('btn-mode-ai');
    const btnAdmin = document.getElementById('btn-mode-admin');
    
    if (mode === 'ai') {
        btnAi.className = 'flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold bg-primary-container text-white shadow-md transition-all';
        btnAdmin.className = 'flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold text-on-surface-variant hover:text-primary transition-all';
        
        document.getElementById('chat-title').innerText = 'لئو (Leo)';
        document.getElementById('chat-avatar').innerHTML = '<span class="material-symbols-outlined text-3xl">cruelty_free</span>';
        document.querySelector('#chat-typing span').innerText = 'لئو در حال تایپ است...';
    } else {
        btnAdmin.className = 'flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold bg-primary-container text-white shadow-md transition-all';
        btnAi.className = 'flex-1 flex items-center justify-center gap-2 py-4 rounded-xl text-sm font-bold text-on-surface-variant hover:text-primary transition-all';
        
        document.getElementById('chat-title').innerText = 'پشتیبانی (Admin)';
        document.getElementById('chat-avatar').innerHTML = '<span class="material-symbols-outlined text-3xl">support_agent</span>';
        document.querySelector('#chat-typing span').innerText = 'پشتیبان در حال پاسخگویی است...';
    }
    
    // Reset Chat State
    lastMessageId = 0;
    const msgContainer = document.getElementById('chat-messages');
    if (msgContainer.querySelector('.bg-white\\/60')) return; // locked state
    
    msgContainer.innerHTML = '<div class="flex justify-center mb-8"><div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">امروز</div></div>';
    
    initChat();
}

function initChat() {
    if (chatPollingInterval) clearInterval(chatPollingInterval);
    
    const fd = new FormData();
    fd.append('action', 'init');
    fd.append('mode', chatMode);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                currentTicketId = data.ticket_id;
                fetchMessages();
                chatPollingInterval = setInterval(fetchMessages, 3000);
            }
        });
}

function fetchMessages() {
    if (!currentTicketId) return;
    
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentTicketId);
    fd.append('last_id', lastMessageId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
                document.getElementById('chat-typing').style.display = 'none';
            }
        });
}

function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    
    messages.forEach(msg => {
        const isUser = msg.sender_type === 'user';
        const avatar = msg.sender_type === 'ai' ? 'cruelty_free' : 'support_agent';
        
        let imgHtml = '';
        if (msg.image_url) {
            imgHtml = `<img src="${msg.image_url}" class="rounded-xl mb-3 max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity">`;
        }
        // Base64 images are not saved to DB for AI mode to save space, but if we had them they'd be here.

        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });

        if (isUser) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                        ${imgHtml}
                        <div>${msg.message.replace(/\n/g, '<br>')}</div>
                        <div class="text-[9px] text-white/70 mt-2 text-left w-full block">${time} <span class="material-symbols-outlined text-[10px] ml-0.5" style="vertical-align: middle">done_all</span></div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%]">
                    <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center shrink-0 border-2 border-white shadow-sm mt-auto">
                        <span class="material-symbols-outlined text-lg">${avatar}</span>
                    </div>
                    <div class="bg-white px-5 py-4 rounded-3xl rounded-br-sm shadow-md text-sm border border-outline-variant/10 leading-relaxed text-on-surface">
                        ${imgHtml}
                        <div class="markdown-body">${msg.message.replace(/\n/g, '<br>')}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-2 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
}

function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('image-preview-container').classList.add('flex');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('chat-image-input').value = '';
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('image-preview-container').classList.remove('flex');
    document.getElementById('image-preview').src = '';
}

function sendChatMessage(e) {
    e.preventDefault();
    if (!currentTicketId) return;
    
    const input = document.getElementById('chat-input');
    const imageInput = document.getElementById('chat-image-input');
    const msg = input.value.trim();
    
    if (!msg && imageInput.files.length === 0) return;
    
    // Show Optimistic UI for User Message
    const container = document.getElementById('chat-messages');
    let imgHtml = '';
    if (imageInput.files.length > 0) {
        imgHtml = `<img src="${document.getElementById('image-preview').src}" class="rounded-xl mb-3 max-w-[200px] opacity-70">`;
    }
    
    const time = new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    
    container.insertAdjacentHTML('beforeend', `
        <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto opacity-70" id="temp-msg">
            <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                ${imgHtml}
                <div>${msg.replace(/\n/g, '<br>')}</div>
                <div class="text-[9px] text-white/70 mt-2 text-left w-full block"><span class="material-symbols-outlined text-[10px] animate-spin">sync</span></div>
            </div>
        </div>
    `);
    scrollToBottom();
    
    // Clear Inputs
    input.value = '';
    const file = imageInput.files[0];
    clearImage();
    
    // Show Typing Indicator
    document.getElementById('chat-typing').style.display = 'flex';
    scrollToBottom();
    
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('ticket_id', currentTicketId);
    fd.append('message', msg);
    if (file) {
        fd.append('image', file);
    }
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // Redirect user to the dedicated chat page to continue the conversation
                window.location.href = 'chat.php?ticket_id=' + currentTicketId;
            } else {
                alert('خطا در ارسال پیام');
                document.getElementById('temp-msg')?.remove();
                input.disabled = false;
                document.getElementById('chat-send-btn').disabled = false;
            }
        });
}

// Initialize chat if user is logged in
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('#chat-messages .bg-white\\/60')) {
        setChatMode('ai');
    }
});

// Cart Logic
function addToCart(btn, productId, type = 'standard') {
    if(window.event) window.event.preventDefault();
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span>';
    
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId + '&type=' + type
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span>';
            btn.classList.add('bg-green-500', 'text-white');
            btn.classList.remove('bg-primary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-primary');
            }, 2000);
        } else {
            alert('خطا در افزودن به سبد خرید');
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
    });
}

function toggleWishlist(btn, productId) {
    if(event) event.preventDefault();
    
    fetch('actions/wishlist_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const icon = btn.querySelector('.material-symbols-outlined');
            if (data.action === 'added') {
                icon.style.fontVariationSettings = "'FILL' 1";
                icon.style.color = '#dc2626'; 
            } else {
                icon.style.fontVariationSettings = "'FILL' 0";
                icon.style.color = 'inherit';
            }
        } else {
            alert(data.message || 'خطایی رخ داد.');
            if (data.message === 'ابتدا وارد حساب کاربری شوید.') {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// --- Slider Logic ---
const slides = [
    {
        title: "اشتراک هوشمند؛<br />همیشه در دسترس",
        desc: "برنامه غذایی و دارویی پت شما هرگز متوقف نمی‌شود. با فعال‌سازی اشتراک، از تخفیف دائمی و اولویت در خدمات بهره‌مند شوید.",
        badgeIcon: "autorenew",
        badgeText: "سیستم تحویل خودکار (Autoship)",
        link: "subscriptions.php",
        linkText: "شروع تجربه اشتراک",
        img: "assets/images/cat-hero.jpg"
    },
    {
        title: "برترین محصولات<br />برای سلامت پت",
        desc: "فروشگاه ما با مجموعه‌ای بی‌نظیر از بهترین برندهای جهانی، تضمین‌کننده سلامت و نشاط حیوان خانگی شماست.",
        badgeIcon: "local_shipping",
        badgeText: "ارسال رایگان سفارشات بالای ۵۰۰ هزار تومان",
        link: "shop.php",
        linkText: "مشاهده فروشگاه",
        img: "assets/images/toy-mouse.jpg"
    },
    {
        title: "کلینیک تخصصی<br />در دستان شما",
        desc: "با استفاده از سیستم یکپارچه رزرواسیون آنلاین، بدون معطلی و در سریع‌ترین زمان ممکن برای پت خود نوبت بگیرید.",
        badgeIcon: "medical_services",
        badgeText: "پشتیبانی درمانی حرفه‌ای",
        link: "booking.php",
        linkText: "رزرو نوبت کلینیک",
        img: "assets/images/presentation-dog.jpg"
    },
    {
        title: "داروخانه تخصصی؛<br />ارسال فوری نسخه",
        desc: "تامین مستقیم داروهای کمیاب، واکسن‌های زنجیره سرد و مکمل‌های تقویتی انواع گونه‌های حیوانات با تاییدیه دامپزشکی.",
        badgeIcon: "local_pharmacy",
        badgeText: "داروخانه آنلاین دامپزشکی",
        link: "pharmacy.php",
        linkText: "ورود به داروخانه",
        img: "assets/images/dog-hero.jpg"
    }
];

let currentSlide = 0;
let slideInterval = setInterval(nextSlide, 6000);

function updateSlide() {
    const slide = slides[currentSlide];
    
    // Animate content out
    const content = document.getElementById('hero-content');
    content.style.opacity = 0;
    
    setTimeout(() => {
        document.getElementById('hero-title').innerHTML = slide.title;
        document.getElementById('hero-desc').innerText = slide.desc;
        document.getElementById('hero-badge-icon').innerText = slide.badgeIcon;
        document.getElementById('hero-badge-text').innerText = slide.badgeText;
        document.getElementById('hero-link').href = slide.link;
        document.getElementById('hero-link').innerText = slide.linkText;
        document.getElementById('hero-bg').style.backgroundImage = `url("${slide.img}")`;
        
        // Animate content in
        content.style.opacity = 1;
        content.style.transition = 'opacity 0.5s ease';
    }, 300);

    // Update Pills
    const pills = document.getElementById('hero-pills').children;
    for(let i=0; i<pills.length; i++) {
        if(i === currentSlide) {
            pills[i].className = "w-8 h-2 rounded-full bg-primary-container scale-125 transition-all cursor-pointer";
        } else {
            pills[i].className = "w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer";
        }
    }
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlide();
    resetInterval();
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlide();
    resetInterval();
}

function goToSlide(index) {
    currentSlide = index;
    updateSlide();
    resetInterval();
}

function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 6000);
}
</script>
<script src="assets/js/swiper-bundle.min.js"></script>
<link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
<script>
    if (document.querySelector('.charity-index-slider')) {
        new Swiper('.charity-index-slider', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
