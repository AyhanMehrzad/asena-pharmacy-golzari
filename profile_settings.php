<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $_SESSION['settings_success'] ?? '';
$error = $_SESSION['settings_error'] ?? '';
unset($_SESSION['settings_success'], $_SESSION['settings_error']);

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
            <a href="profile_settings.php" class="flex items-center gap-3 p-4 rounded-xl bg-secondary-container text-on-secondary-container font-bold text-body-md transition-colors">
                <span class="material-symbols-outlined text-[24px]">settings</span>
                تنظیمات
            </a>
            <a href="rewards.php" class="flex items-center gap-3 p-4 rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-bold text-body-md transition-colors">
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
<h1 class="font-headline-lg text-headline-lg text-primary mb-2">تنظیمات حساب کاربری</h1>
<p class="text-on-surface-variant font-body-md">اطلاعات شخصی و نشانی‌های خود را در این بخش مدیریت کنید.</p>
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
<!-- Section 1: Account Information -->
<form method="POST" action="actions/settings_action.php" class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden settings-card">
    <?php echo csrf_field(); ?>
<input type="hidden" name="action" value="update_account">
<div class="p-6 border-b border-outline-variant bg-surface-alt">
<h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
<span class="material-symbols-outlined">account_circle</span>
                        اطلاعات حساب کاربری
                    </h2>
</div>
<div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
<!-- Username -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">نام کاربری</label>
<div class="relative group">
<input name="name" class="w-full h-12 bg-surface-container-low border border-outline-variant rounded-lg px-4 font-body-md focus:ring-2 focus:ring-primary-container outline-none transition-all" type="text" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"/>
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-primary opacity-50"><span class="material-symbols-outlined text-sm">edit</span></span>
</div>
</div>
<!-- Email -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">ایمیل</label>
<div class="relative group">
<input name="email" class="w-full h-12 bg-surface-container-low border border-outline-variant rounded-lg px-4 font-body-md focus:ring-2 focus:ring-primary-container outline-none transition-all" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"/>
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-primary opacity-50"><span class="material-symbols-outlined text-sm">edit</span></span>
</div>
</div>
<!-- Phone -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">شماره موبایل (غیرقابل تغییر)</label>
<div class="flex gap-2">
<input class="flex-grow h-12 bg-surface-container-low border border-outline-variant/50 rounded-lg px-4 font-body-md text-on-surface-variant/70 outline-none text-left cursor-not-allowed" dir="ltr" type="text" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled/>
</div>
</div>
<!-- Password -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">رمز عبور جدید (در صورت نیاز)</label>
<div class="relative group">
<input name="password" class="w-full h-12 bg-surface-container-low border border-outline-variant rounded-lg px-4 font-body-md focus:ring-2 focus:ring-primary-container outline-none transition-all text-left" dir="ltr" type="password" placeholder="••••••••••••"/>
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-primary opacity-50"><span class="material-symbols-outlined text-sm">lock</span></span>
</div>
</div>
</div>
<div class="p-6 bg-surface-alt flex justify-end">
<button type="submit" class="bg-primary text-on-primary px-8 py-2 rounded-lg font-label-lg hover:opacity-90 transition-all">ذخیره تغییرات</button>
</div>
</form>
<!-- Section 2: Shipping Address -->
<form method="POST" action="actions/settings_action.php" class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden settings-card">
    <?php echo csrf_field(); ?>
<input type="hidden" name="action" value="update_address">
<div class="p-6 border-b border-outline-variant bg-surface-alt">
<h2 class="font-title-lg text-title-lg text-primary flex items-center gap-2">
<span class="material-symbols-outlined">location_on</span>
                        نشانی ارسال
                    </h2>
</div>
<div class="p-8 space-y-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
<!-- City Selection -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">شهر</label>
<select class="w-full h-12 bg-surface-container-low border border-outline-variant/50 rounded-lg px-4 font-body-md text-on-surface-variant/70 outline-none transition-all cursor-not-allowed appearance-none" disabled>
<option value="تبریز" selected>تبریز (در حال حاضر فقط تبریز)</option>
</select>
<input type="hidden" name="city" value="تبریز">
</div>
<!-- Postal Code -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">کد پستی <span class="text-error">*</span></label>
<input name="postal_code" required class="w-full h-12 bg-surface-container-low border border-outline-variant rounded-lg px-4 font-body-md focus:ring-2 focus:ring-primary-container outline-none transition-all" placeholder="مثلاً: ۱۴۱۵۹۶۳۴۸۷" type="text" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>"/>
</div>
</div>
<!-- Address Input (Full Width) -->
<div class="space-y-2">
<label class="block text-label-lg font-label-lg text-on-surface-variant">نشانی دقیق</label>
<textarea name="address" class="w-full bg-surface-container-low border border-outline-variant rounded-lg p-4 font-body-md focus:ring-2 focus:ring-primary-container outline-none transition-all" placeholder="نام محله، خیابان اصلی، کوچه، پلاک، واحد..." rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
</div>
<!-- Map Integration -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="relative rounded-xl overflow-hidden border border-outline-variant h-64 bg-surface-container z-0" id="map"></div>
<input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($user['latitude'] ?? ''); ?>">
<input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($user['longitude'] ?? ''); ?>">
<p class="text-label-sm text-on-surface-variant mt-2">نشانگر را روی نقشه جابجا کنید یا روی مکان مورد نظر کلیک کنید.</p>
</div>
<div class="p-6 bg-surface-alt flex justify-between items-center">
<p class="text-on-surface-variant font-label-sm max-w-sm">تغییر آدرس ممکن است بر هزینه‌های ارسال در فاکتورهای آینده تاثیر بگذارد.</p>
<button type="submit" class="bg-primary text-on-primary px-8 py-2 rounded-lg font-label-lg hover:opacity-90 transition-all">به‌روزرسانی نشانی</button>
</div>
</form>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
        // Map Initialization
        document.addEventListener("DOMContentLoaded", function() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            
            // Default to Tabriz if no coordinates saved
            let startLat = latInput.value ? parseFloat(latInput.value) : 38.0700;
            let startLng = lngInput.value ? parseFloat(lngInput.value) : 46.2931;
            let zoomLevel = latInput.value ? 15 : 12;

            const map = L.map('map').setView([startLat, startLng], zoomLevel);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let marker = L.marker([startLat, startLng], {draggable: true}).addTo(map);

            const addressInput = document.querySelector('textarea[name="address"]');

            async function reverseGeocode(lat, lng) {
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                    const data = await response.json();
                    if (data && data.display_name) {
                        // Keep user's custom details if any, but replace or prepend?
                        // Let's just overwrite for now since they are picking a new spot.
                        addressInput.value = data.display_name;
                        
                        // Add a subtle flash effect to let user know it updated
                        addressInput.parentElement.classList.add('ring-2', 'ring-primary', 'transition-all');
                        setTimeout(() => addressInput.parentElement.classList.remove('ring-2', 'ring-primary'), 1000);
                    }
                } catch (error) {
                    console.error("Geocoding failed:", error);
                }
            }

            // Update inputs on marker drag
            marker.on('dragend', function(e) {
                const position = marker.getLatLng();
                latInput.value = position.lat;
                lngInput.value = position.lng;
                reverseGeocode(position.lat, position.lng);
            });

            // Update inputs and marker on map click
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                latInput.value = e.latlng.lat;
                lngInput.value = e.latlng.lng;
                reverseGeocode(e.latlng.lat, e.latlng.lng);
            });
        });

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