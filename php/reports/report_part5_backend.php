<?php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class Part5ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getBedriddenSeniors($year = null, $month = null)
    {
        try {
            // Query to get bedridden seniors from health_condition and senior_illness tables
            $sql = "SELECT 
                    a.applicant_id,
                    CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
                    a.gender,
                    ad.barangay,
                    hc.has_existing_illness,
                    hc.hospitalized_last6mos,
                    GROUP_CONCAT(DISTINCT si.illness_name SEPARATOR ', ') as illnesses,
                    MAX(si.illness_date) as latest_illness_date
                FROM applicants a 
                LEFT JOIN health_condition hc ON a.applicant_id = hc.applicant_id 
                LEFT JOIN senior_illness si ON a.applicant_id = si.applicant_id 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Active' 
                AND (hc.has_existing_illness = 1 OR hc.hospitalized_last6mos = 1 OR si.illness_id IS NOT NULL)";
            
            $params = [];
            
            // Add year filter if provided
            if ($year !== null) {
                $sql .= " AND (YEAR(a.date_created) = ? OR YEAR(si.illness_date) = ?)";
                $params[] = $year;
                $params[] = $year;
            }

            // Add month filter if provided
            if ($month !== null) {
                $sql .= " AND (MONTH(a.date_created) = ? OR MONTH(si.illness_date) = ?)";
                $params[] = $month;
                $params[] = $month;
            }

            $sql .= " GROUP BY a.applicant_id, a.first_name, a.middle_name, a.last_name, a.gender, ad.barangay, hc.has_existing_illness, hc.hospitalized_last6mos
                     ORDER BY ad.barangay, a.last_name, a.first_name";

            $stmt = $this->conn->prepare($sql);
            
            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalCount = count($results);

            // Get statistics
            $stats = $this->getBedriddenStatistics($year, $month);

            return [
                'success' => true,
                'data' => $results,
                'count' => $totalCount,
                'statistics' => $stats,
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getBedriddenSeniors: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }

    private function getBedriddenStatistics($year = null, $month = null)
    {
        try {
            // Get statistics by barangay and gender
            $sql = "SELECT 
                    ad.barangay,
                    COUNT(DISTINCT a.applicant_id) as total_count,
                    SUM(CASE WHEN a.gender = 'Male' OR a.gender = 'M' THEN 1 ELSE 0 END) as male_count,
                    SUM(CASE WHEN a.gender = 'Female' OR a.gender = 'F' THEN 1 ELSE 0 END) as female_count
                FROM applicants a 
                LEFT JOIN health_condition hc ON a.applicant_id = hc.applicant_id 
                LEFT JOIN senior_illness si ON a.applicant_id = si.applicant_id 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Active' 
                AND (hc.has_existing_illness = 1 OR hc.hospitalized_last6mos = 1 OR si.illness_id IS NOT NULL)";
            
            $params = [];
            
            // Add year filter if provided
            if ($year !== null) {
                $sql .= " AND (YEAR(a.date_created) = ? OR YEAR(si.illness_date) = ?)";
                $params[] = $year;
                $params[] = $year;
            }

            // Add month filter if provided
            if ($month !== null) {
                $sql .= " AND (MONTH(a.date_created) = ? OR MONTH(si.illness_date) = ?)";
                $params[] = $month;
                $params[] = $month;
            }

            $sql .= " GROUP BY ad.barangay ORDER BY ad.barangay";

            $stmt = $this->conn->prepare($sql);
            
            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getBedriddenStatistics: " . $e->getMessage());
            return [];
        }
    }
}

try {
    $api = new Part5ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        $result = $api->getBedriddenSeniors($year, $month);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}