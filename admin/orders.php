<?php
$currentPage = 'orders';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    csrf_verify();
    
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $allowed_statuses = ['pending_payment', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses) && $order_id > 0) {
        // Fetch current status
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $old_status = $stmt->fetchColumn();
        
        if ($old_status !== $new_status) {
            $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($updateStmt->execute([$new_status, $order_id])) {
                $logStmt = $pdo->prepare("INSERT INTO order_logs (order_id, old_status, new_status) VALUES (?, ?, ?)");
                $logStmt->execute([$order_id, $old_status, $new_status]);
            }
        }
    }
    header("Location: orders.php");
    exit;
}

// Fetch orders
$stmt = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach items to orders
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($order_ids), '?'));
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.image_url, p.category, p.brand, p.target_animal, p.pharmacy_tag, p.is_autoship 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id IN ($ph)
    ");
    $itemsStmt->execute($order_ids);
    $all_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    $items_by_order = [];
    foreach ($all_items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
    foreach ($orders as &$order) {
        $order['items'] = $items_by_order[$order['id']] ?? [];
    }
    unset($order);
}

// Fetch order logs
$stmt = $pdo->query("SELECT * FROM order_logs ORDER BY changed_at DESC");
$all_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$logs_by_order = [];
foreach($all_logs as $log) {
    $logs_by_order[$log['order_id']][] = $log;
}

// Helper to translate status
function translate_status($status) {
    $map = [
        'pending_payment' => 'در انتظار پرداخت',
        'processing' => 'در حال پردازش',
        'shipped' => 'در مسیر ارسال',
        'delivered' => 'تحویل شده',
        'cancelled' => 'لغو شده'
    ];
    return $map[$status] ?? $status;
}

// Date Formatter for Jalali
$fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
?>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Header Section -->
    <header class="flex justify-between items-center mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary">مدیریت سفارشات</h2>
            <p class="text-on-surface-variant font-body-md mt-1">پیگیری سفارشات، پرداخت‌ها و ارسال‌ها</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="subscriptions.php?filter=today" class="flex items-center gap-2 bg-primary text-white px-5 py-2 rounded-xl font-label-lg font-bold shadow-sm hover:bg-primary-container transition-all text-xs">
                <span class="material-symbols-outlined text-base">local_shipping</span>
                تقویم نوبت‌های ارسال
            </a>
            <a href="export_orders.php" class="flex items-center gap-2 bg-secondary-container text-white px-5 py-2 rounded-xl font-label-lg font-bold shadow-sm hover:opacity-90 transition-opacity text-xs">
                <span class="material-symbols-outlined text-base">download</span>
                خروجی CSV
            </a>
        </div>
    </header>

    <!-- Orders Table -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden border border-outline-variant/30">
        <div class="p-6 border-b border-outline-variant bg-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="font-title-lg text-title-lg text-primary">لیست تمامی سفارشات</h3>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-lowest border-b border-outline-variant">
                    <tr>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">شناسه سفارش</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">مشتری</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">مبلغ کل (تومان)</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">وضعیت</th>
                        <th class="px-6 py-4 font-label-lg text-on-surface-variant">تاریخ ثبت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-on-surface-variant">هیچ سفارشی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $order): ?>
                            <td class="px-6 py-4 font-bold text-primary align-top" dir="ltr">
                                <div>#PC-<?= $order['id'] ?></div>
                                <?php if (!empty($order['items'])): ?>
                                    <div class="mt-2 space-y-1 text-right" dir="rtl">
                                        <?php foreach($order['items'] as $item): ?>
                                            <?php 
                                                $is_pharma = (str_contains($item['category'] ?? '', 'دارو') || str_contains($item['category'] ?? '', 'مکمل') || !empty($item['pharmacy_tag']));
                                                $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : '../assets/images/toy-mouse.jpg';
                                            ?>
                                            <div class="flex items-center gap-2 bg-surface-container-low p-1.5 rounded-lg border border-outline-variant/30 text-xs font-normal">
                                                <img src="<?= $img ?>" class="w-8 h-8 rounded-md object-cover bg-white shrink-0 border border-outline-variant/40">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <?php if($is_pharma): ?>
                                                            <span class="bg-secondary-container/15 text-secondary-container px-1 py-0.2 rounded text-[9px] font-bold">💊 دارو</span>
                                                        <?php else: ?>
                                                            <span class="bg-primary/10 text-primary px-1 py-0.2 rounded text-[9px] font-bold">🛍️ پت‌شاپ</span>
                                                        <?php endif; ?>
                                                        <?php if(!empty($item['is_autoship'])): ?>
                                                            <span class="bg-status-active/10 text-status-active px-1 py-0.2 rounded text-[9px] font-bold">🔄 Autoship</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-[11px] font-bold text-primary truncate"><?= htmlspecialchars($item['product_name_snapshot']) ?></p>
                                                    <p class="text-[10px] text-on-surface-variant"><?= $item['quantity'] ?> عدد × <?= number_format($item['price_at_purchase']) ?> ت</p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant align-top font-bold"><?= htmlspecialchars($order['user_name']) ?></td>
                            <td class="px-6 py-4 font-bold align-top"><?= number_format($order['total_amount']) ?></td>
                            <td class="px-6 py-4">
                                <form action="orders.php" method="POST" class="m-0 p-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" data-current="<?= $order['status'] ?>" onchange="if(confirm('آیا از تغییر وضعیت این سفارش اطمینان دارید؟')){ this.form.submit(); } else { this.value = this.getAttribute('data-current'); }" class="border border-outline-variant/30 rounded-md py-1 px-2 text-[11px] font-bold focus:outline-none focus:ring-1 focus:ring-primary <?php 
                                        switch($order['status']) {
                                            case 'pending_payment': echo 'bg-status-warning/20 text-status-warning'; break;
                                            case 'processing': echo 'bg-primary-fixed text-on-primary-fixed-variant'; break;
                                            case 'shipped': echo 'bg-secondary-fixed text-on-secondary-fixed'; break;
                                            case 'delivered': echo 'bg-status-active/20 text-status-active'; break;
                                            case 'cancelled': echo 'bg-error-container text-error'; break;
                                            default: echo 'bg-surface-variant text-on-surface-variant'; break;
                                        }
                                    ?>">
                                        <option value="pending_payment" <?= $order['status'] === 'pending_payment' ? 'selected' : '' ?>>در انتظار پرداخت</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>در حال پردازش</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>در مسیر ارسال</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>تحویل شده</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>لغو شده</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant text-sm font-body-md persian-number" dir="ltr">
                                <p><?= $fmt->format(new DateTime($order['created_at'])) ?></p>
                                <?php if(isset($logs_by_order[$order['id']])): ?>
                                    <div class="mt-2 text-xs flex flex-col gap-1 border-t border-outline-variant/30 pt-2 text-right w-full" dir="rtl">
                                        <p class="font-bold text-primary mb-1">تاریخچه تغییرات:</p>
                                        <?php foreach($logs_by_order[$order['id']] as $log): ?>
                                            <p class="text-on-surface-variant text-[10px] bg-surface-container-low p-1.5 rounded-lg border border-outline-variant/30">
                                                تغییر از <strong><?= translate_status($log['old_status']) ?></strong> به <strong><?= translate_status($log['new_status']) ?></strong><br>
                                                <span class="text-outline persian-number"><?= $fmt->format(new DateTime($log['changed_at'])) ?></span>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
