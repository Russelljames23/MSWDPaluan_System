<?php
require_once "../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../css/popup.css">
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
                        <a href="index.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-gray-700 group">
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
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-blue-700 bg-blue-100 rounded-lg dark:text-blue  dark:hover:bg-blue-700 group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400  dark:group-hover:text-white">
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
                            class="flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
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
                        <a href="./generate_id.php?session_context=<?php echo $ctx; ?>"
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

        <!-- I Basic Information -->
        <main class="p-4 md:ml-64 h-auto pt-20">
            <div class="w-full  items-center justify-center">
                <h2 class="mt-3 mb-3 text-2xl text-center tracking-tight font-bold  text-gray-900 dark:text-white">
                    APPLICATION FORM</h2>
            </div>
            <form id="applicantForm" action="">
                <section id="step1" class="bg-white flex flex-col dark:bg-gray-900  rounded-lg">
                    <div class="p-5 mb-8 rounded-lg w-full">
                        <div>
                            <h4 class="text-sm font-medium dark:text-white mb-3">I. BASIC INFORMAION</h4>
                            <div class="flex flex-col gap-5">
                                <div class="flex w-full  justify-between items-center gap-5">
                                    <div class="w-90 flex flex-col gap-3">
                                        <div class="w-90">
                                            <label for="email"
                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Last
                                                Name: <span class="text-red-500">*</span></label>
                                            <input type="text" id="lname" name="lname"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="" required>
                                        </div>
                                        <div class="w-90">
                                            <label for="email"
                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">First
                                                Name: <span class="text-red-500">*</span></label>
                                            <input type="text" id="fname" name="fname"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="" required>
                                        </div>
                                        <div class="w-90">
                                            <label for="email"
                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Middle
                                                Name: <span class="text-red-500">*</span></label>
                                            <input type="text" id="mname" name="mname"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="" required>
                                        </div>
                                        <div class="w-90">
                                            <label for="email"
                                                class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Suffix:</label>
                                            <input type="text" id="suffix" name="suffix"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="" required>
                                        </div>
                                    </div>
                                    <div class="w-full flex gap-3 flex-col justify-between">
                                        <div class="w-full flex flex-row gap-5 ">
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Gender: <span class="text-red-500">*</span></label>
                                                <select id="countries" name="gender"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                    <option value=""></option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                            </div>
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Birthdate: <span class="text-red-500">*</span></label>
                                                <input type="date" name="b_date"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Age: <span class="text-red-500">*</span></label>
                                                <input type="text" id="age" name="age"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Civil
                                                    Status: <span class="text-red-500">*</span></label>
                                                <select id="civil_status" name="civil_status"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                    <option selected>Select Civil Status</option>
                                                    <option value="Single">Single</option>
                                                    <option value="Married">Married</option>
                                                    <option value="Separated">Separated</option>
                                                    <option value="Widowed">Widowed</option>
                                                    <option value="Divorced">Divorced</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="w-full flex flex-row gap-5 ">

                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Citizenship: <span class="text-red-500">*</span></label>
                                                <input type="text" name="citizenship"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Religion: <span class="text-red-500">*</span></label>
                                                <input type="text" name="religion"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">IP Group:</label>
                                                <input type="text" name="ip_group"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                        </div>
                                        <div class="w-full flex flex-row gap-5">
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Birthplace: <span class="text-red-500">*</span></label>
                                                <input type="text" name="birth_place"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                        </div>
                                        <div class="w-full flex flex-row gap-5">
                                            <div class="w-full">
                                                <label for="email"
                                                    class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Educational Attainment: <span class="text-red-500">*</span></label>
                                                <input type="text" name="educational_attainment"
                                                    class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                    placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full flex-row justify-between items-center gap-5">
                                    <div class="w-50">
                                        <label for="email"
                                            class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Living
                                            Arrangement: <span class="text-red-500">*</span></label>
                                        <select id="living_arrangement" name="living_arrangement"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value=""></option>
                                            <option value="Owned">Owned</option>
                                            <option value="Living alone">Living alone</option>
                                            <option value="Living with relatives">Living with relatives</option>
                                            <option value="Rent">Rent</option>
                                        </select>
                                    </div>
                                    <div class="w-full">
                                        <label for="email"
                                            class="block mb-2 text-sm font-normal text-gray-900 dark:text-gray-300">Address:</label>
                                        <div class="flex flex-row gap-3">
                                            <input type="text" id="house_no" name="house_no"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="House No." required>
                                            <input type="text" id="street" name="street"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="Street" required>
                                            <select id="brgy" name="brgy"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light">
                                                <option selected>Barangay <span class="text-red-500">*</span></option>
                                                <option value="I - Mapalad" class="text-gray-900">I - Mapalad</option>
                                                <option value="II - Handang Tumulong">II - Handang Tumulong</option>
                                                <option value="III - Silahis ng Pag-asa">III - Silahis ng Pag-asa
                                                </option>
                                                <option value="IV - Pag-asa ng Bayan">IV - Pag-asa ng Bayan</option>
                                                <option value="V - Bagong Silang">V - Bagong Silang</option>
                                                <option value="VI - San Jose">VI - San Jose</option>
                                                <option value="VII - Lumang Bayan">VII - Lumang Bayan</option>
                                                <option value="VIII - Marikit">VIII - Marikit</option>
                                                <option value="IX - Tubili">IX - Tubili</option>
                                                <option value="X - Alipaoy">X - Alipaoy</option>
                                                <option value="XI - Harison">XI - Harison</option>
                                                <option value="XII - Mananao">XII - Mananao</option>
                                            </select>
                                            <input type="text" id="municipality" name="municipality"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="City/Municipality *" required>
                                            <input type="text" id="province" name="province"
                                                class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                                placeholder="Province *" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- step marks -->
                    <div class="flex justify-center items-center gap-8">
                        <div class="flex flex-col items-center gap-1">
                            <div class="border border-gray-400 rounded-full bg-blue-500 h-4 w-4"></div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">Step 1</p>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <div class="border border-gray-400 rounded-full bg-gray-500 h-4 w-4"></div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">Step 2</p>
                        </div>
                    </div>
                    <div class="w-full flex justify-end items-center  mb-2 ">
                        <button type="button" onclick="nextStep()"
                            class="text-white cursor-pointer bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-1 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Next</button>
                    </div>
                </section>
                <section id="step2" class="bg-white hidden flex-col dark:bg-gray-900 rounded-lg">
                    <div class="p-5 mb-10 gap-10 rounded-lg w-full flex flex-col">
                        <div>
                            <h4 class="text-sm font-medium dark:text-white mb-3">II. ECONOMIC STATUS</h4>
                            <div class="flex flex-col w-full  justify-between items-center gap-5">
                                <!-- Pensioner  -->
                                <div class="w-full flex justify-between  flex-row gap-5 ">
                                    <div class="w-50  flex flex-row gap-2 items-center">
                                        <label for="email"
                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Pensioner?</label>
                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                            <div class="flex items-center ">
                                                <input id="" type="radio" value="1" name="is_pensioner"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for=""
                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="0"
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
                                        <input type="text" id="" name="pension_amount" disabled
                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                            placeholder="" required>
                                    </div>
                                    <div class="w-full  flex flex-row items-center gap-2">
                                        <div class="flex flex-row w-full gap-2 items-center">
                                            <label for="source"
                                                class="block text-sm font-normal text-gray-900 dark:text-gray-300">Source:</label>
                                            <select id="sourceSelect" name="pension_source" disabled
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
                                <div class="w-full flex justify-between gap-5 pt-4 border-t border-gray-200 flex-row ">
                                    <div class="w-110  flex flex-row gap-2 items-center ">
                                        <label for="email"
                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Permanent
                                            Source of Income?</label>
                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                            <div class="flex items-center ">
                                                <input id="" type="radio" value="1" name="has_permanent_income"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for=""
                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="0"
                                                    name="has_permanent_income"
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
                                        <input type="text" id="" name="income_source" disabled
                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                            placeholder="" required>
                                    </div>
                                </div>
                                <!-- reg support from fam  -->
                                <div class="w-full flex justify-between flex-row gap-5 pt-4 border-t border-gray-200">
                                    <div class="w-full   flex flex-row gap-2 items-center">
                                        <label for="email"
                                            class="block  text-sm font-normal text-gray-900 dark:text-gray-300">Regular
                                            Support from Family?</label>
                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                            <div class="flex items-center ">
                                                <input id="" type="radio" value="1" name="has_family_support"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for=""
                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="0"
                                                    name="has_family_support"
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
                                        <input type="text" id="" name="support_type" disabled
                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                            placeholder="">
                                    </div>
                                    <div class="w-full flex boder flex-row gap-2 items-center">
                                        <label for="email"
                                            class="block text-sm w-70  font-normal text-gray-900 dark:text-gray-300">Cash
                                            (How much and how often)</label>
                                        <div class="flex flex-row gap-2 items-center">
                                            <input type="text" id="" name="support_cash" disabled
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
                            <h4 class="text-sm font-medium dark:text-white mb-3">III. HEALTH CONDITION</h4>
                            <div class="flex flex-col gap-5">
                                <div class="w-full flex justify-between  flex-row gap-5 ">
                                    <div class="w-120  flex flex-row gap-2  items-center">
                                        <label for="email"
                                            class="block  w-35 text-sm font-normal text-gray-900 dark:text-gray-300">Has
                                            existing illness?</label>
                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                            <div class="flex items-center ">
                                                <input id="" type="radio" value="1" name="has_existing_illness"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for=""
                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="0"
                                                    name="has_existing_illness"
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
                                        <input type="text" id="" name="illness_details" disabled
                                            class="shadow-sm w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block  p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light"
                                            placeholder="" required>
                                    </div>
                                    <div class="w-150  flex flex-row   gap-2 items-center">
                                        <label for="email"
                                            class="block w-65  text-sm font-normal text-gray-900 dark:text-gray-300">Hospitalized
                                            witihin the last six months?</label>
                                        <div class="flex flex-row gap-2 itemscenter justify-center">
                                            <div class="flex items-center ">
                                                <input id="" type="radio" value="1" name="hospitalized_last6mos"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for=""
                                                    class=" text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="0"
                                                    name="hospitalized_last6mos"
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
            <!-- PopUp Message  -->
            <div id="popupModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex z-50  items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out"
                    id="popupBox">
                    <h2 id="popupTitle" class="text-xl font-semibold mb-3 text-gray-800"></h2>
                    <p id="popupMessage" class="text-gray-600 mb-6 leading-relaxed"></p>
                    <button id="popupCloseBtn"
                        class="px-4 py-1 bg-blue-600 text-white text-xs rounded-sm font-medium hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">OK</button>
                </div>
            </div>
            <!-- Additional Registration Data Modal -->
            <div id="registrationModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex z-50 items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 transform scale-95 opacity-0 transition-all duration-300 ease-out">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Additional Registration Information</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Registration <span class="text-red-500">*</span></label>
                            <input type="date" id="date_of_registration"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ID Number <span class="text-red-500">*</span></label>
                            <input type="text" id="id_number"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter ID Number" required>
                            <p class="text-xs text-gray-500 mt-1">Must be unique for each applicant</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Local Control Number</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="local_control_number"
                                    class="flex-1 p-2 border border-gray-300 rounded-lg bg-gray-100"
                                    value="Auto-generated" readonly>
                                <button type="button" onclick="generateCustomLocalControlNumber()"
                                    class="px-3 py-2 text-xs bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                    Custom
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Separate local reference number (auto-generated or custom)</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideRegistrationModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="button" onclick="confirmRegistration()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            Confirm Registration
                        </button>
                    </div>
                </div>
            </div>
            <!-- Modal for showing missing required fields -->
            <div id="requiredFieldsModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex z-50 items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 transform scale-95 opacity-0 transition-all duration-300 ease-out">
                    <h2 class="text-xl font-semibold mb-4 text-red-600">Missing Required Fields</h2>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-3">Please fill in the following required fields:</p>
                        <ul id="missingFieldsList" class="text-sm text-gray-700 max-h-60 overflow-y-auto bg-gray-50 p-3 rounded-lg">
                            <!-- Missing fields will be listed here -->
                        </ul>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="hideRequiredFieldsModal()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Okay
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- disabled/enabled toggles input -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const birthDateInput = document.querySelector('input[name="b_date"]');
            const ageInput = document.getElementById("age");

            if (birthDateInput && ageInput) {
                birthDateInput.addEventListener("change", () => {
                    const birthDate = new Date(birthDateInput.value);
                    const today = new Date();

                    if (!isNaN(birthDate.getTime())) {
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const m = today.getMonth() - birthDate.getMonth();

                        // Adjust if birthday hasn't occurred yet this year
                        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }

                        ageInput.value = age >= 0 ? age : "";

                        // Store the calculated age for form submission
                        sessionStorage.setItem('calculatedAge', age);
                    } else {
                        ageInput.value = "";
                        sessionStorage.removeItem('calculatedAge');
                    }
                });

                // Also calculate on page load if birth date is already filled
                if (birthDateInput.value) {
                    birthDateInput.dispatchEvent(new Event('change'));
                }
            }

            // Enable/disable conditional fields based on radio button selections
            setupConditionalFields();
        });

        function setupConditionalFields() {
            // Pensioner fields
            const pensionerRadios = document.querySelectorAll('input[name="is_pensioner"]');
            const pensionAmount = document.querySelector('input[name="pension_amount"]');
            const pensionSource = document.querySelector('select[name="pension_source"]');

            pensionerRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const isPensioner = this.value === '1';
                    pensionAmount.disabled = !isPensioner;
                    pensionSource.disabled = !isPensioner;

                    if (!isPensioner) {
                        pensionAmount.value = '';
                        pensionSource.value = '';
                    }
                });
            });

            // Permanent Income fields
            const incomeRadios = document.querySelectorAll('input[name="has_permanent_income"]');
            const incomeSource = document.querySelector('input[name="income_source"]');

            incomeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIncome = this.value === '1';
                    incomeSource.disabled = !hasIncome;

                    if (!hasIncome) {
                        incomeSource.value = '';
                    }
                });
            });

            // Family Support fields
            const supportRadios = document.querySelectorAll('input[name="has_family_support"]');
            const supportType = document.querySelector('input[name="support_type"]');
            const supportCash = document.querySelector('input[name="support_cash"]');

            supportRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasSupport = this.value === '1';
                    supportType.disabled = !hasSupport;
                    supportCash.disabled = !hasSupport;

                    if (!hasSupport) {
                        supportType.value = '';
                        supportCash.value = '';
                    }
                });
            });

            // Illness fields
            const illnessRadios = document.querySelectorAll('input[name="has_existing_illness"]');
            const illnessDetails = document.querySelector('input[name="illness_details"]');

            illnessRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIllness = this.value === '1';
                    illnessDetails.disabled = !hasIllness;

                    if (!hasIllness) {
                        illnessDetails.value = '';
                    }
                });
            });
        }
    </script>

    <!-- Insert Applicant  -->
    <script>
        let isSubmitting = false;

        // Required fields configuration (only text/select fields as mentioned)
        const requiredFields = [{
                name: 'lname',
                label: 'Last Name'
            },
            {
                name: 'fname',
                label: 'First Name'
            },
            {
                name: 'mname',
                label: 'Middle Name'
            },
            {
                name: 'gender',
                label: 'Gender'
            },
            {
                name: 'b_date',
                label: 'Birthdate'
            },
            {
                name: 'age',
                label: 'Age'
            },
            {
                name: 'civil_status',
                label: 'Civil Status'
            },
            {
                name: 'citizenship',
                label: 'Citizenship'
            },
            {
                name: 'religion',
                label: 'Religion'
            },
            {
                name: 'birth_place',
                label: 'Birthplace'
            },
            {
                name: 'educational_attainment',
                label: 'Educational Attainment'
            },
            {
                name: 'living_arrangement',
                label: 'Living Arrangement'
            },
            {
                name: 'brgy',
                label: 'Barangay'
            },
            {
                name: 'municipality',
                label: 'City/Municipality'
            },
            {
                name: 'province',
                label: 'Province'
            }
        ];

        // Function to validate required fields
        function validateRequiredFields() {
            const missingFields = [];

            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field.name}"]`);
                if (element) {
                    const value = element.value.trim();
                    const isValid = value !== '' && value !== null && value !== undefined;

                    // Remove existing error styling
                    element.classList.remove('border-red-500', 'bg-red-50');

                    // Add error styling if field is invalid
                    if (!isValid) {
                        element.classList.add('border-red-500', 'bg-red-50');
                        missingFields.push(field.label);
                    }
                }
            });

            return missingFields;
        }

        // Function to clear error styling
        function clearErrorStyling() {
            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field.name}"]`);
                if (element) {
                    element.classList.remove('border-red-500', 'bg-red-50');
                }
            });
        }

        // Function to show required fields modal
        function showRequiredFieldsModal(missingFields) {
            const modal = document.getElementById("requiredFieldsModal");
            const box = modal.querySelector('div');
            const list = document.getElementById("missingFieldsList");

            // Clear previous list
            list.innerHTML = '';

            // Add missing fields to list
            missingFields.forEach(field => {
                const li = document.createElement('li');
                li.className = 'py-1 px-2 border-b border-gray-200 last:border-b-0';
                li.textContent = `• ${field}`;
                list.appendChild(li);
            });

            modal.classList.remove("hidden");
            setTimeout(() => {
                box.classList.remove("scale-95", "opacity-0");
                box.classList.add("scale-100", "opacity-100");
            }, 10);
        }

        // Function to hide required fields modal
        function hideRequiredFieldsModal() {
            const modal = document.getElementById("requiredFieldsModal");
            const box = modal.querySelector('div');

            box.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 200);
        }

        // Modal popup function
        function showPopup(message, type = "info", redirect = false) {
            const modal = document.getElementById("popupModal");
            const box = document.getElementById("popupBox");
            const title = document.getElementById("popupTitle");
            const msg = document.getElementById("popupMessage");
            const closeBtn = document.getElementById("popupCloseBtn");

            // Set message and styles
            msg.textContent = message;

            if (type === "success") {
                title.textContent = "✅ Success";
                title.className = "text-xl font-semibold mb-3 text-#FFFFFF";
                closeBtn.style.background = "#27AE60";
                msg.style.color = "#333333";
            } else if (type === "error") {
                title.textContent = "⚠️ Error";
                title.className = "text-xl font-semibold  mb-3 text-#FFFFFF";
                closeBtn.style.background = "Red";
                msg.style.color = "#333333";
            } else {
                title.textContent = "ℹ️ Notice";
                title.className = "text-xl font-semibold mb-3 text-#FFFFFF";
                closeBtn.style.background = "blue";
                msg.style.color = "#333333";
            }

            // Show modal and animate
            modal.classList.remove("hidden");
            setTimeout(() => {
                box.classList.remove("scale-95", "opacity-0");
                box.classList.add("scale-100", "opacity-100");
            }, 10);

            // Close button - FIXED: Only redirect on success, stay on page otherwise
            closeBtn.onclick = () => {
                box.classList.add("scale-95", "opacity-0");
                setTimeout(() => {
                    modal.classList.add("hidden");
                    if (redirect && type === "success") {
                        // Reset form and stay on the same page instead of redirecting
                        document.getElementById("applicantForm").reset();
                        prevStep(); // Go back to step 1
                        // Clear any stored data
                        sessionStorage.removeItem('calculatedAge');
                        // Clear error styling
                        clearErrorStyling();
                    }
                }, 200);
            };
        }

        // Show registration modal
        function showRegistrationModal() {
            const modal = document.getElementById("registrationModal");
            const box = modal.querySelector('div');

            // Set default values
            document.getElementById('date_of_registration').value = new Date().toISOString().split('T')[0];
            document.getElementById('local_control_number').value = "Auto-generated";
            document.getElementById('local_control_number').classList.remove('bg-yellow-100', 'border-yellow-300');
            document.getElementById('local_control_number').classList.add('bg-gray-100', 'border-gray-300');
            document.getElementById('id_number').value = "";

            modal.classList.remove("hidden");
            setTimeout(() => {
                box.classList.remove("scale-95", "opacity-0");
                box.classList.add("scale-100", "opacity-100");
            }, 10);
        }

        // Hide registration modal
        function hideRegistrationModal() {
            const modal = document.getElementById("registrationModal");
            const box = modal.querySelector('div');

            box.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 200);
        }

        // Generate custom local control number
        function generateCustomLocalControlNumber() {
            const customLocalControlNumber = prompt("Enter custom local control number:", "");
            if (customLocalControlNumber && customLocalControlNumber.trim() !== "") {
                document.getElementById('local_control_number').value = customLocalControlNumber.trim();
                document.getElementById('local_control_number').classList.remove('bg-gray-100', 'border-gray-300');
                document.getElementById('local_control_number').classList.add('bg-yellow-100', 'border-yellow-300');
                document.getElementById('local_control_number').readOnly = false;
            }
        }

        function nextStep() {
            // Validate step 1 required fields
            const missingFields = validateRequiredFields();

            if (missingFields.length > 0) {
                showRequiredFieldsModal(missingFields);
                return;
            }

            // If validation passes, proceed to next step
            document.getElementById("step1").classList.add("hidden");
            document.getElementById("step2").classList.remove("hidden");
        }

        function prevStep() {
            document.getElementById("step2").classList.add("hidden");
            document.getElementById("step1").classList.remove("hidden");
            // Clear error styling when going back
            clearErrorStyling();
        }

        // Collect form data
        function collectFormData() {
            const form = document.getElementById("applicantForm");
            const data = {};

            form.querySelectorAll("input, select, textarea").forEach(el => {
                if (!el.name) return;
                if (el.type === "radio") {
                    if (el.checked) data[el.name] = parseInt(el.value, 10);
                } else if (el.type === "checkbox") {
                    data[el.name] = el.checked ? 1 : 0;
                } else {
                    data[el.name] = el.value.trim();
                }
            });

            return data;
        }

        // Updated submitForm function - now shows registration modal
        async function submitForm() {
            if (isSubmitting) return;

            // Validate required fields in step 2 (radio buttons) - KEEPING YOUR EXISTING VALIDATION
            const step2Fields = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            let hasErrors = false;

            step2Fields.forEach(field => {
                const radios = document.querySelectorAll(`input[name="${field}"]:checked`);
                if (radios.length === 0) {
                    hasErrors = true;
                    // Highlight the field that's missing
                    const fieldGroup = document.querySelector(`input[name="${field}"]`).closest('.flex.items-center');
                    if (fieldGroup) {
                        fieldGroup.style.border = "1px solid red";
                        fieldGroup.style.padding = "4px";
                        fieldGroup.style.borderRadius = "4px";
                    }
                }
            });

            if (hasErrors) {
                showPopup("Please complete all required fields in Step 2.", "error");
                return;
            }

            // Show registration modal instead of submitting directly
            showRegistrationModal();
        }

        // Confirm registration and submit form data
        async function confirmRegistration() {
            const dateOfRegistration = document.getElementById('date_of_registration').value;
            const idNumber = document.getElementById('id_number').value.trim();
            const localControlNumber = document.getElementById('local_control_number').value;

            if (!dateOfRegistration) {
                showPopup("Please select a date of registration.", "error");
                return;
            }

            if (!idNumber) {
                showPopup("Please enter an ID number.", "error");
                return;
            }

            // Validate ID number format
            const idNumberPattern = /^[A-Za-z0-9-]+$/;
            if (!idNumberPattern.test(idNumber)) {
                showPopup("Please enter a valid ID number (letters, numbers, and hyphens only).", "error");
                return;
            }

            // Validate local control number if custom
            if (localControlNumber !== "Auto-generated" && localControlNumber !== "") {
                const controlNumberPattern = /^[A-Za-z0-9-]+$/;
                if (!controlNumberPattern.test(localControlNumber)) {
                    showPopup("Please enter a valid local control number (letters, numbers, and hyphens only).", "error");
                    return;
                }
            }

            // Add the additional data to the form data
            const formData = collectFormData();
            formData.date_of_registration = dateOfRegistration;
            formData.id_number = idNumber;
            formData.local_control_number = localControlNumber === "Auto-generated" ? "" : localControlNumber;

            hideRegistrationModal();
            await submitFormData(formData);
        }

        // Separate function for actual form submission
        async function submitFormData(formData) {
            if (isSubmitting) return;
            isSubmitting = true;

            const submitBtn = document.querySelector('button[onclick="submitForm()"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";
            }

            try {
                const res = await fetch("/mswdpaluan_system-main/php/register/applicant.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(formData),
                });

                const text = await res.text();
                try {
                    const json = JSON.parse(text);

                    if (json.success) {
                        let successMessage = "Application submitted successfully!";
                        if (json.local_control_number) {
                            successMessage += ` | Local Control Number: ${json.local_control_number}`;
                        }

                        showPopup(successMessage, "success", true);
                        // Clear stored age after successful submission
                        sessionStorage.removeItem('calculatedAge');

                        // RESET THE BUTTON STATE ON SUCCESS
                        isSubmitting = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = "Submit";
                        }
                    } else {
                        showPopup(json.error || "Submission failed.", "error");
                        // RESET THE BUTTON STATE ON ERROR TOO
                        isSubmitting = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = "Submit";
                        }
                    }
                } catch {
                    console.error("Server returned non-JSON:", text);
                    showPopup("Unexpected server response. Check console for details.", "error");
                    // RESET THE BUTTON STATE ON PARSE ERROR
                    isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Submit";
                    }
                }
            } catch (err) {
                showPopup("Network error: " + err.message, "error");
                // RESET THE BUTTON STATE ON NETWORK ERROR
                isSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit";
                }
            }
        }

        // Reset field highlighting when user interacts with radio buttons
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                const fieldGroup = e.target.closest('.flex.items-center');
                if (fieldGroup && fieldGroup.style.border === '1px solid red') {
                    fieldGroup.style.border = '';
                    fieldGroup.style.padding = '';
                    fieldGroup.style.borderRadius = '';
                }
            }
        });

        // Add real-time validation for fields (clear errors when user starts typing)
        document.addEventListener('DOMContentLoaded', function() {
            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field.name}"]`);
                if (element) {
                    element.addEventListener('input', function() {
                        if (this.value.trim() !== '') {
                            this.classList.remove('border-red-500', 'bg-red-50');
                        }
                    });

                    // For select elements
                    if (element.tagName === 'SELECT') {
                        element.addEventListener('change', function() {
                            if (this.value !== '') {
                                this.classList.remove('border-red-500', 'bg-red-50');
                            }
                        });
                    }
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const birthDateInput = document.querySelector('input[name="b_date"]');
            const ageInput = document.getElementById("age");

            if (birthDateInput && ageInput) {
                birthDateInput.addEventListener("change", () => {
                    const birthDate = new Date(birthDateInput.value);
                    const today = new Date();

                    if (!isNaN(birthDate.getTime())) {
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const m = today.getMonth() - birthDate.getMonth();

                        // Adjust if birthday hasn't occurred yet this year
                        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }

                        ageInput.value = age >= 0 ? age : "";
                    } else {
                        ageInput.value = "";
                    }
                });
            }
        });
    </script>
</body>

</html>