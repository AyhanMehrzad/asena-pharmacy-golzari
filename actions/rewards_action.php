<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'redeem') {
    csrf_verify();
    $cost = (int)($_POST['cost'] ?? 0);
    $discount = (int)($_POST['discount'] ?? 0);

    // Validate request
    $valid_rewards = [
        500 => 5,
        1000 => 10,
        1500 => 15,
        2000 => 20
    ];

    if (!isset($valid_rewards[$cost]) || $valid_rewards[$cost] !== $discount) {
        $_SESSION['reward_error'] = "درخواست نامعتبر است.";
        header("Location: rewards.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check user points with row lock
        $stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['loyalty_points'] < $cost) {
            $_SESSION['reward_error'] = "امتیاز شما برای دریافت این کد تخفیف کافی نیست.";
            $pdo->rollBack();
            header("Location: ../rewards.php");
            exit;
        }

        // Generate a random string code
        $randomString = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $code = "PETCARE-{$discount}-{$randomString}";

        // Deduct points
        $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
        $stmt->execute([$cost, $user_id]);

        // Insert code
        $stmt = $pdo->prepare("INSERT INTO promo_codes (user_id, code, discount_percentage, points_cost) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $code, $discount, $cost]);

        $pdo->commit();
        $_SESSION['reward_success'] = "کد تخفیف با موفقیت ایجاد شد! کد شما: $code";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['reward_error'] = "خطا در پردازش درخواست. لطفا دوباره تلاش کنید.";
    }

    header("Location: ../rewards.php");
    exit;
} else {
    header("Location: ../rewards.php");
    exit;
}
?>
