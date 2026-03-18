<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

$currentYear = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$currentMonth = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;

// Validate parameters
if ($currentMonth !== null && ($currentMonth < 1 || $currentMonth > 12)) {
    $currentMonth = null;
}
if ($currentYear !== null && ($currentYear < 2000 || $currentYear > 2100)) {
    $currentYear = null;
}

$servername = "localhost";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";
$dbname = "u401132124_mswd_seniors";

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Part - VI</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
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
    </style>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
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
                        <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                            <li>
                                <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
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
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <i class="fas fa-chart-bar w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
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
            <div class="w-full flex justify-between items-center mb-4">
                <div><?php require_once "../../php/reports/date_filter_component.php"; ?></div>
                <button type="button" onclick="generateReport()"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-2 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    Generate Report
                </button>
            </div>
            <div class="w-full items-center justify-center">
                <h4 class="text-2xl font-bold dark:text-white text-center">Monthly Reports</h4>
            </div>
            <div class="flex flex-row items-center justify-center gap-5 mt-2">
                <?php
                // Calculate display text
                $displayText = 'All Time';
                if ($currentYear && $currentMonth) {
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
                    $displayText = $monthNames[$currentMonth] . ' ' . $currentYear;
                } elseif ($currentYear) {
                    $displayText = 'Year ' . $currentYear;
                } elseif ($currentMonth) {
                    $displayText = $monthNames[$currentMonth] . ' (All Years)';
                }
                ?>
                <h4 class="text-xl font-medium dark:text-white px-2 text-center" id="reportPeriod">
                    <?php echo htmlspecialchars($displayText); ?>
                </h4>
                <div>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part1()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        I
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part2()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        II
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part3()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        III
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part4()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        IV
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part5()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        V
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part6()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-white focus:outline-none bg-blue-700 rounded-sm border border-gray-200 dark:bg-blue-700 dark:text-white">
                        VI
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part7to9()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        VII-IX
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="benefits()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Benefits
                    </button>
                </div>
            </div>
            <!-- Part VI -->
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                        <div class="flex flex-col md:flex-row items-center justify-between p-2">
                            <h4 class="text-lg font-medium dark:text-white"
                                style="font-family: 'Times New Roman', Times, serif;">VI. List of Deceased Registered Senior Citizens</h4>
                            <div class="flex items-center w-full space-x-3 md:w-auto">
                                <button id="filterDropdownButton2" data-dropdown-toggle="filterDropdown2"
                                    class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg md:w-auto  hover:bg-gray-100 hover:text-primary-700 f dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                    type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                        class="w-4 h-4 mr-2 text-gray-400" viewbox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Filter by Year
                                    <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                    </svg>
                                </button>
                                <!-- Dropdown menu will be populated dynamically -->
                                <div id="filterDropdown2"
                                    class="z-10 hidden w-30 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                                    <ul class="space-y-2 text-sm" id="yearFilterList" aria-labelledby="dropdownDefault2">
                                        <!-- Years will be loaded dynamically -->
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                                onclick="applyYearFilter(null)">
                                                All Years</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center">
                                    <span class="text-gray-600 dark:text-gray-300 mr-2">Total Deceased Seniors:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" id="totalDeceased">0</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 dark:text-gray-300 mr-2">Selected Period:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" id="selectedPeriod">All Time</span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="deceasedTable"
                                class="w-full text-sm text-center text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600">
                                <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                    <tr class="flex w-full  border-gray-300 dark:border-gray-600">
                                        <th scope="col" class="px-4 py-3 flex w-full text-sm text-left">Name</th>
                                        <th scope="col"
                                            class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Date of Death</th>
                                        <th scope="col"
                                            class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Barangay</th>
                                    </tr>
                                </thead>
                                <tbody id="deceasedBody" class="block max-h-80 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] 
                                        [&::-webkit-scrollbar]:hidden">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-center">Loading data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
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
    <script>
        // Update navigation functions to preserve filters
        window.part1 = function() {
            navigateToReport('report.php');
        };

        window.part2 = function() {
            navigateToReport('reportpart2.php');
        };

        window.part3 = function() {
            navigateToReport('reportpart3.php');
        };

        window.part4 = function() {
            navigateToReport('reportpart4.php');
        };

        window.part5 = function() {
            navigateToReport('reportpart5.php');
        };

        window.part6 = function() {
            // Already on part 6, reload with current filters
            window.location.reload();
        };

        window.part7to9 = function() {
            navigateToReport('reportpart7to9.php');
        };

        window.benefits = function() {
            navigateToReport('reportbenefits.php');
        };

        // Helper to navigate between report pages
        function navigateToReport(pageName) {
            let url = pageName;
            const params = new URLSearchParams();

            // Get session context from current URL
            const currentUrl = new URLSearchParams(window.location.search);
            const sessionContext = currentUrl.get('session_context');

            if (sessionContext) {
                params.append('session_context', sessionContext);
            }

            // Add current filters
            const currentYear = <?php echo isset($currentYear) && $currentYear ? json_encode($currentYear) : 'null'; ?>;
            const currentMonth = <?php echo isset($currentMonth) && $currentMonth ? json_encode($currentMonth) : 'null'; ?>;

            if (currentYear && currentYear !== 'null') {
                params.append('year', currentYear);
            }
            if (currentMonth && currentMonth !== 'null') {
                params.append('month', currentMonth);
            }

            const queryString = params.toString();
            if (queryString) {
                url += '?' + queryString;
            }

            console.log('Navigating to:', url);
            window.location.href = url;
        }

        // Part VI specific functions
        (function() {
            'use strict';

            console.log('Part VI page initialized');

            // Get current filter values from PHP
            const currentYear = <?php echo isset($currentYear) && $currentYear ? json_encode($currentYear) : 'null'; ?>;
            const currentMonth = <?php echo isset($currentMonth) && $currentMonth ? json_encode($currentMonth) : 'null'; ?>;

            // Load data when page is ready
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded. Loading Part VI data...');
                fetchDeceasedSeniors();
            });

            // Main function to fetch deceased seniors
            async function fetchDeceasedSeniors() {
                try {
                    showLoading();

                    // Build API URL
                    let apiUrl = '../../php/reports/report_part6_backend.php';
                    apiUrl += '?';

                    // Add filters if provided
                    const params = new URLSearchParams();

                    if (currentYear && currentYear !== 'null') {
                        params.append('year', currentYear);
                    }
                    if (currentMonth && currentMonth !== 'null') {
                        params.append('month', currentMonth);
                    }

                    apiUrl += params.toString();

                    // Add cache busting
                    apiUrl += '&_=' + Date.now();

                    console.log('Calling API:', apiUrl);

                    // Fetch data
                    const response = await fetch(apiUrl);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();
                    console.log('API Response:', result);

                    // Display the data
                    if (result.success) {
                        displayDeceasedSeniors(result);
                        updateDeceasedSummary(result);
                        populateYearFilter(result.available_years || [], result.year_counts || []);
                    } else {
                        throw new Error(result.message || 'Unknown error from API');
                    }

                } catch (error) {
                    console.error('Error loading deceased seniors:', error);
                    showError('Failed to load data: ' + error.message);
                }
            }

            // Function to display deceased seniors in the table
            function displayDeceasedSeniors(data) {
                const tbody = document.getElementById('deceasedBody');
                if (!tbody) {
                    console.error('Table body not found!');
                    return;
                }

                // Clear existing content
                tbody.innerHTML = '';

                // Check if we have data
                if (!data.data || data.data.length === 0) {
                    tbody.innerHTML = `
        <tr>
            <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg">No deceased seniors found</p>
                <p class="text-sm mt-1">No deceased seniors in the selected period</p>
            </td>
        </tr>
    `;
                    return;
                }

                console.log('Displaying', data.data.length, 'records');

                // Create rows for each senior
                data.data.forEach((item, index) => {
                    const row = document.createElement('tr');
                    row.className = 'flex w-full font-semibold dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700';

                    // Format name properly
                    let fullName = '';
                    if (item.last_name && item.first_name) {
                        fullName = `${item.first_name} ${item.middle_name || ''} ${item.last_name}`.trim();
                    } else {
                        fullName = item.full_name || 'Unknown Name';
                    }

                    // Format date of death
                    let deathDate = 'N/A';
                    if (item.deceased_date && item.deceased_date !== '0000-00-00') {
                        const dateObj = new Date(item.deceased_date);
                        if (!isNaN(dateObj)) {
                            // Format as DD-MM-YYYY
                            const day = String(dateObj.getDate()).padStart(2, '0');
                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const year = dateObj.getFullYear();
                            deathDate = `${day}-${month}-${year}`;
                        }
                    }

                    row.innerHTML = `
            <td class="px-4 py-3 flex w-full border-b-0 borde-t-0 border-l-0 border-r-0 text-left border border-gray-300 dark:border-gray-600" 
                style="font-family: 'Times New Roman', Times, serif;">
                ${index + 1}. ${fullName}
            </td>
            <td class="px-4 py-3 flex-l w-full border-b-0 borde-t-0 border-r-0 text-center border border-gray-300 dark:border-gray-600">
                ${deathDate}
            </td>
            <td class="px-4 py-3 flex-l w-full border-b-0 borde-t-0 border-r-0 text-center border border-gray-300 dark:border-gray-600"
                style="font-family: 'Times New Roman', Times, serif;">
                ${item.barangay || 'N/A'}
            </td>
        `;
                    tbody.appendChild(row);
                });

                console.log('Displayed', data.data.length, 'records');
            }

            // Update summary statistics
            function updateDeceasedSummary(data) {
                const totalElement = document.getElementById('totalDeceased');
                const periodElement = document.getElementById('selectedPeriod');

                if (totalElement && data.count !== undefined) {
                    totalElement.textContent = data.count || 0;
                }

                if (periodElement && data.filters) {
                    const monthNames = [
                        'January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'
                    ];

                    let periodText = 'All Time';
                    const year = data.filters.year;
                    const month = data.filters.month;

                    if (month && year) {
                        const monthName = monthNames[month - 1] || 'Unknown';
                        periodText = `${monthName} ${year}`;
                    } else if (year) {
                        periodText = `Year ${year}`;
                    } else if (month) {
                        const monthName = monthNames[month - 1] || 'Unknown';
                        periodText = `${monthName} (All Years)`;
                    }

                    periodElement.textContent = periodText;
                }
            }

            // Populate year filter dropdown
            function populateYearFilter(years, yearCounts) {
                const yearList = document.getElementById('yearFilterList');
                if (!yearList) return;

                // Clear existing items
                yearList.innerHTML = '';

                // Add "All Years" option
                const allYearsLi = document.createElement('li');
                allYearsLi.innerHTML = `
        <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
            onclick="applyYearFilter(null)">
            All Years
        </a>
    `;
                yearList.appendChild(allYearsLi);

                // Create a map of year counts for easy lookup
                const countMap = {};
                if (yearCounts && Array.isArray(yearCounts)) {
                    yearCounts.forEach(item => {
                        if (item.year && item.year > 1900) {
                            countMap[item.year] = item.count;
                        }
                    });
                }

                // Add available years with counts
                if (years && years.length > 0) {
                    years.forEach(year => {
                        const count = countMap[year] || 0;
                        const li = document.createElement('li');
                        li.innerHTML = `
                <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                    onclick="applyYearFilter(${year})">
                    ${year} (${count})
                </a>
            `;
                        yearList.appendChild(li);
                    });
                }
            }

            // Utility functions
            function showLoading() {
                const tbody = document.getElementById('deceasedBody');
                if (tbody) {
                    tbody.innerHTML = `
            <tr>
                <td colspan="3" class="px-4 py-8 text-center">
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Loading data...</p>
                </td>
            </tr>
        `;
                }
            }

            function showError(message) {
                const tbody = document.getElementById('deceasedBody');
                if (tbody) {
                    tbody.innerHTML = `
            <tr>
                <td colspan="3" class="px-4 py-8 text-center text-red-500">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="text-lg">${message}</p>
                    <button onclick="window.location.reload()" 
                        class="mt-3 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Retry
                    </button>
                </td>
            </tr>
        `;
                }
            }

            // Filter functions
            window.applyYearFilter = function(year) {
                const url = new URL(window.location);

                if (year === null) {
                    url.searchParams.delete('year');
                    url.searchParams.delete('month');
                } else {
                    url.searchParams.set('year', year);
                    // Keep month filter if it exists
                    const currentMonth = <?php echo isset($currentMonth) && $currentMonth ? json_encode($currentMonth) : 'null'; ?>;
                    if (currentMonth && currentMonth !== 'null') {
                        url.searchParams.set('month', currentMonth);
                    }
                }

                window.location.href = url.toString();
            };

            // Listen for filter changes from date_filter_component.php
            if (typeof window !== 'undefined') {
                window.addEventListener('filtersApplied', function(e) {
                    console.log('Filters applied in Part VI:', e.detail);
                    // Reload data with new filters
                    fetchDeceasedSeniors();
                });
            }

            // Generate report function
            window.generateReport = function() {
                console.log('=== Generate Report Clicked ===');

                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                let year = urlParams.get('year');
                let month = urlParams.get('month');
                let ctx = urlParams.get('session_context');

                // If not in URL, try to get from filter components
                if (!year || !month) {
                    const selectedMonthText = document.getElementById('selectedMonth')?.textContent?.trim();
                    const selectedYearText = document.getElementById('selectedYear')?.textContent?.trim();

                    const monthNames = {
                        'January': 1,
                        'February': 2,
                        'March': 3,
                        'April': 4,
                        'May': 5,
                        'June': 6,
                        'July': 7,
                        'August': 8,
                        'September': 9,
                        'October': 10,
                        'November': 11,
                        'December': 12
                    };

                    if (selectedMonthText && selectedMonthText !== 'All Months' && monthNames[selectedMonthText]) {
                        month = monthNames[selectedMonthText];
                    }

                    if (selectedYearText && selectedYearText !== 'All Years') {
                        year = parseInt(selectedYearText);
                    }
                }

                // Build URL
                let url = 'generate_consolidated_report.php';
                const params = [];

                if (ctx) {
                    params.push('session_context=' + encodeURIComponent(ctx));
                }

                if (year && year !== 'null') {
                    params.push('year=' + encodeURIComponent(year));
                }

                if (month && month !== 'null') {
                    params.push('month=' + encodeURIComponent(month));
                }

                if (params.length > 0) {
                    url += '?' + params.join('&');
                }

                console.log('Navigating to consolidated report:', url);
                window.location.href = url;
            };

        })();
    </script>

</body>

</html>