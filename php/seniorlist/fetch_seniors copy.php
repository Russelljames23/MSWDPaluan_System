<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $mode = $_GET['mode'] ?? 'seniors';

    // --- Fetch barangays only ---
    if ($mode === 'barangays') {
        $stmt = $conn->query("
            SELECT DISTINCT barangay 
            FROM addresses 
            WHERE barangay IS NOT NULL AND barangay != '' 
            ORDER BY barangay ASC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    // --- Pagination setup ---
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $barangays = !empty($_GET['barangays']) ? explode(',', $_GET['barangays']) : [];
    $status = $_GET['status'] ?? 'all';
    $benefit_types = !empty($_GET['benefit_types']) ? explode(',', $_GET['benefit_types']) : [];

    $conditions = [];
    $params = [];
    $join_clauses = [];

    // --- Filter: Active applicants only ---
    $conditions[] = "a.status = 'Active'";

    // --- Search filter ---
    if ($search !== '') {
        $conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.middle_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // --- Barangay filter ---
    if (!empty($barangays)) {
        $in = implode(',', array_fill(0, count($barangays), '?'));
        $conditions[] = "ad.barangay IN ($in)";
        $params = array_merge($params, $barangays);
    }

    // --- Status filter ---
    if ($status !== 'all') {
        $conditions[] = "a.validation = ?";
        $params[] = $status;
    }

    // --- Benefit types filter ---
    if (!empty($benefit_types)) {
        // Join with benefits_distribution table (this is your linking table)
        $join_clauses[] = "INNER JOIN benefits_distribution bd ON bd.applicant_id = a.applicant_id";

        // Add condition for benefit types
        $benefit_in = implode(',', array_fill(0, count($benefit_types), '?'));
        $conditions[] = "bd.benefit_id IN ($benefit_in)";

        // Add benefit type parameters
        foreach ($benefit_types as $type) {
            $params[] = $type;
        }

        // Add DISTINCT to avoid duplicates when an applicant has multiple benefits
        $distinct_keyword = "DISTINCT";
    } else {
        $distinct_keyword = "";
    }

    $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $joins = implode(' ', $join_clauses);

    // --- Get total count ---
    $countQuery = "
        SELECT COUNT($distinct_keyword a.applicant_id) 
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id
        $joins
        $where
    ";

    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // --- Fetch paginated data ---
    $query = "
        SELECT 
            (@rownum := @rownum + 1) AS rownum,
            a.applicant_id,
            CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) AS full_name,
            a.birth_date,
            a.age,
            a.gender,
            a.civil_status,
            ad.barangay,
            a.date_created,
            a.date_modified,
            a.validation,
            a.status,
            a.control_number
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id
        $joins,
        (SELECT @rownum := ?) r
        $where
        GROUP BY a.applicant_id
        ORDER BY a.date_created DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);

    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 2, $val);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Pagination metadata ---
    $start = $offset + 1;
    $end = min($offset + $limit, $total);
    $totalPages = ceil($total / $limit);

    echo json_encode([
        "total_records" => $total,
        "total_pages" => $totalPages,
        "start" => $total ? $start : 0,
        "end" => $total ? $end : 0,
        "seniors" => $rows
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("fetch_seniors.php error: " . $e->getMessage());
    echo json_encode([
        "error" => "Database error occurred",
        "details" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
}
