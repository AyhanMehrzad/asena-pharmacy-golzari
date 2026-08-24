<?php
// Dynamic SEO parameters based on active filters
$animal_seo_map = ['dog' => 'سگ', 'cat' => 'گربه', 'horse' => 'اسب', 'cow' => 'دام و گاو', 'chick' => 'طیور و پرندگان'];
$tag_seo_map = [
    'drugs' => 'داروهای تخصصی',
    'pain_management' => 'مسکن و مدیریت درد',
    'inflammation' => 'داروهای التهاب و تنفس',
    'vitamins' => 'مکمل‌ها و ویتامین‌ها',
    'therapy' => 'محصولات درمانی',
    'dewormer' => 'ضد انگل و کرم‌کش',
    'hoof_care' => 'مراقبت سم و پنجه',
    'first_aid' => 'کمک‌های اولیه',
    'vaccines' => 'واکسن‌ها و سرم‌ها'
];

$selected_animal_param = isset($_GET['animal']) ? trim($_GET['animal']) : '';
$selected_tag_param = isset($_GET['tag']) ? trim($_GET['tag']) : '';

$page_title = "داروخانه آنلاین حیوانات و دامپزشکی آسنا | خرید دارو، واکسن و مکمل‌های تخصصی";
$page_description = "داروخانه آنلاین دامپزشکی آسنا؛ مرجع خرید آنلاین دارو، واکسن، مکمل و ضد انگل سگ، گربه، اسب، دام و طیور با تاییدیه دکتر داروساز و ارسال سریع با زنجیره سرد.";

if ($selected_animal_param && isset($animal_seo_map[$selected_animal_param])) {
    $page_title = "داروخانه تخصصی " . $animal_seo_map[$selected_animal_param] . " | خرید داروها، واکسن و مکمل‌های " . $animal_seo_map[$selected_animal_param] . " - آسنا";
    $page_description = "خرید آنلاین انواع دارو، مکمل درمانی، واکسن و ویتامین‌های مخصوص " . $animal_seo_map[$selected_animal_param] . " با ضمانت اصالت و تاییدیه دامپزشکی در داروخانه آنلاین آسنا.";
} elseif ($selected_tag_param && isset($tag_seo_map[$selected_tag_param])) {
    $page_title = "خرید آنلاین " . $tag_seo_map[$selected_tag_param] . " حیوانات | داروخانه دامپزشکی آسنا";
    $page_description = "لیست بهترین " . $tag_seo_map[$selected_tag_param] . " برای سگ، گربه، اسب و دام با قیمت مناسب، تحویل دوره‌ای Autoship و ارسال مطمئن.";
}

require_once 'includes/header.php';

// Pagination variables
$limit = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter variables
$animal = isset($_GET['animal']) ? trim($_GET['animal']) : '';
$pharmacy_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$autoship_only = isset($_GET['autoship']) && ($_GET['autoship'] == '1' || $_GET['autoship'] == 'true');
$min_rating = isset($_GET['min_rating']) && is_numeric($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_brands = isset($_GET['brands']) && is_array($_GET['brands']) ? $_GET['brands'] : [];
$price_ranges = isset($_GET['price_ranges']) && is_array($_GET['price_ranges']) ? $_GET['price_ranges'] : [];
$in_stock = isset($_GET['in_stock']) ? $_GET['in_stock'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevant';

// Check columns
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
} catch (Exception $e) {}

// Base pharmacy where clause: match pharmacy, supplements, veterinary or any medical tags
$where = [];
$params = [];

$pharma_or_conditions = [
    "category LIKE '%دارو%'",
    "category LIKE '%مکمل%'",
    "category LIKE '%Pharmacy%'",
    "category LIKE '%درمان%'",
    "category LIKE '%بهداشت%'"
];

if ($has_tag_col) {
    $pharma_or_conditions[] = "pharmacy_tag IS NOT NULL AND pharmacy_tag != ''";
}
if ($has_animal_col) {
    $pharma_or_conditions[] = "target_animal IN ('horse', 'cow', 'chick')";
}

$base_pharma_condition = "(" . implode(" OR ", $pharma_or_conditions) . ")";

// If specific animal or tag is selected, we filter directly
if ($animal && $animal !== 'all' && $has_animal_col) {
    $where[] = "(target_animal = ? OR target_animal = 'all')";
    $params[] = $animal;
} elseif ($pharmacy_tag && $has_tag_col) {
    $where[] = "pharmacy_tag = ?";
    $params[] = $pharmacy_tag;
} else {
    $where[] = $base_pharma_condition;
}

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ? OR brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
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
    foreach ($selected_brands as $b) $params[] = $b;
}

// Price filtering
if (!empty($price_ranges)) {
    $price_conditions = [];
    foreach ($price_ranges as $pr) {
        if ($pr === 'under_500k') $price_conditions[] = "price < 500000";
        elseif ($pr === '500k_to_1500k') $price_conditions[] = "(price >= 500000 AND price <= 1500000)";
        elseif ($pr === 'over_1500k') $price_conditions[] = "price > 1500000";
    }
    if (!empty($price_conditions)) $where[] = "(" . implode(" OR ", $price_conditions) . ")";
}

if ($in_stock) {
    $where[] = "stock > 0";
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Sorting
$orderBy = "ORDER BY created_at DESC";
if ($sort === 'price_asc') $orderBy = "ORDER BY price ASC";
elseif ($sort === 'price_desc') $orderBy = "ORDER BY price DESC";
elseif ($sort === 'rating_desc' && $has_rating_col) $orderBy = "ORDER BY rating_cache DESC";

// Count & Fetch
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Fallback: If no products found for default view, load all products so the page is never blank
if ($total == 0 && empty($search) && empty($pharmacy_tag) && empty($animal) && empty($selected_brands)) {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    $stmt = $pdo->prepare("SELECT * FROM products $orderBy LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $totalPages = ceil($total / $limit);
    $stmt = $pdo->prepare("SELECT * FROM products $whereClause $orderBy LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Distinct pharmacy brands
$brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
$all_brands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);

// User Wishlist
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishlist_stmt->execute([$_SESSION['user_id']]);
    $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Species List (Local self-hosted SVGs for Iran network compatibility)
$animal_list = [
    'all' => ['name' => 'همه گونه‌ها', 'image' => 'assets/images/all-pets-avatar.svg'],
    'dog' => ['name' => 'سگ', 'image' => 'assets/images/dog-avatar.svg'],
    'cat' => ['name' => 'گربه', 'image' => 'assets/images/cat-avatar.svg'],
    'horse' => ['name' => 'اسب', 'image' => 'assets/images/horse-avatar.svg'],
    'cow' => ['name' => 'گاو و دام', 'image' => 'assets/images/cow-avatar.svg'],
    'chick' => ['name' => 'جوجه و طیور', 'image' => 'assets/images/chick-avatar.svg']
];

// 9 Pharmacy Category Tags (Page 5)
$pharmacy_tags_list = [
    'drugs' => ['title' => 'داروها', 'icon' => 'medication'],
    'pain_management' => ['title' => 'مدیریت درد', 'icon' => 'healing'],
    'inflammation' => ['title' => 'مدیریت التهاب و تنفس', 'icon' => 'air'],
    'vitamins' => ['title' => 'ویتامین‌ها و مکمل‌ها', 'icon' => 'pill'],
    'therapy' => ['title' => 'محصولات درمانی', 'icon' => 'vital_signs'],
    'dewormer' => ['title' => 'ضد انگل و کرم‌کش', 'icon' => 'bug_report'],
    'hoof_care' => ['title' => 'مراقبت از سم و پنجه', 'icon' => 'footprint'],
    'first_aid' => ['title' => 'کمک‌های اولیه', 'icon' => 'medical_services'],
    'vaccines' => ['title' => 'واکسن‌ها', 'icon' => 'vaccines']
];

function buildUrl($updates) {
    $query = $_GET;
    foreach($updates as $key => $val) {
        if ($val === null || $val === '') unset($query[$key]);
        else $query[$key] = $val;
    }
    return '?' . http_build_query($query);
}
?>

<!-- Pharmacy Navigation Secondary Bar -->
<div class="bg-primary-container text-white border-t border-white/20 hidden md:block">
   <div class="max-w-container-max mx-auto px-margin-desktop flex items-center justify-between text-label-lg font-bold">
       <div class="flex gap-6 py-3">
           <a href="pharmacy.php" class="hover:text-secondary-container transition-colors <?php echo empty($animal) ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروخانه کل حیوانات</a>
           <a href="pharmacy.php?animal=dog" class="hover:text-secondary-container transition-colors <?php echo $animal == 'dog' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروهای سگ</a>
           <a href="pharmacy.php?animal=cat" class="hover:text-secondary-container transition-colors <?php echo $animal == 'cat' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروهای گربه</a>
           <a href="pharmacy.php?animal=horse" class="hover:text-secondary-container transition-colors <?php echo $animal == 'horse' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروهای اسب</a>
           <a href="pharmacy.php?animal=cow" class="hover:text-secondary-container transition-colors <?php echo $animal == 'cow' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروهای دام و احشام</a>
           <a href="pharmacy.php?animal=chick" class="hover:text-secondary-container transition-colors <?php echo $animal == 'chick' ? 'text-secondary-container underline underline-offset-8' : ''; ?>">داروهای طیور</a>
       </div>
       <div class="flex items-center gap-2 text-xs bg-white/10 px-3 py-1.5 rounded-full">
           <span class="material-symbols-outlined text-[16px] text-status-warning">emergency</span>
           <span>پشتیبانی و ثبت نسخه دارویی: ۰۲۱-۸۸۸۸۸۸۸</span>
       </div>
   </div>
</div>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[70vh]">
    
    <!-- Hero Banner with Prescription Upload Action -->
    <section class="bg-gradient-to-r from-[#001a48] via-primary-container to-[#002d72] text-white rounded-[2.5rem] p-8 lg:p-12 mb-10 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="max-w-2xl space-y-4">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-secondary-container text-white rounded-full text-xs font-bold shadow-md">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    داروخانه تخصصی و مجاز دامپزشکی آسنا
                </div>
                <h1 class="text-3xl lg:text-5xl font-bold leading-tight tracking-tight">
                    داروهای تخصصی و نسخه‌ای حیوانات خانگی و دام
                </h1>
                <p class="text-white/80 text-sm lg:text-base leading-relaxed">
                    تضمین اصالت داروها، شرایط نگهداری زنجیره سرد و تحویل فوری به سراسر کشور. نسخه پزشک خود را ارسال کنید تا داروها آماده و ارسال شوند.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <button onclick="document.getElementById('prescriptionModal').classList.remove('hidden')" class="bg-secondary-container text-white px-8 py-3.5 rounded-2xl font-bold hover:bg-[#ea580c] transition-all shadow-lg flex items-center gap-2">
                        <span class="material-symbols-outlined">upload_file</span>
                        ارسال تصویر نسخه پزشک
                    </button>
                    <a href="shop.php" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-6 py-3.5 rounded-2xl font-bold transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">storefront</span>
                        رفتن به فروشگاه عمومی پت
                    </a>
                </div>
            </div>

            <!-- Pharmacy Quick Stats -->
            <div class="grid grid-cols-2 gap-4 w-full lg:w-auto shrink-0">
                <div class="bg-white/10 backdrop-blur-md p-5 rounded-3xl border border-white/20 text-center">
                    <span class="material-symbols-outlined text-3xl text-secondary-container mb-2">ac_unit</span>
                    <p class="text-xl font-bold">۱۰۰٪</p>
                    <p class="text-xs text-white/70">حفظ زنجیره سرد</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md p-5 rounded-3xl border border-white/20 text-center">
                    <span class="material-symbols-outlined text-3xl text-status-warning mb-2">autorenew</span>
                    <p class="text-xl font-bold">Autoship</p>
                    <p class="text-xs text-white/70">تخفیف تکرار سفارش</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Circular Animal Selector (Centered, No Background Card) -->
    <section class="mb-10 relative">
        <div class="text-center max-w-xl mx-auto mb-6">
            <h2 class="text-2xl font-bold text-primary flex items-center justify-center gap-2 mb-1">
                <span class="material-symbols-outlined text-secondary-container">pets</span>
                انتخاب دارو بر اساس گونه حیوان
            </h2>
            <p class="text-xs text-on-surface-variant">دسته‌بندی داروهای اختصاصی دام کوچک و دام بزرگ</p>
        </div>

        <div class="relative flex items-center justify-center max-w-5xl mx-auto px-4">
            <!-- Navigation Arrow Prev -->
            <button type="button" onclick="scrollAnimalCarousel(-1)" class="w-10 h-10 rounded-full border border-outline-variant/40 bg-white flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm active:scale-95 shrink-0 ml-3 z-10">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>

            <!-- Centered Species Circles -->
            <div id="animal-carousel-container" class="flex items-center justify-center gap-6 overflow-x-auto py-3 px-2 no-scrollbar scroll-smooth">
                <?php foreach($animal_list as $key => $species): ?>
                    <?php $selected = ($animal === $key) || ($key === 'all' && empty($animal)); ?>
                    <a href="<?php echo buildUrl(['animal' => $key === 'all' ? null : $key, 'page' => 1]); ?>" 
                       class="animal-circle-item flex flex-col items-center gap-3 shrink-0 cursor-pointer group <?php echo $selected ? 'active' : ''; ?>"
                       style="width: 110px;">
                        <div class="animal-circle-ring w-24 h-24 rounded-full p-1 border-2 border-outline-variant/30 bg-surface-container-low transition-all duration-300 flex items-center justify-center relative overflow-hidden group-hover:border-primary shadow-sm">
                            <img src="<?php echo $species['image']; ?>" class="w-full h-full object-cover rounded-full group-hover:scale-110 transition-transform duration-500" alt="<?php echo $species['name']; ?>">
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
            <button type="button" onclick="scrollAnimalCarousel(1)" class="w-10 h-10 rounded-full border border-outline-variant/40 bg-white flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all shadow-sm active:scale-95 shrink-0 mr-3 z-10">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
        </div>
    </section>

    <!-- 9 Pharmacy Category Tags (Page 5) -->
    <div class="flex items-center gap-2 lg:gap-3 overflow-x-auto pb-4 mb-8 no-scrollbar">
        <?php foreach($pharmacy_tags_list as $tag_key => $tag_data): ?>
            <?php $is_active = ($pharmacy_tag === $tag_key); ?>
            <a href="<?php echo buildUrl(['tag' => $is_active ? null : $tag_key, 'page' => 1]); ?>" 
               class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs lg:text-sm font-medium whitespace-nowrap transition-all shrink-0 border <?php echo $is_active ? 'bg-primary text-white border-primary shadow-lg font-bold scale-105' : 'bg-white text-on-surface border-outline-variant/30 hover:border-primary'; ?>">
                <span class="material-symbols-outlined text-[18px]"><?php echo $tag_data['icon']; ?></span>
                <span><?php echo $tag_data['title']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Main Filter Form and Products Grid -->
    <form id="pharmacy-filter-form" action="pharmacy.php" method="GET" class="flex flex-col md:flex-row gap-8">
        <?php if($search): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <?php if($animal): ?><input type="hidden" name="animal" value="<?php echo htmlspecialchars($animal); ?>"><?php endif; ?>
        <?php if($pharmacy_tag): ?><input type="hidden" name="tag" value="<?php echo htmlspecialchars($pharmacy_tag); ?>"><?php endif; ?>

        <!-- Mobile Filter Toggle Button -->
        <button type="button" onclick="toggleFilters()" class="md:hidden w-full flex items-center justify-center gap-2 bg-surface-container-low border border-outline-variant rounded-xl py-3 font-bold text-primary mb-6 active:scale-95 transition-transform">
            <span class="material-symbols-outlined">tune</span>
            فیلترها و مرتب‌سازی پیشرفته داروخانه
        </button>

        <!-- Mobile Backdrop -->
        <div id="filter-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleFilters()"></div>

        <!-- Sidebar Facets -->
        <aside id="filter-sidebar" class="fixed inset-y-0 right-0 w-80 md:w-1/4 lg:w-1/5 bg-surface-container-lowest md:bg-transparent z-[70] md:z-0 md:relative flex-shrink-0 flex flex-col shadow-2xl md:shadow-none transition-transform duration-300 translate-x-full md:translate-x-0 overflow-y-auto md:overflow-visible">
           <div class="p-6 md:p-0">
               <div class="flex items-center justify-between mb-6 md:hidden">
                   <h2 class="text-title-lg font-bold text-primary">فیلترهای داروخانه</h2>
                   <button type="button" onclick="toggleFilters()" class="text-on-surface-variant hover:text-error transition-colors">
                       <span class="material-symbols-outlined">close</span>
                   </button>
               </div>
               
               <div class="flex items-center justify-between mb-4">
                   <h2 class="text-title-lg font-bold hidden md:block">فیلترهای داروخانه</h2>
                   <?php if($search || $animal || $pharmacy_tag || $autoship_only || $min_rating > 0 || !empty($selected_brands) || !empty($price_ranges) || $in_stock): ?>
                   <a href="pharmacy.php" class="text-xs text-error font-bold hover:underline">حذف همه فیلترها</a>
                   <?php endif; ?>
               </div>
           
               <!-- 1. Autoship Toggle Filter Switch -->
               <div class="bg-primary/5 rounded-2xl p-4 mb-4 border border-primary/10">
                   <div class="flex items-center justify-between">
                       <div class="flex items-center gap-2">
                           <span class="material-symbols-outlined text-secondary-container text-[20px]">autorenew</span>
                           <div>
                               <span class="text-sm font-bold text-primary block">فقط داروهای Autoship</span>
                               <span class="text-[11px] text-on-surface-variant">تخفیف دوره‌ای ۱۰٪ تا ۱۵٪</span>
                           </div>
                       </div>
                       <label class="autoship-switch">
                           <input type="checkbox" name="autoship" value="1" <?php echo $autoship_only ? 'checked' : ''; ?> onchange="this.form.submit()">
                           <span class="autoship-slider"></span>
                       </label>
                   </div>
               </div>

               <!-- 2. Clinical Pharmacy Categories Accordion -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>دسته‌بندی‌های دارویی</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   <ul class="space-y-2.5 text-body-md text-on-surface-variant pr-2 transition-all duration-300">
                       <li>
                            <a href="<?php echo buildUrl(['tag' => null, 'page' => 1]); ?>" class="flex items-center justify-between <?php echo empty($pharmacy_tag) ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>همه داروها و مکمل‌ها</span>
                                <?php if(empty($pharmacy_tag)): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <?php foreach($pharmacy_tags_list as $t_key => $t_info): ?>
                       <li>
                            <a href="<?php echo buildUrl(['tag' => $t_key, 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $pharmacy_tag === $t_key ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant"><?php echo $t_info['icon']; ?></span>
                                    <span><?php echo $t_info['title']; ?></span>
                                </span>
                                <?php if($pharmacy_tag === $t_key): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <?php endforeach; ?>
                   </ul>
               </div>

               <!-- 3. Target Animal Species Accordion -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>گونه هدف حیوان</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   <ul class="space-y-2.5 text-body-md text-on-surface-variant pr-2 transition-all duration-300">
                       <li>
                            <a href="<?php echo buildUrl(['animal' => null, 'page' => 1]); ?>" class="flex items-center justify-between <?php echo empty($animal) ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>همه گونه‌ها 🐾</span>
                                <?php if(empty($animal)): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['animal' => 'dog', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $animal === 'dog' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>سگ 🐕</span>
                                <?php if($animal === 'dog'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['animal' => 'cat', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $animal === 'cat' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>گربه 🐈</span>
                                <?php if($animal === 'cat'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['animal' => 'horse', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $animal === 'horse' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>اسب 🐎</span>
                                <?php if($animal === 'horse'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['animal' => 'cow', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $animal === 'cow' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>گاو و دام 🐄</span>
                                <?php if($animal === 'cow'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                       <li>
                            <a href="<?php echo buildUrl(['animal' => 'chick', 'page' => 1]); ?>" class="flex items-center justify-between <?php echo $animal === 'chick' ? 'font-bold text-primary' : 'hover:text-primary'; ?>">
                                <span>جوجه و طیور 🐥</span>
                                <?php if($animal === 'chick'): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                            </a>
                       </li>
                   </ul>
               </div>

               <!-- 4. Customer Rating Filter -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>امتیاز و رضایت دارویی</span>
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

               <!-- 5. Dynamic Pharmacy Brand Filter with Instant Search Bar -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>برندها و لابراتوارها</span>
                       <span class="material-symbols-outlined">expand_less</span>
                   </h3>
                   
                   <div class="mb-3 relative">
                       <input type="text" id="brand-search-input" onkeyup="filterBrandList(this.value)" placeholder="جستجوی برند دارو..." class="w-full text-xs py-2 px-3 pl-8 rounded-xl border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-surface-container-low">
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

               <!-- 6. Price Range Filter -->
               <div class="border-t border-outline-variant/30 py-4">
                   <h3 class="font-bold text-on-surface mb-3 flex justify-between items-center cursor-pointer select-none" onclick="toggleAccordion(this)">
                       <span>محدوده قیمت دارو</span>
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

               <!-- 7. In-Stock Availability Filter -->
               <div class="border-t border-outline-variant/30 py-4">
                   <div class="space-y-3 text-body-md text-on-surface-variant pr-2">
                       <label class="flex items-center gap-3 cursor-pointer hover:text-primary">
                           <input type="checkbox" name="in_stock" value="1" <?php echo $in_stock ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4">
                           <span class="text-sm font-bold text-primary">فقط داروهای موجود در انبار</span>
                       </label>
                   </div>
               </div>
           </div>
        </aside>

        <!-- Product Grid -->
        <section class="w-full md:w-3/4 lg:w-4/5">
            <div class="flex justify-between items-center mb-6 pb-3 border-b border-outline-variant/20">
                <h2 class="text-xl font-bold text-primary">
                    لیست داروها و مکمل‌های تخصصی (<?php echo $total; ?> قلم)
                </h2>
                <select name="sort" onchange="this.form.submit()" class="border border-outline-variant rounded-xl py-1.5 px-3 bg-white text-xs text-on-surface outline-none cursor-pointer">
                    <option value="relevant" <?php echo $sort == 'relevant' ? 'selected' : ''; ?>>مرتبط‌ترین / جدیدترین</option>
                    <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>بالاترین امتیاز رضایت</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>قیمت: کم به زیاد</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>قیمت: زیاد به کم</option>
                </select>
            </div>

            <?php if(count($products) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($products as $product): ?>
                <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-lg hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
                    
                    <!-- Badges Top Left -->
                    <div class="absolute top-5 left-5 flex flex-col gap-1.5 z-10">
                        <?php if($product['discount_price']): ?>
                        <div class="bg-secondary-container text-on-secondary-container text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm">تخفیف ویژه</div>
                        <?php endif; ?>
                        
                        <?php if(!empty($product['is_autoship'])): ?>
                        <div class="bg-primary-container text-white text-[11px] px-2 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">autorenew</span>
                            Autoship
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Image Container -->
                    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 sm:mb-6 overflow-hidden relative">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="block w-full h-full">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" onerror="this.src='assets/images/pharma-default.svg'" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        
                        <!-- Desktop Hover Overlay Only (lg:flex) -->
                        <div class="hidden lg:flex absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/70 to-transparent justify-center z-20">
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>)" class="bg-primary text-white w-full py-2.5 rounded-xl font-bold flex justify-center items-center gap-2 hover:bg-primary-container shadow-lg transition-colors cursor-pointer text-xs">
                                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                                افزودن به سبد دارو
                            </button>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 flex flex-col">
                        <div class="flex items-center justify-between text-xs text-on-surface-variant mb-1">
                            <span class="text-primary font-bold text-[11px]"><?php echo htmlspecialchars($product['brand'] ?? 'داروخانه آسنا'); ?></span>
                            <span class="text-status-warning font-bold flex items-center gap-0.5 text-xs">
                                <span class="material-symbols-outlined text-[14px] text-amber-500">star</span>
                                <?php echo $product['rating_cache'] ?? 4.8; ?>
                            </span>
                        </div>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="block mb-2">
                            <h3 class="text-sm sm:text-base font-bold text-on-surface line-clamp-2 hover:text-primary transition-colors leading-snug"><?php echo htmlspecialchars($product['name']); ?></h3>
                        </a>

                        <!-- Card Footer with Price & Permanent Touch-Friendly Button -->
                        <div class="mt-auto flex items-center justify-between pt-3 border-t border-outline-variant/20 gap-2">
                            <div class="flex flex-col">
                                <?php if($product['discount_price']): ?>
                                <span class="text-[10px] sm:text-xs text-on-surface-variant line-through font-mono"><?php echo number_format($product['price']); ?> ت</span>
                                <span class="text-sm sm:text-base font-bold text-primary font-mono"><?php echo number_format($product['discount_price']); ?> <span class="text-[10px] font-normal">تومان</span></span>
                                <?php else: ?>
                                <span class="text-sm sm:text-base font-bold text-primary font-mono"><?php echo number_format($product['price']); ?> <span class="text-[10px] font-normal">تومان</span></span>
                                <?php endif; ?>
                            </div>

                            <!-- Dedicated Touch & Mobile Button (ONLY for mobile/touch, hidden on desktop: lg:hidden) -->
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>)" 
                                    class="lg:hidden bg-primary hover:bg-primary-container text-white px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition-all shrink-0 cursor-pointer"
                                    title="افزودن به سبد دارو">
                                <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                                <span class="text-xs">خرید</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-3xl p-8 border border-outline-variant/20">
                    <p class="text-lg font-bold text-primary">دارویی در این دسته‌بندی یافت نشد.</p>
                </div>
            <?php endif; ?>
        </section>
    </form>

    <!-- Prescription Upload Modal -->
    <div id="prescriptionModal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative">
            <button onclick="document.getElementById('prescriptionModal').classList.add('hidden')" class="absolute top-6 left-6 text-on-surface-variant hover:text-error">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">medical_information</span>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-primary">ارسال نسخه پزشک</h3>
                    <p class="text-xs text-on-surface-variant">بررسی رایگان نسخه توسط داروسازان آسنا</p>
                </div>
            </div>
            <form onsubmit="alert('نسخه شما با موفقیت دریافت شد. همکاران ما در اسرع وقت با شما تماس خواهند گرفت.'); document.getElementById('prescriptionModal').classList.add('hidden'); return false;" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold mb-2">نام و نام خانوادگی:</label>
                    <input type="text" required class="w-full text-sm p-3 border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-2">شماره تماس همراه:</label>
                    <input type="tel" required placeholder="۰۹۱۲..." class="w-full text-sm p-3 border border-outline-variant rounded-xl outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-2">تصویر نسخه پزشک:</label>
                    <input type="file" required class="w-full text-xs p-2 border border-outline-variant rounded-xl">
                </div>
                <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-bold hover:bg-primary-container transition-all">
                    ثبت و ارسال نسخه
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION: VETERINARY PHARMACY SEO-RICH FAQ ACCORDION                       -->
    <!-- ========================================================================= -->
    <section class="mt-16 bg-white rounded-3xl p-8 border border-outline-variant/30 shadow-sm">
        <div class="text-center max-w-2xl mx-auto mb-8">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full mb-2">
                <span class="material-symbols-outlined text-[16px]">help</span>
                پرسش‌های متداول دارویی
            </span>
            <h3 class="text-2xl font-bold text-primary mb-2">راهنمای خرید و سوالات متداول داروخانه دامپزشکی آسنا</h3>
            <p class="text-xs text-on-surface-variant">پاسخ دکتر داروساز به مهم‌ترین سوالات شما درباره نحوه ارسال، تایید نسخه و شرایط نگهداری داروها</p>
        </div>

        <div class="space-y-4 max-w-4xl mx-auto">
            <div class="border border-outline-variant/30 rounded-2xl p-5 hover:border-primary/40 transition-colors">
                <h4 class="font-bold text-primary text-sm flex items-center justify-between cursor-pointer select-none" onclick="toggleAccordion(this)">
                    <span>۱. آیا برای خرید تمامی داروها نیاز به نسخه دامپزشک است؟</span>
                    <span class="material-symbols-outlined text-primary">expand_more</span>
                </h4>
                <p class="text-xs text-on-surface-variant mt-3 leading-relaxed hidden">
                    خیر؛ مکمل‌های تقویتی، ویتامین‌ها، محصولات مراقبت از پوست و مو و ضد انگل‌های عمومی بدون نیاز به نسخه قابل سفارش هستند. تنها داروهای تخصصی، آنتی‌بیوتیک‌ها و داروهای بیهوشی نیاز به آپلود نسخه یا تایید تلفنی با کلینیک دامپزشک دارند.
                </p>
            </div>

            <div class="border border-outline-variant/30 rounded-2xl p-5 hover:border-primary/40 transition-colors">
                <h4 class="font-bold text-primary text-sm flex items-center justify-between cursor-pointer select-none" onclick="toggleAccordion(this)">
                    <span>۲. شرایط نگهداری و ارسال واکسن‌ها و داروهای حساس به دما (زنجیره سرد) چگونه است؟</span>
                    <span class="material-symbols-outlined text-primary">expand_more</span>
                </h4>
                <p class="text-xs text-on-surface-variant mt-3 leading-relaxed hidden">
                    کلیه واکسن‌ها، سرم‌ها و داروهای بیولوژیک در کلمن‌های مخصوص دارای آیس‌پک استاندارد (زنجیره سرد ۲ تا ۸ درجه سانتی‌گراد) بسته‌بندی شده و توسط پیک اختصاصی در سریع‌ترین زمان ممکن تحویل داده می‌شوند.
                </p>
            </div>

            <div class="border border-outline-variant/30 rounded-2xl p-5 hover:border-primary/40 transition-colors">
                <h4 class="font-bold text-primary text-sm flex items-center justify-between cursor-pointer select-none" onclick="toggleAccordion(this)">
                    <span>۳. سیستم تحویل خودکار (Autoship) دارو چگونه کار می‌کند؟</span>
                    <span class="material-symbols-outlined text-primary">expand_more</span>
                </h4>
                <p class="text-xs text-on-surface-variant mt-3 leading-relaxed hidden">
                    برای داروهای مصرف مستمر (مانند داروهای قلبی، کلیوی یا مکمل‌های مفصلی)، می‌توانید بازه زمانی تحویل خودکار (مثلاً هر ۳۰ روز) را انتخاب کنید. سیستم به‌صورت خودکار سفارش را با ۱۰٪ تا ۱۵٪ تخفیف دائمی پردازش کرده و پیش از ارسال با شما هماهنگ خواهد کرد.
                </p>
            </div>

            <div class="border border-outline-variant/30 rounded-2xl p-5 hover:border-primary/40 transition-colors">
                <h4 class="font-bold text-primary text-sm flex items-center justify-between cursor-pointer select-none" onclick="toggleAccordion(this)">
                    <span>۴. چگونه می‌توانم پیش از خرید با دکتر داروساز مشورت کنم؟</span>
                    <span class="material-symbols-outlined text-primary">expand_more</span>
                </h4>
                <p class="text-xs text-on-surface-variant mt-3 leading-relaxed hidden">
                    شما می‌توانید از طریق خط اختصاصی ۰۲۱-۸۸۸۸۸۸۸۸ یا دکمه «مشاوره تلفنی با داروساز» در بالای صفحه، به‌صورت مستقیم با تیم متخصصان داروسازی دامپزشکی آسنا ارتباط برقرار نمایید.
                </p>
            </div>
        </div>
    </section>

    <!-- Schema.org JSON-LD Structured Data for Pharmacy SEO & Rich Snippets -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Pharmacy",
          "@id": "https://asena.pet/pharmacy#organization",
          "name": "داروخانه آنلاین دامپزشکی آسنا",
          "description": "داروخانه تخصصی حیوانات خانگی و دام آسنا، تامین مستقیم انواع دارو، واکسن، مکمل و ضد انگل با تاییدیه دامپزشکی",
          "telephone": "+98-21-88888888",
          "priceRange": "$$",
          "openingHours": "Mo-Su 00:00-24:00"
        },
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "خانه",
              "item": "https://asena.pet/index.php"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "داروخانه تخصصی دامپزشکی",
              "item": "https://asena.pet/pharmacy.php"
            }
          ]
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "آیا برای خرید تمامی داروها نیاز به نسخه دامپزشک است؟",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "خیر؛ مکمل‌های تقویتی، ویتامین‌ها و ضد انگل‌های عمومی بدون نیاز به نسخه قابل سفارش هستند. تنها داروهای تخصصی نیاز به تایید نسخه دارند."
              }
            },
            {
              "@type": "Question",
              "name": "شرایط نگهداری و ارسال واکسن‌ها و داروهای حساس به دما چگونه است؟",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "کلیه واکسن‌ها و داروهای بیولوژیک در کلمن‌های مخصوص دارای آیس‌پک استاندارد ۲ تا ۸ درجه سانتی‌گراد بسته‌بندی و ارسال می‌شوند."
              }
            },
            {
              "@type": "Question",
              "name": "سیستم تحویل خودکار (Autoship) دارو چگونه کار می‌کند؟",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "برای داروهای مصرف مستمر می‌توانید بازه تحویل دوره‌ای را انتخاب کرده و از ۱۰٪ تا ۱۵٪ تخفیف دائمی بهره‌مند شوید."
              }
            }
          ]
        }
      ]
    }
    </script>
</main>

<script>
function scrollAnimalCarousel(direction) {
    const container = document.getElementById('animal-carousel-container');
    if (container) container.scrollBy({ left: direction * -240, behavior: 'smooth' });
}

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

function addToCart(btn, productId) {
    if(window.event) window.event.preventDefault();
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
    btn.disabled = true;
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId
    })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">check_circle</span> اضافه شد';
            btn.classList.add('bg-status-active');
            setTimeout(() => { btn.innerHTML = originalText; btn.classList.remove('bg-status-active'); btn.disabled = false; }, 2000);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
