<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

/* ---- إنشاء الجداول ---- */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS general_sections (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    icon       VARCHAR(20)  DEFAULT '★',
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS general_section_cards (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    section_id       INT          NOT NULL,
    display_name     VARCHAR(255) NOT NULL,
    description_text TEXT,
    image            VARCHAR(255),
    icon_file        VARCHAR(255),
    sign_video       VARCHAR(255),
    sort_order       INT          DEFAULT 0,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/* إضافة عمود sign_video إن لم يكن موجوداً */
mysqli_query($conn, "ALTER TABLE general_section_cards ADD COLUMN IF NOT EXISTS sign_video VARCHAR(255) DEFAULT NULL");

/* إضافة عمود section_video إن لم يكن موجوداً */
mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS section_video VARCHAR(255) DEFAULT NULL");

/* إضافة عمود description إن لم يكن موجوداً */
mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");

/* إضافة أعمدة الفيديو التعريفي */
mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS intro_title VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS intro_video VARCHAR(255) DEFAULT NULL");

$message = '';
$error   = '';

/* ---- معالجة الحفظ ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $section_name = trim($_POST['section_name'] ?? '');

    if ($section_name === '') {
        $error = 'يرجى إدخال اسم القسم.';
    } else {

        $section_desc  = trim($_POST['section_description'] ?? '');
        $intro_title   = trim($_POST['intro_title'] ?? '');

        /* 1) حفظ القسم */
        $stmt = mysqli_prepare($conn, "INSERT INTO general_sections (name, icon, description, intro_title) VALUES (?, '', ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $section_name, $section_desc, $intro_title);
        mysqli_stmt_execute($stmt);
        $section_id = mysqli_insert_id($conn);

        /* 2) إنشاء المجلدات */
        $base_dir = __DIR__ . "/../images/general/custom/{$section_id}/";
        if (!is_dir($base_dir . 'section')) mkdir($base_dir . 'section', 0755, true);
        foreach (['icons', 'images', 'videos', 'section'] as $sub) {
            if (!is_dir($base_dir . $sub)) mkdir($base_dir . $sub, 0755, true);
        }

        $allowed_video = ['video/mp4', 'video/webm'];
        $allowed_image = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];

        /* 2b) رفع صورة/فيديو البطاقة الخارجية → icon_file */
        $icon_file_val = '';
        if (isset($_FILES['section_icon']['error']) && $_FILES['section_icon']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['section_icon']['tmp_name'];
            $mime = mime_content_type($tmp);
            $dest = null;
            if (in_array($mime, $allowed_video)) {
                $dest = $base_dir . "section/icon.mp4";
                $icon_file_val = "images/general/custom/{$section_id}/section/icon.mp4";
            } elseif (in_array($mime, $allowed_image)) {
                $ext  = pathinfo($_FILES['section_icon']['name'], PATHINFO_EXTENSION) ?: 'png';
                $dest = $base_dir . "section/icon.{$ext}";
                $icon_file_val = "images/general/custom/{$section_id}/section/icon.{$ext}";
            }
            if ($dest && !move_uploaded_file($tmp, $dest)) $icon_file_val = '';
        }

        /* 2c) رفع فيديو إشارة البطاقة الخارجية → sign_video */
        $sign_video_val = '';
        if (isset($_FILES['section_sign']['error']) && $_FILES['section_sign']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['section_sign']['tmp_name'];
            $mime = mime_content_type($tmp);
            if (in_array($mime, $allowed_video)) {
                $dest = $base_dir . "section/sign.mp4";
                if (move_uploaded_file($tmp, $dest))
                    $sign_video_val = "images/general/custom/{$section_id}/section/sign.mp4";
            }
        }

        mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS icon_file VARCHAR(255) DEFAULT NULL");
        mysqli_query($conn, "ALTER TABLE general_sections ADD COLUMN IF NOT EXISTS sign_video VARCHAR(255) DEFAULT NULL");
        if ($icon_file_val || $sign_video_val) {
            $sv = mysqli_prepare($conn, "UPDATE general_sections SET icon_file=?, sign_video=? WHERE id=?");
            mysqli_stmt_bind_param($sv, "ssi", $icon_file_val, $sign_video_val, $section_id);
            mysqli_stmt_execute($sv);
        }

        /* رفع الفيديو التعريفي */
        $intro_video_val = '';
        if (isset($_FILES['intro_video']['error']) && $_FILES['intro_video']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['intro_video']['tmp_name'];
            $mime = mime_content_type($tmp);
            if (in_array($mime, $allowed_video)) {
                $intro_dir = $base_dir . 'intro/';
                if (!is_dir($intro_dir)) mkdir($intro_dir, 0755, true);
                $dest = $intro_dir . 'intro.mp4';
                if (move_uploaded_file($tmp, $dest))
                    $intro_video_val = "images/general/custom/{$section_id}/intro/intro.mp4";
            }
        }

        if ($intro_video_val) {
            $iv = mysqli_prepare($conn, "UPDATE general_sections SET intro_video=? WHERE id=?");
            mysqli_stmt_bind_param($iv, "si", $intro_video_val, $section_id);
            mysqli_stmt_execute($iv);
        }

        /* 3) معالجة البطاقات */
        $card_names = $_POST['card_name'] ?? [];
        $card_descs = $_POST['card_desc'] ?? [];

        foreach ($card_names as $i => $raw) {
            $name = trim($raw);
            if ($name === '') continue;

            $desc       = trim($card_descs[$i] ?? '');
            $icon_file  = '';
            $sign_video = '';
            $img_file   = '';
            $sort       = $i + 1;

            /* --- رفع الأيقونة (صورة أو فيديو) --- */
            if (isset($_FILES['card_icon']['error'][$i]) && $_FILES['card_icon']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp  = $_FILES['card_icon']['tmp_name'][$i];
                $mime = mime_content_type($tmp);
                if (in_array($mime, $allowed_video)) {
                    $fname = "icon_{$sort}.mp4";
                    if (move_uploaded_file($tmp, $base_dir . "icons/{$fname}"))
                        $icon_file = "images/general/custom/{$section_id}/icons/{$fname}";
                } elseif (in_array($mime, $allowed_image)) {
                    $fname = "icon_{$sort}.png";
                    if (move_uploaded_file($tmp, $base_dir . "icons/{$fname}"))
                        $icon_file = "images/general/custom/{$section_id}/icons/{$fname}";
                }
            }

            /* --- رفع فيديو لغة الإشارة --- */
            if (isset($_FILES['card_sign_video']['error'][$i]) && $_FILES['card_sign_video']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp  = $_FILES['card_sign_video']['tmp_name'][$i];
                $mime = mime_content_type($tmp);
                if (in_array($mime, $allowed_video)) {
                    $fname = "sign_{$sort}.mp4";
                    if (move_uploaded_file($tmp, $base_dir . "videos/{$fname}"))
                        $sign_video = "images/general/custom/{$section_id}/videos/{$fname}";
                }
            }

            /* --- رفع الصورة الرئيسية --- */
            if (isset($_FILES['card_image']['error'][$i]) && $_FILES['card_image']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp  = $_FILES['card_image']['tmp_name'][$i];
                $mime = mime_content_type($tmp);
                if (in_array($mime, $allowed_image)) {
                    $fname = "image_{$sort}.png";
                    if (move_uploaded_file($tmp, $base_dir . "images/{$fname}"))
                        $img_file = "images/general/custom/{$section_id}/images/{$fname}";
                }
            }

            /* حفظ البطاقة */
            $cs = mysqli_prepare($conn,
                "INSERT INTO general_section_cards
                 (section_id, display_name, description_text, image, icon_file, sign_video, sort_order)
                 VALUES (?,?,?,?,?,?,?)"
            );
            mysqli_stmt_bind_param($cs, "isssssi",
                $section_id, $name, $desc, $img_file, $icon_file, $sign_video, $sort
            );
            mysqli_stmt_execute($cs);
        }

        $message = "✓ تم إنشاء القسم «{$section_name}» بنجاح";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إضافة قسم جديد</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f8fc}

.page-title{text-align:center;color:#21425f;font-size:32px;font-weight:900;margin-bottom:8px}
.page-sub{text-align:center;color:#6a849a;font-size:16px;margin-bottom:28px}

.alert{max-width:960px;margin:0 auto 20px;padding:14px 20px;border-radius:14px;font-size:16px;font-weight:700}
.alert-success{background:#d4f5e4;color:#1a7a45;border:1px solid #a8e6c2}
.alert-error{background:#fde8e8;color:#c0392b;border:1px solid #f5b7b1}

/* ---- بطاقة معلومات القسم ---- */
.intro-card{
    max-width:960px;margin:0 auto 36px;
    background:#fff;border-radius:22px;padding:26px 28px;
    box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7;
}
.intro-card h2{margin:0 0 18px;color:#21425f;font-size:22px;font-weight:900}
.fields-row{display:grid;grid-template-columns:1fr 220px;gap:16px}
.section-video-preview{
    width:100%;border-radius:10px;
    overflow:hidden;background:#e8eef5;margin-bottom:8px;
    display:none;
}
.field-group{display:flex;flex-direction:column;gap:6px}
.field-group label{font-size:13px;font-weight:700;color:#21425f}
.field-group input,
.field-group textarea{
    padding:10px 14px;border:1px solid #cde0ef;border-radius:10px;
    font-size:15px;font-family:inherit;color:#21425f;background:#fff;resize:vertical;
}
.field-group input:focus,
.field-group textarea:focus{outline:none;border-color:#5498d4;box-shadow:0 0 0 3px rgba(84,152,212,.15)}

/* ---- شبكة البطاقات ---- */
.items-grid{max-width:960px;margin:0 auto;display:grid;gap:24px}
.section-grid-title{
    max-width:960px;margin:0 auto 16px;
    font-size:20px;font-weight:900;color:#21425f;
    padding-bottom:10px;border-bottom:2px solid #e2edf7;
    display:flex;align-items:center;gap:10px;
}

/* ---- بطاقة واحدة ---- */
.item-card{
    background:#fff;border-radius:22px;padding:24px;
    box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7;
}
.item-header{
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #edf4fb;
}
.item-title{font-size:18px;font-weight:900;color:#21425f}
.remove-btn{
    padding:7px 16px;border-radius:10px;background:#fde8e8;
    color:#c0392b;font-size:14px;font-weight:700;border:none;cursor:pointer;transition:.2s;
}
.remove-btn:hover{background:#d93025;color:#fff}

/* ---- 3 مربعات وسائط ---- */
.media-row{
    display:grid;grid-template-columns:repeat(3,1fr);
    gap:16px;margin-bottom:20px;
}
.media-block{border:1px solid #e2edf7;border-radius:14px;padding:14px;background:#f8fbff}
.media-block h4{margin:0 0 10px;font-size:14px;font-weight:900;color:#21425f}
.media-preview{
    width:100%;height:110px;border-radius:10px;
    overflow:hidden;background:#e8eef5;margin-bottom:10px;
    display:flex;align-items:center;justify-content:center;
    color:#8ab0c8;font-size:28px;
}
.file-input{
    width:100%;padding:8px 12px;
    border:2px dashed #b0c8e0;border-radius:10px;
    font-size:13px;cursor:pointer;background:#fff;
}
.media-hint{font-size:11px;color:#8ab0c8;margin-top:6px}

/* ---- نصوص البطاقة ---- */
.text-section{border-top:1px solid #e2edf7;padding-top:16px}
.text-section h4{margin:0 0 12px;font-size:15px;font-weight:900;color:#21425f}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* ---- أزرار ---- */
.actions-row{
    max-width:960px;margin:24px auto 0;
    display:flex;gap:14px;flex-wrap:wrap;
}
.add-card-btn{
    flex:1;padding:15px;border-radius:16px;
    background:#e8f3ff;color:#21425f;
    font-size:16px;font-weight:900;border:2px dashed #b0cceb;
    cursor:pointer;transition:.2s;text-align:center;
}
.add-card-btn:hover{background:#d4eaff;border-color:#7aacdb}
.submit-btn{
    flex:1;padding:15px;border-radius:16px;
    background:#28b978;color:#fff;
    font-size:16px;font-weight:900;border:none;
    cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(40,185,120,.22);
}
.submit-btn:hover{background:#22a86c;transform:translateY(-2px)}

.back-link{
    display:block;width:max-content;margin:36px auto 0;
    padding:14px 36px;border-radius:18px;background:#21425f;color:#fff;
    text-decoration:none;font-size:17px;font-weight:900;
    box-shadow:0 8px 20px rgba(33,66,95,.16);transition:.25s;
}
.back-link:hover{transform:translateY(-3px)}

@media(max-width:800px){
    .fields-row{grid-template-columns:1fr}
    .media-row{grid-template-columns:1fr}
    .field-row{grid-template-columns:1fr}
    .actions-row{flex-direction:column}
}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">
<h1 class="page-title">➕ إضافة قسم جديد</h1>
<p class="page-sub">أنشئ قسماً جديداً وأضف له بطاقات بقدر ما تريد</p>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="mainForm">

    <!-- معلومات القسم -->
    <div class="intro-card">
        <h2>📁 معلومات القسم</h2>
        <div class="fields-row" style="grid-template-columns:1fr 1fr 200px 200px;">
            <div class="field-group">
                <label>اسم القسم *</label>
                <input type="text" name="section_name" placeholder="مثال: أوقات اليوم" required>
            </div>
            <div class="field-group">
                <label>📝 وصف القسم</label>
                <textarea name="section_description" rows="3" placeholder="اكتب وصفاً مختصراً يظهر للطفل عند فتح القسم..."></textarea>
            </div>
            <div class="field-group">
                <label>🖼 أيقونة القسم الخارجية</label>
                <div class="section-video-preview" id="section-icon-preview"></div>
                <input type="file" name="section_icon" class="file-input"
                       accept="video/mp4,video/webm,image/*"
                       onchange="previewSectionFile(this,'section-icon-preview')">
                <p class="media-hint" style="font-size:11px;color:#8ab0c8;margin-top:4px">صورة أو فيديو MP4</p>
            </div>
            <div class="field-group">
                <label>🤟 فيديو القسم الخارجي (بالإشارة)</label>
                <div class="section-video-preview" id="section-sign-preview"></div>
                <input type="file" name="section_sign" class="file-input"
                       accept="video/mp4"
                       onchange="previewSectionFile(this,'section-sign-preview')">
                <p class="media-hint" style="font-size:11px;color:#8ab0c8;margin-top:4px">فيديو MP4 فقط</p>
            </div>
        </div>
    </div>

    <!-- الفيديو التعريفي -->
    <div class="intro-card" style="max-width:960px;margin:0 auto 28px;background:#fff;border-radius:22px;padding:24px 28px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7;">
        <h2 style="margin:0 0 16px;color:#21425f;font-size:20px;font-weight:900">🎬 الفيديو التعريفي للقسم</h2>
        <div style="display:grid;grid-template-columns:1fr 260px;gap:18px;align-items:start">
            <div class="field-group">
                <label>✏ النص الذي يظهر فوق الفيديو</label>
                <input type="text" name="intro_title" placeholder="مثال: هيا نتعرف على أوقات اليوم">
                <p style="font-size:12px;color:#8ab0c8;margin:4px 0 0">يظهر هذا النص بالخط الكبير أعلى الفيديو في صفحة الطفل</p>
            </div>
            <div class="field-group">
                <label>📹 رفع الفيديو التعريفي</label>
                <div id="intro-video-preview" style="display:none;border-radius:10px;overflow:hidden;margin-bottom:8px;"></div>
                <input type="file" name="intro_video" class="file-input" accept="video/mp4,video/webm"
                       onchange="previewSectionFile(this,'intro-video-preview')">
                <p style="font-size:11px;color:#8ab0c8;margin-top:4px">فيديو MP4 فقط</p>
            </div>
        </div>
    </div>

    <!-- البطاقات -->
    <div class="section-grid-title">🃏 البطاقات</div>

    <div class="items-grid" id="cards-container">

        <!-- بطاقة أولى افتراضية -->
        <div class="item-card" data-index="0">
            <div class="item-header">
                <span class="item-title">بطاقة 1</span>
                <button type="button" class="remove-btn" onclick="removeCard(this)">✕ حذف</button>
            </div>

            <!-- وسائط ثلاثة -->
            <div class="media-row">
                <div class="media-block">
                    <h4>🎬 فيديو الأيقونة</h4>
                    <div class="media-preview">🎬</div>
                    <input type="file" name="card_icon[]" class="file-input"
                           accept="image/*,video/mp4"
                           onchange="previewFile(this)">
                    <p class="media-hint">صورة أو فيديو MP4</p>
                </div>
                <div class="media-block">
                    <h4>🤟 فيديو لغة الإشارة</h4>
                    <div class="media-preview">🤟</div>
                    <input type="file" name="card_sign_video[]" class="file-input"
                           accept="video/mp4"
                           onchange="previewFile(this)">
                    <p class="media-hint">فيديو MP4 فقط</p>
                </div>
                <div class="media-block">
                    <h4>🖼 الصورة الرئيسية</h4>
                    <div class="media-preview">🖼</div>
                    <input type="file" name="card_image[]" class="file-input"
                           accept="image/*"
                           onchange="previewFile(this)">
                    <p class="media-hint">تظهر عند النقر على البطاقة</p>
                </div>
            </div>

            <!-- النصوص -->
            <div class="text-section">
                <h4>✏ نصوص البطاقة</h4>
                <div class="field-row">
                    <div class="field-group">
                        <label>اسم البطاقة</label>
                        <input type="text" name="card_name[]" placeholder="مثال: الصباح">
                    </div>
                    <div class="field-group">
                        <label>النص الوصفي</label>
                        <textarea name="card_desc[]" rows="3" placeholder="اكتب وصفاً مختصراً..."></textarea>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /cards-container -->

    <!-- أزرار -->
    <div class="actions-row">
        <button type="button" class="add-card-btn" onclick="addCard()">
            + إضافة بطاقة جديدة
        </button>
        <button type="submit" class="submit-btn">
            💾 حفظ القسم والبطاقات
        </button>
    </div>

</form>

<a href="lesson-types.php?subject=الثقافة العامة" class="back-link">رجوع</a>
</main>
</div>

<script>
let cardCount = 1;

function cardTemplate(num) {
    return `
    <div class="item-card" data-index="${num}">
        <div class="item-header">
            <span class="item-title">بطاقة ${num + 1}</span>
            <button type="button" class="remove-btn" onclick="removeCard(this)">✕ حذف</button>
        </div>
        <div class="media-row">
            <div class="media-block">
                <h4>🎬 فيديو الأيقونة</h4>
                <div class="media-preview">🎬</div>
                <input type="file" name="card_icon[]" class="file-input"
                       accept="image/*,video/mp4" onchange="previewFile(this)">
                <p class="media-hint">صورة أو فيديو MP4</p>
            </div>
            <div class="media-block">
                <h4>🤟 فيديو لغة الإشارة</h4>
                <div class="media-preview">🤟</div>
                <input type="file" name="card_sign_video[]" class="file-input"
                       accept="video/mp4" onchange="previewFile(this)">
                <p class="media-hint">فيديو MP4 فقط</p>
            </div>
            <div class="media-block">
                <h4>🖼 الصورة الرئيسية</h4>
                <div class="media-preview">🖼</div>
                <input type="file" name="card_image[]" class="file-input"
                       accept="image/*" onchange="previewFile(this)">
                <p class="media-hint">تظهر عند النقر على البطاقة</p>
            </div>
        </div>
        <div class="text-section">
            <h4>✏ نصوص البطاقة</h4>
            <div class="field-row">
                <div class="field-group">
                    <label>اسم العرض</label>
                    <input type="text" name="card_name[]" placeholder="مثال: المساء">
                </div>
                <div class="field-group">
                    <label>النص الوصفي</label>
                    <textarea name="card_desc[]" rows="3" placeholder="اكتب وصفاً مختصراً..."></textarea>
                </div>
            </div>
        </div>
    </div>`;
}

function addCard() {
    const container = document.getElementById('cards-container');
    const div = document.createElement('div');
    div.innerHTML = cardTemplate(cardCount);
    container.appendChild(div.firstElementChild);
    cardCount++;
}

function removeCard(btn) {
    const blocks = document.querySelectorAll('.item-card');
    if (blocks.length <= 1) {
        alert('يجب أن يكون هناك بطاقة واحدة على الأقل');
        return;
    }
    btn.closest('.item-card').remove();
    /* إعادة ترقيم */
    document.querySelectorAll('.item-card .item-title').forEach((t, i) => {
        t.textContent = `بطاقة ${i + 1}`;
    });
}

/* معاينة صورة/فيديو القسم الخارجي */
function previewSectionFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    if (!file) { preview.style.display='none'; return; }
    const url = URL.createObjectURL(file);
    if (file.type.startsWith('video/')) {
        preview.innerHTML = `<video src="${url}" autoplay muted loop playsinline
            style="width:100%;height:100px;object-fit:cover;border-radius:8px"></video>`;
    } else {
        preview.innerHTML = `<img src="${url}"
            style="width:100%;height:100px;object-fit:cover;border-radius:8px">`;
    }
    preview.style.display = 'block';
}

/* معاينة الملف المرفوع */
function previewFile(input) {
    const preview = input.previousElementSibling;
    if (!preview || !preview.classList.contains('media-preview')) return;
    const file = input.files[0];
    if (!file) return;

    const url = URL.createObjectURL(file);
    if (file.type.startsWith('video/')) {
        preview.innerHTML = `<video src="${url}" autoplay muted loop playsinline
            style="width:100%;height:100%;object-fit:cover;border-radius:8px"></video>`;
    } else {
        preview.innerHTML = `<img src="${url}"
            style="width:100%;height:100%;object-fit:cover;border-radius:8px">`;
    }
}
</script>
</body>
</html>
