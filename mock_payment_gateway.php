<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$authority = trim($_GET['authority'] ?? $_GET['Authority'] ?? '');
$amount = (int)($_GET['amount'] ?? 0);
$desc = trim($_GET['desc'] ?? 'خرید اینترنتی از داروخانه و پت‌شاپ آسنا (دکتر گلزاری)');
$callback_url = trim($_GET['callback'] ?? '');

if (empty($authority) || $amount <= 0 || empty($callback_url)) {
    die("پارامترهای درخواست پرداخت نامعتبر است.");
}

$fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd - HH:mm');
$currentDate = $fmt->format(time());
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>درگاه پرداخت اینترنتی شاپرک — آسنا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/vazirmatn.css">
    <link rel="stylesheet" href="assets/css/material-symbols.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
    
    <!-- Top Shaparak Header -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white p-6 flex items-center justify-between shadow-inner">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center border border-white/20">
                <span class="material-symbols-outlined text-2xl text-amber-400">shield</span>
            </div>
            <div>
                <h1 class="text-base font-bold">سامانه پرداخت اینترنتی شاپرک</h1>
                <p class="text-[11px] text-blue-200">درگاه امن پرداخت الکترونیک بانکی (شبیه‌ساز تایید تراکنش)</p>
            </div>
        </div>
        <div class="text-left font-mono">
            <span class="text-xs bg-amber-400/20 text-amber-300 px-3 py-1 rounded-full border border-amber-400/30 font-bold">
                تست محیط Sandbox
            </span>
        </div>
    </div>

    <!-- Merchant & Order Summary Info -->
    <div class="bg-slate-50 p-6 border-b border-slate-200">
        <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block mb-1">پذیرنده فروشگاه:</span>
                <strong class="text-slate-800 text-sm block">داروخانه و پت‌شاپ آنلاین آسنا</strong>
                <span class="text-[11px] text-slate-500">شعبه تخصصی دکتر گلزاری</span>
            </div>
            <div class="text-left">
                <span class="text-slate-500 block mb-1">مبلغ قابل پرداخت:</span>
                <strong class="text-xl font-bold text-emerald-600 font-mono block">
                    <?= number_format($amount) ?> <span class="text-xs text-slate-600 font-normal">تومان</span>
                </strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-1">کد پیگیری تراکنش (Authority):</span>
                <span class="font-mono text-[11px] text-slate-700 bg-white px-2 py-1 rounded border border-slate-300 inline-block" dir="ltr">
                    <?= htmlspecialchars($authority) ?>
                </span>
            </div>
            <div class="text-left">
                <span class="text-slate-500 block mb-1">تاریخ و زمان درخواست:</span>
                <span class="text-[11px] text-slate-700 font-mono"><?= $currentDate ?></span>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-200/60 text-xs text-slate-600">
            <strong>شرح تراکنش:</strong> <?= htmlspecialchars($desc) ?>
        </div>
    </div>

    <!-- Mock Card Simulation Form -->
    <div class="p-6 space-y-4">
        
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700">شماره کارت بانکی (پیش‌فرض کارت تست شتاب):</label>
            <div class="relative">
                <input type="text" value="۶۰۳۷ - ۹۹۱۷ - ۴۵۸۲ - ۹۰۱۲" readonly class="w-full p-3 rounded-xl border border-slate-300 bg-slate-50 text-center font-mono font-bold tracking-widest text-slate-800 text-sm">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">credit_card</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">رمز اینترنتی (رمز پویا / ثابت):</label>
                <input type="password" value="123456" readonly class="w-full p-3 rounded-xl border border-slate-300 bg-slate-50 text-center font-mono text-slate-800 text-sm">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">کد اعتبارسنجی CVV2:</label>
                <input type="text" value="۸۴۲" readonly class="w-full p-3 rounded-xl border border-slate-300 bg-slate-50 text-center font-mono text-slate-800 text-sm">
            </div>
        </div>

        <!-- Notification Banner -->
        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-2xl flex items-center gap-3 text-xs text-blue-800">
            <span class="material-symbols-outlined text-xl text-blue-600 shrink-0">info</span>
            <span>این صفحه شبیه‌ساز پرداخت جهت تست کامل سفارش، ثبت تراکنش در دیتابیس، کسر موجودی و صدور فاکتور طراحی شده است.</span>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 flex flex-col sm:flex-row gap-3">
            <!-- Success Payment Button -->
            <a href="<?= htmlspecialchars($callback_url) ?>?Authority=<?= urlencode($authority) ?>&Status=OK" 
               class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-2xl text-center text-sm shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                پرداخت و تکمیل سفارش (تایید موفق)
            </a>

            <!-- Cancel Button -->
            <a href="<?= htmlspecialchars($callback_url) ?>?Authority=<?= urlencode($authority) ?>&Status=NOK" 
               class="sm:w-36 bg-slate-200 hover:bg-red-100 hover:text-red-700 text-slate-700 font-bold py-3.5 rounded-2xl text-center text-sm transition-all flex items-center justify-center gap-1 active:scale-95">
                <span class="material-symbols-outlined text-lg">cancel</span>
                انصراف
            </a>
        </div>
    </div>

    <!-- Footer Security Notice -->
    <div class="bg-slate-50 p-4 border-t border-slate-200 text-center text-[11px] text-slate-500 flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm text-emerald-600">lock</span>
        <span>اتصال امن SSL ۲۵۶ بیتی به شبکه تبادل اطلاعات بانکی (شاپرک)</span>
    </div>

</div>

</body>
</html>
