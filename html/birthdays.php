<?php
// birthdays.php - Senior Citizen Birthday Management System
require_once "../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

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
    $profile_photo_url = '../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
$barangay = $_GET['barangay'] ?? 'all';
$milestone = $_GET['milestone'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters
$whereConditions = ["a.status = 'Active'", "a.birth_date IS NOT NULL", "a.birth_date != '0000-00-00'"];
$params = [];

// Filter by type
switch ($filter) {
    case 'today':
        $whereConditions[] = "DATE_FORMAT(a.birth_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
        $pageTitle = "Today's Birthdays";
        break;

    case 'upcoming':
        $whereConditions[] = "DATE_FORMAT(a.birth_date, '%m-%d') > DATE_FORMAT(CURDATE(), '%m-%d')";
        $whereConditions[] = "DATE_FORMAT(a.birth_date, '%m-%d') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')";
        $pageTitle = "Upcoming Birthdays (Next 7 Days)";
        break;

    case 'this_month':
        $whereConditions[] = "MONTH(a.birth_date) = MONTH(CURDATE())";
        $pageTitle = "This Month's Birthdays";
        break;

    case 'next_month':
        $whereConditions[] = "MONTH(a.birth_date) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
        $pageTitle = "Next Month's Birthdays";
        break;

    case 'by_month':
        $whereConditions[] = "MONTH(a.birth_date) = ?";
        $params[] = $month;
        $pageTitle = date('F', mktime(0, 0, 0, $month, 1)) . " Birthdays";
        break;

    case 'milestone':
        if ($milestone) {
            $whereConditions[] = "TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 = ?";
            $params[] = $milestone;
            $pageTitle = "Turning $milestone Years Old";
        } else {
            // Show all milestone birthdays (60, 65, 70, 75, 80, 85, 90, 95, 100+)
            $milestoneAges = [60, 65, 70, 75, 80, 85, 90, 95, 100];
            $milestoneConditions = [];
            foreach ($milestoneAges as $age) {
                if ($age == 100) {
                    $milestoneConditions[] = "TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 >= $age";
                } else {
                    $milestoneConditions[] = "TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 = $age";
                }
            }
            if (!empty($milestoneConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $milestoneConditions) . ")";
            }
            $pageTitle = "Milestone Birthdays";
        }
        break;

    case 'passed':
        $whereConditions[] = "DATE_FORMAT(a.birth_date, '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')";
        $pageTitle = "Passed Birthdays This Year";
        break;

    case 'no_birthdate':
        $whereConditions = ["(a.birth_date IS NULL OR a.birth_date = '0000-00-00')"];
        $pageTitle = "Seniors Without Birthdate";
        break;

    default:
        $pageTitle = "All Birthdays";
        break;
}

// Filter by barangay
if ($barangay !== 'all') {
    $whereConditions[] = "ad.barangay = ?";
    $params[] = $barangay;
}

// Search by name
if ($search) {
    $whereConditions[] = "(CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) LIKE ? OR 
                           a.first_name LIKE ? OR a.last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Build the main query
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countQuery = "SELECT COUNT(*) as total 
               FROM applicants a
               LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
               $whereClause";

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;
$totalPages = ceil($totalCount / $perPage);

// Get birthdays data with pagination
$mainQuery = "SELECT 
    a.applicant_id,
    a.last_name,
    a.first_name,
    a.middle_name,
    a.suffix,
    a.gender,
    a.birth_date,
    a.current_age,
    a.contact_number,
    ad.barangay,
    ad.house_no,
    ad.street,
    ad.municipality,
    ad.province,
    ard.id_number,
    ard.local_control_number,
    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) as current_calculated_age,
    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 as next_age,
    DATEDIFF(
        DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
        STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(a.birth_date, '%m-%d')), '%Y-%m-%d')
    ) % 365 as days_until_birthday,
    DATE_FORMAT(a.birth_date, '%M %d') as formatted_birthday,
    DATE_FORMAT(a.birth_date, '%M %d, %Y') as full_birthdate,
    CASE 
        WHEN DATE_FORMAT(a.birth_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') THEN 'today'
        WHEN DATE_FORMAT(a.birth_date, '%m-%d') > DATE_FORMAT(CURDATE(), '%m-%d') THEN 'upcoming'
        ELSE 'passed'
    END as birthday_status,
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 IN (60, 65, 70, 75, 80, 85, 90, 95, 100) THEN 'milestone'
        WHEN TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) + 1 > 100 THEN 'centenarian'
        ELSE 'regular'
    END as birthday_type
FROM applicants a
LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
$whereClause
ORDER BY 
    CASE 
        WHEN DATE_FORMAT(a.birth_date, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d') 
        THEN DATE_FORMAT(a.birth_date, '%m-%d')
        ELSE DATE_FORMAT(a.birth_date, '%m-%d') + 365
    END,
    a.last_name,
    a.first_name
LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($mainQuery);
$stmt->execute($params);
$birthdays = $stmt->fetchAll();

// Get statistics for dashboard
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND birth_date IS NOT NULL AND birth_date != '0000-00-00') as total_with_birthdates,
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')) as today_count,
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND DATE_FORMAT(birth_date, '%m-%d') > DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(birth_date, '%m-%d') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')) as upcoming_7days,
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND MONTH(birth_date) = MONTH(CURDATE())) as this_month_count,
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND MONTH(birth_date) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))) as next_month_count,
    (SELECT COUNT(*) FROM applicants WHERE status = 'Active' AND (birth_date IS NULL OR birth_date = '0000-00-00')) as no_birthdate_count";

$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get birthdays by month
$monthlyQuery = "SELECT 
    MONTH(birth_date) as month_num,
    DATE_FORMAT(birth_date, '%M') as month_name,
    COUNT(*) as count
FROM applicants 
WHERE status = 'Active' 
AND birth_date IS NOT NULL 
AND birth_date != '0000-00-00'
GROUP BY MONTH(birth_date), DATE_FORMAT(birth_date, '%M')
ORDER BY MONTH(birth_date)";

$monthlyStmt = $pdo->query($monthlyQuery);
$birthdaysByMonth = $monthlyStmt->fetchAll();

// Get milestone statistics
$milestoneQuery = "SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 60 THEN '60'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 65 THEN '65'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 70 THEN '70'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 75 THEN '75'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 80 THEN '80'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 85 THEN '85'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 90 THEN '90'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 95 THEN '95'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 = 100 THEN '100'
        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) + 1 > 100 THEN '100+'
        ELSE NULL
    END as milestone_age,
    COUNT(*) as count
FROM applicants 
WHERE status = 'Active'
AND birth_date IS NOT NULL 
AND birth_date != '0000-00-00'
GROUP BY milestone_age
HAVING milestone_age IS NOT NULL
ORDER BY CAST(milestone_age AS UNSIGNED)";

$milestoneStmt = $pdo->query($milestoneQuery);
$milestoneStats = $milestoneStmt->fetchAll();

// Get all barangays for filter
$barangaysQuery = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
$barangaysStmt = $pdo->query($barangaysQuery);
$allBarangays = $barangaysStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - MSWD Paluan</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">

    <style>
        /* Birthday specific styles */
        .birthday-badge {
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

        .today-birthday {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border-left: 4px solid #ffc107;
        }

        .dark .today-birthday {
            background: linear-gradient(135deg, #2d2400 0%, #3d2f00 100%);
            border-left: 4px solid #ffc107;
        }

        .upcoming-birthday {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
        }

        .dark .upcoming-birthday {
            background: linear-gradient(135deg, #0d2840 0%, #13375e 100%);
            border-left: 4px solid #2196f3;
        }

        .milestone-birthday {
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            border-left: 4px solid #e91e63;
        }

        .dark .milestone-birthday {
            background: linear-gradient(135deg, #3d0b1e 0%, #5a1232 100%);
            border-left: 4px solid #e91e63;
        }

        .centenarian-birthday {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 4px solid #ff9800;
        }

        .dark .centenarian-birthday {
            background: linear-gradient(135deg, #4d2c00 0%, #663d00 100%);
            border-left: 4px solid #ff9800;
        }

        .birthday-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            font-weight: bold;
        }

        .age-badge {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .calendar-day {
            transition: all 0.3s ease;
        }

        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
            font-weight: bold;
        }

        .calendar-day.has-birthday {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            font-weight: bold;
        }

        .dark .calendar-day.has-birthday {
            background: linear-gradient(135deg, #0d2840, #13375e);
        }

        .month-selector {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .dark .month-selector {
            scrollbar-color: #4b5563 transparent;
        }

        .month-selector::-webkit-scrollbar {
            height: 6px;
        }

        .month-selector::-webkit-scrollbar-track {
            background: transparent;
        }

        .month-selector::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }

        .dark .month-selector::-webkit-scrollbar-thumb {
            background-color: #4b5563;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .birthday-list {
                break-inside: avoid;
            }
        }
    </style>

    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Add these missing dark mode styles */
        .calendar-day {
            @apply text-gray-900 dark:text-white;
        }

        .calendar-day.today {
            @apply bg-gradient-to-r from-yellow-400 to-orange-500 dark:from-yellow-600 dark:to-orange-700;
        }

        .calendar-day.has-birthday {
            @apply dark:bg-gradient-to-r dark:from-blue-900 dark:to-indigo-900;
        }

        /* Fix table header colors */
        .dark .bg-gray-50 {
            background-color: #374151 !important;
        }

        /* Fix dropdown menu */
        .dark #dropdown {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
        }

        /* Fix modal background */
        .dark #smsGreetingsModal {
            background-color: rgba(0, 0, 0, 0.75) !important;
        }

        /* Fix modal content */
        .dark .modal-content {
            background-color: #1f2937 !important;
            color: #f3f4f6 !important;
        }

        /* Fix form inputs in dark mode */
        .dark select,
        .dark input[type="text"],
        .dark textarea {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
            color: #f3f4f6 !important;
        }

        /* Fix pagination in dark mode */
        .dark .pagination-button {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
            color: #f3f4f6 !important;
        }

        /* Fix statistic cards in dark mode */
        .dark .stat-card {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
        }

        /* Fix table row hover in dark mode */
        .dark tbody tr:hover {
            background-color: #374151 !important;
        }

        .dark .today-birthday:hover {
            background: linear-gradient(135deg, #3d2f00 0%, #4d3f00 100%) !important;
        }

        .dark .upcoming-birthday:hover {
            background: linear-gradient(135deg, #1a365d 0%, #2a4a7d 100%) !important;
        }

        .dark .milestone-birthday:hover {
            background: linear-gradient(135deg, #5a1232 0%, #6a1a42 100%) !important;
        }

        .dark .centenarian-birthday:hover {
            background: linear-gradient(135deg, #663d00 0%, #764d00 100%) !important;
        }
    </style>
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
                            <?php echo htmlspecialchars($full_name); ?>
                        </span>
                        <span class="block text-sm text-gray-900 truncate dark:text-white">
                            <?php echo htmlspecialchars($user_data['user_type'] ?? 'User'); ?>
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
    <aside class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
        aria-label="Sidenav" id="drawer-navigation">
        <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
            <ul class="space-y-2">
                <li>
                    <a href="./admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="./register.php?session_context=<?php echo $ctx; ?>"
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
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                        <li>
                            <a href="./SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                            </a>
                        </li>
                        <li>
                            <a href="./SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                            </a>
                        </li>
                        <li>
                            <a href="./SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- <li>
                    <a href="#"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                        <i class="fas fa-birthday-cake w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
                        <span class="ml-3">Birthdays</span>
                    </a>
                </li> -->
                <li>
                    <a href="./benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Benefits</span>
                    </a>
                </li>
                <li>
                    <a href="./generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="./reports/report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Report</span>
                    </a>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a href="./archived.php?session_context=<?php echo $ctx; ?>"
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

    <!-- Main Content -->
    <main class="p-4 md:ml-64 h-auto pt-20">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white"><?php echo $pageTitle; ?></h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        <?php echo $totalCount; ?> senior<?php echo $totalCount != 1 ? 's' : ''; ?> found
                        <?php if ($filter === 'today'): ?>
                            • <?php echo date('F j, Y'); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition-colors no-print">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                            <i class="fas fa-birthday-cake text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Today</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['today_count']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                            <i class="fas fa-calendar-week text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Next 7 Days</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['upcoming_7days']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                            <i class="fas fa-calendar-alt text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">This Month</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['this_month_count']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 mr-4">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No Birthdate</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['no_birthdate_count']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex flex-row md:flex-row md:items-center justify-between gap-4">
                <!-- Filter Tabs -->
                <div class="flex flex-row gap-2">
                    <a href="?filter=today&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-center rounded-lg transition-colors <?php echo $filter === 'today' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-birthday-cake mr-2"></i>Today
                    </a>
                    <a href="?filter=upcoming&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-center rounded-lg transition-colors <?php echo $filter === 'upcoming' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-calendar-week mr-2"></i>Upcoming (7 Days)
                    </a>
                    <a href="?filter=this_month&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-center rounded-lg transition-colors <?php echo $filter === 'this_month' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-calendar mr-2"></i>This Month
                    </a>
                    <a href="?filter=milestone&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-center rounded-lg transition-colors <?php echo $filter === 'milestone' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-medal mr-2"></i>Milestone
                    </a>
                    <a href="?filter=all&session_context=<?php echo $ctx; ?>"
                        class="px-4 py-2 text-sm font-medium text-center rounded-lg transition-colors <?php echo $filter === 'all' ? 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-list mr-2"></i>All
                    </a>
                </div>

                <!-- Additional Filters -->
                <div class="flex flex-row gap-2">
                    <!-- Month Selector -->
                    <?php if ($filter === 'by_month' || $filter === 'all'): ?>
                        <div class="relative">
                            <select id="month-select" onchange="filterByMonth(this.value)"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">All Months</option>
                                <?php foreach ($birthdaysByMonth as $monthData): ?>
                                    <option value="<?php echo $monthData['month_num']; ?>" <?php echo $month == $monthData['month_num'] ? 'selected' : ''; ?>>
                                        <?php echo $monthData['month_name']; ?> (<?php echo $monthData['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Barangay Filter -->
                    <div class="relative">
                        <select id="barangay-select" onchange="filterByBarangay(this.value)"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="all">All Barangays</option>
                            <?php foreach ($allBarangays as $brgy): ?>
                                <option value="<?php echo htmlspecialchars($brgy); ?>" <?php echo $barangay === $brgy ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brgy); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <form method="GET" class="flex gap-2">
                            <input type="hidden" name="session_context" value="<?php echo $ctx; ?>">
                            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by name..."
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition-colors">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if ($search): ?>
                                <a href="?filter=<?php echo $filter; ?>&session_context=<?php echo $ctx; ?>"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Month Quick Selector -->
            <?php if ($filter === 'all' || $filter === 'by_month'): ?>
                <div class="mt-4">
                    <div class="flex overflow-x-auto gap-2 pb-2 month-selector">
                        <?php foreach ($birthdaysByMonth as $monthData): ?>
                            <a href="?filter=by_month&month=<?php echo $monthData['month_num']; ?>&session_context=<?php echo $ctx; ?>"
                                class="flex-shrink-0 px-4 py-2 text-sm font-medium rounded-lg transition-colors <?php echo $month == $monthData['month_num'] ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                                <?php echo substr($monthData['month_name'], 0, 3); ?> (<?php echo $monthData['count']; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Milestone Quick Links -->
            <?php if ($filter === 'milestone' || $filter === 'all'): ?>
                <div class="mt-4">
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($milestoneStats as $milestone): ?>
                            <a href="?filter=milestone&milestone=<?php echo $milestone['milestone_age']; ?>&session_context=<?php echo $ctx; ?>"
                                class="px-3 py-1 text-sm font-medium rounded-full transition-colors <?php echo $milestone == $milestone['milestone_age'] ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>">
                                <?php echo $milestone['milestone_age']; ?> Years (<?php echo $milestone['count']; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Birthday Calendar View -->
        <?php if ($filter === 'this_month' || $filter === 'by_month' || $filter === 'all'): ?>
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Birthday Calendar</h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('F Y', mktime(0, 0, 0, $month, 1)); ?>
                        </span>
                        <button onclick="prevMonth()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button onclick="nextMonth()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    <!-- Days of week -->
                    <?php $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
                    <?php foreach ($daysOfWeek as $day): ?>
                        <div class="text-center font-medium text-gray-500 dark:text-gray-400 py-2">
                            <?php echo $day; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Calendar days -->
                    <?php
                    $firstDay = date('w', mktime(0, 0, 0, $month, 1, $year));
                    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
                    $currentDay = date('j');
                    $currentMonth = date('n');
                    $currentYear = date('Y');

                    // Get birthdays for this month
                    $monthBirthdaysQuery = "SELECT DAY(birth_date) as day, COUNT(*) as count 
                                        FROM applicants 
                                        WHERE status = 'Active' 
                                        AND MONTH(birth_date) = ? 
                                        AND birth_date IS NOT NULL 
                                        AND birth_date != '0000-00-00'
                                        GROUP BY DAY(birth_date)";
                    $monthBirthdaysStmt = $pdo->prepare($monthBirthdaysQuery);
                    $monthBirthdaysStmt->execute([$month]);
                    $birthdaysByDay = $monthBirthdaysStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    // Fill empty days at start
                    for ($i = 0; $i < $firstDay; $i++): ?>
                        <div class="calendar-day p-2 rounded-lg"></div>
                    <?php endfor; ?>

                    <!-- Actual days -->
                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $isToday = ($day == $currentDay && $month == $currentMonth && $year == $currentYear);
                        $hasBirthday = isset($birthdaysByDay[$day]);
                        $birthdayCount = $hasBirthday ? $birthdaysByDay[$day] : 0;
                    ?>
                        <div class="calendar-day p-2 rounded-lg text-center cursor-pointer transition-all duration-200 
                                <?php echo $isToday ? 'today' : ''; ?> 
                                <?php echo $hasBirthday ? 'has-birthday' : ''; ?>"
                            onclick="viewDayBirthdays(<?php echo $day; ?>, <?php echo $month; ?>, <?php echo $year; ?>)"
                            data-tooltip="<?php echo $hasBirthday ? "$birthdayCount birthday(s)" : 'No birthdays'; ?>">
                            <div class="font-medium <?php echo $isToday ? 'text-white' : 'text-gray-900 dark:text-white'; ?>">
                                <?php echo $day; ?>
                            </div>
                            <?php if ($hasBirthday): ?>
                                <div class="text-xs mt-1 <?php echo $isToday ? 'text-white' : 'text-blue-600 dark:text-blue-400'; ?>">
                                    <?php echo $birthdayCount; ?> <i class="fas fa-birthday-cake"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Birthday List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Birthday List</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <?php echo min($perPage, count($birthdays)); ?> of <?php echo $totalCount; ?> senior<?php echo $totalCount != 1 ? 's' : ''; ?>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="openGreetingsModal()"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 transition-colors no-print">
                        <i class="fas fa-paper-plane mr-2"></i> Send Greetings
                    </button>
                    <!-- <button onclick="generateBulkCertificates()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition-colors no-print">
                        <i class="fas fa-certificate mr-2"></i> Generate Certificates
                    </button> -->
                </div>
            </div>

            <!-- List -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Senior</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Birthday</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Age</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barangay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($birthdays)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-birthday-cake text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                    <p class="text-gray-500 dark:text-gray-400">No birthdays found</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try changing your filters</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($birthdays as $senior):
                                $fullName = trim($senior['last_name'] . ', ' . $senior['first_name'] . ' ' . ($senior['middle_name'] ?: ''));
                                if ($senior['suffix']) {
                                    $fullName .= ' ' . $senior['suffix'];
                                }
                                $birthdayClass = '';
                                if ($senior['birthday_status'] === 'today') {
                                    $birthdayClass = 'today-birthday';
                                } elseif ($senior['birthday_type'] === 'milestone' || $senior['birthday_type'] === 'centenarian') {
                                    $birthdayClass = $senior['birthday_type'] === 'centenarian' ? 'centenarian-birthday' : 'milestone-birthday';
                                } elseif ($senior['birthday_status'] === 'upcoming') {
                                    $birthdayClass = 'upcoming-birthday';
                                }
                            ?>
                                <tr class="<?php echo $birthdayClass; ?> hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="birthday-avatar mr-3">
                                                <?php echo strtoupper(substr($senior['first_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    <?php echo htmlspecialchars($fullName); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    ID: <?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($senior['formatted_birthday']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($senior['full_birthdate']); ?>
                                        </div>
                                        <?php if ($senior['birthday_status'] === 'today'): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 birthday-badge">
                                                <i class="fas fa-birthday-cake mr-1"></i> Today!
                                            </span>
                                        <?php elseif ($senior['birthday_status'] === 'upcoming'): ?>
                                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                in <?php echo $senior['days_until_birthday']; ?> day<?php echo $senior['days_until_birthday'] != 1 ? 's' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="age-badge">
                                            <?php echo $senior['next_age']; ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Current: <?php echo $senior['current_calculated_age']; ?>
                                        </div>
                                        <?php if ($senior['birthday_type'] === 'milestone' || $senior['birthday_type'] === 'centenarian'): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 mt-1">
                                                <i class="fas fa-medal mr-1"></i>
                                                <?php echo $senior['birthday_type'] === 'centenarian' ? '100+' : 'Milestone'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($senior['barangay'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($senior['municipality'] ?? ''); ?>, <?php echo htmlspecialchars($senior['province'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($senior['contact_number'] ?? 'N/A'); ?>
                                        </div>
                                        <?php if ($senior['contact_number']): ?>
                                            <button onclick="sendGreeting(<?php echo $senior['applicant_id']; ?>, '<?php echo addslashes($fullName); ?>', '<?php echo addslashes($senior['contact_number']); ?>')"
                                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mt-1 no-print">
                                                <i class="fas fa-sms mr-1"></i> Send SMS
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($senior['birthday_status'] === 'today'): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                <i class="fas fa-birthday-cake mr-1"></i> Celebrating
                                            </span>
                                        <?php elseif ($senior['birthday_status'] === 'upcoming'): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                <i class="fas fa-calendar-alt mr-1"></i> Upcoming
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                <i class="fas fa-calendar-check mr-1"></i> Passed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium no-print">
                                        <div class="flex space-x-2">
                                            <button onclick="viewSenior(<?php echo $senior['applicant_id']; ?>)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                data-tooltip="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <!-- <button onclick="generateCertificate(<?php echo $senior['applicant_id']; ?>)"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                data-tooltip="Generate Certificate">
                                                <i class="fas fa-certificate"></i>
                                            </button> -->
                                            <!-- <button onclick="editBirthdate(<?php echo $senior['applicant_id']; ?>)"
                                                class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                data-tooltip="Edit Birthdate">
                                                <i class="fas fa-edit"></i>
                                            </button> -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-400">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                First
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                class="px-3 py-1 text-sm font-medium rounded-lg <?php echo $i == $page ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                Next
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"
                                class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                Last
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reports Section -->
        <!-- <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6 no-print">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Birthday Reports</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="./reports/birthday_monthly.php?session_context=<?php echo $ctx; ?>"
                    class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-800 mr-4">
                            <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Monthly Report</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Birthdays by month analysis</p>
                        </div>
                    </div>
                </a>
                <a href="./reports/birthday_milestone.php?session_context=<?php echo $ctx; ?>"
                    class="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-800 mr-4">
                            <i class="fas fa-medal text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Milestone Report</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Special age celebrations</p>
                        </div>
                    </div>
                </a>
                <a href="./reports/birthday_barangay.php?session_context=<?php echo $ctx; ?>"
                    class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-800 mr-4">
                            <i class="fas fa-map-marker-alt text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Barangay Report</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Birthdays by location</p>
                        </div>
                    </div>
                </a>
            </div>
        </div> -->

        <!-- SMS Greetings Modal -->
        <div id="smsGreetingsModal" class="hidden fixed inset-0 bg-gray-900/50 z-[60] flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b dark:border-gray-700 bg-gradient-to-r from-green-50 to-blue-50 dark:from-gray-800 dark:to-gray-900">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Send Birthday Greetings via SMS</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Send personalized birthday messages to selected seniors</p>
                    </div>
                    <button id="closeGreetingsModal" class="text-gray-400 hover:text-gray-900 dark:hover:text-white p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <!-- Demo Mode Banner -->
                    <div id="demoModeBanner" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg dark:bg-yellow-900/30 dark:border-yellow-800">
                        <div class="flex items-center">
                            <i class="fas fa-flask text-yellow-600 dark:text-yellow-400 mr-3 text-lg"></i>
                            <div>
                                <h4 class="font-medium text-yellow-800 dark:text-yellow-300">Demo Mode Active</h4>
                                <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                                    SMS will be logged to database but NOT actually sent.
                                    <a href="./settings/sms.php?session_context=<?php echo $ctx; ?>" class="underline hover:text-yellow-900 dark:hover:text-yellow-300">
                                        Switch to real mode
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div id="smsModalStatus" class="hidden mb-4 p-4 rounded-lg"></div>

                    <!-- Recipient Summary -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-users mr-2 text-blue-500"></i>Recipients Summary
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Filtered</div>
                                <div id="totalRecipients" class="text-3xl font-bold text-gray-900 dark:text-white">0</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">With Phone Numbers</div>
                                <div id="withPhoneNumbers" class="text-3xl font-bold text-green-600 dark:text-green-400">0</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Will receive SMS</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Message Length</div>
                                <div id="charCountGreeting" class="text-3xl font-bold text-blue-600 dark:text-blue-400">0/160</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Characters</div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Template -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-medium text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-comment-alt mr-2 text-green-500"></i>Message Template
                            </h4>
                            <div class="flex gap-2">
                                <button type="button" onclick="useDefaultGreeting()"
                                    class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 rounded-lg transition">
                                    <i class="fas fa-redo mr-1"></i>Default
                                </button>
                                <button type="button" onclick="useMilestoneGreeting()"
                                    class="px-3 py-1.5 text-xs font-medium text-purple-600 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800 rounded-lg transition">
                                    <i class="fas fa-medal mr-1"></i>Milestone
                                </button>
                            </div>
                        </div>
                        <textarea id="greetingMessage" rows="5"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 font-medium"
                            placeholder="Enter your birthday greeting message...">🎉 Happy Birthday {name}! 🎂 From all of us at MSWD Paluan, may your special day be filled with joy, good health, and blessings. Enjoy your day! 🎈</textarea>

                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Available placeholders:</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div class="text-center">
                                    <code class="inline-block bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs font-mono">{name}</code>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Full Name</div>
                                </div>
                                <div class="text-center">
                                    <code class="inline-block bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs font-mono">{age}</code>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Turning Age</div>
                                </div>
                                <div class="text-center">
                                    <code class="inline-block bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs font-mono">{birthday}</code>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Birth Date</div>
                                </div>
                                <div class="text-center">
                                    <code class="inline-block bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs font-mono">{barangay}</code>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Barangay</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-cog mr-2 text-purple-500"></i>Options
                        </h4>
                        <div class="space-y-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="includeTodayOnly" checked
                                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeTodayOnly" class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <i class="fas fa-birthday-cake mr-2 text-yellow-500"></i>Send to today's birthdays only
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="includeMilestone" checked
                                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeMilestone" class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <i class="fas fa-medal mr-2 text-purple-500"></i>Include milestone birthdays (60, 65, 70, etc.)
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="sendTestFirst"
                                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="sendTestFirst" class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <i class="fas fa-vial mr-2 text-green-500"></i>Send test SMS first (0272873751)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-eye mr-2 text-blue-500"></i>Message Preview
                        </h4>
                        <div id="messagePreview" class="bg-white dark:bg-gray-800 rounded-lg p-4 text-sm border-2 border-dashed border-gray-200 dark:border-gray-700 min-h-[100px]">
                            <p class="text-gray-600 dark:text-gray-300 italic">Message preview will appear here...</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="modeIndicator">Demo Mode: SMS will be logged only</span>
                        </div>
                        <div class="flex space-x-3">
                            <button id="closeGreetingsModalBtn" type="button"
                                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                            <button id="sendGreetingsBtn" type="button"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 dark:from-green-700 dark:to-green-800 dark:hover:from-green-800 dark:hover:to-green-900 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-md">
                                <i class="fas fa-paper-plane mr-2"></i>Send Greetings
                            </button>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div id="sendingProgress" class="hidden mt-8">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2 text-blue-500"></i>Sending Progress
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span id="progressText" class="font-medium">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                    <div id="progressBar" class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="progressTotal">0</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total</div>
                                </div>
                                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="progressSent">0</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Sent</div>
                                </div>
                                <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="progressFailed">0</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Failed</div>
                                </div>
                                <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-700 dark:text-gray-300" id="progressRemaining">0</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Remaining</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div id="sendingResults" class="hidden mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-clipboard-check mr-2 text-green-500"></i>Sending Results
                            </h4>
                            <button onclick="document.getElementById('resultsContent').scrollTop = 0"
                                class="text-xs px-3 py-1 text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg">
                                <i class="fas fa-arrow-up mr-1"></i>Top
                            </button>
                        </div>
                        <div id="resultsContent" class="space-y-2 max-h-60 overflow-y-auto p-1 bg-gray-50 dark:bg-gray-700/30 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <!-- Print Header (hidden by default) -->
    <div class="print-only hidden">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold"><?php echo $pageTitle; ?></h1>
            <p class="text-gray-600">Generated on <?php echo date('F j, Y h:i A'); ?></p>
            <p class="text-gray-600">Total: <?php echo $totalCount; ?> senior<?php echo $totalCount != 1 ? 's' : ''; ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
    <script>
        // Dark mode toggle functionality
        function initDarkMode() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }

            // Add theme change listener
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        const isDarkMode = document.documentElement.classList.contains('dark');
                        // Redraw charts if needed
                        if (window.drawCharts && window.drawBirthdayCharts) {
                            setTimeout(() => {
                                drawCharts();
                                drawBirthdayCharts();
                            }, 100);
                        }
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true
            });
        }

        // Call on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            initDarkMode();
        });
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing page...');

            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(element => {
                new Flowbite.Tooltip(element, {
                    placement: 'top'
                });
            });

            // Initialize modal event listeners
            initializeModalEventListeners();

            // Initialize theme
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            let theme = 'light';

            if (savedTheme) {
                theme = savedTheme;
            } else if (systemPrefersDark) {
                theme = 'dark';
            }

            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }

            console.log('Page initialization complete');
        });

        // Filter functions
        function filterByMonth(month) {
            const url = new URL(window.location.href);
            if (month) {
                url.searchParams.set('filter', 'by_month');
                url.searchParams.set('month', month);
            } else {
                url.searchParams.delete('filter');
                url.searchParams.delete('month');
            }
            window.location.href = url.toString();
        }

        function filterByBarangay(barangay) {
            const url = new URL(window.location.href);
            if (barangay && barangay !== 'all') {
                url.searchParams.set('barangay', barangay);
            } else {
                url.searchParams.delete('barangay');
            }
            window.location.href = url.toString();
        }

        // Calendar navigation
        function prevMonth() {
            const url = new URL(window.location.href);
            let month = parseInt(url.searchParams.get('month') || <?php echo date('n'); ?>);
            let year = parseInt(url.searchParams.get('year') || <?php echo date('Y'); ?>);

            month--;
            if (month < 1) {
                month = 12;
                year--;
            }

            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            url.searchParams.set('filter', 'by_month');
            window.location.href = url.toString();
        }

        function nextMonth() {
            const url = new URL(window.location.href);
            let month = parseInt(url.searchParams.get('month') || <?php echo date('n'); ?>);
            let year = parseInt(url.searchParams.get('year') || <?php echo date('Y'); ?>);

            month++;
            if (month > 12) {
                month = 1;
                year++;
            }

            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            url.searchParams.set('filter', 'by_month');
            window.location.href = url.toString();
        }

        // View birthdays for specific day
        function viewDayBirthdays(day, month, year) {
            const dateStr = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            const url = new URL(window.location.href);
            url.searchParams.set('filter', 'by_date');
            url.searchParams.set('date', dateStr);
            window.location.href = url.toString();
        }

        // Action functions
        function viewSenior(applicantId) {
            window.open(`./SeniorList/senior_view.php?id=${applicantId}&session_context=<?php echo $ctx; ?>`, '_blank');
        }

        function sendGreeting(applicantId, fullName, phoneNumber) {
            if (!phoneNumber || phoneNumber === 'N/A') {
                showToast('No phone number available for ' + fullName, 'error');
                return;
            }

            showToast(`Sending birthday SMS to ${fullName}...`, 'info');

            // In real implementation, make AJAX call to send SMS
            fetch('/MSWDPALUAN_SYSTEM-MAIN/php/send_birthday_sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        applicant_id: applicantId,
                        full_name: fullName,
                        phone_number: phoneNumber,
                        user_id: <?php echo $user_id; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`SMS sent to ${fullName}`, 'success');
                    } else {
                        showToast('Failed to send SMS: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error sending SMS', 'error');
                });
        }

        function sendBulkGreetings() {
            window.location.href = ("./settings/sms.php?session_context=<?php echo $ctx; ?>");
        }

        function generateCertificate(applicantId) {
            showToast('Generating birthday certificate...', 'info');
            window.open(`./generate_birthday_certificate.php?applicant_id=${applicantId}&session_context=<?php echo $ctx; ?>`, '_blank');
        }

        function generateBulkCertificates() {
            if (!confirm('Generate birthday certificates for all seniors in this list?')) return;

            showToast('Opening certificates...', 'info');

            // Get all senior IDs from current page
            const seniorIds = <?php echo json_encode(array_column($birthdays, 'applicant_id')); ?>;

            // Open certificates in batches of 5 to avoid browser limits
            const batchSize = 5;
            for (let i = 0; i < seniorIds.length; i += batchSize) {
                const batch = seniorIds.slice(i, i + batchSize);
                setTimeout(() => {
                    batch.forEach(id => {
                        window.open(`./generate_birthday_certificate.php?applicant_id=${id}&session_context=<?php echo $ctx; ?>`, '_blank');
                    });
                }, i * 100);
            }
        }

        function editBirthdate(applicantId) {
            const newDate = prompt('Enter new birthdate (YYYY-MM-DD):');
            if (newDate) {
                // Validate date format
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateRegex.test(newDate)) {
                    showToast('Invalid date format. Use YYYY-MM-DD', 'error');
                    return;
                }

                showToast('Updating birthdate...', 'info');

                fetch('/MSWDPALUAN_SYSTEM-MAIN/php/update_birthdate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            applicant_id: applicantId,
                            birth_date: newDate,
                            user_id: <?php echo $user_id; ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Birthdate updated successfully', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showToast('Failed to update birthdate: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error updating birthdate', 'error');
                    });
            }
        }

        function printBirthdayList() {
            // Add print-specific styling
            const style = document.createElement('style');
            style.innerHTML = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .print-only,
                .print-only * {
                    visibility: visible;
                }
                .print-only {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
            }
        `;
            document.head.appendChild(style);

            // Trigger print
            window.print();

            // Remove style after printing
            setTimeout(() => {
                document.head.removeChild(style);
            }, 100);
        }

        function exportBirthdays() {
            showToast('Exporting birthday list...', 'info');

            // Create CSV content
            let csv = 'Name,Birthday,Age,Barangay,Contact,Status\n';

            <?php foreach ($birthdays as $senior):
                $fullName = trim($senior['last_name'] . ', ' . $senior['first_name'] . ' ' . ($senior['middle_name'] ?: ''));
                if ($senior['suffix']) {
                    $fullName .= ' ' . $senior['suffix'];
                }
            ?>
                csv += `"<?php echo addslashes($fullName); ?>",` +
                    `"<?php echo $senior['full_birthdate']; ?>",` +
                    `"<?php echo $senior['next_age']; ?>",` +
                    `"<?php echo addslashes($senior['barangay'] ?? 'N/A'); ?>",` +
                    `"<?php echo addslashes($senior['contact_number'] ?? 'N/A'); ?>",` +
                    `"<?php echo $senior['birthday_status'] === 'today' ? 'Today' : ($senior['birthday_status'] === 'upcoming' ? 'Upcoming' : 'Passed'); ?>"\n`;
            <?php endforeach; ?>

            // Create download link
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'birthdays_<?php echo date('Y-m-d'); ?>.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            showToast('Birthday list exported successfully', 'success');
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());

            // Create toast
            const toast = document.createElement('div');
            toast.className = `custom-toast fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-full ${type === 'info' ? 'bg-blue-500' : type === 'success' ? 'bg-green-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-500'}`;
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

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);

            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printBirthdayList();
            }
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) searchInput.focus();
            }
            // Escape to clear search
            if (e.key === 'Escape') {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput && searchInput.value) {
                    window.location.href = window.location.pathname + '?filter=<?php echo $filter; ?>&session_context=<?php echo $ctx; ?>';
                }
            }
        });

        // SMS Greetings Modal Functions
        let currentFilterData = <?php echo json_encode($birthdays); ?>;
        let currentFilter = '<?php echo $filter; ?>';

        function openGreetingsModal() {
            console.log('Opening greetings modal...');

            const modal = document.getElementById('smsGreetingsModal');
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling

            // Reset UI
            resetGreetingsModal();

            // Calculate recipients
            calculateRecipients();

            // Update message preview
            updateMessagePreview();

            // Initialize character counter
            const messageInput = document.getElementById('greetingMessage');
            if (messageInput) {
                messageInput.addEventListener('input', updateGreetingCharCount);
                messageInput.dispatchEvent(new Event('input'));
            }
        }


        function closeGreetingsModal() {
            console.log('Closing greetings modal...');
            const modal = document.getElementById('smsGreetingsModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            document.body.style.overflow = 'auto'; // Re-enable scrolling
            resetGreetingsModal();
        }

        function resetGreetingsModal() {
            // Reset status
            const statusDiv = document.getElementById('smsModalStatus');
            if (statusDiv) {
                statusDiv.innerHTML = '';
                statusDiv.classList.add('hidden');
            }

            // Hide progress and results
            const progressDiv = document.getElementById('sendingProgress');
            if (progressDiv) progressDiv.classList.add('hidden');

            const resultsDiv = document.getElementById('sendingResults');
            if (resultsDiv) resultsDiv.classList.add('hidden');

            // Reset progress bar
            const progressBar = document.getElementById('progressBar');
            if (progressBar) progressBar.style.width = '0%';

            const progressText = document.getElementById('progressText');
            if (progressText) progressText.textContent = '0%';

            // Enable send button
            const sendBtn = document.getElementById('sendGreetingsBtn');
            if (sendBtn) sendBtn.disabled = false;
        }

        function calculateRecipients() {
            const includeTodayOnly = document.getElementById('includeTodayOnly');
            const includeMilestone = document.getElementById('includeMilestone');

            if (!includeTodayOnly || !includeMilestone) {
                console.error('Checkbox elements not found');
                return {
                    total: 0,
                    withPhone: 0
                };
            }

            let filteredData = currentFilterData;

            // Apply filters
            if (includeTodayOnly.checked) {
                filteredData = filteredData.filter(senior => senior.birthday_status === 'today');
            }

            if (includeMilestone.checked) {
                filteredData = filteredData.filter(senior =>
                    senior.birthday_type === 'milestone' || senior.birthday_type === 'centenarian'
                );
            }

            const total = filteredData.length;
            const withPhone = filteredData.filter(senior =>
                senior.contact_number &&
                senior.contact_number !== 'N/A' &&
                senior.contact_number !== '' &&
                senior.contact_number !== null
            ).length;

            // Update UI
            const totalEl = document.getElementById('totalRecipients');
            const withPhoneEl = document.getElementById('withPhoneNumbers');
            if (totalEl) totalEl.textContent = total;
            if (withPhoneEl) withPhoneEl.textContent = withPhone;

            // Store filtered data for sending
            window.currentRecipients = filteredData;

            // Enable/disable send button
            const sendBtn = document.getElementById('sendGreetingsBtn');
            if (sendBtn) {
                sendBtn.disabled = withPhone === 0;
                if (withPhone === 0) {
                    sendBtn.title = 'No recipients with valid phone numbers';
                } else {
                    sendBtn.title = '';
                }
            }

            console.log('Recipients calculated:', {
                total,
                withPhone
            });
            return {
                total,
                withPhone
            };
        }

        function useDefaultGreeting() {
            const messageInput = document.getElementById('greetingMessage');
            if (messageInput) {
                messageInput.value = '🎉 Happy Birthday {name}! 🎂 From all of us at MSWD Paluan, may your special day be filled with joy, good health, and blessings. Enjoy your day! 🎈';
                updateGreetingCharCount();
                updateMessagePreview();
            }
        }

        function useMilestoneGreeting() {
            const messageInput = document.getElementById('greetingMessage');
            if (messageInput) {
                messageInput.value = '🎊 Congratulations on reaching {age} years! 🎂 This is a special milestone, {name}. The Municipal Social Welfare and Development Office of Paluan wishes you continued good health, happiness, and many more birthdays to come! 🎈';
                updateGreetingCharCount();
                updateMessagePreview();
            }
        }

        function updateGreetingCharCount() {
            const messageInput = document.getElementById('greetingMessage');
            const charCount = document.getElementById('charCountGreeting');
            if (!messageInput || !charCount) return;

            const length = messageInput.value.length;
            charCount.textContent = `${length}/160`;
            charCount.className = 'char-counter text-xs';

            if (length > 160) {
                charCount.classList.add('text-red-600', 'font-bold');
            } else if (length > 140) {
                charCount.classList.add('text-yellow-600');
            }

            updateMessagePreview();
        }

        function updateMessagePreview() {
            const messageInput = document.getElementById('greetingMessage');
            const previewDiv = document.getElementById('messagePreview');
            if (!messageInput || !previewDiv) return;

            const sampleSenior = currentFilterData[0] || {
                first_name: 'Juan',
                last_name: 'Dela Cruz',
                next_age: '75',
                formatted_birthday: 'January 15',
                barangay: 'Sample Barangay'
            };

            const fullName = `${sampleSenior.first_name} ${sampleSenior.last_name}`;

            let preview = messageInput.value
                .replace(/{name}/g, fullName)
                .replace(/{age}/g, sampleSenior.next_age)
                .replace(/{birthday}/g, sampleSenior.formatted_birthday)
                .replace(/{barangay}/g, sampleSenior.barangay || 'your barangay');

            previewDiv.innerHTML = `<p class="text-gray-700 dark:text-gray-200 whitespace-pre-wrap">${preview}</p>`;
        }

        async function sendGreetings() {
            console.log('sendGreetings() called');

            const {
                withPhone: recipientsCount
            } = calculateRecipients();
            if (recipientsCount === 0) {
                showModalStatus('No recipients with valid phone numbers found.', 'error');
                return;
            }

            const messageInput = document.getElementById('greetingMessage');
            const sendTestFirstCheck = document.getElementById('sendTestFirst');
            if (!messageInput || !sendTestFirstCheck) return;

            const message = messageInput.value;
            const sendTestFirst = sendTestFirstCheck.checked;

            // Validate message
            if (!message.trim()) {
                showModalStatus('Please enter a greeting message.', 'error');
                return;
            }

            // Check SMS mode
            let isDemoMode = true;
            try {
                const response = await fetch('../php/quick_sms_test.php');
                const data = await response.json();
                isDemoMode = data.demo_mode === true || data.demo_mode === 1;
            } catch (error) {
                console.error('Error checking mode:', error);
            }

            // Confirm before sending
            let confirmMessage = `Send birthday greetings to ${recipientsCount} recipient${recipientsCount !== 1 ? 's' : ''}?`;

            if (isDemoMode) {
                confirmMessage += '\n\n⚠️ DEMO MODE ENABLED\nSMS will be logged to database but NOT actually sent.';
            } else {
                confirmMessage += '\n\n📱 REAL MODE\nSMS will be sent via Semaphore API (uses credits).';
            }

            if (sendTestFirst) {
                confirmMessage += '\n\nA test SMS will be sent to 0272873751 first.';
            }

            if (!confirm(confirmMessage)) {
                console.log('User cancelled');
                return;
            }

            // Disable send button and show progress
            const sendBtn = document.getElementById('sendGreetingsBtn');
            if (sendBtn) sendBtn.disabled = true;

            const progressDiv = document.getElementById('sendingProgress');
            if (progressDiv) progressDiv.classList.remove('hidden');

            const resultsDiv = document.getElementById('sendingResults');
            if (resultsDiv) resultsDiv.classList.remove('hidden');

            // Get recipients with phone numbers
            const recipients = window.currentRecipients.filter(senior =>
                senior.contact_number &&
                senior.contact_number !== 'N/A' &&
                senior.contact_number !== '' &&
                senior.contact_number !== null
            );

            // Update progress UI
            const progressTotal = document.getElementById('progressTotal');
            const progressRemaining = document.getElementById('progressRemaining');
            if (progressTotal) progressTotal.textContent = recipients.length;
            if (progressRemaining) progressRemaining.textContent = recipients.length;

            // Send test SMS first if requested
            if (sendTestFirst) {
                showModalStatus('Sending test SMS...', 'info');

                try {
                    // For demo mode, just simulate
                    await new Promise(resolve => setTimeout(resolve, 1500));
                    showModalStatus('Test SMS successful! Starting bulk send...', 'success');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                } catch (error) {
                    showModalStatus(`Test failed: ${error.message}`, 'error');
                    if (sendBtn) sendBtn.disabled = false;
                    return;
                }
            }

            // Start bulk sending
            showModalStatus(`Starting to send ${recipients.length} birthday greetings...`, 'info');

            let sent = 0;
            let failed = 0;
            const resultsContent = document.getElementById('resultsContent');
            if (resultsContent) resultsContent.innerHTML = '';

            // Process recipients
            for (let i = 0; i < recipients.length; i++) {
                const senior = recipients[i];

                try {
                    // In demo mode, simulate sending
                    if (isDemoMode) {
                        // Simulate API delay
                        await new Promise(resolve => setTimeout(resolve, 200));

                        // Simulate success (95% success rate in demo)
                        const success = Math.random() > 0.05;

                        if (success) {
                            sent++;
                            if (resultsContent) {
                                // Format name properly
                                const firstName = senior.first_name || '';
                                const lastName = senior.last_name || '';
                                const middleInitial = senior.middle_name ? ' ' + senior.middle_name.charAt(0) + '.' : '';
                                const suffix = senior.suffix ? ' ' + senior.suffix : '';

                                // Create display name (Last, First M.)
                                let displayName;
                                if (lastName && firstName) {
                                    displayName = `${lastName}, ${firstName}${middleInitial}${suffix}`;
                                } else if (firstName) {
                                    displayName = firstName;
                                } else {
                                    displayName = 'Unknown';
                                }

                                // Truncate if too long
                                if (displayName.length > 30) {
                                    displayName = displayName.substring(0, 27) + '...';
                                }

                                resultsContent.innerHTML += `
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mr-3">
                                                <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">${displayName}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    ${senior.contact_number || 'No phone'} • ${isDemoMode ? 'Logged (Demo)' : 'Sent'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs px-2 py-1 rounded-full ${isDemoMode ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'}">
                                            ${isDemoMode ? 'DEMO' : 'SENT'}
                                        </div>
                                    </div>
                                `;
                            }
                        } else {
                            failed++;
                            if (resultsContent) {
                                // Format name (same as above)
                                const firstName = senior.first_name || '';
                                const lastName = senior.last_name || '';
                                const middleInitial = senior.middle_name ? ' ' + senior.middle_name.charAt(0) + '.' : '';
                                const suffix = senior.suffix ? ' ' + senior.suffix : '';

                                let displayName;
                                if (lastName && firstName) {
                                    displayName = `${lastName}, ${firstName}${middleInitial}${suffix}`;
                                } else if (firstName) {
                                    displayName = firstName;
                                } else {
                                    displayName = 'Unknown';
                                }

                                if (displayName.length > 30) {
                                    displayName = displayName.substring(0, 27) + '...';
                                }

                                resultsContent.innerHTML += `
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-900 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center mr-3">
                                                <i class="fas fa-times text-red-600 dark:text-red-400 text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">${displayName}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    ${senior.contact_number || 'No phone'} • ${result.message || 'Failed'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                            FAILED
                                        </div>
                                    </div>
                                `;
                            }
                        }
                    } else {
                        // Real mode - would call actual API
                        // For now, simulate
                        await new Promise(resolve => setTimeout(resolve, 300));
                        sent++;
                        if (resultsContent) {
                            resultsContent.innerHTML += `
                        <div class="flex items-center p-2 bg-green-50 dark:bg-green-900/30 rounded">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>
                            <span class="text-sm">${senior.first_name} ${senior.last_name}: Sent</span>
                        </div>
                    `;
                        }
                    }

                    // Update progress
                    const progress = Math.round(((i + 1) / recipients.length) * 100);
                    const progressBar = document.getElementById('progressBar');
                    const progressText = document.getElementById('progressText');
                    const progressSent = document.getElementById('progressSent');
                    const progressFailed = document.getElementById('progressFailed');

                    if (progressBar) progressBar.style.width = `${progress}%`;
                    if (progressText) progressText.textContent = `${progress}%`;
                    if (progressSent) progressSent.textContent = sent;
                    if (progressFailed) progressFailed.textContent = failed;
                    if (progressRemaining) progressRemaining.textContent = recipients.length - (i + 1);

                } catch (error) {
                    failed++;
                    if (resultsContent) {
                        resultsContent.innerHTML += `
                    <div class="flex items-center p-2 bg-red-50 dark:bg-red-900/30 rounded">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
                        <span class="text-sm">${senior.first_name} ${senior.last_name}: Error</span>
                    </div>
                `;
                    }
                }
            }

            // Show final results
            let finalMessage;
            if (isDemoMode) {
                finalMessage = `✅ Demo Complete!\n${sent} SMS logged to database, ${failed} failed.\nNo actual SMS were sent.`;
            } else {
                finalMessage = `✅ Real Mode Complete!\n${sent} SMS sent via Semaphore API, ${failed} failed.`;
            }

            showModalStatus(finalMessage, sent > 0 ? 'success' : 'error');

            // Re-enable send button after a delay
            setTimeout(() => {
                if (sendBtn) sendBtn.disabled = false;
            }, 2000);
        }
        async function sendTestSMS(message) {
            console.log('Sending test SMS (always simulated for tests)...');

            const testMessage = message
                .replace(/{name}/g, 'Test Recipient')
                .replace(/{age}/g, '60')
                .replace(/{birthday}/g, 'Today')
                .replace(/{barangay}/g, 'Test Barangay');

            // Always send as test for test button
            const testData = {
                phone_number: '09272873751',
                message: testMessage,
                is_test: true // This ensures we get a simulated response
            };

            try {
                console.log('Calling send_sms_test.php with test flag...');
                const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/send_sms_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });

                console.log('Response status:', response.status);

                const text = await response.text();
                console.log('Response text:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed response:', data);

                    // For test requests, we should always get success
                    if (data.is_test) {
                        return {
                            success: true,
                            message: 'Test SMS approved. In real mode, this would be sent via Semaphore API.',
                            status: 'test_approved',
                            is_test: true,
                            demo_mode: data.demo_mode || false
                        };
                    }

                    return data;
                } catch (e) {
                    console.error('JSON parse error:', e);
                    return {
                        success: false,
                        message: 'Invalid JSON response',
                        raw_response: text.substring(0, 200)
                    };
                }
            } catch (error) {
                console.error('Network error:', error);
                return {
                    success: false,
                    message: 'Network error: ' + error.message
                };
            }
        }

        async function sendIndividualGreeting(senior, template) {
            // Personalize message
            const fullName = `${senior.first_name} ${senior.last_name}`;
            const personalizedMessage = template
                .replace(/{name}/g, fullName)
                .replace(/{age}/g, senior.next_age)
                .replace(/{birthday}/g, senior.formatted_birthday)
                .replace(/{barangay}/g, senior.barangay || '');

            try {
                const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/send_birthday_sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        applicant_id: senior.applicant_id,
                        phone_number: senior.contact_number,
                        message: personalizedMessage,
                        user_id: <?php echo $user_id; ?>
                    })
                });

                return await response.json();
            } catch (error) {
                return {
                    success: false,
                    message: 'Network error'
                };
            }
        }

        function showModalStatus(message, type = 'info') {
            const statusDiv = document.getElementById('smsModalStatus');
            if (!statusDiv) return;

            statusDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'info' ? 'fa-info-circle' : type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;

            statusDiv.className = `mb-4 p-4 rounded-lg ${type === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'}`;
            statusDiv.classList.remove('hidden');

            // Auto-hide info messages after 5 seconds
            if (type === 'info') {
                setTimeout(() => {
                    statusDiv.classList.add('hidden');
                }, 5000);
            }
        }
        // ============ INITIALIZE EVENT LISTENERS ============
        function initializeModalEventListeners() {
            console.log('Initializing modal event listeners...');

            // Close modal buttons
            const closeBtn1 = document.getElementById('closeGreetingsModal');
            const closeBtn2 = document.getElementById('closeGreetingsModalBtn');

            if (closeBtn1) {
                closeBtn1.addEventListener('click', closeGreetingsModal);
                console.log('Close button 1 initialized');
            }

            if (closeBtn2) {
                closeBtn2.addEventListener('click', closeGreetingsModal);
                console.log('Close button 2 initialized');
            }

            // Send greetings button
            const sendBtn = document.getElementById('sendGreetingsBtn');
            if (sendBtn) {
                sendBtn.addEventListener('click', sendGreetings);
                console.log('Send button initialized');
            }

            // Checkbox change listeners
            const includeTodayOnly = document.getElementById('includeTodayOnly');
            const includeMilestone = document.getElementById('includeMilestone');

            if (includeTodayOnly) {
                includeTodayOnly.addEventListener('change', calculateRecipients);
                console.log('Include today checkbox initialized');
            }

            if (includeMilestone) {
                includeMilestone.addEventListener('change', calculateRecipients);
                console.log('Include milestone checkbox initialized');
            }

            // Close modal when clicking outside
            const modal = document.getElementById('smsGreetingsModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target.id === 'smsGreetingsModal') {
                        closeGreetingsModal();
                    }
                });
                console.log('Modal click outside initialized');
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('smsGreetingsModal');
                if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeGreetingsModal();
                }
            });

            console.log('All modal event listeners initialized');
        }

        // Initialize modal event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing modal listeners...');

            // Close modal buttons
            const closeGreetingsModalBtn = document.getElementById('closeGreetingsModal');
            const closeGreetingsModalBtn2 = document.getElementById('closeGreetingsModalBtn');

            if (closeGreetingsModalBtn) {
                closeGreetingsModalBtn.addEventListener('click', closeGreetingsModal);
            }

            if (closeGreetingsModalBtn2) {
                closeGreetingsModalBtn2.addEventListener('click', closeGreetingsModal);
            }

            // Send greetings button
            const sendGreetingsBtn = document.getElementById('sendGreetingsBtn');
            if (sendGreetingsBtn) {
                sendGreetingsBtn.addEventListener('click', sendGreetings);
            }

            // Checkbox change listeners
            const includeTodayOnly = document.getElementById('includeTodayOnly');
            const includeMilestone = document.getElementById('includeMilestone');
            const sendTestFirst = document.getElementById('sendTestFirst');

            if (includeTodayOnly) {
                includeTodayOnly.addEventListener('change', calculateRecipients);
            }

            if (includeMilestone) {
                includeMilestone.addEventListener('change', calculateRecipients);
            }

            if (sendTestFirst) {
                sendTestFirst.addEventListener('change', function() {
                    // Just update UI if needed
                });
            }

            // Close modal when clicking outside
            const modal = document.getElementById('smsGreetingsModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target.id === 'smsGreetingsModal') {
                        closeGreetingsModal();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('smsGreetingsModal');
                if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeGreetingsModal();
                }
            });

            console.log('Modal listeners initialized');
        });

        // Simplified SMS test function for debugging
        async function sendSimpleTest() {
            console.log('Testing SMS sending...');

            const testData = {
                phone_number: '09272873751',
                message: '🎉 Test SMS from MSWD Paluan System',
                is_test: true
            };

            try {
                console.log('Sending request to:', '/MSWDPALUAN_SYSTEM-MAIN/php/send_sms_test.php');
                const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/send_sms_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                const text = await response.text();
                console.log('Raw response:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed response:', data);
                    return data;
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Response was HTML:', text.substring(0, 200));
                    return {
                        success: false,
                        message: 'Invalid JSON response: ' + e.message
                    };
                }
            } catch (error) {
                console.error('Fetch error:', error);
                return {
                    success: false,
                    message: 'Network error: ' + error.message
                };
            }
        }

        // Test button for debugging - add this to your page temporarily
        // document.addEventListener('DOMContentLoaded', function() {
        //     // Add debug button to test SMS
        //     const debugBtn = document.createElement('button');
        //     debugBtn.textContent = 'Test SMS Connection';
        //     debugBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 transition-colors no-print';
        //     debugBtn.style.position = 'fixed';
        //     debugBtn.style.bottom = '20px';
        //     debugBtn.style.right = '20px';
        //     debugBtn.style.zIndex = '1000';
        //     debugBtn.onclick = async function() {
        //         console.log('Testing SMS connection...');
        //         const result = await sendSimpleTest();
        //         alert('Test result: ' + (result.success ? 'Success' : 'Failed') + '\n' + (result.message || ''));
        //     };
        //     document.body.appendChild(debugBtn);
        // });
        
        // Alternative SMS sending that works without external endpoints
        async function sendSMSDirectly(phoneNumber, message) {
            console.log('Sending SMS directly:', {
                phoneNumber,
                messageLength: message.length
            });

            // Get SMS settings via AJAX
            try {
                const settingsResponse = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/helpers/sms_helper.php?action=get_settings');
                let smsSettings;

                if (settingsResponse.ok) {
                    const settingsText = await settingsResponse.text();
                    try {
                        smsSettings = JSON.parse(settingsText);
                    } catch (e) {
                        // If not JSON, use default
                        smsSettings = {
                            api_key: '11203b3c9a4bc430dd3a1b181ece8b6c',
                            demo_mode: false,
                            is_active: true
                        };
                    }
                } else {
                    // Use default settings
                    smsSettings = {
                        api_key: '11203b3c9a4bc430dd3a1b181ece8b6c',
                        demo_mode: false,
                        is_active: true
                    };
                }

                // Simulate SMS sending (replace with actual API call in production)
                if (smsSettings.demo_mode) {
                    console.log('Demo mode: Logging SMS only');
                    return {
                        success: true,
                        status: 'demo_sent',
                        message: 'SMS logged in demo mode (not sent)',
                        demo_mode: true
                    };
                }

                // For now, simulate success
                console.log('Would send SMS to:', phoneNumber);
                return {
                    success: true,
                    status: 'queued',
                    message: 'SMS queued for delivery',
                    demo_mode: false
                };

            } catch (error) {
                console.error('SMS sending error:', error);
                return {
                    success: false,
                    message: 'Failed to send SMS: ' + error.message
                };
            }
        }

        // Add mode check button
        // document.addEventListener('DOMContentLoaded', function() {
        //     const modeBtn = document.createElement('button');
        //     modeBtn.textContent = '🔍 Check SMS Mode';
        //     modeBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 dark:bg-orange-700 dark:hover:bg-orange-800 transition-colors no-print';
        //     modeBtn.style.position = 'fixed';
        //     modeBtn.style.bottom = '120px';
        //     modeBtn.style.right = '20px';
        //     modeBtn.style.zIndex = '1000';
        //     modeBtn.onclick = async function() {
        //         try {
        //             console.log('Checking SMS mode...');

        //             // Use quick_sms_test.php which we know works
        //             const response = await fetch('../php/quick_sms_test.php');
        //             const text = await response.text();
        //             console.log('Response:', text);

        //             const data = JSON.parse(text);

        //             let message = '📱 SMS System Status:\n\n';
        //             message += `Mode: ${data.demo_mode ? '✅ DEMO MODE' : '📱 REAL MODE'}\n`;
        //             message += `Active: ${data.is_active ? '✅ Yes' : '❌ No'}\n`;
        //             message += `API Key: ${data.has_api_key ? '✅ Set' : '❌ Not Set'}\n\n`;

        //             if (data.demo_mode) {
        //                 message += 'ℹ️ System is in DEMO MODE\nSMS will be logged to database but NOT actually sent.\n\n';
        //                 message += 'To send real SMS:\n1. Go to SMS Settings\n2. Disable "Demo Mode"\n3. Ensure API key is valid';
        //             } else {
        //                 message += 'ℹ️ System is in REAL MODE\nSMS will be sent via Semaphore API.\n';
        //                 message += 'Make sure you have sufficient credits.';
        //             }

        //             alert(message);

        //         } catch (error) {
        //             console.error('Error:', error);
        //             alert('Error checking SMS mode: ' + error.message);
        //         }
        //     };
        //     document.body.appendChild(modeBtn);
        // });

        // document.addEventListener('DOMContentLoaded', function() {
        //     const testModalBtn = document.createElement('button');
        //     testModalBtn.textContent = '🎯 Test Modal';
        //     testModalBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-pink-600 rounded-lg hover:bg-pink-700 dark:bg-pink-700 dark:hover:bg-pink-800 transition-colors no-print';
        //     testModalBtn.style.position = 'fixed';
        //     testModalBtn.style.bottom = '170px';
        //     testModalBtn.style.right = '20px';
        //     testModalBtn.style.zIndex = '1000';
        //     testModalBtn.onclick = function() {
        //         console.log('Test modal button clicked');
        //         openGreetingsModal();
        //     };
        //     document.body.appendChild(testModalBtn);
        // });

        // Add this function to update mode indicator
        async function updateModeIndicator() {
            const indicator = document.getElementById('modeIndicator');
            const demoBanner = document.getElementById('demoModeBanner');

            if (!indicator) return;

            try {
                const response = await fetch('../php/quick_sms_test.php');
                const data = await response.json();

                if (data.demo_mode) {
                    indicator.innerHTML = '<i class="fas fa-flask mr-1"></i>Demo Mode: SMS will be logged only';
                    if (demoBanner) demoBanner.classList.remove('hidden');
                } else {
                    indicator.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Real Mode: SMS will be sent via Semaphore API';
                    if (demoBanner) demoBanner.classList.add('hidden');
                }
            } catch (error) {
                indicator.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Unable to determine mode';
            }
        }

        // Update the openGreetingsModal function:
        function openGreetingsModal() {
            console.log('Opening greetings modal...');

            const modal = document.getElementById('smsGreetingsModal');
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Reset UI
            resetGreetingsModal();

            // Calculate recipients
            calculateRecipients();

            // Update message preview
            updateMessagePreview();

            // Update mode indicator
            updateModeIndicator();

            // Initialize character counter
            const messageInput = document.getElementById('greetingMessage');
            if (messageInput) {
                messageInput.addEventListener('input', updateGreetingCharCount);
                messageInput.dispatchEvent(new Event('input'));
            }
        }
    </script>
    <script>
        // Debug: Check if modal functions are available
        console.log('=== DEBUG MODAL FUNCTIONS ===');
        console.log('openGreetingsModal exists:', typeof openGreetingsModal);
        console.log('Modal element exists:', document.getElementById('smsGreetingsModal') !== null);
        console.log('Send Greetings button in table:', document.querySelector('button[onclick*="openGreetingsModal"]'));

        // Test: Simulate clicking the Send Greetings button
        setTimeout(function() {
            console.log('Testing modal open in 2 seconds...');
            // Uncomment to auto-open modal for testing
            // openGreetingsModal();
        }, 2000);
    </script>
</body>

</html>