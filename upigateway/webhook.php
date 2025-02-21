<?php


$logFile = "webhook_log.txt";

// Function to log data
function logData($message) {
    global $logFile;
    $logEntry = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
// Get raw POST data
$incomingData = file_get_contents("php://input");

// Parse the URL-encoded data
parse_str($incomingData, $data);

// Check if the payment was successful
if (isset($data['status']) && $data['status'] === "success") {
    
    // Extract necessary data
    $reference_id = isset($data['client_txn_id']) ? $data['client_txn_id'] : null;

    if ($reference_id) {
        $purposeParts = explode('-', $reference_id);
        $user_id = isset($purposeParts[0]) ? $purposeParts[0] : null;
        $coins_id = isset($purposeParts[1]) ? $purposeParts[1] : null;

        // API endpoint
        $apiUrl = 'https://hidude.in/api/auth/add_coins';

        // Prepare form data
        $formData = [
            'user_id' => $user_id,
            'coins_id' => $coins_id
        ];

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));

        // Execute the request and get the response
        $apiResponse = curl_exec($ch);
        curl_close($ch);

        echo "Payment received. Reference ID: " . $reference_id;
    } else {
        echo "Reference ID missing.";
    }
} else {
    echo "Payment not successful.";
}

?>
