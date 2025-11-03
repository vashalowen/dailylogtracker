<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) exit;
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

// ✅ Fetch user role
list($p_status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
$role = ($p_status === 200 && !empty($profile_data)) ? ($profile_data[0]['role'] ?? 'User') : 'User';

// ✅ Correct joined structure (same as main view)
$select = 'id,report_date,activity,notes,photo_url,created_at,location_id,' .
          'profiles(name,department,position,role),' .
          'location:location_id(locations,address,email)';

// ✅ Role-based fetch (no filter yet)
if ($role === 'Admin') {
    $endpoint = 'daily_reports?select=' . $select;
} else {
    $endpoint = 'daily_reports?select=' . $select . '&user_id=eq.' . $user_id;
}

// ✅ Fetch all records
list($status, $reports) = supabase_request($endpoint, 'GET');
if ($status !== 200 || empty($reports)) {
    echo json_encode([]);
    exit;
}

// ✅ Manual case-insensitive search (covers nested fields)
if ($q !== '') {
    $q_lower = mb_strtolower($q);
    $reports = array_filter($reports, function($r) use ($q_lower) {
        $fields = [
            $r['activity'] ?? '',
            $r['notes'] ?? '',
            $r['report_date'] ?? '',
            $r['profiles']['name'] ?? '',
            $r['profiles']['department'] ?? '',
            $r['profiles']['position'] ?? '',
            $r['profiles']['role'] ?? '',
            $r['location']['locations'] ?? '',
            $r['location']['address'] ?? '',
            $r['location']['email'] ?? ''
        ];
        foreach ($fields as $f) {
            if (stripos($f, $q_lower) !== false) return true;
        }
        return false;
    });
}

// ✅ Output JSON
echo json_encode(array_values($reports));

