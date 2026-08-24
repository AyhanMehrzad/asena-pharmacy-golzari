<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'درخواست نامعتبر است.']);
    exit;
}

// CSRF Protection
$submitted_token = $_POST['csrf_token'] ?? '';
$expected_token  = $_SESSION['csrf_token'] ?? '';
if (!$expected_token || !hash_equals($expected_token, $submitted_token)) {
    echo json_encode(['status' => 'error', 'message' => 'خطای امنیتی (توکن نامعتبر). لطفاً صفحه را رفرش کنید.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$target_type = trim($_POST['target_type'] ?? '');
$target_id = (int)($_POST['target_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!in_array($target_type, ['product', 'doctor']) || $target_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ارسالی برای ثبت امتیاز نامعتبر است.']);
    exit;
}

// Check if user has already reviewed this target
$chkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND target_type = ? AND target_id = ?");
$chkStmt->execute([$user_id, $target_type, $target_id]);
$existing_review_id = $chkStmt->fetchColumn();

// Verify Purchase / Verified Appointment
$is_verified = 0;
if ($target_type === 'product') {
    $vStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?");
    $vStmt->execute([$user_id, $target_id]);
    $is_verified = ($vStmt->fetchColumn() > 0) ? 1 : 0;
} elseif ($target_type === 'doctor') {
    $vStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND doctor_id = ?");
    $vStmt->execute([$user_id, $target_id]);
    $is_verified = ($vStmt->fetchColumn() > 0) ? 1 : 0;
}

$points_awarded = 0;
if ($existing_review_id) {
    // Update existing review
    $upd = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, is_verified_buyer = ?, status = 'approved', created_at = NOW() WHERE id = ?");
    $upd->execute([$rating, $comment, $is_verified, $existing_review_id]);
} else {
    // Insert new review
    $ins = $pdo->prepare("INSERT INTO reviews (user_id, target_type, target_id, rating, comment, is_verified_buyer, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())");
    $ins->execute([$user_id, $target_type, $target_id, $rating, $comment, $is_verified]);
    
    // Reward loyalty points (+5 points for reviewing)
    if ($is_verified) {
        $rwStmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 5 WHERE id = ?");
        $rwStmt->execute([$user_id]);
        $points_awarded = 5;
    }
}

// Recalculate Bayesian Weighted Average
$stats = recalculate_bayesian_rating($pdo, $target_type, $target_id);

echo json_encode([
    'status' => 'success',
    'message' => 'نظر و امتیاز شما با موفقیت ثبت شد.' . ($points_awarded ? ' (+۵ امتیاز وفاداری به کیف پول شما اضافه شد)' : ''),
    'new_rating' => $stats['rating'],
    'review_count' => $stats['review_count'],
    'points_awarded' => $points_awarded
]);
