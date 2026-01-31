<?php
// fetch_benefit_types.php
require_once "../php/login/admin_header.php";
header('Content-Type: application/json');

$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch distinct benefit types from benefits table
    $stmt = $pdo->prepare("
        SELECT DISTINCT benefit_type_id, benefit_name 
        FROM benefits 
        WHERE is_active = 1 
        ORDER BY benefit_name
    ");
    
    $stmt->execute();
    $benefit_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no benefit types found in benefits table, try to get from benefit_types table
    if (empty($benefit_types)) {
        $stmt = $pdo->prepare("
            SELECT id as benefit_type_id, benefit_name 
            FROM benefit_types 
            WHERE is_active = 1 
            ORDER BY benefit_name
        ");
        $stmt->execute();
        $benefit_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'benefit_types' => $benefit_types
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'benefit_types' => []
    ]);
}
?>