<!-- SIGN LANGUAGE COMPONENT -->

<style>
.sign-floating {
  position: fixed !important;
  left: 22px !important;
  bottom: 22px !important;
  z-index: 2147483647 !important;
  display: block !important;
  pointer-events: auto !important;
}

.sign-circle {
  width: 75px;
  height: 75px;
  border-radius: 50%;
  border: 4px solid white;
  background: #32d27b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 8px;
  box-shadow: 0 10px 25px rgba(0,0,0,.25);
  position: relative;
  z-index: 2147483647;
  transition: .25s ease;
}

.sign-circle.active {
  box-shadow:
    0 0 0 8px rgba(50, 210, 123, .25),
    0 0 28px rgba(50, 210, 123, .9),
    0 10px 25px rgba(0,0,0,.25);
  transform: scale(1.08);
}

.sign-circle:hover {
  transform: scale(1.06);
}

.sign-circle.active:hover {
  transform: scale(1.08);
}

.sign-circle img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

@media (max-width: 768px) {
  .sign-floating {
    left: 12px !important;
    bottom: 12px !important;
  }

  .sign-circle {
    width: 50px !important;
    height: 50px !important;
    padding: 6px !important;
    border-width: 3px !important;
  }

  .sign-video-box {
    width: 200px !important;
    bottom: 68px !important;
  }

  .sign-video-wrap {
    height: 200px !important;
  }
}

.sign-video-box {
  position: absolute;
  left: 0;
  bottom: 95px;
  width: 260px;
  height: auto;
  background: white;
  border-radius: 24px;
  padding: 8px;
  display: none;
  box-shadow: 0 18px 45px rgba(0,0,0,.28);
  z-index: 2147483647;
 
}

.sign-video-box.active {
  display: block !important;
}

.sign-helper-text {
  width: 100%;
  text-align: center;
  font-family: Arial, sans-serif;
  font-size: 16px;
  font-weight: 900;
  color: #21425f;
  background: #eafff2;
  border: 2px solid #92efba;
  border-radius: 16px;
  padding: 8px 10px;
  margin-bottom: 8px;
  line-height: 1.5;
  display: none;
}
.sign-video-wrap {
  position: relative;
  width: 100%;
  height: 260px;
}

.sign-video-box video {
  width: 100%;
  height: 100%;
  border-radius: 18px;
  object-fit: contain;
  background: #000;
}

.sign-play-btn {
  position: absolute;
  inset: 0;
  margin: auto;
  width: 74px;
  height: 74px;
  border-radius: 50%;
  border: 4px solid white;
  background: rgba(255, 91, 120, 0.92);
  color: white;
  font-size: 34px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
  box-shadow: 0 10px 24px rgba(0,0,0,.25);
}

.sign-play-btn.hide {
  display: none !important;
}

.close-sign {
  position: absolute;
  top: -20px;
  right: -20px;

  width: 55px;
  height: 55px;

  border-radius: 50%;
  border: 4px solid white; /* نفس الإطار */

  background: #ff3b3b;
  color: white;
  font-size: 26px;
  font-weight: 900;

  display: flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  z-index: 9999;

  box-shadow: 0 8px 20px rgba(0,0,0,0.25);
}
.sign-active-target {
  outline: none !important;
  box-shadow: 0 0 0 5px #ff3b3b !important;
  border-radius: 28px !important;
}

/* أيقونات الهيدر والفيديوهات الدائرية */


/* إذا الداتا على الرابط نفسه مش على الدائرة */
.nav-video-item.sign-active-target {
  outline: none !important;
  box-shadow: none !important;
}



/* الأقسام الكبيرة فقط */
[data-sign-video].sign-active-target:not(.circle-icon):not(.nav-video-circle):not(.profile-avatar) {
  outline: none !important;
  box-shadow: 0 0 0 5px #ff3b3b !important;
  border-radius: 28px !important;
}
</style>

<div class="sign-floating">

  <button class="sign-circle" id="signToggleBtn" onclick="toggleSignVideo()" type="button" title="لغة الإشارة">
    <img src="<?php echo $sign_base; ?>assets/icons/sign-icon.png" class="sign-icon" alt="لغة الإشارة">
  </button>

  <div class="sign-video-box" id="signVideoBox">
    <button class="close-sign" onclick="closeSignVideo()" type="button">×</button>

    <div id="signHelperText" class="sign-helper-text"></div>

    <div class="sign-video-wrap">
      <video id="signVideo" playsinline muted preload="auto">
        <source id="signVideoSource" src="<?php echo $sign_video ?? 'assets/videos/home-sign.mp4'; ?>" type="video/mp4">
      </video>

      <button class="sign-play-btn" id="signPlayBtn" onclick="playSignVideo()" type="button">▶</button>
    </div>
  </div>

</div>

<script>
let sectionVideoStarted = false;
let currentSectionVideo = "<?php echo $sign_video ?? 'assets/videos/home-sign.mp4'; ?>";
let currentSectionTarget = null;
let currentSectionText = "";
let isDashboardHover = false;
let isHeroHover = false;

function isSignBoxOpen() {
  const box = document.getElementById("signVideoBox");
  return box && box.classList.contains("active");
}

function clearActiveSignTarget() {
  document.querySelectorAll(".sign-active-target").forEach(el => {
    el.classList.remove("sign-active-target");
  });
}

function setActiveSignTarget(element) {
  clearActiveSignTarget();

  if (!element) return;

  // إذا العنصر فيه دائرة داخله
  const circle = element.querySelector(".nav-circle");

  if (circle) {
    circle.classList.add("sign-active-target");
  } else {
    element.classList.add("sign-active-target");
  }
}

function showSignText(text = "") {
  const textBox = document.getElementById("signHelperText");
  if (!textBox) return;

  textBox.textContent = text || "";
  textBox.style.display = (text && text.trim() !== "") ? "block" : "none";
}

function showPlayButton() {
  const playBtn = document.getElementById("signPlayBtn");
  if (playBtn) playBtn.classList.remove("hide");
}

function hidePlayButton() {
  const playBtn = document.getElementById("signPlayBtn");
  if (playBtn) playBtn.classList.add("hide");
}

function setVideoOnly(videoPath) {
  const video = document.getElementById("signVideo");
  const source = document.getElementById("signVideoSource");
  if (!video || !source || !videoPath) return;

  if (source.getAttribute("src") !== videoPath) {
    video.pause();
    source.setAttribute("src", videoPath);
    video.load();
  }
}

function setSignVideo(videoPath, targetElement = null, shouldPlay = false, text = "") {
  const box = document.getElementById("signVideoBox");
  const video = document.getElementById("signVideo");
  if (!videoPath || !video) return;

  currentSectionVideo = videoPath;
  currentSectionTarget = targetElement;
  currentSectionText = text || "";
  isDashboardHover = false;
  isHeroHover = false;

  setVideoOnly(videoPath);
  sectionVideoStarted = false;
  showPlayButton();

  if (box && box.classList.contains("active")) {
    setActiveSignTarget(targetElement);
    showSignText(currentSectionText);

    if (shouldPlay) {
      video.play().then(() => {
        sectionVideoStarted = true;
        hidePlayButton();
      }).catch(() => showPlayButton());
    }
  }
}

function openDashboardSignVideo(videoPath, element = null, text = "") {
  const video = document.getElementById("signVideo");
  if (!isSignBoxOpen() || !video) return;

  isDashboardHover = true;
  isHeroHover = false;

  setVideoOnly(videoPath);
  setActiveSignTarget(element);
  showSignText(text);

  video.currentTime = 0;
  video.play().then(() => hidePlayButton()).catch(() => showPlayButton());

  sectionVideoStarted = false;
}

function leaveDashboardSignVideo() {
  const video = document.getElementById("signVideo");
  if (!isSignBoxOpen() || !video || !isDashboardHover) return;

  isDashboardHover = false;

  if (currentSectionVideo) {
    setVideoOnly(currentSectionVideo);
    setActiveSignTarget(currentSectionTarget);
    showSignText(currentSectionText);

    video.pause();
    video.currentTime = 0;

    showPlayButton();
  }
}

function openHeroSignVideo(videoPath, element = null, text = "") {
  const heroVisualVideo = document.getElementById("heroIntroVideo");
  const heroSignBox = document.getElementById("heroSignBox");

  if (!isSignBoxOpen()) return;

  isHeroHover = true;
  isDashboardHover = false;

  clearActiveSignTarget();

  if (heroVisualVideo && heroSignBox) {
    heroVisualVideo.currentTime = 0;
    heroVisualVideo.play().then(() => {
      heroSignBox.classList.add("playing");
      const playBtn = document.getElementById("playBtn");
      if (playBtn) playBtn.style.display = "none";
    }).catch(() => {});
  }
}

function leaveHeroSignVideo() {
  const heroVisualVideo = document.getElementById("heroIntroVideo");
  const heroSignBox = document.getElementById("heroSignBox");
  const playBtn = document.getElementById("playBtn");

  if (!isSignBoxOpen() || !isHeroHover) return;

  isHeroHover = false;

  if (heroVisualVideo && heroSignBox) {
    heroVisualVideo.pause();
    heroVisualVideo.currentTime = 0;
    heroSignBox.classList.remove("playing");
  }

  if (playBtn) playBtn.style.display = "flex";
}
function openSignVideo(videoPath, element = null, text = "") {
  const video = document.getElementById("signVideo");
  if (!isSignBoxOpen() || !video) return;

  /* ✅ لا تشغل فيديو الكروت إلا بعد تشغيل فيديو القسم */
  if (!sectionVideoStarted) {
    setActiveSignTarget(currentSectionTarget);
    showSignText(currentSectionText);
    return;
  }

  isDashboardHover = false;
  isHeroHover = false;

  setVideoOnly(videoPath);
  setActiveSignTarget(element);
  showSignText(text);

  video.currentTime = 0;
  video.play().then(() => hidePlayButton()).catch(() => showPlayButton());
}

function playSignVideo() {
  const video = document.getElementById("signVideo");
  if (!video) return;

  isDashboardHover = false;
  isHeroHover = false;
  sectionVideoStarted = true;

  video.muted = true;
  video.play().then(() => hidePlayButton()).catch(() => showPlayButton());
}

function closeSignVideo() {
  const box = document.getElementById("signVideoBox");
  const toggleBtn = document.getElementById("signToggleBtn");
  const video = document.getElementById("signVideo");
  const heroVisualVideo = document.getElementById("heroIntroVideo");
  const heroSignBox = document.getElementById("heroSignBox");
  const playBtn = document.getElementById("playBtn");

  if (!box || !video) return;

  box.classList.remove("active");
  if (toggleBtn) toggleBtn.classList.remove("active");
  document.body.classList.remove("sign-open");

  clearActiveSignTarget();
  showPlayButton();
  showSignText("");

  isDashboardHover = false;
  isHeroHover = false;

  video.pause();
  video.currentTime = 0;

  if (heroVisualVideo && heroSignBox) {
    heroVisualVideo.pause();
    heroVisualVideo.currentTime = 0;
    heroSignBox.classList.remove("playing");
  }

  if (playBtn) playBtn.style.display = "flex";

  if (currentSectionVideo) setVideoOnly(currentSectionVideo);
}

function toggleSignVideo() {
  const box = document.getElementById("signVideoBox");
  const toggleBtn = document.getElementById("signToggleBtn");
  const video = document.getElementById("signVideo");

  if (!box) return;

  if (box.classList.contains("active")) {
    closeSignVideo();
  } else {
    box.classList.add("active");
    if (toggleBtn) toggleBtn.classList.add("active");
    document.body.classList.add("sign-open");

    // تسجيل ضغطة زر المساعدة
    fetch('<?php echo $sign_base; ?>api/log-help.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'page=' + encodeURIComponent(window.location.pathname)
    }).catch(function(){});

    isDashboardHover = false;
    isHeroHover = false;
    sectionVideoStarted = false;

    if (currentSectionVideo) {
      setVideoOnly(currentSectionVideo);
      setActiveSignTarget(currentSectionTarget);
      showSignText(currentSectionText);
    }

    if (video) {
      video.pause();
      video.currentTime = 0;
      video.muted = true;
    }

    showPlayButton();
  }
}

const signSections = document.querySelectorAll("[data-sign-video]");

if (signSections.length > 0) {
 function updateActiveSection() {
  if (isDashboardHover || isHeroHover) return;

  const sections = document.querySelectorAll("[data-sign-video]");
  let closestSection = null;
  let closestDistance = Infinity;

  sections.forEach(section => {
    const rect = section.getBoundingClientRect();

    // المسافة من أعلى الشاشة (مع مراعاة الهيدر)
    const distance = Math.abs(rect.top - 140);

    if (rect.top < window.innerHeight && rect.bottom > 140) {
      if (distance < closestDistance) {
        closestDistance = distance;
        closestSection = section;
      }
    }
  });

  if (closestSection) {
    const text =
      closestSection.dataset.signText ||
      closestSection.getAttribute("aria-label") ||
      "";

    setSignVideo(
      closestSection.dataset.signVideo,
      closestSection,
      false,
      text
    );
  }
}

/* يشتغل عند السكرول */
window.addEventListener("scroll", updateActiveSection);

/* أول تحميل */
window.addEventListener("load", updateActiveSection);
}

const mainSignVideo = document.getElementById("signVideo");

if (mainSignVideo) {
  mainSignVideo.addEventListener("ended", function() {
    showPlayButton();
  });

  mainSignVideo.addEventListener("pause", function() {
    if (!mainSignVideo.ended) showPlayButton();
  });
}

const heroIntroVideo = document.getElementById("heroIntroVideo");
const heroPlayBtn = document.getElementById("playBtn");
const heroSignBox = document.getElementById("heroSignBox");

function isHelpOpenForHero() {
  const box = document.getElementById("signVideoBox");
  return box && box.classList.contains("active");
}

function playHeroVideo() {
  if (!heroIntroVideo || !heroPlayBtn || !heroSignBox) return;

  heroIntroVideo.play().then(() => {
    heroSignBox.classList.add("playing");
    heroPlayBtn.style.display = "none";
  }).catch(() => {});
}

function stopHeroVideo(reset = false) {
  if (!heroIntroVideo || !heroPlayBtn || !heroSignBox) return;

  heroIntroVideo.pause();

  if (reset) {
    heroIntroVideo.currentTime = 0;
  }

  heroSignBox.classList.remove("playing");
  heroPlayBtn.style.display = "flex";
}

if (heroPlayBtn && heroIntroVideo && heroSignBox) {
  heroPlayBtn.addEventListener('click', function (e) {
    e.preventDefault();
    heroIntroVideo.play().then(() => {
      heroSignBox.classList.add('playing');
      heroPlayBtn.style.display = 'none';
    }).catch(() => {});
  });
}

</script>
