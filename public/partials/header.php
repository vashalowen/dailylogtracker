<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
list($status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
$user = ($status === 200 && !empty($profile_data)) ? $profile_data[0] : ['name' => 'Unknown', 'role' => 'User'];

$page_title = $page_title ?? 'Daily Tracker';
$page_heading = $page_heading ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($page_title) ?></title>


<link rel="stylesheet" href="../assets/css/dashboard-dark.css">
<link rel="stylesheet" href="../assets/css/modal.css">
<link rel="stylesheet" href="../assets/css/action.css">


</head>
<body>
<header>
    <div class="header-top">
        <h1>REPHIL Daily Log Tracker</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <nav>
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
        <a href="view_reports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_reports.php' ? 'active' : '' ?>">📋 Reports</a>
        <a href="add_report.php" class="<?= basename($_SERVER['PHP_SELF']) === 'add_report.php' ? 'active' : '' ?>">➕ Add Report</a>
        <?php if ($user['role'] === 'Admin'): ?>
            <a href="view_users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_users.php' ? 'active' : '' ?>">👥 Users</a>
            <a href="location_view.php" class="<?= basename($_SERVER['PHP_SELF']) === 'location_view.php' ? 'active' : '' ?>">📍 Locations</a>
        <?php endif; ?>
        <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">👤 Profile</a>
    </nav>
</header>

<div class="container">
    <h2><?= htmlspecialchars($page_heading) ?></h2>
    <p><strong>Welcome,</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</p>

    <hr>
