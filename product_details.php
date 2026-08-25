<?php
require_once 'includes/header.php';

$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    csrf_verify();
    if (!isset($_SESSION['user_id'])) {
        $error = "برای ثبت نظر باید وارد حساب کاربری شوید.";
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating >= 1 && $rating <= 5) {
            // Check verified purchase
            $vStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?");
            $vStmt->execute([$_SESSION['user_id'], $product_id]);
            $is_verified = ($vStmt->fetchColumn() > 0) ? 1 : 0;

            // Check existing review
            $chkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND target_type = 'product' AND target_id = ?");
            $chkStmt->execute([$_SESSION['user_id'], $product_id]);
            $existing_id = $chkStmt->fetchColumn();

            if ($existing_id) {
                $upd = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, is_verified_buyer = ?, status = 'approved', created_at = NOW() WHERE id = ?");
                $upd->execute([$rating, $comment, $is_verified, $existing_id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO reviews (user_id, target_type, target_id, rating, comment, is_verified_buyer, status, created_at) VALUES (?, 'product', ?, ?, ?, ?, 'approved', NOW())");
                $ins->execute([$_SESSION['user_id'], $product_id, $rating, $comment, $is_verified]);
                
                if ($is_verified) {
                    $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 5 WHERE id = ?")->execute([$_SESSION['user_id']]);
                }
            }

            // Recalculate Bayesian Rating
            recalculate_bayesian_rating($pdo, 'product', $product_id);
            $success = "نظر شما با موفقیت ثبت شد." . ($is_verified ? " (+۵ امتیاز وفاداری به کیف پول شما افزوده شد)" : "");
        }
    }
}

// Get reviews with verified buyer status
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.target_type = 'product' AND r.target_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bayesian Score & Count
$stats = recalculate_bayesian_rating($pdo, 'product', $product_id);
$avg_rating = $stats['rating'];
$review_count = $stats['review_count'];

// Fetch Related / Recommended Products (Smart Tag & Category matching + Always Random)
$target_animal = $product['target_animal'] ?? 'all';
$pharmacy_tag  = $product['pharmacy_tag'] ?? null;
$category      = $product['category'] ?? '';

$conditions = ["id != ?"];
$params = [$product_id];

$tag_or = [];
if (!empty($pharmacy_tag)) {
    $tag_or[] = "pharmacy_tag = ?";
    $params[] = $pharmacy_tag;
}
if (!empty($category)) {
    $tag_or[] = "category = ?";
    $params[] = $category;
}
if (!empty($target_animal) && $target_animal !== 'all') {
    $tag_or[] = "(target_animal = ? OR target_animal = 'all')";
    $params[] = $target_animal;
}

if (!empty($tag_or)) {
    $conditions[] = "(" . implode(" OR ", $tag_or) . ")";
}

$where_clause = implode(" AND ", $conditions);
$rec_stmt = $pdo->prepare("SELECT * FROM products WHERE {$where_clause} ORDER BY RAND() LIMIT 4");
$rec_stmt->execute($params);
$related_products = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($related_products) < 4) {
    $exclude_ids = array_merge([$product_id], array_column($related_products, 'id'));
    $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
    $needed = 4 - count($related_products);
    $rec_fallback = $pdo->prepare("SELECT * FROM products WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT $needed");
    $rec_fallback->execute($exclude_ids);
    $fallback_items = $rec_fallback->fetchAll(PDO::FETCH_ASSOC);
    $related_products = array_merge($related_products, $fallback_items);
}

// Animal names map
$animal_fa_map = [
    'dog' => 'سگ',
    'cat' => 'گربه',
    'horse' => 'اسب',
    'cow' => 'گاو و دام',
    'chick' => 'جوجه و طیور',
    'all' => 'همه حیوانات'
];

$animal_display = $animal_fa_map[$product['target_animal'] ?? 'all'] ?? 'عمومی';

$is_autoship = !empty($product['is_autoship']);
$autoship_discount = $product['autoship_discount'] ?? 10;
$base_price = $product['discount_price'] ?? $product['price'];
$autoship_price = round($base_price * (100 - $autoship_discount) / 100);
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 lg:py-12 px-margin-desktop min-h-[70vh]">
    
    <!-- Breadcrumb -->
    <div class="text-label-sm text-on-surface-variant mb-8 flex items-center gap-2">
        <a href="index.php" class="hover:underline">خانه</a> > 
        <a href="shop.php" class="hover:underline">فروشگاه</a> > 
        <?php if(!empty($product['target_animal'])): ?>
            <a href="shop.php?animal=<?php echo $product['target_animal']; ?>" class="hover:underline"><?php echo $animal_display; ?></a> >
        <?php endif; ?>
        <span class="text-on-surface font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Product Main Card -->
    <div class="bg-white rounded-[3rem] p-6 lg:p-12 shadow-xl border border-outline-variant/20 flex flex-col lg:flex-row gap-12 mb-16">
        
        <!-- Product Image -->
        <div class="lg:w-5/12 flex flex-col gap-4">
            <div class="aspect-square bg-surface-container-lowest rounded-3xl overflow-hidden relative border border-outline-variant/30 shadow-inner group">
                <?php if($product['discount_price']): ?>
                    <div class="absolute top-6 left-6 bg-secondary-container text-white text-xs px-3.5 py-1 rounded-full z-10 font-bold shadow-md">تخفیف ویژه</div>
                <?php endif; ?>
                
                <?php if($is_autoship): ?>
                    <div class="absolute top-6 right-6 bg-primary-container text-white text-xs px-3.5 py-1 rounded-full z-10 font-bold shadow-md flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">autorenew</span>
                        پشتیبانی از Autoship
                    </div>
                <?php endif; ?>

                <img loading="lazy" src="<?php echo htmlspecialchars($product['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            
            <!-- Quick Features Banner -->
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-primary text-xl mb-1">verified_user</span>
                    <p class="text-[11px] font-bold text-on-surface">ضمانت اصالت دارو</p>
                </div>
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-secondary-container text-xl mb-1">local_shipping</span>
                    <p class="text-[11px] font-bold text-on-surface">ارسال به سراسر کشور</p>
                </div>
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-status-active text-xl mb-1">support_agent</span>
                    <p class="text-[11px] font-bold text-on-surface">مشاوره دارویی</p>
                </div>
            </div>
        </div>

        <!-- Product Info & Purchase Form -->
        <div class="lg:w-7/12 flex flex-col justify-between">
            <div>
                <!-- Category & Species Tags -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full"><?php echo htmlspecialchars($product['category']); ?></span>
                    <span class="text-xs font-bold text-secondary bg-secondary-container/15 px-3 py-1 rounded-full">گونه: <?php echo $animal_display; ?></span>
                    <?php if(!empty($product['brand'])): ?>
                    <span class="text-xs font-bold text-on-surface-variant bg-surface-container-high px-3 py-1 rounded-full">برند: <?php echo htmlspecialchars($product['brand']); ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold text-on-surface mb-4 leading-snug"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Ratings Summary -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-outline-variant/30">
                    <div class="flex items-center text-status-warning font-bold">
                        <span class="material-symbols-outlined text-[22px] star-rating-filled">star</span>
                        <span class="mr-1 mt-0.5 text-base"><?php echo $avg_rating; ?></span>
                    </div>
                    <span class="text-on-surface-variant text-xs">(<?php echo count($reviews); ?> دیدگاه ثبت شده کاربران)</span>
                    <span class="text-outline-variant">•</span>
                    <span class="text-xs <?php echo $product['stock'] > 0 ? 'text-status-active font-bold' : 'text-error font-bold'; ?>">
                        <?php echo $product['stock'] > 0 ? 'موجود در انبار (' . $product['stock'] . ' عدد)' : 'ناموجود'; ?>
                    </span>
                </div>

                <!-- Description -->
                <div class="mb-8 text-sm lg:text-base text-on-surface-variant leading-relaxed bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/20">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?? 'توضیحات و دستور مصرف دارویی برای این محصول ثبت شده است.')); ?>
                </div>

                <!-- Autoship Option Selector Box (Page 6) -->
                <?php if($is_autoship): ?>
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-5 rounded-2xl border-2 border-secondary-container/30 mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container text-2xl animate-spin" style="animation-duration: 10s;">autorenew</span>
                            <div>
                                <span class="text-sm font-bold text-primary block">خرید اشتراکی با تحویل خودکار (Autoship)</span>
                                <span class="text-xs text-on-surface-variant">تخفیف مداوم <?php echo $autoship_discount; ?>٪ و تحویل سروقت در بازه دلخواه</span>
                            </div>
                        </div>
                        <span class="bg-secondary-container text-white text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            <?php echo number_format($autoship_price); ?> تومان
                        </span>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 text-xs pt-2 border-t border-secondary-container/20">
                        <label class="flex items-center gap-1.5 cursor-pointer font-bold text-primary">
                            <input type="radio" name="purchase_type" value="one_time" checked class="text-primary focus:ring-primary">
                            خرید معمولی یک‌باره
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer font-bold text-secondary-container">
                            <input type="radio" name="purchase_type" value="autoship" class="text-secondary-container focus:ring-secondary-container">
                            ارسال دوره‌ای خودکار (با <?php echo $autoship_discount; ?>٪ تخفیف)
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Price and Action Buttons -->
            <div class="mt-4 pt-6 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex flex-col">
                    <?php if($product['discount_price']): ?>
                        <span class="text-sm text-on-surface-variant line-through mb-1"><?php echo number_format($product['price']); ?> تومان</span>
                        <span class="text-3xl font-bold text-primary"><?php echo number_format($product['discount_price']); ?> <span class="text-sm font-normal">تومان</span></span>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-primary"><?php echo number_format($product['price']); ?> <span class="text-sm font-normal">تومان</span></span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>)" class="flex-1 sm:flex-initial bg-primary text-white px-10 py-4 rounded-2xl font-bold flex items-center justify-center gap-3 hover:bg-primary-container transition-all shadow-xl shadow-primary/20 hover:-translate-y-0.5 active:scale-95">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        افزودن به سبد خرید
                    </button>
                    
                    <?php if($is_autoship): ?>
                    <a href="subscriptions.php" class="bg-secondary-container text-white px-5 py-4 rounded-2xl font-bold hover:bg-[#ea580c] transition-all shadow-md flex items-center justify-center" title="پلن‌های ارسال خودکار">
                        <span class="material-symbols-outlined">autorenew</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SMART RELATED PRODUCTS (Page 6 Recommendation System)                    -->
    <!-- ========================================================================= -->
    <?php if(!empty($related_products)): ?>
    <section class="mb-16">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">recommend</span>
                <div>
                    <h2 class="text-2xl font-bold text-primary">محصولات و داروهای مرتبط</h2>
                    <p class="text-xs text-on-surface-variant">پیشنهادات تخصصی برای <?php echo $animal_display; ?></p>
                </div>
            </div>
            <a href="shop.php?animal=<?php echo $product['target_animal'] ?? ''; ?>" class="text-xs font-bold text-primary hover:underline">مشاهده همه محصولات این دسته ></a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($related_products as $rel): ?>
            <div class="bg-white rounded-3xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-outline-variant/15 flex flex-col justify-between group">
                <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 overflow-hidden relative">
                    <img loading="lazy" src="<?php echo htmlspecialchars($rel['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                </div>
                <div>
                    <span class="text-[11px] text-on-surface-variant font-bold"><?php echo htmlspecialchars($rel['brand'] ?? 'آسنا'); ?></span>
                    <a href="product_details.php?id=<?php echo $rel['id']; ?>">
                        <h3 class="text-sm font-bold text-on-surface mb-3 line-clamp-2 hover:text-primary transition-colors cursor-pointer">
                            <?php echo htmlspecialchars($rel['name']); ?>
                        </h3>
                    </a>
                    <div class="flex items-center justify-between pt-2 border-t border-outline-variant/20">
                        <span class="text-sm font-bold text-primary"><?php echo number_format($rel['discount_price'] ?? $rel['price']); ?> ت</span>
                        <button type="button" onclick="addToCart(this, <?php echo $rel['id']; ?>)" class="bg-surface-container-low hover:bg-primary hover:text-white text-primary p-2 rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reviews Section -->
    <section class="max-w-4xl mx-auto bg-white rounded-3xl p-8 shadow-sm border border-outline-variant/20">
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-outline-variant/20">
            <h2 class="text-xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">reviews</span>
                دیدگاه‌ها و تجربیات مصرف‌کنندگان
            </h2>
            <div class="flex flex-col items-end">
                <div class="flex items-center gap-2 text-status-warning font-bold text-sm">
                    <span class="material-symbols-outlined text-[20px] star-rating-filled">star</span>
                    <span><?php echo $avg_rating; ?> از ۵</span>
                </div>
                <span class="text-[11px] text-on-surface-variant">
                    <?php if($review_count > 0): ?>
                        (بر اساس <?php echo $review_count; ?> نظر خریداران)
                    <?php else: ?>
                        (امتیاز کارشناسی آسنا)
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="bg-status-active/10 text-status-active p-4 rounded-xl mb-6 font-bold text-sm border border-status-active/20"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="bg-error/10 text-error p-4 rounded-xl mb-6 font-bold text-sm border border-error/20"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Review Form -->
        <div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/30 mb-8">
            <?php if(isset($_SESSION['user_id'])): ?>
            <h3 class="font-bold text-on-surface mb-4 text-sm">ثبت دیدگاه یا تجربه مصرف دارو</h3>
            <form action="product_details.php?id=<?php echo $product_id; ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="submit_review">
                
                <div>
                    <label class="block text-xs font-bold mb-2">امتیاز شما به این محصول:</label>
                    <div class="flex flex-row-reverse justify-end gap-1 rating-stars">
                        <input type="radio" name="rating" value="5" id="star5" class="hidden" required><label for="star5" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="4" id="star4" class="hidden"><label for="star4" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="3" id="star3" class="hidden"><label for="star3" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="2" id="star2" class="hidden"><label for="star2" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="1" id="star1" class="hidden"><label for="star1" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-2">متن نظر یا تجربه اثرگذاری:</label>
                    <textarea name="comment" rows="3" required class="w-full border border-outline-variant/50 rounded-xl p-3 focus:ring-2 focus:ring-primary outline-none text-sm resize-none bg-white" placeholder="تجربه خود از مصرف یا ویژگی‌های این محصول را بنویسید..."></textarea>
                </div>
                
                <button type="submit" class="bg-primary text-white px-8 py-3 rounded-xl font-bold hover:bg-primary-container transition-all text-xs">ثبت دیدگاه</button>
            </form>
            <style>
                .rating-stars label:hover,
                .rating-stars label:hover ~ label,
                .rating-stars input:checked ~ label {
                    color: #f59e0b;
                    font-variation-settings: 'FILL' 1;
                }
            </style>
            <?php else: ?>
                <div class="text-center py-6">
                    <p class="text-on-surface-variant font-bold mb-3 text-sm">برای ثبت نظر، ابتدا باید وارد حساب کاربری خود شوید.</p>
                    <a href="login.php" class="inline-block bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-xs">ورود به حساب</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews List -->
        <div class="space-y-4">
            <?php if(empty($reviews)): ?>
                <p class="text-center text-on-surface-variant py-8 border border-outline-variant/30 rounded-2xl border-dashed text-xs">هنوز دیدگاهی ثبت نشده است. اولین نفری باشید که نظر خود را به اشتراک می‌گذارد!</p>
            <?php else: ?>
                <?php foreach($reviews as $rev): ?>
                    <div class="bg-surface-container-low/50 p-5 rounded-2xl border border-outline-variant/20">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                    <?php echo mb_substr($rev['user_name'] ?? 'ک', 0, 1, 'UTF-8'); ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-on-surface text-xs"><?php echo htmlspecialchars($rev['user_name'] ?? 'کاربر آسنا'); ?></h4>
                                    <p class="text-[10px] text-on-surface-variant"><?php echo date('Y/m/d', strtotime($rev['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center text-status-warning">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span class="material-symbols-outlined text-xs <?php echo $i <= $rev['rating'] ? 'star-rating-filled' : 'star-rating-empty'; ?>">star</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="text-xs text-on-surface-variant leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function addToCart(btn, productId) {
    if(window.event) window.event.preventDefault();
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> در حال افزودن...';
    btn.disabled = true;

    // Check purchase type (one_time or autoship)
    const selectedRadio = document.querySelector('input[name="purchase_type"]:checked');
    const purchaseType = selectedRadio ? selectedRadio.value : 'standard';
    
    let postBody = 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId;
    if (purchaseType === 'autoship') {
        postBody += '&type=autoship&frequency=1_month';
    }
    
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: postBody
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">check_circle</span> اضافه شد';
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
</script>

<!-- Schema.org JSON-LD Structured Data for Google Rich Snippets (Product, Offer, Rating) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": <?php echo json_encode($product['name']); ?>,
  "image": [
    <?php echo json_encode($product['image_url'] ?: 'assets/images/pharma-default.svg'); ?>
  ],
  "description": <?php echo json_encode(strip_tags($product['description'] ?? $product['name'])); ?>,
  "brand": {
    "@type": "Brand",
    "name": <?php echo json_encode($product['brand'] ?? 'داروخانه آسنا'); ?>
  },
  "category": <?php echo json_encode($product['category'] ?? 'دامپزشکی'); ?>,
  "offers": {
    "@type": "Offer",
    "url": <?php echo json_encode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>,
    "priceCurrency": "IRR",
    "price": "<?php echo ($product['discount_price'] ?: $product['price']) * 10; ?>",
    "priceValidUntil": "<?php echo date('Y-12-31'); ?>",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "<?php echo ($product['stock'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'; ?>",
    "seller": {
      "@type": "Pharmacy",
      "name": "داروخانه آنلاین و تخصصی آسنا"
    }
  }<?php if(!empty($product['rating_cache']) && $product['rating_cache'] > 0): ?>,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $product['rating_cache']; ?>",
    "reviewCount": "<?php echo max(1, (int)($product['review_count_cache'] ?? count($reviews))); ?>"
  }
  <?php endif; ?>
}
</script>

<?php include 'includes/footer.php'; ?>
