<?php
/**
 * ASENA Platform — Interactive Admin Panel Master Guide
 * Comprehensive walkthrough for platform administrators to manage shop, clinic, autoship, sms, and inventory.
 */

require_once 'includes/admin_header.php';
?>

<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="relative rounded-3xl p-8 md:p-10 bg-gradient-to-r from-[#001a44] via-[#002d72] to-[#0f766e] text-white shadow-xl overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-bold mb-3 border border-white/20">
                <span class="material-symbols-outlined text-sm">menu_book</span>
                راهنمای جامع و مستندات عملیاتی
            </div>
            <h1 class="text-2xl md:text-4xl font-extrabold mb-3">راهنمای کاربری پنل مدیریت آسنا (ASENA Admin Guide)</h1>
            <p class="text-sm md:text-base opacity-90 max-w-2xl leading-relaxed">
                این راهنما نحوه مدیریت سفارشات، انبارداری، تقویم پزشکان، پیامک‌های ملی‌پیامک و سیستم ارسال خودکار (Autoship) را به صورت گام‌به‌گام توضیح می‌دهد.
            </p>
        </div>
    </div>

    <!-- Quick Navigation Anchor Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="#guide-orders" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-blue-600 mb-1 group-hover:scale-110 transition-transform">shopping_cart</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">سفارشات</div>
        </a>
        <a href="#guide-inventory" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-emerald-600 mb-1 group-hover:scale-110 transition-transform">inventory_2</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">انبار و محصولات</div>
        </a>
        <a href="#guide-clinic" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-cyan-600 mb-1 group-hover:scale-110 transition-transform">medical_services</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">کلینیک و پزشکان</div>
        </a>
        <a href="#guide-autoship" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-amber-600 mb-1 group-hover:scale-110 transition-transform">autorenew</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">ارسال خودکار</div>
        </a>
        <a href="#guide-sms" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-purple-600 mb-1 group-hover:scale-110 transition-transform">sms</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">تنظیمات پیامک</div>
        </a>
        <a href="#guide-donations" class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-primary transition-all text-center group">
            <span class="material-symbols-outlined text-3xl text-rose-600 mb-1 group-hover:scale-110 transition-transform">volunteer_activism</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">خیریه و امداد</div>
        </a>
    </div>

    <!-- Section 1: Orders Management -->
    <section id="guide-orders" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined">shopping_cart</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۱. چرخه مدیریت و پردازش سفارشات (Orders)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > سفارشات (orders.php)</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs md:text-sm">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                <span class="font-bold text-blue-600 block mb-1">مرحله ۱: بررسی سفارش جدید</span>
                سفارش‌های جدید با وضعیت «در انتظار بررسی» یا «پرداخت شده» ثبت می‌شوند. نام خریدار، شماره تماس، آدرس پستی و اقلام خریداری‌شده را کنترل کنید.
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                <span class="font-bold text-amber-600 block mb-1">مرحله ۲: بسته‌بندی و ارسال</span>
                پس از تجمیع اقلام از انبار، وضعیت سفارش را به «در حال پردازش» و پس از تحویل به پیک/پست به «ارسال شده» تغییر دهید. با این کار پیامک کد رهگیری خودکار به مشتری ارسال می‌گردد.
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700">
                <span class="font-bold text-emerald-600 block mb-1">مرحله ۳: تکمیل یا لغو</span>
                پس از تحویل مرسوله، وضعیت به «تکمیل شده» تغییر می‌یابد. در صورت انصراف، لغو سفارش به طور خودکار موجودی کالا را به انبار بازمی‌گرداند.
            </div>
        </div>
    </section>

    <!-- Section 2: Inventory & Products -->
    <section id="guide-inventory" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۲. انبارداری و افزودن کالاهای جدید (Inventory)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > انبار و محصولات (inventory.php)</p>
            </div>
        </div>
        <ul class="list-disc pr-6 space-y-2 text-xs md:text-sm text-slate-700 dark:text-slate-300">
            <li><strong>افزودن محصول جدید:</strong> برای ثبت غذای خشک، کنسرو یا مکمل، نام دقیق، دسته‌بندی (سگ، گربه، پرنده)، قیمت اصلی، قیمت تخفیف‌دار و تعداد موجودی انبار را وارد نمایید.</li>
            <li><strong>تصویر شاخص محصول:</strong> حتماً تصویر واضح با پس‌زمینه سفید و فرمت JPG یا WebP بارگذاری کنید تا در سرچ تصویر گوگل (Google Image Search) امتیاز بالا بگیرد.</li>
            <li><strong>هشدار کمبود موجودی:</strong> کالاهایی که موجودی آنها به کمتر از ۵ عدد برسد با برچسب زرد رنگ مشخص می‌شوند تا قبل از اتمام، سفارش تامین ثبت گردد.</li>
        </ul>
    </section>

    <!-- Section 3: Clinic & Doctors -->
    <section id="guide-clinic" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/40 text-cyan-600 flex items-center justify-center">
                <span class="material-symbols-outlined">medical_services</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۳. مدیریت کلینیک و شیفت پزشکان (Clinic Management)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > مدیریت کلینیک (clinic_management.php)</p>
            </div>
        </div>
        <p class="text-xs md:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            در این بخش ادمین می‌تواند لیست پزشکان معالج، تخصص، هزینه ویزیت و شیفت‌های هفتگی را تعریف کند.
            همچنین با استفاده از ابزار <strong>بستن اسلات‌های اضطراری (Blocked Slots)</strong>، اگر پزشکی در ساعت خاصی در کلینیک حضور ندارد، می‌توانید آن بازه زمانی را غیرفعال کنید تا بیمار نتواند نوبت رزرو کند.
        </p>
    </section>

    <!-- Section 4: Autoship Subscriptions -->
    <section id="guide-autoship" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined">autorenew</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۴. سیستم اشتراک و ارسال دوره‌ای (Autoship)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > اشتراک‌های دوره‌ای (subscriptions.php)</p>
            </div>
        </div>
        <p class="text-xs md:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            سیستم Autoship آسنا مجهز به ورکر خودکار است (`autoship_worker.php`). این ورکر هر روز صبح اشتراک‌های سررسیدشده را شناسایی، فاکتور صادر و پیامک یادآوری به مشتری ارسال می‌کند. ادمین می‌تواند وضعیت چرخه هر مشترک را مشاهده، تاریخ تحویل را جلو/عقب بیندازد یا در صورت درخواست مشتری اشتراک را تعلیق نماید.
        </p>
    </section>

    <!-- Section 5: Melipayamak SMS Gateway -->
    <section id="guide-sms" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 flex items-center justify-center">
                <span class="material-symbols-outlined">sms</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۵. تنظیمات و عیب‌یابی درگاه پیامک (Melipayamak Settings)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > تنظیمات پیامک (sms_settings.php)</p>
            </div>
        </div>
        <div class="space-y-3 text-xs md:text-sm text-slate-700 dark:text-slate-300">
            <p>سامانه آسنا از وب‌سرویس پترن/خدماتی ملی‌پیامک (BaseServiceNumber) برای ارسال آنی کد تایید OTP بدون تاخیر بلک‌لیست استفاده می‌کند.</p>
            <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800">
                <span class="font-bold text-purple-800 dark:text-purple-300 block mb-1">نکته حیاتی IP Whitelist:</span>
                ملی‌پیامک آی‌پی‌های خروجی سرور را کنترل می‌کند. در صورت جابجایی هاست، آی‌پی جدید سرور (مانند <code>78.159.108.66</code>) باید در پنل کاربری ملی‌پیامک در بخش <em>تنظیمات وب‌سرویس</em> ثبت شود، در غیر این صورت خطای -111 برگردانده می‌شود.
            </div>
        </div>
    </section>

    <!-- Section 6: Donations & Charity -->
    <section id="guide-donations" class="p-6 md:p-8 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-700 pb-4">
            <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center">
                <span class="material-symbols-outlined">volunteer_activism</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">۶. خیریه و پرونده‌های درمانی حیوانات (Charity)</h2>
                <p class="text-xs text-slate-500">مسیر دسترسی: منوی کناری > خیریه (donations.php)</p>
            </div>
        </div>
        <p class="text-xs md:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            برای هر حیوان آسیب‌دیده، می‌توانید یک کمپین حمایتی با عنوان، شرح حادثه، هدف مالی درمانی و تصاویر اولیه ایجاد کنید. با واریز مبالغ توسط کاربران، درصد تکمیل کمپین به صورت گرافیکی نمایش داده شده و پس از رسیدن به مبلغ هدف، بسته می‌شود.
        </p>
    </section>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
