<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die('Missing location ID.');

list($status, $resp) = supabase_request('location?id=eq.' . $id, 'DELETE');

if ($status === 204) {
    header('Location: location_view.php?msg=deleted');
    exit;
} else {
    die("❌ Delete failed (HTTP $status)");
}
