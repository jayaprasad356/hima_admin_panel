<?php

// Define log file path
$logFile = "webhook_log.txt";

// Function to log data
function logData($message) {
    global $logFile;
    $logEntry = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Get raw POST data
$incomingData = file_get_contents("php://input");

// Log raw webhook data
logData("Raw Data: " . $incomingData);

// Parse the URL-encoded data
parse_str($incomingData, $data);

// Log parsed data
logData("Parsed Data: " . json_encode($data));

// Check if the payment was successful
if (isset($data['status']) && $data['status'] === "success") {
    
    // Extract reference ID
    $reference_id = isset($data['client_txn_id']) ? $data['client_txn_id'] : null;

    if ($reference_id) {
        // Split reference_id based on '-'
        $purposeParts = explode('-', $reference_id);

        // Ensure we have at least 2 parts
        $user_id = isset($purposeParts[0]) ? $purposeParts[0] : null;
        $coins_id = isset($purposeParts[1]) ? $purposeParts[1] : null;
        $app_id = isset($purposeParts[2]) ? $purposeParts[2] : null;
        if($app_id == 'HM') {
            $apiUrl = 'https://himaapp.in/api/auth/add_coins';
        }else{
            $apiUrl = 'https://hidude.in/api/auth/add_coins';
        }


        // Log extracted details
        logData("Extracted user_id: $user_id, coins_id: $coins_id");

        // API endpoint
        //$apiUrl = 'https://himaapp.in/api/auth/add_coins';

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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log API response
        logData("API Response: HTTP Code $httpCode, Response: $apiResponse");

        echo "Payment received. Reference ID: " . $reference_id;
    } else {
        logData("Error: Reference ID missing.");
        echo "Reference ID missing.";
    }
} else {
    logData("Error: Payment not successful.");
    echo "Payment not successful.";
}

?>
