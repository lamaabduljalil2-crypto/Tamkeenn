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
$category = $_GET['category'] ?? $_POST['category'] ?? 'vegetable';
if (!in_array($category, ['vegetable', 'fruit'], true)) {
    $category = 'vegetable';
}
$_foodPermKey = ($category === 'vegetable') ? 'gc_food_vegetable' : 'gc_food_fruit';
if ($isSupervisor && !supCan($_foodPermKey, 'can_add')) {
    header('Location: supervisor-dashboard.php'); exit;
}

$baseFolder = $category === 'fruit' ? 'fruits' : 'vegetables';
$DIR_IMG = [__DIR__ . "/../images/general/$baseFolder/images/", "images/general/$baseFolder/images/"];
$DIR_VID = [__DIR__ . "/../images/general/$baseFolder/videos/", "images/general/$baseFolder/videos/"];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'الرجاء إدخال اسم الصنف';
    } else {
        $image = uploadFoodFile($_FILES['image'] ?? null, $DIR_IMG[0], $DIR_IMG[1], $IMG_EXT);
        $video = uploadFoodFile($_FILES['video'] ?? null, $DIR_VID[0], $DIR_VID[1], $VID_EXT);

        $maxRes = mysqli_prepare($conn, "SELECT COALESCE(MAX(sort_order),0) AS m FROM food_items WHERE category = ?");
        mysqli_stmt_bind_param($maxRes, "s", $category);
        mysqli_stmt_execute($maxRes);
        $maxRow = mysqli_fetch_assoc(mysqli_stmt_get_result($maxRes));
        $order = intval($maxRow['m']) + 1;

        $supCreator = $isSupervisor ? intval($_SESSION['supervisor_id'] ?? 0) : 0;
        mysqli_query($conn, "ALTER TABLE food_items ADD COLUMN IF NOT EXISTS created_by_supervisor INT DEFAULT 0");
        $ins = mysqli_prepare($conn, "INSERT INTO food_items (category, name, image, video, sort_order, created_by_supervisor) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($ins, "ssssii", $category, $name, $image, $video, $order, $supCreator);
        mysqli_stmt_execute($ins);

        header("Location: manage-food.php?category=" . urlencode($category));
        exit;
    }
}

$catLabel = $category === 'vegetable' ? 'الخضار' : 'الفواكه';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إضافة صنف</title>
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

<h1 class="page-title">إضافة صنف جديد</h1>

<form class="form-box" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">

    <?php if ($error !== ''): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <span class="cat-tag">القسم: <?php echo htmlspecialchars($catLabel); ?></span>

    <label>اسم الصنف</label>
    <input type="text" name="name" required placeholder="مثال: طماطم">
    <div class="hint">التهجئة بالحروف بتتولّد تلقائياً من الاسم.</div>

    <label>صورة الصنف</label>
    <input type="file" name="image" accept="image/*">

    <label>فيديو لغة الإشارة</label>
    <input type="file" name="video" accept="video/*">

    <button type="submit" class="save-btn">حفظ الصنف</button>

</form>

<a href="manage-food.php?category=<?php echo urlencode($category); ?>" class="back-link">
رجوع للقائمة
</a>

</main>
</div>
</body>
</html>
