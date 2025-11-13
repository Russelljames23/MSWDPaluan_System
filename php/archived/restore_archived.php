<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) throw new Exception("Missing applicant ID.");

    //  Start transaction only if not already active
    $transactionStarted = false;
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
        $transactionStarted = true;
    }

    //  Check if applicant exists in active table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "⚠️ Applicant already exists in Active List."
        ]);
        exit;
    }

    //  Fetch archived applicant
    $stmt = $conn->prepare("SELECT * FROM archived_applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $archivedApplicant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$archivedApplicant) throw new Exception("Archived applicant not found.");

    //  Ensure main table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS applicants LIKE archived_applicants");

    //  Insert applicant into main table with status from archive
    $insert = $conn->prepare("
        INSERT INTO applicants 
        (applicant_id, last_name, first_name, middle_name, gender, age, civil_status, birth_date, 
         pension_status, status, date_of_death, inactive_reason, date_of_inactive, date_created, date_modified)
        VALUES 
        (:applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :civil_status, :birth_date,
         :pension_status, :status, :date_of_death, :inactive_reason, :date_of_inactive, :date_created, :date_modified)
    ");
    $insert->execute([
        ':applicant_id' => $archivedApplicant['applicant_id'],
        ':last_name'    => $archivedApplicant['last_name'],
        ':first_name'   => $archivedApplicant['first_name'],
        ':middle_name'  => $archivedApplicant['middle_name'],
        ':gender'       => $archivedApplicant['gender'],
        ':age'          => $archivedApplicant['age'],
        ':civil_status' => $archivedApplicant['civil_status'],
        ':birth_date'   => $archivedApplicant['birth_date'],
        ':pension_status' => $archivedApplicant['pension_status'],
        ':status'       => $archivedApplicant['status'] ?? 'Active', //  Restore original status
        ':date_of_death' => $archivedApplicant['date_of_death'],
        ':inactive_reason' => $archivedApplicant['inactive_reason'],
        ':date_of_inactive' => $archivedApplicant['date_of_inactive'],
        ':date_created' => $archivedApplicant['date_created'],
        ':date_modified' => date('Y-m-d H:i:s')
    ]);

    //  Handle related archived tables
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $target => $archive) {
        $conn->exec("CREATE TABLE IF NOT EXISTS $target LIKE $archive");

        $columns = $conn->query("SHOW COLUMNS FROM $target")->fetchAll(PDO::FETCH_COLUMN);
        $columnList = implode(',', $columns);

        // Copy data from archive to main
        $copy = $conn->prepare("INSERT INTO $target ($columnList) SELECT $columnList FROM $archive WHERE applicant_id = ?");
        $copy->execute([$id]);

        // Delete from archive
        $conn->prepare("DELETE FROM $archive WHERE applicant_id = ?")->execute([$id]);
    }

    //  Delete main archived applicant
    $conn->prepare("DELETE FROM archived_applicants WHERE applicant_id = ?")->execute([$id]);

    //  Commit only if this script started the transaction
    if ($transactionStarted && $conn->inTransaction()) {
        $conn->commit();
    }

    echo json_encode([
        "success" => true,
        "message" => " Applicant successfully restored."
    ]);
} catch (Exception $e) {
    //  Rollback only if this script started the transaction
    if (isset($transactionStarted) && $transactionStarted && $conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "⚠️ Error restoring record: " . $e->getMessage()
    ]);
}
