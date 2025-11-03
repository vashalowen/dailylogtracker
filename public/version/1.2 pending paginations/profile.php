<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// block access if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// get the profile details for this user
list($status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');

$profile = ($status === 200 && !empty($profile_data)) ? $profile_data[0] : null;

$page_title = 'Profile Info';
$page_heading = 'Profile Information';
include 'partials/header.php';
?>



    <?php if ($profile): ?>
        <div class="box">
           
            <div class="field"><label>Email:</label> <?= htmlspecialchars($profile['email']) ?></div>
            <div class="field"><label>Department:</label> <?= htmlspecialchars($profile['department']) ?></div>
            <div class="field"><label>Role:</label> <?= htmlspecialchars($profile['role']) ?></div>
            <div class="field"><label>Created:</label> <?= date('Y-m-d h:i A', strtotime($profile['created_at'])) ?></div>
        </div>
    <?php else: ?>
        <p>Profile not found or access denied (HTTP <?= $status ?>)</p>
    <?php endif; ?>
</body>
</html>
