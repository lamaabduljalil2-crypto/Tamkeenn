<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../config/session_child.php';
require_once '../../config/db.php';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

function filePathFromDb($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (strpos($path, '../') === 0 || strpos($path, '../../') === 0 || strpos($path, '/') === 0) return $path;
    return '../../' . ltrim($path, '/');
}

$letters = [
    1=>["big"=>"A","small"=>"a"], 2=>["big"=>"B","small"=>"b"],
    3=>["big"=>"C","small"=>"c"], 4=>["big"=>"D","small"=>"d"],
    5=>["big"=>"E","small"=>"e"], 6=>["big"=>"F","small"=>"f"],
    7=>["big"=>"G","small"=>"g"], 8=>["big"=>"H","small"=>"h"],
    9=>["big"=>"I","small"=>"i"], 10=>["big"=>"J","small"=>"j"],
    11=>["big"=>"K","small"=>"k"], 12=>["big"=>"L","small"=>"l"],
    13=>["big"=>"M","small"=>"m"], 14=>["big"=>"N","small"=>"n"],
    15=>["big"=>"O","small"=>"o"], 16=>["big"=>"P","small"=>"p"],
    17=>["big"=>"Q","small"=>"q"], 18=>["big"=>"R","small"=>"r"],
    19=>["big"=>"S","small"=>"s"], 20=>["big"=>"T","small"=>"t"],
    21=>["big"=>"U","small"=>"u"], 22=>["big"=>"V","small"=>"v"],
    23=>["big"=>"W","small"=>"w"], 24=>["big"=>"X","small"=>"x"],
    25=>["big"=>"Y","small"=>"y"], 26=>["big"=>"Z","small"=>"z"],
];

$evenPieces = [2,4,6,8,10];
$oddPieces  = [1,3,5,7,9];

$currentId = isset($_GET['id']) ? intval($_GET['id']) : 1;
if ($currentId < 1) $currentId = 1;

/* جلب الحرف من DB */
$dbItem = null;
$stmt = mysqli_prepare($conn,
    "SELECT * FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الحروف الإنجليزية' AND letter_id=? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $currentId);
mysqli_stmt_execute($stmt);
$dbItem = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($dbItem) {
    $letterTitle = trim($dbItem['lesson_title'] ?? '');
    $customSign  = trim($dbItem['custom_sign'] ?? '');
    if ($letterTitle === '') $letterTitle = "Letter " . $currentId;

    $cleanLetterTitle = trim($letterTitle);
    $letterLength = mb_strlen($cleanLetterTitle, 'UTF-8');

    if ($letterLength > 1) {
        $currentBig          = $cleanLetterTitle;
        $currentSmall        = "";
        $currentLetterDisplay = $cleanLetterTitle;
    } else {
        $currentBig          = strtoupper($cleanLetterTitle);
        $currentSmall        = strtolower($cleanLetterTitle);
        $currentLetterDisplay = $currentBig . $currentSmall;
    }

    if ($currentId > 26 && $customSign !== '') {
        $currentSignImage = filePathFromDb($customSign);
    } else {
        $currentSignImage = "../../images/signs/english/" . $currentId . ".png";
    }

} elseif (isset($letters[$currentId])) {
    $currentBig           = $letters[$currentId]["big"];
    $currentSmall         = $letters[$currentId]["small"];
    $currentLetterDisplay = $currentBig . $currentSmall;
    $currentSignImage     = "../../images/signs/english/" . $currentId . ".png";
} else {
    die("هذا الحرف غير موجود");
}

/* متغيرات البازل */
$puzzleIndex = ($currentId - 1) % count($evenPieces);
$fixedPiece  = $evenPieces[$puzzleIndex];
$correct     = $fixedPiece - 1;

/* تسجيل التقدم */
if (!function_exists('track_progress')) {
    function track_progress($conn, $type, $key, $label = '') {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') return;
        if (!$conn || !in_array($type, ['lesson','story','game','section'], true)) return;
        mysqli_set_charset($conn, 'utf8mb4');
        $uid   = intval($_SESSION['user_id']);
        $cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
        $key   = substr((string)$key, 0, 100);
        $label = substr((string)$label, 0, 255);
        if ($key === '') return;
        $st = mysqli_prepare($conn,
            "INSERT INTO progress (user_id,child_name,activity_type,activity_key,activity_label)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE activity_label=VALUES(activity_label), created_at=CURRENT_TIMESTAMP"
        );
        if ($st) {
            mysqli_stmt_bind_param($st, "issss", $uid, $cname, $type, $key, $label);
            mysqli_stmt_execute($st);
        }
    }
}
track_progress($conn, 'game', 'english-puzzle-' . $currentId, 'لعبة البازل');

/* خيارات البازل */
$options = [$correct];
while (count($options) < 3) {
    $rand = $oddPieces[array_rand($oddPieces)];
    if (!in_array($rand, $options)) $options[] = $rand;
}
shuffle($options);

function randomWrongLetter($letters, $currentBig) {
    do {
        $randId = rand(1, 26);
        $letter = $letters[$randId]["big"] . $letters[$randId]["small"];
    } while (strtoupper($letters[$randId]["big"]) === strtoupper($currentBig));
    return $letter;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>لعبة البازل</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;height:100vh;overflow:hidden;background:#f4f4f4}
.game-page{width:100%;height:100vh;position:relative}
.game-content{width:100%;height:100%;position:relative;padding-top:120px}
.options-side{position:absolute;left:20px;top:140px;display:flex;flex-direction:column;gap:45px;z-index:10}
.option-card{width:150px;height:150px;position:relative;cursor:grab;transition:0.2s ease}
.option-card:hover{transform:scale(1.08)}
.option-card img{width:100%;height:100%;object-fit:contain;pointer-events:none}
.letter-badge{position:absolute;left:8px;top:50px;width:86px;height:58px;border-radius:50%;background:#fff6b8;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:500;color:#000;pointer-events:none;line-height:1;text-align:center;padding-top:4px;font-family:Arial,sans-serif}
.puzzle-side{position:absolute;right:430px;top:170px;width:350px;height:350px;display:flex;align-items:center;justify-content:center}
.drop-zone{width:300px;height:300px;position:relative;overflow:visible;display:flex;align-items:center;justify-content:center}
.fixed-piece{width:300px;height:300px;object-fit:contain}
.sign-img{position:absolute;width:145px;height:145px;left:78px;top:78px;border-radius:50%;object-fit:cover;pointer-events:none}
.result{position:absolute;bottom:40px;left:50%;transform:translateX(-50%);font-size:38px;font-weight:bold}

/* الداش */
.children-dash{width:100%;height:110px;background:linear-gradient(90deg,#ffecd2,#fcb69f);position:fixed;top:0;right:0;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,0.12)}
.children-dash-inner{width:100%;height:100%;display:flex;align-items:center;justify-content:space-between;padding:0 35px}
.logo-box{display:flex;align-items:center;justify-content:center;text-decoration:none}
.logo-box img{width:95px;object-fit:contain}
.dash-nav{display:flex;align-items:center;gap:22px}
.circle-icon{position:relative;width:78px;height:78px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:0.25s;box-shadow:0 4px 12px rgba(0,0,0,0.12)}
.circle-icon:hover{transform:translateY(-4px) scale(1.05)}
.nav-icon-video{width:60px;height:60px;object-fit:contain;border-radius:50%}
.dash-hover-label{position:absolute;bottom:-30px;background:#ff5e8a;color:white;padding:6px 16px;border-radius:20px;font-size:15px;font-weight:bold;white-space:nowrap;opacity:0;transition:0.2s}
.circle-icon:hover .dash-hover-label{opacity:1}
.profile-wrap{position:relative}
.profile-btn{border:none;background:white;border-radius:50px;padding:10px 18px;display:flex;align-items:center;gap:12px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.12);transition:0.2s}
.profile-btn:hover{transform:translateY(-2px)}
.profile-hello{font-size:18px;font-weight:bold;color:#444}
.profile-video-box{width:55px;height:55px;border-radius:50%;overflow:hidden}
.profile-video-box video{width:100%;height:100%;object-fit:cover}
.profile-menu{position:absolute;top:85px;left:0;background:white;border-radius:20px;min-width:220px;overflow:hidden;display:none;box-shadow:0 10px 25px rgba(0,0,0,0.12)}
.profile-menu a{display:block;padding:15px 18px;text-decoration:none;color:#444;font-size:17px;font-weight:bold;transition:0.2s}
.profile-menu a:hover{background:#fff0f4;color:#ff5e8a}
.profile-menu.show{display:block}

/* الشرح الجانبي */
.guide-panel{position:absolute;right:40px;top:150px;width:260px;height:420px;background:#fff8f1;border-radius:28px;box-shadow:0 10px 24px rgba(0,0,0,.12);padding:14px;z-index:50}
.guide-title{text-align:center;font-size:22px;font-weight:bold;color:#8a5737;margin-bottom:10px;line-height:1.5}
.guide-video-wrap{position:relative;width:100%;height:340px;border-radius:24px;overflow:hidden;background:#ddd}
.guide-video{width:100%;height:100%;object-fit:cover;display:block}
.video-overlay-btn{position:absolute;inset:0;border:none;background:rgba(0,0,0,.12);display:flex;align-items:center;justify-content:center;cursor:pointer}
.video-overlay-btn span{width:70px;height:70px;border-radius:50%;background:white;color:#8a5737;font-size:34px;display:flex;align-items:center;justify-content:center}

/* كارد الفوز */
.win-overlay{position:fixed;inset:0;background:rgba(255,255,255,.25);backdrop-filter:blur(10px);display:none;align-items:center;justify-content:center;z-index:99998}
.win-card{width:380px;background:rgba(255,255,255,.92);border-radius:34px;padding:22px;box-shadow:0 20px 45px rgba(0,0,0,.22);text-align:center;border:2px solid rgba(255,255,255,.75)}
.win-title{text-align:center;font-size:32px;font-weight:900;color:#21425f;margin-bottom:14px}
.win-video-wrap{position:relative;width:100%;height:300px;border-radius:24px;overflow:hidden;background:#ddd;margin-bottom:18px}
.win-video{width:100%;height:100%;object-fit:cover;display:block}
.win-buttons{display:flex;justify-content:center;align-items:flex-start;gap:20px}
.action-item{display:flex;flex-direction:column;align-items:center;gap:7px}
.action-label{font-size:15px;font-weight:900;color:#7b5237}
#restartBtn,#exitBtn{border:none;outline:none;cursor:pointer;font-family:Arial,sans-serif;font-weight:900;text-decoration:none;color:white;height:54px;width:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 22px rgba(92,119,255,.25);transition:.2s ease}
#restartBtn{font-size:27px;background:rgb(255,217,0)}
#exitBtn{font-size:30px;background:#df5459}
#restartBtn:hover,#exitBtn:hover{transform:translateY(-2px);filter:brightness(1.04)}

/* الزينة */
#confettiFull{position:fixed;inset:0;width:100vw;height:100vh;display:none;pointer-events:none;z-index:99999}

@media(max-width:768px){
  /* هيدر */
  .children-dash{height:60px!important;}
  .children-dash-inner{height:60px!important;padding:0 10px!important;}
  .logo-box img{width:40px!important;height:40px!important;}
  .circle-icon{width:44px!important;height:44px!important;}
  .nav-icon-video{width:36px!important;height:36px!important;}
  .profile-btn{padding:5px 8px!important;gap:5px!important;}
  .profile-video-box{width:34px!important;height:34px!important;}
  .profile-hello{font-size:11px!important;}
  .profile-menu{top:50px!important;left:0!important;right:auto!important;}

  /* محتوى اللعبة - صف جانبي */
  .game-content{padding-top:80px!important;display:flex!important;flex-direction:row!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;gap:14px!important;overflow-y:auto!important;height:auto!important;padding-left:8px!important;padding-right:8px!important;}
  .puzzle-side{order:1!important;}
  .options-side{order:2!important;}
  .guide-panel{order:3!important;}

  /* البازل على اليمين */
  .puzzle-side{position:static!important;width:200px!important;height:200px!important;flex-shrink:0!important;margin-right:8px!important;margin-left:auto!important;}
  .drop-zone{width:185px!important;height:185px!important;}
  .fixed-piece{width:185px!important;height:185px!important;}
  .sign-img{width:88px!important;height:88px!important;left:48px!important;top:48px!important;}

  /* الخيارات عمود على اليسار */
  .options-side{position:static!important;flex-direction:column!important;gap:10px!important;align-items:center!important;width:auto!important;padding:0!important;margin-top:0!important;min-height:435px!important;}
  .option-card{width:148px!important;height:148px!important;}
  .letter-badge{width:80px!important;height:52px!important;font-size:34px!important;left:5px!important;top:46px!important;}

  /* فيديو الإشارة */
  .guide-panel{position:static!important;width:50%!important;height:auto!important;border-radius:16px!important;padding:6px!important;margin:14px auto 0!important;max-width:180px!important;flex:0 0 100%!important;}
  .guide-video-wrap{height:200px!important;}
  .guide-video{object-fit:contain!important;background:#000!important;}
  .guide-title{font-size:11px!important;margin-bottom:3px!important;}

  /* نتيجة */
  .result{position:static!important;font-size:22px!important;text-align:center!important;margin-top:4px!important;}

  /* كارد الفوز */
  .win-card{width:88vw!important;padding:14px!important;}
  .win-video-wrap{height:200px!important;border-radius:16px!important;}
  .win-video{object-fit:contain!important;background:#000!important;}
  .win-title{font-size:22px!important;margin-bottom:8px!important;}
  .win-buttons{gap:14px!important;}
}
</style>
</head>
<body>

<header class="children-dash">
  <div class="children-dash-inner" style="display:flex!important;justify-content:center!important;align-items:center!important;position:relative!important;padding:0 10px!important;">

    <a href="../../index.php" class="logo-box" style="position:absolute!important;right:10px!important;top:50%!important;transform:translateY(-50%)!important;">
      <img src="../../logo.png" alt="logo">
    </a>

    <nav class="dash-nav" style="display:flex!important;flex-direction:row!important;align-items:center!important;gap:16px!important;flex:unset!important;">
      <a href="english-letter.php?id=<?php echo $currentId; ?>" class="circle-icon">
        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="../../assets/icons/letter.mp4" type="video/mp4">
        </video>
        <span class="dash-hover-label">اللغة الانجليزية </span>
      </a>
    </nav>

    <div class="profile-wrap" id="profileWrap" style="position:absolute!important;left:10px!important;top:50%!important;transform:translateY(-50%)!important;">
      <button type="button" class="profile-btn" id="profileBtn">
        <span class="profile-hello">مرحباً <?php echo htmlspecialchars($child_display_name); ?></span>
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

<aside class="guide-panel">
    <div class="guide-title" id="guideTitle">اسحب القطعة المناسبة وضعها في مكانها</div>
    <div class="guide-video-wrap">
        <video id="guideVideo" class="guide-video" playsinline preload="metadata">
            <source src="../../images/videos/puzzle-guide.mp4" type="video/mp4">
        </video>
        <button class="video-overlay-btn" id="guideOverlay" type="button"><span>▶</span></button>
    </div>
</aside>

<div id="winOverlay" class="win-overlay">
    <div class="win-card">
        <div class="win-title">أحسنت</div>
        <div class="win-video-wrap">
            <video id="winVideo" class="win-video" playsinline preload="metadata">
                <source src="../../images/videos/cups-win.mp4" type="video/mp4">
            </video>
        </div>
        <div class="win-buttons">
            <div class="action-item">
                <button id="restartBtn" type="button" onclick="location.reload()">🔁</button>
                <span class="action-label">إعادة اللعبة</span>
            </div>
            <div class="action-item">
                <a id="exitBtn" href="english-letter.php?id=<?php echo $currentId; ?>">➜</a>
                <span class="action-label">العودة</span>
            </div>
        </div>
    </div>
</div>

<canvas id="confettiFull"></canvas>

<div class="game-page">
    <div class="game-content">

        <div class="options-side">
            <?php foreach($options as $op): ?>
                <div class="option-card" data-id="<?php echo $op; ?>">
                    <img src="../../images/puzzle/<?php echo $op; ?>.png">
                    <div class="letter-badge">
                        <?php
                        if ($op == $correct) {
                            echo htmlspecialchars($currentLetterDisplay);
                        } else {
                            echo htmlspecialchars(randomWrongLetter($letters, $currentBig));
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="puzzle-side">
            <div class="drop-zone" id="dropZone" data-correct="<?php echo $correct; ?>">
                <img src="../../images/puzzle/<?php echo $fixedPiece; ?>.png" class="fixed-piece">
                <img src="<?php echo htmlspecialchars($currentSignImage); ?>" class="sign-img"
                     onerror="this.onerror=null;this.src='../../images/signs/english/<?php echo $currentId; ?>.png';">
            </div>
        </div>

    </div>
    <div class="result" id="resultText"></div>
</div>


<script>
const profileBtn = document.getElementById("profileBtn");
const profileMenu = document.getElementById("profileMenu");
if(profileBtn && profileMenu){
    profileBtn.addEventListener("click", function(e){ e.stopPropagation(); profileMenu.classList.toggle("show"); });
    document.addEventListener("click", function(){ profileMenu.classList.remove("show"); });
}

const guideVideo   = document.getElementById("guideVideo");
const guideOverlay = document.getElementById("guideOverlay");
const guideTitle   = document.getElementById("guideTitle");

function setGuideVideo(src, text, autoplay=false){
    guideTitle.textContent = text;
    guideVideo.src = src;
    guideVideo.load();
    if(autoplay){ guideVideo.play(); guideOverlay.style.display="none"; }
    else { guideOverlay.style.display="flex"; }
}
function toggleGuideVideo(){
    if(guideVideo.paused){ guideVideo.play(); guideOverlay.style.display="none"; }
    else { guideVideo.pause(); guideOverlay.style.display="flex"; }
}
guideOverlay.addEventListener("click", toggleGuideVideo);
guideVideo.addEventListener("click", toggleGuideVideo);
guideVideo.addEventListener("ended", function(){ guideOverlay.style.display="flex"; });

const winOverlay = document.getElementById("winOverlay");
const winVideo   = document.getElementById("winVideo");
function showWinCard(){
    winOverlay.style.display="flex";
    winVideo.currentTime=0;
    winVideo.play();
}

/* الزينة */
const confettiCanvas = document.getElementById("confettiFull");
const cctx = confettiCanvas.getContext("2d");
let confettiOn=false, confettiRAF=null, pieces=[];

function resizeConfetti(){
    confettiCanvas.width  = window.innerWidth  * devicePixelRatio;
    confettiCanvas.height = window.innerHeight * devicePixelRatio;
    cctx.setTransform(devicePixelRatio,0,0,devicePixelRatio,0,0);
}
window.addEventListener("resize", resizeConfetti);
resizeConfetti();

function startConfettiFromTop(){
    confettiOn=true;
    confettiCanvas.style.display="block";
    resizeConfetti();
    const W=window.innerWidth, H=window.innerHeight;
    pieces=Array.from({length:220},()=>({
        x:Math.random()*W, y:-Math.random()*(H*.8)-20,
        w:6+Math.random()*10, h:3+Math.random()*8,
        vy:2+Math.random()*5, vx:-1.5+Math.random()*3,
        rot:Math.random()*Math.PI, vr:-.12+Math.random()*.24, a:.75+Math.random()*.25
    }));
    const start=performance.now();
    function tick(ts){
        if(!confettiOn) return;
        if(ts-start>3500){ stopConfetti(); return; }
        cctx.clearRect(0,0,window.innerWidth,window.innerHeight);
        pieces.forEach(p=>{
            p.x+=p.vx; p.y+=p.vy; p.rot+=p.vr;
            if(p.x<-30) p.x=W+30;
            if(p.x>W+30) p.x=-30;
            cctx.save(); cctx.translate(p.x,p.y); cctx.rotate(p.rot);
            cctx.fillStyle=`hsla(${(p.x+p.y+ts-start)%360},92%,60%,${p.a})`;
            cctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);
            cctx.restore();
        });
        confettiRAF=requestAnimationFrame(tick);
    }
    confettiRAF=requestAnimationFrame(tick);
}
function stopConfetti(){
    confettiOn=false;
    confettiCanvas.style.display="none";
    cctx.clearRect(0,0,window.innerWidth,window.innerHeight);
    if(confettiRAF) cancelAnimationFrame(confettiRAF);
    confettiRAF=null;
}

/* اللعبة */
const cards      = document.querySelectorAll(".option-card");
const dropZone   = document.getElementById("dropZone");
const resultText = document.getElementById("resultText");
let active=null, home=null;

cards.forEach(card=>{
    card.addEventListener("mousedown", e=>{
        e.preventDefault();
        active=card;
        home={parent:card.parentElement, next:card.nextElementSibling};
        const rect=card.getBoundingClientRect();
        card.style.position="fixed";
        card.style.left=rect.left+"px";
        card.style.top=rect.top+"px";
        card.style.width="265px";
        card.style.height="265px";
        card.style.zIndex="99999";
        card.style.transform="none";
        card.style.cursor="grabbing";
        card.querySelector("img").style.width="265px";
        card.querySelector("img").style.height="265px";
        const badge=card.querySelector(".letter-badge");
        badge.style.width="130px"; badge.style.height="85px";
        badge.style.left="15px";  badge.style.top="90px";
        badge.style.fontSize="55px"; badge.style.lineHeight="1";
        badge.style.paddingTop="6px";
        document.body.appendChild(card);
        move(e);
        document.addEventListener("mousemove",move);
        document.addEventListener("mouseup",up);
    });
});

function move(e){
    if(!active) return;
    active.style.left=(e.clientX-132)+"px";
    active.style.top=(e.clientY-132)+"px";
}

function up(e){
    if(!active) return;
    document.removeEventListener("mousemove",move);
    document.removeEventListener("mouseup",up);
    const zone=dropZone.getBoundingClientRect();
    const correct=dropZone.dataset.correct;
    const near = e.clientX>zone.left-170 && e.clientX<zone.right+80 &&
                 e.clientY>zone.top-100  && e.clientY<zone.bottom+100;
    if(near && active.dataset.id==correct){
        active.style.position="absolute";
        active.style.left="-152px"; active.style.top="8px";
        active.style.width="265px"; active.style.height="265px";
        active.style.zIndex="20";   active.style.cursor="default";
        dropZone.appendChild(active);
        resultText.innerHTML="";
        startConfettiFromTop();
        setTimeout(showWinCard, 3000);
        document.querySelectorAll(".option-card").forEach(c=>{
            if(c!==active){ c.style.pointerEvents="none"; c.style.opacity="0.3"; }
        });
    } else {
        active.removeAttribute("style");
        active.querySelector("img").removeAttribute("style");
        active.querySelector(".letter-badge").removeAttribute("style");
        if(home.next) home.parent.insertBefore(active,home.next);
        else home.parent.appendChild(active);
        resultText.innerHTML="حاول مرة أخرى";
        resultText.style.color="#d32f2f";
        setGuideVideo("../../images/videos/cups-lose.mp4","حاول مرة أخرى",true);
        setTimeout(()=>{ resultText.innerHTML=""; },1200);
    }
    active=null; home=null;
}

setGuideVideo("../../images/videos/puzzle-guide.mp4","اسحب القطعة المناسبة وضعها في مكانها",false);

window.addEventListener("keydown", function(e){
    if(["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Space"].includes(e.code)) e.preventDefault();
},{passive:false});
</script>
</body>
</html>
