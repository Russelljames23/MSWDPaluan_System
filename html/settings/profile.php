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
        .nav-list li #button.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #button.active-link svg {
            color: #1d4ed8;
        }

        /* Highlight active sidebar link */
        .nav-list li #button1.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #button1.active-link svg {
            color: #1d4ed8;
        }

        .nav-list li #button2.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #button2.active-link svg {
            color: #1d4ed8;
        }

        .nav-list li #button3.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #button3.active-link svg {
            color: #1d4ed8;
        }
    </style>
</head>

<body>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <nav
            class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
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
                    <a href="https://flowbite.com" class="flex items-center justify-between mr-4 ">
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
                                <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>
                            </span>
                            <span class="block text-sm text-gray-900 truncate dark:text-white">
                                <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'User Type'); ?>
                            </span>
                        </div>
                        <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
                            <li>
                                <a href="#"
                                    class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white">My
                                    profile</a>
                            </li>
                        </ul>
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
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Senior
                                    List</a>
                            </li>
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
                        <a href="#"  style="color: blue;"
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
        <main class="p-4 md:ml-64  pt-20">
            <div class="flex flex-row justify-between gap-2">
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
                            <a id="button" class="cursor-pointer active-link">
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
                            <button class="cursor-pointer">
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
                            <a href="accounts.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
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
                            <a href="historylogs.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3M3.22302 14C4.13247 18.008 7.71683 21 12 21c4.9706 0 9-4.0294 9-9 0-4.97056-4.0294-9-9-9-3.72916 0-6.92858 2.26806-8.29409 5.5M7 9H3V5" />
                                </svg>

                                <span class="links_name">History Log</span>
                            </a>
                            <span class="tooltip">History Log</span>
                        </li>
                        <li>
                            <a href="activitylogs.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-3 5h3m-6 0h.01M12 16h3m-6 0h.01M10 3v4h4V3h-4Z" />
                                </svg>

                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3M3.22302 14C4.13247 18.008 7.71683 21 12 21c4.9706 0 9-4.0294 9-9 0-4.97056-4.0294-9-9-9-3.72916 0-6.92858 2.26806-8.29409 5.5M7 9H3V5" />
                                </svg>

                                <span class="links_name">Activity Log</span>
                            </a>
                            <span class="tooltip">Activity Log</span>
                        </li>
                    </ul>
                </div>
                <!-- profile  -->
                <section id="profileSection" class="bg-gray-50 dark:bg-gray-900 w-full">
                    <div class="mx-auto max-w-screen-xl ">
                        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg px-5">
                            <!-- Close Button (top-right) -->
                            <div class="absolute top-2 right-2 group w-fit h-fit">
                                <button type="button"
                                    class="text-gray-400 hover:text-gray-900 cursor-pointer inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white "
                                    data-dismiss-target="#toast-default" aria-label="Close">
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
                            <!-- Content -->
                            <div
                                class="flex flex-col pb-10 border-b border-gray-200 dark:border-gray-700 md:flex-row justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                                <div class="flex flex-row gap-2 items-center">
                                    <div class="relative inline-block">
                                        <img class="w-20 h-20 rounded-full ring-2 ring-gray-300 dark:ring-gray-500"
                                            src="https://spng.pngfind.com/pngs/s/378-3780189_member-icon-png-transparent-png.png"
                                            alt="Bordered avatar" />

                                        <div class="absolute -bottom-4 right-0 group">
                                            <button type="button"
                                                class="relative bg-white text-gray-400 hover:text-gray-900 rounded-full cursor-pointer hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700 shadow-md">
                                                <svg class="w-5 h-5 text-blue-800 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="square"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 0 1 3-3h1m4-6a3 3 0 0 1 3-3h1m-4 18a3 3 0 0 0 3-3v-1m-9 3a3 3 0 0 1-3-3v-1m9-3a3 3 0 0 1 3 3v1m-9-3a3 3 0 0 0-3 3v1m9-9a3 3 0 0 0-3-3h-1m-6 3a3 3 0 0 1 3-3h1m-6 6a3 3 0 0 1 3 3v1m6-9a3 3 0 0 1 3 3v1m-9-6a3 3 0 0 0-3-3h-1" />
                                                </svg>
                                            </button>
                                            <!-- Tooltip -->
                                            <span
                                                class="absolute bottom-10 right-1/2 translate-x-1/2 mb-1 hidden group-hover:block px-2 py-1 text-xs text-white bg-gray-700/80 rounded-md shadow-lg whitespace-nowrap">
                                                Change Photo
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-sm font-medium dark:text-white">
                                            Russell James Tadalan
                                        </h2>
                                        <h2 class="text-xs font-normal dark:text-white">
                                            russelljames23@gmail.com
                                        </h2>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row items-center justify-center space-y-3 md:space-y-0 ">
                                <form class="w-full items-center justify-center flex py-4">
                                    <div class="flex flex-col gap-3 w-full">
                                        <div class="flex flex-col gap-6 w-full">
                                            <div class="flex flex-row w-full items-center">
                                                <label for="first_name"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Name</label>
                                                <div class="w-full text-right">
                                                    <label for="first_name"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">Russell
                                                        James Tadalan</label>
                                                    <input type="text" id="first_name"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                            <div class="flex flex-row w-full items-center">
                                                <label for="birthdate"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Birthdate</label>
                                                <div class="w-full text-right">
                                                    <label for="birthdate"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">December
                                                        27,2001</label>
                                                    <input type="text" id="birthdate"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                            <div class="flex flex-row w-full items-center">
                                                <label for="email"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Email
                                                    Account</label>
                                                <div class="w-full text-right">
                                                    <label for="email"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">russelljamestadalan23@gmail.com</label>
                                                    <input type="text" id="email"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                            <div class="flex flex-row w-full items-center">
                                                <label for="mobile_number"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Mobile
                                                    number</label>
                                                <div class="w-full text-right">
                                                    <label for="mobile_number"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">09664750533</label>
                                                    <input type="text" id="mobile_number"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                            <div class="flex flex-row w-full items-center">
                                                <label for="address"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Address</label>
                                                <div class="w-full text-right">
                                                    <label for="address"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">Brgy.11
                                                        Harrison,Paluan Occidental Mindoro</label>
                                                    <input type="text" id="address"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                            <div class="flex flex-row w-full items-center">
                                                <label for="password"
                                                    class="block mb-2 w-full text-sm font-medium text-gray-900 dark:text-white">Password</label>
                                                <div class="w-full text-right">
                                                    <label for="password"
                                                        class="profile-label block mb-2 w-full text-sm font-medium text-gray-600 dark:text-white">**********</label>
                                                    <input type="text" id="password"
                                                        class="profile-input hidden bg-gray-50  border-gray-300 text-gray-900 text-sm text-right rounded-sm h-8  w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white "
                                                        placeholder="" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-end">
                                            <button id="editBtn" type="button"
                                                class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-sm text-[13px] cursor-pointer px-3 py-1.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">
                                                Edit Profile
                                            </button>
                                            <div class="flex flex-row gap-2">
                                                <button type="button" id="cancelBtn"
                                                    class="hidden text-gray-800 bg-gray-200 hover:bg-gray-300 font-medium rounded-sm text-[13px] cursor-pointer px-3 py-1.5 text-center dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
                                                    Cancel
                                                </button>
                                                <button type="submit" id="updateBtn"
                                                    class="text-white hidden bg-blue-700 hover:bg-blue-800 font-medium rounded-sm text-[13px] cursor-pointer px-3 py-1.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">
                                                    Update
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
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
</body>

</html>