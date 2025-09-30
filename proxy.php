<?php

// Tiyakin ang Philippine Timezone
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    echo json_encode(['error' => 'No token provided.']);
    exit;
}

// Prepare the Facebook Graph API URL
$url = "https://graph.facebook.com/me?fields=id,email&access_token=" . urlencode($token);

// Use cURL to make the server-side request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// NEW LOGIC: I-extract ang specific error message mula sa Facebook response
$error_message = 'N/A';
if (isset($data['error']) && isset($data['error']['message'])) {
    // Kinukuha ang message mula sa 'error' array (ito ang error na lumalabas sa Facebook Debugger)
    $error_message = $data['error']['message'];
} elseif ($http_code !== 200 && !$data) {
    // Kung may HTTP error at walang ma-decode na JSON (e.g., cURL error or blank response)
    $error_message = 'cURL/Connection Error or Invalid API Response';
}


// Create a default log entry (para sa results.txt - failures)
$log_entry = [
    // Ginamit ang 'h:i:s A' para sa 12-hour format (e.g., 01:22:07 PM)
    'timestamp' => date('Y-m-d h:i:s A'),
    'token' => $token,
    'http_code' => $http_code,
    'error_message' => $error_message, // IDINAGDAG ANG ERROR MESSAGE DITO
    'full_response' => $data // Naglalaman ng kumpletong JSON response mula sa FB
];

// --- LOGGING LOGIC ---

$log_file = 'results.txt'; // For all checks/failures
$success_file = 'success.txt'; // For successful tokens

// Check for success condition: HTTP code is NOT 400 AND no 'error' key in response data
if ($http_code !== 400 && !isset($data['error'])) {

    // **CHECK FOR DUPLICATE TOKEN**
    $is_duplicate = false;
    if (file_exists($success_file)) {
        // Simple check: basahin ang buong file, at hanapin ang token.
        $success_tokens_content = file_get_contents($success_file);
        if (strpos($success_tokens_content, $token) !== false) {
            $is_duplicate = true;
        }
    }

    if (!$is_duplicate) {
        // Log the success entry (token lang, walang JSON encoding)
        file_put_contents($success_file, $token . "\n", FILE_APPEND);
    }
    // **END DUPLICATE CHECK**

} else {
    // Kung HINDI success (default/failure), i-log ang full JSON sa results.txt
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
}

// --- END LOGGING LOGIC ---

// Return the result to the client-side script
echo json_encode($data);

?>