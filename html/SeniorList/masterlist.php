<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database configuration
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Fetch current user data - ADD THIS
$user_id = $_SESSION['user_id'] ?? 0;
$user_data = [];

if ($user_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        $user_data = [];
    }
}

// Prepare full name - ADD THIS
$full_name = '';
if (!empty($user_data['firstname']) && !empty($user_data['lastname'])) {
    $full_name = $user_data['firstname'] . ' ' . $user_data['lastname'];
    if (!empty($user_data['middlename'])) {
        $full_name = $user_data['firstname'] . ' ' . $user_data['middlename'] . ' ' . $user_data['lastname'];
    }
}

// Get profile photo URL - ADD THIS
$profile_photo_url = '';
if (!empty($user_data['profile_photo'])) {
    $profile_photo_url = '../../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar if no profile photo - ADD THIS
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}

// Initialize variables
// Handle barangays as array (from checkboxes) or comma-separated string
$filtered_barangays = [];
if (isset($_GET['barangays'])) {
    if (is_array($_GET['barangays'])) {
        // Received as array from checkboxes
        $filtered_barangays = array_filter($_GET['barangays']);
    } elseif (is_string($_GET['barangays']) && !empty($_GET['barangays'])) {
        // Received as comma-separated string
        $filtered_barangays = explode(',', $_GET['barangays']);
    }
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50; // Number of records per page

$seniors_data = [];
$total_records = 0;
$total_pages = 1;

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build WHERE clause for filtering
    $where_conditions = [];
    $params = [];

    // Filter by barangay
    if (!empty($filtered_barangays)) {
        $barangay_placeholders = implode(',', array_fill(0, count($filtered_barangays), '?'));
        $where_conditions[] = "ad.barangay IN ($barangay_placeholders)";
        $params = array_merge($params, $filtered_barangays);
    }

    // Filter by search term
    if (!empty($search_term)) {
        $where_conditions[] = "(CONCAT(a.last_name, ' ', a.first_name, ' ', COALESCE(a.middle_name, '')) LIKE ? 
                               OR a.control_number LIKE ? 
                               OR ar.id_number LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total 
                  FROM applicants a
                  LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
                  LEFT JOIN applicant_registration_details ar ON a.applicant_id = ar.applicant_id
                  $where_clause";

    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);

    // Calculate offset
    $offset = ($current_page - 1) * $per_page;

    // Fetch seniors data with all related information
    $sql = "
            SELECT 
                a.applicant_id,
                a.last_name,
                a.first_name,
                a.middle_name,
                a.suffix,
                CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name,
                a.birth_date,
                YEAR(CURDATE()) - YEAR(a.birth_date) - 
                (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(a.birth_date, '%m%d')) as age,
                a.gender,
                a.civil_status,
                a.religion,
                a.birth_place,
                a.educational_attainment,
                a.specialization_skills,
                a.community_involvement,
                a.problems_needs,
                a.remarks,
                a.status,
                a.validation,
                a.control_number,
                a.date_created,
                a.date_modified,
                
                -- Address information
                ad.house_no,
                ad.street,
                ad.barangay,
                ad.municipality,
                ad.province,
                
                -- Registration details
                ar.id_number,
                ar.local_control_number,
                ar.registration_status,
                
                -- Demographic information
                adem.is_ip_member,
                adem.ip_group,
                adem.tribal_affiliation,
                adem.dialect_spoken,
                
                -- Education background
                aeb.school_name,
                aeb.year_graduated,
                aeb.course_taken,
                
                -- Economic status
                es.is_pensioner,
                es.pension_amount,
                es.pension_source,
                es.pension_source_other,
                es.has_permanent_income,
                es.has_family_support,
                es.income_source,
                es.income_source_detail,
                es.support_type,
                es.support_cash,
                es.support_in_kind,
                es.assets_properties,
                es.living_residing_with,
                es.monthly_income,
                es.has_sss,
                es.has_gsis,
                es.has_pvao,
                es.has_insurance,
                es.has_tin,
                es.has_philhealth,
                -- Add the specific number fields
                a.living_arrangement,           -- FROM applicants table
                es.tin_number,                  -- FROM economic_status
                es.philhealth_number,           -- FROM economic_status
                es.gsis_number,                 -- FROM economic_status
                es.sss_number,                  -- FROM economic_status
                
                -- Health condition
                hc.has_existing_illness,
                hc.illness_details,
                hc.hospitalized_last6mos,
                hc.has_disability
                
            FROM applicants a
            LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
            LEFT JOIN applicant_registration_details ar ON a.applicant_id = ar.applicant_id
            LEFT JOIN applicant_demographics adem ON a.applicant_id = adem.applicant_id
            LEFT JOIN applicant_educational_background aeb ON a.applicant_id = aeb.applicant_id
            LEFT JOIN economic_status es ON a.applicant_id = es.applicant_id
            LEFT JOIN health_condition hc ON a.applicant_id = hc.applicant_id
            $where_clause
            ORDER BY a.last_name, a.first_name
            LIMIT ? OFFSET ?
        ";

    $stmt = $pdo->prepare($sql);

    // Combine all parameters: filter params + limit + offset
    $all_params = $params;

    // Add limit and offset as separate parameters
    $paramIndex = 1;
    foreach ($all_params as $value) {
        $stmt->bindValue($paramIndex, $value);
        $paramIndex++;
    }

    // Bind limit and offset as integers
    $stmt->bindValue($paramIndex, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $seniors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch benefits for all seniors to optimize queries
    $senior_ids = array_column($seniors, 'applicant_id');
    if (!empty($senior_ids)) {
        $placeholders = implode(',', array_fill(0, count($senior_ids), '?'));

        // Fetch benefits from senior_benefits_view
        $benefits_sql = "
            SELECT applicant_id, program_type, service_category 
            FROM senior_benefits_view 
            WHERE applicant_id IN ($placeholders)
        ";
        $benefits_stmt = $pdo->prepare($benefits_sql);
        $benefits_stmt->execute($senior_ids);
        $all_benefits = $benefits_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize benefits by senior ID
        $benefits_by_senior = [];
        foreach ($all_benefits as $benefit) {
            $senior_id = $benefit['applicant_id'];
            if (!isset($benefits_by_senior[$senior_id])) {
                $benefits_by_senior[$senior_id] = [];
            }
            $benefits_by_senior[$senior_id][] = $benefit;
        }
    }

    // Process each senior's data
    foreach ($seniors as $senior) {
        $senior_id = $senior['applicant_id'];

        // Extract date parts
        $birth_date = $senior['birth_date'];
        $birth_year = $birth_date && $birth_date != '0000-00-00' ? date('Y', strtotime($birth_date)) : '';
        $birth_month = $birth_date && $birth_date != '0000-00-00' ? date('F', strtotime($birth_date)) : '';
        $birth_day = $birth_date && $birth_date != '0000-00-00' ? date('d', strtotime($birth_date)) : '';

        // Get benefits for this senior
        $senior_benefits = $benefits_by_senior[$senior_id] ?? [];

        // Helper function to check benefits
        $hasBenefit = function ($program_type, $service_category) use ($senior_benefits) {
            foreach ($senior_benefits as $benefit) {
                if ($benefit['program_type'] == $program_type && $benefit['service_category'] == $service_category) {
                    return '✓';
                }
            }
            return '';
        };

        // Categorize benefits
        $national_aics = [
            'Food' => $hasBenefit('DSWD', 'AICS_Food'),
            'Burial' => $hasBenefit('DSWD', 'AICS_Burial'),
            'Medical' => $hasBenefit('DSWD', 'AICS_Medical'),
            'Transpo' => $hasBenefit('DSWD', 'AICS_Transpo')
        ];

        $national_other = [
            'SocPen' => $hasBenefit('DSWD', 'SocPen'),
            'Pantawid' => $hasBenefit('DSWD', 'Pantawid'),
            'Livelihood' => $hasBenefit('DSWD', 'Livelihood')
        ];

        $local_aics = [
            'Food' => $hasBenefit('Local', 'AICS_Food'),
            'Burial' => $hasBenefit('Local', 'AICS_Burial'),
            'Medical' => $hasBenefit('Local', 'AICS_Medical'),
            'Transpo' => $hasBenefit('Local', 'AICS_Transpo')
        ];

        $local_other = [
            'SocPen' => $hasBenefit('Local', 'SocPen'),
            'Livelihood' => $hasBenefit('Local', 'Livelihood')
        ];

        // Other pension status
        $other_pension = '';
        if ($senior['is_pensioner'] && $senior['pension_source'] == 'Others') {
            $other_pension = 'Yes';
        } else {
            $other_pension = 'No';
        }

        // Monthly income formatting
        $monthly_income = $senior['monthly_income'] ? '₱' . number_format($senior['monthly_income'], 2) : 'Not specified';

        // Store processed data
        $seniors_data[] = [
            'basic' => $senior,
            'birth_parts' => [
                'year' => $birth_year,
                'month' => $birth_month,
                'day' => $birth_day
            ],
            'benefits' => [
                'national_aics' => $national_aics,
                'national_other' => $national_other,
                'local_aics' => $local_aics,
                'local_other' => $local_other
            ],
            'other_pension' => $other_pension,
            'monthly_income' => $monthly_income
        ];
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch barangays for filter
$barangays = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND TRIM(barangay) != '' ORDER BY barangay");
    $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silently fail - filter won't work but page will still load
}

// Helper functions
function formatYesNo($value)
{
    if ($value == 1 || $value === true || $value === '1') return 'Yes';
    if ($value == 0 || $value === false || $value === '0') return 'No';
    return '';
}

function formatEmpty($value)
{
    return (!empty($value) && $value !== '') ? htmlspecialchars($value) : '';
}

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['export_all']) && $_GET['export_all'] === 'true') {
    // Build the same query as before but without LIMIT
    $where_conditions = [];
    $params = [];

    // Filter by barangay
    if (!empty($filtered_barangays)) {
        $barangay_placeholders = implode(',', array_fill(0, count($filtered_barangays), '?'));
        $where_conditions[] = "ad.barangay IN ($barangay_placeholders)";
        $params = array_merge($params, $filtered_barangays);
    }
    // Clean barangay names for display
    $cleaned_barangays = [];
    foreach ($barangays as $barangay) {
        // Remove any special characters that could cause issues
        $cleaned = preg_replace('/[\\\\\/\?\*\[\]]/', '', $barangay);
        $cleaned = trim($cleaned);
        if (!empty($cleaned)) {
            $cleaned_barangays[] = $cleaned;
        }
    }
    $barangays = array_unique($cleaned_barangays);
    // Filter by search term
    if (!empty($search_term)) {
        $where_conditions[] = "(CONCAT(a.last_name, ' ', a.first_name, ' ', COALESCE(a.middle_name, '')) LIKE ? 
                               OR a.control_number LIKE ? 
                               OR ar.id_number LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Modified SQL without LIMIT for export
    $export_sql = "
        SELECT 
            a.applicant_id,
            a.last_name,
            a.first_name,
            a.middle_name,
            a.suffix,
            CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name,
            a.birth_date,
            YEAR(CURDATE()) - YEAR(a.birth_date) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(a.birth_date, '%m%d')) as age,
            a.gender,
            a.civil_status,
            a.religion,
            a.birth_place,
            a.educational_attainment,
            a.specialization_skills,
            a.community_involvement,
            a.problems_needs,
            a.remarks,
            a.status,
            a.validation,
            a.control_number,
            a.date_created,
            a.date_modified,
            
            -- Address information
            ad.house_no,
            ad.street,
            ad.barangay,
            ad.municipality,
            ad.province,
            
            -- Registration details
            ar.id_number,
            ar.local_control_number,
            ar.registration_status,
            
            -- Demographic information
            adem.is_ip_member,
            adem.ip_group,
            adem.tribal_affiliation,
            adem.dialect_spoken,
            
            -- Education background
            aeb.school_name,
            aeb.year_graduated,
            aeb.course_taken,
            
            -- Economic status
            es.is_pensioner,
            es.pension_amount,
            es.pension_source,
            es.pension_source_other,
            es.has_permanent_income,
            es.has_family_support,
            es.income_source,
            es.income_source_detail,
            es.support_type,
            es.support_cash,
            es.support_in_kind,
            es.assets_properties,
            es.living_residing_with,
            es.monthly_income,
            es.has_sss,
            es.has_gsis,
            es.has_pvao,
            es.has_insurance,
            es.has_tin,
            es.has_philhealth,
            es.tin_number,
            es.philhealth_number,
            es.gsis_number,
            es.sss_number,
            a.living_arrangement,
            
            -- Health condition
            hc.has_existing_illness,
            hc.illness_details,
            hc.hospitalized_last6mos,
            hc.has_disability
            
        FROM applicants a
        LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
        LEFT JOIN applicant_registration_details ar ON a.applicant_id = ar.applicant_id
        LEFT JOIN applicant_demographics adem ON a.applicant_id = adem.applicant_id
        LEFT JOIN applicant_educational_background aeb ON a.applicant_id = aeb.applicant_id
        LEFT JOIN economic_status es ON a.applicant_id = es.applicant_id
        LEFT JOIN health_condition hc ON a.applicant_id = hc.applicant_id
        $where_clause
        ORDER BY ad.barangay, a.last_name, a.first_name
    ";

    try {
        $stmt = $pdo->prepare($export_sql);
        $stmt->execute($params);
        $all_seniors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch benefits for all seniors
        $senior_ids = array_column($all_seniors, 'applicant_id');
        $all_benefits = [];

        if (!empty($senior_ids)) {
            $placeholders = implode(',', array_fill(0, count($senior_ids), '?'));
            $benefits_sql = "
                SELECT applicant_id, program_type, service_category 
                FROM senior_benefits_view 
                WHERE applicant_id IN ($placeholders)
            ";
            $benefits_stmt = $pdo->prepare($benefits_sql);
            $benefits_stmt->execute($senior_ids);
            $all_benefits = $benefits_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Organize benefits by senior ID
        $benefits_by_senior = [];
        foreach ($all_benefits as $benefit) {
            $senior_id = $benefit['applicant_id'];
            if (!isset($benefits_by_senior[$senior_id])) {
                $benefits_by_senior[$senior_id] = [];
            }
            $benefits_by_senior[$senior_id][] = $benefit;
        }

        // Process seniors data
        $seniors_data = [];
        foreach ($all_seniors as $senior) {
            $senior_id = $senior['applicant_id'];

            // Extract date parts
            $birth_date = $senior['birth_date'];
            $birth_year = $birth_date && $birth_date != '0000-00-00' ? date('Y', strtotime($birth_date)) : '';
            $birth_month = $birth_date && $birth_date != '0000-00-00' ? date('F', strtotime($birth_date)) : '';
            $birth_day = $birth_date && $birth_date != '0000-00-00' ? date('d', strtotime($birth_date)) : '';

            // Get benefits
            $senior_benefits = $benefits_by_senior[$senior_id] ?? [];

            $hasBenefit = function ($program_type, $service_category) use ($senior_benefits) {
                foreach ($senior_benefits as $benefit) {
                    if ($benefit['program_type'] == $program_type && $benefit['service_category'] == $service_category) {
                        return '✓';
                    }
                }
                return '';
            };

            // Categorize benefits
            $national_aics = [
                'Food' => $hasBenefit('DSWD', 'AICS_Food'),
                'Burial' => $hasBenefit('DSWD', 'AICS_Burial'),
                'Medical' => $hasBenefit('DSWD', 'AICS_Medical'),
                'Transpo' => $hasBenefit('DSWD', 'AICS_Transpo')
            ];

            $national_other = [
                'SocPen' => $hasBenefit('DSWD', 'SocPen'),
                'Pantawid' => $hasBenefit('DSWD', 'Pantawid'),
                'Livelihood' => $hasBenefit('DSWD', 'Livelihood')
            ];

            $local_aics = [
                'Food' => $hasBenefit('Local', 'AICS_Food'),
                'Burial' => $hasBenefit('Local', 'AICS_Burial'),
                'Medical' => $hasBenefit('Local', 'AICS_Medical'),
                'Transpo' => $hasBenefit('Local', 'AICS_Transpo')
            ];

            $local_other = [
                'SocPen' => $hasBenefit('Local', 'SocPen'),
                'Livelihood' => $hasBenefit('Local', 'Livelihood')
            ];

            // Other pension status
            $other_pension = '';
            if ($senior['is_pensioner'] && $senior['pension_source'] == 'Others') {
                $other_pension = 'Yes';
            }

            // Monthly income formatting
            $monthly_income = $senior['monthly_income'] ? '₱' . number_format($senior['monthly_income'], 2) : 'Not specified';

            // Store processed data
            $seniors_data[] = [
                'basic' => $senior,
                'birth_parts' => [
                    'year' => $birth_year,
                    'month' => $birth_month,
                    'day' => $birth_day
                ],
                'benefits' => [
                    'national_aics' => $national_aics,
                    'national_other' => $national_other,
                    'local_aics' => $local_aics,
                    'local_other' => $local_other
                ],
                'other_pension' => $other_pension,
                'monthly_income' => $monthly_income
            ];
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $seniors_data,
            'total' => count($seniors_data)
        ]);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master List - Demographic Data</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <style>
        /* Enhanced logo styling for page display */
        .highlighted-logo {
            filter: 
                brightness(1.3)      /* Make brighter */
                contrast(1.2)        /* Increase contrast */
                saturate(1.5)        /* Make colors more vibrant */
                drop-shadow(0 0 8px #3b82f6)  /* Blue glow */
                drop-shadow(0 0 12px rgba(59, 130, 246, 0.7));
            
            /* Optional border */
            border: 3px solid rgba(59, 130, 246, 0.4);
            border-radius: 12px;
            
            /* Inner glow effect */
            box-shadow: 
                inset 0 0 10px rgba(255, 255, 255, 0.6),
                0 0 20px rgba(59, 130, 246, 0.5);
            
            /* Animation for extra attention */
            animation: pulse-glow 2s infinite alternate;
        }
        
        @keyframes pulse-glow {
            from {
                box-shadow: 
                    inset 0 0 10px rgba(255, 255, 255, 0.6),
                    0 0 15px rgba(59, 130, 246, 0.5);
            }
            to {
                box-shadow: 
                    inset 0 0 15px rgba(255, 255, 255, 0.8),
                    0 0 25px rgba(59, 130, 246, 0.8);
            }
        }
    </style>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .demographic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .demographic-table th,
        .demographic-table td {
            border: 1px solid #ddd;
            padding: 2px 3px;
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
        }

        /* .demographic-table th {
            background-color: #f3f4f6;
            font-weight: 600;
        } */

        .demographic-table .section-header {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: center;
        }

        /* .demographic-table .sub-header {
            background-color: #f9fafb;
            font-weight: 500;
        } */

        @media print {
            .no-print {
                display: none !important;
            }

            .demographic-table {
                font-size: 7pt;
            }

            .demographic-table th,
            .demographic-table td {
                padding: 1px 2px;
            }
        }

        .table-container {
            max-width: 100%;
            overflow-x: auto;
        }

        .filter-container {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .pagination-btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            background: white;
            cursor: pointer;
        }

        .pagination-btn:hover {
            background: #f3f4f6;
        }

        .pagination-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
            cursor: pointer;
        }

        .checkbox-label input {
            margin-right: 0.5rem;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased">
        <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex justify-start items-center">
                    <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                        aria-controls="drawer-navigation"
                        class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <svg aria-hidden="true" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Toggle sidebar</span>
                    </button>
                    <a href="#" class="flex items-center justify-between mr-4">
                        <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                            alt="MSWD LOGO" />
                        <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-8 h-8 rounded-full object-cover"
                            src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                            alt="user photo" />
                    </button>
                    <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
                        id="dropdown">
                        <div class="py-3 px-4">
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                <?php
                                if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                                    echo htmlspecialchars($_SESSION['fullname']);
                                } else if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                                    echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
                                } else {
                                    echo 'User';
                                }
                                ?>
                            </span>
                            <span class="block text-sm text-gray-900 truncate dark:text-white">
                                <?php
                                if (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
                                    echo htmlspecialchars($_SESSION['user_type']);
                                } else if (isset($_SESSION['role_name']) && !empty($_SESSION['role_name'])) {
                                    echo htmlspecialchars($_SESSION['role_name']);
                                } else {
                                    echo 'User Type';
                                }
                                ?>
                            </span>
                        </div>
                        <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
                            <li>
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/php/login/logout.php"
                                    class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
            aria-label="Sidenav" id="drawer-navigation">
            <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
                <ul class="space-y-2">
                    <li>
                        <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-user-plus w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Register</span>
                        </a>
                    </li>
                    <li>
                        <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                            class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            <i class="fas fa-list w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul id="dropdown-pages" class=" py-2 space-y-2">
                            <li>
                                <a href="#"
                                    class="flex items-center p-2 pl-11 w-full text-base text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                                    <i class="fas fa-check-circle mr-2 text-sm text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>Active List
                                </a>
                            </li>
                            <li>
                                <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                                </a>
                            </li>
                            <li>
                                <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Benefits</span>
                        </a>
                    </li>
                    <li>
                        <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="../archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-archive w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Archived</span>
                        </a>
                    </li>
                    <li>
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/settings/profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="p-4 md:ml-64 pt-20">
            <!-- Header with navigation -->
            <div class="w-full flex justify-between items-center mb-6 no-print">
                <a href="./activelist.php?session_context=<?php echo $ctx; ?>"
                    class="text-white flex flex-row items-center cursor-pointer bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to List
                </a>

                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-table mr-3"></i>Demographic Master List
                    </h1>
                </div>

                <div class="relative">
                    <button id="actionDropdownButton" data-dropdown-toggle="actionDropdown"
                        class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200"
                        type="button">
                        <i class="fas fa-ellipsis-v mr-2"></i>
                        Actions
                    </button>

                    <div id="actionDropdown"
                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                        <ul class=" text-sm text-gray-700 dark:text-gray-200">
                            <!-- <li>
                                <button onclick="window.print()"
                                    class="block px-4 py-2 w-full hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-print mr-2"></i>Print
                                </button>
                            </li> -->
                            <li>
                                <button onclick="exportToExcel()"
                                    class="block px-4 py-2 w-full cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-file-excel mr-2"></i>Export to Excel
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Header -->
            <!-- <div class="w-full flex justify-between items-center mb-6 no-print">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-table mr-3"></i>Demographic Master List
                </h1>
                <div class="flex items-center space-x-4">
                    <button onclick="window.print()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none dark:focus:ring-blue-800 transition duration-200">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="exportToExcel()"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg focus:ring-4 focus:ring-green-300 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none dark:focus:ring-green-800 transition duration-200">
                        <i class="fas fa-file-excel mr-2"></i>Export to Excel
                    </button>
                </div>
            </div> -->

            <!-- Filters -->
            <div class="filter-container no-print dark:text-white dark:bg-gray-800">
                <form method="GET" id="filterForm" action="masterlist.php">
                    <input type="hidden" name="session_context" value="<?php echo $ctx; ?>">
                    <input type="hidden" name="page" value="1" id="pageInput">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 ">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Search by Name or ID
                            </label>
                            <div class="relative">
                                <input type="text" name="search" id="searchInput"
                                    value="<?php echo htmlspecialchars($search_term); ?>"
                                    placeholder="Search by name or ID..."
                                    class="w-full p-2 pl-10 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Barangay Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Filter by Barangay
                            </label>
                            <div class="relative">
                                <button type="button" id="barangayDropdownButton" data-dropdown-toggle="barangayDropdown"
                                    class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-left flex justify-between items-center">
                                    <span id="selectedBarangaysText">
                                        <?php
                                        if (empty($filtered_barangays)) {
                                            echo 'All Barangays';
                                        } else {
                                            echo count($filtered_barangays) . ' selected';
                                        }
                                        ?>
                                    </span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>

                                <div id="barangayDropdown" class="z-10 hidden w-full bg-white rounded-lg shadow dark:bg-gray-700 max-h-60 overflow-y-auto">
                                    <div class="p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <h6 class="font-medium text-gray-900 dark:text-white">Select Barangays</h6>
                                            <button type="button" onclick="toggleAllBarangays()" class="text-sm text-blue-600 hover:underline">
                                                Toggle All
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2" id="barangayCheckboxContainer">
                                            <?php foreach ($barangays as $barangay): ?>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="barangays[]"
                                                        value="<?php echo htmlspecialchars($barangay); ?>"
                                                        class="barangay-checkbox auto-filter text-blue-600 bg-gray-200  rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-300"
                                                        <?php echo in_array($barangay, $filtered_barangays) ? 'checked' : ''; ?>>
                                                    <span class="text-sm"><?php echo htmlspecialchars($barangay); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-3 flex justify-between">
                                            <button type="button" onclick="clearBarangaySelection()"
                                                class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden submit button for pressing Enter in search -->
                    <button type="submit" style="display: none;"></button>
                </form>

                <!-- Selected Filters Display -->
                <?php if (!empty($filtered_barangays) || !empty($search_term)): ?>
                    <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($search_term)): ?>
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded text-xs">
                                    Search: "<?php echo htmlspecialchars($search_term); ?>"
                                    <button type="button" onclick="clearSearch()" class="ml-1 text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            <?php endif; ?>

                            <?php foreach ($filtered_barangays as $barangay): ?>
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 rounded text-xs">
                                    <?php echo htmlspecialchars($barangay); ?>
                                    <button type="button" onclick="removeBarangayFilter('<?php echo htmlspecialchars($barangay); ?>')" class="ml-1 text-green-600 hover:text-green-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            <?php endforeach; ?>

                            <?php if (!empty($filtered_barangays) || !empty($search_term)): ?>
                                <button type="button" onclick="clearAllFilters()" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                    Clear All
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-users text-blue-600 dark:text-blue-300"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Seniors</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo $total_records; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-300"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">
                                <?php echo $total_records; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-user-clock text-yellow-600 dark:text-yellow-300"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Validated</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">
                                N/A
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900">
                            <i class="fas fa-map-marker-alt text-purple-600 dark:text-purple-300"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Barangays</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo count($barangays); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demographic Table -->
            <div class="mb-6 dark:text-white dark:bg-gray-800">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <table class="demographic-table dark:bg-gray-800">
                        <thead class=" text-center text-gray-700  bg-gray-50 dark:bg-gray-800 dark:text-white">
                            <tr>
                                <th colspan="2" rowspan="4">Province</th>
                                <th colspan="2" rowspan="4">Municipality</th>
                                <th colspan="2" rowspan="4">Barangay</th>
                                <th colspan="4">NAME</th>
                                <th rowspan="4">DOUBLE ENTRY IDENTIFIER</th>
                                <th colspan="3">BIRTH DATE</th>
                                <th colspan="5">OTHER INFORMATION</th>
                                <th rowspan="4">STATUS</th>
                                <th colspan="14">Program and Services Received from DSWD</th>
                                <th colspan="4">Other Pensions</th>
                                <th rowspan="4">IP Group</th>
                                <th rowspan="4">Educational Attainment</th>
                                <th colspan="6">ID NUMBERS</th>
                                <th rowspan="4">Monthly Income</th>
                                <th colspan="2">Source of Income</th>
                                <th colspan="2">Assets & Properties</th>
                                <th colspan="2">Living/Residing with</th>
                                <th colspan="2">Specialization/Skills</th>
                                <th colspan="2">Involvement in Community Activities</th>
                                <th colspan="8">Problems/Needs Commonly Encountered</th>
                            </tr>
                            <tr class="sub-header">
                                <th rowspan="3">Last Name</th>
                                <th rowspan="3">Suffix</th>
                                <th rowspan="3">First Name</th>
                                <th rowspan="3">Middle Name</th>
                                <th rowspan="3">Year</th>
                                <th rowspan="3">Month</th>
                                <th rowspan="3">Day</th>
                                <th rowspan="3">Age</th>
                                <th rowspan="3">Sex</th>
                                <th rowspan="3">Place of Birth</th>
                                <th rowspan="3">Civil Status</th>
                                <th rowspan="3">Religion</th>
                                <th colspan="7">National</th>
                                <th colspan="7">Local</th>
                                <th rowspan="3">SSS</th>
                                <th rowspan="3">GSIS</th>
                                <th rowspan="3">PVAO</th>
                                <th rowspan="3">Insurance</th>
                                <th rowspan="3">OSCA</th>
                                <th rowspan="3">TIN</th>
                                <th rowspan="3">PHILHEALTH</th>
                                <th rowspan="3">GSIS</th>
                                <th rowspan="3">SSS</th>
                                <th rowspan="3">Others</th>
                                <th rowspan="3">Selection</th>
                                <th rowspan="3">Remark</th>
                                <th rowspan="3">Selection</th>
                                <th rowspan="3">Remark</th>
                                <th rowspan="3">Selection</th>
                                <th rowspan="3">Remark</th>
                                <th rowspan="3">Selection</th>
                                <th rowspan="3">Remark</th>
                                <th rowspan="3">Selection</th>
                                <th rowspan="3">Remark</th>
                                <th rowspan="3">Economic</th>
                                <th rowspan="3">Social Emotional</th>
                                <th colspan="2" rowspan="2">Health</th>
                                <th rowspan="3">Housing</th>
                                <th rowspan="3">Community Services</th>
                                <th rowspan="3">Identify Others Specific Needs</th>
                                <th rowspan="3">Remarks</th>
                            </tr>
                            <tr class="sub-header">
                                <th colspan="4">AICS</th>
                                <th rowspan="2">Socpen</th>
                                <th rowspan="2">Pantawid</th>
                                <th rowspan="2">Livelihood</th>
                                <th colspan="4">AICS</th>
                                <th colspan="2">Socpen</th>
                                <th rowspan="2">Livelihood</th>
                            </tr>
                            <tr class="sub-header">
                                <th>Food</th>
                                <th>Burial</th>
                                <th>Medical</th>
                                <th>Transport</th>
                                <th>Food</th>
                                <th>Burial</th>
                                <th>Medical</th>
                                <th>Transport</th>
                                <th>Prov</th>
                                <th>Munis</th>
                                <th>Illness</th>
                                <th>Disability</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($seniors_data)): ?>
                                <tr>
                                    <td colspan="71" class="text-center py-4 text-gray-500">
                                        No data found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($seniors_data as $senior):
                                    $basic = $senior['basic'];
                                    $birth_parts = $senior['birth_parts'];
                                    $benefits = $senior['benefits'];
                                ?>
                                    <tr>
                                        <!-- Province (2 columns) -->
                                        <td colspan="2"><?php echo formatEmpty($basic['province']); ?></td>

                                        <!-- Municipality (2 columns) -->
                                        <td colspan="2"><?php echo formatEmpty($basic['municipality']); ?></td>

                                        <!-- Barangay (2 columns) -->
                                        <td colspan="2"><?php echo formatEmpty($basic['barangay']); ?></td>

                                        <!-- NAME Columns (4 columns) -->
                                        <td><?php echo formatEmpty($basic['last_name']); ?></td>
                                        <td><?php echo formatEmpty($basic['suffix']); ?></td>
                                        <td><?php echo formatEmpty($basic['first_name']); ?></td>
                                        <td><?php echo formatEmpty($basic['middle_name']); ?></td>

                                        <!-- DOUBLE ENTRY IDENTIFIER (Full Name) -->
                                        <td><?php echo formatEmpty($basic['full_name']); ?></td>

                                        <!-- BIRTH DATE (3 columns) -->
                                        <td><?php echo $birth_parts['year']; ?></td>
                                        <td><?php echo $birth_parts['month']; ?></td>
                                        <td><?php echo $birth_parts['day']; ?></td>

                                        <!-- OTHER INFORMATION (5 columns) -->
                                        <td><?php echo $basic['age']; ?></td>
                                        <td><?php echo formatEmpty($basic['gender']); ?></td>
                                        <td><?php echo formatEmpty($basic['birth_place']); ?></td>
                                        <td><?php echo formatEmpty($basic['civil_status']); ?></td>
                                        <td><?php echo formatEmpty($basic['religion']); ?></td>

                                        <!-- STATUS -->
                                        <td><?php echo formatEmpty($basic['status']); ?></td>

                                        <!-- Program and Services Received from DSWD - NATIONAL (7 columns) -->
                                        <td><?php echo $benefits['national_aics']['Food']; ?></td>
                                        <td><?php echo $benefits['national_aics']['Burial']; ?></td>
                                        <td><?php echo $benefits['national_aics']['Medical']; ?></td>
                                        <td><?php echo $benefits['national_aics']['Transpo']; ?></td>
                                        <td><?php echo $benefits['national_other']['SocPen']; ?></td>
                                        <td><?php echo $benefits['national_other']['Pantawid']; ?></td>
                                        <td><?php echo $benefits['national_other']['Livelihood']; ?></td>

                                        <!-- Program and Services Received from DSWD - LOCAL (7 columns) -->
                                        <td><?php echo $benefits['local_aics']['Food']; ?></td>
                                        <td><?php echo $benefits['local_aics']['Burial']; ?></td>
                                        <td><?php echo $benefits['local_aics']['Medical']; ?></td>
                                        <td><?php echo $benefits['local_aics']['Transpo']; ?></td>
                                        <td><?php echo $benefits['local_other']['SocPen']; ?></td>
                                        <td><?php echo $benefits['local_other']['SocPen']; ?></td>
                                        <td><?php echo $benefits['local_other']['Livelihood']; ?></td>

                                        <!-- Other Pensions (4 columns) -->
                                        <td><?php echo formatYesNo($basic['has_sss']); ?></td>
                                        <td><?php echo formatYesNo($basic['has_gsis']); ?></td>
                                        <td><?php echo formatYesNo($basic['has_pvao']); ?></td>
                                        <td><?php echo formatYesNo($basic['has_insurance']); ?></td>

                                        <!-- IP Group -->
                                        <td><?php echo formatEmpty($basic['ip_group']); ?></td>

                                        <!-- Educational Attainment -->
                                        <td><?php echo formatEmpty($basic['educational_attainment']); ?></td>

                                        <!-- ID NUMBERS (6 columns) -->
                                        <!-- ID NUMBERS (6 columns) -->
                                        <td><?php echo formatEmpty($basic['id_number']); ?></td>
                                        <td><?php echo formatEmpty($basic['tin_number']); ?></td>
                                        <td><?php echo formatEmpty($basic['philhealth_number']); ?></td>
                                        <td><?php echo formatEmpty($basic['gsis_number']); ?></td>
                                        <td><?php echo formatEmpty($basic['sss_number']); ?></td>
                                        <td>
                                            <?php
                                            $other_pension = '';
                                            if ($basic['is_pensioner'] && $basic['pension_source'] == 'Others') {
                                                $other_pension = 'Yes';
                                            }
                                            echo $other_pension ?: 'No';
                                            ?>
                                        </td>

                                        <!-- Monthly Income -->
                                        <td><?php echo $senior['monthly_income']; ?></td>

                                        <!-- Source of Income (2 columns) -->
                                        <td><?php echo formatEmpty($basic['income_source']); ?></td>
                                        <td><?php echo formatEmpty($basic['income_source_detail']); ?></td>

                                        <!-- Assets & Properties (2 columns) -->
                                        <td><?php echo formatEmpty($basic['assets_properties']); ?></td>
                                        <td></td>

                                        <!-- Living/Residing with (2 columns) -->
                                        <td><?php echo formatEmpty($basic['living_arrangement']); ?></td>
                                        <td></td>

                                        <!-- Specialization/Skills (2 columns) -->
                                        <td><?php echo formatEmpty($basic['specialization_skills']); ?></td>
                                        <td></td>

                                        <!-- Involvement in Community Activities (2 columns) -->
                                        <td><?php echo formatEmpty($basic['community_involvement']); ?></td>
                                        <td></td>

                                        <!-- Problems/Needs Commonly Encountered (8 columns) -->
                                        <td><?php echo formatEmpty($basic['problems_needs']); ?></td>
                                        <td></td>
                                        <td><?php echo formatYesNo($basic['has_existing_illness']); ?></td>
                                        <td><?php echo formatYesNo($basic['has_disability']); ?></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td><?php echo formatEmpty($basic['remarks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center space-x-2 mb-6 no-print">
                    <button onclick="changePage(1)"
                        class="pagination-btn <?php echo $current_page == 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $current_page == 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button onclick="changePage(<?php echo max(1, $current_page - 1); ?>)"
                        class="pagination-btn <?php echo $current_page == 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $current_page == 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <?php
                    // Show page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <button onclick="changePage(<?php echo $i; ?>)"
                            class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>

                    <button onclick="changePage(<?php echo min($total_pages, $current_page + 1); ?>)"
                        class="pagination-btn <?php echo $current_page == $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button onclick="changePage(<?php echo $total_pages; ?>)"
                        class="pagination-btn <?php echo $current_page == $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>

                <div class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6 no-print">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> |
                    Showing <?php echo count($seniors_data); ?> of <?php echo $total_records; ?> records
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
        // ---------- THEME INITIALIZATION (MUST BE OUTSIDE DOMContentLoaded) ----------
        // Initialize theme from localStorage or system preference
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            let theme = 'light';
            if (savedTheme) {
                theme = savedTheme;
            } else if (systemPrefersDark) {
                theme = 'dark';
            }

            setTheme(theme);
        }

        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }

        // Listen for theme changes from other pages
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const theme = e.newValue;
                setTheme(theme);
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Initialize theme on page load (BEFORE DOMContentLoaded)
        initTheme();
    </script>
    <!-- Add this after your PHP code, before the closing </body> tag -->
    <script>
        // Pass PHP data to JavaScript
        window.seniorsData = <?php echo json_encode($seniors_data); ?>;
        window.allBarangays = <?php echo json_encode($barangays); ?>;
    </script>
    <script>
        // Filter functions
        function applyFilters() {
            document.getElementById('pageInput').value = 1;
            document.getElementById('filterForm').submit();
        }

        function toggleAllBarangays() {
            const checkboxes = document.querySelectorAll('.barangay-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            updateSelectedBarangaysText();
            autoFilter();
        }

        function clearBarangaySelection() {
            document.querySelectorAll('.barangay-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateSelectedBarangaysText();
            autoFilter();
        }

        function removeBarangayFilter(barangay) {
            document.querySelectorAll('.barangay-checkbox').forEach(cb => {
                if (cb.value === barangay) {
                    cb.checked = false;
                }
            });
            updateSelectedBarangaysText();
            autoFilter();
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('pageInput').value = 1;
            document.getElementById('filterForm').submit();
        }

        function clearAllFilters() {
            document.getElementById('searchInput').value = '';
            clearBarangaySelection();
        }

        function changePage(page) {
            document.getElementById('pageInput').value = page;
            document.getElementById('filterForm').submit();
        }

        // Update selected barangays text
        function updateSelectedBarangaysText() {
            const checkboxes = document.querySelectorAll('.barangay-checkbox:checked');
            const textElement = document.getElementById('selectedBarangaysText');

            if (checkboxes.length === 0) {
                textElement.textContent = 'All Barangays';
            } else {
                textElement.textContent = `${checkboxes.length} selected`;
            }
        }

        // Auto-filter function with debouncing
        function autoFilter() {
            clearTimeout(window.autoFilterTimeout);
            window.autoFilterTimeout = setTimeout(() => {
                document.getElementById('pageInput').value = 1;
                document.getElementById('filterForm').submit();
            }, 500);
        }

        // Helper function to add multi-level headers to a worksheet
        function addHeadersToWorksheet(worksheet) {
            // Add title rows
            worksheet.mergeCells('A1:BJ1');
            const titleCell = worksheet.getCell('A1');
            titleCell.value = 'SENIOR CITIZEN DATA BANKING';
            titleCell.font = {
                bold: true,
                size: 17,
                name: 'Calibri'
            };
            titleCell.alignment = {
                horizontal: 'center'
            };

            // Empty row
            worksheet.addRow([]);

            // DSWD Information
            worksheet.mergeCells('A3:BJ3');
            const dswdCell = worksheet.getCell('A3');
            dswdCell.value = 'Department of Social Welfare and Development';
            dswdCell.font = {
                bold: true,
                size: 12,
                name: 'Calibri'
            };
            dswdCell.alignment = {
                horizontal: 'center'
            };

            // Field Office
            worksheet.mergeCells('A4:BJ4');
            const fieldOfficeCell = worksheet.getCell('A4');
            fieldOfficeCell.value = 'Field Office: IV - MIMAROPA';
            fieldOfficeCell.font = {
                size: 10,
                name: 'Calibri'
            };
            fieldOfficeCell.alignment = {
                horizontal: 'center'
            };

            // Province
            worksheet.mergeCells('A5:BJ5');
            const provinceCell = worksheet.getCell('A5');
            provinceCell.value = 'Province of Occidental Mindoro';
            provinceCell.font = {
                size: 10,
                name: 'Calibri'
            };
            provinceCell.alignment = {
                horizontal: 'center'
            };

            // Empty row
            worksheet.addRow([]);

            // Municipality
            worksheet.mergeCells('A7:BJ7');
            const municipalityCell = worksheet.getCell('A7');
            municipalityCell.value = 'MUNICIPALITY OF PALUAN';
            municipalityCell.font = {
                bold: true,
                size: 10,
                name: 'Calibri'
            };
            municipalityCell.alignment = {
                horizontal: 'center'
            };

            // Empty rows for spacing
            worksheet.addRow([]);
            worksheet.addRow([]);

            // Create multi-level headers that match the HTML table exactly
            // Row 9: Main headers with colspan
            const mainHeaders = [
                // Province (2 columns colspan)
                'Province', '',
                // Municipality (2 columns colspan)
                'Municipality', '',
                // Barangay (2 columns colspan)
                'Barangay', '',
                // NAME (4 columns colspan)
                'NAME', '', '', '',
                // DOUBLE ENTRY IDENTIFIER (1 column)
                'DOUBLE ENTRY IDENTIFIER',
                // BIRTH DATE (3 columns colspan)
                'BIRTH DATE', '', '',
                // OTHER INFORMATION (5 columns colspan)
                'OTHER INFORMATION', '', '', '', '',
                // STATUS (1 column)
                'STATUS',
                // Program and Services Received from DSWD (14 columns colspan)
                'Program and Services Received from DSWD', '', '', '', '', '', '', '', '', '', '', '', '', '',
                // Other Pensions (4 columns colspan)
                'Other Pensions', '', '', '',
                // IP Group (1 column)
                'IP Group',
                // Educational Attainment (1 column)
                'Educational Attainment',
                // ID NUMBERS (6 columns colspan)
                'ID NUMBERS', '', '', '', '', '',
                // Monthly Income (1 column)
                'Monthly Income',
                // Source of Income (2 columns colspan)
                'Source of Income', '',
                // Assets & Properties (2 columns colspan)
                'Assets & Properties', '',
                // Living/Residing with (2 columns colspan)
                'Living/Residing with', '',
                // Specialization/Skills (2 columns colspan)
                'Specialization/Skills', '',
                // Involvement in Community Activities (2 columns colspan)
                'Involvement in Community Activities', '',
                // Problems/Needs Commonly Encountered (8 columns colspan)
                'Problems/Needs Commonly Encountered', '', '', '', '', '', '', ''
            ];

            const mainHeaderRow = worksheet.addRow(mainHeaders);
            mainHeaderRow.height = 30;

            // Style main header row
            mainHeaderRow.eachCell((cell, colNumber) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: {
                        argb: 'FFE2EFDA'
                    }
                };
                cell.font = {
                    bold: true,
                    color: {
                        argb: 'FF000000'
                    },
                    name: 'Calibri',
                    size: 10
                };
                cell.alignment = {
                    vertical: 'middle',
                    horizontal: 'center',
                    wrapText: true
                };
                cell.border = {
                    top: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    bottom: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    left: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    right: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    }
                };
            });

            // Row 10: Sub-headers
            const subHeaders = [
                // Province (empty - already covered by colspan)
                '', '',
                // Municipality (empty)
                '', '',
                // Barangay (empty)
                '', '',
                // NAME sub-headers
                'Last Name', 'Suffix', 'First Name', 'Middle Name',
                // DOUBLE ENTRY IDENTIFIER (empty - rowspan 4)
                '',
                // BIRTH DATE sub-headers
                'Year', 'Month', 'Day',
                // OTHER INFORMATION sub-headers
                'Age', 'Sex', 'Place of Birth', 'Civil Status', 'Religion',
                // STATUS (empty - rowspan 4)
                '',
                // Program and Services - National (7 columns)
                'National', '', '', '', '', '', '',
                // Program and Services - Local (7 columns)
                'Local', '', '', '', '', '', '',
                // Other Pensions sub-headers
                'SSS', 'GSIS', 'PVAO', 'Insurance',
                // IP Group (empty - rowspan 4)
                '',
                // Educational Attainment (empty - rowspan 4)
                '',
                // ID NUMBERS sub-headers
                'OSCA', 'TIN', 'PHILHEALTH', 'GSIS', 'SSS', 'Others',
                // Monthly Income (empty - rowspan 4)
                '',
                // Source of Income sub-headers
                'Selection', 'Remark',
                // Assets & Properties sub-headers
                'Selection', 'Remark',
                // Living/Residing with sub-headers
                'Selection', 'Remark',
                // Specialization/Skills sub-headers
                'Selection', 'Remark',
                // Involvement in Community Activities sub-headers
                'Selection', 'Remark',
                // Problems/Needs sub-headers
                'Economic', 'Social Emotional', 'Health', 'Health', 'Housing', 'Community Services', 'Identify Others Specific Needs', 'Remarks'
            ];

            const subHeaderRow = worksheet.addRow(subHeaders);
            subHeaderRow.height = 25;

            // Style sub-header row
            subHeaderRow.eachCell((cell, colNumber) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: {
                        argb: 'FFF2F2F2'
                    }
                };
                cell.font = {
                    bold: true,
                    color: {
                        argb: 'FF000000'
                    },
                    name: 'Calibri',
                    size: 9
                };
                cell.alignment = {
                    vertical: 'middle',
                    horizontal: 'center',
                    wrapText: true
                };
                cell.border = {
                    top: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    bottom: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    left: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    right: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    }
                };
            });

            // Row 11: Third level headers
            const thirdHeaders = [
                // Province (empty)
                '', '',
                // Municipality (empty)
                '', '',
                // Barangay (empty)
                '', '',
                // NAME (empty - already have sub-headers)
                '', '', '', '',
                // DOUBLE ENTRY IDENTIFIER (empty)
                '',
                // BIRTH DATE (empty)
                '', '', '',
                // OTHER INFORMATION (empty)
                '', '', '', '', '',
                // STATUS (empty)
                '',
                // National AICS headers
                'AICS', '', '', '', 'Socpen', 'Pantawid', 'Livelihood',
                // Local AICS headers
                'AICS', '', '', '', 'Socpen', 'Socpen', 'Livelihood',
                // Other Pensions (empty)
                '', '', '', '',
                // IP Group (empty)
                '',
                // Educational Attainment (empty)
                '',
                // ID NUMBERS (empty)
                '', '', '', '', '', '',
                // Monthly Income (empty)
                '',
                // Source of Income (empty)
                '', '',
                // Assets & Properties (empty)
                '', '',
                // Living/Residing with (empty)
                '', '',
                // Specialization/Skills (empty)
                '', '',
                // Involvement in Community Activities (empty)
                '', '',
                // Problems/Needs - Health sub-header
                '', '', 'Illness', 'Disability', '', '', '', ''
            ];

            const thirdHeaderRow = worksheet.addRow(thirdHeaders);
            thirdHeaderRow.height = 20;

            // Style third header row
            thirdHeaderRow.eachCell((cell, colNumber) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: {
                        argb: 'FFF8F8F8'
                    }
                };
                cell.font = {
                    bold: true,
                    color: {
                        argb: 'FF000000'
                    },
                    name: 'Calibri',
                    size: 8
                };
                cell.alignment = {
                    vertical: 'middle',
                    horizontal: 'center',
                    wrapText: true
                };
                cell.border = {
                    top: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    bottom: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    left: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    right: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    }
                };
            });

            // Row 12: Fourth level headers (AICS details)
            const fourthHeaders = [
                // Province (empty)
                '', '',
                // Municipality (empty)
                '', '',
                // Barangay (empty)
                '', '',
                // NAME (empty)
                '', '', '', '',
                // DOUBLE ENTRY IDENTIFIER (empty)
                '',
                // BIRTH DATE (empty)
                '', '', '',
                // OTHER INFORMATION (empty)
                '', '', '', '', '',
                // STATUS (empty)
                '',
                // National AICS details
                'Food', 'Burial', 'Medical', 'Transport', '', '', '',
                // Local AICS details
                'Food', 'Burial', 'Medical', 'Transport', 'Prov', 'Munis', '',
                // Other Pensions (empty)
                '', '', '', '',
                // IP Group (empty)
                '',
                // Educational Attainment (empty)
                '',
                // ID NUMBERS (empty)
                '', '', '', '', '', '',
                // Monthly Income (empty)
                '',
                // Source of Income (empty)
                '', '',
                // Assets & Properties (empty)
                '', '',
                // Living/Residing with (empty)
                '', '',
                // Specialization/Skills (empty)
                '', '',
                // Involvement in Community Activities (empty)
                '', '',
                // Problems/Needs (empty)
                '', '', '', '', '', '', '', ''
            ];

            const fourthHeaderRow = worksheet.addRow(fourthHeaders);
            fourthHeaderRow.height = 20;

            // Style fourth header row
            fourthHeaderRow.eachCell((cell, colNumber) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: {
                        argb: 'FFFCFCFC'
                    }
                };
                cell.font = {
                    color: {
                        argb: 'FF000000'
                    },
                    name: 'Calibri',
                    size: 8
                };
                cell.alignment = {
                    vertical: 'middle',
                    horizontal: 'center',
                    wrapText: true
                };
                cell.border = {
                    top: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    bottom: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    left: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    },
                    right: {
                        style: 'thin',
                        color: {
                            argb: 'FF000000'
                        }
                    }
                };
            });

            // Set column widths
            worksheet.columns = [{
                    width: 8
                }, {
                    width: 8
                }, // Province (2)
                {
                    width: 10
                }, {
                    width: 10
                }, // Municipality (2)
                {
                    width: 10
                }, {
                    width: 10
                }, // Barangay (2)
                {
                    width: 12
                }, {
                    width: 6
                }, {
                    width: 12
                }, {
                    width: 12
                }, // Name (4)
                {
                    width: 20
                }, // Double Entry
                {
                    width: 8
                }, {
                    width: 10
                }, {
                    width: 6
                }, // Birth Date (3)
                {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 15
                }, {
                    width: 10
                }, {
                    width: 12
                }, // Other Info (5)
                {
                    width: 10
                }, // Status
                {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 8
                }, {
                    width: 8
                }, {
                    width: 8
                }, {
                    width: 10
                }, // National (7)
                {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 8
                }, {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 10
                }, // Local (7)
                {
                    width: 6
                }, {
                    width: 6
                }, {
                    width: 8
                }, {
                    width: 10
                }, // Other Pensions (4)
                {
                    width: 15
                }, // IP Group
                {
                    width: 18
                }, // Educational Attainment
                {
                    width: 12
                }, {
                    width: 10
                }, {
                    width: 10
                }, {
                    width: 10
                }, {
                    width: 10
                }, {
                    width: 10
                }, // ID Numbers (6)
                {
                    width: 15
                }, // Monthly Income
                {
                    width: 15
                }, {
                    width: 12
                }, // Source of Income (2)
                {
                    width: 20
                }, {
                    width: 12
                }, // Assets (2)
                {
                    width: 15
                }, {
                    width: 12
                }, // Living (2)
                {
                    width: 15
                }, {
                    width: 12
                }, // Skills (2)
                {
                    width: 18
                }, {
                    width: 12
                }, // Activities (2)
                {
                    width: 15
                }, {
                    width: 15
                }, {
                    width: 8
                }, {
                    width: 10
                }, {
                    width: 10
                }, {
                    width: 15
                }, {
                    width: 20
                }, {
                    width: 20
                } // Problems/Needs (8)
            ];

            // Merge cells for colspan headers
            // Province merge (A9:B9)
            worksheet.mergeCells('A9:B9');
            // Municipality merge (C9:D9)
            worksheet.mergeCells('C9:D9');
            // Barangay merge (E9:F9)
            worksheet.mergeCells('E9:F9');
            // NAME merge (G9:J9)
            worksheet.mergeCells('G9:J9');
            // BIRTH DATE merge (K9:M9)
            worksheet.mergeCells('K9:M9');
            // OTHER INFORMATION merge (N9:R9)
            worksheet.mergeCells('N9:R9');
            // Program and Services merge (T9:AK9)
            worksheet.mergeCells('T9:AK9');
            // Other Pensions merge (AL9:AO9)
            worksheet.mergeCells('AL9:AO9');
            // ID NUMBERS merge (AQ9:AV9)
            worksheet.mergeCells('AQ9:AV9');
            // Source of Income merge (AX9:AY9)
            worksheet.mergeCells('AX9:AY9');
            // Assets merge (AZ9:BA9)
            worksheet.mergeCells('AZ9:BA9');
            // Living merge (BB9:BC9)
            worksheet.mergeCells('BB9:BC9');
            // Skills merge (BD9:BE9)
            worksheet.mergeCells('BD9:BE9');
            // Activities merge (BF9:BG9)
            worksheet.mergeCells('BF9:BG9');
            // Problems/Needs merge (BH9:BO9)
            worksheet.mergeCells('BH9:BO9');

            // Merge cells for National/Local headers
            // National merge (T10:Z10)
            worksheet.mergeCells('T10:Z10');
            // Local merge (AA10:AG10)
            worksheet.mergeCells('AA10:AG10');

            // Merge AICS headers
            // National AICS merge (T11:W11)
            worksheet.mergeCells('T11:W11');
            // Local AICS merge (AA11:AD11)
            worksheet.mergeCells('AA11:AD11');
            // Local Socpen merge (AE11:AF11)
            worksheet.mergeCells('AE11:AF11');

            // Merge Health header (BI10:BJ10)
            worksheet.mergeCells('BI10:BJ10');
        }

        // Helper function to style data rows
        function styleDataRows(worksheet) {
            for (let i = 13; i <= worksheet.rowCount; i++) {
                const row = worksheet.getRow(i);
                row.height = 20;
                row.eachCell((cell, colNumber) => {
                    cell.fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: {
                            argb: 'FFFFFFFF'
                        }
                    };
                    cell.font = {
                        color: {
                            argb: 'FF000000'
                        },
                        name: 'Calibri',
                        size: 9
                    };
                    cell.alignment = {
                        vertical: 'middle',
                        horizontal: 'center'
                    };
                    cell.border = {
                        top: {
                            style: 'thin',
                            color: {
                                argb: 'FFD9D9D9'
                            }
                        },
                        bottom: {
                            style: 'thin',
                            color: {
                                argb: 'FFD9D9D9'
                            }
                        },
                        left: {
                            style: 'thin',
                            color: {
                                argb: 'FFD9D9D9'
                            }
                        },
                        right: {
                            style: 'thin',
                            color: {
                                argb: 'FFD9D9D9'
                            }
                        }
                    };
                });
            }
        }

        // Function to sanitize worksheet names (remove invalid Excel characters)
        function sanitizeSheetName(name) {
            if (!name) return 'Sheet';

            // Replace invalid characters with underscores
            // Excel doesn't allow: \ / ? * [ ]
            // Also limit length to 31 characters (Excel limit)
            let sanitized = name.replace(/[\\/\?\*\[\]]/g, '_');

            // Remove leading/trailing apostrophes and spaces
            sanitized = sanitized.replace(/^[\s']+|[\s']+$/g, '');

            // Truncate to 31 characters (Excel limit)
            if (sanitized.length > 31) {
                sanitized = sanitized.substring(0, 31);
            }

            // Ensure name is not empty
            if (!sanitized.trim()) {
                sanitized = 'Sheet_' + Date.now();
            }

            return sanitized;
        }

        // Function to create a unique sheet name
        function createUniqueSheetName(workbook, baseName) {
            let sheetName = sanitizeSheetName(baseName);
            let counter = 1;
            let originalName = sheetName;

            // Check if sheet name already exists
            while (workbook.getWorksheet(sheetName)) {
                sheetName = sanitizeSheetName(`${originalName}_${counter}`);
                counter++;
                if (counter > 100) { // Safety limit
                    sheetName = sanitizeSheetName(`${originalName}_${Date.now()}`);
                    break;
                }
            }

            return sheetName;
        }

        // Function to create a worksheet with error handling
        function createWorksheetSafely(workbook, desiredName, data) {
            try {
                const sheetName = createUniqueSheetName(workbook, desiredName);
                const worksheet = workbook.addWorksheet(sheetName);

                // Add headers
                addHeadersToWorksheet(worksheet);

                // Add data if available
                if (data && data.length > 0) {
                    data.forEach(rowData => {
                        try {
                            worksheet.addRow(rowData);
                        } catch (rowError) {
                            console.error('Error adding row:', rowError);
                            // Continue with next row
                        }
                    });

                    // Style data rows
                    try {
                        styleDataRows(worksheet);
                    } catch (styleError) {
                        console.error('Error styling rows:', styleError);
                    }

                    // Add summary
                    const lastRow = worksheet.rowCount;
                    if (lastRow > 12) { // Check if we have data rows
                        try {
                            worksheet.mergeCells(`A${lastRow + 2}:BJ${lastRow + 2}`);
                            const summaryCell = worksheet.getCell(`A${lastRow + 2}`);
                            const displayName = desiredName || sheetName;
                            summaryCell.value = `TOTAL FOR ${displayName.toUpperCase()}: ${data.length} SENIOR CITIZENS`;
                            summaryCell.font = {
                                bold: true,
                                size: 11,
                                name: 'Calibri'
                            };
                            summaryCell.alignment = {
                                horizontal: 'center'
                            };
                            summaryCell.fill = {
                                type: 'pattern',
                                pattern: 'solid',
                                fgColor: {
                                    argb: 'FFE6F3FF'
                                }
                            };
                        } catch (summaryError) {
                            console.error('Error adding summary:', summaryError);
                        }
                    }
                } else {
                    // No data message
                    try {
                        worksheet.mergeCells('A13:BJ13');
                        const noDataCell = worksheet.getCell('A13');
                        noDataCell.value = `No data found for ${desiredName || sheetName}`;
                        noDataCell.font = {
                            italic: true,
                            size: 11,
                            name: 'Calibri'
                        };
                        noDataCell.alignment = {
                            horizontal: 'center'
                        };
                    } catch (msgError) {
                        console.error('Error adding no data message:', msgError);
                    }
                }

                return worksheet;
            } catch (error) {
                console.error(`Error creating worksheet "${desiredName}":`, error);
                return null;
            }
        }

        // Add this function to handle server-side data fetching for export
        async function fetchAllFilteredData() {
            try {
                // Show loading
                const exportBtn = document.querySelector('button[onclick*="exportToExcel"]');
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading Data...';
                exportBtn.disabled = true;

                // Gather all current filter parameters
                const params = new URLSearchParams(window.location.search);

                // Remove pagination parameters
                params.delete('page');

                // Add export flag
                params.append('export', 'excel');
                params.append('export_all', 'true');

                // Fetch all data from server
                const response = await fetch(`masterlist.php?${params.toString()}`);

                if (!response.ok) {
                    throw new Error('Failed to fetch data');
                }

                const result = await response.json();

                // Restore button
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;

                if (!result.success) {
                    throw new Error(result.error || 'Failed to fetch data');
                }

                return result.data || [];

            } catch (error) {
                console.error('Error fetching data:', error);

                // Restore button
                const exportBtn = document.querySelector('button[onclick*="exportToExcel"]');
                if (exportBtn) {
                    exportBtn.innerHTML = '<i class="fas fa-file-excel mr-2"></i>Export to Excel';
                    exportBtn.disabled = false;
                }

                alert('Error fetching data for export: ' + error.message);
                return [];
            }
        }

        // Helper function to create a data row from senior data
        function createDataRow(senior) {
            const basic = senior.basic || {};
            const birth_parts = senior.birth_parts || {};
            const benefits = senior.benefits || {};

            function formatYesNo(value) {
                if (value == 1 || value === true || value === '1') return 'Yes';
                if (value == 0 || value === false || value === '0') return 'No';
                return '';
            }

            function getValue(obj, key, defaultValue = '') {
                if (obj && typeof obj[key] !== 'undefined') {
                    const val = obj[key];
                    return val !== null && val !== '' ? val : defaultValue;
                }
                return defaultValue;
            }

            function getBenefit(benefitObj, category, type, defaultValue = '') {
                if (benefitObj && benefitObj[category] && typeof benefitObj[category][type] !== 'undefined') {
                    return benefitObj[category][type] || defaultValue;
                }
                return defaultValue;
            }

            return [
                // Province (2 columns)
                getValue(basic, 'province'), '',
                // Municipality (2 columns)
                getValue(basic, 'municipality'), '',
                // Barangay (2 columns)
                getValue(basic, 'barangay'), '',
                // NAME (4 columns)
                getValue(basic, 'last_name'),
                getValue(basic, 'suffix'),
                getValue(basic, 'first_name'),
                getValue(basic, 'middle_name'),
                // DOUBLE ENTRY IDENTIFIER
                getValue(basic, 'full_name'),
                // BIRTH DATE (3 columns)
                getValue(birth_parts, 'year'),
                getValue(birth_parts, 'month'),
                getValue(birth_parts, 'day'),
                // OTHER INFORMATION (5 columns)
                getValue(basic, 'age'),
                getValue(basic, 'gender'),
                getValue(basic, 'birth_place'),
                getValue(basic, 'civil_status'),
                getValue(basic, 'religion'),
                // STATUS
                getValue(basic, 'status'),
                // Program and Services - NATIONAL AICS (4 columns)
                getBenefit(benefits, 'national_aics', 'Food'),
                getBenefit(benefits, 'national_aics', 'Burial'),
                getBenefit(benefits, 'national_aics', 'Medical'),
                getBenefit(benefits, 'national_aics', 'Transpo'),
                // Program and Services - NATIONAL OTHER (3 columns)
                getBenefit(benefits, 'national_other', 'SocPen'),
                getBenefit(benefits, 'national_other', 'Pantawid'),
                getBenefit(benefits, 'national_other', 'Livelihood'),
                // Program and Services - LOCAL AICS (4 columns)
                getBenefit(benefits, 'local_aics', 'Food'),
                getBenefit(benefits, 'local_aics', 'Burial'),
                getBenefit(benefits, 'local_aics', 'Medical'),
                getBenefit(benefits, 'local_aics', 'Transpo'),
                // Program and Services - LOCAL OTHER (3 columns)
                getBenefit(benefits, 'local_other', 'SocPen'),
                getBenefit(benefits, 'local_other', 'SocPen'), // Duplicate for Prov/Munis
                getBenefit(benefits, 'local_other', 'Livelihood'),
                // Other Pensions (4 columns)
                formatYesNo(getValue(basic, 'has_sss')),
                formatYesNo(getValue(basic, 'has_gsis')),
                formatYesNo(getValue(basic, 'has_pvao')),
                formatYesNo(getValue(basic, 'has_insurance')),
                // IP Group
                getValue(basic, 'ip_group'),
                // Educational Attainment
                getValue(basic, 'educational_attainment'),
                // ID NUMBERS (6 columns)
                getValue(basic, 'id_number'),
                getValue(basic, 'tin_number'),
                getValue(basic, 'philhealth_number'),
                getValue(basic, 'gsis_number'),
                getValue(basic, 'sss_number'),
                senior['other_pension'] || '',
                // Monthly Income
                senior['monthly_income'] || '',
                // Source of Income (2 columns)
                getValue(basic, 'income_source'),
                getValue(basic, 'income_source_detail'),
                // Assets & Properties (2 columns)
                getValue(basic, 'assets_properties'),
                '',
                // Living/Residing with (2 columns)
                getValue(basic, 'living_arrangement'),
                '',
                // Specialization/Skills (2 columns)
                getValue(basic, 'specialization_skills'),
                '',
                // Involvement in Community Activities (2 columns)
                getValue(basic, 'community_involvement'),
                '',
                // Problems/Needs Commonly Encountered (8 columns)
                getValue(basic, 'problems_needs'),
                '',
                formatYesNo(getValue(basic, 'has_existing_illness')),
                formatYesNo(getValue(basic, 'has_disability')),
                '',
                '',
                '',
                getValue(basic, 'remarks')
            ];
        }

        // Main export function - THIS IS THE FUNCTION THAT GETS CALLED
        async function exportToExcel() {
            try {
                // First fetch ALL filtered data from server
                const allSeniorsData = await fetchAllFilteredData();

                if (!allSeniorsData || allSeniorsData.length === 0) {
                    alert('No data available to export!');
                    return;
                }

                console.log(`Exporting ${allSeniorsData.length} records...`);

                // Show progress
                const exportBtn = document.querySelector('button[onclick*="exportToExcel"]');
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
                exportBtn.disabled = true;

                // Create workbook
                const workbook = new ExcelJS.Workbook();
                workbook.creator = 'MSWD Paluan System';
                workbook.created = new Date();

                // Predefined barangays with Roman numerals
                const barangays = [{
                        roman: 'I',
                        name: 'I - Mapalad',
                        filterName: 'Mapalad'
                    },
                    {
                        roman: 'II',
                        name: 'II - Handang Tumulong',
                        filterName: 'Handang Tumulong'
                    },
                    {
                        roman: 'III',
                        name: 'III - Silahis ng Pag-asa',
                        filterName: 'Silahis ng Pag-asa'
                    },
                    {
                        roman: 'IV',
                        name: 'IV - Pag-asa ng Bayan',
                        filterName: 'Pag-asa ng Bayan'
                    },
                    {
                        roman: 'V',
                        name: 'V - Bagong Silang',
                        filterName: 'Bagong Silang'
                    },
                    {
                        roman: 'VI',
                        name: 'VI - San Jose',
                        filterName: 'San Jose'
                    },
                    {
                        roman: 'VII',
                        name: 'VII - Lumang Bayan',
                        filterName: 'Lumang Bayan'
                    },
                    {
                        roman: 'VIII',
                        name: 'VIII - Marikit',
                        filterName: 'Marikit'
                    },
                    {
                        roman: 'IX',
                        name: 'IX - Tubili',
                        filterName: 'Tubili'
                    },
                    {
                        roman: 'X',
                        name: 'X - Alipaoy',
                        filterName: 'Alipaoy'
                    },
                    {
                        roman: 'XI',
                        name: 'XI - Harison',
                        filterName: 'Harison'
                    },
                    {
                        roman: 'XII',
                        name: 'XII - Mananao',
                        filterName: 'Mananao'
                    }
                ];

                // Group data by barangay
                const dataByBarangay = {};
                const otherBarangays = {};

                // Initialize arrays for each barangay
                barangays.forEach(barangay => {
                    dataByBarangay[barangay.filterName] = [];
                });

                // Process each senior and group by barangay
                allSeniorsData.forEach(senior => {
                    const basic = senior.basic;
                    const barangayName = basic.barangay || '';

                    if (barangayName) {
                        // Sanitize the barangay name before matching
                        const cleanBarangayName = barangayName.trim();

                        let matchedBarangay = barangays.find(b =>
                            b.filterName.toLowerCase() === cleanBarangayName.toLowerCase()
                        );

                        // Try partial match if exact not found
                        if (!matchedBarangay) {
                            matchedBarangay = barangays.find(b =>
                                cleanBarangayName.toLowerCase().includes(b.filterName.toLowerCase()) ||
                                b.filterName.toLowerCase().includes(cleanBarangayName.toLowerCase())
                            );
                        }

                        if (matchedBarangay) {
                            if (!dataByBarangay[matchedBarangay.filterName]) {
                                dataByBarangay[matchedBarangay.filterName] = [];
                            }
                            const rowData = createDataRow(senior);
                            dataByBarangay[matchedBarangay.filterName].push(rowData);
                        } else {
                            // Sanitize barangay name before using as key
                            const sanitizedKey = sanitizeSheetName(cleanBarangayName);
                            if (!otherBarangays[sanitizedKey]) {
                                otherBarangays[sanitizedKey] = {
                                    originalName: cleanBarangayName,
                                    data: []
                                };
                            }
                            const rowData = createDataRow(senior);
                            otherBarangays[sanitizedKey].data.push(rowData);
                        }
                    }
                });

                // 1. Create Summary Sheet with ALL data
                try {
                    const summarySheet = createWorksheetSafely(workbook, 'Summary', allSeniorsData.map(senior => createDataRow(senior)));

                    // Add grand summary at the bottom
                    if (summarySheet && summarySheet.rowCount > 12) {
                        const lastRowSummary = summarySheet.rowCount;
                        summarySheet.mergeCells(`A${lastRowSummary + 2}:BJ${lastRowSummary + 2}`);
                        const summaryCell = summarySheet.getCell(`A${lastRowSummary + 2}`);
                        summaryCell.value = `GRAND TOTAL: ${allSeniorsData.length} SENIOR CITIZENS`;
                        summaryCell.font = {
                            bold: true,
                            size: 12,
                            name: 'Calibri'
                        };
                        summaryCell.alignment = {
                            horizontal: 'center'
                        };
                        summaryCell.fill = {
                            type: 'pattern',
                            pattern: 'solid',
                            fgColor: {
                                argb: 'FFC6E0B4'
                            }
                        };
                    }
                } catch (summaryError) {
                    console.error('Error creating summary sheet:', summaryError);
                }

                // 2. Create individual sheets for each predefined barangay
                barangays.forEach(barangay => {
                    const barangayData = dataByBarangay[barangay.filterName] || [];
                    if (barangayData.length > 0) {
                        createWorksheetSafely(workbook, barangay.name, barangayData);
                    }
                });

                // 3. Create sheets for other barangays
                const otherBarangayKeys = Object.keys(otherBarangays);
                if (otherBarangayKeys.length > 0) {
                    if (otherBarangayKeys.length <= 10) {
                        // Create individual sheets for other barangays (if not too many)
                        otherBarangayKeys.forEach(key => {
                            const barangayInfo = otherBarangays[key];
                            createWorksheetSafely(workbook, barangayInfo.originalName, barangayInfo.data);
                        });
                    } else {
                        // If too many other barangays, combine into one sheet
                        let combinedData = [];
                        let totalOther = 0;

                        otherBarangayKeys.forEach(key => {
                            const barangayInfo = otherBarangays[key];
                            barangayInfo.data.forEach(rowData => {
                                combinedData.push(rowData);
                            });
                            totalOther += barangayInfo.data.length;
                        });

                        const otherSheet = createWorksheetSafely(workbook, 'Other Barangays', combinedData);

                        if (otherSheet && otherSheet.rowCount > 12) {
                            const lastRowOther = otherSheet.rowCount;
                            otherSheet.mergeCells(`A${lastRowOther + 4}:BJ${lastRowOther + 4}`);
                            const summaryCell = otherSheet.getCell(`A${lastRowOther + 4}`);
                            summaryCell.value = `TOTAL FOR ${otherBarangayKeys.length} OTHER BARANGAYS: ${totalOther} SENIOR CITIZENS`;
                            summaryCell.font = {
                                bold: true,
                                size: 11,
                                name: 'Calibri'
                            };
                            summaryCell.alignment = {
                                horizontal: 'center'
                            };
                            summaryCell.fill = {
                                type: 'pattern',
                                pattern: 'solid',
                                fgColor: {
                                    argb: 'FFE6F3FF'
                                }
                            };
                        }
                    }
                }

                // Generate filename with filters
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const params = new URLSearchParams(window.location.search);
                const searchTerm = params.get('search');
                const barangayFilter = params.get('barangays');

                let fileName = `Demographic_Master_List_${timestamp}`;

                if (searchTerm) {
                    fileName += `_search_${searchTerm.substring(0, 20).replace(/[^\w]/g, '_')}`;
                }
                if (barangayFilter) {
                    fileName += `_barangay_filter`;
                }

                fileName += '.xlsx';

                // Save file
                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                saveAs(blob, fileName);

                // Restore button
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;

                // Show success message
                const totalSheets = workbook.worksheets.length;
                alert(`✅ Excel file exported successfully!\n\n📊 Total Records: ${allSeniorsData.length}\n📁 Total Sheets: ${totalSheets}\n💾 File: ${fileName}`);

            } catch (error) {
                console.error('Error exporting to Excel:', error);

                // Restore button
                const exportBtn = document.querySelector('button[onclick*="exportToExcel"]');
                if (exportBtn) {
                    exportBtn.innerHTML = '<i class="fas fa-file-excel mr-2"></i>Export to Excel';
                    exportBtn.disabled = false;
                }

                // Show user-friendly error message
                if (error.message.includes('worksheet name') && error.message.includes('cannot include')) {
                    alert('Error: Some barangay names contain invalid characters for Excel sheet names.\n\nPlease check your barangay names in the database and remove special characters like: * ? : \\ / [ ]');
                } else {
                    alert('Error exporting to Excel: ' + error.message);
                }
            }
        }

        // Attach event listeners
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedBarangaysText();

            // Attach change event to checkboxes for auto-filtering
            document.querySelectorAll('.barangay-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    updateSelectedBarangaysText();
                    autoFilter();
                });
            });

            // Search with Enter key
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('pageInput').value = 1;
                    document.getElementById('filterForm').submit();
                }
            });

            // Also filter when search input changes (with debounce)
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('pageInput').value = 1;
                    document.getElementById('filterForm').submit();
                }, 800);
            });

            // Remove the old exportMasterlistToExcel function call
            // The exportToExcel function is already the main function
        });
    </script>
</body>

</html>