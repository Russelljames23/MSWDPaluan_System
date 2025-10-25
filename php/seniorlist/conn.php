<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mswd_seniors"; // your DB name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Return JSON and stop script
    header("Content-Type: application/json");
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
?>
