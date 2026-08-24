<?php
require_once '../includes/db.php';
$currentPage = 'dashboard';

// Fetch doctor profile data early for POST handlers
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctorProfile = $stmt->fetch();
    $doctorId = $doctorProfile['id'] ?? 0;
} else {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle POST actions BEFORE headers are sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_schedule') {
        $schedule_data = [];
        $days = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
        foreach ($days as $day) {
            if (!empty($_POST["day_$day"])) { // checkbox is checked
                $schedule_data[$day] = [
                    'm_active' => isset($_POST["{$day}_m_active"]) ? true : false,
                    'm_start'  => $_POST["{$day}_m_start"] ?? '',
                    'm_end'    => $_POST["{$day}_m_end"] ?? '',
                    'a_active' => isset($_POST["{$day}_a_active"]) ? true : false,
                    'a_start'  => $_POST["{$day}_a_start"] ?? '',
                    'a_end'    => $_POST["{$day}_a_end"] ?? '',
                ];
            }
        }
        $schedule_info = json_encode($schedule_data);
        $stmt = $pdo->prepare("UPDATE doctors SET schedule_info = ? WHERE id = ?");
        if ($stmt->execute([$schedule_info, $doctorId])) {
            $success = "برنامه زمانی با موفقیت بروزرسانی شد.";
            $doctorProfile['schedule_info'] = $schedule_info;
        } else {
            $error = "خطا در بروزرسانی برنامه زمانی.";
        }
    } 
    elseif ($action === 'update_status') {
        $apptId = $_POST['appointment_id'];
        $status = $_POST['status'];
        if (in_array($status, ['approved', 'completed', 'cancelled'])) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
            if ($stmt->execute([$status, $apptId, $doctorId])) {
                $success = "وضعیت نوبت بروزرسانی شد.";
            } else {
                $error = "خطا در بروزرسانی وضعیت.";
            }
        }
    }
    elseif ($action === 'upload_doc') {
        $petId = $_POST['pet_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $docTitle = $_POST['title'];
        
        if ($petId && $userId && isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/clinical_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['document']['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$petId, $userId, $docTitle, $fileName, 'uploads/clinical_docs/' . $fileName])) {
                    $success = "سند بالینی با موفقیت آپلود شد.";
                } else {
                    $error = "خطا در ثبت سند در دیتابیس.";
                }
            } else {
                $error = "خطا در آپلود فایل.";
            }
        } else {
            $error = "اطلاعات ناقص است یا خطایی در فایل وجود دارد. (توجه: حیوان باید در سیستم ثبت شده باشد)";
        }
    }
}

require_once 'includes/doctor_header.php';

// Income Calculation (Completed/Approved)
$stmt = $pdo->prepare("SELECT COUNT(*) as total_visits FROM appointments WHERE doctor_id = ? AND status IN ('completed', 'approved')");
$stmt->execute([$doctorId]);
$visitStats = $stmt->fetch();
$totalIncome = $visitStats['total_visits'] * $doctorProfile['price'];

// Fetch Today's Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$doctorId]);
$todayAppts = $stmt->fetchAll();

// Fetch Upcoming Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date > CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$doctorId]);
$upcomingAppts = $stmt->fetchAll();

// Fetch History Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 50
");
$stmt->execute([$doctorId]);
$historyAppts = $stmt->fetchAll();

// Get unique pets for history modal (that have pet_id)
$stmt = $pdo->prepare("
    SELECT DISTINCT a.pet_id, a.pet_name, a.pet_type, a.user_id 
    FROM appointments a 
    WHERE a.doctor_id = ? AND a.pet_id IS NOT NULL
");
$stmt->execute([$doctorId]);
$doctorPets = $stmt->fetchAll();

// Fetch documents for the modal dynamically if requested
$documents = [];
if (isset($_GET['view_pet_history'])) {
    $histPetId = (int)$_GET['view_pet_history'];
    $stmt = $pdo->prepare("SELECT * FROM pet_documents WHERE pet_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$histPetId]);
    $documents = $stmt->fetchAll();
    
    // Also fetch past appointments for this pet
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE pet_id = ? AND doctor_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
    $stmt->execute([$histPetId, $doctorId]);
    $petPastAppts = $stmt->fetchAll();
}

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');

foreach ($todayAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
foreach ($upcomingAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
foreach ($historyAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
unset($appt);

?>

<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6 md:space-y-8">
    
    <?php if ($success): ?>
        <div class="bg-status-active/10 text-status-active p-4 rounded-xl font-bold flex items-center gap-2 border border-status-active/20">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $success ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-error/10 text-error p-4 rounded-xl font-bold flex items-center gap-2 border border-error/20">
            <span class="material-symbols-outlined">error</span>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Income Card -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-primary-container transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-primary-container/10 flex items-center justify-center text-primary-container">
                    <span class="material-symbols-outlined text-[32px]">account_balance_wallet</span>
                </div>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">درآمد کل تخمینی</h3>
            <p class="font-display-lg text-headline-lg text-primary mt-1"><?= number_format($totalIncome) ?> <span class="text-sm">تومان</span></p>
            <p class="text-label-sm text-on-surface-variant mt-2"><?= $visitStats['total_visits'] ?> نوبت تایید یا تکمیل شده</p>
        </div>
        
        <!-- Today's Appointments Count -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-secondary-container transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-secondary-fixed/30 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[32px]">today</span>
                </div>
            </div>
            <h3 class="font-label-lg text-label-lg text-on-surface-variant">نوبت‌های امروز</h3>
            <p class="font-display-lg text-headline-lg text-primary mt-1"><?= count($todayAppts) ?></p>
            <p class="text-label-sm text-on-surface-variant mt-2">نیاز به رسیدگی</p>
        </div>

        <!-- Schedule Update -->
        <div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-label-lg text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">schedule</span>
                    برنامه حضور شما
                </h3>
            </div>
            <?php
            $hasSchedule = false;
            if (!empty($doctorProfile['schedule_info'])) {
                $decoded = json_decode($doctorProfile['schedule_info'], true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $hasSchedule = true;
                }
            }
            ?>
            <div class="mt-2 text-center flex-1 flex flex-col justify-center">
                <?php if($hasSchedule): ?>
                    <p class="text-status-active text-sm font-bold flex items-center justify-center gap-1 mb-3">
                        <span class="material-symbols-outlined text-base">check_circle</span> برنامه تنظیم شده است
                    </p>
                <?php else: ?>
                    <p class="text-status-warning text-sm font-bold flex items-center justify-center gap-1 mb-3">
                        <span class="material-symbols-outlined text-base">warning</span> برنامه‌ای تنظیم نشده
                    </p>
                <?php endif; ?>
                <button onclick="document.getElementById('scheduleModal').classList.remove('hidden');document.getElementById('scheduleModal').classList.add('flex')" class="w-full bg-primary/10 hover:bg-primary hover:text-white text-primary font-bold py-2 rounded-lg transition-colors text-sm">
                    ویرایش برنامه هفتگی
                </button>
            </div>
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2/3: Today & Upcoming Appointments -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Today's Appointments -->
            <div class="bg-white rounded-xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-5 border-b border-outline-variant/20 flex justify-between items-center bg-primary/5">
                    <h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">event_available</span>
                        نوبت‌های امروز
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant font-label-sm">
                            <tr>
                                <th class="px-6 py-3">حیوان</th>
                                <th class="px-6 py-3">صاحب حیوان</th>
                                <th class="px-6 py-3">زمان</th>
                                <th class="px-6 py-3">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php if (empty($todayAppts)): ?>
                                <tr><td colspan="4" class="px-6 py-8 text-center text-on-surface-variant">هیچ نوبتی برای امروز ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($todayAppts as $appt): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer" onclick="openAppointmentDetails(this)" data-appt="<?= htmlspecialchars(json_encode($appt), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                                                    <span class="material-symbols-outlined text-lg">pets</span>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-primary"><?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?></p>
                                                    <p class="text-[11px] text-on-surface-variant">
                                                        <?= !empty($appt['pet_race']) ? htmlspecialchars($appt['pet_race']) : 'نژاد نامشخص' ?>
                                                        <br>
                                                        <?= !empty($appt['pet_gender']) ? htmlspecialchars($appt['pet_gender']) : '' ?> 
                                                        <?= !empty($appt['pet_age']) ? ' | ' . htmlspecialchars($appt['pet_age']) : '' ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-on-surface"><?= htmlspecialchars($appt['user_name'] ?: 'بدون نام') ?></p>
                                            <p class="text-[11px] text-on-surface-variant" dir="ltr"><?= htmlspecialchars($appt['phone']) ?></p>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-primary" dir="ltr"><?= htmlspecialchars($appt['appointment_time']) ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-2 items-center">
                                                <!-- Status Update Form -->
                                                <?php if ($appt['status'] !== 'completed' && $appt['status'] !== 'cancelled'): ?>
                                                    <form method="POST" class="flex gap-1" onclick="event.stopPropagation();">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                                        <?php if ($appt['status'] === 'pending' || $appt['status'] === 'در انتظار'): ?>
                                                            <button type="submit" name="status" value="approved" class="px-3 py-1 bg-status-active/10 text-status-active hover:bg-status-active hover:text-white rounded-lg text-xs font-bold transition-colors">تایید</button>
                                                        <?php else: ?>
                                                            <button type="submit" name="status" value="completed" class="px-3 py-1 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg text-xs font-bold transition-colors">تکمیل</button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="status" value="cancelled" onclick="return confirm('آیا از لغو این نوبت اطمینان دارید؟');" class="px-3 py-1 bg-error/10 text-error hover:bg-error hover:text-white rounded-lg text-xs font-bold transition-colors">لغو</button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php 
                                                    $badgeClass = $appt['status'] === 'completed' ? 'bg-primary/20 text-primary' : 'bg-error/20 text-error';
                                                    $statusText = $appt['status'] === 'completed' ? 'تکمیل شده' : 'لغو شده';
                                                    ?>
                                                    <span class="px-3 py-1 rounded-lg text-xs font-bold <?= $badgeClass ?>"><?= $statusText ?></span>
                                                <?php endif; ?>
                                                
                                                <!-- Action Buttons -->
                                                <?php if ($appt['pet_id']): ?>
                                                    <button onclick="event.stopPropagation(); openUploadModal(<?= $appt['pet_id'] ?>, <?= $appt['user_id'] ?>, '<?= htmlspecialchars(addslashes($appt['pet_name'] ?: $appt['pet_type'])) ?>')" class="w-8 h-8 rounded-full flex items-center justify-center text-primary bg-primary/5 hover:bg-primary hover:text-white transition-colors" title="آپلود سند بالینی">
                                                        <span class="material-symbols-outlined text-sm">upload_file</span>
                                                    </button>
                                                    <a href="?view_pet_history=<?= $appt['pet_id'] ?>" onclick="event.stopPropagation();" class="w-8 h-8 rounded-full flex items-center justify-center text-secondary bg-secondary/5 hover:bg-secondary hover:text-white transition-colors" title="مشاهده تاریخچه">
                                                        <span class="material-symbols-outlined text-sm">history</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-5 border-b border-outline-variant/20">
                    <h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">calendar_month</span>
                        نوبت‌های پیش‌رو
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant font-label-sm">
                            <tr>
                                <th class="px-6 py-3">تاریخ و زمان</th>
                                <th class="px-6 py-3">حیوان</th>
                                <th class="px-6 py-3">صاحب حیوان</th>
                                <th class="px-6 py-3">وضعیت / عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php if (empty($upcomingAppts)): ?>
                                <tr><td colspan="4" class="px-6 py-6 text-center text-on-surface-variant">هیچ نوبتی برای روزهای آینده ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($upcomingAppts as $appt): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer" onclick="openAppointmentDetails(this)" data-appt="<?= htmlspecialchars(json_encode($appt), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="px-6 py-3">
                                            <div class="flex flex-col gap-1 items-end">
                                                <span class="font-bold text-primary" dir="ltr"><?= $fmtDate->format(new DateTime($appt['appointment_date'])) ?></span>
                                                <span class="text-xs text-on-surface-variant bg-surface-container-low px-2 py-0.5 rounded" dir="ltr">ساعت <?= substr($appt['appointment_time'], 0, 5) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 font-bold text-on-surface">
                                            <?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?> <span class="text-xs text-on-surface-variant font-normal"><?= !empty($appt['pet_race']) ? '(' . htmlspecialchars($appt['pet_race']) . ')' : '' ?></span>
                                        </td>
                                        <td class="px-6 py-3 text-xs"><?= htmlspecialchars($appt['user_name']) ?></td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-2 justify-end">
                                                <?php if ($appt['status'] === 'pending' || $appt['status'] === 'در انتظار'): ?>
                                                    <span class="px-2 py-1 bg-status-warning/20 text-status-warning rounded-md text-[10px] font-bold">در انتظار</span>
                                                <?php else: ?>
                                                    <?php 
                                                    $status_fa = $appt['status'];
                                                    $badgeClass = 'bg-outline-variant/20 text-on-surface-variant';
                                                    if ($appt['status'] === 'approved') {
                                                        $status_fa = 'تایید شده';
                                                        $badgeClass = 'bg-status-active/20 text-status-active';
                                                    }
                                                    elseif ($appt['status'] === 'completed') {
                                                        $status_fa = 'تکمیل شده';
                                                        $badgeClass = 'bg-primary/20 text-primary';
                                                    }
                                                    elseif ($appt['status'] === 'cancelled') {
                                                        $status_fa = 'لغو شده';
                                                        $badgeClass = 'bg-error/20 text-error';
                                                    }
                                                    ?>
                                                    <span class="px-2 py-1 <?= $badgeClass ?> rounded-md text-[10px] font-bold"><?= $status_fa ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($appt['status'] !== 'completed' && $appt['status'] !== 'cancelled'): ?>
                                                    <form method="POST" class="flex gap-1 inline m-0" onclick="event.stopPropagation();">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                                        <?php if ($appt['status'] === 'pending' || $appt['status'] === 'در انتظار'): ?>
                                                            <button type="submit" name="status" value="approved" class="px-3 py-1 bg-status-active/10 text-status-active hover:bg-status-active hover:text-white rounded-lg text-xs font-bold transition-colors">تایید</button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="status" value="cancelled" onclick="return confirm('آیا از لغو این نوبت اطمینان دارید؟');" class="px-3 py-1 bg-error/10 text-error hover:bg-error hover:text-white rounded-lg text-xs font-bold transition-colors">لغو</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right 1/3: History Card -->
        <div class="lg:col-span-1 space-y-8">
            
            <!-- Appointment History Card -->
            <div class="bg-white rounded-xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-5 border-b border-outline-variant/20 bg-surface-container-low">
                    <h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">history</span>
                        تاریخچه نوبت‌ها
                    </h2>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-outline-variant/10 max-h-[600px] overflow-y-auto">
                        <?php if (empty($historyAppts)): ?>
                            <li class="p-6 text-center text-on-surface-variant text-sm">تاریخچه‌ای موجود نیست.</li>
                        <?php else: ?>
                            <?php foreach ($historyAppts as $appt): ?>
                                <li class="p-4 hover:bg-surface-container-lowest transition-colors flex items-start gap-3 cursor-pointer" onclick="openAppointmentDetails(this)" data-appt="<?= htmlspecialchars(json_encode($appt), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($appt['status'] === 'completed'): ?>
                                        <div class="w-8 h-8 rounded-full bg-status-active/10 text-status-active flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        </div>
                                    <?php elseif ($appt['status'] === 'cancelled'): ?>
                                        <div class="w-8 h-8 rounded-full bg-error/10 text-error flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[18px]">cancel</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-outline-variant/30 text-on-surface-variant flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[18px]">update</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-sm text-on-surface truncate">
                                            <?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?> 
                                            <span class="text-xs text-on-surface-variant font-normal">توسط <?= htmlspecialchars($appt['user_name']) ?></span>
                                        </p>
                                        <p class="text-[11px] text-on-surface-variant mt-1" dir="ltr">
                                            <?= $fmtDate->format(new DateTime($appt['appointment_date'])) ?> <?= substr($appt['appointment_time'], 0, 5) ?>
                                        </p>
                                    </div>
                                    
                                    <?php if ($appt['pet_id']): ?>
                                        <a href="?view_pet_history=<?= $appt['pet_id'] ?>" onclick="event.stopPropagation();" class="text-primary hover:text-primary-container p-1" title="پرونده">
                                            <span class="material-symbols-outlined text-sm">folder_open</span>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Upload Medical Record Modal -->
<div id="uploadDocModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center">
            <h3 class="font-title-lg text-primary">آپلود سند بالینی</h3>
            <button onclick="document.getElementById('uploadDocModal').classList.add('hidden');document.getElementById('uploadDocModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload_doc">
                <input type="hidden" name="pet_id" id="upload_pet_id" value="">
                <input type="hidden" name="user_id" id="upload_user_id" value="">
                
                <div class="bg-surface-container-low p-3 rounded-lg mb-4 text-sm">
                    ارسال سند برای حیوان: <span id="upload_pet_name" class="font-bold text-primary"></span>
                </div>
                
                <div class="space-y-1">
                    <label class="text-sm font-bold text-on-surface-variant">عنوان سند</label>
                    <input type="text" name="title" required placeholder="مثال: نسخه دارویی، جواب آزمایش و..." class="w-full rounded-lg border-outline-variant focus:ring-primary focus:border-primary text-sm">
                </div>
                
                <div class="space-y-1">
                    <label class="text-sm font-bold text-on-surface-variant">فایل ضمیمه</label>
                    <input type="file" name="document" required class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-white hover:file:bg-primary transition-all">
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full py-3 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">آپلود و ذخیره فایل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Patient History Modal (Triggered by GET param) -->
<?php if (isset($_GET['view_pet_history'])): ?>
<div id="patientHistoryModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-title-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">folder_special</span>
                پرونده بالینی حیوان
            </h3>
            <a href="index.php" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </a>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-8 flex-1 bg-surface">
            <!-- Documents Section -->
            <div>
                <h4 class="font-bold text-on-surface mb-4 flex items-center gap-2 border-b border-outline-variant/20 pb-2">
                    <span class="material-symbols-outlined text-secondary">description</span>
                    اسناد و مدارک آپلود شده
                </h4>
                <?php if (empty($documents)): ?>
                    <p class="text-sm text-on-surface-variant">هیچ سندی برای این حیوان آپلود نشده است.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($documents as $doc): ?>
                            <div class="flex items-start justify-between p-3 border border-outline-variant/30 rounded-xl bg-white shadow-sm">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 bg-error/10 text-error rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined">description</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-primary text-sm"><?= htmlspecialchars($doc['title']) ?></p>
                                        <p class="text-[10px] text-outline mt-1" dir="ltr"><?= substr($doc['uploaded_at'], 0, 10) ?></p>
                                    </div>
                                </div>
                                <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="w-8 h-8 rounded-full flex items-center justify-center text-primary bg-primary/10 hover:bg-primary hover:text-white transition-colors">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Past Visits Section -->
            <div>
                <h4 class="font-bold text-on-surface mb-4 flex items-center gap-2 border-b border-outline-variant/20 pb-2">
                    <span class="material-symbols-outlined text-secondary">history</span>
                    تاریخچه مراجعات به شما
                </h4>
                <?php if (empty($petPastAppts)): ?>
                    <p class="text-sm text-on-surface-variant">سابقه مراجعه‌ای ثبت نشده است.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($petPastAppts as $pastAppt): ?>
                            <div class="flex items-center justify-between p-3 border border-outline-variant/30 rounded-xl bg-white shadow-sm">
                                <div class="flex gap-3">
                                    <?php if ($pastAppt['status'] === 'completed'): ?>
                                        <div class="w-8 h-8 rounded-full bg-status-active/10 text-status-active flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                        </div>
                                    <?php elseif ($pastAppt['status'] === 'cancelled'): ?>
                                        <div class="w-8 h-8 rounded-full bg-error/10 text-error flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-outline-variant/30 text-on-surface-variant flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[16px]">event</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-bold text-sm text-primary" dir="ltr"><?= substr($pastAppt['appointment_date'], 0, 10) ?> - <?= substr($pastAppt['appointment_time'], 0, 5) ?></p>
                                        <p class="text-[11px] text-on-surface-variant">وضعیت: <?= htmlspecialchars($pastAppt['status']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-title-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">edit_calendar</span>
                تنظیم برنامه حضور هفتگی
            </h3>
            <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden');document.getElementById('scheduleModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="action" value="update_schedule">
            
            <div class="p-6 overflow-y-auto flex-1 bg-surface space-y-4">
                <p class="text-sm text-on-surface-variant mb-4">لطفاً روزها و ساعات کاری خود را با دقت مشخص کنید. این اطلاعات در صفحه رزرو نوبت به کاربران نمایش داده خواهد شد.</p>
                
                <?php
                $currentSchedule = [];
                if (!empty($doctorProfile['schedule_info'])) {
                    $decoded = json_decode($doctorProfile['schedule_info'], true);
                    if (is_array($decoded)) {
                        $currentSchedule = $decoded;
                    }
                }
                
                $daysMap = [
                    'sat' => 'شنبه',
                    'sun' => 'یک‌شنبه',
                    'mon' => 'دوشنبه',
                    'tue' => 'سه‌شنبه',
                    'wed' => 'چهارشنبه',
                    'thu' => 'پنج‌شنبه',
                    'fri' => 'جمعه'
                ];
                
                foreach ($daysMap as $key => $faName):
                    $isWorking = isset($currentSchedule[$key]);
                    // Default to true if not set (for backward compatibility), otherwise use the boolean value
                    $m_active = $isWorking ? (isset($currentSchedule[$key]['m_active']) ? $currentSchedule[$key]['m_active'] : true) : true;
                    $a_active = $isWorking ? (isset($currentSchedule[$key]['a_active']) ? $currentSchedule[$key]['a_active'] : true) : true;
                    
                    $m_start = $isWorking ? $currentSchedule[$key]['m_start'] : '09:00';
                    $m_end = $isWorking ? $currentSchedule[$key]['m_end'] : '13:00';
                    $a_start = $isWorking ? $currentSchedule[$key]['a_start'] : '16:00';
                    $a_end = $isWorking ? $currentSchedule[$key]['a_end'] : '20:00';
                ?>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-outline-variant/20">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="day_<?= $key ?>" value="1" <?= $isWorking ? 'checked' : '' ?> class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary">
                            <span class="font-bold text-on-surface"><?= $faName ?></span>
                        </label>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-8">
                        <!-- Morning Shift -->
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer mb-1 text-on-surface-variant hover:text-primary transition-colors w-max">
                                <input type="checkbox" name="<?= $key ?>_m_active" value="1" <?= $m_active ? 'checked' : '' ?> class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary">
                                <span class="text-xs font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">light_mode</span> فعال بودن شیفت صبح
                                </span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="time" name="<?= $key ?>_m_start" value="<?= $m_start ?>" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary" dir="ltr">
                                <span class="text-sm text-on-surface-variant">تا</span>
                                <input type="time" name="<?= $key ?>_m_end" value="<?= $m_end ?>" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary" dir="ltr">
                            </div>
                        </div>
                        
                        <!-- Afternoon Shift -->
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer mb-1 text-on-surface-variant hover:text-primary transition-colors w-max">
                                <input type="checkbox" name="<?= $key ?>_a_active" value="1" <?= $a_active ? 'checked' : '' ?> class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary">
                                <span class="text-xs font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">wb_sunny</span> فعال بودن شیفت عصر
                                </span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="time" name="<?= $key ?>_a_start" value="<?= $a_start ?>" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary" dir="ltr">
                                <span class="text-sm text-on-surface-variant">تا</span>
                                <input type="time" name="<?= $key ?>_a_end" value="<?= $a_end ?>" class="w-full text-sm rounded-lg border-outline-variant focus:ring-primary focus:border-primary" dir="ltr">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
            </div>
            <div class="p-4 border-t border-outline-variant/30 bg-surface-container-lowest">
                <button type="submit" class="w-full py-3 rounded-lg font-bold bg-primary text-white hover:bg-primary-container transition-colors">ذخیره برنامه هفتگی</button>
            </div>
        </form>
    </div>
</div>

<!-- Appointment Details Modal -->
<div id="appointmentDetailsModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-title-lg text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                جزئیات نوبت
            </h3>
            <button onclick="document.getElementById('appointmentDetailsModal').classList.add('hidden');document.getElementById('appointmentDetailsModal').classList.remove('flex')" class="text-on-surface-variant hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 space-y-6 overflow-y-auto">
            
            <div class="flex items-center gap-4 pb-6 border-b border-outline-variant/20">
                <div class="w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[32px]">pets</span>
                </div>
                <div>
                    <h4 id="dtl_pet_name" class="font-title-lg text-primary text-xl">نام حیوان</h4>
                    <p id="dtl_pet_type_race" class="text-sm text-on-surface-variant mt-1">نوع - نژاد</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">تاریخ و زمان</p>
                    <p id="dtl_datetime" class="font-bold text-primary" dir="ltr">--</p>
                </div>
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">وضعیت</p>
                    <div id="dtl_status">--</div>
                </div>
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">صاحب حیوان</p>
                    <p id="dtl_owner_name" class="font-bold text-on-surface">--</p>
                </div>
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">تلفن تماس</p>
                    <p id="dtl_owner_phone" class="font-bold text-on-surface" dir="ltr">--</p>
                </div>
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">جنسیت حیوان</p>
                    <p id="dtl_pet_gender" class="font-bold text-on-surface">--</p>
                </div>
                <div class="bg-surface p-3 rounded-lg border border-outline-variant/30">
                    <p class="text-xs text-on-surface-variant mb-1 font-bold">سن حیوان</p>
                    <p id="dtl_pet_age" class="font-bold text-on-surface">--</p>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/20 flex flex-wrap gap-2" id="dtl_actions">
                <!-- Action buttons will be injected here via JS -->
            </div>

        </div>
    </div>
</div>

<script>
function openUploadModal(petId, userId, petName) {
    document.getElementById('upload_pet_id').value = petId;
    document.getElementById('upload_user_id').value = userId;
    document.getElementById('upload_pet_name').innerText = petName;
    
    // If opened from details modal, ensure we close the details modal first for clarity
    document.getElementById('appointmentDetailsModal').classList.add('hidden');
    document.getElementById('appointmentDetailsModal').classList.remove('flex');
    
    document.getElementById('uploadDocModal').classList.remove('hidden');
    document.getElementById('uploadDocModal').classList.add('flex');
}

function openAppointmentDetails(rowElement) {
    const appt = JSON.parse(rowElement.getAttribute('data-appt'));
    
    // Set text contents
    const pName = appt.pet_name ? appt.pet_name : appt.pet_type;
    const pRace = appt.pet_race ? appt.pet_race : 'نژاد نامشخص';
    document.getElementById('dtl_pet_name').innerText = pName;
    document.getElementById('dtl_pet_type_race').innerText = appt.pet_type + ' - ' + pRace;
    
    document.getElementById('dtl_datetime').innerText = appt.jalali_date + ' | ' + appt.appointment_time.substr(0, 5);
    document.getElementById('dtl_owner_name').innerText = appt.user_name || 'نامشخص';
    document.getElementById('dtl_owner_phone').innerText = appt.phone || 'نامشخص';
    document.getElementById('dtl_pet_gender').innerText = appt.pet_gender || 'نامشخص';
    document.getElementById('dtl_pet_age').innerText = appt.pet_age || 'نامشخص';

    // Set Status
    let statusFa = appt.status;
    let badgeClass = 'bg-outline-variant/20 text-on-surface-variant';
    if(appt.status === 'approved') { statusFa = 'تایید شده'; badgeClass = 'bg-status-active/20 text-status-active'; }
    else if(appt.status === 'completed') { statusFa = 'تکمیل شده'; badgeClass = 'bg-primary/20 text-primary'; }
    else if(appt.status === 'cancelled') { statusFa = 'لغو شده'; badgeClass = 'bg-error/20 text-error'; }
    else if(appt.status === 'pending' || appt.status === 'در انتظار') { statusFa = 'در انتظار'; badgeClass = 'bg-status-warning/20 text-status-warning'; }
    
    document.getElementById('dtl_status').innerHTML = `<span class="px-2 py-1 rounded-md text-[10px] font-bold ${badgeClass}">${statusFa}</span>`;

    // Action Buttons
    const actionsContainer = document.getElementById('dtl_actions');
    let actionsHtml = '';
    
    // Upload & History
    if(appt.pet_id) {
        actionsHtml += `<button onclick="openUploadModal(${appt.pet_id}, ${appt.user_id}, '${pName.replace(/'/g, "\\'")}')" class="flex-1 flex justify-center items-center gap-2 py-2 px-3 rounded-lg font-bold bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs">
            <span class="material-symbols-outlined text-[18px]">upload_file</span> آپلود سند
        </button>`;
        actionsHtml += `<a href="?view_pet_history=${appt.pet_id}" class="flex-1 flex justify-center items-center gap-2 py-2 px-3 rounded-lg font-bold bg-secondary/10 text-secondary hover:bg-secondary hover:text-white transition-colors text-xs">
            <span class="material-symbols-outlined text-[18px]">history</span> پرونده پزشکی
        </a>`;
    }
    
    // Status updates
    if (appt.status !== 'completed' && appt.status !== 'cancelled') {
        actionsHtml += `<form method="POST" class="w-full flex gap-2 mt-2">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="appointment_id" value="${appt.id}">`;
            
        if (appt.status === 'pending' || appt.status === 'در انتظار') {
            actionsHtml += `<button type="submit" name="status" value="approved" class="flex-1 py-2 rounded-lg font-bold bg-status-active/10 text-status-active hover:bg-status-active hover:text-white transition-colors text-xs">تایید نوبت</button>`;
        } else {
            actionsHtml += `<button type="submit" name="status" value="completed" class="flex-1 py-2 rounded-lg font-bold bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs">تکمیل نوبت</button>`;
        }
        actionsHtml += `<button type="submit" name="status" value="cancelled" onclick="return confirm('آیا از لغو این نوبت اطمینان دارید؟');" class="flex-1 py-2 rounded-lg font-bold bg-error/10 text-error hover:bg-error hover:text-white transition-colors text-xs">لغو نوبت</button>
        </form>`;
    }
    
    actionsContainer.innerHTML = actionsHtml;

    document.getElementById('appointmentDetailsModal').classList.remove('hidden');
    document.getElementById('appointmentDetailsModal').classList.add('flex');
}
</script>

<?php require_once 'includes/doctor_footer.php'; ?>
