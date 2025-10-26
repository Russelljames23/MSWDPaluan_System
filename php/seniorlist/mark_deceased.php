<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['date_of_death'])) {
    echo json_encode(["error" => "Missing applicant ID or date of death."]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Deceased', date_of_death = ?, date_modified = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->execute([$data['date_of_death'], $data['applicant_id']]);

    echo json_encode(["message" => "Senior successfully marked as deceased."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
