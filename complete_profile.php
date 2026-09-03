<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/SmsService.php';

// User must be logged in (from OAuth) but might have a dummy phone number starting with '09OAUTH'
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT phone, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['oauth_phone_verified'])) {
    $step = 3;
} elseif (isset($_SESSION['oauth_otp_code'])) {
    $step = 2;
} else {
    $step = 1;
}

$phone_input = $_POST['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        // Step 1: Send OTP
        $raw_phone = trim($_POST['phone'] ?? '');
        $phone_input = SmsService::normalizePhone($raw_phone);
        if (empty($phone_input) || strlen($phone_input) !== 11) {
            $error = 'شماره موبایل نامعتبر است.';
        } else {
            // Check if phone already used by someone else
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $check->execute([$phone_input, $user_id]);
            if ($check->rowCount() > 0) {
                $error = 'این شماره موبایل قبلاً ثبت شده است.';
            } else {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $_SESSION['oauth_otp_code'] = $otp;
                $_SESSION['oauth_phone_pending'] = $phone_input;
                $_SESSION['oauth_otp_expires_at'] = time() + 180; // Valid for 3 minutes
                
                $sms = new SmsService();
                $sms->sendOtp($phone_input, $otp);
                
                $step = 2;
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        // Step 2: Verify OTP
        $otp_input = SmsService::sanitizeCode($_POST['otp'] ?? '');
        $expected_otp = $_SESSION['oauth_otp_code'] ?? '';
        $pending_phone = $_SESSION['oauth_phone_pending'] ?? '';
        $expiresAt = $_SESSION['oauth_otp_expires_at'] ?? 0;
        
        if (time() > $expiresAt) {
            $error = 'کد وارد شده منقضی شده است. لطفاً مجدداً درخواست ارسال کد دهید.';
            $step = 2;
        } elseif (empty($otp_input) || $otp_input !== $expected_otp) {
            $error = 'کد وارد شده نامعتبر است.';
            $step = 2; // Stay on step 2
        } else {
            // Success
            $_SESSION['oauth_phone_verified'] = true;
            $step = 3;
            
            unset($_SESSION['oauth_otp_code']);
            unset($_SESSION['oauth_otp_expires_at']);
        }
    } elseif (isset($_POST['save_address'])) {
        // Step 3: Save Address
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pending_phone = $_SESSION['oauth_phone_pending'] ?? '';
        
        if (empty($city) || empty($address)) {
            $error = 'شهر و آدرس الزامی است.';
            $step = 3;
        } else {
            // Save everything
            $update = $pdo->prepare("UPDATE users SET phone = ?, city = ?, postal_code = ?, address = ? WHERE id = ?");
            $update->execute([$pending_phone, $city, $postal_code, $address, $user_id]);
            
            unset($_SESSION['oauth_phone_verified']);
            unset($_SESSION['oauth_phone_pending']);
            
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>ASENA | تکمیل پروفایل</title>
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
            <h1 class="text-4xl font-bold mb-6 leading-tight">تکمیل حساب کاربری</h1>
            <p class="text-lg opacity-90 leading-relaxed">
                برای استفاده کامل از خدمات ما و دریافت سفارشات، لطفاً شماره موبایل و اطلاعات آدرس خود را تکمیل کنید.
            </p>
        </div>
    </section>

    <!-- Authentication Form Section -->
    <section class="w-full lg:w-5/12 bg-white flex flex-col justify-center px-8 md:px-16 lg:px-24 py-12 relative overflow-y-auto">
        <div class="max-w-md w-full mx-auto">
            <h2 class="text-3xl font-bold text-on-surface mb-2">
                <?php echo $step === 3 ? 'اطلاعات آدرس' : 'تأیید شماره موبایل'; ?>
            </h2>
            <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">
                <?php 
                if ($step === 3) echo 'لطفاً برای ارسال سفارشات، آدرس خود را وارد کنید.';
                else echo 'ورود موفقیت‌آمیز بود. لطفا شماره موبایل خود را تایید کنید.'; 
                ?>
            </p>

            <?php if($error): ?>
                <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
                    <div class="flex-1">
                        <p class="text-sm opacity-90 leading-relaxed font-bold"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">شماره موبایل</label>
                    <input name="phone" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="09123456789" type="text" required/>
                </div>
                <button type="submit" name="send_otp" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">ارسال کد تایید</button>
            </form>
            <?php elseif ($step === 2): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">کد تأیید پیامک شده</label>
                    <input name="otp" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-center tracking-widest text-lg dir-ltr" placeholder="------" type="text" maxlength="6" required/>
                </div>
                <button type="submit" name="verify_otp" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">تأیید موبایل</button>
            </form>
            <?php elseif ($step === 3): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">شهر</label>
                    <input name="city" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" placeholder="مثال: تهران" type="text" required/>
                </div>
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">کد پستی (اختیاری)</label>
                    <input name="postal_code" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="1234567890" type="text"/>
                </div>
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">آدرس دقیق</label>
                    <textarea name="address" class="w-full p-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" rows="3" placeholder="خیابان، کوچه، پلاک..." required></textarea>
                </div>
                <button type="submit" name="save_address" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">ثبت اطلاعات و ورود</button>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
