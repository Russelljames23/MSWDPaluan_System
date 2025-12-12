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

// Function to generate local control number
function generateLocalControlNumber($conn)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applicant_registration_details");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $sequence = str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
    return $sequence;
}

// Function to check if ID number already exists
function isIdNumberUnique($conn, $id_number)
{
    if (empty($id_number)) return false;

    $stmt = $conn->prepare("SELECT registration_id, id_number FROM applicant_registration_details WHERE id_number = ?");
    $stmt->execute([$id_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return false if found (not unique), true if not found (unique)
    return $result ? false : true;
}

// Function to check if local control number already exists
function isLocalControlNumberUnique($conn, $local_control_number)
{
    if (empty($local_control_number)) return true;

    $stmt = $conn->prepare("SELECT registration_id, local_control_number FROM applicant_registration_details WHERE local_control_number = ?");
    $stmt->execute([$local_control_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? false : true;
}

// Function to get applicant details including id_number (with better handling)
function getApplicantWithIdNumber($conn, $applicant_id)
{
    $stmt = $conn->prepare("
        SELECT 
            a.applicant_id, 
            a.first_name, 
            a.last_name, 
            a.middle_name, 
            a.birth_date, 
            a.gender,
            COALESCE(ard.id_number, 'Not assigned') as id_number,
            COALESCE(ard.local_control_number, 'Not assigned') as local_control_number
        FROM applicants a
        LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
        WHERE a.applicant_id = ?
        LIMIT 1
    ");
    $stmt->execute([$applicant_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// NEW: Function to check for potential duplicates (more comprehensive)
function checkForDuplicates($conn, $data)
{
    $duplicates = [];

    // Check 1: Exact match on name, birthdate, and gender (most strict)
    $stmt = $conn->prepare("
        SELECT 
            a.applicant_id, 
            a.first_name, 
            a.last_name, 
            a.middle_name, 
            a.birth_date, 
            a.gender,
            COALESCE(ard.id_number, 'Not assigned') as id_number,
            COALESCE(ard.local_control_number, 'Not assigned') as local_control_number,
            a.date_created
        FROM applicants a
        LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
        WHERE LOWER(TRIM(a.first_name)) = LOWER(TRIM(?)) 
        AND LOWER(TRIM(a.last_name)) = LOWER(TRIM(?)) 
        AND LOWER(TRIM(COALESCE(a.middle_name, ''))) = LOWER(TRIM(COALESCE(?, '')))
        AND a.birth_date = ? 
        AND a.gender = ?
        AND a.status != 'Archived'  -- Exclude archived records
        ORDER BY a.date_created DESC
        LIMIT 1
    ");

    $stmt->execute([
        trim($data['fname'] ?? ''),
        trim($data['lname'] ?? ''),
        trim($data['mname'] ?? ''),
        $data['b_date'] ?? '',
        $data['gender'] ?? ''
    ]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_number_display = !empty($row['id_number']) && $row['id_number'] !== 'Not assigned' ?
            "ID Number: {$row['id_number']}" :
            "Applicant ID: {$row['applicant_id']}";

        $duplicates[] = [
            'type' => 'exact_match',
            'message' => 'An applicant with exactly the same name, birth date, and gender already exists.',
            'id_number' => $row['id_number'],
            'applicant_id' => $row['applicant_id'],
            'local_control_number' => $row['local_control_number'],
            'existing_data' => $row
        ];
        return $duplicates; // Return immediately on exact match
    }

    // Check 2: Similar name and birthdate (case insensitive)
    $stmt = $conn->prepare("
        SELECT 
            a.applicant_id, 
            a.first_name, 
            a.last_name, 
            a.middle_name, 
            a.birth_date, 
            a.gender,
            COALESCE(ard.id_number, 'Not assigned') as id_number,
            COALESCE(ard.local_control_number, 'Not assigned') as local_control_number,
            a.date_created
        FROM applicants a
        LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
        WHERE LOWER(TRIM(a.first_name)) = LOWER(TRIM(?)) 
        AND LOWER(TRIM(a.last_name)) = LOWER(TRIM(?)) 
        AND a.birth_date = ?
        AND a.status != 'Archived'  -- Exclude archived records
        ORDER BY a.date_created DESC
        LIMIT 1
    ");

    $stmt->execute([
        trim($data['fname'] ?? ''),
        trim($data['lname'] ?? ''),
        $data['b_date'] ?? ''
    ]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_number_display = !empty($row['id_number']) && $row['id_number'] !== 'Not assigned' ?
            "ID Number: {$row['id_number']}" :
            "Applicant ID: {$row['applicant_id']}";

        $duplicates[] = [
            'type' => 'similar_match',
            'message' => 'An applicant with the same first name, last name, and birth date already exists.',
            'id_number' => $row['id_number'],
            'applicant_id' => $row['applicant_id'],
            'local_control_number' => $row['local_control_number'],
            'existing_data' => $row
        ];
    }

    // Check 3: Recent submission within 30 seconds (prevents double-click/refresh)
    $stmt = $conn->prepare("
        SELECT 
            a.applicant_id, 
            a.first_name, 
            a.last_name, 
            a.date_created,
            COALESCE(ard.id_number, 'Not assigned') as id_number,
            COALESCE(ard.local_control_number, 'Not assigned') as local_control_number
        FROM applicants a
        LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
        WHERE LOWER(TRIM(a.first_name)) = LOWER(TRIM(?)) 
        AND LOWER(TRIM(a.last_name)) = LOWER(TRIM(?)) 
        AND LOWER(TRIM(COALESCE(a.middle_name, ''))) = LOWER(TRIM(COALESCE(?, '')))
        AND TIMESTAMPDIFF(SECOND, a.date_created, NOW()) < 30
        AND a.status != 'Archived'  -- Exclude archived records
        ORDER BY a.date_created DESC 
        LIMIT 1
    ");

    $stmt->execute([
        trim($data['fname'] ?? ''),
        trim($data['lname'] ?? ''),
        trim($data['mname'] ?? '')
    ]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_number_display = !empty($row['id_number']) && $row['id_number'] !== 'Not assigned' ?
            "ID Number: {$row['id_number']}" :
            "Applicant ID: {$row['applicant_id']}";

        $duplicates[] = [
            'type' => 'recent_submission',
            'message' => 'This applicant was submitted very recently. Please wait a moment before trying again.',
            'id_number' => $row['id_number'],
            'applicant_id' => $row['applicant_id'],
            'local_control_number' => $row['local_control_number'],
            'existing_data' => $row
        ];
    }

    return $duplicates;
}

try {
    $conn->beginTransaction();

    // STEP 0: Check for ID number uniqueness first (since it's required)
    $id_number = trim($data['id_number'] ?? '');
    if (empty($id_number)) {
        $conn->rollBack();
        echo json_encode([
            "error" => "ID Number is required. Please enter an ID Number."
        ]);
        exit;
    }

    // Check if ID number already exists
    if (!isIdNumberUnique($conn, $id_number)) {
        $conn->rollBack();

        // Get the existing applicant details for better error message
        $stmt = $conn->prepare("
            SELECT a.applicant_id, a.first_name, a.last_name, a.birth_date
            FROM applicants a
            INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
            WHERE ard.id_number = ?
            LIMIT 1
        ");
        $stmt->execute([$id_number]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $existingName = $existing ? "{$existing['first_name']} {$existing['last_name']}" : "existing applicant";

        echo json_encode([
            "error" => "ID Number '{$id_number}' is already assigned to {$existingName} (Born: {$existing['birth_date']}). Please use a different ID Number."
        ]);
        exit;
    }

    // STEP 1: Check for local control number uniqueness
    $local_control_number = trim($data['local_control_number'] ?? '');
    if (!empty($local_control_number) && $local_control_number !== "Auto-generated") {
        if (!isLocalControlNumberUnique($conn, $local_control_number)) {
            $conn->rollBack();
            echo json_encode([
                "error" => "Local Control Number '{$local_control_number}' already exists. Please use a different number."
            ]);
            exit;
        }
    }

    // STEP 2: Check for applicant duplicates
    $duplicateChecks = checkForDuplicates($conn, $data);

    if (!empty($duplicateChecks)) {
        $conn->rollBack();

        // For exact matches, block submission
        foreach ($duplicateChecks as $check) {
            if ($check['type'] === 'exact_match') {
                $id_number_display = !empty($check['id_number']) && $check['id_number'] !== 'Not assigned' ?
                    "ID Number: {$check['id_number']}" :
                    "Applicant ID: {$check['applicant_id']}";

                $local_control_display = !empty($check['local_control_number']) && $check['local_control_number'] !== 'Not assigned' ?
                    " | Local Control: {$check['local_control_number']}" : "";

                echo json_encode([
                    "error" => "DUPLICATE_ENTRY: " . $check['message'] .
                        " {$id_number_display}{$local_control_display}. Please check if this is the same person."
                ]);
                exit;
            }
        }

        // For similar matches or recent submissions, also block
        if (!empty($duplicateChecks)) {
            $check = $duplicateChecks[0];
            $id_number_display = !empty($check['id_number']) && $check['id_number'] !== 'Not assigned' ?
                "ID Number: {$check['id_number']}" :
                "Applicant ID: {$check['applicant_id']}";

            $local_control_display = !empty($check['local_control_number']) && $check['local_control_number'] !== 'Not assigned' ?
                " | Local Control: {$check['local_control_number']}" : "";

            echo json_encode([
                "error" => "POTENTIAL_DUPLICATE: " . $check['message'] .
                    " {$id_number_display}{$local_control_display}. Please verify if this is a new applicant."
            ]);
            exit;
        }
    }

    // Calculate accurate current_age from birth date
    $current_age = calculateCurrentAge($data['b_date'] ?? null);

    // Generate local control number if not provided or set to auto-generated
    if (empty($local_control_number) || $local_control_number === "Auto-generated") {
        $local_control_number = generateLocalControlNumber($conn);
    }

    $date_of_registration = $data['date_of_registration'] ?? date('Y-m-d');

    // STEP 3: Insert into applicants (main table)
    $stmt = $conn->prepare("
        INSERT INTO applicants (
            last_name, first_name, middle_name, suffix, gender, age, current_age, 
            civil_status, birth_date, citizenship, religion, birth_place, 
            educational_attainment, living_arrangement, validation, status, age_last_updated, date_created
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Validation', 'Active', CURDATE(), NOW())
    ");

    $stmt->execute([
        trim($data['lname'] ?? ''),
        trim($data['fname'] ?? ''),
        trim($data['mname'] ?? ''),
        trim($data['suffix'] ?? ''),
        $data['gender'] ?? null,
        $data['age'] ?? null,
        $current_age,
        $data['civil_status'] ?? null,
        $data['b_date'] ?? null,
        trim($data['citizenship'] ?? ''),
        trim($data['religion'] ?? ''),
        trim($data['birth_place'] ?? ''),
        trim($data['educational_attainment'] ?? ''),
        $data['living_arrangement'] ?? null
    ]);

    $applicant_id = $conn->lastInsertId();

    // STEP 4: Insert into applicant_registration_details
    // Get IP group from data if available
    $ip_group = trim($data['ip_group'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO applicant_registration_details (
            applicant_id, local_control_number, id_number, date_of_registration, ip_group,
            registration_status, date_created
        ) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([
        $applicant_id,
        $local_control_number,
        $id_number,
        $date_of_registration,
        !empty($ip_group) ? $ip_group : null
    ]);

    $registration_id = $conn->lastInsertId();

    // STEP 5: Insert into applicant_demographics (if you still need this separate table)
    if (!empty($ip_group)) {
        $stmt = $conn->prepare("
            INSERT INTO applicant_demographics (
                applicant_id, ip_group
            ) VALUES (?, ?)
        ");
        $stmt->execute([
            $applicant_id,
            $ip_group
        ]);
    }

    // STEP 6: Address
    $stmt = $conn->prepare("
        INSERT INTO addresses (applicant_id, house_no, street, barangay, municipality, province)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        trim($data['house_no'] ?? ''),
        trim($data['street'] ?? ''),
        $data['brgy'] ?? null,
        trim($data['municipality'] ?? ''),
        trim($data['province'] ?? '')
    ]);

    // STEP 7: Economic Status
    // Fix: Add support_in_kind field handling
    $support_in_kind = isset($data['support_in_kind']) ? trim($data['support_in_kind']) : (isset($data['support_type']) && strpos(strtolower($data['support_type']), 'kind') !== false ? trim($data['support_type']) : null);

    $stmt = $conn->prepare("
        INSERT INTO economic_status (
            applicant_id, is_pensioner, pension_amount, pension_source, pension_source_other,
            has_permanent_income, income_source, has_family_support, support_type, support_cash, support_in_kind
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['is_pensioner'] ?? 0,
        trim($data['pension_amount'] ?? ''),
        $data['pension_source'] ?? null,
        trim($data['pension_source_other'] ?? ''),
        $data['has_permanent_income'] ?? 0,
        trim($data['income_source'] ?? ''),
        $data['has_family_support'] ?? 0,
        trim($data['support_type'] ?? ''),
        trim($data['support_cash'] ?? ''),
        $support_in_kind
    ]);

    // STEP 8: Health Condition
    $stmt = $conn->prepare("
        INSERT INTO health_condition (
            applicant_id, has_existing_illness, illness_details, hospitalized_last6mos
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        $data['has_existing_illness'] ?? 0,
        trim($data['illness_details'] ?? ''),
        $data['hospitalized_last6mos'] ?? 0
    ]);

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Application submitted successfully!",
        "applicant_id" => $applicant_id,
        "registration_id" => $registration_id,
        "local_control_number" => $local_control_number,
        "id_number" => $id_number,
        "date_of_registration" => $date_of_registration,
        "calculated_age" => $current_age
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(["error" => "Registration failed: " . $e->getMessage()]);
}
