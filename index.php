<?php


session_start();

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php');
    exit;
}

?>