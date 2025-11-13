<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Missing applicant ID.");
    }

    $id = intval($_GET['id']);

    // Update status to Active and clear date_of_death
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Active', date_of_inactive = NULL , inactive_reason = NULL
        WHERE applicant_id = ?
    ");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Record restored to Active list."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No record updated."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
