<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$error = '';

$neededColumns = [
    "letter_id"             => "ALTER TABLE lessons ADD COLUMN letter_id INT NULL AFTER id",
    "custom_sign"           => "ALTER TABLE lessons ADD COLUMN custom_sign VARCHAR(255) NULL AFTER letter_id",
    "coloring_image"        => "ALTER TABLE lessons ADD COLUMN coloring_image VARCHAR(255) NULL AFTER custom_sign",
    "example_word"          => "ALTER TABLE lessons ADD COLUMN example_word VARCHAR(255) NULL AFTER lesson_title",
    "created_by_supervisor" => "ALTER TABLE lessons ADD COLUMN created_by_supervisor INT DEFAULT 0",
];

foreach ($neededColumns as $col => $sqlAlter) {

    $check = mysqli_query($conn, "SHOW COLUMNS FROM lessons LIKE '$col'");

    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query($conn, $sqlAlter);
    }
}

if (!is_dir("../uploads/images")) {
    mkdir("../uploads/images", 0777, true);
}

if (!is_dir("../uploads/videos")) {
    mkdir("../uploads/videos", 0777, true);
}

function uploadFile($fileInput, $folder) {

    if (
        !isset($_FILES[$fileInput]) ||
        $_FILES[$fileInput]['error'] !== 0
    ) {
        return '';
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

    return '';
}

function englishDefaultCardImage($letterId) {
    return "images/letters/english/" . intval($letterId) . ".png";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $letter_id =
        intval($_POST['letter_id'] ?? 0);

    $letter_text =
        trim($_POST['letter_text'] ?? '');

    $example_word =
        trim($_POST['example_word'] ?? '');

    if ($letter_id <= 0) {

        $error = 'يرجى إدخال رقم الحرف';

    } elseif ($letter_text === '') {

        $error = 'يرجى إدخال الحرف الإنجليزي';

    } elseif ($example_word === '') {

        $error =
            'يرجى إدخال المثال الذي يظهر فوق الفيديو';

    } else {

        $card_image =
            uploadFile('card_image','images');

        if (
            $card_image === '' &&
            $letter_id <= 26
        ) {
            $card_image =
                englishDefaultCardImage($letter_id);
        }

        $letter_video =
            uploadFile('letter_video','videos');

        $coloring_image =
            uploadFile('coloring_image','images');

        $custom_sign = '';

        if ($letter_id > 26) {

            $custom_sign =
                uploadFile('custom_sign','images');

            if ($custom_sign === '') {

                $error =
                    'يرجى رفع صورة الإشارة لأن رقم الحرف أكبر من 26';
            }
        }

        if ($error === '') {

            $subject = 'اللغة الإنجليزية';
            $type = 'الحروف الإنجليزية';

            $supCreator = $isSupervisor ? intval($_SESSION['supervisor_id'] ?? 0) : 0;
            $sql = "
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
                coloring_image,
                created_by_supervisor
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt =
                mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ssissssssi",
                $subject,
                $type,
                $letter_id,
                $letter_text,
                $example_word,
                $card_image,
                $letter_video,
                $custom_sign,
                $coloring_image,
                $supCreator
            );

            if (mysqli_stmt_execute($stmt)) {

                header(
                    "Location: subject-lessons.php?subject=" .
                    urlencode($subject) .
                    "&type=" .
                    urlencode($type)
                );

                exit;

            } else {

                $error =
                    'حدث خطأ أثناء إضافة الحرف';
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
إضافة حرف إنجليزي جديد
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

.page-title{
    text-align:center;
    color:#21425f;
}

.admin-form{
    position:relative;
    max-width:850px;
    margin:70px auto 0;
    background:linear-gradient(180deg,#ffffff,#f7fbff);
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
    margin:16px 0 8px;
    font-size:18px;
}

.form-card input[type="text"],
.form-card input[type="file"]{
    width:100%;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid #d5e0ea;
    background:#fbfdff;
    font-size:18px;
    font-family:Arial;
    outline:none;
}

.note-box{
    width:100%;
    padding:15px;
    border-radius:20px;
    background:#eef7ff;
    border:2px dashed #cfe0ef;
    color:#21425f;
    font-size:17px;
    font-weight:800;
    text-align:center;
    line-height:1.8;
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

.video-example-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    align-items:end;
    margin-top:12px;
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

.admin-error{
    max-width:760px;
    margin:20px auto;
    background:#ffecec;
    color:#b00020;
    padding:15px;
    border-radius:18px;
    text-align:center;
    font-weight:bold;
}
</style>

</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">
إضافة حرف إنجليزي جديد
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
id="letter_id">

</div>

<div class="circle-field">

<label>الحرف</label>

<input
type="text"
name="letter_text"
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

الكلمة تظهر فوق الفيديو بصفحة الطفل.

</div>

<div id="customSignBox" class="custom-sign-box">

<label>صورة الإشارة للحرف الجديد</label>

<input
type="file"
name="custom_sign"
accept="image/*">

</div>

<label>صورة الكارد / صورة المثال</label>

<input
type="file"
name="card_image"
accept="image/*">

<div class="video-example-row">

<div>

<label>فيديو المثال</label>

<input
type="file"
name="letter_video"
accept="video/mp4">

</div>

<div>

<label>الكلمة فوق الفيديو</label>

<input
type="text"
name="example_word"
placeholder="Apple">

</div>

</div>

<label>صورة التلوين</label>

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

    const value =
        parseInt(letterInput.value || "0");

    if(value > 26){

        customSignBox.style.display =
            "block";

    }else{

        customSignBox.style.display =
            "none";
    }
}

if(letterInput && customSignBox){

    letterInput.addEventListener(
        "input",
        toggleCustomSignBox
    );

    toggleCustomSignBox();
}
</script>

</body>
</html>