<?php
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

$results = [];
$total   = 0;

// الجداول المرتبطة بـ child_id
$byChildId = ['chat_messages', 'lesson_progress', 'game_progress', 'story_progress', 'general_section_progress'];

foreach ($byChildId as $table) {
    $chk = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    if (!$chk || mysqli_num_rows($chk) === 0) continue;

    // جلب IDs الموجودة في الجدول لكن مش في children
    $ids = mysqli_query($conn, "SELECT DISTINCT child_id FROM `{$table}` WHERE child_id NOT IN (SELECT id FROM children)");
    if (!$ids || mysqli_num_rows($ids) === 0) {
        $results[] = ['table' => $table, 'count' => 0, 'note' => 'لا توجد بيانات يتيمة'];
        continue;
    }

    $orphanIds = [];
    while ($row = mysqli_fetch_assoc($ids)) $orphanIds[] = intval($row['child_id']);
    $idList = implode(',', $orphanIds);

    $del = mysqli_query($conn, "DELETE FROM `{$table}` WHERE child_id IN ({$idList})");
    $count = $del ? mysqli_affected_rows($conn) : 0;
    $total += $count;
    $results[] = ['table' => $table, 'count' => $count, 'note' => "أطفال: " . implode(', ', $orphanIds)];
}

// جدول progress مرتبط بـ user_id
$chk = mysqli_query($conn, "SHOW TABLES LIKE 'progress'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $ids = mysqli_query($conn, "SELECT DISTINCT user_id FROM progress WHERE user_id NOT IN (SELECT id FROM children)");
    if ($ids && mysqli_num_rows($ids) > 0) {
        $orphanIds = [];
        while ($row = mysqli_fetch_assoc($ids)) $orphanIds[] = intval($row['user_id']);
        $idList = implode(',', $orphanIds);
        $del = mysqli_query($conn, "DELETE FROM progress WHERE user_id IN ({$idList})");
        $count = $del ? mysqli_affected_rows($conn) : 0;
        $total += $count;
        $results[] = ['table' => 'progress', 'count' => $count, 'note' => "أطفال: " . implode(', ', $orphanIds)];
    } else {
        $results[] = ['table' => 'progress', 'count' => 0, 'note' => 'لا توجد بيانات يتيمة'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تنظيف البيانات</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;padding:40px 20px;}
.box{background:#fff;border-radius:20px;padding:32px;max-width:640px;margin:auto;
     box-shadow:0 6px 24px rgba(33,66,95,.12);}
h1{color:#21425f;font-size:22px;font-weight:900;margin-bottom:6px;}
.sub{color:#7a90a3;font-size:14px;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;margin-bottom:20px;}
th{background:#f0f5fb;padding:10px 14px;font-size:12px;font-weight:900;color:#7a90a3;text-align:right;}
td{padding:10px 14px;border-bottom:1px solid #f0f5fb;font-size:13px;color:#21425f;}
.cnt-good{color:#28a75d;font-weight:900;}
.cnt-zero{color:#aaa;}
.total{background:#e8fff0;border-radius:12px;padding:14px 18px;color:#1a7a40;
       font-weight:900;font-size:15px;margin-bottom:20px;}
.btn{display:inline-block;padding:12px 28px;background:#21425f;color:#fff;
     border-radius:12px;text-decoration:none;font-weight:900;font-size:14px;}
.btn:hover{background:#2a84c9;}
</style>
</head>
<body>
<div class="box">
    <h1>🧹 تنظيف بيانات الأطفال المحذوفين</h1>
    <p class="sub">حذف كل البيانات المرتبطة بأطفال تم حذفهم من النظام</p>

    <div class="total">
        ✅ إجمالي السجلات المحذوفة: <?= $total ?>
    </div>

    <table>
        <tr>
            <th>الجدول</th>
            <th>سجلات محذوفة</th>
            <th>تفاصيل</th>
        </tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['table']) ?></td>
            <td class="<?= $r['count'] > 0 ? 'cnt-good' : 'cnt-zero' ?>">
                <?= $r['count'] ?>
            </td>
            <td style="color:#7a90a3;font-size:12px;"><?= htmlspecialchars($r['note']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <a href="dashboard.php" class="btn">← العودة للوحة التحكم</a>
</div>
</body>
</html>
