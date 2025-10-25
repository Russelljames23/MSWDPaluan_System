<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $mode = $_GET['mode'] ?? 'seniors';

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

    // Pagination params
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $barangays = !empty($_GET['barangays']) ? explode(',', $_GET['barangays']) : [];

    $conditions = [];
    $params = [];

    if ($search) {
        $conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.middle_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($barangays)) {
        $in = implode(',', array_fill(0, count($barangays), '?'));
        $conditions[] = "ad.barangay IN ($in)";
        $params = array_merge($params, $barangays);
    }

    $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Get total count
    $countStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id
        $where
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Fetch paginated data
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
            a.status
        FROM applicants a
        LEFT JOIN addresses ad ON ad.applicant_id = a.applicant_id,
        (SELECT @rownum := ?) r
        $where
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
    echo json_encode(["error" => $e->getMessage()]);
}
