<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session_child.php';

require_once '../config/db.php';

// منع بقاء ملفات الفيديو القديمة في كاش المتصفح/CDN
$video_cache_version = time();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['siteLanguage']) && in_array($_COOKIE['siteLanguage'], ['en', 'ar'])) {
        $_SESSION['lang'] = $_COOKIE['siteLanguage'];
    } else {
        $_SESSION['lang'] = 'ar';
    }
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('siteLanguage', $_GET['lang'], time() + (30 * 24 * 60 * 60), '/');
}

$lang = $_SESSION['lang'];
$dir  = ($lang === 'en') ? 'ltr' : 'rtl';

$child_display_name = trim($_SESSION['full_name'] ?? '');
if ($child_display_name === '') $child_display_name = trim($_SESSION['username'] ?? '');
if ($child_display_name === '') $child_display_name = ($lang === 'ar') ? 'الطفل' : 'Child';

/* ── جلب الألعاب ── */
$db_games = [];
$games_res = $conn->query("SELECT * FROM games WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
if ($games_res) {
    while ($g = $games_res->fetch_assoc()) $db_games[] = $g;
}

$signVideoMap = [
    'memory'  => '../assets/videos/memory-game-sign.mp4?v=' . $video_cache_version,
    'cups'    => '../assets/videos/cup-game-sign.mp4?v=' . $video_cache_version,
    'chase'   => '../assets/videos/chase-game-sign.mp4?v=' . $video_cache_version,
    'colors'  => '../assets/videos/colors-game-sign.mp4?v=' . $video_cache_version,
    'numbers' => '../assets/videos/numbers-game-sign.mp4?v=' . $video_cache_version,
];

$text = [
    'ar' => [
        'page_title'     => 'صفحة الطفل',
        'logo_title'     => '  ',
        'logo_sub'       => '  ',
        'home'           => 'الرئيسية',
        'educational'    => 'القسم التعليمي',
        'games'          => 'الألعاب',
        'chat'           => 'الرسائل',
        'settings'       => 'إعدادات الحساب',
        'logout'         => 'تسجيل الخروج',
        'hello'          => 'أهلاً',
        'hero_label'     => 'لوحة الطفل',
        'hero_desc'      => 'استكشف الأقسام التعليمية والأنشطة والألعاب الممتعة داخل منصة  .',
        'start_learning' => 'ابدأ التعلم',
        'go_games'       => 'اذهب إلى الألعاب',
        'education_title'=> 'الأقسام التعليمية',
        'education_desc' => 'اختر القسم الذي تريد الدخول إليه مباشرة.',
        'edu_badge'      => 'تعليمي',
        'arabic'         => 'العربي',
        'english'        => 'الإنجليزي',
        'general'        => ' ثقافة عامة',
        'math'           => 'الرياضيات',
        'arabic_desc'    => 'تعلم الحروف، الكلمات، القراءة، والكتابة بسهولة ومتعة.',
        'english_desc'   => 'تعلم الحروف، الكلمات، الجمل، والتواصل بثقة وسهولة.',
        'general_desc'   => ' تعلم الفصول، الطقس، الحيوانات، أيام الأسبوع، والفواكه والخضار بطريقة مرئية.',
        'math_desc'      => 'تعلم الأعداد، العد، العمليات الحسابية، وحل المسائل بطريقة ممتعة.',
        'open_section'   => 'الدخول للقسم',
        'games_title'    => 'قسم الألعاب',
        'games_desc'     => 'اختر اللعبة التي تريدها من البطاقات التالية.',
        'game_badge'     => '',
        'start_game'     => 'ابدأ اللعب',
        'no_games'       => 'لا توجد ألعاب متاحة حالياً',
        'announce'       => 'أنت الآن في الصفحة الرئيسية للطفل. اضغط تاب للتنقل وإنتر للدخول',
    ],
    'en' => [
        'page_title'     => 'Child Page',
        'logo_title'     => 'Platform',
        'logo_sub'       => 'Educational Platform for Kids',
        'home'           => 'Home',
        'educational'    => 'Educational',
        'games'          => 'Games',
        'chat'           => 'Chat',
        'settings'       => 'Account Settings',
        'logout'         => 'Logout',
        'hello'          => 'Hello',
        'hero_label'     => 'Child Dashboard',
        'hero_desc'      => 'Explore the educational sections, activities, and fun games inside the platform.',
        'start_learning' => 'Start Learning',
        'go_games'       => 'Go to Games',
        'education_title'=> 'Educational Sections',
        'education_desc' => 'Choose the section you want to open directly.',
        'edu_badge'      => 'Educational',
        'arabic'         => 'Arabic',
        'english'        => 'English',
        'general'        => 'General Education',
        'math'           => 'Mathematics',
        'arabic_desc'    => 'Learn Arabic letters, words, and reading in a fun way.',
        'english_desc'   => 'Start with basic English letters and words step by step.',
        'general_desc'   => 'Explore the world around you through fun and simple topics for children.',
        'math_desc'      => 'Learn numbers and basic math operations in a fun and interactive way.',
        'open_section'   => 'Open Section',
        'games_title'    => 'Games Section',
        'games_desc'     => 'Choose the game you want from the following cards.',
        'game_badge'     => 'Game',
        'start_game'     => 'Start Game',
        'no_games'       => 'No games available right now',
        'announce'       => 'You are now on the child home page. Press Tab to navigate and Enter to open',
    ],
];

$t = $text[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>

    <script src="../assets/js/language-sync.js"></script>
    <link rel="stylesheet" href="../assets/css/accessibility.css">
    <link rel="stylesheet" href="../assets/css/children-page.css?v=2">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-thin-straight/css/uicons-thin-straight.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">

<style>
.cards-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;align-items:stretch;}
.content-card{display:flex;flex-direction:column;height:100%;transition:0.3s ease;}
.content-card .card-image{width:100%;height:200px;overflow:hidden;border-radius:18px;}
.content-card .card-image img{width:100%;height:100%;object-fit:cover;display:block;}
.content-card .card-image .card-emoji-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#e0e7ff,#f3e8ff);border-radius:18px;}
.content-card h3{margin:14px 0 10px;}
.content-card p{flex-grow:1;line-height:1.8;}
.education-theme .cards-grid{grid-template-columns:repeat(4,1fr);}
.games-theme .cards-grid{grid-template-columns:repeat(3,1fr);}
@media(max-width:1200px){.cards-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:700px){.cards-grid{grid-template-columns:repeat(2,1fr);gap:12px;}}
@media(max-width:1000px){.games-theme .cards-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.games-theme .cards-grid{grid-template-columns:repeat(2,1fr);}}
.hero-section,.content-section,.content-card,.hero-video-card{transition:0.35s ease;}
.sign-helper-text{width:100%;text-align:center;font-family:"arial",Arial,sans-serif;font-size:15px;font-weight:900;color:#21425f;margin-bottom:8px;line-height:1.5;}
.games-empty{grid-column:1/-1;text-align:center;padding:40px 20px;color:#9ca3af;font-size:18px;font-weight:700;}
.nav-video-circle{width:70px;height:70px;overflow:hidden!important;}
.nav-video-circle video{width:100%!important;height:100%!important;object-fit:cover!important;}
@media(max-width:768px){
.children-dash-inner{flex-wrap:nowrap;align-items:center;justify-content:space-between;gap:6px;padding:8px 12px;}
.nav-video-circle{width:52px!important;height:52px!important;}
.profile-hello{display:none!important;}
.hero-section .hero-content{flex-direction:column;text-align:center;gap:20px;padding:24px 16px;}
.education-theme .cards-grid,.games-theme .cards-grid,.cards-grid{grid-template-columns:repeat(2,1fr);gap:14px;}
.content-card .card-image{height:150px;}
}
@media(max-width:480px){
.nav-video-circle{width:44px!important;height:44px!important;}
.education-theme .cards-grid,.games-theme .cards-grid,.cards-grid{grid-template-columns:1fr;gap:12px;}
.profile-hello{display:none!important;}
}
.chat-unread-badge{position:absolute;top:-4px;left:-4px;background:#e53935;color:#fff;font-size:11px;font-weight:900;padding:2px 6px;border-radius:999px;min-width:20px;text-align:center;line-height:1.4;box-shadow:0 2px 6px rgba(0,0,0,0.2);}

/* ✅ إصلاح فتح قائمة البروفايل */
.profile-wrap{
    position:relative;
}
.profile-menu{
    display:none;
    position:absolute;
    top:calc(100% + 12px);
    left:0;
    min-width:190px;
    background:#fff;
    border-radius:18px;
    padding:10px;
    box-shadow:0 12px 30px rgba(0,0,0,0.16);
    z-index:99999;
}
.profile-menu.show{
    display:block !important;
}
.profile-menu a{
    display:block;
    padding:12px 14px;
    border-radius:12px;
    text-decoration:none;
    color:#21425f;
    font-weight:900;
    font-family:"arial",Arial,sans-serif;
}
.profile-menu a:hover,
.profile-menu a:focus{
    background:#f1f7ff;
}

</style>
</head>

<body class="page-with-children-dash <?php echo ($lang === 'en') ? 'ltr' : ''; ?>">

<div class="floating-bg" aria-hidden="true">
    <span class="star star-1">★</span><span class="star star-2">★</span>
    <span class="star star-3">★</span><span class="star star-4">★</span>
    <span class="star star-5">★</span><span class="star star-6">★</span>
    <span class="star star-7">★</span><span class="star star-8">★</span>
    <span class="star star-9">★</span><span class="star star-10">★</span>
    <span class="star star-11">★</span><span class="star star-12">★</span>
    <span class="bubble bubble-1"></span><span class="bubble bubble-2"></span>
    <span class="bubble bubble-3"></span><span class="bubble bubble-4"></span>
    <span class="bubble bubble-5"></span><span class="bubble bubble-6"></span>
    <span class="bubble bubble-7"></span><span class="bubble bubble-8"></span>
</div>

<header class="children-dash">
<div class="children-dash-inner">

    <div class="dash-start">
        <a href="../index.php" class="logo-box" tabindex="0">
            <img src="../logo.png" alt="logo">
            <nav class="logo-text">
                <h1><?php echo $t['logo_title']; ?></h1>
                <p><?php echo $t['logo_sub']; ?></p>
            </nav>
        </a>
    </div>

    <nav class="dash-nav">
        <a href="../index.php" class="nav-video-item" aria-label="الرئيسية"
           onmouseenter="openDashboardSignVideo('../assets/videos/home-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الرئيسية')"
           onmouseleave="leaveDashboardSignVideo()"
           onfocus="openDashboardSignVideo('../assets/videos/home-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الرئيسية')"
           onblur="leaveDashboardSignVideo()"
           onclick="setSignVideo('../assets/videos/home-icon-sign.mp4?v=<?php echo $video_cache_version; ?>')">
            <span class="nav-video-circle">
                <video autoplay muted loop playsinline><source src="../assets/icons/home.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4"></video>
            </span>
            <span class="nav-tooltip">الرئيسية</span>
        </a>

        <a href="#education-section" class="nav-video-item" aria-label="القسم التعليمي"
           onmouseenter="openDashboardSignVideo('../assets/videos/subjects-icon-signs.mp4?v=<?php echo $video_cache_version; ?>', this, 'القسم التعليمي')"
           onmouseleave="leaveDashboardSignVideo()"
           onfocus="openDashboardSignVideo('../assets/videos/subjects-icon-signs.mp4?v=<?php echo $video_cache_version; ?>', this, 'القسم التعليمي')"
           onblur="leaveDashboardSignVideo()"
           onclick="sectionVideoOpened=true;currentSection='education';setSignVideo('../assets/videos/subjects-sign.mp4?v=<?php echo $video_cache_version; ?>');showSignText('اختر المادة الذي تريد تعلمها');">
            <span class="nav-video-circle">
                <video autoplay muted loop playsinline><source src="../assets/icons/learn.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4"></video>
            </span>
            <span class="nav-tooltip">القسم التعليمي</span>
        </a>

        <a href="#games-section" class="nav-video-item" aria-label="الألعاب"
           onmouseenter="openDashboardSignVideo('../assets/videos/games-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الألعاب')"
           onmouseleave="leaveDashboardSignVideo()"
           onfocus="openDashboardSignVideo('../assets/videos/games-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الألعاب')"
           onblur="leaveDashboardSignVideo()"
           onclick="sectionVideoOpened=true;currentSection='games';setSignVideo('../assets/videos/games-sign.mp4?v=<?php echo $video_cache_version; ?>');showSignText('اختر اللعبة الذي تريدها من البطاقات التالية');">
            <span class="nav-video-circle">
                <video autoplay muted loop playsinline><source src="../assets/icons/game.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4"></video>
            </span>
            <span class="nav-tooltip">الألعاب</span>
        </a>

        <?php
        $unread_nav    = 0;
        $child_id_nav  = intval($_SESSION['user_id']);
        $tbl_check     = $conn->query("SHOW TABLES LIKE 'chat_messages'");
        if ($tbl_check && $tbl_check->num_rows > 0) {
            $unread_res = $conn->prepare("SELECT COUNT(*) AS c FROM chat_messages WHERE child_id=? AND sender='admin' AND is_read=0");
            if ($unread_res) {
                $unread_res->bind_param('i', $child_id_nav);
                $unread_res->execute();
                $unread_row = $unread_res->get_result()->fetch_assoc();
                $unread_nav = intval($unread_row['c'] ?? 0);
            }
        }
        ?>
        <a href="../subjects/general/child-chat.php" class="nav-video-item" aria-label="<?php echo $t['chat']; ?>"
           onmouseenter="openDashboardSignVideo('../assets/videos/chat-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, '<?php echo $t['chat']; ?>')"
           onmouseleave="leaveDashboardSignVideo()"
           onfocus="openDashboardSignVideo('../assets/videos/chat-icon-sign.mp4?v=<?php echo $video_cache_version; ?>', this, '<?php echo $t['chat']; ?>')"
           onblur="leaveDashboardSignVideo()"
           onclick="setSignVideo('../assets/videos/chat-icon-sign.mp4?v=<?php echo $video_cache_version; ?>')" tabindex="0">
            <span class="nav-video-circle" style="position:relative;">
                <video autoplay muted loop playsinline><source src="../assets/icons/chat.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4"></video>
                <?php if ($unread_nav > 0): ?>
                <span class="chat-unread-badge"><?php echo $unread_nav; ?></span>
                <?php endif; ?>
            </span>
            <span class="nav-tooltip"><?php echo $t['chat']; ?></span>
        </a>
    </nav>

    <div class="dash-end">
        <div class="profile-wrap">
            <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
                <span class="profile-hello"><?php echo $t['hello'] . ' ' . htmlspecialchars($child_display_name); ?></span>
                <div class="profile-video-box">
                    <video autoplay muted loop playsinline><source src="../assets/icons/profile.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4"></video>
                </div>
            </button>
            <div class="profile-menu" id="profileMenu">
                <a href="../auth/account_settings.php"
                   onmouseenter="openDashboardSignVideo('../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'إعدادات الحساب')"
                   onmouseleave="leaveDashboardSignVideo()">
                    <?php echo $t['settings']; ?>
                </a>
                <a href="logout.php"
                   onmouseenter="openDashboardSignVideo('../assets/videos/logout-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'تسجيل الخروج')"
                   onmouseleave="leaveDashboardSignVideo()">
                    <?php echo $t['logout']; ?>
                </a>
            </div>
        </div>
    </div>

</div>
</header>

<main class="children-page">

    <!-- ── هيرو ── -->
    <section id="home-section" class="hero-section"
        data-sign-video="../assets/videos/children-home-sign.mp4?v=<?php echo $video_cache_version; ?>"
        data-sign-text="استكشف الأقسام التعليمية والأنشطة والألعاب الممتعة داخل المنصة">
        <div class="hero-content">
            <div class="children-hero-text">
                <span class="hero-label" tabindex="0"><?php echo $t['hero_label']; ?></span>
                <h1 tabindex="0"><?php echo $t['hello'] . ' ' . htmlspecialchars($child_display_name); ?></h1>
                <p tabindex="0"><?php echo $t['hero_desc']; ?></p>
                <div class="hero-actions">
                    <a href="#education-section" class="hero-btn primary" tabindex="0"
                       onclick="setSignVideo('../assets/videos/subjects-sign.mp4?v=<?php echo $video_cache_version; ?>')">
                        <?php echo $t['start_learning']; ?>
                    </a>
                    <a href="#games-section" class="hero-btn secondary" tabindex="0"
                       onclick="setSignVideo('../assets/videos/games-sign.mp4?v=<?php echo $video_cache_version; ?>)">
                        <?php echo $t['go_games']; ?>
                    </a>
                </div>
            </div>
            <div class="hero-video-card">
                <video autoplay muted loop playsinline tabindex="0">
                    <source src="../assets/icons/slide.mp4?v=<?php echo $video_cache_version; ?>" type="video/mp4">
                </video>
            </div>
        </div>
    </section>

    <!-- ── الأقسام التعليمية ── -->
    <section id="education-section" class="content-section education-theme"
        data-sign-video="../assets/videos/subjects-sign.mp4?v=<?php echo $video_cache_version; ?>"
        data-sign-text="اختر المادة الذي تريد تعلمها">
        <div class="section-head">
            <h2 tabindex="0"><?php echo $t['education_title']; ?></h2>
            <p tabindex="0"><?php echo $t['education_desc']; ?></p>
        </div>
        <div class="cards-grid">
            <a href="../subjects/arabic/arabic.php" class="content-card" tabindex="0"
               onmouseenter="openSignVideo('../assets/videos/arabic-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'اللغة العربية')"
               onfocus="openSignVideo('../assets/videos/arabic-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'اللغة العربية')">
                <div class="card-image"><img src="../assets/images/arabic-section.png" alt=""></div>
                <span class="edu-badge"><?php echo $t['edu_badge']; ?></span>
                <h3><?php echo $t['arabic']; ?></h3>
                <p><?php echo $t['arabic_desc']; ?></p>
            </a>
            <a href="../subjects/english/english.php" class="content-card" tabindex="0"
               onmouseenter="openSignVideo('../assets/videos/english-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'اللغة الإنجليزية')"
               onfocus="openSignVideo('../assets/videos/english-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'اللغة الإنجليزية')">
                <div class="card-image"><img src="../assets/images/english-section.png" alt=""></div>
                <span class="edu-badge"><?php echo $t['edu_badge']; ?></span>
                <h3><?php echo $t['english']; ?></h3>
                <p><?php echo $t['english_desc']; ?></p>
            </a>
            <a href="../subjects/math/math.php" class="content-card" tabindex="0"
               onmouseenter="openSignVideo('../assets/videos/math-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الرياضيات')"
               onfocus="openSignVideo('../assets/videos/math-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الرياضيات')">
                <div class="card-image"><img src="../assets/images/math-section.png" alt=""></div>
                <span class="edu-badge"><?php echo $t['edu_badge']; ?></span>
                <h3><?php echo $t['math']; ?></h3>
                <p><?php echo $t['math_desc']; ?></p>
            </a>
            <a href="../subjects/general/general.php" class="content-card" tabindex="0"
               onmouseenter="openSignVideo('../assets/videos/general-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الثقافة العامة')"
               onfocus="openSignVideo('../assets/videos/general-sign.mp4?v=<?php echo $video_cache_version; ?>', this, 'الثقافة العامة')">
                <div class="card-image"><img src="../assets/images/qeneral-section.png" alt=""></div>
                <span class="edu-badge"><?php echo $t['edu_badge']; ?></span>
                <h3><?php echo $t['general']; ?></h3>
                <p><?php echo $t['general_desc']; ?></p>
            </a>
        </div>
    </section>

    <!-- ── قسم الألعاب ── -->
    <section id="games-section" class="content-section games-theme"
        data-sign-video="../assets/videos/games-sign.mp4?v=<?php echo $video_cache_version; ?>"
        data-sign-text="اختر اللعبة الذي تريدها من البطاقات التالية">

        <div class="section-head">
            <h2 tabindex="0"><?php echo $t['games_title']; ?></h2>
            <p tabindex="0"><?php echo $t['games_desc']; ?></p>
        </div>

        <div class="cards-grid">
            <?php if (empty($db_games)): ?>
                <p class="games-empty"><?php echo $t['no_games']; ?></p>
            <?php else: ?>
                <?php foreach ($db_games as $g):
                    $gameName = ($lang === 'en' && !empty($g['title_en'])) ? $g['title_en'] : $g['title'];
                    $gameDesc = ($lang === 'en' && !empty($g['description_en'])) ? $g['description_en'] : ($g['description'] ?? '');
                    $signVid  = $signVideoMap[$g['game_type']] ?? '../assets/videos/games-sign.mp4?v=' . $video_cache_version;
                    $emojiMap = ['memory'=>'🃏','cups'=>'🏆','chase'=>'🐭','colors'=>'🌈','numbers'=>'🔢'];
                    $emoji    = $emojiMap[$g['game_type']] ?? '🎮';

                    $gameUrl = trim($g['url'] ?? '');
                    if (
                        $gameUrl !== '' &&
                        !preg_match('/^https?:\/\//i', $gameUrl) &&
                        !str_starts_with($gameUrl, '/') &&
                        !str_starts_with($gameUrl, '../') &&
                        !str_starts_with($gameUrl, './')
                    ) {
                        $gameUrl = '../' . ltrim($gameUrl, '/');
                    }
                ?>
                <!-- =====================================================
                     ✅ التراك: onclick يرسل game_id لـ track.php
                     ===================================================== -->
                <a href="<?php echo htmlspecialchars($gameUrl); ?>"
                   class="content-card"
                   tabindex="0"
                   onclick="trackAndGo(event, 'game', <?php echo intval($g['id']); ?>, '<?php echo addslashes($gameUrl); ?>')"
                   onmouseenter="openSignVideo('<?php echo htmlspecialchars($signVid); ?>', this, '<?php echo htmlspecialchars($gameName); ?>')"
                   onfocus="openSignVideo('<?php echo htmlspecialchars($signVid); ?>', this, '<?php echo htmlspecialchars($gameName); ?>')">

                    <div class="card-image">
    <?php if (!empty($g['thumbnail'])): ?>
        <?php
        $thumbSrc = $g['thumbnail'];
        if (!preg_match('/^https?:\/\//i', $thumbSrc) && !str_starts_with($thumbSrc, '/')) {
            $thumbSrc = '../' . $thumbSrc;
        }
        ?>
        <img src="<?php echo htmlspecialchars($thumbSrc); ?>" alt="<?php echo htmlspecialchars($gameName); ?>">
                        <?php else: ?>
                            <div class="card-emoji-placeholder"><?php echo $emoji; ?></div>
                        <?php endif; ?>
                    </div>

                    <span class="game-badge"><?php echo $t['game_badge']; ?></span>
                    <h3><?php echo htmlspecialchars($gameName); ?></h3>
                    <p><?php echo htmlspecialchars($gameDesc); ?></p>
                    <span class="card-link"><?php echo $t['start_game']; ?></span>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php
$sign_base  = "../";
$sign_video = "../assets/videos/children-home-sign.mp4?v=" . $video_cache_version;
include "../components/sign-language.php";
?>

<script src="../assets/js/script.js"></script>
<script>
/* =====================================================
   ✅ دالة التراك — تُرسل game_id لـ track.php ثم تنتقل
   ===================================================== */
function trackAndGo(e, type, id, url) {
    e.preventDefault();

    fetch('track.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'type=' + encodeURIComponent(type) + '&id=' + id
    }).finally(function () {
        window.location.href = url;
    });
}

function showSignText(text) {
    const box = document.getElementById("signVideoBox");
    if (!box) return;

    let textBox = document.getElementById("signHelperText");

    if (!textBox) {
        textBox = document.createElement("div");
        textBox.id = "signHelperText";
        textBox.className = "sign-helper-text";
        box.prepend(textBox);
    }

    textBox.textContent = text || "";
    textBox.style.display = (text && text.trim() !== "") ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", function () {
    const profileWrap = document.querySelector(".profile-wrap");
    const profileBtn  = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");

    if (profileBtn && profileMenu && profileWrap) {
        profileBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            profileWrap.classList.toggle("active");
            profileMenu.classList.toggle("show");
        });

        profileMenu.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        document.addEventListener("click", function () {
            profileWrap.classList.remove("active");
            profileMenu.classList.remove("show");
        });
    }
});

document.addEventListener("scroll", function () {
    if (!document.body.classList.contains("sign-open")) return;

    const sections = document.querySelectorAll("section[data-sign-video]");
    let current = null;

    sections.forEach(section => {
        const rect = section.getBoundingClientRect();

        if (rect.top <= 150 && rect.bottom >= 150) {
            current = section;
        }
    });

    if (current) {
        const vid = current.getAttribute("data-sign-video");
        const txt = current.getAttribute("data-sign-text");

        setSignVideo(vid);
        showSignText(txt);
    } else {
        setSignVideo("../assets/videos/children-home-sign.mp4?v=<?php echo $video_cache_version; ?>");
        showSignText("<?php echo $t['announce']; ?>");
    }
});
</script>

<script>
(function () {
  if (!('ontouchstart' in window)) return;
  var pending = null;
  document.addEventListener('touchend', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signVideoBox, #signToggleBtn')) return;
    var target = e.target.closest('a, button, input, select, textarea, [onclick], [onmouseenter]') || e.target;
    if (!target || target === document.body) { pending = null; return; }
    if (pending !== target) {
      e.preventDefault();
      e.stopImmediatePropagation();
      pending = target;
      target.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
    } else { pending = null; }
  }, { passive: false });
  document.addEventListener('touchstart', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signVideoBox, #signToggleBtn')) return;
    var target = e.target.closest('a, button, input, select, textarea, [onclick], [onmouseenter]') || e.target;
    if (target !== pending) pending = null;
  }, { passive: true });
})();
</script>
</body>
</html>
