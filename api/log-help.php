<?php
if (session_status() === PHP_SESSION_NONE) require_once __DIR__ . '/../config/session_child.php';
require_once __DIR__ . '/../config/db.php';
mysqli_set_charset($conn, 'utf8mb4');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'child') {
    http_response_code(403);
    echo '{"ok":false}';
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS help_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    child_name VARCHAR(255) NULL,
    page_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$uid   = intval($_SESSION['user_id']);
$cname = substr($_SESSION['child_name'] ?? $_SESSION['username'] ?? '', 0, 255);
$page  = substr($_POST['page'] ?? '', 0, 500);

$st = mysqli_prepare($conn, "INSERT INTO help_clicks (user_id, child_name, page_url) VALUES (?,?,?)");
if ($st) {
    mysqli_stmt_bind_param($st, "iss", $uid, $cname, $page);
    mysqli_stmt_execute($st);
}

echo '{"ok":true}';
