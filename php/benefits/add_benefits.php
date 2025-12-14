<?php
// add_benefits.php - Improved version with activity logging
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

// Database configuration
$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

// Create connection (using mysqli for consistency with original code)
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    sendJsonError("Database connection failed: " . $conn->connect_error, 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Invalid request method. Only POST is allowed.", 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJsonError("Invalid or missing JSON data.");
}

if (!isset($input['applicant_ids']) || !isset($input['benefits']) || !isset($input['date'])) {
    sendJsonError("Missing required fields: applicant_ids, benefits, or date.");
}

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

try {
    // Create a PDO connection for ActivityLogger (if needed)
    $pdoConn = null;
    if (class_exists('ActivityLogger')) {
        try {
            $pdoConn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdoConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $logger = new ActivityLogger($pdoConn);
        } catch (PDOException $e) {
            // Continue without PDO logger if it fails
            error_log("PDO connection failed for logging: " . $e->getMessage());
        }
    }

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
    $applicantIdsStr = implode(',', array_map('intval', $applicantIds));
    $nameQuery = "SELECT applicant_id, first_name, last_name FROM applicants WHERE applicant_id IN ($applicantIdsStr)";
    $nameResult = $conn->query($nameQuery);

    if ($nameResult) {
        while ($row = $nameResult->fetch_assoc()) {
            $applicantNames[$row['applicant_id']] = $row['first_name'] . ' ' . $row['last_name'];
        }
    }

    // Check if any benefits already exist for these applicants on this date
    $existingBenefits = [];
    $applicantIdsStr = implode(',', array_map('intval', $applicantIds));
    $benefitIds = array_column($benefits, 'id');
    $benefitIdsStr = implode(',', array_map('intval', $benefitIds));

    $checkQuery = "SELECT bd.applicant_id, bd.benefit_id, bd.benefit_name, a.first_name, a.last_name 
                   FROM benefits_distribution bd 
                   JOIN applicants a ON bd.applicant_id = a.applicant_id 
                   WHERE bd.applicant_id IN ($applicantIdsStr) 
                   AND bd.benefit_id IN ($benefitIdsStr) 
                   AND DATE(bd.distribution_date) = ?";

    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $date);
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
            } else {
                throw new Exception("Execute failed for applicant $applicantId, benefit $benefitName: " . $stmt->error);
            }
        }
    }

    $conn->commit();

    // Log the activity
    if ($logger) {
        $logger->log('ADD_BENEFITS', 'Benefits added to multiple beneficiaries', [
            'applicant_ids' => $applicantIds,
            'applicant_count' => count($applicantIds),
            'benefits_added' => $benefitDetails,
            'total_benefits_added' => $successCount,
            'total_amount' => $totalAmount,
            'distribution_date' => $date,
            'added_by' => $userName,
            'added_by_id' => $userId,
            'added_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'summary' => [
                'applicants' => count($applicantIds),
                'benefit_types' => count($benefits),
                'total_distributions' => $successCount,
                'total_amount_distributed' => $totalAmount
            ]
        ]);
    }

    // Also log to file if logger not available
    if (!$logger) {
        $logMessage = date('Y-m-d H:i:s') . " - ADD_BENEFITS - Benefits added to " . count($applicantIds) . " beneficiaries by $userName - Total: $" . $totalAmount;
        error_log($logMessage);
    }

    // Return success response
    sendJsonSuccess("Benefits added successfully to " . count($applicantIds) . " beneficiary(ies)", [
        'count' => $successCount,
        'applicant_count' => count($applicantIds),
        'benefit_count' => count($benefits),
        'total_amount' => $totalAmount,
        'distribution_date' => $date,
        'added_by' => $userName,
        'added_at' => date('Y-m-d H:i:s'),
        'summary' => [
            'applicants' => count($applicantIds),
            'benefit_types' => count($benefits),
            'total_distributions' => $successCount,
            'total_amount' => $totalAmount
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && method_exists($conn, 'rollback') && $conn->errno !== 0) {
        $conn->rollback();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to add benefits', [
            'applicant_ids' => $applicantIds ?? [],
            'benefits_attempted' => $benefits ?? [],
            'distribution_date_attempted' => $date ?? '',
            'error_message' => $e->getMessage(),
            'added_by' => $userName,
            'added_by_id' => $userId,
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
if (ob_get_length()) {
    ob_end_flush();
}
