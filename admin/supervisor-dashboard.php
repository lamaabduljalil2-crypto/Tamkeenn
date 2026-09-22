<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

if (($_SESSION['admin_role'] ?? '') !== 'supervisor') {
    header('Location: dashboard.php'); exit;
}

$sup_id = intval($_SESSION['supervisor_id']);

$st = mysqli_prepare($conn, "SELECT * FROM supervisors WHERE id=?");
mysqli_stmt_bind_param($st, 'i', $sup_id);
mysqli_stmt_execute($st);
$sup = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
if (!$sup || !$sup['is_active']) {
    session_destroy();
    header('Location: ../auth/login.php'); exit;
}

// ── تعريف جميع الأقسام مع روابطها المباشرة ──────────────────
$sectionDefs = [
    // أقسام رئيسية
    'arabic_letters'    => ['label'=>'الحروف العربية',    'icon'=>'🔤','img'=>'../assets/icons/ar-letter.png','color'=>'#2a84c9','bg'=>'#e6f3ff',
        'links'=>['list'=>'subject-lessons.php?subject='.urlencode('اللغة العربية').'&type='.urlencode('الحروف العربية'),'add'=>'add-lesson.php'],
        'preview'=>'subjects/arabic/arabic.php'],
    'english_letters'   => ['label'=>'الحروف الإنجليزية', 'icon'=>'🔡','img'=>'../assets/icons/en-letter.png','color'=>'#28a75d','bg'=>'#e6ffec',
        'links'=>['list'=>'subject-lessons.php?subject='.urlencode('اللغة الإنجليزية').'&type='.urlencode('الحروف الإنجليزية'),'add'=>'add-english-letter.php'],
        'preview'=>'subjects/english/english-letters.php'],
    'arabic_numbers'    => ['label'=>'الأرقام العربية',   'icon'=>'🔢','img'=>'../assets/icons/math-ar.png','color'=>'#d49a24','bg'=>'#fff6e0',
        'links'=>['list'=>'subject-lessons.php?subject=%D8%A7%D9%84%D8%B1%D9%8A%D8%A7%D8%B6%D9%8A%D8%A7%D8%AA&type=%D8%A7%D9%84%D8%A3%D8%B1%D9%82%D8%A7%D9%85+%D8%A7%D9%84%D8%B9%D8%B1%D8%A8%D9%8A%D8%A9','add'=>'add-arabic-number.php'],
        'preview'=>'subjects/math/math.php'],
    'english_numbers'   => ['label'=>'الأرقام الإنجليزية','icon'=>'🔣','img'=>'../assets/icons/math-number.png','color'=>'#7b58d6','bg'=>'#f0ebff',
        'links'=>['list'=>'subject-lessons.php?subject=%D8%A7%D9%84%D9%84%D8%BA%D8%A9+%D8%A7%D9%84%D8%A5%D9%86%D8%AC%D9%84%D9%8A%D8%B2%D9%8A%D8%A9&type=%D8%A7%D9%84%D8%A3%D8%B1%D9%82%D8%A7%D9%85+%D8%A7%D9%84%D8%A5%D9%86%D8%AC%D9%84%D9%8A%D8%B2%D9%8A%D8%A9','add'=>'add-english-number.php'],
        'preview'=>'subjects/english/english-numbers.php'],
    'stories'           => ['label'=>'القصص',              'icon'=>'📖','color'=>'#e05353','bg'=>'#ffe6f5',
        'links'=>['list'=>'stories.php','add'=>'add-story.php'],
        'preview'=>'subjects/general/stories.php'],
    'games'             => ['label'=>'الألعاب',            'icon'=>'🎮','color'=>'#00b4b4','bg'=>'#e0fbfb',
        'links'=>['list'=>'games.php','add'=>null],
        'preview'=>'auth/children.php#games-section'],

    // ── الثقافة العامة — كل قسم بطاقة مستقلة ──
    'gc_animals_wild'   => ['label'=>'الحيوانات المفترسة', 'icon'=>'🦁','color'=>'#e05353','bg'=>'#ffe6e6',
        'links'=>['list'=>'manage-animals.php?category=wild','add'=>'add-animal.php?category=wild'],
        'preview'=>'subjects/general/wild-animals.php'],
    'gc_animals_pet'    => ['label'=>'الحيوانات الأليفة',  'icon'=>'🐮','color'=>'#28a75d','bg'=>'#e6ffec',
        'links'=>['list'=>'manage-animals.php?category=pet','add'=>'add-animal.php?category=pet'],
        'preview'=>'subjects/general/pets.php'],
    'gc_weather'        => ['label'=>'الطقس',              'icon'=>'☁️','color'=>'#2a84c9','bg'=>'#e6f3ff',
        'links'=>['list'=>'weather.php','add'=>null],
        'preview'=>'subjects/general/weather.php'],
    'gc_seasons'        => ['label'=>'الفصول الأربعة',    'icon'=>'🌸','color'=>'#28a75d','bg'=>'#e6ffec',
        'links'=>['list'=>'seasons.php','add'=>null],
        'preview'=>'subjects/general/seasons.php'],
    'gc_days'           => ['label'=>'أيام الأسبوع',       'icon'=>'📅','color'=>'#7b58d6','bg'=>'#f0ebff',
        'links'=>['list'=>'days.php','add'=>null],
        'preview'=>'subjects/general/days.php'],
    'gc_food_vegetable' => ['label'=>'الخضار',             'icon'=>'🥕','color'=>'#28a75d','bg'=>'#e6ffec',
        'links'=>['list'=>'manage-food.php?category=vegetable','add'=>'add-food.php?category=vegetable'],
        'preview'=>'subjects/general/vegetables.php'],
    'gc_food_fruit'     => ['label'=>'الفواكه',            'icon'=>'🍓','color'=>'#e05353','bg'=>'#ffe6f5',
        'links'=>['list'=>'manage-food.php?category=fruit','add'=>'add-food.php?category=fruit'],
        'preview'=>'subjects/general/fruits.php'],
    'gc_islam'          => ['label'=>'أركان الإسلام',      'icon'=>'☪️','color'=>'#1a5fa0','bg'=>'#e6f3ff',
        'links'=>['list'=>'admin-pillars.php','add'=>null],
        'preview'=>'subjects/general/islamic.php'],

    // أقسام أخرى
    'children'          => ['label'=>'الأطفال',            'icon'=>'👦','color'=>'#2a84c9','bg'=>'#e6f3ff',
        'links'=>['list'=>'children.php','add'=>'add-child.php']],
    'reports'           => ['label'=>'التقارير',           'icon'=>'📊','color'=>'#7b58d6','bg'=>'#f0ebff',
        'links'=>['list'=>'reports.php','add'=>null]],
    'chat'              => ['label'=>'المحادثات',          'icon'=>'💬','color'=>'#e05353','bg'=>'#ffe6e6',
        'links'=>['list'=>'admin-chat.php','add'=>null]],
];

// ── إضافة الأقسام المخصصة ديناميكياً ──────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS general_sections (
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL,
    icon VARCHAR(20) DEFAULT '★', sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$csRes = mysqli_query($conn, "SELECT id, name, icon, icon_file FROM general_sections ORDER BY sort_order ASC, id ASC");
if ($csRes) {
    while ($cs = mysqli_fetch_assoc($csRes)) {
        $key = 'gc_custom_' . $cs['id'];
        $sectionDefs[$key] = [
            'label'   => $cs['name'],
            'icon'    => $cs['icon'] ?: '★',
            'img'     => !empty($cs['icon_file']) ? '../' . $cs['icon_file'] : '',
            'color'   => '#7b58d6',
            'bg'      => '#f0ebff',
            'links'   => ['list'=>'manage-custom-section.php?id='.$cs['id'],'add'=>null],
            'preview' => 'subjects/general/custom-section.php?id='.$cs['id'],
        ];
    }
}

// ── الأقسام المتاحة للشريط الجانبي (عرض فقط) ────────────────
$availableSections = array_filter($sectionDefs, fn($k) => supCan($k, 'can_view'), ARRAY_FILTER_USE_KEY);
// ── كل الأقسام للبطاقات (تظهر دائماً) ───────────────────────
$allSections = $sectionDefs;

// ── عدد الرسائل غير المقروءة ─────────────────────────────────
$unread_count = 0;
$ucr = mysqli_query($conn, "SELECT COUNT(*) as c FROM chat_messages WHERE sender='child' AND is_read=0");
if ($ucr) $unread_count = mysqli_fetch_assoc($ucr)['c'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>لوحة المشرف</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}

/* ══ Layout ══ */
.admin-layout{display:block;}
.admin-content{margin-right:240px;padding:36px 40px;transition:margin-right .3s ease;min-height:100vh;}

/* ══ Sidebar ══ */
.admin-sidebar{
    width:240px;
    background:linear-gradient(175deg,#0f2942 0%,#1a3f6f 55%,#1e5096 100%);
    display:flex;flex-direction:column;padding:0;
    position:fixed;right:0;top:0;height:100vh;
    box-shadow:4px 0 24px rgba(0,0,0,.22);
    z-index:1000;overflow:hidden;
    transition:width .3s ease;
}
.admin-sidebar::before{content:"";position:absolute;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.04);top:-80px;right:-80px;pointer-events:none;}

/* ══ زر التبديل ══ */
.sidebar-open-btn{
    display:flex;position:fixed;top:50%;right:240px;
    transform:translateY(-50%);z-index:1001;
    width:22px;height:52px;
    background:linear-gradient(175deg,#0f2942,#1a3f6f);
    border:none;border-radius:8px 0 0 8px;
    cursor:pointer;color:#fff;font-size:14px;
    box-shadow:-3px 0 10px rgba(0,0,0,.25);
    transition:right .3s ease,background .2s;
    align-items:center;justify-content:center;
}
.sidebar-open-btn:hover{background:#2a84c9;}

/* ══ ديسكتوب collapsed ══ */
@media(min-width:901px){
    .admin-sidebar.collapsed{width:0;padding:0;}
    .admin-sidebar.collapsed > *{visibility:hidden;}
    .admin-layout:has(.admin-sidebar.collapsed) .admin-content{margin-right:0;}
    .admin-layout:has(.admin-sidebar.collapsed) .sidebar-open-btn{right:0;}
}

/* ══ موبايل وتابلت ══ */
@media(max-width:900px){
    .admin-sidebar{width:0;}
    .admin-sidebar.open{width:240px;}
    .admin-content{margin-right:0;padding:20px 16px;}
    .sidebar-open-btn{right:0;}
    .admin-layout:has(.admin-sidebar.open) .sidebar-open-btn{right:240px;}
    .admin-layout:has(.admin-sidebar.open)::before{
        content:"";position:fixed;inset:0;
        background:rgba(0,0,0,.35);z-index:999;
    }
}

/* ══ عناصر السايدبار ══ */
.sidebar-logo-area{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px 20px 22px;border-bottom:1px solid rgba(255,255,255,.1);}
.sidebar-logo-area img{height:120px;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,.3));}
.sidebar-logo-label{font-size:11px;font-weight:800;color:rgba(255,255,255,.45);margin-top:8px;letter-spacing:1.5px;text-transform:uppercase;}
.sidebar-admin-info{display:flex;align-items:center;gap:10px;margin:16px 16px 8px;background:rgba(255,255,255,.07);border-radius:14px;padding:10px 14px;}
.sidebar-admin-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#42a5f5,#1565c0);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:900;color:#fff;flex-shrink:0;}
.sidebar-admin-name{font-size:13px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sidebar-admin-role{font-size:10px;color:rgba(255,255,255,.5);font-weight:700;margin-top:1px;}
.sidebar-section-title{font-size:9px;font-weight:900;color:rgba(255,255,255,.35);letter-spacing:2px;text-transform:uppercase;padding:14px 20px 6px;}
.admin-sidebar nav{display:flex;flex-direction:column;gap:3px;padding:4px 12px;flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;scrollbar-width:none;-ms-overflow-style:none;}
.admin-sidebar nav::-webkit-scrollbar{display:none;}
.admin-sidebar nav a{display:flex;align-items:center;gap:11px;padding:11px 14px;border-radius:12px;font-size:13px;font-weight:800;color:rgba(255,255,255,.75);text-decoration:none;transition:all .22s ease;position:relative;}
.admin-sidebar nav a .nav-icon{font-size:16px;width:22px;text-align:center;flex-shrink:0;}
.admin-sidebar nav a:hover{background:rgba(255,255,255,.12);color:#fff;transform:translateX(-3px);}
.admin-sidebar nav a.active{background:rgba(255,255,255,.18);color:#fff;box-shadow:inset 3px 0 0 #42a5f5;}
.nav-badge{margin-right:auto;background:#e53935;color:#fff;font-size:10px;font-weight:900;padding:2px 7px;border-radius:999px;animation:pulse-badge 1.8s infinite;}
@keyframes pulse-badge{0%,100%{box-shadow:0 0 0 0 rgba(229,57,53,.5);}50%{box-shadow:0 0 0 5px rgba(229,57,53,0);}}
.sidebar-divider{height:1px;background:rgba(255,255,255,.08);margin:8px 16px;}
.sidebar-logout{margin:8px 12px 20px;}
.sidebar-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;font-size:13px;font-weight:900;color:#ff5252;text-decoration:none;background:rgba(229,57,53,.22);border:1px solid rgba(255,82,82,.4);transition:all .22s ease;}
.sidebar-logout a:hover{background:rgba(229,57,53,.38);color:#ff1744;}

/* ══ ترحيب ══ */
.welcome-wrap{
    background:linear-gradient(135deg,#21425f,#2a84c9);
    border-radius:22px;padding:26px 30px;
    margin-bottom:28px;
    display:flex;align-items:center;justify-content:space-between;
    box-shadow:0 8px 28px rgba(33,66,95,.2);
    gap:16px;
}
.welcome-text h1{font-size:22px;font-weight:900;color:#fff;}
.welcome-text p{font-size:13px;color:rgba(255,255,255,.75);font-weight:700;margin-top:5px;}
.welcome-badge{background:rgba(255,255,255,.15);border-radius:14px;padding:12px 22px;text-align:center;flex-shrink:0;}
.welcome-badge .num{font-size:30px;font-weight:900;color:#fff;line-height:1;}
.welcome-badge .lbl{font-size:11px;color:rgba(255,255,255,.75);font-weight:800;margin-top:3px;}

/* ══ عنوان قسم ══ */
.section-header{display:flex;align-items:center;gap:10px;margin:28px 0 14px;}
.section-header h2{font-size:15px;font-weight:900;color:#21425f;white-space:nowrap;}
.section-header::after{content:'';flex:1;height:2px;background:linear-gradient(90deg,#e0eaf5,transparent);}

/* ══ البطاقات ══ */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin-bottom:8px;}
.sec-card{
    background:#fff;border-radius:20px;
    box-shadow:0 4px 18px rgba(33,66,95,.07);
    overflow:hidden;transition:.22s ease;
    display:flex;flex-direction:column;
}
.sec-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(33,66,95,.14);}

.sec-card-top{padding:18px 18px 14px;display:flex;align-items:center;gap:14px;flex:1;}
.sec-icon-wrap{
    width:50px;height:50px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:24px;flex-shrink:0;
}
.sec-label{font-size:15px;font-weight:900;color:#21425f;line-height:1.3;}
.sec-perms{display:flex;gap:4px;margin-top:6px;flex-wrap:wrap;}
.perm-tag{font-size:9px;font-weight:900;padding:2px 7px;border-radius:5px;}

.sec-card-actions{padding:0 14px 14px;display:flex;gap:8px;}
.sec-btn{
    flex:1;display:flex;align-items:center;justify-content:center;
    gap:5px;padding:10px;border-radius:12px;
    font-size:12px;font-weight:900;text-decoration:none;transition:.18s;
}
.sec-btn-view:hover{filter:brightness(1.08);}
.sec-locked{background:#f5f7fa;color:#b0bec5;cursor:default;border-radius:12px;padding:10px;font-size:12px;font-weight:900;text-align:center;flex:1;}

.no-perms{background:#fff;border-radius:18px;padding:50px 30px;text-align:center;box-shadow:0 6px 20px rgba(33,66,95,.08);}

/* ══ ريسبونسف ══ */
@media(max-width:900px){
    .cards-grid{grid-template-columns:repeat(2,1fr);}
    .welcome-wrap{padding:20px 22px;}
    .welcome-text h1{font-size:18px;}
}
@media(max-width:600px){
    .cards-grid{grid-template-columns:repeat(2,1fr);gap:10px;}
    .welcome-wrap{flex-direction:column;align-items:flex-start;padding:18px;}
    .welcome-badge{align-self:stretch;display:flex;gap:12px;align-items:center;justify-content:center;padding:10px 18px;}
    .welcome-badge .num{font-size:24px;}
    .sec-card-top{padding:14px 14px 10px;gap:10px;}
    .sec-icon-wrap{width:42px;height:42px;font-size:20px;}
    .sec-label{font-size:13px;}
    .perm-tag{font-size:8px;}
    .sec-card-actions{padding:0 10px 10px;gap:6px;}
    .sec-btn{font-size:11px;padding:8px 6px;}
    .section-header h2{font-size:13px;}
}
@media(max-width:360px){
    .cards-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="admin-layout">

<aside class="admin-sidebar">
    <div class="sidebar-logo-area">
        <img src="../logo.png" alt="تمكين">
        <span class="sidebar-logo-label">لوحة التحكم</span>
    </div>
    <div class="sidebar-admin-info">
        <div class="sidebar-admin-avatar"><?php echo mb_substr($sup['full_name'] ?? 'م', 0, 1, 'UTF-8'); ?></div>
        <div>
            <div class="sidebar-admin-name"><?php echo htmlspecialchars($sup['full_name'] ?? 'المشرف'); ?></div>
            <div class="sidebar-admin-role">مشرف</div>
        </div>
    </div>
    <div class="sidebar-section-title">القائمة الرئيسية</div>
    <nav>
        <a href="supervisor-dashboard.php" class="<?php echo $current_page === 'supervisor-dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> الصفحة الرئيسية
        </a>
        <?php foreach ($availableSections as $key => $def): ?>
        <?php if ($key === 'chat') continue; ?>
        <a href="<?php echo $def['links']['list'] ?>">
            <span class="nav-icon">
                <?php if (!empty($def['img'])): ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;background:rgba(255,255,255,0.18);border-radius:6px;flex-shrink:0;">
                    <img src="<?php echo $def['img'] ?>" style="width:18px;height:18px;object-fit:contain;">
                </span>
                <?php else: echo $def['icon']; endif; ?>
            </span>
            <?php echo $def['label'] ?>
        </a>
        <?php endforeach; ?>
        <?php if (supCan('chat', 'can_view')): ?>
        <a href="admin-chat.php" class="<?php echo $current_page === 'admin-chat.php' ? 'active' : ''; ?>">
            <span class="nav-icon">💬</span>
            <span>المحادثات</span>
            <?php if ($unread_count > 0): ?>
            <span class="nav-badge"><?php echo $unread_count ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-divider"></div>
    <div class="sidebar-logout">
        <a href="../auth/logout.php"><span style="font-size:17px;">🚪</span> تسجيل الخروج</a>
    </div>
</aside>

<!-- زر التبديل -->
<button class="sidebar-open-btn" id="supOpenBtn">›</button>

<script>
(function(){
    var sidebar = document.querySelector('.admin-sidebar');
    var openBtn = document.getElementById('supOpenBtn');
    if (!sidebar) return;

    var isSmall = function(){ return window.innerWidth <= 900; };
    var KEY = 'supSidebarCollapsed';

    if (!isSmall() && localStorage.getItem(KEY) === '1') sidebar.classList.add('collapsed');

    function collapse(){
        if (isSmall()) sidebar.classList.remove('open');
        else { sidebar.classList.add('collapsed'); localStorage.setItem(KEY,'1'); }
    }
    function expand(){
        if (isSmall()) sidebar.classList.add('open');
        else { sidebar.classList.remove('collapsed'); localStorage.setItem(KEY,'0'); }
    }
    function isOpen(){ return isSmall() ? sidebar.classList.contains('open') : !sidebar.classList.contains('collapsed'); }
    function updateArrow(){ if(openBtn) openBtn.textContent = isOpen() ? '›' : '‹'; }
    function toggle(){ if(isOpen()) collapse(); else expand(); updateArrow(); }

    if (openBtn) openBtn.addEventListener('click', toggle);
    updateArrow();

    sidebar.querySelectorAll('a').forEach(function(a){
        a.addEventListener('click', function(){ if(isSmall()){ collapse(); updateArrow(); } });
    });

    // منع الزوم على iOS
    document.addEventListener('touchmove', function(e){ if(e.touches.length>1) e.preventDefault(); },{passive:false});
    var lastTouch=0;
    document.addEventListener('touchend', function(e){ var n=Date.now(); if(n-lastTouch<300) e.preventDefault(); lastTouch=n; },false);
})();
</script>

<main class="admin-content">

    <div class="welcome-wrap">
        <div class="welcome-text">
            <h1>مرحباً، <?php echo htmlspecialchars($sup['full_name']) ?> 👋</h1>
            <p>لوحة تحكم المشرف — <?php echo date('l، d/m/Y') ?></p>
        </div>
        <div class="welcome-badge">
            <div class="num"><?php echo count($availableSections) ?></div>
            <div class="lbl">قسم متاح</div>
        </div>
    </div>

    <?php
    // تجميع الأقسام في فئات للعرض — من allSections (كل الأقسام)
    $mainKeys  = ['arabic_letters','english_letters','arabic_numbers','english_numbers','stories','games'];
    $gcKeys    = ['gc_animals_wild','gc_animals_pet','gc_weather','gc_seasons','gc_days','gc_food_vegetable','gc_food_fruit','gc_islam'];
    $otherKeys = ['children','reports','chat'];

    $mainSections  = array_filter($allSections, fn($k) => in_array($k, $mainKeys), ARRAY_FILTER_USE_KEY);
    $gcSections    = array_filter($allSections, fn($k) => in_array($k, $gcKeys) || strpos($k,'gc_custom_') === 0, ARRAY_FILTER_USE_KEY);
    $otherSections = array_filter($allSections, fn($k) => in_array($k, $otherKeys), ARRAY_FILTER_USE_KEY);

    $renderCards = function($list): void { ?>
    <div class="cards-grid">
    <?php foreach ($list as $key => $def):
        $pv = supCan($key, 'can_view');
        $pa = supCan($key, 'can_add');
        $pe = supCan($key, 'can_edit');
        $pd = supCan($key, 'can_delete');
        $hasAnyPerm = $pv || $pa || $pe || $pd;
    ?>
    <div class="sec-card" style="<?php echo !$hasAnyPerm ? 'opacity:.6' : '' ?>">
        <div class="sec-card-top">
            <div class="sec-icon-wrap" style="background:<?php echo $def['bg'] ?>">
                <?php if (!empty($def['img'])): ?>
                <img src="<?php echo $def['img'] ?>" style="width:28px;height:28px;object-fit:contain;" onerror="this.outerHTML='<span><?php echo $def['icon'] ?></span>'">
                <?php else: echo $def['icon']; endif; ?>
            </div>
            <div>
                <div class="sec-label"><?php echo $def['label'] ?></div>
                <div class="sec-perms">
                    <?php if ($pv): ?><span class="perm-tag" style="background:#e8f3ff;color:#1f5f9a">عرض</span><?php endif; ?>
                    <?php if ($pa): ?><span class="perm-tag" style="background:#e8fff0;color:#1a7a40">إضافة</span><?php endif; ?>
                    <?php if ($pe): ?><span class="perm-tag" style="background:#fff6e0;color:#8a5e00">تعديل</span><?php endif; ?>
                    <?php if ($pd): ?><span class="perm-tag" style="background:#ffe8e8;color:#c0392b">حذف</span><?php endif; ?>
                    <?php if (!$hasAnyPerm): ?><span class="perm-tag" style="background:#f0f0f0;color:#aaa">🔒 غير متاح</span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sec-card-actions">
            <?php if ($hasAnyPerm): ?>
            <a href="<?php echo $def['links']['list'] ?>" class="sec-btn sec-btn-view"
               style="background:<?php echo $def['bg'] ?>;color:<?php echo $def['color'] ?>">
               👁️ إدارة
            </a>
            <?php if (!empty($def['preview'])): ?>
            <a href="preview.php?url=<?php echo urlencode($def['preview']) ?>&label=<?php echo urlencode($def['label']) ?>"
               target="_blank" class="sec-btn"
               style="background:#fff8e1;color:#e65100;border:1.5px solid #ffcc80;">
               👶 معاينة
            </a>
            <?php endif; ?>
            <?php else: ?>
            <div class="sec-locked">🔒 لا توجد صلاحيات</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php };

    if ($mainSections): ?>
    <div class="section-header"><h2>📚 الأقسام الرئيسية</h2></div>
    <?php $renderCards($mainSections); endif;

    if ($gcSections): ?>
    <div class="section-header"><h2>🌿 الثقافة العامة</h2></div>
    <?php $renderCards($gcSections); endif;

    if ($otherSections): ?>
    <div class="section-header"><h2>⚙️ أقسام أخرى</h2></div>
    <?php $renderCards($otherSections); endif; ?>

</main>
</div>
</body>
</html>
