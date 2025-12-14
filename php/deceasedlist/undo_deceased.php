<?php
// undo_deceased.php - Improved version with activity logging
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

// Get ID from GET parameter
if (!isset($_GET['id'])) {
    sendJsonError("Missing applicant ID.");
}

$id = intval($_GET['id']);
if ($id <= 0) {
    sendJsonError("Invalid applicant ID.");
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

try {
    $conn->beginTransaction();

    // Get applicant info before updating for logging
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, 
               date_of_death, date_of_inactive, inactive_reason
        FROM applicants 
        WHERE applicant_id = ?
    ");
    $infoStmt->execute([$id]);
    $applicantInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicantInfo) {
        throw new Exception("No applicant found with ID: " . $id);
    }

    // Check if applicant is already active
    if ($applicantInfo['status'] === 'Active') {
        throw new Exception("Applicant is already active.");
    }

    // Check if applicant is inactive (not deceased)
    if ($applicantInfo['status'] === 'Inactive') {
        throw new Exception("Applicant is marked as inactive, not deceased. Use the inactive restoration instead.");
    }

    // Only proceed if applicant is actually deceased
    if ($applicantInfo['status'] !== 'Deceased') {
        throw new Exception("Applicant is not marked as deceased. Current status: " . $applicantInfo['status']);
    }

    // Update status to Active and clear deceased fields
    $stmt = $conn->prepare("
        UPDATE applicants 
        SET status = 'Active', 
            date_of_death = NULL,
            date_modified = NOW()
        WHERE applicant_id = ?
    ");
    $stmt->execute([$id]);

    $updatedCount = $stmt->rowCount();

    if ($updatedCount === 0) {
        throw new Exception("No record updated.");
    }

    // Commit transaction
    $conn->commit();

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

    // Log the activity
    $logger->log('UNDO_DECEASED', "Restored senior from deceased to active status", [
        'applicant_id' => $id,
        'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
        'control_number' => $applicantInfo['control_number'] ?? null,
        'previous_status' => $applicantInfo['status'],
        'previous_date_of_death' => $applicantInfo['date_of_death'] ?? null,
        'restored_by' => $userName,
        'restored_by_id' => $userId,
        'restored_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'restored_to_status' => 'Active',
        'notes' => 'Death record cleared and status restored to Active'
    ]);

    // Return success response
    sendJsonSuccess("Record restored to Active list.", [
        'applicant_id' => $id,
        'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
        'previous_status' => $applicantInfo['status'],
        'new_status' => 'Active',
        'control_number' => $applicantInfo['control_number'] ?? null,
        'date_of_death_cleared' => $applicantInfo['date_of_death'] ?? null,
        'warning' => 'Death record has been cleared. Please verify the applicant\'s status.'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to restore senior from deceased status', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'restored_by' => $userName ?? 'Unknown'
        ]);
    }

    error_log("Database error in undo_deceased.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to restore senior from deceased status', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'restored_by' => $userName ?? 'Unknown'
        ]);
    }

    error_log("Error in undo_deceased.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
