<?php
// test_db.php
include '../db.php';
if ($conn) {
    echo "Database connection successful!";
    // Test if tables exist
    $tables = ['applicants', 'applicant_registration_details', 'addresses', 'economic_status', 'health_condition'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT 1 FROM $table LIMIT 1");
            echo "<br>Table '$table' exists";
        } catch (Exception $e) {
            echo "<br>Table '$table' NOT found: " . $e->getMessage();
        }
    }
} else {
    echo "Database connection failed!";
}
?>