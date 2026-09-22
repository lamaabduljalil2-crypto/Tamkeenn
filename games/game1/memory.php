<?php
require_once __DIR__ . '/../../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
require_once '../../config/db.php';

$cfg_pairs   = 4;
$pair_images = []; // مصفوفة: [1 => 'path', 2 => 'path', ...]

$gs_res = $conn->query("SELECT game_settings FROM games WHERE game_type='memory' AND is_active=1 LIMIT 1");
if ($gs_res && $gs_row = $gs_res->fetch_assoc()) {
    $s = json_decode($gs_row['game_settings'] ?? '{}', true) ?? [];
    $cfg_pairs = (int)($s['pairs'] ?? 4);

    /* جمع صور الأزواج من الإعدادات */
    for ($pi = 1; $pi <= 8; $pi++) {
        $imgKey = 'pair_' . $pi . '_image';
        if (!empty($s[$imgKey])) {
            /* المسار مخزون نسبةً لجذر المشروع → نحوّله لمسار من موقع هذه الصفحة */
            $raw = $s[$imgKey];
            if (preg_match('/^https?:\/\//i', $raw)) {
                $pair_images[$pi] = $raw;          // رابط خارجي
            } else {
                $pair_images[$pi] = '../../' . ltrim($raw, '/');
            }
        }
    }
}

/* تحقق: إذا ما في صورة لأي زوج، استخدم الصور الافتراضية */
$has_custom_images = !empty($pair_images);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>لعبة الذاكرة</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

:root{
    --bg1:#f8efe7;
    --bg2:#f4e5d8;
    --card:#fffaf6;
    --accent:#f6c89f;
    --accent-dark:#9b6544;
    --text:#7b5237;
    --soft:#ead8c8;
    --shadow:0 10px 24px rgba(133, 97, 69, 0.12);
}

body{
    font-family:"arial", sans-serif;
    background:
        radial-gradient(circle at top right, #fff8f2 0%, #f8efe7 38%, #f3e5d8 100%);
    color:var(--text);
    height:100vh;
    overflow:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}

/* ================= HEADER ================= */

.children-dash.kids-dashboard{
    width:100%;
    height:110px;
    background:rgba(255, 195, 255, 0.88);
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

.dash-start,
.dash-end{
    display:flex;
    align-items:center;
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
    filter:drop-shadow(0 6px 12px rgba(0,0,0,0.12));
}

/* ================= NAV ICONS ================= */

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

.nav-icon-video,
.circle-video{
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
    font-size:22px;
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

/* ================= PROFILE ================= */

.profile-wrap{
    position:relative;
}

.profile-btn{
    border:none;
    background:rgba(234, 181, 199, 0.88);
    border-radius:999px;
    padding:8px 12px 8px 18px;
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    box-shadow:0 6px 16px rgba(0,0,0,.08);
    transition:.25s ease;
    font-family:"arial", sans-serif;
}

.profile-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 22px rgba(0,0,0,.12);
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
    box-shadow:0 4px 10px rgba(0,0,0,.12);
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
    transition:.2s ease;
}

.profile-menu a:hover{
    background:#f8efe6;
    padding-right:24px;
}

/* ================= MAIN ================= */

.memory-page{
    height:calc(100vh - 110px);
    padding:8px 18px 16px;
}

.hero-box{
    height:100%;
    background:rgba(255, 223, 245, 0.72);
    border-radius:34px;
    box-shadow:var(--shadow);
    padding:18px;
    display:flex;
    flex-direction:column;
    gap:14px;
    margin-top:0;
}

.page-title{
    text-align:center;
}

.page-title h2{
    font-size:30px;
    color:#8a5737;
    margin-bottom:4px;
    font-weight:800;
}

.page-title p{
    font-size:17px;
    color:#9d7558;
    font-weight:600;
}

.game-layout{
    flex:1;
    display:grid;
    grid-template-columns:280px 1fr;
    gap:16px;
    min-height:0;
}

/* ================= GUIDE ================= */

.guide-panel{
    background:linear-gradient(180deg,#fff8f1,#f9ecdf);
    border-radius:28px;
    box-shadow:var(--shadow);
    padding:16px;
    display:flex;
    flex-direction:column;
    min-height:0;
}

.guide-panel h3{
    text-align:center;
    font-size:24px;
    color:#8a5737;
    margin-bottom:12px;
    font-weight:800;
}

.guide-video-wrap{
    position:relative;
    flex:1;
    min-height:0;
    border-radius:24px;
    overflow:hidden;
    background:#ddccb8;
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
    background:rgba(91, 67, 48, 0.12);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.video-overlay-btn span{
    width:78px;
    height:78px;
    border-radius:50%;
    background:rgba(255,255,255,0.92);
    color:#8a5737;
    font-size:35px;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 20px rgba(0,0,0,0.12);
}

.guide-note{
    margin-top:10px;
    text-align:center;
    font-size:15px;
    color:#987256;
    font-weight:600;
    line-height:1.6;
}

/* ================= GAME ================= */

.game-panel{
    background:linear-gradient(180deg,#fffdfb,#fff7f1);
    border-radius:28px;
    box-shadow:var(--shadow);
    padding:16px;
    display:flex;
    flex-direction:column;
    min-height:0;
    position:relative;
    overflow:hidden;
}

.top-bar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
}

.status-box{
    background:#f8ead9;
    border-radius:18px;
    padding:10px 16px;
    font-size:20px;
    font-weight:800;
    color:#8a5737;
}

.restart-btn{
    background:rgb(255, 86, 86);
    color:#fff;
    border:none;
    border-radius:12px;
    padding:10px 22px;
    font-family:"arial", sans-serif;
    cursor:pointer;
    transition:.3s;
    font-size:22px;
    font-weight:800;
}

.restart-btn:hover{
    transform:translateY(-2px);
}

.game-board-wrap{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:0;
    overflow:hidden;
}

.game-board{
    display:grid;
    justify-items:center;
    direction:ltr;
}

.card{
    position:relative;
    transform-style:preserve-3d;
    transition:transform .55s ease;
    cursor:pointer;
}

.card.flip,
.card.matched{
    transform:rotateY(180deg);
}

.card:hover:not(.flip):not(.matched){
    transform:translateY(-4px) scale(1.02);
}

.face{
    position:absolute;
    inset:0;
    border-radius:12px;
    overflow:hidden;
    backface-visibility:hidden;
    border:2px solid #fff;
    box-shadow:0 6px 14px rgba(0,0,0,0.12);
    background:#fff;
}

.face img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.front{
    transform:rotateY(180deg);
}

.card.matched{
    animation:matchedPulse .55s ease;
}

@keyframes matchedPulse{
    0%{transform:rotateY(180deg) scale(1);}
    50%{transform:rotateY(180deg) scale(1.06);}
    100%{transform:rotateY(180deg) scale(1);}
}

/* ================= WIN / WRONG CARD ================= */

.result-overlay{
    position:absolute;
    inset:0;
    background:rgba(67, 35, 35, 0.34);
    backdrop-filter:blur(7px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:60;
}

.result-overlay.show{
    display:flex;
}

.result-box{
    width:min(92%, 525px);
    min-height:330px;
    background:#fffafa;
    border-radius:40px;
    box-shadow:0 26px 55px rgba(79, 44, 44, .22);
    border:3px solid rgba(255,255,255,.95);
    padding:34px 28px 28px;
    text-align:center;
    animation:resultPop .35s ease;
}

@keyframes resultPop{
    from{transform:scale(.78);opacity:0;}
    to{transform:scale(1);opacity:1;}
}

.result-icon{
    font-size:58px;
    line-height:1;
    margin-bottom:18px;
}

.result-title{
    font-size:48px;
    font-weight:900;
    color:#21425f;
    margin-bottom:22px;
}

.result-buttons{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:36px;
    direction:rtl;
}

.result-action{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
    text-decoration:none;
    border:none;
    background:transparent;
    cursor:pointer;
    font-family:Arial, sans-serif;
}

.result-square{
    width:68px;
    height:68px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:34px;
    font-weight:900;
    box-shadow:0 14px 28px rgba(0,0,0,.11);
    transition:.22s ease;
}

.result-action:hover .result-square{
    transform:translateY(-4px) scale(1.04);
}

.result-label{
    font-size:20px;
    font-weight:900;
    color:#6f523e;
    white-space:nowrap;
}

.result-back .result-square{
    background:#e95158;
}

.result-replay .result-square{
    background:#ffd200;
    color:#ffffff;
}

.result-error .result-square{
    background:#e95158;
}

.result-continue .result-square{
    background:#ffd200;
    color:#ffffff;
}

.confetti{
    position:absolute;
    top:-24px;
    width:10px;
    height:18px;
    border-radius:3px;
    animation:fall linear forwards;
    z-index:70;
}

@keyframes fall{
    to{
        transform:translateY(520px) rotate(660deg);
        opacity:1;
    }
}
/* ================= RESPONSIVE ================= */

@media (max-width:900px){
    body{overflow:auto;}
    .children-dash.kids-dashboard{height:auto;padding:14px 0;}
    .children-dash-inner{flex-direction:column;gap:18px;}
    .dash-nav{gap:16px;}
    .circle-icon{width:70px;height:70px;}
    .profile-hello{font-size:15px;}
    .memory-page{height:auto;min-height:calc(100vh - 88px);}
    .hero-box{height:auto;}
    .game-layout{grid-template-columns:1fr;}
    .guide-panel{min-height:320px;}
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
  .guide-panel{min-height:220px!important;}
  .guide-video-wrap{min-height:140px!important;max-height:140px!important;}
  .game-panel{min-height:unset!important;}
  .game-board-wrap{min-height:420px!important;height:420px!important;}
  .top-bar{flex-wrap:wrap!important;gap:8px!important;}
  .status-box{font-size:16px!important;padding:7px 12px!important;}
  .restart-btn{font-size:17px!important;padding:8px 16px!important;}
  .guide-video{object-fit:contain!important;background:#000!important;}
}
</style>
</head>
<body>
<header class="children-dash kids-dashboard">
  <div class="children-dash-inner">

    <div class="dash-start">
      <a href="../../index.php" class="logo-box" tabindex="0">
        <img src="../../logo.png" alt="logo">
      </a>
    </div>

    <nav class="dash-nav">
      <a href="../../auth/children.php" class="circle-icon home-icon" tabindex="0" aria-label="الرئيسية">
        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="../../assets/icons/children.mp4" type="video/mp4">
        </video>
        <span class="dash-hover-label">الرئيسية</span>
      </a>

      <a href="../../games/game1/memory.php" class="circle-icon home-icon" tabindex="0" aria-label="لعبة الذاكرة">
        <video autoplay muted loop playsinline class="circle-video">
          <source src="../../assets/icons/memory.mp4" type="video/mp4">
        </video>
        <span class="dash-hover-label">لعبة الذاكرة</span>
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

<main class="memory-page">
    <div class="hero-box">
        <div class="page-title">
            <h2>لعبة الذاكرة</h2>
            <p>افتح البطاقات وابحث عن الصورتين المتشابهتين</p>
        </div>

        <div class="game-layout">
            <aside class="guide-panel">
                <h3>شرح اللعبة</h3>
                <div class="guide-video-wrap">
                    <video id="guideVideo" class="guide-video" playsinline preload="metadata">
                        <source src="../../images/videos/memory-guide.mp4" type="video/mp4">
                    </video>
                    <button class="video-overlay-btn" id="guideOverlay" onclick="toggleGuideVideo()">
                        <span>▶</span>
                    </button>
                </div>
                <div class="guide-note">شاهد الشرح ثم ابدأ اللعب 🌟</div>
            </aside>

            <section class="game-panel">
                <div class="top-bar">
                    <div class="status-box">
                        عدد الأزواج: <span id="score">0</span> / <?php echo (int)$cfg_pairs; ?>
                    </div>
                </div>

                <div class="game-board-wrap">
                    <div class="game-board" id="gameBoard"></div>
                </div>

                <div class="result-overlay" id="winOverlay">
                    <div class="result-box">
                        <div class="result-icon">🎉</div>
                        <div class="result-title">أحسنت</div>

                        <div class="result-buttons">
                            <a href="../../auth/children.php" class="result-action result-back">
                                <span class="result-square">➜</span>
                                <span class="result-label">العودة</span>
                            </a>

                            <button type="button" class="result-action result-replay" onclick="restartGame()">
                                <span class="result-square">🔁</span>
                                <span class="result-label">إعادة اللعبة</span>
                            </button>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
</main>

<script>
const TOTAL_PAIRS = <?php echo (int)$cfg_pairs; ?>;

/*
 * PAIR_IMAGES: مصفوفة من PHP
 * المفاتيح أرقام (1..N) والقيم مسارات الصور
 * مثال: {1:"../../uploads/games/memory/abc.png", 2:"../../uploads/games/memory/xyz.png"}
 */
const PAIR_IMAGES = <?php echo json_encode($pair_images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

/**
 * إرجاع مسار صورة الوجه الأمامي للزوج رقم num
 * إذا وُجدت صورة مخصصة → تُستخدم، وإلا → الصورة الافتراضية
 */
function pairImageSrc(num) {
    if (PAIR_IMAGES[num]) return PAIR_IMAGES[num];
    return `../../assets/images/memory/${num}.png`;
}

const board      = document.getElementById("gameBoard");
const scoreEl    = document.getElementById("score");
const winOverlay = document.getElementById("winOverlay");

let flippedCards = [];
let lockBoard    = false;
let matchedPairs = 0;

/* ══════════════════════════════════════════
   حساب الحجم الأمثل للبطاقات تلقائياً
══════════════════════════════════════════ */
function adjustLayout() {
    const wrap = document.querySelector('.game-board-wrap');
    if (!wrap) return;

    const totalCards = TOTAL_PAIRS * 2;
    const RATIO      = 3 / 4;
    const GAP        = 10;
    const PAD        = 12;

    const W = wrap.clientWidth  - PAD * 2;
    const H = wrap.clientHeight - PAD * 2;
    if (W <= 0 || H <= 0) return;

    let bestCols = 4, bestCardW = 0;

    for (let cols = 2; cols <= totalCards; cols++) {
        const rows    = Math.ceil(totalCards / cols);
        const maxCardW = (W - GAP * (cols - 1)) / cols;
        const maxCardH = maxCardW / RATIO;
        const totalH   = rows * maxCardH + GAP * (rows - 1);

        let cardW;
        if (totalH <= H) {
            cardW = maxCardW;
        } else {
            const limitH = (H - GAP * (rows - 1)) / rows;
            cardW = limitH * RATIO;
        }

        if (cardW > bestCardW) { bestCardW = cardW; bestCols = cols; }
    }

    const cardW = Math.floor(bestCardW);
    const cardH = Math.floor(cardW / RATIO);

    board.style.gridTemplateColumns = `repeat(${bestCols}, ${cardW}px)`;
    board.style.gap = `${GAP}px`;

    document.querySelectorAll('.card').forEach(card => {
        card.style.width  = cardW + 'px';
        card.style.height = cardH + 'px';
    });
}

/* ══════════════════════════════════════════
   منطق اللعبة
══════════════════════════════════════════ */
function shuffledCards() {
    const images = [];
    for (let i = 1; i <= TOTAL_PAIRS; i++) images.push(i, i);
    return images.sort(() => Math.random() - 0.5);
}

function createBoard() {
    board.innerHTML     = "";
    flippedCards        = [];
    lockBoard           = false;
    matchedPairs        = 0;
    scoreEl.textContent = 0;
    winOverlay.classList.remove("show");
    removeConfetti();

    shuffledCards().forEach(num => {
        const card = document.createElement("div");
        card.className     = "card";
        card.dataset.image = num;

        card.innerHTML = `
            <div class="face back">
                <img src="../../assets/images/memory/0.png" alt="ظهر البطاقة"
                     onerror="this.style.display='none'">
            </div>
            <div class="face front">
                <img src="${pairImageSrc(num)}" alt="صورة الزوج ${num}"
                     onerror="this.src='../../assets/images/memory/${num}.png'">
            </div>
        `;

        card.addEventListener("click", flipCard);
        card.addEventListener("touchend", function(e){ e.preventDefault(); flipCard.call(this); });
        board.appendChild(card);
    });

    requestAnimationFrame(adjustLayout);
}

function flipCard() {
    if (lockBoard) return;
    if (this.classList.contains("flip") || this.classList.contains("matched")) return;
    if (flippedCards.length === 2) return;

    this.classList.add("flip");
    flippedCards.push(this);

    if (flippedCards.length === 2) checkMatch();
}

function checkMatch() {
    const [card1, card2] = flippedCards;

    if (card1.dataset.image === card2.dataset.image) {
        card1.classList.add("matched");
        card2.classList.add("matched");
        flippedCards = [];
        matchedPairs++;
        scoreEl.textContent = matchedPairs;
        if (matchedPairs === TOTAL_PAIRS) setTimeout(showWinEffect, 550);
    } else {
        lockBoard = true;
        setTimeout(() => {
            card1.classList.remove("flip");
            card2.classList.remove("flip");
            flippedCards = [];
            lockBoard    = false;
        }, 700);
    }
}

function restartGame() {
    winOverlay.classList.remove("show");
    const video   = document.getElementById("guideVideo");
    const overlay = document.getElementById("guideOverlay");
    video.src = "../../images/videos/memory-guide.mp4";
    video.load();
    overlay.style.display = "flex";
    createBoard();
}


function showWinEffect() {
    fetch('../../auth/save_game_progress.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_key=memory&game_label=' + encodeURIComponent('لعبة الذاكرة')
    }).catch(() => {});

    winOverlay.classList.add("show");
    createConfetti(45);

    const video   = document.getElementById("guideVideo");
    const overlay = document.getElementById("guideOverlay");
    video.src = "../../images/videos/cups-win.mp4";
    video.load();
    video.play();
    overlay.style.display = "none";
}

function createConfetti(count) {
    const colors = ["#ffd166","#ff9f1c","#ff6b6b","#90e0ef","#caffbf","#ffc6ff","#f7b267"];
    for (let i = 0; i < count; i++) {
        const piece = document.createElement("div");
        piece.className = "confetti";
        piece.style.left = Math.random() * 100 + "%";
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.animationDuration = (2.1 + Math.random() * 1.3) + "s";
        piece.style.animationDelay    = (Math.random() * 0.35) + "s";
        winOverlay.appendChild(piece);
    }
}

function removeConfetti() {
    document.querySelectorAll(".confetti").forEach(el => el.remove());
}

function toggleGuideVideo() {
    const video   = document.getElementById("guideVideo");
    const overlay = document.getElementById("guideOverlay");
    if (video.paused) {
        video.play();
        overlay.style.display = "none";
    } else {
        video.pause();
        overlay.style.display = "flex";
    }
}

const guideVideo   = document.getElementById("guideVideo");
const guideOverlay = document.getElementById("guideOverlay");

guideVideo.addEventListener("click", () => {
    if (guideVideo.paused) {
        guideVideo.play();
        guideOverlay.style.display = "none";
    } else {
        guideVideo.pause();
        guideOverlay.style.display = "flex";
    }
});

guideVideo.addEventListener("ended", () => {
    guideOverlay.style.display = "flex";
});

window.addEventListener('resize', adjustLayout);

createBoard();
</script>

<script>
const profileBtn  = document.getElementById("profileBtn");
const profileWrap = document.getElementById("profileWrap");

profileBtn.addEventListener("click", function(e){
    e.stopPropagation();
    profileWrap.classList.toggle("active");
});

document.addEventListener("click", function(){
    profileWrap.classList.remove("active");
});
</script>
</body>
</html>
