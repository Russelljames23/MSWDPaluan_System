<?php
require_once "../php/login/admin_header.php";
require_once "../php/db.php";

// Initialize variables
$seniors = [];
$barangays = [];
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;

// Check if database connection is working
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Safe fetch function
function safeFetchAll($stmt)
{
    $rows = [];
    if ($stmt) {
        try {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result !== false) {
                $rows = $result;
            }
        } catch (Exception $e) {
            // Silently continue
        }
    }
    return $rows;
}

// Safe single fetch function
function safeFetch($stmt)
{
    if (!$stmt) {
        return false;
    }

    try {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Count total seniors - using direct query approach
    $count_query = "SELECT COUNT(*) as total FROM applicants WHERE status = 'Active'";

    $total_count = 0;
    try {
        $count_stmt = $conn->query($count_query);
        if ($count_stmt) {
            $count_row = safeFetch($count_stmt);
            if ($count_row && isset($count_row['total'])) {
                $total_count = (int)$count_row['total'];
            }
        }
    } catch (Exception $e) {
        $total_count = 0;
    }

    $total_pages = $total_count > 0 ? ceil($total_count / $items_per_page) : 0;

    // Calculate offset
    $offset = ($current_page - 1) * $items_per_page;

    // Main query for seniors
    $query = "SELECT 
                a.applicant_id,
                a.last_name,
                a.first_name,
                a.middle_name,
                a.birth_date,
                a.gender,
                a.civil_status,
                a.validation,
                a.status
              FROM applicants a
              WHERE a.status = 'Active'
              ORDER BY a.last_name, a.first_name";

    // Add LIMIT and OFFSET only if we have results
    if ($total_count > 0) {
        $query .= " LIMIT " . intval($items_per_page) . " OFFSET " . intval($offset);
    }

    $seniors_raw = [];
    try {
        $stmt = $conn->query($query);
        if ($stmt) {
            $seniors_raw = safeFetchAll($stmt);
        }
    } catch (Exception $e) {
        $seniors_raw = [];
    }

    // Process each senior
    foreach ($seniors_raw as $senior) {
        // Get address
        $address = ['barangay' => 'N/A', 'municipality' => 'N/A', 'province' => 'N/A'];
        if (isset($senior['applicant_id'])) {
            try {
                $addr_query = "SELECT barangay, municipality, province FROM addresses WHERE applicant_id = " . intval($senior['applicant_id']);
                $addr_stmt = $conn->query($addr_query);
                if ($addr_stmt) {
                    $addr_data = safeFetch($addr_stmt);
                    if ($addr_data) {
                        $address = $addr_data;
                    }
                }
            } catch (Exception $e) {
                // Keep default address
            }
        }

        // Get registration details
        $registration = ['id_number' => 'N/A', 'date_of_registration' => 'N/A', 'local_control_number' => 'N/A'];
        if (isset($senior['applicant_id'])) {
            try {
                $reg_query = "SELECT id_number, date_of_registration, local_control_number 
                             FROM applicant_registration_details 
                             WHERE applicant_id = " . intval($senior['applicant_id']);
                $reg_stmt = $conn->query($reg_query);
                if ($reg_stmt) {
                    $reg_data = safeFetch($reg_stmt);
                    if ($reg_data) {
                        $registration = $reg_data;
                    }
                }
            } catch (Exception $e) {
                // Keep default registration
            }
        }

        // Calculate age
        $age = 'N/A';
        if (!empty($senior['birth_date']) && $senior['birth_date'] != '0000-00-00') {
            try {
                $birthDate = new DateTime($senior['birth_date']);
                $today = new DateTime();
                $age = $birthDate->diff($today)->y;
            } catch (Exception $e) {
                $age = 'N/A';
            }
        }

        // Format full name
        $full_name = '';
        if (isset($senior['last_name']) && isset($senior['first_name'])) {
            $full_name = strtoupper($senior['last_name'] . ', ' . $senior['first_name']);
            if (!empty($senior['middle_name'])) {
                $full_name .= ' ' . strtoupper($senior['middle_name']);
            }
        }

        $seniors[] = [
            'applicant_id' => $senior['applicant_id'] ?? 0,
            'full_name' => $full_name,
            'birth_date' => $senior['birth_date'] ?? 'N/A',
            'age' => $age,
            'gender' => $senior['gender'] ?? 'N/A',
            'civil_status' => $senior['civil_status'] ?? 'N/A',
            'barangay' => $address['barangay'],
            'municipality' => $address['municipality'],
            'province' => $address['province'],
            'validation' => $senior['validation'] ?? 'For Validation',
            'status' => $senior['status'] ?? 'Active',
            'id_number' => $registration['id_number'],
            'date_of_registration' => $registration['date_of_registration'],
            'local_control_number' => $registration['local_control_number']
        ];
    }
} catch (Exception $e) {
    // Continue with empty array
}

// Fetch barangays
$barangays = [];
try {
    $barangay_query = "SELECT DISTINCT * FROM addresses WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
    $barangay_stmt = $conn->query($barangay_query);
    if ($barangay_stmt) {
        $barangays = safeFetchAll($barangay_stmt);
    }
} catch (Exception $e) {
    $barangays = [];
}

// Only use session_context if it exists
$ctx = isset($_GET['session_context']) ? urlencode($_GET['session_context']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Senior Citizen ID - MSWD Paluan</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .print-page,
            .print-page * {
                visibility: visible;
            }

            .print-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
            }

            .id-front-page {
                page-break-after: always;
            }

            @page {
                size: landscape;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }

        /* Custom styles for ID cards */
        .id-card {
            width: 3.17in;
            height: 2.14in;
            border: 1px solid #000;
            padding: 2px;
            font-family: Arial, sans-serif;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .id-header {
            text-align: center;
            display: flex;
            flex-direction: row;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 1px;
            line-height: 1;
        }

        .id-content {
            padding: 1px;
        }

        .id-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .id-address {
            text-transform: uppercase;
        }

        .id-footer {
            text-align: center;
            font-weight: bold;
            color: #dc2626;
            margin-top: 2px;
        }

        /* For print layout */
        .print-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 0.1in;
            width: 13in;
            height: 8.5in;
            padding: 0.2in;
        }

        /* Benefits page styling */
        .benefits-page {
            width: 13in;
            height: 8.5in;
            padding: 0.3in;
            page-break-after: always;
            font-family: Arial, sans-serif;
        }

        .benefits-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 0.1in;
        }

        .benefits-list {
            font-size: 8pt;
            line-height: 1.2;
        }

        .benefits-footer {
            margin-top: 0.2in;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 2in;
            display: inline-block;
            margin-top: 0.1in;
        }

        /* Pagination styles */
        .pagination-btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            color: #374151;
        }

        .pagination-btn:hover {
            background-color: #f3f4f6;
        }

        .pagination-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
    </style>
</head>

<body>
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
                    <a href="#" class="flex items-center justify-between mr-4 ">
                        <img src="../img/MSWD_LOGO-removebg-preview.png"
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
                    <button type="button" data-drawer-toggle="drawer-navigation" aria-controls="drawer-navigation"
                        class="p-2 mr-1 text-gray-500 rounded-lg md:hidden hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                        <span class="sr-only">Toggle search</span>
                        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                            </path>
                        </svg>
                    </button>
                    <button type="button"
                        class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-8 h-8 rounded-full"
                            src="https://spng.pngfind.com/pngs/s/378-3780189_member-icon-png-transparent-png.png"
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
                        <a href="./index.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-blue-700 group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">

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
                        <a href="./register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
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
                            class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
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
                                <a href="./SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="./SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="./SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="./benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-blue-700 dark:text-white group">
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
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-blue-700 bg-blue-100 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-blue-700 group">
                            <svg class="w-6 h-6 text-blue-700 group-hover:text-blue-700 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                    clip-rule="evenodd" />
                            </svg>

                            <span class="ml-3">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="./reports/report.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="./archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                            <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 1 0 0 4h16a2 2 0 1 0 0-4H4Zm0 6h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8Zm10.707 5.707a1 1 0 0 0-1.414-1.414l-.293.293V12a1 1 0 1 0-2 0v2.586l-.293-.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l2-2Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="ml-3">Archived</span>
                        </a>
                    </li>
                    <li>
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/settings/profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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

        <main class="p-4 md:ml-64 h-auto pt-20">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generate Senior Citizen ID</h2>
                    <p class="text-gray-600 dark:text-gray-400">Create and print ID cards in batch format (9 per page) following exact document format</p>
                </div>

                <!-- Search and Filter Section -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search-senior" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Search Senior Citizen
                            </label>
                            <div class="relative">
                                <input type="text" id="search-senior"
                                    class="w-full p-2.5 pl-10 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Search by name, ID number, or barangay">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-barangay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Barangay
                            </label>
                            <select id="filter-barangay"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Barangays</option>
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay['barangay']); ?>">
                                        <?php echo htmlspecialchars($barangay['barangay']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-validation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Validation Status
                            </label>
                            <select id="filter-validation"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Status</option>
                                <option value="Validated">Validated</option>
                                <option value="For Validation">For Validation</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Senior Selection Table -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Seniors for ID Generation</h3>
                        <div class="w-full md:w-auto flex items-center space-x-3">
                            <button id="select-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Select All
                            </button>
                            <button id="deselect-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Deselect All
                            </button>
                            <span id="selected-count" class="text-sm text-gray-600 dark:text-gray-400">0 selected</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 w-12">
                                        <input id="master-checkbox" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    </th>
                                    <th scope="col" class="px-4 py-3">No.</th>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Birthdate</th>
                                    <th scope="col" class="px-4 py-3">Age</th>
                                    <th scope="col" class="px-4 py-3">Gender</th>
                                    <th scope="col" class="px-4 py-3">Barangay</th>
                                    <th scope="col" class="px-4 py-3">ID Number</th>
                                    <th scope="col" class="px-4 py-3">Date Issued</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="seniors-table-body">
                                <?php if (empty($seniors)): ?>
                                    <tr>
                                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No active senior citizens found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($seniors as $index => $senior): ?>
                                        <?php
                                        $global_index = $offset + $index + 1;
                                        // Format the registration date for display
                                        $date_issued_display = 'N/A';
                                        if (!empty($senior['date_of_registration']) && $senior['date_of_registration'] != '0000-00-00') {
                                            try {
                                                $dateObj = new DateTime($senior['date_of_registration']);
                                                $date_issued_display = $dateObj->format('m/d/Y');
                                            } catch (Exception $e) {
                                                $date_issued_display = 'N/A';
                                            }
                                        }
                                        ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 senior-row"
                                            data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                            data-validation="<?php echo htmlspecialchars($senior['validation'] ?? ''); ?>">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" class="senior-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    data-id="<?php echo htmlspecialchars($senior['applicant_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($senior['full_name']); ?>"
                                                    data-birthdate="<?php echo htmlspecialchars($senior['birth_date'] ?? ''); ?>"
                                                    data-age="<?php echo htmlspecialchars($senior['age'] ?? ''); ?>"
                                                    data-gender="<?php echo htmlspecialchars($senior['gender'] ?? ''); ?>"
                                                    data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                                    data-id-number="<?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?>"
                                                    data-date-issued="<?php echo htmlspecialchars($senior['date_of_registration'] ?? ''); ?>"
                                                    data-local-control="<?php echo htmlspecialchars($senior['local_control_number'] ?? ''); ?>">
                                            </td>
                                            <td class="px-4 py-3"><?php echo $global_index; ?></td>
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($senior['full_name']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php
                                                if (!empty($senior['birth_date']) && $senior['birth_date'] != '0000-00-00') {
                                                    try {
                                                        $birthDate = new DateTime($senior['birth_date']);
                                                        echo htmlspecialchars($birthDate->format('m/d/Y'));
                                                    } catch (Exception $e) {
                                                        echo 'N/A';
                                                    }
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['age'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['gender'] ?? ''); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['barangay'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($date_issued_display); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs rounded <?php echo ($senior['validation'] === 'Validated') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($senior['validation'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex items-center justify-between p-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-700 dark:text-gray-400">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                    <span class="font-medium"><?php echo min($offset + $items_per_page, $total_count); ?></span> of
                                    <span class="font-medium"><?php echo $total_count; ?></span> seniors
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=1&session_context=<?php echo $ctx; ?>" class="pagination-btn">
                                            First
                                        </a>
                                        <a href="?page=<?php echo $current_page - 1; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn">
                                            Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Show page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);

                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?page=<?php echo $i; ?>&session_context=<?php echo $ctx; ?>"
                                            class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=<?php echo $current_page + 1; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn">
                                            Next
                                        </a>
                                        <a href="?page=<?php echo $total_pages; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn">
                                            Last
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ID Preview and Generation Controls -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Preview Controls -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ID Preview & Generation</h3>

                        <!-- Current Selection -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">Selected for Generation</h4>
                            <div id="selected-list" class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>
                            </div>
                        </div>

                        <!-- Generation Options -->
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Signatory Selection
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="osca-head" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                            OSCA HEAD
                                        </label>
                                        <select id="osca-head" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="EVELYN V. BELTRAN">EVELYN V. BELTRAN</option>
                                            <option value="ROSALINA V. BARRALES">ROSALINA V. BARRALES</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="municipal-mayor" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                            Municipal Mayor
                                        </label>
                                        <select id="municipal-mayor" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="MICHAEL D. DIAZ">MICHAEL D. DIAZ</option>
                                            <option value="MERIAM E. LEYCANO-QUIJANO">MERIAM E. LEYCANO-QUIJANO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <p>• IDs will be printed in Long Bond Paper (8.5" x 13") landscape</p>
                                    <p>• 9 IDs per page (Front: ID Info, Back: Benefits)</p>
                                    <p>• Exact format matching the reference document</p>
                                    <p>• No photo area (matches document format)</p>
                                    <p>• Date Issued will use the registration date from database</p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button id="preview-ids-btn"
                                        class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-blue-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                        </svg>
                                        Preview IDs
                                    </button>
                                    <button id="generate-pdf-btn"
                                        class="px-5 py-2.5 bg-green-700 hover:bg-green-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-green-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                                        </svg>
                                        Generate PDF
                                    </button>
                                    <button id="print-ids-btn"
                                        class="px-5 py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-purple-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                        </svg>
                                        Print IDs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Preview -->
                    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-center">ID Format Preview</h3>

                        <div class="border-2 border-blue-800 rounded-lg p-3 bg-gradient-to-br from-blue-50 to-gray-50 dark:from-gray-800 dark:to-gray-900">
                            <!-- Republic of the Philippines Header -->
                            <div class="text-center mb-2">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Republic of the Philippines</h4>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Office for Senior Citizens Affairs (OSCA)</h4>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Paluan, Occidental Mindoro</h4>
                            </div>

                            <!-- ID Info -->
                            <div class="space-y-1">
                                <div>
                                    <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Name:</label>
                                    <div class="text-[9px] font-bold text-gray-900 dark:text-white truncate">DANILO T. DUEŃAS</div>
                                </div>
                                <div>
                                    <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Address:</label>
                                    <div class="text-[9px] text-gray-900 dark:text-white truncate">Brgy. V - BAGONG SILANG</div>
                                </div>
                                <div class="grid grid-cols-3 gap-1 mt-2">
                                    <div>
                                        <div class="text-[6px] text-gray-700 dark:text-gray-300">Date of Birth</div>
                                        <div class="text-[7px] font-medium text-gray-900 dark:text-white">01/31/1961</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-[6px] text-gray-700 dark:text-gray-300">Sex</div>
                                        <div class="text-[7px] font-medium text-gray-900 dark:text-white">M</div>
                                    </div>
                                    <div>
                                        <div class="text-[6px] text-gray-700 dark:text-gray-300">Date Issued</div>
                                        <div class="text-[7px] font-medium text-gray-900 dark:text-white">02/01/2021</div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <div class="text-[6px] text-gray-700 dark:text-gray-300">Signature / Thumbmark</div>
                                    <div class="h-3 border-b border-gray-300 dark:border-gray-600 mt-1"></div>
                                </div>
                                <div class="text-center mt-1">
                                    <div class="text-[7px] font-medium text-gray-900 dark:text-white">I.D No. <span class="font-bold">011035</span></div>
                                </div>
                            </div>

                            <!-- Non-Transferable Notice -->
                            <div class="text-center mt-3">
                                <div class="text-[6px] font-bold text-red-600 dark:text-red-400">THIS CARD IS NON-TRANSFERABLE</div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Back side contains:</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Benefits and Privileges under RA 9994</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">With signatories</div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Printing Specifications:</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Long Bond Paper (8.5" x 13")</li>
                                <li>• Landscape Orientation</li>
                                <li>• 9 IDs per page (3x3 grid)</li>
                                <li>• Exact document format (no photos)</li>
                                <li>• Front: ID Info, Back: Benefits</li>
                                <li>• Uses database registration date</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Print Preview Modal -->
            <div id="print-preview-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 bg-opacity-50">
                <div class="relative min-h-screen flex items-center justify-center p-4">
                    <div class="relative w-full max-w-6xl mx-auto bg-white rounded-lg shadow-lg">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-xl font-bold text-gray-900">ID Cards Print Preview</h3>
                            <button id="close-preview-btn" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Preview Content - Improved Container -->
                        <div class="p-4 overflow-x-auto">
                            <div id="preview-content" class="flex flex-col items-center justify-center min-w-max">
                                <!-- Preview will be generated here -->
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex flex-col sm:flex-row justify-between items-center p-4 border-t gap-4">
                            <div class="text-sm text-gray-600">
                                Showing: <span id="preview-count">0</span> IDs | Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                            </div>
                            <div class="flex space-x-2">
                                <button id="prev-page-btn" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Previous
                                </button>
                                <button id="next-page-btn" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Next
                                </button>
                                <button id="print-preview-btn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>

    <script>
        // Global variables
        let selectedSeniors = new Map();
        let currentPreviewPage = 1;
        let totalPreviewPages = 1;
        let allPreviewPages = [];

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners for buttons
            document.getElementById('select-all-btn').addEventListener('click', selectAll);
            document.getElementById('deselect-all-btn').addEventListener('click', deselectAll);
            document.getElementById('master-checkbox').addEventListener('change', toggleMasterCheckbox);
            document.getElementById('preview-ids-btn').addEventListener('click', previewIDs);
            document.getElementById('generate-pdf-btn').addEventListener('click', generatePDF);
            document.getElementById('print-ids-btn').addEventListener('click', printIDs);
            document.getElementById('close-preview-btn').addEventListener('click', closePreview);
            document.getElementById('print-preview-btn').addEventListener('click', printPreview);
            document.getElementById('prev-page-btn').addEventListener('click', prevPage);
            document.getElementById('next-page-btn').addEventListener('click', nextPage);

            // Search and filter event listeners
            document.getElementById('search-senior').addEventListener('input', filterTable);
            document.getElementById('filter-barangay').addEventListener('change', filterTable);
            document.getElementById('filter-validation').addEventListener('change', filterTable);

            // Add event listeners to checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('senior-checkbox')) {
                    const checkbox = e.target;
                    const id = checkbox.dataset.id;
                    const name = checkbox.dataset.name;

                    if (checkbox.checked) {
                        selectedSeniors.set(id, {
                            name: name,
                            birthdate: checkbox.dataset.birthdate,
                            age: checkbox.dataset.age,
                            gender: checkbox.dataset.gender,
                            barangay: checkbox.dataset.barangay,
                            idNumber: checkbox.dataset.idNumber,
                            dateIssued: checkbox.dataset.dateIssued, // This is date_of_registration from database
                            localControl: checkbox.dataset.localControl
                        });
                    } else {
                        selectedSeniors.delete(id);
                    }

                    updateSelectedList();
                    updateMasterCheckbox();
                }
            });

            // Update selected list initially
            updateSelectedList();
        });

        // Filter table based on search and filters
        function filterTable() {
            const search = document.getElementById('search-senior').value.toLowerCase();
            const barangay = document.getElementById('filter-barangay').value;
            const validation = document.getElementById('filter-validation').value;

            const rows = document.querySelectorAll('.senior-row');

            rows.forEach(row => {
                const name = row.querySelector('.senior-checkbox').dataset.name.toLowerCase();
                const rowBarangay = row.dataset.barangay;
                const rowValidation = row.dataset.validation;

                let show = true;

                // Apply search filter
                if (search && !name.includes(search)) {
                    show = false;
                }

                // Apply barangay filter
                if (barangay !== 'all' && rowBarangay !== barangay) {
                    show = false;
                }

                // Apply validation filter
                if (validation !== 'all' && rowValidation !== validation) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        // Update selected list display
        function updateSelectedList() {
            const listContainer = document.getElementById('selected-list');
            const countElement = document.getElementById('selected-count');

            countElement.textContent = `${selectedSeniors.size} selected`;

            if (selectedSeniors.size === 0) {
                listContainer.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>';
                return;
            }

            let html = '<div class="space-y-1 max-h-32 overflow-y-auto">';
            selectedSeniors.forEach((senior, id) => {
                html += `
                    <div class="flex justify-between items-center text-sm p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                        <span class="truncate">${senior.name}</span>
                        <button class="text-red-500 hover:text-red-700 ml-2" onclick="removeSelected('${id}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            listContainer.innerHTML = html;
        }

        // Remove selected senior
        function removeSelected(id) {
            selectedSeniors.delete(id);

            // Uncheck in table
            const checkbox = document.querySelector(`.senior-checkbox[data-id="${id}"]`);
            if (checkbox) checkbox.checked = false;

            updateSelectedList();
            updateMasterCheckbox();
        }

        // Select all seniors on current page
        function selectAll() {
            const checkboxes = document.querySelectorAll('.senior-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = true;
                    const id = checkbox.dataset.id;
                    if (!selectedSeniors.has(id)) {
                        selectedSeniors.set(id, {
                            name: checkbox.dataset.name,
                            birthdate: checkbox.dataset.birthdate,
                            age: checkbox.dataset.age,
                            gender: checkbox.dataset.gender,
                            barangay: checkbox.dataset.barangay,
                            idNumber: checkbox.dataset.idNumber,
                            dateIssued: checkbox.dataset.dateIssued, // This is date_of_registration from database
                            localControl: checkbox.dataset.localControl
                        });
                    }
                }
            });

            updateSelectedList();
            updateMasterCheckbox();
        }

        // Deselect all seniors
        function deselectAll() {
            selectedSeniors.clear();
            document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedList();
            updateMasterCheckbox();
        }

        // Toggle master checkbox
        function toggleMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');

            if (masterCheckbox.checked) {
                selectAll();
            } else {
                deselectAll();
            }
        }

        // Update master checkbox state
        function updateMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');
            const checkboxes = document.querySelectorAll('.senior-checkbox:not([style*="display: none"])');
            const checkedCount = document.querySelectorAll('.senior-checkbox:checked').length;

            if (checkedCount === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            }
        }

        // Preview IDs
        function previewIDs() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Convert Map to Array
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            // Generate all preview pages
            allPreviewPages = generateAllPreviewPages(seniorsArray);
            totalPreviewPages = allPreviewPages.length;
            currentPreviewPage = 1;

            // Display first page
            displayPreviewPage();

            // Update preview info
            document.getElementById('preview-count').textContent = seniorsArray.length;
            updatePageNavigation();

            // Show modal
            document.getElementById('print-preview-modal').classList.remove('hidden');
        }

        // Generate all preview pages (front and back)
        function generateAllPreviewPages(seniorsArray) {
            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const pages = [];

            // Group seniors into pages of 9 IDs each
            for (let i = 0; i < seniorsArray.length; i += 9) {
                const pageSeniors = seniorsArray.slice(i, i + 9);

                // Create front page (ID cards)
                pages.push(generateFrontPage(pageSeniors));

                // Create back page (benefits) - exactly 9 IDs per back page
                pages.push(generateBackPage(oscaHead, municipalMayor));
            }

            return pages;
        }

        // Generate front page with ID cards
        function generateFrontPage(seniors) {
            let html = `
                <div class="print-page id-front-page" style="width: 13in; height: 8.5in;">
                    <div class="print-grid">
            `;

            seniors.forEach(senior => {
                // Format birth date
                const dob = senior.birthdate ? formatDate(senior.birthdate) : 'N/A';

                // Use date_of_registration from database
                const dateIssued = senior.dateIssued ? formatDate(senior.dateIssued) : 'N/A';

                const idNumber = senior.idNumber && senior.idNumber !== 'N/A' ? senior.idNumber :
                    senior.localControl ? senior.localControl :
                    'PALUAN-' + senior.id.substring(0, 6);

                const genderCode = senior.gender === 'Male' ? 'M' : senior.gender === 'Female' ? 'F' : '';

                html += `
                    <div class="id-card">
                        <!-- Republic Header -->
                        <div class="id-header" style="font-size: 6pt; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                            <img src="../img/MSWD_LOGO-removebg-preview.png" alt="PH Seal" class="w-10 h-10 rounded-full vertical-align-middle">
                            <div >
                            <div style="font-size: 7.5pt;">Republic of the Philippines</div>
                            <div style="font-size: 7.5pt;">Office for Senior Citizens Affairs (OSCA)</div>
                            <div style="font-size: 7.5pt;">Paluan, Occidental Mindoro</div>
                            </div>
                            <img src="../img/paluan.png" alt="Mindoro Seal" class="w-10 h-10 rounded-full vertical-align-middle">
                        </div>
                        
                        <!-- ID Content -->
                        <div class="id-content" style="font-size: 8pt;">
                            <div style="margin-bottom: 1px; text-decoration: underline;" class="flex flex-row gap-1">
                                <div style="font-weight: bold; font-size: 8pt;">Name:</div>
                                <div class="id-name" style="font-size: 8pt; font-weight: bold; margin-left: 5px;">${senior.name}</div>
                            </div>
                            <div class="flex flex-row justify-between align-middle mt-1">
                                <div class="flex flex-col  text-align: left;">
                                    <div style="margin-bottom: 2px; text-decoration: underline;" class="flex flex-row ">
                                        <div style="font-weight: bold; font-size: 8pt;" >Address:</div>
                                        <div class="id-address" style="font-size: 8pt; font-weight: bold; margin-left: 5px;">Brgy. ${senior.barangay || ''}</div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;">
                                        <div>
                                            <div style="font-size: 8pt; font-weight: bold;">Date of Birth</div>
                                            <div style="font-size: 8pt; font-weight: bold; font-weight: bold; text-decoration: underline;">${dob}</div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 8pt; font-weight: bold;">Sex</div>
                                            <div style="font-size: 8pt; font-weight: bold; font-weight: bold; text-decoration: underline;">${genderCode}</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 8pt; font-weight: bold;">Date Issued</div>
                                            <div style="font-size: 8pt; font-weight: bold; font-weight: bold; text-decoration: underline;">${dateIssued}</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="height:1in; width:1in; border: 1px solid #000;"></div>
                            </div>
                            <div class="flex flex-row justify-between align-middle">
                                <div style="text-align: left;">
                                    <div style="border-bottom: 1px solid #000; width: 100%;" class="left-0"></div>
                                    <div style="font-size: 8pt; font-weight: bold;">Signature / Thumbmark</div>
                                </div>
                                <div style="text-align: right; margin-right:0.5in;">
                                    <div style="font-size: 7pt; font-weight: bold;">
                                        I.D No. <span style="font-weight: bold;">${idNumber}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Non-Transferable Notice -->
                        <div class="id-footer" style="font-size: 7pt; font-weight: bold; color: red; text-align: center;">
                            THIS CARD IS NON-TRANSFERABLE
                        </div>
                    </div>
                `;
            });

            // Fill empty spots with blank ID cards (to maintain 9 per page)
            for (let i = seniors.length; i < 9; i++) {
                html += '<div class="id-card"></div>';
            }

            html += `
                    </div>
                </div>
            `;

            return html;
        }

        // Generate back page with benefits (one back page for each 9 IDs)
        function generateBackPage(oscaHead, municipalMayor) {
            return `
                <div class="print-page benefits-page">
                    <div class="benefits-header" style="font-size: 10pt;">
                        <div>Benefits and Privileges under Republic Act No. 9994</div>
                    </div>
                    
                    <div class="benefits-list">
                        <div style="margin-bottom: 2px;">• Free medical/dental diagnostic & laboratory fees in all government facilities.</div>
                        <div style="margin-bottom: 2px;">• 20% discount in purchase medicines</div>
                        <div style="margin-bottom: 2px;">• 20% discount in Hotels, Restaurant, and Recreation Centers & Funeral Parlors.</div>
                        <div style="margin-bottom: 2px;">• 20% discount on theatres, cinema houses and concert halls, etc.</div>
                        <div style="margin-bottom: 2px;">• 20% discount in medical/ dental services, diagnostic & laboratory fees in private facilities.</div>
                        <div style="margin-bottom: 2px;">• 20% discount in fare for domestic air, sea travel and public land transportation</div>
                        <div style="margin-bottom: 2px;">• 5% discount in basic necessities and prime commodities</div>
                        <div style="margin-bottom: 2px;">• 12% VAT- exemption on the purchase of goods & service which are entitled to the 20% discount</div>
                        <div style="margin-bottom: 2px;">• 5% discount monthly utilization of water/electricity provided that the water and electricity meter bases are under the name of senior citizens</div>
                        
                        <div style="margin-top: 10px; font-style: italic;">
                            Persons and Corporations violating RA 9994 shall be penalized. Only for the exclusive use of Senior Citizens; abuse of privileges is punishable by law.
                        </div>
                    </div>
                    
                    <div class="benefits-footer">
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <div style="text-align: center;">
                                <div style="font-weight: bold; font-size: 9pt;">${oscaHead}</div>
                                <div class="signature-line"></div>
                                <div style="font-size: 8pt;">OSCA HEAD</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-weight: bold; font-size: 9pt;">${municipalMayor}</div>
                                <div class="signature-line"></div>
                                <div style="font-size: 8pt;">Municipal Mayor</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Format date to MM/DD/YYYY
        function formatDate(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';

            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'N/A';

                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const year = date.getFullYear();

                return `${month}/${day}/${year}`;
            } catch (e) {
                return 'N/A';
            }
        }

        // Display current preview page
        function displayPreviewPage() {
            const previewContent = document.getElementById('preview-content');
            if (allPreviewPages[currentPreviewPage - 1]) {
                // Create a centered container for the preview
                const container = document.createElement('div');
                container.className = 'flex flex-col items-center justify-center w-full';
                container.innerHTML = allPreviewPages[currentPreviewPage - 1];

                // Clear and add centered content
                previewContent.innerHTML = '';
                previewContent.appendChild(container);
            }
        }

        // Update page navigation
        function updatePageNavigation() {
            document.getElementById('current-page').textContent = currentPreviewPage;
            document.getElementById('total-pages').textContent = totalPreviewPages;

            // Show/hide navigation buttons
            document.getElementById('prev-page-btn').style.display = currentPreviewPage > 1 ? 'block' : 'none';
            document.getElementById('next-page-btn').style.display = currentPreviewPage < totalPreviewPages ? 'block' : 'none';
        }

        // Previous page
        function prevPage() {
            if (currentPreviewPage > 1) {
                currentPreviewPage--;
                displayPreviewPage();
                updatePageNavigation();
            }
        }

        // Next page
        function nextPage() {
            if (currentPreviewPage < totalPreviewPages) {
                currentPreviewPage++;
                displayPreviewPage();
                updatePageNavigation();
            }
        }

        // Generate PDF
        function generatePDF() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Prepare data for PDF generation
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;

            // Create form and submit to PHP script
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../php/generate_id_pdf.php';
            form.style.display = 'none';

            const seniorsInput = document.createElement('input');
            seniorsInput.type = 'hidden';
            seniorsInput.name = 'seniors';
            seniorsInput.value = JSON.stringify(seniorsArray);
            form.appendChild(seniorsInput);

            const oscaHeadInput = document.createElement('input');
            oscaHeadInput.type = 'hidden';
            oscaHeadInput.name = 'osca_head';
            oscaHeadInput.value = oscaHead;
            form.appendChild(oscaHeadInput);

            const mayorInput = document.createElement('input');
            mayorInput.type = 'hidden';
            mayorInput.name = 'municipal_mayor';
            mayorInput.value = municipalMayor;
            form.appendChild(mayorInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // Print IDs (direct print from browser)
        function printIDs() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // First show preview, then trigger print
            previewIDs();

            // After a short delay, trigger print
            setTimeout(() => {
                document.getElementById('print-preview-btn').click();
            }, 500);
        }

        // Close preview modal
        function closePreview() {
            document.getElementById('print-preview-modal').classList.add('hidden');
        }

        // Print preview
        function printPreview() {
            const printWindow = window.open('', '_blank');
            printWindow.document.title = 'Senior Citizen IDs - Print';

            let printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Senior Citizen IDs - Print</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        @page {
                            size: landscape;
                            margin: 0;
                        }
                        
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        
                        .print-page {
                            width: 13in;
                            height: 8.5in;
                            page-break-after: always;
                            position: relative;
                            overflow: hidden;
                        }
                        
                        .id-front-page {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                        }
                        
                        .print-grid {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            grid-template-rows: repeat(3, 1fr);
                            gap: 0.1in;
                            width: 13in;
                            height: 8.5in;
                            padding: 0.2in;
                            box-sizing: border-box;
                        }
                        
                        .id-card {
                            width: 3.33in;
                            height: 2.13in;
                            border: 1px solid #000;
                            padding: 2px;
                            background: white;
                            position: relative;
                            overflow: hidden;
                            box-sizing: border-box;
                            font-size: 6pt;
                        }
                        
                        .id-header {
                            text-align: center;
                            font-weight: bold;
                            margin-bottom: 1px;
                            line-height: 1;
                            font-size: 6pt;
                        }
                        
                        .id-content {
                            padding: 1px;
                        }
                        
                        .id-name {
                            font-weight: bold;
                            text-transform: uppercase;
                            font-size: 7pt;
                        }
                        
                        .id-address {
                            text-transform: uppercase;
                            font-size: 7pt;
                        }
                        
                        .id-footer {
                            text-align: center;
                            font-weight: bold;
                            color: #dc2626;
                            margin-top: 2px;
                            font-size: 5pt;
                        }
                        
                        .benefits-page {
                            width: 13in;
                            height: 8.5in;
                            padding: 0.3in;
                            page-break-after: always;
                            font-size: 8pt;
                            box-sizing: border-box;
                        }
                        
                        .benefits-header {
                            text-align: center;
                            font-weight: bold;
                            margin-bottom: 0.1in;
                            font-size: 10pt;
                        }
                        
                        .benefits-list {
                            line-height: 1.2;
                        }
                        
                        .benefits-footer {
                            margin-top: 0.2in;
                            text-align: center;
                        }
                        
                        .signature-line {
                            border-top: 1px solid #000;
                            width: 2in;
                            display: inline-block;
                            margin-top: 0.1in;
                        }
                        
                        @media print {
                            body {
                                margin: 0 !important;
                                padding: 0 !important;
                            }
                            
                            .print-page {
                                width: 13in !important;
                                height: 8.5in !important;
                                page-break-after: always !important;
                            }
                        }
                    </style>
                </head>
                <body>
            `;

            // Add all pages
            allPreviewPages.forEach(page => {
                printHTML += page;
            });

            printHTML += `
                </body>
                </html>
            `;

            printWindow.document.write(printHTML);
            printWindow.document.close();
            printWindow.focus();

            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
                closePreview();
            }, 1000);
        }
    </script>
</body>

</html>