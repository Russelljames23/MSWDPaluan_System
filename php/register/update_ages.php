<?php
// update_ages.php - Manual age update script
include '../db.php';

header('Content-Type: application/json');

try {
    // Update all active seniors' ages
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET current_age = TIMESTAMPDIFF(YEAR, birth_date, CURDATE()),
            age_last_updated = CURDATE()
        WHERE status = 'Active' 
        AND birth_date IS NOT NULL
    ");
    
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    echo json_encode([
        "success" => true,
        "message" => "Updated ages for $affected seniors",
        "updated_count" => $affected
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to update ages: " . $e->getMessage()
    ]);
}
?>