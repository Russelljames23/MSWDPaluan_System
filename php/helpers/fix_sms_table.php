<?php
// Fix SMS settings table structure
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

echo "<h2>Fixing SMS Settings Table Structure</h2>";

// 1. Backup current data
echo "<h3>1. Backing up current data...</h3>";
$conn->query("CREATE TABLE IF NOT EXISTS sms_settings_backup_".date('Ymd_His')." AS SELECT * FROM sms_settings");

// 2. Extract the SMS-specific data
$result = $conn->query("SELECT id, provider, api_key, sender_id, is_active, demo_mode, created_at, updated_at FROM sms_settings WHERE id = 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "<p>Found existing SMS settings:</p>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    // 3. Drop the messed up table
    echo "<h3>2. Recreating table structure...</h3>";
    $conn->query("DROP TABLE IF EXISTS sms_settings");
    
    // 4. Create clean SMS-only table
    $createTable = "CREATE TABLE sms_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(50) DEFAULT 'semaphore',
        api_key VARCHAR(255),
        sender_id VARCHAR(50) DEFAULT 'Semaphore',
        is_active TINYINT(1) DEFAULT 1,
        demo_mode TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✅ Created clean SMS settings table</p>";
        
        // 5. Insert the SMS data back
        $insert = "INSERT INTO sms_settings (provider, api_key, sender_id, is_active, demo_mode) 
                   VALUES ('" . $row['provider'] . "', 
                           '" . $row['api_key'] . "', 
                           'Semaphore', 
                           " . $row['is_active'] . ", 
                           0)";
        
        if ($conn->query($insert)) {
            echo "<p style='color: green;'>✅ Restored SMS settings</p>";
            
            // 6. Verify
            $verify = $conn->query("SELECT * FROM sms_settings");
            if ($verify && $data = $verify->fetch_assoc()) {
                echo "<h3>3. Final Settings:</h3>";
                echo "<pre>";
                print_r($data);
                echo "</pre>";
                
                // Check for SMTP columns
                $describe = $conn->query("DESCRIBE sms_settings");
                $columns = [];
                while ($col = $describe->fetch_assoc()) {
                    $columns[] = $col['Field'];
                }
                
                echo "<h3>4. Table Columns:</h3>";
                echo "<ul>";
                foreach ($columns as $col) {
                    echo "<li>" . $col . "</li>";
                }
                echo "</ul>";
                
                if (in_array('smtp_host', $columns)) {
                    echo "<p style='color: red;'>❌ Still has SMTP columns!</p>";
                } else {
                    echo "<p style='color: green;'>✅ Clean SMS-only table structure!</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to restore settings: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No existing settings found</p>";
}

$conn->close();

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Run this test: <a href='test_sms_only.php' target='_blank'>Test SMS Only</a></li>";
echo "<li>Go to your SMS page: <a href='sms.php' target='_blank'>SMS Settings</a></li>";
echo "<li>Test sending an SMS</li>";
echo "</ol>";
?>