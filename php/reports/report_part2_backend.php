<?php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class Part2ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getForValidationSeniors($year = null, $month = null, $page = 1, $limit = 10)
    {
        try {
            // Calculate offset
            $offset = ($page - 1) * $limit;

            // First, get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM applicants a 
                        WHERE a.validation = 'For Validation'
                        AND a.status = 'Active'";

            $countParams = [];
            if ($year !== null) {
                $countSql .= " AND YEAR(a.date_created) = ?";
                $countParams[] = $year;
            }
            if ($month !== null) {
                $countSql .= " AND MONTH(a.date_created) = ?";
                $countParams[] = $month;
            }

            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords = $countResult['total'] ?? 0;
            $totalPages = ceil($totalRecords / $limit);

            // Now get the paginated data - FIXED SQL SYNTAX
            $sql = "SELECT 
                    a.applicant_id,
                    a.last_name,
                    a.first_name,
                    a.middle_name,
                    a.suffix,
                    a.gender,
                    a.birth_date,
                    a.current_age,
                    ad.barangay,
                    a.date_created
                FROM applicants a 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.validation = 'For Validation'
                AND a.status = 'Active'";

            $params = [];

            if ($year !== null) {
                $sql .= " AND YEAR(a.date_created) = ?";
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND MONTH(a.date_created) = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY a.date_created DESC";

            // FIX: Use integers directly in LIMIT clause for MariaDB
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);

            $stmt = $this->conn->prepare($sql);
            
            // Execute with only the WHERE clause parameters (not LIMIT/OFFSET)
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formattedResults = [];
            foreach ($results as $index => $row) {
                $middleInitial = !empty($row['middle_name']) ? substr($row['middle_name'], 0, 1) . '.' : '';
                $suffix = !empty($row['suffix']) ? ' ' . $row['suffix'] : '';
                $fullName = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middleInitial . $suffix);

                $dateOfBirth = 'N/A';
                if (!empty($row['birth_date']) && $row['birth_date'] != '0000-00-00') {
                    $dateOfBirth = date('m-d-Y', strtotime($row['birth_date']));
                }

                $formattedResults[] = [
                    'id' => $row['applicant_id'],
                    'name' => $fullName,
                    'date_of_birth' => $dateOfBirth,
                    'age' => $row['current_age'] ?? 'N/A',
                    'sex' => !empty($row['gender']) ? substr(strtoupper($row['gender']), 0, 1) : 'N/A',
                    'barangay' => $row['barangay'] ?? 'Not Specified',
                    'date_created' => $row['date_created']
                ];
            }

            return [
                'success' => true,
                'data' => $formattedResults,
                'pagination' => [
                    'total_records' => (int)$totalRecords,
                    'total_pages' => (int)$totalPages,
                    'current_page' => (int)$page,
                    'limit' => (int)$limit,
                    'offset' => (int)$offset
                ],
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ],
                'debug' => [
                    'sql' => $sql,
                    'params' => $params,
                    'total_records' => $totalRecords
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getForValidationSeniors: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }
}

try {
    $api = new Part2ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        $result = $api->getForValidationSeniors($year, $month, $page, $limit);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}