<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['ticket_id'] ?? null;

if (!$ticket_id) {
    // Redirect to tickets history if no ticket provided
    header('Location: user_tickets.php');
    exit;
}

// Auto-close tickets inactive for 24 hours
$pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Verify ticket ownership
$stmt = $pdo->prepare("SELECT mode, status FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: user_tickets.php');
    exit;
}

$mode = $ticket['mode'];

// Fetch all tickets for sidebar
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user_id]);
$all_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<main class="w-full max-w-[1200px] mx-auto py-8 px-4 h-[calc(100vh-100px)]">
    <div class="grid grid-cols-1 md:grid-cols-4 h-full gap-6">
        
        <!-- Mobile Sidebar Backdrop -->
        <div id="chat-sidebar-backdrop" onclick="toggleChatSidebar()" class="fixed inset-0 bg-black/50 z-[50] hidden md:hidden"></div>

        <!-- Sidebar: Chat History -->
        <div id="chat-sidebar" class="fixed md:relative top-0 right-0 h-full w-4/5 md:w-auto z-[60] md:z-0 translate-x-full md:translate-x-0 transition-transform duration-300 workstation-module rounded-none md:rounded-[2.5rem] overflow-hidden flex flex-col bg-surface-container-lowest col-span-1 shadow-2xl border-none">
            <div class="md:hidden p-4 bg-primary text-white flex justify-between items-center">
                <h3 class="font-bold">منوی گفتگو</h3>
                <button onclick="toggleChatSidebar()" class="material-symbols-outlined hover:bg-white/10 rounded-full p-2">close</button>
            </div>
            <div class="p-6 border-b border-outline-variant/20 bg-surface-container-lowest flex items-center justify-between">
                <h3 class="font-bold text-primary">گفتگوهای من</h3>
                <a href="index.php#support-section" class="w-8 h-8 rounded-full bg-primary-container/10 text-primary-container flex items-center justify-center hover:bg-primary-container hover:text-white transition-colors" title="گفتگوی جدید">
                    <span class="material-symbols-outlined text-sm">add</span>
                </a>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php foreach($all_tickets as $t): ?>
                <a href="chat.php?ticket_id=<?php echo $t['id']; ?>" class="block w-full text-right p-3 rounded-xl hover:bg-surface-container transition-colors flex items-center gap-3 <?php echo $t['id'] == $ticket_id ? 'bg-primary-container/10 border border-primary-container/20 shadow-sm' : ''; ?>">
                    <div class="w-10 h-10 rounded-full <?php echo $t['mode'] == 'ai' ? 'bg-primary-container text-white' : 'bg-secondary-container text-white'; ?> flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl"><?php echo $t['mode'] == 'ai' ? 'cruelty_free' : 'support_agent'; ?></span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-between items-center mb-1">
                            <h4 class="font-bold text-primary text-sm truncate"><?php echo $t['mode'] == 'ai' ? 'لئو (AI)' : 'پشتیبانی انسانی'; ?></h4>
                        </div>
                        <p class="text-[10px] text-on-surface-variant truncate flex justify-between">
                            <span>تیکت #<?php echo $t['id']; ?></span>
                            <span class="<?php echo $t['status'] == 'open' ? 'text-emerald-500 font-bold' : 'text-outline'; ?>"><?php echo $t['status'] == 'open' ? 'باز' : 'بسته'; ?></span>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area (Main) -->
        <div class="workstation-module rounded-[2.5rem] overflow-hidden flex flex-col h-full col-span-1 md:col-span-3 shadow-2xl border-none bg-surface-container-lowest relative">
        <!-- Chat Header -->
        <div class="bg-white border-b border-outline-variant/20 px-6 py-4 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center gap-4">
                <!-- Mobile Sidebar Toggle -->
                <button type="button" onclick="toggleChatSidebar()" class="md:hidden w-10 h-10 rounded-full bg-primary-container/10 text-primary-container hover:bg-primary-container hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined">menu_open</span>
                </button>
                <a href="user_tickets.php" class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
                <div class="relative">
                    <div class="w-14 h-14 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container border-2 border-primary-container">
                        <span class="material-symbols-outlined text-3xl"><?php echo $mode === 'ai' ? 'cruelty_free' : 'support_agent'; ?></span>
                    </div>
                    <?php if($ticket['status'] === 'open'): ?>
                    <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white"></div>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-primary"><?php echo $mode === 'ai' ? 'لئو (Leo)' : 'پشتیبانی یکپارچه'; ?></h3>
                    <p class="text-xs text-on-surface-variant flex items-center gap-1">
                        <?php if($ticket['status'] === 'open'): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        آنلاین
                        <?php else: ?>
                        بسته شده
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-outline-variant">تیکت #<?php echo $ticket_id; ?></span>
            </div>
        </div>
        
        <!-- Chat Body -->
        <div class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar bg-surface-container-lowest relative" id="chat-messages">
            <div class="flex justify-center mb-8">
                <div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">تاریخچه مکالمه</div>
            </div>
            <!-- Messages will be injected here via JS -->
        </div>
        
        <!-- Loading Indicator -->
        <div id="chat-typing" class="px-6 py-2 bg-surface-container-lowest hidden items-center gap-2 text-xs text-on-surface-variant font-medium">
            <div class="flex gap-1">
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <span><?php echo $mode === 'ai' ? 'لئو در حال تایپ است...' : 'پشتیبان در حال پاسخگویی است...'; ?></span>
        </div>

        <!-- Image Preview Overlay -->
        <div id="image-preview-container" class="hidden px-6 py-4 bg-surface-container border-t border-outline-variant/20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img id="image-preview" src="" class="w-16 h-16 object-cover rounded-lg shadow-sm border border-outline-variant/30">
                <div class="text-xs font-bold text-primary">تصویر ضمیمه شد</div>
            </div>
            <button type="button" onclick="clearImage()" class="w-8 h-8 bg-error/10 text-error rounded-full flex items-center justify-center hover:bg-error hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>

        <!-- Chat Footer (Input Bar) -->
        <?php if($ticket['status'] === 'open'): ?>
        <div class="p-4 bg-white border-t border-outline-variant/20 z-10">
            <form id="chat-form" class="flex items-center gap-3 relative" onsubmit="sendChatMessage(event)">
                <input type="file" id="chat-image-input" class="hidden" accept="image/*" onchange="handleImageSelect(this)">
                <button type="button" onclick="document.getElementById('chat-image-input').click()" class="w-12 h-12 rounded-full hover:bg-primary-container/10 text-on-surface-variant hover:text-primary-container flex items-center justify-center transition-colors shrink-0">
                    <span class="material-symbols-outlined text-2xl">attach_file</span>
                </button>
                
                <div class="flex-1 relative">
                    <input id="chat-input" class="w-full bg-surface-container-low border-none rounded-full pl-14 pr-6 py-4 focus:ring-2 focus:ring-primary-container transition-all text-sm font-medium" placeholder="پیام خود را بنویسید..." type="text" autocomplete="off" />
                    <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full hover:bg-black/5 flex items-center justify-center text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined">sentiment_satisfied</span>
                    </button>
                </div>
                
                <button type="submit" id="chat-send-btn" class="w-14 h-14 bg-primary-container text-white rounded-full hover:scale-105 hover:bg-primary transition-all flex items-center justify-center shadow-lg shrink-0">
                    <span class="material-symbols-outlined text-2xl -ml-1">send</span>
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="p-6 bg-surface-container-low border-t border-outline-variant/20 z-10 text-center flex flex-col items-center gap-4">
            <p class="text-sm font-bold text-on-surface-variant">این گفتگو بسته شده است.</p>
            <button type="button" onclick="reopenTicket()" class="bg-primary text-white px-6 py-2 rounded-xl font-bold hover:bg-primary-container hover:scale-105 transition-all shadow-md">
                باز کردن مجدد گفتگو
            </button>
        </div>
        <?php endif; ?>
        </div>
    </div>
</main>

<script>
const currentTicketId = <?php echo $ticket_id; ?>;
const chatMode = '<?php echo $mode; ?>';
let lastMessageId = 0;
let chatPollingInterval = null;

function fetchMessages() {
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentTicketId);
    fd.append('last_id', lastMessageId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
                document.getElementById('chat-typing').style.display = 'none';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    
    messages.forEach(msg => {
        const isUser = msg.sender_type === 'user';
        const avatar = chatMode === 'ai' ? 'cruelty_free' : 'support_agent';
        
        let imgHtml = '';
        if (msg.image_url) {
            const safeImgUrl = escapeHtml(msg.image_url);
            imgHtml = `<img src="${safeImgUrl}" class="rounded-xl mb-3 max-w-[200px] h-auto cursor-pointer border border-outline-variant/20" alt="ضمیمه چت">`;
        }

        const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');
        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });

        if (isUser) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                        ${imgHtml}
                        <div>${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-2 text-left w-full block">${time} <span class="material-symbols-outlined text-[10px] ml-0.5" style="vertical-align: middle">done_all</span></div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%]">
                    <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center shrink-0 border-2 border-white shadow-sm mt-auto">
                        <span class="material-symbols-outlined text-lg">${avatar}</span>
                    </div>
                    <div class="bg-white px-5 py-4 rounded-3xl rounded-br-sm shadow-md text-sm border border-outline-variant/10 leading-relaxed text-on-surface">
                        ${imgHtml}
                        <div class="markdown-body">${safeMessage}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-2 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
}

// --- Mobile Sidebar Toggle ---
function toggleChatSidebar() {
    const sidebar = document.getElementById('chat-sidebar');
    const backdrop = document.getElementById('chat-sidebar-backdrop');
    if (sidebar.classList.contains('translate-x-full')) {
        sidebar.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
    } else {
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('hidden');
    }
}

// Ensure chat messages stay scrolled to bottom
const chatMessages = document.getElementById('chat-messages');
chatMessages.scrollTop = chatMessages.scrollHeight;

function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('image-preview-container').classList.add('flex');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('chat-image-input').value = '';
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('image-preview-container').classList.remove('flex');
    document.getElementById('image-preview').src = '';
}

function sendChatMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('chat-input');
    const imageInput = document.getElementById('chat-image-input');
    const msg = input.value.trim();
    
    if (!msg && imageInput.files.length === 0) return;
    
    // Show Optimistic UI for User Message
    const container = document.getElementById('chat-messages');
    let imgHtml = '';
    if (imageInput.files.length > 0) {
        imgHtml = `<img src="${document.getElementById('image-preview').src}" class="rounded-xl mb-3 max-w-[200px] opacity-70">`;
    }
    
    const time = new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    
    container.insertAdjacentHTML('beforeend', `
        <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto opacity-70" id="temp-msg">
            <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                ${imgHtml}
                <div>${msg.replace(/\n/g, '<br>')}</div>
                <div class="text-[9px] text-white/70 mt-2 text-left w-full block"><span class="material-symbols-outlined text-[10px] animate-spin">sync</span></div>
            </div>
        </div>
    `);
    scrollToBottom();
    
    // Clear Inputs
    input.value = '';
    const file = imageInput.files[0];
    clearImage();
    
    // Show Typing Indicator
    if(chatMode === 'ai') document.getElementById('chat-typing').style.display = 'flex';
    scrollToBottom();
    
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('ticket_id', currentTicketId);
    fd.append('message', msg);
    if (file) {
        fd.append('image', file);
    }
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('temp-msg')?.remove();
                fetchMessages(); // will clear typing indicator
            }
        });
}

function reopenTicket() {
    if(!confirm("آیا از باز کردن مجدد این گفتگو اطمینان دارید؟")) return;
    
    const fd = new FormData();
    fd.append('action', 'reopen');
    fd.append('ticket_id', currentTicketId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert('خطا در باز کردن مجدد گفتگو');
            }
        });
}

document.addEventListener('DOMContentLoaded', () => {
    fetchMessages();
    chatPollingInterval = setInterval(fetchMessages, 3000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
