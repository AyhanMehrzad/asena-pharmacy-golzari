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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'confirm_delivery') {
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        $received = (int)($_POST['received'] ?? 0);
        
        $new_status = $received ? 'delivered' : 'not_received';
        
        // Ensure this delivery belongs to the current user
        $stmt = $pdo->prepare("
            SELECT d.id FROM subscription_deliveries d
            JOIN user_subscriptions s ON d.subscription_id = s.id
            WHERE d.id = ? AND s.user_id = ? AND d.status = 'shipped'
        ");
        $stmt->execute([$delivery_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $updateStmt = $pdo->prepare("UPDATE subscription_deliveries SET status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $delivery_id]);
            
            if ($received) {
                $_SESSION['profile_success'] = "تایید دریافت مرسوله با موفقیت ثبت شد. از خرید شما متشکریم!";
            } else {
                $_SESSION['profile_error'] = "گزارش عدم دریافت مرسوله ثبت شد. پشتیبانی با شما تماس خواهد گرفت.";
            }
        }
    }
}

header('Location: ../profile.php');
exit;
?>
