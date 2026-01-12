<?php
// delete_benefit.php - Enhanced with proper admin/staff activity logging
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function sendJsonError($message, $code = 400)
{
    http_response_code($code);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        "success" => false,
        "error" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendJsonSuccess($message, $data = [])
{
    http_response_code(200);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $response = array_merge([
        "success" => true,
        "message" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ], $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    sendJsonError("Database connection failed.", 500);
}

// =========================================================
// USER INFO DETECTION (Same as update_benefit.php)
// =========================================================
function getCurrentUserInfo($pdo)
{
    // Check for session context in input data
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

    // Determine context from session
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

    // If still no ID, use default admin ID
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

                // Verify if user_type matches context
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

    error_log("Delete Benefit - User Info: ID=" . $userId . ", Name=" . $userName . ", Type=" . $userType . ", Context=" . $context);

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

// Initialize logger
if (class_exists('ActivityLogger')) {
    $logger = new ActivityLogger($pdo);
} else {
    // Enhanced logger with context awareness
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

                    // Get user info from details if available
                    $logUserId = $details['deleted_by_id'] ?? $details['updated_by_id'] ?? $details['added_by_id'] ?? 0;

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute([
                        $logUserId,
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

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Log raw input for debugging
error_log("Delete Benefit - Raw input: " . file_get_contents('php://input'));

if (!$input) {
    // Try to get POST data if JSON fails
    $input = $_POST;
}

if (!$input) {
    error_log("Delete Benefit: No input received");
    sendJsonError("Invalid or missing data.");
}

error_log("Delete Benefit - Parsed input: " . print_r($input, true));

$id = $input['id'] ?? null;

// Validate ID
if (!$id || !is_numeric($id) || $id <= 0) {
    error_log("Delete Benefit: Invalid benefit ID: " . $id);
    sendJsonError("Invalid benefit ID.");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if benefit exists and get details for logging
    $selectStmt = $pdo->prepare("
        SELECT id, benefit_name, created_at, updated_at 
        FROM benefits 
        WHERE id = ?
    ");
    $selectStmt->execute([$id]);
    $benefit = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$benefit) {
        $pdo->rollBack();

        // Log attempt to delete non-existent benefit
        $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $description = ($sessionContext === 'staff')
            ? 'Staff attempted to delete non-existent benefit'
            : 'Admin attempted to delete non-existent benefit';

        $logger->log($activityType, $description, [
            'attempted_benefit_id' => $id,
            'deleted_by' => $userName,
            'deleted_by_id' => $userId,
            'user_type' => $userType,
            'user_context' => $sessionContext,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'error_type' => 'Benefit Not Found'
        ]);

        sendJsonError("Benefit not found with ID: $id", 404);
    }

    // Check for existing distributions of this benefit
    $checkDistributions = $pdo->prepare("
        SELECT COUNT(*) as distribution_count 
        FROM benefits_distribution 
        WHERE benefit_id = ?
    ");
    $checkDistributions->execute([$id]);
    $distributionResult = $checkDistributions->fetch(PDO::FETCH_ASSOC);
    $distributionCount = $distributionResult['distribution_count'] ?? 0;

    if ($distributionCount > 0) {
        // Get distribution details for logging
        $distDetailsStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT applicant_id) as unique_beneficiaries,
                   MIN(distribution_date) as earliest_distribution,
                   MAX(distribution_date) as latest_distribution
            FROM benefits_distribution 
            WHERE benefit_id = ?
        ");
        $distDetailsStmt->execute([$id]);
        $distributionDetails = $distDetailsStmt->fetch(PDO::FETCH_ASSOC);

        $pdo->rollBack();

        // Log attempt to delete benefit with existing distributions
        $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $description = ($sessionContext === 'staff')
            ? 'Staff attempted to delete benefit with existing distributions'
            : 'Admin attempted to delete benefit with existing distributions';

        $logger->log($activityType, $description, [
            'benefit_id' => $id,
            'benefit_name' => $benefit['benefit_name'],
            'distribution_count' => $distributionCount,
            'unique_beneficiaries' => $distributionDetails['unique_beneficiaries'] ?? 0,
            'earliest_distribution' => $distributionDetails['earliest_distribution'] ?? null,
            'latest_distribution' => $distributionDetails['latest_distribution'] ?? null,
            'deleted_by' => $userName,
            'deleted_by_id' => $userId,
            'user_type' => $userType,
            'user_context' => $sessionContext,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'error_type' => 'Benefit Has Distributions',
            'suggestion' => 'Archive or update distributions first before deleting benefit type.'
        ]);

        sendJsonError("Cannot delete benefit '{$benefit['benefit_name']}' because it has $distributionCount existing distribution(s) to beneficiaries. Please archive or reassign these distributions first.", 409);
    }

    // Delete the benefit
    $deleteStmt = $pdo->prepare("DELETE FROM benefits WHERE id = ?");
    $deleteStmt->execute([$id]);

    $affectedRows = $deleteStmt->rowCount();

    if ($affectedRows === 0) {
        throw new Exception("No benefit was deleted. It may have been removed by another user.");
    }

    $pdo->commit();

    // Log the deletion activity with proper context
    if ($sessionContext === 'staff') {
        $activityType = 'STAFF_DELETE_BENEFIT';
        $description = 'Staff deleted benefit type from system';
    } else {
        $activityType = 'DELETE_BENEFIT';
        $description = 'Admin deleted benefit type from system';
    }

    $logDetails = [
        'benefit_id' => $id,
        'benefit_name' => $benefit['benefit_name'],
        'created_at' => $benefit['created_at'] ?? null,
        'last_updated' => $benefit['updated_at'] ?? null,
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'deleted_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'affected_rows' => $affectedRows,
        'deletion_summary' => [
            'id' => $id,
            'name' => $benefit['benefit_name'],
            'created' => $benefit['created_at'] ?? null,
            'deleted' => date('Y-m-d H:i:s'),
            'deleted_by_user' => $userName,
            'deleted_by_user_id' => $userId,
            'user_context' => $sessionContext
        ],
        'notes' => 'Benefit was not in use (no existing distributions)'
    ];

    $logger->log($activityType, $description, $logDetails);

    // Return success response with user context
    sendJsonSuccess("Benefit deleted successfully", [
        'benefit_id' => $id,
        'benefit_name' => $benefit['benefit_name'],
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'deleted_at' => date('Y-m-d H:i:s'),
        'affected_rows' => $affectedRows,
        'activity_type_logged' => $activityType,
        'deletion_summary' => "Benefit '{$benefit['benefit_name']}' (ID: $id) was deleted from the system."
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackEx) {
            error_log("Rollback failed: " . $rollbackEx->getMessage());
        }
    }

    // Log error with proper context
    $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
    $description = ($sessionContext === 'staff')
        ? 'Staff failed to delete benefit due to database error'
        : 'Admin failed to delete benefit due to database error';

    $logger->log($activityType, $description, [
        'benefit_id' => $id ?? 'unknown',
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Database Error'
    ]);

    error_log("Database error in delete_benefit.php: " . $e->getMessage());

    // Check for foreign key constraint error
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'foreign key constraint') !== false) {
        sendJsonError("Cannot delete benefit because it is referenced in existing distributions. Please archive or update these distributions first.", 409);
    }

    sendJsonError("Failed to delete benefit: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackEx) {
            error_log("Rollback failed: " . $rollbackEx->getMessage());
        }
    }

    // Log error with proper context
    $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
    $description = ($sessionContext === 'staff')
        ? 'Staff failed to delete benefit due to application error'
        : 'Admin failed to delete benefit due to application error';

    $logger->log($activityType, $description, [
        'benefit_id' => $id ?? 'unknown',
        'error_message' => $e->getMessage(),
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Application Error'
    ]);

    error_log("Error in delete_benefit.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_level() > 0) {
    ob_end_flush();
}
