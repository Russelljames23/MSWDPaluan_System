<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

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
            /* overflow: hidden; */
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
        /* Tooltip styling - fixed visibility & design */
        .tooltip {
            position: absolute;
            top: 50%;
            left: 100%;
            transform: translateY(-50%);
            margin-left: 10px;
            background: rgba(221, 221, 221, 0.555);
            /* darker semi-transparent background */
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

        /* Show tooltip when hovering over an item */
        .sidebar li:hover .tooltip {
            opacity: 1;
            transform: translate(10px, -50%);
            /* subtle slide-in effect */
        }

        /* Hide tooltips when sidebar is expanded */
        .sidebar.open li .tooltip {
            display: none;
        }


        /* Page content */
        .home-section {
            margin-left: 78px;
            padding: 20px;
            transition: all 0.4s ease;
        }

        .sidebar.open~.home-section {
            margin-left: 200px;
        }

        /* Highlight active sidebar link */
        .nav-list li #history.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #history.active-link svg {
            color: #1d4ed8;
        }
    </style>
    <style>
        /* Responsive table improvements */
        @media (max-width: 640px) {

            #logsTable th:nth-child(3),
            #logsTable td:nth-child(3),
            #logsTable th:nth-child(5),
            #logsTable td:nth-child(5),
            #logsTable th:nth-child(6),
            #logsTable td:nth-child(6),
            #logsTable th:nth-child(8),
            #logsTable td:nth-child(8) {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 768px) {

            #logsTable th:nth-child(5),
            #logsTable td:nth-child(5),
            #logsTable th:nth-child(6),
            #logsTable td:nth-child(6) {
                display: none;
            }

            .filter-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Overlay animations */
        #logOverlay {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        #logOverlay>div {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Table hover effects */
        #logsTableBody tr {
            transition: all 0.2s ease;
        }

        #logsTableBody tr:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Loading spinner */
        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Status badges with pulse animation for active sessions */
        .status-badge-active {
            position: relative;
        }

        .status-badge-active::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: inherit;
            border-radius: inherit;
            opacity: 0.5;
            animation: pulse 2s infinite;
            z-index: -1;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
            }

            50% {
                opacity: 0.8;
            }

            100% {
                opacity: 0.5;
            }
        }

        /* Scrollbar styling for dark mode */
        #overlayContent::-webkit-scrollbar {
            width: 8px;
        }

        #overlayContent::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #overlayContent::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #overlayContent::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .dark #overlayContent::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark #overlayContent::-webkit-scrollbar-thumb {
            background: #6b7280;
        }

        .dark #overlayContent::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Print styles */
        @media print {
            #logOverlay {
                position: static;
                background: white;
                display: block !important;
            }

            #closeOverlay,
            #refreshLogs,
            #exportLogs,
            #clearFilters {
                display: none;
            }
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
                        <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../register.php?session_context=<?php echo $ctx; ?>"
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
                                <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75  hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
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
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-blue bg-blue-100 hover:bg-blue-100 dark:hover:bg-blue-700 group">
                            <svg aria-hidden="true"
                                class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400 group-hover:text-blue-700 dark:group-hover:text-white"
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

        <!-- Main content -->
        <main class="p-2 md:ml-64  pt-20">
            <div class="flex flex-row justify-between gap-1">
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
                            <a href="profile.php?session_context=<?php echo $ctx; ?>" id="profile" class="cursor-pointer active-link">
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-900" aria-hidden="true"
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
                            <button class="cursor-pointer active-link">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M13 3a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0V3ZM6.343 4.929A1 1 0 0 0 4.93 6.343l1.414 1.414a1 1 0 0 0 1.414-1.414L6.343 4.929Zm12.728 1.414a1 1 0 0 0-1.414-1.414l-1.414 1.414a1 1 0 0 0 1.414 1.414l1.414-1.414ZM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm-9 4a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H3Zm16 0a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2ZM7.757 17.657a1 1 0 1 0-1.414-1.414l-1.414 1.414a1 1 0 1 0 1.414 1.414l1.414-1.414Zm9.9-1.414a1 1 0 0 0-1.414 1.414l1.414 1.414a1 1 0 0 0 1.414-1.414l-1.414-1.414ZM13 19a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0v-2Z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span class="links_name">Theme</span>
                            </button>
                            <span class="tooltip">Theme</span>
                        </li>

                        <li>
                            <a href="accounts.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer active-link">
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
                            <a href="#" id="history" class="cursor-pointer active-link">
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
                <!-- history log -->
                <section id="historySection" class="bg-gray-50 dark:bg-gray-900 w-full">
                    <div class="mx-auto max-w-screen-xl ">
                        <!-- Stats Overview Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 1.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sessions</p>
                                        <p id="totalSessions" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Now</p>
                                        <p id="activeSessions" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Activities</p>
                                        <p id="totalActivities" class="text-2xl font-semibold text-gray-900 dark:text-white">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Duration</p>
                                        <p id="avgDuration" class="text-2xl font-semibold text-gray-900 dark:text-white">0m</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Container with Overlay Support -->
                        <div class="relative">
                            <!-- Overlay -->
                            <div id="logOverlay" class="hidden fixed inset-0 bg-gray-900/50 bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-hidden">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-auto ">
                                    <div class="flex justify-between items-center p-6 mb-2 border-b dark:border-gray-700">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="overlayTitle">Log Details</h3>
                                        <button id="closeOverlay" class="text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="px-4 overflow-y-auto " id="overlayContent">
                                        <!-- Content will be loaded here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Main Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                                <!-- Header with Filters -->
                                <div class="border-b dark:border-gray-700">
                                    <div class="p-2 md:p-2">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div>
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">System Logs</h2>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Track user sessions and system activities
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <!-- Quick Actions -->
                                                <button id="exportLogs" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Export
                                                </button>

                                                <button id="clearFilters" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Clear Filters
                                                </button>

                                                <button id="refreshLogs" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Refresh
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Advanced Filters -->
                                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2   ">
                                            <!-- Search -->
                                            <div>
                                                <label for="searchLogs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" id="searchLogs" placeholder="Search logs..."
                                                        class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                </div>
                                            </div>

                                            <!-- Log Type -->
                                            <div>
                                                <label for="logTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Log Type</label>
                                                <select id="logTypeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="both">All Logs</option>
                                                    <option value="session">Sessions Only</option>
                                                    <option value="activity">Activities Only</option>
                                                </select>
                                            </div>

                                            <!-- Date Range -->
                                            <div>
                                                <label for="dateRangeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                                                <select id="dateRangeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="all">All Time</option>
                                                    <option value="today">Today</option>
                                                    <option value="week">Last 7 Days</option>
                                                    <option value="month">Last 30 Days</option>
                                                    <option value="year">Last Year</option>
                                                </select>
                                            </div>

                                            <!-- User Type -->
                                            <div>
                                                <label for="userTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User Type</label>
                                                <select id="userTypeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                                    <option value="all">All Users</option>
                                                    <option value="admin">Admin Only</option>
                                                    <option value="staff">Staff Only</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <div id="logsLoading" class="p-8 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                                        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading logs...</p>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div id="logsEmpty" class="hidden p-12 text-center">
                                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No logs found</h3>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Try adjusting your search or filter criteria
                                    </p>
                                </div>

                                <!-- Table Container -->
                                <div class="overflow-x-auto">
                                    <table id="logsTable" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 hidden">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-2 py-2">Type</th>
                                                <th scope="col" class="px-2 py-2">User</th>
                                                <th scope="col" class="px-2 py-2 hidden md:table-cell">Activity</th>
                                                <th scope="col" class="px-2 py-2">Time</th>
                                                <th scope="col" class="px-2 py-2 hidden lg:table-cell">IP Address</th>
                                                <th scope="col" class="px-2 py-2 hidden lg:table-cell">Duration</th>
                                                <th scope="col" class="px-2 py-2">Status</th>
                                                <th scope="col" class="px-2 py-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="logsTableBody">
                                            <!-- Data will be populated here -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div id="logsPagination" class="hidden flex flex-col sm:flex-row items-center justify-between p-4 border-t dark:border-gray-700">
                                    <div class="mb-4 sm:mb-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-400">
                                            Showing <span id="pageStart" class="font-medium">1</span> to
                                            <span id="pageEnd" class="font-medium">10</span> of
                                            <span id="totalItems" class="font-medium">0</span> results
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button id="firstPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            « First
                                        </button>
                                        <button id="prevPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            ‹ Prev
                                        </button>
                                        <span class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
                                        </span>
                                        <button id="nextPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Next ›
                                        </button>
                                        <button id="lastPage" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Last »
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
    <script src="/MSWDPALUAN_SYSTEM-MAIN/js/logs-manager.js"></script>

    <script>
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");

        // Sidebar toggle
        closeBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
        });

        // My Profile click behavior
        profileLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the profile section
            profileSection.classList.remove('hidden');
            accountSection.classList.add('hidden');
            historySection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My Profile link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            profileLink.classList.add('active-link');

        });

        // My Account click behavior
        accountLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the account section
            accountSection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            historySection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My Account link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            accountLink.classList.add('active-link');

        });
        // My History click behavior
        historyLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the history section
            historySection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            accountSection.classList.add('hidden');
            activitySection.classList.add('hidden');
            // Highlight the My History link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            historyLink.classList.add('active-link');

        });
        // My Activity click behavior
        activityLink.addEventListener("click", (e) => {
            e.preventDefault();

            // Show the activity section
            activitySection.classList.remove('hidden');
            profileSection.classList.add('hidden');
            accountSection.classList.add('hidden');
            historySection.classList.add('hidden');
            // Highlight the My Activity link and remove highlight from others
            allLinks.forEach(link => link.classList.remove('active-link'));
            activityLink.classList.add('active-link');

        });

        // Close button behavior inside the profile section
        const closeProfileBtn = document.querySelector('[aria-label="Close"]');
        if (closeProfileBtn) {
            closeProfileBtn.addEventListener('click', () => {
                profileSection.classList.add('hidden');
                profileLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the account section
        const closeAccountBtn = document.querySelector('[aria-label="Close1"]');
        if (closeAccountBtn) {
            closeAccountBtn.addEventListener('click', () => {
                accountSection.classList.add('hidden');
                accountLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the history section
        const closeHistoryBtn = document.querySelector('[aria-label="Close2"]');
        if (closeHistoryBtn) {
            closeHistoryBtn.addEventListener('click', () => {
                historySection.classList.add('hidden');
                historyLink.classList.remove('active-link');
            });
        }
        // Close button behavior inside the activity section
        const closeActivityBtn = document.querySelector('[aria-label="Close3"]');
        if (closeActivityBtn) {
            closeActivityBtn.addEventListener('click', () => {
                activitySection.classList.add('hidden');
                activityLink.classList.remove('active-link');
            });
        }
    </script>


</body>

</html>