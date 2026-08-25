<?php
$currentPage = 'recommendations';
require_once 'includes/admin_header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../includes/functions.php';
    csrf_verify();
    
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO curated_recommendations 
            (slot_type, product_id, custom_badge, custom_title, custom_subtitle, is_active, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($_POST['slot_type']),
            (int)$_POST['product_id'],
            trim($_POST['custom_badge'] ?? ''),
            trim($_POST['custom_title'] ?? ''),
            trim($_POST['custom_subtitle'] ?? ''),
            isset($_POST['is_active']) ? 1 : 0,
            (int)($_POST['display_order'] ?? 0)
        ]);
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE curated_recommendations 
            SET slot_type=?, product_id=?, custom_badge=?, custom_title=?, custom_subtitle=?, is_active=?, display_order=? 
            WHERE id=?
        ");
        $stmt->execute([
            trim($_POST['slot_type']),
            (int)$_POST['product_id'],
            trim($_POST['custom_badge'] ?? ''),
            trim($_POST['custom_title'] ?? ''),
            trim($_POST['custom_subtitle'] ?? ''),
            isset($_POST['is_active']) ? 1 : 0,
            (int)($_POST['display_order'] ?? 0),
            (int)$_POST['rec_id']
        ]);
    } elseif ($action === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE curated_recommendations SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([(int)$_POST['rec_id']]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM curated_recommendations WHERE id = ?");
        $stmt->execute([(int)$_POST['rec_id']]);
    }
    
    header("Location: recommendations.php" . (!empty($_GET['slot']) ? "?slot=" . urlencode($_GET['slot']) : ""));
    exit;
}

// Current Filter
$currentSlot = $_GET['slot'] ?? 'all';
$queryWhere = "";
$queryParams = [];
if ($currentSlot !== 'all') {
    $queryWhere = "WHERE cr.slot_type = ?";
    $queryParams[] = $currentSlot;
}

// Fetch Recommendations with Product Details
$stmt = $pdo->prepare("
    SELECT cr.*, p.name as product_name, p.category as product_category, 
           p.price as product_price, p.discount_price as product_discount_price,
           p.image_url as product_image_url, p.target_animal, p.stock
    FROM curated_recommendations cr
    JOIN products p ON cr.product_id = p.id
    $queryWhere
    ORDER BY cr.slot_type ASC, cr.display_order ASC, cr.created_at DESC
");
$stmt->execute($queryParams);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Products for Dropdown
$productsStmt = $pdo->query("SELECT id, name, category, price, discount_price, image_url, stock, target_animal FROM products ORDER BY name ASC");
$allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Counters
$totalCount = $pdo->query("SELECT COUNT(*) FROM curated_recommendations")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM curated_recommendations WHERE is_active = 1")->fetchColumn();
$bannerCount = $pdo->query("SELECT COUNT(*) FROM curated_recommendations WHERE slot_type = 'banner' AND is_active = 1")->fetchColumn();
$notifCount = $pdo->query("SELECT COUNT(*) FROM curated_recommendations WHERE slot_type = 'notification' AND is_active = 1")->fetchColumn();
$upsellCount = $pdo->query("SELECT COUNT(*) FROM curated_recommendations WHERE slot_type = 'cart_upsell' AND is_active = 1")->fetchColumn();

$slotDetails = [
    'banner' => [
        'title' => 'بنر اسلایدر اصلی',
        'icon' => 'view_carousel',
        'color' => 'bg-primary/10 text-primary',
        'desc' => 'نمایش به عنوان اسلاید در بنر هدر صفحه اصلی'
    ],
    'notification' => [
        'title' => 'اعلان و پیام بالای سایت',
        'icon' => 'campaign',
        'color' => 'bg-secondary-container/15 text-secondary-container',
        'desc' => 'نوار پیام شناور در بالای تمام صفحات فروشگاه با دکمه خرید'
    ],
    'cart_upsell' => [
        'title' => 'پیشنهاد مکمل سبد خرید',
        'icon' => 'add_shopping_cart',
        'color' => 'bg-status-active/10 text-status-active',
        'desc' => 'پیشنهاد هوشمند خرید همراه در صفحه سبد خرید و تسویه‌حساب'
    ],
    'spotlight' => [
        'title' => 'محصول برگزیده (Spotlight)',
        'icon' => 'hotel_class',
        'color' => 'bg-purple-100 text-purple-800',
        'desc' => 'نمایش در بخش محصولات ویژه و پیشنهادات طلایی'
    ]
];
?>

<!-- Main Dashboard Canvas -->
<div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">
    
    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 shadow-sm">
        <div>
            <h1 class="text-headline-md font-bold text-primary mb-1">مدیریت پیشنهادات، بنرها و اعلان‌ها</h1>
            <p class="text-body-sm text-on-surface-variant">انتخاب دستی محصولات پیشنهادی برای اسلایدر اصلی، نوار اعلان تخفیف‌ها و پیشنهادهای سبد خرید</p>
        </div>
        <button onclick="openModal('add')" class="w-full sm:w-auto px-6 py-3 bg-secondary-container hover:bg-[#ea580c] text-white rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95">
            <span class="material-symbols-outlined">auto_awesome</span>
            <span>افزودن پیشنهاد جدید</span>
        </button>
    </div>

    <!-- KPIs Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">کل پیشنهادات فعال</p>
                <p class="text-2xl font-black text-primary"><?= $activeCount ?> <span class="text-xs font-normal text-on-surface-variant">/ <?= $totalCount ?></span></p>
            </div>
            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-2xl">recommend</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-primary mb-1">بنرهای اسلایدر هوم‌پیج</p>
                <p class="text-2xl font-black text-primary"><?= $bannerCount ?> فعال</p>
            </div>
            <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-2xl">view_carousel</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-secondary-container mb-1">نوار اعلان بالای سایت</p>
                <p class="text-2xl font-black text-secondary-container"><?= $notifCount ?> فعال</p>
            </div>
            <div class="w-10 h-10 bg-secondary-container/10 rounded-xl flex items-center justify-center text-secondary-container">
                <span class="material-symbols-outlined text-2xl">campaign</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-status-active mb-1">پیشنهاد سبد خرید (Upsell)</p>
                <p class="text-2xl font-black text-status-active"><?= $upsellCount ?> فعال</p>
            </div>
            <div class="w-10 h-10 bg-status-active/10 rounded-xl flex items-center justify-center text-status-active">
                <span class="material-symbols-outlined text-2xl">add_shopping_cart</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-outline-variant/30">
        <a href="recommendations.php" class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 <?= $currentSlot === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            همه موقعیت‌ها (<?= $totalCount ?>)
        </a>
        <a href="recommendations.php?slot=banner" class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1 <?= $currentSlot === 'banner' ? 'bg-primary text-white shadow-sm' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-base">view_carousel</span>
            بنر اسلایدر اصلی
        </a>
        <a href="recommendations.php?slot=notification" class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1 <?= $currentSlot === 'notification' ? 'bg-secondary-container text-white shadow-sm' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-base">campaign</span>
            نوار اعلان بالای سایت
        </a>
        <a href="recommendations.php?slot=cart_upsell" class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1 <?= $currentSlot === 'cart_upsell' ? 'bg-status-active text-white shadow-sm' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-base">add_shopping_cart</span>
            پیشنهادهای سبد خرید
        </a>
        <a href="recommendations.php?slot=spotlight" class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1 <?= $currentSlot === 'spotlight' ? 'bg-purple-700 text-white shadow-sm' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
            <span class="material-symbols-outlined text-base">hotel_class</span>
            محصولات برگزیده (Spotlight)
        </a>
    </div>

    <!-- Recommendations Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-low border-b border-outline-variant/50 text-xs text-primary font-bold">
                    <tr>
                        <th class="px-6 py-4">موقعیت نمایش (Slot)</th>
                        <th class="px-6 py-4">کالای انتخابی</th>
                        <th class="px-6 py-4">نشان و عنوان تبلیغاتی سفارشی</th>
                        <th class="px-6 py-4">قیمت کالا</th>
                        <th class="px-6 py-4">ترتیب</th>
                        <th class="px-6 py-4">وضعیت</th>
                        <th class="px-6 py-4">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-sm">
                    <?php if (empty($recommendations)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">inventory</span>
                                    <p class="font-bold">هیچ آیتم پیشنهادی در این موقعیت تعریف نشده است.</p>
                                    <button onclick="openModal('add')" class="mt-2 text-xs text-secondary-container font-bold underline">افزودن اولین آیتم</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recommendations as $rec): ?>
                        <?php 
                            $slotInfo = $slotDetails[$rec['slot_type']] ?? [
                                'title' => $rec['slot_type'],
                                'icon' => 'bookmark',
                                'color' => 'bg-surface-container text-on-surface'
                            ];
                        ?>
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $slotInfo['color'] ?>">
                                    <span class="material-symbols-outlined text-sm"><?= $slotInfo['icon'] ?></span>
                                    <?= $slotInfo['title'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-surface-container border border-outline-variant/20 overflow-hidden shrink-0">
                                        <img src="<?= htmlspecialchars($rec['product_image_url'] ?: 'assets/images/toy-mouse.jpg') ?>" class="w-full h-full object-cover" alt="Product">
                                    </div>
                                    <div>
                                        <p class="font-bold text-primary text-sm line-clamp-1"><?= htmlspecialchars($rec['product_name']) ?></p>
                                        <p class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($rec['product_category']) ?> | موجودی: <?= $rec['stock'] ?> عدد</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1 max-w-xs">
                                    <?php if (!empty($rec['custom_badge'])): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-secondary-container/10 text-secondary-container text-[11px] font-black"><?= htmlspecialchars($rec['custom_badge']) ?></span>
                                    <?php endif; ?>
                                    <p class="font-bold text-xs text-primary"><?= htmlspecialchars($rec['custom_title'] ?: $rec['product_name']) ?></p>
                                    <?php if (!empty($rec['custom_subtitle'])): ?>
                                        <p class="text-[11px] text-on-surface-variant line-clamp-1"><?= htmlspecialchars($rec['custom_subtitle']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                <?php if (!empty($rec['product_discount_price'])): ?>
                                    <span class="font-bold text-status-danger text-sm block"><?= number_format($rec['product_discount_price']) ?> ت</span>
                                    <span class="line-through text-on-surface-variant text-[11px]"><?= number_format($rec['product_price']) ?></span>
                                <?php else: ?>
                                    <span class="font-bold text-primary text-sm"><?= number_format($rec['product_price']) ?> ت</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-bold text-xs text-on-surface-variant">
                                <?= (int)$rec['display_order'] ?>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline m-0">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="rec_id" value="<?= $rec['id'] ?>">
                                    <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold transition-all <?= $rec['is_active'] ? 'bg-status-active/15 text-status-active hover:bg-status-active/25' : 'bg-outline-variant/20 text-on-surface-variant hover:bg-outline-variant/30' ?>">
                                        <?= $rec['is_active'] ? 'فعال ✓' : 'غیرفعال ✕' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="editRec(this)" data-rec='<?= json_encode($rec, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>' class="p-2 hover:bg-surface-container rounded-lg text-primary transition-colors" title="ویرایش">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('آیا از حذف این پیشنهاد اطمینان دارید؟');" class="inline m-0">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="rec_id" value="<?= $rec['id'] ?>">
                                        <button type="submit" class="p-2 hover:bg-status-danger/10 rounded-lg text-status-danger transition-colors" title="حذف">
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

<!-- Modal Add / Edit Recommendation -->
<div id="recModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 md:p-8 max-h-[92vh] overflow-y-auto space-y-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-outline-variant/30 pb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container text-2xl">auto_awesome</span>
                <h3 id="modalTitle" class="text-title-lg font-bold text-primary">افزودن پیشنهاد جدید</h3>
            </div>
            <button onclick="closeModal()" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="recForm" method="POST" class="space-y-4">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" id="form_action" value="add">
            <input type="hidden" name="rec_id" id="form_rec_id" value="">
            
            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface-variant">موقعیت نمایش در سایت *</label>
                <select name="slot_type" id="recSlotType" required class="w-full rounded-xl border border-outline-variant p-3 text-sm outline-none focus:border-primary font-bold">
                    <option value="banner">بنر اسلایدر اصلی هوم‌پیج (Hero Slider Banner)</option>
                    <option value="notification">نوار اعلان پیام ویژه بالای هدر (Top Broadcast Notification)</option>
                    <option value="cart_upsell">پیشنهاد مکمل سبد خرید (Cart Upsell & Cross-sell)</option>
                    <option value="spotlight">محصول برگزیده فروشگاه (Featured Spotlight)</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface-variant">انتخاب کالا از انبار *</label>
                <select name="product_id" id="recProductId" required class="w-full rounded-xl border border-outline-variant p-3 text-sm outline-none focus:border-primary">
                    <option value="">-- یک محصول را انتخاب کنید --</option>
                    <?php foreach ($allProducts as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['category']) ?> - <?= number_format($p['discount_price'] ?: $p['price']) ?> تومان)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-on-surface-variant">نشان یا بج سفارشی (Badge)</label>
                    <input type="text" name="custom_badge" id="recCustomBadge" placeholder="مثال: 🔥 پیشنهاد طلایی، ⚡ تخفیف ۲۰٪" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-on-surface-variant">ترتیب نمایش (اولویت)</label>
                    <input type="number" name="display_order" id="recDisplayOrder" value="1" min="0" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface-variant">عنوان بازاریابی سفارشی (اختیاری)</label>
                <input type="text" name="custom_title" id="recCustomTitle" placeholder="در صورت خالی بودن، نام کالا قرار می‌گیرد" class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-on-surface-variant">توضیح تکمیلی یا پیام تبلیغاتی</label>
                <textarea name="custom_subtitle" id="recCustomSubtitle" rows="2" placeholder="توضیحات جذاب بازاریابی برای متقاعد کردن خریدار..." class="w-full rounded-xl border border-outline-variant p-2.5 text-sm outline-none focus:border-primary"></textarea>
            </div>

            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/30 flex items-center justify-between">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" id="recIsActive" value="1" checked class="w-5 h-5 rounded text-primary focus:ring-primary">
                    <div>
                        <span class="text-sm font-bold text-primary block">فعالسازی فوری در سایت</span>
                        <span class="text-xs text-on-surface-variant">آیتم بلافاصله در موقعیت انتخاب شده برای خریداران نمایش داده می‌شود.</span>
                    </div>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/30">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl border border-outline-variant text-on-surface-variant text-sm font-bold hover:bg-surface-container">انصراف</button>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-container text-white rounded-xl text-sm font-bold shadow-md">ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRec(btn) {
    try {
        const raw = btn.getAttribute('data-rec');
        const rec = JSON.parse(raw);
        openModal('edit', rec);
    } catch(e) {
        console.error('Error parsing recommendation data:', e);
    }
}

function openModal(mode, rec = null) {
    const modal = document.getElementById('recModal');
    const form = document.getElementById('recForm');
    const title = document.getElementById('modalTitle');
    
    if (mode === 'edit' && rec) {
        title.innerText = 'ویرایش پیشنهاد';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_rec_id').value = rec.id;
        document.getElementById('recSlotType').value = rec.slot_type;
        document.getElementById('recProductId').value = rec.product_id;
        document.getElementById('recCustomBadge').value = rec.custom_badge || '';
        document.getElementById('recCustomTitle').value = rec.custom_title || '';
        document.getElementById('recCustomSubtitle').value = rec.custom_subtitle || '';
        document.getElementById('recDisplayOrder').value = rec.display_order ?? 1;
        document.getElementById('recIsActive').checked = (rec.is_active == 1);
    } else {
        title.innerText = 'افزودن پیشنهاد جدید';
        form.reset();
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_rec_id').value = '';
        document.getElementById('recDisplayOrder').value = 1;
        document.getElementById('recIsActive').checked = true;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('recModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
