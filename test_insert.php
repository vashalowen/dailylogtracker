<?php
require_once __DIR__ . '/config/supabase.php';

// sample data
$data = [
    'user_id'     => 'c8f4b836-9c24-4c5f-9ef0-92cf7f38f80a',
    'report_date' => date('Y-m-d'),
    'activity'    => 'Connection test: inserted via PHP',
    'photo_url'   => null,
    'comments'    => null
];

list($status, $response) = supabase_request('daily_reports', 'POST', [$data]);

if ($status === 201) {
    echo "✅ Insert success:\n";
    print_r($response);
} else {
    echo "❌ Insert failed (HTTP $status):\n";
    print_r($response);
}


?>