<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch current user profile
list($p_status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
$profile = ($p_status === 200 && !empty($profile_data)) ? $profile_data[0] : [];

$user_name   = $profile['name'] ?? 'Unknown';
$user_role   = $profile['role'] ?? 'User';
$department  = $profile['department'] ?? '-';
$email       = $profile['email'] ?? '-';

// ✅ Compute date ranges
$today        = date('Y-m-d');
$month_start  = date('Y-m-01');
$month_end    = date('Y-m-t');

// ✅ Fetch report counts
if ($user_role === 'Admin') {
    // Admin sees all
    list($today_status, $today_reports) = supabase_request(
        'daily_reports?select=id&report_date=eq.' . $today,
        'GET'
    );
    list($month_status, $month_reports) = supabase_request(
        'daily_reports?select=id&report_date=gte.' . $month_start . '&report_date=lte.' . $month_end,
        'GET'
    );
} else {
    // Regular user sees their own
    list($today_status, $today_reports) = supabase_request(
        'daily_reports?select=id&user_id=eq.' . $user_id . '&report_date=eq.' . $today,
        'GET'
    );
    list($month_status, $month_reports) = supabase_request(
        'daily_reports?select=id&user_id=eq.' . $user_id . '&report_date=gte.' . $month_start . '&report_date=lte.' . $month_end,
        'GET'
    );
}

$daily_count   = ($today_status === 200 && is_array($today_reports)) ? count($today_reports) : 0;
$monthly_count = ($month_status === 200 && is_array($month_reports)) ? count($month_reports) : 0;

// ✅ Page info
$page_title   = 'Dashboard';
$page_heading = 'Dashboard';
include 'partials/header.php';
?>

<style>
/* --- Dashboard Styling (Dark Enhanced) --- */
.profile-box {
  background: linear-gradient(145deg, rgba(25,35,56,0.95), rgba(15,25,45,0.95));
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  border: 1px solid rgba(45,117,255,0.25);
  color: #d7e2fb;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
  transition: 0.3s ease;
}
.profile-box strong {
  color: #9bb3e6;
}
.profile-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(45,117,255,0.25);
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 22px;
  margin-top: 25px;
}

/* --- Card --- */
.card {
  background: linear-gradient(160deg, rgba(18,28,46,0.95), rgba(15,23,40,0.95));
  border: 1px solid rgba(60,90,140,0.4);
  border-radius: 12px;
  padding: 25px 20px;
  text-align: center;
  color: #e4e9f2;
  transition: all 0.25s ease;
  box-shadow: 0 2px 10px rgba(0,0,0,0.4);
  position: relative;
  overflow: hidden;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(45,117,255,0.25);
  border-color: rgba(74,168,255,0.5);
}
.card a {
  text-decoration: none;
  color: #cfd8f3;
  font-weight: 600;
  letter-spacing: 0.3px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 6px;
  transition: color 0.2s ease;
}
.card a:hover {
  color: #4aa8ff;
}

/* --- Accent Glow for Cards --- */
.card::before {
  content: "";
  position: absolute;
  top: 0;
  left: -50%;
  width: 200%;
  height: 100%;
  background: linear-gradient(120deg, transparent, rgba(74,168,255,0.1), transparent);
  transform: translateX(-100%);
  transition: transform 0.6s ease;
}
.card:hover::before {
  transform: translateX(100%);
}

/* --- Container --- */
.container {
  max-width: 1000px;
  margin: 40px auto;
  background: linear-gradient(180deg, #0e1624, #101b31);
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.5);
  padding: 35px;
  border: 1px solid rgba(60,90,140,0.35);
  color: #e4e9f2;
}

/* --- Responsive --- */
@media (max-width: 768px) {
  .container {
    padding: 18px;
  }
  .grid {
    grid-template-columns: 1fr;
  }
  .card {
    padding: 18px;
  }
}

</style>

<div class="container">

    <div class="profile-box">
        <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($department) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($user_role) ?></p>
    </div>

    <!-- Summary Cards -->
    <div class="grid" style="margin-bottom:25px;">
        <div class="card" style="background:#e8f5e9;">
            <h2 style="color:#1b5e20; margin:0; font-size:2rem;"><?= $daily_count ?></h2>
            <p>Reports Submitted Today</p>
        </div>
        <div class="card" style="background:#e3f2fd;">
            <h2 style="color:#0d47a1; margin:0; font-size:2rem;"><?= $monthly_count ?></h2>
            <p>Reports This Month</p>
        </div>
    </div>

    <!-- Menu Cards -->
    <div class="grid">
        <div class="card"><a href="add_report.php">📝 Submit Daily Report</a></div>
        <div class="card"><a href="view_reports.php">📋 View Reports</a></div>
        <div class="card"><a href="profile.php">👤 My Profile</a></div>

        <?php if ($user_role === 'Admin'): ?>
            <div class="card"><a href="view_users.php">👥 Manage Users</a></div>
            <div class="card"><a href="location_view.php">📍 Manage Locations</a></div>
        <?php endif; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
