<?php
$conn = new mysqli("localhost", "root", "", "kids");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات");
}

$conn->set_charset("utf8mb4");
?>