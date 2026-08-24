<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Auto-close tickets inactive for 24 hours
$pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Fetch all tickets for this user
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
$fmtDateTime = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY - HH:mm');
?>

<main class="w-full max-w-container-max mx-auto py-16 px-4">
    <div class="mb-10 text-center space-y-4">
        <h1 class="text-4xl font-bold text-primary">تاریخچه پشتیبانی و مشاوره</h1>
        <p class="text-on-surface-variant text-lg">سوابق گفتگوهای شما با لئو (هوش مصنوعی) و تیم پشتیبانی آسنا</p>
    </div>

    <div class="bg-surface-container-lowest rounded-[2.5rem] p-8 shadow-sm border border-outline-variant/10">
        <?php if(empty($tickets)): ?>
            <div class="text-center py-16">
                <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">forum</span>
                <h3 class="text-xl font-bold text-on-surface mb-2">هیچ تیکتی یافت نشد</h3>
                <p class="text-on-surface-variant mb-6">تا به حال با هوش مصنوعی یا پشتیبانی صحبت نکرده‌اید.</p>
                <a href="index.php#support-section" class="bg-primary-container text-white px-8 py-3 rounded-full font-bold">شروع گفتگوی جدید</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($tickets as $t): ?>
                <div class="flex flex-col md:flex-row items-center justify-between p-6 bg-surface-container-low rounded-3xl hover:bg-surface-container transition-colors border border-outline-variant/20 gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="w-16 h-16 rounded-full <?php echo $t['mode'] == 'ai' ? 'bg-primary-container text-white' : 'bg-secondary-container text-white'; ?> flex items-center justify-center shrink-0 shadow-inner">
                            <span class="material-symbols-outlined text-3xl"><?php echo $t['mode'] == 'ai' ? 'cruelty_free' : 'support_agent'; ?></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-primary mb-1">
                                <?php echo $t['mode'] == 'ai' ? 'مشاوره هوشمند (لئو)' : 'پشتیبانی انسانی'; ?>
                            </h3>
                            <p class="text-xs text-on-surface-variant font-medium">
                                وضعیت: <?php echo $t['status'] == 'open' ? '<span class="text-emerald-500 font-bold">باز و در جریان</span>' : 'بسته شده'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end">
                        <div class="text-left">
                            <p class="text-xs text-on-surface-variant font-bold mb-1">تاریخ شروع</p>
                            <p class="text-sm font-bold persian-number"><?php echo $fmtDateTime->format(new DateTime($t['created_at'])); ?></p>
                        </div>
                        <?php if($t['status'] == 'open'): ?>
                        <a href="chat.php?ticket_id=<?php echo $t['id']; ?>" class="bg-primary-container text-white px-6 py-2.5 rounded-full text-sm font-bold shadow-md hover:scale-105 transition-transform flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-sm">chat</span>
                            ادامه گفتگو
                        </a>
                        <?php else: ?>
                        <a href="chat.php?ticket_id=<?php echo $t['id']; ?>" class="bg-surface-container-high text-on-surface-variant px-6 py-2.5 rounded-full text-sm font-bold hover:scale-105 transition-transform flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-sm">history</span>
                            مشاهده گفتگو
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
