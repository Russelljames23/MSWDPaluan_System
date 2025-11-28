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

    // 4️⃣ Archive applicant - explicitly list columns to avoid column count mismatch
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
        ':applicant_id' => $applicant['applicant_id'],
        ':last_name' => $applicant['last_name'],
        ':first_name' => $applicant['first_name'],
        ':middle_name' => $applicant['middle_name'],
        ':gender' => $applicant['gender'],
        ':age' => $applicant['age'],
        ':current_age' => $applicant['current_age'],
        ':civil_status' => $applicant['civil_status'],
        ':birth_date' => $applicant['birth_date'],
        ':citizenship' => $applicant['citizenship'],
        ':birth_place' => $applicant['birth_place'],
        ':living_arrangement' => $applicant['living_arrangement'],
        ':validation' => $applicant['validation'],
        ':status' => $applicant['status'],
        ':date_of_death' => $applicant['date_of_death'],
        ':inactive_reason' => $applicant['inactive_reason'],
        ':date_of_inactive' => $applicant['date_of_inactive'],
        ':remarks' => $applicant['remarks'],
        ':date_created' => $applicant['date_created'],
        ':date_modified' => $applicant['date_modified'],
        ':control_number' => $applicant['control_number'],
        ':age_last_updated' => $applicant['age_last_updated'] ?? null
    ]);

    // 5️⃣ Archive related records with explicit column listing
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
        $stmt = $conn->prepare("
            INSERT INTO $archive ($columnList, archived_date)
            SELECT $columnList, NOW() FROM $source WHERE applicant_id = ?
        ");
        $stmt->execute([$id]);
    }

    // 6️⃣ Delete from original tables (in reverse order to maintain referential integrity)
    $deleteOrder = [
        "senior_illness",
        "health_condition",
        "economic_status",
        "addresses",
        "applicants"
    ];

    foreach ($deleteOrder as $table) {
        $deleteStmt = $conn->prepare("DELETE FROM $table WHERE applicant_id = ?");
        $deleteResult = $deleteStmt->execute([$id]);

        if (!$deleteResult) {
            throw new Exception("Failed to delete from $table for applicant $id");
        }
    }

    if ($conn->inTransaction()) $conn->commit();

    echo json_encode(["success" => true, "message" => "Senior archived successfully."]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "⚠️ Error: " . $e->getMessage()]);
}
