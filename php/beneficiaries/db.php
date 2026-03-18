<?php
// db.php - Unified database connection
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For debugging, show error
    error_log("Database connection failed in db.php: " . $e->getMessage());
    
    // Return JSON error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strpos($_SERVER['REQUEST_URI'], '/api/') !== false ||
        strpos($_SERVER['REQUEST_URI'], 'fetch_seniors.php') !== false) {
        header('Content-Type: application/json');
        die(json_encode([
            "success" => false,
            "error" => "Database connection failed",
            "message" => $e->getMessage()
        ]));
    } else {
        die("Database connection failed. Please try again later.");
    }
}
?>