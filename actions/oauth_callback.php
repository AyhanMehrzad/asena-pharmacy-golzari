<?php
require_once '../includes/db.php';
require_once '../includes/config.php';

$provider = isset($_GET['provider']) ? $_GET['provider'] : '';

if (!in_array($provider, ['google', 'apple'])) {
    die('Invalid provider');
}

$id_column = $provider === 'google' ? 'google_id' : 'apple_id';
$oauth_id = null;
$oauth_email = null;
$oauth_name = 'کاربر ' . ucfirst($provider);

if ($provider === 'google') {
    if (!isset($_GET['code'])) {
        die('Google Auth Failed: No code returned.');
    }
    
    // Exchange code for token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]));
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        // Get user info
        $ch2 = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
        $user_response = curl_exec($ch2);
        curl_close($ch2);
        
        $user_info = json_decode($user_response, true);
        if (isset($user_info['id'])) {
            $oauth_id = $user_info['id'];
            $oauth_email = $user_info['email'] ?? null;
            $oauth_name = $user_info['name'] ?? $oauth_name;
        } else {
            die('Failed to retrieve user info from Google.');
        }
    } else {
        die('Failed to get Google Access Token: ' . ($data['error_description'] ?? 'Unknown error'));
    }
} elseif ($provider === 'apple') {
    // Apple POSTs to the callback
    if (!isset($_POST['id_token'])) {
        die('Apple Auth Failed: No id_token returned.');
    }
    
    // An id_token is a JWT. 
    // SECURITY WARNING: In a production environment, you MUST verify the JWT signature against Apple's public keys.
    // This requires a JWT library (e.g. firebase/php-jwt). 
    // Here we extract the payload for demonstration.
    $token_parts = explode('.', $_POST['id_token']);
    if (count($token_parts) >= 2) {
        $payload = json_decode(base64_decode($token_parts[1]), true);
        if (isset($payload['sub'])) {
            $oauth_id = $payload['sub']; // The unique Apple ID
            $oauth_email = $payload['email'] ?? null;
            
            // Apple only sends the 'user' JSON object (with name) on the FIRST login
            if (isset($_POST['user'])) {
                $user_json = json_decode($_POST['user'], true);
                if (isset($user_json['name'])) {
                    $oauth_name = trim(($user_json['name']['firstName'] ?? '') . ' ' . ($user_json['name']['lastName'] ?? ''));
                }
            }
        } else {
            die('Invalid Apple id_token structure.');
        }
    } else {
        die('Invalid Apple id_token.');
    }
}

if (!$oauth_id) {
    die('OAuth Flow Failed to retrieve an ID.');
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, role, phone FROM users WHERE $id_column = ?");
    $stmt->execute([$oauth_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Login existing user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        
        // If they haven't completed their profile (dummy phone check)
        if (strpos($user['phone'], '09OAUTH') === 0) {
            header('Location: ../complete_profile.php');
            exit;
        }
    } else {
        // Register new user
        // Generate a random mock phone since it's required and UNIQUE. We prefix with 09OAUTH so we know it's unverified.
        $mock_phone = '09OAUTH' . rand(10000, 99999) . time();
        $insert = $pdo->prepare("INSERT INTO users (phone, name, email, $id_column, loyalty_points) VALUES (?, ?, ?, ?, 50)");
        $insert->execute([$mock_phone, $oauth_name, $oauth_email, $oauth_id]);
        
        $new_user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_role'] = 'user';
        
        // Redirect new users to complete their profile (verify phone number and enter address)
        header('Location: ../complete_profile.php');
        exit;
    }

    header('Location: ../index.php');
    exit;
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
