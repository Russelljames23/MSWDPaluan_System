<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Database connection settings
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Get POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $month = isset($data['month']) ? $data['month'] : null;
    $year = isset($data['year']) ? $data['year'] : null;
    $benefit_option = isset($data['benefit_option']) ? $data['benefit_option'] : 'all';
    $benefit_id = isset($data['benefit_id']) ? $data['benefit_id'] : null;
    $benefit_ids = isset($data['benefit_ids']) ? $data['benefit_ids'] : [];

    // Validate month and year
    if (!$month || !$year) {
        echo json_encode(['success' => false, 'message' => 'Month and year are required']);
        exit;
    }

    if (!is_numeric($month) || $month < 1 || $month > 12) {
        echo json_encode(['success' => false, 'message' => 'Invalid month']);
        exit;
    }

    if (!is_numeric($year) || $year < 2000 || $year > 2100) {
        echo json_encode(['success' => false, 'message' => 'Invalid year']);
        exit;
    }

    $response = [
        'success' => true,
        'data' => []
    ];

    // ==================== PART 1: Get benefits distribution data ====================
    $query = "
        SELECT 
            bd.applicant_id,
            CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
            a.age,
            a.gender,
            a.civil_status,
            addr.barangay,
            a.birth_date,
            a.contact_number,
            b.benefit_name,
            b.id as benefit_id,
            bd.amount,
            bd.distribution_date as date_received
        FROM benefits_distribution bd
        INNER JOIN applicants a ON bd.applicant_id = a.applicant_id
        INNER JOIN benefits b ON bd.benefit_id = b.id
        LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
        WHERE MONTH(bd.distribution_date) = :month 
        AND YEAR(bd.distribution_date) = :year
    ";

    $params = [
        ':month' => $month,
        ':year' => $year
    ];

    // Add benefit filter based on selection option
    if ($benefit_option === 'specific' && $benefit_id && $benefit_id !== 'null' && $benefit_id !== '' && $benefit_id !== 'undefined') {
        $query .= " AND bd.benefit_id = :benefit_id";
        $params[':benefit_id'] = $benefit_id;
    } elseif ($benefit_option === 'multiple' && is_array($benefit_ids) && count($benefit_ids) > 0) {
        // Filter by multiple benefit IDs
        $placeholders = [];
        foreach ($benefit_ids as $index => $id) {
            $placeholder = ":benefit_id_" . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }
        $query .= " AND bd.benefit_id IN (" . implode(',', $placeholders) . ")";
    }

    $query .= " ORDER BY b.benefit_name, full_name, bd.distribution_date";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['beneficiaries'] = $beneficiaries;

    // ==================== PART 1B: Get benefit-wise distribution data for separate tables ====================
    if ($benefit_option === 'all') {
        // First, get all benefits distributed in this month
        $benefitListQuery = "
            SELECT DISTINCT 
                b.id,
                b.benefit_name
            FROM benefits_distribution bd
            INNER JOIN benefits b ON bd.benefit_id = b.id
            WHERE MONTH(bd.distribution_date) = :month 
            AND YEAR(bd.distribution_date) = :year
            ORDER BY b.benefit_name
        ";

        $benefitListStmt = $pdo->prepare($benefitListQuery);
        $benefitListStmt->execute([':month' => $month, ':year' => $year]);
        $monthly_benefits = $benefitListStmt->fetchAll(PDO::FETCH_ASSOC);

        $response['data']['monthly_benefits'] = $monthly_benefits;

        // For each benefit, get detailed beneficiary list
        $benefitWiseData = [];
        foreach ($monthly_benefits as $benefit) {
            $benefitDetailQuery = "
                SELECT 
                    bd.applicant_id,
                    CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
                    a.age,
                    a.gender,
                    addr.barangay,
                    bd.amount,
                    bd.distribution_date as date_received
                FROM benefits_distribution bd
                INNER JOIN applicants a ON bd.applicant_id = a.applicant_id
                LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
                WHERE bd.benefit_id = :benefit_id
                AND MONTH(bd.distribution_date) = :month 
                AND YEAR(bd.distribution_date) = :year
                ORDER BY full_name
            ";

            $benefitDetailStmt = $pdo->prepare($benefitDetailQuery);
            $benefitDetailStmt->execute([
                ':benefit_id' => $benefit['id'],
                ':month' => $month,
                ':year' => $year
            ]);

            $beneficiaries_for_benefit = $benefitDetailStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals for this benefit
            $total_amount = 0;
            $recipient_count = count($beneficiaries_for_benefit);

            foreach ($beneficiaries_for_benefit as $beneficiary) {
                $total_amount += floatval($beneficiary['amount']);
            }

            $benefitWiseData[] = [
                'benefit_id' => $benefit['id'],
                'benefit_name' => $benefit['benefit_name'],
                'recipients' => $beneficiaries_for_benefit,
                'total_amount' => $total_amount,
                'recipient_count' => $recipient_count,
                'average_amount' => $recipient_count > 0 ? $total_amount / $recipient_count : 0
            ];
        }

        $response['data']['benefit_wise_data'] = $benefitWiseData;
    }

    // ==================== PART 2: Get list of all seniors ====================
    $seniorsQuery = "
        SELECT 
            a.applicant_id,
            CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
            a.age,
            a.gender,
            a.civil_status,
            a.birth_date,
            addr.barangay,
            a.contact_number,
            a.validation,
            a.status,
            a.date_created
        FROM applicants a
        LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
        WHERE a.status IN ('Active', 'Deceased', 'Inactive')
        ORDER BY a.last_name, a.first_name
    ";

    $seniorsStmt = $pdo->prepare($seniorsQuery);
    $seniorsStmt->execute();
    $allSeniors = $seniorsStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['all_seniors'] = $allSeniors;

    // ==================== PART 3: Get seniors who received benefits this month ====================
    $benefitedSeniorsQuery = "
        SELECT DISTINCT
            a.applicant_id,
            CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as full_name,
            a.age,
            a.gender,
            a.civil_status,
            addr.barangay,
            GROUP_CONCAT(DISTINCT b.benefit_name ORDER BY b.benefit_name SEPARATOR ', ') as benefits_received,
            SUM(bd.amount) as total_amount_received,
            COUNT(bd.id) as number_of_benefits
        FROM applicants a
        INNER JOIN benefits_distribution bd ON a.applicant_id = bd.applicant_id
        INNER JOIN benefits b ON bd.benefit_id = b.id
        LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
        WHERE MONTH(bd.distribution_date) = :month 
        AND YEAR(bd.distribution_date) = :year
    ";

    $benefitedParams = $params;

    // Add benefit filter for benefited seniors
    if ($benefit_option === 'specific' && $benefit_id && $benefit_id !== 'null' && $benefit_id !== '' && $benefit_id !== 'undefined') {
        $benefitedSeniorsQuery .= " AND bd.benefit_id = :benefit_id";
    } elseif ($benefit_option === 'multiple' && is_array($benefit_ids) && count($benefit_ids) > 0) {
        $placeholders = [];
        foreach ($benefit_ids as $index => $id) {
            $placeholder = ":benefit_id_" . $index;
            $placeholders[] = $placeholder;
            $benefitedParams[$placeholder] = $id;
        }
        $benefitedSeniorsQuery .= " AND bd.benefit_id IN (" . implode(',', $placeholders) . ")";
    }

    $benefitedSeniorsQuery .= " GROUP BY a.applicant_id ORDER BY full_name";

    $benefitedSeniorsStmt = $pdo->prepare($benefitedSeniorsQuery);
    $benefitedSeniorsStmt->execute($benefitedParams);
    $benefitedSeniors = $benefitedSeniorsStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['benefited_seniors'] = $benefitedSeniors;

    // ==================== PART 4: Get summary statistics ====================
    $summaryQuery = "
        SELECT 
            COUNT(DISTINCT bd.applicant_id) as total_beneficiaries,
            COUNT(bd.id) as total_benefits,
            COALESCE(SUM(bd.amount), 0) as total_amount,
            COALESCE(AVG(bd.amount), 0) as average_amount
        FROM benefits_distribution bd
        WHERE MONTH(bd.distribution_date) = :month 
        AND YEAR(bd.distribution_date) = :year
    ";

    $summaryParams = $params;

    // Add benefit filter for summary
    if ($benefit_option === 'specific' && $benefit_id && $benefit_id !== 'null' && $benefit_id !== '' && $benefit_id !== 'undefined') {
        $summaryQuery .= " AND bd.benefit_id = :benefit_id";
    } elseif ($benefit_option === 'multiple' && is_array($benefit_ids) && count($benefit_ids) > 0) {
        $placeholders = [];
        foreach ($benefit_ids as $index => $id) {
            $placeholder = ":benefit_id_" . $index;
            $placeholders[] = $placeholder;
            $summaryParams[$placeholder] = $id;
        }
        $summaryQuery .= " AND bd.benefit_id IN (" . implode(',', $placeholders) . ")";
    }

    $summaryStmt = $pdo->prepare($summaryQuery);
    $summaryStmt->execute($summaryParams);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // Convert numeric values to proper types
    if ($summary) {
        $summary['total_beneficiaries'] = (int)$summary['total_beneficiaries'];
        $summary['total_benefits'] = (int)$summary['total_benefits'];
        $summary['total_amount'] = (float)$summary['total_amount'];
        $summary['average_amount'] = (float)$summary['average_amount'];
    } else {
        $summary = [
            'total_beneficiaries' => 0,
            'total_benefits' => 0,
            'total_amount' => 0.00,
            'average_amount' => 0.00
        ];
    }

    // Add total seniors count
    $summary['total_seniors'] = count($allSeniors);
    $summary['benefited_seniors_count'] = count($benefitedSeniors);
    $summary['non_benefited_seniors_count'] = count($allSeniors) - count($benefitedSeniors);

    $response['data']['summary'] = $summary;

    // ==================== PART 5: Get benefit type breakdown ====================
    if ($benefit_option === 'all') {
        $benefitTypeQuery = "
            SELECT 
                b.id,
                b.benefit_name,
                COUNT(DISTINCT bd.applicant_id) as recipient_count,
                COALESCE(SUM(bd.amount), 0) as total_amount,
                COALESCE(AVG(bd.amount), 0) as average_amount
            FROM benefits_distribution bd
            INNER JOIN benefits b ON bd.benefit_id = b.id
            WHERE MONTH(bd.distribution_date) = :month 
            AND YEAR(bd.distribution_date) = :year
            GROUP BY b.id, b.benefit_name
            ORDER BY b.benefit_name
        ";

        $benefitTypeStmt = $pdo->prepare($benefitTypeQuery);
        $benefitTypeStmt->execute($params);
        $benefitTypeBreakdown = $benefitTypeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert numeric values
        foreach ($benefitTypeBreakdown as &$item) {
            $item['recipient_count'] = (int)$item['recipient_count'];
            $item['total_amount'] = (float)$item['total_amount'];
            $item['average_amount'] = (float)$item['average_amount'];
        }

        $response['data']['benefit_breakdown'] = $benefitTypeBreakdown;
    } else {
        $response['data']['benefit_breakdown'] = [];
    }

    // ==================== PART 6: Get barangay breakdown ====================
    $barangayQuery = "
        SELECT 
            addr.barangay,
            COUNT(DISTINCT a.applicant_id) as beneficiary_count,
            COALESCE(SUM(bd.amount), 0) as total_amount
        FROM applicants a
        LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
        LEFT JOIN benefits_distribution bd ON a.applicant_id = bd.applicant_id 
            AND MONTH(bd.distribution_date) = :month 
            AND YEAR(bd.distribution_date) = :year
    ";

    $barangayParams = $params;

    // Add benefit filter for barangay breakdown
    if ($benefit_option === 'specific' && $benefit_id && $benefit_id !== 'null' && $benefit_id !== '' && $benefit_id !== 'undefined') {
        $barangayQuery .= " AND bd.benefit_id = :benefit_id";
    } elseif ($benefit_option === 'multiple' && is_array($benefit_ids) && count($benefit_ids) > 0) {
        $placeholders = [];
        foreach ($benefit_ids as $index => $id) {
            $placeholder = ":benefit_id_" . $index;
            $placeholders[] = $placeholder;
            $barangayParams[$placeholder] = $id;
        }
        $barangayQuery .= " AND bd.benefit_id IN (" . implode(',', $placeholders) . ")";
    }

    $barangayQuery .= " GROUP BY addr.barangay ORDER BY total_amount DESC";

    $barangayStmt = $pdo->prepare($barangayQuery);
    $barangayStmt->execute($barangayParams);
    $barangay_breakdown = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['barangay_breakdown'] = $barangay_breakdown;

    // ==================== PART 7: Get status breakdown ====================
    $statusBreakdownQuery = "
        SELECT 
            a.status,
            COUNT(DISTINCT a.applicant_id) as senior_count,
            COUNT(DISTINCT CASE WHEN bd.applicant_id IS NOT NULL THEN a.applicant_id END) as benefited_count
        FROM applicants a
        LEFT JOIN benefits_distribution bd ON a.applicant_id = bd.applicant_id 
            AND MONTH(bd.distribution_date) = :month 
            AND YEAR(bd.distribution_date) = :year
    ";

    $statusParams = $params;

    // Add benefit filter for status breakdown
    if ($benefit_option === 'specific' && $benefit_id && $benefit_id !== 'null' && $benefit_id !== '' && $benefit_id !== 'undefined') {
        $statusBreakdownQuery .= " AND bd.benefit_id = :benefit_id";
    } elseif ($benefit_option === 'multiple' && is_array($benefit_ids) && count($benefit_ids) > 0) {
        $placeholders = [];
        foreach ($benefit_ids as $index => $id) {
            $placeholder = ":benefit_id_" . $index;
            $placeholders[] = $placeholder;
            $statusParams[$placeholder] = $id;
        }
        $statusBreakdownQuery .= " AND bd.benefit_id IN (" . implode(',', $placeholders) . ")";
    }

    $statusBreakdownQuery .= " GROUP BY a.status ORDER BY a.status";

    $statusStmt = $pdo->prepare($statusBreakdownQuery);
    $statusStmt->execute($statusParams);
    $statusBreakdown = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['data']['status_breakdown'] = $statusBreakdown;

    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Database error in fetch_monthly_benefits: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'error_details' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in fetch_monthly_benefits: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
