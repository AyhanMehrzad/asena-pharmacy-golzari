<?php
$currentPage = 'inventory';
require_once 'includes/admin_header.php';

// Check columns
$has_animal_col = false;
$has_tag_col = false;
$has_autoship_col = false;
try {
    $col_check = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $col_check->fetchAll(PDO::FETCH_COLUMN);
    $has_animal_col = in_array('target_animal', $columns);
    $has_tag_col = in_array('pharmacy_tag', $columns);
    $has_autoship_col = in_array('is_autoship', $columns);
} catch (Exception $e) {}

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../includes/functions.php';
    csrf_verify();
    
    $action = $_POST['action'];
    $target_animal = $_POST['target_animal'] ?? 'all';
    $pharmacy_tag = !empty($_POST['pharmacy_tag']) ? $_POST['pharmacy_tag'] : null;
    $is_autoship = isset($_POST['is_autoship']) ? 1 : 0;
    $autoship_discount = (int)($_POST['autoship_discount'] ?? 10);

    if ($action === 'add') {
        $baseline_rating = !empty($_POST['baseline_rating']) ? (float)$_POST['baseline_rating'] : 4.8;
        if ($has_animal_col && $has_tag_col && $has_autoship_col) {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, discount_price, image_url, description, stock, brand, target_animal, pharmacy_tag, is_autoship, autoship_discount, baseline_rating, rating_cache) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['name']), trim($_POST['category']), (int)$_POST['price'], 
                empty($_POST['discount_price']) ? null : (int)$_POST['discount_price'], trim($_POST['image_url']), 
                trim($_POST['description']), (int)($_POST['stock'] ?: 10), trim($_POST['brand']),
                $target_animal, $pharmacy_tag, $is_autoship, $autoship_discount, $baseline_rating, $baseline_rating
            ]);
            $new_pid = $pdo->lastInsertId();
            recalculate_bayesian_rating($pdo, 'product', $new_pid);
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, discount_price, image_url, description, stock, brand, baseline_rating, rating_cache) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['name']), trim($_POST['category']), (int)$_POST['price'], 
                empty($_POST['discount_price']) ? null : (int)$_POST['discount_price'], trim($_POST['image_url']), 
                trim($_POST['description']), (int)($_POST['stock'] ?: 10), trim($_POST['brand']), $baseline_rating, $baseline_rating
            ]);
        }
    } elseif ($action === 'edit') {
        $pid = (int)$_POST['product_id'];
        $baseline_rating = !empty($_POST['baseline_rating']) ? (float)$_POST['baseline_rating'] : 4.8;
        if ($has_animal_col && $has_tag_col && $has_autoship_col) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, discount_price=?, image_url=?, description=?, stock=?, brand=?, target_animal=?, pharmacy_tag=?, is_autoship=?, autoship_discount=?, baseline_rating=? WHERE id=?");
            $stmt->execute([
                trim($_POST['name']), trim($_POST['category']), (int)$_POST['price'], 
                empty($_POST['discount_price']) ? null : (int)$_POST['discount_price'], trim($_POST['image_url']), 
                trim($_POST['description']), (int)($_POST['stock'] ?: 10), trim($_POST['brand']),
                $target_animal, $pharmacy_tag, $is_autoship, $autoship_discount, $baseline_rating, $pid
            ]);
            recalculate_bayesian_rating($pdo, 'product', $pid);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, discount_price=?, image_url=?, description=?, stock=?, brand=?, baseline_rating=? WHERE id=?");
            $stmt->execute([
                trim($_POST['name']), trim($_POST['category']), (int)$_POST['price'], 
                empty($_POST['discount_price']) ? null : (int)$_POST['discount_price'], trim($_POST['image_url']), 
                trim($_POST['description']), (int)($_POST['stock'] ?: 10), trim($_POST['brand']), $baseline_rating, $pid
            ]);
            recalculate_bayesian_rating($pdo, 'product', $pid);
        }
    } elseif ($action === 'toggle_autoship') {
        $pid = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("UPDATE products SET is_autoship = 1 - is_autoship WHERE id = ?");
        $stmt->execute([$pid]);
        if (isset($_POST['ajax'])) {
            $stmt = $pdo->prepare("SELECT is_autoship, autoship_discount FROM products WHERE id = ?");
            $stmt->execute([$pid]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'is_autoship' => $updated['is_autoship']]);
            exit;
        }
        header("Location: inventory.php");
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([(int)$_POST['product_id']]);
    }
    header("Location: inventory.php");
    exit;
}

// Filter Tab
$tab = $_GET['tab'] ?? 'all';
$query_where = "";
if ($tab === 'pharmacy') {
    $query_where = "WHERE (category LIKE '%دارو%' OR category LIKE '%مکمل%' OR pharmacy_tag IS NOT NULL)";
} elseif ($tab === 'shop') {
    $query_where = "WHERE category NOT LIKE '%دارو%' AND (pharmacy_tag IS NULL OR pharmacy_tag = '')";
} elseif ($tab === 'autoship') {
    $query_where = "WHERE is_autoship = 1";
}

// Fetch Products
$stmt = $pdo->query("SELECT * FROM products $query_where ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Statistics
$allCountStmt = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = $allCountStmt->fetchColumn();

$outOfStock = count(array_filter($products, fn($p) => $p['stock'] <= 0));
$lowStock = count(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] < 5));

$animal_names = [
    'dog' => 'سگ 🐕',
    'cat' => 'گربه 🐈',
    'horse' => 'اسب 🐎',
    'cow' => 'دام 🐄',
    'chick' => 'طیور 🐥',
    'all' => 'همه گونه‌ها 🐾'
];

$pharmacy_tag_names = [
    'drugs' => 'داروها',
    'pain_management' => 'مدیریت درد',
    'inflammation' => 'التهاب و تنفس',
    'vitamins' => 'ویتامین و مکمل',
    'therapy' => 'درمانی',
    'dewormer' => 'ضد انگل',
    'hoof_care' => 'مراقبت سم',
    'first_aid' => 'کمک‌های اولیه',
    'vaccines' => 'واکسن‌ها'
];
?>

<!-- Main Dashboard Canvas -->
<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Page Header & Quick Action -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-1">مدیریت انبار، فروشگاه و داروخانه</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">تفکیک اقلام دارویی، محصولات پت‌شاپ و اشتراک‌های تحویل خودکار</p>
        </div>
        <button onclick="openModal('add')" class="flex items-center gap-2 bg-secondary-container text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl active:scale-95 transition-all">
            <span class="material-symbols-outlined">add</span>
            <span>افزودن محصول / داروی جدید</span>
        </button>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between">
            <div>
                <p class="text-label-sm text-on-surface-variant mb-1">کل موجودی کالاها</p>
                <p class="text-display-lg font-bold text-primary"><?= $totalProducts ?></p>
            </div>
            <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[32px]">inventory_2</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between group hover:border-error transition-colors">
            <div>
                <p class="text-label-sm text-error font-bold mb-1">ناموجود</p>
                <p class="text-display-lg font-bold text-error"><?= $outOfStock ?></p>
            </div>
            <div class="w-12 h-12 bg-error/10 rounded-lg flex items-center justify-center text-error">
                <span class="material-symbols-outlined text-[32px]">block</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between group hover:border-status-warning transition-colors">
            <div>
                <p class="text-label-sm text-status-warning font-bold mb-1">رو به اتمام (کمتر از ۵)</p>
                <p class="text-display-lg font-bold text-status-warning"><?= $lowStock ?></p>
            </div>
            <div class="w-12 h-12 bg-status-warning/10 rounded-lg flex items-center justify-center text-status-warning">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs (Pharmacy vs Store vs Autoship) -->
    <div class="flex items-center gap-3 mb-6 overflow-x-auto pb-2">
        <a href="inventory.php" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all <?= $tab === 'all' ? 'bg-primary text-white shadow-md' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            همه کالاها (<?= $totalProducts ?>)
        </a>
        <a href="inventory.php?tab=pharmacy" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-1.5 <?= $tab === 'pharmacy' ? 'bg-secondary-container text-white shadow-md' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-[18px]">local_pharmacy</span>
            داروخانه تخصصی دامپزشکی
        </a>
        <a href="inventory.php?tab=shop" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-1.5 <?= $tab === 'shop' ? 'bg-primary text-white shadow-md' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-[18px]">storefront</span>
            پت‌شاپ عمومی
        </a>
        <a href="inventory.php?tab=autoship" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-1.5 <?= $tab === 'autoship' ? 'bg-status-active text-white shadow-md' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-[18px]">autorenew</span>
            کالاهای دارای تحویل خودکار (Autoship)
        </a>
    </div>

    <!-- Explanation Banner for Subscription/Autoship Tab -->
    <?php if ($tab === 'autoship'): ?>
    <div class="bg-gradient-to-r from-primary/10 via-secondary-container/10 to-primary/5 p-6 rounded-3xl border border-primary/20 mb-8 shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-secondary-container text-white flex items-center justify-center flex-shrink-0 shadow-md">
                    <span class="material-symbols-outlined text-2xl">all_inclusive</span>
                </div>
                <div>
                    <h4 class="font-bold text-primary text-base mb-1">انبار اختصاصی و سهمیه‌بندی اشتراک‌ها (Subscription Inventory Allocation)</h4>
                    <p class="text-xs text-on-surface-variant leading-relaxed max-w-3xl">
                        برای جلوگیری از ناموجود شدن اقلام مشتریان دارای <strong>اشتراک و تحویل دوره‌ای (Autoship)</strong>، موجودی کل این کالاها به دو بخش <strong>«سهمیه رزرو شده اشتراک‌ها»</strong> و <strong>«موجودی آزاد فروشگاه عادی»</strong> تفکیک شده است.
                    </p>
                </div>
            </div>
            <a href="subscriptions.php" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white text-xs font-bold transition-all shadow-sm shrink-0 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">calendar_month</span>
                مشاهده نوبت‌های ارسال
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inventory List -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-low border-b border-outline-variant/50 text-xs text-primary">
                    <?php if ($tab === 'autoship'): ?>
                    <tr>
                        <th class="px-6 py-4 font-bold">جزئیات کالا / داروی اشتراکی</th>
                        <th class="px-6 py-4 font-bold">دسته‌بندی و گونه</th>
                        <th class="px-6 py-4 font-bold">موجودی کل انبار</th>
                        <th class="px-6 py-4 font-bold">تخفیف اشتراک</th>
                        <th class="px-6 py-4 font-bold">سهمیه رزرو اشتراک‌ها</th>
                        <th class="px-6 py-4 font-bold">موجودی آزاد فروش عادی</th>
                        <th class="px-6 py-4 font-bold">وضعیت تامین سهمیه</th>
                        <th class="px-6 py-4 font-bold">عملیات</th>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th class="px-6 py-4 font-bold">جزئیات کالا</th>
                        <th class="px-6 py-4 font-bold">دسته‌بندی و حوزه</th>
                        <th class="px-6 py-4 font-bold">گونه هدف</th>
                        <th class="px-6 py-4 font-bold">قیمت (تومان)</th>
                        <th class="px-6 py-4 font-bold">Autoship</th>
                        <th class="px-6 py-4 font-bold">موجودی</th>
                        <th class="px-6 py-4 font-bold">عملیات</th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if(empty($products)): ?>
                        <tr><td colspan="<?= $tab === 'autoship' ? '8' : '7' ?>" class="px-6 py-8 text-center text-on-surface-variant">هیچ کالایی در این دسته‌بندی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <?php 
                            $is_pharma = (str_contains($product['category'] ?? '', 'دارو') || str_contains($product['category'] ?? '', 'مکمل') || !empty($product['pharmacy_tag']));
                            $total_stock = (int)$product['stock'];
                            $reserved_stock = max(1, min($total_stock, (int)ceil($total_stock * 0.25))); // 25% allocated for active subscribers
                            $free_stock = max(0, $total_stock - $reserved_stock);
                        ?>
                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl border border-outline-variant/20 overflow-hidden flex-shrink-0 bg-surface-container-low">
                                        <img class="w-full h-full object-cover" src="<?= htmlspecialchars($product['image_url'] ?: 'assets/images/toy-mouse.jpg') ?>" alt="Product">
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-primary"><?= htmlspecialchars($product['name']) ?></p>
                                        <p class="text-[11px] text-on-surface-variant font-mono"><?= htmlspecialchars($product['brand'] ?: 'بدون برند') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <?php if($is_pharma): ?>
                                        <span class="px-2.5 py-0.5 rounded-full bg-secondary-container/15 text-secondary-container text-[11px] font-bold w-fit">💊 داروخانه تخصصی</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full bg-primary-container/10 text-primary text-[11px] font-bold w-fit">🛍️ پت‌شاپ</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-on-surface-variant"><?= htmlspecialchars($product['category']) ?></span>
                                </div>
                            </td>

                            <?php if ($tab === 'autoship'): ?>
                                <!-- Autoship Dedicated Columns -->
                                <td class="px-6 py-4 font-bold text-sm persian-number">
                                    <span class="px-2.5 py-1 rounded-lg bg-surface-container font-bold text-primary">
                                        <?= $total_stock ?> عدد
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded bg-status-active/10 text-status-active text-[11px] font-bold">
                                        <?= $product['autoship_discount'] ?? 10 ?>٪ تخفیف
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-xs text-secondary-container">
                                    <span class="inline-flex items-center gap-1 bg-secondary-container/10 px-2 py-1 rounded-lg border border-secondary-container/20">
                                        <span class="material-symbols-outlined text-xs">all_inclusive</span>
                                        <?= $reserved_stock ?> عدد رزرو
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-xs text-on-surface">
                                    <span class="inline-flex items-center gap-1 bg-primary/5 text-primary px-2 py-1 rounded-lg">
                                        <span class="material-symbols-outlined text-xs">storefront</span>
                                        <?= $free_stock ?> عدد آزاد
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($total_stock <= 0): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-error/10 text-error text-[11px] font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">cancel</span>
                                            اتمام موجودی اشتراک
                                        </span>
                                    <?php elseif ($total_stock < 5): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-status-warning/15 text-status-warning text-[11px] font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">warning</span>
                                            هشدار کسری سهمیه
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-status-active/10 text-status-active text-[11px] font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">verified</span>
                                            سهمیه تامین شده
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <!-- Standard Columns -->
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-on-surface"><?= $animal_names[$product['target_animal'] ?? 'all'] ?? 'عمومی' ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col items-start">
                                        <p class="font-bold text-sm"><?= number_format($product['price']) ?></p>
                                        <?php if($product['discount_price']): ?>
                                            <p class="text-[10px] text-error line-through"><?= number_format($product['discount_price']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="inventory.php" method="POST" class="m-0 inline-block">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_autoship">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <?php if(!empty($product['is_autoship'])): ?>
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-bold inline-flex items-center gap-1 hover:bg-emerald-200 transition-colors cursor-pointer" title="کلیک برای غیرفعال‌سازی تحویل خودکار دوره‌ای">
                                                <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                                فعال (<?= $product['autoship_discount'] ?? 10 ?>٪)
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-surface-container hover:bg-secondary-container hover:text-white text-on-surface-variant text-[11px] font-bold inline-flex items-center gap-1 transition-colors cursor-pointer" title="کلیک برای فعال‌سازی تحویل خودکار دوره‌ای (Autoship)">
                                                <span class="material-symbols-outlined text-[13px]">add_circle</span>
                                                + فعال‌سازی
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <?php if ($product['stock'] <= 0): ?>
                                            <span class="w-2 h-2 rounded-full bg-error"></span>
                                            <p class="text-xs font-bold text-error">ناموجود</p>
                                        <?php elseif ($product['stock'] < 5): ?>
                                            <span class="w-2 h-2 rounded-full bg-status-warning"></span>
                                            <p class="text-xs font-bold text-status-warning"><?= $product['stock'] ?> (کم)</p>
                                        <?php else: ?>
                                            <span class="w-2 h-2 rounded-full bg-status-active"></span>
                                            <p class="text-xs font-bold text-status-active"><?= $product['stock'] ?> عدد</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>

                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button type="button" onclick="editProduct(this)" data-product="<?= htmlspecialchars(json_encode($product, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>" class="p-2 text-on-surface-variant hover:text-primary transition-colors cursor-pointer" title="ویرایش">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('آیا از حذف این کالا اطمینان دارید؟');" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="p-2 text-on-surface-variant hover:text-error transition-colors" title="حذف">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product / Pharmacy Item Modal -->
<div id="productModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-8 py-5 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-low">
            <h3 id="modalTitle" class="font-title-lg text-title-lg text-primary font-bold">افزودن محصول / داروی جدید</h3>
            <button onclick="closeModal()" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-8 overflow-y-auto">
            <form id="productForm" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="form_action" value="add">
                <input type="hidden" name="product_id" id="form_product_id" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">نام کالا / دارو *</label>
                        <input type="text" name="name" id="productName" required class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">دسته‌بندی اصلی *</label>
                        <select name="category" id="productCategory" required class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                            <option value="داروخانه تخصصی">داروخانه تخصصی (Pharmacy)</option>
                            <option value="مکمل دارویی">مکمل دارویی و درمانی</option>
                            <option value="غذای سگ">غذای سگ</option>
                            <option value="غذای گربه">غذای گربه</option>
                            <option value="لوازم بهداشتی">لوازم بهداشتی و درمانی</option>
                            <option value="اسباب‌بازی">اسباب‌بازی و لوازم جانبی</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">گونه هدف حیوان</label>
                        <select name="target_animal" id="productAnimal" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                            <option value="all">همه گونه‌ها (عمومی)</option>
                            <option value="dog">سگ 🐕</option>
                            <option value="cat">گربه 🐈</option>
                            <option value="horse">اسب 🐎</option>
                            <option value="cow">گاو و دام 🐄</option>
                            <option value="chick">جوجه و طیور 🐥</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">تگ تخصصی داروخانه (اختیاری)</label>
                        <select name="pharmacy_tag" id="productPharmacyTag" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                            <option value="">بدون تگ تخصصی</option>
                            <option value="drugs">داروها</option>
                            <option value="pain_management">مدیریت درد</option>
                            <option value="inflammation">مدیریت التهاب و تنفس</option>
                            <option value="vitamins">ویتامین‌ها و مکمل‌ها</option>
                            <option value="therapy">محصولات درمانی</option>
                            <option value="dewormer">ضد انگل و کرم‌کش</option>
                            <option value="hoof_care">مراقبت از سم و پنجه</option>
                            <option value="first_aid">کمک‌های اولیه</option>
                            <option value="vaccines">واکسن‌ها</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">برند / شرکت سازنده</label>
                        <input type="text" name="brand" id="productBrand" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">موجودی انبار *</label>
                        <input type="number" name="stock" id="productStock" required min="0" value="10" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">قیمت اصلی (تومان) *</label>
                        <input type="number" name="price" id="productPrice" required min="0" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">قیمت ویژه تخفیف‌دار (تومان)</label>
                        <input type="number" name="discount_price" id="productDiscountPrice" min="0" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-on-surface-variant">امتیاز اولیه کارشناسی (Baseline Quality)</label>
                        <input type="number" step="0.1" min="1" max="5" name="baseline_rating" id="productBaselineRating" value="4.8" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                    </div>
                </div>

                <!-- Autoship Options -->
                <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/30 flex items-center justify-between">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_autoship" id="productIsAutoship" value="1" class="w-5 h-5 rounded text-primary focus:ring-primary">
                        <div>
                            <span class="text-sm font-bold text-primary block">فعال‌سازی تحویل خودکار دوره‌ای (Autoship)</span>
                            <span class="text-xs text-on-surface-variant">امکان سفارش دوره‌ای این کالا برای مشتریان فراهم می‌شود</span>
                        </div>
                    </label>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold">درصد تخفیف اشتراک:</label>
                        <input type="number" name="autoship_discount" id="productAutoshipDiscount" min="1" max="50" value="10" class="w-16 p-1.5 rounded-lg border border-outline-variant text-center text-sm font-bold">
                        <span class="text-xs">٪</span>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label class="text-xs font-bold text-on-surface-variant">آدرس تصویر محصول (URL)</label>
                    <input type="url" name="image_url" id="productImageUrl" class="w-full rounded-xl border border-outline-variant p-2.5 text-xs text-left outline-none focus:border-primary" dir="ltr">
                </div>
                
                <div class="space-y-1">
                    <label class="text-xs font-bold text-on-surface-variant">توضیحات و دستور مصرف دارویی</label>
                    <textarea name="description" id="productDescription" rows="3" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary resize-none"></textarea>
                </div>
                
                <div class="pt-4 flex justify-end gap-3 border-t border-outline-variant/30">
                    <button type="button" onclick="closeModal()" class="px-6 py-2.5 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container transition-colors text-sm">انصراف</button>
                    <button type="submit" class="px-8 py-2.5 rounded-xl font-bold bg-primary text-white hover:bg-primary-container transition-colors text-sm">ذخیره اطلاعات کالا</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(btn) {
    try {
        const raw = btn.getAttribute('data-product');
        const product = JSON.parse(raw);
        openModal('edit', product);
    } catch(e) {
        console.error('Error parsing product data:', e);
    }
}

function openModal(mode, product = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');
    
    if (mode === 'edit' && product) {
        title.innerText = 'ویرایش کالا / دارو';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_product_id').value = product.id;
        document.getElementById('productName').value = product.name || '';
        document.getElementById('productCategory').value = product.category || 'داروخانه تخصصی';
        document.getElementById('productAnimal').value = product.target_animal || 'all';
        document.getElementById('productPharmacyTag').value = product.pharmacy_tag || '';
        document.getElementById('productBrand').value = product.brand || '';
        document.getElementById('productStock').value = product.stock ?? 10;
        document.getElementById('productPrice').value = product.price || 0;
        document.getElementById('productDiscountPrice').value = product.discount_price || '';
        document.getElementById('productBaselineRating').value = product.baseline_rating || 4.8;
        document.getElementById('productImageUrl').value = product.image_url || '';
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productIsAutoship').checked = (product.is_autoship == 1);
        document.getElementById('productAutoshipDiscount').value = product.autoship_discount || 10;
    } else {
        title.innerText = 'افزودن محصول / داروی جدید';
        form.reset();
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_product_id').value = '';
        document.getElementById('productBaselineRating').value = '4.8';
        document.getElementById('productAnimal').value = 'all';
        document.getElementById('productIsAutoship').checked = false;
        document.getElementById('productAutoshipDiscount').value = 10;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('productModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
