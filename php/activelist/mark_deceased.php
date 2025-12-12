<?php
// mark_deceased.php - Improved version with proper activity logging
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

if (empty($data['date_of_death'])) {
    sendJsonError("Missing date of death");
}

// Initialize logger - FIXED: Correct constructor usage
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
    $logger = new SimpleLogger();
}

// Process IDs (supporting both single and array)
$ids = is_array($data['applicant_id']) ? $data['applicant_id'] : [$data['applicant_id']];
$ids = array_filter($ids, function ($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
});

if (empty($ids)) {
    sendJsonError("No valid applicant IDs provided");
}

// Validate date format
$date_of_death = $data['date_of_death'];
if (!strtotime($date_of_death)) {
    sendJsonError("Invalid date format for date of death");
}

try {
    $updatedApplicants = [];
    $conn->beginTransaction();

    // Get applicant info before updating
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, date_of_death
        FROM applicants 
        WHERE applicant_id IN ($placeholders)
    ");
    $infoStmt->execute($ids);
    $applicantsInfo = $infoStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($applicantsInfo) !== count($ids)) {
        throw new Exception("One or more applicants not found");
    }

    // Check if any applicants are already deceased
    $alreadyDeceased = array_filter($applicantsInfo, function ($applicant) {
        return $applicant['status'] === 'Deceased';
    });

    if (!empty($alreadyDeceased)) {
        $deceasedNames = array_map(function ($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $alreadyDeceased);
        throw new Exception("Some applicants are already marked as deceased: " . implode(', ', $deceasedNames));
    }

    // Check if applicants are already inactive
    $alreadyInactive = array_filter($applicantsInfo, function ($applicant) {
        return $applicant['status'] === 'Inactive';
    });

    if (!empty($alreadyInactive)) {
        $inactiveNames = array_map(function ($a) {
            return $a['first_name'] . ' ' . $a['last_name'];
        }, $alreadyInactive);
        throw new Exception("Some applicants are already inactive. Please restore them first before marking as deceased: " . implode(', ', $inactiveNames));
    }

    // Update all applicants
    $sql = "
        UPDATE applicants 
        SET status = 'Deceased', 
            date_of_death = ?,
            date_modified = NOW()
        WHERE applicant_id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    // Combine parameters: date_of_death + ids
    $params = array_merge([$date_of_death], $ids);
    $stmt->execute($params);

    $updatedCount = $stmt->rowCount();

    if ($updatedCount === 0) {
        throw new Exception("No applicants were updated");
    }

    // Commit transaction
    $conn->commit();

    // Get user info for logging
    session_start();
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
    $applicantNames = array_map(function ($a) {
        return $a['first_name'] . ' ' . $a['last_name'];
    }, $applicantsInfo);

    $applicantControlNumbers = array_filter(array_column($applicantsInfo, 'control_number'));

    $logger->log('MARK_DECEASED', "Marked " . count($ids) . " senior(s) as deceased", [
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'control_numbers' => !empty($applicantControlNumbers) ? $applicantControlNumbers : 'N/A',
        'date_of_death' => $date_of_death,
        'marked_by' => $userName,
        'marked_by_id' => $userId,
        'marked_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'updated_count' => $updatedCount,
        'previous_status' => array_column($applicantsInfo, 'status')
    ]);

    // Return success response
    sendJsonSuccess("Senior(s) successfully marked as deceased", [
        'updated_count' => $updatedCount,
        'applicant_ids' => $ids,
        'applicant_names' => $applicantNames,
        'date_of_death' => $date_of_death
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to mark senior(s) as deceased', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'date_of_death_attempted' => $date_of_death,
            'marked_by' => $userName ?? 'Unknown'
        ]);
    }

    error_log("Database error in mark_deceased.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to mark senior(s) as deceased', [
            'applicant_ids' => $ids,
            'error_message' => $e->getMessage(),
            'date_of_death_attempted' => $date_of_death,
            'marked_by' => $userName ?? 'Unknown'
        ]);
    }

    error_log("Error in mark_deceased.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
