<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username     = trim($_POST["username"] ?? "");
    $password     = trim($_POST["password"] ?? "");
    $country_code = trim($_POST["country_code"] ?? "+970");
    $phone_number = trim($_POST["phone_number"] ?? "");
    $phone        = $country_code . $phone_number;
    $age          = isset($_POST["age"]) ? intval($_POST["age"]) : 0;
    $gender       = trim($_POST["gender"] ?? "");

    if ($username === "") {
        $error = "اسم المستخدم مطلوب.";
    } elseif (!preg_match("/^[\p{Arabic}a-zA-Z]+$/u", $username)) {
        $error = "اسم المستخدم يجب أن يحتوي على حروف فقط.";
    } elseif ($password === "") {
        $error = "كلمة المرور مطلوبة.";
    } elseif ($phone_number === "") {
        $error = "رقم الجوال مطلوب.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone_number)) {
        $error = "رقم الجوال غير صحيح.";
    } elseif ($age < 4 || $age > 8) {
        $error = "العمر يجب أن يكون بين 4 و 8.";
    } elseif (!in_array($gender, ["male", "female"])) {
        $error = "يرجى اختيار الجنس.";
    } else {

        $check = mysqli_prepare($conn, "SELECT id FROM children WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "اسم المستخدم موجود مسبقًا.";
        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO children (username, password_hash, age, gender, phone) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiss", $username, $password_hash, $age, $gender, $phone);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: children.php");
                exit;
            } else {
                $error = "حدث خطأ أثناء إضافة الطفل.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>إضافة طفل</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
/* ── Phone Picker ── */
.phone-picker-wrap { position: relative; }

.phone-field-wrap {
  display: flex;
  align-items: stretch;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  direction: ltr;
  background: #fff;
  width: 100%;
  box-sizing: border-box;
  transition: border-color .2s, box-shadow .2s;
}
.phone-field-wrap:focus-within {
  border-color: #4a90e2;
  box-shadow: 0 0 0 3px rgba(74,144,226,.12);
}

.country-code-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 12px;
  background: transparent !important;
  border: none;
  border-left: 2px solid #e2e8f0;
  cursor: pointer;
  white-space: nowrap;
  min-width: 110px;
  justify-content: center;
  transition: background .2s;
  font-family: inherit;
  color: #2d3748;
}
.country-code-btn:hover {
  background: #f8fafc !important;
}

.cc-flag-lg  { width: 28px; height: 20px; object-fit: cover; border-radius: 3px; display: block; }
.cc-info     { display: flex; flex-direction: column; align-items: flex-start; }
.cc-dial     { font-size: 13px; font-weight: 700; color: #2d3748; direction: ltr; }
.cc-arrow    { font-size: 9px; color: #718096; margin-right: 2px; }

.country-dropdown {
  display: none;
  position: absolute;
  z-index: 999;
  top: calc(100% + 6px);
  right: 0;
  width: 300px;
  max-height: 280px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,.15);
  direction: rtl;
}
.country-dropdown.open { display: block; animation: ccFadeIn .15s ease; }
@keyframes ccFadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }

.cc-search-wrap {
  padding: 10px 12px 8px;
  border-bottom: 1px solid #edf2f7;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 1;
}
.cc-search-wrap input {
  width: 100%;
  box-sizing: border-box;
  padding: 7px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  direction: rtl;
  background: #f8fafc;
  transition: border-color .2s;
}
.cc-search-wrap input:focus { border-color: #4a90e2; background: #fff; }

.cc-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  cursor: pointer;
  font-size: 13px;
  transition: background .15s;
  direction: rtl;
}
.cc-option:hover  { background: #f0f6ff; }
.cc-option.active { background: #ebf4ff; }

.opt-flag { width: 26px; height: 18px; object-fit: cover; border-radius: 2px; flex-shrink: 0; }
.opt-name { flex: 1; text-align: right; color: #2d3748; }
.opt-code { font-size: 12px; font-weight: 700; color: #4a90e2; direction: ltr; background: #ebf4ff; padding: 2px 6px; border-radius: 4px; }

.phone-number-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 10px 14px;
  font-size: 15px;
  direction: ltr;
  background: transparent;
  color: #2d3748;
}
.phone-number-input::placeholder { color: #a0aec0; }
</style>
</head>

<body>

<div class="admin-layout">

<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">

<h1 class="page-title">إضافة طفل جديد</h1>

<?php if ($error !== ""): ?>
    <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" class="admin-form">

    <label>اسم المستخدم</label>
    <input type="text" name="username" placeholder="أدخل اسم المستخدم" required>

    <label>كلمة المرور</label>
    <input type="password" name="password" placeholder="أدخل كلمة المرور" required>

    <label>رقم الجوال</label>
    <div class="phone-picker-wrap">
      <div class="phone-field-wrap">
        <input type="hidden" name="country_code" id="countryCodeInput" value="<?php echo htmlspecialchars($_POST['country_code'] ?? '+970'); ?>">
        <button type="button" class="country-code-btn" id="ccBtn" onclick="toggleCCDropdown(event)">
          <img class="cc-flag-lg" id="ccFlag" src="https://flagcdn.com/w40/ps.png" alt="فلسطين">
          <div class="cc-info">
            <span class="cc-dial" id="ccCode"><?php echo htmlspecialchars($_POST['country_code'] ?? '+970'); ?></span>
          </div>
          <span class="cc-arrow">▼</span>
        </button>
        <input type="tel" name="phone_number" id="phone_number" class="phone-number-input"
               placeholder="أدخل رقم الجوال"
               value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
               required>
      </div>
      <div class="country-dropdown" id="ccDropdown">
        <div class="cc-search-wrap">
          <input type="text" id="ccSearch" placeholder="🔍 ابحث عن دولة..." oninput="filterCC(this.value)">
        </div>
        <div id="ccList"></div>
      </div>
    </div>

    <label>العمر</label>
    <input type="number" name="age" min="4" max="8" placeholder="العمر من 4 إلى 8" required>

    <label>الجنس</label>
    <select name="gender" required>
        <option value="">اختر الجنس</option>
        <option value="male">ذكر</option>
        <option value="female">أنثى</option>
    </select>

    <button type="submit">إضافة الطفل</button>

</form>

<a href="children.php" class="back-link">العودة إلى إدارة الأطفال</a>

</main>
</div>

<script>
const COUNTRIES = [
  { iso:"ps", name:"فلسطين",           code:"+970" },
  { iso:"sa", name:"السعودية",         code:"+966" },
  { iso:"ae", name:"الإمارات",         code:"+971" },
  { iso:"kw", name:"الكويت",           code:"+965" },
  { iso:"qa", name:"قطر",              code:"+974" },
  { iso:"bh", name:"البحرين",          code:"+973" },
  { iso:"om", name:"عُمان",            code:"+968" },
  { iso:"ye", name:"اليمن",            code:"+967" },
  { iso:"jo", name:"الأردن",           code:"+962" },
  { iso:"lb", name:"لبنان",            code:"+961" },
  { iso:"sy", name:"سوريا",            code:"+963" },
  { iso:"iq", name:"العراق",           code:"+964" },
  { iso:"eg", name:"مصر",              code:"+20"  },
  { iso:"ly", name:"ليبيا",            code:"+218" },
  { iso:"tn", name:"تونس",             code:"+216" },
  { iso:"dz", name:"الجزائر",          code:"+213" },
  { iso:"ma", name:"المغرب",           code:"+212" },
  { iso:"sd", name:"السودان",          code:"+249" },
  { iso:"tr", name:"تركيا",            code:"+90"  },
  { iso:"gb", name:"المملكة المتحدة",  code:"+44"  },
  { iso:"us", name:"الولايات المتحدة", code:"+1"   },
  { iso:"fr", name:"فرنسا",            code:"+33"  },
  { iso:"de", name:"ألمانيا",          code:"+49"  },
];

function flagUrl(iso) {
  return `https://flagcdn.com/w40/${iso}.png`;
}

function renderCCList(list) {
  const c = document.getElementById("ccList");
  c.innerHTML = "";
  list.forEach(item => {
    const d = document.createElement("div");
    d.className = "cc-option";
    d.innerHTML = `<img class="opt-flag" src="${flagUrl(item.iso)}" alt="${item.name}"><span class="opt-name">${item.name}</span><span class="opt-code">${item.code}</span>`;
    d.onclick = () => selectCountry(item);
    c.appendChild(d);
  });
}

function selectCountry(c) {
  document.getElementById("ccFlag").src = flagUrl(c.iso);
  document.getElementById("ccFlag").alt = c.name;
  document.getElementById("ccCode").textContent = c.code;
  document.getElementById("countryCodeInput").value = c.code;
  closeCCDropdown();
}

function toggleCCDropdown(e) {
  e.stopPropagation();
  const dd = document.getElementById("ccDropdown");
  if (dd.classList.contains("open")) { closeCCDropdown(); return; }
  renderCCList(COUNTRIES);
  dd.classList.add("open");
  document.getElementById("ccSearch").value = "";
  setTimeout(() => document.getElementById("ccSearch").focus(), 50);
}

function closeCCDropdown() {
  document.getElementById("ccDropdown").classList.remove("open");
}

function filterCC(q) {
  renderCCList(COUNTRIES.filter(c => c.name.includes(q) || c.code.includes(q)));
}

document.addEventListener("click", function(e) {
  const wrap = document.querySelector(".phone-picker-wrap");
  if (wrap && !wrap.contains(e.target)) closeCCDropdown();
});

document.addEventListener("DOMContentLoaded", function() {
  const saved = document.getElementById("countryCodeInput").value || "+970";
  const found = COUNTRIES.find(c => c.code === saved) || COUNTRIES[0];
  document.getElementById("ccFlag").src = flagUrl(found.iso);
  document.getElementById("ccFlag").alt = found.name;
  document.getElementById("ccCode").textContent = found.code;
});
</script>
</body>
</html>