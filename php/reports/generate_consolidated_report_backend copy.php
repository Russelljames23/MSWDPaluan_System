<?php
// generate_consolidated_report_backend.php - FIXED VERSION
// Adjust the path based on where this file is located
$dbPath = dirname(__DIR__) . '/db.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
} else {
    // Try alternative path
    $dbPath = dirname(dirname(__DIR__)) . '/php/db.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    } else {
        // Try one more path
        $dbPath = $_SERVER['DOCUMENT_ROOT'] . '/MSWDPALUAN_SYSTEM-MAIN/php/db.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        } else {
            // If all paths fail, try to find it
            die(json_encode(['success' => false, 'message' => 'Database connection file not found']));
        }
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

class ConsolidatedReportAPI
{
    private $conn;
    private $year;
    private $month;
    private $monthNames;

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
    }

    public function generateReport($year = null, $month = null)
    {
        $this->year = $year;
        $this->month = $month;

        try {
            // Test database connection first
            if (!$this->conn) {
                return [
                    'success' => false,
                    'message' => 'Database connection failed'
                ];
            }

            $reportData = [
                'part1' => $this->getPart1Data(),
                'part2' => $this->getPart2Data(),
                'part3' => $this->getPart3Data(),
                'part4' => $this->getPart4Data(),
                'part5' => $this->getPart5Data(),
                'part6' => $this->getPart6Data(),
                'part7to9' => $this->getPart7to9Data(),
                'benefits' => $this->getBenefitsData()
            ];

            return [
                'success' => true,
                'data' => $reportData,
                'filters' => [
                    'year' => $this->year,
                    'month' => $this->month,
                    'month_name' => $month ? $this->monthNames[$month] : null
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in generateReport: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("General error in generateReport: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ];
        }
    }

    // PART I: Number of Registered Senior Citizens - FIXED
    private function getPart1Data()
    {
        try {
            // Get all barangays
            $barangays = $this->getAllBarangays();

            $data = [];
            $totals = ['male' => 0, 'female' => 0, 'overall' => 0];

            foreach ($barangays as $barangay) {
                $male_count = $this->getCountByBarangayAndGender($barangay, 'Male');
                $female_count = $this->getCountByBarangayAndGender($barangay, 'Female');
                $barangay_total = $male_count + $female_count;

                $data[] = [
                    'barangay' => $barangay,
                    'male' => $male_count,
                    'female' => $female_count,
                    'total' => $barangay_total
                ];

                $totals['male'] += $male_count;
                $totals['female'] += $female_count;
                $totals['overall'] += $barangay_total;
            }

            return [
                'data' => $data,
                'totals' => [
                    'male' => $totals['male'],
                    'female' => $totals['female'],
                    'overall' => $totals['overall']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getPart1Data: " . $e->getMessage());
            return [
                'data' => [],
                'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
            ];
        }
    }

    // PART II: Number of Newly Registered Senior Citizens
    private function getPart2Data()
    {
        try {
            $sql = "SELECT 
                    a.applicant_id,
                    a.first_name,
                    a.middle_name,
                    a.last_name,
                    a.suffix,
                    a.gender,
                    a.birth_date as date_of_birth,
                    YEAR(CURDATE()) - YEAR(a.birth_date) - (RIGHT(CURDATE(), 5) < RIGHT(a.birth_date, 5)) as age,
                    ad.barangay,
                    ard.date_of_registration
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE a.status = 'Active'";  // Added status filter

            $params = [];

            // Add date filtering if year and/or month are provided
            if ($this->year !== null) {
                $sql .= " AND YEAR(ard.date_of_registration) = ?";
                $params[] = $this->year;
            }
            if ($this->month !== null) {
                $sql .= " AND MONTH(ard.date_of_registration) = ?";
                $params[] = $this->month;
            }

            // Order by registration date (newest first)
            $sql .= " ORDER BY ard.date_of_registration DESC, a.last_name, a.first_name";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the data
            $formattedResults = [];
            foreach ($results as $index => $row) {
                $middleInitial = !empty($row['middle_name']) ? substr($row['middle_name'], 0, 1) . '.' : '';
                $suffix = !empty($row['suffix']) ? ' ' . $row['suffix'] : '';

                $formattedResults[] = [
                    'number' => $index + 1,
                    'name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middleInitial . $suffix),
                    'date_of_birth' => !empty($row['date_of_birth']) && $row['date_of_birth'] != '0000-00-00'
                        ? date('m-d-Y', strtotime($row['date_of_birth']))
                        : 'N/A',
                    'age' => $row['age'],
                    'sex' => $row['gender'] == 'Male' ? 'M' : ($row['gender'] == 'Female' ? 'F' : 'N/A'),
                    'barangay' => $row['barangay'] ?? 'Not Specified',
                    'date_of_registration' => $row['date_of_registration']
                ];
            }

            return [
                'data' => $formattedResults,
                'count' => count($formattedResults)
            ];
        } catch (Exception $e) {
            error_log("Error in getPart2Data: " . $e->getMessage());
            return [
                'data' => [],
                'count' => 0
            ];
        }
    }

    // PART III: Number of Pensioners per Barangay - FIXED
    private function getPart3Data()
    {
        try {
            // Get barangays
            $barangays = $this->getAllBarangays();

            $results = [];
            $totalMale = 0;
            $totalFemale = 0;
            $totalOverall = 0;

            foreach ($barangays as $barangay) {
                // Use INNER JOIN to ensure we only get applicants with addresses
                $sql = "SELECT 
                        SUM(CASE WHEN a.gender = 'Male' OR a.gender = 'M' THEN 1 ELSE 0 END) as male_count,
                        SUM(CASE WHEN a.gender = 'Female' OR a.gender = 'F' THEN 1 ELSE 0 END) as female_count,
                        COUNT(DISTINCT a.applicant_id) as total_count
                    FROM applicants a 
                    INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                    WHERE a.status = 'Active' 
                    AND ad.barangay = ?";

                $params = [$barangay];

                // Add year filter if provided
                if ($this->year !== null) {
                    $sql .= " AND YEAR(a.date_created) = ?";
                    $params[] = $this->year;
                }

                // Add month filter if provided
                if ($this->month !== null) {
                    $sql .= " AND MONTH(a.date_created) = ?";
                    $params[] = $this->month;
                }

                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $maleCount = (int)($row['male_count'] ?? 0);
                $femaleCount = (int)($row['female_count'] ?? 0);
                $totalCount = (int)($row['total_count'] ?? 0);

                $results[] = [
                    'barangay' => $barangay,
                    'male' => $maleCount,
                    'female' => $femaleCount,
                    'total' => $totalCount
                ];

                $totalMale += $maleCount;
                $totalFemale += $femaleCount;
                $totalOverall += $totalCount;
            }

            return [
                'data' => $results,
                'totals' => [
                    'male' => $totalMale,
                    'female' => $totalFemale,
                    'overall' => $totalOverall
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getPart3Data: " . $e->getMessage());
            return [
                'data' => [],
                'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
            ];
        }
    }

    // PART IV: Number of Localized Pensioners - FIXED
    private function getPart4Data()
    {
        try {
            // Get barangays
            $barangays = $this->getAllBarangays();

            $results = [];
            $totalMale = 0;
            $totalFemale = 0;
            $totalOverall = 0;

            foreach ($barangays as $barangay) {
                // Use the exact query from report_part4_backend.php
                $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.gender = 'Male' OR a.gender = 'M' THEN a.applicant_id END) as male_count,
                        COUNT(DISTINCT CASE WHEN a.gender = 'Female' OR a.gender = 'F' THEN a.applicant_id END) as female_count,
                        COUNT(DISTINCT a.applicant_id) as total_count
                    FROM applicants a 
                    INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                    INNER JOIN economic_status es ON a.applicant_id = es.applicant_id
                    WHERE a.status = 'Active' 
                    AND es.pension_source IN ('SSS', 'GSIS')
                    AND ad.barangay = ?";

                $params = [$barangay];

                // Add year filter if provided
                if ($this->year !== null) {
                    $sql .= " AND YEAR(a.date_created) = ?";
                    $params[] = $this->year;
                }

                // Add month filter if provided
                if ($this->month !== null) {
                    $sql .= " AND MONTH(a.date_created) = ?";
                    $params[] = $this->month;
                }

                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $maleCount = (int)($row['male_count'] ?? 0);
                $femaleCount = (int)($row['female_count'] ?? 0);
                $totalCount = (int)($row['total_count'] ?? 0);

                $results[] = [
                    'barangay' => $barangay,
                    'male' => $maleCount,
                    'female' => $femaleCount,
                    'total' => $totalCount
                ];

                $totalMale += $maleCount;
                $totalFemale += $femaleCount;
                $totalOverall += $totalCount;
            }

            return [
                'data' => $results,
                'totals' => [
                    'male' => $totalMale,
                    'female' => $totalFemale,
                    'overall' => $totalOverall
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getPart4Data: " . $e->getMessage());
            return [
                'data' => [],
                'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
            ];
        }
    }

    // PART V: List of Bedridden Senior Citizens
    private function getPart5Data()
    {
        try {
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
            if ($this->year !== null) {
                $sql .= " AND (YEAR(a.date_created) = ? OR YEAR(si.illness_date) = ?)";
                $params[] = $this->year;
                $params[] = $this->year;
            }

            // Add month filter if provided
            if ($this->month !== null) {
                $sql .= " AND (MONTH(a.date_created) = ? OR MONTH(si.illness_date) = ?)";
                $params[] = $this->month;
                $params[] = $this->month;
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

            // Format results
            $formattedResults = [];
            foreach ($results as $index => $row) {
                $formattedResults[] = [
                    'number' => $index + 1,
                    'name' => $row['full_name'],
                    'sex' => $row['gender'] == 'Male' ? 'M' : ($row['gender'] == 'Female' ? 'F' : 'N/A'),
                    'barangay' => $row['barangay'] ?? 'Not Specified'
                ];
            }

            return [
                'data' => $formattedResults,
                'count' => count($formattedResults)
            ];
        } catch (Exception $e) {
            error_log("Error in getPart5Data: " . $e->getMessage());
            return [
                'data' => [],
                'count' => 0
            ];
        }
    }

    // PART VI: List of Deceased Registered Senior Citizens
    private function getPart6Data()
    {
        try {
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
            if ($this->year !== null) {
                $sql .= " AND YEAR(a.date_of_death) = ?";
                $params[] = $this->year;
            }

            // Add month filter if provided
            if ($this->month !== null) {
                $sql .= " AND MONTH(a.date_of_death) = ?";
                $params[] = $this->month;
            }

            $sql .= " ORDER BY a.date_of_death DESC, ad.barangay, a.last_name, a.first_name";

            $stmt = $this->conn->prepare($sql);

            if ($params) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format results
            $formattedResults = [];
            foreach ($results as $index => $row) {
                $formattedResults[] = [
                    'number' => $index + 1,
                    'name' => $row['full_name'],
                    'barangay' => $row['barangay'] ?? 'Not Specified'
                ];
            }

            return [
                'data' => $formattedResults,
                'count' => count($formattedResults)
            ];
        } catch (Exception $e) {
            error_log("Error in getPart6Data: " . $e->getMessage());
            return [
                'data' => [],
                'count' => 0
            ];
        }
    }

    // PART VII-IX: Statistics and Activities
    private function getPart7to9Data()
    {
        try {
            // Get PhilHealth count from report_statistics table
            $philhealthCount = 0;
            try {
                $sql = "SELECT count 
                    FROM report_statistics 
                    WHERE report_type = 'philhealth'";

                $params = [];

                if ($this->year !== null) {
                    $sql .= " AND year = ?";
                    $params[] = $this->year;
                }

                if ($this->month !== null) {
                    $sql .= " AND month = ?";
                    $params[] = $this->month;
                }

                $sql .= " ORDER BY last_updated DESC LIMIT 1";

                $stmt = $this->conn->prepare($sql);

                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }

                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $philhealthCount = $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error getting PhilHealth count from report_statistics: " . $e->getMessage());
            }

            // Get booklets count from report_statistics table
            $bookletsCount = 0;
            try {
                $sql = "SELECT count 
                    FROM report_statistics 
                    WHERE report_type = 'booklets'";

                $params = [];

                if ($this->year !== null) {
                    $sql .= " AND year = ?";
                    $params[] = $this->year;
                }

                if ($this->month !== null) {
                    $sql .= " AND month = ?";
                    $params[] = $this->month;
                }

                $sql .= " ORDER BY last_updated DESC LIMIT 1";

                $stmt = $this->conn->prepare($sql);

                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }

                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $bookletsCount = $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error getting booklets count from report_statistics: " . $e->getMessage());
            }

            // Get activities from report_activities table
            $activities = [];
            try {
                $sql = "SELECT description 
                    FROM report_activities 
                    WHERE 1=1";

                $params = [];

                if ($this->year !== null) {
                    $sql .= " AND year = ?";
                    $params[] = $this->year;
                }

                if ($this->month !== null) {
                    $sql .= " AND month = ?";
                    $params[] = $this->month;
                }

                $sql .= " ORDER BY created_at DESC";

                $stmt = $this->conn->prepare($sql);

                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as $row) {
                    $activities[] = $row['description'];
                }
            } catch (Exception $e) {
                error_log("Error getting activities: " . $e->getMessage());
            }

            // If no activities found in database, use default activities
            if (empty($activities)) {
                $activities = [
                    "Monthly meeting with senior citizens",
                    "Distribution of social pension benefits",
                    "Health monitoring and check-up activities"
                ];

                // Add month-specific activity
                if ($this->month !== null && $this->year !== null) {
                    $monthName = $this->monthNames[$this->month];
                    $activities[] = "Monthly report for " . $monthName . " " . $this->year;
                }
            }

            return [
                'philhealth_count' => $philhealthCount,
                'booklets_count' => $bookletsCount,
                'activities' => $activities
            ];
        } catch (Exception $e) {
            error_log("Error in getPart7to9Data: " . $e->getMessage());
            return [
                'philhealth_count' => 0,
                'booklets_count' => 0,
                'activities' => []
            ];
        }
    }

    // Benefits Summary
    private function getBenefitsData()
    {
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

        $benefits = [];

        try {
            foreach ($benefitTypes as $benefitType) {
                if ($benefitType === 'OSCA ID (New)') {
                    $benefitData = $this->getOSCAData($benefitType);
                } else {
                    $benefitData = $this->getBenefitDistributionData($benefitType);
                }

                $benefits[$benefitType] = [
                    'male' => $benefitData['male'] ?? 0,
                    'female' => $benefitData['female'] ?? 0,
                    'total' => $benefitData['total'] ?? 0
                ];
            }
        } catch (Exception $e) {
            error_log("Error in getBenefitsData: " . $e->getMessage());
            // Return empty benefits if error
            foreach ($benefitTypes as $benefit) {
                $benefits[$benefit] = [
                    'male' => 0,
                    'female' => 0,
                    'total' => 0
                ];
            }
        }

        return $benefits;
    }

    // Get OSCA ID data from id_generation_logs table
    private function getOSCAData($benefitType)
    {
        try {
            $sql = "SELECT 
                COUNT(DISTINCT CASE WHEN a.gender = 'Male' THEN igl.applicant_id END) as male_count,
                COUNT(DISTINCT CASE WHEN a.gender = 'Female' THEN igl.applicant_id END) as female_count,
                COUNT(DISTINCT igl.applicant_id) as total_count
            FROM id_generation_logs igl 
            JOIN applicants a ON igl.applicant_id = a.applicant_id 
            WHERE igl.status = 'Printed'
            AND igl.generation_date IS NOT NULL 
            AND igl.generation_date != '0000-00-00'";

            $params = [];

            // Add year filter if provided
            if ($this->year !== null) {
                $sql .= " AND YEAR(igl.generation_date) = ?";
                $params[] = $this->year;
            }

            // Add month filter if provided
            if ($this->month !== null) {
                $sql .= " AND MONTH(igl.generation_date) = ?";
                $params[] = $this->month;
            }

            $stmt = $this->conn->prepare($sql);

            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'male' => (int)$row['male_count'],
                    'female' => (int)$row['female_count'],
                    'total' => (int)$row['total_count']
                ];
            }

            return ['male' => 0, 'female' => 0, 'total' => 0];
        } catch (Exception $e) {
            error_log("Error in getOSCAData: " . $e->getMessage());
            return ['male' => 0, 'female' => 0, 'total' => 0];
        }
    }

    // Get benefit distribution data
    private function getBenefitDistributionData($benefitType)
    {
        try {
            $sql = "SELECT 
                COUNT(DISTINCT CASE WHEN a.gender = 'Male' THEN bd.applicant_id END) as male_count,
                COUNT(DISTINCT CASE WHEN a.gender = 'Female' THEN bd.applicant_id END) as female_count,
                COUNT(DISTINCT bd.applicant_id) as total_count
            FROM benefits_distribution bd 
            JOIN applicants a ON bd.applicant_id = a.applicant_id 
            WHERE bd.benefit_name LIKE ?";

            $params = ["%$benefitType%"];

            // Add year filter if provided
            if ($this->year !== null) {
                $sql .= " AND YEAR(bd.distribution_date) = ?";
                $params[] = $this->year;
            }

            // Add month filter if provided
            if ($this->month !== null) {
                $sql .= " AND MONTH(bd.distribution_date) = ?";
                $params[] = $this->month;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'male' => (int)$row['male_count'],
                    'female' => (int)$row['female_count'],
                    'total' => (int)$row['total_count']
                ];
            }

            return ['male' => 0, 'female' => 0, 'total' => 0];
        } catch (Exception $e) {
            error_log("Error in getBenefitDistributionData for $benefitType: " . $e->getMessage());
            return ['male' => 0, 'female' => 0, 'total' => 0];
        }
    }

    // Helper methods - FIXED
    private function getCountByBarangayAndGender($barangay, $gender)
    {
        $sql = "SELECT COUNT(DISTINCT a.applicant_id) as count 
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE a.status = 'Active' 
                AND ad.barangay = ? 
                AND a.gender = ?";

        $params = [$barangay, $gender];

        if ($this->year !== null) {
            $sql .= " AND YEAR(ard.date_of_registration) = ?";
            $params[] = $this->year;
        }
        if ($this->month !== null) {
            $sql .= " AND MONTH(ard.date_of_registration) = ?";
            $params[] = $this->month;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Database error in getCountByBarangayAndGender: " . $e->getMessage());
            return 0;
        }
    }

    // Get all barangays from database or default list - FIXED
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
                    return !is_null($barangay) && trim($barangay) !== '' && $barangay !== 'Barangay';
                });

                if (empty($results)) {
                    return $this->getDefaultBarangays();
                }

                return array_values($results); // Reindex array
            }

            return $this->getDefaultBarangays();
        } catch (PDOException $e) {
            error_log("Database error getting barangays: " . $e->getMessage());
            return $this->getDefaultBarangays();
        }
    }

    private function getDefaultBarangays()
    {
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
}

// Handle request
try {
    $api = new ConsolidatedReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        // Validate
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        if ($year !== null && ($year < 2000 || $year > date('Y') + 1)) {
            $year = null;
        }

        $result = $api->generateReport($year, $month);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
