<?php
// -----------------------------
// HEADERS + DEBUG
// -----------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include '../db.php'; // Adjust path if necessary

// -----------------------------
// READ JSON BODY
// -----------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing JSON data."]);
    exit;
}

try {
    $conn->beginTransaction();

    // STEP 0: Prevent accidental duplicate submission (within last 5 mins)
    $check = $conn->prepare("
        SELECT applicant_id 
        FROM applicants 
        WHERE first_name = ? 
          AND last_name = ? 
          AND middle_name = ? 
          AND birth_date = ? 
          AND gender = ?
          AND TIMESTAMPDIFF(MINUTE, date_created, NOW()) < 5
    ");
    $check->execute([
        $data['fname'] ?? '',
        $data['lname'] ?? '',
        $data['mname'] ?? '',
        $data['b_date'] ?? '',
        $data['gender'] ?? ''
    ]);

    if ($check->fetch()) {
        echo json_encode([
            "error" => "This applicant was already registered recently."
        ]);
        exit;
    }

    // Optional: Warn if similar applicant already exists in database (not blocking)
    $duplicateWarning = "";
    $warn = $conn->prepare("
        SELECT applicant_id 
        FROM applicants 
        WHERE first_name = ? AND last_name = ? AND birth_date = ?
    ");
    $warn->execute([
        $data['fname'] ?? '',
        $data['lname'] ?? '',
        $data['b_date'] ?? ''
    ]);

    if ($warn->fetch()) {
        $duplicateWarning = "Note: Another applicant with the same name and birth date already exists.";
    }

    // STEP 1: Applicants
    $stmt = $conn->prepare("
        INSERT INTO applicants (
            last_name, first_name, middle_name, gender, age, civil_status,
            birth_date, citizenship, birth_place, living_arrangement, status, date_created
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'On Process', NOW())
    ");
    $stmt->execute([
        $data['lname'] ?? null,
        $data['fname'] ?? null,
        $data['mname'] ?? null,
        $data['gender'] ?? null,
        $data['age'] ?? null,
        $data['civil_status'] ?? null,
        $data['b_date'] ?? null,
        $data['citizenship'] ?? null,
        $data['birth_place'] ?? null,
        $data['living_arrangement'] ?? null
    ]);

    $applicant_id = $conn->lastInsertId();

    // STEP 2: Address
    $stmt = $conn->prepare("
        INSERT INTO addresses (applicant_id, house_no, street, barangay, municipality, province)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['house_no'] ?? null,
        $data['street'] ?? null,
        $data['brgy'] ?? null,
        $data['municipality'] ?? null,
        $data['province'] ?? null
    ]);

    // STEP 3: Economic Status
    $stmt = $conn->prepare("
        INSERT INTO economic_status (
            applicant_id, is_pensioner, pension_amount, pension_source, pension_source_other,
            has_permanent_income, income_source, has_family_support, support_type, support_cash, support_in_kind
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['is_pensioner'] ?? null,
        $data['pension_amount'] ?? null,
        $data['pension_source'] ?? null,
        $data['pension_source_other'] ?? null,
        $data['has_permanent_income'] ?? null,
        $data['income_source'] ?? null,
        $data['has_family_support'] ?? null,
        $data['support_type'] ?? null,
        $data['support_cash'] ?? null,
        $data['support_in_kind'] ?? null
    ]);

    // STEP 4: Health Condition
    $stmt = $conn->prepare("
        INSERT INTO health_condition (
            applicant_id, has_existing_illness, illness_details, hospitalized_last6mos
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['has_existing_illness'] ?? null,
        $data['illness_details'] ?? null,
        $data['hospitalized_last6mos'] ?? null
    ]);

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Application submitted successfully!" .
            ($duplicateWarning ? " $duplicateWarning" : ""),
        "applicant_id" => $applicant_id
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Failed: " . $e->getMessage()]);
}
