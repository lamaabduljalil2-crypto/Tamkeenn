<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

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

function defaultEnglishNumberName($number) {
    $names = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety', 100 => 'One Hundred'
    ];
    return $names[intval($number)] ?? (string)$number;
}

function defaultSignPath($number) { return 'images/signs/numbers/' . intval($number) . '.png'; }
function defaultNumberImagePath($number) { return 'images/numbers/english/' . intval($number) . '.png'; }
function defaultColoringPath($number) { return 'images/coloring/number-en/' . intval($number) . '.png'; }

function isNumberSignPath($path) { return strpos((string)$path, 'images/signs/numbers/') !== false; }

function uploadFileAdmin($fileInput, $folder, $oldPath = '') {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== 0) return $oldPath;
    if (!is_dir('../uploads/images')) mkdir('../uploads/images', 0777, true);
    if (!is_dir('../uploads/videos')) mkdir('../uploads/videos', 0777, true);
    $originalName = basename($_FILES[$fileInput]['name']);
    $safeName = time() . '_' . rand(1000,9999) . '_' . $originalName;
    $target = '../uploads/' . $folder . '/' . $safeName;
    if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $target)) return 'uploads/' . $folder . '/' . $safeName;
    return $oldPath;
}

function displayFileName($path) {
    if (empty($path)) return 'لا يوجد ملف';
    return basename($path);
}

$id = intval($_GET['id'] ?? 0);
$pageTitle = 'تعديل الرقم الإنجليزي';

$sql="SELECT * FROM lessons WHERE id=? AND subject_name='اللغة الإنجليزية' AND lesson_type='الأرقام الإنجليزية' LIMIT 1";
$stmt=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($stmt,'i',$id); mysqli_stmt_execute($stmt); $row=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$row) die('الرقم غير موجود');

/* ── فحص صلاحية المشرف ── */
if ($isSupervisor) {
    $_ownsNum = supOwns(intval($row['created_by_supervisor'] ?? 0));
    if (!$_ownsNum && !supCan('english_numbers', 'can_edit')) {
        header('Location: supervisor-dashboard.php'); exit;
    }
}

$numberIdFromDb=intval($row['letter_id'] ?? 0);
$signImageFromDb=trim($row['custom_sign'] ?? '') ?: defaultSignPath($numberIdFromDb);
$numberImageFromDb=trim($row['lesson_image'] ?? '');
if ($numberImageFromDb === '' || isNumberSignPath($numberImageFromDb)) $numberImageFromDb = defaultNumberImagePath($numberIdFromDb);
$coloringImageFromDb=trim($row['coloring_image'] ?? '') ?: defaultColoringPath($numberIdFromDb);
$item=[
'id'=>$row['id'],'number_id'=>$numberIdFromDb,'number_text'=>$row['lesson_title'] ?? '', 'number_name'=>$row['example_word'] ?? '',
'sign_image'=>$signImageFromDb,'number_image'=>$numberImageFromDb,'coloring_image'=>$coloringImageFromDb,'number_video'=>$row['card_video'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number_id=intval($_POST['number_id'] ?? 0); $number_text=trim($_POST['number_text'] ?? ''); $number_name=trim($_POST['number_name'] ?? '');
    if ($number_id < 0) $error='يرجى إدخال رقم صحيح.';
    else {
        if ($number_text==='') $number_text=(string)$number_id;
        if ($number_name==='') $number_name=defaultEnglishNumberName($number_id);
        $oldSign=trim($_POST['default_sign_image'] ?? '') ?: defaultSignPath($number_id);
        $oldNumberImage=trim($_POST['default_number_image'] ?? ''); if ($oldNumberImage==='' || isNumberSignPath($oldNumberImage)) $oldNumberImage=defaultNumberImagePath($number_id);
        $oldColoring=trim($_POST['default_coloring_image'] ?? '') ?: defaultColoringPath($number_id);
        $oldVideo=trim($_POST['old_number_video'] ?? '');
        $sign_image=uploadFileAdmin('sign_image','images',$oldSign); $number_image=uploadFileAdmin('number_image','images',$oldNumberImage); $coloring_image=uploadFileAdmin('coloring_image','images',$oldColoring); $number_video=uploadFileAdmin('number_video','videos',$oldVideo);
        if ($error==='') {
            $update="UPDATE lessons SET letter_id=?, lesson_title=?, example_word=?, custom_sign=?, lesson_image=?, coloring_image=?, card_video=? WHERE id=?";
            $stmt=mysqli_prepare($conn,$update); mysqli_stmt_bind_param($stmt,'issssssi',$number_id,$number_text,$number_name,$sign_image,$number_image,$coloring_image,$number_video,$id);
            if (mysqli_stmt_execute($stmt)) { header('Location: subject-lessons.php?subject='.urlencode('اللغة الإنجليزية').'&type='.urlencode('الأرقام الإنجليزية')); exit; }
            else $error='حدث خطأ أثناء الحفظ.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title><?php echo $pageTitle; ?></title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;} body{font-family:Arial,sans-serif;background:#f3f8fd;} .page-title{text-align:center;color:#21425f;}
.admin-form{position:relative;max-width:1000px;margin:70px auto 0;background:linear-gradient(180deg,#ffffff,#f7fbff);padding:78px 30px 34px;border-radius:34px;box-shadow:0 14px 35px rgba(33,66,95,.10);border:1px solid #e1edf7;}
.top-fields{position:absolute;top:-55px;left:34px;display:flex;gap:18px;z-index:5;} .circle-field{width:118px;height:118px;border-radius:50%;background:#fff;box-shadow:0 10px 25px rgba(33,66,95,.16);border:6px solid #e8f3ff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:10px;}
.circle-field label{color:#21425f;font-size:15px;font-weight:bold;margin:0 0 6px;} .circle-field input{width:78px;height:40px;border:0;outline:0;text-align:center;font-size:26px;font-weight:bold;color:#21425f;background:transparent;font-family:Arial;}
.form-card{background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid #e2edf7;border-radius:30px;padding:26px 24px;box-shadow:0 8px 22px rgba(33,66,95,.08);} .form-card h2{text-align:center;color:#21425f;font-size:28px;margin:0 0 22px;font-weight:900;}
.form-card label{display:block;color:#21425f;font-weight:800;margin:15px 0 8px;font-size:18px;} .form-card input[type="text"],.form-card input[type="file"],.form-card input[type="number"]{width:100%;padding:14px 16px;border-radius:16px;border:1px solid #d5e0ea;background:#fbfdff;font-size:18px;font-family:Arial;outline:none;}
.note-box{width:100%;padding:15px;border-radius:20px;background:#eef7ff;border:2px dashed #cfe0ef;color:#21425f;font-size:17px;font-weight:800;text-align:center;line-height:1.8;margin-bottom:18px;} .files-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
.file-box{background:#fff;border:1px solid #e2edf7;border-radius:24px;padding:18px;box-shadow:0 8px 18px rgba(33,66,95,.06);} .preview-img{width:100%;height:160px;object-fit:contain;background:#f1f7fc;border-radius:20px;padding:10px;border:1px dashed #cfe0ef;margin-bottom:10px;}
.file-name-box{width:100%;min-height:52px;display:flex;align-items:center;justify-content:center;text-align:center;padding:12px;border-radius:16px;background:#eef7ff;border:2px dashed #cfe0ef;color:#21425f;font-size:15px;font-weight:800;direction:ltr;overflow-wrap:anywhere;margin-bottom:10px;}
.form-actions{display:flex;justify-content:center;align-items:center;gap:18px;margin-top:32px;flex-wrap:wrap;} .admin-form button{border:0;padding:15px 44px;border-radius:18px;background:#21425f;color:#fff;font-size:20px;font-weight:bold;cursor:pointer;font-family:Arial;}
.back-link{display:inline-flex;align-items:center;justify-content:center;padding:15px 40px;border-radius:18px;background:#e9eef4;color:#21425f;text-decoration:none;font-size:20px;font-weight:bold;} .admin-error{max-width:760px;margin:20px auto;background:#ffecec;color:#b00020;padding:15px;border-radius:18px;text-align:center;font-weight:bold;}
@media(max-width:900px){.admin-form{padding-top:35px;margin-top:25px;}.top-fields{position:relative;top:auto;left:auto;justify-content:center;margin-bottom:25px;}.files-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">
<h1 class="page-title"><?php echo $pageTitle; ?></h1>
<?php if ($error !== ''): ?><div class="admin-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="admin-form">
<div class="top-fields">
<div class="circle-field"><label>رقم</label><input type="number" name="number_id" id="number_id" value="<?php echo htmlspecialchars($item['number_id']); ?>"></div>
<div class="circle-field"><label>الكتابة</label><input type="text" name="number_text" id="number_text" value="<?php echo htmlspecialchars($item['number_text']); ?>" placeholder="5"></div>
</div>
<div class="form-card">
<h2>بيانات الرقم الإنجليزي</h2>
<div class="note-box">اكتبي رقم الرقم فقط، وسيتم تعبئة المسارات تلقائياً حسب الرقم:<br>images/signs/numbers/ID.png<br>images/numbers/english/ID.png<br>images/coloring/number-en/ID.png<br>وإذا أردتِ تغيير أي صورة ارفعي ملف بديل وسيتم استخدامه بدل الافتراضي.</div>
<label>اسم الرقم</label><input type="text" name="number_name" id="number_name" value="<?php echo htmlspecialchars($item['number_name']); ?>" placeholder="Five">
<div class="files-grid">
<div class="file-box"><label>صورة الإشارة</label><img src="../<?php echo htmlspecialchars($item['sign_image']); ?>" class="preview-img" id="signPreview" onerror="this.onerror=null;this.src='../assets/icons/sign-icon.png';"><div class="file-name-box" id="signPathBox"><?php echo htmlspecialchars($item['sign_image']); ?></div><input type="hidden" name="default_sign_image" id="defaultSignInput" value="<?php echo htmlspecialchars($item['sign_image']); ?>"><input type="file" name="sign_image" accept="image/*"></div>
<div class="file-box"><label>صورة الرقم</label><img src="../<?php echo htmlspecialchars($item['number_image']); ?>" class="preview-img" id="numberPreview" onerror="this.onerror=null;this.src='../assets/images/no-image.png';"><div class="file-name-box" id="numberPathBox"><?php echo htmlspecialchars($item['number_image']); ?></div><input type="hidden" name="default_number_image" id="defaultNumberInput" value="<?php echo htmlspecialchars($item['number_image']); ?>"><input type="file" name="number_image" accept="image/*"></div>
<div class="file-box"><label>صورة التلوين</label><img src="../<?php echo htmlspecialchars($item['coloring_image']); ?>" class="preview-img" id="coloringPreview" onerror="this.onerror=null;this.src='../images/coloring/default.png';"><div class="file-name-box" id="coloringPathBox"><?php echo htmlspecialchars($item['coloring_image']); ?></div><input type="hidden" name="default_coloring_image" id="defaultColoringInput" value="<?php echo htmlspecialchars($item['coloring_image']); ?>"><input type="file" name="coloring_image" accept="image/*"></div>
</div>

</div>
<div class="form-actions"><button type="submit">حفظ</button><a href="subject-lessons.php?subject=اللغة الإنجليزية&type=الأرقام الإنجليزية" class="back-link">رجوع</a></div>
</form>
</main>
</div>
<script>
const numberInput=document.getElementById('number_id'),textInput=document.getElementById('number_text'),nameInput=document.getElementById('number_name');
const signPreview=document.getElementById('signPreview'),numberPreview=document.getElementById('numberPreview'),coloringPreview=document.getElementById('coloringPreview');
const signPathBox=document.getElementById('signPathBox'),numberPathBox=document.getElementById('numberPathBox'),coloringPathBox=document.getElementById('coloringPathBox');
const defaultSignInput=document.getElementById('defaultSignInput'),defaultNumberInput=document.getElementById('defaultNumberInput'),defaultColoringInput=document.getElementById('defaultColoringInput');
const defaultNames={0:'Zero',1:'One',2:'Two',3:'Three',4:'Four',5:'Five',6:'Six',7:'Seven',8:'Eight',9:'Nine',10:'Ten',20:'Twenty',30:'Thirty',40:'Forty',50:'Fifty',60:'Sixty',70:'Seventy',80:'Eighty',90:'Ninety',100:'One Hundred'};
function refreshDefaults(){const id=parseInt(numberInput.value||'0',10); if(isNaN(id)||id<0)return; const signPath='images/signs/numbers/'+id+'.png'; const numberPath='images/numbers/english/'+id+'.png'; const coloringPath='images/coloring/number-en/'+id+'.png'; textInput.value=String(id); if(defaultNames[id]) nameInput.value=defaultNames[id]; else if(!nameInput.value.trim()) nameInput.value=String(id); signPathBox.textContent=signPath; numberPathBox.textContent=numberPath; coloringPathBox.textContent=coloringPath; defaultSignInput.value=signPath; defaultNumberInput.value=numberPath; defaultColoringInput.value=coloringPath; signPreview.src='../'+signPath; numberPreview.src='../'+numberPath; coloringPreview.src='../'+coloringPath;}
if(numberInput){numberInput.addEventListener('input', refreshDefaults);}
</script>
</body>
</html>
