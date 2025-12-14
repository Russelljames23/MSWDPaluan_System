<?php
// archived.php - Improved version with activity logging
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

// Get ID from POST or GET
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if ($id <= 0) {
    sendJsonError("Missing or invalid applicant ID.");
}

// Initialize logger
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

    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
    }

    // 1️⃣ Fetch applicant details for logging
    $stmt = $conn->prepare("
        SELECT applicant_id, first_name, last_name, control_number, status, 
               validation, gender, age, birth_date
        FROM applicants 
        WHERE applicant_id = ?
    ");
    $stmt->execute([$id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        throw new Exception("Applicant not found.");
    }

    // 2️⃣ Check if already archived
    $checkArchived = $conn->prepare("SELECT COUNT(*) FROM archived_applicants WHERE applicant_id = ? AND restored_date IS NULL");
    $checkArchived->execute([$id]);
    if ($checkArchived->fetchColumn() > 0) {
        throw new Exception("Applicant is already archived.");
    }

    // 3️⃣ Ensure archived_applicants table exists with proper structure
    $conn->exec("
        CREATE TABLE IF NOT EXISTS archived_applicants (
            applicant_id int(11) NOT NULL,
            last_name varchar(100) NOT NULL,
            first_name varchar(100) NOT NULL,
            middle_name varchar(100) DEFAULT NULL,
            gender enum('Male','Female') NOT NULL,
            age int(11) DEFAULT NULL,
            current_age int(11) DEFAULT NULL,
            civil_status enum('Single','Married','Separated','Widowed','Divorced') DEFAULT NULL,
            birth_date date DEFAULT NULL,
            citizenship varchar(100) DEFAULT NULL,
            birth_place varchar(255) DEFAULT NULL,
            living_arrangement enum('Owned','Living alone','Living with relatives','Rent') DEFAULT NULL,
            validation enum('Validated','For Validation') DEFAULT 'For Validation',
            status enum('Active','Inactive','Deceased') NOT NULL DEFAULT 'Active',
            date_of_death date DEFAULT NULL,
            inactive_reason varchar(255) DEFAULT NULL,
            date_of_inactive date DEFAULT NULL,
            remarks text DEFAULT NULL,
            date_created timestamp NOT NULL DEFAULT current_timestamp(),
            date_modified datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            control_number varchar(50) DEFAULT NULL,
            age_last_updated date DEFAULT NULL,
            archived_date datetime DEFAULT current_timestamp(),
            restored_date datetime DEFAULT NULL,
            PRIMARY KEY (applicant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // 4️⃣ Fetch complete applicant data for archiving
    $fullApplicantStmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = ?");
    $fullApplicantStmt->execute([$id]);
    $fullApplicant = $fullApplicantStmt->fetch(PDO::FETCH_ASSOC);

    // Archive applicant - explicitly list columns to avoid column count mismatch
    $insertApplicant = $conn->prepare("
        INSERT INTO archived_applicants (
            applicant_id, last_name, first_name, middle_name, gender, age, current_age, 
            civil_status, birth_date, citizenship, birth_place, living_arrangement, 
            validation, status, date_of_death, inactive_reason, date_of_inactive, 
            remarks, date_created, date_modified, control_number, age_last_updated, archived_date
        )
        VALUES (
            :applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :current_age, 
            :civil_status, :birth_date, :citizenship, :birth_place, :living_arrangement, 
            :validation, :status, :date_of_death, :inactive_reason, :date_of_inactive, 
            :remarks, :date_created, :date_modified, :control_number, :age_last_updated, NOW()
        )
    ");

    $insertApplicant->execute([
        ':applicant_id' => $fullApplicant['applicant_id'],
        ':last_name' => $fullApplicant['last_name'],
        ':first_name' => $fullApplicant['first_name'],
        ':middle_name' => $fullApplicant['middle_name'],
        ':gender' => $fullApplicant['gender'],
        ':age' => $fullApplicant['age'],
        ':current_age' => $fullApplicant['current_age'],
        ':civil_status' => $fullApplicant['civil_status'],
        ':birth_date' => $fullApplicant['birth_date'],
        ':citizenship' => $fullApplicant['citizenship'],
        ':birth_place' => $fullApplicant['birth_place'],
        ':living_arrangement' => $fullApplicant['living_arrangement'],
        ':validation' => $fullApplicant['validation'],
        ':status' => $fullApplicant['status'],
        ':date_of_death' => $fullApplicant['date_of_death'],
        ':inactive_reason' => $fullApplicant['inactive_reason'],
        ':date_of_inactive' => $fullApplicant['date_of_inactive'],
        ':remarks' => $fullApplicant['remarks'],
        ':date_created' => $fullApplicant['date_created'],
        ':date_modified' => $fullApplicant['date_modified'],
        ':control_number' => $fullApplicant['control_number'],
        ':age_last_updated' => $fullApplicant['age_last_updated'] ?? null
    ]);

    // Track related records for logging
    $relatedRecords = [];
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $source => $archive) {
        // Ensure archive table exists with proper structure
        $conn->exec("CREATE TABLE IF NOT EXISTS $archive LIKE $source");
        $conn->exec("
            ALTER TABLE $archive 
            ADD COLUMN IF NOT EXISTS archived_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS restored_date DATETIME NULL
        ");

        // Count records before archiving
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM $source WHERE applicant_id = ?");
        $countStmt->execute([$id]);
        $recordCount = $countStmt->fetchColumn();

        if ($recordCount > 0) {
            $relatedRecords[$source] = $recordCount;

            // Get column names from source table (excluding auto_increment columns)
            $stmt = $conn->query("SHOW COLUMNS FROM $source");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Extra'] !== 'auto_increment') {
                    $columns[] = $row['Field'];
                }
            }
            $columnList = implode(', ', $columns);

            // Archive with explicit column names
            $archiveStmt = $conn->prepare("
                INSERT INTO $archive ($columnList, archived_date)
                SELECT $columnList, NOW() FROM $source WHERE applicant_id = ?
            ");
            $archiveStmt->execute([$id]);
        }
    }

    // 6️⃣ Delete from original tables (in reverse order to maintain referential integrity)
    $deleteOrder = [
        "senior_illness",
        "health_condition",
        "economic_status",
        "addresses",
        "applicants"
    ];

    $deletedCounts = [];
    foreach ($deleteOrder as $table) {
        $deleteStmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
        $deleteResult = $deleteStmt->execute([$id]);

        if (!$deleteResult) {
            throw new Exception("Failed to delete from $table for applicant $id");
        }

        $deletedCounts[$table] = $deleteStmt->rowCount();
    }

    if ($conn->inTransaction()) {
        $conn->commit();
    }

    // Log the activity
    $logger->log('ARCHIVE_SENIOR', "Archived senior and related records", [
        'applicant_id' => $id,
        'applicant_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
        'control_number' => $applicant['control_number'] ?? null,
        'status' => $applicant['status'],
        'validation' => $applicant['validation'],
        'archived_by' => $userName,
        'archived_by_id' => $userId,
        'archived_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'related_records_archived' => $relatedRecords,
        'tables_deleted_from' => $deletedCounts,
        'total_records_archived' => array_sum($relatedRecords) + 1, // +1 for the main applicant
        'archive_summary' => [
            'applicant' => 1,
            'addresses' => $relatedRecords['addresses'] ?? 0,
            'economic_status' => $relatedRecords['economic_status'] ?? 0,
            'health_condition' => $relatedRecords['health_condition'] ?? 0,
            'senior_illness' => $relatedRecords['senior_illness'] ?? 0
        ]
    ]);

    // Return success response
    sendJsonSuccess("Senior archived successfully.", [
        'applicant_id' => $id,
        'applicant_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
        'control_number' => $applicant['control_number'] ?? null,
        'archived_at' => date('Y-m-d H:i:s'),
        'records_archived' => [
            'applicant' => 1,
            'addresses' => $relatedRecords['addresses'] ?? 0,
            'economic_status' => $relatedRecords['economic_status'] ?? 0,
            'health_condition' => $relatedRecords['health_condition'] ?? 0,
            'senior_illness' => $relatedRecords['senior_illness'] ?? 0
        ],
        'total_records' => array_sum($relatedRecords) + 1
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to archive senior', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'archived_by' => $userName ?? 'Unknown',
            'error_type' => 'Database Error'
        ]);
    }

    error_log("Database error in archived.php: " . $e->getMessage());
    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error
    if ($logger) {
        $logger->log('ERROR', 'Failed to archive senior', [
            'applicant_id' => $id,
            'error_message' => $e->getMessage(),
            'archived_by' => $userName ?? 'Unknown',
            'error_type' => 'Application Error'
        ]);
    }

    error_log("Error in archived.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 400);
}

// Clean up output buffer
if (ob_get_length()) {
    ob_end_flush();
}
