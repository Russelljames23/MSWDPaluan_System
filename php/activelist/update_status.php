<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['applicant_id']) || empty($data['validation'])) {
    echo json_encode(["error" => "Missing applicant ID(s) or status."]);
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

// Check if control numbers are required and provided for validation
if ($data['validation'] === 'Validated') {
    if (empty($data['control_numbers']) || !is_array($data['control_numbers'])) {
        echo json_encode(["error" => "Control numbers are required for validation."]);
        exit;
    }

    // Check if we have the same number of control numbers as applicants
    if (count($data['control_numbers']) !== count($ids)) {
        echo json_encode(["error" => "Number of control numbers does not match number of selected applicants."]);
        exit;
    }

    // Validate each control number
    foreach ($data['control_numbers'] as $controlNumber) {
        if (empty($controlNumber)) {
            echo json_encode(["error" => "All control numbers must be filled."]);
            exit;
        }

        if (strlen($controlNumber) < 1 || strlen($controlNumber) > 50) {
            echo json_encode(["error" => "Control numbers must be between 1 and 50 characters."]);
            exit;
        }
    }

    // Check for duplicate control numbers in the request
    if (count($data['control_numbers']) !== count(array_unique($data['control_numbers']))) {
        echo json_encode(["error" => "Duplicate control numbers are not allowed."]);
        exit;
    }
}

try {
    // Check if control numbers already exist in database (only for new validation)
    if ($data['validation'] === 'Validated') {
        $placeholders = implode(',', array_fill(0, count($data['control_numbers']), '?'));
        $checkSql = "SELECT COUNT(*) as count FROM applicants WHERE control_number IN ($placeholders)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute($data['control_numbers']);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo json_encode(["error" => "One or more control numbers already exist for other applicants."]);
            exit;
        }
    }

    // Prepare update queries
    if ($data['validation'] === 'Validated') {
        // Update each applicant with their individual control number
        $updatedApplicants = [];

        // Use transaction for multiple updates
        $conn->beginTransaction();

        try {
            foreach ($ids as $index => $applicantId) {
                $controlNumber = $data['control_numbers'][$index];

                $sql = "UPDATE applicants SET validation = ?, control_number = ?, date_modified = NOW() WHERE applicant_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$data['validation'], $controlNumber, $applicantId]);

                $updatedApplicants[] = [
                    'id' => $applicantId,
                    'control_number' => $controlNumber
                ];
            }

            $conn->commit();

            echo json_encode([
                "message" => "Status updated successfully with control numbers.",
                "updated_applicants" => $updatedApplicants
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } else {
        // For non-validated status, clear the control numbers
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE applicants SET validation = ?, control_number = NULL, date_modified = NOW() WHERE applicant_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_merge([$data['validation']], $ids));

        echo json_encode([
            "message" => "Status updated successfully.",
            "updated_ids" => $ids
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
