<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

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
        CURLOPT_HTTPHEADER => $headers
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($status === 200 && isset($data['user']['id'])) {
        $_SESSION['user_id'] = $data['user']['id'];
        $_SESSION['user_email'] = $data['user']['email'];
        $_SESSION['access_token'] = $data['access_token'];

  header('Location: dashboard.php');
  exit;
    } else {
        $message = "❌ Login failed: Invalid email or password";
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
/* === Global Styles === */
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

/* === Login Card === */
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

/* === Input Fields === */
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

/* === Button === */
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

/* === Footer Links === */
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

/* === Responsive === */
@media (max-width: 480px) {
  .login-container {
    padding: 25px 20px;
    border-radius: 12px;
  }
  h2 {
    font-size: 20px;
  }
  input, button {
    font-size: 14px;
  }
}
</style>
</head>
<body>

<div class="login-container">
  <h2>REPHIL Daily Log Tracker</h2>
  <p>Sign in to access your account</p>
  <form method="POST">
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
  <div class="login-footer">
    <p>Need help? <a href="mailto:support@rephil.com.ph">Contact Admin</a></p>
  </div>
</div>

</body>
</html>



