<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

$subject = 'الثقافة العامة';
$type = 'القصص';
$error = '';

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

/* مسارات الرفع */
$DIR_POSTER  = [__DIR__ . '/../images/general/stories/images/poster/', 'images/general/stories/images/poster/'];
$DIR_IMAGES  = [__DIR__ . '/../images/general/stories/uploads/',       'images/general/stories/uploads/'];
$DIR_VIDEOS  = [__DIR__ . '/../images/general/stories/videos/',        'images/general/stories/videos/'];
$DIR_CARDVID = [__DIR__ . '/../assets/videos/',                        'assets/videos/'];

$IMG_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
$VID_EXT = ['mp4', 'webm', 'ogg', 'mov'];

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

function uploadKeyed($field, $key, $serverDir, $publicPrefix, $allowed) {
    if (!isset($_FILES[$field]) || !isset($_FILES[$field]['name'][$key])) return '';
    $file = [
        'name'     => $_FILES[$field]['name'][$key],
        'tmp_name' => $_FILES[$field]['tmp_name'][$key],
        'error'    => $_FILES[$field]['error'][$key],
    ];
    return uploadStoryFile($file, $serverDir, $publicPrefix, $allowed);
}

function pubPath($p) {
    $p = trim((string)$p);
    if ($p === '') return '';
    return '../' . ltrim($p, '/');
}

/* التأكد من وجود الجداول وكل الأعمدة (لو الجدول قديم وناقص) */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NULL,
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

/* جلب القصة */
$stmt = mysqli_prepare($conn, "SELECT * FROM stories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$story = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$story) {
    header("Location: subject-lessons.php?subject=" . urlencode($subject) . "&type=" . urlencode($type));
    exit;
}

/* ── فحص صلاحية المشرف: can_edit أو يملك القصة ── */
if ($isSupervisor) {
    $_ownsStory = supOwns(intval($story['created_by_supervisor'] ?? 0));
    if (!$_ownsStory && !supCan('stories', 'can_edit')) {
        header("Location: supervisor-dashboard.php"); exit;
    }
}

/* خريطة الصفحات الحالية حسب id */
function loadPagesById($conn, $storyId) {
    $map = [];
    $st = mysqli_prepare($conn, "SELECT * FROM story_pages WHERE story_id = ? ORDER BY page_order ASC, id ASC");
    mysqli_stmt_bind_param($st, "i", $storyId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($r = mysqli_fetch_assoc($res)) {
        $map[intval($r['id'])] = $r;
    }
    return $map;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $cover_title = trim($_POST['cover_title'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);

    if ($title === '') {
        $error = 'الرجاء إدخال اسم القصة';
    } else {

        /* قيم القصة الحالية (نحافظ عليها إذا ما رُفع ملف جديد) */
        $posterPath = $story['poster_image'];
        $cardVideo  = $story['sign_video'];
        $coverImage = $story['cover_image'];
        $coverVideo = $story['cover_video'];

        $np = uploadStoryFile($_FILES['poster'] ?? null, $DIR_POSTER[0], $DIR_POSTER[1], $IMG_EXT);
        if ($np !== '') $posterPath = $np;

        $nc = uploadStoryFile($_FILES['card_sign_video'] ?? null, $DIR_CARDVID[0], $DIR_CARDVID[1], $VID_EXT);
        if ($nc !== '') $cardVideo = $nc;

        $nci = uploadStoryFile($_FILES['cover_image'] ?? null, $DIR_IMAGES[0], $DIR_IMAGES[1], $IMG_EXT);
        if ($nci !== '') $coverImage = $nci;

        $ncv = uploadStoryFile($_FILES['cover_video'] ?? null, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);
        if ($ncv !== '') $coverVideo = $ncv;

        $upStory = mysqli_prepare(
            $conn,
            "UPDATE stories SET title=?, poster_image=?, sign_video=?, cover_image=?, cover_video=?, cover_title=?, sort_order=? WHERE id=?"
        );
        mysqli_stmt_bind_param($upStory, "ssssssii", $title, $posterPath, $cardVideo, $coverImage, $coverVideo, $cover_title, $sort_order, $id);
        mysqli_stmt_execute($upStory);

        /* الصفحات الحالية */
        $existing = loadPagesById($conn, $id);
        $existIds = $_POST['existing_ids'] ?? [];
        $order = 0;

        foreach ($existIds as $pidRaw) {
            $pid = intval($pidRaw);
            if (!isset($existing[$pid])) continue;

            /* حذف؟ */
            if (isset($_POST['exist_delete'][$pid])) {
                $d = mysqli_prepare($conn, "DELETE FROM story_pages WHERE id = ? AND story_id = ?");
                mysqli_stmt_bind_param($d, "ii", $pid, $id);
                mysqli_stmt_execute($d);
                continue;
            }

            $order++;
            $cap = trim((string)($_POST['exist_caption'][$pid] ?? ''));

            $img = $existing[$pid]['image'];
            $vid = $existing[$pid]['sign_video'];

            $newImg = uploadKeyed('exist_image', $pid, $DIR_IMAGES[0], $DIR_IMAGES[1], $IMG_EXT);
            if ($newImg !== '') $img = $newImg;

            $newVid = uploadKeyed('exist_video', $pid, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);
            if ($newVid !== '') $vid = $newVid;

            $up = mysqli_prepare(
                $conn,
                "UPDATE story_pages SET page_order=?, image=?, sign_video=?, caption=? WHERE id=? AND story_id=?"
            );
            mysqli_stmt_bind_param($up, "isssii", $order, $img, $vid, $cap, $pid, $id);
            mysqli_stmt_execute($up);
        }

        /* صفحات جديدة */
        $newCaptions = $_POST['new_caption'] ?? [];
        foreach ($newCaptions as $idx => $cap) {
            $cap = trim((string)$cap);
            $pageImg = uploadKeyed('new_image', $idx, $DIR_IMAGES[0], $DIR_IMAGES[1], $IMG_EXT);
            $pageVid = uploadKeyed('new_video', $idx, $DIR_VIDEOS[0], $DIR_VIDEOS[1], $VID_EXT);

            if ($cap === '' && $pageImg === '' && $pageVid === '') continue;

            $order++;
            $pIns = mysqli_prepare(
                $conn,
                "INSERT INTO story_pages (story_id, page_order, image, sign_video, caption) VALUES (?,?,?,?,?)"
            );
            mysqli_stmt_bind_param($pIns, "iisss", $id, $order, $pageImg, $pageVid, $cap);
            mysqli_stmt_execute($pIns);
        }

        header("Location: subject-lessons.php?subject=" . urlencode($subject) . "&type=" . urlencode($type));
        exit;
    }
}

/* للعرض */
$pagesMap = loadPagesById($conn, $id);
$posterPreview = pubPath($story['poster_image'] ?? '');
$coverPreview  = pubPath($story['cover_image'] ?? '');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>تعديل القصة</title>
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

.form-box h3.first{ border-top:none; padding-top:0; margin-top:0; }

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

.form-box .hint{ color:#6b7f91; font-size:13px; font-weight:bold; margin-top:5px; }

.preview-img{
    width:140px;
    height:105px;
    object-fit:cover;
    border-radius:14px;
    margin-top:8px;
    border:2px solid #d8e6f5;
}

.page-row{
    background:#f3f9ff;
    border:2px solid #e1ecf7;
    border-radius:18px;
    padding:16px 16px 6px;
    margin-top:16px;
    position:relative;
}

.page-row.to-delete{
    opacity:.5;
    background:#ffecec;
    border-color:#ffc9c9;
}

.page-row .row-title{ font-weight:900; color:#21425f; font-size:16px; margin-bottom:4px; }

.del-check{
    position:absolute;
    top:12px;
    left:12px;
    font-size:14px;
    font-weight:bold;
    color:#c0392b;
    display:flex;
    align-items:center;
    gap:6px;
    cursor:pointer;
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

.cur-media{ color:#6b7f91; font-size:13px; font-weight:bold; margin-top:5px; }

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

<h1 class="page-title">تعديل القصة</h1>

<form class="form-box" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?php echo intval($id); ?>">

    <?php if ($error !== ''): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h3 class="first">معلومات القصة</h3>

    <label>اسم القصة</label>
    <input type="text" name="title" required value="<?php echo htmlspecialchars($story['title']); ?>">

    <label>صورة البطاقة</label>
    <?php if ($posterPreview !== ''): ?>
        <img src="<?php echo htmlspecialchars($posterPreview); ?>" class="preview-img" onerror="this.style.display='none';">
    <?php endif; ?>
    <input type="file" name="poster" accept="image/*">
    <div class="hint">اتركها فاضية إذا ما بدك تغيّر الصورة.</div>

    <label>🤟 فيديو القسم الخارجي (بالإشارة)</label>
    <input type="file" name="card_sign_video" accept="video/*">
    <div class="cur-media">الحالي: <?php echo $story['sign_video'] ? htmlspecialchars($story['sign_video']) : 'لا يوجد'; ?></div>

    <label>ترتيب العرض</label>
    <input type="number" name="sort_order" value="<?php echo intval($story['sort_order']); ?>">

    <h3>الغلاف</h3>

    <label>صورة الغلاف</label>
    <?php if ($coverPreview !== ''): ?>
        <img src="<?php echo htmlspecialchars($coverPreview); ?>" class="preview-img" onerror="this.style.display='none';">
    <?php endif; ?>
    <input type="file" name="cover_image" accept="image/*">
    <div class="hint">اتركها فاضية إذا ما بدك تغيّر الغلاف.</div>

    <label>🤟 فيديو الغلاف  (بالإشارة) </label>
    <input type="file" name="cover_video" accept="video/*">
    <div class="cur-media">الحالي: <?php echo $story['cover_video'] ? htmlspecialchars($story['cover_video']) : 'لا يوجد'; ?></div>

    <label>نص الغلاف</label>
    <input type="text" name="cover_title" value="<?php echo htmlspecialchars($story['cover_title'] ?? ''); ?>">

    <h3>صفحات القصة</h3>

    <?php $n = 0; foreach ($pagesMap as $pid => $pg): $n++; ?>
    <div class="page-row">
        <label class="del-check">
            <input type="checkbox" name="exist_delete[<?php echo $pid; ?>]" value="1"
                   onchange="this.closest('.page-row').classList.toggle('to-delete', this.checked);">
            حذف الصفحة
        </label>

        <input type="hidden" name="existing_ids[]" value="<?php echo $pid; ?>">

        <div class="row-title">صفحة <?php echo $n; ?></div>

        <label>نص الصفحة</label>
        <input type="text" name="exist_caption[<?php echo $pid; ?>]" value="<?php echo htmlspecialchars($pg['caption'] ?? ''); ?>">

        <label>صورة الصفحة</label>
        <?php $pImg = pubPath($pg['image'] ?? ''); ?>
        <?php if ($pImg !== ''): ?>
            <img src="<?php echo htmlspecialchars($pImg); ?>" class="preview-img" onerror="this.style.display='none';">
        <?php endif; ?>
        <input type="file" name="exist_image[<?php echo $pid; ?>]" accept="image/*">
        <div class="hint">اتركها فاضية للإبقاء على الصورة الحالية.</div>

        <label>فيديو لغة الإشارة (اختياري)</label>
        <input type="file" name="exist_video[<?php echo $pid; ?>]" accept="video/*">
        <div class="cur-media">الحالي: <?php echo $pg['sign_video'] ? htmlspecialchars($pg['sign_video']) : 'لا يوجد'; ?></div>
    </div>
    <?php endforeach; ?>

    <div id="newPagesContainer"></div>

    <button type="button" class="add-page-btn" id="addPageBtn">+ إضافة صفحة جديدة</button>

    <button type="submit" class="save-btn">حفظ التعديلات</button>

</form>

<a
href="subject-lessons.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="back-link">
رجوع للقصص
</a>

</main>
</div>

<script>
const newPagesContainer = document.getElementById('newPagesContainer');
const addPageBtn = document.getElementById('addPageBtn');
let newCount = 0;

function makeNewPageRow() {
    newCount++;
    const row = document.createElement('div');
    row.className = 'page-row';
    row.innerHTML = `
        <button type="button" class="remove-page">حذف</button>
        <div class="row-title">صفحة جديدة ${newCount}</div>

        <label>نص الصفحة</label>
        <input type="text" name="new_caption[]" placeholder="مثال: عادت إلى البيت وهي سعيدة">

        <label>صورة الصفحة</label>
        <input type="file" name="new_image[]" accept="image/*">

        <label>فيديو لغة الإشارة (اختياري)</label>
        <input type="file" name="new_video[]" accept="video/*">
    `;
    row.querySelector('.remove-page').addEventListener('click', function () {
        row.remove();
    });
    newPagesContainer.appendChild(row);
}

addPageBtn.addEventListener('click', makeNewPageRow);
</script>
</body>
</html>
