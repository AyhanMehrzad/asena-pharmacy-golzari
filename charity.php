<?php
include 'includes/header.php';

// Fetch active campaigns
$stmt = $pdo->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
$campaigns = $stmt->fetchAll();

// Fetch top 5 donors
$stmt = $pdo->query("
    SELECT donor_name, SUM(amount) as total_donated 
    FROM donations 
    WHERE status = 'successful' AND donor_name != 'ناشناس'
    GROUP BY donor_name 
    ORDER BY total_donated DESC 
    LIMIT 5
");
$topDonors = $stmt->fetchAll();

// Fetch recent donations
$stmt = $pdo->query("
    SELECT d.donor_name, d.amount, d.created_at, c.title as campaign_title 
    FROM donations d 
    LEFT JOIN campaigns c ON d.campaign_id = c.id 
    WHERE d.status = 'successful' 
    ORDER BY d.created_at DESC 
    LIMIT 10
");
$recentDonations = $stmt->fetchAll();
?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />

<main class="w-full max-w-[1400px] mx-auto px-4 lg:px-8 space-y-16 pb-24 mt-8">
    
    <?php if(isset($_SESSION['charity_success'])): ?>
    <div class="bg-status-active/10 border border-status-active/20 text-status-active p-6 rounded-2xl flex items-center gap-4">
        <span class="material-symbols-outlined text-3xl">check_circle</span>
        <p class="font-bold text-lg"><?php echo $_SESSION['charity_success']; unset($_SESSION['charity_success']); ?></p>
    </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['charity_error'])): ?>
    <div class="bg-error/10 border border-error/20 text-error p-6 rounded-2xl flex items-center gap-4">
        <span class="material-symbols-outlined text-3xl">error</span>
        <p class="font-bold text-lg"><?php echo $_SESSION['charity_error']; unset($_SESSION['charity_error']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Hero Section with Swiper -->
    <section class="relative bg-surface-container-low rounded-[3rem] overflow-hidden">
        <div class="swiper charitySwiper h-[500px] md:h-[600px] lg:h-[700px]">
            <div class="swiper-wrapper">
                <?php if(empty($campaigns)): ?>
                <div class="swiper-slide flex items-center justify-center h-full bg-primary-container text-white p-12 text-center">
                    <div>
                        <span class="material-symbols-outlined text-6xl mb-4">volunteer_activism</span>
                        <h2 class="text-3xl font-bold">در حال حاضر کمپین فعالی وجود ندارد</h2>
                        <p class="mt-4 opacity-80">اما شما همیشه می‌توانید به صورت عمومی حمایت کنید.</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach($campaigns as $camp): 
                        $percent = $camp['goal_amount'] > 0 ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                    ?>
                    <div class="swiper-slide relative h-full group">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-[10s] group-hover:scale-110" style="background-image: url('<?php echo htmlspecialchars($camp['image_url'] ?: 'https://placehold.co/1200x600?text=Campaign'); ?>')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/50 to-transparent"></div>
                        
                        <div class="absolute inset-0 p-8 md:p-16 flex flex-col justify-end text-white">
                            <div class="max-w-3xl space-y-6">
                                <span class="bg-secondary-container text-white px-4 py-2 rounded-full text-xs font-bold shadow-lg inline-flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">favorite</span> کمپین فعال
                                </span>
                                <h2 class="text-4xl md:text-6xl font-bold leading-tight"><?php echo htmlspecialchars($camp['title']); ?></h2>
                                <p class="text-lg md:text-xl font-light opacity-90 leading-relaxed"><?php echo nl2br(htmlspecialchars($camp['description'])); ?></p>
                                
                                <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 mt-8 space-y-4 max-w-xl">
                                    <div class="flex justify-between text-sm font-bold">
                                        <span>تامین شده: <?php echo number_format($camp['current_amount']); ?> تومان</span>
                                        <span>هدف: <?php echo number_format($camp['goal_amount']); ?> تومان</span>
                                    </div>
                                    <div class="w-full h-3 bg-white/20 rounded-full overflow-hidden">
                                        <div class="h-full bg-secondary-container transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                    <div class="text-left text-xs font-bold text-secondary-container"><?php echo $percent; ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Add Pagination -->
            <div class="swiper-pagination !bottom-8"></div>
            <!-- Add Navigation -->
            <div class="swiper-button-prev !text-white !left-8 hidden md:flex"></div>
            <div class="swiper-button-next !text-white !right-8 hidden md:flex"></div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Donation Form -->
        <div class="lg:col-span-8">
            <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-outline-variant/10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-16 h-16 bg-primary-container/10 rounded-2xl flex items-center justify-center text-primary-container">
                        <span class="material-symbols-outlined text-4xl">volunteer_activism</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-primary">فرم حمایت و نیکوکاری</h2>
                        <p class="text-on-surface-variant mt-1">با هر مبلغی، دلی را شاد کنید.</p>
                    </div>
                </div>

                <form action="charity_payment.php" method="POST" class="space-y-8">
                    <div class="space-y-3">
                        <label class="font-bold text-primary text-lg">انتخاب کمپین</label>
                        <select name="campaign_id" class="w-full bg-surface-container-low border-none rounded-xl p-4 focus:ring-2 focus:ring-primary-container text-on-surface">
                            <option value="">کمک عمومی به آسنا (بدون کمپین خاص)</option>
                            <?php foreach($campaigns as $camp): ?>
                                <option value="<?php echo $camp['id']; ?>"><?php echo htmlspecialchars($camp['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="font-bold text-primary text-lg">مبلغ حمایت (تومان)</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <button type="button" onclick="setAmount(50000)" class="btn-amount py-3 rounded-xl border-2 border-outline-variant/30 text-primary font-bold hover:bg-primary-container hover:text-white transition-all">۵۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(100000)" class="btn-amount py-3 rounded-xl border-2 border-outline-variant/30 text-primary font-bold hover:bg-primary-container hover:text-white transition-all">۱۰۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(500000)" class="btn-amount py-3 rounded-xl border-2 border-outline-variant/30 text-primary font-bold hover:bg-primary-container hover:text-white transition-all">۵۰۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(1000000)" class="btn-amount py-3 rounded-xl border-2 border-outline-variant/30 text-primary font-bold hover:bg-primary-container hover:text-white transition-all">۱,۰۰۰,۰۰۰</button>
                        </div>
                        <input type="number" name="amount" id="customAmount" placeholder="مبلغ دلخواه خود را وارد کنید..." required min="1000" class="w-full bg-surface-container-low border-none rounded-xl p-4 focus:ring-2 focus:ring-primary-container text-on-surface font-bold text-lg">
                    </div>

                    <div class="space-y-3">
                        <label class="font-bold text-primary text-lg">نام شما (اختیاری)</label>
                        <input type="text" name="donor_name" placeholder="در صورت خالی بودن، نام کاربری شما استفاده می‌شود" class="w-full bg-surface-container-low border-none rounded-xl p-4 focus:ring-2 focus:ring-primary-container text-on-surface">
                        <label class="flex items-center gap-3 mt-4 cursor-pointer">
                            <input type="checkbox" name="is_anonymous" class="w-5 h-5 rounded text-primary-container focus:ring-primary-container border-outline-variant">
                            <span class="font-bold text-on-surface-variant">می‌خواهم حمایتم به صورت ناشناس ثبت شود.</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-secondary-container text-white py-5 rounded-2xl font-bold text-xl shadow-xl hover:shadow-2xl hover:scale-[1.02] transition-all flex justify-center items-center gap-3">
                        <span class="material-symbols-outlined">payments</span>
                        پرداخت و ثبت حمایت
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar (Top Donors & Recent) -->
        <div class="lg:col-span-4 space-y-8">
            <!-- Top Donors -->
            <div class="bg-primary text-white p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10">
                    <span class="material-symbols-outlined text-[200px]">workspace_premium</span>
                </div>
                <h3 class="text-2xl font-bold mb-6 flex items-center gap-3 relative z-10">
                    <span class="material-symbols-outlined text-secondary-container">stars</span>
                    قهرمانان برتر ما
                </h3>
                
                <div class="space-y-4 relative z-10">
                    <?php if(empty($topDonors)): ?>
                    <p class="text-white/70">هنوز رکوردی ثبت نشده است.</p>
                    <?php else: ?>
                        <?php foreach($topDonors as $i => $donor): ?>
                        <div class="flex items-center gap-4 bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/10">
                            <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center font-bold shadow-inner">
                                <?php echo $i + 1; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold"><?php echo htmlspecialchars($donor['donor_name']); ?></h4>
                                <p class="text-xs text-white/70"><?php echo number_format($donor['total_donated']); ?> تومان</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Donations -->
            <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-outline-variant/10">
                <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container">history</span>
                    آخرین حمایت‌ها
                </h3>
                
                <div class="space-y-6">
                    <?php if(empty($recentDonations)): ?>
                    <p class="text-on-surface-variant">هنوز حمایتی ثبت نشده است.</p>
                    <?php else: ?>
                        <?php foreach($recentDonations as $recent): ?>
                        <div class="flex items-start gap-4 border-b border-outline-variant/20 last:border-0 pb-4 last:pb-0">
                            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container shrink-0">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($recent['donor_name']); ?></h4>
                                <div class="text-xs text-on-surface-variant mt-1">
                                    حمایت از: <?php echo htmlspecialchars($recent['campaign_title'] ?: 'عمومی'); ?>
                                </div>
                                <div class="text-sm font-bold text-secondary-container mt-2"><?php echo number_format($recent['amount']); ?> تومان</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Swiper JS -->
<script src="assets/css/swiper-bundle.min.css"></script>
<script src="assets/js/swiper-bundle.min.js"></script>

<script>
    const swiper = new Swiper('.charitySwiper', {
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        }
    });

    function setAmount(amount) {
        document.getElementById('customAmount').value = amount;
    }
</script>

<?php include 'includes/footer.php'; ?>
