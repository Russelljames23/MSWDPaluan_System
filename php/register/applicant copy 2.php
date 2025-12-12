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

include '../db.php';

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

// Function to calculate accurate current age from birth date
function calculateCurrentAge($birth_date)
{
    if (!$birth_date) return null;

    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
    return $age;
}

// Function to generate control number (existing logic)


// Function to generate local control number
function generateLocalControlNumber($conn)
{
    // $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applicant_registration_details");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $sequence = str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
    return $sequence;
}

// Function to check if ID number already exists
function isIdNumberUnique($conn, $id_number)
{
    if (empty($id_number)) return true;

    $stmt = $conn->prepare("SELECT registration_id FROM applicant_registration_details WHERE id_number = ?");
    $stmt->execute([$id_number]);
    return !$stmt->fetch();
}

// Function to check if local control number already exists
function isLocalControlNumberUnique($conn, $local_control_number)
{
    if (empty($local_control_number)) return true;

    $stmt = $conn->prepare("SELECT registration_id FROM applicant_registration_details WHERE local_control_number = ?");
    $stmt->execute([$local_control_number]);
    return !$stmt->fetch();
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

    // Check if ID number is unique
    $id_number = $data['id_number'] ?? '';
    if (!isIdNumberUnique($conn, $id_number)) {
        echo json_encode([
            "error" => "ID Number already exists. Please use a different ID Number."
        ]);
        exit;
    }

    // Check if local control number is unique
    $local_control_number = $data['local_control_number'] ?? '';
    if (!empty($local_control_number) && !isLocalControlNumberUnique($conn, $local_control_number)) {
        echo json_encode([
            "error" => "Local Control Number already exists. Please use a different number."
        ]);
        exit;
    }

    // Optional: Warn if similar applicant already exists in database
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

    // Calculate accurate current_age from birth date
    $current_age = calculateCurrentAge($data['b_date'] ?? null);

    // Generate control numbers
    $local_control_number = $data['local_control_number'] ?: generateLocalControlNumber($conn);
    $date_of_registration = $data['date_of_registration'] ?? date('Y-m-d');

    /// STEP 1: Insert into applicants (main table - fixed)
    $stmt = $conn->prepare("
        INSERT INTO applicants (
            last_name, first_name, middle_name, suffix, gender, age, current_age, 
            civil_status, birth_date, citizenship, religion, birth_place, 
            educational_attainment, living_arrangement, validation, status, age_last_updated, date_created
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Validation', 'Active', CURDATE(), NOW())
    ");

    $stmt->execute([
        $data['lname'] ?? null,
        $data['fname'] ?? null,
        $data['mname'] ?? null,
        $data['suffix'] ?? null,
        $data['gender'] ?? null,
        $data['age'] ?? null,
        $current_age,
        $data['civil_status'] ?? null,
        $data['b_date'] ?? null,
        $data['citizenship'] ?? null,
        $data['religion'] ?? null,
        $data['birth_place'] ?? null,
        $data['educational_attainment'] ?? null,
        $data['living_arrangement'] ?? null  
    ]);

    $applicant_id = $conn->lastInsertId();

    // STEP 2: Insert into applicant_registration_details (new table)
    $stmt = $conn->prepare("
        INSERT INTO applicant_registration_details (
            applicant_id, local_control_number, id_number, date_of_registration
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $local_control_number,
        $id_number,
        $date_of_registration
    ]);

    // STEP 3: Insert into applicant_demographics (new table) - IP Group goes here
    $stmt = $conn->prepare("
        INSERT INTO applicant_demographics (
            applicant_id, ip_group
        ) VALUES (?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['ip_group'] ?? null
    ]);

    // STEP 4: Address
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

    // STEP 5: Economic Status
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

    // STEP 6: Health Condition
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
        "applicant_id" => $applicant_id,
        "local_control_number" => $local_control_number,
        "id_number" => $id_number,
        "date_of_registration" => $date_of_registration,
        "calculated_age" => $current_age
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Failed: " . $e->getMessage()]);
}
