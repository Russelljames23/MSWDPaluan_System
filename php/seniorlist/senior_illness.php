<?php
// senior_illness.php - FIXED VERSION WITH ENHANCED STAFF CONTEXT DETECTION

// Start session early for ActivityLogger
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include './conn.php'; // ✅ PDO connection

header("Content-Type: application/json; charset=UTF-8");

// ✅ CORS Handling
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Helper function for JSON responses
function jsonExit($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ✅ Safety check for DB connection
if (!isset($conn) || !($conn instanceof PDO)) {
    jsonExit(['success' => false, 'error' => "Database connection not available. Check conn.php"], 500);
}

// ------------------------------
// HELPER FUNCTIONS - ENHANCED FOR BETTER STAFF DETECTION
// ------------------------------
function getCurrentUserInfo($conn)
{
    // Log for debugging
    error_log("=== SESSION DEBUG senior_illness.php ===");
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("Session ID: " . session_id());
    error_log("SESSION contents: " . print_r($_SESSION, true));
    
    // IMPORTANT: Check if this is a staff page request by looking at referrer or URL patterns
    $isStaffRequest = false;
    
    // Check referrer for staff pages
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referrer = $_SERVER['HTTP_REFERER'];
        if (strpos($referrer, 'staff_') !== false || 
            strpos($referrer, 'staff/') !== false ||
            strpos($referrer, '/staff') !== false) {
            $isStaffRequest = true;
            error_log("Detected staff request from referrer: " . $referrer);
        }
    }
    
    // Also check for explicit session_context parameter
    $sessionContext = null;
    
    // 1. Check GET parameters first (for view requests)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['session_context'])) {
        $sessionContext = $_GET['session_context'];
        $_SESSION['session_context'] = $sessionContext;
        error_log("Found session_context in GET: " . $sessionContext);
        
        // Set appropriate user ID based on context
        if ($sessionContext === 'staff' && isset($_GET['staff_user_id'])) {
            $_SESSION['staff_user_id'] = intval($_GET['staff_user_id']);
            $_SESSION['user_id'] = intval($_GET['staff_user_id']);
            error_log("Set staff_user_id from GET: " . $_GET['staff_user_id']);
        } elseif ($sessionContext === 'admin' && isset($_GET['admin_user_id'])) {
            $_SESSION['admin_user_id'] = intval($_GET['admin_user_id']);
            $_SESSION['user_id'] = intval($_GET['admin_user_id']);
            error_log("Set admin_user_id from GET: " . $_GET['admin_user_id']);
        }
    }
    
    // 2. Check for JSON data (for POST requests)
    $jsonData = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = file_get_contents('php://input');
        $jsonData = json_decode($input, true);
        
        if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
            if (isset($jsonData['session_context'])) {
                $sessionContext = $jsonData['session_context'];
                $_SESSION['session_context'] = $sessionContext;
                error_log("Found session_context in JSON: " . $sessionContext);
                
                if ($sessionContext === 'staff' && isset($jsonData['staff_user_id'])) {
                    $_SESSION['staff_user_id'] = intval($jsonData['staff_user_id']);
                    $_SESSION['user_id'] = intval($jsonData['staff_user_id']);
                    error_log("Set staff_user_id from JSON: " . $jsonData['staff_user_id']);
                } elseif ($sessionContext === 'admin' && isset($jsonData['admin_user_id'])) {
                    $_SESSION['admin_user_id'] = intval($jsonData['admin_user_id']);
                    $_SESSION['user_id'] = intval($jsonData['admin_user_id']);
                    error_log("Set admin_user_id from JSON: " . $jsonData['admin_user_id']);
                }
            }
        }
    }
    
    // 3. If no explicit context, try to infer from session data
    if (empty($sessionContext)) {
        // Check if we have staff_user_id in session (strong indicator of staff context)
        if (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
            $sessionContext = 'staff';
            error_log("Inferred staff context from existing staff_user_id in session");
        }
        // Check if we have admin_user_id in session
        elseif (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
            $sessionContext = 'admin';
            error_log("Inferred admin context from existing admin_user_id in session");
        }
        // Check user_type in session
        elseif (isset($_SESSION['user_type'])) {
            $userType = strtolower($_SESSION['user_type']);
            if (strpos($userType, 'staff') !== false) {
                $sessionContext = 'staff';
                error_log("Inferred staff context from user_type: " . $userType);
            } else {
                $sessionContext = 'admin';
                error_log("Inferred admin context from user_type: " . $userType);
            }
        }
        // Last resort: check if it's likely a staff request
        elseif ($isStaffRequest) {
            $sessionContext = 'staff';
            error_log("Inferred staff context from request analysis");
        }
        else {
            $sessionContext = 'admin'; // Default to admin
            error_log("Defaulting to admin context");
        }
        
        $_SESSION['session_context'] = $sessionContext;
    }
    
    // Determine user ID based on context
    $userId = 0;
    $userName = 'Unknown User';
    $userType = 'Unknown';
    
    if ($sessionContext === 'staff') {
        // Try to get staff user ID from session
        if (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
            $userId = $_SESSION['staff_user_id'];
            error_log("Using staff_user_id: " . $userId);
        } 
        elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            error_log("Using user_id as staff ID: " . $userId);
        }
        // Fallback: if no staff ID but we have admin ID, switch context
        elseif (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
            $userId = $_SESSION['admin_user_id'];
            $sessionContext = 'admin';
            $_SESSION['session_context'] = 'admin';
            error_log("No staff ID found, switching to admin context with ID: " . $userId);
        }
    } else {
        // Admin context
        if (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
            $userId = $_SESSION['admin_user_id'];
            error_log("Using admin_user_id: " . $userId);
        }
        elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            error_log("Using user_id as admin ID: " . $userId);
        }
    }
    
    // Default to admin ID 57 if no ID found (for testing/fallback)
    if ($userId === 0) {
        // Try to get from database if we have email/username in session
        if (isset($_SESSION['email']) || isset($_SESSION['username'])) {
            try {
                $email = $_SESSION['email'] ?? $_SESSION['username'] ?? '';
                $stmt = $conn->prepare(
                    "SELECT id, user_type FROM users WHERE (email = ? OR username = ?) AND status = 'active' LIMIT 1"
                );
                $stmt->execute([$email, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $userId = $user['id'];
                    $dbUserType = strtolower($user['user_type']);
                    
                    if (strpos($dbUserType, 'staff') !== false) {
                        $sessionContext = 'staff';
                        $_SESSION['session_context'] = 'staff';
                        $_SESSION['staff_user_id'] = $userId;
                    } else {
                        $sessionContext = 'admin';
                        $_SESSION['session_context'] = 'admin';
                        $_SESSION['admin_user_id'] = $userId;
                    }
                    error_log("Found user in DB: ID=" . $userId . ", type=" . $user['user_type']);
                }
            } catch (Exception $e) {
                error_log("Error checking user in DB: " . $e->getMessage());
            }
        }
        
        // Ultimate fallback
        if ($userId === 0) {
            $userId = 57; // Default admin ID
            $sessionContext = 'admin';
            $_SESSION['admin_user_id'] = 57;
            $_SESSION['user_id'] = 57;
            error_log("Using default admin ID: 57");
        }
    }
    
    // Get user name from session
    if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
        $userName = $_SESSION['fullname'];
    } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
    } elseif (isset($_SESSION['username'])) {
        $userName = $_SESSION['username'];
    }
    
    // Get user type from session
    if ($sessionContext === 'staff') {
        $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Staff';
    } else {
        $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Admin';
    }
    
    // Verify user status in database if needed
    if ($userId && $conn) {
        try {
            $stmt = $conn->prepare(
                "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $dbUserType = strtolower($user['user_type']);
                // If DB says admin but we think staff, correct it
                if (strpos($dbUserType, 'admin') !== false && $sessionContext === 'staff') {
                    $sessionContext = 'admin';
                    $userType = 'Admin';
                    $_SESSION['session_context'] = 'admin';
                    error_log("User {$userId} is admin in DB, correcting context to admin");
                }
                // If DB says staff but we think admin, correct it (less likely but possible)
                elseif (strpos($dbUserType, 'staff') !== false && $sessionContext === 'admin') {
                    $sessionContext = 'staff';
                    $userType = 'Staff';
                    $_SESSION['session_context'] = 'staff';
                    error_log("User {$userId} is staff in DB, correcting context to staff");
                }
            }
        } catch (Exception $e) {
            error_log("Error verifying user in DB: " . $e->getMessage());
        }
    }
    
    $userInfo = [
        'id' => $userId,
        'name' => $userName,
        'type' => $userType,
        'context' => $sessionContext,
        'is_staff' => ($sessionContext === 'staff')
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

// Get senior name for logging
function getSeniorName($conn, $applicant_id)
{
    try {
        $stmt = $conn->prepare("SELECT first_name, last_name FROM applicants WHERE applicant_id = ?");
        $stmt->execute([$applicant_id]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($applicant) {
            return trim(($applicant['last_name'] ?? '') . ', ' . ($applicant['first_name'] ?? ''));
        }
    } catch (Exception $e) {
        error_log("Error fetching senior name: " . $e->getMessage());
    }
    return 'Unknown Senior';
}

$method = $_SERVER['REQUEST_METHOD'];

// =========================================================
// 🧾 GET — Fetch senior illness records + health condition + application date
// =========================================================
if ($method === 'GET') {
    $applicant_id = isset($_GET['applicant_id']) ? (int)$_GET['applicant_id'] : 0;
    if ($applicant_id <= 0) {
        jsonExit(['success' => false, 'error' => 'Invalid or missing applicant_id'], 400);
    }

    try {
        $userInfo = getCurrentUserInfo($conn);
        
        // 1️⃣ Fetch illness history
        $stmt = $conn->prepare("
            SELECT illness_id, illness_name, illness_date, created_at, updated_at
            FROM senior_illness
            WHERE applicant_id = :applicant_id
            ORDER BY illness_date DESC, created_at DESC
        ");
        $stmt->execute(['applicant_id' => $applicant_id]);
        $illnesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2️⃣ Fetch health condition
        $stmt2 = $conn->prepare("
            SELECT has_existing_illness, illness_details, hospitalized_last6mos
            FROM health_condition
            WHERE applicant_id = :applicant_id
            LIMIT 1
        ");
        $stmt2->execute(['applicant_id' => $applicant_id]);
        $health_condition = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [
            'has_existing_illness' => null,
            'illness_details' => null,
            'hospitalized_last6mos' => null
        ];

        // 3️⃣ Fetch applicant's date
        $stmt3 = $conn->prepare("
            SELECT date_created
            FROM applicants
            WHERE applicant_id = :applicant_id
            LIMIT 1
        ");
        $stmt3->execute(['applicant_id' => $applicant_id]);
        $appRow = $stmt3->fetch(PDO::FETCH_ASSOC);
        $application_date = $appRow['date_created'] ?? null;

        // Log view activity with proper context
        $seniorName = getSeniorName($conn, $applicant_id);
        
        // Determine activity type based on user context
        if ($userInfo['context'] === 'staff') {
            $activityType = 'STAFF_VIEW_ILLNESS';
            $description = 'Staff viewed senior illness records';
        } else {
            $activityType = 'VIEW_ILLNESS';
            $description = 'Admin viewed senior illness records';
        }
        
        $logDetails = [
            'applicant_id' => $applicant_id,
            'applicant_name' => $seniorName,
            'viewed_by' => $userInfo['name'],
            'viewed_by_id' => $userInfo['id'],
            'user_type' => $userInfo['type'],
            'user_context' => $userInfo['context'],
            'session_context' => $_SESSION['session_context'] ?? 'unknown',
            'total_illnesses' => count($illnesses),
            'has_health_condition' => !empty($health_condition['illness_details']),
            'request_source' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ];

        $logResult = logActivity($logger, $activityType, $description, $logDetails);

        if (!$logResult) {
            error_log("Failed to log illness view activity");
        }

        // ✅ 4️⃣ Return combined data
        jsonExit([
            'success' => true,
            'illnesses' => $illnesses,
            'health_condition' => $health_condition,
            'application_date' => $application_date,
            'user_context' => $userInfo['context'],
            'user_info' => [
                'id' => $userInfo['id'],
                'name' => $userInfo['name'],
                'type' => $userInfo['type']
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Error fetching illness data: " . $e->getMessage());
        jsonExit(['success' => false, 'error' => 'Failed to load illness data: ' . $e->getMessage()], 500);
    }

    // =========================================================
    // 💾 POST — Add new illness record
    // =========================================================
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        // Try form data as fallback
        $data = $_POST;
    }
    
    if (!$data) jsonExit(['success' => false, 'error' => 'Invalid payload'], 400);

    $applicant_id = (int)($data['applicant_id'] ?? 0);
    $illness_name = trim($data['illness_name'] ?? '');
    $illness_date = trim($data['illness_date'] ?? '');
    
    // Get user info BEFORE validation so we can detect context
    $userInfo = getCurrentUserInfo($conn);

    if ($applicant_id <= 0 || !$illness_name || !$illness_date) {
        jsonExit(['success' => false, 'error' => 'Missing required fields'], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $illness_date)) {
        jsonExit(['success' => false, 'error' => 'illness_date must be YYYY-MM-DD'], 400);
    }

    try {
        $seniorName = getSeniorName($conn, $applicant_id);

        // Begin transaction
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO senior_illness (applicant_id, illness_name, illness_date, created_at, updated_at)
            VALUES (:applicant_id, :illness_name, :illness_date, NOW(), NOW())
        ");
        $stmt->execute([
            'applicant_id' => $applicant_id,
            'illness_name' => $illness_name,
            'illness_date' => $illness_date
        ]);

        $illness_id = $conn->lastInsertId();

        $conn->commit();

        // Log add illness activity with proper context
        if ($userInfo['context'] === 'staff') {
            $activityType = 'STAFF_ADD_ILLNESS';
            $description = 'Staff added illness record for senior';
        } else {
            $activityType = 'ADD_ILLNESS';
            $description = 'Admin added illness record for senior';
        }
        
        $logDetails = [
            'applicant_id' => $applicant_id,
            'applicant_name' => $seniorName,
            'illness_id' => $illness_id,
            'illness_name' => $illness_name,
            'illness_date' => $illness_date,
            'added_by' => $userInfo['name'],
            'added_by_id' => $userInfo['id'],
            'user_type' => $userInfo['type'],
            'user_context' => $userInfo['context'],
            'session_context' => $_SESSION['session_context'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_source' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ];

        $logResult = logActivity($logger, $activityType, $description, $logDetails);

        if (!$logResult) {
            error_log("Failed to log illness add activity");
        }

        jsonExit([
            'success' => true,
            'message' => 'Illness added successfully',
            'illness_id' => $illness_id,
            'user_context' => $userInfo['context'],
            'logged_as' => $activityType,
            'user_info' => [
                'id' => $userInfo['id'],
                'name' => $userInfo['name'],
                'type' => $userInfo['type']
            ]
        ], 201);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error adding illness: " . $e->getMessage());
        jsonExit(['success' => false, 'error' => 'Failed to add illness record: ' . $e->getMessage()], 500);
    }
    
} else {
    jsonExit(['success' => false, 'error' => 'Method not allowed. Use GET or POST.'], 405);
}