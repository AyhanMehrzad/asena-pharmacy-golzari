<?php
require_once '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['user_id'])) {
    $msg = trim($_POST['message']);
    $uid = (int)$_POST['user_id'];
    if (!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, sender_type, message, is_read) VALUES (?, 'admin', ?, TRUE)");
        $stmt->execute([$uid, $msg]);
    }
    header("Location: chat.php?user_id=$uid");
    exit;
}

// Get distinct users who have sent a message (not AI)
$users_stmt = $pdo->query("
    SELECT DISTINCT u.id, u.name, u.phone 
    FROM chat_messages c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.message NOT LIKE '[AI] %'
");
$chat_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
if ($user_id_filter) {
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages 
        WHERE user_id = ? AND message NOT LIKE '[AI] %' 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user_id_filter]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark as read
    $pdo->prepare("UPDATE chat_messages SET is_read = TRUE WHERE user_id = ? AND sender_type = 'user'")->execute([$user_id_filter]);
}
?>
<?php include 'includes/header.php'; ?>
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">پشتیبانی آنلاین</h1>
    </div>

    <div class="flex h-[70vh] bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Sidebar: Users list -->
        <div class="w-1/3 border-l border-gray-200 flex flex-col bg-gray-50">
            <div class="p-4 border-b border-gray-200 bg-white">
                <h3 class="font-bold text-gray-700">لیست گفتگوها</h3>
            </div>
            <div class="flex-1 overflow-y-auto">
                <?php foreach($chat_users as $cu): ?>
                    <a href="chat.php?user_id=<?php echo $cu['id']; ?>" class="block p-4 border-b border-gray-100 hover:bg-blue-50 transition-colors <?php echo $user_id_filter == $cu['id'] ? 'bg-blue-50 border-l-4 border-blue-500' : ''; ?>">
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($cu['name'] ?? 'کاربر ناشناس'); ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($cu['phone']); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="w-2/3 flex flex-col relative">
            <?php if($user_id_filter): ?>
                <div class="p-4 border-b border-gray-200 bg-white">
                    <h3 class="font-bold text-gray-800">گفتگو با کاربر</h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50" id="chat-box">
                    <?php foreach($messages as $msg): ?>
                        <?php if($msg['sender_type'] === 'admin'): ?>
                            <div class="flex justify-start flex-row-reverse gap-3">
                                <div class="bg-blue-600 text-white p-3 rounded-2xl rounded-tl-sm max-w-[70%] text-sm">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex justify-start gap-3">
                                <div class="bg-white border border-gray-200 p-3 rounded-2xl rounded-tr-sm max-w-[70%] text-sm text-gray-700">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="p-4 bg-white border-t border-gray-200">
                    <form method="POST" class="flex gap-2">
                        <input type="hidden" name="user_id" value="<?php echo $user_id_filter; ?>">
                        <input type="text" name="message" required placeholder="پیام خود را بنویسید..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700">ارسال</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    <p>یک گفتگو را از لیست انتخاب کنید</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const chatBox = document.getElementById('chat-box');
    if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>
<?php include 'includes/footer.php'; ?>
