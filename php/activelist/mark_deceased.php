<?php
// mark_deceased.php - Enhanced version with proper staff/admin context detection
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
$activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
} elseif (file_exists(dirname(__DIR__) . '/ActivityLogger.php')) {
    require_once dirname(__DIR__) . '/ActivityLogger.php';
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

if (empty($data['date_of_death'])) {
    sendJsonError("Missing date of death");
}

// =========================================================
// ENHANCED CONTEXT DETECTION AND SESSION MANAGEMENT
// =========================================================
function detectUserContext($data)
{
    $context = 'admin'; // Default to admin
    $userId = 0;
    $userName = 'Unknown';
    
    error_log("=== START detectUserContext ===");
    error_log("Received data: " . print_r($data, true));
    error_log("Session data before detection: " . print_r($_SESSION, true));
    
    // 1. Check for explicit context in request data
    if (isset($data['session_context'])) {
        $context = $data['session_context'];
        error_log("Context from request data: " . $context);
        
        // IMPORTANT: Store context in session for future requests
        $_SESSION['session_context'] = $context;
        
        // If we have staff_user_id in request, store it in session
        if ($context === 'staff' && isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
            $_SESSION['staff_user_id'] = (int)$data['staff_user_id'];
            error_log("Stored staff_user_id in session: " . $data['staff_user_id']);
        }
        // If we have admin_user_id in request, store it in session
        elseif ($context === 'admin' && isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
            $_SESSION['admin_user_id'] = (int)$data['admin_user_id'];
            error_log("Stored admin_user_id in session: " . $data['admin_user_id']);
        }
    }
    
    // 2. Check session for context (if not set in request)
    if (!isset($data['session_context']) && isset($_SESSION['session_context'])) {
        $context = $_SESSION['session_context'];
        error_log("Context from session: " . $context);
    }
    
    // 3. Determine user ID based on context
    if ($context === 'staff') {
        // Priority 1: staff_user_id from request
        if (isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
            $userId = (int)$data['staff_user_id'];
            error_log("Using staff_user_id from request: " . $userId);
        }
        // Priority 2: staff_user_id from session
        elseif (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
            $userId = (int)$_SESSION['staff_user_id'];
            error_log("Using staff_user_id from session: " . $userId);
        }
        // Priority 3: user_id from session (fallback)
        elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            error_log("Using user_id from session as staff: " . $userId);
        }
    } else {
        // Admin context
        // Priority 1: admin_user_id from request
        if (isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
            $userId = (int)$data['admin_user_id'];
            error_log("Using admin_user_id from request: " . $userId);
        }
        // Priority 2: admin_user_id from session
        elseif (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
            $userId = (int)$_SESSION['admin_user_id'];
            error_log("Using admin_user_id from session: " . $userId);
        }
        // Priority 3: user_id from session (fallback)
        elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            error_log("Using user_id from session as admin: " . $userId);
        }
    }
    
    // 4. Get user name
    // Priority 1: staff_user_name or admin_user_name from request
    if ($context === 'staff' && isset($data['staff_user_name']) && !empty($data['staff_user_name'])) {
        $userName = $data['staff_user_name'];
        error_log("Using staff_user_name from request: " . $userName);
    } elseif ($context === 'admin' && isset($data['admin_user_name']) && !empty($data['admin_user_name'])) {
        $userName = $data['admin_user_name'];
        error_log("Using admin_user_name from request: " . $userName);
    }
    // Priority 2: Session data
    elseif (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
        $userName = $_SESSION['fullname'];
        error_log("Using fullname from session: " . $userName);
    } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
        error_log("Using firstname+lastname from session: " . $userName);
    } elseif (isset($_SESSION['username'])) {
        $userName = $_SESSION['username'];
        error_log("Using username from session: " . $userName);
    }
    
    // 5. Final fallback for user ID
    if ($userId === 0) {
        if ($context === 'staff') {
            $userId = 0; // Staff ID 0 for unknown staff
            error_log("Using fallback staff ID: 0");
        } else {
            $userId = 57; // Default admin ID
            error_log("Using fallback admin ID: 57");
        }
    }
    
    // 6. Verify the user exists in database and get accurate info
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
                
                // Update context based on database user_type if needed
                $dbUserType = strtolower($user['user_type'] ?? '');
                if (strpos($dbUserType, 'staff') !== false && $context === 'admin') {
                    $context = 'staff';
                    $_SESSION['session_context'] = 'staff';
                    error_log("Corrected context to 'staff' based on database user_type");
                } elseif (strpos($dbUserType, 'admin') !== false && $context === 'staff') {
                    $context = 'admin';
                    $_SESSION['session_context'] = 'admin';
                    error_log("Corrected context to 'admin' based on database user_type");
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
$ids = array_filter($ids, function ($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
});

if (empty($ids)) {
    sendJsonError("No valid applicant IDs provided");
}

// Validate date format
$date_of_death = $data['date_of_death'];
if (!strtotime($date_of_death)) {
    sendJsonError("Invalid date format for date of death");
}

try {
    $updatedApplicants = [];
    $conn->beginTransaction();

    // Get applicant info before updating
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, date_of_death
        FROM applicants 
        WHERE applicant_id IN ($placeholders)
    ");
    $infoStmt->execute($ids);
    $applicantsInfo = $infoStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($applicantsInfo) !== count($ids)) {
        throw new Exception("One or more applicants not found");
    }

    // Check if any applicants are already deceased
    $alreadyDeceased = array_filter($applicantsInfo, function ($applicant) {
        return $applicant['status'] === 'Deceased';
    });

    if (!empty($alreadyDeceased)) {
        $deceasedNames = array_map(function ($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $alreadyDeceased);
        throw new Exception("Some applicants are already marked as deceased: " . implode(', ', $deceasedNames));
    }

    // Check if applicants are already inactive
    $alreadyInactive = array_filter($applicantsInfo, function ($applicant) {
        return $applicant['status'] === 'Inactive';
    });

    if (!empty($alreadyInactive)) {
        $inactiveNames = array_map(function ($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $alreadyInactive);
        throw new Exception("Some applicants are already inactive. Please restore them first before marking as deceased: " . implode(', ', $inactiveNames));
    }

    // Update all applicants
    $sql = "
        UPDATE applicants 
        SET status = 'Deceased', 
            date_of_death = ?,
            date_modified = NOW()
        WHERE applicant_id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    // Combine parameters: date_of_death + ids
    $params = array_merge([$date_of_death], $ids);
    $stmt->execute($params);

    $updatedCount = $stmt->rowCount();

    if ($updatedCount === 0) {
        throw new Exception("No applicants were updated");
    }

    // Commit transaction
    $conn->commit();

    // Get applicant names for logging
    $applicantNames = array_map(function ($a) {
        return $a['first_name'] . ' ' . $a['last_name'];
    }, $applicantsInfo);

    $applicantControlNumbers = array_filter(array_column($applicantsInfo, 'control_number'));

    // Log the activity with proper context
    if ($context === 'staff') {
        $activityType = 'STAFF_MARK_DECEASED';
        $description = "Staff marked " . count($ids) . " senior(s) as deceased";
        
        // IMPORTANT: For staff, we need to log with the actual staff user ID
        // Get staff user info from database to ensure accuracy
        try {
            $userStmt = $conn->prepare(
                "SELECT id, firstname, lastname, user_type FROM users WHERE id = ?"
            );
            $userStmt->execute([$userId]);
            $dbUser = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dbUser) {
                $userName = trim(($dbUser['firstname'] ?? '') . ' ' . ($dbUser['lastname'] ?? '')) ?: $userName;
                error_log("Using database staff user name: " . $userName);
            }
        } catch (Exception $e) {
            error_log("Error fetching staff user from DB: " . $e->getMessage());
        }
    } else {
        $activityType = 'MARK_DECEASED';
        $description = "Marked " . count($ids) . " senior(s) as deceased";
        
        // For admin, get admin user info from database
        try {
            $userStmt = $conn->prepare(
                "SELECT id, firstname, lastname, user_type FROM users WHERE id = ?"
            );
            $userStmt->execute([$userId]);
            $dbUser = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dbUser) {
                $userName = trim(($dbUser['firstname'] ?? '') . ' ' . ($dbUser['lastname'] ?? '')) ?: $userName;
                error_log("Using database admin user name: " . $userName);
            }
        } catch (Exception $e) {
            error_log("Error fetching admin user from DB: " . $e->getMessage());
        }
    }
    
    $logDetails = [
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'control_numbers' => !empty($applicantControlNumbers) ? $applicantControlNumbers : 'N/A',
        'date_of_death' => $date_of_death,
        'marked_by' => $userName,
        'marked_by_id' => $userId,
        'user_context' => $context,
        'marked_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'updated_count' => $updatedCount,
        'previous_status' => array_column($applicantsInfo, 'status'),
        'request_source' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
    ];

    $logger->log($activityType, $description, $logDetails);

    // Return success response
    sendJsonSuccess("Senior(s) successfully marked as deceased", [
        'updated_count' => $updatedCount,
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'date_of_death' => $date_of_death,
        'marked_by' => $userName,
        'marked_by_id' => $userId,
        'context' => $context,
        'log_type' => $activityType
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $errorActivityType = ($context === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $logger->log($errorActivityType, 'Failed to mark senior(s) as deceased', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'date_of_death_attempted' => $date_of_death,
            'marked_by' => $userName,
            'marked_by_id' => $userId,
            'context' => $context
        ]);
    }

    error_log("Database error in mark_deceased.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $errorActivityType = ($context === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $logger->log($errorActivityType, 'Failed to mark senior(s) as deceased', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'date_of_death_attempted' => $date_of_death,
            'marked_by' => $userName,
            'marked_by_id' => $userId,
            'context' => $context
        ]);
    }

    error_log("Error in mark_deceased.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}