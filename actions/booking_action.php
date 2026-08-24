<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'booking.php';
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php');
    exit;
}

csrf_verify();

$doctor_id     = (int)($_POST['doctor_id'] ?? 0);
$date          = trim($_POST['appointment_date'] ?? '');
$time          = trim($_POST['appointment_time'] ?? '');
$pet_type      = trim($_POST['pet_type'] ?? '');
$pet_race      = trim($_POST['pet_race'] ?? '');
$visit_purpose = trim($_POST['visit_purpose'] ?? 'معاینه و چکاپ سلامت');
$pet_notes     = trim($_POST['pet_notes'] ?? '');

// ── Validate inputs ────────────────────────────────────────────────────────────
if ($doctor_id <= 0 || empty($date) || empty($time) || empty($pet_type)) {
    $_SESSION['booking_error'] = 'لطفا پزشک، تاریخ و زمان را به درستی انتخاب کنید.';
    header('Location: ../booking.php');
    exit;
}

// Reject past dates
if (strtotime($date) < strtotime('today')) {
    $_SESSION['booking_error'] = 'تاریخ انتخابی نمی‌تواند در گذشته باشد.';
    header('Location: ../booking.php');
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate(
    (int)substr($date, 5, 2),
    (int)substr($date, 8, 2),
    (int)substr($date, 0, 4)
)) {
    $_SESSION['booking_error'] = 'فرمت تاریخ نامعتبر است.';
    header('Location: ../booking.php');
    exit;
}

// Validate time format (HH:MM)
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    $_SESSION['booking_error'] = 'فرمت زمان نامعتبر است.';
    header('Location: ../booking.php');
    exit;
}

try {
    // ── Doctor validation ──────────────────────────────────────────────────────
    $docCheck = $pdo->prepare("SELECT id, price FROM doctors WHERE id = ?");
    $docCheck->execute([$doctor_id]);
    $doctorRow = $docCheck->fetch();
    if (!$doctorRow) {
        $_SESSION['booking_error'] = 'پزشک انتخابی معتبر نیست.';
        header('Location: ../booking.php');
        exit;
    }
    $doctor_price = (int)$doctorRow['price'];

    $pdo->beginTransaction();

    // ── Double-booking guard ───────────────────────────────────────────────────
    // Check if this exact slot is already taken (prevents race conditions via DB constraint)
    $dupStmt = $pdo->prepare(
        "SELECT id FROM appointments
         WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
         AND status NOT IN ('cancelled')
         LIMIT 1
         FOR UPDATE"
    );
    $dupStmt->execute([$doctor_id, $date, $time]);
    if ($dupStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = 'این زمان قبلاً رزرو شده است. لطفاً زمان دیگری انتخاب کنید.';
        header('Location: ../booking.php');
        exit;
    }

    // ── Insert appointment with visit_purpose and pet_notes ───────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, pet_type, pet_race, visit_purpose, pet_notes, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([$_SESSION['user_id'], $doctor_id, $date, $time, $pet_type, $pet_race, $visit_purpose, $pet_notes]);
    $appointment_id = $pdo->lastInsertId();

    // ── Store booking pending order in session for payment flow ───────────────
    $_SESSION['pending_order'] = [
        'type'           => 'booking',
        'booking_id'     => $appointment_id,
        'items'          => [],
        'total_amount'   => $doctor_price,
        'created_at'     => time(),
    ];
    $_SESSION['pay_nonce'] = bin2hex(random_bytes(24));
    
    $pdo->commit();

    header('Location: ../test_payment.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Booking error: " . $e->getMessage());
    $_SESSION['booking_error'] = 'خطای سیستمی در ثبت نوبت. لطفاً دوباره تلاش کنید.';
    header('Location: ../booking.php');
    exit;
}
