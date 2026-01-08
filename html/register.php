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
require_once "../php/login/admin_header.php";
// Fix session handling for admin
if (isset($_GET['session_context']) && !empty($_GET['session_context'])) {
    $ctx = $_GET['session_context'];

    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'admin';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Senior Citizen</title>
    <link rel="stylesheet" href="../css/popup.css">
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <style>
        .iti {
            width: 100%;
        }

        .iti__flag {
            background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags.png");
        }

        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            .iti__flag {
                background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags@2x.png");
            }
        }

        .error-border {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        .success-border {
            border-color: #10b981 !important;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
            border: 2px solid #d1d5db;
        }

        .step-circle.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .step-circle.completed {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }

        .step-line {
            flex-grow: 1;
            height: 2px;
            background-color: #d1d5db;
            margin: 0 10px;
        }

        .step-label {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
        }

        .step-label.active {
            color: #3b82f6;
        }

        .step-label.completed {
            color: #10b981;
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
                    <a href="#" class="flex items-center justify-between mr-4 ">
                        <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
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
                        <a href="admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
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
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <svg class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
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
                            class="flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
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
                        <a href="generate_id.php?session_context=<?php echo $ctx; ?>"
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

        <!-- Main Content -->
        <main class="p-4 md:ml-64 h-auto pt-20">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Senior Citizen Registration</h1>
                            <p class="text-gray-600 dark:text-gray-300 mt-1">Fill out the application form below</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                Admin Panel
                            </span>
                        </div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="step-indicator mb-8">
                        <div class="step-circle active">1</div>
                        <div class="step-label active">Basic Information</div>
                        <div class="step-line"></div>
                        <div class="step-circle">2</div>
                        <div class="step-label">Contact & Address</div>
                        <div class="step-line"></div>
                        <div class="step-circle">3</div>
                        <div class="step-label">Economic Status</div>
                        <div class="step-line"></div>
                        <div class="step-circle">4</div>
                        <div class="step-label">Health & Submit</div>
                    </div>

                    <form id="applicantForm" class="space-y-8">
                        <!-- Step 1: Basic Information -->
                        <div id="step1" class="form-step active">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Personal Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label for="lname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Last Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="lname" name="lname" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter last name">
                                    </div>
                                    <div>
                                        <label for="fname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            First Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="fname" name="fname" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter first name">
                                    </div>
                                    <div>
                                        <label for="mname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Middle Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="mname" name="mname" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter middle name">
                                    </div>
                                    <div>
                                        <label for="suffix" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Suffix
                                        </label>
                                        <input type="text" id="suffix" name="suffix"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Jr., Sr., III, etc.">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label for="gender" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select id="gender" name="gender" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="b_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Birthdate <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="b_date" name="b_date" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div>
                                        <label for="age" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Age <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="age" name="age" required readonly
                                            class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="civil_status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Civil Status <span class="text-red-500">*</span>
                                        </label>
                                        <select id="civil_status" name="civil_status" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Status</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Separated">Separated</option>
                                            <option value="Divorced">Divorced</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="citizenship" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Citizenship <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="citizenship" name="citizenship" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Filipino">
                                    </div>
                                    <div>
                                        <label for="religion" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Religion <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="religion" name="religion" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Roman Catholic">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="birth_place" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Birthplace <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="birth_place" name="birth_place" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="City, Province">
                                    </div>
                                    <div>
                                        <label for="educational_attainment" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Educational Attainment <span class="text-red-500">*</span>
                                        </label>
                                        <select id="educational_attainment" name="educational_attainment" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Level</option>
                                            <option value="No Formal Education">No Formal Education</option>
                                            <option value="Elementary Level">Elementary Level</option>
                                            <option value="Elementary Graduate">Elementary Graduate</option>
                                            <option value="High School Level">High School Level</option>
                                            <option value="High School Graduate">High School Graduate</option>
                                            <option value="College Level">College Level</option>
                                            <option value="College Graduate">College Graduate</option>
                                            <option value="Post Graduate">Post Graduate</option>
                                            <option value="Vocational">Vocational</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="button" onclick="nextStep(2)"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Contact & Address
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Contact & Address -->
                        <div id="step2" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Contact Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Contact Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" id="contact_number" name="contact_number" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter phone number">
                                        <p class="mt-1 text-xs text-gray-500">Format: +639XXXXXXXXX or 09XXXXXXXXX</p>
                                        <div id="contact_number_error" class="mt-1 text-xs text-red-600 hidden"></div>
                                    </div>
                                    <div>
                                        <label for="ip_group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            IP Group
                                        </label>
                                        <input type="text" id="ip_group" name="ip_group"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Indigenous People Group (if applicable)">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Address Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="house_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            House Number
                                        </label>
                                        <input type="text" id="house_no" name="house_no"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., 123">
                                    </div>
                                    <div>
                                        <label for="street" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Street
                                        </label>
                                        <input type="text" id="street" name="street"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Main Street">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="brgy" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Barangay <span class="text-red-500">*</span>
                                        </label>
                                        <select id="brgy" name="brgy" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Barangay</option>
                                            <option value="I - Mapalad">I - Mapalad</option>
                                            <option value="II - Handang Tumulong">II - Handang Tumulong</option>
                                            <option value="III - Silahis ng Pag-asa">III - Silahis ng Pag-asa</option>
                                            <option value="IV - Pag-asa ng Bayan">IV - Pag-asa ng Bayan</option>
                                            <option value="V - Bagong Silang">V - Bagong Silang</option>
                                            <option value="VI - San Jose">VI - San Jose</option>
                                            <option value="VII - Lumang Bayan">VII - Lumang Bayan</option>
                                            <option value="VIII - Marikit">VIII - Marikit</option>
                                            <option value="IX - Tubili">IX - Tubili</option>
                                            <option value="X - Alipaoy">X - Alipaoy</option>
                                            <option value="XI - Harison">XI - Harison</option>
                                            <option value="XII - Mananao">XII - Mananao</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="municipality" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Municipality <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="municipality" name="municipality" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            value="Paluan" readonly>
                                    </div>
                                    <div>
                                        <label for="province" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Province <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="province" name="province" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            value="Occidental Mindoro" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="living_arrangement" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Living Arrangement <span class="text-red-500">*</span>
                                </label>
                                <select id="living_arrangement" name="living_arrangement" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <option value="">Select Arrangement</option>
                                    <option value="Owned">Owned</option>
                                    <option value="Living alone">Living alone</option>
                                    <option value="Living with relatives">Living with relatives</option>
                                    <option value="Rent">Rent</option>
                                </select>
                            </div>

                            <div class="flex justify-between">
                                <button type="button" onclick="prevStep(1)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(3)"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Economic Status
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Economic Status -->
                        <div id="step3" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Economic Status</h3>

                                <!-- Pensioner Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Pension Status</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Are you a pensioner?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="is_pensioner_yes" type="radio" value="1" name="is_pensioner"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="is_pensioner_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="is_pensioner_no" type="radio" value="0" name="is_pensioner" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="is_pensioner_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="pension_amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Monthly Pension Amount
                                            </label>
                                            <input type="number" id="pension_amount" name="pension_amount" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="0.00">
                                        </div>
                                        <div>
                                            <label for="pension_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Pension Source
                                            </label>
                                            <select id="pension_source" name="pension_source" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <option value="">Select Source</option>
                                                <option value="GSIS">GSIS</option>
                                                <option value="SSS">SSS</option>
                                                <option value="AFPSLAI">AFPSLAI</option>
                                                <option value="Private">Private</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Income Source Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Income Source</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Permanent Source of Income?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_permanent_income_yes" type="radio" value="1" name="has_permanent_income"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_permanent_income_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_permanent_income_no" type="radio" value="0" name="has_permanent_income" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_permanent_income_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="income_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Source of Income
                                            </label>
                                            <input type="text" id="income_source" name="income_source" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., Business, Farming, etc.">
                                        </div>
                                    </div>
                                </div>

                                <!-- Family Support Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Family Support</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Regular Family Support?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_family_support_yes" type="radio" value="1" name="has_family_support"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_family_support_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_family_support_no" type="radio" value="0" name="has_family_support" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_family_support_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="support_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Type of Support
                                            </label>
                                            <input type="text" id="support_type" name="support_type" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., Cash, In-kind">
                                        </div>
                                        <div>
                                            <label for="support_cash" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Amount/Frequency
                                            </label>
                                            <input type="text" id="support_cash" name="support_cash" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., ₱5,000/month">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between">
                                <button type="button" onclick="prevStep(2)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(4)"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Health & Submit
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Health & Submit -->
                        <div id="step4" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Health Condition</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="mb-4">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Existing Illness?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_existing_illness_yes" type="radio" value="1" name="has_existing_illness"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_existing_illness_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_existing_illness_no" type="radio" value="0" name="has_existing_illness" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_existing_illness_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="illness_details" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Illness Details
                                            </label>
                                            <input type="text" id="illness_details" name="illness_details" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="Specify illness if any">
                                        </div>
                                    </div>

                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="mb-4">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Hospitalized in last 6 months?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="hospitalized_last6mos_yes" type="radio" value="1" name="hospitalized_last6mos"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="hospitalized_last6mos_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="hospitalized_last6mos_no" type="radio" value="0" name="hospitalized_last6mos" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="hospitalized_last6mos_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Details -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Registration Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="date_of_registration" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Date of Registration <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="date_of_registration" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div>
                                        <label for="id_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            ID Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="id_number" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., SC-2024-001">
                                        <p class="mt-1 text-xs text-gray-500">Unique identifier for the senior</p>
                                    </div>
                                    <div>
                                        <label for="local_control_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Local Control Number
                                        </label>
                                        <div class="flex">
                                            <input type="text" id="local_control_number" readonly
                                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                                value="Auto-generated">
                                            <button type="button" onclick="generateCustomLocalControlNumber()"
                                                class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 font-medium rounded-r-lg text-sm px-4 py-2.5 dark:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                                Custom
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Internal reference number</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between">
                                <button type="button" onclick="prevStep(3)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="submitForm()"
                                    class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                                    Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Popup Modal -->
    <div id="popupModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex z-50 items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out"
            id="popupBox">
            <h2 id="popupTitle" class="text-xl font-semibold mb-3 text-gray-800"></h2>
            <p id="popupMessage" class="text-gray-600 mb-6 leading-relaxed"></p>
            <button id="popupCloseBtn"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">
                OK
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

    <script>
        // Initialize theme
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

        initTheme();

        // Phone number input with intl-tel-input
        let phoneInput;
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize phone input
            const phoneInputElement = document.querySelector("#contact_number");
            if (phoneInputElement) {
                phoneInput = window.intlTelInput(phoneInputElement, {
                    initialCountry: "ph",
                    separateDialCode: true,
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                });

                // Add validation
                phoneInputElement.addEventListener('blur', validatePhoneNumber);
                phoneInputElement.addEventListener('input', function() {
                    this.classList.remove('error-border', 'success-border');
                    document.getElementById('contact_number_error').classList.add('hidden');
                });
            }

            // Age calculation
            const birthDateInput = document.querySelector('#b_date');
            const ageInput = document.querySelector('#age');

            if (birthDateInput && ageInput) {
                birthDateInput.addEventListener('change', calculateAge);
                calculateAge(); // Calculate on load if date is already set
            }

            // Conditional field toggling
            setupConditionalFields();
        });

        function calculateAge() {
            const birthDateInput = document.querySelector('#b_date');
            const ageInput = document.querySelector('#age');

            if (!birthDateInput.value) {
                ageInput.value = '';
                return;
            }

            const birthDate = new Date(birthDateInput.value);
            const today = new Date();

            if (isNaN(birthDate.getTime())) {
                ageInput.value = '';
                return;
            }

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            ageInput.value = age >= 0 ? age : '';
        }

        function validatePhoneNumber() {
            const phoneElement = document.querySelector("#contact_number");
            const errorElement = document.getElementById('contact_number_error');

            if (!phoneInput || !phoneElement) return true;

            const phoneNumber = phoneElement.value.trim();

            if (!phoneNumber) {
                phoneElement.classList.add('error-border');
                errorElement.textContent = "Contact number is required";
                errorElement.classList.remove('hidden');
                return false;
            }

            // Basic validation for Philippine numbers
            const phPattern = /^(09|\+639)\d{9}$/;
            const cleanedNumber = phoneNumber.replace(/\D/g, '');

            if (!phPattern.test(phoneNumber) && cleanedNumber.length !== 11 && cleanedNumber.length !== 12) {
                phoneElement.classList.add('error-border');
                errorElement.textContent = "Please enter a valid Philippine phone number (09XXXXXXXXX or +639XXXXXXXXX)";
                errorElement.classList.remove('hidden');
                return false;
            }

            if (phoneInput.isValidNumber()) {
                phoneElement.classList.remove('error-border');
                phoneElement.classList.add('success-border');
                errorElement.classList.add('hidden');
                return true;
            } else {
                phoneElement.classList.add('error-border');
                errorElement.textContent = "Please enter a valid phone number";
                errorElement.classList.remove('hidden');
                return false;
            }
        }

        function setupConditionalFields() {
            // Pensioner fields
            const pensionerRadios = document.querySelectorAll('input[name="is_pensioner"]');
            const pensionAmount = document.querySelector('#pension_amount');
            const pensionSource = document.querySelector('#pension_source');

            pensionerRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const isPensioner = this.value === '1';
                    pensionAmount.disabled = !isPensioner;
                    pensionSource.disabled = !isPensioner;

                    if (!isPensioner) {
                        pensionAmount.value = '';
                        pensionSource.value = '';
                    }
                });
            });

            // Permanent Income fields
            const incomeRadios = document.querySelectorAll('input[name="has_permanent_income"]');
            const incomeSource = document.querySelector('#income_source');

            incomeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIncome = this.value === '1';
                    incomeSource.disabled = !hasIncome;

                    if (!hasIncome) {
                        incomeSource.value = '';
                    }
                });
            });

            // Family Support fields
            const supportRadios = document.querySelectorAll('input[name="has_family_support"]');
            const supportType = document.querySelector('#support_type');
            const supportCash = document.querySelector('#support_cash');

            supportRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasSupport = this.value === '1';
                    supportType.disabled = !hasSupport;
                    supportCash.disabled = !hasSupport;

                    if (!hasSupport) {
                        supportType.value = '';
                        supportCash.value = '';
                    }
                });
            });

            // Illness fields
            const illnessRadios = document.querySelectorAll('input[name="has_existing_illness"]');
            const illnessDetails = document.querySelector('#illness_details');

            illnessRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIllness = this.value === '1';
                    illnessDetails.disabled = !hasIllness;

                    if (!hasIllness) {
                        illnessDetails.value = '';
                    }
                });
            });
        }

        function nextStep(step) {
            // Validate current step
            if (!validateStep(step - 1)) {
                return;
            }

            // Hide current step
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });

            // Show next step
            document.getElementById(`step${step}`).classList.add('active');

            // Update step indicators
            updateStepIndicators(step);
        }

        function prevStep(step) {
            // Hide current step
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });

            // Show previous step
            document.getElementById(`step${step}`).classList.add('active');

            // Update step indicators
            updateStepIndicators(step);
        }

        function updateStepIndicators(currentStep) {
            const steps = document.querySelectorAll('.step-circle');
            const labels = document.querySelectorAll('.step-label');

            steps.forEach((circle, index) => {
                const stepNumber = index + 1;
                if (stepNumber < currentStep) {
                    circle.classList.remove('active');
                    circle.classList.add('completed');
                    labels[index].classList.remove('active');
                    labels[index].classList.add('completed');
                } else if (stepNumber === currentStep) {
                    circle.classList.add('active');
                    circle.classList.remove('completed');
                    labels[index].classList.add('active');
                    labels[index].classList.remove('completed');
                } else {
                    circle.classList.remove('active', 'completed');
                    labels[index].classList.remove('active', 'completed');
                }
            });
        }

        function validateStep(stepNumber) {
            const step = document.getElementById(`step${stepNumber}`);
            let isValid = true;
            let firstError = null;

            // Get all required inputs in this step
            const requiredInputs = step.querySelectorAll('[required]');

            requiredInputs.forEach(input => {
                // Skip disabled inputs
                if (input.disabled) return;

                const value = input.value.trim();
                const isEmpty = value === '';

                // Special validation for contact number
                if (input.id === 'contact_number') {
                    if (!validatePhoneNumber()) {
                        isValid = false;
                        if (!firstError) firstError = input;
                        input.classList.add('error-border');
                    } else {
                        input.classList.remove('error-border');
                    }
                } else if (isEmpty) {
                    isValid = false;
                    if (!firstError) firstError = input;
                    input.classList.add('error-border');
                } else {
                    input.classList.remove('error-border');
                }
            });

            // Scroll to first error if any
            if (!isValid && firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstError.focus();

                // Show error message
                showPopup('Please fill in all required fields marked with *', 'error');
            }

            return isValid;
        }

        function generateCustomLocalControlNumber() {
            const customNumber = prompt("Enter custom local control number:", "");
            const lcnInput = document.getElementById('local_control_number');

            if (customNumber && customNumber.trim() !== "") {
                lcnInput.value = customNumber.trim();
                lcnInput.readOnly = false;
                lcnInput.classList.remove('bg-gray-100');
                lcnInput.classList.add('bg-yellow-50', 'border-yellow-300');
            }
        }

        function showPopup(message, type = "info") {
            const modal = document.getElementById("popupModal");
            const box = document.getElementById("popupBox");
            const title = document.getElementById("popupTitle");
            const msg = document.getElementById("popupMessage");
            const closeBtn = document.getElementById("popupCloseBtn");

            // Set message and styles
            msg.textContent = message;

            if (type === "success") {
                title.textContent = "✅ Success";
                title.className = "text-xl font-semibold mb-3 text-green-600";
                closeBtn.className = "px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all focus:outline-none focus:ring-2 focus:ring-green-400";
            } else if (type === "error") {
                title.textContent = "⚠️ Error";
                title.className = "text-xl font-semibold mb-3 text-red-600";
                closeBtn.className = "px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-all focus:outline-none focus:ring-2 focus:ring-red-400";
            } else if (type === "warning") {
                title.textContent = "⚠️ Warning";
                title.className = "text-xl font-semibold mb-3 text-yellow-600";
                closeBtn.className = "px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-all focus:outline-none focus:ring-2 focus:ring-yellow-400";
            } else {
                title.textContent = "ℹ️ Information";
                title.className = "text-xl font-semibold mb-3 text-blue-600";
                closeBtn.className = "px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400";
            }

            // Show modal
            modal.classList.remove("hidden");
            setTimeout(() => {
                box.classList.remove("scale-95", "opacity-0");
                box.classList.add("scale-100", "opacity-100");
            }, 10);

            // Close button handler
            closeBtn.onclick = () => {
                box.classList.add("scale-95", "opacity-0");
                setTimeout(() => {
                    modal.classList.add("hidden");

                    // If success, reset form
                    if (type === "success") {
                        document.getElementById("applicantForm").reset();
                        // Reset to step 1
                        document.querySelectorAll('.form-step').forEach(step => {
                            step.classList.remove('active');
                        });
                        document.getElementById('step1').classList.add('active');
                        updateStepIndicators(1);

                        // Reset phone input
                        if (phoneInput) {
                            phoneInput.setNumber("");
                        }

                        // Reset conditional fields
                        setupConditionalFields();
                        calculateAge();
                    }
                }, 200);
            };
        }

        async function submitForm() {
            // Validate step 4
            if (!validateStep(4)) {
                return;
            }

            // Validate all required fields in the entire form
            const requiredFields = [
                'lname', 'fname', 'mname', 'gender', 'b_date', 'age',
                'civil_status', 'citizenship', 'religion', 'birth_place',
                'educational_attainment', 'brgy', 'municipality', 'province',
                'living_arrangement', 'contact_number', 'date_of_registration',
                'id_number'
            ];

            let missingFields = [];
            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element && !element.disabled) {
                    const value = element.value.trim();
                    if (!value && element.required) {
                        missingFields.push(field.replace('_', ' '));
                    }
                }
            });

            // Special validation for radio groups
            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            radioGroups.forEach(group => {
                const radios = document.querySelectorAll(`input[name="${group}"]:checked`);
                if (radios.length === 0) {
                    missingFields.push(group.replace('_', ' '));
                }
            });

            if (missingFields.length > 0) {
                showPopup(`Please complete the following fields: ${missingFields.join(', ')}`, 'error');
                return;
            }

            // Validate phone number
            if (!validatePhoneNumber()) {
                showPopup('Please enter a valid contact number', 'error');
                return;
            }

            // Collect form data
            const formData = collectFormData();

            // Format phone number
            if (phoneInput) {
                formData.contact_number = phoneInput.getNumber();
            }

            // Show loading
            const submitBtn = document.querySelector('button[onclick="submitForm()"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';

            try {
                const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/register/applicant.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    showPopup(`Application submitted successfully! ID: ${result.id_number || ''}`, 'success');
                } else {
                    showPopup(result.error || 'Submission failed. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Submission error:', error);
                showPopup('Network error. Please check your connection and try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }

        function collectFormData() {
            const form = document.getElementById('applicantForm');
            const formData = new FormData(form);
            const data = {};

            // Collect all form data
            formData.forEach((value, key) => {
                data[key] = value.trim();
            });

            // Collect radio button values
            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            radioGroups.forEach(group => {
                const selected = form.querySelector(`input[name="${group}"]:checked`);
                if (selected) {
                    data[group] = selected.value;
                }
            });

            // Add additional fields
            data.date_of_registration = document.getElementById('date_of_registration').value;
            data.id_number = document.getElementById('id_number').value;
            data.local_control_number = document.getElementById('local_control_number').value;

            // Add admin user info
            data.admin_user_id = <?php echo json_encode($_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? 57); ?>;
            data.admin_user_name = <?php echo json_encode($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin'); ?>;
            data.session_context = <?php echo json_encode($ctx ?? ''); ?>;
            data.request_source = 'admin_register';

            return data;
        }
    </script>
</body>

</html>