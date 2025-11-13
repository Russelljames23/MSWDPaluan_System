<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['pension_status'])) {
    echo json_encode(["error" => "Missing applicant ID(s) or pension status."]);
    exit;
}

// Ensure $ids is an array
$ids = is_array($data['applicant_id']) ? $data['applicant_id'] : [$data['applicant_id']];

// Validate that all IDs are integers
$ids = array_filter($ids, fn($id) => filter_var($id, FILTER_VALIDATE_INT) !== false);
if (empty($ids)) {
    echo json_encode(["error" => "No valid applicant IDs provided."]);
    exit;
}

try {
    // Prepare placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE applicants SET pension_status = ?, date_modified = NOW() WHERE applicant_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    // Merge pension_status as first param
    $params = array_merge([$data['pension_status']], $ids);
    $stmt->execute($params);

    echo json_encode([
        "message" => "Pension status updated successfully.",
        "updated_ids" => $ids
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
