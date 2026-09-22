<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/session_child.php';

require_once '../../config/db.php';

$lang = $_SESSION['lang'] ?? 'ar';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$sql = "SELECT * FROM arabic_letter_examples WHERE letter_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$dbResult = mysqli_stmt_get_result($stmt);
$dbItem = mysqli_fetch_assoc($dbResult);

if (!$dbItem) {
    die("هذا الحرف غير موجود");
}

$mainExampleWord = trim($dbItem['example_word'] ?? '');

if ($mainExampleWord === '') {
    $mainExampleWord = trim($dbItem['start_word'] ?? '');
}

$item = [
    "letter" => $dbItem["letter_text"],
    "name"   => $dbItem["letter_text"],
    "word"   => $mainExampleWord
];

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
track_progress($conn, 'lesson', 'arabic-letter-' . $id, $item['letter']);

/* ── lesson_progress tracking ── */
if (isset($_SESSION['user_id']) && $dbItem) {
    $_lp_child = intval($_SESSION['user_id']);
    $_lp_lid   = intval($dbItem['id']);
    $s2 = mysqli_prepare($conn,
        "INSERT INTO lesson_progress (child_id, lesson_id, completed_at) VALUES (?,?,NOW())"
    );
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'ii', $_lp_child, $_lp_lid);
        mysqli_stmt_execute($s2);
    }
}
/* ── نهاية lesson_progress ── */

$teachingOrder = [
    1, 27, 28, 2, 12, 10, 24,
    3, 23, 6, 20, 8, 25, 18,
    11, 21, 7, 14, 13, 19,
    22, 5, 16, 4, 9, 15,
    26, 17
];

$orderSql = implode(',', $teachingOrder);

$orderResult = mysqli_query(
    $conn,
    "
    SELECT letter_id
    FROM arabic_letter_examples
    ORDER BY
    CASE
        WHEN letter_id IN ($orderSql) THEN 0
        ELSE 1
    END,
    FIELD(letter_id, $orderSql),
    letter_id ASC,
    id ASC
    "
);

$letterOrder = [];
if ($orderResult) {
    while ($row = mysqli_fetch_assoc($orderResult)) {
        $lid = (int)$row['letter_id'];
        if (!in_array($lid, $letterOrder, true)) {
            $letterOrder[] = $lid;
        }
    }
}

if (empty($letterOrder)) {
    $letterOrder[] = $id;
}

$currentIndex = array_search($id, $letterOrder, true);

if ($currentIndex === false) {
    die("هذا الحرف غير موجود");
}

$isFirst = $currentIndex === 0;
$isLast = $currentIndex === count($letterOrder) - 1;

$prevId = $isFirst ? $id : $letterOrder[$currentIndex - 1];
$nextId = $isLast ? $id : $letterOrder[$currentIndex + 1];

$buttonWords = [
    "start"  => $mainExampleWord,
    "middle" => $dbItem["middle_word"],
    "end"    => $dbItem["end_word"],
    "alone"  => $dbItem["letter_text"]
];

$mainCardImage = !empty($dbItem['card_image'])
    ? "../../" . $dbItem['card_image']
    : "../../images/letters/arabic/" . $id . ".png";

$startImage = $mainCardImage;

$defaultSignRelativePath = "../../images/signs/arabic/" . $id . ".png";
$defaultSignServerPath = __DIR__ . "/../../images/signs/arabic/" . $id . ".png";

if (file_exists($defaultSignServerPath)) {
    $signImagePath = $defaultSignRelativePath;
} elseif (!empty($dbItem['custom_sign'])) {
    $signImagePath = "../../" . ltrim($dbItem['custom_sign'], '/');
} else {
    $signImagePath = "../../assets/icons/sign-icon.png";
}

$middleImage = !empty($dbItem['middle_image'])
    ? "../../" . $dbItem['middle_image']
    : "../../images/examples/" . $id . "/middle.png";

$endImage = !empty($dbItem['end_image'])
    ? "../../" . $dbItem['end_image']
    : "../../images/examples/" . $id . "/end.png";

$aloneImage = "../../images/examples/" . $id . "/alone.png";

$startVideo = !empty($dbItem['start_video'])
    ? "../../" . $dbItem['start_video']
    : "";

$middleVideo = !empty($dbItem['middle_video'])
    ? "../../" . $dbItem['middle_video']
    : "";

$endVideo = !empty($dbItem['end_video'])
    ? "../../" . $dbItem['end_video']
    : "";

function colorSelectedLetter($word, $letter) {
    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
    $done = false;
    $result = '';

    foreach ($chars as $ch) {
        if (!$done && ($ch === $letter || ($letter === 'أ' && ($ch === 'أ' || $ch === 'ا')) || ($letter === 'هـ' && $ch === 'ه'))) {
            $result .= '<span class="selected-letter-red">' . htmlspecialchars($ch) . '</span>';
            $done = true;
        } else {
            $result .= htmlspecialchars($ch);
        }
    }

    return $result;
}

$charToId = [
    "أ" => 1, "ا" => 1, "إ" => 1, "آ" => 1,
    "ب" => 2,
    "ت" => 3,
    "ث" => 4,
    "ج" => 5,
    "ح" => 6,
    "خ" => 7,
    "د" => 8,
    "ذ" => 9,
    "ر" => 10,
    "ز" => 11,
    "س" => 12,
    "ش" => 13,
    "ص" => 14,
    "ض" => 15,
    "ط" => 16,
    "ظ" => 17,
    "ع" => 18,
    "غ" => 19,
    "ف" => 20,
    "ق" => 21,
    "ك" => 22,
    "ل" => 23,
    "م" => 24,
    "ن" => 25,
    "ه" => 26, "هـ" => 26,
    "و" => 27,
    "ي" => 28, "ى" => 28,
    "ة" => 29,
    "ء" => 30
];

if (!isset($charToId[$item['letter']])) {
    $charToId[$item['letter']] = $id;
}

$customSignMap = [];

$customSignSql = "
SELECT letter_id, custom_sign
FROM arabic_letter_examples
WHERE custom_sign IS NOT NULL
AND custom_sign != ''
";

$customSignResult = mysqli_query($conn, $customSignSql);

if ($customSignResult) {
    while ($customRow = mysqli_fetch_assoc($customSignResult)) {
        $customLetterId = (int)$customRow['letter_id'];
        $customPath = trim($customRow['custom_sign']);

        if ($customLetterId > 0 && $customPath !== '') {
            $customSignMap[$customLetterId] = "../../" . ltrim($customPath, '/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>حرف <?php echo htmlspecialchars($item['name']); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=2">
  <link rel="stylesheet" href="../../assets/css/letter-style.css?v=2" />

  <style>
.arabic-learning-card {
  transform: translateY(-50px) !important;
  transform-origin: top center !important;
  overflow: visible !important;
}

.left-panel .nav-row {
  margin-top: -20px !important;
  transform: translateY(-25px) !important;
}

.nav-row {
  margin-top: -2px !important;
  transform: translateY(-5px) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 14px !important;
}

.nav-row .arrow-btn {
  width: 58px !important;
  height: 58px !important;
  min-width: 58px !important;
  padding: 0 !important;
  border-radius: 50% !important;
  background: #ffffff !important;
  border: 5px solid #fff !important;
  box-shadow: 0 8px 18px rgba(34, 100, 130, 0.18) !important;
}

.nav-row .menu-btn {
  width: 118px !important;
  height: 58px !important;
  min-width: 118px !important;
  padding: 0 !important;
  border-radius: 24px !important;
  background: #ffffff !important;
  border: 5px solid #fff !important;
  box-shadow: 0 8px 18px rgba(34, 100, 130, 0.18) !important;
}

.nav-row .arrow-img {
  width: 38px !important;
  height: 38px !important;
}

.nav-row .menu-img {
  width: 42px !important;
  height: 42px !important;
}

.nav-row .arrow-btn::after,
.nav-row .menu-btn::after {
  bottom: -32px !important;
  font-size: 18px !important;
  padding: 6px 12px !important;
}

#bigCenterLetter {
    position: absolute;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 260px;
    font-weight: 900;
    color: #000;
    z-index: 20;
    pointer-events: none;
    font-family: Arial, sans-serif;
    line-height: 1;
    background: #ffffff;
}

/* ===== أمثلة الحرف - تفتح تحت الزر مباشرة ===== */
.arabic-action-row {
  overflow: visible !important;
}

.examples-hidden-panel {
  position: relative !important;
  inset: auto !important;
  width: 100% !important;
  max-height: 0 !important;
  overflow: hidden !important;
  opacity: 0 !important;
  visibility: hidden !important;
  padding: 0 !important;
  margin: 0 !important;
  border-radius: 22px !important;
  transform: none !important;
  box-shadow: none !important;
  pointer-events: none !important;
  transition: max-height 0.35s ease, opacity 0.25s ease, padding 0.25s ease, margin 0.25s ease !important;
}

.examples-hidden-panel.show {
  max-height: 800px !important;
  opacity: 1 !important;
  visibility: visible !important;
  padding: 16px !important;
  margin-top: 10px !important;
  pointer-events: auto !important;
  box-shadow: 0 18px 35px rgba(199, 54, 131, 0.18) !important;
  overflow: visible !important;
}

/* ===== DASHBOARD LIKE ENGLISH LETTER PAGE - DESKTOP ONLY ===== */
@media (min-width: 901px) {
  .kids-dashboard.children-dash{
      width:calc(100% - 30px) !important;
      max-width:1480px !important;
      margin:12px auto 0 !important;
      position:sticky !important;
      top:8px !important;
      z-index:1000 !important;
  }

  .kids-dashboard .children-dash-inner{
      background:linear-gradient(135deg,#dff4ff,#cfeeff) !important;
      border-radius:30px !important;
      padding:12px 30px !important;
      min-height:105px !important;
      height:105px !important;
      display:grid !important;
      grid-template-columns:300px 1fr 300px !important;
      align-items:center !important;
      gap:18px !important;
      box-shadow:0 10px 24px rgba(44,111,170,.14) !important;
  }

  .kids-dashboard .dash-start{
      display:flex !important;
      align-items:center !important;
      justify-content:flex-start !important;
      height:100% !important;
  }

  .kids-dashboard .dash-start .dash-end{
      display:flex !important;
      align-items:center !important;
      justify-content:flex-start !important;
      height:100% !important;
  }

  .kids-dashboard .logo-box{
      display:flex !important;
      align-items:center !important;
      justify-content:center !important;
      text-decoration:none !important;
      height:100% !important;
  }

  .kids-dashboard .logo-box img{
      width:82px !important;
      height:auto !important;
      object-fit:contain !important;
      display:block !important;
  }

  .kids-dashboard .dash-nav{
      display:flex !important;
      align-items:center !important;
      justify-content:center !important;
      gap:24px !important;
      height:100% !important;
      padding:0 !important;
      margin:0 !important;
      overflow:visible !important;
  }

  .kids-dashboard .circle-icon{
      width:74px !important;
      height:74px !important;
      border-radius:50% !important;
      overflow:visible !important;
      display:flex !important;
      align-items:center !important;
      justify-content:center !important;
      background:#fff !important;
      text-decoration:none !important;
      position:relative !important;
      box-shadow:0 8px 18px rgba(0,0,0,.13) !important;
      transition:.25s ease !important;
      flex-shrink:0 !important;
      top:-15px !important;
  }

  .kids-dashboard .circle-icon:hover{
      transform:translateY(-3px) scale(1.04) !important;
      box-shadow:0 11px 22px rgba(0,0,0,.18) !important;
  }

  .kids-dashboard .circle-icon video,
  .kids-dashboard .circle-icon img{
      width:100% !important;
      height:100% !important;
      object-fit:cover !important;
      border-radius:50% !important;
      display:block !important;
      pointer-events:none !important;
  }

  .kids-dashboard .dash-hover-label{
      position:absolute !important;
      left:50% !important;
      top:calc(100% + 10px) !important;
      transform:translateX(-50%) !important;
      min-width:125px !important;
      padding:8px 16px !important;
      border-radius:999px !important;
      background:linear-gradient(135deg,#ff7bb2,#ff4f8f) !important;
      color:#fff !important;
      font-size:15px !important;
      font-weight:900 !important;
      font-family:"Cairo",Arial,sans-serif !important;
      white-space:nowrap !important;
      opacity:0 !important;
      visibility:hidden !important;
      pointer-events:none !important;
      transition:.2s ease !important;
      z-index:99999 !important;
      text-align:center !important;
      box-shadow:0 8px 18px rgba(255,79,143,.35) !important;
  }

  .kids-dashboard .circle-icon:hover .dash-hover-label,
  .kids-dashboard .circle-icon:focus .dash-hover-label{
      opacity:1 !important;
      visibility:visible !important;
  }

  .kids-dashboard .profile-wrap{
      position:relative !important;
      display:flex !important;
      align-items:center !important;
      justify-content:flex-end !important;
      height:100% !important;
  }

  .kids-dashboard .profile-btn{
      border:none !important;
      background:rgba(255,255,255,.95) !important;
      border-radius:999px !important;
      padding:8px 16px 8px 10px !important;
      display:flex !important;
      align-items:center !important;
      gap:10px !important;
      cursor:pointer !important;
      box-shadow:0 8px 18px rgba(44,111,170,.10) !important;
      font-family:"Cairo",Arial,sans-serif !important;
  }

  .kids-dashboard .profile-video-box{
      width:54px !important;
      height:54px !important;
      border-radius:50% !important;
      overflow:hidden !important;
      flex-shrink:0 !important;
      border:3px solid #fff !important;
  }

  .kids-dashboard .profile-video-box video{
      width:100% !important;
      height:100% !important;
      object-fit:cover !important;
      display:block !important;
  }

  .kids-dashboard .profile-hello{
      font-size:17px !important;
      font-weight:900 !important;
      color:#214f7d !important;
      white-space:nowrap !important;
  }

  .kids-dashboard .profile-menu{
      position:absolute !important;
      top:calc(100% + 10px) !important;
      right:0 !important;
      min-width:230px !important;
      background:#fff !important;
      border-radius:24px !important;
      box-shadow:0 18px 35px rgba(0,0,0,.10) !important;
      padding:12px 0 !important;
      opacity:0 !important;
      visibility:hidden !important;
      transform:translateY(8px) !important;
      transition:.22s ease !important;
      z-index:1001 !important;
  }

  .kids-dashboard .profile-menu.show{
      opacity:1 !important;
      visibility:visible !important;
      transform:translateY(0) !important;
  }

  .kids-dashboard .profile-menu a{
      display:block !important;
      text-decoration:none !important;
      color:#214f7d !important;
      font-size:18px !important;
      font-weight:900 !important;
      padding:16px 22px !important;
      transition:.2s ease !important;
  }

  .kids-dashboard .profile-menu a:hover{
      background:#f3f9ff !important;
  }
}

/* ===== RESPONSIVE ===== */

@media (max-width: 900px) {
  .content-layout {
    grid-template-columns: 1fr !important;
    margin-top: 0 !important;
    gap: 14px;
  }

  .right-panel { order: -1; }
  .left-panel  { order: 1; }

  .image-stage { height: 380px !important; }

  .letter-card  { transform: none !important; }
  .speech-card  { transform: none !important; }

  .arabic-learning-card {
    transform: none !important;
    min-height: auto !important;
    overflow: visible !important;
  }

  .left-panel,
  .speech-card,
  .letter-learning-card {
    overflow: visible !important;
  }

  .letter-main-title {
    font-size: 42px !important;
    transform: none !important;
  }

  .top-title {
    margin-top: 0 !important;
    margin-bottom: 8px !important;
    text-align: center;
  }

  .page-wrap    { margin-top: 12px !important; }
  .content-layout { margin-top: 0 !important; }

  .example-side-container {
    left: 12px !important;
    top: auto !important;
    bottom: 140px !important;
    transform: none !important;
  }

  .example-video-wrap {
    width: 140px !important;
    height: 140px !important;
  }

  .example-side-word {
    font-size: 28px !important;
    min-width: 100px !important;
  }

  .bottom-spelling-circle { width: 72px !important; height: 72px !important; }
  .bottom-spelling-circles { gap: 14px !important; }

  .left-panel .nav-row {
    transform: none !important;
    margin-top: 10px !important;
  }
}

@media (max-width: 768px) {
  .children-dash { width: calc(100% - 16px) !important; top: 6px; }

  /* الهيدر: صف واحد - min-width:0 مهم لمنع الـ overflow */
  .kids-dashboard .children-dash-inner {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 8px 10px !important;
    height: 64px !important;
    min-height: 64px !important;
    max-height: 64px !important;
    gap: 6px !important;
  }

  /* شعار */
  .kids-dashboard .dash-start { flex: 0 0 40px !important; min-width: 0 !important; height: auto !important; }
  .kids-dashboard .dash-start .dash-end { display: flex !important; align-items: center !important; height: auto !important; }
  .kids-dashboard .logo-box { height: auto !important; }
  .kids-dashboard .logo-box img { width: 40px !important; height: 40px !important; }
  .kids-dashboard .logo-text { display: none !important; }

  /* أيقونات الناف */
  .kids-dashboard .dash-nav {
    flex: 1 1 0 !important;
    min-width: 0 !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 12px !important;
    height: 48px !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
  }

  /* بروفايل */
  .kids-dashboard .profile-wrap { flex: 0 0 auto !important; min-width: 0 !important; height: auto !important; }
  .kids-dashboard .profile-btn { padding: 5px 8px !important; gap: 5px !important; }
  .kids-dashboard .profile-video-box { width: 36px !important; height: 36px !important; }
  .kids-dashboard .profile-hello { font-size: 11px !important; display: block !important; max-width: 70px !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
  .kids-dashboard .profile-menu { left: 0 !important; right: auto !important; }

  .kids-dashboard .circle-icon { top: 0 !important; width: 44px !important; height: 44px !important; overflow: hidden !important; position: relative !important; }
  .kids-dashboard .circle-icon video,
  .kids-dashboard .circle-icon img { width: 38px !important; height: 38px !important; }
  /* .nav-text مش absolute على الموبايل - بنخبيه */
  .kids-dashboard .dash-nav .nav-text,
  .kids-dashboard .circle-icon .nav-text,
  .kids-dashboard .circle-icon .dash-hover-label {
    position: absolute !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }

  .image-stage { height: 320px !important; padding: 10px !important; }
  .image-stage-with-signs { padding-bottom: 110px !important; }

  .main-sign-circle {
    width: 100px !important; height: 100px !important;
    top: 12px !important; right: 12px !important;
  }

  .arabic-action-card {
    min-height: 100px !important;
    grid-template-columns: 72px 3px 1fr 36px !important;
    gap: 10px !important;
    padding: 10px 14px !important;
  }

  .arabic-video-circle { width: 68px !important; height: 68px !important; }
  .arabic-action-text h3 { font-size: 18px !important; }
  .arabic-action-text p  { font-size: 13px !important; }
  .click-card { font-size: 28px !important; padding: 10px 6px !important; }
  .example-word { font-size: 24px !important; }
  .bottom-spelling-circle { width: 60px !important; height: 60px !important; }
  .bottom-spelling-circles { gap: 10px !important; }

  .nav-row .arrow-btn { width: 50px !important; height: 50px !important; }
  .nav-row .menu-btn  { width: 100px !important; height: 50px !important; }
  .nav-row .arrow-img { width: 30px !important; height: 30px !important; }
  .nav-row .menu-img  { width: 34px !important; height: 34px !important; }
}

@media (max-width: 540px) {
  .page-wrap { width: calc(100% - 14px) !important; margin: 8px auto 16px !important; }

  .letter-main-title { font-size: 34px !important; }
  .image-stage { height: 280px !important; }
  .image-stage-with-signs { padding-bottom: 95px !important; }

  .main-sign-circle {
    width: 80px !important; height: 80px !important;
    top: 8px !important; right: 8px !important;
  }

  .example-side-container { left: 6px !important; bottom: 100px !important; }
  .example-video-wrap { width: 110px !important; height: 110px !important; }
  .example-side-word { font-size: 22px !important; padding: 7px 12px !important; }
  .video-play-btn { width: 60px !important; height: 60px !important; font-size: 28px !important; }
  .bottom-spelling-circle { width: 52px !important; height: 52px !important; }
  .bottom-spelling-circles { gap: 8px !important; bottom: 10px !important; }

  .arabic-learning-card { padding: 12px !important; border-radius: 22px !important; }

  .arabic-action-card {
    min-height: 86px !important;
    grid-template-columns: 60px 2px 1fr 30px !important;
    gap: 8px !important;
    padding: 8px 10px !important;
    border-radius: 20px !important;
  }

  .arabic-video-circle { width: 56px !important; height: 56px !important; }
  .arabic-action-text h3 { font-size: 16px !important; }
  .arabic-action-text p  { font-size: 12px !important; }
  .arabic-action-arrow { width: 28px !important; height: 28px !important; font-size: 26px !important; }

  .examples-panel-head h3 { font-size: 20px !important; }
  .close-examples-btn { width: 44px !important; height: 44px !important; font-size: 26px !important; }

  .click-card { font-size: 24px !important; padding: 8px 4px !important; border-radius: 10px !important; }
  .example-card { padding: 10px !important; min-height: 76px !important; border-radius: 14px !important; }
  .example-word { font-size: 20px !important; }

  .nav-row { gap: 10px !important; }
  .nav-row .arrow-btn { width: 44px !important; height: 44px !important; }
  .nav-row .menu-btn  { width: 86px !important; height: 44px !important; min-width: 86px !important; }
  .nav-row .arrow-img { width: 26px !important; height: 26px !important; }
  .nav-row .menu-img  { width: 28px !important; height: 28px !important; }

  .profile-hello { display: block !important; font-size: 12px !important; max-width: 65px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .dash-nav { gap: 20px !important; }
  .circle-icon { width: 46px !important; height: 46px !important; }
}

@media (max-width: 380px) {
  .image-stage { height: 240px !important; }
  .image-stage-with-signs { padding-bottom: 80px !important; }
  .main-sign-circle { width: 66px !important; height: 66px !important; }
  .letter-main-title { font-size: 28px !important; }

  .arabic-action-card {
    min-height: 76px !important;
    grid-template-columns: 50px 2px 1fr 26px !important;
  }

  .arabic-video-circle { width: 46px !important; height: 46px !important; }
  .arabic-action-text h3 { font-size: 14px !important; }
  .arabic-action-text p  { display: none !important; }
  .click-cards-row { gap: 8px !important; }
  .examples-grid { gap: 8px !important; }
  .bottom-spelling-circle { width: 44px !important; height: 44px !important; }
  .bottom-spelling-circles { gap: 6px !important; }
  .example-video-wrap { width: 90px !important; height: 90px !important; }
}
/* ===== FIX: إيقاف الـ scroll jump عند فتح/إغلاق الأمثلة ===== */
* {
  overflow-anchor: none;
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

<header class="children-dash kids-dashboard">
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
        <span class="dash-hover-label">صفحة الطفل</span>
      </a>

      <a href="../../subjects/arabic/arabic.php"
         class="circle-icon home-icon"
         tabindex="0"
         aria-label="قسم اللغة العربية">
        <img src="../../assets/icons/ar-letter.png" alt="اللغة العربية">
        <span class="dash-hover-label">اللغة العربية</span>
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
    <h2 class="letter-main-title"><?php echo htmlspecialchars($item['letter']); ?></h2>
  </section>

  <section class="content-layout">

    <div class="left-panel">

      <div class="speech-card letter-learning-card arabic-learning-card">

        <div class="arabic-action-row" id="arabicActionRow">

          <button type="button" class="arabic-action-card examples-new" id="examplesToggleBtn" onclick="toggleExamplesPanel()">
            <div class="arabic-video-circle">
              <video autoplay muted loop playsinline preload="auto">
                <source src="../../assets/icons/examples.mp4" type="video/mp4">
              </video>
            </div>

            <div class="arabic-action-divider"></div>

            <div class="arabic-action-text">
              <h3>أمثلة الحرف</h3>
              <p>شاهد كلمات</p>
              <p>تحتوي على الحرف</p>
            </div>

            <div class="arabic-action-arrow">›</div>
          </button>

          <!-- ✅ أمثلة الحرف هنا مباشرة تحت الزر -->
          <div class="examples-hidden-panel" id="examplesHiddenPanel">
            <div class="examples-panel-head">
              <h3>أمثلة حرف <?php echo htmlspecialchars($item['letter']); ?></h3>
              <button type="button" class="close-examples-btn" onclick="toggleExamplesPanel()" aria-label="إغلاق">×</button>
            </div>

            <div class="harakat-section visual-only-section">
              <div class="click-cards-row">
                <button type="button" class="click-card" onclick="showHaraka('fatha')" aria-label="فتحة">
                  <?php echo htmlspecialchars($item['letter']); ?>َ
                </button>

                <button type="button" class="click-card" onclick="showHaraka('damma')" aria-label="ضمة">
                  <?php echo htmlspecialchars($item['letter']); ?>ُ
                </button>

                <button type="button" class="click-card" onclick="showHaraka('kasra')" aria-label="كسرة">
                  <?php echo htmlspecialchars($item['letter']); ?>ِ
                </button>
              </div>
            </div>

            <div class="examples-section visual-only-section">
              <div class="examples-grid">
                <button type="button" class="example-card start-card" onclick="showExample('start')" aria-label="أول الكلمة">
                  <span class="example-dot"></span>
                  <span class="example-word"><?php echo colorSelectedLetter($buttonWords['start'], $item['letter']); ?></span>
                </button>

                <button type="button" class="example-card middle-card" onclick="showExample('middle')" aria-label="وسط الكلمة">
                  <span class="example-dot"></span>
                  <span class="example-word"><?php echo colorSelectedLetter($buttonWords['middle'], $item['letter']); ?></span>
                </button>

                <button type="button" class="example-card end-card" onclick="showExample('end')" aria-label="آخر الكلمة">
                  <span class="example-dot"></span>
                  <span class="example-word"><?php echo colorSelectedLetter($buttonWords['end'], $item['letter']); ?></span>
                </button>

                <button type="button" class="example-card alone-card" onclick="showExample('alone')" aria-label="منفصل">
                  <span class="example-dot"></span>
                  <span class="example-word"><?php echo colorSelectedLetter($buttonWords['alone'], $item['letter']); ?></span>
                </button>
              </div>
            </div>
          </div>

          <a href="../coloring.php?type=arabic&id=<?php echo $id; ?>" class="arabic-action-card color-new">
            <div class="arabic-video-circle">
              <video autoplay muted loop playsinline preload="auto">
                <source src="../../assets/icons/coloring.mp4" type="video/mp4">
              </video>
            </div>

            <div class="arabic-action-divider"></div>

            <div class="arabic-action-text">
              <h3>تلوين الحرف</h3>
              <p>لوّن الحرف</p>
              <p>بطريقتك المفضلة</p>
            </div>

            <div class="arabic-action-arrow">›</div>
          </a>

          <a href="arabic-quiz.php?id=<?php echo $id; ?>" class="arabic-action-card fish-new">
            <div class="arabic-video-circle">
              <video autoplay muted loop playsinline preload="auto">
                <source src="../../assets/icons/fish.mp4" type="video/mp4">
              </video>
            </div>

            <div class="arabic-action-divider"></div>

            <div class="arabic-action-text">
              <h3>لعبة السمكة</h3>
              <p>اختر الحرف الصحيح</p>
              <p>وساعد السمكة</p>
            </div>

            <div class="arabic-action-arrow">›</div>
          </a>

        </div>

      </div>

    <div class="nav-row">

        <a href="<?php echo $isFirst ? '#' : 'arabic-letter.php?id='.$prevId; ?>" class="arrow-btn <?php echo $isFirst ? 'disabled' : ''; ?>" data-title="الحرف السابق" aria-label="الحرف السابق">
          <img src="../../assets/icons/arrow.png" class="arrow-img prev" alt="الحرف السابق">
        </a>

        <a href="arabic.php" class="menu-btn" data-title="كل الحروف" aria-label="كل الحروف">
          <img src="../../assets/icons/menu.png" class="menu-img" alt="كل الحروف">
        </a>

        <a href="<?php echo $isLast ? '#' : 'arabic-letter.php?id='.$nextId; ?>" class="arrow-btn <?php echo $isLast ? 'disabled' : ''; ?>" data-title="الحرف التالي" aria-label="الحرف التالي">
          <img src="../../assets/icons/arrow.png" class="arrow-img next" alt="الحرف التالي">
        </a>

      </div>

    </div>

    <div class="right-panel">
      <div class="letter-card">
        <div class="image-stage image-stage-with-signs">

          <div class="main-sign-circle">
            <img
              src="<?php echo htmlspecialchars($signImagePath); ?>"
              alt="إشارة حرف <?php echo htmlspecialchars($item['letter']); ?>"
              class="main-sign-circle-image"
              onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';"
            />
          </div>

          <img
            src="<?php echo htmlspecialchars($mainCardImage); ?>"
            alt="حرف <?php echo htmlspecialchars($item['name']); ?> - <?php echo htmlspecialchars($item['word']); ?>"
            class="main-image"
            id="mainLetterImage"
          />

          <div class="example-side-container" id="exampleSideContainer">
            <div class="example-side-word" id="exampleSideWord"></div>

            <div class="example-video-wrap" id="exampleVideoWrap">
              <video
                id="exampleVideo"
                class="example-video"
                muted
                playsinline
                preload="metadata"
                oncontextmenu="return false;"
              ></video>

              <button
                type="button"
                class="video-play-btn"
                id="videoPlayBtn"
                onclick="toggleExampleVideo()"
                aria-label="تشغيل الفيديو"
                oncontextmenu="return false;"
              >
                ▶
              </button>
            </div>
          </div>

          <div class="bottom-spelling-circles" id="bottomSpellingCircles"></div>

        </div>
      </div>
    </div>

  </section>

  <div id="previewOverlay" class="preview-overlay">
    <div class="preview-modal">
      <button type="button" class="preview-close" onclick="closePreview()">✕</button>
      <img id="overlayPreviewImage" src="" alt="" class="preview-image">
      <div id="overlayPreviewWord" class="example-preview-word"></div>
      <div id="overlayPreviewSigns" class="example-word-signs"></div>
    </div>
  </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const profileBtn = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");
    const exampleVideo = document.getElementById("exampleVideo");
    const playBtn = document.getElementById("videoPlayBtn");

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

    if (exampleVideo && playBtn) {
        function playExampleVideo() {
            if (!exampleVideo.src) return;

            exampleVideo.play().then(() => {
                playBtn.style.display = "none";
            }).catch(() => {});
        }

        playBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            playExampleVideo();
        });

        exampleVideo.addEventListener("click", function () {
            playExampleVideo();
        });

        exampleVideo.addEventListener("play", function () {
            playBtn.style.display = "none";
        });

        exampleVideo.addEventListener("pause", function () {
            if (!exampleVideo.ended) {
                playBtn.style.display = "flex";
            }
        });

        exampleVideo.addEventListener("ended", function () {
            playBtn.style.display = "flex";
        });
    }

    renderBottomSpelling(currentWord);
    showExampleVideo(currentWord, exampleAssetsMap.start.video);
});

const currentLetter = <?php echo json_encode($item['letter'], JSON_UNESCAPED_UNICODE); ?>;
const currentWord = <?php echo json_encode($buttonWords['start'], JSON_UNESCAPED_UNICODE); ?>;
const exampleWordsMap = <?php echo json_encode($buttonWords, JSON_UNESCAPED_UNICODE); ?>;
const charToIdMap = <?php echo json_encode($charToId, JSON_UNESCAPED_UNICODE); ?>;
const customArabicSigns = <?php echo json_encode($customSignMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

function getArabicSignPath(ch) {
    const signId = charToIdMap[ch];

    if (!signId) {
        return "../../assets/icons/sign-icon.png";
    }

    if (customArabicSigns[signId]) {
        return customArabicSigns[signId];
    }

    if (signId <= 30) {
        return "../../images/signs/arabic/" + signId + ".png";
    }

    return "../../assets/icons/sign-icon.png";
}

const mainImagePath = <?php echo json_encode($mainCardImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const mainSignImagePath = <?php echo json_encode($signImagePath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

const exampleAssetsMap = {
    start: {
        image: <?php echo json_encode($startImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        video: <?php echo json_encode($startVideo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    },
    middle: {
        image: <?php echo json_encode($middleImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        video: <?php echo json_encode($middleVideo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    },
    end: {
        image: <?php echo json_encode($endImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        video: <?php echo json_encode($endVideo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    },
    alone: {
        image: <?php echo json_encode($aloneImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        video: ""
    }
};

function toggleExamplesPanel() {
    const panel = document.getElementById("examplesHiddenPanel");
    const btn = document.getElementById("examplesToggleBtn");

    if (!panel || !btn) return;

    const scrollY = window.scrollY;
    panel.classList.toggle("show");
    btn.classList.toggle("active");
    window.scrollTo(0, scrollY);
}

function renderBottomSpelling(word) {
    const container = document.getElementById("bottomSpellingCircles");
    if (!container) return;

    container.innerHTML = "";
    container.style.direction = "rtl";
    container.style.flexDirection = "row";

    const chars = Array.from(word).filter(ch => charToIdMap[ch]);

    chars.forEach(ch => {
        const signId = charToIdMap[ch];
        if (!signId) return;

        const item = document.createElement("div");
        item.className = "bottom-spelling-item";
        item.style.direction = "rtl";

        item.innerHTML = `
            <div class="bottom-spelling-circle">
                <img src="${getArabicSignPath(ch)}" alt="${ch}" class="bottom-spelling-image" onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';">
            </div>
        `;

        container.appendChild(item);
    });
}

function highlightWord(word, letter) {
    let used = false;

    return Array.from(word).map(ch => {
        const isAlif = letter === "أ" && (ch === "أ" || ch === "ا" || ch === "إ" || ch === "آ");
        const isHaa = letter === "هـ" && ch === "ه";

        if (!used && (ch === letter || isAlif || isHaa)) {
            used = true;
            return `<span class="selected-letter-red">${ch}</span>`;
        }

        return ch;
    }).join("");
}

function showExampleVideo(word, customVideoPath = "") {
    const container = document.getElementById("exampleSideContainer");
    const wordDiv = document.getElementById("exampleSideWord");
    const video = document.getElementById("exampleVideo");
    const playBtn = document.getElementById("videoPlayBtn");

    if (!container || !video || !wordDiv || !playBtn || !word) return;

    video.pause();
    video.currentTime = 0;
    video.removeAttribute("src");
    video.load();

    wordDiv.innerHTML = highlightWord(word, currentLetter);
    container.style.display = "flex";

    if (customVideoPath) {
        video.src = customVideoPath;
    } else {
        const cleanWord = word.replace(/\s+/g, "");
        video.src = "../../images/videos/" + cleanWord + ".mp4";
    }

    video.load();
    playBtn.style.display = "flex";
}

function stopExampleVideo() {
    const container = document.getElementById("exampleSideContainer");
    const wordDiv = document.getElementById("exampleSideWord");
    const video = document.getElementById("exampleVideo");
    const playBtn = document.getElementById("videoPlayBtn");

    if (!container || !video || !wordDiv || !playBtn) return;

    video.pause();
    video.currentTime = 0;
    video.removeAttribute("src");
    video.load();

    wordDiv.innerHTML = "";
    playBtn.style.display = "flex";
    container.style.display = "none";
}

function toggleExampleVideo() {
    const video = document.getElementById("exampleVideo");
    const playBtn = document.getElementById("videoPlayBtn");

    if (!video || !playBtn || !video.src) return;

    video.play().then(() => {
        playBtn.style.display = "none";
    }).catch(() => {});
}

function showExample(type) {

    const mainLetterImage = document.getElementById("mainLetterImage");
    const stage = document.querySelector(".image-stage");

    let bigLetter = document.getElementById("bigCenterLetter");

    if (!bigLetter && stage) {
        bigLetter = document.createElement("div");
        bigLetter.id = "bigCenterLetter";
        stage.appendChild(bigLetter);
    }

    let selectedWord = "";
    let selectedImage = "";
    let selectedVideo = "";

    if (bigLetter) {
        bigLetter.style.display = "none";
        bigLetter.textContent = "";
    }

    if (mainLetterImage) {
        mainLetterImage.style.display = "block";
    }

    if (type === "start") {

        selectedWord = exampleWordsMap.start || "";
        selectedImage = exampleAssetsMap.start.image || mainImagePath;
        selectedVideo = exampleAssetsMap.start.video || "";

    } else if (type === "middle") {

        selectedWord = exampleWordsMap.middle || "";
        selectedImage = exampleAssetsMap.middle.image || "";
        selectedVideo = exampleAssetsMap.middle.video || "";

    } else if (type === "end") {

        selectedWord = exampleWordsMap.end || "";
        selectedImage = exampleAssetsMap.end.image || "";
        selectedVideo = exampleAssetsMap.end.video || "";

   } else if (type === "alone") {

    selectedWord = exampleWordsMap.alone || currentLetter;
    selectedImage = "";
    selectedVideo = "";

    if (mainLetterImage) {
        mainLetterImage.style.display = "none";
    }

    if (bigLetter) {
        bigLetter.textContent = currentLetter;
        bigLetter.style.display = "flex";
    }

    const signImage = document.querySelector(".main-sign-circle-image");

    if (signImage) {

        signImage.src = mainSignImagePath;

        signImage.onerror = function () {
            this.src = "../../assets/icons/sign-icon.png";
        };
    }

    const signCircle = document.querySelector(".main-sign-circle");

    if (signCircle) {

        signCircle.style.display = "flex";

        signCircle.style.position = "absolute";
        signCircle.style.top = "22px";
        signCircle.style.right = "22px";
        signCircle.style.zIndex = "30";
    }
}
    if (!selectedWord) return;

    if (type !== "alone" && mainLetterImage && selectedImage) {
        mainLetterImage.src = selectedImage;
        mainLetterImage.alt = selectedWord;
    }

    renderBottomSpelling(selectedWord);

    if (type === "start" || type === "middle" || type === "end") {
        showExampleVideo(selectedWord, selectedVideo);
    } else {
        stopExampleVideo();
    }
}

function showHaraka(type) {
    const img = document.getElementById("overlayPreviewImage");
    const word = document.getElementById("overlayPreviewWord");
    const signs = document.getElementById("overlayPreviewSigns");
    const overlay = document.getElementById("previewOverlay");
    const folder = "../../images/harakat/";

    if (!img || !word || !signs || !overlay) return;

    word.textContent = "";
    signs.innerHTML = "";

    if (type === "fatha") {
        img.src = folder + "fatha.png";
        img.alt = "فتحة";
    } else if (type === "damma") {
        img.src = folder + "damma.png";
        img.alt = "ضمة";
    } else if (type === "kasra") {
        img.src = folder + "kasra.png";
        img.alt = "كسرة";
    }

    overlay.classList.add("show");
}

function closePreview() {
    const overlay = document.getElementById("previewOverlay");
    if (overlay) {
        overlay.classList.remove("show");
    }
}
</script>

<script>
document.addEventListener("keydown", function(e) {
    const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"];

    if (keys.includes(e.key)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>