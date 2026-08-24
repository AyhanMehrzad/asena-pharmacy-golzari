<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($action === 'update_account') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);
            }
            $_SESSION['settings_success'] = "اطلاعات حساب با موفقیت بروزرسانی شد.";
        } catch (PDOException $e) {
            $_SESSION['settings_error'] = "خطا در بروزرسانی اطلاعات حساب.";
        }
    } elseif ($action === 'update_address') {
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        // Ensure lat/lng are null if empty
        if ($latitude === '') $latitude = null;
        if ($longitude === '') $longitude = null;

        if (empty($postal_code)) {
            $_SESSION['settings_error'] = "وارد کردن کد پستی الزامی است.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET city = ?, postal_code = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?");
                $stmt->execute([$city, $postal_code, $address, $latitude, $longitude, $user_id]);
                $_SESSION['settings_success'] = "نشانی با موفقیت بروزرسانی شد.";
            } catch (PDOException $e) {
                $_SESSION['settings_error'] = "خطا در بروزرسانی نشانی.";
            }
        }
    }
}

header("Location: ../profile_settings.php");
exit;
?>
