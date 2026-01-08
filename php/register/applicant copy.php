<?php
// applicant.php - UPDATED SESSION HANDLING
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// -----------------------------
// ENHANCED SESSION HANDLING FOR BOTH STAFF AND ADMIN
// -----------------------------
function initializeSessionForApplicant()
{
    error_log("=== initializeSessionForApplicant Started ===");
    
    // Check if session is already started
    if (session_status() === PHP_SESSION_NONE) {
        error_log("Session not started, initializing...");
        
        // First, check if we have session context from URL or POST
        $sessionContext = null;
        
        // Check for session_context in POST data (from fetch request)
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $postData = json_decode($input, true);
            if ($postData && isset($postData['session_context'])) {
                $sessionContext = $postData['session_context'];
                error_log("Found session_context in POST data: " . $sessionContext);
            }
        }
        
        // Check for session_context in GET parameters
        if (!$sessionContext && isset($_GET['session_context'])) {
            $sessionContext = $_GET['session_context'];
            error_log("Found session_context in GET: " . $sessionContext);
        }
        
        // If we have a session context, use it for session name
        if ($sessionContext && preg_match('/^[a-zA-Z0-9_-]+$/', $sessionContext)) {
            $sessionName = 'SESS_' . $sessionContext;
            session_name($sessionName);
            error_log("Set session name to: " . $sessionName);
        }
        
        // Check for existing session cookie
        $hasSessionCookie = false;
        if (isset($_COOKIE[session_name()])) {
            error_log("Found session cookie: " . session_name());
            $hasSessionCookie = true;
        } else {
            // Check all possible session cookies
            foreach ($_COOKIE as $name => $value) {
                if (strpos($name, 'SESS_') === 0) {
                    session_name($name);
                    error_log("Found alternative session cookie: " . $name);
                    $hasSessionCookie = true;
                    break;
                }
            }
        }
        
        // Start session
        @session_start();
        
        if ($hasSessionCookie) {
            error_log("Session started with existing cookie. ID: " . session_id());
        } else {
            error_log("Session started with new ID: " . session_id());
        }
        
        // Force session context if not set
        if (!isset($_SESSION['session_context'])) {
            if ($sessionContext) {
                $_SESSION['session_context'] = $sessionContext;
            } else {
                // Determine from URL pattern
                $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
                if (strpos($currentUrl, '/staff/') !== false || strpos($currentUrl, 'staff_') !== false) {
                    $_SESSION['session_context'] = 'staff';
                } else {
                    $_SESSION['session_context'] = 'admin';
                }
            }
            error_log("Set session_context to: " . $_SESSION['session_context']);
        }
        
        // Ensure we have basic session structure
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 0;
        }
        
        if (!isset($_SESSION['session_initialized'])) {
            $_SESSION['session_initialized'] = true;
            $_SESSION['session_start_time'] = time();
        }
        
        error_log("Session initialized. Context: " . ($_SESSION['session_context'] ?? 'none') . 
                  ", User ID: " . ($_SESSION['user_id'] ?? 'none'));
    } else {
        error_log("Session already started. ID: " . session_id() . 
                  ", Context: " . ($_SESSION['session_context'] ?? 'none'));
    }
    
    return true;
}

// Initialize session
initializeSessionForApplicant();

error_log("=== APPLICANT.PHP STARTED ===");
error_log("Session ID: " . (session_id() ?: 'NO SESSION'));
error_log("Session Status: " . session_status());
error_log("Session Data: " . json_encode($_SESSION));
error_log("Cookies: " . json_encode($_COOKIE));

// If session doesn't have user data, try to get from request
if (empty($_SESSION) || (!isset($_SESSION['user_id']) && !isset($_SESSION['id']))) {
    error_log("Session empty or no user data, checking for alternative authentication");
    
    // Check if this is an AJAX request with user data
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if ($data && isset($data['staff_user_id'])) {
        $_SESSION['staff_user_id'] = $data['staff_user_id'];
        $_SESSION['user_id'] = $data['staff_user_id'];
        $_SESSION['session_context'] = 'staff';
        error_log("Set staff user from AJAX data: " . $data['staff_user_id']);
    }
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
    error_log("Database connection failed");
    sendJsonError("Database connection failed", 500);
}

// -----------------------------
// SIMPLIFIED USER ID RESOLUTION - FIXED VERSION
// -----------------------------
function getCurrentUserId($conn)
{
    error_log("=== getCurrentUserId Started ===");
    
    // Log current session for debugging
    error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'none'));
    error_log("Session staff_user_id: " . ($_SESSION['staff_user_id'] ?? 'none'));
    error_log("Session admin_user_id: " . ($_SESSION['admin_user_id'] ?? 'none'));
    
    // Method 1: Direct session lookup
    if (isset($_SESSION['session_context'])) {
        $context = $_SESSION['session_context'];
        error_log("Found session context: " . $context);
        
        if ($context === 'staff') {
            // For staff, try these in order:
            $staffIds = ['staff_user_id', 'user_id', 'id'];
            foreach ($staffIds as $idKey) {
                if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                    $userId = $_SESSION[$idKey];
                    error_log("Found staff ID in session[{$idKey}]: " . $userId);
                    
                    // Verify it's actually a staff user
                    if (verifyUserIsStaff($conn, $userId)) {
                        $_SESSION['staff_user_id'] = $userId;
                        return $userId;
                    }
                }
            }
        } else if ($context === 'admin') {
            // For admin, try these in order:
            $adminIds = ['admin_user_id', 'user_id', 'id'];
            foreach ($adminIds as $idKey) {
                if (isset($_SESSION[$idKey]) && !empty($_SESSION[$idKey])) {
                    $userId = $_SESSION[$idKey];
                    error_log("Found admin ID in session[{$idKey}]: " . $userId);
                    $_SESSION['admin_user_id'] = $userId;
                    return $userId;
                }
            }
        }
    }
    
    // Method 2: Check username in session
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $userId = findUserIdByUsername($conn, $_SESSION['username']);
        if ($userId) {
            error_log("Found user ID from username: " . $userId);
            
            // Determine context from user type
            $userType = getUserType($conn, $userId);
            if (isStaffUserType($userType)) {
                $_SESSION['session_context'] = 'staff';
                $_SESSION['staff_user_id'] = $userId;
            } else if (isAdminUserType($userType)) {
                $_SESSION['session_context'] = 'admin';
                $_SESSION['admin_user_id'] = $userId;
            }
            
            $_SESSION['user_id'] = $userId;
            return $userId;
        }
    }
    
    // Method 3: URL-based detection
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
    error_log("Current URL: " . $currentUrl);
    
    if (strpos($currentUrl, '/staff/') !== false || strpos($currentUrl, 'staff_') !== false) {
        error_log("Detected staff section from URL");
        $staffId = findAnyStaffUser($conn);
        if ($staffId) {
            $_SESSION['session_context'] = 'staff';
            $_SESSION['staff_user_id'] = $staffId;
            $_SESSION['user_id'] = $staffId;
            return $staffId;
        }
    }
    
    // Method 4: Default based on current session data
    if (isset($_SESSION['session_context']) && $_SESSION['session_context'] === 'staff') {
        $staffId = findAnyStaffUser($conn);
        return $staffId ?: 57; // Fallback to admin if no staff found
    }
    
    // Method 5: Default to Admin
    error_log("Defaulting to Admin ID: 57");
    $_SESSION['session_context'] = 'admin';
    $_SESSION['admin_user_id'] = 57;
    $_SESSION['user_id'] = 57;
    return 57;
}

// Helper functions (keep your existing ones but add this new one)
function verifyUserIsStaff($conn, $userId)
{
    try {
        $stmt = $conn->prepare(
            "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $isStaff = isStaffUserType($user['user_type']);
            error_log("User {$userId} is staff: " . ($isStaff ? 'YES' : 'NO'));
            return $isStaff;
        }
    } catch (Exception $e) {
        error_log("Error verifying staff user: " . $e->getMessage());
    }
    
    return false;
}

function getUserType($conn, $userId)
{
    try {
        $stmt = $conn->prepare(
            "SELECT user_type FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ? $user['user_type'] : '';
    } catch (Exception $e) {
        error_log("Error getting user type: " . $e->getMessage());
        return '';
    }
}

function isStaffUserType($userType)
{
    $userType = strtolower($userType);
    return (
        strpos($userType, 'staff') !== false ||
        strpos($userType, 'data entry') !== false ||
        strpos($userType, 'viewer') !== false
    );
}

function isAdminUserType($userType)
{
    $userType = strtolower($userType);
    return (
        strpos($userType, 'admin') !== false ||
        strpos($userType, 'super admin') !== false
    );
}

function findUserIdByUsername($conn, $username)
{
    try {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? AND status = 'active'"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ? $user['id'] : null;
    } catch (Exception $e) {
        error_log("Error finding user by username: " . $e->getMessage());
        return null;
    }
}

function findAnyStaffUser($conn)
{
    try {
        $stmt = $conn->prepare(
            "SELECT id FROM users 
             WHERE (user_type LIKE '%Staff%' OR user_type LIKE '%Data Entry%' OR user_type LIKE '%Viewer%')
             AND status = 'active' 
             ORDER BY id ASC 
             LIMIT 1"
        );
        $stmt->execute();
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $staff ? $staff['id'] : null;
    } catch (Exception $e) {
        error_log("Error finding any staff user: " . $e->getMessage());
        return null;
    }
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
} else {
    // Try to get from database
    try {
        $stmt = $conn->prepare("SELECT firstname, lastname, username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (!empty($user['firstname']) && !empty($user['lastname'])) {
                $userName = $user['firstname'] . ' ' . $user['lastname'];
            } elseif (!empty($user['username'])) {
                $userName = $user['username'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting user name: " . $e->getMessage());
    }
}

error_log("Final resolved - User ID: $userId, User Name: $userName, Context: " . ($_SESSION['session_context'] ?? 'none'));


// -----------------------------
// GET USER NAME WITH CONTEXT
// -----------------------------
function getUserNameWithContext($conn, $userId)
{
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.firstname, 
                u.lastname, 
                u.middlename, 
                u.user_type,
                COALESCE(
                    CONCAT(u.firstname, ' ', u.lastname),
                    u.username
                ) as display_name
            FROM users u
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $displayName = '';

            if (!empty($user['firstname']) && !empty($user['lastname'])) {
                $displayName = $user['firstname'] . ' ' . $user['lastname'];
                if (!empty($user['middlename'])) {
                    $displayName = $user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname'];
                }
            } else {
                $displayName = $user['username'] ?? 'Unknown User';
            }

            // Add user type in parentheses
            return $displayName . ' (' . $user['user_type'] . ')';
        }
    } catch (Exception $e) {
        error_log("Error getting user name: " . $e->getMessage());
    }

    return 'Unknown User';
}

error_log("Final User ID: $userId, User Name: $userName");

// -----------------------------
// LOAD ActivityLogger
// -----------------------------
$logger = null;
$activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';

error_log("Looking for ActivityLogger at: $activityLoggerPath");

if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
    error_log("ActivityLogger.php loaded successfully");

    if (class_exists('ActivityLogger')) {
        try {
            $logger = new ActivityLogger($conn);
            error_log("ActivityLogger initialized successfully");
        } catch (Exception $e) {
            error_log("Failed to initialize ActivityLogger: " . $e->getMessage());
        }
    } else {
        error_log("ActivityLogger class not found after loading file");
    }
} else {
    error_log("ActivityLogger.php NOT FOUND at: $activityLoggerPath");
}

// If still no logger, create simple one
if (!$logger) {
    error_log("Creating SimpleLogger as fallback");

    class SimpleLogger
    {
        private $conn;

        public function __construct($conn)
        {
            $this->conn = $conn;
        }

        public function log($type, $desc, $details = null)
        {
            global $userId, $userName;

            error_log("SimpleLogger: Attempting to log - $type: $desc");
            error_log("SimpleLogger - Using User ID: $userId, Name: $userName");

            try {
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                $query = "INSERT INTO activity_logs 
                         (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";

                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([
                    $userId,
                    $type,
                    $desc,
                    $detailsJson,
                    $ipAddress,
                    substr($userAgent, 0, 500)
                ]);

                if ($result) {
                    $lastId = $this->conn->lastInsertId();
                    error_log("SimpleLogger: Log inserted successfully! ID: $lastId");
                    return true;
                } else {
                    $error = $stmt->errorInfo();
                    error_log("SimpleLogger: Query failed - " . json_encode($error));
                    return false;
                }
            } catch (Exception $e) {
                error_log("SimpleLogger error: " . $e->getMessage());
                return false;
            }
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

error_log("Applicant.php - User ID: $userId, User Name: $userName");

// -----------------------------
// READ JSON BODY
// -----------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

error_log("Received data: " . json_encode($data));

if (!$data) {
    error_log("No JSON data received");
    sendJsonError("Invalid or missing JSON data.");
}
// -----------------------------
// READ JSON BODY
// -----------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

error_log("Received data: " . json_encode($data));

if (!$data) {
    error_log("No JSON data received");
    sendJsonError("Invalid or missing JSON data.");
}

// Check for staff/admin user data in the request
error_log("Checking for user data in request...");
if (isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
    error_log("Found staff_user_id in request: " . $data['staff_user_id']);
    $_SESSION['staff_user_id'] = $data['staff_user_id'];
    $_SESSION['user_id'] = $data['staff_user_id'];
    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'staff';
    }
} elseif (isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
    error_log("Found admin_user_id in request: " . $data['admin_user_id']);
    $_SESSION['admin_user_id'] = $data['admin_user_id'];
    $_SESSION['user_id'] = $data['admin_user_id'];
    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'admin';
    }
}

// Also check for session_context in request
if (isset($data['session_context']) && !empty($data['session_context'])) {
    error_log("Found session_context in request: " . $data['session_context']);
    $_SESSION['session_context'] = $data['session_context'];
}

error_log("After processing request - Session context: " . ($_SESSION['session_context'] ?? 'none'));
error_log("After processing request - User ID: " . ($_SESSION['user_id'] ?? 'none'));
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

// Function to check for potential duplicates
function checkForDuplicates($conn, $data)
{
    $duplicates = [];

    // Check 1: Exact match on name, birthdate, and gender
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
        return $duplicates;
    }

    return $duplicates;
}

try {
    error_log("Starting transaction");
    $conn->beginTransaction();

    $applicantName = trim($data['fname'] ?? '') . ' ' . trim($data['lname'] ?? '');
    $birthDate = $data['b_date'] ?? '';

    error_log("Processing applicant: $applicantName, Birthdate: $birthDate");

    // STEP 0: Check for ID number uniqueness
    $id_number = trim($data['id_number'] ?? '');
    if (empty($id_number)) {
        $conn->rollBack();

        error_log("ID number missing for applicant: $applicantName");
        if ($logger) {
            $logger->log('ERROR', 'Registration attempt with missing ID number', [
                'applicant_name' => $applicantName,
                'birth_date' => $birthDate,
                'attempted_by' => $userName,
                'error_type' => 'Missing ID Number'
            ]);
        }

        sendJsonError("ID Number is required. Please enter an ID Number.");
    }

    error_log("Checking ID number: $id_number");

    // Check if ID number already exists
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

        error_log("Duplicate ID number found: $id_number assigned to $existingName");

        if ($logger) {
            $logger->log('ERROR', 'Attempted to use duplicate ID number', [
                'attempted_id' => $id_number,
                'applicant_name' => $applicantName,
                'existing_applicant_id' => $existing['applicant_id'] ?? null,
                'existing_applicant_name' => $existingName,
                'existing_birth_date' => $existing['birth_date'] ?? null,
                'attempted_by' => $userName
            ]);
        }

        sendJsonError("ID Number '{$id_number}' is already assigned to {$existingName} (Born: {$existing['birth_date']}). Please use a different ID Number.");
    }

    // STEP 1: Check for local control number uniqueness
    $local_control_number = trim($data['local_control_number'] ?? '');
    if (!empty($local_control_number) && $local_control_number !== "Auto-generated") {
        if (!isLocalControlNumberUnique($conn, $local_control_number)) {
            $conn->rollBack();

            error_log("Duplicate local control number: $local_control_number");

            if ($logger) {
                $logger->log('ERROR', 'Attempted to use duplicate local control number', [
                    'attempted_local_control' => $local_control_number,
                    'applicant_name' => $applicantName,
                    'attempted_by' => $userName
                ]);
            }

            sendJsonError("Local Control Number '{$local_control_number}' already exists. Please use a different number.");
        }
    }

    // STEP 2: Check for applicant duplicates
    $duplicateChecks = checkForDuplicates($conn, $data);

    if (!empty($duplicateChecks)) {
        $conn->rollBack();

        error_log("Duplicate applicant found for: $applicantName");

        if ($logger) {
            $logger->log('ERROR', 'Duplicate applicant check failed', [
                'applicant_name' => $applicantName,
                'birth_date' => $birthDate,
                'duplicate_type' => $duplicateChecks[0]['type'] ?? 'unknown',
                'existing_applicant_id' => $duplicateChecks[0]['applicant_id'] ?? null,
                'existing_id_number' => $duplicateChecks[0]['id_number'] ?? null,
                'existing_local_control' => $duplicateChecks[0]['local_control_number'] ?? null,
                'attempted_by' => $userName
            ]);
        }

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

    // Calculate accurate current_age from birth date
    $current_age = calculateCurrentAge($data['b_date'] ?? null);
    error_log("Calculated age: $current_age");

    // Generate local control number if not provided
    if (empty($local_control_number) || $local_control_number === "Auto-generated") {
        $local_control_number = generateLocalControlNumber($conn);
        error_log("Generated local control number: $local_control_number");
    }

    $date_of_registration = $data['date_of_registration'] ?? date('Y-m-d');
    error_log("Date of registration: $date_of_registration");

    // STEP 3: Insert into applicants (main table)
    $stmt = $conn->prepare("
        INSERT INTO applicants (
            last_name, first_name, middle_name, suffix, gender, age, current_age, 
            civil_status, birth_date, citizenship, religion, birth_place, 
            educational_attainment, living_arrangement, validation, status, age_last_updated, date_created
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Validation', 'Active', CURDATE(), NOW())
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
        $data['living_arrangement'] ?? null
    ];

    error_log("Inserting applicant with data: " . json_encode($insertData));

    $stmt->execute($insertData);
    $applicant_id = $conn->lastInsertId();
    error_log("Applicant inserted with ID: $applicant_id");

    // STEP 4: Insert into applicant_registration_details
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

    // STEP 5: Insert into applicant_demographics
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
    error_log("Transaction committed successfully");

    // Log successful registration
    if ($logger) {
        $logResult = $logger->log('REGISTER_SENIOR', 'New senior citizen registered successfully', [
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

        error_log("Log result: " . ($logResult ? "SUCCESS" : "FAILED"));
    } else {
        error_log("No logger available to log success");
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
        error_log("Transaction rolled back");
    }

    // Log registration error
    if ($logger) {
        $logger->log('ERROR', 'Registration failed - Database error', [
            'applicant_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
            'id_number_attempted' => $id_number ?? '',
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'registered_by' => $userName,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'error_type' => 'Database Error'
        ]);
    }

    sendJsonError("Registration failed due to a database error. Please try again.");
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());

    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }

    // Log registration error
    if ($logger) {
        $logger->log('ERROR', 'Registration failed - Application error', [
            'applicant_name' => trim($data['lname'] ?? '') . ', ' . trim($data['fname'] ?? ''),
            'id_number_attempted' => $id_number ?? '',
            'error_message' => $e->getMessage(),
            'registered_by' => $userName,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'error_type' => 'Application Error'
        ]);
    }

    sendJsonError("Registration failed: " . $e->getMessage());
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
