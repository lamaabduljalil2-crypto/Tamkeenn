<?php
/**
 * auth/save_game_progress.php
 * ===========================
 * Endpoint تستدعيه الألعاب (fetch/POST) عند الفوز لتسجيل إكمال اللعبة.
 *
 * المعاملات المطلوبة (POST):
 *   game_key   — نوع اللعبة:  memory | cups | chase | colors | numbers
 *   game_label — اسم اللعبة بالعربي: لعبة الذاكرة ...
 */

require_once __DIR__ . '/../config/session_child.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

/* يجب أن يكون الطفل مسجّل الدخول */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

$user_id    = (int) $_SESSION['user_id'];
$child_name = trim($_SESSION['child_name']  ??
              trim($_SESSION['full_name']   ??
              trim($_SESSION['username']    ?? '')));

$game_key   = trim($_POST['game_key']   ?? '');
$game_label = trim($_POST['game_label'] ?? '');

if ($game_key === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_game_key']);
    exit;
}

/*
 * INSERT … ON DUPLICATE KEY UPDATE
 * إذا اللعبة مسجّلة مسبقاً للطفل نحدّث التاريخ فقط
 * (الـ UNIQUE KEY على user_id + activity_type + activity_key)
 */
$stmt = $conn->prepare("
    INSERT INTO progress
        (user_id, child_name, activity_type, activity_key, activity_label)
    VALUES
        (?, ?, 'game', ?, ?)
    ON DUPLICATE KEY UPDATE
        child_name     = VALUES(child_name),
        activity_label = VALUES(activity_label),
        created_at     = CURRENT_TIMESTAMP
");

$stmt->bind_param('isss', $user_id, $child_name, $game_key, $game_label);
$ok = $stmt->execute();

echo json_encode(['ok' => $ok]);
