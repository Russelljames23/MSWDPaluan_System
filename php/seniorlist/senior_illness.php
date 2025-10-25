<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include './conn.php'; // ✅ PDO connection

header("Content-Type: application/json; charset=UTF-8");

// ✅ CORS Handling
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Helper function for JSON responses
function jsonExit($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ✅ Safety check for DB connection
if (!isset($conn) || !($conn instanceof PDO)) {
    jsonExit(['success' => false, 'error' => "Database connection not available. Check conn.php"], 500);
}

$method = $_SERVER['REQUEST_METHOD'];

// =========================================================
// 🧾 GET — Fetch senior illness records + health condition + application date
// =========================================================
if ($method === 'GET') {
    $applicant_id = isset($_GET['applicant_id']) ? (int)$_GET['applicant_id'] : 0;
    if ($applicant_id <= 0) {
        jsonExit(['success' => false, 'error' => 'Invalid or missing applicant_id'], 400);
    }

    try {
        // 1️⃣ Fetch illness history
        $stmt = $conn->prepare("
            SELECT illness_id, illness_name, illness_date, created_at, updated_at
            FROM senior_illness
            WHERE applicant_id = :applicant_id
            ORDER BY illness_date DESC, created_at DESC
        ");
        $stmt->execute(['applicant_id' => $applicant_id]);
        $illnesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2️⃣ Fetch health condition
        $stmt2 = $conn->prepare("
            SELECT has_existing_illness, illness_details, hospitalized_last6mos
            FROM health_condition
            WHERE applicant_id = :applicant_id
            LIMIT 1
        ");
        $stmt2->execute(['applicant_id' => $applicant_id]);
        $health_condition = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [
            'has_existing_illness' => null,
            'illness_details' => null,
            'hospitalized_last6mos' => null
        ];

        // 3️⃣ Fetch applicant's date (adjust to your real column name)
        $stmt3 = $conn->prepare("
            SELECT date_created
            FROM applicants
            WHERE applicant_id = :applicant_id
            LIMIT 1
        ");
        $stmt3->execute(['applicant_id' => $applicant_id]);
        $appRow = $stmt3->fetch(PDO::FETCH_ASSOC);
        $application_date = $appRow['date_created'] ?? null;

        // ✅ 4️⃣ Return combined data
        jsonExit([
            'success' => true,
            'illnesses' => $illnesses,
            'health_condition' => $health_condition,
            'application_date' => $application_date
        ]);
    } catch (PDOException $e) {
        jsonExit(['success' => false, 'error' => $e->getMessage()], 500);
    }

    // =========================================================
    // 💾 POST — Add new illness record
    // =========================================================
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) jsonExit(['success' => false, 'error' => 'Invalid JSON payload'], 400);

    $applicant_id = (int)($data['applicant_id'] ?? 0);
    $illness_name = trim($data['illness_name'] ?? '');
    $illness_date = trim($data['illness_date'] ?? '');

    if ($applicant_id <= 0 || !$illness_name || !$illness_date) {
        jsonExit(['success' => false, 'error' => 'Missing required fields'], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $illness_date)) {
        jsonExit(['success' => false, 'error' => 'illness_date must be YYYY-MM-DD'], 400);
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO senior_illness (applicant_id, illness_name, illness_date, created_at, updated_at)
            VALUES (:applicant_id, :illness_name, :illness_date, NOW(), NOW())
        ");
        $stmt->execute([
            'applicant_id' => $applicant_id,
            'illness_name' => $illness_name,
            'illness_date' => $illness_date
        ]);

        jsonExit([
            'success' => true,
            'message' => 'Illness added successfully',
            'illness_id' => $conn->lastInsertId()
        ], 201);
    } catch (PDOException $e) {
        jsonExit(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    jsonExit(['success' => false, 'error' => 'Method not allowed. Use GET or POST.'], 405);
}
