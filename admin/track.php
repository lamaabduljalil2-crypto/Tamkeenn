<?php
/*
   نظام تتبّع تقدّم الأطفال.
   ضع هذا الملف في جذر المشروع:  kids/track.php
   الاستخدام داخل أي صفحة طفل (بعد session_start وتضمين db.php):
       require_once __DIR__ . '/../../track.php';
       track_progress($conn, 'story', 'story-5', 'اسم القصة');
   الأنواع المسموحة: lesson | story | game | section
*/

if (!function_exists('track_progress')) {

    function track_progress($conn, $type, $key, $label = '') {

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        /* نسجّل فقط للأطفال المسجّلين دخول */
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') {
            return;
        }

        $allowed = ['lesson', 'story', 'game', 'section'];
        if (!in_array($type, $allowed, true)) {
            return;
        }

        if (!$conn) {
            return;
        }

        mysqli_set_charset($conn, 'utf8mb4');

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

        $uid   = intval($_SESSION['user_id']);
        $cname = $_SESSION['child_name'] ?? $_SESSION['username'] ?? '';
        $type  = substr((string)$type, 0, 20);
        $key   = substr((string)$key, 0, 100);
        $label = substr((string)$label, 0, 255);

        if ($key === '') {
            return;
        }

        $st = mysqli_prepare(
            $conn,
            "INSERT INTO progress (user_id, child_name, activity_type, activity_key, activity_label)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE activity_label = VALUES(activity_label), created_at = CURRENT_TIMESTAMP"
        );

        if ($st) {
            mysqli_stmt_bind_param($st, "issss", $uid, $cname, $type, $key, $label);
            mysqli_stmt_execute($st);
        }
    }
}
