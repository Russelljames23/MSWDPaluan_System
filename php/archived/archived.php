<?php
// archived.php - Fixed and improved version
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

// Check database connection
if (!$conn) {
    sendJsonError("Database connection failed", 500);
}

// Get ID from multiple possible sources
$id = 0;

// Check JSON input first
$input = file_get_contents("php://input");
if (!empty($input)) {
    $data = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE && $data) {
        if (isset($data['applicant_id'])) {
            $id = intval($data['applicant_id']);
        } elseif (isset($data['id'])) {
            $id = intval($data['id']);
        }
    } else {
        parse_str($input, $parsedData);
        if (isset($parsedData['applicant_id'])) {
            $id = intval($parsedData['applicant_id']);
        } elseif (isset($parsedData['id'])) {
            $id = intval($parsedData['id']);
        }
    }
}

// Check POST data
if ($id <= 0) {
    if (isset($_POST['applicant_id'])) {
        $id = intval($_POST['applicant_id']);
    } elseif (isset($_POST['id'])) {
        $id = intval($_POST['id']);
    }
}

// Check GET data
if ($id <= 0) {
    if (isset($_GET['applicant_id'])) {
        $id = intval($_GET['applicant_id']);
    } elseif (isset($_GET['id'])) {
        $id = intval($_GET['id']);
    }
}

if ($id <= 0) {
    sendJsonError("No applicant ID provided. Please select a senior to archive.");
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

    // Start transaction
    $conn->beginTransaction();

    // 1️⃣ Check if applicant exists in main table
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $checkStmt->execute([$id]);
    $applicantExists = $checkStmt->fetchColumn() > 0;

    if (!$applicantExists) {
        // Check if already archived
        $checkArchived = $conn->prepare("
            SELECT applicant_id, first_name, last_name FROM archived_applicants 
            WHERE applicant_id = ? AND restored_date IS NULL
        ");
        $checkArchived->execute([$id]);
        $archivedApplicant = $checkArchived->fetch(PDO::FETCH_ASSOC);

        if ($archivedApplicant) {
            sendJsonSuccess("Senior already archived.", [
                'applicant_id' => $id,
                'applicant_name' => $archivedApplicant['first_name'] . ' ' . $archivedApplicant['last_name'],
                'already_archived' => true,
                'archived_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            throw new Exception("Applicant not found in database.");
        }
    }

    // 2️⃣ Check if already archived (not restored)
    $checkArchived = $conn->prepare("
        SELECT COUNT(*) FROM archived_applicants 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $checkArchived->execute([$id]);
    if ($checkArchived->fetchColumn() > 0) {
        $conn->rollBack();

        // Get applicant name for response
        $nameStmt = $conn->prepare("SELECT first_name, last_name FROM applicants WHERE applicant_id = ?");
        $nameStmt->execute([$id]);
        $applicant = $nameStmt->fetch(PDO::FETCH_ASSOC);

        sendJsonSuccess("Applicant '" . $applicant['first_name'] . ' ' . $applicant['last_name'] . "' is already archived.", [
            'applicant_id' => $id,
            'applicant_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
            'already_archived' => true
        ]);
    }

    // 3️⃣ Archive applicant data
    $archiveApplicant = $conn->prepare("
        INSERT INTO archived_applicants (
            applicant_id, last_name, first_name, middle_name, suffix, gender, age, current_age,
            civil_status, birth_date, citizenship, religion, birth_place, educational_attainment,
            living_arrangement, validation, status, date_of_death, inactive_reason, date_of_inactive,
            remarks, date_created, date_modified, control_number, age_last_updated,
            archived_by, archived_by_name, archived_date
        )
        SELECT 
            applicant_id, last_name, first_name, middle_name, suffix, gender, age, current_age,
            civil_status, birth_date, citizenship, religion, birth_place, educational_attainment,
            living_arrangement, validation, status, date_of_death, inactive_reason, date_of_inactive,
            remarks, date_created, date_modified, control_number, age_last_updated,
            ?, ?, NOW()
        FROM applicants 
        WHERE applicant_id = ?
    ");

    $archiveApplicant->execute([$userId, $userName, $id]);

    // 4️⃣ Archive related records with dynamic column mapping
    $relatedTables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness",
        "applicant_registration_details" => "archived_applicant_registration_details",
        "applicant_demographics" => "archived_applicant_demographics",
        "benefits_distribution" => "archived_benefits_distribution"
    ];

    $archivedCounts = [];

    foreach ($relatedTables as $sourceTable => $archiveTable) {
        try {
            // Check if source table exists
            $checkSource = $conn->query("SHOW TABLES LIKE '$sourceTable'");
            if (!$checkSource->fetch()) {
                continue; // Skip if source table doesn't exist
            }

            // Check if archive table exists, create if not
            $checkArchive = $conn->query("SHOW TABLES LIKE '$archiveTable'");
            if (!$checkArchive->fetch()) {
                // Skip if archive table doesn't exist and we can't create it
                continue;
            }

            // Get column names from source table (excluding auto_increment columns)
            $columnStmt = $conn->query("SHOW COLUMNS FROM $sourceTable");
            $columns = [];
            $autoIncrementCol = null;

            while ($col = $columnStmt->fetch(PDO::FETCH_ASSOC)) {
                if (strpos($col['Extra'], 'auto_increment') !== false) {
                    $autoIncrementCol = $col['Field'];
                } else {
                    $columns[] = $col['Field'];
                }
            }

            $columnList = implode(', ', $columns);

            // Count records before archiving
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM $sourceTable WHERE applicant_id = ?");
            $countStmt->execute([$id]);
            $recordCount = $countStmt->fetchColumn();

            if ($recordCount > 0) {
                // Copy data to archive table
                $copyStmt = $conn->prepare("
                    INSERT INTO $archiveTable ($columnList, archived_date)
                    SELECT $columnList, NOW() 
                    FROM $sourceTable 
                    WHERE applicant_id = ?
                ");
                $copyStmt->execute([$id]);

                $archivedCounts[$sourceTable] = $copyStmt->rowCount();

                // Delete from source table
                $deleteStmt = $conn->prepare("DELETE FROM $sourceTable WHERE applicant_id = ?");
                $deleteStmt->execute([$id]);
            }
        } catch (Exception $e) {
            // Log error but continue with other tables
            error_log("Error archiving $sourceTable: " . $e->getMessage());
            continue;
        }
    }

    // 5️⃣ Delete applicant from main table
    $deleteApplicant = $conn->prepare("DELETE FROM applicants WHERE applicant_id = ?");
    $deleteApplicant->execute([$id]);

    // Commit transaction
    $conn->commit();

    // 6️⃣ Log the activity
    try {
        // Get applicant name for log
        $nameStmt = $conn->prepare("SELECT first_name, last_name, control_number FROM archived_applicants WHERE applicant_id = ?");
        $nameStmt->execute([$id]);
        $applicant = $nameStmt->fetch(PDO::FETCH_ASSOC);

        $logStmt = $conn->prepare("
            INSERT INTO activity_logs 
            (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $logDetails = json_encode([
            'applicant_id' => $id,
            'applicant_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
            'control_number' => $applicant['control_number'] ?? null,
            'archived_by' => $userName,
            'archived_by_id' => $userId,
            'archived_at' => date('Y-m-d H:i:s'),
            'records_archived' => $archivedCounts,
            'total_records' => array_sum($archivedCounts) + 1 // +1 for applicant
        ], JSON_UNESCAPED_UNICODE);

        $logStmt->execute([
            $userId,
            'ARCHIVE_SENIOR',
            'Archived senior and related records',
            $logDetails,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
        ]);
    } catch (Exception $e) {
        // Don't fail if logging fails
        error_log("Activity logging failed: " . $e->getMessage());
    }

    // Return success response
    sendJsonSuccess("Senior archived successfully.", [
        'applicant_id' => $id,
        'applicant_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
        'control_number' => $applicant['control_number'] ?? null,
        'archived_at' => date('Y-m-d H:i:s'),
        'records_archived' => $archivedCounts,
        'total_records' => array_sum($archivedCounts) + 1
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    sendJsonError("Database error: " . $e->getMessage(), 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    sendJsonError($e->getMessage(), 400);
} finally {
    // Clean up output buffer
    if (ob_get_length()) {
        ob_end_flush();
    }
}
