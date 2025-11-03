<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ✅ Fetch profile
$user_id = $_SESSION['user_id'];
list($p_status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
$profile = ($p_status === 200 && !empty($profile_data)) ? $profile_data[0] : null;

// ✅ Fetch locations
list($loc_status, $location_data) = supabase_request('location?select=id,locations', 'GET');
$locations = ($loc_status === 200 && !empty($location_data)) ? $location_data : [];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile) {
    $report_date = trim($_POST['report_date']);
    $activity    = trim($_POST['activity']);
    $notes       = trim($_POST['notes']);
    $location_id = (int) $_POST['location_id'];

    // ✅ Handle multiple photo uploads
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

    // ✅ Insert into Supabase
    $data = [[
        'user_id'     => $profile['id'],
        'report_date' => $report_date,
        'activity'    => $activity,
        'notes'       => $notes,
        'photo_url'   => $photo_json,
        'location_id' => $location_id
    ]];

    list($status, $response) = supabase_request('daily_reports', 'POST', $data);
    $message = ($status === 201)
        ? "✅ Report submitted successfully!"
        : "❌ Failed to submit report (HTTP $status)";
}

// ✅ Page info
$page_title = 'Add Report';
$page_heading = 'Submit Daily Report';
include 'partials/header.php';
?>

<style>
/* --- Form Styling (Dark Theme) --- */
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

h2 {
  color: #cfd8f3;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(60,90,140,0.4);
  padding-bottom: 6px;
}

.profile-card {
  background: linear-gradient(160deg, rgba(20,30,55,0.95), rgba(14,24,45,0.9));
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  border-left: 4px solid #2d75ff;
  color: #d7e2fb;
  box-shadow: 0 0 8px rgba(45,117,255,0.15);
}

label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
  color: #9bb3e6;
}

input[type="date"],
textarea,
input[type="text"],
input[type="file"] {
  width: 100%;
  padding: 10px;
  background: #0f1a2c;
  border: 1px solid #23385f;
  border-radius: 6px;
  font-size: 15px;
  margin-bottom: 12px;
  box-sizing: border-box;
  color: #e4e9f2;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

textarea {
  resize: vertical;
  height: 90px;
}

input:focus,
textarea:focus,
input[type="file"]:focus {
  border-color: #4aa8ff;
  outline: none;
  box-shadow: 0 0 0 2px rgba(74,168,255,0.25);
}

/* --- Buttons --- */
button {
  background: linear-gradient(90deg, #2d75ff, #4aa8ff);
  color: white;
  border: none;
  padding: 12px 18px;
  font-size: 16px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.25s ease;
  width: 100%;
  font-weight: 600;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

button:hover {
  filter: brightness(1.15);
  transform: translateY(-2px);
}

/* --- Helper Text --- */
.helper-text {
  font-size: 13px;
  color: #8ca0c8;
  margin-top: -8px;
  margin-bottom: 10px;
}

/* --- Section Title --- */
.section-title {
  font-weight: 700;
  color: #9bb3e6;
  margin-top: 25px;
  border-bottom: 1px solid rgba(60,90,140,0.4);
  padding-bottom: 5px;
}

/* --- Searchable Dropdown --- */
.dropdown-container {
  position: relative;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #121d31;
  border: 1px solid #23385f;
  border-radius: 6px;
  max-height: 160px;
  overflow-y: auto;
  display: none;
  z-index: 5;
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

.dropdown-item {
  padding: 8px 10px;
  cursor: pointer;
  color: #e4e9f2;
  transition: background 0.2s ease;
}

.dropdown-item:hover {
  background: rgba(45,117,255,0.2);
}

/* --- Responsive --- */
@media (max-width: 768px) {
  .form-container {
    margin: 20px;
    padding: 20px;
  }
}

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
    <div class="profile-card">
        <p><strong><?= htmlspecialchars($profile['name']) ?></strong></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($profile['department']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($profile['role']) ?></p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="section-title">Report Details</div>

        <label for="report_date">Report Date</label>
        <input type="date" name="report_date" id="report_date" required>
        <p class="helper-text">Choose the date of your report.</p>

        <label for="activity">Activity</label>
        <textarea name="activity" id="activity" placeholder="Describe your tasks or accomplishments..." required></textarea>

        <label for="notes">Notes (Optional)</label>
        <textarea name="notes" id="notes" placeholder="Additional remarks or observations..."></textarea>

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
        <p class="helper-text">Attach images related to your work.</p>

        <button type="submit">Submit Report</button>
    </form>

<?php else: ?>
    <p>Profile not found for this user.</p>
<?php endif; ?>
</div>

<script>
// ✅ Searchable dropdown
const locations = <?= json_encode($locations) ?>;
const input = document.getElementById('locationSearch');
const list = document.getElementById('dropdownList');
const hiddenInput = document.getElementById('locationId');

input.addEventListener('input', () => {
    const query = input.value.toLowerCase();
    list.innerHTML = '';
    const filtered = locations.filter(l => l.locations.toLowerCase().includes(query));

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
        list.style.display = 'none';
    }
});

document.addEventListener('click', e => {
    if (!e.target.closest('.dropdown-container')) {
        list.style.display = 'none';
    }
});
</script>

<?php include 'partials/footer.php'; ?>
