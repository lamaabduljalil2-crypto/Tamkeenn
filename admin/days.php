<?php
require_once 'includes/admin-check.php';

$base_dir = __DIR__ . '/../images/general/days/';
$base_url = '../images/general/days/';
$cfg_file = $base_dir . 'config.json';

$default_config = [
    'items' => [
        'engine'    => ['title'=>'الأسبوع',   'train_img'=>'0','letters'=>['ا','ل','ا','س','ب','و','ع']],
        'saturday'  => ['title'=>'السبت',      'train_img'=>'1','letters'=>['ا','ل','س','ب','ت']],
        'sunday'    => ['title'=>'الأحد',      'train_img'=>'2','letters'=>['ا','ل','ا','ح','د']],
        'monday'    => ['title'=>'الإثنين',    'train_img'=>'3','letters'=>['ا','ل','ا','ث','ن','ي','ن']],
        'tuesday'   => ['title'=>'الثلاثاء',   'train_img'=>'4','letters'=>['ا','ل','ث','ل','ا','ث','ا','ء']],
        'wednesday' => ['title'=>'الأربعاء',   'train_img'=>'5','letters'=>['ا','ل','ا','ر','ب','ع','ا','ء']],
        'thursday'  => ['title'=>'الخميس',     'train_img'=>'6','letters'=>['ا','ل','خ','م','ي','س']],
        'friday'    => ['title'=>'الجمعة',     'train_img'=>'7','letters'=>['ا','ل','ج','م','ع','ة']],
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
        $titles  = $_POST['title']   ?? [];
        $letters = $_POST['letters'] ?? [];
        foreach ($config['items'] as $key => $item) {
            if (isset($titles[$key]))  $config['items'][$key]['title'] = trim($titles[$key]);
            if (isset($letters[$key])) {
                $arr = array_filter(array_map('trim', explode(',', $letters[$key])));
                if (!empty($arr)) $config['items'][$key]['letters'] = array_values($arr);
            }
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

            if ($file_type === 'train_img' && in_array($mime,$allowed_image)) {
                $img_num = $config['items'][$key]['train_img'] ?? '0';
                $dest    = $base_dir.'train/'.$img_num.'.png';
                $valid   = true;
            } elseif ($file_type === 'video' && in_array($mime,$allowed_video)) {
                $dest  = $base_dir.'videos/'.$key.'.mp4';
                $valid = true;
            }

            if ($valid && $dest) {
                if (!is_dir(dirname($dest))) mkdir(dirname($dest),0755,true);
                if (move_uploaded_file($tmp,$dest)) { $message='تم الرفع ✓'; }
                else { $error='فشل رفع الملف – تحقق من صلاحيات المجلد.'; }
            } else { $error='نوع الملف غير مسموح به.'; }
        } else { $error='لم يتم اختيار ملف أو حدث خطأ أثناء الرفع.'; }
    }

    // AJAX response
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>empty($error), 'message'=>$message, 'error'=>$error], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة أيام الأسبوع</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f8fc}
.page-title{text-align:center;color:#21425f;font-size:32px;font-weight:900;margin-bottom:8px}
.page-sub{text-align:center;color:#6a849a;font-size:16px;margin-bottom:28px}
.alert{max-width:960px;margin:0 auto 20px;padding:14px 20px;border-radius:14px;font-size:16px;font-weight:700}
.alert-success{background:#d4f5e4;color:#1a7a45;border:1px solid #a8e6c2}
.alert-error{background:#fde8e8;color:#c0392b;border:1px solid #f5b7b1}
.items-grid{max-width:960px;margin:0 auto;display:grid;gap:24px}
.item-card{background:#fff;border-radius:22px;padding:24px;box-shadow:0 8px 24px rgba(33,66,95,.09);border:1px solid #e2edf7}
.item-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #e8f0f7}
.item-badge{background:#21425f;color:#fff;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:900}
.item-title{font-size:20px;font-weight:900;color:#21425f}
.media-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.media-block{border:1px solid #e2edf7;border-radius:14px;padding:14px;background:#f8fbff}
.media-block h4{margin:0 0 10px;font-size:14px;font-weight:900;color:#21425f}
.media-preview{width:100%;height:120px;border-radius:10px;overflow:hidden;background:#ddd;margin-bottom:4px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative}
.media-preview:hover .overlay{opacity:1}
.media-preview video,.media-preview img{width:100%;height:100%;object-fit:cover;display:block}
.overlay{position:absolute;inset:0;background:rgba(33,66,95,.5);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;pointer-events:none}
.file-hidden{display:none}
.upload-msg{font-size:12px;font-weight:700;min-height:18px;margin-top:4px}
.text-section{border-top:1px solid #e2edf7;padding-top:16px}
.text-section h4{margin:0 0 12px;font-size:15px;font-weight:900;color:#21425f}
.field-group{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.field-group label{font-size:13px;font-weight:700;color:#21425f}
.field-group input{padding:10px 12px;border:1px solid #cde0ef;border-radius:10px;font-size:14px;font-family:inherit;color:#21425f;background:#fff}
.letters-hint{font-size:12px;color:#8ab0c8;margin-top:4px}
.letters-preview{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.letter-badge{background:#e8f3ff;border:1px solid #b8d8f0;border-radius:8px;padding:4px 10px;font-size:16px;font-weight:700;color:#21425f}
.save-bar{max-width:960px;margin:32px auto 0;display:flex;justify-content:center}
.btn-save-main{padding:16px 60px;border-radius:18px;background:#28b978;color:#fff;font-size:18px;font-weight:900;border:none;cursor:pointer;transition:.2s;box-shadow:0 8px 20px rgba(40,185,120,.25)}
.btn-save-main:hover{background:#22a86c;transform:translateY(-2px)}
.back-link{display:block;width:max-content;margin:20px auto 0;padding:14px 36px;border-radius:18px;background:#21425f;color:#fff;text-decoration:none;font-size:17px;font-weight:900;box-shadow:0 8px 20px rgba(33,66,95,.16);transition:.25s}
.back-link:hover{transform:translateY(-3px)}
@media(max-width:700px){.media-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">
<h1 class="page-title">إدارة أيام الأسبوع</h1>
<p class="page-sub">تعديل صور القطار، فيديوهات الإشارة، والحروف لكل يوم</p>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post">
<input type="hidden" name="action" value="update_all_text">
<div class="items-grid">
<?php foreach ($config['items'] as $key => $item):
    $img_num   = $item['train_img'];
    $is_engine = ($key === 'engine');
    $uid       = htmlspecialchars($key);
?>
<div class="item-card">
    <div class="item-header">
        <span class="item-badge"><?= $is_engine ? '🚂 قاطرة' : '🚃 عربة' ?></span>
        <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
    </div>

    <div class="media-row">
        <div class="media-block">
            <h4>🖼 صورة <?= $is_engine ? 'القاطرة' : 'العربة' ?></h4>
            <form method="post" enctype="multipart/form-data" onsubmit="return false">
                <input type="hidden" name="action" value="upload_file">
                <input type="hidden" name="file_type" value="train_img">
                <input type="hidden" name="key" value="<?= $uid ?>">
                <input type="hidden" name="ajax" value="1">
                <div class="media-preview" onclick="document.getElementById('train-<?= $uid ?>').click()">
                    <img src="<?= $base_url ?>train/<?= $img_num ?>.png?t=<?= time() ?>" alt="">
                    <div class="overlay">🔄 تغيير</div>
                </div>
                <div class="upload-msg" id="msg-train-<?= $uid ?>"></div>
                <input type="file" name="file" id="train-<?= $uid ?>" class="file-hidden" accept="image/*"
                       onchange="ajaxUpload(this.form, 'msg-train-<?= $uid ?>')">
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
    </div>

    <div class="text-section">
        <h4>✏ تعديل العنوان والحروف</h4>
        <div class="field-group">
            <label>الاسم / العنوان</label>
            <input type="text" name="title[<?= $uid ?>]" value="<?= htmlspecialchars($item['title']) ?>">
        </div>
        <div class="field-group">
            <label>الحروف (مفصولة بفاصلة)</label>
            <input type="text" name="letters[<?= $uid ?>]" value="<?= htmlspecialchars(implode(',', $item['letters'])) ?>" dir="rtl">
            <span class="letters-hint">مثال: ا,ل,س,ب,ت</span>
        </div>
        <div class="letters-preview">
            <?php foreach ($item['letters'] as $letter): ?>
            <span class="letter-badge"><?= htmlspecialchars($letter) ?></span>
            <?php endforeach; ?>
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
