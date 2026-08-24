<?php
session_start();
$currentPage = 'tickets';
require_once 'includes/admin_header.php';

// Auto-close tickets inactive for 24 hours
$pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Fetch all admin tickets
$stmt = $pdo->prepare("
    SELECT t.id, t.status, t.created_at, u.name, u.phone 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.mode = 'admin' 
    ORDER BY t.updated_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-on-surface">پشتیبانی یکپارچه</h2>
            <p class="text-on-surface-variant">مدیریت تیکت‌ها و پیام‌های کاربران</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/20 flex h-[700px] overflow-hidden">
        
        <!-- Tickets Sidebar -->
        <div class="w-1/3 border-l border-outline-variant/20 flex flex-col bg-surface-container-lowest">
            <div class="p-4 border-b border-outline-variant/20 bg-surface-container-lowest">
                <div class="relative">
                    <span class="absolute inset-y-0 right-3 flex items-center text-outline">
                        <span class="material-symbols-outlined">search</span>
                    </span>
                    <input class="w-full pr-10 pl-4 py-2 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="جستجوی کاربر یا شماره..." type="text"/>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php foreach($tickets as $t): ?>
                <button onclick="loadTicket(<?php echo $t['id']; ?>)" class="w-full text-right p-4 rounded-xl hover:bg-surface-container transition-colors flex items-center gap-4 focus:bg-primary-container/10 focus:text-primary group">
                    <div class="w-12 h-12 rounded-full bg-primary-container text-white flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-between items-center mb-1">
                            <h4 class="font-bold truncate text-sm"><?php echo htmlspecialchars($t['name'] ?: 'کاربر ' . $t['phone']); ?></h4>
                            <span class="text-[10px] text-outline whitespace-nowrap"><?php echo date('H:i', strtotime($t['created_at'])); ?></span>
                        </div>
                        <p class="text-xs text-on-surface-variant truncate">
                            وضعیت: <?php echo $t['status'] == 'open' ? '<span class="text-emerald-500 font-bold">باز</span>' : 'بسته'; ?>
                        </p>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col bg-surface-container-low" id="chat-area">
            <!-- Empty State -->
            <div class="flex-1 flex flex-col items-center justify-center text-on-surface-variant opacity-50" id="empty-state">
                <span class="material-symbols-outlined text-6xl mb-4">forum</span>
                <p>یک تیکت را برای مشاهده پیام‌ها انتخاب کنید</p>
            </div>

            <!-- Active Chat -->
            <div class="hidden flex-1 flex flex-col h-full" id="active-chat">
                <!-- Header -->
                <div class="bg-white border-b border-outline-variant/20 px-6 py-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary-container text-white flex items-center justify-center">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-primary" id="active-user-name">کاربر</h3>
                            <p class="text-xs text-on-surface-variant">تیکت #<span id="active-ticket-id"></span></p>
                        </div>
                    </div>
                    <button class="px-4 py-2 text-sm font-bold text-error bg-error/10 rounded-lg hover:bg-error hover:text-white transition-colors">
                        بستن تیکت
                    </button>
                </div>
                
                <!-- Messages -->
                <div class="flex-1 p-6 space-y-4 overflow-y-auto custom-scrollbar" id="admin-chat-messages">
                    <!-- Messages injected here -->
                </div>

                <!-- Input -->
                <div class="p-4 bg-white border-t border-outline-variant/20">
                    <form id="admin-chat-form" class="flex items-center gap-3 relative" onsubmit="sendAdminMessage(event)">
                        <div class="flex-1 relative">
                            <input id="admin-chat-input" class="w-full bg-surface-container-low border-none rounded-xl pr-4 pl-4 py-4 focus:ring-2 focus:ring-primary transition-all text-sm font-medium" placeholder="پاسخ خود را بنویسید..." type="text" autocomplete="off" />
                        </div>
                        <button type="submit" class="w-14 h-14 bg-primary text-white rounded-xl hover:scale-105 hover:bg-primary-container transition-all flex items-center justify-center shadow-lg shrink-0">
                            <span class="material-symbols-outlined text-2xl -ml-1">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentAdminTicketId = null;
let adminLastMessageId = 0;
let adminPollingInterval = null;

function loadTicket(ticketId) {
    currentAdminTicketId = ticketId;
    adminLastMessageId = 0;
    
    document.getElementById('empty-state').classList.add('hidden');
    document.getElementById('active-chat').classList.remove('hidden');
    document.getElementById('active-chat').classList.add('flex');
    document.getElementById('active-ticket-id').innerText = ticketId;
    
    const msgContainer = document.getElementById('admin-chat-messages');
    msgContainer.innerHTML = '';
    
    if (adminPollingInterval) clearInterval(adminPollingInterval);
    fetchAdminMessages();
    adminPollingInterval = setInterval(fetchAdminMessages, 3000);
}

function fetchAdminMessages() {
    if (!currentAdminTicketId) return;
    
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentAdminTicketId);
    fd.append('last_id', adminLastMessageId);
    
    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderAdminMessages(data.messages);
                adminLastMessageId = data.messages[data.messages.length - 1].id;
                const container = document.getElementById('admin-chat-messages');
                container.scrollTop = container.scrollHeight;
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderAdminMessages(messages) {
    const container = document.getElementById('admin-chat-messages');
    
    messages.forEach(msg => {
        const isAdmin = msg.sender_type === 'admin';
        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
        const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');
        
        let imgHtml = '';
        if (msg.image_url) {
            const safeImg = escapeHtml(msg.image_url);
            imgHtml = `<img src="../${safeImg}" class="rounded-xl mb-3 max-w-[250px] border border-outline-variant/20 cursor-pointer" onclick="window.open(this.src)" alt="ضمیمه پیام">`;
        }

        if (isAdmin) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                        <div>${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-2 text-left w-full block">${time}</div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%]">
                    <div class="bg-white px-5 py-4 rounded-3xl rounded-br-sm shadow-md text-sm border border-outline-variant/10 leading-relaxed text-on-surface">
                        ${imgHtml}
                        <div>${safeMessage}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-2 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function sendAdminMessage(e) {
    e.preventDefault();
    if (!currentAdminTicketId) return;
    
    const input = document.getElementById('admin-chat-input');
    const msg = input.value.trim();
    if (!msg) return;
    
    input.value = '';
    
    const fd = new FormData();
    // Reusing the same endpoint, but we need to pass a special flag or just handle it. 
    // Since we check user_id in chat_action.php, wait, chat_action checks ticket_id vs user_id!
    // As an admin, user_id in session is the admin's user_id, NOT the ticket owner's user_id.
    // I need to update chat_action.php to allow admins to reply!
    
    fd.append('action', 'admin_send');
    fd.append('ticket_id', currentAdminTicketId);
    fd.append('message', msg);
    
    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                fetchAdminMessages();
            } else {
                alert('خطا در ارسال پیام');
            }
        });
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
