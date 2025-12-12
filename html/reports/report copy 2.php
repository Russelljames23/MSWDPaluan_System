<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior List</title>
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
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-blue-100 dark:hover:bg-gray-700 group">
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
                                <a href="../SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Senior
                                    List</a>
                            </li>
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
                    <li>
                    <li>
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg transition duration-75 bg-blue-100 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                            <svg class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400 group-hover:text-blue-700 dark:group-hover:text-white"
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
        <main class="p-4 md:ml-64 pt-17">
            
            <div class="w-full flex justify-between items-center mb-4">
                <div><?php require_once "../../php/reports/date_filter_component.php"; ?></div> <!-- Empty div for spacing -->
                <button type="button" onclick="generateReport()"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-sm text-sm px-3 py-2 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    Generate Report
                </button>
            </div>
            <div class="w-full items-center justify-center">
                <h4 class="text-2xl font-bold dark:text-white text-center">Monthly Reports</h4>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-center gap-3 mt-2 mb-4">
                <?php
                // Calculate display text
                $displayText = 'All Time';
                if ($currentYear && $currentMonth) {
                    $monthNames = [
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    ];
                    $displayText = $monthNames[$currentMonth] . ' ' . $currentYear;
                } elseif ($currentYear) {
                    $displayText = 'Year ' . $currentYear;
                } elseif ($currentMonth) {
                    $displayText = $monthNames[$currentMonth] . ' (All Years)';
                }
                ?>
                <h4 class="text-xl font-medium dark:text-white px-2 text-center" id="reportPeriod">
                    <?php echo htmlspecialchars($displayText); ?>
                </h4>
                <div class="flex flex-wrap justify-center gap-2">
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="loadPart1()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-white focus:outline-none bg-blue-700 rounded-sm border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        I
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part2()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        II
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part3()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        III
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part4()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        IV
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part5()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        V
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part6()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        VI
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="part7to9()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        VII-IX
                    </button>
                    <button type="button" style="font-family: 'Times New Roman', Times, serif;" onclick="benefits()"
                        class="py-1 px-3 w-20 cursor-pointer text-sm font-black text-gray-900 focus:outline-none bg-white rounded-sm border border-gray-200 hover:bg-gray-100 hover:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Benefits
                    </button>
                </div>
            </div>

            <!-- Part I -->
            <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                        <div class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 p-4">
                            <h4 class="text-lg font-medium dark:text-white text-center md:text-left" style="font-family: 'Times New Roman', Times, serif;">
                                I. Number of Registered Senior Citizens
                            </h4>
                            <!-- <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                <div class="relative w-full sm:w-auto">
                                    <button id="monthDropdownButton" data-dropdown-toggle="monthDropdown"
                                        class="flex items-center cursor-pointer justify-between w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <span class="flex items-center">
                                            <svg class="-ml-1 mr-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path clip-rule="evenodd" fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                            </svg>
                                            <span id="selectedMonth">Month</span>
                                        </span>
                                    </button>
                                    <div id="monthDropdown"
                                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200 max-h-60 overflow-y-auto"
                                            aria-labelledby="monthDropdownButton">
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('All Months', null)">All Months</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('January', 1)">January</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('February', 2)">February</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('March', 3)">March</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('April', 4)">April</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('May', 5)">May</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('June', 6)">June</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('July', 7)">July</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('August', 8)">August</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('September', 9)">September</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('October', 10)">October</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('November', 11)">November</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('December', 12)">December</a></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="relative w-full sm:w-auto">
                                    <button id="yearDropdownButton" data-dropdown-toggle="yearDropdown"
                                        class="flex items-center cursor-pointer justify-between w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                                class="w-4 h-4 mr-2 text-gray-400" viewbox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span id="selectedYear">Year</span>
                                        </span>
                                        <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path clip-rule="evenodd" fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                        </svg>
                                    </button>
                                    <div id="yearDropdown"
                                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-32 dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200 max-h-60 overflow-y-auto"
                                            aria-labelledby="yearDropdownButton">
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear('All Years', null)">All Years</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2025, 2025)">2025</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2024, 2024)">2024</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2023, 2023)">2023</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2022, 2022)">2022</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2021, 2021)">2021</a></li>
                                            <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(2020, 2020)">2020</a></li>
                                        </ul>
                                    </div>
                                </div>

                                <button type="button" onclick="clearFilters()"
                                    class="w-full sm:w-auto px-4 py-2 text-sm cursor-pointer font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                    Clear Filters
                                </button>
                            </div> -->
                        </div>
                        <div class="overflow-x-auto">
                            <table id="reportTable"
                                class="w-full text-sm text-center text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600">
                                <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                    <tr class="flex w-full border-gray-300 dark:border-gray-600">
                                        <th scope="col" class="px-4 py-3 flex w-full text-sm text-left">Barangay</th>
                                        <th scope="col" class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Male
                                        </th>
                                        <th scope="col" class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Female</th>
                                        <th scope="col" class="px-4 py-3 flex-l w-full text-sm border-l border-gray-300 dark:border-gray-600">
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody" class="block max-h-80 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center">Loading data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>

    <script>
        // Global variables
        let currentData = null;
        let currentYear = null;
        let currentMonth = null;
        let selectedMonthName = 'All Months';
        let selectedYearValue = 'All Years';
        let availableYears = [];
        let availableMonths = [];

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Load available date ranges first, then load data
            loadDateRanges().then(() => {
                // Initialize dropdown texts
                document.getElementById('selectedMonth').textContent = selectedMonthName;
                document.getElementById('selectedYear').textContent = selectedYearValue;

                loadSeniorCounts(currentYear, currentMonth);
            });
        });

        // Fetch available date ranges from backend
        async function loadDateRanges() {
            try {
                const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/reports/report_backend.php?action=get_date_ranges');
                const result = await response.json();

                if (result.success) {
                    availableYears = result.years;
                    availableMonths = result.months;
                    populateYearDropdown();
                } else {
                    console.error('Failed to load date ranges:', result.message);
                    // Fallback to default years if API fails
                    populateYearDropdownWithDefaults();
                }
            } catch (error) {
                console.error('Error loading date ranges:', error);
                // Fallback to default years if API fails
                populateYearDropdownWithDefaults();
            }
        }

        // Populate year dropdown with available years from database
        function populateYearDropdown() {
            const yearDropdown = document.getElementById('yearDropdown');
            const ul = yearDropdown.querySelector('ul');
            ul.innerHTML = '';

            // Add "All Years" option
            const allYearsItem = document.createElement('li');
            allYearsItem.innerHTML = `<a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear('All Years', null)">All Years</a>`;
            ul.appendChild(allYearsItem);

            // Add available years
            if (availableYears.length > 0) {
                availableYears.sort((a, b) => b - a); // Sort descending
                availableYears.forEach(year => {
                    const yearItem = document.createElement('li');
                    yearItem.innerHTML = `<a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(${year}, ${year})">${year}</a>`;
                    ul.appendChild(yearItem);
                });
            } else {
                // Fallback if no years found
                populateYearDropdownWithDefaults();
            }
        }

        // Fallback function to populate year dropdown with default years
        function populateYearDropdownWithDefaults() {
            const yearDropdown = document.getElementById('yearDropdown');
            const ul = yearDropdown.querySelector('ul');
            ul.innerHTML = '';

            // Add "All Years" option
            const allYearsItem = document.createElement('li');
            allYearsItem.innerHTML = `<a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear('All Years', null)">All Years</a>`;
            ul.appendChild(allYearsItem);

            // Add default years (current year and previous 5 years)
            const currentYear = new Date().getFullYear();
            for (let year = currentYear; year >= currentYear - 5; year--) {
                const yearItem = document.createElement('li');
                yearItem.innerHTML = `<a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(${year}, ${year})">${year}</a>`;
                ul.appendChild(yearItem);
            }
        }

        // Fetch data from backend
        async function loadSeniorCounts(year = null, month = null) {
            try {
                showLoading();

                // Build URL with filters
                let url = '/MSWDPALUAN_SYSTEM-MAIN/php/reports/report_backend.php?action=get_senior_counts';
                if (year !== null) {
                    url += `&year=${year}`;
                }
                if (month !== null) {
                    url += `&month=${month}`;
                }

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    currentData = result;
                    displayData(result);
                    updateReportPeriod(result.filters);
                } else {
                    showError('Failed to load data: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load data. Please try again.');
            }
        }

        // Update report period display
        function updateReportPeriod(filters) {
            const periodElement = document.getElementById('reportPeriod');
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            let displayText = '';

            if (filters.month !== null && filters.year !== null) {
                const monthName = monthNames[filters.month - 1] || 'Unknown';
                displayText = `${monthName} ${filters.year}`;
                currentMonth = filters.month;
                currentYear = filters.year;
                selectedMonthName = monthName;
                selectedYearValue = filters.year;
            } else if (filters.year !== null) {
                displayText = `Year ${filters.year}`;
                currentYear = filters.year;
                selectedYearValue = filters.year;
                selectedMonthName = 'All Months';
            } else if (filters.month !== null) {
                const monthName = monthNames[filters.month - 1] || 'Unknown';
                displayText = `${monthName} (All Years)`;
                currentMonth = filters.month;
                selectedMonthName = monthName;
                selectedYearValue = 'All Years';
            } else {
                displayText = 'All Time';
                selectedMonthName = 'All Months';
                selectedYearValue = 'All Years';
            }

            // Update dropdown button texts
            document.getElementById('selectedMonth').textContent = selectedMonthName;
            document.getElementById('selectedYear').textContent = selectedYearValue;

            periodElement.textContent = displayText;
        }

        // Month selection handler
        function selectMonth(monthName, monthNumber) {
            document.getElementById('selectedMonth').textContent = monthName;
            selectedMonthName = monthName;
            currentMonth = monthNumber;
            loadSeniorCounts(currentYear, currentMonth);

            // Close dropdown
            const dropdown = document.getElementById('monthDropdown');
            dropdown.classList.add('hidden');
        }

        // Year selection handler
        function selectYear(yearName, yearNumber) {
            document.getElementById('selectedYear').textContent = yearName;
            selectedYearValue = yearName;
            currentYear = yearNumber;
            loadSeniorCounts(currentYear, currentMonth);

            // Close dropdown
            const dropdown = document.getElementById('yearDropdown');
            dropdown.classList.add('hidden');
        }

        // Display data in table
        function displayData(data) {
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '';

            data.data.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'flex w-full font-semibold text-gray-700';
                row.innerHTML = `
                <td class="px-4 py-3 flex w-full border-b-0 border-t-0 border-l-0 border-r-0 text-left border border-gray-300 dark:border-gray-600" style="font-family: 'Times New Roman', Times, serif;">
                    ${item.barangay}
                </td>
                <td class="px-4 py-3 flex-l w-full border-b-0 border-t-0 border-r-0 text-center border border-gray-300 dark:border-gray-600">
                    ${item.male}
                </td>
                <td class="px-4 py-3 flex-l w-full border-b-0 border-t-0 border-r-0 text-center border border-gray-300 dark:border-gray-600">
                    ${item.female}
                </td>
                <td class="px-4 py-3 flex-l w-full border-b-0 border-t-0 border-r-0 text-center border border-gray-300 dark:border-gray-600">
                    ${item.total}
                </td>
            `;
                tbody.appendChild(row);
            });

            // Add totals row
            const totalsRow = document.createElement('tr');
            totalsRow.className = 'flex w-full font-semibold text-gray-700 bg-gray-100 dark:bg-gray-800 border-gray-400 dark:border-gray-500';
            totalsRow.innerHTML = `
            <td class="flex w-full px-4 py-3 text-left border border-l-0 border-r-0 border-gray-300 dark:border-gray-600" style="font-family: 'Times New Roman', Times, serif;">
                Total
            </td>
            <td class="flex-l w-full px-4 py-3 border border-r-0 border-gray-300 dark:border-gray-600">
                ${data.totals.male}
            </td>
            <td class="flex-l w-full px-4 py-3 border border-r-0 border-gray-300 dark:border-gray-600">
                ${data.totals.female}
            </td>
            <td class="flex-l w-full px-4 py-3 border border-r-0 border-gray-300 dark:border-gray-600">
                ${data.totals.total}
            </td>
        `;
            tbody.appendChild(totalsRow);
        }

        // Utility functions
        function showLoading() {
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-3 text-center">Loading data...</td></tr>';
        }

        function showError(message) {
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-3 text-center text-red-500">${message}</td></tr>`;
        }

        // Clear all filters
        function clearFilters() {
            currentYear = null;
            currentMonth = null;
            selectedMonthName = 'All Months';
            selectedYearValue = 'All Years';

            document.getElementById('selectedMonth').textContent = selectedMonthName;
            document.getElementById('selectedYear').textContent = selectedYearValue;

            loadSeniorCounts();
        }

        // Generate report function
        function generateReport() {
            const periodText = document.getElementById('reportPeriod').textContent;
            alert(`Generating report for: ${periodText}\nThis feature would export the current data as PDF.`);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const monthDropdown = document.getElementById('monthDropdown');
            const yearDropdown = document.getElementById('yearDropdown');
            const monthButton = document.getElementById('monthDropdownButton');
            const yearButton = document.getElementById('yearDropdownButton');

            if (!monthButton.contains(event.target) && !monthDropdown.contains(event.target)) {
                monthDropdown.classList.add('hidden');
            }
            if (!yearButton.contains(event.target) && !yearDropdown.contains(event.target)) {
                yearDropdown.classList.add('hidden');
            }
        });
    </script>
    <script>
        function part1() {
            applyFilters();
        }

        function part2() {
            navigateWithFilters('reportpart2.php');
        }

        function part3() {
            navigateWithFilters('reportpart3.php');
        }

        function part4() {
            navigateWithFilters('reportpart4.php');
        }

        function part5() {
            navigateWithFilters('reportpart5.php');
        }

        function part6() {
            navigateWithFilters('reportpart6.php');
        }

        function part7to9() {
            navigateWithFilters('reportpart7to9.php');
        }

        function benefits() {
            navigateWithFilters('reportbenefits.php');
        }

        // The filter functions (selectMonth, selectYear, etc.) are now in date_filter_component.php
    </script>
</body>

</html>