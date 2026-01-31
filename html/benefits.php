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
    $profile_photo_url = '../' . $user_data['profile_photo'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Benefits</title>
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

        /* Responsive fixes - preserve original design */
        @media (max-width: 768px) {
            .responsive-padding {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .responsive-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .responsive-table {
                min-width: 640px;
            }

            .responsive-text {
                font-size: 0.875rem !important;
            }

            .responsive-heading {
                font-size: 1.25rem !important;
            }

            .responsive-modal {
                padding: 0.5rem !important;
                margin: 0.5rem !important;
            }

            .responsive-modal-content {
                width: 95% !important;
                max-width: 95% !important;
            }
        }

        @media (max-width: 640px) {
            .responsive-table {
                min-width: 500px;
            }

            .responsive-text-sm {
                font-size: 0.8125rem !important;
            }

            .responsive-button {
                padding: 0.375rem 0.75rem !important;
                font-size: 0.8125rem !important;
            }

            .responsive-icon {
                width: 1rem !important;
                height: 1rem !important;
            }
        }

        @media (max-width: 480px) {
            .responsive-table {
                min-width: 400px;
            }

            .responsive-hide-text {
                display: none;
            }

            .responsive-show-icon {
                display: inline-block !important;
            }
        }

        /* Prevent horizontal overflow */
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Make sure the ordered list numbers show even with flex items */
        #benefitsList {
            counter-reset: benefit-counter;
            list-style: none;
            /* Hide default browser numbering */
            /* padding-left: 1.5rem; */
            /* Adds space for custom numbers */
        }

        #benefitsList li {
            counter-increment: benefit-counter;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 1.2rem;
            /* space for the number */
            margin-bottom: 0.25rem;
        }

        /* Custom number before each <li> */
        #benefitsList li::before {
            content: counter(benefit-counter) ".";
            position: absolute;
            left: 0;
            color: #374151;
            /* Tailwind gray-700 */
            font-weight: 600;
        }

        /* Touch-friendly sizes for mobile */
        @media (max-width: 768px) {

            button,
            [role="button"],
            a[href] {
                min-height: 44px;
                min-width: 44px;
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
                        <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white responsive-text">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 h-8 w-8 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
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
                            <span class="ml-3 responsive-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="./register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-user-plus w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3 responsive-text">Register</span>
                        </a>
                    </li>
                    <li>
                        <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                            class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            <i class="fas fa-list w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap responsive-text">Master List</span>
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
                            <span class="ml-3 responsive-text">Benefits</span>
                        </a>
                    </li>
                    <li>
                        <a href="./generate_id.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3 responsive-text">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="./reports/report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3 responsive-text">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="./archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-archive w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3 responsive-text">Archived</span>
                        </a>
                    </li>
                    <li>
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/settings/profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3 responsive-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        <!-- Benefits  -->
        <main class="p-4 md:ml-64 pt-20 flex flex-col">
            <div class="flex items-center flex-row">
                <div class="border border-b-0">
                    <a href="#" type="button" class="cursor-pointer">
                        <h4 class="text-xl font-medium text-blue-700 px-2 responsive-text">Benefits</h4>
                    </a>
                </div>
                <div class="flex items-center flex-row border dark:text-white border-t-0 border-r-0 border-l-0 w-full">
                    <div class="">
                        <a href="./beneficiary.php?session_context=<?php echo $ctx; ?>" type="button" class="cursor-pointer">
                            <h4 class="text-xl font-medium dark:text-blue px-2 responsive-text">Beneficiaries</h4>
                        </a>
                    </div>
                </div>
            </div>
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                        <div>
                            <p class="text-2xl font-semibold px-5 mt-5 text-gray-900 dark:text-white responsive-heading">Benefits</p>
                        </div>
                        <div
                            class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <div class="w-full md:w-1/2">
                                <form class="flex items-center">
                                    <label for="simple-search" class="sr-only">Search</label>
                                    <div class="relative w-full">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                fill="currentColor" viewbox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <input type="text" id="simple-search"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Search" required="">
                                    </div>
                                </form>
                            </div>
                            <div
                                class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                                <button type="button" id="defaultModalButton" data-modal-target="defaultModal"
                                    data-modal-toggle="defaultModal" class="flex items-center justify-center cursor-pointer text-white bg-blue-700
                                    hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600
                                    dark:hover:bg-blue-700 responsive-button">
                                    <svg class="h-3.5 w-3.5 mr-2 responsive-icon" fill="currentColor" viewbox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                    </svg>
                                    <span class="responsive-hide-text">Add Benefits</span>
                                    <span class="responsive-show-icon" style="display: none;">Add</span>
                                </button>
                                <!-- Filter -->
                                <div class="relative flex items-center space-x-3 w-full md:w-auto">
                                    <button id="filterDropdownButton" data-dropdown-toggle="filterDropdown"
                                        class="w-full md:w-auto cursor-pointer flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10  dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 responsive-button"
                                        type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                            class="h-4 w-4 mr-2 text-gray-400 responsive-icon" viewbox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Filter
                                        <svg class="-mr-1 ml-1.5 w-5 h-5 responsive-icon" fill="currentColor" viewbox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path clip-rule="evenodd" fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                        </svg>
                                    </button>
                                    <div id="filterDropdown"
                                        class="absolute z-9999 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                                        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Filter by
                                            address
                                        </h6>
                                        <ul class="space-y-2 text-sm" id="filterAddresses"
                                            aria-labelledby="filterDropdownButton">

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto responsive-table-container">
                            <table id="deceasedTable" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 responsive-table">
                                <thead class="text-sm text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-white">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">List of Benefits</th>
                                        <th scope="col" class="px-17 py-3 flex items-center justify-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b dark:border-gray-700 dark:text-white">
                                        <td class="px-4 py-3 responsive-text">Availed of Social Pension</td>
                                        <td class="px-15 py-3 flex items-center justify-end">
                                            <button id="apple-imac-27-dropdown-button"
                                                class="inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                                type="button">
                                                <svg class="w-6 h-6 text-gray-800 dark:text-white responsive-icon" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                        d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z"
                                                        clip-rule="evenodd" />
                                                </svg>

                                            </button>
                                            <button id="apple-imac-27-dropdown-button"
                                                class="inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                                type="button">
                                                <svg class="w-6 h-6 text-gray-800 dark:text-white responsive-icon" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 flex items-center justify-end">
                                            <button id="apple-imac-27-dropdown-button"
                                                class="inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                                type="button">
                                            </button>
                                        </td>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Benefits modal -->
            <div id="defaultModal" tabindex="-1" aria-hidden="true"
                class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full responsive-modal">
                <div class="relative p-4 w-138 max-w-xl h-full md:h-auto responsive-modal-content">
                    <!-- Modal content -->
                    <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                        <!-- Modal header -->
                        <div
                            class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Add Benefits
                            </h3>
                            <button type="button"
                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                data-modal-toggle="defaultModal">
                                <svg aria-hidden="true" class="w-5 h-5 cursor-pointer" fill="currentColor"
                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <form action="/MSWDPALUAN_SYSTEM-MAIN/php/benefits/save_benefits.php" method="POST" id="benefitsForm">
                            <input type="hidden" name="benefits_json" id="benefits_json">
                            <div class="w-full h-full grid gap-4 mb-4 sm:grid-cols-1">
                                <div class="flex flex-row w-full gap-2 justify-end items-end    ">
                                    <div class="w-full">
                                        <label for="name"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name</label>
                                        <input type="text" name="benefitname" id="benfitname"
                                            class="bg-gray-50 w-full border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Type benefit here">
                                    </div>
                                    <button type="button" id="benefitBtn" class="flex items-center justify-center px-4 py-2.6 h-10.5 text-sm font-medium cursor-pointer text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 responsive-button">
                                        <svg class="h-3.5 w-3.5 mr-2 responsive-icon" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                        </svg>
                                        Add
                                    </button>
                                </div>
                                <div
                                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50  rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <ol id="benefitsList"
                                        class="max-w-md space-y-1 h-20 overflow-y-auto text-gray-500 list-decimal list-inside dark:text-gray-400">
                                        <!-- <li>
                                            <span class="font-semibold text-gray-900 dark:text-white"></span>
                                        </li> -->
                                    </ol>

                                </div>
                                <div class="flex justify-end w-full">
                                    <button type="submit" class="flex items-center justify-center px-4 py-2 text-sm font-medium cursor-pointer text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 responsive-button">
                                        <svg class="h-3.5 w-3.5 mr-2 responsive-icon" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                        </svg>
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Edit/Add Modal -->
            <div id="benefitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 bg-opacity-40">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                    <h3 id="benefitModalTitle" class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Add Benefit</h3>
                    <input type="text" id="benefitModalInput" placeholder="Type benefit here" class="w-full p-2.5 rounded-lg border border-gray-300 mb-4">
                    <div class="flex justify-end space-x-2">
                        <button id="benefitModalCancel" class="px-4 py-2 cursor-pointer bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                        <button id="benefitModalSave" class="px-4 py-2 cursor-pointer bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                    </div>
                </div>
            </div>

            <!-- PopUp Message  -->
            <div id="popupModal" class="fixed inset-0 bg-gray-900/50 bg-opacity-40 hidden flex z-50  items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out"
                    id="popupBox">
                    <h2 id="popupTitle" class="text-xl font-semibold mb-3 text-gray-800"></h2>
                    <p id="popupMessage" class="text-gray-600 mb-6 leading-relaxed"></p>
                    <button id="popupCloseBtn"
                        class="px-4 py-1 cursor-pointer bg-blue-600 text-white text-xs rounded-sm font-medium hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">OK</button>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="/MSWDPALUAN_SYSTEM-MAIN/js/tailwind.config.js"></script>
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

        // Responsive text handling
        function handleResponsiveText() {
            const width = window.innerWidth;
            const hideTextElements = document.querySelectorAll('.responsive-hide-text');
            const showIconElements = document.querySelectorAll('.responsive-show-icon');

            if (width <= 480) {
                hideTextElements.forEach(el => el.style.display = 'none');
                showIconElements.forEach(el => el.style.display = 'inline');
            } else {
                hideTextElements.forEach(el => el.style.display = 'inline');
                showIconElements.forEach(el => el.style.display = 'none');
            }
        }

        // Handle responsive layout on load and resize
        window.addEventListener('load', handleResponsiveText);
        window.addEventListener('resize', handleResponsiveText);
    </script>
    <script>
        function benefits() {
            document.getElementById('benefits').style.display = "flex";
            document.getElementById('beneficiary').style.display = "none";
        }

        function beneficiary() {
            document.getElementById('benefits').style.display = "none";
            document.getElementById('beneficiary').style.display = "flex";
        }
    </script>
    <script>
        const table = document.getElementById("deceasedTable");
        const dropdownButton = document.getElementById("filterDropdownButton");
        const dropdownMenu = document.getElementById("filterDropdownMenu");

        // Toggle dropdown visibility
        dropdownButton.addEventListener("click", () => {
            dropdownMenu.classList.toggle("hidden");
        });

        // Filter selection
        dropdownMenu.querySelectorAll("button").forEach(btn => {
            btn.addEventListener("click", () => {
                const filterType = btn.getAttribute("data-filter");
                const rows = Array.from(table.querySelectorAll("tbody tr"));

                if (filterType === "az") {
                    rows.sort((a, b) => {
                        const nameA = a.cells[1].textContent.toLowerCase();
                        const nameB = b.cells[1].textContent.toLowerCase();
                        return nameA.localeCompare(nameB);
                    });
                    dropdownButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                </svg>
                Filter: A-Z
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            `;
                } else if (filterType === "newlyRegistered") {
                    rows.sort((a, b) => {
                        const dateA = new Date(a.cells[6].textContent);
                        const dateB = new Date(b.cells[6].textContent);
                        return dateB - dateA; // newest first
                    });
                    dropdownButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                </svg>
                Filter: Newly Registered
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            `;
                }

                const tbody = table.querySelector("tbody");
                rows.forEach(row => tbody.appendChild(row));
                updateNumbers();

                dropdownMenu.classList.add("hidden"); // close menu after selection
            });
        });

        function updateNumbers() {
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach((row, index) => row.cells[0].textContent = index + 1);
        }

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add("hidden");
            }
        });
    </script>

    <script>
        // Get button and dropdown
        const filterButton = document.getElementById('filterDropdownButtons');
        const filterMenu = document.getElementById('filterDropdownMenus');

        // Toggle dropdown visibility
        filterButton.addEventListener('click', (event) => {
            event.stopPropagation(); // prevent closing immediately
            filterMenu.classList.toggle('hidden');
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!filterMenu.contains(event.target) && !filterButton.contains(event.target)) {
                filterMenu.classList.add('hidden');
            }
        });

        // Handle filter button clicks
        filterMenu.addEventListener('click', (event) => {
            if (event.target.matches('[data-filter]')) {
                const filterType = event.target.getAttribute('data-filter');
                applyFilter(filterType);
                filterMenu.classList.add('hidden'); // Close after selecting
            }
        });

        // Example filter logic (customize this for your data)
        function applyFilter(type) {
            switch (type) {
                case 'az':
                    console.log('Filter applied: A-Z sorting');
                    // your sorting/filter logic here
                    break;
                case 'age':
                    console.log('Filter applied: 60-100');
                    // your filtering logic here
                    break;
                case 'newlyRegistered':
                    console.log('Filter applied: Newly Registered');
                    // your filtering logic here
                    break;
                default:
                    console.log('Unknown filter:', type);
            }
        }
    </script>

    <!-- AddBenefits -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const addButton = document.getElementById("benefitBtn");
            const benefitInput = document.getElementById("benfitname");
            const benefitsList = document.getElementById("benefitsList");
            const benefitsForm = document.getElementById("benefitsForm");

            // Popup elements
            const popupModal = document.getElementById("popupModal");
            const popupTitle = document.getElementById("popupTitle");
            const popupMessage = document.getElementById("popupMessage");
            const popupCloseBtn = document.getElementById("popupCloseBtn");

            // Add benefit to list
            addButton.addEventListener("click", function() {
                const benefitText = benefitInput.value.trim();
                // if (!benefitText) return alert("Please enter a benefit name first.");
                if (!benefitText) {
                    showPopup("⚠️ Error", "Please enter a benefit name first.");
                    return;
                }

                const newItem = document.createElement("li");
                newItem.innerHTML = `
                    <span class="font-semibold text-gray-900 dark:text-white">${benefitText}</span>
                    <button type="button" class="text-red-600 cursor-pointer hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 font-bold text-lg ml-2" aria-label="Remove Benefit">&times;</button>
                `;
                benefitsList.appendChild(newItem);
                benefitInput.value = "";
            });

            // Remove benefit
            benefitsList.addEventListener("click", function(e) {
                if (e.target.tagName === "BUTTON" || e.target.closest("button")) {
                    e.target.closest("li").remove();
                }
            });

            // Form submission with AJAX
            benefitsForm.addEventListener("submit", function(e) {
                e.preventDefault(); // prevent normal submit

                // Collect benefit names
                const benefitsArray = [];
                benefitsList.querySelectorAll("li span").forEach(span => {
                    benefitsArray.push(span.textContent.trim());
                });

                if (benefitsArray.length === 0) {
                    showPopup("⚠️ Error", "Please add at least one benefit before saving.");
                    return;
                }

                // Send AJAX POST
                fetch('../php/benefits/save_benefits.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            benefits: benefitsArray
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showPopup("✅ Success", "Benefits added successfully!");
                            // Clear list after saving
                            benefitsList.innerHTML = "";
                        } else {
                            showPopup("⚠️ Error", data.message || "Something went wrong.");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showPopup("⚠️ Error", "Failed to save benefits.");
                    });
            });

            // Show popup function
            function showPopup(title, message) {
                popupTitle.textContent = title;
                popupMessage.textContent = message;
                popupModal.classList.remove("hidden");
                setTimeout(() => {
                    popupModal.querySelector("#popupBox").classList.add("scale-100", "opacity-100");
                }, 10);
            }

            // Close popup
            popupCloseBtn.addEventListener("click", function() {
                popupModal.querySelector("#popupBox").classList.remove("scale-100", "opacity-100");
                setTimeout(() => {
                    popupModal.classList.add("hidden");
                }, 600);
                window.location.reload();
            });
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // DOM Elements
            const benefitsTableBody = document.querySelector("#deceasedTable tbody");
            const popupModal = document.getElementById("popupModal");
            const popupTitle = document.getElementById("popupTitle");
            const popupMessage = document.getElementById("popupMessage");
            const popupCloseBtn = document.getElementById("popupCloseBtn");
            const benefitModal = document.getElementById("benefitModal");
            const benefitModalTitle = document.getElementById("benefitModalTitle");
            const benefitModalInput = document.getElementById("benefitModalInput");
            const benefitModalSave = document.getElementById("benefitModalSave");
            const benefitModalCancel = document.getElementById("benefitModalCancel");

            // Variables
            let currentEditId = null;
            const adminUserId = <?php echo json_encode($user_id ?? 0); ?>;

            // ========== POPUP FUNCTIONS ==========
            function showPopup(title, message) {
                popupTitle.textContent = title;
                popupMessage.textContent = message;
                popupModal.classList.remove("hidden");
                setTimeout(() => {
                    popupModal.querySelector("#popupBox").classList.add("scale-100", "opacity-100");
                }, 10);
            }

            function showConfirmPopup(title, message, callback) {
                popupTitle.textContent = title;
                popupMessage.textContent = message;
                popupCloseBtn.textContent = "Cancel";

                // Create OK button
                const okBtn = document.createElement("button");
                okBtn.textContent = "OK";
                okBtn.className = popupCloseBtn.className;
                okBtn.style.marginLeft = "10px";
                okBtn.style.backgroundColor = "#27AE60";

                // Add OK button temporarily
                const popupBox = popupModal.querySelector("#popupBox");
                popupBox.appendChild(okBtn);

                popupModal.classList.remove("hidden");
                setTimeout(() => popupBox.classList.add("scale-100", "opacity-100"), 10);

                function cleanUp() {
                    popupBox.classList.remove("scale-100", "opacity-100");
                    setTimeout(() => {
                        popupModal.classList.add("hidden");
                        okBtn.remove();
                        popupCloseBtn.textContent = "OK";
                    }, 300);
                }

                okBtn.onclick = () => {
                    callback(true);
                    cleanUp();
                };

                popupCloseBtn.onclick = () => {
                    callback(false);
                    cleanUp();
                };
            }

            // ========== LOAD BENEFITS ==========
            function loadBenefits() {
                fetch('../php/benefits/fetch_benefits.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            benefitsTableBody.innerHTML = "";
                            data.benefits.forEach(benefit => {
                                const row = document.createElement("tr");
                                row.classList.add("border-b", "border-gray-200", "text-gray-700", "dark:border-gray-700", "dark:text-white");
                                row.innerHTML = `
                                <td class="px-4 py-3 text-gray-700 dark:text-white responsive-text">${benefit.benefit_name}</td>
                                <td class="px-15 py-3 flex items-center justify-end space-x-2">
                                    <button class="edit-btn inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white" data-id="${benefit.id}" type="button" title="Edit">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 010 2.828l-10 10A2 2 0 016 16H4a1 1 0 01-1-1v-2a2 2 0 01.586-1.414l10-10a2 2 0 012.828 0z"/></svg>
                                    </button>
                                    <button class="delete-btn inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-600" data-id="${benefit.id}" type="button" title="Delete">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v8a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v8a1 1 0 11-2 0V6z" clip-rule="evenodd"/></svg>
                                    </button>
                                </td>
                            `;
                                benefitsTableBody.appendChild(row);
                            });

                            attachDeleteButtons();
                            attachEditButtons(); // Re-attach edit buttons after loading
                        } else {
                            showPopup("Error", data.message || "Failed to load benefits.");
                        }
                    })
                    .catch(err => {
                        console.error("Load benefits error:", err);
                        showPopup("Error", "Failed to fetch benefits.");
                    });
            }

            // ========== ATTACH EVENT HANDLERS ==========
            function attachDeleteButtons() {
                document.querySelectorAll(".delete-btn").forEach(btn => {
                    btn.addEventListener("click", function() {
                        const id = this.dataset.id;
                        showConfirmPopup("Confirm Delete", "Are you sure you want to delete this benefit?", function(confirmed) {
                            if (!confirmed) return;

                            fetch(`../php/benefits/delete_benefit.php`, {
                                    method: "POST",
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        id: id,
                                        session_context: "admin",
                                        admin_user_id: adminUserId
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        showPopup("Success", "Benefit deleted successfully!");
                                        loadBenefits();
                                    } else {
                                        showPopup("Error", data.message || "Failed to delete benefit.");
                                    }
                                })
                                .catch(err => {
                                    console.error("Delete error:", err);
                                    showPopup("Error", "Failed to delete benefit.");
                                });
                        });
                    });
                });
            }

            function attachEditButtons() {
                document.querySelectorAll(".edit-btn").forEach(btn => {
                    btn.addEventListener("click", function(e) {
                        e.stopPropagation(); // Prevent event bubbling

                        const row = this.closest("tr");
                        const id = this.dataset.id;
                        const name = row.querySelector("td:first-child").textContent.trim();

                        currentEditId = id;
                        benefitModalTitle.textContent = "Edit Benefit";
                        benefitModalInput.value = name;
                        benefitModal.classList.remove("hidden");
                    });
                });
            }

            // ========== MODAL HANDLERS ==========
            benefitModalCancel.addEventListener("click", () => {
                benefitModal.classList.add("hidden");
                currentEditId = null;
                benefitModalInput.value = "";
            });

            benefitModalSave.addEventListener("click", () => {
                const newName = benefitModalInput.value.trim();
                if (!newName) {
                    showPopup("Error", "Benefit name cannot be empty!");
                    return;
                }

                fetch("../php/benefits/update_benefit.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            id: currentEditId,
                            benefit_name: newName,
                            session_context: "admin",
                            admin_user_id: adminUserId
                        })
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log("Update response:", data);

                        if (data && data.success) {
                            showPopup("Success", data.message || "Benefit updated successfully!");
                            benefitModal.classList.add("hidden");
                            currentEditId = null;
                            benefitModalInput.value = "";
                            loadBenefits(); // Refresh the list
                        } else {
                            const errorMsg = data && data.error ? data.error : "Failed to update benefit.";
                            console.error("Update Response Error:", data);
                            showPopup("Error", errorMsg);
                        }
                    })
                    .catch(err => {
                        console.error("Fetch error:", err);
                        showPopup("Error", "Network error. Please check console.");
                    });
            });

            // ========== INITIALIZE ==========
            popupCloseBtn.addEventListener("click", function() {
                const popupBox = popupModal.querySelector("#popupBox");
                popupBox.classList.remove("scale-100", "opacity-100");
                setTimeout(() => popupModal.classList.add("hidden"), 300);
            });

            // Initial load
            loadBenefits();
        });
    </script>
</body>

</html>