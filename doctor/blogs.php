<?php
/**
 * ASENA Doctor Panel — Blog & Clinical Health Articles Management
 */

require_once 'includes/doctor_header.php';
require_once '../includes/blog_service.php';

// Fetch all database blog posts
$db_posts = get_all_db_blogs($pdo);

// Clinical and medical articles catalog
$clinical_catalog = [
    [
        'id' => 0,
        'slug' => 'vaccination-schedule-dogs-cats',
        'title' => 'جدول کامل واکسیناسیون سگ و گربه در ایران + سنین تزریق و مراقبت‌ها',
        'category_name' => 'پزشکی و سلامت',
        'author_name' => 'آسنا',
        'status' => 'published',
        'created_at' => '۲۰۲۶-۰۹-۰۱',
        'is_builtin' => true
    ],
    [
        'id' => 0,
        'slug' => 'pet-poisoning-emergency-guide',
        'title' => 'علائم مسمومیت در حیوانات خانگی و اقدامات اورژانسی حیاتی دامپزشکی',
        'category_name' => 'پزشکی و سلامت',
        'author_name' => 'آسنا',
        'status' => 'published',
        'created_at' => '۲۰۲۶-۰۹-۰۱',
        'is_builtin' => true
    ],
    [
        'id' => 0,
        'slug' => 'human-vs-veterinary-medications',
        'title' => 'تفاوت داروهای دامپزشکی با انسانی و خطرات مرگبار مصرف خودسرانه',
        'category_name' => 'دارو و نسخه',
        'author_name' => 'آسنا',
        'status' => 'published',
        'created_at' => '۲۰۲۶-۰۹-۰۱',
        'is_builtin' => true
    ],
    [
        'id' => 0,
        'slug' => 'best-dry-food-selection-guide',
        'title' => 'راهنمای انتخاب بهترین غذای خشک بر اساس نژاد و سن سگ و گربه',
        'category_name' => 'پت‌شاپ و تغذیه',
        'author_name' => 'آسنا',
        'status' => 'published',
        'created_at' => '۲۰۲۶-۰۹-۰۱',
        'is_builtin' => true
    ]
];

$all_posts = array_merge($db_posts, $clinical_catalog);
?>

<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    
    <!-- Top Action Banner -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary text-3xl">edit_note</span>
                نگارش و مدیریت مقالات تخصصی پزشکان
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                پزشکان گرامی می‌توانند مقالات آموزشی، توصیه‌های درمانی و راهنماهای سلامت پت را مستقیماً در پایگاه دانش آسنا منتشر نمایند.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="../knowledge_base.php" target="_blank" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-50 transition-all flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>مشاهده وبلاگ</span>
            </a>
            <a href="../blog_editor.php" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs md:text-sm shadow-md transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                <span>+ نگارش مقاله جدید</span>
            </a>
        </div>
    </div>

    <!-- Table of Clinical Articles -->
    <div class="rounded-3xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <h2 class="font-extrabold text-sm md:text-base text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">clinical_notes</span>
                مقالات بالینی و آموزشی منتشر شده
            </h2>
            <span class="text-xs text-slate-500"><?= count($all_posts) ?> مقاله فعال</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 text-xs font-bold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-4">عنوان مقاله</th>
                        <th class="p-4">دسته‌بندی</th>
                        <th class="p-4">نویسنده</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4">تاریخ</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-xs md:text-sm">
                    <?php foreach ($all_posts as $p): ?>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/20 transition-colors">
                        <td class="p-4 font-bold text-slate-900 dark:text-white max-w-md">
                            <div class="line-clamp-1"><?= htmlspecialchars($p['title']) ?></div>
                            <div class="text-[11px] text-slate-400 font-normal mt-0.5"><?= htmlspecialchars($p['slug']) ?></div>
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                <?= htmlspecialchars($p['category_name'] ?? 'پزشکی و سلامت') ?>
                            </span>
                        </td>
                        <td class="p-4 font-bold text-slate-700 dark:text-slate-300">
                            <?= htmlspecialchars($p['author_name'] ?? 'آسنا') ?>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                منتشر شده
                            </span>
                        </td>
                        <td class="p-4 text-slate-500 dark:text-slate-400 text-xs">
                            <?= substr($p['created_at'] ?? '۲۰۲۶-۰۹-۰۱', 0, 10) ?>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="../knowledge_base.php?article=<?= urlencode($p['slug']) ?>" target="_blank" class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="مشاهده در سایت">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>

                                <?php if (!empty($p['id'])): ?>
                                <a href="../blog_editor.php?id=<?= $p['id'] ?>" class="p-2 rounded-xl text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors" title="ویرایش در ویرایشگر بصری">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <?php else: ?>
                                <span class="text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-700/50 px-2 py-1 rounded-md">سیستمی</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/doctor_footer.php'; ?>
