<?php
// mark_inactive.php - Enhanced version with better activity logging
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Include database
include '../db.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions (borrowed from update_status.php)
function sendJsonError($message, $code = 400) {
    http_response_code($code);
    if (ob_get_length()) ob_clean();
    echo json_encode([
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
$activityLoggerPath = dirname(__DIR__) . '/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
} elseif (file_exists(dirname(__DIR__) . '/settings/ActivityLogger.php')) {
    require_once dirname(__DIR__) . '/settings/ActivityLogger.php';
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

// Initialize logger with fallback (like update_status.php)
$user_id = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 0);
$user_name = $_SESSION['username'] ?? ($_SESSION['fullname'] ?? ($_SESSION['firstname'] . ' ' . $_SESSION['lastname'] ?? 'System'));

if (class_exists('ActivityLogger')) {
    $logger = new ActivityLogger($conn, $user_id, $user_name);
} else {
    // Create minimal logger like update_status.php does
    class SimpleLogger {
        public function log($type, $desc, $details = null) {
            error_log("Activity: $type - $desc");
            return true;
        }
    }
    $logger = new SimpleLogger($conn);
}

// Process IDs (supporting both single and array like update_status.php)
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
    
    // Log the activity (enhanced logging)
    $applicantNames = array_map(function($a) {
        return $a['first_name'] . ' ' . $a['last_name'];
    }, $applicantsInfo);
    
    $applicantControlNumbers = array_filter(array_column($applicantsInfo, 'control_number'));
    
    $logger->log('MARK_INACTIVE', "Marked " . count($ids) . " senior(s) as inactive", [
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'control_numbers' => !empty($applicantControlNumbers) ? $applicantControlNumbers : 'N/A',
        'reason' => $reason,
        'inactive_date' => $date_of_inactive,
        'marked_by' => $user_name,
        'marked_by_id' => $user_id,
        'marked_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'updated_count' => $updatedCount
    ]);
    
    // Return success response
    sendJsonSuccess("Senior(s) successfully marked as inactive", [
        'updated_count' => $updatedCount,
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to mark senior(s) as inactive', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'reason_attempted' => $reason,
            'marked_by' => $user_name
        ]);
    }
    
    error_log("Database error in mark_inactive.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to mark senior(s) as inactive', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'reason_attempted' => $reason,
            'marked_by' => $user_name
        ]);
    }
    
    error_log("Error in mark_inactive.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}