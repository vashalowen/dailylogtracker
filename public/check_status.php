<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$location_id = intval($_GET['location_id'] ?? 0);
$today = date('Y-m-d');

if ($location_id === 0) {
    echo json_encode(['error' => 'Missing location']);
    exit;
}

// Query today's record for this user + location
$endpoint = "daily_reports?user_id=eq.$user_id&location_id=eq.$location_id&report_date=eq.$today";
list($status, $data) = supabase_request($endpoint, 'GET');

if ($status === 200 && !empty($data)) {
    echo json_encode(['mode' => 'checkout']);
} else {
    echo json_encode(['mode' => 'checkin']);
}
