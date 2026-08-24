<?php
require_once 'includes/header.php';

$cart_items = $_SESSION['cart'] ?? [];
$cart_types = $_SESSION['cart_types'] ?? [];
$cart_frequencies = $_SESSION['cart_frequency'] ?? [];

$standard_products = [];
$autoship_products = [];

$std_total_price = 0;
$std_total_discount = 0;

$auto_total_price = 0;
$auto_total_discount = 0;

if (!empty($cart_items)) {
    // Get all product IDs from cart
    $ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($db_products as $prod) {
        $p_id = $prod['id'];
        $qty = $cart_items[$p_id];
        $prod['qty'] = $qty;
        $prod['type'] = $cart_types[$p_id] ?? 'standard';
        $prod['frequency'] = $cart_frequencies[$p_id] ?? '1_month';
        
        $price = $prod['price'];
        
        if ($prod['type'] === 'autoship') {
            // Apply 15% Autoship discount or custom autoship discount if higher
            $auto_pct = !empty($prod['autoship_discount']) ? (int)$prod['autoship_discount'] : 15;
            $auto_unit_price = round($price * (1 - ($auto_pct / 100)));
            $prod['autoship_pct'] = $auto_pct;
            $prod['unit_price'] = $auto_unit_price;
            
            $auto_total_price += $price * $qty;
            $auto_total_discount += ($price - $auto_unit_price) * $qty;
            $autoship_products[] = $prod;
        } else {
            $discount_price = $prod['discount_price'] ? $prod['discount_price'] : $price;
            $prod['unit_price'] = $discount_price;
            
            $std_total_price += $price * $qty;
            $std_total_discount += ($price - $discount_price) * $qty;
            $standard_products[] = $prod;
        }
    }
}

$std_final_price = $std_total_price - $std_total_discount;
$auto_final_price = $auto_total_price - $auto_total_discount;

// Auto select tab: If only autoship items exist, default to autoship tab
$default_tab = (empty($standard_products) && !empty($autoship_products)) ? 'autoship' : 'standard';
if (isset($_GET['tab']) && in_array($_GET['tab'], ['standard', 'autoship'])) {
    $default_tab = $_GET['tab'];
}
?>

<main class="max-w-container-max mx-auto overflow-hidden py-10 lg:py-16 px-margin-desktop min-h-[70vh]">
    
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 pb-6 border-b border-outline-variant/30">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-2">
                <a href="index.php" class="hover:text-primary transition-colors">خانه</a>
                <span>></span>
                <span class="text-primary font-bold">سبد خرید هوشمند</span>
            </div>
            <h1 class="text-2xl lg:text-4xl font-bold text-primary">سبد خرید و اشتراک‌های دوره‌ای</h1>
        </div>

        <div class="flex items-center gap-3">
            <span class="bg-primary-container/10 text-primary px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">verified_user</span>
                ضمانت اصالت و سلامت اقلام
            </span>
            <span class="bg-secondary-container/15 text-secondary-container px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                ارسال اکسپرس و زنجیره سرد
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(isset($_SESSION['profile_error'])): ?>
        <a href="profile_settings.php" class="block bg-error/10 text-error p-4 rounded-2xl mb-8 font-bold text-sm border border-error/20 flex items-center gap-2 hover:bg-error/20 transition-colors cursor-pointer group">
            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">error</span>
            <?php echo $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?>
            <span class="material-symbols-outlined mr-auto">chevron_left</span>
        </a>
    <?php endif; ?>
    <?php if(isset($_SESSION['profile_success'])): ?>
        <div class="bg-status-active/10 text-status-active p-4 rounded-2xl mb-8 font-bold text-sm border border-status-active/20 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?php echo $_SESSION['profile_success']; unset($_SESSION['profile_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($standard_products) && empty($autoship_products)): ?>
        <!-- Empty Cart State -->
        <div class="text-center py-20 bg-white rounded-3xl border border-outline-variant/30 shadow-sm space-y-4 max-w-2xl mx-auto">
            <div class="w-20 h-20 mx-auto rounded-full bg-surface-container flex items-center justify-center text-primary/40">
                <span class="material-symbols-outlined text-5xl">shopping_cart</span>
            </div>
            <h2 class="text-xl font-bold text-on-surface">سبد خرید شما در حال حاضر خالی است!</h2>
            <p class="text-sm text-on-surface-variant max-w-md mx-auto">می‌توانید انواع داروهای دامپزشکی، مکمل‌های تقویتی و محصولات حیوانات خانگی را از داروخانه و پت‌شاپ آسنا بررسی و انتخاب نمایید.</p>
            <div class="flex items-center justify-center gap-4 pt-4">
                <a href="pharmacy.php" class="bg-primary text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-primary-container transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">medication</span>
                    داروخانه تخصصی
                </a>
                <a href="shop.php" class="bg-surface-container hover:bg-surface-container-high text-primary px-6 py-3 rounded-xl font-bold text-sm transition-all border border-outline-variant/40 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                    فروشگاه و پت‌شاپ
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- ========================================================================= -->
        <!-- DUAL-TAB SEGMENTED CONTROLLER (Slideable Tab Navigation)                   -->
        <!-- ========================================================================= -->
        <div class="mb-8">
            <div class="bg-surface-container-low p-1.5 rounded-2xl inline-flex flex-wrap sm:flex-nowrap gap-1 border border-outline-variant/40 shadow-inner w-full sm:w-auto">
                
                <!-- Tab 1: Standard One-Time Orders -->
                <button type="button" onclick="switchCartTab('standard')" id="tab-btn-standard" 
                        class="flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer <?php echo $default_tab === 'standard' ? 'bg-white text-primary shadow-md' : 'text-on-surface-variant hover:text-primary hover:bg-white/50'; ?>">
                    <span class="material-symbols-outlined text-lg">shopping_bag</span>
                    <span>سفارش‌های عادی یک‌باره</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono <?php echo $default_tab === 'standard' ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-on-surface-variant'; ?>">
                        <?= count($standard_products) ?>
                    </span>
                </button>

                <!-- Tab 2: Autoship Recurring Orders -->
                <button type="button" onclick="switchCartTab('autoship')" id="tab-btn-autoship" 
                        class="flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer relative <?php echo $default_tab === 'autoship' ? 'bg-white text-secondary-container shadow-md' : 'text-on-surface-variant hover:text-secondary-container hover:bg-white/50'; ?>">
                    <span class="material-symbols-outlined text-lg animate-spin" style="animation-duration: 8s;">autorenew</span>
                    <span>تحویل خودکار دوره‌ای (Autoship)</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono <?php echo $default_tab === 'autoship' ? 'bg-secondary-container text-white' : 'bg-secondary-container/20 text-secondary-container'; ?>">
                        <?= count($autoship_products) ?>
                    </span>
                    <span class="hidden md:inline text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">۱۵٪ تخفیف</span>
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: STANDARD ONE-TIME PURCHASES PANEL                                  -->
        <!-- ========================================================================= -->
        <div id="panel-standard" class="<?php echo $default_tab === 'standard' ? 'block' : 'hidden'; ?> transition-all duration-300">
            <?php if (empty($standard_products)): ?>
                <div class="text-center py-16 bg-white rounded-3xl border border-outline-variant/30 p-8 space-y-3">
                    <span class="material-symbols-outlined text-4xl text-primary/30">remove_shopping_cart</span>
                    <p class="font-bold text-on-surface">کالایی در بخش سفارش‌های عادی یک‌باره ندارید.</p>
                    <p class="text-xs text-on-surface-variant">تمام اقلام در بخش «تحویل خودکار Autoship» قرار دارند یا سبد شما خالی است.</p>
                </div>
            <?php else: ?>
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Standard Items List -->
                    <div class="lg:w-2/3 space-y-4">
                        <?php foreach($standard_products as $prod): ?>
                        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-outline-variant/30 flex flex-col sm:flex-row items-center gap-5 relative group hover:border-primary/30 transition-all">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 bg-surface-container-lowest rounded-2xl overflow-hidden shrink-0 border border-outline-variant/30">
                                <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover" alt="<?= htmlspecialchars($prod['name']) ?>">
                            </div>
                            
                            <div class="flex-1 w-full">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <span class="text-[11px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md mb-1 inline-block"><?= htmlspecialchars($prod['category']) ?></span>
                                        <a href="product_details.php?id=<?= $prod['id'] ?>" class="block font-bold text-sm sm:text-base text-on-surface hover:text-primary transition-colors">
                                            <?= htmlspecialchars($prod['name']) ?>
                                        </a>
                                    </div>
                                    <form action="actions/cart_action.php" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="text-error hover:bg-error/10 p-2 rounded-xl transition-colors cursor-pointer" title="حذف از سبد">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- 1-Click Autoship Conversion Bar -->
                                <div class="my-3 p-2.5 rounded-xl bg-orange-50/70 border border-orange-200/60 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 text-xs text-orange-900 font-medium">
                                        <span class="material-symbols-outlined text-secondary-container text-base">savings</span>
                                        <span>تخفیف مداوم ۱۵٪ با ارسال منظم دوره‌ای</span>
                                    </div>
                                    <form action="actions/cart_action.php" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <input type="hidden" name="action" value="toggle_type">
                                        <button type="submit" class="bg-secondary-container hover:bg-[#ea580c] text-white px-3 py-1 rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1 active:scale-95">
                                            <span class="material-symbols-outlined text-sm">autorenew</span>
                                            تبدیل به تحویل خودکار (Autoship)
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="flex justify-between items-center w-full pt-1">
                                    <!-- Quantity Stepper -->
                                    <div class="flex items-center gap-3 bg-surface-container rounded-xl p-1.5 border border-outline-variant/30">
                                        <form action="actions/cart_action.php" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                            <input type="hidden" name="action" value="decrease">
                                            <button type="submit" class="w-7 h-7 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-primary transition-colors cursor-pointer"><span class="material-symbols-outlined text-xs">remove</span></button>
                                        </form>
                                        
                                        <span class="font-bold w-6 text-center font-mono text-sm"><?= $prod['qty'] ?></span>
                                        
                                        <form action="actions/cart_action.php" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                            <input type="hidden" name="action" value="increase">
                                            <button type="submit" class="w-7 h-7 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-primary transition-colors cursor-pointer"><span class="material-symbols-outlined text-xs">add</span></button>
                                        </form>
                                    </div>

                                    <div class="text-right">
                                        <?php if($prod['discount_price']): ?>
                                            <span class="text-[11px] text-on-surface-variant line-through font-mono block"><?= number_format($prod['price'] * $prod['qty']) ?> ت</span>
                                        <?php endif; ?>
                                        <span class="text-base font-bold text-primary font-mono">
                                            <?= number_format($prod['unit_price'] * $prod['qty']) ?> <span class="text-xs font-normal">تومان</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Standard Summary Box -->
                    <div class="lg:w-1/3">
                        <div class="bg-surface-container-low rounded-3xl p-6 lg:p-8 sticky top-28 border border-outline-variant/40 shadow-sm space-y-6">
                            <h3 class="text-base font-bold text-primary border-b border-outline-variant/30 pb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary-container">receipt_long</span>
                                خلاصه فاکتور خرید عادی
                            </h3>
                            
                            <div class="space-y-3 text-xs">
                                <div class="flex justify-between items-center text-on-surface-variant">
                                    <span>قیمت کل کالاها (<?= count($standard_products) ?> قلم)</span>
                                    <span class="font-bold font-mono text-on-surface"><?= number_format($std_total_price) ?> تومان</span>
                                </div>
                                <?php if($std_total_discount > 0): ?>
                                <div class="flex justify-between items-center text-secondary-container font-bold">
                                    <span>سود شما از تخفیف‌ها</span>
                                    <span class="font-mono"><?= number_format($std_total_discount) ?> تومان</span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center text-on-surface-variant">
                                    <span>هزینه بسته‌بندی و ارسال</span>
                                    <span class="text-status-active font-bold">رایگان</span>
                                </div>
                            </div>
                            
                            <div class="border-t border-outline-variant/30 pt-4 flex justify-between items-center">
                                <span class="text-xs font-bold">مبلغ قابل پرداخت</span>
                                <span class="text-lg font-bold text-primary font-mono">
                                    <span class="text-xl text-emerald-700"><?= number_format($std_final_price) ?></span> تومان
                                </span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="payment.php" class="w-full bg-primary text-white py-4 rounded-2xl font-bold flex justify-center items-center gap-2 hover:bg-primary-container shadow-lg transition-all text-xs active:scale-95">
                                    <span>تکمیل خرید و پرداخت عادی</span>
                                    <span class="material-symbols-outlined text-sm">arrow_left_alt</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="w-full bg-secondary-container text-white py-4 rounded-2xl font-bold flex justify-center items-center gap-2 hover:bg-[#ea580c] shadow-lg transition-all text-xs active:scale-95">
                                    <span>برای پرداخت وارد شوید</span>
                                    <span class="material-symbols-outlined text-sm">person</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: AUTOSHIP RECURRING PURCHASES PANEL                                 -->
        <!-- ========================================================================= -->
        <div id="panel-autoship" class="<?php echo $default_tab === 'autoship' ? 'block' : 'hidden'; ?> transition-all duration-300">
            <?php if (empty($autoship_products)): ?>
                <div class="text-center py-16 bg-white rounded-3xl border border-outline-variant/30 p-8 space-y-4 max-w-xl mx-auto">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-3xl">autorenew</span>
                    </div>
                    <h3 class="font-bold text-primary text-base">هنوز کالایی را برای تحویل خودکار دوره‌ای تنظیم نکرده‌اید</h3>
                    <p class="text-xs text-on-surface-variant leading-relaxed">
                        با سیستم Autoship آسنا، داروها و غذای پت شما با <strong>۱۵٪ تخفیف دائمی</strong> و <strong>ارسال رایگان خودکار</strong> در موعدهای مقرر به دستتان می‌رسد.
                    </p>
                    <button type="button" onclick="switchCartTab('standard')" class="bg-secondary-container text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md hover:bg-[#ea580c] transition-all cursor-pointer">
                        مشاهده اقلام سبد و فعال‌سازی Autoship
                    </button>
                </div>
            <?php else: ?>
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Autoship Items List -->
                    <div class="lg:w-2/3 space-y-4">
                        
                        <!-- Autoship Benefit Banner -->
                        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white p-5 rounded-3xl shadow-lg flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                    <span class="material-symbols-outlined text-2xl">verified</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">اشتراک هوشمند تحویل خودکار (Autoship) فعال است</h4>
                                    <p class="text-[11px] text-white/90">تخفیف دائمی ۱۵٪ + بدون نیاز به سفارش مجدد ماهانه</p>
                                </div>
                            </div>
                            <span class="text-xs bg-white text-orange-900 font-bold px-3 py-1 rounded-full shrink-0 shadow-sm">ارسال منظم رایگان</span>
                        </div>

                        <?php foreach($autoship_products as $prod): ?>
                        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border-2 border-secondary-container/30 flex flex-col sm:flex-row items-center gap-5 relative group hover:border-secondary-container transition-all">
                            
                            <div class="w-24 h-24 sm:w-28 sm:h-28 bg-surface-container-lowest rounded-2xl overflow-hidden shrink-0 border border-outline-variant/30 relative">
                                <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover" alt="<?= htmlspecialchars($prod['name']) ?>">
                                <span class="absolute bottom-1 right-1 bg-secondary-container text-white text-[9px] font-bold px-1.5 py-0.2 rounded-md">Autoship</span>
                            </div>
                            
                            <div class="flex-1 w-full">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="text-[10px] font-bold text-white bg-secondary-container px-2 py-0.5 rounded-md">🔄 اشتراک دوره‌ای</span>
                                            <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md"><?= $prod['autoship_pct'] ?>٪ تخفیف دائمی</span>
                                        </div>
                                        <a href="product_details.php?id=<?= $prod['id'] ?>" class="block font-bold text-sm sm:text-base text-on-surface hover:text-secondary-container transition-colors">
                                            <?= htmlspecialchars($prod['name']) ?>
                                        </a>
                                    </div>
                                    <form action="actions/cart_action.php" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="text-error hover:bg-error/10 p-2 rounded-xl transition-colors cursor-pointer" title="حذف از اشتراک">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Frequency Selection Toolbar -->
                                <div class="my-3 p-3 rounded-2xl bg-surface-container-low border border-outline-variant/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-secondary-container text-sm">schedule</span>
                                        <span class="text-xs font-bold text-on-surface">دوره تکرار ارسال:</span>
                                    </div>
                                    
                                    <form action="actions/cart_action.php" method="POST" class="m-0 flex items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <input type="hidden" name="action" value="set_frequency">
                                        <select name="frequency" onchange="this.form.submit()" class="bg-white border border-outline-variant rounded-xl px-3 py-1.5 text-xs font-bold text-primary outline-none focus:border-secondary-container cursor-pointer shadow-sm">
                                            <option value="2_weeks" <?= $prod['frequency'] === '2_weeks' ? 'selected' : '' ?>>هر ۲ هفته یک‌بار</option>
                                            <option value="1_month" <?= $prod['frequency'] === '1_month' ? 'selected' : '' ?>>هر ۱ ماه (پیش‌فرض)</option>
                                            <option value="2_months" <?= $prod['frequency'] === '2_months' ? 'selected' : '' ?>>هر ۲ ماه یک‌بار</option>
                                            <option value="3_months" <?= $prod['frequency'] === '3_months' ? 'selected' : '' ?>>هر ۳ ماه یک‌بار</option>
                                        </select>
                                    </form>
                                </div>
                                
                                <div class="flex flex-wrap justify-between items-center w-full pt-1 gap-2">
                                    <!-- Quantity Stepper -->
                                    <div class="flex items-center gap-3 bg-surface-container rounded-xl p-1.5 border border-outline-variant/30">
                                        <form action="actions/cart_action.php" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                            <input type="hidden" name="action" value="decrease">
                                            <button type="submit" class="w-7 h-7 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-secondary-container transition-colors cursor-pointer"><span class="material-symbols-outlined text-xs">remove</span></button>
                                        </form>
                                        
                                        <span class="font-bold w-6 text-center font-mono text-sm"><?= $prod['qty'] ?></span>
                                        
                                        <form action="actions/cart_action.php" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                            <input type="hidden" name="action" value="increase">
                                            <button type="submit" class="w-7 h-7 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-secondary-container transition-colors cursor-pointer"><span class="material-symbols-outlined text-xs">add</span></button>
                                        </form>
                                    </div>

                                    <!-- Switch Back to One-Time Purchase Button -->
                                    <form action="actions/cart_action.php" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <input type="hidden" name="action" value="toggle_type">
                                        <button type="submit" class="text-on-surface-variant hover:text-primary text-[11px] font-bold underline transition-colors cursor-pointer">
                                            تبدیل به خرید عادی ۱ باره
                                        </button>
                                    </form>

                                    <div class="text-right">
                                        <span class="text-[11px] text-on-surface-variant line-through font-mono block"><?= number_format($prod['price'] * $prod['qty']) ?> ت</span>
                                        <span class="text-base font-bold text-secondary-container font-mono">
                                            <?= number_format($prod['unit_price'] * $prod['qty']) ?> <span class="text-xs font-normal">تومان</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Autoship Summary Box -->
                    <div class="lg:w-1/3">
                        <div class="bg-gradient-to-b from-orange-50/50 to-amber-50/30 rounded-3xl p-6 lg:p-8 sticky top-28 border-2 border-secondary-container/30 shadow-sm space-y-6">
                            <h3 class="text-base font-bold text-secondary-container border-b border-secondary-container/20 pb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary-container">autorenew</span>
                                صورت‌حساب اشتراک خودکار
                            </h3>
                            
                            <div class="space-y-3 text-xs">
                                <div class="flex justify-between items-center text-on-surface-variant">
                                    <span>قیمت بدون اشتراک (<?= count($autoship_products) ?> قلم)</span>
                                    <span class="font-bold font-mono text-on-surface line-through"><?= number_format($auto_total_price) ?> تومان</span>
                                </div>
                                <div class="flex justify-between items-center text-emerald-800 bg-emerald-100 p-2 rounded-xl font-bold">
                                    <span>تخفیف اشتراک خودکار (۱۵٪)</span>
                                    <span class="font-mono">-<?= number_format($auto_total_discount) ?> تومان</span>
                                </div>
                                <div class="flex justify-between items-center text-on-surface-variant">
                                    <span>هزینه بسته‌بندی و ارسال دوره‌ای</span>
                                    <span class="text-status-active font-bold">رایگان (طرح اشتراک)</span>
                                </div>
                                <div class="flex justify-between items-center text-on-surface-variant">
                                    <span>امتیاز وفاداری اهدایی</span>
                                    <span class="text-amber-600 font-bold font-mono">+۵۰ امتیاز طلایی</span>
                                </div>
                            </div>
                            
                            <div class="border-t border-secondary-container/20 pt-4 flex justify-between items-center">
                                <span class="text-xs font-bold text-on-surface">مبلغ هر نوبت ارسال</span>
                                <span class="text-lg font-bold text-secondary-container font-mono">
                                    <span class="text-2xl text-secondary-container font-bold"><?= number_format($auto_final_price) ?></span> تومان
                                </span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="payment.php?type=autoship" class="w-full bg-secondary-container hover:bg-[#ea580c] text-white py-4 rounded-2xl font-bold flex justify-center items-center gap-2 shadow-lg transition-all text-xs active:scale-95">
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                    <span>تایید و شروع اشتراک خودکار</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="w-full bg-secondary-container text-white py-4 rounded-2xl font-bold flex justify-center items-center gap-2 hover:bg-[#ea580c] shadow-lg transition-all text-xs active:scale-95">
                                    <span>برای فعال‌سازی اشتراک وارد شوید</span>
                                    <span class="material-symbols-outlined text-sm">person</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<script>
function switchCartTab(tab) {
    const btnStd = document.getElementById('tab-btn-standard');
    const btnAuto = document.getElementById('tab-btn-autoship');
    const panelStd = document.getElementById('panel-standard');
    const panelAuto = document.getElementById('panel-autoship');
    
    if (tab === 'autoship') {
        panelStd.classList.add('hidden');
        panelAuto.classList.remove('hidden');
        
        btnStd.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer text-on-surface-variant hover:text-primary hover:bg-white/50';
        btnAuto.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer relative bg-white text-secondary-container shadow-md';
    } else {
        panelAuto.classList.add('hidden');
        panelStd.classList.remove('hidden');
        
        btnAuto.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer relative text-on-surface-variant hover:text-secondary-container hover:bg-white/50';
        btnStd.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl font-bold text-sm transition-all cursor-pointer bg-white text-primary shadow-md';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
