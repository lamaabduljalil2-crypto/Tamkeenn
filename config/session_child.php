<?php
if (session_status() === PHP_SESSION_NONE) {
    $life = 604800; // 7 أيام
    ini_set('session.gc_maxlifetime', $life);
    ini_set('session.cookie_lifetime', $life);
    session_set_cookie_params([
        'lifetime' => $life,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('kids_child');
    session_start();
}
