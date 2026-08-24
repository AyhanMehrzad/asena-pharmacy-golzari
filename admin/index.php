<?php
$currentPage = 'dashboard';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Handle Event Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['action']) && $_POST['action'] === 'add_event') {
        $title = trim($_POST['title']);
        $time = trim($_POST['time']);
        $color = $_POST['color'] ?? 'primary';
        if (!empty($title) && !empty($time)) {
            $stmt = $pdo->prepare("INSERT INTO dashboard_events (title, event_time, color) VALUES (?, ?, ?)");
            $stmt->execute([$title, $time, $color]);
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_event') {
        $event_id = (int)$_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM dashboard_events WHERE id = ?");
        $stmt->execute([$event_id]);
    }
    header("Location: index.php");
    exit;
}

// Fetch dynamic data for dashboard

// 1. Today's appointments
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(status='approved') as approved, SUM(status='pending') as pending FROM appointments WHERE appointment_date = CURDATE()");
$appt_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Active subscriptions
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent FROM subscriptions WHERE status = 'active'");
$sub_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Loyalty points (mocking gifts)
$stmt = $pdo->query("SELECT SUM(loyalty_points) as total_points, COUNT(*) as users_count FROM users WHERE loyalty_points > 0");
$loyalty_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Inventory alerts
$stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock <= 5");
$inv_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. Live clinic status
$stmt = $pdo->query("
    SELECT a.*, d.name as doctor_name, u.name as owner_name 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    JOIN users u ON a.user_id = u.id 
    WHERE a.appointment_date = CURDATE() 
    ORDER BY a.appointment_time ASC LIMIT 5
");
$live_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Top selling / low stock product (Needs attention)
$stmt = $pdo->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 1");
$top_product = $stmt->fetch(PDO::FETCH_ASSOC);

// 7. Dashboard Events
$stmt = $pdo->query("SELECT * FROM dashboard_events ORDER BY event_time ASC");
$dashboard_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Dashboard Content Container -->
<div class="p-4 md:p-8 space-y-6 md:space-y-8">
    <!-- Section 1: Overview Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card: Appointments -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-primary-container transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-primary-container/10 flex items-center justify-center text-primary-container">
                    <span class="material-symbols-outlined text-[32px]">calendar_today</span>
                </div>
                <span class="text-status-active font-label-sm flex items-center gap-1">
                    امروز
                </span>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">نوبت‌های امروز</h3>
            <p class="font-display-lg text-display-lg text-primary mt-1"><?= (int)$appt_stats['total'] ?></p>
            <p class="text-label-sm text-on-surface-variant mt-2"><?= (int)$appt_stats['approved'] ?> نوبت تایید شده، <?= (int)$appt_stats['pending'] ?> در انتظار</p>
        </div>

        <!-- Card: Subscriptions -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-secondary-container transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-secondary-fixed/30 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[32px]">autorenew</span>
                </div>
                <span class="text-status-active font-label-sm flex items-center gap-1">
                    ۵٪+ <span class="material-symbols-outlined text-sm">trending_up</span>
                </span>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">اشتراک‌های فعال (شارژ خودکار)</h3>
            <p class="font-display-lg text-display-lg text-primary mt-1"><?= number_format((int)$sub_stats['total']) ?></p>
            <p class="text-label-sm text-on-surface-variant mt-2"><?= (int)$sub_stats['recent'] ?> اشتراک جدید در این هفته</p>
        </div>

        <!-- Card: Donations -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-primary-container transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-tertiary-fixed/30 flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined text-[32px]">volunteer_activism</span>
                </div>
                <span class="text-status-active font-label-sm flex items-center gap-1">
                    فعال
                </span>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">مجموع امتیاز وفاداری کاربران</h3>
            <div class="flex items-baseline gap-2 mt-1">
                <p class="font-display-lg text-display-lg text-primary"><?= number_format((int)$loyalty_stats['total_points']) ?></p>
                <span class="text-label-lg text-on-surface-variant">امتیاز</span>
            </div>
            <p class="text-label-sm text-on-surface-variant mt-2">از سمت <?= (int)$loyalty_stats['users_count'] ?> کاربر وفادار</p>
        </div>

        <!-- Card: Inventory -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-error transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-error-container/50 flex items-center justify-center text-error">
                    <span class="material-symbols-outlined text-[32px]">warning</span>
                </div>
                <?php if($inv_stats['low_stock'] > 0): ?>
                    <span class="text-error font-bold font-label-sm">بحرانی</span>
                <?php else: ?>
                    <span class="text-status-active font-bold font-label-sm">ایمن</span>
                <?php endif; ?>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">هشدار موجودی انبار</h3>
            <p class="font-display-lg text-display-lg text-primary mt-1"><?= (int)$inv_stats['low_stock'] ?></p>
            <p class="text-label-sm text-on-surface-variant mt-2">کالاهای زیر حد نصاب ایمنی</p>
        </div>
    </div>

    <!-- Main Layout Grid: Clinic Status & Charity Impact -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Clinic Status (2/3 Column) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-5 border-b border-outline-variant/20 flex justify-between items-center">
                    <h2 class="font-title-lg text-title-lg text-primary">وضعیت زنده کلینیک</h2>
                    <a href="clinic_management.php" class="text-primary-container font-label-lg hover:underline">مشاهده تقویم کامل</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead class="bg-surface-container-low text-on-surface-variant font-label-sm">
                            <tr>
                                <th class="px-6 py-3">صاحب حیوان</th>
                                <th class="px-6 py-3">نوع حیوان</th>
                                <th class="px-6 py-3">دامپزشک</th>
                                <th class="px-6 py-3">زمان نوبت</th>
                                <th class="px-6 py-3">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php if (empty($live_appointments)): ?>
                                <tr><td colspan="5" class="px-6 py-4 text-center text-on-surface-variant">هیچ نوبتی برای امروز ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($live_appointments as $apt): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-primary scale-75">person</span>
                                                </div>
                                                <div>
                                                    <p class="font-label-lg text-primary"><?= htmlspecialchars($apt['owner_name']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-body-md"><?= htmlspecialchars($apt['pet_type']) ?></td>
                                        <td class="px-6 py-4 font-body-md text-on-surface-variant"><?= htmlspecialchars($apt['doctor_name']) ?></td>
                                        <td class="px-6 py-4 font-body-md text-primary font-bold" dir="ltr"><?= substr($apt['appointment_time'], 0, 5) ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            $bg = 'bg-surface-variant text-on-surface-variant';
                                            $dot = 'bg-outline';
                                            $label = 'نامشخص';
                                            if ($apt['status'] == 'pending') {
                                                $bg = 'bg-status-warning/20 text-status-warning';
                                                $dot = 'bg-status-warning';
                                                $label = 'در انتظار';
                                            } elseif ($apt['status'] == 'approved') {
                                                $bg = 'bg-primary-fixed text-on-primary-fixed';
                                                $dot = 'bg-primary';
                                                $label = 'تایید شده';
                                            } elseif ($apt['status'] == 'completed') {
                                                $bg = 'bg-status-active/20 text-status-active';
                                                $dot = 'bg-status-active';
                                                $label = 'تکمیل شده';
                                            }
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $bg ?> text-label-sm font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full <?= $dot ?>"></span> <?= $label ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inventory Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30">
                    <h2 class="font-title-lg text-title-lg text-primary mb-6">آیتم نیازمند توجه در انبار</h2>
                    <div class="space-y-4">
                        <?php if($top_product): ?>
                            <?php 
                            $img_src = $top_product['image_url'];
                            if (!str_starts_with($img_src, 'http')) {
                                $img_src = '../' . $img_src;
                            }
                            ?>
                            <div class="flex items-center gap-4 group">
                                <div class="w-12 h-12 rounded bg-surface-container overflow-hidden">
                                    <img class="w-full h-full object-cover" src="<?= htmlspecialchars($img_src) ?>"/>
                                </div>
                                <div class="flex-1">
                                    <p class="font-label-lg text-primary"><?= htmlspecialchars($top_product['name']) ?></p>
                                    <p class="text-label-sm text-on-surface-variant"><?= number_format($top_product['price']) ?> تومان</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-label-lg text-error">موجود: <?= $top_product['stock'] ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-3 text-status-active bg-status-active/10 p-4 rounded-lg">
                                <span class="material-symbols-outlined">check_circle</span>
                                <p class="font-bold text-sm">تمامی کالاها دارای موجودی ایمن (بیشتر از ۵ عدد) هستند.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-primary-container p-6 rounded-xl stat-card-shadow flex flex-col justify-between text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="font-title-lg text-title-lg mb-2">مدیریت سریع موجودی</h3>
                        <p class="font-body-md opacity-80 mb-6">به‌روزرسانی لحظه‌ای انبار و ثبت ورود کالاهای جدید</p>
                        <a href="inventory.php" class="w-full py-3 bg-secondary-container text-white font-bold rounded-lg hover:bg-secondary transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">edit_square</span>
                            به‌روزرسانی انبار
                        </a>
                    </div>
                    <!-- Subtle abstract decoration -->
                    <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
                </div>
            </div>
        </div>

        <!-- Charity Impact (1/3 Column) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-surface-container-high/50 p-6 rounded-xl border border-outline-variant/20 relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3 text-primary">
                        <span class="material-symbols-outlined">event_note</span>
                        <h3 class="font-title-lg text-title-lg">رویدادهای امروز</h3>
                    </div>
                    <button onclick="document.getElementById('eventsModal').classList.remove('hidden')" class="text-on-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </button>
                </div>
                <ul class="space-y-4">
                    <?php if (empty($dashboard_events)): ?>
                        <li class="text-sm text-on-surface-variant">هیچ رویدادی ثبت نشده است.</li>
                    <?php else: ?>
                        <?php foreach($dashboard_events as $ev): ?>
                        <li class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-2 h-2 rounded-full bg-<?= $ev['color'] ?> ring-4 ring-<?= $ev['color'] ?>/10"></div>
                                <div class="w-0.5 h-full bg-outline-variant/30 mt-1"></div>
                            </div>
                            <div>
                                <p class="font-label-lg text-<?= $ev['color'] ?>"><?= htmlspecialchars($ev['title']) ?></p>
                                <p class="text-label-sm text-on-surface-variant">ساعت <?= htmlspecialchars($ev['event_time']) ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Manage Events Modal -->
<div id="eventsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="document.getElementById('eventsModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">مدیریت رویدادهای امروز</h2>
        
        <!-- List existing events -->
        <div class="mb-6 space-y-3">
            <h3 class="font-bold text-sm text-on-surface-variant mb-2">رویدادهای فعلی:</h3>
            <?php if (empty($dashboard_events)): ?>
                <p class="text-sm text-on-surface-variant">رویدادی وجود ندارد.</p>
            <?php else: ?>
                <?php foreach($dashboard_events as $ev): ?>
                    <div class="flex items-center justify-between bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
                        <div>
                            <p class="text-sm font-bold text-<?= $ev['color'] ?>"><?= htmlspecialchars($ev['title']) ?></p>
                            <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($ev['event_time']) ?></p>
                        </div>
                        <form action="index.php" method="POST" class="m-0" onsubmit="return confirm('آیا از حذف این رویداد اطمینان دارید؟');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" class="text-error hover:bg-error/10 p-1 rounded transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <hr class="border-outline-variant/30 mb-6">
        
        <!-- Add new event form -->
        <form action="index.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <h3 class="font-bold text-sm text-on-surface-variant mb-2">افزودن رویداد جدید:</h3>
            <input type="hidden" name="action" value="add_event">
            <div>
                <label class="block text-xs font-bold mb-1">عنوان رویداد</label>
                <input type="text" name="title" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary outline-none text-sm" placeholder="مثال: استراحت">
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold mb-1">ساعت (مانند 14:30)</label>
                    <input type="text" name="time" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary outline-none text-sm" placeholder="14:30" dir="ltr">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold mb-1">رنگ نمایشی</label>
                    <select name="color" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary outline-none text-sm">
                        <option value="primary">آبی (اصلی)</option>
                        <option value="secondary">نارنجی (ثانویه)</option>
                        <option value="tertiary">سبز (سوم)</option>
                        <option value="error">قرمز (خطا/مهم)</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-2 rounded-lg font-bold mt-2 hover:bg-primary transition-colors">ثبت رویداد جدید</button>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.stat-card-shadow').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
            card.style.transition = 'transform 0.3s ease';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
</script>

<?php require_once 'includes/admin_footer.php'; ?>
