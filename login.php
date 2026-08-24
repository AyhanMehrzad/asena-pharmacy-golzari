<?php
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/SmsService.php';

if (isset($_GET['cancel_signup'])) {
    unset($_SESSION['signup_data']);
    header("Location: login.php");
    exit;
}


// Generate OAuth URLs
$google_oauth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'response_type' => 'code',
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'scope' => 'email profile',
    'access_type' => 'online'
]);

$apple_oauth_url = "https://appleid.apple.com/auth/authorize?" . http_build_query([
    'response_type' => 'code id_token',
    'client_id' => APPLE_CLIENT_ID,
    'redirect_uri' => APPLE_REDIRECT_URI,
    'scope' => 'name email',
    'response_mode' => 'form_post'
]);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/functions.php';
    $mode = $_POST['mode'] ?? 'login';
    
    if ($mode === 'verify_signup') {
        $otp = trim($_POST['otp'] ?? '');
        if (isset($_SESSION['signup_data'])) {
            if ($otp === $_SESSION['signup_data']['otp']) {
                $phone = $_SESSION['signup_data']['phone'];
                $name = $_SESSION['signup_data']['name'];
                $password = $_SESSION['signup_data']['password'];
                
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (phone, name, password, loyalty_points) VALUES (?, ?, ?, 50)");
                if ($stmt->execute([$phone, $name, $hash])) {
                    $user_id = $pdo->lastInsertId();
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_role'] = 'user';
                    
                    $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                    
                    unset($_SESSION['signup_data']);
                    unset($_SESSION['mock_otp']);
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'خطا در ثبت کاربر.';
                }
            } else {
                $error = 'کد وارد شده اشتباه است.';
            }
        } else {
            $error = 'نشست شما منقضی شده است. لطفاً دوباره ثبت نام کنید.';
        }
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            $error = 'لطفاً تمامی فیلدها را پر کنید.';
        } else {
            $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
            if ($rate_error) {
                $error = $rate_error;
            } else {
                if ($mode === 'signup') {
                    $name = trim($_POST['name'] ?? '');
                    
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$phone]);
                    if ($stmt->rowCount() > 0) {
                        $error = 'این شماره موبایل/ایمیل قبلاً ثبت شده است.';
                    } else {
                        $otp = sprintf("%06d", mt_rand(100000, 999999));
                        $sms = new SmsService();
                        $sms->sendOtp($phone, $otp);
                        
                        $_SESSION['signup_data'] = [
                            'phone' => $phone,
                            'name' => $name,
                            'password' => $password,
                            'otp' => $otp
                        ];
                        $_SESSION['mock_otp'] = $otp;
                    }
                } elseif ($mode === 'login') {
                    $stmt = $pdo->prepare("SELECT id, role, password FROM users WHERE phone = ?");
                    $stmt->execute([$phone]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                        
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = 'اطلاعات ورود اشتباه است.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>ASENA | ورود و ثبت‌نام</title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/material-symbols.css" rel="stylesheet"/>
<link href="assets/css/geist.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="bg-surface-container-lowest overflow-hidden">
<main class="min-h-screen w-full flex flex-row items-stretch">
<!-- Hero Section -->
<section class="hidden lg:flex lg:w-7/12 relative overflow-hidden bg-primary-container items-center justify-center">
<div class="relative z-10 p-24 max-w-2xl text-white">
<div class="mb-8">
<span class="inline-block px-4 py-1 rounded-full bg-secondary-container text-white font-bold text-sm mb-4">
                        خدمات متمایز حیوانات خانگی
                    </span>
<h1 class="text-4xl font-bold mb-6 leading-tight">
                        مراقبتی هوشمندانه برای همراهان همیشگی شما
                    </h1>
<p class="text-lg opacity-90 leading-relaxed">
                        به جامعه بزرگ ASENA بپیوندید. جایی که تکنولوژی و عشق به حیوانات با هم تلاقی می‌کنند تا بهترین تجربه درمانی و رفاهی را فراهم آورند.
                    </p>
</div>
<!-- Feature Bento Mini -->
<div class="grid grid-cols-2 gap-4 mt-12">
<div class="bg-white/10 backdrop-blur-md p-6 rounded-xl border border-white/20">
<span class="material-symbols-outlined text-secondary-container mb-2">medical_services</span>
<div class="text-lg font-bold text-white">پرونده پزشکی</div>
<div class="text-sm text-white/70">دسترسی آنی به سوابق سلامت</div>
</div>
<div class="bg-white/10 backdrop-blur-md p-6 rounded-xl border border-white/20">
<span class="material-symbols-outlined text-secondary-container mb-2">calendar_month</span>
<div class="text-lg font-bold text-white">نوبت‌دهی آنلاین</div>
<div class="text-sm text-white/70">رزرو سریع با متخصصین مجرب</div>
</div>
</div>
</div>
<!-- Absolute decorative image -->
<div class="absolute bottom-[-10%] right-[-5%] w-[60%] aspect-square opacity-20 pointer-events-none">
<div class="w-full h-full bg-contain bg-no-repeat bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCFE6vx2k_Wfe0zaVbd1QQVGRwjl1g48-Zok8ZB6fCDowYjiuFFCql4FyJUgSd0ofs9o5ol-R-J6ct5C0_1wyElNn88XtUgWJqe8J3ebcR-1ms6GCh3BmnJva9J2aeDnbbq8Co7HxGZxmdoCC4tqG7QD6gKyYaMqtHXlJ9rC-7bFiHK0sIj1SoxluAQUWWRDWo1XYJrqcNiD3tmNJv6M5ota4_YTLdkfYDFxeDM_kYrYx2NZv0vlaCG')"></div>
</div>
</section>

<!-- Authentication Form Section -->
<section class="w-full lg:w-5/12 bg-white flex flex-col justify-center px-8 md:px-16 lg:px-24 py-12 relative overflow-y-auto">
<!-- Mobile Top Bar -->
<div class="absolute top-8 right-8 lg:right-24 flex items-center gap-2">
<a href="index.php" class="text-primary-container font-bold text-xl hover:opacity-80 transition-opacity">ASENA</a>
<div class="w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center">
<span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">pets</span>
</div>
</div>
<!-- Form Container -->
<?php if(isset($_SESSION['signup_data'])): ?>
<div class="max-w-md w-full mx-auto" id="auth-container">
    <div class="mb-12 mt-12 lg:mt-0">
        <h2 class="text-3xl font-bold text-on-surface mb-2">تایید شماره موبایل</h2>
        <p class="text-sm text-on-surface-variant">کد ۶ رقمی ارسال شده به <?php echo htmlspecialchars($_SESSION['signup_data']['phone']); ?> را وارد کنید.</p>
    </div>
    
    <?php if(isset($_SESSION['mock_otp'])): ?>
        <div class="flex items-start gap-3 bg-blue-50/80 border border-blue-200 text-blue-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                <span class="material-symbols-outlined text-blue-600 text-[18px]">sms</span>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm text-blue-900">پیامک تایید (نسخه آزمایشی)</h4>
                <p class="text-xs opacity-90 mt-1 leading-relaxed font-bold tracking-wider">
                    <?php echo htmlspecialchars($_SESSION['mock_otp']); unset($_SESSION['mock_otp']); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm text-red-900">خطا</h4>
                <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <form class="space-y-5" method="POST" action="login.php">
        <input type="hidden" name="mode" value="verify_signup" />
        <div class="input-group">
            <label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">کد تایید ۶ رقمی</label>
            <input name="otp" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-center tracking-widest text-lg dir-ltr" placeholder="------" type="text" maxlength="6" required autofocus/>
        </div>
        
        <button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
            تایید و عضویت
        </button>
        <div class="text-center mt-6">
            <a class="font-bold text-sm text-secondary hover:underline" href="login.php?cancel_signup=1">تغییر شماره / لغو</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="max-w-md w-full mx-auto" id="auth-container">
<!-- Toggle Header -->
<div class="mb-12 mt-12 lg:mt-0">
<h2 class="text-3xl font-bold text-on-surface mb-2" id="form-title">خوش آمدید</h2>
<p class="text-sm text-on-surface-variant" id="form-subtitle">لطفاً برای ورود به پنل کاربری اطلاعات خود را وارد کنید.</p>
<div class="flex mt-8 p-1 bg-surface-container-low rounded-xl">
<button class="flex-1 py-3 rounded-lg font-bold text-sm transition-all duration-300 bg-white shadow-sm text-primary" id="btn-login" onclick="toggleMode('login')">ورود</button>
<button class="flex-1 py-3 rounded-lg font-bold text-sm transition-all duration-300 text-on-surface-variant hover:text-on-surface" id="btn-signup" onclick="toggleMode('signup')">ثبت‌نام</button>
</div>
</div>

<?php if($error): ?>
    <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
            <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-sm text-red-900">خطا در ورود</h4>
            <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['login_success'])): ?>
    <div class="flex items-start gap-3 bg-emerald-50/80 border border-emerald-200 text-emerald-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
            <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-sm text-emerald-900">عملیات موفق</h4>
            <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($_SESSION['login_success']); unset($_SESSION['login_success']); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Input Fields -->
<form class="space-y-5" method="POST" action="login.php">
<input type="hidden" name="mode" id="form-mode" value="<?php echo htmlspecialchars($_POST['mode'] ?? 'login'); ?>" />

<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">شماره موبایل</label>
<div class="relative">
<input name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="0912..." type="text" required/>
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">smartphone</span>
</div>
</div>

<div class="space-y-5 <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? '' : 'hidden'; ?>" id="signup-fields">
<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2">نام و نام خانوادگی</label>
<input name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" placeholder="نام شما" type="text"/>
</div>
</div>

<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">رمز عبور</label>
<div class="relative">
<input name="password" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="••••••••" type="password" required/>
<button class="absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="const p = this.previousElementSibling; p.type = p.type === 'password' ? 'text' : 'password';">
<span class="material-symbols-outlined">visibility</span>
</button>
</div>
</div>

<div class="flex items-center justify-between py-2 <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? 'hidden' : ''; ?>" id="login-extras">
<label class="flex items-center gap-2 cursor-pointer group">
<input class="rounded border-outline-variant text-primary focus:ring-primary-container w-4 h-4" type="checkbox"/>
<span class="font-bold text-sm text-on-surface-variant group-hover:text-on-surface transition-colors">مرا به خاطر بسپار</span>
</label>
<a class="font-bold text-sm text-secondary hover:underline" href="forgot_password.php">فراموشی رمز عبور؟</a>
</div>

<button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
<span id="submit-text"><?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? 'ایجاد حساب کاربری' : 'ورود به حساب'; ?></span>
</button>
</form>
<?php endif; ?>

<?php if(!isset($_SESSION['signup_data'])): ?>
<!-- Divider -->
<div class="relative my-10 text-center">
<div class="absolute inset-0 flex items-center"><div class="w-full border-t border-surface-container-high"></div></div>
<span class="relative px-4 bg-white text-on-surface-variant font-bold text-xs">یا ورود از طریق</span>
</div>

<!-- Social Logins -->
<div class="grid grid-cols-2 gap-4">
<a href="<?php echo htmlspecialchars($google_oauth_url); ?>" class="flex items-center justify-center gap-3 h-12 border border-outline-variant rounded-lg hover:bg-surface-container-low transition-all font-bold text-sm text-on-surface cursor-pointer">
<svg class="w-5 h-5" viewbox="0 0 24 24">
<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"></path>
</svg>
                        گوگل
                    </a>
<a href="<?php echo htmlspecialchars($apple_oauth_url); ?>" class="flex items-center justify-center gap-3 h-12 border border-outline-variant rounded-lg hover:bg-surface-container-low transition-all font-bold text-sm text-on-surface cursor-pointer">
<svg class="w-5 h-5" fill="currentColor" viewbox="0 0 24 24">
<path d="M17.05 20.28c-.96.95-2.06 1.72-3.32 1.72-1.18 0-1.6-.74-2.95-.74-1.37 0-1.87.72-2.96.72-1.2 0-2.2-.76-3.19-1.72-2.01-1.96-3.53-5.54-3.53-8.8 0-3.3 1.6-5.06 3.19-5.06 1.03 0 1.83.6 2.65.6.83 0 1.4-.6 2.62-.6 1.34 0 2.5.76 3.1 1.72-2.73 1.65-2.28 5.6.43 6.7-.6 1.43-1.35 2.83-2.54 3.76zm-3.54-15.65c.6-.73 1-1.74 1-2.75 0-.14-.02-.28-.04-.41-.95.04-2.1.64-2.78 1.43-.6.7-.85 1.65-.85 2.65 0 .15.02.3.06.41.05 0 .1 0 .15 0 .9 0 1.9-.45 2.46-1.33z"></path>
</svg>
                        اپل
                    </a>
</div>
<?php endif; ?>
</div>
<!-- Footer Links -->
<div class="mt-8 pb-8 text-center w-full">
<div class="flex flex-wrap justify-center gap-6 font-bold text-sm text-outline">
<a class="hover:text-primary transition-colors" href="#">قوانین و مقررات</a>
<a class="hover:text-primary transition-colors" href="#">حریم خصوصی</a>
<a class="hover:text-primary transition-colors" href="#">پشتیبانی</a>
<span class="mr-auto hidden md:inline opacity-60">© 2024 ASENA Iran</span>
</div>
</div>
</section>
</main>
<script src="assets/js/login.js"></script>
</body>
</html>