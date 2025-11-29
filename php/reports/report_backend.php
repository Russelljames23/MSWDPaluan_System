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

    public function getSeniorCounts($year = null, $month = null)
    {
        $barangays = [
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
        }

        return [
            'success' => true,
            'data' => $data,
            'totals' => $totals,
            'filters' => [
                'year' => $year,
                'month' => $month
            ]
        ];
    }

    private function getCountByBarangayAndGender($barangay, $gender, $year = null, $month = null)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE ad.barangay = ? 
                AND a.gender = ?";

        // Add date filtering if year and/or month are provided
        if ($year !== null) {
            $sql .= " AND YEAR(ard.date_of_registration) = ?";
        }
        if ($month !== null) {
            $sql .= " AND MONTH(ard.date_of_registration) = ?";
        }

        try {
            $stmt = $this->conn->prepare($sql);

            // Build parameters array dynamically
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

    // New method to get available years and months from database
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
                // Get filter parameters
                $year = isset($_GET['year']) ? intval($_GET['year']) : null;
                $month = isset($_GET['month']) ? intval($_GET['month']) : null;

                // Validate month range
                if ($month !== null && ($month < 1 || $month > 12)) {
                    $month = null;
                }

                // Validate year (reasonable range)
                if ($year !== null && ($year < 2000 || $year > 2100)) {
                    $year = null;
                }

                $result = $reportAPI->getSeniorCounts($year, $month);
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

// Don't close the connection here as it's managed by config.php
// The connection will be automatically closed when the script ends
