<?php
require_once __DIR__ . '/../../config/session_child.php';
require_once '../../config/db.php';
$cfg_cups = 3; $cfg_swaps = 5; $cfg_swap_speed = 750;
$gs_res = $conn->query("SELECT game_settings FROM games WHERE game_type='cups' AND is_active=1 LIMIT 1");
if ($gs_res && $gs_row = $gs_res->fetch_assoc()) {
    $s = json_decode($gs_row['game_settings'] ?? '{}', true) ?? [];
    $cfg_cups       = (int)($s['cups']       ?? 3);
    $cfg_swaps      = (int)($s['swaps']      ?? 5);
    $cfg_swap_speed = (int)($s['swap_speed'] ?? 750);
}
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>لعبة الجبنة تحت الأكواب</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../../assets/css/arabic-grid.css">

<style>
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

body{
    direction:rtl;
    font-family:"Cairo", sans-serif;
    background:
        radial-gradient(circle at top right, rgba(255,209,102,.30), transparent 20%),
        radial-gradient(circle at bottom left, rgba(122,214,255,.22), transparent 22%),
        linear-gradient(180deg,#d9cde0 0%,#dfcae3 50%,#eef7ff 100%);
    color:#20314f;
       overflow-x:hidden;
    height: 100vh;
}

a{
    text-decoration:none;
    color:inherit;
}

/* ================= HEADER ================= */

.children-dash{
    width:100%;
    height:110px;
    background:linear-gradient(135deg,#fdf7ff,#f4e7ff);
    box-shadow:0 8px 24px rgba(0,0,0,0.08);
    position:sticky;
    top:0;
    z-index:1000;
    border-bottom:3px solid rgba(206,147,216,.35);
}

.children-dash-inner{
    max-width:1450px;
    margin:auto;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 28px;
}

.logo-box{
    display:flex;
    align-items:center;
    justify-content:center;
    transition:.25s ease;
}

.logo-box:hover{
    transform:scale(1.05);
}

.logo-box img{
    width:82px;
    height:82px;
    object-fit:contain;
    filter:drop-shadow(0 6px 12px rgba(0,0,0,.12));
}

.dash-nav{
    display:flex;
    align-items:center;
    gap:24px;
}

.circle-icon{
    width:82px;
    height:82px;
    border-radius:50%;
    overflow:visible;
    background:rgba(255,255,255,.88);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    box-shadow:
        0 6px 16px rgba(0,0,0,.08),
        inset 0 0 0 2px rgba(255,255,255,.8);
    transition:.25s ease;
}

.circle-icon:hover{
    transform:translateY(-5px) scale(1.07);
    box-shadow:
        0 10px 22px rgba(0,0,0,.14),
        0 0 0 6px rgba(206,147,216,.22);
}

.circle-icon video{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:50%;
}

.dash-hover-label{
    position:absolute;
    bottom:-34px;
    left:50%;
    transform:translateX(-50%);
    background:linear-gradient(135deg,#ce93d8,#ba68c8);
    color:white;
    padding:6px 16px;
    border-radius:999px;
    font-size:15px;
    font-weight:800;
    white-space:nowrap;
    opacity:0;
    visibility:hidden;
    transition:.25s ease;
    box-shadow:0 6px 14px rgba(0,0,0,.12);
    pointer-events:none;
}

.circle-icon:hover .dash-hover-label{
    opacity:1;
    visibility:visible;
    bottom:-42px;
}

.profile-wrap{
    position:relative;
}

.profile-btn{
    border:none;
    background:rgba(255,255,255,.88);
    border-radius:999px;
    padding:8px 12px 8px 18px;
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    box-shadow:0 6px 16px rgba(0,0,0,.08);
    font-family:"Cairo", sans-serif;
}

.profile-hello{
    font-size:17px;
    font-weight:800;
    color:#7b5237;
}

.profile-video-box{
    width:54px;
    height:54px;
    border-radius:50%;
    overflow:hidden;
}

.profile-video-box video{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-menu{
    position:absolute;
    top:76px;
    left:0;
    min-width:220px;
    background:white;
    border-radius:18px;
    overflow:hidden;
    display:none;
    flex-direction:column;
    box-shadow:0 12px 28px rgba(0,0,0,.14);
    z-index:2000;
}

.profile-wrap.active .profile-menu{
    display:flex;
}

.profile-menu a{
    padding:14px 18px;
    color:#7b5237;
    font-size:16px;
    font-weight:800;
}

.profile-menu a:hover{
    background:#f8efe6;
}

/* ================= PAGE ================= */

.game-page{
    width:min(1320px,96%);
    margin:24px auto 0;
}

.page-title{
    text-align:center;
    margin-bottom:16px;
}

.page-title h2{
    font-size:32px;
    color:#8a5737;
    font-weight:900;
}

.page-title p{
    font-size:18px;
    color:#9d7558;
    font-weight:700;
}

.game-layout{
    display:grid;
    grid-template-columns:1fr 270px;
    gap:18px;
    align-items:stretch;
}

/* ================= GAME ================= */

.game-card{
    background:rgba(211,168,211,.82);
    border:1px solid rgba(151,190,255,.22);
    backdrop-filter:blur(10px);
    border-radius:32px;
    padding:24px;
    box-shadow:0 22px 50px rgba(68,102,153,.12);
    position:relative;
    overflow:hidden;
}

.gameStage{
    position:relative;
    width:100%;
    min-height:460px;
    border-radius:24px;
    overflow:hidden;
    background:linear-gradient(180deg,#c9a5a5 0%,#c9a5a5 45%,#c9a5a5 100%);
    border:2px solid rgba(145,190,230,.22);
}

#game{
    width:100%;
    height:auto;
    display:block;
    border-radius:24px;
    position:relative;
    z-index:1;
}

#msg{
    position:absolute;
    top:18px;
    right:18px;
    z-index:5;
    min-width:120px;
    min-height:44px;
    padding:8px 18px;
    border-radius:18px;
    background:rgba(255,255,255,.88);
    box-shadow:0 10px 22px rgba(60,115,180,.12);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    font-weight:900;
    color:#21425f;
}

#startOverlay{
    position:absolute;
    inset:0;
    z-index:10;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(232,246,255,.35);
    backdrop-filter:blur(6px);
    transition:.35s ease;
}

#startOverlay.hidden{
    opacity:0;
    visibility:hidden;
    pointer-events:none;
}

#playBtn{
    width:96px;
    height:96px;
    border-radius:50%;
    border:none;
    cursor:pointer;
    background:rgba(255,255,255,.9);
    color:#2d80c8;
    font-size:46px;
    font-weight:900;
    box-shadow:0 14px 32px rgba(37,80,130,.22);
    display:flex;
    align-items:center;
    justify-content:center;
    padding-right:5px;
    transition:.22s ease;
}

#playBtn:hover{
    transform:scale(1.06);
    background:white;
}

/* ================= VIDEO PANEL ================= */

.guide-panel{
    background:linear-gradient(180deg,#fff8f1,#f9ecdf);
    border-radius:28px;
    box-shadow:0 10px 24px rgba(133,97,69,.12);
    padding:16px;
    display:flex;
    flex-direction:column;
    min-height:100%;
}

.guide-title{
    text-align:center;
    font-size:22px;
    font-weight:900;
    color:#8a5737;
    margin-bottom:12px;
    line-height:1.5;
}

.guide-video-wrap{
    position:relative;
    flex:1;
    min-height:360px;
    border-radius:24px;
    overflow:hidden;
    background:#d1bfae;
}

.guide-video{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.video-overlay-btn{
    position:absolute;
    inset:0;
    border:none;
    background:rgba(91,67,48,.12);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.video-overlay-btn span{
    width:78px;
    height:78px;
    border-radius:50%;
    background:rgba(255,255,255,.92);
    color:#8a5737;
    font-size:35px;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
}

/* ================= END ================= */

#endOverlay{
    position:absolute;
    inset:0;
    z-index:20;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.20);
    backdrop-filter:blur(10px);
    border-radius:24px;
}

.end-box{
    width:min(420px,90%);
    background:rgba(255,255,255,.90);
    border:2px solid rgba(255,255,255,.78);
    border-radius:34px;
    padding:28px 24px;
    text-align:center;
    box-shadow:0 20px 45px rgba(35,72,110,.22);
    animation:popIn .35s ease;
}

@keyframes popIn{
    from{transform:scale(.88);opacity:0;}
    to{transform:scale(1);opacity:1;}
}

#endEmoji{
    font-size:64px;
    line-height:1;
    margin-bottom:10px;
}

#endTitle{
    margin:0 0 18px;
    color:#21425f;
    font-size:34px;
    font-weight:900;
}

.end-actions{
    display:flex;
    justify-content:center;
    align-items:flex-start;
    gap:20px;
}

.action-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:7px;
}

.action-label{
    font-size:15px;
    font-weight:900;
    color:#7b5237;
}

#restartBtn,
#exitBtn{
    border:none;
    outline:none;
    cursor:pointer;
    font-family:"Cairo", sans-serif;
    font-weight:900;
    text-decoration:none;
    color:white;
    height:54px;
    border-radius:16px;
    box-shadow:0 10px 22px rgba(92,119,255,.25);
    transition:.2s ease;
}

#restartBtn{
    width:64px;
    font-size:27px;
    background:rgb(255, 217, 0);}

#exitBtn{
    width:64px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:30px;
    background:#df5459;
}

#restartBtn:hover,
#exitBtn:hover{
    transform:translateY(-2px);
    filter:brightness(1.04);
}

#confettiFull{
    position:fixed;
    inset:0;
    width:100vw;
    height:100vh;
    display:none;
    pointer-events:none;
    z-index:9999;
}

.winGlow{
    animation:winPulse 1s ease-in-out infinite alternate;
}

@keyframes winPulse{
    from{box-shadow:0 22px 50px rgba(68,102,153,.12);}
    to{
        box-shadow:
            0 24px 55px rgba(68,102,153,.18),
            0 0 0 5px rgba(255,215,0,.10);
    }
}

@media(max-width:900px){
    body{
        overflow:auto;
    }

    .children-dash{
        height:auto;
        padding:14px 0;
    }

    .children-dash-inner{
        flex-direction:column;
        gap:18px;
    }

    .game-layout{
        grid-template-columns:1fr;
    }

    .guide-video-wrap{
        min-height:320px;
    }
}

@media(max-width:768px){
  html,body{height:auto!important;overflow-x:hidden!important;overflow-y:auto!important;}
  .children-dash{height:auto!important;padding:6px 10px!important;position:sticky!important;top:0!important;z-index:1000!important;}
  .children-dash-inner{display:grid!important;grid-template-columns:auto 1fr auto!important;align-items:center!important;gap:8px!important;padding:4px 8px!important;min-height:unset!important;}
  .dash-nav{justify-content:center!important;}
  .logo-box img{width:44px!important;height:44px!important;}
  .dash-nav{gap:8px!important;}
  .circle-icon{width:50px!important;height:50px!important;}
  .dash-hover-label{display:none!important;}
  .profile-btn{padding:4px 8px 4px 6px!important;gap:6px!important;}
  .profile-hello{font-size:12px!important;}
  .profile-video-box{width:36px!important;height:36px!important;}
  .memory-page,.game-page{height:auto!important;min-height:unset!important;padding-bottom:20px!important;}
  .hero-box{height:auto!important;}
  .game-layout{grid-template-columns:1fr!important;}
  .guide-panel{min-height:unset!important;order:-1!important;}
  .guide-video-wrap{min-height:150px!important;max-height:170px!important;}
  .guide-video{object-fit:contain!important;background:#000!important;}
  .gameStage{min-height:unset!important;height:280px!important;}
  #game{width:100%!important;height:100%!important;}
  .page-title h2{font-size:22px!important;}
}
</style>
</head>

<body>

<header class="children-dash">
  <div class="children-dash-inner">

    <div class="dash-start">
      <a href="../../index.php" class="logo-box" tabindex="0">
        <img src="../../logo.png" alt="logo">
      </a>
    </div>

    <nav class="dash-nav">
      <a href="../../auth/children.php"
         class="circle-icon home-icon"
         tabindex="0"
         aria-label="الرئيسية">
        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="../../assets/icons/children.mp4" type="video/mp4">
        </video>
        <span class="dash-hover-label">الرئيسية</span>
      </a>
    </nav>

    <div class="profile-wrap" id="profileWrap">
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

<main class="game-page">

  <div class="page-title">
    <h2>لعبة الجبنة و الأكواب</h2>
  </div>

  <div class="game-layout">

    <section class="game-card">
      <div class="gameStage">
        <canvas id="game" width="1000" height="460"></canvas>

        <div id="msg"> ⭐</div>

        <div id="startOverlay">
          <button id="playBtn" type="button" aria-label="ابدأ اللعبة">▶</button>
        </div>

        <div id="endOverlay">
          <div class="end-box">
            <div id="endEmoji"></div>
            <h2 id="endTitle">انتهت اللعبة</h2>

            <div class="end-actions">

              <div class="action-item">
                <button id="restartBtn" type="button">🔁</button>
                <span class="action-label">إعادة اللعبة</span>
              </div>

              <div class="action-item">
                <a id="exitBtn" href="../../auth/children.php" aria-label="العودة لصفحة الطفل">➜</a>
                <span class="action-label">العودة</span>
              </div>

            </div>
          </div>
        </div>

      </div>
    </section>

    <aside class="guide-panel">
      <div class="guide-title" id="guideTitle">
        راقب قطعة الجبن وتتبع مسارها
      </div>

      <div class="guide-video-wrap">
        <video id="guideVideo" class="guide-video" playsinline preload="metadata">
          <source src="../../images/videos/cups-guide.mp4" type="video/mp4">
        </video>

        <button class="video-overlay-btn" id="guideOverlay" type="button" onclick="toggleGuideVideo()">
          <span>▶</span>
        </button>
      </div>
    </aside>

  </div>
</main>

<canvas id="confettiFull"></canvas>

<script>
const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");

const msgEl = document.getElementById("msg");
const restartBtn = document.getElementById("restartBtn");
const startOverlay = document.getElementById("startOverlay");
const playBtn = document.getElementById("playBtn");
const endOverlay = document.getElementById("endOverlay");
const endTitle = document.getElementById("endTitle");
const endEmoji = document.getElementById("endEmoji");

const guideVideo = document.getElementById("guideVideo");
const guideOverlay = document.getElementById("guideOverlay");
const guideTitle = document.getElementById("guideTitle");

const profileBtn = document.getElementById("profileBtn");
const profileWrap = document.getElementById("profileWrap");

if(profileBtn && profileWrap){
    profileBtn.addEventListener("click", function(e){
        e.stopPropagation();
        profileWrap.classList.toggle("active");
    });

    document.addEventListener("click", function(){
        profileWrap.classList.remove("active");
    });
}

const confettiCanvas = document.getElementById("confettiFull");
const cctx = confettiCanvas.getContext("2d");

const cheeseImg = new Image();
cheeseImg.src = "cheese.png";

const cupImg = new Image();
cupImg.src = "cup.png";

const SETTINGS = {
  CUP_COUNT: <?php echo (int)$cfg_cups; ?>,
  CUP_W: 190,
  CUP_H: 230,
  CUP_BASE_Y: 210,
  CUP_RAISE_Y: 120,

  CHEESE_SIZE: 85,

  SHOW_CHEESE_TIME: 900,
  DROP_TIME: 350,

  SHUFFLE_SWAPS: <?php echo (int)$cfg_swaps; ?>,
  SHUFFLE_SWAP_TIME: <?php echo (int)$cfg_swap_speed; ?>,
  SHUFFLE_GAP: 180,

  CONFETTI_DURATION: 3500,
  CONFETTI_COUNT: 220,
};

const SHAKE_DURATION = 450;
const SHAKE_AMPLITUDE = 10;

let cups = [];
let cheeseCupId = 0;
let state = "idle";
let allowPick = false;
let pickedCupId = null;

let anim = null;
let loopId = null;

let errorCupId = null;
let errorStart = 0;
let gameEnded = false;

function randInt(min, max){
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function lerp(a,b,t){
  return a + (b-a)*t;
}

function easeInOut(t){
  return t*t*(3-2*t);
}

function setMessage(t){
  msgEl.textContent = t;
}

function setGuideVideo(src, titleText, autoplay = false){
  guideTitle.textContent = titleText;
  guideVideo.src = src;
  guideVideo.load();

  if(autoplay){
    guideVideo.play();
    guideOverlay.style.display = "none";
  }else{
    guideOverlay.style.display = "flex";
  }
}

function toggleGuideVideo(){
  if(guideVideo.paused){
    guideVideo.play();
    guideOverlay.style.display = "none";
  }else{
    guideVideo.pause();
    guideOverlay.style.display = "flex";
  }
}

guideVideo.addEventListener("click", toggleGuideVideo);

guideVideo.addEventListener("ended", () => {
  guideOverlay.style.display = "flex";
});

function resizeConfetti(){
  confettiCanvas.width = window.innerWidth * devicePixelRatio;
  confettiCanvas.height = window.innerHeight * devicePixelRatio;
  cctx.setTransform(devicePixelRatio,0,0,devicePixelRatio,0,0);
}

window.addEventListener("resize", resizeConfetti);
resizeConfetti();

function drawCup(cup){
  let offsetX = 0;

  if(errorCupId === cup.id){
    const elapsed = performance.now() - errorStart;

    if(elapsed < SHAKE_DURATION){
      offsetX = Math.sin(elapsed * 0.06) * SHAKE_AMPLITUDE;
    }else{
      errorCupId = null;
    }
  }

  if(!cupImg.complete || cupImg.naturalWidth === 0){
    ctx.fillStyle = "rgba(255,255,255,0.10)";
    ctx.fillRect(cup.x + offsetX, cup.y, SETTINGS.CUP_W, SETTINGS.CUP_H);
  }else{
    ctx.drawImage(cupImg, cup.x + offsetX, cup.y, SETTINGS.CUP_W, SETTINGS.CUP_H);
  }

  if(errorCupId === cup.id){
    ctx.save();
    ctx.lineWidth = 5;
    ctx.strokeStyle = "rgba(255,70,70,.95)";
    ctx.beginPath();
    ctx.roundRect(cup.x + offsetX + 6, cup.y + 6, SETTINGS.CUP_W - 12, SETTINGS.CUP_H - 12, 18);
    ctx.stroke();

    const cx = cup.x + offsetX + SETTINGS.CUP_W / 2;
    const cy = cup.y + 36;

    ctx.lineWidth = 6;
    ctx.strokeStyle = "rgba(255,70,70,.95)";
    ctx.beginPath();
    ctx.moveTo(cx - 14, cy - 14);
    ctx.lineTo(cx + 14, cy + 14);
    ctx.moveTo(cx + 14, cy - 14);
    ctx.lineTo(cx - 14, cy + 14);
    ctx.stroke();
    ctx.restore();
  }
}

function drawCheeseUnderCup(cup){
  if(!cheeseImg.complete || cheeseImg.naturalWidth === 0) return;

  const x = cup.x + (SETTINGS.CUP_W - SETTINGS.CHEESE_SIZE) / 2;
  const y = SETTINGS.CUP_BASE_Y + SETTINGS.CUP_H - 92;

  ctx.drawImage(cheeseImg, x, y, SETTINGS.CHEESE_SIZE, SETTINGS.CHEESE_SIZE);
}

function initCups(){
  const totalW = SETTINGS.CUP_COUNT * SETTINGS.CUP_W;
  const gap = (canvas.width - totalW) / (SETTINGS.CUP_COUNT + 1);

  cups = [];

  for(let i = 0; i < SETTINGS.CUP_COUNT; i++){
    const x = gap + i * (SETTINGS.CUP_W + gap);

    cups.push({
      id:i,
      x:x,
      y:SETTINGS.CUP_RAISE_Y,
      targetX:x,
      targetY:SETTINGS.CUP_RAISE_Y
    });
  }

  cheeseCupId = randInt(0, SETTINGS.CUP_COUNT - 1);
  pickedCupId = null;
  allowPick = false;
  anim = null;
  errorCupId = null;
  errorStart = 0;
  gameEnded = false;
}

function startLoop(){
  if(loopId) return;

  const tick = (ts) => {
    update(ts);
    render();
    loopId = requestAnimationFrame(tick);
  };

  loopId = requestAnimationFrame(tick);
}

function animateToTargets(duration, onDone){
  const start = performance.now();
  const from = cups.map(c => ({x:c.x, y:c.y}));
  const to = cups.map(c => ({x:c.targetX, y:c.targetY}));

  anim = {
    start:start,
    dur:duration,
    from:from,
    to:to,
    onDone:onDone
  };
}

function update(ts){
  if(!anim) return;

  const t = Math.min(1, (ts - anim.start) / anim.dur);
  const e = easeInOut(t);

  cups.forEach((c,i) => {
    c.x = lerp(anim.from[i].x, anim.to[i].x, e);
    c.y = lerp(anim.from[i].y, anim.to[i].y, e);
  });

  if(t >= 1){
    const done = anim.onDone;
    anim = null;
    if(done) done();
  }
}

function resetGame(){
  stopConfetti();
  document.querySelector(".game-card")?.classList.remove("winGlow");

  endOverlay.style.display = "none";
  msgEl.style.display = "flex";

  setGuideVideo(
    "../../images/videos/cups-guide.mp4",
    "راقب قطعة الجبن وتتبع مسارها",
    false
  );

  initCups();
  state = "showing";

  startLoop();

  setTimeout(() => startDrop(), SETTINGS.SHOW_CHEESE_TIME);
}

function startDrop(){
  state = "dropping";

  cups.forEach(c => c.targetY = SETTINGS.CUP_BASE_Y);

  animateToTargets(SETTINGS.DROP_TIME, () => startShuffle());
}

function startShuffle(){
  state = "shuffling";
  doShuffleSwap(0);
}

function doShuffleSwap(step){
  if(step >= SETTINGS.SHUFFLE_SWAPS){
    state = "choosing";
    allowPick = true;
    setMessage("اختار الكوب");
    return;
  }

  let a = randInt(0, SETTINGS.CUP_COUNT - 1);
  let b = randInt(0, SETTINGS.CUP_COUNT - 1);

  while(b === a){
    b = randInt(0, SETTINGS.CUP_COUNT - 1);
  }

  const ax = cups[a].targetX;
  const bx = cups[b].targetX;

  cups[a].targetX = bx;
  cups[b].targetX = ax;

  animateToTargets(SETTINGS.SHUFFLE_SWAP_TIME, () => {
    setTimeout(() => doShuffleSwap(step + 1), SETTINGS.SHUFFLE_GAP);
  });
}

function handleCanvasPick(clientX, clientY) {
  if(!allowPick || state !== "choosing") return;
  const rect = canvas.getBoundingClientRect();
  const mx = (clientX - rect.left) * (canvas.width / rect.width);
  const my = (clientY - rect.top)  * (canvas.height / rect.height);
  const hit = cups.find(c =>
    mx >= c.x && mx <= c.x + SETTINGS.CUP_W &&
    my >= c.y && my <= c.y + SETTINGS.CUP_H
  );
  if(!hit) return;
  pickedCupId = hit.id;
  revealResult();
}

canvas.addEventListener("touchend", (e) => {
  e.preventDefault();
  const t = e.changedTouches[0];
  handleCanvasPick(t.clientX, t.clientY);
}, { passive: false });

canvas.addEventListener("click", (e) => {
  handleCanvasPick(e.clientX, e.clientY);
});

function revealResult(){
  allowPick = false;
  state = "revealed";

  const won = pickedCupId === cheeseCupId;

  const chosen = cups.find(c => c.id === pickedCupId);
  chosen.targetY = SETTINGS.CUP_RAISE_Y;

  const cheeseCup = cups.find(c => c.id === cheeseCupId);

  if(!won){
    cheeseCup.targetY = SETTINGS.CUP_RAISE_Y;
  }

  animateToTargets(420, () => {
    if(won){
      document.querySelector(".game-card")?.classList.add("winGlow");
      startConfettiFromTop();

      setGuideVideo(
        "../../images/videos/cups-win.mp4",
        "أحسنت",
        true
      );

      setTimeout(() => showEnd(true), 850);
    }else{
      errorCupId = pickedCupId;
      errorStart = performance.now();

      setGuideVideo(
        "../../images/videos/cups-lose.mp4",
        "حاول مرة أخرى",
        true
      );

      setTimeout(() => showEnd(false), 850);
    }
  });
}

function showEnd(won){
  if(gameEnded) return;

  gameEnded = true;
  allowPick = false;
  msgEl.style.display = "none";

  if(won){
    fetch('../../auth/save_game_progress.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_key=cups&game_label=' + encodeURIComponent('لعبة الأكواب')
    }).catch(() => {});

    endEmoji.textContent = "🎉";
    endTitle.textContent = "أحسنت";
  }else{
    endEmoji.textContent = "";
    endTitle.textContent = "حاول مرة أخرى";
  }

  endOverlay.style.display = "flex";
}

function render(){
  ctx.clearRect(0,0,canvas.width,canvas.height);

  ctx.fillStyle = "rgba(255,255,255,0.08)";
  ctx.fillRect(0, SETTINGS.CUP_BASE_Y + SETTINGS.CUP_H - 10, canvas.width, 8);

  if(state === "showing" || state === "revealed"){
    const cheeseCup = cups.find(c => c.id === cheeseCupId);
    drawCheeseUnderCup(cheeseCup);
  }

  cups.forEach(drawCup);
}

function hideOverlay(){
  startOverlay.classList.add("hidden");
}

playBtn.addEventListener("click", () => {
  hideOverlay();
  resetGame();
});

restartBtn.addEventListener("click", () => {
  resetGame();
});

let confettiOn = false;
let confettiRAF = null;
let pieces = [];

function startConfettiFromTop(){
  confettiOn = true;
  confettiCanvas.style.display = "block";
  resizeConfetti();

  const W = window.innerWidth;
  const H = window.innerHeight;

  pieces = Array.from({ length: SETTINGS.CONFETTI_COUNT }, () => ({
    x:Math.random() * W,
    y:-Math.random() * (H * .8) - 20,
    w:6 + Math.random() * 10,
    h:3 + Math.random() * 8,
    vy:2 + Math.random() * 5,
    vx:-1.5 + Math.random() * 3,
    rot:Math.random() * Math.PI,
    vr:-.12 + Math.random() * .24,
    a:.75 + Math.random() * .25
  }));

  const start = performance.now();

  const tick = (ts) => {
    if(!confettiOn) return;

    const elapsed = ts - start;

    if(elapsed > SETTINGS.CONFETTI_DURATION){
      stopConfetti();
      return;
    }

    cctx.clearRect(0,0,window.innerWidth,window.innerHeight);

    pieces.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.rot += p.vr;

      if(p.x < -30) p.x = W + 30;
      if(p.x > W + 30) p.x = -30;

      cctx.save();
      cctx.translate(p.x,p.y);
      cctx.rotate(p.rot);

      const hue = (p.x + p.y + elapsed) % 360;
      cctx.fillStyle = `hsla(${hue}, 92%, 60%, ${p.a})`;
      cctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);

      cctx.restore();
    });

    confettiRAF = requestAnimationFrame(tick);
  };

  confettiRAF = requestAnimationFrame(tick);
}

function stopConfetti(){
  confettiOn = false;
  confettiCanvas.style.display = "none";
  cctx.clearRect(0,0,window.innerWidth,window.innerHeight);

  if(confettiRAF){
    cancelAnimationFrame(confettiRAF);
  }

  confettiRAF = null;
}

if(!CanvasRenderingContext2D.prototype.roundRect){
  CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r){
    const radius = typeof r === "number" ? {tl:r,tr:r,br:r,bl:r} : r;

    this.beginPath();
    this.moveTo(x + radius.tl, y);
    this.lineTo(x + w - radius.tr, y);
    this.quadraticCurveTo(x + w, y, x + w, y + radius.tr);
    this.lineTo(x + w, y + h - radius.br);
    this.quadraticCurveTo(x + w, y + h, x + w - radius.br, y + h);
    this.lineTo(x + radius.bl, y + h);
    this.quadraticCurveTo(x, y + h, x, y + h - radius.bl);
    this.lineTo(x, y + radius.tl);
    this.quadraticCurveTo(x, y, x + radius.tl, y);
    this.closePath();

    return this;
  };
}

cupImg.onerror = () => setMessage("cup.png غير موجودة");
cheeseImg.onerror = () => setMessage("cheese.png غير موجودة");
</script>
</body>
</html>
