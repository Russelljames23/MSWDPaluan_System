<?php
// senior_edit.php - FIXED VERSION WITH PROPER STAFF/ADMIN DETECTION FOR BOTH GET AND POST
// GET -> fetch applicant (JSON)
// POST -> update applicant (form-data)

// Start session early for ActivityLogger
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

include __DIR__ . '/conn.php'; // make sure this sets $conn as PDO
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($conn) || !($conn instanceof PDO)) {
    echo json_encode(["success" => false, "message" => "Database connection error (expected PDO)."]);
    exit;
}

// ------------------------------
// HELPER FUNCTIONS
// ------------------------------
function sendJsonResponse($success, $message, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    exit;
}

function validateRequiredFields($data, $requiredFields)
{
    $missingFields = [];
    foreach ($requiredFields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $missingFields[] = $label;
        }
    }
    return $missingFields;
}

function calculateCurrentAge($birth_date)
{
    if (!$birth_date) return null;
    try {
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        return $today->diff($birth)->y;
    } catch (Exception $e) {
        error_log("Error calculating age: " . $e->getMessage());
        return null;
    }
}

function getCurrentUserInfo($conn)
{
    // Log session information for debugging
    error_log("=== SESSION DEBUG ===");
    error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'none'));
    error_log("Staff user ID: " . ($_SESSION['staff_user_id'] ?? 'none'));
    error_log("Admin user ID: " . ($_SESSION['admin_user_id'] ?? 'none'));
    error_log("Full name: " . ($_SESSION['fullname'] ?? 'none'));
    error_log("User type: " . ($_SESSION['user_type'] ?? 'none'));

    // Check for GET parameters for session context (for view requests)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['session_context']) && $_GET['session_context'] === 'staff') {
            $_SESSION['session_context'] = 'staff';
            if (isset($_GET['staff_user_id']) && !empty($_GET['staff_user_id'])) {
                $_SESSION['staff_user_id'] = intval($_GET['staff_user_id']);
                $_SESSION['user_id'] = intval($_GET['staff_user_id']);
            }
        } elseif (isset($_GET['session_context']) && $_GET['session_context'] === 'admin') {
            $_SESSION['session_context'] = 'admin';
            if (isset($_GET['admin_user_id']) && !empty($_GET['admin_user_id'])) {
                $_SESSION['admin_user_id'] = intval($_GET['admin_user_id']);
                $_SESSION['user_id'] = intval($_GET['admin_user_id']);
            }
        }
    }

    // Check for POST/JSON data that might contain user context
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);

    if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
        // Check if JSON contains staff context
        if (isset($jsonData['session_context']) && $jsonData['session_context'] === 'staff') {
            $_SESSION['session_context'] = 'staff';
            if (isset($jsonData['staff_user_id'])) {
                $_SESSION['staff_user_id'] = $jsonData['staff_user_id'];
                $_SESSION['user_id'] = $jsonData['staff_user_id'];
            }
        } elseif (isset($jsonData['session_context']) && $jsonData['session_context'] === 'admin') {
            $_SESSION['session_context'] = 'admin';
            if (isset($jsonData['admin_user_id'])) {
                $_SESSION['admin_user_id'] = $jsonData['admin_user_id'];
                $_SESSION['user_id'] = $jsonData['admin_user_id'];
            }
        }
    }

    // Determine user context
    $context = $_SESSION['session_context'] ?? 'admin';
    $userId = 0;
    $userName = 'Unknown User';
    $userType = 'Unknown';

    // Get user ID based on context
    if ($context === 'staff') {
        // Try to get staff user ID
        $staffIds = ['staff_user_id', 'user_id', 'id'];
        foreach ($staffIds as $idKey) {
            if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                $userId = $_SESSION[$idKey];
                break;
            }
        }

        // If no staff ID found but we have admin ID, switch context
        if ($userId === 0 && isset($_SESSION['admin_user_id'])) {
            $userId = $_SESSION['admin_user_id'];
            $context = 'admin';
        }
    } else {
        // Admin context
        $adminIds = ['admin_user_id', 'user_id', 'id'];
        foreach ($adminIds as $idKey) {
            if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                $userId = $_SESSION[$idKey];
                break;
            }
        }
    }

    // Default to admin ID 57 if no ID found
    if ($userId === 0) {
        $userId = 57; // Default admin ID
        $context = 'admin';
        $_SESSION['admin_user_id'] = 57;
        $_SESSION['user_id'] = 57;
    }

    // Get user name
    if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
        $userName = $_SESSION['fullname'];
    } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
    } elseif (isset($_SESSION['username'])) {
        $userName = $_SESSION['username'];
    }

    // Get user type
    if ($context === 'staff') {
        $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Staff';
    } else {
        $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Admin';
    }

    // Verify staff status if needed
    if ($context === 'staff' && $conn) {
        try {
            $stmt = $conn->prepare(
                "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $dbUserType = strtolower($user['user_type']);
                if (strpos($dbUserType, 'admin') !== false) {
                    // User is actually admin in DB, switch context
                    $context = 'admin';
                    $userType = 'Admin';
                    $_SESSION['session_context'] = 'admin';
                }
            }
        } catch (Exception $e) {
            error_log("Error verifying user: " . $e->getMessage());
        }
    }

    $userInfo = [
        'id' => $userId,
        'name' => $userName,
        'type' => $userType,
        'context' => $context
    ];

    error_log("Final user info: " . print_r($userInfo, true));

    return $userInfo;
}

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

function logActivity($logger, $activityType, $description, $details = null)
{
    if ($logger) {
        return $logger->log($activityType, $description, $details);
    }
    return false;
}

// ------------------------------
// GET — Fetch applicant data
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // Fetch applicant
        $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$applicant) {
            sendJsonResponse(false, "Applicant not found", [], 404);
        }

        // Fetch related data
        $tables = [
            'address' => 'addresses',
            'economic' => 'economic_status',
            'health' => 'health_condition'
        ];

        $relatedData = [];
        foreach ($tables as $key => $table) {
            $stmt = $conn->prepare("SELECT * FROM {$table} WHERE applicant_id = :id");
            $stmt->execute([':id' => $id]);
            $relatedData[$key] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Log view activity with proper context
        $userInfo = getCurrentUserInfo($conn);
        $activityType = ($userInfo['context'] === 'staff') ? 'STAFF_VIEW_SENIOR' : 'VIEW_SENIOR';
        $description = ($userInfo['context'] === 'staff')
            ? 'Staff viewed senior citizen details'
            : 'Admin viewed senior citizen details';
        
        logActivity($logger, $activityType, $description, [
            'applicant_id' => $id,
            'applicant_name' => ($applicant['last_name'] ?? '') . ', ' . ($applicant['first_name'] ?? ''),
            'viewed_by' => $userInfo['name'],
            'viewed_by_id' => $userInfo['id'],
            'user_type' => $userInfo['type'],
            'user_context' => $userInfo['context'],
            'session_context' => $_SESSION['session_context'] ?? 'unknown',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);

        sendJsonResponse(true, "Applicant data retrieved successfully", [
            "applicant" => $applicant,
            "address" => $relatedData['address'],
            "economic" => $relatedData['economic'],
            "health" => $relatedData['health']
        ]);
    } catch (Exception $e) {
        error_log("Error fetching data: " . $e->getMessage());
        sendJsonResponse(false, "Error fetching data: " . $e->getMessage(), [], 500);
    }
}

// ------------------------------
// POST — Update applicant data
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for JSON input first, then fall back to form data
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);

    if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
        // JSON input
        $p = $jsonData;

        // Update session with request data for better context detection
        if (isset($p['session_context']) && $p['session_context'] === 'staff') {
            $_SESSION['session_context'] = 'staff';
            if (isset($p['staff_user_id'])) {
                $_SESSION['staff_user_id'] = $p['staff_user_id'];
                $_SESSION['user_id'] = $p['staff_user_id'];
            }
        } elseif (isset($p['session_context']) && $p['session_context'] === 'admin') {
            $_SESSION['session_context'] = 'admin';
            if (isset($p['admin_user_id'])) {
                $_SESSION['admin_user_id'] = $p['admin_user_id'];
                $_SESSION['user_id'] = $p['admin_user_id'];
            }
        }
    } else {
        // Form data
        $p = $_POST;

        // Also check for session_context in form data
        if (isset($p['session_context']) && $p['session_context'] === 'staff') {
            $_SESSION['session_context'] = 'staff';
            if (isset($p['staff_user_id'])) {
                $_SESSION['staff_user_id'] = $p['staff_user_id'];
                $_SESSION['user_id'] = $p['staff_user_id'];
            }
        } elseif (isset($p['session_context']) && $p['session_context'] === 'admin') {
            $_SESSION['session_context'] = 'admin';
            if (isset($p['admin_user_id'])) {
                $_SESSION['admin_user_id'] = $p['admin_user_id'];
                $_SESSION['user_id'] = $p['admin_user_id'];
            }
        }
    }

    if (empty($p['id'])) {
        sendJsonResponse(false, "Missing applicant ID");
    }

    $id = intval($p['id']);
    $userInfo = getCurrentUserInfo($conn);

    // Required fields validation
    $requiredFields = [
        'lname' => 'Last Name',
        'fname' => 'First Name',
        'mname' => 'Middle Name',
        'gender' => 'Gender',
        'age' => 'Age',
        'civil_status' => 'Civil Status',
        'birth_date' => 'Birthdate',
        'citizenship' => 'Citizenship',
        'birth_place' => 'Birthplace',
        'living_arrangement' => 'Living Arrangement'
    ];

    $missingFields = validateRequiredFields($p, $requiredFields);
    if (!empty($missingFields)) {
        sendJsonResponse(false, "Missing required fields: " . implode(', ', $missingFields));
    }

    try {
        // Fetch existing applicant data for comparison and logging
        $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $existingApplicant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingApplicant) {
            sendJsonResponse(false, "Applicant not found", [], 404);
        }

        $conn->beginTransaction();
        $changes = [];
        $applicantName = trim($p['lname'] ?? '') . ', ' . trim($p['fname'] ?? '');

        // --- Update Applicants table ---
        $applicantUpdateFields = [
            'last_name' => ':last_name',
            'first_name' => ':first_name',
            'middle_name' => ':middle_name',
            'gender' => ':gender',
            'age' => ':age',
            'civil_status' => ':civil_status',
            'birth_date' => ':birth_date',
            'citizenship' => ':citizenship',
            'birth_place' => ':birth_place',
            'living_arrangement' => ':living_arrangement'
        ];

        // Track changes for logging
        foreach ($applicantUpdateFields as $field => $param) {
            $newValue = $p[str_replace('_', '', $field)] ?? null;
            $oldValue = $existingApplicant[$field] ?? null;

            if ($newValue != $oldValue) {
                $changes['applicant'][$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        $sql = "UPDATE applicants SET 
            last_name = :last_name,
            first_name = :first_name,
            middle_name = :middle_name,
            gender = :gender,
            age = :age,
            civil_status = :civil_status,
            birth_date = :birth_date,
            citizenship = :citizenship,
            birth_place = :birth_place,
            living_arrangement = :living_arrangement,
            date_modified = NOW()
            WHERE applicant_id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':last_name' => $p['lname'] ?? null,
            ':first_name' => $p['fname'] ?? null,
            ':middle_name' => $p['mname'] ?? null,
            ':gender' => $p['gender'] ?? null,
            ':age' => $p['age'] ?? null,
            ':civil_status' => $p['civil_status'] ?? null,
            ':birth_date' => $p['birth_date'] ?? null,
            ':citizenship' => $p['citizenship'] ?? null,
            ':birth_place' => $p['birth_place'] ?? null,
            ':living_arrangement' => $p['living_arrangement'] ?? null,
            ':id' => $id
        ]);

        // --- Address ---
        $stmt = $conn->prepare("SELECT * FROM addresses WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $existingAddress = $stmt->fetch(PDO::FETCH_ASSOC);

        $addressFields = ['house_no', 'street', 'barangay', 'municipality', 'province'];
        foreach ($addressFields as $field) {
            $newValue = $p[$field] ?? '';
            $oldValue = $existingAddress[$field] ?? '';

            if ($newValue != $oldValue) {
                $changes['address'][$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if ($existingAddress) {
            $sql = "UPDATE addresses SET 
                    house_no = :house_no, 
                    street = :street, 
                    barangay = :barangay, 
                    municipality = :municipality, 
                    province = :province 
                WHERE applicant_id = :id";
        } else {
            $sql = "INSERT INTO addresses (applicant_id, house_no, street, barangay, municipality, province)
                    VALUES (:id, :house_no, :street, :barangay, :municipality, :province)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':house_no' => $p['house_no'] ?? '',
            ':street' => $p['street'] ?? '',
            ':barangay' => $p['barangay'] ?? '',
            ':municipality' => $p['municipality'] ?? '',
            ':province' => $p['province'] ?? ''
        ]);

        // --- Economic Status ---
        $stmt = $conn->prepare("SELECT * FROM economic_status WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $existingEconomic = $stmt->fetch(PDO::FETCH_ASSOC);

        $economicFields = [
            'is_pensioner',
            'pension_amount',
            'pension_source',
            'pension_source_other',
            'has_permanent_income',
            'income_source',
            'has_family_support',
            'support_type',
            'support_cash',
            'support_in_kind'
        ];

        foreach ($economicFields as $field) {
            $newValue = $p[$field] ?? null;
            $oldValue = $existingEconomic[$field] ?? null;

            if ($newValue != $oldValue) {
                $changes['economic'][$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if ($existingEconomic) {
            $sql = "UPDATE economic_status SET
                    is_pensioner = :is_pensioner,
                    pension_amount = :pension_amount,
                    pension_source = :pension_source,
                    pension_source_other = :pension_source_other,
                    has_permanent_income = :has_permanent_income,
                    income_source = :income_source,
                    has_family_support = :has_family_support,
                    support_type = :support_type,
                    support_cash = :support_cash,
                    support_in_kind = :support_in_kind
                WHERE applicant_id = :id";
        } else {
            $sql = "INSERT INTO economic_status (applicant_id, is_pensioner, pension_amount, pension_source, 
                    pension_source_other, has_permanent_income, income_source, has_family_support, 
                    support_type, support_cash, support_in_kind)
                    VALUES (:id, :is_pensioner, :pension_amount, :pension_source, :pension_source_other, 
                    :has_permanent_income, :income_source, :has_family_support, :support_type, 
                    :support_cash, :support_in_kind)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':is_pensioner' => $p['is_pensioner'] ?? null,
            ':pension_amount' => $p['pension_amount'] ?? null,
            ':pension_source' => $p['pension_source'] ?? null,
            ':pension_source_other' => $p['pension_source_other'] ?? null,
            ':has_permanent_income' => $p['has_permanent_income'] ?? null,
            ':income_source' => $p['income_source'] ?? null,
            ':has_family_support' => $p['has_family_support'] ?? null,
            ':support_type' => $p['support_type'] ?? null,
            ':support_cash' => $p['support_cash'] ?? null,
            ':support_in_kind' => $p['support_in_kind'] ?? null
        ]);

        // --- Health Condition ---
        $stmt = $conn->prepare("SELECT * FROM health_condition WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $existingHealth = $stmt->fetch(PDO::FETCH_ASSOC);

        $healthFields = ['has_existing_illness', 'illness_details', 'hospitalized_last6mos'];
        foreach ($healthFields as $field) {
            $newValue = $p[$field] ?? null;
            $oldValue = $existingHealth[$field] ?? null;

            if ($newValue != $oldValue) {
                $changes['health'][$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if ($existingHealth) {
            $sql = "UPDATE health_condition SET 
                    has_existing_illness = :has_existing_illness, 
                    illness_details = :illness_details, 
                    hospitalized_last6mos = :hospitalized_last6mos 
                WHERE applicant_id = :id";
        } else {
            $sql = "INSERT INTO health_condition (applicant_id, has_existing_illness, illness_details, hospitalized_last6mos)
                    VALUES (:id, :has_existing_illness, :illness_details, :hospitalized_last6mos)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':has_existing_illness' => $p['has_existing_illness'] ?? null,
            ':illness_details' => $p['illness_details'] ?? null,
            ':hospitalized_last6mos' => $p['hospitalized_last6mos'] ?? null
        ]);

        // --- Update date_modified ---
        $stmt = $conn->prepare("UPDATE applicants SET date_modified = NOW() WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);

        $conn->commit();

        // Log the update activity with proper context
        $logDetails = [
            'applicant_id' => $id,
            'applicant_name' => $applicantName,
            'updated_by' => $userInfo['name'],
            'updated_by_id' => $userInfo['id'],
            'user_type' => $userInfo['type'],
            'user_context' => $userInfo['context'],
            'session_context' => $_SESSION['session_context'] ?? 'unknown',
            'changes_made' => !empty($changes) ? $changes : 'No changes detected',
            'total_changes' => count($changes, COUNT_RECURSIVE) - count($changes)
        ];

        // Use appropriate activity type based on user context
        $activityType = ($userInfo['context'] === 'staff') ? 'STAFF_UPDATE_SENIOR' : 'UPDATE_SENIOR';
        $description = ($userInfo['context'] === 'staff')
            ? 'Staff updated senior citizen information'
            : 'Admin updated senior citizen information';

        logActivity($logger, $activityType, $description, $logDetails);

        sendJsonResponse(true, "Applicant information updated successfully!", [
            'applicant_id' => $id,
            'applicant_name' => $applicantName,
            'updated_by' => $userInfo['name'],
            'updated_by_type' => $userInfo['type'],
            'updated_by_context' => $userInfo['context'],
            'session_context' => $_SESSION['session_context'] ?? 'unknown',
            'changes' => $changes
        ]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error updating applicant: " . $e->getMessage());
        sendJsonResponse(false, "Error updating data: " . $e->getMessage(), [], 500);
    }
    exit;
}

// Invalid request
sendJsonResponse(false, "Invalid request", [], 400);