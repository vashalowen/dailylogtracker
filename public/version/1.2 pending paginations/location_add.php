<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locations = trim($_POST['locations']);
    $address   = trim($_POST['address']);
    $email     = trim($_POST['email']);

    $data = [[
        'locations' => $locations,
        'address'   => $address,
        'email'     => $email
    ]];

    list($status, $response) = supabase_request('location', 'POST', $data);
    $message = ($status === 201) ? '✅ Location added successfully!' : "❌ Failed (HTTP $status)";
}

$page_title = 'Location Add';
$page_heading = 'Location Add';
include 'partials/header.php';

?>

<?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>

<form method="POST">
  <label>Location Name:</label><br>
  <input type="text" name="locations" required><br><br>

  <label>Address:</label><br>
  <input type="text" name="address"><br><br>

  <label>Email:</label><br>
  <input type="email" name="email"><br><br>

  <button type="submit">💾 Save</button>
</form>
</body>
</html>
