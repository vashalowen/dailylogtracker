<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$report_id = $_GET['id'] ?? null;
if (!$report_id) {
    die('Missing report ID.');
}

list($status, $response) = supabase_request('daily_reports?id=eq.' . $report_id, 'DELETE');

if ($status === 204) {
    header('Location: view_reports.php?msg=deleted');
    exit;
} else {
    die("Delete failed (HTTP $status)");
}
