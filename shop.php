<?php
require_once 'includes/header.php';

// Pagination variables
$limit = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter variables
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$animal = isset($_GET['animal']) ? trim($_GET['animal']) : '';
$pharmacy_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$autoship_only = isset($_GET['autoship']) && ($_GET['autoship'] == '1' || $_GET['autoship'] == 'true');
$min_rating = isset($_GET['min_rating']) && is_numeric($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_brands = isset($_GET['brands']) && is_array($_GET['brands']) ? $_GET['brands'] : [];
$price_ranges = isset($_GET['price_ranges']) && is_array($_GET['price_ranges']) ? $_GET['price_ranges'] : [];
$in_stock = isset($_GET['in_stock']) ? $_GET['in_stock'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevant';

// Check if new columns exist in database
$has_animal_col = false;
$has_tag_col = false;
$has_autoship_col = false;
$has_rating_col = false;

try {
    $col_check = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $col_check->fetchAll(PDO::FETCH_COLUMN);
    $has_animal_col = in_array('target_animal', $columns);
    $has_tag_col = in_array('pharmacy_tag', $columns);
    $has_autoship_col = in_array('is_autoship', $columns);
    $has_rating_col = in_array('rating_cache', $columns);
} catch (Exception $e) {
    // Silently continue if check fails
}

// Build query - Exclude pharmacy products from the general pet shop
$where = [
    "(category NOT LIKE '%دارو%' AND category != 'داروخانه تخصصی' AND (pharmacy_tag IS NULL OR pharmacy_tag = '') AND (target_animal NOT IN ('horse', 'cow', 'chick') OR target_animal IS NULL OR target_animal = 'all'))"
];
$params = [];

if ($category) {
    $where[] = "category LIKE ?";
    $params[] = "%$category%";
}

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ? OR brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Animal filtering
if ($animal && $animal !== 'all' && $has_animal_col) {
    $where[] = "(target_animal = ? OR target_animal = 'all')";
    $params[] = $animal;
} elseif ($animal && $animal !== 'all') {
    $animal_fa_map = ['dog' => 'سگ', 'cat' => 'گربه', 'bird' => 'پرنده'];
    $fa_keyword = $animal_fa_map[$animal] ?? $animal;
    $where[] = "(category LIKE ? OR name LIKE ?)";
    $params[] = "%$fa_keyword%";
    $params[] = "%$fa_keyword%";
}

// Pharmacy Tag filtering
if ($pharmacy_tag && $has_tag_col) {
    $where[] = "pharmacy_tag = ?";
    $params[] = $pharmacy_tag;
}

// Autoship filtering
if ($autoship_only && $has_autoship_col) {
    $where[] = "is_autoship = 1";
}

// Rating filtering
if ($min_rating > 0 && $has_rating_col) {
    $where[] = "rating_cache >= ?";
    $params[] = $min_rating;
}

// Brand filtering
if (!empty($selected_brands)) {
    $placeholders = implode(',', array_fill(0, count($selected_brands), '?'));
    $where[] = "brand IN ($placeholders)";
    foreach ($selected_brands as $b) {
        $params[] = $b;
    }
}

// Price filtering
if (!empty($price_ranges)) {
    $price_conditions = [];
    foreach ($price_ranges as $pr) {
        if ($pr === 'under_500k') {
            $price_conditions[] = "price < 500000";
        } elseif ($pr === '500k_to_1500k') {
            $price_conditions[] = "(price >= 500000 AND price <= 1500000)";
        } elseif ($pr === 'over_1500k') {
            $price_conditions[] = "price > 1500000";
        }
    }
    if (!empty($price_conditions)) {
        $where[] = "(" . implode(" OR ", $price_conditions) . ")";
    }
}

// In-stock filtering
if ($in_stock) {
    $where[] = "stock > 0";
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Sorting
$orderBy = "ORDER BY created_at DESC"; // default (newest)
if ($sort === 'price_asc') {
    $orderBy = "ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $orderBy = "ORDER BY price DESC";
} elseif ($sort === 'rating_desc' && $has_rating_col) {
    $orderBy = "ORDER BY rating_cache DESC, review_count_cache DESC";
}

// Get total for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Get products
$stmt = $pdo->prepare("SELECT * FROM products $whereClause $orderBy LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available unique brands for the sidebar
$brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
$all_brands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Suggested Autoship Offers (for top banner)
$autoship_offers = [];
if ($has_autoship_col) {
    $autoStmt = $pdo->query("SELECT * FROM products WHERE is_autoship = 1 ORDER BY (price - IFNULL(discount_price, price)) DESC, rating_cache DESC LIMIT 4");
    $autoship_offers = $autoStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Automatic Best Offers (Highest discounts / highest rated)
$best_offers = [];
try {
    $bestStmt = $pdo->query("SELECT * FROM products WHERE discount_price IS NOT NULL AND discount_price < price ORDER BY (price - discount_price) DESC LIMIT 4");
    $best_offers = $bestStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch Recommended Products (Smart recommendation engine)
$recommended_products = [];
try {
    $recStmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 4");
    $recommended_products = $recStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch user wishlist if logged in
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishlist_stmt->execute([$_SESSION['user_id']]);
    $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Companion Pet Animal definitions (Local self-hosted SVGs for Iran network compatibility)
$animal_list = [
    'all' => ['name' => 'همه حیوانات', 'icon' => 'pets', 'image' => 'assets/images/all-pets-avatar.svg'],
    'dog' => ['name' => 'سگ', 'icon' => 'sound_detection_dog_barking', 'image' => 'assets/images/dog-avatar.svg'],
    'cat' => ['name' => 'گربه', 'icon' => 'cruelty_free', 'image' => 'assets/images/cat-avatar.svg'],
    'bird' => ['name' => 'پرندگان', 'icon' => 'flutter', 'image' => 'assets/images/bird-avatar.svg'],
    'small_pet' => ['name' => 'جونده و آبزیان', 'icon' => 'set_meal', 'image' => 'assets/images/smallpet-avatar.svg']
];

// Helper functions for URL parameters
function buildUrl($updates) {
    $query = $_GET;
    foreach($updates as $key => $val) {
        if ($val === null || $val === '') unset($query[$key]);
        else $query[$key] = $val;
    }
    return '?' . http_build_query($query);
}

function buildUrlRemoveArrayItem($arrayName, $valueToRemove) {
    $query = $_GET;
    if (isset($query[$arrayName]) && is_array($query[$arrayName])) {
        $query[$arrayName] = array_filter($query[$arrayName], function($v) use ($valueToRemove) {
            return $v !== $valueToRemove;
        });
        if (empty($query[$arrayName])) {
            unset($query[$arrayName]);
        }
    }
    unset($query['page']);
    return '?' . http_build_query($query);
}
?>

<!-- Secondary Nav Bar -->
<div class="bg-primary text-white border-t border-white/20 hidden md:block">
   <div class="max-w-container-max mx-auto px-margin-desktop flex items-center gap-8 text-label-lg font-bold">
       <a href="shop.php" class="py-3 hover:text-secondary-container transition-colors <?php echo empty($animal) && empty($category) ? 'text-secondary-container underline underline-offset-8' : ''; ?>">تمام محصولات پت‌شاپ</a>
       <a href="shop.php?category=غذای+سگ" class="py-3 hover:text-secondary-container transition-colors <?php echo $category == 'غذای سگ' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">غذای سگ</a>
       <a href="shop.php?category=غذای+گربه" class="py-3 hover:text-secondary-container transition-colors <?php echo $category == 'غذای گربه' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">غذای گربه</a>
       <a href="shop.php?category=لوازم+بهداشتی" class="py-3 hover:text-secondary-container transition-colors <?php echo $category == 'لوازم بهداشتی' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">بهداشتی و نظافت</a>
       <a href="shop.php?category=اسباب‌بازی" class="py-3 hover:text-secondary-container transition-colors <?php echo $category == 'اسباب‌بازی' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">اسباب‌بازی و سرگرمی</a>
       <a href="pharmacy.php" class="py-2 mr-auto flex items-center gap-1.5 text-secondary-container hover:text-white transition-all bg-white/10 hover:bg-secondary-container px-4 rounded-full text-xs font-bold shadow-sm">
           <span class="material-symbols-outlined text-[16px]">local_pharmacy</span>
           ورود به داروخانه تخصصی دامپزشکی
       </a>
   </div>
</div>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[70vh]">
    
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="text-label-sm text-on-surface-variant">
            <a href="index.php" class="hover:underline">خانه</a> > 
            <a href="shop.php" class="hover:underline">فروشگاه</a> 
            <?php if($animal && isset($animal_list[$animal])): ?> > <span class="text-on-surface"><?php echo $animal_list[$animal]['name']; ?></span><?php endif; ?>
            <?php if($category): ?> > <span class="text-on-surface"><?php echo htmlspecialchars($category); ?></span><?php endif; ?>
        </div>

        <!-- Autoship Mode Quick Badge / Header Toggle -->
        <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-2xl shadow-sm border border-outline-variant/30">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container text-[20px] animate-spin" style="animation-duration: 8s;">autorenew</span>
                <span class="text-label-sm font-bold text-primary">حالت تحویل خودکار (Autoship)</span>
            </div>
            <label class="autoship-switch">
                <input type="checkbox" id="header-autoship-toggle" <?php echo $autoship_only ? 'checked' : ''; ?> onchange="toggleAutoshipParam(this.checked)">
                <span class="autoship-slider"></span>
            </label>
        </div>
    </div>

    <!-- SECTION 1: PET SHOP SHOWCASE HERO BANNER -->
    <section class="bg-gradient-to-r from-primary via-primary-container to-[#001a48] text-white rounded-[2rem] p-6 lg:p-10 mb-10 shadow-xl relative overflow-hidden">
        <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/4 -top-20 w-80 h-80 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-white/10 rounded-full text-xs font-bold">
                    <span class="material-symbols-outlined text-secondary-container text-[16px]">verified</span>
                    فروشگاه جامع لوازم و غذای پت آسنا
                </div>
                <h2 class="text-2xl lg:text-4xl font-bold tracking-tight text-white">
                    بهترین محصولات برای نشاط و تغذیه پت شما
                </h2>
                <p class="text-xs lg:text-sm text-white/80 max-w-2xl leading-relaxed">
                    مجموعه‌ای از برترین برندهای غذای خشک، کنسرو، اسباب‌بازی‌های هوشمند، ملزومات بهداشتی و جای خواب با تضمین اصالت و ارسال سریع.
                </p>
            </div>

            <a href="pharmacy.php" class="bg-secondary-container/20 hover:bg-secondary-container border border-secondary-container/40 text-white p-4 rounded-2xl transition-all shrink-0 flex items-center gap-3 group">
                <span class="w-10 h-10 rounded-xl bg-secondary-container flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-xl">medication</span>
                </span>
                <div class="text-right">
                    <p class="text-xs font-bold text-secondary-container group-hover:text-white transition-colors">بخش داروخانه دامپزشکی</p>
                    <p class="text-[11px] text-white/80">خرید داروها، واکسن‌ها و ارسال نسخه</p>
                </div>
                <span class="material-symbols-outlined text-sm text-secondary-container group-hover:text-white transition-colors">arrow_back</span>
            </a>
        </div>
    </section>

    <!-- SECTION 2: CIRCULAR ANIMAL SELECTOR (Centered, No Background Card) -->
    <section class="mb-12 relative">
        <div class="text-center max-w-xl mx-auto mb-6">
            <h3 class="text-2xl font-bold text-primary flex items-center justify-center gap-2 mb-1">
                <span class="material-symbols-outlined text-secondary-container">pets</span>
                انتخاب گونه حیوان
            </h3>
            <p class="text-xs text-on-surface-variant">برای مشاهده محصولات متناسب با پت خود، گونه مورد نظرتان را انتخاب کنید</p>
        </div>

        <div class="relative flex items-center justify-center max-w-5xl mx-auto px-4">
            <!-- Navigation Arrow Prev -->
            <button type="button" onclick="scrollAnimalCarousel(-1)" class="w-10 h-10 rounded-full border border-outline-variant/40 bg-white flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm active:scale-95 shrink-0 ml-3 z-10" title="قبلی">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>

            <!-- Centered Species Circles Container -->
            <div id="animal-carousel-container" class="flex items-center justify-center gap-6 overflow-x-auto py-3 px-2 no-scrollbar scroll-smooth">
                <?php foreach($animal_list as $key => $species): ?>
                    <?php 
                        $selected = ($animal === $key) || ($key === 'all' && empty($animal)); 
                    ?>
                    <a href="<?php echo buildUrl(['animal' => $key === 'all' ? null : $key, 'page' => 1]); ?>" 
                       class="animal-circle-item flex flex-col items-center gap-3 shrink-0 cursor-pointer group <?php echo $selected ? 'active' : ''; ?>"
                       style="width: 110px;">
                        <div class="animal-circle-ring w-24 h-24 rounded-full p-1 border-2 border-outline-variant/30 bg-surface-container-low transition-all duration-300 flex items-center justify-center relative overflow-hidden group-hover:border-primary shadow-sm">
                            <img loading="lazy" src="<?php echo $species['image']; ?>" class="w-full h-full object-cover rounded-full group-hover:scale-110 transition-transform duration-500" alt="<?php echo $species['name']; ?>">
                            <?php if($selected): ?>
                            <div class="absolute inset-0 bg-primary/20 backdrop-blur-[1px] flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-2xl drop-shadow">check_circle</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="animal-circle-label text-sm text-center font-bold text-on-surface-variant group-hover:text-primary transition-colors">
                            <?php echo $species['name']; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Navigation Arrow Next -->
            <button type="button" onclick="scrollAnimalCarousel(1)" class="w-10 h-10 rounded-full border border-outline-variant/40 bg-white flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm active:scale-95 shrink-0 mr-3 z-10" title="بعدی">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
        </div>

        <!-- Carousel Pagination Dots -->
        <div class="flex justify-center items-center gap-1.5 mt-3">
            <?php foreach($animal_list as $key => $species): ?>
                <?php $is_curr = ($animal === $key) || ($key === 'all' && empty($animal)); ?>
                <span class="h-1.5 rounded-full transition-all duration-300 <?php echo $is_curr ? 'w-6 bg-primary' : 'w-2 bg-outline-variant/40'; ?>"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 3: SUGGESTED AUTOSHIP OFFERS BANNER (Page 6 Top)                  -->
    <!-- ========================================================================= -->
    <?php if(!empty($autoship_offers) && ($autoship_only || empty($category))): ?>
    <section class="mb-12 bg-gradient-to-l from-secondary-container/10 via-amber-50 to-orange-50 border-2 border-secondary-container/30 rounded-3xl p-6 lg:p-8 relative overflow-hidden shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-secondary-container text-white rounded-full text-xs font-bold mb-2">
                    <span class="material-symbols-outlined text-[14px]">local_shipping</span>
                    پیشنهاد ویژه تحویل دوره‌ای هوشمند
                </div>
                <h3 class="text-2xl font-bold text-primary">پیشنهادات برگزیده ارسال خودکار (Suggested Autoship Offers)</h3>
                <p class="text-sm text-on-surface-variant">با فعال‌سازی ارسال دوره‌ای، علاوه بر تضمین عدم اتمام داروی حیوان خانگی، تا ۱۵٪ تخفیف ثابت دریافت کنید.</p>
            </div>
            <a href="subscriptions.php" class="bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-primary-container transition-all self-start md:self-auto shrink-0 shadow-md">
                مشاهده پلن‌های اشتراک
            </a>
        </div>

        <!-- Autoship Suggested Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($autoship_offers as $auto_item): ?>
            <div class="bg-white rounded-2xl p-5 shadow-md border border-outline-variant/20 flex flex-col justify-between relative hover:-translate-y-1 transition-transform">
                <div class="absolute top-4 left-4 bg-status-active text-white text-[11px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                    <span class="material-symbols-outlined text-[12px]">autorenew</span>
                    <?php echo $auto_item['autoship_discount'] ?? 10; ?>٪ تخفیف دائمی
                </div>

                <div class="aspect-square bg-surface-container-lowest rounded-xl overflow-hidden mb-4 relative">
                    <img loading="lazy" src="<?php echo htmlspecialchars($auto_item['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($auto_item['name']); ?>">
                </div>

                <div>
                    <span class="text-[11px] text-on-surface-variant font-bold"><?php echo htmlspecialchars($auto_item['brand'] ?? 'آسنا'); ?></span>
                    <h4 class="text-sm font-bold text-primary line-clamp-2 mb-2"><?php echo htmlspecialchars($auto_item['name']); ?></h4>
                    
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-outline-variant/20">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-on-surface-variant line-through"><?php echo number_format($auto_item['price']); ?> ت</span>
                            <?php 
                                $disc = $auto_item['autoship_discount'] ?? 10;
                                $auto_price = $auto_item['discount_price'] ? ($auto_item['discount_price'] * (100 - $disc) / 100) : ($auto_item['price'] * (100 - $disc) / 100);
                            ?>
                            <span class="text-sm font-bold text-secondary-container"><?php echo number_format($auto_price); ?> تومان</span>
                        </div>
                        <button type="button" onclick="addToCart(this, <?php echo $auto_item['id']; ?>, 'autoship')" class="bg-secondary-container text-white p-2 rounded-xl hover:bg-[#ea580c] transition-colors cursor-pointer" title="افزودن با اشتراک دوره‌ای (Autoship)">
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- MAIN STORE SECTION: FILTERS SIDEBAR + PRODUCT GRID                       -->
    <!-- ========================================================================= -->
    <!-- Main Filter Form (Page 6 Middle) -->
    <form id="shop-filter-form" action="shop.php" method="GET" class="flex flex-col md:flex-row gap-8">
        
        <!-- Hidden inputs to preserve states -->
        <?php if($search): ?>
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
        <?php endif; ?>
        <?php if($category): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        <?php endif; ?>
        <?php if($animal): ?>
            <input type="hidden" name="animal" value="<?php echo htmlspecialchars($animal); ?>">
        <?php endif; ?>
        <?php if($pharmacy_tag): ?>
            <input type="hidden" name="tag" value="<?php echo htmlspecialchars($pharmacy_tag); ?>">
        <?php endif; ?>

        <!-- Mobile Filter Toggle Button -->
        <button type="button" onclick="toggleFilters()" class="md:hidden w-full flex items-center justify-center gap-2 bg-surface-container-low border border-outline-variant rounded-xl py-3 font-bold text-primary mb-6 active:scale-95 transition-transform">
            <span class="material-symbols-outlined">tune</span>
            فیلترها و مرتب‌سازی پیشرفته
        </button>

        <!-- Mobile Backdrop -->
        <div id="filter-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleFilters()"></div>

        <!-- Sidebar Facets (Page 6 Middle Controls) -->
        <aside id="filter-sidebar" class="fixed inset-y-0 right-0 w-80 md:w-1/4 lg:w-1/5 bg-surface-container-lowest md:bg-transparent z-[70] md:z-0 md:relative flex-shrink-0 flex flex-col shadow-2xl md:shadow-none transition-transform duration-300 translate-x-full md:translate-x-0 overflow-y-auto md:overflow-visible">
           <div class="p-6 md:p-0">
               <div class="flex items-center justify-between mb-6 md:hidden">
                   <h2 class="text-title-lg font-bold text-primary">فیلترهای جستجو</h2>
                   <button type="button" onclick="toggleFilters()" class="text-on-surface-variant hover:text-error transition-colors">
                       <span class="material-symbols-outlined">close</span>
                   </button>
               </div>
               
               <div class="flex items-center justify-between mb-4">
                   <h2 class="text-title-lg font-bold hidden md:block">فیلترها</h2>
                   <?php if($category || $search || $animal || $pharmacy_tag || $autoship_only || $min_rating > 0 || !empty($selected_brands) || !empty($price_ranges)): ?>
                   <a href="shop.php" class="text-xs text-error font-bold hover:underline">حذف همه فیلترها</a>
                   <?php endif; ?>
               </div>
           
               <!-- 1. Autoship Toggle Filter Switch (Page 6) -->
               <div class="bg-primary/5 rounded-2xl p-4 mb-4 border border-primary/10">
                   <div class="flex items-center justify-between">
                       <div class="flex items-center gap-2">
                           <span class="material-symbols-outlined text-secondary-container text-[20px]">autorenew</span>
                           <div>
                               <span class="text-sm font-bold text-primary block">فقط کالاهای Autoship</span>
                               <span class="text-[11px] text-on-surface-variant">دارای قابلیت ارسال دوره‌ای</span>
                           </div>
                       </div>
                       <label class="autoship-switch">
                           <input type="checkbox" name="autoship" value="1" <?php echo $autoship_only ? 'checked' : ''; ?> onchange="this.form.submit()">
                           <span class="autoship-slider"></span>
                       </label>
                   </div>
               </div>

               <!-- 2. Categories Filter Accordion -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>دسته‌بندی‌های پت‌شاپ</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   <ul class="space-y-2.5 text-body-md text-on-surface-variant pr-2 transition-all duration-300">
                       <li>
                            <a href="<?php echo buildUrl(['category' => null, 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $category == '' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>همه محصولات پت‌شاپ</span>
                                <?php if($category == ''): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['category' => 'غذای سگ', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $category == 'غذای سگ' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>غذای سگ</span>
                                <?php if($category == 'غذای سگ'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['category' => 'غذای گربه', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $category == 'غذای گربه' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>غذای گربه</span>
                                <?php if($category == 'غذای گربه'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['category' => 'لوازم بهداشتی', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $category == 'لوازم بهداشتی' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>لوازم بهداشتی و درمانی</span>
                            </a>
                       </li>
                   </ul>
               </div>

               <!-- 3. Customer Rating Filter (Page 6) -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>امتیاز مشتریان</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   <div class="space-y-2 text-body-md text-on-surface-variant pr-2">
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="radio" name="min_rating" value="0" <?php echo $min_rating == 0 ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-primary focus:ring-primary w-4 h-4">
                           <span>همه امتیازها</span>
                       </label>
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="radio" name="min_rating" value="5" <?php echo $min_rating == 5 ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-primary focus:ring-primary w-4 h-4">
                           <div class="flex items-center text-status-warning">
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                           </div>
                           <span class="text-xs text-on-surface-variant">(۵ ستاره)</span>
                       </label>
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="radio" name="min_rating" value="4" <?php echo $min_rating == 4 ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-primary focus:ring-primary w-4 h-4">
                           <div class="flex items-center text-status-warning">
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-empty">star</span>
                           </div>
                           <span class="text-xs text-on-surface-variant">۴ ستاره و بالاتر</span>
                       </label>
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="radio" name="min_rating" value="3" <?php echo $min_rating == 3 ? 'checked' : ''; ?> onchange="this.form.submit()" class="text-primary focus:ring-primary w-4 h-4">
                           <div class="flex items-center text-status-warning">
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-filled">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-empty">star</span>
                               <span class="material-symbols-outlined text-[16px] star-rating-empty">star</span>
                           </div>
                           <span class="text-xs text-on-surface-variant">۳ ستاره و بالاتر</span>
                       </label>
                   </div>
               </div>

               <!-- 4. Dynamic Brand Filter with Instant Brand Search Bar (Page 6) -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>برندها</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   
                   <!-- Brand Search Input (Page 6: "brand (search bar)") -->
                   <div class="mb-3 relative">
                       <input type="text" id="brand-search-input" onkeyup="filterBrandList(this.value)" placeholder="جستجوی برند..." class="w-full text-xs py-2 px-3 pl-8 rounded-xl border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-surface-container-low">
                       <span class="material-symbols-outlined text-[16px] text-on-surface-variant absolute left-2.5 top-2.5">search</span>
                   </div>

                   <div id="brands-checkbox-list" class="space-y-2.5 text-body-md text-on-surface-variant pr-2 max-h-52 overflow-y-auto no-scrollbar">
                       <?php foreach($all_brands as $b): ?>
                           <label class="brand-item-label flex items-center gap-3 cursor-pointer hover:text-primary">
                               <input type="checkbox" name="brands[]" value="<?php echo htmlspecialchars($b); ?>" <?php echo in_array($b, $selected_brands) ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                               <span class="brand-item-name text-sm"><?php echo htmlspecialchars($b); ?></span>
                           </label>
                       <?php endforeach; ?>
                   </div>
               </div>

               <!-- 5. Price Filter (Page 6) -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>محدوده قیمت</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   <div class="space-y-3 text-body-md text-on-surface-variant pr-2">
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="checkbox" name="price_ranges[]" value="under_500k" <?php echo in_array('under_500k', $price_ranges) ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                           <span class="text-xs">زیر ۵۰۰,۰۰۰ تومان</span>
                       </label>
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="checkbox" name="price_ranges[]" value="500k_to_1500k" <?php echo in_array('500k_to_1500k', $price_ranges) ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                           <span class="text-xs">۵۰۰,۰۰۰ تا ۱,۵۰۰,۰۰۰ تومان</span>
                       </label>
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="checkbox" name="price_ranges[]" value="over_1500k" <?php echo in_array('over_1500k', $price_ranges) ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                           <span class="text-xs">بالای ۱,۵۰۰,۰۰۰ تومان</span>
                       </label>
                   </div>
               </div>

               <!-- 6. Availability Filter -->
               <div class="border-t border-outline-variant/30 py-4">
                   <div class="space-y-3 text-body-md text-on-surface-variant pr-2">
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="checkbox" name="in_stock" value="1" <?php echo $in_stock ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                           <span class="text-sm font-bold text-primary">فقط کالاهای موجود در انبار</span>
                       </label>
                   </div>
               </div>

            </div>
        </aside>

        <!-- Product Grid Area -->
        <section class="w-full md:w-3/4 lg:w-4/5">
            <!-- Header/Sorting -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b border-outline-variant/20">
               <div>
                   <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                       <?php if($search): ?>
                           نتایج جستجو برای "<?php echo htmlspecialchars($search); ?>"
                       <?php elseif($pharmacy_tag && isset($pharmacy_tags_list[$pharmacy_tag])): ?>
                           <?php echo $pharmacy_tags_list[$pharmacy_tag]['title']; ?>
                       <?php elseif($animal && isset($animal_list[$animal])): ?>
                           محصولات مخصوص <?php echo $animal_list[$animal]['name']; ?>
                       <?php else: ?>
                           <?php echo $category ? htmlspecialchars($category) : 'تمام محصولات و داروها'; ?>
                       <?php endif; ?>
                       <span class="text-sm text-on-surface-variant font-normal mr-2">(<?php echo $total; ?> کالا)</span>
                   </h1>
                   <?php if($autoship_only): ?>
                   <p class="text-xs text-secondary-container font-bold mt-1">نمایش کالاهای دارای تخفیف اشتراک دوره‌ای Autoship</p>
                   <?php endif; ?>
               </div>
               
               <div class="mt-4 sm:mt-0 flex items-center gap-2 text-sm">
                   <span class="text-on-surface-variant">مرتب‌سازی:</span>
                   <select name="sort" onchange="this.form.submit()" class="border border-outline-variant rounded-xl py-2 px-3 bg-white text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none cursor-pointer text-sm shadow-sm font-medium">
                       <option value="relevant" <?php echo $sort == 'relevant' ? 'selected' : ''; ?>>مرتبط‌ترین / جدیدترین‌ها</option>
                       <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>محبوب‌ترین (بالاترین امتیاز)</option>
                       <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>قیمت: ارزان به گران</option>
                       <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>قیمت: گران به ارزان</option>
                   </select>
               </div>
            </div>

            <!-- Active Filters Banner -->
            <?php if($category || $search || $animal || $pharmacy_tag || $autoship_only || $min_rating > 0 || !empty($selected_brands) || !empty($price_ranges)): ?>
            <div class="flex flex-wrap items-center gap-2 mb-6 bg-surface-container-low p-3 rounded-2xl">
                <span class="text-xs text-on-surface-variant font-bold ml-1">فیلترهای فعال:</span>

                <?php if($animal && isset($animal_list[$animal])): ?>
                <span class="bg-white border border-primary/20 px-3 py-1 rounded-full text-xs font-bold text-primary flex items-center gap-1 shadow-sm">
                    گونه: <?php echo $animal_list[$animal]['name']; ?>
                    <a href="<?php echo buildUrl(['animal' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>

                <?php if($pharmacy_tag && isset($pharmacy_tags_list[$pharmacy_tag])): ?>
                <span class="bg-white border border-secondary-container/30 px-3 py-1 rounded-full text-xs font-bold text-secondary-container flex items-center gap-1 shadow-sm">
                    دسته‌بندی: <?php echo $pharmacy_tags_list[$pharmacy_tag]['title']; ?>
                    <a href="<?php echo buildUrl(['tag' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>

                <?php if($autoship_only): ?>
                <span class="bg-white border border-secondary-container/40 px-3 py-1 rounded-full text-xs font-bold text-secondary-container flex items-center gap-1 shadow-sm">
                    ارسال خودکار (Autoship)
                    <a href="<?php echo buildUrl(['autoship' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>

                <?php if($min_rating > 0): ?>
                <span class="bg-white border border-outline-variant/50 px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-sm">
                    حداقل <?php echo $min_rating; ?> ستاره
                    <a href="<?php echo buildUrl(['min_rating' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>

                <?php if($category): ?>
                <span class="bg-white border border-outline-variant/50 px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-sm">
                    <?php echo htmlspecialchars($category); ?>
                    <a href="<?php echo buildUrl(['category' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>
                
                <?php if($search): ?>
                <span class="bg-white border border-outline-variant/50 px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-sm">
                    "<?php echo htmlspecialchars($search); ?>"
                    <a href="<?php echo buildUrl(['q' => null, 'page' => 1]); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endif; ?>

                <?php foreach($selected_brands as $b): ?>
                <span class="bg-white border border-outline-variant/50 px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-sm">
                    برند: <?php echo htmlspecialchars($b); ?>
                    <a href="<?php echo buildUrlRemoveArrayItem('brands', $b); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endforeach; ?>

                <?php foreach($price_ranges as $pr): ?>
                <span class="bg-white border border-outline-variant/50 px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-sm">
                    <?php 
                        if ($pr === 'under_500k') echo 'زیر ۵۰۰,۰۰۰ تومان';
                        elseif ($pr === '500k_to_1500k') echo '۵۰۰,۰۰۰ تا ۱,۵۰۰,۰۰۰ تومان';
                        elseif ($pr === 'over_1500k') echo 'بالای ۱,۵۰۰,۰۰۰ تومان';
                    ?>
                    <a href="<?php echo buildUrlRemoveArrayItem('price_ranges', $pr); ?>" class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer">close</a>
                </span>
                <?php endforeach; ?>

                <a href="shop.php" class="text-error text-xs hover:underline mr-auto font-bold">پاک کردن همه</a>
            </div>
            <?php endif; ?>

            <!-- ========================================================================= -->
            <!-- PRODUCT GRID (Standard ASENA Animated Card from PROJECT_GUIDELINES.md)    -->
            <!-- ========================================================================= -->
            <?php if(count($products) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($products as $product): ?>
                <div class="bg-white rounded-3xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
                    
                    <!-- Badges Top Left -->
                    <div class="absolute top-6 left-6 flex flex-col gap-1.5 z-10">
                        <?php if($product['discount_price']): ?>
                        <div class="bg-secondary-container text-on-secondary-container text-label-sm px-3 py-1 rounded-full font-bold shadow-sm">تخفیف ویژه</div>
                        <?php endif; ?>
                        
                        <?php if(!empty($product['is_autoship'])): ?>
                        <div class="bg-primary-container text-white text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">autorenew</span>
                            Autoship
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Wishlist Button Top Right -->
                    <?php $in_wishlist = in_array($product['id'], $user_wishlist); ?>
                    <button type="button" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)" class="absolute top-6 right-6 z-10 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-on-surface hover:text-error transition-colors shadow-sm">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo $in_wishlist ? '1' : '0'; ?>; color: <?php echo $in_wishlist ? '#dc2626' : 'inherit'; ?>;">favorite</span>
                    </button>

                    <!-- Product Image & Overlay -->
                    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 sm:mb-6 overflow-hidden relative">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="block w-full h-full">
                            <img loading="lazy" src="<?php echo htmlspecialchars($product['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        
                        <!-- Desktop Only Animated Add to Cart Overlay -->
                        <div class="hidden lg:flex absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/70 to-transparent justify-center z-20">
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" class="bg-primary text-white w-full py-2.5 rounded-xl font-bold flex justify-center items-center gap-2 hover:bg-primary-container shadow-lg transition-colors text-xs cursor-pointer">
                                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                                افزودن به سبد خرید
                            </button>
                        </div>
                    </div>

                    <!-- Product Info & Rating -->
                    <div class="flex-1 flex flex-col">
                        <div class="flex items-center justify-between text-xs text-on-surface-variant mb-1">
                            <span class="truncate">
                                <?php echo htmlspecialchars($product['category']); ?>
                                <?php if(!empty($product['brand'])) echo ' • <span class="text-primary font-bold">' . htmlspecialchars($product['brand']) . '</span>'; ?>
                            </span>
                            
                            <!-- Star Rating (Page 6: Rating System) -->
                            <?php $rating = $product['rating_cache'] ?? 4.8; ?>
                            <div class="flex items-center gap-0.5 text-status-warning font-bold text-xs shrink-0">
                                <span class="material-symbols-outlined text-[14px] text-amber-500">star</span>
                                <span><?php echo $rating; ?></span>
                            </div>
                        </div>

                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="block mb-2">
                            <h3 class="text-sm sm:text-base font-bold text-on-surface line-clamp-2 hover:text-primary transition-colors cursor-pointer leading-snug">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                        </a>

                        <?php if(!empty($product['is_autoship'])): ?>
                        <div class="mb-2">
                            <span class="text-[10px] text-secondary-container font-bold bg-secondary-container/10 px-2 py-0.5 rounded-md inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">autorenew</span>
                                Autoship: <?php echo $product['autoship_discount'] ?? 10; ?>٪ تخفیف
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Card Footer with Price & Permanent Touch Button -->
                        <div class="mt-auto flex items-center justify-between pt-3 border-t border-outline-variant/20 gap-2">
                            <div class="flex flex-col">
                                <?php if($product['discount_price']): ?>
                                <span class="text-[10px] sm:text-xs text-on-surface-variant line-through font-mono"><?php echo number_format($product['price']); ?> ت</span>
                                <span class="text-sm sm:text-base font-bold text-primary font-mono"><?php echo number_format($product['discount_price']); ?> <span class="text-[10px] font-normal">تومان</span></span>
                                <?php else: ?>
                                <span class="text-sm sm:text-base font-bold text-primary font-mono"><?php echo number_format($product['price']); ?> <span class="text-[10px] font-normal">تومان</span></span>
                                <?php endif; ?>
                            </div>

                            <!-- Dedicated Touch-Friendly Button for Android & iOS (Mobile Only, lg:hidden) -->
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" 
                                    class="lg:hidden bg-primary hover:bg-primary-container text-white px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition-all shrink-0 cursor-pointer"
                                    title="افزودن به سبد خرید">
                                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                                <span class="text-xs">خرید</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-20 text-on-surface-variant bg-surface-container-lowest border border-outline-variant/30 rounded-3xl p-8">
                    <span class="material-symbols-outlined text-6xl mb-4 text-primary opacity-60">search_off</span>
                    <p class="text-xl font-bold text-primary mb-2">محصولی با این مشخصات یافت نشد!</p>
                    <p class="text-sm text-on-surface-variant mb-6">فیلترهای انتخابی را تغییر داده یا همه محصولات را بررسی کنید.</p>
                    <a href="shop.php" class="bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-bold inline-block hover:bg-primary-container transition-all">پاک کردن فیلترها و نمایش همه</a>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="flex justify-center items-center gap-2 mt-12 mb-8" dir="ltr">
                <?php if($page > 1): ?>
                    <a href="<?php echo buildUrl(['page' => $page - 1]); ?>" class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined">chevron_left</span></a>
                <?php endif; ?>

                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="w-10 h-10 rounded-full bg-primary text-white font-bold flex items-center justify-center"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo buildUrl(['page' => $i]); ?>" class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center hover:border-primary hover:text-primary transition-colors"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($page < $totalPages): ?>
                    <a href="<?php echo buildUrl(['page' => $page + 1]); ?>" class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined">chevron_right</span></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </section>
    </form>

    <!-- ========================================================================= -->
    <!-- SECTION 4: AUTOMATIC BEST OFFERS (Page 6 Bottom)                          -->
    <!-- ========================================================================= -->
    <?php if(!empty($best_offers)): ?>
    <section class="mt-20 pt-10 border-t border-outline-variant/30">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-secondary-container text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-2xl animate-pulse">local_fire_department</span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-primary">پیشنهادات شگفت‌انگیز خودکار (Automatic Best Offers)</h3>
                    <p class="text-sm text-on-surface-variant">بیشترین تخفیف‌های ویژه بر روی داروهای پرمصرف و مکمل‌های برتر</p>
                </div>
            </div>
            <a href="shop.php?sort=price_asc" class="text-sm font-bold text-primary hover:text-secondary-container transition-colors hidden sm:block">مشاهده همه تخفیف‌ها ></a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($best_offers as $bo_item): ?>
            <div class="hot-offer-card bg-white rounded-3xl p-5 shadow-lg border border-secondary-container/30 flex flex-col justify-between relative group hover:-translate-y-1 transition-all">
                <div class="absolute top-4 left-4 bg-error text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full shadow-sm z-10">
                    🔥 تخفیف ویژه
                </div>
                
                <div class="aspect-square bg-surface-container-lowest rounded-2xl overflow-hidden mb-4 relative">
                    <img loading="lazy" src="<?php echo htmlspecialchars($bo_item['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="<?php echo htmlspecialchars($bo_item['name']); ?>">
                </div>

                <div>
                    <span class="text-[11px] text-on-surface-variant"><?php echo htmlspecialchars($bo_item['category']); ?></span>
                    <a href="product_details.php?id=<?php echo $bo_item['id']; ?>">
                        <h4 class="text-sm font-bold text-on-surface line-clamp-2 hover:text-primary transition-colors mb-2"><?php echo htmlspecialchars($bo_item['name']); ?></h4>
                    </a>

                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-outline-variant/20">
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant line-through"><?php echo number_format($bo_item['price']); ?> تومان</span>
                            <span class="text-sm font-bold text-primary"><?php echo number_format($bo_item['discount_price']); ?> تومان</span>
                        </div>
                        <button type="button" onclick="addToCart(this, <?php echo $bo_item['id']; ?>)" class="bg-primary text-white p-2.5 rounded-xl hover:bg-primary-container transition-colors shadow-sm" title="خرید سریع">
                            <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- SECTION 5: SMART RECOMMENDATION SYSTEM (Page 6 Bottom)                   -->
    <!-- ========================================================================= -->
    <?php if(!empty($recommended_products)): ?>
    <section class="mt-16 bg-surface-container-low rounded-3xl p-8 border border-outline-variant/20">
        <div class="flex items-center gap-3 mb-6">
            <span class="material-symbols-outlined text-primary text-2xl">auto_awesome</span>
            <div>
                <h3 class="text-xl font-bold text-primary">سیستم پیشنهاد هوشمند آسنا (Smart Recommendations)</h3>
                <p class="text-xs text-on-surface-variant">محصولات منتخب و مکمل‌های مراقبتی هماهنگ با نیاز حیوان شما</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($recommended_products as $rec): ?>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-outline-variant/20 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="aspect-square bg-surface-container-lowest rounded-xl overflow-hidden mb-3">
                    <img loading="lazy" src="<?php echo htmlspecialchars($rec['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($rec['name']); ?>">
                </div>
                <div>
                    <h4 class="text-xs font-bold text-on-surface line-clamp-2 mb-2"><?php echo htmlspecialchars($rec['name']); ?></h4>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-primary"><?php echo number_format($rec['discount_price'] ?? $rec['price']); ?> تومان</span>
                        <a href="product_details.php?id=<?php echo $rec['id']; ?>" class="text-xs text-primary font-bold hover:underline">مشاهده</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- Client Side Interactive Scripts -->
<script>
// Animal Carousel Scroll Control
function scrollAnimalCarousel(direction) {
    const container = document.getElementById('animal-carousel-container');
    if (container) {
        container.scrollBy({
            left: direction * -240, // RTL scroll direction
            behavior: 'smooth'
        });
    }
}

// Brand Search Filter (Page 6)
function filterBrandList(query) {
    const q = query.toLowerCase().trim();
    const labels = document.querySelectorAll('#brands-checkbox-list .brand-item-label');
    labels.forEach(label => {
        const text = label.querySelector('.brand-item-name').innerText.toLowerCase();
        if (text.includes(q)) {
            label.style.display = 'flex';
        } else {
            label.style.display = 'none';
        }
    });
}

// Accordion Toggle
function toggleAccordion(element) {
    const content = element.nextElementSibling;
    const icon = element.querySelector('.material-symbols-outlined');
    if (content) {
        content.classList.toggle('hidden');
        if (icon) {
            icon.innerText = icon.innerText === 'expand_less' ? 'expand_more' : 'expand_less';
        }
    }
}

// Mobile Filter Sidebar Toggle
function toggleFilters() {
    const sidebar = document.getElementById('filter-sidebar');
    const backdrop = document.getElementById('filter-backdrop');
    if (sidebar && backdrop) {
        const isHidden = sidebar.classList.contains('translate-x-full');
        if (isHidden) {
            sidebar.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        } else {
            sidebar.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
        }
    }
}

// Header Autoship Quick Toggle
function toggleAutoshipParam(isChecked) {
    const url = new URL(window.location.href);
    if (isChecked) {
        url.searchParams.set('autoship', '1');
    } else {
        url.searchParams.delete('autoship');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Add to Cart with AJAX
function addToCart(btn, productId, type = 'standard') {
    if(window.event) window.event.preventDefault();
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
    btn.disabled = true;
    
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId + '&type=' + type
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">check_circle</span>';
            btn.classList.add('bg-status-active');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-status-active');
                btn.disabled = false;
            }, 2000);
        } else {
            alert('خطا در افزودن به سبد خرید');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Toggle Wishlist with AJAX
function toggleWishlist(btn, productId) {
    if(window.event) window.event.preventDefault();
    
    fetch('actions/wishlist_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            const icon = btn.querySelector('.material-symbols-outlined');
            if(data.in_wishlist) {
                icon.style.fontVariationSettings = "'FILL' 1";
                icon.style.color = '#dc2626';
            } else {
                icon.style.fontVariationSettings = "'FILL' 0";
                icon.style.color = 'inherit';
            }
        } else {
            if(data.message) alert(data.message);
        }
    })
    .catch(err => console.error(err));
}
</script>

<?php include 'includes/footer.php'; ?>
