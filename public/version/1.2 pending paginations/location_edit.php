<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die('Missing location ID.');

list($status, $data) = supabase_request('location?id=eq.' . $id, 'GET');
$loc = ($status === 200 && !empty($data)) ? $data[0] : null;
if (!$loc) die('Location not found.');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locations = trim($_POST['locations']);
    $address   = trim($_POST['address']);
    $email     = trim($_POST['email']);

    $update = [
        'locations' => $locations,
        'address'   => $address,
        'email'     => $email
    ];

    list($status, $resp) = supabase_request('location?id=eq.' . $id, 'PATCH', $update);
    $message = ($status === 204) ? '✅ Location updated successfully!' : "❌ Update failed (HTTP $status)";
}

$page_title = 'Location Edit';
$page_heading = 'Location Edit';
include 'partials/header.php';
?>

<?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>

<form method="POST">
  <label>Location Name:</label><br>
  <input type="text" name="locations" value="<?= htmlspecialchars($loc['locations']) ?>" required><br><br>

  <label>Address:</label><br>
  <input type="text" name="address" value="<?= htmlspecialchars($loc['address']) ?>"><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" value="<?= htmlspecialchars($loc['email']) ?>"><br><br>

  <button type="submit">💾 Save Changes</button>
</form>
</body>
</html>
