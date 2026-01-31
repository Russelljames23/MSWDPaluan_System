<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Start output buffering
ob_start();

try {
    // Include database connection - use relative path
    require_once __DIR__ . '/db.php';

    // Check if connection was established
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not established");
    }

    // Log for debugging
    $debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'true';

    if ($debug_mode) {
        error_log("fetch_seniors.php accessed with mode: " . ($_GET['mode'] ?? 'none'));
        error_log("GET parameters: " . print_r($_GET, true));
    }

    $mode = $_GET['mode'] ?? 'seniors';

    // --- Fetch barangays only ---
    if ($mode === 'barangays') {
        if ($debug_mode) error_log("Fetching barangays");

        $stmt = $conn->query("
            SELECT DISTINCT barangay 
            FROM addresses 
            WHERE barangay IS NOT NULL AND barangay != '' 
            ORDER BY barangay ASC
        ");

        if (!$stmt) {
            throw new Exception("Failed to fetch barangays: " . ($conn->errorInfo()[2] ?? 'Unknown error'));
        }

        $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($debug_mode) error_log("Found " . count($barangays) . " barangays");

        echo json_encode($barangays);
        exit;
    }

    // --- For seniors list ---
    if ($debug_mode) error_log("Fetching seniors list");

    // --- Pagination setup ---
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $barangays = !empty($_GET['barangays']) ? explode(',', $_GET['barangays']) : [];
    $status = $_GET['status'] ?? 'all';

    // Filter parameters
    $filter_type = $_GET['filter_type'] ?? '';
    $validation_status = $_GET['validation_status'] ?? '';
    $age_group = $_GET['age_group'] ?? '';
    $min_age = $_GET['min_age'] ?? 0;
    $max_age = $_GET['max_age'] ?? 0;
    $milestone_age = $_GET['milestone_age'] ?? null;

    $conditions = [];
    $params = [];

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
        $placeholders = implode(',', array_fill(0, count($barangays), '?'));
        $conditions[] = "ad.barangay IN ($placeholders)";
        $params = array_merge($params, $barangays);
    }

    // --- Status filter ---
    if ($status !== 'all') {
        $conditions[] = "a.validation = ?";
        $params[] = $status;
    }

    // --- Validation status filter (from dashboard) ---
    if ($filter_type === 'validation' && !empty($validation_status)) {
        $conditions[] = "a.validation = ?";
        $params[] = $validation_status;

        // Also set the status filter for the UI
        $status = $validation_status; // This will override the dropdown filter
    }

    // --- Age group filter ---
    if ($filter_type === 'age' && !empty($age_group)) {
        if ($age_group === '90+') {
            $conditions[] = "a.current_age >= ?";
            $params[] = $min_age;
        } else if ($age_group === 'Under 60') {
            $conditions[] = "a.current_age < ?";
            $params[] = $min_age;
        } else if ($min_age > 0 && $max_age > 0) {
            $conditions[] = "a.current_age BETWEEN ? AND ?";
            $params[] = $min_age;
            $params[] = $max_age;
        }
    }

    // --- Milestone filter ---
    if ($filter_type === 'milestone' && !empty($milestone_age)) {
        $conditions[] = "YEAR(CURDATE()) - YEAR(a.birth_date) + 1 = ?";
        $params[] = $milestone_age;
    }

    $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // --- Get total count ---
    $countQuery = "
        SELECT COUNT(DISTINCT a.applicant_id) 
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id
        $where
    ";

    if ($debug_mode) {
        error_log("Count query: " . $countQuery);
        error_log("Count params: " . print_r($params, true));
    }

    $countStmt = $conn->prepare($countQuery);
    if (!$countStmt) {
        throw new Exception("Failed to prepare count query");
    }

    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    if ($debug_mode) error_log("Total records found: " . $total);

    // --- Fetch paginated data ---
    // Simplified query without @rownum variable
    $query = "
        SELECT 
            ROW_NUMBER() OVER (ORDER BY a.date_created DESC) AS rownum,
            a.applicant_id,
            CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) AS full_name,
            DATE_FORMAT(a.birth_date, '%Y-%m-%d') as birth_date,
            a.current_age AS age,
            a.gender,
            a.civil_status,
            ad.barangay,
            DATE_FORMAT(a.date_created, '%Y-%m-%d') as date_created,
            DATE_FORMAT(a.date_modified, '%Y-%m-%d') as date_modified,
            a.validation,
            a.status,
            a.control_number
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id
        $where
        ORDER BY a.date_created DESC
        LIMIT $limit OFFSET $offset
    ";

    if ($debug_mode) error_log("Main query: " . $query);

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare main query");
    }

    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($debug_mode) error_log("Rows fetched: " . count($rows));

    // Manually calculate rownum if ROW_NUMBER() doesn't work
    if (empty($rows[0]['rownum'])) {
        $startNum = $offset + 1;
        foreach ($rows as $index => &$row) {
            $row['rownum'] = $startNum + $index;
        }
    }

    // --- Pagination metadata ---
    $start = $total ? ($offset + 1) : 0;
    $end = min($offset + $limit, $total);
    $totalPages = ceil($total / $limit);

    $response = [
        "success" => true,
        "total_records" => $total,
        "total_pages" => $totalPages,
        "start" => $start,
        "end" => $end,
        "seniors" => $rows
    ];

    if ($debug_mode) {
        $response['debug'] = [
            "query" => $query,
            "params" => $params,
            "row_count" => count($rows)
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    $errorOutput = ob_get_clean();

    error_log("fetch_seniors.php error: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());

    echo json_encode([
        "success" => false,
        "error" => "Database error occurred",
        "message" => $e->getMessage(),
        "output_buffer" => $errorOutput
    ]);
}

ob_end_flush();
