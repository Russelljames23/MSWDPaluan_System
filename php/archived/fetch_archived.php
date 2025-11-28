    <?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");

    include_once("../db.php");

    try {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        // Ensure archived_applicants table exists
        $conn->exec("CREATE TABLE IF NOT EXISTS archived_applicants LIKE applicants");

        // Ensure archived_date column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM archived_applicants LIKE 'archived_date'");
        if ($checkColumn->rowCount() === 0) {
            $conn->exec("ALTER TABLE archived_applicants ADD COLUMN archived_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        }

        // Fetch all archived applicants (Archived, Inactive, Deceased, or empty)
        $query = "
            SELECT 
                applicant_id,
                last_name,
                first_name,
                middle_name,
                gender,
                age,
                civil_status,
                birth_date,
                date_of_death,
                validation,
                status,
                date_created,
                date_modified,
                archived_date
            FROM archived_applicants
            WHERE (status IS NULL OR status IN ('Archived','Inactive','Deceased',''))
            ORDER BY archived_date DESC, date_modified DESC
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $archived = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $full_name = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? ''));
            $archived[] = [
                "applicant_id"   => $row["applicant_id"] ?? 0,
                "full_name"      => $full_name ?: "N/A",
                "birth_date"     => !empty($row["birth_date"]) ? date("Y-m-d", strtotime($row["birth_date"])) : "",
                "age"            => $row["age"] ?? "",
                "gender"         => $row["gender"] ?? "",
                "civil_status"   => $row["civil_status"] ?? "",
                "archived_date"  => !empty($row["archived_date"]) ? date("Y-m-d", strtotime($row["archived_date"])) : "",
                "validation" => $row["validation"] ?? "",
                "date_of_death"  => !empty($row["date_of_death"]) ? date("Y-m-d", strtotime($row["date_of_death"])) : "",
                "status"         => $row["status"] ?? ""
            ];
        }

        // Always return consistent JSON structure
        echo json_encode([
            "success"  => true,
            "archived" => $archived
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "archived" => [],
            "message" => "⚠️ Error fetching archived records: " . $e->getMessage()
        ]);
    }
