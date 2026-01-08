<?php

/**
 * Semaphore API Diagnostic Tool - FIXED VERSION
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test the API key directly
$apiKey = '11203b3c9a4bc430dd3a1b181ece8b6c';

echo "<h2>Semaphore API Diagnostic Test</h2>";
echo "<p>Testing API Key: <code>" . substr($apiKey, 0, 8) . "...</code></p>";
echo "<p>Your account has: <strong>₱872 credits</strong> and is <strong>Inactive</strong></p>";
echo "<p>Note: Account shows 'Inactive' but you have credits. This might affect sending.</p>";

// List of valid sender names to try
$validSenderNames = ['SEMAPHORE', 'GLOBE', 'SMART', 'NOTICE'];

foreach ($validSenderNames as $senderName) {
    echo "<h3>Testing with Sender Name: <code>$senderName</code></h3>";

    $testNumber = '09664750533';
    $testMessage = 'Test SMS from MSWD System Diagnostic';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $apiKey,
        'number' => '09664750533',
        'message' => $testMessage,
        'sendername' => $senderName
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "HTTP Code: $httpCode<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (is_array($data) && isset($data[0]['status']) && ($data[0]['status'] == 'Queued' || $data[0]['status'] == 'Pending')) {
            echo "<div style='background-color: #d4edda; padding: 10px; border: 1px solid #c3e6cb; color: #155724;'>
                    ✅ SUCCESS with sender name: <strong>$senderName</strong><br>
                    Status: " . $data[0]['status'] . "<br>
                    Message ID: " . ($data[0]['message_id'] ?? 'N/A') . "
                  </div>";
            break; // Stop testing if we found a working sender name
        } else {
            echo "<div style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; color: #856404;'>
                    ⚠️ Failed with sender name: <strong>$senderName</strong><br>
                    Error: " . ($data[0]['error'] ?? 'Unknown error') . "
                  </div>";
        }
    } else {
        echo "<div style='background-color: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; color: #721c24;'>
                ❌ HTTP Error $httpCode
              </div>";
    }
    echo "<hr>";
}

// Test account activation
echo "<h3>Account Status Information</h3>";
echo "<p>Your account shows 'Inactive' status but has 872 credits.</p>";
echo "<p>This might mean:</p>";
echo "<ul>
        <li>Account needs activation (check email from Semaphore)</li>
        <li>Account is on hold (contact Semaphore support)</li>
        <li>You need to verify your account</li>
      </ul>";
echo "<p>Contact Semaphore support at: support@semaphore.co</p>";
