<?php
require_once '../includes/db.php'; // ensure we have db access

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header("Location: user_management.php");
    exit;
}

// Handle Document Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_doc') {
    $petId = $_POST['pet_id'];
    $docTitle = $_POST['title'];
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/clinical_docs/';
        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            $stmt = $pdo->prepare("INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$petId, $userId, $docTitle, $fileName, 'uploads/clinical_docs/' . $fileName]);
        }
    }
    header("Location: user_details.php?id=" . $userId);
    exit;
}

// Handle Pet Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_pet') {
    $petId = $_POST['pet_id'];
    $stmt = $pdo->prepare("DELETE FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$petId, $userId]);
    header("Location: user_details.php?id=" . $userId);
    exit;
}

// Handle User Edit
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    try {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $loyalty_points = $_POST['loyalty_points'] ?? 0;

        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ?, address = ?, role = ?, loyalty_points = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $email, $address, $role, $loyalty_points, $userId]);
        
        // Sync with doctors table if role is doctor
        if ($role === 'doctor') {
            $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
            $stmt->execute([$userId]);
            $doc = $stmt->fetch();
            
            if ($doc) {
                $stmt = $pdo->prepare("UPDATE doctors SET name = ?, phone = ? WHERE user_id = ?");
                $stmt->execute([$name, $phone, $userId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO doctors (user_id, name, phone, specialty) VALUES (?, ?, ?, 'عمومی')");
                $stmt->execute([$userId, $name, $phone]);
            }
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/doctors/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['profile_picture']['name']);
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fileName)) {
                    $dbPath = 'uploads/doctors/' . $fileName;
                    $stmt = $pdo->prepare("UPDATE doctors SET image_url = ? WHERE user_id = ?");
                    $stmt->execute([$dbPath, $userId]);
                }
            }
        }
        
        header("Location: user_details.php?id=" . $userId . "&success=edit");
        exit;
    } catch(PDOException $e) {
        $error = "خطا در بروزرسانی. ممکن است شماره تماس تکراری باشد.";
    }
}

$currentPage = 'users';
require_once 'includes/admin_header.php';

// Fetch User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: user_management.php");
    exit;
}

// Fetch Pets
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
$stmt->execute([$userId]);
$pets = $stmt->fetchAll();

// Fetch Documents
$stmt = $pdo->prepare("SELECT d.*, p.name as pet_name FROM pet_documents d LEFT JOIN user_pets p ON d.pet_id = p.id WHERE d.user_id = ? ORDER BY d.uploaded_at DESC");
$stmt->execute([$userId]);
$documents = $stmt->fetchAll();

// Fetch Orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Fetch Subscriptions
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll();

// Fetch Appointments
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC LIMIT 5");
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll();
?>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-4">
            <a href="user_management.php" class="w-10 h-10 rounded-full flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors text-on-surface-variant">
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary flex items-center gap-3">
                    <?= htmlspecialchars($user['name'] ?: 'کاربر بدون نام') ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="px-2 py-1 bg-primary text-white text-xs rounded-full font-label-sm">مدیر سیستم</span>
                    <?php endif; ?>
                </h2>
                <p class="font-body-md text-on-surface-variant" dir="ltr"><?= htmlspecialchars($user['phone']) ?></p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="document.getElementById('editUserModal').classList.remove('hidden');document.getElementById('editUserModal').classList.add('flex')" class="px-6 py-3 border border-outline-variant rounded-xl font-bold text-primary hover:bg-surface-container-low transition-colors">ویرایش پروفایل</button>
            <button class="px-6 py-3 bg-error text-white rounded-xl font-bold shadow-md hover:bg-error-container hover:text-error transition-colors">مسدودسازی کاربر</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-8 p-4 bg-error-container text-error rounded-xl font-bold text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'edit'): ?>
        <div class="mb-8 p-4 bg-status-active/20 text-status-active rounded-xl font-bold text-sm border border-status-active/30">
            پروفایل کاربر با موفقیت بروزرسانی شد.
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Right Column: User Info & Pets -->
        <div class="lg:col-span-1 space-y-8">
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 p-6">
                <h3 class="font-title-lg text-primary mb-4 border-b border-outline-variant/30 pb-3">اطلاعات کاربری</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-on-surface-variant mb-1">شماره تماس</p>
                        <p class="font-bold text-primary" dir="ltr"><?= htmlspecialchars($user['phone']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-on-surface-variant mb-1">ایمیل</p>
                        <p class="font-bold text-primary"><?= htmlspecialchars($user['email'] ?: 'ثبت نشده') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-on-surface-variant mb-1">امتیاز وفاداری</p>
                        <p class="font-bold text-secondary text-lg flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">stars</span>
                            <?= number_format($user['loyalty_points']) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-on-surface-variant mb-1">آدرس ثبت شده</p>
                        <p class="font-bold text-primary text-sm leading-relaxed"><?= htmlspecialchars($user['address'] ?: 'ثبت نشده') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-on-surface-variant mb-1">تاریخ عضویت</p>
                        <p class="font-bold text-primary text-sm" dir="ltr"><?= substr($user['created_at'], 0, 10) ?></p>
                    </div>
                </div>
            </div>

            <!-- Pets List -->
            <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 p-6">
                <div class="flex justify-between items-center mb-4 border-b border-outline-variant/30 pb-3">
                    <h3 class="font-title-lg text-primary">حیوانات خانگی</h3>
                    <button class="text-primary font-bold text-sm hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> افزودن
                    </button>
                </div>
                
                <?php if (empty($pets)): ?>
                    <p class="text-sm text-on-surface-variant text-center py-4">هیچ حیوان خانگی ثبت نشده است.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pets as $pet): ?>
                            <div class="flex items-center justify-between p-3 bg-surface-container-lowest border border-outline-variant/20 rounded-xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-primary-container/10 rounded-full flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">pets</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-primary"><?= htmlspecialchars($pet['name']) ?></p>
                                        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($pet['type'] . ' - ' . $pet['race']) ?></p>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('آیا از حذف این حیوان خانگی اطمینان دارید؟');">
                                    <input type="hidden" name="action" value="delete_pet">
                                    <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                                    <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center text-error bg-error-container/50 hover:bg-error hover:text-white transition-colors" title="حذف حیوان خانگی">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Left/Center Column: Activity, Documents, Subs -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Clinical Documents -->
            <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <div class="p-6 border-b border-outline-variant/30 flex justify-between items-center">
                    <h3 class="font-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">folder_special</span>
                        پرونده‌های پزشکی و بالینی
                    </h3>
                    <button onclick="document.getElementById('uploadModal').classList.remove('hidden');document.getElementById('uploadModal').classList.add('flex')" class="px-4 py-2 bg-primary-container text-white rounded-lg font-bold text-sm flex items-center gap-2 hover:brightness-110 transition-all">
                        <span class="material-symbols-outlined text-sm">upload_file</span>
                        آپلود سند جدید
                    </button>
                </div>
                
                <div class="p-6">
                    <?php if (empty($documents)): ?>
                        <div class="text-center py-8 bg-surface-container-low rounded-xl">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                            <p class="text-on-surface-variant font-bold">هیچ سندی موجود نیست.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($documents as $doc): ?>
                                <div class="flex items-start justify-between p-4 border border-outline-variant/30 rounded-xl hover:bg-surface-container-lowest transition-colors group">
                                    <div class="flex gap-3">
                                        <div class="w-10 h-10 bg-error/10 text-error rounded-lg flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined">description</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-primary text-sm"><?= htmlspecialchars($doc['title']) ?></p>
                                            <p class="text-[10px] text-on-surface-variant mb-1">مربوط به: <?= htmlspecialchars($doc['pet_name'] ?: 'نامشخص') ?></p>
                                            <p class="text-[10px] text-outline" dir="ltr"><?= substr($doc['uploaded_at'], 0, 10) ?></p>
                                        </div>
                                    </div>
                                    <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="w-8 h-8 rounded-full flex items-center justify-center text-primary bg-primary-fixed/30 hover:bg-primary hover:text-white transition-colors">
                                        <span class="material-symbols-outlined text-sm">download</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 p-6">
                <h3 class="font-title-lg text-primary mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">autorenew</span>
                    اشتراک‌های فعال (Autoship)
                </h3>
                <?php if (empty($subscriptions)): ?>
                    <p class="text-sm text-on-surface-variant">اشتراک فعالی وجود ندارد.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($subscriptions as $sub): ?>
                            <div class="flex justify-between items-center p-4 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-bold text-primary">اشتراک شناسه #<?= $sub['id'] ?></p>
                                        <?php if ($sub['status'] === 'active'): ?>
                                            <span class="px-2 py-0.5 bg-status-active/20 text-status-active text-[10px] rounded-full font-bold">فعال</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-status-paused/20 text-status-paused text-[10px] rounded-full font-bold">متوقف</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-on-surface-variant">ارسال بعدی: <span class="font-bold" dir="ltr"><?= $sub['next_delivery_date'] ?></span></p>
                                </div>
                                <button class="text-primary font-bold text-sm hover:underline">مدیریت</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <div class="p-6 border-b border-outline-variant/30 flex justify-between items-center">
                    <h3 class="font-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">shopping_bag</span>
                        سفارشات اخیر
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30">
                            <tr>
                                <th class="px-6 py-3 font-bold text-on-surface-variant">شماره سفارش</th>
                                <th class="px-6 py-3 font-bold text-on-surface-variant">مبلغ (تومان)</th>
                                <th class="px-6 py-3 font-bold text-on-surface-variant">وضعیت</th>
                                <th class="px-6 py-3 font-bold text-on-surface-variant">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php if(empty($orders)): ?>
                                <tr><td colspan="4" class="px-6 py-4 text-center text-on-surface-variant">سفارشی یافت نشد</td></tr>
                            <?php else: ?>
                                <?php foreach($orders as $order): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-6 py-3 font-bold text-primary">#<?= $order['id'] ?></td>
                                        <td class="px-6 py-3 font-bold"><?= number_format($order['total_amount']) ?></td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-1 bg-surface-container text-on-surface-variant text-[11px] rounded-full font-bold">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-on-surface-variant" dir="ltr"><?= substr($order['created_at'], 0, 10) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center">
            <h3 class="font-title-lg text-primary">آپلود سند پزشکی</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden');document.getElementById('uploadModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <?php if (empty($pets)): ?>
                <div class="bg-error-container text-on-error-container p-4 rounded-lg font-bold text-sm mb-4">
                    این کاربر هنوز حیوان خانگی ثبت نکرده است. لطفا ابتدا یک حیوان خانگی ثبت کنید.
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="upload_doc">
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">انتخاب حیوان خانگی</label>
                        <select name="pet_id" required class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">عنوان سند</label>
                        <input type="text" name="title" required placeholder="مثال: نتیجه آزمایش خون دی ۱۴۰۲" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">فایل ضمیمه</label>
                        <input type="file" name="document" required class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-white hover:file:bg-primary transition-all">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full py-3 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">آپلود فایل</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-white z-10 shrink-0">
            <h3 class="font-title-lg text-primary">ویرایش پروفایل کاربر</h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden');document.getElementById('editUserModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="edit_user">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">نام و نام خانوادگی</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">شماره تماس</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" dir="ltr" required class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">ایمیل</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" dir="ltr" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">نقش کاربری</label>
                        <select name="role" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                            <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>کاربر عادی</option>
                            <option value="admin" <?= ($user['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>مدیر سیستم</option>
                            <option value="doctor" <?= ($user['role'] ?? 'user') === 'doctor' ? 'selected' : '' ?>>پزشک</option>
                        </select>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-on-surface-variant">امتیاز وفاداری</label>
                        <input type="number" name="loyalty_points" value="<?= htmlspecialchars($user['loyalty_points'] ?? 0) ?>" required class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-on-surface-variant">آدرس</label>
                    <textarea name="address" rows="3" class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div class="space-y-1">
                    <label class="text-sm font-bold text-on-surface-variant">تصویر پروفایل (برای پزشکان)</label>
                    <input type="file" name="profile_picture" class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-white hover:file:bg-primary transition-all border border-outline-variant rounded-lg p-2">
                </div>
                
                <div class="pt-4 flex gap-3">
                    <button type="submit" class="flex-1 py-3 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">ذخیره تغییرات</button>
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden');document.getElementById('editUserModal').classList.remove('flex')" class="flex-1 py-3 rounded-lg font-bold border border-outline-variant text-on-surface-variant hover:bg-surface-container-low transition-colors">انصراف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
