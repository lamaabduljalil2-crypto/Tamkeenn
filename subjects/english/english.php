
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

require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

/* =========================================================
   منع كاش الصفحة بالكامل
   ========================================================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* =========================================================
   رقم جديد لكاش الفيديوهات في كل تحميل للصفحة
   ========================================================= */
$video_cache_version = time();

/* =========================================================
   دالة إضافة نسخة للكاش لأي ملف فيديو
   ========================================================= */
function videoCacheUrl(string $url): string
{
    global $video_cache_version;

    if ($url === '') {
        return $url;
    }

    $separator = (strpos($url, '?') !== false) ? '&' : '?';

    return $url . $separator . 'v=' . $video_cache_version;
}

/* ---- جلب الأيقونات المخصصة للغة الإنجليزية ---- */
$enCustomIcons = [];

$_stmt = mysqli_prepare(
    $conn,
    "SELECT type_name, icon_file FROM type_icons WHERE subject='اللغة الإنجليزية'"
);

if ($_stmt) {
    mysqli_stmt_execute($_stmt);
    $_res = mysqli_stmt_get_result($_stmt);

    while ($_r = mysqli_fetch_assoc($_res)) {
        $iconPath = '../../' . ltrim($_r['icon_file'], '/');

        $enCustomIcons[$_r['type_name']] = $iconPath;
    }

    mysqli_stmt_close($_stmt);
}

/* =========================================================
   دالة مساعدة: تعيد HTML الأيقونة
   مخصصة أو افتراضية + كاش جديد
   ========================================================= */
function childCircleIcon(string $type, array $customIcons, string $defaultSrc): string
{
    if (isset($customIcons[$type])) {

        $url = $customIcons[$type];

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        $freshUrl = videoCacheUrl($url);

        if (in_array($ext, ['mp4', 'webm'], true)) {

            $mime = ($ext === 'webm') ? 'video/webm' : 'video/mp4';

            return '<video autoplay muted loop playsinline class="circle-video">
                        <source src="' . htmlspecialchars($freshUrl, ENT_QUOTES, 'UTF-8') . '" type="' . $mime . '">
                    </video>';
        }

        return '<img src="' . htmlspecialchars($freshUrl, ENT_QUOTES, 'UTF-8') . '" class="circle-video" alt="">';
    }

    $freshDefault = videoCacheUrl($defaultSrc);

    return '<video autoplay muted loop playsinline class="circle-video">
                <source src="' . htmlspecialchars($freshDefault, ENT_QUOTES, 'UTF-8') . '" type="video/mp4">
            </video>';
}

/* =========================================================
   مسارات الفيديوهات الأساسية
   ========================================================= */
$dashboardHomeVideo = videoCacheUrl('../../assets/videos/kids-icon-sign.mp4');
$englishNavVideo    = videoCacheUrl('../../assets/videos/english-word.mp4');

$logoVideo = videoCacheUrl('../../images/videos/logo.mp4');

$profileVideo = videoCacheUrl('../../assets/icons/profile.mp4');

$settingsVideo = videoCacheUrl('../../assets/videos/settings-sign.mp4');
$logoutVideo   = videoCacheUrl('../../assets/videos/logout-sign.mp4');

$heroIntroVideo = videoCacheUrl('../../images/videos/english-intro.mp4');

$lettersCardVideo = videoCacheUrl('../../assets/videos/english-letters-sign.mp4');
$numbersCardVideo = videoCacheUrl('../../assets/videos/english-numbers-sign.mp4');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

  <meta charset="UTF-8" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />

  <title>اللغة الإنجليزية</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap"
    rel="stylesheet"
  >

  <link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=4" />

  <style>

    .choice-section {
      background: rgba(243, 255, 222, 0.92);
      border-radius: 30px;
      padding: 28px 22px 30px;
      box-shadow: var(--shadow);
    }

    .choice-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-top: 18px;
    }

    .choice-card {
      text-decoration: none;
      color: inherit;
      border-radius: 30px;
      padding: 30px 20px;
      text-align: center;
      box-shadow: 0 12px 24px rgba(70, 130, 180, 0.14);
      transition: transform .22s ease, box-shadow .22s ease;
      position: relative;
      overflow: hidden;
      border: 3px solid rgba(255,255,255,0.65);
      min-height: 240px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .choice-card::before {
      content: "";
      position: absolute;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      top: -25px;
      left: -20px;
      background: rgba(255,255,255,0.28);
    }

    .choice-card::after {
      content: "";
      position: absolute;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      bottom: -10px;
      right: -8px;
      background: rgba(255,255,255,0.22);
    }

    .choice-card:hover {
      transform: translateY(-7px) scale(1.02);
      box-shadow: 0 18px 30px rgba(70, 130, 180, 0.20);
    }

    .choice-card.letters-card {
      background: linear-gradient(180deg, #faffb6 0%, #faffb6 100%);
    }

    .choice-card.numbers-card {
      background: linear-gradient(180deg, #c1ffbd 0%, #c1ffbd 100%);
    }

    .choice-circle {
      width: 110px;
      height: 110px;
      margin: 0 auto 18px;
      border-radius: 50%;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      font-weight: 900;
      color: var(--blue-dark);
      box-shadow:
        0 8px 18px rgba(0,0,0,.08),
        inset 0 0 0 5px rgba(255,255,255,.75);
      position: relative;
      z-index: 1;
      overflow: hidden;
    }

    .circle-video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .choice-card h4 {
      margin: 0 0 10px;
      font-size: 28px;
      color: #21425f;
      font-weight: 900;
      position: relative;
      z-index: 1;
    }

    .choice-card p {
      margin: 0;
      font-size: 17px;
      color: #21425f;
      font-weight: 700;
      line-height: 1.8;
      position: relative;
      z-index: 1;
    }

    /* ===== MOBILE RESPONSIVE ===== */

    @media (max-width: 768px) {

      .children-dash-inner {
        grid-template-columns: auto 1fr auto !important;
        padding: 8px 12px !important;
        min-height: auto !important;
        gap: 8px !important;
      }

      .logo-box img {
        width: 44px !important;
        height: 44px !important;
      }

      .logo-text {
        display: none !important;
      }

      .dash-nav {
        gap: 12px !important;
        flex-wrap: nowrap !important;
        justify-content: center !important;
      }

      .circle-icon {
        width: 44px !important;
        height: 44px !important;
      }

      .profile-hello {
        font-size: 13px !important;
        max-width: 70px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .profile-video-box {
        width: 40px !important;
        height: 40px !important;
      }

      .profile-menu {
        left: 0 !important;
        right: auto !important;
        top: calc(100% + 6px) !important;
      }

      .hero-box {
        grid-template-columns: 1fr !important;
        text-align: center;
        padding: 22px 16px !important;
      }

      .hero-sign-video {
        justify-content: center;
        margin-left: 0;
      }

      .hero-sign-video video {
        width: 160px;
        height: 210px;
      }

      .hero-text {
        text-align: center;
      }

      .hero-text h2 {
        font-size: 24px;
      }

      .choice-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 14px !important;
      }

      .choice-card {
        padding: 20px 12px !important;
        min-height: 180px !important;
        border-radius: 22px !important;
      }

      .choice-circle {
        width: 80px !important;
        height: 80px !important;
      }

      .choice-card h4 {
        font-size: 20px !important;
      }

      .choice-card p {
        font-size: 14px !important;
      }
    }

    @media (max-width: 480px) {

      .choice-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
      }

      .choice-card {
        min-height: 140px !important;
        padding: 18px 16px !important;
      }
    }

    @media (max-width: 380px) {

      .hero-text h2 {
        font-size: 20px !important;
      }

      .choice-card h4 {
        font-size: 18px !important;
      }
    }

    @media (max-width: 768px) {

      .sign-floating {
        left: 12px !important;
        bottom: 12px !important;
      }

      .sign-circle {
        width: 52px !important;
        height: 52px !important;
        padding: 6px !important;
      }

      .sign-video-box {
        width: 200px !important;
        bottom: 68px !important;
      }
    }

    .circle-icon {
      position: relative;
      overflow: visible !important;
    }

    .nav-text {
      position: absolute;
      top: calc(100% + 8px);
      left: 50%;
      transform: translateX(-50%) translateY(-6px);
      opacity: 0;
      pointer-events: none;
      white-space: nowrap;
      background: linear-gradient(135deg, #ff7aa8, #ff5f8f);
      color: #fff;
      padding: 7px 16px;
      border-radius: 999px;
      font-size: 15px;
      font-weight: 900;
      transition: .22s ease;
      z-index: 50;
      box-shadow: 0 8px 18px rgba(255, 94, 143, 0.30);
    }

    .circle-icon:hover .nav-text {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .sign-floating {
      position: fixed !important;
      left: 22px !important;
      bottom: 22px !important;
      z-index: 2147483647 !important;
      display: block !important;
      pointer-events: auto !important;
    }

    .sign-circle {
      width: 75px;
      height: 75px;
      border-radius: 50%;
      border: 4px solid white;
      background: #32d27b;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 8px;
      box-shadow: 0 10px 25px rgba(0,0,0,.25);
      position: relative;
      z-index: 2147483647;
      transition: .25s ease;
    }

    .sign-circle.active {
      box-shadow:
        0 0 0 8px rgba(50, 210, 123, .25),
        0 0 28px rgba(50, 210, 123, .9),
        0 10px 25px rgba(0,0,0,.25);
      transform: scale(1.08);
    }

    .sign-circle:hover {
      transform: scale(1.06);
    }

    .sign-circle.active:hover {
      transform: scale(1.08);
    }

    .sign-circle img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .sign-video-box {
      position: absolute;
      left: 0;
      bottom: 95px;
      width: 260px;
      height: auto;
      background: white;
      border-radius: 24px;
      padding: 8px;
      display: none;
      box-shadow: 0 18px 45px rgba(0,0,0,.28);
      z-index: 2147483647;
    }

    .sign-video-box.active {
      display: block !important;
    }

    .sign-helper-text {
      width: 100%;
      text-align: center;
      font-family: Arial, sans-serif;
      font-size: 16px;
      font-weight: 900;
      color: #21425f;
      background: #eafff2;
      border: 2px solid #92efba;
      border-radius: 16px;
      padding: 8px 10px;
      margin-bottom: 8px;
      line-height: 1.5;
      display: none;
    }

    .sign-video-wrap {
      position: relative;
      width: 100%;
      height: 260px;
    }

    .sign-video-box video {
      width: 100%;
      height: 100%;
      border-radius: 18px;
      object-fit: contain;
      background: #000;
    }

    .close-sign {
      position: absolute;
      top: -20px;
      right: -20px;
      width: 55px;
      height: 55px;
      border-radius: 50%;
      border: 4px solid white;
      background: #ff3b3b;
      color: white;
      font-size: 26px;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 9999;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    }

    .sign-play-btn {
      position: absolute;
      inset: 0;
      margin: auto;
      width: 74px;
      height: 74px;
      border-radius: 50%;
      border: 4px solid white;
      background: rgba(255, 91, 120, 0.92);
      color: white;
      font-size: 34px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 5;
      box-shadow: 0 10px 24px rgba(0,0,0,.25);
    }

    .sign-play-btn.hide {
      display: none !important;
    }

    .hero-sign-video.playing .play-btn {
      display: none !important;
    }

    .circle-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
    }

    .circle-icon video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .circle-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

  </style>

</head>

<body>

<header class="children-dash">

  <div class="children-dash-inner">

    <div class="dash-start">

      <div class="dash-end">

        <a
          href="../../index.php"
          class="logo-box"
          tabindex="0"
          data-dashboard-video="<?php echo htmlspecialchars($logoVideo, ENT_QUOTES, 'UTF-8'); ?>"
          data-dashboard-text="شعار منصة تمكين"
        >

          <img src="../../logo.png" alt="logo">

        </a>

      </div>

    </div>


    <nav class="dash-nav">

      <a
        href="../../auth/children.php"
        class="circle-icon home-icon"
        tabindex="0"
        aria-label="<?php echo ($lang === 'ar') ? 'العودة إلى الصفحة الرئيسية' : 'Back to home page'; ?>"
        data-dashboard-video="<?php echo htmlspecialchars($dashboardHomeVideo, ENT_QUOTES, 'UTF-8'); ?>"
        data-dashboard-text="صفحة الطفل"
      >

        <video
          class="nav-icon-video"
          autoplay
          muted
          loop
          playsinline
          preload="auto"
        >

          <source
            src="<?php echo htmlspecialchars(videoCacheUrl('../../assets/icons/children.mp4'), ENT_QUOTES, 'UTF-8'); ?>"
            type="video/mp4"
          >

        </video>

        <span class="nav-text">صفحة الطفل</span>

      </a>


      <a
        href="../../subjects/english/english.php"
        class="circle-icon home-icon"
        tabindex="0"
        aria-label="قسم اللغة الإنجليزية"
        data-dashboard-video="<?php echo htmlspecialchars($englishNavVideo, ENT_QUOTES, 'UTF-8'); ?>"
        data-dashboard-text="اللغة الإنجليزية"
      >

        <img src="../../assets/icons/en-letter.png" alt="ENGLISH">

        <span class="nav-text">اللغة الإنجليزية</span>

      </a>

    </nav>


    <div class="profile-wrap">

      <button
        type="button"
        class="profile-btn"
        id="profileBtn"
        tabindex="0"
      >

        <span class="profile-hello">

          مرحباً <?php echo htmlspecialchars($child_display_name); ?>

        </span>

        <div class="profile-video-box">

          <video
            autoplay
            muted
            loop
            playsinline
            preload="auto"
          >

            <source
              src="<?php echo htmlspecialchars($profileVideo, ENT_QUOTES, 'UTF-8'); ?>"
              type="video/mp4"
            >

          </video>

        </div>

      </button>


      <div class="profile-menu" id="profileMenu">

        <a
          href="../../auth/account_settings.php"
          data-dashboard-video="<?php echo htmlspecialchars($settingsVideo, ENT_QUOTES, 'UTF-8'); ?>"
          data-dashboard-text="إعدادات الحساب"
        >
          إعدادات الحساب
        </a>

        <a
          href="../../auth/logout.php"
          data-dashboard-video="<?php echo htmlspecialchars($logoutVideo, ENT_QUOTES, 'UTF-8'); ?>"
          data-dashboard-text="تسجيل الخروج"
        >
          تسجيل الخروج
        </a>

      </div>

    </div>

  </div>

</header>


<main class="page-wrap">


<section class="hero-box" id="heroBox">

  <div class="hero-text">

    <span class="small-badge">هيا نتعلم</span>

    <h2>مرحباً بك في قسم اللغة الإنجليزية</h2>

    <p>
      اختر ما تريد تعلمه، ثم ابدأ رحلتك الممتعة مع الحروف الإنجليزية أو الأرقام الإنجليزية.
    </p>

    <a href="#englishChoices" class="start-btn">
      ابدأ الآن
    </a>

  </div>


  <div class="hero-sign-video" id="heroSignBox">

    <video
      id="heroIntroVideo"
      muted
      playsinline
      preload="auto"
    >

      <source
        src="<?php echo htmlspecialchars($heroIntroVideo, ENT_QUOTES, 'UTF-8'); ?>"
        type="video/mp4"
      >

    </video>

    <div class="play-btn" id="playBtn">▶</div>

  </div>

</section>


<section class="choice-section" id="englishChoices">

  <div class="section-title">

    <h3>اختر القسم</h3>

    <p>اضغط على القسم الذي تريد تعلمه</p>

  </div>


  <div class="choice-grid">


    <!-- الحروف الإنجليزية -->

    <a
      href="english-letters.php"
      class="choice-card letters-card"
      data-card-video="<?php echo htmlspecialchars($lettersCardVideo, ENT_QUOTES, 'UTF-8'); ?>"
      data-card-text="الحروف الإنجليزية"
    >

      <div class="choice-circle">

        <?=
          childCircleIcon(
            'الحروف الإنجليزية',
            $enCustomIcons,
            '../../assets/icons/letter.mp4'
          )
        ?>

      </div>

      <h4>الحروف الإنجليزية</h4>

      <p>
        تعلم الحروف الإنجليزية بطريقة سهلة وممتعة
      </p>

    </a>


    <!-- الأرقام الإنجليزية -->

    <a
      href="english-numbers.php"
      class="choice-card numbers-card"
      data-card-video="<?php echo htmlspecialchars($numbersCardVideo, ENT_QUOTES, 'UTF-8'); ?>"
      data-card-text="الأرقام الإنجليزية"
    >

      <div class="choice-circle">

        <?=
          childCircleIcon(
            'الأرقام الإنجليزية',
            $enCustomIcons,
            '../../assets/icons/numbers.mp4'
          )
        ?>

      </div>

      <h4>الأرقام الإنجليزية</h4>

      <p>
        تعلم الأرقام الإنجليزية بطريقة ممتعة وبسيطة
      </p>

    </a>


  </div>

</section>

</main>


<div class="sign-floating">

  <button
    class="sign-circle"
    id="signToggleBtn"
    type="button"
    title="لغة الإشارة"
  >

    <img
      src="../../assets/icons/sign-icon.png"
      class="sign-icon"
      alt="لغة الإشارة"
    >

  </button>


  <div
    class="sign-video-box"
    id="signVideoBox"
  >

    <button
      class="close-sign"
      id="closeSignBtn"
      type="button"
    >
      ×
    </button>


    <div
      id="signHelperText"
      class="sign-helper-text"
    ></div>


    <div class="sign-video-wrap">

      <video
        id="signHelpVideo"
        playsinline
        muted
        preload="auto"
      ></video>

      <button
        class="sign-play-btn"
        id="signPlayBtn"
        type="button"
      >
        ▶
      </button>

    </div>

  </div>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

  /* =====================================================
     دالة قوية لمنع كاش الفيديوهات في JavaScript
     ===================================================== */

  function freshVideoPath(videoPath) {

    if (!videoPath) {
      return videoPath;
    }

    let cleanPath = String(videoPath);

    /*
      إزالة أي v قديمة إذا كانت موجودة
    */
    cleanPath = cleanPath.replace(/([?&])v=[^&]*/g, "");

    /*
      تنظيف ? أو & الزائدة
    */
    cleanPath = cleanPath.replace(/[?&]+$/, "");

    const separator = cleanPath.includes("?") ? "&" : "?";

    return cleanPath + separator + "v=" + Date.now();
  }


  /* =====================================================
     تحديث كل فيديوهات الصفحة عند التحميل
     ===================================================== */

  document.querySelectorAll("video source").forEach(function (source) {

    const originalSrc = source.getAttribute("src");

    if (!originalSrc) {
      return;
    }

    source.setAttribute(
      "src",
      freshVideoPath(originalSrc)
    );

    const video = source.closest("video");

    if (video) {

      video.load();

    }

  });


  /* =====================================================
     تحديث فيديوهات الفيديو المباشر التي لها src
     ===================================================== */

  document.querySelectorAll("video[src]").forEach(function (video) {

    const originalSrc = video.getAttribute("src");

    if (!originalSrc) {
      return;
    }

    video.setAttribute(
      "src",
      freshVideoPath(originalSrc)
    );

    video.load();

  });


  /* =====================================================
     البروفايل
     ===================================================== */

  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");

  if (profileBtn && profileMenu) {

    profileBtn.addEventListener("click", function (e) {

      e.stopPropagation();

      profileMenu.classList.toggle("show");

    });


    document.addEventListener("click", function (e) {

      if (
        !profileBtn.contains(e.target) &&
        !profileMenu.contains(e.target)
      ) {

        profileMenu.classList.remove("show");

      }

    });

  }


  /* =====================================================
     عناصر فيديو المساعدة
     ===================================================== */

  const signToggleBtn = document.getElementById("signToggleBtn");
  const signVideoBox = document.getElementById("signVideoBox");
  const closeSignBtn = document.getElementById("closeSignBtn");
  const signHelpVideo = document.getElementById("signHelpVideo");
  const signHelperText = document.getElementById("signHelperText");
  const signPlayBtn = document.getElementById("signPlayBtn");


  /* =====================================================
     عناصر فيديو المقدمة
     ===================================================== */

  const heroBox = document.getElementById("heroBox");
  const heroIntroVideo = document.getElementById("heroIntroVideo");
  const heroSignBox = document.getElementById("heroSignBox");
  const playBtn = document.getElementById("playBtn");


  let helpOpen = false;
  let lastHelpVideo = "";


  function isHelpOpen() {

    return helpOpen;

  }


  function showHelpText(text) {

    signHelperText.textContent = text || "";

    signHelperText.style.display =
      text ? "block" : "none";

  }


  function showSignPlay() {

    signPlayBtn.classList.remove("hide");

  }


  function hideSignPlay() {

    signPlayBtn.classList.add("hide");

  }


  function stopHelpVideo() {

    if (!signHelpVideo) {
      return;
    }

    signHelpVideo.pause();

    try {
      signHelpVideo.currentTime = 0;
    } catch (e) {}

    signVideoBox.classList.remove("active");

    showHelpText("");

    showSignPlay();

  }


  /* =====================================================
     تشغيل فيديو المساعدة مع منع الكاش
     ===================================================== */

  function playHelpVideo(videoPath, text) {

    if (!isHelpOpen() || !videoPath) {
      return;
    }

    signVideoBox.classList.add("active");

    showHelpText(text);


    /*
      نضيف timestamp جديد دائماً.
      حتى لو كان نفس الفيديو ونفس الاسم،
      المتصفح سيطلب النسخة الجديدة.
    */

    const freshPath = freshVideoPath(videoPath);


    signHelpVideo.pause();

    signHelpVideo.removeAttribute("src");

    signHelpVideo.load();


    signHelpVideo.src = freshPath;

    signHelpVideo.load();


    lastHelpVideo = videoPath;


    try {
      signHelpVideo.currentTime = 0;
    } catch (e) {}


    signHelpVideo.muted = true;


    signHelpVideo.play().then(function () {

      hideSignPlay();

    }).catch(function () {

      showSignPlay();

    });

  }


  /* =====================================================
     فتح المساعدة
     ===================================================== */

  function openHelpBox() {

    fetch(
      '../../api/log-help.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body:
          'page=' +
          encodeURIComponent(window.location.pathname)
      }
    ).catch(function () {});


    helpOpen = true;

    signToggleBtn.classList.add("active");

    document.body.classList.add("sign-open");


    signVideoBox.classList.remove("active");

    showHelpText("");

    stopHelpVideo();

  }


  /* =====================================================
     إغلاق المساعدة
     ===================================================== */

  function closeHelpBox() {

    helpOpen = false;

    signVideoBox.classList.remove("active");

    signToggleBtn.classList.remove("active");

    document.body.classList.remove("sign-open");


    showHelpText("");

    stopHelpVideo();

  }


  signToggleBtn.addEventListener(
    "click",
    function () {

      if (helpOpen) {

        closeHelpBox();

      } else {

        openHelpBox();

      }

    }
  );


  closeSignBtn.addEventListener(
    "click",
    closeHelpBox
  );


  signPlayBtn.addEventListener(
    "click",
    function () {

      if (!signHelpVideo.src) {
        return;
      }

      signHelpVideo.play().then(function () {

        hideSignPlay();

      }).catch(function () {

        showSignPlay();

      });

    }
  );


  signHelpVideo.addEventListener(
    "ended",
    function () {

      showSignPlay();

    }
  );


  signHelpVideo.addEventListener(
    "pause",
    function () {

      if (!signHelpVideo.ended) {

        showSignPlay();

      }

    }
  );


  /* =====================================================
     فيديوهات الهيدر
     ===================================================== */

  document
    .querySelectorAll("[data-dashboard-video]")
    .forEach(function (item) {

      item.addEventListener(
        "mouseenter",
        function () {

          playHelpVideo(
            item.dataset.dashboardVideo,
            item.dataset.dashboardText || ""
          );

        }
      );


      item.addEventListener(
        "focus",
        function () {

          playHelpVideo(
            item.dataset.dashboardVideo,
            item.dataset.dashboardText || ""
          );

        }
      );


      item.addEventListener(
        "mouseleave",
        function () {

          if (isHelpOpen()) {
            stopHelpVideo();
          }

        }
      );


      item.addEventListener(
        "blur",
        function () {

          if (isHelpOpen()) {
            stopHelpVideo();
          }

        }
      );

    });


  /* =====================================================
     فيديوهات البطاقات
     ===================================================== */

  document
    .querySelectorAll("[data-card-video]")
    .forEach(function (card) {

      card.addEventListener(
        "mouseenter",
        function () {

          playHelpVideo(
            card.dataset.cardVideo,
            card.dataset.cardText || ""
          );

        }
      );


      card.addEventListener(
        "focus",
        function () {

          playHelpVideo(
            card.dataset.cardVideo,
            card.dataset.cardText || ""
          );

        }
      );


      card.addEventListener(
        "mouseleave",
        function () {

          if (isHelpOpen()) {
            stopHelpVideo();
          }

        }
      );


      card.addEventListener(
        "blur",
        function () {

          if (isHelpOpen()) {
            stopHelpVideo();
          }

        }
      );

    });


  /* =====================================================
     فيديو المقدمة
     ===================================================== */

  function hideHeroPlay() {

    if (playBtn) {
      playBtn.style.display = "none";
    }

    if (heroSignBox) {
      heroSignBox.classList.add("playing");
    }

  }


  function showHeroPlay() {

    if (playBtn) {
      playBtn.style.display = "flex";
    }

    if (heroSignBox) {
      heroSignBox.classList.remove("playing");
    }

  }


  function refreshHeroVideoSource() {

    if (!heroIntroVideo) {
      return;
    }

    const source = heroIntroVideo.querySelector("source");

    if (!source) {
      return;
    }

    const originalSrc =
      source.getAttribute("src");

    if (!originalSrc) {
      return;
    }

    const freshSrc =
      freshVideoPath(originalSrc);


    source.setAttribute(
      "src",
      freshSrc
    );

    heroIntroVideo.load();

  }


  function playHeroVideo() {

    if (!heroIntroVideo) {
      return;
    }


    /*
      كل تشغيل يأخذ نسخة جديدة من الفيديو
      حتى لو تم استبدال نفس الملف على السيرفر.
    */

    refreshHeroVideoSource();


    heroIntroVideo.play().then(function () {

      hideHeroPlay();

    }).catch(function () {

      showHeroPlay();

    });

  }


  function stopHeroVideo(reset) {

    if (!heroIntroVideo) {
      return;
    }

    heroIntroVideo.pause();

    if (reset) {

      try {
        heroIntroVideo.currentTime = 0;
      } catch (e) {}

    }

    showHeroPlay();

  }


  if (heroBox && heroIntroVideo) {

    heroBox.addEventListener(
      "mouseenter",
      function () {

        if (isHelpOpen()) {
          playHeroVideo();
        }

      }
    );


    heroBox.addEventListener(
      "mouseleave",
      function () {

        if (isHelpOpen()) {
          stopHeroVideo(true);
        }

      }
    );

  }


  if (playBtn && heroIntroVideo) {

    playBtn.addEventListener(
      "click",
      function (e) {

        e.preventDefault();

        e.stopPropagation();


        if (heroIntroVideo.paused) {

          playHeroVideo();

        } else {

          stopHeroVideo(false);

        }

      }
    );

  }


  if (heroIntroVideo) {

    heroIntroVideo.addEventListener(
      "click",
      function (e) {

        e.preventDefault();

        e.stopPropagation();


        if (heroIntroVideo.paused) {

          playHeroVideo();

        } else {

          stopHeroVideo(false);

        }

      }
    );


    heroIntroVideo.addEventListener(
      "ended",
      function () {

        stopHeroVideo(true);

      }
    );

  }

});
</script>

</body>
</html>

