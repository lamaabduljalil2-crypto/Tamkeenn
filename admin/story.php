<?php
require_once __DIR__ . '/../config/session_child.php';
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

require_once __DIR__ . '/../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

/* تأكيد وجود الجداول (احتياط) */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    poster_image VARCHAR(255) DEFAULT NULL,
    sign_video VARCHAR(255) DEFAULT NULL,
    story_link VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
foreach ([
    'title'        => "ALTER TABLE stories ADD COLUMN title VARCHAR(255) NULL",
    'poster_image' => "ALTER TABLE stories ADD COLUMN poster_image VARCHAR(255) NULL",
    'sign_video'   => "ALTER TABLE stories ADD COLUMN sign_video VARCHAR(255) NULL",
    'story_link'   => "ALTER TABLE stories ADD COLUMN story_link VARCHAR(255) NULL",
    'sort_order'   => "ALTER TABLE stories ADD COLUMN sort_order INT DEFAULT 0",
    'cover_image'  => "ALTER TABLE stories ADD COLUMN cover_image VARCHAR(255) NULL",
    'cover_video'  => "ALTER TABLE stories ADD COLUMN cover_video VARCHAR(255) NULL",
    'cover_title'  => "ALTER TABLE stories ADD COLUMN cover_title VARCHAR(500) NULL",
] as $col => $alterSql) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM stories LIKE '$col'");
    if ($colCheck && mysqli_num_rows($colCheck) == 0) {
        mysqli_query($conn, $alterSql);
    }
}
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS story_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    page_order INT DEFAULT 0,
    image VARCHAR(255) NULL,
    sign_video VARCHAR(255) NULL,
    caption VARCHAR(1000) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$storyId = intval($_GET['id'] ?? 0);

/* جلب القصة */
$stmt = mysqli_prepare($conn, "SELECT * FROM stories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $storyId);
mysqli_stmt_execute($stmt);
$story = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$story) {
    header("Location: stories.php");
    exit;
}

/* جلب صفحات القصة */
$pages = [];
$pStmt = mysqli_prepare($conn, "SELECT * FROM story_pages WHERE story_id = ? ORDER BY page_order ASC, id ASC");
mysqli_stmt_bind_param($pStmt, "i", $storyId);
mysqli_stmt_execute($pStmt);
$pRes = mysqli_stmt_get_result($pStmt);
while ($pr = mysqli_fetch_assoc($pRes)) {
    $pages[] = $pr;
}

/* إذا القصة قديمة (ملف خاص) وما إلها صفحات بقاعدة البيانات، حوّل لرابطها */
if (empty($pages) && !empty($story['story_link']) && strpos($story['story_link'], 'story.php') === false) {
    header("Location: " . $story['story_link']);
    exit;
}

function pubPath($p) {
    $p = trim((string)$p);
    if ($p === '') return '';
    return '../../' . ltrim($p, '/');
}

/* بيانات الغلاف */
$coverImage = pubPath($story['cover_image'] ?? '');
if ($coverImage === '') {
    $coverImage = pubPath($story['poster_image'] ?? '');
}
$coverVideo = pubPath($story['cover_video'] ?? '');
$coverTitle = trim((string)($story['cover_title'] ?? ''));
if ($coverTitle === '') {
    $coverTitle = 'اضغط على الغلاف لفتح القصة';
}
$storyTitle = trim((string)($story['title'] ?? 'قصة'));

/* تسجيل تقدّم الطفل: قرأ قصة (الدالة مكتوبة جوّا الصفحة، ما بتحتاج ملف خارجي) */
if (!function_exists('track_progress')) {
    function track_progress($conn, $type, $key, $label = '') {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') return;
        if (!$conn || !in_array($type, ['lesson','story','game','section'], true)) return;
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            child_name VARCHAR(255) NULL,
            activity_type VARCHAR(20) NOT NULL,
            activity_key VARCHAR(100) NOT NULL,
            activity_label VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_progress (user_id, activity_type, activity_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $uid = intval($_SESSION['user_id']);
        $cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
        $key = substr((string)$key, 0, 100);
        $label = substr((string)$label, 0, 255);
        if ($key === '') return;
        $st = mysqli_prepare($conn, "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE activity_label = VALUES(activity_label), created_at = CURRENT_TIMESTAMP");
        if ($st) {
            mysqli_stmt_bind_param($st, "issss", $uid, $cname, $type, $key, $label);
            mysqli_stmt_execute($st);
        }
    }
}
track_progress($conn, 'story', 'story-' . $storyId, $storyTitle);

/* بناء بيانات لوحة الإشارة لكل صفحة (JS) */
$pageData = [
    'cover' => ['title' => $coverTitle, 'video' => $coverVideo],
];
foreach ($pages as $i => $pg) {
    $pageData['page' . ($i + 1)] = [
        'title' => trim((string)($pg['caption'] ?? '')),
        'video' => pubPath($pg['sign_video'] ?? ''),
    ];
}

$totalPages = count($pages);
$coverZ = $totalPages + 20;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title><?php echo htmlspecialchars($storyTitle); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">

<style>
*{
  box-sizing:border-box;
}

body{
  margin:0;
  font-family:"Cairo",sans-serif;
  background:linear-gradient(135deg,#eaf5ff 0%,#fff2d9 100%);
  min-height:100vh;
  overflow:hidden;
}

/* ================= DASH ================= */

.children-dash{
  position:fixed;
  top:16px;
  left:50%;
  transform:translateX(-50%);
  width:min(1120px,94vw);
  z-index:1000;
}

.children-dash-inner{
  height:86px;
  background:rgba(255,255,255,.9);
  backdrop-filter:blur(12px);
  border-radius:28px;
  box-shadow:0 18px 40px rgba(0,0,0,.12);
  display:grid;
  grid-template-columns:1fr auto 1fr;
  align-items:center;
  padding:10px 22px;
  border:1px solid rgba(255,255,255,.7);
}

.dash-start{ display:flex; justify-content:flex-start; }
.dash-center{ display:flex; justify-content:center; }
.dash-end{ display:flex; justify-content:flex-end; }

.logo-box{
  display:flex;
  align-items:center;
  gap:12px;
  text-decoration:none;
}

.logo-box img{
  width:58px;
  height:58px;
  object-fit:contain;
}

.dash-nav{
  display:flex;
  align-items:center;
  gap:18px;
}

.circle-icon{
  width:70px;
  height:70px;
  border-radius:50%;
  overflow:visible;
  display:flex;
  align-items:center;
  justify-content:center;
  background:#fff;
  box-shadow:0 6px 16px rgba(0,0,0,0.12);
  transition:.25s ease;
  text-decoration:none;
  position:relative;
}

.circle-icon:hover{
  transform:translateY(-3px) scale(1.05);
  box-shadow:0 10px 20px rgba(0,0,0,0.18);
}

.circle-icon img,
.circle-icon video{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
  border-radius:50%;
}

.nav-text{
  position:absolute;
  top:calc(100% + 8px);
  left:50%;
  transform:translateX(-50%) translateY(-6px);
  opacity:0;
  pointer-events:none;
  white-space:nowrap;
  background:linear-gradient(135deg,#ff7aa8,#ff5f8f);
  color:#fff;
  padding:7px 16px;
  border-radius:999px;
  font-size:15px;
  font-weight:900;
  transition:.22s ease;
  z-index:50;
  box-shadow:0 8px 18px rgba(255,94,143,.30);
}

.circle-icon:hover .nav-text{
  opacity:1;
  transform:translateX(-50%) translateY(0);
}

.profile-wrap{ position:relative; }

.profile-btn{
  border:none;
  background:linear-gradient(135deg,#ffe9b6,#dff5ff);
  border-radius:999px;
  padding:7px 14px 7px 8px;
  display:flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  box-shadow:0 10px 22px rgba(0,0,0,.10);
  font-family:"Cairo",sans-serif;
}

.profile-video-box{
  width:52px;
  height:52px;
  border-radius:50%;
  overflow:hidden;
  border:3px solid #fff;
  background:#fff;
}

.profile-video-box video{
  width:100%;
  height:100%;
  object-fit:cover;
}

.profile-hello{
  color:#244d6b;
  font-weight:900;
  font-size:15px;
  white-space:nowrap;
}

.profile-menu{
  position:absolute;
  top:70px;
  left:0;
  min-width:180px;
  background:#fff;
  border-radius:18px;
  box-shadow:0 16px 34px rgba(0,0,0,.16);
  overflow:hidden;
  display:none;
  z-index:2000;
}

.profile-menu.show{ display:block; }

.profile-menu a{
  display:block;
  padding:12px 16px;
  text-decoration:none;
  color:#244d6b;
  font-weight:800;
  font-size:14px;
}

.profile-menu a:hover{ background:#eef8ff; }

/* ================= MAIN ================= */

.main-scene{
  width:100vw;
  height:100vh;
  padding-top:110px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:25px;
  direction:rtl;
}

/* ================= فيديو الإشارة ================= */

.sign-panel{
  width:320px;
  height:560px;
  border-radius:30px;
  background:rgba(255,255,255,.9);
  box-shadow:0 24px 54px rgba(0,0,0,.14);
  padding:18px;
  display:flex;
  flex-direction:column;
  align-items:center;
  margin-top:20px;
  margin-left:30px;
}

.sign-title{
  width:100%;
  min-height:85px;
  background:linear-gradient(135deg,#fff7d9,#e3f6ff);
  border-radius:22px;
  padding:13px 15px;
  color:#244d6b;
  font-size:18px;
  font-weight:900;
  text-align:center;
  line-height:1.7;
  display:flex;
  align-items:center;
  justify-content:center;
}

.sign-video-wrap{
  margin-top:17px;
  width:100%;
  flex:1;
  border-radius:26px;
  overflow:hidden;
  background:#fff;
  border:7px solid #fff;
  box-shadow:0 16px 34px rgba(0,0,0,.13);
  position:relative;
}

.sign-video-wrap video{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.play-overlay{
  position:absolute;
  inset:0;
  display:grid;
  place-items:center;
  background:rgba(255,255,255,.16);
  cursor:pointer;
}

.play-circle{
  width:72px;
  height:72px;
  border-radius:50%;
  border:none;
  background:rgba(255,255,255,.94);
  color:#245475;
  font-size:34px;
  font-weight:900;
  box-shadow:0 12px 30px rgba(0,0,0,.18);
  cursor:pointer;
}

/* ================= الكتاب ================= */

.book-side{
  direction:rtl;
  transform:translate(55px,-10px);
}

.stage{
  width:min(920px,68vw);
  height:min(560px,62vh);
  position:relative;
  display:flex;
  justify-content:center;
  align-items:center;
  perspective:3200px;
}

.book-shadow{
  position:absolute;
  width:74%;
  height:44px;
  bottom:5px;
  background:radial-gradient(circle,rgba(0,0,0,.24),rgba(0,0,0,0));
  filter:blur(12px);
  border-radius:50%;
  z-index:0;
}

.book{
  width:min(780px,62vw);
  height:min(500px,56vh);
  position:relative;
  transform-style:preserve-3d;
  z-index:2;
}

.book-base{
  position:absolute;
  inset:0;
  border-radius:26px;
  background:linear-gradient(135deg,#fffdf8,#f1e4cf);
  box-shadow:
  0 30px 70px rgba(0,0,0,.20),
  inset 0 0 0 1px rgba(120,85,45,.08);
  overflow:hidden;
}

.spine{
  position:absolute;
  top:0;
  bottom:0;
  right:0;
  width:28px;
  background:linear-gradient(180deg,#8d5524,#6e3e13,#9a6632);
  border-radius:0 26px 26px 0;
  z-index:5;
}

.page-stack-lines{
  position:absolute;
  top:30px;
  bottom:30px;
  left:92px;
  right:92px;
  border-radius:16px;
  background:
  repeating-linear-gradient(
  to bottom,
  rgba(196,174,148,.55) 0px,
  rgba(196,174,148,.55) 2px,
  rgba(255,255,255,0) 2px,
  rgba(255,255,255,0) 8px
  );
  opacity:.18;
  z-index:1;
}

.page-bed{
  position:absolute;
  top:36px;
  bottom:36px;
  left:84px;
  right:84px;
  border-radius:16px;
  background:linear-gradient(180deg,#fffefb,#f9f1e5);
  overflow:hidden;
  z-index:1;
}

/* ================= الصفحات ================= */

.page{
  position:absolute;
  top:20px;
  bottom:20px;
  left:54px;
  right:54px;
  transform-origin:top right;
  transform-style:preserve-3d;
  transition:transform 1.6s cubic-bezier(.22,.74,.12,1);
  cursor:pointer;
}

.page.flipped{
  transform:rotateY(180deg) rotateZ(-3deg);
}

.page-face{
  position:absolute;
  inset:0;
  backface-visibility:hidden;
  overflow:hidden;
  border:none !important;
  box-shadow:none !important;
  background:transparent !important;
}

.page-face.back{
  transform:rotateY(180deg);
}

.cover-page{
  top:14px;
  bottom:14px;
  left:48px;
  right:48px;
}

.cover-page .page-face.front{
  border-radius:22px;
  overflow:hidden;
}

/* ================= الصور ================= */

.page-image-wrap{
  width:100%;
  height:100%;
  padding:0;
  margin:0;
  background:transparent;
  overflow:hidden;
  display:flex;
  align-items:center;
  justify-content:center;
}

.page-image{
  width:100%;
  height:100%;
  display:block;
  object-fit:cover;
  margin:0;
  padding:0;
  border:none !important;
  outline:none !important;
  box-shadow:none !important;
  background:transparent;
}

.page:not(.cover-page) .page-face.front{
  border-radius:18px;
  overflow:hidden;
}

.page:not(.cover-page) .page-image{
  object-fit:cover;
  border-radius:18px;
}

.cover-page .page-image{
  object-fit:cover;
  border-radius:22px;
}

.page-face.front::after,
.page-face.back::after{
  display:none !important;
}

/* ================= الأزرار ================= */

.book-controls{
  position:absolute;
  bottom:-95px;
  left:50%;
  transform:translateX(-50%);
  display:flex;
  align-items:center;
  justify-content:center;
  gap:22px;
  z-index:999;
}

.nav{
  width:72px;
  height:72px;
  border:none;
  border-radius:50%;
  background:#fff;
  box-shadow:0 14px 28px rgba(0,0,0,.18);
  font-size:40px;
  font-weight:900;
  color:#264d6c;
  cursor:pointer;
  transition:.25s ease;
}

.nav:hover{ transform:scale(1.08); }

.nav:disabled{
  opacity:.35;
  cursor:default;
  transform:none;
}
</style>
</head>

<body>

<header class="children-dash">
  <div class="children-dash-inner">

    <div class="dash-start">
      <a href="../../index.php" class="logo-box" tabindex="0">
        <img src="../../logo.png" alt="logo">
      </a>
    </div>

    <div class="dash-center">
      <nav class="dash-nav">
        <a href="../../auth/children.php"
           class="circle-icon home-icon"
           tabindex="0"
           aria-label="العودة إلى الصفحة الرئيسية">
          <video class="nav-icon-video" autoplay muted loop playsinline>
            <source src="../../assets/icons/children.mp4" type="video/mp4">
          </video>
          <span class="nav-text">صفحة الطفل</span>
        </a>

        <a href="stories.php"
           class="circle-icon home-icon"
           tabindex="0"
           aria-label="القصص">
          <video class="nav-icon-video" autoplay muted loop playsinline>
            <source src="../../assets/icons/story.mp4" type="video/mp4">
          </video>
          <span class="nav-text">القصص </span>
        </a>
      </nav>
    </div>

    <div class="dash-end">
      <div class="profile-wrap">
        <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
          <div class="profile-video-box">
            <video autoplay muted loop playsinline>
              <source src="../../assets/icons/profile.mp4" type="video/mp4">
            </video>
          </div>
          <span class="profile-hello">مرحباً <?php echo htmlspecialchars($child_display_name); ?></span>
        </button>

        <div class="profile-menu" id="profileMenu">
          <a href="../../auth/account_settings.php">إعدادات الحساب</a>
          <a href="../../auth/logout.php">تسجيل الخروج</a>
        </div>
      </div>
    </div>

  </div>
</header>

<main class="main-scene">

  <!-- الكتاب -->
  <section class="book-side">
    <div class="stage">
      <div class="book-shadow"></div>

      <div class="book" id="book">
        <div class="book-base">
          <div class="spine"></div>
          <div class="page-bed"></div>
          <div class="page-stack-lines"></div>
        </div>

        <!-- الغلاف -->
        <div class="page cover-page" data-page="cover" style="z-index:<?php echo $coverZ; ?>;">
          <div class="page-face front">
            <div class="page-image-wrap">
              <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="غلاف الكتاب" class="page-image">
            </div>
          </div>
          <div class="page-face back">
            <div class="page-image-wrap" style="background:#fdf6ea;"></div>
          </div>
        </div>

        <?php foreach ($pages as $i => $pg): ?>
        <?php
            $pageNum = $i + 1;
            $pageImg = pubPath($pg['image'] ?? '');
            $pageZ = $coverZ - $pageNum;
        ?>
        <!-- الصفحة <?php echo $pageNum; ?> -->
        <div class="page" data-page="<?php echo $pageNum; ?>" style="z-index:<?php echo $pageZ; ?>;">
          <div class="page-face front">
            <div class="page-image-wrap">
              <img src="<?php echo htmlspecialchars($pageImg); ?>" alt="الصفحة <?php echo $pageNum; ?>" class="page-image">
            </div>
          </div>
          <div class="page-face back">
            <div class="page-image-wrap" style="background:#fffdf8;"></div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>

      <div class="book-controls">
        <button class="nav prev" id="prevBtn">‹</button>
        <button class="nav next" id="nextBtn">›</button>
      </div>
    </div>
  </section>

  <!-- فيديو لغة الإشارة -->
  <aside class="sign-panel">
    <div class="sign-title" id="signTitle">
      <?php echo htmlspecialchars($coverTitle); ?>
    </div>

    <div class="sign-video-wrap">
      <video id="signVideo" playsinline>
        <source id="signVideoSource" src="<?php echo htmlspecialchars($coverVideo); ?>" type="video/mp4">
      </video>

      <div class="play-overlay" id="playOverlay">
        <button class="play-circle" type="button">▶</button>
      </div>
    </div>
  </aside>

</main>

<script>
  const pages = Array.from(document.querySelectorAll('.page'));
  const nextBtn = document.getElementById('nextBtn');
  const prevBtn = document.getElementById('prevBtn');

  const signTitle = document.getElementById('signTitle');
  const signVideo = document.getElementById('signVideo');
  const signVideoSource = document.getElementById('signVideoSource');
  const playOverlay = document.getElementById('playOverlay');

  const pageData = <?php echo json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  const TOTAL_CONTENT_PAGES = <?php echo $totalPages; ?>;

  let current = -1;
  let busy = false;

  function getVisiblePageKey() {
    if (current < 0) return "cover";
    let p = current + 1;
    if (p > TOTAL_CONTENT_PAGES) p = TOTAL_CONTENT_PAGES;
    if (p < 1) p = 1;
    return "page" + p;
  }

  function updateSignContent() {
    const key = getVisiblePageKey();
    const data = pageData[key];
    if (!data) return;

    signTitle.textContent = data.title || "";

    signVideo.pause();
    signVideo.currentTime = 0;

    if (data.video) {
      signVideoSource.src = data.video;
      signVideo.load();
      playOverlay.style.display = "grid";
    } else {
      signVideoSource.src = "";
      signVideo.load();
      playOverlay.style.display = "none";
    }
  }

  function syncButtons() {
    prevBtn.disabled = current < 0;
    nextBtn.disabled = current >= pages.length - 1;
  }

  function flipNext() {
    if (busy || current >= pages.length - 1) return;

    busy = true;
    current += 1;

    updateSignContent();

    pages[current].classList.add('flipped');

    setTimeout(() => {
      busy = false;
      syncButtons();
    }, 1050);
  }

  function flipPrev() {
    if (busy || current < 0) return;

    busy = true;

    pages[current].classList.remove('flipped');
    current -= 1;

    updateSignContent();

    setTimeout(() => {
      busy = false;
      syncButtons();
    }, 1050);
  }

  nextBtn.addEventListener('click', flipNext);
  prevBtn.addEventListener('click', flipPrev);

  /* الضغط على الغلاف يفتح الصفحة الأولى */
  if (pages[0]) {
    pages[0].addEventListener('click', () => {
      if (current === -1) {
        flipNext();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') flipNext();
    if (e.key === 'ArrowRight') flipPrev();
  });

  playOverlay.addEventListener('click', () => {
    if (!signVideoSource.src) return;
    signVideo.play();
    playOverlay.style.display = "none";
  });

  signVideo.addEventListener('click', () => {
    if (!signVideoSource.src) return;
    if (signVideo.paused) {
      signVideo.play();
      playOverlay.style.display = "none";
    } else {
      signVideo.pause();
      playOverlay.style.display = "grid";
    }
  });

  signVideo.addEventListener('ended', () => {
    playOverlay.style.display = "grid";
  });

  const profileBtn = document.getElementById('profileBtn');
  const profileMenu = document.getElementById('profileMenu');

  profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileMenu.classList.toggle('show');
  });

  document.addEventListener('click', () => {
    profileMenu.classList.remove('show');
  });

  syncButtons();
  updateSignContent();
</script> 

</body>
</html>
