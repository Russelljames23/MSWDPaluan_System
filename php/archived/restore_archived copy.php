<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) throw new Exception("Missing applicant ID.");

    $transactionStarted = false;
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
        $transactionStarted = true;
    }

    // 1️⃣ First, check if applicant exists in applicants table and delete if found
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $checkStmt->execute([$id]);
    $existsInApplicants = $checkStmt->fetchColumn() > 0;

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
        }
    }

    // 2️⃣ Fetch archived applicant
    $stmt = $conn->prepare("SELECT * FROM archived_applicants WHERE applicant_id = ? AND restored_date IS NULL");
    $stmt->execute([$id]);
    $archivedApplicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archivedApplicant) {
        throw new Exception("Archived applicant not found or already restored.");
    }

    // 3️⃣ Restore applicant to main table with explicit column mapping
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
        ':status' => 'Active', // Always set to Active when restoring
        ':date_of_death' => $archivedApplicant['date_of_death'],
        ':inactive_reason' => $archivedApplicant['inactive_reason'],
        ':date_of_inactive' => $archivedApplicant['date_of_inactive'],
        ':remarks' => $archivedApplicant['remarks'],
        ':date_created' => $archivedApplicant['date_created'],
        ':control_number' => $archivedApplicant['control_number'],
        ':age_last_updated' => $archivedApplicant['age_last_updated'] ?? null
    ]);

    // 4️⃣ Restore related data with explicit column mapping
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $target => $archive) {
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

        // Mark as restored in archive
        $updateStmt = $conn->prepare("
            UPDATE $archive SET restored_date = NOW() 
            WHERE applicant_id = ? AND restored_date IS NULL
        ");
        $updateStmt->execute([$id]);
    }

    // 5️⃣ Mark applicant as restored in archived_applicants
    $updateApplicant = $conn->prepare("
        UPDATE archived_applicants SET restored_date = NOW() 
        WHERE applicant_id = ? AND restored_date IS NULL
    ");
    $updateApplicant->execute([$id]);

    if ($transactionStarted && $conn->inTransaction()) $conn->commit();

    echo json_encode(["success" => true, "message" => "Applicant successfully restored to active list."]);
} catch (Exception $e) {
    if (isset($transactionStarted) && $transactionStarted && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "⚠️ Error restoring record: " . $e->getMessage()]);
}
