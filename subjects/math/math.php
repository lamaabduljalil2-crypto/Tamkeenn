<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================================================
   منع المتصفح من الاحتفاظ بنسخة قديمة من الصفحة
   ========================================================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../../config/session_child.php';

$lang = $_SESSION['lang'] ?? 'ar';

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "child"
) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/db.php';

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

/* =========================================================
   إصدار الكاش للفيديوهات عند تحميل الصفحة
   ========================================================= */
$video_cache_version = time();

/**
 * إضافة نسخة كاش جديدة إلى رابط الفيديو.
 */
function videoCacheUrl($url)
{
    global $video_cache_version;

    $url = trim((string)$url);

    if ($url === '') {
        return '';
    }

    // إزالة v القديمة إن وجدت
    $url = preg_replace('/([?&])v=[^&]*/', '$1', $url);

    // تنظيف ? أو & في نهاية الرابط
    $url = preg_replace('/[?&]+$/', '', $url);

    $separator = (strpos($url, '?') !== false) ? '&' : '?';

    return $url . $separator . 'v=' . $video_cache_version;
}

function toArabicDigits($number)
{
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

    return str_replace($western, $arabic, (string)$number);
}

function filePathFromDb($path)
{
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (
        strpos($path, '../') === 0 ||
        strpos($path, '../../') === 0 ||
        strpos($path, '/') === 0
    ) {
        return $path;
    }

    return '../../' . ltrim($path, '/');
}

function defaultArabicNumberData($id, $name = '')
{
    $names = [
        0   => 'صفر',
        1   => 'واحد',
        2   => 'اثنان',
        3   => 'ثلاثة',
        4   => 'أربعة',
        5   => 'خمسة',
        6   => 'ستة',
        7   => 'سبعة',
        8   => 'ثمانية',
        9   => 'تسعة',
        10  => 'عشرة',
        20  => 'عشرون',
        30  => 'ثلاثون',
        40  => 'أربعون',
        50  => 'خمسون',
        60  => 'ستون',
        70  => 'سبعون',
        80  => 'ثمانون',
        90  => 'تسعون',
        100 => 'مئة'
    ];

    return [
        'db_id'          => 0,
        'id'             => (int)$id,
        'number'         => toArabicDigits($id),
        'name'           => $name !== ''
            ? $name
            : ($names[(int)$id] ?? toArabicDigits($id)),
        'sign_image'     => 'images/signs/numbers/' . intval($id) . '.png',
        'number_image'   => 'images/numbers/arabic/' . intval($id) . '.png',
        'coloring_image' => 'images/coloring/number-ar/' . intval($id) . '.png',
        'number_video'   => ''
    ];
}

$defaultOrder = [
    0,1,2,3,4,5,6,7,8,9,
    10,20,30,40,50,60,70,80,90,100
];

$numbers = [];

$dbResult = mysqli_query(
    $conn,
    "
    SELECT * FROM lessons
    WHERE subject_name = 'الرياضيات'
      AND lesson_type = 'الأرقام العربية'
    ORDER BY letter_id ASC, id ASC
    "
);

if ($dbResult && mysqli_num_rows($dbResult) > 0) {

    while ($row = mysqli_fetch_assoc($dbResult)) {

        $numId = intval($row['letter_id'] ?? 0);

        if ($numId < 0) {
            continue;
        }

        $baseData = defaultArabicNumberData($numId);

        $numbers[$numId] = [
            'db_id'          => intval($row['id'] ?? 0),
            'id'             => $numId,

            'number'         =>
                trim($row['lesson_title'] ?? '') !== ''
                ? trim($row['lesson_title'])
                : toArabicDigits($numId),

            'name'           =>
                trim($row['example_word'] ?? '') !== ''
                ? trim($row['example_word'])
                : $baseData['name'],

            'sign_image'     =>
                trim($row['custom_sign'] ?? '') !== ''
                ? trim($row['custom_sign'])
                : $baseData['sign_image'],

            'number_image'   =>
                trim($row['lesson_image'] ?? '') !== ''
                ? trim($row['lesson_image'])
                : $baseData['number_image'],

            'coloring_image' =>
                trim($row['coloring_image'] ?? '') !== ''
                ? trim($row['coloring_image'])
                : $baseData['coloring_image'],

            'number_video'   =>
                trim($row['card_video'] ?? '')
        ];
    }
}

/* إذا ما تم تعبئة أي رقم → الافتراضيين */
if (empty($numbers)) {

    foreach ($defaultOrder as $numId) {
        $numbers[$numId] = defaultArabicNumberData($numId);
    }
}

$order = array_keys($numbers);
sort($order, SORT_NUMERIC);

function assignColorsLimit2($order, $colorCount = 14)
{
    $pool = [];

    for ($i = 1; $i <= $colorCount; $i++) {
        $pool[] = $i;
        $pool[] = $i;
    }

    shuffle($pool);

    $result = [];

    $cols = 5;

    foreach ($order as $index => $numId) {

        $row = intdiv($index, $cols);
        $col = $index % $cols;

        $used = [];

        if ($col > 0 && isset($result[$index - 1])) {
            $used[] = $result[$index - 1];
        }

        if ($row > 0 && isset($result[$index - $cols])) {
            $used[] = $result[$index - $cols];
        }

        foreach ($pool as $key => $color) {

            if (!in_array($color, $used)) {

                $result[$index] = $color;

                unset($pool[$key]);

                break;
            }
        }

        if (!isset($result[$index])) {
            $result[$index] = array_shift($pool) ?: 1;
        }
    }

    return $result;
}

$colorMap = assignColorsLimit2($order, 14);


/* =========================================================
   روابط الفيديوهات مع Cache Busting
   ========================================================= */

$logoVideo = videoCacheUrl('../../images/videos/logo.mp4');

$kidsIconVideo = videoCacheUrl('../../assets/videos/kids-icon-sign.mp4');

$mathSignVideo = videoCacheUrl('../../assets/videos/math-sign.mp4');

$childrenIconVideo = videoCacheUrl('../../assets/icons/children.mp4');

$mathIconVideo = videoCacheUrl('../../assets/icons/math.mp4');

$profileIconVideo = videoCacheUrl('../../assets/icons/profile.mp4');

$settingsSignVideo = videoCacheUrl('../../assets/videos/settings-sign.mp4');

$logoutSignVideo = videoCacheUrl('../../assets/videos/logout-sign.mp4');

$heroIntroVideo = videoCacheUrl('../../images/videos/math-intro.mp4');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

  <meta charset="UTF-8" />

  <meta name="viewport"
        content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />

  <title>تعليم الأرقام - تمكين</title>

  <link rel="stylesheet"
        href="../../assets/css/arabic-grid.css?v=4">

  <style>

    .simple-sign-floating{
      position:fixed!important;
      left:22px!important;
      bottom:22px!important;
      z-index:999999999!important;
    }

    .simple-sign-btn{
      width:78px;
      height:78px;
      border-radius:50%;
      border:5px solid #fff;
      background:#35d07f;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:10px;
      cursor:pointer;
      box-shadow:0 12px 28px rgba(0,0,0,.25);
      transition:.25s ease;
    }

    .simple-sign-btn img{
      width:100%;
      height:100%;
      object-fit:contain;
    }

    .simple-sign-btn.active{
      box-shadow:
        0 0 0 8px rgba(53,208,127,.25),
        0 0 30px rgba(53,208,127,.95),
        0 12px 28px rgba(0,0,0,.25);
      transform:scale(1.08);
    }

    .simple-sign-box{
      position:absolute;
      left:0;
      bottom:98px;
      width:260px;
      background:#fff;
      border-radius:24px;
      padding:10px;
      display:none;
      box-shadow:0 18px 45px rgba(0,0,0,.28);
    }

    .simple-sign-box.show{
      display:block;
    }

    .simple-sign-text{
      text-align:center;
      font-size:17px;
      font-weight:900;
      color:#21425f;
      background:#eafff2;
      border:2px solid #92efba;
      border-radius:16px;
      padding:8px;
      margin-bottom:8px;
    }

    .simple-sign-box video{
      width:100%;
      height:200px;
      border-radius:18px;
      object-fit:cover;
      background:#000;
    }

    .card-wrapper{
      position:relative;
      width:200px;
      height:230px;
    }

    .card-bg{
      width:100%;
      height:100%;
      object-fit:contain;
    }

    .card-sign{
      position:absolute;
      top:70px;
      left:50%;
      transform:translateX(-50%);
      width:95px;
      height:95px;
      object-fit:contain;
    }

    .card-number{
      position:absolute!important;
      bottom:18px!important;
      left:50%!important;
      transform:translateX(-50%)!important;
      font-size:40px!important;
      font-weight:900!important;
      color:#000!important;
      z-index:5;
      white-space:nowrap;
    }

    .letters-grid{
      display:grid;
      grid-template-columns:repeat(5,1fr);
      gap:20px;
      justify-items:center;
    }

    .number-link{
      text-decoration:none;
      color:inherit;
    }

    body.sign-open .card-wrapper:hover .card-sign{
      outline:5px solid #ff2f2f!important;
      outline-offset:5px;
      border-radius:50%;
      box-shadow:0 0 0 8px rgba(255,47,47,0.2);
    }

    .hero-sign-video.playing .play-btn{
      display:none!important;
    }

    /* ===== MOBILE ===== */

    @media (max-width: 768px){

      .children-dash-inner{
        grid-template-columns:auto 1fr auto !important;
        padding:8px 12px !important;
        min-height:auto !important;
        gap:8px !important;
      }

      .logo-box img{
        width:44px !important;
        height:44px !important;
      }

      .logo-text{
        display:none !important;
      }

      .dash-nav{
        gap:12px !important;
        flex-wrap:nowrap !important;
        justify-content:center !important;
        transform:none !important;
      }

      .nav-video-item{
        display:flex !important;
        align-items:center !important;
      }

      .nav-text{
        display:none !important;
      }

      .circle-icon{
        width:44px !important;
        height:44px !important;
      }

      .profile-hello{
        font-size:13px !important;
        max-width:70px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
      }

      .profile-video-box{
        width:40px !important;
        height:40px !important;
      }

      .profile-menu{
        left:0 !important;
        right:auto !important;
      }

      .hero-box{
        grid-template-columns:1fr !important;
        text-align:center;
        padding:22px 16px !important;
        gap:16px !important;
      }

      .hero-sign-video{
        justify-content:center;
        margin-left:0;
      }

      .hero-sign-video video{
        width:160px;
        height:210px;
      }

      .hero-text{
        text-align:center;
      }

      .hero-text h2{
        font-size:24px;
      }

      .letters-grid{
        grid-template-columns:repeat(3,1fr) !important;
        gap:10px !important;
      }

      .card-wrapper{
        width:100% !important;
        height:auto !important;
        aspect-ratio:200 / 230;
      }

      .card-sign{
        width:45% !important;
        height:40% !important;
        top:28% !important;
      }

      .card-number{
        font-size:28px !important;
        bottom:12px !important;
      }

      .simple-sign-floating{
        left:12px !important;
        bottom:12px !important;
      }

      .simple-sign-btn{
        width:52px !important;
        height:52px !important;
        padding:6px !important;
      }

      .simple-sign-box{
        width:200px !important;
        bottom:68px !important;
      }
    }

    @media (max-width: 480px){

      .letters-grid{
        grid-template-columns:repeat(3,1fr) !important;
        gap:8px !important;
      }
    }

    @media (max-width: 380px){

      .letters-grid{
        grid-template-columns:repeat(3,1fr) !important;
        gap:6px !important;
      }
    }

  </style>

</head>

<body>

<header class="children-dash">

  <div class="children-dash-inner">

    <div class="dash-start">

      <div class="dash-end">

        <a href="../../index.php"
           class="logo-box"
           tabindex="0"
           data-dashboard-video="<?php echo htmlspecialchars($logoVideo); ?>"
           data-dashboard-text="شعار منصة تمكين">

          <img src="../../logo.png" alt="logo">

        </a>

      </div>

    </div>


    <nav class="dash-nav">

      <a href="../../auth/children.php"
         class="nav-video-item"
         tabindex="0"
         aria-label="صفحة الطفل"
         data-dashboard-video="<?php echo htmlspecialchars($kidsIconVideo); ?>"
         data-dashboard-text="صفحة الطفل">

        <span class="circle-icon home-icon">

          <video class="nav-icon-video"
                 autoplay
                 muted
                 loop
                 playsinline>

            <source
              src="<?php echo htmlspecialchars($childrenIconVideo); ?>"
              type="video/mp4">

          </video>

        </span>

        <span class="nav-text">صفحة الطفل</span>

      </a>


      <a href="../../subjects/math/math.php"
         class="nav-video-item"
         tabindex="0"
         aria-label="الرياضيات"
         data-dashboard-video="<?php echo htmlspecialchars($mathSignVideo); ?>"
         data-dashboard-text="الرياضيات">

        <span class="circle-icon home-icon">

          <video class="nav-icon-video"
                 autoplay
                 muted
                 loop
                 playsinline>

            <source
              src="<?php echo htmlspecialchars($mathIconVideo); ?>"
              type="video/mp4">

          </video>

        </span>

        <span class="nav-text">الرياضيات</span>

      </a>

    </nav>


    <div class="profile-wrap">

      <button type="button"
              class="profile-btn"
              id="profileBtn"
              tabindex="0">

        <span class="profile-hello">
          مرحباً <?php echo htmlspecialchars($child_display_name); ?>
        </span>

        <div class="profile-video-box">

          <video autoplay muted loop playsinline>

            <source
              src="<?php echo htmlspecialchars($profileIconVideo); ?>"
              type="video/mp4">

          </video>

        </div>

      </button>


      <div class="profile-menu" id="profileMenu">

        <a href="../../auth/account_settings.php"
           data-dashboard-video="<?php echo htmlspecialchars($settingsSignVideo); ?>"
           data-dashboard-text="إعدادات الحساب">
          إعدادات الحساب
        </a>

        <a href="../../auth/logout.php"
           data-dashboard-video="<?php echo htmlspecialchars($logoutSignVideo); ?>"
           data-dashboard-text="تسجيل الخروج">
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

      <h2>اختر الرقم الذي تريد تعلمه</h2>

      <p>
        اضغط على أي رقم لتنتقل إلى صفحة فيها التعلم والأنشطة بطريقة ممتعة وسهلة.
      </p>

      <a href="#mathSection" class="start-btn">
        ابدأ الآن
      </a>

    </div>


    <div class="hero-sign-video" id="heroSignBox">

      <video id="heroIntroVideo"
             muted
             playsinline
             preload="auto">

        <source
          src="<?php echo htmlspecialchars($heroIntroVideo); ?>"
          type="video/mp4">

      </video>

      <div class="play-btn" id="playBtn">
        ▶
      </div>

    </div>

  </section>


  <section class="letters-section" id="mathSection">

    <div class="section-title">

      <h3>الأرقام العربية</h3>

      <p>اضغط على الرقم وابدأ التعلم</p>

    </div>


    <div class="letters-grid">

      <?php foreach ($order as $index => $numId): ?>

        <?php if (!isset($numbers[$numId])) continue; ?>

        <?php

          $item = $numbers[$numId];

          $signImage = filePathFromDb($item['sign_image']);

          if ($signImage === '') {
              $signImage =
                "../../images/signs/numbers/" .
                intval($item['id']) .
                ".png";
          }

        ?>

        <a href="arabic-number.php?id=<?php echo intval($item['id']); ?>"
           class="number-link track-lesson-link"
           <?php if ($item['db_id'] > 0): ?>
             data-lesson-id="<?php echo $item['db_id']; ?>"
           <?php endif; ?>>

          <div class="card-wrapper">

            <img
              src="../../images/cards/<?php echo $colorMap[$index] ?? 1; ?>.png"
              class="card-bg"
              alt="بطاقة رقم <?php echo htmlspecialchars($item['number']); ?>">


            <img
              src="<?php echo htmlspecialchars($signImage); ?>"
              class="card-sign"
              alt="إشارة رقم <?php echo htmlspecialchars($item['name']); ?>"
              onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';">


            <div class="card-number">
              <?php echo htmlspecialchars($item['number']); ?>
            </div>

          </div>

        </a>

      <?php endforeach; ?>

    </div>

  </section>

</main>


<div class="simple-sign-floating">

  <button type="button"
          class="simple-sign-btn"
          id="simpleSignBtn"
          title="لغة الإشارة">

    <img
      src="../../assets/icons/sign-icon.png"
      alt="لغة الإشارة">

  </button>


  <div class="simple-sign-box"
       id="simpleSignBox">

    <div class="simple-sign-text"
         id="simpleSignText">
    </div>

    <video id="simpleSignVideo"
           muted
           playsinline
           preload="auto">
    </video>

  </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

  /* =========================================================
     دالة مهمة جداً لمنع الكاش عن الفيديوهات
     ========================================================= */

  function freshVideoPath(videoPath) {

    if (!videoPath) {
      return videoPath;
    }

    let cleanPath = String(videoPath);

    /*
      نحذف أي v قديم
      حتى لا يصبح الرابط مثلاً:
      video.mp4?v=123?v=456
    */
    cleanPath = cleanPath.replace(/([?&])v=[^&]*/g, "");

    /*
      تنظيف ? أو & في نهاية الرابط
    */
    cleanPath = cleanPath.replace(/[?&]+$/, "");

    const separator =
      cleanPath.includes("?") ? "&" : "?";

    return cleanPath +
           separator +
           "v=" +
           Date.now();
  }


  /* =========================================================
     تحديث كل فيديوهات الصفحة عند تحميلها
     ========================================================= */

  document.querySelectorAll("video").forEach(function (video) {

    const source = video.querySelector("source");

    if (source && source.getAttribute("src")) {

      const freshSrc =
        freshVideoPath(source.getAttribute("src"));

      source.setAttribute("src", freshSrc);

      video.load();

    } else if (video.getAttribute("src")) {

      const freshSrc =
        freshVideoPath(video.getAttribute("src"));

      video.setAttribute("src", freshSrc);

      video.load();
    }

  });


  /* =========================================================
     Profile Menu
     ========================================================= */

  const profileBtn =
    document.getElementById("profileBtn");

  const profileMenu =
    document.getElementById("profileMenu");


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


  /* =========================================================
     Sign Language
     ========================================================= */

  let signHelpOn = false;

  const signBtn =
    document.getElementById("simpleSignBtn");

  const signBox =
    document.getElementById("simpleSignBox");

  const signVideo =
    document.getElementById("simpleSignVideo");

  const signText =
    document.getElementById("simpleSignText");

  const heroBox =
    document.getElementById("heroBox");

  const heroVideo =
    document.getElementById("heroIntroVideo");

  const heroSignBox =
    document.getElementById("heroSignBox");

  const playBtn =
    document.getElementById("playBtn");


  /* =========================================================
     تشغيل فيديو المساعدة - دائماً نسخة جديدة
     ========================================================= */

  function showHelpVideo(videoPath, text) {

    if (!signHelpOn || !videoPath) {
      return;
    }

    signText.textContent = text || "";

    signBox.classList.add("show");


    /*
      نضع timestamp جديد كل مرة
      حتى لو كان اسم الفيديو نفسه
    */

    const freshPath =
      freshVideoPath(videoPath);


    signVideo.pause();

    signVideo.removeAttribute("src");

    signVideo.load();


    signVideo.setAttribute(
      "src",
      freshPath
    );


    signVideo.load();

    signVideo.currentTime = 0;

    signVideo.play().catch(() => {});

  }


  function stopHelpVideo() {

    signBox.classList.remove("show");

    signVideo.pause();

    signVideo.currentTime = 0;

  }


  /* =========================================================
     زر لغة الإشارة
     ========================================================= */

  if (signBtn) {

    signBtn.addEventListener("click", function () {

      signHelpOn = !signHelpOn;

      signBtn.classList.toggle(
        "active",
        signHelpOn
      );

      document.body.classList.toggle(
        "sign-open",
        signHelpOn
      );


      if (!signHelpOn) {

        stopHelpVideo();


        if (heroVideo) {

          heroVideo.pause();

          heroVideo.currentTime = 0;

          if (playBtn) {
            playBtn.style.display = "flex";
          }

          if (heroSignBox) {
            heroSignBox.classList.remove("playing");
          }

        }

      }

    });

  }


  /* =========================================================
     فيديوهات الهيدر
     ========================================================= */

  document
    .querySelectorAll("[data-dashboard-video]")
    .forEach(function (item) {


      item.addEventListener(
        "mouseenter",
        function () {

          showHelpVideo(
            item.dataset.dashboardVideo,
            item.dataset.dashboardText
          );

        }
      );


      item.addEventListener(
        "focus",
        function () {

          showHelpVideo(
            item.dataset.dashboardVideo,
            item.dataset.dashboardText
          );

        }
      );


      item.addEventListener(
        "mouseleave",
        function () {

          if (signHelpOn) {
            stopHelpVideo();
          }

        }
      );


      item.addEventListener(
        "blur",
        function () {

          if (signHelpOn) {
            stopHelpVideo();
          }

        }
      );

    });


  /* =========================================================
     فيديو الهيرو
     ========================================================= */

  function refreshHeroVideo() {

    if (!heroVideo) {
      return;
    }

    const source =
      heroVideo.querySelector("source");


    if (source) {

      const originalSrc =
        source.getAttribute("src");

      if (originalSrc) {

        const freshSrc =
          freshVideoPath(originalSrc);

        source.setAttribute(
          "src",
          freshSrc
        );

        heroVideo.load();

      }

    } else {

      const currentSrc =
        heroVideo.getAttribute("src");

      if (currentSrc) {

        heroVideo.setAttribute(
          "src",
          freshVideoPath(currentSrc)
        );

        heroVideo.load();

      }

    }

  }


  function playFreshHeroVideo() {

    if (!heroVideo) {
      return;
    }

    /*
      نطلب النسخة الجديدة قبل التشغيل
    */
    refreshHeroVideo();

    heroVideo.currentTime = 0;

    heroVideo
      .play()
      .then(function () {

        if (heroSignBox) {
          heroSignBox.classList.add("playing");
        }

        if (playBtn) {
          playBtn.style.display = "none";
        }

      })
      .catch(function () {});

  }


  if (playBtn && heroVideo) {

    playBtn.addEventListener(
      "click",
      function (e) {

        e.preventDefault();

        e.stopPropagation();


        if (signHelpOn) {
          return;
        }


        if (heroVideo.paused) {

          playFreshHeroVideo();

        } else {

          heroVideo.pause();

          if (heroSignBox) {
            heroSignBox.classList.remove("playing");
          }

          playBtn.style.display = "flex";

        }

      }
    );

  }


  if (heroVideo) {

    heroVideo.addEventListener(
      "click",
      function (e) {

        e.preventDefault();

        e.stopPropagation();


        if (signHelpOn) {
          return;
        }


        if (heroVideo.paused) {

          playFreshHeroVideo();

        } else {

          heroVideo.pause();

          if (heroSignBox) {
            heroSignBox.classList.remove("playing");
          }

          if (playBtn) {
            playBtn.style.display = "flex";
          }

        }

      }
    );


    heroVideo.addEventListener(
      "ended",
      function () {

        if (heroSignBox) {
          heroSignBox.classList.remove("playing");
        }

        if (playBtn) {
          playBtn.style.display = "flex";
        }

      }
    );

  }


  /* =========================================================
     عند المرور على الهيرو أثناء تشغيل المساعدة
     ========================================================= */

  if (heroBox) {

    heroBox.addEventListener(
      "mouseenter",
      function () {

        if (
          signHelpOn &&
          heroVideo
        ) {

          playFreshHeroVideo();

        }

      }
    );


    heroBox.addEventListener(
      "mouseleave",
      function () {

        if (
          signHelpOn &&
          heroVideo
        ) {

          heroVideo.pause();

          heroVideo.currentTime = 0;

          if (heroSignBox) {
            heroSignBox.classList.remove("playing");
          }

          if (playBtn) {
            playBtn.style.display = "flex";
          }

        }

      }
    );

  }


  /* =========================================================
     التراك عند الضغط على الرقم
     ========================================================= */

  document
    .querySelectorAll(
      'a.track-lesson-link[data-lesson-id]'
    )
    .forEach(function (link) {


      link.addEventListener(
        'click',
        function (e) {

          e.preventDefault();

          const href = link.href;


          fetch(
            '../../auth/track.php',
            {
              method: 'POST',
              credentials: 'include',
              headers: {
                'Content-Type':
                  'application/x-www-form-urlencoded'
              },
              body:
                'type=lesson&id=' +
                encodeURIComponent(
                  link.dataset.lessonId
                )
            }
          )
          .finally(function () {

            window.location.href = href;

          });

        }
      );

    });

});

</script>

</body>
</html>