<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
// ✅ تسجيل زيارة قسم الطقس
require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'child') {
    $__uid = intval($_SESSION['user_id']);
    $__cn  = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS progress (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, child_name VARCHAR(255) NULL, activity_type VARCHAR(20) NOT NULL, activity_key VARCHAR(100) NOT NULL, activity_label VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $__st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,'section','section-الطقس','الطقس') ON DUPLICATE KEY UPDATE activity_label=VALUES(activity_label), created_at=CURRENT_TIMESTAMP");
    if ($__st) { mysqli_stmt_bind_param($__st, 'is', $__uid, $__cn); mysqli_stmt_execute($__st); }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
    <title>أحوال الطقس</title>

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
            font-family: "Cairo", Arial, sans-serif;
            background: linear-gradient(135deg, #f7f4ec, #eef5f8);
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
            background: #ffffff;
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
            display: flex;
            flex-direction: row-reverse;
            width: calc(100% - 30px);
            max-width: 1400px;
            height: calc(100vh - 128px);
            margin: 10px auto 0;
            gap: 16px;
        }

        .left-side {
            width: 62%;
            padding: 0;
            background: transparent;
        }

        .display-area {
            width: 100%;
            height: 100%;
            position: relative;
            border-radius: 24px;
            background: #f8f4ed;
            overflow: hidden;
            padding: 18px;
            border: 3px solid #e4ddd0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .main-title {
            margin: 0 0 12px;
            text-align: right;
            font-size: 25px;
            font-weight: 800;
            color: #ff7a45;
        }

        .main-view {
            width: 100%;
            height: calc(100% - 38px);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-sign-wrapper {
            width: 64%;
            max-width: 430px;
            height: 500px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            border-radius: 22px;
            overflow: hidden;
            background: #ddd;
            border: 2px solid #e3d7c8;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            z-index: 3;
            transition: all 0.75s ease;
            visibility: visible;
        }

        .main-sign-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #ddd;
        }

        .weather-image {
            max-width: 70%;
            max-height: 90%;
            width: auto;
            height: auto;
            object-fit: contain;
            position: absolute;
            top: 0%;
            left: 40%;
            border: none;
            box-shadow: none;
            background: transparent;
            border-radius: 22px;
            opacity: 0;
            transition: opacity 0.6s ease, transform 0.6s ease;
            z-index: 1;
        }

        .corner-sign-video {
            position: absolute;
            left: 18px;
            bottom: 18px;
            width: 160px;
            height: 250px;
            border-radius: 18px;
            overflow: hidden;
            border: 2px solid #e3d7c8;
            background: #ddd;
            box-shadow: 0 8px 20px rgba(0,0,0,0.14);
            opacity: 0;
            transform: scale(0.7);
            transition: opacity 0.75s ease, transform 0.75s ease;
            z-index: 5;
            pointer-events: auto;
        }

        .corner-sign-video video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .display-area.active .main-sign-wrapper {
            left: 18px;
            bottom: 18px;
            top: auto;
            transform: translate(0, 0) scale(0.35);
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
        }

        .display-area.active .weather-image {
            opacity: 1;
            transform: scale(1);
        }

        .display-area.active .corner-sign-video {
            opacity: 1;
            transform: scale(1);
        }

        .weather-text {
            position: absolute;
            left: 0px;
            bottom: 300px;
            width: 190px;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.6;
            text-align: center;
            color: #222;
            background: rgba(255,255,255,0.9);
            padding: 10px;
            border-radius: 14px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transition: 0.4s;
            z-index: 5;
        }

        .display-area.active .weather-text {
            opacity: 1;
        }

        .display-area:not(.active) .weather-text {
            opacity: 0;
        }

        /* ===== جهة عناصر الطقس ===== */
        .right-side {
            width: 38%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 16px 18px;
            background: #f8fafb;
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            border: 3px solid #e4ddd0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .weather-title {
            margin: 0 0 18px;
            text-align: center;
            font-size: 30px;
            font-weight: 800;
            color: #e53935;
            z-index: 2;
        }

        .weather-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            position: relative;
            flex: 1;
        }

        .weather-item {
            min-height: 155px;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.45s ease;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.7);
            padding: 14px 10px;
            position: relative;
            z-index: 1;
            transform-origin: center center;
        }

        .weather-item:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
        }

        .weather-item.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.88);
        }

        .weather-item.expanded {
            position: absolute;
            top: 8px;
            right: 0;
            left: 0;
            bottom: 0;
            z-index: 20;
            min-height: auto;
            border-radius: 30px;
            transform: scale(1);
            box-shadow: 0 14px 28px rgba(0,0,0,0.12);
               justify-content: flex-start;
    padding-top: 18px;
    gap: 6px;
    overflow: visible; 
        }

        .weather-item.expanded:hover {
            transform: scale(1);
        }

        .weather-item.expanded .icon-circle {
            width: 125px;
            height: 125px;
            margin-bottom: 10px;
        }

        .weather-item.expanded .weather-name {
            font-size: 30px;
        }

        .weather-spelling {
            display: none;
            width: 100%;
            margin-top: 10px;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }

        .weather-item.expanded .weather-spelling,
        .center-weather.expanded .weather-spelling {
            display: flex;
        }

        .spelling-word {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: center;
            max-width: 95%;
            direction: rtl;
        }

        .spelling-word img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
        }

        .icon-circle {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: all 0.45s ease;
        }

        .icon-circle video,
        .icon-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .weather-name {
            font-size: 22px;
            font-weight: 800;
            color: #222;
            text-align: center;
            line-height: 1.2;
            transition: all 0.45s ease;
        }

        .sunny {
            background: #ed9154;
        }

        .rainy {
            background: #9ac6e0;
        }

        .cloudy {
            background: #7d98b4;
        }

        .windy {
            background: #93d0c1;
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
            background: rgba(0, 0, 0, 0.18);
            transition: opacity 0.25s ease;
            z-index: 2;
        }

        .play-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .play-btn {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.92);
            color: #333;
            font-size: 26px;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        }

        .corner-sign-video .play-btn {
            width: 44px;
            height: 44px;
            font-size: 20px;
        }

        /* ===== العنصر الأوسط ===== */
        .center-weather {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 165px;
            height: 165px;
            background: #f4d2d2;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            z-index: 10;
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
            border: 2px solid #e4ddd0;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 10px 6px;
        }

        .center-weather:hover {
            transform: translate(-50%, -50%) scale(1.08);
        }

        .center-weather .weather-name {
            font-size: 16px;
        }

        .center-weather .icon-circle {
            width: 60px;
            height: 60px;
            margin-bottom: 6px;
        }

        .center-weather.hidden {
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, -50%) scale(0.88);
        }

        .center-weather.expanded {
            top: 50%;
            left: 50%;
            right: auto;
            bottom: auto;
            width: 100%;
            max-width: 100%;
            height: calc(100% - 8px);
            transform: translate(-50%, -50%) scale(1);
            border-radius: 30px;
            z-index: 20;
            padding: 26px 10px;
            gap: 8px;
            justify-content: flex-start;
        }

        .center-weather.expanded:hover {
            transform: translate(-50%, -50%) scale(1);
        }

        .center-weather.expanded .icon-circle {
            width: 125px;
            height: 125px;
            margin-bottom: 10px;
        }

        .center-weather.expanded .weather-name {
            font-size: 30px;
        }

        @media (max-width: 1100px) {
            body {
                overflow: auto;
            }

            .children-dash-inner {
                grid-template-columns: auto 1fr auto !important;
                justify-items: unset;
                text-align: unset;
            }

            .dash-start,
            .dash-end {
                min-width: unset;
                justify-content: center;
            }

            .dash-nav {
                flex-wrap: nowrap;
                gap: 20px;
                justify-content: center;
            }

            .profile-menu {
                left: 0 !important;
                right: auto !important;
            }

            .page {
                flex-direction: column;
                height: auto;
                min-height: auto;
                overflow: visible;
            }

            .left-side,
            .right-side {
                width: 100%;
            }

            .display-area {
                min-height: 420px;
            }

            .main-sign-wrapper {
                width: 72%;
                max-width: 400px;
                height: 230px;
            }

            .corner-sign-video {
                width: 130px;
                height: 185px;
                left: 14px;
                bottom: 14px;
            }

            .weather-title {
                font-size: 28px;
            }

            .weather-item {
                min-height: 140px;
            }

            .weather-item.expanded .icon-circle {
                width: 110px;
                height: 110px;
            }

            .weather-item.expanded .weather-name {
                font-size: 26px;
            }

            .icon-circle {
                width: 74px;
                height: 74px;
            }

            .weather-name {
                font-size: 19px;
            }

            .weather-text {
                bottom: 220px;
                width: 160px;
                font-size: 14px;
            }

            .spelling-word img {
                width: 56px;
                height: 56px;
            }
        }
        @media (max-width: 768px) {
            /* هيدر */
            .children-dash { padding: 0 !important; width: 100% !important; margin: 0 !important; position: sticky !important; top: 0 !important; border-radius: 0 !important; }
            .children-dash-inner { padding: 8px 12px !important; gap: 8px !important; border-radius: 0 !important; }
            .logo-box img { width: 44px !important; height: 44px !important; }
            .logo-text { display: none !important; }
            .dash-nav { gap: 10px !important; flex-wrap: nowrap !important; }
            .circle-icon { width: 44px !important; height: 44px !important; }
            .profile-hello { font-size: 13px !important; max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .profile-video-box { width: 40px !important; height: 40px !important; }
            .profile-menu { left: 0 !important; right: auto !important; }

            /* display area: unlock */
            .display-area { height: auto !important; overflow: visible !important; min-height: unset !important; padding: 14px !important; }
            .main-view { height: auto !important; position: static !important; display: flex !important; flex-direction: column !important; align-items: flex-start !important; justify-content: flex-start !important; gap: 10px !important; }
            .main-title { white-space: normal !important; overflow: visible !important; font-size: 18px !important; }

            /* الفيديو الأولي: أصغر وما يتقص */
            .main-sign-wrapper { position: relative !important; top: auto !important; left: auto !important; transform: none !important; width: 95% !important; max-width: 100% !important; height: 150px !important; }
            .main-sign-video { object-fit: contain !important; background: #f0f0f0 !important; }
            .display-area.active .main-sign-wrapper { display: none !important; }

            /* البطاقات المخفية: لا تحجز مساحة */
            .weather-item.hidden, .center-weather.hidden { display: none !important; }

            /* اخفِ عناصر البطاقة افتراضياً */
            .weather-image { display: none; }
            .corner-sign-video { display: none; }
            .weather-text { display: none; }

            /* الصورة فوق (عرض كامل) */
            .display-area.active .weather-image {
                display: block !important; position: relative !important;
                top: auto !important; left: auto !important; transform: none !important;
                width: 100% !important; max-height: 200px !important;
                opacity: 1 !important; margin: 0 auto; object-fit: contain !important;
            }

            /* نص الشرح + فيديو جنب بعض */
            .display-area.active .weather-text {
                display: block !important; position: relative !important;
                left: auto !important; bottom: auto !important;
                opacity: 1 !important; font-size: 13px !important;
                width: calc(100% - 125px) !important; min-width: 90px;
                background: rgba(255,255,255,0.9); border-radius: 14px; padding: 10px !important;
            }
            .display-area.active .corner-sign-video {
                display: block !important; position: relative !important;
                left: auto !important; bottom: auto !important;
                width: 110px !important; height: 150px !important;
                opacity: 1 !important; transform: none !important; flex-shrink: 0;
            }
            .display-area.active .main-view {
                flex-direction: row !important; flex-wrap: wrap !important;
                align-items: flex-start !important; justify-content: flex-start !important;
            }
            .display-area.active .weather-image { width: 100% !important; }

            /* weather grid */
            .weather-item { min-height: 110px !important; padding: 10px 8px !important; }
            .weather-item.expanded {
                position: relative !important; top: auto !important; right: auto !important;
                left: auto !important; bottom: auto !important;
                grid-column: 1 / -1 !important; transform: none !important;
            }
            .weather-item.expanded:hover { transform: none !important; }

            /* center-weather: في وسط الصف الثاني */
            .center-weather {
                position: relative !important; top: auto !important; left: auto !important;
                transform: none !important;
                width: 155px !important; height: auto !important; min-height: 120px !important;
                grid-column: 1 / -1 !important; justify-self: center !important;
                border-radius: 20px !important; padding: 14px 10px !important;
            }
            .center-weather:hover { transform: scale(1.02) !important; }
            .center-weather.expanded { width: 100% !important; justify-self: stretch !important; transform: none !important; grid-column: 1 / -1 !important; }
            .center-weather .icon-circle { width: 60px !important; height: 60px !important; }
            .center-weather .weather-name { font-size: 16px !important; }

            .icon-circle { width: 60px !important; height: 60px !important; }
            .spelling-word img { width: 56px !important; height: 56px !important; }
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

    <div class="left-side">
        <div class="display-area" id="displayArea">
            <h2 class="main-title" id="displayTitle">هيا نتعرف على أحوال الطقس</h2>

            <div class="main-view">
                <img id="weatherImage" class="weather-image" src="" alt="">

                <div class="main-sign-wrapper" id="mainSignWrapper">
                    <div class="video-player" onclick="toggleVideo('mainSignVideo','mainOverlay')">
                        <video id="mainSignVideo" class="main-sign-video" preload="metadata" playsinline webkit-playsinline>
                            <source src="../../images/general/weather/videos/weather.mp4" type="video/mp4">
                        </video>
                        <div class="play-overlay" id="mainOverlay">
                            <button class="play-btn" type="button" onclick="event.stopPropagation(); playVideo('mainSignVideo','mainOverlay')">▶</button>
                        </div>
                    </div>
                </div>

                <div class="weather-text" id="weatherText"></div>

                <div class="corner-sign-video" id="cornerBox">
                    <div class="video-player" onclick="toggleVideo('cornerSignVideo','cornerOverlay')">
                        <video id="cornerSignVideo" preload="metadata">
                            <source src="" type="video/mp4">
                            المتصفح لا يدعم تشغيل الفيديو
                        </video>
                        <div class="play-overlay" id="cornerOverlay">
                            <button class="play-btn" type="button" onclick="event.stopPropagation(); playVideo('cornerSignVideo','cornerOverlay')">▶</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="right-side">
        <h1 class="weather-title">أحوال الطقس</h1>

        <div class="weather-grid">
            <div class="weather-item sunny" onclick="toggleWeather(this,'sunny','الطقس المشمس','مشمس')">
                <div class="icon-circle">
                    <video autoplay muted loop playsinline>
                        <source src="../../images/general/weather/icons/sunny.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="weather-name">مشمس</div>
                <div class="weather-spelling"></div>
            </div>

            <div class="weather-item rainy" onclick="toggleWeather(this,'rainy','الطقس الماطر','ماطر')">
                <div class="icon-circle">
                    <video autoplay muted loop playsinline>
                        <source src="../../images/general/weather/icons/rainy.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="weather-name">ماطر</div>
                <div class="weather-spelling"></div>
            </div>

            <div class="weather-item cloudy" onclick="toggleWeather(this,'cloudy','الطقس الغائم','غائم')">
                <div class="icon-circle">
                    <video autoplay muted loop playsinline>
                        <source src="../../images/general/weather/icons/cloudy.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="weather-name">غائم</div>
                <div class="weather-spelling"></div>
            </div>

            <div class="weather-item windy" onclick="toggleWeather(this,'windy','الطقس العاصف','عاصف')">
                <div class="icon-circle">
                    <video autoplay muted loop playsinline>
                        <source src="../../images/general/weather/icons/windy.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="weather-name">عاصف</div>
                <div class="weather-spelling"></div>
            </div>

            <div class="center-weather" onclick="toggleWeather(this,'snowy','الطقس مثلج','مثلج')">
                <div class="icon-circle">
                    <video autoplay muted loop playsinline>
                        <source src="../../images/general/weather/icons/snowy.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="weather-name">مثلج</div>
                <div class="weather-spelling"></div>
            </div>
        </div>
    </div>

</div>

<script>
    let expandedWeather = null;

    const weatherTexts = {
        sunny: "تشرق الشمس في الطقس المشمس ويكون الجو دافئاً ومضيئاً.",
        rainy: "يتساقط المطر في الطقس الماطر وتبتل الأرض والطرقات.",
        cloudy: "تغطي الغيوم السماء في الطقس الغائم وقد تحجب أشعة الشمس.",
        windy: "تهب الرياح في الطقس العاصف وتتحرك الأشجار بقوة.",
        snowy: "يتساقط الثلج ويكون الجو بارداً جداً."
    };

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

    function normalizeLetter(letter) {
        const map = {
            "أ": "ا",
            "إ": "ا",
            "آ": "ا",
            "ؤ": "و",
            "ئ": "ي",
            "ة": "ة",
            "ى": "ى"
        };
        return map[letter] || letter;
    }

    function renderSpelling(element, word) {
        const spellingBox = element.querySelector(".weather-spelling");
        if (!spellingBox) return;

        spellingBox.innerHTML = "";

        const words = word.trim().split(/\s+/);

        words.forEach(singleWord => {
            const wordRow = document.createElement("div");
            wordRow.className = "spelling-word";

            [...singleWord].forEach(letter => {
                const normalizedLetter = normalizeLetter(letter);
                const src = arabicLetterImages[normalizedLetter];
                if (!src) return;

                const img = document.createElement("img");
                img.src = src;
                img.alt = letter;
                wordRow.appendChild(img);
            });

            spellingBox.appendChild(wordRow);
        });
    }

    function clearAllSpellings() {
        document.querySelectorAll(".weather-spelling").forEach(box => {
            box.innerHTML = "";
        });
    }

    function changeWeather(weather, title) {
        const displayArea = document.getElementById("displayArea");
        const displayTitle = document.getElementById("displayTitle");
        const mainSignVideo = document.getElementById("mainSignVideo");
        const cornerSignVideo = document.getElementById("cornerSignVideo");
        const weatherImage = document.getElementById("weatherImage");
        const weatherText = document.getElementById("weatherText");
        const mainOverlay = document.getElementById("mainOverlay");
        const cornerOverlay = document.getElementById("cornerOverlay");

        const signVideoPath = "../../images/general/weather/videos/" + weather + ".mp4";
        const imagePath = "../../images/general/weather/images/" + weather + ".png";

        displayTitle.textContent = title;
        weatherText.textContent = weatherTexts[weather];
        weatherImage.src = imagePath;
        weatherImage.alt = title;

        mainSignVideo.pause();
        cornerSignVideo.pause();

        mainSignVideo.src = signVideoPath;
        cornerSignVideo.src = signVideoPath;

        mainSignVideo.load();
        cornerSignVideo.load();

        mainOverlay.classList.remove("hidden");
        cornerOverlay.classList.remove("hidden");

        displayArea.classList.add("active");
    }

    function resetMainView() {
        const displayArea = document.getElementById("displayArea");
        const displayTitle = document.getElementById("displayTitle");
        const mainSignVideo = document.getElementById("mainSignVideo");
        const cornerSignVideo = document.getElementById("cornerSignVideo");
        const mainOverlay = document.getElementById("mainOverlay");
        const cornerOverlay = document.getElementById("cornerOverlay");
        const weatherImage = document.getElementById("weatherImage");
        const weatherText = document.getElementById("weatherText");

        displayArea.classList.remove("active");
        displayTitle.textContent = "هيا نتعرف على أحوال الطقس";

        cornerSignVideo.pause();
        mainSignVideo.pause();

        mainSignVideo.removeAttribute("src");
        cornerSignVideo.removeAttribute("src");
        mainSignVideo.load();
        cornerSignVideo.load();

        weatherImage.src = "";
        weatherImage.alt = "";
        weatherText.textContent = "";

        mainOverlay.classList.remove("hidden");
        cornerOverlay.classList.remove("hidden");
    }

    function toggleWeather(element, weather, title, spellingWord) {
        const allItems = document.querySelectorAll(".weather-item, .center-weather");

        if (expandedWeather === element) {
            element.classList.remove("expanded");

            allItems.forEach(item => {
                item.classList.remove("hidden");
            });

            clearAllSpellings();
            expandedWeather = null;
            resetMainView();
            return;
        }

        allItems.forEach(item => {
            item.classList.remove("expanded");

            if (item !== element) {
                item.classList.add("hidden");
            } else {
                item.classList.remove("hidden");
            }
        });

        clearAllSpellings();

        element.classList.add("expanded");
        renderSpelling(element, spellingWord);

        expandedWeather = element;

        changeWeather(weather, title);
    }

    window.addEventListener("DOMContentLoaded", function () {
        setupVideo("mainSignVideo", "mainOverlay");
        setupVideo("cornerSignVideo", "cornerOverlay");
    });
</script>

</body>
</html>