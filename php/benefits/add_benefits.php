<?php
// add_benefits.php - Enhanced with proper admin/staff activity logging
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

// Create connection (using mysqli for consistency with original code)
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    sendJsonError("Database connection failed: " . $conn->connect_error, 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Invalid request method. Only POST is allowed.", 405);
}

// =========================================================
// USER INFO DETECTION (Consistent with other benefit files)
// =========================================================
function getCurrentUserInfo($conn)
{
    // Check for session context in input data
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);

    if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
        error_log("Add Benefits - JSON data received: " . print_r($jsonData, true));

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
            $stmt = $conn->prepare(
                "SELECT firstname, lastname, middlename, user_type, role_name 
                 FROM users 
                 WHERE id = ? AND status = 'active' 
                 LIMIT 1"
            );
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

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

    error_log("Add Benefits - User Info: ID=" . $userId . ", Name=" . $userName . ", Type=" . $userType . ", Context=" . $context);

    return $userInfo;
}

// Get user info
$userInfo = getCurrentUserInfo($conn);
$userId = $userInfo['id'];
$userName = $userInfo['name'];
$userType = $userInfo['type'];
$sessionContext = $userInfo['context'];

// Load ActivityLogger (PDO version - we'll need PDO for logging)
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
    try {
        // Create a PDO connection for ActivityLogger
        $pdoConn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdoConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $logger = new ActivityLogger($pdoConn);
    } catch (PDOException $e) {
        error_log("PDO connection failed for ActivityLogger: " . $e->getMessage());
        $logger = null;
    }
}

// Simple fallback logger if ActivityLogger is not available
if (!$logger) {
    class SimpleLogger
    {
        private $pdo;

        public function __construct($pdo = null)
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

            // Log to database if PDO connection is available
            if ($this->pdo) {
                try {
                    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

                    // Get user info from details
                    $logUserId = $details['added_by_id'] ?? 0;

                    $query = "INSERT INTO activity_logs 
                             (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";

                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute([
                        $logUserId,
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

    // Try to create PDO connection for SimpleLogger
    try {
        $pdoConn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdoConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $logger = new SimpleLogger($pdoConn);
    } catch (PDOException $e) {
        error_log("Failed to create PDO connection for logging: " . $e->getMessage());
        $logger = new SimpleLogger(null); // Fallback to file-only logging
    }
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Log raw input for debugging
error_log("Add Benefits - Raw input: " . file_get_contents('php://input'));

if (!$input) {
    sendJsonError("Invalid or missing JSON data.");
}

if (!isset($input['applicant_ids']) || !isset($input['benefits']) || !isset($input['date'])) {
    sendJsonError("Missing required fields: applicant_ids, benefits, or date.");
}

try {
    // Start transaction
    $conn->begin_transaction();

    $applicantIds = $input['applicant_ids'];
    $benefits = $input['benefits'];
    $date = $input['date'];

    // Validate input
    if (!is_array($applicantIds) || empty($applicantIds)) {
        throw new Exception("No applicant IDs provided.");
    }

    if (!is_array($benefits) || empty($benefits)) {
        throw new Exception("No benefits provided.");
    }

    if (empty(trim($date))) {
        throw new Exception("Distribution date is required.");
    }

    // Validate date format
    if (!strtotime($date)) {
        throw new Exception("Invalid date format for distribution date.");
    }

    // Get applicant names for logging
    $applicantNames = [];
    if (!empty($applicantIds)) {
        $placeholders = implode(',', array_fill(0, count($applicantIds), '?'));
        $nameQuery = "SELECT applicant_id, first_name, last_name FROM applicants WHERE applicant_id IN ($placeholders)";
        $nameStmt = $conn->prepare($nameQuery);

        // Bind parameters
        $types = str_repeat('i', count($applicantIds));
        $nameStmt->bind_param($types, ...$applicantIds);
        $nameStmt->execute();
        $nameResult = $nameStmt->get_result();

        if ($nameResult) {
            while ($row = $nameResult->fetch_assoc()) {
                $applicantNames[$row['applicant_id']] = $row['first_name'] . ' ' . $row['last_name'];
            }
        }
        $nameStmt->close();
    }

    // Check if any benefits already exist for these applicants on this date
    $existingBenefits = [];
    if (!empty($applicantIds) && !empty($benefits)) {
        $benefitIds = array_column($benefits, 'id');

        if (!empty($benefitIds)) {
            $applicantPlaceholders = implode(',', array_fill(0, count($applicantIds), '?'));
            $benefitPlaceholders = implode(',', array_fill(0, count($benefitIds), '?'));

            $checkQuery = "SELECT bd.applicant_id, bd.benefit_id, bd.benefit_name, a.first_name, a.last_name 
                           FROM benefits_distribution bd 
                           JOIN applicants a ON bd.applicant_id = a.applicant_id 
                           WHERE bd.applicant_id IN ($applicantPlaceholders) 
                           AND bd.benefit_id IN ($benefitPlaceholders) 
                           AND DATE(bd.distribution_date) = ?";

            $checkStmt = $conn->prepare($checkQuery);

            // Bind parameters
            $params = array_merge($applicantIds, $benefitIds, [$date]);
            $types = str_repeat('i', count($applicantIds)) . str_repeat('i', count($benefitIds)) . 's';
            $checkStmt->bind_param($types, ...$params);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            while ($row = $checkResult->fetch_assoc()) {
                $existingBenefits[] = [
                    'applicant_id' => $row['applicant_id'],
                    'applicant_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'benefit_id' => $row['benefit_id'],
                    'benefit_name' => $row['benefit_name']
                ];
            }
            $checkStmt->close();
        }
    }

    if (!empty($existingBenefits)) {
        $conflicts = [];
        foreach ($existingBenefits as $conflict) {
            $conflicts[] = "{$conflict['applicant_name']} ({$conflict['benefit_name']})";
        }
        throw new Exception("Some benefits already exist for this date: " . implode(', ', $conflicts));
    }

    // Prepare the insert statement - include benefit_name
    $stmt = $conn->prepare("INSERT INTO benefits_distribution (applicant_id, benefit_id, benefit_name, amount, distribution_date) VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $successCount = 0;
    $benefitDetails = [];
    $totalAmount = 0;
    $benefitSummary = [];

    foreach ($applicantIds as $applicantId) {
        $applicantName = $applicantNames[$applicantId] ?? "Applicant ID: $applicantId";

        foreach ($benefits as $benefit) {
            $benefitId = $benefit['id'];
            $benefitName = $benefit['name'];
            $amount = $benefit['amount'];

            $stmt->bind_param("iisds", $applicantId, $benefitId, $benefitName, $amount, $date);

            if ($stmt->execute()) {
                $successCount++;
                $totalAmount += $amount;

                $benefitDetails[] = [
                    'applicant_id' => $applicantId,
                    'applicant_name' => $applicantName,
                    'benefit_id' => $benefitId,
                    'benefit_name' => $benefitName,
                    'amount' => $amount
                ];

                // Track benefit summary
                if (!isset($benefitSummary[$benefitName])) {
                    $benefitSummary[$benefitName] = [
                        'count' => 0,
                        'total_amount' => 0
                    ];
                }
                $benefitSummary[$benefitName]['count']++;
                $benefitSummary[$benefitName]['total_amount'] += $amount;
            } else {
                throw new Exception("Execute failed for applicant $applicantId, benefit $benefitName: " . $stmt->error);
            }
        }
    }

    $conn->commit();

    // Log the activity with proper context
    if ($logger) {
        // Determine activity type based on user context
        if ($sessionContext === 'staff') {
            $activityType = 'STAFF_ADD_BENEFITS_TO_BENEFICIARIES';
            $description = 'Staff added benefits to multiple beneficiaries';
        } else {
            $activityType = 'ADD_BENEFITS_TO_BENEFICIARIES';
            $description = 'Admin added benefits to multiple beneficiaries';
        }

        // Create log details
        $logDetails = [
            'applicant_ids' => $applicantIds,
            'applicant_count' => count($applicantIds),
            'applicant_names' => array_values($applicantNames),
            'benefits_added' => $benefitDetails,
            'total_benefits_added' => $successCount,
            'total_amount' => $totalAmount,
            'distribution_date' => $date,
            'added_by' => $userName,
            'added_by_id' => $userId,
            'user_type' => $userType,
            'user_context' => $sessionContext,
            'added_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'summary' => [
                'applicants' => count($applicantIds),
                'benefit_types' => count($benefits),
                'total_distributions' => $successCount,
                'total_amount_distributed' => $totalAmount,
                'benefit_breakdown' => $benefitSummary
            ]
        ];

        $logger->log($activityType, $description, $logDetails);
    }

    // Also log to file if logger not available
    if (!$logger) {
        $logMessage = date('Y-m-d H:i:s') . " - $activityType - Benefits added to " . count($applicantIds) . " beneficiaries by $userName - Total: $" . $totalAmount;
        error_log($logMessage);
    }

    // Return success response with user context
    sendJsonSuccess("Benefits added successfully to " . count($applicantIds) . " beneficiary(ies)", [
        'count' => $successCount,
        'applicant_count' => count($applicantIds),
        'benefit_count' => count($benefits),
        'total_amount' => $totalAmount,
        'distribution_date' => $date,
        'added_by' => $userName,
        'added_by_id' => $userId,
        'user_type' => $userType,
        'user_context' => $sessionContext,
        'activity_type_logged' => $activityType ?? 'ADD_BENEFITS_TO_BENEFICIARIES',
        'added_at' => date('Y-m-d H:i:s'),
        'summary' => [
            'applicants' => count($applicantIds),
            'benefit_types' => count($benefits),
            'total_distributions' => $successCount,
            'total_amount' => $totalAmount,
            'benefit_breakdown' => $benefitSummary
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && method_exists($conn, 'rollback') && $conn->errno !== 0) {
        $conn->rollback();
    }

    // Log error with proper context
    if ($logger) {
        $activityType = ($sessionContext === 'staff') ? 'STAFF_ERROR' : 'ERROR';
        $description = ($sessionContext === 'staff')
            ? 'Staff failed to add benefits to beneficiaries'
            : 'Admin failed to add benefits to beneficiaries';

        $logger->log($activityType, $description, [
            'applicant_ids' => $applicantIds ?? [],
            'benefits_attempted' => $benefits ?? [],
            'distribution_date_attempted' => $date ?? '',
            'error_message' => $e->getMessage(),
            'added_by' => $userName,
            'added_by_id' => $userId,
            'user_type' => $userType,
            'user_context' => $sessionContext,
            'error_type' => 'Benefit Distribution Error',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    }

    // Also log to file if logger not available
    if (!$logger) {
        $logMessage = date('Y-m-d H:i:s') . " - ERROR - Failed to add benefits by $userName - Error: " . $e->getMessage();
        error_log($logMessage);
    }

    error_log("Error adding benefits: " . $e->getMessage());
    sendJsonError("Error adding benefits: " . $e->getMessage());
}

// Clean up resources
if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
if (isset($pdoConn)) {
    $pdoConn = null;
}

// Clean up output buffer
if (ob_get_level() > 0) {
    ob_end_flush();
}
