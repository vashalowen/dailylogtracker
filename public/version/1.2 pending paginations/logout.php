<?php
session_start();

// clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// optional: clear cookies if you set any
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// redirect to login page
header('Location: login.php');
exit;
