<?php
/**
 * games/game5/index.php  —  لعبة الأعداد
 */
require_once __DIR__ . '/../../../config/session_child.php';
require_once __DIR__ . '/../../../config/db.php';
$child_display_name = $_SESSION['child_name'] ?? $_SESSION['username'] ?? 'الطفل';

$cfg_rounds  = 8;
$cfg_max_num = 10;
$gs_res = $conn->query("SELECT game_settings FROM games WHERE game_type='numbers' AND is_active=1 LIMIT 1");
if ($gs_res && $gs_row = $gs_res->fetch_assoc()) {
    $s = json_decode($gs_row['game_settings'] ?? '{}', true) ?? [];
    $cfg_rounds  = (int)($s['rounds']  ?? 8);
    $cfg_max_num = (int)($s['max_num'] ?? 10);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لعبة الأعداد</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{ box-sizing:border-box; margin:0; padding:0; }

body{
    font-family:"Cairo", sans-serif;
    background:linear-gradient(160deg,#EAF3DE 0%,#E1F5EE 50%,#E6F1FB 100%);
    color:#20314f;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

/* ====== PAGE ====== */
.game-page{
    width:min(1320px,96%);
    height:min(760px, calc(100vh - 28px));
    margin:0 auto;
    display:flex;
    flex-direction:column;
    gap:14px;
}

.game-layout{
    flex:1;
    display:grid;
    grid-template-columns:1fr 270px;
    gap:18px;
    min-height:0;
}

/* ====== GAME CARD ====== */
.game-card{
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(10px);
    border-radius:32px;
    padding:24px;
    box-shadow:0 22px 50px rgba(0,0,0,.08);
    display:flex;
    flex-direction:column;
    gap:14px;
    position:relative;
    overflow:hidden;
}

.top-bar{
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; flex-wrap:wrap;
}
.status-box{
    background:rgba(255,255,255,.7);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:999px;
    padding:8px 18px;
    font-size:17px;
    font-weight:800;
    color:#085041;
    backdrop-filter:blur(4px);
}
.restart-btn{
    background:rgba(255,255,255,.7);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:999px;
    padding:8px 20px;
    font-family:"Cairo",sans-serif;
    font-size:16px;
    font-weight:800;
    color:#085041;
    cursor:pointer;
    transition:.2s;
    backdrop-filter:blur(4px);
}
.restart-btn:hover{ background:rgba(255,255,255,.95); transform:translateY(-1px); }

/* ====== PROGRESS ====== */
.progress-bar{
    height:8px;
    background:rgba(255,255,255,.5);
    border-radius:999px;
    overflow:hidden;
}
.progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#1D9E75,#378ADD);
    transition:width .5s cubic-bezier(.4,0,.2,1);
}

/* ====== QUESTION AREA ====== */
.question-area{
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center; gap:20px; min-height:0;
}

.question-label{
    font-size:20px; font-weight:700; color:#0F6E56;
}

/* Objects grid */
.objects-grid{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    align-items:center;
    gap:10px;
    max-width:550px;
    padding:20px;
    background:rgba(255,255,255,.6);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:24px;
    min-height:100px;
    animation:fadeIn .4s ease;
}
.obj-item{
    font-size:46px;
    line-height:1;
    animation:popIn .3s ease backwards;
}

@keyframes fadeIn{
    from{ opacity:0; transform:scale(.95); }
    to  { opacity:1; transform:scale(1); }
}
@keyframes popIn{
    from{ opacity:0; transform:scale(.4); }
    to  { opacity:1; transform:scale(1); }
}

/* Number choices */
.number-choices{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    width:100%;
    max-width:560px;
}
.num-btn{
    height:78px;
    border-radius:18px;
    border:3px solid rgba(255,255,255,.8);
    cursor:pointer;
    font-size:32px;
    font-weight:900;
    font-family:"Cairo",sans-serif;
    color:#fff;
    text-shadow:0 2px 6px rgba(0,0,0,.15);
    transition:.25s ease;
    outline:none;
}
.num-btn:nth-child(1){ background:#5DCAA5; border-color:#9FE1CB; }
.num-btn:nth-child(2){ background:#85B7EB; border-color:#B5D4F4; }
.num-btn:nth-child(3){ background:#EF9F27; border-color:#FAC775; }
.num-btn:nth-child(4){ background:#AFA9EC; border-color:#CECBF6; }

.num-btn:hover:not(:disabled){
    transform:translateY(-6px) scale(1.06);
    box-shadow:0 14px 28px rgba(0,0,0,.13);
}
.num-btn:disabled{ cursor:default; }

.num-btn.correct{
    background:#639922 !important;
    border-color:#C0DD97 !important;
    color:#fff !important;
    transform:scale(1.1) !important;
    box-shadow:0 0 0 5px rgba(99,153,34,.3), 0 14px 28px rgba(0,0,0,.12) !important;
    animation:correctPop .45s ease;
}
.num-btn.wrong{
    background:#E24B4A !important;
    border-color:#F7C1C1 !important;
    color:#fff !important;
    box-shadow:0 0 0 5px rgba(226,75,74,.3) !important;
    animation:shake .4s ease;
}
.num-btn.neutral{
    opacity:.4;
    transform:none !important;
    box-shadow:none !important;
}

@keyframes correctPop{
    0%{ transform:scale(1.1); }
    50%{ transform:scale(1.2); }
    100%{ transform:scale(1.1); }
}
@keyframes shake{
    0%,100%{ transform:translateX(0); }
    25%{ transform:translateX(-9px); }
    75%{ transform:translateX(9px); }
}

/* ====== WIN OVERLAY ====== */
.win-overlay{
    position:absolute; inset:0;
    background:rgba(234,243,222,.88);
    backdrop-filter:blur(6px);
    display:none; align-items:center; justify-content:center;
    z-index:30; border-radius:32px;
}
.win-overlay.show{ display:flex; }
.win-box{
    width:min(92%,420px);
    background:rgba(255,255,255,.9);
    border-radius:28px;
    box-shadow:0 16px 30px rgba(0,0,0,.1);
    padding:36px 28px; text-align:center;
    position:relative; z-index:3;
    animation:fadeIn .5s ease;
}
.win-box h3{ font-size:40px; color:#085041; margin-bottom:8px; font-weight:900; }
.win-box p { font-size:20px; color:#0F6E56; margin-bottom:20px; font-weight:700; }

/* ====== GUIDE PANEL ====== */
.guide-panel{
    background:rgba(255,255,255,.75);
    backdrop-filter:blur(6px);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:28px;
    box-shadow:0 10px 24px rgba(0,0,0,.07);
    padding:16px; display:flex; flex-direction:column; min-height:0;
}
.guide-title{
    text-align:center; font-size:18px; font-weight:900;
    color:#085041; margin-bottom:12px; line-height:1.5;
}
.guide-video-wrap{
    position:relative; flex:1; min-height:200px;
    border-radius:18px; overflow:hidden; background:#c8e6c9;
}
.guide-video{ width:100%; height:100%; object-fit:cover; display:block; }
.video-overlay-btn{
    position:absolute; inset:0; border:none;
    background:rgba(15,110,86,.1);
    display:flex; align-items:center; justify-content:center; cursor:pointer;
}
.video-overlay-btn span{
    width:68px; height:68px; border-radius:50%;
    background:rgba(255,255,255,.92); color:#085041;
    font-size:28px; display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
}

/* CONFETTI */
#confettiFull{
    position:fixed;inset:0;
    width:100vw;height:100vh;
    display:none;pointer-events:none;z-index:9999;
}

/* ====== RESPONSIVE ====== */
@media(max-width:900px){
    body{ overflow:auto; }
    .game-layout{ grid-template-columns:1fr; }
    .number-choices{ grid-template-columns:repeat(2,1fr); }
}
</style>
</head>
<body>

<main class="game-page">
  <div class="game-layout">

    <!-- GAME -->
    <section class="game-card">
      <div class="top-bar">
        <div class="status-box">السؤال: <span id="qNum">1</span> / <?php echo $cfg_rounds; ?></div>
        <div class="status-box">✅ <span id="score">0</span> صحيح</div>
        <button class="restart-btn" onclick="restartGame()">🔄 إعادة اللعبة</button>
      </div>

      <div class="progress-bar">
        <div class="progress-fill" id="progress" style="width:0%"></div>
      </div>

      <div class="question-area">
        <p class="question-label">كم عدد النجوم الموجودة؟ 👇</p>
        <div class="objects-grid" id="objectsGrid"></div>
        <div class="number-choices" id="numChoices"></div>
      </div>

      <!-- WIN OVERLAY -->
      <div class="win-overlay" id="winOverlay">
        <div class="win-box">
          <h3>أحسنت! 🌟</h3>
          <p id="finalScore">أجبت بشكل صحيح على ...</p>
          <button class="restart-btn" onclick="restartGame()">العب مجدداً</button>
        </div>
      </div>
    </section>

    <!-- GUIDE -->
    <aside class="guide-panel">
      <div class="guide-title" id="guideTitle">
        عدّ النجوم ثم انقر على الرقم الصحيح 🔢
      </div>
      <div class="guide-video-wrap">
        <video id="guideVideo" class="guide-video" playsinline preload="metadata">
          <source src="../../../images/videos/numbers-guide.mp4" type="video/mp4">
        </video>
        <button class="video-overlay-btn" id="guideOverlay" type="button" onclick="toggleGuide()">
          <span>▶</span>
        </button>
      </div>
    </aside>

  </div>
</main>

<canvas id="confettiFull"></canvas>

<script>
const TOTAL_ROUNDS = <?php echo $cfg_rounds; ?>;
const EMOJI        = '⭐';
const MIN_NUM      = 1;
const MAX_NUM      = <?php echo $cfg_max_num; ?>;

let currentRound = 0;
let score        = 0;
let locked       = false;
let rounds       = [];

function randInt(min, max){
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
function shuffle(arr){
    return [...arr].sort(() => Math.random() - 0.5);
}
function buildChoices(correct){
    const pool = new Set([correct]);
    while(pool.size < 4){
        pool.add(randInt(MIN_NUM, MAX_NUM));
    }
    return shuffle([...pool]);
}
function buildRounds(){
    const arr = []; let prev = -1;
    for(let i = 0; i < TOTAL_ROUNDS; i++){
        let n;
        do{ n = randInt(MIN_NUM, MAX_NUM); } while(n === prev);
        arr.push({ correct: n });
        prev = n;
    }
    return arr;
}

function restartGame(){
    currentRound = 0; score = 0; locked = false;
    rounds = buildRounds();
    document.getElementById('score').textContent = 0;
    document.getElementById('winOverlay').classList.remove('show');
    document.getElementById('progress').style.width = '0%';
    stopConfetti();

    document.getElementById('guideTitle').textContent = 'عدّ النجوم ثم انقر على الرقم الصحيح 🔢';
    const gv = document.getElementById('guideVideo');
    gv.src = '../../../images/videos/numbers-guide.mp4';
    gv.load();
    document.getElementById('guideOverlay').style.display = 'flex';

    renderRound();
}

function renderRound(){
    if(currentRound >= TOTAL_ROUNDS){ endGame(); return; }
    locked = false;
    const { correct } = rounds[currentRound];
    document.getElementById('qNum').textContent = currentRound + 1;
    document.getElementById('progress').style.width = (currentRound / TOTAL_ROUNDS * 100) + '%';

    const grid = document.getElementById('objectsGrid');
    grid.innerHTML = '';
    for(let i = 0; i < correct; i++){
        const span = document.createElement('span');
        span.className = 'obj-item';
        span.textContent = EMOJI;
        span.style.animationDelay = (i * 0.05) + 's';
        grid.appendChild(span);
    }

    const choices = buildChoices(correct);
    const choicesEl = document.getElementById('numChoices');
    choicesEl.innerHTML = '';
    choices.forEach(num => {
        const btn = document.createElement('button');
        btn.className = 'num-btn';
        btn.textContent = num;
        btn.addEventListener('click', () => pick(btn, num, correct));
        choicesEl.appendChild(btn);
    });
}

function pick(btn, chosen, correct){
    if(locked) return;
    locked = true;
    const all = document.querySelectorAll('.num-btn');

    if(chosen === correct){
        btn.classList.add('correct');
        score++;
        document.getElementById('score').textContent = score;
        all.forEach(b => { if(b !== btn) b.classList.add('neutral'); });
    } else {
        btn.classList.add('wrong');
        all.forEach(b => {
            if(b === btn) return;
            if(+b.textContent === correct) b.classList.add('correct');
            else b.classList.add('neutral');
        });
    }

    all.forEach(b => b.disabled = true);
    setTimeout(() => { currentRound++; renderRound(); }, 1100);
}

function endGame(){
    document.getElementById('finalScore').textContent =
        'أجبت بشكل صحيح على ' + score + ' من ' + TOTAL_ROUNDS + ' أسئلة 🌟';
    document.getElementById('winOverlay').classList.add('show');
    document.getElementById('progress').style.width = '100%';
    startConfetti();

    fetch('../../../auth/save_game_progress.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'game_key=numbers&game_label=' + encodeURIComponent('لعبة الأعداد')
    }).catch(()=>{});

    document.getElementById('guideTitle').textContent = 'أحسنت! 🎉';
    const gv = document.getElementById('guideVideo');
    gv.src = '../../../images/videos/numbers-win.mp4';
    gv.load();
    gv.play().catch(()=>{});
    document.getElementById('guideOverlay').style.display = 'none';
}

function toggleGuide(){
    const v  = document.getElementById('guideVideo');
    const ov = document.getElementById('guideOverlay');
    if(v.paused){ v.play(); ov.style.display = 'none'; }
    else        { v.pause(); ov.style.display = 'flex'; }
}
document.getElementById('guideVideo').addEventListener('ended', () => {
    document.getElementById('guideOverlay').style.display = 'flex';
});

/* CONFETTI */
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

restartGame();
</script>
</body>
</html>