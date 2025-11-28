<?php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'benefits_distribution'");
if ($tableCheck->num_rows == 0) {
    echo json_encode(['table_exists' => false, 'message' => 'Table does not exist']);
} else {
    // Show table structure
    $structure = $conn->query("DESCRIBE benefits_distribution");
    $columns = [];
    while ($row = $structure->fetch_assoc()) {
        $columns[] = $row;
    }
    echo json_encode(['table_exists' => true, 'columns' => $columns]);
}

$conn->close();
?>