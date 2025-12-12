<?php
// test_fixed_logging.php
require_once '../db.php';
require_once 'ActivityLogger.php';

session_start();

// Set valid session user (use your actual admin user)
$_SESSION['user_id'] = 57; // Your actual admin user ID from database
$_SESSION['username'] = 'Admin';

$logger = new ActivityLogger($conn);

// Test logging
$success = $logger->log('TEST_FIX', 'Testing fixed activity logging system');

if ($success) {
    echo "SUCCESS: Activity logged properly!<br><br>";
    
    // Show the log
    $query = "SELECT al.*, u.username FROM activity_logs al 
              JOIN users u ON al.user_id = u.id 
              ORDER BY al.id DESC LIMIT 1";
    $stmt = $conn->query($query);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Latest log:<br>";
    echo "User: " . $log['username'] . " (ID: " . $log['user_id'] . ")<br>";
    echo "Activity: " . $log['activity_type'] . "<br>";
    echo "Description: " . $log['description'] . "<br>";
} else {
    echo "FAILED: Could not log activity";
}
?>