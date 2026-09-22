<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
$category = $_GET['category'] ?? 'fruit';
$foodPermKey = ($category === 'vegetable') ? 'gc_food_vegetable' : 'gc_food_fruit';
if ($isSupervisor && !supHasAny($foodPermKey)) {
    header('Location: supervisor-dashboard.php'); exit;
}
mysqli_set_charset($conn, 'utf8mb4');

if (!function_exists('ensure_food_setup')) {
    function ensure_food_setup($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS food_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            image VARCHAR(255) NULL,
            video VARCHAR(255) NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        foreach ([
            'category'   => "ALTER TABLE food_items ADD COLUMN category VARCHAR(20) NULL",
            'name'       => "ALTER TABLE food_items ADD COLUMN name VARCHAR(255) NULL",
            'image'      => "ALTER TABLE food_items ADD COLUMN image VARCHAR(255) NULL",
            'video'      => "ALTER TABLE food_items ADD COLUMN video VARCHAR(255) NULL",
            'sort_order' => "ALTER TABLE food_items ADD COLUMN sort_order INT DEFAULT 0",
        ] as $col => $alterSql) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM food_items LIKE '$col'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, $alterSql);
            }
        }
        $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM food_items");
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : ['c' => 1];
        if (intval($countRow['c']) === 0) {
            $seed = [
                ['vegetable','طماطم','images/general/vegetables/images/tomato.png','images/general/vegetables/videos/tomato.mp4',1],
                ['vegetable','خيار','images/general/vegetables/images/cucumber.png','images/general/vegetables/videos/cucumber.mp4',2],
                ['vegetable','جزر','images/general/vegetables/images/carrot.png','images/general/vegetables/videos/carrot.mp4',3],
                ['vegetable','بطاطا','images/general/vegetables/images/potato.png','images/general/vegetables/videos/potato.mp4',4],
                ['vegetable','باذنجان','images/general/vegetables/images/eggplant.png','images/general/vegetables/videos/eggplant.mp4',5],
                ['vegetable','فلفل','images/general/vegetables/images/pepper.png','images/general/vegetables/videos/pepper.mp4',6],
                ['vegetable','بصل','images/general/vegetables/images/onion.png','images/general/vegetables/videos/onion.mp4',7],
                ['vegetable','خس','images/general/vegetables/images/lettuce.png','images/general/vegetables/videos/lettuce.mp4',8],
                ['vegetable','كوسا','images/general/vegetables/images/zucchini.png','images/general/vegetables/videos/zucchini.mp4',9],
                ['vegetable','ثوم','images/general/vegetables/images/garlic.png','images/general/vegetables/videos/garlic.mp4',10],
                ['vegetable','ذرة','images/general/vegetables/images/corn.png','images/general/vegetables/videos/corn.mp4',11],
                ['vegetable','ليمون','images/general/vegetables/images/lemon.png','images/general/vegetables/videos/lemon.mp4',12],
                ['fruit','خوخ','images/general/fruits/images/peach.png','images/general/fruits/videos/peach.mp4',1],
                ['fruit','برتقال','images/general/fruits/images/orange.png','images/general/fruits/videos/orange.mp4',2],
                ['fruit','بطيخ','images/general/fruits/images/melon.png','images/general/fruits/videos/melon.mp4',3],
                ['fruit','مانجا','images/general/fruits/images/mango.png','images/general/fruits/videos/mango.mp4',4],
                ['fruit','كيوي','images/general/fruits/images/kiwi.png','images/general/fruits/videos/kiwi.mp4',5],
                ['fruit','عنب','images/general/fruits/images/grape.png','images/general/fruits/videos/grape.mp4',6],
                ['fruit','تين','images/general/fruits/images/fig.png','images/general/fruits/videos/fig.mp4',7],
                ['fruit','موز','images/general/fruits/images/banana.png','images/general/fruits/videos/banana.mp4',8],
                ['fruit','تفاح','images/general/fruits/images/apple.png','images/general/fruits/videos/apple.mp4',9],
                ['fruit','جوز الهند','images/general/fruits/images/coconut.png','images/general/fruits/videos/coconut.mp4',10],
                ['fruit','فراولة','images/general/fruits/images/strawberry.png','images/general/fruits/videos/strawberry.mp4',11],
                ['fruit','اجاص','images/general/fruits/images/pear.png','images/general/fruits/videos/pear.mp4',12],
            ];
            $stmt = mysqli_prepare($conn, "INSERT INTO food_items (category,name,image,video,sort_order) VALUES (?,?,?,?,?)");
            foreach ($seed as $f) {
                mysqli_stmt_bind_param($stmt, "ssssi", $f[0], $f[1], $f[2], $f[3], $f[4]);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}
ensure_food_setup($conn);

$subject = 'الثقافة العامة';
$type = 'فواكه وخضروات';

$category = $_GET['category'] ?? 'vegetable';
if (!in_array($category, ['vegetable', 'fruit'], true)) {
    $category = 'vegetable';
}

/* حذف صنف */
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $ownerCheck = mysqli_query($conn, "SELECT created_by_supervisor FROM food_items WHERE id=$delId");
    $ownerRow   = $ownerCheck ? mysqli_fetch_assoc($ownerCheck) : null;
    $_isOwner   = $isSupervisor && $ownerRow && intval($ownerRow['created_by_supervisor']) === intval($_SESSION['supervisor_id'] ?? -1);
    if ($isSupervisor && !supCan($foodPermKey, 'can_delete') && !$_isOwner) {
        header("Location: supervisor-dashboard.php"); exit;
    }
    $d = mysqli_prepare($conn, "DELETE FROM food_items WHERE id = ? AND category = ?");
    mysqli_stmt_bind_param($d, "is", $delId, $category);
    mysqli_stmt_execute($d);
    header("Location: manage-food.php?category=" . urlencode($category));
    exit;
}

/* جلب الأصناف */
$items = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM food_items WHERE category = ? ORDER BY sort_order ASC, id ASC");
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $items[] = $r;
}

$catLabel = $category === 'vegetable' ? 'الخضار' : 'الفواكه';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة <?php echo htmlspecialchars($catLabel); ?></title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.page-title{text-align:center;width:100%;}

.food-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
    gap:24px;
    margin-top:30px;
    max-width:1150px;
    margin-left:auto;
    margin-right:auto;
}

.food-card{
    background:#fff;
    border-radius:26px;
    padding:20px 16px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.food-circle{
    width:120px;
    height:120px;
    border-radius:50%;
    background:#f1f8ff;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 14px;
    overflow:hidden;
    box-shadow:0 8px 18px rgba(0,0,0,.06);
}

.food-circle img{
    width:74%;
    height:74%;
    object-fit:contain;
}

.food-card h3{
    color:#21425f;
    font-size:22px;
    margin-bottom:14px;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
}

.edit-btn,.delete-btn{
    padding:9px 18px;
    border-radius:12px;
    text-decoration:none;
    font-weight:bold;
    font-size:15px;
}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin:24px auto;
    text-align:center;
    color:#21425f;
    font-size:18px;
    font-weight:bold;
    max-width:1000px;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
@media(max-width:700px){.food-grid{grid-template-columns:repeat(2,1fr);gap:12px;}}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">إدارة <?php echo htmlspecialchars($catLabel); ?></h1>

<?php if (!$isSupervisor || supCan($foodPermKey, 'can_add')): ?>
<a href="add-food.php?category=<?php echo urlencode($category); ?>" class="add-btn">
+ إضافة صنف جديد
</a>
<?php endif; ?>

<?php if (count($items) > 0): ?>

<div class="food-grid">
    <?php foreach ($items as $it): ?>
    <div class="food-card">
        <div class="food-circle">
            <img src="../<?php echo htmlspecialchars(ltrim((string)$it['image'], '/')); ?>"
                 alt="<?php echo htmlspecialchars($it['name']); ?>"
                 onerror="this.style.opacity=.3;">
        </div>
        <h3><?php echo htmlspecialchars($it['name']); ?></h3>
        <?php $_owns = $isSupervisor && intval($it['created_by_supervisor'] ?? 0) === intval($_SESSION['supervisor_id'] ?? -1); ?>
        <div class="card-actions">
            <?php if (!$isSupervisor || supCan($foodPermKey, 'can_edit') || $_owns): ?>
            <a class="edit-btn" href="edit-food.php?id=<?php echo intval($it['id']); ?>">تعديل</a>
            <?php endif; ?>
            <?php if (!$isSupervisor || supCan($foodPermKey, 'can_delete') || $_owns): ?>
            <a class="delete-btn"
               href="manage-food.php?category=<?php echo urlencode($category); ?>&delete=<?php echo intval($it['id']); ?>"
               onclick="return confirm('هل أنت متأكد من حذف الصنف؟');">حذف</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<div class="empty-box">لا يوجد أصناف في هذا القسم بعد. اضغط "إضافة صنف جديد".</div>

<?php endif; ?>

<a
href="subject-lessons.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="back-link">
رجوع لأقسام الفواكه والخضار
</a>

</main>
</div>
</body>
</html>
