
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// حدد نوع الجلسة قبل بدئها بناءً على الـ role المُرسل
$_login_role = trim($_POST["role"] ?? "student");

if ($_login_role === "admin") {
    require_once "../config/session_admin.php";
} else {
    require_once "../config/session_child.php";
}

require_once "../config/db.php";

$error = "";
$error_video = "";
$error_sign_text = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role = trim($_POST["role"] ?? "student");

    if ($username === "" || $password === "") {

        $error = "يرجى إدخال بيانات الدخول.";
        $error_video = "../assets/videos/empty-fields.mp4";
        $error_sign_text = "يرجى إدخال اسم المستخدم وكلمة المرور";

    } else {

        if ($role === "admin") {

            // أولاً: تحقق من جدول admins
            $sql = "SELECT * FROM admins WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            // ثانياً: إذا لم يوجد في admins، تحقق من supervisors
            if (!$admin) {

                $chkTbl = mysqli_query(
                    $conn,
                    "SHOW TABLES LIKE 'supervisors'"
                );

                if ($chkTbl && mysqli_num_rows($chkTbl) > 0) {

                    $ss = mysqli_prepare(
                        $conn,
                        "SELECT * FROM supervisors WHERE username=? AND is_active=1"
                    );

                    mysqli_stmt_bind_param($ss, 's', $username);
                    mysqli_stmt_execute($ss);

                    $sup = mysqli_fetch_assoc(
                        mysqli_stmt_get_result($ss)
                    );

                    if (
                        $sup &&
                        password_verify(
                            $password,
                            $sup['password_hash']
                        )
                    ) {

                        $_SESSION["admin_id"] = $sup["id"];
                        $_SESSION["admin_username"] = $sup["username"];
                        $_SESSION["admin_role"] = "supervisor";
                        $_SESSION["supervisor_id"] = $sup["id"];

                        header(
                            "Location: ../admin/supervisor-dashboard.php"
                        );
                        exit;
                    }
                }

                $error = "هذا الحساب غير موجود.";
                $error_video = "../assets/videos/user-not-found.mp4";
                $error_sign_text = "هذا الحساب غير موجود";

            } elseif (
                !password_verify(
                    $password,
                    $admin["password_hash"]
                )
            ) {

                $error = "كلمة المرور غير صحيحة.";
                $error_video = "../assets/videos/wrong-password.mp4";
                $error_sign_text = "كلمة المرور غير صحيحة";

            } else {

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["admin_role"] =
                    $admin["role"] ?? "admin";

                header("Location: ../admin/dashboard.php");
                exit;
            }

        } else {

            $sql = "SELECT * FROM children WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (!$child = mysqli_fetch_assoc($result)) {

                $error = "هذا الحساب غير موجود.";
                $error_video =
                    "../assets/videos/user-not-found.mp4";
                $error_sign_text = "هذا الحساب غير موجود";

            } elseif (
                !password_verify(
                    $password,
                    $child["password_hash"]
                )
            ) {

                $error = "كلمة المرور غير صحيحة.";
                $error_video =
                    "../assets/videos/wrong-password.mp4";
                $error_sign_text = "كلمة المرور غير صحيحة";

            } else {

                $_SESSION["user_id"] = $child["id"];
                $_SESSION["username"] = $child["username"];
                $_SESSION["child_name"] = $child["username"];
                $_SESSION["role"] = "child";

                // تحديث آخر تسجيل دخول
                $upd = mysqli_prepare(
                    $conn,
                    "UPDATE children SET last_login = NOW() WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $upd,
                    "i",
                    $child["id"]
                );

                mysqli_stmt_execute($upd);

                header("Location: children.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
تسجيل الدخول - <?php echo date('H:i:s'); ?>
</title>

<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/login.css?v=8">

<style>

.login-header {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
}

.login-nav {
    flex: 1 !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 20px !important;
    padding-right: 80px !important;
}

.logo-box {
    position: absolute !important;
    right: 16px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
}

</style>

<style>

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

.login-nav,
.login-header,
.icon-wrap.nav-btn {
    overflow: visible !important;
}

.icon-wrap.nav-btn {
    position: relative !important;
    display: inline-flex !important;
    justify-content: center !important;
    align-items: center !important;
    text-decoration: none !important;
}

.icon-wrap.nav-btn .nav-label {
    position: absolute !important;
    top: 76px !important;
    left: 50% !important;
    right: auto !important;
    transform: translateX(-50%) !important;
    background: rgba(255, 127, 134, 0.92) !important;
    color: #fff !important;
    padding: 6px 14px !important;
    border-radius: 10px !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    font-family: Arial, sans-serif !important;
    white-space: nowrap !important;
    opacity: 0 !important;
    display: block !important;
    visibility: hidden !important;
    pointer-events: none !important;
    transition: 0.2s ease !important;
    z-index: 99999 !important;
    text-align: center !important;
    box-shadow: 0 4px 12px rgba(255, 127, 134, 0.35) !important;
}

.icon-wrap.nav-btn:hover .nav-label,
.icon-wrap.nav-btn:focus .nav-label {
    opacity: 1 !important;
    visibility: visible !important;
}

.icon-wrap.nav-btn .nav-label::after {
    content: "" !important;
    position: absolute !important;
    top: -6px !important;
    left: 50% !important;
    right: auto !important;
    transform: translateX(-50%) !important;
    border-width: 0 6px 6px 6px !important;
    border-style: solid !important;
    border-color:
        transparent
        transparent
        rgba(255, 127, 134, 0.92)
        transparent !important;
}

/* التظليل الافتراضي */
.sign-active-target {
    outline: 0.1px dashed rgba(255, 120, 120, 0.4) !important;
    box-shadow: 0 0 10px rgba(255, 120, 120, 0.2) !important;
    border-radius: 8px !important;
}

/* التظليل على الكلمة */
.icon-wrap.nav-btn .nav-label.sign-active-target {
    opacity: 1 !important;
    visibility: visible !important;
    background: rgba(255, 127, 134, 0.9) !important;
    box-shadow: none !important;
    border-radius: 10px !important;
    color: #fff !important;
}

.icon-wrap::after,
.nav-btn::after {
    display: none !important;
    content: none !important;
}

/* توم وجيري */
.tom-run {
    width: 130px !important;
    height: auto !important;
}

.jerry-run {
    width: 90px !important;
    height: auto !important;
}

</style>

</head>

<body class="auth-body">

<header
    class="header login-header"
    style="
        display:flex !important;
        justify-content:center !important;
        align-items:center !important;
        position:relative !important;
    "
>

<nav
    class="nav login-nav"
    style="
        display:flex !important;
        flex-direction:row !important;
        align-items:center !important;
        justify-content:center !important;
        gap:20px !important;
        flex:unset !important;
        margin-right:60px !important;
    "
>

<a
    href="../index.php"
    class="icon-wrap nav-btn"
    onmouseenter="openDashboardSignVideo('../assets/videos/home-icon-sign.mp4', this, 'الرئيسية')"
    onmouseleave="leaveDashboardSignVideo()"
    onfocus="openDashboardSignVideo('../assets/videos/home-icon-sign.mp4', this, 'الرئيسية')"
    onblur="leaveDashboardSignVideo()"
>

    <span class="circle-icon">

        <video autoplay muted loop playsinline>

            <source
                src="../assets/icons/home.mp4"
                type="video/mp4"
            >

        </video>

    </span>

    <span class="nav-label">الرئيسية</span>

</a>


<a
    href="../index.php#contact-section"
    class="icon-wrap nav-btn"
    onmouseenter="openDashboardSignVideo('../assets/videos/contact-sign.mp4', this, 'تواصل معنا')"
    onmouseleave="leaveDashboardSignVideo()"
    onfocus="openDashboardSignVideo('../assets/videos/contact-sign.mp4', this, 'تواصل معنا')"
    onblur="leaveDashboardSignVideo()"
>

    <span class="circle-icon">

        <video autoplay muted loop playsinline>

            <source
                src="../assets/images/contact.mp4"
                type="video/mp4"
            >

        </video>

    </span>

    <span class="nav-label">تواصل معنا</span>

</a>

</nav>


<a
    href="../index.php"
    class="logo-box"
    style="
        position:absolute;
        right:16px;
        top:50%;
        transform:translateY(-50%);
    "
>

    <img
        src="../logo.png"
        alt="تمكين"
    >

</a>

</header>


<div class="login-page">

<div
    class="characters-runner"
    aria-hidden="true"
>

    <img
        src="../assets/images/jerry.gif"
        class="runner jerry-run"
        alt=""
        style="width:90px;height:auto;"
    >

    <img
        src="../assets/images/tom.gif"
        class="runner tom-run"
        alt=""
        style="width:130px;height:auto;"
    >

</div>


<div
    class="login-card"
    data-sign-video="../assets/videos/login-sign.mp4"
>


<div class="auth-tabs">

<button
    type="button"
    class="auth-tab active"
    id="childrenTab"
    onmouseenter="playHoverSign(this, '../assets/videos/login-child.mp4', 'اضغط هنا لدخول الأطفال')"
    onmouseleave="stopHoverSign()"
>

    دخول الأطفال

</button>


<button
    type="button"
    class="auth-tab"
    id="adminTab"
    onmouseenter="playHoverSign(this, '../assets/videos/login-admin.mp4', 'اضغط هنا لدخول الأدمن')"
    onmouseleave="stopHoverSign()"
>

    دخول الأدمن

</button>

</div>


<?php if ($error !== ""): ?>

<div
    class="form-message error-message"
    id="loginError"
    data-sign-video="<?php echo htmlspecialchars($error_video); ?>"
    data-sign-text="<?php echo htmlspecialchars($error_sign_text); ?>"
>

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>


<form method="POST">

<input
    type="hidden"
    name="role"
    id="roleInput"
    value="<?php echo htmlspecialchars($role ?? 'student'); ?>"
>


<div class="input-box">

<label>اسم المستخدم</label>

<input
    type="text"
    name="username"
    placeholder="أدخل اسم المستخدم"
    onmouseenter="playHoverSign(this, '../assets/videos/username.mp4', 'ادخل اسم المستخدم')"
    onmouseleave="stopHoverSign()"
>


<label>كلمة المرور </label>

<input
    type="password"
    name="password"
    placeholder="أدخل كلمة المرور"
    onmouseenter="playHoverSign(this, '../assets/videos/password.mp4', 'ادخل كلمة المرور')"
    onmouseleave="stopHoverSign()"
>


<button
    type="submit"
    class="primary-btn"
    id="loginSubmitBtn"
    onmouseenter="playHoverSign(this, '../assets/videos/login-btn.mp4', 'اضغط هنا لتسجيل الدخول')"
    onmouseleave="stopHoverSign()"
    onfocus="playHoverSign(this, '../assets/videos/login-btn.mp4', 'اضغط هنا لتسجيل الدخول')"
    onblur="stopHoverSign()"
>

تسجيل الدخول

</button>

</div>

</form>


<p
    class="auth-footer"
    onmouseenter="playHoverSign(this, '../assets/videos/create-account.mp4', 'ليس لديك حساب؟ إنشاء حساب')"
    onmouseleave="stopHoverSign()"
    onfocus="playHoverSign(this, '../assets/videos/create-account.mp4', 'ليس لديك حساب؟ إنشاء حساب')"
    onblur="stopHoverSign()"
>

ليس لديك حساب؟

<a href="register.php">إنشاء حساب</a>

</p>

</div>

</div>


<script>

/* =========================================================
   متغيرات فيديو الإشارة
   ========================================================= */

let currentSignVideoPath =
    "../assets/videos/login-sign.mp4";

let currentSignTarget = null;

let currentSignText =
    "مرحبًا، هذه صفحة تسجيل الدخول";


/* =========================================================
   إضافة رقم جديد للفيديو لمنع الكاش
   ========================================================= */

function freshVideoPath(videoPath) {

    if (!videoPath) {
        return videoPath;
    }

    const cleanPath =
        videoPath.split("?")[0];

    return cleanPath + "?v=" + Date.now();
}


/* =========================================================
   التحقق من وجود رسالة خطأ
   ========================================================= */

function hasVisibleLoginError() {

    const errorBox =
        document.getElementById("loginError");

    return (
        errorBox &&
        errorBox.style.display !== "none"
    );
}


/* =========================================================
   حفظ الفيديو الحالي
   ========================================================= */

function saveCurrentSign(videoPath, element, text) {

    currentSignVideoPath =
        videoPath;

    currentSignTarget =
        element;

    currentSignText =
        text || "";
}


/* =========================================================
   الحفاظ على فيديو الخطأ
   ========================================================= */

function keepErrorSign() {

    const errorBox =
        document.getElementById("loginError");

    const box =
        document.getElementById("signVideoBox");

    if (
        !errorBox ||
        errorBox.style.display === "none"
    ) {
        return false;
    }

    if (
        !box ||
        !box.classList.contains("active")
    ) {
        return true;
    }

    saveCurrentSign(
        freshVideoPath(
            errorBox.dataset.signVideo
        ),
        errorBox,
        errorBox.dataset.signText
    );

    setSignVideo(
        currentSignVideoPath,
        currentSignTarget,
        false,
        currentSignText
    );

    showSignText(currentSignText);

    setActiveSignTarget(errorBox);

    return true;
}


/* =========================================================
   تشغيل فيديو عند التركيز
   ========================================================= */

function playFocusSign(
    element,
    videoPath,
    text
) {

    const box =
        document.getElementById("signVideoBox");

    if (
        !box ||
        !box.classList.contains("active")
    ) {
        return;
    }

    if (keepErrorSign()) {
        return;
    }

    videoPath =
        freshVideoPath(videoPath);

    saveCurrentSign(
        videoPath,
        element,
        text
    );

    openSignVideo(
        videoPath,
        element,
        text
    );

    showSignText(text);
}


/* =========================================================
   تشغيل فيديو عند المرور
   ========================================================= */

function playHoverSign(
    element,
    videoPath,
    text
) {

    const box =
        document.getElementById("signVideoBox");

    if (
        !box ||
        !box.classList.contains("active")
    ) {
        return;
    }

    if (keepErrorSign()) {
        return;
    }

    videoPath =
        freshVideoPath(videoPath);

    saveCurrentSign(
        videoPath,
        element,
        text
    );

    openSignVideo(
        videoPath,
        element,
        text
    );

    showSignText(text);
}


/* =========================================================
   إيقاف فيديو المرور
   ========================================================= */

function stopHoverSign() {

    if (keepErrorSign()) {
        return;
    }

    const video =
        document.getElementById("signVideo");

    const playBtn =
        document.getElementById("signPlayBtn");

    if (video) {

        video.pause();

        video.currentTime = 0;
    }

    if (playBtn) {

        playBtn.classList.remove("hide");

    }

    clearActiveSignTarget();
}


/* =========================================================
   إعدادات التبويبات والحقول
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const childrenTab =
            document.getElementById("childrenTab");

        const adminTab =
            document.getElementById("adminTab");

        const roleInput =
            document.getElementById("roleInput");

        const errorBox =
            document.getElementById("loginError");

        const inputs =
            document.querySelectorAll("input");


        if (
            childrenTab &&
            adminTab &&
            roleInput
        ) {

            childrenTab.addEventListener(
                "click",
                function () {

                    childrenTab.classList.add("active");

                    adminTab.classList.remove("active");

                    roleInput.value = "student";

                }
            );


            adminTab.addEventListener(
                "click",
                function () {

                    adminTab.classList.add("active");

                    childrenTab.classList.remove("active");

                    roleInput.value = "admin";

                }
            );

        }


        inputs.forEach(
            function (input) {

                input.addEventListener(
                    "input",
                    function () {

                        if (errorBox) {

                            errorBox.style.display =
                                "none";

                        }


                        const card =
                            document.querySelector(
                                ".login-card"
                            );

                        const playBtn =
                            document.getElementById(
                                "signPlayBtn"
                            );


                        saveCurrentSign(
                            freshVideoPath(
                                "../assets/videos/login-sign.mp4"
                            ),
                            card,
                            "مرحبًا، هذه صفحة تسجيل الدخول"
                        );


                        setSignVideo(
                            currentSignVideoPath,
                            currentSignTarget,
                            false,
                            currentSignText
                        );


                        showSignText(
                            currentSignText
                        );


                        clearActiveSignTarget();


                        if (playBtn) {

                            playBtn.classList.remove(
                                "hide"
                            );

                        }

                    }
                );

            }
        );


        if (window.history.replaceState) {

            window.history.replaceState(
                null,
                null,
                window.location.href
            );

        }

    }
);

</script>


<?php

$sign_base = "../";

$sign_video =
    "../assets/videos/login-sign.mp4";

include "../components/sign-language.php";

?>


<script>

/* =========================================================
   مهم جداً:
   إجبار جميع دوال فيديو الإشارة على استخدام
   نسخة جديدة من الفيديو وعدم استخدام الكاش القديم
   ========================================================= */

(function () {

    const originalSetSignVideo =
        window.setSignVideo;

    const originalOpenSignVideo =
        window.openSignVideo;

    const originalOpenDashboardSignVideo =
        window.openDashboardSignVideo;


    if (
        typeof originalSetSignVideo ===
        "function"
    ) {

        window.setSignVideo =
            function (
                videoPath,
                element,
                ...args
            ) {

                videoPath =
                    freshVideoPath(videoPath);

                return originalSetSignVideo.call(
                    this,
                    videoPath,
                    element,
                    ...args
                );

            };

    }


    if (
        typeof originalOpenSignVideo ===
        "function"
    ) {

        window.openSignVideo =
            function (
                videoPath,
                element,
                ...args
            ) {

                videoPath =
                    freshVideoPath(videoPath);

                return originalOpenSignVideo.call(
                    this,
                    videoPath,
                    element,
                    ...args
                );

            };

    }


    if (
        typeof originalOpenDashboardSignVideo ===
        "function"
    ) {

        window.openDashboardSignVideo =
            function (
                videoPath,
                element,
                ...args
            ) {

                videoPath =
                    freshVideoPath(videoPath);

                return originalOpenDashboardSignVideo.call(
                    this,
                    videoPath,
                    element,
                    ...args
                );

            };

    }

})();


/* =========================================================
   تحديد العنصر النشط
   ========================================================= */

function setActiveSignTarget(element) {

    clearActiveSignTarget();

    if (!element) {
        return;
    }


    const circle =
        element.classList.contains("circle-icon")
            ? element
            : element.querySelector(".circle-icon");


    if (circle) {

        const label =
            circle.parentElement
                ? circle.parentElement.querySelector(
                    ".nav-label"
                )
                : null;


        if (label) {

            label.classList.add(
                "sign-active-target"
            );


            label.style.setProperty(
                "opacity",
                "1",
                "important"
            );


            label.style.setProperty(
                "visibility",
                "visible",
                "important"
            );


            label.style.setProperty(
                "background",
                "rgba(255, 127, 134, 0.9)",
                "important"
            );


            label.style.setProperty(
                "color",
                "#fff",
                "important"
            );


            label.style.setProperty(
                "border-radius",
                "10px",
                "important"
            );


            label.style.setProperty(
                "box-shadow",
                "none",
                "important"
            );

        } else {

            circle.classList.add(
                "sign-active-target"
            );

        }

    } else {

        element.classList.add(
            "sign-active-target"
        );

    }

}


/* =========================================================
   إزالة العنصر النشط
   ========================================================= */

function clearActiveSignTarget() {

    document
        .querySelectorAll(".sign-active-target")
        .forEach(
            function (el) {

                el.classList.remove(
                    "sign-active-target"
                );


                el.style.removeProperty(
                    "opacity"
                );

                el.style.removeProperty(
                    "visibility"
                );

                el.style.removeProperty(
                    "background"
                );

                el.style.removeProperty(
                    "color"
                );

                el.style.removeProperty(
                    "border-radius"
                );

                el.style.removeProperty(
                    "box-shadow"
                );

            }
        );

}


/* =========================================================
   نص فيديو الإشارة
   ========================================================= */

function showSignText(text = "") {

    const textBox =
        document.getElementById(
            "signHelperText"
        );

    if (!textBox) {
        return;
    }


    textBox.textContent =
        text || "";


    textBox.style.display =
        (
            text &&
            text.trim() !== ""
        )
            ? "block"
            : "none";

}

</script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const box =
            document.getElementById(
                "signVideoBox"
            );

        const video =
            document.getElementById(
                "signVideo"
            );

        const playBtn =
            document.getElementById(
                "signPlayBtn"
            );

        const errorBox =
            document.getElementById(
                "loginError"
            );

        const card =
            document.querySelector(
                ".login-card"
            );


        if (!box || !video) {
            return;
        }


        const firstHelpText =
            "مرحبًا، هذه صفحة تسجيل الدخول";


        function showFirstHelpText() {

            if (
                !box.classList.contains(
                    "active"
                )
            ) {
                return;
            }


            if (
                errorBox &&
                errorBox.style.display !==
                "none"
            ) {
                return;
            }


            saveCurrentSign(
                freshVideoPath(
                    "../assets/videos/login-sign.mp4"
                ),
                card,
                firstHelpText
            );


            setSignVideo(
                currentSignVideoPath,
                currentSignTarget,
                false,
                currentSignText
            );


            showSignText(
                currentSignText
            );


            if (playBtn) {

                playBtn.classList.remove(
                    "hide"
                );

            }

        }


        const observer =
            new MutationObserver(
                function () {

                    if (keepErrorSign()) {
                        return;
                    }

                    showFirstHelpText();

                }
            );


        observer.observe(
            box,
            {
                attributes: true,
                attributeFilter: ["class"]
            }
        );


        showFirstHelpText();


        if (playBtn) {

            playBtn.onclick =
                function (e) {

                    e.preventDefault();
                    e.stopPropagation();


                    if (currentSignVideoPath) {

                        setSignVideo(
                            freshVideoPath(
                                currentSignVideoPath
                            ),
                            currentSignTarget,
                            false,
                            currentSignText
                        );


                        showSignText(
                            currentSignText
                        );

                    }


                    video.play()
                        .then(
                            function () {

                                sectionVideoStarted =
                                    true;

                                playBtn.classList.add(
                                    "hide"
                                );

                            }
                        )
                        .catch(
                            function () {

                                playBtn.classList.remove(
                                    "hide"
                                );

                            }
                        );

                };

        }


        video.addEventListener(
            "play",
            function () {

                if (playBtn) {

                    playBtn.classList.add(
                        "hide"
                    );

                }

            }
        );


        video.addEventListener(
            "pause",
            function () {

                if (
                    playBtn &&
                    video.currentTime <
                    video.duration
                ) {

                    playBtn.classList.remove(
                        "hide"
                    );

                }

            }
        );


        video.addEventListener(
            "ended",
            function () {

                if (playBtn) {

                    playBtn.classList.remove(
                        "hide"
                    );

                }

            }
        );

    }
);

</script>


<?php if ($error !== ""): ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        function runErrorSign() {

            const errorBox =
                document.getElementById(
                    "loginError"
                );

            const box =
                document.getElementById(
                    "signVideoBox"
                );

            const playBtn =
                document.getElementById(
                    "signPlayBtn"
                );

            const video =
                document.getElementById(
                    "signVideo"
                );


            if (
                !errorBox ||
                !box ||
                !video
            ) {

                setTimeout(
                    runErrorSign,
                    100
                );

                return;
            }


            if (
                !box.classList.contains(
                    "active"
                )
            ) {
                return;
            }


            saveCurrentSign(
                freshVideoPath(
                    errorBox.dataset.signVideo
                ),
                errorBox,
                errorBox.dataset.signText
            );


            setSignVideo(
                currentSignVideoPath,
                currentSignTarget,
                false,
                currentSignText
            );


            showSignText(
                currentSignText
            );


            setActiveSignTarget(
                errorBox
            );


            video.play()
                .then(
                    function () {

                        sectionVideoStarted =
                            true;

                        if (playBtn) {

                            playBtn.classList.add(
                                "hide"
                            );

                        }

                    }
                )
                .catch(
                    function () {

                        if (playBtn) {

                            playBtn.classList.remove(
                                "hide"
                            );

                        }

                    }
                );

        }


        runErrorSign();

    }
);

</script>

<?php endif; ?>


<script>

/* =========================================================
   الضغط خارج بطاقة تسجيل الدخول
   ========================================================= */

document.addEventListener(
    "click",
    function (e) {

        const errorBox =
            document.getElementById(
                "loginError"
            );


        if (
            errorBox &&
            errorBox.style.display !==
            "none"
        ) {

            keepErrorSign();

            return;
        }


        const card =
            document.querySelector(
                ".login-card"
            );

        const box =
            document.getElementById(
                "signVideoBox"
            );

        const playBtn =
            document.getElementById(
                "signPlayBtn"
            );


        if (!card || !box) {
            return;
        }


        if (card.contains(e.target)) {
            return;
        }


        if (
            !box.classList.contains(
                "active"
            )
        ) {
            return;
        }


        saveCurrentSign(
            freshVideoPath(
                "../assets/videos/login-sign.mp4"
            ),
            card,
            "مرحبًا، هذه صفحة تسجيل الدخول"
        );


        setSignVideo(
            currentSignVideoPath,
            currentSignTarget,
            false,
            currentSignText
        );


        showSignText(
            currentSignText
        );


        if (playBtn) {

            playBtn.classList.remove(
                "hide"
            );

        }


        clearActiveSignTarget();

    }
);


/* =========================================================
   عند مغادرة بطاقة تسجيل الدخول
   ========================================================= */

const loginCard =
    document.querySelector(
        ".login-card"
    );


if (loginCard) {

    loginCard.addEventListener(
        "mouseleave",
        function () {

            const errorBox =
                document.getElementById(
                    "loginError"
                );


            if (
                errorBox &&
                errorBox.style.display !==
                "none"
            ) {

                errorBox.style.display =
                    "none";


                const box =
                    document.getElementById(
                        "signVideoBox"
                    );

                const playBtn =
                    document.getElementById(
                        "signPlayBtn"
                    );


                if (
                    !box ||
                    !box.classList.contains(
                        "active"
                    )
                ) {
                    return;
                }


                saveCurrentSign(
                    freshVideoPath(
                        "../assets/videos/login-sign.mp4"
                    ),
                    loginCard,
                    "مرحباً، هذه صفحة تسجيل الدخول"
                );


                setSignVideo(
                    currentSignVideoPath,
                    currentSignTarget,
                    false,
                    currentSignText
                );


                showSignText(
                    currentSignText
                );


                if (playBtn) {

                    playBtn.classList.remove(
                        "hide"
                    );

                }


                clearActiveSignTarget();

            }

        }
    );

}

</script>


<script>

/* =========================================================
   الموبايل:
   أول ضغطة تعرض فيديو الإشارة
   ثاني ضغطة تنفذ العنصر
   ========================================================= */

(function () {

    if (!('ontouchstart' in window)) {
        return;
    }


    var pending = null;


    document.addEventListener(
        'touchend',
        function (e) {

            var box =
                document.getElementById(
                    'signVideoBox'
                );


            if (
                !box ||
                !box.classList.contains(
                    'active'
                )
            ) {
                return;
            }


            // استثناء صندوق الفيديو وزر الإشارة
            if (
                e.target.closest(
                    '#signVideoBox, #signToggleBtn'
                )
            ) {
                return;
            }


            // أي عنصر تفاعلي
            var target =
                e.target.closest(
                    'a, button, input, select, textarea, [onclick], [onmouseenter]'
                ) || e.target;


            if (
                !target ||
                target === document.body
            ) {

                pending = null;

                return;
            }


            // الضغطة الأولى
            if (pending !== target) {

                e.preventDefault();
                e.stopImmediatePropagation();

                pending = target;


                target.dispatchEvent(
                    new MouseEvent(
                        'mouseenter',
                        {
                            bubbles: true
                        }
                    )
                );

            } else {

                // الضغطة الثانية
                pending = null;

            }

        },
        {
            passive: false
        }
    );


    document.addEventListener(
        'touchstart',
        function (e) {

            var box =
                document.getElementById(
                    'signVideoBox'
                );


            if (
                !box ||
                !box.classList.contains(
                    'active'
                )
            ) {
                return;
            }


            if (
                e.target.closest(
                    '#signVideoBox, #signToggleBtn'
                )
            ) {
                return;
            }


            var target =
                e.target.closest(
                    'a, button, input, select, textarea, [onclick], [onmouseenter]'
                ) || e.target;


            if (target !== pending) {

                pending = null;

            }

        },
        {
            passive: true
        }
    );

})();

</script>


<script>

/* =========================================================
   تحديث كاش فيديوهات HTML الموجودة داخل الصفحة
   ========================================================= */

(function () {

    function refreshVideoSources() {

        document
            .querySelectorAll(
                'video source'
            )
            .forEach(
                function (source) {

                    var src =
                        source.getAttribute(
                            'src'
                        );


                    if (!src) {
                        return;
                    }


                    var cleanPath =
                        src.split("?")[0];


                    source.setAttribute(
                        'src',
                        cleanPath +
                        '?v=' +
                        Date.now()
                    );


                    var video =
                        source.closest(
                            'video'
                        );


                    if (video) {

                        video.load();

                    }

                }
            );

    }


    refreshVideoSources();

})();

</script>

</body>
</html>
