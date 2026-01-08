<?php
require_once "../../php/helpers/sms_helper.php";

echo "<h2>Quick SMS Test</h2>";

// Direct test without class issues
$apiKey = '11203b3c9a4bc430dd3a1b181ece8b6c';
$testNumber = '09272873751';
$testMessage = 'Quick test from MSWD';

echo "<p>Testing direct API call...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'apikey' => $apiKey,
    'number' => '639272873751',
    'message' => $testMessage,
    'sender_name' => 'SEMAPHORE'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

if ($httpCode == 200) {
    echo "<h3 style='color: green;'>✅ Direct API call works!</h3>";

    // Now test with SMSGateway class
    echo "<h3>Testing with SMSGateway class...</h3>";

    $settings = [
        'api_key' => $apiKey,
        'sender_id' => 'SEMAPHORE',
        'is_active' => true,
        'demo_mode' => false
    ];

    // Create a simple test class
    class SimpleSMS
    {
        public function send($number, $message)
        {
            global $apiKey;

            $postData = [
                'apikey' => $apiKey,
                'number' => '639' . substr(preg_replace('/\D/', '', $number), 1),
                'message' => $message,
                'sender_name' => 'SEMAPHORE'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ['http_code' => $httpCode, 'response' => $response];
        }
    }

    $simple = new SimpleSMS();
    $result = $simple->send($testNumber, 'Test via Simple class');

    echo "HTTP Code: {$result['http_code']}<br>";
    echo "Response: <pre>" . htmlspecialchars($result['response']) . "</pre><br>";

    if ($result['http_code'] == 200) {
        echo "<h3 style='color: green;'>✅ Simple class works!</h3>";
    }

    // Check if main class exists
    if (class_exists('SMSGateway')) {
        echo "<h3>SMSGateway class exists ✓</h3>";

        // Check method exists
        $methods = ['send', 'sendBulk', 'validatePhoneNumber'];
        foreach ($methods as $method) {
            if (method_exists('SMSGateway', $method)) {
                echo "<p>$method() method exists ✓</p>";
            } else {
                echo "<p style='color: red;'>$method() method missing ✗</p>";
            }
        }
    } else {
        echo "<h3 style='color: red;'>SMSGateway class doesn't exist!</h3>";
    }
} else {
    echo "<h3 style='color: red;'>❌ Direct API call failed</h3>";
}
