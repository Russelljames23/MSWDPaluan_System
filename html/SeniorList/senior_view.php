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
    <title>Senior List</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                                <a href="../../MSWDPALUAN_SYSTEM-MAIN/php/login/logout.php"
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
                        <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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
                            class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
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
                        <ul id="dropdown-pages" class="py-2 space-y-2">
                            <li>
                                <a href="#"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="./inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="./deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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
                    <li>
                        <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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
                        <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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
                        <a href="../archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
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

        <main class="p-4 md:ml-64 pt-20">
            <!-- Header with navigation - FIXED FOR DARK MODE -->
            <div  class="w-full flex justify-between items-center mb-6 no-print">
                <a href="./activelist.php?session_context=<?php echo $ctx; ?>"
                    class="flex flex-row items-center cursor-pointer bg-blue-700 hover:bg-blue-800 text-white dark:text-white font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to List
                </a>

                <div class="flex items-center space-x-4">
                    <a href="#" class="text-lg font-medium text-blue-700 dark:text-blue-400">
                        <i class="fas fa-chart-pie mr-2"></i>Applicant
                    </a>
                    <a href="./senior_demographic.php?session_context=<?php echo $ctx; ?>&id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>"
                        class="text-lg font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition duration-200">
                        <i class="fas fa-file-alt mr-2"></i>Demographic
                    </a>
                </div>

                <div class="relative">
                    <button id="actionDropdownButton" data-dropdown-toggle="actionDropdown"
                        class="bg-blue-700 hover:bg-blue-800 text-white dark:text-white font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200"
                        type="button">
                        <i class="fas fa-ellipsis-v mr-2"></i>
                        Actions
                    </button>

                    <div id="actionDropdown"
                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-800 dark:divide-gray-600">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                            <li>
                                <a href="#" data-modal-target="default-modal" data-modal-toggle="default-modal" id="editButton"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <i class="fas fa-edit mr-2"></i>Edit Information
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="window.print()"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <i class="fas fa-print mr-2"></i>Print Profile
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <form action="">
                <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                    <div class="flex flex-col  items-center w-full px-20 border border-gray-200 py-10">
                        <div class="flex flex-row   justify-center ml-15">
                            <div class="flex flex-row  mb-2 items-end ">
                                <div class="flex flex-col items-center gap-1 relative">
                                    <div class="flex flex-col items-center justify-center ">
                                        <div class="flex flex-row items-center gap-2  p-0">
                                            <img src="../../img/OIP-removebg-preview.png" alt="" class="h-[29px] mt-1 ">
                                            <h1
                                                class="text-3xl text-center  font-extrabold text-gray-900  lg:text-[45px] dark:text-white">
                                                DSWD</h1>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-gray-900 mt-[-1px] dark:text-white ">Department of
                                                Social
                                                Welfare
                                                and
                                                Development</p>
                                        </div>
                                    </div>
                                    <h4 class="text-[13px] text-center  font-bold text-gray-900  dark:text-white">
                                        SOCIAL PENSION FOR INDIGENT SENIOR CITIZENS</h4>
                                </div>
                            </div>
                            <div class="flex flex-col items-center ml-20 ">
                                <div class="">
                                    <p class="text-xs text-gray-900 dark:text-white italic font-bold">ANNEX 2</p>
                                </div>
                                <div class="h-[1in] w-[1in] flex items-center justify-center border  mt-8 p-0">
                                    <p class="text-[10.5px] text-gray-900 dark:text-white ">1x1 picture</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5   w-full">
                            <h4 class="text-[18px] text-center  font-bold text-gray-900  dark:text-white">
                                APPLICATION FORM</h4>
                        </div>
                        <!-- Basic Information  -->
                        <div class="mt-5  flex gap-1 flex-col w-full">
                            <div class="justify-start flex">
                                <h4 class="text-[16px] text-center  font-bold text-gray-900  dark:text-white">
                                    I. BASIC INFORMATION</h4>
                            </div>
                            <div class="flex flex-col py-2 px-4 w-full">
                                <div class="">
                                    <div class="flex flex-row w-full justify-between  gap-5 ">
                                        <div class="flex flex-row items-end gap-3 w-full">
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Name:</h5>
                                            <div class="w-full">
                                                <input type="text" readonly name="full_name"
                                                    class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                        </div>
                                        <div class="flex flex-row  gap-3 w-180 items-end">
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Citizenship:</h5>
                                            <div class="w-full">
                                                <input type="text" readonly name="citizenship"
                                                    class="block w-full text-center text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                        </div>
                                    </div>
                                    <h5
                                        class="text-[13px] italic text-start mt-[-3px] ml-33  font-normal text-gray-900  dark:text-white">
                                        (Last Name, First Name, Middle Name)</h5>
                                </div>
                                <div class="w-full ">
                                    <div class="flex flex-row w-full justify-between gap-5 ">
                                        <div class="flex flex-row items-end gap-3 w-full">
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Address:</h5>
                                            <div class="w-full flex flex-row">
                                                <input type="text" readonly name="houseno"
                                                    class="block w-17 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                <input type="text" readonly name="street"
                                                    class="block w-34 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                <input type="text" readonly name="barangay"
                                                    class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                <input type="text" readonly name="municipality"
                                                    class="block w-40 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                <input type="text" readonly name="province"
                                                    class="block w-40 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row mt-[-3px] ml-16.5 w-187 border">
                                        <h5
                                            class="text-[13px] text-start italic font-normal text-gray-900  dark:text-white">
                                            (House No</h5>
                                        <h5
                                            class="text-[13px] text-start ml-15 italic  font-normal text-gray-900  dark:text-white">
                                            Street</h5>
                                        <h5
                                            class="text-[13px] text-start ml-34  italic font-normal text-gray-900  dark:text-white">
                                            Barangay</h5>
                                        <h5
                                            class="text-[13px] text-start ml-30  italic font-normal text-gray-900  dark:text-white">
                                            City/Municipality</h5>
                                        <h5
                                            class="text-[13px] text-start ml-20  italic font-normal text-gray-900  dark:text-white">
                                            Province)</h5>
                                    </div>
                                </div>
                                <div class="flex flex-row w-full justify-between gap-5 ">
                                    <div class="flex flex-row items-end gap-3 w-100">
                                        <h5 class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                            Age:</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="age"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end gap-3 w-120">
                                        <h5 class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                            Sex:</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="gender"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end gap-3 w-160">
                                        <h5
                                            class="text-[14px] w-25  text-center font-medium text-gray-900  dark:text-white">
                                            Civil Status:</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="civil_status"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex flex-row w-full justify-between gap-5 ">
                                        <div class="flex flex-row items-end gap-3 w-120">
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Birthdate:</h5>
                                            <div class="w-full">
                                                <input type="text" readonly name="birth_date"
                                                    class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                        </div>
                                        <div class="flex flex-row items-end gap-3 w-full">
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Birthplace:</h5>
                                            <div class="w-full">
                                                <input type="text" readonly name="birth_place"
                                                    class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                        </div>
                                    </div>
                                    <h5
                                        class="text-[13px] italic text-start mt-[-3px] ml-33  font-normal text-gray-900  dark:text-white">
                                        (Month, Date, Year)</h5>
                                </div>
                                <div class="flex flex-row items-end">
                                    <div class="flex flex-row items-start justify-start  ">
                                        <h5
                                            class="text-[14px] w-34 text-start font-medium text-gray-900  dark:text-white">
                                            Living Arrangement:</h5>
                                    </div>
                                    <div class="flex flex-row justify-between gap-2 w-full ">
                                        <div class="flex flex-row items-end  ">
                                            <div class="">
                                                <input type="text" readonly name="living_arrangement_owned"
                                                    class="block w-20 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                            <h5
                                                class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                Owned:</h5>
                                        </div>
                                        <div class="flex flex-row items-end ">
                                            <div class="">
                                                <input type="text" readonly name="living_arrangement_alone"
                                                    class="block w-20 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                            <h5
                                                class="text-[14px] w-22  text-start font-medium text-gray-900  dark:text-white">
                                                Living Alone:</h5>
                                        </div>
                                        <div class="flex flex-row items-end ">
                                            <div class="">
                                                <input type="text" readonly name="living_arrangement_relatives"
                                                    class="block w-20 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                            <h5
                                                class="text-[14px] w-34.5  text-start font-medium text-gray-900  dark:text-white">
                                                Living with Relatives:</h5>
                                        </div>
                                        <div class="flex flex-row items-end ">
                                            <div class="">
                                                <input type="text" readonly name="living_arrangement_rent"
                                                    class="block w-25 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                            </div>
                                            <h5
                                                class="text-[14px] text-center font-medium text-gray-900  dark:text-white">
                                                Rent:</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Economics -->
                        <div class="mt-5  flex flex-col w-full">
                            <div class="justify-start flex">
                                <h4 class="text-[16px] text-center  font-bold text-gray-900  dark:text-white">
                                    II. ECONOMICS</h4>
                            </div>
                            <div class="flex flex-col py-2 px-4 gap-1 w-full">
                                <div class="flex flex-row items-end justify-between gap-20">
                                    <div class="flex flex-row justify-start  w-70 ">
                                        <div class="flex flex-row items-start justify-start  ">
                                            <h5
                                                class="text-[14px]  text-start font-medium text-gray-900  dark:text-white">
                                                Pensioner?</h5>
                                        </div>
                                        <div class="flex flex-row gap-3 justify-between">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="is_pensioner_yes"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    Yes</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="is_pensioner_no"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    No</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end w-130">
                                        <h5
                                            class="text-[14px] w-50  text-start font-medium text-gray-900  dark:text-white">
                                            If yes, please specify</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="pension_amount"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row items-end justify-between gap-20">
                                    <div class="flex flex-row justify-start gap-1.5  w-180 ">
                                        <div class="flex flex-row items-start justify-start  ">
                                            <h5
                                                class="text-[14px]  text-start font-medium text-gray-900  dark:text-white">
                                                Source:</h5>
                                        </div>
                                        <div class="flex flex-row justify-between w-full">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="pension_source_gsis"
                                                        class="block w-15 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    GSIS</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="pension_source_sss"
                                                        class="block w-15 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    SSS</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="pension_source_afpslai"
                                                        class="block w-15 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start  font-medium text-gray-900  dark:text-white">
                                                    AFPSLAI</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="pension_source_others"
                                                        class="block w-15 text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    Others</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row items-end justify-between w-full ">
                                    <div class="flex flex-row justify-start  w-70 ">
                                        <div class="flex flex-row items-start justify-start ">
                                            <h5
                                                class="text-[14px] w-50 text-start font-medium text-gray-900  dark:text-white">
                                                Permanent Source of Income?</h5>
                                        </div>
                                        <div class="flex flex-row gap-3 justify-between">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_permanent_income_yes"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    Yes</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_permanent_income_none"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    None</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end  ">
                                        <h5
                                            class="text-[14px] w-40  text-start font-medium text-gray-900  dark:text-white">
                                            If yes, from what source?</h5>
                                        <div class=" ">
                                            <input type="text" readonly name="income_source"
                                                class="block  text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row items-end justify-between gap-20">
                                    <div class="flex flex-row justify-start  w-70 ">
                                        <div class="flex flex-row items-start justify-start ">
                                            <h5
                                                class="text-[14px] w-50 text-start font-medium text-gray-900  dark:text-white">
                                                Regular Support from Family?</h5>
                                        </div>
                                        <div class="flex flex-row gap-3 justify-between">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_family_support_yes"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    Yes</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_family_support_no"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    No</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row items-end w-full justify-between gap-2 ">
                                    <div class="flex flex-row items-end w-80">
                                        <h5
                                            class="text-[14px] w-60  text-start font-medium text-gray-900  dark:text-white">
                                            Type of Support?</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="support_type"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end w-full ">
                                        <h5
                                            class="text-[14px]  w-full text-start font-medium text-gray-900  dark:text-white">
                                            Cash (how much and how often)</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="support_cash"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                        <h5
                                            class="text-[14px] w-63.5  text-start font-medium text-gray-900  dark:text-white">
                                            In kind (specify)</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Health Condition  -->
                        <div class="mt-5  flex flex-col w-full">
                            <div class="justify-start flex">
                                <h4 class="text-[16px] text-center  font-bold text-gray-900  dark:text-white">
                                    III. HEALTH CONDITION</h4>
                            </div>
                            <div class="flex flex-col py-2 px-4 gap-1 w-full">
                                <div class="flex flex-row items-end justify-between gap-10">
                                    <div class="flex flex-row justify-start  w-70 ">
                                        <div class="flex flex-row items-start justify-start ">
                                            <h5
                                                class="text-[14px] w-33 text-start font-medium text-gray-900  dark:text-white">
                                                Has existing illness?</h5>
                                        </div>
                                        <div class="flex flex-row gap-3 justify-between">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_existing_illness_yes"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    Yes</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="has_existing_illness_no"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    No</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-end w-full">
                                        <h5
                                            class="text-[14px] w-50  text-start font-medium text-gray-900  dark:text-white">
                                            If yes, please specify?</h5>
                                        <div class="w-full">
                                            <input type="text" readonly name="illness_details"
                                                class="block w-full text-sm h-5 text-center text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row items-end">
                                    <div class="flex flex-row justify-start  w-full gap-1 ">
                                        <div class="flex flex-row items-start justify-start">
                                            <h5
                                                class="text-[14px] w-full text-start font-medium text-gray-900  dark:text-white">
                                                Hospitalized witihin the last six months?</h5>
                                        </div>
                                        <div class="flex flex-row gap-3 justify-between">
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="hospitalized_last6mos_yes"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]  text-center font-medium text-gray-900  dark:text-white">
                                                    Yes</h5>
                                            </div>
                                            <div class="flex flex-row items-end ">
                                                <div class="">
                                                    <input type="text" readonly name="hospitalized_last6mos_no"
                                                        class="block w-15 text-sm h-5 text-gray-900 bg-transparent border-0 border-b border-gray-900 appearance-none ">
                                                </div>
                                                <h5
                                                    class="text-[14px]   text-start font-medium text-gray-900  dark:text-white">
                                                    No</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>

            <!-- edit modal  -->
            <div id="default-modal" tabindex="-1" aria-hidden="true"
                class="hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-10 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-2 w-full  max-w-5xl max-h-full">
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                        <!-- Modal header -->
                        <div
                            class="flex items-center justify-between p-2 border-b rounded-t dark:border-gray-600 border-gray-200">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Edit Senior
                            </h3>
                            <button type="button"
                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                data-modal-hide="default-modal">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                </svg>
                                <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <div class="p-2 space-y-4">
                            <form id="applicantForm" action="">
                                <section id="step1" class="bg-white flex flex-col dark:bg-gray-900 rounded-lg">
                                    <div class="p-5 mb-8 rounded-lg w-full">
                                        <div>
                                            <h4 class="text-sm font-medium dark:text-white mb-3">I. BASIC INFORMATION
                                            </h4>
                                            <div class="flex flex-col gap-5">
                                                <div class="flex w-full justify-between items-center gap-5">
                                                    <div class="w-90 flex flex-col gap-3">
                                                        <div class="w-90">
                                                            <label
                                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Last
                                                                Name:</label>
                                                            <input type="text" id="lname" name="lname"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                                                required>
                                                        </div>
                                                        <div class="w-90">
                                                            <label
                                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">First
                                                                Name:</label>
                                                            <input type="text" id="fname" name="fname"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                                                required>
                                                        </div>
                                                        <div class="w-90">
                                                            <label
                                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Middle
                                                                Name:</label>
                                                            <input type="text" id="mname" name="mname"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                                                required>
                                                        </div>
                                                    </div>

                                                    <div class="w-full flex gap-3 flex-col justify-between">
                                                        <div class="w-full flex flex-row gap-5">
                                                            <div class="w-50">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Gender:</label>
                                                                <select id="gender" name="gender"
                                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700">
                                                                    <option value="">Select</option>
                                                                    <option value="Male">Male</option>
                                                                    <option value="Female">Female</option>
                                                                </select>
                                                            </div>

                                                            <div class="w-50">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Age:</label>
                                                                <input type="text" id="age" name="age"
                                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700"
                                                                    placeholder="">
                                                            </div>

                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Civil
                                                                    Status:</label>
                                                                <select id="civil_status" name="civil_status"
                                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700">
                                                                    <option value="">Select</option>
                                                                    <option value="Single">Single</option>
                                                                    <option value="Married">Married</option>
                                                                    <option value="Separated">Separated</option>
                                                                    <option value="Widowed">Widowed</option>
                                                                    <option value="Divorced">Divorced</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="w-full flex flex-row gap-5">
                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Birthdate:</label>
                                                                <input type="date" id="birth_date" name="birth_date"
                                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700"
                                                                    placeholder="">
                                                            </div>
                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Citizenship:</label>
                                                                <input type="text" id="citizenship" name="citizenship"
                                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700"
                                                                    placeholder="">
                                                            </div>
                                                        </div>

                                                        <div class="w-full flex flex-row gap-5">
                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Birthplace:</label>
                                                                <input type="text" id="birth_place" name="birth_place"
                                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700"
                                                                    placeholder="">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex w-full flex-row justify-between items-center gap-5">
                                                    <div class="w-50">
                                                        <label
                                                            class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Living
                                                            Arrangement:</label>
                                                        <select id="living_arrangement" name="living_arrangement"
                                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700">
                                                            <option value="">Select</option>
                                                            <option value="Owned">Owned</option>
                                                            <option value="Living alone">Living alone</option>
                                                            <option value="Living with relatives">Living with relatives
                                                            </option>
                                                            <option value="Rent">Rent</option>
                                                        </select>
                                                    </div>

                                                    <div class="w-full">
                                                        <label
                                                            class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Address:</label>
                                                        <div class="flex flex-row gap-3">
                                                            <input type="text" id="house_no" name="house_no"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700"
                                                                placeholder="House No.">

                                                            <input type="text" id="street" name="street"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700"
                                                                placeholder="Street">

                                                            <select id="barangay" name="barangay"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700">
                                                                <option value="">Barangay</option>
                                                                <option value="I - Mapalad">I - Mapalad</option>
                                                                <option value="II - Handang Tumulong">II - Handang
                                                                    Tumulong</option>
                                                                <option value="III - Silahis ng Pag-asa">III - Silahis
                                                                    ng Pag-asa</option>
                                                                <option value="IV - Pag-asa ng Bayan">IV - Pag-asa ng
                                                                    Bayan</option>
                                                                <option value="V - Bagong Silang">V - Bagong Silang
                                                                </option>
                                                                <option value="VI - San Jose">VI - San Jose</option>
                                                                <option value="VII - Lumang Bayan">VII - Lumang Bayan
                                                                </option>
                                                                <option value="VIII - Marikit">VIII - Marikit</option>
                                                                <option value="IX - Tubili">IX - Tubili</option>
                                                                <option value="X - Alipaoy">X - Alipaoy</option>
                                                                <option value="XI - Harison">XI - Harison</option>
                                                                <option value="XII - Mananao">XII - Mananao</option>
                                                            </select>

                                                            <input type="text" id="municipality" name="municipality"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700"
                                                                placeholder="City/Municipality">

                                                            <input type="text" id="province" name="province"
                                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700"
                                                                placeholder="Province">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-full flex justify-end items-center mb-2">
                                        <button type="button" onclick="nextStep()"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-1 me-2 dark:bg-blue-600 dark:hover:bg-blue-700">Next</button>
                                    </div>
                                </section>
                                <section id="step2" class="bg-white hidden flex-col dark:bg-gray-900 rounded-lg">
                                    <div class="p-5 mb-10 gap-10 rounded-lg w-full flex flex-col">
                                        <div>
                                            <h4 class="text-sm font-medium dark:text-white mb-3">II. ECONOMIC STATUS
                                            </h4>
                                            <div class="flex flex-col w-full  justify-between items-center gap-5">
                                                <!-- Pensioner  -->
                                                <div class="w-full flex justify-between  flex-row gap-5 ">
                                                    <div class="w-50  flex flex-row gap-2 items-center">
                                                        <label for="email"
                                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Pensioner?</label>
                                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                                            <div class="flex items-center ">
                                                                <input id="is_pensioner" type="radio" value="1"
                                                                    name="is_pensioner"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for=""
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <input checked id="is_pensioner" type="radio" value="0"
                                                                    name="is_pensioner"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for="default-radio-2"
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full flex flex-row gap-2 items-center">
                                                        <label for="email"
                                                            class="block text-sm w-40  font-normal text-gray-900 dark:text-gray-300">If
                                                            yes, how much?</label>
                                                        <input type="text" id="pension_amount" name="pension_amount"
                                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                            placeholder="" required>
                                                    </div>
                                                    <div class="w-full  flex flex-row items-center gap-2">
                                                        <div class="flex flex-row w-full gap-2 items-center">
                                                            <label for="source"
                                                                class="block text-sm font-normal text-gray-900 dark:text-gray-300">Source:</label>
                                                            <select id="pension_source" name="pension_source"
                                                                class="bg-gray-50 border cursor-pointer border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                                <option value=""></option>
                                                                <option value="GSIS">GSIS</option>
                                                                <option value="SSS">SSS</option>
                                                                <option value="AFPSLAI">AFPSLAI</option>
                                                                <option value="Others">Others</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Permanent Source  -->
                                                <div
                                                    class="w-full flex justify-between gap-5 pt-4 border-t border-gray-200 flex-row ">
                                                    <div class="w-110  flex flex-row gap-2 items-center ">
                                                        <label for="email"
                                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Permanent
                                                            Source of Income?</label>
                                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                                            <div class="flex items-center ">
                                                                <input id="has_permanent_income" type="radio" value="1"
                                                                    name="has_permanent_income"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for=""
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <input checked id="has_permanent_income" type="radio"
                                                                    value="0" name="has_permanent_income"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for="default-radio-2"
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">None</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full flex flex-row gap-2 items-center ">
                                                        <label for="email"
                                                            class="block text-sm w-50  font-normal text-gray-900 dark:text-gray-300">If
                                                            yes, from what source?</label>
                                                        <input type="text" id="income_source" name="income_source"
                                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                            placeholder="">
                                                    </div>
                                                </div>
                                                <!-- reg support from fam  -->
                                                <div
                                                    class="w-full flex justify-between flex-row gap-5 pt-4 border-t border-gray-200">
                                                    <div class="w-full   flex flex-row gap-2 items-center">
                                                        <label for="email"
                                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Regular
                                                            Support from Family?</label>
                                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                                            <div class="flex items-center ">
                                                                <input id="has_family_support" type="radio" value="1"
                                                                    name="has_family_support"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for=""
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <input checked id="has_family_support" type="radio"
                                                                    value="0" name="has_family_support"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for="default-radio-2"
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full flex  flex-row gap-2 items-center">
                                                        <label for="email"
                                                            class="block text-sm w-50  font-normal text-gray-900 dark:text-gray-300">Type
                                                            of Support?</label>
                                                        <input type="text" id="support_type" name="support_type"
                                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                            placeholder="">
                                                    </div>
                                                    <div class="w-full flex boder flex-row gap-2 items-center">
                                                        <label for="email"
                                                            class="block text-sm w-70  font-normal text-gray-900 dark:text-gray-300">Cash
                                                            (How much and how often)</label>
                                                        <div class="flex flex-row gap-2 items-center">
                                                            <input type="text" id="support_cash" name="support_cash"
                                                                class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                                placeholder=""><label for="email"
                                                                class="block text-sm w-50  font-normal text-gray-900 dark:text-gray-300">In
                                                                kind (specify)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium dark:text-white mb-3">III. HEALTH CONDITION
                                            </h4>
                                            <div class="flex flex-col gap-5">
                                                <div class="w-full flex justify-between  flex-row gap-5 ">
                                                    <div class="w-120  flex flex-row gap-2  items-center">
                                                        <label for="email"
                                                            class="block  w-35 text-sm font-normal text-gray-900 dark:text-gray-300">Has
                                                            existing illness?</label>
                                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                                            <div class="flex items-center ">
                                                                <input id="has_existing_illness" type="radio" value="1"
                                                                    name="has_existing_illness"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for=""
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <input checked id="has_existing_illness" type="radio"
                                                                    value="0" name="has_existing_illness"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for="default-radio-2"
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="w-full flex flex-row  gap-2 items-center">
                                                        <label for="email"
                                                            class="block text-sm w-60  font-normal text-gray-900 dark:text-gray-300">If
                                                            yes, please specify:</label>
                                                        <input type="text" id="illness_details" name="illness_details"
                                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                            placeholder="">
                                                    </div>
                                                    <div class="w-150  flex flex-row   gap-2 items-center">
                                                        <label for="email"
                                                            class="block w-65  text-sm font-normal text-gray-900 dark:text-gray-300">Hospitalized
                                                            witihin the last six months?</label>
                                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                                            <div class="flex items-center ">
                                                                <input id="hospitalized_last6mos" type="radio" value="1"
                                                                    name="hospitalized_last6mos"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for=""
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <input checked id="hospitalized_last6mos" type="radio"
                                                                    value="0" name="hospitalized_last6mos"
                                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                                <label for="default-radio-2"
                                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- step marks -->
                                    <div class="flex justify-center items-center gap-8">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="border border-gray-400 rounded-full bg-gray-500 h-4 w-4"></div>
                                            <p class="text-sm text-gray-900 dark:text-white font-medium">Step 1</p>
                                        </div>
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="border border-gray-400 rounded-full bg-blue-500 h-4 w-4"></div>
                                            <p class="text-sm text-gray-900 dark:text-white font-medium">Step 2</p>
                                        </div>
                                    </div>
                                    <div class="w-full flex justify-between">
                                        <button type="button" onclick="prevStep()"
                                            class="text-white cursor-pointer bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-1 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Back</button>
                                        <button type="button" onclick="submitForm()"
                                            class="text-white cursor-pointer bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-1 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Submit</button>
                                    </div>
                                </section>
                            </form>
                        </div>
                    </div>
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
    </script>
    <script>
        function nextStep() {
            document.getElementById("step1").classList.add("hidden");
            document.getElementById("step2").classList.remove("hidden");
        }

        function prevStep() {
            document.getElementById("step2").classList.add("hidden");
            document.getElementById("step1").classList.remove("hidden");
        }
    </script>

    <!-- form fetch  -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            if (!id) return;

            const phpFilePath = `/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/senior_view.php?id=${encodeURIComponent(id)}`;

            fetch(phpFilePath)
                .then(res => {
                    if (!res.ok) throw new Error(`Server responded with status ${res.status}`);
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        console.error('Server error:', data.error);
                        return;
                    }

                    const {
                        applicant,
                        address,
                        economic,
                        health
                    } = data;

                    // ---------- Helper Functions ----------
                    const setField = (selector, value) => {
                        const el = document.querySelector(selector);
                        if (el) el.value = value ?? '';
                    };

                    const setYesNoField = (yesSelector, noSelector, value) => {
                        const yesEl = document.querySelector(yesSelector);
                        const noEl = document.querySelector(noSelector);
                        if (yesEl && noEl) {
                            yesEl.value = (value == 1 || value === true) ? '✓' : '';
                            noEl.value = (value == 0 || value === false) ? '✓' : '';
                        }
                    };

                    const setPensionSource = (source) => {
                        setField('input[name="pension_source_gsis"]', source === 'GSIS' ? '✓' : '');
                        setField('input[name="pension_source_sss"]', source === 'SSS' ? '✓' : '');
                        setField('input[name="pension_source_afpslai"]', source === 'AFPSLAI' ? '✓' : '');
                        setField('input[name="pension_source_others"]', source === 'Others' ? '✓' : '');
                    };

                    const setLivingArrangement = (arrangement) => {
                        setField('input[name="living_arrangement_owned"]', arrangement === 'Owned' ? '✓' : '');
                        setField('input[name="living_arrangement_alone"]', arrangement === 'Living alone' ? '✓' : '');
                        setField('input[name="living_arrangement_relatives"]', arrangement === 'Living with relatives' ? '✓' : '');
                        setField('input[name="living_arrangement_rent"]', arrangement === 'Rent' ? '✓' : '');
                    };

                    // ---------- Applicant Info ----------
                    if (applicant) {
                        const fullName = `${applicant.last_name ?? ''}, ${applicant.first_name ?? ''} ${applicant.middle_name ?? ''}.`;
                        setField('input[name="full_name"]', fullName.trim());
                        setField('input[name="citizenship"]', applicant.citizenship);
                        setField('input[name="age"]', applicant.age);
                        setField('input[name="gender"]', applicant.gender);
                        setField('input[name="civil_status"]', applicant.civil_status);
                        setField('input[name="birth_date"]', applicant.birth_date);
                        setField('input[name="houseno"]', address.house_no);
                        setField('input[name="street"]', address.street);
                        setField('input[name="barangay"]', address.barangay);
                        setField('input[name="municipality"]', address.municipality);
                        setField('input[name="province"]', address.province);
                        setLivingArrangement(applicant.living_arrangement);
                    }

                    // ---------- Address Info ----------
                    // if (address) {
                    //     const fullAddress = [
                    //         address.house_no, address.street, address.barangay,
                    //         address.municipality, address.province
                    //     ].filter(Boolean).join(', ');
                    //     setField('input[name="addresses"]', fullAddress);
                    // }

                    // ---------- Economic Info ----------
                    if (economic) {
                        setYesNoField('input[name="is_pensioner_yes"]', 'input[name="is_pensioner_no"]', economic.is_pensioner);
                        setField('input[name="pension_amount"]', economic.pension_amount);
                        setPensionSource(economic.pension_source);

                        setYesNoField('input[name="has_permanent_income_yes"]', 'input[name="has_permanent_income_none"]', economic.has_permanent_income);
                        setField('input[name="income_source"]', economic.income_source);

                        setYesNoField('input[name="has_family_support_yes"]', 'input[name="has_family_support_no"]', economic.has_family_support);
                        setField('input[name="support_type"]', economic.support_type);
                        setField('input[name="support_cash"]', economic.support_cash);
                    }

                    // ---------- Health Info ----------
                    if (health) {
                        setYesNoField('input[name="has_existing_illness_yes"]', 'input[name="has_existing_illness_no"]', health.has_existing_illness);
                        setField('input[name="illness_details"]', health.illness_details);
                        setYesNoField('input[name="hospitalized_last6mos_yes"]', 'input[name="hospitalized_last6mos_no"]', health.hospitalized_last6mos);
                    }

                })
                .catch(err => {
                    alert('Failed to load applicant data. Check console for details.');
                    console.error('Error fetching applicant data:', err);
                });
        });
    </script>

    <!-- modal -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('applicantForm');
            // --- Get ?id= from URL ---
            const urlParams = new URLSearchParams(window.location.search);
            const applicantId = urlParams.get('id');
            if (!applicantId) {
                alert("Missing applicant ID in URL.");
                return;
            }

            // --- Helper to set form field values ---
            function setField(id, value) {
                const el = document.getElementById(id) || form.querySelector(`[name="${id}"]`);
                if (!el) return;
                if (el.type === 'radio') {
                    const radios = form.querySelectorAll(`[name="${el.name}"]`);
                    radios.forEach(r => r.checked = (r.value == value));
                } else {
                    el.value = value ?? '';
                }
            }

            // --- Fill form with fetched data ---
            function fillForm(data) {
                const {
                    applicant,
                    address,
                    economic,
                    health
                } = data;

                if (applicant) {
                    setField('lname', applicant.last_name);
                    setField('fname', applicant.first_name);
                    setField('mname', applicant.middle_name);
                    setField('gender', applicant.gender);
                    setField('age', applicant.age);
                    setField('civil_status', applicant.civil_status);
                    setField('birth_date', applicant.birth_date);
                    setField('citizenship', applicant.citizenship);
                    setField('birth_place', applicant.birth_place);
                    setField('living_arrangement', applicant.living_arrangement);
                }

                if (address) {
                    setField('house_no', address.house_no);
                    setField('street', address.street);
                    setField('barangay', address.barangay);
                    setField('municipality', address.municipality);
                    setField('province', address.province);
                }

                if (economic) {
                    setField('is_pensioner', economic.is_pensioner);
                    setField('pension_amount', economic.pension_amount);
                    setField('pension_source', economic.pension_source);
                    setField('has_permanent_income', economic.has_permanent_income);
                    setField('income_source', economic.income_source);
                    setField('has_family_support', economic.has_family_support);
                    setField('support_type', economic.support_type);
                    setField('support_cash', economic.support_cash);
                    setField('support_in_kind', economic.support_in_kind);
                }

                if (health) {
                    setField('has_existing_illness', health.has_existing_illness);
                    setField('illness_details', health.illness_details);
                    setField('hospitalized_last6mos', health.hospitalized_last6mos);
                }
            }

            // --- Load applicant data from PHP ---
            async function loadApplicant() {
                try {
                    const res = await fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/senior_edit.php?id=${encodeURIComponent(applicantId)}`);
                    const json = await res.json();
                    if (json.success && json.data) {
                        fillForm(json.data);
                    } else {
                        alert(json.message || 'Failed to fetch applicant data.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error loading applicant data.');
                }
            }

            loadApplicant();

            // --- Submit form (update applicant) ---
            window.submitForm = async function() {
                const fd = new FormData(form);
                fd.append('id', applicantId);

                try {
                    const res = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/senior_edit.php', {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();
                    if (json.success) {
                        alert(json.message || 'Applicant updated successfully!');
                        window.location.reload();
                    } else {
                        alert(json.message || 'Update failed.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error updating applicant.');
                }
            };
        });
    </script>


</body>

</html>