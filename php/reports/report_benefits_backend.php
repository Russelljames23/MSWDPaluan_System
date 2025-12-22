<?php
// report_benefits_backend.php
require_once "../db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class BenefitsReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getBenefitsSummary($year = null, $month = null)
    {
        try {
            // Define all the benefit types we want to track
            $benefitTypes = [
                'OSCA ID (New)' => 'id',
                'Social Pension' => 'benefit',
                'LSP (SSS/GSIS)' => 'benefit',
                'LSP Non Pensioners' => 'benefit',
                'AICS' => 'benefit',
                'Birthday Gift' => 'benefit',
                'Milestone' => 'benefit',
                'Bedridden SC' => 'benefit',
                'Burial Assistance' => 'benefit',
                'Medical Assistance Php.5,000.00' => 'benefit',
                'Centenarian Awardee (Php.50,000.00)' => 'benefit',
                'Medical Assistance Php.1,000.00' => 'benefit',
                'Christmas Gift' => 'benefit'
            ];

            $results = [];
            $totalCount = 0;
            $totalAmount = 0;

            foreach ($benefitTypes as $benefitType => $source) {
                if ($source === 'id') {
                    // Handle OSCA ID (New) from ID generation tables
                    $row = $this->getOSCAData($benefitType, $year, $month);
                } else {
                    // Handle other benefits from benefits_distribution table
                    $row = $this->getBenefitData($benefitType, $year, $month);
                }

                if ($row) {
                    $results[] = $row;
                    $totalCount += $row['total_count'];
                    $totalAmount += $row['total_amount'];
                } else {
                    // Add zero counts if no data found
                    $results[] = [
                        'benefit_name' => $benefitType,
                        'male_count' => 0,
                        'female_count' => 0,
                        'total_count' => 0,
                        'total_amount' => 0
                    ];
                }
            }

            return [
                'success' => true,
                'data' => $results,
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
                'filters' => [
                    'year' => $year,
                    'month' => $month
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getBenefitsSummary: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }

    private function getOSCAData($benefitType, $year = null, $month = null)
    {
        // Query to get OSCA ID data from id_generation_logs table
        $sql = "SELECT 
                COUNT(DISTINCT CASE WHEN a.gender = 'Male' THEN igl.applicant_id END) as male_count,
                COUNT(DISTINCT CASE WHEN a.gender = 'Female' THEN igl.applicant_id END) as female_count,
                COUNT(DISTINCT igl.applicant_id) as total_count,
                0 as total_amount
            FROM id_generation_logs igl 
            JOIN applicants a ON igl.applicant_id = a.applicant_id 
            WHERE igl.status = 'Printed'
            AND igl.generation_date IS NOT NULL 
            AND igl.generation_date != '0000-00-00'";

        $params = [];

        // Add year filter if provided
        if ($year !== null) {
            $sql .= " AND YEAR(igl.generation_date) = ?";
            $params[] = $year;
        }

        // Add month filter if provided
        if ($month !== null) {
            $sql .= " AND MONTH(igl.generation_date) = ?";
            $params[] = $month;
        }

        $stmt = $this->conn->prepare($sql);

        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['benefit_name'] = $benefitType;
            return $row;
        }

        return null;
    }

    private function getBenefitData($benefitType, $year = null, $month = null)
    {
        $sql = "SELECT 
                COUNT(DISTINCT CASE WHEN a.gender = 'Male' THEN bd.applicant_id END) as male_count,
                COUNT(DISTINCT CASE WHEN a.gender = 'Female' THEN bd.applicant_id END) as female_count,
                COUNT(DISTINCT bd.applicant_id) as total_count,
                SUM(bd.amount) as total_amount
            FROM benefits_distribution bd 
            JOIN applicants a ON bd.applicant_id = a.applicant_id 
            WHERE bd.benefit_name LIKE ?";

        $params = ["%$benefitType%"];

        // Add year filter if provided
        if ($year !== null) {
            $sql .= " AND YEAR(bd.distribution_date) = ?";
            $params[] = $year;
        }

        // Add month filter if provided
        if ($month !== null) {
            $sql .= " AND MONTH(bd.distribution_date) = ?";
            $params[] = $month;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['benefit_name'] = $benefitType;
            return $row;
        }

        return null;
    }

    public function getAvailableYears()
    {
        try {
            // Get years from both benefits_distribution and id_generation_logs
            $years = [];

            // Get years from benefits_distribution
            $sql1 = "SELECT DISTINCT YEAR(distribution_date) as year 
                    FROM benefits_distribution 
                    WHERE distribution_date IS NOT NULL 
                    AND distribution_date != '0000-00-00' 
                    ORDER BY year DESC";

            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute();
            $years1 = $stmt1->fetchAll(PDO::FETCH_COLUMN, 0);
            $years = array_merge($years, $years1);

            // Get years from id_generation_logs for OSCA ID
            $sql2 = "SELECT DISTINCT YEAR(generation_date) as year 
                    FROM id_generation_logs 
                    WHERE generation_date IS NOT NULL 
                    AND generation_date != '0000-00-00' 
                    ORDER BY year DESC";

            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute();
            $years2 = $stmt2->fetchAll(PDO::FETCH_COLUMN, 0);
            $years = array_merge($years, $years2);

            // Filter out invalid years and get unique values
            $validYears = array_filter($years, function ($year) {
                return $year > 1900;
            });

            $uniqueYears = array_unique($validYears);
            rsort($uniqueYears); // Sort descending

            return array_values($uniqueYears);
        } catch (PDOException $e) {
            error_log("Database error in getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    public function getBenefitsCountByYear()
    {
        try {
            // Get counts from both tables combined
            $sql = "SELECT 
                    YEAR(date_field) as year,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM (
                    SELECT distribution_date as date_field, amount, 1 as source 
                    FROM benefits_distribution 
                    WHERE distribution_date IS NOT NULL 
                    AND distribution_date != '0000-00-00'
                    
                    UNION ALL
                    
                    SELECT generation_date as date_field, 0 as amount, 2 as source 
                    FROM id_generation_logs 
                    WHERE generation_date IS NOT NULL 
                    AND generation_date != '0000-00-00'
                    AND status = 'Printed'
                ) combined
                GROUP BY YEAR(date_field) 
                ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getBenefitsCountByYear: " . $e->getMessage());
            return [];
        }
    }
}

try {
    $api = new BenefitsReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        $result = $api->getBenefitsSummary($year, $month);

        // Add available years to the response
        if ($result['success']) {
            $result['available_years'] = $api->getAvailableYears();
            $result['year_counts'] = $api->getBenefitsCountByYear();
        }

        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
