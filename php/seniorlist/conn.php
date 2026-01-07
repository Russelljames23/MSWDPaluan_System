<?php
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23"; // your DB name

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
