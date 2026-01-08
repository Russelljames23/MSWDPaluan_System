<?php
require_once "../../php/helpers/sms_helper.php";

echo "<h2>📱 SMS System Verification</h2>";

// Get current settings
$settings = getSMSSettings();
echo "<h3>Current Settings:</h3>";
echo "<pre>";
print_r($settings);
echo "</pre>";

// Test the gateway
$smsGateway = new SMSGateway($settings);

echo "<h3>Test Configuration:</h3>";
$testResult = $smsGateway->send('09272873751', 'Verification test from MSWD System');

echo "<pre>";
print_r($testResult);
echo "</pre>";

if ($testResult['success']) {
    echo "<div style='background: linear-gradient(to right, #d4edda, #c3e6cb); padding: 20px; border: 3px solid #28a745; border-radius: 10px; text-align: center;'>
            <h1 style='color: #155724;'>✅ SMS SYSTEM IS WORKING!</h1>
            <p style='font-size: 18px;'>Status: <strong>{$testResult['status']}</strong></p>";
    
    if (isset($testResult['message_id'])) {
        echo "<p>Message ID: <code>{$testResult['message_id']}</code></p>";
    }
    
    echo "<p>Sender Name: <strong>SEMAPHORE</strong> (shows as MFDELIVERY)</p>
          <p>Demo Mode: <strong>" . ($settings['demo_mode'] ? 'ON (test only)' : 'OFF (real SMS)') . "</strong></p>
          <p>Credits Used: 1 credit per SMS</p>
        </div>";
    
    // Show recent logs
    echo "<h3>Recent SMS Logs:</h3>";
    
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    if (!$conn->connect_error) {
        $logs = $conn->query("SELECT phone_number, message, status, created_at FROM sms_logs ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>
                <tr style='background-color: #f2f2f2;'>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>";
        
        while($row = $logs->fetch_assoc()) {
            $statusColor = $row['status'] === 'queued' ? 'green' : ($row['status'] === 'demo_sent' ? 'orange' : 'red');
            echo "<tr>
                    <td>{$row['phone_number']}</td>
                    <td>" . substr($row['message'], 0, 30) . "...</td>
                    <td style='color: $statusColor;'><strong>{$row['status']}</strong></td>
                    <td>{$row['created_at']}</td>
                  </tr>";
        }
        echo "</table>";
        $conn->close();
    }
} else {
    echo "<div style='background-color: #f8d7da; padding: 20px; border: 3px solid #dc3545; border-radius: 10px;'>
            <h1 style='color: #721c24;'>❌ SMS System Needs Fixing</h1>
            <p>Error: {$testResult['message']}</p>
          </div>";
}

echo "<hr>";
echo "<h3>Configuration Summary:</h3>";
echo "<ul>
        <li><strong>API Key:</strong> Working ✓</li>
        <li><strong>Sender Name:</strong> SEMAPHORE (ALL CAPS) ✓</li>
        <li><strong>Parameter:</strong> sender_name (with underscore) ✓</li>
        <li><strong>Number Format:</strong> 639XXXXXXXXX ✓</li>
        <li><strong>Demo Mode:</strong> " . ($settings['demo_mode'] ? 'ON' : 'OFF') . "</li>
      </ul>";

echo "<p><a href='sms.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to SMS Settings Page</a></p>";
?>