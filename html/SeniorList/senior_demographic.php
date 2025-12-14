<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database connection
require_once '../../php/db_config.php';

// Get applicant ID from URL
$applicant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($applicant_id <= 0) {
    header("Location: activelist.php");
    exit();
}

// Fetch comprehensive senior data
$senior_data = [];

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch main applicant information
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name,
            DATE_FORMAT(a.birth_date, '%M %d, %Y') as formatted_birth_date,
            YEAR(CURDATE()) - YEAR(a.birth_date) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(a.birth_date, '%m%d')) as calculated_age
        FROM applicants a 
        WHERE a.applicant_id = ?
    ");
    $stmt->execute([$applicant_id]);
    $senior_data['applicant'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch address information
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['address'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch demographic information
    $stmt = $pdo->prepare("SELECT * FROM applicant_demographics WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['demographic'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch educational background
    $stmt = $pdo->prepare("SELECT * FROM applicant_educational_background WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['education'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch registration details
    $stmt = $pdo->prepare("SELECT * FROM applicant_registration_details WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['registration'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch economic status
    $stmt = $pdo->prepare("SELECT * FROM economic_status WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['economic'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch health condition
    $stmt = $pdo->prepare("SELECT * FROM health_condition WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['health'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch illnesses
    $stmt = $pdo->prepare("SELECT * FROM senior_illness WHERE applicant_id = ? ORDER BY illness_date DESC");
    $stmt->execute([$applicant_id]);
    $senior_data['illnesses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch benefits distribution
    $stmt = $pdo->prepare("
        SELECT bd.*, b.benefit_name 
        FROM benefits_distribution bd 
        LEFT JOIN benefits b ON bd.benefit_id = b.id 
        WHERE bd.applicant_id = ? 
        ORDER BY bd.distribution_date DESC
    ");
    $stmt->execute([$applicant_id]);
    $senior_data['benefits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$senior_data['applicant']) {
        header("Location: activelist.php");
        exit();
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function formatYesNo($value)
{
    if ($value == 1 || $value === true) return '<span class="text-green-600 font-semibold">Yes</span>';
    if ($value == 0 || $value === false) return '<span class="text-red-600 font-semibold">No</span>';
    return '<span class="text-gray-500">Not specified</span>';
}

function formatEmpty($value)
{
    return !empty($value) ? $value : '<span class="text-gray-500 italic">Not specified</span>';
}

function formatDate($date)
{
    if (!$date || $date == '0000-00-00') return '<span class="text-gray-500 italic">Not specified</span>';
    return date('F d, Y', strtotime($date));
}

function formatStatus($status)
{
    $statusClasses = [
        'Active' => 'bg-green-100 text-green-800',
        'Inactive' => 'bg-yellow-100 text-yellow-800',
        'Deceased' => 'bg-red-100 text-red-800'
    ];
    $class = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $status . '</span>';
}

function formatValidation($validation)
{
    if ($validation == 'Validated') {
        return '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Validated</span>';
    }
    return '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">For Validation</span>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Demographic - <?php echo htmlspecialchars($senior_data['applicant']['full_name'] ?? 'Senior'); ?></title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased">
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
                        <a href="../index.php?session_context=<?php echo $ctx; ?>"
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
                                <a href="#" style="color: blue;"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-blue-700 bg-blue-100 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
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
            <!-- Header with navigation -->
            <div class="w-full flex justify-between items-center mb-6">
                <a href="./activelist.php?session_context=<?php echo $ctx; ?>"
                    class="text-white flex flex-row items-center cursor-pointer bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to List
                </a>

                <div class="flex items-center space-x-4">
                    <a href="senior_view.php?session_context=<?php echo $ctx; ?>&id=<?php echo $applicant_id; ?>"
                        class="text-lg font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition duration-200">
                        <i class="fas fa-file-alt mr-2"></i>Application
                    </a>
                    <a href="#" class="text-lg font-medium text-blue-700 dark:text-blue-400">
                        <i class="fas fa-chart-pie mr-2"></i>Demographic
                    </a>
                </div>

                <div class="relative">
                    <button id="actionDropdownButton" data-dropdown-toggle="actionDropdown"
                        class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200"
                        type="button">
                        <i class="fas fa-ellipsis-v mr-2"></i>
                        Actions
                    </button>

                    <div id="actionDropdown"
                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                            <li>
                                <a href="senior_edit.php?session_context=<?php echo $ctx; ?>&id=<?php echo $applicant_id; ?>"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-edit mr-2"></i>Edit Information
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="window.print()"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-print mr-2"></i>Print Profile
                                </a>
                            </li>
                            <li>
                                <a href="#" data-modal-target="deleteModal" data-modal-toggle="deleteModal"
                                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <i class="fas fa-archive mr-2"></i>Archive Senior
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Senior Profile Header -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-blue-300 dark:from-blue-900 dark:to-blue-700 flex items-center justify-center text-4xl text-blue-600 dark:text-blue-300">
                            <?php
                            $first_letter = strtoupper(substr($senior_data['applicant']['first_name'] ?? '?', 0, 1));
                            $last_letter = strtoupper(substr($senior_data['applicant']['last_name'] ?? '?', 0, 1));
                            echo $first_letter . $last_letter;
                            ?>
                        </div>
                        <div class="absolute -bottom-2 -right-2">
                            <?php echo formatStatus($senior_data['applicant']['status'] ?? 'Active'); ?>
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($senior_data['applicant']['full_name'] ?? ''); ?>
                                    <?php if (!empty($senior_data['applicant']['suffix'])): ?>
                                        <span class="text-gray-600"><?php echo htmlspecialchars($senior_data['applicant']['suffix']); ?></span>
                                    <?php endif; ?>
                                </h1>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">
                                    <i class="fas fa-id-card mr-2"></i>
                                    <?php if (!empty($senior_data['applicant']['control_number'])): ?>
                                        Control #: <?php echo htmlspecialchars($senior_data['applicant']['control_number']); ?> |
                                    <?php endif; ?>
                                    ID #: <?php echo htmlspecialchars($senior_data['registration']['id_number'] ?? 'N/A'); ?>
                                </p>
                                <div class="flex items-center space-x-4 mt-3">
                                    <span class="flex items-center text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-birthday-cake mr-2"></i>
                                        <?php echo formatDate($senior_data['applicant']['birth_date'] ?? ''); ?>
                                        (<?php echo $senior_data['applicant']['calculated_age'] ?? $senior_data['applicant']['age'] ?? '?'; ?> years)
                                    </span>
                                    <span class="flex items-center text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-<?php echo ($senior_data['applicant']['gender'] == 'Male') ? 'mars' : 'venus'; ?> mr-2"></i>
                                        <?php echo htmlspecialchars($senior_data['applicant']['gender'] ?? ''); ?>
                                    </span>
                                    <span class="flex items-center text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-heart mr-2"></i>
                                        <?php echo htmlspecialchars($senior_data['applicant']['civil_status'] ?? 'Not specified'); ?>
                                    </span>
                                    <?php echo formatValidation($senior_data['applicant']['validation'] ?? 'For Validation'); ?>
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Registered on</p>
                                <p class="text-gray-700 dark:text-gray-300 font-medium">
                                    <?php echo date('F d, Y', strtotime($senior_data['applicant']['date_created'] ?? 'now')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demographic Information Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Column 1: Personal & Demographics -->
                <div class="space-y-6">
                    <!-- Personal Information Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-user-circle mr-3"></i>Personal Information
                            </h2>
                            <span class="text-sm text-gray-500">Last updated: <?php echo date('M d, Y', strtotime($senior_data['applicant']['date_modified'] ?? 'now')); ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Citizenship</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['applicant']['citizenship']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Birth Place</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['applicant']['birth_place']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Religion</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['applicant']['religion']); ?></p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Living Arrangement</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['applicant']['living_arrangement']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Educational Attainment</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php
                                        $edu = $senior_data['education']['educational_attainment'] ?? $senior_data['applicant']['educational_attainment'];
                                        echo formatEmpty($edu);
                                        ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Date Registered</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatDate($senior_data['registration']['date_of_registration'] ?? $senior_data['applicant']['date_created']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-home mr-3"></i>Address Information
                        </h2>

                        <div class="space-y-4">
                            <div class="flex items-start space-x-4">
                                <div class="text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-map-marker-alt text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Complete Address</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php
                                        $address_parts = [];
                                        if (!empty($senior_data['address']['house_no'])) $address_parts[] = $senior_data['address']['house_no'];
                                        if (!empty($senior_data['address']['street'])) $address_parts[] = $senior_data['address']['street'];
                                        if (!empty($senior_data['address']['barangay'])) $address_parts[] = $senior_data['address']['barangay'];
                                        if (!empty($senior_data['address']['municipality'])) $address_parts[] = $senior_data['address']['municipality'];
                                        if (!empty($senior_data['address']['province'])) $address_parts[] = $senior_data['address']['province'];

                                        if (empty($address_parts)) {
                                            echo '<span class="text-gray-500 italic">Not specified</span>';
                                        } else {
                                            echo implode(', ', array_filter($address_parts));
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Barangay</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['address']['barangay']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Municipality</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['address']['municipality']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Province</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['address']['province']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Local Control #</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['registration']['local_control_number']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Demographic Profile Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-users mr-3"></i>Demographic Profile
                        </h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">IP Member</p>
                                <p class="font-medium"><?php echo formatYesNo($senior_data['demographic']['is_ip_member'] ?? false); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">IP Group</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['demographic']['ip_group']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tribal Affiliation</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['demographic']['tribal_affiliation']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Dialect Spoken</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['demographic']['dialect_spoken']); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($senior_data['education']['school_name'])): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Educational Background</p>
                                <div class="space-y-2">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <i class="fas fa-graduation-cap mr-2"></i>
                                        <?php echo htmlspecialchars($senior_data['education']['school_name']); ?>
                                        <?php if (!empty($senior_data['education']['year_graduated'])): ?>
                                            (<?php echo htmlspecialchars($senior_data['education']['year_graduated']); ?>)
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($senior_data['education']['course_taken'])): ?>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                                            Course: <?php echo htmlspecialchars($senior_data['education']['course_taken']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Economic, Health & Benefits -->
                <div class="space-y-6">
                    <!-- Economic Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-chart-line mr-3"></i>Economic Status
                        </h2>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pensioner</p>
                                    <p class="font-medium"><?php echo formatYesNo($senior_data['economic']['is_pensioner'] ?? false); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pension Amount</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php
                                        if (!empty($senior_data['economic']['pension_amount'])) {
                                            echo '₱' . number_format($senior_data['economic']['pension_amount'], 2);
                                        } else {
                                            echo formatEmpty(null);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($senior_data['economic']['pension_source'])): ?>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pension Source</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($senior_data['economic']['pension_source']); ?>
                                        <?php if (!empty($senior_data['economic']['pension_source_other'])): ?>
                                            (<?php echo htmlspecialchars($senior_data['economic']['pension_source_other']); ?>)
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Permanent Income</p>
                                    <p class="font-medium"><?php echo formatYesNo($senior_data['economic']['has_permanent_income'] ?? false); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Family Support</p>
                                    <p class="font-medium"><?php echo formatYesNo($senior_data['economic']['has_family_support'] ?? false); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($senior_data['economic']['income_source'])): ?>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Income Source</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior_data['economic']['income_source']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($senior_data['economic']['support_type'])): ?>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Support Type</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior_data['economic']['support_type']); ?></p>
                                    <?php if (!empty($senior_data['economic']['support_cash'])): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Cash: <?php echo htmlspecialchars($senior_data['economic']['support_cash']); ?>
                                            <?php if (!empty($senior_data['economic']['support_in_kind'])): ?>
                                                | In-kind: <?php echo htmlspecialchars($senior_data['economic']['support_in_kind']); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Health Condition Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-heartbeat mr-3"></i>Health Condition
                        </h2>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Existing Illness</p>
                                    <p class="font-medium"><?php echo formatYesNo($senior_data['health']['has_existing_illness'] ?? false); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Recent Hospitalization</p>
                                    <p class="font-medium"><?php echo formatYesNo($senior_data['health']['hospitalized_last6mos'] ?? false); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($senior_data['health']['illness_details'])): ?>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Illness Details</p>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior_data['health']['illness_details']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($senior_data['illnesses'])): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Illness History</p>
                                    <div class="space-y-2">
                                        <?php foreach ($senior_data['illnesses'] as $illness): ?>
                                            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                                <span class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($illness['illness_name']); ?></span>
                                                <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($illness['illness_date'])); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Benefits Received Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-gift mr-3"></i>Benefits Received
                        </h2>

                        <?php if (!empty($senior_data['benefits'])): ?>
                            <div class="space-y-3">
                                <?php foreach ($senior_data['benefits'] as $benefit): ?>
                                    <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($benefit['benefit_name']); ?></p>
                                            <p class="text-sm text-gray-500">Received: <?php echo date('F d, Y', strtotime($benefit['distribution_date'])); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-blue-700 dark:text-blue-300">₱<?php echo number_format($benefit['amount'], 2); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($benefit['created_at'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-gift text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400">No benefits recorded yet</p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Benefits Received</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ₱<?php
                                    $total = 0;
                                    foreach ($senior_data['benefits'] as $benefit) {
                                        $total += $benefit['amount'];
                                    }
                                    echo number_format($total, 2);
                                    ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-info-circle mr-3"></i>Additional Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Remarks</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo formatEmpty($senior_data['applicant']['remarks']); ?></p>
                        </div>
                        <?php if ($senior_data['applicant']['status'] == 'Deceased' && !empty($senior_data['applicant']['date_of_death'])): ?>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Date of Death</p>
                                <p class="font-medium text-red-600"><?php echo formatDate($senior_data['applicant']['date_of_death']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($senior_data['applicant']['status'] == 'Inactive'): ?>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Inactive Reason</p>
                                <p class="font-medium text-yellow-600"><?php echo formatEmpty($senior_data['applicant']['inactive_reason']); ?></p>
                                <?php if (!empty($senior_data['applicant']['date_of_inactive'])): ?>
                                    <p class="text-xs text-gray-500">Since: <?php echo formatDate($senior_data['applicant']['date_of_inactive']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Registration Status</p>
                            <p class="font-medium"><?php echo formatEmpty($senior_data['registration']['registration_status']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Approval Date</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo formatDate($senior_data['registration']['approval_date']); ?></p>
                        </div>
                        <?php if (!empty($senior_data['registration']['registration_remarks'])): ?>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Registration Remarks</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($senior_data['registration']['registration_remarks']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Record Created</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo date('F d, Y', strtotime($senior_data['applicant']['date_created'])); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($senior_data['applicant']['date_created'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Last Modified</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo date('F d, Y', strtotime($senior_data['applicant']['date_modified'])); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($senior_data['applicant']['date_modified'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Age Last Updated</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo formatDate($senior_data['applicant']['age_last_updated']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="mt-6 flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>Senior ID: <span class="font-medium text-gray-700 dark:text-gray-300"><?php echo $applicant_id; ?></span></p>
                </div>
                <div class="flex space-x-3">
                    <a href="senior_edit.php?session_context=<?php echo $ctx; ?>&id=<?php echo $applicant_id; ?>"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 transition duration-200">
                        <i class="fas fa-edit mr-2"></i>Edit Information
                    </a>
                    <button onclick="window.print()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 transition duration-200">
                        <i class="fas fa-print mr-2"></i>Print Profile
                    </button>
                    <button data-modal-target="archiveModal" data-modal-toggle="archiveModal"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-red-700 hover:bg-red-800 rounded-lg focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800 transition duration-200">
                        <i class="fas fa-archive mr-2"></i>Archive
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="p-6 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Are you sure you want to archive this senior?</h3>
                    <p class="text-sm text-gray-500 mb-6">This will move the senior to the archived list. The record can be restored later if needed.</p>
                    <form action="../../php/seniorlist/archive_senior.php" method="POST" class="inline">
                        <input type="hidden" name="applicant_id" value="<?php echo $applicant_id; ?>">
                        <input type="hidden" name="session_context" value="<?php echo $ctx; ?>">
                        <button type="submit"
                            class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2">
                            <i class="fas fa-check mr-2"></i>Yes, archive it
                        </button>
                    </form>
                    <button data-modal-hide="archiveModal" type="button"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                        <i class="fas fa-times mr-2"></i>No, cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        // Update the page title with senior name
        document.title = "<?php echo htmlspecialchars($senior_data['applicant']['full_name'] ?? 'Senior'); ?> - Demographic Profile";

        // Add active class to current page in sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.href;
            const sidebarLinks = document.querySelectorAll('aside a');

            sidebarLinks.forEach(link => {
                if (link.href === currentUrl) {
                    link.classList.add('bg-blue-100', 'dark:bg-blue-900');
                    link.classList.add('text-blue-700', 'dark:text-blue-300');
                }
            });
        });
    </script>
</body>

</html>