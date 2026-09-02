<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/SmsService.php';

$error = '';
$success = '';
$phone = $_GET['phone'] ?? ($_POST['phone'] ?? '');

if (empty($phone)) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
        if ($rate_error) {
            $error = $rate_error;
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->rowCount() > 0) {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $stmt = $pdo->prepare("UPDATE users SET sms_code = ? WHERE phone = ?");
                if ($stmt->execute([$otp, $phone])) {
                    $sms = new SmsService($pdo);
                    $sms->sendOtp($phone, $otp);
                    $success = 'ع©ط¯ طھط£غŒغŒط¯ ط¬ط¯غŒط¯ ظ¾غŒط§ظ…ع© ط´ط¯.';
                } else {
                    $error = 'ط®ط·ط§ ط¯ط± ط³غŒط³طھظ…. ظ„ط·ظپط§ظ‹ ط¯ظˆط¨ط§ط±ظ‡ طھظ„ط§ط´ ع©ظ†غŒط¯.';
                }
            } else {
                $error = 'ع©ط§ط±ط¨ط±غŒ غŒط§ظپطھ ظ†ط´ط¯.';
            }
        }
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $password = $_POST['password'] ?? '';
    
    if (empty($otp) || empty($password)) {
        $error = 'ظ„ط·ظپط§ظ‹ طھظ…ط§ظ…غŒ ظپغŒظ„ط¯ظ‡ط§ ط±ط§ ظ¾ط± ع©ظ†غŒط¯.';
    } else {
        $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
        if ($rate_error) {
            $error = $rate_error;
        } else {
            $stmt = $pdo->prepare("SELECT id, sms_code FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = 'ع©ط§ط±ط¨ط±غŒ ط¨ط§ ط§غŒظ† ظ…ط´ط®طµط§طھ غŒط§ظپطھ ظ†ط´ط¯.';
            } elseif (empty($user['sms_code']) || $user['sms_code'] !== $otp) {
                $error = 'ع©ط¯ طھط£غŒغŒط¯ ظˆط§ط±ط¯ ط´ط¯ظ‡ ظ†ط§ظ…ط¹طھط¨ط± ط§ط³طھ.';
            } else {
                // Success! Reset password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, sms_code = NULL WHERE id = ?");
                if ($stmt->execute([$hash, $user['id']])) {
                    // Optional: Automatically log them in
                    // $_SESSION['user_id'] = $user['id'];
                    
                    // For now, redirect to login with success message in session or query string
                    $_SESSION['login_success'] = 'ط±ظ…ط² ط¹ط¨ظˆط± ط´ظ…ط§ ط¨ط§ ظ…ظˆظپظ‚غŒطھ طھط؛غŒغŒط± ع©ط±ط¯. ظ„ط·ظپط§ظ‹ ظˆط§ط±ط¯ ط´ظˆغŒط¯.';
                    header("Location: login.php?reset=success");
                    exit;
                } else {
                    $error = 'ط®ط·ط§ ط¯ط± طھط؛غŒغŒط± ط±ظ…ط² ط¹ط¨ظˆط±. ظ„ط·ظپط§ظ‹ ط¯ظˆط¨ط§ط±ظ‡ طھظ„ط§ط´ ع©ظ†غŒط¯.';
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
<title>ASENA | ط¨ط§ط²ظ†ط´ط§ظ†غŒ ط±ظ…ط² ط¹ط¨ظˆط±</title>
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
                طھط£غŒغŒط¯ ظ‡ظˆغŒطھ
            </span>
            <h1 class="text-4xl font-bold mb-6 leading-tight">
                ط§غŒط¬ط§ط¯ ط±ظ…ط² ط¹ط¨ظˆط± ط¬ط¯غŒط¯
            </h1>
            <p class="text-lg opacity-90 leading-relaxed">
                ظ„ط·ظپط§ظ‹ ع©ط¯ غ¶ ط±ظ‚ظ…غŒ ظ¾غŒط§ظ…ع© ط´ط¯ظ‡ ط±ط§ ط¨ظ‡ ظ‡ظ…ط±ط§ظ‡ ط±ظ…ط² ط¹ط¨ظˆط± ط¬ط¯غŒط¯ ط®ظˆط¯ ظˆط§ط±ط¯ ع©ظ†غŒط¯ طھط§ ط¨ظ‡ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ ط®ظˆط¯ ط¯ط³طھط±ط³غŒ ظ¾غŒط¯ط§ ع©ظ†غŒط¯.
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
    <!-- Mobile Top Bar -->
    <div class="absolute top-8 right-8 lg:right-24 flex items-center gap-2">
        <a href="index.php" class="text-primary-container font-bold text-xl hover:opacity-80 transition-opacity">ASENA</a>
        <div class="w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">pets</span>
        </div>
    </div>
    
    <div class="max-w-md w-full mx-auto">
        <div class="mb-12 mt-12 lg:mt-0">
            <h2 class="text-3xl font-bold text-on-surface mb-2">ط¨ط§ط²ظ†ط´ط§ظ†غŒ ط±ظ…ط² ط¹ط¨ظˆط±</h2>
            <p class="text-sm text-on-surface-variant leading-relaxed">ط¨ط±ط§غŒ ط´ظ…ط§ط±ظ‡ <?php echo htmlspecialchars($phone); ?></p>
        </div>

        <?php if($success): ?>
            <div class="flex items-start gap-3 bg-emerald-50/80 border border-emerald-200 text-emerald-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                    <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-sm text-emerald-900">ظ…ظˆظپظ‚غŒطھ</h4>
                    <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                    <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-sm text-red-900">ط®ط·ط§</h4>
                    <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Input Fields -->
        <form class="space-y-6" method="POST" action="reset_password.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>" />
            
            <div class="input-group">
                <label class="block font-bold text-sm text-on-surface-variant mb-2">ع©ط¯ طھط£غŒغŒط¯ غ¶ ط±ظ‚ظ…غŒ</label>
                <div class="relative">
                    <input name="otp" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-center tracking-widest text-lg dir-ltr" placeholder="------" type="text" maxlength="6" required/>
                </div>
            </div>

            <div class="input-group">
                <label class="block font-bold text-sm text-on-surface-variant mb-2">ط±ظ…ط² ط¹ط¨ظˆط± ط¬ط¯غŒط¯</label>
                <div class="relative">
                    <input name="password" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" type="password" required/>
                    <button class="absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="const p = this.previousElementSibling; p.type = p.type === 'password' ? 'text' : 'password';">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
                طھط؛غŒغŒط± ط±ظ…ط² ط¹ط¨ظˆط±
            </button>
            
            <div class="text-center mt-4">
                <button type="button" id="resend-btn" onclick="document.getElementById('resend-form').submit();" class="text-sm font-bold text-secondary hover:underline disabled:opacity-50 disabled:no-underline" disabled>
                    ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯ (<span id="countdown">120</span> ط«ط§ظ†غŒظ‡)
                </button>
            </div>
            
            <div class="text-center mt-6">
                <a class="font-bold text-sm text-secondary hover:underline" href="login.php">ط¨ط§ط²ع¯ط´طھ ط¨ظ‡ طµظپط­ظ‡ ظˆط±ظˆط¯</a>
            </div>
        </form>

        <form id="resend-form" method="POST" action="reset_password.php" class="hidden">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>" />
            <input type="hidden" name="action" value="resend" />
        </form>
    </div>
</section>
</main>
<script>
    let timeLeft = 120;
    const countdownEl = document.getElementById('countdown');
    const resendBtn = document.getElementById('resend-btn');

    if (countdownEl && resendBtn) {
        const timer = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timer);
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯';
            } else {
                countdownEl.innerText = timeLeft;
            }
        }, 1000);
    }
</script>
</body>
</html>

