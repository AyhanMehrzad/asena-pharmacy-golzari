<?php
include 'includes/header.php';
?>

<!-- Add glowing effects to root if needed -->
<style>
.subscription-card-glow {
    box-shadow: 0px 4px 40px rgba(0, 45, 114, 0.08);
}
</style>

<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-primary-container/10 blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-secondary-container/10 blur-[150px]"></div>
</div>

<main class="py-24">
<!-- Hero Section -->
        <section class="max-w-4xl mx-auto px-margin-mobile md:px-margin-desktop mb-24 text-center">
            <h1 class="font-headline-lg text-headline-lg md:text-display-lg text-primary mb-6 font-bold">برنامه‌های
                اشتراک هوشمند آسنا</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
                با عضویت در برنامه‌های اشتراک ما، نه تنها ۱۵٪ در هزینه‌ها صرفه‌جویی می‌کنید، بلکه خیالتان از بابت تامین
                همیشگی نیازهای حیوان خانگی‌تان راحت خواهد بود.
            </p>
        </section>
        <!-- Subscription Cards Section -->
        <section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                <!-- 3-Month Plan -->
                <div class="bg-primary-container rounded-[2.5rem] p-10 text-white flex flex-col space-y-10 shadow-xl hover:-translate-y-2 transition-transform">
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۳ ماهه</div>
                        <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                            <div class="w-4 h-4 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold">اشتراک پایه</h3>
                        <div class="flex items-baseline gap-1 mb-2">
                            <span class="text-3xl font-bold">۲,۵۰۰,۰۰۰</span>
                            <span class="text-xs opacity-70">تومان / ماهانه</span>
                        </div>
                        <ul class="space-y-4 text-white/70 text-sm">
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>ارسال رایگان دوره‌ای</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>۵٪ تخفیف روی تمامی محصولات</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>یک نوبت ویزیت چک‌آپ رایگان</li>
                        </ul>
                    </div>
                    <a href="subscription_checkout.php?plan=3_months" class="block text-center w-full bg-white text-primary-container py-4 rounded-2xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto">انتخاب اشتراک</a>
                </div>
                <!-- 6-Month Plan -->
                <div class="bg-primary-container rounded-[2.5rem] p-10 text-white flex flex-col space-y-10 shadow-2xl border-4 border-secondary-container relative transform scale-105 z-10">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-secondary-container text-white px-8 py-2 rounded-full text-xs font-bold shadow-lg">بهترین ارزش</div>
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۶ ماهه</div>
                        <div class="w-12 h-12 rounded-full border-2 border-white flex items-center justify-center">
                            <div class="w-6 h-6 rounded-full bg-white"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-3xl font-bold">اشتراک ویژه</h3>
                        <div class="flex items-baseline gap-1 mb-2">
                            <span class="text-4xl font-bold">۲,۱۰۰,۰۰۰</span>
                            <span class="text-xs opacity-70">تومان / ماهانه</span>
                        </div>
                        <ul class="space-y-4 text-white/90 text-sm">
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>ارسال رایگان فوری (اکسپرس)</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>۱۰٪ تخفیف روی تمامی محصولات</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>پشتیبانی اختصاصی ۲۴ ساعته</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-xl">check_circle</span>دو نوبت چک‌آپ و واکسیناسیون</li>
                        </ul>
                    </div>
                    <a href="subscription_checkout.php?plan=6_months" class="block text-center w-full bg-secondary-container text-white py-5 rounded-2xl font-bold shadow-lg hover:shadow-2xl transition-all mt-auto">خرید بهترین گزینه</a>
                </div>
                <!-- 12-Month Plan -->
                <div class="bg-primary-container rounded-[2.5rem] p-10 text-white flex flex-col space-y-10 shadow-xl hover:-translate-y-2 transition-transform">
                    <div class="flex justify-between items-start">
                        <div class="bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-xs font-bold">۱۲ ماهه</div>
                        <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                            <div class="w-4 h-4 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold">اشتراک طلایی</h3>
                        <div class="flex items-baseline gap-1 mb-2">
                            <span class="text-3xl font-bold">۱,۸۵۰,۰۰۰</span>
                            <span class="text-xs opacity-70">تومان / ماهانه</span>
                        </div>
                        <ul class="space-y-4 text-white/70 text-sm">
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>ارسال رایگان بدون محدودیت</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>۱۵٪ تخفیف ثابت سالانه</li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary-container text-lg">check_circle</span>بسته سلامت سالانه رایگان</li>
                        </ul>
                    </div>
                    <a href="subscription_checkout.php?plan=12_months" class="block text-center w-full bg-white text-primary-container py-4 rounded-2xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto">انتخاب اشتراک</a>
                </div>
            </div>
        </section>
        <!-- How it Works Section -->
        <section class="mt-32 py-24 border-y border-outline-variant/30">
            <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
                <h2 class="font-headline-md text-headline-md text-center text-primary mb-16">اشتراک هوشمند چگونه کار
                    می‌کند؟</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12">
                    <div
                        class="bg-white/60 backdrop-blur-xl border border-white/20 rounded-lg p-8 flex flex-col subscription-card-glow hover:scale-105 hover:border-primary/40 transition-all duration-300 cursor-pointer">
                        <div
                            class="w-20 h-20 bg-surface-container-lowest rounded-full flex items-center justify-center mx-auto mb-8 shadow-sm border border-outline-variant group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                            <span class="material-symbols-outlined text-primary text-3xl">fact_check</span>
                        </div>
                        <h4 class="font-title-lg text-title-lg mb-3">انتخاب برنامه</h4>
                        <p class="text-body-md text-on-surface-variant leading-relaxed">برنامه زمانی مناسب با نیازهای پت
                            خود را انتخاب کنید.</p>
                    </div>
                    <div class="text-center group">
                        <div
                            class="w-20 h-20 bg-surface-container-lowest rounded-full flex items-center justify-center mx-auto mb-8 shadow-sm border border-outline-variant group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                            <span class="material-symbols-outlined text-primary text-3xl">add_shopping_cart</span>
                        </div>
                        <h4 class="font-title-lg text-title-lg mb-3">افزودن محصولات</h4>
                        <p class="text-body-md text-on-surface-variant leading-relaxed">کالاها و برندهای محبوبتان را به
                            لیست دوره اضافه کنید.</p>
                    </div>
                    <div class="text-center group">
                        <div
                            class="w-20 h-20 bg-surface-container-lowest rounded-full flex items-center justify-center mx-auto mb-8 shadow-sm border border-outline-variant group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                            <span class="material-symbols-outlined text-secondary text-3xl">savings</span>
                        </div>
                        <h4 class="font-title-lg text-title-lg mb-3">ذخیره ۱۵ درصدی</h4>
                        <p class="text-body-md text-on-surface-variant leading-relaxed">تخفیف ویژه اشتراک به‌صورت خودکار
                            اعمال خواهد شد.</p>
                    </div>
                    <div class="text-center group">
                        <div
                            class="w-20 h-20 bg-surface-container-lowest rounded-full flex items-center justify-center mx-auto mb-8 shadow-sm border border-outline-variant group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                            <span class="material-symbols-outlined text-primary text-3xl">sentiment_satisfied</span>
                        </div>
                        <h4 class="font-title-lg text-title-lg mb-3">آرامش کامل</h4>
                        <p class="text-body-md text-on-surface-variant leading-relaxed">ما سر وقت همه‌چیز را درب منزل
                            تحویل می‌دهیم.</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- FAQ Section -->
        <section class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop mt-32">
            <h2 class="font-headline-md text-headline-md text-center text-primary mb-12">سوالات متداول</h2>
            <div class="space-y-4">
                <details
                    class="group bg-white/40 backdrop-blur-sm border border-outline-variant/20 rounded-lg overflow-hidden transition-all duration-300"
                    open="">
                    <summary
                        class="flex justify-between items-center p-6 cursor-pointer list-none hover:bg-surface-container-lowest transition-colors">
                        <span class="font-title-lg text-title-lg">آیا می‌توانم اشتراک خود را لغو کنم؟</span>
                        <span
                            class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div
                        class="p-6 pt-0 text-body-lg text-on-surface-variant border-t border-outline-variant/30 leading-relaxed">
                        بله، شما در هر زمان می‌توانید اشتراک خود را از طریق پنل کاربری لغو یا متوقف کنید. هیچ جریمه‌ای
                        برای لغو اشتراک وجود ندارد.
                    </div>
                </details>
                <details
                    class="group bg-white/40 backdrop-blur-sm border border-outline-variant/20 rounded-lg overflow-hidden transition-all duration-300">
                    <summary
                        class="flex justify-between items-center p-6 cursor-pointer list-none hover:bg-surface-container-lowest transition-colors">
                        <span class="font-title-lg text-title-lg">تغییر محصولات در دوره اشتراک امکان‌پذیر است؟</span>
                        <span
                            class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div
                        class="p-6 pt-0 text-body-lg text-on-surface-variant border-t border-outline-variant/30 leading-relaxed">
                        بله، تا ۴۸ ساعت قبل از موعد ارسال بعدی، شما می‌توانید لیست محصولات خود را ویرایش کنید.
                    </div>
                </details>
                <details
                    class="group bg-white/40 backdrop-blur-sm border border-outline-variant/20 rounded-lg overflow-hidden transition-all duration-300">
                    <summary
                        class="flex justify-between items-center p-6 cursor-pointer list-none hover:bg-surface-container-lowest transition-colors">
                        <span class="font-title-lg text-title-lg">هزینه ارسال چگونه محاسبه می‌شود؟</span>
                        <span
                            class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div
                        class="p-6 pt-0 text-body-lg text-on-surface-variant border-t border-outline-variant/30 leading-relaxed">
                        تمامی برنامه‌های اشتراک ASENA شامل ارسال رایگان برای تمامی خریدهای دوره‌ای می‌باشند.
                    </div>
                </details>
            </div>
        </section>
</main>
<script>
    // Smooth hover interactions for buttons
    document.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('mousedown', () => btn.classList.add('scale-95'));
        btn.addEventListener('mouseup', () => btn.classList.remove('scale-95'));
        btn.addEventListener('mouseleave', () => btn.classList.remove('scale-95'));
    });
</script>
<?php
include 'includes/footer.php';
?>
