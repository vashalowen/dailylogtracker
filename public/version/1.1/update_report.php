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
$input = json_decode(file_get_contents('php://input'), true);

// Ensure there’s something to update
if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty update data']);
    exit;
}

// Clean allowed fields (avoid accidental key overwrite)
$allowed = ['report_date', 'activity', 'notes', 'location'];
$updateData = array_intersect_key($input, array_flip($allowed));

// If location is text, you may resolve it to location_id before updating
if (isset($updateData['location'])) {
    $locName = trim($updateData['location']);
    if ($locName !== '') {
        list($locStatus, $locData) = supabase_request('location?select=id&locations=eq.' . urlencode($locName), 'GET');
        if ($locStatus === 200 && !empty($locData)) {
            $updateData['location_id'] = $locData[0]['id'];
        }
    }
    unset($updateData['location']);
}

// Execute PATCH update
list($status, $response) = supabase_request("daily_reports?id=eq.$id", 'PATCH', [$updateData]);

if ($status === 200) {
    echo json_encode(['success' => true, 'message' => 'Report updated successfully']);
} else {
    http_response_code($status);
    echo json_encode(['error' => 'Failed to update report', 'status' => $status]);
}
