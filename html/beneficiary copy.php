<?php
require_once "../php/login/admin_header.php";
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

// Fallback to avatar if no profile photo
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}
?>
<!-- Pass PHP data to JavaScript -->
<script>
    // Pass PHP user data to JavaScript safely
    window.phpUserData = <?php
                            echo json_encode([
                                'id' => $user_id ?? 0,
                                'firstname' => $user_data['firstname'] ?? '',
                                'lastname' => $user_data['lastname'] ?? '',
                                'middlename' => $user_data['middlename'] ?? '',
                                'full_name' => $full_name ?? 'System Administrator',
                                'profile_photo' => $profile_photo_url ?? '',
                                'user_type' => $_SESSION['user_type'] ?? '',
                                'role_name' => $_SESSION['role_name'] ?? ''
                            ]);
                            ?>;
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiary</title>
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

        /* Active filter badge styling */
        .filter-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            margin: 2px;
            background-color: #dbeafe;
            color: #1e40af;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .dark .filter-badge {
            background-color: #1e3a8a;
            color: #dbeafe;
        }

        /* Responsive adjustments */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 768px) {
            .mobile-scroll {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .mobile-table {
                min-width: 800px;
            }

            .mobile-stack {
                flex-direction: column;
                gap: 0.5rem;
            }

            .mobile-full-width {
                width: 100% !important;
            }

            .mobile-text-center {
                text-align: center;
            }

            .mobile-padding {
                padding: 0.75rem !important;
            }

            .mobile-small-text {
                font-size: 0.875rem;
            }

            .action-dropdown {
                position: static !important;
            }
        }

        @media (max-width: 640px) {
            .sm- {
                display: none;
            }

            .sm-mobile-block {
                display: block;
            }

            .sm-mobile-w-full {
                width: 100% !important;
            }
        }

        /* Improved modal responsiveness */
        @media (max-width: 640px) {

            #addBenefitModal,
            #viewBenefitsModal,
            #printModal {
                padding: 0.5rem;
            }

            .modal-content {
                margin: 0.5rem;
                width: calc(100% - 1rem);
            }
        }

        /* Better dropdown positioning on mobile */
        @media (max-width: 768px) {
            [data-dropdown-toggle]+[id$="Dropdown"] {
                position: fixed !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%);
                width: 90vw !important;
                max-width: 400px;
                max-height: 70vh;
                overflow-y: auto;
            }
        }

        /* Touch-friendly buttons */
        @media (max-width: 768px) {

            button,
            .btn,
            [type="button"],
            [type="submit"] {
                min-height: 44px;
                min-width: 44px;
            }

            input,
            select,
            textarea {
                font-size: 16px !important;
                /* Prevents zoom on iOS */
            }
        }

        /* Better table readability on mobile */
        @media (max-width: 768px) {

            table td,
            table th {
                padding: 8px 4px !important;
                font-size: 12px !important;
            }
        }

        /* Status badge styling */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-deceased {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-inactive {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Validation status styling */
        .status-validated {
            color: #28a745;
            font-weight: bold;
        }

        .status-pending {
            color: #dc3545;
            font-weight: bold;
        }

        /* Validation filter styling */
        .validation-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .validation-validated {
            background-color: #d1fae5;
            color: #065f46;
        }

        .validation-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Disabled benefit styling */
        .benefit-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .disabled-reason {
            font-size: 11px;
            color: #dc3545;
            margin-top: 2px;
        }


        /* Benefits Report Modal Specific Styles */
        #specificBenefitContainer {
            transition: all 0.3s ease;
        }

        /* Summary grid responsiveness */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-deceased {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-inactive {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-validated {
            color: #28a745;
            font-weight: bold;
        }

        .status-pending {
            color: #dc3545;
            font-weight: bold;
        }

        .bg-green-50 {
            background-color: #f0fdf4 !important;
        }

        /* Tooltip styles */
        .relative.group {
            position: relative;
        }

        .relative.group span {
            pointer-events: none;
            white-space: nowrap;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        .relative.group span::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 4px;
            border-style: solid;
            border-color: rgba(0, 0, 0, 0.75) transparent transparent transparent;
        }

        .dark .relative.group span::before {
            border-color: rgba(55, 65, 81, 1) transparent transparent transparent;
        }

        /* Pagination button hover effects */
        #paginationNav button:hover:not(:disabled) {
            transform: translateY(-1px);
            transition: transform 0.2s ease;
        }

        #paginationNav button:disabled {
            cursor: not-allowed;
            background-color: #f9fafb;
        }

        .dark #paginationNav button:disabled {
            background-color: #374151;
        }

        /* Responsive pagination adjustments */
        @media (max-width: 640px) {
            #paginationNav {
                flex-direction: column;
                gap: 0.75rem;
                align-items: center;
            }

            #paginationNav>span {
                text-align: center;
                width: 100%;
            }

            #paginationNav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.25rem;
            }

            #paginationNav li {
                margin-bottom: 0.25rem;
            }
        }

        /* Modal centering fix */
        .modal-center {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Modal animation */
        .modal-animate-in {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Top Navigation -->
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
                            class="mr-3 h-8 sm:h-10 border border-gray-50 rounded-full py-1 px-0.5 sm:py-1.5 sm:px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                            alt="MSWD LOGO" />
                        <span class="self-center text-lg sm:text-xl md:text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 w-8 h-8 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-full h-full rounded-full object-cover"
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
                        <a href="./admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
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
                    <li>
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <i class="fas fa-gift w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
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
        <main class="p-2 sm:p-4 md:ml-64 pt-16 md:pt-20 flex flex-col">
            <!-- Breadcrumb Navigation -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center mb-4 gap-2">
                <div class="flex items-center w-full sm:w-auto">
                    <div class="border border-t-0 border-l-0 flex-shrink-0">
                        <a href="./benefits.php?session_context=<?php echo $ctx; ?>" type="button" class="cursor-pointer">
                            <h4 class="text-lg sm:text-xl font-medium px-2 dark:text-white">Benefits</h4>
                        </a>
                    </div>
                    <div class="border-b-0 border border-l-0 flex-shrink-0">
                        <a href="#" type="button" class="cursor-pointer">
                            <h4 class="text-lg sm:text-xl font-medium text-blue-700 px-2">Beneficiaries</h4>
                        </a>
                    </div>
                    <div class="border-t-0 flex-grow border border-l-0 border-r-0 h-[30px]"></div>
                </div>
            </div>

            <!-- Main Content Section -->
            <section class="bg-gray-50 dark:bg-gray-900 p-2 sm:p-3 md:p-5 rounded-lg">
                <div class="mx-auto w-full">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md rounded-lg">
                        <div class="flex flex-col  p-3 sm:flex-row sm:items-center justify-between gap-3">
                            <h4 class="text-lg sm:text-xl font-medium dark:text-white">Beneficiaries</h4>
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <!-- Add Benefit Button (Initially Hidden) -->
                                <div id="addBenefitBtnContainer" class="flex gap-2 hidden">
                                    <button id="addBenefitBtn"
                                        class="px-3 py-2 cursor-pointer text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 w-full sm:w-auto">
                                        <i class="fas fa-gift mr-1"></i> Add Benefit
                                    </button>
                                </div>
                                <div class="relative w-full sm:w-auto">
                                    <button id="printBenefitsReportButton"
                                        class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <i class="fas fa-file-alt w-4 h-4 mr-2"></i>
                                        Benefits Report
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Search and Filter Section -->
                        <div class="p-3">
                            <!-- Control Buttons -->
                            <div class="flex flex-col  sm:flex-row gap-3 justify-between">
                                <!-- Search Bar -->
                                <div class="w-full sm:w-1/2 ">
                                    <form class="flex items-center">
                                        <label for="simple-search" class="sr-only">Search</label>
                                        <div class="relative w-full">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                    fill="currentColor" viewbox="0 0 20 20"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd"
                                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input type="text" id="simple-search"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                placeholder="Search by name..." required>
                                        </div>
                                    </form>
                                </div>

                                <!-- Filter Controls -->
                                <div class="flex gap-2">
                                    <!-- Status Filter -->
                                    <div class="relative w-full sm:w-auto">
                                        <button id="statusFilterButton" data-dropdown-toggle="statusFilterDropdown"
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                            type="button">
                                            <i class="fas fa-user-check w-4 h-4 mr-2"></i>
                                            Status
                                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                        </button>
                                    </div>

                                    <!-- Validation Filter (NEW) -->
                                    <div class="relative w-full sm:w-auto">
                                        <button id="validationFilterButton" data-dropdown-toggle="validationFilterDropdown"
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                            type="button">
                                            <i class="fas fa-check-circle w-4 h-4 mr-2"></i>
                                            Validation
                                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                        </button>
                                    </div>

                                    <!-- Benefits Filter -->
                                    <div class="relative w-full sm:w-auto">
                                        <button id="benefitsFilterButton" data-dropdown-toggle="benefitsFilterDropdown"
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                            type="button">
                                            <i class="fas fa-gift w-4 h-4 mr-2"></i>
                                            Benefits
                                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                        </button>
                                    </div>

                                    <!-- Barangay Filter -->
                                    <div class="relative w-full sm:w-auto">
                                        <button id="filterDropdownButton" data-dropdown-toggle="filterDropdown"
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                            type="button">
                                            <i class="fas fa-map-marker-alt w-4 h-4 mr-2"></i>
                                            Barangay
                                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Filters Display -->
                            <div id="activeFiltersContainer" class="pt-2 hidden">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Active filters:</span>
                                    <div id="activeFilters" class="flex flex-wrap gap-1">
                                        <!-- Active filters will be displayed here -->
                                    </div>
                                    <button id="clearAllFilters"
                                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 cursor-pointer">
                                        Clear all
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="table-container mobile-scroll">
                            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400 mobile-table">
                                <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-2 py-3">
                                            <input id="selectAllCheckbox" type="checkbox"
                                                class="w-4 h-4 text-blue-600 bg-gray-200 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-300">
                                        </th>
                                        <th scope="col" class="px-2 py-3">No.</th>
                                        <th scope="col" class="px-2 py-3">Name</th>
                                        <th scope="col" class="px-2 py-3">Birthdate</th>
                                        <th scope="col" class="px-2 py-3">Age</th>
                                        <th scope="col" class="px-2 py-3 sm-">Gender</th>
                                        <th scope="col" class="px-2 py-3">Civil Status</th>
                                        <th scope="col" class="px-2 py-3">Barangay</th>
                                        <th scope="col" class="px-2 py-3">Date Registered</th>
                                        <th scope="col" class="px-2 py-3">Date Modified</th>
                                        <th scope="col" class="px-2 py-3">Status</th>
                                        <th scope="col" class="px-2 py-3">Validation</th>
                                        <th scope="col" class="px-2 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="beneficiaryBody">
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav id="paginationNav"
                            class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-3 sm:p-4 border-t border-gray-200 dark:border-gray-700"
                            aria-label="Table navigation">
                        </nav>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Benefit Modal - CENTERED -->
    <div id="addBenefitModal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-600/50 bg-opacity-50">
        <div class="relative w-full max-w-2xl max-h-[90vh] p-4 modal-animate-in">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-800 p-4 sm:p-5">
                <div class="flex justify-between items-center pb-3 mb-3 sm:pb-4 sm:mb-4 border-b dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add Benefits
                    </h3>
                    <button type="button" id="closeBenefitModal"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form id="benefitForm">
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Selected Beneficiaries
                            </label>
                            <div id="selectedBeneficiariesList" class="max-h-32 overflow-y-auto border rounded p-3 dark:border-gray-600 text-sm">
                                <!-- Selected beneficiaries will be listed here -->
                            </div>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Benefits
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                    (Restrictions apply based on status and validation)
                                </span>
                            </label>
                            <div id="benefitsCheckboxContainer" class="max-h-40 overflow-y-auto border rounded p-3 dark:border-gray-600">
                                <!-- Benefits checkboxes with amount inputs will be populated here -->
                            </div>
                            <div id="benefitRestrictionsNote" class="text-xs text-gray-600 dark:text-gray-400 mt-2 hidden">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span id="restrictionText"></span>
                            </div>
                        </div>
                        <div>
                            <label for="benefitDate" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Date
                            </label>
                            <input type="date" id="benefitDate" name="benefitDate"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Benefits
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Benefits Modal - CENTERED -->
    <div id="viewBenefitsModal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
        <div class="relative w-full max-w-4xl max-h-[90vh] p-2 sm:p-4 modal-animate-in">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-800 p-4 sm:p-5">
                <div class="flex justify-between items-center pb-3 mb-3 sm:pb-4 sm:mb-4 border-b border-gray-500 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                        <span id="viewBeneficiaryName"></span> - Benefits History
                    </h3>
                    <button type="button" id="closeViewBenefitsModal"
                        class="text-gray-400 bg-transparent cursor-pointer hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-2 py-3">Benefit Name</th>
                                <th scope="col" class="px-2 py-3">Amount</th>
                                <th scope="col" class="px-2 py-3">Date Received</th>
                                <th scope="col" class="px-2 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="benefitsHistoryBody">
                            <!-- Benefits history will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pt-3 mt-3 sm:pt-4 sm:mt-4 border-t dark:border-gray-600 gap-2">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Total Benefits Received: ₱<span id="totalBenefitsAmount">0.00</span>
                    </div>
                    <button type="button" id="closeViewBenefitsModalBottom"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-sm cursor-pointer text-sm px-3 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 w-full sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits Report Modal - CENTERED -->
    <div id="benefitsReportModal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
        <div class="relative w-full max-w-3xl max-h-[90vh] p-2 sm:p-4 modal-animate-in">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-800 p-4 sm:p-5">
                <div class="flex justify-between items-center mb-3 sm:mb-4 border-b dark:border-gray-600 pb-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Print Benefits Report
                    </h3>
                    <button type="button" id="closeBenefitsReportModal"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form id="benefitsReportForm">
                    <div class="space-y-4 mb-4 overflow-auto max-h-[60vh] pr-2">
                        <div class="flex flex-row justify-between gap-5 w-full">
                            <!-- Month Selection -->
                            <div class="w-full">
                                <label for="reportMonth" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Select Month
                                </label>
                                <select id="reportMonth" name="reportMonth"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    required>
                                    <option value="">-- Select Month --</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>

                            <!-- Year Selection -->
                            <div class="w-full">
                                <label for="reportYear" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Select Year
                                </label>
                                <select id="reportYear" name="reportYear"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    required>
                                    <option value="">-- Select Year --</option>
                                    <!-- Years will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>

                        <!-- Benefit Selection -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Benefit Selection
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
                                <div class="flex items-center">
                                    <input id="benefitAll" type="radio" name="benefitOption" value="all" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="benefitAll" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        All Benefits
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="benefitSpecific" type="radio" name="benefitOption" value="specific"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="benefitSpecific" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Specific Benefit(s)
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="benefitMultiple" type="radio" name="benefitOption" value="multiple"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="benefitMultiple" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Multiple Benefits
                                    </label>
                                </div>
                            </div>

                            <!-- Single Specific Benefit Container -->
                            <div id="specificBenefitContainer" class="mt-2 hidden">
                                <select id="specificBenefit" name="specificBenefit"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option value="">-- Select Single Benefit --</option>
                                    <!-- Benefits will be populated by JavaScript -->
                                </select>
                            </div>

                            <!-- Multiple Benefits Container -->
                            <div id="multipleBenefitsContainer" class="mt-2 hidden">
                                <div class="bg-gray-50 border border-gray-300 rounded-lg p-3 dark:bg-gray-700 dark:border-gray-600 max-h-48 overflow-y-auto">
                                    <div class="flex items-center mb-2">
                                        <input id="selectAllBenefits" type="checkbox"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-white">
                                        <label for="selectAllBenefits" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            Select All Benefits
                                        </label>
                                    </div>
                                    <div id="multipleBenefitsList" class="space-y-1 pl-6">
                                        <!-- Multiple benefits checkboxes will be populated here -->
                                        <div class="text-gray-500 text-sm italic dark:text-white">Loading benefits...</div>
                                    </div>
                                </div>
                                <div id="selectedBenefitsCount" class="text-xs text-gray-600 dark:text-gray-400 mt-1 hidden">
                                    <span id="countSelectedBenefits">0</span> benefit(s) selected
                                </div>
                            </div>
                        </div>

                        <!-- Report Type -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Report Type
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="flex items-center">
                                    <input id="reportTypeSummary" type="radio" name="reportType" value="summary" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="reportTypeSummary" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Summary Report
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="reportTypeDetailed" type="radio" name="reportType" value="detailed"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="reportTypeDetailed" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Detailed List
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Include Details -->
                        <div class="space-y-1">
                            <div class="flex items-center">
                                <input id="includeTotals" type="checkbox" name="includeTotals" checked
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeTotals" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Include Totals Summary
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="includeBarangayBreakdown" type="checkbox" name="includeBarangayBreakdown"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeBarangayBreakdown" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Include Barangay Breakdown
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="includeBenefitBreakdown" type="checkbox" name="includeBenefitBreakdown" checked
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeBenefitBreakdown" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Include Benefit Type Breakdown
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="includeSeniorsList" type="checkbox" name="includeSeniorsList" checked
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeSeniorsList" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Include Complete List of Seniors
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="showOnlyBenefited" type="checkbox" name="showOnlyBenefited"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="showOnlyBenefited" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Show only seniors who received benefits
                                </label>
                            </div>
                        </div>

                        <!-- Report Title -->
                        <div>
                            <label for="benefitsReportTitle" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Report Title
                            </label>
                            <input type="text" id="benefitsReportTitle" name="benefitsReportTitle"
                                value="MSWD Paluan - Benefits Distribution Report"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t dark:border-gray-700">
                        <button type="button" id="cancelBenefitsReport"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 w-full sm:w-auto">
                            <i class="fas fa-print mr-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Popup Modal - CENTERED -->
    <div id="popupModal"
        class="fixed inset-0 bg-gray-600/50 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div id="popupBox"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg transform scale-95 opacity-0 transition-all duration-200 w-11/12 sm:w-80 p-4 mx-4 modal-animate-in">
            <h2 id="popupTitle" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Title</h2>
            <p id="popupMessage" class="text-sm text-gray-700 dark:text-gray-300 mb-4">Message</p>
            <div class="flex justify-end">
                <button id="popupCloseBtn"
                    class="px-4 py-2 bg-blue-600 cursor-pointer text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] min-w-[64px]">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Dropdown Menus -->
    <!-- Status Filter Dropdown -->
    <div id="statusFilterDropdown"
        class="z-50 hidden w-56 p-3 bg-white rounded-lg shadow dark:bg-gray-700 fixed md:absolute">
        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
            Filter by Status
        </h6>
        <ul id="statusList" class="space-y-2 text-sm" aria-labelledby="statusFilterButton">
            <li class="flex items-center">
                <input id="status-all" type="radio" name="status" value="all" checked
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="status-all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    All Status
                </label>
            </li>
            <li class="flex items-center">
                <input id="status-active" type="radio" name="status" value="Active"
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="status-active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    Active
                </label>
            </li>
            <li class="flex items-center">
                <input id="status-deceased" type="radio" name="status" value="Deceased"
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="status-deceased" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    Deceased
                </label>
            </li>
        </ul>
    </div>

    <!-- Validation Filter Dropdown (NEW) -->
    <div id="validationFilterDropdown"
        class="z-50 hidden w-56 p-3 bg-white rounded-lg shadow dark:bg-gray-700 fixed md:absolute">
        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
            Filter by Validation Status
        </h6>
        <ul id="validationList" class="space-y-2 text-sm" aria-labelledby="validationFilterButton">
            <li class="flex items-center">
                <input id="validation-all" type="radio" name="validation" value="all" checked
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="validation-all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    All Validation
                </label>
            </li>
            <li class="flex items-center">
                <input id="validation-validated" type="radio" name="validation" value="Validated"
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="validation-validated" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    <span class="validation-badge validation-validated">Validated</span>
                </label>
            </li>
            <li class="flex items-center">
                <input id="validation-pending" type="radio" name="validation" value="For Validation"
                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                           focus:ring-primary-500 dark:focus:ring-primary-600 
                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="validation-pending" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    <span class="validation-badge validation-pending">For Validation</span>
                </label>
            </li>
        </ul>
    </div>

    <!-- Benefits Filter Dropdown -->
    <div id="benefitsFilterDropdown"
        class="z-50 hidden w-56 p-3 bg-white rounded-lg shadow dark:bg-gray-700 fixed md:absolute">
        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
            Filter by Benefits
        </h6>
        <ul id="benefitTypeList" class="space-y-2 text-sm max-h-60 overflow-y-auto" aria-labelledby="benefitsFilterButton">
            <li class="text-gray-400 text-sm text-center">Loading benefits...</li>
        </ul>
        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
            <button id="clearBenefitsFilter"
                class="w-full text-xs text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white cursor-pointer py-2">
                Clear benefits filter
            </button>
        </div>
    </div>

    <!-- Barangay Filter Dropdown -->
    <div id="filterDropdown"
        class="z-50 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700 fixed md:absolute">
        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
            Barangay
        </h6>
        <ul id="barangayList" class="space-y-2 text-sm" aria-labelledby="dropdownDefault">
            <li class="text-gray-400 text-sm text-center">Loading...</li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="/MSWDPALUAN_SYSTEM-MAIN/js/tailwind.config.js"></script>
    <script>
        // ---------- THEME INITIALIZATION (MUST BE OUTSIDE DOMContentLoaded) ----------
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

        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }

        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const theme = e.newValue;
                setTheme(theme);
            }
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        initTheme();
    </script>

    <!-- Beneficiary Table Script -->
    <script>
        // Test function to verify the PHP file exists and works
        async function testBenefitsReportAPI() {
            try {
                console.log('Testing benefits report API...');

                const testUrl = '/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_monthly_benefits.php';
                const testData = {
                    month: '01',
                    year: '2024',
                    benefit_id: null
                };

                const response = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });

                const text = await response.text();
                console.log('Test response:', text);

                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        console.log('✅ Benefits report API test successful!');
                        return true;
                    } else {
                        console.error('❌ Benefits report API test failed:', result.message);
                        return false;
                    }
                } catch (parseError) {
                    console.error('❌ Failed to parse test response:', parseError);
                    console.error('Response text:', text);
                    return false;
                }
            } catch (error) {
                console.error('❌ Error testing benefits report API:', error);
                return false;
            }
        }

        // Call this in your initializePage function
        async function initializePage() {
            console.log("Initializing beneficiary page...");

            // Test the API first
            await testBenefitsReportAPI();

            setupResponsiveDropdowns();
            fetchBarangays();
            setupStatusFilter();
            setupValidationFilter();
            fetchBenefitTypes();
            fetchBenefits();
            fetchBeneficiaries();
        }
        document.addEventListener("DOMContentLoaded", () => {
            // Global variables
            window.globalSelectedBeneficiaries = new Map();
            let currentPage = 1;
            let totalPages = 1;
            let totalRecords = 0;
            let lastSearch = "";
            let selectedBarangays = [];
            let availableBenefits = [];
            let selectedBenefitTypes = [];
            let selectedStatus = 'all';
            let selectedValidation = 'all';

            // Store beneficiary details for benefit restrictions
            let beneficiaryDetails = new Map();

            // DOM elements
            const tableBody = document.getElementById("beneficiaryBody");
            const barangayList = document.getElementById("barangayList");
            const statusList = document.getElementById("statusList");
            const validationList = document.getElementById("validationList");
            const searchInput = document.getElementById("simple-search");
            const paginationNav = document.getElementById("paginationNav");
            const addBenefitBtnContainer = document.getElementById("addBenefitBtnContainer");
            const addBenefitBtn = document.getElementById("addBenefitBtn");
            const selectAllCheckbox = document.getElementById("selectAllCheckbox");
            const addBenefitModal = document.getElementById("addBenefitModal");
            const closeBenefitModal = document.getElementById("closeBenefitModal");
            const benefitForm = document.getElementById("benefitForm");
            const selectedBeneficiariesList = document.getElementById("selectedBeneficiariesList");
            const benefitsCheckboxContainer = document.getElementById("benefitsCheckboxContainer");
            const benefitRestrictionsNote = document.getElementById("benefitRestrictionsNote");
            const restrictionText = document.getElementById("restrictionText");

            // View Benefits Modal elements
            const viewBenefitsModal = document.getElementById("viewBenefitsModal");
            const closeViewBenefitsModal = document.getElementById("closeViewBenefitsModal");
            const closeViewBenefitsModalBottom = document.getElementById("closeViewBenefitsModalBottom");
            const viewBeneficiaryName = document.getElementById("viewBeneficiaryName");
            const benefitsHistoryBody = document.getElementById("benefitsHistoryBody");
            const totalBenefitsAmount = document.getElementById("totalBenefitsAmount");

            // Filter elements
            const statusFilterButton = document.getElementById("statusFilterButton");
            const statusFilterDropdown = document.getElementById("statusFilterDropdown");
            const validationFilterButton = document.getElementById("validationFilterButton");
            const validationFilterDropdown = document.getElementById("validationFilterDropdown");
            const benefitsFilterButton = document.getElementById("benefitsFilterButton");
            const benefitsFilterDropdown = document.getElementById("benefitsFilterDropdown");
            const benefitTypeList = document.getElementById("benefitTypeList");
            const clearBenefitsFilterBtn = document.getElementById("clearBenefitsFilter");
            const activeFiltersContainer = document.getElementById("activeFiltersContainer");
            const activeFilters = document.getElementById("activeFilters");
            const clearAllFiltersBtn = document.getElementById("clearAllFilters");

            // Print elements
            const printModal = document.getElementById("printModal");
            const closePrintModal = document.getElementById("closePrintModal");
            const cancelPrint = document.getElementById("cancelPrint");
            const printForm = document.getElementById("printForm");
            const printButton = document.getElementById("printButton");
            const totalRecordsCount = document.getElementById("totalRecordsCount");
            const selectedCount = document.getElementById("selectedCount");


            // ---------------- BENEFITS REPORT FUNCTIONALITY ----------------

            // DOM elements for benefits report
            const benefitsReportModal = document.getElementById('benefitsReportModal');
            const closeBenefitsReportModal = document.getElementById('closeBenefitsReportModal');
            const cancelBenefitsReport = document.getElementById('cancelBenefitsReport');
            const benefitsReportForm = document.getElementById('benefitsReportForm');
            const printBenefitsReportButton = document.getElementById('printBenefitsReportButton');
            const reportYearSelect = document.getElementById('reportYear');
            const specificBenefitContainer = document.getElementById('specificBenefitContainer');
            const multipleBenefitsContainer = document.getElementById('multipleBenefitsContainer');
            const specificBenefitSelect = document.getElementById('specificBenefit');
            const multipleBenefitsList = document.getElementById('multipleBenefitsList');
            const selectAllBenefitsCheckbox = document.getElementById('selectAllBenefits');
            const selectedBenefitsCount = document.getElementById('selectedBenefitsCount');
            const countSelectedBenefits = document.getElementById('countSelectedBenefits');
            const benefitAllRadio = document.getElementById('benefitAll');
            const benefitSpecificRadio = document.getElementById('benefitSpecific');
            const benefitMultipleRadio = document.getElementById('benefitMultiple');

            // Store available benefits for the report
            let reportAvailableBenefits = [];

            // Show benefits report modal
            if (printBenefitsReportButton) {
                printBenefitsReportButton.addEventListener('click', () => {
                    populateYears();
                    populateBenefitsForReport();
                    benefitsReportForm.reset();
                    benefitAllRadio.checked = true;
                    specificBenefitContainer.classList.add('hidden');
                    multipleBenefitsContainer.classList.add('hidden');
                    benefitsReportModal.classList.remove('hidden');
                });
            }

            // Close benefits report modal
            if (closeBenefitsReportModal) {
                closeBenefitsReportModal.addEventListener('click', () => {
                    benefitsReportModal.classList.add('hidden');
                });
            }

            if (cancelBenefitsReport) {
                cancelBenefitsReport.addEventListener('click', () => {
                    benefitsReportModal.classList.add('hidden');
                });
            }

            // Toggle between benefit selection options
            if (benefitAllRadio && benefitSpecificRadio && benefitMultipleRadio) {
                benefitAllRadio.addEventListener('change', function() {
                    specificBenefitContainer.classList.add('hidden');
                    multipleBenefitsContainer.classList.add('hidden');
                    selectedBenefitsCount.classList.add('hidden');
                });

                benefitSpecificRadio.addEventListener('change', function() {
                    specificBenefitContainer.classList.remove('hidden');
                    multipleBenefitsContainer.classList.add('hidden');
                    selectedBenefitsCount.classList.add('hidden');
                });

                benefitMultipleRadio.addEventListener('change', function() {
                    specificBenefitContainer.classList.add('hidden');
                    multipleBenefitsContainer.classList.remove('hidden');
                    selectedBenefitsCount.classList.remove('hidden');
                });
            }

            // Populate years dropdown
            function populateYears() {
                const currentYear = new Date().getFullYear();
                reportYearSelect.innerHTML = '<option value="">-- Select Year --</option>';

                // Add current year and previous 5 years
                for (let year = currentYear; year >= currentYear - 5; year--) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    reportYearSelect.appendChild(option);
                }
            }

            // Populate benefits dropdown specifically for report
            function populateBenefitsForReport() {
                console.log('Populating benefits for report...');

                // Use the already loaded availableBenefits if they exist
                if (availableBenefits && availableBenefits.length > 0) {
                    reportAvailableBenefits = availableBenefits;
                    updateBenefitsDropdown();
                    updateMultipleBenefitsList();
                    return;
                }

                // Otherwise fetch benefits
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits.php")
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                        return res.json();
                    })
                    .then(data => {
                        if (data.success && data.benefits && data.benefits.length > 0) {
                            reportAvailableBenefits = data.benefits;
                            availableBenefits = data.benefits; // Also update the global variable
                            updateBenefitsDropdown();
                            updateMultipleBenefitsList();
                            console.log('Benefits loaded for report:', reportAvailableBenefits.length);
                        } else {
                            console.error('No benefits found:', data.message);
                            showPopup('No benefits found in the system.', 'error');
                            updateBenefitsDropdown();
                            updateMultipleBenefitsList();
                        }
                    })
                    .catch(err => {
                        console.error('Error loading benefits for report:', err);
                        showPopup('Failed to load benefits for report.', 'error');
                        updateBenefitsDropdown();
                        updateMultipleBenefitsList();
                    });
            }

            function updateBenefitsDropdown() {
                specificBenefitSelect.innerHTML = '<option value="">-- Select Single Benefit --</option>';

                if (reportAvailableBenefits.length > 0) {
                    reportAvailableBenefits.forEach(benefit => {
                        const option = document.createElement('option');
                        option.value = benefit.id;
                        option.textContent = benefit.benefit_name;
                        specificBenefitSelect.appendChild(option);
                    });
                    console.log('Benefits dropdown updated with', reportAvailableBenefits.length, 'benefits');
                } else {
                    specificBenefitSelect.innerHTML = '<option value="">No benefits available</option>';
                }
            }

            function updateMultipleBenefitsList() {
                multipleBenefitsList.innerHTML = '';

                if (reportAvailableBenefits.length === 0) {
                    multipleBenefitsList.innerHTML = '<div class="text-gray-500 text-sm italic">No benefits available</div>';
                    return;
                }

                reportAvailableBenefits.forEach((benefit, index) => {
                    const benefitId = `multi-benefit-${index}`;
                    const checkbox = document.createElement('div');
                    checkbox.className = 'flex items-center';
                    checkbox.innerHTML = `
            <input id="${benefitId}" type="checkbox" value="${benefit.id}"
                class="multi-benefit-checkbox w-4 h-4 text-blue-600 bg-gray-100 border border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-600"
                data-name="${benefit.benefit_name}">
            <label for="${benefitId}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                ${benefit.benefit_name}
            </label>
        `;
                    multipleBenefitsList.appendChild(checkbox);
                });

                // Add event listeners for multi-benefit checkboxes
                multipleBenefitsList.querySelectorAll('.multi-benefit-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelectedBenefitsCount);
                });

                // Add event listener for "Select All" checkbox
                if (selectAllBenefitsCheckbox) {
                    selectAllBenefitsCheckbox.addEventListener('change', function() {
                        const checkboxes = multipleBenefitsList.querySelectorAll('.multi-benefit-checkbox');
                        checkboxes.forEach(cb => {
                            cb.checked = this.checked;
                        });
                        updateSelectedBenefitsCount();
                    });
                }
            }

            function updateSelectedBenefitsCount() {
                const selectedCheckboxes = multipleBenefitsList.querySelectorAll('.multi-benefit-checkbox:checked');
                const count = selectedCheckboxes.length;
                countSelectedBenefits.textContent = count;

                // Update "Select All" checkbox state
                if (selectAllBenefitsCheckbox) {
                    const allCheckboxes = multipleBenefitsList.querySelectorAll('.multi-benefit-checkbox');
                    selectAllBenefitsCheckbox.checked = count > 0 && count === allCheckboxes.length;
                    selectAllBenefitsCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
                }

                if (count > 0) {
                    selectedBenefitsCount.classList.remove('hidden');
                } else {
                    selectedBenefitsCount.classList.add('hidden');
                }
            }

            // Get selected multiple benefits
            function getSelectedMultipleBenefits() {
                const selectedBenefits = [];
                const selectedCheckboxes = multipleBenefitsList.querySelectorAll('.multi-benefit-checkbox:checked');

                selectedCheckboxes.forEach(checkbox => {
                    selectedBenefits.push({
                        id: checkbox.value,
                        name: checkbox.getAttribute('data-name')
                    });
                });

                return selectedBenefits;
            }

            // Handle benefits report form submission
            if (benefitsReportForm) {
                benefitsReportForm.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const formData = new FormData(benefitsReportForm);
                    const month = formData.get('reportMonth');
                    const year = formData.get('reportYear');
                    const benefitOption = formData.get('benefitOption');
                    const specificBenefitId = formData.get('benefitOption') === 'specific' ? formData.get('specificBenefit') : null;
                    const reportType = formData.get('reportType');
                    const includeTotals = formData.get('includeTotals') === 'on';
                    const includeBarangayBreakdown = formData.get('includeBarangayBreakdown') === 'on';
                    const includeBenefitBreakdown = formData.get('includeBenefitBreakdown') === 'on';
                    const includeSeniorsList = formData.get('includeSeniorsList') === 'on';
                    const showOnlyBenefited = formData.get('showOnlyBenefited') === 'on';
                    const reportTitle = formData.get('benefitsReportTitle') || 'MSWD Paluan - Benefits Distribution Report';

                    // Validation
                    if (!month || !year) {
                        showPopup('Please select both month and year.', 'error');
                        return;
                    }

                    if (benefitOption === 'specific' && !specificBenefitId) {
                        showPopup('Please select a specific benefit.', 'error');
                        return;
                    }

                    if (benefitOption === 'multiple') {
                        const selectedMultipleBenefits = getSelectedMultipleBenefits();
                        if (selectedMultipleBenefits.length === 0) {
                            showPopup('Please select at least one benefit for multiple benefits report.', 'error');
                            return;
                        }
                    }

                    // Show loading indicator
                    showPopup('Generating report... Please wait.', 'info');

                    try {
                        // Prepare request data
                        const requestData = {
                            month: month,
                            year: year,
                            benefit_option: benefitOption
                        };

                        // Add specific benefit filter if selected
                        if (benefitOption === 'specific' && specificBenefitId) {
                            requestData.benefit_id = specificBenefitId;

                            // Find the benefit name for the report
                            const selectedBenefit = reportAvailableBenefits.find(b => b.id == specificBenefitId);
                            if (selectedBenefit) {
                                console.log('Selected benefit:', selectedBenefit.benefit_name);
                                requestData.benefit_name = selectedBenefit.benefit_name;
                            }
                        }

                        // Add multiple benefits filter if selected
                        if (benefitOption === 'multiple') {
                            const selectedMultipleBenefits = getSelectedMultipleBenefits();
                            requestData.benefit_ids = selectedMultipleBenefits.map(b => b.id);
                            requestData.benefit_names = selectedMultipleBenefits.map(b => b.name);
                            console.log('Selected multiple benefits:', requestData.benefit_names);
                        }

                        console.log('Sending request data:', requestData);

                        // Make the API request
                        const url = `/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_monthly_benefits.php`;

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(requestData)
                        });

                        // Get response text first
                        const responseText = await response.text();
                        console.log('Raw API response:', responseText.substring(0, 500) + '...');

                        let result;
                        try {
                            result = JSON.parse(responseText);
                            console.log('Parsed response structure:', Object.keys(result));
                        } catch (parseError) {
                            console.error('Failed to parse JSON response:', parseError);

                            // Check if it's a PHP error
                            if (responseText.includes('Fatal error') || responseText.includes('Parse error')) {
                                console.error('PHP Error detected:', responseText);
                                throw new Error('PHP error in fetch_monthly_benefits.php. Check server logs.');
                            }

                            throw new Error('Invalid JSON response from server');
                        }

                        // Check if request was successful
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        if (result.success) {
                            console.log('Report data received successfully');

                            // Generate the benefits report
                            generateBenefitsReport(result.data, {
                                month: month,
                                year: year,
                                benefitOption: benefitOption,
                                specificBenefitId: specificBenefitId,
                                selectedMultipleBenefits: benefitOption === 'multiple' ? getSelectedMultipleBenefits() : [],
                                reportType: reportType,
                                includeTotals: includeTotals,
                                includeBarangayBreakdown: includeBarangayBreakdown,
                                includeBenefitBreakdown: includeBenefitBreakdown,
                                includeSeniorsList: includeSeniorsList,
                                showOnlyBenefited: showOnlyBenefited,
                                reportTitle: reportTitle,
                                selectedBenefitName: requestData.benefit_name || 'Selected Benefit'
                            });

                            // Close modal
                            benefitsReportModal.classList.add('hidden');

                        } else {
                            console.error('Server returned error:', result);
                            let errorMsg = result.message || 'Failed to generate report.';

                            if (result.error) {
                                errorMsg += ` Error: ${result.error}`;
                            }

                            showPopup(errorMsg, 'error');
                        }

                    } catch (error) {
                        console.error('Error generating benefits report:', error);
                        console.error('Error stack:', error.stack);

                        let errorMessage = 'An error occurred while generating the report. ';

                        if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                            errorMessage += 'Cannot connect to server. Please check your internet connection.';
                        } else if (error.message.includes('PHP error')) {
                            errorMessage += 'Server-side PHP error. Please contact the administrator.';
                        } else if (error.message.includes('JSON')) {
                            errorMessage += 'Server returned invalid response format.';
                        } else {
                            errorMessage += error.message;
                        }

                        showPopup(errorMessage, 'error');
                    }
                });
            }

            // Modified generateBenefitsReport function to handle multiple benefits
            function generateBenefitsReport(data, options) {
                // Validate data
                if (!data) {
                    showPopup('No data received for report generation.', 'error');
                    return;
                }

                // Ensure we have at least empty arrays if data is missing
                if (!data.beneficiaries || !Array.isArray(data.beneficiaries)) {
                    data.beneficiaries = [];
                }
                if (!data.summary || typeof data.summary !== 'object') {
                    data.summary = {
                        total_beneficiaries: 0,
                        total_benefits: 0,
                        total_amount: 0,
                        average_amount: 0,
                        total_seniors: 0,
                        benefited_seniors_count: 0,
                        non_benefited_seniors_count: 0
                    };
                }
                if (!data.barangay_breakdown || !Array.isArray(data.barangay_breakdown)) {
                    data.barangay_breakdown = [];
                }
                if (!data.benefit_breakdown || !Array.isArray(data.benefit_breakdown)) {
                    data.benefit_breakdown = [];
                }
                if (!data.benefited_seniors || !Array.isArray(data.benefited_seniors)) {
                    data.benefited_seniors = [];
                }

                const {
                    month,
                    year,
                    benefitOption,
                    specificBenefitId,
                    selectedMultipleBenefits,
                    reportType,
                    includeTotals,
                    includeBarangayBreakdown,
                    includeBenefitBreakdown,
                    includeSeniorsList,
                    showOnlyBenefited,
                    reportTitle,
                    selectedBenefitName
                } = options;

                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                const monthName = monthNames[parseInt(month) - 1];

                // Get selected benefit name(s)
                let reportBenefitName = 'All Benefits';
                let reportSubtitle = '';

                if (benefitOption === 'specific') {
                    reportBenefitName = selectedBenefitName || 'Selected Benefit';
                    reportSubtitle = `Specific Benefit: ${reportBenefitName}`;
                } else if (benefitOption === 'multiple' && selectedMultipleBenefits.length > 0) {
                    const benefitNames = selectedMultipleBenefits.map(b => b.name);
                    if (benefitNames.length === 1) {
                        reportBenefitName = benefitNames[0];
                        reportSubtitle = `Specific Benefit: ${reportBenefitName}`;
                    } else if (benefitNames.length === 2) {
                        reportBenefitName = benefitNames.join(' and ');
                        reportSubtitle = `Multiple Benefits: ${reportBenefitName}`;
                    } else if (benefitNames.length > 2) {
                        reportBenefitName = `${benefitNames[0]} and ${benefitNames.length - 1} more`;
                        reportSubtitle = `Multiple Benefits: ${benefitNames.length} selected`;
                    }
                } else {
                    reportSubtitle = 'All Benefits';
                }

                // Create print window
                const printWindow = window.open('', '_blank');

                // Get user name safely
                const generatedByName = window.phpUserData && window.phpUserData.full_name ?
                    window.phpUserData.full_name :
                    (window.phpUserData && window.phpUserData.firstname && window.phpUserData.lastname ?
                        `${window.phpUserData.firstname} ${window.phpUserData.lastname}` :
                        'System Administrator');

                // Build HTML content
                let html = `
<!DOCTYPE html>
<html>
<head>
    <title>${reportTitle}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            margin: 0;
            color: #1e40af;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        .report-info {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
        }
        .report-info h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .benefit-highlight {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .multiple-benefits-list {
            background-color: #e8f4fd;
            border-left: 4px solid #3b82f6;
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .multiple-benefits-list ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
        }
        .multiple-benefits-list li {
            margin: 3px 0;
        }
        .summary-box {
            background: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .summary-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #3b82f6;
        }
        .summary-item h4 {
            margin: 0 0 5px 0;
            color: #4b5563;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        th {
            background: #3b82f6;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2563eb;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            border-left: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .total-row {
            background: #dbeafe !important;
            font-weight: bold;
        }
        .section-title {
            font-size: 18px;
            color: #1e40af;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .breakdown-table {
            font-size: 10px;
        }
        .breakdown-table th,
        .breakdown-table td {
            padding: 8px 6px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        /* Media query for printing */
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            table {
                font-size: 9px;
            }
            .header h1 {
                font-size: 20px;
            }
            .summary-item .value {
                font-size: 16px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print();" style="background-color: #3b82f6; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
            🖨️ Print Report
        </button>
        <button onclick="window.close();" style="background-color: #6b7280; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            ❌ Close
        </button>
    </div>
    
    <div class="header">
        <h1>${reportTitle}</h1>
        <div class="subtitle">
            ${monthName} ${year} • ${reportSubtitle} • 
            Generated: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
        </div>
    </div>`;

                // Highlight specific or multiple benefits
                if (benefitOption === 'specific') {
                    html += `
    <div class="benefit-highlight">
        <strong>📊 Specific Benefit Report:</strong> This report shows distribution data specifically for 
        <strong>"${reportBenefitName}"</strong> during ${monthName} ${year}.
    </div>`;
                } else if (benefitOption === 'multiple' && selectedMultipleBenefits.length > 0) {
                    html += `
    <div class="multiple-benefits-list">
        <strong>📊 Multiple Benefits Report:</strong> This report shows distribution data for the following benefits during ${monthName} ${year}:
        <ul>`;

                    selectedMultipleBenefits.forEach(benefit => {
                        html += `<li>${benefit.name}</li>`;
                    });

                    html += `
        </ul>
    </div>`;
                }

                // Add report info section
                html += `
    <div class="report-info">
        <h3>Report Details</h3>
        <p><strong>Period:</strong> ${monthName} ${year}</p>
        <p><strong>Benefit Coverage:</strong> ${reportBenefitName}</p>`;

                if (benefitOption === 'multiple' && selectedMultipleBenefits.length > 1) {
                    html += `<p><strong>Number of Benefits:</strong> ${selectedMultipleBenefits.length}</p>`;
                }

                html += `
        <p><strong>Report Type:</strong> ${reportType === 'detailed' ? 'Detailed List' : 'Summary Report'}</p>
        <p><strong>Generated by:</strong> ${generatedByName}</p>
    </div>`;

                // Highlight specific benefit if selected
                if (benefitOption === 'specific') {
                    html += `
    <div class="benefit-highlight">
        <strong>📊 Specific Benefit Report:</strong> This report shows distribution data specifically for 
        <strong>"${reportBenefitName}"</strong> during ${monthName} ${year}.
    </div>`;
                }

                // Add summary section if requested
                if (includeTotals && data.summary) {
                    const summary = data.summary;
                    html += `
    <div class="summary-box">
        <h3>Summary Overview</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>Total Beneficiaries</h4>
                <div class="value">${summary.total_beneficiaries || 0}</div>
            </div>
            <div class="summary-item">
                <h4>Total Benefits Distributed</h4>
                <div class="value">${summary.total_benefits || 0}</div>
            </div>
            <div class="summary-item">
                <h4>Total Amount</h4>
                <div class="value">₱${parseFloat(summary.total_amount || 0).toFixed(2)}</div>
            </div>`;

                    if (benefitOption === 'all') {
                        html += `
            <div class="summary-item">
                <h4>Average Amount per Benefit</h4>
                <div class="value">₱${parseFloat(summary.average_amount || 0).toFixed(2)}</div>
            </div>`;
                    }

                    html += `
        </div>
    </div>`;
                }

                // Add detailed list if requested and data exists
                if (reportType === 'detailed' && data.beneficiaries && data.beneficiaries.length > 0) {
                    html += `
    <h3 class="section-title">Beneficiaries List ${benefitOption === 'specific' ? `for ${reportBenefitName}` : ''}</h3>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>`;

                    if (benefitOption === 'all') {
                        html += `<th>Benefit Received</th>`;
                    }

                    html += `
                <th>Amount</th>
                <th>Date Received</th>
                <th>Barangay</th>
            </tr>
        </thead>
        <tbody>`;

                    let totalAmount = 0;
                    data.beneficiaries.forEach((beneficiary, index) => {
                        const amount = parseFloat(beneficiary.amount || 0);
                        totalAmount += amount;

                        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${beneficiary.full_name || 'N/A'}</td>
                <td>${beneficiary.age || 'N/A'}</td>
                <td>${beneficiary.gender || 'N/A'}</td>`;

                        if (benefitOption === 'all') {
                            html += `<td>${beneficiary.benefit_name || 'N/A'}</td>`;
                        }

                        html += `
                <td>₱${amount.toFixed(2)}</td>
                <td>${beneficiary.date_received ? new Date(beneficiary.date_received).toLocaleDateString() : 'N/A'}</td>
                <td>${beneficiary.barangay || 'N/A'}</td>
            </tr>`;
                    });

                    html += `
            <tr class="total-row">
                <td colspan="${benefitOption === 'all' ? 7 : 6}" style="text-align: right;"><strong>Total:</strong></td>
                <td><strong>₱${totalAmount.toFixed(2)}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>`;
                } else if (reportType === 'detailed') {
                    html += `
    <div class="no-data">
        <p>No beneficiaries found for the selected criteria.</p>
        <p><small>Month: ${monthName} ${year} | Benefit: ${reportBenefitName}</small></p>
    </div>`;
                }

                // Add barangay breakdown if requested
                if (includeBarangayBreakdown && data.barangay_breakdown && data.barangay_breakdown.length > 0) {
                    html += `
    <h3 class="section-title">Barangay Breakdown</h3>
    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Barangay</th>
                <th>Number of Beneficiaries</th>
                <th>Total Amount</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>`;

                    let barangayTotalAmount = 0;
                    let barangayTotalBeneficiaries = 0;

                    data.barangay_breakdown.forEach(item => {
                        const amount = parseFloat(item.total_amount || 0);
                        const beneficiaries = parseInt(item.beneficiary_count || 0);
                        barangayTotalAmount += amount;
                        barangayTotalBeneficiaries += beneficiaries;

                        const percentage = barangayTotalAmount > 0 ? ((amount / barangayTotalAmount) * 100).toFixed(1) : '0.0';

                        html += `
            <tr>
                <td>${item.barangay || 'N/A'}</td>
                <td>${beneficiaries}</td>
                <td>₱${amount.toFixed(2)}</td>
                <td>${percentage}%</td>
            </tr>`;
                    });

                    html += `
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td><strong>${barangayTotalBeneficiaries}</strong></td>
                <td><strong>₱${barangayTotalAmount.toFixed(2)}</strong></td>
                <td><strong>100%</strong></td>
            </tr>
        </tbody>
    </table>`;
                }

                // Add benefit type breakdown if requested and if showing all benefits
                if (includeBenefitBreakdown && benefitOption === 'all' && data.benefit_breakdown && data.benefit_breakdown.length > 0) {
                    html += `
    <h3 class="section-title">Benefit Type Breakdown</h3>
    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Benefit Type</th>
                <th>Number of Recipients</th>
                <th>Total Amount</th>
                <th>Average Amount</th>
            </tr>
        </thead>
        <tbody>`;

                    let benefitTotalAmount = 0;
                    let benefitTotalRecipients = 0;

                    data.benefit_breakdown.forEach(item => {
                        const amount = parseFloat(item.total_amount || 0);
                        const recipients = parseInt(item.recipient_count || 0);
                        const average = recipients > 0 ? (amount / recipients) : 0;
                        benefitTotalAmount += amount;
                        benefitTotalRecipients += recipients;

                        html += `
            <tr>
                <td>${item.benefit_name || 'N/A'}</td>
                <td>${recipients}</td>
                <td>₱${amount.toFixed(2)}</td>
                <td>₱${average.toFixed(2)}</td>
            </tr>`;
                    });

                    html += `
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td><strong>${benefitTotalRecipients}</strong></td>
                <td><strong>₱${benefitTotalAmount.toFixed(2)}</strong></td>
                <td><strong>₱${(benefitTotalRecipients > 0 ? benefitTotalAmount / benefitTotalRecipients : 0).toFixed(2)}</strong></td>
            </tr>
        </tbody>
    </table>`;
                }

                // Add benefited seniors list if requested
                if (includeSeniorsList && data.benefited_seniors && data.benefited_seniors.length > 0) {
                    html += `
    <h3 class="section-title">Seniors Who Received Benefits ${benefitOption === 'specific' ? `(${reportBenefitName})` : ''}</h3>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Barangay</th>`;
                    if (benefitOption === 'all') {
                        html += `<th>Benefits Received</th>`;
                    }

                    html += `
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>`;

                    data.benefited_seniors.forEach((senior, index) => {
                        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${senior.full_name || 'N/A'}</td>
                <td>${senior.age || 'N/A'}</td>
                <td>${senior.gender || 'N/A'}</td>
                <td>${senior.barangay || 'N/A'}</td>`;

                        if (benefitOption === 'all') {
                            html += `<td>${senior.benefits_received || 'N/A'}</td>`;
                        }

                        html += `
                <td><strong>₱${parseFloat(senior.total_amount_received || 0).toFixed(2)}</strong></td>
            </tr>`;
                    });

                    // Calculate total for benefited seniors
                    const totalBenefitedAmount = data.benefited_seniors.reduce((sum, senior) => {
                        return sum + parseFloat(senior.total_amount_received || 0);
                    }, 0);

                    html += `
            <tr class="total-row">
                <td colspan="${benefitOption === 'all' ? 6 : 5}" style="text-align: right;"><strong>Total Amount:</strong></td>
                <td><strong>₱${totalBenefitedAmount.toFixed(2)}</strong></td>
            </tr>
        </tbody>
    </table>`;
                }

                // Add footer
                html += `
    <div class="footer">
        <p><strong>MSWD Paluan - Municipal Social Welfare and Development Office</strong></p>
        <p>Benefits Distribution Report for ${monthName} ${year}</p>
        <p>Benefit: ${reportBenefitName}</p>
        <p>Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
        <p>Page generated automatically by MSWD Paluan System</p>
    </div>
</body>
</html>`;

                // Write to print window
                printWindow.document.write(html);
                printWindow.document.close();

                // Focus window
                printWindow.focus();

                // Auto-print after a short delay
                setTimeout(() => {
                    printWindow.print();
                }, 1000);
            }

            // ---------------- BENEFIT RESTRICTION LOGIC ----------------
            function checkBenefitRestrictions(beneficiaryStatus, beneficiaryValidation) {
                const restrictions = {
                    deceased: {
                        allowed: ["Burial Assistance"],
                        message: "Deceased seniors can only receive Burial Assistance"
                    },
                    validated: {
                        disallowed: ["Burial Assistance", "LSP Non Pensioners"],
                        message: "Validated seniors cannot receive Burial Assistance or LSP Non Pensioners"
                    },
                    pending: {
                        allowed: "all",
                        message: "For Validation seniors can receive all benefits except Burial Assistance if deceased"
                    }
                };

                return restrictions;
            }

            function normalizeBenefitName(benefitName) {
                return benefitName.toLowerCase().trim();
            }

            function isBurialBenefit(benefitName) {
                const normalized = normalizeBenefitName(benefitName);
                return normalized.includes('burial');
            }

            function isNonPensionersBenefit(benefitName) {
                const normalized = normalizeBenefitName(benefitName);
                const nonPensionKeywords = ['non pension', 'non-pension', 'nonpension', 'non pensioner', 'non-pensioner'];
                return nonPensionKeywords.some(keyword => normalized.includes(keyword));
            }

            function analyzeSelection(selectedBeneficiaries) {
                let hasDeceased = false;
                let hasActiveValidated = false;
                let hasActiveForValidation = false;
                let countDeceased = 0;
                let countActiveValidated = 0;
                let countActiveForValidation = 0;

                for (const [id, details] of selectedBeneficiaries) {
                    if (details.status === "Deceased") {
                        hasDeceased = true;
                        countDeceased++;
                    } else if (details.status === "Active" && details.validation === "Validated") {
                        hasActiveValidated = true;
                        countActiveValidated++;
                    } else if (details.status === "Active" && details.validation === "For Validation") {
                        hasActiveForValidation = true;
                        countActiveForValidation++;
                    }
                }

                return {
                    hasDeceased,
                    hasActiveValidated,
                    hasActiveForValidation,
                    countDeceased,
                    countActiveValidated,
                    countActiveForValidation,
                    totalSelected: selectedBeneficiaries.size
                };
            }

            function checkBenefitForSelection(benefitName, selectionAnalysis) {
                const isBurial = isBurialBenefit(benefitName);
                const isNonPension = isNonPensionersBenefit(benefitName);

                // RULE 1: If selection contains ONLY deceased
                if (selectionAnalysis.hasDeceased &&
                    !selectionAnalysis.hasActiveValidated &&
                    !selectionAnalysis.hasActiveForValidation) {
                    return {
                        visible: isBurial,
                        enabled: isBurial,
                        reason: isBurial ? "" : "Not available for Deceased seniors"
                    };
                }

                // RULE 2: If selection contains ONLY active validated
                if (!selectionAnalysis.hasDeceased &&
                    selectionAnalysis.hasActiveValidated &&
                    !selectionAnalysis.hasActiveForValidation) {
                    if (isNonPension) {
                        return {
                            visible: true,
                            enabled: false,
                            reason: "Not available for Active Validated seniors"
                        };
                    }
                    return {
                        visible: true,
                        enabled: true,
                        reason: ""
                    };
                }

                // RULE 3: If selection contains ONLY active for validation
                if (!selectionAnalysis.hasDeceased &&
                    !selectionAnalysis.hasActiveValidated &&
                    selectionAnalysis.hasActiveForValidation) {
                    return {
                        visible: true,
                        enabled: true,
                        reason: ""
                    };
                }

                // RULE 4: If selection contains BOTH deceased and active
                if (selectionAnalysis.hasDeceased &&
                    (selectionAnalysis.hasActiveValidated || selectionAnalysis.hasActiveForValidation)) {
                    if (isBurial) {
                        return {
                            visible: true,
                            enabled: false,
                            reason: "Mixed selection - Only Deceased seniors can receive this benefit"
                        };
                    }
                    return {
                        visible: false,
                        enabled: false,
                        reason: "Not available for mixed selection"
                    };
                }

                // RULE 5: If selection contains BOTH active validated and for validation
                if (!selectionAnalysis.hasDeceased &&
                    selectionAnalysis.hasActiveValidated &&
                    selectionAnalysis.hasActiveForValidation) {
                    if (isNonPension) {
                        return {
                            visible: true,
                            enabled: false,
                            reason: "Not available when Active Validated seniors are selected"
                        };
                    }
                    return {
                        visible: true,
                        enabled: true,
                        reason: ""
                    };
                }

                return {
                    visible: true,
                    enabled: true,
                    reason: ""
                };
            }

            function getBenefitRestrictionMessage(selectionAnalysis) {
                const totalSelected = selectionAnalysis.totalSelected;

                if (totalSelected === 0) {
                    return "No beneficiaries selected";
                }

                let message = `Selected ${totalSelected} beneficiary(ies): `;
                const parts = [];

                if (selectionAnalysis.countDeceased > 0) parts.push(`${selectionAnalysis.countDeceased} Deceased`);
                if (selectionAnalysis.countActiveValidated > 0) parts.push(`${selectionAnalysis.countActiveValidated} Active Validated`);
                if (selectionAnalysis.countActiveForValidation > 0) parts.push(`${selectionAnalysis.countActiveForValidation} Active For Validation`);

                message += parts.join(", ");

                if (selectionAnalysis.hasDeceased &&
                    !selectionAnalysis.hasActiveValidated &&
                    !selectionAnalysis.hasActiveForValidation) {
                    message += " - Only Burial benefits available";
                } else if (!selectionAnalysis.hasDeceased &&
                    selectionAnalysis.hasActiveValidated &&
                    !selectionAnalysis.hasActiveForValidation) {
                    message += " - Non Pensioners benefits disabled";
                } else if (!selectionAnalysis.hasDeceased &&
                    !selectionAnalysis.hasActiveValidated &&
                    selectionAnalysis.hasActiveForValidation) {
                    message += " - All benefits available";
                } else if (selectionAnalysis.hasDeceased &&
                    (selectionAnalysis.hasActiveValidated || selectionAnalysis.hasActiveForValidation)) {
                    message += " - Mixed selection: Only Burial benefits shown (disabled for Active)";
                } else if (!selectionAnalysis.hasDeceased &&
                    selectionAnalysis.hasActiveValidated &&
                    selectionAnalysis.hasActiveForValidation) {
                    message += " - Mixed Active: Non Pensioners benefits disabled";
                }

                return message;
            }

            function updateBenefitRestrictionsNote(selectionAnalysis) {
                if (selectionAnalysis.totalSelected === 0) {
                    benefitRestrictionsNote.classList.add('hidden');
                    return;
                }

                restrictionText.textContent = getBenefitRestrictionMessage(selectionAnalysis);
                benefitRestrictionsNote.classList.remove('hidden');
            }

            function populateBenefitsCheckboxesWithRestrictions(selectedBeneficiaries) {
                benefitsCheckboxContainer.innerHTML = "";

                if (availableBenefits.length === 0) {
                    benefitsCheckboxContainer.innerHTML = '<p class="text-gray-500 text-center py-2">No benefits available</p>';
                    return;
                }

                const selectionAnalysis = analyzeSelection(selectedBeneficiaries);
                let visibleBenefitsCount = 0;

                availableBenefits.forEach((benefit, index) => {
                    const benefitId = `benefit-${index}`;
                    const amountId = `amount-${index}`;
                    const restriction = checkBenefitForSelection(benefit.benefit_name, selectionAnalysis);

                    if (!restriction.visible) {
                        return;
                    }

                    visibleBenefitsCount++;

                    const benefitItem = `
                    <div class="benefit-item mb-3 p-2 border border-gray-200 rounded dark:border-gray-600 ${!restriction.enabled ? 'benefit-disabled' : ''}">
                        <div class="flex items-center mb-2">
                            <input type="checkbox" id="${benefitId}" value="${benefit.id}" 
                                ${!restriction.enabled ? 'disabled' : ''}
                                class="benefit-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                data-benefit-name="${benefit.benefit_name}"
                                data-is-burial="${isBurialBenefit(benefit.benefit_name)}"
                                data-is-non-pension="${isNonPensionersBenefit(benefit.benefit_name)}">
                            <label for="${benefitId}" class="ml-2 text-sm font-medium ${!restriction.enabled ? 'text-gray-400' : 'text-gray-900 dark:text-gray-300'} truncate">
                                ${benefit.benefit_name}
                                ${!restriction.enabled ? '<span class="text-xs text-red-500 ml-1">(Restricted)</span>' : ''}
                            </label>
                        </div>
                        ${!restriction.enabled && restriction.reason ? `
                            <div class="ml-6">
                                <p class="disabled-reason text-xs text-red-600 dark:text-red-400">${restriction.reason}</p>
                            </div>
                        ` : `
                            <div class="benefit-amount-container ml-6 hidden">
                                <label for="${amountId}" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-400 truncate">
                                    Amount for ${benefit.benefit_name}
                                </label>
                                <input type="number" id="${amountId}" 
                                    class="benefit-amount bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="Enter amount" min="0" step="0.01">
                            </div>
                        `}
                    </div>
                `;

                    benefitsCheckboxContainer.insertAdjacentHTML("beforeend", benefitItem);

                    if (restriction.enabled) {
                        const checkbox = document.getElementById(benefitId);
                        const amountContainer = checkbox.closest('.benefit-item').querySelector('.benefit-amount-container');

                        checkbox.addEventListener('change', function() {
                            if (this.checked) {
                                amountContainer.classList.remove('hidden');
                            } else {
                                amountContainer.classList.add('hidden');
                            }
                        });
                    }
                });

                if (visibleBenefitsCount === 0) {
                    benefitsCheckboxContainer.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-gray-500 dark:text-gray-400 mb-2">No benefits available for the selected beneficiaries</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            ${selectionAnalysis.hasDeceased ? 
                              'Deceased seniors can only receive Burial benefits' : 
                              'Please check the selection criteria'}
                        </p>
                    </div>
                `;
                }

                updateBenefitRestrictionsNote(selectionAnalysis);
            }

            // ---------------- POPUP MODAL ----------------
            function showPopup(message, type = "info") {
                const modal = document.getElementById("popupModal");
                const box = document.getElementById("popupBox");
                const title = document.getElementById("popupTitle");
                const msg = document.getElementById("popupMessage");
                const closeBtn = document.getElementById("popupCloseBtn");

                msg.textContent = message;
                title.className = "text-lg font-semibold mb-2 dark:text-white";

                switch (type) {
                    case "success":
                        title.textContent = "✅ Success";
                        closeBtn.style.backgroundColor = "#27AE60";
                        break;
                    case "error":
                        title.textContent = "❌ Error";
                        closeBtn.style.backgroundColor = "#E74C3C";
                        break;
                    default:
                        title.textContent = "ℹ️ Notice";
                        closeBtn.style.backgroundColor = "#3498DB";
                }

                modal.classList.remove("hidden");
                setTimeout(() => {
                    box.classList.remove("scale-95", "opacity-0");
                    box.classList.add("scale-100", "opacity-100");
                }, 10);

                closeBtn.onclick = () => {
                    box.classList.add("scale-95", "opacity-0");
                    setTimeout(() => {
                        modal.classList.add("hidden");
                    }, 200);
                };
            }

            // ---------------- FETCH BENEFICIARIES ----------------
            const fetchBeneficiaries = () => {
                console.log("Fetching beneficiaries...");

                const params = new URLSearchParams({
                    page: currentPage,
                    search: lastSearch,
                    barangays: selectedBarangays.join(','),
                    status: selectedStatus,
                    mode: 'with_benefits'
                });

                if (selectedValidation !== 'all') {
                    params.append('validation_status', selectedValidation);
                }

                if (selectedBenefitTypes.length > 0) {
                    params.append('benefit_types', selectedBenefitTypes.map(b => b.id).join(','));
                }

                fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/beneficiaries/fetch_seniors.php?${params}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        tableBody.innerHTML = "";
                        totalRecords = data.total_records || 0;
                        totalPages = data.total_pages || 1;

                        if (!data.seniors || data.seniors.length === 0) {
                            tableBody.innerHTML = `
                            <tr>
                                <td colspan="13" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                    No beneficiary records found.
                                    <br><small>Status: ${selectedStatus}, Validation: ${selectedValidation}</small>
                                </td>
                            </tr>`;
                            paginationNav.innerHTML = "";
                            return;
                        }

                        data.seniors.forEach((senior, index) => {
                            const statusClass = senior.status === "Active" ? "status-active" :
                                senior.status === "Deceased" ? "status-deceased" :
                                "status-inactive";

                            const validationStatusColor = senior.validation === "Validated" ? "status-validated" :
                                senior.validation === "For Validation" ? "status-pending" :
                                "text-red-600";

                            const createdAt = senior.date_created ? new Date(senior.date_created).toLocaleDateString() : "";
                            const modifiedAt = senior.date_modified ? new Date(senior.date_modified).toLocaleDateString() : "";

                            const buttonId = `dropdownBtn-${index}`;
                            const dropdownId = `dropdownMenu-${index}`;

                            beneficiaryDetails.set(senior.applicant_id.toString(), {
                                status: senior.status,
                                validation: senior.validation,
                                name: senior.full_name || 'Unknown'
                            });

                            const row = `
                            <tr class="border-b text-xs font-medium border-gray-200">
                                <td class="px-2 py-3">
                                    <input type="checkbox" class="beneficiaryCheckbox text-blue-600 bg-gray-200 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-300" 
                                        value="${senior.applicant_id}" 
                                        data-name="${senior.full_name || 'Unknown'}"
                                        data-status="${senior.status}"
                                        data-validation="${senior.validation}">
                                </td>
                                <td class="px-2 py-3 text-center">${senior.rownum}</td>
                                <td class="px-2 py-3 truncate max-w-[120px]">${senior.full_name || ""}</td>
                                <td class="px-2 py-3">${senior.birth_date || ""}</td>
                                <td class="px-2 py-3 text-center">${senior.age || ""}</td>
                                <td class="px-2 py-3 text-center sm-">${senior.gender || ""}</td>
                                <td class="px-2 py-3">${senior.civil_status || ""}</td>
                                <td class="px-2 py-3 truncate max-w-[100px]">${senior.barangay || ""}</td>
                                <td class="px-2 py-3">${createdAt}</td>
                                <td class="px-2 py-3">${modifiedAt}</td>
                                <td class="px-2 py-3 text-center">
                                    <span class="status-badge ${statusClass}">${senior.status}</span>
                                </td>
                                <td class="px-2 py-3 text-center ${validationStatusColor}">${senior.validation}</td>
                                <td class="px-2 py-3 relative">
                                    <button id="${buttonId}" 
                                        class="inline-flex cursor-pointer items-center p-1 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 1 0 0 6Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </button>

                                    <div id="${dropdownId}" 
                                        class="hidden absolute right-0 top-8 z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow-lg dark:bg-gray-700 dark:divide-gray-600 action-dropdown">
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button onclick="viewBeneficiary(${senior.applicant_id}, '${(senior.full_name || 'Unknown').replace(/'/g, "\\'")}')" 
                                                   class="block py-2 cursor-pointer px-4 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white w-full text-left">
                                                    👁 View Benefits
                                                </button>
                                            </li>
                                            <li>
                                                <button onclick="addSingleBenefit('${senior.applicant_id}', '${(senior.full_name || 'Unknown').replace(/'/g, "\\'")}', '${senior.status}', '${senior.validation}')"
                                                   class="block cursor-pointer py-2 px-4 w-full text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                                    💰 Add Benefit
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>`;

                            tableBody.insertAdjacentHTML("beforeend", row);

                            const button = document.getElementById(buttonId);
                            const menu = document.getElementById(dropdownId);
                            const checkbox = tableBody.querySelector(`.beneficiaryCheckbox[value="${senior.applicant_id}"]`);

                            checkbox.checked = window.globalSelectedBeneficiaries.has(senior.applicant_id.toString());

                            checkbox.addEventListener("change", () => {
                                const beneficiaryId = checkbox.value;
                                const beneficiaryName = checkbox.dataset.name?.trim() || "Unknown";
                                const beneficiaryStatus = checkbox.dataset.status || "Active";
                                const beneficiaryValidation = checkbox.dataset.validation || "For Validation";

                                if (checkbox.checked) {
                                    window.globalSelectedBeneficiaries.set(beneficiaryId, {
                                        name: beneficiaryName,
                                        status: beneficiaryStatus,
                                        validation: beneficiaryValidation
                                    });
                                } else {
                                    window.globalSelectedBeneficiaries.delete(beneficiaryId);
                                }

                                updateSelectAllCheckbox();
                                updateAddBenefitVisibility();
                            });

                            button.addEventListener("click", (e) => {
                                e.stopPropagation();
                                document.querySelectorAll("[id^='dropdownMenu-']").forEach(m => {
                                    if (m !== menu) m.classList.add("hidden");
                                });
                                menu.classList.toggle("hidden");
                            });
                        });

                        document.addEventListener("click", () => {
                            document.querySelectorAll("[id^='dropdownMenu-']").forEach(m => m.classList.add("hidden"));
                        });

                        renderPagination(data.start, data.end);
                        updateActiveFiltersDisplay();
                    })
                    .catch(err => {
                        console.error("Error fetching beneficiaries:", err);
                        tableBody.innerHTML = `
                        <tr>
                            <td colspan="13" class="text-center py-4 text-red-500">
                                Error loading data
                                <br><small>Check browser console for details</small>
                            </td>
                        </tr>`;

                        showPopup("Failed to load beneficiaries. Please try again.", "error");
                    });
            };

            // ---------------- ENHANCED PAGINATION ----------------
            const renderPagination = (start, end) => {
                if (totalPages <= 1) {
                    paginationNav.innerHTML = "";
                    return;
                }

                let html = `
    <span class="text-sm font-normal text-gray-500 dark:text-gray-400 mb-2 md:mb-0">
        Showing <span class="font-semibold text-gray-900 dark:text-white">${start}</span> –
        <span class="font-semibold text-gray-900 dark:text-white">${end}</span> of
        <span class="font-semibold text-gray-900 dark:text-white">${totalRecords}</span>
    </span>
    <ul class="inline-flex items-stretch -space-x-px">
`;

                // Previous Button with Tooltip
                html += `
    <li>
        <div class="relative group inline-flex items-center justify-center">
            <button ${currentPage === 1 ? "disabled" : ""} data-nav="prev"
                class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 ml-0 text-gray-500 bg-white rounded-l-sm border border-gray-300 
                hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white
                ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 
                        01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 
                        011.414 0z" clip-rule="evenodd"/>
                    </svg>
            </button>
            <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 
                text-xs text-black text-center font-medium w-[95px] dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                Previous page
            </span>
        </div>
    </li>
`;

                // Always show page 1
                if (totalPages > 0) {
                    html += `
    <li>
        <button data-page="1"
            class="flex items-center justify-center text-sm py-2 px-3 leading-tight 
            ${1 === currentPage ?
                'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' :
                'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
            1
        </button>
    </li>
`;
                }

                // Determine which pages to show
                let startPage = Math.max(2, currentPage - 1);
                let endPage = Math.min(totalPages - 1, currentPage + 1);

                // Adjust if we're near the beginning
                if (currentPage <= 3) {
                    startPage = 2;
                    endPage = Math.min(4, totalPages - 1);
                }

                // Adjust if we're near the end
                if (currentPage >= totalPages - 2) {
                    startPage = Math.max(2, totalPages - 3);
                    endPage = totalPages - 1;
                }

                // Show ellipsis after page 1 if needed
                if (startPage > 2) {
                    html += `
        <li>
            <span class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                ...
            </span>
        </li>
    `;
                }

                // Show middle pages (pages 2-4)
                for (let i = startPage; i <= endPage; i++) {
                    html += `
        <li>
            <button data-page="${i}"
                class="flex items-center justify-center text-sm py-2 px-3 leading-tight 
                ${i === currentPage ?
                    'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' :
                    'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
                ${i}
            </button>
        </li>
    `;
                }

                // Show ellipsis before last page if needed
                if (endPage < totalPages - 1) {
                    html += `
        <li>
            <span class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                ...
            </span>
        </li>
    `;
                }

                // Show last page if there is one (and it's not page 1)
                if (totalPages > 1) {
                    html += `
        <li>
            <button data-page="${totalPages}"
                class="flex items-center justify-center text-sm py-2 px-3 leading-tight 
                ${totalPages === currentPage ?
                    'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' :
                    'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
                ${totalPages}
            </button>
        </li>
    `;
                }

                // Next Button with Tooltip
                html += `
    <li>
        <div class="relative group inline-flex items-center justify-center">
            <button ${currentPage === totalPages ? "disabled" : ""} data-nav="next"
                class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 text-gray-500 bg-white rounded-r-sm border border-gray-300 
                hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white
                ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 
                    011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 
                    01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
            <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 
                text-xs text-black text-center font-medium w-[74px] dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                Next page
            </span>
        </div>
    </li>
</ul>`;

                paginationNav.innerHTML = html;

                // Event listeners for pagination buttons
                paginationNav.querySelectorAll("[data-page]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        currentPage = parseInt(btn.dataset.page);
                        fetchBeneficiaries();
                    });
                });

                paginationNav.querySelectorAll("[data-nav]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        if (btn.dataset.nav === "prev" && currentPage > 1) {
                            currentPage--;
                            fetchBeneficiaries();
                        } else if (btn.dataset.nav === "next" && currentPage < totalPages) {
                            currentPage++;
                            fetchBeneficiaries();
                        }
                    });
                });
            };

            // ---------------- UPDATE BUTTON VISIBILITY ----------------
            function updateAddBenefitVisibility() {
                if (window.globalSelectedBeneficiaries.size > 0) {
                    addBenefitBtnContainer.classList.remove('hidden');
                } else {
                    addBenefitBtnContainer.classList.add('hidden');
                }
            }

            // ---------------- UPDATE ACTIVE FILTERS DISPLAY ----------------
            function updateActiveFiltersDisplay() {
                const filters = [];

                if (selectedStatus !== 'all') {
                    filters.push({
                        type: 'status',
                        label: `Status: ${selectedStatus}`,
                        value: selectedStatus
                    });
                }

                if (selectedValidation !== 'all') {
                    filters.push({
                        type: 'validation',
                        label: `Validation: ${selectedValidation}`,
                        value: selectedValidation
                    });
                }

                if (selectedBarangays.length > 0) {
                    const badgeText = selectedBarangays.length === 1 ?
                        `Barangay: ${selectedBarangays[0]}` :
                        `Barangay: ${selectedBarangays.length} selected`;
                    filters.push({
                        type: 'barangay',
                        label: badgeText,
                        value: selectedBarangays.join(',')
                    });
                }

                if (selectedBenefitTypes.length > 0) {
                    const benefitNames = selectedBenefitTypes.map(b => b.name);
                    const badgeText = selectedBenefitTypes.length === 1 ?
                        `Benefit: ${benefitNames[0]}` :
                        `Benefits: ${selectedBenefitTypes.length} selected`;
                    filters.push({
                        type: 'benefit',
                        label: badgeText,
                        value: selectedBenefitTypes.map(b => b.id).join(',')
                    });
                }

                if (filters.length > 0) {
                    activeFiltersContainer.classList.remove('hidden');
                    activeFilters.innerHTML = '';

                    filters.forEach(filter => {
                        const badge = document.createElement('div');
                        badge.className = 'filter-badge';
                        badge.innerHTML = `
                        <span>${filter.label}</span>
                        <button type="button" class="remove-filter ml-1 cursor-pointer" data-type="${filter.type}" data-value="${filter.value}">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    `;
                        activeFilters.appendChild(badge);
                    });
                } else {
                    activeFiltersContainer.classList.add('hidden');
                }

                document.querySelectorAll('.remove-filter').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const type = this.getAttribute('data-type');
                        if (type === 'status') {
                            selectedStatus = 'all';
                            statusList.querySelectorAll('input[type="radio"]').forEach(radio => {
                                if (radio.value === 'all') radio.checked = true;
                            });
                        } else if (type === 'validation') {
                            selectedValidation = 'all';
                            validationList.querySelectorAll('input[type="radio"]').forEach(radio => {
                                if (radio.value === 'all') radio.checked = true;
                            });
                        } else if (type === 'barangay') {
                            selectedBarangays = [];
                            barangayList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                                cb.checked = false;
                            });
                        } else if (type === 'benefit') {
                            selectedBenefitTypes = [];
                            benefitTypeList.querySelectorAll('.benefit-filter-checkbox').forEach(cb => {
                                cb.checked = false;
                            });
                        }
                        currentPage = 1;
                        fetchBeneficiaries();
                        updateActiveFiltersDisplay();
                    });
                });
            }

            // ---------------- FETCH BARANGAYS ----------------
            function fetchBarangays() {
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/beneficiaries/fetch_seniors.php?mode=barangays")
                    .then(res => res.json())
                    .then(barangays => {
                        barangayList.innerHTML = "";
                        barangays.forEach((b, i) => {
                            const id = `barangay-${i}`;
                            barangayList.insertAdjacentHTML("beforeend", `
                            <li class="flex items-center">
                                <input id="${id}" type="checkbox" value="${b}"
                                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                                           focus:ring-primary-500 dark:focus:ring-primary-600 
                                           dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="${id}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    ${b}
                                </label>
                            </li>
                        `);
                        });

                        barangayList.querySelectorAll("input[type='checkbox']").forEach(cb => {
                            cb.addEventListener("change", () => {
                                selectedBarangays = Array.from(barangayList.querySelectorAll("input[type='checkbox']:checked"))
                                    .map(cb => cb.value);
                                currentPage = 1;
                                fetchBeneficiaries();
                                updateActiveFiltersDisplay();
                            });
                        });
                    })
                    .catch(err => {
                        console.error("Error loading barangays:", err);
                        barangayList.innerHTML = `<li class='text-red-500 text-center'>Error loading barangays</li>`;
                    });
            }

            // ---------------- SETUP STATUS FILTER ----------------
            function setupStatusFilter() {
                statusList.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        selectedStatus = this.value;
                        currentPage = 1;
                        fetchBeneficiaries();
                        updateActiveFiltersDisplay();
                    });
                });
            }

            // ---------------- SETUP VALIDATION FILTER ----------------
            function setupValidationFilter() {
                validationList.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        selectedValidation = this.value;
                        currentPage = 1;
                        fetchBeneficiaries();
                        updateActiveFiltersDisplay();
                    });
                });
            }

            // ---------------- FETCH BENEFIT TYPES ----------------
            function fetchBenefitTypes() {
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits.php")
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.benefits && data.benefits.length > 0) {
                            benefitTypeList.innerHTML = '';
                            data.benefits.forEach((benefit, index) => {
                                const benefitId = `benefit-filter-${index}`;
                                const isChecked = selectedBenefitTypes.some(b => b.id === benefit.id);

                                benefitTypeList.insertAdjacentHTML('beforeend', `
                                <li class="flex items-center">
                                    <input id="${benefitId}" type="checkbox" value="${benefit.id}" 
                                        ${isChecked ? 'checked' : ''}
                                        class="benefit-filter-checkbox w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                                               focus:ring-primary-500 dark:focus:ring-primary-600 
                                               dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                        data-name="${benefit.benefit_name}">
                                    <label for="${benefitId}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        ${benefit.benefit_name}
                                    </label>
                                </li>
                            `);
                            });

                            benefitTypeList.querySelectorAll('.benefit-filter-checkbox').forEach(cb => {
                                cb.addEventListener('change', function() {
                                    const benefitId = this.value;
                                    const benefitName = this.getAttribute('data-name');

                                    if (this.checked) {
                                        if (!selectedBenefitTypes.some(b => b.id === benefitId)) {
                                            selectedBenefitTypes.push({
                                                id: benefitId,
                                                name: benefitName
                                            });
                                        }
                                    } else {
                                        selectedBenefitTypes = selectedBenefitTypes.filter(b => b.id !== benefitId);
                                    }

                                    currentPage = 1;
                                    fetchBeneficiaries();
                                    updateActiveFiltersDisplay();
                                });
                            });
                        } else {
                            benefitTypeList.innerHTML = '<li class="text-gray-500 text-sm text-center">No benefits available</li>';
                        }
                    })
                    .catch(err => {
                        console.error('Error loading benefit types:', err);
                        benefitTypeList.innerHTML = '<li class="text-red-500 text-sm text-center">Error loading benefits</li>';
                    });
            }

            // ---------------- FETCH BENEFITS ----------------
            function fetchBenefits() {
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits.php")
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            availableBenefits = data.benefits || [];
                        } else {
                            console.error("Error loading benefits:", data.message);
                            showPopup(data.message || "Failed to load benefits.", "error");
                            availableBenefits = [];
                        }
                    })
                    .catch(err => {
                        console.error("Error loading benefits:", err);
                        showPopup("Failed to load benefits.", "error");
                        availableBenefits = [];
                    });
            }

            // ---------------- FETCH BENEFITS HISTORY ----------------
            async function fetchBenefitsHistory(applicantId, fullName) {
                try {
                    const response = await fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits_history.php?applicant_id=${applicantId}`);
                    const data = await response.json();

                    viewBeneficiaryName.textContent = fullName;
                    benefitsHistoryBody.innerHTML = '';
                    let totalAmount = 0;

                    if (data.success && data.benefits && data.benefits.length > 0) {
                        data.benefits.forEach(benefit => {
                            const row = `
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-2 py-3 truncate max-w-[150px]">${benefit.benefit_name}</td>
                                <td class="px-2 py-3">₱${parseFloat(benefit.amount).toFixed(2)}</td>
                                <td class="px-2 py-3">${new Date(benefit.date_received).toLocaleDateString()}</td>
                                <td class="px-2 py-3">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                        Received
                                    </span>
                                </td>
                            </tr>
                        `;
                            benefitsHistoryBody.insertAdjacentHTML('beforeend', row);
                            totalAmount += parseFloat(benefit.amount);
                        });
                    } else {
                        benefitsHistoryBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                No benefits received yet.
                            </td>
                        </tr>
                    `;
                    }

                    totalBenefitsAmount.textContent = totalAmount.toFixed(2);
                    viewBenefitsModal.classList.remove('hidden');

                } catch (error) {
                    console.error('Error fetching benefits history:', error);
                    showPopup('Failed to load benefits history.', 'error');
                }
            }

            // ---------------- SELECT ALL FUNCTIONALITY ----------------
            function updateSelectAllCheckbox() {
                const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");
                const checkedCheckboxes = tableBody.querySelectorAll(".beneficiaryCheckbox:checked");

                if (checkboxes.length === 0) {
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                        selectAllCheckbox.disabled = true;
                    }
                    return;
                }

                if (selectAllCheckbox) {
                    selectAllCheckbox.disabled = false;

                    if (checkedCheckboxes.length === checkboxes.length) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length > 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    }
                }
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener("change", function() {
                    const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");

                    checkboxes.forEach(checkbox => {
                        const isChecked = this.checked;
                        checkbox.checked = isChecked;

                        const beneficiaryId = checkbox.value;
                        const beneficiaryName = checkbox.dataset.name?.trim() || "Unknown";
                        const beneficiaryStatus = checkbox.dataset.status || "Active";
                        const beneficiaryValidation = checkbox.dataset.validation || "For Validation";

                        if (isChecked) {
                            window.globalSelectedBeneficiaries.set(beneficiaryId, {
                                name: beneficiaryName,
                                status: beneficiaryStatus,
                                validation: beneficiaryValidation
                            });
                        } else {
                            window.globalSelectedBeneficiaries.delete(beneficiaryId);
                        }
                    });

                    this.indeterminate = false;
                    updateAddBenefitVisibility();
                });
            }

            // ---------------- ADD BENEFIT MODAL ----------------
            if (addBenefitBtn) {
                addBenefitBtn.addEventListener("click", () => {
                    if (window.globalSelectedBeneficiaries.size === 0) {
                        showPopup("Please select at least one beneficiary.", "error");
                        return;
                    }

                    selectedBeneficiariesList.innerHTML = "";
                    window.globalSelectedBeneficiaries.forEach((details, id) => {
                        const name = details.name || "Unknown";
                        const status = details.status || "Active";
                        const validation = details.validation || "For Validation";

                        selectedBeneficiariesList.insertAdjacentHTML("beforeend", `
                        <div class="text-sm text-gray-700 dark:text-gray-300 py-1 truncate">
                            • ${name} 
                            <span class="text-xs text-gray-500">(${status} | ${validation})</span>
                        </div>
                    `);
                    });

                    populateBenefitsCheckboxesWithRestrictions(window.globalSelectedBeneficiaries);
                    updateBenefitRestrictionsNote(window.globalSelectedBeneficiaries);

                    benefitForm.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('benefitDate').value = today;

                    addBenefitModal.classList.remove('hidden');
                });
            }

            if (closeBenefitModal) {
                closeBenefitModal.addEventListener("click", () => {
                    addBenefitModal.classList.add('hidden');
                });
            }

            // ---------------- VIEW BENEFITS MODAL ----------------
            if (closeViewBenefitsModal) {
                closeViewBenefitsModal.addEventListener("click", () => {
                    viewBenefitsModal.classList.add('hidden');
                });
            }

            if (closeViewBenefitsModalBottom) {
                closeViewBenefitsModalBottom.addEventListener("click", () => {
                    viewBenefitsModal.classList.add('hidden');
                });
            }

            // ---------------- CLEAR BENEFITS FILTER ----------------
            if (clearBenefitsFilterBtn) {
                clearBenefitsFilterBtn.addEventListener('click', () => {
                    selectedBenefitTypes = [];
                    benefitTypeList.querySelectorAll('.benefit-filter-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    currentPage = 1;
                    fetchBeneficiaries();
                    updateActiveFiltersDisplay();
                });
            }

            // ---------------- CLEAR ALL FILTERS ----------------
            if (clearAllFiltersBtn) {
                clearAllFiltersBtn.addEventListener('click', () => {
                    selectedStatus = 'all';
                    statusList.querySelectorAll('input[type="radio"]').forEach(radio => {
                        if (radio.value === 'all') radio.checked = true;
                    });

                    selectedValidation = 'all';
                    validationList.querySelectorAll('input[type="radio"]').forEach(radio => {
                        if (radio.value === 'all') radio.checked = true;
                    });

                    selectedBarangays = [];
                    barangayList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                    });

                    selectedBenefitTypes = [];
                    benefitTypeList.querySelectorAll('.benefit-filter-checkbox').forEach(cb => {
                        cb.checked = false;
                    });

                    lastSearch = '';
                    searchInput.value = '';

                    currentPage = 1;
                    fetchBeneficiaries();
                    updateActiveFiltersDisplay();
                });
            }

            // Handle form submission
            if (benefitForm) {
                benefitForm.addEventListener("submit", async (e) => {
                    e.preventDefault();

                    let hasActiveInSelection = false;
                    let hasDeceasedInSelection = false;
                    let deceasedBeneficiaryIds = [];

                    for (const [id, details] of window.globalSelectedBeneficiaries) {
                        if (details.status === "Active") {
                            hasActiveInSelection = true;
                        }
                        if (details.status === "Deceased") {
                            hasDeceasedInSelection = true;
                            deceasedBeneficiaryIds.push(id);
                        }
                    }

                    const selectedBenefits = [];
                    const selectedBurialBenefits = [];

                    benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox:checked').forEach(checkbox => {
                        const benefitId = checkbox.value;
                        const benefitName = checkbox.closest('.benefit-item').querySelector('label').textContent.replace('(Restricted)', '').trim();
                        const isBurial = checkbox.getAttribute('data-is-burial') === 'true';
                        const amountInput = checkbox.closest('.benefit-item').querySelector('.benefit-amount');
                        const amount = amountInput ? amountInput.value.trim() : '0';

                        if (!amount || amount === '0') {
                            showPopup(`Please enter amount for ${benefitName}`, "error");
                            if (amountInput) amountInput.focus();
                            throw new Error(`Amount required for ${benefitName}`);
                        }

                        selectedBenefits.push({
                            id: benefitId,
                            name: benefitName,
                            amount: parseFloat(amount),
                            isBurial: isBurial
                        });

                        if (isBurial) {
                            selectedBurialBenefits.push(benefitName);
                        }
                    });

                    if (selectedBenefits.length === 0) {
                        showPopup("Please select at least one benefit and enter amounts.", "error");
                        return;
                    }

                    if (hasActiveInSelection && selectedBurialBenefits.length > 0) {
                        showPopup("Active seniors cannot receive Burial benefits. Please deselect Burial benefits or remove Active seniors from selection.", "error");
                        return;
                    }

                    if (hasDeceasedInSelection && selectedBurialBenefits.length > 0) {
                        try {
                            const checkResponse = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/benefits/check_existing_benefits.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    applicant_ids: deceasedBeneficiaryIds,
                                    benefit_types: selectedBurialBenefits
                                })
                            });

                            const checkResult = await checkResponse.json();

                            if (checkResult.success && checkResult.has_existing) {
                                const modal = document.createElement('div');
                                modal.className = 'fixed inset-0 bg-gray-600/50 bg-opacity-50 flex items-center justify-center z-50';
                                modal.innerHTML = `
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-11/12 sm:w-96 p-6 mx-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                        Existing Burial Benefits Found
                                    </h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                                        Some deceased beneficiaries already have burial benefits. Deceased seniors should only receive burial benefits once.
                                    </p>
                                    <div class="mb-4 max-h-40 overflow-y-auto">
                                        ${checkResult.existing_benefits.map(benefit => `
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                                <strong>${benefit.applicant_name}</strong> already received:<br>
                                                ${benefit.benefit_name} on ${benefit.distribution_date}
                                            </div>
                                        `).join('')}
                                    </div>
                                    <div class="flex justify-end space-x-3">
                                        <button id="cancelAddBenefits" 
                                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                            Cancel
                                        </button>
                                        <button id="proceedAddBenefits" 
                                            class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600">
                                            Proceed Anyway
                                        </button>
                                    </div>
                                </div>
                            `;

                                document.body.appendChild(modal);

                                document.getElementById('cancelAddBenefits').addEventListener('click', () => {
                                    document.body.removeChild(modal);
                                });

                                document.getElementById('proceedAddBenefits').addEventListener('click', async () => {
                                    document.body.removeChild(modal);
                                    await submitBenefitsForm();
                                });

                                return;
                            }
                        } catch (error) {
                            console.error('Error checking existing benefits:', error);
                        }
                    }

                    await submitBenefitsForm();

                    async function submitBenefitsForm() {
                        const formData = new FormData(benefitForm);
                        const adminUserId = <?php echo json_encode($user_id ?? 0); ?>;

                        try {
                            const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/benefits/add_benefits.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    applicant_ids: Array.from(window.globalSelectedBeneficiaries.keys()),
                                    benefits: selectedBenefits,
                                    date: formData.get('benefitDate'),
                                    session_context: "admin",
                                    admin_user_id: adminUserId,
                                    check_existing: true
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                showPopup(result.message, "success");
                                addBenefitModal.classList.add('hidden');

                                window.globalSelectedBeneficiaries.clear();
                                updateAddBenefitVisibility();
                                updateSelectAllCheckbox();

                                fetchBeneficiaries();
                            } else {
                                showPopup(result.message || "Failed to add benefits.", "error");
                            }
                        } catch (error) {
                            console.error('Error adding benefits:', error);
                            showPopup("An error occurred while adding benefits.", "error");
                        }
                    }
                });
            }

            // ---------------- SEARCH ----------------
            let searchTimeout;
            searchInput.addEventListener("input", (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    lastSearch = e.target.value.trim();
                    currentPage = 1;
                    fetchBeneficiaries();
                }, 400);
            });

            // ---------------- RESPONSIVE DROPDOWN POSITIONING ----------------
            function setupResponsiveDropdowns() {
                const dropdowns = [{
                        button: statusFilterButton,
                        dropdown: statusFilterDropdown
                    },
                    {
                        button: validationFilterButton,
                        dropdown: validationFilterDropdown
                    },
                    {
                        button: benefitsFilterButton,
                        dropdown: benefitsFilterDropdown
                    },
                    {
                        button: filterDropdownButton,
                        dropdown: filterDropdown
                    }
                ];

                dropdowns.forEach(({
                    button,
                    dropdown
                }) => {
                    if (button && dropdown) {
                        button.addEventListener('click', (e) => {
                            e.stopPropagation();

                            dropdowns.forEach(({
                                dropdown: d
                            }) => {
                                if (d !== dropdown) d.classList.add('hidden');
                            });

                            const isHidden = dropdown.classList.toggle('hidden');

                            if (!isHidden) {
                                const rect = button.getBoundingClientRect();
                                const isMobile = window.innerWidth < 768;

                                if (isMobile) {
                                    dropdown.style.position = 'fixed';
                                    dropdown.style.left = '50%';
                                    dropdown.style.top = '50%';
                                    dropdown.style.transform = 'translate(-50%, -50%)';
                                    dropdown.style.width = '90vw';
                                    dropdown.style.maxWidth = '400px';
                                } else {
                                    dropdown.style.position = 'absolute';
                                    dropdown.style.left = 'auto';
                                    dropdown.style.right = '0';
                                    dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
                                    dropdown.style.transform = 'none';
                                    dropdown.style.width = '';
                                }
                            }
                        });
                    }
                });

                document.addEventListener('click', () => {
                    dropdowns.forEach(({
                        dropdown
                    }) => {
                        dropdown.classList.add('hidden');
                    });
                });
            }

            // ---------------- ACTION FUNCTIONS ----------------
            window.viewBeneficiary = (applicantId, fullName) => {
                fetchBenefitsHistory(applicantId, fullName);
            };

            window.addSingleBenefit = (applicantId, fullName, status, validation) => {
                window.globalSelectedBeneficiaries.clear();
                window.globalSelectedBeneficiaries.set(applicantId.toString(), {
                    name: fullName,
                    status: status,
                    validation: validation
                });

                const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");
                checkboxes.forEach(checkbox => {
                    checkbox.checked = (checkbox.value === applicantId.toString());
                });

                updateSelectAllCheckbox();
                updateAddBenefitVisibility();

                addBenefitBtn.click();
            };

            // ---------------- INITIALIZE ----------------
            function initializePage() {
                console.log("Initializing beneficiary page...");
                setupResponsiveDropdowns();
                fetchBarangays();
                setupStatusFilter();
                setupValidationFilter();
                fetchBenefitTypes();
                fetchBenefits();
                fetchBeneficiaries();
            }

            // Start everything
            initializePage();
        });
    </script>

</body>

</html>