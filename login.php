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
    
    if ($mode === 'resend_signup_otp') {
        if (isset($_SESSION['signup_data'])) {
            $phone = $_SESSION['signup_data']['phone'];
            $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
            if ($rate_error) {
                $error = $rate_error;
            } else {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $_SESSION['signup_data']['otp'] = $otp;
                $sms = new SmsService($pdo);
                $sms->sendOtp($phone, $otp);
                $success = 'ع©ط¯ طھط§غŒغŒط¯ ط¬ط¯غŒط¯ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط¨ط±ط§غŒ ط´ظ…ط§ط±ظ‡ ' . htmlspecialchars($phone) . ' ظ¾غŒط§ظ…ع© ط´ط¯.';
            }
        } else {
            $error = 'ظ†ط´ط³طھ ط´ظ…ط§ ظ…ظ†ظ‚ط¶غŒ ط´ط¯ظ‡ ط§ط³طھ. ظ„ط·ظپط§ظ‹ ط¯ظˆط¨ط§ط±ظ‡ ط«ط¨طھ ظ†ط§ظ… ع©ظ†غŒط¯.';
        }
    } elseif ($mode === 'verify_signup') {
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
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'ط®ط·ط§ ط¯ط± ط«ط¨طھ ع©ط§ط±ط¨ط±.';
                }
            } else {
                $error = 'ع©ط¯ ظˆط§ط±ط¯ ط´ط¯ظ‡ ط§ط´طھط¨ط§ظ‡ ط§ط³طھ.';
            }
        } else {
            $error = 'ظ†ط´ط³طھ ط´ظ…ط§ ظ…ظ†ظ‚ط¶غŒ ط´ط¯ظ‡ ط§ط³طھ. ظ„ط·ظپط§ظ‹ ط¯ظˆط¨ط§ط±ظ‡ ط«ط¨طھ ظ†ط§ظ… ع©ظ†غŒط¯.';
        }
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            $error = 'ظ„ط·ظپط§ظ‹ طھظ…ط§ظ…غŒ ظپغŒظ„ط¯ظ‡ط§ ط±ط§ ظ¾ط± ع©ظ†غŒط¯.';
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
                        $error = 'ط§غŒظ† ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„/ط§غŒظ…غŒظ„ ظ‚ط¨ظ„ط§ظ‹ ط«ط¨طھ ط´ط¯ظ‡ ط§ط³طھ.';
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
                        $error = 'ط§ط·ظ„ط§ط¹ط§طھ ظˆط±ظˆط¯ ط§ط´طھط¨ط§ظ‡ ط§ط³طھ.';
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
<title>ASENA | ظˆط±ظˆط¯ ظˆ ط«ط¨طھâ€Œظ†ط§ظ…</title>
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
                        ط®ط¯ظ…ط§طھ ظ…طھظ…ط§غŒط² ط­غŒظˆط§ظ†ط§طھ ط®ط§ظ†ع¯غŒ
                    </span>
<h1 class="text-4xl font-bold mb-6 leading-tight">
                        ظ…ط±ط§ظ‚ط¨طھغŒ ظ‡ظˆط´ظ…ظ†ط¯ط§ظ†ظ‡ ط¨ط±ط§غŒ ظ‡ظ…ط±ط§ظ‡ط§ظ† ظ‡ظ…غŒط´ع¯غŒ ط´ظ…ط§
                    </h1>
<p class="text-lg opacity-90 leading-relaxed">
                        ط¨ظ‡ ط¬ط§ظ…ط¹ظ‡ ط¨ط²ط±ع¯ ASENA ط¨ظ¾غŒظˆظ†ط¯غŒط¯. ط¬ط§غŒغŒ ع©ظ‡ طھع©ظ†ظˆظ„ظˆعکغŒ ظˆ ط¹ط´ظ‚ ط¨ظ‡ ط­غŒظˆط§ظ†ط§طھ ط¨ط§ ظ‡ظ… طھظ„ط§ظ‚غŒ ظ…غŒâ€Œع©ظ†ظ†ط¯ طھط§ ط¨ظ‡طھط±غŒظ† طھط¬ط±ط¨ظ‡ ط¯ط±ظ…ط§ظ†غŒ ظˆ ط±ظپط§ظ‡غŒ ط±ط§ ظپط±ط§ظ‡ظ… ط¢ظˆط±ظ†ط¯.
                    </p>
</div>
<!-- Feature Bento Mini -->
<div class="grid grid-cols-2 gap-4 mt-12">
<div class="bg-white/10 backdrop-blur-md p-6 rounded-xl border border-white/20">
<span class="material-symbols-outlined text-secondary-container mb-2">medical_services</span>
<div class="text-lg font-bold text-white">ظ¾ط±ظˆظ†ط¯ظ‡ ظ¾ط²ط´ع©غŒ</div>
<div class="text-sm text-white/70">ط¯ط³طھط±ط³غŒ ط¢ظ†غŒ ط¨ظ‡ ط³ظˆط§ط¨ظ‚ ط³ظ„ط§ظ…طھ</div>
</div>
<div class="bg-white/10 backdrop-blur-md p-6 rounded-xl border border-white/20">
<span class="material-symbols-outlined text-secondary-container mb-2">calendar_month</span>
<div class="text-lg font-bold text-white">ظ†ظˆط¨طھâ€Œط¯ظ‡غŒ ط¢ظ†ظ„ط§غŒظ†</div>
<div class="text-sm text-white/70">ط±ط²ط±ظˆ ط³ط±غŒط¹ ط¨ط§ ظ…طھط®طµطµغŒظ† ظ…ط¬ط±ط¨</div>
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
        <h2 class="text-3xl font-bold text-on-surface mb-2">طھط§غŒغŒط¯ ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„</h2>
        <p class="text-sm text-on-surface-variant">ع©ط¯ غ¶ ط±ظ‚ظ…غŒ ط§ط±ط³ط§ظ„ ط´ط¯ظ‡ ط¨ظ‡ <?php echo htmlspecialchars($_SESSION['signup_data']['phone']); ?> ط±ط§ ظˆط§ط±ط¯ ع©ظ†غŒط¯.</p>
    </div>

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

    <?php if($success): ?>
        <div class="flex items-start gap-3 bg-emerald-50/80 border border-emerald-200 text-emerald-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm text-emerald-900">ط§ط±ط³ط§ظ„ ظ…ظˆظپظ‚</h4>
                <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <form class="space-y-5" method="POST" action="login.php">
        <input type="hidden" name="mode" value="verify_signup" />
        <div class="input-group">
            <label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">ع©ط¯ طھط§غŒغŒط¯ غ¶ ط±ظ‚ظ…غŒ</label>
            <input name="otp" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-center tracking-widest text-lg dir-ltr" placeholder="------" type="text" maxlength="6" required autofocus/>
        </div>
        
        <button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
            طھط§غŒغŒط¯ ظˆ ط¹ط¶ظˆغŒطھ
        </button>

        <div class="flex items-center justify-between mt-4 pt-2 border-t border-outline-variant/30">
            <button type="button" id="resend-signup-btn" onclick="document.getElementById('resend-signup-form').submit();" class="text-sm font-bold text-secondary hover:underline disabled:opacity-50 disabled:no-underline flex items-center gap-1.5" disabled>
                <span class="material-symbols-outlined text-[16px]">refresh</span>
                <span>ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯ (<span id="signup-countdown">60</span> ط«ط§ظ†غŒظ‡)</span>
            </button>
            <a class="font-bold text-sm text-on-surface-variant hover:text-primary transition-colors" href="login.php?cancel_signup=1">طھط؛غŒغŒط± ط´ظ…ط§ط±ظ‡ / ط§طµظ„ط§ط­</a>
        </div>
    </form>

    <form id="resend-signup-form" method="POST" action="login.php" class="hidden">
        <input type="hidden" name="mode" value="resend_signup_otp" />
    </form>
</div>
<?php else: ?>
<div class="max-w-md w-full mx-auto" id="auth-container">
<!-- Toggle Header -->
<div class="mb-12 mt-12 lg:mt-0">
<h2 class="text-3xl font-bold text-on-surface mb-2" id="form-title">ط®ظˆط´ ط¢ظ…ط¯غŒط¯</h2>
<p class="text-sm text-on-surface-variant" id="form-subtitle">ظ„ط·ظپط§ظ‹ ط¨ط±ط§غŒ ظˆط±ظˆط¯ ط¨ظ‡ ظ¾ظ†ظ„ ع©ط§ط±ط¨ط±غŒ ط§ط·ظ„ط§ط¹ط§طھ ط®ظˆط¯ ط±ط§ ظˆط§ط±ط¯ ع©ظ†غŒط¯.</p>
<div class="flex mt-8 p-1 bg-surface-container-low rounded-xl">
<button class="flex-1 py-3 rounded-lg font-bold text-sm transition-all duration-300 bg-white shadow-sm text-primary" id="btn-login" onclick="toggleMode('login')">ظˆط±ظˆط¯</button>
<button class="flex-1 py-3 rounded-lg font-bold text-sm transition-all duration-300 text-on-surface-variant hover:text-on-surface" id="btn-signup" onclick="toggleMode('signup')">ط«ط¨طھâ€Œظ†ط§ظ…</button>
</div>
</div>

<?php if($error): ?>
    <div class="flex items-start gap-3 bg-red-50/80 border border-red-200 text-red-800 p-4 rounded-2xl shadow-sm mb-6 backdrop-blur-sm transition-all">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
            <span class="material-symbols-outlined text-red-600 text-[18px]">error</span>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-sm text-red-900">ط®ط·ط§ ط¯ط± ظˆط±ظˆط¯</h4>
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
            <h4 class="font-bold text-sm text-emerald-900">ط¹ظ…ظ„غŒط§طھ ظ…ظˆظپظ‚</h4>
            <p class="text-xs opacity-90 mt-1 leading-relaxed"><?php echo htmlspecialchars($_SESSION['login_success']); unset($_SESSION['login_success']); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Input Fields -->
<form class="space-y-5" method="POST" action="login.php">
<input type="hidden" name="mode" id="form-mode" value="<?php echo htmlspecialchars($_POST['mode'] ?? 'login'); ?>" />

<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„</label>
<div class="relative">
<input name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="0912..." type="text" required/>
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">smartphone</span>
</div>
</div>

<div class="space-y-5 <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? '' : 'hidden'; ?>" id="signup-fields">
<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2">ظ†ط§ظ… ظˆ ظ†ط§ظ… ط®ط§ظ†ظˆط§ط¯ع¯غŒ</label>
<input name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm" placeholder="ظ†ط§ظ… ط´ظ…ط§" type="text"/>
</div>
</div>

<div class="input-group">
<label class="block font-bold text-sm text-on-surface-variant mb-2 transition-colors">ط±ظ…ط² ط¹ط¨ظˆط±</label>
<div class="relative">
<input name="password" class="w-full h-12 pr-4 pl-12 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-surface-container-lowest transition-all text-sm text-left dir-ltr" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" type="password" required/>
<button class="absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-primary transition-colors" type="button" onclick="const p = this.previousElementSibling; p.type = p.type === 'password' ? 'text' : 'password';">
<span class="material-symbols-outlined">visibility</span>
</button>
</div>
</div>

<div class="flex items-center justify-between py-2 <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? 'hidden' : ''; ?>" id="login-extras">
<label class="flex items-center gap-2 cursor-pointer group">
<input class="rounded border-outline-variant text-primary focus:ring-primary-container w-4 h-4" type="checkbox"/>
<span class="font-bold text-sm text-on-surface-variant group-hover:text-on-surface transition-colors">ظ…ط±ط§ ط¨ظ‡ ط®ط§ط·ط± ط¨ط³ظ¾ط§ط±</span>
</label>
<a class="font-bold text-sm text-secondary hover:underline" href="forgot_password.php">ظپط±ط§ظ…ظˆط´غŒ ط±ظ…ط² ط¹ط¨ظˆط±طں</a>
</div>

<button type="submit" class="w-full h-12 bg-primary-container text-white rounded-lg font-bold text-lg hover:bg-primary transition-all active:scale-[0.98] shadow-lg shadow-primary-container/20">
<span id="submit-text"><?php echo (isset($_POST['mode']) && $_POST['mode'] === 'signup') ? 'ط§غŒط¬ط§ط¯ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ' : 'ظˆط±ظˆط¯ ط¨ظ‡ ط­ط³ط§ط¨'; ?></span>
</button>
</form>
<?php endif; ?>

<?php if(!isset($_SESSION['signup_data'])): ?>
<!-- Divider -->
<div class="relative my-10 text-center">
<div class="absolute inset-0 flex items-center"><div class="w-full border-t border-surface-container-high"></div></div>
<span class="relative px-4 bg-white text-on-surface-variant font-bold text-xs">غŒط§ ظˆط±ظˆط¯ ط§ط² ط·ط±غŒظ‚</span>
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
                        ع¯ظˆع¯ظ„
                    </a>
<a href="<?php echo htmlspecialchars($apple_oauth_url); ?>" class="flex items-center justify-center gap-3 h-12 border border-outline-variant rounded-lg hover:bg-surface-container-low transition-all font-bold text-sm text-on-surface cursor-pointer">
<svg class="w-5 h-5" fill="currentColor" viewbox="0 0 24 24">
<path d="M17.05 20.28c-.96.95-2.06 1.72-3.32 1.72-1.18 0-1.6-.74-2.95-.74-1.37 0-1.87.72-2.96.72-1.2 0-2.2-.76-3.19-1.72-2.01-1.96-3.53-5.54-3.53-8.8 0-3.3 1.6-5.06 3.19-5.06 1.03 0 1.83.6 2.65.6.83 0 1.4-.6 2.62-.6 1.34 0 2.5.76 3.1 1.72-2.73 1.65-2.28 5.6.43 6.7-.6 1.43-1.35 2.83-2.54 3.76zm-3.54-15.65c.6-.73 1-1.74 1-2.75 0-.14-.02-.28-.04-.41-.95.04-2.1.64-2.78 1.43-.6.7-.85 1.65-.85 2.65 0 .15.02.3.06.41.05 0 .1 0 .15 0 .9 0 1.9-.45 2.46-1.33z"></path>
</svg>
                        ط§ظ¾ظ„
                    </a>
</div>
<?php endif; ?>
</div>
<!-- Footer Links -->
<div class="mt-8 pb-8 text-center w-full">
<div class="flex flex-wrap justify-center gap-6 font-bold text-sm text-outline">
<a class="hover:text-primary transition-colors" href="#">ظ‚ظˆط§ظ†غŒظ† ظˆ ظ…ظ‚ط±ط±ط§طھ</a>
<a class="hover:text-primary transition-colors" href="#">ط­ط±غŒظ… ط®طµظˆطµغŒ</a>
<a class="hover:text-primary transition-colors" href="#">ظ¾ط´طھغŒط¨ط§ظ†غŒ</a>
<span class="mr-auto hidden md:inline opacity-60">آ© <?= date('Y') ?> ASENA</span>
</div>
</div>
</section>
</main>
<script src="assets/js/login.js"></script>
<script>
    const signupCountdownEl = document.getElementById('signup-countdown');
    const resendSignupBtn = document.getElementById('resend-signup-btn');
    if (signupCountdownEl && resendSignupBtn) {
        let timeLeft = 60;
        const timer = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timer);
                resendSignupBtn.disabled = false;
                resendSignupBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">refresh</span><span>ط§ط±ط³ط§ظ„ ظ…ط¬ط¯ط¯ ع©ط¯ ظ¾غŒط§ظ…ع©غŒ</span>';
            } else {
                signupCountdownEl.innerText = timeLeft;
            }
        }, 1000);
    }
</script>
</body>
</html>
