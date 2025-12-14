<?php
// restore_archived.php - Improved version with proper status preservation
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

// Get ID from POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    sendJsonError("Missing or invalid applicant ID.");
}

// Initialize logger
if (class_exists('ActivityLogger')) {
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

    $transactionStarted = false;
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
        $transactionStarted = true;
    }

    // Get archived applicant info for logging and status determination
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, validation, 
               archived_date, date_created, date_of_death, inactive_reason, date_of_inactive
        FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $infoStmt->execute([$id]);
    $applicantInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicantInfo) {
        // Check if applicant exists at all
        $checkExists = $conn->prepare("SELECT COUNT(*) FROM archived_applicants WHERE applicant_id = ?");
        $checkExists->execute([$id]);
        if ($checkExists->fetchColumn() > 0) {
            throw new Exception("Archived applicant has already been restored.");
        } else {
            throw new Exception("Archived applicant not found.");
        }
    }

    // Determine the appropriate status based on archived data
    $status = 'Active'; // Default

    // Check for deceased status
    if (!empty($applicantInfo['date_of_death'])) {
        $status = 'Deceased';
    }
    // Check for inactive status
    elseif (!empty($applicantInfo['inactive_reason']) || !empty($applicantInfo['date_of_inactive'])) {
        $status = 'Inactive';
    }
    // Check if status is explicitly set in archived data
    elseif (!empty($applicantInfo['status']) && in_array($applicantInfo['status'], ['Active', 'Inactive', 'Deceased'])) {
        $status = $applicantInfo['status'];
    }

    // 1️⃣ First, check if applicant exists in applicants table and delete if found
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $checkStmt->execute([$id]);
    $existsInApplicants = $checkStmt->fetchColumn() > 0;

    $deletedFromMain = [];
    if ($existsInApplicants) {
        // Delete any existing records in main tables to avoid conflicts
        $deleteOrder = [
            "senior_illness",
            "health_condition",
            "economic_status",
            "addresses",
            "applicants"
        ];

        foreach ($deleteOrder as $table) {
            $deleteStmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
            $deleteStmt->execute([$id]);
            $deletedFromMain[$table] = $deleteStmt->rowCount();
        }
    }

    // 2️⃣ Fetch archived applicant
    $stmt = $conn->prepare("SELECT * FROM archived_applicants WHERE applicant_id = ? AND restored_date IS NULL");
    $stmt->execute([$id]);
    $archivedApplicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archivedApplicant) {
        throw new Exception("Archived applicant not found or already restored.");
    }

    // Calculate archived duration
    $archivedDate = $archivedApplicant['archived_date'] ?? null;
    $daysArchived = $archivedDate ? floor((time() - strtotime($archivedDate)) / (60 * 60 * 24)) : 0;

    // 3️⃣ Restore applicant to main table with preserved status
    $insertApplicant = $conn->prepare("
        INSERT INTO applicants (
            applicant_id, last_name, first_name, middle_name, gender, age, current_age, 
            civil_status, birth_date, citizenship, birth_place, living_arrangement, 
            validation, status, date_of_death, inactive_reason, date_of_inactive, 
            remarks, date_created, date_modified, control_number, age_last_updated
        )
        VALUES (
            :applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :current_age, 
            :civil_status, :birth_date, :citizenship, :birth_place, :living_arrangement, 
            :validation, :status, :date_of_death, :inactive_reason, :date_of_inactive, 
            :remarks, :date_created, NOW(), :control_number, :age_last_updated
        )
    ");

    $insertApplicant->execute([
        ':applicant_id' => $archivedApplicant['applicant_id'],
        ':last_name' => $archivedApplicant['last_name'],
        ':first_name' => $archivedApplicant['first_name'],
        ':middle_name' => $archivedApplicant['middle_name'],
        ':gender' => $archivedApplicant['gender'],
        ':age' => $archivedApplicant['age'],
        ':current_age' => $archivedApplicant['current_age'],
        ':civil_status' => $archivedApplicant['civil_status'],
        ':birth_date' => $archivedApplicant['birth_date'],
        ':citizenship' => $archivedApplicant['citizenship'],
        ':birth_place' => $archivedApplicant['birth_place'],
        ':living_arrangement' => $archivedApplicant['living_arrangement'],
        ':validation' => $archivedApplicant['validation'],
        ':status' => $status, // Use the determined status
        ':date_of_death' => $archivedApplicant['date_of_death'],
        ':inactive_reason' => $archivedApplicant['inactive_reason'],
        ':date_of_inactive' => $archivedApplicant['date_of_inactive'],
        ':remarks' => $archivedApplicant['remarks'],
        ':date_created' => $archivedApplicant['date_created'],
        ':control_number' => $archivedApplicant['control_number'],
        ':age_last_updated' => $archivedApplicant['age_last_updated'] ?? null
    ]);

    $restoredRecords = ['applicants' => 1];

    // 4️⃣ Restore related data with explicit column mapping
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $target => $archive) {
        // Check if archive table exists
        $checkArchive = $conn->query("SHOW TABLES LIKE '$archive'");
        if (!$checkArchive->fetch()) {
            continue; // Skip if archive table doesn't exist
        }

        // Count records in archive before restoring
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM $archive WHERE applicant_id = ? AND restored_date IS NULL");
        $countStmt->execute([$id]);
        $recordCount = $countStmt->fetchColumn();

        if ($recordCount > 0) {
            // Get column names from target table (excluding auto_increment columns)
            $stmt = $conn->query("SHOW COLUMNS FROM $target");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Extra'] !== 'auto_increment') {
                    $columns[] = $row['Field'];
                }
            }
            $columnList = implode(', ', $columns);

            // Delete any existing records in target table
            $deleteStmt = $conn->prepare("DELETE FROM $target WHERE applicant_id = ?");
            $deleteStmt->execute([$id]);

            // Copy data from archive to main table
            $copy = $conn->prepare("
                INSERT INTO $target ($columnList)
                SELECT $columnList FROM $archive 
                WHERE applicant_id = ? AND restored_date IS NULL
            ");
            $copy->execute([$id]);

            $restoredRecords[$target] = $copy->rowCount();

            // Mark as restored in archive
            $updateStmt = $conn->prepare("
                UPDATE $archive SET restored_date = NOW() 
                WHERE applicant_id = ? AND restored_date IS NULL
            ");
            $updateStmt->execute([$id]);
        }
    }

    // 5️⃣ Mark applicant as restored in archived_applicants
    $updateApplicant = $conn->prepare("
        UPDATE archived_applicants SET restored_date = NOW() 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $updateApplicant->execute([$id]);

    if ($transactionStarted && $conn->inTransaction()) {
        $conn->commit();
    }

    // Log the restoration activity
    $logger->log('RESTORE_SENIOR', 'Archived senior restored', [
        'applicant_id' => $id,
        'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
        'control_number' => $applicantInfo['control_number'] ?? null,
        'original_status' => $applicantInfo['status'] ?? 'Archived',
        'restored_status' => $status,
        'validation' => $applicantInfo['validation'] ?? 'For Validation',
        'restored_by' => $userName,
        'restored_by_id' => $userId,
        'restored_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'archived_date' => $applicantInfo['archived_date'] ?? null,
        'days_archived' => $daysArchived,
        'original_created_date' => $applicantInfo['date_created'] ?? null,
        'records_restored' => $restoredRecords,
        'records_deleted_from_main' => $deletedFromMain,
        'total_records_restored' => array_sum($restoredRecords),
        'has_date_of_death' => !empty($applicantInfo['date_of_death']),
        'has_inactive_reason' => !empty($applicantInfo['inactive_reason']),
        'notes' => 'Senior restored from archive with preserved status'
    ]);

    // Return success response
    sendJsonSuccess("Applicant successfully restored.", [
        'applicant_id' => $id,
        'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
        'control_number' => $applicantInfo['control_number'] ?? null,
        'original_status' => $applicantInfo['status'] ?? 'Archived',
        'restored_status' => $status,
        'restored_at' => date('Y-m-d H:i:s'),
        'records_restored' => $restoredRecords,
        'total_records' => array_sum($restoredRecords),
        'days_archived' => $daysArchived,
        'archived_since' => $applicantInfo['archived_date'] ?? null,
        'validation' => $applicantInfo['validation'] ?? 'For Validation'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($transactionStarted) && $transactionStarted && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to restore archived senior', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'restored_by' => $userName ?? 'Unknown',
            'error_type' => 'Database Error'
        ]);
    }

    error_log("Database error in restore_archived.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($transactionStarted) && $transactionStarted && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to restore archived senior', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'restored_by' => $userName ?? 'Unknown',
            'error_type' => 'Application Error'
        ]);
    }

    error_log("Error in restore_archived.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
