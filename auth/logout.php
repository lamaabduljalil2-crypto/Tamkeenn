<?php
$life = 604800;
$params = ['lifetime' => $life, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax'];

// أنهِ session الأدمن
ini_set('session.gc_maxlifetime', $life);
ini_set('session.cookie_lifetime', $life);
session_set_cookie_params($params);
session_name('kids_admin');
session_start();
session_unset();
session_destroy();
session_write_close();

// أنهِ session الطفل
ini_set('session.gc_maxlifetime', $life);
ini_set('session.cookie_lifetime', $life);
session_set_cookie_params($params);
session_name('kids_child');
session_start();
session_unset();
session_destroy();
session_write_close();

header("Location: ../auth/login.php");
exit;
