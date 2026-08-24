<?php
require_once '../includes/db.php';

// Route Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$adminCheck = $stmt->fetch();
if (!$adminCheck || $adminCheck['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fetch today's orders or all pending/processing orders
$stmt = $pdo->prepare("
    SELECT o.id, u.name as user_name, u.phone, o.shipping_address, o.total_amount, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE DATE(o.created_at) = CURDATE() OR o.status = 'processing'
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send Telegram Notification
$botToken = 'YOUR_TELEGRAM_BOT_TOKEN_HERE';
$chatId = 'YOUR_ADMIN_CHAT_ID_HERE'; // The admin's chat ID

$shipmentCount = count($orders);
if ($shipmentCount > 0 && $botToken !== 'YOUR_TELEGRAM_BOT_TOKEN_HERE') {
    $message = "📦 *گزارش ارسال‌های امروز*\n\n";
    $message .= "تعداد سفارشات برای پردازش و ارسال: *" . $shipmentCount . "* سفارش\n";
    $message .= "لطفاً پنل مدیریت را برای جزئیات بررسی کنید.";
    
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    // We suppress errors so downloading CSV isn't blocked if telegram fails
    @file_get_contents($url . '?' . http_build_query($data));
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="todays_shipments_' . date('Y-m-d') . '.csv"');
// Fix for Persian characters in Excel (BOM)
echo "\xEF\xBB\xBF"; 

$output = fopen('php://output', 'w');

// Output column headings
fputcsv($output, ['ID سفارش', 'نام مشتری', 'شماره تماس', 'آدرس ارسال', 'مبلغ کل (تومان)', 'وضعیت', 'تاریخ ثبت']);

foreach ($orders as $order) {
    // Translate status for the report
    $status_fa = $order['status'];
    switch ($order['status']) {
        case 'pending_payment': $status_fa = 'در انتظار پرداخت'; break;
        case 'processing': $status_fa = 'در حال پردازش'; break;
        case 'shipped': $status_fa = 'ارسال شده'; break;
        case 'delivered': $status_fa = 'تحویل شده'; break;
        case 'cancelled': $status_fa = 'لغو شده'; break;
    }
    
    fputcsv($output, [
        $order['id'],
        $order['user_name'],
        $order['phone'],
        $order['shipping_address'] ?? 'ندارد',
        $order['total_amount'],
        $status_fa,
        $order['created_at']
    ]);
}

fclose($output);
exit;
?>
