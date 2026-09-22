<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

// ── بناء مصفوفة الأقسام(نفس edit-supervisor.php) ──────────
$sections = [
    /* ── أقسام رئيسية ── */
    'arabic_letters'  => ['label'=>'الحروف العربية',     'icon'=>'🔤','color'=>'#2a84c9','bg'=>'#e6f3ff','group'=>'main'],
    'english_letters' => ['label'=>'الحروف الإنجليزية',  'icon'=>'🔡','color'=>'#28a75d','bg'=>'#e6ffec','group'=>'main'],
    'arabic_numbers'  => ['label'=>'الأرقام العربية',    'icon'=>'🔢','color'=>'#d49a24','bg'=>'#fff6e0','group'=>'main'],
    'english_numbers' => ['label'=>'الأرقام الإنجليزية', 'icon'=>'🔣','color'=>'#7b58d6','bg'=>'#f0ebff','group'=>'main'],
    'stories'         => ['label'=>'القصص',               'icon'=>'📖','color'=>'#e05353','bg'=>'#ffe6f5','group'=>'main'],
    'games'           => ['label'=>'الألعاب',             'icon'=>'🎮','color'=>'#00b4b4','bg'=>'#e0fbfb','group'=>'main'],

    /* ── ماستر الثقافة العامة ── */
    'general_culture' => ['label'=>'الثقافة العامة — كاملة','icon'=>'🌿','color'=>'#28a75d','bg'=>'#c8f5dc','group'=>'gc_master','is_master'=>true,'indent'=>0],

    /* ── أقسام الثقافة العامة ── */
    'gc_weather'      => ['label'=>'الطقس',               'icon'=>'☁️','color'=>'#2a84c9','bg'=>'#e6f3ff','group'=>'gc_sub','indent'=>1],
    'gc_seasons'      => ['label'=>'الفصول الأربعة',      'icon'=>'🌸','color'=>'#28a75d','bg'=>'#e6ffec','group'=>'gc_sub','indent'=>1],
    'gc_days'         => ['label'=>'أيام الأسبوع',         'icon'=>'📅','color'=>'#7b58d6','bg'=>'#f0ebff','group'=>'gc_sub','indent'=>1],
    'gc_islam'        => ['label'=>'أركان الإسلام',        'icon'=>'☪️','color'=>'#1a5fa0','bg'=>'#e6f3ff','group'=>'gc_sub','indent'=>1],
];

// ── الأقسام المخصصة (بعد gc_islam) ────────────────────────
$csRes = mysqli_query($conn, "SELECT id, name, icon FROM general_sections ORDER BY sort_order ASC, id ASC");
if ($csRes) {
    while ($cs = mysqli_fetch_assoc($csRes)) {
        $key = 'gc_custom_' . $cs['id'];
        $sections[$key] = ['label'=>$cs['name'],'icon'=>$cs['icon']?:'★','color'=>'#7b58d6','bg'=>'#f0ebff','group'=>'gc_sub','indent'=>1];
    }
}
$sections['gc_food']           = ['label'=>'فواكه وخضروات',        'icon'=>'🍎','color'=>'#d49a24','bg'=>'#fff6e0','group'=>'gc_sub','is_master'=>true,'indent'=>1];
$sections['gc_food_vegetable'] = ['label'=>'الخضار',                'icon'=>'🥕','color'=>'#28a75d','bg'=>'#e6ffec','group'=>'gc_sub2','indent'=>2];
$sections['gc_food_fruit']     = ['label'=>'الفواكه',               'icon'=>'🍓','color'=>'#e05353','bg'=>'#ffe6f5','group'=>'gc_sub2','indent'=>2];
$sections['gc_animals']        = ['label'=>'الحيوانات',             'icon'=>'🐾','color'=>'#00b4b4','bg'=>'#e0fbfb','group'=>'gc_sub','is_master'=>true,'indent'=>1];
$sections['gc_animals_wild']   = ['label'=>'الحيوانات المفترسة',    'icon'=>'🦁','color'=>'#e05353','bg'=>'#ffe6e6','group'=>'gc_sub2','indent'=>2];
$sections['gc_animals_pet']    = ['label'=>'الحيوانات الأليفة',     'icon'=>'🐮','color'=>'#28a75d','bg'=>'#e6ffec','group'=>'gc_sub2','indent'=>2];

// ── باقي الأقسام ────────────────────────────────────────────
$sections['children'] = ['label'=>'الأطفال',   'icon'=>'👦','color'=>'#2a84c9','bg'=>'#e6f3ff','group'=>'other'];
$sections['reports']  = ['label'=>'التقارير',  'icon'=>'📊','color'=>'#7b58d6','bg'=>'#f0ebff','group'=>'other'];
$sections['chat']     = ['label'=>'المحادثات', 'icon'=>'💬','color'=>'#e05353','bg'=>'#ffe6e6','group'=>'other'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $password  = trim($_POST['password']  ?? '');

    if (!$full_name || !$username || !$password) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM supervisors WHERE username=?");
        mysqli_stmt_bind_param($check, 's', $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'اسم المستخدم مستخدم مسبقاً، اختر اسماً آخر.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = mysqli_prepare($conn, "INSERT INTO supervisors (full_name, username, password_hash) VALUES (?,?,?)");
            mysqli_stmt_bind_param($ins, 'sss', $full_name, $username, $hash);
            mysqli_stmt_execute($ins);
            $newId = mysqli_insert_id($conn);

            foreach ($sections as $key => $s) {
                $isMaster = !empty($s['is_master']);
                $grp      = $s['group'];
                if ($isMaster) {
                    $en = isset($_POST["perm_{$key}"]) ? 1 : 0;
                    $v=$en; $a=$en; $e=$en; $d=$en;
                } elseif ($grp === 'gc_sub') {
                    $v = 0; $a = 0; $d = 0;
                    $e = isset($_POST["perm_{$key}_edit"]) ? 1 : 0;
                } elseif ($key === 'chat') {
                    $v = isset($_POST['perm_chat_view']) ? 1 : 0;
                    $a = isset($_POST['perm_chat_add'])  ? 1 : 0;
                    $e = 0; $d = 0;
                } elseif ($key === 'reports') {
                    $v = isset($_POST['perm_reports_view']) ? 1 : 0;
                    $a = 0; $e = 0; $d = 0;
                } else {
                    $v = isset($_POST["perm_{$key}_view"])   ? 1 : 0;
                    $a = isset($_POST["perm_{$key}_add"])    ? 1 : 0;
                    $e = isset($_POST["perm_{$key}_edit"])   ? 1 : 0;
                    $d = isset($_POST["perm_{$key}_delete"]) ? 1 : 0;
                }
                $ps = mysqli_prepare($conn, "INSERT INTO supervisor_permissions (supervisor_id,section,can_view,can_add,can_edit,can_delete) VALUES (?,?,?,?,?,?)");
                mysqli_stmt_bind_param($ps, 'isiiii', $newId, $key, $v, $a, $e, $d);
                mysqli_stmt_execute($ps);
            }
            header('Location: supervisors.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إضافة مشرف</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#f0f5fb;}
.page-header{display:flex;align-items:center;gap:14px;margin-bottom:28px;}
.page-header-back{width:40px;height:40px;border-radius:12px;background:#fff;box-shadow:0 4px 12px rgba(33,66,95,.1);display:flex;align-items:center;justify-content:center;text-decoration:none;color:#21425f;font-size:18px;transition:.2s;flex-shrink:0;}
.page-header-back:hover{background:#21425f;color:#fff;}
.page-header h1{font-size:22px;font-weight:900;color:#21425f;}
.page-header p{font-size:13px;color:#7a90a3;font-weight:700;margin-top:3px;}
.form-layout{display:grid;grid-template-columns:1fr 1.7fr;gap:20px;align-items:start;}
.panel{background:#fff;border-radius:20px;box-shadow:0 6px 24px rgba(33,66,95,.08);overflow:hidden;}
.panel-head{padding:18px 22px;border-bottom:1px solid #f0f5fb;}
.panel-head h2{font-size:15px;font-weight:900;color:#21425f;}
.panel-body{padding:22px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:12px;font-weight:900;color:#21425f;margin-bottom:8px;}
.field-wrap{position:relative;}
.field-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:16px;pointer-events:none;}
.field input{width:100%;padding:12px 40px 12px 14px;border:2px solid #e8eef5;border-radius:12px;font-size:14px;font-family:Arial;color:#21425f;outline:none;transition:.2s;background:#fafcff;}
.field input:focus{border-color:#2a84c9;background:#fff;box-shadow:0 0 0 3px rgba(42,132,201,.1);}
.field-hint{font-size:11px;color:#7a90a3;font-weight:700;margin-top:5px;}
.avatar-preview{width:70px;height:70px;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:900;color:#fff;margin:0 auto 18px;background:linear-gradient(135deg,#2a84c9,#7b58d6);transition:.3s;}
.perm-controls{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.perm-ctrl-btn{padding:7px 14px;border-radius:10px;border:none;font-size:12px;font-weight:900;cursor:pointer;transition:.18s;}
.ctrl-all{background:#21425f;color:#fff;} .ctrl-view{background:#e8f3ff;color:#1f5f9a;} .ctrl-none{background:#f0f5fb;color:#7a90a3;}
.perm-table{width:100%;border-collapse:collapse;}
.perm-table thead tr{background:#f7fbff;}
.perm-table thead th{padding:10px 12px;font-size:11px;font-weight:900;color:#7a90a3;text-align:center;border-bottom:2px solid #f0f5fb;}
.perm-table thead th:first-child{text-align:right;}
.perm-table tbody tr{border-bottom:1px solid #f7fafd;}
.perm-table tbody tr:hover{background:#f7fbff;}
.perm-table td{padding:10px 12px;}
.group-divider td{background:#f0f5fb;padding:8px 14px;font-size:11px;font-weight:900;color:#7a90a3;letter-spacing:1px;border:none !important;}
.master-row{background:#f0fff6 !important;}
.master-badge{display:inline-block;background:#28a75d;color:#fff;font-size:9px;font-weight:900;padding:2px 7px;border-radius:6px;margin-right:6px;vertical-align:middle;}
.master-switch-label{display:inline-flex;align-items:center;gap:10px;cursor:pointer;padding:8px 14px;border-radius:12px;border:2px solid #ccc;transition:.2s;user-select:none;width:100%;}
.master-switch-label.master-on{background:#e8fff0;border-color:#28a75d;}
.master-switch-label.master-off{background:#f5f5f5;border-color:#ddd;}
.master-switch-input{display:none;}
.master-switch-track{position:relative;width:44px;height:24px;border-radius:999px;background:#ccc;transition:.2s;flex-shrink:0;}
.master-on .master-switch-track{background:#28a75d;}
.master-switch-thumb{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.25);transition:.2s;}
.master-on .master-switch-thumb{transform:translateX(20px);}
.master-switch-text{font-size:12px;font-weight:900;color:#21425f;flex:1;}
.master-on .master-switch-text{color:#1a7a40;}
.sec-badge{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:10px;font-size:13px;font-weight:800;}
.toggle-wrap{display:flex;justify-content:center;}
.toggle{position:relative;width:36px;height:20px;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:#dce7f2;border-radius:999px;cursor:pointer;transition:.2s;}
.toggle-slider::before{content:'';position:absolute;width:14px;height:14px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
.toggle input:checked+.toggle-slider{background:#2a84c9;}
.toggle input:checked+.toggle-slider::before{transform:translateX(16px);}
.toggle input.perm-add:checked+.toggle-slider{background:#28a75d;}
.toggle input.perm-edit:checked+.toggle-slider{background:#d49a24;}
.toggle input.perm-delete:checked+.toggle-slider{background:#e05353;}
.toggle input:disabled+.toggle-slider{opacity:.4;cursor:not-allowed;}
.master-note{font-size:11px;color:#28a75d;font-weight:800;margin-top:6px;padding:6px 10px;background:#e6ffec;border-radius:8px;border:1px solid #b8f0cc;}
.error-box{background:#ffe8e8;border:1px solid #f5c6c6;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-size:13px;font-weight:800;color:#c0392b;}
.form-footer{display:flex;gap:12px;margin-top:20px;}
.btn-submit{flex:1;padding:14px;border-radius:14px;border:none;background:linear-gradient(135deg,#28a75d,#1a7a40);color:#fff;font-size:15px;font-weight:900;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-submit:hover{transform:translateY(-1px);}
.btn-cancel{padding:14px 24px;border-radius:14px;background:#f0f5fb;color:#21425f;font-size:14px;font-weight:900;text-decoration:none;display:flex;align-items:center;justify-content:center;}
@media(max-width:900px){.form-layout{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<div class="page-header">
    <a href="supervisors.php" class="page-header-back">←</a>
    <div>
        <h1>➕ إضافة مشرف جديد</h1>
        <p>أنشئ حساب مشرف وحدد صلاحياته</p>
    </div>
</div>

<form method="POST">
<div class="form-layout">

    <div>
        <div class="panel">
            <div class="panel-head"><h2>👤 بيانات الحساب</h2></div>
            <div class="panel-body">
                <?php if ($error): ?>
                <div class="error-box">⚠️ <?php echo htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="avatar-preview" id="avatarPreview">؟</div>

                <div class="field">
                    <label>الاسم الكامل *</label>
                    <div class="field-wrap">
                        <span class="field-icon">👤</span>
                        <input type="text" name="full_name" id="fullName"
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label>اسم المستخدم *</label>
                    <div class="field-wrap">
                        <span class="field-icon">@</span>
                        <input type="text" name="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="field-hint">يُستخدم لتسجيل الدخول — لا يمكن تكراره</div>
                </div>
                <div class="field">
                    <label>كلمة المرور *</label>
                    <div class="field-wrap">
                        <span class="field-icon">🔑</span>
                        <input type="password" name="password" id="passField" placeholder="6 أحرف على الأقل" required>
                    </div>
                    <div class="field-hint" id="passHint">6 أحرف على الأقل</div>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn-submit">✅ إنشاء الحساب</button>
            <a href="supervisors.php" class="btn-cancel">إلغاء</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>🔐 الصلاحيات</h2></div>
        <div class="panel-body">
            <div class="perm-controls">
                <button type="button" class="perm-ctrl-btn ctrl-all"  onclick="setAll(1,1,1,1)">✅ تفعيل الكل</button>
                <button type="button" class="perm-ctrl-btn ctrl-view" onclick="setAll(1,0,0,0)">👁️ عرض فقط</button>
                <button type="button" class="perm-ctrl-btn ctrl-none" onclick="setAll(0,0,0,0)">❌ إلغاء الكل</button>
            </div>
            <div class="master-note">
                💡 الماستر <strong>🌿 الثقافة العامة</strong> يشمل كل أقسامها — زر واحد يفعّل الكل.<br>
                ماستر <strong>🐾 الحيوانات</strong> يشمل المفترسة والأليفة، وماستر <strong>🍎 فواكه وخضروات</strong> يشمل الاثنين.
            </div><br>

            <table class="perm-table">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>👁️<br><small>عرض</small></th>
                        <th>➕<br><small>إضافة</small></th>
                        <th>✏️<br><small>تعديل</small></th>
                        <th>🗑️<br><small>حذف</small></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $lastGroup = '';
                $groupLabels = [
                    'main'      => 'الأقسام الرئيسية',
                    'gc_master' => 'الثقافة العامة',
                    'gc_sub'    => '',
                    'gc_sub2'   => '',
                    'other'     => 'أقسام أخرى',
                ];
                foreach ($sections as $key => $s):
                    $group    = $s['group'];
                    $isMaster = !empty($s['is_master']);
                    $indent   = $s['indent'] ?? 0;

                    if ($group !== $lastGroup && isset($groupLabels[$group]) && $groupLabels[$group] !== ''):
                        $lastGroup = $group;
                ?>
                <tr class="group-divider"><td colspan="5"><?php echo $groupLabels[$group] ?></td></tr>
                <?php endif;
                    $posted = isset($_POST['full_name']);
                    if ($isMaster) {
                        $en = $posted && isset($_POST["perm_{$key}"]) ? 1 : 0;
                    } elseif ($group === 'gc_sub') {
                        $ce = $posted && isset($_POST["perm_{$key}_edit"]) ? 1 : 0;
                    } elseif ($key === 'chat') {
                        $cv = $posted && isset($_POST['perm_chat_view']) ? 1 : 0;
                        $ca = $posted && isset($_POST['perm_chat_add'])  ? 1 : 0;
                    } elseif ($key === 'reports') {
                        $cv = $posted && isset($_POST['perm_reports_view']) ? 1 : 0;
                    } else {
                        $cv = $posted && isset($_POST["perm_{$key}_view"])   ? 1 : 0;
                        $ca = $posted && isset($_POST["perm_{$key}_add"])    ? 1 : 0;
                        $ce = $posted && isset($_POST["perm_{$key}_edit"])   ? 1 : 0;
                        $cd = $posted && isset($_POST["perm_{$key}_delete"]) ? 1 : 0;
                    }
                ?>
                <?php if ($isMaster): ?>
                <tr class="master-row">
                    <td class="indent-<?php echo $indent ?>">
                        <span class="master-badge">ماستر</span>
                        <span class="sec-badge" style="background:<?php echo $s['bg'] ?>;color:<?php echo $s['color'] ?>">
                            <?php echo $s['icon'] ?> <?php echo $s['label'] ?>
                        </span>
                    </td>
                    <td colspan="4">
                        <label class="master-switch-label <?php echo $en ? 'master-on' : 'master-off' ?>">
                            <input type="checkbox" name="perm_<?php echo $key ?>"
                                   class="perm-cb perm-master master-switch-input"
                                   <?php echo $en ? 'checked' : '' ?>>
                            <span class="master-switch-track">
                                <span class="master-switch-thumb"></span>
                            </span>
                            <span class="master-switch-text">
                                <?php echo $en ? '✅ مفعّل — كامل الصلاحيات' : '⭕ معطّل' ?>
                            </span>
                        </label>
                    </td>
                </tr>
                <?php elseif ($group === 'gc_sub'): ?>
                <tr>
                    <td class="indent-<?php echo $indent ?>">
                        <span class="sec-badge" style="background:<?php echo $s['bg'] ?>;color:<?php echo $s['color'] ?>">
                            <?php echo $s['icon'] ?> <?php echo $s['label'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                    <td>
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox"
                                       name="perm_<?php echo $key ?>_edit"
                                       class="perm-cb perm-edit"
                                       <?php echo $ce ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                </tr>
                <?php elseif ($key === 'chat'): ?>
                <tr>
                    <td><span class="sec-badge" style="background:<?php echo $s['bg'] ?>;color:<?php echo $s['color'] ?>"><?php echo $s['icon'] ?> <?php echo $s['label'] ?></span></td>
                    <td><div class="toggle-wrap"><label class="toggle"><input type="checkbox" name="perm_chat_view" class="perm-cb perm-view" data-key="chat" <?php echo $cv?'checked':''?>><span class="toggle-slider"></span></label></div></td>
                    <td>
                        <div class="toggle-wrap"><label class="toggle"><input type="checkbox" name="perm_chat_add" class="perm-cb perm-add" <?php echo $ca?'checked':''?> <?php echo !$cv?'disabled':''?>><span class="toggle-slider"></span></label></div>
                        <div style="font-size:9px;color:#7a90a3;text-align:center;font-weight:800;">تواصل</div>
                    </td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                </tr>
                <?php elseif ($key === 'reports'): ?>
                <tr>
                    <td><span class="sec-badge" style="background:<?php echo $s['bg'] ?>;color:<?php echo $s['color'] ?>"><?php echo $s['icon'] ?> <?php echo $s['label'] ?></span></td>
                    <td><div class="toggle-wrap"><label class="toggle"><input type="checkbox" name="perm_reports_view" class="perm-cb perm-view" data-key="reports" <?php echo $cv?'checked':''?>><span class="toggle-slider"></span></label></div></td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                    <td style="text-align:center;color:#ccc;font-size:18px;">—</td>
                </tr>
                <?php else: ?>
                <tr>
                    <td class="indent-<?php echo $indent ?>">
                        <span class="sec-badge" style="background:<?php echo $s['bg'] ?>;color:<?php echo $s['color'] ?>">
                            <?php echo $s['icon'] ?> <?php echo $s['label'] ?>
                        </span>
                    </td>
                    <?php foreach (['view'=>['perm-view',$cv],'add'=>['perm-add',$ca],'edit'=>['perm-edit',$ce],'delete'=>['perm-delete',$cd]] as $act => [$cls,$checked]): ?>
                    <td>
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox"
                                       name="perm_<?php echo $key ?>_<?php echo $act ?>"
                                       class="perm-cb <?php echo $cls ?>"
                                       <?php echo $checked ? 'checked' : '' ?>
                                       <?php echo ($act !== 'view' && !$cv) ? 'disabled' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</form>
</main>
</div>

<script>
const nameInput = document.getElementById('fullName');
const avatar    = document.getElementById('avatarPreview');
const colors    = ['#2a84c9','#28a75d','#7b58d6','#d49a24','#e05353','#00b4b4'];
nameInput.addEventListener('input', function() {
    const v = this.value.trim();
    avatar.textContent = v ? v.charAt(0) : '؟';
    avatar.style.background = v
        ? `linear-gradient(135deg,${colors[v.charCodeAt(0)%colors.length]},${colors[(v.charCodeAt(0)+2)%colors.length]})`
        : 'linear-gradient(135deg,#2a84c9,#7b58d6)';
});
const passInput = document.getElementById('passField');
const passHint  = document.getElementById('passHint');
passInput.addEventListener('input', function() {
    const l = this.value.length;
    if (!l)       { passHint.textContent='6 أحرف على الأقل';                    passHint.style.color='#7a90a3'; }
    else if (l<6) { passHint.textContent='⚠️ قصيرة جداً';                       passHint.style.color='#e05353'; }
    else if (l<10){ passHint.textContent='🟡 مقبولة';                           passHint.style.color='#d49a24'; }
    else          { passHint.textContent='✅ قوية';                             passHint.style.color='#28a75d'; }
});
document.querySelectorAll('.perm-view').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const row = this.closest('tr');
        ['perm-add','perm-edit','perm-delete'].forEach(function(cls) {
            const other = row.querySelector('.'+cls);
            if (other) { if (!cb.checked) other.checked=false; other.disabled=!cb.checked; }
        });
    });
});
function updateMasterLabel(input) {
    const label = input.closest('.master-switch-label');
    const textEl = label.querySelector('.master-switch-text');
    if (input.checked) {
        label.classList.replace('master-off','master-on');
        textEl.textContent = '✅ مفعّل — كامل الصلاحيات';
    } else {
        label.classList.replace('master-on','master-off');
        textEl.textContent = '⭕ معطّل';
    }
}
document.querySelectorAll('.master-switch-input').forEach(function(cb) {
    cb.addEventListener('change', function() { updateMasterLabel(this); });
});
function setAll(v,a,e,d) {
    document.querySelectorAll('.perm-view').forEach(c=>{c.checked=!!v;c.dispatchEvent(new Event('change'));});
    document.querySelectorAll('.master-switch-input').forEach(c=>{c.checked=!!v;updateMasterLabel(c);});
    document.querySelectorAll('.perm-edit').forEach(c=>{if(!c.disabled)c.checked=!!e;});
    if (v) {
        document.querySelectorAll('.perm-add').forEach(c=>{if(!c.disabled)c.checked=!!a;});
        document.querySelectorAll('.perm-delete').forEach(c=>{if(!c.disabled)c.checked=!!d;});
    }
}
</script>
</body>
</html>
