<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// POST only — blocks GET-based cart manipulation via URL/image tags
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../cart.php');
    exit;
}

csrf_verify();

$action     = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($product_id > 0) {
    switch ($action) {
        case 'add':
            // Verify product exists and is in stock before adding
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND stock > 0");
            $stmt->execute([$product_id]);
            if ($stmt->fetch()) {
                $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
            }
            break;

        case 'increase':
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]++;
            }
            break;

        case 'decrease':
            if (isset($_SESSION['cart'][$product_id])) {
                if ($_SESSION['cart'][$product_id] > 1) {
                    $_SESSION['cart'][$product_id]--;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
            }
            break;

        case 'remove':
            unset($_SESSION['cart'][$product_id]);
            break;
    }
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'cart_count' => array_sum($_SESSION['cart'])]);
    exit;
}

// Safe redirect — strips external hosts to prevent open redirect
$referer = $_SERVER['HTTP_REFERER'] ?? '../cart.php';
safe_redirect($referer, '../cart.php');
