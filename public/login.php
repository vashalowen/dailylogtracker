<?php
// --- Secure session setup ---

session_start();


require_once __DIR__ . '/../config/supabase.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Sanitize input ---
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $message = "⚠️ Please enter both email and password.";
    } else {
        // --- Build Supabase Auth request ---
        $url = SUPABASE_URL . '/auth/v1/token?grant_type=password';
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
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $message = "⚠️ Network error: " . htmlspecialchars($curl_error);
        } else {
            $data = json_decode($response, true);
            if ($status === 200 && isset($data['user']['id'])) {
                // --- Success ---
                session_regenerate_id(true);
                $_SESSION['user_id'] = $data['user']['id'];
                $_SESSION['user_email'] = $data['user']['email'];
                $_SESSION['access_token'] = $data['access_token'];
                header('Location: dashboard.php');
                exit;
            } else {
                // --- Failure ---
                $msg = $data['error_description'] ?? $data['msg'] ?? '';
                $message = "❌ Login failed: Invalid email or password. $msg";
                sleep(1); // slow brute-force attempts
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | REPHIL Daily Log Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: linear-gradient(145deg, #0b1220, #1a2338);
  color: #e4e9f2;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}
.login-container {
  background: rgba(18, 28, 49, 0.95);
  border: 1px solid rgba(80, 120, 200, 0.3);
  box-shadow: 0 6px 20px rgba(0,0,0,0.4);
  border-radius: 14px;
  padding: 35px 30px;
  width: 90%;
  max-width: 400px;
  text-align: center;
  backdrop-filter: blur(8px);
  animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.login-container h2 {
  color: #e8efff;
  margin-bottom: 8px;
  font-size: 24px;
}
.login-container p {
  color: #9bb3e6;
  margin-bottom: 25px;
  font-size: 14px;
}
input[type="email"],
input[type="password"] {
  width: 90%;
  padding: 12px 14px;
  margin-bottom: 15px;
  background: #0f1a2c;
  border: 1px solid #24365c;
  border-radius: 8px;
  color: #e4e9f2;
  font-size: 15px;
  transition: all 0.2s ease;
}
input:focus {
  outline: none;
  border-color: #4aa8ff;
  box-shadow: 0 0 0 2px rgba(74,168,255,0.3);
}
button {
  width: 100%;
  background: linear-gradient(90deg, #2d75ff, #4aa8ff);
  border: none;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s ease, filter 0.2s ease;
}
button:hover {
  transform: translateY(-2px);
  filter: brightness(1.1);
}
.login-footer {
  margin-top: 20px;
  font-size: 13px;
  color: #9bb3e6;
}
.login-footer a {
  color: #4aa8ff;
  text-decoration: none;
}
.login-footer a:hover {
  text-decoration: underline;
}
.message {
  color: #ff6b6b;
  background: rgba(255, 0, 0, 0.1);
  padding: 8px;
  border-radius: 6px;
  margin-bottom: 12px;
  font-size: 14px;
}
@media (max-width: 480px) {
  .login-container {
    padding: 25px 20px;
    border-radius: 12px;
  }
  h2 { font-size: 20px; }
  input, button { font-size: 14px; }
}
</style>
</head>
<body>

<div class="login-container">
  <h2>REPHIL Daily Log Tracker</h2>
  <p>Sign in to access your account</p>

  <?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>

  <div class="login-footer">
    <p>Rephil Log Tracker @ 2025-2026</a></p>
  </div>
</div>

</body>
</html>
