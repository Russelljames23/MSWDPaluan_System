<?php
// generate_consolidated_report_backend.php - FIXED VERSION
require_once "../db.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class ConsolidatedReportAPI
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getAllReportData($year = null, $month = null)
    {
        try {
            // Get month name
            $monthName = '';
            if ($month) {
                $monthNames = [
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
                $monthName = $monthNames[$month] ?? '';
            }

            // PART 1: Number of Registered Senior Citizens
            $part1 = $this->getPart1Data($year, $month);

            // PART 2: Newly Registered Senior Citizens (For Validation)
            $part2 = $this->getPart2Data($year, $month);

            // PART 3: Number of Pensioners per Barangay
            $part3 = $this->getPart3Data($year, $month);

            // PART 4: Number of Localized Pensioners
            $part4 = $this->getPart4Data($year, $month);

            // PART 5: Bedridden Senior Citizens
            $part5 = $this->getPart5Data($year, $month);

            // PART 6: Deceased Senior Citizens
            $part6 = $this->getPart6Data($year, $month);

            // PART 7-9: PhilHealth, Booklets, Activities
            $part7to9 = $this->getPart7to9Data($year, $month);

            // Benefits Summary
            $benefits = $this->getBenefitsData($year, $month);

            return [
                'success' => true,
                'data' => [
                    'part1' => $part1,
                    'part2' => $part2,
                    'part3' => $part3,
                    'part4' => $part4,
                    'part5' => $part5,
                    'part6' => $part6,
                    'part7to9' => $part7to9,
                    'benefits' => $benefits
                ],
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => $monthName
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Error in getAllReportData: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching report data: ' . $e->getMessage()
            ];
        }
    }

    private function getPart2Data($year, $month)
    {
        try {
            // Use EXACT SAME logic as report_part2_backend.php
            $sql = "SELECT 
                    a.applicant_id,
                    a.last_name,
                    a.first_name,
                    a.middle_name,
                    a.suffix,
                    a.gender,
                    a.birth_date,
                    a.age,
                    COALESCE(ad.barangay, 'Not Specified') as barangay,
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

            $sql .= " ORDER BY a.date_created DESC, a.last_name, a.first_name";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formattedResults = [];
            foreach ($results as $index => $row) {
                $middleInitial = !empty($row['middle_name']) ? substr(trim($row['middle_name']), 0, 1) . '.' : '';
                $suffix = !empty($row['suffix']) ? ' ' . trim($row['suffix']) : '';
                $fullName = trim(
                    $row['last_name'] . ', ' .
                        $row['first_name'] . ' ' .
                        $middleInitial . $suffix
                );

                $dateOfBirth = 'N/A';
                if (!empty($row['birth_date']) && $row['birth_date'] != '0000-00-00') {
                    $dateOfBirth = date('m-d-Y', strtotime($row['birth_date']));
                }

                $genderCode = 'U';
                if (!empty($row['gender'])) {
                    $gender = strtoupper(trim($row['gender']));
                    $genderCode = ($gender == 'MALE' || $gender == 'M') ? 'M' : (($gender == 'FEMALE' || $gender == 'F') ? 'F' : 'U');
                }

                $age = $row['age'] ?? 'N/A';
                if ($age === 'N/A' && !empty($row['birth_date']) && $row['birth_date'] != '0000-00-00') {
                    $birthDateObj = new DateTime($row['birth_date']);
                    $today = new DateTime();
                    $age = $today->diff($birthDateObj)->y;
                }

                $formattedResults[] = [
                    'name' => htmlspecialchars($fullName),
                    'date_of_birth' => $dateOfBirth,
                    'age' => $age,
                    'sex' => $genderCode,
                    'barangay' => htmlspecialchars($row['barangay'] ?? 'Not Specified')
                ];
            }

            return [
                'data' => $formattedResults,
                'count' => count($formattedResults)
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart2Data: " . $e->getMessage());
            return [
                'data' => [],
                'count' => 0
            ];
        }
    }

    private function getBenefitsData($year, $month)
    {
        try {
            // Use the EXACT SAME logic as report_benefits_backend.php
            $benefits = $this->getAllBenefits();

            $benefitsData = [];
            $totalMale = 0;
            $totalFemale = 0;
            $totalOverall = 0;

            // First, add OSCA ID (New) which is handled specially
            $oscaRow = $this->getOSCAData('OSCA ID (New)', $year, $month);
            if ($oscaRow) {
                $displayName = $this->formatBenefitDisplayName('OSCA ID (New)');
                $benefitsData[$displayName] = [
                    'male' => $oscaRow['male_count'] ?? 0,
                    'female' => $oscaRow['female_count'] ?? 0,
                    'total' => $oscaRow['total_count'] ?? 0
                ];

                $totalMale += $oscaRow['male_count'] ?? 0;
                $totalFemale += $oscaRow['female_count'] ?? 0;
                $totalOverall += $oscaRow['total_count'] ?? 0;
            }

            // Then process all other benefits from the database
            foreach ($benefits as $benefit) {
                $benefitName = trim($benefit['benefit_name']);

                // Skip OSCA ID (New) since we already added it
                if ($benefitName === 'OSCA ID (New)') {
                    continue;
                }

                // Get data for this benefit
                $row = $this->getBenefitData($benefitName, $year, $month);

                $maleCount = $row['male_count'] ?? 0;
                $femaleCount = $row['female_count'] ?? 0;
                $totalCount = $row['total_count'] ?? 0;

                // Format display name
                $displayName = $this->formatBenefitDisplayName($benefitName);

                // Check if this benefit already exists
                if (isset($benefitsData[$displayName])) {
                    // Merge counts for duplicate benefits
                    $benefitsData[$displayName]['male'] += $maleCount;
                    $benefitsData[$displayName]['female'] += $femaleCount;
                    $benefitsData[$displayName]['total'] += $totalCount;
                } else {
                    $benefitsData[$displayName] = [
                        'male' => $maleCount,
                        'female' => $femaleCount,
                        'total' => $totalCount
                    ];
                }

                $totalMale += $maleCount;
                $totalFemale += $femaleCount;
                $totalOverall += $totalCount;
            }

            // Add totals row
            $benefitsData['TOTAL NUMBER OF SENIOR CITIZENS SERVED'] = [
                'male' => $totalMale,
                'female' => $totalFemale,
                'total' => $totalOverall
            ];

            return $benefitsData;
        } catch (PDOException $e) {
            error_log("Error in getBenefitsData: " . $e->getMessage());
            return $this->getDefaultBenefitsData();
        }
    }

    private function formatBenefitDisplayName($benefitName)
    {
        if ($benefitName === 'LSP Non Pensioners') {
            return 'LSP Non Pensioners';
        }

        if ($benefitName === 'OSCA ID (New)') {
            return 'Total # of SC Availed of OSCA ID (New)';
        }

        if (strpos($benefitName, 'Total # of SC Availed of ') === 0) {
            return $benefitName;
        }

        if (strpos($benefitName, 'SC Availed of ') === 0) {
            return 'Total # of ' . $benefitName;
        }

        if (strpos($benefitName, 'SC ') === 0) {
            return 'Total # of ' . $benefitName;
        }

        return 'Total # of SC Availed of ' . $benefitName;
    }

    private function getAllBenefits()
    {
        $sql = "SELECT id, benefit_name FROM benefits ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDefaultBenefitsData()
    {
        $defaultBenefits = [
            'Total # of SC Availed of OSCA ID (New)',
            'Total # of SC Availed of SP',
            'Total # of SC Availed of LSP (SSS/GSIS)',
            'LSP Non Pensioners',
            'Total # of SC Availed of AICS',
            'Total # of SC Availed of Birthday Gift',
            'Total # of SC Availed of Milestone',
            'Total # of Bedridden SC',
            'Total # of SC Availed of Burial Assistance',
            'Total # of SC Availed Medical Assistance Php.5,000.00 with wheel chair',
            'Total # of SC Centenarian Awardee (Php.50,000.00)',
            'Total # of SC (Provision Of Medical Assistance) Php.1,000.00 (Brgy.Mananao)',
            'Total # of SC Availed of Christmas Gift',
            'Total # of SC Availed of wheelchair',
            'Total # of SC Availed of eye glass',
            'Total # of SC Availed of bike',
            'Total # of SC Availed of ball'
        ];

        $benefitsData = [];
        foreach ($defaultBenefits as $benefit) {
            $benefitsData[$benefit] = [
                'male' => 0,
                'female' => 0,
                'total' => 0
            ];
        }

        $benefitsData['TOTAL NUMBER OF SENIOR CITIZENS SERVED'] = [
            'male' => 0,
            'female' => 0,
            'total' => 0
        ];

        return $benefitsData;
    }

    private function getOSCAData($benefitType, $year = null, $month = null)
    {
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

        if ($year !== null) {
            $sql .= " AND YEAR(igl.generation_date) = ?";
            $params[] = $year;
        }
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

        return [
            'benefit_name' => $benefitType,
            'male_count' => 0,
            'female_count' => 0,
            'total_count' => 0,
            'total_amount' => 0
        ];
    }

    private function getBenefitData($benefitType, $year = null, $month = null)
    {
        $sql = "SELECT 
                COUNT(DISTINCT CASE WHEN a.gender = 'Male' THEN bd.applicant_id END) as male_count,
                COUNT(DISTINCT CASE WHEN a.gender = 'Female' THEN bd.applicant_id END) as female_count,
                COUNT(DISTINCT bd.applicant_id) as total_count,
                COALESCE(SUM(bd.amount), 0) as total_amount
            FROM benefits_distribution bd 
            JOIN applicants a ON bd.applicant_id = a.applicant_id 
            WHERE bd.benefit_name LIKE ?";

        $params = ["%$benefitType%"];

        if ($year !== null) {
            $sql .= " AND YEAR(bd.distribution_date) = ?";
            $params[] = $year;
        }
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

        return [
            'benefit_name' => $benefitType,
            'male_count' => 0,
            'female_count' => 0,
            'total_count' => 0,
            'total_amount' => 0
        ];
    }

    private function getPart1Data($year, $month)
    {
        $barangays = $this->getAllBarangays();

        $data = [];
        $totals = ['male' => 0, 'female' => 0, 'overall' => 0];

        foreach ($barangays as $barangay) {
            $maleCount = $this->getCountByBarangayAndGender($barangay, 'Male', $year, $month);
            $femaleCount = $this->getCountByBarangayAndGender($barangay, 'Female', $year, $month);
            $totalCount = $maleCount + $femaleCount;

            $data[] = [
                'barangay' => $barangay,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'total_count' => $totalCount
            ];

            $totals['male'] += $maleCount;
            $totals['female'] += $femaleCount;
            $totals['overall'] += $totalCount;
        }

        return [
            'data' => $data,
            'totals' => $totals
        ];
    }

    private function getPart3Data($year, $month)
    {
        try {
            $barangays = $this->getAllBarangays();

            $data = [];
            $totals = ['male' => 0, 'female' => 0, 'overall' => 0];

            foreach ($barangays as $barangay) {
                $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.gender IN ('Male', 'M') THEN a.applicant_id END) as male_count,
                        COUNT(DISTINCT CASE WHEN a.gender IN ('Female', 'F') THEN a.applicant_id END) as female_count,
                        COUNT(DISTINCT a.applicant_id) as total_count
                    FROM applicants a 
                    LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                    WHERE a.status = 'Active' 
                    AND ad.barangay = ?";

                $params = [$barangay];

                if ($year !== null) {
                    $sql .= " AND YEAR(a.date_created) = ?";
                    $params[] = $year;
                }
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

                $data[] = [
                    'barangay' => $barangay,
                    'male_count' => $maleCount,
                    'female_count' => $femaleCount,
                    'total_count' => $totalCount
                ];

                $totals['male'] += $maleCount;
                $totals['female'] += $femaleCount;
                $totals['overall'] += $totalCount;
            }

            return [
                'data' => $data,
                'totals' => $totals
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart3Data: " . $e->getMessage());
            return ['data' => [], 'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]];
        }
    }

    private function getPart4Data($year, $month)
    {
        try {
            $barangays = $this->getAllBarangays();

            $data = [];
            $totals = ['male' => 0, 'female' => 0, 'overall' => 0];

            foreach ($barangays as $barangay) {
                $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.gender IN ('Male', 'M') THEN a.applicant_id END) as male_count,
                        COUNT(DISTINCT CASE WHEN a.gender IN ('Female', 'F') THEN a.applicant_id END) as female_count,
                        COUNT(DISTINCT a.applicant_id) as total_count
                    FROM applicants a 
                    INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                    INNER JOIN economic_status es ON a.applicant_id = es.applicant_id
                    WHERE a.status = 'Active' 
                    AND es.pension_source IN ('SSS', 'GSIS')
                    AND ad.barangay = ?";

                $params = [$barangay];

                if ($year !== null) {
                    $sql .= " AND YEAR(a.date_created) = ?";
                    $params[] = $year;
                }
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

                $data[] = [
                    'barangay' => $barangay,
                    'male_count' => $maleCount,
                    'female_count' => $femaleCount,
                    'total_count' => $totalCount
                ];

                $totals['male'] += $maleCount;
                $totals['female'] += $femaleCount;
                $totals['overall'] += $totalCount;
            }

            return [
                'data' => $data,
                'totals' => $totals
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart4Data: " . $e->getMessage());
            return ['data' => [], 'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]];
        }
    }

    private function getPart5Data($year, $month)
    {
        try {
            $sql = "SELECT 
                    CONCAT(a.last_name, ', ', a.first_name, ' ',
                           COALESCE(CONCAT(LEFT(a.middle_name, 1), '.'), '')) as name,
                    CASE WHEN a.gender = 'Male' THEN 'M' WHEN a.gender = 'Female' THEN 'F' ELSE '' END as sex,
                    ad.barangay
                FROM applicants a 
                LEFT JOIN health_condition hc ON a.applicant_id = hc.applicant_id 
                LEFT JOIN senior_illness si ON a.applicant_id = si.applicant_id 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Active' 
                AND (hc.has_existing_illness = 1 OR hc.hospitalized_last6mos = 1 OR si.illness_id IS NOT NULL)";

            $params = [];

            if ($year !== null) {
                $sql .= " AND (YEAR(a.date_created) = ? OR YEAR(si.illness_date) = ?)";
                $params[] = $year;
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND (MONTH(a.date_created) = ? OR MONTH(si.illness_date) = ?)";
                $params[] = $month;
                $params[] = $month;
            }

            $sql .= " GROUP BY a.applicant_id 
                     ORDER BY ad.barangay, a.last_name, a.first_name 
                     LIMIT 100";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $results,
                'count' => count($results)
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart5Data: " . $e->getMessage());
            return ['data' => [], 'count' => 0];
        }
    }

    private function getPart6Data($year, $month)
    {
        try {
            $sql = "SELECT 
                    CONCAT(a.last_name, ', ', a.first_name, ' ',
                           COALESCE(CONCAT(LEFT(a.middle_name, 1), '.'), '')) as name,
                    DATE_FORMAT(a.date_of_birth, '%m-%d-%Y') as date_of_birth,
                    ad.barangay
                FROM applicants a 
                LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                WHERE a.status = 'Deceased' 
                AND a.date_of_death IS NOT NULL 
                AND a.date_of_death != '0000-00-00'";

            $params = [];

            if ($year !== null) {
                $sql .= " AND YEAR(a.date_of_death) = ?";
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND MONTH(a.date_of_death) = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY a.date_of_death DESC, ad.barangay, a.last_name, a.first_name 
                     LIMIT 100";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $results,
                'count' => count($results)
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart6Data: " . $e->getMessage());
            return ['data' => [], 'count' => 0];
        }
    }

    private function getPart7to9Data($year, $month)
    {
        try {
            $philhealthCount = $this->getStatisticCount('philhealth', $year, $month);
            $bookletsCount = $this->getStatisticCount('booklets', $year, $month);
            $activities = $this->getActivities($year, $month);

            return [
                'philhealth_count' => $philhealthCount,
                'booklets_count' => $bookletsCount,
                'activities' => $activities
            ];
        } catch (PDOException $e) {
            error_log("Error in getPart7to9Data: " . $e->getMessage());
            return [
                'philhealth_count' => 0,
                'booklets_count' => 0,
                'activities' => []
            ];
        }
    }

    // Helper methods
    private function getAllBarangays()
    {
        try {
            $sql = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND TRIM(barangay) != '' ORDER BY barangay";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($results)) {
                return array_values(array_filter($results, function ($barangay) {
                    return !is_null($barangay) && trim($barangay) !== '';
                }));
            }
        } catch (PDOException $e) {
            error_log("Error getting barangays: " . $e->getMessage());
        }

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

    private function getCountByBarangayAndGender($barangay, $gender, $year = null, $month = null)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM applicants a 
                INNER JOIN addresses ad ON a.applicant_id = ad.applicant_id 
                INNER JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id 
                WHERE ad.barangay = ? 
                AND a.gender = ?";

        $params = [$barangay, $gender];

        if ($year !== null) {
            $sql .= " AND YEAR(ard.date_of_registration) = ?";
            $params[] = $year;
        }
        if ($month !== null) {
            $sql .= " AND MONTH(ard.date_of_registration) = ?";
            $params[] = $month;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getCountByBarangayAndGender: " . $e->getMessage());
            return 0;
        }
    }

    private function getStatisticCount($reportType, $year = null, $month = null)
    {
        try {
            $sql = "SELECT count FROM report_statistics WHERE report_type = ?";
            $params = [$reportType];

            if ($year !== null) {
                $sql .= " AND year = ?";
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND month = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY last_updated DESC LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getStatisticCount: " . $e->getMessage());
            return 0;
        }
    }

    private function getActivities($year = null, $month = null)
    {
        try {
            $sql = "SELECT description FROM report_activities WHERE 1=1";
            $params = [];

            if ($year !== null) {
                $sql .= " AND year = ?";
                $params[] = $year;
            }
            if ($month !== null) {
                $sql .= " AND month = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            return $results;
        } catch (PDOException $e) {
            error_log("Error in getActivities: " . $e->getMessage());
            return [];
        }
    }
}

// Handle request
try {
    $api = new ConsolidatedReportAPI($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;

        // Validate parameters
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        if ($year !== null && ($year < 1900 || $year > date('Y') + 1)) {
            $year = null;
        }

        $result = $api->getAllReportData($year, $month);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Consolidated Report Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
