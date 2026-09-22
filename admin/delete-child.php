<?php
require_once 'includes/admin-check.php';
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {

    // جداول مرتبطة بـ child_id
    $byChildId = ['chat_messages', 'lesson_progress', 'game_progress', 'story_progress', 'general_section_progress'];
    foreach ($byChildId as $table) {
        $chk = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $st = mysqli_prepare($conn, "DELETE FROM `{$table}` WHERE child_id = ?");
            if ($st) { mysqli_stmt_bind_param($st, 'i', $id); mysqli_stmt_execute($st); }
        }
    }

    // جدول progress مرتبط بـ user_id
    $chk = mysqli_query($conn, "SHOW TABLES LIKE 'progress'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $st = mysqli_prepare($conn, "DELETE FROM progress WHERE user_id = ?");
        if ($st) { mysqli_stmt_bind_param($st, 'i', $id); mysqli_stmt_execute($st); }
    }

    // حذف الطفل نفسه
    $st = mysqli_prepare($conn, "DELETE FROM children WHERE id = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
}

header("Location: children.php");
exit;
?>