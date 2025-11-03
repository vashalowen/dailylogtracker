<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$report_id = $_GET['id'] ?? null;
if (!$report_id) die('Missing report ID.');

// ✅ Fetch report
list($status, $report_data) = supabase_request('daily_reports?id=eq.' . $report_id, 'GET');
$report = ($status === 200 && !empty($report_data)) ? $report_data[0] : null;
if (!$report) die('Report not found.');

// ✅ Fetch all locations for dropdown
list($loc_status, $loc_data) = supabase_request('location?select=id,locations', 'GET');
$locations = ($loc_status === 200 && !empty($loc_data)) ? $loc_data : [];

$message = '';

// ✅ Decode existing photos
$existing_photos = [];
if (!empty($report['photo_url'])) {
    $decoded = json_decode($report['photo_url'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $existing_photos = $decoded;
    } elseif (is_string($report['photo_url'])) {
        $existing_photos = [$report['photo_url']];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = trim($_POST['report_date']);
    $activity    = trim($_POST['activity']);
    $location_id = (int) $_POST['location_id'];

    $photo_urls = $existing_photos;

    // ✅ Handle new uploads
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

    // ✅ Remove selected old photos
    if (!empty($_POST['remove_photos'])) {
        foreach ($_POST['remove_photos'] as $to_remove) {
            $photo_urls = array_filter($photo_urls, fn($p) => $p !== $to_remove);
        }
    }

    $photo_json = json_encode(array_values($photo_urls));

    // ✅ Update record in Supabase
    $update = [
        'report_date' => $report_date,
        'activity'    => $activity,
        'photo_url'   => $photo_json,
        'location_id' => $location_id
    ];

    list($status, $resp) = supabase_request('daily_reports?id=eq.' . $report_id, 'PATCH', $update);

    if ($status === 204) {
        $message = "✅ Report updated successfully!";
        list($_, $new_data) = supabase_request('daily_reports?id=eq.' . $report_id, 'GET');
        $report = $new_data[0];
        $existing_photos = json_decode($report['photo_url'], true) ?? [];
    } else {
        $message = "❌ Update failed (HTTP $status)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Report</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .photo-container img { width: 100px; height: auto; margin-right: 5px; border-radius: 4px; }
    .remove-label { font-size: 13px; color: #c00; }
</style>
</head>
<body>
<h2>Edit Report #<?= htmlspecialchars($report['id']) ?></h2>
<a href="view_reports.php">⬅ Back to Reports</a>
<hr>

<?php if ($message): ?>
<p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Report Date:</label><br>
    <input type="date" name="report_date" value="<?= htmlspecialchars($report['report_date']) ?>" required><br><br>

    <label>Activity:</label><br>
    <textarea name="activity" rows="4" cols="50" required><?= htmlspecialchars($report['activity']) ?></textarea><br><br>

    <label>Location:</label><br>
    <select name="location_id" required>
        <option value="">-- Select Location --</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc['id']) ?>"
                <?= ($report['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc['locations']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Existing Photos:</label><br>
    <div class="photo-container">
        <?php if (!empty($existing_photos)): ?>
            <?php foreach ($existing_photos as $p): ?>
                <div style="display:inline-block; text-align:center; margin-right:10px;">
                    <img src="../<?= htmlspecialchars($p) ?>" alt="photo"><br>
                    <label class="remove-label">
                        <input type="checkbox" name="remove_photos[]" value="<?= htmlspecialchars($p) ?>"> Remove
                    </label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No photos attached.</p>
        <?php endif; ?>
    </div>
    <br>

    <label>Add More Photos:</label><br>
    <input type="file" name="photos[]" accept="image/*" multiple><br><br>

    <button type="submit">💾 Save Changes</button>
</form>
</body>
</html>
