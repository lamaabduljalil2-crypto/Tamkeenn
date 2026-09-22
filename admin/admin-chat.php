<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

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

/* ── AJAX: إرسال رد ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ajax'])) {
    header('Content-Type: application/json');
    if (!supCan('chat', 'can_add')) { echo json_encode(['ok'=>false,'err'=>'no_perm']); exit; }
    $child_id   = intval($_POST['child_id'] ?? 0);
    $child_name = trim($_POST['child_name'] ?? '');
    $text       = trim($_POST['reply_text'] ?? '');
    if ($child_id > 0 && $text !== '') {
        $ins = mysqli_prepare($conn,
            "INSERT INTO chat_messages (child_id, child_name, sender, message) VALUES (?,?,'admin',?)");
        mysqli_stmt_bind_param($ins, 'iss', $child_id, $child_name, $text);
        mysqli_stmt_execute($ins);
        echo json_encode(['ok' => true, 'id' => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

/* ── AJAX: جلب رسائل جديدة ── */
if (isset($_GET['poll']) && isset($_GET['child_id']) && isset($_GET['last_id'])) {
    header('Content-Type: application/json');
    $cid     = intval($_GET['child_id']);
    $last_id = intval($_GET['last_id']);
    $u = mysqli_prepare($conn, "UPDATE chat_messages SET is_read=1 WHERE child_id=? AND sender='child' AND is_read=0");
    mysqli_stmt_bind_param($u, 'i', $cid);
    mysqli_stmt_execute($u);
    $q = mysqli_prepare($conn, "SELECT * FROM chat_messages WHERE child_id=? AND id>? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($q, 'ii', $cid, $last_id);
    mysqli_stmt_execute($q);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    if (supCan('chat', 'can_add')) {
        $child_id   = intval($_POST['child_id'] ?? 0);
        $child_name = trim($_POST['child_name'] ?? '');
        $text       = trim($_POST['reply_text'] ?? '');
        if ($child_id > 0 && $text !== '') {
            $ins = mysqli_prepare($conn,
                "INSERT INTO chat_messages (child_id, child_name, sender, message) VALUES (?,?,'admin',?)");
            mysqli_stmt_bind_param($ins, 'iss', $child_id, $child_name, $text);
            mysqli_stmt_execute($ins);
            header("Location: admin-chat.php?child_id={$child_id}");
            exit;
        }
    }
}

$active_child_id = intval($_GET['child_id'] ?? 0);

if ($active_child_id > 0) {
    $u = mysqli_prepare($conn,
        "UPDATE chat_messages SET is_read=1 WHERE child_id=? AND sender='child' AND is_read=0");
    mysqli_stmt_bind_param($u, 'i', $active_child_id);
    mysqli_stmt_execute($u);
}

$convs = mysqli_query($conn, "
    SELECT child_id, child_name,
           MAX(created_at) AS last_time,
           SUM(sender='child' AND is_read=0) AS unread
    FROM chat_messages
    GROUP BY child_id, child_name
    ORDER BY last_time DESC
");
$conversations = mysqli_fetch_all($convs, MYSQLI_ASSOC);

$all_children = [];
$children_res = mysqli_query($conn,
    "SELECT id, username AS display_name FROM children ORDER BY username ASC");
if ($children_res) $all_children = mysqli_fetch_all($children_res, MYSQLI_ASSOC);

$active_msgs = [];
$active_name = '';
if ($active_child_id > 0) {
    foreach ($all_children as $ch) {
        if ($ch['id'] == $active_child_id) { $active_name = $ch['display_name']; break; }
    }
    $q = mysqli_prepare($conn,
        "SELECT * FROM chat_messages WHERE child_id=? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($q, 'i', $active_child_id);
    mysqli_stmt_execute($q);
    $active_msgs = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    if (!$active_name && $active_msgs) $active_name = $active_msgs[0]['child_name'];
}

$unread_map = [];
foreach ($conversations as $c) $unread_map[$c['child_id']] = intval($c['unread']);
$total_unread = array_sum(array_column($conversations, 'unread'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>المحادثات</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f6ff; }

.page-title { text-align:center; color:#21425f; font-size:28px; font-weight:900; margin-bottom:4px; }
.page-sub   { text-align:center; color:#6a849a; font-size:14px; margin-bottom:18px; }

/* ── layout: sidebar + chat ── */
.chat-layout {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 16px;
    height: calc(100vh - 170px);
    min-height: 500px;
}

/* ── sidebar panel (tabbed) ── */
.side-panel {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(33,66,95,.08);
    border: 1px solid #cce3f8;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* tabs */
.panel-tabs {
    display: flex;
    border-bottom: 2px solid #cce3f8;
    flex-shrink: 0;
}
.tab-btn {
    flex: 1;
    padding: 13px 8px;
    font-size: 13px;
    font-weight: 800;
    border: none;
    background: #f0f6ff;
    color: #6a849a;
    cursor: pointer;
    transition: .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}
.tab-btn:first-child { border-radius: 16px 0 0 0; }
.tab-btn:last-child  { border-radius: 0 16px 0 0; }
.tab-btn.active {
    background: #fff;
    color: #0277bd;
    border-bottom-color: #0277bd;
}
.tab-badge {
    background: #f44336;
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    padding: 1px 5px;
    border-radius: 999px;
    min-width: 16px;
    text-align: center;
}

/* tab content panes */
.tab-pane { display: none; flex: 1; flex-direction: column; overflow: hidden; }
.tab-pane.active { display: flex; }

.panel-search { padding: 10px 12px; border-bottom: 1px solid #e8f3fc; flex-shrink: 0; }
.panel-search input {
    width: 100%; padding: 8px 14px;
    border: 1px solid #b3d9f5; border-radius: 22px;
    font-size: 14px; background: #f0f6ff; outline: none; color: #303030;
    font-family: inherit;
}
.panel-search input:focus { border-color: #29b6f6; }
.panel-items { overflow-y: auto; flex: 1; }

/* child item */
.child-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; text-decoration: none; color: #303030;
    border-bottom: 1px solid #f0f6ff; transition: .15s; cursor: pointer;
}
.child-item:hover  { background: #e8f3fc; }
.child-item.active { background: #bbdefb; }

.child-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: #b3d9f5; color: #21425f; font-size: 18px; font-weight: 900;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.child-avatar.has-msg { background: #0277bd; color: #fff; }

.child-info { flex: 1; min-width: 0; }
.child-name { font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.child-sub  { font-size: 12px; color: #8ab0c8; margin-top: 2px; }

.unread-badge {
    background: #f44336; color: #fff; font-size: 11px; font-weight: 900;
    padding: 2px 7px; border-radius: 999px; min-width: 20px;
    text-align: center; flex-shrink: 0;
}
.no-items { padding: 32px 14px; text-align: center; color: #8ab0c8; font-size: 14px; font-weight: 700; }

/* conv item */
.conv-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; text-decoration: none; color: #303030;
    border-bottom: 1px solid #f0f6ff; transition: .15s;
}
.conv-item:hover  { background: #e8f3fc; }
.conv-item.active { background: #bbdefb; }

.conv-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: #0288d1; color: #fff; font-size: 18px; font-weight: 900;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.conv-info { flex: 1; min-width: 0; }
.conv-name { font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-time { font-size: 12px; color: #667781; margin-top: 2px; }

/* ── chat window ── */
.chat-window {
    border-radius: 18px;
    box-shadow: 0 10px 40px rgba(33,66,95,.15);
    display: flex; flex-direction: column; overflow: hidden;
    background: #e8f4fd;
}

.chat-header {
    background: linear-gradient(135deg, #5498d4, #21425f);
    color: #fff;
    padding: 14px 22px;
    display: flex; align-items: center; gap: 14px;
    flex-shrink: 0; direction: rtl;
}
.chat-header a { color: rgba(255,255,255,.85); text-decoration: none; font-size: 22px; line-height: 1; }
.chat-header-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,.2);
    border: 2px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
}
.chat-header-text { flex: 1; }
.chat-header-text h2 { font-size: 18px; font-weight: 900; }
.chat-header-text p  { font-size: 13px; opacity: .8; margin-top: 3px; }

/* messages */
.messages-area {
    flex: 1; overflow-y: auto;
    padding: 16px 20px;
    display: flex; flex-direction: column; gap: 4px;
    direction: ltr;
    background: #e8f4fd;
}

.date-sep { text-align:center; margin: 12px 0 6px; direction: rtl; }
.date-sep span {
    background: rgba(41,182,246,.15); color: #21425f;
    font-size: 12px; font-weight: 700;
    padding: 4px 16px; border-radius: 20px;
}

.msg-row { display: flex; margin-bottom: 2px; }
.msg-row.admin { justify-content: flex-end; }
.msg-row.child { justify-content: flex-start; }

.bubble {
    position: relative;
    max-width: 65%; min-width: 100px;
    padding: 10px 16px 26px;
    border-radius: 18px;
    font-size: 15px; line-height: 1.6;
    word-break: break-word;
    direction: rtl;
    box-shadow: 0 2px 8px rgba(33,66,95,.1);
}
.msg-row.admin .bubble {
    background: linear-gradient(135deg, #5498d4, #21425f);
    color: #fff; border-top-right-radius: 4px;
}
.msg-row.admin .bubble::after {
    content: ''; position: absolute; top: 0; right: -8px;
    border-top: 9px solid #5498d4; border-right: 9px solid transparent;
}
.msg-row.child .bubble {
    background: #fff; color: #21425f;
    border: 1px solid #b3d9f5; border-top-left-radius: 4px;
}
.msg-row.child .bubble::after {
    content: ''; position: absolute; top: 0; left: -8px;
    border-top: 9px solid #fff; border-left: 9px solid transparent;
}
.bubble-sender { font-size: 12px; font-weight: 900; color: #5498d4; margin-bottom: 4px; }
.bubble-time {
    position: absolute; bottom: 6px;
    font-size: 11px; white-space: nowrap; direction: ltr;
}
.msg-row.admin .bubble-time { left: 12px; color: rgba(255,255,255,.75); }
.msg-row.child .bubble-time { right: 12px; color: #8ab0c8; }
.tick { font-size: 11px; opacity: .85; }
.msg-row.chain .bubble::after { display: none; }
.msg-row.chain.admin .bubble { border-top-right-radius: 18px; }
.msg-row.chain.child .bubble { border-top-left-radius: 18px; }

/* reply box */
.reply-box {
    padding: 12px 16px;
    background: #d0e8f8; border-top: 1px solid #b3d9f5;
    flex-shrink: 0; direction: rtl;
}
.reply-form { display: flex; gap: 10px; align-items: flex-end; }
.reply-textarea {
    flex: 1; padding: 11px 18px;
    border: 1.5px solid #a8cce8; border-radius: 26px;
    font-size: 15px; font-family: inherit; color: #21425f;
    resize: none; min-height: 46px; max-height: 120px;
    overflow-y: auto; background: #fff; outline: none; direction: rtl;
    transition: border-color .2s;
}
.reply-textarea:focus { border-color: #29b6f6; }
.btn-send {
    width: 46px; height: 46px; border-radius: 50%;
    background: linear-gradient(135deg, #29b6f6, #0277bd);
    color: #fff; font-size: 18px;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 3px 10px rgba(2,119,189,.4);
    transition: .2s; flex-shrink: 0;
}
.btn-send:hover { transform: scale(1.1); }

/* empty state */
.no-chat {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #e8f4fd; color: #6a9db8; gap: 12px;
}
.no-chat span { font-size: 56px; }
.no-chat p { font-size: 16px; font-weight: 700; }

/* ── responsive ── */
@media (max-width: 860px) {
    .chat-layout {
        grid-template-columns: 1fr;
        height: auto;
    }
    .side-panel { height: 300px; }
    .chat-window { height: calc(100vh - 200px); min-height: 400px; }
}

@media (max-width: 600px) {
    .chat-layout { gap: 10px; }
    .bubble { max-width: 80%; font-size: 14px; }
    .chat-header-text h2 { font-size: 16px; }
}
</style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/admin-sidebar.php'; ?>

<main class="admin-content">
<h1 class="page-title">💬 المحادثات</h1>
<p class="page-sub">تواصل مع الأطفال وأجب على رسائلهم</p>

<div class="chat-layout">

  <!-- ── Sidebar: تابات الأطفال والمحادثات ── -->
  <div class="side-panel">
    <div class="panel-tabs">
      <button class="tab-btn active" onclick="switchTab('children', this)">
        👧 الأطفال
        <span class="tab-badge" style="background:#0277bd"><?= count($all_children) ?></span>
      </button>
      <button class="tab-btn" onclick="switchTab('convs', this)">
        📋 المحادثات
        <?php if ($total_unread > 0): ?>
          <span class="tab-badge"><?= $total_unread ?></span>
        <?php else: ?>
          <span class="tab-badge" style="background:#0277bd"><?= count($conversations) ?></span>
        <?php endif; ?>
      </button>
    </div>

    <!-- تاب: الأطفال -->
    <div class="tab-pane active" id="tab-children">
      <div class="panel-search">
        <input type="text" id="childSearch" placeholder="🔍 ابحث عن طفل..." oninput="filterChildren(this.value)">
      </div>
      <div class="panel-items" id="childList">
        <?php if (empty($all_children)): ?>
          <div class="no-items">لا يوجد أطفال مسجلون</div>
        <?php endif; ?>
        <?php foreach ($all_children as $ch):
          $cid      = $ch['id'];
          $cname    = $ch['display_name'];
          $initial  = mb_substr($cname, 0, 1);
          $has_conv = isset($unread_map[$cid]);
          $unread   = $unread_map[$cid] ?? 0;
          $is_active = ($cid == $active_child_id);
        ?>
        <a href="admin-chat.php?child_id=<?= $cid ?>"
           class="child-item <?= $is_active ? 'active' : '' ?>"
           data-name="<?= htmlspecialchars(mb_strtolower($cname)) ?>">
          <div class="child-avatar <?= $has_conv ? 'has-msg' : '' ?>"><?= htmlspecialchars($initial) ?></div>
          <div class="child-info">
            <div class="child-name"><?= htmlspecialchars($cname) ?></div>
            <div class="child-sub"><?= $has_conv ? 'محادثة موجودة' : 'لا توجد محادثة' ?></div>
          </div>
          <?php if ($unread > 0): ?>
            <span class="unread-badge"><?= $unread ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- تاب: المحادثات -->
    <div class="tab-pane" id="tab-convs">
      <div class="panel-items">
        <?php if (empty($conversations)): ?>
          <div class="no-items">لا توجد محادثات بعد</div>
        <?php endif; ?>
        <?php foreach ($conversations as $c):
          $is_active = ($c['child_id'] == $active_child_id);
          $initial   = mb_substr($c['child_name'], 0, 1);
          $time_str  = date('d/m H:i', strtotime($c['last_time']));
        ?>
        <a href="admin-chat.php?child_id=<?= $c['child_id'] ?>"
           class="conv-item <?= $is_active ? 'active' : '' ?>">
          <div class="conv-avatar"><?= htmlspecialchars($initial) ?></div>
          <div class="conv-info">
            <div class="conv-name"><?= htmlspecialchars($c['child_name']) ?></div>
            <div class="conv-time"><?= $time_str ?></div>
          </div>
          <?php if ($c['unread'] > 0): ?>
            <span class="unread-badge"><?= $c['unread'] ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── نافذة الشات ── -->
  <div class="chat-window">
    <?php if ($active_child_id > 0): ?>

      <div class="chat-header">
        <a href="admin-chat.php" title="رجوع">←</a>
        <div class="chat-header-avatar"><?= mb_substr($active_name, 0, 1) ?></div>
        <div class="chat-header-text">
          <h2><?= htmlspecialchars($active_name) ?></h2>
          <p>محادثة مع الطفل</p>
        </div>
      </div>

      <div class="messages-area" id="msgArea">
        <?php if (empty($active_msgs)): ?>
          <div class="no-chat" style="border-radius:0;">
            <span>✉️</span><p>لا توجد رسائل — ابدأ المحادثة</p>
          </div>
        <?php else:
          $prev_sender = null;
          $prev_date   = null;
          foreach ($active_msgs as $msg):
            $side       = $msg['sender'] === 'child' ? 'child' : 'admin';
            $time       = date('H:i', strtotime($msg['created_at']));
            $msg_date   = date('Y-m-d', strtotime($msg['created_at']));
            $today      = date('Y-m-d');
            $yesterday  = date('Y-m-d', strtotime('-1 day'));
            $date_label = ($msg_date === $today) ? 'اليوم'
                        : (($msg_date === $yesterday) ? 'أمس'
                        : date('d/m/Y', strtotime($msg['created_at'])));
            $chain      = ($prev_sender === $side && $prev_date === $msg_date);
            if ($msg_date !== $prev_date):
        ?>
          <div class="date-sep"><span><?= $date_label ?></span></div>
        <?php endif; ?>
        <div class="msg-row <?= $side ?> <?= $chain ? 'chain' : '' ?>">
          <div class="bubble">
            <?php if ($side === 'admin' && !$chain): ?>
              <div class="bubble-sender">الأدمن</div>
            <?php endif; ?>
            <?= nl2br(htmlspecialchars($msg['message'])) ?>
            <span class="bubble-time">
              <?= $time ?>
              <?php if ($side === 'admin'): ?>
                <span class="tick"> ✓✓</span>
              <?php endif; ?>
            </span>
          </div>
        </div>
        <?php
            $prev_sender = $side;
            $prev_date   = $msg_date;
          endforeach;
        endif; ?>
      </div>

      <div class="reply-box">
        <form id="adminChatForm" class="reply-form" method="post">
          <input type="hidden" name="child_id"   value="<?= $active_child_id ?>">
          <input type="hidden" name="child_name" value="<?= htmlspecialchars($active_name) ?>">
          <input type="hidden" name="reply" value="1">
          <textarea id="adminTextarea" class="reply-textarea" name="reply_text"
                    placeholder="اكتب رسالتك..." rows="1"
                    onInput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
          <button type="submit" class="btn-send" title="إرسال">➤</button>
        </form>
      </div>

    <?php else: ?>
      <div class="no-chat">
        <span>💬</span>
        <p>اختر طفلاً من القائمة لبدء المحادثة</p>
      </div>
    <?php endif; ?>
  </div>

</div>
</main>
</div>

<script>
/* ── Tabs ── */
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

/* إذا كان هناك طفل محدد، افتح تاب المحادثات تلقائياً */
<?php if ($active_child_id > 0 && count($conversations) > 0): ?>
const activeConvTab = document.querySelector('.tab-btn:nth-child(2)');
if (activeConvTab) switchTab('convs', activeConvTab);
<?php endif; ?>

/* ── Scroll to bottom ── */
const area = document.getElementById('msgArea');
if (area) area.scrollTop = area.scrollHeight;

/* ── Last known ID ── */
let lastMsgId = <?= $active_msgs ? end($active_msgs)['id'] : 0 ?>;
const activeChildId = <?= $active_child_id ?>;

/* ── Send AJAX ── */
function adminSend() {
    const ta = document.getElementById('adminTextarea');
    const text = ta.value.trim();
    if (!text || !activeChildId) return;
    const childName = document.querySelector('input[name="child_name"]')?.value || '';
    const fd = new FormData();
    fd.append('reply_ajax', '1');
    fd.append('child_id', activeChildId);
    fd.append('child_name', childName);
    fd.append('reply_text', text);
    fetch('admin-chat.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                lastMsgId = data.id;
                const now = new Date();
                const hh = String(now.getHours()).padStart(2,'0');
                const mm = String(now.getMinutes()).padStart(2,'0');
                appendBubble('admin', text, hh + ':' + mm, data.id);
            }
        });
    ta.value = '';
    ta.style.height = 'auto';
}

/* ── Append bubble ── */
function appendBubble(sender, message, time, id) {
    if (!area) return;
    const side = sender === 'admin' ? 'admin' : 'child';
    const row = document.createElement('div');
    row.className = 'msg-row ' + side;
    row.dataset.msgId = id;
    const tick = sender === 'admin' ? '<span class="tick"> ✓✓</span>' : '';
    const safe = message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    row.innerHTML = `<div class="bubble">${safe}<span class="bubble-time">${time}${tick}</span></div>`;
    area.appendChild(row);
    area.scrollTop = area.scrollHeight;
}

/* ── Polling ── */
if (activeChildId) {
    setInterval(function() {
        fetch(`admin-chat.php?poll=1&child_id=${activeChildId}&last_id=${lastMsgId}`)
            .then(r => r.json())
            .then(rows => {
                rows.forEach(function(msg) {
                    if (document.querySelector(`[data-msg-id="${msg.id}"]`)) return;
                    lastMsgId = Math.max(lastMsgId, msg.id);
                    const t = msg.created_at.substr(11, 5);
                    appendBubble(msg.sender, msg.message, t, msg.id);
                });
            });
    }, 3000);
}

/* ── Keyboard shortcut ── */
const adminTA = document.getElementById('adminTextarea');
if (adminTA) {
    adminTA.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            adminSend();
        }
    });
}
document.getElementById('adminChatForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    adminSend();
});

/* ── Filter children ── */
function filterChildren(val) {
    const q = val.trim().toLowerCase();
    document.querySelectorAll('#childList .child-item').forEach(function(item) {
        const name = item.getAttribute('data-name') || '';
        item.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
    const visible = [...document.querySelectorAll('#childList .child-item')]
        .filter(i => i.style.display !== 'none');
    const empty = document.querySelector('#childList .no-items');
    if (empty) empty.style.display = visible.length === 0 ? '' : 'none';
}
</script>
</body>
</html>
