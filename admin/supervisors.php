<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS supervisors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS supervisor_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supervisor_id INT NOT NULL,
    section VARCHAR(60) NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_add  TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    UNIQUE KEY uniq_sup_sec (supervisor_id, section),
    FOREIGN KEY (supervisor_id) REFERENCES supervisors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM supervisors WHERE id=$did");
    header('Location: supervisors.php'); exit;
}
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE supervisors SET is_active = 1 - is_active WHERE id=$tid");
    header('Location: supervisors.php'); exit;
}

$supervisors = [];
$res = mysqli_query($conn, "SELECT * FROM supervisors ORDER BY created_at DESC");
if ($res) while ($r = mysqli_fetch_assoc($res)) $supervisors[] = $r;

// ── أقسام العرض في الـ chips (أقسام رئيسية + الثقافة العامة كمجموعة) ──
$sections = [
    'arabic_letters'  => ['label'=>'الحروف العربية',     'icon'=>'🔤', 'color'=>'#2a84c9'],
    'english_letters' => ['label'=>'الحروف الإنجليزية',  'icon'=>'🔡', 'color'=>'#28a75d'],
    'arabic_numbers'  => ['label'=>'الأرقام العربية',    'icon'=>'🔢', 'color'=>'#d49a24'],
    'english_numbers' => ['label'=>'الأرقام الإنجليزية', 'icon'=>'🔣', 'color'=>'#7b58d6'],
    'stories'         => ['label'=>'القصص',               'icon'=>'📖', 'color'=>'#e05353'],
    'games'           => ['label'=>'الألعاب',             'icon'=>'🎮', 'color'=>'#00b4b4'],
    // الثقافة العامة — نعرضها كمجموعة واحدة في الـ chips
    'general_culture' => ['label'=>'الثقافة العامة',     'icon'=>'🌿', 'color'=>'#28a75d', 'is_gc_group'=>true],
    'children'        => ['label'=>'الأطفال',             'icon'=>'👦', 'color'=>'#2a84c9'],
    'reports'         => ['label'=>'التقارير',            'icon'=>'📊', 'color'=>'#7b58d6'],
    'chat'            => ['label'=>'المحادثات',           'icon'=>'💬', 'color'=>'#e05353'],
];

// الأقسام الفرعية للثقافة العامة (للحساب فقط)
$gcSubKeys = ['gc_animals','gc_weather','gc_seasons','gc_days','gc_food','gc_islam'];
// + gc_custom_*

$avatarColors = ['#2a84c9','#28a75d','#7b58d6','#d49a24','#e05353','#00b4b4','#ff4f8f'];

/**
 * يحسب مستوى صلاحية الثقافة العامة للعرض في الـ chip:
 * - يجمع كل صلاحيات general_culture + gc_* + gc_custom_*
 */
function calcGCTotal($perms, $gcSubKeys) {
    // ماستر
    $master = $perms['general_culture'] ?? null;
    if ($master && ($master['can_view']+$master['can_add']+$master['can_edit']+$master['can_delete']) === 4) {
        return 4; // كامل
    }
    $total = 0;
    $count = 0;
    $allKeys = array_merge($gcSubKeys, array_filter(array_keys($perms), fn($k) => strpos($k,'gc_custom_')===0));
    foreach ($allKeys as $k) {
        $p = $perms[$k] ?? null;
        if ($p) { $total += ($p['can_view']+$p['can_add']+$p['can_edit']+$p['can_delete']); $count++; }
    }
    if ($master) $total += ($master['can_view']+$master['can_add']+$master['can_edit']+$master['can_delete']);
    return $total;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة المشرفين</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;}
.page-header h1{font-size:24px;font-weight:900;color:#21425f;}
.page-header p{font-size:13px;color:#7a90a3;font-weight:700;margin-top:3px;}
.btn-add{display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2a84c9,#1a5fa0);color:#fff;padding:11px 22px;border-radius:14px;text-decoration:none;font-weight:900;font-size:14px;box-shadow:0 4px 14px rgba(42,132,201,.35);transition:.2s;}
.btn-add:hover{transform:translateY(-1px);}
.stats-bar{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
.stat-mini{background:#fff;border-radius:16px;padding:16px 20px;box-shadow:0 4px 14px rgba(33,66,95,.07);display:flex;align-items:center;gap:14px;}
.stat-mini-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.stat-mini-num{font-size:26px;font-weight:900;color:#21425f;line-height:1;}
.stat-mini-lbl{font-size:12px;font-weight:800;color:#7a90a3;margin-top:2px;}
.sup-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:18px;}
.sup-card{background:#fff;border-radius:20px;box-shadow:0 6px 24px rgba(33,66,95,.08);overflow:hidden;transition:.2s;}
.sup-card:hover{box-shadow:0 10px 32px rgba(33,66,95,.13);transform:translateY(-2px);}
.card-top{padding:20px 20px 16px;display:flex;align-items:center;gap:14px;border-bottom:1px solid #f0f5fb;}
.avatar{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#fff;flex-shrink:0;}
.sup-info{flex:1;}
.sup-name{font-size:16px;font-weight:900;color:#21425f;}
.sup-meta{font-size:12px;color:#7a90a3;font-weight:700;margin-top:2px;}
.status-badge{padding:4px 12px;border-radius:999px;font-size:11px;font-weight:900;flex-shrink:0;}
.badge-on{background:#e8fff0;color:#28a75d;} .badge-off{background:#ffe8e8;color:#e05353;}
.card-perms{padding:14px 20px;}
.perms-title{font-size:11px;font-weight:900;color:#7a90a3;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;}
.perms-wrap{display:flex;flex-wrap:wrap;gap:6px;}
.perm-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:8px;font-size:11px;font-weight:800;}
.chip-full{background:#e8fff0;color:#1a7a40;} .chip-partial{background:#fff6e0;color:#8a5e00;}
.card-actions{padding:12px 16px 16px;display:flex;gap:8px;}
.act-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 0;border-radius:12px;font-size:12px;font-weight:900;text-decoration:none;transition:.18s;cursor:pointer;border:none;}
.btn-edit{background:#f0f7ff;color:#1f5f9a;} .btn-edit:hover{background:#2a84c9;color:#fff;}
.btn-toggle-on{background:#fff6e0;color:#8a5e00;} .btn-toggle-on:hover{background:#d49a24;color:#fff;}
.btn-toggle-off{background:#e8fff0;color:#1a7a40;} .btn-toggle-off:hover{background:#28a75d;color:#fff;}
.btn-del{background:#fff0f0;color:#c0392b;} .btn-del:hover{background:#e05353;color:#fff;}
.empty-state{background:#fff;border-radius:20px;padding:60px 30px;text-align:center;box-shadow:0 6px 24px rgba(33,66,95,.08);}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:20px;padding:30px 28px;max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-icon{font-size:44px;margin-bottom:12px;} .modal-title{font-size:18px;font-weight:900;color:#21425f;margin-bottom:8px;}
.modal-sub{font-size:13px;color:#7a90a3;font-weight:700;margin-bottom:22px;}
.modal-btns{display:flex;gap:10px;}
.modal-confirm{flex:1;padding:11px;border-radius:12px;border:none;font-weight:900;font-size:14px;cursor:pointer;transition:.2s;}
.modal-yes{background:#e05353;color:#fff;} .modal-no{background:#f0f5fb;color:#21425f;}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<div class="page-header">
    <div>
        <h1>👥 إدارة المشرفين</h1>
        <p>تحكم بحسابات المشرفين وصلاحياتهم بالكامل</p>
    </div>
    <a href="add-supervisor.php" class="btn-add"><span style="font-size:18px">+</span> إضافة مشرف</a>
</div>

<?php
$totalSup    = count($supervisors);
$activeSup   = count(array_filter($supervisors, fn($s) => $s['is_active']));
$inactiveSup = $totalSup - $activeSup;
?>
<div class="stats-bar">
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#e8f3ff;">👥</div><div><div class="stat-mini-num"><?php echo $totalSup ?></div><div class="stat-mini-lbl">إجمالي المشرفين</div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#e8fff0;">✅</div><div><div class="stat-mini-num"><?php echo $activeSup ?></div><div class="stat-mini-lbl">مشرف نشط</div></div></div>
    <div class="stat-mini"><div class="stat-mini-icon" style="background:#ffe8e8;">🔴</div><div><div class="stat-mini-num"><?php echo $inactiveSup ?></div><div class="stat-mini-lbl">مشرف معطّل</div></div></div>
</div>

<?php if ($supervisors): ?>
<div class="sup-grid">
<?php foreach ($supervisors as $i => $sup):
    $perms = [];
    $pr = mysqli_query($conn, "SELECT * FROM supervisor_permissions WHERE supervisor_id={$sup['id']}");
    if ($pr) while ($p = mysqli_fetch_assoc($pr)) $perms[$p['section']] = $p;
    $avatarColor = $avatarColors[$i % count($avatarColors)];
    $initial = mb_substr($sup['full_name'], 0, 1, 'UTF-8');
?>
<div class="sup-card">
    <div class="card-top">
        <div class="avatar" style="background:<?php echo $avatarColor ?>"><?php echo $initial ?></div>
        <div class="sup-info">
            <div class="sup-name"><?php echo htmlspecialchars($sup['full_name']) ?></div>
            <div class="sup-meta">@<?php echo htmlspecialchars($sup['username']) ?> &nbsp;·&nbsp; <?php
                $d = time() - strtotime($sup['created_at']);
                echo $d < 86400 ? 'اليوم' : 'منذ ' . intval($d/86400) . ' يوم';
            ?></div>
        </div>
        <span class="status-badge <?php echo $sup['is_active'] ? 'badge-on' : 'badge-off' ?>">
            <?php echo $sup['is_active'] ? '● نشط' : '● معطّل' ?>
        </span>
    </div>

    <div class="card-perms">
        <div class="perms-title">الصلاحيات</div>
        <div class="perms-wrap">
        <?php
        $hasAny = false;
        foreach ($sections as $key => $info):
            if (!empty($info['is_gc_group'])):
                // حساب مجموع صلاحيات الثقافة العامة (ماستر + فرعية)
                $gcTotal = calcGCTotal($perms, $gcSubKeys);
                if ($gcTotal === 0) continue;
                $cls = $gcTotal >= 4 ? 'chip-full' : 'chip-partial';
                $lbl = $gcTotal >= 4 ? 'كامل' : 'جزئي';
                $hasAny = true;
            ?>
            <span class="perm-chip <?php echo $cls ?>">
                <?php echo $info['icon'] ?> <?php echo $info['label'] ?>
                <span style="opacity:.7">(<?php echo $lbl ?>)</span>
            </span>
            <?php
            else:
                $p = $perms[$key] ?? null;
                $total = $p ? ($p['can_view']+$p['can_add']+$p['can_edit']+$p['can_delete']) : 0;
                if ($total === 0) continue;
                $cls = $total === 4 ? 'chip-full' : 'chip-partial';
                $lbl = $total === 4 ? 'كامل' : 'جزئي';
                $hasAny = true;
            ?>
            <span class="perm-chip <?php echo $cls ?>">
                <?php echo $info['icon'] ?> <?php echo $info['label'] ?>
                <span style="opacity:.7">(<?php echo $lbl ?>)</span>
            </span>
            <?php endif; endforeach; ?>
        <?php if (!$hasAny): ?>
        <span style="font-size:12px;color:#a0b0c0;font-weight:800;">لا توجد صلاحيات محددة</span>
        <?php endif; ?>
        </div>
    </div>

    <div class="card-actions">
        <a href="edit-supervisor.php?id=<?php echo $sup['id'] ?>" class="act-btn btn-edit">✏️ تعديل</a>
        <button class="act-btn <?php echo $sup['is_active'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>"
            onclick="confirmToggle(<?php echo $sup['id'] ?>, '<?php echo $sup['is_active'] ? 'تعطيل' : 'تفعيل' ?>', '<?php echo htmlspecialchars($sup['full_name'], ENT_QUOTES) ?>')">
            <?php echo $sup['is_active'] ? '🔴 تعطيل' : '🟢 تفعيل' ?>
        </button>
        <button class="act-btn btn-del"
            onclick="confirmDelete(<?php echo $sup['id'] ?>, '<?php echo htmlspecialchars($sup['full_name'], ENT_QUOTES) ?>')">
            🗑️ حذف
        </button>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div style="font-size:56px;margin-bottom:16px;">👥</div>
    <div style="font-size:20px;font-weight:900;color:#21425f;margin-bottom:8px;">لا يوجد مشرفون بعد</div>
    <div style="font-size:14px;color:#7a90a3;font-weight:700;">اضغط "إضافة مشرف" لإنشاء أول حساب مشرف</div>
</div>
<?php endif; ?>

</main>
</div>

<div class="modal-overlay" id="modalToggle">
    <div class="modal-box">
        <div class="modal-icon" id="toggleIcon">🔄</div>
        <div class="modal-title" id="toggleTitle">تغيير الحالة</div>
        <div class="modal-sub"   id="toggleSub"></div>
        <div class="modal-btns">
            <button class="modal-confirm modal-yes" id="toggleConfirmBtn">تأكيد</button>
            <button class="modal-confirm modal-no" onclick="closeModals()">إلغاء</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="modalDelete">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">حذف المشرف</div>
        <div class="modal-sub" id="deleteSub"></div>
        <div class="modal-btns">
            <button class="modal-confirm modal-yes" id="deleteConfirmBtn">حذف نهائياً</button>
            <button class="modal-confirm modal-no" onclick="closeModals()">إلغاء</button>
        </div>
    </div>
</div>

<script>
function closeModals(){document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('show'));}
function confirmToggle(id,action,name){
    document.getElementById('toggleIcon').textContent=action==='تعطيل'?'🔴':'🟢';
    document.getElementById('toggleTitle').textContent=action+' المشرف';
    document.getElementById('toggleSub').textContent='هل تريد '+action+' حساب "'+name+'"؟';
    document.getElementById('toggleConfirmBtn').onclick=()=>window.location.href='supervisors.php?toggle='+id;
    document.getElementById('modalToggle').classList.add('show');
}
function confirmDelete(id,name){
    document.getElementById('deleteSub').textContent='سيتم حذف حساب "'+name+'" وجميع صلاحياته نهائياً.';
    document.getElementById('deleteConfirmBtn').onclick=()=>window.location.href='supervisors.php?delete='+id;
    document.getElementById('modalDelete').classList.add('show');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModals();}));
</script>
</body>
</html>
