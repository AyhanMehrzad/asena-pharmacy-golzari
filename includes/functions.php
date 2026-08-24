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

