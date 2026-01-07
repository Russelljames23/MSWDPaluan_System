<?php
// save_benefits.php - Improved version with activity logging
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
    sendJsonError("Database connection failed: " . $e->getMessage(), 500);
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
    $logger->log('ERROR', 'Attempted to save invalid benefits', [
        'invalid_benefits' => $invalidBenefits,
        'attempted_by' => $userName,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    
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
    
    // Log warning if duplicates found
    if (!empty($duplicates)) {
        $logger->log('WARNING', 'Duplicate benefits detected', [
            'duplicate_benefits' => $duplicates,
            'new_benefits' => $newBenefits,
            'added_by' => $userName,
            'added_by_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
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
    
    // Log the activity
    $logger->log('ADD_BENEFIT_TYPE', 'New benefit types added to system', [
        'benefits_added' => $insertedBenefits,
        'total_added' => count($insertedBenefits),
        'duplicates_skipped' => $duplicates,
        'added_by' => $userName,
        'added_by_id' => $userId,
        'added_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'benefit_count_after' => count($newBenefits) + count($duplicates) // approximate
    ]);
    
    // Return success response
    $responseData = [
        'added_count' => count($insertedBenefits),
        'benefits_added' => $insertedBenefits,
        'added_by' => $userName,
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
    
    // Log error
    $logger->log('ERROR', 'Failed to save new benefit types', [
        'benefits_attempted' => $validatedBenefits,
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'added_by' => $userName,
        'added_by_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Database Error'
    ]);
    
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
    
    // Log error
    $logger->log('ERROR', 'Failed to save new benefit types', [
        'benefits_attempted' => $validatedBenefits,
        'error_message' => $e->getMessage(),
        'added_by' => $userName,
        'added_by_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'error_type' => 'Application Error'
    ]);
    
    error_log("Error in save_benefits.php: " . $e->getMessage());
    sendJsonError("Failed to save benefits: " . $e->getMessage(), 500);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}