<?php
require_once __DIR__ . '/../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

require_once __DIR__ . '/../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

if (!function_exists('ensure_food_setup')) {
    function ensure_food_setup($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS food_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            image VARCHAR(255) NULL,
            video VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        foreach ([
            'category'   => "ALTER TABLE food_items ADD COLUMN category VARCHAR(20) NULL",
            'name'       => "ALTER TABLE food_items ADD COLUMN name VARCHAR(255) NULL",
            'image'      => "ALTER TABLE food_items ADD COLUMN image VARCHAR(255) NULL",
            'video'      => "ALTER TABLE food_items ADD COLUMN video VARCHAR(255) NULL",
            'sort_order' => "ALTER TABLE food_items ADD COLUMN sort_order INT DEFAULT 0",
        ] as $col => $alterSql) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM food_items LIKE '$col'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, $alterSql);
            }
        }
        $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM food_items");
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : ['c' => 1];
        if (intval($countRow['c']) === 0) {
            $seed = [
                ['vegetable','طماطم','images/general/vegetables/images/tomato.png','images/general/vegetables/videos/tomato.mp4',1],
                ['vegetable','خيار','images/general/vegetables/images/cucumber.png','images/general/vegetables/videos/cucumber.mp4',2],
                ['vegetable','جزر','images/general/vegetables/images/carrot.png','images/general/vegetables/videos/carrot.mp4',3],
                ['vegetable','بطاطا','images/general/vegetables/images/potato.png','images/general/vegetables/videos/potato.mp4',4],
                ['vegetable','باذنجان','images/general/vegetables/images/eggplant.png','images/general/vegetables/videos/eggplant.mp4',5],
                ['vegetable','فلفل','images/general/vegetables/images/pepper.png','images/general/vegetables/videos/pepper.mp4',6],
                ['vegetable','بصل','images/general/vegetables/images/onion.png','images/general/vegetables/videos/onion.mp4',7],
                ['vegetable','خس','images/general/vegetables/images/lettuce.png','images/general/vegetables/videos/lettuce.mp4',8],
                ['vegetable','كوسا','images/general/vegetables/images/zucchini.png','images/general/vegetables/videos/zucchini.mp4',9],
                ['vegetable','ثوم','images/general/vegetables/images/garlic.png','images/general/vegetables/videos/garlic.mp4',10],
                ['vegetable','ذرة','images/general/vegetables/images/corn.png','images/general/vegetables/videos/corn.mp4',11],
                ['vegetable','ليمون','images/general/vegetables/images/lemon.png','images/general/vegetables/videos/lemon.mp4',12],
                ['fruit','خوخ','images/general/fruits/images/peach.png','images/general/fruits/videos/peach.mp4',1],
                ['fruit','برتقال','images/general/fruits/images/orange.png','images/general/fruits/videos/orange.mp4',2],
                ['fruit','بطيخ','images/general/fruits/images/melon.png','images/general/fruits/videos/melon.mp4',3],
                ['fruit','مانجا','images/general/fruits/images/mango.png','images/general/fruits/videos/mango.mp4',4],
                ['fruit','كيوي','images/general/fruits/images/kiwi.png','images/general/fruits/videos/kiwi.mp4',5],
                ['fruit','عنب','images/general/fruits/images/grape.png','images/general/fruits/videos/grape.mp4',6],
                ['fruit','تين','images/general/fruits/images/fig.png','images/general/fruits/videos/fig.mp4',7],
                ['fruit','موز','images/general/fruits/images/banana.png','images/general/fruits/videos/banana.mp4',8],
                ['fruit','تفاح','images/general/fruits/images/apple.png','images/general/fruits/videos/apple.mp4',9],
                ['fruit','جوز الهند','images/general/fruits/images/coconut.png','images/general/fruits/videos/coconut.mp4',10],
                ['fruit','فراولة','images/general/fruits/images/strawberry.png','images/general/fruits/videos/strawberry.mp4',11],
                ['fruit','اجاص','images/general/fruits/images/pear.png','images/general/fruits/videos/pear.mp4',12],
            ];
            $stmt = mysqli_prepare($conn, "INSERT INTO food_items (category,name,image,video,sort_order) VALUES (?,?,?,?,?)");
            foreach ($seed as $f) {
                mysqli_stmt_bind_param($stmt, "ssssi", $f[0], $f[1], $f[2], $f[3], $f[4]);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}
ensure_food_setup($conn);

$category = 'fruit';
$rows = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM food_items WHERE category = ? ORDER BY sort_order ASC, id ASC");
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }

$itemsData = [];
foreach ($rows as $r) {
    $itemsData[] = [
        'key'   => 'f' . $r['id'],
        'name'  => $r['name'],
        'image' => '../../' . ltrim((string)$r['image'], '/'),
        'video' => '../../' . ltrim((string)$r['video'], '/'),
    ];
}

/* تسجيل تقدّم الطفل: زار قسم الفواكه (الدالة مكتوبة جوّا الصفحة) */
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
track_progress($conn, 'section', 'section-fruit', 'الفواكه');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>قسم الفواكه </title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body{
      font-family:"Cairo", sans-serif;
      background:#f6edf4;
      color:#334;
      overflow-x:hidden;
    }

    a{
      text-decoration:none;
      color:inherit;
    }

    img, video{
      display:block;
      max-width:100%;
    }

    :root{
      --blue:#9b2f67;
      --text:#8a6d80;
      --yellow:#f3c94a;
      --soft-pink:#f8dce8;
      --hero:#f4e4ec;
      --header-wrap:#eadff1;
      --header-bg:#cfe2ee;
      --shadow:0 10px 24px rgba(99, 91, 136, .08);
      --shadow-hover:0 14px 28px rgba(99, 91, 136, .12);
      --radius-xl:32px;
      --radius-lg:24px;
      --radius-full:999px;
    }

    /* header */
    .page-shell{
      width:96%;
      max-width:1720px;
      margin:14px auto 0;
      background:var(--header-wrap);
      padding:14px 14px 0;
    }

    .children-dash{
      background:var(--header-bg);
      border-radius:30px;
      padding:14px 22px;
      box-shadow:var(--shadow);
    }

    .children-dash-inner{
      position:relative;
      min-height:78px;
      display:flex;
      align-items:center;
      justify-content:space-between;
    }

    .dash-start,
    .dash-end{
      display:flex;
      align-items:center;
      z-index:2;
    }

    .dash-center{
      position:absolute;
      left:50%;
      top:50%;
      transform:translate(-50%, -50%);
      z-index:1;
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

    .dash-nav{
      display:flex;
      align-items:center;
      gap:14px;
    }

    .circle-icon{
      width:80px;
      height:80px;
      border-radius:50%;
      background:rgba(255,255,255,.82);
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      box-shadow:0 6px 16px rgba(0,0,0,.05);
      transition:.2s ease;
    }

    .circle-icon:hover{
      transform:translateY(-2px);
    }

    .circle-icon video{
      width:90%;
      height:90%;
      object-fit:contain;
    }

    .profile-wrap{
      position:relative;
    }

    .profile-btn{
      border:none;
      background:#ead9a2;
      border-radius:var(--radius-full);
      padding:7px 14px 7px 10px;
      display:flex;
      align-items:center;
      gap:10px;
      cursor:pointer;
      font-family:"Cairo",sans-serif;
      font-weight:800;
      color:#0f568f;
      min-width:185px;
      box-shadow:0 6px 16px rgba(0,0,0,.04);
    }

    .profile-video-box{
      width:50px;
      height:50px;
      border-radius:50%;
      overflow:hidden;
      background:#fff;
      flex-shrink:0;
    }

    .profile-video-box video{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .profile-hello{
      font-size:17px;
      white-space:nowrap;
    }

    .profile-menu{
      position:absolute;
      top:calc(100% + 8px);
      left:0;
      min-width:190px;
      background:#fff;
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 14px 28px rgba(0,0,0,.12);
      display:none;
      z-index:100;
    }

    .profile-wrap.open .profile-menu{
      display:block;
    }

    .profile-menu a{
      display:block;
      padding:12px 14px;
      font-weight:700;
      color:#4c5b72;
    }

    .profile-menu a:hover{
      background:#f6effa;
    }

    /* page */
    .page-wrap{
      width:82%;
      max-width:1300px;
      margin:28px auto 40px;
    }

    .hero-box{
      background:var(--hero);
      border-radius:34px;
      padding:28px 34px;
      box-shadow:var(--shadow);
      text-align:center;
      margin-bottom:28px;
    }

    .hero-box h2{
      font-size:40px;
      color:var(--blue);
      font-weight:900;
      margin-bottom:8px;
    }

    .hero-box p{
      font-size:18px;
      color:var(--text);
      font-weight:700;
    }

    .fruits-section{
      background:var(--soft-pink);
      border-radius:32px;
      padding:30px 24px;
      box-shadow:var(--shadow);
    }

    .section-title{
      text-align:center;
      margin-bottom:22px;
    }

    .section-title h3{
      font-size:38px;
      color:var(--blue);
      font-weight:900;
      margin-bottom:4px;
    }

    .section-title p{
      font-size:17px;
      color:#9a7e8e;
      font-weight:700;
    }

    .cards-area{
      position:relative;
      min-height:540px;
    }

    .fruits-grid{
      display:grid;
      grid-template-columns:repeat(4, minmax(170px, 1fr));
      gap:22px;
      transition:.3s ease;
    }

    .fruit-card{
      background:linear-gradient(180deg,#ffffffd8,#fff6facc);
      border-radius:26px;
      padding:18px 14px 16px;
      box-shadow:0 8px 18px rgba(0,0,0,.06);
      text-align:center;
      cursor:pointer;
      transition:.25s ease;
      border:3px solid transparent;
    }

    .fruit-card:hover{
      transform:translateY(-5px);
      box-shadow:var(--shadow-hover);
    }

    .fruit-card.active{
      border-color:#ea98bb;
      box-shadow:0 14px 28px rgba(175, 83, 122, .18);
    }

    .fruit-image-wrap{
      width:112px;
      height:112px;
      border-radius:50%;
      background:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      margin:0 auto 12px;
      box-shadow:0 8px 18px rgba(0,0,0,.08);
      overflow:hidden;
    }

    .fruit-image-wrap img{
      width:74%;
      height:74%;
      object-fit:contain;
    }

    .fruit-name{
      font-size:22px;
      color:var(--blue);
      font-weight:900;
      line-height:1.3;
    }

    .selected-layout{
      display:none;
      grid-template-columns:220px 1fr;
      gap:24px;
      align-items:start;
    }

    .selected-layout.show{
      display:grid;
    }

    .selected-card-holder{
      display:flex;
      justify-content:center;
    }

    .selected-card-holder .fruit-card{
      width:100%;
      max-width:220px;
      min-height:240px;
      position:sticky;
      top:18px;
    }

    .details-card{
      background:rgba(255,255,255,.72);
      border-radius:28px;
      padding:24px;
      box-shadow:0 10px 24px rgba(0,0,0,.07);
      display:grid;
      grid-template-columns:1fr 320px;
      gap:22px;
      align-items:stretch;
      min-height:310px;
    }

    .details-info{
      display:flex;
      flex-direction:column;
      justify-content:center;
    }

    .details-label{
      display:inline-block;
      background:#f0d69a;
      color:#7b6116;
      border-radius:999px;
      padding:7px 14px;
      font-size:14px;
      font-weight:800;
      margin-bottom:12px;
      width:fit-content;
    }

    .details-title{
      font-size:36px;
      color:var(--blue);
      font-weight:900;
      margin-bottom:12px;
    }

    .details-subtitle{
      font-size:20px;
      color:#8c7180;
      font-weight:800;
      margin-bottom:12px;
    }

    .spelling-row{
      display:flex;
      flex-direction:column;
      gap:14px;
      margin-top:8px;
      align-items:flex-start;
    }

    .spelling-word{
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:center;
      justify-content:flex-start;
    }

    .spelling-word img{
      width:72px;
      height:72px;
      object-fit:contain;
      background:transparent;
      border:none;
      box-shadow:none;
      padding:0;
      border-radius:0;
    }

    .sign-video-box{
      border-radius:24px;
      overflow:hidden;
      position:relative;
      background:#d7c8d0;
      min-height:260px;
      box-shadow:0 10px 22px rgba(0,0,0,.08);
    }

    .sign-video-box video{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .video-play{
      position:absolute;
      top:50%;
      left:50%;
      transform:translate(-50%,-50%);
      width:68px;
      height:68px;
      border-radius:50%;
      background:rgba(157,74,100,.72);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:28px;
      cursor:pointer;
      z-index:2;
    }

    .back-hint{
      margin-top:14px;
      font-size:14px;
      color:#8a7280;
      font-weight:700;
    }

    @media (max-width: 1200px){
      .fruits-grid{
        grid-template-columns:repeat(3, minmax(170px, 1fr));
      }

      .details-card{
        grid-template-columns:1fr 280px;
      }
    }

    @media (max-width: 980px){
      .children-dash-inner{
        min-height:auto;
        display:flex;
        flex-direction:column;
        gap:14px;
      }

      .dash-center{
        position:static;
        transform:none;
      }

      .page-wrap{
        width:92%;
      }

      .fruits-grid{
        grid-template-columns:repeat(2, minmax(170px, 1fr));
      }

      .selected-layout{
        grid-template-columns:1fr;
      }

      .selected-card-holder .fruit-card{
        max-width:260px;
        position:static;
      }

      .details-card{
        grid-template-columns:1fr;
      }
    }

    @media (max-width: 600px){
      .page-wrap{
        width:95%;
      }

      .hero-box h2{
        font-size:30px;
      }

      .hero-box p{
        font-size:15px;
      }

      .section-title h3{
        font-size:30px;
      }

      .section-title p{
        font-size:15px;
      }

      .fruits-grid{
        grid-template-columns:1fr;
      }

      .fruit-name{
        font-size:20px;
      }

      .details-title{
        font-size:28px;
      }

      .details-subtitle{
        font-size:18px;
      }

      .spelling-word img{
        width:56px;
        height:56px;
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
            <img src="../../logo.png" alt="logo">

          </a>
        </div>

        <div class="dash-center">
          <nav class="dash-nav">
            <a href="../../auth/children.php" class="circle-icon" tabindex="0" aria-label="العودة إلى الصفحة الرئيسية">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/children.mp4" type="video/mp4">
              </video>
            </a>

            <a href="../../subjects/general/food.php" class="circle-icon" tabindex="0" aria-label="الثقافة العامة">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/vegan.mp4" type="video/mp4">
              </video>
            </a>
          </nav>
        </div>

        <div class="dash-end">
          <div class="profile-wrap">
            <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
              <div class="profile-video-box">
                <video autoplay muted loop playsinline>
                  <source src="../../assets/icons/profile.mp4" type="video/mp4">
                </video>
              </div>
              <span class="profile-hello">مرحباً <?php echo htmlspecialchars($child_display_name); ?></span>
            </button>

            <div class="profile-menu" id="profileMenu">
              <a href="../../auth/account_settings.php">إعدادات الحساب</a>
              <a href="../../auth/logout.php">تسجيل الخروج</a>
            </div>
          </div>
        </div>

      </div>
    </header>
  </div>

  <main class="page-wrap">

    <section class="fruits-section">
      <div class="section-title">
        <h3>اختر الفاكهة</h3>
        <p>عند الضغط على البطاقة ستظهر التفاصيل كاملة</p>
      </div>

      <div class="cards-area">

        <div class="fruits-grid" id="fruitsGrid"></div>

        <div class="selected-layout" id="selectedLayout">
          <div class="selected-card-holder" id="selectedCardHolder"></div>

          <div class="details-card">
            <div class="details-info">
              <div class="details-title" id="detailsTitle"></div>
              <div class="details-subtitle" id="detailsSubtitle"></div>
              <div class="spelling-row" id="spellingRow"></div>
            </div>

            <div class="sign-video-box">
              <video id="detailsVideo" playsinline>
                <source id="detailsVideoSource" src="" type="video/mp4">
              </video>
              <div class="video-play" id="detailsPlayBtn">▶</div>
            </div>
          </div>
        </div>

      </div>
    </section>

  </main>

  <script>
    const profileBtn = document.getElementById("profileBtn");
    const profileWrap = document.querySelector(".profile-wrap");

    if (profileBtn && profileWrap) {
      profileBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        profileWrap.classList.toggle("open");
      });

      document.addEventListener("click", function(e) {
        if (!profileWrap.contains(e.target)) {
          profileWrap.classList.remove("open");
        }
      });
    }

    const fruits = <?php echo json_encode($itemsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const arabicLetterImages = {
      "ا": "../../images/signs/arabic/1.png",
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

    const fruitsGrid = document.getElementById("fruitsGrid");
    const selectedLayout = document.getElementById("selectedLayout");
    const selectedCardHolder = document.getElementById("selectedCardHolder");
    const detailsTitle = document.getElementById("detailsTitle");
    const detailsSubtitle = document.getElementById("detailsSubtitle");
    const spellingRow = document.getElementById("spellingRow");
    const detailsVideo = document.getElementById("detailsVideo");
    const detailsVideoSource = document.getElementById("detailsVideoSource");
    const detailsPlayBtn = document.getElementById("detailsPlayBtn");

    let activeKey = null;

    function createCard(item) {
      const card = document.createElement("div");
      card.className = "fruit-card";
      if (activeKey === item.key) card.classList.add("active");

      card.innerHTML = `
        <div class="fruit-image-wrap">
          <img src="${item.image}" alt="${item.name}">
        </div>
        <div class="fruit-name">${item.name}</div>
      `;

      card.addEventListener("click", () => toggleItem(item));
      return card;
    }

    function renderGrid() {
      fruitsGrid.innerHTML = "";

      fruits.forEach(item => {
        const card = createCard(item);
        fruitsGrid.appendChild(card);
      });
    }

    function normalizeLetter(letter) {
      const map = {
        "أ": "ا",
        "إ": "ا",
        "آ": "ا",
        "ؤ": "و",
        "ئ": "ي",
        "ة": "ة",
        "ى": "ى"
      };
      return map[letter] || letter;
    }

    function renderSpelling(name) {
      spellingRow.innerHTML = "";

      const words = name.trim().split(/\s+/);

      words.forEach(word => {
        const wordRow = document.createElement("div");
        wordRow.className = "spelling-word";

        [...word].forEach(letter => {
          const normalizedLetter = normalizeLetter(letter);
          const src = arabicLetterImages[normalizedLetter];
          if (!src) return;

          const img = document.createElement("img");
          img.src = src;
          img.alt = letter;
          wordRow.appendChild(img);
        });

        spellingRow.appendChild(wordRow);
      });
    }

    function showDetails(item) {
      fruitsGrid.style.display = "none";
      selectedLayout.classList.add("show");

      selectedCardHolder.innerHTML = "";
      const selectedCard = createCard(item);
      selectedCardHolder.appendChild(selectedCard);

      detailsTitle.textContent = item.name;
      renderSpelling(item.name);

      detailsVideo.pause();
      detailsVideoSource.src = item.video;
      detailsVideo.load();
      detailsPlayBtn.style.display = "flex";
    }

    function hideDetails() {
      activeKey = null;
      selectedLayout.classList.remove("show");
      fruitsGrid.style.display = "grid";
      renderGrid();

      detailsVideo.pause();
      detailsVideo.currentTime = 0;
      detailsPlayBtn.style.display = "flex";
    }

    function toggleItem(item) {
      if (activeKey === item.key) {
        hideDetails();
        return;
      }

      activeKey = item.key;
      renderGrid();
      showDetails(item);
    }

    detailsPlayBtn.addEventListener("click", function() {
      detailsVideo.play();
      detailsPlayBtn.style.display = "none";
    });

    detailsVideo.addEventListener("click", function() {
      if (detailsVideo.paused) {
        detailsVideo.play();
        detailsPlayBtn.style.display = "none";
      } else {
        detailsVideo.pause();
        detailsPlayBtn.style.display = "flex";
      }
    });

    detailsVideo.addEventListener("ended", function() {
      detailsPlayBtn.style.display = "flex";
    });

    renderGrid();
  </script>

</body>
</html>
