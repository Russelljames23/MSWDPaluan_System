<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['date_of_inactive']) || empty($data['reason'])) {
    echo json_encode(["error" => "Missing applicant ID, date, or reason."]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Inactive', inactive_reason = ?, date_of_inactive = ?, date_modified = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->execute([$data['reason'], $data['date_of_inactive'], $data['applicant_id']]);

    echo json_encode(["message" => "Senior successfully marked as inactive."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
