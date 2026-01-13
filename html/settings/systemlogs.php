<?php
require_once "../../php/login/admin_header.php";
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
    <title>Settings</title>
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
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

        * {
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* Smooth theme icon transitions */
        #nav-theme-light-icon,
        #nav-theme-dark-icon {
            transition: opacity 0.3s ease;
        }

        /* Sidebar container */
        .sidebar {
            position: relative;
            border-radius: 10px;
            height: 100%;
            width: 78px;
            background: #fff;
            transition: all 0.4s ease;
            z-index: 10;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .dark .sidebar {
            background: #1f2937;
            /* gray-800 */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar.open {
            width: 200px;
        }

        /* Logo section + toggle button */
        .sidebar .logo-details {
            height: 60px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #ddd;
        }

        .dark .sidebar .logo-details {
            border-bottom: 1px solid #374151;
            /* gray-700 */
        }

        .logo-details #btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .logo-details #btn svg {
            width: 24px;
            height: 24px;
            transition: transform 0.4s ease;
            flex-shrink: 0;
            opacity: 1;
            visibility: visible;
        }

        .sidebar.open .logo-details #btn svg {
            transform: rotate(180deg);
        }

        /* Navigation list */
        .nav-list {
            list-style: none;
            padding: 15px;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .nav-list li {
            position: relative;
            display: flex;
            width: 100%;
        }

        .nav-list li a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 35px;
            width: 100%;
            padding: 0 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .nav-list li a:hover {
            background: #e4e9f7;
        }

        .nav-list li button {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 35px;
            width: 100%;
            padding: 0 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .nav-list li button:hover {
            background: #e4e9f7;
        }

        .nav-list svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 1;
            visibility: visible;
            transition: all 0.3s ease;
        }

        /* Dark mode styles for nav list */
        .dark .nav-list li a,
        .dark .nav-list li button {
            background: #374151;
            /* gray-700 */
            border: 1px solid #4b5563;
            /* gray-600 */
            color: #e5e7eb;
            /* gray-200 */
        }

        .dark .nav-list li a:hover,
        .dark .nav-list li button:hover {
            background: #4b5563;
            /* gray-600 */
        }

        /* Hide link text when collapsed, keep icons */
        .links_name {
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 14px;
        }

        .sidebar.open .links_name {
            opacity: 1;
        }

        /* Tooltip styling */
        .tooltip {
            position: absolute;
            top: 50%;
            left: 100%;
            transform: translateY(-50%);
            margin-left: 10px;
            background: rgba(221, 221, 221, 0.95);
            color: #000;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 2px 8px rgba(138, 138, 138, 0.15);
            backdrop-filter: blur(4px);
            z-index: 200;
        }

        .dark .tooltip {
            background: rgba(55, 65, 81, 0.95);
            /* gray-700 */
            color: #e5e7eb;
            /* gray-200 */
        }

        /* Show tooltip when hovering over an item */
        .sidebar li:hover .tooltip {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        /* Hide tooltips when sidebar is expanded */
        .sidebar.open li .tooltip {
            display: none;
        }

        /* Active link styling */
        .nav-list li #history.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #history.active-link svg {
            color: #1d4ed8;
        }

        .dark .links_name {
            color: #d1d5db;
        }

        .dark .nav-list li #history.active-link {
            color: #60a5fa;
            /* blue-400 */
            border-color: #3b82f6;
            /* blue-500 */
            background: #1e40af;
            /* blue-800 */
        }

        .dark .nav-list li #history.active-link svg {
            color: #60a5fa;
            /* blue-400 */
        }
    </style>
    <style>
        /* Responsive table improvements */
        @media (max-width: 640px) {

            #logsTable th:nth-child(3),
            #logsTable td:nth-child(3),
            #logsTable th:nth-child(5),
            #logsTable td:nth-child(5),
            #logsTable th:nth-child(6),
            #logsTable td:nth-child(6),
            #logsTable th:nth-child(8),
            #logsTable td:nth-child(8) {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 768px) {

            #logsTable th:nth-child(5),
            #logsTable td:nth-child(5),
            #logsTable th:nth-child(6),
            #logsTable td:nth-child(6) {
                display: none;
            }

            .filter-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Overlay animations */
        #logOverlay {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        #logOverlay>div {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Table hover effects */
        #logsTableBody tr {
            transition: all 0.2s ease;
        }

        #logsTableBody tr:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Loading spinner */
        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Status badges with pulse animation for active sessions */
        .status-badge-active {
            position: relative;
        }

        .status-badge-active::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: inherit;
            border-radius: inherit;
            opacity: 0.5;
            animation: pulse 2s infinite;
            z-index: -1;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
            }

            50% {
                opacity: 0.8;
            }

            100% {
                opacity: 0.5;
            }
        }

        /* Scrollbar styling for dark mode */
        #overlayContent::-webkit-scrollbar {
            width: 8px;
        }

        #overlayContent::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #overlayContent::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #overlayContent::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .dark #overlayContent::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark #overlayContent::-webkit-scrollbar-thumb {
            background: #6b7280;
        }

        .dark #overlayContent::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Print styles */
        @media print {
            #logOverlay {
                position: static;
                background: white;
                display: block !important;
            }

            #closeOverlay,
            #refreshLogs,
            #exportLogs,
            #clearFilters {
                display: none;
            }
        }
    </style>
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
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <i class="fas fa-cog w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main content -->
        <main class="p-2 md:ml-64  pt-20">
            <div class="flex flex-row justify-between gap-1">
                <!-- partial:index.partial.html -->
                <div class="sidebar open">
                    <div class="logo-details">
                        <button type="button" class="border" id="btn">
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M6 6h8m-8 4h12M6 14h8m-8 4h12" />
                            </svg>
                        </button>
                    </div>
                    <ul class="nav-list">
                        <li>
                            <a href="profile.php?session_context=<?php echo $ctx; ?>" id="profile" class="cursor-pointer">
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="links_name">My Profile</span>
                            </a>
                            <span class="tooltip">My Profile</span>
                        </li>
                        <li>
                            <button type="button" id="nav-theme-toggle"
                                class="flex items-center w-full px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                <svg id="nav-theme-light-icon" class="w-4 h-4 mr-2 hidden" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                                </svg>
                                <svg id="nav-theme-dark-icon" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                                </svg>
                                <span id="nav-theme-text">Dark Mode</span>
                            </button>
                        </li>

                        <li>
                            <a href="accounts.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer active-link">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span class="links_name">Accounts</span>
                            </a>
                            <span class="tooltip">Accounts</span>
                        </li>
                        <li>
                            <a href="sms.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M5 5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H5Zm2.5 5.5a1 1 0 1 0 0 2h9a1 1 0 1 0 0-2h-9Zm0 3a1 1 0 1 0 0 2h5a1 1 0 1 0 0-2h-5Z"
                                        clip-rule="evenodd" />
                                    <path d="M8.707 4.293A1 1 0 0 0 8 4H6a1 1 0 0 0-1 1v1.382a1 1 0 0 0 .553.894l2.618 1.309a1 1 0 0 0 .894 0L12.447 7.276A1 1 0 0 0 13 6.382V5a1 1 0 0 0-1-1h-2a1 1 0 0 0-.707.293Z" />
                                </svg>
                                <span class="links_name">SMS Settings</span>
                            </a>
                            <span class="tooltip">SMS Settings</span>
                        </li>
                        <li>
                            <a href="#" id="history" class="cursor-pointer active-link">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3M3.22302 14C4.13247 18.008 7.71683 21 12 21c4.9706 0 9-4.0294 9-9 0-4.97056-4.0294-9-9-9-3.72916 0-6.92858 2.26806-8.29409 5.5M7 9H3V5" />
                                </svg>

                                <span class="links_name">System Logs</span>
                            </a>
                            <span class="tooltip">System Logs</span>
                        </li>
                    </ul>
                </div>
                <!-- history log -->
                <section id="historySection" class="bg-gray-50 dark:bg-gray-900 w-full">
                    <div class="mx-auto max-w-screen-xl ">
                        <!-- Stats Overview Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 1.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sessions</p>
                                        <p id="totalSessions" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Now</p>
                                        <p id="activeSessions" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Activities</p>
                                        <p id="totalActivities" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Duration</p>
                                        <p id="avgDuration" class="text-2xl font-semibold text-gray-900 dark:text-white">0m</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Container with Overlay Support -->
                        <div class="relative">
                            <!-- Overlay -->
                            <div id="logOverlay" class="hidden fixed inset-0 bg-gray-900/50 bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-hidden">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-auto ">
                                    <div class="flex justify-between items-center p-6 mb-2 border-b dark:border-gray-700">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="overlayTitle">Log Details</h3>
                                        <button id="closeOverlay" class="text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="px-4 overflow-y-auto " id="overlayContent">
                                        <!-- Content will be loaded here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Main Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                                <!-- Header with Filters -->
                                <div class="border-b dark:border-gray-700">
                                    <div class="p-2 md:p-2">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div>
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">System Logs</h2>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Track user sessions and system activities
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <!-- Quick Actions -->
                                                <button id="exportLogs" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Export
                                                </button>

                                                <button id="clearFilters" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Clear Filters
                                                </button>

                                                <button id="refreshLogs" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Refresh
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Advanced Filters -->
                                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2   ">
                                            <!-- Search -->
                                            <div>
                                                <label for="searchLogs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" id="searchLogs" placeholder="Search logs..."
                                                        class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                </div>
                                            </div>

                                            <!-- Log Type -->
                                            <div>
                                                <label for="logTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Log Type</label>
                                                <select id="logTypeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="both">All Logs</option>
                                                    <option value="session">Sessions Only</option>
                                                    <option value="activity">Activities Only</option>
                                                </select>
                                            </div>

                                            <!-- Date Range -->
                                            <div>
                                                <label for="dateRangeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                                                <select id="dateRangeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="all">All Time</option>
                                                    <option value="today">Today</option>
                                                    <option value="week">Last 7 Days</option>
                                                    <option value="month">Last 30 Days</option>
                                                    <option value="year">Last Year</option>
                                                </select>
                                            </div>

                                            <!-- User Type -->
                                            <div>
                                                <label for="userTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User Type</label>
                                                <select id="userTypeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="all">All Users</option>
                                                    <option value="admin">Admin Only</option>
                                                    <option value="staff">Staff Only</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <div id="logsLoading" class="p-8 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                                        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading logs...</p>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div id="logsEmpty" class="hidden p-12 text-center">
                                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No logs found</h3>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Try adjusting your search or filter criteria
                                    </p>
                                </div>

                                <!-- Table Container -->
                                <div class="overflow-x-auto">
                                    <table id="logsTable" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 hidden">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-2 py-2">Type</th>
                                                <th scope="col" class="px-2 py-2">User</th>
                                                <th scope="col" class="px-2 py-2 hidden md:table-cell">Activity</th>
                                                <th scope="col" class="px-2 py-2">Time</th>
                                                <th scope="col" class="px-2 py-2 hidden lg:table-cell">IP Address</th>
                                                <th scope="col" class="px-2 py-2 hidden lg:table-cell">Duration</th>
                                                <th scope="col" class="px-2 py-2">Status</th>
                                                <th scope="col" class="px-2 py-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="logsTableBody">
                                            <!-- Data will be populated here -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div id="logsPagination" class="hidden flex flex-col sm:flex-row items-center justify-between p-4 border-t dark:border-gray-700">
                                    <div class="mb-4 sm:mb-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-400">
                                            Showing <span id="pageStart" class="font-medium">1</span> to
                                            <span id="pageEnd" class="font-medium">10</span> of
                                            <span id="totalItems" class="font-medium">0</span> results
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button id="firstPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            « First
                                        </button>
                                        <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            ‹ Prev
                                        </button>
                                        <span class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
                                        </span>
                                        <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Next ›
                                        </button>
                                        <button id="lastPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Last »
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
        // logs-manager.js
        // Combined Logs Management System
        const LogManager = {
            config: {
                currentFilter: 'all',
                currentSearch: '',
                currentLogType: 'both',
                currentDateRange: 'all',
                currentSort: {
                    field: 'timestamp',
                    direction: 'DESC'
                },
                currentPage: 1,
                itemsPerPage: 20,
                totalItems: 0,
                totalPages: 0,
                apiUrl: '/MSWDPALUAN_SYSTEM-MAIN/php/settings/combined_logs_backend.php'
            },

            init() {
                this.bindEvents();
                this.loadLogs();
                // this.startAutoRefresh(60000); // Uncomment for auto-refresh
            },

            bindEvents() {
                // Search with debounce
                const searchInput = document.getElementById('searchLogs');
                if (searchInput) {
                    let searchTimeout;
                    searchInput.addEventListener('input', () => {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            this.config.currentSearch = searchInput.value;
                            this.config.currentPage = 1;
                            this.loadLogs();
                        }, 500);
                    });
                }

                // Filter changes
                const logTypeFilter = document.getElementById('logTypeFilter');
                const dateRangeFilter = document.getElementById('dateRangeFilter');
                const userTypeFilter = document.getElementById('userTypeFilter');

                if (logTypeFilter) {
                    logTypeFilter.addEventListener('change', (e) => {
                        this.config.currentLogType = e.target.value;
                        this.config.currentPage = 1;
                        this.loadLogs();
                    });
                }

                if (dateRangeFilter) {
                    dateRangeFilter.addEventListener('change', (e) => {
                        this.config.currentDateRange = e.target.value;
                        this.config.currentPage = 1;
                        this.loadLogs();
                    });
                }

                if (userTypeFilter) {
                    userTypeFilter.addEventListener('change', (e) => {
                        this.config.currentFilter = e.target.value;
                        this.config.currentPage = 1;
                        this.loadLogs();
                    });
                }

                // Action buttons
                const refreshBtn = document.getElementById('refreshLogs');
                const clearBtn = document.getElementById('clearFilters');
                const exportBtn = document.getElementById('exportLogs');

                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => this.refreshLogs());
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', () => this.clearFilters());
                }

                if (exportBtn) {
                    exportBtn.addEventListener('click', () => this.exportLogs());
                }

                // Overlay
                const closeOverlayBtn = document.getElementById('closeOverlay');
                const overlay = document.getElementById('logOverlay');

                if (closeOverlayBtn) {
                    closeOverlayBtn.addEventListener('click', () => this.hideOverlay());
                }

                if (overlay) {
                    overlay.addEventListener('click', (e) => {
                        if (e.target.id === 'logOverlay') this.hideOverlay();
                    });
                }

                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') this.hideOverlay();
                    if (e.key === 'r' && e.ctrlKey) {
                        e.preventDefault();
                        this.refreshLogs();
                    }
                });
            },

            async loadLogs() {
                this.showLoading();

                const params = new URLSearchParams({
                    search: this.config.currentSearch,
                    filter: this.config.currentFilter,
                    log_type: this.config.currentLogType,
                    date_range: this.config.currentDateRange,
                    sort: this.config.currentSort.field,
                    order: this.config.currentSort.direction,
                    page: this.config.currentPage,
                    limit: this.config.itemsPerPage,
                    _t: Date.now() // Cache busting
                });

                try {
                    console.log('Fetching logs from:', `${this.config.apiUrl}?${params.toString().substring(0, 100)}...`);

                    const response = await fetch(`${this.config.apiUrl}?${params}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('HTTP Error:', response.status, errorText);

                        // Try to parse as JSON for detailed error
                        try {
                            const errorData = JSON.parse(errorText);
                            throw new Error(`HTTP ${response.status}: ${errorData.error || errorData.debug || 'Server error'}`);
                        } catch (e) {
                            throw new Error(`HTTP ${response.status}: Failed to load logs`);
                        }
                    }

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || data.debug || 'Failed to load logs');
                    }

                    this.updateStats(data.data || []);
                    this.renderTable(data.data || []);
                    this.updatePagination(data);
                    this.updateFilterIndicators(data.filters || {});

                } catch (error) {
                    console.error('Error loading logs:', error);
                    this.showError(error.message);
                } finally {
                    this.hideLoading();
                }
            },

            updateStats(logs) {
                const sessions = logs.filter(log => log.log_type === 'session');
                const activities = logs.filter(log => log.log_type === 'activity');
                const activeSessions = sessions.filter(s => s.status === 'Active');

                // Update stats cards
                const totalSessionsEl = document.getElementById('totalSessions');
                const activeSessionsEl = document.getElementById('activeSessions');
                const totalActivitiesEl = document.getElementById('totalActivities');
                const avgDurationEl = document.getElementById('avgDuration');

                if (totalSessionsEl) totalSessionsEl.textContent = sessions.length;
                if (activeSessionsEl) activeSessionsEl.textContent = activeSessions.length;
                if (totalActivitiesEl) totalActivitiesEl.textContent = activities.length;

                // Calculate average duration
                const durations = sessions
                    .map(s => s.duration)
                    .filter(d => d !== 'N/A' && d !== 'Ongoing')
                    .map(d => this.parseDuration(d));

                const avgDuration = durations.length > 0 ?
                    this.formatDuration(Math.round(durations.reduce((a, b) => a + b) / durations.length)) :
                    '0m';

                if (avgDurationEl) avgDurationEl.textContent = avgDuration;
            },

            parseDuration(duration) {
                // Convert "2h 30m" to minutes
                let minutes = 0;
                if (duration.includes('h')) {
                    const hours = parseInt(duration) || 0;
                    minutes += hours * 60;
                }
                if (duration.includes('m')) {
                    const match = duration.match(/(\d+)m/);
                    if (match) minutes += parseInt(match[1]) || 0;
                }
                return minutes;
            },

            formatDuration(minutes) {
                if (minutes < 60) return `${minutes}m`;
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
            },

            renderTable(logs) {
                const tableBody = document.getElementById('logsTableBody');
                const table = document.getElementById('logsTable');
                const emptyState = document.getElementById('logsEmpty');

                if (!tableBody || !table || !emptyState) return;

                if (logs.length === 0) {
                    table.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    return;
                }

                table.classList.remove('hidden');
                emptyState.classList.add('hidden');

                tableBody.innerHTML = logs.map(log => this.createTableRow(log)).join('');

                // Add click handlers to view buttons
                tableBody.querySelectorAll('.view-details').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const logId = e.target.dataset.logId;
                        const logType = e.target.dataset.logType;
                        this.showLogDetails(logId, logType);
                    });
                });

                // Add row click handlers
                tableBody.querySelectorAll('tr').forEach(row => {
                    row.addEventListener('click', (e) => {
                        const logId = row.querySelector('.view-details')?.dataset?.logId;
                        const logType = row.querySelector('.view-details')?.dataset?.logType;
                        if (logId && logType) {
                            this.showLogDetails(logId, logType);
                        }
                    });
                });
            },

            createTableRow(log) {
                const isMobile = window.innerWidth < 768;
                const isTablet = window.innerWidth < 1024;

                // Status badge classes
                const statusColors = {
                    'Active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'Completed': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'N/A': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                };

                const statusClass = statusColors[log.status] || statusColors['N/A'];

                // Format IP address
                const ipAddress = log.ip_address === '::1' ? 'Localhost' : (log.ip_address || 'N/A');

                return `
            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">${log.icon}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            ${log.log_type === 'session'
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'}">
                            ${log.log_type === 'session' ? 'Session' : 'Activity'}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900 dark:text-white">${log.user_name}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${log.user_type}</div>
                </td>
                ${!isMobile ? `
                <td class="px-4 py-3">
                    <div class="font-medium">${log.activity_type}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                        ${log.description}
                    </div>
                </td>
                ` : ''}
                <td class="px-4 py-3">
                    <div class="font-medium">${log.timestamp}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${log.time_ago}</div>
                </td>
                ${!isTablet ? `
                <td class="px-4 py-3">
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded font-mono">
                        ${ipAddress}
                    </code>
                </td>
                <td class="px-4 py-3">${log.duration}</td>
                ` : ''}
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                        ${log.status}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <button class="view-details px-3 py-1 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                            data-log-id="${log.log_id}" data-log-type="${log.log_type}">
                        View
                    </button>
                </td>
            </tr>
        `;
            },

            async showLogDetails(logId, logType) {
                try {
                    // Fetch detailed log information
                    const response = await fetch(`${this.config.apiUrl}?action=detail&id=${logId}&type=${logType}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load details');
                    }

                    const log = data.data;
                    const overlay = document.getElementById('logOverlay');
                    const content = document.getElementById('overlayContent');
                    const title = document.getElementById('overlayTitle');

                    if (!overlay || !content || !title) return;

                    title.textContent = `${logType === 'session' ? 'Session' : 'Activity'} Details`;

                    // Format details content
                    content.innerHTML = this.formatLogDetails(log, logType);

                    // Show overlay
                    overlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';

                } catch (error) {
                    console.error('Error loading log details:', error);
                    alert('Failed to load log details: ' + error.message);
                }
            },

            formatLogDetails(log, logType) {
                const formatField = (label, value, isCode = false) => `
            <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">${label}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white ${isCode ? 'font-mono bg-gray-50 dark:bg-gray-700 p-2 rounded' : ''}">
                    ${value || 'N/A'}
                </dd>
            </div>
        `;

                // Format login/logout times
                const loginTime = log.login_time ? new Date(log.login_time).toLocaleString() : 'N/A';
                const logoutTime = log.logout_time && log.logout_time !== '0000-00-00 00:00:00' ?
                    new Date(log.logout_time).toLocaleString() :
                    'Still active';

                // Calculate duration
                const duration = logType === 'session' && log.login_time && log.logout_time && log.logout_time !== '0000-00-00 00:00:00' ?
                    this.calculateDurationFromTimes(log.login_time, log.logout_time) :
                    'N/A';

                // User name
                const userName = log.firstname && log.lastname ?
                    `${log.lastname}, ${log.firstname} (${log.user_type || 'N/A'})` :
                    'Unknown';

                return `
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Log ID', log.id || log.log_id)}
                    ${formatField('User', userName)}
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Type', logType === 'session' ? 'User Session' : 'System Activity')}
                    ${formatField('Status', logType === 'session' ? (log.logout_time && log.logout_time !== '0000-00-00 00:00:00' ? 'Completed' : 'Active') : 'Completed')}
                </div>
                
                ${logType === 'session' ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Login Time', loginTime)}
                    ${formatField('Logout Time', logoutTime)}
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Duration', duration)}
                    ${formatField('Login Type', log.login_type || 'N/A')}
                </div>
                ` : `
                <div class="grid grid-cols-1 gap-4">
                    ${formatField('Activity Type', log.activity_type || 'N/A')}
                    ${formatField('Description', log.description || 'N/A')}
                </div>
                `}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('IP Address', log.ip_address || 'N/A', true)}
                    ${formatField('Timestamp', log.login_time || log.created_at ? new Date(log.login_time || log.created_at).toLocaleString() : 'N/A')}
                </div>
                
                ${log.user_agent ? `
                <div class="border-t dark:border-gray-700 pt-4">
                    ${formatField('User Agent', log.user_agent, true)}
                </div>
                ` : ''}
            </div>
        `;
            },

            calculateDurationFromTimes(startTime, endTime) {
                try {
                    const start = new Date(startTime);
                    const end = new Date(endTime);
                    const diffMs = end - start;

                    const hours = Math.floor(diffMs / (1000 * 60 * 60));
                    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);

                    if (hours > 0) {
                        return `${hours}h ${minutes}m ${seconds}s`;
                    } else if (minutes > 0) {
                        return `${minutes}m ${seconds}s`;
                    } else {
                        return `${seconds}s`;
                    }
                } catch (e) {
                    return 'N/A';
                }
            },

            hideOverlay() {
                const overlay = document.getElementById('logOverlay');
                if (overlay) {
                    overlay.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            },

            updatePagination(data) {
                this.config.totalItems = data.total;
                this.config.totalPages = data.total_pages;

                // Update pagination info
                const pageStart = document.getElementById('pageStart');
                const pageEnd = document.getElementById('pageEnd');
                const totalItems = document.getElementById('totalItems');
                const currentPage = document.getElementById('currentPage');
                const totalPages = document.getElementById('totalPages');

                if (pageStart) {
                    pageStart.textContent = Math.min((this.config.currentPage - 1) * this.config.itemsPerPage + 1, data.total);
                }
                if (pageEnd) {
                    pageEnd.textContent = Math.min(this.config.currentPage * this.config.itemsPerPage, data.total);
                }
                if (totalItems) {
                    totalItems.textContent = data.total;
                }
                if (currentPage) {
                    currentPage.textContent = this.config.currentPage;
                }
                if (totalPages) {
                    totalPages.textContent = data.total_pages;
                }

                // Enable/disable pagination buttons
                const pagination = document.getElementById('logsPagination');
                if (!pagination) return;

                if (data.total_pages > 1) {
                    pagination.classList.remove('hidden');

                    const buttons = ['firstPage', 'prevPage', 'nextPage', 'lastPage'];
                    buttons.forEach(id => {
                        const btn = document.getElementById(id);
                        if (btn) {
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    });

                    const firstPage = document.getElementById('firstPage');
                    const prevPage = document.getElementById('prevPage');
                    const nextPage = document.getElementById('nextPage');
                    const lastPage = document.getElementById('lastPage');

                    if (firstPage) firstPage.disabled = this.config.currentPage === 1;
                    if (prevPage) prevPage.disabled = this.config.currentPage === 1;
                    if (nextPage) nextPage.disabled = this.config.currentPage === data.total_pages;
                    if (lastPage) lastPage.disabled = this.config.currentPage === data.total_pages;

                } else {
                    pagination.classList.add('hidden');
                }

                // Bind pagination events
                this.bindPaginationEvents();
            },

            bindPaginationEvents() {
                const firstPage = document.getElementById('firstPage');
                const prevPage = document.getElementById('prevPage');
                const nextPage = document.getElementById('nextPage');
                const lastPage = document.getElementById('lastPage');

                if (firstPage) {
                    firstPage.onclick = () => {
                        this.config.currentPage = 1;
                        this.loadLogs();
                    };
                }

                if (prevPage) {
                    prevPage.onclick = () => {
                        if (this.config.currentPage > 1) {
                            this.config.currentPage--;
                            this.loadLogs();
                        }
                    };
                }

                if (nextPage) {
                    nextPage.onclick = () => {
                        if (this.config.currentPage < this.config.totalPages) {
                            this.config.currentPage++;
                            this.loadLogs();
                        }
                    };
                }

                if (lastPage) {
                    lastPage.onclick = () => {
                        this.config.currentPage = this.config.totalPages;
                        this.loadLogs();
                    };
                }
            },

            updateFilterIndicators(filters) {
                // Update filter dropdowns to reflect current state
                const logTypeFilter = document.getElementById('logTypeFilter');
                const dateRangeFilter = document.getElementById('dateRangeFilter');
                const userTypeFilter = document.getElementById('userTypeFilter');

                if (logTypeFilter && filters.log_type) logTypeFilter.value = filters.log_type;
                if (dateRangeFilter && filters.date_range) dateRangeFilter.value = filters.date_range;
                if (userTypeFilter && filters.filter) userTypeFilter.value = filters.filter;
            },

            refreshLogs() {
                const btn = document.getElementById('refreshLogs');
                if (!btn) return;

                const originalText = btn.innerHTML;

                btn.innerHTML = `
            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Refreshing...
        `;

                this.config.currentPage = 1;
                this.loadLogs();

                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 1000);
            },

            clearFilters() {
                this.config.currentSearch = '';
                this.config.currentFilter = 'all';
                this.config.currentLogType = 'both';
                this.config.currentDateRange = 'all';
                this.config.currentPage = 1;

                const searchInput = document.getElementById('searchLogs');
                const logTypeFilter = document.getElementById('logTypeFilter');
                const dateRangeFilter = document.getElementById('dateRangeFilter');
                const userTypeFilter = document.getElementById('userTypeFilter');

                if (searchInput) searchInput.value = '';
                if (logTypeFilter) logTypeFilter.value = 'both';
                if (dateRangeFilter) dateRangeFilter.value = 'all';
                if (userTypeFilter) userTypeFilter.value = 'all';

                this.loadLogs();
            },

            exportLogs() {
                const params = new URLSearchParams({
                    search: this.config.currentSearch,
                    filter: this.config.currentFilter,
                    log_type: this.config.currentLogType,
                    date_range: this.config.currentDateRange,
                    sort: this.config.currentSort.field,
                    order: this.config.currentSort.direction,
                    page: this.config.currentPage,
                    limit: this.config.itemsPerPage,
                    export: 'csv'
                });

                // Show loading state on the export button
                const exportBtn = document.getElementById('exportLogs');
                if (exportBtn) {
                    const originalHTML = exportBtn.innerHTML;
                    exportBtn.innerHTML = `
            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Exporting...
        `;
                    exportBtn.disabled = true;

                    // Create hidden iframe for download
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = `${this.config.apiUrl}?${params}`;
                    document.body.appendChild(iframe);

                    // Set timeout to restore button even if iframe doesn't load
                    const restoreButton = () => {
                        exportBtn.innerHTML = originalHTML;
                        exportBtn.disabled = false;
                        if (iframe.parentNode) {
                            document.body.removeChild(iframe);
                        }
                    };

                    // Restore button after download starts (or timeout)
                    setTimeout(restoreButton, 3000);

                    // Also restore on iframe load
                    iframe.onload = restoreButton;
                    iframe.onerror = restoreButton;

                } else {
                    // Fallback: Open in new tab
                    window.open(`${this.config.apiUrl}?${params}`, '_blank');
                }
            },

            // Add these helper methods to LogManager object:
            showExportSuccess() {
                // Create a toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 z-50';
                toast.innerHTML = `
                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 shadow-lg animate-slide-up">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-300 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-green-800 dark:text-green-200 font-medium">Export successful!</span>
                            <button class="ml-4 text-green-600 hover:text-green-800 dark:text-green-300 dark:hover:text-green-100" onclick="this.parentElement.parentElement.remove()">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);

                // Remove toast after 5 seconds
                setTimeout(() => {
                    if (toast.parentNode) {
                        document.body.removeChild(toast);
                    }
                }, 5000);
            },

            showExportError() {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 z-50';
                toast.innerHTML = `
        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 shadow-lg animate-slide-up">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-300 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-red-800 dark:text-red-200 font-medium">Export failed. Please try again.</span>
                <button class="ml-4 text-red-600 hover:text-red-800 dark:text-red-300 dark:hover:text-red-100" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    if (toast.parentNode) {
                        document.body.removeChild(toast);
                    }
                }, 5000);
            },

            showLoading() {
                const loadingEl = document.getElementById('logsLoading');
                const tableEl = document.getElementById('logsTable');
                const paginationEl = document.getElementById('logsPagination');
                const emptyEl = document.getElementById('logsEmpty');

                if (loadingEl) loadingEl.classList.remove('hidden');
                if (tableEl) tableEl.classList.add('hidden');
                if (paginationEl) paginationEl.classList.add('hidden');
                if (emptyEl) emptyEl.classList.add('hidden');
            },

            hideLoading() {
                const loadingEl = document.getElementById('logsLoading');
                if (loadingEl) loadingEl.classList.add('hidden');
            },

            showError(message) {
                const tableBody = document.getElementById('logsTableBody');
                const table = document.getElementById('logsTable');
                const pagination = document.getElementById('logsPagination');

                if (!tableBody || !table) return;

                tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-red-600 dark:text-red-400">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium">Error loading logs</h3>
                    <p class="mt-1 text-sm">${message}</p>
                    <button onclick="LogManager.loadLogs()" 
                            class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Try Again
                    </button>
                </td>
            </tr>
        `;

                table.classList.remove('hidden');
                if (pagination) pagination.classList.add('hidden');
            },

            startAutoRefresh(interval) {
                setInterval(() => {
                    if (!document.hidden && document.visibilityState === 'visible') {
                        this.loadLogs();
                    }
                }, interval);
            }
        };

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => LogManager.init());

        // Handle window resize for responsive table
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (LogManager.config.currentSearch || LogManager.config.currentFilter !== 'all') {
                    LogManager.loadLogs();
                }
            }, 250);
        });
    </script>

    <script>
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");

        // Sidebar toggle
        closeBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
        });

        // My Profile click behavior
        profileLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the profile section
            profileSection.classList.remove('hidden');
            accountSection.classList.add('hidden');
            historySection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My Profile link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            profileLink.classList.add('active-link');

        });

        // My Account click behavior
        accountLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the account section
            accountSection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            historySection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My Account link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            accountLink.classList.add('active-link');

        });
        // My History click behavior
        historyLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the history section
            historySection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            accountSection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My History link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            historyLink.classList.add('active-link');

        });
        // My Activity click behavior
        activityLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the activity section
            activitySection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            accountSection.classList.add('hidden');
            historySection.classList.add('hidden');
            // Highlight the My Activity link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            activityLink.classList.add('active-link');

        });

        // Close button behavior inside the profile section
        const closeProfileBtn = document.querySelector('[aria-label="Close"]');
        if (closeProfileBtn) {
            closeProfileBtn.addEventListener('click', () => {
                profileSection.classList.add('hidden');
                profileLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the account section
        const closeAccountBtn = document.querySelector('[aria-label="Close1"]');
        if (closeAccountBtn) {
            closeAccountBtn.addEventListener('click', () => {
                accountSection.classList.add('hidden');
                accountLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the history section
        const closeHistoryBtn = document.querySelector('[aria-label="Close2"]');
        if (closeHistoryBtn) {
            closeHistoryBtn.addEventListener('click', () => {
                historySection.classList.add('hidden');
                historyLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the activity section
        const closeActivityBtn = document.querySelector('[aria-label="Close3"]');
        if (closeActivityBtn) {
            closeActivityBtn.addEventListener('click', () => {
                activitySection.classList.add('hidden');
                activityLink.classList.remove('active-link');
            });
        }
    </script>

    <script>
        // Theme Switcher
        document.addEventListener('DOMContentLoaded', function() {
            const navThemeToggle = document.getElementById('nav-theme-toggle');
            const navThemeLightIcon = document.getElementById('nav-theme-light-icon');
            const navThemeDarkIcon = document.getElementById('nav-theme-dark-icon');
            const navThemeText = document.getElementById('nav-theme-text');

            // Function to update theme icons based on current theme
            function updateThemeUI(isDarkMode) {
                if (isDarkMode) {
                    // If dark mode is active, show light icon (for switching to light mode)
                    if (navThemeLightIcon) navThemeLightIcon.classList.remove('hidden');
                    if (navThemeDarkIcon) navThemeDarkIcon.classList.add('hidden');
                    if (navThemeText) navThemeText.textContent = 'Light Mode';
                } else {
                    // If light mode is active, show dark icon (for switching to dark mode)
                    if (navThemeLightIcon) navThemeLightIcon.classList.add('hidden');
                    if (navThemeDarkIcon) navThemeDarkIcon.classList.remove('hidden');
                    if (navThemeText) navThemeText.textContent = 'Dark Mode';
                }
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
                updateThemeUI(theme === 'dark');
            }

            // Initialize theme
            function initTheme() {
                // Check localStorage for saved theme
                const savedTheme = localStorage.getItem('theme');

                // Check for system preference
                const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                // Determine which theme to use
                let theme = 'light';
                if (savedTheme) {
                    theme = savedTheme;
                } else if (systemPrefersDark) {
                    theme = 'dark';
                }

                // Apply the theme
                setTheme(theme);

                // Update UI
                updateThemeUI(theme === 'dark');
            }

            // Initialize theme on page load
            initTheme();

            // Toggle theme when button is clicked
            if (navThemeToggle) {
                navThemeToggle.addEventListener('click', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    setTheme(isDark ? 'light' : 'dark');
                });
            }

            // Listen for theme changes from other tabs/windows
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    const theme = e.newValue;
                    setTheme(theme);
                }
            });

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                // Only apply system theme if no user preference is saved
                if (!localStorage.getItem('theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });

            // Optional: Add keyboard shortcut for theme toggle (Alt+T)
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 't') {
                    const isDark = document.documentElement.classList.contains('dark');
                    setTheme(isDark ? 'light' : 'dark');
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>