<?php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class Part4ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getLocalizedPensioners($year = null, $month = null)
    {
        try {
            // Define all barangays (use same as Part 3 for consistency)
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
                'XI - Harrison',
                'XII - Mananao'
            ];

            // Get barangays from database if they exist
            $barangayQuery = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
            $barangayStmt = $this->conn->prepare($barangayQuery);
            $barangayStmt->execute();
            $dbBarangays = $barangayStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Use database barangays if available
            if (!empty($dbBarangays)) {
                $barangays = $dbBarangays;
            }

            $results = [];
            $totalMale = 0;
            $totalFemale = 0;
            $totalOverall = 0;

            foreach ($barangays as $barangay) {
                // Build query for localized pensioners (SSS or GSIS)
                $sql = "SELECT 
                        SUM(CASE WHEN a.gender = 'Male' OR a.gender = 'M' THEN 1 ELSE 0 END) as male_count,
                        SUM(CASE WHEN a.gender = 'Female' OR a.gender = 'F' THEN 1 ELSE 0 END) as female_count,
                        COUNT(DISTINCT a.applicant_id) as total_count
                    FROM applicants a 
                    INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                    INNER JOIN economic_status es ON a.applicant_id = es.applicant_id
                    WHERE a.status = 'Active' 
                    AND es.pension_source IN ('SSS', 'GSIS')";
                
                $params = [];
                
                // Add barangay filter
                $sql .= " AND ad.barangay = ?";
                $params[] = $barangay;

                // Add year filter if provided
                if ($year !== null) {
                    $sql .= " AND YEAR(a.date_created) = ?";
                    $params[] = $year;
                }

                // Add month filter if provided
                if ($month !== null) {
                    $sql .= " AND MONTH(a.date_created) = ?";
                    $params[] = $month;
                }

                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $maleCount = $row['male_count'] ?? 0;
                $femaleCount = $row['female_count'] ?? 0;
                $totalCount = $row['total_count'] ?? 0;

                $results[] = [
                    'barangay' => $barangay,
                    'male' => (int)$maleCount,
                    'female' => (int)$femaleCount,
                    'total' => (int)$totalCount
                ];

                $totalMale += (int)$maleCount;
                $totalFemale += (int)$femaleCount;
                $totalOverall += (int)$totalCount;
            }

            // Add totals row
            $results[] = [
                'barangay' => 'Total',
                'male' => $totalMale,
                'female' => $totalFemale,
                'total' => $totalOverall,
                'is_total' => true
            ];

            return [
                'success' => true,
                'data' => $results,
                'summary' => [
                    'total_male' => $totalMale,
                    'total_female' => $totalFemale,
                    'total_overall' => $totalOverall
                ],
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getLocalizedPensioners: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }
}

try {
    $api = new Part4ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        $result = $api->getLocalizedPensioners($year, $month);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}