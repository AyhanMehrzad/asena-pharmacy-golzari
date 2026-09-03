<?php
/**
 * ASENA Database Setup & Migration Runner
 * Security: CLI-Only or Token Protected
 */

$isCli = (php_sapi_name() === 'cli');
$installedFile = __DIR__ . '/.installed';

// Master/local config
$rootConfig = __DIR__ . '/../../config.php';
if (file_exists($rootConfig)) require_once $rootConfig;
$localConfig = __DIR__ . '/config.php';
if (file_exists($localConfig)) require_once $localConfig;

// Token check for web execution
$setupSecret = defined('SETUP_SECRET_TOKEN') ? SETUP_SECRET_TOKEN : (getenv('SETUP_SECRET_TOKEN') ?: 'asena_setup_auth_key_2026');
$providedToken = $_GET['token'] ?? '';
$isForce = $isCli ? in_array('--force', $argv ?? []) : (isset($_GET['force']) && $_GET['force'] === '1');

if (!$isCli && (!hash_equals($setupSecret, (string)$providedToken))) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    die('<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><title>دسترسی غیرمجاز</title><style>body{font-family:Tahoma,sans-serif;padding:50px;background:#f8fafc;color:#1e293b;text-align:center;}div{max-width:500px;margin:auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);}</style></head><body><div><h2>دسترسی غیرمجاز</h2><p>به دلایل امنیتی، اسکریپت نصب دیتابیس تنها از طریق خط فرمان (CLI) یا با توکن امنیتی مجاز قابل اجرا است.</p></div></body></html>');
}

if (file_exists($installedFile) && !$isForce) {
    header('Content-Type: text/html; charset=utf-8');
    die("دیتابیس قبلاً نصب شده است. جهت نصب مجدد از پارامتر force استفاده نمایید.");
}

require_once __DIR__ . '/includes/db.php';

// Locate SQL file
$sqlCandidates = [
    __DIR__ . '/petshop_db.sql',
    __DIR__ . '/database.sql',
    __DIR__ . '/pharmacy_db.sql'
];

$sqlFile = null;
foreach ($sqlCandidates as $candidate) {
    if (file_exists($candidate)) {
        $sqlFile = $candidate;
        break;
    }
}

if (!$sqlFile) {
    die("خطا: فایل SQL نصب پیدا نشد.");
}

try {
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    touch($installedFile);
    echo "پایگاه داده با موفقیت از فایل " . basename($sqlFile) . " راه‌اندازی و قفل شد.";
} catch(PDOException $e) {
    error_log("Database setup failed: " . $e->getMessage());
    die("خطا در راه‌اندازی پایگاه داده: " . htmlspecialchars($e->getMessage()));
}
