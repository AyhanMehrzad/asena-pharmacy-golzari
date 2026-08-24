<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Add columns to products table if they do not exist
    $columns_to_add = [
        'target_animal' => "VARCHAR(50) DEFAULT 'all'",
        'pharmacy_tag' => "VARCHAR(100) DEFAULT NULL",
        'is_autoship' => "TINYINT(1) NOT NULL DEFAULT 0",
        'autoship_discount' => "INT(11) DEFAULT 10",
        'rating_cache' => "DECIMAL(2,1) DEFAULT 4.5",
        'review_count_cache' => "INT(11) DEFAULT 0"
    ];

    foreach ($columns_to_add as $col => $definition) {
        $check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'products' 
              AND COLUMN_NAME = ?
        ");
        $check->execute([$col]);
        if ($check->fetchColumn() == 0) {
            $pdo->exec("ALTER TABLE `products` ADD `$col` $definition");
            echo "Added column `$col` to `products` table.\n";
        }
    }

    // 2. Update existing products with appropriate animal & tag & autoship data
    $pdo->exec("UPDATE `products` SET `target_animal` = 'dog', `is_autoship` = 1, `autoship_discount` = 10, `rating_cache` = 4.8, `review_count_cache` = 14 WHERE `category` LIKE '%سگ%' AND `category` NOT LIKE '%دارو%'");
    $pdo->exec("UPDATE `products` SET `target_animal` = 'cat', `is_autoship` = 1, `autoship_discount` = 10, `rating_cache` = 4.9, `review_count_cache` = 22 WHERE `category` LIKE '%گربه%' AND `category` NOT LIKE '%دارو%'");
    $pdo->exec("UPDATE `products` SET `pharmacy_tag` = 'therapy', `is_autoship` = 1, `autoship_discount` = 15 WHERE `category` LIKE '%مکمل%'");

    // 3. Seed Pharmacy Products for all Animal Types (Horse, Cow, Chick, Dog, Cat)
    $pharmacy_products = [
        // HORSE (اسب)
        [
            'name' => 'خمیر ضد انگل آیورمکتین مخصوص اسب اکولان',
            'category' => 'داروخانه تخصصی',
            'price' => 850000,
            'discount_price' => 740000,
            'image_url' => 'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=600&auto=format&fit=crop&q=80',
            'description' => 'ژل خوراکی ضد انگل و کرم‌کش قوی برای کنترل انواع انگل‌های داخلی و روده‌ای اسب‌ها با اثرگذاری طولانی‌مدت.',
            'brand' => 'اکولان',
            'stock' => 15,
            'target_animal' => 'horse',
            'pharmacy_tag' => 'dewormer',
            'is_autoship' => 1,
            'autoship_discount' => 12,
            'rating_cache' => 4.9,
            'review_count_cache' => 18
        ],
        [
            'name' => 'روغن و مرهم تقویتی سم اسب مدل Hoof Care Pro',
            'category' => 'داروخانه تخصصی',
            'price' => 620000,
            'discount_price' => 540000,
            'image_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
            'description' => 'فرمولاسیون ویژه حاوی تار طبیعی و بیوتین جهت تقویت بافت شاخی سم اسب و جلوگیری از ترک خوردگی و خشکی.',
            'brand' => 'کاوامیرا',
            'stock' => 20,
            'target_animal' => 'horse',
            'pharmacy_tag' => 'hoof_care',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.7,
            'review_count_cache' => 9
        ],
        [
            'name' => 'محلول مسکن و ضدالتهاب اسب فینیل بوتازون خوراکی',
            'category' => 'داروخانه تخصصی',
            'price' => 980000,
            'discount_price' => 890000,
            'image_url' => 'https://images.unsplash.com/photo-1598974357801-cbca100e6571?w=600&auto=format&fit=crop&q=80',
            'description' => 'داروی ضد درد و تسکین التهابات تاندونی و مفاصل اسب‌های کورس و پرش، موثر در بهبودی سریع صدمات عضلانی.',
            'brand' => 'وت‌فارما',
            'stock' => 12,
            'target_animal' => 'horse',
            'pharmacy_tag' => 'pain_management',
            'is_autoship' => 0,
            'autoship_discount' => 5,
            'rating_cache' => 5.0,
            'review_count_cache' => 24
        ],
        [
            'name' => 'پودر مکمل الکترولیت و ویتامین E اسب اکواین',
            'category' => 'داروخانه تخصصی',
            'price' => 1250000,
            'discount_price' => 1100000,
            'image_url' => 'https://images.unsplash.com/photo-1566251037378-5e04e3bec343?w=600&auto=format&fit=crop&q=80',
            'description' => 'مکمل تامین املاح ضروری و ویتامین‌های آنتی‌اکسیدان پس از تمرینات سنگین، جلوگیری از دهیدراتاسیون و گرفتگی عضلات.',
            'brand' => 'نوترینت پرو',
            'stock' => 25,
            'target_animal' => 'horse',
            'pharmacy_tag' => 'vitamins',
            'is_autoship' => 1,
            'autoship_discount' => 15,
            'rating_cache' => 4.8,
            'review_count_cache' => 16
        ],

        // COW / LIVESTOCK (گاو و دام)
        [
            'name' => 'پماد پستانی ضد ورم پستان حاد و تحت حاد گاو شیری',
            'category' => 'داروخانه تخصصی',
            'price' => 450000,
            'discount_price' => 390000,
            'image_url' => 'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=600&auto=format&fit=crop&q=80',
            'description' => 'سوسپانسیون آنتی بیوتیکی فوق العاده قوی جهت درمان و کنترل ورم پستان با دوره پرهیز کوتاه مدت.',
            'brand' => 'وت‌مکس',
            'stock' => 30,
            'target_animal' => 'cow',
            'pharmacy_tag' => 'inflammation',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.9,
            'review_count_cache' => 31
        ],
        [
            'name' => 'اسپری اکسید روی و تار ضد گندیدگی سم دام (Foot Rot)',
            'category' => 'داروخانه تخصصی',
            'price' => 320000,
            'discount_price' => 280000,
            'image_url' => 'https://images.unsplash.com/photo-1546445317-29f4545e9d53?w=600&auto=format&fit=crop&q=80',
            'description' => 'اسپری درمانی و ضدعفونی کننده لایه‌های شاخی سم گاو و گوسفند جهت پیشگیری از لنگش و عفونت سم.',
            'brand' => 'کاوامیرا',
            'stock' => 40,
            'target_animal' => 'cow',
            'pharmacy_tag' => 'hoof_care',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.6,
            'review_count_cache' => 14
        ],
        [
            'name' => 'بلوس آهسته‌رهش کلسیم و ویتامین D3 گاو تازه زا',
            'category' => 'داروخانه تخصصی',
            'price' => 780000,
            'discount_price' => 690000,
            'image_url' => 'https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?w=600&auto=format&fit=crop&q=80',
            'description' => 'پیشگیری قطعی از تب شیر (Hypocalcemia) و فلجی زایمان با فراهمی زیستی بالا در شکمبه دام سنگین.',
            'brand' => 'فارماپرو',
            'stock' => 20,
            'target_animal' => 'cow',
            'pharmacy_tag' => 'vitamins',
            'is_autoship' => 1,
            'autoship_discount' => 15,
            'rating_cache' => 5.0,
            'review_count_cache' => 27
        ],
        [
            'name' => 'واکسن کشته دامی آنتروتوکسمی و شاربن علامتی',
            'category' => 'داروخانه تخصصی',
            'price' => 550000,
            'discount_price' => NULL,
            'image_url' => 'https://images.unsplash.com/photo-1588693951525-6b7a5ee2e3d3?w=600&auto=format&fit=crop&q=80',
            'description' => 'ایمن‌سازی فعال گله در برابر پرخوری و کلستریدیوزهای شایع با بالاترین تیتر آنتی‌بادی ایمنی‌بخش.',
            'brand' => 'رازی وت',
            'stock' => 50,
            'target_animal' => 'cow',
            'pharmacy_tag' => 'vaccines',
            'is_autoship' => 0,
            'autoship_discount' => 0,
            'rating_cache' => 4.8,
            'review_count_cache' => 19
        ],

        // CHICK / POULTRY (جوجه و طیور)
        [
            'name' => 'محلول خوراکی مولتی ویتامین + اسیدهای آمینه پرورشی طیور',
            'category' => 'داروخانه تخصصی',
            'price' => 290000,
            'discount_price' => 245000,
            'image_url' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&auto=format&fit=crop&q=80',
            'description' => 'تقویت ضریب تبدیل غذایی، بهبود رشد جوجه یک‌روزه و ارتقای مقاومت سیستم ایمنی در شرایط استرس گرمایی.',
            'brand' => 'اویسان',
            'stock' => 60,
            'target_animal' => 'chick',
            'pharmacy_tag' => 'vitamins',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.9,
            'review_count_cache' => 42
        ],
        [
            'name' => 'پودر محلول در آب ضد کوکسیدیوز و عفونت‌های گوارشی جوجه',
            'category' => 'داروخانه تخصصی',
            'price' => 380000,
            'discount_price' => 330000,
            'image_url' => 'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=600&auto=format&fit=crop&q=80',
            'description' => 'داروی درمانی و کنترل‌کننده کوکسیدیوز روده‌ای و اسهال‌های خونی در مزارع پرورش جوجه و نیمچه گوشتی.',
            'brand' => 'کمی فارما',
            'stock' => 35,
            'target_animal' => 'chick',
            'pharmacy_tag' => 'drugs',
            'is_autoship' => 1,
            'autoship_discount' => 12,
            'rating_cache' => 4.7,
            'review_count_cache' => 15
        ],
        [
            'name' => 'واکسن قطره چشمی نیوکاسل سویه لاسوتا + برونشیت طیور',
            'category' => 'داروخانه تخصصی',
            'price' => 420000,
            'discount_price' => NULL,
            'image_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&auto=format&fit=crop&q=80',
            'description' => 'واکسیناسیون زنده جهت ایجاد ایمنی مخاطی و همورال فوق‌العاده قوی در سیستم تنفسی جوجه و طیور تخمگذار.',
            'brand' => 'رازی وت',
            'stock' => 80,
            'target_animal' => 'chick',
            'pharmacy_tag' => 'vaccines',
            'is_autoship' => 0,
            'autoship_discount' => 0,
            'rating_cache' => 5.0,
            'review_count_cache' => 38
        ],
        [
            'name' => 'محلول ضدعفونی کننده و کمک‌های اولیه هوای سالن و آب طیور',
            'category' => 'داروخانه تخصصی',
            'price' => 310000,
            'discount_price' => 260000,
            'image_url' => 'https://images.unsplash.com/photo-1596704017254-9b121068fb31?w=600&auto=format&fit=crop&q=80',
            'description' => 'ضدعفونی کننده غیرسمی با پایه نانو نقره برای التیام زخم‌ها، استریل کردن خطوط آبرسانی و هوای سالن.',
            'brand' => 'نانووت',
            'stock' => 45,
            'target_animal' => 'chick',
            'pharmacy_tag' => 'first_aid',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.6,
            'review_count_cache' => 11
        ],

        // DOG (سگ)
        [
            'name' => 'قرص ضد انگل و کرم‌کش سگ درنتال پلاس بایر آلمان',
            'category' => 'داروخانه تخصصی',
            'price' => 490000,
            'discount_price' => 420000,
            'image_url' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600&auto=format&fit=crop&q=80',
            'description' => 'معتبرترین قرص ضدانگل طیف وسیع برای سگ‌ها جهت نابودی تضمینی کرم‌های نواری، گرد و ژیاردیا.',
            'brand' => 'بایر (Bayer)',
            'stock' => 50,
            'target_animal' => 'dog',
            'pharmacy_tag' => 'dewormer',
            'is_autoship' => 1,
            'autoship_discount' => 15,
            'rating_cache' => 5.0,
            'review_count_cache' => 64
        ],
        [
            'name' => 'شربت ضد التهاب و مسکن ملئوکسیکام خوراکی سگ',
            'category' => 'داروخانه تخصصی',
            'price' => 580000,
            'discount_price' => 495000,
            'image_url' => 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80',
            'description' => 'داروی ضد التهاب غیر استروئیدی (NSAID) برای کاهش سریع دردهای ناشی از استئوآرتریت و جراحی‌های ارتوپدی.',
            'brand' => 'وت‌فارما',
            'stock' => 25,
            'target_animal' => 'dog',
            'pharmacy_tag' => 'pain_management',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.8,
            'review_count_cache' => 29
        ],
        [
            'name' => 'اسپری استنشاقی و ضد اسپاسم تنفسی سگ‌های نژاد پوزه‌کوتاه',
            'category' => 'داروخانه تخصصی',
            'price' => 640000,
            'discount_price' => 560000,
            'image_url' => 'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=600&auto=format&fit=crop&q=80',
            'description' => 'اسپری تخصصی جهت بهبود تنفس، کاهش التهاب مجاری تنفسی و آسم در سگ‌های بولداگ، پاگ و شیتزو.',
            'brand' => 'پت‌مدیکال',
            'stock' => 18,
            'target_animal' => 'dog',
            'pharmacy_tag' => 'inflammation',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.9,
            'review_count_cache' => 21
        ],
        [
            'name' => 'کیت جامع کمک‌های اولیه اورژانسی سگ و حیوانات خانگی',
            'category' => 'داروخانه تخصصی',
            'price' => 890000,
            'discount_price' => 780000,
            'image_url' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600&auto=format&fit=crop&q=80',
            'description' => 'شامل بتادین حیوانی، بانداژ خودچسب، پنس کنه کش، دماسنج دیجیتال، پد گاز استریل و اسپری التیام زخم.',
            'brand' => 'تریکسی',
            'stock' => 30,
            'target_animal' => 'dog',
            'pharmacy_tag' => 'first_aid',
            'is_autoship' => 0,
            'autoship_discount' => 5,
            'rating_cache' => 4.9,
            'review_count_cache' => 35
        ],
        [
            'name' => 'بالم ارگانیک نرم‌کننده و محافظ پد پنجه سگ و گربه',
            'category' => 'داروخانه تخصصی',
            'price' => 280000,
            'discount_price' => 230000,
            'image_url' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&auto=format&fit=crop&q=80',
            'description' => 'بالم کاملاً طبیعی حاوی شی باتر و موم عسل برای بازسازی ترک خوردگی و خشکی پنجه ناشی از پیاده‌روی روی آسفالت گرم یا سرد.',
            'brand' => 'پت‌کر',
            'stock' => 35,
            'target_animal' => 'dog',
            'pharmacy_tag' => 'hoof_care',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.7,
            'review_count_cache' => 18
        ],

        // CAT (گربه)
        [
            'name' => 'خمیر مالت و مکمل ویتامینه تقویت ایمنی گربه جیم کت',
            'category' => 'داروخانه تخصصی',
            'price' => 460000,
            'discount_price' => 390000,
            'image_url' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80',
            'description' => 'دفع آسان گلوله‌های مویی (Hairball) و تقویت پوشش مو و ناخن گربه با ویتامین‌های گروه B و زینک.',
            'brand' => 'جیم کت (GimCat)',
            'stock' => 45,
            'target_animal' => 'cat',
            'pharmacy_tag' => 'vitamins',
            'is_autoship' => 1,
            'autoship_discount' => 15,
            'rating_cache' => 5.0,
            'review_count_cache' => 78
        ],
        [
            'name' => 'قطره ضد استرس و فرومون آرامبخش درمانی گربه فلی‌وی',
            'category' => 'داروخانه تخصصی',
            'price' => 720000,
            'discount_price' => 640000,
            'image_url' => 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600&auto=format&fit=crop&q=80',
            'description' => 'کاهش اضطراب محیطی، ترس از سفر، پرخاشگری و رفتارهای نشانه‌گذاری با تقلید فرومون چهره‌ای مادر.',
            'brand' => 'فلی‌وی (Feliway)',
            'stock' => 20,
            'target_animal' => 'cat',
            'pharmacy_tag' => 'therapy',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.9,
            'review_count_cache' => 43
        ],
        [
            'name' => 'قطره موضعی ضد کک، کنه و انگل‌های پوستی گربه ادوکیت',
            'category' => 'داروخانه تخصصی',
            'price' => 520000,
            'discount_price' => 450000,
            'image_url' => 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=600&auto=format&fit=crop&q=80',
            'description' => 'محافظت ماهیانه پشت گردنی علیه طیف گسترده‌ای از انگل‌های خارجی و جرب گوش در گربه‌ها.',
            'brand' => 'بایر (Bayer)',
            'stock' => 35,
            'target_animal' => 'cat',
            'pharmacy_tag' => 'dewormer',
            'is_autoship' => 1,
            'autoship_discount' => 12,
            'rating_cache' => 4.8,
            'review_count_cache' => 37
        ],
        [
            'name' => 'قطره اشک شستشو و رفع عفونت و التهاب چشم گربه',
            'category' => 'داروخانه تخصصی',
            'price' => 290000,
            'discount_price' => 240000,
            'image_url' => 'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600&auto=format&fit=crop&q=80',
            'description' => 'محلول استریل پاک‌کننده لکه‌های اشک زیر چشم و تسکین سوزش و التهابات ملتحمه در گربه‌های پرشین و DSH.',
            'brand' => 'پت‌مدیکال',
            'stock' => 40,
            'target_animal' => 'cat',
            'pharmacy_tag' => 'inflammation',
            'is_autoship' => 1,
            'autoship_discount' => 10,
            'rating_cache' => 4.6,
            'review_count_cache' => 19
        ]
    ];

    $insert_stmt = $pdo->prepare("
        INSERT INTO products 
        (name, category, price, discount_price, image_url, description, brand, stock, target_animal, pharmacy_tag, is_autoship, autoship_discount, rating_cache, review_count_cache)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($pharmacy_products as $p) {
        // Check if item already exists by name
        $chk = $pdo->prepare("SELECT id FROM products WHERE name = ?");
        $chk->execute([$p['name']]);
        if (!$chk->fetch()) {
            $insert_stmt->execute([
                $p['name'],
                $p['category'],
                $p['price'],
                $p['discount_price'],
                $p['image_url'],
                $p['description'],
                $p['brand'],
                $p['stock'],
                $p['target_animal'],
                $p['pharmacy_tag'],
                $p['is_autoship'],
                $p['autoship_discount'],
                $p['rating_cache'],
                $p['review_count_cache']
            ]);
            $count++;
        }
    }

    echo "Migration completed successfully! Inserted $count new specialized pharmacy products.\n";

} catch (PDOException $e) {
    echo "Database migration error: " . $e->getMessage() . "\n";
}
