<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/session_child.php';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
$lang = $_SESSION['lang'] ?? 'ar';
// ✅ تسجيل زيارة قسم الطعام
require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');
$__uid = intval($_SESSION['user_id']);
$__cn  = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS progress (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, child_name VARCHAR(255) NULL, activity_type VARCHAR(20) NOT NULL, activity_key VARCHAR(100) NOT NULL, activity_label VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$__st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,'section','section-فواكه وخضراوات','فواكه وخضراوات') ON DUPLICATE KEY UPDATE activity_label=VALUES(activity_label), created_at=CURRENT_TIMESTAMP");
if ($__st) { mysqli_stmt_bind_param($__st, 'is', $__uid, $__cn); mysqli_stmt_execute($__st); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>الفواكه والخضار</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    :root{
      --bg-main:#f2edf7;
      --header-wrap:#eadff1;
      --header-bg:#cfe2ee;
      --hero-bg:#efe7f1;
      --section-bg:#d9e2bf;
      --blue:#1f5f9a;
      --text:#62748d;
      --yellow:#f1cd43;
      --yellow-soft:#ead78d;
      --white:#fff;
      --shadow:0 10px 24px rgba(99, 91, 136, .08);
      --shadow-hover:0 14px 28px rgba(99, 91, 136, .12);
      --r-xl:32px;
      --r-lg:24px;
      --r-md:18px;
      --r-full:999px;
    }

    body{
      font-family:"Cairo", sans-serif;
      background:var(--bg-main);
      color:#334;
      overflow-x:hidden;
    }

    a{
      text-decoration:none;
      color:inherit;
    }

    img,video{
      display:block;
      max-width:100%;
    }

    /* ================= HEADER ================= */
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
      overflow:visible !important;
      box-shadow:0 6px 16px rgba(0,0,0,.05);
      transition:.2s ease;
      position:relative;
      flex-shrink:0;
    }

    .circle-icon:hover{
      transform:translateY(-2px);
    }

    .circle-icon video,
    .circle-icon img{
      width:90%;
      height:90%;
      object-fit:cover;
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

    .profile-wrap{
      position:relative;
    }

    .profile-btn{
      border:none;
      background:#ead9a2;
      border-radius:var(--r-full);
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
      z-index:1200;
    }

    .profile-menu.show{
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

    /* ================= PAGE ================= */
    .page-wrap{
      width:78%;
      max-width:1280px;
      margin:30px auto 34px;
      transition:transform .25s ease;
    }

    /* ================= HERO ================= */
    .hero-box{
      background:var(--hero-bg);
      border-radius:34px;
      padding:34px 42px;
      box-shadow:var(--shadow);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:44px;
      position:relative;
      overflow:hidden;
      min-height:360px;
    }

    .hero-box::before{
      content:"";
      position:absolute;
      width:110px;
      height:110px;
      left:-8px;
      bottom:-8px;
      border-radius:28px;
      background:rgba(204,218,241,.55);
    }

    .hero-box::after{
      content:"";
      position:absolute;
      width:150px;
      height:150px;
      right:24px;
      top:0;
      border-radius:0 0 30px 30px;
      background:rgba(245,214,226,.42);
    }

    .hero-sign-video{
      width:230px;
      height:300px;
      border-radius:24px;
      overflow:hidden;
      position:relative;
      background:#cfd3d8;
      flex-shrink:0;
      box-shadow:0 10px 22px rgba(0,0,0,.08);
      z-index:1;
    }

    .hero-sign-video video{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .play-btn{
      position:absolute;
      top:50%;
      left:50%;
      transform:translate(-50%,-50%);
      width:72px;
      height:72px;
      border-radius:50%;
      background:rgba(157,74,100,.72);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:30px;
      cursor:pointer;
      z-index:2;
    }

    .hero-sign-video.playing .play-btn{
      display:none !important;
    }

    .hero-text{
      flex:1;
      position:relative;
      z-index:1;
      display:flex;
      flex-direction:column;
      align-items:flex-start;
      justify-content:center;
    }

    .small-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:var(--yellow-soft);
      color:#6f5d17;
      border-radius:999px;
      padding:8px 16px;
      font-size:15px;
      font-weight:800;
      margin-bottom:16px;
    }

    .hero-text h2{
      font-size:46px;
      line-height:1.2;
      color:var(--blue);
      font-weight:900;
      margin-bottom:10px;
    }

    .hero-text p{
      font-size:18px;
      line-height:1.9;
      color:#7d8ca1;
      font-weight:600;
      margin-bottom:18px;
      max-width:600px;
    }

    .start-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:var(--yellow);
      color:#604d00;
      font-size:17px;
      font-weight:900;
      padding:12px 24px;
      border-radius:999px;
      box-shadow:0 10px 22px rgba(241,205,67,.28);
      transition:.2s ease;
    }

    .start-btn:hover{
      transform:translateY(-2px);
    }

    /* ================= SECTION ================= */
    .choice-section{
      background:var(--section-bg);
      border-radius:32px;
      padding:34px 24px 28px;
      box-shadow:var(--shadow);
      margin-top:28px;
      text-align:center;
    }

    .choice-section h3{
      font-size:42px;
      line-height:1.2;
      color:var(--blue);
      font-weight:900;
      margin-bottom:4px;
    }

    .choice-section > p{
      font-size:18px;
      color:#8a95a7;
      font-weight:700;
      margin-bottom:22px;
    }

    .choice-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(260px, 1fr));
      gap:24px;
      max-width:920px;
      margin:0 auto;
    }

    .choice-card{
      position:relative;
      border-radius:28px;
      padding:24px 18px 20px;
      box-shadow:0 10px 22px rgba(0,0,0,.05);
      transition:.22s ease;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:flex-start;
      min-height:260px;
      overflow:hidden;
    }

    .choice-card:hover{
      transform:translateY(-4px);
      box-shadow:var(--shadow-hover);
    }

    .vegetables-card{
      background:linear-gradient(135deg,#e3f4e7,#d0ead4);
    }

    .fruits-card{
      background:linear-gradient(135deg,#f9dfe8,#f3d2df);
    }

    .choice-circle{
      width:96px;
      height:96px;
      border-radius:50%;
      background:rgba(255,255,255,.92);
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 8px 18px rgba(0,0,0,.07);
      margin-bottom:14px;
      overflow:hidden;
    }

    .choice-circle video{
      width:72%;
      height:72%;
      object-fit:contain;
      border-radius:50%;
    }

    .choice-card h4{
      font-size:26px;
      color:var(--blue);
      font-weight:900;
      margin-bottom:8px;
    }

    .choice-card p{
      font-size:16px;
      line-height:1.8;
      color:#6c7a8f;
      font-weight:700;
      margin:0 0 14px;
      max-width:360px;
    }

    .choice-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:10px 22px;
      border-radius:999px;
      background:#fff;
      color:#5f6d83;
      font-weight:900;
      font-size:15px;
      box-shadow:0 8px 16px rgba(0,0,0,.06);
    }

    /* ================= SIGN LANGUAGE BUTTON ================= */
    .sign-floating{
      position:fixed !important;
      left:22px !important;
      bottom:22px !important;
      z-index:2147483647 !important;
      display:block !important;
      pointer-events:auto !important;
    }

    .sign-circle{
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
      position:relative;
      z-index:2147483647;
      transition:.25s ease;
    }

    .sign-circle.active{
      box-shadow:
        0 0 0 8px rgba(50,210,123,.25),
        0 0 28px rgba(50,210,123,.9),
        0 10px 25px rgba(0,0,0,.25);
      transform:scale(1.08);
    }

    .sign-circle:hover{
      transform:scale(1.06);
    }

    .sign-circle.active:hover{
      transform:scale(1.08);
    }

    .sign-circle img{
      width:100%;
      height:100%;
      object-fit:contain;
      display:block;
    }

    .sign-video-box{
      position:absolute;
      left:0;
      bottom:95px;
      width:260px;
      height:auto;
      background:white;
      border-radius:24px;
      padding:8px;
      display:none;
      box-shadow:0 18px 45px rgba(0,0,0,.28);
      z-index:2147483647;
    }

    .sign-video-box.active{
      display:block !important;
    }

    .sign-helper-text{
      width:100%;
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
      display:none;
    }

    .sign-video-wrap{
      position:relative;
      width:100%;
      height:260px;
    }

    .sign-video-box video{
      width:100%;
      height:100%;
      border-radius:18px;
      object-fit:contain;
      background:#000;
    }

    .close-sign{
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
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      z-index:9999;
      box-shadow:0 8px 20px rgba(0,0,0,0.25);
    }

    .sign-play-btn{
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
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:5;
      box-shadow:0 10px 24px rgba(0,0,0,.25);
    }

    .sign-play-btn.hide{
      display:none !important;
    }

    body.sign-open .page-wrap{
      transform:translateX(120px);
    }

    body.sign-open .hero-box,
    body.sign-open .choice-section{
      max-width:calc(100% - 120px);
    }

    /* ================= RESPONSIVE ================= */
    @media (max-width: 1200px){
      .page-wrap{
        width:88%;
      }

      .hero-text h2{
        font-size:38px;
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

      .hero-box{
        padding:26px 24px;
        gap:28px;
      }
    }

    @media (max-width: 820px){
      .hero-box{
        flex-direction:column;
        text-align:center;
        min-height:auto;
      }

      .hero-text{
        align-items:center;
      }

      .hero-sign-video{
        width:220px;
        height:285px;
      }

      .choice-grid{
        grid-template-columns:1fr;
      }
    }

    @media (max-width: 600px){
      .page-shell{ width:98%; padding:10px 10px 0; }
      .children-dash{ padding:14px 12px; }
      .logo-box, .profile-btn{ min-width:auto; }
      .profile-hello{ font-size:15px; }
      .circle-icon{ width:58px; height:58px; }
      .page-wrap{ width:94%; margin:22px auto 26px; }
      .hero-box{ padding:22px 16px; border-radius:26px; }
      .hero-text h2{ font-size:29px; }
      .hero-text p{ font-size:15px; }
      .choice-section{ padding:26px 16px; }
      .choice-section h3{ font-size:30px; }
      .choice-section > p{ font-size:15px; }
      .choice-card h4{ font-size:23px; }
      .choice-card p{ font-size:14px; }
      .profile-menu{ left:50%; transform:translateX(-50%); }
    }

    .tap-highlighted { outline:3px solid #32d27b !important; outline-offset:3px !important; }

    @media (max-width: 768px) {
      .page-shell {
        width:100% !important; margin:0 !important; padding:6px 8px 0 !important;
        border-radius:0 !important; position:sticky !important; top:0 !important; z-index:999 !important;
      }

      .children-dash { border-radius:16px !important; padding:8px 12px !important; }

      .children-dash-inner {
        flex-direction:row !important; min-height:54px !important;
        gap:6px !important; justify-content:space-between !important;
      }

      .dash-center { position:static !important; transform:none !important; }

      .logo-box img { width:58px !important; }

      .circle-icon { width:46px !important; height:46px !important; }

      .profile-btn { min-width:unset !important; padding:5px 10px 5px 8px !important; }

      .profile-hello { font-size:12px !important; white-space:nowrap !important; }

      .page-wrap {
        width:100% !important; max-width:100% !important;
        margin:8px auto 16px !important; padding:0 8px !important;
      }

      body.sign-open .page-wrap { transform:none !important; }
      body.sign-open .hero-box,
      body.sign-open .choice-section { max-width:100% !important; }

      .hero-box {
        flex-direction:column !important; text-align:center !important;
        min-height:unset !important; padding:18px 14px !important; gap:16px !important;
      }

      .hero-text { align-items:center !important; }
      .hero-text h2 { font-size:26px !important; }
      .hero-text p { font-size:14px !important; }

      .hero-sign-video { width:100% !important; max-width:300px !important; height:200px !important; }
      .hero-sign-video video { object-fit:contain !important; background:#cfd3d8 !important; }

      .choice-grid { grid-template-columns:1fr !important; gap:14px !important; }

      .sign-floating { left:12px !important; bottom:12px !important; touch-action:manipulation !important; }
      .sign-circle { width:52px !important; height:52px !important; padding:6px !important; touch-action:manipulation !important; }
      .sign-video-box { width:220px !important; bottom:65px !important; }
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
            <a href="../../auth/children.php"
               class="circle-icon"
               tabindex="0"
               aria-label="العودة إلى صفحة الطفل"
               data-dashboard-video="../../assets/videos/home-icon-sign.mp4"
               data-dashboard-text="صفحة الطفل">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/children.mp4" type="video/mp4">
              </video>
              <span class="nav-text">صفحة الطفل</span>
            </a>

            <a href="../../subjects/general/general.php"
               class="circle-icon"
               tabindex="0"
               aria-label="الثقافة العامة"
               data-dashboard-video="../../assets/videos/general-sign.mp4"
               data-dashboard-text="الثقافة العامة">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/general.mp4" type="video/mp4">
              </video>
              <span class="nav-text">الثقافة العامة</span>
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

    <section class="hero-box" id="heroBox">
      <div class="hero-text">
        <span class="small-badge">هيا نتعلم</span>
        <h2>قسم الفواكه والخضار</h2>
        <p>تعلم أسماء الفواكه والخضار بطريقة بصرية ممتعة ومناسبة للأطفال</p>
        <a href="#sections" class="start-btn">ابدأ الآن</a>
      </div>

      <div class="hero-sign-video" id="heroSignBox">
        <video id="heroIntroVideo" muted playsinline preload="auto">
          <source src="../../images/videos/food-intro.mp4" type="video/mp4">
        </video>
        <div class="play-btn" id="playBtn">▶</div>
      </div>
    </section>

    <section class="choice-section"
             id="sections"
             data-section-video="../../assets/videos/general-choose-section.mp4"
             data-section-text="اختر القسم">

      <h3>اختر القسم</h3>
      <p>اضغط على أي قسم لتبدأ التعلم</p>

      <div class="choice-grid">

        <a href="vegetables.php"
           class="choice-card vegetables-card"
           data-card-video="../../assets/videos/vegetables-sign.mp4"
           data-card-text="قسم الخضار">
          <div class="choice-circle">
            <video autoplay muted loop playsinline>
              <source src="../../assets/icons/vegetables.mp4" type="video/mp4">
            </video>
          </div>
          <h4>قسم الخضار</h4>
          <p>تعلم أسماء الخضار المفيدة بطريقة سهلة ومرحة للأطفال</p>
          <span class="choice-btn">ابدأ التعلم</span>
        </a>

        <a href="fruits.php"
           class="choice-card fruits-card"
           data-card-video="../../assets/videos/fruits-sign.mp4"
           data-card-text="قسم الفواكه">
          <div class="choice-circle">
            <video autoplay muted loop playsinline>
              <source src="../../assets/icons/fruits.mp4" type="video/mp4">
            </video>
          </div>
          <h4>قسم الفواكه</h4>
          <p>تعلم أسماء الفواكه اللذيذة بألوان جميلة وأسلوب ممتع</p>
          <span class="choice-btn">ابدأ التعلم</span>
        </a>

      </div>
    </section>

  </main>

  <div class="sign-floating">
    <button class="sign-circle" id="signToggleBtn" type="button" title="لغة الإشارة">
      <img src="../../assets/icons/sign-icon.png" class="sign-icon" alt="لغة الإشارة">
    </button>

    <div class="sign-video-box" id="signVideoBox">
      <button class="close-sign" id="closeSignBtn" type="button">×</button>

      <div id="signHelperText" class="sign-helper-text"></div>

      <div class="sign-video-wrap">
        <video id="signHelpVideo" playsinline muted preload="auto"></video>
        <button class="sign-play-btn" id="signPlayBtn" type="button">▶</button>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function () {

    /* ================= VIDEO CACHE BUSTING ================= */
    // يمنع المتصفح من عرض نسخة الفيديو القديمة عند استبدال الملف بنفس الاسم
    document.querySelectorAll("video").forEach(function (video) {
        video.querySelectorAll("source").forEach(function (source) {
            const originalSrc = source.getAttribute("src");
            if (originalSrc && !originalSrc.startsWith("blob:")) {
                source.setAttribute("src", originalSrc.split("?")[0] + "?v=" + Date.now());
            }
        });

        const directSrc = video.getAttribute("src");
        if (directSrc && !directSrc.startsWith("blob:")) {
            video.setAttribute("src", directSrc.split("?")[0] + "?v=" + Date.now());
        }
        video.load();
    });
    const profileBtn = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");

    if (profileBtn && profileMenu) {
      profileBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        profileMenu.classList.toggle("show");
      });

      document.addEventListener("click", function (e) {
        if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
          profileMenu.classList.remove("show");
        }
      });
    }

    const signToggleBtn = document.getElementById("signToggleBtn");
    const signVideoBox = document.getElementById("signVideoBox");
    const closeSignBtn = document.getElementById("closeSignBtn");
    const signHelpVideo = document.getElementById("signHelpVideo");
    const signHelperText = document.getElementById("signHelperText");
    const signPlayBtn = document.getElementById("signPlayBtn");

    const heroBox = document.getElementById("heroBox");
    const heroIntroVideo = document.getElementById("heroIntroVideo");
    const heroSignBox = document.getElementById("heroSignBox");
    const playBtn = document.getElementById("playBtn");
    const choiceSection = document.getElementById("sections");

    let helpOpen = false;
    let lastHelpVideo = "";
    let currentHelpType = "";
    let chooseSectionOpened = false;

    function isHelpOpen() {
      return helpOpen;
    }

    function showHelpText(text) {
      if (!signHelperText) return;
      signHelperText.textContent = text || "";
      signHelperText.style.display = text ? "block" : "none";
    }

    function showSignPlay() {
      if (signPlayBtn) signPlayBtn.classList.remove("hide");
    }

    function hideSignPlay() {
      if (signPlayBtn) signPlayBtn.classList.add("hide");
    }

    function loadHelpVideo(videoPath, text, type) {
        if (!isHelpOpen() || !videoPath) return;

        signVideoBox.classList.add("active");
        showHelpText(text);
        currentHelpType = type || "";

        const freshVideoPath = videoPath.split("?")[0] + "?v=" + Date.now();
        signHelpVideo.pause();
        signHelpVideo.removeAttribute("src");
        signHelpVideo.src = freshVideoPath;
        signHelpVideo.load();
        lastHelpVideo = videoPath;
        signHelpVideo.currentTime = 0;
        showSignPlay();
    }

    function playHelpVideo(videoPath, text, type) {
        if (!isHelpOpen() || !videoPath) return;

        signVideoBox.classList.add("active");
        showHelpText(text);
        currentHelpType = type || "";

        const freshVideoPath = videoPath.split("?")[0] + "?v=" + Date.now();
        signHelpVideo.pause();
        signHelpVideo.removeAttribute("src");
        signHelpVideo.src = freshVideoPath;
        signHelpVideo.load();
        lastHelpVideo = videoPath;
        signHelpVideo.currentTime = 0;
        signHelpVideo.muted = true;

        signHelpVideo.play().then(function () {
            hideSignPlay();
            if (currentHelpType === "section") {
                chooseSectionOpened = true;
            }
        }).catch(function () {
            showSignPlay();
        });
    }

    function stopHelpVideo(keepBox) {
      signHelpVideo.pause();

      if (!keepBox) {
        signHelpVideo.currentTime = 0;
        signVideoBox.classList.remove("active");
        showHelpText("");
        currentHelpType = "";
      }

      showSignPlay();
    }

   function openHelpBox() {
  fetch('../../api/log-help.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'page=' + encodeURIComponent(window.location.pathname)
  }).catch(function(){});

  helpOpen = true;
  signToggleBtn.classList.add("active");

  /* لا نضيف sign-open حتى ما تنزاح البطاقات */
  // document.body.classList.add("sign-open");

  signVideoBox.classList.remove("active");
  showHelpText("");
  signHelpVideo.pause();
  signHelpVideo.removeAttribute("src");
  signHelpVideo.load();

  lastHelpVideo = "";
  currentHelpType = "";
  showSignPlay();

  checkChoiceSectionVisible();
}

    function closeHelpBox() {
      helpOpen = false;
      signToggleBtn.classList.remove("active");
      document.body.classList.remove("sign-open");

      signVideoBox.classList.remove("active");
      showHelpText("");
      signHelpVideo.pause();
      signHelpVideo.currentTime = 0;
      signHelpVideo.removeAttribute("src");
      signHelpVideo.load();

      lastHelpVideo = "";
      currentHelpType = "";
      chooseSectionOpened = false;
      showSignPlay();
    }

    signToggleBtn.addEventListener("click", function () {
      if (helpOpen) {
        closeHelpBox();
      } else {
        openHelpBox();
      }
    });

    closeSignBtn.addEventListener("click", closeHelpBox);

    signPlayBtn.addEventListener("click", function () {
      if (!signHelpVideo.src) return;

      signHelpVideo.play().then(function () {
        hideSignPlay();

        if (currentHelpType === "section") {
          chooseSectionOpened = true;
        }
      }).catch(function () {
        showSignPlay();
      });
    });

    signHelpVideo.addEventListener("play", function () {
      hideSignPlay();

      if (currentHelpType === "section") {
        chooseSectionOpened = true;
      }
    });

    signHelpVideo.addEventListener("ended", function () {
      showSignPlay();
    });

    signHelpVideo.addEventListener("pause", function () {
      if (!signHelpVideo.ended) {
        showSignPlay();
      }
    });

    document.querySelectorAll("[data-dashboard-video]").forEach(function (item) {
      item.addEventListener("mouseenter", function () {
        playHelpVideo(item.dataset.dashboardVideo, item.dataset.dashboardText || "", "dashboard");
      });

      item.addEventListener("focus", function () {
        playHelpVideo(item.dataset.dashboardVideo, item.dataset.dashboardText || "", "dashboard");
      });

      item.addEventListener("mouseleave", function () {
        if (isHelpOpen()) stopHelpVideo(false);
      });

      item.addEventListener("blur", function () {
        if (isHelpOpen()) stopHelpVideo(false);
      });
    });

    function checkChoiceSectionVisible() {
      if (!choiceSection || !isHelpOpen() || chooseSectionOpened) return;

      const rect = choiceSection.getBoundingClientRect();
      const windowHeight = window.innerHeight || document.documentElement.clientHeight;
      const sectionIsVisible = rect.top < windowHeight * 0.75 && rect.bottom > windowHeight * 0.25;

      if (sectionIsVisible) {
        loadHelpVideo(
          choiceSection.dataset.sectionVideo,
          choiceSection.dataset.sectionText || "اختر القسم",
          "section"
        );
      }
    }

    window.addEventListener("scroll", checkChoiceSectionVisible);
    window.addEventListener("resize", checkChoiceSectionVisible);

    if (choiceSection) {
      choiceSection.addEventListener("mouseenter", function () {
        if (!chooseSectionOpened) {
          loadHelpVideo(
            choiceSection.dataset.sectionVideo,
            choiceSection.dataset.sectionText || "اختر القسم",
            "section"
          );
        }
      });
    }

    document.querySelectorAll("[data-card-video]").forEach(function (card) {
      card.addEventListener("mouseenter", function () {
        if (!chooseSectionOpened) return;
        playHelpVideo(card.dataset.cardVideo, card.dataset.cardText || "", "card");
      });

      card.addEventListener("focus", function () {
        if (!chooseSectionOpened) return;
        playHelpVideo(card.dataset.cardVideo, card.dataset.cardText || "", "card");
      });

      card.addEventListener("mouseleave", function () {
        if (isHelpOpen() && chooseSectionOpened) stopHelpVideo(false);
      });

      card.addEventListener("blur", function () {
        if (isHelpOpen() && chooseSectionOpened) stopHelpVideo(false);
      });
    });

    function hideHeroPlay() {
      if (playBtn) playBtn.style.display = "none";
      if (heroSignBox) heroSignBox.classList.add("playing");
    }

    function showHeroPlay() {
      if (playBtn) playBtn.style.display = "flex";
      if (heroSignBox) heroSignBox.classList.remove("playing");
    }

    function playHeroVideo() {
      if (!heroIntroVideo) return;

      heroIntroVideo.play().then(function () {
        hideHeroPlay();
      }).catch(function () {
        showHeroPlay();
      });
    }

    function stopHeroVideo(reset) {
      if (!heroIntroVideo) return;

      heroIntroVideo.pause();

      if (reset) {
        heroIntroVideo.currentTime = 0;
      }

      showHeroPlay();
    }

    if (heroBox && heroIntroVideo) {
      heroBox.addEventListener("mouseenter", function () {
        if (isHelpOpen()) {
          playHeroVideo();
        }
      });

      heroBox.addEventListener("mouseleave", function () {
        if (isHelpOpen()) {
          stopHeroVideo(true);
        }
      });
    }

    if (playBtn && heroIntroVideo) {
      playBtn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (heroIntroVideo.paused) {
          playHeroVideo();
        } else {
          stopHeroVideo(false);
        }
      });
    }

    if (heroIntroVideo) {
      heroIntroVideo.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (heroIntroVideo.paused) {
          playHeroVideo();
        } else {
          stopHeroVideo(false);
        }
      });

      heroIntroVideo.addEventListener("ended", function () {
        stopHeroVideo(true);
      });
    }
  });
  </script>

</body>
</html>
