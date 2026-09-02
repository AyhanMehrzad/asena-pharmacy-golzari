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
        $phone_input = trim($_POST['phone'] ?? '');
        if (empty($phone_input) || strlen($phone_input) < 10) {
            $error = 'ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ظ†ط§ظ…ط¹طھط¨ط± ط§ط³طھ.';
        } else {
            // Check if phone already used by someone else
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $check->execute([$phone_input, $user_id]);
            if ($check->rowCount() > 0) {
                $error = 'ط§غŒظ† ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ظ‚ط¨ظ„ط§ظ‹ ط«ط¨طھ ط´ط¯ظ‡ ط§ط³طھ.';
            } else {
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $_SESSION['oauth_otp_code'] = $otp;
                $_SESSION['oauth_phone_pending'] = $phone_input;
                
                $sms = new SmsService();
                $sms->sendOtp($phone_input, $otp);
                
                $step = 2;
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        $pending_phone = $_SESSION['oauth_phone_pending'] ?? '';
        if (!empty($pending_phone)) {
            $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $pending_phone);
            if ($rate_error) {
                $error = $rate_error;
            } else {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $_SESSION['oauth_otp_code'] = $otp;
                $sms = new SmsService();
                $sms->sendOtp($pending_phone, $otp);
                $success = 'ع©ط¯ طھط§غŒغŒط¯ ط¬ط¯غŒط¯ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط¨ط±ط§غŒ ط´ظ…ط§ط±ظ‡ ' . htmlspecialchars($pending_phone) . ' ط§ط±ط³ط§ظ„ ط´ط¯.';
            }
        }
        $step = 2;
    } elseif (isset($_POST['verify_otp'])) {
        // Step 2: Verify OTP
        $otp_input = trim($_POST['otp'] ?? '');
        $expected_otp = $_SESSION['oauth_otp_code'] ?? '';
        $pending_phone = $_SESSION['oauth_phone_pending'] ?? '';
        
        if (empty($otp_input) || $otp_input !== $expected_otp) {
            $error = 'ع©ط¯ ظˆط§ط±ط¯ ط´ط¯ظ‡ ظ†ط§ظ…ط¹طھط¨ط± ط§ط³طھ.';
            $step = 2; // Stay on step 2
        } else {
            // Success
            $_SESSION['oauth_phone_verified'] = true;
            $step = 3;
            
            unset($_SESSION['oauth_otp_code']);
        }
    } elseif (isset($_POST['save_address'])) {
        // Step 3: Save Address
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $pending_phone = $_SESSION['oauth_phone_pending'] ?? '';
        
        if (empty($city) || empty($address)) {
            $error = 'ط´ظ‡ط± ظˆ ط¢ط¯ط±ط³ ط§ظ„ط²ط§ظ…غŒ ط§ط³طھ.';
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
    <title>ASENA | طھع©ظ…غŒظ„ ظ¾ط±ظˆظپط§غŒظ„</title>
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
            <h1 class="text-4xl font-bold mb-6 leading-tight">طھع©ظ…غŒظ„ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ</h1>
            <p class="text-lg opacity-90 leading-relaxed">
                ط¨ط±ط§غŒ ط§ط³طھظپط§ط¯ظ‡ ع©ط§ظ…ظ„ ط§ط² ط®ط¯ظ…ط§طھ ظ…ط§ ظˆ ط¯ط±غŒط§ظپطھ ط³ظپط§ط±ط´ط§طھطŒ ظ„ط·ظپط§ظ‹ ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ظˆ ط§ط·ظ„ط§ط¹ط§طھ ط¢ط¯ط±ط³ ط®ظˆط¯ ط±ط§ طھع©ظ…غŒظ„ ع©ظ†غŒط¯.
            </p>
        </div>
    </section>

    <!-- Authentication Form Section -->
    <section class="w-full lg:w-5/12 bg-white flex flex-col justify-center px-8 md:px-16 lg:px-24 py-12 relative overflow-y-auto">
        <div class="max-w-md w-full mx-auto">
            <h2 class="text-3xl font-bold text-on-surface mb-2">
                <?php echo $step === 3 ? 'ط§ط·ظ„ط§ط¹ط§طھ ط¢ط¯ط±ط³' : 'طھط£غŒغŒط¯ ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„'; ?>
            </h2>
            <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">
                <?php 
                if ($step === 3) echo 'ظ„ط·ظپط§ظ‹ ط¨ط±ط§غŒ ط§ط±ط³ط§ظ„ ط³ظپط§ط±ط´ط§طھطŒ ط¢ط¯ط±ط³ ط®ظˆط¯ ط±ط§ ظˆط§ط±ط¯ ع©ظ†غŒط¯.';
                else echo 'ظˆط±ظˆط¯ ظ…ظˆظپظ‚غŒطھâ€Œط¢ظ…غŒط² ط¨ظˆط¯. ظ„ط·ظپط§ ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ط®ظˆط¯ ط±ط§ طھط§غŒغŒط¯ ع©ظ†غŒط¯.'; 
                ?>
            </p>

            <?php if($error): ?>
                <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
                    <div class="flex-1">
                        <p class="text-sm opacity-90 leading-relaxed font-bold"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="flex items-start gap-3 bg-emerald-50/80 border border-emerald-200 text-emerald-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm opacity-90 leading-relaxed font-bold text-emerald-900"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„</label>
                    <input name="phone" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="09123456789" type="text" required/>
                </div>
                <button type="submit" name="send_otp" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">ط§ط±ط³ط§ظ„ ع©ط¯ طھط§غŒغŒط¯</button>
            </form>
            <?php elseif ($step === 2): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">ع©ط¯ طھط£غŒغŒط¯ ظ¾غŒط§ظ…ع© ط´ط¯ظ‡</label>
                    <input name="otp" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-center tracking-widest text-lg dir-ltr" placeholder="------" type="text" maxlength="6" required autofocus/>
                </div>
                <button type="submit" name="verify_otp" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">طھط£غŒغŒط¯ ظ…ظˆط¨ط§غŒظ„</button>

                <div class="flex items-center justify-between mt-4 pt-2 border-t border-outline-variant/30">
                    <button type="button" id="resend-profile-btn" onclick="document.getElementById('resend-profile-form').submit();" class="text-sm font-bold text-secondary hover:underline disabled:opacity-50 disabled:no-underline flex items-center gap-1.5" disabled>
                        <span class="material-symbols-outlined text-[16px]">refresh</span>
                        <span>ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯ (<span id="profile-countdown">60</span> ط«ط§ظ†غŒظ‡)</span>
                    </button>
                    <a class="font-bold text-sm text-on-surface-variant hover:text-primary transition-colors" href="complete_profile.php">طھط؛غŒغŒط± ط´ظ…ط§ط±ظ‡</a>
                </div>
            </form>

            <form id="resend-profile-form" method="POST" action="complete_profile.php" class="hidden">
                <input type="hidden" name="resend_otp" value="1" />
            </form>
            <?php elseif ($step === 3): ?>
            <form class="space-y-6" method="POST">
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">ط´ظ‡ط±</label>
                    <input name="city" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" placeholder="ظ…ط«ط§ظ„: طھظ‡ط±ط§ظ†" type="text" required/>
                </div>
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">ع©ط¯ ظ¾ط³طھغŒ (ط§ط®طھغŒط§ط±غŒ)</label>
                    <input name="postal_code" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="1234567890" type="text"/>
                </div>
                <div class="input-group">
                    <label class="block font-bold text-sm text-on-surface-variant mb-2">ط¢ط¯ط±ط³ ط¯ظ‚غŒظ‚</label>
                    <textarea name="address" class="w-full p-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" rows="3" placeholder="ط®غŒط§ط¨ط§ظ†طŒ ع©ظˆع†ظ‡طŒ ظ¾ظ„ط§ع©..." required></textarea>
                </div>
                <button type="submit" name="save_address" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all shadow-lg">ط«ط¨طھ ط§ط·ظ„ط§ط¹ط§طھ ظˆ ظˆط±ظˆط¯</button>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>
<script>
    const profileCountdownEl = document.getElementById('profile-countdown');
    const resendProfileBtn = document.getElementById('resend-profile-btn');
    if (profileCountdownEl && resendProfileBtn) {
        let timeLeft = 60;
        const timer = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timer);
                resendProfileBtn.disabled = false;
                resendProfileBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">refresh</span><span>ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯ ظ¾غŒط§ظ…ع©غŒ</span>';
            } else {
                profileCountdownEl.innerText = timeLeft;
            }
        }, 1000);
    }
</script>
</body>
</html>

