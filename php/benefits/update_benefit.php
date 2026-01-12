<?php
// update_benefit.php - Improved version with better user detection
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
    @session_start();
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
// IMPROVED USER INFO DETECTION (Consistent with save_benefits.php)
// =========================================================
function getCurrentUserInfo($pdo)
{
    // First check for session context in input data
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

    error_log("Update Benefit - User Info: ID=" . $userId . ", Name=" . $userName . ", Type=" . $userType . ", Context=" . $context);

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
    // Simple fallback logger
    class SimpleLogger
    {
        private $pdo;

        public function __construct($pdo)
        {
            $this->pdo = $pdo;
        }

        public function log($type, $desc, $details = null)
        {
            $logMessage = date('Y-m-d H:i:s') . " - Activity: $type - $desc";
            if ($details) {
                $logMessage .= " - Details: " . json_encode($details);
            }
            error_log($logMessage);

            // Log to database
            if ($this->pdo) {
                try {
                    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute([
                        $details['updated_by_id'] ?? 0,
                        $type,
                        $desc,
                        $detailsJson,
                        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                        substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
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

if (!$input) {
    // Try to get POST data if JSON fails
    $input = $_POST;
}

if (!$input) {
    error_log("Update Benefit: No input received");
    sendJsonError("Invalid or missing data.");
}

error_log("Update Benefit - Input received: " . json_encode($input));

$id = $input['id'] ?? null;
$benefit_name = trim($input['benefit_name'] ?? '');

// Validate input
if (!$id || !is_numeric($id) || $id <= 0) {
    error_log("Update Benefit: Invalid benefit ID: " . $id);
    sendJsonError("Invalid benefit ID.");
}

if (empty($benefit_name)) {
    error_log("Update Benefit: Empty benefit name for ID: " . $id);
    sendJsonError("Benefit name cannot be empty.");
}

if (strlen($benefit_name) > 255) {
    error_log("Update Benefit: Benefit name too long for ID: " . $id);
    sendJsonError("Benefit name is too long. Maximum 255 characters allowed.");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get the current benefit info before update for logging
    $selectStmt = $pdo->prepare("SELECT id, benefit_name, created_at FROM benefits WHERE id = ?");
    $selectStmt->execute([$id]);
    $currentBenefit = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentBenefit) {
        error_log("Update Benefit: Benefit not found with ID: " . $id);
        sendJsonError("Benefit not found with ID: $id");
    }

    error_log("Update Benefit - Current: " . $currentBenefit['benefit_name'] . ", New: " . $benefit_name);

    // Check if new name is the same as current name (case-insensitive)
    if (strcasecmp($currentBenefit['benefit_name'], $benefit_name) === 0) {
        $pdo->rollBack();

        // Log no-change attempt
        if ($logger) {
            $activityType = ($sessionContext === 'staff') ? 'STAFF_ATTEMPT_UPDATE_BENEFIT' : 'ATTEMPT_UPDATE_BENEFIT';
            $description = ($sessionContext === 'staff')
                ? 'Staff attempted to update benefit with no changes'
                : 'Admin attempted to update benefit with no changes';

            $logger->log($activityType, $description, [
                'benefit_id' => $id,
                'benefit_name' => $currentBenefit['benefit_name'],
                'new_name_attempted' => $benefit_name,
                'updated_by' => $userName,
                'updated_by_id' => $userId,
                'user_type' => $userType,
                'user_context' => $sessionContext
            ]);
        }

        sendJsonError("Benefit name is unchanged. No update needed.");
    }

    // Check if new name already exists (case-insensitive, excluding current benefit)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM benefits WHERE LOWER(benefit_name) = LOWER(?) AND id != ?");
    $checkStmt->execute([$benefit_name, $id]);
    $exists = $checkStmt->fetchColumn() > 0;

    if ($exists) {
        $pdo->rollBack();
        error_log("Update Benefit: Duplicate name found: " . $benefit_name);

        // Log duplicate name attempt
        if ($logger) {
            $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
            $description = ($sessionContext === 'staff')
                ? 'Staff attempted to update benefit with duplicate name'
                : 'Admin attempted to update benefit with duplicate name';

            $logger->log($activityType, $description, [
                'benefit_id' => $id,
                'current_name' => $currentBenefit['benefit_name'],
                'new_name_attempted' => $benefit_name,
                'updated_by' => $userName,
                'updated_by_id' => $userId,
                'user_type' => $userType,
                'user_context' => $sessionContext
            ]);
        }

        sendJsonError("Benefit name '{$benefit_name}' already exists. Please use a different name.");
    }

    // Update benefit
    $updateStmt = $pdo->prepare("UPDATE benefits SET benefit_name = :benefit_name, updated_at = NOW() WHERE id = :id");
    $updateStmt->bindParam(':benefit_name', $benefit_name, PDO::PARAM_STR);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();

    $affectedRows = $updateStmt->rowCount();
    error_log("Update Benefit - Affected rows: " . $affectedRows);

    if ($affectedRows === 0) {
        // Check if benefit still exists
        $checkExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM benefits WHERE id = ?");
        $checkExistsStmt->execute([$id]);
        $stillExists = $checkExistsStmt->fetchColumn() > 0;

        if (!$stillExists) {
            sendJsonError("Benefit no longer exists. It may have been deleted.");
        } else {
            sendJsonError("No changes were made. The name may have been updated by another user.");
        }
    }

    $pdo->commit();

    // Log successful update
    if ($logger) {
        // Determine activity type based on user context
        if ($sessionContext === 'staff') {
            $activityType = 'STAFF_UPDATE_BENEFIT';
            $description = 'Staff updated benefit type';
        } else {
            $activityType = 'UPDATE_BENEFIT';
            $description = 'Admin updated benefit type';
        }

        // Create log details
        $logDetails = [
            'benefit_id' => $id,
            'old_name' => $currentBenefit['benefit_name'],
            'new_name' => $benefit_name,
            'created_at' => $currentBenefit['created_at'] ?? null,
            'updated_by' => $userName,
            'updated_by_id' => $userId,
            'user_type' => $userType,
            'user_context' => $sessionContext,
            'updated_at' => date('Y-m-d H:i:s'),
            'affected_rows' => $affectedRows
        ];

        $logger->log($activityType, $description, $logDetails);
    }

    // Return success response
    sendJsonSuccess("Benefit updated successfully", [
        'benefit_id' => $id,
        'old_name' => $currentBenefit['benefit_name'],
        'new_name' => $benefit_name,
        'updated_by' => $userName,
        'updated_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'updated_at' => date('Y-m-d H:i:s'),
        'affected_rows' => $affectedRows
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

    // Log the specific database error
    error_log("Database error in update_benefit.php: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());

    // Check for specific database errors
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        sendJsonError("Benefit name '{$benefit_name}' already exists. Please use a different name.", 409);
    }

    if ($e->getCode() == 42000 || strpos($e->getMessage(), 'SQL syntax') !== false) {
        sendJsonError("Database syntax error. Please contact administrator.", 500);
    }

    // Generic error message for user, detailed for logs
    sendJsonError("Failed to update benefit due to a database error.", 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackEx) {
            error_log("Rollback failed: " . $rollbackEx->getMessage());
        }
    }

    error_log("General error in update_benefit.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_level() > 0) {
    ob_end_flush();
}
