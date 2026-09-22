<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

// ── تحديد نوع العملية مبكراً لفحص الصلاحية ──────────────────
$isNew = isset($_GET['new']);
$id    = intval($_GET['id'] ?? 0);

// ── فحص صلاحية المشرف ────────────────────────────────────────
if (isset($isSupervisor) && $isSupervisor) {
    if ($isNew) {
        if (!supCan('arabic_letters', 'can_add')) {
            header('Location: supervisor-dashboard.php'); exit;
        }
    } else {
        // يسمح بالتعديل إذا كان يملك can_edit أو يملك هذا العنصر
        // التحقق من الملكية يتم بعد جلب العنصر من DB (أسفل)
    }
}

$error = '';

/* إنشاء الأعمدة تلقائيًا عند الحاجة */
foreach ([
    'custom_sign'           => "ALTER TABLE arabic_letter_examples ADD COLUMN custom_sign VARCHAR(255) NULL AFTER letter_text",
    'coloring_image'        => "ALTER TABLE arabic_letter_examples ADD COLUMN coloring_image VARCHAR(255) NULL AFTER custom_sign",
    'created_by_supervisor' => "ALTER TABLE arabic_letter_examples ADD COLUMN created_by_supervisor INT DEFAULT 0",
] as $_acol => $_asql) {
    $__chk = mysqli_query($conn, "SHOW COLUMNS FROM arabic_letter_examples LIKE '$_acol'");
    if ($__chk && mysqli_num_rows($__chk) == 0) mysqli_query($conn, $_asql);
}

/* إنشاء عمود المثال الذي يظهر فوق الفيديو */
$checkExampleWord = mysqli_query($conn, "SHOW COLUMNS FROM arabic_letter_examples LIKE 'example_word'");
if ($checkExampleWord && mysqli_num_rows($checkExampleWord) == 0) {
    mysqli_query($conn, "ALTER TABLE arabic_letter_examples ADD COLUMN example_word VARCHAR(255) NULL AFTER letter_text");
}

function uploadFileEdit($fileInput, $folder, $oldPath = '') {

    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== 0) {
        return $oldPath;
    }

    if (!is_dir("../uploads/images")) {
        mkdir("../uploads/images", 0777, true);
    }

    if (!is_dir("../uploads/videos")) {
        mkdir("../uploads/videos", 0777, true);
    }

    $originalName = basename($_FILES[$fileInput]['name']);
    $safeName = time() . '_' . rand(1000,9999) . '_' . $originalName;
    $target = "../uploads/" . $folder . "/" . $safeName;

    if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $target)) {
        return "uploads/" . $folder . "/" . $safeName;
    }

    return $oldPath;
}


function displayFileName($path) {
    if (empty($path)) {
        return "لا يوجد ملف";
    }

    return basename($path);
}

if ($isNew) {
    $item = [
        'letter_id' => '',
        'letter_text' => '',
        'example_word' => '',
        'custom_sign' => '',
        'coloring_image' => '',
        'card_image' => '',
        'start_word' => '',
        'start_image' => '',
        'start_video' => '',
        'middle_word' => '',
        'middle_image' => '',
        'middle_video' => '',
        'end_word' => '',
        'end_image' => '',
        'end_video' => ''
    ];
} else {
    $sql = "SELECT * FROM arabic_letter_examples WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);

    if (!$item) {
        die("الحرف غير موجود");
    }

    // ── فحص الملكية للمشرف عند التعديل ────────────────────────
    if ($isSupervisor) {
        $_ownsItem = supOwns(intval($item['created_by_supervisor'] ?? 0));
        if (!$_ownsItem && !supCan('arabic_letters', 'can_edit')) {
            header('Location: supervisor-dashboard.php'); exit;
        }
    }

    if (!isset($item['custom_sign'])) {
        $item['custom_sign'] = '';
    }

    if (!isset($item['example_word'])) {
        $item['example_word'] = $item['start_word'] ?? '';
    }

    if (!isset($item['coloring_image'])) {
        $item['coloring_image'] = '';
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $letter_id = intval($_POST['letter_id'] ?? 0);
    $letter_text = trim($_POST['letter_text'] ?? '');
    $example_word = trim($_POST['example_word'] ?? '');

    $start_word = $example_word;
    $middle_word = trim($_POST['middle_word'] ?? '');
    $end_word = trim($_POST['end_word'] ?? '');

    $defaultStartImage = "images/letters/arabic/" . $letter_id . ".png";

    $oldStartImage = $isNew ? $defaultStartImage : ($item['start_image'] ?: $defaultStartImage);

    $start_image = uploadFileEdit(
        'start_image',
        'images',
        $oldStartImage
    );

    $card_image = $start_image;

    $middle_image = uploadFileEdit(
        'middle_image',
        'images',
        $item['middle_image']
    );

    $end_image = uploadFileEdit(
        'end_image',
        'images',
        $item['end_image']
    );

    $start_video = uploadFileEdit(
        'start_video',
        'videos',
        $item['start_video']
    );

    $middle_video = uploadFileEdit(
        'middle_video',
        'videos',
        $item['middle_video']
    );

    $end_video = uploadFileEdit(
        'end_video',
        'videos',
        $item['end_video']
    );

    $custom_sign = $item['custom_sign'] ?? '';

    if ($letter_id > 30) {
        $custom_sign = uploadFileEdit(
            'custom_sign',
            'images',
            $custom_sign
        );

        if ($custom_sign === '') {
            $error = 'يرجى رفع صورة إشارة للحرف الجديد لأن رقمه أكبر من 30.';
        }
    } else {
        $custom_sign = '';
    }

    $coloring_image = uploadFileEdit(
        'coloring_image',
        'images',
        $item['coloring_image'] ?? ''
    );

    if ($error === '') {

    if ($isNew) {

        $supCreator = $isSupervisor ? intval($_SESSION['supervisor_id'] ?? 0) : 0;
        $insert = "
        INSERT INTO arabic_letter_examples
        (
            letter_id,
            letter_text,
            example_word,
            custom_sign,
            coloring_image,
            card_image,
            start_word,
            start_image,
            start_video,
            middle_word,
            middle_image,
            middle_video,
            end_word,
            end_image,
            end_video,
            created_by_supervisor
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $insert);

        mysqli_stmt_bind_param(
            $stmt,
            "issssssssssssssi",
            $letter_id,
            $letter_text,
            $example_word,
            $custom_sign,
            $coloring_image,
            $card_image,
            $start_word,
            $start_image,
            $start_video,
            $middle_word,
            $middle_image,
            $middle_video,
            $end_word,
            $end_image,
            $end_video,
            $supCreator
        );

    } else {

        $update = "
        UPDATE arabic_letter_examples
        SET
            letter_id = ?,
            letter_text = ?,
            example_word = ?,
            custom_sign = ?,
            coloring_image = ?,
            card_image = ?,
            start_word = ?,
            start_image = ?,
            start_video = ?,
            middle_word = ?,
            middle_image = ?,
            middle_video = ?,
            end_word = ?,
            end_image = ?,
            end_video = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $update);

        mysqli_stmt_bind_param(
            $stmt,
            "issssssssssssssi",
            $letter_id,
            $letter_text,
            $example_word,
            $custom_sign,
            $coloring_image,
            $card_image,
            $start_word,
            $start_image,
            $start_video,
            $middle_word,
            $middle_image,
            $middle_video,
            $end_word,
            $end_image,
            $end_video,
            $id
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        header(
            "Location: subject-lessons.php?subject=" .
            urlencode("اللغة العربية") .
            "&type=" .
            urlencode("الحروف العربية")
        );
        exit;
    } else {
        $error = "حدث خطأ أثناء الحفظ.";
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
<?php echo $isNew ? 'إضافة حرف جديد' : 'تعديل الحرف'; ?>
</title>

<link rel="stylesheet" href="assets/admin.css">

<style>
*{
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f3f8fd;
}

.admin-form{
    position:relative;
    max-width:1320px;
    margin:70px auto 0;
    background:linear-gradient(180deg,#ffffff,#f7fbff);
    padding:80px 28px 34px;
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
    background:#ffffff;
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

.circle-field input[type="number"]{
    appearance:textfield;
}

.circle-field input[type="number"]::-webkit-inner-spin-button,
.circle-field input[type="number"]::-webkit-outer-spin-button{
    -webkit-appearance:none;
    margin:0;
}

.examples-row{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    align-items:stretch;
}

.example-box{
    min-height:590px;
    padding:24px 22px 26px;
    border-radius:30px;
    background:#ffffff;
    border:1px solid #e2edf7;
    box-shadow:0 8px 22px rgba(33,66,95,.08);
    display:flex;
    flex-direction:column;
}

.example-box h2{
    text-align:center;
    color:#21425f;
    font-size:27px;
    margin:0 0 18px;
    font-weight:900;
}

.example-box label{
    display:block;
    color:#21425f;
    font-weight:800;
    margin:13px 0 8px;
    font-size:17px;
}

.example-box input[type="text"],
.example-box input[type="file"]{
    width:100%;
    padding:13px 15px;
    border-radius:16px;
    border:1px solid #d5e0ea;
    background:#fbfdff;
    font-size:17px;
    box-sizing:border-box;
    font-family:Arial;
    outline:none;
}

.example-box input[type="text"]:focus,
.example-box input[type="file"]:focus{
    border-color:#8fc4ef;
    box-shadow:0 0 0 4px rgba(143,196,239,.22);
}

.preview-img{
    width:100%;
    height:210px;
    object-fit:contain;
    background:#f1f7fc;
    border-radius:24px;
    padding:12px;
    margin-bottom:10px;
    border:1px dashed #cfe0ef;
}

.file-name-box{
    width:100%;
    min-height:54px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    padding:13px 14px;
    border-radius:18px;
    background:#f1f7fc;
    border:1px dashed #cfe0ef;
    color:#21425f;
    font-size:16px;
    font-weight:800;
    direction:ltr;
    overflow-wrap:anywhere;
    margin-bottom:8px;
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
    margin:0 0 18px;
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
    color:white;
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

.admin-form button:hover,
.back-link:hover{
    transform:translateY(-2px);
}

@media(max-width:1100px){
    .admin-form{
        padding-top:35px;
        margin-top:25px;
    }

    .examples-row{
        grid-template-columns:1fr;
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
</style>
</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">
<?php echo $isNew ? 'إضافة حرف جديد' : 'تعديل حرف ' . htmlspecialchars($item['letter_text']); ?>
</h1>

<?php if ($error !== ''): ?>
<div class="admin-error">
<?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-form">

<div class="top-fields">

<div class="circle-field">
<label>رقم الحرف</label>
<input
type="number"
name="letter_id"
id="letter_id"
value="<?php echo htmlspecialchars($item['letter_id']); ?>">
</div>

<div class="circle-field">
<label>الحرف</label>
<input
type="text"
name="letter_text"
value="<?php echo htmlspecialchars($item['letter_text']); ?>">
</div>

</div>

<div class="custom-sign-box" id="customSignBox">
<div class="note-box">
الحروف الأساسية من 1 إلى 30 تأخذ صورة الإشارة تلقائيًا من:<br>
images/signs/arabic/رقم_الحرف.png<br>
إذا كان رقم الحرف أكبر من 30 ارفعي صورة إشارة مخصصة من هنا.
</div>

<label>صورة الإشارة للحرف الجديد</label>
<div class="file-name-box <?php echo empty($item['custom_sign']) ? 'empty-file' : ''; ?>">
<?php echo htmlspecialchars(displayFileName($item['custom_sign'] ?? '')); ?>
</div>
<input
 type="file"
 name="custom_sign"
 accept="image/*">
</div>

<div class="custom-sign-box" style="display:block; background:#f6fff8; border-color:#82e7a5;">
<div class="note-box">
صورة التلوين اختيارية، وإذا لم ترفعي صورة سيتم استخدام الصورة الافتراضية حسب رقم الحرف من:<br>
images/coloring/arabic/رقم_الحرف.png
</div>

<label>صورة التلوين</label>
<div class="file-name-box <?php echo empty($item['coloring_image']) ? 'empty-file' : ''; ?>">
<?php echo htmlspecialchars(displayFileName($item['coloring_image'] ?? '')); ?>
</div>
<input
 type="file"
 name="coloring_image"
 accept="image/*">
</div>

<div class="examples-row">

<div class="example-box">

<h2>أول الكلمة</h2>

<label>المثال الذي يظهر فوق الفيديو</label>
<input
type="text"
name="example_word"
value="<?php echo htmlspecialchars($item['example_word'] ?? $item['start_word']); ?>">

<label>الصورة الحالية</label>

<?php
$currentStartImage = !empty($item['card_image']) ? $item['card_image'] : $item['start_image'];

if ($currentStartImage === '') {
    $currentStartImage =
        "images/letters/arabic/" .
        intval($item['letter_id']) .
        ".png";
}
?>

<img
id="startAutoPreview"
src="../<?php echo htmlspecialchars($currentStartImage); ?>"
class="preview-img"
alt="صورة أول الكلمة">

<label>تغيير الصورة</label>
<input
type="file"
name="start_image"
accept="image/*">

<label>الفيديو الحالي</label>

<div class="file-name-box <?php echo empty($item['start_video']) ? 'empty-file' : ''; ?>">
<?php echo htmlspecialchars(displayFileName($item['start_video'])); ?>
</div>

<label>تغيير الفيديو</label>
<input
type="file"
name="start_video"
accept="video/mp4">

</div>

<div class="example-box">

<h2>وسط الكلمة</h2>

<label>الكلمة</label>
<input
type="text"
name="middle_word"
value="<?php echo htmlspecialchars($item['middle_word']); ?>">

<label>الصورة الحالية</label>

<?php if (!empty($item['middle_image'])): ?>
<img
src="../<?php echo htmlspecialchars($item['middle_image']); ?>"
class="preview-img"
alt="صورة وسط الكلمة">
<?php else: ?>
<div class="file-name-box empty-file" style="height:210px;">لا توجد صورة</div>
<?php endif; ?>

<label>تغيير الصورة</label>
<input
type="file"
name="middle_image"
accept="image/*">

<label>الفيديو الحالي</label>

<div class="file-name-box <?php echo empty($item['middle_video']) ? 'empty-file' : ''; ?>">
<?php echo htmlspecialchars(displayFileName($item['middle_video'])); ?>
</div>

<label>تغيير الفيديو</label>
<input
type="file"
name="middle_video"
accept="video/mp4">

</div>

<div class="example-box">

<h2>آخر الكلمة</h2>

<label>الكلمة</label>
<input
type="text"
name="end_word"
value="<?php echo htmlspecialchars($item['end_word']); ?>">

<label>الصورة الحالية</label>

<?php if (!empty($item['end_image'])): ?>
<img
src="../<?php echo htmlspecialchars($item['end_image']); ?>"
class="preview-img"
alt="صورة آخر الكلمة">
<?php else: ?>
<div class="file-name-box empty-file" style="height:210px;">لا توجد صورة</div>
<?php endif; ?>

<label>تغيير الصورة</label>
<input
type="file"
name="end_image"
accept="image/*">

<label>الفيديو الحالي</label>

<div class="file-name-box <?php echo empty($item['end_video']) ? 'empty-file' : ''; ?>">
<?php echo htmlspecialchars(displayFileName($item['end_video'])); ?>
</div>

<label>تغيير الفيديو</label>
<input
type="file"
name="end_video"
accept="video/mp4">

</div>

</div>

<div class="form-actions">

<button type="submit">
حفظ
</button>

<a
href="subject-lessons.php?subject=<?php echo urlencode('اللغة العربية'); ?>&type=<?php echo urlencode('الحروف العربية'); ?>"
class="back-link">
رجوع
</a>

</div>

</form>

</main>

</div>

<script>
const letterInput = document.getElementById('letter_id');
const startAutoPreview = document.getElementById('startAutoPreview');
const customSignBox = document.getElementById('customSignBox');

function toggleCustomSignBox() {
    if (!letterInput || !customSignBox) return;

    const value = parseInt(letterInput.value || '0');

    if (value > 30) {
        customSignBox.style.display = 'block';
    } else {
        customSignBox.style.display = 'none';
    }
}

if (letterInput && startAutoPreview) {
    letterInput.addEventListener('input', function () {
        const id = this.value.trim();

        if (id !== '') {
            startAutoPreview.src =
                '../images/letters/arabic/' + id + '.png';
        }

        toggleCustomSignBox();
    });
}

toggleCustomSignBox();
</script>

</body>
</html>
