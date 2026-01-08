<?php
// Complete fix for SMS system
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Complete SMS System Fix</h2>";

// 1. Update sms_settings table structure and data
$queries = [
    // Update table structure
    "ALTER TABLE sms_settings MODIFY COLUMN sender_id VARCHAR(50) DEFAULT 'Semaphore'",
    "ALTER TABLE sms_settings MODIFY COLUMN demo_mode TINYINT(1) DEFAULT 0",
    
    // Update existing data
    "UPDATE sms_settings SET sender_id = 'Semaphore', demo_mode = 0, updated_at = NOW()",
    
    // Update createRequiredTables function defaults
    "DROP TABLE IF EXISTS sms_settings_backup",
    "CREATE TABLE sms_settings_backup AS SELECT * FROM sms_settings",
    
    // Recreate table with correct defaults
    "DROP TABLE IF EXISTS sms_settings",
    "CREATE TABLE sms_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(50) DEFAULT 'semaphore',
        api_key VARCHAR(255) DEFAULT '11203b3c9a4bc430dd3a1b181ece8b6c',
        sender_id VARCHAR(50) DEFAULT 'Semaphore',
        is_active TINYINT(1) DEFAULT 1,
        demo_mode TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Restore data
    "INSERT INTO sms_settings (provider, api_key, sender_id, is_active, demo_mode) 
     SELECT provider, api_key, 'Semaphore' as sender_id, is_active, 0 as demo_mode 
     FROM sms_settings_backup"
];

foreach ($queries as $query) {
    echo "<p>Executing: " . substr($query, 0, 50) . "...</p>";
    if ($conn->query($query)) {
        echo "<p style='color: green;'>✅ Success</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
    echo "<hr>";
}

// 2. Test the settings
echo "<h3>Current SMS Settings:</h3>";
$result = $conn->query("SELECT * FROM sms_settings");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    if ($row['sender_id'] === 'Semaphore' && $row['demo_mode'] == 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Settings are correctly configured!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Settings still need adjustment</p>";
    }
}

$conn->close();

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Refresh your SMS settings page</li>";
echo "<li>Verify Sender Name shows 'Semaphore'</li>";
echo "<li>Make sure Demo Mode is UNCHECKED</li>";
echo "<li>Test sending an SMS</li>";
echo "</ol>";

// Also provide direct test
echo "<hr>";
echo "<h3>Direct Test:</h3>";
echo '<a href="sms.php?test=1" target="_blank">Click here to test SMS functionality</a>';
?>