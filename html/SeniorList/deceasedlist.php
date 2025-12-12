<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deceased List</title>
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
                                <a href="./seniorlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Senior
                                    List</a>
                            </li>
                            <li>
                                <a href="./activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="./inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="#" style="color: blue;"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-blue-700 bg-blue-100 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
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
                    </li>
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
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                        <div
                            class="flex flex-col md:flex-col justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <h4 class="text-xl font-medium dark:text-white">Deceased List</h4>
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
                                        <th class="px-4 py-3">Date of Death</th>
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
                    <p id="popupMessage" class="text-sm text-gray-700 dark:text-gray-300 mb-4">Message</p>
                    <div class="flex justify-end">
                        <button id="popupCloseBtn"
                            class="px-4 py-1 bg-blue-600 cursor-pointer text-white text-xs rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                            OK
                        </button>
                    </div>
                </div>
            </div>
            <!--  Confirmation Modal -->
            <div id="confirmModal"
                class="fixed inset-0 bg-gray-600/50 bg-opacity-40 hidden flex z-50 items-center justify-center">
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
                fetch(`../../php/deceasedlist/fetch_deceased.php?${params}`)
                    .then(res => res.json())
                    .then(data => {
                        tbody.innerHTML = "";
                        totalRecords = data.total_records;
                        totalPages = data.total_pages;

                        if (!data.deceased || data.deceased.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-gray-500">No deceased records found.</td></tr>`;
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
                            <td>${row.date_of_death || ""}</td>
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
                    const res = await fetch(`../../php/deceasedlist/undo_deceased.php?id=${id}`, {
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

                    const response = await fetch(`../../php/archived/archived.php`, {
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