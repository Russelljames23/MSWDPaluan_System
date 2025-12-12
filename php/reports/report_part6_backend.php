<?php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class Part6ReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getDeceasedSeniors($year = null, $month = null)
    {
        try {
            // Query to get deceased seniors from applicants table
            $sql = "SELECT 
                    a.applicant_id,
                    a.last_name,
                    a.first_name,
                    a.middle_name,
                    CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
                    a.status,
                    a.date_of_death as deceased_date,
                    ad.barangay,
                    a.date_created,
                    a.gender,
                    a.validation,
                    a.control_number
                FROM applicants a 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Deceased' 
                AND a.date_of_death IS NOT NULL 
                AND a.date_of_death != '0000-00-00'";

            $params = [];

            // Add year filter if provided
            if ($year !== null) {
                $sql .= " AND YEAR(a.date_of_death) = ?";
                $params[] = $year;
            }

            // Add month filter if provided
            if ($month !== null) {
                $sql .= " AND MONTH(a.date_of_death) = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY a.date_of_death DESC, ad.barangay, a.last_name, a.first_name";

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalCount = count($results);

            // Get statistics
            $stats = $this->getDeceasedStatistics($year, $month);

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
            error_log("Database error in getDeceasedSeniors: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }

    private function getDeceasedStatistics($year = null, $month = null)
    {
        try {
            // Get statistics by barangay and year
            $sql = "SELECT 
                    ad.barangay,
                    COUNT(DISTINCT a.applicant_id) as total_deaths,
                    YEAR(a.date_of_death) as death_year,
                    MONTH(a.date_of_death) as death_month,
                    DATE_FORMAT(a.date_of_death, '%M') as month_name
                FROM applicants a 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Deceased' 
                AND a.date_of_death IS NOT NULL 
                AND a.date_of_death != '0000-00-00'";

            $params = [];

            // Add year filter if provided
            if ($year !== null) {
                $sql .= " AND YEAR(a.date_of_death) = ?";
                $params[] = $year;
            }

            // Add month filter if provided
            if ($month !== null) {
                $sql .= " AND MONTH(a.date_of_death) = ?";
                $params[] = $month;
            }

            $sql .= " GROUP BY ad.barangay, YEAR(a.date_of_death), MONTH(a.date_of_death) 
                     ORDER BY YEAR(a.date_of_death) DESC, MONTH(a.date_of_death) DESC, ad.barangay";

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDeceasedStatistics: " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableYears()
    {
        try {
            $sql = "SELECT DISTINCT YEAR(date_of_death) as year 
                    FROM applicants 
                    WHERE status = 'Deceased' 
                    AND date_of_death IS NOT NULL 
                    AND date_of_death != '0000-00-00' 
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $years = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Filter out invalid years (like 0 or null)
            $validYears = array_filter($years, function ($year) {
                return $year > 1900;
            });

            return array_values($validYears);
        } catch (PDOException $e) {
            error_log("Database error in getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    public function getDeceasedCountByYear()
    {
        try {
            $sql = "SELECT 
                    YEAR(date_of_death) as year,
                    COUNT(*) as count
                FROM applicants 
                WHERE status = 'Deceased' 
                AND date_of_death IS NOT NULL 
                AND date_of_death != '0000-00-00' 
                GROUP BY YEAR(date_of_death) 
                ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDeceasedCountByYear: " . $e->getMessage());
            return [];
        }
    }
}

try {
    $api = new Part6ReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        $result = $api->getDeceasedSeniors($year, $month);

        // Add available years to the response
        if ($result['success']) {
            $result['available_years'] = $api->getAvailableYears();
            $result['year_counts'] = $api->getDeceasedCountByYear();
        }

        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
