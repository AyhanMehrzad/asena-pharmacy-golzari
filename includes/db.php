<?php
// Ensure session is started globally before any potential output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Master configuration file in root (if exists)
$rootConfig = __DIR__ . '/../../config.php';
if (file_exists($rootConfig)) {
    require_once $rootConfig;
}

$host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$dbname = defined('DB_NAME') ? DB_NAME : 'asena_premium';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';

try {
    // Connect directly to the database with utf8mb4 charset (standard for cPanel & local)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch(PDOException $e) {
    // Try local default credentials if connection failed on localhost
    $connected = false;
    if ($host === '127.0.0.1' || $host === 'localhost') {
        foreach (array_unique([$dbname, 'asena_premium', 'petshop_db']) as $tryDb) {
            try {
                $pdo = new PDO("mysql:host=127.0.0.1;dbname=$tryDb;charset=utf8mb4", 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                $connected = true;
                break;
            } catch (PDOException $eLocal) {}
        }
    }
    if (!$connected) {
        // If database doesn't exist on local development, try to create it
        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");
            $sqlFile = __DIR__ . '/../petshop_db.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if (!empty(trim($sql))) {
                    $pdo->exec($sql);
                }
            }
        } catch(PDOException $e2) {
            die("خطا در اتصال به دیتابیس: لطفاً اطلاعات دیتابیس در فایل config.php را بررسی کنید. (" . $e->getMessage() . ")");
        }
    }
}

require_once __DIR__ . '/functions.php';
?>
