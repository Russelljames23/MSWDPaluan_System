<?php
// applicant.php - Improved version with proper activity logging
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Helper functions
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

// Check database connection
if (!$conn) {
    sendJsonError("Database connection failed", 500);
}

// Load ActivityLogger
$logger = null;
$activityLoggerPath = dirname(__DIR__) . '/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
} elseif (file_exists(dirname(__DIR__) . '/settings/ActivityLogger.php')) {
    require_once dirname(__DIR__) . '/settings/ActivityLogger.php';
} else {
    // Try to find ActivityLogger.php in parent directory
    $activityLoggerPath = dirname(dirname(__DIR__)) . '/ActivityLogger.php';
    if (file_exists($activityLoggerPath)) {
        require_once $activityLoggerPath;
    }
}

// -----------------------------
// READ JSON BODY
// -----------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    sendJsonError("Invalid or missing JSON data.");
}

// Initialize logger
if (class_exists('ActivityLogger')) {
    // ActivityLogger constructor only takes $conn parameter
    $logger = new ActivityLogger($conn);
} else {
    // Create minimal logger for debugging
    class SimpleLogger
    {
        public function log($type, $desc, $details = null)
        {
            // Log to file for debugging
            $logMessage = date('Y-m-d H:i:s') . " - Activity: $type - $desc";
            if ($details) {
                $logMessage .= " - Details: " . json_encode($details);
            }
            error_log($logMessage);

            // Also try to log to database directly for debugging
            global $conn;
            if (isset($conn)) {
                try {
                    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                    // Get user from session
                    session_start();
                    $userId = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 0);

                    // Get user name
                    $userName = 'Unknown';
                    if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                        $userName = $_SESSION['fullname'];
                    } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
                    } elseif (isset($_SESSION['username'])) {
                        $userName = $_SESSION['username'];
                    }

                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $userId,
                        $type,
                        $desc,
                        $detailsJson,
                        $ipAddress,
                        substr($userAgent, 0, 500)
                    ]);

                    return $stmt->rowCount() > 0;
                } catch (Exception $e) {
                    error_log("Direct DB logging failed: " . $e->getMessage());
                    return false;
                }
            }
            return true;
        }
    }
    $logger = new SimpleLogger($conn);
}

// Get user info for logging
$userId = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 0);
$userName = 'Unknown';
if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
    $userName = $_SESSION['fullname'];
} elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
    $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
} elseif (isset($_SESSION['username'])) {
    $userName = $_SESSION['username'];
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

// Function to check for potential duplicates (more comprehensive)
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

    $applicantName = trim($data['fname'] ?? '') . ' ' . trim($data['lname'] ?? '');
    $birthDate = $data['b_date'] ?? '';

    // STEP 0: Check for ID number uniqueness first (since it's required)
    $id_number = trim($data['id_number'] ?? '');
    if (empty($id_number)) {
        $conn->rollBack();

        // Log missing ID number
        $logger->log('ERROR', 'Registration attempt with missing ID number', [
            'applicant_name' => $applicantName,
            'birth_date' => $birthDate,
            'attempted_by' => $userName,
            'error_type' => 'Missing ID Number'
        ]);

        sendJsonError("ID Number is required. Please enter an ID Number.");
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

        // Log duplicate ID attempt
        $logger->log('ERROR', 'Attempted to use duplicate ID number', [
            'attempted_id' => $id_number,
            'applicant_name' => $applicantName,
            'existing_applicant_id' => $existing['applicant_id'] ?? null,
            'existing_applicant_name' => $existingName,
            'existing_birth_date' => $existing['birth_date'] ?? null,
            'attempted_by' => $userName
        ]);

        sendJsonError("ID Number '{$id_number}' is already assigned to {$existingName} (Born: {$existing['birth_date']}). Please use a different ID Number.");
    }

    // STEP 1: Check for local control number uniqueness
    $local_control_number = trim($data['local_control_number'] ?? '');
    if (!empty($local_control_number) && $local_control_number !== "Auto-generated") {
        if (!isLocalControlNumberUnique($conn, $local_control_number)) {
            $conn->rollBack();

            // Log duplicate local control number attempt
            $logger->log('ERROR', 'Attempted to use duplicate local control number', [
                'attempted_local_control' => $local_control_number,
                'applicant_name' => $applicantName,
                'attempted_by' => $userName
            ]);

            sendJsonError("Local Control Number '{$local_control_number}' already exists. Please use a different number.");
        }
    }

    // STEP 2: Check for applicant duplicates
    $duplicateChecks = checkForDuplicates($conn, $data);

    if (!empty($duplicateChecks)) {
        $conn->rollBack();

        // Log duplicate check findings
        $logger->log('ERROR', 'Duplicate applicant check failed', [
            'applicant_name' => $applicantName,
            'birth_date' => $birthDate,
            'duplicate_type' => $duplicateChecks[0]['type'] ?? 'unknown',
            'existing_applicant_id' => $duplicateChecks[0]['applicant_id'] ?? null,
            'existing_id_number' => $duplicateChecks[0]['id_number'] ?? null,
            'existing_local_control' => $duplicateChecks[0]['local_control_number'] ?? null,
            'attempted_by' => $userName
        ]);

        // For exact matches, block submission
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

        // For similar matches or recent submissions, also block
        if (!empty($duplicateChecks)) {
            $check = $duplicateChecks[0];
            $id_number_display = !empty($check['id_number']) && $check['id_number'] !== 'Not assigned' ?
                "ID Number: {$check['id_number']}" :
                "Applicant ID: {$check['applicant_id']}";

            $local_control_display = !empty($check['local_control_number']) && $check['local_control_number'] !== 'Not assigned' ?
                " | Local Control: {$check['local_control_number']}" : "";

            sendJsonError("POTENTIAL_DUPLICATE: " . $check['message'] .
                " {$id_number_display}{$local_control_display}. Please verify if this is a new applicant.");
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

    $address_id = $conn->lastInsertId();

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

    $economic_status_id = $conn->lastInsertId();

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

    $health_condition_id = $conn->lastInsertId();

    // Check for senior illness if applicable
    $senior_illness_inserted = false;
    if (isset($data['senior_illness']) && is_array($data['senior_illness'])) {
        foreach ($data['senior_illness'] as $illness) {
            if (!empty(trim($illness))) {
                $stmt = $conn->prepare("
                    INSERT INTO senior_illness (applicant_id, illness_name)
                    VALUES (?, ?)
                ");
                $stmt->execute([$applicant_id, trim($illness)]);
                $senior_illness_inserted = true;
            }
        }
    }

    $conn->commit();

    // Log successful registration
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
        'barangay' => $data['brgy'] ?? '',
        'municipality' => $data['municipality'] ?? '',
        'province' => $data['province'] ?? '',
        'registered_by' => $userName,
        'registered_by_id' => $userId,
        'registration_date' => $date_of_registration,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'record_ids' => [
            'registration_id' => $registration_id,
            'address_id' => $address_id,
            'economic_status_id' => $economic_status_id,
            'health_condition_id' => $health_condition_id
        ],
        'has_illnesses' => $senior_illness_inserted,
        'is_pensioner' => $data['is_pensioner'] ?? 0,
        'has_family_support' => $data['has_family_support'] ?? 0,
        'validation_status' => 'For Validation',
        'system_status' => 'Active'
    ]);

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
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log registration error
    $logger->log('ERROR', 'Registration failed - Database error', [
        'applicant_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
        'id_number_attempted' => $id_number ?? '',
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'registered_by' => $userName,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Database Error'
    ]);

    error_log("Registration database error: " . $e->getMessage());
    sendJsonError("Registration failed due to a database error. Please try again.");
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log registration error
    $logger->log('ERROR', 'Registration failed - Application error', [
        'applicant_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
        'id_number_attempted' => $id_number ?? '',
        'error_message' => $e->getMessage(),
        'registered_by' => $userName,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Application Error'
    ]);

    error_log("Registration error: " . $e->getMessage());
    sendJsonError("Registration failed: " . $e->getMessage());
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
