<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

$tables = ['progress', 'lesson_progress', 'game_progress', 'story_progress', 'general_section_progress'];
$results = [];
foreach ($tables as $t) {
    $ok = mysqli_query($conn, "TRUNCATE TABLE `$t`");
    $results[] = ['table' => $t, 'ok' => $ok, 'err' => $ok ? '' : mysqli_error($conn)];
}

@unlink(__FILE__);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تصفير التقارير</title>
<style>
body{font-family:Arial,sans-serif;background:#f0f5fb;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.box{background:#fff;border-radius:20px;padding:36px 44px;box-shadow:0 8px 30px rgba(0,0,0,.1);text-align:center;max-width:460px;width:90%;}
h2{color:#21425f;margin-bottom:22px;font-size:22px;}
.row{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f0f5fb;font-size:15px;font-weight:800;color:#21425f;}
.ok{color:#28a75d;font-size:18px;} .err{color:#e05353;font-size:13px;}
a{display:inline-block;margin-top:24px;background:#21425f;color:#fff;padding:13px 32px;border-radius:14px;text-decoration:none;font-weight:900;font-size:15px;}
a:hover{background:#2a84c9;}
</style>
</head>
<body>
<div class="box">
    <h2>🗑️ تصفير جميع التقارير</h2>
    <?php foreach ($results as $r): ?>
    <div class="row">
        <span><?php echo htmlspecialchars($r['table']); ?></span>
        <?php if ($r['ok']): ?>
            <span class="ok">✅ تم</span>
        <?php else: ?>
            <span class="err">⚠️ <?php echo htmlspecialchars($r['err']); ?></span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <a href="dashboard.php">← العودة للداش بورد</a>
</div>
</body>
</html>
