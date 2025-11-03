<?php

// config/supabase.php

// --- Set these once ---
if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', 'https://ucfzebkqurzkbgvpytia.supabase.co');
}
if (!defined('SUPABASE_KEY')) {
    define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVjZnplYmtxdXJ6a2JndnB5dGlhIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTUzMjExMSwiZXhwIjoyMDc3MTA4MTExfQ.u1HgqfmsNyyiwFYZFwjQZAXBiH5xR5HrhounLkqcNAM'); // service key for dev
}

function supabase_request($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_URL . '/rest/v1/' . ltrim($endpoint, '/');

    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY // <- this part is critical
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, json_decode($response, true)];
}
