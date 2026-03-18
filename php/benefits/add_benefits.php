<?php
// add_benefits.php - Fixed column issue
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Enhanced error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');
date_default_timezone_set('Asia/Manila');

// Start output buffering
ob_start();

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the request for debugging
error_log("[" . date('Y-m-d H:i:s') . "] Add Benefits Request: " . $_SERVER['REQUEST_METHOD'] . " from " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));

// Helper functions
function sendJsonError($message, $code = 400, $additionalData = [])
{
    http_response_code($code);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $response = [
        "success" => false,
        "error" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ];

    if (!empty($additionalData)) {
        $response = array_merge($response, $additionalData);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
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

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    sendJsonError("Database connection failed. Please try again later.", 500);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Invalid request method. Only POST is allowed.", 405);
}

// Read and validate JSON input
$input = file_get_contents('php://input');
error_log("Raw input received: " . $input);

if (empty($input)) {
    sendJsonError("No data received.", 400);
}

$jsonData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonError("Invalid JSON format: " . json_last_error_msg(), 400);
}

if (!$jsonData) {
    sendJsonError("Invalid or missing JSON data.", 400);
}

// Validate required fields
$requiredFields = ['applicant_ids', 'benefits', 'date'];
foreach ($requiredFields as $field) {
    if (!isset($jsonData[$field])) {
        sendJsonError("Missing required field: $field", 400);
    }
}

// Validate data types
if (!is_array($jsonData['applicant_ids']) || empty($jsonData['applicant_ids'])) {
    sendJsonError("Invalid or empty applicant_ids.", 400);
}

if (!is_array($jsonData['benefits']) || empty($jsonData['benefits'])) {
    sendJsonError("Invalid or empty benefits.", 400);
}

if (empty(trim($jsonData['date']))) {
    sendJsonError("Distribution date is required.", 400);
}

// Validate date format
if (!strtotime($jsonData['date'])) {
    sendJsonError("Invalid date format for distribution date.", 400);
}

// =========================================================
// USER INFO DETECTION - SIMPLIFIED
// =========================================================
$userInfo = [
    'id' => 0,
    'name' => 'Unknown User',
    'type' => 'Admin',
    'context' => 'admin'
];

// Get user info from JSON if provided
if (isset($jsonData['admin_user_id'])) {
    $userInfo['id'] = intval($jsonData['admin_user_id']);
} elseif (isset($_SESSION['user_id'])) {
    $userInfo['id'] = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['admin_user_id'])) {
    $userInfo['id'] = intval($_SESSION['admin_user_id']);
}

// Get user name from JSON or session
if (isset($jsonData['admin_user_name'])) {
    $userInfo['name'] = $jsonData['admin_user_name'];
} elseif (isset($_SESSION['fullname'])) {
    $userInfo['name'] = $_SESSION['fullname'];
} elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
    $userInfo['name'] = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
}

// Get context
if (isset($jsonData['session_context'])) {
    $userInfo['context'] = $jsonData['session_context'];
} elseif (isset($_SESSION['session_context'])) {
    $userInfo['context'] = $_SESSION['session_context'];
}

// If no ID found, use default admin ID
if ($userInfo['id'] === 0) {
    $userInfo['id'] = 57; // Default admin ID
}

// Get user details from database
if ($userInfo['id'] > 0) {
    try {
        $stmt = $conn->prepare(
            "SELECT firstname, lastname, middlename, user_type, role_name 
             FROM users 
             WHERE id = ? AND status = 'active' 
             LIMIT 1"
        );
        $stmt->bind_param("i", $userInfo['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Build name
            $firstName = $user['firstname'] ?? '';
            $lastName = $user['lastname'] ?? '';
            $middleName = $user['middlename'] ?? '';

            if (!empty($firstName) && !empty($lastName)) {
                $userInfo['name'] = $lastName . ', ' . $firstName;
                if (!empty($middleName)) {
                    $userInfo['name'] = $lastName . ', ' . $firstName . ' ' . substr($middleName, 0, 1) . '.';
                }
            }

            $userInfo['type'] = $user['user_type'] ?? $user['role_name'] ?? 'Admin';

            // Update context based on user type
            if (stripos($userInfo['type'], 'staff') !== false) {
                $userInfo['context'] = 'staff';
            } else {
                $userInfo['context'] = 'admin';
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching user details: " . $e->getMessage());
        // Continue with default values
    }
}

error_log("User Info - ID: {$userInfo['id']}, Name: {$userInfo['name']}, Type: {$userInfo['type']}, Context: {$userInfo['context']}");

// Now proceed with the main logic
$applicantIds = $jsonData['applicant_ids'];
$benefits = $jsonData['benefits'];
$date = $jsonData['date'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get applicant details
    $applicantDetails = [];
    $applicantNames = [];
    if (!empty($applicantIds)) {
        $placeholders = implode(',', array_fill(0, count($applicantIds), '?'));
        $detailsQuery = "SELECT applicant_id, first_name, last_name, status, validation FROM applicants WHERE applicant_id IN ($placeholders)";
        $detailsStmt = $conn->prepare($detailsQuery);

        // Bind parameters
        $types = str_repeat('i', count($applicantIds));
        $detailsStmt->bind_param($types, ...$applicantIds);
        $detailsStmt->execute();
        $detailsResult = $detailsStmt->get_result();

        if ($detailsResult) {
            while ($row = $detailsResult->fetch_assoc()) {
                $applicantId = $row['applicant_id'];
                $applicantDetails[$applicantId] = [
                    'status' => $row['status'],
                    'validation' => $row['validation']
                ];
                $applicantNames[$applicantId] = trim($row['first_name'] . ' ' . $row['last_name']);
            }
        }
        $detailsStmt->close();
    }

    // Helper functions for benefit validation
    function normalizeBenefitName($name)
    {
        return strtolower(trim($name));
    }

    function isBurialBenefit($benefitName)
    {
        $normalized = normalizeBenefitName($benefitName);
        return stripos($normalized, 'burial') !== false;
    }

    function isNonPensionersBenefit($benefitName)
    {
        $normalized = normalizeBenefitName($benefitName);
        $keywords = ['non pension', 'non-pension', 'nonpension', 'non pensioner', 'non-pensioner'];
        foreach ($keywords as $keyword) {
            if (stripos($normalized, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    // Validate each benefit for each applicant
    $validBenefits = [];
    $validationErrors = [];

    foreach ($applicantIds as $applicantId) {
        $applicantStatus = $applicantDetails[$applicantId]['status'] ?? 'Active';
        $applicantValidation = $applicantDetails[$applicantId]['validation'] ?? 'For Validation';
        $applicantName = $applicantNames[$applicantId] ?? "Applicant ID: $applicantId";

        foreach ($benefits as $benefit) {
            $benefitName = $benefit['name'] ?? '';
            $benefitAmount = $benefit['amount'] ?? 0;

            if (empty($benefitName)) {
                $validationErrors[] = "Benefit name is empty for applicant $applicantName";
                continue;
            }

            if (!is_numeric($benefitAmount) || $benefitAmount <= 0) {
                $validationErrors[] = "Invalid amount for $benefitName for applicant $applicantName";
                continue;
            }

            $isBurial = isBurialBenefit($benefitName);
            $isNonPension = isNonPensionersBenefit($benefitName);

            // Check restrictions
            if ($applicantStatus === "Deceased" && !$isBurial) {
                $validationErrors[] = "$applicantName (Deceased) can only receive Burial benefits, not '$benefitName'";
                continue;
            }

            if ($applicantStatus === "Active" && $isBurial) {
                $validationErrors[] = "$applicantName (Active) cannot receive Burial benefits ('$benefitName')";
                continue;
            }

            if ($applicantStatus === "Active" && $applicantValidation === "Validated" && $isNonPension) {
                $validationErrors[] = "$applicantName (Active Validated) cannot receive Non-Pensioners benefits ('$benefitName')";
                continue;
            }

            // If all validations pass, add to valid benefits
            $validBenefits[] = [
                'applicant_id' => $applicantId,
                'benefit_id' => $benefit['id'] ?? 0,
                'benefit_name' => $benefitName,
                'amount' => floatval($benefitAmount),
                'applicant_name' => $applicantName,
                'applicant_status' => $applicantStatus,
                'applicant_validation' => $applicantValidation
            ];
        }
    }

    // If there are validation errors, return them
    if (!empty($validationErrors)) {
        $conn->rollback();
        sendJsonError("Validation failed", 400, [
            'validation_errors' => $validationErrors,
            'total_applicants' => count($applicantIds),
            'total_benefits_attempted' => count($benefits)
        ]);
    }

    // Check for existing benefits on the same date
    $existingBenefits = [];
    if (!empty($validBenefits)) {
        $applicantIdsForCheck = array_unique(array_column($validBenefits, 'applicant_id'));
        $applicantPlaceholders = implode(',', array_fill(0, count($applicantIdsForCheck), '?'));

        $checkQuery = "SELECT 
                        bd.applicant_id,
                        bd.benefit_id,
                        bd.benefit_name,
                        DATE(bd.distribution_date) as distribution_date,
                        a.first_name,
                        a.last_name,
                        a.status
                       FROM benefits_distribution bd 
                       JOIN applicants a ON bd.applicant_id = a.applicant_id 
                       WHERE bd.applicant_id IN ($applicantPlaceholders) 
                       AND DATE(bd.distribution_date) = ?";

        $checkStmt = $conn->prepare($checkQuery);

        // Prepare parameters: applicant IDs + date
        $params = array_merge($applicantIdsForCheck, [$date]);
        $types = str_repeat('i', count($applicantIdsForCheck)) . 's';
        $checkStmt->bind_param($types, ...$params);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        while ($row = $checkResult->fetch_assoc()) {
            $existingBenefits[] = [
                'applicant_id' => $row['applicant_id'],
                'applicant_name' => $row['first_name'] . ' ' . $row['last_name'],
                'benefit_name' => $row['benefit_name'],
                'distribution_date' => $row['distribution_date'],
                'status' => $row['status']
            ];
        }
        $checkStmt->close();
    }

    // If there are existing benefits, check if they conflict
    if (!empty($existingBenefits)) {
        // Check if any of the existing benefits are the same as what we're trying to add
        $conflicts = [];
        foreach ($existingBenefits as $existing) {
            foreach ($validBenefits as $newBenefit) {
                if (
                    $existing['applicant_id'] == $newBenefit['applicant_id'] &&
                    $existing['benefit_name'] == $newBenefit['benefit_name']
                ) {
                    $conflicts[] = "{$existing['applicant_name']} already received '{$existing['benefit_name']}' on {$existing['distribution_date']}";
                }
            }
        }

        if (!empty($conflicts)) {
            $conn->rollback();
            sendJsonError("Some benefits already exist for this date", 400, [
                'conflicts' => $conflicts,
                'existing_benefits' => $existingBenefits
            ]);
        }
    }

    // FIRST, check the table structure
    $checkTableQuery = "SHOW COLUMNS FROM benefits_distribution LIKE 'added_by'";
    $checkResult = $conn->query($checkTableQuery);
    $hasAddedByColumn = $checkResult && $checkResult->num_rows > 0;
    if ($checkResult) $checkResult->free();

    // Prepare insert statement based on table structure
    if ($hasAddedByColumn) {
        // Table has the new columns
        $stmt = $conn->prepare("INSERT INTO benefits_distribution (applicant_id, benefit_id, benefit_name, amount, distribution_date, added_by, added_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    } else {
        // Table doesn't have the new columns - use the old structure
        $stmt = $conn->prepare("INSERT INTO benefits_distribution (applicant_id, benefit_id, benefit_name, amount, distribution_date) VALUES (?, ?, ?, ?, ?)");
        error_log("Note: Using old table structure without added_by columns");
    }

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $successCount = 0;
    $totalAmount = 0;
    $benefitSummary = [];

    foreach ($validBenefits as $benefit) {
        if ($hasAddedByColumn) {
            // Bind with all 7 parameters
            $stmt->bind_param(
                "iisdssi",
                $benefit['applicant_id'],
                $benefit['benefit_id'],
                $benefit['benefit_name'],
                $benefit['amount'],
                $date,
                $userInfo['name'],
                $userInfo['id']
            );
        } else {
            // Bind with only 5 parameters
            $stmt->bind_param(
                "iisds",
                $benefit['applicant_id'],
                $benefit['benefit_id'],
                $benefit['benefit_name'],
                $benefit['amount'],
                $date
            );
        }

        if ($stmt->execute()) {
            $successCount++;
            $totalAmount += $benefit['amount'];

            // Track benefit summary
            $benefitName = $benefit['benefit_name'];
            if (!isset($benefitSummary[$benefitName])) {
                $benefitSummary[$benefitName] = [
                    'count' => 0,
                    'total_amount' => 0
                ];
            }
            $benefitSummary[$benefitName]['count']++;
            $benefitSummary[$benefitName]['total_amount'] += $benefit['amount'];
        } else {
            throw new Exception("Execute failed for applicant {$benefit['applicant_name']}, benefit {$benefit['benefit_name']}: " . $stmt->error);
        }
    }

    $conn->commit();
    $stmt->close();

    // Log the activity
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Benefits added successfully by {$userInfo['name']} (ID: {$userInfo['id']}, Type: {$userInfo['type']}) - " .
        "Applicants: " . count($applicantIds) . ", Benefits added: $successCount, Total amount: ₱" . number_format($totalAmount, 2);
    error_log($logMessage);

    // Return success response
    sendJsonSuccess("Benefits added successfully to " . count($applicantIds) . " beneficiary(ies)", [
        'count' => $successCount,
        'applicant_count' => count($applicantIds),
        'benefit_count' => count(array_unique(array_column($validBenefits, 'benefit_name'))),
        'total_amount' => $totalAmount,
        'distribution_date' => $date,
        'added_by' => $userInfo['name'],
        'added_by_id' => $userInfo['id'],
        'user_type' => $userInfo['type'],
        'user_context' => $userInfo['context'],
        'summary' => [
            'applicants' => count($applicantIds),
            'benefit_types' => count($benefitSummary),
            'total_distributions' => $successCount,
            'total_amount' => $totalAmount,
            'benefit_breakdown' => $benefitSummary
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }

    error_log("Error adding benefits: " . $e->getMessage());
    sendJsonError("Error adding benefits: " . $e->getMessage(), 500, [
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    // Clean up
    if (isset($conn)) {
        $conn->close();
    }
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
