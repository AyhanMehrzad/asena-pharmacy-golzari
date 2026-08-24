<?php
// === OAUTH CONFIGURATION ===
// NOTE: You MUST fill these with your actual credentials from Google/Apple Developer Consoles.

// Google OAuth
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
// Make sure this exact URI is authorized in your Google Cloud Console
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\\\');
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . $base_dir . '/actions/oauth_callback.php?provider=google');

// Apple OAuth
define('APPLE_CLIENT_ID', 'com.yourdomain.petshop'); // Service ID
define('APPLE_TEAM_ID', 'YOUR_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_KEY_ID');
define('APPLE_REDIRECT_URI', 'https://yourdomain.com/petshop/actions/oauth_callback.php?provider=apple');
// For Apple, you need the actual .p8 file content or path.
// Doing raw Apple Sign-In requires generating a JWT, which is very difficult without composer dependencies.
// A common approach is using 'league/oauth2-apple' via Composer.
?>
