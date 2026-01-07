<?php
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if (!isset($_GET['applicant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Applicant ID is required']);
    exit;
}

$applicantId = intval($_GET['applicant_id']);

try {
    // Fetch benefits history for the applicant
    // Try to get benefit_name from benefits_distribution first, fallback to benefits table
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(bd.benefit_name, b.benefit_name) as benefit_name,
            bd.amount,
            bd.distribution_date as date_received
        FROM benefits_distribution bd
        LEFT JOIN benefits b ON bd.benefit_id = b.id
        WHERE bd.applicant_id = ? 
        ORDER BY bd.distribution_date DESC
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $applicantId);
    $stmt->execute();
    $result = $stmt->get_result();

    $benefits = [];
    $totalAmount = 0;

    while ($row = $result->fetch_assoc()) {
        $benefits[] = $row;
        $totalAmount += floatval($row['amount']);
    }

    echo json_encode([
        'success' => true,
        'benefits' => $benefits,
        'total_amount' => $totalAmount,
        'count' => count($benefits)
    ]);
} catch (Exception $e) {
    error_log("Error fetching benefits history: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching benefits history: ' . $e->getMessage(),
        'benefits' => []
    ]);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
