<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$search = trim($_GET['search'] ?? '');
$gender = $_GET['gender'] ?? '';
$age    = $_GET['age'] ?? '';
$order  = $_GET['order'] ?? 'newest';

$where  = "WHERE 1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (id = ? OR username LIKE ?)";
    $params[] = intval($search);
    $params[] = "%$search%";
    $types   .= "is";
}

if ($gender !== '') {
    $where   .= " AND gender = ?";
    $params[] = $gender;
    $types   .= "s";
}


$orderBy = "id DESC";
if ($order === 'oldest')   $orderBy = "id ASC";

$countAll    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM children"))['total'];
$countMale   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM children WHERE gender='male'"))['total'];
$countFemale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM children WHERE gender='female'"))['total'];

$sql  = "SELECT * FROM children $where ORDER BY $orderBy";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة الأطفال</title>
<link rel="stylesheet" href="assets/admin.css">

<style>
.children-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-subtitle {
    color: #777;
    font-size: 18px;
    margin-top: -22px;
    margin-bottom: 25px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: #fff;
    border-radius: 22px;
    padding: 25px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
    display: flex;
    align-items: center;
    gap: 18px;
}

.stat-icon {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.stat-blue  { background: #e8f1ff; }
.stat-green { background: #e7f8ee; }
.stat-pink  { background: #ffe8f0; }

.stat-info h3     { color: #21425f; font-size: 18px; margin-bottom: 8px; }
.stat-info strong { color: #21425f; font-size: 32px; }

.filter-box {
    background: #fff;
    padding: 22px;
    border-radius: 22px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
    margin-bottom: 25px;
}

.filter-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 14px;
    align-items: end;
}

.filter-group label {
    display: block;
    color: #21425f;
    font-weight: bold;
    margin-bottom: 8px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 14px;
    border: 2px solid #dce7f2;
    border-radius: 14px;
    font-size: 16px;
    outline: none;
}

.search-btn,
.reset-btn {
    border: none;
    padding: 14px 22px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
}

.search-btn { background: #2878e8; color: #fff; }
.reset-btn  { background: #eef3f8; color: #21425f; }

.child-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dff3ff, #fff0f6);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    font-size: 20px;
}

.gender-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 13px;
}

.gender-male   { background: #e8f1ff; color: #2878e8; }
.gender-female { background: #ffe8f0; color: #e8437a; }

.phone-cell {
    direction: ltr;
    text-align: center;
    color: #21425f;
    font-size: 13px;
}

.phone-empty { color: #aab4c0; font-size: 13px; }

.actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}

/* ── جدول بستايل الريبورتس ── */
.table-wrap {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 6px 18px rgba(33,66,95,.08);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 24px;
}

.table-wrap table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

.table-wrap thead th {
    background: #21425f;
    color: #fff;
    padding: 12px 10px;
    font-size: 13px;
    font-weight: 900;
    text-align: center;
    white-space: nowrap;
}

.table-wrap thead th:first-child {
    border-radius: 0 18px 0 0;
    text-align: right;
    padding-right: 18px;
}

.table-wrap thead th:last-child { border-radius: 18px 0 0 0; }

.table-wrap tbody td {
    padding: 12px 10px;
    text-align: center;
    border-bottom: 1px solid #f0f5fb;
    font-size: 13px;
}

.table-wrap tbody td:first-child {
    text-align: right;
    padding-right: 18px;
    font-weight: 900;
    color: #21425f;
    font-size: 14px;
}

.table-wrap tbody tr:hover { background: #f7fbff; }
.table-wrap tbody tr:last-child td { border: none; }

.empty-row {
    padding: 35px !important;
    color: #777;
    font-size: 18px !important;
    text-align: center !important;
}

@media (max-width: 1000px) {
    .filter-form { grid-template-columns: 1fr 1fr; }
    .stats-grid  { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 700px) {
    .filter-form { grid-template-columns: 1fr; }
    .stats-grid  { grid-template-columns: 1fr; }

    .children-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }

    .page-subtitle { font-size: 15px; }
}
</style>
</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<div class="children-header">
    <div>
        <h1 class="page-title">إدارة الأطفال</h1>
        <p class="page-subtitle">يمكنك إضافة وتعديل وحذف بيانات الأطفال بسهولة</p>
    </div>

    <?php if (supCan('children','can_add')): ?>
    <a href="add-child.php" class="add-btn">+ إضافة طفل جديد</a>
    <?php endif; ?>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-blue">👦</div>
        <div class="stat-info">
            <h3>إجمالي الأطفال</h3>
            <strong><?php echo $countAll; ?></strong>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-green">♂</div>
        <div class="stat-info">
            <h3>عدد الذكور</h3>
            <strong><?php echo $countMale; ?></strong>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-pink">♀</div>
        <div class="stat-info">
            <h3>عدد الإناث</h3>
            <strong><?php echo $countFemale; ?></strong>
        </div>
    </div>
</div>

<div class="filter-box">
    <form method="GET" class="filter-form" id="filterForm">

        <div class="filter-group">
            <label>بحث</label>
            <input type="text" name="search" placeholder="ابحث بالاسم أو الرقم..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group">
            <label>الجنس</label>
            <select name="gender">
                <option value="">كل الجنس</option>
                <option value="male"   <?php if ($gender === 'male')   echo 'selected'; ?>>ذكر</option>
                <option value="female" <?php if ($gender === 'female') echo 'selected'; ?>>أنثى</option>
            </select>
        </div>

        <div class="filter-group">
            <label>ترتيب حسب</label>
            <select name="order" id="orderSelect">
                <option value="newest"   <?php if ($order === 'newest')   echo 'selected'; ?>>الأحدث</option>
                <option value="oldest"   <?php if ($order === 'oldest')   echo 'selected'; ?>>الأقدم</option>
            </select>
        </div>

        <a href="children.php" class="reset-btn">إعادة تعيين</a>

    </form>
</div>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>اسم المستخدم</th>
    <th>الصورة</th>
    <th>الرقم</th>
    <th>الجنس</th>
    <th>رقم الجوال</th>
    <th>تاريخ الإضافة</th>
    <th>الإجراءات</th>
</tr>
</thead>

<tbody>
<?php if (mysqli_num_rows($result) > 0): ?>
<?php while ($child = mysqli_fetch_assoc($result)): ?>
<tr>
    <td><?php echo htmlspecialchars($child['username']); ?></td>

    <td>
        <div class="child-avatar">
            <?php echo $child['gender'] === 'male' ? '👦' : '👧'; ?>
        </div>
    </td>

    <td><?php echo $child['id']; ?></td>

    <td>
        <?php if ($child['gender'] === 'male'): ?>
            <span class="gender-badge gender-male">ذكر</span>
        <?php else: ?>
            <span class="gender-badge gender-female">أنثى</span>
        <?php endif; ?>
    </td>

    <td class="phone-cell">
        <?php if (!empty($child['phone'])): ?>
            <?php echo htmlspecialchars($child['phone']); ?>
        <?php else: ?>
            <span class="phone-empty">—</span>
        <?php endif; ?>
    </td>

    <td style="font-size:12px;color:#7a90a3;font-weight:800;"><?php echo $child['created_at']; ?></td>

    <td>
        <div class="actions">
            <?php if (supCan('children','can_edit')): ?><a href="edit-child.php?id=<?php echo $child['id']; ?>" class="edit-btn">تعديل</a><?php endif; ?>
            <?php if (supCan('children','can_delete')): ?><a href="delete-child.php?id=<?php echo $child['id']; ?>" class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا الطفل؟');">حذف</a><?php endif; ?>
        </div>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="7" class="empty-row">لا يوجد أطفال مطابقين للبحث</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

</main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const filterForm   = document.getElementById("filterForm");
    const searchInput  = document.querySelector('input[name="search"]');
    const genderSelect = document.querySelector('select[name="gender"]');
    const orderSelect  = document.getElementById("orderSelect");

    // ترتيب حسب → submit فوري لأنه server-side
    if (orderSelect) {
        orderSelect.addEventListener("change", function () {
            filterForm.submit();
        });
    }

    // فلترة client-side للبحث والجنس
    const rows = document.querySelectorAll(".table-wrap tbody tr");

    function filterTable() {
        const search = searchInput.value.trim().toLowerCase();
        const gender = genderSelect.value;

        rows.forEach(row => {
            if (row.querySelector('.empty-row')) return;

            const username     = row.children[0].textContent.trim().toLowerCase();
            const rowId        = row.children[2].textContent.trim().toLowerCase();
            const rowGenderTxt = row.children[3].textContent.trim();

            let rowGender = "";
            if (rowGenderTxt === "ذكر")  rowGender = "male";
            if (rowGenderTxt === "أنثى") rowGender = "female";

            const matchSearch = search === "" || rowId.includes(search) || username.includes(search);
            const matchGender = gender === "" || rowGender === gender;

            row.style.display = (matchSearch && matchGender) ? "" : "none";
        });
    }

    if (searchInput)  searchInput.addEventListener("input", filterTable);
    if (genderSelect) genderSelect.addEventListener("change", filterTable);
});
</script>
</body>
</html>
