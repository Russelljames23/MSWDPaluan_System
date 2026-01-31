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
    </style>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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

                                <!-- Print Button -->
                                <div class="relative w-full sm:w-auto">
                                    <button id="printButton"
                                        class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <i class="fas fa-print w-4 h-4 mr-2"></i>
                                        Print
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
                                <div class="flex  gap-2">
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
                                        <th scope="col" class="px-2 py-3 ">
                                            <input id="selectAllCheckbox" type="checkbox"
                                                class="w-4 h-4 text-blue-600 bg-gray-200 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-300">
                                        </th>
                                        <th scope="col" class="px-2 py-3">No.</th>
                                        <th scope="col" class="px-2 py-3">Name</th>
                                        <th scope="col" class="px-2 py-3 ">Birthdate</th>
                                        <th scope="col" class="px-2 py-3">Age</th>
                                        <th scope="col" class="px-2 py-3 sm-">Gender</th>
                                        <th scope="col" class="px-2 py-3 ">Civil Status</th>
                                        <th scope="col" class="px-2 py-3">Barangay</th>
                                        <th scope="col" class="px-2 py-3 ">Date Registered</th>
                                        <th scope="col" class="px-2 py-3 ">Date Modified</th>
                                        <th scope="col" class="px-2 py-3">Status</th>
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
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0 p-3 sm:p-4"
                            aria-label="Table navigation">
                        </nav>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Benefit Modal -->
    <div id="addBenefitModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full bg-gray-900/50">
        <div class="relative p-2 sm:p-4 w-full max-w-2xl h-full md:h-auto">
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
                            </label>
                            <div id="benefitsCheckboxContainer" class="max-h-40 overflow-y-auto border rounded p-3 dark:border-gray-600">
                                <!-- Benefits checkboxes with amount inputs will be populated here -->
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

    <!-- View Benefits Modal -->
    <div id="viewBenefitsModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full bg-gray-900/50">
        <div class="relative p-2 sm:p-4 w-full max-w-4xl h-full md:h-auto">
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

    <!-- Print Modal -->
    <div id="printModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full bg-gray-900/50">
        <div class="relative p-2 sm:p-4 w-full max-w-2xl h-full md:h-auto">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-800 p-4 sm:p-5">
                <div class="flex justify-between items-center pb-3 mb-3 sm:pb-4 sm:mb-4 border-b dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Print Beneficiaries
                    </h3>
                    <button type="button" id="closePrintModal"
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
                <form id="printForm">
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Print Options
                            </label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input id="printAll" type="radio" name="printOption" value="all" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="printAll" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Print All Filtered Beneficiaries (<span id="totalRecordsCount">0</span> records)
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="printSelected" type="radio" name="printOption" value="selected"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="printSelected" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Print Selected Only (<span id="selectedCount">0</span> selected)
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="printCurrentPage" type="radio" name="printOption" value="current_page"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="printCurrentPage" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Print Current Page Only
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Include Columns
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="flex items-center">
                                    <input id="colName" type="checkbox" name="columns[]" value="name" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colName" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Name
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colBirthdate" type="checkbox" name="columns[]" value="birthdate" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colBirthdate" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Birthdate
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colAge" type="checkbox" name="columns[]" value="age" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colAge" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Age
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colGender" type="checkbox" name="columns[]" value="gender" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colGender" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Gender
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colCivilStatus" type="checkbox" name="columns[]" value="civil_status" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colCivilStatus" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Civil Status
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colBarangay" type="checkbox" name="columns[]" value="barangay" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colBarangay" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Barangay
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colDateRegistered" type="checkbox" name="columns[]" value="date_registered"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colDateRegistered" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Date Registered
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="colStatus" type="checkbox" name="columns[]" value="status" checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="colStatus" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                        Status
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="printTitle" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Report Title
                            </label>
                            <input type="text" id="printTitle" name="printTitle"
                                value="MSWD Paluan - Beneficiaries Report"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input id="showFilters" type="checkbox" name="showFilters" checked
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="showFilters" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Show Applied Filters in Report
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="includeBenefitInfo" type="checkbox" name="includeBenefitInfo" checked
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="includeBenefitInfo" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    Include Benefit Filter Information
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                        <button type="button" id="cancelPrint"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 w-full sm:w-auto">
                            <i class="fas fa-print mr-2"></i>Generate Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Popup Modal -->
    <div id="popupModal"
        class="fixed inset-0 bg-gray-600/50 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div id="popupBox"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg transform scale-95 opacity-0 transition-all duration-200 w-11/12 sm:w-80 p-4 mx-4">
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
    <script src="../js/tailwind.config.js"></script>
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

            // DOM elements
            const tableBody = document.getElementById("beneficiaryBody");
            const barangayList = document.getElementById("barangayList");
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

            // View Benefits Modal elements
            const viewBenefitsModal = document.getElementById("viewBenefitsModal");
            const closeViewBenefitsModal = document.getElementById("closeViewBenefitsModal");
            const closeViewBenefitsModalBottom = document.getElementById("closeViewBenefitsModalBottom");
            const viewBeneficiaryName = document.getElementById("viewBeneficiaryName");
            const benefitsHistoryBody = document.getElementById("benefitsHistoryBody");
            const totalBenefitsAmount = document.getElementById("totalBenefitsAmount");

            // Benefits Filter elements
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

            // ---------------- RESPONSIVE DROPDOWN POSITIONING ----------------
            function setupResponsiveDropdowns() {
                const dropdowns = [{
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

                            // Close other dropdowns
                            dropdowns.forEach(({
                                dropdown: d
                            }) => {
                                if (d !== dropdown) d.classList.add('hidden');
                            });

                            // Toggle current dropdown
                            const isHidden = dropdown.classList.toggle('hidden');

                            if (!isHidden) {
                                // Position dropdown
                                const rect = button.getBoundingClientRect();
                                const isMobile = window.innerWidth < 768;

                                if (isMobile) {
                                    // Center on mobile
                                    dropdown.style.position = 'fixed';
                                    dropdown.style.left = '50%';
                                    dropdown.style.top = '50%';
                                    dropdown.style.transform = 'translate(-50%, -50%)';
                                    dropdown.style.width = '90vw';
                                    dropdown.style.maxWidth = '400px';
                                } else {
                                    // Position below button on desktop
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

                // Close dropdowns when clicking outside
                document.addEventListener('click', () => {
                    dropdowns.forEach(({
                        dropdown
                    }) => {
                        dropdown.classList.add('hidden');
                    });
                });
            }

            // ---------------- POPUP MODAL ----------------
            function showPopup(message, type = "info") {
                const modal = document.getElementById("popupModal");
                const box = document.getElementById("popupBox");
                const title = document.getElementById("popupTitle");
                const msg = document.getElementById("popupMessage");
                const closeBtn = document.getElementById("popupCloseBtn");

                msg.textContent = message;
                title.className = "text-lg font-semibold mb-2";

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

            // ---------------- PRINT FUNCTIONALITY ----------------
            function openPrintModal() {
                document.getElementById('totalRecordsCount').textContent = totalRecords;
                document.getElementById('selectedCount').textContent = window.globalSelectedBeneficiaries.size;

                const printSelectedRadio = document.getElementById('printSelected');
                if (window.globalSelectedBeneficiaries.size === 0) {
                    printSelectedRadio.disabled = true;
                    if (printSelectedRadio.checked) {
                        document.getElementById('printAll').checked = true;
                    }
                } else {
                    printSelectedRadio.disabled = false;
                }

                printModal.classList.remove('hidden');
            }

            async function handlePrintForm(e) {
                e.preventDefault();

                const formData = new FormData(printForm);
                const printData = {
                    option: formData.get('printOption'),
                    columns: formData.getAll('columns[]'),
                    title: formData.get('printTitle') || 'MSWD Paluan - Beneficiaries Report',
                    showFilters: formData.get('showFilters') === 'on',
                    includeBenefitInfo: formData.get('includeBenefitInfo') === 'on'
                };

                try {
                    showPopup("Generating print report...", "info");

                    let beneficiaries = [];
                    let url = '';

                    if (printData.option === 'selected') {
                        const selectedIds = Array.from(window.globalSelectedBeneficiaries.keys());
                        if (selectedIds.length === 0) {
                            showPopup("No beneficiaries selected.", "error");
                            return;
                        }

                        url = `/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?mode=for_print&ids=${selectedIds.join(',')}`;
                    } else if (printData.option === 'current_page') {
                        const params = new URLSearchParams({
                            page: currentPage,
                            search: lastSearch,
                            barangays: selectedBarangays.join(','),
                            mode: 'with_benefits',
                            limit: 100
                        });

                        if (selectedBenefitTypes.length > 0) {
                            params.append('benefit_types', selectedBenefitTypes.map(b => b.id).join(','));
                        }

                        url = `/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`;
                    } else {
                        const params = new URLSearchParams({
                            page: 1,
                            search: lastSearch,
                            barangays: selectedBarangays.join(','),
                            mode: 'with_benefits',
                            limit: 10000
                        });

                        if (selectedBenefitTypes.length > 0) {
                            params.append('benefit_types', selectedBenefitTypes.map(b => b.id).join(','));
                        }

                        url = `/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`;
                    }

                    const response = await fetch(url);
                    const data = await response.json();
                    beneficiaries = data.seniors || [];

                    if (beneficiaries.length === 0) {
                        showPopup("No beneficiaries to print.", "error");
                        return;
                    }

                    generatePrintDocument(beneficiaries, printData);
                    printModal.classList.add('hidden');

                } catch (error) {
                    console.error('Error generating print:', error);
                    showPopup("Failed to generate print report. Please try again.", "error");
                }
            }

            function generatePrintDocument(beneficiaries, printData) {
                const printWindow = window.open('', '_blank', 'width=900,height=600');

                if (!printWindow) {
                    showPopup("Please allow pop-ups to generate the print report.", "error");
                    return;
                }

                const currentDate = new Date().toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                let tableHeaders = '<tr>';
                let headerCount = 0;

                tableHeaders += '<th style="width: 30px;">#</th>';
                headerCount++;

                if (printData.columns.includes('name')) {
                    tableHeaders += '<th>Name</th>';
                    headerCount++;
                }
                if (printData.columns.includes('birthdate')) {
                    tableHeaders += '<th>Birthdate</th>';
                    headerCount++;
                }
                if (printData.columns.includes('age')) {
                    tableHeaders += '<th>Age</th>';
                    headerCount++;
                }
                if (printData.columns.includes('gender')) {
                    tableHeaders += '<th>Gender</th>';
                    headerCount++;
                }
                if (printData.columns.includes('civil_status')) {
                    tableHeaders += '<th>Civil Status</th>';
                    headerCount++;
                }
                if (printData.columns.includes('barangay')) {
                    tableHeaders += '<th>Barangay</th>';
                    headerCount++;
                }
                if (printData.columns.includes('date_registered')) {
                    tableHeaders += '<th>Date Registered</th>';
                    headerCount++;
                }
                if (printData.columns.includes('status')) {
                    tableHeaders += '<th>Status</th>';
                    headerCount++;
                }
                tableHeaders += '</tr>';

                let tableRows = '';
                beneficiaries.forEach((senior, index) => {
                    tableRows += '<tr>';
                    tableRows += `<td style="text-align: center;">${index + 1}</td>`;

                    if (printData.columns.includes('name')) {
                        tableRows += `<td>${senior.full_name || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('birthdate')) {
                        tableRows += `<td>${senior.birth_date || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('age')) {
                        tableRows += `<td style="text-align: center;">${senior.age || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('gender')) {
                        tableRows += `<td style="text-align: center;">${senior.gender || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('civil_status')) {
                        tableRows += `<td>${senior.civil_status || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('barangay')) {
                        tableRows += `<td>${senior.barangay || 'N/A'}</td>`;
                    }
                    if (printData.columns.includes('date_registered')) {
                        const regDate = senior.date_created ? new Date(senior.date_created).toLocaleDateString() : 'N/A';
                        tableRows += `<td>${regDate}</td>`;
                    }
                    if (printData.columns.includes('status')) {
                        const status = senior.validation || 'N/A';
                        const statusClass = status === 'Validated' ? 'status-validated' : 'status-pending';
                        tableRows += `<td class="${statusClass}">${status}</td>`;
                    }
                    tableRows += '</tr>';
                });

                let filterInfo = '';
                if (printData.showFilters) {
                    const filters = [];

                    if (lastSearch) {
                        filters.push(`Search: "${lastSearch}"`);
                    }
                    if (selectedBarangays.length > 0) {
                        filters.push(`Barangays: ${selectedBarangays.join(', ')}`);
                    }

                    if (printData.includeBenefitInfo && selectedBenefitTypes.length > 0) {
                        const benefitNames = selectedBenefitTypes.map(b => b.name);
                        filters.push(`<strong>Filtered by Benefits:</strong> ${benefitNames.join(', ')}`);

                        if (printData.title === 'MSWD Paluan - Beneficiaries Report') {
                            printData.title = `MSWD Paluan - Beneficiaries with ${benefitNames.length > 1 ? 'Selected Benefits' : benefitNames[0]} Report`;
                        }
                    }

                    if (filters.length > 0) {
                        filterInfo = `
                            <div class="filter-info" style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff; font-size: 11px;">
                                <strong>Applied Filters:</strong><br>
                                ${filters.join('<br>')}<br>
                                <strong>Total Filtered Records:</strong> ${beneficiaries.length}
                            </div>
                        `;
                    }
                }

                const generatedBy = '<?php echo htmlspecialchars($_SESSION["fullname"] ?? ($_SESSION["firstname"] ?? "") . " " . ($_SESSION["lastname"] ?? "") ?? "System"); ?>';

                const printHTML = `
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>${printData.title}</title>
                        <style>
                            @page {
                                margin: 15mm;
                                size: A4 portrait;
                            }
                            
                            body {
                                font-family: 'Arial', sans-serif;
                                margin: 0;
                                padding: 0;
                                color: #000;
                                font-size: 12px;
                            }
                            
                            .print-container {
                                max-width: 100%;
                                margin: 0 auto;
                            }
                            
                            .print-header {
                                text-align: center;
                                margin-bottom: 20px;
                                padding-bottom: 15px;
                                border-bottom: 2px solid #333;
                            }
                            
                            .logo-container {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 30px;
                                margin-bottom: 10px;
                            }
                            
                            .logo {
                                height: 70px;
                                width: auto;
                            }
                            
                            h1 {
                                margin: 5px 0;
                                font-size: 18px;
                                font-weight: bold;
                            }
                            
                            .subtitle {
                                font-size: 14px;
                                margin-bottom: 5px;
                                font-weight: 500;
                            }
                            
                            .date-info {
                                font-size: 11px;
                                color: #666;
                                margin-bottom: 15px;
                            }
                            
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-top: 10px;
                                font-size: 10px;
                            }
                            
                            th {
                                background-color: #2d3748;
                                color: white;
                                border: 1px solid #4a5568;
                                padding: 6px 8px;
                                text-align: left;
                                font-weight: bold;
                            }
                            
                            td {
                                border: 1px solid #ddd;
                                padding: 6px 8px;
                            }
                            
                            tr:nth-child(even) {
                                background-color: #f9f9f9;
                            }
                            
                            .status-validated {
                                color: #28a745;
                                font-weight: bold;
                            }
                            
                            .status-pending {
                                color: #dc3545;
                                font-weight: bold;
                            }
                            
                            .footer {
                                margin-top: 30px;
                                padding-top: 15px;
                                border-top: 1px solid #ddd;
                                font-size: 10px;
                                color: #666;
                                text-align: center;
                            }
                            
                            .no-print {
                                display: none;
                            }
                            
                            .controls {
                                margin: 20px 0;
                                padding: 15px;
                                background-color: #f8f9fa;
                                border: 1px solid #dee2e6;
                                border-radius: 5px;
                                text-align: center;
                            }
                            
                            .print-btn {
                                background-color: #007bff;
                                color: white;
                                border: none;
                                padding: 10px 20px;
                                border-radius: 4px;
                                cursor: pointer;
                                margin: 5px;
                            }
                            
                            .print-btn:hover {
                                background-color: #0056b3;
                            }
                            
                            .close-btn {
                                background-color: #6c757d;
                                color: white;
                                border: none;
                                padding: 10px 20px;
                                border-radius: 4px;
                                cursor: pointer;
                                margin: 5px;
                            }
                            
                            .close-btn:hover {
                                background-color: #545b62;
                            }
                            
                            @media print {
                                .no-print {
                                    display: none !important;
                                }
                                
                                .controls {
                                    display: none;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="print-container">
                            <div class="print-header">
                                <div class="logo-container">
                                    <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo" class="logo">
                                    <img src="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png" alt="Paluan Logo" class="logo">
                                </div>
                                <h1>${printData.title}</h1>
                                <div class="subtitle">Municipal Social Welfare and Development Office - Paluan</div>
                                <div class="date-info">
                                    Generated on: ${currentDate}<br>
                                    Total Records: ${beneficiaries.length}
                                </div>
                            </div>
                            
                            ${filterInfo}
                            
                            <table>
                                <thead>
                                    ${tableHeaders}
                                </thead>
                                <tbody>
                                    ${tableRows}
                                </tbody>
                            </table>
                            
                            <div class="footer">
                                <p>Generated by: ${generatedBy}</p>
                                <p>MSWD Paluan Beneficiary Management System</p>
                                <p>Page 1 of 1</p>
                            </div>
                            
                            <div class="controls no-print">
                                <p>Click the button below to print or save as PDF</p>
                                <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
                                <button class="close-btn" onclick="window.close()">✕ Close Window</button>
                            </div>
                        </div>
                        
                        <script>
                            window.focus();
                        <\/script>
                    </body>
                    </html>
                `;

                printWindow.document.write(printHTML);
                printWindow.document.close();
                printWindow.focus();
            }

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
                        if (type === 'barangay') {
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
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?mode=barangays")
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
                            populateBenefitsCheckboxes();
                        } else {
                            console.error("Error loading benefits:", data.message);
                            showPopup(data.message || "Failed to load benefits.", "error");
                            availableBenefits = [];
                            populateBenefitsCheckboxes();
                        }
                    })
                    .catch(err => {
                        console.error("Error loading benefits:", err);
                        showPopup("Failed to load benefits.", "error");
                        availableBenefits = [];
                        populateBenefitsCheckboxes();
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

            // ---------------- POPULATE BENEFITS CHECKBOXES ----------------
            function populateBenefitsCheckboxes() {
                benefitsCheckboxContainer.innerHTML = "";

                if (availableBenefits.length === 0) {
                    benefitsCheckboxContainer.innerHTML = '<p class="text-gray-500 text-center py-2">No benefits available</p>';
                    return;
                }

                availableBenefits.forEach((benefit, index) => {
                    const benefitId = `benefit-${index}`;
                    const amountId = `amount-${index}`;

                    benefitsCheckboxContainer.insertAdjacentHTML("beforeend", `
                        <div class="benefit-item mb-3 p-2 border border-gray-200 rounded dark:border-gray-600">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="${benefitId}" value="${benefit.id}" 
                                    class="benefit-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="${benefitId}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 truncate">
                                    ${benefit.benefit_name}
                                </label>
                            </div>
                            <div class="benefit-amount-container ml-6 hidden">
                                <label for="${amountId}" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-400 truncate">
                                    Amount for ${benefit.benefit_name}
                                </label>
                                <input type="number" id="${amountId}" 
                                    class="benefit-amount bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="Enter amount" min="0" step="0.01">
                            </div>
                        </div>
                    `);
                });

                benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const amountContainer = this.closest('.benefit-item').querySelector('.benefit-amount-container');
                        if (this.checked) {
                            amountContainer.classList.remove('hidden');
                        } else {
                            amountContainer.classList.add('hidden');
                        }
                    });
                });
            }

            // ---------------- FETCH BENEFICIARIES ----------------
            const fetchBeneficiaries = () => {
                const params = new URLSearchParams({
                    page: currentPage,
                    search: lastSearch,
                    barangays: selectedBarangays.join(','),
                    mode: 'with_benefits'
                });

                if (selectedBenefitTypes.length > 0) {
                    params.append('benefit_types', selectedBenefitTypes.map(b => b.id).join(','));
                }

                fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        tableBody.innerHTML = "";
                        totalRecords = data.total_records;
                        totalPages = data.total_pages;

                        if (!data.seniors || data.seniors.length === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="12" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                        No beneficiary records found.
                                    </td>
                                </tr>`;
                            paginationNav.innerHTML = "";
                            return;
                        }

                        data.seniors.forEach((senior, index) => {
                            const statusColor =
                                senior.validation === "Validated" ? "text-green-600" :
                                senior.validation === "For Validation" ? "text-red-600" :
                                "text-red-600";

                            const createdAt = senior.date_created ? new Date(senior.date_created).toLocaleDateString() : "";
                            const modifiedAt = senior.date_modified ? new Date(senior.date_modified).toLocaleDateString() : "";

                            const buttonId = `dropdownBtn-${index}`;
                            const dropdownId = `dropdownMenu-${index}`;

                            const row = `
                                <tr class="border-b text-xs font-medium border-gray-200">
                                    <td class="px-2 py-3 ">
                                        <input type="checkbox" class="beneficiaryCheckbox text-blue-600 bg-gray-200 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-200 dark:border-gray-300" 
                                            value="${senior.applicant_id}" data-name="${senior.full_name || 'Unknown'}">
                                    </td>
                                    <td class="px-2 py-3 text-center">${senior.rownum}</td>
                                    <td class="px-2 py-3 truncate max-w-[120px]">${senior.full_name || ""}</td>
                                    <td class="px-2 py-3 ">${senior.birth_date || ""}</td>
                                    <td class="px-2 py-3 text-center">${senior.age || ""}</td>
                                    <td class="px-2 py-3 text-center sm-">${senior.gender || ""}</td>
                                    <td class="px-2 py-3 ">${senior.civil_status || ""}</td>
                                    <td class="px-2 py-3 truncate max-w-[100px]">${senior.barangay || ""}</td>
                                    <td class="px-2 py-3 ">${createdAt}</td>
                                    <td class="px-2 py-3 ">${modifiedAt}</td>
                                    <td class="px-2 py-3 text-center ${statusColor}">${senior.validation}</td>
                                    <td class="px-2 py-3 relative">
                                        <button id="${buttonId}" 
                                            class="inline-flex cursor-pointer items-center p-1 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd"
                                                    d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
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
                                                    <button onclick="addSingleBenefit('${senior.applicant_id}', '${(senior.full_name || 'Unknown').replace(/'/g, "\\'")}')"
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

                            checkbox.checked = window.globalSelectedBeneficiaries.has(senior.applicant_id);

                            checkbox.addEventListener("change", () => {
                                const beneficiaryId = checkbox.value;
                                const beneficiaryName = checkbox.dataset.name?.trim() || "Unknown";

                                if (checkbox.checked) {
                                    window.globalSelectedBeneficiaries.set(beneficiaryId, beneficiaryName);
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
                    })
                    .catch(err => {
                        console.error("Error fetching beneficiaries:", err);
                        let errorMessage = "Error loading data";
                        if (err.message.includes("500")) {
                            errorMessage = "Server error. Please check if benefits_distribution table exists.";
                        }

                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="12" class="text-center py-4 text-red-500">
                                    ${errorMessage}
                                    <br><small>Check browser console for details</small>
                                </td>
                            </tr>`;

                        showPopup("Failed to load beneficiaries. Please try again.", "error");
                    });
            };

            // ---------------- RENDER PAGINATION ----------------
            const renderPagination = (start, end) => {
                if (totalPages <= 1) {
                    paginationNav.innerHTML = "";
                    return;
                }

                let html = `
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400 mb-2 sm:mb-0">
                        Showing <span class="font-semibold text-gray-900 dark:text-white">${start}</span> –
                        <span class="font-semibold text-gray-900 dark:text-white">${end}</span> of
                        <span class="font-semibold text-gray-900 dark:text-white">${totalRecords}</span>
                    </span>
                    <ul class="inline-flex items-stretch -space-x-px">
                `;

                // Previous Button
                html += `
                    <li>
                        <button ${currentPage === 1 ? "disabled" : ""} data-nav="prev"
                            class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 ml-0 text-gray-500 bg-white rounded-l-sm border border-gray-300 
                            hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 
                                01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 
                                011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </li>
                `;

                // Page Numbers
                for (let i = 1; i <= totalPages; i++) {
                    html += `
                        <li>
                            <button data-page="${i}"
                                class="flex items-center justify-center text-sm py-2 px-2 sm:px-3 leading-tight min-w-[40px]
                                ${i === currentPage
                            ? 'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
                                ${i}
                            </button>
                        </li>
                    `;
                }

                // Next Button
                html += `
                    <li>
                        <button ${currentPage === totalPages ? "disabled" : ""} data-nav="next"
                            class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 text-gray-500 bg-white rounded-r-sm border border-gray-300 
                            hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 
                                011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 
                                01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </li>
                </ul>`;

                paginationNav.innerHTML = html;

                paginationNav.querySelectorAll("[data-page]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        currentPage = parseInt(btn.dataset.page);
                        fetchBeneficiaries();
                    });
                });

                paginationNav.querySelectorAll("[data-nav]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        if (btn.dataset.nav === "prev" && currentPage > 1) currentPage--;
                        else if (btn.dataset.nav === "next" && currentPage < totalPages) currentPage++;
                        fetchBeneficiaries();
                    });
                });
            };

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

                        if (isChecked) {
                            window.globalSelectedBeneficiaries.set(beneficiaryId, beneficiaryName);
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
                    window.globalSelectedBeneficiaries.forEach((name, id) => {
                        selectedBeneficiariesList.insertAdjacentHTML("beforeend", `
                            <div class="text-sm text-gray-700 dark:text-gray-300 py-1 truncate">• ${name}</div>
                        `);
                    });

                    benefitForm.reset();
                    benefitsCheckboxContainer.querySelectorAll('.benefit-amount-container').forEach(container => {
                        container.classList.add('hidden');
                    });
                    benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                    });

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

                    const selectedBenefits = [];
                    benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox:checked').forEach(checkbox => {
                        const benefitId = checkbox.value;
                        const benefitName = checkbox.closest('.benefit-item').querySelector('label').textContent.trim();
                        const amountInput = checkbox.closest('.benefit-item').querySelector('.benefit-amount');
                        const amount = amountInput.value.trim();

                        if (!amount) {
                            showPopup(`Please enter amount for ${benefitName}`, "error");
                            amountInput.focus();
                            throw new Error(`Amount required for ${benefitName}`);
                        }

                        selectedBenefits.push({
                            id: benefitId,
                            name: benefitName,
                            amount: parseFloat(amount)
                        });
                    });

                    if (selectedBenefits.length === 0) {
                        showPopup("Please select at least one benefit and enter amounts.", "error");
                        return;
                    }

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
                                admin_user_id: adminUserId
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
                        if (error.message && error.message.includes('Amount required')) {
                            // Error already handled above
                        } else {
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

            // ---------------- PRINT MODAL EVENT LISTENERS ----------------
            if (printButton) {
                printButton.addEventListener('click', openPrintModal);
            }

            if (closePrintModal) {
                closePrintModal.addEventListener('click', () => {
                    printModal.classList.add('hidden');
                });
            }

            if (cancelPrint) {
                cancelPrint.addEventListener('click', () => {
                    printModal.classList.add('hidden');
                });
            }

            if (printForm) {
                printForm.addEventListener('submit', handlePrintForm);
            }

            // ---------------- ACTION FUNCTIONS ----------------
            window.viewBeneficiary = (applicantId, fullName) => {
                fetchBenefitsHistory(applicantId, fullName);
            };

            window.addSingleBenefit = (applicantId, fullName) => {
                window.globalSelectedBeneficiaries.clear();
                window.globalSelectedBeneficiaries.set(applicantId, fullName);

                const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");
                checkboxes.forEach(checkbox => {
                    checkbox.checked = (checkbox.value === applicantId);
                });

                updateSelectAllCheckbox();
                updateAddBenefitVisibility();

                addBenefitBtn.click();
            };

            // ---------------- INITIAL LOAD ----------------
            setupResponsiveDropdowns();
            fetchBarangays();
            fetchBenefitTypes();
            fetchBenefits();
            fetchBeneficiaries();

            // Handle window resize
            window.addEventListener('resize', () => {
                fetchBeneficiaries();
            });
        });
    </script>

</body>

</html>