<?php
require_once 'includes/admin-check.php';

$base_dir = __DIR__ . '/../images/general/islam-pillars/';
$base_url = '../images/general/islam-pillars/';
$cfg_file = $base_dir . 'config.json';

$default_config = [
    'items' => [
        'shahada' => ['name'=>'الشهادتان','text'=>'الشهادتان هما أول أركان الإسلام وفيهما يعلن المسلم إيمانه بالله ورسوله.','icon_type'=>'image'],
        'prayer'  => ['name'=>'إقام الصلاة','text'=>'نقيم الصلاة كل يوم بخشوع ونذكر الله في أوقات محددة.','icon_type'=>'video'],
        'zakat'   => ['name'=>'إيتاء الزكاة','text'=>'الزكاة تعلمنا مساعدة الآخرين ومشاركة الخير مع المحتاجين.','icon_type'=>'video'],
        'fasting' => ['name'=>'صوم رمضان','text'=>'في رمضان نصوم ونتعلم الصبر والطاعة وفعل الخير.','icon_type'=>'video'],
        'hajj'    => ['name'=>'حج البيت','text'=>'يحج المسلم إلى بيت الله الحرام إذا كان يستطيع ذلك.','icon_type'=>'image'],
    ]
];

$config = $default_config;
if (file_exists($cfg_file)) {
    $loaded = json_decode(file_get_contents($cfg_file), true);
    if ($loaded) $config = array_replace_recursive($default_config, $loaded);
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_all_text') {
        $names = $_POST['name'] ?? [];
        $texts = $_POST['text'] ?? [];
        foreach ($config['items'] as $key => $item) {
            if (isset($names[$key])) $config['items'][$key]['name'] = trim($names[$key]);
            if (isset($texts[$key])) $config['items'][$key]['text'] = trim($texts[$key]);
        }
        file_put_contents($cfg_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $message = 'تم حفظ التعديلات بنجاح ✓';
    }

    if ($action === 'upload_file') {
        $key       = $_POST['key']       ?? '';
        $file_type = $_POST['file_type'] ?? '';
        $allowed_video = ['video/mp4','video/webm'];
        $allowed_image = ['image/png','image/jpeg','image/jpg','image/gif','image/webp'];

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['file']['tmp_name'];
            $mime = mime_content_type($tmp);
            $dest = null; $valid = false;

            if ($file_type === 'intro_video' && in_array($mime,$allowed_video)) { $dest=$base_dir.'videos/islam.mp4'; $valid=true; }
            elseif ($file_type === 'icon_image' && in_array($mime,$allowed_image)) { $dest=$base_dir.'icons/'.$key.'.png'; $valid=true; }
            elseif ($file_type === 'icon_video' && in_array($mime,$allowed_video)) { $dest=$base_dir.'icons/'.$key.'.mp4'; $valid=true; }
            elseif ($file_type === 'video' && in_array($mime,$allowed_video)) { $dest=$base_dir.'videos/'.$key.'.mp4'; $valid=true; }
            elseif ($file_type === 'image' && in_array($mime,$allowed_image)) { $dest=$base_dir.'images/'.$key.'.png'; $valid=true; }

            if ($valid && $dest) {
                if (!is_dir(dirname($dest))) mkdir(dirname($dest),0755,true);
                if (move_uploaded_file($tmp,$dest)) { $message='تم الرفع ✓'; }
                else { $error='فشل رفع الملف – تحقق من صلاحيات المجلد.'; }
            } else { $error='نوع الملف غير مسموح به.'; }
        } else { $error='لم يتم اختيار ملف أو حدث خطأ أثناء الرفع.'; }
    }

    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>empty($error), 'message'=>$message, 'error'=>$error], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$colors = ['shahada'=>'#eddca8','prayer'=>'#cfe4d1','zakat'=>'#d5e5f3','fasting'=>'#e9d4ec','hajj'=>'#d9ead7'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة أركان الإسلام</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f8fc}
.page-title{text-align:center;color:#21425f;font-size:32px;font-weight:900;margin-bottom:8px}
.page-sub{text-align:center;color:#6a849a;font-size:16px;margin-bottom:28px}
.alert{max-width:960px;margin:0 auto 20px;padding:14px 20px;border-radius:14px;font-size:16px;font-weight:700}
.alert-success{background:#d4f5e4;color:#1a7a45;border:1px solid #a8e6c2}
.alert-error{background:#fde8e8;color:#c0392b;border:1px solid #f5b7b1}
.intro-card{max-width:960px;margin:0 auto 36px;background:#fff;border-radius:22px;padding:24px 28px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7}
.intro-card h2{margin:0 0 16px;color:#21425f;font-size:22px;font-weight:900}
.upload-row{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.preview-box{width:140px;height:100px;border-radius:14px;overflow:hidden;background:#ddd;flex-shrink:0;cursor:pointer;position:relative}
.preview-box:hover .overlay{opacity:1}
.preview-box video{width:100%;height:100%;object-fit:cover;display:block}
.items-grid{max-width:960px;margin:0 auto;display:grid;gap:24px}
.item-card{background:#fff;border-radius:22px;padding:24px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7}
.item-header{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.item-color-dot{width:18px;height:18px;border-radius:50%;flex-shrink:0}
.item-title{font-size:20px;font-weight:900;color:#21425f}
.media-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:16px;margin-bottom:20px}
.media-block{border:1px solid #e2edf7;border-radius:14px;padding:14px;background:#f8fbff}
.media-block h4{margin:0 0 10px;font-size:14px;font-weight:900;color:#21425f}
.media-preview{width:100%;height:110px;border-radius:10px;overflow:hidden;background:#ddd;margin-bottom:4px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative}
.media-preview:hover .overlay{opacity:1}
.media-preview video,.media-preview img{width:100%;height:100%;object-fit:cover;display:block}
.no-media{color:#aab8c4;font-size:12px;text-align:center}
.overlay{position:absolute;inset:0;background:rgba(33,66,95,.5);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;pointer-events:none}
.file-hidden{display:none}
.upload-msg{font-size:12px;font-weight:700;min-height:18px;margin-top:4px}
.text-section{border-top:1px solid #e2edf7;padding-top:16px}
.text-section h4{margin:0 0 12px;font-size:15px;font-weight:900;color:#21425f}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.field-group{display:flex;flex-direction:column;gap:6px}
.field-group label{font-size:13px;font-weight:700;color:#21425f}
.field-group input,.field-group textarea{padding:10px 12px;border:1px solid #cde0ef;border-radius:10px;font-size:14px;font-family:inherit;color:#21425f;background:#fff;resize:vertical}
.field-group textarea{min-height:90px}
.save-bar{max-width:960px;margin:32px auto 0;display:flex;justify-content:center}
.btn-save-main{padding:16px 60px;border-radius:18px;background:#28b978;color:#fff;font-size:18px;font-weight:900;border:none;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(40,185,120,.25)}
.btn-save-main:hover{background:#22a86c;transform:translateY(-2px)}
.back-link{display:block;width:max-content;margin:20px auto 0;padding:14px 36px;border-radius:18px;background:#21425f;color:#fff;text-decoration:none;font-size:17px;font-weight:900;box-shadow:0 8px 20px rgba(33,66,95,.16);transition:.25s}
.back-link:hover{transform:translateY(-3px)}
@media(max-width:700px){.field-row{grid-template-columns:1fr}.media-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">
<h1 class="page-title">إدارة أركان الإسلام</h1>
<p class="page-sub">تعديل النصوص والصور والفيديوهات لكل ركن من أركان الإسلام</p>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- فيديو المقدمة -->
<div class="intro-card">
    <h2>☪ فيديو المقدمة العامة</h2>
    <div class="upload-row">
        <form method="post" enctype="multipart/form-data" onsubmit="return false">
            <input type="hidden" name="action" value="upload_file">
            <input type="hidden" name="file_type" value="intro_video">
            <input type="hidden" name="key" value="islam">
            <input type="hidden" name="ajax" value="1">
            <div class="preview-box" onclick="document.getElementById('introFile').click()">
                <video autoplay muted loop playsinline>
                    <source src="<?= $base_url ?>videos/islam.mp4?t=<?= time() ?>" type="video/mp4">
                </video>
                <div class="overlay">🔄 تغيير</div>
            </div>
            <div class="upload-msg" id="msg-intro-islam" style="margin-top:6px"></div>
            <input type="file" name="file" id="introFile" class="file-hidden" accept="video/mp4"
                   onchange="ajaxUpload(this.form, 'msg-intro-islam')">
        </form>
        <span style="font-size:14px;color:#6a849a">انقر على الفيديو لتغييره</span>
    </div>
</div>

<form method="post">
<input type="hidden" name="action" value="update_all_text">
<div class="items-grid">
<?php foreach ($config['items'] as $key => $item):
    $color = $colors[$key] ?? '#ccc';
    $is_image_icon = ($item['icon_type'] === 'image');
    $uid = htmlspecialchars($key);
?>
<div class="item-card">
    <div class="item-header">
        <span class="item-color-dot" style="background:<?= $color ?>"></span>
        <span class="item-title"><?= htmlspecialchars($item['name']) ?></span>
    </div>

    <div class="media-row">
        <div class="media-block">
            <h4><?= $is_image_icon ? '🖼 أيقونة الركن (صورة)' : '🎬 أيقونة الركن (فيديو)' ?></h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_file">
                <input type="hidden" name="file_type" value="<?= $is_image_icon ? 'icon_image' : 'icon_video' ?>">
                <input type="hidden" name="key" value="<?= $uid ?>">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('icon-<?= $uid ?>').click()">
                    <?php if ($is_image_icon): ?>
                    <img src="<?= $base_url ?>icons/<?= $uid ?>.png?t=<?= time() ?>" alt="">
                    <?php else: ?>
                    <video autoplay muted loop playsinline><source src="<?= $base_url ?>icons/<?= $uid ?>.mp4?t=<?= time() ?>" type="video/mp4"></video>
                    <?php endif; ?>
                    <div class="overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-icon-<?= $uid ?>"></div>
                <input type="file" name="file" id="icon-<?= $uid ?>" class="file-hidden"
                       accept="<?= $is_image_icon ? 'image/*' : 'video/mp4' ?>"
                       onchange="ajaxUpload(this.form, 'msg-icon-<?= $uid ?>')">
            </form>
        </div>

        <div class="media-block">
            <h4>🤟 فيديو لغة الإشارة</h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_file">
                <input type="hidden" name="file_type" value="video">
                <input type="hidden" name="key" value="<?= $uid ?>">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('sign-<?= $uid ?>').click()">
                    <video autoplay muted loop playsinline><source src="<?= $base_url ?>videos/<?= $uid ?>.mp4?t=<?= time() ?>" type="video/mp4"></video>
                    <div class="overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-sign-<?= $uid ?>"></div>
                <input type="file" name="file" id="sign-<?= $uid ?>" class="file-hidden" accept="video/mp4"
                       onchange="ajaxUpload(this.form, 'msg-sign-<?= $uid ?>')">
            </form>
        </div>

        <div class="media-block">
            <h4>🖼 الصورة الرئيسية</h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_file">
                <input type="hidden" name="file_type" value="image">
                <input type="hidden" name="key" value="<?= $uid ?>">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('img-<?= $uid ?>').click()">
                    <img src="<?= $base_url ?>images/<?= $uid ?>.png?t=<?= time() ?>" alt="">
                    <div class="overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-img-<?= $uid ?>"></div>
                <input type="file" name="file" id="img-<?= $uid ?>" class="file-hidden" accept="image/*"
                       onchange="ajaxUpload(this.form, 'msg-img-<?= $uid ?>')">
            </form>
        </div>
    </div>

    <div class="text-section">
        <h4>✏ تعديل النصوص</h4>
        <div class="field-row">
            <div class="field-group">
                <label>الاسم المعروض</label>
                <input type="text" name="name[<?= $uid ?>]" value="<?= htmlspecialchars($item['name']) ?>">
            </div>
            <div class="field-group">
                <label>النص الوصفي</label>
                <textarea name="text[<?= $uid ?>]"><?= htmlspecialchars($item['text']) ?></textarea>
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

<a href="lesson-types.php?subject=الثقافة العامة" class="back-link">رجوع</a>
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
