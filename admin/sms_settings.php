<?php
$currentPage = 'sms_settings';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';
require_once '../includes/SmsService.php';

$success = '';
$error = '';
$testResult = null;
$gatewayCheck = null;

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'save_settings') {
        // Admin notification settings
        $adminPhones = trim($_POST['admin_notification_phones'] ?? '');
        $smsOnOrder  = isset($_POST['admin_sms_on_order']) ? '1' : '0';
        $smsOnBooking = isset($_POST['admin_sms_on_booking']) ? '1' : '0';
        $doctorSmsOnBooking = isset($_POST['doctor_sms_on_booking']) ? '1' : '0';

        // Gateway credentials
        $apiKey   = trim($_POST['melipayamak_api_key'] ?? '');
        $username = trim($_POST['melipayamak_username'] ?? '');
        $password = trim($_POST['melipayamak_password'] ?? '');
        $from     = trim($_POST['melipayamak_from'] ?? '');

        // Pattern Body IDs
        $bodyIds = [
            'melipayamak_body_id_otp'            => trim($_POST['melipayamak_body_id_otp'] ?? '518597'),
            'melipayamak_body_id_booking'        => trim($_POST['melipayamak_body_id_booking'] ?? '528861'),
            'melipayamak_body_id_reschedule'     => trim($_POST['melipayamak_body_id_reschedule'] ?? '528862'),
            'melipayamak_body_id_shipping'       => trim($_POST['melipayamak_body_id_shipping'] ?? '528863'),
            'melipayamak_body_id_subscription'   => trim($_POST['melipayamak_body_id_subscription'] ?? '528864'),
            'melipayamak_body_id_charity'        => trim($_POST['melipayamak_body_id_charity'] ?? '528865'),
            'melipayamak_body_id_admin_order'    => trim($_POST['melipayamak_body_id_admin_order'] ?? '528866'),
            'melipayamak_body_id_doctor_booking' => trim($_POST['melipayamak_body_id_doctor_booking'] ?? '528867'),
        ];

        // Clean and normalize phones
        $phoneArray = preg_split('/[,\s;]+/', $adminPhones);
        $cleanPhones = [];
        foreach ($phoneArray as $p) {
            $p = SmsService::normalizePhone($p);
            if (!empty($p) && strlen($p) >= 10) {
                $cleanPhones[] = $p;
            }
        }
        $savedPhonesStr = implode(', ', array_unique($cleanPhones));

        set_setting($pdo, 'admin_notification_phones', $savedPhonesStr);
        set_setting($pdo, 'admin_sms_on_order', $smsOnOrder);
        set_setting($pdo, 'admin_sms_on_booking', $smsOnBooking);
        set_setting($pdo, 'doctor_sms_on_booking', $doctorSmsOnBooking);

        set_setting($pdo, 'melipayamak_api_key', $apiKey);
        set_setting($pdo, 'melipayamak_username', $username);
        set_setting($pdo, 'melipayamak_password', $password);
        set_setting($pdo, 'melipayamak_from', $from);

        foreach ($bodyIds as $k => $v) {
            set_setting($pdo, $k, $v);
        }

        $success = "طھظ†ط¸غŒظ…ط§طھ ظˆط¨â€Œط³ط±ظˆغŒط³ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©طŒ ط§ظ„ع¯ظˆظ‡ط§ ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط¯ط± ط¯غŒطھط§ط¨غŒط³ ط°ط®غŒط±ظ‡ ط´ط¯.";
    } elseif ($action === 'check_gateway') {
        $sms = new SmsService($pdo);
        $gatewayCheck = $sms->checkCredit();
    } elseif ($action === 'test_sms') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        $testType  = $_POST['test_type'] ?? 'otp';
        $testPhone = SmsService::normalizePhone($testPhone);

        if (empty($testPhone) || strlen($testPhone) < 10) {
            $error = "ظ„ط·ظپط§ظ‹ غŒع© ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ظ…ط¹طھط¨ط± ط¬ظ‡طھ طھط³طھ ظˆط§ط±ط¯ ظ†ظ…ط§غŒغŒط¯.";
        } else {
            $sms = new SmsService($pdo);
            $res = false;
            $typeName = '';
            $desc = '';

            if ($testType === 'otp') {
                $typeName = 'ع©ط¯ طھط§غŒغŒط¯ ط§ط¹طھط¨ط§ط±ط³ظ†ط¬غŒ (OTP)';
                $code = rand(100000, 999999);
                $res = $sms->sendOtp($testPhone, $code);
                $desc = $res ? "ع©ط¯ طھط§غŒغŒط¯ ط¢ط²ظ…ط§غŒط´غŒ ($code) ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ $testPhone ط§ط±ط³ط§ظ„ ط´ط¯." : ("ط®ط·ط§ ط¯ط± ط§ط±ط³ط§ظ„ OTP: " . SmsService::getLastError());
            } elseif ($testType === 'admin_order') {
                $typeName = 'ظ‡ط´ط¯ط§ط± ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ظ…ط¯غŒط±';
                $fakeOrderId = rand(1050, 1999);
                $fakeAmount = 485000;
                $res = $sms->sendAdminNewOrderAlert($testPhone, $fakeOrderId, $fakeAmount);
                $desc = $res ? "ظ¾غŒط§ظ…ع© ظ‡ط´ط¯ط§ط± ط³ظپط§ط±ط´ (#PC-$fakeOrderId) ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط§ط±ط³ط§ظ„ ط´ط¯." : ("ط®ط·ط§ ط¯ط± ط§ط±ط³ط§ظ„: " . SmsService::getLastError());
            } elseif ($testType === 'doctor_booking') {
                $typeName = 'ظ‡ط´ط¯ط§ط± ط±ط²ط±ظˆ ظ†ظˆط¨طھ ط¨ظ‡ ظ¾ط²ط´ع©';
                $res = $sms->sendDoctorNewAppointmentAlert($testPhone, 'ط¯ع©طھط± ظ†ط§ظ…غŒ', 'ظ…غŒظ„ظˆ', '1404/06/20', '17:30');
                $desc = $res ? "ظ¾غŒط§ظ…ع© ظ†ظˆط¨طھ ظپط±ط¶غŒ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط§ط±ط³ط§ظ„ ط´ط¯." : ("ط®ط·ط§ ط¯ط± ط§ط±ط³ط§ظ„: " . SmsService::getLastError());
            } else {
                $typeName = 'ظ¾غŒط§ظ…ع© ظ…ط³طھظ‚غŒظ… ط³ط§ط¯ظ‡';
                $text = "طھط³طھ ظ…ظˆظپظ‚غŒطھâ€Œط¢ظ…غŒط² ط§ط±طھط¨ط§ط· ط¯ط±ع¯ط§ظ‡ ظ¾غŒط§ظ…ع© ط¨ط§ ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§.\nasena.company\nط²ظ…ط§ظ†: " . date('H:i:s');
                $res = $sms->sendDirectSms($testPhone, $text);
                $desc = $res ? "ظ¾غŒط§ظ…ع© ظ…ط³طھظ‚غŒظ… ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ $testPhone ط§ط±ط³ط§ظ„ ط´ط¯." : ("ط®ط·ط§ ط¯ط± ط§ط±ط³ط§ظ„ ظ…ط³طھظ‚غŒظ…: " . SmsService::getLastError());
            }

            $testResult = [
                'ok' => (bool)$res,
                'type' => $typeName,
                'message' => $desc,
                'last_response' => SmsService::getLastResponse(),
                'last_error' => SmsService::getLastError()
            ];

            if ($testResult['ok']) {
                $success = $testResult['message'];
            } else {
                $error = $testResult['message'];
            }
        }
    }
}

// Retrieve current settings
$adminNotificationPhones = get_setting($pdo, 'admin_notification_phones', '09146676978');
$adminSmsOnOrder         = get_setting($pdo, 'admin_sms_on_order', '1');
$adminSmsOnBooking       = get_setting($pdo, 'admin_sms_on_booking', '1');
$doctorSmsOnBooking      = get_setting($pdo, 'doctor_sms_on_booking', '1');

$apiKeyVal   = get_setting($pdo, 'melipayamak_api_key', getenv('MELIPAYAMAK_API_KEY') ?: 'd3cbc1e6-79e8-4a25-910e-35e86370cad0');
$usernameVal = get_setting($pdo, 'melipayamak_username', getenv('MELIPAYAMAK_USERNAME') ?: '09146676978');
$passwordVal = get_setting($pdo, 'melipayamak_password', getenv('MELIPAYAMAK_PASSWORD') ?: 'NZ456QM9L');
$fromVal     = get_setting($pdo, 'melipayamak_from', getenv('MELIPAYAMAK_FROM') ?: '2170007653');

$phoneList = array_filter(array_map('trim', explode(',', $adminNotificationPhones)));

// Patterns list for display & configuration
$patterns = [
    [
        'id' => 'otp',
        'key' => 'melipayamak_body_id_otp',
        'name' => 'ع©ط¯ طھط§غŒغŒط¯ OTP (ظˆط±ظˆط¯ / ط«ط¨طھâ€Œظ†ط§ظ… / ط¨ط§ط²غŒط§ط¨غŒ)',
        'vars' => '{0} = ع©ط¯ طھط§غŒغŒط¯',
        'body_id' => SmsService::getBodyId('otp', $pdo),
        'sample' => "ع©ط¯ طھط§غŒغŒط¯ ظˆط±ظˆط¯ ط¨ظ‡ ط¢ط³ظ†ط§:\n{0}\nasena.company",
        'target' => 'ع©ط§ط±ط¨ط± / ظ…ط´طھط±غŒ'
    ],
    [
        'id' => 'booking',
        'key' => 'melipayamak_body_id_booking',
        'name' => 'طھط§غŒغŒط¯ ط±ط²ط±ظˆ ظ†ظˆط¨طھ ظˆغŒط²غŒطھ',
        'vars' => '{0} = طھط§ط±غŒط®, {1} = ط³ط§ط¹طھ',
        'body_id' => SmsService::getBodyId('booking', $pdo),
        'sample' => "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒطŒ ظ†ظˆط¨طھ ظˆغŒط²غŒطھ ط´ظ…ط§ ط¯ط± ط¢ط³ظ†ط§ ط¨ط±ط§غŒ طھط§ط±غŒط® {0} ط³ط§ط¹طھ {1} ط¨ط§ ظ…ظˆظپظ‚غŒطھ طھط§غŒغŒط¯ ط´ط¯.\nasena.company",
        'target' => 'ع©ط§ط±ط¨ط± / ط¨غŒظ…ط§ط±'
    ],
    [
        'id' => 'reschedule',
        'key' => 'melipayamak_body_id_reschedule',
        'name' => 'طھط؛غŒغŒط± ط²ظ…ط§ظ† ظ†ظˆط¨طھ ظˆغŒط²غŒطھ',
        'vars' => '{0} = ظ†ط§ظ… ظ¾ط²ط´ع©, {1} = ظ†ط§ظ… ظ¾طھ, {2} = طھط§ط±غŒط® ط¬ط¯غŒط¯, {3} = ط³ط§ط¹طھ ط¬ط¯غŒط¯',
        'body_id' => SmsService::getBodyId('reschedule', $pdo),
        'sample' => "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒ ط¢ط³ظ†ط§طŒ ط²ظ…ط§ظ† ظ†ظˆط¨طھ ظˆغŒط²غŒطھ ظ¾طھ ط´ظ…ط§ ({1}) ط¨ط§ ط¯ع©طھط± {0} ط¨ظ‡ طھط§ط±غŒط® {2} ط³ط§ط¹طھ {3} طھط؛غŒغŒط± غŒط§ظپطھ.\nasena.company",
        'target' => 'ع©ط§ط±ط¨ط± / ط¨غŒظ…ط§ط±'
    ],
    [
        'id' => 'shipping',
        'key' => 'melipayamak_body_id_shipping',
        'name' => 'ط§ط±ط³ط§ظ„ ط³ظپط§ط±ط´ ظپط±ظˆط´ع¯ط§ظ‡ / ط¯ط§ط±ظˆط®ط§ظ†ظ‡',
        'vars' => '{0} = ط´ظ…ط§ط±ظ‡ ط³ظپط§ط±ط´',
        'body_id' => SmsService::getBodyId('shipping', $pdo),
        'sample' => "ط³ظپط§ط±ط´ ط´ظ…ط§ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ {0} ط¯ط± ط¢ط³ظ†ط§ ظ¾ط±ط¯ط§ط²ط´ ظˆ طھط­ظˆغŒظ„ ظˆط§ط­ط¯ ط§ط±ط³ط§ظ„ ط´ط¯.\nasena.company",
        'target' => 'ط®ط±غŒط¯ط§ط±'
    ],
    [
        'id' => 'subscription',
        'key' => 'melipayamak_body_id_subscription',
        'name' => 'ظپط¹ط§ظ„â€Œط³ط§ط²غŒ ط¨ط³طھظ‡ ط§ط´طھط±ط§ع©',
        'vars' => '{0} = ظ†ط§ظ… ط§ط´طھط±ط§ع© (ظ…ط§ظ‡ط§ظ†ظ‡/ط·ظ„ط§غŒغŒ)',
        'body_id' => SmsService::getBodyId('subscription', $pdo),
        'sample' => "ط§ط´طھط±ط§ع© {0} ط´ظ…ط§ ط¯ط± ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ظپط¹ط§ظ„ ع¯ط±ط¯غŒط¯.\nasena.company",
        'target' => 'ظ…ط´طھط±ع©'
    ],
    [
        'id' => 'charity',
        'key' => 'melipayamak_body_id_charity',
        'name' => 'طھط´ع©ط± ظˆط§ط±غŒط² ط®غŒط±غŒظ‡ ط­غŒظˆط§ظ†ط§طھ',
        'vars' => '{0} = ظ…ط¨ظ„ط؛ ظˆط§ط±غŒط²غŒ ط¨ظ‡ طھظˆظ…ط§ظ†',
        'body_id' => SmsService::getBodyId('charity', $pdo),
        'sample' => "ع©ط§ط±ط¨ط± ع¯ط±ط§ظ…غŒطŒ ط§ط² ط­ظ…ط§غŒطھ ط§ط±ط²ط´ظ…ظ†ط¯ ط´ظ…ط§ ط¨ظ‡ ظ…ط¨ظ„ط؛ {0} طھظˆظ…ط§ظ† ط¨ظ‡ ظ¾ظˆغŒط´ ط®غŒط±غŒظ‡ ط­غŒظˆط§ظ†ط§طھ ط¢ط³ظ†ط§ ط³ظ¾ط§ط³ع¯ط²ط§ط±غŒظ….\nasena.company",
        'target' => 'ظ†غŒع©ظˆع©ط§ط±'
    ],
    [
        'id' => 'admin_order',
        'key' => 'melipayamak_body_id_admin_order',
        'name' => 'ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ظ…ط¯غŒط±',
        'vars' => '{0} = ط´ظ…ط§ط±ظ‡ ط³ظپط§ط±ط´, {1} = ظ…ط¨ظ„ط؛ ع©ظ„ ط¨ظ‡ طھظˆظ…ط§ظ†',
        'body_id' => SmsService::getBodyId('admin_order', $pdo),
        'sample' => "ظ…ط¯غŒط± ع¯ط±ط§ظ…غŒطŒ ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ {0} ط¨ط§ ظ…ط¨ظ„ط؛ {1} طھظˆظ…ط§ظ† ط¯ط± ط³ط§ظ…ط§ظ†ظ‡ ط¢ط³ظ†ط§ ط«ط¨طھ ط´ط¯.\nasena.company",
        'target' => 'ظ…ط¯غŒط±ط§ظ† ط³غŒط³طھظ…'
    ],
    [
        'id' => 'doctor_booking',
        'key' => 'melipayamak_body_id_doctor_booking',
        'name' => 'ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ظ†ظˆط¨طھ ط¬ط¯غŒط¯ ط¨ظ‡ ظ¾ط²ط´ع©',
        'vars' => '{0} = ظ†ط§ظ… ظ¾ط²ط´ع©, {1} = ظ†ط§ظ… ظ¾طھ, {2} = طھط§ط±غŒط®, {3} = ط³ط§ط¹طھ',
        'body_id' => SmsService::getBodyId('doctor_booking', $pdo),
        'sample' => "ط¯ع©طھط± {0} ع¯ط±ط§ظ…غŒطŒ ظ†ظˆط¨طھ ط¬ط¯غŒط¯ ط¨ط±ط§غŒ ظ¾طھ ({1}) ط¯ط± طھط§ط±غŒط® {2} ط³ط§ط¹طھ {3} ط¯ط± ط¢ط³ظ†ط§ ط«ط¨طھ ط´ط¯.\nasena.company",
        'target' => 'ظ¾ط²ط´ع© ظ…ط¹ط§ظ„ط¬'
    ],
];
?>

<div class="p-6 lg:p-8 max-w-[1400px] mx-auto rtl">
    <!-- Page Title & Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-[30px]" style="font-variation-settings: 'FILL' 1;">sms</span>
                </div>
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-primary font-bold">طھظ†ط¸غŒظ…ط§طھ ط¯ط±ع¯ط§ظ‡ ظ¾غŒط§ظ…ع© ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع© (Melipayamak Gateway)</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">ظ…ط¯غŒط±غŒطھ ط§ط¹طھط¨ط§ط±ط³ظ†ط¬غŒ OTPطŒ ط§ظ„ع¯ظˆظ‡ط§غŒ ط®ط¯ظ…ط§طھغŒطŒ ط®ط·ظˆط· ظپط±ط³طھظ†ط¯ظ‡ ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط®ظˆط¯ع©ط§ط±</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="https://console.melipayamak.com" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant hover:border-secondary-container px-4 py-2.5 rounded-xl font-bold text-sm text-primary shadow-sm hover:shadow transition-all">
                <span class="material-symbols-outlined text-[20px] text-secondary-container">token</span>
                <span>ع©ظ†ط³ظˆظ„ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©</span>
            </a>
            <a href="https://login.melipayamak.com/?module=ShareService" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant hover:border-secondary-container px-4 py-2.5 rounded-xl font-bold text-sm text-primary shadow-sm hover:shadow transition-all">
                <span class="material-symbols-outlined text-[20px] text-secondary-container">open_in_new</span>
                <span>ظ¾ظ†ظ„ ط§ظ„ع¯ظˆظ‡ط§غŒ ط®ط¯ظ…ط§طھغŒ</span>
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl flex items-center gap-3 font-bold text-sm shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl flex items-center gap-3 font-bold text-sm shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Gateway Health Check Result (If triggered) -->
    <?php if ($gatewayCheck !== null): ?>
        <div class="mb-6 p-5 rounded-2xl border <?= $gatewayCheck['ok'] ? 'bg-emerald-50/80 border-emerald-300 text-emerald-900' : 'bg-amber-50/80 border-amber-300 text-amber-900' ?> shadow-sm">
            <div class="flex items-center gap-3 mb-2 font-bold text-base">
                <span class="material-symbols-outlined <?= $gatewayCheck['ok'] ? 'text-emerald-600' : 'text-amber-600' ?>">
                    <?= $gatewayCheck['ok'] ? 'verified' : 'warning' ?>
                </span>
                <span>ظ†طھغŒط¬ظ‡ ط¨ط±ط±ط³غŒ ط³ظ„ط§ظ…طھ ظˆ ط§طھطµط§ظ„ ط¯ط±ع¯ط§ظ‡ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©:</span>
            </div>
            <?php if ($gatewayCheck['ok']): ?>
                <p class="text-sm font-bold text-emerald-800">
                    ط§طھطµط§ظ„ ظ…ظˆظپظ‚غŒطھâ€Œط¢ظ…غŒط² ط§ط³طھ! ظ…ظˆط¬ظˆط¯غŒ ظپط¹ظ„غŒ ط­ط³ط§ط¨ ط´ظ…ط§: <strong class="text-emerald-950 font-black text-lg"><?= number_format($gatewayCheck['credit']) ?></strong> ط±غŒط§ظ„ / ظ¾غŒط§ظ…ع© (ط§ط² ط·ط±غŒظ‚ <?= $gatewayCheck['source'] ?>).
                </p>
            <?php else: ?>
                <p class="text-sm font-bold text-amber-900 mb-1">
                    <?= htmlspecialchars($gatewayCheck['error'] ?? 'ط®ط·ط§ ط¯ط± ط¨ط±ظ‚ط±ط§ط±غŒ ط§ط±طھط¨ط§ط·') ?>
                </p>
                <?php if (!empty($gatewayCheck['code'])): ?>
                    <p class="text-xs text-amber-800 font-mono" dir="ltr">Melipayamak Error Code: <?= (int)$gatewayCheck['code'] ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">ط´ظ…ط§ط±ظ‡â€Œظ‡ط§غŒ ظ…ط¯غŒط±ط§ظ† ظ…طھطµظ„</p>
                <p class="text-2xl font-black text-primary"><?= count($phoneList) ?> <span class="text-xs font-normal text-on-surface-variant">ط´ظ…ط§ط±ظ‡</span></p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[28px]">contact_phone</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">ظ¾غŒط§ظ…ع© ط§ط¹طھط¨ط§ط±ط³ظ†ط¬غŒ OTP</p>
                <p class="text-lg font-black text-secondary-container">ظپط¹ط§ظ„ (ظ¾طھط±ظ† ط®ط¯ظ…ط§طھغŒ)</p>
            </div>
            <div class="w-12 h-12 bg-secondary-container/10 text-secondary-container rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">password</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">ظ¾غŒط§ظ…ع© ط³ظپط§ط±ط´ط§طھ ط¬ط¯غŒط¯</p>
                <p class="text-lg font-black <?= $adminSmsOnOrder === '1' ? 'text-emerald-600' : 'text-slate-400' ?>">
                    <?= $adminSmsOnOrder === '1' ? 'ظپط¹ط§ظ„ (ط§ط±ط³ط§ظ„ ط¢ظ†غŒ)' : 'ط؛غŒط±ظپط¹ط§ظ„' ?>
                </p>
            </div>
            <div class="w-12 h-12 <?= $adminSmsOnOrder === '1' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">shopping_cart_checkout</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">ظ…ع©ط§ظ†غŒط²ظ… ط§ط±ط³ط§ظ„</p>
                <p class="text-lg font-black text-emerald-600">ع†ظ†ط¯ ظ„ط§غŒظ‡â€Œط§غŒ ظ‡ظˆط´ظ…ظ†ط¯</p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <span class="material-symbols-outlined text-[28px]">hub</span>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Configuration Form (Col 7) -->
        <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-outline-variant/30">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container text-[26px]">tune</span>
                    <div>
                        <h3 class="font-bold text-primary text-lg">طھظ†ط¸غŒظ…ط§طھ ط§ط·ظ„ط§ط¹ط§طھ ط§طھطµط§ظ„ ط¨ظ‡ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©</h3>
                        <p class="text-xs text-on-surface-variant">ط§ط·ظ„ط§ط¹ط§طھ ط§ط­ط±ط§ط² ظ‡ظˆغŒطھطŒ ع©ظ„غŒط¯ظ‡ط§ ظˆ ط´ظ…ط§ط±ظ‡ ظپط±ط³طھظ†ط¯ظ‡ ط±ط§ ط¯ط± ط§غŒظ† ط¨ط®ط´ طھظ†ط¸غŒظ… ظ†ظ…ط§غŒغŒط¯</p>
                    </div>
                </div>
                <form method="POST" action="sms_settings.php" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="check_gateway">
                    <button type="submit" class="text-xs font-bold text-secondary-container hover:text-secondary-container/80 flex items-center gap-1 bg-secondary-container/10 px-3 py-1.5 rounded-lg transition-all">
                        <span class="material-symbols-outlined text-[16px]">sync</span>
                        <span>ط¨ط±ط±ط³غŒ ظˆط¶ط¹غŒطھ ط¯ط±ع¯ط§ظ‡</span>
                    </button>
                </form>
            </div>

            <form method="POST" action="sms_settings.php" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">

                <!-- Gateway Auth Credentials Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">
                            طھظˆع©ظ† ع©ظ†ط³ظˆظ„ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع© (Console API Key):
                        </label>
                        <input type="text" name="melipayamak_api_key" value="<?= htmlspecialchars($apiKeyVal) ?>" class="w-full p-2.5 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" dir="ltr">
                        <p class="text-[11px] text-on-surface-variant mt-1">ط§ط² ط¨ط®ط´ ع©ظ†ط³ظˆظ„ REST ط³ط§ظ…ط§ظ†ظ‡ console.melipayamak.com</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">
                            ظ†ط§ظ… ع©ط§ط±ط¨ط±غŒ ظ¾ظ†ظ„ (Username):
                        </label>
                        <input type="text" name="melipayamak_username" value="<?= htmlspecialchars($usernameVal) ?>" class="w-full p-2.5 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" placeholder="09146676978" dir="ltr">
                        <p class="text-[11px] text-on-surface-variant mt-1">ظ†ط§ظ… ع©ط§ط±ط¨ط±غŒ غŒط§ ط´ظ…ط§ط±ظ‡ ظ‡ظ…ط±ط§ظ‡ ظˆط±ظˆط¯ ط¨ظ‡ ظ¾ظ†ظ„ ظ…ظ„غŒ ظ¾غŒط§ظ…ع©</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">
                            ط±ظ…ط² ط¹ط¨ظˆط± غŒط§ ع©ظ„غŒط¯ ظˆط¨â€Œط³ط±ظˆغŒط³ (Password / ApiKey):
                        </label>
                        <input type="text" name="melipayamak_password" value="<?= htmlspecialchars($passwordVal) ?>" class="w-full p-2.5 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" placeholder="ط±ظ…ط² ط¹ط¨ظˆط± غŒط§ ع©ظ„غŒط¯ ط§ط®طھطµط§طµغŒ" dir="ltr">
                        <p class="text-[11px] text-on-surface-variant mt-1">ط±ظ…ط² ظ¾ظ†ظ„ غŒط§ ApiKey ظ…ظ†ظˆغŒ طھظ†ط¸غŒظ…ط§طھ -> ط¯ط³طھط±ط³غŒ ظˆط¨â€Œط³ط±ظˆغŒط³</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">
                            ط´ظ…ط§ط±ظ‡ ط§ط®طھطµط§طµغŒ ظپط±ط³طھظ†ط¯ظ‡ (From Number):
                        </label>
                        <input type="text" name="melipayamak_from" value="<?= htmlspecialchars($fromVal) ?>" class="w-full p-2.5 bg-surface-container-lowest border border-outline-variant rounded-lg text-xs font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" placeholder="2170007653" dir="ltr">
                        <p class="text-[11px] text-on-surface-variant mt-1">ط®ط· ط§ط®طھطµط§طµغŒ ط´ظ…ط§ ط¬ظ‡طھ ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع©â€Œظ‡ط§غŒ ظ…ط³طھظ‚غŒظ…</p>
                    </div>
                </div>

                <!-- Admin Notification Phones -->
                <div>
                    <label class="block text-sm font-bold text-primary mb-2">
                        ط´ظ…ط§ط±ظ‡â€Œظ‡ط§غŒ ظ…ظˆط¨ط§غŒظ„ ظ…ط¯غŒط±ط§ظ† ط¬ظ‡طھ ط¯ط±غŒط§ظپطھ ظ¾غŒط§ظ…ع©:
                    </label>
                    <textarea name="admin_notification_phones" rows="2" class="w-full p-3.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono text-left focus:ring-2 focus:ring-secondary-container outline-none transition-all" placeholder="09146676978, 09123456789" dir="ltr"><?= htmlspecialchars($adminNotificationPhones) ?></textarea>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        ظ…غŒâ€Œطھظˆط§ظ†غŒط¯ ع†ظ†ط¯ ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ط±ط§ ط¨ط§ ع©ط§ظ…ط§ (,) غŒط§ ظپط§طµظ„ظ‡ ط¬ط¯ط§ ع©ظ†غŒط¯. ظ‡ظ†ع¯ط§ظ… ط«ط¨طھ ط³ظپط§ط±ط´ غŒط§ ظ†ظˆط¨طھ ط¬ط¯غŒط¯طŒ ط¨ظ‡ ظ‡ظ…ظ‡ ط§غŒظ† ط´ظ…ط§ط±ظ‡â€Œظ‡ط§ ظ¾غŒط§ظ…ع© ط§ط±ط³ط§ظ„ ط®ظˆط§ظ‡ط¯ ط´ط¯.
                    </p>

                    <!-- Active Phone Chips -->
                    <?php if (!empty($phoneList)): ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php foreach ($phoneList as $p): ?>
                            <span class="inline-flex items-center gap-1.5 bg-primary/5 text-primary text-xs font-bold px-3 py-1.5 rounded-lg border border-primary/10" dir="ltr">
                                <span class="material-symbols-outlined text-[14px]">phone_iphone</span>
                                <span><?= htmlspecialchars($p) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Event Toggles -->
                <div class="pt-4 border-t border-outline-variant/30 space-y-4">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">ط±ظˆغŒط¯ط§ط¯ظ‡ط§غŒ ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ط®ظˆط¯ع©ط§ط±</p>

                    <!-- Toggle 1: Order Alert to Admin -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ط¨ظ‡ ظ…ط¯غŒط± ظ‡ظ†ع¯ط§ظ… ط³ظپط§ط±ط´ ط¬ط¯غŒط¯</p>
                                <p class="text-xs text-on-surface-variant">ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ط´ظ…ط§ط±ظ‡ ط³ظپط§ط±ط´ ظˆ ظ…ط¨ظ„ط؛ ط¨ظ‡ ط´ظ…ط§ط±ظ‡â€Œظ‡ط§غŒ ط«ط¨طھ ط´ط¯ظ‡ ط¨ط§ظ„ط§</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_order" value="1" <?= $adminSmsOnOrder === '1' ? 'checked' : '' ?> class="w-5 h-5 text-secondary-container rounded focus:ring-secondary-container">
                    </label>

                    <!-- Toggle 2: Booking Alert to Admin -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ط¨ظ‡ ظ…ط¯غŒط± ظ‡ظ†ع¯ط§ظ… ط«ط¨طھ ظ†ظˆط¨طھ ظˆغŒط²غŒطھ</p>
                                <p class="text-xs text-on-surface-variant">ط§ط·ظ„ط§ط¹â€Œط±ط³ط§ظ†غŒ ظپظˆط±غŒ ط±ط²ط±ظˆ ظˆظ‚طھ ع©ظ„غŒظ†غŒع© ط¨ظ‡ ظ…ط¯غŒط±غŒطھ</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_booking" value="1" <?= $adminSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 text-secondary-container rounded focus:ring-secondary-container">
                    </label>

                    <!-- Toggle 3: Doctor Alert on Booking -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">stethoscope</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ظ…ط³طھظ‚غŒظ… ط¨ظ‡ ظ¾ط²ط´ع© ظ‡ظ†ع¯ط§ظ… ط±ط²ط±ظˆ ظˆظ‚طھ</p>
                                <p class="text-xs text-on-surface-variant">ط§ط±ط³ط§ظ„ ط¬ط²ط¦غŒط§طھ ظ†ظˆط¨طھ ط¨غŒظ…ط§ط± ط¨ظ‡ ط´ظ…ط§ط±ظ‡ ظ‡ظ…ط±ط§ظ‡ ظ¾ط²ط´ع© ظ…ط¹ط§ظ„ط¬</p>
                            </div>
                        </div>
                        <input type="checkbox" name="doctor_sms_on_booking" value="1" <?= $doctorSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 text-secondary-container rounded focus:ring-secondary-container">
                    </label>
                </div>

                <!-- Pattern IDs Mapping Section -->
                <div class="pt-4 border-t border-outline-variant/30">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">ط´ظ†ط§ط³ظ‡â€Œظ‡ط§غŒ ع©ط¯ ظ…طھظ† ط§ظ„ع¯ظˆظ‡ط§ ط¯ط± ظ¾ظ†ظ„ (Pattern Body IDs)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($patterns as $pat): ?>
                            <div>
                                <label class="block text-[11px] font-bold text-primary mb-1">
                                    <?= htmlspecialchars($pat['name']) ?>:
                                </label>
                                <input type="text" name="<?= htmlspecialchars($pat['key']) ?>" value="<?= htmlspecialchars($pat['body_id']) ?>" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" dir="ltr">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-4 border-t border-outline-variant/30 flex justify-end">
                    <button type="submit" class="flex items-center gap-2 bg-secondary-container hover:bg-secondary-container/90 text-on-secondary-container font-bold px-6 py-3 rounded-xl shadow-md hover:shadow-lg transition-all">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        <span>ط°ط®غŒط±ظ‡ طھظ…ط§ظ…غŒ طھظ†ط¸غŒظ…ط§طھ ط¯ط± ط¯غŒطھط§ط¨غŒط³</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Testing & Diagnostic Tool (Col 5) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 stat-card-shadow">
                <div class="flex items-center gap-3 mb-5 pb-3 border-b border-outline-variant/30">
                    <span class="material-symbols-outlined text-secondary-container text-[24px]">send_and_archive</span>
                    <div>
                        <h3 class="font-bold text-primary text-base">ط§ط¨ط²ط§ط± طھط³طھ ط²ظ†ط¯ظ‡ ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع©</h3>
                        <p class="text-xs text-on-surface-variant">طھط³طھ ط§ط±ط³ط§ظ„ ع©ط¯ طھط§غŒغŒط¯ OTP ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ط¨ظ‡ ط´ظ…ط§ط±ظ‡ ظ‡ظ…ط±ط§ظ‡ ط¯ظ„ط®ظˆط§ظ‡</p>
                    </div>
                </div>

                <form method="POST" action="sms_settings.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="test_sms">

                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ ط¬ظ‡طھ طھط³طھ:</label>
                        <input type="text" name="test_phone" value="09146676978" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono text-left outline-none focus:ring-2 focus:ring-secondary-container" placeholder="09146676978" dir="ltr" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-primary mb-1.5">ظ†ظˆط¹ ظ¾غŒط§ظ…ع© ط§ط±ط³ط§ظ„غŒ ط¬ظ‡طھ طھط³طھ:</label>
                        <select name="test_type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-primary outline-none focus:ring-2 focus:ring-secondary-container">
                            <option value="otp">ع©ط¯ ط§ط¹طھط¨ط§ط±ط³ظ†ط¬غŒ ظˆط±ظˆط¯ / OTP (طھظˆطµغŒظ‡ ط´ط¯ظ‡)</option>
                            <option value="admin_order">ظ‡ط´ط¯ط§ط± ط«ط¨طھ ط³ظپط§ط±ط´ ط¬ط¯غŒط¯ ط¨ظ‡ ظ…ط¯غŒط±</option>
                            <option value="doctor_booking">ظ‡ط´ط¯ط§ط± ط«ط¨طھ ظ†ظˆط¨طھ ط¬ط¯غŒط¯ ط¨ظ‡ ظ¾ط²ط´ع©</option>
                            <option value="direct">ظ¾غŒط§ظ…ع© ظ…ط³طھظ‚غŒظ… ظ…طھظ†غŒ ط³ط§ط¯ظ‡</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary/95 text-white font-bold py-3 px-4 rounded-xl shadow transition-all">
                        <span class="material-symbols-outlined text-[20px]">send</span>
                        <span>ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ…ع© ط¢ط²ظ…ط§غŒط´غŒ</span>
                    </button>
                </form>

                <!-- Detailed Test Result -->
                <?php if ($testResult): ?>
                    <div class="mt-4 p-4 rounded-xl text-xs <?= $testResult['ok'] ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : 'bg-rose-50 text-rose-900 border border-rose-200' ?>">
                        <div class="flex items-center gap-2 font-bold mb-1.5">
                            <span class="material-symbols-outlined text-[18px]"><?= $testResult['ok'] ? 'check_circle' : 'error' ?></span>
                            <span><?= htmlspecialchars($testResult['type']) ?>: <?= $testResult['ok'] ? 'ط§ط±ط³ط§ظ„ ظ…ظˆظپظ‚' : 'ط®ط·ط§ ط¯ط± ط§ط±ط³ط§ظ„' ?></span>
                        </div>
                        <p class="mb-2"><?= htmlspecialchars($testResult['message']) ?></p>
                        <?php if (!empty($testResult['last_response'])): ?>
                            <details class="mt-2 font-mono text-[10px] bg-white/70 p-2 rounded border" dir="ltr">
                                <summary class="cursor-pointer font-bold text-slate-700">View Raw API Response</summary>
                                <pre class="mt-1 whitespace-pre-wrap"><?= htmlspecialchars(print_r($testResult['last_response'], true)) ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Important Panel Setup Instructions Card -->
            <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 stat-card-shadow">
                <div class="flex items-center gap-2 mb-3 text-secondary-container font-bold text-sm">
                    <span class="material-symbols-outlined text-[20px]">help_center</span>
                    <span>ط±ط§ظ‡ظ†ظ…ط§غŒ ط±ظپط¹ ط®ط·ط§ظ‡ط§غŒ ظ…طھط¯ط§ظˆظ„ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©</span>
                </div>
                <div class="space-y-2.5 text-xs text-on-surface-variant leading-relaxed">
                    <div class="p-2.5 rounded-lg bg-surface-container-low border border-outline-variant/30">
                        <strong class="text-primary font-bold block mb-0.5">ط®ط·ط§غŒ 35 / ط¨ظ„ع©â€Œظ„غŒط³طھ ظ…ط®ط§ط¨ط±ط§طھ:</strong>
                        <span>ظ…ط®ط§ط·ط¨ط§ظ†غŒ ع©ظ‡ ظ¾غŒط§ظ…ع© طھط¨ظ„غŒط؛ط§طھغŒ ط±ط§ ط¨ط³طھظ‡â€Œط§ظ†ط¯ ظ¾غŒط§ظ… ظ…ط¹ظ…ظˆظ„غŒ ط¯ط±غŒط§ظپطھ ظ†ظ…غŒâ€Œع©ظ†ظ†ط¯ط› ط¢ط³ظ†ط§ ط¨ظ‡ ط·ظˆط± ط®ظˆط¯ع©ط§ط± ط§ط² ظ¾طھط±ظ† ط®ط¯ظ…ط§طھغŒ ط§ط³طھظپط§ط¯ظ‡ ظ…غŒâ€Œع©ظ†ط¯ طھط§ ظ¾غŒط§ظ…ع© ط¨ظ‡ غ±غ°غ°ظھ ط§ظپط±ط§ط¯ ط¨ط±ط³ط¯.</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-container-low border border-outline-variant/30">
                        <strong class="text-primary font-bold block mb-0.5">ط®ط·ط§غŒ 109- / 108- (ظ…ط­ط¯ظˆط¯غŒطھ IP):</strong>
                        <span>ط¯ط± ظ¾ظ†ظ„ ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع© > ظ…ظ†ظˆغŒ طھظ†ط¸غŒظ…ط§طھ > ط¯ط³طھط±ط³غŒ ظˆط¨â€Œط³ط±ظˆغŒط³طŒ ط¢غŒâ€Œظ¾غŒ ط³ط±ظˆط± ط±ط§ ط¯ط± ظ„غŒط³طھ ظ…ط¬ط§ط² ط«ط¨طھ ع©ظ†غŒط¯ غŒط§ ط¯ط± طµظˆط±طھ ظ…ط³ط¯ظˆط¯غŒ ظ…ظˆظ‚طھ ط¯ظ‚ط§غŒظ‚غŒ طµط¨ط± ظ†ظ…ط§غŒغŒط¯.</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-container-low border border-outline-variant/30">
                        <strong class="text-primary font-bold block mb-0.5">ط®ط·ط§غŒ 110- (ط§ظ„ط²ط§ظ… ApiKey):</strong>
                        <span>ط¯ط± ظ…طھط¯ظ‡ط§غŒ ظˆط¨â€Œط³ط±ظˆغŒط³ ط¨ظ‡ ط¬ط§غŒ ط±ظ…ط² ط¹ط¨ظˆط± ظ¾ظ†ظ„طŒ ط§ط² ع©ظ„غŒط¯ ApiKey ط§ط®طھطµط§طµغŒ ط³ط§ط®طھظ‡ ط´ط¯ظ‡ ط¯ط± ظ¾ظ†ظ„ ط§ط³طھظپط§ط¯ظ‡ ظپط±ظ…ط§غŒغŒط¯.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patterns Catalog Section -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
            <div>
                <h3 class="font-bold text-primary text-lg">ع©ط§طھط§ظ„ظˆع¯ ط§ظ„ع¯ظˆظ‡ط§ ظˆ ظ¾طھط±ظ†â€Œظ‡ط§غŒ ط³ط§ظ…ط§ظ†ظ‡ (Melipayamak Patterns)</h3>
                <p class="text-xs text-on-surface-variant">ظ…طھظ†â€Œظ‡ط§غŒ طھط§غŒغŒط¯ ط´ط¯ظ‡ ط¯ط± ط¨ط®ط´ آ«ظˆط¨â€Œط³ط±ظˆغŒط³ ط®ط¯ظ…ط§طھغŒ ط§ط´طھط±ط§ع©غŒآ» ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع© ط¬ظ‡طھ ط¹ط¨ظˆط± ط§ط² ط¨ظ„ع©â€Œظ„غŒط³طھ ظ…ط®ط§ط¨ط±ط§طھ</p>
            </div>
            <a href="https://login.melipayamak.com/?module=ShareService" target="_blank" class="text-xs font-bold text-secondary-container hover:underline flex items-center gap-1">
                <span>ط«ط¨طھ ط§ظ„ع¯ظˆغŒ ط¬ط¯غŒط¯ ط¯ط± ظ…ظ„غŒâ€Œظ¾غŒط§ظ…ع©</span>
                <span class="material-symbols-outlined text-[16px]">launch</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($patterns as $pat): ?>
                <div class="bg-surface-container-low border border-outline-variant/40 rounded-2xl p-5 flex flex-col justify-between hover:border-secondary-container/50 transition-all">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="inline-block bg-primary/10 text-primary font-black text-xs px-2.5 py-1 rounded-lg">
                                ط´ظ†ط§ط³ظ‡ ع©ط¯ ظ…طھظ†: <span class="font-mono text-sm"><?= htmlspecialchars($pat['body_id']) ?></span>
                            </span>
                            <span class="text-[11px] font-bold text-on-surface-variant bg-surface-container-lowest px-2 py-0.5 rounded border border-outline-variant/40">
                                <?= htmlspecialchars($pat['target']) ?>
                            </span>
                        </div>
                        <h4 class="font-bold text-sm text-primary mb-2"><?= htmlspecialchars($pat['name']) ?></h4>
                        <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30 text-xs text-on-surface leading-relaxed font-sans mb-3 whitespace-pre-line" dir="rtl">
                            <?= htmlspecialchars($pat['sample']) ?>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-outline-variant/20 flex justify-between items-center text-[11px] text-on-surface-variant font-mono">
                        <span>ظ…طھط؛غŒط±ظ‡ط§: <?= htmlspecialchars($pat['vars']) ?></span>
                        <span class="text-emerald-600 font-bold font-sans flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>طھط§غŒغŒط¯ ط´ط¯ظ‡</span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>

