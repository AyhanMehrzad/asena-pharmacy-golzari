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
    if ($action === 'add_pet') {
        $name = trim($_POST['pet_name'] ?? '');
        $type = trim($_POST['pet_type'] ?? '');
        $race = trim($_POST['pet_race'] ?? '');
        
        if (!empty($name) && !empty($type)) {
            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, race) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $name, $type, $race])) {
                $_SESSION['profile_success'] = "حیوان خانگی جدید با موفقیت اضافه شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در ثبت حیوان خانگی.";
            }
        } else {
            $_SESSION['profile_error'] = "نام و نوع حیوان الزامی است.";
        }
    } elseif ($action === 'upload_document') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        $title  = trim($_POST['doc_title'] ?? '');

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if ($pet_id > 0 && !empty($title) && isset($_FILES['document'])) {
            $validation = validate_upload($_FILES['document'], $allowed_mimes, 5 * 1024 * 1024);

            if (!$validation['ok']) {
                $_SESSION['profile_error'] = $validation['error'];
            } else {
                $ext          = match($validation['mime']) {
                    'image/jpeg'       => 'jpg',
                    'image/png'        => 'png',
                    'image/webp'       => 'webp',
                    'application/pdf'  => 'pdf',
                    default            => 'bin',
                };
                $uploadDir  = '../uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $target_file = $uploadDir . $newFileName;
                $db_path     = 'uploads/documents/' . $newFileName;

                if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                    $stmt = $pdo->prepare("INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$pet_id, $user_id, $title, $newFileName, $db_path]);
                    $_SESSION['profile_success'] = 'سند با موفقیت آپلود شد.';
                } else {
                    $_SESSION['profile_error'] = 'خطا در انتقال فایل.';
                }
            }
        } else {
            $_SESSION['profile_error'] = 'لطفاً تمام فیلدها را پر کنید و فایلی انتخاب نمایید.';
        }
    } elseif ($action === 'edit_pet') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        $name = trim($_POST['pet_name'] ?? '');
        $type = trim($_POST['pet_type'] ?? '');
        $race = trim($_POST['pet_race'] ?? '');
        
        if ($pet_id > 0 && !empty($name) && !empty($type)) {
            // Ensure the pet belongs to the user
            $stmt = $pdo->prepare("UPDATE user_pets SET name = ?, type = ?, race = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$name, $type, $race, $pet_id, $user_id])) {
                $_SESSION['profile_success'] = "مشخصات حیوان خانگی با موفقیت بروزرسانی شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در بروزرسانی حیوان خانگی.";
            }
        } else {
            $_SESSION['profile_error'] = "نام و نوع حیوان الزامی است.";
        }
    } elseif ($action === 'delete_pet') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($pet_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM user_pets WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$pet_id, $user_id])) {
                $_SESSION['profile_success'] = "حیوان خانگی با موفقیت حذف شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در حذف حیوان خانگی.";
            }
        }
    } elseif ($action === 'cancel_subscription') {
        $sub_id = (int)($_POST['subscription_id'] ?? 0);
        if ($sub_id > 0) {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$sub_id, $user_id])) {
                $_SESSION['profile_success'] = "اشتراک با موفقیت لغو شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در لغو اشتراک.";
            }
        }
    } elseif ($action === 'cancel_order') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        if ($order_id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending_payment', 'processing')");
                $stmt->execute([$order_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Restore stock
                    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $items->execute([$order_id]);
                    $restoreStmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    foreach ($items->fetchAll() as $item) {
                        $restoreStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    // Deduct loyalty points
                    $pdo->prepare("UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - 50) WHERE id = ?")->execute([$user_id]);
                    
                    $pdo->commit();
                    $_SESSION['profile_success'] = "سفارش شما با موفقیت لغو شد.";
                } else {
                    $pdo->rollBack();
                    $_SESSION['profile_error'] = "امکان لغو این سفارش وجود ندارد (ممکن است ارسال شده باشد).";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['profile_error'] = "خطای سیستمی در لغو سفارش.";
            }
        }
    } elseif ($action === 'cancel_appointment') {
        $apt_id = (int)($_POST['appointment_id'] ?? 0);
        if ($apt_id > 0) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved', 'در انتظار')");
            $stmt->execute([$apt_id, $user_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['profile_success'] = "نوبت با موفقیت لغو شد.";
            } else {
                $_SESSION['profile_error'] = "امکان لغو این نوبت وجود ندارد.";
            }
        }
    }
}

header("Location: ../profile.php");
exit;
?>
