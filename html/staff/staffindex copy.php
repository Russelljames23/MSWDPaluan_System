<?php
// Debug session
error_log("=== PAGE LOADED ===");
error_log("Page: " . basename(__FILE__));
error_log("Session ID: " . session_id());
error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'none'));
error_log("Staff user ID: " . ($_SESSION['staff_user_id'] ?? 'none'));
error_log("Admin user ID: " . ($_SESSION['admin_user_id'] ?? 'none'));
error_log("Full name: " . ($_SESSION['fullname'] ?? 'none'));
error_log("Username: " . ($_SESSION['username'] ?? 'none'));
require_once "../../php/login/staff_header.php";
require_once '../../php/login/staff_session_sync.php';

// Fix session handling for staff
if (isset($_GET['session_context']) && !empty($_GET['session_context'])) {
    // Store session context but don't use it for session name
    $ctx = $_GET['session_context'];

    // Set a default session context if not set
    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'Staff';
    }

    // Make sure we have a user ID
    if (!isset($_SESSION['user_id']) && isset($user_id) && $user_id > 0) {
        $_SESSION['user_id'] = $user_id;
    }

    // Store fullname in session if available
    if (!isset($_SESSION['fullname']) && !empty($full_name)) {
        $_SESSION['fullname'] = $full_name;
    }
}

$ctx = urlencode($_GET['session_context'] ?? session_id());

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
syncStaffSession($pdo);
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
?>
<?php
// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get current staff user ID
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_name = $_SESSION['fullname'] ?? 'Staff';

// Get statistics - FIRST get total seniors for everyone (as originally)
$stats = [];

// Total seniors - For staff, show all (like original)
$query = "SELECT COUNT(*) as total FROM applicants WHERE status != 'Deceased'";
$result = mysqli_query($conn, $query);
$stats['total'] = mysqli_fetch_assoc($result)['total'] ?? 0;
$stats['total_registered'] = $stats['total']; // Add this line to fix the issue

// Get seniors registered by this staff (from activity logs)
$query = "SELECT DISTINCT JSON_EXTRACT(activity_details, '$.applicant_id') as applicant_id 
          FROM activity_logs 
          WHERE user_id = ? AND activity_type = 'REGISTER_SENIOR' 
          AND activity_details LIKE '%applicant_id%'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff_registered_ids = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['applicant_id'])) {
        $applicant_id = trim($row['applicant_id'], '"\'');
        if (is_numeric($applicant_id)) {
            $staff_registered_ids[] = (int)$applicant_id;
        }
    }
}
$stmt->close();

// Count seniors registered by this staff
$stats['staff_total'] = count($staff_registered_ids);

// Gender distribution for all seniors
$query = "SELECT gender, COUNT(*) as count FROM applicants WHERE status != 'Deceased' GROUP BY gender";
$result = mysqli_query($conn, $query);
$stats['gender'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['gender'][$row['gender']] = $row['count'];
}

// Gender distribution for staff's registered seniors
$stats['staff_gender'] = ['Male' => 0, 'Female' => 0];
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query = "SELECT gender, COUNT(*) as count FROM applicants WHERE applicant_id IN ($ids_str) AND status != 'Deceased' GROUP BY gender";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['staff_gender'][$row['gender']] = $row['count'];
    }
}

// Calculate gender percentages for staff's seniors
$male_count = $stats['staff_gender']['Male'] ?? 0;
$female_count = $stats['staff_gender']['Female'] ?? 0;
$total_gender = $male_count + $female_count;

if ($total_gender > 0) {
    $male_percentage = round(($male_count / $total_gender) * 100, 1);
    $female_percentage = round(($female_count / $total_gender) * 100, 1);
} else {
    $male_percentage = 0;
    $female_percentage = 0;
}

// Status distribution for all seniors
$query = "SELECT status, COUNT(*) as count FROM applicants GROUP BY status";
$result = mysqli_query($conn, $query);
$stats['status'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['status'][$row['status']] = $row['count'];
}

// Status distribution for staff's registered seniors
$stats['staff_status'] = ['Active' => 0, 'Inactive' => 0, 'Deceased' => 0];
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query = "SELECT status, COUNT(*) as count FROM applicants WHERE applicant_id IN ($ids_str) GROUP BY status";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['staff_status'][$row['status']] = $row['count'];
    }
}

// Calculate percentages for status for staff's seniors
$active_count = $stats['staff_status']['Active'] ?? 0;
$inactive_count = $stats['staff_status']['Inactive'] ?? 0;
$deceased_count = $stats['staff_status']['Deceased'] ?? 0;

if ($stats['staff_total'] > 0) {
    $active_percentage = round(($active_count / $stats['staff_total']) * 100, 1);
    $inactive_percentage = round(($inactive_count / $stats['staff_total']) * 100, 1);
    $deceased_percentage = round(($deceased_count / $stats['staff_total']) * 100, 1);
} else {
    $active_percentage = 0;
    $inactive_percentage = 0;
    $deceased_percentage = 0;
}

// Recent registrations (last 30 days) - by this staff
$query = "SELECT COUNT(DISTINCT JSON_EXTRACT(activity_details, '$.applicant_id')) as count 
          FROM activity_logs 
          WHERE user_id = ? AND activity_type = 'REGISTER_SENIOR' 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND activity_details LIKE '%applicant_id%'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['recent_registrations'] = $result->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Age distribution for staff's registered seniors
$query = "SELECT 
            CASE 
                WHEN current_age BETWEEN 60 AND 64 THEN '60-64'
                WHEN current_age BETWEEN 65 AND 69 THEN '65-69'
                WHEN current_age BETWEEN 70 AND 74 THEN '70-74'
                WHEN current_age BETWEEN 75 AND 79 THEN '75-79'
                WHEN current_age BETWEEN 80 AND 84 THEN '80-84'
                WHEN current_age BETWEEN 85 AND 89 THEN '85-89'
                WHEN current_age >= 90 THEN '90+'
                ELSE 'Under 60'
            END as age_group,
            COUNT(*) as count 
          FROM applicants 
          WHERE status != 'Deceased'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query .= " AND applicant_id IN ($ids_str)";
}
$query .= " GROUP BY age_group
          ORDER BY 
            CASE age_group
                WHEN '60-64' THEN 1
                WHEN '65-69' THEN 2
                WHEN '70-74' THEN 3
                WHEN '75-79' THEN 4
                WHEN '80-84' THEN 5
                WHEN '85-89' THEN 6
                WHEN '90+' THEN 7
                ELSE 8
            END";
$result = mysqli_query($conn, $query);
$stats['age_groups'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['age_groups'][$row['age_group']] = $row['count'];
}

// Barangay distribution for staff's registered seniors
$query = "SELECT a.barangay, COUNT(*) as count 
          FROM addresses a 
          JOIN applicants ap ON a.applicant_id = ap.applicant_id
          WHERE a.barangay IS NOT NULL AND a.barangay != '' AND ap.status != 'Deceased'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query .= " AND ap.applicant_id IN ($ids_str)";
}
$query .= " GROUP BY a.barangay 
          ORDER BY 
            CASE a.barangay
                WHEN 'I - Mapalad' THEN 1
                WHEN 'II - Handang Tumulong' THEN 2
                WHEN 'III - Silahis ng Pag-asa' THEN 3
                WHEN 'IV - Pag-asa ng Bayan' THEN 4
                WHEN 'V - Bagong Silang' THEN 5
                WHEN 'VI - San Jose' THEN 6
                WHEN 'VII - Lumang Bayan' THEN 7
                WHEN 'VIII - Marikit' THEN 8
                WHEN 'IX - Tubili' THEN 9
                WHEN 'X - Alipaoy' THEN 10
                WHEN 'XI - Harison' THEN 11
                WHEN 'XII - Mananao' THEN 12
                ELSE 13
            END";
$result = mysqli_query($conn, $query);
$stats['barangays'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['barangays'][$row['barangay']] = $row['count'];
}

// Get top 5 barangays
$top_barangays = [];
if (!empty($stats['barangays'])) {
    arsort($stats['barangays']);
    $top_barangays = array_slice($stats['barangays'], 0, 5, true);
}

// Get staff's recent activities from activity_logs
$query = "SELECT al.activity_type, al.description, al.created_at 
          FROM activity_logs al
          WHERE al.user_id = ? 
          ORDER BY al.created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['recent_activities'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['recent_activities'][] = $row;
}
$stmt->close();

// Count total staff activities in last 7 days
$query = "SELECT COUNT(*) as staff_activities 
          FROM activity_logs 
          WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['staff_activities'] = $result->fetch_assoc()['staff_activities'] ?? 0;
$stmt->close();

// Get pending validation count for all seniors
$query = "SELECT COUNT(*) as pending_validation FROM applicants WHERE validation = 'For Validation' AND status != 'Deceased'";
$result = mysqli_query($conn, $query);
$stats['pending_validation'] = mysqli_fetch_assoc($result)['pending_validation'] ?? 0;

// Get monthly trend data for this staff's registrations
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                 COUNT(DISTINCT JSON_EXTRACT(activity_details, '$.applicant_id')) as count 
          FROM activity_logs 
          WHERE user_id = ? AND activity_type = 'REGISTER_SENIOR' 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          AND activity_details LIKE '%applicant_id%'
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month DESC
          LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['monthly_trend'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['monthly_trend'][$row['month']] = $row['count'];
}
$stmt->close();

// Get today's registrations by this staff
$query = "SELECT COUNT(DISTINCT JSON_EXTRACT(activity_details, '$.applicant_id')) as today 
          FROM activity_logs 
          WHERE user_id = ? AND activity_type = 'REGISTER_SENIOR' 
          AND DATE(created_at) = CURDATE()
          AND activity_details LIKE '%applicant_id%'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['today_registrations'] = $result->fetch_assoc()['today'] ?? 0;
$stmt->close();

// Get staff's performance stats
$stats['performance'] = [
    'active_count' => $active_count,
    'validated_count' => 0,
    'pending_count' => 0
];

// ========== NEW: BIRTHDAY MONITORING SECTION ==========
// Get today's birthdays for staff's registered seniors
$today = date('m-d');
$query_today = "SELECT 
    a.applicant_id,
    CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name,
    a.birth_date,
    a.current_age,
    a.gender,
    ad.barangay,
    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) as new_age_today,
    DATE_FORMAT(a.birth_date, '%M %d, %Y') as formatted_birthdate,
    a.contact_number
FROM applicants a
LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
WHERE a.status = 'Active'
AND DATE_FORMAT(a.birth_date, '%m-%d') = '$today'
AND a.birth_date IS NOT NULL
AND a.birth_date != '0000-00-00'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query_today .= " AND a.applicant_id IN ($ids_str)";
}
$query_today .= " ORDER BY a.last_name, a.first_name";

$result_today = mysqli_query($conn, $query_today);
$birthdays_today = [];
while ($row = mysqli_fetch_assoc($result_today)) {
    $birthdays_today[] = $row;
}

// Get upcoming birthdays (next 7 days) for staff's registered seniors
$query_upcoming = "SELECT 
    a.applicant_id,
    CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name,
    a.birth_date,
    a.current_age,
    a.gender,
    ad.barangay,
    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 as turning_age,
    DATEDIFF(
        DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
        STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(a.birth_date, '%m-%d')), '%Y-%m-%d')
    ) % 365 as days_until_birthday,
    DATE_FORMAT(a.birth_date, '%M %d') as birthday_month_day,
    a.contact_number
FROM applicants a
LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
WHERE a.status = 'Active'
AND a.birth_date IS NOT NULL
AND a.birth_date != '0000-00-00'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query_upcoming .= " AND a.applicant_id IN ($ids_str)";
}
$query_upcoming .= " AND (
    DATE_FORMAT(a.birth_date, '%m-%d') >= '$today'
    OR DATE_FORMAT(a.birth_date, '%m-%d') < DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 358 DAY), '%m-%d')
)
AND DATE_FORMAT(a.birth_date, '%m-%d') != '$today'
ORDER BY 
    CASE 
        WHEN DATE_FORMAT(a.birth_date, '%m-%d') >= '$today' 
        THEN DATE_FORMAT(a.birth_date, '%m-%d')
        ELSE DATE_FORMAT(a.birth_date, '%m-%d') + 365
    END
LIMIT 10";

$result_upcoming = mysqli_query($conn, $query_upcoming);
$upcoming_birthdays = [];
while ($row = mysqli_fetch_assoc($result_upcoming)) {
    $upcoming_birthdays[] = $row;
}

// Get birthdays by month for staff's registered seniors
$query_monthly = "SELECT 
    MONTH(birth_date) as birth_month,
    COUNT(*) as count,
    DATE_FORMAT(birth_date, '%M') as month_name
FROM applicants 
WHERE status = 'Active'
AND birth_date IS NOT NULL
AND birth_date != '0000-00-00'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query_monthly .= " AND applicant_id IN ($ids_str)";
}
$query_monthly .= " GROUP BY MONTH(birth_date), DATE_FORMAT(birth_date, '%M')
ORDER BY MONTH(birth_date)";

$result_monthly = mysqli_query($conn, $query_monthly);
$birthdays_by_month = [];
while ($row = mysqli_fetch_assoc($result_monthly)) {
    $birthdays_by_month[$row['month_name']] = $row['count'];
}

// Get milestone birthdays for staff's registered seniors
$query_milestone = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 60 THEN 'Turning 60'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 65 THEN 'Turning 65'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 70 THEN 'Turning 70'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 75 THEN 'Turning 75'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 80 THEN 'Turning 80'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 85 THEN 'Turning 85'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 90 THEN 'Turning 90'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 95 THEN 'Turning 95'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 >= 100 THEN '100+ Years'
        ELSE 'Other'
    END as milestone,
    COUNT(*) as count
FROM applicants 
WHERE status = 'Active'
AND birth_date IS NOT NULL
AND birth_date != '0000-00-00'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query_milestone .= " AND applicant_id IN ($ids_str)";
}
$query_milestone .= " AND DATE_FORMAT(birth_date, '%m-%d') >= '$today'
AND DATE_FORMAT(birth_date, '%m-%d') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d')
GROUP BY milestone
ORDER BY 
    CASE milestone
        WHEN 'Turning 60' THEN 1
        WHEN 'Turning 65' THEN 2
        WHEN 'Turning 70' THEN 3
        WHEN 'Turning 75' THEN 4
        WHEN 'Turning 80' THEN 5
        WHEN 'Turning 85' THEN 6
        WHEN 'Turning 90' THEN 7
        WHEN 'Turning 95' THEN 8
        WHEN '100+ Years' THEN 9
        ELSE 10
    END";

$result_milestone = mysqli_query($conn, $query_milestone);
$milestone_birthdays = [];
while ($row = mysqli_fetch_assoc($result_milestone)) {
    $milestone_birthdays[$row['milestone']] = $row['count'];
}

// Get birthday statistics for staff's registered seniors
$total_with_birthdates = 0;
$query_total = "SELECT COUNT(*) as total FROM applicants WHERE status = 'Active' AND birth_date IS NOT NULL AND birth_date != '0000-00-00'";
if (!empty($staff_registered_ids)) {
    $ids_str = implode(',', $staff_registered_ids);
    $query_total = "SELECT COUNT(*) as total FROM applicants WHERE status = 'Active' AND birth_date IS NOT NULL AND birth_date != '0000-00-00' AND applicant_id IN ($ids_str)";
}
$result_total = mysqli_query($conn, $query_total);
if ($row = mysqli_fetch_assoc($result_total)) {
    $total_with_birthdates = $row['total'];
}

// Calculate percentages
$today_birthday_count = count($birthdays_today);
$upcoming_birthday_count = count($upcoming_birthdays);
$birthday_percentage = $total_with_birthdates > 0 ? round(($today_birthday_count / $total_with_birthdates) * 100, 1) : 0;

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - MSWD Paluan</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">

    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .stat-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .chart-container {
            min-height: 300px;
            position: relative;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Birthday specific styles */
        .birthday-badge {
            animation: birthday-pulse 1.5s infinite alternate;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.5);
        }

        @keyframes birthday-pulse {
            from {
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
                transform: scale(1);
            }

            to {
                box-shadow: 0 0 20px rgba(255, 193, 7, 0.7);
                transform: scale(1.05);
            }
        }

        .birthday-card {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border-left: 4px solid #ffc107;
        }

        .dark .birthday-card {
            background: linear-gradient(135deg, #2d2400 0%, #3d2f00 100%);
            border-left: 4px solid #ffc107;
        }

        .upcoming-badge {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }

        .dark .upcoming-badge {
            background: linear-gradient(135deg, #0d2840 0%, #13375e 100%);
        }

        .milestone-badge {
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
        }

        .dark .milestone-badge {
            background: linear-gradient(135deg, #3d0b1e 0%, #5a1232 100%);
        }

        /* Confetti animation */
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #ffc107;
            top: -10px;
            z-index: 9999;
            animation: confetti-fall 3s linear forwards;
        }

        /* Birthday modal styles */
        .birthday-modal {
            animation: modalSlideUp 0.3s ease-out;
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .birthday-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: bold;
        }

        .age-milestone {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {

            .grid-cols-4,
            .grid-cols-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script type="text/javascript">
        google.charts.load('current', {
            'packages': ['corechart', 'bar']
        });
        google.charts.setOnLoadCallback(initializeAllCharts);

        let charts = {};
        let isDarkMode = false;
        let birthdayChartData = null;

        function initializeAllCharts() {
            isDarkMode = document.documentElement.classList.contains('dark');
            drawCharts();
            drawBirthdayCharts();
        }

        function drawCharts() {
            const chartOptions = {
                backgroundColor: 'transparent',
                chartArea: {
                    width: '85%',
                    height: '75%'
                },
                legend: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    },
                    position: 'labeled'
                },
                tooltip: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    }
                },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            // Gender Distribution Pie Chart
            try {
                var genderData = google.visualization.arrayToDataTable([
                    ['Gender', 'Count'],
                    ['Male', <?php echo $male_count; ?>],
                    ['Female', <?php echo $female_count; ?>]
                ]);

                var genderOptions = {
                    ...chartOptions,
                    title: '',
                    pieHole: 0.4,
                    colors: ['#3B82F6', '#EC4899'],
                    pieSliceText: 'value'
                };

                var genderChart = new google.visualization.PieChart(document.getElementById('gender-chart'));
                genderChart.draw(genderData, genderOptions);
            } catch (error) {
                console.error('Error drawing gender chart:', error);
            }

            // Status Distribution Pie Chart
            try {
                var statusData = google.visualization.arrayToDataTable([
                    ['Status', 'Count'],
                    ['Active', <?php echo $active_count; ?>],
                    ['Inactive', <?php echo $inactive_count; ?>],
                    ['Deceased', <?php echo $deceased_count; ?>]
                ]);

                var statusOptions = {
                    ...chartOptions,
                    title: '',
                    pieHole: 0.4,
                    colors: ['#10B981', '#F59E0B', '#EF4444'],
                    pieSliceText: 'value'
                };

                var statusChart = new google.visualization.PieChart(document.getElementById('status-chart'));
                statusChart.draw(statusData, statusOptions);
            } catch (error) {
                console.error('Error drawing status chart:', error);
            }

            // Age Distribution Column Chart
            try {
                var ageData = new google.visualization.DataTable();
                ageData.addColumn('string', 'Age Group');
                ageData.addColumn('number', 'Count');

                <?php
                $age_groups = $stats['age_groups'] ?? [];
                $all_age_groups = [
                    '60-64' => 0,
                    '65-69' => 0,
                    '70-74' => 0,
                    '75-79' => 0,
                    '80-84' => 0,
                    '85-89' => 0,
                    '90+' => 0
                ];

                foreach ($all_age_groups as $group => $value) {
                    if (isset($age_groups[$group])) {
                        $all_age_groups[$group] = $age_groups[$group];
                    }
                }

                echo "ageData.addRows([\n";
                foreach ($all_age_groups as $group => $count) {
                    echo "['$group', $count],\n";
                }
                echo "]);";
                ?>

                var ageOptions = {
                    ...chartOptions,
                    title: '',
                    hAxis: {
                        title: 'Age Group',
                        textStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563'
                        }
                    },
                    vAxis: {
                        title: 'Number of Seniors',
                        titleTextStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563'
                        },
                        minValue: 0
                    },
                    colors: ['#8B5CF6'],
                    legend: {
                        position: 'none'
                    }
                };

                var ageChart = new google.visualization.ColumnChart(document.getElementById('age-chart'));
                ageChart.draw(ageData, ageOptions);
            } catch (error) {
                console.error('Error drawing age chart:', error);
            }

            // Barangay Distribution Bar Chart
            try {
                var barangayData = new google.visualization.DataTable();
                barangayData.addColumn('string', 'Barangay');
                barangayData.addColumn('number', 'Count');

                <?php
                if (!empty($stats['barangays'])) {
                    echo "barangayData.addRows([\n";
                    foreach ($stats['barangays'] as $barangay => $count) {
                        $shortName = preg_replace('/^[IVXLCDM]+ - /', '', $barangay);
                        echo "['$shortName', $count],\n";
                    }
                    echo "]);";
                } else {
                    echo "barangayData.addRows([['No Data', 0]]);";
                }
                ?>

                var barangayOptions = {
                    ...chartOptions,
                    title: '',
                    hAxis: {
                        title: '',
                        textStyle: {
                            fontSize: 11,
                            color: isDarkMode ? '#fff' : '#4B5563'
                        },
                        slantedText: true,
                        slantedTextAngle: 45
                    },
                    vAxis: {
                        title: 'Number of Seniors',
                        titleTextStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563'
                        },
                        minValue: 0
                    },
                    legend: {
                        position: 'none'
                    },
                    colors: ['#10B981']
                };

                var barangayChart = new google.visualization.ColumnChart(document.getElementById('barangay-chart'));
                barangayChart.draw(barangayData, barangayOptions);
            } catch (error) {
                console.error('Error drawing barangay chart:', error);
            }
        }

        // NEW: Draw birthday charts for staff
        function drawBirthdayCharts() {
            const chartOptions = {
                backgroundColor: 'transparent',
                chartArea: {
                    width: '85%',
                    height: '75%'
                },
                legend: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    },
                    position: 'labeled'
                },
                tooltip: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    },
                    showColorCode: true,
                    trigger: 'selection'
                },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            // Birthday by Month Chart for staff's registered seniors
            try {
                var birthdayMonthData = new google.visualization.DataTable();
                birthdayMonthData.addColumn('string', 'Month');
                birthdayMonthData.addColumn('number', 'Birthdays');

                <?php
                $all_months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];

                if (!empty($birthdays_by_month)) {
                    echo "birthdayMonthData.addRows([\n";
                    foreach ($all_months as $month) {
                        $count = $birthdays_by_month[$month] ?? 0;
                        echo "['$month', $count],\n";
                    }
                    echo "]);";
                } else {
                    echo "birthdayMonthData.addRows([['No Data', 0]]);";
                }
                ?>

                var birthdayMonthOptions = {
                    ...chartOptions,
                    title: '',
                    colors: ['#FF9800'],
                    hAxis: {
                        title: 'Month',
                        textStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563'
                        }
                    },
                    vAxis: {
                        title: 'Number of Birthdays',
                        minValue: 0,
                        textStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563'
                        }
                    }
                };

                charts.birthdayMonth = new google.visualization.ColumnChart(document.getElementById('birthday-month-chart'));
                charts.birthdayMonth.draw(birthdayMonthData, birthdayMonthOptions);

            } catch (error) {
                console.error('Error drawing birthday month chart:', error);
            }

            // Milestone Birthdays Chart for staff's registered seniors
            try {
                var milestoneData = new google.visualization.DataTable();
                milestoneData.addColumn('string', 'Milestone');
                milestoneData.addColumn('number', 'Count');

                <?php
                if (!empty($milestone_birthdays)) {
                    echo "milestoneData.addRows([\n";
                    foreach ($milestone_birthdays as $milestone => $count) {
                        echo "['$milestone', $count],\n";
                    }
                    echo "]);";
                } else {
                    echo "milestoneData.addRows([['No Milestones', 0]]);";
                }
                ?>

                var milestoneOptions = {
                    ...chartOptions,
                    title: '',
                    pieHole: 0.4,
                    colors: ['#FFC107', '#FF9800', '#FF5722', '#E91E63', '#9C27B0', '#3F51B5', '#2196F3', '#00BCD4', '#009688'],
                    pieSliceText: 'value'
                };

                charts.milestone = new google.visualization.PieChart(document.getElementById('milestone-chart'));
                charts.milestone.draw(milestoneData, milestoneOptions);

            } catch (error) {
                console.error('Error drawing milestone chart:', error);
            }
        }

        // NEW: Show birthday celebration modal
        function showBirthdayCelebration(senior) {
            // Remove any existing modal
            const existingModal = document.querySelector('.birthday-celebration-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create confetti effect
            createConfetti();

            // Create modal
            const modal = document.createElement('div');
            modal.className = 'birthday-celebration-modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden birthday-modal">
                    <div class="relative">
                        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-yellow-400 via-orange-500 to-pink-500"></div>
                        <div class="p-6 text-center">
                            <div class="mb-4">
                                <div class="birthday-avatar mx-auto mb-4">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Happy Birthday!</h3>
                                <div class="age-milestone mb-4">${senior.new_age_today}</div>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">${senior.full_name}</p>
                                <p class="text-gray-600 dark:text-gray-400">${senior.formatted_birthdate}</p>
                                <div class="mt-4 inline-flex items-center px-4 py-2 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    ${senior.barangay || 'N/A'}
                                </div>
                            </div>
                            
                            <div class="mt-6 space-y-3">
                                <button onclick="sendBirthdayGreeting(${senior.applicant_id}, '${senior.full_name}', '${senior.contact_number || ''}')" 
                                        class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-sms mr-2"></i> Send SMS Greeting
                                </button>
                                <button onclick="generateBirthdayCertificate(${senior.applicant_id})" 
                                        class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-certificate mr-2"></i> Generate Certificate
                                </button>
                                <button onclick="viewSeniorProfile(${senior.applicant_id})" 
                                        class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-user-circle mr-2"></i> View Profile
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                        <button onclick="closeModal('birthday-celebration')" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal('birthday-celebration');
                }
            });

            // Add escape key to close
            const handleEsc = function(e) {
                if (e.key === 'Escape') {
                    closeModal('birthday-celebration');
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }

        // NEW: Create confetti effect
        function createConfetti() {
            const colors = ['#FFC107', '#FF9800', '#FF5722', '#E91E63', '#9C27B0', '#3F51B5'];

            for (let i = 0; i < 30; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.opacity = Math.random() * 0.5 + 0.5;

                document.body.appendChild(confetti);

                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }
        }

        // NEW: Send birthday greeting
        function sendBirthdayGreeting(applicantId, fullName, phoneNumber) {
            if (!phoneNumber) {
                showToast('No phone number available for this senior', 'error');
                return;
            }

            showToast(`Sending birthday SMS to ${fullName}...`, 'info');

            // In a real implementation, you would make an AJAX call to send SMS
            // For now, we'll simulate it
            setTimeout(() => {
                showToast(`Birthday SMS sent to ${fullName}`, 'success');

                // Log the action
                fetch('../../php/log_birthday_greeting.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        applicant_id: applicantId,
                        full_name: fullName,
                        phone_number: phoneNumber,
                        greeting_type: 'sms',
                        sent_by: <?php echo $staff_id; ?>
                    })
                });
            }, 1000);
        }

        // NEW: Generate birthday certificate
        function generateBirthdayCertificate(applicantId) {
            showToast('Generating birthday certificate...', 'info');

            // Open certificate generation in new window
            window.open(`./generate_birthday_certificate.php?applicant_id=${applicantId}&session_context=<?php echo $ctx; ?>`, '_blank');

            // Log the action
            fetch('../../php/log_birthday_greeting.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    applicant_id: applicantId,
                    greeting_type: 'certificate',
                    sent_by: <?php echo $staff_id; ?>
                })
            });
        }

        // NEW: View senior profile
        function viewSeniorProfile(applicantId) {
            window.location.href = `./staff_view_senior.php?id=${applicantId}&session_context=<?php echo $ctx; ?>`;
        }

        // NEW: Send birthday reminders
        function sendBirthdayReminders() {
            showToast('Sending birthday reminders...', 'info');

            fetch('../../php/send_birthday_reminders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_reminders',
                        user_id: <?php echo $staff_id; ?>,
                        staff_only: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Sent ${data.sent_count} birthday reminders`, 'success');
                    } else {
                        showToast('Failed to send reminders: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error sending reminders', 'error');
                });
        }

        // NEW: Send birthday greetings to all
        function sendBirthdayGreetingsToAll() {
            showToast('Sending birthday greetings to all celebrants...', 'info');

            <?php foreach ($birthdays_today as $senior): ?>
                setTimeout(() => {
                    sendBirthdayGreeting(
                        <?php echo $senior['applicant_id']; ?>,
                        '<?php echo addslashes($senior['full_name']); ?>',
                        '<?php echo addslashes($senior['contact_number'] ?? ''); ?>'
                    );
                }, <?php echo (array_search($senior, $birthdays_today) * 1000) + 1000; ?>);
            <?php endforeach; ?>

            setTimeout(() => {
                showToast('All birthday greetings sent successfully!', 'success');
            }, <?php echo (count($birthdays_today) * 1000) + 2000; ?>);
        }

        // Generic close modal function
        function closeModal(type) {
            const modal = document.querySelector(`.${type}-modal`);
            if (modal) {
                modal.remove();
            }
        }

        // Redraw charts on window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                drawCharts();
                drawBirthdayCharts();
            }, 250);
        });

        // Theme change detection
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    isDarkMode = document.documentElement.classList.contains('dark');
                    drawCharts();
                    drawBirthdayCharts();
                }
            });
        });
        observer.observe(document.documentElement, {
            attributes: true
        });

        // Show toast notification
        function showToast(message, type = 'info') {
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `custom-toast fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white transition-all duration-300 transform translate-x-full ${type === 'info' ? 'bg-blue-500' : type === 'success' ? 'bg-green-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-500'}`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'info' ? 'fa-info-circle' : type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'} mr-3"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Refresh dashboard data
        function refreshDashboard() {
            const refreshBtn = document.getElementById('refresh-btn');
            const originalHtml = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;

            showToast('Refreshing dashboard...', 'info');

            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check if there are birthdays today and show celebration
            <?php if (count($birthdays_today) > 0): ?>
                setTimeout(() => {
                    // Show celebration for the first birthday
                    const firstBirthday = <?php echo json_encode($birthdays_today[0] ?? null); ?>;
                    if (firstBirthday) {
                        showBirthdayCelebration(firstBirthday);
                    }
                }, 1000);
            <?php endif; ?>

            // Add birthday countdown timer
            updateBirthdayCountdown();
            setInterval(updateBirthdayCountdown, 60000); // Update every minute

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    refreshDashboard();
                }
            });
        });

        // NEW: Update birthday countdown
        function updateBirthdayCountdown() {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);

            const diff = tomorrow - now;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            const countdownElement = document.getElementById('birthday-countdown');
            if (countdownElement) {
                countdownElement.textContent = `${hours}h ${minutes}m`;
            }
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
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
                <a href="#" class="flex items-center justify-between mr-4 ">
                    <img src="../../img/MSWD_LOGO-removebg-preview.png"
                        class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50"
                        alt="MSWD LOGO" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD
                        PALUAN</span>
                </a>
                <form action="#" method="GET" class="hidden md:block md:pl-2">
                    <label for="topbar-search" class="sr-only">Search</label>
                    <div class="relative md:w-64 md:w-96">
                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                                </path>
                            </svg>
                        </div>
                        <input type="text" name="email" id="topbar-search"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Search" />
                    </div>
                </form>
            </div>
            <!-- UserProfile -->
            <div class="flex items-center lg:order-2">
                <button type="button"
                    class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full object-cover"
                        src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                        alt="user photo" />
                </button>
                <!-- Dropdown menu -->
                <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
                    id="dropdown">
                    <div class="py-3 px-4">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                            <?php
                            // Display fullname with fallback
                            if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                                echo htmlspecialchars($_SESSION['fullname']);
                            } else if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                                // Construct fullname from first and last name if available
                                echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
                            } else {
                                echo 'User';
                            }
                            ?>
                        </span>
                        <span class="block text-sm text-gray-900 truncate dark:text-white">
                            <?php
                            // Display user type with proper formatting
                            if (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
                                echo htmlspecialchars($_SESSION['user_type']);
                            } else if (isset($_SESSION['role_name']) && !empty($_SESSION['role_name'])) {
                                // Fallback to role_name if available
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
                                class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Sign
                                out</a>
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
            <form action="#" method="GET" class="md:hidden mb-2">
                <label for="sidebar-search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                            viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                            </path>
                        </svg>
                    </div>
                    <input type="text" name="search" id="sidebar-search"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        placeholder="Search" />
                </div>
            </form>
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
            <ul class="space-y-2">
                <li>
                    <a href="#"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor"
                            class="w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400  dark:group-hover:text-white">
                            <!-- Top-left (taller) -->
                            <rect x="3" y="3" width="8" height="10" rx="1.5" />

                            <!-- Top-right (smaller) -->
                            <rect x="13" y="3" width="8" height="6" rx="1.5" />

                            <!-- Bottom-left (smaller) -->
                            <rect x="3" y="15" width="8" height="6" rx="1.5" />

                            <!-- Bottom-right (taller) -->
                            <rect x="13" y="11" width="8" height="10" rx="1.5" />

                        </svg>

                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <g transform="translate(24,0) scale(-1,1)">
                                <path fill-rule="evenodd"
                                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
                                    clip-rule="evenodd" />
                            </g>
                        </svg>
                        <span class="ml-3">Register</span>
                    </a>
                </li>
                <li>
                    <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                        class="flex items-center p-2 cursor-pointer w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
                        <svg aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5" />
                        </svg>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                        <svg aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages" aria-hidden="true"
                            class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                        <li>
                            <a href="./staff_activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                List</a>
                        </li>
                        <li>
                            <a href="./staff_inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                List</a>
                        </li>
                        <li>
                            <a href="./staff_deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                List</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="./staff_benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 7V2.221a2 2 0 0 0-.5.365L3.586 6.5a2 2 0 0 0-.365.5H8Zm2 0V2h7a2 2 0 0 1 2 2v.126a5.087 5.087 0 0 0-4.74 1.368v.001l-6.642 6.642a3 3 0 0 0-.82 1.532l-.74 3.692a3 3 0 0 0 3.53 3.53l3.694-.738a3 3 0 0 0 1.532-.82L19 15.149V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                                clip-rule="evenodd" />
                            <path fill-rule="evenodd"
                                d="M17.447 8.08a1.087 1.087 0 0 1 1.187.238l.002.001a1.088 1.088 0 0 1 0 1.539l-.377.377-1.54-1.542.373-.374.002-.001c.1-.102.22-.182.353-.237Zm-2.143 2.027-4.644 4.644-.385 1.924 1.925-.385 4.644-4.642-1.54-1.54Zm2.56-4.11a3.087 3.087 0 0 0-2.187.909l-6.645 6.645a1 1 0 0 0-.274.51l-.739 3.693a1 1 0 0 0 1.177 1.176l3.693-.738a1 1 0 0 0 .51-.274l6.65-6.646a3.088 3.088 0 0 0-2.185-5.275Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Benefits</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3" />
                        </svg>

                        <span class="ml-3">Report</span>
                    </a>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a href="./staff_profile.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg aria-hidden="true"
                            class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content - DASHBOARD -->
    <main class="p-4 md:ml-64 h-auto pt-20">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Staff Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Welcome back, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Staff'); ?>! Here's your personal overview.</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- <button onclick="sendBirthdayReminders()"
                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 flex items-center">
                    <i class="fas fa-bell mr-2"></i> Send Birthday Reminders
                </button> -->
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden md:inline">
                    Last updated: <?php echo date('M j, Y H:i'); ?>
                </span>
                <button id="refresh-btn" onclick="refreshDashboard()"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Birthday Alert Banner -->
        <?php if (count($birthdays_today) > 0): ?>
            <div class="mb-6 birthday-card rounded-xl shadow p-4 fade-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 mr-4">
                            <i class="fas fa-birthday-cake text-2xl text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                <span class="birthday-badge px-3 py-1 rounded-full bg-yellow-500 text-white text-sm mr-2">
                                    <?php echo count($birthdays_today); ?>
                                </span>
                                Birthday<?php echo count($birthdays_today) > 1 ? 's' : ''; ?> Today! (Your Seniors)
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                <?php foreach ($birthdays_today as $index => $senior): ?>
                                    <?php if ($index < 3): ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($senior['full_name']); ?></span> (turning <?php echo $senior['new_age_today']; ?>)<?php echo $index < min(2, count($birthdays_today) - 1) ? ', ' : ''; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count($birthdays_today) > 3): ?>
                                    and <?php echo count($birthdays_today) - 3; ?> more...
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <button onclick="sendBirthdayGreetingsToAll()"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg hover:from-yellow-600 hover:to-orange-700 transition-all duration-200 flex items-center">
                        <i class="fas fa-gift mr-2"></i> Send All Greetings
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="mb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Registered Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in"
                    onclick="window.location.href='./staff_activelist.php?session_context=<?php echo $ctx; ?>'"
                    style="cursor: pointer;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Your Registered Seniors</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $stats['staff_total']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-users text-blue-600 dark:text-blue-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Total System: <?php echo $stats['total']; ?></span>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">
                                <i class="fas fa-user-check mr-1"></i>Your Share
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Today's Birthdays Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in birthday-card"
                    style="animation-delay: 0.1s; cursor: pointer;"
                    onclick="window.location.href='./staff_birthdays.php?filter=birthday_today&session_context=<?php echo $ctx; ?>'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Birthdays</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $today_birthday_count; ?></p>
                            <?php if ($today_birthday_count > 0): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo $birthday_percentage; ?>% of your active seniors
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 birthday-badge">
                            <i class="fas fa-birthday-cake text-yellow-600 dark:text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">
                                <i class="fas fa-clock mr-1"></i>
                                Next update: <span id="birthday-countdown">24h 0m</span>
                            </span>
                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">
                                <i class="fas fa-calendar-day mr-1"></i>Your Seniors
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Birthdays Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in upcoming-badge"
                    style="animation-delay: 0.2s; cursor: pointer;"
                    onclick="window.location.href='./staff_birthdays.php?filter=upcoming_birthdays&session_context=<?php echo $ctx; ?>'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming (7 days)</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo $upcoming_birthday_count; ?></p>
                            <?php if (!empty($upcoming_birthdays)): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Next: <?php echo htmlspecialchars($upcoming_birthdays[0]['full_name'] ?? ''); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Next 7 days</span>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">
                                <i class="fas fa-arrow-right mr-1"></i>View
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Milestone Birthdays Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in milestone-badge"
                    style="animation-delay: 0.3s; cursor: pointer;"
                    onclick="window.location.href='./staff_birthdays.php?filter=milestone_birthdays&session_context=<?php echo $ctx; ?>'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Milestone Birthdays</p>
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2"><?php echo array_sum($milestone_birthdays); ?></p>
                            <?php if (!empty($milestone_birthdays)): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <?php echo array_keys($milestone_birthdays)[0] ?? 'No milestones'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <i class="fas fa-medal text-purple-600 dark:text-purple-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">60, 65, 70, 75, 80+</span>
                            <span class="text-purple-600 dark:text-purple-400 font-medium">
                                <i class="fas fa-star mr-1"></i>Special
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Monitoring Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Birthday Monitoring (Your Seniors)</h2>
                <div class="flex space-x-2">
                    <a href="./staff_birthdays.php?filter=birthdays&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-red-600 rounded-lg hover:from-orange-600 hover:to-red-700 transition-all duration-200 flex items-center">
                        <i class="fas fa-calendar-week mr-2"></i> View All Birthdays
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Today's Birthdays List -->
                <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Today's Celebrants</h3>
                        <span class="px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full dark:bg-yellow-900 dark:text-yellow-300">
                            <?php echo date('F j'); ?>
                        </span>
                    </div>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <?php if (count($birthdays_today) > 0): ?>
                            <?php foreach ($birthdays_today as $senior): ?>
                                <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition-colors cursor-pointer"
                                    onclick="showBirthdayCelebration(<?php echo htmlspecialchars(json_encode($senior)); ?>)">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-center text-white font-bold mr-3">
                                            <?php echo strtoupper(substr($senior['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior['full_name']); ?></p>
                                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <span class="mr-3">
                                                    <i class="fas fa-cake-candles mr-1"></i>Turning <?php echo $senior['new_age_today']; ?>
                                                </span>
                                                <?php if ($senior['barangay']): ?>
                                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($senior['barangay']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">
                                        <i class="fas fa-gift"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-birthday-cake text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400">No birthdays today</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Check upcoming birthdays</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($birthdays_today) > 0): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button onclick="sendBirthdayGreetingsToAll()"
                                class="w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                                <i class="fas fa-paper-plane mr-2"></i> Send Greetings to All
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Birthdays -->
                <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Birthdays</h3>
                        <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-300">
                            Next 7 days
                        </span>
                    </div>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <?php if (count($upcoming_birthdays) > 0): ?>
                            <?php foreach ($upcoming_birthdays as $senior): ?>
                                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold mr-3">
                                            <?php echo strtoupper(substr($senior['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior['full_name']); ?></p>
                                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <span class="mr-3">
                                                    <i class="fas fa-calendar-alt mr-1"></i><?php echo htmlspecialchars($senior['birthday_month_day']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-cake-candles mr-1"></i>Turning <?php echo $senior['turning_age']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                            in <?php echo $senior['days_until_birthday']; ?> day<?php echo $senior['days_until_birthday'] != 1 ? 's' : ''; ?>
                                        </div>
                                        <?php if ($senior['barangay']): ?>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($senior['barangay']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-alt text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400">No upcoming birthdays</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Next 7 days</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Birthday Charts -->
                <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Birthday Analytics</h3>
                        <div class="flex space-x-2">
                            <button onclick="drawBirthdayCharts()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <!-- Birthday by Month Chart -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Birthdays by Month</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Total: <?php echo array_sum($birthdays_by_month); ?>
                                </span>
                            </div>
                            <div id="birthday-month-chart" style="height: 150px;"></div>
                        </div>

                        <!-- Milestone Birthdays Chart -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Milestone Birthdays</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Next 30 days
                                </span>
                            </div>
                            <div id="milestone-chart" style="height: 150px;"></div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-lg font-bold text-gray-900 dark:text-white"><?php echo $total_with_birthdates; ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">With Birthdates</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400"><?php echo $birthday_percentage; ?>%</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Birthday Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Original Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Gender Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Gender Distribution (Your Seniors)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Male vs Female ratio in your registrations</p>
                    </div>
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-300">
                            Total: <?php echo $total_gender; ?>
                        </span>
                    </div>
                </div>
                <div id="gender-chart" style="height: 300px;"></div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400"><?php echo $male_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Male</div>
                    </div>
                    <div class="text-center p-2 bg-pink-50 dark:bg-pink-900/30 rounded-lg">
                        <div class="text-lg font-bold text-pink-600 dark:text-pink-400"><?php echo $female_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Female</div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status Distribution (Your Seniors)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active, Inactive, and Deceased status in your registrations</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900 dark:text-green-300">
                            Active: <?php echo $active_count; ?>
                        </span>
                    </div>
                </div>
                <div id="status-chart" style="height: 300px;"></div>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400"><?php echo $active_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Active</div>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400"><?php echo $inactive_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Inactive</div>
                    </div>
                    <div class="text-center p-2 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <div class="text-lg font-bold text-red-600 dark:text-red-400"><?php echo $deceased_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Deceased</div>
                    </div>
                </div>
            </div>

            <!-- Age Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Age Distribution (Your Seniors)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Senior citizens by age groups in your registrations</p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="window.location.href='./staff_activelist.php?session_context=<?php echo $ctx; ?>'"
                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            data-tooltip="View all">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                    </div>
                </div>
                <div id="age-chart" style="height: 300px;"></div>
                <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-2"></i>Distribution of your seniors by age bracket
                </div>
            </div>

            <!-- Barangay Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Barangay Distribution (Your Seniors)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Senior citizens per barangay in your registrations</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900 dark:text-green-300">
                            <?php echo count($stats['barangays']); ?> barangays
                        </span>
                    </div>
                </div>
                <div id="barangay-chart" style="height: 300px;"></div>
                <?php if (!empty($top_barangays)): ?>
                    <div class="mt-4">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top Barangays (Your Seniors):</div>
                        <div class="flex flex-wrap gap-2">
                            <?php $counter = 0; ?>
                            <?php foreach ($top_barangays as $barangay => $count): ?>
                                <?php if ($count > 0 && $counter < 5): ?>
                                    <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                        <?php echo str_replace(['I - ', 'II - ', 'III - ', 'IV - ', 'V - ', 'VI - ', 'VII - ', 'VIII - ', 'IX - ', 'X - ', 'XI - ', 'XII - '], '', $barangay); ?>: <?php echo $count; ?>
                                    </span>
                                    <?php $counter++; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats and Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="./staff_register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-800 mr-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <span>Register New Senior</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./staff_activelist.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-800 mr-3">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <span>View Your Active Seniors</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./staff_benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-purple-700 bg-purple-50 rounded-lg hover:bg-purple-100 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-800 mr-3">
                                <i class="fas fa-gift"></i>
                            </div>
                            <span>Manage Benefits</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./staff_generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-800 mr-3">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <span>Generate ID</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Recent Activity</h3>
                    <span class="text-xs text-green-600 dark:text-green-400 pulse">
                        <i class="fas fa-circle"></i> Live
                    </span>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($stats['recent_activities'])): ?>
                        <?php foreach ($stats['recent_activities'] as $activity): ?>
                            <div class="flex items-center">
                                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900 mr-3">
                                    <?php
                                    $icon = 'fa-user';
                                    $color = 'blue';
                                    if (strpos($activity['activity_type'], 'REGISTER') !== false) {
                                        $icon = 'fa-user-plus';
                                        $color = 'green';
                                    } elseif (strpos($activity['activity_type'], 'UPDATE') !== false) {
                                        $icon = 'fa-edit';
                                        $color = 'yellow';
                                    } elseif (strpos($activity['activity_type'], 'DELETE') !== false) {
                                        $icon = 'fa-trash';
                                        $color = 'red';
                                    } elseif (strpos($activity['activity_type'], 'LOGIN') !== false) {
                                        $icon = 'fa-sign-in-alt';
                                        $color = 'blue';
                                    } elseif (strpos($activity['activity_type'], 'VALIDATE') !== false) {
                                        $icon = 'fa-check-circle';
                                        $color = 'green';
                                    } elseif (strpos($activity['activity_type'], 'MARK_') !== false) {
                                        $icon = 'fa-tag';
                                        $color = 'orange';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?> text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-300"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Performance</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Registration Rate</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <?php echo $stats['staff_total']; ?> seniors
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <?php
                            $registration_rate = $stats['staff_total'] > 0 ? min(100, $stats['staff_total'] * 2) : 0;
                            ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $registration_rate; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Activity Level</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <?php
                                $activity_level = $stats['recent_registrations'] > 0 ? 'High' : ($stats['staff_total'] > 0 ? 'Moderate' : 'Low');
                                echo $activity_level;
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $activity_level === 'High' ? 80 : ($activity_level === 'Moderate' ? 50 : 20); ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Success Rate</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <?php
                                $success_rate = $stats['staff_total'] > 0 ? round(($active_count / $stats['staff_total']) * 100) : 0;
                                echo $success_rate; ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $success_rate; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Completion Rate</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <?php echo round(($active_count / max(1, $stats['staff_total'])) * 100); ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo round(($active_count / max(1, $stats['staff_total'])) * 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Stats -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['staff_total']; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Your Registered</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $today_birthday_count; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Birthdays Today</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo count($stats['barangays']); ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Barangays Covered</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo $stats['staff_activities']; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Your Activities</div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/staff_tailwind.config.js"></script>
    <script src="../../js/staff_theme.js"></script>
    <script>
        // ---------- THEME INITIALIZATION ----------
        (function() {
            const StaffTheme = {
                init: function() {
                    const savedTheme = localStorage.getItem('staff_theme');
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    let theme = 'light';
                    if (savedTheme) {
                        theme = savedTheme;
                    } else if (systemPrefersDark) {
                        theme = 'dark';
                    }

                    this.set(theme);
                    return theme;
                },

                set: function(theme) {
                    const root = document.documentElement;
                    const wasDark = root.classList.contains('dark');
                    const isDark = theme === 'dark';

                    if (isDark && !wasDark) {
                        root.classList.add('dark');
                        localStorage.setItem('staff_theme', 'dark');
                    } else if (!isDark && wasDark) {
                        root.classList.remove('dark');
                        localStorage.setItem('staff_theme', 'light');
                    }

                    window.dispatchEvent(new CustomEvent('staffThemeChanged'));
                }
            };

            StaffTheme.init();

            window.addEventListener('storage', function(e) {
                if (e.key === 'staff_theme') {
                    const theme = e.newValue;
                    const currentIsDark = document.documentElement.classList.contains('dark');
                    const newIsDark = theme === 'dark';

                    if ((newIsDark && !currentIsDark) || (!newIsDark && currentIsDark)) {
                        StaffTheme.set(theme);
                    }
                }
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('staff_theme')) {
                    StaffTheme.set(e.matches ? 'dark' : 'light');
                }
            });
        })();
    </script>
</body>

</html>