<?php
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['PHP_SELF'] = '/asena/asena-premium/login.php';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
echo $protocol . '://' . $host . $base_dir . '/actions/oauth_callback.php?provider=google';
?>
