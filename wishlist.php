<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT p.* FROM products p
    JOIN wishlist w ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="max-w-container-max mx-auto overflow-hidden py-12 px-margin-desktop min-h-[70vh]">
    <div class="flex items-center gap-4 mb-8">
        <span class="material-symbols-outlined text-4xl text-primary">favorite</span>
        <h1 class="text-display-md text-on-surface">علاقه‌مندی‌های من</h1>
    </div>

    <?php if(count($products) > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach($products as $product): ?>
        <div class="bg-white rounded-3xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
            <!-- Wishlist Button -->
            <button type="button" onclick="removeFromWishlist(this, <?php echo $product['id']; ?>)" class="absolute top-6 right-6 z-10 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-error hover:bg-error/10 transition-colors shadow-sm">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">favorite</span>
            </button>

            <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-6 overflow-hidden relative">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center z-20">
                    <button type="button" onclick="window.location.href='actions/cart_action.php?action=add&product_id=<?php echo $product['id']; ?>'" class="bg-primary text-white w-full py-3 rounded-xl font-bold flex justify-center items-center gap-2 hover:bg-primary-container">
                        <span class="material-symbols-outlined">add_shopping_cart</span>
                        افزودن به سبد
                    </button>
                </div>
                
                <!-- Mobile Quick Add to Cart -->
                <button type="button" onclick="window.location.href='actions/cart_action.php?action=add&product_id=<?php echo $product['id']; ?>'" class="lg:hidden absolute bottom-4 left-4 z-30 w-10 h-10 bg-primary/90 backdrop-blur-md text-white rounded-full flex items-center justify-center shadow-lg active:scale-95 transition-transform border border-white/20">
                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                </button>
            </div>
            <div class="flex-1 flex flex-col">
                <p class="text-label-sm text-on-surface-variant mb-1"><?php echo htmlspecialchars($product['category']); ?></p>
                <h3 class="text-title-lg font-bold text-on-surface mb-4 line-clamp-2 hover:text-primary transition-colors cursor-pointer"><?php echo htmlspecialchars($product['name']); ?></h3>
                <div class="mt-auto flex justify-between items-center">
                    <div class="flex flex-col">
                        <?php if($product['discount_price']): ?>
                        <span class="text-label-sm text-on-surface-variant line-through mb-1"><?php echo number_format($product['price']); ?> تومان</span>
                        <span class="text-title-lg font-bold text-primary"><?php echo number_format($product['discount_price']); ?> تومان</span>
                        <?php else: ?>
                        <span class="text-title-lg font-bold text-primary"><?php echo number_format($product['price']); ?> تومان</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="text-center py-20 text-on-surface-variant bg-surface-container-lowest border border-outline-variant/30 rounded-xl">
            <span class="material-symbols-outlined text-6xl mb-4 opacity-50">heart_broken</span>
            <p class="text-title-lg font-bold">لیست علاقه‌مندی‌های شما خالی است.</p>
            <a href="shop.php" class="bg-primary text-white px-8 py-3 rounded-xl inline-block mt-6 hover:bg-primary-container transition-colors">بازدید از فروشگاه</a>
        </div>
    <?php endif; ?>
</main>

<script>
function removeFromWishlist(btn, productId) {
    if(!confirm('آیا از حذف این محصول از علاقه‌مندی‌ها اطمینان دارید؟')) return;
    
    fetch('actions/wishlist_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Remove the card from the DOM
            btn.closest('div.bg-white').remove();
            
            // Check if grid is empty
            const grid = document.querySelector('.grid');
            if (grid && grid.children.length === 0) {
                location.reload(); // reload to show empty state
            }
        } else {
            alert(data.message || 'خطایی رخ داد.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
