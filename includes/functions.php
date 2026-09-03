<?php
// Centralized utility functions.
// Session is already started via includes/db.php.

function e($string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): void {
    $submitted = trim($_POST['csrf_token'] ?? '');
    $expected  = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        die('خطای امنیتی: توکن نامعتبر است. لطفاً صفحه را رفرش کنید.');
    }
}

function safe_redirect(string $url, string $fallback = '/'): void {
    $parsed = parse_url($url);
    // Block schemes, hosts, AND backslash-based protocol-relative URLs
    if (!empty($parsed['scheme'])
        || !empty($parsed['host'])
        || str_starts_with(ltrim($url), '\\')
        || (str_starts_with(ltrim($url), '/') && str_starts_with(ltrim(ltrim($url), '/'), '/'))
    ) {
        $url = $fallback;
    }
    // Strip any newline chars to prevent header injection
    $url = preg_replace('/[\r\n]/', '', $url);
    header('Location: ' . $url, true, 302);
    exit;
}

function validate_upload(array $file, array $allowed_mimes, int $max_bytes = 5_242_880): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'خطا در آپلود فایل (کد: ' . $file['error'] . ').'];
    }
    if ($file['size'] > $max_bytes) {
        $mb = number_format($max_bytes / 1_048_576, 1);
        return ['ok' => false, 'error' => "حجم فایل بیش از {$mb} مگابایت است."];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mimes, true)) {
        return ['ok' => false, 'error' => "نوع فایل «{$mime}» مجاز نیست."];
    }
    return ['ok' => true, 'mime' => $mime];
}

/**
 * Sliding-window rate limiter for authentication endpoints.
 * Limits login attempts to 5 per 2 minutes per IP address.
 */
function check_rate_limit(PDO $pdo, string $ip, string $username = ''): ?string {
    // 1. Clean up old entries (older than 2 minutes)
    $pdo->query("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");

    // 2. Count recent attempts for this IP
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, MIN(attempt_time) as first_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempts = (int)($row['count'] ?? 0);

    if ($attempts >= 5) {
        $first_attempt_time = strtotime($row['first_attempt'] ?? date('Y-m-d H:i:s'));
        $unblock_time = $first_attempt_time + 120; // 2 minutes
        $remaining = max(1, $unblock_time - time());
        
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $time_str = ($minutes > 0 ? $minutes . ' دقیقه و ' : '') . $seconds . ' ثانیه';
        
        return "تعداد دفعات مجاز به پایان رسیده است. لطفاً $time_str دیگر تلاش کنید.";
    }

    // 3. Log this attempt
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $username]);
    
    return null;
}

/**
 * Bayesian Weighted Average Calculator for Products and Doctors
 * Prior damping weight C = 5
 */
function recalculate_bayesian_rating(PDO $pdo, string $target_type, int $target_id): array {
    $c = 5; // prior confidence weight
    if ($target_type === 'product') {
        $stmt = $pdo->prepare("SELECT IFNULL(baseline_rating, 4.8) FROM products WHERE id = ?");
        $stmt->execute([$target_id]);
        $baseline = (float)($stmt->fetchColumn() ?: 4.8);

        $revStmt = $pdo->prepare("SELECT COUNT(*) as cnt, IFNULL(SUM(rating), 0) as total_sum FROM reviews WHERE target_type = 'product' AND target_id = ? AND status = 'approved'");
        $revStmt->execute([$target_id]);
        $res = $revStmt->fetch(PDO::FETCH_ASSOC);
        $n = (int)$res['cnt'];
        $sum = (float)$res['total_sum'];

        $bayesian_score = round((($c * $baseline) + $sum) / ($c + $n), 1);

        $upd = $pdo->prepare("UPDATE products SET rating_cache = ?, review_count_cache = ? WHERE id = ?");
        $upd->execute([$bayesian_score, $n, $target_id]);
        return ['rating' => $bayesian_score, 'review_count' => $n, 'baseline' => $baseline];
    } elseif ($target_type === 'doctor') {
        $stmt = $pdo->prepare("SELECT IFNULL(baseline_rating, 4.9) FROM doctors WHERE id = ?");
        $stmt->execute([$target_id]);
        $baseline = (float)($stmt->fetchColumn() ?: 4.9);

        $revStmt = $pdo->prepare("SELECT COUNT(*) as cnt, IFNULL(SUM(rating), 0) as total_sum FROM reviews WHERE target_type = 'doctor' AND target_id = ? AND status = 'approved'");
        $revStmt->execute([$target_id]);
        $res = $revStmt->fetch(PDO::FETCH_ASSOC);
        $n = (int)$res['cnt'];
        $sum = (float)$res['total_sum'];

        $bayesian_score = round((($c * $baseline) + $sum) / ($c + $n), 1);

        $upd = $pdo->prepare("UPDATE doctors SET rating = ?, review_count = ? WHERE id = ?");
        $upd->execute([$bayesian_score, $n, $target_id]);
        return ['rating' => $bayesian_score, 'review_count' => $n, 'baseline' => $baseline];
    }
    return ['rating' => 4.8, 'review_count' => 0, 'baseline' => 4.8];
}

/**
 * Returns dynamic absolute base URL of the current application install.
 */
function get_app_base_url(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $subDir = str_replace($docRoot, '', $appRoot);
    return $scheme . '://' . $host . $subDir;
}

/**
 * Formats autoship delivery frequency into human-readable Persian text.
 */
function format_autoship_frequency(?string $freq): string {
    $map = [
        '2_weeks'  => 'هر ۲ هفته یک‌بار (۱۴ روز)',
        '2_week'   => 'هر ۲ هفته یک‌بار (۱۴ روز)',
        '14_days'  => 'هر ۲ هفته یک‌بار (۱۴ روز)',
        '14'       => 'هر ۲ هفته یک‌بار (۱۴ روز)',
        '1_week'   => 'هر ۱ هفته یک‌بار (۷ روز)',
        '7_days'   => 'هر ۱ هفته یک‌بار (۷ روز)',
        '7'        => 'هر ۱ هفته یک‌بار (۷ روز)',
        '1_month'  => 'هر ۱ ماه یک‌بار (۳۰ روز)',
        'monthly'  => 'هر ۱ ماه یک‌بار (۳۰ روز)',
        '30_days'  => 'هر ۱ ماه یک‌بار (۳۰ روز)',
        '30'       => 'هر ۱ ماه یک‌بار (۳۰ روز)',
        '2_months' => 'هر ۲ ماه یک‌بار (۶۰ روز)',
        '60_days'  => 'هر ۲ ماه یک‌بار (۶۰ روز)',
        '60'       => 'هر ۲ ماه یک‌بار (۶۰ روز)',
        '3_months' => 'هر ۳ ماه یک‌بار (۹۰ روز)',
        '90_days'  => 'هر ۳ ماه یک‌بار (۹۰ روز)',
        '90'       => 'هر ۳ ماه یک‌بار (۹۰ روز)',
    ];
    return $map[$freq ?? ''] ?? 'هر ۱ ماه یک‌بار (۳۰ روز)';
}

/**
 * Returns integer number of days corresponding to an autoship frequency string.
 */
function get_autoship_frequency_days(?string $freq): int {
    switch ($freq) {
        case '2_weeks':
        case '2_week':
        case '14_days':
        case '14':
            return 14;
        case '1_week':
        case '7_days':
        case '7':
            return 7;
        case '2_months':
        case '60_days':
        case '60':
            return 60;
        case '3_months':
        case '90_days':
        case '90':
            return 90;
        case '1_month':
        case 'monthly':
        case '30_days':
        case '30':
        default:
            return 30;
    }
}

/**
 * Get active curated recommendations for a given slot type.
 */
function get_curated_recommendations(PDO $pdo, string $slot_type, int $limit = 4): array {
    try {
        $stmt = $pdo->prepare("
            SELECT cr.*, p.name as product_name, p.category as product_category, 
                   p.price as product_price, p.discount_price as product_discount_price,
                   p.image_url as product_image_url, p.target_animal, p.is_autoship, p.autoship_discount
            FROM curated_recommendations cr
            JOIN products p ON cr.product_id = p.id
            WHERE cr.slot_type = ? AND cr.is_active = 1
            ORDER BY cr.display_order ASC, cr.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $slot_type, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Ensure site_settings table exists and retrieve setting
 */
function get_setting(PDO $pdo, string $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false && $row['setting_value'] !== null) {
            return $row['setting_value'];
        }
    } catch (Exception $e) {
        // Table might not exist yet, create it
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT DEFAULT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $ex) {}
    }
    return $default;
}

/**
 * Save setting to site_settings table
 */
function set_setting(PDO $pdo, string $key, $value): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT DEFAULT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            return $stmt->execute([$key, $value]);
        } catch (Exception $ex) {
            return false;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Persian Localization & Number/Currency Helpers
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists(__DIR__ . '/jdf.php')) {
    require_once __DIR__ . '/jdf.php';
}

/**
 * Convert English ASCII digits to standard Persian digits
 */
function to_persian_num($input): string {
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($en, $fa, (string)$input);
}

/**
 * Standardized Iranian Toman currency formatter with optional Persian numerals
 */
function format_price($amount, bool $showUnit = true, bool $persianDigits = true): string {
    $num = is_numeric($amount) ? (float)$amount : 0;
    $formatted = number_format($num);
    if ($persianDigits) {
        $formatted = to_persian_num($formatted);
    }
    return $showUnit ? ($formatted . ' تومان') : $formatted;
}

