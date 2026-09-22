<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// منع المتصفح والخادم من الاحتفاظ بنسخ قديمة من فيديوهات الإشارة
$video_cache_version = time();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/session_child.php';

$type = $_GET['type'] ?? 'arabic';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

require_once "../config/db.php";

function coloringPathFromDb($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (strpos($path, '../') === 0 || strpos($path, '/') === 0) {
        return $path;
    }

    return "../" . ltrim($path, '/');
}

$child_display_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";

$basePath = "../images/coloring/";
$image = "";

// رابط الرجوع وفيديو الإشارة حسب النوع
if ($type === 'arabic') {
    $backLink = "../subjects/arabic/arabic-letter.php?id=$id";
    $backIcon = "../assets/icons/ar-letter.png";
    $backLabel = "الحرف العربي";
    $backSignVideo = "../assets/videos/arabic-word.mp4?v=<?php echo $video_cache_version; ?>";
} elseif ($type === 'english') {
    $backLink = "../subjects/english/english-letter.php?id=$id";
    $backIcon = "../assets/icons/en-letter.png";
    $backLabel = "الحرف الإنجليزي";
    $backSignVideo = "../assets/videos/english-word.mp4?v=<?php echo $video_cache_version; ?>";
} elseif ($type === 'number-ar') {
    $backLink = "../subjects/math/arabic-number.php?id=$id";
    $backIcon = "../assets/icons/math-ar.png";
    $backLabel = "الرقم العربي";
    $backSignVideo = "../assets/videos/math-sign.mp4?v=<?php echo $video_cache_version; ?>";
} elseif ($type === 'number-en') {
    $backLink = "../subjects/english/english-number.php?id=$id";
    $backIcon = "../assets/icons/math-number.png";
    $backLabel = "الرقم الإنجليزي";
    $backSignVideo = "../assets/videos/math-sign.mp4?v=<?php echo $video_cache_version; ?>";
} else {
    $backLink = "../auth/children.php";
    $backIcon = "../assets/icons/children.mp4";
    $backLabel = "الرئيسية";
    $backSignVideo = "../assets/videos/home-icon-sign.mp4?v=<?php echo $video_cache_version; ?>";
}

if ($type === "arabic") {

    $sql = "SELECT coloring_image FROM arabic_letter_examples WHERE letter_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row && !empty($row['coloring_image'])) {
        $image = coloringPathFromDb($row['coloring_image']);
    } else {
        $image = $basePath . "arabic/$id.png";
    }

} elseif ($type === "english") {

    $sql = "
    SELECT coloring_image
    FROM lessons
    WHERE subject_name = 'اللغة الإنجليزية'
    AND lesson_type = 'الحروف الإنجليزية'
    AND letter_id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row && !empty($row['coloring_image'])) {
        $image = coloringPathFromDb($row['coloring_image']);
    } else {
        $image = $basePath . "english/$id.png";
    }

} elseif ($type === "number-ar") {

    /*
      أرقام العربي مضافة من صفحة الأدمن داخل جدول lessons
      لذلك لازم نقرأ coloring_image من lessons وليس من arabic_numbers
    */
    $sql = "
    SELECT coloring_image
    FROM lessons
    WHERE subject_name = 'الرياضيات'
      AND lesson_type = 'الأرقام العربية'
      AND letter_id = ?
    ORDER BY id DESC
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row && !empty($row['coloring_image'])) {
            $image = coloringPathFromDb($row['coloring_image']);
        } else {
            $image = $basePath . "number-ar/$id.png";
        }
    } else {
        $image = $basePath . "number-ar/$id.png";
    }

} elseif ($type === "number-en") {

    $sql = "
    SELECT coloring_image
    FROM lessons
    WHERE subject_name = 'اللغة الإنجليزية'
    AND lesson_type = 'الأرقام الإنجليزية'
    AND letter_id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row && !empty($row['coloring_image'])) {
        $image = coloringPathFromDb($row['coloring_image']);
    } else {
        $image = $basePath . "number-en/$id.png";
    }

} else {
    $image = $basePath . "number-en/$id.png";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>التلوين 🎨</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box}

body{
  margin:0;
  background:linear-gradient(180deg,#eaf6ff,#d6efff);
  font-family:'Cairo',sans-serif;
  min-height:100vh;
}

/* ===== HEADER ===== */
.coloring-dash{
  width:calc(100% - 30px);
  max-width:1400px;
  margin:14px auto 0;
  position:sticky;
  top:10px;
  z-index:1000;
}
.coloring-dash-inner{
  background:linear-gradient(135deg,#dff4ff,#cfeeff);
  border-radius:28px;
  padding:10px 22px;
  min-height:80px;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  box-shadow:0 10px 24px rgba(44,111,170,0.14);
}
.coloring-logo{
  position:absolute;
  right:22px;
  top:50%;
  transform:translateY(-50%);
  display:flex;
  align-items:center;
  text-decoration:none;
}
.coloring-logo img{
  width:68px;
  height:auto;
  object-fit:contain;
}
.coloring-nav{
  display:flex;
  align-items:center;
  gap:22px;
}
.coloring-icon{
  width:62px;
  height:62px;
  border-radius:50%;
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  text-decoration:none;
  box-shadow:0 6px 16px rgba(0,0,0,0.12);
  transition:.25s ease;
  overflow:hidden;
}
.coloring-icon:hover{ transform:translateY(-3px) scale(1.05); }
.coloring-icon video,.coloring-icon img{
  width:100%;
  height:100%;
  object-fit:cover;
  border-radius:50%;
}
.coloring-profile{
  position:absolute;
  left:22px;
  top:50%;
  transform:translateY(-50%);
}
.coloring-profile-btn{
  border:none;
  background:rgba(255,255,255,0.95);
  border-radius:999px;
  padding:8px 14px 8px 8px;
  display:flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  box-shadow:0 6px 16px rgba(44,111,170,0.10);
}
.coloring-profile-hello{
  font-size:16px;
  font-weight:900;
  color:#214f7d;
  white-space:nowrap;
}
.coloring-profile-vid{
  width:44px;
  height:44px;
  border-radius:50%;
  overflow:hidden;
  flex-shrink:0;
}
.coloring-profile-vid video{ width:100%;height:100%;object-fit:cover; }
.coloring-menu{
  position:absolute;
  top:calc(100% + 8px);
  left:0;
  min-width:200px;
  background:#fff;
  border-radius:20px;
  box-shadow:0 14px 30px rgba(0,0,0,0.12);
  padding:8px 0;
  opacity:0;
  visibility:hidden;
  transform:translateY(8px);
  transition:.2s ease;
  z-index:999;
}
.coloring-menu.show{ opacity:1;visibility:visible;transform:translateY(0); }
.coloring-menu a{
  display:block;
  padding:13px 18px;
  text-decoration:none;
  color:#214f7d;
  font-size:16px;
  font-weight:900;
}
.coloring-menu a:hover{ background:#f3f9ff; }

/* ===== MOBILE HEADER ===== */
@media(max-width:768px){
  .coloring-dash{ width:calc(100% - 16px); margin:8px auto 0; }
  .coloring-dash-inner{ min-height:56px; padding:6px 10px; border-radius:20px; }
  .coloring-logo img{ width:40px; }
  .coloring-logo{ right:10px; }
  .coloring-profile{ left:10px; }
  .coloring-profile-btn{ padding:5px 8px; gap:6px; }
  .coloring-profile-vid{ width:32px; height:32px; }
  .coloring-profile-hello{ font-size:11px; max-width:60px; overflow:hidden; text-overflow:ellipsis; }
  .coloring-icon{ width:44px; height:44px; }
  .coloring-nav{ gap:12px; }
}

.coloring-wrap{
  display:flex;
  justify-content:center;
  align-items:center;
  min-height:calc(100vh - 110px);
}

.coloring-container{
  max-width: 1200px;     /* 👈 عرض الصفحة */
  margin: 0 auto;        /* 👈 توسيط */
  display:grid;
  grid-template-columns:260px 1fr;
  gap:10px 18px;
  padding:10px;
    width: 100%;
  max-width: 1100px;   /* 👈 حجم الصفحة */
  height: auto; 
}

.title{
  grid-column:1 / 3;
  font-size:28px;
  font-weight:900;
  color:#21425f;
  margin:0;
  text-align:center;
}

.sign-video-panel{
  grid-row:2 / 4;
  background:#fff;
  border-radius:24px;
  padding:12px;
  box-shadow:0 10px 22px rgba(0,0,0,0.08);
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
}

.sign-video-title{
  margin:0;
  font-size:18px;
  font-weight:900;
  color:#21425f;
  text-align:center;
}

.old-sign-video-box{
  width:100%;
  height:360px;
  border-radius:22px;
  overflow:hidden;
  background:#f3f8fc;
  position:relative;
  box-shadow:inset 0 0 0 3px #d9efff;
}

.old-sign-video-box video{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.video-play-overlay{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,0.18);
  border:0;
  cursor:pointer;
  font-size:54px;
  color:#fff;
  text-shadow:0 5px 14px rgba(0,0,0,0.25);
}

.video-caption{
  margin:0;
  font-size:15px;
  font-weight:800;
  color:#45677f;
  line-height:1.7;
  text-align:center;
}

.tools-wrap{
  grid-column:2 / 3;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:8px;
  background:#fff;
  padding:12px 14px;
  border-radius:22px;
  box-shadow:0 10px 22px rgba(0,0,0,0.08);
  max-width:100%;
}

.tools-row{
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  justify-content:center;
  gap:8px;
}

.color{
  width:34px;
  height:34px;
  border-radius:50%;
  cursor:pointer;
  border:3px solid #fff;
  box-shadow:0 2px 6px rgba(0,0,0,0.15);
  transition:transform .18s ease;
}

.color:hover,.color.active{
  transform:scale(1.15);
  outline:2px solid #21425f;
}

.tool-btn,.size-btn{
  border:none;
  cursor:pointer;
  font-family:inherit;
  font-weight:800;
  border-radius:12px;
  padding:8px 14px;
  background:#eef5fb;
  color:#21425f;
  transition:.2s ease;
}

.tool-btn:hover,.size-btn:hover,.size-btn.active{
  background:#4da6ff;
  color:#fff;
}

.canvas-area{
  grid-column:2 / 3;
  display:flex;
  align-items:center;
  justify-content:center;
  min-height:0;
}

.canvas-box{
  position:relative;
  display:inline-block;
  background:#fff;
  padding:8px;
  border-radius:22px;
  box-shadow:0 12px 28px rgba(0,0,0,0.10);
  max-width:100%;
  max-height:calc(100vh - 210px);
}

.canvas-box img{
  display:block;
  max-width:calc(100vw - 320px);
  max-height:calc(100vh - 230px);
  border-radius:14px;
  user-select:none;
  -webkit-user-drag:none;
}

#drawCanvas{
  position:absolute;
  top:8px;
  left:8px;
  border-radius:14px;
  cursor:crosshair;
  touch-action:none;
}

@media(max-width:900px){
  body{overflow:auto}
  .coloring-container{
    height:auto;
    min-height:100vh;
    grid-template-columns:1fr;
    grid-template-rows:auto auto auto auto;
  }

  .title,.tools-wrap,.canvas-area{
    grid-column:1;
  }

  .sign-video-panel{
    grid-column:1;
    grid-row:auto;
  }

  .old-sign-video-box{
    height:230px;
  }

  .canvas-box img{
    max-width:90vw;
    max-height:55vh;
  }
}
</style>
</head>

<body>



<div class="coloring-wrap">
<div class="coloring-container">

  <div class="tools-wrap">
    <div class="tools-row" id="colorsRow">
      <div class="color active" style="background:#000000" data-color="#000000" data-video-key="black" title="أسود"></div>
      <div class="color" style="background:#cd0707" data-color="#cd0707" data-video-key="red" title="أحمر"></div>
      <div class="color" style="background:#ff7a00" data-color="#ff7a00" data-video-key="orange" title="برتقالي"></div>
      <div class="color" style="background:#ffd400" data-color="#ffd400" data-video-key="yellow" title="أصفر"></div>
      <div class="color" style="background:#33aa33" data-color="#33aa33" data-video-key="green" title="أخضر"></div>
      <div class="color" style="background:#0047ff" data-color="#0047ff" data-video-key="blue" title="أزرق"></div>
      <div class="color" style="background:#77609d" data-color="#77609d" data-video-key="purple" title="بنفسجي"></div>
      <div class="color" style="background:#ff4fc3" data-color="#ff4fc3" data-video-key="pink" title="وردي"></div>
      <div class="color" style="background:#8b4513" data-color="#8b4513" data-video-key="brown" title="بني"></div>
      <div class="color" style="background:#808080" data-color="#808080" data-video-key="gray" title="رمادي"></div>
      <div class="color" style="background:#ffffff" data-color="#ffffff" data-video-key="white" title="أبيض"></div>
    </div>

    <div class="tools-row">
      <button class="size-btn" data-size="4" data-video-key="thin">رفيع</button>
      <button class="size-btn active" data-size="10" data-video-key="medium">وسط</button>
      <button class="size-btn" data-size="22" data-video-key="thick">عريض</button>
    </div>

    <div class="tools-row">
      <button class="tool-btn" id="penBtn" data-video-key="pen">✏️ قلم</button>
      <button class="tool-btn" id="eraserBtn" data-video-key="eraser">🧽 ممحاة</button>
      <button class="tool-btn" id="clearBtn" data-video-key="clear">❌ مسح الكل</button>
    </div>
  </div>

  <div class="canvas-area">
    <div class="canvas-box">
      <img id="colorImage" src="<?php echo htmlspecialchars($image); ?>" alt="صورة التلوين" onerror="this.onerror=null;this.src='../images/coloring/default.png';">
      <canvas id="drawCanvas"></canvas>
    </div>
  </div>

</div>
</div>

<?php
$sign_base = "../";
$sign_video = "../assets/videos/coloring/intro.mp4?v=<?php echo $video_cache_version; ?>";
include "../components/sign-language.php";
?>

<script>
// Profile menu
const cpBtn = document.getElementById("coloringProfileBtn");
const cpMenu = document.getElementById("coloringMenu");
if(cpBtn && cpMenu){
  cpBtn.addEventListener("click", function(e){ e.stopPropagation(); cpMenu.classList.toggle("show"); });
  document.addEventListener("click", function(){ cpMenu.classList.remove("show"); });
}

// ربط عناصر الهيدر بزر المساعدة
document.querySelectorAll("[data-dashboard-video]").forEach(function(item){
  item.addEventListener("mouseenter", function(){
    openDashboardSignVideo(item.dataset.dashboardVideo, item, item.dataset.dashboardText || "");
  });
  item.addEventListener("focus", function(){
    openDashboardSignVideo(item.dataset.dashboardVideo, item, item.dataset.dashboardText || "");
  });
  item.addEventListener("mouseleave", leaveDashboardSignVideo);
  item.addEventListener("blur", leaveDashboardSignVideo);
});

const canvas = document.getElementById("drawCanvas");
const img = document.getElementById("colorImage");
const ctx = canvas.getContext("2d");

const colorButtons = document.querySelectorAll(".color");
const sizeButtons = document.querySelectorAll(".size-btn");
const penBtn = document.getElementById("penBtn");
const eraserBtn = document.getElementById("eraserBtn");
const clearBtn = document.getElementById("clearBtn");

const signVideos = {
  intro: {
    src: "../assets/videos/coloring/intro.mp4?v=<?php echo $video_cache_version; ?>",
    title: "شرح التلوين"
  },

  black: { src: "../assets/videos/coloring/black.mp4?v=<?php echo $video_cache_version; ?>", title: "أسود" },
  red: { src: "../assets/videos/coloring/red.mp4?v=<?php echo $video_cache_version; ?>", title: "أحمر" },
  orange: { src: "../assets/videos/coloring/orange.mp4?v=<?php echo $video_cache_version; ?>", title: "برتقالي" },
  yellow: { src: "../assets/videos/coloring/yellow.mp4?v=<?php echo $video_cache_version; ?>", title: "أصفر" },
  green: { src: "../assets/videos/coloring/green.mp4?v=<?php echo $video_cache_version; ?>", title: "أخضر" },
  blue: { src: "../assets/videos/coloring/blue.mp4?v=<?php echo $video_cache_version; ?>", title: "أزرق" },
  purple: { src: "../assets/videos/coloring/purple.mp4?v=<?php echo $video_cache_version; ?>", title: "بنفسجي" },
  pink: { src: "../assets/videos/coloring/pink.mp4?v=<?php echo $video_cache_version; ?>", title: "وردي" },
  brown: { src: "../assets/videos/coloring/brown.mp4?v=<?php echo $video_cache_version; ?>", title: "بني" },
  gray: { src: "../assets/videos/coloring/gray.mp4?v=<?php echo $video_cache_version; ?>", title: "رمادي" },
  white: { src: "../assets/videos/coloring/white.mp4?v=<?php echo $video_cache_version; ?>", title: "أبيض" },

  thin: { src: "../assets/videos/coloring/thin.mp4?v=<?php echo $video_cache_version; ?>", title: " خط رفيع" },
  medium: { src: "../assets/videos/coloring/medium.mp4?v=<?php echo $video_cache_version; ?>", title: " خط وسط" },
  thick: { src: "../assets/videos/coloring/thick.mp4?v=<?php echo $video_cache_version; ?>", title: " خط عريض" },
  pen: { src: "../assets/videos/coloring/pen.mp4?v=<?php echo $video_cache_version; ?>", title: " قلم" },
  eraser: { src: "../assets/videos/coloring/eraser.mp4?v=<?php echo $video_cache_version; ?>", title: "ممحاة" },
  clear: { src: "../assets/videos/coloring/clear.mp4?v=<?php echo $video_cache_version; ?>", title: "مسح الكل" }
};

let drawing = false;
let currentColor = "#000000";
let brushSize = 10;
let isEraser = false;
let lastX = 0;
let lastY = 0;

function playHelpVideo(key, element = null) {
  const data = signVideos[key];
  if (!data) return;

  if (typeof openDashboardSignVideo === "function") {
    openDashboardSignVideo(data.src, element, data.title);
  } else if (typeof setSignVideo === "function") {
    setSignVideo(data.src, element, true, data.title);
    if (typeof showSignText === "function") showSignText(data.title);
  }
}

function resizeCanvas() {
  canvas.width = img.clientWidth;
  canvas.height = img.clientHeight;
}

function getPos(e) {
  const rect = canvas.getBoundingClientRect();

  if (e.touches && e.touches.length > 0) {
    return {
      x: e.touches[0].clientX - rect.left,
      y: e.touches[0].clientY - rect.top
    };
  }

  return {
    x: e.clientX - rect.left,
    y: e.clientY - rect.top
  };
}

function startDrawing(e) {
  drawing = true;
  const pos = getPos(e);
  lastX = pos.x;
  lastY = pos.y;

  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
}

function draw(e) {
  if (!drawing) return;

  const pos = getPos(e);

  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  ctx.lineWidth = brushSize;

  if (isEraser) {
    ctx.globalCompositeOperation = "destination-out";
  } else {
    ctx.globalCompositeOperation = "source-over";
    ctx.strokeStyle = currentColor;
  }

  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();

  lastX = pos.x;
  lastY = pos.y;
}

function stopDrawing() {
  drawing = false;
  ctx.beginPath();
}

colorButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    currentColor = btn.dataset.color;
    isEraser = false;

    colorButtons.forEach(c => c.classList.remove("active"));
    btn.classList.add("active");
  });

  btn.addEventListener("mouseenter", () => {
    playHelpVideo(btn.dataset.videoKey, btn);
  });
});

sizeButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    brushSize = parseInt(btn.dataset.size, 10);

    sizeButtons.forEach(s => s.classList.remove("active"));
    btn.classList.add("active");
  });

  btn.addEventListener("mouseenter", () => {
    playHelpVideo(btn.dataset.videoKey, btn);
  });
});

penBtn.addEventListener("click", () => {
  isEraser = false;
});

penBtn.addEventListener("mouseenter", () => {
  playHelpVideo("pen", penBtn);
});

eraserBtn.addEventListener("click", () => {
  isEraser = true;
});

eraserBtn.addEventListener("mouseenter", () => {
  playHelpVideo("eraser", eraserBtn);
});

clearBtn.addEventListener("click", () => {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
});

clearBtn.addEventListener("mouseenter", () => {
  playHelpVideo("clear", clearBtn);
});

canvas.addEventListener("mousedown", startDrawing);
canvas.addEventListener("mousemove", draw);
canvas.addEventListener("mouseup", stopDrawing);
canvas.addEventListener("mouseleave", stopDrawing);

canvas.addEventListener("touchstart", (e) => {
  e.preventDefault();
  startDrawing(e);
}, { passive: false });

canvas.addEventListener("touchmove", (e) => {
  e.preventDefault();
  draw(e);
}, { passive: false });

canvas.addEventListener("touchend", stopDrawing);
canvas.addEventListener("touchcancel", stopDrawing);

img.onload = resizeCanvas;

window.addEventListener("load", () => {
  resizeCanvas();

  if (typeof setSignVideo === "function") {
    setSignVideo("../assets/videos/coloring/intro.mp4?v=<?php echo $video_cache_version; ?>", null, false, "هيا نلون معاً , قم باستخدام القلم و الألوان و الممحاة للرسم ");
  }

  if (typeof showSignText === "function") {
    showSignText("شرح التلوين");
  }
});

if (img.complete) {
  resizeCanvas();
}
</script>

<script>
(function () {
  if (!('ontouchstart' in window)) return;
  var pending = null;
  document.addEventListener('touchend', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signToggleBtn, .sign-video-box')) return;
    var target = e.target.closest('a, button') || e.target;
    if (!target || target === document.body) { pending = null; return; }
    if (pending !== target) {
      e.preventDefault();
      e.stopImmediatePropagation();
      pending = target;
      target.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
    } else { pending = null; }
  }, { passive: false });
  document.addEventListener('touchstart', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signToggleBtn, .sign-video-box')) return;
    var target = e.target.closest('a, button') || e.target;
    if (target !== pending) pending = null;
  }, { passive: true });
})();
</script>

</body>
</html>