<?php
// senior_edit.php
// GET -> fetch applicant (JSON)
// POST -> update applicant (form-data)

include __DIR__ . '/conn.php'; // make sure this sets $conn as PDO
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($conn) || !($conn instanceof PDO)) {
    echo json_encode(["success" => false, "message" => "Database connection error (expected PDO)."]);
    exit;
}

// ------------------------------
// GET — Fetch applicant data
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM applicants WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$applicant) {
            echo json_encode(["success" => false, "message" => "Applicant not found"]);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM addresses WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $conn->prepare("SELECT * FROM economic_status WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $economic = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $conn->prepare("SELECT * FROM health_condition WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $health = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        echo json_encode([
            "success" => true,
            "data" => [
                "applicant" => $applicant,
                "address" => $address,
                "economic" => $economic,
                "health" => $health
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error fetching data: " . $e->getMessage()]);
    }
    exit;
}

// ------------------------------
// POST — Update applicant data
// ------------------------------
// ------------------------------
// POST — Update applicant data
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = $_POST;

    if (empty($p['id'])) {
        echo json_encode(["success" => false, "message" => "Missing applicant ID"]);
        exit;
    }

    $id = intval($p['id']);

    try {
        $conn->beginTransaction();

        // --- Applicants ---
        $sql = "UPDATE applicants SET
            last_name = :last_name,
            first_name = :first_name,
            middle_name = :middle_name,
            gender = :gender,
            age = :age,
            civil_status = :civil_status,
            birth_date = :birth_date,
            citizenship = :citizenship,
            birth_place = :birth_place,
            living_arrangement = :living_arrangement,
            date_modified = NOW()
            WHERE applicant_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':last_name' => $p['lname'] ?? null,
            ':first_name' => $p['fname'] ?? null,
            ':middle_name' => $p['mname'] ?? null,
            ':gender' => $p['gender'] ?? null,
            ':age' => $p['age'] ?? null,
            ':civil_status' => $p['civil_status'] ?? null,
            ':birth_date' => $p['birth_date'] ?? null,
            ':citizenship' => $p['citizenship'] ?? null,
            ':birth_place' => $p['birth_place'] ?? null,
            ':living_arrangement' => $p['living_arrangement'] ?? null,
            ':id' => $id
        ]);

        // --- Address ---
        $stmt = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $sql = "UPDATE addresses SET 
                        house_no=:house_no, 
                        street=:street, 
                        barangay=:barangay, 
                        municipality=:municipality, 
                        province=:province 
                    WHERE applicant_id=:id";
        } else {
            $sql = "INSERT INTO addresses (applicant_id, house_no, street, barangay, municipality, province)
                    VALUES (:id, :house_no, :street, :barangay, :municipality, :province)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':house_no' => $p['house_no'] ?? '',
            ':street' => $p['street'] ?? '',
            ':barangay' => $p['barangay'] ?? '',
            ':municipality' => $p['municipality'] ?? '',
            ':province' => $p['province'] ?? ''
        ]);

        // --- Economic ---
        $stmt = $conn->prepare("SELECT COUNT(*) FROM economic_status WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $sql = "UPDATE economic_status SET
                        is_pensioner=:is_pensioner,
                        pension_amount=:pension_amount,
                        pension_source=:pension_source,
                        pension_source_other=:pension_source_other,
                        has_permanent_income=:has_permanent_income,
                        income_source=:income_source,
                        has_family_support=:has_family_support,
                        support_type=:support_type,
                        support_cash=:support_cash,
                        support_in_kind=:support_in_kind
                    WHERE applicant_id=:id";
        } else {
            $sql = "INSERT INTO economic_status (applicant_id, is_pensioner, pension_amount, pension_source, pension_source_other, has_permanent_income, income_source, has_family_support, support_type, support_cash, support_in_kind)
                    VALUES (:id, :is_pensioner, :pension_amount, :pension_source, :pension_source_other, :has_permanent_income, :income_source, :has_family_support, :support_type, :support_cash, :support_in_kind)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':is_pensioner' => $p['is_pensioner'] ?? null,
            ':pension_amount' => $p['pension_amount'] ?? null,
            ':pension_source' => $p['pension_source'] ?? null,
            ':pension_source_other' => $p['pension_source_other'] ?? null,
            ':has_permanent_income' => $p['has_permanent_income'] ?? null,
            ':income_source' => $p['income_source'] ?? null,
            ':has_family_support' => $p['has_family_support'] ?? null,
            ':support_type' => $p['support_type'] ?? null,
            ':support_cash' => $p['support_cash'] ?? null,
            ':support_in_kind' => $p['support_in_kind'] ?? null
        ]);

        // --- Health ---
        $stmt = $conn->prepare("SELECT COUNT(*) FROM health_condition WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $sql = "UPDATE health_condition SET 
                        has_existing_illness=:has_existing_illness, 
                        illness_details=:illness_details, 
                        hospitalized_last6mos=:hospitalized_last6mos 
                    WHERE applicant_id=:id";
        } else {
            $sql = "INSERT INTO health_condition (applicant_id, has_existing_illness, illness_details, hospitalized_last6mos)
                    VALUES (:id, :has_existing_illness, :illness_details, :hospitalized_last6mos)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':has_existing_illness' => $p['has_existing_illness'] ?? null,
            ':illness_details' => $p['illness_details'] ?? null,
            ':hospitalized_last6mos' => $p['hospitalized_last6mos'] ?? null
        ]);

        // --- Update date_modified whenever related tables change ---
        $stmt = $conn->prepare("UPDATE applicants SET date_modified = NOW() WHERE applicant_id = :id");
        $stmt->execute([':id' => $id]);

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Applicant information updated successfully!"]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["success" => false, "message" => "Error updating data: " . $e->getMessage()]);
    }
    exit;
}


echo json_encode(["success" => false, "message" => "Invalid request"]);
