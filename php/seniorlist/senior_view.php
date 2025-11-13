<?php
// ---------------------------------------------
// senior_view.php
// Returns applicant data in JSON only
// ---------------------------------------------

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Disable HTML error output to avoid breaking JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Include DB connection (adjust path if needed)
include __DIR__ . '/conn.php'; // make sure this path is correct

// Check for ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing applicant ID"]);
    exit;
}

$id = intval($_GET['id']);

try {
    if (!$conn) {
        throw new Exception("Database connection not established.");
    }

    // Fetch applicant info
    $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        http_response_code(404);
        echo json_encode(["error" => "Applicant not found"]);
        exit;
    }

    // Combine names into full_name (works even if separate columns)
    $applicant['full_name'] = trim(
        ($applicant['last_name'] ?? '') . ', ' .
            ($applicant['first_name'] ?? '') . ' ' .
            ($applicant['middle_name'] ?? '') . '.'
    );

    if (($applicant['birth_date'])) {
        $date = new DateTime($applicant['birth_date']);
        $applicant['birth_date'] = $date->format('m-d-Y'); // MM-DD-YYYY
    }

    // Fetch address
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch economic status
    $stmt = $conn->prepare("SELECT * FROM economic_status WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $economic = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch health condition
    $stmt = $conn->prepare("SELECT * FROM health_condition WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $health = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return JSON
    echo json_encode([
        "applicant" => $applicant,
        "address" => $address,
        "economic" => $economic,
        "health" => $health
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}
