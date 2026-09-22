<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// منع تخزين الفيديوهات القديمة في كاش المتصفح أو الخادم
$video_cache_version = time();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/session_child.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['lang'])) {
    if (
        isset($_COOKIE['siteLanguage']) &&
        in_array($_COOKIE['siteLanguage'], ['en', 'ar'], true)
    ) {
        $_SESSION['lang'] = $_COOKIE['siteLanguage'];
    } else {
        $_SESSION['lang'] = 'ar';
    }
}

$lang = $_SESSION['lang'];
$user_id = (int) $_SESSION['user_id'];

$text = [
    'ar' => [
        'title'           => 'إعدادات الحساب',
        'username'        => 'اسم المستخدم',
        'password'        => 'كلمة المرور الجديدة',
        'password_note'   => 'اتركها فارغة إذا كنت لا تريد تغييرها',
        'phone'           => 'رقم الجوال',
        'gender'          => 'الجنس',
        'male'            => 'ذكر',
        'female'          => 'أنثى',
        'save'            => 'حفظ التعديلات',
        'success'         => 'تم تحديث البيانات بنجاح',
        'back'            => 'العودة لصفحة الطفل',
        'error_username'  => 'اسم المستخدم مطلوب',
        'error_gender'    => 'يرجى اختيار الجنس',
        'error_reserved'  => 'اسم المستخدم مستخدم مسبقًا',
        'error_phone'     => 'رقم الجوال يجب أن يتكون من 10 أرقام بالضبط',
    ],

    'en' => [
        'title'           => 'Account Settings',
        'username'        => 'Username',
        'password'        => 'New Password',
        'password_note'   => 'Leave it empty if you do not want to change it',
        'phone'           => 'Phone Number',
        'gender'          => 'Gender',
        'male'            => 'Male',
        'female'          => 'Female',
        'save'            => 'Save Changes',
        'success'         => 'Information updated successfully',
        'back'            => 'Back to Child Page',
        'error_username'  => 'Username is required',
        'error_gender'    => 'Please choose gender',
        'error_reserved'  => 'This username is already taken',
        'error_phone'     => 'Phone number must contain exactly 10 digits',
    ]
];

$t = $text[$lang];

$message = '';
$error = '';
$error_video = '';
$error_sign_text = '';

/*
|--------------------------------------------------------------------------
| جلب بيانات المستخدم
|--------------------------------------------------------------------------
| لا يوجد age هنا
| لا يوجد country_code هنا
*/

$stmt = $conn->prepare("
    SELECT username, gender, phone
    FROM children
    WHERE id = ?
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

} else {

    die("Database error.");
}

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| رقم الهاتف المخزن
|--------------------------------------------------------------------------
*/

$saved_phone = $user['phone'] ?? '';

/*
|--------------------------------------------------------------------------
| معالجة النموذج
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $phone = trim($_POST["phone"] ?? "");
    $gender = trim($_POST["gender"] ?? "");

    /*
    | إزالة أي رموز أو مسافات غير رقمية من الهاتف
    */
    $phone = preg_replace('/[^0-9]/', '', $phone);

    /*
    |--------------------------------------------------------------------------
    | التحقق من البيانات
    |--------------------------------------------------------------------------
    */

    if ($username === '') {

        $error = $t['error_username'];

        $error_video =
            "../assets/videos/username.mp4?v=" .
            $video_cache_version;

        $error_sign_text = $t['error_username'];

    } elseif (
        $phone !== '' &&
        !preg_match('/^[0-9]{10}$/', $phone)
    ) {

        $error = $t['error_phone'];

        $error_video =
            "../assets/videos/number.mp4?v=" .
            $video_cache_version;

        $error_sign_text = $t['error_phone'];

    } elseif (
        !in_array($gender, ['male', 'female'], true)
    ) {

        $error = $t['error_gender'];

        $error_video =
            "../assets/videos/gender.mp4?v=" .
            $video_cache_version;

        $error_sign_text = $t['error_gender'];

    } else {

        /*
        |--------------------------------------------------------------------------
        | التأكد من أن اسم المستخدم غير مستخدم
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM children
            WHERE username = ?
            AND id != ?
        ");

        if ($check) {

            $check->bind_param(
                "si",
                $username,
                $user_id
            );

            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {

                $error = $t['error_reserved'];

                $error_video =
                    "../assets/videos/pre-booked-user.mp4?v=" .
                    $video_cache_version;

                $error_sign_text =
                    $t['error_reserved'];
            }

            $check->close();

        } else {

            $error = "Database error.";

            $error_video =
                "../assets/videos/empty-fields.mp4?v=" .
                $video_cache_version;

            $error_sign_text = "حدث خطأ";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حفظ التعديلات
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        /*
        |--------------------------------------------------------------------------
        | مع تغيير كلمة المرور
        |--------------------------------------------------------------------------
        */

        if ($password !== '') {

            $password_hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $stmt = $conn->prepare("
                UPDATE children
                SET
                    username = ?,
                    password_hash = ?,
                    gender = ?,
                    phone = ?
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "ssssi",
                    $username,
                    $password_hash,
                    $gender,
                    $phone,
                    $user_id
                );

                if (!$stmt->execute()) {
                    $error = "Database error.";
                }

                $stmt->close();

            } else {

                $error = "Database error.";
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | بدون تغيير كلمة المرور
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                UPDATE children
                SET
                    username = ?,
                    gender = ?,
                    phone = ?
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "sssi",
                    $username,
                    $gender,
                    $phone,
                    $user_id
                );

                if (!$stmt->execute()) {
                    $error = "Database error.";
                }

                $stmt->close();

            } else {

                $error = "Database error.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | تحديث Session
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            $_SESSION['username'] = $username;
            $_SESSION['gender'] = $gender;

            $message = $t['success'];

            /*
            |--------------------------------------------------------------------------
            | إعادة جلب البيانات بعد الحفظ
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT username, gender, phone
                FROM children
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $user_id
                );

                $stmt->execute();

                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                $stmt->close();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| القيمة الظاهرة في خانة الهاتف
|--------------------------------------------------------------------------
*/

$saved_phone = $_POST['phone'] ?? ($user['phone'] ?? '');

$saved_phone = htmlspecialchars(
    $saved_phone,
    ENT_QUOTES,
    'UTF-8'
);

?>

<!DOCTYPE html>

<html
    lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>"
    dir="<?php echo ($lang === 'en') ? 'ltr' : 'rtl'; ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?>
</title>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap"
    rel="stylesheet"
>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: 'Cairo', sans-serif;

    background:
        linear-gradient(
            180deg,
            #dff4f7 0%,
            #f6fbff 45%,
            #fffaf2 100%
        );

    padding: 40px 16px;

    color: #183153;
}

.settings-box {

    max-width: 760px;

    margin: auto;

    background: rgba(255,255,255,0.92);

    border-radius: 28px;

    box-shadow:
        0 18px 40px rgba(25,78,113,.12);

    padding: 32px;

    transition: .35s ease;
}

h1 {

    margin: 0 0 22px;

    font-size: 34px;

    color: #17486d;
}

.message {

    padding: 12px 16px;

    border-radius: 14px;

    margin-bottom: 18px;

    font-weight: 700;
}

.success {

    background: #e9f9ec;

    color: #1d7a33;
}

.error {

    background: #ffeaea;

    color: #b42318;
}

.input-box {

    margin-bottom: 18px;

    border-radius: 18px;
}

label {

    display: block;

    margin-bottom: 8px;

    font-weight: 700;

    color: #23496c;
}

input,
select {

    width: 100%;

    padding: 13px 15px;

    border: 1px solid #d7e5f1;

    border-radius: 16px;

    font-family: inherit;

    font-size: 15px;

    background: #fff;

    outline: none;

    transition: 0.25s ease;
}

input:focus,
select:focus {

    border-color: #58a6ff;

    box-shadow:
        0 0 0 4px rgba(88,166,255,0.12);
}

small {

    display: block;

    margin-top: 6px;

    color: #6c8197;
}

.btn-row {

    display: flex;

    gap: 12px;

    flex-wrap: wrap;

    margin-top: 10px;
}

.save-btn,
.back-link {

    text-decoration: none;

    border: none;

    border-radius: 16px;

    padding: 13px 22px;

    font-family: inherit;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;

    transition: 0.25s ease;
}

.save-btn {

    background:
        linear-gradient(
            135deg,
            #4aa6ff,
            #317fe7
        );

    color: #fff;

    box-shadow:
        0 10px 22px rgba(49,127,231,.24);
}

.save-btn:hover {

    transform: translateY(-2px);
}

.back-link {

    background: #edf6ff;

    color: #2d77d1;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}

.back-link:hover {

    background: #e2f0ff;
}

.sign-active-target {

    outline:
        0.1px dashed rgba(255, 120, 120, 0.4)
        !important;

    box-shadow:
        0 0 10px rgba(255, 120, 120, 0.2)
        !important;
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

@media (max-width: 640px) {

    .settings-box {

        padding: 22px;
    }

    h1 {

        font-size: 28px;
    }
}

/* Gender */

.gender-select {

    position: relative;
}

.gender-box {

    width: 100%;

    padding: 13px;

    border-radius: 16px;

    border: 1px solid #d7e5f1;

    background: #fff;

    cursor: pointer;

    text-align: right;

    font-family: inherit;

    font-size: 15px;
}

.gender-menu {

    position: absolute;

    width: 100%;

    background: white;

    border-radius: 16px;

    box-shadow:
        0 10px 25px rgba(0,0,0,.1);

    display: none;

    z-index: 10;
}

.gender-menu.active {

    display: block;
}

.gender-option {

    padding: 12px;

    cursor: pointer;
}

.gender-option:hover {

    background: #f0f8ff;
}

/* Phone field */

.phone-field-wrap {

    display: flex;

    width: 100%;

    align-items: stretch;

    border: 1px solid #d7e5f1;

    border-radius: 16px;

    overflow: hidden;

    direction: ltr;

    background: #fff;
}

.phone-field-wrap:focus-within {

    border-color: #58a6ff;

    box-shadow:
        0 0 0 4px rgba(88,166,255,0.12);
}

.phone-number-input {

    width: 100%;

    flex: 1;

    border: none !important;

    outline: none;

    padding: 13px 15px;

    font-size: 15px;

    direction: ltr;

    text-align: left;

    background: transparent;

    box-shadow: none !important;
}

</style>

</head>

<body>

<div
    class="settings-box"
    data-sign-video="../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>"
>

    <h1
        onmouseenter="
            playHoverSign(
                this,
                '../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>',
                'هذه صفحة إعدادات الحساب'
            )
        "
        onmouseleave="stopHoverSign()"
    >

        <?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?>

    </h1>

    <?php if ($message !== ''): ?>

        <div
            class="message success"
            onmouseenter="
                playHoverSign(
                    this,
                    '../assets/videos/success-sign.mp4?v=<?php echo $video_cache_version; ?>',
                    'تم تحديث البيانات بنجاح'
                )
            "
            onmouseleave="stopHoverSign()"
        >

            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>

        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div
            class="message error"
            id="settingsError"
            data-sign-video="<?php echo htmlspecialchars($error_video, ENT_QUOTES, 'UTF-8'); ?>"
            data-sign-text="<?php echo htmlspecialchars($error_sign_text, ENT_QUOTES, 'UTF-8'); ?>"
        >

            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>

        </div>

    <?php endif; ?>


    <form method="POST" action="">

        <!-- Username -->

        <div class="input-box">

            <label for="username">

                <?php echo htmlspecialchars($t['username'], ENT_QUOTES, 'UTF-8'); ?>

            </label>

            <input
                type="text"
                id="username"
                name="username"
                value="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                required
                onmouseenter="
                    playHoverSign(
                        this,
                        '../assets/videos/username.mp4?v=<?php echo $video_cache_version; ?>',
                        'اكتب اسم المستخدم'
                    )
                "
                onmouseleave="stopHoverSign()"
            >

        </div>


        <!-- Password -->

        <div class="input-box">

            <label for="password">

                <?php echo htmlspecialchars($t['password'], ENT_QUOTES, 'UTF-8'); ?>

            </label>

            <input
                type="password"
                id="password"
                name="password"
                onmouseenter="
                    playHoverSign(
                        this,
                        '../assets/videos/password-settings-.mp4?v=<?php echo $video_cache_version; ?>',
                        'اكتب كلمة المرور الجديدة و اتركها فارغ اذا كنت لا تريد تغييرها'
                    )
                "
                onmouseleave="stopHoverSign()"
            >

            <small>

                <?php echo htmlspecialchars($t['password_note'], ENT_QUOTES, 'UTF-8'); ?>

            </small>

        </div>


        <!-- Phone -->

        <div class="input-box">

            <label for="phone">

                <?php echo htmlspecialchars($t['phone'], ENT_QUOTES, 'UTF-8'); ?>

            </label>

            <div
                class="phone-field-wrap"
                onmouseenter="
                    playHoverSign(
                        this,
                        '../assets/videos/number.mp4?v=<?php echo $video_cache_version; ?>',
                        'اكتب رقم الجوال المكون من 10 أرقام'
                    )
                "
                onmouseleave="stopHoverSign()"
            >

                <input
                    id="phone"
                    type="tel"
                    name="phone"
                    class="phone-number-input"
                    placeholder="0501234567"
                    value="<?php echo $saved_phone; ?>"
                    maxlength="10"
                    minlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    autocomplete="tel"
                    oninput="
                        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                    "
                >

            </div>

        </div>


        <!-- Gender -->

        <div class="input-box">

            <label>

                <?php echo htmlspecialchars($t['gender'], ENT_QUOTES, 'UTF-8'); ?>

            </label>

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
                            '../assets/videos/gender.mp4?v=<?php echo $video_cache_version; ?>',
                            'اختر الجنس'
                        )
                    "
                    onmouseleave="stopHoverSign()"
                >

                    <span id="genderText">

                        <?php

                        if (($user['gender'] ?? '') === 'male') {

                            echo htmlspecialchars(
                                $t['male'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        } elseif (($user['gender'] ?? '') === 'female') {

                            echo htmlspecialchars(
                                $t['female'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        } else {

                            echo ($lang === 'en')
                                ? 'Choose gender'
                                : 'اختر الجنس';
                        }

                        ?>

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
                                '../assets/videos/gender.mp4?v=<?php echo $video_cache_version; ?>',
                                'اختر الجنس'
                            )
                        "
                        onmouseleave="stopHoverSign()"
                        onclick="
                            chooseGender(
                                '',
                                '<?php echo $lang === 'en' ? 'Choose gender' : 'اختر الجنس'; ?>'
                            )
                        "
                    >

                        <?php echo $lang === 'en' ? 'Choose gender' : 'اختر الجنس'; ?>

                    </div>


                    <div
                        class="gender-option boy"
                        onmouseenter="
                            playHoverSign(
                                this,
                                '../assets/videos/boy.mp4?v=<?php echo $video_cache_version; ?>',
                                'ذكر'
                            )
                        "
                        onmouseleave="stopHoverSign()"
                        onclick="
                            chooseGender(
                                'male',
                                '<?php echo $lang === 'en' ? 'Male' : 'ذكر'; ?>'
                            )
                        "
                    >

                        <?php echo htmlspecialchars($t['male'], ENT_QUOTES, 'UTF-8'); ?>

                    </div>


                    <div
                        class="gender-option girl"
                        onmouseenter="
                            playHoverSign(
                                this,
                                '../assets/videos/girl.mp4?v=<?php echo $video_cache_version; ?>',
                                'أنثى'
                            )
                        "
                        onmouseleave="stopHoverSign()"
                        onclick="
                            chooseGender(
                                'female',
                                '<?php echo $lang === 'en' ? 'Female' : 'أنثى'; ?>'
                            )
                        "
                    >

                        <?php echo htmlspecialchars($t['female'], ENT_QUOTES, 'UTF-8'); ?>

                    </div>

                </div>


                <input
                    type="hidden"
                    name="gender"
                    id="genderInput"
                    value="<?php echo htmlspecialchars($user['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >

            </div>

        </div>


        <!-- Buttons -->

        <div class="btn-row">

            <button
                type="submit"
                class="save-btn"
                onmouseenter="
                    playHoverSign(
                        this,
                        '../assets/videos/save.mp4?v=<?php echo $video_cache_version; ?>',
                        'حفظ التعديلات'
                    )
                "
                onmouseleave="stopHoverSign()"
            >

                <?php echo htmlspecialchars($t['save'], ENT_QUOTES, 'UTF-8'); ?>

            </button>


            <a
                href="children.php"
                class="back-link"
                onmouseenter="
                    playHoverSign(
                        this,
                        '../assets/videos/back-sign.mp4?v=<?php echo $video_cache_version; ?>',
                        'العودة لصفحة الطفل'
                    )
                "
                onmouseleave="stopHoverSign()"
            >

                <?php echo htmlspecialchars($t['back'], ENT_QUOTES, 'UTF-8'); ?>

            </a>

        </div>

    </form>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Sign language helper
|--------------------------------------------------------------------------
*/

let currentSignVideoPath =
    "../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>";

let currentSignTarget = null;

let currentSignText =
    "هذه صفحة إعدادات الحساب";


document.addEventListener(
    "DOMContentLoaded",
    function () {

        const errorBox =
            document.getElementById("settingsError");

        const inputs =
            document.querySelectorAll(
                "input, select"
            );

        inputs.forEach(
            function (input) {

                input.addEventListener(
                    "input",
                    function () {

                        if (errorBox) {
                            errorBox.style.display = "none";
                        }

                    }
                );

                input.addEventListener(
                    "change",
                    function () {

                        if (errorBox) {
                            errorBox.style.display = "none";
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


function showSignText(text) {

    const box =
        document.getElementById("signVideoBox");

    if (!box) return;

    let textBox =
        document.getElementById("signHelperText");

    if (!textBox) {

        textBox =
            document.createElement("div");

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
        videoPath;

    currentSignTarget =
        element;

    currentSignText =
        text;
}


function playHoverSign(
    element,
    videoPath,
    text
) {

    const box =
        document.getElementById("signVideoBox");

    const video =
        document.getElementById("signVideo");

    const source =
        document.getElementById("signVideoSource");

    const playBtn =
        document.getElementById("signPlayBtn");

    if (!box || !video || !source) return;

    if (!box.classList.contains("active")) return;

    saveCurrentSign(
        videoPath,
        element,
        text
    );

    if (
        source.getAttribute("src") !==
        videoPath
    ) {

        video.pause();

        source.setAttribute(
            "src",
            videoPath
        );

        video.load();
    }

    clearActiveSignTarget();

    element.classList.add(
        "sign-active-target"
    );

    showSignText(text);

    video.currentTime = 0;

    video.muted = true;

    video.play()
        .then(
            function () {

                if (playBtn) {
                    playBtn.classList.add("hide");
                }

            }
        )
        .catch(
            function () {

                if (playBtn) {
                    playBtn.classList.remove("hide");
                }

            }
        );
}


function stopHoverSign() {

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

</script>


<?php

$sign_base = "../";

$sign_video =
    "../assets/videos/settings-sign.mp4?v=" .
    $video_cache_version;

include "../components/sign-language.php";

?>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const box =
            document.getElementById("signVideoBox");

        const video =
            document.getElementById("signVideo");

        const playBtn =
            document.getElementById("signPlayBtn");

        const errorBox =
            document.getElementById("settingsError");

        const card =
            document.querySelector(".settings-box");

        if (!box || !video) return;

        const firstHelpText =
            "هذه صفحة إعدادات الحساب";


        function showFirstHelpText() {

            if (!box.classList.contains("active")) return;

            if (
                errorBox &&
                errorBox.style.display !== "none"
            ) return;

            saveCurrentSign(
                "../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>",
                card,
                firstHelpText
            );

            setSignVideo(
                currentSignVideoPath,
                currentSignTarget
            );

            showSignText(
                currentSignText
            );

            if (playBtn) {
                playBtn.classList.remove("hide");
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

                    if (currentSignVideoPath) {

                        setSignVideo(
                            currentSignVideoPath,
                            currentSignTarget
                        );

                        showSignText(
                            currentSignText
                        );
                    }

                    video.play()
                        .then(
                            function () {

                                if (
                                    typeof sectionVideoStarted !==
                                    "undefined"
                                ) {
                                    sectionVideoStarted = true;
                                }

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
                    playBtn.classList.add("hide");
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
                        !box.classList.contains("active")
                    ) return;

                    saveCurrentSign(
                        "../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>",
                        card,
                        "هذه صفحة إعدادات الحساب"
                    );

                    setSignVideo(
                        currentSignVideoPath,
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
                    "settingsError"
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


            box.classList.add(
                "active"
            );

            document.body.classList.add(
                "sign-open"
            );


            saveCurrentSign(
                errorBox.dataset.signVideo,
                errorBox,
                errorBox.dataset.signText
            );


            setSignVideo(
                currentSignVideoPath,
                currentSignTarget
            );


            showSignText(
                currentSignText
            );


            video.play()
                .then(
                    function () {

                        if (
                            typeof sectionVideoStarted !==
                            "undefined"
                        ) {
                            sectionVideoStarted = true;
                        }

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

document.addEventListener(
    "click",
    function (e) {

        const card =
            document.querySelector(
                ".settings-box"
            );

        const box =
            document.getElementById(
                "signVideoBox"
            );

        const playBtn =
            document.getElementById(
                "signPlayBtn"
            );

        if (!card || !box) return;

        if (card.contains(e.target)) return;

        if (!box.classList.contains("active")) return;


        saveCurrentSign(
            "../assets/videos/settings-sign.mp4?v=<?php echo $video_cache_version; ?>",
            card,
            "هذه صفحة إعدادات الحساب"
        );


        setSignVideo(
            currentSignVideoPath,
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

        if (!box || !menu || !select) return;


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
                    !select.contains(e.target)
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


</body>

</html>