<?php
require_once __DIR__.'/../../config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $p['path'],
        'domain' => $p['domain'],
        'secure' => $p['secure'],
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_destroy();

header('Location: login.php');
exit;