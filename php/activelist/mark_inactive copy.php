<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['date_of_inactive']) || empty($data['reason'])) {
    echo json_encode(["error" => "Missing applicant ID, date, or reason."]);
    exit;
}

// Validate reason length
if (strlen(trim($data['reason'])) === 0) {
    echo json_encode(["error" => "Reason cannot be empty or just whitespace."]);
    exit;
}

// Sanitize reason
$reason = trim($data['reason']);
if (strlen($reason) > 255) {
    echo json_encode(["error" => "Reason is too long. Maximum 255 characters allowed."]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Inactive', 
            inactive_reason = ?, 
            date_of_inactive = ?, 
            date_modified = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->execute([
        $reason,
        $data['date_of_inactive'],
        $data['applicant_id']
    ]);

    // Check if any row was affected
    if ($stmt->rowCount() === 0) {
        throw new Exception("No applicant found with the provided ID.");
    }

    // Commit transaction
    $conn->commit();

    // Return consistent success response
    echo json_encode([
        "success" => true,
        "message" => "Senior successfully marked as inactive."
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
