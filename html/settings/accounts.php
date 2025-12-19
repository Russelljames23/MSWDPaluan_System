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
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/index.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/register.php?session_context=<?php echo $ctx; ?>"
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
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/benefits.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/generate_id.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/reports/report.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/archived.php?session_context=<?php echo $ctx; ?>"
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
    <script src="../js/tailwind.config.js"></script>
    <script>
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");
        const profileLink = document.querySelector('.nav-list li:first-child button'); // My Profile link
        const accountLink = document.querySelector('.nav-list li:nth-child(3) button'); // My Account link
        const historyLink = document.querySelector('.nav-list li:nth-child(4) button'); // My History link
        const activityLink = document.querySelector('.nav-list li:nth-child(5) button'); // My Activity link
        const profileSection = document.getElementById('profileSection'); // Your hidden profile section
        const accountSection = document.getElementById('accountSection'); // Your hidden account section
        const historySection = document.getElementById('historySection'); // Your hidden history section
        const activitySection = document.getElementById('activitySection'); // Your hidden activity section
        const allLinks = document.querySelectorAll('.nav-list button');

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

    <script>
        const editBtn = document.getElementById("editBtn");
        const cancelBtn = document.getElementById("cancelBtn");
        const updateBtn = document.getElementById("updateBtn");
        const labels = document.querySelectorAll(".profile-label");
        const inputs = document.querySelectorAll(".profile-input");

        editBtn.addEventListener("click", () => {
            // Show inputs and hide labels
            labels.forEach((lbl) => lbl.classList.add("hidden"));
            inputs.forEach((inp) => inp.classList.remove("hidden"));

            // Toggle buttons
            editBtn.classList.add("hidden");
            cancelBtn.classList.remove("hidden");
            updateBtn.classList.remove("hidden");
        });

        cancelBtn.addEventListener("click", () => {
            // Hide inputs and show labels
            labels.forEach((lbl) => lbl.classList.remove("hidden"));
            inputs.forEach((inp) => inp.classList.add("hidden"));

            // Toggle buttons
            cancelBtn.classList.add("hidden");
            updateBtn.classList.add("hidden");
            editBtn.classList.remove("hidden");
        });
    </script>

    <script>
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

                // Modal close buttons
                const modalCloseButtons = document.querySelectorAll('[data-modal-toggle="defaultModal"], [data-modal-hide="defaultModal"]');
                modalCloseButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        this.closeModal();
                    });
                });

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
                    // Use absolute path to ensure correct API endpoint
                    const apiUrl = '/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts.php?action=get_accounts';
                    console.log('Fetching from:', apiUrl);

                    const response = await fetch(apiUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    console.log('Response status:', response.status);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const text = await response.text();
                    console.log('Raw response:', text);

                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);

                    if (data.records) {
                        this.accounts = data.records;
                        this.renderAccounts();
                    } else {
                        this.accounts = [];
                        this.renderAccounts();
                        console.warn('No records found in response');
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
                        <button onclick="accountsManager.editAccount(${account.id})" 
                                class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                title="Edit account">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="accountsManager.deleteAccount(${account.id})" 
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
                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts.php', {
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
                    lastname: document.getElementById('lastname').value.trim(),
                    firstname: document.getElementById('firstname').value.trim(),
                    middlename: document.getElementById('middlename').value.trim(),
                    birthdate: document.getElementById('birthdate').value,
                    gender: this.getSelectedGender(),
                    email: document.getElementById('email').value.trim(),
                    contact_no: document.getElementById('contact_no').value.trim(),
                    address: document.getElementById('address').value.trim(),
                    username: document.getElementById('username').value.trim(),
                    password: document.getElementById('password').value,
                    user_type: document.getElementById('select-type').value,
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

                const confirmPassword = document.getElementById('confirm-password').value;
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
                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts.php', {
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

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.accountsManager = new AccountsManager();
            });
        } else {
            window.accountsManager = new AccountsManager();
        }
    </script>
</body>

</html>