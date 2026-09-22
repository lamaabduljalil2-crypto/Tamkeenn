<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$toast = ''; $toastType = '';

/* ── دالة رفع الملف ── */
function uploadGameFile(string $inputName, string $subDir, array $allowedMime): string {
    if (empty($_FILES[$inputName]['name'])) return '';
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMime)) return '';
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('', true) . '.' . strtolower($ext);
    $dir  = "../uploads/$subDir/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return '';
    return "uploads/$subDir/$name";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = trim($_POST['act'] ?? '');

    /* ── إضافة / تعديل ── */
    if (($act === 'add' || $act === 'edit') && supCan('games', $act === 'add' ? 'can_add' : 'can_edit')) {
        $title    = trim($_POST['title']          ?? '');
        $title_en = trim($_POST['title_en']       ?? '');
        $desc     = trim($_POST['description']    ?? '');
        $desc_en  = trim($_POST['description_en'] ?? '');
        $type     = trim($_POST['game_type']      ?? '');
        $active   = isset($_POST['is_active']) ? 1 : 0;
        $sort     = (int)($_POST['sort_order'] ?? 0);

        $thumbMime = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm'];
        $newThumb  = uploadGameFile('thumbnail_file', 'games/thumbs', $thumbMime);
        $thumb     = $newThumb ?: trim($_POST['thumbnail_current'] ?? '');

       $gameMime = [
    'text/html',
    'application/zip',
    'application/x-zip-compressed',
    'application/javascript',
    'application/octet-stream',
    'text/x-php',
    'application/x-httpd-php'
];
        $newUrl   = uploadGameFile('game_file', 'games/files', $gameMime);
        $url      = $newUrl ?: trim($_POST['url_current'] ?? '');

        if (!$title) {
            $toast = 'يرجى إدخال اسم اللعبة'; $toastType = 'error';
        } elseif ($act === 'add') {
            $stmt = $conn->prepare("INSERT INTO games(title,title_en,description,description_en,game_type,thumbnail,url,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssii', $title,$title_en,$desc,$desc_en,$type,$thumb,$url,$active,$sort);
            $stmt->execute();
            $toast = 'تمت الإضافة ✅'; $toastType = 'success';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE games SET title=?,title_en=?,description=?,description_en=?,game_type=?,thumbnail=?,url=?,is_active=?,sort_order=? WHERE id=?");
            $stmt->bind_param('sssssssiii', $title,$title_en,$desc,$desc_en,$type,$thumb,$url,$active,$sort,$id);
            $stmt->execute();
            $toast = 'تم التحديث ✅'; $toastType = 'success';
        }
    }

    /* ── حفظ الإعدادات ── */
    if ($act === 'save_settings') {
        $id = (int)($_POST['id'] ?? 0);

        /* جلب الإعدادات الحالية للحفاظ على القيم غير المرسلة */
        $cur_res = $conn->query("SELECT game_settings FROM games WHERE id=$id");
        $cur_row = $cur_res ? $cur_res->fetch_assoc() : [];
        $settings = json_decode($cur_row['game_settings'] ?? '{}', true) ?? [];

        /* تحديث قيم POST */
        foreach ($_POST as $k => $v) {
            if (str_starts_with($k, 'setting_')) {
                $key = substr($k, 8);
                /* لا تمسح مسار الصورة إذا كان فارغاً وفيه ملف مرفوع */
                if ($v !== '' || empty($_FILES['pair_img_' . ltrim($key, 'pair_') . '_image']['name'])) {
                    $settings[$key] = is_numeric($v) ? $v + 0 : $v;
                }
            }
        }

        /* رفع صور الأزواج (pair_img_1 … pair_img_8) */
        $imgMime = ['image/jpeg','image/png','image/gif','image/webp'];
        for ($pi = 1; $pi <= 8; $pi++) {
            $fKey = 'pair_img_' . $pi;
            if (!empty($_FILES[$fKey]['name']) && $_FILES[$fKey]['error'] === UPLOAD_ERR_OK) {
                $uploaded = uploadGameFile($fKey, 'games/memory', $imgMime);
                if ($uploaded) $settings['pair_' . $pi . '_image'] = $uploaded;
            }
        }

        $json = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE games SET game_settings=? WHERE id=?");
        $stmt->bind_param('si', $json, $id);
        $stmt->execute();
        $toast = 'تم حفظ الإعدادات ✅'; $toastType = 'success';
    }

    /* ── حذف ── */
    if ($act === 'delete' && supCan('games', 'can_delete')) {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM games WHERE id=$id");
        $toast = 'تم الحذف'; $toastType = 'success';
    }

    /* ── تفعيل / إيقاف ── */
    if ($act === 'toggle' && supCan('games', 'can_edit')) {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("UPDATE games SET is_active=1-is_active WHERE id=$id");
        $toast = 'تم تغيير الحالة'; $toastType = 'success';
    }
}

/* ── جلب الألعاب ── */
$games = [];
$res = $conn->query("SELECT * FROM games ORDER BY sort_order ASC, id ASC");
if ($res) while ($r = $res->fetch_assoc()) $games[] = $r;

$typeLabels = ['memory'=>'ذاكرة','cups'=>'أكواب','chase'=>'مطاردة','colors'=>'ألوان','numbers'=>'أعداد','other'=>'أخرى'];
$typeEmoji  = ['memory'=>'🃏','cups'=>'🏆','chase'=>'🐭','colors'=>'🌈','numbers'=>'🔢','other'=>'🎮'];

function adminThumbSrc(string $path): string {
    if (empty($path)) return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    if (str_starts_with($path, '/')) return $path;
    if (str_starts_with($path, '../') || str_starts_with($path, './')) return $path;
    return '../' . $path;
}

function settingsChips($type, $settings) {
    $chips = [];
    if ($type === 'memory') {
        if (isset($settings['pairs'])) $chips[] = ['⚙️', $settings['pairs'] . ' أزواج'];
        // عدّ صور الأزواج المخصصة
        $pairImgCount = 0;
        for ($i = 1; $i <= 8; $i++) {
            if (!empty($settings['pair_' . $i . '_image'])) $pairImgCount++;
        }
        if ($pairImgCount > 0)             $chips[] = ['🖼️', $pairImgCount . ' صور مخصصة'];
        elseif (!empty($settings['card_image'])) $chips[] = ['🐾', 'صورة مخصصة'];
    }
    if ($type === 'cups') {
        if (isset($settings['cups']))       $chips[] = ['🏆', $settings['cups']  . ' أكواب'];
        if (isset($settings['swaps']))      $chips[] = ['🔀', $settings['swaps'] . ' تبديلات'];
        $speeds = [1000=>'بطيء',750=>'متوسط',500=>'سريع',300=>'سريع جداً'];
        if (isset($settings['swap_speed'])) $chips[] = ['⚡', $speeds[$settings['swap_speed']] ?? $settings['swap_speed']];
    }
    if ($type === 'chase') {
        if (isset($settings['lives']))         $chips[] = ['❤️', $settings['lives'] . ' أرواح'];
        $sp = [4=>'بطيء',5=>'متوسط',7=>'سريع'];
        if (isset($settings['player_speed']))  $chips[] = ['🐭', $sp[$settings['player_speed']] ?? ''];
        $cs = [1=>'بطيء',2=>'متوسط',3=>'سريع'];
        if (isset($settings['cat_speed']))     $chips[] = ['🐱', $cs[$settings['cat_speed']] ?? ''];
    }
    if (in_array($type, ['colors','numbers']) && isset($settings['rounds']))  $chips[] = ['🔄', $settings['rounds'] . ' جولات'];
    if ($type === 'numbers' && isset($settings['max_num'])) $chips[] = ['🔢', 'حتى ' . $settings['max_num']];
    return $chips;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة الألعاب</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
/* ── Toast ── */
.toast{position:fixed;top:22px;left:50%;transform:translateX(-50%) translateY(-80px);padding:13px 28px;border-radius:14px;font-size:16px;font-weight:700;z-index:9999;transition:.35s ease;box-shadow:0 10px 28px rgba(0,0,0,.14);pointer-events:none;}
.toast.show{transform:translateX(-50%) translateY(0);}
.toast.success{background:#22c55e;color:#fff;}
.toast.error{background:#ef4444;color:#fff;}

/* ── Top bar ── */
.games-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.games-topbar h1{margin:0;font-size:26px;}
.btn-add{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6c63ff,#a78bfa);color:#fff;border:none;border-radius:12px;padding:11px 22px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:0 6px 16px rgba(108,99,255,.30);transition:.25s ease;}
.btn-add:hover{transform:translateY(-2px);filter:brightness(1.06);}

/* ── CARDS GRID ── */
.games-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:24px;align-items:stretch;}

.game-admin-card{display:flex;flex-direction:column;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,.08);transition:.3s ease;position:relative;}
.game-admin-card:hover{transform:translateY(-6px);box-shadow:0 14px 32px rgba(0,0,0,.13);}
.game-admin-card.inactive{opacity:.55;}

.card-image{width:100%;height:200px;overflow:hidden;position:relative;background-size:cover;background-position:center;}
.card-image::before{content:'';position:absolute;inset:-10px;background:inherit;filter:blur(14px) brightness(.80);transform:scale(1.08);z-index:0;}
.card-image img,.card-image video{width:100%;height:100%;object-fit:contain;display:block;position:relative;z-index:1;transition:.3s ease;}
.game-admin-card:hover .card-image img,.game-admin-card:hover .card-image video{transform:scale(1.04);}
.card-emoji-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:72px;background:linear-gradient(135deg,#e0e7ff,#f3e8ff);}

.card-badges{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 14px 0;}
.type-badge{background:#ede9fe;color:#7c3aed;border-radius:8px;padding:4px 12px;font-size:13px;font-weight:700;}
.status-badge{border-radius:8px;padding:4px 12px;font-size:13px;font-weight:700;}
.status-badge.active{background:#dcfce7;color:#16a34a;}
.status-badge.inactive{background:#fee2e2;color:#dc2626;}

.card-body{padding:8px 14px 12px;flex:1;display:flex;flex-direction:column;gap:6px;}
.card-title{font-size:18px;font-weight:800;color:#1f2937;margin:0;}
.card-title-en{font-size:13px;color:#9ca3af;font-weight:600;margin:0;}
.card-desc{font-size:14px;color:#6b7280;line-height:1.65;flex:1;margin:0;}

.settings-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;}
.s-chip{background:#f3f4f6;border-radius:8px;padding:3px 10px;font-size:12px;font-weight:700;color:#374151;display:flex;align-items:center;gap:4px;}

.card-actions{display:flex;border-top:1px solid #f3f4f6;}
.card-actions button,.card-actions a{flex:1;border:none;background:transparent;padding:11px 4px;cursor:pointer;font-size:12px;font-weight:700;color:#374151;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:4px;text-decoration:none;transition:.2s ease;}
.card-actions button:hover,.card-actions a:hover{background:#f9fafb;}
.btn-edit{color:#2563eb;}
.btn-settings{color:#7c3aed;}
.btn-toggle{color:#d97706;}
.btn-delete{color:#dc2626;}
.sep{width:1px;background:#f3f4f6;align-self:stretch;}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:.25s ease;}
.modal-overlay.open{opacity:1;visibility:visible;}
.modal-box{background:#fff;border-radius:24px;width:min(680px,95vw);max-height:90vh;overflow-y:auto;padding:28px;box-shadow:0 20px 50px rgba(0,0,0,.22);transform:scale(.92);transition:.25s ease;}
.modal-overlay.open .modal-box{transform:scale(1);}
.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
.modal-head h2{margin:0;font-size:21px;color:#1f2937;}
.modal-close{border:none;background:#f3f4f6;border-radius:10px;width:36px;height:36px;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#374151;transition:.2s;}
.modal-close:hover{background:#e5e7eb;}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-grid .full{grid-column:1/-1;}
.form-group{display:flex;flex-direction:column;gap:5px;}
.form-group label{font-size:14px;font-weight:700;color:#374151;}
.form-group input,.form-group select,.form-group textarea{border:2px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:14px;font-family:inherit;color:#1f2937;outline:none;transition:.2s;background:#fff;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.12);}
.form-group textarea{resize:vertical;min-height:72px;}
.form-check{display:flex;align-items:center;gap:10px;cursor:pointer;font-size:15px;font-weight:700;color:#374151;}
.form-check input[type=checkbox]{width:20px;height:20px;accent-color:#6c63ff;cursor:pointer;}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px;padding-top:18px;border-top:1px solid #f3f4f6;}
.btn-save{background:linear-gradient(135deg,#6c63ff,#a78bfa);color:#fff;border:none;border-radius:12px;padding:11px 30px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:.25s;}
.btn-save:hover{filter:brightness(1.06);}
.btn-cancel{background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:11px 22px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
.btn-cancel:hover{background:#e5e7eb;}

/* ── Upload Box ── */
.upload-box{border:2px dashed #d1d5db;border-radius:14px;padding:22px 16px;cursor:pointer;transition:.2s;background:#f9fafb;text-align:center;}
.upload-box:hover{border-color:#6c63ff;background:#f3f0ff;}
.upload-box.has-file{border-color:#6c63ff;border-style:solid;background:#faf8ff;}
.upload-preview{display:flex;flex-direction:column;align-items:center;gap:6px;pointer-events:none;}
.upload-preview img,.upload-preview video{max-height:130px;max-width:100%;border-radius:10px;object-fit:contain;margin-bottom:4px;}
.upload-icon{font-size:38px;line-height:1;}
.upload-hint{font-size:14px;font-weight:700;color:#374151;}
.upload-sub{font-size:12px;color:#9ca3af;}
.upload-filename{font-size:13px;font-weight:700;color:#6c63ff;word-break:break-all;}
.upload-change{font-size:12px;color:#9ca3af;margin-top:2px;}

/* ── Settings modal ── */
.settings-field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.settings-field label{font-size:14px;font-weight:700;color:#374151;}
.settings-field select,.settings-field input[type=number]{border:2px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:15px;font-family:inherit;outline:none;transition:.2s;background:#fff;width:100%;}
.settings-field select:focus,.settings-field input[type=number]:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12);}
.settings-divider{font-size:13px;color:#9ca3af;font-weight:700;border-bottom:1px solid #f3f4f6;padding-bottom:8px;margin-bottom:14px;}
.no-settings{text-align:center;color:#9ca3af;font-size:15px;font-weight:700;padding:24px 0;}

/* ── Image picker ── */
.img-field-wrap{display:flex;flex-direction:column;gap:8px;}
.img-field-wrap input[type=text]{border:2px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:14px;font-family:inherit;outline:none;transition:.2s;background:#fff;width:100%;box-sizing:border-box;}
.img-field-wrap input[type=text]:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12);}
.img-preview-box{width:100%;height:110px;border-radius:14px;border:2px dashed #e5e7eb;display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden;transition:.2s;}
.img-preview-box img{max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;}
.img-preview-box .ph{font-size:38px;color:#d1d5db;}
.img-presets-label{font-size:13px;color:#9ca3af;font-weight:700;}
.img-preset-row{display:flex;flex-wrap:wrap;gap:6px;}
.img-preset-btn{width:48px;height:48px;border-radius:10px;border:2px solid #e5e7eb;background:#f9fafb;cursor:pointer;overflow:hidden;padding:0;transition:.2s;display:flex;align-items:center;justify-content:center;font-size:22px;}
.img-preset-btn:hover{border-color:#7c3aed;background:#f3e8ff;}
.img-preset-btn.selected{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.20);}
.img-preset-btn img{width:100%;height:100%;object-fit:contain;}

/* ── Pair images grid ── */
.pair-images-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:4px;}
.pair-img-card{background:#f9fafb;border-radius:14px;padding:12px;border:2px solid #e5e7eb;display:flex;flex-direction:column;gap:8px;}
.pair-img-card-label{font-size:13px;font-weight:800;color:#6c63ff;text-align:center;}

/* Empty */
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state .em{font-size:60px;margin-bottom:12px;}
.empty-state p{font-size:18px;font-weight:700;}

@media(max-width:600px){.form-grid{grid-template-columns:1fr;}.form-grid .full{grid-column:1;}.pair-images-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>
<main class="admin-content">

<?php if ($toast): ?>
<div class="toast <?php echo $toastType; ?>" id="pageToast"><?php echo htmlspecialchars($toast); ?></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const t=document.getElementById('pageToast');if(t){t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3500);}});</script>
<?php endif; ?>

<div class="games-topbar">
    <h1 class="page-title" style="margin:0">إدارة الألعاب</h1>
    <?php if (supCan('games', 'can_add')): ?>
    <button class="btn-add" onclick="openModal()">＋ إضافة لعبة</button>
    <?php endif; ?>
</div>

<?php if (empty($games)): ?>
<div class="empty-state"><div class="em">🎮</div><p>لا توجد ألعاب — أضف أول لعبة!</p></div>
<?php else: ?>

<div class="games-grid">
<?php foreach ($games as $g):
    $typeLabel = $typeLabels[$g['game_type']] ?? $g['game_type'];
    $emoji     = $typeEmoji[$g['game_type']] ?? '🎮';
    $settings  = json_decode($g['game_settings'] ?? '{}', true) ?? [];
    $chips     = settingsChips($g['game_type'], $settings);
    $hasThumb  = !empty($g['thumbnail']);
    $isVideo   = $hasThumb && preg_match('/\.(mp4|webm)$/i', $g['thumbnail']);
?>
<div class="game-admin-card <?php echo $g['is_active'] ? '' : 'inactive'; ?>">

    <?php
        $thumbSrc = $hasThumb ? htmlspecialchars(adminThumbSrc($g['thumbnail'])) : '';
        $bgStyle  = ($hasThumb && !$isVideo) ? ' style="background-image:url(\'' . $thumbSrc . '\')"' : '';
    ?>
    <div class="card-image"<?php echo $bgStyle; ?>>
        <?php if ($hasThumb && $isVideo): ?>
            <video src="<?php echo $thumbSrc; ?>" autoplay muted loop playsinline></video>
        <?php elseif ($hasThumb): ?>
            <img src="<?php echo $thumbSrc; ?>" alt="">
        <?php else: ?>
            <div class="card-emoji-ph"><?php echo $emoji; ?></div>
        <?php endif; ?>
    </div>

    <div class="card-badges">
        <span class="type-badge"><?php echo htmlspecialchars($typeLabel); ?></span>
        <span class="status-badge <?php echo $g['is_active'] ? 'active' : 'inactive'; ?>">
            <?php echo $g['is_active'] ? 'مفعّلة' : 'مخفية'; ?>
        </span>
    </div>

    <div class="card-body">
        <h2 class="card-title"><?php echo htmlspecialchars($g['title']); ?></h2>
        <?php if ($g['title_en']): ?>
            <p class="card-title-en"><?php echo htmlspecialchars($g['title_en']); ?></p>
        <?php endif; ?>
        <?php if ($g['description']): ?>
            <p class="card-desc"><?php echo htmlspecialchars($g['description']); ?></p>
        <?php endif; ?>
        <?php if (!empty($chips)): ?>
        <div class="settings-chips">
            <?php foreach ($chips as [$ic, $lbl]): ?>
                <span class="s-chip"><?php echo $ic; ?> <?php echo htmlspecialchars($lbl); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-actions">
        <?php if (supCan('games','can_edit')): ?>
        <button class="btn-edit" onclick='openModal(<?php echo json_encode($g, JSON_UNESCAPED_UNICODE); ?>)'>✏️ تعديل</button>
        <div class="sep"></div>
        <button class="btn-settings" onclick='openSettings(<?php echo json_encode($g, JSON_UNESCAPED_UNICODE); ?>)'>⚙️ إعدادات</button>
        <div class="sep"></div>
        <form method="POST" style="flex:1;display:flex;">
            <input type="hidden" name="act" value="toggle">
            <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
            <button type="submit" class="btn-toggle" style="width:100%;">
                <?php echo $g['is_active'] ? '🙈 إيقاف' : '👁 تفعيل'; ?>
            </button>
        </form>
        <div class="sep"></div>
        <form method="POST" style="flex:1;display:flex;" onsubmit="return confirm('حذف هذه اللعبة؟')">
            <?php if (supCan('games','can_delete')): ?>
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
            <button type="submit" class="btn-delete" style="width:100%;">🗑 حذف</button>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</div>

<!-- ══════════════ MODAL: إضافة / تعديل ══════════════ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeIfOut(event,'modalOverlay')">
  <div class="modal-box">
    <div class="modal-head">
        <h2 id="modalTitle">إضافة لعبة</h2>
        <button class="modal-close" onclick="closeModal('modalOverlay')">✕</button>
    </div>
    <form method="POST" id="gameForm" enctype="multipart/form-data">
        <input type="hidden" name="act" id="formAct" value="add">
        <input type="hidden" name="id"  id="formId"  value="">
        <input type="hidden" name="thumbnail_current" id="f_thumb_current" value="">
        <input type="hidden" name="url_current"       id="f_url_current"   value="">

        <div class="form-grid">
            <div class="form-group"><label>اسم اللعبة (عربي) *</label><input type="text" name="title" id="f_title" required></div>
            <div class="form-group"><label>Game Name (EN)</label><input type="text" name="title_en" id="f_title_en" dir="ltr"></div>
            <div class="form-group full"><label>الوصف (عربي)</label><textarea name="description" id="f_desc"></textarea></div>
            <div class="form-group full"><label>Description (EN)</label><textarea name="description_en" id="f_desc_en" dir="ltr"></textarea></div>
            <div class="form-group">
                <label>نوع اللعبة</label>
                <select name="game_type" id="f_type">
                    <option value="memory">🃏 ذاكرة</option>
                    <option value="cups">🏆 أكواب</option>
                    <option value="chase">🐭 مطاردة</option>
                    <option value="colors">🌈 ألوان</option>
                    <option value="numbers">🔢 أعداد</option>
                    <option value="other">🎮 أخرى</option>
                </select>
            </div>
            <div class="form-group"><label>ترتيب العرض</label><input type="number" name="sort_order" id="f_sort" value="0" min="0"></div>

            <div class="form-group full">
                <label>صورة أو فيديو الغلاف</label>
                <div class="upload-box" id="thumbUploadBox" onclick="document.getElementById('thumbFileInput').click()">
                    <div class="upload-preview" id="thumbPreview">
                        <span class="upload-icon">🖼️</span>
                        <span class="upload-hint">اضغط لاختيار صورة أو فيديو</span>
                        <span class="upload-sub">PNG · JPG · GIF · WEBP · MP4 · WEBM</span>
                    </div>
                </div>
                <input type="file" id="thumbFileInput" name="thumbnail_file"
                       accept="image/*,video/mp4,video/webm" style="display:none"
                       onchange="previewUpload(this,'thumbPreview','thumbUploadBox')">
            </div>

            <div class="form-group full">
                <label>ملف اللعبة</label>
                <div class="upload-box" id="gameUploadBox" onclick="document.getElementById('gameFileInput').click()">
                    <div class="upload-preview" id="gamePreview">
                        <span class="upload-icon">🎮</span>
                        <span class="upload-hint">اضغط لاختيار ملف اللعبة</span>
                        <span class="upload-sub">HTML · ZIP · JS</span>
                    </div>
                </div>
                <input type="file" id="gameFileInput" name="game_file"
                       accept=".html,.htm,.zip,.js,.php" style="display:none"
                       onchange="previewUpload(this,'gamePreview','gameUploadBox')">
            </div>

            <div class="form-group full">
                <label class="form-check">
                    <input type="checkbox" name="is_active" id="f_active" checked> مفعّلة
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('modalOverlay')">إلغاء</button>
            <button type="submit" class="btn-save">💾 حفظ</button>
        </div>
    </form>
  </div>
</div>

<!-- ══════════════ MODAL: إعدادات اللعبة ══════════════ -->
<div class="modal-overlay" id="settingsOverlay" onclick="closeIfOut(event,'settingsOverlay')">
  <div class="modal-box" style="max-width:560px">
    <div class="modal-head">
        <h2 id="settingsTitle">إعدادات اللعبة</h2>
        <button class="modal-close" onclick="closeModal('settingsOverlay')">✕</button>
    </div>
    <form method="POST" id="settingsForm" enctype="multipart/form-data">
        <input type="hidden" name="act" value="save_settings">
        <input type="hidden" name="id" id="s_id" value="">
        <div id="settingsFields"></div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('settingsOverlay')">إلغاء</button>
            <button type="submit" class="btn-save" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">⚙️ حفظ الإعدادات</button>
        </div>
    </form>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════
   الثوابت
   ══════════════════════════════════════════════ */
const ANIMAL_PRESETS = [
    {emoji:'🐱', path:'assets/images/animals/cat.png'},
    {emoji:'🐶', path:'assets/images/animals/dog.png'},
    {emoji:'🐸', path:'assets/images/animals/frog.png'},
    {emoji:'🐰', path:'assets/images/animals/rabbit.png'},
    {emoji:'🐻', path:'assets/images/animals/bear.png'},
    {emoji:'🦊', path:'assets/images/animals/fox.png'},
    {emoji:'🐧', path:'assets/images/animals/penguin.png'},
    {emoji:'🦁', path:'assets/images/animals/lion.png'},
    {emoji:'🐼', path:'assets/images/animals/panda.png'},
    {emoji:'🐯', path:'assets/images/animals/tiger.png'},
];

const PAIRS_OPTIONS = [
    {v:2, l:'2 أزواج (سهل جداً)'},
    {v:3, l:'3 أزواج (سهل)'},
    {v:4, l:'4 أزواج (متوسط)'},
    {v:5, l:'5 أزواج (صعب)'},
    {v:6, l:'6 أزواج (صعب جداً)'},
    {v:7, l:'7 أزواج (خبير)'},
    {v:8, l:'8 أزواج (خبير+)'},
];

const SETTINGS_FIELDS = {
    cups: [
        {key:'cups',       label:'عدد الأكواب',   type:'select',
         options:[{v:3,l:'3 أكواب'},{v:4,l:'4 أكواب'},{v:5,l:'5 أكواب'}], def:3},
        {key:'swaps',      label:'عدد التبديلات', type:'number', min:2, max:15, def:5},
        {key:'swap_speed', label:'سرعة التبديل',  type:'select',
         options:[{v:1000,l:'بطيء (1 ثانية)'},{v:750,l:'متوسط (0.75 ثانية)'},{v:500,l:'سريع (0.5 ثانية)'},{v:300,l:'سريع جداً (0.3 ثانية)'}], def:750}
    ],
    chase: [
        {key:'lives',        label:'عدد الأرواح', type:'select',
         options:[{v:2,l:'2 أرواح'},{v:3,l:'3 أرواح'},{v:5,l:'5 أرواح'}], def:3},
        {key:'player_speed', label:'سرعة الفأر',  type:'select',
         options:[{v:4,l:'بطيء'},{v:5,l:'متوسط'},{v:7,l:'سريع'}], def:5},
        {key:'cat_speed',    label:'سرعة القط',   type:'select',
         options:[{v:1,l:'بطيء'},{v:2,l:'متوسط'},{v:3,l:'سريع'}], def:2}
    ],
    colors: [
        {key:'rounds', label:'عدد الجولات', type:'select',
         options:[{v:5,l:'5 جولات'},{v:8,l:'8 جولات'},{v:10,l:'10 جولات'}], def:8}
    ],
    numbers: [
        {key:'rounds',  label:'عدد الجولات',    type:'select',
         options:[{v:5,l:'5 جولات'},{v:8,l:'8 جولات'},{v:10,l:'10 جولات'}], def:8},
        {key:'max_num', label:'أقصى عدد للعد',  type:'select',
         options:[{v:5,l:'حتى 5 (للصغار)'},{v:10,l:'حتى 10'}], def:10}
    ],
    other: []
};

/* ══════════════════════════════════════════════
   بناء حقل اختيار صورة (عام / قابل للإعادة)
   ══════════════════════════════════════════════ */
function buildImgPickerField(key, label, val) {
    const div = document.createElement('div');
    div.className = 'settings-field';

    const lbl = document.createElement('label');
    lbl.textContent = label;
    div.appendChild(lbl);

    const wrap = document.createElement('div');
    wrap.className = 'img-field-wrap';

    /* معاينة */
    const previewBox = document.createElement('div');
    previewBox.className = 'img-preview-box';
    previewBox.id = 'imgPreview_' + key;
    setImgPreviewContent(previewBox, val);

    /* حقل النص */
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.name = 'setting_' + key;
    inp.value = val || '';
    inp.placeholder = 'assets/images/animals/cat.png  أو رابط http://...';
    inp.setAttribute('dir', 'ltr');
    inp.addEventListener('input', () => {
        setImgPreviewContent(previewBox, inp.value.trim());
        clearPresetSelection(row);
    });

    /* أزرار الاختيار السريع */
    const presetsLabel = document.createElement('div');
    presetsLabel.className = 'img-presets-label';
    presetsLabel.textContent = 'اختيار سريع:';

    const row = document.createElement('div');
    row.className = 'img-preset-row';

    ANIMAL_PRESETS.forEach(p => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'img-preset-btn' + (val === p.path ? ' selected' : '');
        btn.title = p.path;
        btn.innerHTML = `<img src="../${p.path}" alt="${p.emoji}"
            onerror="this.outerHTML='${p.emoji}'"
            style="width:100%;height:100%;object-fit:contain;">`;
        btn.addEventListener('click', () => {
            inp.value = p.path;
            setImgPreviewContent(previewBox, p.path);
            clearPresetSelection(row);
            btn.classList.add('selected');
        });
        row.appendChild(btn);
    });

    wrap.appendChild(previewBox);
    wrap.appendChild(inp);
    wrap.appendChild(presetsLabel);
    wrap.appendChild(row);
    div.appendChild(wrap);
    return div;
}

function setImgPreviewContent(box, path) {
    if (!path) { box.innerHTML = '<span class="ph">🖼️</span>'; return; }
    const src = path.startsWith('http') ? path : ('../' + path);
    box.innerHTML = `<img src="${src}" onerror="this.parentNode.innerHTML='<span class=ph>🖼️</span>'">`;
}

function clearPresetSelection(row) {
    row?.querySelectorAll('.img-preset-btn').forEach(b => b.classList.remove('selected'));
}

/* ══════════════════════════════════════════════
   إعدادات لعبة الذاكرة (خاصة)
   ══════════════════════════════════════════════ */
function renderMemorySettings(container, current) {
    /* ── عدد الأزواج ── */
    const pairsDiv = document.createElement('div');
    pairsDiv.className = 'settings-field';
    const pairsLbl = document.createElement('label');
    pairsLbl.textContent = 'عدد الأزواج';
    pairsDiv.appendChild(pairsLbl);

    const pairsSel = document.createElement('select');
    pairsSel.name = 'setting_pairs';
    pairsSel.id   = 'memory_pairs_select';
    const savedPairs = parseInt(current['pairs']) || 4;

    PAIRS_OPTIONS.forEach(o => {
        const opt = document.createElement('option');
        opt.value = o.v;
        opt.textContent = o.l;
        if (o.v === savedPairs) opt.selected = true;
        pairsSel.appendChild(opt);
    });
    pairsDiv.appendChild(pairsSel);
    container.appendChild(pairsDiv);

    /* ── قسم صور الأزواج ── */
    const pairSection = document.createElement('div');
    pairSection.id = 'pair_images_section';
    container.appendChild(pairSection);

    function renderPairImages(count) {
        pairSection.innerHTML = '';

        const divider = document.createElement('div');
        divider.className = 'settings-divider';
        divider.textContent = '🖼️ صور الأزواج (اختياري)';
        pairSection.appendChild(divider);

        const grid = document.createElement('div');
        grid.className = 'pair-images-grid';

        for (let i = 1; i <= count; i++) {
            const key    = 'pair_' + i + '_image';
            const val    = current[key] || '';
            const fileInputName = 'pair_img_' + i;

            const card = document.createElement('div');
            card.className = 'pair-img-card';

            const cardLabel = document.createElement('div');
            cardLabel.className = 'pair-img-card-label';
            cardLabel.textContent = '🃏 الزوج ' + i;
            card.appendChild(cardLabel);

            /* معاينة مصغرة */
            const preview = document.createElement('div');
            preview.className = 'img-preview-box';
            preview.id = 'imgPreview_' + key;
            preview.style.height = '80px';
            setImgPreviewContent(preview, val);
            card.appendChild(preview);

            /* حقل نصي للمسار الحالي (يُرسل للسيرفر إذا لم يُرفع ملف) */
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'setting_' + key;
            inp.id    = 'inp_' + key;
            inp.value = val;
            card.appendChild(inp);

            /* ── زر رفع صورة (file input حقيقي) ── */
            const fileInput = document.createElement('input');
            fileInput.type   = 'file';
            fileInput.name   = fileInputName;
            fileInput.accept = 'image/*';
            fileInput.style.display = 'none';
            fileInput.id = 'file_' + key;
            card.appendChild(fileInput);

            const uploadLabel = document.createElement('label');
            uploadLabel.htmlFor = 'file_' + key;
            uploadLabel.style.cssText = 'display:flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#6c63ff,#a78bfa);color:#fff;border-radius:10px;padding:7px 12px;font-size:13px;font-weight:700;cursor:pointer;margin-top:6px;';
            uploadLabel.textContent = '📤 رفع صورة';
            card.appendChild(uploadLabel);

            fileInput.addEventListener('change', () => {
                if (!fileInput.files[0]) return;
                const url = URL.createObjectURL(fileInput.files[0]);
                /* معاينة فورية */
                preview.innerHTML = `<img src="${url}" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;">`;
                /* امسح المسار النصي لأن السيرفر سيأخذ الملف */
                inp.value = '';
                clearPresetSelection(row);
                uploadLabel.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
                uploadLabel.textContent = '✅ ' + fileInput.files[0].name.slice(0,18);
            });

            /* أزرار الاختيار السريع */
            const row = document.createElement('div');
            row.className = 'img-preset-row';
            row.style.cssText = 'justify-content:center;margin-top:6px;';

            ANIMAL_PRESETS.forEach(p => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'img-preset-btn' + (val === p.path ? ' selected' : '');
                btn.title = p.emoji;
                btn.style.width = '36px';
                btn.style.height = '36px';
                btn.innerHTML = `<img src="../${p.path}" alt="${p.emoji}"
                    onerror="this.outerHTML='${p.emoji}'"
                    style="width:100%;height:100%;object-fit:contain;">`;
                btn.addEventListener('click', () => {
                    inp.value = p.path;
                    setImgPreviewContent(preview, p.path);
                    clearPresetSelection(row);
                    btn.classList.add('selected');
                    /* امسح الملف المرفوع إن وُجد */
                    fileInput.value = '';
                    uploadLabel.style.background = 'linear-gradient(135deg,#6c63ff,#a78bfa)';
                    uploadLabel.textContent = '📤 رفع صورة';
                });
                row.appendChild(btn);
            });

            /* زر مسح */
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.style.cssText = 'background:#fee2e2;border:none;border-radius:8px;padding:4px 10px;font-size:12px;font-weight:700;color:#dc2626;cursor:pointer;margin-top:4px;';
            clearBtn.textContent = '✕ بدون صورة';
            clearBtn.addEventListener('click', () => {
                inp.value = '';
                fileInput.value = '';
                setImgPreviewContent(preview, '');
                clearPresetSelection(row);
                uploadLabel.style.background = 'linear-gradient(135deg,#6c63ff,#a78bfa)';
                uploadLabel.textContent = '📤 رفع صورة';
            });

            card.appendChild(row);
            card.appendChild(clearBtn);
            grid.appendChild(card);
        }

        pairSection.appendChild(grid);
    }

    renderPairImages(savedPairs);
    pairsSel.addEventListener('change', () => renderPairImages(parseInt(pairsSel.value)));
}

/* ══════════════════════════════════════════════
   معاينة الملف المرفوع
   ══════════════════════════════════════════════ */
function previewUpload(input, previewId, boxId) {
    const file    = input.files[0];
    const preview = document.getElementById(previewId);
    const box     = document.getElementById(boxId);
    if (!file) return;
    box.classList.add('has-file');
    const isVideo = file.type.startsWith('video/');
    const isImage = file.type.startsWith('image/');
    const url     = URL.createObjectURL(file);
    if (isImage) {
        preview.innerHTML = `<img src="${url}"><span class="upload-filename">${file.name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
    } else if (isVideo) {
        preview.innerHTML = `<video src="${url}" autoplay muted loop style="max-height:130px;border-radius:10px;"></video><span class="upload-filename">${file.name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
    } else {
        preview.innerHTML = `<span class="upload-icon">📦</span><span class="upload-filename">${file.name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
    }
}

function showExistingFile(previewId, boxId, path, mode) {
    if (!path) return;
    const preview = document.getElementById(previewId);
    const box     = document.getElementById(boxId);
    box.classList.add('has-file');
    const src  = path.startsWith('http') ? path : ('../' + path);
    const name = path.split('/').pop();
    if (mode === 'thumb') {
        const isVid = /\.(mp4|webm)$/i.test(path);
        if (isVid) {
            preview.innerHTML = `<video src="${src}" autoplay muted loop style="max-height:130px;border-radius:10px;"></video><span class="upload-filename">${name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
        } else {
            preview.innerHTML = `<img src="${src}" onerror="this.parentNode.innerHTML='<span class=upload-icon>🖼️</span>'"><span class="upload-filename">${name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
        }
    } else {
        preview.innerHTML = `<span class="upload-icon">📦</span><span class="upload-filename">${name}</span><span class="upload-change">اضغط لتغيير الملف</span>`;
    }
}

/* ══════════════════════════════════════════════
   فتح Modal إضافة / تعديل
   ══════════════════════════════════════════════ */
function openModal(game) {
    document.getElementById('thumbFileInput').value = '';
    document.getElementById('gameFileInput').value  = '';
    document.getElementById('thumbPreview').innerHTML =
        '<span class="upload-icon">🖼️</span><span class="upload-hint">اضغط لاختيار صورة أو فيديو</span><span class="upload-sub">PNG · JPG · GIF · WEBP · MP4 · WEBM</span>';
    document.getElementById('gamePreview').innerHTML =
        '<span class="upload-icon">🎮</span><span class="upload-hint">اضغط لاختيار ملف اللعبة</span><span class="upload-sub">HTML · ZIP · JS</span>';
    document.getElementById('thumbUploadBox').classList.remove('has-file');
    document.getElementById('gameUploadBox').classList.remove('has-file');

    if (game) {
        document.getElementById('modalTitle').textContent = 'تعديل اللعبة';
        document.getElementById('formAct').value          = 'edit';
        document.getElementById('formId').value           = game.id;
        document.getElementById('f_title').value          = game.title          || '';
        document.getElementById('f_title_en').value       = game.title_en       || '';
        document.getElementById('f_desc').value           = game.description    || '';
        document.getElementById('f_desc_en').value        = game.description_en || '';
        document.getElementById('f_type').value           = game.game_type      || 'other';
        document.getElementById('f_sort').value           = game.sort_order     || 0;
        document.getElementById('f_active').checked       = game.is_active == 1;
        document.getElementById('f_thumb_current').value  = game.thumbnail      || '';
        document.getElementById('f_url_current').value    = game.url            || '';
        showExistingFile('thumbPreview', 'thumbUploadBox', game.thumbnail, 'thumb');
        showExistingFile('gamePreview',  'gameUploadBox',  game.url,       'file');
    } else {
        document.getElementById('modalTitle').textContent = 'إضافة لعبة جديدة';
        document.getElementById('formAct').value          = 'add';
        document.getElementById('formId').value           = '';
        document.getElementById('f_thumb_current').value  = '';
        document.getElementById('f_url_current').value    = '';
        document.getElementById('gameForm').reset();
        document.getElementById('f_active').checked = true;
    }
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* ══════════════════════════════════════════════
   Modal الإعدادات
   ══════════════════════════════════════════════ */
function openSettings(game) {
    document.getElementById('s_id').value = game.id;
    document.getElementById('settingsTitle').textContent = '⚙️ إعدادات: ' + game.title;

    const current   = game.game_settings ? JSON.parse(game.game_settings) : {};
    const container = document.getElementById('settingsFields');
    container.innerHTML = '';

    /* ── لعبة الذاكرة: معالجة خاصة ── */
    if (game.game_type === 'memory') {
        renderMemorySettings(container, current);
        document.getElementById('settingsOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        return;
    }

    /* ── باقي الألعاب ── */
    const fields = SETTINGS_FIELDS[game.game_type] || [];
    if (fields.length === 0) {
        container.innerHTML = '<div class="no-settings">لا توجد إعدادات لهذا النوع من الألعاب</div>';
    } else {
        fields.forEach(f => {
            const val = current[f.key] !== undefined ? current[f.key] : f.def;
            const div = document.createElement('div');
            div.className = 'settings-field';

            const lbl = document.createElement('label');
            lbl.textContent = f.label;
            div.appendChild(lbl);

            if (f.type === 'select') {
                const sel = document.createElement('select');
                sel.name = 'setting_' + f.key;
                f.options.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.v; opt.textContent = o.l;
                    if (o.v == val) opt.selected = true;
                    sel.appendChild(opt);
                });
                div.appendChild(sel);
            } else {
                const inp = document.createElement('input');
                inp.type = 'number';
                inp.name = 'setting_' + f.key;
                inp.value = val;
                inp.min = f.min || 1;
                inp.max = f.max || 100;
                div.appendChild(inp);
            }
            container.appendChild(div);
        });
    }

    document.getElementById('settingsOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* ══════════════════════════════════════════════
   إغلاق Modals
   ══════════════════════════════════════════════ */
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function closeIfOut(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal('modalOverlay');
        closeModal('settingsOverlay');
    }
});
</script>
</body>
</html>
