<?php
require_once __DIR__ . '/config/session_child.php';

$is_logged_in = isset($_SESSION["username"]) || isset($_SESSION["child_name"]);
$user_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>تمكين</title>

  <script src="assets/js/language-sync.js"></script>

  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-thin-straight/css/uicons-thin-straight.css">
  <link rel="stylesheet" href="assets/css/style.css?v=2" />
  <link rel="stylesheet" href="assets/css/accessibility.css">

  <style>
    /* ── إزاحة العناصر الأمامية فقط عند فتح لوحة الإشارة ── desktop only ── */
    @media (min-width: 1025px) {
      .hero-image,
      .hero-text,
      .cards-container,
      .about-cards,
      .footer-container {
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: right center;
      }

      body.sign-panel-open .hero-image,
      body.sign-panel-open .hero-text,
      body.sign-panel-open .cards-container,
      body.sign-panel-open .about-cards,
      body.sign-panel-open .footer-container {
        transform: scale(0.88);
      }
    }
  </style>
</head>

<body class="index-page">

<header class="header">

  <div class="header-left">

    <?php if ($is_logged_in): ?>
      <a href="auth/children.php"
         class="login-btn"
         id="loginBtn"
         onmouseenter="openDashboardSignVideo('assets/videos/login-icon-sign.mp4?v=' + Date.now(), this, 'صفحة الطفل')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/login-icon-sign.mp4?v=' + Date.now(), this, 'صفحة الطفل')"
         onblur="leaveDashboardSignVideo()">
        مرحباً <?php echo htmlspecialchars($user_name); ?>
      </a>
    <?php else: ?>
      <a href="auth/login.php"
         class="login-btn"
         id="loginBtn"
         onmouseenter="openDashboardSignVideo('assets/videos/login-icon-sign.mp4?v=' + Date.now(), this, 'تسجيل الدخول')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/login-icon-sign.mp4?v=' + Date.now(), this, 'تسجيل الدخول')"
         onblur="leaveDashboardSignVideo()">
        تسجيل الدخول
      </a>
    <?php endif; ?>

  </div>

<nav class="nav">

<a href="#home"
   class="nav-item"
   onclick="scrollToSection('home','assets/videos/home-sign.mp4?v=' + Date.now(),'منصة تمكين'); return false;"
   onmouseenter="openDashboardSignVideo('assets/videos/home-icon-sign.mp4?v=' + Date.now(), this, 'الرئيسية')"
   onmouseleave="leaveDashboardSignVideo()"
   onfocus="openDashboardSignVideo('assets/videos/home-icon-sign.mp4?v=' + Date.now(), this, 'الرئيسية')"
   onblur="leaveDashboardSignVideo()">

  <div class="nav-circle">
    <video autoplay muted loop playsinline>
      <source src="assets/icons/home.mp4?v=<?php echo time(); ?>" type="video/mp4">
    </video>
  </div>

  <span class="nav-label">الرئيسية</span>
</a>

  <a href="#subjects-section"
     class="nav-item"
     onclick="scrollToSection('subjects-section','assets/videos/subjects-sign.mp4?v=' + Date.now(),'اختر المادة التعليمية وابدأ التعلم'); return false;"
     onmouseenter="openDashboardSignVideo('assets/videos/subjects-icon-sign.mp4?v=' + Date.now(), this, 'البرامج التعليمية')"
     onmouseleave="leaveDashboardSignVideo()"
     onfocus="openDashboardSignVideo('assets/videos/subjects-icon-sign.mp4?v=' + Date.now(), this, 'البرامج التعليمية')"
     onblur="leaveDashboardSignVideo()">
    <div class="nav-circle">
      <video autoplay muted loop playsinline>
        <source src="assets/icons/subjects.mp4?v=<?php echo time(); ?>" type="video/mp4">
      </video>
    </div>
    <span class="nav-label">البرامج التعليمية</span>
  </a>

  <a href="#about-section"
     class="nav-item"
     onclick="scrollToSection('about-section','assets/videos/about-sign.mp4?v=' + Date.now(),'لماذا تعد منصة تمكين مناسبة لطفلك؟'); return false;"
     onmouseenter="openDashboardSignVideo('assets/videos/about-icon-sign.mp4?v=' + Date.now(), this, 'عن المنصة')"
     onmouseleave="leaveDashboardSignVideo()"
     onfocus="openDashboardSignVideo('assets/videos/about-icon-sign.mp4?v=' + Date.now(), this, 'عن المنصة')"
     onblur="leaveDashboardSignVideo()">
    <div class="nav-circle">
      <video autoplay muted loop playsinline>
        <source src="assets/icons/about.mp4?v=<?php echo time(); ?>" type="video/mp4">
      </video>
    </div>
    <span class="nav-label">عن المنصة</span>
  </a>

  <a href="#contact-section"
     class="nav-item"
     onclick="scrollToSection('contact-section','assets/videos/contact-sign.mp4?v=' + Date.now(),'تواصل معنا'); return false;"
     onmouseenter="openDashboardSignVideo('assets/videos/contact-sign.mp4?v=' + Date.now(), this, 'تواصل معنا')"
     onmouseleave="leaveDashboardSignVideo()"
     onfocus="openDashboardSignVideo('assets/videos/contact-sign.mp4?v=' + Date.now(), this, 'تواصل معنا')"
     onblur="leaveDashboardSignVideo()">
    <div class="nav-circle">
      <video autoplay muted loop playsinline>
        <source src="assets/images/contact.mp4?v=<?php echo time(); ?>" type="video/mp4">
      </video>
    </div>
    <span class="nav-label">تواصل معنا</span>
  </a>

</nav>

  <div class="logo-box" tabindex="0">
    <img src="logo.png" alt="تمكين">
  </div>

</header>

<div class="page-wrapper"
     id="home"
     data-sign-video="assets/videos/home-sign.mp4"
     data-sign-text="منصة تمكين">

  <div class="floating-decor">
    <span class="star s1">★</span>
    <span class="star s2">★</span>
    <span class="star s3">★</span>
    <span class="star s4">★</span>
    <span class="star s5">★</span>
    <span class="star s6">★</span>
    <span class="star s7">★</span>
    <span class="star s8">★</span>
    <span class="star s9">★</span>
    <span class="star s10">★</span>
    <span class="star s11">★</span>
    <span class="star s12">★</span>

    <span class="bubble b1"></span>
    <span class="bubble b2"></span>
    <span class="bubble b3"></span>
    <span class="bubble b4"></span>
    <span class="bubble b5"></span>
    <span class="bubble b6"></span>
    <span class="bubble b7"></span>
    <span class="bubble b8"></span>
  </div>

  <main class="hero">
    <div class="hero-text">
      <h1 id="heroTitle">منصة <span> تمكين</span></h1>

      <p id="heroDesc">
        تجربة تعليمية تفاعلية للأطفال الصم من عمر 4 إلى 8 سنوات، تعتمد على الصور والألعاب والتعلم البصري بطريقة ممتعة وسهلة.
      </p>

      <div class="hero-buttons">
        <a href="#subjects-section"
           class="btn primary-btn"
           id="startBtn"
           onclick="setSignVideo('assets/videos/subjects-sign.mp4?v=' + Date.now(), document.getElementById('subjects-section'), false, 'اختر المادة التعليمية وابدأ التعلم')">
          ابدأ الآن
        </a>

        <a href="#about-section"
           class="btn secondary-btn"
           id="moreBtn"
           onclick="setSignVideo('assets/videos/about-sign.mp4?v=' + Date.now(), document.getElementById('about-section'), false, 'لماذا تعد منصة تمكين مناسبة لطفلك؟')">
          اكتشف المزيد
        </a>
      </div>
    </div>

    <div class="hero-image">
      <img src="assets/images/kids-pencil.png" id="heroImg" alt="أطفال يتعلمون فوق قلم ملون" />
    </div>
  </main>
</div>

<div class="page-wrapper1"
     id="subjects-section"
     data-sign-video="assets/videos/subjects-sign.mp4"
     data-sign-text="اختر المادة التعليمية وابدأ التعلم">

  <main class="hero1 subjects-new">
    <h1 class="main-title" id="subjectsTitle">
      اختر <span>المادة وابدأ</span> التعلم
    </h1>

    <div class="cards-container">

      <div class="card red" tabindex="0"
           onmouseenter="openSignVideo('assets/videos/arabic-sign.mp4?v=' + Date.now(), this, 'اللغة العربية')"
           onfocus="openSignVideo('assets/videos/arabic-sign.mp4?v=' + Date.now(), this, 'اللغة العربية')">
        <div class="subject-icon-wrap">
          <img src="assets/images/arabic-icon.png" alt="اللغة العربية" class="subject-icon">
        </div>
        <div class="title" id="arabicTitle">اللغة العربية</div>
        <div class="desc" id="arabicDesc">تعلم الحروف، الكلمات، القراءة، والقواعد بسهولة ومتعة.</div>
      </div>

      <div class="card purple" tabindex="0"
           onmouseenter="openSignVideo('assets/videos/english-sign.mp4?v=' + Date.now(), this, 'اللغة الإنجليزية')"
           onfocus="openSignVideo('assets/videos/english-sign.mp4?v=' + Date.now(), this, 'اللغة الإنجليزية')">
        <div class="subject-icon-wrap">
          <img src="assets/images/english-icon.png" alt="اللغة الإنجليزية" class="subject-icon">
        </div>
        <div class="title" id="englishTitle">اللغة الإنجليزية</div>
        <div class="desc" id="englishDesc">تعلم الحروف، الكلمات، الجمل، والتواصل بثقة وسهولة.</div>
      </div>

      <div class="card orange" tabindex="0"
           onmouseenter="openSignVideo('assets/videos/math-sign.mp4?v=' + Date.now(), this, 'الرياضيات')"
           onfocus="openSignVideo('assets/videos/math-sign.mp4?v=' + Date.now(), this, 'الرياضيات')">
        <div class="subject-icon-wrap">
          <img src="assets/images/math-icon.png" alt="الرياضيات" class="subject-icon">
        </div>
        <div class="title" id="mathTitle">الرياضيات</div>
        <div class="desc" id="mathDesc">تعلم الأعداد، العد، العمليات الحسابية، وحل المسائل بطريقة شيقة.</div>
      </div>

      <div class="card green" tabindex="0"
           onmouseenter="openSignVideo('assets/videos/general-sign.mp4?v=' + Date.now(), this, 'الثقافة العامة')"
           onfocus="openSignVideo('assets/videos/general-sign.mp4?v=' + Date.now(), this, 'الثقافة العامة')">
        <div class="subject-icon-wrap">
          <img src="assets/images/general-icon.png" alt="الثقافة العامة" class="subject-icon">
        </div>
        <div class="title" id="generalTitle">الثقافة العامة</div>
        <div class="desc" id="generalDesc">تعلم الفصول، الطقس، الحيوانات، أيام الأسبوع، والفواكه والخضار بطريقة مرئية.</div>
      </div>

    </div>
  </main>
</div>

<div id="about-section"
     class="about-section"
     data-sign-video="assets/videos/about-sign.mp4"
     data-sign-text="لماذا تعد منصة تمكين مناسبة لطفلك؟">

  <div class="about-decor">
    <span class="spark sp1">✦</span>
    <span class="spark sp2">✦</span>
  </div>

  <h1 class="about-title" id="aboutTitle">
    لماذا تعد <span> منصة تمكين</span> مناسبة لطفلك؟
  </h1>

  <div class="about-cards">

    <div class="about-card orange" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/sign-support.mp4?v=' + Date.now(), this, 'دعم لغة الإشارة')"
         onfocus="openSignVideo('assets/videos/sign-support.mp4?v=' + Date.now(), this, 'دعم لغة الإشارة')">
      <div class="about-img-box">
        <img src="assets/images/about/sign-support.png" alt="دعم لغة الإشارة">
      </div>
      <div class="about-card-text">
        <h3 id="feature2Title">دعم لغة الإشارة</h3>
        <p id="feature2Desc">المنصة مهيأة لإضافة شرح مرئي بلغة الإشارة بشكل تدريجي.</p>
      </div>
    </div>

    <div class="about-card mint" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/interactive-learning.mp4?v=' + Date.now(), this, 'تعليم تفاعلي ممتع')"
         onfocus="openSignVideo('assets/videos/interactive-learning.mp4?v=' + Date.now(), this, 'تعليم تفاعلي ممتع')">
      <div class="about-img-box">
        <img src="assets/images/about/interactive-learning.png" alt="تعليم تفاعلي ممتع">
      </div>
      <div class="about-card-text">
        <h3 id="feature3Title">تعليم تفاعلي ممتع</h3>
        <p id="feature3Desc">أنشطة وألعاب تجعل التعلم تجربة ممتعة وسهلة للطفل.</p>
      </div>
    </div>

    <div class="about-card pink" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/visual-learning.mp4?v=' + Date.now(), this, 'تعلم بصري واضح')"
         onfocus="openSignVideo('assets/videos/visual-learning.mp4?v=' + Date.now(), this, 'تعلم بصري واضح')">
      <div class="about-img-box">
        <img src="assets/images/about/visual-learning.png" alt="تعلم بصري واضح">
      </div>
      <div class="about-card-text">
        <h3 id="feature1Title">تعلم بصري واضح</h3>
        <p id="feature1Desc">محتوى يعتمد على الصور والرسوم لتسهيل الفهم والانتباه.</p>
      </div>
    </div>

    <div class="about-card blue" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/progress.mp4?v=' + Date.now(), this, 'تقدم ملحوظ و استمرار دائم')"
         onfocus="openSignVideo('assets/videos/progress.mp4?v=' + Date.now(), this, 'تقدم ملحوظ و استمرار دائم')">
      <div class="about-img-box">
        <img src="assets/images/about/progress.png" alt="تقدم ملحوظ و تطور">
      </div>
      <div class="about-card-text">
        <h3 id="feature5Title">تقدم ملحوظ و استمرار دائم</h3>
        <p id="feature5Desc">محتوى يتطور باستمرار ليلائم احتياجات الطفل ويحقق أفضل النتائج.</p>
      </div>
    </div>

    <div class="about-card cream" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/safe.mp4?v=' + Date.now(), this, 'بيئة آمنة لطفلك')"
         onfocus="openSignVideo('assets/videos/safe.mp4?v=' + Date.now(), this, 'بيئة آمنة لطفلك')">
      <div class="about-img-box">
        <img src="assets/images/about/safe.png" alt="بيئة آمنة لطفلك">
      </div>
      <div class="about-card-text">
        <h3 id="feature6Title">بيئة آمنة لطفلك</h3>
        <p id="feature6Desc">نحرص على توفير بيئة آمنة وخالية من أي محتوى غير مناسب.</p>
      </div>
    </div>

    <div class="about-card purple" tabindex="0"
         onmouseenter="openSignVideo('assets/videos/skills.mp4?v=' + Date.now(), this, 'تنمية التفكير والفهم')"
         onfocus="openSignVideo('assets/videos/skills.mp4?v=' + Date.now(), this, 'تنمية التفكير والفهم')">
      <div class="about-img-box">
        <img src="assets/images/about/skills.png" alt="تنمية التفكير والفهم">
      </div>
      <div class="about-card-text">
        <h3 id="feature4Title">تنمية التفكير والفهم</h3>
        <p id="feature4Desc">محتوى يساعد على تنمية مهارات التفكير والتركيز والذاكرة.</p>
      </div>
    </div>

  </div>
</div>

<footer class="footer"
        id="contact-section"
        data-sign-video="assets/videos/contact-sign.mp4"
        data-sign-text="تواصل معنا">

  <div class="footer-container">

    <div class="footer-box">
      <h2>منصة <span> تمكين</span></h2>
      <p>
        منصة تعليمية تفاعلية للأطفال الصم من عمر 4 إلى 8 سنوات، تعتمد على التعلم بلغة الإشارة و التعلم البصري والأنشطة الممتعة.
      </p>
    </div>

    <div class="footer-box">
      <h3>روابط سريعة</h3>

      <a href="#home"
         onclick="scrollToSection('home','assets/videos/home-sign.mp4?v=' + Date.now(),'منصة تمكين'); return false;"
         onmouseenter="openDashboardSignVideo('assets/videos/home-icon-sign.mp4?v=' + Date.now(), this, 'الرئيسية')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/home-icon-sign.mp4?v=' + Date.now(), this, 'الرئيسية')"
         onblur="leaveDashboardSignVideo()">
         الرئيسية
      </a>

      <a href="#subjects-section"
         onclick="scrollToSection('subjects-section','assets/videos/subjects-sign.mp4?v=' + Date.now(),'اختر المادة التعليمية وابدأ التعلم'); return false;"
         onmouseenter="openDashboardSignVideo('assets/videos/subjects-icon-sign.mp4?v=' + Date.now(), this, 'البرامج التعليمية')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/subjects-icon-sign.mp4?v=' + Date.now(), this, 'البرامج التعليمية')"
         onblur="leaveDashboardSignVideo()">
         البرامج التعليمية
      </a>

      <a href="#about-section"
         onclick="scrollToSection('about-section','assets/videos/about-sign.mp4?v=' + Date.now(),'لماذا تعد منصة تمكين مناسبة لطفلك؟'); return false;"
         onmouseenter="openDashboardSignVideo('assets/videos/about-icon-sign.mp4?v=' + Date.now(), this, 'عن المنصة')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/about-icon-sign.mp4?v=' + Date.now(), this, 'عن المنصة')"
         onblur="leaveDashboardSignVideo()">
         عن المنصة
      </a>

      <a href="#contact-section"
         onclick="scrollToSection('contact-section','assets/videos/contact-sign.mp4?v=' + Date.now(),'تواصل معنا'); return false;"
         onmouseenter="openDashboardSignVideo('assets/videos/contact-sign.mp4?v=' + Date.now(), this, 'تواصل معنا')"
         onmouseleave="leaveDashboardSignVideo()"
         onfocus="openDashboardSignVideo('assets/videos/contact-sign.mp4?v=' + Date.now(), this, 'تواصل معنا')"
         onblur="leaveDashboardSignVideo()">
         تواصل معنا
      </a>
    </div>

    <div class="footer-box">
      <h3>البرامج التعليمية</h3>

      <a tabindex="0"
         onmouseenter="openSignVideo('assets/videos/arabic-word.mp4?v=' + Date.now(), this, 'اللغة العربية')"
         onfocus="openSignVideo('assets/videos/arabic-word.mp4?v=' + Date.now(), this, 'اللغة العربية')">
        اللغة العربية
      </a>

      <a tabindex="0"
         onmouseenter="openSignVideo('assets/videos/english-word.mp4?v=' + Date.now(), this, 'اللغة الإنجليزية')"
         onfocus="openSignVideo('assets/videos/english-word.mp4?v=' + Date.now(), this, 'اللغة الإنجليزية')">
        اللغة الإنجليزية
      </a>

      <a tabindex="0"
         onmouseenter="openSignVideo('assets/videos/math-word.mp4?v=' + Date.now(), this, 'الرياضيات')"
         onfocus="openSignVideo('assets/videos/math-word.mp4?v=' + Date.now(), this, 'الرياضيات')">
        الرياضيات
      </a>

      <a tabindex="0"
         onmouseenter="openSignVideo('assets/videos/general-word.mp4?v=' + Date.now(), this, 'الثقافة العامة')"
         onfocus="openSignVideo('assets/videos/general-word.mp4?v=' + Date.now(), this, 'الثقافة العامة')">
        الثقافة العامة
      </a>
    </div>

    <div class="footer-box">
      <h3>تواصل معنا</h3>

      <div class="contact-item" tabindex="0"
           onmouseenter="openSignVideo('assets/videos/phone-sign.mp4?v=' + Date.now(), this, 'رقم الهاتف')"
           onfocus="openSignVideo('assets/videos/phone-sign.mp4?v=' + Date.now(), this, 'رقم الهاتف')">
        <span>📞</span>
        <p>+970 595 834 045</p>
      </div>

      <a class="contact-item"
         href="mailto:projectg1426@gmail.com"
         onmouseenter="openSignVideo('assets/videos/email-sign.mp4?v=' + Date.now(), this, 'البريد الإلكتروني')"
         onfocus="openSignVideo('assets/videos/email-sign.mp4?v=' + Date.now(), this, 'البريد الإلكتروني')"
         onclick="handleEmailClick(event, this)">
        <span>📧</span>
        <p>projectg1426@gmail.com</p>
      </a>
    </div>

  </div>

  <div class="footer-bottom">
    © 2026 منصة تمكين للأطفال الصم - جميع الحقوق محفوظة
  </div>

</footer>

<script src="assets/js/script.js"></script>

<?php
$sign_base = "";
$sign_video = "assets/videos/home-sign.mp4?v=" . time();
include "components/sign-language.php";
?>

<script>
function scrollToSection(id, videoPath = "", text = "") {
  const el = document.getElementById(id);
  if (!el) return;

  if (videoPath && typeof setSignVideo === "function") {
    setSignVideo(videoPath, el, false, text);
  }

  if (id === "home") {
    window.scrollTo({ top: 0, behavior: "smooth" });
    return;
  }

  const rect = el.getBoundingClientRect();
  const pageY = window.pageYOffset;
  const header = document.querySelector(".header");
  const headerHeight = header ? header.offsetHeight : 120;

  const target =
    rect.top + pageY
    - (window.innerHeight / 2)
    + (rect.height / 2)
    - (headerHeight / 2);

  window.scrollTo({ top: Math.max(target, 0), behavior: "smooth" });
}
</script>

<script>
(function () {
  if (!('ontouchstart' in window)) return;
  var pending = null;

  document.addEventListener('touchend', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signVideoBox, #signToggleBtn')) return;

    var target = e.target.closest('a, button, input, select, [onclick], [onmouseenter]') || e.target;

    if (!target || target === document.body) {
      pending = null;
      return;
    }

    if (pending !== target) {
      e.preventDefault();
      e.stopImmediatePropagation();
      pending = target;

      target.dispatchEvent(new MouseEvent('mouseenter', {
        bubbles: true
      }));
    } else {
      pending = null;
    }
  }, { passive: false });

  document.addEventListener('touchstart', function (e) {
    var box = document.getElementById('signVideoBox');
    if (!box || !box.classList.contains('active')) return;
    if (e.target.closest('#signVideoBox, #signToggleBtn')) return;

    var target = e.target.closest('a, button, input, select, [onclick], [onmouseenter]') || e.target;

    if (target !== pending) pending = null;
  }, { passive: true });
})();
</script>

<!-- ── مراقب لوحة الإشارة: يحرك المحتوى على الشاشات الكبيرة فقط ── -->
<script>
(function () {
  function watchSignPanel() {
    var box = document.getElementById('signVideoBox');

    if (!box) {
      setTimeout(watchSignPanel, 300);
      return;
    }

    var observer = new MutationObserver(function () {
      document.body.classList.toggle(
        'sign-panel-open',
        box.classList.contains('active')
      );
    });

    observer.observe(box, {
      attributes: true,
      attributeFilter: ['class']
    });
  }

  watchSignPanel();
})();
</script>

<!-- ── تحديث كاش جميع الفيديوهات الموجودة في الصفحة ── -->
<script>
(function () {
  function refreshVideoCache() {
    document.querySelectorAll('video source').forEach(function (source) {
      var src = source.getAttribute('src');

      if (!src) return;

      src = src.split('?')[0] + '?v=' + Date.now();

      source.setAttribute('src', src);

      var video = source.closest('video');

      if (video) {
        video.load();
      }
    });
  }

  refreshVideoCache();
})();
</script>

<!-- ── تحديث كاش فيديوهات لوحة الإشارة ── -->
<script>
(function () {

  function addCacheVersion(videoPath) {
    if (!videoPath || typeof videoPath !== 'string') {
      return videoPath;
    }

    var cleanPath = videoPath.split('?')[0];

    return cleanPath + '?v=' + Date.now();
  }

  var originalOpenDashboardSignVideo = window.openDashboardSignVideo;

  if (typeof originalOpenDashboardSignVideo === 'function') {

    window.openDashboardSignVideo = function (videoPath, element, text) {

      return originalOpenDashboardSignVideo.call(
        this,
        addCacheVersion(videoPath),
        element,
        text
      );

    };

  }

  var originalOpenSignVideo = window.openSignVideo;

  if (typeof originalOpenSignVideo === 'function') {

    window.openSignVideo = function (videoPath, element, text) {

      return originalOpenSignVideo.call(
        this,
        addCacheVersion(videoPath),
        element,
        text
      );

    };

  }

  var originalSetSignVideo = window.setSignVideo;

  if (typeof originalSetSignVideo === 'function') {

    window.setSignVideo = function (videoPath, element, autoScroll, text) {

      return originalSetSignVideo.call(
        this,
        addCacheVersion(videoPath),
        element,
        autoScroll,
        text
      );

    };

  }

})();
</script>

</body>
</html>