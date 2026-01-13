<?php
// applicant.php - IMPROVED WITH CONTACT NUMBER SUPPORT
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
// -----------------------------
// DATABASE CONNECTION
// -----------------------------
include '../db.php';

if (!$conn) {
    sendJsonError("Database connection failed", 500);
}
// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// -----------------------------
// SESSION INITIALIZATION
// -----------------------------
function initializeSessionForApplicant()
{
    if (session_status() === PHP_SESSION_NONE) {
        // Simply start the session - DO NOT change session name
        @session_start();

        // Set default session context if not set
        if (!isset($_SESSION['session_context'])) {
            $_SESSION['session_context'] = 'admin';
        }

        // Ensure basic session structure
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 0;
        }

        if (!isset($_SESSION['session_initialized'])) {
            $_SESSION['session_initialized'] = true;
        }
    }

    return true;
}

// Initialize session
initializeSessionForApplicant();



// -----------------------------
// HELPER FUNCTIONS
// -----------------------------
function sendJsonError($message, $code = 400)
{
    http_response_code($code);
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "success" => false,
        "error" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sendJsonSuccess($message, $data = [])
{
    if (ob_get_length()) ob_clean();
    echo json_encode(array_merge([
        "success" => true,
        "message" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ], $data));
    exit;
}

// Get current user ID
function getCurrentUserId($conn)
{
    // Method 1: Direct session lookup
    if (isset($_SESSION['session_context'])) {
        $context = $_SESSION['session_context'];

        if ($context === 'staff') {
            $staffIds = ['staff_user_id', 'user_id', 'id'];
            foreach ($staffIds as $idKey) {
                if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                    $userId = $_SESSION[$idKey];
                    if (verifyUserIsStaff($conn, $userId)) {
                        $_SESSION['staff_user_id'] = $userId;
                        return $userId;
                    }
                }
            }
        } else if ($context === 'admin') {
            $adminIds = ['admin_user_id', 'user_id', 'id'];
            foreach ($adminIds as $idKey) {
                if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                    $userId = $_SESSION[$idKey];
                    $_SESSION['admin_user_id'] = $userId;
                    return $userId;
                }
            }
        }
    }

    // Method 2: Default to Admin
    error_log("Defaulting to Admin ID: 57");
    $_SESSION['session_context'] = 'admin';
    $_SESSION['admin_user_id'] = 57;
    $_SESSION['user_id'] = 57;
    return 57;
}

function verifyUserIsStaff($conn, $userId)
{
    try {
        $stmt = $conn->prepare(
            "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userType = strtolower($user['user_type']);
            return (strpos($userType, 'staff') !== false ||
                strpos($userType, 'data entry') !== false ||
                strpos($userType, 'viewer') !== false);
        }
    } catch (Exception $e) {
        error_log("Error verifying staff user: " . $e->getMessage());
    }

    return false;
}

// Get current user ID
$userId = getCurrentUserId($conn);

// Get user name
$userName = 'Unknown';
if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
    $userName = $_SESSION['fullname'];
} elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
    $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
} elseif (isset($_SESSION['username'])) {
    $userName = $_SESSION['username'];
}

// -----------------------------
// READ JSON BODY
// -----------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    sendJsonError("Invalid or missing JSON data.");
}

// Update session with request data
if (isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
    $_SESSION['staff_user_id'] = $data['staff_user_id'];
    $_SESSION['user_id'] = $data['staff_user_id'];
    $_SESSION['session_context'] = 'staff';
} elseif (isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
    $_SESSION['admin_user_id'] = $data['admin_user_id'];
    $_SESSION['user_id'] = $data['admin_user_id'];
    $_SESSION['session_context'] = 'admin';
}

if (isset($data['session_context']) && !empty($data['session_context'])) {
    $_SESSION['session_context'] = $data['session_context'];
}

// -----------------------------
// VALIDATION FUNCTIONS
// -----------------------------
function calculateCurrentAge($birth_date)
{
    if (!$birth_date) return null;
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

function generateLocalControlNumber($conn)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applicant_registration_details");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return str_pad(($result['count'] + 1), 6, '0', STR_PAD_LEFT);
}

function isIdNumberUnique($conn, $id_number)
{
    if (empty($id_number)) return false;

    $stmt = $conn->prepare("
        SELECT registration_id, id_number 
        FROM applicant_registration_details 
        WHERE id_number = ?
    ");
    $stmt->execute([$id_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? false : true;
}

function isLocalControlNumberUnique($conn, $local_control_number)
{
    if (empty($local_control_number)) return true;

    $stmt = $conn->prepare("
        SELECT registration_id, local_control_number 
        FROM applicant_registration_details 
        WHERE local_control_number = ?
    ");
    $stmt->execute([$local_control_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? false : true;
}

function validatePhoneNumber($phone)
{
    if (empty($phone)) return true; // Not required for existing records

    // Remove all non-numeric characters
    $cleanNumber = preg_replace('/[^0-9]/', '', $phone);

    // Check if it's a valid Philippine number
    if (strlen($cleanNumber) === 11 && strpos($cleanNumber, '09') === 0) {
        return true;
    }

    if (strlen($cleanNumber) === 12 && strpos($cleanNumber, '639') === 0) {
        return true;
    }

    return false;
}

function checkForDuplicates($conn, $data)
{
    $duplicates = [];

    // Check exact match on name, birthdate, and gender
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
        AND a.status != 'Archived'
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
        $duplicates[] = [
            'type' => 'exact_match',
            'message' => 'An applicant with exactly the same name, birth date, and gender already exists.',
            'id_number' => $row['id_number'],
            'applicant_id' => $row['applicant_id'],
            'local_control_number' => $row['local_control_number'],
            'existing_data' => $row
        ];
    }

    return $duplicates;
}

// -----------------------------
// MAIN PROCESSING
// -----------------------------
try {
    // Begin transaction
    $conn->beginTransaction();

    $applicantName = trim($data['fname'] ?? '') . ' ' . trim($data['lname'] ?? '');
    $birthDate = $data['b_date'] ?? '';

    error_log("Processing applicant: $applicantName, Birthdate: $birthDate");

    // STEP 0: Validate required fields
    $requiredFields = [
        'lname' => 'Last Name',
        'fname' => 'First Name',
        'mname' => 'Middle Name',
        'gender' => 'Gender',
        'b_date' => 'Birthdate',
        'civil_status' => 'Civil Status',
        'citizenship' => 'Citizenship',
        'religion' => 'Religion',
        'birth_place' => 'Birthplace',
        'educational_attainment' => 'Educational Attainment',
        'living_arrangement' => 'Living Arrangement',
        'brgy' => 'Barangay',
        'municipality' => 'Municipality',
        'province' => 'Province',
        'id_number' => 'ID Number'
    ];

    $missingFields = [];
    foreach ($requiredFields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $missingFields[] = $label;
        }
    }

    if (!empty($missingFields)) {
        $conn->rollBack();
        sendJsonError("Missing required fields: " . implode(', ', $missingFields));
    }

    // STEP 1: Validate ID number uniqueness
    $id_number = trim($data['id_number'] ?? '');

    if (!isIdNumberUnique($conn, $id_number)) {
        $conn->rollBack();

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

        sendJsonError("ID Number '{$id_number}' is already assigned to {$existingName} (Born: {$existing['birth_date']}). Please use a different ID Number.");
    }

    // STEP 2: Validate local control number uniqueness
    $local_control_number = trim($data['local_control_number'] ?? '');
    if (!empty($local_control_number) && $local_control_number !== "Auto-generated") {
        if (!isLocalControlNumberUnique($conn, $local_control_number)) {
            $conn->rollBack();
            sendJsonError("Local Control Number '{$local_control_number}' already exists. Please use a different number.");
        }
    }

    // STEP 3: Validate phone number if provided
    $contact_number = trim($data['contact_number'] ?? '');
    if (!empty($contact_number) && !validatePhoneNumber($contact_number)) {
        $conn->rollBack();
        sendJsonError("Please enter a valid Philippine phone number (09XXXXXXXXX or +639XXXXXXXXX).");
    }

    // STEP 4: Check for duplicates
    $duplicateChecks = checkForDuplicates($conn, $data);

    if (!empty($duplicateChecks)) {
        $conn->rollBack();

        foreach ($duplicateChecks as $check) {
            if ($check['type'] === 'exact_match') {
                $id_number_display = !empty($check['id_number']) && $check['id_number'] !== 'Not assigned' ?
                    "ID Number: {$check['id_number']}" :
                    "Applicant ID: {$check['applicant_id']}";

                $local_control_display = !empty($check['local_control_number']) && $check['local_control_number'] !== 'Not assigned' ?
                    " | Local Control: {$check['local_control_number']}" : "";

                sendJsonError("DUPLICATE_ENTRY: " . $check['message'] .
                    " {$id_number_display}{$local_control_display}. Please check if this is the same person.");
            }
        }
    }

    // STEP 5: Calculate age and prepare data
    $current_age = calculateCurrentAge($data['b_date'] ?? null);

    // Generate local control number if not provided
    if (empty($local_control_number) || $local_control_number === "Auto-generated") {
        $local_control_number = generateLocalControlNumber($conn);
    }

    $date_of_registration = $data['date_of_registration'] ?? date('Y-m-d');

    // STEP 6: Insert into applicants table (WITH CONTACT NUMBER)
    $stmt = $conn->prepare("
        INSERT INTO applicants (
            last_name, first_name, middle_name, suffix, gender, age, current_age, 
            civil_status, birth_date, citizenship, religion, birth_place, 
            educational_attainment, living_arrangement, contact_number, 
            validation, status, age_last_updated, date_created
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Validation', 'Active', CURDATE(), NOW())
    ");

    $insertData = [
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
        $data['living_arrangement'] ?? null,
        !empty($contact_number) ? $contact_number : null
    ];

    $stmt->execute($insertData);
    $applicant_id = $conn->lastInsertId();
    error_log("Applicant inserted with ID: $applicant_id");

    // STEP 7: Insert into applicant_registration_details
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
    error_log("Registration details inserted with ID: $registration_id");

    // STEP 8: Insert into applicant_demographics (if IP group exists)
    if (!empty($ip_group)) {
        $stmt = $conn->prepare("
            INSERT INTO applicant_demographics (
                applicant_id, ip_group
            ) VALUES (?, ?)
        ");
        $stmt->execute([$applicant_id, $ip_group]);
    }

    // STEP 9: Insert into addresses
    $stmt = $conn->prepare("
        INSERT INTO addresses (
            applicant_id, house_no, street, barangay, municipality, province
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicant_id,
        trim($data['house_no'] ?? ''),
        trim($data['street'] ?? ''),
        $data['brgy'] ?? null,
        trim($data['municipality'] ?? ''),
        trim($data['province'] ?? '')
    ]);

    $address_id = $conn->lastInsertId();

    // STEP 10: Insert into economic_status
    $support_in_kind = isset($data['support_in_kind']) ? trim($data['support_in_kind']) : (isset($data['support_type']) && strpos(strtolower($data['support_type']), 'kind') !== false ?
            trim($data['support_type']) : null);

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

    $economic_status_id = $conn->lastInsertId();

    // STEP 11: Insert into health_condition
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

    $health_condition_id = $conn->lastInsertId();

    // Commit transaction
    $conn->commit();

    // Load ActivityLogger if exists
    $logger = null;
    $activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';

    if (file_exists($activityLoggerPath)) {
        require_once $activityLoggerPath;
        if (class_exists('ActivityLogger')) {
            try {
                $logger = new ActivityLogger($conn);
            } catch (Exception $e) {
                error_log("Failed to initialize ActivityLogger: " . $e->getMessage());
            }
        }
    }

    // Log successful registration if logger exists
    if ($logger) {
        $logger->log('REGISTER_SENIOR', 'New senior citizen registered successfully', [
            'applicant_id' => $applicant_id,
            'applicant_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
            'full_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? '') . ' ' . trim($data['mname'] ?? ''),
            'id_number' => $id_number,
            'local_control_number' => $local_control_number,
            'age' => $current_age,
            'birth_date' => $birthDate,
            'gender' => $data['gender'] ?? '',
            'civil_status' => $data['civil_status'] ?? '',
            'contact_number' => !empty($contact_number) ? $contact_number : 'Not provided',
            'barangay' => $data['brgy'] ?? '',
            'registered_by' => $userName,
            'registered_by_id' => $userId,
            'registration_date' => $date_of_registration
        ]);
    }

    // Return success response
    sendJsonSuccess("Application submitted successfully!", [
        "applicant_id" => $applicant_id,
        "registration_id" => $registration_id,
        "local_control_number" => $local_control_number,
        "id_number" => $id_number,
        "date_of_registration" => $date_of_registration,
        "calculated_age" => $current_age,
        "applicant_name" => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
        "barangay" => $data['brgy'] ?? '',
        "registered_by" => $userName,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    error_log("PDOException: " . $e->getMessage());

    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    sendJsonError("Registration failed due to a database error. Please try again.");
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());

    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    sendJsonError("Registration failed: " . $e->getMessage());
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
