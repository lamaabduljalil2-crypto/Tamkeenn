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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
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
track_progress($conn, 'lesson', 'english-letter-' . $id, chr(64 + max(1, min(26, $id))));
$lettersData = [
    1  => ["letter" => "A", "name" => "A", "word" => "Apple"],
    2  => ["letter" => "B", "name" => "B", "word" => "Bear"],
    3  => ["letter" => "C", "name" => "C", "word" => "Cat"],
    4  => ["letter" => "D", "name" => "D", "word" => "Dog"],
    5  => ["letter" => "E", "name" => "E", "word" => "Egg"],
    6  => ["letter" => "F", "name" => "F", "word" => "Fish"],
    7  => ["letter" => "G", "name" => "G", "word" => "Girl"],
    8  => ["letter" => "H", "name" => "H", "word" => "Hat"],
    9  => ["letter" => "I", "name" => "I", "word" => "Ice"],
    10 => ["letter" => "J", "name" => "J", "word" => "Jug"],
    11 => ["letter" => "K", "name" => "K", "word" => "Kite"],
    12 => ["letter" => "L", "name" => "L", "word" => "Lion"],
    13 => ["letter" => "M", "name" => "M", "word" => "Monkey"],
    14 => ["letter" => "N", "name" => "N", "word" => "Nest"],
    15 => ["letter" => "O", "name" => "O", "word" => "Orange"],
    16 => ["letter" => "P", "name" => "P", "word" => "Pen"],
    17 => ["letter" => "Q", "name" => "Q", "word" => "Queen"],
    18 => ["letter" => "R", "name" => "R", "word" => "Rain"],
    19 => ["letter" => "S", "name" => "S", "word" => "Sun"],
    20 => ["letter" => "T", "name" => "T", "word" => "Train"],
    21 => ["letter" => "U", "name" => "U", "word" => "Uniform"],
    22 => ["letter" => "V", "name" => "V", "word" => "Van"],
    23 => ["letter" => "W", "name" => "W", "word" => "Water"],
    24 => ["letter" => "X", "name" => "X", "word" => "X-Ray"],
    25 => ["letter" => "Y", "name" => "Y", "word" => "Yellow"],
    26 => ["letter" => "Z", "name" => "Z", "word" => "Zipper"]
];

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

$sql = "
SELECT *
FROM lessons
WHERE subject_name = 'اللغة الإنجليزية'
AND lesson_type = 'الحروف الإنجليزية'
AND letter_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dbItem = mysqli_fetch_assoc($result);

/* ── lesson_progress tracking ── */
if (isset($_SESSION['user_id']) && $dbItem) {
    $_lp_child = intval($_SESSION['user_id']);
    $_lp_lid   = intval($dbItem['id']);
    $s2 = mysqli_prepare($conn,
        "INSERT INTO lesson_progress (child_id, lesson_id, completed_at) VALUES (?,?,NOW())"
    );
    mysqli_stmt_bind_param($s2, 'ii', $_lp_child, $_lp_lid);
    mysqli_stmt_execute($s2);
}
/* ── نهاية lesson_progress ── */

if ($id >= 1 && $id <= 26 && isset($lettersData[$id])) {

    $item = $lettersData[$id];

    if ($dbItem && trim($dbItem['example_word'] ?? '') !== '') {
        $item['word'] = trim($dbItem['example_word']);
    }

    $cardImage = $dbItem ? trim($dbItem['lesson_image'] ?? '') : '';

    $mainImagePath = $cardImage !== ''
        ? filePathFromDb($cardImage)
        : "../../images/letters/english/" . $id . ".png";

    $signImagePath = "../../images/signs/english/" . $id . ".png";

    $cardVideo = $dbItem ? trim($dbItem['card_video'] ?? '') : '';

    if ($cardVideo !== '') {
        $exampleVideoPath = filePathFromDb($cardVideo);
    } else {
        $exampleVideoPath = "../../images/videos/english/" . $item['word'] . ".mp4";
    }

} elseif ($dbItem) {

    $letterText = trim($dbItem['lesson_title'] ?? '');
    $exampleWord = trim($dbItem['example_word'] ?? '');
    $cardImage = trim($dbItem['lesson_image'] ?? '');
    $cardVideo = trim($dbItem['card_video'] ?? '');
    $customSign = trim($dbItem['custom_sign'] ?? '');

    if ($letterText === '') {
        $letterText = "Letter " . $id;
    }

    if ($exampleWord === '') {
        $exampleWord = $letterText;
    }

    $item = [
        "letter" => $letterText,
        "name" => $letterText,
        "word" => $exampleWord
    ];

    $mainImagePath = $cardImage !== ''
        ? filePathFromDb($cardImage)
        : "../../assets/images/no-image.png";

    $signImagePath = $customSign !== ''
        ? filePathFromDb($customSign)
        : "../../assets/icons/sign-icon.png";

    $exampleVideoPath = $cardVideo !== ''
        ? filePathFromDb($cardVideo)
        : "";

} else {
    die("هذا الحرف غير موجود");
}

$letterOrder = [];

$orderSql = "
SELECT letter_id
FROM lessons
WHERE subject_name = 'اللغة الإنجليزية'
AND lesson_type = 'الحروف الإنجليزية'
AND letter_id IS NOT NULL
AND letter_id > 0
ORDER BY letter_id ASC, id ASC
";

$orderResult = mysqli_query($conn, $orderSql);

if ($orderResult) {
    while ($row = mysqli_fetch_assoc($orderResult)) {
        $lid = (int)$row['letter_id'];

        if ($lid > 0 && !in_array($lid, $letterOrder, true)) {
            $letterOrder[] = $lid;
        }
    }
}

for ($i = 1; $i <= 26; $i++) {
    if (!in_array($i, $letterOrder, true)) {
        $letterOrder[] = $i;
    }
}

sort($letterOrder, SORT_NUMERIC);

$currentIndex = array_search($id, $letterOrder, true);

if ($currentIndex === false) {
    $letterOrder[] = $id;
    sort($letterOrder, SORT_NUMERIC);
    $currentIndex = array_search($id, $letterOrder, true);
}

$isFirst = $currentIndex === 0;
$isLast = $currentIndex === count($letterOrder) - 1;

$prevId = $isFirst ? $id : $letterOrder[$currentIndex - 1];
$nextId = $isLast ? $id : $letterOrder[$currentIndex + 1];

$charToId = [
    "A" => 1, "B" => 2, "C" => 3, "D" => 4, "E" => 5, "F" => 6,
    "G" => 7, "H" => 8, "I" => 9, "J" => 10, "K" => 11, "L" => 12,
    "M" => 13, "N" => 14, "O" => 15, "P" => 16, "Q" => 17, "R" => 18,
    "S" => 19, "T" => 20, "U" => 21, "V" => 22, "W" => 23, "X" => 24,
    "Y" => 25, "Z" => 26,
    "a" => 1, "b" => 2, "c" => 3, "d" => 4, "e" => 5, "f" => 6,
    "g" => 7, "h" => 8, "i" => 9, "j" => 10, "k" => 11, "l" => 12,
    "m" => 13, "n" => 14, "o" => 15, "p" => 16, "q" => 17, "r" => 18,
    "s" => 19, "t" => 20, "u" => 21, "v" => 22, "w" => 23, "x" => 24,
    "y" => 25, "z" => 26
];

if (!isset($charToId[$item['letter']])) {
    $charToId[$item['letter']] = $id;
}

$customEnglishSignMap = [];

$customEnglishSql = "
SELECT letter_id, custom_sign
FROM lessons
WHERE subject_name = 'اللغة الإنجليزية'
AND lesson_type = 'الحروف الإنجليزية'
AND custom_sign IS NOT NULL
AND custom_sign != ''
";

$customEnglishResult = mysqli_query($conn, $customEnglishSql);

if ($customEnglishResult) {
    while ($customRow = mysqli_fetch_assoc($customEnglishResult)) {
        $customLetterId = (int)$customRow['letter_id'];
        $customPath = trim($customRow['custom_sign']);

        if ($customLetterId > 0 && $customPath !== '') {
            $customEnglishSignMap[$customLetterId] = filePathFromDb($customPath);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

<title>حرف <?php echo htmlspecialchars($item['name']); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/arabic-grid.css?v=4">
<link rel="stylesheet" href="../../assets/css/letter-style.css?v=2">

<style>
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

.content-layout{
    direction:ltr;
}

.content-layout > *{
    direction:rtl;
}

.english-learning-card{
    padding:18px !important;
    border-radius:30px !important;
    transform:none !important;
}

.english-action-row{
    display:flex;
    flex-direction:column;
    gap:16px !important;
}

.english-action-card{
    min-height:132px !important;
    border-radius:32px !important;
    padding:12px 20px !important;
    display:grid;
    grid-template-columns:115px 3px 1fr 42px !important;
    align-items:center;
    gap:18px !important;
    text-decoration:none;
    box-shadow:0 12px 24px rgba(37,80,130,.12) !important;
    transition:.25s ease;
    transform:none !important;
}

.english-action-card:hover{
    transform:translateY(-3px) !important;
}

.puzzle-new{
    background:linear-gradient(135deg,#fff8ff,#f6edff);
    border:2px dashed #c28af2;
}

.color-new{
    background:linear-gradient(135deg,#f6fff8,#edfff4);
    border:2px dashed #82e7a5;
}

.english-video-circle{
    width:112px !important;
    height:112px !important;
    border-radius:50%;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    box-shadow:0 8px 18px rgba(0,0,0,.11) !important;
}

.english-video-circle video{
    width:88%;
    height:88%;
    object-fit:cover;
    border-radius:50%;
}

.english-action-divider{
    height:78px !important;
    width:3px;
    border-radius:20px;
}

.puzzle-new .english-action-divider{
    background:#9b59d0;
}

.color-new .english-action-divider{
    background:#36bf7a;
}

.english-action-text h3{
    margin:0 0 6px !important;
    font-size:24px !important;
    font-weight:900;
}

.puzzle-new h3{
    color:#7a37af;
}

.color-new h3{
    color:#168653;
}

.english-action-text p{
    margin:0;
    font-size:15px !important;
    font-weight:800;
    line-height:1.65 !important;
    color:#17486c;
}

.english-action-arrow{
    width:40px !important;
    height:40px !important;
    border-radius:50%;
    color:#fff;
    font-size:40px !important;
    font-weight:900;
    display:flex;
    align-items:center;
    justify-content:center;
    padding-bottom:5px;
}

.puzzle-new .english-action-arrow{
    background:#8d3cc1;
}

.color-new .english-action-arrow{
    background:#35bd7b;
}

#bottomSpellingCircles{
    direction:ltr !important;
    flex-direction:row !important;
    justify-content:center;
}

.bottom-spelling-item{
    direction:ltr !important;
}

.example-side-word{
    direction:ltr;
    unicode-bidi:isolate;
}

.nav-row{
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:14px !important;
    margin-top:18px !important;
}

.arrow-btn,
.menu-btn{
    width:210px !important;
    height:52px !important;
    border-radius:18px !important;
    background:#fff !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    text-decoration:none !important;
    box-shadow:0 9px 20px rgba(37,80,130,.13) !important;
    transition:.25s ease !important;
    position:relative !important;
    border:none !important;
}

.arrow-btn:hover,
.menu-btn:hover{
    transform:translateY(-3px) scale(1.03) !important;
}

.arrow-btn.disabled{
    opacity:.45 !important;
    pointer-events:none !important;
}

.arrow-img{
    width:38px !important;
    height:38px !important;
    object-fit:contain !important;
    display:block !important;
}

.arrow-img.prev{
    transform:rotate(180deg) !important;
}

.arrow-img.next{
    transform:rotate(0deg) !important;
}

.menu-img{
    width:38px !important;
    height:38px !important;
    object-fit:contain !important;
    display:block !important;
}

.arrow-btn::after,
.menu-btn::after{
    content:attr(data-title) !important;
    position:absolute !important;
    bottom:-31px !important;
    left:50% !important;
    transform:translateX(-50%) !important;
    background:linear-gradient(135deg,#ff7bb2,#ff4f8f) !important;
    color:white !important;
    font-size:13px !important;
    font-weight:900 !important;
    padding:6px 12px !important;
    border-radius:999px !important;
    white-space:nowrap !important;
    opacity:0 !important;
    visibility:hidden !important;
    transition:.2s ease !important;
    box-shadow:0 7px 15px rgba(255,79,143,.32) !important;
    z-index:9999 !important;
    font-family:"Cairo",Arial,sans-serif !important;
}

.arrow-btn:hover::after,
.menu-btn:hover::after{
    opacity:1 !important;
    visibility:visible !important;
}

.letter-card{
    transform:scale(.9) translateY(1px) !important;
    transform-origin:top center;
}

/* force sizes - debug */
.image-stage { height: 580px !important; position: relative !important; }
.main-sign-circle { width: 165px !important; height: 165px !important; }
.example-video-wrap { width: 220px !important; height: 220px !important; }
.video-play-btn { width: 70px !important; height: 70px !important; }

/* ===== MOBILE ===== */
@media (max-width: 900px) {
  .content-layout { grid-template-columns: 1fr !important; margin-top: 0 !important; gap: 14px; }
  .right-panel { order: -1; }
  .left-panel  { order: 1; }
  .image-stage { height: 380px !important; }
  .letter-card { transform: none !important; }
  .english-learning-card { transform: none !important; min-height: auto !important; }
  .page-wrap { margin-top: 12px !important; }
  .letter-main-title { font-size: 42px !important; }
  .top-title { text-align: center; }
}

@media (max-width: 768px) {
  .kids-dashboard .children-dash-inner {
    display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important;
    align-items: center !important; width: 100% !important; box-sizing: border-box !important;
    padding: 8px 10px !important; height: 64px !important; min-height: 64px !important;
    max-height: 64px !important; gap: 6px !important;
  }
  .kids-dashboard .dash-start { flex: 0 0 40px !important; min-width: 0 !important; height: auto !important; }
  .kids-dashboard .logo-box img { width: 40px !important; height: 40px !important; }
  .kids-dashboard .logo-text { display: none !important; }
  .kids-dashboard .dash-nav {
    flex: 1 1 0 !important; min-width: 0 !important; display: flex !important;
    flex-direction: row !important; align-items: center !important; justify-content: center !important;
    gap: 12px !important; height: 48px !important; margin: 0 !important; padding: 0 !important;
    transform: none !important;
  }
  .kids-dashboard .profile-wrap { flex: 0 0 auto !important; min-width: 0 !important; }
  .kids-dashboard .profile-btn { padding: 5px 8px !important; gap: 5px !important; }
  .kids-dashboard .profile-video-box { width: 36px !important; height: 36px !important; }
  .kids-dashboard .profile-hello { font-size: 11px !important; display: block !important; max-width: 70px !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
  .kids-dashboard .profile-menu { left: 0 !important; right: auto !important; }
  .kids-dashboard .circle-icon { top: 0 !important; width: 44px !important; height: 44px !important; overflow: hidden !important; position: relative !important; }
  .kids-dashboard .circle-icon video, .kids-dashboard .circle-icon img { width: 38px !important; height: 38px !important; }
  .kids-dashboard .dash-hover-label { opacity: 0 !important; pointer-events: none !important; }

  .image-stage { height: 580px !important; position: relative !important; overflow: hidden !important; }
  .main-sign-circle { width: 165px !important; height: 165px !important; top: 10px !important; right: 10px !important; }
  .example-side-container { left: 8px !important; top: auto !important; bottom: 120px !important; transform: none !important; position: absolute !important; }
  .example-video-wrap { width: 220px !important; height: 220px !important; }
  .example-side-word { font-size: 24px !important; min-width: 100px !important; padding: 6px 12px !important; }
  .video-play-btn { width: 70px !important; height: 70px !important; font-size: 30px !important; }
  .bottom-spelling-circles { gap: 10px !important; }
  .bottom-spelling-circle { width: 60px !important; height: 60px !important; }
  .english-action-card {
    min-height: 100px !important;
    grid-template-columns: 72px 3px 1fr 36px !important;
    gap: 10px !important; padding: 10px 14px !important;
  }
  .english-video-circle { width: 68px !important; height: 68px !important; }
  .english-action-text h3 { font-size: 18px !important; }
  .english-action-text p  { font-size: 13px !important; }
  .arrow-btn { width: 52px !important; height: 52px !important; border-radius: 50% !important; }
  .menu-btn  { width: 110px !important; height: 52px !important; }
  .bottom-spelling-circle { width: 60px !important; height: 60px !important; }
}

@media (max-width: 540px) {
  .page-wrap { width: calc(100% - 14px) !important; margin: 8px auto 16px !important; }
  .letter-main-title { font-size: 34px !important; }
  .image-stage { height: 280px !important; }
  .main-sign-circle { width: 72px !important; height: 72px !important; top: 8px !important; right: 8px !important; }
  .example-video-wrap { width: 100px !important; height: 100px !important; }
  .example-side-word { font-size: 18px !important; padding: 5px 10px !important; }
  .bottom-spelling-circle { width: 48px !important; height: 48px !important; }
  .bottom-spelling-circles { gap: 8px !important; bottom: 8px !important; }
  .english-action-card {
    min-height: 86px !important;
    grid-template-columns: 60px 2px 1fr 30px !important;
    gap: 8px !important; padding: 8px 10px !important;
  }
  .english-video-circle { width: 56px !important; height: 56px !important; }
  .english-action-text h3 { font-size: 16px !important; }
  .english-action-text p  { font-size: 12px !important; }
  .arrow-btn { width: 44px !important; height: 44px !important; }
  .menu-btn  { width: 90px !important; height: 44px !important; }
  .bottom-spelling-circle { width: 52px !important; height: 52px !important; }
}

@media (max-width: 380px) {
  .image-stage { height: 240px !important; }
  .letter-main-title { font-size: 28px !important; }
  .english-action-card { grid-template-columns: 50px 2px 1fr 26px !important; }
  .english-video-circle { width: 46px !important; height: 46px !important; }
  .english-action-text h3 { font-size: 14px !important; }
  .english-action-text p  { display: none !important; }
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

      <a href="../../subjects/english/english-letters.php"
         class="circle-icon home-icon"
         tabindex="0"
         aria-label="قسم الحروف الإنجليزية">
        <img src="../../assets/icons/en-letter.png" alt="English Letters">
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
    <h2 class="letter-main-title">
      <?php echo htmlspecialchars($item['letter']); ?>
    </h2>
  </section>

  <section class="content-layout">

    <div class="left-panel">

      <div class="speech-card letter-learning-card english-learning-card">

        <div class="english-action-row">

          <a href="english-quiz.php?id=<?php echo $id; ?>" class="english-action-card puzzle-new">
            <div class="english-video-circle">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/puzzle.mp4" type="video/mp4">
              </video>
            </div>

            <div class="english-action-divider"></div>

            <div class="english-action-text">
              <h3>لعبة البازل</h3>
              <p>رتّب قطع الصورة</p>
              <p>لتكوّن الحرف</p>
            </div>

            <div class="english-action-arrow">›</div>
          </a>

          <a href="../coloring.php?type=english&id=<?php echo $id; ?>" class="english-action-card color-new">
            <div class="english-video-circle">
              <video autoplay muted loop playsinline>
                <source src="../../assets/icons/coloring.mp4" type="video/mp4">
              </video>
            </div>

            <div class="english-action-divider"></div>

            <div class="english-action-text">
              <h3>تلوين الحرف</h3>
              <p>لوّن الحرف</p>
              <p>بطريقتك المفضلة</p>
            </div>

            <div class="english-action-arrow">›</div>
          </a>

        </div>

      </div>

      <section class="nav-row">

        <a
          href="english-letter.php?id=<?php echo $prevId; ?>"
          class="arrow-btn <?php echo $isFirst ? 'disabled' : ''; ?>"
          data-title="الحرف السابق"
          aria-label="الحرف السابق"
          <?php echo $isFirst ? 'onclick="return false;"' : ''; ?>
        >
          <img src="../../assets/icons/arrow.png" class="arrow-img prev" alt="الحرف السابق">
        </a>

        <a href="english-letters.php" class="menu-btn" data-title="كل الحروف" aria-label="كل الحروف">
          <img src="../../assets/icons/menu.png" class="menu-img" alt="كل الحروف">
        </a>

        <a
          href="english-letter.php?id=<?php echo $nextId; ?>"
          class="arrow-btn <?php echo $isLast ? 'disabled' : ''; ?>"
          data-title="الحرف التالي"
          aria-label="الحرف التالي"
          <?php echo $isLast ? 'onclick="return false;"' : ''; ?>
        >
          <img src="../../assets/icons/arrow.png" class="arrow-img next" alt="الحرف التالي">
        </a>

      </section>

    </div>

    <div class="right-panel">

      <div class="letter-card">

        <div class="image-stage image-stage-with-signs" style="height:580px;position:relative;overflow:hidden;">

          <div class="main-sign-circle" style="width:165px;height:165px;">
            <img
              src="<?php echo htmlspecialchars($signImagePath); ?>"
              alt="إشارة حرف <?php echo htmlspecialchars($item['letter']); ?>"
              class="main-sign-circle-image"
              onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';">
          </div>

          <img
            src="<?php echo htmlspecialchars($mainImagePath); ?>"
            alt="حرف <?php echo htmlspecialchars($item['name']); ?> - <?php echo htmlspecialchars($item['word']); ?>"
            class="main-image"
            id="mainLetterImage"
            onerror="this.onerror=null;this.src='../../assets/images/no-image.png';">

          <div class="example-side-container" id="exampleSideContainer">

            <div class="example-side-word" id="exampleSideWord"></div>

            <div class="example-video-wrap" id="exampleVideoWrap" style="width:190px;height:190px;">

              <video
                id="exampleVideo"
                class="example-video"
                muted
                playsinline
                preload="metadata"
                oncontextmenu="return false;">
              </video>

              <button
                type="button"
                class="video-play-btn"
                id="videoPlayBtn"
                onclick="toggleExampleVideo()"
                aria-label="تشغيل الفيديو"
                oncontextmenu="return false;">
                ▶
              </button>

            </div>

          </div>

          <div class="bottom-spelling-circles" id="bottomSpellingCircles"></div>

        </div>

      </div>

    </div>

  </section>

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
    showExampleVideo(currentWord);
});

const currentLetter = <?php echo json_encode($item['letter'], JSON_UNESCAPED_UNICODE); ?>;
const currentWord = <?php echo json_encode($item['word'], JSON_UNESCAPED_UNICODE); ?>;
const currentVideoPath = <?php echo json_encode($exampleVideoPath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const charToIdMap = <?php echo json_encode($charToId, JSON_UNESCAPED_UNICODE); ?>;
const customEnglishSignMap = <?php echo json_encode($customEnglishSignMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function getEnglishSignPath(ch){

    const signId = charToIdMap[ch];

    if(!signId){
        return "../../assets/icons/sign-icon.png";
    }

    if(signId >= 1 && signId <= 26){
        return "../../images/signs/english/" + signId + ".png";
    }

    if(customEnglishSignMap[signId]){
        return customEnglishSignMap[signId];
    }

    return "../../assets/icons/sign-icon.png";
}

function renderBottomSpelling(word) {

    const container = document.getElementById("bottomSpellingCircles");

    if (!container) return;

    container.innerHTML = "";
    container.style.direction = "ltr";
    container.style.flexDirection = "row";

    const chars = Array.from(word).filter(ch => /[a-zA-Z]/.test(ch));

    chars.forEach(ch => {

        const signId = charToIdMap[ch];

        if (!signId) return;

        const item = document.createElement("div");
        item.className = "bottom-spelling-item";
        item.style.direction = "ltr";

        item.innerHTML = `
            <div class="bottom-spelling-circle">
                <img
                src="${getEnglishSignPath(ch)}"
                alt="${ch}"
                class="bottom-spelling-image"
                onerror="this.onerror=null;this.style.display='none';">
            </div>
        `;

        container.appendChild(item);
    });
}

function highlightWord(word, letter) {

    let used = false;

    return Array.from(word).map(ch => {

        if (!used && ch.toLowerCase() === letter.toLowerCase()) {
            used = true;
            return `<span class="selected-letter-red">${ch}</span>`;
        }

        return ch;

    }).join("");
}

function showExampleVideo(word) {

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

    if (currentVideoPath && currentVideoPath.trim() !== "") {
        video.src = currentVideoPath;
    }

    video.load();
    playBtn.style.display = "flex";
}

function toggleExampleVideo() {

    const video = document.getElementById("exampleVideo");
    const playBtn = document.getElementById("videoPlayBtn");

    if (!video || !playBtn || !video.src) return;

    video.play().then(() => {
        playBtn.style.display = "none";
    }).catch(() => {});
}

document.addEventListener("keydown", function(e) {

    const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"];

    if (keys.includes(e.key)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
