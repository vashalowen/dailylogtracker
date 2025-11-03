<?php
require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing report ID']);
    exit;
}

$id = htmlspecialchars($_GET['id']);
$select = 'id,report_date,activity,notes,photo_url,created_at,location_id,' .
          'profiles(name,department,position,role),' .
          'location:location_id(locations,address,email)';

// Query Supabase for the report
list($status, $data) = supabase_request("daily_reports?select=$select&id=eq.$id", 'GET');

if ($status === 200 && !empty($data)) {
    echo json_encode($data[0]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
}
