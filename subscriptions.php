<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fetch available products for the custom box builder
$stmt = $pdo->query("SELECT id, name, category, price, discount_price, image_url, brand, target_animal, is_autoship FROM products WHERE stock > 0 ORDER BY is_autoship DESC, rating_cache DESC LIMIT 80");
$catalog_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<style>
.subscription-card-glow {
    box-shadow: 0px 4px 40px rgba(0, 45, 114, 0.08);
}
</style>

<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-primary-container/10 blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-secondary-container/10 blur-[150px]"></div>
</div>

<main class="py-12 lg:py-20">
    
    <!-- Hero Section -->
    <section class="max-w-4xl mx-auto px-margin-mobile md:px-margin-desktop mb-16 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-secondary-container/15 text-secondary-container rounded-full text-xs font-bold mb-4 shadow-sm">
            <span class="material-symbols-outlined text-base">autorenew</span>
            سرویس هوشمند تحویل خودکار (Autoship & Care Boxes)
        </div>
        <h1 class="text-3xl md:text-5xl font-bold text-primary mb-4">برنامه‌های اشتراک و تحویل خودکار آسنا</h1>
        <p class="text-sm md:text-base text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
            با عضویت در برنامه‌های اشتراک ما، علاوه بر <strong>۱۵٪ تخفیف دائمی</strong> و <strong>ارسال رایگان</strong>، دیگر نگران تمام شدن دارو، مکمل یا غذای پت خود نخواهید بود.
        </p>

        <!-- Quick Jump Links -->
        <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
            <a href="#suggested-plans" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container transition-all shadow-sm">
                📦 پکیج‌های پیشنهادی متخصصین
            </a>
            <a href="#custom-box-builder" class="bg-secondary-container hover:bg-[#ea580c] text-white px-5 py-2.5 rounded-xl font-bold text-xs transition-all shadow-md flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">build</span>
                🛠️ ساخت پکیج اشتراک اختصاصی شما
            </a>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 1: PRE-BUILT SUGGESTED PACKAGES                                   -->
    <!-- ========================================================================= -->
    <section id="suggested-plans" class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop scroll-mt-24 mb-24">
        <div class="text-center mb-10">
            <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full mb-2 inline-block">پکیج‌های پیشنهادی</span>
            <h2 class="text-2xl font-bold text-primary">پلن‌های جامع و آماده مراقبت دوره‌ای</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            <!-- 3-Month Plan -->
            <div class="bg-primary-container rounded-[2.5rem] p-8 lg:p-10 text-white flex flex-col space-y-8 shadow-xl hover:-translate-y-2 transition-transform">
                <div class="flex justify-between items-start">
                    <div class="bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold">۳ ماهه</div>
                    <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                        <div class="w-4 h-4 rounded-full bg-white/10"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold">اشتراک پایه</h3>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold font-mono">۲,۵۰۰,۰۰۰</span>
                        <span class="text-xs opacity-70">تومان / ماهانه</span>
                    </div>
                    <ul class="space-y-3 text-white/80 text-xs leading-relaxed">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>ارسال رایگان دوره‌ای درب منزل</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>۱۰٪ تخفیف روی تمامی داروها و محصولات</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>یک نوبت چک‌آپ و مشاوره دارویی رایگان</li>
                    </ul>
                </div>
                <a href="subscription_checkout.php?plan=3_months" class="block text-center w-full bg-white text-primary-container py-3.5 rounded-xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto text-xs active:scale-95 shadow-md">انتخاب اشتراک ۳ ماهه</a>
            </div>

            <!-- 6-Month Plan -->
            <div class="bg-primary-container rounded-[2.5rem] p-8 lg:p-10 text-white flex flex-col space-y-8 shadow-2xl border-4 border-secondary-container relative transform scale-100 lg:scale-105 z-10">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary-container text-white px-6 py-1 rounded-full text-xs font-bold shadow-lg">بهترین ارزش • محبوب‌ترین</div>
                <div class="flex justify-between items-start">
                    <div class="bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold">۶ ماهه</div>
                    <div class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center">
                        <div class="w-4 h-4 rounded-full bg-white"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold">اشتراک ویژه (VIP)</h3>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold font-mono">۲,۱۰۰,۰۰۰</span>
                        <span class="text-xs opacity-70">تومان / ماهانه</span>
                    </div>
                    <ul class="space-y-3 text-white/90 text-xs leading-relaxed">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>ارسال رایگان فوری اکسپرس با زنجیره سرد</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>۱۵٪ تخفیف ثابت روی تمامی سفارشات</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>پشتیبانی اولویت‌دار داروساز ۲۴ ساعته</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>دو نوبت ویزیت و واکسیناسیون رایگان</li>
                    </ul>
                </div>
                <a href="subscription_checkout.php?plan=6_months" class="block text-center w-full bg-secondary-container text-white py-4 rounded-xl font-bold shadow-lg hover:bg-[#ea580c] transition-all mt-auto text-xs active:scale-95">خرید بهترین گزینه (۶ ماهه)</a>
            </div>

            <!-- 12-Month Plan -->
            <div class="bg-primary-container rounded-[2.5rem] p-8 lg:p-10 text-white flex flex-col space-y-8 shadow-xl hover:-translate-y-2 transition-transform">
                <div class="flex justify-between items-start">
                    <div class="bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold">۱۲ ماهه</div>
                    <div class="w-10 h-10 rounded-full border-2 border-white/20 flex items-center justify-center">
                        <div class="w-4 h-4 rounded-full bg-white/10"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold">اشتراک طلایی سالانه</h3>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold font-mono">۱,۸۵۰,۰۰۰</span>
                        <span class="text-xs opacity-70">تومان / ماهانه</span>
                    </div>
                    <ul class="space-y-3 text-white/80 text-xs leading-relaxed">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>ارسال رایگان بدون محدودیت در طول سال</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>۱۵٪ تخفیف ثابت سالانه + هدایای تولد پت</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-secondary-container text-base">check_circle</span>بسته چک‌آپ کامل و آزمایشات سالانه</li>
                    </ul>
                </div>
                <a href="subscription_checkout.php?plan=12_months" class="block text-center w-full bg-white text-primary-container py-3.5 rounded-xl font-bold hover:bg-secondary-container hover:text-white transition-colors mt-auto text-xs active:scale-95 shadow-md">انتخاب اشتراک ۱۲ ماهه</a>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 2: CUSTOM SUBSCRIPTION PACK BUILDER STUDIO (User's Vision)       -->
    <!-- ========================================================================= -->
    <section id="custom-box-builder" class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop scroll-mt-24 mb-24">
        
        <div class="bg-gradient-to-br from-slate-900 via-primary to-slate-950 text-white rounded-[2.5rem] p-6 sm:p-10 lg:p-12 shadow-2xl relative overflow-hidden border border-white/10">
            <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 mb-8 pb-6 border-b border-white/15 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-secondary-container text-white rounded-full text-xs font-bold mb-3 shadow-md">
                        <span class="material-symbols-outlined text-sm">tune</span>
                        استودیوی اشتراک هوشمند
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight">🛠️ پکیج اشتراک اختصاصی خودت رو بساز</h2>
                    <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-2xl">
                        اگر پکیج‌های پیشنهادی بالا را دوست ندارید، هر محصول دارویی، مکمل، غذا یا تشویقی را که می‌خواهید انتخاب کنید تا با <strong>۱۵٪ تخفیف ثابت</strong> و <strong>ارسال رایگان در دوره زمانی دلخواه</strong> برای شما ارسال شود.
                    </p>
                </div>

                <div class="flex items-center gap-3 bg-white/10 px-4 py-2.5 rounded-2xl border border-white/15">
                    <span class="material-symbols-outlined text-secondary-container text-2xl">savings</span>
                    <div class="text-right">
                        <span class="text-xs text-white/80 block">تخفیف روی تمام اقلام انتخابی:</span>
                        <span class="text-sm font-bold text-secondary-container">۱۵٪ تخفیف مداوم + ارسال رایگان</span>
                    </div>
                </div>
            </div>

            <!-- Builder Layout: Left Side (Catalog Picker) & Right Side (Live Box Tray) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start relative z-10">
                
                <!-- Left: Product Catalog Browser (8 Cols) -->
                <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                    
                    <!-- Species & Category Filters + Live Search -->
                    <div class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/15 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5 text-xs font-bold" id="speciesFilterBar">
                                <button type="button" onclick="filterCustomCatalog('all')" class="species-btn active px-3 py-1.5 rounded-xl bg-secondary-container text-white transition-all" data-species="all">همه حیوانات 🐾</button>
                                <button type="button" onclick="filterCustomCatalog('dog')" class="species-btn px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all" data-species="dog">سگ 🐕</button>
                                <button type="button" onclick="filterCustomCatalog('cat')" class="species-btn px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all" data-species="cat">گربه 🐈</button>
                                <button type="button" onclick="filterCustomCatalog('horse')" class="species-btn px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all" data-species="horse">اسب 🐎</button>
                                <button type="button" onclick="filterCustomCatalog('cow')" class="species-btn px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all" data-species="cow">دام 🐄</button>
                            </div>

                            <div class="relative w-full sm:w-48">
                                <input type="text" id="builderSearchInput" oninput="searchCustomCatalog(this.value)" placeholder="جستجوی دارو یا کالا..." class="w-full bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 text-xs text-white placeholder-white/50 outline-none focus:border-secondary-container">
                                <span class="material-symbols-outlined absolute left-2 top-2 text-white/50 text-sm">search</span>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[600px] overflow-y-auto pr-1 no-scrollbar" id="builderProductsGrid">
                        <?php foreach($catalog_products as $prod): ?>
                            <?php 
                                $is_pharma = (str_contains($prod['category'] ?? '', 'دارو') || str_contains($prod['category'] ?? '', 'مکمل'));
                                $p_species = $prod['target_animal'] ?? 'all';
                            ?>
                            <div class="builder-product-card bg-white text-on-surface rounded-2xl p-4 shadow-md flex flex-col justify-between border border-outline-variant/30 hover:border-secondary-container transition-all"
                                 data-id="<?= $prod['id'] ?>"
                                 data-name="<?= htmlspecialchars($prod['name']) ?>"
                                 data-price="<?= $prod['price'] ?>"
                                 data-species="<?= htmlspecialchars($p_species) ?>"
                                 data-img="<?= htmlspecialchars($prod['image_url'] ?? '') ?>">
                                
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-surface-container-lowest shrink-0 border border-outline-variant/30">
                                        <img src="<?= htmlspecialchars($prod['image_url']) ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover" alt="<?= htmlspecialchars($prod['name']) ?>">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded-md mb-1 inline-block"><?= htmlspecialchars($prod['category']) ?></span>
                                        <h4 class="text-xs font-bold text-on-surface truncate leading-tight"><?= htmlspecialchars($prod['name']) ?></h4>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-primary font-mono"><?= number_format($prod['price']) ?> تومان</span>
                                            <span class="text-[10px] text-emerald-800 bg-emerald-100 font-bold px-1.5 py-0.2 rounded">-۱۵٪ اشتراک</span>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" onclick="addToCustomBox(<?= $prod['id'] ?>, '<?= addslashes($prod['name']) ?>', <?= $prod['price'] ?>, '<?= addslashes($prod['image_url'] ?? '') ?>')" 
                                        class="builder-add-btn w-full bg-secondary-container hover:bg-[#ea580c] text-white py-2 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1 active:scale-95 cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">add_circle</span>
                                    <span>افزودن به باکس اشتراک</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Live Box Tray & Checkout (4 Cols) -->
                <div class="lg:col-span-5 xl:col-span-4 sticky top-28">
                    <div class="bg-white text-on-surface rounded-3xl p-6 shadow-2xl border-2 border-secondary-container space-y-6">
                        
                        <div class="flex items-center justify-between border-b border-outline-variant/30 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary-container text-2xl">all_inclusive</span>
                                <h3 class="font-bold text-primary text-base">باکس اختصاصی شما</h3>
                            </div>
                            <span class="text-xs font-bold bg-secondary-container/10 text-secondary-container px-2.5 py-0.5 rounded-full font-mono" id="boxItemCount">۰ قلم کالا</span>
                        </div>

                        <!-- Selected Items Container -->
                        <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1 no-scrollbar" id="selectedBoxItemsList">
                            <div class="text-center py-8 text-on-surface-variant space-y-2" id="emptyBoxPlaceholder">
                                <span class="material-symbols-outlined text-3xl opacity-40">inventory_2</span>
                                <p class="text-xs font-medium">هنوز هیچ کالایی به باکس خود اضافه نکرده‌اید.</p>
                                <p class="text-[11px] text-primary font-bold">از لیست سمت راست اقلام دلخواه را انتخاب کنید.</p>
                            </div>
                        </div>

                        <!-- Interval Selector -->
                        <div class="bg-surface-container-low p-3.5 rounded-2xl border border-outline-variant/30 space-y-2">
                            <label class="block text-xs font-bold text-primary">دوره تکرار و ارسال باکس:</label>
                            <select id="boxIntervalSelect" onchange="updateBoxCalculations()" class="w-full bg-white border border-outline-variant rounded-xl p-2 text-xs font-bold text-on-surface outline-none focus:border-secondary-container cursor-pointer shadow-sm">
                                <option value="1">هر ۱ ماه یک‌بار (پیش‌فرض پیشنهادی)</option>
                                <option value="2">هر ۲ ماه یک‌بار</option>
                                <option value="3">هر ۳ ماه یک‌بار</option>
                            </select>
                        </div>

                        <!-- Pricing Breakdown -->
                        <div class="space-y-2 text-xs border-t border-outline-variant/30 pt-3">
                            <div class="flex justify-between items-center text-on-surface-variant">
                                <span>قیمت کل کاتالوگ:</span>
                                <span class="font-bold font-mono text-on-surface" id="boxSubtotalText">۰ تومان</span>
                            </div>
                            <div class="flex justify-between items-center text-emerald-800 font-bold bg-emerald-50 p-2 rounded-xl">
                                <span>تخفیف ویژه اشتراک (۱۵٪):</span>
                                <span class="font-mono" id="boxDiscountText">۰ تومان</span>
                            </div>
                            <div class="flex justify-between items-center text-on-surface-variant">
                                <span>هزینه ارسال:</span>
                                <span class="text-status-active font-bold">رایگان (طرح اشتراک)</span>
                            </div>
                        </div>

                        <!-- Final Amount -->
                        <div class="border-t border-secondary-container/20 pt-3 flex justify-between items-center">
                            <span class="text-xs font-bold text-primary">مبلغ هر نوبت ارسال:</span>
                            <span class="text-lg font-bold text-secondary-container font-mono" id="boxFinalPriceText">۰ تومان</span>
                        </div>

                        <!-- Submit to Checkout Form -->
                        <form action="subscription_checkout.php" method="POST" id="customBoxCheckoutForm" onsubmit="return validateCustomBoxSubmit()">
                            <input type="hidden" name="plan" value="custom">
                            <input type="hidden" name="custom_items" id="customItemsPayload" value="{}">
                            <input type="hidden" name="interval_months" id="intervalMonthsPayload" value="1">
                            <button type="submit" id="boxCheckoutBtn" disabled class="w-full bg-secondary-container hover:bg-[#ea580c] disabled:opacity-50 disabled:cursor-not-allowed text-white py-3.5 rounded-xl font-bold text-xs shadow-lg transition-all flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                                <span class="material-symbols-outlined text-base">check_circle</span>
                                <span>ثبت و فعال‌سازی باکس اشتراک</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop mt-20">
        <h2 class="text-2xl font-bold text-center text-primary mb-8">سوالات متداول اشتراک‌ها</h2>
        <div class="space-y-4">
            <details class="group bg-white rounded-2xl border border-outline-variant/30 overflow-hidden shadow-sm transition-all" open>
                <summary class="flex justify-between items-center p-5 cursor-pointer list-none hover:bg-surface-container-low transition-colors font-bold text-sm text-primary">
                    <span>آیا می‌توانم اقلام یا تاریخ ارسال اشتراک خود را تغییر دهم؟</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="p-5 pt-0 text-xs text-on-surface-variant leading-relaxed border-t border-outline-variant/20">
                    بله! شما در هر زمان از پنل کاربری خود می‌توانید تاریخ نوبت بعدی را جلو یا عقب بیندازید، یک ماه را به تعویق بیندازید و یا بدون هیچ‌گونه جریمه‌ای اشتراک را لغو نمایید.
                </div>
            </details>
            <details class="group bg-white rounded-2xl border border-outline-variant/30 overflow-hidden shadow-sm transition-all">
                <summary class="flex justify-between items-center p-5 cursor-pointer list-none hover:bg-surface-container-low transition-colors font-bold text-sm text-primary">
                    <span>تخفیف ۱۵ درصدی اشتراک چگونه محاسبه می‌شود؟</span>
                    <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="p-5 pt-0 text-xs text-on-surface-variant leading-relaxed border-t border-outline-variant/20">
                    تمامی محصولات انتخابی در پکیج اشتراک اختصاصی یا پکیج‌های پیشنهادی مشمول ۱۵٪ تخفیف ثابت و ارسال رایگان دوره‌ای خواهند بود.
                </div>
            </details>
        </div>
    </section>
</main>

<!-- Custom Box Builder Client Script -->
<script>
let customBox = {}; // { productId: { name, price, qty, img } }

function addToCustomBox(id, name, price, img) {
    if (customBox[id]) {
        customBox[id].qty++;
    } else {
        customBox[id] = { name: name, price: price, qty: 1, img: img };
    }
    renderCustomBox();
}

function removeFromCustomBox(id) {
    delete customBox[id];
    renderCustomBox();
}

function changeBoxQty(id, delta) {
    if (customBox[id]) {
        customBox[id].qty += delta;
        if (customBox[id].qty <= 0) {
            delete customBox[id];
        }
    }
    renderCustomBox();
}

function renderCustomBox() {
    const list = document.getElementById('selectedBoxItemsList');
    const placeholder = document.getElementById('emptyBoxPlaceholder');
    const checkoutBtn = document.getElementById('boxCheckoutBtn');
    const itemCountBadge = document.getElementById('boxItemCount');
    
    const keys = Object.keys(customBox);
    let totalItems = 0;
    let subtotal = 0;
    
    if (keys.length === 0) {
        list.innerHTML = `
            <div class="text-center py-8 text-on-surface-variant space-y-2" id="emptyBoxPlaceholder">
                <span class="material-symbols-outlined text-3xl opacity-40">inventory_2</span>
                <p class="text-xs font-medium">هنوز هیچ کالایی به باکس خود اضافه نکرده‌اید.</p>
                <p class="text-[11px] text-primary font-bold">از لیست سمت راست اقلام دلخواه را انتخاب کنید.</p>
            </div>
        `;
        checkoutBtn.disabled = true;
        itemCountBadge.textContent = '۰ قلم کالا';
    } else {
        let html = '';
        keys.forEach(id => {
            const item = customBox[id];
            totalItems += item.qty;
            subtotal += item.price * item.qty;
            const itemImg = item.img || 'assets/images/pharma-default.svg';
            
            html += `
                <div class="flex items-center justify-between gap-2 p-2 bg-surface-container-low rounded-xl border border-outline-variant/30 text-xs">
                    <img src="${itemImg}" class="w-10 h-10 rounded-lg object-cover bg-white shrink-0" onerror="this.src='assets/images/pharma-default.svg'">
                    <div class="flex-1 min-w-0">
                        <h5 class="font-bold text-on-surface truncate text-[11px]">${item.name}</h5>
                        <span class="text-[10px] text-primary font-mono">${(item.price * 0.85).toLocaleString('fa-IR')} ت</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-white p-1 rounded-lg border border-outline-variant/40">
                        <button type="button" onclick="changeBoxQty(${id}, -1)" class="w-5 h-5 flex items-center justify-center text-xs text-on-surface-variant hover:text-error cursor-pointer">-</button>
                        <span class="font-bold font-mono text-xs w-4 text-center">${item.qty}</span>
                        <button type="button" onclick="changeBoxQty(${id}, 1)" class="w-5 h-5 flex items-center justify-center text-xs text-on-surface-variant hover:text-primary cursor-pointer">+</button>
                    </div>
                    <button type="button" onclick="removeFromCustomBox(${id})" class="text-error hover:opacity-75 p-1 cursor-pointer">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </div>
            `;
        });
        list.innerHTML = html;
        checkoutBtn.disabled = false;
        itemCountBadge.textContent = totalItems + ' قلم کالا';
    }
    
    updateBoxCalculations();
}

function updateBoxCalculations() {
    const keys = Object.keys(customBox);
    let subtotal = 0;
    let simplePayload = {};
    
    keys.forEach(id => {
        subtotal += customBox[id].price * customBox[id].qty;
        simplePayload[id] = customBox[id].qty;
    });
    
    const discount = Math.round(subtotal * 0.15);
    const finalPrice = subtotal - discount;
    
    document.getElementById('boxSubtotalText').textContent = subtotal.toLocaleString('fa-IR') + ' تومان';
    document.getElementById('boxDiscountText').textContent = '-' + discount.toLocaleString('fa-IR') + ' تومان';
    document.getElementById('boxFinalPriceText').textContent = finalPrice.toLocaleString('fa-IR') + ' تومان';
    
    document.getElementById('customItemsPayload').value = JSON.stringify(simplePayload);
    document.getElementById('intervalMonthsPayload').value = document.getElementById('boxIntervalSelect').value;
}

function validateCustomBoxSubmit() {
    if (Object.keys(customBox).length === 0) {
        alert('لطفاً حداقل ۱ قلم کالا به باکس اشتراک خود اضافه کنید.');
        return false;
    }
    return true;
}

function filterCustomCatalog(species) {
    document.querySelectorAll('.species-btn').forEach(b => {
        b.classList.remove('bg-secondary-container', 'active');
        b.classList.add('bg-white/10');
    });
    const activeBtn = document.querySelector(`.species-btn[data-species="${species}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('bg-white/10');
        activeBtn.classList.add('bg-secondary-container', 'active');
    }
    
    const cards = document.querySelectorAll('.builder-product-card');
    cards.forEach(c => {
        const cSpecies = c.getAttribute('data-species');
        if (species === 'all' || cSpecies === species || cSpecies === 'all' || !cSpecies) {
            c.style.display = 'flex';
        } else {
            c.style.display = 'none';
        }
    });
}

function searchCustomCatalog(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.builder-product-card');
    cards.forEach(c => {
        const name = c.getAttribute('data-name').toLowerCase();
        if (name.includes(q)) {
            c.style.display = 'flex';
        } else {
            c.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
