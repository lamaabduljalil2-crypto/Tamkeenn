<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

mysqli_set_charset($conn, 'utf8mb4');

/* ---- إنشاء الجداول ---- */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS general_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(20) DEFAULT '★',
    icon_file VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    sort_order       INT DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$existing_cols = [];
$res = mysqli_query($conn, "SHOW COLUMNS FROM general_sections");
while ($row = mysqli_fetch_assoc($res)) $existing_cols[] = $row['Field'];

$cols_to_add = [
    'icon_file'   => "ALTER TABLE general_sections ADD COLUMN icon_file VARCHAR(255) DEFAULT NULL",
    'sign_video'  => "ALTER TABLE general_sections ADD COLUMN sign_video VARCHAR(255) DEFAULT NULL",
    'description' => "ALTER TABLE general_sections ADD COLUMN description TEXT DEFAULT NULL",
    'intro_title' => "ALTER TABLE general_sections ADD COLUMN intro_title VARCHAR(255) DEFAULT NULL",
    'intro_video' => "ALTER TABLE general_sections ADD COLUMN intro_video VARCHAR(255) DEFAULT NULL",
];
foreach ($cols_to_add as $col => $sql) {
    if (!in_array($col, $existing_cols)) mysqli_query($conn, $sql);
}

$cards_cols = [];
$res2 = mysqli_query($conn, "SHOW COLUMNS FROM general_section_cards");
while ($row = mysqli_fetch_assoc($res2)) $cards_cols[] = $row['Field'];
if (!in_array('sign_video', $cards_cols))
    mysqli_query($conn, "ALTER TABLE general_section_cards ADD COLUMN sign_video VARCHAR(255) DEFAULT NULL");
if (!in_array('created_by_supervisor', $cards_cols))
    mysqli_query($conn, "ALTER TABLE general_section_cards ADD COLUMN created_by_supervisor INT DEFAULT 0");

$section_id = intval($_GET['id'] ?? 0);
if ($section_id <= 0) { header('Location: lesson-types.php?subject=الثقافة العامة'); exit; }
if ($isSupervisor && !supCan('gc_custom_' . $section_id, 'can_view')) { header('Location: supervisor-dashboard.php'); exit; }

/* ---- حذف القسم ---- */
if (isset($_GET['delete_section']) && $_GET['delete_section'] == 1) {
    $qr = mysqli_prepare($conn, "SELECT icon_file, image, sign_video FROM general_section_cards WHERE section_id=?");
    mysqli_stmt_bind_param($qr, 'i', $section_id); mysqli_stmt_execute($qr);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($qr), MYSQLI_ASSOC);
    foreach ($rows as $r) {
        foreach (['icon_file','image','sign_video'] as $f) {
            if (!empty($r[$f])) { $fp = __DIR__ . '/../' . $r[$f]; if (file_exists($fp)) @unlink($fp); }
        }
    }
    $sq = mysqli_prepare($conn, "SELECT icon_file FROM general_sections WHERE id=?");
    mysqli_stmt_bind_param($sq,'i',$section_id); mysqli_stmt_execute($sq);
    $sr = mysqli_fetch_assoc(mysqli_stmt_get_result($sq));
    if ($sr && !empty($sr['icon_file'])) { $fp = __DIR__.'/../'.$sr['icon_file']; if(file_exists($fp)) @unlink($fp); }
    $sd = __DIR__ . "/../images/general/custom/{$section_id}/";
    foreach (['icons','images','videos','section','intro'] as $sub) { if (is_dir($sd.$sub)) @rmdir($sd.$sub); }
    if (is_dir($sd)) @rmdir($sd);
    $d1 = mysqli_prepare($conn,"DELETE FROM general_section_cards WHERE section_id=?");
    mysqli_stmt_bind_param($d1,'i',$section_id); mysqli_stmt_execute($d1);
    $d2 = mysqli_prepare($conn,"DELETE FROM general_sections WHERE id=?");
    mysqli_stmt_bind_param($d2,'i',$section_id); mysqli_stmt_execute($d2);
    header('Location: lesson-types.php?subject=الثقافة العامة'); exit;
}

$s = mysqli_prepare($conn, "SELECT * FROM general_sections WHERE id = ?");
mysqli_stmt_bind_param($s, 'i', $section_id); mysqli_stmt_execute($s);
$section = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if (!$section) { header('Location: lesson-types.php?subject=الثقافة العامة'); exit; }

$base_dir = __DIR__ . "/../images/general/custom/{$section_id}/";
$base_url = "../images/general/custom/{$section_id}/";
$message = '';
$error   = '';
$allowed_video = ['video/mp4','video/webm'];
$allowed_image = ['image/png','image/jpeg','image/jpg','image/gif','image/webp'];
$_gcCustomKey = 'gc_custom_' . $section_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- حفظ كل النصوص دفعة واحدة (القسم + البطاقات) ---- */
    if ($action === 'save_all_text') {
        // نصوص القسم
        $new_name = trim($_POST['section_name'] ?? '');
        $new_desc = trim($_POST['section_description'] ?? '');
        $new_intro_t = trim($_POST['intro_title'] ?? '');
        if ($new_name !== '') {
            $u = mysqli_prepare($conn,"UPDATE general_sections SET name=?, description=?, intro_title=? WHERE id=?");
            mysqli_stmt_bind_param($u,'sssi',$new_name,$new_desc,$new_intro_t,$section_id);
            mysqli_stmt_execute($u);
            $section['name'] = $new_name;
            $section['description'] = $new_desc;
            $section['intro_title'] = $new_intro_t;
        }
        // نصوص البطاقات
        $names = $_POST['display_name'] ?? [];
        $descs = $_POST['description_text'] ?? [];
        foreach ($names as $card_id => $name) {
            $card_id = intval($card_id);
            $name    = trim($name);
            $desc    = trim($descs[$card_id] ?? '');
            if ($card_id > 0 && $name !== '') {
                $u = mysqli_prepare($conn,"UPDATE general_section_cards SET display_name=?,description_text=? WHERE id=? AND section_id=?");
                mysqli_stmt_bind_param($u,'ssii',$name,$desc,$card_id,$section_id);
                mysqli_stmt_execute($u);
            }
        }
        $message = 'تم حفظ جميع التعديلات ✓';
    }

    /* ---- رفع ملفات القسم (AJAX) ---- */
    if ($action === 'upload_section_icon') {
        if (isset($_FILES['section_icon_file']) && $_FILES['section_icon_file']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['section_icon_file']['tmp_name'];
            $mime = mime_content_type($tmp);
            $sec_dir = $base_dir . 'section/';
            if (!is_dir($sec_dir)) mkdir($sec_dir, 0755, true);
            $dest = null; $new_path = '';
            if (in_array($mime, $allowed_video)) { $dest = $sec_dir.'icon.mp4'; $new_path = "images/general/custom/{$section_id}/section/icon.mp4"; }
            elseif (in_array($mime, $allowed_image)) { $ext = in_array($mime,['image/jpeg','image/jpg'])?'jpg':'png'; $dest=$sec_dir."icon.{$ext}"; $new_path="images/general/custom/{$section_id}/section/icon.{$ext}"; }
            if ($dest && move_uploaded_file($tmp, $dest)) {
                if (!empty($section['icon_file'])) { $old=__DIR__.'/../'.$section['icon_file']; if(file_exists($old)&&realpath($old)!==realpath($dest)) @unlink($old); }
                $u2=mysqli_prepare($conn,"UPDATE general_sections SET icon_file=? WHERE id=?");
                mysqli_stmt_bind_param($u2,'si',$new_path,$section_id); mysqli_stmt_execute($u2);
                $section['icon_file']=$new_path; $message='تم الرفع ✓';
            } else { $error='فشل رفع الأيقونة.'; }
        } else { $error='لم يتم اختيار ملف.'; }
    }

    if ($action === 'upload_section_sign') {
        if (!isset($_FILES['section_sign_file'])) { $error='لم يتم إرسال الملف.'; }
        elseif ($_FILES['section_sign_file']['error'] !== UPLOAD_ERR_OK) { $error='خطأ في رفع الملف رقم '.$_FILES['section_sign_file']['error']; }
        else {
            $tmp=$_FILES['section_sign_file']['tmp_name'];
            $ext=strtolower(pathinfo($_FILES['section_sign_file']['name'],PATHINFO_EXTENSION));
            if ($ext!=='mp4') { $error='يسمح فقط بملفات mp4'; }
            else {
                $sign_dir=$base_dir.'section/'; if(!is_dir($sign_dir)) mkdir($sign_dir,0755,true);
                $dest=$sign_dir.'sign.mp4'; $new_path="images/general/custom/{$section_id}/section/sign.mp4";
                if (move_uploaded_file($tmp,$dest)) {
                    $u3=mysqli_prepare($conn,"UPDATE general_sections SET sign_video=? WHERE id=?");
                    mysqli_stmt_bind_param($u3,'si',$new_path,$section_id); mysqli_stmt_execute($u3);
                    $section['sign_video']=$new_path; $message='تم الرفع ✓';
                } else { $error='فشل نقل الملف.'; }
            }
        }
    }

    if ($action === 'upload_intro_video') {
        if (isset($_FILES['intro_video_file']) && $_FILES['intro_video_file']['error'] === UPLOAD_ERR_OK) {
            $tmp=$_FILES['intro_video_file']['tmp_name']; $mime=mime_content_type($tmp);
            if (in_array($mime,$allowed_video)) {
                $intro_dir=$base_dir.'intro/'; if(!is_dir($intro_dir)) mkdir($intro_dir,0755,true);
                $dest=$intro_dir.'intro.mp4'; $new_path="images/general/custom/{$section_id}/intro/intro.mp4";
                if (move_uploaded_file($tmp,$dest)) {
                    $u2=mysqli_prepare($conn,"UPDATE general_sections SET intro_video=? WHERE id=?");
                    mysqli_stmt_bind_param($u2,'si',$new_path,$section_id); mysqli_stmt_execute($u2);
                    $section['intro_video']=$new_path; $message='تم الرفع ✓';
                } else { $error='فشل رفع الفيديو.'; }
            } else { $error='يُقبل فيديو MP4 فقط.'; }
        } else { $error='لم يتم اختيار ملف.'; }
    }

    /* ---- رفع ملف بطاقة (AJAX) ---- */
    if ($action === 'upload_card_file') {
        $card_id   = intval($_POST['card_id']   ?? 0);
        $file_type = $_POST['file_type'] ?? '';
        $qr=mysqli_prepare($conn,"SELECT sort_order FROM general_section_cards WHERE id=? AND section_id=?");
        mysqli_stmt_bind_param($qr,'ii',$card_id,$section_id); mysqli_stmt_execute($qr);
        $crow=mysqli_fetch_assoc(mysqli_stmt_get_result($qr));
        if ($crow && isset($_FILES['file']) && $_FILES['file']['error']===UPLOAD_ERR_OK) {
            $tmp=$_FILES['file']['tmp_name']; $mime=mime_content_type($tmp); $sort=$crow['sort_order'];
            $dest=null; $valid=false; $new_path=''; $db_col='';
            if (!is_dir($base_dir.'icons'))  mkdir($base_dir.'icons',  0755, true);
            if (!is_dir($base_dir.'images')) mkdir($base_dir.'images', 0755, true);
            if (!is_dir($base_dir.'videos')) mkdir($base_dir.'videos', 0755, true);
            if ($file_type==='icon') {
                if (in_array($mime,$allowed_video)) { $dest=$base_dir."icons/icon_{$sort}.mp4"; $new_path="images/general/custom/{$section_id}/icons/icon_{$sort}.mp4"; }
                elseif (in_array($mime,$allowed_image)) { $dest=$base_dir."icons/icon_{$sort}.png"; $new_path="images/general/custom/{$section_id}/icons/icon_{$sort}.png"; }
                $valid=(bool)$dest; $db_col='icon_file';
            } elseif ($file_type==='sign_video'&&in_array($mime,$allowed_video)) {
                $dest=$base_dir."videos/sign_{$sort}.mp4"; $new_path="images/general/custom/{$section_id}/videos/sign_{$sort}.mp4"; $valid=true; $db_col='sign_video';
            } elseif ($file_type==='image'&&in_array($mime,$allowed_image)) {
                $dest=$base_dir."images/image_{$sort}.png"; $new_path="images/general/custom/{$section_id}/images/image_{$sort}.png"; $valid=true; $db_col='image';
            }
            if ($valid&&$dest&&move_uploaded_file($tmp,$dest)) {
                $u=mysqli_prepare($conn,"UPDATE general_section_cards SET {$db_col}=? WHERE id=? AND section_id=?");
                mysqli_stmt_bind_param($u,'sii',$new_path,$card_id,$section_id); mysqli_stmt_execute($u); $message='تم الرفع ✓';
            } else { $error=$valid?'فشل رفع الملف.':'نوع الملف غير مسموح به.'; }
        } else { $error='لم يتم اختيار ملف أو حدث خطأ.'; }
    }

    /* ---- حذف بطاقة ---- */
    if ($action==='delete_card'&&$isSupervisor) {
        $cid_check=intval($_POST['card_id']??0);
        $oc=mysqli_query($conn,"SELECT created_by_supervisor FROM general_section_cards WHERE id=$cid_check AND section_id=$section_id");
        $or=$oc?mysqli_fetch_assoc($oc):null;
        $_cardOwned=$or&&intval($or['created_by_supervisor'])===intval($_SESSION['supervisor_id']??-1);
        if (!supCan($_gcCustomKey,'can_delete')&&!$_cardOwned) { header('Location: supervisor-dashboard.php'); exit; }
    }
    if ($action==='delete_card') {
        $card_id=intval($_POST['card_id']??0);
        if ($card_id>0) {
            $qr=mysqli_prepare($conn,"SELECT icon_file,image,sign_video FROM general_section_cards WHERE id=? AND section_id=?");
            mysqli_stmt_bind_param($qr,'ii',$card_id,$section_id); mysqli_stmt_execute($qr);
            $crow=mysqli_fetch_assoc(mysqli_stmt_get_result($qr));
            if ($crow) { foreach(['icon_file','image','sign_video'] as $f) { if(!empty($crow[$f])){$fp=__DIR__.'/../'.$crow[$f];if(file_exists($fp))@unlink($fp);} } }
            $d=mysqli_prepare($conn,"DELETE FROM general_section_cards WHERE id=? AND section_id=?");
            mysqli_stmt_bind_param($d,'ii',$card_id,$section_id); mysqli_stmt_execute($d); $message='تم حذف البطاقة ✓';
        }
    }

    /* ---- إضافة بطاقة جديدة ---- */
    if ($action==='add_card') {
        $name=trim($_POST['new_card_name']??''); $desc=trim($_POST['new_card_desc']??'');
        if ($name==='') { $error='يرجى إدخال اسم البطاقة.'; } else {
            $qr=mysqli_query($conn,"SELECT MAX(sort_order) as m FROM general_section_cards WHERE section_id={$section_id}");
            $sort=(mysqli_fetch_assoc($qr)['m']??0)+1;
            if (!is_dir($base_dir.'icons'))  mkdir($base_dir.'icons',  0755, true);
            if (!is_dir($base_dir.'images')) mkdir($base_dir.'images', 0755, true);
            if (!is_dir($base_dir.'videos')) mkdir($base_dir.'videos', 0755, true);
            $icon_file=''; $img_file=''; $sign_video='';
            if (isset($_FILES['new_card_icon'])&&$_FILES['new_card_icon']['error']===UPLOAD_ERR_OK) {
                $tmp=$_FILES['new_card_icon']['tmp_name']; $mime=mime_content_type($tmp);
                if (in_array($mime,$allowed_video)) { $dest=$base_dir."icons/icon_{$sort}.mp4"; if(move_uploaded_file($tmp,$dest)) $icon_file="images/general/custom/{$section_id}/icons/icon_{$sort}.mp4"; }
                elseif (in_array($mime,$allowed_image)) { $dest=$base_dir."icons/icon_{$sort}.png"; if(move_uploaded_file($tmp,$dest)) $icon_file="images/general/custom/{$section_id}/icons/icon_{$sort}.png"; }
            }
            if (isset($_FILES['new_card_sign'])&&$_FILES['new_card_sign']['error']===UPLOAD_ERR_OK) {
                $tmp=$_FILES['new_card_sign']['tmp_name']; $mime=mime_content_type($tmp);
                if (in_array($mime,$allowed_video)) { $dest=$base_dir."videos/sign_{$sort}.mp4"; if(move_uploaded_file($tmp,$dest)) $sign_video="images/general/custom/{$section_id}/videos/sign_{$sort}.mp4"; }
            }
            if (isset($_FILES['new_card_image'])&&$_FILES['new_card_image']['error']===UPLOAD_ERR_OK) {
                $tmp=$_FILES['new_card_image']['tmp_name']; $mime=mime_content_type($tmp);
                if (in_array($mime,$allowed_image)) { $dest=$base_dir."images/image_{$sort}.png"; if(move_uploaded_file($tmp,$dest)) $img_file="images/general/custom/{$section_id}/images/image_{$sort}.png"; }
            }
            $supCreator=$isSupervisor?intval($_SESSION['supervisor_id']??0):0;
            $ins=mysqli_prepare($conn,"INSERT INTO general_section_cards (section_id,display_name,description_text,image,icon_file,sign_video,sort_order,created_by_supervisor) VALUES (?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($ins,'isssssii',$section_id,$name,$desc,$img_file,$icon_file,$sign_video,$sort,$supCreator);
            mysqli_stmt_execute($ins);
            $message="✓ تمت إضافة البطاقة «{$name}» بنجاح";
        }
    }

    // AJAX response
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>empty($error),'message'=>$message,'error'=>$error], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$qr=mysqli_prepare($conn,"SELECT * FROM general_section_cards WHERE section_id=? ORDER BY sort_order ASC");
mysqli_stmt_bind_param($qr,'i',$section_id); mysqli_stmt_execute($qr);
$cards=mysqli_fetch_all(mysqli_stmt_get_result($qr), MYSQLI_ASSOC);

$card_colors=['#ed9154','#9ac6e0','#7d98b4','#93d0c1','#d6eaf8','#a8d8ea','#c9e8a0','#f7c9a0','#c9b8e8','#b8d8f0'];
$section_icon_path     = !empty($section['icon_file'])   ? '../'.$section['icon_file'].'?t='.time() : '';
$section_icon_is_video = !empty($section['icon_file'])   && str_ends_with($section['icon_file'],'.mp4');
$sec_sign_path         = !empty($section['sign_video'])  ? '../'.$section['sign_video'].'?t='.time() : '';
$intro_vid_path        = !empty($section['intro_video']) ? '../'.$section['intro_video'].'?t='.time() : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة قسم: <?= htmlspecialchars($section['name']) ?></title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f8fc}
.page-title{text-align:center;color:#21425f;font-size:32px;font-weight:900;margin-bottom:8px}
.page-sub{text-align:center;color:#6a849a;font-size:16px;margin-bottom:28px}
.alert{max-width:960px;margin:0 auto 20px;padding:14px 20px;border-radius:14px;font-size:16px;font-weight:700}
.alert-success{background:#d4f5e4;color:#1a7a45;border:1px solid #a8e6c2}
.alert-error{background:#fde8e8;color:#c0392b;border:1px solid #f5b7b1}
.intro-card{max-width:960px;margin:0 auto 24px;background:#fff;border-radius:22px;padding:24px 28px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7}
.intro-card h2{margin:0 0 18px;color:#21425f;font-size:20px;font-weight:900}
.fields-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;margin-bottom:16px}
.field-group{display:flex;flex-direction:column;gap:6px}
.field-group label{font-size:13px;font-weight:700;color:#21425f}
.field-group input[type=text],.field-group textarea{padding:10px 14px;border:1px solid #cde0ef;border-radius:10px;font-size:15px;font-family:inherit;color:#21425f;background:#fff;resize:vertical}
.field-group input[type=file]{padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:13px;cursor:pointer;background:#fff;width:100%}
.field-hint{font-size:11px;color:#8aabbd;margin-top:3px}
.upload-msg{font-size:12px;font-weight:700;min-height:16px;margin-top:4px}
.items-grid{max-width:960px;margin:0 auto;display:grid;gap:24px}
.item-card{background:#fff;border-radius:22px;padding:24px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7}
.item-header{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px}
.item-header-left{display:flex;align-items:center;gap:14px}
.item-color-dot{width:18px;height:18px;border-radius:50%;flex-shrink:0}
.item-title{font-size:20px;font-weight:900;color:#21425f}
.media-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px}
.media-block{border:1px solid #e2edf7;border-radius:14px;padding:14px;background:#f8fbff}
.media-block h4{margin:0 0 10px;font-size:14px;font-weight:900;color:#21425f}
.media-preview{width:100%;height:110px;border-radius:10px;overflow:hidden;background:#ddd;margin-bottom:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative}
.media-preview:hover .change-overlay{opacity:1}
.media-preview video,.media-preview img{width:100%;height:100%;object-fit:cover;display:block}
.no-media{color:#aab8c4;font-size:13px}
.change-overlay{position:absolute;inset:0;background:rgba(33,66,95,.5);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;pointer-events:none}
.file-input-hidden{display:none}
.text-section{border-top:1px solid #e2edf7;padding-top:16px}
.text-section h4{margin:0 0 12px;font-size:15px;font-weight:900;color:#21425f}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.field-group textarea{min-height:90px}
.btn-delete{padding:8px 18px;border-radius:12px;background:#fde8e8;color:#c0392b;font-size:13px;font-weight:900;border:none;cursor:pointer;transition:.2s}
.btn-delete:hover{background:#d93025;color:#fff}
.add-card{max-width:960px;margin:24px auto 0;background:#f0f8ff;border-radius:22px;padding:24px;border:2px dashed #b0cceb}
.add-card h2{margin:0 0 18px;color:#21425f;font-size:20px;font-weight:900}
.btn-add{padding:10px 26px;border-radius:12px;background:#21425f;color:#fff;font-size:14px;font-weight:900;border:none;cursor:pointer;transition:.2s}
.btn-add:hover{background:#2d5a7e}
.save-bar{max-width:960px;margin:32px auto 0;display:flex;justify-content:center}
.btn-save-main{padding:16px 60px;border-radius:18px;background:#28b978;color:#fff;font-size:18px;font-weight:900;border:none;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(40,185,120,.25)}
.btn-save-main:hover{background:#22a86c;transform:translateY(-2px)}
.back-link{display:block;width:max-content;margin:20px auto 0;padding:14px 36px;border-radius:18px;background:#21425f;color:#fff;text-decoration:none;font-size:17px;font-weight:900;box-shadow:0 8px 20px rgba(33,66,95,.16);transition:.25s}
.back-link:hover{transform:translateY(-3px)}
.delete-section-btn{display:block;width:max-content;margin:14px auto 0;padding:12px 32px;border-radius:18px;background:#fde8e8;color:#c0392b;text-decoration:none;font-size:16px;font-weight:900;border:2px solid #f5b7b1;transition:.25s;cursor:pointer}
.delete-section-btn:hover{background:#d93025;color:#fff;border-color:#d93025}
@media(max-width:700px){.fields-grid-2{grid-template-columns:1fr}.field-row{grid-template-columns:1fr}.media-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">
<h1 class="page-title"><?= htmlspecialchars($section['icon']) ?> <?= htmlspecialchars($section['name']) ?></h1>
<p class="page-sub">تعديل النصوص والصور والفيديوهات لكل بطاقة في هذا القسم</p>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- ===== فورم واحد يشمل كل شيء ===== -->
<form method="post" id="main-form">
<input type="hidden" name="action" value="save_all_text">

<!-- معلومات القسم -->
<div class="intro-card">
    <h2>📁 معلومات القسم</h2>
    <div class="fields-grid-2">
        <div class="field-group">
            <label>اسم القسم *</label>
            <input type="text" name="section_name" value="<?= htmlspecialchars($section['name']) ?>" placeholder="مثال: أوقات اليوم" required>
        </div>
        <div class="field-group">
            <label>📝 وصف القسم</label>
            <textarea name="section_description" rows="3" placeholder="وصف مختصر..."><?= htmlspecialchars($section['description'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- ملفات القسم (AJAX) -->
    <div class="fields-grid-2" style="border-top:1px solid #e2edf7;padding-top:16px">
        <div class="field-group">
            <label>🖼 أيقونة القسم الخارجية</label>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_section_icon">
                <input type="hidden" name="ajax" value="1">
                <input type="file" name="section_icon_file" accept="image/*,video/mp4"
                       style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:13px;cursor:pointer;background:#fff"
                       onchange="ajaxUpload(this.form,'msg-sec-icon')">
                <span class="field-hint">صورة أو فيديو MP4 — يُرفع فوراً</span>
                <div class="upload-msg" id="msg-sec-icon"></div>
            </form>
            <?php if ($section_icon_path): ?>
            <div style="margin-top:8px;width:100%;height:70px;border-radius:8px;overflow:hidden;background:#ddd">
                <?php if ($section_icon_is_video): ?>
                <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover"><source src="<?= $section_icon_path ?>" type="video/mp4"></video>
                <?php else: ?><img src="<?= $section_icon_path ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="field-group">
            <label>🤟 فيديو القسم الخارجي (بالإشارة)
</label>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_section_sign">
                <input type="hidden" name="ajax" value="1">
                <input type="file" name="section_sign_file" accept="video/mp4"
                       style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:13px;cursor:pointer;background:#fff"
                       onchange="ajaxUpload(this.form,'msg-sec-sign')">
                <span class="field-hint">فيديو MP4 فقط — يُرفع فوراً</span>
                <div class="upload-msg" id="msg-sec-sign"></div>
            </form>
            <?php if ($sec_sign_path): ?>
            <div style="margin-top:8px;width:100%;height:70px;border-radius:8px;overflow:hidden;background:#ddd">
                <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover"><source src="<?= $sec_sign_path ?>" type="video/mp4"></video>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- الفيديو التعريفي -->
<div class="intro-card">
    <h2>🎬 الفيديو التعريفي للقسم</h2>
    <div class="fields-grid-2">
        <div class="field-group">
            <label>← النص الذي يظهر فوق الفيديو</label>
            <input type="text" name="intro_title" value="<?= htmlspecialchars($section['intro_title'] ?? '') ?>" placeholder="مثال: هيا نتعرف على أوقات اليوم">
            <span class="field-hint">يظهر هذا النص بالخط الكبير أعلى الفيديو</span>
        </div>
        <div class="field-group">
            <label>📤 رفع الفيديو التعريفي</label>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_intro_video">
                <input type="hidden" name="ajax" value="1">
                <input type="file" name="intro_video_file" accept="video/mp4"
                       style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:13px;cursor:pointer;background:#fff"
                       onchange="ajaxUpload(this.form,'msg-intro-vid')">
                <span class="field-hint">فيديو MP4 فقط — يُرفع فوراً</span>
                <div class="upload-msg" id="msg-intro-vid"></div>
            </form>
            <?php if ($intro_vid_path): ?>
            <div style="margin-top:8px;width:100%;height:60px;border-radius:8px;overflow:hidden;background:#ddd">
                <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover"><source src="<?= $intro_vid_path ?>" type="video/mp4"></video>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- البطاقات -->
<div class="items-grid">
<?php if (empty($cards)): ?>
<p style="text-align:center;color:#8ab0c8;font-size:16px">لا توجد بطاقات بعد — أضف بطاقة من الأسفل.</p>
<?php endif; ?>

<?php foreach ($cards as $i => $card):
    $color     = $card_colors[$i % count($card_colors)];
    $icon_path = $card['icon_file']  ? '../'.$card['icon_file'].'?t='.time() : '';
    $sign_path = $card['sign_video'] ? '../'.$card['sign_video'].'?t='.time() : '';
    $img_path  = $card['image']      ? '../'.$card['image'].'?t='.time()      : '';
    $is_vid_icon = $card['icon_file'] && str_ends_with($card['icon_file'],'.mp4');
    $_ownsCard = $isSupervisor && intval($card['created_by_supervisor']??0)===intval($_SESSION['supervisor_id']??-1);
?>
<div class="item-card">
    <div class="item-header">
        <div class="item-header-left">
            <span class="item-color-dot" style="background:<?= $color ?>"></span>
            <span class="item-title"><?= htmlspecialchars($card['display_name']) ?></span>
        </div>
        <?php if (!$isSupervisor || supCan($_gcCustomKey,'can_delete') || $_ownsCard): ?>
        <form method="post" onsubmit="return confirm('هل تريد حذف هذه البطاقة نهائياً؟')">
            <input type="hidden" name="action"  value="delete_card">
            <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
            <button type="submit" class="btn-delete">🗑 حذف البطاقة</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="media-row">
        <div class="media-block">
            <h4>🎬 الأيقونة</h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_card_file">
                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                <input type="hidden" name="file_type" value="icon">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('file-icon-<?= $card['id'] ?>').click()">
                    <?php if ($icon_path&&$is_vid_icon): ?><video autoplay muted loop playsinline><source src="<?= $icon_path ?>" type="video/mp4"></video>
                    <?php elseif ($icon_path): ?><img src="<?= $icon_path ?>" alt="">
                    <?php else: ?><span class="no-media">لا يوجد<br><small>انقر للإضافة</small></span><?php endif; ?>
                    <div class="change-overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-icon-<?= $card['id'] ?>"></div>
                <input type="file" name="file" id="file-icon-<?= $card['id'] ?>" class="file-input-hidden"
                       accept="image/*,video/mp4" onchange="ajaxUpload(this.form,'msg-icon-<?= $card['id'] ?>')">
            </form>
        </div>

        <div class="media-block">
            <h4>🤟 فيديو لغة الإشارة</h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_card_file">
                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                <input type="hidden" name="file_type" value="sign_video">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('file-sign-<?= $card['id'] ?>').click()">
                    <?php if ($sign_path): ?><video autoplay muted loop playsinline><source src="<?= $sign_path ?>" type="video/mp4"></video>
                    <?php else: ?><span class="no-media">لا يوجد<br><small>انقر للإضافة</small></span><?php endif; ?>
                    <div class="change-overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-sign-<?= $card['id'] ?>"></div>
                <input type="file" name="file" id="file-sign-<?= $card['id'] ?>" class="file-input-hidden"
                       accept="video/mp4" onchange="ajaxUpload(this.form,'msg-sign-<?= $card['id'] ?>')">
            </form>
        </div>

        <div class="media-block">
            <h4>🖼 الصورة الرئيسية</h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_card_file">
                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                <input type="hidden" name="file_type" value="image">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('file-img-<?= $card['id'] ?>').click()">
                    <?php if ($img_path): ?><img src="<?= $img_path ?>" alt="">
                    <?php else: ?><span class="no-media">لا يوجد<br><small>انقر للإضافة</small></span><?php endif; ?>
                    <div class="change-overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-img-<?= $card['id'] ?>"></div>
                <input type="file" name="file" id="file-img-<?= $card['id'] ?>" class="file-input-hidden"
                       accept="image/*" onchange="ajaxUpload(this.form,'msg-img-<?= $card['id'] ?>')">
            </form>
        </div>
    </div>

    <div class="text-section">
        <h4>✏ تعديل النصوص</h4>
        <div class="field-row">
            <div class="field-group">
                <label>اسم البطاقة</label>
                <input type="text" name="display_name[<?= $card['id'] ?>]" value="<?= htmlspecialchars($card['display_name']) ?>">
            </div>
            <div class="field-group">
                <label>النص الوصفي</label>
                <textarea name="description_text[<?= $card['id'] ?>]"><?= htmlspecialchars($card['description_text'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="save-bar">
    <button type="submit" class="btn-save-main">💾 حفظ جميع التعديلات</button>
</div>

</form>

<?php if (!$isSupervisor || supCan($_gcCustomKey,'can_add')): ?>
<div class="add-card">
    <h2>➕ إضافة بطاقة جديدة</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_card">
        <div class="field-row" style="margin-bottom:14px">
            <div class="field-group">
                <label>اسم العرض *</label>
                <input type="text" name="new_card_name" placeholder="مثال: الصباح" required>
            </div>
            <div class="field-group">
                <label>النص الوصفي</label>
                <textarea name="new_card_desc" rows="3" placeholder="وصف مختصر..."></textarea>
            </div>
        </div>
        <div class="media-row" style="margin-bottom:16px">
            <div class="media-block">
                <h4>🎬 الأيقونة</h4>
                <input type="file" name="new_card_icon" accept="image/*,video/mp4" style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:14px;cursor:pointer;background:#fff">
            </div>
            <div class="media-block">
                <h4>🤟 فيديو الإشارة</h4>
                <input type="file" name="new_card_sign" accept="video/mp4" style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:14px;cursor:pointer;background:#fff">
            </div>
            <div class="media-block">
                <h4>🖼 الصورة الرئيسية</h4>
                <input type="file" name="new_card_image" accept="image/*" style="width:100%;padding:8px 12px;border:2px dashed #b0c8e0;border-radius:10px;font-size:14px;cursor:pointer;background:#fff">
            </div>
        </div>
        <button type="submit" class="btn-add">➕ إضافة البطاقة</button>
    </form>
</div>
<?php endif; ?>

<a href="lesson-types.php?subject=الثقافة العامة" class="back-link">رجوع</a>
<?php if (!$isSupervisor || supCan($_gcCustomKey,'can_delete')): ?>
<a href="manage-custom-section.php?id=<?= $section_id ?>&delete_section=1"
   class="delete-section-btn"
   onclick="return confirm('هل أنت متأكد؟ سيتم حذف القسم «<?= htmlspecialchars($section['name']) ?>» وجميع بطاقاته نهائياً!')">
   🗑 حذف هذا القسم نهائياً
</a>
<?php endif; ?>

</main>
</div>
<script>
function ajaxUpload(form, msgId) {
    const msg = document.getElementById(msgId);
    msg.textContent = '⏳ جاري الرفع...';
    msg.style.color = '#8ab0c8';
    const fd = new FormData(form);
    fetch('', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => {
            msg.textContent = d.ok ? '✓ تم الرفع' : ('✗ ' + (d.error || 'خطأ'));
            msg.style.color  = d.ok ? '#28b978' : '#c0392b';
        })
        .catch(() => { msg.textContent = '✗ فشل الاتصال'; msg.style.color = '#c0392b'; });
}
</script>
</body>
</html>
