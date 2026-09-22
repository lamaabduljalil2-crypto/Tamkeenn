<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$isNew = isset($_GET['new']);
$id = intval($_GET['id'] ?? 0);
$error = '';

$neededColumns = [
    "letter_id" => "ALTER TABLE lessons ADD COLUMN letter_id INT NULL AFTER id",
    "custom_sign" => "ALTER TABLE lessons ADD COLUMN custom_sign VARCHAR(255) NULL AFTER letter_id",
    "coloring_image" => "ALTER TABLE lessons ADD COLUMN coloring_image VARCHAR(255) NULL AFTER custom_sign",
    "example_word" => "ALTER TABLE lessons ADD COLUMN example_word VARCHAR(255) NULL AFTER lesson_title"
];

foreach ($neededColumns as $col => $sqlAlter) {

    $check = mysqli_query($conn, "SHOW COLUMNS FROM lessons LIKE '$col'");

    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query($conn, $sqlAlter);
    }
}

function uploadFileEdit($fileInput, $folder, $oldPath = '') {

    if (
        !isset($_FILES[$fileInput]) ||
        $_FILES[$fileInput]['error'] !== 0
    ) {
        return $oldPath;
    }

    if (!is_dir("../uploads/images")) {
        mkdir("../uploads/images", 0777, true);
    }

    if (!is_dir("../uploads/videos")) {
        mkdir("../uploads/videos", 0777, true);
    }

    $originalName = basename($_FILES[$fileInput]['name']);

    $safeName =
        time() .
        '_' .
        rand(1000,9999) .
        '_' .
        $originalName;

    $target =
        "../uploads/" .
        $folder .
        "/" .
        $safeName;

    if (
        move_uploaded_file(
            $_FILES[$fileInput]['tmp_name'],
            $target
        )
    ) {
        return "uploads/" . $folder . "/" . $safeName;
    }

    return $oldPath;
}

function displayFileName($path) {

    if (empty($path)) {
        return 'لا يوجد ملف';
    }

    return basename($path);
}

function englishDefaultCardImage($letterId) {
    return "images/letters/english/" . intval($letterId) . ".png";
}

function isSignImagePath($path) {

    return strpos(
        (string)$path,
        'images/signs/english/'
    ) !== false;
}

$item = [
    'letter_id' => '',
    'letter_text' => '',
    'example_word' => '',
    'card_image' => '',
    'letter_video' => '',
    'custom_sign' => '',
    'coloring_image' => ''
];

if (!$isNew) {

    $sql = "SELECT * FROM lessons WHERE id = ? LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, 'i', $id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        die('الحرف غير موجود');
    }

    /* ── فحص صلاحية المشرف ── */
    if ($isSupervisor) {
        $_ownsLetter = supOwns(intval($row['created_by_supervisor'] ?? 0));
        if (!$_ownsLetter && !supCan('english_letters', 'can_edit')) {
            header('Location: supervisor-dashboard.php'); exit;
        }
    }

    $item = [
        'letter_id' => $row['letter_id'] ?? '',
        'letter_text' => $row['lesson_title'] ?? '',
        'example_word' => $row['example_word'] ?? '',
        'card_image' => $row['lesson_image'] ?? '',
        'letter_video' => $row['card_video'] ?? '',
        'custom_sign' => $row['custom_sign'] ?? '',
        'coloring_image' => $row['coloring_image'] ?? ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $letter_id =
        intval($_POST['letter_id'] ?? 0);

    $letter_text =
        trim($_POST['letter_text'] ?? '');

    $example_word =
        trim($_POST['example_word'] ?? '');

    if ($letter_id <= 0) {

        $error = 'يرجى إدخال رقم الحرف.';

    } elseif ($letter_text === '') {

        $error = 'يرجى إدخال الحرف الإنجليزي.';

    } else {

        if ($example_word === '') {
            $example_word = $letter_text;
        }

        $oldCardImage =
            $item['card_image'] ?? '';

        if (
            $letter_id <= 26 &&
            (
                $oldCardImage === '' ||
                isSignImagePath($oldCardImage)
            )
        ) {

            $oldCardImage =
                englishDefaultCardImage($letter_id);
        }

        $card_image = uploadFileEdit(
            'card_image',
            'images',
            $oldCardImage
        );

        $letter_video = uploadFileEdit(
            'letter_video',
            'videos',
            $item['letter_video'] ?? ''
        );

        $coloring_image = uploadFileEdit(
            'coloring_image',
            'images',
            $item['coloring_image'] ?? ''
        );

        if ($letter_id <= 26) {

            $custom_sign = '';

        } else {

            $custom_sign = uploadFileEdit(
                'custom_sign',
                'images',
                $item['custom_sign'] ?? ''
            );

            if ($custom_sign === '') {

                $error =
                    'يرجى رفع صورة الإشارة لأن رقم الحرف أكبر من 26.';
            }
        }

        if ($error === '') {

            if ($isNew) {

                $subject = 'اللغة الإنجليزية';
                $type = 'الحروف الإنجليزية';

                $insert = "
                INSERT INTO lessons
                (
                    subject_name,
                    lesson_type,
                    letter_id,
                    lesson_title,
                    example_word,
                    lesson_image,
                    card_video,
                    custom_sign,
                    coloring_image
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";

                $stmt =
                    mysqli_prepare($conn, $insert);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssissssss",
                    $subject,
                    $type,
                    $letter_id,
                    $letter_text,
                    $example_word,
                    $card_image,
                    $letter_video,
                    $custom_sign,
                    $coloring_image
                );

            } else {

                $update = "
                UPDATE lessons
                SET
                    letter_id = ?,
                    lesson_title = ?,
                    example_word = ?,
                    lesson_image = ?,
                    card_video = ?,
                    custom_sign = ?,
                    coloring_image = ?
                WHERE id = ?
                ";

                $stmt =
                    mysqli_prepare($conn, $update);

                mysqli_stmt_bind_param(
                    $stmt,
                    "issssssi",
                    $letter_id,
                    $letter_text,
                    $example_word,
                    $card_image,
                    $letter_video,
                    $custom_sign,
                    $coloring_image,
                    $id
                );
            }

            if (mysqli_stmt_execute($stmt)) {

                header(
                    'Location: subject-lessons.php?subject=' .
                    urlencode('اللغة الإنجليزية') .
                    '&type=' .
                    urlencode('الحروف الإنجليزية')
                );

                exit;

            } else {

                $error = 'حدث خطأ أثناء الحفظ.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">

<title>
<?php
echo $isNew
? 'إضافة حرف إنجليزي جديد'
: 'تعديل الحرف الإنجليزي';
?>
</title>

<link rel="stylesheet" href="assets/admin.css">

<style>

*{
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    background:#f3f8fd;
}

.admin-form{
    position:relative;
    max-width:850px;
    margin:70px auto 0;
    background:linear-gradient(180deg,#fff,#f7fbff);
    padding:78px 30px 34px;
    border-radius:34px;
    box-shadow:0 14px 35px rgba(33,66,95,.10);
    border:1px solid #e1edf7;
}

.top-fields{
    position:absolute;
    top:-55px;
    left:34px;
    display:flex;
    gap:18px;
    z-index:5;
}

.circle-field{
    width:118px;
    height:118px;
    border-radius:50%;
    background:#fff;
    box-shadow:0 10px 25px rgba(33,66,95,.16);
    border:6px solid #e8f3ff;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:10px;
}

.circle-field label{
    color:#21425f;
    font-size:15px;
    font-weight:bold;
    margin:0 0 6px;
}

.circle-field input{
    width:78px;
    height:40px;
    border:0;
    outline:0;
    text-align:center;
    font-size:26px;
    font-weight:bold;
    color:#21425f;
    background:transparent;
    font-family:Arial;
}

.form-card{
    background:linear-gradient(180deg,#ffffff,#f8fbff);
    border:1px solid #e2edf7;
    border-radius:30px;
    padding:26px 24px;
    box-shadow:0 8px 22px rgba(33,66,95,.08);
}

.form-card h2{
    text-align:center;
    color:#21425f;
    font-size:28px;
    margin:0 0 22px;
    font-weight:900;
}

.form-card label{
    display:block;
    color:#21425f;
    font-weight:800;
    margin:15px 0 8px;
    font-size:18px;
}

.form-card input[type="text"],
.form-card input[type="file"],
.form-card input[type="number"]{
    width:100%;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid #d5e0ea;
    background:#fbfdff;
    font-size:18px;
    font-family:Arial;
    outline:none;
}

.file-name-box{
    width:100%;
    min-height:58px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    padding:15px;
    border-radius:20px;
    background:#eef7ff;
    border:2px dashed #cfe0ef;
    color:#21425f;
    font-size:17px;
    font-weight:800;
    direction:ltr;
    overflow-wrap:anywhere;
    margin-bottom:10px;
}

.empty-file{
    color:#8a9aad;
    direction:rtl;
}

.note-box{
    width:100%;
    padding:14px;
    border-radius:18px;
    background:#eef7ff;
    border:2px dashed #cfe0ef;
    color:#21425f;
    font-size:16px;
    font-weight:800;
    text-align:center;
    line-height:1.7;
    margin-bottom:14px;
}

.custom-sign-box{
    display:none;
    margin-top:16px;
    padding:18px;
    border-radius:22px;
    background:#fff6fa;
    border:2px dashed #ff9fc3;
}

.form-actions{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:18px;
    margin-top:32px;
    flex-wrap:wrap;
}

.admin-form button{
    border:0;
    padding:15px 44px;
    border-radius:18px;
    background:#21425f;
    color:#fff;
    font-size:20px;
    font-weight:bold;
    cursor:pointer;
    font-family:Arial;
}

.back-link{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:15px 40px;
    border-radius:18px;
    background:#e9eef4;
    color:#21425f;
    text-decoration:none;
    font-size:20px;
    font-weight:bold;
}

.page-title{
    text-align:center;
    width:100%;
}

@media(max-width:900px){

    .admin-form{
        padding-top:35px;
        margin-top:25px;
    }

    .top-fields{
        position:relative;
        top:auto;
        left:auto;
        justify-content:center;
        margin-bottom:25px;
    }

    .circle-field{
        width:105px;
        height:105px;
    }
}
.video-example-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    align-items:end;
    margin-top:12px;
}

.video-field .file-name-box{
    min-height:48px;
    font-size:15px;
    padding:10px;
}

.video-field input[type="file"]{
    padding:10px 12px;
    font-size:15px;
}

@media(max-width:700px){

    .video-example-row{
        grid-template-columns:1fr;
    }
}
</style>

</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">

<?php
echo $isNew
? 'إضافة حرف إنجليزي جديد'
: 'تعديل حرف ' .
htmlspecialchars($item['letter_text']);
?>

</h1>

<?php if ($error !== ''): ?>

<div class="admin-error">
<?php echo htmlspecialchars($error); ?>
</div>

<?php endif; ?>

<form
method="POST"
enctype="multipart/form-data"
class="admin-form">

<div class="top-fields">

<div class="circle-field">

<label>رقم الحرف</label>

<input
type="number"
name="letter_id"
id="letter_id"
value="<?php
echo $isNew
? ''
: htmlspecialchars($item['letter_id']);
?>">

</div>

<div class="circle-field">

<label>الحرف</label>

<input
type="text"
name="letter_text"
value="<?php
echo htmlspecialchars($item['letter_text']);
?>"
placeholder="A">

</div>

</div>

<div class="form-card">

<h2>بيانات الحرف الإنجليزي</h2>

<div class="note-box">

صورة الإشارة للحروف 1 إلى 26 تؤخذ تلقائياً حسب رقم الحرف.

<br>

صورة الكارد هي صورة المثال.

<br>

الفيديو هو فيديو الاسم.

</div>

<div id="customSignBox" class="custom-sign-box">

<label>صورة الإشارة للحرف الجديد</label>

<div class="file-name-box <?php
echo empty($item['custom_sign'])
? 'empty-file'
: '';
?>">

<?php
echo htmlspecialchars(
    displayFileName($item['custom_sign'])
);
?>

</div>

<input
type="file"
name="custom_sign"
accept="image/*">

</div>

<label>صورة الكارد / المثال</label>

<div class="file-name-box <?php
echo empty($item['card_image'])
? 'empty-file'
: '';
?>">

<?php
echo htmlspecialchars(
    displayFileName($item['card_image'])
);
?>

</div>

<input
type="file"
name="card_image"
accept="image/*">

<div class="video-example-row">

    <div class="video-field">

        <label>فيديو الاسم / المثال</label>

        <div class="file-name-box <?php
        echo empty($item['letter_video'])
        ? 'empty-file'
        : '';
        ?>">

        <?php
        echo htmlspecialchars(
            displayFileName($item['letter_video'])
        );
        ?>

        </div>

        <input
        type="file"
        name="letter_video"
        accept="video/mp4">

    </div>

    <div class="example-field">

        <label>المثال الذي يظهر فوق الفيديو</label>

        <input
        type="text"
        name="example_word"
        value="<?php echo htmlspecialchars($item['example_word']); ?>"
        placeholder="Apple">

    </div>

</div>

<label>صورة التلوين</label>

<div class="file-name-box <?php
echo empty($item['coloring_image'])
? 'empty-file'
: '';
?>">

<?php
echo htmlspecialchars(
    displayFileName($item['coloring_image'])
);
?>

</div>

<input
type="file"
name="coloring_image"
accept="image/*">

</div>

<div class="form-actions">

<button type="submit">
حفظ
</button>

<a
href="subject-lessons.php?subject=اللغة الإنجليزية&type=الحروف الإنجليزية"
class="back-link">
رجوع
</a>

</div>

</form>

</main>

</div>

<script>

const letterInput =
    document.getElementById("letter_id");

const customSignBox =
    document.getElementById("customSignBox");

function toggleCustomSignBox(){

    if (!letterInput || !customSignBox) return;

    const value =
        parseInt(letterInput.value || "0",10);

    if (value > 26) {

        customSignBox.style.display = "block";

    } else {

        customSignBox.style.display = "none";
    }
}

if (letterInput && customSignBox){

    letterInput.addEventListener(
        "input",
        toggleCustomSignBox
    );

    toggleCustomSignBox();
}

</script>

</body>
</html>