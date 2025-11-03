<?php
require_once __DIR__ . '/../config/supabase.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email']);
    $password    = trim($_POST['password']);
    $name        = trim($_POST['name']);
    $department  = trim($_POST['department']);
    $position    = trim($_POST['position']);
    $role        = trim($_POST['role']);

    // 1️⃣ Create user in Supabase Auth
    $signup_url = SUPABASE_URL . '/auth/v1/signup';
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ];
    $payload = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $signup_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $signup_response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $signup_data = json_decode($signup_response, true);

    if (($status === 200 || $status === 201) && isset($signup_data['user']['id'])) {
        $user_id = $signup_data['user']['id'];

        // 2️⃣ Insert extra profile data
        $profile_data = [[
            'id' => $user_id,
            'name' => $name,
            'department' => $department,
            'position' => $position,
            'role' => $role,
            'email' => $email
        ]];

        list($p_status, $p_response) = supabase_request('profiles', 'POST', $profile_data);

        if ($p_status === 201) {
            $message = "✅ User and profile created successfully!";
        } else {
            $message = "⚠️ User created, but profile insert failed (HTTP $p_status)";
        }
    } else {
        $error = json_decode($signup_response, true);
        $msg = $error['msg'] ?? 'Signup failed';
        $message = "❌ Failed to create user (HTTP $status): $msg";
    }
}
$page_title = 'View Users';
$page_heading = 'Users List';
include 'partials/header.php';
?>

  <style>
    label { display: inline-block; width: 120px; margin-bottom: 5px; }
    input, select { width: 250px; margin-bottom: 10px; }
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
  width: 30%;
  font-weight: 600;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

button:hover {
  filter: brightness(1.15);
  transform: translateY(-2px);
}
	
  </style>

  <h2>Register New User</h2>
  <?php if ($message): ?>
      <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Name:</label>
    <input type="text" name="name" required><br>

    <label>Email:</label>
    <input type="email" name="email" required><br>

    <label>Password:</label>
    <input type="password" name="password" required minlength="6"><br>

    <label>Department:</label>
    <input type="text" name="department"><br>

    <label>Position:</label>
    <input type="text" name="position"><br>

    <label>Role:</label>
    <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="Admin">Admin</option>
        <option value="User">User</option>
    </select><br>

    <button type="submit">Create Account</button>
  </form>
</body>
</html>
