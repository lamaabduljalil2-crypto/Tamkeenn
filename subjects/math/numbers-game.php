<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../config/session_child.php';

require_once '../../config/db.php';

$lang = $_SESSION['lang'] ?? 'ar';

$total = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($total < 0) $total = 0;

$from = $_GET['from'] ?? 'arabic';
$useArabicNumbers = ($from !== 'english');
$numbersAreArabic = $useArabicNumbers;

if ($from === 'english') {
    $backNumberPage = "../english/english-number.php?id=" . $total;
} else {
    $backNumberPage = "arabic-number.php?id=" . $total;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

$arabicNumberWords = [
  0 => "صفر", 1 => "واحد", 2 => "اثنان", 3 => "ثلاثة", 4 => "أربعة", 5 => "خمسة",
  6 => "ستة", 7 => "سبعة", 8 => "ثمانية", 9 => "تسعة", 10 => "عشرة",
  20 => "عشرون", 30 => "ثلاثون", 40 => "أربعون", 50 => "خمسون", 60 => "ستون",
  70 => "سبعون", 80 => "ثمانون", 90 => "تسعون", 100 => "مئة"
];

$englishNumberWords = [
  0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five",
  6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten",
  20 => "Twenty", 30 => "Thirty", 40 => "Forty", 50 => "Fifty", 60 => "Sixty",
  70 => "Seventy", 80 => "Eighty", 90 => "Ninety", 100 => "One Hundred"
];

$numberWords = ($from === 'english') ? $englishNumberWords : $arabicNumberWords;
$numberWord = $numberWords[$total] ?? (string)$total;

function arNum($num) {
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    return str_replace($western, $arabic, strval($num));
}

$id = $total;

if ($from === 'english') {
  $backLink = "../../subjects/english/english-number.php?id=" . $id;
} else {
  $backLink = "../../subjects/math/arabic-number.php?id=" . $id;
}

function gameFilePathFromDb($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (strpos($path, '../') === 0 || strpos($path, '../../') === 0 || strpos($path, '/') === 0) return $path;
    return '../../' . ltrim($path, '/');
}

$numberSignImage = "../../images/signs/numbers/" . $total . ".png";

if ($from === 'english') {
    $subjectName = 'اللغة الإنجليزية';
    $lessonType = 'الأرقام الإنجليزية';
} else {
    $subjectName = 'الرياضيات';
    $lessonType = 'الأرقام العربية';
}

$numberStmt = mysqli_prepare(
    $conn,
    "
    SELECT custom_sign, example_word
    FROM lessons
    WHERE subject_name = ?
    AND lesson_type = ?
    AND letter_id = ?
    LIMIT 1
    "
);

if ($numberStmt) {
    mysqli_stmt_bind_param($numberStmt, "ssi", $subjectName, $lessonType, $total);
    mysqli_stmt_execute($numberStmt);
    $numberResult = mysqli_stmt_get_result($numberStmt);
    $numberRow = mysqli_fetch_assoc($numberResult);

    if ($numberRow) {
        if (trim($numberRow['custom_sign'] ?? '') !== '') {
            $numberSignImage = gameFilePathFromDb($numberRow['custom_sign']);
        }

        if (trim($numberRow['example_word'] ?? '') !== '') {
            $numberWord = trim($numberRow['example_word']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

<title>لعبة العدد <?php echo $useArabicNumbers ? arNum($total) : $total; ?></title>

<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: Arial, sans-serif;
  background: linear-gradient(135deg, #eaf7ff, #fff8df);
  min-height: 100vh;
  text-align: center;
  color: #183b5a;
}

/* ===== DASHBOARD ===== */
.children-dash {
  width: calc(100% - 30px);
  max-width: 1400px;
  margin: 16px auto 0;
  position: sticky;
  top: 10px;
  z-index: 1000;
}

.children-dash-inner {
  background: linear-gradient(135deg, #dff4ff, #cfeeff);
  border-radius: 30px;
  padding: 10px 22px;
  min-height: 110px;
  display: grid;
  grid-template-columns: 260px 1fr 320px;
  align-items: center;
  gap: 20px;
  box-shadow: 0 10px 24px rgba(44, 111, 170, 0.15);
}

.dash-start,
.dash-end {
  display: flex;
  align-items: center;
}

.dash-start {
  justify-content: flex-start;
}

.dash-end {
  justify-content: flex-end;
}

.logo-box img {
  width: 115px;
  height: auto;
  display: block;
}

.dash-nav {
  display: flex;
  justify-content: center;
  gap: 35px;
}

.circle-icon {
  width: 82px;
  height: 82px;
  border-radius: 50%;
  background: #fff;
  border: 4px solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  box-shadow: 0 10px 22px rgba(17, 75, 122, 0.12);
  transition: 0.25s;
  text-decoration: none;
}

.circle-icon:hover {
  transform: translateY(-5px) scale(1.05);
}

.circle-icon video {
  width: 70px;
  height: 70px;
  object-fit: contain;
  border-radius: 50%;
}

.circle-icon img {
  width: 70px;
  height: 70px;
  object-fit: contain;
}

.profile-wrap {
  display: flex;
  justify-content: flex-end;
  position: relative;
}

.profile-btn {
  border: none;
  background: #fff;
  border-radius: 999px;
  padding: 10px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(17, 75, 122, 0.12);
}

.profile-hello {
  font-size: 18px;
  font-weight: 900;
  color: #1f4d7a;
}

.profile-video-box {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
}

.profile-video-box video {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.profile-menu {
  position: absolute;
  width: 150px;
  height: 150px%;
  top: 70px;
  left: 0;
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 12px 25px rgba(0,0,0,.2);
  display: none;
  overflow: hidden;
  z-index: 2000;
}

.profile-menu.show {
  display: block;
}

.profile-menu a {
  display: block;
  padding: 12px 18px;
  text-decoration: none;
  color: #21425f;
  font-weight: bold;
  white-space: nowrap;
}

.profile-menu a:hover {
  background: #f0f8ff;
}

/* ===== GAME ===== */
.game-page {
  max-width: 1100px;
  margin: auto;
  padding: 24px 20px 40px;
}

.number-info {
  position: relative !important;
  right: -190px !important;
  top: 0 !important;
  margin: 15px 0 22px !important;
  width: fit-content !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 14px !important;
}

.number-word {
  font-size: 60px;
  font-weight: bold;
  color: #2a7dc2;
}

.number-sign {
  width: 120px;
  height: 120px;
  object-fit: contain;
  background: white;
  border-radius: 22px;
  padding: 8px;
  box-shadow: 0 8px 18px rgba(17, 75, 122, 0.12);
}

.drag-area {
  background: #fff;
  border-radius: 28px;
  padding: 22px;
  box-shadow: 0 12px 28px rgba(17, 75, 122, 0.12);
  border: 4px solid #ffe1a8;
  margin-bottom: 32px;
  position: relative;
  top: -150px;
}

.drag-title {
  font-size: 23px;
  font-weight: bold;
  color: #e58a00;
  margin-bottom: 15px;
}

.pieces {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 18px 26px;
  min-height: 90px;
  
  

}

.piece {
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  background: transparent;
  border: none;
  box-shadow: none;
}

.piece:active {
  cursor: grabbing;
  transform: scale(.95);
}

.piece.dragging {
  opacity: .2;
}

.piece img {
  width:90px;
  height: 90px;
  object-fit: contain;
  pointer-events: none;
}

.boxes {
  display: flex;
  justify-content: center;
  gap: 45px;
  flex-wrap: wrap;
  margin-top: -70px;
}

.drop-box {
  width: 230px;
  min-height: 210px;
  background: #f1fbff;
  border: 4px dashed #2a7dc2;
  border-radius: 28px;
  padding: 45px 12px 15px;
  position: relative;
  box-shadow: 0 10px 22px rgba(17, 75, 122, 0.10);
  transition: .2s;
}

.drop-box.hover {
  background: #e3f6ff;
  transform: translateY(-4px);
}

.box-number {
  position: absolute;
  top: -24px;
  right: 50%;
  transform: translateX(50%);
  width: 82px;
  height: 55px;
  border-radius: 20px;
  background: #2a7dc2;
  color: white;
  font-size: 30px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 16px rgba(42, 125, 194, .25);
}

.box-content {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
  min-height: 125px;
}

.box-content .piece {
  width: 52px;
  height: 52px;
  cursor: default;
}

.box-content .piece img {
  width: 50px;
  height: 50px;
}

.message {
  margin-top: 22px;
  min-height: 42px;
  font-size: 25px;
  font-weight: bold;
  color: #199455;
}

.wrong {
  animation: shake .3s ease;
  border-color: #ff5b5b !important;
}

@keyframes shake {
  0% { transform: translateX(0); }
  25% { transform: translateX(8px); }
  50% { transform: translateX(-8px); }
  75% { transform: translateX(8px); }
  100% { transform: translateX(0); }
}

@media (max-width: 800px) {
  .children-dash-inner {
    grid-template-columns: 1fr;
    gap: 18px;
    padding: 18px;
  }

  .dash-start,
  .dash-end,
  .profile-wrap {
    justify-content: center;
  }

  .number-info {
    right: 0 !important;
    margin-inline: auto !important;
  }

  .drag-area {
    top: -60px;
  }

  .boxes {
    margin-top: -30px;
  }
}
.actions {
  margin-top: -130px;
  display: flex;
  justify-content: flex-end;  /* 👈 يخليه يمين */
  padding-left:850px;        /* 👈 مسافة عن الحافة */

}

.replay-btn {
  width: 62px;
  height: 62px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #2ecc71, #16a85a);
  color: white;
  font-size: 38px;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(46, 204, 113, .28);
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transition: .2s ease;
}

.replay-btn:hover {
  transform: translateY(-4px) rotate(-20deg);
}
.dash-hover-label{
  position:absolute;
  top:92px;
  left:50%;
  transform:translateX(-50%);
  background:#fff;
  color:#21425f;
  font-size:15px;
  font-weight:900;
  padding:6px 14px;
  border-radius:999px;
  white-space:nowrap;
  opacity:0;
  visibility:hidden;
  transition:.22s ease;
  box-shadow:0 8px 18px rgba(0,0,0,.12);
  z-index:100;
}

.circle-icon:hover .dash-hover-label{
  opacity:1;
  visibility:visible;
}


/* ===== CONFETTI عند الفوز ===== */
.confetti-layer {
  position: fixed;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 999999;
}

.confetti-piece {
  position: absolute;
  top: -24px;
  width: 12px;
  height: 18px;
  border-radius: 4px;
  opacity: 0.95;
  animation-name: confettiFall;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes confettiFall {
  0% {
    transform: translateY(-30px) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(110vh) rotate(720deg);
    opacity: 0;
  }
}
.win-card{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.45);
  display:none;
  align-items:center;
  justify-content:center;
  z-index:9999999;
}

.win-card.show{
  display:flex;
}

.win-box{
  background:#fff;
  width:525px;
  max-width:90%;
  border-radius:38px;
  padding:34px 26px 36px;
  text-align:center;
  box-shadow:0 20px 45px rgba(0,0,0,.22);
  animation:winPop .35s ease;
}

@keyframes winPop{
  from{
    transform:scale(.7);
    opacity:0;
  }
  to{
    transform:scale(1);
    opacity:1;
  }
}

.win-celebrate{
  font-size:64px;
  line-height:1;
  margin-bottom:8px;
}

.win-title{
  font-size:42px;
  font-weight:900;
  color:#183b5a;
  margin:18px 0 32px;
  line-height:1;
}

.win-text{
  display:none;
}

.win-buttons{
  display:flex;
  justify-content:center;
  align-items:flex-start;
  gap:36px;
  flex-wrap:wrap;
  direction:ltr;
}

.win-action{
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
}

.win-btn{
  width:66px;
  height:66px;
  min-width:66px;
  padding:0;
  border:none;
  border-radius:18px;
  text-decoration:none;
  font-size:34px;
  font-weight:900;
  cursor:pointer;
  transition:.2s;
  display:flex;
  align-items:center;
  justify-content:center;
  box-shadow:0 12px 24px rgba(0,0,0,.12);
  line-height:1;
}

.win-btn:hover{
  transform:translateY(-3px) scale(1.03);
}

.win-replay{
  background:#ffd400;
  color:#ffffff;
}

.win-back{
  background:#ef535a;
  color:#ffffff;
}

.win-label{
  font-size:20px;
  font-weight:900;
  color:#7a5a43;
  line-height:1.2;
  white-space:nowrap;
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
  .children-dash { width: calc(100% - 16px) !important; margin: 8px auto 0 !important; }
  .children-dash-inner {
    display: flex !important; flex-direction: row !important;
    align-items: center !important; justify-content: center !important;
    position: relative !important; padding: 8px 10px !important;
    min-height: auto !important; height: 60px !important;
    grid-template-columns: unset !important; gap: 6px !important;
  }
  .dash-start { position: absolute !important; right: 10px !important; }
  .logo-box img { width: 40px !important; height: 40px !important; }
  .dash-nav { display: flex !important; flex-direction: row !important; align-items: center !important; gap: 14px !important; }
  .circle-icon { width: 44px !important; height: 44px !important; }
  .circle-icon video, .circle-icon img { width: 36px !important; height: 36px !important; }
  .profile-wrap { position: absolute !important; left: 10px !important; }
  .profile-btn { padding: 5px 8px !important; gap: 5px !important; }
  .profile-video-box { width: 34px !important; height: 34px !important; }
  .profile-hello { font-size: 11px !important; max-width: 60px !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
  .profile-menu { left: 0 !important; top: 48px !important; }

  .game-page { padding: 74px 12px 20px !important; }
  .number-info { position: static !important; right: auto !important; margin: 0 auto 14px !important; justify-content: center !important; }
  .number-word { font-size: 42px !important; }
  .number-sign { width: 80px !important; height: 80px !important; }
  .drag-area { top: 0 !important; padding: 14px !important; margin-bottom: 16px !important; }
  .drag-title { font-size: 16px !important; margin-bottom: 10px !important; }
  .piece { width: 70px !important; height: 70px !important; }
  .piece img { width: 60px !important; height: 60px !important; }
  .boxes { margin-top: 0 !important; gap: 16px !important; justify-content: center !important; }
  .drop-box { width: 155px !important; min-height: 150px !important; padding: 35px 8px 10px !important; border-radius: 20px !important; }
  .box-number { width: 60px !important; height: 42px !important; font-size: 22px !important; }
  .box-content .piece { width: 40px !important; height: 40px !important; }
  .box-content .piece img { width: 36px !important; height: 36px !important; }
  .actions { margin-top: 14px !important; padding-left: 0 !important; justify-content: center !important; }
  .win-box { width: 88vw !important; padding: 22px 16px !important; border-radius: 24px !important; }
  .win-title { font-size: 28px !important; }

  /* تمييز القطعة المختارة */
  .piece.selected { outline: 3px solid #2a7dc2 !important; border-radius: 8px !important; background: #e8f4ff !important; }
  .drop-box.tap-hover { background: #e3f6ff !important; transform: translateY(-3px) !important; }
}
</style>
</head>

<body>

<header class="children-dash">
  <div class="children-dash-inner">

    <div class="dash-start">
      <div class="dash-end">
        <a href="../../auth/children.php" class="logo-box" tabindex="0">
          <img src="../../logo.png" alt="logo">
        </a>
      </div>
    </div>

    <nav class="dash-nav">
    <a href="../../auth/children.php"
   class="circle-icon home-icon"
   tabindex="0">

  <video class="nav-icon-video" autoplay muted loop playsinline>
    <source src="../../assets/icons/children.mp4" type="video/mp4">
  </video>

  <span class="dash-hover-label">صفحة الطفل</span>

</a>

<a href="<?php echo $backLink; ?>"
   class="circle-icon home-icon"
   tabindex="0">

  <img src="../../assets/icons/math-ar.png" alt="Numbers">

  <span class="dash-hover-label">العودة الى صفحة الرقم</span>

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

<div class="game-page">

  <div class="number-info">
    <div class="number-word"><?php echo $useArabicNumbers ? arNum($total) : $total; ?></div>
    <img class="number-sign" src="<?php echo htmlspecialchars($numberSignImage); ?>" onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';" alt="إشارة العدد">
  </div>

  <div class="drag-area">
    <div class="drag-title">
      اسحب العناصر إلى الصناديق
    </div>
    <div class="pieces" id="pieces"></div>
  </div>

  <div class="boxes">
    <div class="drop-box" id="box1">
      <div class="box-number" id="num1"></div>
      <div class="box-content"></div>
    </div>

    <div class="drop-box" id="box2">
      <div class="box-number" id="num2"></div>
      <div class="box-content"></div>
    </div>
  </div>

  <div class="message" id="message"></div>
<div class="actions">
  <button class="replay-btn" type="button" onclick="startGame()" aria-label="إعادة اللعبة">
    ↻
  </button>
</div>
</div>

<?php
$sign_base = "../../";
$sign_video = "../../assets/videos/math-game-intro.mp4";
include __DIR__ . "/../../components/sign-language.php";
?>

<script>
const introVideoPath = "../../assets/videos/math-game-intro.mp4";
const introText = "هيا نضع هذه الصور في الصناديق حسب الرقم الموجود عليها";

function playGameHelp(videoPath, text) {
  const box = document.getElementById("signVideoBox");
  const toggleBtn = document.getElementById("signToggleBtn");
  const video = document.getElementById("signVideo");

  if (!box || !video) return;

  box.classList.add("active");
  if (toggleBtn) toggleBtn.classList.add("active");
  document.body.classList.add("sign-open");

  sectionVideoStarted = true;

  setVideoOnly(videoPath);
  showSignText(text);

  video.currentTime = 0;
  video.muted = true;

  video.play()
    .then(function () {
      hidePlayButton();
    })
    .catch(function () {
      showPlayButton();
    });
}

const oldToggleSignVideo = window.toggleSignVideo;

window.toggleSignVideo = function () {
  oldToggleSignVideo();

  const box = document.getElementById("signVideoBox");

  if (box && box.classList.contains("active")) {
    setVideoOnly(introVideoPath);
    showSignText(introText);

    const video = document.getElementById("signVideo");
    if (video) {
      video.pause();
      video.currentTime = 0;
    }

    showPlayButton();
  }
};

const total = <?php echo $total; ?>;
const useArabicNumbers = <?php echo $useArabicNumbers ? "true" : "false"; ?>;

const images = [
  "../../images/math/apple.png",
  "../../images/math/ball.png",
  "../../images/math/cat.png",
  "../../images/math/car.png",
  "../../images/math/star.png",
  "../../images/math/flower.png",
  "../../images/math/duck.png",
  "../../images/math/fish.png",
  "../../images/math/teddy.png",
  "../../images/math/orange.png",
  "../../images/math/butterfly.png"
];

let firstNumber = 0;
let secondNumber = 0;
let currentImage = "";
let successPlayed = false;

const pieces = document.getElementById("pieces");
const box1 = document.getElementById("box1");
const box2 = document.getElementById("box2");
const num1 = document.getElementById("num1");
const num2 = document.getElementById("num2");
const message = document.getElementById("message");

function toArabicNumber(number) {
  const western = ["0","1","2","3","4","5","6","7","8","9"];
  const arabic = ["٠","١","٢","٣","٤","٥","٦","٧","٨","٩"];
  return String(number).replace(/[0-9]/g, function(digit) {
    return arabic[western.indexOf(digit)];
  });
}

function showNumber(number) {
  return useArabicNumbers ? toArabicNumber(number) : number;
}

function randomImage() {
  return images[Math.floor(Math.random() * images.length)];
}

let lastSplit = null;

function randomSplit(number) {

  if (number === 0) return [0, 0];

  let splits = [];

  for (let i = 0; i <= number; i++) {
    splits.push([i, number - i]);
  }

  let chosen;

  do {
    let randomIndex = Math.floor(Math.random() * splits.length);
    chosen = splits[randomIndex];

    if (Math.random() > 0.5) {
      chosen = [chosen[1], chosen[0]];
    }

  } while (JSON.stringify(chosen) === JSON.stringify(lastSplit));

  lastSplit = chosen;

  return chosen;
}
function startGame() {
  const winCard = document.getElementById("winCard");
  if (winCard) {
    winCard.classList.remove("show");
  }

  message.innerText = "";
  successPlayed = false;

  currentImage = randomImage();

  const split = randomSplit(total);
  firstNumber = split[0];
  secondNumber = split[1];

  num1.innerText = showNumber(firstNumber);
  num2.innerText = showNumber(secondNumber);

  pieces.innerHTML = "";
  box1.querySelector(".box-content").innerHTML = "";
  box2.querySelector(".box-content").innerHTML = "";

  for (let i = 0; i < total; i++) {
    const piece = document.createElement("div");
    piece.className = "piece";
    piece.id = "piece-" + i;
    piece.draggable = true;

    const img = document.createElement("img");
    img.src = currentImage;
    img.alt = "عنصر للسحب";

    piece.appendChild(img);
    piece.addEventListener("dragstart", dragStart);
    piece.addEventListener("dragend", dragEnd);

    pieces.appendChild(piece);
  }
}

function dragStart(event) {
  const item = event.currentTarget;

  event.dataTransfer.setData("text/plain", item.id);
  event.dataTransfer.effectAllowed = "move";

  const img = item.querySelector("img").cloneNode(true);
  img.style.width = "70px";
  img.style.height = "70px";
  img.style.objectFit = "contain";
  img.style.position = "absolute";
  img.style.top = "-1000px";
  img.style.left = "-1000px";

  document.body.appendChild(img);
  event.dataTransfer.setDragImage(img, 35, 35);

  setTimeout(function () {
    img.remove();
  }, 0);

  item.classList.add("dragging");
}

function dragEnd(event) {
  event.currentTarget.classList.remove("dragging");
}

function allowDrop(event) {
  event.preventDefault();
  event.currentTarget.classList.add("hover");
}

function leaveDrop(event) {
  event.currentTarget.classList.remove("hover");
}

function dropItem(event, boxId) {
  event.preventDefault();

  const box = document.getElementById(boxId);
  box.classList.remove("hover");

  const content = box.querySelector(".box-content");
  const limit = boxId === "box1" ? firstNumber : secondNumber;

  const id = event.dataTransfer.getData("text/plain");
  const item = document.getElementById(id);

  if (!item) return;

  if (content.children.length >= limit) {
    item.classList.remove("dragging");

    playGameHelp(
      "../../assets/videos/box-full.mp4",
      "هذا الصندوق ممتلئ، جرّب الصندوق الآخر"
    );

    box.classList.add("wrong");

    setTimeout(function() {
      box.classList.remove("wrong");
    }, 350);

    return;
  }

  content.appendChild(item);
  item.classList.remove("dragging");
  item.draggable = false;
  item.style.cursor = "default";

  checkWin();
}


function startConfetti() {
  let oldLayer = document.querySelector(".confetti-layer");
  if (oldLayer) oldLayer.remove();

  const layer = document.createElement("div");
  layer.className = "confetti-layer";
  document.body.appendChild(layer);

  const colors = ["#ff4f8f", "#ffd166", "#06d6a0", "#4dabf7", "#9b5de5", "#ff8c42", "#2ec4b6"];
  const count = 120;

  for (let i = 0; i < count; i++) {
    const piece = document.createElement("span");
    piece.className = "confetti-piece";
    piece.style.left = Math.random() * 100 + "vw";
    piece.style.background = colors[Math.floor(Math.random() * colors.length)];
    piece.style.animationDuration = (2.2 + Math.random() * 2.2) + "s";
    piece.style.animationDelay = (Math.random() * 0.8) + "s";
    piece.style.width = (8 + Math.random() * 10) + "px";
    piece.style.height = (10 + Math.random() * 16) + "px";
    piece.style.borderRadius = Math.random() > 0.5 ? "50%" : "4px";
    layer.appendChild(piece);
  }

  setTimeout(function () {
    layer.remove();
  }, 5200);
}

function checkWin() {
  const count1 = box1.querySelector(".box-content").children.length;
  const count2 = box2.querySelector(".box-content").children.length;

  if (!successPlayed && count1 === firstNumber && count2 === secondNumber) {
    successPlayed = true;

    startConfetti();

    playGameHelp(
      "../../images/videos/cups-win.mp4",
      "أحسنت! إجابة صحيحة"
    );

    setTimeout(function(){
      showWinCard();
    }, 2000);
  }
}
box1.addEventListener("dragover", allowDrop);
box2.addEventListener("dragover", allowDrop);

box1.addEventListener("dragleave", leaveDrop);
box2.addEventListener("dragleave", leaveDrop);

box1.addEventListener("drop", function(event) {
  dropItem(event, "box1");
});

box2.addEventListener("drop", function(event) {
  dropItem(event, "box2");
});

document.getElementById("profileBtn").onclick = function(e){
  e.stopPropagation();
  document.getElementById("profileMenu").classList.toggle("show");
};

document.onclick = function(){
  document.getElementById("profileMenu").classList.remove("show");
};

startGame();

/* ===== Touch Drag & Drop للموبايل ===== */
if ('ontouchstart' in window) {
  let dragPiece = null;
  let ghost = null;

  function getBoxUnder(x, y) {
    if (ghost) ghost.style.display = 'none';
    const el = document.elementFromPoint(x, y);
    if (ghost) ghost.style.display = '';
    return el ? el.closest('.drop-box') : null;
  }

  document.addEventListener('touchstart', function(e) {
    const piece = e.target.closest('.piece');
    if (!piece || !pieces.contains(piece)) return;
    e.preventDefault();
    dragPiece = piece;
    const t = e.touches[0];
    const rect = piece.getBoundingClientRect();

    ghost = piece.cloneNode(true);
    ghost.style.cssText = 'position:fixed;z-index:99999;pointer-events:none;opacity:0.85;width:70px;height:70px;';
    ghost.style.left = (t.clientX - 35) + 'px';
    ghost.style.top  = (t.clientY - 35) + 'px';
    document.body.appendChild(ghost);
    piece.style.opacity = '0.3';
  }, { passive: false });

  document.addEventListener('touchmove', function(e) {
    if (!dragPiece || !ghost) return;
    e.preventDefault();
    const t = e.touches[0];
    ghost.style.left = (t.clientX - 35) + 'px';
    ghost.style.top  = (t.clientY - 35) + 'px';

    [box1, box2].forEach(function(b){ b.classList.remove('tap-hover'); });
    const under = getBoxUnder(t.clientX, t.clientY);
    if (under) under.classList.add('tap-hover');
  }, { passive: false });

  document.addEventListener('touchend', function(e) {
    if (!dragPiece || !ghost) return;
    const t = e.changedTouches[0];
    ghost.remove(); ghost = null;
    dragPiece.style.opacity = '';
    [box1, box2].forEach(function(b){ b.classList.remove('tap-hover'); });

    const under = getBoxUnder(t.clientX, t.clientY);
    if (under) {
      const boxId = under.id;
      const content = under.querySelector('.box-content');
      const limit = boxId === 'box1' ? firstNumber : secondNumber;
      if (content.children.length >= limit) {
        playGameHelp('../../assets/videos/box-full.mp4', 'هذا الصندوق ممتلئ، جرّب الصندوق الآخر');
        under.classList.add('wrong');
        setTimeout(function(){ under.classList.remove('wrong'); }, 350);
      } else {
        content.appendChild(dragPiece);
        dragPiece.draggable = false;
        dragPiece.style.cursor = 'default';
        checkWin();
      }
    }
    dragPiece = null;
  }, { passive: false });
}

const profileLinks = document.querySelectorAll("#profileMenu a");

profileLinks.forEach(function(link){

  link.addEventListener("mouseenter", function(){

    /* ما يشتغل إلا إذا زر المساعدة مفتوح */
    const signBox = document.getElementById("signVideoBox");

    if(!signBox || !signBox.classList.contains("active")){
      return;
    }

    const href = link.getAttribute("href") || "";

    if(href.includes("account_settings")){

      playGameHelp(
        "../../assets/videos/settings-sign.mp4",
        "إعدادات الحساب"
      );

    }else if(href.includes("logout")){

      playGameHelp(
        "../../assets/videos/logout-sign.mp4",
        "تسجيل الخروج"
      );

    }

  });

});
function showWinCard(){
  const card = document.getElementById("winCard");
  if (card) card.classList.add("show");
}

function closeWinCard(){
  const card = document.getElementById("winCard");
  if (card) card.classList.remove("show");
}

</script>

<script>
(function () {
  if (!('ontouchstart' in window)) return;
  var pending = null;
  document.addEventListener('touchend', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signToggleBtn, .sign-video-box')) return;
    if (e.target.closest('.piece, .drop-box')) return;
    var target = e.target.closest('a, button, [data-dashboard-video]') || e.target;
    if (!target || target === document.body) { pending = null; return; }
    if (pending !== target) {
      e.preventDefault();
      e.stopImmediatePropagation();
      pending = target;
      target.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
    } else { pending = null; }
  }, { passive: false });
  document.addEventListener('touchstart', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signToggleBtn, .sign-video-box')) return;
    if (e.target.closest('.piece, .drop-box')) return;
    var target = e.target.closest('a, button, [data-dashboard-video]') || e.target;
    if (target !== pending) pending = null;
  }, { passive: true });
})();
</script>
<div id="winCard" class="win-card">

  <div class="win-box">

    <div class="win-celebrate">🎉</div>

    <div class="win-title">
      أحسنت
    </div>

    <div class="win-buttons">

      <div class="win-action">
        <a
          class="win-btn win-back"
          href="<?php echo $backLink; ?>"
          aria-label="العودة">
          ➜
        </a>
        <div class="win-label">العودة</div>
      </div>

      <div class="win-action">
        <button
          class="win-btn win-replay"
          type="button"
          onclick="startGame();closeWinCard();"
          aria-label="إعادة اللعبة">
          🔁
        </button>
        <div class="win-label">إعادة اللعبة</div>
      </div>

    </div>

  </div>

</div>
</body>
</html>