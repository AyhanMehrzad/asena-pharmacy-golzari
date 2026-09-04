<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/SmsService.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawPhone = trim($_POST['phone'] ?? '');
    $phone = SmsService::normalizePhone($rawPhone);
    
    if (empty($phone)) {
        $error = 'لطفاً شماره موبایل معتبر خود را وارد کنید.';
    } else {
        $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
        if ($rate_error) {
            $error = $rate_error;
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            
            if ($stmt->rowCount() === 0) {
                $error = 'کاربری با این شماره موبایل یافت نشد.';
            } else {
                // Generate a 6-digit OTP
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                
                $_SESSION['reset_password_data'] = [
                    'phone'      => $phone,
                    'otp'        => $otp,
                    'expires_at' => time() + 180 // Valid for 3 minutes
                ];
                
                $stmt = $pdo->prepare("UPDATE users SET sms_code = ? WHERE phone = ?");
                if ($stmt->execute([$otp, $phone])) {
                    $sms = new SmsService();
                    $sms->sendOtp($phone, $otp);
                    
                    header("Location: reset_password.php?phone=" . urlencode($phone));
                    exit;
                } else {
                    $error = 'خطا در سیستم. لطفاً دوباره تلاش کنید.';
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
<title>ASENA | فراموشی رمز عبور</title>
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
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
                بازیابی امن رمز عبور
            </h1>
            <p class="text-lg opacity-90 leading-relaxed">
                رمز عبور خود را فراموش کرده‌اید؟ نگران نباشید. با وارد کردن شماره موبایل خود، رمز عبور جدیدی تنظیم کنید و به راحتی به حساب کاربری خود بازگردید.
            </p>
        </div>
    </div>
    <!-- Absolute decorative image -->
    <div class="absolute bottom-[-10%] right-[-5%] w-[60%] aspect-square opacity-20 pointer-events-none">
        <div class="w-full h-full bg-contain bg-no-repeat bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCFE6vx2k_Wfe0zaVbd1QQVGRwjl1g48-Zok8ZB6fCDowYjiuFFCql4FyJUgSd0ofs9o5ol-R-J6ct5C0_1wyElNn88XtUgWJqe8J3ebcR-1ms6GCh3BmnJva9J2aeDnbbq8Co7HxGZxmdoCC4tqG7QD6gKyYaMqtHXlJ9rC-7bFiHK0sIj1SoxluAQUWWRDWo1XYJrqcNiD3tmNJv6M5ota4_YTLdkfYDFxeDM_kYrYx2NZv0vlaCG')"></div>
    </div>
</section>

<!-- Authentication Form Section -->
<section class="w-full lg:w-5/12 bg-white flex flex-col justify-center px-8 md:px-16 lg:px-24 py-12 relative overflow-y-auto">
    <a href="index.php" class="absolute top-8 right-8 lg:right-24 flex items-center gap-3 group" title="بازگشت به صفحه اصلی">
        <span class="text-primary-container font-bold text-xl group-hover:opacity-80 transition-opacity">ASENA</span>
        <div class="w-10 h-10 bg-primary-container/10 border border-primary-container/20 rounded-xl p-1.5 flex items-center justify-center group-hover:scale-105 transition-all shadow-sm">
            <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-full h-full object-contain">
        </div>
    </a>
    <!-- Form Container -->
    <div class="max-w-md w-full mx-auto">
        <div class="mb-12 mt-12 lg:mt-0">
            <h2 class="text-3xl font-bold text-on-surface mb-2">فراموشی رمز عبور</h2>
            <p class="text-sm text-on-surface-variant leading-relaxed">لطفاً شماره موبایل یا ایمیل متصل به حساب کاربری خود را وارد کنید تا کد تأیید برای شما ارسال شود.</p>
        </div>

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

        <!-- Input Fields -->
        <form class="space-y-6" method="POST" action="forgot_password.php">
            <?php echo csrf_field(); ?>
            <div class="input-group">
                <label class="block font-bold text-sm text-on-surface-variant mb-2">ایمیل یا شماره موبایل</label>
                <div class="relative">
                    <input name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="0912..." type="text" required/>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">smartphone</span>
                </div>
            </div>

            <button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
                ارسال کد تأیید
            </button>
            
            <div class="text-center mt-6">
                <a class="font-bold text-sm text-secondary hover:underline" href="login.php">بازگشت به صفحه ورود</a>
            </div>
        </form>
    </div>
</section>
</main>
</body>
</html>
