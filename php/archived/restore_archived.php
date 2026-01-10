<?php
// restore_archived.php - Updated with proper user context detection and activity logging
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

// =========================================================
// CONTEXT DETECTION FUNCTION (Copied from archived.php)
// =========================================================
function detectUserContext($data)
{
    $context = 'admin';
    $userId = 0;
    $userName = 'Unknown';

    if (isset($data['session_context'])) {
        $context = $data['session_context'];
        $_SESSION['session_context'] = $context;
    } elseif (isset($_SESSION['session_context'])) {
        $context = $_SESSION['session_context'];
    }

    if ($context === 'staff') {
        if (isset($data['staff_user_id']) && !empty($data['staff_user_id'])) {
            $userId = (int)$data['staff_user_id'];
            $_SESSION['staff_user_id'] = $userId;
        } elseif (isset($_SESSION['staff_user_id']) && !empty($_SESSION['staff_user_id'])) {
            $userId = (int)$_SESSION['staff_user_id'];
        } elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }
    } else {
        if (isset($data['admin_user_id']) && !empty($data['admin_user_id'])) {
            $userId = (int)$data['admin_user_id'];
            $_SESSION['admin_user_id'] = $userId;
        } elseif (isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id'])) {
            $userId = (int)$_SESSION['admin_user_id'];
        } elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        } else {
            $userId = 57; // Default admin ID
        }
    }

    if ($context === 'staff' && isset($data['staff_user_name']) && !empty($data['staff_user_name'])) {
        $userName = $data['staff_user_name'];
    } elseif ($context === 'admin' && isset($data['admin_user_name']) && !empty($data['admin_user_name'])) {
        $userName = $data['admin_user_name'];
    } elseif (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
        $userName = $_SESSION['fullname'];
    } elseif (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
    } elseif (isset($_SESSION['username'])) {
        $userName = $_SESSION['username'];
    }

    return [
        'context' => $context,
        'user_id' => $userId,
        'user_name' => $userName
    ];
}

// Check database connection
if (!$conn) {
    sendJsonError("Database connection failed", 500);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Invalid request method. Use POST.", 405);
}

// Get input data from multiple sources
$inputData = [];
$input = file_get_contents("php://input");
if (!empty($input)) {
    $jsonData = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE && $jsonData) {
        $inputData = $jsonData;
    }
}

if (empty($inputData)) {
    $inputData = $_POST;
    if (empty($inputData)) {
        $inputData = $_GET;
    }
}

// Get ID from input data
$id = 0;
if (isset($inputData['applicant_id'])) {
    $id = intval($inputData['applicant_id']);
} elseif (isset($inputData['id'])) {
    $id = intval($inputData['id']);
}

if ($id <= 0) {
    sendJsonError("Missing or invalid applicant ID.");
}

// Detect user context
$userInfo = detectUserContext($inputData);
$context = $userInfo['context'];
$userId = $userInfo['user_id'];
$userName = $userInfo['user_name'];

// Initialize logger
$logger = null;
$activityLoggerPath = dirname(__DIR__) . '/settings/ActivityLogger.php';
if (file_exists($activityLoggerPath)) {
    require_once $activityLoggerPath;
    if (class_exists('ActivityLogger')) {
        $logger = new ActivityLogger($conn);
    }
}

error_log("=== RESTORE PROCESS STARTED ===");
error_log("Applicant ID to restore: $id");
error_log("User context: $context, User ID: $userId, User Name: $userName");

try {
    // 1️⃣ Check if archived applicant exists and is not already restored
    $infoStmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, validation, 
               archived_date, date_created, date_of_death, inactive_reason, date_of_inactive,
               archived_by_name, archived_by
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

    $applicantName = $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'];
    error_log("Restoring applicant: $applicantName");
    error_log("Control Number: " . ($applicantInfo['control_number'] ?? 'N/A'));
    error_log("Originally archived by: " . ($applicantInfo['archived_by_name'] ?? 'Unknown'));

    $conn->beginTransaction();

    // 2️⃣ Check if applicant already exists in main table and delete if necessary
    $checkMain = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $checkMain->execute([$id]);
    $existsInMain = $checkMain->fetchColumn() > 0;

    $deletedFromMain = [];
    if ($existsInMain) {
        // Delete in reverse order to avoid foreign key constraints
        $deleteOrder = [
            "benefits_distribution",
            "senior_illness",
            "health_condition",
            "economic_status",
            "addresses",
            "applicant_demographics",
            "applicant_registration_details",
            "applicants"
        ];

        foreach ($deleteOrder as $table) {
            // Check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->fetch()) {
                $deleteStmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
                $deleteStmt->execute([$id]);
                $deletedFromMain[$table] = $deleteStmt->rowCount();
            }
        }
        error_log("Deleted existing records from main tables: " . json_encode($deletedFromMain));
    }

    // 3️⃣ Get column information for dynamic restoration
    $applicantColumns = [];
    $applicantStmt = $conn->query("SHOW COLUMNS FROM applicants");
    while ($col = $applicantStmt->fetch(PDO::FETCH_ASSOC)) {
        // Skip auto-increment columns
        if (strpos($col['Extra'], 'auto_increment') === false) {
            $applicantColumns[] = $col['Field'];
        }
    }

    $archivedColumns = [];
    $archivedStmt = $conn->query("SHOW COLUMNS FROM archived_applicants");
    while ($col = $archivedStmt->fetch(PDO::FETCH_ASSOC)) {
        // Skip archive-specific columns
        if (!in_array($col['Field'], ['archived_date', 'restored_date', 'archived_by', 'archived_by_name'])) {
            $archivedColumns[] = $col['Field'];
        }
    }

    // Find common columns between both tables
    $commonColumns = array_intersect($applicantColumns, $archivedColumns);

    // Create column lists for SQL
    $insertColumns = implode(', ', $commonColumns);
    $selectColumns = implode(', ', $commonColumns);

    // 4️⃣ Restore applicant to main table
    $restoreApplicant = $conn->prepare("
        INSERT INTO applicants ($insertColumns)
        SELECT $selectColumns
        FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");

    $restoreApplicant->execute([$id]);

    if ($restoreApplicant->rowCount() === 0) {
        throw new Exception("Failed to restore applicant. No records were copied.");
    }

    $restoredRecords = ['applicants' => 1];
    error_log("Main applicant restored successfully");

    // 5️⃣ Restore related records from archive tables to main tables
    $relatedTables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness",
        "applicant_registration_details" => "archived_applicant_registration_details",
        "applicant_demographics" => "archived_applicant_demographics",
        "benefits_distribution" => "archived_benefits_distribution"
    ];

    foreach ($relatedTables as $targetTable => $archiveTable) {
        try {
            // Check if archive table exists
            $checkArchive = $conn->query("SHOW TABLES LIKE '$archiveTable'");
            if (!$checkArchive->fetch()) {
                continue; // Skip if archive table doesn't exist
            }

            // Check if target table exists
            $checkTarget = $conn->query("SHOW TABLES LIKE '$targetTable'");
            if (!$checkTarget->fetch()) {
                continue; // Skip if target table doesn't exist
            }

            // Check if there are records to restore
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM $archiveTable WHERE applicant_id = ? AND restored_date IS NULL");
            $countStmt->execute([$id]);
            $recordCount = $countStmt->fetchColumn();

            if ($recordCount > 0) {
                // Get column names from target table (excluding auto_increment columns)
                $columnStmt = $conn->query("SHOW COLUMNS FROM $targetTable");
                $columns = [];

                while ($col = $columnStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (strpos($col['Extra'], 'auto_increment') === false) {
                        $columns[] = $col['Field'];
                    }
                }

                // Get column names from archive table (excluding archive-specific columns)
                $archiveColumnStmt = $conn->query("SHOW COLUMNS FROM $archiveTable");
                $archiveColumns = [];

                while ($col = $archiveColumnStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!in_array($col['Field'], ['archived_date', 'restored_date', 'archived_by', 'archived_by_name'])) {
                        $archiveColumns[] = $col['Field'];
                    }
                }

                // Find common columns between target and archive tables
                $commonTableColumns = array_intersect($columns, $archiveColumns);

                if (empty($commonTableColumns)) {
                    continue; // Skip if no common columns
                }

                $columnList = implode(', ', $commonTableColumns);
                $archiveColumnList = implode(', ', $commonTableColumns);

                // Delete any existing records in target table
                $deleteStmt = $conn->prepare("DELETE FROM $targetTable WHERE applicant_id = ?");
                $deleteStmt->execute([$id]);

                // Copy data from archive to main table
                $copy = $conn->prepare("
                    INSERT INTO $targetTable ($columnList)
                    SELECT $archiveColumnList FROM $archiveTable 
                    WHERE applicant_id = ? AND restored_date IS NULL
                ");
                $copy->execute([$id]);

                $restoredRecords[$targetTable] = $copy->rowCount();
                error_log("Restored " . $copy->rowCount() . " records to $targetTable");
            }
        } catch (Exception $e) {
            // Log error but continue with other tables
            error_log("Error restoring $targetTable: " . $e->getMessage());
            continue;
        }
    }

    // 6️⃣ DELETE THE ARCHIVED RECORDS INSTEAD OF JUST MARKING THEM
    // Delete from all archive tables in reverse order to avoid foreign key issues
    $archiveTablesToDelete = [
        "archived_benefits_distribution",
        "archived_senior_illness",
        "archived_health_condition",
        "archived_economic_status",
        "archived_addresses",
        "archived_applicant_demographics",
        "archived_applicant_registration_details",
        "archived_applicants"
    ];

    $deletedFromArchive = [];

    foreach ($archiveTablesToDelete as $archiveTable) {
        try {
            // Check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE '$archiveTable'");
            if ($checkTable->fetch()) {
                $deleteStmt = $conn->prepare("DELETE FROM $archiveTable WHERE applicant_id = ?");
                $deleteStmt->execute([$id]);
                $deletedFromArchive[$archiveTable] = $deleteStmt->rowCount();
                if ($deleteStmt->rowCount() > 0) {
                    error_log("Deleted " . $deleteStmt->rowCount() . " records from $archiveTable");
                }
            }
        } catch (Exception $e) {
            // If deletion fails due to foreign key, mark as restored instead
            error_log("Warning: Could not delete from $archiveTable: " . $e->getMessage());

            // Try to mark as restored as fallback
            try {
                $markRestored = $conn->prepare("
                    UPDATE $archiveTable SET restored_date = NOW() 
                    WHERE applicant_id = ? AND restored_date IS NULL
                ");
                $markRestored->execute([$id]);
                $deletedFromArchive[$archiveTable . '_marked'] = $markRestored->rowCount();
                error_log("Marked " . $markRestored->rowCount() . " records as restored in $archiveTable");
            } catch (Exception $ex) {
                error_log("Could not mark as restored either: " . $ex->getMessage());
            }
        }
    }

    $conn->commit();
    error_log("Transaction committed successfully");

    // 7️⃣ Calculate archived duration
    $archivedDate = $applicantInfo['archived_date'] ?? null;
    $daysArchived = $archivedDate ? floor((time() - strtotime($archivedDate)) / (60 * 60 * 24)) : 0;

    // 8️⃣ Determine activity type based on context
    if ($context === 'staff') {
        $activityType = 'STAFF_RESTORE_SENIOR';
        $description = "Staff restored archived senior to active list";
    } else {
        $activityType = 'RESTORE_SENIOR';
        $description = "Admin restored archived senior to active list";
    }

    // 9️⃣ Log the restoration activity
    $logDetails = [
        'applicant_id' => $id,
        'applicant_name' => $applicantName,
        'control_number' => $applicantInfo['control_number'] ?? null,
        'original_status' => $applicantInfo['status'] ?? 'Archived',
        'restored_status' => $applicantInfo['status'] ?? 'Active',
        'validation' => $applicantInfo['validation'] ?? 'For Validation',
        'restored_by' => $userName,
        'restored_by_id' => $userId,
        'restored_at' => date('Y-m-d H:i:s'),
        'user_context' => $context,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'archived_date' => $applicantInfo['archived_date'] ?? null,
        'archived_by' => $applicantInfo['archived_by_name'] ?? 'Unknown',
        'archived_by_id' => $applicantInfo['archived_by'] ?? 0,
        'days_archived' => $daysArchived,
        'original_created_date' => $applicantInfo['date_created'] ?? null,
        'records_restored' => $restoredRecords,
        'records_deleted_from_main' => $deletedFromMain,
        'records_deleted_from_archive' => $deletedFromArchive,
        'total_records_restored' => array_sum($restoredRecords),
        'has_date_of_death' => !empty($applicantInfo['date_of_death']),
        'has_inactive_reason' => !empty($applicantInfo['inactive_reason']),
        'dynamic_columns_used' => true,
        'columns_restored' => $commonColumns,
        'notes' => 'Senior restored from archive with proper data cleanup'
    ];

    // Use the ActivityLogger if available
    if ($logger) {
        $logger->log($activityType, $description, $logDetails);
        error_log("Activity logged using ActivityLogger");
    } else {
        // Direct database logging as fallback
        try {
            $logStmt = $conn->prepare("
                INSERT INTO activity_logs 
                (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $logStmt->execute([
                $userId,
                $activityType,
                $description,
                json_encode($logDetails, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
            ]);
            error_log("Activity logged directly to database");
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }

    // Return success response
    sendJsonSuccess("Applicant successfully restored and removed from archive.", [
        'applicant_id' => $id,
        'applicant_name' => $applicantName,
        'control_number' => $applicantInfo['control_number'] ?? null,
        'original_status' => $applicantInfo['status'] ?? 'Archived',
        'restored_status' => $applicantInfo['status'] ?? 'Active',
        'restored_at' => date('Y-m-d H:i:s'),
        'restored_by' => $userName,
        'restored_by_id' => $userId,
        'user_context' => $context,
        'records_restored' => $restoredRecords,
        'records_deleted_from_archive' => $deletedFromArchive,
        'total_records_restored' => array_sum($restoredRecords),
        'days_archived' => $daysArchived,
        'archived_since' => $applicantInfo['archived_date'] ?? null,
        'archived_by' => $applicantInfo['archived_by_name'] ?? 'Unknown',
        'validation' => $applicantInfo['validation'] ?? 'For Validation',
        'dynamic_query' => true,
        'data_cleanup' => 'Archived records were properly deleted to prevent conflicts'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back due to PDOException: " . $e->getMessage());
    }

    error_log("Database error in restore_archived.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back due to Exception: " . $e->getMessage());
    }

    error_log("Error in restore_archived.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
