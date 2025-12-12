<?php
// update_status.php
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

// Helper functions
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
    sendJsonError("Missing applicant ID(s)");
}

if (empty($data['validation'])) {
    sendJsonError("Missing validation status");
}

// Initialize logger
$user_id = $_SESSION['user_id'] ?? 57; // Fallback to admin ID
$user_name = $_SESSION['username'] ?? ($_SESSION['fullname'] ?? 'System');

if (class_exists('ActivityLogger')) {
    $logger = new ActivityLogger($conn, $user_id, $user_name);
} else {
    // Create minimal logger
    class SimpleLogger {
        public function log($type, $desc, $details = null) {
            error_log("Activity: $type - $desc");
            return true;
        }
    }
    $logger = new SimpleLogger();
}

// Process IDs
$ids = is_array($data['applicant_id']) ? $data['applicant_id'] : [$data['applicant_id']];
$ids = array_filter($ids, function($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
});

if (empty($ids)) {
    sendJsonError("No valid applicant IDs provided");
}

// Check for control numbers if validating
if ($data['validation'] === 'Validated') {
    if (empty($data['control_numbers']) || !is_array($data['control_numbers'])) {
        sendJsonError("Control numbers are required for validation");
    }
    
    if (count($data['control_numbers']) !== count($ids)) {
        sendJsonError("Number of control numbers does not match number of selected applicants");
    }
    
    // Validate each control number
    foreach ($data['control_numbers'] as $index => $controlNumber) {
        if (empty(trim($controlNumber))) {
            sendJsonError("Control number at position " . ($index + 1) . " is empty");
        }
        
        $controlNumber = trim($controlNumber);
        if (strlen($controlNumber) > 50) {
            sendJsonError("Control number '{$controlNumber}' is too long (max 50 characters)");
        }
        
        // Check for duplicates in this request
        $firstOccurrence = array_search($controlNumber, $data['control_numbers']);
        if ($firstOccurrence !== false && $firstOccurrence !== $index) {
            sendJsonError("Duplicate control number: '{$controlNumber}'");
        }
    }
    
    // Check for duplicates in database
    $placeholders = implode(',', array_fill(0, count($data['control_numbers']), '?'));
    $checkSql = "SELECT control_number FROM applicants WHERE control_number IN ($placeholders) AND validation = 'Validated'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute($data['control_numbers']);
    $existing = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($existing)) {
        sendJsonError("Control numbers already exist: " . implode(', ', $existing));
    }
}

try {
    if ($data['validation'] === 'Validated') {
        // Validate each applicant
        $updatedApplicants = [];
        $conn->beginTransaction();
        
        foreach ($ids as $index => $applicantId) {
            $controlNumber = trim($data['control_numbers'][$index]);
            
            // Verify applicant exists
            $verifyStmt = $conn->prepare("SELECT first_name, last_name FROM applicants WHERE applicant_id = ?");
            $verifyStmt->execute([$applicantId]);
            $applicant = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$applicant) {
                throw new Exception("Applicant ID {$applicantId} not found");
            }
            
            // Update with control number
            $updateStmt = $conn->prepare("UPDATE applicants SET validation = ?, control_number = ?, date_modified = NOW() WHERE applicant_id = ?");
            $updateStmt->execute([$data['validation'], $controlNumber, $applicantId]);
            
            // Log the validation
            $logger->log('VALIDATE_SENIOR', "Validated senior {$applicant['first_name']} {$applicant['last_name']}", [
                'applicant_id' => $applicantId,
                'control_number' => $controlNumber,
                'validated_by' => $user_name
            ]);
            
            $updatedApplicants[] = [
                'id' => $applicantId,
                'name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
                'control_number' => $controlNumber
            ];
        }
        
        $conn->commit();
        sendJsonSuccess("Applicants validated successfully", ['updated' => $updatedApplicants]);
        
    } else {
        // Remove validation (set to 'For Validation')
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Get names before update for logging
        $namesStmt = $conn->prepare("SELECT applicant_id, first_name, last_name FROM applicants WHERE applicant_id IN ($placeholders)");
        $namesStmt->execute($ids);
        $applicants = $namesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update all at once
        $sql = "UPDATE applicants SET validation = ?, control_number = NULL, date_modified = NOW() WHERE applicant_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_merge([$data['validation']], $ids));
        
        // Log the update
        $applicantNames = array_map(function($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $applicants);
        
        $logger->log('UPDATE_SENIOR', "Updated validation status for " . count($ids) . " applicants", [
            'applicant_ids' => $ids,
            'applicant_names' => $applicantNames,
            'new_status' => $data['validation'],
            'updated_by' => $user_name
        ]);
        
        sendJsonSuccess("Validation status updated successfully", [
            'updated_count' => count($ids),
            'applicant_ids' => $ids
        ]);
    }
    
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error in update_status.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error in update_status.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 500);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
?>