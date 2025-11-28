<?php
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['applicant_ids']) || !isset($input['benefits']) || !isset($input['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: applicant_ids, benefits, or date']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    $applicantIds = $input['applicant_ids'];
    $benefits = $input['benefits'];
    $date = $input['date'];

    // Prepare the insert statement - include benefit_name
    $stmt = $conn->prepare("INSERT INTO benefits_distribution (applicant_id, benefit_id, benefit_name, amount, distribution_date) VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $successCount = 0;
    foreach ($applicantIds as $applicantId) {
        foreach ($benefits as $benefit) {
            $benefitId = $benefit['id'];
            $benefitName = $benefit['name'];
            $amount = $benefit['amount'];

            $stmt->bind_param("iisds", $applicantId, $benefitId, $benefitName, $amount, $date);
            if ($stmt->execute()) {
                $successCount++;
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Benefits added successfully to " . count($applicantIds) . " beneficiary(ies)",
        'count' => $successCount
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error adding benefits: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding benefits: ' . $e->getMessage()]);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
