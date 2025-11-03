<?php
date_default_timezone_set('Asia/Manila');   // ✅ Always use Philippine time


session_start();
require_once __DIR__ . '/../config/supabase.php';

// --- Require login ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Fetch user profile ---
list($p_status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
$profile = ($p_status === 200 && !empty($profile_data)) ? $profile_data[0] : null;

// --- Fetch locations ---
list($loc_status, $location_data) = supabase_request('location?select=id,locations', 'GET');
$locations = ($loc_status === 200 && !empty($location_data)) ? $location_data : [];

$message = '';
$today = date('Y-m-d');
$mode = 'checkin'; // default

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile) {
    $report_date = trim($_POST['report_date'] ?? '');
    $activity    = trim($_POST['activity'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $location_id = intval($_POST['location_id'] ?? 0);
	

    if (empty($report_date) || empty($activity) || $location_id === 0) {
        $message = "⚠️ Please complete all required fields (Date, Activity, Location).";
    } else {
        // --- Check if user already has report for this location today ---
        $check_endpoint = "daily_reports?user_id=eq.$user_id&location_id=eq.$location_id&report_date=eq.$today";
        list($check_status, $existing_reports) = supabase_request($check_endpoint, 'GET');

        if ($check_status === 200 && !empty($existing_reports)) {
            $mode = 'checkout';
        } else {
            $mode = 'checkin';
        }
		$timestamp = date('Y-m-d H:i:s');
        $now = date('H:i');
        $time_info = ($mode === 'checkin') ? "Check-In: $now" : "Check-Out: $now";

        // Combine into activity text
        $activity .= " ($time_info)";

        // --- Handle multiple photo uploads ---
        $photo_urls = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['photos']['name'] as $i => $name) {
                $tmp_name = $_FILES['photos']['tmp_name'][$i];
                $clean_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                $target = $upload_dir . $clean_name;
                if (move_uploaded_file($tmp_name, $target)) {
                    $photo_urls[] = 'assets/uploads/' . $clean_name;
                }
            }
        }

        $photo_json = !empty($photo_urls) ? json_encode($photo_urls) : null;

        // --- Insert new report ---
        $data = [[
            'user_id'     => $profile['id'],
            'report_date' => $report_date,
            'activity'    => $activity,
            'notes'       => $notes,
            'photo_url'   => $photo_json,
            'location_id' => $location_id,
			'timestamp'   => $timestamp  // ✅ add here
        ]];

        list($status, $response) = supabase_request('daily_reports', 'POST', $data);

        if ($status === 201) {
            $message = ($mode === 'checkin')
                ? "✅ Checked in at $now for this location."
                : "✅ Checked out at $now for this location.";
        } else {
            $message = "❌ Failed to submit report (HTTP $status)";
        }
    }
}

$page_title = 'Add Report';
$page_heading = 'Submit Daily Report';
include 'partials/header.php';
?>

<style>
.form-container {
  max-width: 650px;
  margin: 40px auto;
  background: linear-gradient(145deg, rgba(18,28,46,0.95), rgba(12,20,35,0.95));
  border-radius: 12px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.5);
  padding: 30px;
  border: 1px solid rgba(60,90,140,0.4);
  color: #e4e9f2;
}
input, textarea {
  width: 100%;
  padding: 10px;
  background: #0f1a2c;
  border: 1px solid #23385f;
  border-radius: 6px;
  color: #e4e9f2;
  font-size: 15px;
  margin-bottom: 12px;
}
button {
  background: linear-gradient(90deg, #2d75ff, #4aa8ff);
  color: white;
  border: none;
  padding: 12px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  width: 100%;
  transition: all 0.25s ease;
}
button:hover { filter: brightness(1.15); transform: translateY(-2px); }
.section-title {
  font-weight: 700; color: #9bb3e6;
  margin-top: 25px;
  border-bottom: 1px solid rgba(60,90,140,0.4);
  padding-bottom: 5px;
}
.dropdown-container { position: relative; }
.dropdown-list {
  position: absolute; top: 100%; left: 0; right: 0;
  background: #121d31; border: 1px solid #23385f; border-radius: 6px;
  max-height: 160px; overflow-y: auto; display: none; z-index: 5;
}
.dropdown-item { padding: 8px 10px; cursor: pointer; color: #e4e9f2; }
.dropdown-item:hover { background: rgba(45,117,255,0.2); }
</style>

<div class="form-container">
<h2>Submit Daily Report</h2>

<?php if ($message): ?>
  <div class="message" style="margin-bottom:15px;
       background:<?= str_contains($message, '✅') ? '#e6f8e8' : '#fde8e8' ?>;
       padding:10px; border-radius:6px;">
      <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

<?php if ($profile): ?>
<form method="POST" enctype="multipart/form-data" id="reportForm">
    <div class="section-title">Report Details</div>
	
	<div class="section-title">Timestamp</div>
		<label for="timestamp">Current Timestamp (Auto)</label>
		<input type="text" name="timestamp" id="timestamp" readonly>

    <label for="report_date">Report Date</label>
    <input type="date" name="report_date" id="report_date" required value="<?= htmlspecialchars($today) ?>">

    <label for="activity">Activity</label>
    <textarea name="activity" id="activity" placeholder="Describe your tasks..." required></textarea>

    <label for="notes">Notes (Optional)</label>
    <textarea name="notes" id="notes" placeholder="Additional remarks..."></textarea>

    <div class="section-title">Location</div>
    <div class="dropdown-container">
        <label for="locationSearch">Search Location</label>
        <input type="text" id="locationSearch" placeholder="Type to search..." autocomplete="off">
        <div id="dropdownList" class="dropdown-list"></div>
        <input type="hidden" name="location_id" id="locationId" required>
    </div>

    <div class="section-title">Attachments</div>
    <label for="photos">Upload Photos (Optional)</label>
    <input type="file" name="photos[]" id="photos" accept="image/*" multiple>

    <button type="submit">Submit Report</button>
</form>
<?php else: ?>
  <p>Profile not found for this user.</p>
<?php endif; ?>
</div>

<script>


// === Auto-fill current timestamp (Asia/Manila) ===
function updateTimestamp() {
  const tsField = document.getElementById('timestamp');
  const now = new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' });
  tsField.value = now;
}
updateTimestamp();
setInterval(updateTimestamp, 60000); // refresh every minute





// === Location Dropdown ===
const locations = <?= json_encode($locations) ?>;
const input = document.getElementById('locationSearch');
const list = document.getElementById('dropdownList');
const hiddenInput = document.getElementById('locationId');
let filtered = [];

input.addEventListener('input', () => {
  const query = input.value.toLowerCase().trim();
  list.innerHTML = '';
  if (!query) { list.style.display = 'none'; hiddenInput.value = ''; return; }

  filtered = locations.filter(l => l.locations.toLowerCase().includes(query));
  if (filtered.length) {
    list.style.display = 'block';
    filtered.forEach(l => {
      const item = document.createElement('div');
      item.textContent = l.locations;
      item.classList.add('dropdown-item');
      item.onclick = () => {
        input.value = l.locations;
        hiddenInput.value = l.id;
        list.style.display = 'none';
      };
      list.appendChild(item);
    });
  } else {
    list.style.display = 'block';
    const msg = document.createElement('div');
    msg.textContent = '⚠️ No location found.';
    msg.classList.add('dropdown-item');
    msg.style.color = '#ff6b6b';
    list.appendChild(msg);
    hiddenInput.value = '';
  }
});

input.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (filtered.length > 0) {
      input.value = filtered[0].locations;
      hiddenInput.value = filtered[0].id;
      list.style.display = 'none';
    } else {
      alert('⚠️ No matching location found. Please try again.');
    }
  }
});

document.addEventListener('click', e => {
  if (!e.target.closest('.dropdown-container')) list.style.display = 'none';
});
</script>

<?php include 'partials/footer.php'; ?>
