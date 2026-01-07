<?php
// delete_benefit.php - Improved version with activity logging
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

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
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

// Initialize logger
if (class_exists('ActivityLogger')) {
    // ActivityLogger constructor only takes $conn parameter
    $logger = new ActivityLogger($pdo);
} else {
    // Create minimal logger for debugging
    class SimpleLogger {
        private $pdo;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function log($type, $desc, $details = null) {
            // Log to file for debugging
            $logMessage = date('Y-m-d H:i:s') . " - Activity: $type - $desc";
            if ($details) {
                $logMessage .= " - Details: " . json_encode($details);
            }
            error_log($logMessage);
            
            // Also try to log to database directly for debugging
            if ($this->pdo) {
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
                    
                    $stmt = $this->pdo->prepare($query);
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
    $logger = new SimpleLogger($pdo);
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

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJsonError("Invalid or missing JSON data.");
}

if (!isset($input['id'])) {
    sendJsonError("No benefit ID provided.");
}

$id = $input['id'];

// Validate ID
if (!is_numeric($id) || $id <= 0) {
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
        $logger->log('ERROR', 'Attempted to delete non-existent benefit', [
            'attempted_benefit_id' => $id,
            'deleted_by' => $userName,
            'deleted_by_id' => $userId,
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
        $logger->log('ERROR', 'Attempted to delete benefit with existing distributions', [
            'benefit_id' => $id,
            'benefit_name' => $benefit['benefit_name'],
            'distribution_count' => $distributionCount,
            'unique_beneficiaries' => $distributionDetails['unique_beneficiaries'] ?? 0,
            'earliest_distribution' => $distributionDetails['earliest_distribution'] ?? null,
            'latest_distribution' => $distributionDetails['latest_distribution'] ?? null,
            'deleted_by' => $userName,
            'deleted_by_id' => $userId,
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
    
    // Log the deletion activity
    $logger->log('DELETE_BENEFIT', 'Benefit type deleted from system', [
        'benefit_id' => $id,
        'benefit_name' => $benefit['benefit_name'],
        'created_at' => $benefit['created_at'] ?? null,
        'last_updated' => $benefit['updated_at'] ?? null,
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'deleted_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'affected_rows' => $affectedRows,
        'deletion_summary' => [
            'id' => $id,
            'name' => $benefit['benefit_name'],
            'created' => $benefit['created_at'] ?? null,
            'deleted' => date('Y-m-d H:i:s'),
            'deleted_by_user' => $userName,
            'deleted_by_user_id' => $userId
        ],
        'notes' => 'Benefit was not in use (no existing distributions)'
    ]);
    
    // Return success response
    sendJsonSuccess("Benefit deleted successfully", [
        'benefit_id' => $id,
        'benefit_name' => $benefit['benefit_name'],
        'deleted_by' => $userName,
        'deleted_at' => date('Y-m-d H:i:s'),
        'affected_rows' => $affectedRows,
        'deletion_summary' => "Benefit '{$benefit['benefit_name']}' (ID: $id) was deleted from the system."
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    $logger->log('ERROR', 'Failed to delete benefit', [
        'benefit_id' => $id ?? 'unknown',
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
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
        $pdo->rollBack();
    }
    
    // Log error
    $logger->log('ERROR', 'Failed to delete benefit', [
        'benefit_id' => $id ?? 'unknown',
        'error_message' => $e->getMessage(),
        'deleted_by' => $userName,
        'deleted_by_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Application Error'
    ]);
    
    error_log("Error in delete_benefit.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}