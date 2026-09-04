<?php
/**
 * ASENA Ultra Visual Blog Builder & Live WYSIWYG Editor
 * Enhanced with:
 * - Ready-made Clinical Article Templates (1-click load)
 * - Rich Component Library (Callouts, FAQs, Warnings, Doctor Tips, Tables, CTAs, Images)
 * - Live SEO & Readability Health Checker
 * - Auto Read-Time & Word Counter
 * - Desktop & Mobile Preview Switcher
 * - Font Size, Color & Highlight Tools
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/blog_service.php';

// Authentication Check: Admin or Doctor
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !in_array($user['role'], ['admin', 'doctor'])) {
    header("Location: index.php");
    exit;
}

$user_role = $user['role'];
$user_name = $user['name'] ?? 'کاربر';
$user_id = (int)$_SESSION['user_id'];

// Load existing post if editing
$post_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
if ($post_id > 0) {
    $post = get_db_blog_by_id($pdo, $post_id);
}

// Handle Form Submission (Save / Publish)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_blog') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_desc = trim($_POST['short_desc'] ?? '');
    $category = $_POST['category'] ?? 'medical';
    $content = $_POST['content'] ?? '';
    $read_time = trim($_POST['read_time'] ?? '۵ دقیقه مطالعه');
    $author_name = trim($_POST['author_name'] ?? 'آسنا');
    $author_role = trim($_POST['author_role'] ?? 'تیم تخصصی و تحریریه آسنا');
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if (empty($title)) {
        $error = 'عنوان مقاله نمی‌تواند خالی باشد.';
    } else {
        $data = [
            'id' => $post_id,
            'title' => $title,
            'slug' => $slug,
            'short_desc' => $short_desc,
            'category' => $category,
            'content' => $content,
            'read_time' => $read_time,
            'author_name' => $author_name,
            'author_role' => $author_role,
            'status' => $status
        ];

        $saved_id = save_db_blog($pdo, $data, $user_id);
        if ($saved_id > 0) {
            $post_id = $saved_id;
            $post = get_db_blog_by_id($pdo, $post_id);
            $message = 'مقاله با موفقیت ذخیره و در پایگاه دانش به‌روزرسانی شد!';
        } else {
            $error = 'خطا در ذخیره‌سازی مقاله در پایگاه داده.';
        }
    }
}

// Set initial field values
$init_title = $post['title'] ?? '';
$init_slug = $post['slug'] ?? '';
$init_short_desc = $post['short_desc'] ?? '';
$init_category = $post['category'] ?? 'medical';
$init_read_time = $post['read_time'] ?? '۵ دقیقه مطالعه';
$init_author_name = $post['author_name'] ?? 'آسنا';
$init_author_role = $post['author_role'] ?? 'تیم تخصصی و تحریریه آسنا';
$init_content = $post['content'] ?? '<p>متن مقاله خود را اینجا تایپ کنید، یا از دکمه <strong>«قالب‌های آماده نگارش»</strong> در بالا استفاده فرمایید تا ساختار کامل مقاله با یک کلیک ایجاد شود.</p>';
$init_status = $post['status'] ?? 'published';
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایشگر پیشرفته مقالات و وبلاگ - ASENA Blog Studio</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/material-symbols.css" rel="stylesheet">
    <link href="assets/css/vazirmatn.css" rel="stylesheet">
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        #canvas-content:focus { outline: none; }
        #canvas-content h2 { font-size: 1.4rem; font-weight: 800; margin-top: 1.75rem; margin-bottom: 0.75rem; color: #0f172a; border-right: 4px solid #002d72; padding-right: 0.75rem; }
        #canvas-content h3 { font-size: 1.2rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem; color: #1e293b; }
        #canvas-content p { margin-bottom: 1rem; line-height: 2.1; color: #334155; }
        #canvas-content ul { list-style-type: disc; padding-right: 1.75rem; margin-bottom: 1rem; color: #334155; }
        #canvas-content ol { list-style-type: decimal; padding-right: 1.75rem; margin-bottom: 1rem; color: #334155; }
        #canvas-content a { color: #0284c7; text-decoration: underline; font-weight: 600; }
        #canvas-content table { width: 100%; margin: 1.5rem 0; border-collapse: collapse; border-radius: 1rem; overflow: hidden; }
        #canvas-content th, #canvas-content td { padding: 0.75rem 1rem; border: 1px solid #e2e8f0; text-align: right; }
        #canvas-content th { background: #f1f5f9; font-weight: 800; color: #0f172a; }
        .toolbar-btn { transition: all 0.15s ease; }
        .toolbar-btn:hover { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
        .toolbar-btn:active { transform: scale(0.95); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .mobile-preview-frame {
            max-width: 420px !important;
            margin-left: auto;
            margin-right: auto;
            border: 10px solid #0f172a;
            border-radius: 36px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);
            overflow-x: hidden;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            background: #ffffff;
        }
        .dark .mobile-preview-frame { background: #020617; }
        .mobile-preview-frame .p-6, .mobile-preview-frame .p-10, .mobile-preview-frame .p-12 {
            padding: 1rem !important;
        }
        .mobile-preview-frame #canvas-title {
            font-size: 1.35rem !important;
            line-height: 1.4 !important;
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen relative pb-32">

<!-- Ambient Background Glows -->
<div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
    <div class="absolute -top-40 right-10 w-[550px] h-[550px] bg-gradient-to-br from-blue-600/15 via-[#002d72]/20 to-teal-500/10 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-5 w-[500px] h-[500px] bg-gradient-to-tr from-indigo-700/15 via-[#0f766e]/15 to-blue-500/10 rounded-full blur-3xl"></div>
</div>

<form id="blog-form" method="POST" action="">
    <input type="hidden" name="action" value="save_blog">
    <input type="hidden" id="input-title" name="title" value="<?= htmlspecialchars($init_title) ?>">
    <input type="hidden" id="input-slug" name="slug" value="<?= htmlspecialchars($init_slug) ?>">
    <input type="hidden" id="input-short-desc" name="short_desc" value="<?= htmlspecialchars($init_short_desc) ?>">
    <input type="hidden" id="input-category" name="category" value="<?= htmlspecialchars($init_category) ?>">
    <input type="hidden" id="input-read-time" name="read_time" value="<?= htmlspecialchars($init_read_time) ?>">
    <input type="hidden" id="input-author-name" name="author_name" value="<?= htmlspecialchars($init_author_name) ?>">
    <input type="hidden" id="input-author-role" name="author_role" value="<?= htmlspecialchars($init_author_role) ?>">
    <input type="hidden" id="input-content" name="content" value="">
    <input type="hidden" id="input-status" name="status" value="<?= htmlspecialchars($init_status) ?>">

    <!-- Top Sticky Bar -->
    <header class="sticky top-0 z-50 bg-white/85 dark:bg-slate-900/85 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">`r`n                <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-8 h-8 object-contain">
                <a href="<?= $user_role === 'admin' ? 'admin/blogs.php' : 'doctor/blogs.php' ?>" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-colors" title="بازگشت به پنل">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
                <div>
                    <h1 class="font-black text-sm md:text-base leading-tight flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-xl">draw</span>
                        استودیوی نگارش مقاله آسنا
                    </h1>
                    <p class="text-[11px] text-slate-500">حالت: <?= $user_role === 'admin' ? 'مدیریت کل سیستم' : 'پزشک معالج کلینیک' ?></p>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2 md:gap-3">
                <!-- Ready-made Templates Trigger -->
                <button type="button" onclick="openTemplatesModal()" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 hover:bg-amber-100 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-base">auto_awesome</span>
                    <span>قالب‌های آماده</span>
                </button>

                <!-- Preview Mode Toggle (Desktop / Mobile) -->
                <div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl p-0.5 border border-slate-200 dark:border-slate-700 text-xs">
                    <button type="button" id="btn-desktop-view" onclick="setPreviewMode('desktop')" class="px-2.5 py-1.5 rounded-lg font-bold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-xs">
                        🖥️ دسکتاپ
                    </button>
                    <button type="button" id="btn-mobile-view" onclick="setPreviewMode('mobile')" class="px-2.5 py-1.5 rounded-lg font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white">
                        📱 موبایل
                    </button>
                </div>

                <select id="select-status" class="text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 outline-none">
                    <option value="published" <?= $init_status === 'published' ? 'selected' : '' ?>>🟢 انتشار عمومی</option>
                    <option value="draft" <?= $init_status === 'draft' ? 'selected' : '' ?>>🟡 پیش‌نویس خصوصی</option>
                </select>

                <?php if ($post_id > 0 && !empty($init_slug)): ?>
                <a href="knowledge_base.php?article=<?= urlencode($init_slug) ?>" target="_blank" class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 hover:bg-slate-100 transition-all">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    <span>مشاهده زنده</span>
                </a>
                <?php endif; ?>

                <button type="submit" onclick="prepareAndSubmit()" class="px-5 py-2 rounded-xl bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs md:text-sm shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>ذخیره و انتشار</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Notification Messages -->
    <?php if (!empty($message)): ?>
    <div class="max-w-5xl mx-auto px-4 mt-6">
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs md:text-sm font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php if (!empty($init_slug)): ?>
            <a href="knowledge_base.php?article=<?= urlencode($init_slug) ?>" target="_blank" class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700">
                مشاهده در سایت ↗
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="max-w-5xl mx-auto px-4 mt-6">
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs md:text-sm font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Writing Canvas Container -->
    <main id="editor-main-container" class="max-w-5xl mx-auto px-4 mt-6 transition-all duration-300">
        
        <!-- Quick Block Inserter Bar (One-Click Component Palette) -->
        <div class="mb-4 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-base">widgets</span>
                    جعبه المان‌های هوشمند:
                </span>
                <span class="text-[11px] text-slate-400 hidden sm:inline">کلیک برای درج خودکار</span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:flex-wrap">
                <!-- 1. Quote Box (Image 3) -->
                <button type="button" onclick="insertCalloutBox()" class="shrink-0 px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-800 dark:text-teal-300 border border-teal-300 dark:border-teal-800 hover:bg-teal-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-teal-600">format_quote</span>
                    <span>کادر نقل‌قول</span>
                </button>

                <!-- 2. FAQ Accordion (Image 4) -->
                <button type="button" onclick="insertFaqAccordion()" class="shrink-0 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-800 hover:bg-blue-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-blue-600">quiz</span>
                    <span>سوال متداول</span>
                </button>

                <!-- 3. Red Warning Alert Box -->
                <button type="button" onclick="insertWarningBox()" class="shrink-0 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800 hover:bg-rose-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-rose-600">warning</span>
                    <span>باکس هشدار</span>
                </button>

                <!-- 4. Doctor Pro Tip Box -->
                <button type="button" onclick="insertDoctorTipBox()" class="shrink-0 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-emerald-600">lightbulb</span>
                    <span>توصیه پزشک</span>
                </button>

                <!-- 5. Clinical Comparison Table -->
                <button type="button" onclick="insertClinicalTable()" class="shrink-0 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800 hover:bg-indigo-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-indigo-600">table_chart</span>
                    <span>جدول مقایسه</span>
                </button>

                <!-- 6. Clinic Appointment CTA Banner -->
                <button type="button" onclick="insertAppointmentBanner()" class="shrink-0 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 hover:bg-amber-100 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-amber-600">calendar_add_on</span>
                    <span>بنر نوبت‌دهی</span>
                </button>

                <!-- 7. Image with Modern Frame & Caption -->
                <button type="button" onclick="insertImagePrompt()" class="shrink-0 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-200 font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-slate-500">add_photo_alternate</span>
                    <span>عکس و کپشن</span>
                </button>
            </div>
        </div>

        <!-- Live Article Header Glass Card -->
        <div class="relative rounded-3xl p-4 sm:p-6 md:p-10 mb-6 border border-white/60 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/80 backdrop-blur-xl shadow-xl overflow-hidden space-y-4">
            
            <!-- Category & Read Time Selectors -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-bold shrink-0">دسته‌بندی:</span>
                    <select id="select-category" class="text-xs font-bold rounded-xl border border-primary/30 bg-primary/10 text-primary dark:text-blue-300 dark:bg-blue-950/40 px-3 py-1.5 outline-none">
                        <option value="medical" <?= $init_category === 'medical' ? 'selected' : '' ?>>💉 پزشکی و سلامت</option>
                        <option value="pharmacy" <?= $init_category === 'pharmacy' ? 'selected' : '' ?>>💊 دارو و نسخه</option>
                        <option value="shop" <?= $init_category === 'shop' ? 'selected' : '' ?>>🐾 پت‌شاپ و تغذیه</option>
                        <option value="platform" <?= $init_category === 'platform' ? 'selected' : '' ?>>📖 راهنمای سامانه آسنا</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-200/60 dark:border-slate-700">
                        <span class="material-symbols-outlined text-primary text-base">schedule</span>
                        <span id="label-read-time"><?= htmlspecialchars($init_read_time) ?></span>
                        <input type="hidden" id="canvas-read-time" value="<?= htmlspecialchars($init_read_time) ?>">
                    </div>

                    <div class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 rounded-xl border border-slate-200/60 dark:border-slate-700">
                        <span id="counter-words" class="font-black text-primary">۰</span> کلمه
                    </div>
                </div>
            </div>

            <!-- Title (Editable) -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[11px] font-bold text-slate-400">عنوان اصلی مقاله (H1 سئو):</label>
                    <span id="title-char-count" class="text-[11px] text-slate-400">۰ کاراکتر</span>
                </div>
                <div id="canvas-title" contenteditable="true" class="text-xl sm:text-2xl md:text-4xl font-extrabold text-slate-900 dark:text-white leading-snug outline-none border-b-2 border-transparent focus:border-primary/40 pb-2 transition-all break-words"><?= !empty($init_title) ? htmlspecialchars($init_title) : 'عنوان جذاب و سئومحور مقاله خود را اینجا تایپ فرمایید...' ?></div>
            </div>

            <!-- URL Slug Preview (Responsive Stack) -->
            <div class="text-xs text-slate-500 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-200/60 dark:border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-primary">link</span>
                        پیوند یکتا (Slug سئو):
                    </span>
                    <span class="text-[10px] text-slate-400">تولید خودکار از عنوان</span>
                </div>
                <div class="flex items-center gap-1.5 bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <span class="text-slate-400 text-[11px] shrink-0 font-mono hidden md:inline">https://asena.company/.../?article=</span>
                    <span class="text-slate-400 text-[11px] shrink-0 font-mono md:hidden">slug:</span>
                    <input type="text" id="canvas-slug" value="<?= htmlspecialchars($init_slug) ?>" placeholder="عنوان-مقاله" class="bg-transparent outline-none text-primary dark:text-blue-400 font-bold w-full text-xs min-w-0 font-mono">
                </div>
            </div>

            <!-- Excerpt / Meta Description -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[11px] font-bold text-slate-400">خلاصه کوتاه مقاله (توضیحات متا در گوگل):</label>
                    <span id="desc-char-count" class="text-[11px] text-slate-400">۰ کاراکتر</span>
                </div>
                <div id="canvas-short-desc" contenteditable="true" class="text-xs sm:text-sm md:text-base text-slate-600 dark:text-slate-300 leading-relaxed outline-none border-b-2 border-transparent focus:border-primary/40 pb-2 transition-all break-words"><?= !empty($init_short_desc) ? htmlspecialchars($init_short_desc) : 'خلاصه مقاله جهت نمایش در کارت‌های پایگاه دانش و توضیحات گوگل...' ?></div>
            </div>

            <!-- Author Box -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-200/60 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary to-blue-600 flex items-center justify-center text-white shadow-md shrink-0">
                    <span class="material-symbols-outlined text-lg">verified_user</span>
                </div>
                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center gap-1.5">
                        <input type="text" id="canvas-author-name" value="<?= htmlspecialchars($init_author_name) ?>" class="text-xs sm:text-sm font-bold bg-transparent border-b border-dashed border-slate-300 dark:border-slate-700 outline-none w-full max-w-[180px] text-slate-900 dark:text-white focus:border-primary" placeholder="نام نویسنده">
                        <span class="material-symbols-outlined text-blue-500 text-sm shrink-0" title="تایید شده توسط آسنا">check_circle</span>
                    </div>
                    <input type="text" id="canvas-author-role" value="<?= htmlspecialchars($init_author_role) ?>" class="text-[11px] sm:text-xs bg-transparent border-b border-dashed border-slate-200 dark:border-slate-800 outline-none w-full max-w-[240px] text-slate-500 dark:text-slate-400 focus:border-primary" placeholder="سمت یا تخصص">
                </div>
            </div>
        </div>

        <!-- Sticky Floating WYSIWYG Formatting Toolbar -->
        <div class="sticky top-20 z-40 my-4 p-2.5 rounded-2xl border border-white/60 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-xl flex items-center gap-1.5 overflow-x-auto no-scrollbar text-slate-700 dark:text-slate-200">
            
            <!-- Font Size Selector -->
            <div class="shrink-0 flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-xl px-2.5 py-1 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-[17px] text-slate-500">format_size</span>
                <select id="fontSizeSelect" onchange="setFontSize(this.value); this.value='';" class="text-xs font-bold bg-transparent outline-none cursor-pointer text-slate-700 dark:text-slate-200">
                    <option value="">اندازه قلم</option>
                    <option value="13px">بسیار کوچک (13px)</option>
                    <option value="15px">کوچک (15px)</option>
                    <option value="16px">استاندارد (16px)</option>
                    <option value="18px">بزرگ (18px)</option>
                    <option value="22px">خیلی بزرگ (22px)</option>
                    <option value="26px">تیتر برجسته (26px)</option>
                    <option value="32px">عظیم (32px)</option>
                </select>
            </div>

            <!-- Font Color Picker -->
            <label class="shrink-0 toolbar-btn flex items-center gap-1 px-2.5 h-9 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800" title="تغییر رنگ قلم">
                <span class="material-symbols-outlined text-[19px] text-primary">format_color_text</span>
                <span class="text-xs font-bold hidden sm:inline">رنگ متن</span>
                <input type="color" onchange="formatDoc('foreColor', this.value)" value="#0f172a" class="w-5 h-5 rounded-md cursor-pointer border-0 p-0 bg-transparent shadow-sm">
            </label>

            <!-- Highlight Picker -->
            <label class="shrink-0 toolbar-btn flex items-center gap-1 px-2.5 h-9 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800" title="هایلایت و رنگ پس‌زمینه">
                <span class="material-symbols-outlined text-[19px] text-amber-500">format_ink_highlighter</span>
                <span class="text-xs font-bold hidden sm:inline">هایلایت</span>
                <input type="color" onchange="formatDoc('hiliteColor', this.value)" value="#fef08a" class="w-5 h-5 rounded-md cursor-pointer border-0 p-0 bg-transparent shadow-sm">
            </label>

            <div class="shrink-0 w-[1px] h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- Formatting Actions -->
            <button type="button" onclick="formatDoc('bold')" class="shrink-0 toolbar-btn w-9 h-9 rounded-xl flex items-center justify-center font-bold hover:bg-slate-100 dark:hover:bg-slate-800" title="بولد (ضخیم)">
                <span class="material-symbols-outlined text-[19px]">format_bold</span>
            </button>
            <button type="button" onclick="formatDoc('italic')" class="shrink-0 toolbar-btn w-9 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="ایتالیک (مایل)">
                <span class="material-symbols-outlined text-[19px]">format_italic</span>
            </button>
            <button type="button" onclick="formatDoc('underline')" class="shrink-0 toolbar-btn w-9 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="زیرخط (Underline)">
                <span class="material-symbols-outlined text-[19px]">format_underlined</span>
            </button>

            <div class="shrink-0 w-[1px] h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- Headings -->
            <button type="button" onclick="formatDoc('formatBlock', '<h2>')" class="shrink-0 toolbar-btn px-2.5 h-9 rounded-xl flex items-center justify-center font-black text-xs hover:bg-slate-100 dark:hover:bg-slate-800" title="تیتر اصلی (H2)">
                H2
            </button>
            <button type="button" onclick="formatDoc('formatBlock', '<h3>')" class="shrink-0 toolbar-btn px-2.5 h-9 rounded-xl flex items-center justify-center font-black text-xs hover:bg-slate-100 dark:hover:bg-slate-800" title="زیرتیتر (H3)">
                H3
            </button>

            <div class="shrink-0 w-[1px] h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- Alignment -->
            <button type="button" onclick="formatDoc('justifyRight')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="راست‌چین">
                <span class="material-symbols-outlined text-[18px]">format_align_right</span>
            </button>
            <button type="button" onclick="formatDoc('justifyCenter')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="وسط‌چین">
                <span class="material-symbols-outlined text-[18px]">format_align_center</span>
            </button>
            <button type="button" onclick="formatDoc('justifyLeft')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="چپ‌چین">
                <span class="material-symbols-outlined text-[18px]">format_align_left</span>
            </button>

            <div class="shrink-0 w-[1px] h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- Lists & Hyperlinks -->
            <button type="button" onclick="formatDoc('insertUnorderedList')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="لیست نشانه‌دار">
                <span class="material-symbols-outlined text-[19px]">format_list_bulleted</span>
            </button>
            <button type="button" onclick="formatDoc('insertOrderedList')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="لیست شماره‌دار">
                <span class="material-symbols-outlined text-[19px]">format_list_numbered</span>
            </button>
            <button type="button" onclick="insertLinkPrompt()" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800" title="افزودن پیوند / لینک">
                <span class="material-symbols-outlined text-[19px]">link</span>
            </button>
            <button type="button" onclick="formatDoc('removeFormat')" class="shrink-0 toolbar-btn w-8 h-9 rounded-xl flex items-center justify-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30" title="پاک‌کردن قالب‌بندی">
                <span class="material-symbols-outlined text-[18px]">format_clear</span>
            </button>
        </div>

        <!-- Live Content Editor Body (Glass Canvas) -->
        <div class="rounded-3xl p-4 sm:p-6 md:p-12 border border-white/60 dark:border-slate-800/80 bg-white/85 dark:bg-slate-900/85 backdrop-blur-xl shadow-lg leading-loose text-slate-800 dark:text-slate-200 min-h-[480px] break-words">
            <div id="canvas-content" contenteditable="true" class="prose prose-blue dark:prose-invert max-w-none outline-none break-words">
                <?= $init_content ?>
            </div>
        </div>

        <!-- Live SEO & Content Health Assistant Bar -->
        <div class="mt-6 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <h3 class="text-xs font-black text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-500 text-base">verified</span>
                    دستیار سلامت محتوا و سئو هوشمند (SEO Health Check)
                </h3>
                <span id="seo-score-badge" class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 self-start sm:self-auto">
                    امتیاز سئو: ۱۰۰٪
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div id="check-title" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>طول عنوان استاندارد است</span>
                </div>
                <div id="check-desc" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>توضیحات خلاصه تکمیل است</span>
                </div>
                <div id="check-headings" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>ساختار تیترهای H2 رعایت شده</span>
                </div>
                <div id="check-length" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>حجم متن برای گوگل و هوش مصنوعی عالی است</span>
                </div>
            </div>
        </div>

    </main>
</form>

<!-- Modal: Ready-Made Article Templates -->
<div id="templates-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 md:p-8 max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <h3 class="text-base md:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">auto_awesome</span>
                انتخاب قالب آماده نگارش مقاله (یک کلیک برای ایجاد)
            </h3>
            <button type="button" onclick="closeTemplatesModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <p class="text-xs text-slate-500">
            برای تسریع در نگارش، یکی از ساختارهای استاندارد زیر را انتخاب کنید تا استخوان‌بندی کامل مقاله در ادیتور لود شود:
        </p>

        <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
            <!-- Template 1 -->
            <div onclick="applyTemplate(1)" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-primary hover:bg-blue-50/50 dark:hover:bg-blue-950/20 cursor-pointer transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">medical_services</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">قالب راهنمای بیماری و مراقبت بالینی</h4>
                    <p class="text-xs text-slate-500 mt-0.5">شامل معرفی بیماری، علائم اولیه، کادر هشدار اورژانسی، اقدامات حمایتی و ۲ سوال متداول.</p>
                </div>
            </div>

            <!-- Template 2 -->
            <div onclick="applyTemplate(2)" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-primary hover:bg-blue-50/50 dark:hover:bg-blue-950/20 cursor-pointer transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-100 dark:bg-teal-900/40 text-teal-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">medication</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">قالب راهنمای دارویی و تفاوت با داروی انسانی</h4>
                    <p class="text-xs text-slate-500 mt-0.5">شامل تفاوت فرمولاسیون، خطرات مصرف خودسرانه، جدول دوز بر اساس وزن و توصیه داروساز.</p>
                </div>
            </div>

            <!-- Template 3 -->
            <div onclick="applyTemplate(3)" class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-primary hover:bg-blue-50/50 dark:hover:bg-blue-950/20 cursor-pointer transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">pets</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">قالب راهنمای تغذیه و مقایسه غذای خشک</h4>
                    <p class="text-xs text-slate-500 mt-0.5">شامل تفکیک نیاز بر اساس سن و نژاد، جدول مقایسه پروتئین، آلرژی‌ها و بنر سفارش آنلاین.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatDoc(cmd, value = null) {
    if (cmd === 'hiliteColor') {
        if (!document.execCommand('hiliteColor', false, value)) {
            document.execCommand('backColor', false, value);
        }
    } else {
        document.execCommand(cmd, false, value);
    }
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

function setFontSize(size) {
    if (!size) return;
    const sel = window.getSelection();
    if (!sel.rangeCount || sel.isCollapsed) {
        alert('لطفاً ابتدا متنی که می‌خواهید اندازه آن تغییر کند را انتخاب فرمایید.');
        return;
    }
    const range = sel.getRangeAt(0);
    const span = document.createElement('span');
    span.style.fontSize = size;
    span.appendChild(range.extractContents());
    range.insertNode(span);
    sel.removeAllRanges();
    const newRange = document.createRange();
    newRange.selectNodeContents(span);
    sel.addRange(newRange);
    document.getElementById('canvas-content').focus();
}

function insertLinkPrompt() {
    const url = prompt('آدرس اینترنتی پیوند (URL) را وارد نمایید:', 'https://');
    if (url && url !== 'https://') {
        formatDoc('createLink', url);
    }
}

// 1. Callout / Quote Box (Image 3)
function insertCalloutBox() {
    const title = prompt('عنوان کادر برجسته / نقل‌قول:', '۱. استامینوفن (تیلنول / پاراستامول)');
    if (!title) return;
    const desc = prompt('متن توضیحات کادر:', 'گربه‌ها فاقد آنزیم گلوکورونیل ترانسفراز هستند. مصرف حتی مقدار اندکی استامینوفن باعث تولید متابولیت بسیار سمی و تغییر هموگلوبین خون می‌شود...');
    
    const html = `
    <div class="my-5 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 select-all">
        <h4 class="font-bold text-teal-700 dark:text-teal-400 mb-1.5 text-base">${title}</h4>
        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">${desc || 'توضیحات تکمیلی...'}</p>
    </div><p><br></p>`;
    
    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 2. FAQ Accordion (Image 4)
function insertFaqAccordion() {
    const q = prompt('متن پرسش متداول:', 'اگر حیوانم درد دارد چه داروی مسکنی می‌توانم در خانه بدهم؟');
    if (!q) return;
    const a = prompt('پاسخ پزشک:', 'هیچ مسکن انسانی مجاز نیست. فقط مسکن‌های اختصاصی دامپزشکی با دستور پزشک معالج ایمن هستند.');

    const html = `
    <details class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 p-4 my-3 transition-all">
        <summary class="font-bold text-sm text-slate-900 dark:text-white cursor-pointer list-none flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">help_outline</span>
                ${q}
            </span>
            <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
        </summary>
        <p class="mt-3 text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed pt-2.5 border-t border-slate-200/50 dark:border-slate-800">
            ${a || 'پاسخ سوال...'}
        </p>
    </details><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 3. Red Warning Emergency Box
function insertWarningBox() {
    const title = prompt('عنوان هشدار اورژانسی:', '⚠️ هشدار فوری و اورژانسی:');
    if (!title) return;
    const desc = prompt('متن هشدار:', 'در صورت مشاهده بی‌حالی شدید، استفراغ مداوم یا تنگی نفس، از هرگونه خوددرمانی خودداری کرده و فوراً به نزدیک‌ترین بیمارستان دامپزشکی مراجعه فرمایید.');

    const html = `
    <div class="my-5 p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 select-all">
        <div class="flex items-center gap-2 font-black text-rose-700 dark:text-rose-400 mb-1.5 text-sm md:text-base">
            <span class="material-symbols-outlined text-rose-600">emergency</span>
            <span>${title}</span>
        </div>
        <p class="text-xs md:text-sm text-rose-900/80 dark:text-rose-200 leading-relaxed">${desc || 'متن هشدار...'}</p>
    </div><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 4. Doctor Pro Tip Box
function insertDoctorTipBox() {
    const title = prompt('عنوان توصیه پزشک:', '💡 توصیه طلایی متخصص دامپزشکی آسنا:');
    if (!title) return;
    const desc = prompt('متن توصیه:', 'همیشه داروها را همراه با غذا یا طبق دستور دقیق نسخه بدهید تا از آسیب‌های گوارشی پیشگیری شود.');

    const html = `
    <div class="my-5 p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/60 select-all">
        <div class="flex items-center gap-2 font-black text-emerald-700 dark:text-emerald-400 mb-1.5 text-sm md:text-base">
            <span class="material-symbols-outlined text-emerald-600">lightbulb</span>
            <span>${title}</span>
        </div>
        <p class="text-xs md:text-sm text-emerald-900/80 dark:text-emerald-200 leading-relaxed">${desc || 'متن توصیه...'}</p>
    </div><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 5. Clinical Table
function insertClinicalTable() {
    const html = `
    <div class="overflow-x-auto my-6">
        <table class="w-full text-xs md:text-sm">
            <thead>
                <tr class="bg-slate-100 dark:bg-slate-800">
                    <th class="p-3 font-bold border border-slate-200 dark:border-slate-700">دوره / سن پت</th>
                    <th class="p-3 font-bold border border-slate-200 dark:border-slate-700">نوع اقدام / واکسن</th>
                    <th class="p-3 font-bold border border-slate-200 dark:border-slate-700">توضیحات و مراقبت</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">۶ الی ۸ هفتگی</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700 font-bold text-primary">نوبت اول چندگانه (DHPPi / RCP)</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">شروع ایمنی‌زایی پایه تولگی</td>
                </tr>
                <tr>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">۱۰ الی ۱۲ هفتگی</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700 font-bold text-primary">نوبت دوم یادآور + ضدانگل</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">تقویت آنتی‌بادی‌های خونی</td>
                </tr>
                <tr>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">۱۴ الی ۱۶ هفتگی</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700 font-bold text-rose-600">واکسن هاری (Rabies)</td>
                    <td class="p-3 border border-slate-200 dark:border-slate-700">الزامی قانونی و صدور شناسنامه بهداشتی</td>
                </tr>
            </tbody>
        </table>
    </div><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 6. Clinic Appointment Banner
function insertAppointmentBanner() {
    const html = `
    <div class="my-6 p-6 rounded-3xl bg-gradient-to-r from-blue-900 to-[#002d72] text-white flex flex-col md:flex-row items-center justify-between gap-4 shadow-lg select-all">
        <div>
            <h4 class="font-extrabold text-base md:text-lg mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-400">stethoscope</span>
                نیاز به مشاوره آنلاین یا معاینه حضوری پت دارید؟
            </h4>
            <p class="text-xs text-blue-200">دامپزشکان متخصص آسنا هم‌اکنون آماده ارائه خدمات درمانی به پت دلبند شما هستند.</p>
        </div>
        <a href="clinic_appointment.php" target="_blank" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md shrink-0">
            🐾 رزرو آنلاین نوبت ویزیت
        </a>
    </div><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// 7. Image Prompt
function insertImagePrompt() {
    const url = prompt('آدرس اینترنتی تصویر را وارد فرمایید:', 'assets/images/sample.png');
    if (!url) return;
    const caption = prompt('زیرنویس و کپشن عکس:', 'تصویر ۱: راهنمای معاینه بالینی');

    const html = `
    <figure class="my-6 text-center select-all">
        <img src="${url}" alt="${caption}" class="rounded-2xl max-w-full mx-auto shadow-md border border-slate-200 dark:border-slate-800" />
        <figcaption class="mt-2 text-xs text-slate-500 font-medium">${caption}</figcaption>
    </figure><p><br></p>`;

    document.execCommand('insertHTML', false, html);
    document.getElementById('canvas-content').focus();
    updateLiveMetrics();
}

// Templates Modal Handlers
function openTemplatesModal() {
    const modal = document.getElementById('templates-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeTemplatesModal() {
    const modal = document.getElementById('templates-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function applyTemplate(type) {
    if (!confirm('آیا مایلید محتوای این قالب در ادیتور جایگزین شود؟')) return;

    const titleEl = document.getElementById('canvas-title');
    const descEl = document.getElementById('canvas-short-desc');
    const contentEl = document.getElementById('canvas-content');
    const catEl = document.getElementById('select-category');

    if (type === 1) { // Disease & Clinical Care
        titleEl.innerText = 'راهنمای کامل علائم، پیشگیری و درمان عفونت‌های تنفسی در سگ و گربه';
        descEl.innerText = 'شناخت علائم بالینی سرفه، عطسه و تب در حیوانات خانگی و زمان طلایی مراجعه به کلینیک دامپزشکی آسنا.';
        catEl.value = 'medical';
        contentEl.innerHTML = `
        <h2>مقدمه و اهمیت تشخیص زودهنگام</h2>
        <p>عفونت‌های تنفسی از شایع‌ترین علل مراجعه به درمانگاه‌های دامپزشکی هستند. سیستم ایمنی پت‌ها در مواجهه با تغییرات آب‌وهوا یا تراکم محیطی آسیب‌پذیر می‌شود.</p>

        <div class="my-5 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
            <h4 class="font-bold text-teal-700 dark:text-teal-400 mb-1.5 text-base">علائم کلیدی که باید زیر نظر بگیرید:</h4>
            <p class="text-sm text-slate-600 dark:text-slate-300">ترشحات چرکی بینی، خس‌خس سینه، کم‌اشتهایی بیش از ۲۴ ساعت و کاهش سطح هوشیاری.</p>
        </div>

        <h2>اقدامات اورژانسی در منزل</h2>
        <p>محیط را مرطوب نگه دارید (استفاده از بخور سرد) و از مصرف هرگونه داروی انسانی جداً پرهیز نمایید.</p>

        <div class="my-5 p-5 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60">
            <div class="flex items-center gap-2 font-black text-rose-700 dark:text-rose-400 mb-1.5">
                <span class="material-symbols-outlined text-rose-600">emergency</span>
                <span>هشدار مسمومیت دارویی:</span>
            </div>
            <p class="text-xs md:text-sm text-rose-900/80 leading-relaxed">هرگز به پت مبتلا به سرماخوردگی استامینوفن، ادویل یا آسپرین ندهید.</p>
        </div>

        <h2>پرسش‌های متداول سرپرستان پت</h2>
        <details class="group rounded-2xl border border-slate-200 bg-slate-50 p-4 my-3">
            <summary class="font-bold text-sm cursor-pointer list-none flex justify-between">
                <span>آیا سرماخوردگی گربه به انسان سرایت می‌کند؟</span>
                <span class="material-symbols-outlined">expand_more</span>
            </summary>
            <p class="mt-3 text-xs text-slate-600 border-t pt-2">خیر، اکثر ویروس‌های تنفسی دامی اختصاصی گونه هستند و قابلیت انتقال به انسان را ندارند.</p>
        </details>`;
    } else if (type === 2) { // Medication Guide
        titleEl.innerText = 'راهنمای جامع مصرف آنتی‌بیوتیک‌ها در دامپزشکی و خطرات دوز نامناسب';
        descEl.innerText = 'چرا نباید دوره آنتی‌بیوتیک پت را نصفه رها کرد؟ بررسی خطرات مقاومت دارویی و عوارض گوارشی.';
        catEl.value = 'pharmacy';
        contentEl.innerHTML = `
        <h2>اهمیت تکمیل دوره درمان دارویی</h2>
        <p>قطع زودهنگام دارو به محض بهبود ظاهری حیوان، شایع‌ترین دلیل بازگشت شدیدتر عفونت و ایجاد باکتری‌های مقاوم است.</p>

        <div class="my-5 p-5 rounded-2xl bg-emerald-50 border border-emerald-200">
            <h4 class="font-bold text-emerald-800 mb-1">💡 نکته طلایی داروخانه آسنا:</h4>
            <p class="text-xs text-emerald-900">مصرف پروبیوتیک‌های اختصاصی پت با فاصله ۲ ساعت از آنتی‌بیوتیک، فلور مفید روده را حفظ می‌کند.</p>
        </div>`;
    } else if (type === 3) { // Nutrition & Food
        titleEl.innerText = 'راهنمای جامع انتخاب بهترین غذای خشک سگ و گربه بر اساس سن و نژاد';
        descEl.innerText = 'بررسی نسبت پروتئین، چربی و کربوهیدرات در تغذیه پت‌ها و تفاوت نیازهای توله با سگ بالغ.';
        catEl.value = 'shop';
        contentEl.innerHTML = `
        <h2>معیارهای طلایی سنجش کیفیت غذای خشک</h2>
        <p>منبع اولیه پروتئین همیشه باید گوشت مشخص (مرغ، بره، سالمون) باشد و نه ضایعات یا فرآورده‌های فرعی مبهم.</p>
        <div class="overflow-x-auto my-4">
            <table class="w-full text-xs">
                <thead><tr class="bg-slate-100"><th>مرحله سنی</th><th>حداقل پروتئین</th><th>نیاز کلیدی</th></tr></thead>
                <tbody>
                    <tr><td>پاپی و کیتن (رشد)</td><td>۳۰٪ الی ۳۶٪</td><td>DHA و کلسیم بالا برای مفاصل</td></tr>
                    <tr><td>حیوان بالغ (Adult)</td><td>۲۴٪ الی ۲۸٪</td><td>کنترل وزن و سلامت پوست</td></tr>
                    <tr><td>مسن (Senior)</td><td>۲۰٪ الی ۲۴٪</td><td>گلوکوزامین و فیبر بالا</td></tr>
                </tbody>
            </table>
        </div>`;
    }

    closeTemplatesModal();
    updateLiveMetrics();
}

// Preview Mode Switcher (Desktop vs Mobile Frame)
function setPreviewMode(mode) {
    const container = document.getElementById('editor-main-container');
    const btnDesktop = document.getElementById('btn-desktop-view');
    const btnMobile = document.getElementById('btn-mobile-view');

    if (mode === 'mobile') {
        container.classList.add('mobile-preview-frame');
        btnMobile.classList.add('bg-white', 'dark:bg-slate-700', 'text-slate-800', 'dark:text-white', 'shadow-xs');
        btnMobile.classList.remove('text-slate-500');
        btnDesktop.classList.remove('bg-white', 'dark:bg-slate-700', 'text-slate-800', 'dark:text-white', 'shadow-xs');
        btnDesktop.classList.add('text-slate-500');
    } else {
        container.classList.remove('mobile-preview-frame');
        btnDesktop.classList.add('bg-white', 'dark:bg-slate-700', 'text-slate-800', 'dark:text-white', 'shadow-xs');
        btnDesktop.classList.remove('text-slate-500');
        btnMobile.classList.remove('bg-white', 'dark:bg-slate-700', 'text-slate-800', 'dark:text-white', 'shadow-xs');
        btnMobile.classList.add('text-slate-500');
    }
}

// Live Metrics & SEO Checker
function updateLiveMetrics() {
    const titleEl = document.getElementById('canvas-title');
    const shortDescEl = document.getElementById('canvas-short-desc');
    const contentEl = document.getElementById('canvas-content');
    const slugInput = document.getElementById('canvas-slug');

    const titleText = titleEl ? titleEl.innerText.trim() : '';
    const descText = shortDescEl ? shortDescEl.innerText.trim() : '';
    const contentText = contentEl ? contentEl.innerText.trim() : '';

    // Character Counts
    document.getElementById('title-char-count').innerText = `${titleText.length} کاراکتر`;
    document.getElementById('desc-char-count').innerText = `${descText.length} کاراکتر`;

    // Word Count & Read Time
    const words = contentText ? contentText.split(/\s+/).filter(w => w.length > 0).length : 0;
    document.getElementById('counter-words').innerText = words;

    const readMinutes = Math.max(1, Math.round(words / 180));
    const readTimeStr = `${readMinutes} دقیقه مطالعه`;
    document.getElementById('label-read-time').innerText = readTimeStr;
    document.getElementById('canvas-read-time').value = readTimeStr;

    // Auto-generate slug if empty
    if (slugInput && (!slugInput.value || slugInput.value.startsWith('post-'))) {
        const autoSlug = titleText.replace(/[^\p{L}\p{N}\-]+/gu, '-').replace(/^-+|-+$/g, '');
        if (autoSlug) slugInput.value = autoSlug;
    }

    // SEO Checks
    const checkTitle = titleText.length >= 25 && titleText.length <= 80;
    const checkDesc = descText.length >= 40 && descText.length <= 180;
    const checkHeadings = contentEl ? contentEl.querySelectorAll('h2, h3').length >= 1 : false;
    const checkLength = words >= 150;

    toggleCheckStatus('check-title', checkTitle, 'طول عنوان استاندارد است', 'طول عنوان کوتاه است (حداقل ۲۵ کاراکتر)');
    toggleCheckStatus('check-desc', checkDesc, 'توضیحات خلاصه تکمیل است', 'خلاصه کوتاه است (حداقل ۴۰ کاراکتر)');
    toggleCheckStatus('check-headings', checkHeadings, 'ساختار تیترهای H2 رعایت شده', 'حداقل یک تیتر H2 به متن بیفزایید');
    toggleCheckStatus('check-length', checkLength, 'حجم متن برای سئو مناسب است', 'مقاله کوتاه است (حداقل ۱۵۰ کلمه)');

    const score = [checkTitle, checkDesc, checkHeadings, checkLength].filter(Boolean).length * 25;
    const badge = document.getElementById('seo-score-badge');
    badge.innerText = `امتیاز سئو: ${score}٪`;
    badge.className = score >= 75 
        ? 'px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'
        : 'px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300';
}

function toggleCheckStatus(id, passed, passText, failText) {
    const el = document.getElementById(id);
    if (!el) return;
    if (passed) {
        el.className = 'flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400';
        el.innerHTML = `<span class="material-symbols-outlined text-sm">check_circle</span><span>${passText}</span>`;
    } else {
        el.className = 'flex items-center gap-1.5 text-amber-600 dark:text-amber-400';
        el.innerHTML = `<span class="material-symbols-outlined text-sm">error</span><span>${failText}</span>`;
    }
}

// Prepare before submit
function prepareAndSubmit() {
    const titleEl = document.getElementById('canvas-title');
    const slugEl = document.getElementById('canvas-slug');
    const shortDescEl = document.getElementById('canvas-short-desc');
    const contentEl = document.getElementById('canvas-content');
    const catEl = document.getElementById('select-category');
    const statusEl = document.getElementById('select-status');
    const readTimeEl = document.getElementById('canvas-read-time');
    const authorNameEl = document.getElementById('canvas-author-name');
    const authorRoleEl = document.getElementById('canvas-author-role');

    document.getElementById('input-title').value = titleEl.innerText.trim();
    document.getElementById('input-slug').value = slugEl.value.trim();
    document.getElementById('input-short-desc').value = shortDescEl.innerText.trim();
    document.getElementById('input-content').value = contentEl.innerHTML;
    document.getElementById('input-category').value = catEl.value;
    document.getElementById('input-status').value = statusEl.value;
    document.getElementById('input-read-time').value = readTimeEl.value.trim();
    document.getElementById('input-author-name').value = authorNameEl.value.trim();
    document.getElementById('input-author-role').value = authorRoleEl.value.trim();
}

// Attach Live Listeners
document.addEventListener('DOMContentLoaded', () => {
    const titleEl = document.getElementById('canvas-title');
    const descEl = document.getElementById('canvas-short-desc');
    const contentEl = document.getElementById('canvas-content');

    if (titleEl) titleEl.addEventListener('input', updateLiveMetrics);
    if (descEl) descEl.addEventListener('input', updateLiveMetrics);
    if (contentEl) contentEl.addEventListener('input', updateLiveMetrics);

    updateLiveMetrics();
});
</script>

</body>
</html>
