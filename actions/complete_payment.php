<?php
require_once '../includes/db.php';
require_once '../includes/gateway.php';

// ── Gate 1: Must arrive via GET callback from ZarinPal ───────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../cart.php');
    exit;
}

$authority = trim($_GET['Authority'] ?? '');
$status    = trim($_GET['Status'] ?? '');

// ── Gate 2: Gateway reported failure ──────────────────────────────────────────
if ($status !== 'OK' || empty($authority)) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'پرداخت لغو شد یا با خطا مواجه گردید.';
    header('Location: ../profile.php');
    exit;
}

// ── Gate 3: Authority must match session — prevents authority injection ────────
$pending = $_SESSION['pending_order'] ?? null;
$is_booking = ($pending['type'] ?? '') === 'booking';
$is_subscription = ($pending['type'] ?? '') === 'subscription';

if (!$pending
    || ($pending['authority'] ?? '') !== $authority
    || empty($pending['total_amount'])
    || (!$is_booking && !$is_subscription && empty($pending['items']))
) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'اطلاعات سفارش نامعتبر یا منقضی شده است.';
    header('Location: ../cart.php');
    exit;
}

// ── Gate 4: Server-to-server verification with ZarinPal ──────────────────────
$gateway    = new ZarinPalGateway();
$verified   = $gateway->verifyPayment((int)$pending['total_amount'], $authority);

if (!$verified['success']) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'تأییدیه پرداخت از درگاه دریافت نشد: ' . $verified['error'];
    header('Location: ../cart.php');
    exit;
}

$ref_id = $verified['ref_id'];

// ── Verified — commit atomically ──────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $user_id      = (int)$_SESSION['user_id'];
    $total_amount = (int)$pending['total_amount'];
    $items        = $pending['items'] ?? [];

    if ($is_subscription) {
        $months = intval($pending['plan_id'] ?? 1);
        if ($months < 1) $months = 1;

        $subStmt = $pdo->prepare(
            "INSERT INTO user_subscriptions (user_id, plan_name, amount, status, next_delivery_date)
             VALUES (?, ?, ?, 'active', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY))"
        );
        $subStmt->execute([$user_id, $pending['plan_name'], $total_amount]);
        $sub_id = $pdo->lastInsertId();

        $delStmt = $pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date) VALUES (?, ?, ?)");
        
        for ($i = 1; $i <= $months; $i++) {
            $days = 3 + (($i - 1) * 30);
            $scheduled = date('Y-m-d', strtotime("+$days days"));
            $delStmt->execute([$sub_id, $i, $scheduled]);
        }
        
        $order_id = $sub_id; // For the success message below
    } else {
        // 1. Create order with real amount and ref_id
    $orderStmt = $pdo->prepare(
        "INSERT INTO orders (user_id, total_amount, status, gateway_ref_id)
         VALUES (?, ?, 'processing', ?)"
    );
    // If gateway_ref_id column doesn't exist yet, fall back gracefully
    try {
        $orderStmt->execute([$user_id, $total_amount, $ref_id]);
    } catch (PDOException $colErr) {
        $orderStmt = $pdo->prepare(
            "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'processing')"
        );
        $orderStmt->execute([$user_id, $total_amount]);
    }
    $order_id = $pdo->lastInsertId();

    // 2. Insert order_items and decrement stock
    if (!$is_booking && !$is_subscription) {
        $itemStmt  = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, product_name_snapshot)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stockStmt = $pdo->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?"
        );

        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['qty'];

            $checkStmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $checkStmt->execute([$pid]);
            $current_stock = (int)$checkStmt->fetchColumn();

            if ($current_stock < $qty) {
                throw new RuntimeException(
                    "محصول «{$item['product_name_snapshot']}» موجودی کافی ندارد (موجود: {$current_stock})."
                );
            }

            $itemStmt->execute([
                $order_id, $pid, $qty,
                (int)$item['unit_price'],
                $item['product_name_snapshot'],
            ]);
            $stockStmt->execute([$qty, $pid, $qty]);
        }
        }
    } // End of else block for regular orders

    // 3. Loyalty points and booking approval
    if ($is_booking && !empty($pending['booking_id'])) {
        $pdo->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?")
            ->execute([$pending['booking_id']]);
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 20 WHERE id = ?")
            ->execute([$user_id]);
            
        // Send SMS Booking Confirmation
        require_once '../includes/SmsService.php';
        $u_stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $u_stmt->execute([$user_id]);
        $u = $u_stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['phone'])) {
            $a_stmt = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE id = ?");
            $a_stmt->execute([$pending['booking_id']]);
            $appt = $a_stmt->fetch(PDO::FETCH_ASSOC);
            if ($appt) {
                $sms = new SmsService();
                $sms->sendBookingConfirmation($u['phone'], $appt['appointment_date'], $appt['appointment_time']);
            }
        }
    } else {
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 50 WHERE id = ?")
            ->execute([$user_id]);
    }

    $pdo->commit();

    // 4. Clean up session
    unset($_SESSION['cart'], $_SESSION['pending_order']);
    
    if ($is_subscription) {
        $_SESSION['profile_success'] =
            "پرداخت موفق! اشتراک «{$pending['plan_name']}» با موفقیت فعال شد. کد رهگیری: {$ref_id}";
    } else {
        $_SESSION['profile_success'] =
            "پرداخت موفق! سفارش #PC-{$order_id} ثبت شد. کد رهگیری: {$ref_id}";
    }
    header('Location: ../profile.php');
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = $e->getMessage();
    header('Location: ../cart.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Payment commit error [{$ref_id}]: " . $e->getMessage());
    $_SESSION['profile_error'] =
        'خطای سیستمی در ثبت سفارش. مبلغ کسر شده با کد رهگیری ' . $ref_id . ' قابل استرداد است.';
    header('Location: ../cart.php');
    exit;
}
