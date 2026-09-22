<?php

/* =========================================================
   منع كاش الصفحة والمتصفح
   ========================================================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../../config/session_child.php';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
$lang = $_SESSION['lang'] ?? 'ar';

require_once __DIR__ . '/../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');


/* =========================================================
   رقم جديد لكسر كاش الفيديوهات عند فتح الصفحة
   ========================================================= */
$video_cache_version = time();


/* =========================================================
   دالة إضافة رقم كاش للفيديو
   ========================================================= */
function videoCacheUrl(string $url): string
{
    global $video_cache_version;

    if ($url === '') {
        return $url;
    }

    // إزالة أي v قديم حتى لا يصبح الرابط:
    // file.mp4?v=123&v=456
    $url = preg_replace('/([?&])v=[^&]*/', '', $url);
    $url = preg_replace('/[?&]+$/', '', $url);

    $separator = (strpos($url, '?') !== false) ? '&' : '?';

    return $url . $separator . 'v=' . $video_cache_version;
}


/* =========================================================
   تأكيد وجود الجدول
   ========================================================= */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    poster_image VARCHAR(255) DEFAULT NULL,
    sign_video VARCHAR(255) DEFAULT NULL,
    story_link VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");


/* =========================================================
   التأكد من وجود كل الأعمدة
   ========================================================= */
foreach ([
    'title'        => "ALTER TABLE stories ADD COLUMN title VARCHAR(255) NULL",
    'poster_image' => "ALTER TABLE stories ADD COLUMN poster_image VARCHAR(255) NULL",
    'sign_video'   => "ALTER TABLE stories ADD COLUMN sign_video VARCHAR(255) NULL",
    'story_link'   => "ALTER TABLE stories ADD COLUMN story_link VARCHAR(255) NULL",
    'sort_order'   => "ALTER TABLE stories ADD COLUMN sort_order INT DEFAULT 0",
    'cover_image'  => "ALTER TABLE stories ADD COLUMN cover_image VARCHAR(255) NULL",
    'cover_video'  => "ALTER TABLE stories ADD COLUMN cover_video VARCHAR(255) NULL",
    'cover_title'  => "ALTER TABLE stories ADD COLUMN cover_title VARCHAR(500) NULL",
] as $col => $alterSql) {

    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM stories LIKE '$col'");

    if ($colCheck && mysqli_num_rows($colCheck) == 0) {
        mysqli_query($conn, $alterSql);
    }
}


/* =========================================================
   تعبئة القصص الافتراضية مرة واحدة لو الجدول فاضي
   ========================================================= */
$storyCountRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM stories");
$storyCountRow = $storyCountRes
    ? mysqli_fetch_assoc($storyCountRes)
    : ['c' => 1];

if (intval($storyCountRow['c']) === 0) {

    $defaultStories = [
        [
            'رحلتي إلى المدرسة',
            'images/general/stories/images/poster/story1.png',
            'assets/videos/story1-sign.mp4',
            'story1.php',
            1
        ],
        [
            'إطعام الحيوانات',
            'images/general/stories/images/poster/story2.png',
            'assets/videos/story2-sign.mp4',
            'story2.php',
            2
        ],
        [
            'زراعة البذور',
            'images/general/stories/images/poster/story3.png',
            'assets/videos/story3-sign.mp4',
            'story3.php',
            3
        ],
        [
            'أنا أحب الرسم',
            'images/general/stories/images/poster/story4.png',
            'assets/videos/story4-sign.mp4',
            'story4.php',
            4
        ],
    ];

    $seedStmt = mysqli_prepare(
        $conn,
        "INSERT INTO stories
        (title, poster_image, sign_video, story_link, sort_order)
        VALUES (?,?,?,?,?)"
    );

    foreach ($defaultStories as $st) {

        mysqli_stmt_bind_param(
            $seedStmt,
            "ssssi",
            $st[0],
            $st[1],
            $st[2],
            $st[3],
            $st[4]
        );

        mysqli_stmt_execute($seedStmt);
    }
}


/* =========================================================
   جلب القصص
   ========================================================= */
$storiesRes = mysqli_query(
    $conn,
    "SELECT * FROM stories ORDER BY sort_order ASC, id ASC"
);

$stories = [];

if ($storiesRes) {
    while ($r = mysqli_fetch_assoc($storiesRes)) {
        $stories[] = $r;
    }
}


/* =========================================================
   فيديوهات الصفحة مع كسر الكاش
   ========================================================= */

$headerChildrenVideo = videoCacheUrl('../../assets/icons/children.mp4');
$headerGeneralVideo  = videoCacheUrl('../../assets/icons/general.mp4');
$profileVideo        = videoCacheUrl('../../assets/icons/profile.mp4');

$dashboardHomeVideo = videoCacheUrl('../../assets/videos/home-icon-sign.mp4');
$dashboardGeneralVideo = videoCacheUrl('../../assets/videos/general-sign.mp4');

$heroIntroVideo = videoCacheUrl('../../assets/videos/stories-intro.mp4');
$sectionVideo   = videoCacheUrl('../../assets/videos/stories-choose-section.mp4');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

<title>القصص</title>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap"
    rel="stylesheet"
>

<style>

*{margin:0;padding:0;box-sizing:border-box;}

body{
    font-family:"Cairo",sans-serif;
    background:#eef5ff;
    color:#24415f;
    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}

img,video{
    display:block;
    max-width:100%;
}

.page-shell{
    width:96%;
    max-width:1600px;
    margin:14px auto 0;
    background:#e6f0ff;
    padding:14px 14px 0;
}

.children-dash{
    background:#cfe7ff;
    border-radius:30px;
    padding:14px 22px;
    box-shadow:0 10px 24px rgba(44,111,170,.14);
}

.children-dash-inner{
    position:relative;
    min-height:78px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.dash-start,
.dash-end{
    display:flex;
    align-items:center;
    z-index:2;
}

.dash-center{
    position:absolute;
    left:50%;
    top:50%;
    transform:translate(-50%,-50%);
    z-index:1;
}

.logo-box{
    display:flex;
    align-items:center;
    background:none;
    padding:0;
    box-shadow:none;
}

.logo-box img{
    width:95px;
    height:auto;
    object-fit:contain;
}

.dash-nav{
    display:flex;
    align-items:center;
    gap:14px;
}

.circle-icon{
    width:82px;
    height:82px;
    border-radius:50%;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:visible;
    box-shadow:0 6px 16px rgba(0,0,0,.08);
    transition:.25s ease;
    position:relative;
    flex-shrink:0;
}

.circle-icon:hover{
    transform:translateY(-4px);
}

.circle-icon video,
.circle-icon img{
    width:90%;
    height:90%;
    object-fit:cover;
    border-radius:50%;
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
}

.circle-icon:hover .nav-text{
    opacity:1;
    transform:translateX(-50%) translateY(0);
}

.profile-wrap{
    position:relative;
}

.profile-btn{
    border:none;
    background:#fff0b8;
    border-radius:999px;
    padding:7px 14px 7px 10px;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    font-family:"Cairo",sans-serif;
    font-weight:800;
    color:#1f5f9a;
    min-width:185px;
}

.profile-video-box{
    width:50px;
    height:50px;
    border-radius:50%;
    overflow:hidden;
    background:#fff;
    flex-shrink:0;
}

.profile-video-box video{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-menu{
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    min-width:190px;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 14px 28px rgba(0,0,0,.12);
    display:none;
    z-index:1000;
}

.profile-menu.show{
    display:block;
}

.profile-menu a{
    display:block;
    padding:12px 14px;
    font-weight:800;
    color:#4c5b72;
}

.profile-menu a:hover{
    background:#f3f9ff;
}

.page-wrap{
    width:82%;
    max-width:1320px;
    margin:30px auto 34px;
    transition:transform .25s ease;
}

.hero-box{
    background:linear-gradient(135deg,#fff3d9,#e7f4ff);
    border-radius:38px;
    padding:38px 44px;
    box-shadow:0 10px 24px rgba(44,111,170,.12);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:44px;
    min-height:350px;
    position:relative;
    overflow:hidden;
}

.hero-box::before{
    content:"";
    position:absolute;
    width:320px;
    height:320px;
    border-radius:50%;
    background:rgba(255,255,255,.20);
    top:-140px;
    right:-120px;
}

.hero-box::after{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    border-radius:50%;
    background:rgba(255,255,255,.14);
    bottom:-100px;
    left:-90px;
}

.hero-text{
    flex:1;
    position:relative;
    z-index:2;
}

.small-badge{
    display:inline-flex;
    background:#ffe6a8;
    color:#8a5b00;
    border-radius:999px;
    padding:8px 18px;
    font-size:15px;
    font-weight:900;
    margin-bottom:16px;
}

.hero-text h2{
    font-size:50px;
    color:#1f5f9a;
    font-weight:900;
    margin-bottom:14px;
}

.hero-text p{
    font-size:18px;
    line-height:1.9;
    color:#6c7d91;
    font-weight:700;
    max-width:620px;
    margin-bottom:22px;
}

.start-btn{
    display:inline-flex;
    background:#f5c84c;
    color:#624000;
    font-size:17px;
    font-weight:900;
    padding:12px 28px;
    border-radius:999px;
    box-shadow:0 10px 22px rgba(245,200,76,.28);
    transition:.25s ease;
}

.start-btn:hover{
    transform:translateY(-3px);
}

.hero-sign-video{
    width:245px;
    height:315px;
    border-radius:30px;
    overflow:hidden;
    background:#d8e8f8;
    flex-shrink:0;
    box-shadow:0 10px 24px rgba(0,0,0,.10);
    border:7px solid rgba(255,255,255,.75);
    position:relative;
    z-index:2;
}

.hero-sign-video video{
    width:100%;
    height:100%;
    object-fit:contain;
    background:#000;
}

.play-btn{
    position:absolute;
    inset:0;
    margin:auto;
    width:74px;
    height:74px;
    border-radius:50%;
    border:4px solid white;
    background:rgba(255,91,120,.92);
    color:#fff;
    font-size:34px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:5;
    box-shadow:0 10px 24px rgba(0,0,0,.25);
}

.hero-sign-video.playing .play-btn{
    display:none!important;
}

.stories-section{
    background:linear-gradient(135deg,#dff1ff,#fff4dc);
    border-radius:42px;
    padding:42px 30px 38px;
    box-shadow:0 12px 30px rgba(44,111,170,.12);
    margin-top:34px;
    text-align:center;
    position:relative;
    overflow:hidden;
}

.stories-section::before{
    content:"";
    position:absolute;
    width:320px;
    height:320px;
    border-radius:50%;
    background:rgba(255,255,255,.20);
    top:-120px;
    right:-120px;
    pointer-events:none;
}

.stories-section::after{
    content:"";
    position:absolute;
    width:240px;
    height:240px;
    border-radius:50%;
    background:rgba(255,255,255,.14);
    bottom:-100px;
    left:-100px;
    pointer-events:none;
}

.stories-section h3{
    font-size:48px;
    color:#1f5f9a;
    font-weight:900;
    margin-bottom:8px;
    position:relative;
    z-index:2;
}

.stories-section>p{
    font-size:19px;
    color:#6c7d91;
    font-weight:800;
    margin-bottom:30px;
    position:relative;
    z-index:2;
}

.stories-grid{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:repeat(4,minmax(240px,1fr));
    gap:30px;
    max-width:1350px;
    margin:0 auto;
    align-items:stretch;
}

.story-card{
    position:relative;
    border-radius:42px;
    padding:18px 18px 26px;
    min-height:405px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    overflow:hidden;
    transition:.32s ease;
    border:5px solid rgba(255,255,255,.85);
    box-shadow:
        0 14px 34px rgba(44,111,170,.12),
        0 6px 14px rgba(0,0,0,.05);
    cursor:pointer;
}

.story-card:hover{
    transform:translateY(-10px) scale(1.025);
    box-shadow:
        0 24px 48px rgba(44,111,170,.20),
        0 12px 24px rgba(0,0,0,.10);
}

.story-card:nth-child(4n+1){
    background:linear-gradient(135deg,#ffe4ef,#ffd2e3);
}

.story-card:nth-child(4n+2){
    background:linear-gradient(135deg,#dff5ff,#cdeeff);
}

.story-card:nth-child(4n+3){
    background:linear-gradient(135deg,#fff1cd,#ffe3a7);
}

.story-card:nth-child(4n){
    background:linear-gradient(135deg,#e8ffd9,#cdf4bb);
}

.story-img{
    width:100%;
    height:255px;
    object-fit:cover;
    border-radius:32px;
    margin-bottom:20px;
    box-shadow:
        0 12px 26px rgba(0,0,0,.14),
        inset 0 0 0 5px rgba(255,255,255,.78);
    transition:.32s ease;
    position:relative;
    z-index:2;
}

.story-card:hover .story-img{
    transform:scale(1.04);
}

.story-card h4{
    font-size:31px;
    color:#1f5f9a;
    font-weight:900;
    line-height:1.5;
    text-align:center;
    margin-top:2px;
    margin-bottom:8px;
    position:relative;
    z-index:2;
}

.no-stories{
    position:relative;
    z-index:2;
    background:rgba(255,255,255,.7);
    border-radius:24px;
    padding:30px;
    font-size:22px;
    font-weight:900;
    color:#1f5f9a;
    max-width:600px;
    margin:0 auto;
}

.sign-floating{
    position:fixed!important;
    left:22px!important;
    bottom:22px!important;
    z-index:2147483647!important;
}

.sign-circle{
    width:75px;
    height:75px;
    border-radius:50%;
    border:4px solid white;
    background:#32d27b;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    padding:8px;
    box-shadow:0 10px 25px rgba(0,0,0,.25);
    position:relative;
    transition:.25s ease;
}

.sign-circle.active{
    box-shadow:
        0 0 0 8px rgba(50,210,123,.25),
        0 0 28px rgba(50,210,123,.9),
        0 10px 25px rgba(0,0,0,.25);
    transform:scale(1.08);
}

.sign-circle img{
    width:100%;
    height:100%;
    object-fit:contain;
}

.sign-video-box{
    position:absolute;
    left:0;
    bottom:95px;
    width:260px;
    background:white;
    border-radius:24px;
    padding:8px;
    display:none;
    box-shadow:0 18px 45px rgba(0,0,0,.28);
}

.sign-video-box.active{
    display:block;
}

.sign-helper-text{
    width:100%;
    text-align:center;
    font-size:16px;
    font-weight:900;
    color:#21425f;
    background:#eafff2;
    border:2px solid #92efba;
    border-radius:16px;
    padding:8px 10px;
    margin-bottom:8px;
    display:none;
}

.sign-video-wrap{
    position:relative;
    width:100%;
    height:260px;
}

.sign-video-box video{
    width:100%;
    height:100%;
    border-radius:18px;
    object-fit:contain;
    background:#000;
}

.close-sign{
    position:absolute;
    top:-20px;
    right:-20px;
    width:55px;
    height:55px;
    border-radius:50%;
    border:4px solid white;
    background:#ff3b3b;
    color:white;
    font-size:26px;
    font-weight:900;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.sign-play-btn{
    position:absolute;
    inset:0;
    margin:auto;
    width:74px;
    height:74px;
    border-radius:50%;
    border:4px solid white;
    background:rgba(255,91,120,.92);
    color:white;
    font-size:34px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
}

.sign-play-btn.hide{
    display:none!important;
}

body.sign-open .page-wrap{
    transform:translateX(120px);
}

body.sign-open .hero-box,
body.sign-open .stories-section{
    max-width:calc(100% - 120px);
}

@media(max-width:1150px){

    .stories-grid{
        grid-template-columns:repeat(2,minmax(250px,1fr));
    }

}

@media(max-width:700px){

    .stories-grid{
        grid-template-columns:1fr;
    }

    .story-card{
        min-height:auto;
    }

    .story-img{
        height:240px;
    }

    .hero-box{
        flex-direction:column;
        text-align:center;
    }

    .children-dash-inner{
        flex-direction:column;
        gap:14px;
    }

    .dash-center{
        position:static;
        transform:none;
    }

    .page-wrap{
        width:92%;
    }

}

@media(max-width:768px){

    html,body{
        height:auto!important;
        overflow-x:hidden!important;
        overflow-y:auto!important;
    }

    .page-shell{
        width:100%!important;
        margin:0!important;
        padding:6px 6px 0!important;
        position:sticky!important;
        top:0!important;
        z-index:999!important;
    }

    .children-dash{
        border-radius:16px!important;
        padding:8px 10px!important;
    }

    .children-dash-inner{
        flex-direction:row!important;
        flex-wrap:nowrap!important;
        align-items:center!important;
        justify-content:space-between!important;
        min-height:unset!important;
        gap:6px!important;
        padding:0!important;
    }

    .dash-center{
        position:static!important;
        transform:none!important;
    }

    .dash-start,
    .dash-center,
    .dash-end{
        flex-shrink:0;
    }

    .logo-box img{
        width:54px!important;
    }

    .circle-icon{
        width:52px!important;
        height:52px!important;
    }

    .profile-btn{
        min-width:unset!important;
        padding:5px 8px 5px 6px!important;
        gap:6px!important;
        font-size:13px!important;
    }

    .profile-video-box{
        width:34px!important;
        height:34px!important;
    }

    .page-wrap{
        width:96%!important;
        margin:16px auto 24px!important;
    }

    .hero-box{
        flex-direction:column!important;
        padding:22px 18px!important;
        gap:18px!important;
        min-height:unset!important;
    }

    .hero-text h2{
        font-size:30px!important;
    }

    .hero-text p{
        font-size:15px!important;
    }

    .hero-sign-video{
        width:100%!important;
        height:200px!important;
    }

    .hero-sign-video video{
        object-fit:contain!important;
    }

    .stories-section{
        padding:24px 14px 20px!important;
        border-radius:24px!important;
    }

    .stories-section h3{
        font-size:28px!important;
    }

    .stories-section>p{
        font-size:15px!important;
    }

    .stories-grid{
        grid-template-columns:repeat(2,1fr)!important;
        gap:14px!important;
    }

    .story-card{
        min-height:unset!important;
        padding:12px 10px 16px!important;
        border-radius:22px!important;
    }

    .story-img{
        height:150px!important;
        border-radius:18px!important;
    }

    .story-card h4{
        font-size:18px!important;
    }

    .sign-floating{
        left:12px!important;
        bottom:12px!important;
    }

    .sign-circle{
        width:52px!important;
        height:52px!important;
    }

    .sign-video-box{
        width:220px!important;
        bottom:72px!important;
    }

    body.sign-open .page-wrap{
        transform:none!important;
    }

    body.sign-open .hero-box,
    body.sign-open .stories-section{
        max-width:100%!important;
    }

}

</style>

</head>

<body>


<!-- =========================================================
     الهيدر
     ========================================================= -->

<div class="page-shell">

    <header class="children-dash">

        <div class="children-dash-inner">

            <div class="dash-start">

                <a href="../../index.php" class="logo-box">

                    <img
                        src="../../logo.png"
                        alt="logo"
                    >

                </a>

            </div>


            <div class="dash-center">

                <nav class="dash-nav">


                    <!-- صفحة الطفل -->

                    <a
                        href="../../auth/children.php"
                        class="circle-icon"
                        data-dashboard-video="<?php echo htmlspecialchars($dashboardHomeVideo); ?>"
                        data-dashboard-text="صفحة الطفل"
                    >

                        <video
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="auto"
                        >
                            <source
                                src="<?php echo htmlspecialchars($headerChildrenVideo); ?>"
                                type="video/mp4"
                            >
                        </video>

                        <span class="nav-text">
                            صفحة الطفل
                        </span>

                    </a>


                    <!-- الثقافة العامة -->

                    <a
                        href="../../subjects/general/general.php"
                        class="circle-icon"
                        data-dashboard-video="<?php echo htmlspecialchars($dashboardGeneralVideo); ?>"
                        data-dashboard-text="الثقافة العامة"
                    >

                        <video
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="auto"
                        >
                            <source
                                src="<?php echo htmlspecialchars($headerGeneralVideo); ?>"
                                type="video/mp4"
                            >
                        </video>

                        <span class="nav-text">
                            الثقافة العامة
                        </span>

                    </a>

                </nav>

            </div>


            <div class="dash-end">

                <div class="profile-wrap">

                    <button
                        type="button"
                        class="profile-btn"
                        id="profileBtn"
                    >

                        <div class="profile-video-box">

                            <video
                                autoplay
                                muted
                                loop
                                playsinline
                                preload="auto"
                            >

                                <source
                                    src="<?php echo htmlspecialchars($profileVideo); ?>"
                                    type="video/mp4"
                                >

                            </video>

                        </div>

                        <span>
                            مرحباً
                            <?php echo htmlspecialchars($child_display_name); ?>
                        </span>

                    </button>


                    <div
                        class="profile-menu"
                        id="profileMenu"
                    >

                        <a href="../../auth/account_settings.php">
                            إعدادات الحساب
                        </a>

                        <a href="../../auth/logout.php">
                            تسجيل الخروج
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </header>

</div>


<!-- =========================================================
     المحتوى
     ========================================================= -->

<main class="page-wrap">


    <!-- الهيرو -->

    <section
        class="hero-box"
        id="heroBox"
    >

        <div class="hero-text">

            <span class="small-badge">
                هيا نقرأ
            </span>

            <h2>
                قسم القصص
            </h2>

            <p>
                استمتع بقراءة القصص المصورة وتعلم الكلمات الجديدة بطريقة سهلة وممتعة.
            </p>

            <a
                href="#stories"
                class="start-btn"
            >
                ابدأ الآن
            </a>

        </div>


        <div
            class="hero-sign-video"
            id="heroSignBox"
        >

            <video
                id="heroIntroVideo"
                muted
                playsinline
                preload="auto"
            >

                <source
                    src="<?php echo htmlspecialchars($heroIntroVideo); ?>"
                    type="video/mp4"
                >

            </video>

            <div
                class="play-btn"
                id="playBtn"
            >
                ▶
            </div>

        </div>

    </section>


    <!-- =====================================================
         القصص
         ===================================================== -->

    <section
        class="stories-section"
        id="stories"
        data-section-video="<?php echo htmlspecialchars($sectionVideo); ?>"
        data-section-text="اختر القصة"
    >

        <h3>
            اختر القصة
        </h3>

        <p>
            اضغط على بطاقة القصة لتبدأ القراءة
        </p>


        <div class="stories-grid">

            <?php if (!empty($stories)): ?>

                <?php foreach ($stories as $story): ?>

                    <?php

                    $cardTitle = trim(
                        (string)($story['title'] ?? '')
                    );

                    $cardPoster = trim(
                        (string)($story['poster_image'] ?? '')
                    );

                    $cardLink = trim(
                        (string)($story['story_link'] ?? '')
                    );

                    $cardSign = trim(
                        (string)($story['sign_video'] ?? '')
                    );

                    $storyId = intval(
                        $story['id']
                    );


                    $posterSrc =
                        $cardPoster !== ''
                        ? '../../' . ltrim($cardPoster, '/')
                        : '';


                    $cardHref =
                        $cardLink !== ''
                        ? $cardLink
                        : '#';


                    $signSrc =
                        $cardSign !== ''
                        ? videoCacheUrl('../../' . ltrim($cardSign, '/'))
                        : '';

                    ?>

                    <div
                        class="story-card"
                        data-story-id="<?php echo $storyId; ?>"
                        data-href="<?php echo htmlspecialchars($cardHref); ?>"
                        data-card-video="<?php echo htmlspecialchars($signSrc); ?>"
                        data-card-text="<?php echo htmlspecialchars($cardTitle); ?>"
                    >

                        <img
                            src="<?php echo htmlspecialchars($posterSrc); ?>"
                            class="story-img"
                            alt="<?php echo htmlspecialchars($cardTitle); ?>"
                        >

                        <h4>
                            <?php echo htmlspecialchars($cardTitle); ?>
                        </h4>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-stories">
                    لا يوجد قصص متاحة حالياً
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<!-- =========================================================
     زر لغة الإشارة
     ========================================================= -->

<div class="sign-floating">

    <button
        class="sign-circle"
        id="signToggleBtn"
        type="button"
        title="لغة الإشارة"
    >

        <img
            src="../../assets/icons/sign-icon.png"
            alt="لغة الإشارة"
        >

    </button>


    <div
        class="sign-video-box"
        id="signVideoBox"
    >

        <button
            class="close-sign"
            id="closeSignBtn"
            type="button"
        >
            ×
        </button>


        <div
            id="signHelperText"
            class="sign-helper-text"
        ></div>


        <div class="sign-video-wrap">

            <video
                id="signHelpVideo"
                playsinline
                muted
                preload="auto"
            ></video>

            <button
                class="sign-play-btn"
                id="signPlayBtn"
                type="button"
            >
                ▶
            </button>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       دالة كسر الكاش للفيديو
       ===================================================== */

    function freshVideoPath(videoPath) {

        if (!videoPath) {
            return videoPath;
        }

        let cleanPath = String(videoPath);

        /*
         * إزالة v القديم
         */
        cleanPath = cleanPath.replace(
            /([?&])v=[^&]*/g,
            ""
        );

        /*
         * تنظيف ? أو & الموجودة في النهاية
         */
        cleanPath = cleanPath.replace(
            /[?&]+$/,
            ""
        );

        const separator =
            cleanPath.includes("?")
                ? "&"
                : "?";

        return cleanPath +
            separator +
            "v=" +
            Date.now();
    }


    /* =====================================================
       تحديث كل فيديوهات الصفحة عند تحميلها
       ===================================================== */

    document.querySelectorAll("video").forEach(function (video) {

        /*
         * إذا كان الفيديو عنده src مباشر
         */
        if (video.getAttribute("src")) {

            const newSrc =
                freshVideoPath(
                    video.getAttribute("src")
                );

            video.setAttribute(
                "src",
                newSrc
            );

            video.load();
        }


        /*
         * إذا كان الفيديو يعتمد على source
         */
        const source =
            video.querySelector("source");

        if (source && source.getAttribute("src")) {

            const newSrc =
                freshVideoPath(
                    source.getAttribute("src")
                );

            source.setAttribute(
                "src",
                newSrc
            );

            video.load();
        }

    });


    /* =====================================================
       بروفايل
       ===================================================== */

    const profileBtn =
        document.getElementById("profileBtn");

    const profileMenu =
        document.getElementById("profileMenu");


    if (profileBtn && profileMenu) {

        profileBtn.addEventListener(
            "click",
            function (e) {

                e.stopPropagation();

                profileMenu.classList.toggle("show");

            }
        );


        document.addEventListener(
            "click",
            function (e) {

                if (
                    !profileBtn.contains(e.target) &&
                    !profileMenu.contains(e.target)
                ) {

                    profileMenu.classList.remove("show");

                }

            }
        );

    }


    /* =====================================================
       التراك + الانتقال عند ضغط بطاقة القصة
       ===================================================== */

    document
        .querySelectorAll(".story-card[data-story-id]")
        .forEach(function (card) {

            card.addEventListener(
                "click",
                function () {

                    var storyId =
                        this.dataset.storyId;

                    var href =
                        this.dataset.href;


                    if (!href || href === '#') {
                        return;
                    }


                    fetch(
                        '../../auth/track.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },

                            body:
                                'type=story&id=' +
                                encodeURIComponent(storyId)
                        }
                    )
                    .finally(
                        function () {

                            window.location.href =
                                href;

                        }
                    );

                }
            );

        });


    /* =====================================================
       لغة الإشارة
       ===================================================== */

    const signToggleBtn =
        document.getElementById("signToggleBtn");

    const signVideoBox =
        document.getElementById("signVideoBox");

    const closeSignBtn =
        document.getElementById("closeSignBtn");

    const signHelpVideo =
        document.getElementById("signHelpVideo");

    const signHelperText =
        document.getElementById("signHelperText");

    const signPlayBtn =
        document.getElementById("signPlayBtn");

    const heroBox =
        document.getElementById("heroBox");

    const heroIntroVideo =
        document.getElementById("heroIntroVideo");

    const heroSignBox =
        document.getElementById("heroSignBox");

    const playBtn =
        document.getElementById("playBtn");

    const storiesSection =
        document.getElementById("stories");


    let helpOpen = false;

    let lastHelpVideo = "";

    let currentHelpType = "";

    let chooseSectionOpened = false;


    function isHelpOpen() {
        return helpOpen;
    }


    function showHelpText(txt) {

        if (!signHelperText) {
            return;
        }

        signHelperText.textContent =
            txt || "";

        signHelperText.style.display =
            txt
                ? "block"
                : "none";
    }


    function showSignPlay() {

        if (signPlayBtn) {

            signPlayBtn.classList.remove(
                "hide"
            );

        }

    }


    function hideSignPlay() {

        if (signPlayBtn) {

            signPlayBtn.classList.add(
                "hide"
            );

        }

    }


    /* =====================================================
       تحميل فيديو المساعدة بدون كاش
       ===================================================== */

    function loadHelpVideo(
        vp,
        txt,
        tp
    ) {

        if (!isHelpOpen() || !vp) {
            return;
        }


        signVideoBox.classList.add(
            "active"
        );


        showHelpText(txt);


        currentHelpType =
            tp || "";


        /*
         * مهم:
         * نعمل رابط جديد دائماً للفيديو
         */
        const freshPath =
            freshVideoPath(vp);


        signHelpVideo.pause();

        signHelpVideo.removeAttribute(
            "src"
        );

        signHelpVideo.load();


        signHelpVideo.src =
            freshPath;

        signHelpVideo.load();


        lastHelpVideo =
            vp;


        signHelpVideo.currentTime = 0;

        showSignPlay();

    }


    /* =====================================================
       تشغيل فيديو المساعدة بدون كاش
       ===================================================== */

    function playHelpVideo(
        vp,
        txt,
        tp
    ) {

        if (!isHelpOpen() || !vp) {
            return;
        }


        signVideoBox.classList.add(
            "active"
        );


        showHelpText(txt);


        currentHelpType =
            tp || "";


        /*
         * دائماً نستخدم رابط جديد
         * حتى لو نفس اسم الملف
         */
        const freshPath =
            freshVideoPath(vp);


        signHelpVideo.pause();

        signHelpVideo.removeAttribute(
            "src"
        );

        signHelpVideo.load();


        signHelpVideo.src =
            freshPath;

        signHelpVideo.load();


        lastHelpVideo =
            vp;


        signHelpVideo.currentTime = 0;

        signHelpVideo.muted = true;


        signHelpVideo
            .play()
            .then(function () {

                hideSignPlay();

                if (
                    currentHelpType === "section"
                ) {

                    chooseSectionOpened =
                        true;

                }

            })
            .catch(function () {

                showSignPlay();

            });

    }


    /* =====================================================
       إيقاف فيديو المساعدة
       ===================================================== */

    function stopHelpVideo(
        keepBox
    ) {

        signHelpVideo.pause();


        if (!keepBox) {

            signHelpVideo.currentTime =
                0;

            signVideoBox.classList.remove(
                "active"
            );

            showHelpText("");

            currentHelpType = "";

        }


        showSignPlay();

    }


    /* =====================================================
       فتح صندوق لغة الإشارة
       ===================================================== */

    function openHelpBox() {

        fetch(
            '../../api/log-help.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded'
                },

                body:
                    'page=' +
                    encodeURIComponent(
                        window.location.pathname
                    )
            }
        ).catch(function () {});


        helpOpen = true;


        signToggleBtn.classList.add(
            "active"
        );


        /*
         * لا نضيف sign-open
         * حتى لا تتحرك البطاقات
         */

        signVideoBox.classList.remove(
            "active"
        );


        showHelpText("");


        signHelpVideo.pause();


        signHelpVideo.removeAttribute(
            "src"
        );


        signHelpVideo.load();


        lastHelpVideo = "";

        currentHelpType = "";

        showSignPlay();

    }


    /* =====================================================
       إغلاق صندوق لغة الإشارة
       ===================================================== */

    function closeHelpBox() {

        helpOpen = false;


        signToggleBtn.classList.remove(
            "active"
        );


        document.body.classList.remove(
            "sign-open"
        );


        signVideoBox.classList.remove(
            "active"
        );


        showHelpText("");


        signHelpVideo.pause();


        signHelpVideo.currentTime =
            0;


        signHelpVideo.removeAttribute(
            "src"
        );


        signHelpVideo.load();


        lastHelpVideo = "";

        currentHelpType = "";

        chooseSectionOpened = false;


        showSignPlay();

    }


    signToggleBtn.addEventListener(
        "click",
        function () {

            if (helpOpen) {

                closeHelpBox();

            } else {

                openHelpBox();

            }

        }
    );


    closeSignBtn.addEventListener(
        "click",
        closeHelpBox
    );


    /* =====================================================
       زر تشغيل فيديو المساعدة
       ===================================================== */

    signPlayBtn.addEventListener(
        "click",
        function () {

            if (!signHelpVideo.src) {
                return;
            }


            signHelpVideo
                .play()
                .then(function () {

                    hideSignPlay();

                })
                .catch(function () {

                    showSignPlay();

                });

        }
    );


    signHelpVideo.addEventListener(
        "play",
        function () {

            hideSignPlay();


            if (
                currentHelpType === "section"
            ) {

                chooseSectionOpened =
                    true;

            }

        }
    );


    signHelpVideo.addEventListener(
        "ended",
        function () {

            showSignPlay();

        }
    );


    signHelpVideo.addEventListener(
        "pause",
        function () {

            if (!signHelpVideo.ended) {

                showSignPlay();

            }

        }
    );


    /* =====================================================
       فيديوهات الهيدر عند المرور
       ===================================================== */

    document
        .querySelectorAll("[data-dashboard-video]")
        .forEach(function (item) {


            item.addEventListener(
                "mouseenter",
                function () {

                    playHelpVideo(
                        item.dataset.dashboardVideo,
                        item.dataset.dashboardText || "",
                        "dashboard"
                    );

                }
            );


            item.addEventListener(
                "focus",
                function () {

                    playHelpVideo(
                        item.dataset.dashboardVideo,
                        item.dataset.dashboardText || "",
                        "dashboard"
                    );

                }
            );


            item.addEventListener(
                "mouseleave",
                function () {

                    if (isHelpOpen()) {

                        stopHelpVideo(false);

                    }

                }
            );


            item.addEventListener(
                "blur",
                function () {

                    if (isHelpOpen()) {

                        stopHelpVideo(false);

                    }

                }
            );

        });


    /* =====================================================
       فيديو قسم اختر القصة
       ===================================================== */

    function checkStoriesSectionVisible() {

        if (
            !storiesSection ||
            !isHelpOpen() ||
            chooseSectionOpened
        ) {

            return;

        }


        const rect =
            storiesSection.getBoundingClientRect();


        const wh =
            window.innerHeight ||
            document.documentElement.clientHeight;


        if (
            rect.top < wh * 0.75 &&
            rect.bottom > wh * 0.25
        ) {

            loadHelpVideo(
                storiesSection.dataset.sectionVideo,
                storiesSection.dataset.sectionText || "اختر القصة",
                "section"
            );

        }

    }


    window.addEventListener(
        "scroll",
        checkStoriesSectionVisible
    );


    window.addEventListener(
        "resize",
        checkStoriesSectionVisible
    );


    if (storiesSection) {

        storiesSection.addEventListener(
            "mouseenter",
            function () {

                if (!chooseSectionOpened) {

                    loadHelpVideo(
                        storiesSection.dataset.sectionVideo,
                        storiesSection.dataset.sectionText || "اختر القصة",
                        "section"
                    );

                }

            }
        );

    }


    /* =====================================================
       فيديوهات بطاقات القصص
       ===================================================== */

    document
        .querySelectorAll("[data-card-video]")
        .forEach(function (card) {


            card.addEventListener(
                "mouseenter",
                function () {

                    if (!chooseSectionOpened) {
                        return;
                    }


                    playHelpVideo(
                        card.dataset.cardVideo,
                        card.dataset.cardText || "",
                        "card"
                    );

                }
            );


            card.addEventListener(
                "focus",
                function () {

                    if (!chooseSectionOpened) {
                        return;
                    }


                    playHelpVideo(
                        card.dataset.cardVideo,
                        card.dataset.cardText || "",
                        "card"
                    );

                }
            );


            card.addEventListener(
                "mouseleave",
                function () {

                    if (
                        isHelpOpen() &&
                        chooseSectionOpened
                    ) {

                        stopHelpVideo(false);

                    }

                }
            );


            card.addEventListener(
                "blur",
                function () {

                    if (
                        isHelpOpen() &&
                        chooseSectionOpened
                    ) {

                        stopHelpVideo(false);

                    }

                }
            );

        });


    /* =====================================================
       فيديو الهيرو
       ===================================================== */

    function hideHeroPlay() {

        if (playBtn) {
            playBtn.style.display = "none";
        }

        if (heroSignBox) {
            heroSignBox.classList.add(
                "playing"
            );
        }

    }


    function showHeroPlay() {

        if (playBtn) {
            playBtn.style.display = "flex";
        }

        if (heroSignBox) {
            heroSignBox.classList.remove(
                "playing"
            );
        }

    }


    /*
     * تحديث فيديو الهيرو برابط جديد
     * قبل التشغيل
     */
    function refreshHeroVideoSource() {

        if (!heroIntroVideo) {
            return;
        }


        const source =
            heroIntroVideo.querySelector("source");


        if (!source) {
            return;
        }


        const originalSrc =
            source.getAttribute("src");


        if (!originalSrc) {
            return;
        }


        const freshSrc =
            freshVideoPath(
                originalSrc
            );


        source.setAttribute(
            "src",
            freshSrc
        );


        heroIntroVideo.load();

    }


    function playHeroVideo() {

        if (!heroIntroVideo) {
            return;
        }


        /*
         * كل تشغيل يأخذ رابط كاش جديد
         */
        refreshHeroVideoSource();


        heroIntroVideo
            .play()
            .then(function () {

                hideHeroPlay();

            })
            .catch(function () {

                showHeroPlay();

            });

    }


    function stopHeroVideo(reset) {

        if (!heroIntroVideo) {
            return;
        }


        heroIntroVideo.pause();


        if (reset) {

            heroIntroVideo.currentTime =
                0;

        }


        showHeroPlay();

    }


    if (
        heroBox &&
        heroIntroVideo
    ) {

        heroBox.addEventListener(
            "mouseenter",
            function () {

                if (isHelpOpen()) {

                    playHeroVideo();

                }

            }
        );


        heroBox.addEventListener(
            "mouseleave",
            function () {

                if (isHelpOpen()) {

                    stopHeroVideo(true);

                }

            }
        );

    }


    if (
        playBtn &&
        heroIntroVideo
    ) {

        playBtn.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();


                if (heroIntroVideo.paused) {

                    playHeroVideo();

                } else {

                    stopHeroVideo(false);

                }

            }
        );

    }


    if (heroIntroVideo) {

        heroIntroVideo.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();


                if (heroIntroVideo.paused) {

                    playHeroVideo();

                } else {

                    stopHeroVideo(false);

                }

            }
        );


        heroIntroVideo.addEventListener(
            "ended",
            function () {

                stopHeroVideo(true);

            }
        );

    }

});

</script>

</body>
</html>