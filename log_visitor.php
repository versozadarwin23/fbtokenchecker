<?php
// Tiyakin ang Philippine Timezone
date_default_timezone_set('Asia/Manila');

// Tiyakin na ang request ay POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

header('Content-Type: application/json');

// Kunin ang data na ipinasa mula sa client-side (JSON body)
$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

// I-validate ang kinakailangang data
if (!isset($data['total_checked']) || !isset($data['success_count']) || !isset($data['session_duration_ms'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required data.']);
    exit;
}

// Function para i-convert ang milliseconds sa readable format
function format_duration($milliseconds) {
    $seconds = floor($milliseconds / 1000);
    $hours = floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;

    $parts = [];
    if ($hours > 0) $parts[] = "{$hours}h";
    if ($minutes > 0) $parts[] = "{$minutes}m";
    // I-display ang seconds, kahit 0, kung walang h at m
    if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}s"; 

    return implode(' ', $parts);
}

// Kunin ang IP Address (mas secure na paraan)
function get_ip_address() {
    // Check for proxy servers (e.g., Cloudflare, etc.)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ip_parts = explode(',', $ip);
        $ip = trim($ip_parts[0]);
    }
    // Check for shared internet/proxy
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Default: remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$ip_address = get_ip_address();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';
$total_checked = (int)$data['total_checked'];
$success_count = (int)$data['success_count'];
$duration_ms = (int)$data['session_duration_ms']; 
$formatted_duration = format_duration($duration_ms); // Convert sa H/m/s format

// Ginamit ang 'h:i:s A' para sa 12-hour format (e.g., 01:22:07 PM)
$timestamp = date('Y-m-d h:i:s A');

// Ihanda ang log entry
// Format: [Date Time AM/PM] IP: [IP] | UA: [User Agent] | Duration: [H m s] | Total Checked: [Count] | Success Count: [Count]
$log_entry = "[$timestamp] IP: $ip_address | UA: $user_agent | $formatted_duration | Total Checked: $total_checked | Success Count: $success_count\n";

// Log file name
$log_file = 'visitor.txt';

// I-log ang entry
file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// Magbigay ng success response
echo json_encode(['message' => 'Visitor data logged successfully.']);
?>