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
                'OSCA ID (New)',
                'Social Pension',
                'LSP (SSS/GSIS)',
                'LSP Non Pensioners',
                'AICS',
                'Birthday Gift',
                'Milestone',
                'Bedridden SC',
                'Burial Assistance',
                'Medical Assistance Php.5,000.00',
                'Centenarian Awardee (Php.50,000.00)',
                'Medical Assistance Php.1,000.00',
                'Christmas Gift'
            ];

            $results = [];
            $totalCount = 0;
            $totalAmount = 0;

            foreach ($benefitTypes as $benefitType) {
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

    public function getAvailableYears()
    {
        try {
            $sql = "SELECT DISTINCT YEAR(distribution_date) as year 
                    FROM benefits_distribution 
                    WHERE distribution_date IS NOT NULL 
                    AND distribution_date != '0000-00-00' 
                    ORDER BY year DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $years = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Filter out invalid years
            $validYears = array_filter($years, function ($year) {
                return $year > 1900;
            });

            return array_values($validYears);
        } catch (PDOException $e) {
            error_log("Database error in getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    public function getBenefitsCountByYear()
    {
        try {
            $sql = "SELECT 
                    YEAR(distribution_date) as year,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM benefits_distribution 
                WHERE distribution_date IS NOT NULL 
                AND distribution_date != '0000-00-00' 
                GROUP BY YEAR(distribution_date) 
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
