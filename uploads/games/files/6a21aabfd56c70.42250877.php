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

.game-page{
    width:min(900px, 96%);
    margin:0 auto;
    padding:24px;
    display:flex;
    flex-direction:column;
    gap:16px;
}

/* ── TOP BAR ── */
.topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}
.stat-pill{
    display:flex;
    align-items:center;
    gap:6px;
    background:rgba(255,255,255,.75);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:999px;
    padding:7px 16px;
    font-size:15px;
    color:#3B6D11;
    backdrop-filter:blur(4px);
}
.stat-pill strong{
    font-size:17px;
    font-weight:700;
    color:#085041;
}
.restart-btn{
    background:rgba(255,255,255,.7);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:999px;
    padding:8px 20px;
    font-family:"Cairo", sans-serif;
    font-size:15px;
    font-weight:700;
    color:#085041;
    cursor:pointer;
    transition:background .15s;
}
.restart-btn:hover{ background:rgba(255,255,255,.95); }

/* ── PROGRESS ── */
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

/* ── MAIN CARD ── */
.main-card{
    background:rgba(255,255,255,.7);
    border:0.5px solid rgba(255,255,255,.95);
    border-radius:24px;
    padding:32px 24px;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:24px;
    backdrop-filter:blur(6px);
    min-height:420px;
    justify-content:center;
}

.question-label{
    font-size:18px;
    font-weight:700;
    color:#0F6E56;
}

/* ── OBJECTS STAGE ── */
.objects-stage{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:8px;
    max-width:460px;
    padding:20px 24px;
    background:rgba(255,255,255,.6);
    border:0.5px solid rgba(255,255,255,.9);
    border-radius:18px;
    min-height:90px;
    align-items:center;
}
.obj{
    font-size:36px;
    line-height:1;
    display:inline-block;
    animation:pop .22s ease backwards;
}
@keyframes pop{
    from{ opacity:0; transform:scale(.2); }
    to  { opacity:1; transform:scale(1); }
}

.divider{
    width:90%;
    border:none;
    border-top:0.5px solid rgba(29,158,117,.2);
}

/* ── CHOICES ── */
.choices{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    width:100%;
    max-width:460px;
}
.choice-btn{
    height:70px;
    border-radius:16px;
    border:3px solid rgba(255,255,255,.8);
    font-family:"Cairo", sans-serif;
    font-size:28px;
    font-weight:800;
    cursor:pointer;
    transition:transform .15s, box-shadow .15s;
    outline:none;
    color:#fff;
    text-shadow:0 1px 4px rgba(0,0,0,.15);
}
.choice-btn:nth-child(1){ background:#5DCAA5; border-color:#9FE1CB; }
.choice-btn:nth-child(2){ background:#85B7EB; border-color:#B5D4F4; }
.choice-btn:nth-child(3){ background:#EF9F27; border-color:#FAC775; }
.choice-btn:nth-child(4){ background:#AFA9EC; border-color:#CECBF6; }

.choice-btn:hover:not(:disabled){
    transform:translateY(-5px);
    box-shadow:0 10px 24px rgba(0,0,0,.13);
}
.choice-btn:disabled{ cursor:default; }

/* الإجابة الصحيحة */
.choice-btn.correct{
    background:#639922 !important;
    border-color:#C0DD97 !important;
    color:#fff !important;
    transform:scale(1.1) !important;
    box-shadow:0 0 0 5px rgba(99,153,34,.3), 0 10px 24px rgba(0,0,0,.12) !important;
    animation:correctPop .45s ease;
}
/* الإجابة الخطأ */
.choice-btn.wrong{
    background:#E24B4A !important;
    border-color:#F7C1C1 !important;
    color:#fff !important;
    box-shadow:0 0 0 5px rgba(226,75,74,.3) !important;
    animation:shake .38s ease;
}
/* الأزرار المحايدة */
.choice-btn.neutral{
    opacity:.4;
    transform:none !important;
    box-shadow:none !important;
}

@keyframes correctPop{
    0%  { transform:scale(1.1); }
    50% { transform:scale(1.2); }
    100%{ transform:scale(1.1); }
}
@keyframes shake{
    0%,100%{ transform:translateX(0); }
    25%    { transform:translateX(-9px); }
    75%    { transform:translateX(9px); }
}

/* ── WIN SCREEN ── */
.win-screen{
    display:none;
    flex-direction:column;
    align-items:center;
    gap:12px;
    text-align:center;
    animation:fadeIn .5s ease;
}
.win-screen.show{ display:flex; }
@keyframes fadeIn{
    from{ opacity:0; transform:scale(.9); }
    to  { opacity:1; transform:scale(1); }
}
.win-icon { font-size:64px; line-height:1; margin-bottom:4px; }
.win-title{ font-size:26px; font-weight:900; color:#085041; }
.score-big{
    font-size:56px;
    font-weight:900;
    color:#1D9E75;
    line-height:1;
}
.win-sub{ font-size:16px; color:#0F6E56; font-weight:600; }
.play-again{
    margin-top:12px;
    background:#1D9E75;
    color:#fff;
    border:none;
    border-radius:999px;
    padding:12px 36px;
    font-family:"Cairo", sans-serif;
    font-size:17px;
    font-weight:800;
    cursor:pointer;
    transition:opacity .15s, transform .12s;
}
.play-again:hover{ opacity:.88; transform:translateY(-2px); }

/* ── CONFETTI ── */
#confettiFull{
    position:fixed;inset:0;
    width:100vw;height:100vh;
    display:none;pointer-events:none;z-index:9999;
}

/* ── RESPONSIVE ── */
@media(max-width:600px){
    .choices{ grid-template-columns:repeat(2,1fr); }
    .choice-btn{ height:64px; font-size:24px; }
}
</style>
</head>
<body>

<main class="game-page">

    <!-- TOP BAR -->
    <div class="topbar">
        <div style="display:flex;gap:8px;align-items:center;">
            <div class="stat-pill">سؤال <strong id="qNum">1</strong> / <?php echo $cfg_rounds; ?></div>
            <div class="stat-pill">✅ <strong id="score">0</strong> صحيح</div>
        </div>
        <button class="restart-btn" onclick="restartGame()">🔄 إعادة</button>
    </div>

    <!-- PROGRESS -->
    <div class="progress-bar">
        <div class="progress-fill" id="progress" style="width:0%"></div>
    </div>

    <!-- MAIN CARD -->
    <div class="main-card" id="mainCard">
        <p class="question-label" id="qLabel">كم عدد النجوم؟ 👇</p>
        <div class="objects-stage" id="stage"></div>
        <hr class="divider" id="divider">
        <div class="choices" id="choices"></div>

        <!-- WIN SCREEN (داخل نفس الكارد) -->
        <div class="win-screen" id="winScreen">
            <div class="win-icon">🎉</div>
            <div class="win-title">أحسنت!</div>
            <div class="score-big" id="finalScore">0/8</div>
            <div class="win-sub" id="winSub"></div>
            <button class="play-again" onclick="restartGame()">العب مجدداً</button>
        </div>
    </div>

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

    /* حفظ التقدم */
    fetch('../../auth/save_game_progress.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'game_key=numbers&game_label='+encodeURIComponent('لعبة الأعداد')
    }).catch(()=>{});
}

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