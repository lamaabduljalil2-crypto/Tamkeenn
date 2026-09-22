<?php
/**
 * preview.php — معاينة صفحات الطفل من لوحة المشرف/الأدمن
 * يعرض الصفحة داخل iframe مع شريط معاينة في الأعلى
 */

require_once __DIR__ . '/../config/session_admin.php';

// تأكد المستخدم أدمن أو مشرف
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$adminUser = $_SESSION['admin_username'];
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$adminId   = $_SESSION['admin_id'];

$targetUrl = trim($_GET['url'] ?? '');

if ($targetUrl === '') {
    die('لم يُحدد رابط المعاينة.');
}

// حماية: الرابط يجب أن يبقى داخل المشروع
if (strpos($targetUrl, '..') !== false || strpos($targetUrl, '//') !== false) {
    die('رابط غير مسموح به.');
}

// ── إنشاء session طفل وهمي ────────────────────────────────────
session_write_close();

session_set_cookie_params([
    'lifetime' => 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('kids_child');
session_start();

$_SESSION['user_id']           = 0;
$_SESSION['role']              = 'child';
$_SESSION['child_name']        = 'معاينة — ' . $adminUser;
$_SESSION['username']          = $adminUser;
$_SESSION['lang']              = 'ar';
$_SESSION['_preview_mode']     = true;

session_write_close();

// ── أعد session الأدمن ────────────────────────────────────────
session_name('kids_admin');
session_start();
$_SESSION['admin_id']       = $adminId;
$_SESSION['admin_username'] = $adminUser;
$_SESSION['admin_role']     = $adminRole;
session_write_close();

// ── بناء الرابط الكامل للـ iframe ────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
// احسب المسار الجذر للمشروع (kids/)
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$childUrl  = $protocol . '://' . $host . $scriptDir . '/' . ltrim($targetUrl, '/');

$dashboardUrl = $protocol . '://' . $host . $scriptDir . '/admin/supervisor-dashboard.php';
$sectionLabel = trim($_GET['label'] ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>معاينة — <?php echo htmlspecialchars($sectionLabel ?: $targetUrl) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#0f2942;display:flex;flex-direction:column;height:100vh;overflow:hidden;}

/* ── شريط المعاينة ── */
.preview-bar{
    display:flex;align-items:center;gap:10px;
    padding:10px 20px;
    background:linear-gradient(135deg,#0f2942,#1a4a7a);
    border-bottom:3px solid #e65100;
    flex-shrink:0;
    z-index:9999;
    flex-wrap:wrap;
}
.preview-badge{
    display:flex;align-items:center;gap:6px;
    background:#e65100;color:#fff;
    padding:5px 12px;border-radius:20px;
    font-size:12px;font-weight:900;
    white-space:nowrap;
    animation:pulse-badge 2s infinite;
    flex-shrink:0;
}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.8}}
.preview-info{flex:1;color:rgba(255,255,255,.85);font-size:12px;font-weight:700;min-width:0;}
.preview-info strong{color:#fff;}
.preview-warning{font-size:11px;color:#ffcc80;margin-top:2px;}
.btn-back{
    display:flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:12px;
    background:rgba(255,255,255,.12);color:#fff;
    font-size:12px;font-weight:900;text-decoration:none;
    border:1px solid rgba(255,255,255,.2);
    transition:.18s;white-space:nowrap;flex-shrink:0;
}
.btn-back:hover{background:rgba(255,255,255,.22);}

@media(max-width:600px){
    .preview-bar{padding:8px 12px;gap:8px;}
    .preview-info > div:first-child{display:none;}
    .preview-badge{font-size:11px;padding:4px 10px;}
    .btn-back{padding:6px 10px;font-size:11px;}
    .btn-refresh{width:30px;height:30px;font-size:13px;}
}
.btn-refresh{
    display:flex;align-items:center;justify-content:center;
    width:34px;height:34px;border-radius:10px;
    background:rgba(255,255,255,.1);color:#fff;
    border:1px solid rgba(255,255,255,.15);
    cursor:pointer;font-size:15px;transition:.18s;
    text-decoration:none;
}
.btn-refresh:hover{background:rgba(255,255,255,.22);}

/* ── الـ iframe ── */
.preview-frame{flex:1;width:100%;border:none;background:#fff;}

/* ── شريط الحالة السفلي ── */
.preview-footer{
    padding:5px 20px;
    background:rgba(0,0,0,.4);color:rgba(255,255,255,.5);
    font-size:10px;font-weight:700;
    display:flex;align-items:center;gap:8px;
    flex-shrink:0;
}
.preview-url{direction:ltr;text-align:left;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
</style>
</head>
<body>

<div class="preview-bar">
    <div class="preview-badge">👶 وضع المعاينة</div>
    <div class="preview-info">
        <div>تعرض الصفحة كما يراها الطفل <strong>— <?php echo htmlspecialchars($adminUser) ?></strong></div>
        <div class="preview-warning">⚠️ التقدم والنقاط لن تُسجَّل في هذا الوضع</div>
    </div>
    <?php if ($sectionLabel): ?>
    <div style="background:rgba(255,255,255,.1);color:#fff;padding:5px 12px;border-radius:10px;font-size:12px;font-weight:900;">
        <?php echo htmlspecialchars($sectionLabel) ?>
    </div>
    <?php endif; ?>
    <a href="javascript:document.getElementById('pf').contentWindow.location.reload();" class="btn-refresh" title="تحديث">↺</a>
    <a href="<?php echo htmlspecialchars($dashboardUrl) ?>" class="btn-back">← رجوع للوحة</a>
</div>

<iframe id="pf" src="<?php echo htmlspecialchars($childUrl) ?>" class="preview-frame" allowfullscreen></iframe>

<div class="preview-footer">
    <span>🔗</span>
    <span class="preview-url"><?php echo htmlspecialchars($childUrl) ?></span>
</div>

</body>
</html>
