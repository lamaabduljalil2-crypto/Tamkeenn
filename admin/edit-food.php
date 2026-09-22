<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

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
    }
}
ensure_food_setup($conn);

$error = '';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$IMG_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$VID_EXT = ['mp4', 'webm', 'ogg', 'mov'];

function uploadFoodFile($file, $serverDir, $publicPrefix, $allowed) {
    if (!isset($file) || !isset($file['name']) || $file['name'] === '') return '';
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '';

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return '';

    if (!is_dir($serverDir)) {
        @mkdir($serverDir, 0775, true);
    }

    $base = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    if ($base === '') $base = 'item';

    $fileName = $base . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    $dest = rtrim($serverDir, '/') . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $publicPrefix . $fileName;
    }
    return '';
}

/* جلب الصنف */
$stmt = mysqli_prepare($conn, "SELECT * FROM food_items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$item) {
    header("Location: manage-food.php?category=vegetable");
    exit;
}

$category = $item['category'];
$_foodPermKey = ($category === 'vegetable') ? 'gc_food_vegetable' : 'gc_food_fruit';
$_ownsFood = $isSupervisor && intval($item['created_by_supervisor'] ?? 0) === intval($_SESSION['supervisor_id'] ?? -1);
if ($isSupervisor && !supCan($_foodPermKey, 'can_edit') && !$_ownsFood) {
    header('Location: supervisor-dashboard.php'); exit;
}
$baseFolder = $category === 'fruit' ? 'fruits' : 'vegetables';
$DIR_IMG = [__DIR__ . "/../images/general/$baseFolder/images/", "images/general/$baseFolder/images/"];
$DIR_VID = [__DIR__ . "/../images/general/$baseFolder/videos/", "images/general/$baseFolder/videos/"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'الرجاء إدخال اسم الصنف';
    } else {
        $image = $item['image'];
        $video = $item['video'];

        $newImg = uploadFoodFile($_FILES['image'] ?? null, $DIR_IMG[0], $DIR_IMG[1], $IMG_EXT);
        if ($newImg !== '') $image = $newImg;

        $newVid = uploadFoodFile($_FILES['video'] ?? null, $DIR_VID[0], $DIR_VID[1], $VID_EXT);
        if ($newVid !== '') $video = $newVid;

        $up = mysqli_prepare($conn, "UPDATE food_items SET name = ?, image = ?, video = ? WHERE id = ?");
        mysqli_stmt_bind_param($up, "sssi", $name, $image, $video, $id);
        mysqli_stmt_execute($up);

        header("Location: manage-food.php?category=" . urlencode($category));
        exit;
    }
}

$catLabel = $category === 'vegetable' ? 'الخضار' : 'الفواكه';
$imgPreview = trim((string)($item['image'] ?? '')) !== '' ? '../' . ltrim($item['image'], '/') : '';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تعديل صنف</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.form-box{
    background:#fff;
    border-radius:26px;
    padding:32px;
    max-width:620px;
    margin:30px auto 0;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}
.form-box label{ display:block; color:#21425f; font-weight:bold; font-size:17px; margin:16px 0 8px; }
.form-box input[type=text],
.form-box input[type=file]{
    width:100%;
    padding:12px 14px;
    border:2px solid #d8e6f5;
    border-radius:14px;
    font-size:16px;
    font-family:inherit;
    background:#f7fbff;
}
.form-box .hint{color:#6b7f91;font-size:13px;font-weight:bold;margin-top:6px;}
.preview-img{
    width:130px;
    height:130px;
    object-fit:contain;
    border-radius:14px;
    margin-top:8px;
    border:2px solid #d8e6f5;
    background:#f7fbff;
}
.cat-tag{
    display:inline-block;
    background:#e8f3ff;
    color:#21425f;
    font-weight:900;
    padding:6px 16px;
    border-radius:999px;
    margin-bottom:6px;
}
.save-btn{
    display:block;
    width:100%;
    margin-top:24px;
    background:#21425f;
    color:#fff;
    border:none;
    padding:14px;
    border-radius:14px;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    font-family:inherit;
}
.error-box{
    background:#ffe8e6;
    color:#b3261e;
    border-radius:14px;
    padding:12px 16px;
    font-weight:bold;
    margin-bottom:10px;
}
.page-title{text-align:center;width:100%;}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">تعديل صنف</h1>

<form class="form-box" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?php echo intval($id); ?>">

    <?php if ($error !== ''): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <span class="cat-tag">القسم: <?php echo htmlspecialchars($catLabel); ?></span>

    <label>اسم الصنف</label>
    <input type="text" name="name" required value="<?php echo htmlspecialchars($item['name']); ?>">
    <div class="hint">التهجئة بالحروف بتتولّد تلقائياً من الاسم.</div>

    <label>صورة الصنف</label>
    <?php if ($imgPreview !== ''): ?>
        <img src="<?php echo htmlspecialchars($imgPreview); ?>" class="preview-img" onerror="this.style.display='none';">
    <?php endif; ?>
    <input type="file" name="image" accept="image/*">
    <div class="hint">اتركها فاضية إذا ما بدك تغيّر الصورة.</div>

    <label>فيديو لغة الإشارة</label>
    <input type="file" name="video" accept="video/*">
    <div class="hint">الحالي: <?php echo $item['video'] ? htmlspecialchars($item['video']) : 'لا يوجد'; ?>. اتركها فاضية للإبقاء عليه.</div>

    <button type="submit" class="save-btn">حفظ التعديلات</button>

</form>

<a href="manage-food.php?category=<?php echo urlencode($category); ?>" class="back-link">
رجوع للقائمة
</a>

</main>
</div>
</body>
</html>
