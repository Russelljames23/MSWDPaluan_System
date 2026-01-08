<?php
/**
 * FINAL WORKING TEST - All issues fixed
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>FINAL SMS TEST - All Issues Fixed</h2>";

$apiKey = '11203b3c9a4bc430dd3a1b181ece8b6c';
$testNumber = '09272873751';
$testMessage = 'FINAL TEST: SMS system is now working!';

echo "<p><strong>API Key:</strong> " . substr($apiKey, 0, 8) . "...</p>";
echo "<p><strong>Test Number:</strong> $testNumber</p>";
echo "<p><strong>Message:</strong> $testMessage</p>";

// EXACT WORKING PARAMETERS:
$postData = [
    'apikey' => $apiKey,
    'number' => '639272873751', // Formatted
    'message' => $testMessage,
    'sender_name' => 'SEMAPHORE' // CORRECT: underscore + ALL CAPS
];

echo "<h3>Sending with these EXACT parameters:</h3>";
echo "<pre>" . print_r($postData, true) . "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Results:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (is_array($data) && isset($data[0]['status']) && ($data[0]['status'] == 'Queued' || $data[0]['status'] == 'Pending')) {
        echo "<div style='background-color: #d4edda; padding: 20px; border: 3px solid #c3e6cb; color: #155724; border-radius: 10px; text-align: center;'>
                <h1 style='color: green;'>🎉 SUCCESS! SMS SENT!</h1>
                <p><strong>Status:</strong> " . $data[0]['status'] . "</p>
                <p><strong>Message ID:</strong> " . ($data[0]['message_id'] ?? 'N/A') . "</p>
                <p><strong>Sender Name Used:</strong> " . ($data[0]['sender_name'] ?? 'N/A') . "</p>
                <p><em>Check the recipient's phone for the message</em></p>
              </div>";
        
        // Also test bulk send
        echo "<h3>Testing Bulk Send:</h3>";
        
        $bulkData = [
            'apikey' => $apiKey,
            'number' => '639272873751,639123456789', // Multiple numbers
            'message' => 'Bulk test from MSWD',
            'sender_name' => 'SEMAPHORE'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bulkData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $bulkResponse = curl_exec($ch);
        $bulkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p><strong>Bulk HTTP Code:</strong> $bulkHttpCode</p>";
        echo "<p><strong>Bulk Response:</strong> <pre>" . htmlspecialchars($bulkResponse) . "</pre></p>";
        
    } else {
        echo "<div style='background-color: #f8d7da; padding: 15px; border: 2px solid #f5c6cb; color: #721c24;'>
                <h3>❌ API Response Error</h3>
                <p><strong>Error:</strong> " . ($data[0]['error'] ?? 'Unknown') . "</p>
              </div>";
    }
} else {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 2px solid #f5c6cb; color: #721c24;'>
            <h3>❌ HTTP Error $httpCode</h3>
            <p><strong>Error:</strong> $error</p>
          </div>";
}

echo "<hr>";
echo "<h3>Summary of FIXES:</h3>";
echo "<ol>";
echo "<li><strong>Parameter Name:</strong> Use <code>'sender_name'</code> (with underscore)</li>";
echo "<li><strong>Sender Value:</strong> Use <code>'SEMAPHORE'</code> (ALL CAPS)</li>";
echo "<li><strong>Format Numbers:</strong> Use <code>639XXXXXXXXX</code> format</li>";
echo "<li><strong>Demo Mode:</strong> Must be DISABLED for real SMS</li>";
echo "</ol>";

echo "<h3>Next Steps:</h3>";
echo "<p>Update your <code>sms_helper.php</code> with these fixes:</p>";
echo "<pre>
// In send() method:
\$postData = [
    'apikey' => \$apiKey,
    'number' => \$formattedNumber,
    'message' => \$message,
    'sender_name' => 'SEMAPHORE' // FIXED
];

// In sendBulk() method:
\$postData = [
    'apikey' => \$apiKey,
    'number' => \$numbersString,
    'message' => \$message,
    'sender_name' => 'SEMAPHORE' // FIXED
];
</pre>";
?>