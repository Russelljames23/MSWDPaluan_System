<?php
// update_benefit.php - Improved version with activity logging
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

$id = $input['id'] ?? null;
$benefit_name = trim($input['benefit_name'] ?? '');

// Validate input
if (!$id || !is_numeric($id) || $id <= 0) {
    sendJsonError("Invalid benefit ID.");
}

if (empty($benefit_name)) {
    sendJsonError("Benefit name cannot be empty.");
}

if (strlen($benefit_name) > 255) {
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
        throw new Exception("Benefit not found with ID: $id");
    }
    
    // Check if new name is the same as current name
    if (strcasecmp($currentBenefit['benefit_name'], $benefit_name) === 0) {
        $pdo->rollBack();
        sendJsonError("Benefit name is unchanged. No update needed.");
    }
    
    // Check if new name already exists (case-insensitive, excluding current benefit)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM benefits WHERE LOWER(benefit_name) = LOWER(?) AND id != ?");
    $checkStmt->execute([$benefit_name, $id]);
    $exists = $checkStmt->fetchColumn() > 0;
    
    if ($exists) {
        $pdo->rollBack();
        
        // Log duplicate name attempt
        $logger->log('ERROR', 'Attempted to update benefit with duplicate name', [
            'benefit_id' => $id,
            'current_name' => $currentBenefit['benefit_name'],
            'new_name_attempted' => $benefit_name,
            'updated_by' => $userName,
            'updated_by_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'error_type' => 'Duplicate Benefit Name'
        ]);
        
        sendJsonError("Benefit name '{$benefit_name}' already exists. Please use a different name.");
    }
    
    // Update benefit
    $updateStmt = $pdo->prepare("UPDATE benefits SET benefit_name = :benefit_name, updated_at = NOW() WHERE id = :id");
    $updateStmt->execute(['benefit_name' => $benefit_name, 'id' => $id]);
    
    $affectedRows = $updateStmt->rowCount();
    
    if ($affectedRows === 0) {
        throw new Exception("No changes were made. Benefit may not exist or data is unchanged.");
    }
    
    $pdo->commit();
    
    // Log the activity
    $logger->log('UPDATE_BENEFIT', 'Benefit type updated', [
        'benefit_id' => $id,
        'old_name' => $currentBenefit['benefit_name'],
        'new_name' => $benefit_name,
        'created_at' => $currentBenefit['created_at'] ?? null,
        'updated_by' => $userName,
        'updated_by_id' => $userId,
        'updated_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'affected_rows' => $affectedRows,
        'change_summary' => [
            'id' => $id,
            'from' => $currentBenefit['benefit_name'],
            'to' => $benefit_name,
            'change_type' => 'benefit_name_update'
        ]
    ]);
    
    // Return success response
    sendJsonSuccess("Benefit updated successfully", [
        'benefit_id' => $id,
        'old_name' => $currentBenefit['benefit_name'],
        'new_name' => $benefit_name,
        'updated_by' => $userName,
        'updated_at' => date('Y-m-d H:i:s'),
        'affected_rows' => $affectedRows
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    $logger->log('ERROR', 'Failed to update benefit', [
        'benefit_id' => $id ?? 'unknown',
        'new_name_attempted' => $benefit_name ?? '',
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'updated_by' => $userName,
        'updated_by_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Database Error'
    ]);
    
    error_log("Database error in update_benefit.php: " . $e->getMessage());
    
    // Check for duplicate entry error (MySQL error code 1062)
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        sendJsonError("Benefit name '{$benefit_name}' already exists. Please use a different name.", 409);
    }
    
    sendJsonError("Failed to update benefit: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    $logger->log('ERROR', 'Failed to update benefit', [
        'benefit_id' => $id ?? 'unknown',
        'new_name_attempted' => $benefit_name ?? '',
        'error_message' => $e->getMessage(),
        'updated_by' => $userName,
        'updated_by_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Application Error'
    ]);
    
    error_log("Error in update_benefit.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}