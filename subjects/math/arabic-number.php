<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../config/session_child.php';

$lang = $_SESSION['lang'] ?? 'ar';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/db.php';

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

function toArabicDigits($number) {
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    return str_replace($western, $arabic, (string)$number);
}

function filePathFromDb($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (strpos($path, '../') === 0 || strpos($path, '../../') === 0 || strpos($path, '/') === 0) {
        return $path;
    }

    return '../../' . ltrim($path, '/');
}

function defaultArabicNumberData($id) {
    $names = [
        0 => 'صفر',
        1 => 'واحد',
        2 => 'اثنان',
        3 => 'ثلاثة',
        4 => 'أربعة',
        5 => 'خمسة',
        6 => 'ستة',
        7 => 'سبعة',
        8 => 'ثمانية',
        9 => 'تسعة',
        10 => 'عشرة',
        20 => 'عشرون',
        30 => 'ثلاثون',
        40 => 'أربعون',
        50 => 'خمسون',
        60 => 'ستون',
        70 => 'سبعون',
        80 => 'ثمانون',
        90 => 'تسعون',
        100 => 'مئة'
    ];

    return [
        'db_id' => 0,
        'id' => (int)$id,
        'number' => toArabicDigits($id),
        'name' => 'الرقم ' . toArabicDigits($id),
        'word' => $names[(int)$id] ?? toArabicDigits($id),
        'sign_image' => 'images/signs/numbers/' . intval($id) . '.png',
        'number_image' => 'images/numbers/arabic/' . intval($id) . '.png',
        'coloring_image' => 'images/coloring/number-ar/' . intval($id) . '.png',
        'number_video' => ''
    ];
}

$defaultOrder = [0,1,2,3,4,5,6,7,8,9,10,20,30,40,50,60,70,80,90,100];
$numbersData = [];

foreach ($defaultOrder as $numId) {
    $numbersData[$numId] = defaultArabicNumberData($numId);
}

// قراءة من lessons (نفس الجدول الذي يستخدمه admin وmath.php)
$dbResult = mysqli_query($conn, "
    SELECT * FROM lessons
    WHERE subject_name = 'الرياضيات' AND lesson_type = 'الأرقام العربية'
    ORDER BY letter_id ASC, id ASC
");

if ($dbResult && mysqli_num_rows($dbResult) > 0) {
    // إذا في سجلات DB → نستخدمها فقط (تشمل الأرقام الجديدة)
    $numbersData = []; // امسح الافتراضيين — نعرض ما في DB فقط
    while ($row = mysqli_fetch_assoc($dbResult)) {
        $numId = intval($row['letter_id'] ?? -1);
        if ($numId < 0) continue;

        $baseData = defaultArabicNumberData($numId);
        $displayText = trim($row['lesson_title'] ?? '') !== '' ? trim($row['lesson_title']) : toArabicDigits($numId);
        $wordText    = trim($row['example_word'] ?? '') !== '' ? trim($row['example_word'])  : $baseData['word'];

        $numbersData[$numId] = [
            'db_id'         => intval($row['id'] ?? 0),
            'id'            => $numId,
            'number'        => $displayText,
            'name'          => 'الرقم ' . $displayText,
            'word'          => $wordText,
            'sign_image'    => trim($row['custom_sign']     ?? '') !== '' ? trim($row['custom_sign'])     : $baseData['sign_image'],
            'number_image'  => trim($row['lesson_image']    ?? '') !== '' ? trim($row['lesson_image'])    : $baseData['number_image'],
            'coloring_image'=> trim($row['coloring_image']  ?? '') !== '' ? trim($row['coloring_image'])  : $baseData['coloring_image'],
            'number_video'  => trim($row['card_video']      ?? '')
        ];
    }
}
// إذا DB فاضي → ابق على الافتراضيين (numbersData مملوء بالفعل أعلاه)

$numberOrder = array_keys($numbersData);
$knownOrder = array_flip($defaultOrder);

usort($numberOrder, function($a, $b) use ($knownOrder) {
    $aKnown = isset($knownOrder[$a]);
    $bKnown = isset($knownOrder[$b]);

    if ($aKnown && $bKnown) {
        return $knownOrder[$a] <=> $knownOrder[$b];
    }

    if ($aKnown) return -1;
    if ($bKnown) return 1;

    return $a <=> $b;
});

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

if (!isset($numbersData[$id])) {
    die("هذا الرقم غير موجود");
}

$item = $numbersData[$id];

/* تسجيل تقدّم الطفل في جدول progress */
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
track_progress($conn, 'lesson', 'arabic-number-' . $id, $item['number'] . ' – ' . $item['word']);

/* ── lesson_progress tracking ── */
if (isset($_SESSION['user_id']) && intval($item['db_id']) > 0) {
    $_lp_child = intval($_SESSION['user_id']);
    $_lp_lid   = intval($item['db_id']);
    $s2 = mysqli_prepare($conn,
        "INSERT INTO lesson_progress (child_id, lesson_id, completed_at) VALUES (?,?,NOW())"
    );
    // ✅ FIX: null check لمنع الأعطال لو جدول lesson_progress مش موجود
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'ii', $_lp_child, $_lp_lid);
        mysqli_stmt_execute($s2);
    }
}
/* ── نهاية lesson_progress ── */

$numberSignImage = filePathFromDb($item['sign_image']);
$numberMainImage = filePathFromDb($item['number_image']);
$numberVideoPath = filePathFromDb($item['number_video']);

if ($numberSignImage === '') {
    $numberSignImage = "../../images/signs/numbers/" . $id . ".png";
}

if ($numberMainImage === '') {
    $numberMainImage = "../../images/numbers/arabic/" . $id . ".png";
}

$currentIndex = array_search($id, $numberOrder, true);
$isFirst = ($currentIndex === 0);
$isLast = ($currentIndex === count($numberOrder) - 1);
$prevId = $isFirst ? $id : $numberOrder[$currentIndex - 1];
$nextId = $isLast ? $id : $numberOrder[$currentIndex + 1];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />

  <title><?php echo htmlspecialchars($item['name']); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=4">
  <link rel="stylesheet" href="../../assets/css/letter-style.css?v=2">
  <link rel="stylesheet" href="../../assets/css/number-style.css?v=2">

  <style>
    .content-layout {
      direction: ltr;
    }

    .content-layout > * {
      direction: rtl;
    }

    .number-actions-card {
      padding: 24px;
      border-radius: 30px;
    }

    .number-action-row {
      display: flex;
      flex-direction: column;
      gap: 22px;
    }

    .number-action-card {
      min-height: 140px;
      border-radius: 36px;
      padding: 14px 22px;
      display: grid;
      grid-template-columns: 125px 3px 1fr 46px;
      align-items: center;
      gap: 20px;
      text-decoration: none;
      box-shadow: 0 14px 28px rgba(37, 80, 130, 0.13);
      transition: 0.25s ease;
    }

    .number-action-card:hover {
      transform: translateY(-4px);
    }

    .numbers-game-card {
      background: linear-gradient(135deg, #fff8ff, #f6edff);
      border: 2px dashed #c28af2;
    }

    .number-color-card {
      background: linear-gradient(135deg, #f6fff8, #edfff4);
      border: 2px dashed #82e7a5;
    }

    .number-video-circle {
      width: 125px;
      height: 125px;
      border-radius: 50%;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow: 0 10px 22px rgba(0,0,0,0.12);
    }

    .number-video-circle video {
      width: 88%;
      height: 88%;
      object-fit: cover;
      border-radius: 50%;
    }

    .number-action-divider {
      height: 82px;
      width: 3px;
      border-radius: 20px;
    }

    .numbers-game-card .number-action-divider {
      background: #9b59d0;
    }

    .number-color-card .number-action-divider {
      background: #36bf7a;
    }

    .number-action-text h3 {
      margin: 0 0 8px;
      font-size: 25px;
      font-weight: 900;
    }

    .numbers-game-card h3 {
      color: #7a37af;
    }

    .number-color-card h3 {
      color: #168653;
    }

    .number-action-text p {
      margin: 0;
      font-size: 16px;
      font-weight: 800;
      line-height: 1.7;
      color: #17486c;
    }

    .number-action-arrow {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      color: #fff;
      font-size: 42px;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      padding-bottom: 5px;
    }

    .numbers-game-card .number-action-arrow {
      background: #8d3cc1;
    }

    .number-color-card .number-action-arrow {
      background: #35bd7b;
    }

    .number-stage-custom {
      position: relative;
      min-height: 520px !important;
      overflow: hidden;
    }

    .number-sign-fixed {
      position: absolute;
      top: 34px;
      right: 44px;
      width: 125px !important;
      height: 125px !important;
      border-radius: 50%;
      background: #ead7c8;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      z-index: 5;
      box-shadow: 0 10px 22px rgba(0,0,0,0.10);
    }

    .number-sign-fixed img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .number-big-frame {
      width: 320px !important;
      height: 390px !important;
      margin: 35px auto 0 !important;
      border: 3px dashed #111;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 14px;
    }

    .big-number-image {
      width: 120% !important;
      height: 110% !important;
      object-fit: contain !important;
      display: block;
      max-width: none !important;
      max-height: none !important;
    }

    .children-dash {
      width: calc(100% - 30px) !important;
      max-width: 1520px !important;
      margin: 14px auto 0 !important;
    }

    .children-dash-inner {
      min-height: 2px !important;
      padding: 18px 34px !important;
      align-items: center !important;
    }

    .dash-nav {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 28px !important;
      height: 100% !important;
      overflow: visible !important;
    }

    .dash-nav .circle-icon {
      width: 86px !important;
      height: 86px !important;
      position: relative !important;
      overflow: visible !important;
      align-self: center !important;
      top: -10px !important;
      border-radius: 50% !important;
    }

    .dash-nav .circle-icon video,
    .dash-nav .circle-icon img {
      width: 92% !important;
      height: 92% !important;
      border-radius: 50% !important;
      object-fit: cover !important;
      pointer-events: none !important;
      display: block !important;
    }

    .dash-hover-label {
      position: absolute !important;
      left: 50% !important;
      top: calc(100% + 12px) !important;
      transform: translateX(-50%) !important;
      min-width: 145px !important;
      padding: 10px 16px !important;
      border-radius: 999px !important;
      background: linear-gradient(135deg, #ff7bb2, #ff4f8f) !important;
      color: #fff !important;
      font-size: 14px !important;
      font-weight: 900 !important;
      font-family: "Cairo", Arial, sans-serif !important;
      text-align: center !important;
      white-space: nowrap !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      transition: 0.2s ease !important;
      box-shadow: 0 8px 18px rgba(255, 79, 143, 0.35) !important;
      z-index: 99999 !important;
    }

    .dash-nav .circle-icon:hover .dash-hover-label,
    .dash-nav .circle-icon:focus .dash-hover-label {
      opacity: 1 !important;
      visibility: visible !important;
    }

    .nav-row {
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      gap: 14px !important;
      margin-top: 18px !important;
    }

    .arrow-btn,
    .menu-btn {
      width: 210px !important;
      height: 52px !important;
      border-radius: 18px !important;
      background: #ffffff !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      box-shadow: 0 9px 20px rgba(37, 80, 130, 0.13) !important;
      position: relative !important;
      text-decoration: none !important;
      transition: 0.25s ease !important;
    }

    .arrow-btn:hover,
    .menu-btn:hover {
      transform: translateY(-3px) scale(1.03) !important;
    }

    .arrow-btn.disabled {
      opacity: 0.45 !important;
      pointer-events: none !important;
    }

    .arrow-img {
      width: 38px !important;
      height: 38px !important;
      object-fit: contain !important;
      display: block !important;
    }

    .arrow-img.prev {
      transform: scaleX(-1) !important;
    }

    .arrow-img.next {
      transform: none !important;
    }

    .menu-img {
      width: 38px !important;
      height: 38px !important;
      object-fit: contain !important;
      display: block !important;
    }

    .arrow-btn::after,
    .menu-btn::after {
      content: attr(data-title) !important;
      position: absolute !important;
      bottom: -32px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      background: linear-gradient(135deg, #ef458c, #ff4f8f) !important;
      color: white !important;
      font-size: 20px !important;
      font-weight: 900 !important;
      padding: 8px 16px !important;
      border-radius: 999px !important;
      opacity: 0 !important;
      visibility: hidden !important;
      transition: 0.2s !important;
      white-space: nowrap !important;
      z-index: 9999 !important;
    }

    .arrow-btn:hover::after,
    .menu-btn:hover::after {
      opacity: 1 !important;
      visibility: visible !important;
    }

    .letter-card {
      transform: scale(0.92) !important;
      transform-origin: top center !important;
    }

    .top-title h2 {
      font-size: 30px !important;
      font-weight: 900 !important;
    }

    @media (max-width: 900px) {
      .number-big-frame {
        width: 300px !important;
        height: 380px !important;
      }

      .number-sign-fixed {
        width: 115px !important;
        height: 115px !important;
        right: 25px !important;
      }

      .arrow-btn,
      .menu-btn {
        width: 90px !important;
      }
    }

    .number-big-frame {
      width:420px !important;
      height: 500px !important;
      padding: 18px !important;
      border-width: 4px !important;
    }

    .big-number-image {
      width:115% !important;
      height: 108% !important;
    }

    .number-sign-fixed {
      width: 145px !important;
      height: 145px !important;
      top: 20px !important;
      right: 35px !important;
    }

    /* ===== MOBILE ===== */
    @media (max-width: 768px) {
      html, body { overflow-y: auto !important; height: auto !important; }
    }

    @media (max-width: 900px) {
      .content-layout { grid-template-columns: 1fr !important; margin-top: 0 !important; gap: 14px; }
      .right-panel { order: -1; }
      .left-panel  { order: 1; }
      .letter-card { transform: none !important; }
      .page-wrap { margin-top: 12px !important; }
      .top-title { text-align: center; }
    }

    @media (max-width: 768px) {
      .children-dash-inner {
        display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important;
        align-items: center !important; padding: 8px 10px !important;
        height: 64px !important; min-height: 64px !important; gap: 6px !important;
      }
      .dash-start { flex: 0 0 40px !important; }
      .logo-box img { width: 40px !important; height: 40px !important; }
      .logo-text { display: none !important; }
      .dash-nav {
        flex: 1 1 0 !important; display: flex !important; flex-direction: row !important;
        align-items: center !important; justify-content: center !important;
        gap: 12px !important; transform: none !important; overflow: visible !important;
      }
      .dash-nav .circle-icon { top: 0 !important; width: 44px !important; height: 44px !important; }
      .dash-nav .circle-icon video, .dash-nav .circle-icon img { width: 38px !important; height: 38px !important; }
      .dash-hover-label { opacity: 0 !important; pointer-events: none !important; }
      .profile-wrap { flex: 0 0 auto !important; }
      .profile-btn { padding: 5px 8px !important; gap: 5px !important; }
      .profile-video-box { width: 36px !important; height: 36px !important; }
      .profile-hello { font-size: 11px !important; display: block !important; max-width: 70px !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
      .profile-menu { left: 0 !important; right: auto !important; }

      .letter-card { padding: 0 !important; margin-top: 6px !important; border-radius: 16px !important; overflow: hidden !important; }
      .image-stage { padding: 0 !important; border-radius: 16px !important; }
      .number-stage-custom { min-height: 0 !important; }
      .number-big-frame { width: 100% !important; height: 270px !important; margin: 0 !important; border-radius: 16px !important; padding: 0 !important; border: none !important; }
      .number-sign-fixed { width: 80px !important; height: 80px !important; top: 8px !important; right: 8px !important; }
      .big-number-image { width: 110% !important; height: 110% !important; }

      .number-action-card {
        min-height: 90px !important; grid-template-columns: 70px 3px 1fr 36px !important;
        gap: 10px !important; padding: 10px 14px !important; border-radius: 22px !important;
      }
      .number-video-circle { width: 66px !important; height: 66px !important; }
      .number-action-text h3 { font-size: 18px !important; }
      .number-action-text p { font-size: 13px !important; }
      .arrow-btn { width: 52px !important; height: 52px !important; border-radius: 50% !important; }
      .menu-btn  { width: 110px !important; height: 52px !important; }
    }

    @media (max-width: 540px) {
      .number-big-frame { height: 240px !important; }
      .number-sign-fixed { width: 70px !important; height: 70px !important; }
      .number-action-card { grid-template-columns: 58px 2px 1fr 30px !important; }
      .number-video-circle { width: 54px !important; height: 54px !important; }
      .arrow-btn { width: 44px !important; height: 44px !important; }
      .menu-btn  { width: 90px !important; height: 44px !important; }
    }
  </style>
</head>

<body>

<div class="bubbles-bg">
  <span class="bubble b1"></span>
  <span class="bubble b2"></span>
  <span class="bubble b3"></span>
  <span class="bubble b4"></span>
  <span class="bubble b5"></span>
  <span class="bubble b6"></span>
  <span class="bubble b7"></span>
  <span class="bubble b8"></span>
</div>

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
         aria-label="<?php echo ($lang === 'ar') ? 'العودة إلى الصفحة الرئيسية' : 'Back to home page'; ?>">

        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="../../assets/icons/children.mp4" type="video/mp4">
        </video>

        <span class="dash-hover-label">الرئيسية</span>
      </a>

      <a href="../../subjects/math/math.php"
         class="circle-icon home-icon"
         tabindex="0"
         aria-label="قسم الأرقام العربية">

        <img src="../../assets/icons/math-ar.png" alt="Numbers">

        <span class="dash-hover-label">الأرقام العربية</span>
      </a>
    </nav>

    <div class="profile-wrap">
      <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
        <span class="profile-hello">
          مرحباً <?php echo htmlspecialchars($child_display_name); ?>
        </span>

        <div class="profile-video-box">
          <video autoplay muted loop playsinline>
            <source src="../../assets/icons/profile.mp4" type="video/mp4">
          </video>
        </div>
      </button>

      <div class="profile-menu" id="profileMenu">
        <a href="../../auth/account_settings.php">إعدادات الحساب</a>
        <a href="../../auth/logout.php">تسجيل الخروج</a>
      </div>
    </div>

  </div>
</header>

<main class="page-wrap">

  <section class="top-title">
    <h2><?php echo htmlspecialchars($item['name']); ?></h2>
  </section>

  <section class="content-layout">

    <div class="left-panel">

      <div class="speech-card letter-learning-card number-actions-card">
        <div class="number-action-row">

          <a href="numbers-game.php?id=<?php echo $id; ?>&from=arabic"
             class="number-action-card numbers-game-card">

            <div class="number-video-circle">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/numbers.mp4" type="video/mp4">
              </video>
            </div>

            <div class="number-action-divider"></div>

            <div class="number-action-text">
              <h3>لعبة الأرقام</h3>
              <p>تعرّف على الرقم</p>
              <p>بطريقة ممتعة</p>
            </div>

            <div class="number-action-arrow">›</div>
          </a>

          <a href="../coloring.php?type=number-ar&id=<?php echo $id; ?>"
             class="number-action-card number-color-card">

            <div class="number-video-circle">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/coloring.mp4" type="video/mp4">
              </video>
            </div>

            <div class="number-action-divider"></div>

            <div class="number-action-text">
              <h3>تلوين الرقم</h3>
              <p>لوّن الرقم</p>
              <p>بطريقتك المفضلة</p>
            </div>

            <div class="number-action-arrow">›</div>
          </a>

        </div>
      </div>

      <section class="nav-row">

        <a
          href="arabic-number.php?id=<?php echo $prevId; ?>"
          class="arrow-btn <?php echo $isFirst ? 'disabled' : ''; ?>"
          data-title="الرقم السابق"
          aria-label="الرقم السابق"
          <?php echo $isFirst ? 'onclick="return false;"' : ''; ?>
        >
          <img src="../../assets/icons/arrow.png" class="arrow-img prev" alt="السابق">
        </a>

        <a href="arabic-numbers.php" class="menu-btn" data-title="كل الأرقام">
          <img src="../../assets/icons/menu.png" class="menu-img" alt="كل الأرقام">
        </a>

        <a
          href="arabic-number.php?id=<?php echo $nextId; ?>"
          class="arrow-btn <?php echo $isLast ? 'disabled' : ''; ?>"
          data-title="الرقم التالي"
          aria-label="الرقم التالي"
          <?php echo $isLast ? 'onclick="return false;"' : ''; ?>
        >
          <img src="../../assets/icons/arrow.png" class="arrow-img next" alt="التالي">
        </a>

      </section>

    </div>

    <div class="right-panel">
      <div class="letter-card">
        <div class="image-stage number-stage-custom">

          <div class="number-sign-fixed">
            <img
              src="<?php echo htmlspecialchars($numberSignImage); ?>"
              alt="إشارة <?php echo htmlspecialchars($item['name']); ?>"
              onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';"
            />
          </div>

          <div class="number-big-frame">
            <img
              src="<?php echo htmlspecialchars($numberMainImage); ?>"
              alt="<?php echo htmlspecialchars($item['name']); ?>"
              class="main-image big-number-image"
              onerror="this.onerror=null;this.src='../../assets/images/no-image.png';"
            />
          </div>

        </div>
      </div>
    </div>

  </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
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
});

document.addEventListener("keydown", function(e) {
    const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"];

    if (keys.includes(e.key)) {
        e.preventDefault();
    }
});
</script>
</body>
</html>
