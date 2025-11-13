<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include '../db.php';

try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $filter = $_GET['filter'] ?? '';

    $conditions = ["a.status = 'Inactive'"];
    $params = [];

    if ($search) {
        $conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.middle_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $orderBy = "ORDER BY a.date_of_inactive DESC";
    if ($filter === 'az') $orderBy = "ORDER BY a.last_name ASC";
    elseif ($filter === 'recent') $orderBy = "ORDER BY a.date_of_inactive DESC";

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Count total
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM applicants a $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Data fetch
    $query = "
        SELECT 
            (@rownum := @rownum + 1) AS rownum,
            a.applicant_id,
            CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) AS full_name,
            a.birth_date,
            a.age,
            a.gender,
            a.civil_status,
            a.date_of_inactive,
            a.inactive_reason,
            a.pension_status
        FROM applicants a, (SELECT @rownum := ?) r
        $where
        $orderBy
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
        "deceased" => $rows
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
