<?php
$currentPage = 'dashboard';
require_once 'includes/doctor_header.php';
require_once '../includes/functions.php';

$doctorId = (int)($doctorProfile['id'] ?? 0);
$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];
    
    if ($action === 'update_schedule') {
        $schedule_data = [];
        $days = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
        foreach ($days as $day) {
            if (!empty($_POST["day_$day"])) {
                $schedule_data[$day] = [
                    'm_active' => isset($_POST["{$day}_m_active"]),
                    'm_start'  => $_POST["{$day}_m_start"] ?? '09:00',
                    'm_end'    => $_POST["{$day}_m_end"] ?? '13:00',
                    'a_active' => isset($_POST["{$day}_a_active"]),
                    'a_start'  => $_POST["{$day}_a_start"] ?? '16:00',
                    'a_end'    => $_POST["{$day}_a_end"] ?? '20:00',
                ];
            }
        }
        $schedule_info = json_encode($schedule_data, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("UPDATE doctors SET schedule_info = ? WHERE id = ?");
        if ($stmt->execute([$schedule_info, $doctorId])) {
            $success = "برنامه زمانی حضور در کلینیک با موفقیت بروزرسانی شد.";
            $doctorProfile['schedule_info'] = $schedule_info;
        } else {
            $error = "خطا در بروزرسانی برنامه زمانی.";
        }
    } 
    elseif ($action === 'update_services_tags') {
        $tags = trim($_POST['tags'] ?? '');
        $price = (int)($_POST['price'] ?? $doctorProfile['price']);
        $bio = trim($_POST['bio'] ?? '');
        
        $service_names = $_POST['service_name'] ?? [];
        $service_durations = $_POST['service_duration'] ?? [];
        
        $services_array = [];
        for ($i = 0; $i < count($service_names); $i++) {
            $sName = trim($service_names[$i]);
            $sDur = trim($service_durations[$i] ?? '30 دقیقه');
            if (!empty($sName)) {
                $services_array[] = [
                    'id' => 'srv_' . ($i + 1),
                    'name' => $sName,
                    'duration' => $sDur
                ];
            }
        }
        
        $services_json = json_encode($services_array, JSON_UNESCAPED_UNICODE);
        
        $stmt = $pdo->prepare("UPDATE doctors SET tags = ?, services_json = ?, price = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$tags, $services_json, $price, $bio, $doctorId])) {
            $success = "خدمات، تخصص‌ها و تگ‌های شما با موفقیت بروزرسانی شدند.";
            $doctorProfile['tags'] = $tags;
            $doctorProfile['services_json'] = $services_json;
            $doctorProfile['price'] = $price;
            $doctorProfile['bio'] = $bio;
        } else {
            $error = "خطا در ذخیره خدمات و تخصص‌ها.";
        }
    }
    elseif ($action === 'update_status') {
        $apptId = (int)$_POST['appointment_id'];
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
    elseif ($action === 'reschedule_appointment') {
        $apptId = (int)$_POST['appointment_id'];
        $newDate = trim($_POST['new_date'] ?? '');
        $newTime = trim($_POST['new_time'] ?? '');
        $reason = trim($_POST['reschedule_reason'] ?? 'تغییر زمان به علت موارد فورس‌ماژور و هماهنگی مطب');
        $sendSms = isset($_POST['send_sms']);

        if ($apptId > 0 && !empty($newDate) && !empty($newTime)) {
            // Check conflict
            $conflictStmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND id != ? AND status NOT IN ('cancelled')");
            $conflictStmt->execute([$doctorId, $newDate, $newTime, $apptId]);
            if ($conflictStmt->fetch()) {
                $error = "این زمان قبلاً توسط نوبت دیگری رزرو شده است. لطفاً ساعت یا تاریخ دیگری را انتخاب فرمایید.";
            } else {
                $updStmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, reschedule_reason = ?, rescheduled_at = NOW(), status = 'approved' WHERE id = ? AND doctor_id = ?");
                if ($updStmt->execute([$newDate, $newTime, $reason, $apptId, $doctorId])) {
                    // Fetch user phone & pet name
                    $uStmt = $pdo->prepare("SELECT a.pet_name, a.pet_type, u.phone, u.name as user_name FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
                    $uStmt->execute([$apptId]);
                    $patientInfo = $uStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $smsNotice = "";
                    if ($sendSms && $patientInfo && !empty($patientInfo['phone'])) {
                        require_once '../includes/SmsService.php';
                        $sms = new SmsService();
                        $petName = $patientInfo['pet_name'] ?: $patientInfo['pet_type'] ?: 'حیوان خانگی';
                        $jalaliNewDate = (new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd'))->format(new DateTime($newDate));
                        $sms->sendAppointmentReschedule($patientInfo['phone'], $doctorName, $petName, $jalaliNewDate, $newTime, $reason);
                        $smsNotice = " و پیامک اطلاع‌رسانی به شماره {$patientInfo['phone']} ارسال گردید.";
                    }
                    $success = "زمان نوبت با موفقیت به تاریخ $newDate ساعت $newTime تغییر یافت" . $smsNotice;
                } else {
                    $error = "خطا در تغییر زمان نوبت.";
                }
            }
        } else {
            $error = "لطفاً تاریخ و ساعت جدید را به درستی وارد نمایید.";
        }
    }
    elseif ($action === 'save_medical_record') {
        $apptId = (int)$_POST['appointment_id'];
        $diagnosis = trim($_POST['doctor_diagnosis'] ?? '');
        $prescription = trim($_POST['doctor_prescription'] ?? '');
        $status = $_POST['status'] ?? 'completed';
        
        $stmt = $pdo->prepare("UPDATE appointments SET doctor_diagnosis = ?, doctor_prescription = ?, status = ? WHERE id = ? AND doctor_id = ?");
        if ($stmt->execute([$diagnosis, $prescription, $status, $apptId, $doctorId])) {
            $success = "پرونده بالینی، تشخیص و نسخه دارویی با موفقیت ثبت شد.";
        } else {
            $error = "خطا در ثبت اطلاعات بالینی.";
        }
    }
    elseif ($action === 'upload_doc') {
        $petId = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : null;
        $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $docTitle = trim($_POST['title'] ?? 'سند بالینی پزشک');
        
        if ($userId && isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
            $file_tmp = $_FILES['document']['tmp_name'];
            $mime_type = mime_content_type($file_tmp);
            
            if (isset($allowed_mimes[$mime_type]) && $_FILES['document']['size'] <= 10 * 1024 * 1024) {
                $ext = $allowed_mimes[$mime_type];
                $uploadDir = '../uploads/clinical_docs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileName = 'doc_' . bin2hex(random_bytes(10)) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                $dbPath = 'uploads/clinical_docs/' . $fileName;
                
                if (move_uploaded_file($file_tmp, $targetFile)) {
                    $stmt = $pdo->prepare("INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path, uploaded_by_doctor_id) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$petId, $userId, $docTitle, $fileName, $dbPath, $doctorId])) {
                        $success = "سند بالینی با موفقیت در پرونده بیمار ذخیره شد.";
                    } else {
                        $error = "خطا در ثبت سند در دیتابیس.";
                    }
                } else {
                    $error = "خطا در انتقال فایل.";
                }
            } else {
                $error = "فرمت فایل مجاز نیست (فقط تصاویر JPG, PNG, WEBP یا PDF تا سقف ۱۰ مگابایت).";
            }
        } else {
            $error = "اطلاعات بیمار ناقص است یا فایلی انتخاب نشده است.";
        }
    }
}

// Income Calculation (Completed/Approved)
$stmt = $pdo->prepare("SELECT COUNT(*) as total_visits FROM appointments WHERE doctor_id = ? AND status IN ('completed', 'approved')");
$stmt->execute([$doctorId]);
$visitStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalIncome = ($visitStats['total_visits'] ?? 0) * ($doctorProfile['price'] ?? 150000);

// Selected Calendar Date
$selectedCalDate = $_GET['cal_date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedCalDate)) {
    $selectedCalDate = date('Y-m-d');
}

// Fetch Appointments for the Selected Calendar Date
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone, u.email, u.city, u.address 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$doctorId, $selectedCalDate]);
$calAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Today's Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone, u.email, u.city, u.address 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$doctorId]);
$todayAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Upcoming Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone, u.email, u.city, u.address 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date > CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 30
");
$stmt->execute([$doctorId]);
$upcomingAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch History Appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.phone, u.email, u.city, u.address 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.doctor_id = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 50
");
$stmt->execute([$doctorId]);
$historyAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Documents for Pet Archive (grouped by pet_id and user_id)
$docsStmt = $pdo->prepare("
    SELECT d.*, DATE(d.uploaded_at) as upload_date 
    FROM pet_documents d 
    WHERE d.pet_id IN (SELECT DISTINCT pet_id FROM appointments WHERE doctor_id = ? AND pet_id IS NOT NULL)
       OR d.user_id IN (SELECT DISTINCT user_id FROM appointments WHERE doctor_id = ?)
    ORDER BY d.uploaded_at DESC
");
$docsStmt->execute([$doctorId, $doctorId]);
$allDoctorPetDocs = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

// Group docs by user_id and pet_id for quick client retrieval
$groupedDocs = [];
foreach ($allDoctorPetDocs as $d) {
    $key = $d['pet_id'] ? 'pet_' . $d['pet_id'] : 'user_' . $d['user_id'];
    $groupedDocs[$key][] = $d;
    $groupedDocs['user_' . $d['user_id']][] = $d;
}

// Fetch Doctor Reviews & Ratings
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.phone 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.target_type = 'doctor' AND r.target_id = ? 
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$doctorId]);
$doctorReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');

foreach ($todayAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
foreach ($upcomingAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
foreach ($historyAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
foreach ($calAppts as &$appt) { $appt['jalali_date'] = $fmtDate->format(new DateTime($appt['appointment_date'])); }
unset($appt);

$jalaliCalDate = $fmtDate->format(new DateTime($selectedCalDate));

// Parse doctor's services
$myServices = json_decode($doctorProfile['services_json'] ?? '[]', true) ?: [];
if (empty($myServices)) {
    $myServices = [
        ['id' => '1', 'name' => 'معاینه عمومی و چکاپ دوره ای', 'duration' => '30 دقیقه'],
        ['id' => '2', 'name' => 'واکسیناسیون و انگل‌زدایی جامع', 'duration' => '20 دقیقه'],
        ['id' => '3', 'name' => 'دندانپزشکی و جرم‌گیری تخصصی', 'duration' => '45 دقیقه'],
        ['id' => '4', 'name' => 'مشاوره و جراحی‌های تخصصی', 'duration' => '45 دقیقه'],
    ];
}
?>

<div class="p-4 md:p-8 max-w-[1440px] mx-auto space-y-6 md:space-y-8">
    
    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="bg-status-active/10 text-status-active p-4 rounded-2xl font-bold flex items-center gap-2 border border-status-active/20 shadow-sm animate-pulse">
            <span class="material-symbols-outlined">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-error/10 text-error p-4 rounded-2xl font-bold flex items-center gap-2 border border-error/20 shadow-sm">
            <span class="material-symbols-outlined">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Header Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <!-- Card 1: Today Appointments -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">calendar_today</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">نوبت‌های امروز</p>
                <h3 class="text-2xl font-black text-primary mt-1"><?= count($todayAppts) ?> نوبت</h3>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= $fmtDate->format(new DateTime()) ?></p>
            </div>
        </div>

        <!-- Card 2: Total Consultations -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">medical_services</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">کل مراجعات ویزیت شده</p>
                <h3 class="text-2xl font-black text-primary mt-1"><?= $visitStats['total_visits'] ?? 0 ?> ویزیت</h3>
                <p class="text-[11px] text-emerald-600 font-bold mt-0.5">ثبت شده در سیستم</p>
            </div>
        </div>

        <!-- Card 3: Doctor Rating -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">star</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">امتیاز و رضایت مراجعین</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-black text-amber-600"><?= number_format((float)($doctorProfile['rating'] ?? 5.0), 1) ?></h3>
                    <span class="text-xs text-slate-400">از ۵ (<?= count($doctorReviews) ?> نظر)</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">تاییدیه رسمی آسنا</p>
            </div>
        </div>

        <!-- Card 4: Estimated Income -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">payments</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">مجموع کارکرد مالی</p>
                <h3 class="text-2xl font-black text-orange-600 mt-1"><?= number_format($totalIncome) ?></h3>
                <p class="text-[11px] text-slate-400 mt-0.5">تومان (تعرفه: <?= number_format($doctorProfile['price'] ?? 150000) ?>)</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-outline-variant/30 gap-2 md:gap-8 overflow-x-auto custom-scrollbar text-sm font-bold">
        <button onclick="switchTab('calendar-tab')" id="tab-btn-calendar" class="tab-btn pb-3 px-2 text-primary border-b-2 border-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-xl">calendar_month</span>
            تقویم تعاملی روزانه و نوبت‌ها
        </button>
        <button onclick="switchTab('services-tab')" id="tab-btn-services" class="tab-btn pb-3 px-2 text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-xl">loyalty</span>
            خدمات، علت‌های مراجعه و تگ‌ها
        </button>
        <button onclick="switchTab('reviews-tab')" id="tab-btn-reviews" class="tab-btn pb-3 px-2 text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-xl">reviews</span>
            نظرات و بازخورد مراجعین (<?= count($doctorReviews) ?>)
        </button>
        <button onclick="switchTab('schedule-tab')" id="tab-btn-schedule" class="tab-btn pb-3 px-2 text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-xl">schedule</span>
            برنامه کاری هفتگی
        </button>
        <button onclick="switchTab('history-tab')" id="tab-btn-history" class="tab-btn pb-3 px-2 text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-xl">history</span>
            آرشیو مراجعات گذشته
        </button>
    </div>

    <!-- TAB 1: CALENDAR & DAILY TIMELINE -->
    <div id="calendar-tab" class="tab-content space-y-8">
        <!-- Date Bar & Controls -->
        <div class="bg-white p-5 rounded-2xl stat-card-shadow border border-outline-variant/30 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined">event</span>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-primary">تقویم روزانه نوبت‌های مطب</h3>
                    <p class="text-xs text-on-surface-variant">نمایش نوبت‌های تاریخ: <span class="font-bold text-slate-700"><?= $jalaliCalDate ?> (<?= $selectedCalDate ?>)</span></p>
                </div>
            </div>

            <!-- Quick Day Navigators -->
            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <a href="?cal_date=<?= date('Y-m-d') ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($selectedCalDate === date('Y-m-d')) ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    امروز
                </a>
                <a href="?cal_date=<?= date('Y-m-d', strtotime('+1 day')) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($selectedCalDate === date('Y-m-d', strtotime('+1 day'))) ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    فردا
                </a>
                <a href="?cal_date=<?= date('Y-m-d', strtotime('+2 days')) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($selectedCalDate === date('Y-m-d', strtotime('+2 days'))) ? 'bg-primary text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    پس‌فردا
                </a>
                <form method="GET" class="flex items-center gap-2 m-0 mr-2">
                    <input type="date" name="cal_date" value="<?= $selectedCalDate ?>" onchange="this.form.submit()" class="h-9 px-3 text-xs rounded-xl border border-outline-variant bg-white outline-none focus:ring-2 focus:ring-primary" dir="ltr">
                </form>
            </div>
        </div>

        <!-- Visual Timeline Slots Grid -->
        <div class="bg-white rounded-2xl stat-card-shadow border border-outline-variant/30 p-6 space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-outline-variant/20">
                <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">view_timeline</span>
                    جدول اسلات‌های زمانی ویزیت (روی هر نوبت کلیک کنید تا پرونده بالینی و مدارک باز شود)
                </h4>
                <span class="text-xs text-on-surface-variant bg-slate-100 px-3 py-1 rounded-full font-bold">
                    <?= count($calAppts) ?> نوبت ثبت شده در این روز
                </span>
            </div>

            <?php if (empty($calAppts)): ?>
                <div class="py-12 text-center text-on-surface-variant space-y-2">
                    <span class="material-symbols-outlined text-5xl text-slate-300">event_busy</span>
                    <p class="font-bold text-slate-600">هیچ نوبتی برای این روز رزرو نشده است.</p>
                    <p class="text-xs text-slate-400">در صورت فعال بودن شیفت کاری در این روز، بیماران می‌توانند نوبت جدید ثبت کنند.</p>
                </div>
            <?php else: ?>
                <!-- Grid of appointment cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                    <?php foreach ($calAppts as $appt): 
                        $statusClass = 'border-amber-300 bg-amber-50/50';
                        $statusBadge = 'bg-amber-100 text-amber-700';
                        $statusFa = 'در انتظار ویزیت';
                        if ($appt['status'] === 'approved') {
                            $statusClass = 'border-indigo-300 bg-indigo-50/50';
                            $statusBadge = 'bg-indigo-100 text-indigo-700';
                            $statusFa = 'تایید شده';
                        } elseif ($appt['status'] === 'completed') {
                            $statusClass = 'border-emerald-300 bg-emerald-50/50';
                            $statusBadge = 'bg-emerald-100 text-emerald-700';
                            $statusFa = 'ویزیت انجام شد';
                        } elseif ($appt['status'] === 'cancelled') {
                            $statusClass = 'border-rose-300 bg-rose-50/50 opacity-70';
                            $statusBadge = 'bg-rose-100 text-rose-700';
                            $statusFa = 'لغو شده';
                        }
                    ?>
                        <div class="border-2 <?= $statusClass ?> rounded-2xl p-4 transition-all hover:shadow-lg cursor-pointer group hover:-translate-y-0.5 relative overflow-hidden"
                             onclick="openSmartPatientDossier(this)"
                             data-appt="<?= htmlspecialchars(json_encode($appt, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                            
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full <?= ($appt['status'] === 'completed') ? 'bg-emerald-500' : (($appt['status'] === 'approved') ? 'bg-indigo-500' : 'bg-amber-500') ?>"></span>
                                    <span class="text-xs font-black text-slate-800 bg-white px-2.5 py-1 rounded-lg shadow-sm border border-slate-200" dir="ltr">
                                        <?= substr($appt['appointment_time'], 0, 5) ?>
                                    </span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md <?= $statusBadge ?>"><?= $statusFa ?></span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-primary shadow-sm shrink-0">
                                    <span class="material-symbols-outlined text-2xl">pets</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-bold text-primary truncate"><?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?></h4>
                                    <p class="text-xs text-on-surface-variant truncate">
                                        <?= htmlspecialchars($appt['pet_type']) ?> <?= !empty($appt['pet_race']) ? '• ' . htmlspecialchars($appt['pet_race']) : '' ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Purpose of Visit Badge -->
                            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                <span class="text-indigo-700 font-bold bg-white/80 px-2 py-0.5 rounded-md truncate max-w-[200px]" title="<?= htmlspecialchars($appt['visit_purpose'] ?? 'معاینه عمومی') ?>">
                                    🎯 <?= htmlspecialchars($appt['visit_purpose'] ?: 'معاینه عمومی و چکاپ') ?>
                                </span>
                                <span class="text-slate-500 font-medium"><?= htmlspecialchars($appt['user_name']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Today & Upcoming Quick Table -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Today Table -->
            <div class="bg-white rounded-2xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/20 bg-primary/5 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">event_available</span>
                        لیست نوبت‌های امروز (<?= count($todayAppts) ?>)
                    </h4>
                </div>
                <div class="overflow-x-auto max-h-80">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold">
                            <tr>
                                <th class="p-3">ساعت</th>
                                <th class="p-3">بیمار (پت)</th>
                                <th class="p-3">صاحب پت</th>
                                <th class="p-3">وضعیت / اقدام</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($todayAppts)): ?>
                                <tr><td colspan="4" class="p-6 text-center text-slate-400">هیچ نوبتی برای امروز نیست.</td></tr>
                            <?php else: ?>
                                <?php foreach ($todayAppts as $appt): ?>
                                    <tr class="hover:bg-slate-50 cursor-pointer" onclick="openSmartPatientDossier(this)" data-appt="<?= htmlspecialchars(json_encode($appt, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="p-3 font-bold text-primary" dir="ltr"><?= substr($appt['appointment_time'], 0, 5) ?></td>
                                        <td class="p-3 font-bold"><?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($appt['user_name']) ?></td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($appt['status'] === 'completed') ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' ?>">
                                                <?= htmlspecialchars($appt['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Upcoming Table -->
            <div class="bg-white rounded-2xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/20 bg-indigo-50/50 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-indigo-900 flex items-center gap-2">
                        <span class="material-symbols-outlined">schedule</span>
                        نوبت‌های روزهای آینده (<?= count($upcomingAppts) ?>)
                    </h4>
                </div>
                <div class="overflow-x-auto max-h-80">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold">
                            <tr>
                                <th class="p-3">تاریخ</th>
                                <th class="p-3">ساعت</th>
                                <th class="p-3">بیمار (پت)</th>
                                <th class="p-3">علت مراجعه</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($upcomingAppts)): ?>
                                <tr><td colspan="4" class="p-6 text-center text-slate-400">هیچ نوبتی برای روزهای آینده ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($upcomingAppts as $appt): ?>
                                    <tr class="hover:bg-slate-50 cursor-pointer" onclick="openSmartPatientDossier(this)" data-appt="<?= htmlspecialchars(json_encode($appt, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="p-3 font-bold text-slate-700" dir="ltr"><?= $appt['jalali_date'] ?></td>
                                        <td class="p-3 font-bold text-primary" dir="ltr"><?= substr($appt['appointment_time'], 0, 5) ?></td>
                                        <td class="p-3 font-bold"><?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?></td>
                                        <td class="p-3 text-slate-500 truncate max-w-[150px]"><?= htmlspecialchars($appt['visit_purpose'] ?: 'معاینه عمومی') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: DOCTOR'S SERVICES, VISIT PURPOSES & TAGS -->
    <div id="services-tab" class="tab-content space-y-6 hidden">
        <form method="POST" class="bg-white p-6 md:p-8 rounded-3xl stat-card-shadow border border-outline-variant/30 space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_services_tags">

            <div class="border-b border-outline-variant/20 pb-4">
                <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">loyalty</span>
                    مدیریت تخصص‌ها، علت‌های مراجعه (خدمات) و تگ‌های نمایشی
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">این خدمات و تگ‌ها در صفحه رزرو نوبت به مراجعین نمایش داده شده و گزینه‌های علت مراجعه را تشکیل می‌دهند.</p>
            </div>

            <!-- Tags Field -->
            <div class="space-y-2">
                <label class="block text-sm font-bold text-primary">تگ‌های مهارتی و تخصص‌ها (با کاما جدا کنید)</label>
                <input type="text" name="tags" value="<?= htmlspecialchars($doctorProfile['tags'] ?? 'سگ و گربه, واکسیناسیون, جراحی بافت نرم, دندانپزشکی, چکاپ دوره‌ای') ?>" class="w-full p-3 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary outline-none" placeholder="مثال: سگ و گربه, واکسیناسیون, جراحی تخصصی, پرندگان, دندانپزشکی">
                <p class="text-[11px] text-slate-400">این تگ‌ها به صورت برچسب‌های رنگی زیر نام شما در کارت‌های کلینیک نمایش داده می‌شوند.</p>
            </div>

            <!-- Consultation Fee & Bio -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-primary">تعرفه ویزیت (تومان)</label>
                    <input type="number" name="price" value="<?= $doctorProfile['price'] ?? 150000 ?>" class="w-full p-3 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary outline-none" required>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-primary">بیوگرافی یا توضیحات تخصص</label>
                    <input type="text" name="bio" value="<?= htmlspecialchars($doctorProfile['bio'] ?? '') ?>" class="w-full p-3 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary outline-none" placeholder="مثال: متخصص جراحی دام‌های کوچک با بیش از ۱۰ سال سابقه">
                </div>
            </div>

            <!-- Dynamic Services / Visit Purposes List -->
            <div class="space-y-4 pt-4 border-t border-outline-variant/20">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-1">
                        <span class="material-symbols-outlined text-indigo-600">medical_services</span>
                        علت‌های مراجعه و خدمات قابل ارائه شما (Visit Purposes)
                    </h4>
                    <button type="button" onclick="addServiceRow()" class="px-4 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> افزودن خدمت جدید
                    </button>
                </div>

                <div id="services-container" class="space-y-3">
                    <?php foreach ($myServices as $idx => $srv): ?>
                        <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-2xl service-row">
                            <span class="material-symbols-outlined text-slate-400">drag_indicator</span>
                            <input type="text" name="service_name[]" value="<?= htmlspecialchars($srv['name'] ?? $srv) ?>" placeholder="نام خدمت یا علت مراجعه (مثال: واکسیناسیون سالانه)" class="flex-1 p-2.5 rounded-xl border border-slate-200 text-xs outline-none bg-white" required>
                            <input type="text" name="service_duration[]" value="<?= htmlspecialchars($srv['duration'] ?? '30 دقیقه') ?>" placeholder="مدت زمان (مثال: 30 دقیقه)" class="w-36 p-2.5 rounded-xl border border-slate-200 text-xs outline-none bg-white">
                            <button type="button" onclick="this.closest('.service-row').remove()" class="text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-colors">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full md:w-auto px-8 py-3.5 bg-primary text-white font-bold rounded-2xl hover:bg-primary-container transition-all shadow-md">
                    ذخیره تغییرات تخصص‌ها و خدمات
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 3: REVIEWS & FEEDBACK -->
    <div id="reviews-tab" class="tab-content space-y-6 hidden">
        <div class="bg-white p-6 md:p-8 rounded-3xl stat-card-shadow border border-outline-variant/30 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-outline-variant/20">
                <div>
                    <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500" style="font-variation-settings: 'FILL' 1;">star</span>
                        نظرات و بازخوردهای مراجعین کلینیک
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-1">تنها بیمارانی که نوبت قطعی داشته‌اند مجاز به ثبت نظر و امتیازدهی هستند.</p>
                </div>
                <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 px-4 py-2 rounded-2xl">
                    <span class="text-2xl font-black text-amber-600"><?= number_format((float)($doctorProfile['rating'] ?? 5.0), 1) ?></span>
                    <div class="text-right">
                        <div class="flex text-amber-400 text-sm">★★★★★</div>
                        <span class="text-[10px] text-slate-500">میانگین از <?= count($doctorReviews) ?> نظر</span>
                    </div>
                </div>
            </div>

            <?php if (empty($doctorReviews)): ?>
                <div class="py-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-5xl text-slate-300">chat_bubble_outline</span>
                    <p class="font-bold text-slate-600 mt-2">هنوز نظری برای شما ثبت نشده است.</p>
                    <p class="text-xs text-slate-400">پس از اتمام ویزیت‌ها، مراجعین با دریافت ۵ امتیاز وفاداری نظرات خود را ثبت می‌کنند.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($doctorReviews as $rev): ?>
                        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                        <?= mb_substr($rev['user_name'] ?? 'م', 0, 1) ?>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-xs text-slate-800"><?= htmlspecialchars($rev['user_name']) ?></h5>
                                        <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">✓ ویزیت تایید شده</span>
                                    </div>
                                </div>
                                <div class="text-amber-400 text-xs font-black">
                                    <?= str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5 - (int)$rev['rating']) ?>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed bg-white p-3 rounded-xl border border-slate-100">
                                <?= nl2br(htmlspecialchars($rev['comment'] ?: 'بدون متن نظر')) ?>
                            </p>
                            <span class="text-[10px] text-slate-400 block text-left" dir="ltr"><?= substr($rev['created_at'], 0, 10) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 4: SCHEDULE MANAGEMENT -->
    <div id="schedule-tab" class="tab-content space-y-6 hidden">
        <form method="POST" class="bg-white p-6 md:p-8 rounded-3xl stat-card-shadow border border-outline-variant/30 space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_schedule">

            <div class="border-b border-outline-variant/20 pb-4">
                <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">schedule</span>
                    تنظیم برنامه و شیفت‌های هفتگی حضور در مطب
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">روزها و ساعاتی که تیک فعال دارند به عنوان اسلات‌های قابل رزرو به مراجعین نمایش داده خواهند شد.</p>
            </div>

            <?php
            $currentSchedule = [];
            if (!empty($doctorProfile['schedule_info'])) {
                $decoded = json_decode($doctorProfile['schedule_info'], true);
                if (is_array($decoded)) {
                    $currentSchedule = $decoded;
                }
            }
            $daysMap = ['sat' => 'شنبه', 'sun' => 'یک‌شنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنج‌شنبه', 'fri' => 'جمعه'];
            ?>

            <div class="space-y-4">
                <?php foreach ($daysMap as $key => $faName): 
                    $isWorking = isset($currentSchedule[$key]);
                    $m_active = $isWorking ? ($currentSchedule[$key]['m_active'] ?? true) : true;
                    $a_active = $isWorking ? ($currentSchedule[$key]['a_active'] ?? true) : true;
                    $m_start = $isWorking ? ($currentSchedule[$key]['m_start'] ?? '09:00') : '09:00';
                    $m_end = $isWorking ? ($currentSchedule[$key]['m_end'] ?? '13:00') : '13:00';
                    $a_start = $isWorking ? ($currentSchedule[$key]['a_start'] ?? '16:00') : '16:00';
                    $a_end = $isWorking ? ($currentSchedule[$key]['a_end'] ?? '20:00') : '20:00';
                ?>
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 transition-colors">
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="day_<?= $key ?>" value="1" <?= $isWorking ? 'checked' : '' ?> class="w-5 h-5 rounded text-primary focus:ring-primary">
                                <span class="font-bold text-sm text-slate-800"><?= $faName ?></span>
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-4">
                            <!-- Morning -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-600">
                                    <input type="checkbox" name="<?= $key ?>_m_active" value="1" <?= $m_active ? 'checked' : '' ?> class="w-4 h-4 rounded text-primary">
                                    ☀️ شیفت صبح
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="time" name="<?= $key ?>_m_start" value="<?= $m_start ?>" class="w-full text-xs p-2 rounded-xl border border-slate-200 bg-white" dir="ltr">
                                    <span class="text-xs text-slate-400">تا</span>
                                    <input type="time" name="<?= $key ?>_m_end" value="<?= $m_end ?>" class="w-full text-xs p-2 rounded-xl border border-slate-200 bg-white" dir="ltr">
                                </div>
                            </div>

                            <!-- Afternoon -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-600">
                                    <input type="checkbox" name="<?= $key ?>_a_active" value="1" <?= $a_active ? 'checked' : '' ?> class="w-4 h-4 rounded text-primary">
                                    🌇 شیفت عصر
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="time" name="<?= $key ?>_a_start" value="<?= $a_start ?>" class="w-full text-xs p-2 rounded-xl border border-slate-200 bg-white" dir="ltr">
                                    <span class="text-xs text-slate-400">تا</span>
                                    <input type="time" name="<?= $key ?>_a_end" value="<?= $a_end ?>" class="w-full text-xs p-2 rounded-xl border border-slate-200 bg-white" dir="ltr">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="w-full md:w-auto px-8 py-3.5 bg-primary text-white font-bold rounded-2xl hover:bg-primary-container transition-all shadow-md">
                ذخیره برنامه کاری هفتگی
            </button>
        </form>
    </div>

    <!-- TAB 5: PATIENTS & APPOINTMENTS ARCHIVE -->
    <div id="history-tab" class="tab-content space-y-6 hidden">
        <div class="bg-white rounded-3xl stat-card-shadow border border-outline-variant/30 p-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-outline-variant/20">
                <h3 class="font-bold text-lg text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">history</span>
                    آرشیو کامل مراجعات گذشته (<?= count($historyAppts) ?>)
                </h3>
                <input type="text" id="history-search" onkeyup="filterHistoryTable()" placeholder="جستجو بر اساس نام پت یا صاحب..." class="p-2.5 px-4 rounded-xl border border-outline-variant text-xs outline-none focus:ring-2 focus:ring-primary w-full sm:w-64">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs" id="history-table">
                    <thead class="bg-slate-50 text-slate-600 font-bold">
                        <tr>
                            <th class="p-3">تاریخ و ساعت</th>
                            <th class="p-3">مشخصات پت</th>
                            <th class="p-3">صاحب حیوان</th>
                            <th class="p-3">علت مراجعه</th>
                            <th class="p-3">وضعیت</th>
                            <th class="p-3 text-center">پرونده بالینی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($historyAppts)): ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400">تاریخچه‌ای موجود نیست.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historyAppts as $appt): ?>
                                <tr class="hover:bg-slate-50 cursor-pointer" onclick="openSmartPatientDossier(this)" data-appt="<?= htmlspecialchars(json_encode($appt, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="p-3 font-bold text-slate-700" dir="ltr"><?= $appt['jalali_date'] ?> <?= substr($appt['appointment_time'], 0, 5) ?></td>
                                    <td class="p-3 font-bold text-primary"><?= htmlspecialchars($appt['pet_name'] ?: $appt['pet_type']) ?> (<?= htmlspecialchars($appt['pet_type']) ?>)</td>
                                    <td class="p-3"><?= htmlspecialchars($appt['user_name']) ?> <span class="text-[10px] text-slate-400 block" dir="ltr"><?= htmlspecialchars($appt['phone']) ?></span></td>
                                    <td class="p-3 text-indigo-700 font-medium"><?= htmlspecialchars($appt['visit_purpose'] ?: 'معاینه عمومی') ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($appt['status'] === 'completed') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= htmlspecialchars($appt['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button class="px-3 py-1 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg font-bold transition-all">
                                            مشاهده و نسخه
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SMART PATIENT DOSSIER & MEDICAL RECORD MODAL -->
<!-- ========================================================================= -->
<div id="smartDossierModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-3 md:p-6 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/30">
                    <span class="material-symbols-outlined text-2xl">medical_information</span>
                </div>
                <div>
                    <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                        پرونده بالینی بیمار: <span id="dos_pet_name" class="text-indigo-600">---</span>
                    </h3>
                    <p class="text-xs text-slate-500" id="dos_appt_header_info">نوبت: ---</p>
                </div>
            </div>
            <button onclick="closeSmartPatientDossier()" class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-slate-50/50">
            
            <!-- Section 1: Patient & Owner Profile Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Pet Details -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <h4 class="font-bold text-xs text-indigo-900 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                        <span class="material-symbols-outlined text-indigo-600 text-sm">pets</span>
                        مشخصات حیوان (بیمار)
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-slate-400">گونه:</span> <b id="dos_pet_type" class="text-slate-700">---</b></div>
                        <div><span class="text-slate-400">نژاد:</span> <b id="dos_pet_race" class="text-slate-700">---</b></div>
                        <div><span class="text-slate-400">جنسیت:</span> <b id="dos_pet_gender" class="text-slate-700">---</b></div>
                        <div><span class="text-slate-400">سن:</span> <b id="dos_pet_age" class="text-slate-700">---</b></div>
                    </div>
                </div>

                <!-- Owner Details -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <h4 class="font-bold text-xs text-indigo-900 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                        <span class="material-symbols-outlined text-indigo-600 text-sm">person</span>
                        مشخصات صاحب پت
                    </h4>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between items-center">
                            <span><span class="text-slate-400">نام:</span> <b id="dos_owner_name" class="text-slate-700">---</b></span>
                            <a id="dos_owner_phone_call" href="#" class="px-3 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-lg font-bold text-[11px] flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[14px]">call</span> <span id="dos_owner_phone">---</span>
                            </a>
                        </div>
                        <div><span class="text-slate-400">ایمیل:</span> <span id="dos_owner_email" class="text-slate-600" dir="ltr">---</span></div>
                        <div><span class="text-slate-400">آدرس:</span> <span id="dos_owner_address" class="text-slate-600">---</span></div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Visit Purpose & Client Initial Notes -->
            <div class="bg-indigo-50/70 border border-indigo-100 p-4 rounded-2xl space-y-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600 text-lg">medical_services</span>
                    <h4 class="font-bold text-xs text-indigo-950">علت و هدف مراجعه انتخابی:</h4>
                    <span id="dos_visit_purpose" class="bg-white text-indigo-700 font-black px-3 py-1 rounded-lg text-xs shadow-sm">---</span>
                </div>
                <div class="text-xs text-slate-700 bg-white/80 p-3 rounded-xl border border-indigo-100/50">
                    <span class="font-bold text-slate-800 block mb-1">📝 شرح حال و توضیحات اولیه صاحب پت:</span>
                    <p id="dos_pet_notes" class="leading-relaxed">بدون توضیحات اولیه</p>
                </div>
            </div>

            <!-- Section 3: Historical Documents Archive (Uploaded by owner or other doctors) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-secondary text-base">folder_open</span>
                        مدارک بالینی، آزمایشات و شناسنامه قبلی
                    </h4>
                    <button type="button" onclick="toggleQuickUploadForm()" class="px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">upload_file</span> آپلود مدرک/آزمایش جدید
                    </button>
                </div>

                <!-- Quick Upload Form (Toggleable) -->
                <form id="quick-doc-upload-form" method="POST" enctype="multipart/form-data" class="hidden p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_doc">
                    <input type="hidden" name="pet_id" id="upload_hidden_pet_id" value="">
                    <input type="hidden" name="user_id" id="upload_hidden_user_id" value="">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1 text-slate-700">عنوان سند / آزمایش</label>
                            <input type="text" name="title" required placeholder="مثال: جواب آزمایش CBC یا عکس رادیولوژی" class="w-full p-2.5 rounded-xl border border-slate-300 text-xs bg-white outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1 text-slate-700">انتخاب فایل (عکس یا PDF)</label>
                            <input type="file" name="document" required class="w-full text-xs text-slate-500 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="toggleQuickUploadForm()" class="px-4 py-1.5 text-xs text-slate-500">انصراف</button>
                        <button type="submit" class="px-5 py-1.5 bg-emerald-600 text-white font-bold rounded-xl text-xs hover:bg-emerald-700">ذخیره و پیوست</button>
                    </div>
                </form>

                <!-- Documents List Container -->
                <div id="dos_documents_container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <p class="text-xs text-slate-400 py-2">در حال بارگذاری اسناد...</p>
                </div>
            </div>

            <!-- Section 4: Doctor Diagnosis & E-Prescription -->
            <form method="POST" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_medical_record">
                <input type="hidden" name="appointment_id" id="dos_form_appt_id" value="">

                <div class="border-b border-slate-100 pb-2">
                    <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">edit_document</span>
                        ثبت تشخیص بالینی، نسخه دارویی و اتمام ویزیت
                    </h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">🩺 تشخیص و شرح بالینی پزشک (Clinical Diagnosis)</label>
                        <textarea name="doctor_diagnosis" id="dos_doctor_diagnosis" rows="3" placeholder="یافته‌های بالینی، علائم مشاهده شده، دمای بدن و توصیه به صاحب پت..." class="w-full p-3 rounded-xl border border-slate-200 text-xs outline-none focus:ring-2 focus:ring-primary bg-slate-50/50 resize-none"></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">💊 دستور دارویی و نسخه الکترونیک (E-Prescription)</label>
                        <textarea name="doctor_prescription" id="dos_doctor_prescription" rows="3" placeholder="نام داروها، دوز مصرفی، فواصل زمانی و دستورات داروساز..." class="w-full p-3 rounded-xl border border-slate-200 text-xs outline-none focus:ring-2 focus:ring-primary bg-slate-50/50 resize-none"></textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <label class="text-xs font-bold text-slate-600">وضعیت نوبت:</label>
                        <select name="status" id="dos_form_status" class="text-xs p-2 rounded-xl border border-slate-200 bg-white font-bold">
                            <option value="completed">✓ ویزیت انجام شد (Completed)</option>
                            <option value="approved">⏳ تایید شده (Approved)</option>
                            <option value="cancelled">❌ لغو نوبت (Cancelled)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white font-bold rounded-xl text-xs hover:bg-primary-container transition-all shadow-md">
                            ثبت پرونده و ذخیره نسخه
                        </button>
                    </div>
                </div>
            </form>

            <!-- Section 5: Reschedule Appointment (Force Majeure & Emergency) -->
            <div class="bg-amber-50/70 border border-amber-200 p-5 rounded-2xl space-y-3">
                <div class="flex justify-between items-center cursor-pointer select-none" onclick="toggleRescheduleForm()">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600 text-lg">update</span>
                        <h4 class="font-bold text-xs text-amber-950">تغییر زمان نوبت (موارد اضطراری و فورس‌ماژور پزشک)</h4>
                    </div>
                    <span id="reschedule-toggle-icon" class="material-symbols-outlined text-amber-700 text-base transition-transform">expand_more</span>
                </div>

                <form id="reschedule-form" method="POST" class="hidden pt-3 border-t border-amber-200/60 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reschedule_appointment">
                    <input type="hidden" name="appointment_id" id="dos_reschedule_appt_id" value="">

                    <p class="text-[11px] text-amber-900 leading-relaxed">
                        در صورت وقوع عمل جراحی اورژانسی یا موارد فورس‌ماژور، می‌توانید تاریخ و ساعت نوبت را تغییر دهید. سیستم به صورت خودکار پیامک اطلاع‌رسانی با تاریخ و ساعت جدید برای صاحب پت ارسال می‌کند.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">📅 تاریخ جدید نوبت</label>
                            <input type="date" name="new_date" id="dos_reschedule_new_date" required class="w-full p-2.5 rounded-xl border border-amber-300 text-xs bg-white outline-none focus:ring-2 focus:ring-amber-500" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">⏰ ساعت جدید نوبت</label>
                            <input type="time" name="new_time" id="dos_reschedule_new_time" required class="w-full p-2.5 rounded-xl border border-amber-300 text-xs bg-white outline-none focus:ring-2 focus:ring-amber-500" dir="ltr">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">علت تغییر نوبت (در پیامک به کاربر ارسال می‌شود)</label>
                        <input type="text" name="reschedule_reason" id="dos_reschedule_reason" value="موارد فورس‌ماژور و جراحی اورژانسی در کلینیک" placeholder="مثال: تداخل با عمل جراحی اورژانسی" class="w-full p-2.5 rounded-xl border border-amber-300 text-xs bg-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-amber-950">
                            <input type="checkbox" name="send_sms" value="1" checked class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
                            📱 ارسال خودکار پیامک اطلاع‌رسانی تغییر ساعت به بیمار
                        </label>
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">send</span>
                            تغییر زمان نوبت و ارسال SMS
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
// All Documents preloaded in JS
const allDocumentsGrouped = <?= json_encode($groupedDocs, JSON_UNESCAPED_UNICODE) ?>;

// Switch Navigation Tabs
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-primary', 'border-b-2', 'border-primary');
        btn.classList.add('text-on-surface-variant');
    });

    const activeTab = document.getElementById(tabId);
    if (activeTab) activeTab.classList.remove('hidden');

    const activeBtn = document.getElementById('tab-btn-' + tabId.replace('-tab', ''));
    if (activeBtn) {
        activeBtn.classList.remove('text-on-surface-variant');
        activeBtn.classList.add('text-primary', 'border-b-2', 'border-primary');
    }
}

// Add new service row in Services Tab
function addServiceRow() {
    const container = document.getElementById('services-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-2xl service-row';
    div.innerHTML = `
        <span class="material-symbols-outlined text-slate-400">drag_indicator</span>
        <input type="text" name="service_name[]" placeholder="نام خدمت یا علت مراجعه (مثال: صدور شناسنامه)" class="flex-1 p-2.5 rounded-xl border border-slate-200 text-xs outline-none bg-white" required>
        <input type="text" name="service_duration[]" value="30 دقیقه" placeholder="مدت زمان" class="w-36 p-2.5 rounded-xl border border-slate-200 text-xs outline-none bg-white">
        <button type="button" onclick="this.closest('.service-row').remove()" class="text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-lg">delete</span>
        </button>
    `;
    container.appendChild(div);
}

// Open Smart Patient Dossier Modal
function openSmartPatientDossier(element) {
    const appt = JSON.parse(element.getAttribute('data-appt'));
    
    // Set Header
    const petName = appt.pet_name || appt.pet_type || 'پت نامشخص';
    document.getElementById('dos_pet_name').innerText = petName;
    document.getElementById('dos_appt_header_info').innerText = `نوبت تاریخ: ${appt.jalali_date || appt.appointment_date} ساعت ${appt.appointment_time.substr(0, 5)}`;
    
    // Set Pet Details
    document.getElementById('dos_pet_type').innerText = appt.pet_type || '---';
    document.getElementById('dos_pet_race').innerText = appt.pet_race || 'نامشخص';
    document.getElementById('dos_pet_gender').innerText = appt.pet_gender || 'نامشخص';
    document.getElementById('dos_pet_age').innerText = appt.pet_age || 'نامشخص';

    // Set Owner Details
    document.getElementById('dos_owner_name').innerText = appt.user_name || 'بدون نام';
    document.getElementById('dos_owner_phone').innerText = appt.phone || 'بدون شماره';
    document.getElementById('dos_owner_phone_call').href = appt.phone ? 'tel:' + appt.phone : '#';
    document.getElementById('dos_owner_email').innerText = appt.email || '---';
    document.getElementById('dos_owner_address').innerText = (appt.city ? appt.city + '، ' : '') + (appt.address || 'ثبت نشده');

    // Set Purpose & Notes
    document.getElementById('dos_visit_purpose').innerText = appt.visit_purpose || 'معاینه عمومی و چکاپ';
    document.getElementById('dos_pet_notes').innerText = appt.pet_notes || 'صاحب پت توضیحات اولیه‌ای درج نکرده است.';

    // Set Form IDs
    document.getElementById('dos_form_appt_id').value = appt.id;
    document.getElementById('upload_hidden_pet_id').value = appt.pet_id || '';
    document.getElementById('upload_hidden_user_id').value = appt.user_id || '';
    document.getElementById('dos_doctor_diagnosis').value = appt.doctor_diagnosis || '';
    document.getElementById('dos_doctor_prescription').value = appt.doctor_prescription || '';
    document.getElementById('dos_form_status').value = appt.status || 'completed';

    // Set Reschedule Form Defaults
    document.getElementById('dos_reschedule_appt_id').value = appt.id;
    document.getElementById('dos_reschedule_new_date').value = appt.appointment_date || '';
    document.getElementById('dos_reschedule_new_time').value = (appt.appointment_time || '09:00').substr(0, 5);
    document.getElementById('dos_reschedule_reason').value = appt.reschedule_reason || 'موارد فورس‌ماژور و جراحی اورژانسی در کلینیک';

    // Render Historical Documents
    const docsContainer = document.getElementById('dos_documents_container');
    docsContainer.innerHTML = '';
    
    const docKey = appt.pet_id ? 'pet_' + appt.pet_id : 'user_' + appt.user_id;
    const docs = allDocumentsGrouped[docKey] || [];

    if (docs.length === 0) {
        docsContainer.innerHTML = '<p class="text-xs text-slate-400 py-2 col-span-2">هیچ سند یا آزمایشی برای این بیمار ثبت نشده است.</p>';
    } else {
        docs.forEach(doc => {
            const isPdf = doc.file_name && doc.file_name.endsWith('.pdf');
            const icon = isPdf ? 'picture_as_pdf' : 'image';
            const iconColor = isPdf ? 'text-rose-500 bg-rose-50' : 'text-indigo-500 bg-indigo-50';
            
            docsContainer.insertAdjacentHTML('beforeend', `
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 transition-colors">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg ${iconColor} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">${icon}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-xs text-slate-800 truncate">${escapeHtml(doc.title)}</p>
                            <span class="text-[10px] text-slate-400" dir="ltr">${doc.upload_date || doc.uploaded_at.substr(0, 10)}</span>
                        </div>
                    </div>
                    <a href="../${doc.file_path}" target="_blank" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-indigo-600 hover:border-indigo-300 flex items-center justify-center transition-all shadow-sm shrink-0" title="مشاهده / دانلود">
                        <span class="material-symbols-outlined text-base">visibility</span>
                    </a>
                </div>
            `);
        });
    }

    // Show Modal
    const modal = document.getElementById('smartDossierModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSmartPatientDossier() {
    const modal = document.getElementById('smartDossierModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('quick-doc-upload-form').classList.add('hidden');
    document.getElementById('reschedule-form').classList.add('hidden');
    const icon = document.getElementById('reschedule-toggle-icon');
    if (icon) icon.style.transform = 'rotate(0deg)';
}

function toggleRescheduleForm() {
    const form = document.getElementById('reschedule-form');
    const icon = document.getElementById('reschedule-toggle-icon');
    form.classList.toggle('hidden');
    if (icon) {
        icon.style.transform = form.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

function toggleQuickUploadForm() {
    const form = document.getElementById('quick-doc-upload-form');
    form.classList.toggle('hidden');
}

function filterHistoryTable() {
    const input = document.getElementById('history-search').value.toLowerCase();
    const rows = document.querySelectorAll('#history-table tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}
</script>

<?php require_once 'includes/doctor_footer.php'; ?>
