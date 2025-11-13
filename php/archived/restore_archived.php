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

    //  Check if applicant already active
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "⚠️ Applicant already exists in Active List."]);
        exit;
    }

    //  Fetch archived applicant
    $stmt = $conn->prepare("SELECT * FROM archived_applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $archivedApplicant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$archivedApplicant) throw new Exception("Archived applicant not found.");

    //  Restore applicant
    $conn->exec("CREATE TABLE IF NOT EXISTS applicants LIKE archived_applicants;");

    $insert = $conn->prepare("
        INSERT INTO applicants (
            applicant_id, last_name, first_name, middle_name, gender, age, civil_status,
            birth_date, citizenship, birth_place, living_arrangement, pension_status,
            status, date_of_death, inactive_reason, date_of_inactive, remarks, date_created, date_modified
        )
        VALUES (
            :applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :civil_status,
            :birth_date, :citizenship, :birth_place, :living_arrangement, :pension_status,
            :status, :date_of_death, :inactive_reason, :date_of_inactive, :remarks, :date_created, NOW()
        )
    ");
    $insert->execute([
        ':applicant_id' => $archivedApplicant['applicant_id'],
        ':last_name' => $archivedApplicant['last_name'],
        ':first_name' => $archivedApplicant['first_name'],
        ':middle_name' => $archivedApplicant['middle_name'],
        ':gender' => $archivedApplicant['gender'],
        ':age' => $archivedApplicant['age'],
        ':civil_status' => $archivedApplicant['civil_status'],
        ':birth_date' => $archivedApplicant['birth_date'],
        ':citizenship' => $archivedApplicant['citizenship'],
        ':birth_place' => $archivedApplicant['birth_place'],
        ':living_arrangement' => $archivedApplicant['living_arrangement'],
        ':pension_status' => $archivedApplicant['pension_status'],
        ':status' => $archivedApplicant['status'] ?? 'Active',
        ':date_of_death' => $archivedApplicant['date_of_death'],
        ':inactive_reason' => $archivedApplicant['inactive_reason'],
        ':date_of_inactive' => $archivedApplicant['date_of_inactive'],
        ':remarks' => $archivedApplicant['remarks'],
        ':date_created' => $archivedApplicant['date_created']
    ]);

    //  Restore related data
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $target => $archive) {
        $conn->exec("CREATE TABLE IF NOT EXISTS $target LIKE $archive;");
        $conn->exec("ALTER TABLE $archive ADD COLUMN IF NOT EXISTS restored_date DATETIME NULL;");

        $columns = $conn->query("SHOW COLUMNS FROM $target")->fetchAll(PDO::FETCH_COLUMN);
        $columnList = implode(',', $columns);

        $copy = $conn->prepare("
            INSERT INTO $target ($columnList)
            SELECT $columnList FROM $archive WHERE applicant_id = ?
        ");
        $copy->execute([$id]);

        // Mark as restored instead of deleting right away
        $conn->prepare("
            UPDATE $archive SET restored_date = NOW() WHERE applicant_id = ?
        ")->execute([$id]);

        // Optional: delete after marking
        $conn->prepare("DELETE FROM $archive WHERE applicant_id = ?")->execute([$id]);
    }

    //  Delete applicant from archive after restore
    $conn->prepare("DELETE FROM archived_applicants WHERE applicant_id = ?")->execute([$id]);

    if ($transactionStarted && $conn->inTransaction()) $conn->commit();

    echo json_encode(["success" => true, "message" => "Applicant successfully restored."]);
} catch (Exception $e) {
    if (isset($transactionStarted) && $transactionStarted && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "⚠️ Error restoring record: " . $e->getMessage()]);
}
