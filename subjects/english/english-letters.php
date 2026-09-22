<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// منع كاش الفيديوهات القديمة وإجبار المتصفح على استخدام آخر نسخة من الملف
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

function video_cache_url($url) {
    $url = (string)$url;
    $cleanUrl = preg_replace('/([?&])v=[^&]*/', '$1', $url);
    $cleanUrl = rtrim($cleanUrl, '?&');
    $filePath = realpath(__DIR__ . '/' . ltrim($cleanUrl, '/'));
    $version = ($filePath && is_file($filePath)) ? (string)filemtime($filePath) : (string)time();
    return $cleanUrl . (strpos($cleanUrl, '?') !== false ? '&' : '?') . 'v=' . $version;
}

require_once __DIR__ . '/../../config/session_child.php';
$lang = $_SESSION['lang'] ?? 'ar';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/db.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

function filePathFromDb($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (strpos($path, '../') === 0 || strpos($path, '../../') === 0 || strpos($path, '/') === 0) {
        return $path;
    }

    return "../../" . ltrim($path, '/');
}

$englishLetters = [];
$sql = "
    SELECT * FROM lessons
    WHERE subject_name = 'اللغة الإنجليزية' AND lesson_type = 'الحروف الإنجليزية'
    ORDER BY
    CASE WHEN letter_id IS NULL OR letter_id = 0 THEN id ELSE letter_id END ASC
";
$resultLessons = mysqli_query($conn, $sql);
if ($resultLessons) {
    while ($row = mysqli_fetch_assoc($resultLessons)) {
        $englishLetters[] = $row;
    }
}

// ✅ FIX: إزالة التكرار بحسب letter_id حتى لا يظهر الحرف مرتين
$seenIds = [];
$uniqueLetters = [];
foreach ($englishLetters as $lesson) {
    $lid = intval($lesson['letter_id'] ?? 0);
    $key = $lid > 0 ? "lid_{$lid}" : "row_{$lesson['id']}";
    if (!isset($seenIds[$key])) {
        $seenIds[$key] = true;
        $uniqueLetters[] = $lesson;
    }
}
$englishLetters = $uniqueLetters;

function assignColorsLimit2($order, $colorCount = 14) {
    $pool = [];
    for ($i = 1; $i <= $colorCount; $i++) { $pool[] = $i; $pool[] = $i; $pool[] = $i; }
    shuffle($pool);
    $result = [];
    $cols = 5;
    foreach ($order as $index => $letterId) {
        $row = intdiv($index, $cols);
        $col = $index % $cols;
        $used = [];
        if ($col > 0 && isset($result[$index - 1])) $used[] = $result[$index - 1];
        if ($row > 0 && isset($result[$index - $cols])) $used[] = $result[$index - $cols];
        foreach ($pool as $key => $color) {
            if (!in_array($color, $used)) { $result[$index] = $color; unset($pool[$key]); break; }
        }
        if (!isset($result[$index])) $result[$index] = 1;
    }
    return $result;
}

$colorMap = assignColorsLimit2(range(1, max(count($englishLetters), 1)), 14);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"/>
  <title>English Letters - Shama Amal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=4">
  <style>
    .card-wrapper{position:relative;width:200px;height:230px;}
    .card-bg{width:100%;height:100%;object-fit:contain;}
    .card-sign{position:absolute;top:70px;left:50%;transform:translateX(-50%);width:95px;height:95px;object-fit:contain;}
    .card-letter{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);font-size:26px;font-weight:bold;}
    .letters-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:20px;justify-items:center;}
    .dash-nav .circle-icon{position:relative;overflow:visible!important;display:flex;flex-direction:column;align-items:center;justify-content:center;text-decoration:none;}
    .dash-nav .circle-icon video,.dash-nav .circle-icon img{border-radius:50%;}
    .dash-nav .nav-text{position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%) translateY(-6px);opacity:0;pointer-events:none;white-space:nowrap;font-size:16px;font-weight:900;color:#ffffff;background:linear-gradient(135deg,#ff7aa8,#ff5f8f);padding:7px 18px;border-radius:999px;box-shadow:0 10px 22px rgba(255,94,143,0.35);transition:0.25s ease;z-index:30;}
    .dash-nav .circle-icon:hover .nav-text,.dash-nav .circle-icon:focus .nav-text{opacity:1;transform:translateX(-50%) translateY(0);}
    .children-dash-inner{direction:rtl;grid-template-columns:320px 1fr 320px;}

    /* ===== MOBILE ===== */
    @media (max-width: 768px) {
      .children-dash-inner { grid-template-columns: auto 1fr auto !important; padding: 8px 12px !important; min-height: auto !important; gap: 8px !important; }
      .logo-box img { width: 44px !important; height: 44px !important; }
      .logo-text { display: none !important; }
      .dash-nav { gap: 12px !important; flex-wrap: nowrap !important; justify-content: center !important; }
      .circle-icon { width: 44px !important; height: 44px !important; }
      .profile-hello { font-size: 13px !important; max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .profile-video-box { width: 40px !important; height: 40px !important; }
      .profile-menu { left: 0 !important; right: auto !important; }

      .letters-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
      .card-wrapper { width: 100% !important; height: auto !important; aspect-ratio: 200 / 230; }
      .card-sign { width: 45% !important; height: 40% !important; top: 28% !important; }
      .card-letter { font-size: 22px !important; bottom: 12px !important; }

      .hero-box { grid-template-columns: 1fr !important; text-align: center; padding: 22px 20px !important; gap: 20px !important; }
      .hero-sign-video { justify-content: center; margin-left: 0; }
      .hero-sign-video video { width: 160px; height: 210px; }
      .hero-text { text-align: center; }
      .hero-text h2 { font-size: 26px; }

      .sign-floating { left: 12px !important; bottom: 12px !important; }
      .sign-circle { width: 52px !important; height: 52px !important; padding: 6px !important; }
      .sign-video-box { width: 200px !important; bottom: 68px !important; }
    }

    @media (max-width: 480px) {
      .letters-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; }
      .page-wrap { width: calc(100% - 16px) !important; }
    }

    @media (max-width: 380px) {
      .letters-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 6px !important; }
      .hero-text h2 { font-size: 20px !important; }
    }
  </style>
</head>
<body>

<header class="children-dash">
  <div class="children-dash-inner">
    <div class="dash-start">
      <div class="dash-end">
        <a href="../../index.php" class="logo-box" tabindex="0"
           data-dashboard-video="../../images/videos/logo.mp4"
           data-dashboard-text="شعار منصة شمعة أمل">
          <img src="../../logo.png" alt="logo">
        </a>
      </div>
    </div>

    <nav class="dash-nav">
      <a href="../../auth/children.php" class="circle-icon home-icon" tabindex="0"
         aria-label="<?php echo ($lang === 'ar') ? 'العودة إلى الصفحة الرئيسية' : 'Back to home page'; ?>"
         data-dashboard-video="<?php echo htmlspecialchars(video_cache_url('../../assets/videos/kids-icon-sign.mp4')); ?>"
         data-dashboard-text="صفحة الطفل">
        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="<?php echo htmlspecialchars(video_cache_url('../../assets/icons/children.mp4')); ?>" type="video/mp4">
        </video>
        <span class="nav-text">صفحة الطفل</span>
      </a>

      <a href="../../subjects/english/english.php" class="circle-icon home-icon" tabindex="0"
         aria-label="قسم اللغة الإنجليزية"
         data-dashboard-video="<?php echo htmlspecialchars(video_cache_url('../../assets/videos/english-word.mp4')); ?>"
         data-dashboard-text="اللغة الإنجليزية">
        <img src="../../assets/icons/en-letter.png" alt="ENGLISH">
        <span class="nav-text">اللغة الإنجليزية</span>
      </a>
    </nav>

    <div class="profile-wrap">
      <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
        <span class="profile-hello">مرحباً <?php echo htmlspecialchars($child_display_name); ?></span>
        <div class="profile-video-box">
          <video autoplay muted loop playsinline>
            <source src="<?php echo htmlspecialchars(video_cache_url('../../assets/icons/profile.mp4')); ?>" type="video/mp4">
          </video>
        </div>
      </button>
      <div class="profile-menu" id="profileMenu">
        <a href="../../auth/account_settings.php"
           data-dashboard-video="<?php echo htmlspecialchars(video_cache_url('../../assets/videos/settings-sign.mp4')); ?>"
           data-dashboard-text="إعدادات الحساب">إعدادات الحساب</a>
        <a href="../../auth/logout.php"
           data-dashboard-video="<?php echo htmlspecialchars(video_cache_url('../../assets/videos/logout-sign.mp4')); ?>"
           data-dashboard-text="تسجيل الخروج">تسجيل الخروج</a>
      </div>
    </div>
  </div>
</header>

<main class="page-wrap">
  <section class="hero-box" id="heroBox" data-sign-video="<?php echo htmlspecialchars(video_cache_url('../../images/videos/english-intro.mp4')); ?>" data-sign-text="قسم اللغة الإنجليزية">
    <div class="hero-sign-video" id="heroSignBox">
      <video id="heroIntroVideo" muted playsinline>
        <source src="<?php echo htmlspecialchars(video_cache_url('../../images/videos/english-intro.mp4')); ?>" type="video/mp4">
      </video>
      <div class="play-btn" id="playBtn">▶</div>
    </div>
    <div class="hero-text">
      <span class="small-badge">هيا نتعلم</span>
      <h2>اختر الحرف الذي تريد تعلمه</h2>
      <p>اضغط على أي حرف لتنتقل إلى صفحة فيها الاستماع والنطق والاختبار والتلوين بطريقة ممتعة وسهلة</p>
      <a href="#lettersSection" class="start-btn">ابدأ الآن</a>
    </div>
  </section>

  <section class="letters-section" id="lettersSection">
    <div class="section-title">
      <h3>English Letters</h3>
      <p>Click a letter to start learning</p>
    </div>

    <div class="letters-grid">
      <?php if (!empty($englishLetters)): ?>
      <?php foreach ($englishLetters as $index => $lesson): ?>
      <?php
        $letterId = isset($lesson['letter_id']) && intval($lesson['letter_id']) > 0
            ? intval($lesson['letter_id'])
            : ($index + 1);
        $lessonDbId = intval($lesson['id'] ?? 0);
        $letterTitle = $lesson['lesson_title'] ?? $lesson['title'] ?? $lesson['name'] ?? '';
        if (trim((string)$letterTitle) === '') $letterTitle = 'Letter ' . $letterId;
        $cardColor = $colorMap[$index] ?? 1;
        $customSign = trim((string)($lesson['custom_sign'] ?? ''));
        $customSignPath = $customSign !== '' ? filePathFromDb($customSign) : '';
        if ($letterId > 26 && $customSignPath !== '') $signImage = $customSignPath;
        else $signImage = "../../images/signs/english/" . $letterId . ".png";
        $defaultSignFallback = $customSignPath !== '' ? $customSignPath : "../../assets/icons/sign-icon.png";
      ?>
      <a href="english-letter.php?id=<?php echo $letterId; ?>"
         <?php if ($lessonDbId > 0): ?>data-lesson-id="<?php echo $lessonDbId; ?>"<?php endif; ?>
         class="track-lesson-link">
        <div class="card-wrapper">
          <img src="../../images/cards/<?php echo $cardColor; ?>.png" class="card-bg" alt="card">
          <img src="<?php echo htmlspecialchars($signImage); ?>" class="card-sign"
               alt="<?php echo htmlspecialchars($letterTitle); ?>"
               onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($defaultSignFallback); ?>';">
          <div class="card-letter"><?php echo htmlspecialchars($letterTitle); ?></div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="grid-column:1/-1;text-align:center;font-size:24px;font-weight:900;color:#21425f;background:#fff;border-radius:24px;padding:28px;">
        لا يوجد حروف إنجليزية مضافة بعد
      </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<!-- Sign Language Component -->
<style>
.sign-floating{position:fixed!important;left:22px!important;bottom:22px!important;z-index:2147483647!important;display:block!important;pointer-events:auto!important;}
.sign-circle{width:75px;height:75px;border-radius:50%;border:4px solid white;background:#32d27b;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:8px;box-shadow:0 10px 25px rgba(0,0,0,.25);position:relative;z-index:2147483647;transition:.25s ease;}
.sign-circle.active{box-shadow:0 0 0 8px rgba(50,210,123,.25),0 0 28px rgba(50,210,123,.9),0 10px 25px rgba(0,0,0,.25);transform:scale(1.08);}
.sign-circle:hover{transform:scale(1.06);}
.sign-circle.active:hover{transform:scale(1.08);}
.sign-circle img{width:100%;height:100%;object-fit:contain;display:block;}
.sign-video-box{position:absolute;left:0;bottom:95px;width:260px;height:auto;background:white;border-radius:24px;padding:8px;display:none;box-shadow:0 18px 45px rgba(0,0,0,.28);z-index:2147483647;}
.sign-video-box.active{display:block!important;}
.sign-helper-text{width:100%;text-align:center;font-family:Arial,sans-serif;font-size:16px;font-weight:900;color:#21425f;background:#eafff2;border:2px solid #92efba;border-radius:16px;padding:8px 10px;margin-bottom:8px;line-height:1.5;display:none;}
.sign-video-wrap{position:relative;width:100%;height:260px;}
.sign-video-box video{width:100%;height:100%;border-radius:18px;object-fit:contain;background:#000;}
.sign-play-btn{position:absolute;inset:0;margin:auto;width:74px;height:74px;border-radius:50%;border:4px solid white;background:rgba(255,91,120,.92);color:white;font-size:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:5;box-shadow:0 10px 24px rgba(0,0,0,.25);}
.sign-play-btn.hide{display:none!important;}
.close-sign{position:absolute;top:-20px;right:-20px;width:55px;height:55px;border-radius:50%;border:4px solid white;background:#ff3b3b;color:white;font-size:26px;font-weight:900;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:9999;box-shadow:0 8px 20px rgba(0,0,0,0.25);}
</style>

<div class="sign-floating">
  <button class="sign-circle" id="signToggleBtn" onclick="toggleSignVideo()" type="button" title="لغة الإشارة">
    <img src="../../assets/icons/sign-icon.png" class="sign-icon" alt="لغة الإشارة">
  </button>
  <div class="sign-video-box" id="signVideoBox">
    <button class="close-sign" onclick="closeSignVideo()" type="button">×</button>
    <div id="signHelperText" class="sign-helper-text"></div>
    <div class="sign-video-wrap">
      <video id="signVideo" playsinline muted preload="auto">
        <source id="signVideoSource" src="<?php echo htmlspecialchars(video_cache_url('../../images/videos/english-intro.mp4')); ?>" type="video/mp4">
      </video>
      <button class="sign-play-btn" id="signPlayBtn" onclick="playSignVideo()" type="button">▶</button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");
  if (profileBtn && profileMenu) {
    profileBtn.addEventListener("click", function (e) { e.stopPropagation(); profileMenu.classList.toggle("show"); });
    document.addEventListener("click", function (e) {
      if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) profileMenu.classList.remove("show");
    });
  }

  window.sectionVideoStarted = false;
  window.currentSectionVideo = <?php echo json_encode(video_cache_url('../../images/videos/english-intro.mp4')); ?>;
  window.currentSectionTarget = null;
  window.currentSectionText = "";
  window.isDashboardHover = false;
  window.isHeroHover = false;

  window.isSignBoxOpen = function () { return document.body.classList.contains("sign-open"); };
  window.showFloatingSignBox = function () { const box = document.getElementById("signVideoBox"); if (box) box.classList.add("active"); };
  window.hideFloatingSignBox = function () { const box = document.getElementById("signVideoBox"); if (box) box.classList.remove("active"); };
  window.clearActiveSignTarget = function () { document.querySelectorAll(".sign-active-target").forEach(el => el.classList.remove("sign-active-target")); };
  window.setActiveSignTarget = function (element) {
    clearActiveSignTarget();
    if (!element) return;
    const circle = element.classList && element.classList.contains("circle-icon") ? element : element.querySelector(".circle-icon,.nav-circle,.nav-video-circle");
    if (circle) circle.classList.add("sign-active-target");
    else element.classList.add("sign-active-target");
  };
  window.showSignText = function (text = "") {
    const textBox = document.getElementById("signHelperText");
    if (!textBox) return;
    textBox.textContent = text || "";
    textBox.style.display = (text && text.trim() !== "") ? "block" : "none";
  };
  window.showPlayButton = function () { const p = document.getElementById("signPlayBtn"); if (p) p.classList.remove("hide"); };
  window.hidePlayButton = function () { const p = document.getElementById("signPlayBtn"); if (p) p.classList.add("hide"); };
  window.setVideoOnly = function (videoPath) {
    const video = document.getElementById("signVideo");
    const source = document.getElementById("signVideoSource");
    if (!video || !source || !videoPath) return;
    const freshVideoPath = videoPath + (videoPath.includes("?") ? "&" : "?") + "refresh=" + Date.now();
    video.pause();
    source.setAttribute("src", freshVideoPath);
    video.load();
  };
  window.setSignVideo = function (videoPath, targetElement = null, shouldPlay = false, text = "") {
    const box = document.getElementById("signVideoBox");
    const video = document.getElementById("signVideo");
    if (!videoPath || !video) return;
    currentSectionVideo = videoPath; currentSectionTarget = targetElement; currentSectionText = text || "";
    isDashboardHover = false; isHeroHover = false;
    setVideoOnly(videoPath); sectionVideoStarted = false; showPlayButton();
    if (box && box.classList.contains("active")) { setActiveSignTarget(targetElement); showSignText(currentSectionText); }
  };
  window.openDashboardSignVideo = function (videoPath, element = null, text = "") {
    const video = document.getElementById("signVideo");
    if (!isSignBoxOpen() || !video) return;
    showFloatingSignBox(); isDashboardHover = true; isHeroHover = false;
    setVideoOnly(videoPath); setActiveSignTarget(element); showSignText(text);
    video.currentTime = 0; video.play().then(() => hidePlayButton()).catch(() => showPlayButton());
    sectionVideoStarted = false;
  };
  window.leaveDashboardSignVideo = function () {
    const video = document.getElementById("signVideo");
    if (!isSignBoxOpen() || !video || !isDashboardHover) return;
    isDashboardHover = false; video.pause(); video.currentTime = 0; showPlayButton();
    showSignText(""); clearActiveSignTarget(); hideFloatingSignBox();
    if (currentSectionVideo) setVideoOnly(currentSectionVideo);
  };
  window.openHeroSignVideo = function () {
    const heroVisualVideo = document.getElementById("heroIntroVideo");
    const heroSignBox = document.getElementById("heroSignBox");
    if (!isSignBoxOpen()) return;
    isHeroHover = true; isDashboardHover = false; clearActiveSignTarget();
    if (heroVisualVideo && heroSignBox) {
      heroVisualVideo.currentTime = 0;
      heroVisualVideo.play().then(() => { heroSignBox.classList.add("playing"); const p = document.getElementById("playBtn"); if (p) p.style.display = "none"; }).catch(() => {});
    }
  };
  window.leaveHeroSignVideo = function () {
    const heroVisualVideo = document.getElementById("heroIntroVideo");
    const heroSignBox = document.getElementById("heroSignBox");
    const playBtn2 = document.getElementById("playBtn");
    if (!isSignBoxOpen() || !isHeroHover) return;
    isHeroHover = false;
    if (heroVisualVideo && heroSignBox) { heroVisualVideo.pause(); heroVisualVideo.currentTime = 0; heroSignBox.classList.remove("playing"); }
    if (playBtn2) playBtn2.style.display = "flex";
  };
  window.openSignVideo = function (videoPath, element = null, text = "") {
    const video = document.getElementById("signVideo");
    if (!isSignBoxOpen() || !video) return;
    if (!sectionVideoStarted) { setActiveSignTarget(currentSectionTarget); showSignText(currentSectionText); return; }
    isDashboardHover = false; isHeroHover = false;
    setVideoOnly(videoPath); setActiveSignTarget(element); showSignText(text);
    video.currentTime = 0; video.play().then(() => hidePlayButton()).catch(() => showPlayButton());
  };
  window.playSignVideo = function () {
    const video = document.getElementById("signVideo");
    if (!video) return;
    isDashboardHover = false; isHeroHover = false; sectionVideoStarted = true;
    video.muted = true; video.play().then(() => hidePlayButton()).catch(() => showPlayButton());
  };
  window.closeSignVideo = function () {
    const box = document.getElementById("signVideoBox");
    const toggleBtn = document.getElementById("signToggleBtn");
    const video = document.getElementById("signVideo");
    const heroVisualVideo = document.getElementById("heroIntroVideo");
    const heroSignBox = document.getElementById("heroSignBox");
    const playBtn2 = document.getElementById("playBtn");
    if (!box || !video) return;
    box.classList.remove("active");
    if (toggleBtn) toggleBtn.classList.remove("active");
    document.body.classList.remove("sign-open");
    clearActiveSignTarget(); showPlayButton(); showSignText("");
    isDashboardHover = false; isHeroHover = false;
    video.pause(); video.currentTime = 0;
    if (heroVisualVideo && heroSignBox) { heroVisualVideo.pause(); heroVisualVideo.currentTime = 0; heroSignBox.classList.remove("playing"); }
    if (playBtn2) playBtn2.style.display = "flex";
    if (currentSectionVideo) setVideoOnly(currentSectionVideo);
  };
  window.toggleSignVideo = function () {
    const box = document.getElementById("signVideoBox");
    const toggleBtn = document.getElementById("signToggleBtn");
    const video = document.getElementById("signVideo");
    if (!box) return;
    if (document.body.classList.contains("sign-open")) { closeSignVideo(); }
    else {
      fetch('../../api/log-help.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'page='+encodeURIComponent(window.location.pathname)}).catch(function(){});
      if (toggleBtn) toggleBtn.classList.add("active");
      document.body.classList.add("sign-open");
      hideFloatingSignBox(); isDashboardHover = false; isHeroHover = false; sectionVideoStarted = false;
      if (currentSectionVideo) setVideoOnly(currentSectionVideo);
      clearActiveSignTarget(); showSignText("");
      if (video) { video.pause(); video.currentTime = 0; video.muted = true; }
      showPlayButton();
    }
  };

  function updateActiveSection() {
    if (isDashboardHover || isHeroHover) return;
    const sections = document.querySelectorAll("[data-sign-video]");
    let closestSection = null; let closestDistance = Infinity;
    sections.forEach(section => {
      const rect = section.getBoundingClientRect();
      const distance = Math.abs(rect.top - 140);
      if (rect.top < window.innerHeight && rect.bottom > 140 && distance < closestDistance) { closestDistance = distance; closestSection = section; }
    });
    if (closestSection) { const text = closestSection.dataset.signText || closestSection.getAttribute("aria-label") || ""; setSignVideo(closestSection.dataset.signVideo, closestSection, false, text); }
  }

  window.addEventListener("scroll", updateActiveSection);
  window.addEventListener("load", updateActiveSection);
  updateActiveSection();

  document.querySelectorAll("[data-dashboard-video]").forEach(function (item) {
    item.addEventListener("mouseenter", function () { openDashboardSignVideo(item.dataset.dashboardVideo, item, item.dataset.dashboardText || ""); });
    item.addEventListener("focus", function () { openDashboardSignVideo(item.dataset.dashboardVideo, item, item.dataset.dashboardText || ""); });
    item.addEventListener("mouseleave", leaveDashboardSignVideo);
    item.addEventListener("blur", leaveDashboardSignVideo);
  });

  const cards = document.querySelectorAll(".letters-grid a");
  cards.forEach(function (card) {
    const img = card.querySelector(".card-sign");
    const textEl = card.querySelector(".card-letter");
    if (!img) return;
    card.setAttribute("data-card-video", img.getAttribute("src"));
    card.setAttribute("data-card-text", textEl ? textEl.textContent.trim() : "");
    card.addEventListener("mouseenter", function () { openSignVideo(card.dataset.cardVideo, card.querySelector(".card-wrapper") || card, card.dataset.cardText || ""); });
    card.addEventListener("focus", function () { openSignVideo(card.dataset.cardVideo, card.querySelector(".card-wrapper") || card, card.dataset.cardText || ""); });
  });

  const mainSignVideo = document.getElementById("signVideo");
  if (mainSignVideo) {
    mainSignVideo.addEventListener("ended", function () { showPlayButton(); });
    mainSignVideo.addEventListener("pause", function () { if (!mainSignVideo.ended) showPlayButton(); });
  }

  const heroBox = document.getElementById("heroBox");
  const heroIntroVideo = document.getElementById("heroIntroVideo");
  const heroPlayBtn = document.getElementById("playBtn");
  const heroSignBox2 = document.getElementById("heroSignBox");

  function isHelpOpenForHero() { const box = document.getElementById("signVideoBox"); return box && box.classList.contains("active"); }
  function playHeroVideo() {
    if (!heroIntroVideo || !heroPlayBtn || !heroSignBox2) return;
    heroIntroVideo.play().then(() => { heroSignBox2.classList.add("playing"); heroPlayBtn.style.display = "none"; }).catch(() => {});
  }
  function stopHeroVideo(reset = false) {
    if (!heroIntroVideo || !heroPlayBtn || !heroSignBox2) return;
    heroIntroVideo.pause(); if (reset) heroIntroVideo.currentTime = 0;
    heroSignBox2.classList.remove("playing"); heroPlayBtn.style.display = "flex";
  }

  if (heroPlayBtn && heroIntroVideo && heroSignBox2) {
    heroPlayBtn.addEventListener("click", function (e) { e.preventDefault(); e.stopPropagation(); if (isHelpOpenForHero()) return; if (heroIntroVideo.paused) playHeroVideo(); else stopHeroVideo(false); });
    heroIntroVideo.addEventListener("click", function (e) { e.preventDefault(); e.stopPropagation(); if (isHelpOpenForHero()) return; if (heroIntroVideo.paused) playHeroVideo(); else stopHeroVideo(false); });
    heroIntroVideo.addEventListener("ended", function () { stopHeroVideo(true); });
  }

  if (heroBox) {
    heroBox.addEventListener("mouseenter", function () { if (isSignBoxOpen()) openHeroSignVideo(); });
    heroBox.addEventListener("mouseleave", function () { if (isSignBoxOpen()) leaveHeroSignVideo(); });
  }

  // ── التراك عند الضغط على الحرف ──
  document.querySelectorAll('a.track-lesson-link[data-lesson-id]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const href = link.href;
      fetch('../../auth/track.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'type=lesson&id=' + encodeURIComponent(link.dataset.lessonId)
      }).finally(function () { window.location.href = href; });
    });
  });
});
</script>
</body>
</html>
