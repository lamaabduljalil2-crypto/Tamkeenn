<?php
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

// ── بيانات كل طفل ─────────────────────────────────────────────
$chkHelp = mysqli_query($conn, "SHOW TABLES LIKE 'help_clicks'");
$hasHelp = $chkHelp && mysqli_num_rows($chkHelp) > 0;

$rows = [];
$res = mysqli_query($conn, "
    SELECT
        user_id,
        MAX(child_name) AS child_name,
        SUM(activity_key LIKE 'arabic-letter-%')  AS ar_letters,
        SUM(activity_key LIKE 'english-letter-%') AS en_letters,
        SUM(activity_key LIKE 'arabic-number-%')  AS ar_numbers,
        SUM(activity_key LIKE 'english-number-%') AS en_numbers,
        SUM(activity_type = 'section')            AS sections,
        SUM(activity_type = 'game')               AS games,
        SUM(activity_type = 'story')              AS stories,
        COUNT(*) AS total,
        MAX(created_at) AS last_activity
    FROM progress
    GROUP BY user_id
    ORDER BY last_activity DESC
");
if ($res) while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;

// إضافة عدد ضغطات زر المساعدة لكل طفل
if ($hasHelp && $rows) {
    $helpMap = [];
    $r2 = mysqli_query($conn, "SELECT user_id, COUNT(*) AS cnt FROM help_clicks GROUP BY user_id");
    if ($r2) while ($h = mysqli_fetch_assoc($r2)) $helpMap[(int)$h['user_id']] = (int)$h['cnt'];
    foreach ($rows as &$r) $r['help_clicks'] = $helpMap[(int)$r['user_id']] ?? 0;
    unset($r);
}

// ── مجاميع عامة ────────────────────────────────────────────────
$totals = ['ar_letters'=>0,'en_letters'=>0,'ar_numbers'=>0,'en_numbers'=>0,'sections'=>0,'games'=>0,'stories'=>0,'total'=>0,'help_clicks'=>0];
foreach ($rows as $r) foreach (array_keys($totals) as $k) $totals[$k] += (int)($r[$k] ?? 0);
$activeChildren = count($rows);


function timeAgoAr($dt) {
    if (!$dt) return '—';
    $d = time() - strtotime($dt);
    if ($d < 60)    return 'الآن';
    if ($d < 3600)  return 'قبل ' . intval($d/60)    . ' دقيقة';
    if ($d < 86400) return 'قبل ' . intval($d/3600)  . ' ساعة';
    return 'قبل ' . intval($d/86400) . ' يوم';
}
function pill($n, $cls) {
    if ($n <= 0) return '<span style="color:#c0cdd8;font-weight:800;">0</span>';
    return '<span class="pill '.$cls.'">'.(int)$n.'</span>';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تقارير تقدّم الأطفال</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}

/* ── بطاقات المجاميع ── */
.sum-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:22px;}
.sum-card{background:#fff;border-radius:16px;padding:14px 12px;box-shadow:0 6px 18px rgba(33,66,95,.08);text-align:center;}
.sum-icon{font-size:22px;margin-bottom:6px;}
.sum-num{font-size:28px;font-weight:900;color:#21425f;line-height:1;}
.sum-lbl{font-size:11px;font-weight:800;color:#7a90a3;margin-top:4px;}


/* ── جدول التفصيل ── */
.report-wrap{background:#fff;border-radius:18px;box-shadow:0 6px 18px rgba(33,66,95,.08);overflow-x:auto;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;min-width:900px;}
thead th{background:#21425f;color:#fff;padding:12px 10px;font-size:13px;font-weight:900;text-align:center;white-space:nowrap;}
thead th:first-child{border-radius:0 18px 0 0;text-align:right;padding-right:18px;}
thead th:last-child{border-radius:18px 0 0 0;}
tbody td{padding:12px 10px;text-align:center;border-bottom:1px solid #f0f5fb;font-size:13px;}
tbody td:first-child{text-align:right;padding-right:18px;font-weight:900;color:#21425f;font-size:14px;}
tbody tr:hover{background:#f7fbff;}
tbody tr:last-child td{border:none;}

/* pills */
.pill{display:inline-block;min-width:30px;padding:3px 10px;border-radius:999px;font-weight:900;font-size:13px;text-align:center;}
.p-ar {background:#e6f3ff;color:#1a5fa0;}
.p-en {background:#e6ffec;color:#1e7a3a;}
.p-nar{background:#fff6e0;color:#8a5e00;}
.p-nen{background:#f0ebff;color:#5e3fb0;}
.p-sec{background:#fff3d9;color:#9a6b00;}
.p-gam{background:#e0fbfb;color:#007a7a;}
.p-sto{background:#ffe6f5;color:#9a006a;}
.p-tot{background:#21425f;color:#fff;}
.p-help{background:#fff3e8;color:#c2410c;}

.last-time{color:#7a90a3;font-size:12px;font-weight:800;}

.page-title{font-size:24px;font-weight:900;color:#21425f;margin-bottom:20px;}

@media(max-width:600px){.sum-grid{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<div class="page-title">📊 تقارير تقدّم الأطفال</div>

<!-- بطاقات المجاميع -->
<div class="sum-grid">
    <div class="sum-card"><div class="sum-icon">👦</div><div class="sum-num"><?php echo $activeChildren ?></div><div class="sum-lbl">طفل نشِط</div></div>
    <div class="sum-card"><div class="sum-icon"><img src="../assets/icons/ar-letter.png" style="width:22px;height:22px;object-fit:contain;"></div><div class="sum-num"><?php echo $totals['ar_letters'] ?></div><div class="sum-lbl">حروف عربية</div></div>
    <div class="sum-card"><div class="sum-icon"><img src="../assets/icons/en-letter.png" style="width:22px;height:22px;object-fit:contain;"></div><div class="sum-num"><?php echo $totals['en_letters'] ?></div><div class="sum-lbl">حروف إنجليزية</div></div>
    <div class="sum-card"><div class="sum-icon"><img src="../assets/icons/math-ar.png" style="width:22px;height:22px;object-fit:contain;"></div><div class="sum-num"><?php echo $totals['ar_numbers'] ?></div><div class="sum-lbl">أرقام عربية</div></div>
    <div class="sum-card"><div class="sum-icon"><img src="../assets/icons/math-number.png" style="width:22px;height:22px;object-fit:contain;"></div><div class="sum-num"><?php echo $totals['en_numbers'] ?></div><div class="sum-lbl">أرقام إنجليزية</div></div>
    <div class="sum-card"><div class="sum-icon">🌿</div><div class="sum-num"><?php echo $totals['sections'] ?></div><div class="sum-lbl">ثقافة عامة</div></div>
    <div class="sum-card"><div class="sum-icon">🎮</div><div class="sum-num"><?php echo $totals['games'] ?></div><div class="sum-lbl">ألعاب</div></div>
    <div class="sum-card"><div class="sum-icon">📖</div><div class="sum-num"><?php echo $totals['stories'] ?></div><div class="sum-lbl">قصص</div></div>
    <?php if ($hasHelp): ?>
    <div class="sum-card"><div class="sum-icon">🤟</div><div class="sum-num"><?php echo $totals['help_clicks'] ?></div><div class="sum-lbl">ضغطات المساعدة</div></div>
    <?php endif; ?>
</div>


<!-- جدول التفصيل -->
<?php if ($rows): ?>
<div class="report-wrap">
<table>
    <thead>
        <tr>
            <th>الطفل</th>
            <th><img src="../assets/icons/ar-letter.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;"> حروف عربية</th>
            <th><img src="../assets/icons/en-letter.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;"> حروف إنجليزية</th>
            <th><img src="../assets/icons/math-ar.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;"> أرقام عربية</th>
            <th><img src="../assets/icons/math-number.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;"> أرقام إنجليزية</th>
            <th>🌿 ثقافة عامة</th>
            <th>🎮 ألعاب</th>
            <th>📖 قصص</th>
            <th>الإجمالي</th>
            <?php if ($hasHelp): ?><th>🤟 زر المساعدة</th><?php endif; ?>
            <th>آخر نشاط</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r):
        $name = trim((string)$r['child_name']) ?: ('طفل #' . (int)$r['user_id']);
    ?>
    <tr>
        <td><?php echo htmlspecialchars($name) ?></td>
        <td><?php echo pill((int)$r['ar_letters'],  'p-ar') ?></td>
        <td><?php echo pill((int)$r['en_letters'],  'p-en') ?></td>
        <td><?php echo pill((int)$r['ar_numbers'],  'p-nar') ?></td>
        <td><?php echo pill((int)$r['en_numbers'],  'p-nen') ?></td>
        <td><?php echo pill((int)$r['sections'],    'p-sec') ?></td>
        <td><?php echo pill((int)$r['games'],       'p-gam') ?></td>
        <td><?php echo pill((int)$r['stories'],     'p-sto') ?></td>
        <td><?php echo pill((int)$r['total'],       'p-tot') ?></td>
        <?php if ($hasHelp): ?><td><?php echo pill((int)($r['help_clicks'] ?? 0), 'p-help') ?></td><?php endif; ?>
        <td class="last-time"><?php echo timeAgoAr($r['last_activity']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<div style="background:#fff;border-radius:18px;padding:50px;text-align:center;box-shadow:0 6px 18px rgba(33,66,95,.08);">
    <div style="font-size:48px;margin-bottom:14px;">📊</div>
    <div style="font-size:18px;font-weight:900;color:#21425f;">ما في تقدّم مسجّل بعد</div>
    <div style="font-size:14px;color:#7a90a3;font-weight:700;margin-top:8px;">رح تظهر الأرقام أول ما الأطفال يبدأوا يستخدمون المنصة</div>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>
