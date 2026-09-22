<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

// ── تأكد وجود عمود created_by_supervisor في كل الجداول المستخدمة ──
foreach (['arabic_letter_examples', 'lessons', 'stories'] as $_cbs_tbl) {
    $_cbs_chk = mysqli_query($conn, "SHOW COLUMNS FROM `$_cbs_tbl` LIKE 'created_by_supervisor'");
    if ($_cbs_chk && mysqli_num_rows($_cbs_chk) == 0) {
        mysqli_query($conn, "ALTER TABLE `$_cbs_tbl` ADD COLUMN created_by_supervisor INT DEFAULT 0");
    }
}

$subject = $_GET['subject'] ?? '';
$type = $_GET['type'] ?? '';

// ── تحديد قسم الصلاحية ─────────────────────────────────────
$permSection = 'arabic_letters';
if ($subject === 'اللغة الإنجليزية' && $type === 'الحروف الإنجليزية')     $permSection = 'english_letters';
elseif ($subject === 'الرياضيات' && $type === 'الأرقام العربية')           $permSection = 'arabic_numbers';
elseif ($subject === 'اللغة الإنجليزية' && $type === 'الأرقام الإنجليزية') $permSection = 'english_numbers';
elseif ($subject === 'الثقافة العامة' && $type === 'القصص')                $permSection = 'stories';
elseif ($subject === 'الثقافة العامة' && $type === 'الحيوانات')             $permSection = 'gc_animals';
elseif ($subject === 'الثقافة العامة' && $type === 'الطقس')                $permSection = 'gc_weather';
elseif ($subject === 'الثقافة العامة' && $type === 'الفصول الأربعة')       $permSection = 'gc_seasons';
elseif ($subject === 'الثقافة العامة' && $type === 'أيام الأسبوع')         $permSection = 'gc_days';
elseif ($subject === 'الثقافة العامة' && $type === 'فواكه وخضروات')        $permSection = 'gc_food';
elseif ($subject === 'الثقافة العامة' && $type === 'أركان الإسلام')        $permSection = 'gc_islam';
elseif ($subject === 'الثقافة العامة')                                       $permSection = 'general_culture';

// ── فحص صلاحية الوصول للمشرف ─────────────────────────────
// يُسمح بالدخول إذا كان يملك أي صلاحية (view أو add أو edit أو delete)
if ($isSupervisor && !supHasAny($permSection)) {
    header('Location: supervisor-dashboard.php'); exit;
}


if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    // تحديد الجدول والتحقق من الملكية
    if ($subject === 'اللغة العربية' && $type === 'الحروف العربية') {
        $ownerRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT created_by_supervisor FROM arabic_letter_examples WHERE id=$deleteId"));
        $deleteSql = "DELETE FROM arabic_letter_examples WHERE id = ?";
    } elseif ($subject === 'الثقافة العامة' && $type === 'القصص') {
        $ownerRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT created_by_supervisor FROM stories WHERE id=$deleteId"));
        $deleteSql = "DELETE FROM stories WHERE id = ?";
    } else {
        $ownerRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT created_by_supervisor FROM lessons WHERE id=$deleteId"));
        $deleteSql = "DELETE FROM lessons WHERE id = ?";
    }

    $_isOwner = $ownerRow && supOwns(intval($ownerRow['created_by_supervisor'] ?? 0));

    if ($isSupervisor && !supCan($permSection, 'can_delete') && !$_isOwner) {
        header("Location: subject-lessons.php?subject=" . urlencode($subject) . "&type=" . urlencode($type));
        exit;
    }

    $deleteStmt = mysqli_prepare($conn, $deleteSql);
    mysqli_stmt_bind_param($deleteStmt, "i", $deleteId);
    mysqli_stmt_execute($deleteStmt);

    /* لو القصة محذوفة، احذف صفحاتها كمان */
    if ($subject === 'الثقافة العامة' && $type === 'القصص') {
        $delPages = mysqli_prepare($conn, "DELETE FROM story_pages WHERE story_id = ?");
        mysqli_stmt_bind_param($delPages, "i", $deleteId);
        mysqli_stmt_execute($delPages);
    }

    header(
        "Location: subject-lessons.php?subject=" .
        urlencode($subject) .
        "&type=" .
        urlencode($type)
    );
    exit;
}

/* =========================================
   توجيه صفحات الثقافة العامة الثابتة
   (الطقس / الفصول / أيام الأسبوع / أركان الإسلام)
========================================= */

if ($subject === 'الثقافة العامة' && $type === 'الطقس') {
    header('Location: weather.php');
    exit;
}

if ($subject === 'الثقافة العامة' && $type === 'الفصول الأربعة') {
    header('Location: seasons.php');
    exit;
}

if ($subject === 'الثقافة العامة' && $type === 'أيام الأسبوع') {
    header('Location: days.php');
    exit;
}

if ($subject === 'الثقافة العامة' && $type === 'أركان الإسلام') {
    header('Location: admin-pillars.php');
    exit;
}

/* =========================================
   توجيه الأقسام المخصصة (الديناميكية)
========================================= */
if ($subject === 'الثقافة العامة' && $type !== '') {
    $cs = mysqli_prepare($conn, "SELECT id FROM general_sections WHERE name = ? LIMIT 1");
    mysqli_stmt_bind_param($cs, 's', $type);
    mysqli_stmt_execute($cs);
    $crow = mysqli_fetch_assoc(mysqli_stmt_get_result($cs));
    if ($crow) {
        if ($isSupervisor && !supCan('gc_custom_' . $crow['id'], 'can_view')) {
            header('Location: supervisor-dashboard.php'); exit;
        }
        header('Location: manage-custom-section.php?id=' . $crow['id']);
        exit;
    }
}

/* =========================================
   دوال مساعدة
========================================= */

function safeTitle($row) {
    $possibleFields = [
        'title',
        'lesson_title',
        'name',
        'letter_text',
        'text',
        'word'
    ];

    foreach ($possibleFields as $field) {
        if (isset($row[$field]) && trim((string)$row[$field]) !== '') {
            return $row[$field];
        }
    }

    return "بطاقة";
}

/* =========================================
   الحروف العربية
========================================= */

if ($subject === 'اللغة العربية' && $type === 'الحروف العربية'):

$lettersResult = mysqli_query(
    $conn,
    "SELECT *, COALESCE(created_by_supervisor,0) AS created_by_supervisor FROM arabic_letter_examples ORDER BY letter_id ASC"
);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الحروف العربية</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.letters-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:28px;
    margin-top:35px;
}

.letter-card{
    background:#fff;
    border-radius:30px;
    padding:28px 18px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.letter-circle{
    width:110px;
    height:110px;
    border-radius:50%;
    background:#E8F3FF;
    margin:0 auto 18px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

.letter-circle img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.letter-card h2{
    color:#21425f;
    font-size:24px;
    margin-bottom:18px;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}

.open-btn,
.edit-btn,
.delete-btn{
    padding:10px 18px;
    border-radius:14px;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
}

.open-btn{background:#e8f3ff;color:#21425f;}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.page-title{
    text-align:center;
    width:100%;
}
@media(max-width:700px){.letters-grid{grid-template-columns:repeat(2,1fr);gap:14px;}}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">اللغة العربية - الحروف العربية</h1>

<?php if (supCan($permSection,'can_add')): ?><a href="edit-arabic-example.php?new=1" class="add-btn">
+ إضافة حرف جديد
</a><?php endif; ?>

<div class="letters-grid">

<?php while($row = mysqli_fetch_assoc($lettersResult)): ?>

<div class="letter-card">

    <div class="letter-circle">

        <?php
        $arabicLetterId = intval($row['letter_id']);
        $defaultArabicSign = "../images/signs/arabic/" . $arabicLetterId . ".png";
        $defaultArabicSignServer = __DIR__ . "/../images/signs/arabic/" . $arabicLetterId . ".png";

        if (file_exists($defaultArabicSignServer)) {
            $arabicSignImage = $defaultArabicSign;
        } elseif (!empty($row['custom_sign'])) {
            $arabicSignImage = "../" . ltrim($row['custom_sign'], '/');
        } else {
            $arabicSignImage = "../assets/icons/sign-icon.png";
        }
        ?>

        <img
        src="<?php echo htmlspecialchars($arabicSignImage); ?>"
        alt="<?php echo htmlspecialchars($row['letter_text']); ?>">

    </div>

    <h2>
        حرف <?php echo htmlspecialchars($row['letter_text']); ?>
    </h2>

    <div class="card-actions">

        <a
        href="../subjects/arabic/arabic-letter.php?id=<?php echo intval($row['letter_id']); ?>"
        class="open-btn">
        عرض
        </a>

        <?php
        $_rowOwned  = supOwns(intval($row['created_by_supervisor'] ?? 0));
        $_rowCanEdit = supCan($permSection,'can_edit') || $_rowOwned;
        $_rowCanDel  = supCan($permSection,'can_delete') || $_rowOwned;
        ?>
        <?php if ($_rowCanEdit): ?>
        <a
        href="edit-arabic-example.php?id=<?php echo intval($row['id']); ?>"
        class="edit-btn">
        تعديل
        </a>
        <?php endif; ?>

        <?php if ($_rowCanDel): ?>
        <a
        href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
        class="delete-btn"
        onclick="return confirm('هل أنت متأكد من حذف الحرف؟');">
        حذف
        </a>
        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

<a
href="<?php echo $isSupervisor ? 'supervisor-dashboard.php' : 'lesson-types.php?subject='.urlencode($subject); ?>"
class="back-link">
رجوع
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;

/* =========================================
   الحروف الإنجليزية
========================================= */

if ($subject === 'اللغة الإنجليزية' && $type === 'الحروف الإنجليزية'):

$englishResultSql = "
SELECT *, COALESCE(created_by_supervisor,0) AS created_by_supervisor
FROM lessons
WHERE subject_name = ?
AND lesson_type = ?
ORDER BY letter_id ASC, id ASC
";

$englishStmt = mysqli_prepare($conn, $englishResultSql);
mysqli_stmt_bind_param($englishStmt, "ss", $subject, $type);
mysqli_stmt_execute($englishStmt);
$englishLettersResult = mysqli_stmt_get_result($englishStmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الحروف الإنجليزية</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.letters-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:28px;
    margin-top:35px;
}

.letter-card{
    background:#fff;
    border-radius:30px;
    padding:28px 18px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.letter-circle{
    width:110px;
    height:110px;
    border-radius:50%;
    background:#E8F3FF;
    margin:0 auto 18px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    color:#21425f;
    font-size:52px;
    font-weight:900;
    font-family:Arial, sans-serif;
}

.letter-circle img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.letter-card h2{
    color:#21425f;
    font-size:24px;
    margin-bottom:18px;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}

.open-btn,
.edit-btn,
.delete-btn{
    padding:10px 18px;
    border-radius:14px;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
}

.open-btn{background:#e8f3ff;color:#21425f;}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.page-title{
    text-align:center;
    width:100%;
}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin-top:30px;
    text-align:center;
    color:#21425f;
    font-size:20px;
    font-weight:bold;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
@media(max-width:700px){.letters-grid{grid-template-columns:repeat(2,1fr);gap:14px;}}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">اللغة الإنجليزية - الحروف الإنجليزية</h1>

<?php if (supCan($permSection,'can_add')): ?>
<a
href="add-english-letter.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="add-btn">
+ إضافة حرف جديد
</a>
<?php endif; ?>

<?php if (mysqli_num_rows($englishLettersResult) > 0): ?>

<div class="letters-grid">

<?php while($row = mysqli_fetch_assoc($englishLettersResult)): ?>

<?php
$title = safeTitle($row);
$englishLetterId = intval($row['letter_id']);
$englishCustomSign = trim((string)($row['custom_sign'] ?? ''));

if ($englishLetterId > 26 && $englishCustomSign !== '') {
    $image = "../" . ltrim($englishCustomSign, '/');
} else {
    $image = "../images/signs/english/" . $englishLetterId . ".png";
}

$englishFallbackImage = $englishCustomSign !== ''
    ? "../" . ltrim($englishCustomSign, '/')
    : "../assets/icons/sign-icon.png";
?>

<div class="letter-card">

    <div class="letter-circle">
        <img
        src="<?php echo htmlspecialchars($image); ?>"
        alt="<?php echo htmlspecialchars($title); ?>"
        onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($englishFallbackImage); ?>';">
    </div>

    <h2><?php echo htmlspecialchars($title); ?></h2>

    <div class="card-actions">

        <a
        href="../subjects/english/english-letter.php?id=<?php echo intval($row['letter_id']); ?>"
        class="open-btn">
        عرض
        </a>

        <?php
        $_rowOwned  = supOwns(intval($row['created_by_supervisor'] ?? 0));
        $_rowCanEdit = supCan($permSection,'can_edit') || $_rowOwned;
        $_rowCanDel  = supCan($permSection,'can_delete') || $_rowOwned;
        ?>
        <?php if ($_rowCanEdit): ?>
        <a
        href="edit-english-letter.php?id=<?php echo intval($row['id']); ?>"
        class="edit-btn">
        تعديل
        </a>
        <?php endif; ?>

        <?php if ($_rowCanDel): ?>
        <a
        href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
        class="delete-btn"
        onclick="return confirm('هل أنت متأكد من حذف الحرف؟');">
        حذف
        </a>
        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty-box">
لا يوجد حروف إنجليزية مضافة بعد
</div>

<?php endif; ?>

<a
href="<?php echo $isSupervisor ? 'supervisor-dashboard.php' : 'lesson-types.php?subject='.urlencode($subject); ?>"
class="back-link">
رجوع
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;

/* =========================================
   الأرقام العربية
========================================= */

if ($subject === 'الرياضيات' && $type === 'الأرقام العربية'):

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

/* ── تهيئة الأرقام العربية الافتراضية في DB (مرة واحدة فقط) ── */
$countRowAr = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM lessons WHERE subject_name='الرياضيات' AND lesson_type='الأرقام العربية' AND letter_id >= 0"
));
if (($countRowAr['c'] ?? 0) == 0) {
    $arabicNums  = [0,1,2,3,4,5,6,7,8,9,10,20,30,40,50,60,70,80,90,100];
    $arabicText  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','١٠','٢٠','٣٠','٤٠','٥٠','٦٠','٧٠','٨٠','٩٠','١٠٠'];
    $arabicNames = ['صفر','واحد','اثنان','ثلاثة','أربعة','خمسة','ستة','سبعة','ثمانية','تسعة','عشرة','عشرون','ثلاثون','أربعون','خمسون','ستون','سبعون','ثمانون','تسعون','مئة'];
    $insAr = mysqli_prepare($conn,
        "INSERT INTO lessons (subject_name, lesson_type, lesson_title, example_word, letter_id)
         VALUES ('الرياضيات','الأرقام العربية',?,?,?)"
    );
    foreach ($arabicNums as $i => $num) {
        mysqli_stmt_bind_param($insAr, "ssi", $arabicText[$i], $arabicNames[$i], $num);
        mysqli_stmt_execute($insAr);
    }
}

$numbersResultSql = "
SELECT *, COALESCE(created_by_supervisor,0) AS created_by_supervisor
FROM lessons
WHERE subject_name = ?
AND lesson_type = ?
ORDER BY letter_id ASC, id ASC
";

$numbersStmt = mysqli_prepare($conn, $numbersResultSql);
mysqli_stmt_bind_param($numbersStmt, "ss", $subject, $type);
mysqli_stmt_execute($numbersStmt);
$numbersResult = mysqli_stmt_get_result($numbersStmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الأرقام العربية</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.letters-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:28px;
    margin-top:35px;
}

.letter-card{
    background:#fff;
    border-radius:30px;
    padding:28px 18px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.letter-circle{
    width:110px;
    height:110px;
    border-radius:50%;
    background:#E8F3FF;
    margin:0 auto 18px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    color:#21425f;
    font-size:42px;
    font-weight:900;
    font-family:Arial,sans-serif;
}

.letter-circle img{
    width:100%;
    height:100%;
    object-fit:contain;
}

.letter-card h2{
    color:#21425f;
    font-size:24px;
    margin-bottom:18px;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}

.open-btn,
.edit-btn,
.delete-btn{
    padding:10px 18px;
    border-radius:14px;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
}

.open-btn{background:#e8f3ff;color:#21425f;}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.page-title{
    text-align:center;
    width:100%;
}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin-top:30px;
    text-align:center;
    color:#21425f;
    font-size:20px;
    font-weight:bold;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
@media(max-width:700px){.letters-grid{grid-template-columns:repeat(2,1fr);gap:14px;}}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">الرياضيات - الأرقام العربية</h1>

<?php if (supCan($permSection,'can_add')): ?><a href="add-arabic-number.php" class="add-btn">
+ إضافة رقم جديد
</a><?php endif; ?>

<?php if ($numbersResult && mysqli_num_rows($numbersResult) > 0): ?>

<div class="letters-grid">

<?php while($row = mysqli_fetch_assoc($numbersResult)): ?>

<?php
$numberId = intval($row['letter_id']);
$title = trim($row['lesson_title'] ?? '');
$name = trim($row['example_word'] ?? '');
$customSign = trim($row['custom_sign'] ?? '');

$defaultSign = "../images/signs/numbers/" . $numberId . ".png";
$defaultSignServer = __DIR__ . "/../images/signs/numbers/" . $numberId . ".png";

if (file_exists($defaultSignServer)) {
    $signImage = $defaultSign;
} elseif ($customSign !== '') {
    $signImage = "../" . ltrim($customSign, '/');
} else {
    $signImage = "../assets/icons/sign-icon.png";
}

$fallbackSign = $customSign !== ''
    ? "../" . ltrim($customSign, '/')
    : "../assets/icons/sign-icon.png";
?>

<div class="letter-card">

    <div class="letter-circle">
        <img
        src="<?php echo htmlspecialchars($signImage); ?>"
        alt="<?php echo htmlspecialchars($title); ?>"
        onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackSign); ?>';">
    </div>

    <h2>
        <?php echo htmlspecialchars($title); ?>
    </h2>

    <?php if ($name !== ''): ?>
        <div style="color:#6b7f91;font-weight:bold;margin-top:-10px;margin-bottom:16px;">
            <?php echo htmlspecialchars($name); ?>
        </div>
    <?php endif; ?>

    <div class="card-actions">

        <a
        href="../subjects/math/arabic-number.php?id=<?php echo $numberId; ?>"
        class="open-btn">
        عرض
        </a>

        <?php
        $_rowOwned  = supOwns(intval($row['created_by_supervisor'] ?? 0));
        $_rowCanEdit = supCan($permSection,'can_edit') || $_rowOwned;
        $_rowCanDel  = supCan($permSection,'can_delete') || $_rowOwned;
        ?>
        <?php if ($_rowCanEdit): ?>
        <a href="edit-arabic-number.php?id=<?php echo intval($row['id']); ?>" class="edit-btn">تعديل</a>
        <?php endif; ?>

        <?php if ($_rowCanDel): ?>
        <a href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
           class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف الرقم؟');">حذف</a>
        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty-box">
لا يوجد أرقام مضافة من لوحة المدير بعد
</div>

<?php endif; ?>

<a
href="lesson-types.php?subject=<?php echo urlencode($subject); ?>"
class="back-link">
رجوع للأقسام
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;



/* =========================================
   الأرقام الإنجليزية
========================================= */

if ($subject === 'اللغة الإنجليزية' && $type === 'الأرقام الإنجليزية'):

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

/* ── تهيئة الأرقام الإنجليزية الافتراضية في DB (مرة واحدة فقط) ── */
$countRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM lessons WHERE subject_name='اللغة الإنجليزية' AND lesson_type='الأرقام الإنجليزية' AND letter_id > 0"
));
if (($countRow['c'] ?? 0) == 0) {
    $defaultEnglish = [
        1=>'One', 2=>'Two', 3=>'Three', 4=>'Four', 5=>'Five',
        6=>'Six', 7=>'Seven', 8=>'Eight', 9=>'Nine', 10=>'Ten',
        20=>'Twenty', 30=>'Thirty', 40=>'Forty', 50=>'Fifty',
        60=>'Sixty', 70=>'Seventy', 80=>'Eighty', 90=>'Ninety', 100=>'One Hundred',
    ];
    $ins = mysqli_prepare($conn,
        "INSERT INTO lessons (subject_name, lesson_type, lesson_title, example_word, letter_id)
         VALUES ('اللغة الإنجليزية','الأرقام الإنجليزية',?,?,?)"
    );
    foreach ($defaultEnglish as $num => $name) {
        $numStr = (string)$num;
        mysqli_stmt_bind_param($ins, "ssi", $numStr, $name, $num);
        mysqli_stmt_execute($ins);
    }
}

$numbersResultSql = "
SELECT *, COALESCE(created_by_supervisor,0) AS created_by_supervisor
FROM lessons
WHERE subject_name = ?
AND lesson_type = ?
ORDER BY letter_id ASC, id ASC
";

$numbersStmt = mysqli_prepare($conn, $numbersResultSql);
mysqli_stmt_bind_param($numbersStmt, "ss", $subject, $type);
mysqli_stmt_execute($numbersStmt);
$numbersResult = mysqli_stmt_get_result($numbersStmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الأرقام الإنجليزية</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.letters-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:28px;margin-top:35px;}
.letter-card{background:#fff;border-radius:30px;padding:28px 18px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,.08);}
.letter-circle{width:110px;height:110px;border-radius:50%;background:#E8F3FF;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#21425f;font-size:42px;font-weight:900;font-family:Arial,sans-serif;}
.letter-circle img{width:100%;height:100%;object-fit:contain;}
.letter-card h2{color:#21425f;font-size:24px;margin-bottom:18px;}
.card-actions{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;}
.open-btn,.edit-btn,.delete-btn{padding:10px 18px;border-radius:14px;text-decoration:none;font-size:16px;font-weight:bold;}
.open-btn{background:#e8f3ff;color:#21425f;}.edit-btn{background:#21425f;color:#fff;}.delete-btn{background:#d93025;color:#fff;}.page-title{text-align:center;width:100%;}
.empty-box{background:#fff;border-radius:24px;padding:28px;margin-top:30px;text-align:center;color:#21425f;font-size:20px;font-weight:bold;box-shadow:0 8px 20px rgba(0,0,0,.07);}
@media(max-width:700px){.letters-grid{grid-template-columns:repeat(2,1fr);gap:14px;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">
<h1 class="page-title">اللغة الإنجليزية - الأرقام الإنجليزية</h1>
<?php if (supCan($permSection,'can_add')): ?><a href="add-english-number.php" class="add-btn">+ إضافة رقم جديد</a><?php endif; ?>

<?php if ($numbersResult && mysqli_num_rows($numbersResult) > 0): ?>
<div class="letters-grid">
<?php while($row = mysqli_fetch_assoc($numbersResult)): ?>
<?php
$numberId = intval($row['letter_id']);
$title = trim($row['lesson_title'] ?? '');
$name = trim($row['example_word'] ?? '');
$customSign = trim($row['custom_sign'] ?? '');
$defaultSign = "../images/signs/numbers/" . $numberId . ".png";
$defaultSignServer = __DIR__ . "/../images/signs/numbers/" . $numberId . ".png";
if (file_exists($defaultSignServer)) {
    $signImage = $defaultSign;
} elseif ($customSign !== '') {
    $signImage = "../" . ltrim($customSign, '/');
} else {
    $signImage = "../assets/icons/sign-icon.png";
}
$fallbackSign = $customSign !== '' ? "../" . ltrim($customSign, '/') : "../assets/icons/sign-icon.png";
?>
<div class="letter-card">
    <div class="letter-circle">
        <img src="<?php echo htmlspecialchars($signImage); ?>" alt="<?php echo htmlspecialchars($title); ?>" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackSign); ?>';">
    </div>
    <h2><?php echo htmlspecialchars($title); ?></h2>
    <?php if ($name !== ''): ?><div style="color:#6b7f91;font-weight:bold;margin-top:-10px;margin-bottom:16px;"><?php echo htmlspecialchars($name); ?></div><?php endif; ?>
    <div class="card-actions">
        <a href="../subjects/english/english-number.php?id=<?php echo $numberId; ?>" class="open-btn">عرض</a>
        <?php
        $_rowOwned  = supOwns(intval($row['created_by_supervisor'] ?? 0));
        $_rowCanEdit = supCan($permSection,'can_edit') || $_rowOwned;
        $_rowCanDel  = supCan($permSection,'can_delete') || $_rowOwned;
        ?>
        <?php if ($_rowCanEdit): ?>
        <a href="edit-english-number.php?id=<?php echo intval($row['id']); ?>" class="edit-btn">تعديل</a>
        <?php endif; ?>
        <?php if ($_rowCanDel): ?>
        <a href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>" class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف الرقم؟');">حذف</a>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="empty-box">لا يوجد أرقام إنجليزية مضافة من لوحة المدير بعد</div>
<?php endif; ?>
<a href="lesson-types.php?subject=<?php echo urlencode($subject); ?>" class="back-link">رجوع للأقسام</a>
</main>
</div>
</body>
</html>
<?php
exit;
endif;

/* =========================================
   القصص  (الثقافة العامة - القصص)
========================================= */

if ($subject === 'الثقافة العامة' && $type === 'القصص'):

/* 1) إنشاء جدول القصص تلقائياً إذا ما كان موجود */
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

/* 1-أ) التأكد من وجود كل أعمدة جدول القصص (لو الجدول قديم وناقص أعمدة) */
$storyCoverCols = [
    'title'        => "ALTER TABLE stories ADD COLUMN title VARCHAR(255) NULL",
    'poster_image' => "ALTER TABLE stories ADD COLUMN poster_image VARCHAR(255) NULL",
    'sign_video'   => "ALTER TABLE stories ADD COLUMN sign_video VARCHAR(255) NULL",
    'story_link'   => "ALTER TABLE stories ADD COLUMN story_link VARCHAR(255) NULL",
    'sort_order'   => "ALTER TABLE stories ADD COLUMN sort_order INT DEFAULT 0",
    'cover_image'  => "ALTER TABLE stories ADD COLUMN cover_image VARCHAR(255) NULL",
    'cover_video'  => "ALTER TABLE stories ADD COLUMN cover_video VARCHAR(255) NULL",
    'cover_title'  => "ALTER TABLE stories ADD COLUMN cover_title VARCHAR(500) NULL",
];
foreach ($storyCoverCols as $col => $alterSql) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM stories LIKE '$col'");
    if ($colCheck && mysqli_num_rows($colCheck) == 0) {
        mysqli_query($conn, $alterSql);
    }
}

/* 1-ب) جدول صفحات القصص (لكل قصة عدة صفحات) */
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

/* 2) تعبئة القصص الأربعة الموجودة (مرة وحدة فقط إذا الجدول فاضي) */
$storyCountRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM stories");
$storyCountRow = $storyCountRes ? mysqli_fetch_assoc($storyCountRes) : ['c' => 1];

if (intval($storyCountRow['c']) === 0) {

    $defaultStories = [
        ['رحلتي إلى المدرسة', 'images/general/stories/images/poster/story1.png', 'assets/videos/story1-sign.mp4', 'story1.php', 1],
        ['إطعام الحيوانات',   'images/general/stories/images/poster/story2.png', 'assets/videos/story2-sign.mp4', 'story2.php', 2],
        ['زراعة البذور',      'images/general/stories/images/poster/story3.png', 'assets/videos/story3-sign.mp4', 'story3.php', 3],
        ['أنا أحب الرسم',     'images/general/stories/images/poster/story4.png', 'assets/videos/story4-sign.mp4', 'story4.php', 4],
    ];

    $seedStmt = mysqli_prepare(
        $conn,
        "INSERT INTO stories (title, poster_image, sign_video, story_link, sort_order) VALUES (?,?,?,?,?)"
    );

    foreach ($defaultStories as $st) {
        mysqli_stmt_bind_param($seedStmt, "ssssi", $st[0], $st[1], $st[2], $st[3], $st[4]);
        mysqli_stmt_execute($seedStmt);
    }
}

/* 3) جلب كل القصص */
$storiesResult = mysqli_query(
    $conn,
    "SELECT *, COALESCE(created_by_supervisor,0) AS created_by_supervisor FROM stories ORDER BY sort_order ASC, id ASC"
);

$storiesPublicDir = "../subjects/general/";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>القصص</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.letters-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:28px;
    margin-top:35px;
}

.letter-card{
    background:#fff;
    border-radius:30px;
    padding:18px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.story-poster{
    width:100%;
    height:190px;
    border-radius:22px;
    object-fit:cover;
    background:#E8F3FF;
    margin-bottom:16px;
}

.letter-card h2{
    color:#21425f;
    font-size:22px;
    margin-bottom:18px;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}

.open-btn,
.edit-btn,
.delete-btn{
    padding:10px 18px;
    border-radius:14px;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
}

.open-btn{background:#e8f3ff;color:#21425f;}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.page-title{
    text-align:center;
    width:100%;
}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin-top:30px;
    text-align:center;
    color:#21425f;
    font-size:20px;
    font-weight:bold;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">الثقافة العامة - القصص</h1>

<?php if (supCan($permSection,'can_add')): ?><a href="add-story.php" class="add-btn">
+ إضافة قصة جديدة
</a><?php endif; ?>

<?php if ($storiesResult && mysqli_num_rows($storiesResult) > 0): ?>

<div class="letters-grid">

<?php while($row = mysqli_fetch_assoc($storiesResult)): ?>

<?php
$storyTitle = trim((string)($row['title'] ?? ''));
$storyPoster = trim((string)($row['poster_image'] ?? ''));
$storyLink = trim((string)($row['story_link'] ?? ''));

$posterSrc = $storyPoster !== ''
    ? "../" . ltrim($storyPoster, '/')
    : "../assets/icons/sign-icon.png";

$viewHref = $storyLink !== ''
    ? $storiesPublicDir . ltrim($storyLink, '/')
    : "#";
?>

<div class="letter-card">

    <img
    src="<?php echo htmlspecialchars($posterSrc); ?>"
    class="story-poster"
    alt="<?php echo htmlspecialchars($storyTitle); ?>"
    onerror="this.onerror=null;this.src='../assets/icons/sign-icon.png';">

    <h2><?php echo htmlspecialchars($storyTitle); ?></h2>

    <div class="card-actions">

        <a
        href="<?php echo htmlspecialchars($viewHref); ?>"
        class="open-btn"
        target="_blank">
        عرض
        </a>

        <?php
        $_storyOwned = supOwns(intval($row['created_by_supervisor'] ?? 0));
        $_storyCanEdit = supCan($permSection,'can_edit') || $_storyOwned;
        $_storyCanDel  = supCan($permSection,'can_delete') || $_storyOwned;
        ?>
        <?php if ($_storyCanEdit): ?>
        <a
        href="edit-story.php?id=<?php echo intval($row['id']); ?>"
        class="edit-btn">
        تعديل
        </a>
        <?php endif; ?>

        <?php if ($_storyCanDel): ?>
        <a
        href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
        class="delete-btn"
        onclick="return confirm('هل أنت متأكد من حذف القصة؟');">
        حذف
        </a>
        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty-box">
لا يوجد قصص مضافة بعد
</div>

<?php endif; ?>

<a
href="lesson-types.php?subject=<?php echo urlencode($subject); ?>"
class="back-link">
رجوع للأقسام
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;

/* =========================================
   الحيوانات  (الثقافة العامة - الحيوانات)
========================================= */

if ($subject === 'الثقافة العامة' && $type === 'الحيوانات'):

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
ensure_animals_setup($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الحيوانات</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.animals-choice{
    display:grid;
    grid-template-columns:1fr;
    gap:28px;
    max-width:480px;
    margin:35px auto 0;
}

.animals-choice a{
    background:#fff;
    border-radius:30px;
    padding:34px 22px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    text-decoration:none;
    color:#21425f;
    transition:.22s ease;
    border:2px solid #e2edf7;
}

.animals-choice a:hover{
    transform:translateY(-5px);
    box-shadow:0 18px 36px rgba(33,66,95,.16);
}

.animals-choice .emoji{
    font-size:60px;
    margin-bottom:14px;
    display:block;
}

.animals-choice h2{
    font-size:26px;
    font-weight:900;
}

.animals-choice span{
    display:block;
    margin-top:8px;
    color:#6a849a;
    font-weight:800;
    font-size:15px;
}
.choice-perms{ display:flex; justify-content:center; gap:5px; margin-top:10px; flex-wrap:wrap; }
.cperm{ font-size:10px; font-weight:900; padding:3px 8px; border-radius:6px; }
.cperm-add{ background:#e8fff0; color:#1a7a40; }
.cperm-edit{ background:#fff6e0; color:#8a5e00; }
.cperm-del{ background:#ffe8e8; color:#c0392b; }
.page-title{text-align:center;width:100%;}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">الثقافة العامة - الحيوانات</h1>

<div class="animals-choice">

    <?php if (supCan('gc_animals_wild', 'can_view')): ?>
    <a href="manage-animals.php?category=wild" class="choice-card">
        <span class="emoji">🦁</span>
        <h2>الحيوانات المفترسة</h2>
        <div class="choice-perms">
            <?php if (supCan('gc_animals_wild','can_add')): ?><span class="cperm cperm-add">إضافة</span><?php endif; ?>
            <?php if (supCan('gc_animals_wild','can_edit')): ?><span class="cperm cperm-edit">تعديل</span><?php endif; ?>
            <?php if (supCan('gc_animals_wild','can_delete')): ?><span class="cperm cperm-del">حذف</span><?php endif; ?>
        </div>
    </a>
    <?php else: ?>
    <div class="choice-card choice-locked">
        <span class="emoji">🦁</span>
        <h2>الحيوانات المفترسة</h2>
        <span class="locked-label">🔒 غير مصرح</span>
    </div>
    <?php endif; ?>

    <?php if (supCan('gc_animals_pet', 'can_view')): ?>
    <a href="manage-animals.php?category=pet" class="choice-card">
        <span class="emoji">🐮</span>
        <h2>الحيوانات الأليفة</h2>
        <div class="choice-perms">
            <?php if (supCan('gc_animals_pet','can_add')): ?><span class="cperm cperm-add">إضافة</span><?php endif; ?>
            <?php if (supCan('gc_animals_pet','can_edit')): ?><span class="cperm cperm-edit">تعديل</span><?php endif; ?>
            <?php if (supCan('gc_animals_pet','can_delete')): ?><span class="cperm cperm-del">حذف</span><?php endif; ?>
        </div>
    </a>
    <?php else: ?>
    <div class="choice-card choice-locked">
        <span class="emoji">🐮</span>
        <h2>الحيوانات الأليفة</h2>
        <span class="locked-label">🔒 غير مصرح</span>
    </div>
    <?php endif; ?>

</div>

<a
href="lesson-types.php?subject=<?php echo urlencode($subject); ?>"
class="back-link">
رجوع للأقسام
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;

/* =========================================
   الفواكه والخضار  (الثقافة العامة - فواكه وخضروات)
========================================= */

if ($subject === 'الثقافة العامة' && $type === 'فواكه وخضروات'):

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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>الفواكه والخضار</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.animals-choice{
    display:grid;
    grid-template-columns:1fr;
    gap:28px;
    max-width:480px;
    margin:35px auto 0;
}
.animals-choice a{
    background:#fff;
    border-radius:30px;
    padding:34px 22px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    text-decoration:none;
    color:#21425f;
    transition:.22s ease;
    border:2px solid #e2edf7;
}
.animals-choice a:hover{ transform:translateY(-5px); box-shadow:0 18px 36px rgba(33,66,95,.16); }
.animals-choice .emoji{ font-size:60px; margin-bottom:14px; display:block; }
.animals-choice h2{ font-size:26px; font-weight:900; }
.animals-choice span{ display:block; margin-top:8px; color:#6a849a; font-weight:800; font-size:15px; }
.page-title{text-align:center;width:100%;}

/* ── بطاقة الاختيار مع مؤشر الصلاحية ── */
.choice-card{
    background:#fff; border-radius:30px; padding:34px 22px; text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08); text-decoration:none; color:#21425f;
    transition:.22s ease; border:2px solid #e2edf7; display:block;
}
.choice-card:hover{ transform:translateY(-5px); box-shadow:0 18px 36px rgba(33,66,95,.16); }
.choice-locked{
    background:#f8fafc; border:2px dashed #d0dce8;
    pointer-events:none; opacity:.55;
}
.locked-label{ display:inline-block; margin-top:10px; font-size:13px; font-weight:800; color:#a0b0c0; }
.choice-perms{ display:flex; justify-content:center; gap:5px; margin-top:10px; flex-wrap:wrap; }
.cperm{ font-size:10px; font-weight:900; padding:3px 8px; border-radius:6px; }
.cperm-add{ background:#e8fff0; color:#1a7a40; }
.cperm-edit{ background:#fff6e0; color:#8a5e00; }
.cperm-del{ background:#ffe8e8; color:#c0392b; }
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">الثقافة العامة - الفواكه والخضار</h1>

<div class="animals-choice">

    <?php if (supCan('gc_food_vegetable', 'can_view')): ?>
    <a href="manage-food.php?category=vegetable" class="choice-card">
        <span class="emoji">🥕</span>
        <h2>الخضار</h2>
        <div class="choice-perms">
            <?php if (supCan('gc_food_vegetable','can_add')): ?><span class="cperm cperm-add">إضافة</span><?php endif; ?>
            <?php if (supCan('gc_food_vegetable','can_edit')): ?><span class="cperm cperm-edit">تعديل</span><?php endif; ?>
            <?php if (supCan('gc_food_vegetable','can_delete')): ?><span class="cperm cperm-del">حذف</span><?php endif; ?>
        </div>
    </a>
    <?php else: ?>
    <div class="choice-card choice-locked">
        <span class="emoji">🥕</span>
        <h2>الخضار</h2>
        <span class="locked-label">🔒 غير مصرح</span>
    </div>
    <?php endif; ?>

    <?php if (supCan('gc_food_fruit', 'can_view')): ?>
    <a href="manage-food.php?category=fruit" class="choice-card">
        <span class="emoji">🍓</span>
        <h2>الفواكه</h2>
        <div class="choice-perms">
            <?php if (supCan('gc_food_fruit','can_add')): ?><span class="cperm cperm-add">إضافة</span><?php endif; ?>
            <?php if (supCan('gc_food_fruit','can_edit')): ?><span class="cperm cperm-edit">تعديل</span><?php endif; ?>
            <?php if (supCan('gc_food_fruit','can_delete')): ?><span class="cperm cperm-del">حذف</span><?php endif; ?>
        </div>
    </a>
    <?php else: ?>
    <div class="choice-card choice-locked">
        <span class="emoji">🍓</span>
        <h2>الفواكه</h2>
        <span class="locked-label">🔒 غير مصرح</span>
    </div>
    <?php endif; ?>

</div>

<a
href="lesson-types.php?subject=<?php echo urlencode($subject); ?>"
class="back-link">
رجوع للأقسام
</a>

</main>
</div>
</body>
</html>

<?php
exit;
endif;

/* =========================================
   باقي الأقسام
========================================= */

$sql = "SELECT * FROM lessons
WHERE subject_name = ?
AND lesson_type = ?
ORDER BY id ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $subject, $type);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title><?php echo htmlspecialchars($type); ?></title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.cards-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:28px;
    margin-top:35px;
}

.admin-card{
    background:#fff;
    border-radius:30px;
    padding:28px 18px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
    text-decoration:none;
    color:#21425f;
}

.card-actions{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
    margin-top:18px;
}

.open-btn,
.edit-btn,
.delete-btn{
    padding:10px 18px;
    border-radius:14px;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
}

.open-btn{background:#e8f3ff;color:#21425f;}
.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin-top:30px;
    text-align:center;
    color:#21425f;
    font-size:20px;
    font-weight:bold;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">
<?php echo htmlspecialchars($subject); ?> - <?php echo htmlspecialchars($type); ?>
</h1>

<?php if (supCan($permSection,'can_add')): ?>
<a
href="add-lesson.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="add-btn">
+ إضافة بطاقة
</a>
<?php endif; ?>

<?php if (mysqli_num_rows($result) > 0): ?>

<div class="cards-grid">

<?php while($row = mysqli_fetch_assoc($result)): ?>

<?php
$title = safeTitle($row);
?>

<div class="admin-card">

<h2><?php echo htmlspecialchars($title); ?></h2>

<div class="card-actions">

<a href="view-lesson.php?id=<?php echo intval($row['id']); ?>" class="open-btn">
عرض
</a>

<?php if (supCan($permSection,'can_edit')): ?>
<a href="edit-lesson.php?id=<?php echo intval($row['id']); ?>" class="edit-btn">
تعديل
</a>
<?php endif; ?>

<?php if (supCan($permSection,'can_delete')): ?>
<a
href="subject-lessons.php?delete=<?php echo intval($row['id']); ?>&subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="delete-btn"
onclick="return confirm('هل أنت متأكد من حذف البطاقة؟');">
حذف
</a>
<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty-box">
لا يوجد بطاقات مضافة بعد
</div>

<?php endif; ?>

<a
href="lesson-types.php?subject=<?php echo urlencode($subject); ?>"
class="back-link">
رجوع للأقسام
</a>

</main>
</div>
</body>
</html>
