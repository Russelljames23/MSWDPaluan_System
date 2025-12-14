<?php
require_once "../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
                    <p class="text-gray-600 dark:text-gray-400">Create and print ID cards in batch format (9 per page)</p>
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
                                    placeholder="Search by name or OSCA ID">
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
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status
                            </label>
                            <select id="filter-status"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Senior Selection Table (Similar to beneficiary.php) -->
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
                                    <th scope="col" class="px-4 py-3">OSCA ID</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="seniors-table-body">
                                <!-- Data will be populated by JavaScript -->
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Loading senior citizens data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="pagination-controls" class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4 border-t dark:border-gray-700">
                        <!-- Will be populated by JavaScript -->
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
                                            <!-- Add more options as needed -->
                                        </select>
                                    </div>
                                    <div>
                                        <label for="municipal-mayor" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                            Municipal Mayor
                                        </label>
                                        <select id="municipal-mayor" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="MICHAEL D. DIAZ">MICHAEL D. DIAZ</option>
                                            <option value="MERIAM E. LEYCANO-QUIJANO">MERIAM E. LEYCANO-QUIJANO</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="date-issued" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Date Issued
                                </label>
                                <input type="date" id="date-issued"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <p>• IDs will be printed in Long Bond Paper (8.5" x 13") landscape</p>
                                    <p>• 9 IDs per page (Front: ID Info, Back: Benefits)</p>
                                    <p>• IDs will be generated in the exact format of the reference document</p>
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
                            <div class="flex mb-2">
                                <!-- Photo Area -->
                                <div class="w-1/3 flex flex-col items-center">
                                    <div class="w-16 h-16 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">1x1</span>
                                    </div>
                                    <div class="text-center mt-1">
                                        <div class="text-[8px] font-medium text-gray-700 dark:text-gray-300">ID PIC</div>
                                    </div>
                                </div>

                                <!-- Info Area -->
                                <div class="w-2/3 pl-2">
                                    <div class="space-y-1">
                                        <div>
                                            <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Name:</label>
                                            <div class="text-[9px] font-bold text-gray-900 dark:text-white truncate">SAMPLE NAME</div>
                                        </div>
                                        <div>
                                            <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Address:</label>
                                            <div class="text-[9px] text-gray-900 dark:text-white truncate">Brgy. I - MAPALAD</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-1">
                                            <div>
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Date of Birth</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white">01/01/1940</div>
                                            </div>
                                            <div class="text-center">
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Sex</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white">M/F</div>
                                            </div>
                                            <div>
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Date Issued</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white"><?php echo date('m/d/Y'); ?></div>
                                            </div>
                                        </div>
                                        <div class="text-center pt-1">
                                            <div class="h-4 border-b border-gray-300 dark:border-gray-600"></div>
                                            <div class="text-[7px] text-gray-700 dark:text-gray-300">Signature/Thumbmark</div>
                                        </div>
                                        <div class="text-center pt-1">
                                            <div class="text-[8px] font-medium text-gray-900 dark:text-white">ID No. <span class="font-bold">000000</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Non-Transferable Notice -->
                            <div class="text-center mt-2">
                                <div class="text-[8px] font-bold text-red-600 dark:text-red-400">THIS CARD IS NON-TRANSFERABLE</div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Back side contains:</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Benefits and Privileges under RA 9994</div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Printing Info:</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Long Bond Paper (8.5" x 13")</li>
                                <li>• Landscape Orientation</li>
                                <li>• 9 IDs per page front & back</li>
                                <li>• Exact document format</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Print Preview Modal -->
            <div id="print-preview-modal" class="hidden fixed inset-0 z-50 overflow-auto bg-black bg-opacity-50">
                <div class="relative w-full max-w-6xl mx-auto my-8">
                    <div class="bg-white rounded-lg shadow-lg">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-xl font-bold text-gray-900">ID Cards Print Preview</h3>
                            <button id="close-preview-btn" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Preview Content -->
                        <div id="preview-content" class="p-4">
                            <!-- Preview will be generated here -->
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-between items-center p-4 border-t">
                            <div class="text-sm text-gray-600">
                                Showing: <span id="preview-count">0</span> IDs | Page: <span id="current-page">1</span> of <span id="total-pages">1</span>
                            </div>
                            <div class="flex space-x-2">
                                <button id="prev-page-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Previous
                                </button>
                                <button id="next-page-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Next
                                </button>
                                <button id="print-preview-btn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Print Preview
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
        let currentPage = 1;
        let totalPages = 1;
        let allSeniors = [];

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Load barangays and seniors
            loadBarangays();
            loadSeniors();

            // Event listeners for buttons
            document.getElementById('select-all-btn').addEventListener('click', selectAll);
            document.getElementById('deselect-all-btn').addEventListener('click', deselectAll);
            document.getElementById('master-checkbox').addEventListener('change', toggleMasterCheckbox);
            document.getElementById('preview-ids-btn').addEventListener('click', previewIDs);
            document.getElementById('generate-pdf-btn').addEventListener('click', generatePDF);
            document.getElementById('print-ids-btn').addEventListener('click', printIDs);
            document.getElementById('close-preview-btn').addEventListener('click', closePreview);
            document.getElementById('print-preview-btn').addEventListener('click', printPreview);
            document.getElementById('prev-page-btn').addEventListener('click', () => navigatePreview(-1));
            document.getElementById('next-page-btn').addEventListener('click', () => navigatePreview(1));

            // Search and filter event listeners
            document.getElementById('search-senior').addEventListener('input', debounce(loadSeniors, 300));
            document.getElementById('filter-barangay').addEventListener('change', loadSeniors);
            document.getElementById('filter-status').addEventListener('change', loadSeniors);

            // Update selected list initially
            updateSelectedList();
        });

        // Load barangays into filter
        function loadBarangays() {
            fetch('/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?mode=barangays')
                .then(res => res.json())
                .then(barangays => {
                    const select = document.getElementById('filter-barangay');
                    select.innerHTML = '<option value="all">All Barangays</option>';
                    barangays.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay;
                        option.textContent = barangay;
                        select.appendChild(option);
                    });
                })
                .catch(err => console.error('Error loading barangays:', err));
        }

        // Load seniors with pagination
        function loadSeniors() {
            const search = document.getElementById('search-senior').value;
            const barangay = document.getElementById('filter-barangay').value;
            const status = document.getElementById('filter-status').value;

            const params = new URLSearchParams({
                page: currentPage,
                search: search,
                barangay: barangay !== 'all' ? barangay : '',
                status: status !== 'all' ? status : ''
            });

            fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`)
                .then(res => res.json())
                .then(data => {
                    allSeniors = data.seniors || [];
                    totalPages = data.total_pages || 1;
                    renderSeniorsTable();
                    renderPagination();
                })
                .catch(err => console.error('Error loading seniors:', err));
        }

        // Render seniors table
        function renderSeniorsTable() {
            const tbody = document.getElementById('seniors-table-body');
            tbody.innerHTML = '';

            if (allSeniors.length === 0) {
                tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    No senior citizens found.
                </td>
            </tr>
        `;
                return;
            }

            allSeniors.forEach((senior, index) => {
                const isSelected = selectedSeniors.has(senior.applicant_id);
                const row = `
            <tr class="border-b dark:border-gray-700">
                <td class="px-4 py-3">
                    <input type="checkbox" class="senior-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        data-id="${senior.applicant_id}"
                        data-name="${senior.full_name || ''}"
                        data-birthdate="${senior.birth_date || ''}"
                        data-gender="${senior.gender || ''}"
                        data-barangay="${senior.barangay || ''}"
                        data-osca-id="${senior.osca_id || ''}"
                        ${isSelected ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3">${(currentPage - 1) * 10 + index + 1}</td>
                <td class="px-4 py-3">${senior.full_name || ''}</td>
                <td class="px-4 py-3">${senior.birth_date || ''}</td>
                <td class="px-4 py-3">${senior.age || ''}</td>
                <td class="px-4 py-3">${senior.gender || ''}</td>
                <td class="px-4 py-3">${senior.barangay || ''}</td>
                <td class="px-4 py-3">${senior.osca_id || 'N/A'}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded ${senior.validation === 'Validated' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${senior.validation || 'Unknown'}
                    </span>
                </td>
            </tr>
        `;
                tbody.innerHTML += row;
            });

            // Add event listeners to checkboxes
            document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    if (this.checked) {
                        selectedSeniors.set(id, {
                            name: name,
                            birthdate: this.dataset.birthdate,
                            gender: this.dataset.gender,
                            barangay: this.dataset.barangay,
                            oscaId: this.dataset.oscaId || generateOSCAId()
                        });
                    } else {
                        selectedSeniors.delete(id);
                    }

                    updateSelectedList();
                    updateMasterCheckbox();
                });
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

            let html = '<div class="space-y-1">';
            selectedSeniors.forEach((senior, id) => {
                html += `
            <div class="flex justify-between items-center text-sm">
                <span class="truncate">${senior.name}</span>
                <button class="text-red-500 hover:text-red-700" onclick="removeSelected('${id}')">
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

        // Select all seniors
        function selectAll() {
            allSeniors.forEach(senior => {
                selectedSeniors.set(senior.applicant_id, {
                    name: senior.full_name || '',
                    birthdate: senior.birth_date || '',
                    gender: senior.gender || '',
                    barangay: senior.barangay || '',
                    oscaId: senior.osca_id || generateOSCAId()
                });
            });

            // Check all checkboxes
            document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                checkbox.checked = true;
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
            const checkboxes = document.querySelectorAll('.senior-checkbox');

            if (masterCheckbox.checked) {
                selectAll();
            } else {
                deselectAll();
            }
        }

        // Update master checkbox state
        function updateMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');
            const checkboxes = document.querySelectorAll('.senior-checkbox');
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

        // Generate OSCA ID if not exists
        function generateOSCAId() {
            const timestamp = new Date().getTime().toString().slice(-6);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            return timestamp + random;
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

            // Group into pages of 9
            const pages = [];
            for (let i = 0; i < seniorsArray.length; i += 9) {
                pages.push(seniorsArray.slice(i, i + 9));
            }

            // Generate preview HTML
            const previewContent = document.getElementById('preview-content');
            previewContent.innerHTML = generatePreviewHTML(pages[0], 1, pages.length);

            // Update preview info
            document.getElementById('preview-count').textContent = seniorsArray.length;
            document.getElementById('total-pages').textContent = pages.length;

            // Show modal
            document.getElementById('print-preview-modal').classList.remove('hidden');
        }

        // Generate preview HTML for a page
        function generatePreviewHTML(seniors, pageNumber, totalPages) {
            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const dateIssued = document.getElementById('date-issued').value;
            const formattedDate = new Date(dateIssued).toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });

            // Create 3x3 grid for 9 IDs
            let html = `
        <div class="print-page bg-white p-4 border-2 border-gray-300" style="width: 13in; height: 8.5in; transform-origin: top left; transform: scale(0.8);">
            <div class="grid grid-cols-3 grid-rows-3 gap-2 h-full">
    `;

            seniors.forEach((senior, index) => {
                html += generateSingleIDHTML(senior, formattedDate, oscaHead, municipalMayor);
            });

            // Fill empty spots if less than 9
            for (let i = seniors.length; i < 9; i++) {
                html += `<div class="border border-dashed border-gray-300 rounded p-2"></div>`;
            }

            html += `
            </div>
            <div class="mt-4 text-center text-sm text-gray-600">
                Page ${pageNumber} of ${totalPages} | Front Side (ID Information)
            </div>
        </div>
        
        <div class="mt-8 print-page bg-white p-4 border-2 border-gray-300" style="width: 13in; height: 8.5in; transform-origin: top left; transform: scale(0.8);">
            <div class="text-center mb-4">
                <h4 class="text-sm font-bold">Benefits and Privileges under Republic Act No. 9994</h4>
            </div>
            <div class="grid grid-cols-3 grid-rows-3 gap-2 h-full">
    `;

            seniors.forEach((senior, index) => {
                html += generateBenefitsHTML(senior, formattedDate, oscaHead, municipalMayor);
            });

            // Fill empty spots if less than 9
            for (let i = seniors.length; i < 9; i++) {
                html += `<div class="border border-dashed border-gray-300 rounded p-2"></div>`;
            }

            html += `
            </div>
            <div class="mt-4 text-center text-sm text-gray-600">
                Page ${pageNumber} of ${totalPages} | Back Side (Benefits Information)
            </div>
        </div>
    `;

            return html;
        }

        // Generate single ID HTML
        function generateSingleIDHTML(senior, dateIssued, oscaHead, municipalMayor) {
            const genderCode = senior.gender === 'Male' ? 'M' : senior.gender === 'Female' ? 'F' : '';
            const dob = senior.birthdate ? new Date(senior.birthdate).toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            }) : '';

            return `
        <div class="border border-gray-300 rounded p-1">
            <!-- Republic Header -->
            <div class="text-center mb-1">
                <div class="text-[6px] font-bold">Republic of the Philippines</div>
                <div class="text-[6px] font-bold">Office for Senior Citizens Affairs (OSCA)</div>
                <div class="text-[6px] font-bold">Paluan, Occidental Mindoro</div>
            </div>
            
            <!-- ID Content -->
            <div class="flex">
                <!-- Photo Area -->
                <div class="w-1/3 flex flex-col items-center">
                    <div class="w-10 h-10 border border-gray-300 bg-gray-100 rounded flex items-center justify-center mb-1">
                        <span class="text-[5px] text-gray-500">1x1</span>
                    </div>
                    <div class="text-center">
                        <div class="text-[5px] font-medium">ID PIC</div>
                    </div>
                </div>
                
                <!-- Info Area -->
                <div class="w-2/3 pl-1">
                    <div class="space-y-0.5">
                        <div>
                            <div class="text-[5px] font-semibold">Name:</div>
                            <div class="text-[6px] font-bold truncate">${senior.name || ''}</div>
                        </div>
                        <div>
                            <div class="text-[5px] font-semibold">Address:</div>
                            <div class="text-[6px] truncate">${senior.barangay || ''}</div>
                        </div>
                        <div class="grid grid-cols-3 gap-0.5">
                            <div>
                                <div class="text-[4px]">Date of Birth</div>
                                <div class="text-[5px]">${dob}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-[4px]">Sex</div>
                                <div class="text-[5px]">${genderCode}</div>
                            </div>
                            <div>
                                <div class="text-[4px]">Date Issued</div>
                                <div class="text-[5px]">${dateIssued}</div>
                            </div>
                        </div>
                        <div class="text-center pt-0.5">
                            <div class="h-3 border-b border-gray-300 mb-0.5"></div>
                            <div class="text-[4px]">Signature/Thumbmark</div>
                        </div>
                        <div class="text-center pt-0.5">
                            <div class="text-[5px] font-medium">ID No. <span class="font-bold">${senior.oscaId || ''}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Non-Transferable Notice -->
            <div class="text-center mt-1">
                <div class="text-[5px] font-bold text-red-600">THIS CARD IS NON-TRANSFERABLE</div>
            </div>
        </div>
    `;
        }

        // Generate benefits HTML
        function generateBenefitsHTML(senior, dateIssued, oscaHead, municipalMayor) {
            return `
        <div class="border border-gray-300 rounded p-1 h-full">
            <!-- Benefits Header -->
            <div class="text-center mb-1">
                <div class="text-[5px] font-bold">Benefits and Privileges under Republic Act No. 9994</div>
            </div>
            
            <!-- Benefits List -->
            <div class="text-[3px] space-y-0.5">
                <div>• Free medical/dental diagnostic & laboratory fees in all government facilities.</div>
                <div>• 20% discount in purchase medicines</div>
                <div>• 20% discount in Hotels, Restaurant, and Recreation Centers & Funeral Parlors.</div>
                <div>• 20% discount on theatres, cinema houses and concert halls, etc.</div>
                <div>• 20% discount in medical/dental services, diagnostic & laboratory fees in private facilities.</div>
                <div>• 20% discount in fare for domestic air, sea travel and public land transportation</div>
                <div>• 5% discount in basic necessities and prime commodities</div>
                <div>• 12% VAT-exemption on the purchase of goods & service which are entitled to the 20% discount</div>
                <div>• 5% discount monthly utilization of water/electricity provided that the water and electricity meter bases are under the name of senior citizens</div>
            </div>
            
            <!-- Warning -->
            <div class="mt-1">
                <div class="text-[3px] text-red-600">
                    Persons and Corporans violating RA 9994 shall be penalized. Only for the exclusive use of Senior Citizens; abuse of privileges is punishable by law.
                </div>
            </div>
            
            <!-- Signatories -->
            <div class="mt-2 grid grid-cols-2 gap-1">
                <div class="text-center">
                    <div class="h-4 border-b border-gray-300 mb-0.5"></div>
                    <div class="text-[4px] font-semibold">${oscaHead}</div>
                    <div class="text-[3px]">OSCA HEAD</div>
                </div>
                <div class="text-center">
                    <div class="h-4 border-b border-gray-300 mb-0.5"></div>
                    <div class="text-[4px] font-semibold">${municipalMayor}</div>
                    <div class="text-[3px]">Municipal Mayor</div>
                </div>
            </div>
        </div>
    `;
        }

        // Generate PDF
        function generatePDF() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Here you would implement PDF generation logic
            // This could involve sending data to a PHP script that generates PDF using TCPDF, FPDF, or similar
            alert('PDF generation would be implemented here. This would create a downloadable PDF file.');
        }

        // Print IDs
        function printIDs() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Open print dialog with the generated content
            const printWindow = window.open('', '_blank');
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            // Group into pages of 9
            const pages = [];
            for (let i = 0; i < seniorsArray.length; i += 9) {
                pages.push(seniorsArray.slice(i, i + 9));
            }

            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const dateIssued = document.getElementById('date-issued').value;
            const formattedDate = new Date(dateIssued).toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });

            // Generate print content
            let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Senior Citizen IDs</title>
            <style>
                @media print {
                    @page {
                        size: landscape;
                        margin: 0.5in;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .page-break {
                        page-break-after: always;
                    }
                    .id-page {
                        width: 13in;
                        height: 8.5in;
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        grid-template-rows: repeat(3, 1fr);
                        gap: 0.1in;
                        padding: 0.2in;
                    }
                    .id-card {
                        border: 1px solid #000;
                        padding: 0.1in;
                        font-size: 6pt;
                        overflow: hidden;
                    }
                    .id-header {
                        text-align: center;
                        font-weight: bold;
                        font-size: 5pt;
                        margin-bottom: 0.05in;
                    }
                    .id-photo {
                        width: 0.6in;
                        height: 0.6in;
                        border: 1px solid #000;
                        background: #f0f0f0;
                        margin: 0 auto 0.05in;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 4pt;
                    }
                    .benefits-card {
                        border: 1px solid #000;
                        padding: 0.1in;
                        font-size: 3pt;
                        height: 100%;
                    }
                    .text-red {
                        color: #dc2626;
                    }
                }
            </style>
        </head>
        <body>
    `;

            // Generate pages
            pages.forEach((pageSeniors, pageIndex) => {
                // Front side (IDs)
                printContent += `
            <div class="id-page">
        `;

                pageSeniors.forEach(senior => {
                    printContent += generateSingleIDHTML(senior, formattedDate, oscaHead, municipalMayor);
                });

                // Fill empty spots
                for (let i = pageSeniors.length; i < 9; i++) {
                    printContent += `<div class="id-card"></div>`;
                }

                printContent += `</div><div class="page-break"></div>`;

                // Back side (Benefits)
                printContent += `
            <div class="id-page">
        `;

                pageSeniors.forEach(senior => {
                    printContent += generateBenefitsHTML(senior, formattedDate, oscaHead, municipalMayor);
                });

                // Fill empty spots
                for (let i = pageSeniors.length; i < 9; i++) {
                    printContent += `<div class="benefits-card"></div>`;
                }

                printContent += `</div>`;

                if (pageIndex < pages.length - 1) {
                    printContent += `<div class="page-break"></div>`;
                }
            });

            printContent += `
        </body>
        </html>
    `;

            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();

            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Close preview modal
        function closePreview() {
            document.getElementById('print-preview-modal').classList.add('hidden');
        }

        // Navigate preview pages
        function navigatePreview(direction) {
            // Implement page navigation in preview
            alert('Page navigation would be implemented here.');
        }

        // Print preview
        function printPreview() {
            // Open print dialog for preview
            window.print();
        }

        // Utility function for debouncing
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Render pagination controls
        function renderPagination() {
            const container = document.getElementById('pagination-controls');

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `
        <span class="text-sm text-gray-600 dark:text-gray-400">
            Page ${currentPage} of ${totalPages}
        </span>
        <div class="flex space-x-2">
    `;

            if (currentPage > 1) {
                html += `
            <button onclick="changePage(${currentPage - 1})" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                Previous
            </button>
        `;
            }

            if (currentPage < totalPages) {
                html += `
            <button onclick="changePage(${currentPage + 1})" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                Next
            </button>
        `;
            }

            html += `</div>`;
            container.innerHTML = html;
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            loadSeniors();
        }
    </script>
</body>

</html>