<?php
$currentPage = 'clinic';
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../includes/functions.php';
    csrf_verify();

    $action = $_POST['action'];
    
    if ($action === 'add_doctor') {
        $user_id = (int)$_POST['user_id'];
        $specialty = trim(strip_tags($_POST['specialty']));
        $price = (int)$_POST['price'];
        
        // Fetch user info to get name
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'doctor'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $image_url = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/doctors/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                $image_url = 'uploads/doctors/' . $fileName;
            }
        }
        
        $baseline_rating = !empty($_POST['baseline_rating']) ? (float)$_POST['baseline_rating'] : 4.9;
        if ($user) {
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, name, specialty, price, rating, review_count, image_url, baseline_rating) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
            if ($stmt->execute([$user_id, $user['name'], $specialty, $price, $baseline_rating, $image_url, $baseline_rating])) {
                $doc_new_id = $pdo->lastInsertId();
                recalculate_bayesian_rating($pdo, 'doctor', $doc_new_id);
                $success = "پزشک جدید با موفقیت اضافه شد.";
            } else {
                $error = "خطا در افزودن پزشک.";
            }
        } else {
            $error = "کاربر نامعتبر است یا نقش پزشک ندارد.";
        }
    } elseif ($action === 'edit_doctor') {
        $doc_id = (int)$_POST['doctor_id'];
        $specialty = trim(strip_tags($_POST['specialty']));
        $price = (int)$_POST['price'];
        $baseline_rating = !empty($_POST['baseline_rating']) ? (float)$_POST['baseline_rating'] : 4.9;
        
        $image_url = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/doctors/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                $image_url = 'uploads/doctors/' . $fileName;
            }
        }
        
        if ($image_url) {
            $stmt = $pdo->prepare("UPDATE doctors SET specialty = ?, price = ?, image_url = ?, baseline_rating = ? WHERE id = ?");
            $res = $stmt->execute([$specialty, $price, $image_url, $baseline_rating, $doc_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE doctors SET specialty = ?, price = ?, baseline_rating = ? WHERE id = ?");
            $res = $stmt->execute([$specialty, $price, $baseline_rating, $doc_id]);
        }
        recalculate_bayesian_rating($pdo, 'doctor', $doc_id);
        
        if ($res) {
            $success = "اطلاعات پزشک بروزرسانی شد.";
        } else {
            $error = "خطا در بروزرسانی اطلاعات.";
        }
    } elseif ($action === 'delete_doctor') {
        $doc_id = (int)$_POST['doctor_id'];
        $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
        if ($stmt->execute([$doc_id])) {
            $success = "پزشک با موفقیت حذف شد.";
        } else {
            $error = "خطا در حذف پزشک.";
        }
    }
}

// Fetch Doctors
$stmt = $pdo->query("SELECT * FROM doctors ORDER BY created_at DESC");
$doctors = $stmt->fetchAll();

// Fetch Eligible Users for 'Add Doctor' (users with role='doctor' but no profile yet)
$stmt = $pdo->query("
    SELECT id, name, phone 
    FROM users 
    WHERE role = 'doctor' 
    AND id NOT IN (SELECT user_id FROM doctors WHERE user_id IS NOT NULL)
");
$eligibleUsers = $stmt->fetchAll();

// Fetch Today's Appointments
$stmt = $pdo->query("
    SELECT a.*, u.name as user_name, u.phone, d.name as doctor_name 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    JOIN doctors d ON a.doctor_id = d.id 
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
");
$appointments = $stmt->fetchAll();
$totalAppointments = count($appointments);
$pendingAppointments = count(array_filter($appointments, fn($a) => $a['status'] === 'pending' || $a['status'] === 'در انتظار'));
?>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Status Messages -->
    <?php if ($success): ?>
        <div class="bg-status-active/10 text-status-active p-4 rounded-xl mb-6 text-body-md font-bold text-center border border-status-active/20">
            <?= $success ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-error/10 text-error p-4 rounded-xl mb-6 text-body-md font-bold text-center border border-error/20">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <header class="flex justify-between items-center mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary">مدیریت کلینیک</h2>
            <p class="text-on-surface-variant font-body-md mt-1">نظارت بر نوبت‌ها، پزشکان و پرونده‌های پزشکی</p>
        </div>
        <div class="flex gap-4">
            <button onclick="document.getElementById('addDoctorModal').classList.remove('hidden');document.getElementById('addDoctorModal').classList.add('flex')" class="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg font-label-lg hover:bg-primary-container transition-colors shadow-sm">
                <span class="material-symbols-outlined">add</span>
                افزودن پزشک جدید
            </button>
            <button class="flex items-center gap-2 bg-white border border-outline-variant px-4 py-2 rounded-lg font-label-lg text-on-surface-variant hover:bg-surface-container transition-colors shadow-sm">
                <span class="material-symbols-outlined">print</span>
                چاپ گزارش روزانه
            </button>
        </div>
    </header>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-12 gap-6">
        <!-- Column 1: Doctor Schedules & Queue -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
            
            <!-- Doctor Schedules Carousel -->
            <section class="bg-white rounded-2xl p-6 shadow-sm overflow-hidden border border-outline-variant/30">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-title-lg text-title-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">calendar_month</span>
                        پزشکان کلینیک
                    </h3>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-4 scroll-smooth">
                    <?php if(empty($doctors)): ?>
                        <p class="text-sm text-on-surface-variant">پزشکی یافت نشد.</p>
                    <?php else: ?>
                        <?php foreach($doctors as $doctor): ?>
                        <div class="min-w-[280px] bg-white border border-outline-variant rounded-xl p-4 flex flex-col gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-full bg-surface relative overflow-hidden flex-shrink-0">
                                    <img class="w-full h-full object-cover" src="<?= htmlspecialchars($doctor['image_url'] ?: 'https://placehold.co/150?text=Doctor') ?>" alt="Doctor">
                                </div>
                                <div>
                                    <h4 class="font-label-lg text-primary"><?= htmlspecialchars($doctor['name']) ?></h4>
                                    <span class="font-label-sm text-secondary bg-secondary-fixed px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($doctor['specialty']) ?></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-auto">
                                <div class="flex items-center gap-2 text-xs text-on-surface-variant bg-surface-container-low p-2 rounded-lg">
                                    <span class="material-symbols-outlined text-sm">star</span>
                                    <span>امتیاز: <?= $doctor['rating'] ?></span>
                                </div>
                                <div class="flex gap-1">
                                    <button onclick="openEditDoctorModal(<?= $doctor['id'] ?>, '<?= htmlspecialchars($doctor['specialty'], ENT_QUOTES) ?>', <?= $doctor['price'] ?>)" class="w-8 h-8 rounded bg-surface-container-low text-primary flex items-center justify-center hover:bg-primary-container hover:text-on-primary-container transition-colors">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('آیا از حذف این پزشک اطمینان دارید؟');" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_doctor">
                                        <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                                        <button type="submit" class="w-8 h-8 rounded bg-surface-container-low text-error flex items-center justify-center hover:bg-error-container hover:text-on-error-container transition-colors">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Appointments Table -->
            <section class="bg-white rounded-2xl shadow-sm overflow-hidden border border-outline-variant/30">
                <div class="p-6 border-b border-outline-variant bg-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h3 class="font-title-lg text-title-lg text-primary">لیست نوبت‌ها</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead class="bg-surface-container-lowest border-b border-outline-variant">
                            <tr>
                                <th class="px-6 py-4 font-label-lg text-on-surface-variant">بیمار</th>
                                <th class="px-6 py-4 font-label-lg text-on-surface-variant">مالک</th>
                                <th class="px-6 py-4 font-label-lg text-on-surface-variant">پزشک</th>
                                <th class="px-6 py-4 font-label-lg text-on-surface-variant">تاریخ و زمان</th>
                                <th class="px-6 py-4 font-label-lg text-on-surface-variant">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            <?php if(empty($appointments)): ?>
                                <tr><td colspan="5" class="px-6 py-4 text-center text-on-surface-variant">نوبتی یافت نشد.</td></tr>
                            <?php else: ?>
                                <?php foreach($appointments as $appt): ?>
                                <tr class="hover:bg-surface-container-low transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-primary-container/10 flex items-center justify-center">
                                                <span class="material-symbols-outlined text-primary">pets</span>
                                            </div>
                                            <div>
                                                <div class="font-label-lg text-primary"><?= htmlspecialchars($appt['pet_type']) ?></div>
                                                <div class="text-xs text-on-surface-variant"><?= htmlspecialchars($appt['pet_race']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-body-md"><?= htmlspecialchars($appt['user_name'] ?: 'بدون نام') ?></div>
                                        <div class="text-xs text-on-surface-variant" dir="ltr"><?= htmlspecialchars($appt['phone']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs px-2 py-1 rounded-md bg-surface-container text-on-surface-variant"><?= htmlspecialchars($appt['doctor_name']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 font-body-md font-bold text-primary">
                                        <?= substr($appt['appointment_date'], 0, 10) ?> - <?= htmlspecialchars($appt['appointment_time']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <?php 
                                            $st = strtolower($appt['status']);
                                            if ($st === 'pending' || $st === 'در انتظار') {
                                                echo '<div class="w-2 h-2 rounded-full bg-status-warning"></div>';
                                                echo '<span class="text-xs font-label-sm text-status-warning">در انتظار تایید</span>';
                                            } elseif ($st === 'cancelled' || $st === 'لغو شده' || $st === 'canceled') {
                                                echo '<div class="w-2 h-2 rounded-full bg-error"></div>';
                                                echo '<span class="text-xs font-label-sm text-error">لغو شده</span>';
                                            } elseif ($st === 'completed' || $st === 'انجام شده') {
                                                echo '<div class="w-2 h-2 rounded-full bg-status-active"></div>';
                                                echo '<span class="text-xs font-label-sm text-status-active">انجام شده</span>';
                                            } elseif ($st === 'confirmed' || $st === 'تایید شده') {
                                                echo '<div class="w-2 h-2 rounded-full bg-status-active"></div>';
                                                echo '<span class="text-xs font-label-sm text-status-active">تایید شده</span>';
                                            } else {
                                                echo '<div class="w-2 h-2 rounded-full bg-surface-variant"></div>';
                                                echo '<span class="text-xs font-label-sm text-on-surface-variant">' . htmlspecialchars($appt['status']) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Column 2: Stats (Right) -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm text-center">
                    <div class="w-10 h-10 mx-auto rounded-lg bg-primary/10 flex items-center justify-center mb-2">
                        <span class="material-symbols-outlined text-primary text-xl">medical_information</span>
                    </div>
                    <div class="text-on-surface-variant text-xs">کل نوبت‌ها</div>
                    <div class="text-xl font-bold text-primary mt-1"><?= $totalAppointments ?></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm text-center">
                    <div class="w-10 h-10 mx-auto rounded-lg bg-secondary/10 flex items-center justify-center mb-2">
                        <span class="material-symbols-outlined text-secondary text-xl">pending_actions</span>
                    </div>
                    <div class="text-on-surface-variant text-xs">در انتظار</div>
                    <div class="text-xl font-bold text-secondary mt-1"><?= $pendingAppointments ?></div>
                </div>
            </div>

            <!-- Medical Timelines / Recent Activity -->
            <section class="bg-white border border-outline-variant rounded-2xl p-6 shadow-sm">
                <h3 class="font-title-lg text-title-lg text-primary mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">history</span>
                    آخرین فعالیت‌های کلینیک
                </h3>
                <div class="space-y-6 relative before:absolute before:right-3.5 before:top-2 before:bottom-2 before:w-[1px] before:bg-outline-variant/50">
                    <div class="relative pr-8">
                        <div class="absolute right-0 top-1 w-7 h-7 rounded-full bg-status-active/10 border-2 border-white flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-status-active"></div>
                        </div>
                        <div class="font-label-lg text-primary">سیستم نوبت دهی</div>
                        <div class="text-xs text-on-surface-variant mt-1">نوبت‌دهی آنلاین با موفقیت همگام‌سازی شد.</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Add Doctor Modal -->
<div id="addDoctorModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-title-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">person_add</span>
                افزودن پزشک جدید
            </h3>
            <button type="button" onclick="document.getElementById('addDoctorModal').classList.add('hidden');document.getElementById('addDoctorModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="flex flex-col flex-1" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_doctor">
            
            <div class="p-6 overflow-y-auto space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">انتخاب کاربر</label>
                    <select name="user_id" required class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary p-2">
                        <option value="">-- انتخاب کاربر (نقش پزشک) --</option>
                        <?php foreach($eligibleUsers as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(empty($eligibleUsers)): ?>
                        <p class="text-xs text-error mt-1">هیچ کاربری با نقش پزشک که فاقد پروفایل باشد یافت نشد.</p>
                    <?php endif; ?>
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">تخصص</label>
                    <input type="text" name="specialty" required placeholder="مثال: دامپزشک عمومی" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary p-2">
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">هزینه ویزیت (تومان)</label>
                    <input type="number" name="price" required min="0" step="10000" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary p-2" dir="ltr">
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">تصویر پروفایل پزشک (اختیاری)</label>
                    <input type="file" name="profile_image" accept="image/*" class="w-full text-sm rounded-lg border border-outline-variant focus:ring-primary focus:border-primary p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                </div>
            </div>
            
            <div class="p-4 border-t border-outline-variant/30 bg-surface-container-lowest">
                <button type="submit" class="w-full py-2 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors" <?= empty($eligibleUsers) ? 'disabled' : '' ?>>ثبت پزشک</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div id="editDoctorModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-title-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">edit</span>
                ویرایش اطلاعات پزشک
            </h3>
            <button type="button" onclick="document.getElementById('editDoctorModal').classList.add('hidden');document.getElementById('editDoctorModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="flex flex-col flex-1" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_doctor">
            <input type="hidden" name="doctor_id" id="edit_doctor_id">
            
            <div class="p-6 overflow-y-auto space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">تخصص</label>
                    <input type="text" name="specialty" id="edit_specialty" required class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary p-2">
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">هزینه ویزیت (تومان)</label>
                    <input type="number" name="price" id="edit_price" required min="0" step="10000" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary p-2" dir="ltr">
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-on-surface-variant">تصویر پروفایل پزشک (اختیاری)</label>
                    <input type="file" name="profile_image" accept="image/*" class="w-full text-sm rounded-lg border border-outline-variant focus:ring-primary focus:border-primary p-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                    <p class="text-xs text-on-surface-variant mt-1">در صورت عدم انتخاب تصویر جدید، تصویر قبلی حفظ خواهد شد.</p>
                </div>
            </div>
            
            <div class="p-4 border-t border-outline-variant/30 bg-surface-container-lowest">
                <button type="submit" class="w-full py-2 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditDoctorModal(id, specialty, price) {
    document.getElementById('edit_doctor_id').value = id;
    document.getElementById('edit_specialty').value = specialty;
    document.getElementById('edit_price').value = price;
    
    document.getElementById('editDoctorModal').classList.remove('hidden');
    document.getElementById('editDoctorModal').classList.add('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
