<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    if ($id <= 0) throw new Exception("Missing applicant ID.");

    if (!$conn->inTransaction()) $conn->beginTransaction();

    // 1️⃣ Fetch applicant
    $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$applicant) throw new Exception("Applicant not found.");

    // 2️⃣ Determine archived status
    $archivedStatus = match ($applicant['status']) {
        'Deceased' => 'Deceased',
        'Inactive' => 'Inactive',
        default => 'Archived',
    };

    // 3️⃣ Archive applicant
    $conn->exec("
        CREATE TABLE IF NOT EXISTS archived_applicants LIKE applicants;
    ");
    $conn->exec("
        ALTER TABLE archived_applicants 
        ADD COLUMN IF NOT EXISTS archived_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS restored_date DATETIME NULL;
    ");

    $insertApplicant = $conn->prepare("
        INSERT INTO archived_applicants (
            applicant_id, last_name, first_name, middle_name, gender, age, civil_status,
            birth_date, citizenship, birth_place, living_arrangement, pension_status,
            status, date_of_death, inactive_reason, date_of_inactive, remarks, date_created, date_modified, archived_date
        )
        VALUES (
            :applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :civil_status,
            :birth_date, :citizenship, :birth_place, :living_arrangement, :pension_status,
            :status, :date_of_death, :inactive_reason, :date_of_inactive, :remarks, :date_created, :date_modified, NOW()
        )
    ");
    $insertApplicant->execute([
        ':applicant_id' => $applicant['applicant_id'],
        ':last_name' => $applicant['last_name'],
        ':first_name' => $applicant['first_name'],
        ':middle_name' => $applicant['middle_name'],
        ':gender' => $applicant['gender'],
        ':age' => $applicant['age'],
        ':civil_status' => $applicant['civil_status'],
        ':birth_date' => $applicant['birth_date'],
        ':citizenship' => $applicant['citizenship'],
        ':birth_place' => $applicant['birth_place'],
        ':living_arrangement' => $applicant['living_arrangement'],
        ':pension_status' => $applicant['pension_status'],
        ':status' => $archivedStatus,
        ':date_of_death' => $applicant['date_of_death'],
        ':inactive_reason' => $applicant['inactive_reason'],
        ':date_of_inactive' => $applicant['date_of_inactive'],
        ':remarks' => $applicant['remarks'],
        ':date_created' => $applicant['date_created'],
        ':date_modified' => $applicant['date_modified']
    ]);

    // 4️⃣ Archive related records
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $source => $archive) {
        $conn->exec("CREATE TABLE IF NOT EXISTS $archive LIKE $source;");
        $conn->exec("
            ALTER TABLE $archive 
            ADD COLUMN IF NOT EXISTS archived_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS restored_date DATETIME NULL;
        ");

        $columns = $conn->query("SHOW COLUMNS FROM $source")->fetchAll(PDO::FETCH_COLUMN);
        $columnList = implode(',', $columns);

        // Insert with archived_date timestamp
        $stmt = $conn->prepare("
            INSERT INTO $archive ($columnList, archived_date)
            SELECT $columnList, NOW() FROM $source WHERE applicant_id = ?
        ");
        $stmt->execute([$id]);
    }

    // 5️⃣ Delete originals (maintaining referential order)
    foreach (array_reverse(array_keys($tables)) as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
        $stmt->execute([$id]);
    }

    $stmt = $conn->prepare("DELETE FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);

    if ($conn->inTransaction()) $conn->commit();

    echo json_encode(["success" => true, "message" => "Senior archived successfully."]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "⚠️ Error: " . $e->getMessage()]);
}
