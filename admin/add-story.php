<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

$subject = 'الثقافة العامة';
$type = 'القصص';
$error = '';

/* تأكيد وجود الجداول */
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
    'title'        => "ALTER TABLE stories ADD COLUMN title VARCHAR(255) NULL",
    'poster_image' => "ALTER TABLE stories ADD COLUMN poster_image VARCHAR(255) NULL",
    'sign_video'   => "ALTER TABLE stories ADD COLUMN sign_video VARCHAR(255) NULL",
    'story_link'   => "ALTER TABLE stories ADD COLUMN story_link VARCHAR(255) NULL",
    'sort_order'   => "ALTER TABLE stories ADD COLUMN sort_order INT DEFAULT 0",
    'cover_image'           => "ALTER TABLE stories ADD COLUMN cover_image VARCHAR(255) NULL",
    'cover_video'           => "ALTER TABLE stories ADD COLUMN cover_video VARCHAR(255) NULL",
    'cover_title'           => "ALTER TABLE stories ADD COLUMN cover_title VARCHAR(500) NULL",
    'created_by_supervisor' => "ALTER TABLE stories ADD COLUMN created_by_supervisor INT DEFAULT 0",
] as $col => $alterSql) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM stories LIKE '$col'");
    if ($colCheck && mysqli_num_rows($colCheck) == 0) {
        mysqli_query($conn, $alterSql);
    }
}
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS story_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    page_order INT DEFAULT 0,
    image VARCHAR(255) NULL,
    sign_video VARCHAR(255) NULL,
    caption VARCHAR(1000) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/* مسارات الرفع على السيرفر والمسارات العامة المخزنة بقاعدة البيانات */
$DIR_POSTER  = [__DIR__ . '/../images/general/stories/images/poster/', 'images/general/stories/images/poster/'];
$DIR_IMAGES  = [__DIR__ . '/../images/general/stories/uploads/',       'images/general/stories/uploads/'];
$DIR_VIDEOS  = [__DIR__ . '/../images/general/stories/videos/',        'images/general/stories/videos/'];
$DIR_CARDVID = [__DIR__ . '/../assets/videos/',                        'assets/videos/'];

$IMG_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$VID_EXT = ['mp4', 'webm', 'ogg', 'mov'];

/* دالة رفع ملف مفرد */
function uploadStoryFile($file, $serverDir, $publicPrefix, $allowed) {
    if (!isset($file) || !isset($file['name']) || $file['name'] === '') return '';
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return '';

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return '';

    if (!is_dir($serverDir)) {
        @mkdir($serverDir, 0775, true);
    }

    $base = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    if ($base === '') $base = 'file';

    $fileName = $base . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    $dest = rtrim($serverDir, '/') . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $publicPrefix . $fileName;
    }
    return '';
}

/* دالة رفع ملف من حقل متعدد (array) حسب الترتيب */
function uploadIndexed($field, $idx, $serverDir, $publicPrefix, $allowed) {
    if (!isset($_FILES[$field]) || !isset($_FILES[$field]['name'][$idx])) return '';
    $file = [
        'name'     => $_FILES[$field]['name'][$idx],
        'tmp_name' => $_FILES[$field]['tmp_name'][$idx],
        'error'    => $_FILES[$field]['error'][$idx],
    ];
    return uploadStoryFile($file, $serverDir, $publicPrefix, $allowed);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $cover_title = trim($_POST['cover_title'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);

    if ($title === '') {
        $error = 'الرجاء إدخال اسم القصة';
    } else {

        $posterPath = uploadStoryFile($_FILES['poster'] ?? null, $DIR_POSTER[0], $DIR_POSTER[1], $IMG_EXT);
        $cardVideo  = uploadStoryFile($_FILES['card_sign_video'] ?? null, $DIR_CARDVID[0], $DIR_CARDVID[1], $VID_EXT);
        $coverImage = uploadStoryFile($_FILES['cover_image'] ?? null, $DIR_IMAGES[0], $DIR_IMAGES[1], $IMG_EXT);
        $coverVideo = uploadStoryFile($_FILES['cover_video'] ?? null, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);

        /* 1) إنشاء القصة */
        $supCreator = $isSupervisor ? intval($_SESSION['supervisor_id'] ?? 0) : 0;
        $ins = mysqli_prepare(
            $conn,
            "INSERT INTO stories (title, poster_image, sign_video, cover_image, cover_video, cover_title, story_link, sort_order, created_by_supervisor)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $tmpLink = '';
        mysqli_stmt_bind_param($ins, "sssssssii", $title, $posterPath, $cardVideo, $coverImage, $coverVideo, $cover_title, $tmpLink, $sort_order, $supCreator);
        mysqli_stmt_execute($ins);

        $storyId = mysqli_insert_id($conn);

        /* 2) ربط الرابط بصفحة العرض الديناميكية */
        $link = 'story.php?id=' . $storyId;
        $upLink = mysqli_prepare($conn, "UPDATE stories SET story_link = ? WHERE id = ?");
        mysqli_stmt_bind_param($upLink, "si", $link, $storyId);
        mysqli_stmt_execute($upLink);

        /* 3) إضافة الصفحات */
        $captions = $_POST['page_caption'] ?? [];
        $order = 0;

        foreach ($captions as $idx => $cap) {
            $cap = trim((string)$cap);

            $pageImg = uploadIndexed('page_image', $idx, $DIR_IMAGES[0], $DIR_IMAGES[1], $IMG_EXT);
            $pageVid = uploadIndexed('page_video', $idx, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);

            /* تجاهل الصفحة الفاضية تماماً */
            if ($cap === '' && $pageImg === '' && $pageVid === '') continue;

            $order++;
            $pIns = mysqli_prepare(
                $conn,
                "INSERT INTO story_pages (story_id, page_order, image, sign_video, caption) VALUES (?,?,?,?,?)"
            );
            mysqli_stmt_bind_param($pIns, "iisss", $storyId, $order, $pageImg, $pageVid, $cap);
            mysqli_stmt_execute($pIns);
        }

        header("Location: subject-lessons.php?subject=" . urlencode($subject) . "&type=" . urlencode($type));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إضافة قصة جديدة</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.form-box{
    background:#fff;
    border-radius:26px;
    padding:30px;
    max-width:760px;
    margin:30px auto 0;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.form-box h3{
    color:#21425f;
    font-size:20px;
    margin:26px 0 6px;
    border-top:2px dashed #e1ecf7;
    padding-top:20px;
}

.form-box h3.first{
    border-top:none;
    padding-top:0;
    margin-top:0;
}

.form-box label{
    display:block;
    color:#21425f;
    font-weight:bold;
    font-size:16px;
    margin:14px 0 7px;
}

.form-box input[type=text],
.form-box input[type=number],
.form-box input[type=file]{
    width:100%;
    padding:11px 13px;
    border:2px solid #d8e6f5;
    border-radius:13px;
    font-size:15px;
    font-family:inherit;
    background:#f7fbff;
}

.form-box .hint{
    color:#6b7f91;
    font-size:13px;
    font-weight:bold;
    margin-top:5px;
}

.page-row{
    background:#f3f9ff;
    border:2px solid #e1ecf7;
    border-radius:18px;
    padding:16px 16px 6px;
    margin-top:16px;
    position:relative;
}

.page-row .row-title{
    font-weight:900;
    color:#21425f;
    font-size:16px;
    margin-bottom:4px;
}

.remove-page{
    position:absolute;
    top:12px;
    left:12px;
    background:#ffe1de;
    color:#c0392b;
    border:none;
    border-radius:10px;
    padding:6px 12px;
    font-weight:bold;
    cursor:pointer;
    font-family:inherit;
    font-size:14px;
}

.add-page-btn{
    margin-top:16px;
    background:#28b978;
    color:#fff;
    border:none;
    border-radius:13px;
    padding:11px 22px;
    font-size:15px;
    font-weight:900;
    cursor:pointer;
    font-family:inherit;
}

.save-btn{
    display:block;
    width:100%;
    margin-top:26px;
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

<h1 class="page-title">إضافة قصة جديدة</h1>

<form class="form-box" method="POST" enctype="multipart/form-data">

    <?php if ($error !== ''): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h3 class="first">معلومات القصة</h3>

    <label>اسم القصة</label>
    <input type="text" name="title" required placeholder="مثال: رحلتي إلى المدرسة">

    <label>صورة البطاقة (تظهر بقائمة القصص للطفل)</label>
    <input type="file" name="poster" accept="image/*">

    <label>🤟 فيديو القسم الخارجي (بالإشارة)</label>
    <input type="file" name="card_sign_video" accept="video/*">
    <div class="hint">يطلع بمساعد لغة الإشارة بصفحة القائمة لما يمرّ الطفل على البطاقة.</div>

    <label>ترتيب العرض</label>
    <input type="number" name="sort_order" value="0">
    <div class="hint">الرقم الأصغر بيظهر أول.</div>

    <h3>الغلاف</h3>

    <label>صورة الغلاف (داخل الكتاب)</label>
    <input type="file" name="cover_image" accept="image/*">

    <label>🤟 فيديو الغلاف  (بالإشارة)</label>
    <input type="file" name="cover_video" accept="video/*">

    <label>نص الغلاف</label>
    <input type="text" name="cover_title" value="اضغط على الغلاف لفتح القصة">

    <h3>صفحات القصة</h3>
    <div class="hint">أضف صفحة لكل لوحة من القصة. كل صفحة فيها صورة + فيديو لغة الإشارة + النص اللي بيظهر بجنب الفيديو.</div>

    <div id="pagesContainer"></div>

    <button type="button" class="add-page-btn" id="addPageBtn">+ إضافة صفحة</button>

    <button type="submit" class="save-btn">حفظ القصة</button>

</form>

<a
href="subject-lessons.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="back-link">
رجوع للقصص
</a>

</main>
</div>

<script>
const pagesContainer = document.getElementById('pagesContainer');
const addPageBtn = document.getElementById('addPageBtn');
let pageCount = 0;

function makePageRow() {
    pageCount++;
    const row = document.createElement('div');
    row.className = 'page-row';
    row.innerHTML = `
        <button type="button" class="remove-page">حذف</button>
        <div class="row-title">صفحة ${pageCount}</div>

        <label>نص الصفحة</label>
        <input type="text" name="page_caption[]" placeholder="مثال: ذهبت ليلى إلى المدرسة صباحاً">

        <label>صورة الصفحة</label>
        <input type="file" name="page_image[]" accept="image/*">

        <label>فيديو لغة الإشارة (اختياري)</label>
        <input type="file" name="page_video[]" accept="video/*">
    `;
    row.querySelector('.remove-page').addEventListener('click', function () {
        row.remove();
        renumber();
    });
    pagesContainer.appendChild(row);
}

function renumber() {
    const titles = pagesContainer.querySelectorAll('.row-title');
    titles.forEach((t, i) => { t.textContent = 'صفحة ' + (i + 1); });
}

addPageBtn.addEventListener('click', makePageRow);

/* نبدأ بصفحة وحدة جاهزة */
makePageRow();
</script>
</body>
</html>
