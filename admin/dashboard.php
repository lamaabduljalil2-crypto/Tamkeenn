<?php
date_default_timezone_set('Asia/Riyadh');

require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    child_name VARCHAR(255) NULL,
    activity_type VARCHAR(20) NOT NULL,
    activity_key VARCHAR(100) NOT NULL,
    activity_label VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function q($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (mysqli_fetch_row($r)[0] ?? 0) : 0;
}

$startOfWeek = date('Y-m-d', strtotime('-7 days'));

$totalChildren = q($conn, "SELECT COUNT(*) FROM children");
$newChildren   = q($conn, "SELECT COUNT(*) FROM children WHERE created_at >= '$startOfWeek'");

$arLettersTotal = q($conn, "SELECT COUNT(DISTINCT letter_id) FROM arabic_letter_examples");
$arLettersNew   = q($conn, "SELECT COUNT(DISTINCT letter_id) FROM arabic_letter_examples WHERE created_at >= '$startOfWeek'");

$enLettersTotal = q($conn, "SELECT COUNT(DISTINCT letter_id) FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الحروف الإنجليزية' AND letter_id > 0");
$enLettersNew   = q($conn, "SELECT COUNT(DISTINCT letter_id) FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الحروف الإنجليزية' AND letter_id > 0 AND created_at >= '$startOfWeek'");

$chkArNum = mysqli_query($conn, "SHOW TABLES LIKE 'arabic_numbers'");
if ($chkArNum && mysqli_num_rows($chkArNum) > 0) {
    $arNumsTotal = q($conn, "SELECT COUNT(*) FROM arabic_numbers");
    $arNumsNew   = q($conn, "SELECT COUNT(*) FROM arabic_numbers WHERE created_at >= '$startOfWeek'");
}
if (empty($arNumsTotal)) {
    $arNumsTotal = q($conn, "SELECT COUNT(*) FROM lessons WHERE lesson_type='الأرقام العربية'");
    $arNumsNew   = q($conn, "SELECT COUNT(*) FROM lessons WHERE lesson_type='الأرقام العربية' AND created_at >= '$startOfWeek'");
}

$enNumsTotal = q($conn, "SELECT COUNT(*) FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الأرقام الإنجليزية'");
$enNumsNew   = q($conn, "SELECT COUNT(*) FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الأرقام الإنجليزية' AND created_at >= '$startOfWeek'");

$totalGeneralFixed = 6;
$chkGenSec = mysqli_query($conn, "SHOW TABLES LIKE 'general_sections'");
$totalGeneralCustom = ($chkGenSec && mysqli_num_rows($chkGenSec) > 0)
    ? q($conn, "SELECT COUNT(*) FROM general_sections") : 0;
$totalGeneral = $totalGeneralFixed + $totalGeneralCustom;

$totalGames   = q($conn, "SELECT COUNT(*) FROM games");
$newGames     = q($conn, "SELECT COUNT(*) FROM games WHERE created_at >= '$startOfWeek'");
$totalStories = q($conn, "SELECT COUNT(*) FROM stories");
$newStories   = q($conn, "SELECT COUNT(*) FROM stories WHERE created_at >= '$startOfWeek'");

// ── ضغطات زر المساعدة ──
$chkHelp = mysqli_query($conn, "SHOW TABLES LIKE 'help_clicks'");
$helpTotal = ($chkHelp && mysqli_num_rows($chkHelp) > 0)
    ? q($conn, "SELECT COUNT(*) FROM help_clicks") : 0;
$helpWeek  = ($chkHelp && mysqli_num_rows($chkHelp) > 0)
    ? q($conn, "SELECT COUNT(*) FROM help_clicks WHERE created_at >= '$startOfWeek'") : 0;

$topLessons = [];
$res = mysqli_query($conn, "SELECT activity_label AS name, COUNT(DISTINCT user_id) AS cnt FROM progress WHERE activity_type = 'lesson' GROUP BY activity_key ORDER BY cnt DESC LIMIT 5");
if ($res) while ($r = mysqli_fetch_assoc($res)) $topLessons[] = $r;

$topGames = [];
$res = mysqli_query($conn, "SELECT activity_label AS name, COUNT(DISTINCT user_id) AS cnt FROM progress WHERE activity_type = 'game' GROUP BY activity_key ORDER BY cnt DESC LIMIT 5");
if ($res) while ($r = mysqli_fetch_assoc($res)) $topGames[] = $r;

$topStories = [];
$res = mysqli_query($conn, "SELECT activity_label AS name, COUNT(DISTINCT user_id) AS cnt FROM progress WHERE activity_type = 'story' GROUP BY activity_key ORDER BY cnt DESC LIMIT 5");
if ($res) while ($r = mysqli_fetch_assoc($res)) $topStories[] = $r;

$recentChildren = [];
$res = mysqli_query($conn, "SELECT username, created_at, last_login FROM children ORDER BY created_at DESC LIMIT 5");
if ($res) while ($r = mysqli_fetch_assoc($res)) $recentChildren[] = $r;

$chartLabels = [];
$chartCategories = [
    'arabic-letter'  => ['حروف عربية',    '#2a84c9', []],
    'english-letter' => ['حروف إنجليزية', '#28a75d', []],
    'arabic-number'  => ['أرقام عربية',   '#d49a24', []],
    'english-number' => ['أرقام إنجليزية','#7b58d6', []],
    'section'        => ['ثقافة عامة',    '#e05353', []],
    'game'           => ['ألعاب',          '#00b4b4', []],
    'story'          => ['قصص',           '#ff4f8f', []],
    'help'           => ['زر المساعدة',   '#f97316', []],
];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($day));
    $chartCategories['arabic-letter'][2][]  = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_key LIKE 'arabic-letter-%'");
    $chartCategories['english-letter'][2][] = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_key LIKE 'english-letter-%'");
    $chartCategories['arabic-number'][2][]  = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_key LIKE 'arabic-number-%'");
    $chartCategories['english-number'][2][] = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_key LIKE 'english-number-%'");
    $chartCategories['section'][2][]        = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_type='section'");
    $chartCategories['game'][2][]           = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_type='game'");
    $chartCategories['story'][2][]          = (int)q($conn, "SELECT COUNT(*) FROM progress WHERE DATE(created_at)='$day' AND activity_type='story'");
    $chartCategories['help'][2][]           = ($chkHelp && mysqli_num_rows($chkHelp) > 0)
        ? (int)q($conn, "SELECT COUNT(*) FROM help_clicks WHERE DATE(created_at)='$day'") : 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الرئيسية – لوحة التحكم</title>
<link rel="stylesheet" href="assets/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}
.admin-content{padding:28px;}

.welcome-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:22px;}
.welcome-row h1{font-size:26px;font-weight:900;color:#21425f;}
.welcome-row p{font-size:14px;color:#7a90a3;font-weight:700;margin-top:4px;}
.date-badge{background:#fff;border-radius:14px;padding:8px 18px;font-size:14px;font-weight:800;color:#21425f;box-shadow:0 4px 12px rgba(0,0,0,.07);}

/* ── بطاقات الإحصاء (مصغّرة) ── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:18px;}
.stat-card{background:#fff;border-radius:16px;padding:12px 11px;box-shadow:0 6px 18px rgba(33,66,95,.08);display:flex;flex-direction:column;gap:5px;position:relative;overflow:hidden;}
.stat-card::after{content:"";position:absolute;width:55px;height:55px;border-radius:50%;bottom:-16px;left:-12px;opacity:.13;}
.c1::after{background:#2a84c9;} .c2::after{background:#2a84c9;} .c3::after{background:#28a75d;}
.c4::after{background:#d49a24;} .c5::after{background:#7b58d6;} .c6::after{background:#e05353;} .c7::after{background:#00b4b4;} .c8::after{background:#f97316;}
.stat-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.c1 .stat-icon{background:#e6f3ff;} .c2 .stat-icon{background:#e6f3ff;} .c3 .stat-icon{background:#e6ffec;}
.c4 .stat-icon{background:#fff6e0;} .c5 .stat-icon{background:#f0ebff;} .c6 .stat-icon{background:#ffe6e6;} .c7 .stat-icon{background:#e0fbfb;} .c8 .stat-icon{background:#fff3e8;}
.stat-icon img{width:22px;height:22px;object-fit:contain;}
.stat-num{font-size:22px;font-weight:900;color:#21425f;line-height:1;}
.stat-lbl{font-size:11px;font-weight:800;color:#7a90a3;}
.stat-badge{display:inline-flex;align-items:center;font-size:9px;font-weight:900;padding:2px 6px;border-radius:999px;width:fit-content;}
.badge-blue{background:#e8f8ee;color:#28a75d;}
.badge-gray{background:#f0f5fb;color:#7a90a3;}

/* ── الصف الأوسط (دونات + رسم + أطفال) ── */
.mid-row{display:grid;grid-template-columns:1fr 2fr 1fr;gap:14px;margin-bottom:0;flex:1;min-height:0;}
.chart-card{background:#fff;border-radius:18px;padding:14px 16px;box-shadow:0 6px 18px rgba(33,66,95,.08);display:flex;flex-direction:column;}
.chart-card h3{font-size:13px;font-weight:900;color:#21425f;margin-bottom:8px;flex-shrink:0;}
.donut-wrap{width:160px;height:160px;flex-shrink:0;margin:0 auto;}
.donut-wrap canvas{width:160px !important;height:160px !important;}
.activity-wrap{position:relative;height:260px;}
.activity-wrap canvas{width:100% !important;height:100% !important;}
.legend-item{display:flex;align-items:center;justify-content:space-between;font-size:11px;font-weight:800;color:#21425f;padding:3px 0;border-bottom:1px solid #f0f5fb;}
.legend-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-left:5px;}

/* ── كارت الأطفال ── */
.info-card{background:#fff;border-radius:18px;padding:16px;box-shadow:0 6px 18px rgba(33,66,95,.08);}
.info-card h3{font-size:14px;font-weight:900;color:#21425f;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #f0f5fb;}
.info-row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f7fafd;font-size:12px;}
.info-row:last-child{border:none;}
.info-name{font-weight:800;color:#21425f;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.child-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2a84c9,#21425f);color:#fff;font-weight:900;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}

.act-time{font-size:10px;color:#7a90a3;font-weight:700;}

@media(max-width:1100px){.mid-row{grid-template-columns:1fr 1fr;} .mid-row .info-card{grid-column:span 2;}}
@media(max-width:700px){
    .mid-row{grid-template-columns:1fr;}
    .mid-row .info-card{grid-column:span 1;}
    .activity-wrap{min-height:200px;}
}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<!-- ترحيب -->
<div class="welcome-row">
    <div>
        <h1>مرحباً، <?php echo htmlspecialchars($_SESSION['admin_username']); ?> 👋</h1>
        <p>لوحة التحكم الرئيسية لإدارة منصة الأطفال التعليمية</p>
    </div>
    <div class="date-badge">📅 <?php echo date('d/m/Y'); ?></div>
</div>

<!-- بطاقات الإحصاء -->
<div class="stat-grid">

    <div class="stat-card c1">
        <div class="stat-icon">👦</div>
        <div class="stat-num"><?php echo $totalChildren; ?></div>
        <div class="stat-lbl">إجمالي الأطفال</div>
        <div class="stat-badge badge-blue">↑ <?php echo $newChildren; ?> هذا الأسبوع</div>
    </div>

    <div class="stat-card c2">
        <div class="stat-icon"><img src="../assets/icons/ar-letter.png" alt="حروف عربية"></div>
        <div class="stat-num"><?php echo $arLettersTotal; ?></div>
        <div class="stat-lbl">حروف عربية</div>
        <div class="stat-badge <?php echo $arLettersNew > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $arLettersNew > 0 ? "↑ {$arLettersNew} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c3">
        <div class="stat-icon"><img src="../assets/icons/en-letter.png" alt="حروف إنجليزية"></div>
        <div class="stat-num"><?php echo $enLettersTotal; ?></div>
        <div class="stat-lbl">حروف إنجليزية</div>
        <div class="stat-badge <?php echo $enLettersNew > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $enLettersNew > 0 ? "↑ {$enLettersNew} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c4">
        <div class="stat-icon"><img src="../assets/icons/math-ar.png" alt="أرقام عربية"></div>
        <div class="stat-num"><?php echo $arNumsTotal; ?></div>
        <div class="stat-lbl">أرقام عربية</div>
        <div class="stat-badge <?php echo $arNumsNew > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $arNumsNew > 0 ? "↑ {$arNumsNew} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c5">
        <div class="stat-icon"><img src="../assets/icons/math-number.png" alt="أرقام إنجليزية"></div>
        <div class="stat-num"><?php echo $enNumsTotal; ?></div>
        <div class="stat-lbl">أرقام إنجليزية</div>
        <div class="stat-badge <?php echo $enNumsNew > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $enNumsNew > 0 ? "↑ {$enNumsNew} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c6">
        <div class="stat-icon">🎮</div>
        <div class="stat-num"><?php echo $totalGames; ?></div>
        <div class="stat-lbl">إجمالي الألعاب</div>
        <div class="stat-badge <?php echo $newGames > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $newGames > 0 ? "↑ {$newGames} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c7">
        <div class="stat-icon">📖</div>
        <div class="stat-num"><?php echo $totalStories; ?></div>
        <div class="stat-lbl">إجمالي القصص</div>
        <div class="stat-badge <?php echo $newStories > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $newStories > 0 ? "↑ {$newStories} جديد" : 'لا جديد هذا الأسبوع'; ?>
        </div>
    </div>

    <div class="stat-card c8">
        <div class="stat-icon">🤟</div>
        <div class="stat-num"><?php echo $helpTotal; ?></div>
        <div class="stat-lbl">ضغطات زر المساعدة</div>
        <div class="stat-badge <?php echo $helpWeek > 0 ? 'badge-blue' : 'badge-gray'; ?>">
            <?php echo $helpWeek > 0 ? "↑ {$helpWeek} هذا الأسبوع" : 'لا ضغطات هذا الأسبوع'; ?>
        </div>
    </div>

</div>

<!-- دونات (يسار) + رسم بياني (وسط) + أطفال (يمين) -->
<div class="mid-row">

    <!-- 1. توزيع المحتوى – يسار -->
    <div class="chart-card">
        <h3>🧩 توزيع المحتوى</h3>
        <div class="donut-wrap"><canvas id="contentDonut"></canvas></div>
        <div style="margin-top:8px">
        <?php
        $totalContent = $arLettersTotal + $enLettersTotal + $arNumsTotal + $enNumsTotal + $totalGeneral + $totalGames + $totalStories;
        $imgStyle = 'width:14px;height:14px;object-fit:contain;vertical-align:middle;margin-left:3px;';
        $contentItems = [
            ['<img src="../assets/icons/ar-letter.png" style="'.$imgStyle.'"> حروف عربية',    $arLettersTotal, '#2a84c9'],
            ['<img src="../assets/icons/en-letter.png" style="'.$imgStyle.'"> حروف إنجليزية', $enLettersTotal, '#28a75d'],
            ['<img src="../assets/icons/math-ar.png"   style="'.$imgStyle.'"> أرقام عربية',   $arNumsTotal,    '#d49a24'],
            ['<img src="../assets/icons/math-number.png" style="'.$imgStyle.'"> أرقام إنجليزية',$enNumsTotal,  '#7b58d6'],
            ['🌿 ثقافة عامة',    $totalGeneral,   '#e05353'],
            ['🎮 ألعاب',          $totalGames,     '#00b4b4'],
            ['📖 قصص',           $totalStories,   '#ff4f8f'],
        ];
        foreach ($contentItems as [$lbl, $cnt, $clr]):
            $pct = $totalContent > 0 ? round($cnt/$totalContent*100) : 0;
        ?>
        <div class="legend-item">
            <span><span class="legend-dot" style="background:<?php echo $clr ?>"></span><?php echo $lbl ?></span>
            <span><?php echo $cnt ?> (<?php echo $pct ?>%)</span>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- 2. نشاط المنصة – وسط -->
    <div class="chart-card">
        <h3>📊 نشاط المنصة – آخر 7 أيام</h3>
        <div class="activity-wrap"><canvas id="activityChart"></canvas></div>
    </div>

    <!-- 3. أحدث تسجيلات الأطفال – يمين -->
    <div class="info-card">
        <h3>👦 أحدث تسجيلات الأطفال</h3>
        <?php if ($recentChildren): foreach ($recentChildren as $c):
            $initial = mb_substr($c['username'], 0, 1, 'UTF-8');
        ?>
        <div class="info-row">
            <div style="display:flex;align-items:center;gap:7px">
                <div class="child-avatar"><?php echo $initial; ?></div>
                <div>
                    <div class="info-name"><?php echo htmlspecialchars($c['username']); ?></div>
                    <div class="act-time last-login-time"
                         data-time="<?php echo htmlspecialchars($c['last_login'] ?? ''); ?>"
                         data-empty="لم يسجل دخول بعد"
                         data-prefix="آخر دخول: "></div>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div style="color:#7a90a3;font-weight:800;text-align:center;padding:16px;font-size:13px">لا يوجد أطفال بعد</div>
        <?php endif; ?>
    </div>

</div>

</main>
</div>

<script>
new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [
        <?php foreach ($chartCategories as $cat): [$lbl, $clr, $vals] = $cat; ?>
        {
            label: <?php echo json_encode($lbl, JSON_UNESCAPED_UNICODE); ?>,
            data: <?php echo json_encode($vals); ?>,
            borderColor: '<?php echo $clr ?>',
            backgroundColor: '<?php echo $clr ?>22',
            borderWidth: 2,
            pointBackgroundColor: '<?php echo $clr ?>',
            pointRadius: 3,
            tension: 0.4,
            fill: false
        },
        <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { font: { size: 10 }, boxWidth: 10, padding: 8, color: '#21425f' }
            }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

new Chart(document.getElementById('contentDonut'), {
    type: 'doughnut',
    data: {
        labels: ['حروف عربية','حروف إنجليزية','أرقام عربية','أرقام إنجليزية','ثقافة عامة','ألعاب','قصص'],
        datasets: [{
            data: [<?php echo "$arLettersTotal,$enLettersTotal,$arNumsTotal,$enNumsTotal,$totalGeneral,$totalGames,$totalStories"; ?>],
            backgroundColor: ['#2a84c9','#28a75d','#d49a24','#7b58d6','#e05353','#00b4b4','#ff4f8f'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

function timeAgoJs(dateStr) {
    if (!dateStr) return '';
    const past = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(past.getTime())) return '';
    const d = Math.abs(Math.floor((Date.now() - past.getTime()) / 1000));
    if (d < 60)    return 'منذ ' + d + ' ثانية';
    if (d < 3600)  return 'منذ ' + Math.floor(d/60) + ' دقيقة';
    if (d < 86400) return 'منذ ' + Math.floor(d/3600) + ' ساعة';
    return 'منذ ' + Math.floor(d/86400) + ' يوم';
}
function updateTimes() {
    document.querySelectorAll('.activity-time[data-time]').forEach(el => {
        el.textContent = el.dataset.time ? timeAgoJs(el.dataset.time) : '';
    });
    document.querySelectorAll('.last-login-time[data-time]').forEach(el => {
        const t = el.dataset.time;
        el.textContent = t ? (el.dataset.prefix||'') + timeAgoJs(t) : (el.dataset.empty||'');
    });
}
updateTimes();
setInterval(updateTimes, 60000);
</script>
</body>
</html>
