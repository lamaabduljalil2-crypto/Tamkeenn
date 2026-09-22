
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================================================
   منع كاش الصفحة والفيديوهات
   ========================================================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../../config/session_child.php';

$lang = $_SESSION["lang"] ?? "ar";
$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

require_once '../../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

/* =========================================================
   جلب الحرف
   ========================================================= */
$sql = "SELECT * FROM arabic_letter_examples WHERE letter_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dbItem = mysqli_fetch_assoc($result);

if (!$dbItem) {
    die("الحرف غير موجود");
}

$item = [
    "letter" => $dbItem["letter_text"],
    "name"   => $dbItem["letter_text"],
    "word"   => $dbItem["start_word"]
];

/* =========================================================
   تتبع التقدم
   ========================================================= */
if (!function_exists('track_progress')) {
    function track_progress($conn, $type, $key, $label = '') {

        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') {
            return;
        }

        if (!$conn || !in_array($type, ['lesson','story','game','section'], true)) {
            return;
        }

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
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ");

        $uid = intval($_SESSION['user_id']);
        $cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';

        $key = substr((string)$key, 0, 100);
        $label = substr((string)$label, 0, 255);

        if ($key === '') {
            return;
        }

        $st = mysqli_prepare(
            $conn,
            "INSERT INTO progress
            (user_id, child_name, activity_type, activity_key, activity_label)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
            activity_label = VALUES(activity_label),
            created_at = CURRENT_TIMESTAMP"
        );

        if ($st) {
            mysqli_stmt_bind_param(
                $st,
                "issss",
                $uid,
                $cname,
                $type,
                $key,
                $label
            );

            mysqli_stmt_execute($st);
        }
    }
}

track_progress(
    $conn,
    'game',
    'arabic-quiz-' . $id,
    'لعبة السمكة'
);

$childName = $_SESSION['child_name'] ?? $_SESSION['full_name'] ?? "الطفل";

$backUrl = "arabic-letter.php?id=" . $id;

/* =========================================================
   صورة إشارة الحرف
   ========================================================= */
if ($id > 30 && !empty($dbItem['custom_sign'])) {

    $signImage = "../../" . ltrim($dbItem['custom_sign'], '/');

} else {

    $signImage = "../../images/signs/arabic/" . $id . ".png";
}

/* =========================================================
   الفيديوهات
   =========================================================
   مهم:
   يتم إعطاء كل فيديو رقم نسخة مختلف حتى لا يأخذ
   المتصفح الفيديو القديم من الكاش.
   ========================================================= */
$video_cache_version = time();

$guideVideo = "/kids/images/videos/game-fish-sign.mp4";
$winVideo   = "/kids/images/videos/cups-win.mp4";
$loseVideo  = "/kids/images/videos/cups-lose.mp4";

/* روابط الفيديو مع منع الكاش */
$guideVideoFresh = $guideVideo . "?v=" . $video_cache_version;
$winVideoFresh   = $winVideo . "?v=" . $video_cache_version;
$loseVideoFresh  = $loseVideo . "?v=" . $video_cache_version;

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

  <meta charset="UTF-8" />

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  />

  <title>
    اختبار حرف <?php echo htmlspecialchars($item['name']); ?>
  </title>

  <link
    rel="stylesheet"
    href="../../assets/css/letter-style.css"
  />

  <link
    rel="stylesheet"
    href="../../assets/css/quiz-game.css"
  />

  <link
    rel="stylesheet"
    href="../../assets/css/arabic-grid.css?v=2"
  />

<style>

*{
  box-sizing:border-box;
}

body{
  margin:0;
  direction:rtl;
  font-family:Arial, sans-serif;
  background:linear-gradient(
    135deg,
    #e9fff0 0%,
    #fff7f7 50%,
    #ffd7ea 100%
  );
  color:#12345a;
  overflow-x:hidden;
}

a{
  text-decoration:none;
  color:inherit;
}

/* ===== الهيدر - نفس arabic-letter.php ===== */

.children-dash{
  width:100% !important;
  height:110px !important;
  margin:0 !important;
  background:#d8f3ff !important;
  border-radius:0 !important;
  box-shadow:0 4px 15px rgba(0,0,0,0.12) !important;
  position:relative;
  z-index:1000;
}

.children-dash-inner{
  width:100% !important;
  height:100% !important;
  display:flex !important;
  flex-direction:row !important;
  align-items:center !important;
  justify-content:space-between !important;
  padding:0 35px !important;
  min-height:auto !important;
  gap:22px !important;
}

.logo-box{
  display:flex;
  align-items:center;
  justify-content:center;
}

.logo-box img{
  width:95px !important;
  height:auto !important;
  object-fit:contain;
}

.dash-nav{
  display:flex !important;
  flex-direction:row !important;
  align-items:center !important;
  justify-content:center !important;
  gap:22px !important;
  transform:none !important;
  flex:1;
}

.circle-icon{
  width:78px !important;
  height:78px !important;
  border-radius:50%;
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  box-shadow:0 4px 12px rgba(0,0,0,0.12);
  transition:.25s ease;
  position:relative;
}

.circle-icon:hover{
  transform:scale(1.04);
}

.circle-icon video,
.circle-icon img{
  width:100% !important;
  height:100% !important;
  object-fit:cover;
}

.nav-video-item{
  position:relative !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
}

.icon-label{
  display:block !important;
  position:absolute !important;
  bottom:-30px !important;
  left:50% !important;
  transform:translateX(-50%) !important;
  background:#ff5e8a !important;
  color:white !important;
  padding:6px 16px !important;
  border-radius:20px !important;
  font-size:15px !important;
  font-weight:bold !important;
  white-space:nowrap !important;
  opacity:0 !important;
  visibility:hidden !important;
  transition:.2s ease !important;
}

.nav-video-item:hover .icon-label,
.nav-video-item:focus .icon-label{
  opacity:1 !important;
  visibility:visible !important;
}

.nav-text{
  display:none !important;
}

.profile-wrap{
  position:relative;
  flex-shrink:0;
}

.profile-btn{
  border:0;
  background:#fff;
  border-radius:50px;
  padding:10px 18px !important;
  display:flex;
  align-items:center;
  gap:12px !important;
  cursor:pointer;
  box-shadow:0 4px 12px rgba(0,0,0,0.12);
  font-family:Arial, sans-serif;
}

.profile-hello{
  font-size:18px !important;
  font-weight:900;
  color:#12345a;
}

.profile-video-box{
  width:55px !important;
  height:55px !important;
  border-radius:50%;
  overflow:hidden;
}

.profile-video-box video{
  width:100%;
  height:100%;
  object-fit:cover;
}

.profile-menu{
  position:absolute;
  top:85px;
  left:0;
  min-width:220px;
  background:#fff;
  border-radius:20px;
  display:none;
  flex-direction:column;
  overflow:hidden;
  box-shadow:0 10px 25px rgba(0,0,0,0.12);
  z-index:3000;
}

.profile-menu.show{
  display:flex !important;
}

.profile-menu a{
  padding:15px 18px;
  font-size:17px;
  font-weight:900;
  color:#7b3f20;
}

.profile-menu a:hover{
  background:#fff3ea;
}

/* ===== الصفحة ===== */

.quiz-page-shell{
  width:100%;
  display:flex;
  justify-content:center;
  padding:12px 0 34px;
}

.quiz-page{
  width:100%;
  display:flex;
  justify-content:center;
}

.quiz-card{
  width:min(1440px,92vw) !important;
  margin:0 auto !important;
  background:rgba(255,255,255,.92);
  border-radius:34px;
  padding:12px 24px 26px !important;
  box-shadow:0 18px 45px rgba(0,0,0,.10);
  overflow:visible !important;
}

.sea-wrap{
  width:100% !important;
  display:grid !important;
  grid-template-columns:minmax(780px,1030px) 230px !important;
  gap:24px !important;
  align-items:start !important;
  justify-content:center !important;
  margin:0 auto !important;
  direction:ltr !important;
}

.sea-area{
  grid-column:1 !important;
  width:100% !important;
  max-width:1030px !important;
  min-width:780px !important;
  height:535px !important;
  min-height:535px !important;
  margin:0 !important;
  transform:translateX(-35px) !important;
  direction:rtl !important;
  position:relative !important;
  border-radius:28px !important;
  overflow:hidden !important;
  margin-left:20px !important;
  margin-top:50px !important;
}

.media-side-stack{
  grid-column:2 !important;
  width:230px !important;
  min-width:230px !important;
  max-width:230px !important;
  display:flex !important;
  flex-direction:column !important;
  gap:14px !important;
  direction:rtl !important;
  transform:translateY(-18px) !important;
  position:relative !important;
  z-index:5 !important;
}

.side-box{
  background:#fff !important;
  border:2px solid #dff2ff !important;
  border-radius:24px !important;
  box-shadow:0 10px 24px rgba(0,0,0,.08) !important;
  padding:10px !important;
}

.sign-side-box{
  min-height:150px !important;
  margin-top:40px !important;
}

.sign-preview-box{
  height:130px !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
}

.sign-preview-img{
  width:125px !important;
  height:125px !important;
  object-fit:contain !important;
  border-radius:50% !important;
}

.sign-fallback{
  width:120px;
  height:120px;
  border-radius:50%;
  align-items:center;
  justify-content:center;
  background:#fff4dd;
  color:#7b3f20;
  font-size:54px;
  font-weight:900;
}

.video-side-box{
  min-height:350px !important;
  margin-top:0 !important;
}

.video-preview-box{
  height:100% !important;
  display:flex !important;
  flex-direction:column !important;
  gap:8px !important;
}

.video-result-title{
  width:100%;
  text-align:center;
  font-family:Arial, sans-serif;
  font-size:22px !important;
  font-weight:900;
  color:#7b3f20;
  line-height:1.35;
  margin:0 !important;
  padding:0 4px 4px !important;
  background:transparent !important;
}

.video-wrapper{
  position:relative !important;
  width:100% !important;
  height:300px !important;
  min-height:300px !important;
  border-radius:20px !important;
  overflow:hidden !important;
  background:#f3e4d8 !important;
  box-shadow:0 10px 22px rgba(91,67,48,.14) !important;
}

.guide-video{
  width:100% !important;
  height:100% !important;
  min-height:300px !important;
  object-fit:contain !important;
  display:block !important;
  background:#f3e4d8 !important;
}

.play-button{
  position:absolute !important;
  inset:0 !important;
  margin:auto !important;
  width:76px !important;
  height:76px !important;
  border-radius:50% !important;
  background:rgba(255,255,255,.92) !important;
  color:#8a5737 !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
  font-size:34px !important;
  font-weight:900 !important;
  cursor:pointer !important;
  box-shadow:0 10px 24px rgba(0,0,0,.18) !important;
  z-index:5 !important;
  transition:.22s ease !important;
}

.play-button:hover{
  transform:scale(1.08);
}

.play-button.hide{
  opacity:0 !important;
  visibility:hidden !important;
  pointer-events:none !important;
}

.sea-stats{
  position:absolute !important;
  top:14px !important;
  left:14px !important;
  right:auto !important;
  display:flex !important;
  flex-direction:row-reverse !important;
  gap:12px !important;
  z-index:15 !important;
}

.sea-stat{
  min-width:120px !important;
  background:rgba(255,255,255,.18) !important;
  backdrop-filter:blur(8px) !important;
  border:1px solid rgba(255,255,255,.22) !important;
  border-radius:20px !important;
  padding:12px 16px !important;
  text-align:center !important;
  color:#fff !important;
  box-shadow:0 8px 20px rgba(0,0,0,.12) !important;
}

.sea-stat-label{
  display:block !important;
  font-size:17px !important;
  font-weight:900 !important;
  margin-bottom:8px !important;
}

.hearts{
  font-size:28px !important;
}

#score{
  font-size:32px !important;
  font-weight:900 !important;
  color:#ffe66d !important;
}

.sea-total{
  font-size:24px !important;
  font-weight:900 !important;
}

.end-actions{
  position:absolute !important;
  inset:0 !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
  z-index:50 !important;
}

.end-box{
  background:rgba(255,255,255,.94) !important;
  border-radius:30px !important;
  padding:24px 30px !important;
  box-shadow:0 18px 40px rgba(35,72,110,.20) !important;
  text-align:center !important;
}

.end-text{
  font-size:28px !important;
  font-weight:900 !important;
  color:#21425f !important;
  margin-bottom:18px !important;
  line-height:1.5 !important;
}

.end-buttons-row{
  display:flex !important;
  justify-content:center !important;
  align-items:flex-start !important;
  gap:22px !important;
}

.end-button-item{
  display:flex !important;
  flex-direction:column !important;
  align-items:center !important;
  gap:7px !important;
}

.quiz-btn.icon-btn{
  width:68px !important;
  height:58px !important;
  border:none !important;
  border-radius:16px !important;
  cursor:pointer !important;
  color:#fff !important;
  font-size:27px !important;
  font-weight:900 !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
  box-shadow:0 10px 22px rgba(92,119,255,.25) !important;
  transition:.22s ease !important;
}

#retryBtn{
  background:#ffd900 !important;
}

#backToLetterBtn{
  background:#df5459 !important;
}

.quiz-btn.icon-btn:hover{
  transform:translateY(-2px) scale(1.04) !important;
  filter:brightness(1.05) !important;
}

.end-button-label{
  font-family:Arial, sans-serif !important;
  font-size:15px !important;
  font-weight:900 !important;
  color:#7b5237 !important;
}

.hidden{
  display:none !important;
}

#confettiCanvas{
  position:fixed;
  inset:0;
  width:100vw;
  height:100vh;
  pointer-events:none;
  z-index:9999;
}

/* ===== Responsive ===== */

@media(max-width:1100px){

  .quiz-card{
    width:96vw !important;
    padding:10px 14px 20px !important;
  }

  .sea-wrap{
    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    direction:rtl !important;
    gap:16px !important;
  }

  .sea-area{
    min-width:0 !important;
    width:100% !important;
    height:420px !important;
    min-height:420px !important;
    transform:none !important;
    margin:0 !important;
  }

  .media-side-stack{
    width:100% !important;
    min-width:0 !important;
    max-width:100% !important;
    flex-direction:row !important;
    transform:none !important;
    gap:12px !important;
  }

  .sign-side-box{
    min-height:auto !important;
    margin-top:0 !important;
    flex:0 0 160px !important;
  }

  .video-side-box{
    min-height:auto !important;
    flex:1 !important;
  }

  .video-wrapper{
    height:200px !important;
    min-height:200px !important;
  }

  .guide-video{
    min-height:200px !important;
  }

  .sign-preview-img{
    width:100px !important;
    height:100px !important;
  }

  .sign-preview-box{
    height:110px !important;
  }
}

@media(max-width:768px){

  .children-dash{
    height:auto !important;
  }

  .children-dash-inner{
    display:grid !important;
    grid-template-columns:auto 1fr auto !important;
    padding:8px 12px !important;
    min-height:auto !important;
    height:auto !important;
    gap:8px !important;
    align-items:center !important;
  }

  .dash-start{
    min-width:unset !important;
  }

  .logo-box img{
    width:44px !important;
    height:44px !important;
  }

  .logo-text{
    display:none !important;
  }

  .dash-nav{
    gap:12px !important;
    flex-wrap:nowrap !important;
    justify-content:center !important;
    transform:none !important;
  }

  .circle-icon{
    width:44px !important;
    height:44px !important;
  }

  .circle-icon video,
  .circle-icon img{
    width:38px !important;
    height:38px !important;
  }

  .nav-video-item{
    width:auto !important;
    height:44px !important;
    display:flex !important;
    align-items:center !important;
  }

  .icon-label{
    display:none !important;
  }

  .profile-btn{
    padding:5px 8px !important;
    gap:6px !important;
  }

  .profile-video-box{
    width:38px !important;
    height:38px !important;
  }

  .profile-hello{
    display:block !important;
    font-size:11px !important;
    max-width:60px !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    white-space:nowrap !important;
  }

  .profile-menu{
    top:calc(100% + 8px) !important;
    left:0 !important;
    right:auto !important;
  }

  .sea-area{
    height:360px !important;
    min-height:360px !important;
  }

  .sign-side-box{
    flex:0 0 130px !important;
  }

  .sign-preview-img{
    width:80px !important;
    height:80px !important;
  }

  .video-wrapper{
    height:170px !important;
    min-height:170px !important;
  }

  .guide-video{
    min-height:170px !important;
  }

  .video-result-title{
    font-size:16px !important;
  }

  .sea-stat{
    min-width:90px !important;
    padding:8px 10px !important;
  }

  .sea-stat-label{
    font-size:14px !important;
  }

  .hearts{
    font-size:22px !important;
  }

  #score{
    font-size:26px !important;
  }
}

@media(max-width:540px){

  .quiz-page-shell{
    padding:8px 0 20px !important;
  }

  .quiz-card{
    width:98vw !important;
    padding:8px 10px 16px !important;
    border-radius:22px !important;
  }

  .sea-area{
    height:300px !important;
    min-height:300px !important;
    border-radius:20px !important;
  }

  .media-side-stack{
    gap:8px !important;
  }

  .sign-side-box{
    flex:0 0 110px !important;
    padding:8px !important;
  }

  .sign-preview-box{
    height:90px !important;
  }

  .sign-preview-img{
    width:70px !important;
    height:70px !important;
  }

  .video-wrapper{
    height:140px !important;
    min-height:140px !important;
  }

  .guide-video{
    min-height:140px !important;
  }

  .play-button{
    width:56px !important;
    height:56px !important;
    font-size:26px !important;
  }

  .video-result-title{
    font-size:13px !important;
  }

  .sea-stats{
    top:8px !important;
    left:8px !important;
    gap:8px !important;
  }

  .sea-stat{
    min-width:72px !important;
    padding:6px 8px !important;
    border-radius:14px !important;
  }

  .sea-stat-label{
    font-size:12px !important;
    margin-bottom:4px !important;
  }

  .hearts{
    font-size:18px !important;
  }

  #score{
    font-size:22px !important;
  }

  .sea-total{
    font-size:18px !important;
  }

  .end-box{
    padding:16px 18px !important;
    border-radius:22px !important;
  }

  .end-text{
    font-size:22px !important;
  }

  .quiz-btn.icon-btn{
    width:56px !important;
    height:48px !important;
    font-size:22px !important;
  }

  .end-button-label{
    font-size:13px !important;
  }

  .circle-icon{
    width:42px !important;
    height:42px !important;
  }
}

@media(max-width:380px){

  .sea-area{
    height:260px !important;
    min-height:260px !important;
  }

  .sign-side-box{
    flex:0 0 90px !important;
  }

  .sign-preview-img{
    width:58px !important;
    height:58px !important;
  }

  .video-wrapper{
    height:115px !important;
    min-height:115px !important;
  }

  .guide-video{
    min-height:115px !important;
  }
}

</style>

</head>

<body>

<header class="children-dash">

  <div class="children-dash-inner">

    <div class="dash-start">

      <div class="dash-end">

        <a
          href="../../index.php"
          class="logo-box"
          tabindex="0"
        >

          <img
            src="../../logo.png"
            alt="logo"
          >

        </a>

      </div>

    </div>

    <nav class="dash-nav">

      <a
        href="../../auth/children.php"
        class="nav-video-item"
        tabindex="0"
        aria-label="الرئيسية"
      >

        <div class="circle-icon">

          <video
            autoplay
            muted
            loop
            playsinline
          >

            <source
              src="../../assets/icons/children.mp4?v=<?php echo $video_cache_version; ?>"
              type="video/mp4"
            >

          </video>

        </div>

        <span class="icon-label">
          الرئيسية
        </span>

      </a>

      <a
        href="../arabic/arabic.php?id=<?php echo $id; ?>"
        class="nav-video-item"
        tabindex="0"
        aria-label="كتاب الحرف"
      >

        <div class="circle-icon">

          <img
            src="../../assets/icons/ar-letter.png"
            alt="اللغة العربية"
          >

        </div>

        <span class="icon-label">
          كتاب الحرف
        </span>

      </a>

    </nav>

    <div class="profile-wrap">

      <button
        type="button"
        class="profile-btn"
        id="profileBtn"
        tabindex="0"
      >

        <span class="profile-hello">

          مرحباً
          <?php echo htmlspecialchars($child_display_name); ?>

        </span>

        <div class="profile-video-box">

          <video
            autoplay
            muted
            loop
            playsinline
          >

            <source
              src="../../assets/icons/profile.mp4?v=<?php echo $video_cache_version; ?>"
              type="video/mp4"
            >

          </video>

        </div>

      </button>

      <div
        class="profile-menu"
        id="profileMenu"
      >

        <a href="../../auth/account_settings.php">
          إعدادات الحساب
        </a>

        <a href="../../auth/logout.php">
          تسجيل الخروج
        </a>

      </div>

    </div>

  </div>

</header>


<main class="page-wrap quiz-page-shell">

  <section class="quiz-page">

    <div class="quiz-card">

      <div class="sea-wrap">

        <div class="media-side-stack">

          <aside class="side-box sign-side-box">

            <div class="sign-preview-box">

              <img
                src="<?php echo htmlspecialchars($signImage); ?>"
                alt="إشارة حرف <?php echo htmlspecialchars($item['name']); ?>"
                class="sign-preview-img"
                onerror="this.onerror=null;this.src='../../assets/icons/sign-icon.png';"
              />

              <div
                id="signFallback"
                class="sign-fallback"
                style="display:none;"
              >

                <?php echo htmlspecialchars($item['letter']); ?>

              </div>

            </div>

          </aside>


          <aside class="side-box video-side-box">

            <div class="video-preview-box">

              <div
                class="video-result-title"
                id="videoResultTitle"
              >
                هيا نبحث عن السمكة التي تحمل الحرف المساوي للصورة
              </div>

              <div class="video-wrapper">

                <video
                  id="guideVideo"
                  class="guide-video"
                  preload="metadata"
                  playsinline
                >

                  <source
                    src="<?php echo htmlspecialchars($guideVideoFresh); ?>"
                    type="video/mp4"
                  >

                </video>

                <div
                  class="play-button"
                  id="playBtn"
                >
                  ▶
                </div>

              </div>

            </div>

          </aside>

        </div>


        <div
          class="sea-area"
          id="seaArea"
        >

          <div class="sea-stats">

            <div class="sea-stat sea-hearts-box">

              <span class="sea-stat-label">
                الخطأ
              </span>

              <span
                id="hearts"
                class="hearts"
              >
                ❤️❤️❤️
              </span>

            </div>

            <div class="sea-stat sea-score-box">

              <span class="sea-stat-label">
                الصح
              </span>

              <span id="score">
                0
              </span>

              <span class="sea-total">
                / 3
              </span>

            </div>

          </div>


          <div
            id="gameToast"
            class="game-toast"
          ></div>


          <div class="bubble bubble1"></div>
          <div class="bubble bubble2"></div>
          <div class="bubble bubble3"></div>
          <div class="bubble bubble4"></div>
          <div class="bubble bubble5"></div>
          <div class="bubble bubble6"></div>


          <img
            src="../../images/quiz/net.png"
            alt="شبكة"
            id="net"
            class="net"
          >


          <div
            id="endActions"
            class="end-actions hidden"
          >

            <div class="end-box">

              <div
                id="endText"
                class="end-text success-text"
              >
                أحسنت! ممتاز يا بطل
              </div>

              <div class="end-buttons-row">

                <div class="end-button-item">

                  <button
                    id="retryBtn"
                    class="quiz-btn icon-btn"
                    title="إعادة"
                  >
                    🔁
                  </button>

                  <span class="end-button-label">
                    إعادة اللعبة
                  </span>

                </div>


                <div class="end-button-item">

                  <button
                    id="backToLetterBtn"
                    class="quiz-btn secondary-btn icon-btn"
                    title="رجوع"
                  >
                    ⬅️
                  </button>

                  <span class="end-button-label">
                    العودة
                  </span>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>

  </section>

</main>


<canvas id="confettiCanvas"></canvas>


<script>

window.QUIZ_CONFIG = {

  targetLetter:
    <?php
    echo json_encode(
        $item['letter'],
        JSON_UNESCAPED_UNICODE
    );
    ?>,

  targetId:
    <?php echo $id; ?>,

  maxWins: 3,

  maxLives: 3,

  redirectUrl:
    <?php
    echo json_encode(
        $backUrl,
        JSON_UNESCAPED_UNICODE
    );
    ?>

};

</script>


<script src="../../assets/js/quiz-game.js"></script>


<script>

document.addEventListener("DOMContentLoaded", function(){

  const profileBtn =
    document.getElementById("profileBtn");

  const profileMenu =
    document.getElementById("profileMenu");


  if(profileBtn && profileMenu){

    profileBtn.addEventListener(
      "click",
      function(e){

        e.preventDefault();
        e.stopPropagation();

        profileMenu.classList.toggle("show");

      }
    );


    profileMenu.addEventListener(
      "click",
      function(e){

        e.stopPropagation();

      }
    );


    document.addEventListener(
      "click",
      function(e){

        if(
          !profileBtn.contains(e.target) &&
          !profileMenu.contains(e.target)
        ){

          profileMenu.classList.remove("show");

        }

      }
    );

  }


  const guideVideo =
    document.getElementById("guideVideo");

  const playBtn =
    document.getElementById("playBtn");

  const videoResultTitle =
    document.getElementById("videoResultTitle");

  const endActions =
    document.getElementById("endActions");

  const endText =
    document.getElementById("endText");

  const retryBtn =
    document.getElementById("retryBtn");

  const backToLetterBtn =
    document.getElementById("backToLetterBtn");

  const scoreEl =
    document.getElementById("score");


  /*
   * ========================================================
   * مهم جداً:
   * كل مرة يتغير فيها الفيديو نضيف Date.now()
   * حتى لو كان اسم الملف نفسه، المتصفح يجبر على تحميل
   * النسخة الجديدة.
   * ========================================================
   */

  const introVideo =
    <?php echo json_encode($guideVideo, JSON_UNESCAPED_UNICODE); ?>;

  const winVideo =
    <?php echo json_encode($winVideo, JSON_UNESCAPED_UNICODE); ?>;

  const loseVideo =
    <?php echo json_encode($loseVideo, JSON_UNESCAPED_UNICODE); ?>;

  const backUrl =
    <?php echo json_encode($backUrl, JSON_UNESCAPED_UNICODE); ?>;


  let lastResult = "intro";


  function freshVideoPath(src){

    if(!src){
      return src;
    }

    /*
     * إزالة أي ?v= قديم
     */
    const cleanSrc =
      src.split("?")[0];

    /*
     * إضافة رقم جديد في كل مرة
     */
    return cleanSrc + "?v=" + Date.now();

  }


  function setResultVideo(
    src,
    title,
    autoplay
  ){

    if(!guideVideo){
      return;
    }


    if(videoResultTitle){

      videoResultTitle.textContent =
        title;

    }


    /*
     * إيقاف الفيديو الحالي
     */
    guideVideo.pause();


    /*
     * إجبار المتصفح على تحميل الفيديو الجديد
     */
    const freshSrc =
      freshVideoPath(src);


    guideVideo.removeAttribute("src");


    /*
     * إزالة source القديم
     */
    while(guideVideo.firstChild){

      guideVideo.removeChild(
        guideVideo.firstChild
      );

    }


    /*
     * إنشاء source جديد
     */
    const source =
      document.createElement("source");

    source.src =
      freshSrc;

    source.type =
      "video/mp4";


    guideVideo.appendChild(source);


    /*
     * وضع src مباشر أيضاً
     * لضمان التحديث في جميع المتصفحات
     */
    guideVideo.src =
      freshSrc;


    /*
     * إعادة تحميل الفيديو
     */
    guideVideo.load();


    if(autoplay){

      const p =
        guideVideo.play();

      if(
        p &&
        typeof p.catch === "function"
      ){

        p.catch(function(){

          if(playBtn){

            playBtn.classList.remove(
              "hide"
            );

          }

        });

      }


      if(playBtn){

        playBtn.classList.add("hide");

      }

    }else{

      guideVideo.pause();

      if(playBtn){

        playBtn.classList.remove(
          "hide"
        );

      }

    }

  }


  function playIntro(){

    lastResult = "intro";

    setResultVideo(
      introVideo,
      "هيا نبحث عن السمكة التي تحمل الحرف المساوي للصورة",
      false
    );

  }


  function playWin(){

    if(lastResult === "win"){
      return;
    }

    lastResult = "win";


    if(endText){

      endText.textContent =
        "أحسنت";

    }


    setResultVideo(
      winVideo,
      "أحسنت",
      true
    );

  }


  function playLose(){

    if(lastResult === "lose"){
      return;
    }

    lastResult = "lose";


    if(endText){

      endText.textContent =
        "حاول مرة أخرى";

    }


    setResultVideo(
      loseVideo,
      "حاول مرة أخرى",
      true
    );

  }


  if(playBtn && guideVideo){

    playBtn.addEventListener(
      "click",
      function(e){

        e.stopPropagation();

        const p =
          guideVideo.play();

        if(
          p &&
          typeof p.catch === "function"
        ){

          p.catch(function(){});

        }

        playBtn.classList.add(
          "hide"
        );

      }
    );


    guideVideo.addEventListener(
      "click",
      function(){

        if(guideVideo.paused){

          const p =
            guideVideo.play();

          if(
            p &&
            typeof p.catch === "function"
          ){

            p.catch(function(){});

          }

          playBtn.classList.add(
            "hide"
          );

        }else{

          guideVideo.pause();

          playBtn.classList.remove(
            "hide"
          );

        }

      }
    );


    guideVideo.addEventListener(
      "ended",
      function(){

        playBtn.classList.remove(
          "hide"
        );

      }
    );

  }


  function getScoreNumber(){

    if(!scoreEl){
      return 0;
    }

    const n =
      parseInt(
        scoreEl.textContent.trim(),
        10
      );

    return Number.isFinite(n)
      ? n
      : 0;

  }


  function isEndVisible(){

    if(!endActions){
      return false;
    }

    return (
      !endActions.classList.contains("hidden") &&
      getComputedStyle(endActions).display !== "none"
    );

  }


  function checkEndVideo(){

    if(!isEndVisible()){
      return;
    }

    if(
      getScoreNumber() >=
      window.QUIZ_CONFIG.maxWins
    ){

      playWin();

    }else{

      playLose();

    }

  }


  if(endActions){

    new MutationObserver(
      checkEndVideo
    ).observe(
      endActions,
      {
        attributes:true,
        attributeFilter:[
          "class",
          "style"
        ]
      }
    );

  }


  if(endText){

    new MutationObserver(
      checkEndVideo
    ).observe(
      endText,
      {
        childList:true,
        subtree:true,
        characterData:true,
        attributes:true,
        attributeFilter:[
          "class"
        ]
      }
    );

  }


  if(scoreEl){

    new MutationObserver(
      checkEndVideo
    ).observe(
      scoreEl,
      {
        childList:true,
        subtree:true,
        characterData:true
      }
    );

  }


  if(retryBtn){

    retryBtn.addEventListener(
      "click",
      function(){

        /*
         * إعادة اللعبة
         * مع إجبار فيديو المقدمة على
         * تحميل النسخة الجديدة
         */
        setTimeout(
          function(){

            lastResult = "";

            playIntro();

          },
          60
        );

      }
    );

  }


  if(backToLetterBtn){

    backToLetterBtn.addEventListener(
      "click",
      function(){

        window.location.href =
          backUrl;

      }
    );

  }


  setInterval(
    checkEndVideo,
    250
  );


  /*
   * تشغيل فيديو المقدمة
   * مع كسر الكاش
   */
  playIntro();

});

</script>


<script>

/*
 * منع التمرير مثل الكود الأصلي
 */
document.body.style.overflow =
  "hidden";


window.addEventListener(
  "keydown",
  function(e){

    const blockedKeys = [
      "ArrowUp",
      "ArrowDown",
      "ArrowLeft",
      "ArrowRight",
      " ",
      "PageUp",
      "PageDown",
      "Home",
      "End"
    ];

    if(
      blockedKeys.includes(e.key)
    ){

      e.preventDefault();

    }

  },
  {
    passive:false
  }
);


window.addEventListener(
  "wheel",
  function(e){

    e.preventDefault();

  },
  {
    passive:false
  }
);


window.addEventListener(
  "touchmove",
  function(e){

    e.preventDefault();

  },
  {
    passive:false
  }
);

</script>

</body>
</html>
```
