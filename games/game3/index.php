<?php
require_once __DIR__ . '/../../config/session_child.php';

require_once '../../config/db.php';
$cfg_lives        = 3;
$cfg_player_speed = 5;
$cfg_cat_speed    = 2;
$gs_res = $conn->query("SELECT game_settings FROM games WHERE game_type='chase' AND is_active=1 LIMIT 1");
if ($gs_res && $gs_row = $gs_res->fetch_assoc()) {
    $s = json_decode($gs_row['game_settings'] ?? '{}', true) ?? [];
    $cfg_lives        = (int)($s['lives']        ?? 3);
    $cfg_player_speed = (int)($s['player_speed'] ?? 5);
    $cfg_cat_speed    = (int)($s['cat_speed']    ?? 2);
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>لعبة المطاردة</title>

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
    font-family:"arial", sans-serif;
    background:
        radial-gradient(circle at top right, rgba(255,209,102,.30), transparent 20%),
        radial-gradient(circle at bottom left, rgba(122,214,255,.22), transparent 22%),
        linear-gradient(180deg,#ffe8f4 0%,#ffd6ea 52%,#f7c5df 100%);
    color:#20314f;
    overflow-x:hidden;
    overflow-y:auto;
    min-height:100vh;
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
    font-family:"arial", sans-serif;
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

.game-layout{
    display:grid;
    grid-template-columns:1fr 270px;
    gap:18px;
    align-items:stretch;
}

/* ================= GAME ================= */

.game-card{
    background:rgba(255,255,255,.82);
    border:1px solid rgba(151,190,255,.22);
    backdrop-filter:blur(10px);
    border-radius:32px;
    padding:24px;
    box-shadow:0 22px 50px rgba(68,102,153,.12);
    position:relative;
    overflow:hidden;
}

#wrap{
    width:100%;
    position:relative;
    margin:0 auto;
    border-radius:24px;
    overflow:hidden;
}

canvas#game{
    width:100%;
    height:auto;
    aspect-ratio:2 / 1;
    display:block;
    border-radius:24px;
    box-shadow:0 18px 40px rgba(43,86,145,.16);
    background:#a45d7e;
    position:relative;
    z-index:2;
}

#tomGif{
    position:absolute;
    width:140px;
    height:140px;
    object-fit:contain;
    z-index:4;
    pointer-events:none;
    display:none;
    transform-origin:center center;
}

#startBtn{
    position:absolute;
    inset:0;
    margin:auto;
    width:96px;
    height:96px;
    border-radius:50%;
    border:none;
    z-index:20;
    cursor:pointer;
    background:rgba(255,255,255,.9);
    color:#2d80c8;
    font-size:46px;
    font-weight:900;
    box-shadow:0 14px 32px rgba(37,80,130,.22);
    display:flex;
    align-items:center;
    justify-content:center;
    padding-right:4px;
    transition:.22s ease;
}

#startBtn:hover{
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
    z-index:30;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.20);
    backdrop-filter:blur(10px);
    border-radius:24px;
}

.end-card{
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

.end-emoji{
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

#endText{
    display:none;
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
    font-family:"arial", sans-serif;
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
    background:rgb(255,217,0);
}

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

/* ================= TOUCH CONTROLS ================= */

/* زر التشغيل/الإيقاف - يظهر فوق الكانفاس في الزاوية */
#toggleControlsBtn{
    position:absolute;
    top:12px;
    right:12px;
    z-index:25;
    width:44px;
    height:44px;
    border-radius:50%;
    border:none;
    cursor:pointer;
    background:rgba(255,255,255,0.85);
    font-size:22px;
    box-shadow:0 4px 12px rgba(0,0,0,0.18);
    display:flex;
    align-items:center;
    justify-content:center;
    transition:.2s ease;
    backdrop-filter:blur(6px);
}

#toggleControlsBtn:hover{
    transform:scale(1.1);
}

#toggleControlsBtn.active{
    background:rgba(100,180,255,0.92);
}

/* منطقة الأزرار اللمسية */
#touchControls{
    position:absolute;
    bottom:14px;
    left:0;
    right:0;
    z-index:15;
    display:none; /* مخفية افتراضياً - تُفعَّل بالزر */
    justify-content:space-between;
    align-items:flex-end;
    padding:0 16px;
    pointer-events:none;
}

#touchControls.visible{
    display:flex;
}

/* مجموعة اليسار واليمين */
.touch-lr{
    display:flex;
    gap:10px;
    pointer-events:all;
}

/* زر القفز */
.touch-jump-wrap{
    pointer-events:all;
}

/* الزر الواحد */
.touch-btn{
    width:62px;
    height:62px;
    border-radius:50%;
    border:none;
    cursor:pointer;
    font-size:26px;
    font-weight:900;
    background:rgba(255,255,255,0.78);
    box-shadow:0 6px 18px rgba(0,0,0,0.18);
    backdrop-filter:blur(6px);
    display:flex;
    align-items:center;
    justify-content:center;
    transition:transform .1s ease, background .1s ease;
    -webkit-tap-highlight-color:transparent;
    user-select:none;
    touch-action:none;
}

.touch-btn:active,
.touch-btn.pressed{
    transform:scale(0.9);
    background:rgba(100,180,255,0.82);
}

.touch-jump-wrap .touch-btn{
    width:70px;
    height:70px;
    font-size:30px;
    background:rgba(255,230,100,0.85);
}

.touch-jump-wrap .touch-btn:active,
.touch-jump-wrap .touch-btn.pressed{
    background:rgba(255,200,0,0.92);
}

/* ===== زر إخفاء/إظهار الشرح (يظهر على الجوال فقط) ===== */
#toggleGuideBtn{
    display:none; /* مخفي على الكمبيوتر */
    width:100%;
    margin-bottom:10px;
    padding:10px 16px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#f9ecdf,#f0dfc8);
    color:#8a5737;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    text-align:center;
    box-shadow:0 4px 12px rgba(133,97,69,.12);
    transition:.2s ease;
}

#toggleGuideBtn:active{
    filter:brightness(.95);
}

/* ===== تابلت ===== */
@media(max-width:1024px){
    .game-layout{
        grid-template-columns:1fr 220px;
        gap:12px;
    }

    .guide-title{
        font-size:17px;
    }

    .guide-video-wrap{
        min-height:260px;
    }

    .logo-box img{
        width:64px;
        height:64px;
    }

    .circle-icon{
        width:64px;
        height:64px;
    }

    .profile-video-box{
        width:44px;
        height:44px;
    }

    .profile-hello{
        font-size:15px;
    }
}

/* ===== جوال ===== */
@media(max-width:700px){

    /* ---- هيدر ---- */
    .children-dash{
        height:auto !important;
        padding:6px 10px !important;
    }

    .children-dash-inner{
        display:grid !important;
        grid-template-columns:auto 1fr auto !important;
        align-items:center !important;
        padding:4px 8px !important;
        gap:8px !important;
        height:auto !important;
        min-height:unset !important;
    }
    .dash-nav{ justify-content:center !important; gap:8px !important; }

    .logo-box img{ width:44px !important; height:44px !important; }
    .circle-icon{ width:50px !important; height:50px !important; overflow:hidden !important; }
    .circle-icon video{ width:100% !important; height:100% !important; }
    .dash-hover-label{ display:none !important; }
    .profile-btn{ padding:4px 8px 4px 6px !important; gap:6px !important; }
    .profile-hello{ font-size:12px !important; }
    .profile-video-box{ width:36px !important; height:36px !important; overflow:hidden !important; }
    .profile-video-box video{ width:100% !important; height:100% !important; }
    .profile-menu{ top:56px; left:auto; right:0; min-width:180px; }

    /* ---- الصفحة ---- */
    .game-page{
        width:100% !important;
        margin:0 !important;
        padding:0 !important;
        padding-bottom:16px !important;
    }

    .page-title{ margin-bottom:6px !important; padding-top:8px !important; }
    .page-title h2{ font-size:18px !important; }

    /* ---- layout ---- */
    .game-layout{ grid-template-columns:1fr !important; gap:8px !important; }

    /* ---- كارت اللعبة عرض كامل ---- */
    .game-card{ padding:0 !important; border-radius:0 !important; box-shadow:none !important; }
    #wrap{ border-radius:0 !important; }
    canvas#game{ border-radius:0 !important; }

    /* زر البداية أصغر */
    #startBtn{
        width:68px;
        height:68px;
        font-size:32px;
    }

    /* الشرح مفتوح دائماً على الجوال */
    #toggleGuideBtn{ display:none !important; }
    .guide-panel.collapsed .guide-video-wrap{ display:block !important; }

    /* ---- بانل الفيديو ---- */
    .guide-panel{
        order:-1 !important;
        padding:10px !important;
        border-radius:18px !important;
    }

    .guide-title{
        font-size:14px;
        margin-bottom:6px;
    }

    .guide-video-wrap{
        min-height:150px;
        max-height:160px;
        border-radius:14px;
    }
    .guide-video{ object-fit:contain !important; background:#000 !important; }

    /* ---- شاشة النهاية ---- */
    .end-card{
        padding:18px 14px;
        border-radius:20px;
    }

    .end-emoji{
        font-size:44px;
    }

    #endTitle{
        font-size:24px;
        margin-bottom:10px;
    }

    #restartBtn, #exitBtn{
        width:50px;
        height:42px;
        font-size:20px;
        border-radius:12px;
    }

    /* ---- أزرار التحكم تظهر تلقائياً ---- */
    #touchControls{
        display:flex;
    }

    .touch-btn{
        width:54px;
        height:54px;
        font-size:21px;
    }

    .touch-jump-wrap .touch-btn{
        width:62px;
        height:62px;
        font-size:25px;
    }

    #toggleControlsBtn{
        width:36px;
        height:36px;
        font-size:17px;
    }
}

/* ===== زر الشاشة الكاملة (فوق يمين فيديو الشرح) ===== */
#gameFullscreenBtn {
    display: none;                  /* مخفي على الكمبيوتر */
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 20;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: rgba(0,0,0,.55);
    color: #fff;
    font-size: 17px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    line-height: 1;
}

@media (max-width: 700px) {
    #gameFullscreenBtn { display: flex; }
}

/* ===== زر الخروج من الشاشة الكاملة ===== */
/* position:fixed على body - بالتالي ظاهر فوق كل شي */
#gameFsExit {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 10001;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(0,0,0,.6);
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

/* ===== وضع الشاشة الكاملة المقلوبة ===== */
body.game-fs {
    /* لا overflow:hidden على body - بكسر position:fixed على Safari */
    background: #000 !important;
}

body.game-fs .children-dash,
body.game-fs .page-title,
body.game-fs .guide-panel {
    display: none !important;
}

/* نخفي باقي المحتوى خلف الـ wrap */
body.game-fs .game-page,
body.game-fs .game-layout,
body.game-fs .game-card {
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}

/*
 * CRITICAL: backdrop-filter/filter على .game-card بيخلي position:fixed داخله
 * يتحسب نسبة للعنصر مش للشاشة → #wrap ما يظهر صح.
 * لازم نشيلها في وضع الشاشة الكاملة.
 */
body.game-fs .game-card {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    filter: none !important;
    will-change: auto !important;
}

/*
 * #wrap يصير position:fixed مباشرة على الشاشة
 * → ما في أي container يقصه بـ overflow:hidden
 *
 * بعد rotate(-90deg): visual_width = height, visual_height = width
 * JS يضبط: width = screen_height, height = screen_width
 * → visual_width = screen_width, visual_height = screen_height (كامل الشاشة بدون حواف)
 */
body.game-fs #wrap {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) rotate(-90deg) !important;
    transform-origin: center center !important;
    z-index: 9999 !important;
    overflow: hidden !important;
    border-radius: 0 !important;
}

body.game-fs canvas#game {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    aspect-ratio: unset !important;
    border-radius: 0 !important;
}

body.game-fs #touchControls  { bottom: 8px !important; padding: 0 12px !important; }
body.game-fs .touch-btn       { width: 54px !important; height: 54px !important; font-size: 20px !important; }
body.game-fs .touch-jump-wrap .touch-btn { width: 62px !important; height: 62px !important; }

body.game-fs #gameFsExit        { display: flex !important; }
body.game-fs #gameFullscreenBtn { display: none !important; }

</style>
</head>

<body>

<button id="gameFsExit" type="button" aria-label="خروج من الشاشة الكاملة">✕</button>

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

      <div class="profile-wrap" id="profileWrap">
        <div class="profile-menu" id="profileMenu">
          <a href="../../auth/account_settings.php">إعدادات الحساب</a>
          <a href="../../auth/logout.php">تسجيل الخروج</a>
        </div>
      </div>
    </div>

  </div>
</header>

<main class="game-page">

  <div class="page-title">
    <h2>لعبة المطاردة</h2>
  </div>

  <div class="game-layout">

    <section class="game-card">
      <div id="wrap">
        <canvas id="game" width="1000" height="500"></canvas>

        <img id="tomGif" src="tom.gif" alt="Tom">

        <button id="startBtn" type="button" aria-label="ابدأ اللعبة">▶</button>

        <!-- زر تشغيل/إيقاف أزرار التحكم -->
        <button id="toggleControlsBtn" type="button" title="أزرار التحكم" aria-label="تفعيل أزرار التحكم">🎮</button>

        <!-- أزرار التحكم اللمسية -->
        <div id="touchControls">
          <!-- قفز -->
          <div class="touch-jump-wrap">
            <button class="touch-btn" id="btnJump" type="button" aria-label="قفز">▲</button>
          </div>
          <!-- يمين + يسار (معكوسة) -->
          <div class="touch-lr">
            <button class="touch-btn" id="btnRight" type="button" aria-label="يمين">▶</button>
            <button class="touch-btn" id="btnLeft"  type="button" aria-label="يسار">◀</button>
          </div>
        </div>

        <div id="endOverlay">
          <div class="end-card">
            <div class="end-emoji" id="endEmoji">⭐</div>
            <h2 id="endTitle">انتهت اللعبة</h2>
            <p id="endText">انتهت اللعبة</p>

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

    <aside class="guide-panel" id="guidePanel">
      <!-- زر إخفاء/إظهار فيديو الشرح (جوال فقط) -->
      <button id="toggleGuideBtn" type="button">📹 فيديو الشرح ▼</button>

      <div class="guide-title" id="guideTitle">
      ساعد الفأر على جمع الجبن والوصول إلى الباب و الابتعاد عن القط و المصيدة
      </div>

      <div class="guide-video-wrap">
        <video id="guideVideo" class="guide-video" playsinline preload="metadata">
          <source src="../../images/videos/chase-guide.mp4" type="video/mp4">
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

const restartBtn = document.getElementById("restartBtn");
const startBtn = document.getElementById("startBtn");
const endOverlay = document.getElementById("endOverlay");
const endEmoji = document.getElementById("endEmoji");
const endTitle = document.getElementById("endTitle");
const endText = document.getElementById("endText");

const tomGifEl = document.getElementById("tomGif");
const wrap = document.getElementById("wrap");

const guideVideo = document.getElementById("guideVideo");
const guideOverlay = document.getElementById("guideOverlay");
const guideTitle = document.getElementById("guideTitle");

const confettiCanvas = document.getElementById("confettiFull");
const cctx = confettiCanvas.getContext("2d");

const jerryImg = new Image(); jerryImg.src = "jerry.gif";
const cheeseImg = new Image(); cheeseImg.src = "cheese.png";
const heartImg = new Image(); heartImg.src = "heart.png";
const grassImg = new Image(); grassImg.src = "grass.png";
const doorImg = new Image(); doorImg.src = "door.png";
const cloudImg = new Image(); cloudImg.src = "cloud.png";
const sunImg = new Image(); sunImg.src = "sun.png";
const trapImg = new Image(); trapImg.src = "trap.png";

const GRASS_DROP = 60;
const SOIL_THICKNESS = 10;
const SOIL_OFFSET_UP = 60;
const VISUAL_Y = GRASS_DROP - 30;

const gravity = 0.8;
const worldWidth = 3000;
let cameraX = 0;

let gameStarted = false;

const player = {
  x:100, y:350, w:80, h:80,
  vx:0, vy:0,
  speed: <?php echo (int)$cfg_player_speed; ?>,
  jump:-18,
  onGround:false,
  direction:1
};

const platforms = [
  {x:0, y:420, w:3000, h:80},
  {x:600, y:300, w:200, h:20},
  {x:1100, y:280, w:200, h:20},
  {x:1700, y:240, w:200, h:20},
  {x:2200, y:260, w:200, h:20}
];

const cheeses = [
  {x:650, y:260, taken:false},
  {x:1150, y:230, taken:false},
  {x:1750, y:195, taken:false},
  {x:2250, y:210, taken:false}
];

const TOM_OLD_H = 70;

const tom = {
  x:1200,
  y:340,
  w:120,
  h:120,
  vx: <?php echo (int)$cfg_cat_speed; ?>,
  minX:800,
  maxX:1700,
  direction:1
};

const finish = { x:2800, y:340, w:200, h:280 };

let score = 0;
let lives = <?php echo (int)$cfg_lives; ?>;
let gameOver = false;
let win = false;
let endShown = false;

const trap = {
  x:finish.x - 140,
  w:70,
  h:50
};

let trapHitCooldown = 0;

const keys = {};

/* ================= منع السكرول ================= */
const blockedScrollKeys = ["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Space"];

window.addEventListener("keydown", function(e){
  if(blockedScrollKeys.includes(e.code)) e.preventDefault();
  keys[e.code] = true;
}, { passive:false });

window.addEventListener("keyup", function(e){
  keys[e.code] = false;
});

window.addEventListener("wheel", function(e){ e.preventDefault(); }, { passive:false });
window.addEventListener("touchmove", function(e){ e.preventDefault(); }, { passive:false });

/* ================= أزرار التحكم اللمسية ================= */

const touchControls   = document.getElementById("touchControls");
const toggleControlsBtn = document.getElementById("toggleControlsBtn");
const btnLeft  = document.getElementById("btnLeft");
const btnRight = document.getElementById("btnRight");
const btnJump  = document.getElementById("btnJump");

// كشف الجوال لتشغيل الأزرار تلقائياً
const isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) || window.innerWidth <= 900;

let controlsVisible = isMobile;

function applyControlsVisibility(){
  if(controlsVisible){
    touchControls.classList.add("visible");
    toggleControlsBtn.classList.add("active");
  } else {
    touchControls.classList.remove("visible");
    toggleControlsBtn.classList.remove("active");
  }
}

applyControlsVisibility();

toggleControlsBtn.addEventListener("click", function(e){
  e.stopPropagation();
  controlsVisible = !controlsVisible;
  applyControlsVisibility();
});

// ===== زر الشاشة الكاملة (تقليب 90°) =====
(function(){
  const fsBtn  = document.getElementById("gameFullscreenBtn");
  const fsExit = document.getElementById("gameFsExit");
  const wrapEl = document.getElementById("wrap");

  window.enterFs = function enterFs(){
    const sw = window.innerWidth, sh = window.innerHeight;
    wrapEl.style.width  = sh + "px";
    wrapEl.style.height = sw + "px";
    document.body.classList.add("game-fs");
    // منع دوران الشاشة في وضع الشاشة الكاملة
    try {
      if(screen.orientation && typeof screen.orientation.lock === "function"){
        screen.orientation.lock("portrait").catch(()=>{});
      }
    } catch(e){}
    // أظهر أزرار التحكم تلقائياً
    const tc  = document.getElementById("touchControls");
    const tcb = document.getElementById("toggleControlsBtn");
    if(tc)  tc.classList.add("visible");
    if(tcb) tcb.classList.add("active");
  }

  function exitFs(){
    document.body.classList.remove("game-fs");
    wrapEl.style.width  = "";
    wrapEl.style.height = "";
    // فك قفل اتجاه الشاشة
    try {
      if(screen.orientation && typeof screen.orientation.unlock === "function"){
        screen.orientation.unlock();
      }
    } catch(e){}
    // أرجع زر التشغيل وأوقف اللعبة
    startBtn.style.display = "flex";
    gameStarted = false;
  }

  fsBtn?.addEventListener("click",    enterFs);
  fsBtn?.addEventListener("touchend", e=>{ e.preventDefault(); enterFs(); }, {passive:false});
  fsExit?.addEventListener("click",    exitFs);
  fsExit?.addEventListener("touchend", e=>{ e.preventDefault(); exitFs(); }, {passive:false});
})();

// ===== دالة مساعدة لربط الأحداث اللمسية والفأرة معاً =====
function bindHoldButton(btn, keyCode){
  // لمس
  btn.addEventListener("touchstart", function(e){
    e.preventDefault();
    keys[keyCode] = true;
    btn.classList.add("pressed");
  }, { passive:false });

  btn.addEventListener("touchend", function(e){
    e.preventDefault();
    keys[keyCode] = false;
    btn.classList.remove("pressed");
  }, { passive:false });

  btn.addEventListener("touchcancel", function(e){
    e.preventDefault();
    keys[keyCode] = false;
    btn.classList.remove("pressed");
  }, { passive:false });

  // فأرة (لأجهزة الكمبيوتر)
  btn.addEventListener("mousedown", function(e){
    e.preventDefault();
    keys[keyCode] = true;
    btn.classList.add("pressed");
  });

  btn.addEventListener("mouseup", function(e){
    keys[keyCode] = false;
    btn.classList.remove("pressed");
  });

  btn.addEventListener("mouseleave", function(e){
    keys[keyCode] = false;
    btn.classList.remove("pressed");
  });
}

bindHoldButton(btnLeft,  "ArrowLeft");
bindHoldButton(btnRight, "ArrowRight");
bindHoldButton(btnJump,  "ArrowUp");

/* ================= باقي اللعبة ================= */

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
guideVideo.addEventListener("ended", () => { guideOverlay.style.display = "flex"; });

function resizeConfetti(){
  confettiCanvas.width = window.innerWidth * devicePixelRatio;
  confettiCanvas.height = window.innerHeight * devicePixelRatio;
  cctx.setTransform(devicePixelRatio,0,0,devicePixelRatio,0,0);
}

window.addEventListener("resize", resizeConfetti);
resizeConfetti();

function collide(a,b){
  return a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
}

function loseLife(){
  lives--;
  player.x = 100;
  player.y = 350;
  player.vy = 0;
  if(lives <= 0) gameOver = true;
}

function showEnd(type){
  if(endShown) return;
  endShown = true;
  gameStarted = false;
  tomGifEl.style.display = "none";

  if(type === "win"){
    fetch('../../auth/save_game_progress.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'game_key=chase&game_label=' + encodeURIComponent('لعبة المطاردة')
    }).catch(() => {});

    endEmoji.textContent = "🎉";
    endTitle.textContent = "صح";
    endText.textContent = "";
    document.querySelector(".game-card")?.classList.add("winGlow");
    startConfettiFromTop();
    setGuideVideo("../../images/videos/cups-win.mp4", "أحسنت", true);
  }else{
    endEmoji.textContent = "";
    endTitle.textContent = "خطأ";
    endText.textContent = "";
    setGuideVideo("../../images/videos/cups-lose.mp4", "حاول مرة أخرى", true);
  }

  endOverlay.style.display = "flex";
}

let cloudOffset = 0;
const CLOUD_SPEED = 0.5;
const CLOUD_SPACING = 500;
const CLOUD_Y = 60;

function updateTomGifPosition(){
  if(!tomGifEl || gameOver || win || !gameStarted) return;

  let scaleX, scaleY, canvasOffsetLeft, canvasOffsetTop;

  if(document.body.classList.contains("game-fs")){
    // في وضع الشاشة الكاملة: getBoundingClientRect بيرجع إحداثيات ملتوية بعد الـ rotate
    // نستخدم الأبعاد الحقيقية للكانفاس مباشرة
    scaleX = canvas.offsetWidth  / canvas.width;
    scaleY = canvas.offsetHeight / canvas.height;
    canvasOffsetLeft = 0;
    canvasOffsetTop  = 0;
  } else {
    const canvasRect = canvas.getBoundingClientRect();
    const wrapRect   = wrap.getBoundingClientRect();
    scaleX = canvasRect.width  / canvas.width;
    scaleY = canvasRect.height / canvas.height;
    canvasOffsetLeft = canvasRect.left - wrapRect.left;
    canvasOffsetTop  = canvasRect.top  - wrapRect.top;
  }

  const x = tom.x - cameraX;
  const y = (tom.y + VISUAL_Y) - (tom.h - TOM_OLD_H);

  tomGifEl.style.width  = `${tom.w * scaleX}px`;
  tomGifEl.style.height = `${tom.h * scaleY}px`;
  tomGifEl.style.left   = `${canvasOffsetLeft + (x * scaleX)}px`;
  tomGifEl.style.top    = `${canvasOffsetTop  + (y * scaleY)}px`;
  tomGifEl.style.transform = tom.direction === -1 ? "scaleX(-1)" : "scaleX(1)";
  tomGifEl.style.display = "block";
}

window.addEventListener("resize", updateTomGifPosition);

function resetGame(){
  stopConfetti();
  document.querySelector(".game-card")?.classList.remove("winGlow");

  score = 0;
  lives = <?php echo (int)$cfg_lives; ?>;
  gameOver = false;
  win = false;
  endShown = false;

  player.x = 100;
  player.y = 350;
  player.vx = 0;
  player.vy = 0;
  player.direction = 1;
  player.onGround = false;

  cheeses.forEach(c => c.taken = false);

  tom.x = 1200;
  tom.vx = <?php echo (int)$cfg_cat_speed; ?>;
  tom.direction = 1;

  cameraX = 0;
  trapHitCooldown = 0;
  cloudOffset = 0;

  endOverlay.style.display = "none";
  tomGifEl.style.display = "block";

  setGuideVideo(
    "../../images/videos/chase-guide.mp4",
    "ساعد الفأر على جمع الجبن والوصول إلى الباب و الابتعاد عن القط و المصيدة",
    false
  );
}

startBtn.addEventListener("click", () => {
  gameStarted = true;
  startBtn.style.display = "none";
  tomGifEl.style.display = "block";
  // على الجوال: شاشة كاملة تلقائياً عند بدء اللعبة (تأخير بسيط لضمان رسم الكانفاس أولاً)
  if(window.innerWidth <= 700 && typeof window.enterFs === "function"){
    setTimeout(window.enterFs, 80);
  }
});

restartBtn.addEventListener("click", () => {
  resetGame();
  gameStarted = true;
  startBtn.style.display = "none";
});

function update(){
  if(!gameStarted || gameOver || win) return;

  if(keys["ArrowRight"] || keys["KeyD"]){
    player.vx = player.speed;
    player.direction = 1;
  }else if(keys["ArrowLeft"] || keys["KeyA"]){
    player.vx = -player.speed;
    player.direction = -1;
  }else{
    player.vx = 0;
  }

  if((keys["Space"] || keys["ArrowUp"] || keys["KeyW"]) && player.onGround){
    player.vy = player.jump;
    player.onGround = false;
  }

  player.vy += gravity;
  player.x += player.vx;
  player.y += player.vy;

  if(player.x < 0) player.x = 0;
  if(player.x > worldWidth - player.w) player.x = worldWidth - player.w;

  if(player.y < 0){
    player.y = 0;
    if(player.vy < 0) player.vy = 0;
  }

  if(player.y > canvas.height - player.h){
    player.y = canvas.height - player.h;
    if(player.vy > 0) player.vy = 0;
  }

  player.onGround = false;

  for(const p of platforms){
    const withinX = player.x < p.x + p.w && player.x + player.w > p.x;
    const feet = player.y + player.h;
    const hitTop = feet >= p.y && feet <= p.y + 15;

    if(withinX && hitTop && player.vy >= 0){
      player.y = p.y - player.h;
      player.vy = 0;
      player.onGround = true;
    }
  }

  if(trapHitCooldown > 0) trapHitCooldown--;

  const p0 = platforms[0];
  const grassVisualHeight = 100;
  const soilTopY =
    (p0.y - grassVisualHeight / 2) + GRASS_DROP + grassVisualHeight - SOIL_OFFSET_UP;

  const trapBox = {
    x:trap.x,
    y:(soilTopY + SOIL_THICKNESS) - trap.h - VISUAL_Y,
    w:trap.w,
    h:trap.h
  };

  if(trapHitCooldown === 0 && collide(player, trapBox)){
    trapHitCooldown = 30;
    loseLife();
    return;
  }

  tom.x += tom.vx;

  if(tom.x < tom.minX || tom.x > tom.maxX) tom.vx *= -1;

  if(tom.vx > 0) tom.direction = 1;
  else if(tom.vx < 0) tom.direction = -1;

  if(collide(player, tom)){
    loseLife();
    return;
  }

  for(const c of cheeses){
    if(!c.taken && collide(player, {x:c.x, y:c.y, w:40, h:40})){
      c.taken = true;
      score += 10;
    }
  }

  if(collide(player, finish)){
    win = true;
    return;
  }

  cloudOffset += CLOUD_SPEED;
  if(cloudOffset > CLOUD_SPACING) cloudOffset = 0;

  cameraX = player.x - 400;
  if(cameraX < 0) cameraX = 0;
  if(cameraX > worldWidth - canvas.width) cameraX = worldWidth - canvas.width;
}

function drawBackground(){
  const g = ctx.createLinearGradient(0,0,0,canvas.height);
  g.addColorStop(0,"#6ec6ff");
  g.addColorStop(1,"#e0f7ff");
  ctx.fillStyle = g;
  ctx.fillRect(0,0,canvas.width,canvas.height);

  if(sunImg.complete && sunImg.naturalWidth > 0){
    const sunScreenX = 60 + cameraX * 0.15;
    const sunX = (sunScreenX % (canvas.width + 200)) - 100;
    ctx.drawImage(sunImg, sunX, 30, 120, 120);
  }

  if(cloudImg.complete && cloudImg.naturalWidth > 0){
    const parallax = cameraX * 0.25;
    for(let i = -2; i < 8; i++){
      const x = i * CLOUD_SPACING - parallax + cloudOffset;
      ctx.drawImage(cloudImg, x, CLOUD_Y, 170, 90);
    }
  }
}

function drawPlatforms(){
  for(const p of platforms){
    const grassVisualHeight = 100;
    let x = p.x - cameraX;
    const endX = x + p.w;

    while(x < endX){
      const tileW = 250;
      const drawW = Math.min(tileW, endX - x);
      ctx.drawImage(grassImg, 0, 0, grassImg.naturalWidth, 1500, x, (p.y - grassVisualHeight / 2) + GRASS_DROP, drawW, grassVisualHeight);
      x += drawW;
    }

    ctx.fillStyle = "#8B4513";
    ctx.fillRect(
      p.x - cameraX,
      (p.y - grassVisualHeight / 2) + GRASS_DROP + grassVisualHeight - SOIL_OFFSET_UP,
      p.w,
      SOIL_THICKNESS
    );
  }
}

function drawHUD(){
  ctx.save();

  const pad = 12;
  const boxW = 220;
  const boxH = 72;

  ctx.fillStyle = "rgba(255,255,255,0.8)";
  ctx.fillRect(pad, pad, boxW, boxH);

  ctx.fillStyle = "#000";
  ctx.font = "18px Arial";
  ctx.textBaseline = "top";
  ctx.fillText("Score: " + score, pad + 80, pad + 10);

  const heartSize = 26;
  const gap = 6;
  const heartsY = pad + 38;

  for(let i = 0; i < lives; i++){
    ctx.drawImage(heartImg, pad + 12 + i * (heartSize + gap), heartsY, heartSize, heartSize);
  }

  ctx.restore();
}

function drawJerry(){
  const x = player.x - cameraX;
  const y = player.y + VISUAL_Y;

  ctx.save();

  if(player.direction === -1){
    ctx.scale(-1,1);
    ctx.drawImage(jerryImg, -x - player.w, y, player.w, player.h);
  }else{
    ctx.drawImage(jerryImg, x, y, player.w, player.h);
  }

  ctx.restore();
}

function draw(){
  ctx.clearRect(0,0,canvas.width,canvas.height);
  drawBackground();
  drawPlatforms();

  const p0 = platforms[0];
  const grassVisualHeight = 100;
  const soilTopY = (p0.y - grassVisualHeight / 2) + GRASS_DROP + grassVisualHeight - SOIL_OFFSET_UP;

  for(const c of cheeses){
    if(!c.taken) ctx.drawImage(cheeseImg, c.x - cameraX, c.y + VISUAL_Y, 40, 40);
  }

  drawJerry();

  const trapX = trap.x - cameraX;
  const trapY = (soilTopY + SOIL_THICKNESS) - trap.h;

  if(trapImg.complete && trapImg.naturalWidth > 0){
    ctx.drawImage(trapImg, trapX, trapY, trap.w, trap.h);
  }else{
    ctx.fillStyle = "red";
    ctx.fillRect(trapX, trapY, trap.w, trap.h);
  }

  const doorX = finish.x - cameraX;
  const doorW = finish.w;
  const doorH = finish.h;
  const doorY = (soilTopY + SOIL_THICKNESS) - doorH + 15;

  if(doorImg.complete && doorImg.naturalWidth > 0){
    ctx.drawImage(doorImg, doorX, doorY, doorW, doorH);
  }else{
    ctx.fillStyle = "black";
    ctx.fillRect(doorX, doorY, doorW, doorH);
  }

  drawHUD();

  if(gameStarted) updateTomGifPosition();
}

function gameLoop(){
  update();
  draw();
  if(gameOver) showEnd("lose");
  if(win) showEnd("win");
  requestAnimationFrame(gameLoop);
}

let confettiOn = false;
let confettiRAF = null;
let pieces = [];

function startConfettiFromTop(){
  confettiOn = true;
  confettiCanvas.style.display = "block";
  resizeConfetti();

  const W = window.innerWidth;
  const H = window.innerHeight;

  pieces = Array.from({ length:220 }, () => ({
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
    if(elapsed > 3500){ stopConfetti(); return; }

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
  if(confettiRAF) cancelAnimationFrame(confettiRAF);
  confettiRAF = null;
}

setGuideVideo(
  "../../images/videos/chase-guide.mp4",
  "ساعد الفأر على جمع الجبن والوصول إلى الباب و الابتعاد عن القط والمصيدة",
  false
);

draw();
gameLoop();

const profileBtn = document.getElementById("profileBtn");
const profileWrap = document.getElementById("profileWrap");

if(profileBtn && profileWrap){
    profileBtn.addEventListener("click", function(e){
        e.preventDefault();
        e.stopPropagation();
        profileWrap.classList.toggle("active");
    });

    document.addEventListener("click", function(){
        profileWrap.classList.remove("active");
    });
}

/* ===== زر إخفاء/إظهار فيديو الشرح ===== */
const toggleGuideBtn = document.getElementById("toggleGuideBtn");
const guidePanel     = document.getElementById("guidePanel");

let guideVisible = true;

if(toggleGuideBtn && guidePanel){
    // ابدأ مطوي على الجوال
    if(window.innerWidth <= 700){
        guidePanel.classList.add("collapsed");
        guideVisible = false;
        toggleGuideBtn.textContent = "📹 فيديو الشرح ▼";
    }

    toggleGuideBtn.addEventListener("click", function(){
        guideVisible = !guideVisible;
        if(guideVisible){
            guidePanel.classList.remove("collapsed");
            toggleGuideBtn.textContent = "📹 فيديو الشرح ▲";
        }else{
            guidePanel.classList.add("collapsed");
            toggleGuideBtn.textContent = "📹 فيديو الشرح ▼";
        }
    });
}
</script>

</body>
</html>