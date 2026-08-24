<!DOCTYPE html>

<html dir="rtl" lang="fa"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>پنل مدیریت آسنا - ASENA Admin</title>
<script src="../assets/js/tailwindcss-cdn.js"></script>
<link href="../assets/css/material-symbols.css" rel="stylesheet"/>
<link href="../assets/css/geist.css" rel="stylesheet"/>
<link href="../assets/css/material-symbols.css" rel="stylesheet"/>
<link href="../assets/css/geist.css" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "surface-variant": "#e2e2e2",
                    "surface-container-high": "#e8e8e8",
                    "secondary-container": "#fd8100",
                    "tertiary": "#001f31",
                    "on-primary-container": "#7a97e2",
                    "on-tertiary-fixed": "#001e2f",
                    "primary": "#001a48",
                    "on-error": "#ffffff",
                    "outline-variant": "#c4c6d2",
                    "outline": "#747782",
                    "primary-fixed-dim": "#b1c5ff",
                    "tertiary-fixed": "#cae6ff",
                    "on-error-container": "#93000a",
                    "on-secondary-fixed": "#301400",
                    "surface-tint": "#3d5ca2",
                    "surface-container-lowest": "#ffffff",
                    "status-paused": "#757575",
                    "error": "#ba1a1a",
                    "tertiary-container": "#133449",
                    "surface": "#f9f9f9",
                    "inverse-surface": "#2f3131",
                    "on-secondary": "#ffffff",
                    "secondary": "#954a00",
                    "surface-dim": "#dadada",
                    "primary-container": "#002d72",
                    "secondary-fixed": "#ffdcc6",
                    "on-secondary-fixed-variant": "#723700",
                    "on-primary": "#ffffff",
                    "on-surface-variant": "#444651",
                    "status-warning": "#FFC60A",
                    "on-secondary-container": "#5d2c00",
                    "surface-container": "#eeeeee",
                    "on-tertiary": "#ffffff",
                    "secondary-fixed-dim": "#ffb785",
                    "on-background": "#1a1c1c",
                    "tertiary-fixed-dim": "#abcae5",
                    "surface-alt": "#F8F9FA",
                    "on-surface": "#1a1c1c",
                    "on-primary-fixed": "#001946",
                    "status-active": "#2E7D32",
                    "on-tertiary-container": "#7f9db6",
                    "surface-container-highest": "#e2e2e2",
                    "inverse-primary": "#b1c5ff",
                    "error-container": "#ffdad6",
                    "on-tertiary-fixed-variant": "#2c4a60",
                    "on-primary-fixed-variant": "#224489",
                    "inverse-on-surface": "#f0f1f1",
                    "surface-container-low": "#f3f3f4",
                    "background": "#f9f9f9",
                    "surface-bright": "#f9f9f9",
                    "primary-fixed": "#dae2ff"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "container-max": "1280px",
                    "margin-desktop": "24px",
                    "base": "4px",
                    "margin-mobile": "16px",
                    "gutter": "16px"
            },
            "fontFamily": {
                    "body-lg": ["Geist"],
                    "label-lg": ["Geist"],
                    "body-md": ["Geist"],
                    "headline-lg-mobile": ["Geist"],
                    "title-lg": ["Geist"],
                    "headline-lg": ["Geist"],
                    "headline-md": ["Geist"],
                    "label-sm": ["Geist"],
                    "display-lg": ["Geist"]
            },
            "fontSize": {
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "label-lg": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "600"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                    "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}]
            }
          },
        },
      }
    </script>
<style>
        body { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .stat-card-shadow {
            box-shadow: 0px 4px 12px rgba(0, 45, 114, 0.08);
        }
        .rtl { direction: rtl; }
    </style>
</head>
<body class="bg-surface text-on-surface selection:bg-secondary-container/30">
<!-- SideNavBar (Right-aligned for Persian RTL) -->
<aside class="fixed inset-y-0 right-0 w-64 bg-tertiary dark:bg-tertiary-container flex flex-col z-40 rtl shadow-lg">
<div class="p-6 flex flex-col gap-2">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-lg bg-secondary-container flex items-center justify-center">
<span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">pets</span>
</div>
<div>
<h1 class="font-title-lg text-title-lg text-tertiary-fixed font-bold leading-tight">آسنا</h1>
<p class="font-label-sm text-label-sm text-on-tertiary-container/70">کنسول مدیریت</p>
</div>
</div>
</div>
<nav class="flex-1 px-4 mt-4 space-y-1">
<!-- Active State: Dashboard -->
<a class="flex items-center gap-3 px-4 py-3 text-secondary-container font-bold border-r-4 border-secondary-container bg-white/5 transition-all" href="#">
<span class="material-symbols-outlined">dashboard</span>
<span class="font-label-lg text-label-lg">پیشخوان مدیریت</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">medical_services</span>
<span class="font-label-lg text-label-lg">مدیریت کلینیک</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">shopping_bag</span>
<span class="font-label-lg text-label-lg">انبار و فروشگاه</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">volunteer_activism</span>
<span class="font-label-lg text-label-lg">خیریه و هدایا</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">group</span>
<span class="font-label-lg text-label-lg">مدیریت کاربران</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">analytics</span>
<span class="font-label-lg text-label-lg">تحلیل و آمار</span>
</a>
</nav>
<div class="p-4 border-t border-white/10">
<button class="w-full bg-secondary-container text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:translate-x-1 duration-200">
<span class="material-symbols-outlined">emergency</span>
<span class="font-label-lg text-label-lg">پشتیبانی اضطراری</span>
</button>
<div class="mt-4 space-y-1">
<a class="flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all" href="#">
<span class="material-symbols-outlined">help</span>
<span class="font-label-sm text-label-sm">مرکز راهنما</span>
</a>
<a class="flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all" href="#">
<span class="material-symbols-outlined text-error">logout</span>
<span class="font-label-sm text-label-sm">خروج از حساب</span>
</a>
</div>
</div>
</aside>
<!-- Main Content Wrapper -->
<main class="mr-64 min-h-screen">
<!-- TopAppBar -->
<header class="sticky top-0 z-50 flex justify-between items-center h-16 px-margin-desktop bg-surface dark:bg-surface-dim shadow-sm border-b border-outline-variant/20">
<div class="flex items-center gap-6">
<div class="relative w-96">
<span class="absolute inset-y-0 right-3 flex items-center text-outline">
<span class="material-symbols-outlined">search</span>
</span>
<input class="w-full pr-10 pl-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-body-md focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="جستجو در پرونده‌ها، موجودی یا تراکنش‌ها..." type="text"/>
</div>
</div>
<div class="flex items-center gap-4">
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors relative">
<span class="material-symbols-outlined text-on-surface-variant">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full border-2 border-surface"></span>
</button>
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-on-surface-variant">settings</span>
</button>
<div class="h-8 w-[1px] bg-outline-variant mx-2"></div>
<div class="flex items-center gap-3 pl-2">
<div class="text-left">
<p class="font-label-lg text-label-lg text-on-surface leading-tight">دکتر سپهر نیک‌پور</p>
<p class="font-label-sm text-label-sm text-on-surface-variant">مدیر کل کلینیک</p>
</div>
<div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden">
<img class="w-full h-full object-cover" data-alt="A professional headshot of a Middle Eastern male veterinarian in a white clinical coat, smiling warmly in a bright, modern veterinary office with blurred medical equipment in the background. High-key lighting, professional corporate photography style, premium clean aesthetic." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAR2jNwOVYMhj9XcV1UR7z0nV_tMr3K8jO4c7vlhKllBRRU5lcutpHFHJzgyBHDDG8lSWV7M3e-53Z3k0nYy9YW4qUQvqrQIdFxH5ckllzs2oz0g43wq0cesY1MbqjD4KaenhKjAbr11Hrfs8V2tY6HSDbu-uJVPj0EjIw9BeQuWv_aJyON5G01A3iiQH5wPjShaZnB001JqdGVFZ84i0TllWUkvKuTDbfk0Qu7Cg6K8jT18lYEjyB7"/>
</div>
</div>
</div>
</header>
<!-- Dashboard Content Container -->
<div class="p-8 space-y-8">
<!-- Section 1: Overview Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<!-- Card: Appointments -->
<div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-primary-container transition-colors">
<div class="flex justify-between items-start mb-4">
<div class="w-12 h-12 rounded-lg bg-primary-container/10 flex items-center justify-center text-primary-container">
<span class="material-symbols-outlined text-[32px]">calendar_today</span>
</div>
<span class="text-status-active font-label-sm flex items-center gap-1">
                            ۱۲٪+ <span class="material-symbols-outlined text-sm">trending_up</span>
</span>
</div>
<h3 class="font-label-lg text-label-lg text-on-surface-variant">نوبت‌های امروز</h3>
<p class="font-display-lg text-display-lg text-primary mt-1">۲۴</p>
<p class="text-label-sm text-on-surface-variant mt-2">۸ نوبت تایید شده، ۱۶ در انتظار</p>
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
<p class="font-display-lg text-display-lg text-primary mt-1">۱,۴۸۲</p>
<p class="text-label-sm text-on-surface-variant mt-2">۳۲ اشتراک جدید در این هفته</p>
</div>
<!-- Card: Donations -->
<div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-primary-container transition-colors">
<div class="flex justify-between items-start mb-4">
<div class="w-12 h-12 rounded-lg bg-tertiary-fixed/30 flex items-center justify-center text-tertiary">
<span class="material-symbols-outlined text-[32px]">volunteer_activism</span>
</div>
<span class="text-status-active font-label-sm flex items-center gap-1">
                            ۱۸٪+ <span class="material-symbols-outlined text-sm">trending_up</span>
</span>
</div>
<h3 class="font-label-lg text-label-lg text-on-surface-variant">مجموع هدایای ماهانه</h3>
<div class="flex items-baseline gap-2 mt-1">
<p class="font-display-lg text-display-lg text-primary">۸۵.۴</p>
<span class="text-label-lg text-on-surface-variant">میلیون تومان</span>
</div>
<p class="text-label-sm text-on-surface-variant mt-2">کمک مالی از ۴۳۲ نیکوکار</p>
</div>
<!-- Card: Inventory -->
<div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30 group hover:border-error transition-colors">
<div class="flex justify-between items-start mb-4">
<div class="w-12 h-12 rounded-lg bg-error-container/50 flex items-center justify-center text-error">
<span class="material-symbols-outlined text-[32px]">warning</span>
</div>
<span class="text-error font-bold font-label-sm">بحرانی</span>
</div>
<h3 class="font-label-lg text-label-lg text-on-surface-variant">هشدار موجودی انبار</h3>
<p class="font-display-lg text-display-lg text-primary mt-1">۱۲</p>
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
<button class="text-primary-container font-label-lg hover:underline">مشاهده تقویم کامل</button>
</div>
<div class="overflow-x-auto">
<table class="w-full text-right">
<thead class="bg-surface-container-low text-on-surface-variant font-label-sm">
<tr>
<th class="px-6 py-3">حیوان / صاحب</th>
<th class="px-6 py-3">نوع خدمات</th>
<th class="px-6 py-3">دامپزشک</th>
<th class="px-6 py-3">زمان نوبت</th>
<th class="px-6 py-3">وضعیت</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/10">
<tr class="hover:bg-surface-container-lowest transition-colors">
<td class="px-6 py-4">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center">
<span class="material-symbols-outlined text-primary scale-75">pets</span>
</div>
<div>
<p class="font-label-lg text-primary">لوسی (سگ ژرمن)</p>
<p class="text-label-sm text-on-surface-variant">علی محمدی</p>
</div>
</div>
</td>
<td class="px-6 py-4 font-body-md">جراحی عقیم‌سازی</td>
<td class="px-6 py-4 font-body-md text-on-surface-variant">دکتر راد</td>
<td class="px-6 py-4 font-body-md text-primary font-bold">۱۰:۳۰</td>
<td class="px-6 py-4">
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-error-container/30 text-error text-label-sm font-bold">
<span class="w-1.5 h-1.5 rounded-full bg-error"></span> در حال جراحی
                                            </span>
</td>
</tr>
<tr class="hover:bg-surface-container-lowest transition-colors">
<td class="px-6 py-4">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center">
<span class="material-symbols-outlined text-primary scale-75">pets</span>
</div>
<div>
<p class="font-label-lg text-primary">برفی (گربه پرشین)</p>
<p class="text-label-sm text-on-surface-variant">سارا پارسا</p>
</div>
</div>
</td>
<td class="px-6 py-4 font-body-md">واکسیناسیون دوره ای</td>
<td class="px-6 py-4 font-body-md text-on-surface-variant">دکتر کریمی</td>
<td class="px-6 py-4 font-body-md">۱۱:۱۵</td>
<td class="px-6 py-4">
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-status-active/10 text-status-active text-label-sm font-bold">
<span class="w-1.5 h-1.5 rounded-full bg-status-active"></span> پذیرش شده
                                            </span>
</td>
</tr>
<tr class="hover:bg-surface-container-lowest transition-colors">
<td class="px-6 py-4">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center">
<span class="material-symbols-outlined text-primary scale-75">pets</span>
</div>
<div>
<p class="font-label-lg text-primary">تدی (پودل)</p>
<p class="text-label-sm text-on-surface-variant">رضا کیانی</p>
</div>
</div>
</td>
<td class="px-6 py-4 font-body-md">معاینه چک‌آپ</td>
<td class="px-6 py-4 font-body-md text-on-surface-variant">دکتر نیک‌پور</td>
<td class="px-6 py-4 font-body-md">۱۲:۰۰</td>
<td class="px-6 py-4">
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-status-warning/10 text-secondary text-label-sm font-bold">
<span class="w-1.5 h-1.5 rounded-full bg-status-warning"></span> در انتظار
                                            </span>
</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Inventory Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="bg-white p-6 rounded-xl stat-card-shadow border border-outline-variant/30">
<h2 class="font-title-lg text-title-lg text-primary mb-6">پرفروش‌ترین‌های انبار</h2>
<div class="space-y-4">
<div class="flex items-center gap-4 group">
<div class="w-12 h-12 rounded bg-surface-container overflow-hidden">
<img class="w-full h-full object-cover" data-alt="High-quality professional studio photography of a premium pet food bag for cats, modern minimal packaging design with royal blue and silver accents, clear pet brand logo, clean studio lighting on a soft gray background." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAvTjCmjxO1Mg_F95XVn2QtQ7vWv_z6g6xUlbrI0A03vttC9UJZHNHfwNWH9L0ztUqkxxhQCAXcR9HuhaSgAKxAg5_Sqy8GCFfI6oAlsAh-vd3EI2buiEGEwu1Ul7-RCTCgN2RoEcAzpNBVOnjf49FTvgVFVc2EK2i1pzKYUikL4NIWbix6DGA857bdEw1VsvHMxBRWoelE0hy1d1OKJTFyJymWel3OftQ9ynHJ3XUBuvU2FNLwFnqo"/>
</div>
<div class="flex-1">
<p class="font-label-lg text-primary">غذای خشک گربه Royal</p>
<p class="text-label-sm text-on-surface-variant">۴۳ فروش در هفته</p>
</div>
<div class="text-right">
<p class="font-label-lg text-status-active">موجود: ۱۸</p>
</div>
</div>
<div class="flex items-center gap-4 group">
<div class="w-12 h-12 rounded bg-surface-container overflow-hidden">
<img class="w-full h-full object-cover" data-alt="High-quality professional studio photography of a dog supplement bottle with orange and white labeling, health-focused branding, clean professional studio lighting, isolated on a light surface." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuCsWWroIEb215TuwzB2WtfPdyOtwqsnoE6_j7RvaZJ-JzTiMWUyN91ch6FH35QbLiJl8-EJ05jvLrWFsNu52z9-f4f6fhbhmumyfg-tODxnvR_oikXzY6AHujtW07OJcFTwqvAk9F9wYvkOhFwmh5Bt7opy23JpXJ5oa6RqVgjolifAmaNUhDQtSCKOlhuyQmV0C5cDbSUCMYEuuP6-j7SyuGnvvwHyc75MpsutRYSlkcpCU3ku8x"/>
</div>
<div class="flex-1">
<p class="font-label-lg text-primary">مکمل کلسیم سگ Veto</p>
<p class="text-label-sm text-on-surface-variant">۲۹ فروش در هفته</p>
</div>
<div class="text-right">
<p class="font-label-lg text-error">موجود: ۳</p>
</div>
</div>
</div>
</div>
<div class="bg-primary-container p-6 rounded-xl stat-card-shadow flex flex-col justify-between text-white relative overflow-hidden">
<div class="relative z-10">
<h3 class="font-title-lg text-title-lg mb-2">مدیریت سریع موجودی</h3>
<p class="font-body-md opacity-80 mb-6">به‌روزرسانی لحظه‌ای انبار و ثبت ورود کالاهای جدید</p>
<button class="w-full py-3 bg-secondary-container text-white font-bold rounded-lg hover:bg-secondary transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined">edit_square</span>
                                    به‌روزرسانی انبار
                                </button>
</div>
<!-- Subtle abstract decoration -->
<div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
<div class="absolute -top-8 -right-8 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
</div>
</div>
</div>
<!-- Charity Impact (1/3 Column) -->
<div class="lg:col-span-1 space-y-6">
<div class="bg-white rounded-xl stat-card-shadow border border-outline-variant/30 overflow-hidden">
<div class="h-48 relative">
<img class="w-full h-full object-cover" data-alt="Heartwarming photo of a group of street cats huddled together in a cozy, well-built wooden winter shelter with warm straw inside. Soft winter sunlight, snowy background outside the shelter, warm and protective atmosphere, professional photography, high contrast." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDfJOGd0yJtl9EQSGdSCSg0zLjDbJ4ITtJ-jyGn0avNahqcnrw90whBqo72uukyWy-Qwql1DUBTEvJn0Bfosi7MFtmzH35FK3mQSN0Jps4EjHwuL1HawY7LaJaI6m79S_jAYqTIGjg580W3YWxqHwrY0gNMWd-8iNZT-U5oPdTlSXDUj-UmHWJP0CsKFQCXvRqZyoz3N6dPce0EzbCxVGc3V-05zkWFNf9TvfLvfho_JPiiLeDQwzcc"/>
<div class="absolute inset-0 bg-gradient-to-t from-primary/80 to-transparent"></div>
<div class="absolute bottom-4 right-4 left-4">
<span class="bg-secondary-container text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">کمپین ویژه</span>
<h3 class="text-white font-title-lg text-title-lg mt-1">سرپناه زمستانی گربه‌ها</h3>
</div>
</div>
<div class="p-6">
<div class="flex justify-between items-end mb-2">
<div>
<p class="text-label-sm text-on-surface-variant">جمع‌آوری شده:</p>
<p class="font-title-lg text-title-lg text-primary">۶۵,۰۰۰,۰۰۰ تومان</p>
</div>
<p class="text-label-lg font-bold text-secondary">۸۲٪</p>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full mb-6 overflow-hidden">
<div class="h-full bg-secondary-container rounded-full" style="width: 82%"></div>
</div>
<h4 class="font-label-lg text-label-lg text-primary mb-4">آخرین حامیان:</h4>
<div class="space-y-3">
<div class="flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-tertiary-fixed text-primary flex items-center justify-center font-bold text-xs">ن.ر</div>
<p class="font-body-md text-on-surface">نیما راد</p>
</div>
<p class="font-label-sm text-secondary">۵۰۰,۰۰۰ تومان</p>
</div>
<div class="flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-bold text-xs">س.م</div>
<p class="font-body-md text-on-surface">سودابه مرادی</p>
</div>
<p class="font-label-sm text-secondary">۱,۲۰۰,۰۰۰ تومان</p>
</div>
<div class="flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-outline-variant/30 text-primary flex items-center justify-center font-bold text-xs">بی‌نام</div>
<p class="font-body-md text-on-surface">حامی ناشناس</p>
</div>
<p class="font-label-sm text-secondary">۲,۰۰۰,۰۰۰ تومان</p>
</div>
</div>
<button class="w-full mt-6 py-3 border-2 border-primary-container text-primary-container font-bold rounded-lg hover:bg-primary-container/5 transition-all">
                                مدیریت تمامی کمپین‌ها
                            </button>
</div>
</div>
<div class="bg-surface-container-high/50 p-6 rounded-xl border border-outline-variant/20">
<div class="flex items-center gap-3 mb-4 text-primary">
<span class="material-symbols-outlined">event_note</span>
<h3 class="font-title-lg text-title-lg">رویدادهای امروز</h3>
</div>
<ul class="space-y-4">
<li class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-2 h-2 rounded-full bg-primary ring-4 ring-primary/10"></div>
<div class="w-0.5 h-full bg-outline-variant/30 mt-1"></div>
</div>
<div>
<p class="font-label-lg text-primary">جلسه تیم پزشکی</p>
<p class="text-label-sm text-on-surface-variant">ساعت ۱۴:۳۰ - اتاق کنفرانس</p>
</div>
</li>
<li class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-2 h-2 rounded-full bg-secondary-container"></div>
<div class="w-0.5 h-full bg-outline-variant/30 mt-1"></div>
</div>
<div>
<p class="font-label-lg text-primary">رسیدگی به محموله انبار</p>
<p class="text-label-sm text-on-surface-variant">ساعت ۱۶:۰۰ - بخش تدارکات</p>
</div>
</li>
<li class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-2 h-2 rounded-full bg-outline"></div>
</div>
<div>
<p class="font-label-lg text-primary">بازبینی گزارش مالی هفته</p>
<p class="text-label-sm text-on-surface-variant">ساعت ۱۷:۳۰ - سیستم مدیریت</p>
</div>
</li>
</ul>
</div>
</div>
</div>
</div>
</main>
<!-- Floating Action Button (FAB) - Suppression logic: rendered only for Home/Dashboard -->
<button class="fixed bottom-8 left-8 w-14 h-14 bg-secondary-container text-white rounded-full shadow-2xl flex items-center justify-center active:scale-95 transition-transform z-50 group">
<span class="material-symbols-outlined text-[28px] group-hover:rotate-90 transition-transform duration-300">add</span>
<span class="absolute left-16 bg-primary text-white text-label-lg px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
            ثبت پرونده یا کالای جدید
        </span>
</button>
<script>
        // Simple micro-interactions
        document.querySelectorAll('.stat-card-shadow').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
                card.style.transition = 'transform 0.3s ease';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Search bar focus effect
        const searchInput = document.querySelector('input[type="text"]');
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('ring-2', 'ring-primary-container/20');
        });
        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('ring-2', 'ring-primary-container/20');
        });
    </script>
</body></html>