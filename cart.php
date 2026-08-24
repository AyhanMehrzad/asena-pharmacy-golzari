<?php
require_once 'includes/header.php';

$cart_items = $_SESSION['cart'] ?? [];
$products = [];
$total_price = 0;
$total_discount = 0;

if (!empty($cart_items)) {
    // Get all product IDs from cart
    $ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($db_products as $prod) {
        $qty = $cart_items[$prod['id']];
        $prod['qty'] = $qty;
        
        $price = $prod['price'];
        $discount_price = $prod['discount_price'] ? $prod['discount_price'] : $price;
        
        $total_price += $price * $qty;
        $total_discount += ($price - $discount_price) * $qty;
        
        $products[] = $prod;
    }
}

$final_price = $total_price - $total_discount;
?>

<main class="max-w-container-max mx-auto overflow-hidden py-16 px-margin-desktop min-h-[70vh]">
    <div class="mb-12">
        <h1 class="text-display-lg text-primary mb-4">سبد خرید شما</h1>
        <p class="text-body-lg text-on-surface-variant">محصولات انتخاب شده جهت تسویه حساب</p>
    </div>

    <?php if(isset($_SESSION['profile_error'])): ?>
        <a href="profile_settings.php" class="block bg-error/10 text-error p-4 rounded-xl mb-8 font-bold text-sm border border-error/20 flex items-center gap-2 hover:bg-error/20 transition-colors cursor-pointer group">
            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">error</span>
            <?php echo $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?>
            <span class="material-symbols-outlined mr-auto">chevron_left</span>
        </a>
    <?php endif; ?>
    <?php if(isset($_SESSION['profile_success'])): ?>
        <div class="bg-status-active/10 text-status-active p-4 rounded-xl mb-8 font-bold text-sm border border-status-active/20 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?php echo $_SESSION['profile_success']; unset($_SESSION['profile_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="text-center py-20 bg-white rounded-3xl border border-outline-variant/30">
            <span class="material-symbols-outlined text-6xl text-primary/30 mb-4">shopping_cart</span>
            <p class="text-title-lg font-bold text-on-surface-variant mb-6">سبد خرید شما خالی است!</p>
            <a href="shop.php" class="bg-primary text-white px-8 py-3 rounded-xl font-bold btn-premium inline-block">مشاهده فروشگاه</a>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Cart Items -->
            <div class="lg:w-2/3 flex flex-col gap-6">
                <?php foreach($products as $prod): ?>
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30 flex flex-col sm:flex-row items-center gap-6 relative group">
                    <div class="w-32 h-32 bg-surface-container-lowest rounded-2xl overflow-hidden shrink-0">
                        <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" class="w-full h-full object-cover" alt="Product Image">
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-title-lg font-bold text-on-surface"><?php echo htmlspecialchars($prod['name']); ?></h3>
                            <form action="actions/cart_action.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="text-error hover:bg-error/10 p-2 rounded-lg transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                        <p class="text-label-sm text-on-surface-variant mb-4"><?php echo htmlspecialchars($prod['category']); ?></p>
                        
                        <div class="flex justify-between items-center w-full">
                            <div class="flex items-center gap-4 bg-surface-container rounded-xl p-2">
                                <form action="actions/cart_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-primary transition-colors cursor-pointer"><span class="material-symbols-outlined text-sm">remove</span></button>
                                </form>
                                
                                <span class="font-bold w-6 text-center"><?php echo $prod['qty']; ?></span>
                                
                                <form action="actions/cart_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm hover:text-primary transition-colors cursor-pointer"><span class="material-symbols-outlined text-sm">add</span></button>
                                </form>
                            </div>
                            <span class="text-title-lg font-bold text-primary">
                                <?php 
                                $disp = $prod['discount_price'] ? $prod['discount_price'] : $prod['price'];
                                echo number_format($disp * $prod['qty']); 
                                ?> تومان
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="lg:w-1/3">
                <div class="bg-surface-container-low rounded-[2rem] p-8 sticky top-32">
                    <h3 class="text-headline-md text-primary mb-8 border-b border-outline-variant/20 pb-4">خلاصه سفارش</h3>
                    
                    <div class="flex flex-col gap-4 mb-8">
                        <div class="flex justify-between items-center text-body-lg">
                            <span class="text-on-surface-variant">مبلغ کل (<?php echo count($products); ?> کالا)</span>
                            <span class="font-bold"><?php echo number_format($total_price); ?> تومان</span>
                        </div>
                        <?php if($total_discount > 0): ?>
                        <div class="flex justify-between items-center text-body-lg text-secondary">
                            <span>سود شما از خرید</span>
                            <span class="font-bold"><?php echo number_format($total_discount); ?> تومان</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center text-body-lg">
                            <span class="text-on-surface-variant">هزینه ارسال</span>
                            <span class="text-status-active font-bold">رایگان</span>
                        </div>
                    </div>
                    
                    <div class="border-t border-outline-variant/20 pt-6 mb-8 flex justify-between items-center">
                        <span class="text-title-lg font-bold">مبلغ قابل پرداخت</span>
                        <span class="text-headline-md font-bold text-primary"><?php echo number_format($final_price); ?> تومان</span>
                    </div>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="payment.php" class="w-full bg-primary text-white py-5 rounded-2xl font-bold flex justify-center items-center gap-3 btn-premium hover:bg-primary-container hover:shadow-xl text-label-lg transition-all">
                            تکمیل خرید و پرداخت
                            <span class="material-symbols-outlined">arrow_left_alt</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="w-full bg-secondary-container text-on-secondary-container py-5 rounded-2xl font-bold flex justify-center items-center gap-3 btn-premium hover:bg-secondary-container/80 text-label-lg transition-all">
                            برای پرداخت وارد شوید
                            <span class="material-symbols-outlined">person</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
