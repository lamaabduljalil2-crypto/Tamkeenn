<?php
/**
 * games/game4/index.php  —  لعبة الألوان
 * ضعه في:  /games/game4/index.php
 */
require_once __DIR__ . '/../../config/session_child.php';
require_once '../../config/db.php';
$child_display_name = $_SESSION['child_name'] ?? $_SESSION['username'] ?? 'الطفل';

/* ── قراءة إعدادات اللعبة من قاعدة البيانات ── */
$cfg_rounds = 8; // قيمة افتراضية
$gs_res = $conn->query("SELECT game_settings FROM games WHERE game_type='colors' AND is_active=1 LIMIT 1");
if ($gs_res && $gs_row = $gs_res->fetch_assoc()) {
    $s = json_decode($gs_row['game_settings'] ?? '{}', true) ?? [];
    $cfg_rounds = (int)($s['rounds'] ?? 8);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>لعبة الألوان</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{ box-sizing:border-box; margin:0; padding:0; }

body{
    font-family:"Cairo", sans-serif;
    background:linear-gradient(180deg,#e8f5ff 0%,#d6eaff 52%,#cce0ff 100%);
    color:#20314f;
    height:100vh;
    overflow:hidden;
}
a{ text-decoration:none; color:inherit; }

/* ====== HEADER ====== */
.children-dash{
    width:100%; height:110px;
    background:linear-gradient(135deg,#fdf7ff,#f4e7ff);
    box-shadow:0 8px 24px rgba(0,0,0,.08);
    position:sticky; top:0; z-index:1000;
    border-bottom:3px solid rgba(206,147,216,.35);
}
.children-dash-inner{
    max-width:1450px; margin:auto; height:100%;
    display:flex; align-items:center; justify-content:space-between; padding:0 28px;
}
.logo-box{
    display:flex; align-items:center; justify-content:center; transition:.25s ease;
}
.logo-box:hover{ transform:scale(1.05); }
.logo-box img{ width:82px; height:82px; object-fit:contain; filter:drop-shadow(0 6px 12px rgba(0,0,0,.12)); }

.dash-nav{ display:flex; align-items:center; gap:24px; }
.circle-icon{
    width:82px; height:82px; border-radius:50%; overflow:visible;
    background:rgba(255,255,255,.88);
    display:flex; align-items:center; justify-content:center;
    position:relative;
    box-shadow:0 6px 16px rgba(0,0,0,.08),inset 0 0 0 2px rgba(255,255,255,.8);
    transition:.25s ease;
}
.circle-icon:hover{ transform:translateY(-5px) scale(1.07); }
.circle-icon video{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.dash-hover-label{
    position:absolute; bottom:-34px; left:50%; transform:translateX(-50%);
    background:linear-gradient(135deg,#ce93d8,#ba68c8); color:white;
    padding:6px 16px; border-radius:999px; font-size:14px; font-weight:800;
    white-space:nowrap; opacity:0; visibility:hidden; transition:.25s ease;
    box-shadow:0 6px 14px rgba(0,0,0,.12); pointer-events:none;
}
.circle-icon:hover .dash-hover-label{ opacity:1; visibility:visible; bottom:-42px; }

.profile-wrap{ position:relative; }
.profile-btn{
    border:none; background:rgba(255,255,255,.88); border-radius:999px;
    padding:8px 12px 8px 18px; display:flex; align-items:center; gap:12px;
    cursor:pointer; box-shadow:0 6px 16px rgba(0,0,0,.08); font-family:"Cairo",sans-serif;
}
.profile-hello{ font-size:17px; font-weight:800; color:#7b5237; }
.profile-video-box{ width:54px; height:54px; border-radius:50%; overflow:hidden; }
.profile-video-box video{ width:100%; height:100%; object-fit:cover; }
.profile-menu{
    position:absolute; top:76px; left:0; min-width:220px; background:white;
    border-radius:18px; overflow:hidden; display:none; flex-direction:column;
    box-shadow:0 12px 28px rgba(0,0,0,.14); z-index:2000;
}
.profile-wrap.active .profile-menu{ display:flex; }
.profile-menu a{ padding:14px 18px; color:#7b5237; font-size:16px; font-weight:800; }
.profile-menu a:hover{ background:#f8efe6; }

/* ====== PAGE ====== */
.game-page{
    width:min(1320px,96%);
    margin:20px auto 0;
    height:calc(100vh - 130px);
    display:flex;
    flex-direction:column;
    gap:14px;
}
.page-title{ text-align:center; }
.page-title h2{ font-size:30px; color:#1a3a6b; font-weight:900; }

.game-layout{
    flex:1;
    display:grid;
    grid-template-columns:1fr 270px;
    gap:18px;
    min-height:0;
}

/* ====== GAME CARD ====== */
.game-card{
    background:rgba(255,255,255,.85);
    backdrop-filter:blur(10px);
    border-radius:32px;
    padding:24px;
    box-shadow:0 22px 50px rgba(30,80,180,.10);
    display:flex;
    flex-direction:column;
    gap:16px;
    position:relative;
    overflow:hidden;
}

.top-bar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
}
.status-box{
    background:#eef2ff;
    border-radius:14px;
    padding:10px 18px;
    font-size:19px;
    font-weight:800;
    color:#3730a3;
}
.restart-btn{
    background:#ef4444; color:#fff; border:none; border-radius:12px;
    padding:10px 22px; font-family:"Cairo",sans-serif; cursor:pointer;
    font-size:18px; font-weight:800; transition:.3s;
}
.restart-btn:hover{ transform:translateY(-2px); filter:brightness(1.06); }

/* ====== QUESTION AREA ====== */
.question-area{
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:28px;
    min-height:0;
}

.question-label{
    font-size:22px;
    font-weight:700;
    color:#4b5563;
}

.color-name-display{
    font-size:64px;
    font-weight:900;
    letter-spacing:4px;
    padding:18px 48px;
    border-radius:24px;
    background:rgba(255,255,255,.7);
    box-shadow:0 8px 24px rgba(0,0,0,.10);
    border:4px solid currentColor;
    animation:fadeIn .4s ease;
}

@keyframes fadeIn{
    from{ opacity:0; transform:scale(.9); }
    to  { opacity:1; transform:scale(1); }
}

/* تأثير تلوين الكلمة عند الإجابة الصحيحة */
@keyframes colorPaint{
    0%  { transform:scale(1);    letter-spacing:4px; }
    30% { transform:scale(1.18); letter-spacing:8px; filter:brightness(1.25); }
    60% { transform:scale(0.96); }
    100%{ transform:scale(1);    letter-spacing:4px; filter:brightness(1); }
}
.color-name-display.painted{
    animation: colorPaint .65s cubic-bezier(.36,.07,.19,.97) forwards;
    transition: color .25s ease, border-color .25s ease, text-shadow .25s ease;
}

/* ====== CHOICES ====== */
.choices-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    width:100%;
    max-width:720px;
}

.color-btn{
    height:120px;
    border-radius:22px;
    border:4px solid rgba(255,255,255,.6);
    cursor:pointer;
    transition:.25s ease;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
    position:relative;
    overflow:hidden;
    outline:none;
}
.color-btn:hover:not(:disabled){
    transform:translateY(-6px) scale(1.04);
    box-shadow:0 14px 30px rgba(0,0,0,.2);
}
.color-btn:disabled{ cursor:default; }

.color-btn.correct{
    border-color:#22c55e !important;
    box-shadow:0 0 0 5px rgba(34,197,94,.45), 0 14px 30px rgba(0,0,0,.2) !important;
    animation:correctPop .45s ease;
}
.color-btn.wrong{
    border-color:#ef4444 !important;
    box-shadow:0 0 0 5px rgba(239,68,68,.4) !important;
    animation:shake .4s ease;
}

@keyframes correctPop{
    0%{transform:scale(1);}
    50%{transform:scale(1.10);}
    100%{transform:scale(1);}
}
@keyframes shake{
    0%,100%{transform:translateX(0);}
    20%{transform:translateX(-8px);}
    40%{transform:translateX(8px);}
    60%{transform:translateX(-8px);}
    80%{transform:translateX(8px);}
}

/* color-btn-label مخفي تماماً — لا نريد إظهار اسم اللون */
.color-btn-label{ display:none; }

/* ====== WIN OVERLAY ====== */
.win-overlay{
    position:absolute; inset:0;
    background:rgba(240,248,255,.88);
    backdrop-filter:blur(6px);
    display:none; align-items:center; justify-content:center;
    z-index:30; border-radius:32px;
}
.win-overlay.show{ display:flex; }

.win-box{
    width:min(92%,420px);
    background:linear-gradient(180deg,#fffaf4,#e0f2fe);
    border-radius:28px;
    box-shadow:0 16px 30px rgba(0,0,0,.12);
    padding:32px 28px;
    text-align:center;
    position:relative; z-index:3;
}
.win-box h3{ font-size:42px; color:#1e40af; margin-bottom:8px; font-weight:900; }
.win-box p { font-size:22px; color:#1d4ed8; margin-bottom:20px; font-weight:700; }

.glow-ring{
    position:absolute; width:300px; height:300px; border-radius:50%;
    background:radial-gradient(circle, rgba(147,197,253,.7) 0%, transparent 68%);
    animation:glowPulse 1.8s ease-in-out infinite;
    z-index:1;
}
@keyframes glowPulse{
    0%,100%{transform:scale(.95);}
    50%{transform:scale(1.08);}
}

.confetti-piece{
    position:absolute; width:10px; height:16px;
    border-radius:3px; animation:fall linear forwards;
    z-index:2;
}
@keyframes fall{
    to{ transform:translateY(540px) rotate(660deg); opacity:.8; }
}

/* ====== GUIDE PANEL ====== */
.guide-panel{
    background:linear-gradient(180deg,#fff8f1,#f9ecdf);
    border-radius:28px;
    box-shadow:0 10px 24px rgba(133,97,69,.12);
    padding:16px;
    display:flex; flex-direction:column; min-height:100%;
}
.guide-title{
    text-align:center; font-size:20px; font-weight:900;
    color:#8a5737; margin-bottom:12px; line-height:1.5;
}
.guide-video-wrap{
    position:relative; flex:1; min-height:200px;
    border-radius:24px; overflow:hidden; background:#d1bfae;
}
.guide-video{ width:100%; height:100%; object-fit:cover; display:block; }
.video-overlay-btn{
    position:absolute; inset:0; border:none;
    background:rgba(91,67,48,.12);
    display:flex; align-items:center; justify-content:center; cursor:pointer;
}
.video-overlay-btn span{
    width:72px; height:72px; border-radius:50%;
    background:rgba(255,255,255,.92); color:#8a5737;
    font-size:32px; display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
}

/* ====== FEEDBACK ICON ====== */
.feedback-icon{
    font-size:72px;
    animation:popIn .35s ease;
    display:none;
}
@keyframes popIn{
    from{transform:scale(.5); opacity:0;}
    to  {transform:scale(1);  opacity:1;}
}

/* ====== RESPONSIVE ====== */
@media(max-width:900px){
    body{ overflow:auto; }
    .game-layout{ grid-template-columns:1fr; }
    .choices-grid{ grid-template-columns:repeat(2,1fr); }
    .color-btn{ height:90px; }
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
  .guide-panel{min-height:260px!important; order:-1!important;}
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="children-dash">
  <div class="children-dash-inner">

    <div class="dash-start">
      <a href="../../index.php" class="logo-box" tabindex="0">
        <img src="../../logo.png" alt="logo">
      </a>
    </div>

    <nav class="dash-nav">
      <a href="../../auth/children.php" class="circle-icon" tabindex="0" aria-label="الرئيسية">
        <video autoplay muted loop playsinline>
          <source src="../../assets/icons/children.mp4" type="video/mp4">
        </video>
        <span class="dash-hover-label">الرئيسية</span>
      </a>
    </nav>

    <div class="profile-wrap" id="profileWrap">
      <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
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

<!-- MAIN -->
<main class="game-page">
  <div class="page-title">
    <h2>🌈 لعبة الألوان</h2>
  </div>

  <div class="game-layout">

    <!-- GAME -->
    <section class="game-card">
      <div class="top-bar">
        <div class="status-box">السؤال: <span id="qNum">1</span> / <?php echo $cfg_rounds; ?></div>
        <div class="status-box">✅ <span id="score">0</span> صحيح</div>
        <button class="restart-btn" onclick="restartGame()">إعادة اللعبة</button>
      </div>

      <div class="question-area">
        <p class="question-label">ما هذا اللون؟ انقر عليه 👇</p>
        <div class="color-name-display" id="colorName" style="color:#333;border-color:#ccc;">
            ...
        </div>
        <div class="choices-grid" id="choicesGrid"></div>
      </div>

      <!-- WIN OVERLAY -->
      <div class="win-overlay" id="winOverlay">
        <div class="glow-ring"></div>
        <div class="win-box">
          <h3>أحسنت! 🎉</h3>
          <p id="finalScore">أجبت بشكل صحيح على ...</p>
          <button class="restart-btn" onclick="restartGame()">العب مجدداً</button>
        </div>
      </div>
    </section>

    <!-- GUIDE -->
    <aside class="guide-panel">
      <div class="guide-title" id="guideTitle">
        اقرأ اسم اللون ثم انقر على اللون الصحيح 🌈
      </div>
      <div class="guide-video-wrap">
        <video id="guideVideo" class="guide-video" playsinline preload="metadata">
          <source src="../../images/videos/colors-guide.mp4" type="video/mp4">
        </video>
        <button class="video-overlay-btn" id="guideOverlay" type="button" onclick="toggleGuide()">
          <span>▶</span>
        </button>
      </div>
    </aside>

  </div>
</main>

<!-- CONFETTI CANVAS -->
<canvas id="confettiFull" style="position:fixed;inset:0;width:100vw;height:100vh;display:none;pointer-events:none;z-index:9999;"></canvas>

<script>
/* ============================================================
   DATA
   ============================================================ */
const ALL_COLORS = [
    { ar:'أحمر',    hex:'#e74c3c' },
    { ar:'أزرق',    hex:'#3498db' },
    { ar:'أخضر',    hex:'#2ecc71' },
    { ar:'أصفر',    hex:'#f1c40f' },
    { ar:'برتقالي', hex:'#e67e22' },
    { ar:'بنفسجي',  hex:'#9b59b6' },
    { ar:'وردي',    hex:'#e91e63' },
    { ar:'بني',     hex:'#795548' },
    { ar:'رمادي',   hex:'#95a5a6' },
    { ar:'أبيض',    hex:'#d0d0d0' },
];

const TOTAL_ROUNDS = <?php echo $cfg_rounds; ?>;

let currentRound   = 0;
let score          = 0;
let locked         = false;
let roundColors    = [];   // shuffled each game

/* ============================================================
   INIT
   ============================================================ */
function shuffle(arr){
    return [...arr].sort(() => Math.random() - 0.5);
}

function restartGame(){
    currentRound = 0;
    score        = 0;
    locked       = false;
    roundColors  = shuffle(ALL_COLORS).slice(0, TOTAL_ROUNDS);

    document.getElementById('score').textContent   = 0;
    document.getElementById('winOverlay').classList.remove('show');
    removeConfetti();
    stopConfetti();

    document.getElementById('guideTitle').textContent =
        'اقرأ اسم اللون ثم انقر على اللون الصحيح 🌈';

    const gv = document.getElementById('guideVideo');
    gv.src = '../../images/videos/colors-guide.mp4';
    gv.load();
    document.getElementById('guideOverlay').style.display = 'flex';

    renderRound();
}

/* ============================================================
   RENDER
   ============================================================ */
function renderRound(){
    if(currentRound >= TOTAL_ROUNDS){
        endGame();
        return;
    }

    locked = false;
    const correct = roundColors[currentRound];
    document.getElementById('qNum').textContent = currentRound + 1;

    /* show color name — محايد حتى يختار الطفل الجواب */
    const nameEl = document.getElementById('colorName');
    nameEl.textContent       = correct.ar;
    nameEl.style.color       = '#2d3748';
    nameEl.style.borderColor = '#cbd5e0';
    nameEl.style.textShadow  = 'none';
    nameEl.classList.remove('painted');

    /* pick 3 wrong colors */
    const others = shuffle(ALL_COLORS.filter(c => c.ar !== correct.ar)).slice(0, 3);
    const choices = shuffle([correct, ...others]);

    /* render buttons */
    const grid = document.getElementById('choicesGrid');
    grid.innerHTML = '';

    choices.forEach(c => {
        const btn = document.createElement('button');
        btn.className = 'color-btn';
        btn.style.background = c.hex;
        btn.setAttribute('aria-label', c.ar);

        const lbl = document.createElement('span');
        lbl.className = 'color-btn-label';
        lbl.textContent = c.ar;
        btn.appendChild(lbl);

        btn.addEventListener('click', () => pick(btn, c, correct));
        grid.appendChild(btn);
    });
}

/* ============================================================
   PICK
   ============================================================ */
function pick(btn, chosen, correct){
    if(locked) return;
    locked = true;

    const isCorrect = chosen.ar === correct.ar;

    if(isCorrect){
        btn.classList.add('correct');
        score++;
        document.getElementById('score').textContent = score;

        /* تلوين الكلمة بلون الإجابة الصحيحة بطريقة جميلة */
        const nameEl = document.getElementById('colorName');
        nameEl.classList.remove('painted');
        void nameEl.offsetWidth; /* reset animation */
        nameEl.style.color       = correct.hex;
        nameEl.style.borderColor = correct.hex;
        nameEl.style.textShadow  = `0 0 24px ${correct.hex}99, 0 0 8px ${correct.hex}66`;
        nameEl.classList.add('painted');
    } else {
        btn.classList.add('wrong');
        /* highlight correct */
        document.querySelectorAll('.color-btn').forEach(b => {
            if(b.style.background === correct.hex ||
               b.style.backgroundColor === correct.hex){
                b.classList.add('correct');
            }
        });
    }

    setTimeout(() => {
        currentRound++;
        renderRound();
    }, 900);
}

/* ============================================================
   END
   ============================================================ */
function endGame(){
    document.getElementById('finalScore').textContent =
        'أجبت بشكل صحيح على ' + score + ' من ' + TOTAL_ROUNDS + ' أسئلة 🌟';

    document.getElementById('winOverlay').classList.add('show');
    startConfetti();

    /* ── حفظ تقدم اللعبة ── */
    fetch('../../auth/save_game_progress.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'game_key=colors&game_label=' + encodeURIComponent('لعبة الألوان')
    }).catch(()=>{});

    document.getElementById('guideTitle').textContent = 'أحسنت! 🎉';
    const gv = document.getElementById('guideVideo');
    gv.src = '../../images/videos/colors-win.mp4';
    gv.load();
    gv.play().catch(()=>{});
    document.getElementById('guideOverlay').style.display = 'none';
}

/* ============================================================
   GUIDE VIDEO
   ============================================================ */
function toggleGuide(){
    const v  = document.getElementById('guideVideo');
    const ov = document.getElementById('guideOverlay');
    if(v.paused){ v.play(); ov.style.display = 'none'; }
    else        { v.pause(); ov.style.display = 'flex'; }
}
document.getElementById('guideVideo').addEventListener('ended', () => {
    document.getElementById('guideOverlay').style.display = 'flex';
});

/* ============================================================
   CONFETTI
   ============================================================ */
const confettiCanvas = document.getElementById('confettiFull');
const cctx = confettiCanvas.getContext('2d');
let confettiOn = false, confettiRAF = null, pieces = [];

function resizeConfetti(){
    confettiCanvas.width  = window.innerWidth  * devicePixelRatio;
    confettiCanvas.height = window.innerHeight * devicePixelRatio;
    cctx.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);
}
window.addEventListener('resize', resizeConfetti);
resizeConfetti();

function startConfetti(){
    confettiOn = true;
    confettiCanvas.style.display = 'block';
    resizeConfetti();
    const W = window.innerWidth, H = window.innerHeight;
    pieces = Array.from({length:200}, () => ({
        x:Math.random()*W, y:-Math.random()*(H*.7)-20,
        w:6+Math.random()*10, h:3+Math.random()*8,
        vy:2+Math.random()*5, vx:-1.5+Math.random()*3,
        rot:Math.random()*Math.PI, vr:-.12+Math.random()*.24,
        a:.8+Math.random()*.2
    }));
    const start = performance.now();
    const tick = ts => {
        if(!confettiOn) return;
        if(ts - start > 4000){ stopConfetti(); return; }
        cctx.clearRect(0, 0, W, H);
        pieces.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.rot += p.vr;
            if(p.x < -30) p.x = W+30;
            if(p.x > W+30) p.x = -30;
            cctx.save();
            cctx.translate(p.x, p.y);
            cctx.rotate(p.rot);
            cctx.fillStyle = `hsla(${(p.x+p.y+ts)%360},90%,58%,${p.a})`;
            cctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
            cctx.restore();
        });
        confettiRAF = requestAnimationFrame(tick);
    };
    confettiRAF = requestAnimationFrame(tick);
}

function stopConfetti(){
    confettiOn = false;
    confettiCanvas.style.display = 'none';
    cctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    if(confettiRAF) cancelAnimationFrame(confettiRAF);
    confettiRAF = null;
}
function removeConfetti(){
    document.querySelectorAll('.confetti-piece').forEach(e => e.remove());
}

/* ============================================================
   PROFILE MENU
   ============================================================ */
document.getElementById('profileBtn').addEventListener('click', function(e){
    e.stopPropagation();
    document.getElementById('profileWrap').classList.toggle('active');
});
document.addEventListener('click', () => {
    document.getElementById('profileWrap').classList.remove('active');
});

/* ============================================================
   START
   ============================================================ */
restartGame();
</script>
</body>
</html>
