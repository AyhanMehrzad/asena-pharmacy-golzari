<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch doctors
try {
    $stmt = $pdo->query("SELECT * FROM doctors ORDER BY rating DESC");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $doctors = [];
}

// Fetch user's pet data if logged in
$user_pets = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, race FROM user_pets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // ignore
    }
}

// Fetch booked slots for the next 14 days
$booked_slots = [];
try {
    $stmt = $pdo->query("SELECT doctor_id, appointment_date, appointment_time FROM appointments WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND status != 'cancelled'");
    $appts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($appts as $appt) {
        $docId = $appt['doctor_id'];
        $date = $appt['appointment_date'];
        $time = substr($appt['appointment_time'], 0, 5); // '09:00'
        if (!isset($booked_slots[$docId])) {
            $booked_slots[$docId] = [];
        }
        if (!isset($booked_slots[$docId][$date])) {
            $booked_slots[$docId][$date] = [];
        }
        $booked_slots[$docId][$date][] = $time;
    }
} catch (PDOException $e) {
    // ignore
}
$booked_slots_json = json_encode($booked_slots);
?>

<!-- Include page-specific styles -->
<link rel="stylesheet" href="assets/css/booking.css">
<style>
    /* Add specific styles for selected states */
    .doctor-card.selected {
        border-color: #fd8100 !important;
        background-color: #f9f9f9 !important;
    }
    .date-card.selected {
        border-color: #fd8100 !important;
        background-color: #eeeeee !important;
    }
    .time-btn.selected {
        background-color: #002d72 !important;
        color: white !important;
        border-color: #002d72 !important;
    }
</style>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[70vh]">
    <!-- Breadcrumb -->
    <div class="text-label-sm text-on-surface-variant mb-6">
        <a href="index.php" class="hover:underline">خانه</a> > 
        <a href="booking.php" class="text-on-surface">رزرو نوبت</a>
    </div>

    <?php if(isset($_SESSION['booking_error'])): ?>
        <div class="bg-error/10 text-error p-4 rounded-xl mb-6 text-body-md font-bold text-center border border-error/20">
            <?php 
                echo htmlspecialchars($_SESSION['booking_error']); 
                unset($_SESSION['booking_error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Progressive Stepper -->
    <div class="flex items-center justify-center mb-12">
        <div class="flex items-center w-full max-w-3xl">
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center font-bold z-10">۱</div>
                <span class="mt-2 text-label-lg font-label-lg text-primary">انتخاب پزشک</span>
            </div>
            <div class="flex-auto border-t-2 border-primary-container mx-2 -mt-7"></div>
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold z-10" id="step-2-indicator">۲</div>
                <span class="mt-2 text-label-lg font-label-lg text-on-surface-variant" id="step-2-text">انتخاب زمان</span>
            </div>
            <div class="flex-auto border-t-2 border-surface-variant mx-2 -mt-7"></div>
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold z-10" id="step-3-indicator">۳</div>
                <span class="mt-2 text-label-lg font-label-lg text-on-surface-variant" id="step-3-text">تأیید نهایی</span>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <form action="actions/booking_action.php" method="POST" id="booking-form" class="flex flex-col lg:flex-row-reverse gap-8">
        <?php echo csrf_field(); ?>
        
        <!-- Hidden Inputs to store selections -->
        <input type="hidden" name="doctor_id" id="input_doctor_id" value="">
        <input type="hidden" name="appointment_date" id="input_date" value="">
        <input type="hidden" name="appointment_time" id="input_time" value="">

        <!-- Left Side: Main Selection -->
        <div class="flex-grow space-y-10 min-w-0">
            <!-- Doctor Selection Section -->
            <section>
                <div class="flex justify-between items-end mb-6">
                    <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">stethoscope</span>
                        ۱. انتخاب پزشک
                    </h2>
                </div>
                <!-- Doctors Grid (Y-axis movement) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar" id="doctors-list">
                    
                    <?php foreach($doctors as $index => $doctor): ?>
                    <div class="doctor-card w-full bg-white border border-slate-200 rounded-2xl p-4 hover:shadow-xl hover:border-indigo-200 transition-all duration-300 group cursor-pointer hover:-translate-y-1 relative overflow-hidden"
                         data-id="<?php echo $doctor['id']; ?>"
                         data-name="<?php echo htmlspecialchars($doctor['name']); ?>"
                         data-image="<?php echo htmlspecialchars($doctor['image_url'] ?: 'assets/images/presentation-dog.jpg'); ?>"
                         data-price="<?php echo $doctor['price']; ?>"
                         data-schedule="<?php echo htmlspecialchars($doctor['schedule_info'] ?? '{}'); ?>"
                         data-services="<?php echo htmlspecialchars($doctor['services_json'] ?? '[]'); ?>"
                         data-tags="<?php echo htmlspecialchars($doctor['tags'] ?? ''); ?>"
                         onclick="selectDoctor(this)">
                        
                        <div class="relative mb-4 overflow-hidden rounded-xl">
                            <img class="w-full h-48 object-cover transform group-hover:scale-105 transition-transform duration-500" src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'assets/images/presentation-dog.jpg'); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>"/>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <?php if($index === 0): ?>
                            <div class="absolute top-2 right-2 bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-lg shadow-emerald-500/30">
                                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                آماده ویزیت
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                            <p class="text-sm font-medium text-indigo-600 bg-indigo-50 inline-block px-2 py-0.5 rounded-md"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                            
                            <?php if(!empty($doctor['tags'])): ?>
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <?php foreach(array_slice(explode(',', $doctor['tags']), 0, 3) as $tg): ?>
                                    <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md font-medium">#<?php echo trim(htmlspecialchars($tg)); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-1 text-amber-500 mt-2">
                                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="text-sm font-bold text-slate-700"><?php echo $doctor['rating']; ?></span>
                                <span class="text-xs text-slate-400 mr-1">
                                    <?php if($doctor['review_count'] > 0): ?>
                                        (<?php echo $doctor['review_count']; ?> نظر مراجعین)
                                    <?php else: ?>
                                        (امتیاز تخصصی آسنا)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <button type="button" class="w-full mt-5 border-2 border-indigo-100 bg-indigo-50/50 text-indigo-700 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-600 hover:border-indigo-600 hover:text-white transition-all shadow-sm select-btn">انتخاب پزشک</button>
                    </div>
                    <?php endforeach; ?>

                </div>
            </section>
            
            <!-- Scheduling Interface -->
            <section class="space-y-6">
                <h2 class="text-title-lg font-title-lg text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500">calendar_month</span>
                    ۲. انتخاب تاریخ و زمان
                </h2>
                
                <!-- Date Picker -->
                <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar" id="dates-list">
                    <div class="text-on-surface-variant text-sm py-4 px-2">لطفاً ابتدا یک پزشک را انتخاب کنید تا روزهای حضور نمایش داده شود.</div>
                </div>
                
                <!-- Time Slots Grid -->
                <div class="space-y-4 hidden" id="times-list">
                    <!-- Dynamic time slots will be generated here by JS -->
                </div>
            </section>

            <!-- Pet Information & Purpose Section -->
            <section class="space-y-6 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/50">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-title-lg font-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">pets</span>
                        اطلاعات بیمار و علت مراجعه
                    </h2>
                </div>
                <p class="text-body-md text-on-surface-variant mb-4">لطفاً مشخصات حیوان و علت مراجعه به پزشک را مشخص کنید.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if(count($user_pets) > 0): ?>
                    <div class="col-span-1 md:col-span-2 space-y-2 mb-2">
                        <label class="text-label-md font-bold text-on-surface-variant">انتخاب از حیوانات ثبت شده شما</label>
                        <select class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container bg-white text-sm" onchange="if(this.value){ const p = JSON.parse(this.value); document.getElementById('pet_type').value = p.type; document.getElementById('pet_race').value = p.race; checkFormCompleteness(); }">
                            <option value="">-- انتخاب کنید یا اطلاعات را به صورت دستی وارد کنید --</option>
                            <?php foreach($user_pets as $pet): ?>
                                <option value='<?php echo json_encode(["type" => $pet["type"], "race" => $pet["race"]]); ?>'><?php echo htmlspecialchars($pet['name'] . ' (' . $pet['type'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant">نوع حیوان</label>
                        <div class="relative">
                            <select name="pet_type" id="pet_type" required class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors pr-4 pl-10" onchange="checkFormCompleteness()">
                                <option value="">انتخاب کنید...</option>
                                <option value="سگ">سگ</option>
                                <option value="گربه">گربه</option>
                                <option value="پرنده">پرنده</option>
                                <option value="جونده">جونده (خرگوش، همستر و...)</option>
                                <option value="خزنده">خزنده</option>
                                <option value="سایر">سایر</option>
                            </select>
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant">نژاد (اختیاری)</label>
                        <input type="text" name="pet_race" id="pet_race" value="" placeholder="مثال: پرشین، ژرمن و..."
                               class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors" />
                    </div>

                    <!-- Purpose of Visit -->
                    <div class="col-span-1 md:col-span-2 space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant flex items-center gap-1">
                            <span class="material-symbols-outlined text-primary text-sm">medical_services</span>
                            علت و هدف مراجعه (سرویس مورد نظر)
                        </label>
                        <div class="relative">
                            <select name="visit_purpose" id="visit_purpose" class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors pr-4 pl-10">
                                <option value="معاینه عمومی و چکاپ دوره ای">معاینه عمومی و چکاپ دوره ای</option>
                                <option value="واکسیناسیون و انگل‌زدایی جامع">واکسیناسیون و انگل‌زدایی جامع</option>
                                <option value="دندانپزشکی و جرم‌گیری تخصصی">دندانپزشکی و جرم‌گیری تخصصی</option>
                                <option value="مشاوره و جراحی‌های تخصصی">مشاوره و جراحی‌های تخصصی</option>
                                <option value="مشاوره تغذیه، رشد و رژیم درمانی">مشاوره تغذیه، رشد و رژیم درمانی</option>
                                <option value="کاشت میکروچیپ و صدور شناسنامه بین‌المللی">کاشت میکروچیپ و صدور شناسنامه بین‌المللی</option>
                            </select>
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <!-- Initial Pet Symptoms / Notes -->
                    <div class="col-span-1 md:col-span-2 space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant flex items-center gap-1">
                            <span class="material-symbols-outlined text-primary text-sm">edit_note</span>
                            توضیحات و شرح حال اولیه پت (اختیاری)
                        </label>
                        <textarea name="pet_notes" id="pet_notes" rows="2" placeholder="اگر حیوان خانگی شما علائم خاصی دارد یا نکته‌ای برای پزشک دارید بنویسید..." class="w-full p-3 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors resize-none outline-none"></textarea>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Right Side: Sticky Summary Sidebar -->
        <aside class="w-full lg:w-[400px] shrink-0">
            <div class="sticky top-28 bg-white rounded-3xl border border-slate-200 shadow-2xl shadow-indigo-900/5 space-y-8 p-8 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-full h-2 bg-gradient-to-r from-indigo-500 to-pink-500"></div>
                <h2 class="text-2xl font-black text-slate-800 mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-pink-500">receipt_long</span>
                    خلاصه نوبت
                </h2>
                <div class="space-y-6">
                    <!-- Selected Doctor Summary -->
                    <div id="summary-doctor" class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-100 rounded-2xl opacity-50 transition-all duration-300 group">
                        <img id="summary-doctor-img" class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-md transition-transform group-hover:scale-110" src="assets/images/presentation-dog.jpg"/>
                        <div>
                            <p class="text-xs font-bold text-slate-400 mb-1">پزشک انتخابی</p>
                            <h4 id="summary-doctor-name" class="text-lg font-bold text-slate-800">پزشک را انتخاب کنید</h4>
                        </div>
                    </div>
                    
                    <!-- Details List -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-body-md font-body-md">
                            <div class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                                تاریخ
                            </div>
                            <span id="summary-date" class="text-on-surface font-semibold text-on-surface-variant">انتخاب نشده</span>
                        </div>
                        <div class="flex justify-between items-center text-body-md font-body-md">
                            <div class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px]">schedule</span>
                                ساعت
                            </div>
                            <span id="summary-time" class="text-on-surface font-semibold text-on-surface-variant">انتخاب نشده</span>
                        </div>
                        <div class="flex justify-between items-center text-body-md font-body-md">
                            <div class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px]">location_on</span>
                                کلینیک
                            </div>
                            <span class="text-on-surface font-semibold">شعبه مرکزی ونک</span>
                        </div>
                    </div>
                    
                    <div class="border-t border-slate-200 pt-6">
                        <div class="flex justify-between items-center mb-8 bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                            <span class="text-lg font-bold text-slate-700">هزینه ویزیت</span>
                            <div class="text-right flex items-baseline gap-1.5">
                                <span id="summary-price" class="text-2xl font-black text-indigo-600 tracking-tight">---</span>
                                <span class="text-sm font-bold text-slate-500">تومان</span>
                            </div>
                        </div>
                        
                        <button type="submit" id="submit-btn" disabled class="w-full bg-slate-100 text-slate-400 py-4 rounded-xl text-lg font-bold transition-all duration-300 flex justify-center items-center gap-2 cursor-not-allowed">
                            لطفا فرم را تکمیل کنید
                        </button>
                        
                        <p class="mt-4 text-center text-label-sm font-label-sm text-on-surface-variant">
                            امکان لغو رایگان تا ۲۴ ساعت قبل از نوبت
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </form>
</main>

<script>
    // Global Booked Slots from PHP
    const bookedSlots = <?php echo $booked_slots_json; ?>;
    
    // Booking interactive logic
    let selectedDoctorId = null;
    let selectedDoctorSchedule = null;
    let selectedDate = null;
    let selectedTime = null;

    const daysMap = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    const daysFa = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

    function selectDoctor(card) {
        // Reset all
        document.querySelectorAll('.doctor-card').forEach(c => {
            c.classList.remove('selected', 'border-secondary-container');
            c.querySelector('.select-btn').className = "w-full mt-4 border border-primary text-primary py-2 rounded-lg text-label-lg font-label-lg hover:bg-primary-container hover:text-white transition-colors select-btn";
            c.querySelector('.select-btn').textContent = "انتخاب پزشک";
        });
        
        // Select this
        card.classList.add('selected', 'border-secondary-container');
        card.querySelector('.select-btn').className = "w-full mt-4 bg-primary text-white py-2 rounded-lg text-label-lg font-label-lg select-btn";
        card.querySelector('.select-btn').textContent = "انتخاب شد";
        
        selectedDoctorId = card.dataset.id;
        document.getElementById('input_doctor_id').value = selectedDoctorId;
        
        // Parse Schedule
        try {
            selectedDoctorSchedule = JSON.parse(card.dataset.schedule || "{}");
        } catch(e) {
            selectedDoctorSchedule = {};
        }
        
        // Update summary
        document.getElementById('summary-doctor').classList.remove('opacity-50');
        document.getElementById('summary-doctor-name').textContent = card.dataset.name;
        document.getElementById('summary-doctor-img').src = card.dataset.image;
        
        // Format price
        let priceStr = new Intl.NumberFormat('fa-IR').format(card.dataset.price);
        document.getElementById('summary-price').textContent = priceStr;
        
        // Reset Date & Time
        selectedDate = null;
        selectedTime = null;
        document.getElementById('input_date').value = "";
        document.getElementById('input_time').value = "";
        document.getElementById('summary-date').textContent = "انتخاب نشده";
        document.getElementById('summary-time').textContent = "انتخاب نشده";
        
        // Parse Services & Populate visit_purpose dropdown
        const purposeSelect = document.getElementById('visit_purpose');
        if (purposeSelect) {
            try {
                const services = JSON.parse(card.dataset.services || "[]");
                if (Array.isArray(services) && services.length > 0) {
                    purposeSelect.innerHTML = '';
                    services.forEach(srv => {
                        const opt = document.createElement('option');
                        opt.value = srv.name || srv.title || srv;
                        opt.textContent = `${srv.name || srv.title || srv} ${srv.duration ? '(' + srv.duration + ')' : ''}`;
                        purposeSelect.appendChild(opt);
                    });
                }
            } catch(e) {}
        }
        
        renderDates();
        updateStepper();
        checkFormCompleteness();
    }

    function renderDates() {
        const datesList = document.getElementById('dates-list');
        const timesList = document.getElementById('times-list');
        datesList.innerHTML = '';
        timesList.innerHTML = '';
        timesList.classList.add('hidden');
        
        if (Object.keys(selectedDoctorSchedule).length === 0) {
            datesList.innerHTML = '<div class="text-on-surface-variant text-sm py-4 px-2">این پزشک هنوز برنامه حضور خود را ثبت نکرده است.</div>';
            return;
        }

        let addedCount = 0;
        let d = new Date();
        
        // Check next 14 days
        for (let i = 0; i < 14; i++) {
            d.setDate(d.getDate() + 1); // start from tomorrow
            let dayIndex = d.getDay();
            let dayKey = daysMap[dayIndex];
            
            if (selectedDoctorSchedule[dayKey]) {
                // Doctor is available on this day
                addedCount++;
                let displayDay = daysFa[dayIndex];
                let displayNum = d.getDate();
                let monthStr = (d.getMonth() + 1).toString().padStart(2, '0');
                let dayStr = d.getDate().toString().padStart(2, '0');
                let valueDate = d.getFullYear() + "-" + monthStr + "-" + dayStr; // YYYY-MM-DD format for DB
                
                let card = document.createElement('div');
                card.className = "date-card flex-shrink-0 w-20 h-24 glass-card rounded-lg flex flex-col items-center justify-center cursor-pointer border-2 border-transparent hover:bg-surface-container transition-colors";
                card.dataset.date = valueDate;
                card.dataset.daykey = dayKey;
                card.dataset.display = displayDay + "، " + displayNum;
                card.onclick = function() { selectDate(this); };
                
                card.innerHTML = `
                    <span class="text-label-sm font-label-sm text-on-surface-variant">${displayDay}</span>
                    <span class="text-headline-md font-headline-md text-on-surface">${displayNum}</span>
                `;
                datesList.appendChild(card);
            }
        }
        
        if (addedCount === 0) {
            datesList.innerHTML = '<div class="text-on-surface-variant text-sm py-4 px-2">پزشک در ۲ هفته آینده حضور ندارد.</div>';
        }
    }

    function selectDate(card) {
        document.querySelectorAll('.date-card').forEach(c => c.classList.remove('selected', 'border-b-4'));
        card.classList.add('selected', 'border-b-4');
        
        selectedDate = card.dataset.date;
        document.getElementById('input_date').value = selectedDate;
        document.getElementById('input_date').dispatchEvent(new Event('change'));
        
        document.getElementById('summary-date').textContent = card.dataset.display;
        document.getElementById('summary-date').classList.remove('text-on-surface-variant');
        
        // Reset Time
        selectedTime = null;
        document.getElementById('input_time').value = "";
        document.getElementById('summary-time').textContent = "انتخاب نشده";
        
        renderTimes(card.dataset.daykey, selectedDate);
        
        updateStepper();
        checkFormCompleteness();
    }

    function generateTimeSlots(start, end) {
        if (!start || !end) return [];
        let slots = [];
        let curr = new Date(`1970-01-01T${start}:00`);
        let endT = new Date(`1970-01-01T${end}:00`);
        
        while (curr < endT) {
            let h = curr.getHours().toString().padStart(2, '0');
            let m = curr.getMinutes().toString().padStart(2, '0');
            slots.push(`${h}:${m}`);
            curr.setMinutes(curr.getMinutes() + 45);
        }
        return slots;
    }

    function renderTimes(dayKey, dateStr) {
        const timesList = document.getElementById('times-list');
        timesList.innerHTML = '';
        timesList.classList.remove('hidden');
        
        let daySched = selectedDoctorSchedule[dayKey];
        if (!daySched) return;
        
        let morningSlots = (daySched.m_active !== false) ? generateTimeSlots(daySched.m_start, daySched.m_end) : [];
        let afternoonSlots = (daySched.a_active !== false) ? generateTimeSlots(daySched.a_start, daySched.a_end) : [];
        
        let bookedForDate = (bookedSlots[selectedDoctorId] && bookedSlots[selectedDoctorId][dateStr]) ? bookedSlots[selectedDoctorId][dateStr] : [];

        let buildSection = (title, icon, slots, label) => {
            if (slots.length === 0) return '';
            let html = `
                <div class="flex items-center gap-2 text-on-surface-variant ${title.includes('عصر') ? 'pt-4' : ''}">
                    <span class="material-symbols-outlined text-[20px]">${icon}</span>
                    <span class="text-label-lg font-label-lg">${title}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mt-2">
            `;
            
            slots.forEach(time => {
                let isBooked = bookedForDate.includes(time);
                if (isBooked) {
                    html += `<button type="button" class="py-2 px-2 rounded-lg bg-[#fd8100]/10 text-[#fd8100] border border-[#fd8100]/40 text-body-md font-body-md cursor-not-allowed text-center opacity-80 flex flex-col items-center justify-center gap-1" disabled>
                                <span>${time}</span>
                                <span class="text-[10px] font-bold">رزرو شده</span>
                             </button>`;
                } else {
                    html += `<button type="button" class="time-btn py-3 px-4 rounded-lg bg-white border border-outline-variant text-body-md font-body-md hover:border-primary transition-colors text-center flex items-center justify-center"
                                data-time="${time}" onclick="selectTime(this, '${time}', '${label}')">${time}</button>`;
                }
            });
            
            html += `</div>`;
            return html;
        };
        
        if (morningSlots.length > 0) {
            timesList.innerHTML += buildSection(`صبح (${daySched.m_start} - ${daySched.m_end})`, 'light_mode', morningSlots, 'صبح');
        } else if (daySched.m_active === false) {
            timesList.innerHTML += `<div class="text-on-surface-variant text-sm py-2 px-2 opacity-80"><span class="material-symbols-outlined text-[16px] align-text-bottom ml-1">info</span>پزشک در شیفت صبح حضور ندارد.</div>`;
        }

        if (afternoonSlots.length > 0) {
            timesList.innerHTML += buildSection(`بعد از ظهر (${daySched.a_start} - ${daySched.a_end})`, 'wb_sunny', afternoonSlots, 'عصر');
        } else if (daySched.a_active === false) {
            timesList.innerHTML += `<div class="text-on-surface-variant text-sm py-2 px-2 opacity-80"><span class="material-symbols-outlined text-[16px] align-text-bottom ml-1">info</span>پزشک در شیفت عصر حضور ندارد.</div>`;
        }
    }

    function selectTime(btn, timeValue, label) {
        document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        
        selectedTime = timeValue; // We'll store HH:MM in DB
        document.getElementById('input_time').value = selectedTime;
        
        document.getElementById('summary-time').textContent = timeValue + ' ' + label;
        document.getElementById('summary-time').classList.remove('text-on-surface-variant');
        
        updateStepper();
        checkFormCompleteness();
    }

    function updateStepper() {
        if (selectedDoctorId) {
            document.getElementById('step-2-indicator').classList.replace('bg-surface-container-high', 'bg-primary-container');
            document.getElementById('step-2-indicator').classList.replace('text-on-surface-variant', 'text-white');
            document.getElementById('step-2-text').classList.replace('text-on-surface-variant', 'text-primary');
        }
        if (selectedDoctorId && selectedDate && selectedTime) {
            document.getElementById('step-3-indicator').classList.replace('bg-surface-container-high', 'bg-primary-container');
            document.getElementById('step-3-indicator').classList.replace('text-on-surface-variant', 'text-white');
            document.getElementById('step-3-text').classList.replace('text-on-surface-variant', 'text-primary');
        }
    }

    function checkFormCompleteness() {
        const submitBtn = document.getElementById('submit-btn');
        const petType = document.getElementById('pet_type').value;
        
        if (selectedDoctorId && selectedDate && selectedTime && petType) {
            submitBtn.disabled = false;
            submitBtn.className = "w-full bg-[#f97316] text-white py-4 rounded-xl text-title-lg font-title-lg font-bold hover:bg-[#ea580c] transition-colors shadow-lg shadow-secondary-container/20 flex justify-center items-center gap-2";
            submitBtn.textContent = "تأیید و ادامه پرداخت";
        } else {
            submitBtn.disabled = true;
            submitBtn.className = "w-full bg-surface-container-high text-on-surface-variant py-4 rounded-xl text-title-lg font-title-lg font-bold transition-all flex justify-center items-center gap-2 cursor-not-allowed";
            submitBtn.textContent = "لطفا فرم را تکمیل کنید";
        }
    }
</script>

<script src="assets/js/booking.js"></script>

<?php include 'includes/footer.php'; ?>