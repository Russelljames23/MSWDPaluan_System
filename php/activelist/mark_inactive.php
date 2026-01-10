<?php
// mark_inactive.php - Enhanced version with proper staff/admin context detection
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Start session early for context detection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
include '../db.php';

// Helper functions
function sendJsonError($message, $code = 400) {
    http_response_code($code);
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "success" => false,
        "error" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sendJsonSuccess($message, $data = []) {
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
$activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
} else {
    // Try to find ActivityLogger.php in parent directory
    $activityLoggerPath = dirname(dirname(__DIR__)) . '/ActivityLogger.php';
    if (file_exists($activityLoggerPath)) {
        require_once $activityLoggerPath;
    }
}

// Get input
$input = file_get_contents("php://input");
if (empty($input)) {
    sendJsonError("No input data received");
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonError("Invalid JSON data: " . json_last_error_msg());
}

// Validate required fields
if (empty($data['applicant_id'])) {
    sendJsonError("Missing applicant ID");
}

if (empty($data['date_of_inactive'])) {
    sendJsonError("Missing inactive date");
}

if (empty($data['reason'])) {
    sendJsonError("Missing reason");
}

// Validate reason
$reason = trim($data['reason']);
if (strlen($reason) === 0) {
    sendJsonError("Reason cannot be empty or just whitespace");
}

if (strlen($reason) > 255) {
    sendJsonError("Reason is too long. Maximum 255 characters allowed");
}

// =========================================================
// SIMPLIFIED AND RELIABLE CONTEXT DETECTION
// =========================================================
function detectUserContext($data)
{
    $context = 'admin'; // Default to admin
    $userId = 0;
    $userName = 'Unknown';
    
    error_log("=== START detectUserContext (mark_inactive.php) ===");
    error_log("Received data: " . print_r($data, true));
    error_log("Session data: " . print_r($_SESSION, true));
    
    // 1. FIRST PRIORITY: Check for explicit context in REQUEST DATA
    if (isset($data['session_context'])) {
        $context = $data['session_context'];
        error_log("Context from request data: " . $context);
        
        // Store context in session for consistency
        $_SESSION['session_context'] = $context;
        
        // Get user ID based on context from request
        if ($context === 'staff') {
            // Staff context
            if (isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
                $userId = (int)$data['staff_user_id'];
                error_log("Using staff_user_id from request: " . $userId);
            }
            if (isset($data['staff_user_name']) && !empty($data['staff_user_name'])) {
                $userName = $data['staff_user_name'];
                error_log("Using staff_user_name from request: " . $userName);
            }
        } else {
            // Admin context
            if (isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
                $userId = (int)$data['admin_user_id'];
                error_log("Using admin_user_id from request: " . $userId);
            }
            if (isset($data['admin_user_name']) && !empty($data['admin_user_name'])) {
                $userName = $data['admin_user_name'];
                error_log("Using admin_user_name from request: " . $userName);
            }
        }
    }
    
    // 2. SECOND PRIORITY: Check session for already determined context
    elseif (isset($_SESSION['session_context'])) {
        $context = $_SESSION['session_context'];
        error_log("Context from session: " . $context);
        
        // Get user ID from session based on context
        if ($context === 'staff') {
            if (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
                $userId = (int)$_SESSION['staff_user_id'];
                error_log("Using staff_user_id from session: " . $userId);
            }
        } else {
            if (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
                $userId = (int)$_SESSION['admin_user_id'];
                error_log("Using admin_user_id from session: " . $userId);
            }
        }
    }
    
    // 3. THIRD PRIORITY: Fallback to user_id from session
    if ($userId === 0 && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        error_log("Using fallback user_id from session: " . $userId);
        
        // Try to determine context from user_type in session
        if (isset($_SESSION['user_type'])) {
            $userType = strtolower($_SESSION['user_type']);
            if (strpos($userType, 'staff') !== false) {
                $context = 'staff';
                error_log("Determined context 'staff' from session user_type");
            } elseif (strpos($userType, 'admin') !== false) {
                $context = 'admin';
                error_log("Determined context 'admin' from session user_type");
            }
        }
    }
    
    // 4. Get user name from session if not already set
    if (empty($userName) || $userName === 'Unknown') {
        if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
            $userName = $_SESSION['fullname'];
            error_log("Using fullname from session: " . $userName);
        } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
            $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
            error_log("Using firstname+lastname from session: " . $userName);
        } elseif (isset($_SESSION['username'])) {
            $userName = $_SESSION['username'];
            error_log("Using username from session: " . $userName);
        }
    }
    
    // 5. FINAL FALLBACK: If still no user ID, use default based on context
    if ($userId === 0) {
        if ($context === 'staff') {
            $userId = 0; // Staff ID 0 for unknown staff
            error_log("Using fallback staff ID: 0");
        } else {
            $userId = 57; // Default admin ID
            error_log("Using fallback admin ID: 57");
        }
    }
    
    // 6. VERIFY USER EXISTS IN DATABASE AND GET ACCURATE INFO
    if ($userId > 0 && isset($GLOBALS['conn'])) {
        try {
            $stmt = $GLOBALS['conn']->prepare(
                "SELECT id, firstname, lastname, user_type FROM users WHERE id = ?"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Use database name if available
                $dbUserName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                if (!empty($dbUserName)) {
                    $userName = $dbUserName;
                    error_log("Using name from database: " . $userName);
                }
                
                // IMPORTANT: If database user_type says "staff" but context is "admin", CORRECT IT
                $dbUserType = strtolower($user['user_type'] ?? '');
                if (strpos($dbUserType, 'staff') !== false && $context === 'admin') {
                    $context = 'staff';
                    $_SESSION['session_context'] = 'staff';
                    $_SESSION['staff_user_id'] = $userId;
                    error_log("CORRECTED: Context changed to 'staff' based on database user_type");
                } elseif (strpos($dbUserType, 'admin') !== false && $context === 'staff') {
                    $context = 'admin';
                    $_SESSION['session_context'] = 'admin';
                    $_SESSION['admin_user_id'] = $userId;
                    error_log("CORRECTED: Context changed to 'admin' based on database user_type");
                }
                
                // Store proper IDs in session for future requests
                if ($context === 'staff') {
                    $_SESSION['staff_user_id'] = $userId;
                } else {
                    $_SESSION['admin_user_id'] = $userId;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking user in database: " . $e->getMessage());
        }
    }
    
    $userInfo = [
        'context' => $context,
        'user_id' => $userId,
        'user_name' => $userName
    ];
    
    error_log("Final user info: " . print_r($userInfo, true));
    error_log("Session data after detection: " . print_r($_SESSION, true));
    error_log("=== END detectUserContext ===");
    
    return $userInfo;
}

// Detect user context
$userInfo = detectUserContext($data);
$context = $userInfo['context'];
$userId = $userInfo['user_id'];
$userName = $userInfo['user_name'];

// Initialize logger
if (class_exists('ActivityLogger')) {
    $logger = new ActivityLogger($conn);
} else {
    // Create minimal logger for debugging
    class SimpleLogger
    {
        private $conn;
        
        public function __construct($conn)
        {
            $this->conn = $conn;
        }
        
        public function log($type, $desc, $details = null)
        {
            // Log to file for debugging
            $logMessage = date('Y-m-d H:i:s') . " - Activity: $type - $desc";
            if ($details) {
                $logMessage .= " - Details: " . json_encode($details);
            }
            error_log($logMessage);

            // Also try to log to database directly for debugging
            if ($this->conn) {
                try {
                    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([
                        $GLOBALS['userId'], // Use the detected user ID
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

// Process IDs (supporting both single and array)
$ids = is_array($data['applicant_id']) ? $data['applicant_id'] : [$data['applicant_id']];
$ids = array_filter($ids, function($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
});

if (empty($ids)) {
    sendJsonError("No valid applicant IDs provided");
}

// Validate date format
$date_of_inactive = $data['date_of_inactive'];
if (!strtotime($date_of_inactive)) {
    sendJsonError("Invalid date format for inactive date");
}

try {
    $updatedApplicants = [];
    $conn->beginTransaction();
    
    // Get applicant info before updating (for logging and verification)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status 
        FROM applicants 
        WHERE applicant_id IN ($placeholders)
    ");
    $infoStmt->execute($ids);
    $applicantsInfo = $infoStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($applicantsInfo) !== count($ids)) {
        throw new Exception("One or more applicants not found");
    }
    
    // Check if any applicants are already inactive
    $alreadyInactive = array_filter($applicantsInfo, function($applicant) {
        return $applicant['status'] === 'Inactive';
    });
    
    if (!empty($alreadyInactive)) {
        $inactiveNames = array_map(function($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $alreadyInactive);
        throw new Exception("Some applicants are already inactive: " . implode(', ', $inactiveNames));
    }
    
    // Update all applicants
    $sql = "
        UPDATE applicants 
        SET status = 'Inactive', 
            inactive_reason = ?, 
            date_of_inactive = ?, 
            date_modified = NOW()
        WHERE applicant_id IN ($placeholders)
    ";
    
    $stmt = $conn->prepare($sql);
    // Combine parameters: reason + date + ids
    $params = array_merge([$reason, $date_of_inactive], $ids);
    $stmt->execute($params);
    
    $updatedCount = $stmt->rowCount();
    
    if ($updatedCount === 0) {
        throw new Exception("No applicants were updated");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Get applicant names for logging
    $applicantNames = array_map(function($a) {
        return $a['first_name'] . ' ' . $a['last_name'];
    }, $applicantsInfo);

    $applicantControlNumbers = array_filter(array_column($applicantsInfo, 'control_number'));

    // Log the activity with proper context
    // IMPORTANT FIX: Use correct activity type based on context
    if ($context === 'staff') {
        $activityType = 'STAFF_MARK_INACTIVE';
        $description = "Staff marked " . count($ids) . " senior(s) as inactive";
        error_log("Logging as STAFF activity: $activityType - $description");
    } else {
        $activityType = 'MARK_INACTIVE';
        $description = "Admin marked " . count($ids) . " senior(s) as inactive";
        error_log("Logging as ADMIN activity: $activityType - $description");
    }
    
    $logDetails = [
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'control_numbers' => !empty($applicantControlNumbers) ? $applicantControlNumbers : 'N/A',
        'reason' => $reason,
        'inactive_date' => $date_of_inactive,
        'marked_by' => $userName,
        'marked_by_id' => $userId,
        'user_context' => $context,
        'marked_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'updated_count' => $updatedCount,
        'previous_status' => array_column($applicantsInfo, 'status'),
        'request_source' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
    ];

    // Log the activity
    if ($logger) {
        $logger->log($activityType, $description, $logDetails);
        error_log("Activity logged successfully: $activityType");
    } else {
        error_log("WARNING: Logger not available for activity: $activityType");
    }
    
    // Return success response
    sendJsonSuccess("Senior(s) successfully marked as inactive", [
        'updated_count' => $updatedCount,
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'marked_by' => $userName,
        'marked_by_id' => $userId,
        'context' => $context,
        'log_type' => $activityType,
        'logged_as' => $context === 'staff' ? 'Staff' : 'Admin'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error with proper context
    if ($logger) {
        $errorActivityType = ($context === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $logger->log($errorActivityType, 'Failed to mark senior(s) as inactive', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'reason_attempted' => $reason,
            'marked_by' => $userName,
            'marked_by_id' => $userId,
            'context' => $context
        ]);
    }
    
    error_log("Database error in mark_inactive.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error with proper context
    if ($logger) {
        $errorActivityType = ($context === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $logger->log($errorActivityType, 'Failed to mark senior(s) as inactive', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'reason_attempted' => $reason,
            'marked_by' => $userName,
            'marked_by_id' => $userId,
            'context' => $context
        ]);
    }
    
    error_log("Error in mark_inactive.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}