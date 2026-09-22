<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../config/session_child.php';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
$lang = $_SESSION['lang'] ?? 'ar';

require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

$section_id = intval($_GET['id'] ?? 0);
if ($section_id <= 0) { header('Location: general.php'); exit; }

$s = mysqli_prepare($conn, "SELECT * FROM general_sections WHERE id = ?");
mysqli_stmt_bind_param($s, 'i', $section_id);
mysqli_stmt_execute($s);
$section = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if (!$section) { header('Location: general.php'); exit; }

$q = mysqli_prepare($conn, "SELECT * FROM general_section_cards WHERE section_id = ? ORDER BY sort_order ASC");
mysqli_stmt_bind_param($q, 'i', $section_id);
mysqli_stmt_execute($q);
$cards = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);

// تسجيل الزيارة
$__uid = intval($_SESSION['user_id']);
$__cn  = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
$__pkey   = substr('section-custom-' . $section_id, 0, 100);
$__plabel = substr($section['name'] ?? ('قسم ' . $section_id), 0, 255);
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS progress (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, child_name VARCHAR(255) NULL, activity_type VARCHAR(20) NOT NULL, activity_key VARCHAR(100) NOT NULL, activity_label VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$__st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,'section',?,?) ON DUPLICATE KEY UPDATE activity_label=VALUES(activity_label), created_at=CURRENT_TIMESTAMP");
if ($__st) { mysqli_stmt_bind_param($__st, 'isss', $__uid, $__cn, $__pkey, $__plabel); mysqli_stmt_execute($__st); }

$card_colors = ['#ed9154','#9ac6e0','#7d98b4','#93d0c1','#f4d2d2','#a8d8ea','#c9e8a0','#f7c9a0','#c9b8e8','#b8d8f0'];

$intro_title = $section['intro_title'] ?? '';
$intro_video = !empty($section['intro_video']) ? '../../' . $section['intro_video'] : '';

// العنوان الذي يظهر في منطقة العرض
$display_title = !empty($intro_title) ? $intro_title : $section['name'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
    <title><?= htmlspecialchars($section['name']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        :root { --blue-dark: #1d5a90; --text: #21425f; --muted: #688096; }

        body {
            margin: 0;
            font-family: "Cairo", Arial, sans-serif;
            background: linear-gradient(135deg, #f7f4ec, #eef5f8);
            min-height: 100vh;
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .children-dash { width: calc(100% - 30px); max-width: 1400px; margin: 16px auto 0; position: sticky; top: 10px; z-index: 1000; }
        .children-dash-inner {
            background: linear-gradient(135deg, #dff4ff, #cfeeff);
            border-radius: 28px; padding: 16px 22px;
            display: grid; grid-template-columns: auto 1fr auto;
            align-items: center; gap: 20px;
            box-shadow: 0 10px 24px rgba(44,111,170,0.14);
        }
        .dash-start { display: flex; align-items: center; justify-content: flex-start; min-width: 220px; }
        .dash-nav   { display: flex; align-items: center; justify-content: center; gap: 24px; }
        .dash-end   { display: flex; align-items: center; justify-content: flex-end; gap: 14px; min-width: 320px; }

        .logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-box img { width: 58px; height: 58px; object-fit: contain; }

        .circle-icon {
            width: 70px; height: 70px; border-radius: 50%;
            overflow: visible; display: flex; align-items: center; justify-content: center;
            background: #fff; box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            transition: 0.25s ease; text-decoration: none; position: relative;
        }
        .circle-icon:hover { transform: translateY(-3px) scale(1.05); }
        .circle-icon img, .circle-icon video { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        .nav-text {
            position: absolute; top: calc(100% + 8px); left: 50%;
            transform: translateX(-50%) translateY(-6px);
            opacity: 0; pointer-events: none; white-space: nowrap;
            background: linear-gradient(135deg,#ff7aa8,#ff5f8f);
            color: #fff; padding: 7px 16px; border-radius: 999px;
            font-size: 15px; font-weight: 900; transition: .22s ease; z-index: 50;
        }
        .circle-icon:hover .nav-text { opacity: 1; transform: translateX(-50%) translateY(0); }

        .profile-wrap { position: relative; }
        .profile-btn {
            border: none; background: rgba(255,255,255,0.95);
            border-radius: 999px; padding: 8px 16px 8px 10px;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer; box-shadow: 0 8px 18px rgba(44,111,170,0.10); font-family: inherit;
        }
        .profile-video-box { width: 54px; height: 54px; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 3px solid #fff; }
        .profile-video-box video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .profile-hello { font-size: 18px; font-weight: 900; color: #214f7d; white-space: nowrap; }
        .profile-menu {
            position: absolute; top: calc(100% + 10px); right: 0;
            min-width: 230px; background: #fff; border-radius: 24px;
            box-shadow: 0 18px 35px rgba(0,0,0,0.10); padding: 12px 0;
            opacity: 0; visibility: hidden; transform: translateY(8px);
            transition: 0.22s ease; z-index: 1001;
        }
        .profile-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .profile-menu a { display: block; text-decoration: none; color: #214f7d; font-size: 18px; font-weight: 900; padding: 16px 22px; transition: 0.2s; }
        .profile-menu a:hover { background: #f3f9ff; }

        /* ===== PAGE ===== */
        .page {
            display: flex; flex-direction: row-reverse;
            width: calc(100% - 30px); max-width: 1400px;
            height: calc(100vh - 128px);
            margin: 10px auto 0; gap: 16px;
        }

        /* LEFT */
        .left-side { width: 62%; padding: 0; background: transparent; }
        .display-area {
            width: 100%; height: 100%; position: relative;
            border-radius: 24px; background: #f8f4ed;
            overflow: hidden; padding: 18px;
            border: 3px solid #e4ddd0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .main-title {
            margin: 0 0 12px; text-align: right;
            font-size: 25px; font-weight: 800; color: #ff7a45;
        }
        .main-view {
            width: 100%; height: calc(100% - 38px);
            position: relative; display: flex; align-items: center; justify-content: center;
        }

        /* الحالة الأولية */
        .intro-state {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 14px;
            position: absolute; inset: 0; z-index: 2; transition: opacity 0.5s;
        }
        .intro-video-wrap {
            position: relative; width: 72%; max-width: 420px;
            height: 460px;
            border-radius: 20px; overflow: hidden;
            background: #ccc; box-shadow: 0 10px 28px rgba(0,0,0,0.15);
        }
        .intro-video-wrap video { width: 100%; height: 100%; display: block; object-fit: cover; }
        .intro-play-btn {
            position: absolute; inset: 0; margin: auto;
            width: 64px; height: 64px; border-radius: 50%;
            border: 4px solid #fff; background: rgba(255,255,255,0.88);
            color: #333; font-size: 28px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2); transition: .2s;
            pointer-events: all;
        }
        .intro-play-btn:hover { background: #fff; transform: scale(1.08); }
        .intro-play-btn.hide { display: none; }
        .intro-icon { font-size: 90px; line-height: 1; }
        .intro-name { font-size: 30px; font-weight: 900; color: #21425f; }
        .intro-hint { font-size: 16px; color: #8ab0c8; font-weight: 700; }

        /* صورة البطاقة */
        .card-image {
            max-width: 70%; max-height: 90%;
            width: auto; height: auto; object-fit: contain;
            position: absolute; top: 0%; left: 40%;
            border: none; box-shadow: none; background: transparent;
            border-radius: 22px; opacity: 0;
            transition: opacity 0.6s ease, transform 0.6s ease; z-index: 1;
        }

        /* corner-box */
        .corner-box {
            position: absolute; left: 18px; bottom: 18px;
            width: 200px; height: 250px;
            border-radius: 18px; overflow: hidden;
            border: 2px solid #e3d7c8; background: #ddd;
            box-shadow: 0 8px 20px rgba(0,0,0,0.14);
            /* ★ مخفي افتراضياً إلا إذا أضفنا الكلاس */
            opacity: 0; transform: scale(0.7);
            transition: opacity 0.75s ease, transform 0.75s ease;
            z-index: 5; display: flex; align-items: center; justify-content: center;
        }
        .corner-box video, .corner-box img { width: 100%; height: 100%; object-fit: cover; display: block; }


        .card-text {
            position: absolute; left: 0px; bottom: 300px;
            width: 190px; font-size: 15px; font-weight: 800;
            line-height: 1.6; text-align: center; color: #222;
            background: rgba(255,255,255,0.9); padding: 10px;
            border-radius: 14px; box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            opacity: 0; transition: 0.4s; z-index: 5;
        }

        .display-area.active .intro-state  { opacity: 0; pointer-events: none; }
        .display-area.active .card-image   { opacity: 1; }
        .display-area.active .corner-box   { opacity: 1; transform: scale(1); }
        .display-area.active .card-text    { opacity: 1; }

        /* RIGHT */
        .right-side {
            width: 38%; display: flex; flex-direction: column;
            justify-content: center; padding: 16px 18px;
            background: #f8fafb; position: relative; overflow: hidden;
            border-radius: 24px; border: 3px solid #e4ddd0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .section-title-bar { margin: 0 0 18px; text-align: center; font-size: 28px; font-weight: 800; color: #e53935; z-index: 2; }

        .cards-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; position: relative; flex: 1; overflow-y: auto; }
        .cards-grid.one-card .card-item { grid-column: 1 / -1; }
        .card-item:last-child:nth-child(odd) { grid-column: 1 / -1; }

        .card-item {
            min-height: 155px; border-radius: 24px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.45s ease;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            border: 2px solid rgba(255,255,255,0.7);
            padding: 14px 10px; position: relative; z-index: 1;
        }
        .card-item:hover { transform: scale(1.03); box-shadow: 0 10px 22px rgba(0,0,0,0.12); }
        .card-item.hidden { opacity: 0; pointer-events: none; transform: scale(0.88); }
        .card-item.expanded {
            position: absolute; top: 0; right: 0; left: 0; bottom: 0;
            z-index: 20; min-height: auto; border-radius: 30px; transform: scale(1);
            box-shadow: 0 14px 28px rgba(0,0,0,0.12);
            justify-content: flex-start; padding-top: 18px; gap: 6px; overflow: visible;
        }
        .card-item.expanded:hover { transform: scale(1); }
        .card-item.expanded .icon-circle { width: 125px; height: 125px; margin-bottom: 10px; }
        .card-item.expanded .card-name { font-size: 30px; }

        .card-spelling { display: none; width: 100%; margin-top: 10px; align-items: center; justify-content: center; flex-direction: column; gap: 12px; }
        .card-item.expanded .card-spelling { display: flex; }
        .spelling-word { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: center; max-width: 95%; direction: rtl; }
        .spelling-word img { width: 72px; height: 72px; object-fit: contain; background: transparent; border: none; padding: 0; }

        .icon-circle {
            width: 82px; height: 82px; border-radius: 50%;
            overflow: hidden; background: rgba(255,255,255,0.75);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px; transition: all 0.45s ease;
        }
        .icon-circle video, .icon-circle img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .icon-circle .emoji-icon { font-size: 40px; line-height: 1; }

        .card-name { font-size: 22px; font-weight: 800; color: #222; text-align: center; line-height: 1.2; transition: all 0.45s ease; }

        .empty-state { text-align: center; color: #8ab0c8; font-size: 18px; font-weight: 700; padding: 40px; }

        @media (max-width: 1100px) {
            body { overflow: auto; }
            .children-dash-inner { grid-template-columns: auto 1fr auto !important; }
            .dash-start, .dash-end { min-width: unset; justify-content: center; }
            .dash-nav { justify-content: center; }
            .profile-menu { left: 0 !important; right: auto !important; }
            .page { flex-direction: column; height: auto; min-height: auto; }
            .left-side, .right-side { width: 100%; }
            .display-area { min-height: 380px; }
            .intro-video-wrap { width: 90%; }
            .card-text { bottom: 220px; width: 160px; font-size: 14px; }
            .spelling-word img { width: 56px; height: 56px; }
        }
        @media (max-width: 768px) {
            /* ── هيدر ثابت ── */
            .children-dash { width: 100% !important; margin: 0 !important; position: sticky !important; top: 0 !important; border-radius: 0 !important; }
            .children-dash-inner { border-radius: 0 !important; padding: 8px 12px !important; gap: 8px !important; }
            .logo-box img { width: 44px !important; height: 44px !important; }
            .dash-nav { gap: 10px !important; flex-wrap: nowrap !important; }
            .circle-icon { width: 44px !important; height: 44px !important; }
            .profile-hello { font-size: 13px !important; max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .profile-video-box { width: 40px !important; height: 40px !important; }

            /* ── الصفحة ── */
            .page { width: 100%; margin: 8px auto 0; gap: 10px; }

            /* ── منطقة العرض: unlock ── */
            .display-area { height: auto !important; overflow: visible !important; min-height: unset !important; padding: 14px !important; }
            .main-view { height: auto !important; flex-direction: column !important; align-items: center !important; gap: 10px; position: static !important; }

            /* ── الحالة الأولية: فيديو تحت العنوان ── */
            .intro-state { position: relative !important; inset: auto !important; width: 100%; flex-direction: column; align-items: center; }
            .intro-video-wrap { height: 240px !important; width: 95% !important; order: 2; }
            .intro-video-wrap video { object-fit: contain !important; background: #f0f0f0 !important; }
            .intro-icon { order: 1; font-size: 60px !important; }
            .intro-name { order: 1; font-size: 22px !important; }
            .intro-hint { order: 1; }

            /* ── عناصر البطاقة مخفية افتراضياً ── */
            .card-image { display: none; }
            .corner-box { display: none; }
            .card-text  { display: none; }

            /* ── عند تفعيل البطاقة ── */
            /* 1. اخفِ الحالة الأولية */
            .display-area.active .intro-state { display: none !important; }

            /* 2. الصورة في الوسط فوق */
            .display-area.active .card-image {
                display: block !important;
                position: relative !important; top: auto !important; left: auto !important;
                width: 100% !important; max-height: 220px !important;
                object-fit: contain !important; opacity: 1 !important;
                margin: 0 auto;
            }

            /* 3. شريط الشرح + الفيديو جنب بعض */
            .display-area.active .card-text,
            .display-area.active .corner-box {
                display: block !important;
                position: relative !important; left: auto !important; bottom: auto !important;
                opacity: 1 !important; transform: none !important;
            }
            /* wrapper لتجميع النص والفيديو: نعملها بـ flex */
            .display-area.active .main-view { display: flex !important; flex-wrap: wrap !important; align-items: flex-start !important; justify-content: center !important; gap: 10px; }

            .display-area.active .card-text {
                width: calc(100% - 150px) !important; min-width: 120px;
                font-size: 14px !important; background: rgba(255,255,255,0.9);
                border-radius: 14px; padding: 10px !important;
                order: 1;
            }
            .display-area.active .corner-box {
                width: 130px !important; height: 170px !important;
                border-radius: 14px !important; overflow: hidden !important;
                flex-shrink: 0; order: 2;
            }

            /* ── شبكة البطاقات ── */
            .right-side { padding: 10px !important; overflow: visible !important; }
            .cards-grid { grid-template-columns: 1fr 1fr !important; gap: 8px !important; overflow: visible !important; }
            .card-item { min-height: 120px !important; padding: 10px 8px !important; }
            .card-item.hidden { display: none !important; }
            .card-item.expanded { position: relative !important; top: auto !important; right: auto !important; left: auto !important; bottom: auto !important; grid-column: 1 / -1 !important; transform: none !important; }
            .icon-circle { width: 60px !important; height: 60px !important; margin-bottom: 8px !important; }
            .card-name { font-size: 16px !important; }
            .spelling-word img { width: 56px !important; height: 56px !important; }
        }
    </style>
</head>
<body>

<header class="children-dash">
    <div class="children-dash-inner">
        <div class="dash-start">
            <a href="../../index.php" class="logo-box">
                <img src="../../logo.png" alt="logo">
            </a>
        </div>
        <nav class="dash-nav">
            <a href="../../auth/children.php" class="circle-icon" aria-label="صفحة الطفل">
                <video autoplay muted loop playsinline><source src="../../assets/icons/children.mp4" type="video/mp4"></video>
                <span class="nav-text">صفحة الطفل</span>
            </a>
            <a href="general.php" class="circle-icon" aria-label="الثقافة العامة">
                <video autoplay muted loop playsinline><source src="../../assets/icons/general.mp4" type="video/mp4"></video>
                <span class="nav-text">الثقافة العامة</span>
            </a>
        </nav>
        <div class="dash-end">
            <div class="profile-wrap">
                <button type="button" class="profile-btn" id="profileBtn">
                    <span class="profile-hello">مرحباً <?= htmlspecialchars($child_display_name) ?></span>
                    <div class="profile-video-box">
                        <video autoplay muted loop playsinline><source src="../../assets/icons/profile.mp4" type="video/mp4"></video>
                    </div>
                </button>
                <div class="profile-menu" id="profileMenu">
                    <a href="../../auth/account_settings.php">إعدادات الحساب</a>
                    <a href="../../auth/logout.php">تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="page">

    <!-- يسار: منطقة العرض -->
    <div class="left-side">
        <div class="display-area" id="displayArea">
            <h2 class="main-title" id="displayTitle"><?= htmlspecialchars($display_title) ?></h2>

            <div class="main-view">

                <!-- الحالة الأولية -->
                <div class="intro-state" id="introState">
                    <?php if ($intro_video): ?>
                        <div class="intro-video-wrap">
                            <video id="introVid" playsinline preload="auto">
                                <source src="<?= htmlspecialchars($intro_video) ?>" type="video/mp4">
                            </video>
                            <button class="intro-play-btn" id="introPlayBtn" type="button">▶</button>
                        </div>
                    <?php else: ?>
                        <div class="intro-icon"><?= htmlspecialchars($section['icon']) ?></div>
                        <div class="intro-name"><?= htmlspecialchars($section['name']) ?></div>
                        <div class="intro-hint">اضغط على بطاقة لتبدأ</div>
                    <?php endif; ?>
                </div>

                <!-- صورة البطاقة -->
                <img id="cardMainImage" class="card-image" src="" alt="">

                <!-- corner-box: فاضي في البداية، يظهر فيديو الإشارة عند فتح البطاقة -->
                <div class="corner-box" id="cornerBox"></div>

                <!-- نص البطاقة -->
                <div class="card-text" id="cardText"></div>

            </div>
        </div>
    </div>

    <!-- يمين: شبكة البطاقات -->
    <div class="right-side">
        <h1 class="section-title-bar"><?= htmlspecialchars($section['icon']) ?> <?= htmlspecialchars($section['name']) ?></h1>

        <?php if (empty($cards)): ?>
        <div class="empty-state">لا توجد بطاقات في هذا القسم بعد 😊</div>
        <?php else: ?>
        <div class="cards-grid <?= count($cards) === 1 ? 'one-card' : '' ?>" id="cardsGrid">

            <?php foreach ($cards as $i => $card):
                $bg       = $card_colors[$i % count($card_colors)];
                $icon     = $card['icon_file']  ?? '';
                $img      = $card['image']       ?? '';
                $sign     = $card['sign_video']  ?? '';
                $is_vid   = $icon && str_ends_with($icon, '.mp4');
                $img_url  = $img  ? '../../' . $img  : '';
                $icon_url = $icon ? '../../' . $icon : '';
                $sign_url = $sign ? '../../' . $sign : '';
            ?>
            <div class="card-item"
                 style="background:<?= $bg ?>"
                 onclick="toggleCard(this,
                     '<?= addslashes($card['display_name']) ?>',
                     '<?= addslashes($card['description_text'] ?? '') ?>',
                     '<?= addslashes($img_url) ?>',
                     '<?= addslashes($icon_url) ?>',
                     <?= $is_vid ? 'true' : 'false' ?>,
                     '<?= addslashes($sign_url) ?>'
                 )">
                <div class="icon-circle">
                    <?php if ($icon && $is_vid): ?>
                    <video autoplay muted loop playsinline><source src="<?= htmlspecialchars($icon_url) ?>" type="video/mp4"></video>
                    <?php elseif ($icon): ?>
                    <img src="<?= htmlspecialchars($icon_url) ?>" alt="<?= htmlspecialchars($card['display_name']) ?>">
                    <?php else: ?>
                    <span class="emoji-icon">🃏</span>
                    <?php endif; ?>
                </div>
                <div class="card-name"><?= htmlspecialchars($card['display_name']) ?></div>
                <div class="card-spelling"></div>
            </div>
            <?php endforeach; ?>

        </div>
        <?php endif; ?>
    </div>

</div>

<script>
let expandedCard = null;

const DEFAULT_DISPLAY_TITLE = "<?= addslashes(htmlspecialchars($display_title)) ?>";

/* ── الفيديو التعريفي ── */
const introVid     = document.getElementById("introVid");
const introPlayBtn = document.getElementById("introPlayBtn");

if (introVid && introPlayBtn) {
    introPlayBtn.addEventListener("click", function () {
        introVid.play().then(function () {
            introPlayBtn.classList.add("hide");
        }).catch(function () {});
    });
    introVid.addEventListener("ended", function () {
        introPlayBtn.classList.remove("hide");
    });
    introVid.addEventListener("click", function () {
        if (introVid.paused) {
            introVid.play().then(function () { introPlayBtn.classList.add("hide"); }).catch(function(){});
        } else {
            introVid.pause();
            introPlayBtn.classList.remove("hide");
        }
    });
}

/* ── تهجي الحروف ── */
const arabicLetterImages = {
    "ا":"../../images/signs/arabic/1.png","ب":"../../images/signs/arabic/2.png",
    "ت":"../../images/signs/arabic/3.png","ث":"../../images/signs/arabic/4.png",
    "ج":"../../images/signs/arabic/5.png","ح":"../../images/signs/arabic/6.png",
    "خ":"../../images/signs/arabic/7.png","د":"../../images/signs/arabic/8.png",
    "ذ":"../../images/signs/arabic/9.png","ر":"../../images/signs/arabic/10.png",
    "ز":"../../images/signs/arabic/11.png","س":"../../images/signs/arabic/12.png",
    "ش":"../../images/signs/arabic/13.png","ص":"../../images/signs/arabic/14.png",
    "ض":"../../images/signs/arabic/15.png","ط":"../../images/signs/arabic/16.png",
    "ظ":"../../images/signs/arabic/17.png","ع":"../../images/signs/arabic/18.png",
    "غ":"../../images/signs/arabic/19.png","ف":"../../images/signs/arabic/20.png",
    "ق":"../../images/signs/arabic/21.png","ك":"../../images/signs/arabic/22.png",
    "ل":"../../images/signs/arabic/23.png","م":"../../images/signs/arabic/24.png",
    "ن":"../../images/signs/arabic/25.png","ه":"../../images/signs/arabic/26.png",
    "و":"../../images/signs/arabic/27.png","ي":"../../images/signs/arabic/28.png",
    "ة":"../../images/signs/arabic/29.png","ء":"../../images/signs/arabic/30.png",
    "ى":"../../images/signs/arabic/31.png"
};

function normalizeLetter(l) { return {"أ":"ا","إ":"ا","آ":"ا","ؤ":"و","ئ":"ي"}[l] || l; }

function renderSpelling(element, word) {
    const box = element.querySelector(".card-spelling");
    if (!box) return;
    box.innerHTML = "";
    word.trim().split(/\s+/).forEach(function(w) {
        const row = document.createElement("div");
        row.className = "spelling-word";
        [...w].forEach(function(letter) {
            const src = arabicLetterImages[normalizeLetter(letter)];
            if (!src) return;
            const img = document.createElement("img");
            img.src = src; img.alt = letter;
            row.appendChild(img);
        });
        box.appendChild(row);
    });
}

function clearAllSpellings() {
    document.querySelectorAll(".card-spelling").forEach(function(b){ b.innerHTML = ""; });
}

function showCard(name, desc, imgSrc, iconSrc, isVideo, signVideoSrc) {
    const displayArea  = document.getElementById("displayArea");
    const displayTitle = document.getElementById("displayTitle");
    const mainImage    = document.getElementById("cardMainImage");
    const cornerBox    = document.getElementById("cornerBox");
    const cardText     = document.getElementById("cardText");

    displayTitle.textContent = name;
    cardText.textContent     = desc;

    if (imgSrc) { mainImage.src = imgSrc; mainImage.alt = name; }
    else { mainImage.src = ""; }

    // ★ اعرض فيديو إشارة البطاقة في corner-box
    cornerBox.innerHTML = "";
    if (signVideoSrc) {
        const vid = document.createElement("video");
        vid.autoplay = true; vid.muted = true; vid.loop = true; vid.playsInline = true;
        vid.style.cssText = "width:100%;height:100%;object-fit:cover;display:block";
        const src = document.createElement("source");
        src.src = signVideoSrc; src.type = "video/mp4";
        vid.appendChild(src);
        cornerBox.appendChild(vid);
    }

    displayArea.classList.add("active");
}

function resetDisplay() {
    const displayArea  = document.getElementById("displayArea");
    const displayTitle = document.getElementById("displayTitle");
    const mainImage    = document.getElementById("cardMainImage");
    const cornerBox    = document.getElementById("cornerBox");
    const cardText     = document.getElementById("cardText");

    displayArea.classList.remove("active");
    displayTitle.textContent = DEFAULT_DISPLAY_TITLE;
    mainImage.src = "";
    cardText.textContent = "";
    // ★ فاضي تماماً عند إلغاء تحديد البطاقة
    cornerBox.innerHTML = "";
}

function toggleCard(element, name, desc, imgSrc, iconSrc, isVideo, signVideoSrc) {
    const allItems = document.querySelectorAll(".card-item");

    if (expandedCard === element) {
        element.classList.remove("expanded");
        allItems.forEach(function(item){ item.classList.remove("hidden"); });
        clearAllSpellings();
        expandedCard = null;
        resetDisplay();
        return;
    }

    allItems.forEach(function(item){
        item.classList.remove("expanded");
        item.classList.toggle("hidden", item !== element);
    });

    clearAllSpellings();
    element.classList.add("expanded");
    renderSpelling(element, name);
    expandedCard = element;
    showCard(name, desc, imgSrc, iconSrc, isVideo, signVideoSrc);
}

/* Profile menu */
document.getElementById("profileBtn").addEventListener("click", function(e){
    e.stopPropagation();
    document.getElementById("profileMenu").classList.toggle("show");
});
document.addEventListener("click", function(){
    document.getElementById("profileMenu").classList.remove("show");
});
</script>

</body>
</html>
