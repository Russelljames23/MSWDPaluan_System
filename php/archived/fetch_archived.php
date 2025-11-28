<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once("../db.php");

try {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Ensure archived_applicants table exists with proper structure
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

    // Fetch all archived applicants
    $query = "
        SELECT 
            applicant_id,
            last_name,
            first_name,
            middle_name,
            gender,
            age,
            current_age,
            civil_status,
            birth_date,
            date_of_death,
            validation,
            status,
            date_created,
            date_modified,
            archived_date,
            restored_date
        FROM archived_applicants
        WHERE restored_date IS NULL
        ORDER BY archived_date DESC, date_modified DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $archived = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $full_name = trim($row['last_name'] . ', ' . $row['first_name'] .
            ($row['middle_name'] ? ' ' . $row['middle_name'] : ''));

        $archived[] = [
            "applicant_id"   => $row["applicant_id"] ?? 0,
            "full_name"      => $full_name ?: "N/A",
            "birth_date"     => !empty($row["birth_date"]) && $row["birth_date"] != '0000-00-00' ?
                date("Y-m-d", strtotime($row["birth_date"])) : "",
            "age"            => $row["age"] ?? "",
            "current_age"    => $row["current_age"] ?? "",
            "gender"         => $row["gender"] ?? "",
            "civil_status"   => $row["civil_status"] ?? "",
            "archived_date"  => !empty($row["archived_date"]) ?
                date("Y-m-d H:i:s", strtotime($row["archived_date"])) : "",
            "validation"     => $row["validation"] ?? "",
            "date_of_death"  => !empty($row["date_of_death"]) && $row["date_of_death"] != '0000-00-00' ?
                date("Y-m-d", strtotime($row["date_of_death"])) : "",
            "status"         => $row["status"] ?? "Archived"
        ];
    }

    echo json_encode([
        "success"  => true,
        "archived" => $archived,
        "count"    => count($archived)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "archived" => [],
        "message" => "⚠️ Error fetching archived records: " . $e->getMessage()
    ]);
}
