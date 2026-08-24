<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $action = $_POST['action'] ?? '';
    $subscription_id = (int)($_POST['subscription_id'] ?? 0);
    
    // 1. Confirm Delivery (Received / Not Received)
    if ($action === 'confirm_delivery') {
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        $received = (int)($_POST['received'] ?? 0);
        $new_status = $received ? 'delivered' : 'not_received';
        
        $stmt = $pdo->prepare("
            SELECT d.id FROM subscription_deliveries d
            JOIN user_subscriptions s ON d.subscription_id = s.id
            WHERE d.id = ? AND s.user_id = ? AND d.status = 'shipped'
        ");
        $stmt->execute([$delivery_id, $user_id]);
        
        if ($stmt->fetch()) {
            $updateStmt = $pdo->prepare("UPDATE subscription_deliveries SET status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $delivery_id]);
            
            if ($received) {
                $_SESSION['profile_success'] = "تایید دریافت مرسوله با موفقیت ثبت شد. از خرید شما متشکریم!";
            } else {
                $_SESSION['profile_error'] = "گزارش عدم دریافت مرسوله ثبت شد. کارشناسان پشتیبانی آسنا پیگیری خواهند کرد.";
            }
        }
    }

    // 2. Reschedule Next Delivery Date
    if ($action === 'reschedule_delivery' && $subscription_id > 0) {
        $new_date = trim($_POST['new_date'] ?? '');
        if (!empty($new_date) && strtotime($new_date) >= strtotime(date('Y-m-d'))) {
            // Verify ownership
            $chk = $pdo->prepare("SELECT id FROM user_subscriptions WHERE id = ? AND user_id = ? AND status = 'active'");
            $chk->execute([$subscription_id, $user_id]);
            if ($chk->fetch()) {
                $upd = $pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?");
                $upd->execute([$new_date, $subscription_id]);

                // Also update the earliest pending delivery scheduled_date
                $updDel = $pdo->prepare("UPDATE subscription_deliveries SET scheduled_date = ? WHERE subscription_id = ? AND status = 'pending' ORDER BY delivery_month ASC LIMIT 1");
                $updDel->execute([$new_date, $subscription_id]);

                $_SESSION['profile_success'] = "تاریخ نوبت بعدی ارسال با موفقیت به " . htmlspecialchars($new_date) . " تغییر یافت.";
            }
        } else {
            $_SESSION['profile_error'] = "تاریخ انتخابی نامعتبر است. لطفاً تاریخی از امروز به بعد انتخاب کنید.";
        }
    }

    // 3. Skip / Postpone Next Delivery (+14 or +30 days)
    if ($action === 'skip_delivery' && $subscription_id > 0) {
        $skip_days = (int)($_POST['skip_days'] ?? 30);
        $chk = $pdo->prepare("SELECT next_delivery_date FROM user_subscriptions WHERE id = ? AND user_id = ? AND status = 'active'");
        $chk->execute([$subscription_id, $user_id]);
        $cur = $chk->fetch(PDO::FETCH_ASSOC);

        if ($cur) {
            $base_date = $cur['next_delivery_date'] ?: date('Y-m-d');
            $new_date = date('Y-m-d', strtotime("+$skip_days days", strtotime($base_date)));

            $upd = $pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?");
            $upd->execute([$new_date, $subscription_id]);

            $updDel = $pdo->prepare("UPDATE subscription_deliveries SET scheduled_date = ? WHERE subscription_id = ? AND status = 'pending' ORDER BY delivery_month ASC LIMIT 1");
            $updDel->execute([$new_date, $subscription_id]);

            $_SESSION['profile_success'] = "نوبت ارسال شما با موفقیت به مدت $skip_days روز به تعویق افتاد (موعد جدید: $new_date).";
        }
    }

    // 4. Cancel Subscription Anytime
    if ($action === 'cancel_subscription' && $subscription_id > 0) {
        $chk = $pdo->prepare("SELECT id FROM user_subscriptions WHERE id = ? AND user_id = ? AND status = 'active'");
        $chk->execute([$subscription_id, $user_id]);
        if ($chk->fetch()) {
            $upd = $pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE id = ?");
            $upd->execute([$subscription_id]);

            $_SESSION['profile_success'] = "اشتراک شما لغو شد. هر زمان که تمایل داشتید می‌توانید آن را مجدداً فعال فرمایید.";
        }
    }
}

header('Location: ../profile.php#subscriptions');
exit;
