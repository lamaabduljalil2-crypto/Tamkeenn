<?php
require_once __DIR__ . '/../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

require_once __DIR__ . '/../../config/db.php';
if (!function_exists('ensure_animals_setup')) {
    function ensure_animals_setup($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
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
        foreach ([
            'category'   => "ALTER TABLE animals ADD COLUMN category VARCHAR(20) NULL",
            'name'       => "ALTER TABLE animals ADD COLUMN name VARCHAR(255) NULL",
            'image'      => "ALTER TABLE animals ADD COLUMN image VARCHAR(255) NULL",
            'video'      => "ALTER TABLE animals ADD COLUMN video VARCHAR(255) NULL",
            'pos_top'    => "ALTER TABLE animals ADD COLUMN pos_top VARCHAR(20) DEFAULT '30%'",
            'pos_left'   => "ALTER TABLE animals ADD COLUMN pos_left VARCHAR(20) DEFAULT '30%'",
            'width'      => "ALTER TABLE animals ADD COLUMN width VARCHAR(20) DEFAULT '130px'",
            'sort_order' => "ALTER TABLE animals ADD COLUMN sort_order INT DEFAULT 0",
        ] as $col => $alterSql) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE '$col'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, $alterSql);
            }
        }
        $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM animals");
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : ['c' => 1];
        if (intval($countRow['c']) === 0) {
            $seed = [
                ['wild','اسد','images/general/animals/items/lion.png','images/general/animals/videos/lion.mp4','36%','30%','210px',1],
                ['wild','نمر','images/general/animals/items/tiger.png','images/general/animals/videos/tiger.mp4','64%','4%','220px',2],
                ['wild','ذئب','images/general/animals/items/wolf.png','images/general/animals/videos/wolf.mp4','36%','5%','140px',3],
                ['wild','ثعلب','images/general/animals/items/fox.png','images/general/animals/videos/fox.mp4','66%','48%','195px',4],
                ['wild','دب','images/general/animals/items/bear.png','images/general/animals/videos/bear.mp4','31%','75%','160px',5],
                ['wild','ثعبان','images/general/animals/items/snake.png','images/general/animals/videos/snake.mp4','81%','30%','120px',6],
                ['wild','تمساح','images/general/animals/items/crocodile.png','images/general/animals/videos/crocodile.mp4','70%','72%','220px',7],
                ['wild','نسر','images/general/animals/items/eagle.png','images/general/animals/videos/eagle.mp4','12%','12%','110px',8],
                ['wild','ضبع','images/general/animals/items/hyena.png','images/general/animals/videos/hyena.mp4','36%','55%','150px',9],
                ['pet','عصفور','images/general/animals/items/bird.png','images/general/animals/videos/bird.mp4','11%','4%','100px',1],
                ['pet','فراشة','images/general/animals/items/butterfly.png','images/general/animals/videos/butterfly.mp4','6%','61%','55px',2],
                ['pet','صوص','images/general/animals/items/chick.png','images/general/animals/videos/chick.mp4','49%','70%','40px',3],
                ['pet','أرنب','images/general/animals/items/rabbit.png','images/general/animals/videos/rabbit.mp4','51%','85%','60px',4],
                ['pet','بطة','images/general/animals/items/duck.png','images/general/animals/videos/duck.mp4','71%','75%','85px',5],
                ['pet','دجاجة','images/general/animals/items/chicken.png','images/general/animals/videos/chicken.mp4','36%','73%','95px',6],
                ['pet','قطة','images/general/animals/items/cat.png','images/general/animals/videos/cat.mp4','42%','20%','55px',7],
                ['pet','كلب','images/general/animals/items/dog.png','images/general/animals/videos/dog.mp4','46%','10%','80px',8],
                ['pet','بقرة','images/general/animals/items/cow.png','images/general/animals/videos/cow.mp4','66%','3%','230px',9],
                ['pet','خروف','images/general/animals/items/sheep.png','images/general/animals/videos/sheep.mp4','68%','23%','160px',10],
                ['pet','سلحفاة','images/general/animals/items/turtle.png','images/general/animals/videos/turtle.mp4','61%','65%','90px',11],
                ['pet','حصان','images/general/animals/items/horse.png','images/general/animals/videos/horse.mp4','26%','39%','200px',12],
                ['pet','حمار','images/general/animals/items/donkey.png','images/general/animals/videos/donkey.mp4','61%','40%','210px',13],
            ];
            $stmt = mysqli_prepare($conn, "INSERT INTO animals (category,name,image,video,pos_top,pos_left,width,sort_order) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($seed as $a) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7]);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}
mysqli_set_charset($conn, 'utf8mb4');
ensure_animals_setup($conn);

/* جلب الحيوانات الأليفة من قاعدة البيانات */
$category = 'pet';
$rows = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM animals WHERE category = ? ORDER BY sort_order ASC, id ASC");
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }

$animalsData = [];
foreach ($rows as $r) {
    $animalsData[] = [
        'key'      => 'a' . $r['id'],
        'name'     => $r['name'],
        'spelling' => preg_split('//u', $r['name'], -1, PREG_SPLIT_NO_EMPTY),
        'image'    => '../../' . ltrim((string)$r['image'], '/'),
        'video'    => '../../' . ltrim((string)$r['video'], '/'),
        'top'      => $r['pos_top'],
        'left'     => $r['pos_left'],
        'width'    => $r['width'],
    ];
}

/* تسجيل تقدّم الطفل: زار قسم الحيوانات الأليفة (الدالة مكتوبة جوّا الصفحة) */
if (!function_exists('track_progress')) {
    function track_progress($conn, $type, $key, $label = '') {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') return;
        if (!$conn || !in_array($type, ['lesson','story','game','section'], true)) return;
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            child_name VARCHAR(255) NULL,
            activity_type VARCHAR(20) NOT NULL,
            activity_key VARCHAR(100) NOT NULL,
            activity_label VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $uid = intval($_SESSION['user_id']);
        $cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
        $key = substr((string)$key, 0, 100);
        $label = substr((string)$label, 0, 255);
        if ($key === '') return;
        $st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE activity_label = VALUES(activity_label), created_at = CURRENT_TIMESTAMP");
        if ($st) {
            mysqli_stmt_bind_param($st, "issss", $uid, $cname, $type, $key, $label);
            mysqli_stmt_execute($st);
        }
    }
}
track_progress($conn, 'section', 'section-pet', 'الحيوانات الأليفة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>قسم الحيوانات </title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap"
    rel="stylesheet"
  />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --green: #5c8f31;
      --green-dark: #3b6a1d;
      --header-wrap: #dff0d7;
      --header-bg: #c9e6bd;
      --text: #5d6e4a;
      --shadow: 0 10px 24px rgba(68, 93, 48, 0.1);
      --radius-full: 999px;
    }

    body {
      font-family: "Cairo", sans-serif;
      background: #eef5e8;
      color: #334;
      overflow-x: hidden;
    }

    html, body {
      height: 100%;
      overflow: hidden;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    img,
    video {
      display: block;
      max-width: 100%;
    }

    .page-shell {
      width: 96%;
      max-width: 1600px;
      margin: 14px auto 0;
      background: var(--header-wrap);
      padding: 14px 14px 0;
    }

    .children-dash {
      background: var(--header-bg);
      border-radius: 30px;
      padding: 14px 22px;
      box-shadow: var(--shadow);
    }

    .children-dash-inner {
      position: relative;
      min-height: 78px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .dash-start,
    .dash-end {
      display: flex;
      align-items: center;
      z-index: 2;
    }

    .dash-center {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 1;
    }

    .logo-box{
      display:flex;
      align-items:center;
      gap:12px;
      text-decoration:none;
      background:none;
      padding:0;
      border-radius:0;
      box-shadow:none;
      transition:.25s ease;
    }

    .logo-box:hover{
      transform:translateY(-2px);
    }

    .logo-box img{
      width:90px;
      height:auto;
      border-radius:0;
      background:none;
      padding:0;
      box-shadow:none;
      object-fit:contain;
    }

    .dash-nav {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .circle-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.85);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: visible !important;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
      transition: 0.2s ease;
      position:relative;
      flex-shrink:0;
    }

    .circle-icon:hover {
      transform: translateY(-2px);
    }

    .circle-icon video {
      width: 90%;
      height: 90%;
      object-fit: contain;
      border-radius:50%;
    }

    .nav-text{
      position:absolute;
      top:calc(100% + 8px);
      left:50%;
      transform:translateX(-50%) translateY(-6px);
      opacity:0;
      pointer-events:none;
      white-space:nowrap;
      background:linear-gradient(135deg, #ff7aa8, #ff5f8f);
      color:#fff;
      padding:7px 16px;
      border-radius:999px;
      font-size:15px;
      font-weight:900;
      transition:.22s ease;
      z-index:50;
      box-shadow:0 8px 18px rgba(255, 94, 143, 0.30);
    }

    .circle-icon:hover .nav-text{
      opacity:1;
      transform:translateX(-50%) translateY(0);
    }

    .profile-wrap {
      position: relative;
    }

    .profile-btn {
      border: none;
      background: #f1df98;
      border-radius: var(--radius-full);
      padding: 7px 14px 7px 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      font-family: "Cairo", sans-serif;
      font-weight: 800;
      color: #4e7222;
      min-width: 185px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
    }

    .profile-video-box {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      overflow: hidden;
      background: #fff;
      flex-shrink: 0;
    }

    .profile-video-box video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .profile-hello {
      font-size: 17px;
      white-space: nowrap;
    }

    .profile-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      min-width: 190px;
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
      display: none;
      z-index: 1200;
    }

    .profile-wrap.open .profile-menu {
      display: block;
    }

    .profile-menu a {
      display: block;
      padding: 12px 14px;
      font-weight: 700;
      color: #4c5b72;
    }

    .profile-menu a:hover {
      background: #f3f8ee;
    }

    .page-wrap {
      width: 86%;
      max-width: 1180px;
      margin: 20px auto 34px;
      transition: transform .25s ease;
    }

    body.sign-open .page-wrap{
      transform: translateX(140px);
    }

    .animals-section {
      background: linear-gradient(180deg, #f4fde9, #e4f3d1);
      border-radius: 32px;
      padding: 16px;
      box-shadow: var(--shadow);
    }

    .garden-layout {
      display: block;
    }

    .garden-board {
      position: relative;
      min-height: 560px;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 12px 26px rgba(0, 0, 0, 0.1);
      background:
        linear-gradient(rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.02)),
        url("../../images/general/animals/garden-bg.png") center / cover no-repeat;
    }

    .garden-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
      pointer-events: none;
      z-index: 0;
    }

    .animal-spot {
      position: absolute;
      cursor: pointer;
      z-index: 2;
      transition: transform 0.25s ease, filter 0.25s ease;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .animal-spot:hover {
      transform: scale(1.04);
      filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.16));
    }

    .animal-image {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .animal-image img {
      width: 100%;
      height: auto;
      max-width: 120%;
      max-height: 250px;
      object-fit: contain;
      filter: drop-shadow(0 10px 16px rgba(0, 0, 0, 0.12));
      user-select: none;
      pointer-events: none;
    }

    .selected-view {
      position: absolute;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      z-index: 5;
      background: rgba(255, 255, 255, 0.10);
    }

    .selected-view.show {
      display: flex;
    }

    .big-card {
      width: min(900px, 100%);
      min-height: 430px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 30px;
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
      display: grid;
      grid-template-columns: 1fr 1fr;
      overflow: hidden;
      direction: ltr;
    }

    .info-half {
      background: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      direction: rtl;
    }

    .card-half {
      padding: 22px;
    }

    .video-half {
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f7fbf2;
    }

    .animal-word {
      font-size: 42px;
      font-weight: 900;
      color: #4b7121;
      margin-bottom: 18px;
    }

    .selected-animal-inside {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 180px;
      margin-bottom: 18px;
      cursor: pointer;
    }

    .selected-animal-inside img {
      max-width: 220px;
      max-height: 190px;
      object-fit: contain;
      filter: drop-shadow(0 10px 16px rgba(0, 0, 0, 0.16));
    }

    .plain-spelling {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 14px;
    }

    .letter-img img {
      width: 62px;
      height: 62px;
      object-fit: contain;
    }

    .sign-video-box {
      width: 100%;
      max-width: 320px;
      min-height: 340px;
      border-radius: 24px;
      overflow: hidden;
      position: relative;
      background: #dce9cb;
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
    }

    .sign-video-box video {
      width: 100%;
      height: 340px;
      object-fit: cover;
      background: #dce9cb;
    }

    .video-play {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: rgba(84, 123, 33, 0.82);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      cursor: pointer;
      z-index: 2;
    }

    .help-floating{
      position:fixed !important;
      left:22px !important;
      bottom:22px !important;
      z-index:2147483647 !important;
    }

    .help-circle{
      width:75px;
      height:75px;
      border-radius:50%;
      border:4px solid white;
      background:#32d27b;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      padding:8px;
      box-shadow:0 10px 25px rgba(0,0,0,.25);
      transition:.25s ease;
    }

    .help-circle.active{
      box-shadow:
        0 0 0 8px rgba(50,210,123,.25),
        0 0 28px rgba(50,210,123,.9),
        0 10px 25px rgba(0,0,0,.25);
      transform:scale(1.08);
    }

    .help-circle img{
      width:100%;
      height:100%;
      object-fit:contain;
    }

    .help-video-box{
      position:absolute;
      left:0;
      bottom:95px;
      width:260px;
      background:white;
      border-radius:24px;
      padding:8px;
      display:none;
      box-shadow:0 18px 45px rgba(0,0,0,.28);
    }

    .help-video-box.active{
      display:block;
    }

    .help-text{
      text-align:center;
      font-family:Arial, sans-serif;
      font-size:16px;
      font-weight:900;
      color:#21425f;
      background:#eafff2;
      border:2px solid #92efba;
      border-radius:16px;
      padding:8px 10px;
      margin-bottom:8px;
      line-height:1.5;
    }

    .help-video-wrap{
      position:relative;
      height:260px;
    }

    .help-video-wrap video{
      width:100%;
      height:100%;
      object-fit:contain;
      background:#000;
      border-radius:18px;
    }

    .help-play{
      position:absolute;
      inset:0;
      margin:auto;
      width:74px;
      height:74px;
      border-radius:50%;
      border:4px solid white;
      background:rgba(255,91,120,.92);
      color:white;
      font-size:34px;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      z-index:5;
    }

    .help-play.hide{
      display:none !important;
    }

    .close-help{
      position:absolute;
      top:-20px;
      right:-20px;
      width:55px;
      height:55px;
      border-radius:50%;
      border:4px solid white;
      background:#ff3b3b;
      color:white;
      font-size:26px;
      font-weight:900;
      cursor:pointer;
      z-index:9999;
      box-shadow:0 8px 20px rgba(0,0,0,0.25);
    }

    @media (max-width: 980px) {
      .children-dash-inner {
        min-height: auto;
        flex-direction: column;
        gap: 14px;
      }

      .dash-center {
        position: static;
        transform: none;
      }
    }

    @media (max-width: 900px) {
      .big-card {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .animal-word {
        font-size: 34px;
      }

      .selected-animal-inside img {
        max-width: 180px;
        max-height: 150px;
      }

      .sign-video-box,
      .sign-video-box video {
        max-width: 100%;
        height: 300px;
        min-height: 300px;
      }
    }

    @media (max-width: 700px) {
      .page-wrap {
        width: 94%;
      }

      .garden-board {
        min-height: 520px;
      }

      .animal-spot .animal-image img {
        max-height: 90px;
      }

      .letter-img img {
        width: 52px;
        height: 52px;
      }
    }
  </style>
</head>

<body>

  <div class="page-shell">
    <header class="children-dash">
      <div class="children-dash-inner">
        <div class="dash-start">
          <a href="../../index.php" class="logo-box" tabindex="0">
            <img src="../../logo.png" alt="logo" />
          </a>
        </div>

        <div class="dash-center">
          <nav class="dash-nav">
            <a
              href="../../auth/children.php"
              class="circle-icon"
              tabindex="0"
              aria-label="العودة إلى الصفحة الرئيسية"
              data-dashboard-video="../../assets/videos/home-icon-sign.mp4"
              data-dashboard-text="صفحة الطفل"
            >
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/children.mp4" type="video/mp4" />
              </video>
              <span class="nav-text">صفحة الطفل</span>
            </a>

            <a
              href="../../subjects/general/animals.php"
              class="circle-icon"
              tabindex="0"
              aria-label="الحيوانات"
              data-dashboard-video="../../assets/videos/animals-sign.mp4"
              data-dashboard-text="الحيوانات"
            >
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/animal.mp4" type="video/mp4" />
              </video>
              <span class="nav-text">الحيوانات</span>
            </a>
          </nav>
        </div>

        <div class="dash-end">
          <div class="profile-wrap">
            <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
              <div class="profile-video-box">
                <video autoplay muted loop playsinline>
                  <source src="../../assets/icons/profile.mp4" type="video/mp4" />
                </video>
              </div>
              <span class="profile-hello">
                مرحباً <?php echo htmlspecialchars($child_display_name); ?>
              </span>
            </button>

            <div class="profile-menu" id="profileMenu">
              <a href="../../auth/account_settings.php"
                 data-dashboard-video="../../assets/videos/settings-sign.mp4"
                 data-dashboard-text="إعدادات الحساب">إعدادات الحساب</a>

              <a href="../../auth/logout.php"
                 data-dashboard-video="../../assets/videos/logout-sign.mp4"
                 data-dashboard-text="تسجيل الخروج">تسجيل الخروج</a>
            </div>
          </div>
        </div>
      </div>
    </header>
  </div>

  <main class="page-wrap">
    <section class="animals-section">
      <div class="garden-layout">
        <div class="garden-board" id="gardenBoard">
          <div class="garden-overlay"></div>

          <div class="selected-view" id="selectedView">
            <div class="animal-card big-card">
              <div class="card-half video-half">
                <div class="sign-video-box">
                  <video id="detailsVideo" playsinline>
                    <source id="detailsVideoSource" src="" type="video/mp4" />
                  </video>
                  <div class="video-play" id="detailsPlayBtn">▶</div>
                </div>
              </div>

              <div class="card-half info-half">
                <div class="animal-word" id="animalWord"></div>
                <div class="selected-animal-inside" id="selectedAnimalSide"></div>
                <div class="spelling-row plain-spelling" id="spellingRow"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <div class="help-floating">
    <button class="help-circle" id="helpToggleBtn" type="button">
      <img src="../../assets/icons/sign-icon.png" alt="لغة الإشارة">
    </button>

    <div class="help-video-box" id="helpVideoBox">
      <button class="close-help" id="closeHelpBtn" type="button">×</button>

      <div class="help-text" id="helpText">اختر حيواناً من المزرعة</div>

      <div class="help-video-wrap">
        <video id="helpVideo" muted playsinline preload="auto"></video>
        <button class="help-play" id="helpPlayBtn" type="button">▶</button>
      </div>
    </div>
  </div>

  <script>
    const profileBtn = document.getElementById("profileBtn");
    const profileWrap = document.querySelector(".profile-wrap");
    const profileMenu = document.getElementById("profileMenu");

    if (profileBtn && profileWrap && profileMenu) {
      profileBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        profileWrap.classList.toggle("open");
      });

      document.addEventListener("click", function (e) {
        if (!profileWrap.contains(e.target)) {
          profileWrap.classList.remove("open");
        }
      });
    }

    const helpToggleBtn = document.getElementById("helpToggleBtn");
    const helpVideoBox = document.getElementById("helpVideoBox");
    const closeHelpBtn = document.getElementById("closeHelpBtn");
    const helpVideo = document.getElementById("helpVideo");
    const helpPlayBtn = document.getElementById("helpPlayBtn");
    const helpText = document.getElementById("helpText");

    let helpOpen = false;
    let currentHelpVideo = "";

    function openHelpVideo(videoPath, text) {
      if (!helpOpen || !videoPath) return;

      helpVideoBox.classList.add("active");
      helpText.textContent = text || "اختر حيواناً من المزرعة";

      if (currentHelpVideo !== videoPath) {
        helpVideo.pause();
        helpVideo.removeAttribute("src");
        helpVideo.src = videoPath;
        helpVideo.load();
        currentHelpVideo = videoPath;
      }

      helpVideo.currentTime = 0;
      helpVideo.muted = true;

      helpVideo.play().then(function(){
        helpPlayBtn.classList.add("hide");
      }).catch(function(){
        helpPlayBtn.classList.remove("hide");
      });
    }

    function loadHelpVideo(videoPath, text) {
      if (!helpOpen || !videoPath) return;

      helpVideoBox.classList.add("active");
      helpText.textContent = text || "اختر حيواناً من المزرعة";

      if (currentHelpVideo !== videoPath) {
        helpVideo.pause();
        helpVideo.removeAttribute("src");
        helpVideo.src = videoPath;
        helpVideo.load();
        currentHelpVideo = videoPath;
      }

      helpVideo.currentTime = 0;
      helpPlayBtn.classList.remove("hide");
    }

    function stopHelpVideo(keepBox = false) {
      helpVideo.pause();

      if (!keepBox) {
        helpVideo.currentTime = 0;
        helpVideoBox.classList.remove("active");
        helpText.textContent = "اختر حيواناً من المزرعة";
      }

      helpPlayBtn.classList.remove("hide");
    }

    function closeHelp() {
      helpOpen = false;
      document.body.classList.remove("sign-open");
      helpToggleBtn.classList.remove("active");
      helpVideoBox.classList.remove("active");
      helpVideo.pause();
      helpVideo.currentTime = 0;
      helpVideo.removeAttribute("src");
      helpVideo.load();
      currentHelpVideo = "";
      helpPlayBtn.classList.remove("hide");
      helpText.textContent = "اختر حيواناً من المزرعة";
    }

    helpToggleBtn.addEventListener("click", function () {
      if (helpOpen) {
        closeHelp();
      } else {
        helpOpen = true;
        document.body.classList.add("sign-open");
        helpToggleBtn.classList.add("active");
        helpVideoBox.classList.add("active");
        helpText.textContent = "اختر حيواناً من المزرعة";
        helpVideo.src = "../../assets/videos/animals-farm-help.mp4";
        helpVideo.load();
        currentHelpVideo = "../../assets/videos/animals-farm-help.mp4";
        helpPlayBtn.classList.remove("hide");
      }
    });

    closeHelpBtn.addEventListener("click", closeHelp);

    helpPlayBtn.addEventListener("click", function () {
      if (!helpVideo.src) return;

      helpVideo.play().then(function(){
        helpPlayBtn.classList.add("hide");
      }).catch(function(){
        helpPlayBtn.classList.remove("hide");
      });
    });

    helpVideo.addEventListener("ended", function () {
      helpPlayBtn.classList.remove("hide");
    });

    helpVideo.addEventListener("pause", function () {
      if (!helpVideo.ended) {
        helpPlayBtn.classList.remove("hide");
      }
    });

    document.querySelectorAll("[data-dashboard-video]").forEach(function (item) {
      item.addEventListener("mouseenter", function () {
        openHelpVideo(item.dataset.dashboardVideo, item.dataset.dashboardText || "");
      });

      item.addEventListener("focus", function () {
        openHelpVideo(item.dataset.dashboardVideo, item.dataset.dashboardText || "");
      });

      item.addEventListener("mouseleave", function () {
        if (helpOpen) stopHelpVideo(false);
      });

      item.addEventListener("blur", function () {
        if (helpOpen) stopHelpVideo(false);
      });
    });

    const animals = <?php echo json_encode($animalsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const arabicLetterImages = {
      "ا": "../../images/signs/arabic/1.png",
      "أ": "../../images/signs/arabic/1.png",
      "ب": "../../images/signs/arabic/2.png",
      "ت": "../../images/signs/arabic/3.png",
      "ث": "../../images/signs/arabic/4.png",
      "ج": "../../images/signs/arabic/5.png",
      "ح": "../../images/signs/arabic/6.png",
      "خ": "../../images/signs/arabic/7.png",
      "د": "../../images/signs/arabic/8.png",
      "ذ": "../../images/signs/arabic/9.png",
      "ر": "../../images/signs/arabic/10.png",
      "ز": "../../images/signs/arabic/11.png",
      "س": "../../images/signs/arabic/12.png",
      "ش": "../../images/signs/arabic/13.png",
      "ص": "../../images/signs/arabic/14.png",
      "ض": "../../images/signs/arabic/15.png",
      "ط": "../../images/signs/arabic/16.png",
      "ظ": "../../images/signs/arabic/17.png",
      "ع": "../../images/signs/arabic/18.png",
      "غ": "../../images/signs/arabic/19.png",
      "ف": "../../images/signs/arabic/20.png",
      "ق": "../../images/signs/arabic/21.png",
      "ك": "../../images/signs/arabic/22.png",
      "ل": "../../images/signs/arabic/23.png",
      "م": "../../images/signs/arabic/24.png",
      "ن": "../../images/signs/arabic/25.png",
      "ه": "../../images/signs/arabic/26.png",
      "و": "../../images/signs/arabic/27.png",
      "ي": "../../images/signs/arabic/28.png",
      "ة": "../../images/signs/arabic/29.png",
      "ء": "../../images/signs/arabic/30.png",
      "ى": "../../images/signs/arabic/31.png"
    };

    const gardenBoard = document.getElementById("gardenBoard");
    const selectedView = document.getElementById("selectedView");
    const selectedAnimalSide = document.getElementById("selectedAnimalSide");
    const spellingRow = document.getElementById("spellingRow");
    const detailsVideo = document.getElementById("detailsVideo");
    const detailsVideoSource = document.getElementById("detailsVideoSource");
    const detailsPlayBtn = document.getElementById("detailsPlayBtn");

    let activeKey = null;

    function createAnimalSpot(item) {

      const spot = document.createElement("div");

      spot.className = "animal-spot";
      spot.dataset.key = item.key;

      spot.style.top = item.top;
      spot.style.left = item.left;
      spot.style.width = item.width || "130px";

      spot.innerHTML = `
        <div class="animal-image">
          <img src="${item.image}" alt="${item.name}" draggable="false">
        </div>
      `;

      spot.addEventListener("click", () => {

        toggleAnimal(item);

        /* فيديو زر المساعدة يتغير فقط عند الضغط */

        if (helpOpen) {

          helpVideoBox.classList.add("active");

          helpText.textContent =
            "اضغط على الحيوان للعودة إلى باقي الحيوانات";

          helpVideo.pause();

          helpVideo.src = "../../assets/videos/animal-card-help.mp4";
          helpVideo.load();

          currentHelpVideo = "../../assets/videos/animal-card-help.mp4";

          helpPlayBtn.classList.remove("hide");
        }

      });

      return spot;
    }

    function renderGarden() {
      document.querySelectorAll(".animal-spot").forEach((el) => el.remove());

      if (activeKey) return;

      animals.forEach((item) => gardenBoard.appendChild(createAnimalSpot(item)));
    }

    function renderSpelling(letters) {
      spellingRow.innerHTML = "";

      letters.forEach((letter) => {
        const src = arabicLetterImages[letter] || arabicLetterImages["ا"];
        const box = document.createElement("div");
        box.className = "letter-img";
        box.innerHTML = `<img src="${src}" alt="${letter}">`;
        spellingRow.appendChild(box);
      });
    }

    function showDetails(item) {
      selectedView.classList.add("show");
      document.getElementById("animalWord").textContent = item.name;

      selectedAnimalSide.innerHTML = `
        <img src="${item.image}" alt="${item.name}">
      `;

      selectedAnimalSide.onclick = hideDetails;

      renderSpelling(item.spelling);

      detailsVideo.pause();
      detailsVideoSource.src = item.video;
      detailsVideo.load();
      detailsVideo.playbackRate = 1;
      detailsPlayBtn.style.display = "flex";
    }

    function hideDetails() {
      activeKey = null;
      selectedView.classList.remove("show");
      selectedAnimalSide.innerHTML = "";
      spellingRow.innerHTML = "";
      document.getElementById("animalWord").textContent = "";

      detailsVideo.pause();
      detailsVideo.currentTime = 0;
      detailsVideo.playbackRate = 1;
      detailsPlayBtn.style.display = "flex";

      renderGarden();
    }

    function toggleAnimal(item) {
      if (activeKey === item.key) {
        hideDetails();
        return;
      }

      activeKey = item.key;
      renderGarden();
      showDetails(item);
    }

    function playVideoNormal() {
      detailsVideo.playbackRate = 1;
      detailsVideo.play();
      detailsPlayBtn.style.display = "none";
    }

    detailsPlayBtn.addEventListener("click", playVideoNormal);

    detailsVideo.addEventListener("click", function () {
      if (detailsVideo.paused) {
        detailsVideo.play();
        detailsPlayBtn.style.display = "none";
      } else {
        detailsVideo.pause();
        detailsPlayBtn.style.display = "flex";
      }
    });

    detailsVideo.addEventListener("ended", function () {
      detailsPlayBtn.style.display = "flex";
    });

    renderGarden();
  </script>
</body>
</html>
