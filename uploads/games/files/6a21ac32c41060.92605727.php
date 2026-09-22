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
    background:linear-gradient(180deg,#fff8bf 0%,#fde779 55%,#facc35 100%);
    color:#20314f;
    min-height:100vh;
    overflow:hidden;
}

.game-page{
    width:min(1320px,96%);
    height:min(760px, calc(100vh - 28px));
    margin:14px auto;
    display:grid;
    grid-template-columns:1fr 270px;
    gap:18px;
    direction:ltr;
}

/* ===== MAIN GAME CARD ===== */
.main-card{
    direction:rtl;
    background:rgba(255,255,255,.88);
    border-radius:32px;
    padding:28px 34px;
    box-shadow:0 22px 50px rgba(180,120,0,.14);
    min-height:0;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    gap:24px;
    position:relative;
    overflow:hidden;
}

/* ===== TOP BAR ===== */
.topbar{
    position:absolute;
    top:24px;
    right:30px;
    left:30px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    z-index:5;
    direction:rtl;
}

.topbar > div{
    display:flex !important;
    flex-direction:row;
    align-items:center;
    gap:14px !important;
}

.stat-pill{
    background:#fef3c7;
    border-radius:14px;
    padding:10px 18px;
    font-size:19px;
    font-weight:900;
    color:#92400e;
    min-width:135px;
    text-align:center;
    box-shadow:none;
}

.stat-pill strong{
    font-size:21px;
    font-weight:900;
    color:#92400e;
}

.restart-btn{
    background:#ef4444;
    color:#fff;
    border:none;
    border-radius:12px;
    padding:10px 22px;
    font-family:"Cairo", sans-serif;
    font-size:18px;
    font-weight:900;
    cursor:pointer;
    transition:.25s ease;
}

.restart-btn:hover{
    transform:translateY(-2px);
    filter:brightness(1.04);
}

/* ===== PROGRESS ===== */
.progress-bar{
    position:absolute;
    top:86px;
    right:30px;
    left:30px;
    height:8px;
    background:rgba(255,255,255,.55);
    border-radius:999px;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#fbbf24,#f59e0b);
    transition:width .45s ease;
}

/* ===== QUESTION AREA ===== */
.question-label{
    margin-top:88px;
    font-size:25px;
    font-weight:900;
    color:#4b5563;
    text-align:center;
}

.objects-stage{
    width:min(560px,90%);
    min-height:132px;
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    align-items:center;
    gap:12px;
    padding:22px;
    background:rgba(255,255,255,.72);
    border-radius:24px;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
}

.obj{
    font-size:50px;
    line-height:1;
    display:inline-block;
    animation:pop .24s ease backwards;
    filter:drop-shadow(0 4px 4px rgba(0,0,0,.18));
}

@keyframes pop{
    from{ opacity:0; transform:scale(.2); }
    to{ opacity:1; transform:scale(1); }
}

.divider{ display:none; }

/* ===== CHOICES ===== */
.choices{
    width:min(560px,90%);
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
}

.choice-btn{
    height:76px;
    border:none;
    border-radius:18px;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#fff;
    font-family:"Cairo", sans-serif;
    font-size:34px;
    font-weight:900;
    cursor:pointer;
    transition:.25s ease;
    outline:none;
    text-shadow:0 2px 6px rgba(0,0,0,.22);
    box-shadow:0 8px 20px rgba(245,158,11,.34);
    border:4px solid rgba(255,255,255,.62);
}

.choice-btn:hover:not(:disabled){
    transform:translateY(-5px) scale(1.04);
    box-shadow:0 14px 28px rgba(245,158,11,.4);
}

.choice-btn:disabled{ cursor:default; }

.choice-btn.correct{
    background:linear-gradient(135deg,#22c55e,#16a34a) !important;
    border-color:#bbf7d0 !important;
    transform:scale(1.08) !important;
    box-shadow:0 0 0 5px rgba(34,197,94,.35),0 12px 24px rgba(0,0,0,.13) !important;
    animation:correctPop .45s ease;
}

.choice-btn.wrong{
    background:linear-gradient(135deg,#ef4444,#dc2626) !important;
    border-color:#fecaca !important;
    box-shadow:0 0 0 5px rgba(239,68,68,.35) !important;
    animation:shake .38s ease;
}

.choice-btn.neutral{
    opacity:.35;
    transform:none !important;
    box-shadow:none !important;
}

@keyframes correctPop{
    0%{transform:scale(1.08);}
    50%{transform:scale(1.18);}
    100%{transform:scale(1.08);}
}

@keyframes shake{
    0%,100%{ transform:translateX(0); }
    25%{ transform:translateX(-9px); }
    75%{ transform:translateX(9px); }
}

/* ===== SIDE GUIDE PANEL ===== */
.guide-panel{
    direction:rtl;
    background:linear-gradient(180deg,#fff8f1,#f9ecdf);
    border-radius:28px;
    box-shadow:0 10px 24px rgba(133,97,69,.12);
    padding:16px;
    display:flex;
    flex-direction:column;
    min-height:0;
}

.guide-title{
    text-align:center;
    font-size:22px;
    font-weight:900;
    color:#8a5737;
    margin-bottom:14px;
    line-height:1.5;
}

.guide-video-wrap{
    position:relative;
    flex:1;
    min-height:420px;
    border-radius:24px;
    overflow:hidden;
    background:#c7b4a3;
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
    width:72px;
    height:72px;
    border-radius:50%;
    background:rgba(255,255,255,.92);
    color:#8a5737;
    font-size:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
}

/* ===== WIN SCREEN ===== */
.win-screen{
    display:none;
    position:absolute;
    inset:0;
    background:rgba(255,251,235,.92);
    backdrop-filter:blur(6px);
    border-radius:32px;
    z-index:20;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:12px;
    text-align:center;
    animation:fadeIn .45s ease;
}

.win-screen.show{ display:flex; }

@keyframes fadeIn{
    from{ opacity:0; transform:scale(.94); }
    to{ opacity:1; transform:scale(1); }
}

.win-icon{ font-size:72px; line-height:1; }
.win-title{ font-size:40px; font-weight:900; color:#92400e; }
.score-big{ font-size:58px; font-weight:900; color:#16a34a; line-height:1; }
.win-sub{ font-size:20px; color:#b45309; font-weight:800; }

.play-again{
    margin-top:14px;
    background:#ef4444;
    color:#fff;
    border:none;
    border-radius:14px;
    padding:12px 34px;
    font-family:"Cairo", sans-serif;
    font-size:18px;
    font-weight:900;
    cursor:pointer;
}

.play-again:hover{
    filter:brightness(1.05);
    transform:translateY(-2px);
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

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    body{ overflow:auto; }
    .game-page{
        height:auto;
        min-height:100vh;
        grid-template-columns:1fr;
        padding:12px 0;
    }
    .main-card{
        min-height:560px;
    }
    .guide-panel{
        min-height:420px;
    }
    .choices{
        grid-template-columns:repeat(2,1fr);
    }
}
</style>
</head>
<body>

<main class="game-page">

    <!-- MAIN GAME CARD -->
    <section class="main-card" id="mainCard">

        <!-- TOP BAR -->
        <div class="topbar">
            <div>
                <div class="stat-pill">السؤال: <strong id="qNum">1</strong> / <?php echo $cfg_rounds; ?></div>
                <div class="stat-pill">✅ <strong id="score">0</strong> صحيح</div>
            </div>
            <button class="restart-btn" onclick="restartGame()">إعادة اللعبة</button>
        </div>

        <!-- PROGRESS -->
        <div class="progress-bar">
            <div class="progress-fill" id="progress" style="width:0%"></div>
        </div>

        <p class="question-label" id="qLabel">كم عدد النجوم الموجودة؟ 👇</p>

        <div class="objects-stage" id="stage"></div>

        <hr class="divider" id="divider">

        <div class="choices" id="choices"></div>

        <!-- WIN SCREEN -->
        <div class="win-screen" id="winScreen">
            <div class="win-icon">🎉</div>
            <div class="win-title">أحسنت!</div>
            <div class="score-big" id="finalScore">0/8</div>
            <div class="win-sub" id="winSub"></div>
            <button class="play-again" onclick="restartGame()">العب مجدداً</button>
        </div>

    </section>

    <!-- GUIDE PANEL -->
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

</main>

<!-- CONFETTI -->
<canvas id="confettiFull"></canvas>

<script>
const TOTAL_ROUNDS = <?php echo $cfg_rounds; ?>;
const MAX_NUM      = <?php echo $cfg_max_num; ?>;
const MIN_NUM      = 1;
const EMOJI        = '⭐';

let round=0, score=0, locked=false, rounds=[];

/* ── helpers ── */
function rand(a,b){ return Math.floor(Math.random()*(b-a+1))+a; }
function shuffle(a){ return [...a].sort(()=>Math.random()-.5); }

function buildRounds(){
    const arr=[]; let prev=-1;
    for(let i=0;i<TOTAL_ROUNDS;i++){
        let n; do{ n=rand(MIN_NUM,MAX_NUM); }while(n===prev);
        arr.push(n); prev=n;
    }
    return arr;
}
function buildChoices(c){
    const s=new Set([c]);
    while(s.size<4) s.add(rand(MIN_NUM,MAX_NUM));
    return shuffle([...s]);
}

/* ── restart ── */
function restartGame(){
    round=0; score=0; locked=false; rounds=buildRounds();
    document.getElementById('score').textContent=0;
    document.getElementById('winScreen').classList.remove('show');
    ['stage','choices','qLabel','divider'].forEach(id=>{
        document.getElementById(id).style.display='';
    });
    stopConfetti();
    setGuideVideo('../../../images/videos/numbers-guide.mp4','عدّ النجوم ثم انقر على الرقم الصحيح 🔢',false);
    render();
}

/* ── render round ── */
function render(){
    if(round>=TOTAL_ROUNDS){ endGame(); return; }
    locked=false;
    const c=rounds[round];

    document.getElementById('qNum').textContent = round+1;
    document.getElementById('progress').style.width = (round/TOTAL_ROUNDS*100)+'%';

    /* objects */
    const stage=document.getElementById('stage');
    stage.innerHTML='';
    for(let i=0;i<c;i++){
        const s=document.createElement('span');
        s.className='obj'; s.textContent=EMOJI;
        s.style.animationDelay=(i*.04)+'s';
        stage.appendChild(s);
    }

    /* choices */
    const ch=document.getElementById('choices');
    ch.innerHTML='';
    buildChoices(c).forEach(n=>{
        const b=document.createElement('button');
        b.className='choice-btn'; b.textContent=n;
        b.addEventListener('click',()=>pick(b,n,c));
        ch.appendChild(b);
    });
}

/* ── pick ── */
function pick(btn, chosen, correct){
    if(locked) return;
    locked=true;
    const all=document.querySelectorAll('.choice-btn');

    if(chosen===correct){
        btn.classList.add('correct');
        score++;
        document.getElementById('score').textContent=score;
        all.forEach(b=>{ if(b!==btn) b.classList.add('neutral'); });
    } else {
        btn.classList.add('wrong');
        all.forEach(b=>{
            if(b===btn) return;
            if(+b.textContent===correct) b.classList.add('correct');
            else b.classList.add('neutral');
        });
    }

    all.forEach(b=>b.disabled=true);
    setTimeout(()=>{ round++; render(); }, 1100);
}

/* ── end ── */
function endGame(){
    document.getElementById('finalScore').textContent = score+'/'+TOTAL_ROUNDS;
    const msgs=['ممتاز! أداء رائع 🌟','جيد جداً، واصل التقدم!','حاول مرة أخرى لتحسين نتيجتك'];
    document.getElementById('winSub').textContent =
        score>=Math.ceil(TOTAL_ROUNDS*.85) ? msgs[0] :
        score>=Math.ceil(TOTAL_ROUNDS*.6)  ? msgs[1] : msgs[2];

    ['stage','choices','qLabel','divider'].forEach(id=>{
        document.getElementById(id).style.display='none';
    });
    document.getElementById('progress').style.width='100%';
    document.getElementById('winScreen').classList.add('show');
    startConfetti();
    setGuideVideo('../../../images/videos/numbers-win.mp4','أحسنت! 🎉',true);

    /* حفظ التقدم */
    fetch('../../../auth/save_game_progress.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'game_key=numbers&game_label='+encodeURIComponent('لعبة الأعداد')
    }).catch(()=>{});
}


/* ── guide video ── */
function setGuideVideo(src, title, autoplay){
    const gv = document.getElementById('guideVideo');
    const ov = document.getElementById('guideOverlay');
    const gt = document.getElementById('guideTitle');
    if(gt) gt.textContent = title;
    if(gv && gv.src.indexOf(src) === -1){
        gv.pause();
        gv.src = src;
        gv.load();
    }
    if(autoplay && gv){
        gv.play().catch(()=>{});
        if(ov) ov.style.display='none';
    }else if(ov){
        ov.style.display='flex';
    }
}

function toggleGuide(){
    const v=document.getElementById('guideVideo');
    const ov=document.getElementById('guideOverlay');
    if(!v) return;
    if(v.paused){
        v.play().catch(()=>{});
        if(ov) ov.style.display='none';
    }else{
        v.pause();
        if(ov) ov.style.display='flex';
    }
}

document.addEventListener('DOMContentLoaded',()=>{
    const v=document.getElementById('guideVideo');
    const ov=document.getElementById('guideOverlay');
    if(v && ov){
        v.addEventListener('ended',()=>{ ov.style.display='flex'; });
    }
});

/* ── confetti ── */
const cc=document.getElementById('confettiFull');
const ctx=cc.getContext('2d');
let confettiOn=false, cRAF=null, pieces=[];

function resizeCC(){
    cc.width=innerWidth*devicePixelRatio;
    cc.height=innerHeight*devicePixelRatio;
    ctx.setTransform(devicePixelRatio,0,0,devicePixelRatio,0,0);
}
window.addEventListener('resize',resizeCC); resizeCC();

function startConfetti(){
    confettiOn=true; cc.style.display='block'; resizeCC();
    const W=innerWidth, H=innerHeight;
    pieces=Array.from({length:180},()=>({
        x:Math.random()*W, y:-Math.random()*(H*.6)-20,
        w:5+Math.random()*10, h:3+Math.random()*8,
        vy:2+Math.random()*5, vx:-1.5+Math.random()*3,
        rot:Math.random()*Math.PI, vr:-.12+Math.random()*.24,
        a:.8+Math.random()*.2
    }));
    const t0=performance.now();
    const tick=ts=>{
        if(!confettiOn) return;
        if(ts-t0>4000){ stopConfetti(); return; }
        ctx.clearRect(0,0,W,H);
        pieces.forEach(p=>{
            p.x+=p.vx; p.y+=p.vy; p.rot+=p.vr;
            if(p.x<-30) p.x=W+30;
            if(p.x>W+30) p.x=-30;
            ctx.save();
            ctx.translate(p.x,p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle=`hsla(${(p.x+p.y+ts)%360},88%,58%,${p.a})`;
            ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);
            ctx.restore();
        });
        cRAF=requestAnimationFrame(tick);
    };
    cRAF=requestAnimationFrame(tick);
}
function stopConfetti(){
    confettiOn=false; cc.style.display='none';
    ctx.clearRect(0,0,innerWidth,innerHeight);
    if(cRAF) cancelAnimationFrame(cRAF); cRAF=null;
}

/* ── start ── */
restartGame();
</script>
</body>
</html>