<?php
// run_age_update.php - One-click age update
session_start();
require_once "../login/admin_header.php";

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    die("Access denied. Admin only.");
}

// Include the cron script
include 'cron_update_ages.php';

// Read the log file and display
$logFile = __DIR__ . '/logs/age_update.log';
if (file_exists($logFile)) {
    echo "<h3>Age Update Results</h3>";
    echo "<pre>" . file_get_contents($logFile) . "</pre>";
} else {
    echo "<p>No log file found. Check permissions.</p>";
}

echo "<p><a href='test_age_update.php'>Back to Test Page</a></p>";
echo "<p><a href='register.php'>Back to Registration</a></p>";