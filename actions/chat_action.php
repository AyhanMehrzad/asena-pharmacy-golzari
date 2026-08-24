<?php
require '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// GEMINI API Configuration
$gemini_api_key = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE';
$gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $gemini_api_key;

$leo_system_prompt = "نام تو لئو (Leo the Lion) است. تو یک دستیار هوشمند دامپزشکی در پت‌شاپ ASENA هستی. 
فقط و فقط درباره حیوانات خانگی، مشکلات جسمی آنها، و محصولات پت‌شاپ صحبت می‌کنی. 
اگر کاربر سوالی نامربوط به حیوانات پرسید، فقط بگو: 'من لئو هستم و فقط می‌توانم درباره حیوانات خانگی به شما کمک کنم.' 
توضیحاتت باید کوتاه، دقیق و با لحنی صمیمی اما حرفه‌ای باشد. اگر تصویری ارسال شد، مشکلات جسمی یا بیماری حیوان را تشخیص بده.";

if ($action === 'init') {
    $mode = $_POST['mode'] ?? 'ai'; // 'ai' or 'admin'
    
    // Find active ticket for this mode
    $stmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id = ? AND mode = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id, $mode]);
    $ticket_id = $stmt->fetchColumn();
    
    if (!$ticket_id) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode) VALUES (?, ?)");
        $stmt->execute([$user_id, $mode]);
        $ticket_id = $pdo->lastInsertId();
        
        // Add welcome message
        if ($mode === 'ai') {
            $welcome = "سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'ai', ?)")->execute([$ticket_id, $welcome]);
        } else {
            $welcome = "درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'admin', ?)")->execute([$ticket_id, $welcome]);
        }
    }
    
    echo json_encode(['status' => 'success', 'ticket_id' => $ticket_id]);
    exit;
}

if ($action === 'fetch') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $last_id = $_POST['last_id'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT id, sender_type, message, image_url, created_at FROM ticket_messages WHERE ticket_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$ticket_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

if ($action === 'send') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');
    
    // Verify ticket belongs to user
    $stmt = $pdo->prepare("SELECT mode FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    $mode = $stmt->fetchColumn();
    
    if (!$mode) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found or unauthorized']);
        exit;
    }
    
    $image_url = null;
    $base64_image = null;
    $mime_type = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $mime_type = mime_content_type($file_tmp);
        
        $allowed_mime_map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        
        if (isset($allowed_mime_map[$mime_type]) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            $ext = $allowed_mime_map[$mime_type];
            if ($mode === 'admin') {
                // Save safely with random hash and strict verified extension
                $filename = 'ticket_' . bin2hex(random_bytes(10)) . '.' . $ext;
                $filepath = '../uploads/' . $filename;
                if (!is_dir('../uploads/')) mkdir('../uploads/', 0755, true);
                if (move_uploaded_file($file_tmp, $filepath)) {
                    $image_url = 'uploads/' . $filename;
                }
            } else if ($mode === 'ai') {
                // Convert to base64 for Gemini (Do NOT save to disk)
                $data = file_get_contents($file_tmp);
                $base64_image = base64_encode($data);
            }
        }
    }
    
    // Insert user message
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, image_url) VALUES (?, 'user', ?, ?)");
    $stmt->execute([$ticket_id, $message, $image_url]);
    $user_msg_id = $pdo->lastInsertId();
    
    // Trigger AI if mode is ai
    if ($mode === 'ai') {
        // Fetch last 10 messages for context
        $stmt = $pdo->prepare("SELECT sender_type, message FROM ticket_messages WHERE ticket_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$ticket_id]);
        $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        $contents = [];
        foreach ($history as $msg) {
            if ($msg['sender_type'] === 'user') {
                $parts = [];
                $parts[] = ["text" => $msg['message'] ?: ''];
                
                // If this is the current message and has an image, attach it
                if ($msg['message'] == $message && $base64_image) {
                    $parts[] = [
                        "inline_data" => [
                            "mime_type" => $mime_type,
                            "data" => $base64_image
                        ]
                    ];
                }
                $contents[] = ["role" => "user", "parts" => $parts];
            } else if ($msg['sender_type'] === 'ai') {
                $contents[] = ["role" => "model", "parts" => [["text" => $msg['message']]]];
            }
        }
        
        $payload = [
            "systemInstruction" => [
                "role" => "system",
                "parts" => [["text" => $leo_system_prompt]]
            ],
            "contents" => $contents,
            "generationConfig" => [
                "temperature" => 0.4,
                "maxOutputTokens" => 400
            ]
        ];
        
        $ch = curl_init($gemini_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        $ai_reply = "خطا در برقراری ارتباط با مغز لئو.";
        
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_reply = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'ai', ?)");
        $stmt->execute([$ticket_id, $ai_reply]);
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'admin_send') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');
    
    // Verify admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'admin', ?)");
    if ($stmt->execute([$ticket_id, $message])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if ($action === 'reopen') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    
    // Verify user
    $stmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    if ($stmt->fetchColumn() == $user_id) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'open' WHERE id = ?");
        $stmt->execute([$ticket_id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit;
}
?>
