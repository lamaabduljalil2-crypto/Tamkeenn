<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/session_child.php';
require_once "../config/db.php";

$error = "";
$error_video = "";
$error_sign_text = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $phone_number = trim($_POST["phone_number"] ?? "");
    $phone = $phone_number;
    $gender = trim($_POST["gender"] ?? "");

    if ($username === "") {

        $error = "اسم المستخدم مطلوب.";
        $error_video = "../assets/videos/username.mp4";
        $error_sign_text = "اسم المستخدم مطلوب";

    } elseif (!preg_match("/^[\p{Arabic}a-zA-Z]+$/u", $username)) {

        $error = "اسم المستخدم يجب أن يحتوي على حروف فقط.";
        $error_video = "../assets/videos/username.mp4";
        $error_sign_text = "اسم المستخدم يجب أن يحتوي على حروف فقط";

    } elseif ($password === "") {

        $error = "كلمة المرور مطلوبة.";
        $error_video = "../assets/videos/password.mp4";
        $error_sign_text = "كلمة المرور مطلوبة";

    } elseif ($phone_number === "") {

        $error = "رقم الهاتف مطلوب.";
        $error_video = "../assets/videos/number.mp4";
        $error_sign_text = "رقم الهاتف مطلوب";

    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone_number)) {

        $error = "رقم الهاتف غير صحيح.";
        $error_video = "../assets/videos/number-wrong.mp4";
        $error_sign_text = "رقم الهاتف غير صحيح";

    } elseif (!in_array($gender, ["male", "female"])) {

        $error = "يرجى اختيار الجنس.";
        $error_video = "../assets/videos/gender.mp4";
        $error_sign_text = "يرجى اختيار الجنس";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM children WHERE username = ?"
        );

        $check->bind_param(
            "s",
            $username
        );

        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "هذا الإسم محجوز مسبقاً.";
            $error_video = "../assets/videos/pre-booked-user.mp4";
            $error_sign_text = "هذا الإسم محجوز مسبقاً";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO children
                (username, password_hash, gender, phone)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $username,
                $password_hash,
                $gender,
                $phone
            );

            if ($stmt->execute()) {

                header("Location: login.php");
                exit;

            } else {

                $error = "حدث خطأ.";
                $error_video = "../assets/videos/empty-fields.mp4";
                $error_sign_text = "حدث خطأ";
            }

            $stmt->close();
        }

        $check->close();
    }
}

$saved_phone = htmlspecialchars(
    $_POST['phone_number'] ?? ''
);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>إنشاء حساب</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

<link
    rel="stylesheet"
    href="../assets/css/login.css?v=8"
>

<style>

@media (max-width: 768px) {

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

}

</style>

<style>

.sign-active-target {
    outline: 0.1px dashed rgba(255, 120, 120, 0.4) !important;
    box-shadow: 0 0 10px rgba(255, 120, 120, 0.2) !important;
    border-radius: 8px !important;
}


/* nav-label */

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

    box-shadow:
        0 4px 12px
        rgba(255, 127, 134, 0.35) !important;
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

.icon-wrap.nav-btn .nav-label.sign-active-target {

    opacity: 1 !important;
    visibility: visible !important;

    background:
        rgba(255, 127, 134, 0.92) !important;

    box-shadow: none !important;

    border-radius: 10px !important;

    color: #fff !important;
}


.sign-helper-text {

    width: 100%;

    text-align: center;

    font-family: Arial, sans-serif;

    font-size: 14px;

    font-weight: 800;

    color: #21425f;

    margin-bottom: 8px;

    line-height: 1.5;
}


/* =========================================================
   رقم الهاتف
   ========================================================= */

.phone-number-input {

    width: 100%;

    box-sizing: border-box;

    height: 44px;

    padding: 10px 12px;

    font-size: 15px;

    direction: ltr;

    text-align: left;

    background: #fff;

    border: 1px solid #ccc;

    border-radius: 10px;

    outline: none;
}

.phone-number-input:focus {

    border-color: #21425f;

    box-shadow:
        0 0 0 2px
        rgba(33,66,95,.15);
}


.register-card {

    width: 100%;

    max-width: 440px;

    padding: 20px;
}


.register-title {

    font-size: 30px;
}


.input-box input,
.phone-number-input,
.gender-box {

    height: 44px;

    font-size: 14px;
}


.primary-btn {

    height: 48px;

    font-size: 17px;
}


@media (max-width: 768px) {

    .register-card {
        padding: 16px 14px;
    }

    .register-title {
        font-size: 22px;
    }

    .input-box input,
    .phone-number-input,
    .gender-box {

        height: 42px;

        font-size: 16px !important;
    }

    .primary-btn {

        height: 46px;

        font-size: 16px;
    }

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


<!-- =====================================================
     الرئيسية
     الفيديو المطلوب:
     ../assets/videos/home-icon-sign.mp4
     ===================================================== -->

<a
    href="../index.php"
    class="icon-wrap nav-btn"

    onmouseenter="
        openDashboardSignVideo(
            freshVideoPath('../assets/videos/home-icon-sign.mp4'),
            this,
            'الرئيسية'
        )
    "

    onmouseleave="
        leaveDashboardSignVideo()
    "

    onfocus="
        openDashboardSignVideo(
            freshVideoPath('../assets/videos/home-icon-sign.mp4'),
            this,
            'الرئيسية'
        )
    "

    onblur="
        leaveDashboardSignVideo()
    "
>

    <span class="circle-icon">

        <video
            autoplay
            muted
            loop
            playsinline
            preload="auto"
        >

            <source
                src="../assets/icons/home.mp4"
                type="video/mp4"
            >

        </video>

    </span>

    <span class="nav-label">
        الرئيسية
    </span>

</a>


<!-- =====================================================
     تواصل معنا
     ===================================================== -->

<a
    href="../index.php#contact-section"
    class="icon-wrap nav-btn"

    onmouseenter="
        openDashboardSignVideo(
            freshVideoPath('../assets/videos/contact-sign.mp4'),
            this,
            'تواصل معنا'
        )
    "

    onmouseleave="
        leaveDashboardSignVideo()
    "

    onfocus="
        openDashboardSignVideo(
            freshVideoPath('../assets/videos/contact-sign.mp4'),
            this,
            'تواصل معنا'
        )
    "

    onblur="
        leaveDashboardSignVideo()
    "
>

    <span class="circle-icon">

        <video
            autoplay
            muted
            loop
            playsinline
            preload="auto"
        >

            <source
                src="../assets/images/contact.mp4"
                type="video/mp4"
            >

        </video>

    </span>

    <span class="nav-label">
        تواصل معنا
    </span>

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


<div class="register-page">


<div
    class="register-card"
    data-sign-video="../assets/videos/register-sign.mp4"
>


<h2 class="register-title">
    إنشاء حساب الأطفال
</h2>


<?php if ($error !== ""): ?>

<div
    class="form-message error-message"
    id="registerError"

    data-sign-video="<?php
        echo htmlspecialchars($error_video);
    ?>"

    data-sign-text="<?php
        echo htmlspecialchars($error_sign_text);
    ?>"
>

<?php
echo htmlspecialchars($error);
?>

</div>

<?php endif; ?>


<form
    class="auth-form"
    method="POST"
>


<div class="input-box">

<label for="username">
    اسم المستخدم
</label>

<input
    id="username"
    type="text"
    name="username"
    placeholder="أدخل اسم المستخدم"

    value="<?php
        echo htmlspecialchars(
            $_POST['username'] ?? ''
        );
    ?>"

    required

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/username.mp4',
            'ادخل اسم المستخدم'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

</div>


<div class="input-box">

<label for="password">
    كلمة المرور
</label>

<input
    id="password"
    type="password"
    name="password"
    placeholder="أدخل كلمة المرور"

    required

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/password.mp4',
            'ادخل كلمة المرور'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

</div>


<!-- =====================================================
     رقم الهاتف فقط
     ===================================================== -->

<div class="input-box">

<label for="phone_number">
    رقم الهاتف
</label>

<input
    id="phone_number"
    type="tel"
    name="phone_number"
    class="phone-number-input"
    placeholder="أدخل رقم الهاتف"

    value="<?php
        echo $saved_phone;
    ?>"

    inputmode="numeric"
    autocomplete="tel"

    required

    oninput="
        this.value =
        this.value.replace(/[^0-9]/g, '')
    "

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/number.mp4',
            'ادخل رقم الهاتف'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

</div>


<div class="two-fields">


<div class="input-box">

<label for="gender">
    الجنس
</label>


<div class="input-box">


<div
    class="gender-select"
    id="genderSelect"
>


<button
    type="button"
    class="gender-box"
    id="genderBox"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/gender.mp4',
            'اختر الجنس'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

    <span id="genderText">
        اختر الجنس
    </span>

</button>


<div
    class="gender-menu"
    id="genderMenu"
>


<div
    class="gender-option"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/gender.mp4',
            'اختر الجنس'
        )
    "

    onmouseleave="
        stopHoverSign()
    "

    onclick="
        chooseGender('', 'اختر الجنس')
    "
>

    اختر الجنس

</div>


<div
    class="gender-option boy"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/boy.mp4',
            'ذكر'
        )
    "

    onmouseleave="
        stopHoverSign()
    "

    onclick="
        chooseGender('male', 'ذكر')
    "
>

    ذكر

</div>


<div
    class="gender-option girl"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/girl.mp4',
            'أنثى'
        )
    "

    onmouseleave="
        stopHoverSign()
    "

    onclick="
        chooseGender('female', 'أنثى')
    "
>

    أنثى

</div>


</div>


<input
    type="hidden"
    name="gender"
    id="genderInput"
    required
>


</div>

</div>

</div>

</div>


<button
    type="submit"
    class="primary-btn"
    id="registerSubmitBtn"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/register-button.mp4',
            'اضغط هنا لإنشاء حساب'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

    إنشاء الحساب

</button>


</form>


<p
    class="auth-footer"

    onmouseenter="
        playHoverSign(
            this,
            '../assets/videos/login-btn2.mp4',
            'لديك حساب؟ تسجيل دخول'
        )
    "

    onmouseleave="
        stopHoverSign()
    "
>

    لديك حساب؟

    <a href="login.php">
        تسجيل الدخول
    </a>

</p>


</div>

</div>


<!-- =========================================================
     فيديوهات الإشارة + منع الكاش
     ========================================================= -->

<?php

$sign_base = "../";

$sign_video =
    "../assets/videos/register-sign.mp4";

include "../components/sign-language.php";

?>


<script>

/* =========================================================
   Cache Busting
   مهم جداً:
   هذا الكود موجود بعد sign-language.php
   حتى تكون دوال الفيديو موجودة فعلياً.
   ========================================================= */

function freshVideoPath(videoPath) {

    if (!videoPath) {
        return videoPath;
    }

    const cleanPath =
        videoPath.split("?")[0];

    return cleanPath +
        "?v=" +
        Date.now();
}


/* =========================================================
   تغليف دوال فيديو الإشارة
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

                return originalSetSignVideo.call(
                    this,
                    freshVideoPath(videoPath),
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

                return originalOpenSignVideo.call(
                    this,
                    freshVideoPath(videoPath),
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

                return originalOpenDashboardSignVideo.call(
                    this,
                    freshVideoPath(videoPath),
                    element,
                    ...args
                );

            };

    }

})();


/* =========================================================
   تحديث كل فيديوهات source الموجودة بالصفحة
   ========================================================= */

(function () {

    document
        .querySelectorAll("video source")
        .forEach(function (source) {

            const src =
                source.getAttribute("src");

            if (!src) {
                return;
            }

            const cleanPath =
                src.split("?")[0];

            source.setAttribute(
                "src",
                cleanPath +
                "?v=" +
                Date.now()
            );

            const video =
                source.closest("video");

            if (video) {

                video.load();

            }

        });

})();

</script>


<script>

/* =========================================================
   النص والتظليل
   ========================================================= */

let currentSignVideoPath =
    freshVideoPath(
        "../assets/videos/register-sign.mp4"
    );

let currentSignTarget = null;

let currentSignText =
    "مرحبًا، هذه صفحة إنشاء حساب جديد";


function showSignText(text) {

    const box =
        document.getElementById(
            "signVideoBox"
        );

    if (!box) {
        return;
    }


    let textBox =
        document.getElementById(
            "signHelperText"
        );


    if (!textBox) {

        textBox =
            document.createElement(
                "div"
            );

        textBox.id =
            "signHelperText";

        textBox.className =
            "sign-helper-text";

        box.prepend(textBox);

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


function saveCurrentSign(
    videoPath,
    element,
    text
) {

    currentSignVideoPath =
        freshVideoPath(
            videoPath
        );

    currentSignTarget =
        element;

    currentSignText =
        text || "";

}


function playFocusSign(
    element,
    videoPath,
    text
) {

    const box =
        document.getElementById(
            "signVideoBox"
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
        videoPath,
        element,
        text
    );


    openSignVideo(
        freshVideoPath(videoPath),
        element,
        text
    );


    showSignText(text);

}


function playHoverSign(
    element,
    videoPath,
    text
) {

    const box =
        document.getElementById(
            "signVideoBox"
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
        videoPath,
        element,
        text
    );


    openSignVideo(
        freshVideoPath(videoPath),
        element,
        text
    );


    showSignText(text);

}


function stopHoverSign() {

    const video =
        document.getElementById(
            "signVideo"
        );

    const playBtn =
        document.getElementById(
            "signPlayBtn"
        );


    if (video) {

        video.pause();

        video.currentTime = 0;

    }


    if (playBtn) {

        playBtn.classList.remove(
            "hide"
        );

    }


    clearActiveSignTarget();

}

</script>


<script>

/* =========================================================
   التظليل
   ========================================================= */

function setActiveSignTarget(element) {

    clearActiveSignTarget();

    if (!element) {
        return;
    }


    const circle =
        element.querySelector
            ? element.querySelector(
                ".circle-icon"
            )
            : null;


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


            return;

        }

    }


    element.classList.add(
        "sign-active-target"
    );

}


function clearActiveSignTarget() {

    document
        .querySelectorAll(
            ".sign-active-target"
        )
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

</script>


<script>

/* =========================================================
   تشغيل فيديو الصفحة الرئيسي
   ========================================================= */

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
                "registerError"
            );

        const card =
            document.querySelector(
                ".register-card"
            );


        if (!box || !video) {
            return;
        }


        const firstHelpText =
            "مرحبًا، هذه صفحة إنشاء حساب جديد";


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
                "../assets/videos/register-sign.mp4",
                card,
                firstHelpText
            );


            setSignVideo(
                freshVideoPath(
                    currentSignVideoPath
                ),
                currentSignTarget
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
                showFirstHelpText
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


                    if (
                        currentSignVideoPath
                    ) {

                        setSignVideo(
                            freshVideoPath(
                                currentSignVideoPath
                            ),
                            currentSignTarget
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


        if (card) {

            card.addEventListener(
                "mouseleave",
                function () {

                    if (
                        !box.classList.contains(
                            "active"
                        )
                    ) {
                        return;
                    }


                    saveCurrentSign(
                        "../assets/videos/register-sign.mp4",
                        card,
                        "مرحبًا، هذه صفحة إنشاء حساب جديد"
                    );


                    setSignVideo(
                        freshVideoPath(
                            currentSignVideoPath
                        ),
                        currentSignTarget
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

        }

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
                    "registerError"
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
                errorBox.dataset.signVideo,
                errorBox,
                errorBox.dataset.signText
            );


            setSignVideo(
                freshVideoPath(
                    currentSignVideoPath
                ),
                currentSignTarget
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
   الضغط خارج البطاقة
   ========================================================= */

document.addEventListener(
    "click",
    function (e) {

        const card =
            document.querySelector(
                ".register-card"
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
            "../assets/videos/register-sign.mp4",
            card,
            "مرحبًا، هذه صفحة إنشاء حساب جديد"
        );


        setSignVideo(
            freshVideoPath(
                currentSignVideoPath
            ),
            currentSignTarget
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

</script>


<script>

/* =========================================================
   اختيار الجنس
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const box =
            document.getElementById(
                "genderBox"
            );

        const menu =
            document.getElementById(
                "genderMenu"
            );

        const select =
            document.getElementById(
                "genderSelect"
            );


        if (
            !box ||
            !menu ||
            !select
        ) {
            return;
        }


        box.addEventListener(
            "click",
            function (e) {

                e.stopPropagation();

                menu.classList.toggle(
                    "active"
                );

            }
        );


        document.addEventListener(
            "click",
            function (e) {

                if (
                    !select.contains(
                        e.target
                    )
                ) {

                    menu.classList.remove(
                        "active"
                    );

                }

            }
        );

    }
);


function chooseGender(
    value,
    text
) {

    document.getElementById(
        "genderText"
    ).textContent = text;


    document.getElementById(
        "genderInput"
    ).value = value;


    document.getElementById(
        "genderMenu"
    ).classList.remove(
        "active"
    );

}

</script>


<script>

/* =========================================================
   أول ضغطة تشير، ثاني ضغطة تنفذ — للموبايل فقط
   ========================================================= */

(function () {

    if (
        !("ontouchstart" in window)
    ) {
        return;
    }


    var pending = null;


    document.addEventListener(
        "touchend",
        function (e) {

            var box =
                document.getElementById(
                    "signVideoBox"
                );


            if (
                !box ||
                !box.classList.contains(
                    "active"
                )
            ) {
                return;
            }


            var SELECTOR =
                'a, button, input, select, textarea';


            var target =
                e.target.closest(
                    SELECTOR
                );


            if (!target) {

                pending = null;

                return;
            }


            if (
                target.id ===
                    "signToggleBtn" ||
                target.closest(
                    "#signVideoBox"
                )
            ) {
                return;
            }


            if (
                pending !== target
            ) {

                e.preventDefault();

                e.stopImmediatePropagation();

                pending = target;


                target.dispatchEvent(
                    new MouseEvent(
                        "mouseenter",
                        {
                            bubbles: true
                        }
                    )
                );

            } else {

                pending = null;

            }

        },
        {
            passive: false
        }
    );


    document.addEventListener(
        "touchstart",
        function (e) {

            var box =
                document.getElementById(
                    "signVideoBox"
                );


            if (
                !box ||
                !box.classList.contains(
                    "active"
                )
            ) {
                return;
            }


            if (
                e.target.closest(
                    "#signVideoBox, #signToggleBtn"
                )
            ) {
                return;
            }


            var target =
                e.target.closest(
                    'a, button, input, select, textarea, [onclick], [onmouseenter]'
                ) ||
                e.target;


            if (
                target !== pending
            ) {

                pending = null;

            }

        },
        {
            passive: true
        }
    );

})();

</script>


</body>
</html>