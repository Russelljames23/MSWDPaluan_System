<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if ($id <= 0) {
        throw new Exception("Missing applicant ID.");
    }

    // ✅ Start a transaction safely
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
    }

    // 1️⃣ Fetch the deceased applicant
    $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = ? AND status = 'Deceased'");
    $stmt->execute([$id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        throw new Exception("Applicant not found or not marked as deceased.");
    }

    // 2️⃣ Insert into archived_applicants
    $conn->exec("CREATE TABLE IF NOT EXISTS archived_applicants LIKE applicants");

    $insertApplicant = $conn->prepare("
        INSERT INTO archived_applicants 
        (applicant_id, last_name, first_name, middle_name, gender, age, civil_status,
         birth_date, citizenship, birth_place, living_arrangement, pension_status,
         status, date_of_death, inactive_reason, remarks, date_created, date_modified)
        VALUES
        (:applicant_id, :last_name, :first_name, :middle_name, :gender, :age, :civil_status,
         :birth_date, :citizenship, :birth_place, :living_arrangement, :pension_status,
         :status, :date_of_death, :inactive_reason, :remarks, :date_created, :date_modified)
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
        ':status' => 'Archived',
        ':date_of_death' => $applicant['date_of_death'],
        ':inactive_reason' => $applicant['inactive_reason'],
        ':remarks' => $applicant['remarks'],
        ':date_created' => $applicant['date_created'],
        ':date_modified' => $applicant['date_modified']
    ]);

    // 3️⃣ Archive related records
    $tables = [
        "addresses" => "archived_addresses",
        "economic_status" => "archived_economic_status",
        "health_condition" => "archived_health_condition",
        "senior_illness" => "archived_senior_illness"
    ];

    foreach ($tables as $source => $archive) {
        $conn->exec("CREATE TABLE IF NOT EXISTS $archive LIKE $source");
        $stmt = $conn->prepare("INSERT INTO $archive SELECT * FROM $source WHERE applicant_id = ?");
        $stmt->execute([$id]);
    }

    // 4️⃣ Delete from original tables (in reverse dependency order)
    foreach (array_keys(array_reverse($tables)) as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
        $stmt->execute([$id]);
    }

    $stmt = $conn->prepare("DELETE FROM applicants WHERE applicant_id = ?");
    $stmt->execute([$id]);

    // 5️⃣ Commit safely
    if ($conn->inTransaction()) {
        $conn->commit();
    }

    // ✅ Ensure commit is visible before frontend refreshes
    usleep(200000); // wait 0.2s

    echo json_encode([
        "success" => true,
        "message" => "✅ Applicant and all related data archived successfully."
    ]);
    exit;
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "⚠️ Error: " . $e->getMessage()
    ]);
}
