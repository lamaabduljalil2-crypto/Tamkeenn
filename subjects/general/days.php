<?php
require_once __DIR__ . '/../../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
// ✅ تسجيل زيارة قسم الأيام
require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'child') {
    $__uid = intval($_SESSION['user_id']);
    $__cn  = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS progress (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, child_name VARCHAR(255) NULL, activity_type VARCHAR(20) NOT NULL, activity_key VARCHAR(100) NOT NULL, activity_label VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $__st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,'section','section-أيام الأسبوع','أيام الأسبوع') ON DUPLICATE KEY UPDATE activity_label=VALUES(activity_label), created_at=CURRENT_TIMESTAMP");
    if ($__st) { mysqli_stmt_bind_param($__st, 'is', $__uid, $__cn); mysqli_stmt_execute($__st); }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
<title>قطار الأيام</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

:root {
    --blue-dark: #1d5a90;
    --text: #21425f;
    --muted: #688096;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #fbf4df, #e4f3fa);
    min-height: 100vh;
    overflow: hidden;
}

/* ===== الهيدر ===== */
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
    border-radius: 28px;
    padding: 16px 22px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 20px;
    box-shadow: 0 10px 24px rgba(44, 111, 170, 0.14);
}

.dash-start {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-width: 220px;
}

.dash-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
}

.dash-end {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    min-width: 320px;
}

.logo-box {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.logo-box img {
    width: 58px;
    height: 58px;
    object-fit: contain;
}

.logo-text h1 {
    margin: 0;
    font-size: 30px;
    line-height: 1.1;
    color: var(--blue-dark);
    font-weight: 900;
}

.logo-text p {
    margin: 4px 0 0;
    font-size: 14px;
    color: #6a8aa5;
    font-weight: 700;
}

 .circle-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        overflow: visible;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        transition: 0.25s ease;
        text-decoration: none;
        position: relative;
    }

        .circle-icon:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 20px rgba(0,0,0,0.18);
        }

        .circle-icon:active {
            transform: scale(0.95);
        }

        .circle-icon img,
        .circle-icon video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 50%;
        }
        .nav-text{
            position:absolute;
            top:calc(100% + 8px);
            left:50%;
            transform:translateX(-50%) translateY(-6px);
            opacity:0;
            pointer-events:none;
            white-space:nowrap;
            background:linear-gradient(135deg,#ff7aa8,#ff5f8f);
            color:#fff;
            padding:7px 16px;
            border-radius:999px;
            font-size:15px;
            font-weight:900;
            transition:.22s ease;
            z-index:50;
            box-shadow:0 8px 18px rgba(255,94,143,.30);
        }

        .circle-icon:hover .nav-text{
            opacity:1;
            transform:translateX(-50%) translateY(0);
        }
.profile-wrap {
    position: relative;
}

.profile-btn {
    border: none;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 999px;
    padding: 8px 16px 8px 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(44, 111, 170, 0.10);
    font-family: inherit;
}

.profile-video-box {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 3px solid #fff;
}

.profile-video-box video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.profile-hello {
    font-size: 18px;
    font-weight: 900;
    color: #214f7d;
    white-space: nowrap;
}

.profile-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    min-width: 230px;
    background: #eccccc;
    border-radius: 24px;
    box-shadow: 0 18px 35px rgba(0, 0, 0, 0.10);
    padding: 12px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px);
    transition: 0.22s ease;
    z-index: 1001;
}

.profile-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-menu a {
    display: block;
    text-decoration: none;
    color: #214f7d;
    font-size: 18px;
    font-weight: 900;
    padding: 16px 22px;
    transition: 0.2s ease;
}

.profile-menu a:hover {
    background: #f3f9ff;
}

/* ===== الصفحة ===== */
.page {
    width: calc(100% - 30px);
    max-width: 1400px;
    height: calc(100vh - 128px);
    margin: 10px auto 0;
}

.train-board {
    width: 100%;
    height: 100%;
    position: relative;
    border-radius: 24px;
    background: #f9fff4;
    overflow: hidden;
    padding: 18px;
    border: 3px solid #e4ddd0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.main-title {
    margin: 0 0 12px;
    text-align: center;
    font-size: 36px;
    font-weight: 900;
    color: #ff7a45;
}

/* ===== القطار ===== */
.train-stage {
    position: relative;
    width: 100%;
    height: calc(100% - 55px);
    display: flex;
    justify-content: center;
    align-items: center;
}

.train-area {
    width: 100%;
    position: relative;
}

.train {
    display: flex;
    align-items: flex-end;
    width: 100%;
    position: relative;
    z-index: 2;
}

/* القاطرة */
.engine {
    flex: 25;
    position: relative;
    cursor: pointer;
    transition: transform 0.35s ease, filter 0.35s ease, opacity 0.35s ease, width 0.35s ease, margin 0.35s ease;
    z-index: 3;
    order: 0;
}

.engine img {
    width: 100%;
    display: block;
}

/* العربات */
.wagon {
    flex: 15;
    position: relative;
    cursor: pointer;
    transition: transform 0.35s ease, filter 0.35s ease, opacity 0.35s ease, width 0.35s ease, margin 0.35s ease;
    z-index: 3;
    overflow: visible;
}

.wagon img {
    width: 100%;
    display: block;
}

/* أسماء الأيام على العربات */
.wagon-label {
    position: absolute;
    left: 45%;
    top: 45%;
    transform: translate(-50%, -50%);
    font-size: 33px;
    font-weight: 900;
    color: #000;
    text-shadow: 0 3px 8px rgba(0,0,0,0.45);
    pointer-events: none;
    white-space: nowrap;
    z-index: 5;
}

.engine.active,
.wagon.active {
    transform: scale(1.08) translateY(-8px);
    z-index: 4;
}

.engine.dimmed,
.wagon.dimmed {
    opacity: 0.45;
    filter: saturate(0.7);
}

.engine.hidden-item,
.wagon.hidden-item {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    flex: 0 !important;
    width: 0 !important;
    margin: 0 !important;
    overflow: hidden;
}

.engine.focused,
.wagon.focused {
    order: 1;
}

/* ===== البطاقة ===== */
.train-card {
    flex: 90;
    order: 2;
    min-height: 460px; /* أطول */
    background: #f2e0e0;
    border-radius: 28px;
    border: 3px solid #e7ddcf;
    box-shadow: 0 18px 34px rgba(0,0,0,0.14);
    padding: 24px;
    z-index: 20;
    opacity: 0;
    visibility: hidden;
    transform: scale(0.96);
    transition: 0.35s ease;
    pointer-events: none;
    display: none;
    margin-inline-start: 10px;
}

.train-card.show {
    display: block;
    opacity: 1;
    visibility: visible;
    transform: scale(1);
    pointer-events: auto;
}

.card-content {
    display: flex;
    flex-direction: row-reverse;
    gap: 18px;
    align-items: stretch;
    height: 100%;
}

.card-video-side {
    width: 50%;
    display: flex;
    align-items: center;
}

.card-info-side {
    width: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 18px;
}

.card-video-holder {
    width: 100%;
    height: 450px; /* أطول */
    border-radius: 22px;
    overflow: hidden;
    background: #fae3e3;
    border: 2px solid #eac283;
    position: relative;
}

.card-video-holder video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #d3d3d3;
}

.video-player {
    position: relative;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.play-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.18);
    transition: opacity 0.25s ease;
    z-index: 2;
}

.play-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

.play-btn {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.94);
    color: #333;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0,0,0,0.18);
}

.card-title {
    margin: 0;
    text-align: center;
    font-size: 34px;
    font-weight: 900;
    color: #1d5a90;
}


.letters-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.letter-sign {
    width: 78px;
    height: 78px;
    object-fit: contain;
    background: #f7fbfd;
    border: 2px solid #ece4d7;
    border-radius: 16px;
    padding: 6px;
    display: block;
    box-shadow: 0 6px 12px rgba(0,0,0,0.05);
}
.letter-text {
    font-size: 34px;
    font-weight: 900;
    color: #14243a;
    line-height: 1;
}

.selected-wagon-display {
    display: none;
    position: relative;
    width: 100%;
    margin-top: 14px;
}

.selected-wagon-display img {
    width: 100%;
    height: auto;
    max-height: 140px;
    object-fit: contain;
    display: block;
}

.selected-wagon-display .card-wagon-day {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 26px;
    font-weight: 900;
    color: #000;
    text-shadow: 0 2px 6px rgba(255,255,255,0.8);
    pointer-events: none;
    white-space: nowrap;
}

.selected-wagon-display.show { display: block; }

@media (max-width: 1100px) {
    body {
        overflow: auto;
    }

    .children-dash-inner {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
    }

    .dash-start,
    .dash-end {
        min-width: unset;
        justify-content: center;
    }

    .dash-nav {
        flex-wrap: wrap;
        gap: 20px;
    }

    .profile-menu {
        right: 50%;
        transform: translate(50%, 8px);
    }

    .profile-menu.show {
        transform: translate(50%, 0);
    }

    .page {
        height: auto;
        min-height: auto;
        margin-bottom: 20px;
    }

    .train-board {
        min-height: 760px;
    }

    .train-stage {
        min-height: 650px;
        height: auto;
    }

    .card-content {
        flex-direction: column;
    }

    .card-video-side,
    .card-info-side {
        width: 100%;
    }

    .letters-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .train {
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .train-card {
        flex: 1 1 100%;
        margin-inline-start: 0;
    }
}

@media (max-width: 700px) {
    .letters-grid { grid-template-columns: repeat(2, 1fr); }
    .card-title { font-size: 28px; }
    .wagon-label { font-size: 18px; }
}

@media (max-width: 768px) {
    body { overflow-x: hidden !important; overflow-y: auto !important; }

    /* header صف مدمج */
    .children-dash {
        width: 100% !important;
        margin: 0 !important;
        top: 0 !important;
        border-radius: 0 !important;
    }

    .children-dash-inner {
        grid-template-columns: auto 1fr auto !important;
        padding: 8px 10px !important;
        gap: 8px !important;
    }

    .dash-start { min-width: unset !important; }
    .dash-end { min-width: unset !important; }

    .logo-box img { width: 40px !important; height: 40px !important; }

    .circle-icon { width: 42px !important; height: 42px !important; }

    .dash-nav { gap: 10px !important; }

    .profile-hello { font-size: 12px !important; }

    .profile-video-box { width: 36px !important; height: 36px !important; }

    .profile-btn { padding: 5px 10px 5px 6px !important; gap: 6px !important; }

    /* page */
    .page {
        height: auto !important;
        margin: 8px auto 16px !important;
        width: 100% !important;
    }

    .train-board {
        min-height: unset !important;
        padding: 10px 10px 28px !important;
        border-radius: 16px !important;
    }

    .main-title { font-size: 22px !important; margin-bottom: 6px !important; }

    /* القطار عمودي على طول الشاشة */
    .train-stage { height: auto !important; min-height: unset !important; }
    .train-area { overflow: visible !important; }

    .train {
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        width: 100% !important;
        align-items: stretch !important;
        gap: 6px !important;
    }

    .engine {
        flex: none !important;
        width: 100% !important;
        height: 80px !important;
        overflow: hidden !important;
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        order: 0 !important;
    }

    .wagon {
        flex: none !important;
        width: 100% !important;
        height: 68px !important;
        overflow: hidden !important;
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .engine img, .wagon img {
        width: 100% !important;
        height: 68px !important;
        max-width: none !important;
        object-fit: contain !important;
        transform: rotate(-90deg) !important;
        transform-origin: center center !important;
    }

    .wagon-label {
        font-size: 15px !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 5 !important;
    }

    /* صورة العربة + اسم اليوم داخلها */
    .card-wagon-image {
        width: 100% !important;
        margin-top: 14px !important;
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .card-wagon-image img {
        width: 100% !important;
        height: auto !important;
        max-height: 130px !important;
        object-fit: contain !important;
    }

    .card-wagon-day {
        display: block !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        font-size: 20px !important;
        font-weight: 900 !important;
        color: #000 !important;
        text-shadow: 0 2px 6px rgba(255,255,255,0.8), 0 0 12px rgba(255,255,255,0.6) !important;
        text-align: center !important;
        white-space: nowrap !important;
        pointer-events: none !important;
        z-index: 5 !important;
    }

    /* البطاقة تطلع فوق القطار */
    .train-card.show {
        order: -1 !important;
        margin-top: 0 !important;
        margin-bottom: 10px !important;
    }

    .engine.hidden-item, .wagon.hidden-item {
        flex: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* البطاقة تحت القطار بعرض كامل */
    .train-card {
        flex: none !important;
        width: 100% !important;
        margin-inline-start: 0 !important;
        margin-top: 10px !important;
        min-height: unset !important;
        padding: 14px !important;
        border-radius: 18px !important;
    }

    .card-content { flex-direction: column !important; gap: 12px !important; }
    .card-video-side, .card-info-side { width: 100% !important; }
    .card-video-holder { height: 200px !important; }
    .card-title { font-size: 22px !important; }
    .letter-sign { width: 50px !important; height: 50px !important; }
    .letters-grid { gap: 8px !important; }
}
</style>
</head>
<body>

<header class="children-dash">
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
   aria-label="العودة إلى الصفحة الرئيسية">
  <video class="nav-icon-video" autoplay muted loop playsinline>
    <source src="../../assets/icons/children.mp4" type="video/mp4">
  </video>
  <span class="nav-text">صفحة الطفل</span>
</a>

<a href="../../subjects/general/general.php"
   class="circle-icon home-icon"
   tabindex="0"
   aria-label="قسم الثقافة العامة">
  <video class="nav-icon-video" autoplay muted loop playsinline>
    <source src="../../assets/icons/general.mp4" type="video/mp4">
  </video>
  <span class="nav-text">الثقافة العامة</span>
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

<div class="page">
    <div class="train-board">
        <h1 class="main-title" > أيام الأسبوع</h1>

        <div class="train-stage">
            <div class="train-area">

                <div class="train">
                    <div class="engine item" data-key="engine" onclick="toggleItem(this,'engine')">
                        <img src="../../images/general/days/train/0.png" alt=" الأسبوع">
                    </div>

                    <div class="wagon item" data-key="saturday" onclick="toggleItem(this,'saturday')">
                        <img src="../../images/general/days/train/1.png" alt="السبت">
                        <span class="wagon-label">السبت</span>
                    </div>

                    <div class="wagon item" data-key="sunday" onclick="toggleItem(this,'sunday')">
                        <img src="../../images/general/days/train/2.png" alt="الأحد">
                        <span class="wagon-label">الأحد</span>
                    </div>

                    <div class="wagon item" data-key="monday" onclick="toggleItem(this,'monday')">
                        <img src="../../images/general/days/train/3.png" alt="الإثنين">
                        <span class="wagon-label">الإثنين</span>
                    </div>

                    <div class="wagon item" data-key="tuesday" onclick="toggleItem(this,'tuesday')">
                        <img src="../../images/general/days/train/4.png" alt="الثلاثاء">
                        <span class="wagon-label">الثلاثاء</span>
                    </div>

                    <div class="wagon item" data-key="wednesday" onclick="toggleItem(this,'wednesday')">
                        <img src="../../images/general/days/train/5.png" alt="الأربعاء">
                        <span class="wagon-label">الأربعاء</span>
                    </div>

                    <div class="wagon item" data-key="thursday" onclick="toggleItem(this,'thursday')">
                        <img src="../../images/general/days/train/6.png" alt="الخميس">
                        <span class="wagon-label">الخميس</span>
                    </div>

                    <div class="wagon item" data-key="friday" onclick="toggleItem(this,'friday')">
                        <img src="../../images/general/days/train/7.png" alt="الجمعة">
                        <span class="wagon-label">الجمعة</span>
                    </div>

                    <div class="train-card" id="trainCard">
                        <div class="card-content">
                            <div class="card-video-side">
                                <div class="card-video-holder">
                                    <div class="video-player" onclick="toggleVideo('dayVideo','dayOverlay')">
                                        <video id="dayVideo" preload="metadata">
                                            <source src="" type="video/mp4">
                                        </video>
                                        <div class="play-overlay" id="dayOverlay">
                                            <button class="play-btn" type="button" onclick="event.stopPropagation(); playVideo('dayVideo','dayOverlay')">▶</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                    <div class="card-info-side">
                                        <h3 class="card-title" id="cardTitle">اليوم</h3>
                                        <div class="letters-grid" id="lettersGrid"></div>
                                       
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script>
const profileBtn = document.getElementById("profileBtn");
const profileMenu = document.getElementById("profileMenu");

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

const arabicLetterImages = {
    "ا": "../../images/signs/arabic/1.png",
    "ب": "../../images/signs/arabic/2.png",
    "ت": "../../images/signs/arabic/3.png",
    "ث": "../../images/signs/arabic/4.png",
    "ج": "../../images/signs/arabic/5.png",
    "ح": "../../images/signs/arabic/6.png",
    "خ": "../../images/signs/arabic/7.png",
    "د": "../../images/signs/arabic/8.png",
    "ذ": "../../images/signs/arabic/9.png",
    "ر": "../../images/signs/arabic/10.png",
    "ز": "../../images/signs/arabic/11.png",
    "س": "../../images/signs/arabic/12.png",
    "ش": "../../images/signs/arabic/13.png",
    "ص": "../../images/signs/arabic/14.png",
    "ض": "../../images/signs/arabic/15.png",
    "ط": "../../images/signs/arabic/16.png",
    "ظ": "../../images/signs/arabic/17.png",
    "ع": "../../images/signs/arabic/18.png",
    "غ": "../../images/signs/arabic/19.png",
    "ف": "../../images/signs/arabic/20.png",
    "ق": "../../images/signs/arabic/21.png",
    "ك": "../../images/signs/arabic/22.png",
    "ل": "../../images/signs/arabic/23.png",
    "م": "../../images/signs/arabic/24.png",
    "ن": "../../images/signs/arabic/25.png",
    "ه": "../../images/signs/arabic/26.png",
    "و": "../../images/signs/arabic/27.png",
    "ي": "../../images/signs/arabic/28.png",
    "ة": "../../images/signs/arabic/29.png",
    "ء": "../../images/signs/arabic/30.png",
    "ى": "../../images/signs/arabic/31.png"
    
};

const itemsData = {
    engine: {
        title: " الأسبوع",
        image: "../../images/general/days/train/0.png",
        video: "../../images/general/days/videos/engine.mp4",
letters: [ "ا", "ل", "ا", "س", "ب", "و", "ع"]    },
    saturday: {
        title: "السبت",
        image: "../../images/general/days/train/1.png",
        video: "../../images/general/days/videos/saturday.mp4",
        letters: ["ا", "ل", "س", "ب", "ت"]
    },
    sunday: {
        title: "الأحد",
        image: "../../images/general/days/train/2.png",
        video: "../../images/general/days/videos/sunday.mp4",
        letters: ["ا", "ل", "ا", "ح", "د"]
    },
    monday: {
        title: "الإثنين",
        image: "../../images/general/days/train/3.png",
        video: "../../images/general/days/videos/monday.mp4",
        letters: ["ا", "ل", "ا", "ث", "ن", "ي", "ن"]
    },
    tuesday: {
        title: "الثلاثاء",
        image: "../../images/general/days/train/4.png",
        video: "../../images/general/days/videos/tuesday.mp4",
        letters: ["ا", "ل", "ث", "ل", "ا", "ث", "ا", "ء"]
    },
    wednesday: {
        title: "الأربعاء",
        image: "../../images/general/days/train/5.png",
        video: "../../images/general/days/videos/wednesday.mp4",
        letters: ["ا", "ل", "ا", "ر", "ب", "ع", "ا", "ء"]
    },
    thursday: {
        title: "الخميس",
        image: "../../images/general/days/train/6.png",
        video: "../../images/general/days/videos/thursday.mp4",
        letters: ["ا", "ل", "خ", "م", "ي", "س"]
    },
    friday: {
        title: "الجمعة",
        image: "../../images/general/days/train/7.png",
        video: "../../images/general/days/videos/friday.mp4",
        letters: ["ا", "ل", "ج", "م", "ع", "ة"]
    }
};

let activeKey = null;

function setupVideo(videoId, overlayId) {
    const video = document.getElementById(videoId);
    const overlay = document.getElementById(overlayId);

    video.removeAttribute("autoplay");
    video.loop = false;
    video.controls = false;

    overlay.classList.remove("hidden");

    video.addEventListener("play", function () {
        overlay.classList.add("hidden");
    });

    video.addEventListener("pause", function () {
        if (!video.ended) {
            overlay.classList.remove("hidden");
        }
    });

    video.addEventListener("ended", function () {
        overlay.classList.remove("hidden");
        video.currentTime = 0;
    });
}

function playVideo(videoId, overlayId) {
    const video = document.getElementById(videoId);
    const overlay = document.getElementById(overlayId);

    video.play();
    overlay.classList.add("hidden");
}

function toggleVideo(videoId, overlayId) {
    const video = document.getElementById(videoId);
    const overlay = document.getElementById(overlayId);

    if (video.paused || video.ended) {
        video.play();
        overlay.classList.add("hidden");
    } else {
        video.pause();
        overlay.classList.remove("hidden");
    }
}

function renderLetters(letters) {
    const grid = document.getElementById("lettersGrid");
    grid.innerHTML = "";

    letters.forEach(letter => {
        const imgSrc = arabicLetterImages[letter] || "";

        const img = document.createElement("img");
        img.className = "letter-sign";
        img.src = imgSrc;
        img.alt = "";
        grid.appendChild(img);
    });
}

function stopMainVideo() {
    const dayVideo = document.getElementById("dayVideo");
    const overlay = document.getElementById("dayOverlay");

    dayVideo.pause();
    dayVideo.currentTime = 0;
    dayVideo.removeAttribute("src");
    dayVideo.load();
    overlay.classList.remove("hidden");
}

function resetTrainState() {
    const allItems = document.querySelectorAll(".item");
    const card = document.getElementById("trainCard");

    allItems.forEach(item => {
        item.classList.remove("active", "dimmed", "hidden-item", "focused");
        item.style.order = "";
    });

    card.classList.remove("show");
    stopMainVideo();
    activeKey = null;
}

function toggleItem(element, key) {
    const allItems = document.querySelectorAll(".item");
    const card = document.getElementById("trainCard");
    const title = document.getElementById("cardTitle");
    const dayVideo = document.getElementById("dayVideo");
    const overlay = document.getElementById("dayOverlay");
    const data = itemsData[key];

    if (activeKey === key) {
        resetTrainState();
        return;
    }

    allItems.forEach(item => {
        item.classList.remove("active", "dimmed", "hidden-item", "focused");

        if (item === element) {
            item.classList.add("active", "focused");
        } else if (item.classList.contains("engine") && !element.classList.contains("engine")) {
            // أخفي الماكينة لما تُفتح عربة يوم
            item.classList.add("hidden-item");
        } else if (item.classList.contains("engine") && element.classList.contains("engine")) {
            // إذا ضغطت على الماكينة نفسها أبقيها
            item.classList.remove("hidden-item");
        } else {
            item.classList.add("hidden-item");
        }
    });

    if (element.classList.contains("engine")) {
        allItems.forEach(item => {
            if (item !== element) {
                item.classList.add("hidden-item");
            }
        });
    }

    title.textContent = data.title;
    renderLetters(data.letters);

    const cardWagonImg = document.getElementById("cardWagonImg");
    if (cardWagonImg) cardWagonImg.src = data.image;
    const cardWagonDay = document.getElementById("cardWagonDay");
    if (cardWagonDay) cardWagonDay.textContent = data.title;
    const selectedWagonDisplay = document.getElementById("selectedWagonDisplay");
    if (selectedWagonDisplay) selectedWagonDisplay.classList.add("show");

    dayVideo.pause();
    dayVideo.src = data.video;
    dayVideo.load();
    overlay.classList.remove("hidden");

    card.classList.add("show");
    activeKey = key;
}

window.addEventListener("DOMContentLoaded", function () {
    setupVideo("dayVideo", "dayOverlay");
});
</script>

</body>
</html>