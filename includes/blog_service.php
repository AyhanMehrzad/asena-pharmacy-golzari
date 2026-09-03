<?php
/**
 * ASENA Blog Service — Database and Management Layer for Visual Blog Posts
 */

function ensure_blog_table($pdo) {
    static $checked = false;
    if ($checked) return;
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `blog_posts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `slug` varchar(255) NOT NULL,
            `title` varchar(500) NOT NULL,
            `short_desc` text DEFAULT NULL,
            `category` varchar(50) NOT NULL DEFAULT 'medical',
            `category_name` varchar(100) NOT NULL DEFAULT 'پزشکی و سلامت',
            `content` longtext NOT NULL,
            `faqs_json` longtext DEFAULT NULL,
            `author_name` varchar(255) NOT NULL DEFAULT 'آسنا',
            `author_role` varchar(255) DEFAULT 'تیم تخصصی آسنا',
            `icon` varchar(50) DEFAULT 'article',
            `accent_color` varchar(100) DEFAULT 'from-blue-600 to-indigo-700',
            `read_time` varchar(50) DEFAULT '۵ دقیقه مطالعه',
            `created_by_user_id` int(11) DEFAULT NULL,
            `status` enum('published','draft') DEFAULT 'published',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_slug` (`slug`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);
        $checked = true;
    } catch (Exception $e) {
        // Log silently if table already exists or permission issues
        error_log("Blog table ensure error: " . $e->getMessage());
    }
}

function get_all_db_blogs($pdo, $status = null) {
    ensure_blog_table($pdo);
    try {
        if ($status) {
            $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status = ? ORDER BY id DESC");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY id DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function get_db_blog_by_slug($pdo, $slug) {
    ensure_blog_table($pdo);
    try {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function get_db_blog_by_id($pdo, $id) {
    ensure_blog_table($pdo);
    try {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function save_db_blog($pdo, $data, $user_id = null) {
    ensure_blog_table($pdo);
    $id = isset($data['id']) && (int)$data['id'] > 0 ? (int)$data['id'] : 0;
    
    // Slugify title if slug empty
    $slug = !empty($data['slug']) ? trim($data['slug']) : '';
    if (empty($slug)) {
        $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '-', trim($data['title']));
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'post-' . time();
        }
    }
    // Prevent slug collisions on new posts
    if (!$id) {
        $check = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }
    }

    $title = trim($data['title'] ?? 'بدون عنوان');
    $short_desc = trim($data['short_desc'] ?? '');
    $category = $data['category'] ?? 'medical';
    
    $category_names = [
        'medical' => 'پزشکی و سلامت',
        'pharmacy' => 'دارو و نسخه',
        'shop' => 'پت‌شاپ و تغذیه',
        'platform' => 'راهنمای سامانه آسنا'
    ];
    $category_name = $category_names[$category] ?? 'عمومی';

    $content = $data['content'] ?? '';
    $faqs_json = !empty($data['faqs']) ? (is_array($data['faqs']) ? json_encode($data['faqs'], JSON_UNESCAPED_UNICODE) : $data['faqs']) : '[]';
    $author_name = !empty($data['author_name']) ? trim($data['author_name']) : 'آسنا';
    $author_role = !empty($data['author_role']) ? trim($data['author_role']) : 'تیم تخصصی و تحریریه آسنا';
    $icon = !empty($data['icon']) ? $data['icon'] : 'article';
    $accent_color = !empty($data['accent_color']) ? $data['accent_color'] : 'from-blue-600 to-indigo-700';
    $read_time = !empty($data['read_time']) ? $data['read_time'] : '۵ دقیقه مطالعه';
    $status = in_array($data['status'] ?? '', ['published', 'draft']) ? $data['status'] : 'published';

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE blog_posts SET 
            slug = ?, title = ?, short_desc = ?, category = ?, category_name = ?,
            content = ?, faqs_json = ?, author_name = ?, author_role = ?,
            icon = ?, accent_color = ?, read_time = ?, status = ?
            WHERE id = ?");
        $stmt->execute([
            $slug, $title, $short_desc, $category, $category_name,
            $content, $faqs_json, $author_name, $author_role,
            $icon, $accent_color, $read_time, $status, $id
        ]);
        return $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO blog_posts 
            (slug, title, short_desc, category, category_name, content, faqs_json, author_name, author_role, icon, accent_color, read_time, created_by_user_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $slug, $title, $short_desc, $category, $category_name,
            $content, $faqs_json, $author_name, $author_role,
            $icon, $accent_color, $read_time, $user_id, $status
        ]);
        return (int)$pdo->lastInsertId();
    }
}

function delete_db_blog($pdo, $id) {
    ensure_blog_table($pdo);
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    return $stmt->execute([(int)$id]);
}
