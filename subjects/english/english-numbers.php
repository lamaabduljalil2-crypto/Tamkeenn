<?php 
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

/* منع تخزين النسخ القديمة من الفيديوهات */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

require_once __DIR__ . '/../../config/session_child.php'; 
$lang = $_SESSION['lang'] ?? 'ar'; 
 
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") { 
    header("Location: ../../auth/login.php"); 
    exit; 
} 
 
require_once '../../config/db.php'; 
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل"; 


/* =========================================================
   كاش الفيديوهات
   ========================================================= */

function videoCacheUrl($url) {
    $url = trim((string)$url);

    if ($url === '') {
        return '';
    }

    /* إزالة أي v أو refresh قديمة */
    $url = preg_replace('/([?&])v=[^&]*/', '$1', $url);
    $url = preg_replace('/([?&])refresh=[^&]*/', '$1', $url);
    $url = rtrim($url, '?&');

    /*
     * نحاول معرفة وقت تعديل ملف الفيديو فعلياً.
     * إذا وجد الملف نستخدم filemtime.
     * إذا لم نجده نستخدم time حتى لا يبقى الفيديو عالقاً بالكاش.
     */
    $filePath = realpath(__DIR__ . '/' . ltrim($url, '/'));

    if ($filePath && is_file($filePath)) {
        $version = filemtime($filePath);
    } else {
        $version = time();
    }

    return $url . (strpos($url, '?') !== false ? '&' : '?') . 'v=' . $version;
}


/* =========================================================
   تحويل مسار الصور القادم من قاعدة البيانات
   ========================================================= */

function filePathFromDb($path) { 
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


/* =========================================================
   أسماء الأرقام الإنجليزية
   ========================================================= */

function defaultEnglishNumberName($number) { 
    $names = [ 
        0 => 'Zero', 
        1 => 'One', 
        2 => 'Two', 
        3 => 'Three', 
        4 => 'Four', 
        5 => 'Five', 
        6 => 'Six', 
        7 => 'Seven', 
        8 => 'Eight', 
        9 => 'Nine', 
        10 => 'Ten', 
        20 => 'Twenty', 
        30 => 'Thirty', 
        40 => 'Forty', 
        50 => 'Fifty', 
        60 => 'Sixty', 
        70 => 'Seventy', 
        80 => 'Eighty', 
        90 => 'Ninety', 
        100 => 'One Hundred' 
    ]; 
 
    return $names[intval($number)] ?? (string)$number; 
} 


/* =========================================================
   البيانات الافتراضية
   ========================================================= */

function defaultEnglishNumberData($id, $name = '') { 
    return [ 
        'db_id' => 0, 
        'id' => (int)$id, 
        'number' => (string)$id, 
        'name' => $name !== '' ? $name : defaultEnglishNumberName($id), 
        'sign_image' => 'images/signs/numbers/' . intval($id) . '.png', 
        'number_image' => 'images/numbers/english/' . intval($id) . '.png', 
        'coloring_image' => 'images/coloring/number-en/' . intval($id) . '.png', 
        'number_video' => '' 
    ]; 
} 


$defaultOrder = [
    1,2,3,4,5,6,7,8,9,10,
    20,30,40,50,60,70,80,90,100
]; 

$numbers = []; 


/* =========================================================
   جلب الأرقام من قاعدة البيانات
   ========================================================= */

$dbResult = mysqli_query( 
    $conn, 
    "SELECT * FROM lessons 
     WHERE subject_name = 'اللغة الإنجليزية' 
     AND lesson_type = 'الأرقام الإنجليزية' 
     ORDER BY letter_id ASC, id ASC" 
); 
 
if ($dbResult && mysqli_num_rows($dbResult) > 0) { 
    while ($row = mysqli_fetch_assoc($dbResult)) { 

        $numId = intval($row['letter_id'] ?? 0); 

        if ($numId < 1) {
            continue;
        }

        $baseData = defaultEnglishNumberData($numId); 

        $numbers[$numId] = [ 
            'db_id'          => intval($row['id'] ?? 0), 
            'id'             => $numId, 
            'number'         => trim($row['lesson_title'] ?? '') !== '' 
                                ? trim($row['lesson_title']) 
                                : (string)$numId, 

            'name'           => trim($row['example_word'] ?? '') !== '' 
                                ? trim($row['example_word']) 
                                : $baseData['name'], 

            'sign_image'     => trim($row['custom_sign'] ?? '') !== '' 
                                ? trim($row['custom_sign']) 
                                : $baseData['sign_image'], 

            'number_image'   => trim($row['lesson_image'] ?? '') !== '' 
                                ? trim($row['lesson_image']) 
                                : $baseData['number_image'], 

            'coloring_image' => trim($row['coloring_image'] ?? '') !== '' 
                                ? trim($row['coloring_image']) 
                                : $baseData['coloring_image'], 

            'number_video'   => trim($row['card_video'] ?? '') 
        ]; 
    } 
} 


/* =========================================================
   إذا كانت قاعدة البيانات فارغة
   ========================================================= */

if (empty($numbers)) { 
    foreach ($defaultOrder as $numId) { 
        $numbers[$numId] = defaultEnglishNumberData($numId); 
    } 
} 


$order = array_keys($numbers); 
sort($order, SORT_NUMERIC); 


/* =========================================================
   توزيع ألوان البطاقات
   ========================================================= */

function assignColorsLimit2($order, $colorCount = 14) { 
 
    $pool = []; 

    for ($i = 1; $i <= $colorCount; $i++) { 
        $pool[] = $i; 
        $pool[] = $i; 
    } 
 
    shuffle($pool); 
 
    $result = []; 
    $cols = 5; 
 
    foreach ($order as $index => $id) { 
 
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
?> 
 
<!DOCTYPE html> 
<html lang="ar" dir="rtl"> 
<head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"> 
  <title>تعلم الأرقام الإنجليزية</title> 
  <link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=4"> 
 
  <style> 
    .card-wrapper { 
      position: relative; 
      width: 200px; 
      height: 230px; 
    } 
 
    .card-bg { 
      width: 100%; 
      height: 100%; 
      object-fit: contain; 
    } 
 
    .card-sign { 
      position: absolute; 
      top: 70px; 
      left: 50%; 
      transform: translateX(-50%); 
      width: 95px; 
      height: 95px; 
      object-fit: contain; 
      border-radius: 50%; 
      transition: 0.2s ease; 
    } 
 
    /* الدائرة الحمراء فقط حول صورة الإشارة الصغيرة */ 
    
 
    .card-letter { 
      position: absolute; 
      bottom: 12px; 
      left: 50%; 
      transform: translateX(-50%); 
      font-size: 26px; 
      font-weight: bold; 
    } 
 
    .letters-grid { 
      display: grid; 
      grid-template-columns: repeat(5, 1fr); 
      gap: 20px; 
      justify-items: center; 
    } 
 
    /* ===== كتابة تحت أيقونات الداش عند الهوفر ===== */ 
    .dash-nav .circle-icon { 
      position: relative !important; 
      overflow: visible !important; 
    } 
 
    .dash-nav .circle-icon video, 
    .dash-nav .circle-icon img { 
      border-radius: 50% !important; 
      pointer-events: none !important; 
    } 
 
     
      .dash-hover-label { 
  position: absolute; 
  left: 50%; 
  top: calc(100% + 12px); 
  transform: translateX(-50%); 
  min-width: 135px; 
  padding: 10px 18px; 
  border-radius: 999px; 
  background: linear-gradient(135deg, #ff7bb2, #ff4f8f); 
  color: #ffffff; 
  font-size: 17px; 
  font-weight: 900; 
  font-family: "Cairo", Arial, sans-serif; 
  white-space: nowrap; 
  opacity: 0; 
  visibility: hidden; 
  pointer-events: none; 
  transition: 0.2s ease; 
  line-height: 1.2; 
  z-index: 99999; 
  text-align: center; 
  box-shadow: 0 8px 18px rgba(255, 79, 143, 0.35); 
} 
 
.dash-nav .circle-icon:hover .dash-hover-label, 
.dash-nav .circle-icon:focus .dash-hover-label { 
  opacity: 1; 
  visibility: visible; 
} 
 
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
      .sign-floating { left: 12px !important; bottom: 12px !important; } 
      .sign-circle { width: 52px !important; height: 52px !important; padding: 6px !important; } 
      .sign-video-box { width: 200px !important; bottom: 68px !important; } 
    } 
 
    @media (max-width: 480px) { 
      .letters-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; } 
    } 
 
    @media (max-width: 380px) { 
      .letters-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 6px !important; } 
    } 
  </style> 
</head> 
 
<body> 
 
<!-- ✅ الداش نفسه --> 
 
<header class="children-dash"> 
  <div class="children-dash-inner"> 
    <div class="dash-start"> 
      <div class="dash-end"> 
        <a href="../../index.php" class="logo-box" tabindex="0"> 
          <img src="../../logo.png" alt="logo"> 
        </a> 
      </div> 
    </div> 

    <nav class="dash-nav"> 
 
      <a href="../../auth/children.php" 
         class="circle-icon home-icon" 
         tabindex="0" 
         aria-label="<?php echo ($lang === 'ar') ? 'العودة إلى الصفحة الرئيسية' : 'Back to home page'; ?>" 
         data-dashboard-video="<?php echo htmlspecialchars(videoCacheUrl('../../assets/videos/kids-icon-sign.mp4')); ?>" 
         data-dashboard-text="صفحة الطفل"> 
 
        <video class="nav-icon-video" autoplay muted loop playsinline> 
          <source src="<?php echo htmlspecialchars(videoCacheUrl('../../assets/icons/children.mp4')); ?>" type="video/mp4"> 
        </video> 

        <span class="dash-hover-label">صفحة الطفل</span> 
 
      </a> 

      <a href="../../subjects/english/english.php" 
         class="circle-icon home-icon" 
         tabindex="0" 
         aria-label="قسم اللغة الإنجليزية" 
         data-dashboard-video="<?php echo htmlspecialchars(videoCacheUrl('../../assets/videos/english-word.mp4')); ?>" 
         data-dashboard-text="اللغة الإنجليزية"> 

        <img src="../../assets/icons/en-letter.png" alt="CHILDREN"> 

        <span class="dash-hover-label">اللغة الإنجليزية</span> 

      </a>  

    </nav>   
 
    <div class="profile-wrap"> 
      <button type="button" class="profile-btn" id="profileBtn" tabindex="0"> 

        <span class="profile-hello"> 
          مرحباً <?php echo htmlspecialchars($child_display_name); ?> 
        </span> 

        <div class="profile-video-box"> 
          <video autoplay muted loop playsinline> 
            <source src="<?php echo htmlspecialchars(videoCacheUrl('../../assets/icons/profile.mp4')); ?>" type="video/mp4"> 
          </video> 
        </div> 

      </button> 
 
      <div class="profile-menu" id="profileMenu"> 

        <a href="../../auth/account_settings.php" 
           data-dashboard-video="<?php echo htmlspecialchars(videoCacheUrl('../../assets/videos/settings-sign.mp4')); ?>" 
           data-dashboard-text="إعدادات الحساب">
           إعدادات الحساب
        </a> 

        <a href="../../auth/logout.php" 
           data-dashboard-video="<?php echo htmlspecialchars(videoCacheUrl('../../assets/videos/logout-sign.mp4')); ?>" 
           data-dashboard-text="تسجيل الخروج">
           تسجيل الخروج
        </a> 

      </div> 
    </div> 
  </div> 
</header> 
 
<main class="page-wrap"> 
 
<section 
  class="letters-section" 
  data-sign-video="<?php echo htmlspecialchars(videoCacheUrl('../../images/videos/english-numbers-intro.mp4')); ?>" 
  data-sign-text="الأرقام الإنجليزية"> 

  <div class="section-title"> 
    <h3>الأرقام الإنجليزية</h3> 
    <p>اضغط على الرقم وابدأ التعلم</p> 
  </div> 
 
  <div class="letters-grid"> 
 
<?php foreach ($order as $index => $numId): ?> 
<?php if (!isset($numbers[$numId])) continue; ?> 

<?php $item = $numbers[$numId]; ?> 

<?php $signImage = filePathFromDb($item['sign_image']); ?> 

<?php 
if ($signImage === '') { 
    $signImage = "../../images/signs/numbers/" . intval($item['id']) . ".png"; 
} 
?> 

  <a href="english-number.php?id=<?php echo $item['id']; ?>"> 
 
    <div class="card-wrapper"> 
 
      <img 
        src="../../images/cards/<?php echo $colorMap[$index]; ?>.png" 
        class="card-bg"> 
 
      <img 
        src="<?php echo htmlspecialchars($signImage); ?>" 
        class="card-sign" 
        onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';"> 
 
      <div class="card-letter"> 
        <?php echo htmlspecialchars($item['number']); ?> 
      </div> 
 
    </div> 
 
  </a> 
 
<?php endforeach; ?> 
 
  </div> 
</section> 
 
</main> 
 
 
<!-- =========================================================
     SIGN LANGUAGE COMPONENT
     ========================================================= -->

<style> 
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
 
/* ممنوع أي خط أحمر على الداش أو البروفايل أو السكشن */ 
.sign-active-target, 
.nav-video-item.sign-active-target, 
.circle-icon.sign-active-target, 
.profile-btn.sign-active-target, 
.profile-video-box.sign-active-target, 
[data-sign-video].sign-active-target { 
  outline: none !important; 
  box-shadow: none !important; 
} 
</style> 
 
<div class="sign-floating"> 

  <button 
    class="sign-circle" 
    id="signToggleBtn" 
    onclick="toggleSignVideo()" 
    type="button" 
    title="لغة الإشارة"> 

    <img 
      src="../../assets/icons/sign-icon.png" 
      class="sign-icon" 
      alt="لغة الإشارة"> 

  </button> 
 
  <div class="sign-video-box" id="signVideoBox"> 

    <button 
      class="close-sign" 
      onclick="closeSignVideo()" 
      type="button">
      ×
    </button> 

    <div id="signHelperText" class="sign-helper-text"></div> 
 
    <div class="sign-video-wrap"> 

      <video 
        id="signVideo" 
        playsinline 
        muted 
        preload="auto"> 

        <source 
          id="signVideoSource" 
          src="<?php echo htmlspecialchars(videoCacheUrl('../../images/videos/english-numbers-intro.mp4')); ?>" 
          type="video/mp4"> 

      </video> 

      <button 
        class="sign-play-btn" 
        id="signPlayBtn" 
        onclick="playSignVideo()" 
        type="button">
        ▶
      </button> 

    </div> 
  </div> 
</div> 
 
<script> 
document.addEventListener("DOMContentLoaded", function () { 

  /* =========================================================
     البروفايل
     ========================================================= */

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


  /* =========================================================
     المتغيرات
     ========================================================= */

  window.sectionVideoStarted = false; 

  window.currentSectionVideo = "<?php 
      echo htmlspecialchars(
          videoCacheUrl('../../images/videos/english-numbers-intro.mp4'),
          ENT_QUOTES,
          'UTF-8'
      ); 
  ?>"; 

  window.currentSectionTarget = null; 
  window.currentSectionText = ""; 
  window.isDashboardHover = false; 
  window.isHeroHover = false; 


  /* =========================================================
     فتح صندوق المساعدة
     ========================================================= */

  window.isSignBoxOpen = function () { 
    const box = document.getElementById("signVideoBox"); 
    return box && box.classList.contains("active"); 
  }; 


  window.clearActiveSignTarget = function () { 
    document.querySelectorAll(".sign-active-target").forEach(el => { 
      el.classList.remove("sign-active-target"); 
    }); 
  }; 


  window.setActiveSignTarget = function (element) { 

    clearActiveSignTarget(); 

    if (!element) return; 

    const circle = 
      element.classList && element.classList.contains("circle-icon") 
        ? element 
        : element.querySelector(
            ".circle-icon, .nav-circle, .nav-video-circle"
          ); 

    if (circle) { 
      circle.classList.add("sign-active-target"); 
    } else { 
      element.classList.add("sign-active-target"); 
    } 
  }; 


  window.showSignText = function (text = "") { 

    const textBox = document.getElementById("signHelperText"); 

    if (!textBox) return; 

    textBox.textContent = text || ""; 

    textBox.style.display = 
      (text && text.trim() !== "") 
        ? "block" 
        : "none"; 
  }; 


  window.showPlayButton = function () { 

    const playBtn = document.getElementById("signPlayBtn"); 

    if (playBtn) { 
      playBtn.classList.remove("hide"); 
    } 
  }; 


  window.hidePlayButton = function () { 

    const playBtn = document.getElementById("signPlayBtn"); 

    if (playBtn) { 
      playBtn.classList.add("hide"); 
    } 
  }; 


  /* =========================================================
     أهم جزء:
     إجبار الفيديو على تحميل النسخة الجديدة
     ========================================================= */

  window.setVideoOnly = function (videoPath) { 

    const video = document.getElementById("signVideo"); 
    const source = document.getElementById("signVideoSource"); 

    if (!video || !source || !videoPath) return; 


    /*
     * إزالة أي cache version قديمة
     */
    let cleanPath = String(videoPath)
      .replace(/([?&])v=[^&]*/g, '$1')
      .replace(/([?&])refresh=[^&]*/g, '$1')
      .replace(/[?&]$/, '');


    /*
     * إضافة وقت جديد في كل تحميل.
     * بهذا الشكل المتصفح لا يستطيع استخدام نسخة MP4 القديمة.
     */
    const freshVideoPath = 
      cleanPath + 
      (cleanPath.includes("?") ? "&" : "?") + 
      "refresh=" + 
      Date.now(); 


    video.pause(); 
    video.currentTime = 0; 

    source.setAttribute("src", freshVideoPath); 

    video.load(); 
  }; 


  /* =========================================================
     تحديد فيديو القسم
     ========================================================= */

  window.setSignVideo = function (
    videoPath, 
    targetElement = null, 
    shouldPlay = false, 
    text = ""
  ) { 

    const box = document.getElementById("signVideoBox"); 
    const video = document.getElementById("signVideo"); 

    if (!videoPath || !video) return; 

    currentSectionVideo = videoPath; 
    currentSectionTarget = targetElement; 
    currentSectionText = text || ""; 

    isDashboardHover = false; 
    isHeroHover = false; 

    setVideoOnly(videoPath); 

    sectionVideoStarted = false; 

    showPlayButton(); 

    if (box && box.classList.contains("active")) { 

      setActiveSignTarget(targetElement); 
      showSignText(currentSectionText); 

      if (shouldPlay) { 

        video.play()
          .then(() => { 
            sectionVideoStarted = true; 
            hidePlayButton(); 
          })
          .catch(() => showPlayButton()); 
      } 
    } 
  }; 


  /* =========================================================
     فيديوهات الداش
     ========================================================= */

  window.openDashboardSignVideo = function (
    videoPath, 
    element = null, 
    text = ""
  ) { 

    const video = document.getElementById("signVideo"); 
    const box = document.getElementById("signVideoBox"); 

    if (
      !document.body.classList.contains("sign-open") || 
      !video || 
      !box
    ) return; 

    box.classList.add("active"); 

    isDashboardHover = true; 
    isHeroHover = false; 

    setVideoOnly(videoPath); 

    setActiveSignTarget(element); 

    showSignText(text); 

    video.currentTime = 0; 

    video.play()
      .then(() => hidePlayButton())
      .catch(() => showPlayButton()); 

    sectionVideoStarted = false; 
  }; 


  window.leaveDashboardSignVideo = function () { 

    const video = document.getElementById("signVideo"); 
    const box = document.getElementById("signVideoBox"); 

    if (
      !video || 
      !box || 
      !isDashboardHover
    ) return; 

    isDashboardHover = false; 

    clearActiveSignTarget(); 
    showSignText(""); 

    video.pause(); 
    video.currentTime = 0; 

    showPlayButton(); 

    box.classList.remove("active"); 
  }; 


  /* =========================================================
     فيديو الـ Hero إن وجد
     ========================================================= */

  window.openHeroSignVideo = function () { 

    const heroVisualVideo = document.getElementById("heroIntroVideo"); 
    const heroSignBox = document.getElementById("heroSignBox"); 

    if (!isSignBoxOpen()) return; 

    isHeroHover = true; 
    isDashboardHover = false; 

    clearActiveSignTarget(); 

    if (heroVisualVideo && heroSignBox) { 

      heroVisualVideo.currentTime = 0; 

      heroVisualVideo.play()
        .then(() => { 

          heroSignBox.classList.add("playing"); 

          const playBtn = document.getElementById("playBtn"); 

          if (playBtn) { 
            playBtn.style.display = "none"; 
          } 

        })
        .catch(() => {}); 
    } 
  }; 


  window.leaveHeroSignVideo = function () { 

    const heroVisualVideo = document.getElementById("heroIntroVideo"); 
    const heroSignBox = document.getElementById("heroSignBox"); 
    const playBtn = document.getElementById("playBtn"); 

    if (!isSignBoxOpen() || !isHeroHover) return; 

    isHeroHover = false; 

    if (heroVisualVideo && heroSignBox) { 

      heroVisualVideo.pause(); 
      heroVisualVideo.currentTime = 0; 
      heroSignBox.classList.remove("playing"); 
    } 

    if (playBtn) { 
      playBtn.style.display = "flex"; 
    } 
  }; 


  /* =========================================================
     فيديوهات صور الأرقام
     ========================================================= */

  window.openSignVideo = function (
    videoPath, 
    element = null, 
    text = ""
  ) { 

    const video = document.getElementById("signVideo"); 

    if (!isSignBoxOpen() || !video) return; 

    /*
     * الأرقام لا تشغل فيديو داخل زر المساعدة.
     * فقط نحافظ على السلوك الموجود.
     */
    if (!sectionVideoStarted) { 

      setActiveSignTarget(currentSectionTarget); 
      showSignText(currentSectionText); 

      return; 
    } 

    isDashboardHover = false; 
    isHeroHover = false; 

    setVideoOnly(videoPath); 

    setActiveSignTarget(element); 

    showSignText(text); 

    video.currentTime = 0; 

    video.play()
      .then(() => hidePlayButton())
      .catch(() => showPlayButton()); 
  }; 


  /* =========================================================
     تشغيل فيديو المساعدة
     ========================================================= */

  window.playSignVideo = function () { 

    const video = document.getElementById("signVideo"); 

    if (!video) return; 

    isDashboardHover = false; 
    isHeroHover = false; 

    sectionVideoStarted = true; 

    video.muted = true; 

    video.play()
      .then(() => hidePlayButton())
      .catch(() => showPlayButton()); 
  }; 


  /* =========================================================
     إغلاق الفيديو
     ========================================================= */

  window.closeSignVideo = function () { 

    const box = document.getElementById("signVideoBox"); 
    const toggleBtn = document.getElementById("signToggleBtn"); 
    const video = document.getElementById("signVideo"); 
    const heroVisualVideo = document.getElementById("heroIntroVideo"); 
    const heroSignBox = document.getElementById("heroSignBox"); 
    const playBtn = document.getElementById("playBtn"); 

    if (!box || !video) return; 

    box.classList.remove("active"); 

    if (toggleBtn) { 
      toggleBtn.classList.remove("active"); 
    } 

    document.body.classList.remove("sign-open"); 

    clearActiveSignTarget(); 
    showPlayButton(); 
    showSignText(""); 

    isDashboardHover = false; 
    isHeroHover = false; 

    video.pause(); 
    video.currentTime = 0; 

    if (heroVisualVideo && heroSignBox) { 

      heroVisualVideo.pause(); 
      heroVisualVideo.currentTime = 0; 
      heroSignBox.classList.remove("playing"); 
    } 

    if (playBtn) { 
      playBtn.style.display = "flex"; 
    } 

    if (currentSectionVideo) { 
      setVideoOnly(currentSectionVideo); 
    } 
  }; 


  /* =========================================================
     زر لغة الإشارة
     ========================================================= */

  window.toggleSignVideo = function () { 

    const box = document.getElementById("signVideoBox"); 
    const toggleBtn = document.getElementById("signToggleBtn"); 
    const video = document.getElementById("signVideo"); 

    if (!box) return; 

    if (document.body.classList.contains("sign-open")) { 

      closeSignVideo(); 

    } else { 

      fetch(
        '../../api/log-help.php',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'page=' + encodeURIComponent(window.location.pathname)
        }
      ).catch(function(){}); 


      if (toggleBtn) { 
        toggleBtn.classList.add("active"); 
      } 

      document.body.classList.add("sign-open"); 

      box.classList.remove("active"); 

      clearActiveSignTarget(); 
      showSignText(""); 

      isDashboardHover = false; 
      isHeroHover = false; 

      sectionVideoStarted = false; 


      if (video) { 

        video.pause(); 
        video.currentTime = 0; 
        video.muted = true; 
      } 

      showPlayButton(); 
    } 
  }; 


  /* =========================================================
     تحديد القسم الحالي
     ========================================================= */

  function updateActiveSection() { 

    if (isDashboardHover || isHeroHover) return; 

    const sections = document.querySelectorAll("[data-sign-video]"); 

    let closestSection = null; 
    let closestDistance = Infinity; 

    sections.forEach(section => { 

      const rect = section.getBoundingClientRect(); 

      const distance = Math.abs(rect.top - 140); 

      if (
        rect.top < window.innerHeight && 
        rect.bottom > 140
      ) { 

        if (distance < closestDistance) { 

          closestDistance = distance; 
          closestSection = section; 
        } 
      } 
    }); 


    if (closestSection) { 

      const text = 
        closestSection.dataset.signText || 
        closestSection.getAttribute("aria-label") || 
        ""; 

      setSignVideo(
        closestSection.dataset.signVideo, 
        closestSection, 
        false, 
        text
      ); 
    } 
  } 


  window.addEventListener("scroll", updateActiveSection); 
  window.addEventListener("load", updateActiveSection); 

  updateActiveSection(); 


  /* =========================================================
     Hover فيديوهات الداش
     ========================================================= */

  document
    .querySelectorAll("[data-dashboard-video]")
    .forEach(function (item) { 

      item.addEventListener("mouseenter", function () { 

        openDashboardSignVideo(
          item.dataset.dashboardVideo, 
          item, 
          item.dataset.dashboardText || ""
        ); 

      }); 


      item.addEventListener("focus", function () { 

        openDashboardSignVideo(
          item.dataset.dashboardVideo, 
          item, 
          item.dataset.dashboardText || ""
        ); 

      }); 


      item.addEventListener(
        "mouseleave", 
        leaveDashboardSignVideo
      ); 

      item.addEventListener(
        "blur", 
        leaveDashboardSignVideo
      ); 

    }); 


  /* =========================================================
     الأرقام:
     لا تشغل فيديو داخل زر المساعدة
     ========================================================= */

  const mainSignVideo = document.getElementById("signVideo"); 

  if (mainSignVideo) { 

    mainSignVideo.addEventListener(
      "ended", 
      function () { 
        showPlayButton(); 
      }
    ); 

    mainSignVideo.addEventListener(
      "pause", 
      function () { 

        if (!mainSignVideo.ended) { 
          showPlayButton(); 
        } 

      }
    ); 
  } 


  /* =========================================================
     Hero إن وجد في الصفحة
     ========================================================= */

  const heroBox = document.getElementById("heroBox"); 
  const heroIntroVideo = document.getElementById("heroIntroVideo"); 
  const heroPlayBtn = document.getElementById("playBtn"); 
  const heroSignBox = document.getElementById("heroSignBox"); 


  function isHelpOpenForHero() { 

    const box = document.getElementById("signVideoBox"); 

    return box && box.classList.contains("active"); 
  } 


  function playHeroVideo() { 

    if (
      !heroIntroVideo || 
      !heroPlayBtn || 
      !heroSignBox
    ) return; 

    heroIntroVideo.play()
      .then(() => { 

        heroSignBox.classList.add("playing"); 
        heroPlayBtn.style.display = "none"; 

      })
      .catch(() => {}); 
  } 


  function stopHeroVideo(reset = false) { 

    if (
      !heroIntroVideo || 
      !heroPlayBtn || 
      !heroSignBox
    ) return; 

    heroIntroVideo.pause(); 

    if (reset) { 
      heroIntroVideo.currentTime = 0; 
    } 

    heroSignBox.classList.remove("playing"); 
    heroPlayBtn.style.display = "flex"; 
  } 


  if (
    heroPlayBtn && 
    heroIntroVideo && 
    heroSignBox
  ) { 

    heroPlayBtn.addEventListener(
      "click", 
      function (e) { 

        e.preventDefault(); 
        e.stopPropagation(); 

        if (isHelpOpenForHero()) return; 

        if (heroIntroVideo.paused) { 
          playHeroVideo(); 
        } else { 
          stopHeroVideo(false); 
        } 
      }
    ); 


    heroIntroVideo.addEventListener(
      "click", 
      function (e) { 

        e.preventDefault(); 
        e.stopPropagation(); 

        if (isHelpOpenForHero()) return; 

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


  if (heroBox) { 

    heroBox.addEventListener(
      "mouseenter", 
      function () { 

        if (isSignBoxOpen()) { 
          openHeroSignVideo(); 
        } 

      }
    ); 


    heroBox.addEventListener(
      "mouseleave", 
      function () { 

        if (isSignBoxOpen()) { 
          leaveHeroSignVideo(); 
        } 

      }
    ); 
  } 

}); 
</script> 
 
</body> 
</html>