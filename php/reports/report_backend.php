<?php
require_once "../db.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

class ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    // PART I: Number of Registered Senior Citizens (Existing)
    public function getSeniorCounts($year = null, $month = null)
    {
        // Debug logging
        error_log("ReportAPI::getSeniorCounts called with year: " . ($year ?? 'NULL') . ", month: " . ($month ?? 'NULL'));

        // Get all barangays
        $barangays = $this->getAllBarangays();

        error_log("Found " . count($barangays) . " barangays");

        $data = [];
        $totals = ['male' => 0, 'female' => 0, 'total' => 0];

        foreach ($barangays as $barangay) {
            $male_count = $this->getCountByBarangayAndGender($barangay, 'Male', $year, $month);
            $female_count = $this->getCountByBarangayAndGender($barangay, 'Female', $year, $month);
            $barangay_total = $male_count + $female_count;

            $data[] = [
                'barangay' => $barangay,
                'male' => $male_count,
                'female' => $female_count,
                'total' => $barangay_total
            ];

            $totals['male'] += $male_count;
            $totals['female'] += $female_count;
            $totals['total'] += $barangay_total;

            error_log("Barangay {$barangay}: Male={$male_count}, Female={$female_count}, Total={$barangay_total}");
        }

        error_log("Grand totals: Male={$totals['male']}, Female={$totals['female']}, Total={$totals['total']}");

        return [
            'success' => true,
            'data' => $data,
            'totals' => $totals,
            'filters' => [
                'year' => $year,
                'month' => $month
            ],
            'debug' => [
                'barangay_count' => count($barangays),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }

    // PART II: Number of Newly Registered Senior Citizens (NEW)
    public function getNewlyRegisteredSeniors($year = null, $month = null, $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT 
                    a.applicant_id,
                    a.first_name,
                    a.middle_name,
                    a.last_name,
                    a.suffix,
                    a.gender,
                    a.date_of_birth,
                    YEAR(CURDATE()) - YEAR(a.date_of_birth) - (RIGHT(CURDATE(), 5) < RIGHT(a.date_of_birth, 5)) as age,
                    ad.barangay,
                    ard.date_of_registration,
                    a.contact_number,
                    ad.purok
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE 1=1";

            $params = [];

            // Add date filtering if year and/or month are provided
            if ($year !== null) {
                $sql .= " AND YEAR(ard.date_of_registration) = ?";
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND MONTH(ard.date_of_registration) = ?";
                $params[] = $month;
            }

            // Order by registration date (newest first)
            $sql .= " ORDER BY ard.date_of_registration DESC, a.last_name, a.first_name";

            // Add limit and offset for pagination
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total 
                        FROM applicants a 
                        INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                        INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                        WHERE 1=1";

            $countParams = [];
            if ($year !== null) {
                $countSql .= " AND YEAR(ard.date_of_registration) = ?";
                $countParams[] = $year;
            }
            if ($month !== null) {
                $countSql .= " AND MONTH(ard.date_of_registration) = ?";
                $countParams[] = $month;
            }

            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);

            // Format the data
            $formattedResults = [];
            foreach ($results as $index => $row) {
                $formattedResults[] = [
                    'number' => $offset + $index + 1,
                    'name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ? substr($row['middle_name'], 0, 1) . '.' : '') . ($row['suffix'] ? ' ' . $row['suffix'] : '')),
                    'date_of_birth' => date('m-d-Y', strtotime($row['date_of_birth'])),
                    'age' => $row['age'],
                    'sex' => $row['gender'] == 'Male' ? 'M' : ($row['gender'] == 'Female' ? 'F' : ''),
                    'barangay' => $row['barangay'],
                    'purok' => $row['purok'],
                    'contact_number' => $row['contact_number'],
                    'date_of_registration' => $row['date_of_registration']
                ];
            }

            return [
                'success' => true,
                'data' => $formattedResults,
                'total' => $countResult['total'] ?? 0,
                'page' => floor($offset / $limit) + 1,
                'total_pages' => ceil(($countResult['total'] ?? 0) / $limit),
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getNewlyRegisteredSeniors: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch newly registered seniors',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getCountByBarangayAndGender($barangay, $gender, $year = null, $month = null)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE ad.barangay = ? 
                AND a.gender = ?";

        if ($year !== null) {
            $sql .= " AND YEAR(ard.date_of_registration) = ?";
        }
        if ($month !== null) {
            $sql .= " AND MONTH(ard.date_of_registration) = ?";
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $params = [$barangay, $gender];

            if ($year !== null) {
                $params[] = $year;
            }
            if ($month !== null) {
                $params[] = $month;
            }

            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    // Get all barangays from database or default list
    // Get all barangays from database or default list
    private function getAllBarangays()
    {
        try {
            $sql = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND TRIM(barangay) != '' ORDER BY barangay";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($results)) {
                // Filter out any empty or null values
                $results = array_filter($results, function ($barangay) {
                    return !is_null($barangay) && trim($barangay) !== '';
                });
                return array_values($results); // Reindex array
            }
        } catch (PDOException $e) {
            error_log("Database error getting barangays: " . $e->getMessage());
        }

        // Default barangays if database query fails or returns empty
        return [
            'I - Mapalad',
            'II - Handang Tumulong',
            'III - Silahis ng Pag-asa',
            'IV - Pag-asa ng Bayan',
            'V - Bagong Silang',
            'VI - San Jose',
            'VII - Lumang Bayan',
            'VIII - Marikit',
            'IX - Tubili',
            'X - Alipaoy',
            'XI - Harison',
            'XII - Mananao'
        ];
    }

    public function getAvailableDateRanges()
    {
        try {
            $sql = "SELECT 
                    YEAR(date_of_registration) as year,
                    MONTH(date_of_registration) as month,
                    COUNT(*) as count
                    FROM applicant_registration_details
                    GROUP BY YEAR(date_of_registration), MONTH(date_of_registration)
                    ORDER BY year DESC, month DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $years = [];
            $months = [];

            foreach ($results as $row) {
                $years[$row['year']] = $row['year'];
                $months[$row['month']] = $row['month'];
            }

            return [
                'success' => true,
                'years' => array_values($years),
                'months' => array_values($months)
            ];
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch date ranges'
            ];
        }
    }
}

// Handle request
try {
    $reportAPI = new ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'get_senior_counts';

        switch ($action) {
            case 'get_senior_counts':
                $year = isset($_GET['year']) ? intval($_GET['year']) : null;
                $month = isset($_GET['month']) ? intval($_GET['month']) : null;

                if ($month !== null && ($month < 1 || $month > 12)) $month = null;
                if ($year !== null && ($year < 2000 || $year > 2100)) $year = null;

                $result = $reportAPI->getSeniorCounts($year, $month);
                echo json_encode($result);
                break;

            case 'get_newly_registered':
                $year = isset($_GET['year']) ? intval($_GET['year']) : null;
                $month = isset($_GET['month']) ? intval($_GET['month']) : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

                if ($month !== null && ($month < 1 || $month > 12)) $month = null;
                if ($year !== null && ($year < 2000 || $year > 2100)) $year = null;

                $result = $reportAPI->getNewlyRegisteredSeniors($year, $month, $limit, $offset);
                echo json_encode($result);
                break;

            case 'get_date_ranges':
                $result = $reportAPI->getAvailableDateRanges();
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
