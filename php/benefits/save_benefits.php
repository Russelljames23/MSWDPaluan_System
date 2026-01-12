<?php
// save_benefits.php - FINAL FIXED VERSION WITH PROPER STAFF/ADMIN DETECTION
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Start session early for ActivityLogger
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

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

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sendJsonError("Database connection failed: " . $e->getMessage(), 500);
}

// =========================================================
// 🧾 IMPROVED USER INFO DETECTION (Based on senior_edit.php)
// =========================================================
function getCurrentUserInfo($pdo)
{
    // Check for session context in GET parameters first (for view requests)
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

    // Check for JSON data (for POST requests)
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);

    if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
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

    // Determine context
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

    // If still no ID, try to get from email/username
    if ($userId === 0) {
        if (isset($_SESSION['email']) || isset($_SESSION['username'])) {
            try {
                $email = $_SESSION['email'] ?? $_SESSION['username'] ?? '';
                $stmt = $pdo->prepare(
                    "SELECT id, user_type FROM users WHERE (email = ? OR username = ?) AND status = 'active' LIMIT 1"
                );
                $stmt->execute([$email, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $userId = $user['id'];
                    $dbUserType = strtolower($user['user_type']);

                    if (strpos($dbUserType, 'staff') !== false) {
                        $context = 'staff';
                        $_SESSION['session_context'] = 'staff';
                        $_SESSION['staff_user_id'] = $userId;
                    } else {
                        $context = 'admin';
                        $_SESSION['session_context'] = 'admin';
                        $_SESSION['admin_user_id'] = $userId;
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking user in DB: " . $e->getMessage());
            }
        }
    }

    // Default fallback
    if ($userId === 0) {
        $userId = 57; // Default admin ID
        $context = 'admin';
        $_SESSION['admin_user_id'] = 57;
        $_SESSION['user_id'] = 57;
    }

    // Get user details from database
    if ($userId > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT firstname, lastname, middlename, user_type, role_name 
                 FROM users 
                 WHERE id = ? AND status = 'active' 
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Build name WITHOUT (Admin) or (Staff) suffix
                $firstName = $user['firstname'] ?? '';
                $lastName = $user['lastname'] ?? '';

                if (!empty($firstName) && !empty($lastName)) {
                    $userName = $lastName . ', ' . $firstName;
                } else {
                    $userName = 'Unknown User';
                }

                $userType = $user['user_type'] ?? $user['role_name'] ?? 'Unknown';

                // IMPORTANT: Verify if user_type matches context
                $dbUserType = strtolower($userType);
                if ($context === 'staff' && strpos($dbUserType, 'staff') === false) {
                    // User is staff in context but admin in DB, correct context
                    $context = 'admin';
                    $_SESSION['session_context'] = 'admin';
                } elseif ($context === 'admin' && strpos($dbUserType, 'staff') !== false) {
                    // User is admin in context but staff in DB, correct context
                    $context = 'staff';
                    $_SESSION['session_context'] = 'staff';
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching user details: " . $e->getMessage());
        }
    }

    // Get user type from session if not from DB
    if ($userType === 'Unknown') {
        if ($context === 'staff') {
            $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Staff';
        } else {
            $userType = $_SESSION['user_type'] ?? $_SESSION['role_name'] ?? 'Admin';
        }
    }

    $userInfo = [
        'id' => $userId,
        'name' => $userName,
        'type' => $userType,
        'context' => $context,
        'is_staff' => ($context === 'staff')
    ];

    error_log("User Info for Benefits: ID=" . $userId . ", Name=" . $userName . ", Type=" . $userType . ", Context=" . $context);

    return $userInfo;
}

// Get user info
$userInfo = getCurrentUserInfo($pdo);
$userId = $userInfo['id'];
$userName = $userInfo['name'];
$userType = $userInfo['type'];
$sessionContext = $userInfo['context'];

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

// Initialize logger with improved user information
if (class_exists('ActivityLogger')) {
    $logger = new ActivityLogger($pdo);
} else {
    // Create enhanced logger that properly handles staff/admin distinction
    class SimpleLogger
    {
        private $pdo;

        public function __construct($pdo)
        {
            $this->pdo = $pdo;
        }

        public function log($type, $desc, $details = null)
        {
            // Log to file for debugging
            $logMessage = date('Y-m-d H:i:s') . " - Activity: $type - $desc";
            if ($details) {
                $logMessage .= " - Details: " . json_encode($details);
            }
            error_log($logMessage);

            // Log to database
            if ($this->pdo) {
                try {
                    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                    // IMPORTANT: Store the actual user_type from details
                    $userTypeForDisplay = $details['user_type'] ?? 'Unknown';

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute([
                        $details['added_by_id'] ?? 0,
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
    $logger = new SimpleLogger($pdo);
}

// Read JSON input from fetch
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJsonError("Invalid or missing JSON data.");
}

if (!isset($input['benefits']) || !is_array($input['benefits']) || count($input['benefits']) === 0) {
    sendJsonError("No benefits provided. Please enter at least one benefit.");
}

// Validate benefit names
$benefits = $input['benefits'];
$validatedBenefits = [];
$invalidBenefits = [];

foreach ($benefits as $benefit) {
    $trimmedBenefit = trim($benefit);
    if (empty($trimmedBenefit)) {
        $invalidBenefits[] = "(empty)";
        continue;
    }

    if (strlen($trimmedBenefit) > 255) {
        $invalidBenefits[] = "'$trimmedBenefit' (too long)";
        continue;
    }

    $validatedBenefits[] = $trimmedBenefit;
}

if (!empty($invalidBenefits)) {
    sendJsonError("Invalid benefit names: " . implode(', ', $invalidBenefits));
}

if (empty($validatedBenefits)) {
    sendJsonError("No valid benefits provided after validation.");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check for duplicate benefits before insertion
    $duplicates = [];
    $newBenefits = [];

    foreach ($validatedBenefits as $benefit) {
        // Check if benefit already exists (case-insensitive)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM benefits WHERE LOWER(benefit_name) = LOWER(?)");
        $checkStmt->execute([$benefit]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            $duplicates[] = $benefit;
        } else {
            $newBenefits[] = $benefit;
        }
    }

    // Only insert new benefits
    if (empty($newBenefits)) {
        $pdo->rollBack();
        sendJsonError("All provided benefits already exist in the system.", 409);
    }

    $stmt = $pdo->prepare("INSERT INTO benefits (benefit_name, created_at) VALUES (:benefit_name, NOW())");
    $insertedIds = [];
    $insertedBenefits = [];

    foreach ($newBenefits as $benefit) {
        $stmt->execute(['benefit_name' => $benefit]);
        $insertedId = $pdo->lastInsertId();
        $insertedIds[] = $insertedId;
        $insertedBenefits[] = [
            'id' => $insertedId,
            'name' => $benefit
        ];
    }

    $pdo->commit();

    // =========================================================
    // 🧾 LOG ACTIVITY WITH PROPER CONTEXT (Like senior_edit.php)
    // =========================================================
    if ($logger) {
        // Determine activity type based on user context
        if ($userInfo['context'] === 'staff') {
            $activityType = 'STAFF_ADD_BENEFIT_TYPE';
            $description = 'Staff added new benefit types to system';
        } else {
            $activityType = 'ADD_BENEFIT_TYPE';
            $description = 'Admin added new benefit types to system';
        }

        // Create log details with user context information
        $logDetails = [
            'benefits_added' => $insertedBenefits,
            'total_added' => count($insertedBenefits),
            'duplicates_skipped' => $duplicates,
            'added_by' => $userInfo['name'],
            'added_by_id' => $userInfo['id'],
            'user_type' => $userInfo['type'], // Store actual user_type
            'user_context' => $userInfo['context'], // Store context (staff/admin)
            'session_context' => $userInfo['context'], // For consistency
            'added_at' => date('Y-m-d H:i:s'),
            'benefit_count_after' => count($newBenefits) + count($duplicates),
            'request_source' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];

        // Log the activity
        $logResult = $logger->log($activityType, $description, $logDetails);

        if (!$logResult) {
            error_log("Warning: Failed to log benefit addition activity");
        }
    }

    // Return success response with user context
    $responseData = [
        'added_count' => count($insertedBenefits),
        'benefits_added' => $insertedBenefits,
        'added_by' => $userName,
        'added_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'activity_type_logged' => ($sessionContext === 'staff') ? 'STAFF_ADD_BENEFIT_TYPE' : 'ADD_BENEFIT_TYPE',
        'added_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($duplicates)) {
        $responseData['duplicates_skipped'] = $duplicates;
        $responseData['warning'] = count($duplicates) . ' benefit(s) already exist and were not added.';
    }

    sendJsonSuccess("Benefits added successfully!", $responseData);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Database error in save_benefits.php: " . $e->getMessage());

    // Check for duplicate entry error (MySQL error code 1062)
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        sendJsonError("One or more benefits already exist in the system. Please check for duplicates.", 409);
    }

    sendJsonError("Failed to save benefits: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error in save_benefits.php: " . $e->getMessage());
    sendJsonError("Failed to save benefits: " . $e->getMessage(), 500);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
