<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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


/* =========================================================
   دالة مهمة: منع تخزين الفيديو القديم في الكاش
   ========================================================= */
function versionedVideoUrl(string $url): string {
    if ($url === '') {
        return '';
    }

    // إزالة أي version قديم إذا كان موجودًا
    $cleanUrl = preg_replace('/[?&]v=[^&]*/', '', $url);

    // تحويل مسار الموقع إلى مسار ملف حقيقي
    $filePath = __DIR__ . '/' . ltrim($cleanUrl, '/');

    $modifiedTime = @filemtime($filePath);

    if ($modifiedTime !== false) {
        $separator = (strpos($cleanUrl, '?') !== false) ? '&' : '?';
        return $cleanUrl . $separator . 'v=' . $modifiedTime;
    }

    return $cleanUrl;
}


/* =========================================================
   إنشاء جدول الأقسام
   ========================================================= */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS general_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(20) DEFAULT '★',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$custom_sections = [];

$res = mysqli_query(
    $conn,
    "SELECT * FROM general_sections ORDER BY sort_order ASC, id ASC"
);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $custom_sections[] = $row;
    }
}


/* =========================================================
   جلب الأيقونات المخصصة للثقافة العامة
   ========================================================= */
$gcCustomIcons = [];

$_stmt = mysqli_prepare(
    $conn,
    "SELECT type_name, icon_file FROM type_icons WHERE subject='الثقافة العامة'"
);

if ($_stmt) {
    mysqli_stmt_execute($_stmt);
    $_res = mysqli_stmt_get_result($_stmt);

    while ($_r = mysqli_fetch_assoc($_res)) {
        $gcCustomIcons[$_r['type_name']] = '../../' . $_r['icon_file'];
    }
}


/* =========================================================
   دالة الأيقونة
   ========================================================= */
function childCircleIcon(
    string $type,
    array $customIcons,
    string $defaultSrc
): string {

    if (isset($customIcons[$type])) {

        $url = $customIcons[$type];
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (in_array($ext, ['mp4', 'webm'])) {

            $versionedUrl = versionedVideoUrl($url);

            return '<video autoplay muted loop playsinline class="circle-video">
                        <source src="' . htmlspecialchars($versionedUrl) . '" type="video/mp4">
                    </video>';
        }

        return '<img src="' . htmlspecialchars($url) . '" class="circle-video" alt="">';
    }

    $versionedDefault = versionedVideoUrl($defaultSrc);

    return '<video autoplay muted loop playsinline class="circle-video">
                <source src="' . htmlspecialchars($versionedDefault) . '" type="video/mp4">
            </video>';
}


$custom_colors = [
    '#ffd6f5',
    '#d4f5e4',
    '#fff3b0',
    '#ffe4d0',
    '#d0eaff',
    '#f0d4ff',
    '#d4ffea',
    '#ffecd0',
    '#c8f7f0',
    '#ffe0b2'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

<title>الثقافة العامة</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/arabic-grid.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;}

:root{
--purple-1:#fcf7ff;
--purple-2:#f4e8ff;
--purple-3:#ead8ff;
--purple-4:#cda7ff;
--purple-5:#7b4bb7;
--purple-6:#5e3691;
--pink-1:#ffe4ec;
--green-1:#e4f8ea;
--yellow-1:#fff6d6;
--yellow-2:#ffe9a9;
--text-dark:#4b2f66;
--text-soft:#7d718d;
--white:#ffffff;
--shadow:0 10px 28px rgba(123,75,183,0.12);
--shadow-soft:0 8px 20px rgba(123,75,183,0.08);
--shadow-hover:0 18px 35px rgba(123,75,183,0.16);
--radius-xl:32px;
--radius-lg:24px;
--radius-md:18px;
--radius-full:999px;
}

body{
font-family:"Cairo",sans-serif;
background:
radial-gradient(circle at top right,#fff5d8 0%,transparent 22%),
radial-gradient(circle at top left,#f4e8ff 0%,transparent 26%),
linear-gradient(180deg,#f8f0ff 0%,#fffdfd 100%);
color:var(--text-dark);
min-height:100vh;
overflow-x:hidden;
}

a{text-decoration:none;color:inherit;}
img,video{display:block;max-width:100%;}

.children-dash{
width:100%;
background:linear-gradient(135deg,#f9f2ff,#ead8ff);
padding:14px 24px;
box-shadow:0 6px 18px rgba(0,0,0,0.05);
position:sticky;
top:0;
z-index:1000;
}

.children-dash-inner{
max-width:1240px;
margin:auto;
display:flex;
align-items:center;
justify-content:space-between;
gap:16px;
flex-wrap:wrap;
}

.dash-start,.dash-end{
display:flex;
align-items:center;
gap:16px;
}

.logo-box{
display:flex;
align-items:center;
gap:12px;
text-decoration:none;
transition:.25s ease;
}

.logo-box:hover{transform:translateY(-2px);}

.logo-box img{
width:90px;
height:auto;
object-fit:contain;
}

.dash-nav{
display:flex;
align-items:center;
gap:12px;
}

.circle-icon{
width:80px;
height:80px;
border-radius:50%;
background:linear-gradient(135deg,#ffffff,#f5ebff);
display:flex;
align-items:center;
justify-content:center;
box-shadow:0 6px 15px rgba(0,0,0,0.10);
transition:.25s ease;
overflow:visible!important;
flex-shrink:0;
position:relative;
}

.circle-icon:hover{
transform:translateY(-3px) scale(1.06);
}

.nav-icon-video,
.circle-video,
.circle-icon video,
.circle-icon img{
width:100%;
height:100%;
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
box-shadow:0 8px 18px rgba(255,94,143,0.30);
}

.circle-icon:hover .nav-text{
opacity:1;
transform:translateX(-50%) translateY(0);
}

.profile-wrap{position:relative;}

.profile-btn{
display:flex;
align-items:center;
gap:10px;
border:none;
background:linear-gradient(135deg,var(--yellow-1),var(--yellow-2));
padding:8px 10px 8px 16px;
border-radius:var(--radius-full);
cursor:pointer;
font-weight:800;
font-family:"Cairo",sans-serif;
box-shadow:0 8px 18px rgba(255,191,61,0.18);
transition:.25s ease;
color:#7a5b09;
}

.profile-btn:hover{transform:translateY(-2px);}

.profile-hello{
font-size:15px;
white-space:nowrap;
}

.profile-video-box{
width:44px;
height:44px;
border-radius:50%;
overflow:hidden;
background:#fff;
display:flex;
align-items:center;
justify-content:center;
box-shadow:0 4px 10px rgba(0,0,0,0.08);
flex-shrink:0;
}

.profile-video-box video{
width:100%;
height:100%;
object-fit:cover;
}

.profile-menu{
position:absolute;
top:62px;
left:0;
min-width:210px;
background:#fff;
border-radius:18px;
box-shadow:0 14px 28px rgba(0,0,0,0.12);
overflow:hidden;
display:none;
z-index:1200;
border:1px solid rgba(123,75,183,0.08);
}

.profile-menu.show{display:block;}

.profile-menu a{
display:block;
padding:13px 16px;
text-decoration:none;
color:#4b3566;
font-weight:700;
transition:.2s ease;
}

.profile-menu a:hover{background:#f7efff;}

.page-wrap{
max-width:1150px;
margin:32px auto;
padding:0 20px 40px;
}

.hero-box{
background:linear-gradient(135deg,#fff8ff 0%,#f4e7ff 52%,#fff7e8 100%);
border-radius:var(--radius-xl);
padding:34px 24px;
box-shadow:var(--shadow);
text-align:center;
margin-bottom:28px;
position:relative;
overflow:hidden;
min-height:350px;
display:grid;
grid-template-columns:1fr 310px;
align-items:center;
gap:24px;
}

.hero-box::before{
content:"";
position:absolute;
width:180px;
height:180px;
border-radius:50%;
background:rgba(255,220,235,0.45);
top:-55px;
right:-35px;
}

.hero-box::after{
content:"";
position:absolute;
width:140px;
height:140px;
border-radius:50%;
background:rgba(214,239,255,0.45);
bottom:-35px;
left:-25px;
}

.hero-box>*{
position:relative;
z-index:1;
}

.hero-text{text-align:right;}

.small-badge{
display:inline-block;
background:#fff;
color:#7b4bb7;
font-weight:900;
padding:8px 18px;
border-radius:999px;
margin-bottom:12px;
box-shadow:0 8px 18px rgba(123,75,183,0.10);
}

.hero-text h2{
font-size:34px;
color:var(--text-dark);
margin-bottom:10px;
font-weight:900;
line-height:1.5;
}

.hero-text p{
color:var(--text-soft);
font-size:18px;
line-height:1.9;
font-weight:700;
margin-bottom:18px;
}

.start-btn{
display:inline-block;
background:linear-gradient(135deg,#8d5bd4,#6f3bb0);
color:#fff;
padding:12px 28px;
border-radius:999px;
font-weight:900;
box-shadow:0 10px 20px rgba(111,59,176,.22);
transition:.22s ease;
}

.start-btn:hover{transform:translateY(-3px);}

.hero-sign-video{
width:300px;
height:300px;
border-radius:30px;
background:#fff;
padding:10px;
box-shadow:0 14px 28px rgba(123,75,183,0.14);
position:relative;
overflow:hidden;
}

.hero-sign-video video{
width:100%;
height:100%;
object-fit:contain;
border-radius:22px;
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

.choice-section{
background:rgba(243,255,222,0.92);
border-radius:30px;
padding:28px 22px 30px;
box-shadow:var(--shadow);
margin-top:28px;
}

.section-title{
text-align:center;
margin-bottom:10px;
}

.section-title h3{
font-size:30px;
font-weight:900;
color:#21425f;
}

.section-title p{
font-size:17px;
font-weight:700;
color:#5f6776;
}

.choice-grid{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:24px;
margin-top:18px;
}

.choice-card{
text-decoration:none;
color:inherit;
border-radius:30px;
padding:30px 20px;
text-align:center;
box-shadow:0 12px 24px rgba(70,130,180,0.14);
transition:transform .22s ease,box-shadow .22s ease;
border:3px solid rgba(255,255,255,0.65);
min-height:240px;
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
position:relative;
overflow:hidden;
}

.choice-card::before{
content:"";
position:absolute;
width:100px;
height:100px;
border-radius:50%;
top:-25px;
left:-20px;
background:rgba(255,255,255,0.28);
}

.choice-card::after{
content:"";
position:absolute;
width:60px;
height:60px;
border-radius:50%;
bottom:-10px;
right:-8px;
background:rgba(255,255,255,0.22);
}

.choice-card:hover{
transform:translateY(-7px) scale(1.02);
box-shadow:0 18px 30px rgba(70,130,180,0.20);
}

.choice-circle{
width:110px;
height:110px;
margin-bottom:15px;
border-radius:50%;
background:#fff;
display:flex;
align-items:center;
justify-content:center;
overflow:hidden;
font-size:42px;
font-weight:bold;
position:relative;
z-index:1;
box-shadow:
0 8px 18px rgba(0,0,0,.08),
inset 0 0 0 5px rgba(255,255,255,.75);
}

.choice-circle .circle-video,
.choice-circle img{
width:100%;
height:100%;
object-fit:cover;
border-radius:50%;
}

.choice-card h4{
font-size:24px;
margin-bottom:8px;
color:var(--text-dark);
position:relative;
z-index:1;
font-weight:900;
}

.choice-card p{
font-size:16px;
color:#5f6776;
line-height:1.8;
position:relative;
z-index:1;
font-weight:700;
}

.choice-card:nth-child(1){background:#bfe6ff;}
.choice-card:nth-child(2){background:#ffe0b2;}
.choice-card:nth-child(3){background:#d1ffc4;}
.choice-card:nth-child(4){background:#ffd6e0;}
.choice-card:nth-child(5){background:#e0d4ff;}
.choice-card:nth-child(6){background:#fff3b0;}
.choice-card:nth-child(7){background:#c8f7f0;}

.sign-floating{
position:fixed!important;
left:22px!important;
bottom:22px!important;
z-index:2147483647!important;
display:block!important;
pointer-events:auto!important;
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
z-index:2147483647;
transition:.25s ease;
}

.sign-circle.active{
box-shadow:
0 0 0 8px rgba(50,210,123,.25),
0 0 28px rgba(50,210,123,.9),
0 10px 25px rgba(0,0,0,.25);
transform:scale(1.08);
}

.sign-circle:hover{transform:scale(1.06);}
.sign-circle.active:hover{transform:scale(1.08);}

.sign-circle img{
width:100%;
height:100%;
object-fit:contain;
display:block;
}

.sign-video-box{
position:absolute;
left:0;
bottom:95px;
width:260px;
height:auto;
background:white;
border-radius:24px;
padding:8px;
display:none;
box-shadow:0 18px 45px rgba(0,0,0,.28);
z-index:2147483647;
}

.sign-video-box.active{
display:block!important;
}

.sign-helper-text{
width:100%;
text-align:center;
font-family:Arial,sans-serif;
font-size:16px;
font-weight:900;
color:#21425f;
background:#eafff2;
border:2px solid #92efba;
border-radius:16px;
padding:8px 10px;
margin-bottom:8px;
line-height:1.5;
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
z-index:9999;
box-shadow:0 8px 20px rgba(0,0,0,0.25);
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
z-index:5;
box-shadow:0 10px 24px rgba(0,0,0,.25);
}

.sign-play-btn.hide{
display:none!important;
}

@media(max-width:992px){
.hero-box{
grid-template-columns:1fr;
text-align:center;
}

.hero-text{text-align:center;}

.hero-sign-video{margin:auto;}

.choice-grid{
grid-template-columns:repeat(2,1fr);
}
}

@media(max-width:768px){

.children-dash{
padding:0;
}

.children-dash-inner{
display:grid!important;
grid-template-columns:auto 1fr auto!important;
padding:8px 12px!important;
min-height:auto!important;
gap:8px!important;
align-items:center!important;
flex-wrap:nowrap!important;
}

.dash-start,.dash-end{
justify-content:center;
}

.logo-box img{
width:44px!important;
height:44px!important;
}

.dash-nav{
gap:12px!important;
flex-wrap:nowrap!important;
justify-content:center!important;
}

.circle-icon{
width:44px!important;
height:44px!important;
}

.nav-text{
display:none!important;
}

.profile-hello{
font-size:13px!important;
max-width:70px;
overflow:hidden;
text-overflow:ellipsis;
white-space:nowrap;
}

.profile-video-box{
width:40px!important;
height:40px!important;
}

.profile-menu{
left:0!important;
right:auto!important;
}

.hero-box{
padding:22px 16px!important;
border-radius:26px!important;
}

.hero-text h2{
font-size:24px!important;
}

.hero-sign-video{
width:160px!important;
height:160px!important;
}

.choice-grid{
grid-template-columns:repeat(2,1fr)!important;
gap:14px!important;
}

.choice-card{
min-height:180px!important;
padding:20px 12px!important;
border-radius:22px!important;
}

.choice-circle{
width:80px!important;
height:80px!important;
}

.choice-card h4{
font-size:18px!important;
}

.sign-floating{
left:12px!important;
bottom:12px!important;
}

.sign-circle{
width:52px!important;
height:52px!important;
padding:6px!important;
}

.sign-video-box{
width:200px!important;
bottom:68px!important;
}
}

@media(max-width:480px){

.choice-grid{
grid-template-columns:repeat(2,1fr)!important;
gap:10px!important;
}

.choice-card{
min-height:140px!important;
padding:14px 8px!important;
border-radius:18px!important;
}

.choice-circle{
width:60px!important;
height:60px!important;
}

.choice-name{
font-size:13px!important;
}

.page-wrap{
padding:0 10px 28px;
margin:12px auto;
}
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
   aria-label="<?php echo ($lang === 'ar') ? 'العودة إلى صفحة الطفل' : 'Back to child page'; ?>"
   data-dashboard-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/home-icon-sign.mp4')) ?>"
   data-dashboard-text="صفحة الطفل">

<video class="nav-icon-video" autoplay muted loop playsinline>
<source src="<?= htmlspecialchars(versionedVideoUrl('../../assets/icons/children.mp4')) ?>" type="video/mp4">
</video>

<span class="nav-text">صفحة الطفل</span>

</a>


<a href="../../subjects/general/general.php"
   class="circle-icon home-icon"
   tabindex="0"
   aria-label="الثقافة العامة"
   data-dashboard-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/general-word.mp4')) ?>"
   data-dashboard-text="الثقافة العامة">

<video autoplay muted loop playsinline class="circle-video">
<source src="<?= htmlspecialchars(versionedVideoUrl('../../assets/icons/general.mp4')) ?>" type="video/mp4">
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
<source src="<?= htmlspecialchars(versionedVideoUrl('../../assets/icons/profile.mp4')) ?>" type="video/mp4">
</video>

</div>

</button>


<div class="profile-menu" id="profileMenu">

<a href="../../auth/account_settings.php"
   data-dashboard-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/settings-sign.mp4')) ?>"
   data-dashboard-text="إعدادات الحساب">
إعدادات الحساب
</a>

<a href="../../auth/logout.php"
   data-dashboard-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/logout-sign.mp4')) ?>"
   data-dashboard-text="تسجيل الخروج">
تسجيل الخروج
</a>

</div>

</div>

</div>

</header>


<main class="page-wrap">

<section class="hero-box" id="heroBox">

<div class="hero-text">

<span class="small-badge">هيا نتعلم</span>

<h2>قسم الثقافة العامة</h2>

<p>
تعلم مواضيع مفيدة وممتعة بطريقة بصرية مناسبة للأطفال
</p>

<a href="#sections" class="start-btn">
ابدأ الآن
</a>

</div>


<div class="hero-sign-video" id="heroSignBox">

<video id="heroIntroVideo" muted playsinline preload="auto">

<source
src="<?= htmlspecialchars(versionedVideoUrl('../../images/videos/general-intro.mp4')) ?>"
type="video/mp4">

</video>

<div class="play-btn" id="playBtn">▶</div>

</div>

</section>


<section class="choice-section"
         id="sections"
         data-section-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/general-choose-section.mp4')) ?>"
         data-section-text="اختر القسم">

<div class="section-title">

<h3>اختر القسم</h3>

<p>اضغط على أي قسم لتبدأ التعلم</p>

</div>


<div class="choice-grid">


<!-- الفصول الأربعة -->

<a href="seasons.php"
   class="choice-card track-general-link"
   data-section="الفصول الأربعة"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/seasons-sign.mp4')) ?>"
   data-card-text="الفصول الأربعة">

<div class="choice-circle">

<?= childCircleIcon(
'الفصول الأربعة',
$gcCustomIcons,
'../../assets/icons/season.mp4'
) ?>

</div>

<h4>الفصول الأربعة</h4>

<p>تعرف على فصول السنة</p>

</a>


<!-- الطقس -->

<a href="weather.php"
   class="choice-card track-general-link"
   data-section="الطقس"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/weather-sign.mp4')) ?>"
   data-card-text="الطقس">

<div class="choice-circle">

<?= childCircleIcon(
'الطقس',
$gcCustomIcons,
'../../assets/icons/weather.mp4'
) ?>

</div>

<h4>الطقس</h4>

<p>تعلم حالات الطقس</p>

</a>


<!-- أيام الأسبوع -->

<a href="days.php"
   class="choice-card track-general-link"
   data-section="أيام الأسبوع"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/days-sign.mp4')) ?>"
   data-card-text="أيام الأسبوع">

<div class="choice-circle">

<?= childCircleIcon(
'أيام الأسبوع',
$gcCustomIcons,
'../../assets/icons/days.mp4'
) ?>

</div>

<h4>أيام الأسبوع</h4>

<p>تعرف على الأيام</p>

</a>


<!-- أركان الإسلام -->

<a href="islamic.php"
   class="choice-card track-general-link"
   data-section="أركان الإسلام"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/islamic-sign.mp4')) ?>"
   data-card-text="أركان الإسلام">

<div class="choice-circle">

<?= childCircleIcon(
'أركان الإسلام',
$gcCustomIcons,
'../../assets/icons/islamic.mp4'
) ?>

</div>

<h4>أركان الإسلام</h4>

<p>تعلم الأركان</p>

</a>


<!-- فواكه وخضراوات -->

<a href="food.php"
   class="choice-card track-general-link"
   data-section="فواكه وخضراوات"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/food-sign.mp4')) ?>"
   data-card-text="فواكه وخضراوات">

<div class="choice-circle">

<?= childCircleIcon(
'فواكه وخضروات',
$gcCustomIcons,
'../../assets/icons/food.mp4'
) ?>

</div>

<h4>فواكه وخضراوات</h4>

<p>تعلم الطعام</p>

</a>


<!-- الحيوانات -->

<a href="animals.php"
   class="choice-card track-general-link"
   data-section="الحيوانات"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/animals-sign.mp4')) ?>"
   data-card-text="الحيوانات">

<div class="choice-circle">

<?= childCircleIcon(
'الحيوانات',
$gcCustomIcons,
'../../assets/icons/animals.mp4'
) ?>

</div>

<h4>الحيوانات</h4>

<p>تعرف على الحيوانات</p>

</a>


<!-- القصص -->

<a href="stories.php"
   class="choice-card"
   data-card-video="<?= htmlspecialchars(versionedVideoUrl('../../assets/videos/stories-sign.mp4')) ?>"
   data-card-text="قصص قصيرة">

<div class="choice-circle">

<?= childCircleIcon(
'القصص',
$gcCustomIcons,
'../../assets/icons/story.mp4'
) ?>

</div>

<h4>قصص قصيرة</h4>

<p>استمتع بالقصص</p>

</a>


<?php foreach ($custom_sections as $i => $cs):

    $bg = $custom_colors[$i % count($custom_colors)];

    $cs_icon = $cs['icon_file'] ?? '';
    $cs_sign = $cs['sign_video'] ?? '';
    $cs_desc = $cs['description'] ?? '';

    $cs_icon_url = $cs_icon ? '../../' . $cs_icon : '';
    $cs_sign_url = $cs_sign ? '../../' . $cs_sign : '';

    $cs_is_vid = $cs_icon &&
        strtolower(pathinfo($cs_icon, PATHINFO_EXTENSION)) === 'mp4';

    if ($cs_sign_url) {
        $cs_sign_url = versionedVideoUrl($cs_sign_url);
    }

    if ($cs_icon_url && $cs_is_vid) {
        $cs_icon_url = versionedVideoUrl($cs_icon_url);
    }

?>

<a href="custom-section.php?id=<?= $cs['id'] ?>"
   class="choice-card custom-section-card"
   style="background:<?= $bg ?>"
   data-card-video="<?= htmlspecialchars($cs_sign_url) ?>"
   data-card-text="<?= htmlspecialchars($cs['name']) ?>">

<div class="choice-circle">

<?php if ($cs_icon && $cs_is_vid): ?>

<video autoplay muted loop playsinline class="circle-video">

<source
src="<?= htmlspecialchars($cs_icon_url) ?>"
type="video/mp4">

</video>

<?php elseif ($cs_icon): ?>

<img
src="<?= htmlspecialchars($cs_icon_url) ?>"
style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
alt="">

<?php else: ?>

<span style="font-size:46px;line-height:1">
<?= htmlspecialchars($cs['icon']) ?>
</span>

<?php endif; ?>

</div>

<h4><?= htmlspecialchars($cs['name']) ?></h4>

<p>
<?= !empty($cs_desc)
    ? htmlspecialchars($cs_desc)
    : 'اضغط لتبدأ التعلم'
?>
</p>

</a>

<?php endforeach; ?>


</div>

</section>

</main>


<div class="sign-floating">

<button
class="sign-circle"
id="signToggleBtn"
type="button"
title="لغة الإشارة">

<img
src="../../assets/icons/sign-icon.png"
class="sign-icon"
alt="لغة الإشارة">

</button>


<div class="sign-video-box" id="signVideoBox">

<button
class="close-sign"
id="closeSignBtn"
type="button">
×
</button>


<div id="signHelperText" class="sign-helper-text"></div>


<div class="sign-video-wrap">

<video
id="signHelpVideo"
playsinline
muted
preload="auto">
</video>

<button
class="sign-play-btn"
id="signPlayBtn"
type="button">
▶
</button>

</div>

</div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");

  if (profileBtn && profileMenu) {

    profileBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      profileMenu.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {

      if (
        !profileBtn.contains(e.target) &&
        !profileMenu.contains(e.target)
      ) {
        profileMenu.classList.remove("show");
      }

    });
  }


  const signToggleBtn = document.getElementById("signToggleBtn");
  const signVideoBox = document.getElementById("signVideoBox");
  const closeSignBtn = document.getElementById("closeSignBtn");
  const signHelpVideo = document.getElementById("signHelpVideo");
  const signHelperText = document.getElementById("signHelperText");
  const signPlayBtn = document.getElementById("signPlayBtn");

  const heroBox = document.getElementById("heroBox");
  const heroIntroVideo = document.getElementById("heroIntroVideo");
  const heroSignBox = document.getElementById("heroSignBox");
  const playBtn = document.getElementById("playBtn");

  const choiceSection = document.getElementById("sections");


  let helpOpen = false;
  let lastHelpVideo = "";
  let currentHelpType = "";
  let chooseSectionOpened = false;


  function isHelpOpen() {
    return helpOpen;
  }


  function showHelpText(text) {

    if (!signHelperText) return;

    signHelperText.textContent = text || "";

    signHelperText.style.display =
      text ? "block" : "none";
  }


  function showSignPlay() {

    if (signPlayBtn) {
      signPlayBtn.classList.remove("hide");
    }

  }


  function hideSignPlay() {

    if (signPlayBtn) {
      signPlayBtn.classList.add("hide");
    }

  }


  function loadHelpVideo(videoPath, text, type) {

    if (!isHelpOpen() || !videoPath) return;

    signVideoBox.classList.add("active");

    showHelpText(text);

    currentHelpType = type || "";


    if (lastHelpVideo !== videoPath) {

      signHelpVideo.pause();

      signHelpVideo.removeAttribute("src");

      signHelpVideo.src = videoPath;

      signHelpVideo.load();

      lastHelpVideo = videoPath;
    }


    signHelpVideo.currentTime = 0;

    showSignPlay();
  }


  function playHelpVideo(videoPath, text, type) {

    if (!isHelpOpen() || !videoPath) return;

    signVideoBox.classList.add("active");

    showHelpText(text);

    currentHelpType = type || "";


    if (lastHelpVideo !== videoPath) {

      signHelpVideo.pause();

      signHelpVideo.removeAttribute("src");

      signHelpVideo.src = videoPath;

      signHelpVideo.load();

      lastHelpVideo = videoPath;
    }


    signHelpVideo.currentTime = 0;

    signHelpVideo.muted = true;


    signHelpVideo.play()
      .then(function () {

        hideSignPlay();

        if (currentHelpType === "section") {
          chooseSectionOpened = true;
        }

      })
      .catch(function () {

        showSignPlay();

      });

  }


  function stopHelpVideo(keepBox) {

    signHelpVideo.pause();

    if (!keepBox) {

      signHelpVideo.currentTime = 0;

      signVideoBox.classList.remove("active");

      showHelpText("");

      currentHelpType = "";
    }

    showSignPlay();
  }


  function openHelpBox() {

    fetch(
      '../../api/log-help.php',
      {
        method:'POST',
        headers:{
          'Content-Type':
          'application/x-www-form-urlencoded'
        },
        body:
        'page=' +
        encodeURIComponent(window.location.pathname)
      }
    ).catch(function(){});


    helpOpen = true;

    signToggleBtn.classList.add("active");

    document.body.classList.add("sign-open");

    signVideoBox.classList.remove("active");

    showHelpText("");

    signHelpVideo.pause();

    signHelpVideo.removeAttribute("src");

    signHelpVideo.load();

    lastHelpVideo = "";

    currentHelpType = "";

    showSignPlay();

    checkChoiceSectionVisible();
  }


  function closeHelpBox() {

    helpOpen = false;

    signToggleBtn.classList.remove("active");

    document.body.classList.remove("sign-open");

    signVideoBox.classList.remove("active");

    showHelpText("");

    signHelpVideo.pause();

    signHelpVideo.currentTime = 0;

    signHelpVideo.removeAttribute("src");

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


  signPlayBtn.addEventListener(
    "click",
    function () {

      if (!signHelpVideo.src) return;

      signHelpVideo.play()
        .then(function () {

          hideSignPlay();

          if (currentHelpType === "section") {
            chooseSectionOpened = true;
          }

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

      if (currentHelpType === "section") {
        chooseSectionOpened = true;
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


  function checkChoiceSectionVisible() {

    if (
      !choiceSection ||
      !isHelpOpen() ||
      chooseSectionOpened
    ) {
      return;
    }


    const rect =
      choiceSection.getBoundingClientRect();

    const windowHeight =
      window.innerHeight ||
      document.documentElement.clientHeight;


    if (
      rect.top < windowHeight * 0.75 &&
      rect.bottom > windowHeight * 0.25
    ) {

      loadHelpVideo(
        choiceSection.dataset.sectionVideo,
        choiceSection.dataset.sectionText ||
        "اختر القسم",
        "section"
      );

    }

  }


  window.addEventListener(
    "scroll",
    checkChoiceSectionVisible
  );


  window.addEventListener(
    "resize",
    checkChoiceSectionVisible
  );


  if (choiceSection) {

    choiceSection.addEventListener(
      "mouseenter",
      function () {

        if (!chooseSectionOpened) {

          loadHelpVideo(
            choiceSection.dataset.sectionVideo,
            choiceSection.dataset.sectionText ||
            "اختر القسم",
            "section"
          );

        }

      }
    );

  }


  document
    .querySelectorAll(
      "[data-card-video]:not(.custom-section-card)"
    )
    .forEach(function (card) {

      card.addEventListener(
        "mouseenter",
        function () {

          if (!chooseSectionOpened) return;

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

          if (!chooseSectionOpened) return;

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


  document
    .querySelectorAll(
      ".custom-section-card[data-card-video]"
    )
    .forEach(function (card) {

      card.addEventListener(
        "mouseenter",
        function () {

          if (
            !isHelpOpen() ||
            !card.dataset.cardVideo
          ) {
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

          if (
            !isHelpOpen() ||
            !card.dataset.cardVideo
          ) {
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

          if (isHelpOpen()) {
            stopHelpVideo(false);
          }

        }
      );


      card.addEventListener(
        "blur",
        function () {

          if (isHelpOpen()) {
            stopHelpVideo(false);
          }

        }
      );

    });


  function hideHeroPlay() {

    if (playBtn) {
      playBtn.style.display = "none";
    }

    if (heroSignBox) {
      heroSignBox.classList.add("playing");
    }

  }


  function showHeroPlay() {

    if (playBtn) {
      playBtn.style.display = "flex";
    }

    if (heroSignBox) {
      heroSignBox.classList.remove("playing");
    }

  }


  function playHeroVideo() {

    if (!heroIntroVideo) return;

    heroIntroVideo.play()
      .then(function () {

        hideHeroPlay();

      })
      .catch(function () {

        showHeroPlay();

      });

  }


  function stopHeroVideo(reset) {

    if (!heroIntroVideo) return;

    heroIntroVideo.pause();

    if (reset) {
      heroIntroVideo.currentTime = 0;
    }

    showHeroPlay();
  }


  if (heroBox && heroIntroVideo) {

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


  if (playBtn && heroIntroVideo) {

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


  document
    .querySelectorAll(
      'a.track-general-link[data-section]'
    )
    .forEach(function (link) {

      link.addEventListener(
        'click',
        function (e) {

          e.preventDefault();

          const href = link.href;

          fetch(
            '../../auth/track.php',
            {
              method: 'POST',
              credentials: 'include',
              headers: {
                'Content-Type':
                'application/x-www-form-urlencoded'
              },
              body:
              'type=section&id=' +
              encodeURIComponent(
                link.dataset.section
              )
            }
          ).finally(function () {

            window.location.href = href;

          });

        }
      );

    });

});

</script>

</body>
</html>