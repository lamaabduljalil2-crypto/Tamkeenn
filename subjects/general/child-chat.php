<?php
require_once __DIR__ . '/../../config/session_child.php';

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "child") {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

$child_id   = intval($_SESSION["user_id"]);
$child_name = $_SESSION["child_name"] ?? $_SESSION["username"] ?? "الطفل";
$lang       = $_SESSION['lang'] ?? 'ar';

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS chat_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    child_id   INT          NOT NULL,
    child_name VARCHAR(255) NOT NULL,
    sender     ENUM('child','admin') NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT      DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/* ── AJAX: جلب رسائل جديدة ── */
if (isset($_GET['poll']) && isset($_GET['last_id'])) {
    header('Content-Type: application/json');
    $last_id = intval($_GET['last_id']);
    $u = mysqli_prepare($conn, "UPDATE chat_messages SET is_read=1 WHERE child_id=? AND sender='admin' AND is_read=0");
    mysqli_stmt_bind_param($u, 'i', $child_id);
    mysqli_stmt_execute($u);
    $q = mysqli_prepare($conn, "SELECT * FROM chat_messages WHERE child_id=? AND id>? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($q, 'ii', $child_id, $last_id);
    mysqli_stmt_execute($q);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $text = trim($_POST['message_text'] ?? '');
    if ($text !== '') {
        $ins = mysqli_prepare($conn,
            "INSERT INTO chat_messages (child_id, child_name, sender, message) VALUES (?,?,'child',?)");
        mysqli_stmt_bind_param($ins, 'iss', $child_id, $child_name, $text);
        mysqli_stmt_execute($ins);
        header("Location: child-chat.php");
        exit;
    }
}

$mark = mysqli_prepare($conn,
    "UPDATE chat_messages SET is_read=1 WHERE child_id=? AND sender='admin' AND is_read=0");
mysqli_stmt_bind_param($mark, 'i', $child_id);
mysqli_stmt_execute($mark);

$q = mysqli_prepare($conn,
    "SELECT * FROM chat_messages WHERE child_id=? ORDER BY created_at ASC");
mysqli_stmt_bind_param($q, 'i', $child_id);
mysqli_stmt_execute($q);
$messages = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>محادثة مع المشرف</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/children-page.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

.floating-bg {
    z-index: 0 !important;
    pointer-events: none;
}
.floating-bg * {
    filter: none !important;
    backdrop-filter: none !important;
}

.chat-box,
.chat-box *,
.chat-messages,
.bubble,
.chat-input-bar,
.chat-header,
.page-wrap,
.page-wrap * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    filter: none !important;
}

.page-wrap {
    position: relative;
    z-index: 2;
    max-width: 780px;
    margin: 0 auto;
    padding: 200px 16px 40px;
}

/* ═══ Chat container ═══ */
.chat-box {
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(33,66,95,.18);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 180px);
    min-height: 420px;
    position: relative;
    z-index: 2;
    isolation: isolate;
    background: #fff;
}

/* ═══ Header ═══ */
.chat-header {
    background: linear-gradient(135deg, #5498d4, #21425f);
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
    direction: rtl;
}
.chat-header-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.chat-header-avatar {
    width: 46px; height: 46px; border-radius: 50%;
    background: rgba(255,255,255,.2);
    border: 2px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
    overflow: hidden;
}
.chat-header-avatar img {
    width: 100%; height: 100%; object-fit: cover; border-radius: 50%;
}
.online-dot {
    position: absolute;
    bottom: 2px; left: 2px;
    width: 12px; height: 12px;
    background: #4ade80;
    border-radius: 50%;
    border: 2px solid #21425f;
}
.chat-header-text { flex: 1; }
.chat-header-text h2 {
    font-size: 17px; font-weight: 900;
    font-family: 'Cairo', sans-serif;
}
.chat-header-status {
    display: flex; align-items: center; gap: 5px;
    margin-top: 3px;
}
.chat-header-status .status-dot {
    width: 7px; height: 7px;
    background: #4ade80;
    border-radius: 50%;
    flex-shrink: 0;
}
.chat-header-status span {
    font-size: 12px; opacity: .9;
    font-family: 'Cairo', sans-serif;
    color: #a8dfa8;
    font-weight: 700;
}

/* ═══ Messages ═══ */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    direction: ltr;
    background: #dce8f5 !important;
    opacity: 1 !important;
    overscroll-behavior: contain;
}

.date-sep {
    text-align: center;
    margin: 10px 0 6px;
    direction: rtl;
}
.date-sep span {
    background: rgba(41,182,246,.15);
    color: #21425f;
    font-size: 11px; font-weight: 700;
    padding: 3px 14px; border-radius: 20px;
    font-family: 'Cairo', sans-serif;
}

.msg-row { display: flex; }
.msg-row.sent     { justify-content: flex-end; }
.msg-row.received { justify-content: flex-start; }

.bubble {
    position: relative;
    max-width: 70%;
    min-width: 90px;
    padding: 9px 14px 24px;
    border-radius: 18px;
    font-size: 14px;
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    line-height: 1.6;
    word-break: break-word;
    direction: rtl;
    box-shadow: 0 2px 8px rgba(33,66,95,.12);
}

.msg-row.sent .bubble {
    background: #5498d4 !important;
    color: #fff !important;
    opacity: 1 !important;
    border-top-right-radius: 4px;
}
.msg-row.sent .bubble::after {
    content: '';
    position: absolute;
    top: 0; right: -8px;
    width: 0; height: 0;
    border-top: 9px solid #5498d4;
    border-right: 9px solid transparent;
}

.msg-row.received .bubble {
    background: #ffffff !important;
    color: #21425f !important;
    opacity: 1 !important;
    border: 1px solid #c8dff0;
    border-top-left-radius: 4px;
}
.msg-row.received .bubble::after {
    content: '';
    position: absolute;
    top: 0; left: -8px;
    width: 0; height: 0;
    border-top: 9px solid #ffffff;
    border-left: 9px solid transparent;
}

.msg-row.chain.sent     .bubble { border-top-right-radius: 18px; }
.msg-row.chain.sent     .bubble::after { display: none; }
.msg-row.chain.received .bubble { border-top-left-radius: 18px; }
.msg-row.chain.received .bubble::after { display: none; }

.bubble-time {
    position: absolute;
    bottom: 6px;
    font-size: 10px;
    white-space: nowrap;
    font-family: 'Cairo', sans-serif;
    direction: ltr;
}
.msg-row.sent     .bubble-time { left: 10px;  color: rgba(255,255,255,.75); }
.msg-row.received .bubble-time { right: 10px; color: #8ab0c8; }

.tick { font-size: 11px; opacity: .85; }

.chat-empty {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 14px; padding: 40px;
    direction: rtl;
}
.chat-empty .icon { font-size: 64px; }
.chat-empty p {
    font-size: 16px; font-weight: 700; color: #6a9db8;
    font-family: 'Cairo', sans-serif; text-align: center; line-height: 1.8;
}

/* ═══ Input bar ═══ */
.chat-input-bar {
    background: #e8f4fd;
    padding: 10px 14px;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-shrink: 0;
    border-top: 1px solid #b3d9f5;
    direction: rtl;
}
.chat-textarea {
    flex: 1;
    padding: 10px 16px;
    border: 1.5px solid #a8cce8;
    border-radius: 24px;
    font-size: 14px;
    font-family: 'Cairo', sans-serif;
    color: #21425f;
    resize: none;
    min-height: 44px;
    max-height: 120px;
    overflow-y: auto;
    background: #fff;
    outline: none;
    transition: border-color .2s;
    direction: rtl;
}
.chat-textarea:focus { border-color: #29b6f6; }
.chat-send-btn {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #29b6f6, #0277bd);
    color: #fff;
    font-size: 18px;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 3px 10px rgba(2,119,189,.4);
    transition: .2s; flex-shrink: 0;
}
.chat-send-btn:hover  { transform: scale(1.1); }
.chat-send-btn:active { transform: scale(1); }

/* ─── nav icons ─── */
.circle-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    position: relative;
}
.circle-icon video,
.circle-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.nav-text {
    position: absolute;
    top: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%) translateY(-6px);
    opacity: 0;
    pointer-events: none;
    white-space: nowrap;
    background: linear-gradient(135deg, #ff7aa8, #ff5f8f);
    color: #fff;
    padding: 7px 16px;
    border-radius: 999px;
    font-size: 15px;
    font-weight: 900;
    transition: .22s ease;
    z-index: 50;
    box-shadow: 0 8px 18px rgba(255,94,143,.30);
}
.circle-icon:hover .nav-text {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}


@media(max-width:600px) { .bubble { max-width: 88%; } }

@media(max-width:768px){
  html,body{overflow-x:hidden!important;}
  .children-dash{position:sticky!important;top:0!important;}
  .page-wrap{padding:14px 10px 14px!important;}
  .chat-box{height:calc(100vh - 110px)!important;min-height:300px!important;}
  .circle-icon{width:52px!important;height:52px!important;}
}
</style>
</head>
<body class="page-with-children-dash">

<div class="floating-bg" aria-hidden="true">
    <span class="star star-1">★</span><span class="star star-2">★</span>
    <span class="star star-3">★</span><span class="star star-4">★</span>
    <span class="star star-5">★</span><span class="star star-6">★</span>
    <span class="star star-7">★</span><span class="star star-8">★</span>
    <span class="star star-9">★</span><span class="star star-10">★</span>
    <span class="star star-11">★</span><span class="star star-12">★</span>
    <span class="bubble bubble-1"></span><span class="bubble bubble-2"></span>
    <span class="bubble bubble-3"></span><span class="bubble bubble-4"></span>
    <span class="bubble bubble-5"></span><span class="bubble bubble-6"></span>
    <span class="bubble bubble-7"></span><span class="bubble bubble-8"></span>
</div>

<header class="children-dash">
  <div class="children-dash-inner">

    <div class="dash-start">
      <a href="../../index.php"
         class="logo-box"
         tabindex="0"
         data-dashboard-video="../../images/videos/logo.mp4"
         data-dashboard-text="شعار منصة شمعة أمل">
        <img src="../../logo.png" alt="logo">
      </a>
    </div>

    <nav class="dash-nav">
      <a href="../../auth/children.php"
         class="circle-icon home-icon"
         tabindex="0"
         aria-label="العودة إلى الصفحة الرئيسية"
         data-dashboard-video="../../assets/videos/home-icon-sign.mp4"
         data-dashboard-text="صفحة الطفل">
        <video class="nav-icon-video" autoplay muted loop playsinline>
          <source src="../../assets/icons/children.mp4" type="video/mp4">
        </video>
        <span class="nav-text">صفحة الطفل</span>
      </a>
    </nav>

    <div class="profile-wrap">
      <button type="button" class="profile-btn" id="profileBtn" tabindex="0">
        <span class="profile-hello">
          مرحباً <?php echo htmlspecialchars($child_name); ?>
        </span>
        <div class="profile-video-box">
          <video autoplay muted loop playsinline>
            <source src="../../assets/icons/profile.mp4" type="video/mp4">
          </video>
        </div>
      </button>

      <div class="profile-menu" id="profileMenu">
        <a href="../../auth/account_settings.php"
           data-dashboard-video="../../assets/videos/settings-sign.mp4"
           data-dashboard-text="إعدادات الحساب">إعدادات الحساب</a>
        <a href="../../auth/logout.php"
           data-dashboard-video="../../assets/videos/logout-sign.mp4"
           data-dashboard-text="تسجيل الخروج">تسجيل الخروج</a>
      </div>
    </div>

  </div>
</header>

<div class="page-wrap">
  <div class="chat-box">

    <div class="chat-header">
      <div class="chat-header-avatar-wrap">
        <div class="chat-header-avatar">🧑‍💼</div>
        <span class="online-dot"></span>
      </div>
      <div class="chat-header-text">
        <h2>المشرف</h2>
        <div class="chat-header-status">
          <span class="status-dot"></span>
          <span>متصل الآن</span>
        </div>
      </div>
    </div>

    <div class="chat-messages" id="msgArea">
      <?php if (empty($messages)): ?>
        <div class="chat-empty">
          <span class="icon">💌</span>
          <p>لا توجد رسائل بعد<br>ابدأ المحادثة الآن!</p>
        </div>
      <?php else:
        $prev_sender = null;
        $prev_date   = null;
        foreach ($messages as $msg):
          $side       = $msg['sender'] === 'child' ? 'sent' : 'received';
          $time       = date('H:i', strtotime($msg['created_at']));
          $msg_date   = date('Y-m-d', strtotime($msg['created_at']));
          $today      = date('Y-m-d');
          $yesterday  = date('Y-m-d', strtotime('-1 day'));
          $date_label = ($msg_date === $today)      ? 'اليوم'
                      : (($msg_date === $yesterday)  ? 'أمس'
                      : date('d/m/Y', strtotime($msg['created_at'])));
          $chain      = ($prev_sender === $side && $prev_date === $msg_date);
          if ($msg_date !== $prev_date):
      ?>
        <div class="date-sep"><span><?= $date_label ?></span></div>
      <?php endif; ?>
      <div class="msg-row <?= $side ?> <?= $chain ? 'chain' : '' ?>">
        <div class="bubble">
          <?= nl2br(htmlspecialchars($msg['message'])) ?>
          <span class="bubble-time">
            <?= $time ?>
            <?php if ($side === 'sent'): ?><span class="tick"> ✓✓</span><?php endif; ?>
          </span>
        </div>
      </div>
      <?php
          $prev_sender = $side;
          $prev_date   = $msg_date;
        endforeach;
      endif; ?>
    </div>

    <div class="chat-input-bar">
      <form id="chatForm" style="display:contents" method="post">
        <input type="hidden" name="send_message" value="1">
        <textarea id="chatTextarea" class="chat-textarea" name="message_text"
                  placeholder="اكتب رسالتك..." rows="1"
                  onInput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
        <button type="submit" class="chat-send-btn" title="إرسال">➤</button>
      </form>
    </div>

  </div>
</div>


<script>
const area = document.getElementById('msgArea');
let lastMsgId = <?= $messages ? end($messages)['id'] : 0 ?>;

function scrollToBottom() {
    if (area) area.scrollTop = area.scrollHeight;
}
scrollToBottom();

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function appendBubble(side, message, time, id) {
    if (!area) return;
    if (id && document.querySelector(`[data-msg-id="${id}"]`)) return;
    const row = document.createElement('div');
    row.className = 'msg-row ' + side;
    if (id) row.dataset.msgId = id;
    const tick = side === 'sent' ? '<span class="tick"> ✓✓</span>' : '';
    row.innerHTML = `<div class="bubble">${escapeHtml(message).replace(/\n/g,'<br>')}<span class="bubble-time">${time}${tick}</span></div>`;
    area.appendChild(row);
    scrollToBottom();
}

function sendMessage() {
    const ta = document.getElementById('chatTextarea');
    const text = ta.value.trim();
    if (!text) return;

    const now = new Date();
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');

    appendBubble('sent', text, hh + ':' + mm, null);

    const fd = new FormData();
    fd.append('send_message', '1');
    fd.append('message_text', text);
    fetch('child-chat.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(() => {
            fetch('child-chat.php?poll=1&last_id=' + lastMsgId)
                .then(r => r.json())
                .then(rows => {
                    rows.forEach(msg => {
                        lastMsgId = Math.max(lastMsgId, msg.id);
                    });
                });
        });

    ta.value = '';
    ta.style.height = 'auto';
}

setInterval(function() {
    fetch('child-chat.php?poll=1&last_id=' + lastMsgId)
        .then(r => r.json())
        .then(rows => {
            rows.forEach(function(msg) {
                if (document.querySelector(`[data-msg-id="${msg.id}"]`)) return;
                lastMsgId = Math.max(lastMsgId, msg.id);
                const t = msg.created_at.substr(11, 5);
                const side = msg.sender === 'child' ? 'sent' : 'received';
                appendBubble(side, msg.message, t, msg.id);
            });
        });
}, 3000);

document.getElementById('chatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    sendMessage();
});

document.getElementById('chatTextarea').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

/* ─── Profile menu ─── */
document.addEventListener("DOMContentLoaded", function () {
    const profileBtn  = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");

    if (profileBtn && profileMenu) {
        profileBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle("show");
        });
        document.addEventListener("click", function(e) {
            if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.remove("show");
            }
        });
    }

});
</script>
</body>
</html>