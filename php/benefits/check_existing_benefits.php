<?php
// check_existing_benefits.php - Check if beneficiaries already have certain benefits
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed"
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method"
    ]);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['applicant_ids']) || !isset($input['benefit_types'])) {
    echo json_encode([
        "success" => false,
        "error" => "Missing required data"
    ]);
    exit;
}

try {
    $applicantIds = $input['applicant_ids'];
    $benefitTypes = $input['benefit_types'];
    
    if (empty($applicantIds) || empty($benefitTypes)) {
        echo json_encode([
            "success" => true,
            "has_existing" => false,
            "existing_benefits" => []
        ]);
        exit;
    }
    
    // Helper function to check if benefit contains "burial"
    function isBurialBenefit($benefitName) {
        $normalized = strtolower(trim($benefitName));
        return stripos($normalized, 'burial') !== false;
    }
    
    // Check if any of the benefit types are burial benefits
    $hasBurialBenefits = false;
    foreach ($benefitTypes as $benefitType) {
        if (isBurialBenefit($benefitType)) {
            $hasBurialBenefits = true;
            break;
        }
    }
    
    if (!$hasBurialBenefits) {
        // Only checking for burial benefits restriction
        echo json_encode([
            "success" => true,
            "has_existing" => false,
            "existing_benefits" => []
        ]);
        exit;
    }
    
    // Prepare the query to check for existing burial benefits
    $placeholders = implode(',', array_fill(0, count($applicantIds), '?'));
    
    $query = "SELECT 
                bd.applicant_id,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                bd.benefit_name,
                DATE(bd.distribution_date) as distribution_date,
                a.status
              FROM benefits_distribution bd
              JOIN applicants a ON bd.applicant_id = a.applicant_id
              WHERE bd.applicant_id IN ($placeholders)
                AND (";
    
    // Add conditions for burial benefits
    $conditions = [];
    foreach ($benefitTypes as $benefitType) {
        if (isBurialBenefit($benefitType)) {
            $conditions[] = "LOWER(bd.benefit_name) LIKE ?";
        }
    }
    
    if (empty($conditions)) {
        echo json_encode([
            "success" => true,
            "has_existing" => false,
            "existing_benefits" => []
        ]);
        exit;
    }
    
    $query .= implode(' OR ', $conditions) . ")";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Build parameters
    $params = array_merge($applicantIds);
    foreach ($benefitTypes as $benefitType) {
        if (isBurialBenefit($benefitType)) {
            $params[] = '%burial%';
        }
    }
    
    // Bind parameters
    $types = str_repeat('i', count($applicantIds)) . str_repeat('s', count($conditions));
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $existingBenefits = [];
    while ($row = $result->fetch_assoc()) {
        // Only include deceased beneficiaries
        if ($row['status'] === 'Deceased') {
            $existingBenefits[] = [
                'applicant_id' => $row['applicant_id'],
                'applicant_name' => $row['applicant_name'],
                'benefit_name' => $row['benefit_name'],
                'distribution_date' => $row['distribution_date']
            ];
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "has_existing" => !empty($existingBenefits),
        "existing_benefits" => $existingBenefits,
        "count" => count($existingBenefits)
    ]);
    
} catch (Exception $e) {
    error_log("Error checking existing benefits: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>