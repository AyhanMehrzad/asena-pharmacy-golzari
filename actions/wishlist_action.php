<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ابتدا وارد حساب کاربری شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'محصول نامعتبر است.']);
    exit;
}

try {
    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        // Remove from wishlist
        $delete = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Add to wishlist
        $insert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $insert->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطای دیتابیس رخ داد.']);
}
?>
