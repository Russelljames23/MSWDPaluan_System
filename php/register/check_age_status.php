<?php
// check_age_status.php - Monitor age update status
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Check when ages were last updated
    $query = "
        SELECT 
            MAX(age_last_updated) as last_update,
            COUNT(*) as total,
            SUM(CASE WHEN age_last_updated = CURDATE() THEN 1 ELSE 0 END) as updated_today,
            SUM(CASE WHEN age_last_updated < CURDATE() OR age_last_updated IS NULL THEN 1 ELSE 0 END) as needs_update
        FROM applicants 
        WHERE status = 'Active'
    ";
    
    $stmt = $pdo->query($query);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'ok',
        'data' => $status,
        'current_date' => date('Y-m-d'),
        'message' => $status['needs_update'] > 0 ? 
            "{$status['needs_update']} applicants need age update" : 
            "All ages are up to date"
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}