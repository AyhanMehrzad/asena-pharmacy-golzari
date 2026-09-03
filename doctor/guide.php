<?php
/**
 * ASENA Platform — Doctor Panel Clinical User Guide
 * Comprehensive walkthrough for veterinarians to manage appointments, shift hours, and clinical notes.
 */

require_once 'includes/doctor_header.php';
?>

<div class="p-6 md:p-10 max-w-6xl mx-auto space-y-8">
    <!-- Header Card -->
    <div class="rounded-3xl p-8 bg-gradient-to-r from-[#001a44] via-[#002d72] to-[#0f766e] text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-bold mb-3 border border-white/20">
                <span class="material-symbols-outlined text-sm">stethoscope</span>
                مستندات بالینی پزشک
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold mb-2">راهنمای کاربری پنل پزشکان آسنا (Doctor Guide)</h1>
            <p class="text-sm opacity-90 leading-relaxed max-w-2xl">
                همکار گرامی، این راهنما به شما کمک می‌کند تا به سادگی شیفت‌های ویزیت، نوبت‌های حضوری و آنلاین، مسدودی ساعت‌های مرخصی و پرونده‌های درمانی بیماران را مدیریت فرمایید.
            </p>
        </div>
    </div>

    <!-- Guide Steps Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 1. Calendar & Appointments -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">۱. تقویم روزانه و مدیریت نوبت‌ها</h2>
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                در تب <strong>نوبت‌ها و تقویم</strong>، لیست بیماران امروز، ساعت ویزیت، گونه حیوان (سگ، گربه، پرنده) و علت مراجعه ثبت شده است. پس از اتمام معاینه، وضعیت نوبت را به «انجام شده» تغییر دهید.
            </p>
        </div>

        <!-- 2. Blocking Slots -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">event_busy</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">۲. بستن اسلات‌ها و ثبت نوبت تلفنی</h2>
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                اگر بیماری به صورت تلفنی نوبت گرفته یا در ساعتی خاص امکان ویزیت ندارید (جلسه یا جراحی اضطراری)، از تب <strong>مسدودی‌ها</strong> آن ساعت را مسدود کنید تا در سایت رزرو نشود.
            </p>
        </div>

        <!-- 3. Weekly Shifts -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">schedule</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">۳. تنظیم برنامه کاری هفتگی</h2>
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                در تب <strong>برنامه کاری هفتگی</strong>، روزهای حضور خود در کلینیک و ساعات شیفت صبح و عصر را مشخص فرمایید. سیستم رزرو آنلاین بر اساس همین ساعات به مراجعین نوبت ارائه می‌دهد.
            </p>
        </div>

        <!-- 4. SMS Alerts -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">sms</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">۴. دریافت پیامک لحظه‌ای نوبت‌های جدید</h2>
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                در تب <strong>اطلاعات تماس</strong> شماره همراه خود را وارد و ذخیره کنید. به محض اینکه کاربری برای شما نوبت رزرو کند، پیامک تایید حاوی مشخصات بیمار فوراً برای شما ارسال می‌گردد.
            </p>
        </div>
    </div>

    <div class="flex justify-center pt-4">
        <a href="index.php" class="px-6 py-3 rounded-xl bg-primary text-white font-bold text-sm hover:bg-blue-800 transition-all flex items-center gap-2 shadow-md">
            <span class="material-symbols-outlined text-lg">dashboard</span>
            ورود به میز کار پزشک
        </a>
    </div>
</div>

<?php require_once 'includes/doctor_footer.php'; ?>
