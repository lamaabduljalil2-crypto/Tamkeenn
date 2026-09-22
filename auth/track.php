<?php
/**
 * track.php — نقطة التراك المركزية
 * المسار: auth/track.php
 */

require_once __DIR__ . '/../config/session_child.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once '../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

// إنشاء جدول progress إن لم يكن موجوداً
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// إنشاء جدول الثقافة العامة إن لم يكن موجوداً
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS general_section_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        child_id INT NOT NULL,
        section_name VARCHAR(100) NOT NULL,
        visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (child_id),
        INDEX (section_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$child_id = intval($_SESSION['user_id']);
$type     = trim($_POST['type'] ?? '');
$id       = intval($_POST['id']   ?? 0);
$section  = trim($_POST['section'] ?? '');

$validTypes = ['lesson', 'game', 'story', 'general'];

if (!$child_id || !in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_params']);
    exit;
}

// lesson/game/story تحتاج id > 0 — general تحتاج section غير فارغ
if (in_array($type, ['lesson', 'game', 'story']) && $id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_params']);
    exit;
}

if ($type === 'general' && $section === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_params']);
    exit;
}

$cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';

switch ($type) {

    case 'lesson':
        $stmt = mysqli_prepare($conn,
            "INSERT INTO lesson_progress (child_id, lesson_id, completed_at)
             VALUES (?, ?, NOW())"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $child_id, $id);
            mysqli_stmt_execute($stmt);
        }
        break;

    case 'game':
        $stmt = mysqli_prepare($conn,
            "INSERT INTO game_progress (child_id, game_id, played_at)
             VALUES (?, ?, NOW())"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $child_id, $id);
            mysqli_stmt_execute($stmt);
        }
        break;

    case 'story':
        $stmt = mysqli_prepare($conn,
            "INSERT INTO story_progress (child_id, story_id, completed_at)
             VALUES (?, ?, NOW())"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $child_id, $id);
            mysqli_stmt_execute($stmt);
        }
        break;

    case 'general':
        // ✅ كتابة في جدول general_section_progress
        $stmt = mysqli_prepare($conn,
            "INSERT INTO general_section_progress (child_id, section_name, visited_at)
             VALUES (?, ?, NOW())"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $child_id, $section);
            mysqli_stmt_execute($stmt);
        }

        // ✅ FIX: أيضاً كتابة في جدول progress حتى يظهر في صفحة التقدم
        $pkey   = substr('section-' . $section, 0, 100);
        $plabel = substr($section, 0, 255);
        $ptype  = 'section';
        $st2 = mysqli_prepare($conn,
            "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE activity_label = VALUES(activity_label), created_at = CURRENT_TIMESTAMP"
        );
        if ($st2) {
            mysqli_stmt_bind_param($st2, 'issss', $child_id, $cname, $ptype, $pkey, $plabel);
            mysqli_stmt_execute($st2);
        }
        break;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
