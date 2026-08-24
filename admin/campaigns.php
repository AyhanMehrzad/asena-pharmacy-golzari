<?php
$currentPage = 'campaigns';
require_once 'includes/admin_header.php';

// Handle Add/Edit Campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../includes/functions.php';
    csrf_verify();
    
    $action = $_POST['action'];
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO campaigns (title, description, goal_amount, image_url, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['title']), 
            trim($_POST['description']), 
            (int)$_POST['goal_amount'], 
            trim($_POST['image_url']),
            trim($_POST['status'])
        ]);
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE campaigns SET title=?, description=?, goal_amount=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([
            trim($_POST['title']), 
            trim($_POST['description']), 
            (int)$_POST['goal_amount'], 
            trim($_POST['image_url']),
            trim($_POST['status']),
            (int)$_POST['campaign_id']
        ]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id=?");
        $stmt->execute([(int)$_POST['campaign_id']]);
    }
    header("Location: campaigns.php");
    exit;
}

// Fetch Campaigns
$stmt = $pdo->query("SELECT * FROM campaigns ORDER BY created_at DESC");
$campaigns = $stmt->fetchAll();

// Statistics
$totalCampaigns = count($campaigns);
$activeCampaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
$totalCollected = array_sum(array_column($campaigns, 'current_amount'));
?>

<!-- Main Dashboard Canvas -->
<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Page Header & Quick Action -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-1">مدیریت کمپین‌های حمایتی</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">تعریف و پیگیری کمک‌های مردمی</p>
        </div>
        <button onclick="openModal('add')" class="flex items-center gap-2 bg-secondary-container text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl active:scale-95 transition-all">
            <span class="material-symbols-outlined">add</span>
            <span>ایجاد کمپین جدید</span>
        </button>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between">
            <div>
                <p class="text-label-sm text-on-surface-variant mb-1">کل کمپین‌ها</p>
                <p class="text-display-lg font-bold text-primary"><?= $totalCampaigns ?></p>
            </div>
            <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[32px]">campaign</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between">
            <div>
                <p class="text-label-sm text-status-active font-bold mb-1">کمپین‌های فعال</p>
                <p class="text-display-lg font-bold text-status-active"><?= $activeCampaigns ?></p>
            </div>
            <div class="w-12 h-12 bg-status-active/10 rounded-lg flex items-center justify-center text-status-active">
                <span class="material-symbols-outlined text-[32px]">play_circle</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex items-center justify-between group hover:border-secondary transition-colors">
            <div>
                <p class="text-label-sm text-secondary font-bold mb-1">مجموع جمع‌آوری شده</p>
                <p class="text-display-sm font-bold text-secondary"><?= number_format($totalCollected) ?> تومان</p>
            </div>
            <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[32px]">volunteer_activism</span>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-surface-container-low border-b border-outline-variant/50">
                    <tr>
                        <th class="px-6 py-4 text-sm font-bold text-primary">عنوان کمپین</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">هدف مبلغ (تومان)</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">مبلغ جمع‌شده</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">وضعیت</th>
                        <th class="px-6 py-4 text-sm font-bold text-primary">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($campaigns as $camp): ?>
                    <tr class="hover:bg-surface-container-lowest transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg border border-outline-variant/20 overflow-hidden flex-shrink-0">
                                    <img class="w-full h-full object-cover" src="<?= htmlspecialchars($camp['image_url'] ?: 'https://placehold.co/100?text=No+Image') ?>" alt="Camp">
                                </div>
                                <div>
                                    <p class="font-label-lg text-label-lg text-primary"><?= htmlspecialchars($camp['title']) ?></p>
                                    <p class="text-[11px] text-on-surface-variant line-clamp-1"><?= htmlspecialchars(mb_substr($camp['description'], 0, 50)) ?>...</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-bold"><?= number_format($camp['goal_amount']) ?></td>
                        <td class="px-6 py-4 font-bold text-secondary-container"><?= number_format($camp['current_amount']) ?></td>
                        <td class="px-6 py-4">
                            <?php if ($camp['status'] === 'active'): ?>
                                <span class="px-3 py-1 rounded-full bg-status-active/10 text-status-active text-[11px] font-bold">فعال</span>
                            <?php elseif ($camp['status'] === 'completed'): ?>
                                <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-[11px] font-bold">تکمیل شده</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full bg-outline-variant/20 text-on-surface-variant text-[11px] font-bold">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button onclick='openModal("edit", <?= json_encode($camp) ?>)' class="p-2 text-on-surface-variant hover:text-primary transition-colors" title="ویرایش">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <form method="POST" onsubmit="return confirm('آیا از حذف این کمپین اطمینان دارید؟');" class="inline m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                    <button type="submit" class="p-2 text-on-surface-variant hover:text-error transition-colors" title="حذف">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="campaignModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center">
            <h3 id="modalTitle" class="font-title-lg text-title-lg text-primary">ایجاد کمپین جدید</h3>
            <button onclick="closeModal()" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="campaignForm" method="POST" class="flex flex-col flex-1 h-full">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="form_action" value="add">
                <input type="hidden" name="campaign_id" id="form_campaign_id" value="">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1 col-span-2">
                        <label class="text-sm font-bold text-on-surface-variant">عنوان کمپین</label>
                        <input type="text" name="title" id="campTitle" required class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">هدف مبلغ (تومان)</label>
                        <input type="number" name="goal_amount" id="campGoal" required min="1000" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">وضعیت</label>
                        <select name="status" id="campStatus" required class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                            <option value="active">فعال</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                    </div>
                </div>
                
                <div class="space-y-1 mb-4">
                    <label class="text-sm font-bold text-on-surface-variant">آدرس تصویر (URL)</label>
                    <input type="url" name="image_url" id="campImage" class="w-full rounded-lg border-outline-variant text-left focus:ring-primary focus:border-primary" dir="ltr">
                </div>
                
                <div class="space-y-1 mb-4">
                    <label class="text-sm font-bold text-on-surface-variant">دلایل کمک / توضیحات کمپین</label>
                    <textarea name="description" id="campDesc" rows="4" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary" required></textarea>
                </div>
                
                <div class="pt-4 flex justify-end gap-3 border-t border-outline-variant/30">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg font-bold text-on-surface-variant hover:bg-surface-container transition-colors">انصراف</button>
                    <button type="submit" class="px-6 py-2 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(mode, camp = null) {
    const modal = document.getElementById('campaignModal');
    const form = document.getElementById('campaignForm');
    const title = document.getElementById('modalTitle');
    
    if (mode === 'edit' && camp) {
        title.innerText = 'ویرایش کمپین';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_campaign_id').value = camp.id;
        document.getElementById('campTitle').value = camp.title;
        document.getElementById('campGoal').value = camp.goal_amount;
        document.getElementById('campStatus').value = camp.status;
        document.getElementById('campImage').value = camp.image_url || '';
        document.getElementById('campDesc').value = camp.description || '';
    } else {
        title.innerText = 'ایجاد کمپین جدید';
        form.reset();
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_campaign_id').value = '';
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('campaignModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
