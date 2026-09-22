<?php
/* عدد الرسائل غير المقروءة */
$unread_count = 0;
if (file_exists(__DIR__ . '/../config/db.php')) {
    @include_once __DIR__ . '/../config/db.php';
    if (isset($conn)) {
        $r = mysqli_query($conn,
            "SELECT COUNT(*) as c FROM chat_messages WHERE sender='child' AND is_read=0"
        );
        if ($r) $unread_count = mysqli_fetch_assoc($r)['c'] ?? 0;
    }
}

/* الصفحة الحالية للتمييز */
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ══ Layout ══ */
.admin-layout {
    display: block;
}
.admin-content {
    margin-right: 240px;
    padding: 40px;
    transition: margin-right .3s ease;
}

/* ══ Sidebar Reset & Base ══ */
.admin-sidebar {
    width: 240px;
    flex-shrink: 0;
    background: linear-gradient(175deg, #0f2942 0%, #1a3f6f 55%, #1e5096 100%);
    display: flex;
    flex-direction: column;
    padding: 0;
    position: fixed;
    right: 0;
    top: 0;
    height: 100vh;
    box-shadow: 4px 0 24px rgba(0,0,0,.22);
    z-index: 1000;
    overflow: hidden;
    transition: width .3s ease;
}

/* ══ زر التبديل ══ */
.sidebar-open-btn {
    display: flex;
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    z-index: 1001;
    width: 22px; height: 52px;
    background: linear-gradient(175deg, #0f2942, #1a3f6f);
    border: none;
    border-radius: 8px 0 0 8px;
    cursor: pointer;
    color: #fff;
    font-size: 14px;
    box-shadow: -3px 0 10px rgba(0,0,0,.25);
    transition: right .3s ease, background .2s;
    align-items: center;
    justify-content: center;
}
.sidebar-open-btn:hover { background: #2a84c9; }

/* يتحرك مع السايدبار على الموبايل */
@media (max-width: 900px) {
    .admin-layout:has(.admin-sidebar.open) .sidebar-open-btn { right: 240px; }
}

/* يتحرك مع السايدبار على الديسكتوب */
@media (min-width: 901px) {
    .sidebar-open-btn { right: 240px; }
    .admin-layout:has(.admin-sidebar.collapsed) .sidebar-open-btn { right: 0; }
}

/* ══ سطح المكتب: انكماش ══ */
@media (min-width: 901px) {
    .admin-sidebar.collapsed {
        width: 0;
        padding: 0;
    }
    .admin-sidebar.collapsed > * { visibility: hidden; }
    .admin-layout:has(.admin-sidebar.collapsed) .admin-content { margin-right: 0; }
}

/* ══ تابلت وموبايل (≤900px) ══ */
@media (max-width: 900px) {
    .admin-sidebar { width: 0; }
    .admin-sidebar.open { width: 240px; }
    .admin-content { margin-right: 0; padding: 20px 16px; }
    .sidebar-open-btn { display: flex; }
    .admin-layout:has(.admin-sidebar.open)::before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 999;
    }
}

/* ══ خلفية زخرفية ══ */
.admin-sidebar::before {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    top: -80px;
    right: -80px;
    pointer-events: none;
}
.admin-sidebar::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.03);
    bottom: 60px;
    left: -60px;
    pointer-events: none;
}

/* ══ منطقة اللوجو ══ */
.sidebar-logo-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 20px 22px;
    border-bottom: 1px solid rgba(255,255,255,.1);
    position: relative;
}
.sidebar-logo-area img {
    height: 120px;
    object-fit: contain;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,.3));
    transition: transform .3s ease;
}
.sidebar-logo-area img:hover {
    transform: scale(1.05);
}
.sidebar-logo-label {
    font-size: 11px;
    font-weight: 800;
    color: rgba(255,255,255,.45);
    margin-top: 8px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* ══ معلومات الأدمن ══ */
.sidebar-admin-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 16px 16px 8px;
    background: rgba(255,255,255,.07);
    border-radius: 14px;
    padding: 10px 14px;
}
.sidebar-admin-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #42a5f5, #1565c0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 900;
    color: #fff;
    flex-shrink: 0;
}
.sidebar-admin-name {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sidebar-admin-role {
    font-size: 10px;
    color: rgba(255,255,255,.5);
    font-weight: 700;
    margin-top: 1px;
}

/* ══ عنوان القسم ══ */
.sidebar-section-title {
    font-size: 9px;
    font-weight: 900;
    color: rgba(255,255,255,.35);
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 14px 20px 6px;
}

/* ══ روابط التنقل ══ */
.admin-sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 4px 12px;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.admin-sidebar nav::-webkit-scrollbar { display: none; }

.admin-sidebar nav a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 800;
    color: rgba(255,255,255,.75);
    text-decoration: none;
    transition: all .22s ease;
    position: relative;
    overflow: hidden;
}
.admin-sidebar nav a .nav-icon {
    font-size: 17px;
    width: 22px;
    text-align: center;
    flex-shrink: 0;
    transition: transform .22s ease;
}
.admin-sidebar nav a:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
    transform: translateX(-3px);
}
.admin-sidebar nav a:hover .nav-icon {
    transform: scale(1.15);
}
.admin-sidebar nav a.active {
    background: rgba(255,255,255,.18);
    color: #fff;
    box-shadow: inset 3px 0 0 #42a5f5;
}
.admin-sidebar nav a.active .nav-icon {
    filter: drop-shadow(0 0 6px rgba(66,165,245,.7));
}

/* ══ Badge الرسائل ══ */
.nav-badge {
    margin-right: auto;
    background: #e53935;
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    padding: 2px 7px;
    border-radius: 999px;
    min-width: 20px;
    text-align: center;
    line-height: 1.5;
    animation: pulse-badge 1.8s infinite;
}
@keyframes pulse-badge {
    0%,100% { box-shadow: 0 0 0 0 rgba(229,57,53,.5); }
    50%      { box-shadow: 0 0 0 5px rgba(229,57,53,0); }
}

/* ══ فاصل ══ */
.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 8px 16px;
}

/* ══ زر تسجيل الخروج ══ */
.sidebar-logout {
    margin: 8px 12px 20px;
}
.sidebar-logout a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 900;
    color: #ff5252;
    text-decoration: none;
    background: rgba(229,57,53,.22);
    border: 1px solid rgba(255,82,82,.4);
    transition: all .22s ease;
}
.sidebar-logout a:hover {
    background: rgba(229,57,53,.38);
    color: #ff1744;
    border-color: rgba(255,23,68,.6);
    transform: translateX(-2px);
}
</style>

<aside class="admin-sidebar">

    <!-- رأس السايدبار -->
    <div class="sidebar-logo-area">
        <img src="../logo.png" alt="تمكين">
        <span class="sidebar-logo-label">لوحة التحكم</span>
    </div>

    <!-- معلومات الأدمن -->
    <?php
    if (($_SESSION['admin_role'] ?? '') === 'supervisor') {
        $supId = intval($_SESSION['supervisor_id'] ?? 0);
        $snRes = isset($conn) ? mysqli_query($conn, "SELECT full_name FROM supervisors WHERE id=$supId") : null;
        $snRow = $snRes ? mysqli_fetch_assoc($snRes) : null;
        $sidebarDisplayName = $snRow['full_name'] ?? ($_SESSION['admin_username'] ?? 'المشرف');
    } else {
        $sidebarDisplayName = $_SESSION['admin_username'] ?? 'المدير';
    }
    ?>
    <div class="sidebar-admin-info">
        <div class="sidebar-admin-avatar">
            <?php echo mb_substr($sidebarDisplayName, 0, 1, 'UTF-8'); ?>
        </div>
        <div>
            <div class="sidebar-admin-name"><?php echo htmlspecialchars($sidebarDisplayName); ?></div>
            <div class="sidebar-admin-role">
                <?php echo (($_SESSION['admin_role'] ?? '') === 'supervisor') ? 'مشرف' : 'مدير النظام'; ?>
            </div>
        </div>
    </div>

    <!-- روابط التنقل -->
    <div class="sidebar-section-title">القائمة الرئيسية</div>

    <nav>
        <?php if (($_SESSION['admin_role'] ?? '') === 'supervisor'): ?>
        <a href="supervisor-dashboard.php" class="<?php echo $current_page === 'supervisor-dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> الصفحة الرئيسية
        </a>
        <?php else: ?>
        <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> الرئيسية
        </a>
        <?php endif; ?>

        <a href="children.php" class="<?php echo $current_page === 'children.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👦</span> الأطفال
        </a>

        <a href="lessons.php" class="<?php echo $current_page === 'lessons.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📚</span> الدروس
        </a>

        <a href="games.php" class="<?php echo $current_page === 'games.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🎮</span> الألعاب
        </a>

        <a href="admin-chat.php" class="<?php echo $current_page === 'admin-chat.php' ? 'active' : ''; ?>">
            <span class="nav-icon">💬</span>
            <span>المحادثات</span>
            <?php if ($unread_count > 0): ?>
            <span class="nav-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>

        <a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span> التقارير
        </a>

        <?php if (($_SESSION['admin_role'] ?? '') !== 'supervisor'): ?>
        <a href="supervisors.php" class="<?php echo $current_page === 'supervisors.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span> إدارة المشرفين
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-divider"></div>

    <!-- تسجيل الخروج -->
    <div class="sidebar-logout">
        <a href="../auth/logout.php">
            <span style="font-size:17px;">🚪</span> تسجيل الخروج
        </a>
    </div>

</aside>

<!-- زر الفتح الخارجي -->
<button class="sidebar-open-btn" id="sidebarOpenBtn" title="فتح القائمة">›</button>

<script>
(function(){
    var sidebar  = document.querySelector('.admin-sidebar');
    var closeBtn = document.getElementById('sidebarCollapseBtn');
    var openBtn  = document.getElementById('sidebarOpenBtn');
    if (!sidebar) return;

    var isSmall   = function(){ return window.innerWidth <= 900; };
    var STORE_KEY = 'adminSidebarCollapsed';

    // سطح المكتب: استعادة الحالة المحفوظة
    if (!isSmall() && localStorage.getItem(STORE_KEY) === '1') {
        sidebar.classList.add('collapsed');
    }
    // شاشة صغيرة: يبدأ مغلق دائماً (لا نستعيد من localStorage)

    function collapse(){
        if (isSmall()){
            sidebar.classList.remove('open');
        } else {
            sidebar.classList.add('collapsed');
            localStorage.setItem(STORE_KEY, '1');
        }
    }
    function expand(){
        if (isSmall()){
            sidebar.classList.add('open');
        } else {
            sidebar.classList.remove('collapsed');
            localStorage.setItem(STORE_KEY, '0');
        }
    }

    function isOpen(){
        return isSmall() ? sidebar.classList.contains('open') : !sidebar.classList.contains('collapsed');
    }
    function toggle(){
        if(isOpen()) collapse(); else expand();
        updateArrow();
    }
    function updateArrow(){
        if(openBtn) openBtn.textContent = isOpen() ? '›' : '‹';
    }

    if (openBtn) openBtn.addEventListener('click', toggle);
    updateArrow();

    // إغلاق تلقائي عند الانتقال لصفحة جديدة (شاشة صغيرة)
    sidebar.querySelectorAll('a').forEach(function(a){
        a.addEventListener('click', function(){
            if (isSmall()){ collapse(); updateArrow(); }
        });
    });
})();

// منع الزوم على iOS
document.addEventListener('touchmove', function(e){
    if(e.touches.length > 1) e.preventDefault();
}, { passive: false });

var lastTouch = 0;
document.addEventListener('touchend', function(e){
    var now = Date.now();
    if(now - lastTouch < 300) e.preventDefault();
    lastTouch = now;
}, false);
</script>
