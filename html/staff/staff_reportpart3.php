<?php
require_once "/MSWDPALUAN_SYSTEM-MAIN/php/login/staff_header.php";
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
$username = "root";
$password = "";
$dbname = "mswd_seniors";

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
    <title>Senior List</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
                                <a href="../../php/login/logout.php"
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
                        <a href="staffindex.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-blue-100 dark:hover:bg-gray-700 group">
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
                        <a href="staff_register.php?session_context=<?php echo $ctx; ?>"
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
                                <a href="staff_activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="staff_inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="staff_deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="staff_benefits.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="staff_generate_id.php?session_context=<?php echo $ctx; ?>"
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
                    <li>
                    <li>
                        <a href="#" class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <svg class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3" />
                            </svg>

                            <span class="ml-3">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="staff_profile.php?session_context=<?php echo $ctx; ?>"
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
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-white focus:outline-none bg-blue-700 rounded-sm border border-gray-200 dark:bg-blue-700 dark:text-white">
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
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
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
            <!-- Part III -->
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                        <div class="flex flex-col md:flex-row items-center justify-between p-2">
                            <h4 class="text-lg font-medium dark:text-white"
                                style="font-family: 'Times New Roman', Times, serif;">III. Number of Pensioners /
                                Barangay</h4>
                        </div>

                        <!-- Summary Statistics -->
                        <!-- <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center">
                                    <span class="text-gray-600 dark:text-gray-300 mr-2">Total Pensioners:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" id="totalPensioners">0</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 dark:text-gray-300 mr-2">Male:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" id="totalMale">0</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 dark:text-gray-300 mr-2">Female:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400" id="totalFemale">0</span>
                                </div>
                            </div>
                        </div> -->

                        <div class="overflow-x-auto">
                            <table id="pensionersTable"
                                class="w-full text-sm text-center text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600">
                                <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                    <tr class="flex w-full border-gray-300 dark:border-gray-600">
                                        <th scope="col" class="px-4 py-3 flex w-full text-sm text-left">Barangay</th>
                                        <th scope="col"
                                            class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Male
                                        </th>
                                        <th scope="col"
                                            class="px-4 py-3 flex-l w-full text-sm  border-l border-gray-300 dark:border-gray-600">
                                            Female</th>
                                        <th scope="col"
                                            class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody id="pensionersBody" class="block max-h-80 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] 
                                                    [&::-webkit-scrollbar]:hidden">
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center">Loading data...</td>
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
    <script src="../../js/staff_tailwind.config.js"></script>
    <script src="../../js/staff_theme.js"></script>
    <script>
        // ---------- THEME INITIALIZATION (MUST BE OUTSIDE DOMContentLoaded) ----------
        // Initialize theme from localStorage or system preference

        // STAFF-SPECIFIC THEME FUNCTIONS for register.php
        (function() {
            // Use the same StaffTheme namespace
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

                    // Dispatch event for staff components
                    window.dispatchEvent(new CustomEvent('staffThemeChanged'));
                }
            };

            // Initialize theme
            StaffTheme.init();

            // Listen for storage events
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

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('staff_theme')) {
                    StaffTheme.set(e.matches ? 'dark' : 'light');
                }
            });
        })();
    </script>
    <script>
        (function() {
            'use strict';

            console.log('Part III page initialized');

            // Get current filter values from PHP
            const currentYear = <?php echo isset($currentYear) && $currentYear ? json_encode($currentYear) : 'null'; ?>;
            const currentMonth = <?php echo isset($currentMonth) && $currentMonth ? json_encode($currentMonth) : 'null'; ?>;

            // DOM elements
            const totalPensionersElement = document.getElementById('totalPensioners');
            const totalMaleElement = document.getElementById('totalMale');
            const totalFemaleElement = document.getElementById('totalFemale');

            // Load data when page is ready
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded. Loading Part III data...');
                console.log('Filters - Year:', currentYear, 'Month:', currentMonth);

                // Load the data
                fetchPensioners();
            });

            // Main function to fetch pensioners per barangay
            async function fetchPensioners() {
                try {
                    showLoading();

                    // Build API URL
                    let apiUrl = '/MSWDPALUAN_SYSTEM-MAIN/php/reports/report_part3_backend.php';
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
                        displayPensioners(result);
                        updateSummary(result);
                        updateReportPeriod(result);
                    } else {
                        throw new Error(result.message || 'Unknown error from API');
                    }

                } catch (error) {
                    console.error('Error loading pensioners:', error);
                    showError('Failed to load data: ' + error.message);
                }
            }

            // Function to display pensioners in the table
            function displayPensioners(data) {
                const tbody = document.getElementById('pensionersBody');
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
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg">No pensioners found</p>
                            <p class="text-sm mt-1">No validated pensioners in the selected period</p>
                        </td>
                    </tr>
                `;
                    return;
                }

                console.log('Displaying', data.data.length, 'records');

                // Create rows for each barangay
                data.data.forEach((item, index) => {
                    const row = document.createElement('tr');

                    // Check if this is the total row
                    const isTotal = item.is_total || item.barangay === 'Total';

                    if (isTotal) {
                        row.className = 'flex w-full font-semibold dark:text-white bg-gray-100 dark:bg-gray-800 border-gray-400 dark:border-gray-500';
                    } else {
                        row.className = 'flex w-full font-semibold dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700';
                    }

                    row.innerHTML = `
                    <td class="px-4 py-3 flex w-full ${isTotal ? 'border' : 'border-b-0 borde-t-0 border-l-0 border-r-0'} border-gray-300 dark:border-gray-600 text-left" 
                        style="font-family: 'Times New Roman', Times, serif;">
                        ${item.barangay}
                    </td>
                    <td class="px-4 py-3 flex-l w-full ${isTotal ? 'border' : 'border-b-0 borde-t-0 border-r-0'} border-gray-300 dark:border-gray-600 text-center">
                        ${item.male}
                    </td>
                    <td class="px-4 py-3 flex-l w-full ${isTotal ? 'border' : 'border-b-0 borde-t-0 border-r-0'} border-gray-300 dark:border-gray-600 text-center">
                        ${item.female}
                    </td>
                    <td class="px-4 py-3 flex-l w-full ${isTotal ? 'border' : 'border-b-0 borde-t-0 border-r-0'} border-gray-300 dark:border-gray-600 text-center">
                        ${item.total}
                    </td>
                `;
                    tbody.appendChild(row);
                });

                console.log('Displayed', data.data.length, 'records');
            }

            // Update summary statistics
            function updateSummary(data) {
                if (totalPensionersElement && data.summary) {
                    totalPensionersElement.textContent = data.summary.total_overall || 0;
                }
                if (totalMaleElement && data.summary) {
                    totalMaleElement.textContent = data.summary.total_male || 0;
                }
                if (totalFemaleElement && data.summary) {
                    totalFemaleElement.textContent = data.summary.total_female || 0;
                }
            }

            // Update report period display
            function updateReportPeriod(data) {
                const periodElement = document.getElementById('reportPeriod');
                if (!periodElement) return;

                const monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                let displayText = 'All Time';
                const year = data?.filters?.year;
                const month = data?.filters?.month;

                if (month && year) {
                    const monthName = monthNames[month - 1] || 'Unknown';
                    displayText = `${monthName} ${year}`;
                } else if (year) {
                    displayText = `Year ${year}`;
                } else if (month) {
                    const monthName = monthNames[month - 1] || 'Unknown';
                    displayText = `${monthName} (All Years)`;
                }

                periodElement.textContent = displayText;
            }

            // Utility functions
            function showLoading() {
                const tbody = document.getElementById('pensionersBody');
                if (tbody) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center">
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
                const tbody = document.getElementById('pensionersBody');
                if (tbody) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-red-500">
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

            // Navigation functions
            window.part1 = function() {
                navigateToReport('staff_report.php');
            };

            window.part2 = function() {
                navigateToReport('staff_reportpart2.php');
            };

            window.part3 = function() {
                // Already on part 3, reload with current filters
                if (typeof applyFilters === 'function') {
                    applyFilters();
                } else {
                    window.location.reload();
                }
            };

            window.part4 = function() {
                navigateToReport('staff_reportpart4.php');
            };

            window.part5 = function() {
                navigateToReport('staff_reportpart5.php');
            };

            window.part6 = function() {
                navigateToReport('staff_reportpart6.php');
            };

            window.part7to9 = function() {
                navigateToReport('staff_reportpart7to9.php');
            };

            window.benefits = function() {
                navigateToReport('staff_reportbenefits.php');
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
                let url = 'staff_generate_consolidated_report.php';
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

            // Listen for filter changes from date_filter_component.php
            if (typeof window !== 'undefined') {
                window.addEventListener('filtersApplied', function(e) {
                    console.log('Filters applied in Part III:', e.detail);
                    // Reload data with new filters
                    fetchPensioners();
                });
            }

        })();
    </script>

</body>

</html>