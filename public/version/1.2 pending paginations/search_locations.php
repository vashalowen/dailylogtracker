<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

if (!isset($_SESSION['user_id'])) exit;

$q = trim($_GET['q'] ?? '');

$endpoint = 'location?select=*';
if (!empty($q)) {
    $endpoint .= '&or=(locations.ilike.*' . urlencode($q) . '*,address.ilike.*' . urlencode($q) . '*,email.ilike.*' . urlencode($q) . '*)';
}

list($status, $locations) = supabase_request($endpoint, 'GET');
header('Content-Type: application/json');
echo json_encode($locations ?? []);
