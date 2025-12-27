<?php
require_once "/MSWDPALUAN_SYSTEM-MAIN/php/login/admin_header.php";
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
                                <a href="../php/login/logout.php"
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
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 bg-blue-100 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                            <svg
                                class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400 group-hover:text-blue-700 dark:group-hover:text-white"
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
                        <a href=".//generate_id.php?session_context=<?php echo $ctx; ?>"
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
        <!-- Beneficiary  -->
        <main class="p-4 md:ml-64 pt-20 flex flex-col">
            <div class="flex items-center flex-row ">
                <div class="border border-t-0 border-l-0">
                    <a href="./benefits.php?session_context=<?php echo $ctx; ?>" type="button" class="cursor-pointer">
                        <h4 class="text-xl font-medium  px-2">Benefits</h4>
                    </a>
                </div>
                <div class="border-b-0 border border-l-0 ">
                    <a href="#" type="button" class="cursor-pointer">
                        <h4 class="text-xl font-medium text-blue-700  px-2">Beneficiaries</h4>
                    </a>
                </div>
                <div class="border-t-0 w-full border border-l-0 border-r-0 h-[30px]">
                </div>
            </div>
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-5xl">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                        <div
                            class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <h4 class="text-xl font-medium dark:text-white">Beneficiaries</h4>
                            <div class="w-full md:w-1/2">
                                <form class="flex items-center">
                                    <label for="simple-search" class="sr-only">Search</label>
                                    <div class="relative w-full">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                                fill="currentColor" viewbox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd"
                                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <input type="text" id="simple-search"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                            placeholder="Search" required="">
                                    </div>
                                </form>
                            </div>
                            <div class="flex flex-row gap-5">
                                <!-- Add Benefit Button (Initially Hidden) -->
                                <div id="addBenefitBtnContainer" class="flex flex-row gap-2 hidden">
                                    <button id="addBenefitBtn"
                                        class="px-3 py-2 cursor-pointer text-xs font-medium text-white bg-green-600 rounded-sm hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                                        💰 Add Benefit
                                    </button>
                                </div>

                                <!-- Filter Button -->
                                <div class="relative w-full md:w-auto">
                                    <button id="filterDropdownButton" data-dropdown-toggle="filterDropdown"
                                        class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg md:w-auto focus:outline-none hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                            class="w-4 h-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Filter
                                        <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path clip-rule="evenodd" fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                        </svg>
                                    </button>

                                    <!-- Dynamic Dropdown -->
                                    <div id="filterDropdown"
                                        class="z-10 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                                        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                            Barangay
                                        </h6>
                                        <ul id="barangayList" class="space-y-2 text-sm" aria-labelledby="dropdownDefault">
                                            <li class="text-gray-400 text-sm text-center">Loading...</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                                <thead
                                    class="text-xs text-center text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">
                                            <input id="selectAllCheckbox" type="checkbox"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        </th>
                                        <th scope="col" class="px-4 py-3">No.</th>
                                        <th scope="col" class="px-4 py-3">Name</th>
                                        <th scope="col" class="px-4 py-3">Birthdate</th>
                                        <th scope="col" class="px-4 py-3">Age</th>
                                        <th scope="col" class="px-4 py-3">Gender</th>
                                        <th scope="col" class="px-4 py-3">Civil Status</th>
                                        <th scope="col" class="px-4 py-3">Barangay</th>
                                        <th scope="col" class="px-4 py-3">Date Registered</th>
                                        <th scope="col" class="px-4 py-3">Date Modified</th>
                                        <th scope="col" class="px-4 py-3">Status</th>
                                        <th scope="col" class="px-4 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="beneficiaryBody">
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <nav id="paginationNav"
                            class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-3"
                            aria-label="Table navigation">
                        </nav>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Benefit Modal -->
    <div id="addBenefitModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto flex bg-gray-900/50 overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add Benefits
                    </h3>
                    <button type="button" id="closeBenefitModal"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form id="benefitForm">
                    <div class="grid gap-4 mb-4 sm:grid-cols-1">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Selected Beneficiaries
                            </label>
                            <div id="selectedBeneficiariesList" class="max-h-32 overflow-y-auto border rounded p-2 dark:border-gray-600">
                                <!-- Selected beneficiaries will be listed here -->
                            </div>
                        </div>
                        <div>
                            <label for="benefitsSelect" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Benefits
                            </label>
                            <div id="benefitsCheckboxContainer" class="max-h-40 overflow-y-auto border rounded p-3 dark:border-gray-600">
                                <!-- Benefits checkboxes with amount inputs will be populated here -->
                            </div>
                        </div>
                        <div>
                            <label for="benefitDate" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Date
                            </label>
                            <input type="date" id="benefitDate" name="benefitDate"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Benefits
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Benefits Modal -->
    <div id="viewBenefitsModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto flex bg-gray-900/50 overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
        <div class="relative p-4 w-full max-w-4xl h-full md:h-auto">
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b border-gray-500 sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <span id="viewBeneficiaryName"></span> - Benefits History
                    </h3>
                    <button type="button" id="closeViewBenefitsModal"
                        class="text-gray-400 bg-transparent cursor-pointer hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Benefit Name</th>
                                <th scope="col" class="px-4 py-3">Amount</th>
                                <th scope="col" class="px-4 py-3">Date Received</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="benefitsHistoryBody">
                            <!-- Benefits history will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-between items-center pt-4 mt-4  dark:border-gray-600">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Total Benefits Received: ₱<span id="totalBenefitsAmount">0.00</span>
                    </div>
                    <button type="button" id="closeViewBenefitsModalBottom"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-sm cursor-pointer text-sm px-3 py-1 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup Modal -->
    <div id="popupModal"
        class="fixed inset-0 bg-gray-600/50 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div id="popupBox"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg transform scale-95 opacity-0 transition-all duration-200 w-80 p-4">
            <h2 id="popupTitle" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Title</h2>
            <p id="popupMessage" class="text-sm text-gray-700 dark:text-gray-300 mb-4">Message</p>
            <div class="flex justify-end">
                <button id="popupCloseBtn"
                    class="px-4 py-1 bg-blue-600 cursor-pointer text-white text-xs rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    OK
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>

    <!-- Beneficiary Table Script -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Global variables
            window.globalSelectedBeneficiaries = new Map();

            const tableBody = document.getElementById("beneficiaryBody");
            const barangayList = document.getElementById("barangayList");
            const searchInput = document.getElementById("simple-search");
            const paginationNav = document.getElementById("paginationNav");
            const addBenefitBtnContainer = document.getElementById("addBenefitBtnContainer");
            const addBenefitBtn = document.getElementById("addBenefitBtn");
            const selectAllCheckbox = document.getElementById("selectAllCheckbox");
            const addBenefitModal = document.getElementById("addBenefitModal");
            const closeBenefitModal = document.getElementById("closeBenefitModal");
            const benefitForm = document.getElementById("benefitForm");
            const selectedBeneficiariesList = document.getElementById("selectedBeneficiariesList");
            const benefitsCheckboxContainer = document.getElementById("benefitsCheckboxContainer");

            // View Benefits Modal elements
            const viewBenefitsModal = document.getElementById("viewBenefitsModal");
            const closeViewBenefitsModal = document.getElementById("closeViewBenefitsModal");
            const closeViewBenefitsModalBottom = document.getElementById("closeViewBenefitsModalBottom");
            const viewBeneficiaryName = document.getElementById("viewBeneficiaryName");
            const benefitsHistoryBody = document.getElementById("benefitsHistoryBody");
            const totalBenefitsAmount = document.getElementById("totalBenefitsAmount");

            let currentPage = 1;
            let totalPages = 1;
            let totalRecords = 0;
            let lastSearch = "";
            let selectedBarangays = [];
            let availableBenefits = [];

            // ---------------- POPUP MODAL ----------------
            function showPopup(message, type = "info") {
                const modal = document.getElementById("popupModal");
                const box = document.getElementById("popupBox");
                const title = document.getElementById("popupTitle");
                const msg = document.getElementById("popupMessage");
                const closeBtn = document.getElementById("popupCloseBtn");

                msg.textContent = message;
                title.className = "text-lg font-semibold mb-2";

                switch (type) {
                    case "success":
                        title.textContent = "✅ Success";
                        closeBtn.style.backgroundColor = "#27AE60";
                        break;
                    case "error":
                        title.textContent = "❌ Error";
                        closeBtn.style.backgroundColor = "#E74C3C";
                        break;
                    default:
                        title.textContent = "ℹ️ Notice";
                        closeBtn.style.backgroundColor = "#3498DB";
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
                    }, 200);
                };
            }

            // ---------------- UPDATE BUTTON VISIBILITY ----------------
            function updateAddBenefitVisibility() {
                if (window.globalSelectedBeneficiaries.size > 0) {
                    addBenefitBtnContainer.classList.remove('hidden');
                } else {
                    addBenefitBtnContainer.classList.add('hidden');
                }
            }

            // ---------------- FETCH BARANGAYS ----------------
            function fetchBarangays() {
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?mode=barangays")
                    .then(res => res.json())
                    .then(barangays => {
                        barangayList.innerHTML = "";
                        barangays.forEach((b, i) => {
                            const id = `barangay-${i}`;
                            barangayList.insertAdjacentHTML("beforeend", `
                        <li class="flex items-center">
                            <input id="${id}" type="checkbox" value="${b}"
                                class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 
                                       focus:ring-primary-500 dark:focus:ring-primary-600 
                                       dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                            <label for="${id}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                ${b}
                            </label>
                        </li>
                    `);
                        });

                        barangayList.querySelectorAll("input[type='checkbox']").forEach(cb => {
                            cb.addEventListener("change", () => {
                                selectedBarangays = Array.from(barangayList.querySelectorAll("input[type='checkbox']:checked"))
                                    .map(cb => cb.value);
                                currentPage = 1;
                                fetchBeneficiaries();
                            });
                        });
                    })
                    .catch(err => {
                        console.error("Error loading barangays:", err);
                        barangayList.innerHTML = `<li class='text-red-500 text-center'>Error loading barangays</li>`;
                    });
            }

            // ---------------- FETCH BENEFITS ----------------
            function fetchBenefits() {
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits.php")
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            availableBenefits = data.benefits || [];
                            populateBenefitsCheckboxes();
                        } else {
                            console.error("Error loading benefits:", data.message);
                            showPopup(data.message || "Failed to load benefits.", "error");
                            availableBenefits = [];
                            populateBenefitsCheckboxes();
                        }
                    })
                    .catch(err => {
                        console.error("Error loading benefits:", err);
                        showPopup("Failed to load benefits.", "error");
                        availableBenefits = [];
                        populateBenefitsCheckboxes();
                    });
            }

            // ---------------- FETCH BENEFITS HISTORY ----------------
            async function fetchBenefitsHistory(applicantId, fullName) {
                try {
                    const response = await fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/benefits/fetch_benefits_history.php?applicant_id=${applicantId}`);
                    const data = await response.json();

                    // Update modal title
                    viewBeneficiaryName.textContent = fullName;

                    // Clear previous data
                    benefitsHistoryBody.innerHTML = '';
                    let totalAmount = 0;

                    if (data.success && data.benefits && data.benefits.length > 0) {
                        data.benefits.forEach(benefit => {
                            const row = `
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-3">${benefit.benefit_name}</td>
                                    <td class="px-4 py-3">₱${parseFloat(benefit.amount).toFixed(2)}</td>
                                    <td class="px-4 py-3">${new Date(benefit.date_received).toLocaleDateString()}</td>
                                    <td class="px-4 py-3">
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                            Received
                                        </span>
                                    </td>
                                </tr>
                            `;
                            benefitsHistoryBody.insertAdjacentHTML('beforeend', row);
                            totalAmount += parseFloat(benefit.amount);
                        });
                    } else {
                        benefitsHistoryBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                    No benefits received yet.
                                </td>
                            </tr>
                        `;
                    }

                    // Update total amount
                    totalBenefitsAmount.textContent = totalAmount.toFixed(2);

                    // Show modal
                    viewBenefitsModal.classList.remove('hidden');

                } catch (error) {
                    console.error('Error fetching benefits history:', error);
                    showPopup('Failed to load benefits history.', 'error');
                }
            }

            // ---------------- POPULATE BENEFITS CHECKBOXES WITH AMOUNT INPUTS ----------------
            function populateBenefitsCheckboxes() {
                benefitsCheckboxContainer.innerHTML = "";

                if (availableBenefits.length === 0) {
                    benefitsCheckboxContainer.innerHTML = '<p class="text-gray-500 text-center">No benefits available</p>';
                    return;
                }

                availableBenefits.forEach((benefit, index) => {
                    const benefitId = `benefit-${index}`;
                    const amountId = `amount-${index}`;

                    benefitsCheckboxContainer.insertAdjacentHTML("beforeend", `
                        <div class="benefit-item mb-3 p-2 border border-gray-200 rounded dark:border-gray-600">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="${benefitId}" value="${benefit.id}" 
                                    class="benefit-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="${benefitId}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    ${benefit.benefit_name}
                                </label>
                            </div>
                            <div class="benefit-amount-container ml-6 hidden">
                                <label for="${amountId}" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-400">
                                    Amount for ${benefit.benefit_name}
                                </label>
                                <input type="number" id="${amountId}" 
                                    class="benefit-amount bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                    placeholder="Enter amount" min="0" step="0.01">
                            </div>
                        </div>
                    `);
                });

                // Add event listeners to show/hide amount inputs
                benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const amountContainer = this.closest('.benefit-item').querySelector('.benefit-amount-container');
                        if (this.checked) {
                            amountContainer.classList.remove('hidden');
                        } else {
                            amountContainer.classList.add('hidden');
                        }
                    });
                });
            }

            // ---------------- FETCH BENEFICIARIES ----------------
            const fetchBeneficiaries = () => {
                const params = new URLSearchParams({
                    page: currentPage,
                    search: lastSearch,
                    barangays: selectedBarangays.join(',')
                });

                fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`)
                    .then(res => res.json())
                    .then(data => {
                        tableBody.innerHTML = "";
                        totalRecords = data.total_records;
                        totalPages = data.total_pages;

                        if (!data.seniors || data.seniors.length === 0) {
                            tableBody.innerHTML = `
                        <tr>
                            <td colspan="12" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                No beneficiary records found.
                            </td>
                        </tr>`;
                            paginationNav.innerHTML = "";
                            return;
                        }

                        data.seniors.forEach((senior, index) => {
                            const statusColor =
                                senior.validation === "Validated" ? "text-green-600" :
                                senior.validation === "For Validation" ? "text-red-600" :
                                "text-red-600";

                            const createdAt = senior.date_created ? new Date(senior.date_created).toLocaleDateString() : "";
                            const modifiedAt = senior.date_modified ? new Date(senior.date_modified).toLocaleDateString() : "";

                            const buttonId = `dropdownBtn-${index}`;
                            const dropdownId = `dropdownMenu-${index}`;

                            const row = `
                        <tr class="border-b text-xs font-medium text-center border-gray-200">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="beneficiaryCheckbox border-gray-600" 
                                    value="${senior.applicant_id}" data-name="${senior.full_name || 'Unknown'}">
                            </td>
                            <td>${senior.rownum}</td>
                            <td>${senior.full_name || ""}</td>
                            <td>${senior.birth_date || ""}</td>
                            <td>${senior.age || ""}</td>
                            <td>${senior.gender || ""}</td>
                            <td>${senior.civil_status || ""}</td>
                            <td>${senior.barangay || ""}</td>
                            <td>${createdAt}</td>
                            <td>${modifiedAt}</td>
                            <td class=" ${statusColor}">${senior.validation}</td>
                            <td class="relative">
                                <button id="${buttonId}" 
                                    class="inline-flex cursor-pointer items-center p-1 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd"
                                            d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                                            clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div id="${dropdownId}" 
                                    class="hidden absolute right-0 top-8 z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow-lg dark:bg-gray-700 dark:divide-gray-600">
                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li>
                                            <button onclick="viewBeneficiary(${senior.applicant_id}, '${senior.full_name || 'Unknown'}')" 
                                               class="block py-2 cursor-pointer px-4 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                                👁 View Benefits
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="addSingleBenefit('${senior.applicant_id}', '${senior.full_name || 'Unknown'}')"
                                               class="block cursor-pointer py-2 px-4 w-full text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                                💰 Add Benefit
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>`;

                            tableBody.insertAdjacentHTML("beforeend", row);

                            const button = document.getElementById(buttonId);
                            const menu = document.getElementById(dropdownId);
                            const checkbox = tableBody.querySelector(`.beneficiaryCheckbox[value="${senior.applicant_id}"]`);

                            // Set checkbox state based on global selection
                            checkbox.checked = window.globalSelectedBeneficiaries.has(senior.applicant_id);

                            checkbox.addEventListener("change", () => {
                                const beneficiaryId = checkbox.value;
                                const beneficiaryName = checkbox.dataset.name?.trim() || "Unknown";

                                if (checkbox.checked) {
                                    window.globalSelectedBeneficiaries.set(beneficiaryId, beneficiaryName);
                                } else {
                                    window.globalSelectedBeneficiaries.delete(beneficiaryId);
                                }

                                updateSelectAllCheckbox();
                                updateAddBenefitVisibility();
                            });

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
                    .catch(err => {
                        console.error("Error fetching beneficiaries:", err);
                        tableBody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-red-500">Error loading data.</td></tr>`;
                    });
            };

            // ---------------- RENDER PAGINATION ----------------
            const renderPagination = (start, end) => {
                if (totalPages <= 1) {
                    paginationNav.innerHTML = "";
                    return;
                }

                let html = `
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        Showing <span class="font-semibold text-gray-900 dark:text-white">${start}</span> –
                        <span class="font-semibold text-gray-900 dark:text-white">${end}</span> of
                        <span class="font-semibold text-gray-900 dark:text-white">${totalRecords}</span>
                    </span>
                    <ul class="inline-flex items-stretch -space-x-px">
                `;

                // Previous Button
                html += `
                    <li>
                        <button ${currentPage === 1 ? "disabled" : ""} data-nav="prev"
                            class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 ml-0 text-gray-500 bg-white rounded-l-sm border border-gray-300 
                            hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 
                                01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 
                                011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </li>
                `;

                // Page Numbers
                for (let i = 1; i <= totalPages; i++) {
                    html += `
                        <li>
                            <button data-page="${i}"
                                class="flex items-center justify-center text-sm py-2 px-3 leading-tight 
                                ${i === currentPage
                            ? 'z-10 text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 cursor-pointer bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'}">
                                ${i}
                            </button>
                        </li>
                    `;
                }

                // Next Button
                html += `
                    <li>
                        <button ${currentPage === totalPages ? "disabled" : ""} data-nav="next"
                            class="flex cursor-pointer items-center justify-center h-full py-[7px] px-2 text-gray-500 bg-white rounded-r-sm border border-gray-300 
                            hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 
                                011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 
                                01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </li>
                </ul>`;

                paginationNav.innerHTML = html;

                // Pagination event listeners
                paginationNav.querySelectorAll("[data-page]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        currentPage = parseInt(btn.dataset.page);
                        fetchBeneficiaries();
                    });
                });

                paginationNav.querySelectorAll("[data-nav]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        if (btn.dataset.nav === "prev" && currentPage > 1) currentPage--;
                        else if (btn.dataset.nav === "next" && currentPage < totalPages) currentPage++;
                        fetchBeneficiaries();
                    });
                });
            };

            // ---------------- SELECT ALL FUNCTIONALITY ----------------
            function updateSelectAllCheckbox() {
                const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");
                const checkedCheckboxes = tableBody.querySelectorAll(".beneficiaryCheckbox:checked");

                if (checkboxes.length === 0) {
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                        selectAllCheckbox.disabled = true;
                    }
                    return;
                }

                if (selectAllCheckbox) {
                    selectAllCheckbox.disabled = false;

                    if (checkedCheckboxes.length === checkboxes.length) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length > 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    }
                }
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener("change", function() {
                    const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");

                    checkboxes.forEach(checkbox => {
                        const isChecked = this.checked;
                        checkbox.checked = isChecked;

                        // Update global selection
                        const beneficiaryId = checkbox.value;
                        const beneficiaryName = checkbox.dataset.name?.trim() || "Unknown";

                        if (isChecked) {
                            window.globalSelectedBeneficiaries.set(beneficiaryId, beneficiaryName);
                        } else {
                            window.globalSelectedBeneficiaries.delete(beneficiaryId);
                        }
                    });

                    this.indeterminate = false;
                    updateAddBenefitVisibility();
                });
            }

            // ---------------- ADD BENEFIT MODAL ----------------
            if (addBenefitBtn) {
                addBenefitBtn.addEventListener("click", () => {
                    if (window.globalSelectedBeneficiaries.size === 0) {
                        showPopup("Please select at least one beneficiary.", "error");
                        return;
                    }

                    // Update selected beneficiaries list
                    selectedBeneficiariesList.innerHTML = "";
                    window.globalSelectedBeneficiaries.forEach((name, id) => {
                        selectedBeneficiariesList.insertAdjacentHTML("beforeend", `
                            <div class="text-sm text-gray-700 dark:text-gray-300">• ${name}</div>
                        `);
                    });

                    // Reset form and hide all amount inputs
                    benefitForm.reset();
                    benefitsCheckboxContainer.querySelectorAll('.benefit-amount-container').forEach(container => {
                        container.classList.add('hidden');
                    });
                    benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    // Set today's date as default
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('benefitDate').value = today;

                    // Show modal
                    addBenefitModal.classList.remove('hidden');
                });
            }

            if (closeBenefitModal) {
                closeBenefitModal.addEventListener("click", () => {
                    addBenefitModal.classList.add('hidden');
                });
            }

            // ---------------- VIEW BENEFITS MODAL ----------------
            if (closeViewBenefitsModal) {
                closeViewBenefitsModal.addEventListener("click", () => {
                    viewBenefitsModal.classList.add('hidden');
                });
            }

            if (closeViewBenefitsModalBottom) {
                closeViewBenefitsModalBottom.addEventListener("click", () => {
                    viewBenefitsModal.classList.add('hidden');
                });
            }

            // Handle form submission
            if (benefitForm) {
                benefitForm.addEventListener("submit", async (e) => {
                    e.preventDefault();

                    const selectedBenefits = [];
                    benefitsCheckboxContainer.querySelectorAll('.benefit-checkbox:checked').forEach(checkbox => {
                        const benefitId = checkbox.value;
                        const benefitName = checkbox.closest('.benefit-item').querySelector('label').textContent.trim();
                        const amountInput = checkbox.closest('.benefit-item').querySelector('.benefit-amount');
                        const amount = amountInput.value.trim();

                        if (!amount) {
                            showPopup(`Please enter amount for ${benefitName}`, "error");
                            amountInput.focus();
                            throw new Error(`Amount required for ${benefitName}`);
                        }

                        selectedBenefits.push({
                            id: benefitId,
                            name: benefitName,
                            amount: parseFloat(amount)
                        });
                    });

                    if (selectedBenefits.length === 0) {
                        showPopup("Please select at least one benefit and enter amounts.", "error");
                        return;
                    }

                    const formData = new FormData(benefitForm);
                    const benefitData = {
                        applicant_ids: Array.from(window.globalSelectedBeneficiaries.keys()),
                        benefits: selectedBenefits,
                        date: formData.get('benefitDate')
                    };

                    try {
                        const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/benefits/add_benefits.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(benefitData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            showPopup(result.message, "success");
                            addBenefitModal.classList.add('hidden');

                            // Clear selection
                            window.globalSelectedBeneficiaries.clear();
                            updateAddBenefitVisibility();
                            updateSelectAllCheckbox();
                        } else {
                            showPopup(result.message || "Failed to add benefits.", "error");
                        }
                    } catch (error) {
                        console.error('Error adding benefits:', error);
                        if (error.message && error.message.includes('Amount required')) {
                            // Error already handled above
                        } else {
                            showPopup("An error occurred while adding benefits.", "error");
                        }
                    }
                });
            }

            // ---------------- SEARCH ----------------
            let searchTimeout;
            searchInput.addEventListener("input", (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    lastSearch = e.target.value.trim();
                    currentPage = 1;
                    fetchBeneficiaries();
                }, 400);
            });

            // ---------------- ACTION FUNCTIONS ----------------
            window.viewBeneficiary = (applicantId, fullName) => {
                fetchBenefitsHistory(applicantId, fullName);
            };

            window.addSingleBenefit = (applicantId, fullName) => {
                // Clear existing selection and select only this beneficiary
                window.globalSelectedBeneficiaries.clear();
                window.globalSelectedBeneficiaries.set(applicantId, fullName);

                // Update checkboxes
                const checkboxes = tableBody.querySelectorAll(".beneficiaryCheckbox");
                checkboxes.forEach(checkbox => {
                    checkbox.checked = (checkbox.value === applicantId);
                });

                updateSelectAllCheckbox();
                updateAddBenefitVisibility();

                // Open the add benefit modal
                addBenefitBtn.click();
            };

            // ---------------- INITIAL LOAD ----------------
            fetchBarangays();
            fetchBenefits();
            fetchBeneficiaries();
        });
    </script>
</body>

</html>