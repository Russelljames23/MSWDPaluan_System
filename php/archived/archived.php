<?php
// archived.php - Fixed version with proper primary key handling
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_errors.log');

// Start output buffering
ob_start();

// Start session early for context detection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once '../db.php';

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
// CONTEXT DETECTION FUNCTION
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
            $userId = 57;
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

// Get ID from multiple possible sources
$id = 0;
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

if (isset($inputData['applicant_id'])) {
    $id = intval($inputData['applicant_id']);
} elseif (isset($inputData['id'])) {
    $id = intval($inputData['id']);
}

if ($id <= 0) {
    sendJsonError("No applicant ID provided. Please select a senior to archive.");
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

try {
    error_log("=== ARCHIVE PROCESS STARTED ===");
    error_log("Applicant ID to archive: $id");
    error_log("User context: $context, User ID: $userId, User Name: $userName");

    // =========================================================
    // CHECK IF APPLICANT EXISTS
    // =========================================================
    $checkApplicant = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $checkApplicant->execute([$id]);
    if ($checkApplicant->fetchColumn() == 0) {
        throw new Exception("Applicant with ID $id not found.");
    }

    // =========================================================
    // GET APPLICANT DATA
    // =========================================================
    $applicantStmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = ?");
    $applicantStmt->execute([$id]);
    $applicantData = $applicantStmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicantData) {
        throw new Exception("Could not retrieve applicant data for ID $id");
    }

    $applicantName = $applicantData['last_name'] . ', ' . $applicantData['first_name'];
    error_log("Applicant: $applicantName");
    error_log("Control Number: " . ($applicantData['control_number'] ?? 'N/A'));
    error_log("Validation: " . ($applicantData['validation'] ?? 'N/A'));

    // =========================================================
    // CHECK IF ALREADY ARCHIVED
    // =========================================================
    $checkArchived = $conn->prepare("
        SELECT COUNT(*) FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $checkArchived->execute([$id]);
    if ($checkArchived->fetchColumn() > 0) {
        sendJsonSuccess("Applicant is already archived.", [
            'applicant_id' => $id,
            'applicant_name' => $applicantName,
            'validation' => $applicantData['validation'] ?? null,
            'already_archived' => true
        ]);
    }

    // Start transaction
    $conn->beginTransaction();
    error_log("Transaction started");

    // =========================================================
    // ARCHIVE MAIN APPLICANT DATA
    // =========================================================
    // Get columns from archived_applicants table
    $archiveColumnsStmt = $conn->query("SHOW COLUMNS FROM archived_applicants");
    $archiveColumns = [];
    while ($column = $archiveColumnsStmt->fetch(PDO::FETCH_ASSOC)) {
        // Skip auto_increment and archive-specific columns we'll handle separately
        if (
            $column['Field'] !== 'archived_date' &&
            $column['Field'] !== 'restored_date' &&
            $column['Field'] !== 'archived_by' &&
            $column['Field'] !== 'archived_by_name' &&
            strpos($column['Extra'], 'auto_increment') === false
        ) {
            $archiveColumns[] = $column['Field'];
        }
    }

    // Build INSERT query
    $archiveColumnList = implode(', ', array_map(function ($col) {
        return "`$col`";
    }, $archiveColumns));

    $placeholders = implode(', ', array_fill(0, count($archiveColumns), '?'));

    // Get values from applicantData for the archive columns
    $values = [];
    foreach ($archiveColumns as $column) {
        if (isset($applicantData[$column])) {
            $values[] = $applicantData[$column];
        } else {
            $values[] = null;
        }
    }

    // Add archive metadata
    $archiveColumnList .= ', archived_date, restored_date, archived_by, archived_by_name';
    $placeholders .= ', NOW(), NULL, ?, ?';
    $values[] = $userId;
    $values[] = $userName;

    // Insert into archived_applicants
    $archiveSql = "INSERT INTO archived_applicants ($archiveColumnList) VALUES ($placeholders)";
    error_log("Archive SQL: " . str_replace(array("\r", "\n"), " ", $archiveSql));

    $archiveStmt = $conn->prepare($archiveSql);
    $archiveResult = $archiveStmt->execute($values);

    if (!$archiveResult) {
        $errorInfo = $archiveStmt->errorInfo();
        throw new Exception("Failed to archive main applicant: " . ($errorInfo[2] ?? 'Unknown error'));
    }

    $mainArchiveId = $conn->lastInsertId();
    error_log("Main applicant archived. Insert ID: $mainArchiveId");

    // =========================================================
    // ARCHIVE RELATED TABLES
    // =========================================================
    $relatedTables = [
        "addresses" => "archived_addresses",
        "applicant_demographics" => "archived_applicant_demographics",
        "applicant_registration_details" => "archived_applicant_registration_details",
        "applicant_educational_background" => "archived_applicant_educational_background",
        "benefits_distribution" => "archived_benefits_distribution",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    $archivedCounts = [];
    $totalRelatedRecords = 0;

    foreach ($relatedTables as $sourceTable => $archiveTable) {
        $tableRecords = 0;
        try {
            // Check if source table exists
            $checkSource = $conn->query("SHOW TABLES LIKE '$sourceTable'");
            if (!$checkSource->fetch()) {
                error_log("Source table $sourceTable does not exist, skipping.");
                continue;
            }

            // Check if archive table exists
            $checkArchive = $conn->query("SHOW TABLES LIKE '$archiveTable'");
            if (!$checkArchive->fetch()) {
                error_log("Archive table $archiveTable does not exist, skipping.");
                continue;
            }

            // Get all records from source table for this applicant
            $selectStmt = $conn->prepare("SELECT * FROM `$sourceTable` WHERE applicant_id = ?");
            $selectStmt->execute([$id]);
            $records = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($records) === 0) {
                error_log("No records found in $sourceTable for applicant ID $id");
                continue;
            }

            // Get archive table structure
            $archiveColsStmt = $conn->query("SHOW COLUMNS FROM `$archiveTable`");
            $archiveTableStructure = [];
            while ($col = $archiveColsStmt->fetch(PDO::FETCH_ASSOC)) {
                $archiveTableStructure[$col['Field']] = $col;
            }

            // Archive each record
            foreach ($records as $record) {
                // Prepare columns and values for this record
                $archiveColumns = [];
                $archiveValues = [];

                foreach ($archiveTableStructure as $field => $colInfo) {
                    // Skip auto_increment columns
                    if (strpos($colInfo['Extra'], 'auto_increment') !== false) {
                        continue;
                    }

                    // Handle special archive columns
                    if ($field === 'archived_date') {
                        $archiveColumns[] = "`$field`";
                        $archiveValues[] = 'NOW()';
                    } elseif ($field === 'restored_date') {
                        $archiveColumns[] = "`$field`";
                        $archiveValues[] = 'NULL';
                    } elseif (array_key_exists($field, $record)) {
                        $archiveColumns[] = "`$field`";
                        $archiveValues[] = '?';
                    } else {
                        // Column exists in archive table but not in source record
                        $archiveColumns[] = "`$field`";
                        $archiveValues[] = 'NULL';
                    }
                }

                // Build INSERT query for this record
                $insertColumns = implode(', ', $archiveColumns);
                $insertValues = implode(', ', $archiveValues);
                $insertSql = "INSERT INTO `$archiveTable` ($insertColumns) VALUES ($insertValues)";

                // Prepare values for binding
                $bindValues = [];
                foreach ($archiveTableStructure as $field => $colInfo) {
                    if (strpos($colInfo['Extra'], 'auto_increment') !== false) {
                        continue;
                    }

                    if ($field === 'archived_date' || $field === 'restored_date') {
                        // These are handled by SQL functions
                        continue;
                    } elseif (array_key_exists($field, $record)) {
                        $bindValues[] = $record[$field];
                    }
                    // NULL values are already in the SQL
                }

                // Execute the insert
                $insertStmt = $conn->prepare($insertSql);
                if ($insertStmt->execute($bindValues)) {
                    $tableRecords++;
                } else {
                    $errorInfo = $insertStmt->errorInfo();
                    error_log("Failed to insert record into $archiveTable: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            }

            if ($tableRecords > 0) {
                // Delete from source table
                $deleteStmt = $conn->prepare("DELETE FROM `$sourceTable` WHERE applicant_id = ?");
                $deleteStmt->execute([$id]);

                $archivedCounts[$sourceTable] = $tableRecords;
                $totalRelatedRecords += $tableRecords;
                error_log("Archived $tableRecords records from $sourceTable to $archiveTable");
            }
        } catch (Exception $e) {
            error_log("Error archiving $sourceTable: " . $e->getMessage());
            // Continue with other tables even if one fails
            continue;
        }
    }

    // Delete applicant from main table
    $deleteApplicant = $conn->prepare("DELETE FROM applicants WHERE applicant_id = ?");
    $deleteApplicant->execute([$id]);
    $deletedRows = $deleteApplicant->rowCount();
    error_log("Deleted $deletedRows record(s) from main applicants table");

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");

    // =========================================================
    // VERIFY ARCHIVED DATA
    // =========================================================
    $verifyStmt = $conn->prepare("
        SELECT applicant_id, last_name, first_name, validation, control_number, archived_date, archived_by_name
        FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
        ORDER BY archived_date DESC LIMIT 1
    ");
    $verifyStmt->execute([$id]);
    $archivedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$archivedData) {
        throw new Exception("Failed to verify archived record after commit.");
    }

    // =========================================================
    // LOG THE ACTIVITY
    // =========================================================
    if ($context === 'staff') {
        $activityType = 'STAFF_ARCHIVE_SENIOR';
        $description = "Staff archived senior and related records";
    } else {
        $activityType = 'ARCHIVE_SENIOR';
        $description = "Admin archived senior and related records";
    }

    $logDetails = [
        'applicant_id' => $id,
        'applicant_name' => $applicantName,
        'control_number' => $applicantData['control_number'] ?? null,
        'validation' => $applicantData['validation'] ?? null,
        'archived_validation' => $archivedData['validation'] ?? null,
        'archived_by' => $archivedData['archived_by_name'] ?? $userName,
        'archived_by_id' => $userId,
        'user_context' => $context,
        'archived_at' => $archivedData['archived_date'] ?? date('Y-m-d H:i:s'),
        'records_archived' => $archivedCounts,
        'total_related_records' => $totalRelatedRecords,
        'total_records' => $totalRelatedRecords + 1,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];

    // Use the logger if available
    if ($logger) {
        $logger->log($activityType, $description, $logDetails);
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
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }

    // Return success response
    sendJsonSuccess("Senior '$applicantName' has been successfully archived with all related records.", [
        'applicant_id' => $id,
        'applicant_name' => $applicantName,
        'control_number' => $applicantData['control_number'] ?? null,
        'validation' => $applicantData['validation'] ?? null,
        'archived_validation' => $archivedData['validation'] ?? null,
        'archived_at' => $archivedData['archived_date'] ?? date('Y-m-d H:i:s'),
        'archived_by' => $archivedData['archived_by_name'] ?? $userName,
        'archived_by_id' => $userId,
        'context' => $context,
        'records_archived' => $archivedCounts,
        'total_related_records' => $totalRelatedRecords,
        'total_records' => $totalRelatedRecords + 1,
        'success' => true
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back due to PDOException: " . $e->getMessage());
    }
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back due to Exception: " . $e->getMessage());
    }
    sendJsonError($e->getMessage(), 400);
} finally {
    // Clean up output buffer
    if (ob_get_length()) {
        ob_end_flush();
    }
}
