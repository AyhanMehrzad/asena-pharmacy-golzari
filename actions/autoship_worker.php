<?php
/**
 * AUTOSHIP WEB WORKER (Poor Man's Cron)
 * 
 * This script is triggered asynchronously by visitors on the site.
 * It checks a lock file to ensure it only processes autoships once per day.
 */

// We don't need a session or CSRF token here because this is a background worker
// that only performs safe operations based on the database state.
require_once __DIR__ . '/../includes/db.php';

$lock_file = __DIR__ . '/../uploads/.autoship_last_run';
$today = date('Y-m-d');

// Check if it has already run today
if (file_exists($lock_file)) {
    $last_run = trim(file_get_contents($lock_file));
    if ($last_run === $today) {
        // Already ran today, exit silently
        header('Content-Type: application/json');
        echo json_encode(['status' => 'skipped', 'message' => 'Already ran today']);
        exit;
    }
}

// Ensure the directory exists (it should, but just in case)
if (!is_dir(dirname($lock_file))) {
    mkdir(dirname($lock_file), 0755, true);
}

// --- TELEGRAM BOT CONFIGURATION ---
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_ADMIN_CHAT_ID', 'YOUR_CHAT_ID_HERE');

function sendTelegramMessage($message) {
    if (TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
        error_log("Telegram notification skipped: Bot token not configured.");
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_ADMIN_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

try {
    $pdo->beginTransaction();

    // Find all subscriptions that are due today or earlier and are active
    $stmt = $pdo->prepare("
        SELECT s.id as subscription_id, s.user_id, s.product_id, s.frequency_days, s.next_delivery_date, 
               p.name as product_name, p.price as product_price, p.discount_price,
               u.phone, u.name as user_name
        FROM subscriptions s
        JOIN products p ON s.product_id = p.id
        JOIN users u ON s.user_id = u.id
        WHERE s.next_delivery_date <= CURDATE() AND s.status = 'active'
    ");
    $stmt->execute();
    $due_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed_count = 0;
    $report_lines = [];

    foreach ($due_subscriptions as $sub) {
        // Calculate Autoship Price (5% off the current lowest price)
        $base_price = $sub['discount_price'] ? $sub['discount_price'] : $sub['product_price'];
        $autoship_price = $base_price * 0.95;

        // 1. Create a new pending order
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending_payment')");
        $orderStmt->execute([$sub['user_id'], $autoship_price]);
        $order_id = $pdo->lastInsertId();

        // 2. Add the item to the order
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, 1, ?)");
        $itemStmt->execute([$order_id, $sub['product_id'], $autoship_price]);

        // 3. Update the subscription's next delivery date
        $updateStmt = $pdo->prepare("UPDATE subscriptions SET next_delivery_date = DATE_ADD(next_delivery_date, INTERVAL frequency_days DAY) WHERE id = ?");
        $updateStmt->execute([$sub['subscription_id']]);

        $processed_count++;
        
        $report_lines[] = "📦 <b>Order #{$order_id}</b>\nUser: {$sub['user_name']} ({$sub['phone']})\nProduct: {$sub['product_name']}";
    }

    $pdo->commit();

    // Mark as run for today regardless of whether there were subscriptions or not,
    // so we don't keep hitting the DB on every page load.
    file_put_contents($lock_file, $today);

    // 4. Send Telegram Notification if any orders were processed
    if ($processed_count > 0) {
        $message = "🚨 <b>ASENA Autoship Report (Web Worker)</b> 🚨\n";
        $message .= "Date: " . date('Y-m-d') . "\n";
        $message .= "Total Processed: {$processed_count} subscriptions\n\n";
        $message .= implode("\n\n", $report_lines);
        
        sendTelegramMessage($message);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'processed' => $processed_count]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Autoship Worker Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal error occurred']);
}
?>
