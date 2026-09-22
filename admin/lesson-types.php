<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

$subject = $_GET['subject'] ?? '';

// ── فحص صلاحية المشرف حسب الـ subject ──────────────────────
if ($isSupervisor) {
    $subjectPermMap = [
        'اللغة العربية'   => 'arabic_letters',
        'اللغة الإنجليزية'=> 'english_letters',
        'الرياضيات'       => 'arabic_numbers',
        'الثقافة العامة'  => 'general_culture',
    ];
    $subjectSection = $subjectPermMap[$subject] ?? null;
    if (!$subjectSection || !supCan($subjectSection, 'can_view')) {
        header('Location: supervisor-dashboard.php'); exit;
    }
}

// ── خريطة أنواع الثقافة العامة → مفاتيح الصلاحية ──────────
$gcTypePermMap = [
    'القصص'           => 'stories',
    'الحيوانات'       => 'gc_animals',
    'الطقس'           => 'gc_weather',
    'الفصول الأربعة' => 'gc_seasons',
    'أيام الأسبوع'    => 'gc_days',
    'فواكه وخضروات'   => 'gc_food',
    'أركان الإسلام'   => 'gc_islam',
];

$types = [];

if ($subject === 'اللغة العربية') {
    $types = ['الحروف العربية'];
} elseif ($subject === 'اللغة الإنجليزية') {
    $types = ['الحروف الإنجليزية', 'الأرقام الإنجليزية'];
} elseif ($subject === 'الرياضيات') {
    $types = ['الأرقام العربية', 'أنشطة الرياضيات'];
} elseif ($subject === 'الثقافة العامة') {
    $allTypes = [
        'القصص', 'الحيوانات', 'الطقس', 'الفصول الأربعة',
        'أيام الأسبوع', 'فواكه وخضروات', 'أركان الإسلام'
    ];
    if ($isSupervisor) {
        foreach ($allTypes as $t) {
            $permKey = $gcTypePermMap[$t] ?? 'general_culture';
            if (supCan($permKey, 'can_view')) $types[] = $t;
        }
    } else {
        $types = $allTypes;
    }
}

/* ---- إنشاء الجداول تلقائياً ---- */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS general_sections (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    icon       VARCHAR(20)  DEFAULT '★',
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// جدول أيقونات الأنواع المخصصة
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS type_icons (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    subject    VARCHAR(100) NOT NULL,
    type_name  VARCHAR(100) NOT NULL,
    icon_file  VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subject_type (subject, type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/* ---- معالجة رفع الأيقونة (AJAX POST) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_type_icon') {
    header('Content-Type: application/json; charset=utf-8');

    $typeName   = trim($_POST['type_name']   ?? '');
    $subjectVal = trim($_POST['subject_val'] ?? '');

    if (!$typeName || !$subjectVal) {
        echo json_encode(['ok'=>false, 'msg'=>'بيانات ناقصة']); exit;
    }

    // صلاحية الرفع — فقط الماستر أو من عنده can_add
    if ($isSupervisor && !supCan('general_culture', 'can_add')) {
        echo json_encode(['ok'=>false, 'msg'=>'ليس لديك صلاحية']); exit;
    }

    if (empty($_FILES['icon_file']['tmp_name'])) {
        echo json_encode(['ok'=>false, 'msg'=>'لم يُرفع أي ملف']); exit;
    }

    $file     = $_FILES['icon_file'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','gif','webp','mp4','webm'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['ok'=>false, 'msg'=>'امتداد غير مسموح به']); exit;
    }
    if ($file['size'] > 50 * 1024 * 1024) {
        echo json_encode(['ok'=>false, 'msg'=>'حجم الملف يتجاوز 50 MB']); exit;
    }

    $uploadDir = '../uploads/type-icons/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    $newName  = 'type_' . md5($subjectVal . $typeName) . '.' . $ext;
    $destPath = $uploadDir . $newName;
    $dbPath   = 'uploads/type-icons/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['ok'=>false, 'msg'=>'فشل في حفظ الملف']); exit;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO type_icons (subject, type_name, icon_file)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE icon_file = VALUES(icon_file), created_at = NOW()"
    );
    mysqli_stmt_bind_param($stmt, 'sss', $subjectVal, $typeName, $dbPath);
    mysqli_stmt_execute($stmt);

    $isVideo = in_array($ext, ['mp4','webm']);
    echo json_encode(['ok'=>true, 'path'=>'../'.$dbPath, 'is_video'=>$isVideo]);
    exit;
}

/* ---- حذف أيقونة مخصصة (AJAX POST) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_type_icon') {
    header('Content-Type: application/json; charset=utf-8');

    $typeName   = trim($_POST['type_name']   ?? '');
    $subjectVal = trim($_POST['subject_val'] ?? '');

    if ($isSupervisor && !supCan('general_culture', 'can_add')) {
        echo json_encode(['ok'=>false]); exit;
    }

    $stmt = mysqli_prepare($conn,
        "SELECT icon_file FROM type_icons WHERE subject=? AND type_name=?"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $subjectVal, $typeName);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row) {
        $filePath = '../' . $row['icon_file'];
        if (file_exists($filePath)) @unlink($filePath);

        $del = mysqli_prepare($conn,
            "DELETE FROM type_icons WHERE subject=? AND type_name=?"
        );
        mysqli_stmt_bind_param($del, 'ss', $subjectVal, $typeName);
        mysqli_stmt_execute($del);
    }

    echo json_encode(['ok'=>true]); exit;
}

/* ---- جلب الأيقونات المخصصة للـ subject الحالي ---- */
$customTypeIcons = [];
if ($subject) {
    $stmt = mysqli_prepare($conn,
        "SELECT type_name, icon_file FROM type_icons WHERE subject=?"
    );
    mysqli_stmt_bind_param($stmt, 's', $subject);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $customTypeIcons[$r['type_name']] = $r['icon_file'];
    }
}

/* ---- جلب الأقسام المخصصة ---- */
$custom_sections = [];
if ($subject === 'الثقافة العامة') {
    $res = mysqli_query($conn, "SELECT * FROM general_sections ORDER BY sort_order ASC, id ASC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if ($isSupervisor && !supCan('gc_custom_' . $row['id'], 'can_view')) continue;
            $custom_sections[] = $row;
        }
    }
}

/* ---- دوال الأيقونة ---- */
function typeIconMedia($type) {
    $map = [
        'الحروف العربية'    => ['type'=>'video', 'src'=>'../assets/icons/arabic.mp4',    'fallback'=>'أ'],
        'الحروف الإنجليزية' => ['type'=>'video', 'src'=>'../assets/icons/letter.mp4',    'fallback'=>'A'],
        'الأرقام الإنجليزية'=> ['type'=>'video', 'src'=>'../assets/icons/numbers.mp4',   'fallback'=>'123'],
        'الأرقام العربية'   => ['type'=>'video', 'src'=>'../assets/icons/arabic-num.mp4','fallback'=>'١٢٣'],
        'أنشطة الرياضيات'  => ['type'=>'video', 'src'=>'../assets/icons/math.mp4',       'fallback'=>'＋'],
        'القصص'             => ['type'=>'video', 'src'=>'../assets/icons/story.mp4',      'fallback'=>'📖'],
        'الحيوانات'         => ['type'=>'video', 'src'=>'../assets/icons/animals.mp4',    'fallback'=>'🐾'],
        'الطقس'             => ['type'=>'video', 'src'=>'../assets/icons/weather.mp4',    'fallback'=>'☁'],
        'الفصول الأربعة'   => ['type'=>'video', 'src'=>'../assets/icons/season.mp4',     'fallback'=>'🌿'],
        'أيام الأسبوع'      => ['type'=>'video', 'src'=>'../assets/icons/days.mp4',       'fallback'=>'📅'],
        'فواكه وخضروات'     => ['type'=>'video', 'src'=>'../assets/icons/food.mp4',       'fallback'=>'🍎'],
        'أركان الإسلام'     => ['type'=>'video', 'src'=>'../assets/icons/islamic.mp4',    'fallback'=>'☪'],
    ];
    return $map[$type] ?? ['type'=>'text', 'src'=>'', 'fallback'=>'★'];
}
function typeIcon($type) { return typeIconMedia($type)['fallback']; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title><?php echo htmlspecialchars($subject); ?></title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f4f8fc;}
.page-title{text-align:center;color:#21425f;font-size:36px;font-weight:900;margin-bottom:12px;}
.subject-subtitle{text-align:center;color:#6a849a;font-size:18px;font-weight:800;margin-bottom:35px;}
.add-section-wrap{display:flex;justify-content:center;gap:14px;margin-bottom:32px;flex-wrap:wrap;}
.add-section-btn{background:#28b978;color:#fff;text-decoration:none;padding:15px 30px;border-radius:18px;font-size:18px;font-weight:900;box-shadow:0 10px 24px rgba(40,185,120,.22);transition:.25s ease;}
.add-section-btn:hover{transform:translateY(-3px);background:#22a86c;}

/* ── شبكة البطاقات ── */
.types-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:24px;}
@media(max-width:700px){
    .types-grid{grid-template-columns:repeat(2,1fr);}
    .types-grid.gc-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:400px){
    .types-grid,.types-grid.gc-grid{grid-template-columns:1fr;}
}
.type-card{position:relative;min-height:185px;border-radius:30px;background:#ffffff;text-decoration:none;padding:26px 22px;box-shadow:0 12px 30px rgba(33,66,95,.09);border:1px solid #e2edf7;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;transition:.25s ease;}
.type-card::before{content:"";position:absolute;inset:12px;border-radius:24px;border:2px dashed #d6e8f7;pointer-events:none;}
.type-card::after{content:"";position:absolute;width:95px;height:95px;border-radius:50%;background:#f1f8ff;left:-30px;bottom:-30px;}
.type-card:hover{transform:translateY(-6px);box-shadow:0 18px 42px rgba(33,66,95,.15);}

/* ── دائرة الأيقونة ── */
.type-icon{width:78px;height:78px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:16px;position:relative;z-index:2;box-shadow:0 8px 18px rgba(0,0,0,.08),inset 0 0 0 4px rgba(255,255,255,.75);flex-shrink:0;}
.type-icon video,.type-icon img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;}
.type-icon .icon-fallback{font-size:32px;font-weight:900;line-height:1;color:#21425f;display:flex;align-items:center;justify-content:center;width:100%;height:100%;}

.type-card h2{margin:0;color:#21425f;font-size:23px;font-weight:900;position:relative;z-index:2;text-align:center;}
.type-card span.card-sub{margin-top:12px;color:#6a849a;font-size:15px;font-weight:800;position:relative;z-index:2;}

/* ── زر تعديل الأيقونة ── */
.icon-edit-btn{
    position:absolute;
    top:10px;
    left:10px;
    z-index:10;
    width:34px;height:34px;
    border-radius:50%;
    background:#21425f;
    color:#fff;
    border:none;
    cursor:pointer;
    font-size:15px;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 4px 12px rgba(0,0,0,.18);
    transition:.2s ease;
    text-decoration:none;
}
.icon-edit-btn:hover{background:#3a6ea5;transform:scale(1.1);}

/* ── زر حذف الأيقونة المخصصة ── */
.icon-delete-btn{
    position:absolute;
    top:10px;
    left:50px;
    z-index:10;
    width:34px;height:34px;
    border-radius:50%;
    background:#e05454;
    color:#fff;
    border:none;
    cursor:pointer;
    font-size:17px;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 4px 12px rgba(0,0,0,.18);
    transition:.2s ease;
}
.icon-delete-btn:hover{background:#c03030;transform:scale(1.1);}

/* ── Modal ── */
.modal-overlay{
    display:none;
    position:fixed;inset:0;
    background:rgba(0,0,0,.5);
    z-index:9000;
    align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex;}
.modal-box{
    background:#fff;
    border-radius:24px;
    padding:32px 28px;
    width:420px;max-width:95vw;
    box-shadow:0 24px 60px rgba(0,0,0,.22);
    position:relative;
    animation:modalIn .25s ease;
}
@keyframes modalIn{from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.modal-title{font-size:22px;font-weight:900;color:#21425f;margin-bottom:20px;text-align:center;}
.modal-close{
    position:absolute;top:14px;left:14px;
    width:34px;height:34px;border-radius:50%;
    background:#f0f4f8;border:none;cursor:pointer;
    font-size:20px;color:#21425f;
    display:flex;align-items:center;justify-content:center;
    transition:.2s ease;
}
.modal-close:hover{background:#e2edf7;}

/* منطقة السحب والإفلات */
.drop-zone{
    border:2px dashed #b0c8e0;
    border-radius:16px;
    padding:30px 20px;
    text-align:center;
    cursor:pointer;
    transition:.2s ease;
    background:#f8fbff;
    margin-bottom:18px;
    position:relative;
}
.drop-zone:hover,.drop-zone.dragover{border-color:#3a8fd4;background:#eaf4ff;}
.drop-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.drop-zone-icon{font-size:38px;margin-bottom:8px;}
.drop-zone-text{color:#6a849a;font-size:15px;font-weight:700;}
.drop-zone-hint{color:#aac0d0;font-size:13px;margin-top:4px;}

/* معاينة الملف */
.preview-wrap{display:none;margin-bottom:18px;text-align:center;}
.preview-wrap video,.preview-wrap img{
    width:100px;height:100px;object-fit:cover;
    border-radius:50%;
    box-shadow:0 6px 18px rgba(0,0,0,.12);
    border:3px solid #e2edf7;
}
.preview-name{font-size:13px;color:#6a849a;margin-top:8px;font-weight:700;}

/* زر الرفع */
.upload-submit-btn{
    width:100%;padding:14px;border:none;border-radius:14px;
    background:linear-gradient(135deg,#3a8fd4,#21425f);
    color:#fff;font-size:17px;font-weight:900;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(33,66,95,.20);
    transition:.25s ease;
    font-family:Arial,sans-serif;
}
.upload-submit-btn:hover{transform:translateY(-2px);}
.upload-submit-btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}

/* شريط التقدم */
.progress-bar-wrap{display:none;margin-bottom:14px;}
.progress-bar-bg{background:#e2edf7;border-radius:999px;height:10px;overflow:hidden;}
.progress-bar-fill{height:100%;width:0;background:linear-gradient(90deg,#28b978,#3a8fd4);border-radius:999px;transition:width .2s ease;}

/* رسالة النتيجة */
.upload-msg{text-align:center;font-size:15px;font-weight:700;margin-top:10px;min-height:22px;}
.upload-msg.ok{color:#28b978;}
.upload-msg.err{color:#e05454;}

.back-link{margin:36px auto 0;width:max-content;display:flex;align-items:center;justify-content:center;padding:14px 36px;border-radius:18px;background:#21425f;color:#fff;text-decoration:none;font-size:18px;font-weight:900;box-shadow:0 10px 24px rgba(33,66,95,.16);transition:.25s ease;}
.back-link:hover{transform:translateY(-3px);}
@media(max-width:700px){.page-title{font-size:30px;}.types-grid{grid-template-columns:1fr;max-width:520px;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<h1 class="page-title"><?php echo htmlspecialchars($subject); ?></h1>
<div class="subject-subtitle">اختر القسم الذي تريد إدارته</div>

<?php if ($subject === 'الثقافة العامة' && supCan('general_culture', 'can_add')): ?>
<div class="add-section-wrap">
    <a href="add-general-section.php" class="add-section-btn">+ إضافة قسم جديد</a>
</div>
<?php endif; ?>

<div class="types-grid<?php echo $subject === 'الثقافة العامة' ? ' gc-grid' : ''; ?>">

<?php foreach ($types as $type):
    $media      = typeIconMedia($type);
    $customFile = $customTypeIcons[$type] ?? null;
    $customUrl  = $customFile ? '../' . $customFile : null;
    $customIsVid= $customFile && str_ends_with(strtolower($customFile), '.mp4');
    $hasCustom  = !empty($customFile);
    $typeKey    = htmlspecialchars($type);
    $subjectKey = htmlspecialchars($subject);
?>
<div style="position:relative;">

    <!-- زر تعديل الأيقونة -->
    <button class="icon-edit-btn" title="تغيير الأيقونة"
            onclick="openModal('<?php echo addslashes($type); ?>','<?php echo addslashes($subject); ?>')">✏️</button>

    <!-- زر حذف الأيقونة المخصصة — يظهر فقط إذا توجد أيقونة مخصصة -->
    <?php if ($hasCustom): ?>
    <button class="icon-delete-btn" title="حذف الأيقونة المخصصة"
            data-type="<?php echo $typeKey; ?>"
            data-subject="<?php echo $subjectKey; ?>"
            onclick="deleteIcon(this)">🗑</button>
    <?php endif; ?>

    <a href="subject-lessons.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
       class="type-card">

        <div class="type-icon" id="icon-wrap-<?php echo md5($type); ?>">
            <?php if ($hasCustom): ?>
                <?php if ($customIsVid): ?>
                <video autoplay muted loop playsinline>
                    <source src="<?php echo htmlspecialchars($customUrl); ?>" type="video/mp4">
                </video>
                <?php else: ?>
                <img src="<?php echo htmlspecialchars($customUrl); ?>" alt="">
                <?php endif; ?>

            <?php elseif ($media['type'] === 'video'): ?>
                <video autoplay muted loop playsinline
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <source src="<?php echo htmlspecialchars($media['src']); ?>" type="video/mp4">
                </video>
                <span class="icon-fallback" style="display:none;"><?php echo htmlspecialchars($media['fallback']); ?></span>
            <?php else: ?>
                <span class="icon-fallback"><?php echo htmlspecialchars($media['fallback']); ?></span>
            <?php endif; ?>
        </div>

        <h2><?php echo htmlspecialchars($type); ?></h2>
        <span class="card-sub">إدارة المحتوى</span>
    </a>
</div>
<?php endforeach; ?>

<?php foreach ($custom_sections as $cs):
    $cs_icon_file = $cs['icon_file'] ?? '';
    $cs_icon_url  = $cs_icon_file ? '../' . $cs_icon_file : '';
    $cs_is_vid    = $cs_icon_file && str_ends_with($cs_icon_file, '.mp4');
?>
<a href="manage-custom-section.php?id=<?php echo intval($cs['id']); ?>"
   class="type-card">
    <div class="type-icon">
        <?php if ($cs_icon_file && $cs_is_vid): ?>
        <video autoplay muted loop playsinline>
            <source src="<?php echo htmlspecialchars($cs_icon_url); ?>" type="video/mp4">
        </video>
        <?php elseif ($cs_icon_file): ?>
        <img src="<?php echo htmlspecialchars($cs_icon_url); ?>" alt="">
        <?php else: ?>
        <span class="icon-fallback"><?php echo htmlspecialchars($cs['icon']); ?></span>
        <?php endif; ?>
    </div>
    <h2><?php echo htmlspecialchars($cs['name']); ?></h2>
    <span class="card-sub">إدارة المحتوى</span>
</a>
<?php endforeach; ?>

</div>

<a href="<?php echo $isSupervisor ? 'supervisor-dashboard.php' : 'lessons.php'; ?>" class="back-link">رجوع</a>

</main>
</div>

<!-- ════════════════════════════════════
     Modal رفع الأيقونة
════════════════════════════════════ -->
<div class="modal-overlay" id="iconModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-title" id="modalTitle">تغيير أيقونة القسم</div>

        <!-- معاينة -->
        <div class="preview-wrap" id="previewWrap">
            <div id="previewContainer"></div>
            <div class="preview-name" id="previewName"></div>
        </div>

        <!-- منطقة الرفع -->
        <div class="drop-zone" id="dropZone">
            <input type="file" id="iconFileInput" accept="image/*,video/mp4,video/webm">
            <div class="drop-zone-icon">📁</div>
            <div class="drop-zone-text">اسحب وأفلت الملف هنا أو اضغط للاختيار</div>
            <div class="drop-zone-hint">صور: JPG، PNG، GIF، WebP &nbsp;|&nbsp; فيديو: MP4، WebM &nbsp;|&nbsp; الحد الأقصى: 50 MB</div>
        </div>

        <!-- شريط التقدم -->
        <div class="progress-bar-wrap" id="progressWrap">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
        </div>

        <button class="upload-submit-btn" id="uploadBtn" onclick="submitUpload()" disabled>رفع الأيقونة</button>
        <div class="upload-msg" id="uploadMsg"></div>
    </div>
</div>

<script>
(function(){
    /* ══ متغيرات Modal ══ */
    let currentType    = '';
    let currentSubject = '';
    let selectedFile   = null;

    /* ══ فتح Modal ══ */
    window.openModal = function(typeName, subjectName) {
        currentType    = typeName;
        currentSubject = subjectName;
        selectedFile   = null;

        document.getElementById('modalTitle').textContent = 'تغيير أيقونة: ' + typeName;
        document.getElementById('iconFileInput').value    = '';
        document.getElementById('previewWrap').style.display = 'none';
        document.getElementById('previewContainer').innerHTML = '';
        document.getElementById('previewName').textContent    = '';
        document.getElementById('uploadBtn').disabled  = true;
        document.getElementById('uploadMsg').textContent = '';
        document.getElementById('uploadMsg').className  = 'upload-msg';
        document.getElementById('progressWrap').style.display = 'none';
        document.getElementById('progressFill').style.width   = '0';

        document.getElementById('iconModal').classList.add('open');
    };

    /* ══ إغلاق Modal ══ */
    window.closeModal = function() {
        document.getElementById('iconModal').classList.remove('open');
    };
    document.getElementById('iconModal').addEventListener('click', function(e){
        if (e.target === this) closeModal();
    });

    /* ══ اختيار الملف ══ */
    const fileInput = document.getElementById('iconFileInput');
    const dropZone  = document.getElementById('dropZone');

    fileInput.addEventListener('change', function(){
        if (this.files[0]) handleFile(this.files[0]);
    });
    dropZone.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('dragover'); });
    dropZone.addEventListener('dragleave',function(){ this.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e){
        e.preventDefault(); this.classList.remove('dragover');
        if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
    });

    function handleFile(file) {
        const allowed = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm'];
        if (!allowed.includes(file.type)) {
            showMsg('نوع الملف غير مسموح به', false); return;
        }
        if (file.size > 50 * 1024 * 1024) {
            showMsg('حجم الملف يتجاوز 50 MB', false); return;
        }

        selectedFile = file;
        document.getElementById('uploadBtn').disabled = false;
        document.getElementById('uploadMsg').textContent = '';

        // معاينة
        const container = document.getElementById('previewContainer');
        container.innerHTML = '';
        const url = URL.createObjectURL(file);
        if (file.type.startsWith('video/')) {
            const v = document.createElement('video');
            v.src = url; v.autoplay = true; v.muted = true; v.loop = true;
            v.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:50%;box-shadow:0 6px 18px rgba(0,0,0,.12);border:3px solid #e2edf7;';
            container.appendChild(v);
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:50%;box-shadow:0 6px 18px rgba(0,0,0,.12);border:3px solid #e2edf7;';
            container.appendChild(img);
        }
        document.getElementById('previewName').textContent = file.name;
        document.getElementById('previewWrap').style.display = 'block';
    }

    /* ══ رفع الملف ══ */
    window.submitUpload = function() {
        if (!selectedFile) return;

        const btn = document.getElementById('uploadBtn');
        btn.disabled = true;
        document.getElementById('progressWrap').style.display = 'block';
        document.getElementById('progressFill').style.width   = '0';
        document.getElementById('uploadMsg').textContent = '';

        const fd = new FormData();
        fd.append('action',      'upload_type_icon');
        fd.append('type_name',   currentType);
        fd.append('subject_val', currentSubject);
        fd.append('icon_file',   selectedFile);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname + '?subject=' + encodeURIComponent(currentSubject));

        xhr.upload.addEventListener('progress', function(e){
            if (e.lengthComputable) {
                const pct = Math.round(e.loaded / e.total * 100);
                document.getElementById('progressFill').style.width = pct + '%';
            }
        });

        xhr.addEventListener('load', function(){
    btn.disabled = false;
    let res;
    try {
        res = JSON.parse(xhr.responseText);
    } catch(e) {
        console.log(xhr.responseText);
        alert(xhr.responseText);
        showMsg('خطأ في الخادم', false);
        return;
    }
            if (res.ok) {
                showMsg('✅ تم رفع الأيقونة بنجاح', true);
                updateCardIcon(currentType, res.path, res.is_video);
                setTimeout(closeModal, 1200);
            } else {
                showMsg('❌ ' + (res.msg || 'فشل الرفع'), false);
            }
        });

        xhr.addEventListener('error', function(){ btn.disabled = false; showMsg('فشل الاتصال بالخادم', false); });
        xhr.send(fd);
    };

    /* ══ تحديث الأيقونة في البطاقة مباشرة بدون reload ══ */
    function updateCardIcon(typeName, filePath, isVideo) {
        // نبحث عن الـ wrapper بالـ data-type
        const wrap = document.querySelector('.type-icon[data-type="' + typeName + '"]');
        if (!wrap) { location.reload(); return; }
        wrap.innerHTML = '';
        if (isVideo) {
            const v = document.createElement('video');
            v.autoplay = true; v.muted = true; v.loop = true; v.playsInline = true;
            v.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;';
            const s = document.createElement('source');
            s.src = filePath; s.type = 'video/mp4';
            v.appendChild(s); wrap.appendChild(v);
        } else {
            const img = document.createElement('img');
            img.src = filePath; img.alt = '';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;';
            wrap.appendChild(img);
        }
        // أضف زر الحذف إذا مش موجود
const card = wrap.closest('div[style*="position:relative"]');
if (card && !card.querySelector('.icon-delete-btn')) {
    const delBtn = document.createElement('button');
    delBtn.className       = 'icon-delete-btn';
    delBtn.title           = 'حذف الأيقونة المخصصة';
    delBtn.dataset.type    = typeName;
    delBtn.dataset.subject = currentSubject;
    delBtn.textContent     = '🗑';
    delBtn.onclick = function(){ deleteIcon(this); };
    const editBtn = card.querySelector('.icon-edit-btn');
    editBtn.insertAdjacentElement('afterend', delBtn);
}
    }

    /* ══ حذف الأيقونة المخصصة ══ */
    window.deleteIcon = function(btn) {
        if (!confirm('هل تريد حذف الأيقونة المخصصة والعودة للأيقونة الافتراضية؟')) return;

        const typeName   = btn.dataset.type;
        const subjectVal = btn.dataset.subject;

        const fd = new FormData();
        fd.append('action',      'delete_type_icon');
        fd.append('type_name',   typeName);
        fd.append('subject_val', subjectVal);

        fetch(window.location.pathname + '?subject=' + encodeURIComponent(subjectVal), { method:'POST', body:fd })
            .then(r => r.json())
            .then(res => {
                if (res.ok) location.reload();
            });
    };

    /* ══ رسائل ══ */
    function showMsg(text, ok) {
        const el = document.getElementById('uploadMsg');
        el.textContent = text;
        el.className   = 'upload-msg ' + (ok ? 'ok' : 'err');
    }

})();
</script>

<?php
// نضيف data-type على كل .type-icon عبر PHP مباشرة
?>
<script>
(function(){
    <?php foreach ($types as $t): ?>
    (function(){
        const el = document.getElementById('icon-wrap-<?php echo md5($t); ?>');
        if (el) el.dataset.type = <?php echo json_encode($t, JSON_UNESCAPED_UNICODE); ?>;
    })();
    <?php endforeach; ?>
})();
</script>

</body>
</html>
