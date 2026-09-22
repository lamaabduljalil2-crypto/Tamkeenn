<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

/* فحص صلاحية الدخول للمشرف */
if ($isSupervisor && !supHasAny('stories')) {
    header('Location: supervisor-dashboard.php'); exit;
}

/* تأكيد وجود الجدول */
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
foreach ([
    'cover_image'           => "ALTER TABLE stories ADD COLUMN cover_image VARCHAR(255) NULL",
    'cover_video'           => "ALTER TABLE stories ADD COLUMN cover_video VARCHAR(255) NULL",
    'cover_title'           => "ALTER TABLE stories ADD COLUMN cover_title VARCHAR(500) NULL",
    'created_by_supervisor' => "ALTER TABLE stories ADD COLUMN created_by_supervisor INT DEFAULT 0",
] as $col => $sql) {
    $r = mysqli_query($conn, "SHOW COLUMNS FROM stories LIKE '$col'");
    if ($r && mysqli_num_rows($r) == 0) mysqli_query($conn, $sql);
}

/* حذف — يُسمح إذا كان يملك can_delete أو يملك القصة */
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $ownerRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT created_by_supervisor FROM stories WHERE id=$delId"));
    $_isOwner = $ownerRow && supOwns(intval($ownerRow['created_by_supervisor']));
    if (supCan('stories', 'can_delete') || $_isOwner) {
        mysqli_query($conn, "DELETE FROM stories WHERE id=$delId");
        mysqli_query($conn, "DELETE FROM story_pages WHERE story_id=$delId");
    }
    header('Location: stories.php'); exit;
}

/* جلب القصص */
$stories = [];
$res = mysqli_query($conn, "SELECT * FROM stories ORDER BY sort_order ASC, id ASC");
if ($res) while ($r = mysqli_fetch_assoc($res)) $stories[] = $r;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة القصص</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;gap:12px;flex-wrap:wrap;}
.page-header-title{display:flex;align-items:center;gap:12px;}
.page-header h1{font-size:22px;font-weight:900;color:#21425f;}
.page-header p{font-size:13px;color:#7a90a3;font-weight:700;margin-top:3px;}

.btn-add{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#e05353,#c0392b);color:#fff;border-radius:14px;font-size:14px;font-weight:900;text-decoration:none;box-shadow:0 4px 14px rgba(224,83,83,.35);transition:.2s;}
.btn-add:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(224,83,83,.45);}

.stories-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;}

.story-card{background:#fff;border-radius:20px;box-shadow:0 6px 20px rgba(33,66,95,.08);overflow:hidden;transition:.2s;}
.story-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(33,66,95,.14);}

.story-poster{width:100%;height:185px;object-fit:cover;background:#f0f5fb;display:block;}
.story-poster-placeholder{width:100%;height:185px;background:linear-gradient(135deg,#ffe4ef,#ffd2e3);display:flex;align-items:center;justify-content:center;font-size:48px;}

.story-body{padding:16px;}
.story-title{font-size:16px;font-weight:900;color:#21425f;margin-bottom:6px;}
.story-link-val{font-size:12px;color:#7a90a3;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:12px;}

.story-actions{display:flex;gap:8px;}
.btn-edit{flex:1;padding:9px;border-radius:10px;background:#e8f3ff;color:#1f5f9a;font-size:12px;font-weight:900;text-decoration:none;text-align:center;transition:.18s;}
.btn-edit:hover{background:#2a84c9;color:#fff;}
.btn-delete{flex:1;padding:9px;border-radius:10px;background:#ffe8e8;color:#c0392b;font-size:12px;font-weight:900;text-decoration:none;text-align:center;border:none;cursor:pointer;transition:.18s;font-family:Arial;}
.btn-delete:hover{background:#e05353;color:#fff;}

.empty-state{background:#fff;border-radius:20px;padding:60px 30px;text-align:center;box-shadow:0 6px 20px rgba(33,66,95,.08);}
.empty-state .icon{font-size:52px;margin-bottom:14px;}
.empty-state p{font-size:16px;font-weight:900;color:#21425f;margin-bottom:6px;}
.empty-state small{font-size:13px;color:#7a90a3;font-weight:700;}

.badge-count{background:#e8f3ff;color:#1f5f9a;font-size:12px;font-weight:900;padding:4px 12px;border-radius:999px;}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<div class="page-header">
    <div class="page-header-title">
        <div>
            <h1>📖 إدارة القصص</h1>
            <p>إجمالي القصص: <span class="badge-count"><?php echo count($stories) ?></span></p>
        </div>
    </div>
    <?php if (supCan('stories', 'can_add')): ?>
    <a href="add-story.php" class="btn-add">+ إضافة قصة</a>
    <?php endif; ?>
</div>

<?php if (empty($stories)): ?>
<div class="empty-state">
    <div class="icon">📖</div>
    <p>لا توجد قصص بعد</p>
    <small>أضف أول قصة للأطفال</small>
</div>

<?php else: ?>
<div class="stories-grid">
<?php foreach ($stories as $s):
    $poster = trim($s['poster_image'] ?? '');
    $link   = trim($s['story_link']   ?? '');
?>
<div class="story-card">
    <?php if ($poster): ?>
    <img src="../<?php echo htmlspecialchars(ltrim($poster,'/')); ?>" class="story-poster" alt="">
    <?php else: ?>
    <div class="story-poster-placeholder">📖</div>
    <?php endif; ?>

    <div class="story-body">
        <div class="story-title"><?php echo htmlspecialchars($s['title']); ?></div>
        <?php if ($link): ?>
        <div class="story-link-val">🔗 <?php echo htmlspecialchars($link); ?></div>
        <?php endif; ?>

        <?php
        $_storyOwned = supOwns(intval($s['created_by_supervisor'] ?? 0));
        $_canEdit    = supCan('stories', 'can_edit') || $_storyOwned;
        $_canDel     = supCan('stories', 'can_delete') || $_storyOwned;
        ?>
        <?php if ($_canEdit || $_canDel): ?>
        <div class="story-actions">
            <?php if ($_canEdit): ?>
            <a href="edit-story.php?id=<?php echo intval($s['id']); ?>" class="btn-edit">✏️ تعديل</a>
            <?php endif; ?>
            <?php if ($_canDel): ?>
            <a href="stories.php?delete=<?php echo intval($s['id']); ?>"
               class="btn-delete"
               onclick="return confirm('هل تريد حذف هذه القصة نهائياً؟');">🗑️ حذف</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>
