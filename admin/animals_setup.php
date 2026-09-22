<?php
/*
   ملف إعداد جدول الحيوانات.
   ضعه داخل مجلد config بجانب db.php  ->  config/animals_setup.php
   ينشئ الجدول، يتأكد من الأعمدة، ويعبّي الحيوانات الموجودة مرة واحدة.
*/

function ensure_animals_setup($conn) {

    mysqli_set_charset($conn, 'utf8mb4');

    /* 1) إنشاء الجدول */
    mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS animals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(20) NOT NULL,
        name VARCHAR(255) NOT NULL,
        image VARCHAR(255) NULL,
        video VARCHAR(255) NULL,
        pos_top VARCHAR(20) DEFAULT '30%',
        pos_left VARCHAR(20) DEFAULT '30%',
        width VARCHAR(20) DEFAULT '130px',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    /* 2) التأكد من وجود كل الأعمدة (لو الجدول قديم وناقص) */
    $cols = [
        'category'   => "ALTER TABLE animals ADD COLUMN category VARCHAR(20) NULL",
        'name'       => "ALTER TABLE animals ADD COLUMN name VARCHAR(255) NULL",
        'image'      => "ALTER TABLE animals ADD COLUMN image VARCHAR(255) NULL",
        'video'      => "ALTER TABLE animals ADD COLUMN video VARCHAR(255) NULL",
        'pos_top'    => "ALTER TABLE animals ADD COLUMN pos_top VARCHAR(20) DEFAULT '30%'",
        'pos_left'   => "ALTER TABLE animals ADD COLUMN pos_left VARCHAR(20) DEFAULT '30%'",
        'width'      => "ALTER TABLE animals ADD COLUMN width VARCHAR(20) DEFAULT '130px'",
        'sort_order' => "ALTER TABLE animals ADD COLUMN sort_order INT DEFAULT 0",
    ];
    foreach ($cols as $col => $alterSql) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE '$col'");
        if ($check && mysqli_num_rows($check) == 0) {
            mysqli_query($conn, $alterSql);
        }
    }

    /* 3) تعبئة الحيوانات الموجودة مرة واحدة فقط إذا الجدول فاضي */
    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM animals");
    $countRow = $countRes ? mysqli_fetch_assoc($countRes) : ['c' => 1];

    if (intval($countRow['c']) === 0) {

        /* [category, name, image, video, top, left, width, sort_order]
           ملاحظة: قيم top هون مضاف لها 6% لأن الكود القديم كان يزيد 6% وقت العرض */
        $seed = [
            // ===== مفترسة =====
            ['wild', 'اسد',    'images/general/animals/items/lion.png',      'images/general/animals/videos/lion.mp4',      '36%', '30%', '210px', 1],
            ['wild', 'نمر',    'images/general/animals/items/tiger.png',     'images/general/animals/videos/tiger.mp4',     '64%', '4%',  '220px', 2],
            ['wild', 'ذئب',    'images/general/animals/items/wolf.png',      'images/general/animals/videos/wolf.mp4',      '36%', '5%',  '140px', 3],
            ['wild', 'ثعلب',   'images/general/animals/items/fox.png',       'images/general/animals/videos/fox.mp4',       '66%', '48%', '195px', 4],
            ['wild', 'دب',     'images/general/animals/items/bear.png',      'images/general/animals/videos/bear.mp4',      '31%', '75%', '160px', 5],
            ['wild', 'ثعبان',  'images/general/animals/items/snake.png',     'images/general/animals/videos/snake.mp4',     '81%', '30%', '120px', 6],
            ['wild', 'تمساح',  'images/general/animals/items/crocodile.png', 'images/general/animals/videos/crocodile.mp4', '70%', '72%', '220px', 7],
            ['wild', 'نسر',    'images/general/animals/items/eagle.png',     'images/general/animals/videos/eagle.mp4',     '12%', '12%', '110px', 8],
            ['wild', 'ضبع',    'images/general/animals/items/hyena.png',     'images/general/animals/videos/hyena.mp4',     '36%', '55%', '150px', 9],

            // ===== أليفة =====
            ['pet', 'عصفور',  'images/general/animals/items/bird.png',      'images/general/animals/videos/bird.mp4',      '11%', '4%',  '100px', 1],
            ['pet', 'فراشة',  'images/general/animals/items/butterfly.png', 'images/general/animals/videos/butterfly.mp4', '6%',  '61%', '55px',  2],
            ['pet', 'صوص',    'images/general/animals/items/chick.png',     'images/general/animals/videos/chick.mp4',     '49%', '70%', '40px',  3],
            ['pet', 'أرنب',   'images/general/animals/items/rabbit.png',    'images/general/animals/videos/rabbit.mp4',    '51%', '85%', '60px',  4],
            ['pet', 'بطة',    'images/general/animals/items/duck.png',      'images/general/animals/videos/duck.mp4',      '71%', '75%', '85px',  5],
            ['pet', 'دجاجة',  'images/general/animals/items/chicken.png',   'images/general/animals/videos/chicken.mp4',   '36%', '73%', '95px',  6],
            ['pet', 'قطة',    'images/general/animals/items/cat.png',       'images/general/animals/videos/cat.mp4',       '42%', '20%', '55px',  7],
            ['pet', 'كلب',    'images/general/animals/items/dog.png',       'images/general/animals/videos/dog.mp4',       '46%', '10%', '80px',  8],
            ['pet', 'بقرة',   'images/general/animals/items/cow.png',       'images/general/animals/videos/cow.mp4',       '66%', '3%',  '230px', 9],
            ['pet', 'خروف',   'images/general/animals/items/sheep.png',     'images/general/animals/videos/sheep.mp4',     '68%', '23%', '160px', 10],
            ['pet', 'سلحفاة', 'images/general/animals/items/turtle.png',    'images/general/animals/videos/turtle.mp4',    '61%', '65%', '90px',  11],
            ['pet', 'حصان',   'images/general/animals/items/horse.png',     'images/general/animals/videos/horse.mp4',     '26%', '39%', '200px', 12],
            ['pet', 'حمار',   'images/general/animals/items/donkey.png',    'images/general/animals/videos/donkey.mp4',    '61%', '40%', '210px', 13],
        ];

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO animals (category, name, image, video, pos_top, pos_left, width, sort_order)
             VALUES (?,?,?,?,?,?,?,?)"
        );

        foreach ($seed as $a) {
            mysqli_stmt_bind_param($stmt, "sssssssi", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7]);
            mysqli_stmt_execute($stmt);
        }
    }
}
