<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
if (!function_exists('ensure_animals_setup')) {
    function ensure_animals_setup($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS animals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            image VARCHAR(255) NULL,
            video VARCHAR(255) NULL,
            pos_top VARCHAR(20) DEFAULT '30%',
            pos_left VARCHAR(20) DEFAULT '30%',
            width VARCHAR(20) DEFAULT '130px',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        foreach ([
            'category'   => "ALTER TABLE animals ADD COLUMN category VARCHAR(20) NULL",
            'name'       => "ALTER TABLE animals ADD COLUMN name VARCHAR(255) NULL",
            'image'      => "ALTER TABLE animals ADD COLUMN image VARCHAR(255) NULL",
            'video'      => "ALTER TABLE animals ADD COLUMN video VARCHAR(255) NULL",
            'pos_top'    => "ALTER TABLE animals ADD COLUMN pos_top VARCHAR(20) DEFAULT '30%'",
            'pos_left'   => "ALTER TABLE animals ADD COLUMN pos_left VARCHAR(20) DEFAULT '30%'",
            'width'      => "ALTER TABLE animals ADD COLUMN width VARCHAR(20) DEFAULT '130px'",
            'sort_order' => "ALTER TABLE animals ADD COLUMN sort_order INT DEFAULT 0",
        ] as $col => $alterSql) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE '$col'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, $alterSql);
            }
        }
        $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM animals");
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : ['c' => 1];
        if (intval($countRow['c']) === 0) {
            $seed = [
                ['wild','اسد','images/general/animals/items/lion.png','images/general/animals/videos/lion.mp4','36%','30%','210px',1],
                ['wild','نمر','images/general/animals/items/tiger.png','images/general/animals/videos/tiger.mp4','64%','4%','220px',2],
                ['wild','ذئب','images/general/animals/items/wolf.png','images/general/animals/videos/wolf.mp4','36%','5%','140px',3],
                ['wild','ثعلب','images/general/animals/items/fox.png','images/general/animals/videos/fox.mp4','66%','48%','195px',4],
                ['wild','دب','images/general/animals/items/bear.png','images/general/animals/videos/bear.mp4','31%','75%','160px',5],
                ['wild','ثعبان','images/general/animals/items/snake.png','images/general/animals/videos/snake.mp4','81%','30%','120px',6],
                ['wild','تمساح','images/general/animals/items/crocodile.png','images/general/animals/videos/crocodile.mp4','70%','72%','220px',7],
                ['wild','نسر','images/general/animals/items/eagle.png','images/general/animals/videos/eagle.mp4','12%','12%','110px',8],
                ['wild','ضبع','images/general/animals/items/hyena.png','images/general/animals/videos/hyena.mp4','36%','55%','150px',9],
                ['pet','عصفور','images/general/animals/items/bird.png','images/general/animals/videos/bird.mp4','11%','4%','100px',1],
                ['pet','فراشة','images/general/animals/items/butterfly.png','images/general/animals/videos/butterfly.mp4','6%','61%','55px',2],
                ['pet','صوص','images/general/animals/items/chick.png','images/general/animals/videos/chick.mp4','49%','70%','40px',3],
                ['pet','أرنب','images/general/animals/items/rabbit.png','images/general/animals/videos/rabbit.mp4','51%','85%','60px',4],
                ['pet','بطة','images/general/animals/items/duck.png','images/general/animals/videos/duck.mp4','71%','75%','85px',5],
                ['pet','دجاجة','images/general/animals/items/chicken.png','images/general/animals/videos/chicken.mp4','36%','73%','95px',6],
                ['pet','قطة','images/general/animals/items/cat.png','images/general/animals/videos/cat.mp4','42%','20%','55px',7],
                ['pet','كلب','images/general/animals/items/dog.png','images/general/animals/videos/dog.mp4','46%','10%','80px',8],
                ['pet','بقرة','images/general/animals/items/cow.png','images/general/animals/videos/cow.mp4','66%','3%','230px',9],
                ['pet','خروف','images/general/animals/items/sheep.png','images/general/animals/videos/sheep.mp4','68%','23%','160px',10],
                ['pet','سلحفاة','images/general/animals/items/turtle.png','images/general/animals/videos/turtle.mp4','61%','65%','90px',11],
                ['pet','حصان','images/general/animals/items/horse.png','images/general/animals/videos/horse.mp4','26%','39%','200px',12],
                ['pet','حمار','images/general/animals/items/donkey.png','images/general/animals/videos/donkey.mp4','61%','40%','210px',13],
            ];
            $stmt = mysqli_prepare($conn, "INSERT INTO animals (category,name,image,video,pos_top,pos_left,width,sort_order) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($seed as $a) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7]);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}

mysqli_set_charset($conn, 'utf8mb4');
ensure_animals_setup($conn);

$error = '';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

$DIR_ITEMS  = [__DIR__ . '/../images/general/animals/items/',  'images/general/animals/items/'];
$DIR_VIDEOS = [__DIR__ . '/../images/general/animals/videos/', 'images/general/animals/videos/'];
$IMG_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$VID_EXT = ['mp4', 'webm', 'ogg', 'mov'];

function uploadAnimalFile($file, $serverDir, $publicPrefix, $allowed) {
    if (!isset($file) || !isset($file['name']) || $file['name'] === '') return '';
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '';

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return '';

    if (!is_dir($serverDir)) {
        @mkdir($serverDir, 0775, true);
    }

    $base = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    if ($base === '') $base = 'animal';

    $fileName = $base . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    $dest = rtrim($serverDir, '/') . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $publicPrefix . $fileName;
    }
    return '';
}

/* جلب الحيوان */
$stmt = mysqli_prepare($conn, "SELECT * FROM animals WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$animal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$animal) {
    header("Location: manage-animals.php?category=wild");
    exit;
}

$category = $animal['category'];
$_animalPermKey = ($category === 'wild') ? 'gc_animals_wild' : 'gc_animals_pet';
$_ownsAnimal = $isSupervisor && intval($animal['created_by_supervisor'] ?? 0) === intval($_SESSION['supervisor_id'] ?? -1);
if ($isSupervisor && !supCan($_animalPermKey, 'can_edit') && !$_ownsAnimal) {
    header('Location: supervisor-dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'الرجاء إدخال اسم الحيوان';
    } else {
        $image = $animal['image'];
        $video = $animal['video'];

        $newImg = uploadAnimalFile($_FILES['image'] ?? null, $DIR_ITEMS[0], $DIR_ITEMS[1], $IMG_EXT);
        if ($newImg !== '') $image = $newImg;

        $newVid = uploadAnimalFile($_FILES['video'] ?? null, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);
        if ($newVid !== '') $video = $newVid;

        $up = mysqli_prepare($conn, "UPDATE animals SET name = ?, image = ?, video = ? WHERE id = ?");
        mysqli_stmt_bind_param($up, "sssi", $name, $image, $video, $id);
        mysqli_stmt_execute($up);

        header("Location: manage-animals.php?category=" . urlencode($category));
        exit;
    }
}

$catLabel = $category === 'wild' ? 'الحيوانات المفترسة' : 'الحيوانات الأليفة';
$imgPreview = trim((string)($animal['image'] ?? '')) !== '' ? '../' . ltrim($animal['image'], '/') : '';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تعديل حيوان</title>
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

.form-box label{
    display:block;
    color:#21425f;
    font-weight:bold;
    font-size:17px;
    margin:16px 0 8px;
}

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
    width:150px;
    height:120px;
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

<h1 class="page-title">تعديل حيوان</h1>

<form class="form-box" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?php echo intval($id); ?>">

    <?php if ($error !== ''): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <span class="cat-tag">القسم: <?php echo htmlspecialchars($catLabel); ?></span>

    <label>اسم الحيوان</label>
    <input type="text" name="name" required value="<?php echo htmlspecialchars($animal['name']); ?>">
    <div class="hint">التهجئة بالحروف بتتولّد تلقائياً من اسم الحيوان.</div>

    <label>صورة الحيوان</label>
    <?php if ($imgPreview !== ''): ?>
        <img src="<?php echo htmlspecialchars($imgPreview); ?>" class="preview-img" onerror="this.style.display='none';">
    <?php endif; ?>
    <input type="file" name="image" accept="image/*">
    <div class="hint">اتركها فاضية إذا ما بدك تغيّر الصورة.</div>

    <label>فيديو لغة الإشارة</label>
    <input type="file" name="video" accept="video/*">
    <div class="hint">الحالي: <?php echo $animal['video'] ? htmlspecialchars($animal['video']) : 'لا يوجد'; ?>. اتركها فاضية للإبقاء عليه.</div>

    <button type="submit" class="save-btn">حفظ التعديلات</button>

</form>

<a href="manage-animals.php?category=<?php echo urlencode($category); ?>" class="back-link">
رجوع للوحة
</a>

</main>
</div>
</body>
</html>
