<?php
// test_staff_fixed.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session data directly without starting session with invalid ID
$_SESSION = [
    'user_id' => 67,
    'username' => 'Staff',
    'fullname' => 'Marianne Ygay',
    'firstname' => 'Marianne',
    'lastname' => 'Ygay',
    'user_type' => 'Staff'
];

// Manually set session status
Session_status() === PHP_SESSION_ACTIVE || session_start();

include '../db.php';

echo "<h1>Staff Logger Test (Fixed)</h1>";

if (!$conn) {
    echo "<p style='color:red'>Database connection failed!</p>";
    exit;
}
echo "<p style='color:green'>Database connected successfully</p>";

$activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
    
    if (class_exists('ActivityLogger')) {
        $logger = new ActivityLogger($conn);
        
        // Test logging as Staff
        $result = $logger->log('STAFF_TEST', 'Testing logger for Staff user', [
            'user_id' => 67,
            'user_type' => 'Staff',
            'test' => 'staff_logging_fixed'
        ]);
        
        if ($result) {
            echo "<p style='color:green'>Staff log inserted successfully!</p>";
            
            // Check last log
            $stmt = $conn->prepare("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Last log in database:</h3>";
            echo "<pre>" . print_r($lastLog, true) . "</pre>";
        } else {
            echo "<p style='color:red'>Failed to insert log</p>";
        }
    }
} else {
    echo "<p style='color:red'>ActivityLogger not found</p>";
}

// Test direct insertion
echo "<hr><h2>Direct Insertion Test</h2>";
try {
    $query = "INSERT INTO activity_logs 
             (user_id, activity_type, description, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        67, // Staff user ID
        'DIRECT_STAFF_TEST',
        'Direct test for Staff user 67',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Test Agent'
    ]);
    
    if ($result) {
        echo "<p style='color:green'>Direct insertion successful for user ID 67</p>";
        echo "<p>Insert ID: " . $conn->lastInsertId() . "</p>";
    } else {
        echo "<p style='color:red'>Direct insertion failed</p>";
        echo "<pre>Error: " . print_r($stmt->errorInfo(), true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Direct insertion error: " . $e->getMessage() . "</p>";
}
?>