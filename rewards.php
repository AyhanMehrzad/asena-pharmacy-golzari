<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $_SESSION['reward_success'] ?? '';
$error = $_SESSION['reward_error'] ?? '';
unset($_SESSION['reward_success'], $_SESSION['reward_error']);

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch promo codes
$stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$promo_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>
<style>
        :root {
            --primary-blue: #002d72;
            --action-orange: #fd8100;
        }
        .settings-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .settings-card:hover {
            box-shadow: 0px 4px 12px rgba(0, 45, 114, 0.08);
        }
        .input-focus-ring:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 1px var(--primary-blue);
        }
</style>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                "error-container": "#ffdad6",
                "inverse-primary": "#b1c5ff",
                "secondary-container": "#fd8100",
                "on-secondary-fixed-variant": "#723700",
                "surface-container-high": "#e8e8e8",
                "surface-variant": "#e2e2e2",
                "on-primary-fixed-variant": "#224489",
                "tertiary-fixed-dim": "#abcae5",
                "surface-tint": "#3d5ca2",
                "on-secondary-fixed": "#301400",
                "on-tertiary": "#ffffff",
                "inverse-surface": "#2f3131",
                "status-warning": "#FFC60A",
                "on-background": "#1a1c1c",
                "error": "#ba1a1a",
                "surface-container": "#eeeeee",
                "surface-alt": "#F8F9FA",
                "surface-bright": "#f9f9f9",
                "on-error": "#ffffff",
                "tertiary-fixed": "#cae6ff",
                "status-paused": "#757575",
                "surface-dim": "#dadada",
                "status-active": "#2E7D32",
                "primary": "#001a48",
                "surface": "#f9f9f9",
                "background": "#f9f9f9",
                "secondary-fixed": "#ffdcc6",
                "outline-variant": "#c4c6d2",
                "surface-container-highest": "#e2e2e2",
                "on-secondary": "#ffffff",
                "on-secondary-container": "#5d2c00",
                "on-primary-container": "#7a97e2",
                "tertiary": "#001f31",
                "surface-container-lowest": "#ffffff",
                "tertiary-container": "#133449",
                "on-tertiary-fixed": "#001e2f",
                "on-tertiary-container": "#7f9db6",
                "secondary": "#954a00",
                "primary-fixed": "#dae2ff",
                "on-error-container": "#93000a",
                "secondary-fixed-dim": "#ffb785",
                "primary-container": "#002d72",
                "on-tertiary-fixed-variant": "#2c4a60",
                "on-surface": "#1a1c1c",
                "outline": "#747782",
                "primary-fixed-dim": "#b1c5ff",
                "inverse-on-surface": "#f0f1f1",
                "surface-container-low": "#f3f3f4",
                "on-primary": "#ffffff",
                "on-primary-fixed": "#001946",
                "on-surface-variant": "#444651"
              },
              "borderRadius": {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
              },
              "spacing": {
                "gutter": "16px",
                "margin-desktop": "24px",
                "margin-mobile": "16px",
                "base": "4px",
                "container-max": "1280px"
              },
              "fontFamily": {
                "label-sm": ["Geist"],
                "body-lg": ["Geist"],
                "label-lg": ["Geist"],
                "headline-lg-mobile": ["Geist"],
                "display-lg": ["Geist"],
                "title-lg": ["Geist"],
                "headline-lg": ["Geist"],
                "headline-md": ["Geist"],
                "body-md": ["Geist"]
              },
              "fontSize": {
                "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                "label-lg": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}],
                "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "600"}],
                "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
              }
            }
          }
        }
</script>
<!-- Main Content Canvas -->
<main class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-8 md:py-12 flex flex-col md:flex-row gap-8">
<!-- Sidebar Navigation (High-End Workstation Feel) -->
<aside class="w-full md:w-[280px] shrink-0 space-y-2">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 mb-4 flex flex-col items-center">
                <div class="w-24 h-24 bg-primary-container text-white rounded-full flex items-center justify-center mb-4 text-4xl font-bold shadow-sm">
                    <?php echo mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8'); ?>
                </div>
                <h3 class="text-title-lg font-bold text-on-surface mb-1"><?php echo htmlspecialchars($user['name'] ?? 'کاربر مهمان'); ?></h3>
                <p class="text-label-sm text-on-surface-variant"><?php echo htmlspecialchars($user['phone']); ?></p>
            </div>
            
            <a href="profile.php" class="flex items-center gap-3 p-4 rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-bold text-body-md transition-colors">
                <span class="material-symbols-outlined text-[24px]">person</span>
                اطلاعات حساب کاربری
            </a>
            <a href="profile_settings.php" class="flex items-center gap-3 p-4 rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-bold text-body-md transition-colors">
                <span class="material-symbols-outlined text-[24px]">settings</span>
                تنظیمات
            </a>
            <a href="rewards.php" class="flex items-center gap-3 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-bold text-body-md transition-colors">
                <span class="material-symbols-outlined text-[24px]">card_giftcard</span>
                امتیاز وفاداری و جوایز
            </a>
            <a href="#" class="flex items-center gap-3 p-4 rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-bold text-body-md transition-colors">
                <span class="material-symbols-outlined text-[24px]">help</span>
                پشتیبانی
            </a>
            <a href="logout.php" class="flex items-center gap-3 p-4 rounded-xl text-error hover:bg-error/10 font-bold text-body-md transition-colors mt-4">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                خروج از حساب
            </a>
</aside>
<!-- Settings Content -->
<div class="flex-grow space-y-8">
<header>
<h1 class="font-headline-lg text-headline-lg text-primary mb-2">امتیاز وفاداری و جوایز</h1>
<p class="text-on-surface-variant font-body-md">امتیازات خود را به کدهای تخفیف تبدیل کنید!</p>
</header>
<?php if ($success): ?>
    <div class="bg-status-active/10 text-status-active p-4 rounded-xl flex items-center gap-3 border border-status-active/20 mb-4">
        <span class="material-symbols-outlined">check_circle</span>
        <span class="font-bold text-sm"><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-error/10 text-error p-4 rounded-xl flex items-center gap-3 border border-error/20 mb-4">
        <span class="material-symbols-outlined">error</span>
        <span class="font-bold text-sm"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Points Display -->
<div class="bg-gradient-to-r from-primary to-primary-container text-white p-8 rounded-xl shadow-lg flex justify-between items-center relative overflow-hidden">
    <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
        <span class="material-symbols-outlined text-[150px] transform translate-x-10 -translate-y-10">stars</span>
    </div>
    <div class="z-10">
        <h2 class="text-label-lg opacity-80 mb-1">امتیاز فعلی شما</h2>
        <p class="text-display-lg font-bold"><?php echo number_format($user['loyalty_points'] ?? 0); ?> <span class="text-title-lg font-normal">امتیاز</span></p>
    </div>
    <div class="z-10 hidden md:block text-left opacity-90 max-w-xs">
        <p class="text-body-md leading-relaxed">هر ماه با ورود به حساب ۲۰ امتیاز بگیرید! همچنین با رزرو نوبت و خریدهای آینده امتیازات بیشتری کسب کنید.</p>
    </div>
</div>

<!-- Redeem Rewards -->
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden settings-card mt-8">
    <div class="p-6 border-b border-outline-variant bg-surface-alt">
        <h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
            <span class="material-symbols-outlined">shopping_bag</span>
            دریافت کد تخفیف
        </h2>
    </div>
    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php 
        $rewards = [
            ['cost' => 500, 'discount' => 5],
            ['cost' => 1000, 'discount' => 10],
            ['cost' => 1500, 'discount' => 15],
            ['cost' => 2000, 'discount' => 20]
        ];
        foreach ($rewards as $r): 
            $can_afford = ($user['loyalty_points'] >= $r['cost']);
        ?>
        <div class="border border-outline-variant rounded-xl p-6 flex justify-between items-center bg-surface hover:shadow-md transition-shadow">
            <div>
                <h3 class="font-bold text-title-lg text-primary mb-1"><?php echo $r['discount']; ?>٪ تخفیف</h3>
                <p class="text-label-sm text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-status-warning">stars</span>
                    <?php echo $r['cost']; ?> امتیاز
                </p>
            </div>
            <form method="POST" action="actions/rewards_action.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="redeem">
                <input type="hidden" name="cost" value="<?php echo $r['cost']; ?>">
                <input type="hidden" name="discount" value="<?php echo $r['discount']; ?>">
                <button type="submit" 
                        class="px-6 py-2 rounded-lg font-bold text-sm transition-all <?php echo $can_afford ? 'bg-secondary text-on-secondary hover:opacity-90' : 'bg-surface-variant text-on-surface-variant/50 cursor-not-allowed'; ?>" 
                        <?php echo $can_afford ? '' : 'disabled'; ?>>
                    دریافت کد
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- My Promo Codes -->
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden settings-card mt-8">
    <div class="p-6 border-b border-outline-variant bg-surface-alt">
        <h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
            <span class="material-symbols-outlined">local_activity</span>
            کدهای تخفیف من
        </h2>
    </div>
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-surface-container-low text-on-surface-variant font-label-sm border-b border-outline-variant">
                <tr>
                    <th class="p-4">کد تخفیف</th>
                    <th class="p-4">درصد تخفیف</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">تاریخ دریافت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant font-body-md">
                <?php if (empty($promo_codes)): ?>
                    <tr><td colspan="4" class="p-8 text-center text-on-surface-variant">شما هنوز هیچ کد تخفیفی دریافت نکرده‌اید.</td></tr>
                <?php else: ?>
                    <?php foreach ($promo_codes as $code): ?>
                        <tr class="hover:bg-surface-alt transition-colors">
                            <td class="p-4 font-bold font-mono text-primary" dir="ltr"><?php echo htmlspecialchars($code['code']); ?></td>
                            <td class="p-4 text-status-active font-bold"><?php echo htmlspecialchars($code['discount_percentage']); ?>٪</td>
                            <td class="p-4">
                                <?php if ($code['is_used']): ?>
                                    <span class="px-2 py-1 bg-surface-variant text-on-surface-variant rounded-full text-xs font-bold">استفاده شده</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-status-active/10 text-status-active rounded-full text-xs font-bold">فعال</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-on-surface-variant text-sm" dir="ltr"><?php echo date('Y-m-d H:i', strtotime($code['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</div>
</main>
<!-- BottomNavBar (Mobile Only) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 pb-4 pt-2 bg-surface-container-lowest shadow-[0px_-4px_12px_rgba(0,45,114,0.08)] rounded-t-xl">
<a class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1" href="#">
<span class="material-symbols-outlined">home</span>
<span class="font-label-sm text-label-sm">خانه</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1" href="#">
<span class="material-symbols-outlined">storefront</span>
<span class="font-label-sm text-label-sm">فروشگاه</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1" href="#">
<span class="material-symbols-outlined">medical_services</span>
<span class="font-label-sm text-label-sm">کلینیک</span>
</a>
<a class="flex flex-col items-center justify-center bg-secondary-container text-on-secondary-container rounded-xl px-4 py-2 scale-105 shadow-md" href="#">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
<span class="font-label-sm text-label-sm">پروفایل</span>
</a>
</nav>
<script>
        // Micro-interaction for input hover/focus states
        document.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('focus', () => {
                el.parentElement.classList.add('shadow-md');
            });
            el.addEventListener('blur', () => {
                el.parentElement.classList.remove('shadow-md');
            });
        });
    </script>
</body></html>