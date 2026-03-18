<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['date_of_death'])) {
    echo json_encode(["error" => "Missing applicant ID or date of death."]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Update applicant status to Deceased
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Deceased', 
            date_of_death = ?,
            date_modified = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->execute([
        $data['date_of_death'],
        $data['applicant_id']
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(["message" => "Senior successfully marked as deceased."]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
