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
    <title>Senior - Inactive List</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <style>
        /* Enhanced logo styling for page display */
        .highlighted-logo {
            filter: 
                brightness(1.3)      /* Make brighter */
                contrast(1.2)        /* Increase contrast */
                saturate(1.5)        /* Make colors more vibrant */
                drop-shadow(0 0 8px #3b82f6)  /* Blue glow */
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
                        <ul id="dropdown-pages" class=" py-2 space-y-2">
                            <li>
                                <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center p-2 pl-11 w-full text-base text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                                    <i class="fas fa-times-circle mr-2 text-sm  text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>Inactive List
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
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                        <div
                            class="flex flex-col md:flex-col justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <h4 class="text-xl font-medium dark:text-white">Inactive List</h4>
                            <div class="flex flex-row justify-between mt-2">
                                <!-- Search  -->
                                <div class="w-full md:w-1/2">
                                    <form class="flex items-center">
                                        <label for="deceased-search" class="sr-only">Search</label>
                                        <div class="relative w-full">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                    fill="currentColor" viewbox="0 0 20 20"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 
                                                    4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input type="text" id="deceased-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                                            focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2
                                            dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                            dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                placeholder="Search deceased..." />
                                        </div>
                                    </form>
                                </div>
                                <!-- Filter  -->
                                <div class="relative w-full md:w-auto">
                                    <button id="filterDropdownButton" class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium 
                                    text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 
                                    hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 
                                    dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                            class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 
                                            01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 
                                            2A1 1 0 018 17v-5.586L3.293 
                                            6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                                        </svg>
                                        Filter
                                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    <!-- Dropdown -->
                                    <div id="filterDropdownMenu"
                                        class="hidden absolute z-10 mt-2 w-44 bg-white rounded-lg shadow dark:bg-gray-800">
                                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button data-filter="az"
                                                    class="block w-full cursor-pointer px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                                    A–Z
                                                </button>
                                            </li>
                                            <li>
                                                <button data-filter="recent"
                                                    class="block w-full px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                                    Recently Deceased
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="">
                            <table id="deceasedTable" class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                                <thead
                                    class="text-xs text-center text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">No.</th>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Birthdate</th>
                                        <th class="px-4 py-3">Age</th>
                                        <th class="px-4 py-3">Gender</th>
                                        <th class="px-4 py-3">Civil Status</th>
                                        <th class="px-4 py-3">Date of Inactive</th>
                                        <th class="px-4 py-3">Inactive Reason</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="deceasedBody"></tbody>
                            </table>
                        </div>
                        <nav id="deceasedPagination" class="flex flex-col md:flex-row justify-between items-start md:items-center 
                            space-y-3 md:space-y-0 p-3" aria-label="Table navigation"></nav>
                    </div>
                </div>
            </section>
            <!-- PopUp Message  -->
            <div id="popupModal"
                class="fixed inset-0 bg-gray-600/50 bg-opacity-50 flex items-center justify-center hidden z-50">
                <div id="popupBox"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg transform scale-95 opacity-0 transition-all duration-200 w-80 p-4">
                    <h2 id="popupTitle" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Title</h2>
                    <p id="popupMessage" class="text-sm text-gray-700 dark:text-white mb-4">Message</p>
                    <div class="flex justify-end">
                        <button id="popupCloseBtn"
                            class="px-4 py-1 bg-blue-600 cursor-pointer dark:text-white text-white text-xs rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                            OK
                        </button>
                    </div>
                </div>
            </div>
            <!--  Confirmation Modal -->
            <div id="confirmModal"
                class="fixed inset-0 bg-gray-600/50 bg-opacity-40 hidden flex z-50 items-center dark:text-white justify-center">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out"
                    id="confirmBox">
                    <h2 id="confirmTitle" class="text-xl font-semibold mb-3 text-gray-800">Please Confirm</h2>
                    <p id="confirmMessage" class="text-gray-600 mb-6 leading-relaxed"></p>
                    <div class="flex justify-center gap-3">
                        <button id="confirmCancelBtn"
                            class="px-4 py-1 bg-blue-600 cursor-pointer text-white text-xs rounded-sm font-medium hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Cancel
                        </button>
                        <button id="confirmOkBtn"
                            class="px-4 py-1 bg-green-600 cursor-pointer text-white text-xs rounded-sm font-medium hover:bg-green-700 transition-all focus:outline-none focus:ring-2 focus:ring-green-400">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
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
        document.addEventListener("DOMContentLoaded", () => {
            const tbody = document.getElementById("deceasedBody");
            const searchInput = document.getElementById("deceased-search");
            const pagination = document.getElementById("deceasedPagination");
            const filterMenu = document.getElementById("filterDropdownMenu");
            const filterBtn = document.getElementById("filterDropdownButton");

            let currentPage = 1;
            let totalPages = 1;
            let totalRecords = 0;
            let currentSearch = "";
            let currentFilter = "";

            // ---------------- POPUP MODAL ----------------
            function showPopup(message, type = "info", redirect = false) {
                const modal = document.getElementById("popupModal");
                const box = document.getElementById("popupBox");
                const title = document.getElementById("popupTitle");
                const msg = document.getElementById("popupMessage");
                const closeBtn = document.getElementById("popupCloseBtn");

                msg.textContent = message;

                title.className = "text-xl font-semibold mb-3";
                msg.style.color = "#333333";

                if (type === "success") {
                    title.textContent = "✅ Success";
                    closeBtn.style.background = "#27AE60";
                } else if (type === "error") {
                    title.textContent = "❌ Error";
                    closeBtn.style.background = "#E74C3C";
                } else {
                    title.textContent = "ℹ️ Notice";
                    closeBtn.style.background = "#3498DB";
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
                        if (redirect) window.location.href = redirect;
                    }, 200);
                };
            }

            // ---------------- CONFIRM MODAL ----------------
            function showConfirm(message, title = "Please Confirm") {
                return new Promise((resolve) => {
                    const modal = document.getElementById("confirmModal");
                    const box = document.getElementById("confirmBox");
                    const titleEl = document.getElementById("confirmTitle");
                    const msg = document.getElementById("confirmMessage");
                    const okBtn = document.getElementById("confirmOkBtn");
                    const cancelBtn = document.getElementById("confirmCancelBtn");

                    titleEl.textContent = title;
                    msg.textContent = message;

                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        box.classList.remove("scale-95", "opacity-0");
                        box.classList.add("scale-100", "opacity-100");
                    }, 10);

                    const closeModal = (result) => {
                        box.classList.add("scale-95", "opacity-0");
                        setTimeout(() => {
                            modal.classList.add("hidden");
                            resolve(result);
                        }, 200);
                    };

                    okBtn.onclick = () => closeModal(true);
                    cancelBtn.onclick = () => closeModal(false);
                });
            }

            // ---------------- FILTER & SEARCH ----------------
            filterBtn.addEventListener("click", () => filterMenu.classList.toggle("hidden"));
            filterMenu.querySelectorAll("[data-filter]").forEach(btn => {
                btn.addEventListener("click", () => {
                    currentFilter = btn.dataset.filter;
                    filterMenu.classList.add("hidden");
                    fetchDeceased();
                });
            });

            let searchTimeout;
            searchInput.addEventListener("input", e => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = e.target.value.trim();
                    currentPage = 1;
                    fetchDeceased();
                }, 400);
            });

            // ---------------- PAGINATION WITH TOOLTIP ----------------
            function renderPagination(start, end) {
                if (totalPages <= 1) {
                    pagination.innerHTML = "";
                    return;
                }

                let html = `
            <span class="text-sm text-gray-600 dark:text-gray-400">
                Showing <b>${start}</b>–<b>${end}</b> of <b>${totalRecords}</b>
            </span>
            <ul class="inline-flex items-stretch -space-x-px">
        `;

                // Previous button with tooltip
                html += `
            <li class="relative group">
                <button ${currentPage === 1 ? "disabled" : ""} data-nav="prev"
                    class="flex items-center cursor-pointer justify-center py-[7px] px-2 text-gray-500 bg-white border border-gray-300 rounded-l-sm hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 text-xs text-black text-center font-medium w-24 dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                    Previous page
                </span>
            </li>
        `;

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    html += `
                <li>
                    <button data-page="${i}" class="flex items-center justify-center text-sm py-2 px-3 leading-tight ${i === currentPage
                            ? 'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
                        ${i}
                    </button>
                </li>
            `;
                }

                // Next button with tooltip
                html += `
            <li class="relative group">
                <button ${currentPage === totalPages ? "disabled" : ""} data-nav="next"
                    class="flex items-center cursor-pointer justify-center py-[7px] px-2 text-gray-500 bg-white border border-gray-300 rounded-r-sm hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 text-xs text-black text-center font-medium w-20 dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                    Next page
                </span>
            </li>
        </ul>`;

                pagination.innerHTML = html;

                pagination.querySelectorAll("[data-page]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        currentPage = parseInt(btn.dataset.page);
                        fetchDeceased();
                    });
                });

                pagination.querySelectorAll("[data-nav]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        if (btn.dataset.nav === "prev" && currentPage > 1) currentPage--;
                        else if (btn.dataset.nav === "next" && currentPage < totalPages) currentPage++;
                        fetchDeceased();
                    });
                });
            }

            // ---------------- FETCH DECEASED ----------------
            function fetchDeceased() {
                const params = new URLSearchParams({
                    page: currentPage,
                    search: currentSearch,
                    filter: currentFilter
                });
                fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/inactivelist/fetch_inactive.php?${params}`)
                    .then(res => res.json())
                    .then(data => {
                        tbody.innerHTML = "";
                        totalRecords = data.total_records;
                        totalPages = data.total_pages;

                        if (!data.deceased || data.deceased.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-gray-500">No Inactive records found.</td></tr>`;
                            pagination.innerHTML = "";
                            return;
                        }

                        data.deceased.forEach((row, index) => {
                            const statusColor =
                                row.validation === "Validated" ? "text-green-600" :
                                row.validation === "For Validation" ? "text-red-600" :
                                "text-red-600";
                            const buttonId = `dropdownBtn-${index}`;
                            const dropdownId = `dropdownMenu-${index}`;
                            const tr = `
                        <tr class="border-b text-xs font-medium text-center border-gray-200 relative">
                            <td>${row.rownum}</td>
                            <td>${row.full_name}</td>
                            <td>${row.birth_date || ""}</td>
                            <td>${row.age || ""}</td>
                            <td>${row.gender || ""}</td>
                            <td>${row.civil_status || ""}</td>
                            <td>${row.date_of_inactive || ""}</td>
                            <td>${row.inactive_reason || ""}</td>
                            <td class="${statusColor}">${row.validation}</td>
                            <td class="relative">
                                <button id="${buttonId}" class="inline-flex cursor-pointer items-center p-1 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <div id="${dropdownId}" class="hidden absolute right-0 top-8 z-50 w-44 bg-white rounded shadow-lg">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li><button onclick="undoDeceased('${row.applicant_id}', '${row.full_name}')" class="block cursor-pointer w-full text-left px-4 py-2 hover:bg-gray-100" title="Return to active list">↩️ Undo</button></li>
                                        <li><button onclick="archiveDeceased('${row.applicant_id}')" class="block w-full text-left cursor-pointer px-4 py-2 hover:bg-gray-100" title="Send to archive">🗃️ Archive</button></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>`;
                            tbody.insertAdjacentHTML("beforeend", tr);

                            const button = document.getElementById(buttonId);
                            const menu = document.getElementById(dropdownId);
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
                    .catch(() => {
                        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-red-500 py-4">Error loading data.</td></tr>`;
                    });
            }

            // ---------------- UNDO & ARCHIVE ----------------
            window.undoDeceased = async (id, name) => {
                const confirm = await showConfirm(`Return ${name} to the active list?`, "Confirm Undo");
                if (!confirm) return;
                try {
                    const res = await fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/inactivelist/undo_inactive.php?id=${id}`, {
                        method: "POST"
                    });
                    const data = await res.json();
                    showPopup(data.message || "Status updated successfully.", "success");
                    fetchDeceased();
                } catch {
                    showPopup("Error updating record.", "error");
                }
            };

            window.archiveDeceased = async (id) => {
                const confirm = await showConfirm("Are you sure you want to archive this record?", "Confirm Archive");
                if (!confirm) return;

                const loader = document.createElement("div");
                loader.textContent = "Archiving...";
                loader.className = "fixed bottom-5 right-5 bg-gray-800 text-white px-4 py-2 rounded shadow-md text-sm z-50";
                document.body.appendChild(loader);

                try {
                    const formData = new FormData();
                    formData.append("id", id);

                    const response = await fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/archived/archived.php`, {
                        method: "POST",
                        body: formData,
                    });

                    const result = await response.json();
                    loader.remove();

                    if (!response.ok || !result.success) {
                        showPopup(result.message || "Failed to archive record.", "error");
                        return;
                    }

                    showPopup(result.message || "Record archived successfully.", "success");
                    fetchDeceased();
                } catch {
                    loader.remove();
                    showPopup("Network or server error while archiving record.", "error");
                }
            };

            fetchDeceased();
        });
    </script>
</body>

</html>