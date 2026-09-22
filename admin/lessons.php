<?php
require_once 'includes/admin-check.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إدارة الدروس</title>

<link rel="stylesheet" href="assets/admin.css">

<style>
*{
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f4f8fc;
}

.page-title{
    text-align:center;
    color:#21425f;
    font-size:36px;
    margin-bottom:38px;
    font-weight:900;
}

.cards-grid{
    max-width:1050px;
    margin:0 auto;
    display:grid;
    grid-template-columns:repeat(2, minmax(260px, 1fr));
    gap:28px;
}

.admin-card{
    position:relative;
    min-height:190px;
    border-radius:32px;
    background:linear-gradient(180deg,#ffffff,#f7fbff);
    text-decoration:none;
    padding:32px 24px;
    box-shadow:0 14px 34px rgba(33,66,95,.10);
    border:1px solid #e1edf7;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    transition:.25s ease;
}

.admin-card::before{
    content:"";
    position:absolute;
    width:120px;
    height:120px;
    border-radius:50%;
    background:#e8f3ff;
    top:-42px;
    right:-42px;
}

.admin-card::after{
    content:"";
    position:absolute;
    width:80px;
    height:80px;
    border-radius:50%;
    background:#fff0f7;
    bottom:-30px;
    left:-25px;
}

.admin-card:hover{
    transform:translateY(-6px);
    box-shadow:0 18px 42px rgba(33,66,95,.16);
}

.admin-icon{
    width:76px;
    height:76px;
    border-radius:50%;
    background:#e8f3ff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:36px;
    margin-bottom:16px;
    position:relative;
    z-index:2;
}

.admin-card h2{
    margin:0;
    color:#21425f;
    font-size:28px;
    font-weight:900;
    position:relative;
    z-index:2;
}

.admin-card span{
    margin-top:10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 18px;
    border-radius:999px;
    background:#eef7ff;
    color:#52718c;
    font-size:16px;
    font-weight:800;
    position:relative;
    z-index:2;
}

.arabic-card .admin-icon{
    background:#fff3db;
}

.english-card .admin-icon{
    background:#e8f3ff;
}

.math-card .admin-icon{
    background:#ecfff3;
}

.general-card .admin-icon{
    background:#fff0f7;
}

@media(max-width:800px){
    .cards-grid{
        grid-template-columns:1fr;
        max-width:520px;
    }

    .page-title{
        font-size:30px;
    }
}
</style>
</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">إدارة الدروس</h1>

<div class="cards-grid">

<a
href="subject-lessons.php?subject=<?php echo urlencode('اللغة العربية'); ?>&type=<?php echo urlencode('الحروف العربية'); ?>"
class="admin-card arabic-card">

<div class="admin-icon">أ</div>
<h2>اللغة العربية</h2>
<span>الحروف</span>

</a>

<a
href="lesson-types.php?subject=<?php echo urlencode('اللغة الإنجليزية'); ?>"
class="admin-card english-card">

<div class="admin-icon">A</div>
<h2>اللغة الإنجليزية</h2>
<span>الحروف والأرقام</span>

</a>

<a
href="subject-lessons.php?subject=<?php echo urlencode('الرياضيات'); ?>&type=<?php echo urlencode('الأرقام العربية'); ?>"
class="admin-card math-card">

<div class="admin-icon">١٢٣</div>
<h2>الرياضيات</h2>
<span>الأرقام والأنشطة</span>

</a>

<a
href="lesson-types.php?subject=<?php echo urlencode('الثقافة العامة'); ?>"
class="admin-card general-card">

<div class="admin-icon">★</div>
<h2>الثقافة العامة</h2>
<span>الأقسام</span>

</a>

</div>

</main>

</div>

</body>
</html>