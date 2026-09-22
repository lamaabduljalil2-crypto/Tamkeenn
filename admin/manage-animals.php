<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
$category = $_GET['category'] ?? 'wild';
$animalPermKey = ($category === 'wild') ? 'gc_animals_wild' : 'gc_animals_pet';
if ($isSupervisor && !supHasAny($animalPermKey)) {
    header('Location: supervisor-dashboard.php'); exit;
}
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

mysqli_set_charset($conn, 'utf8mb4');
ensure_animals_setup($conn);

$subject = 'الثقافة العامة';
$type = 'الحيوانات';

$category = $_GET['category'] ?? $_POST['category'] ?? 'wild';
if (!in_array($category, ['wild', 'pet'], true)) {
    $category = 'wild';
}

/* حذف حيوان */
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    // السماح إذا كان مالك العنصر حتى بدون صلاحية حذف
    $ownerCheck = mysqli_query($conn, "SELECT created_by_supervisor FROM animals WHERE id=$delId");
    $ownerRow   = $ownerCheck ? mysqli_fetch_assoc($ownerCheck) : null;
    $_isOwner   = $isSupervisor && $ownerRow && intval($ownerRow['created_by_supervisor']) === intval($_SESSION['supervisor_id'] ?? -1);
    if ($isSupervisor && !supCan($animalPermKey, 'can_delete') && !$_isOwner) {
        header("Location: supervisor-dashboard.php"); exit;
    }
    $d = mysqli_prepare($conn, "DELETE FROM animals WHERE id = ? AND category = ?");
    mysqli_stmt_bind_param($d, "is", $delId, $category);
    mysqli_stmt_execute($d);
    header("Location: manage-animals.php?category=" . urlencode($category));
    exit;
}

/* حفظ المواضع */
if (isset($_POST['save_positions']) && isset($_POST['pos']) && is_array($_POST['pos'])) {
    $up = mysqli_prepare(
        $conn,
        "UPDATE animals SET pos_top = ?, pos_left = ?, width = ? WHERE id = ? AND category = ?"
    );
    foreach ($_POST['pos'] as $animId => $vals) {
        $animId = intval($animId);
        $top   = substr(trim((string)($vals['top'] ?? '')), 0, 20);
        $left  = substr(trim((string)($vals['left'] ?? '')), 0, 20);
        $width = substr(trim((string)($vals['width'] ?? '')), 0, 20);
        if ($top === '' || $left === '' || $width === '') continue;
        mysqli_stmt_bind_param($up, "sssis", $top, $left, $width, $animId, $category);
        mysqli_stmt_execute($up);
    }
    header("Location: manage-animals.php?category=" . urlencode($category) . "&saved=1");
    exit;
}

/* جلب الحيوانات */
$animals = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM animals WHERE category = ? ORDER BY sort_order ASC, id ASC");
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $animals[] = $r;
}

$catLabel = $category === 'wild' ? 'الحيوانات المفترسة' : 'الحيوانات الأليفة';
$bgImage = $category === 'wild'
    ? '../images/general/animals/forest-bg.png'
    : '../images/general/animals/garden-bg.png';
$saved = isset($_GET['saved']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة <?php echo htmlspecialchars($catLabel); ?></title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.page-title{text-align:center;width:100%;}

.tools-bar{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin:18px auto;
    background:#fff;
    border-radius:18px;
    padding:14px 18px;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
    max-width:1100px;
}

@media(max-width:700px){
    .tools-bar{ flex-direction:column; align-items:stretch; text-align:center; }
    .add-btn-green{ text-align:center; }
    .edit-board{ min-height:70vw !important; }
    .cards-grid{ grid-template-columns:1fr 1fr !important; gap:12px !important; }
    .a-card .thumb{ height:80px; }
    .a-card h3{ font-size:16px; }
    .save-pos-btn{ width:100%; }
}

.tools-bar .hint{
    color:#6a849a;
    font-weight:800;
    font-size:14px;
}

.tools-bar label{
    font-weight:900;
    color:#21425f;
}

.add-btn-green{
    background:#28b978;
    color:#fff;
    text-decoration:none;
    padding:11px 22px;
    border-radius:14px;
    font-weight:900;
}

.save-pos-btn{
    background:#21425f;
    color:#fff;
    border:none;
    padding:12px 26px;
    border-radius:14px;
    font-weight:900;
    font-size:16px;
    cursor:pointer;
    font-family:inherit;
}

.saved-msg{
    text-align:center;
    background:#e6fbef;
    color:#1d854f;
    border:2px solid #9be7bd;
    border-radius:14px;
    padding:10px;
    font-weight:900;
    max-width:1100px;
    margin:0 auto 14px;
}

.board-wrap{
    max-width:1100px;
    margin:0 auto;
}

.edit-board{
    position:relative;
    width:100%;
    min-height:750px;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 12px 26px rgba(0,0,0,.12);
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    background-color:#d7e7c8;
    user-select:none;
    touch-action:none;
}

.a-spot{
    position:absolute;
    cursor:grab;
    transition:filter .15s ease;
    z-index:2;
}

.a-spot img{
    width:100%;
    height:auto;
    display:block;
    filter:drop-shadow(0 8px 14px rgba(0,0,0,.25));
    pointer-events:none;
    object-fit:contain;
}
.a-spot.selected{
    outline:3px dashed #ff5f8f;
    outline-offset:4px;
    border-radius:8px;
    z-index:50;
}

.a-spot.dragging{
    cursor:grabbing;
    filter:drop-shadow(0 14px 22px rgba(0,0,0,.35));
    z-index:60;
}

.cards-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:20px;
    margin:26px auto 0;
    max-width:1100px;
}

.a-card{
    background:#fff;
    border-radius:24px;
    padding:18px 14px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.a-card .thumb{
    width:100%;
    height:120px;
    object-fit:contain;
    margin-bottom:10px;
}

.a-card h3{
    color:#21425f;
    font-size:20px;
    margin-bottom:12px;
}

.a-actions{
    display:flex;
    justify-content:center;
    gap:8px;
}

.edit-btn,.delete-btn{
    padding:9px 16px;
    border-radius:12px;
    text-decoration:none;
    font-weight:bold;
    font-size:15px;
}

.edit-btn{background:#21425f;color:#fff;}
.delete-btn{background:#d93025;color:#fff;}

.empty-box{
    background:#fff;
    border-radius:24px;
    padding:28px;
    margin:20px auto;
    text-align:center;
    color:#21425f;
    font-size:18px;
    font-weight:bold;
    max-width:1100px;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}
</style>
</head>

<body>
<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">إدارة <?php echo htmlspecialchars($catLabel); ?></h1>

<?php if ($saved): ?>
<div class="saved-msg">تم حفظ المواضع بنجاح ✅</div>
<?php endif; ?>

<div class="tools-bar">
    <?php if (!$isSupervisor || supCan($animalPermKey, 'can_add')): ?>
    <a href="add-animal.php?category=<?php echo urlencode($category); ?>" class="add-btn-green">+ إضافة حيوان</a>
    <?php endif; ?>
    <span class="hint">اسحب الحيوان بالماوس لتغيير مكانه.</span>
    <label>حجم المحدد:</label>
    <input type="range" id="widthRange" min="30" max="320" value="130" disabled>
    <span id="widthVal" class="hint">—</span>
</div>

<?php if (count($animals) > 0): ?>

<form method="POST" class="board-wrap" id="boardForm">
    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
    <input type="hidden" name="save_positions" value="1">

    <div class="edit-board" id="editBoard"
         style="background-image:url('<?php echo htmlspecialchars($bgImage); ?>');">

        <?php foreach ($animals as $a): ?>
        <?php
            $aid = intval($a['id']);
            $img = '../' . ltrim((string)$a['image'], '/');
            $top = htmlspecialchars($a['pos_top']);
            $left = htmlspecialchars($a['pos_left']);
            $w = htmlspecialchars($a['width']);
        ?>
        <div class="a-spot" data-id="<?php echo $aid; ?>"
             title="<?php echo htmlspecialchars($a['name']); ?>"
             style="top:<?php echo $top; ?>;left:<?php echo $left; ?>;width:<?php echo $w; ?>;">
            <img src="<?php echo htmlspecialchars($img); ?>" draggable="false"
                 onerror="this.style.opacity=.3;">
            <input type="hidden" name="pos[<?php echo $aid; ?>][top]"   id="top_<?php echo $aid; ?>"   value="<?php echo $top; ?>">
            <input type="hidden" name="pos[<?php echo $aid; ?>][left]"  id="left_<?php echo $aid; ?>"  value="<?php echo $left; ?>">
            <input type="hidden" name="pos[<?php echo $aid; ?>][width]" id="width_<?php echo $aid; ?>" value="<?php echo $w; ?>">
        </div>
        <?php endforeach; ?>

    </div>

    <div style="text-align:center;margin-top:16px;">
        <button type="submit" class="save-pos-btn">💾 حفظ المواضع</button>
    </div>
</form>

<div class="cards-grid">
    <?php foreach ($animals as $a): ?>
    <div class="a-card">
        <img class="thumb" src="../<?php echo htmlspecialchars(ltrim((string)$a['image'], '/')); ?>"
             alt="<?php echo htmlspecialchars($a['name']); ?>" onerror="this.style.opacity=.3;">
        <h3><?php echo htmlspecialchars($a['name']); ?></h3>
        <?php
            $_owns = $isSupervisor && intval($a['created_by_supervisor'] ?? 0) === intval($_SESSION['supervisor_id'] ?? -1);
        ?>
        <div class="a-actions">
            <?php if (!$isSupervisor || supCan($animalPermKey, 'can_edit') || $_owns): ?>
            <a class="edit-btn" href="edit-animal.php?id=<?php echo intval($a['id']); ?>">تعديل</a>
            <?php endif; ?>
            <?php if (!$isSupervisor || supCan($animalPermKey, 'can_delete') || $_owns): ?>
            <a class="delete-btn"
               href="manage-animals.php?category=<?php echo urlencode($category); ?>&delete=<?php echo intval($a['id']); ?>"
               onclick="return confirm('هل أنت متأكد من حذف الحيوان؟');">حذف</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<div class="empty-box">لا يوجد حيوانات في هذا القسم بعد. اضغط "إضافة حيوان".</div>

<?php endif; ?>

<a
href="subject-lessons.php?subject=<?php echo urlencode($subject); ?>&type=<?php echo urlencode($type); ?>"
class="back-link">
رجوع لأقسام الحيوانات
</a>

</main>
</div>

<script>
(function () {
    const board = document.getElementById('editBoard');
    if (!board) return;

    const widthRange = document.getElementById('widthRange');
    const widthVal = document.getElementById('widthVal');

    let selected = null;
    let dragging = null;
    let startX = 0, startY = 0;
    let startLeftPx = 0, startTopPx = 0;

    function pct(value, total) {
        return Math.max(0, Math.min(100, (value / total) * 100));
    }

    function selectSpot(spot) {
        if (selected) selected.classList.remove('selected');
        selected = spot;
        spot.classList.add('selected');
        const w = parseInt(spot.style.width) || 130;
        widthRange.disabled = false;
        widthRange.value = Math.min(320, Math.max(30, w));
        widthVal.textContent = w + 'px';
    }

    function startDrag(spot, clientX, clientY) {
        selectSpot(spot);
        dragging = spot;
        spot.classList.add('dragging');
        const boardRect = board.getBoundingClientRect();
        const spotRect = spot.getBoundingClientRect();
        startX = clientX;
        startY = clientY;
        startLeftPx = spotRect.left - boardRect.left;
        startTopPx = spotRect.top - boardRect.top;
    }

    function moveDrag(clientX, clientY) {
        if (!dragging) return;
        const boardRect = board.getBoundingClientRect();
        const leftPct = pct(startLeftPx + (clientX - startX), boardRect.width);
        const topPct  = pct(startTopPx  + (clientY - startY), boardRect.height);
        const id = dragging.dataset.id;
        dragging.style.left = leftPct.toFixed(2) + '%';
        dragging.style.top  = topPct.toFixed(2) + '%';
        document.getElementById('left_' + id).value = leftPct.toFixed(2) + '%';
        document.getElementById('top_'  + id).value = topPct.toFixed(2) + '%';
    }

    function endDrag() {
        if (dragging) { dragging.classList.remove('dragging'); dragging = null; }
    }

    board.querySelectorAll('.a-spot').forEach(function (spot) {
        spot.addEventListener('mousedown', function (e) {
            e.preventDefault();
            startDrag(spot, e.clientX, e.clientY);
        });
        spot.addEventListener('touchstart', function (e) {
            e.preventDefault();
            startDrag(spot, e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: false });
    });

    document.addEventListener('mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
    document.addEventListener('touchmove', function (e) {
        if (dragging) { e.preventDefault(); moveDrag(e.touches[0].clientX, e.touches[0].clientY); }
    }, { passive: false });

    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);

    widthRange.addEventListener('input', function () {
        if (!selected) return;
        const id = selected.dataset.id;
        const w = parseInt(widthRange.value) || 130;
        selected.style.width = w + 'px';
        document.getElementById('width_' + id).value = w + 'px';
        widthVal.textContent = w + 'px';
    });
})();
</script>
</body>
</html>
