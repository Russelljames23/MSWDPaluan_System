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
            z-index: 40;
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

        .dark .links_name {
            color: #d1d5db;
        }

        /* Active link styling */
        .nav-list li #accounts.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #accounts.active-link svg {
            color: #1d4ed8;
        }

        .dark .nav-list li #accounts.active-link {
            color: #60a5fa;
            /* blue-400 */
            border-color: #3b82f6;
            /* blue-500 */
            background: #1e40af;
            /* blue-800 */
        }

        .dark .nav-list li #accounts.active-link svg {
            color: #60a5fa;
            /* blue-400 */
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
        <main class="p-4 md:ml-64 pt-20">
            <div class="flex flex-row justify-between">
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
                            <a href="#" id="accounts" class="cursor-pointer active-link">
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
                            <a href="systemlogs.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
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
                <!-- accounts  -->
                <section id="accountSection" class="bg-gray-50 dark:bg-gray-900  w-full">
                    <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                            <div class=" py-2">
                                <p class="text-2xl font-semibold px-5 text-gray-900 dark:text-white">Accounts</p>
                                <!-- Close Button (top-right) -->
                                <div class="absolute top-2 right-2 group w-fit h-fit">
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-900 cursor-pointer inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white "
                                        data-dismiss-target="#toast-default" aria-label="Close1">
                                        <span class="sr-only">Close</span>
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                    <!-- Tooltip -->
                                    <span
                                        class="absolute -top-7 right-1/2 translate-x-1/2 hidden group-hover:block px-2 py-1 text-[14px] text-gray-900">
                                        Close
                                    </span>
                                </div>
                            </div>

                            <!-- Search and Controls -->
                            <div
                                class="flex flex-col md:flex-row items-center justify-between p-4 space-y-3 md:space-y-0 md:space-x-4">
                                <!-- Search -->
                                <div class="w-full md:w-1/2">
                                    <form class="flex items-center">
                                        <label for="simple-search" class="sr-only">Search</label>
                                        <div class="relative w-full">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817
                        4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input type="text" id="simple-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                            focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 
                            dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white 
                            dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search"
                                                required="">
                                        </div>
                                    </form>
                                </div>

                                <!-- Buttons -->
                                <div class="w-full md:w-auto flex flex-col md:flex-row items-stretch md:items-center justify-end 
                                                    space-y-2 md:space-y-0 md:space-x-3 flex-shrink-0">
                                    <!-- Add Benefits -->
                                    <button id="defaultModalButton" data-modal-target="defaultModal"
                                        data-modal-toggle="defaultModal"
                                        class="flex items-center cursor-pointer justify-center text-white bg-blue-700 hover:bg-blue-800 
                                                         font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700">
                                        <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0
                                                       11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                        </svg>
                                        Add Account
                                    </button>

                                    <!-- Filter Dropdown -->
                                    <!-- <div class="relative">
                                        <button onclick="toggleDropdown('filterDropdown')" class="flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 
                                                  bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 
                                                  dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
                                                  dark:hover:bg-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 
                                                   01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 
                                                    013 6V3z" clip-rule="evenodd" />
                                            </svg>
                                            Filter
                                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 
                                                    10.586l3.293-3.293a1 1 0 
                                                    111.414 1.414l-4 4a1 1 0 
                                                    01-1.414 0l-4-4a1 1 0 
                                                    010-1.414z" />
                                            </svg>
                                        </button>
                                        <div id="filterDropdown"
                                            class="absolute right-0 mt-2 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                                            <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Filter by
                                                address</h6>
                                            <ul class="space-y-2 text-sm" id="filterAddresses"></ul>
                                        </div>
                                    </div> -->
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <div class="overflow-x-auto">
                                    <table id="deceasedTable"
                                        class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs text-center text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3">Type</th>
                                                <th scope="col" class="px-15 py-3">Name</th>
                                                <th scope="col" class="px-15 py-3">Username</th>
                                                <th scope="col" class="px-4 py-3">Birthdate</th>
                                                <th scope="col" class="px-4 py-3">Gender</th>
                                                <th scope="col" class="px-4 py-3">Contact NO.</th>
                                                <th scope="col" class="px-4 py-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="px-4 py-3">Admin</td>
                                                <td class="px-4 py-3">Tadalan,RussellJames</td>
                                                <td class="px-4 py-3">russelljames23@gmail.com</td>
                                                <td class="px-4 py-3">01/07/1946</td>
                                                <td class="px-4 py-3">Male</td>
                                                <td class="px-4 py-3">9664750533</td>
                                                <td class="px-4 py-3 flex items-center justify-end">
                                                    <button id="apple-imac-27-dropdown-button"
                                                        class="inline-flex items-center cursor-pointer p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                                        type="button">
                                                        <svg class="w-6 h-6 text-gray-800 dark:text-white"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- add account modal -->
                <div id="defaultModal" tabindex="-1" aria-hidden="true"
                    class="hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-8 h-modal md:h-full ">
                    <div class="relative p-4 w-full max-w-2xl h-full md:h-auto ">
                        <!-- Modal content -->
                        <div class="relative py-3 px-4 bg-white rounded-lg shadow dark:bg-gray-800 ">
                            <!-- Modal header -->
                            <div
                                class="flex justify-between items-center pb-2 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Add Account
                                </h3>
                                <button type="button"
                                    class="text-gray-400 cursor-pointer bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                    data-modal-hide="defaultModal"> <!-- Changed from data-modal-toggle to data-modal-hide -->
                                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>
                            <!-- Modal body -->
                            <form action="#">
                                <div class="grid gap-1 sm:grid-cols-2 px-2 sm:gap-1 h-[442px] overflow-auto">
                                    <!-- fullname -->
                                    <div class=" sm:col-span-2 flex flex-col sm:flex-row gap-2 sm:gap-4">
                                        <div class="w-full">
                                            <label for="lastname"
                                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Lastname</label>
                                            <input type="text" name="lastname" id="lastname"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                placeholder="Enter lastname" required="">
                                        </div>
                                        <div class="w-full">
                                            <label for="firstname"
                                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Firstname</label>
                                            <input type="text" name="firstname" id="firstname"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                placeholder="Enter firstname" required="">
                                        </div>
                                        <div class="w-full">
                                            <label for="middlename"
                                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Middlename</label>
                                            <input type="text" name="middlename" id="middlename"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                placeholder="Enter middlename" required="">
                                        </div>
                                    </div>
                                    <div class="w-full">
                                        <label for="birthdate"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Birthdate</label>
                                        <input type="date" name="birthdate" id="birthdate"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="dd-mm-yyyy" required="">
                                        </select>
                                    </div>
                                    <div class="w-full">
                                        <label for="brand" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sex</label>
                                        <div class="flex flex-row justify-between items-center gap-5 bg-gray-50 border p-2.5 px-10 border-gray-300 text-gray-900 rounded-lg">
                                            <div class="flex items-center">
                                                <input id="default-radio-1" type="radio" value="Male" name="gender"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="default-radio-1" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Male</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input id="default-radio-2" type="radio" value="Female" name="gender" checked
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="default-radio-2" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Female</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-full">
                                        <label for="email"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                                        <input type="email" name="email" id="email"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Enter email" required="">
                                    </div>
                                    <div class="w-full">
                                        <label for="contact_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact no.</label>
                                        <input type="number" name="contact_no" id="contact_no"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="+639X-XXXX-XXXX" required="">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="address"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address</label>
                                        <input type="text" name="address" id="address"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Enter address" required="">
                                    </div>
                                    <div class="w-full">
                                        <label for="username"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
                                        <input type="text" name="username" id="username"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Enter Username" required="">
                                    </div>
                                    <div class="w-full">
                                        <label for="select-type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Type</label>
                                        <select id="select-type" name="user_type" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="" disabled selected>Select user type</option>
                                            <!-- <option value="Admin">Admin</option> -->
                                            <option value="Staff">Staff</option>
                                        </select>
                                    </div>
                                    <div class="w-full">
                                        <label for="password"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                                        <input type="password" name="password" id="password"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Enter Password" required="">
                                    </div>
                                    <div class="w-full">
                                        <label for="confirm-password"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm
                                            password</label>
                                        <input type="password" name="confirm-password" id="confirm-password"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Confirm password" required="">
                                    </div>
                                </div>
                                <div class="flex justify-end mt-2">
                                    <button type="submit"
                                        class="inline-flex items-center px-2.5 py-1.5 text-[13px] cursor-pointer font-medium text-center text-white bg-blue-700 rounded-sm focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
        // Fix DOMContentLoaded check
        document.addEventListener('DOMContentLoaded', function() {

            // Fix sidebar toggle - check if elements exist
            let sidebar = document.querySelector(".sidebar");
            let closeBtn = document.querySelector("#btn");

            if (closeBtn && sidebar) {
                closeBtn.addEventListener("click", () => {
                    sidebar.classList.toggle("open");
                });
            }

            // Fix edit/cancel buttons - check if elements exist
            const editBtn = document.getElementById("editBtn");
            const cancelBtn = document.getElementById("cancelBtn");
            const updateBtn = document.getElementById("updateBtn");

            if (editBtn) {
                editBtn.addEventListener("click", () => {
                    // Show inputs and hide labels
                    const labels = document.querySelectorAll(".profile-label");
                    const inputs = document.querySelectorAll(".profile-input");

                    labels.forEach((lbl) => lbl.classList.add("hidden"));
                    inputs.forEach((inp) => inp.classList.remove("hidden"));

                    // Toggle buttons
                    editBtn.classList.add("hidden");
                    if (cancelBtn) cancelBtn.classList.remove("hidden");
                    if (updateBtn) updateBtn.classList.remove("hidden");
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener("click", () => {
                    // Hide inputs and show labels
                    const labels = document.querySelectorAll(".profile-label");
                    const inputs = document.querySelectorAll(".profile-input");

                    labels.forEach((lbl) => lbl.classList.remove("hidden"));
                    inputs.forEach((inp) => inp.classList.add("hidden"));

                    // Toggle buttons
                    cancelBtn.classList.add("hidden");
                    if (updateBtn) updateBtn.classList.add("hidden");
                    if (editBtn) editBtn.classList.remove("hidden");
                });
            }

            // Initialize AccountsManager
            window.accountsManager = new AccountsManager();
        });

        class AccountsManager {
            constructor() {
                this.accounts = [];
                this.modal = null;
                this.isLoading = false;
                this.searchTimeout = null;
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.initModal();
                this.loadAccounts();
            }

            initModal() {
                try {
                    const modalElement = document.getElementById('defaultModal');
                    if (modalElement && typeof Modal !== 'undefined') {
                        this.modal = new Modal(modalElement);
                    }
                } catch (error) {
                    console.warn('Modal initialization failed:', error);
                }
            }

            setupEventListeners() {
                // Search functionality with debounce
                const searchInput = document.getElementById('simple-search');
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        this.debouncedSearch(e.target.value);
                    });
                }

                // Add account form submission
                const addAccountForm = document.querySelector('#defaultModal form');
                if (addAccountForm) {
                    addAccountForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.createAccount();
                    });
                }

                // Add account button - show modal
                const addAccountBtn = document.getElementById('defaultModalButton');
                if (addAccountBtn) {
                    addAccountBtn.addEventListener('click', () => {
                        this.openModal();
                    });
                }

                // Remove data-dismiss-target attribute that causes error
                const dismissButtons = document.querySelectorAll('[data-dismiss-target]');
                dismissButtons.forEach(button => {
                    button.removeAttribute('data-dismiss-target');
                    button.addEventListener('click', () => {
                        this.closeModal();
                    });
                });

                // Fix close button
                const closeAccountBtn = document.querySelector('[aria-label="Close1"]');
                if (closeAccountBtn) {
                    closeAccountBtn.addEventListener('click', () => {
                        const accountSection = document.getElementById('accountSection');
                        if (accountSection) {
                            accountSection.classList.add('hidden');
                        }
                        const accountLink = document.querySelector('#accounts');
                        if (accountLink) {
                            accountLink.classList.remove('active-link');
                        }
                    });
                }

                // Close modal on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isModalOpen()) {
                        this.closeModal();
                    }
                });

                // Real-time form validation
                this.setupFormValidation();
            }

            setupFormValidation() {
                const form = document.querySelector('#defaultModal form');
                if (!form) return;

                // Password confirmation validation
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm-password');

                if (password && confirmPassword) {
                    const validatePasswords = () => {
                        if (password.value && confirmPassword.value && password.value !== confirmPassword.value) {
                            confirmPassword.setCustomValidity('Passwords do not match');
                        } else {
                            confirmPassword.setCustomValidity('');
                        }
                    };

                    password.addEventListener('input', validatePasswords);
                    confirmPassword.addEventListener('input', validatePasswords);
                }

                // Email validation
                const email = document.getElementById('email');
                if (email) {
                    email.addEventListener('blur', () => {
                        if (email.value && !this.isValidEmail(email.value)) {
                            email.setCustomValidity('Please enter a valid email address');
                        } else {
                            email.setCustomValidity('');
                        }
                    });
                }
            }

            debouncedSearch(searchTerm) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.filterAccounts(searchTerm);
                }, 300);
            }

            isModalOpen() {
                const modal = document.getElementById('defaultModal');
                return modal && !modal.classList.contains('hidden');
            }

            openModal() {
                if (this.modal) {
                    this.modal.show();
                } else {
                    const modalElement = document.getElementById('defaultModal');
                    if (modalElement) {
                        modalElement.classList.remove('hidden');
                        modalElement.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    }
                }
            }

            closeModal() {
                // Use Flowbite modal if available
                if (this.modal) {
                    this.modal.hide();
                }

                // Manual cleanup
                const modalElement = document.getElementById('defaultModal');
                if (modalElement) {
                    modalElement.classList.add('hidden');
                    modalElement.setAttribute('aria-hidden', 'true');
                }

                this.removeAllBackdrops();
                this.resetForm();
                document.body.style.overflow = 'auto';
            }

            removeAllBackdrops() {
                const backdropSelectors = [
                    '[modal-backdrop]',
                    '.modal-backdrop',
                    '.fixed.inset-0',
                    '.bg-gray-900',
                    '.bg-opacity-50'
                ];

                backdropSelectors.forEach(selector => {
                    document.querySelectorAll(selector).forEach(element => element.remove());
                });
            }

            resetForm() {
                const form = document.querySelector('#defaultModal form');
                if (form) {
                    form.reset();
                    // Clear validation states
                    form.querySelectorAll(':invalid').forEach(element => {
                        element.setCustomValidity('');
                    });
                    // Reset gender selection
                    const femaleRadio = document.getElementById('default-radio-2');
                    if (femaleRadio) femaleRadio.checked = true;
                }
            }

            async loadAccounts() {
                if (this.isLoading) return;

                this.isLoading = true;
                this.showLoadingState();

                try {
                    // First, test the API
                    const testUrl = '/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts_api.php?test=1';
                    const testResponse = await fetch(testUrl);
                    const testText = await testResponse.text();

                    console.log('API test response:', testText);

                    // Try to parse as JSON
                    try {
                        const testData = JSON.parse(testText);
                        console.log('API test parsed:', testData);
                    } catch (e) {
                        console.error('API test returned non-JSON:', testText.substring(0, 100));
                        throw new Error('API returned HTML instead of JSON. Check PHP errors.');
                    }

                    // Now fetch accounts
                    const apiUrl = '/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts_api.php?action=get_accounts';
                    console.log('Fetching accounts from:', apiUrl);

                    const response = await fetch(apiUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        },
                        cache: 'no-cache'
                    });

                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);

                    // Get raw response text
                    const text = await response.text();
                    console.log('Raw response (first 500 chars):', text.substring(0, 500));

                    // Check if response is HTML (contains <html>, <!DOCTYPE, etc.)
                    if (text.trim().startsWith('<!') || text.includes('<html') || text.includes('<!DOCTYPE')) {
                        console.error('API returned HTML instead of JSON');
                        console.error('Full response:', text);
                        throw new Error('Server returned HTML page instead of JSON. Check for PHP errors or incorrect endpoint.');
                    }

                    // Try to parse as JSON
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Problematic text:', text.substring(0, 200));
                        throw new Error('Invalid JSON response from server: ' + e.message);
                    }

                    console.log('Parsed data:', data);

                    if (data.success === false) {
                        throw new Error(data.message || 'Request failed');
                    }

                    if (data.records) {
                        this.accounts = data.records;
                        this.renderAccounts();
                    } else {
                        this.accounts = [];
                        this.renderAccounts();
                        this.showNotification('No accounts found in system', 'info');
                    }
                } catch (error) {
                    console.error('Error loading accounts:', error);
                    this.showNotification('Error loading accounts: ' + error.message, 'error');
                } finally {
                    this.isLoading = false;
                    this.hideLoadingState();
                }
            }

            showLoadingState() {
                const tbody = document.querySelector('#deceasedTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-3 text-center">
                        <div class="flex justify-center items-center">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                            <span class="ml-2">Loading accounts...</span>
                        </div>
                    </td>
                </tr>
            `;
                }
            }

            hideLoadingState() {
                // Loading state is automatically replaced when rendering
            }

            renderAccounts(accounts = this.accounts) {
                const tbody = document.querySelector('#deceasedTable tbody');
                if (!tbody) return;

                if (accounts.length === 0) {
                    tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            <span class="text-lg">No accounts found</span>
                        </div>
                    </td>
                </tr>
            `;
                    return;
                }

                tbody.innerHTML = accounts.map(account => `
            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <td class="px-4 py-3 items-center text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        account.user_type === 'Admin 1' ? 'bg-purple-100 text-purple-800' :
                        account.user_type === 'Admin 2' ? 'bg-blue-100 text-blue-800' :
                        'bg-green-100 text-green-800'
                    }">
                        ${this.escapeHtml(account.user_type)}
                    </span>
                </td>
                <td class="px-4 py-3 items-center text-center font-medium text-gray-900 dark:text-white">
                    ${this.escapeHtml(account.fullname)}
                </td>
                <td class="px-4 py-3 items-center text-center">${this.escapeHtml(account.username)}</td>
                <td class="px-4 py-3 items-center text-center">${this.escapeHtml(account.birthdate)}</td>
                <td class="px-4 py-3 items-center text-center">
                    <span class="inline-flex items-center">
                        ${account.gender === 'Male' ? 
                            '<svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>' :
                            '<svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm1 4a1 1 0 100 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>'
                        }
                        ${this.escapeHtml(account.gender)}
                    </span>
                </td>
                <td class="px-4 py-3">${this.escapeHtml(account.contact_no)}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end space-x-2">
                        <button onclick="window.accountsManager.editAccount(${account.id})" 
                                class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                title="Edit account">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="window.accountsManager.deleteAccount(${account.id})" 
                                class="p-1 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                title="Delete account">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
            }

            filterAccounts(searchTerm) {
                if (!searchTerm.trim()) {
                    this.renderAccounts();
                    return;
                }

                const filtered = this.accounts.filter(account =>
                    Object.values(account).some(value =>
                        value && value.toString().toLowerCase().includes(searchTerm.toLowerCase())
                    )
                );
                this.renderAccounts(filtered);
            }

            async createAccount() {
                if (this.isLoading) return;

                const accountData = this.getFormData();
                const validation = this.validateFormData(accountData);

                if (!validation.isValid) {
                    this.showNotification(validation.message, 'error');
                    return;
                }

                this.isLoading = true;
                this.showNotification('Creating account...', 'info');

                try {
                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(accountData)
                    });

                    const result = await this.parseResponse(response);

                    if (response.ok) {
                        this.handleCreateSuccess(result, accountData);
                    } else {
                        this.showNotification(result.message || 'Error creating account', 'error');
                    }
                } catch (error) {
                    console.error('Error creating account:', error);
                    this.showNotification('Error creating account: ' + error.message, 'error');
                } finally {
                    this.isLoading = false;
                }
            }

            getFormData() {
                return {
                    lastname: document.getElementById('lastname')?.value.trim() || '',
                    firstname: document.getElementById('firstname')?.value.trim() || '',
                    middlename: document.getElementById('middlename')?.value.trim() || '',
                    birthdate: document.getElementById('birthdate')?.value || '',
                    gender: this.getSelectedGender(),
                    email: document.getElementById('email')?.value.trim() || '',
                    contact_no: document.getElementById('contact_no')?.value.trim() || '',
                    address: document.getElementById('address')?.value.trim() || '',
                    username: document.getElementById('username')?.value.trim() || '',
                    password: document.getElementById('password')?.value || '',
                    user_type: document.getElementById('select-type')?.value || '',
                    created_by: null
                };
            }

            validateFormData(data) {
                const requiredFields = ['lastname', 'firstname', 'email', 'username', 'password', 'user_type'];
                const missingFields = requiredFields.filter(field => !data[field]);

                if (missingFields.length > 0) {
                    return {
                        isValid: false,
                        message: `Please fill all required fields: ${missingFields.join(', ')}`
                    };
                }

                if (!this.isValidEmail(data.email)) {
                    return {
                        isValid: false,
                        message: 'Please enter a valid email address'
                    };
                }

                const confirmPassword = document.getElementById('confirm-password')?.value;
                if (data.password !== confirmPassword) {
                    return {
                        isValid: false,
                        message: 'Passwords do not match'
                    };
                }

                if (data.password.length < 6) {
                    return {
                        isValid: false,
                        message: 'Password must be at least 6 characters long'
                    };
                }

                return {
                    isValid: true
                };
            }

            async parseResponse(response) {
                const text = await response.text();

                if (!text.trim()) {
                    throw new Error('Server returned empty response');
                }

                try {
                    return JSON.parse(text);
                } catch (error) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Server returned invalid response');
                }
            }

            handleCreateSuccess(result, accountData) {
                let message = 'Account created successfully';
                let messageType = 'success';

                if (result.email_sent) {
                    message += ` and credentials sent to ${accountData.email}`;
                } else {
                    message += ` (but email notification failed - please provide credentials manually)`;
                    messageType = 'warning'; // Use warning instead of error
                }

                this.showNotification(message, messageType);
                this.closeModal();

                // Reload accounts after a short delay
                setTimeout(() => this.loadAccounts(), 1000);
            }

            async deleteAccount(accountId) {
                const account = this.accounts.find(acc => acc.id == accountId);
                if (!account) return;

                if (!confirm(`Are you sure you want to delete the account for ${account.fullname}? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts_api.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: accountId
                        })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.showNotification('Account deleted successfully', 'success');
                        this.loadAccounts();
                    } else {
                        this.showNotification(result.message || 'Error deleting account', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting account:', error);
                    this.showNotification('Error deleting account', 'error');
                }
            }

            editAccount(accountId) {
                const account = this.accounts.find(acc => acc.id == accountId);
                if (account) {
                    // For now, show a notification - implement edit modal later
                    this.showNotification(`Edit functionality for ${account.fullname} will be implemented soon`, 'info');
                }
            }

            getSelectedGender() {
                const maleRadio = document.getElementById('default-radio-1');
                return maleRadio && maleRadio.checked ? 'Male' : 'Female';
            }

            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            showNotification(message, type = 'info') {
                // Remove existing notifications
                document.querySelectorAll('.custom-notification').forEach(notification => notification.remove());

                const notification = document.createElement('div');
                notification.className = `custom-notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        } text-white max-w-sm`;

                notification.innerHTML = `
            <div class="flex items-center">
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

                document.body.appendChild(notification);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            }

            escapeHtml(unsafe) {
                if (unsafe == null) return '';
                return unsafe.toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
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