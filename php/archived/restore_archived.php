<?php
// restore_archived.php - Improved version with proper data restoration
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

// Get ID from POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    sendJsonError("Missing or invalid applicant ID.");
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

    $conn->beginTransaction();

    // 1️⃣ Check if archived applicant exists
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
    }

    // 3️⃣ Restore applicant to main table
    $restoreApplicant = $conn->prepare("
        INSERT INTO applicants (
            applicant_id, last_name, first_name, middle_name, suffix, gender, age, current_age,
            civil_status, birth_date, citizenship, religion, birth_place, educational_attainment,
            living_arrangement, validation, status, date_of_death, inactive_reason, date_of_inactive,
            remarks, date_created, date_modified, control_number, age_last_updated
        )
        SELECT 
            applicant_id, last_name, first_name, middle_name, suffix, gender, age, current_age,
            civil_status, birth_date, citizenship, religion, birth_place, educational_attainment,
            living_arrangement, validation, status, date_of_death, inactive_reason, date_of_inactive,
            remarks, date_created, NOW(), control_number, age_last_updated
        FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");

    $restoreApplicant->execute([$id]);

    $restoredRecords = ['applicants' => 1];

    // 4️⃣ Restore related records
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

            // Count records in archive before restoring
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

                $columnList = implode(', ', $columns);

                // Get column names from archive table (excluding archive-specific columns)
                $archiveColumnStmt = $conn->query("SHOW COLUMNS FROM $archiveTable");
                $archiveColumns = [];

                while ($col = $archiveColumnStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!in_array($col['Field'], ['archived_date', 'restored_date', 'archived_by', 'archived_by_name'])) {
                        $archiveColumns[] = $col['Field'];
                    }
                }

                $archiveColumnList = implode(', ', $archiveColumns);

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

                // Mark as restored in archive
                $updateStmt = $conn->prepare("
                    UPDATE $archiveTable SET restored_date = NOW() 
                    WHERE applicant_id = ? AND restored_date IS NULL
                ");
                $updateStmt->execute([$id]);
            }
        } catch (Exception $e) {
            // Log error but continue with other tables
            error_log("Error restoring $targetTable: " . $e->getMessage());
            continue;
        }
    }

    // 5️⃣ Mark applicant as restored in archived_applicants
    $updateApplicant = $conn->prepare("
        UPDATE archived_applicants SET restored_date = NOW() 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $updateApplicant->execute([$id]);

    $conn->commit();

    // 6️⃣ Calculate archived duration
    $archivedDate = $applicantInfo['archived_date'] ?? null;
    $daysArchived = $archivedDate ? floor((time() - strtotime($archivedDate)) / (60 * 60 * 24)) : 0;

    // 7️⃣ Log the restoration activity
    try {
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs 
            (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $logDetails = json_encode([
            'applicant_id' => $id,
            'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
            'control_number' => $applicantInfo['control_number'] ?? null,
            'original_status' => $applicantInfo['status'] ?? 'Archived',
            'restored_status' => $applicantInfo['status'] ?? 'Active',
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
        ], JSON_UNESCAPED_UNICODE);

        $logStmt->execute([
            $userId,
            'RESTORE_SENIOR',
            'Archived senior restored to active list',
            $logDetails,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }

    // Return success response
    sendJsonSuccess("Applicant successfully restored.", [
        'applicant_id' => $id,
        'applicant_name' => $applicantInfo['first_name'] . ' ' . $applicantInfo['last_name'],
        'control_number' => $applicantInfo['control_number'] ?? null,
        'original_status' => $applicantInfo['status'] ?? 'Archived',
        'restored_status' => $applicantInfo['status'] ?? 'Active',
        'restored_at' => date('Y-m-d H:i:s'),
        'records_restored' => $restoredRecords,
        'total_records' => array_sum($restoredRecords),
        'days_archived' => $daysArchived,
        'archived_since' => $applicantInfo['archived_date'] ?? null,
        'validation' => $applicantInfo['validation'] ?? 'For Validation'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Database error in restore_archived.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Error in restore_archived.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
