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
                                <a href="./SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Senior
                                    List</a>
                            </li>
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
                    <p class="text-gray-600 dark:text-gray-400">Create and print ID cards for registered senior citizens</p>
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

                <!-- ID Card Preview and Form Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- ID Card Preview -->
                    <div class="bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 shadow-lg">
                        <div class="text-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">ID Card Preview</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Preview will update as you edit</p>
                        </div>

                        <!-- ID Card Design -->
                        <div class="border-2 border-blue-800 rounded-lg p-4 bg-gradient-to-br from-blue-50 to-gray-50 dark:from-gray-800 dark:to-gray-900">
                            <!-- Republic of the Philippines Header -->
                            <div class="text-center mb-2">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Republic of the Philippines</h4>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Office for Senior Citizens Affairs (OSCA)</h4>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Paluan, Occidental Mindoro</h4>
                            </div>

                            <div class="flex flex-col md:flex-row gap-4 mb-4">
                                <!-- Photo Area -->
                                <div class="md:w-1/3 flex flex-col items-center">
                                    <div class="w-32 h-32 border-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">Photo</span>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300">LD No. <span id="ld-number">010726</span></div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">ID Number</div>
                                    </div>
                                </div>

                                <!-- Information Area -->
                                <div class="md:w-2/3">
                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Name:</label>
                                            <div id="preview-name" class="text-sm font-bold text-gray-900 dark:text-white">LOURDES V. AGUILAR</div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Address:</label>
                                            <div id="preview-address" class="text-sm text-gray-900 dark:text-white">Barangay 1-Mapalad, Paluan, Occidental Mindoro</div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Date of Birth:</label>
                                            <div id="preview-dob" class="text-sm text-gray-900 dark:text-white">01/07/1946</div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Date Issued:</label>
                                            <div id="preview-issued" class="text-sm text-gray-900 dark:text-white"><?php echo date('m/d/Y'); ?></div>
                                        </div>
                                    </div>

                                    <!-- Signature Area -->
                                    <div class="mt-4 pt-2 border-t border-gray-300 dark:border-gray-600">
                                        <div class="text-center">
                                            <div class="h-12 border-b border-gray-300 dark:border-gray-600 mb-1"></div>
                                            <div class="text-xs text-gray-700 dark:text-gray-300">Signature/Thumbmark</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Benefits Section -->
                            <div class="mt-4 pt-3 border-t border-gray-300 dark:border-gray-600">
                                <div class="text-center mb-2">
                                    <div class="text-xs font-bold text-red-600 dark:text-red-400">THIS CARD IS NON-TRANSFERABLE</div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">Benefits and Privileges Under Republic Act No. 9994</div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-1 text-[10px] text-gray-800 dark:text-gray-300">
                                    <div>• Free medical/dental diagnostic & laboratory fees in all government facilities.</div>
                                    <div>• 20% Discount in purchase medicines.</div>
                                    <div>• 20% Discount in Hotels, Restaurants, and Recreation Centers & Funeral Parlors.</div>
                                    <div>• 20% Discount on theatres, cinema house and concert halls, etc.</div>
                                    <div>• 20% Discount in medical/dental services, diagnostic & laboratory fees in private facilities.</div>
                                    <div>• 20% Discount in fare for domestic air, sea travel and public land transportation.</div>
                                    <div>• 5% Discount in basic necessities and prime commodities.</div>
                                    <div>• 15% VAT exemption on the purchase of good & service which are entitled to the 20% discount.</div>
                                    <div>• 5% Discount monthly utilization of water/electricity provided that the water and electricity meter bases are under the name of senior citizens.</div>
                                    <div class="col-span-1 md:col-span-2 mt-1 text-red-600 dark:text-red-400 font-semibold">
                                        Persons and Corporations violating RA 9994 shall be penalized. Only for the exclusive use of Senior Citizens : abuse of privileges is punishable by law.
                                    </div>
                                </div>

                                <!-- Signatories -->
                                <div class="mt-3 pt-2 border-t border-gray-300 dark:border-gray-600 grid grid-cols-2 gap-4">
                                    <div class="text-center">
                                        <div class="h-8 border-b border-gray-300 dark:border-gray-600 mb-1"></div>
                                        <div class="text-xs font-semibold text-gray-900 dark:text-white">EVELYN V. BELTRAN</div>
                                        <div class="text-xs text-gray-700 dark:text-gray-300">OSCA HEAD</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="h-8 border-b border-gray-300 dark:border-gray-600 mb-1"></div>
                                        <div class="text-xs font-semibold text-gray-900 dark:text-white">MICHAEL D. DIAZ</div>
                                        <div class="text-xs text-gray-700 dark:text-gray-300">Municipal Mayor</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Print Button -->
                        <div class="mt-4 flex justify-center">
                            <button id="print-id-card"
                                class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg focus:ring-4 focus:ring-blue-300 focus:outline-none inline-flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"></path>
                                </svg>
                                Print ID Card
                            </button>
                        </div>
                    </div>

                    <!-- Senior Citizen Details Form -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Senior Citizen Information</h3>

                        <form id="id-card-form">
                            <div class="space-y-4">
                                <!-- Basic Information -->
                                <div>
                                    <label for="full-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Full Name
                                    </label>
                                    <input type="text" id="full-name"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        value="LOURDES V. AGUILAR"
                                        oninput="updatePreview('name', this.value)">
                                </div>

                                <div>
                                    <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Complete Address
                                    </label>
                                    <textarea id="address" rows="2"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        oninput="updatePreview('address', this.value)">Barangay 1-Mapalad, Paluan, Occidental Mindoro</textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="date-of-birth" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Date of Birth
                                        </label>
                                        <input type="date" id="date-of-birth"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                            value="1946-01-07"
                                            onchange="updatePreview('dob', formatDate(this.value))">
                                    </div>

                                    <div>
                                        <label for="ld-number-input" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            LD Number
                                        </label>
                                        <input type="text" id="ld-number-input"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                            value="010726"
                                            oninput="updatePreview('ld', this.value)">
                                    </div>
                                </div>

                                <!-- Photo Upload -->
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        ID Photo
                                    </label>
                                    <div class="flex items-center space-x-4">
                                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <button type="button"
                                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                                                Upload Photo
                                            </button>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG or GIF (Max. 2MB)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Signatory Selection -->
                                <div>
                                    <label for="osca-head" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        OSCA Head
                                    </label>
                                    <input type="text" id="osca-head"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        value="EVELYN V. BELTRAN">
                                </div>

                                <div>
                                    <label for="municipal-mayor" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Municipal Mayor
                                    </label>
                                    <input type="text" id="municipal-mayor"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        value="MICHAEL D. DIAZ">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3 mt-6">
                                <button type="submit"
                                    class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-blue-300 focus:outline-none">
                                    Save Changes
                                </button>
                                <button type="button" id="generate-pdf"
                                    class="px-5 py-2.5 bg-green-700 hover:bg-green-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-green-300 focus:outline-none inline-flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd"></path>
                                    </svg>
                                    Generate PDF
                                </button>
                                <button type="button"
                                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg text-sm focus:ring-4 focus:ring-gray-300 focus:outline-none dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white">
                                    Clear Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>

    <script>
        function updatePreview(field, value) {
            switch (field) {
                case 'name':
                    document.getElementById('preview-name').textContent = value.toUpperCase();
                    break;
                case 'address':
                    document.getElementById('preview-address').textContent = value;
                    break;
                case 'dob':
                    document.getElementById('preview-dob').textContent = value;
                    break;
                case 'ld':
                    document.getElementById('ld-number').textContent = value;
                    break;
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${month}/${day}/${year}`;
        }

        // Set current date as issued date
        document.getElementById('preview-issued').textContent = formatDate(new Date());

        // Print functionality
        document.getElementById('print-id-card').addEventListener('click', function() {
            const printContent = document.querySelector('.border-2.border-blue-800').cloneNode(true);
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
        <html>
            <head>
                <title>Senior Citizen ID Card - Print</title>
                <style>
                    body { margin: 0; padding: 20px; }
                    @media print {
                        body { -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>${printContent.innerHTML}</body>
        </html>
    `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        });
    </script>
</body>

</html>