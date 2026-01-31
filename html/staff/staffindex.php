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
    $ctx = $_GET['session_context'];

    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'Staff';
    }

    if (!isset($_SESSION['user_id']) && isset($user_id) && $user_id > 0) {
        $_SESSION['user_id'] = $user_id;
    }

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

// Fetch current user data
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

// Prepare full name
$full_name = '';
if (!empty($user_data['firstname']) && !empty($user_data['lastname'])) {
    $full_name = $user_data['firstname'] . ' ' . $user_data['lastname'];
    if (!empty($user_data['middlename'])) {
        $full_name = $user_data['firstname'] . ' ' . $user_data['middlename'] . ' ' . $user_data['lastname'];
    }
}

// Get profile photo URL
$profile_photo_url = '';
if (!empty($user_data['profile_photo'])) {
    $profile_photo_url = '../../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar if no profile photo
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

// Get statistics - FIRST get total seniors for everyone
$stats = [];

// Total seniors - For staff, show all
$query = "SELECT COUNT(*) as total FROM applicants WHERE status != 'Deceased'";
$result = mysqli_query($conn, $query);
$stats['total'] = mysqli_fetch_assoc($result)['total'] ?? 0;
$stats['total_registered'] = $stats['total'];

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

// Ensure all 12 barangays are included, even with 0 count (STAFF SPECIFIC)
$all_barangays_staff = [
    'I - Mapalad' => 0,
    'II - Handang Tumulong' => 0,
    'III - Silahis ng Pag-asa' => 0,
    'IV - Pag-asa ng Bayan' => 0,
    'V - Bagong Silang' => 0,
    'VI - San Jose' => 0,
    'VII - Lumang Bayan' => 0,
    'VIII - Marikit' => 0,
    'IX - Tubili' => 0,
    'X - Alipaoy' => 0,
    'XI - Harison' => 0,
    'XII - Mananao' => 0
];

// Merge with actual data for staff
foreach ($all_barangays_staff as $barangay => $count) {
    if (isset($stats['barangays'][$barangay])) {
        $all_barangays_staff[$barangay] = $stats['barangays'][$barangay];
    }
}

$stats['barangays'] = $all_barangays_staff;

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

// ========== BIRTHDAY MONITORING SECTION ==========
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

// ========== BIRTHDAY MODAL LOGIC ==========
// Check if we should show birthday modal today
$today_date = date('Y-m-d');
$show_birthday_modal = false;
$all_birthdays_today_for_modal = [];

if (count($birthdays_today) > 0) {
    if (!isset($_SESSION['staff_birthday_modal_shown']) || $_SESSION['staff_birthday_modal_shown'] !== $today_date) {
        $show_birthday_modal = true;
        $_SESSION['staff_birthday_modal_shown'] = $today_date;
        $all_birthdays_today_for_modal = $birthdays_today;
    }
}

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
        /* Enhanced logo styling for page display */
        .highlighted-logo {
            filter:
                brightness(1.3)
                /* Make brighter */
                contrast(1.2)
                /* Increase contrast */
                saturate(1.5)
                /* Make colors more vibrant */
                drop-shadow(0 0 8px #3b82f6)
                /* Blue glow */
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
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .confetti {
            position: fixed;
            z-index: 9998;
            animation: confetti-fall 3s linear forwards;
            pointer-events: none;
        }

        /* Birthday modal styles */
        .birthday-modal {
            animation: modalSlideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .birthday-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFC107, #FF9800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
            position: relative;
        }

        .age-milestone {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(135deg, #FFC107, #FF5722);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
        }

        /* Chart error styles */
        .chart-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
        }

        .dark .chart-error {
            background: linear-gradient(135deg, #2c1a1a 0%, #3c1a1a 100%);
            border: 1px solid #7f1d1d;
        }

        .chart-error-icon {
            font-size: 3rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }

        .dark .chart-error-icon {
            color: #f87171;
        }

        .retry-btn {
            margin-top: 1rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .retry-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
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

        /* New styles for birthday carousel */
        .birthday-carousel {
            position: relative;
            overflow: hidden;
            min-height: 300px;
        }
        
        .birthday-slide {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        
        .birthday-slide.active {
            display: block;
        }
        
        .birthday-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .birthday-nav:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-50%) scale(1.1);
        }
        
        .birthday-nav.prev {
            left: 10px;
        }
        
        .birthday-nav.next {
            right: 10px;
        }
        
        .birthday-counter {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            z-index: 10;
        }
        
        .dark .birthday-nav {
            background: rgba(55, 65, 81, 0.8);
            color: white;
        }
        
        .dark .birthday-nav:hover {
            background: rgba(55, 65, 81, 1);
        }
    </style>

    <script type="text/javascript">
        // Enhanced Google Charts loading with better error handling
        let charts = {};
        let isDarkMode = false;
        let originalBarangayData = null;
        let originalBarangayOptions = null;
        let ageChartData = null;
        let birthdayChartData = null;
        let barangayChartFiltered = false;
        let googleChartsLoaded = false;
        let chartsLoading = false;
        let chartsLoadAttempts = 0;
        const MAX_CHART_LOAD_ATTEMPTS = 3;

        // Main initialization function
        function initDashboard() {
            console.log('Dashboard initialization started');
            
            // Load Google Charts first
            loadGoogleCharts();
            
            // Initialize other components
            initTheme();
            updateBirthdayCountdown();
            
            // Set up periodic countdown updates
            setInterval(updateBirthdayCountdown, 60000);
            
            // Show birthday modal if needed
            <?php if ($show_birthday_modal && !empty($all_birthdays_today_for_modal)): ?>
                setTimeout(() => {
                    const allBirthdays = <?php echo json_encode($all_birthdays_today_for_modal); ?>;
                    if (allBirthdays && allBirthdays.length > 0) {
                        showBirthdayCelebration(allBirthdays);
                    }
                }, 2000);
            <?php endif; ?>
        }

        // Improved Google Charts loading function
        function loadGoogleCharts() {
            if (chartsLoading) return;
            
            chartsLoading = true;
            chartsLoadAttempts++;
            
            console.log('Loading Google Charts, attempt:', chartsLoadAttempts);
            
            // Check if Google Charts is already loaded
            if (typeof google !== 'undefined' && typeof google.charts !== 'undefined' && typeof google.visualization !== 'undefined') {
                console.log('Google Charts already loaded');
                initializeAllCharts();
                return;
            }
            
            // Set a timeout to detect if charts fail to load
            const loadTimeout = setTimeout(() => {
                if (!googleChartsLoaded) {
                    console.warn('Google Charts load timeout');
                    handleChartsLoadFailure();
                }
            }, 10000); // 10 second timeout
            
            // Load Google Charts with error handling
            window.googleChartsLoadCallback = function() {
                clearTimeout(loadTimeout);
                console.log('Google Charts loaded successfully');
                googleChartsLoaded = true;
                chartsLoading = false;
                initializeAllCharts();
            };
            
            // Load with error handling
            try {
                google.charts.load('current', {
                    'packages': ['corechart', 'bar'],
                    'callback': window.googleChartsLoadCallback
                });
            } catch (error) {
                console.error('Error loading Google Charts:', error);
                handleChartsLoadFailure();
            }
        }

        function handleChartsLoadFailure() {
            chartsLoading = false;
            
            if (chartsLoadAttempts < MAX_CHART_LOAD_ATTEMPTS) {
                console.log('Retrying Google Charts load...');
                setTimeout(loadGoogleCharts, 2000);
            } else {
                console.error('Failed to load Google Charts after', MAX_CHART_LOAD_ATTEMPTS, 'attempts');
                showToast('Charts failed to load. Please refresh the page.', 'error');
                
                // Show fallback for barangay chart
                showBarangayChartFallback();
            }
        }

        function initializeAllCharts() {
            try {
                console.log('Initializing all charts');
                
                // Check if Google Charts is properly loaded
                if (typeof google === 'undefined' || typeof google.visualization === 'undefined') {
                    console.error('Google Charts not loaded in initializeAllCharts');
                    handleChartsLoadFailure();
                    return;
                }
                
                isDarkMode = document.documentElement.classList.contains('dark');
                drawCharts();
                drawBirthdayCharts();
                
                console.log('All charts initialized successfully');
                
                // Hide loading indicators
                const loadingIndicator = document.getElementById('barangay-chart-loading');
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
                
            } catch (error) {
                console.error('Error initializing charts:', error);
                showToast('Error loading charts. Please refresh the page.', 'error');
                handleChartsLoadFailure();
            }
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
                showChartError('gender-chart', error);
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
                showChartError('status-chart', error);
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
                showChartError('age-chart', error);
            }

            // Barangay Distribution Bar Chart
            try {
                const barangayChartContainer = document.getElementById('barangay-chart');
                if (!barangayChartContainer) {
                    console.error('Barangay chart container not found');
                    return;
                }

                // Check if the chart already has error display
                if (barangayChartContainer.querySelector('.chart-error-fallback')) {
                    return;
                }

                var barangayData = new google.visualization.DataTable();
                barangayData.addColumn('string', 'Barangay');
                barangayData.addColumn('number', 'Count');

                <?php
                $barangayColors = [
                    '#3B82F6',
                    '#8B5CF6',
                    '#EC4899',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#6B7280',
                    '#84CC16',
                    '#F97316',
                    '#A855F7',
                    '#06B6D4',
                    '#71717A'
                ];

                if (!empty($stats['barangays'])) {
                    echo "barangayData.addRows([\n";
                    foreach ($stats['barangays'] as $barangay => $count) {
                        $formattedBarangay = preg_replace('/^[IVXLCDM]+ - /', '', $barangay);
                        echo "['$formattedBarangay', $count],\n";
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
                            color: isDarkMode ? '#fff' : '#4B5563',
                            bold: false
                        },
                        slantedText: true,
                        slantedTextAngle: 45,
                        showTextEvery: 1
                    },
                    vAxis: {
                        title: 'Number of Seniors',
                        titleTextStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563',
                            fontSize: 12,
                            bold: true
                        },
                        minValue: 0,
                        textStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563',
                            fontSize: 11
                        },
                        gridlines: {
                            color: isDarkMode ? '#4B5563' : '#E5E7EB',
                            count: 6
                        }
                    },
                    legend: {
                        position: 'none'
                    },
                    bar: {
                        groupWidth: '70%'
                    },
                    colors: <?php echo json_encode($barangayColors); ?>,
                    focusTarget: 'category'
                };

                charts.barangay = new google.visualization.ColumnChart(barangayChartContainer);
                charts.barangay.draw(barangayData, barangayOptions);

                originalBarangayData = barangayData;
                originalBarangayOptions = barangayOptions;

                google.visualization.events.addListener(charts.barangay, 'select', function() {
                    const selection = charts.barangay.getSelection();
                    if (selection.length > 0) {
                        const item = selection[0];
                        const barangays = <?php echo json_encode(array_keys($all_barangays_staff)); ?>;
                        const barangay = barangays[item.row];

                        const filterSelect = document.getElementById('barangay-filter');
                        if (filterSelect) {
                            filterSelect.value = barangay;
                        }

                        filterBarangayChart(barangay);
                    }
                });

            } catch (error) {
                console.error('Error drawing barangay chart:', error);
                showBarangayChartFallback();
            }
        }

        function showBarangayChartFallback() {
            const barangayChartContainer = document.getElementById('barangay-chart');
            if (!barangayChartContainer) return;
            
            // Hide loading indicator
            const loadingIndicator = document.getElementById('barangay-chart-loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            // Create fallback display
            barangayChartContainer.innerHTML = `
                <div class="chart-error-fallback flex flex-col items-center justify-center h-full p-8 text-center">
                    <i class="fas fa-chart-bar text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400 mb-2">Chart could not be loaded</p>
                    <button onclick="retryBarangayChart()" class="retry-btn mt-4">
                        <i class="fas fa-redo mr-2"></i>Retry Loading Chart
                    </button>
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-left">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Barangay Distribution (Your Seniors)</h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <?php foreach ($all_barangays_staff as $barangay => $count): ?>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars(preg_replace('/^[IVXLCDM]+ - /', '', $barangay)); ?></span>
                                <span class="font-medium text-gray-900 dark:text-white"><?php echo $count; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            `;
        }

        function retryBarangayChart() {
            const barangayChartContainer = document.getElementById('barangay-chart');
            if (!barangayChartContainer) return;
            
            // Remove fallback
            const fallback = barangayChartContainer.querySelector('.chart-error-fallback');
            if (fallback) {
                fallback.remove();
            }
            
            // Show loading
            const loadingIndicator = document.getElementById('barangay-chart-loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'flex';
            }
            
            // Clear previous chart
            barangayChartContainer.innerHTML = '';
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'barangay-chart-loading';
            loadingDiv.className = 'absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg';
            loadingDiv.innerHTML = `
                <div class="text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loading chart...</p>
                </div>
            `;
            barangayChartContainer.appendChild(loadingDiv);
            
            // Reload Google Charts
            chartsLoadAttempts = 0;
            googleChartsLoaded = false;
            loadGoogleCharts();
        }

        function showChartError(chartId, error) {
            const chartContainer = document.getElementById(chartId);
            if (chartContainer) {
                chartContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-3xl text-yellow-500 mb-2"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Chart temporarily unavailable</p>
                    </div>
                `;
            }
        }

        // Birthday charts
        function drawBirthdayCharts() {
            try {
                if (typeof google === 'undefined' || typeof google.visualization === 'undefined') {
                    console.warn('Google Charts not available for birthday charts');
                    return;
                }

                const chartOptions = {
                    backgroundColor: 'transparent',
                    chartArea: {
                        width: '85%',
                        height: '75%'
                    },
                    legend: {
                        textStyle: {
                            color: isDarkMode ? '#374151' : '#374151',
                            fontSize: 12
                        },
                        position: 'labeled'
                    },
                    tooltip: {
                        textStyle: {
                            color: isDarkMode ? '#374151' : '#374151',
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

                // Birthday by Month Chart
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

                // Milestone Birthdays Chart
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
            } catch (error) {
                console.error('Error in drawBirthdayCharts:', error);
            }
        }

        function getFullBarangayName(shortName) {
            const barangayMap = {
                'Mapalad': 'I - Mapalad',
                'Handang Tumulong': 'II - Handang Tumulong',
                'Silahis ng Pag-asa': 'III - Silahis ng Pag-asa',
                'Pag-asa ng Bayan': 'IV - Pag-asa ng Bayan',
                'Bagong Silang': 'V - Bagong Silang',
                'San Jose': 'VI - San Jose',
                'Lumang Bayan': 'VII - Lumang Bayan',
                'Marikit': 'VIII - Marikit',
                'Tubili': 'IX - Tubili',
                'Alipaoy': 'X - Alipaoy',
                'Harison': 'XI - Harison',
                'Mananao': 'XII - Mananao'
            };

            return barangayMap[shortName] || shortName;
        }

        function filterBarangayChart(barangay) {
            if (!charts.barangay || !originalBarangayData || !originalBarangayOptions) {
                showToast('Chart data not available', 'error');
                return;
            }

            try {
                const chartContainer = document.getElementById('barangay-chart');
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay';
                loadingOverlay.innerHTML = '<div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-2"></div><div class="text-sm text-gray-600 dark:text-gray-300">Filtering...</div></div>';
                chartContainer.parentElement.style.position = 'relative';
                chartContainer.parentElement.appendChild(loadingOverlay);

                const filteredData = new google.visualization.DataTable();
                filteredData.addColumn('string', 'Barangay');
                filteredData.addColumn('number', 'Count');

                const rows = originalBarangayData.getNumberOfRows();
                let found = false;

                for (let i = 0; i < rows; i++) {
                    const rowBarangay = originalBarangayData.getValue(i, 0);
                    const fullBarangayName = getFullBarangayName(rowBarangay);

                    if (fullBarangayName === barangay || rowBarangay === barangay) {
                        const count = originalBarangayData.getValue(i, 1);
                        filteredData.addRow([rowBarangay, count]);
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    showToast('No data found for selected barangay', 'warning');
                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    barangayChartFiltered = false;

                    const filterSelect = document.getElementById('barangay-filter');
                    if (filterSelect) {
                        filterSelect.value = 'all';
                    }
                    loadingOverlay.remove();
                    return;
                }

                const filteredOptions = {
                    ...originalBarangayOptions,
                    hAxis: {
                        ...originalBarangayOptions.hAxis,
                        textStyle: {
                            ...originalBarangayOptions.hAxis.textStyle,
                            fontSize: 14
                        }
                    },
                    vAxis: {
                        ...originalBarangayOptions.vAxis,
                        title: `Number of Seniors (${barangay})`,
                        minValue: 0,
                        maxValue: filteredData.getValue(0, 1) * 1.2
                    },
                    bar: {
                        groupWidth: '60%'
                    },
                    colors: ['#3B82F6']
                };

                charts.barangay.draw(filteredData, filteredOptions);
                barangayChartFiltered = true;
                showToast(`Showing data for ${barangay}`, 'success');

                setTimeout(() => {
                    loadingOverlay.remove();
                }, 500);

            } catch (error) {
                console.error('Error filtering barangay chart:', error);
                showToast('Error filtering chart: ' + error.message, 'error');

                const loadingOverlay = document.querySelector('.loading-overlay');
                if (loadingOverlay) loadingOverlay.remove();

                if (charts.barangay && originalBarangayData && originalBarangayOptions) {
                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    barangayChartFiltered = false;
                    const filterSelect = document.getElementById('barangay-filter');
                    if (filterSelect) {
                        filterSelect.value = 'all';
                    }
                }
            }
        }

        function filterByBarangay(barangay) {
            if (!googleChartsLoaded) {
                showToast('Charts are still loading. Please wait.', 'warning');
                return;
            }

            if (barangay === 'all') {
                if (charts.barangay && originalBarangayData && originalBarangayOptions) {
                    const chartContainer = document.getElementById('barangay-chart');
                    const loadingOverlay = document.createElement('div');
                    loadingOverlay.className = 'loading-overlay';
                    loadingOverlay.innerHTML = '<div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-2"></div><div class="text-sm text-gray-600 dark:text-gray-300">Loading...</div></div>';
                    chartContainer.parentElement.style.position = 'relative';
                    chartContainer.parentElement.appendChild(loadingOverlay);

                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    barangayChartFiltered = false;
                    showToast('Showing all barangays', 'info');

                    setTimeout(() => {
                        loadingOverlay.remove();
                    }, 500);
                }
            } else {
                filterBarangayChart(barangay);
            }
        }

        // Birthday celebration modal
        function showBirthdayCelebration(allBirthdays) {
            const existingModal = document.querySelector('.birthday-celebration-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            if (!allBirthdays || !Array.isArray(allBirthdays) || allBirthdays.length === 0) {
                console.error('Invalid birthday data');
                showToast('No birthday data available', 'error');
                return;
            }
            
            createConfetti();
            
            const modal = document.createElement('div');
            modal.className = 'birthday-celebration-modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80';
            modal.style.cssText = 'z-index: 9999; backdrop-filter: blur(4px); transition: opacity 0.3s ease;';
            
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden birthday-modal">
                    <div class="relative">
                        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-yellow-400 via-orange-500 to-pink-500"></div>
                        
                        <div class="absolute inset-0 overflow-hidden pointer-events-none">
                            <div class="absolute top-0 left-1/4 w-4 h-4 bg-yellow-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="absolute top-4 right-1/3 w-3 h-3 bg-pink-400 rounded-full animate-bounce" style="animation-delay: 0.5s"></div>
                            <div class="absolute bottom-8 left-1/3 w-3 h-3 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.8s"></div>
                        </div>
                        
                        <div class="birthday-carousel p-6 pt-8">
                            <h3 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-4">
                                🎉 ${allBirthdays.length} Birthday${allBirthdays.length > 1 ? 's' : ''} Today! (Your Seniors) 🎉
                            </h3>
                            
                            ${allBirthdays.map((senior, index) => {
                                const age = senior.new_age_today || senior.turning_age || '🎉';
                                const formattedBirthdate = senior.formatted_birthdate || 
                                    (senior.birth_date ? new Date(senior.birth_date).toLocaleDateString('en-US', {
                                        month: 'long',
                                        day: 'numeric',
                                        year: 'numeric'
                                    }) : 'N/A');
                                
                                return `
                                <div class="birthday-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <div class="birthday-avatar mx-auto mb-4 relative">
                                                <div class="w-20 h-20 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">
                                                    ${senior.full_name ? senior.full_name.charAt(0).toUpperCase() : 'S'}
                                                </div>
                                                <div class="absolute -top-2 -right-2 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white text-sm font-bold">${age}</span>
                                                </div>
                                            </div>
                                            
                                            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                                Happy Birthday!
                                            </h4>
                                            
                                            <div class="age-milestone mb-4 text-3xl">${age} Years Old!</div>
                                            
                                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                                ${senior.full_name || 'Unknown Senior'}
                                            </p>
                                            
                                            <p class="text-gray-600 dark:text-gray-400 mb-3">
                                                <i class="fas fa-calendar-day mr-2"></i>${formattedBirthdate}
                                            </p>
                                            
                                            <div class="flex flex-wrap justify-center gap-2 mt-4">
                                                ${senior.barangay ? `
                                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900 dark:to-orange-900 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700">
                                                    <i class="fas fa-map-marker-alt mr-2 text-xs"></i>
                                                    ${senior.barangay}
                                                </div>
                                                ` : ''}
                                                
                                                ${senior.gender ? `
                                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                                                    <i class="fas ${senior.gender === 'Male' ? 'fa-male' : 'fa-female'} mr-2 text-xs"></i>
                                                    ${senior.gender}
                                                </div>
                                                ` : ''}
                                                
                                                ${senior.contact_number ? `
                                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700">
                                                    <i class="fas fa-phone mr-2 text-xs"></i>
                                                    ${senior.contact_number}
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                `;
                            }).join('')}
                            
                            ${allBirthdays.length > 1 ? `
                            <button class="birthday-nav prev" onclick="changeBirthdaySlide(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="birthday-nav next" onclick="changeBirthdaySlide(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <div class="birthday-counter">
                                <span id="current-slide">1</span> / ${allBirthdays.length}
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-between items-center">
                        <button onclick="closeModal('birthday-celebration')" 
                                class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-white bg-gradient-to-r  rounded-lg hover:from-gray-700 hover:to-gray-800 transition-colors flex items-center">
                            <i class="fas fa-times mr-2"></i> Close
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            window.currentBirthdayIndex = 0;
            window.allBirthdaysData = allBirthdays;
            window.currentBirthday = allBirthdays[0];
            
            const scrollY = window.scrollY;
            
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.overflow = 'hidden';
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal('birthday-celebration');
                }
            });
            
            const handleEsc = function(e) {
                if (e.key === 'Escape') {
                    closeModal('birthday-celebration');
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
            
            if (allBirthdays.length > 1) {
                window.birthdaySlideInterval = setInterval(() => {
                    changeBirthdaySlide(1);
                }, 10000);
            }
            
            setTimeout(() => {
                if (document.querySelector('.birthday-celebration-modal')) {
                    closeModal('birthday-celebration');
                }
            }, 60000);
            
            modal.restoreScroll = function() {
                const scrollY = document.body.style.top;
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.overflow = '';
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            };
        }
        
        function changeBirthdaySlide(direction) {
            const slides = document.querySelectorAll('.birthday-slide');
            const totalSlides = slides.length;
            
            if (totalSlides <= 1) return;
            
            if (window.birthdaySlideInterval) {
                clearInterval(window.birthdaySlideInterval);
            }
            
            let newIndex = window.currentBirthdayIndex + direction;
            
            if (newIndex < 0) {
                newIndex = totalSlides - 1;
            } else if (newIndex >= totalSlides) {
                newIndex = 0;
            }
            
            slides[window.currentBirthdayIndex].classList.remove('active');
            slides[newIndex].classList.add('active');
            
            window.currentBirthdayIndex = newIndex;
            window.currentBirthday = window.allBirthdaysData[newIndex];
            
            const counterElement = document.getElementById('current-slide');
            if (counterElement) {
                counterElement.textContent = newIndex + 1;
            }
            
            if (totalSlides > 1) {
                window.birthdaySlideInterval = setInterval(() => {
                    changeBirthdaySlide(1);
                }, 10000);
            }
        }
        
        function restoreScrollPosition() {
            const scrollY = document.body.style.top;
            if (scrollY) {
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.overflow = '';
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            } else {
                document.body.style.overflow = '';
                document.body.style.position = '';
            }
        }

        function closeModal(type) {
            const modal = document.querySelector(`.${type}-modal`);
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = 'translateY(-50px) scale(0.9)';

                setTimeout(() => {
                    modal.remove();
                    restoreScrollPosition();
                    
                    const confettiElements = document.querySelectorAll('.confetti');
                    confettiElements.forEach(el => el.remove());
                    
                    if (window.birthdaySlideInterval) {
                        clearInterval(window.birthdaySlideInterval);
                        window.birthdaySlideInterval = null;
                    }
                }, 300);
            } else {
                restoreScrollPosition();
            }
        }

        function createConfetti() {
            const colors = ['#FFC107', '#FF9800', '#FF5722', '#E91E63', '#9C27B0', '#3F51B5', '#2196F3', '#4CAF50'];

            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 12 + 8 + 'px';
                confetti.style.height = Math.random() * 12 + 8 + 'px';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.opacity = Math.random() * 0.7 + 0.3;
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;

                document.body.appendChild(confetti);

                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.remove();
                    }
                }, 3000);
            }
        }

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

        function refreshDashboard() {
            const refreshBtn = document.getElementById('refresh-btn');
            const originalHtml = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;

            showToast('Refreshing dashboard...', 'info');

            setTimeout(() => {
                if (googleChartsLoaded) {
                    drawCharts();
                    drawBirthdayCharts();
                    showToast('Dashboard updated!', 'success');
                } else {
                    loadGoogleCharts();
                    showToast('Reloading charts...', 'info');
                }
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            }, 1000);
        }

        // Redraw charts on window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (googleChartsLoaded) {
                    drawCharts();
                    drawBirthdayCharts();
                }
            }, 250);
        });

        // Theme change detection
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    isDarkMode = document.documentElement.classList.contains('dark');
                    if (googleChartsLoaded) {
                        drawCharts();
                        drawBirthdayCharts();
                    }
                }
            });
        });
        observer.observe(document.documentElement, {
            attributes: true
        });

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

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing dashboard');
            initDashboard();
        });

        // Fallback for failed charts
        function retryFailedCharts() {
            console.log('Retrying failed charts');
            chartsLoadAttempts = 0;
            googleChartsLoaded = false;
            loadGoogleCharts();
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

        // NEW: Send birthday greeting
        function sendBirthdayGreeting(applicantId, fullName, phoneNumber) {
            if (!phoneNumber) {
                showToast('No phone number available for this senior', 'error');
                return;
            }

            showToast(`Sending birthday SMS to ${fullName}...`, 'info');

            // In a real implementation, you would make an AJAX call to send SMS
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
                        <i class="fas fa-tachometer-alt w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-user-plus w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Register</span>
                    </a>
                </li>
                <li>
                    <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                        class="flex items-center p-2 cursor-pointer w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        <i class="fas fa-list w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                        <li>
                            <a href="./staff_activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                            </a>
                        </li>
                        <li>
                            <a href="./staff_inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                            </a>
                        </li>
                        <li>
                            <a href="./staff_deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="./staff_benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Benefits</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="./staff_report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Report</span>
                    </a>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a href="./staff_profile.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
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
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden md:inline">
                    Today: <?php echo date('F j, Y'); ?>
                </span>
                <button id="refresh-btn" onclick="refreshDashboard()"
                    class="px-4 py-2 text-sm font-medium text-gray-500 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Birthday Alert Banner -->
        <?php if (count($birthdays_today) > 0): ?>
            <div class="mb-6 birthday-card rounded-xl shadow p-4 fade-in cursor-pointer hover:shadow-lg transition-shadow duration-300"
                onclick="showBirthdayCelebration(<?php echo htmlspecialchars(json_encode($birthdays_today)); ?>)">
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
                                    onclick="showBirthdayCelebration([<?php echo htmlspecialchars(json_encode($senior)); ?>])">
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

            <!-- Barangay Distribution Chart - FIXED -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Barangay Distribution (Your Seniors)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Senior citizens per barangay in your registrations</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <select id="barangay-filter" onchange="filterByBarangay(this.value)"
                                class="appearance-none bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="all">All Barangays</option>
                                <?php foreach ($all_barangays_staff as $barangay => $count): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>">
                                        <?php echo htmlspecialchars(preg_replace('/^[IVXLCDM]+ - /', '', $barangay)); ?> (<?php echo $count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <!-- Chart container with fallback -->
                <div id="barangay-chart" style="height: 300px; min-height: 300px; position: relative;">
                    <!-- Loading indicator -->
                    <div id="barangay-chart-loading" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Loading chart...</p>
                        </div>
                    </div>
                </div>
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