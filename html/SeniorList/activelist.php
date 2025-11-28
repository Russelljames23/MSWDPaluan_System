<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active List</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
                        <img src="../../img/MSWD_LOGO-removebg-preview.png"
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
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">Neil Sims</span>
                            <span class="block text-sm text-gray-900 truncate dark:text-white">name@flowbite.com</span>
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
                                <a href="#"
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
                                <a href="#" style="color: blue;"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-blue-700 bg-blue-100 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="./inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="./deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Deceased
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
                <div class="mx-auto  max-w-screen-5xl ">
                    <div class="bg-white  dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                        <div
                            class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                            <h4 class="text-xl font-medium dark:text-white">Active List</h4>
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
                            <div class="flex flex-row gap-5 ">
                                <!-- Update Pension Status Button (Initially Hidden) -->
                                <div id="openModalbtn" class="flex flex-row gap-2 hidden">
                                    <button id="bulkPensionBtn"
                                        class="px-3  py-2 cursor-pointer text-xs font-medium text-white bg-blue-600 rounded-sm hover:bg-ble-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                        Update Pension Status
                                    </button>
                                </div>

                                <!-- Status Filter -->
                                <div class="relative w-full md:w-auto ">
                                    <select id="statusFilter"
                                        class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg md:w-auto focus:outline-none hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 cursor-pointer appearance-none pr-8">
                                        <option value="all">All Status</option>
                                        <option value="For Validation">For Validation</option>
                                        <option value="Validated">Validated</option>
                                    </select>
                                </div>
                                <!-- Filter  -->
                                <div class="relative w-full md:w-auto">
                                    <!--  Filter Button -->
                                    <button id="filterDropdownButton" data-dropdown-toggle="filterDropdown"
                                        class="flex items-center cursor-pointer justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg md:w-auto focus:outline-none hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
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

                                    <!--  Dynamic Dropdown -->
                                    <div id="filterDropdown"
                                        class="z-10 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                                        <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                            Barangay
                                        </h6>
                                        <ul id="barangayList" class="space-y-2 text-sm"
                                            aria-labelledby="dropdownDefault">
                                            <li class="text-gray-400 text-sm text-center">Loading...</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="">
                            <table id="deceasedTable" class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
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
                                <tbody id="seniorBody">
                                    <tr class="border-b text-xs font-medium text-center border-gray-200"></tr>
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

            <!-- Multi-Applicant Pension Modal -->
            <div id="multiPensionModal"
                class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-600/50 bg-opacity-50">
                <div id="multiPensionBox"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-2xl p-4 relative scale-95 opacity-0 transition-all">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Update Pension Status</h2>

                    <p class="mb-2 text-gray-700 dark:text-gray-200">Select applicants to update:</p>
                    <div id="multiApplicantList"
                        class="max-h-60 overflow-y-auto mb-4 border rounded p-2 dark:border-gray-600">
                        <!-- Applicants checkboxes and control number inputs will be added dynamically -->
                    </div>

                    <label class="block mb-2 text-gray-700 dark:text-gray-200">Select new Pension Status:</label>
                    <select id="multiPensionSelect"
                        class="w-full mb-4 p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">--Select Status--</option>
                        <option value="Validated">Validated</option>
                        <option value="For Validation">For Validation</option>
                        <option value="Denied">Denied</option>
                    </select>

                    <div class="flex justify-end space-x-2">
                        <button id="cancelMultiPension"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded hover:bg-gray-400 dark:hover:bg-gray-500">Cancel</button>
                        <button id="confirmMultiPension"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Selected</button>
                    </div>
                </div>
            </div>

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
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <!-- Senior List Table  -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Initialize global variables
            window.globalSelectedApplicants = new Map();
            window.globalPensionStatus = new Map();
            window.countdownIntervals = new Map(); // Store countdown intervals

            // Load recently updated seniors from localStorage
            window.recentlyUpdatedSeniors = new Map();
            initializeRecentlyUpdatedSeniors();

            const tableBody = document.getElementById("seniorBody");
            const barangayList = document.getElementById("barangayList");
            const searchInput = document.getElementById("simple-search");
            const paginationNav = document.getElementById("paginationNav");
            const updateBtnContainer = document.getElementById("openModalbtn");
            const statusFilter = document.getElementById("statusFilter");

            let currentPage = 1;
            let totalPages = 1;
            let totalRecords = 0;
            let lastSearch = "";
            let selectedBarangays = [];
            let selectedStatus = "all";

            // ---------------- COUNTDOWN MANAGEMENT ----------------
            function startCountdown(seniorId, displayElement) {
                // Clear any existing countdown for this senior
                stopCountdown(seniorId);

                function updateCountdown() {
                    const timeRemaining = getCorrectionTimeRemaining(seniorId);

                    if (timeRemaining <= 0) {
                        // Countdown finished
                        stopCountdown(seniorId);
                        displayElement.innerHTML = 'Validated'; // Remove countdown text
                        return;
                    }

                    // Update the display with live countdown
                    displayElement.innerHTML = `Validated <span class="text-xs text-blue-500">(Can correct for ${timeRemaining}s)</span>`;
                }

                // Update immediately
                updateCountdown();

                // Set up interval to update every second
                const interval = setInterval(updateCountdown, 1000);
                window.countdownIntervals.set(seniorId, interval);
            }

            function stopCountdown(seniorId) {
                if (window.countdownIntervals.has(seniorId)) {
                    clearInterval(window.countdownIntervals.get(seniorId));
                    window.countdownIntervals.delete(seniorId);
                }
            }

            function stopAllCountdowns() {
                window.countdownIntervals.forEach((interval, seniorId) => {
                    clearInterval(interval);
                });
                window.countdownIntervals.clear();
            }

            // ---------------- PERSISTENT 1-MINUTE CORRECTION WINDOW ----------------
            function initializeRecentlyUpdatedSeniors() {
                const stored = localStorage.getItem('recentlyUpdatedSeniors');
                if (stored) {
                    try {
                        const parsed = JSON.parse(stored);
                        const now = Date.now();

                        // Only keep entries that are still within the 1-minute correction window
                        for (const [seniorId, updateTime] of parsed) {
                            if (now - updateTime < 60000) { // 1 minute in milliseconds
                                window.recentlyUpdatedSeniors.set(seniorId, updateTime);
                            }
                        }
                        saveRecentlyUpdatedSeniors(); // Save cleaned up version
                    } catch (e) {
                        console.error("Error loading recently updated seniors:", e);
                        localStorage.removeItem('recentlyUpdatedSeniors');
                    }
                }
            }

            function saveRecentlyUpdatedSeniors() {
                localStorage.setItem('recentlyUpdatedSeniors', JSON.stringify([...window.recentlyUpdatedSeniors]));
            }

            function markSeniorAsRecentlyUpdated(seniorId) {
                const updateTime = Date.now();
                window.recentlyUpdatedSeniors.set(seniorId, updateTime);
                saveRecentlyUpdatedSeniors();

                // Set timeout to remove from recently updated after 1 minute (correction window ends)
                setTimeout(() => {
                    window.recentlyUpdatedSeniors.delete(seniorId);
                    saveRecentlyUpdatedSeniors();

                    // Update the UI if the page is visible
                    if (document.visibilityState === 'visible') {
                        const row = document.querySelector(`.multiSelectCheckbox[value='${seniorId}']`)?.closest("tr");
                        if (row) {
                            const statusCell = row.querySelector("td:nth-child(11)");
                            if (statusCell) {
                                // Remove the correction window indicator
                                statusCell.innerHTML = 'Validated';
                            }
                        }
                    }
                }, 60000); // 1 minute
            }

            function isSeniorInCorrectionWindow(seniorId) {
                if (!window.recentlyUpdatedSeniors.has(seniorId)) return false;

                const updateTime = window.recentlyUpdatedSeniors.get(seniorId);
                const elapsed = Date.now() - updateTime;
                return elapsed < 60000; // 1 minute in milliseconds
            }

            function getCorrectionTimeRemaining(seniorId) {
                if (!window.recentlyUpdatedSeniors.has(seniorId)) return 0;

                const updateTime = window.recentlyUpdatedSeniors.get(seniorId);
                const elapsed = Date.now() - updateTime;
                const remaining = Math.max(0, 60000 - elapsed); // 1 minute in milliseconds
                return Math.ceil(remaining / 1000); // Return seconds
            }

            function canUpdateSeniorStatus(seniorId, currentStatus) {
                // Allow status update if:
                // 1. Senior status is "For Validation" (always allowed)
                // 2. OR Senior status is "Validated" AND within 1-minute correction window
                if (currentStatus === "For Validation") {
                    return true;
                }
                if (currentStatus === "Validated") {
                    return isSeniorInCorrectionWindow(seniorId);
                }
                return true; // For other statuses like "Denied"
            }

            // ---------------- POPUP MODAL ----------------
            window.showPopup = function(message, type = "info", redirect = false) {
                const modal = document.getElementById("popupModal");
                const box = document.getElementById("popupBox");
                const title = document.getElementById("popupTitle");
                const msg = document.getElementById("popupMessage");
                const closeBtn = document.getElementById("popupCloseBtn");

                if (!modal || !box) {
                    console.error("Popup modal elements not found!");
                    return;
                }

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
                        if (redirect) window.location.href = redirect;
                    }, 200);
                };
            };

            // ---------------- UPDATE BUTTON VISIBILITY ----------------
            function updateBulkActionVisibility() {
                // Show update button if any seniors are selected
                if (window.globalSelectedApplicants.size > 0) {
                    updateBtnContainer.classList.remove('hidden');
                } else {
                    updateBtnContainer.classList.add('hidden');
                }
            }

            // ---------------- FETCH BARANGAYS ----------------
            function fetchBarangays() {
                // Use relative path instead of absolute path
                fetch("/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?mode=barangays")
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(barangays => {
                        barangayList.innerHTML = "";
                        if (barangays && barangays.length > 0) {
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
                                    fetchSeniors();
                                });
                            });
                        } else {
                            barangayList.innerHTML = `<li class='text-gray-500 text-center'>No barangays found</li>`;
                        }
                    })
                    .catch(err => {
                        console.error("Error loading barangays:", err);
                        barangayList.innerHTML = `<li class='text-red-500 text-center'>Error loading barangays</li>`;
                        showPopup("Failed to load barangays.", "error");
                    });
            }

            // ---------------- FETCH SENIORS ----------------
            const fetchSeniors = () => {
                // Stop all existing countdowns before fetching new data
                stopAllCountdowns();

                const params = new URLSearchParams({
                    page: currentPage,
                    search: lastSearch,
                    barangays: selectedBarangays.join(','),
                    status: selectedStatus
                });

                // Use relative path instead of absolute path
                fetch(`/MSWDPALUAN_SYSTEM-MAIN/php/seniorlist/fetch_seniors.php?${params}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        tableBody.innerHTML = "";

                        if (!data || data.error) {
                            throw new Error(data?.error || "Invalid response from server");
                        }

                        totalRecords = data.total_records || 0;
                        totalPages = data.total_pages || 1;

                        if (!data.seniors || data.seniors.length === 0) {
                            tableBody.innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No senior records found.
                        </td>
                    </tr>`;
                            paginationNav.innerHTML = "";
                            return;
                        }

                        data.seniors.forEach((senior, index) => {
                            if (!senior.applicant_id) return; // Skip invalid entries

                            window.globalPensionStatus.set(String(senior.applicant_id), senior.validation);
                            const statusColor =
                                senior.validation === "Validated" ? "text-green-600" :
                                senior.validation === "For Validation" ? "text-red-600" :
                                "text-red-600";

                            const createdAt = senior.date_created ? new Date(senior.date_created).toLocaleDateString() : "";
                            const modifiedAt = senior.date_modified ? new Date(senior.date_modified).toLocaleDateString() : "";

                            const buttonId = `dropdownBtn-${index}`;
                            const dropdownId = `dropdownMenu-${index}`;

                            const tr = document.createElement("tr");
                            tr.className = "border-b text-xs font-medium text-center border-gray-200";

                            // Create status cell with unique ID for countdown
                            const statusCellId = `status-cell-${senior.applicant_id}`;

                            tr.innerHTML = `
                            <td class="px-4 py-3">
                                <input type="checkbox" class="multiSelectCheckbox border-gray-600" 
                                    value="${senior.applicant_id}" data-name="${senior.full_name || 'Unknown'}" 
                                    data-status="${senior.validation || 'Unknown'}">
                            </td>
                            <td class="px-4 py-3">${senior.rownum || index + 1}</td>
                            <td>${senior.full_name || ""}</td>
                            <td>${senior.birth_date || ""}</td>
                            <td>${senior.age || ""}</td>
                            <td>${senior.gender || ""}</td>
                            <td>${senior.civil_status || ""}</td>
                            <td>${senior.barangay || ""}</td>
                            <td>${createdAt}</td>
                            <td>${modifiedAt}</td>
                            <td id="${statusCellId}" class="${statusColor}">${senior.validation}</td>
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
                                            <button onclick="markInactive('${senior.applicant_id}')"
                                            class="block py-2 cursor-pointer px-4 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                                ⚪ Send to Inactive
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="markDeceased('${senior.applicant_id}', '${senior.full_name || 'Unknown'}')"
                                                    class="w-full cursor-pointer text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-400 dark:hover:text-white">
                                                ☠ Send to Deceased
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>`;

                            tableBody.appendChild(tr);

                            // Start countdown if senior is validated and in correction window
                            if (senior.validation === "Validated" && isSeniorInCorrectionWindow(senior.applicant_id)) {
                                const statusCell = document.getElementById(statusCellId);
                                if (statusCell) {
                                    startCountdown(senior.applicant_id, statusCell);
                                }
                            }

                            const checkbox = tr.querySelector(".multiSelectCheckbox");

                            // Always allow selection (checkboxes are never disabled)
                            checkbox.checked = globalSelectedApplicants.has(senior.applicant_id);

                            checkbox.addEventListener("change", () => {
                                const seniorId = checkbox.value;
                                const seniorName = checkbox.dataset.name?.trim() || "Unknown";
                                const seniorStatus = checkbox.dataset.status || "";

                                if (checkbox.checked) {
                                    globalSelectedApplicants.set(seniorId, {
                                        name: seniorName,
                                        status: seniorStatus,
                                        canUpdate: canUpdateSeniorStatus(seniorId, seniorStatus)
                                    });
                                } else {
                                    globalSelectedApplicants.delete(seniorId);
                                }

                                // Update select all checkbox state and bulk action visibility
                                updateSelectAllCheckbox();
                                updateBulkActionVisibility();
                            });

                            const button = document.getElementById(buttonId);
                            const menu = document.getElementById(dropdownId);

                            if (button && menu) {
                                button.addEventListener("click", (e) => {
                                    e.stopPropagation();
                                    document.querySelectorAll("[id^='dropdownMenu-']").forEach(m => {
                                        if (m !== menu) m.classList.add("hidden");
                                    });
                                    menu.classList.toggle("hidden");
                                });
                            }
                        });

                        document.addEventListener("click", () => {
                            document.querySelectorAll("[id^='dropdownMenu-']").forEach(m => m.classList.add("hidden"));
                        });

                        renderPagination(data.start || 1, data.end || data.seniors.length);
                    })
                    .catch(err => {
                        console.error("Error fetching seniors:", err);
                        tableBody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-red-500">Error loading data: ${err.message}</td></tr>`;
                        showPopup("Failed to fetch senior records: " + err.message, "error");
                    });
            };

            // ---------------- PAGINATION ----------------
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

                // Previous Button with Tooltip
                html += `
                <li>
                    <div class="relative group inline-flex items-center justify-center">
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
                        <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 
                            text-xs text-black text-center font-medium w-[95px] dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                            Previous page
                        </span>
                    </div>
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

                // Next Button with Tooltip
                html += `
                <li>
                    <div class="relative group inline-flex items-center justify-center">
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
                        <span class="absolute bottom-full mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 
                            text-xs text-black text-center font-medium w-[74px] dark:bg-gray-700 px-2 py-1 rounded shadow-lg">
                            Next page
                        </span>
                    </div>
                </li>
            </ul>`;

                paginationNav.innerHTML = html;

                // Event listeners
                paginationNav.querySelectorAll("[data-page]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        currentPage = parseInt(btn.dataset.page);
                        fetchSeniors();
                    });
                });

                paginationNav.querySelectorAll("[data-nav]").forEach(btn => {
                    btn.addEventListener("click", () => {
                        if (btn.dataset.nav === "prev" && currentPage > 1) currentPage--;
                        else if (btn.dataset.nav === "next" && currentPage < totalPages) currentPage++;
                        fetchSeniors();
                    });
                });
            };

            // ---------------- SEARCH ----------------
            let searchTimeout;
            searchInput.addEventListener("input", (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    lastSearch = e.target.value.trim();
                    currentPage = 1;
                    fetchSeniors();
                }, 400);
            });

            // ---------------- STATUS FILTER - ADD THIS NEW SECTION ----------------
            if (statusFilter) {
                statusFilter.addEventListener("change", (e) => {
                    selectedStatus = e.target.value;
                    currentPage = 1;
                    fetchSeniors();
                });
            }

            // ---------------- MULTI-APPLICANT PENSION MODAL ----------------
            const bulkPensionBtn = document.getElementById("bulkPensionBtn");
            const multiModal = document.getElementById("multiPensionModal");
            const multiBox = document.getElementById("multiPensionBox");
            const multiList = document.getElementById("multiApplicantList");
            const multiSelect = document.getElementById("multiPensionSelect");
            const cancelMulti = document.getElementById("cancelMultiPension");
            const confirmMulti = document.getElementById("confirmMultiPension");

            // Function to update modal countdowns
            function updateModalCountdowns() {
                const checkboxes = multiList.querySelectorAll('.multiApplicantCheckbox');
                checkboxes.forEach(cb => {
                    const seniorId = cb.value;
                    const span = cb.nextElementSibling;

                    if (span && isSeniorInCorrectionWindow(seniorId)) {
                        const timeRemaining = getCorrectionTimeRemaining(seniorId);
                        const currentText = span.textContent.split('(Can correct for')[0]; // Remove existing countdown
                        span.innerHTML = `${currentText.trim()} (Can correct for ${timeRemaining}s)`;
                    }
                });
            }

            if (bulkPensionBtn) {
                bulkPensionBtn.addEventListener("click", () => {
                    if (globalSelectedApplicants.size === 0) {
                        showPopup("Please select at least one senior.", "error");
                        return;
                    }

                    multiList.innerHTML = "";

                    let hasEligibleSeniors = false;
                    let hasValidatedSeniors = false;

                    globalSelectedApplicants.forEach((data, id) => {
                        const name = data.name;
                        const currentStatus = data.status;
                        const canUpdate = canUpdateSeniorStatus(id, currentStatus);

                        if (canUpdate) {
                            hasEligibleSeniors = true;
                        }

                        if (currentStatus === "Validated") {
                            hasValidatedSeniors = true;
                        }

                        // Create checkbox with unique ID for countdown updates
                        const checkboxId = `modal-checkbox-${id}`;
                        multiList.insertAdjacentHTML("beforeend", `
                            <div class="mb-2 p-2 border rounded dark:border-gray-600 applicant-item" data-applicant-id="${id}">
                                <label class="flex items-center space-x-2 mb-1" id="label-${id}">
                                    <input type="checkbox" 
                                        class="multiApplicantCheckbox" 
                                        value="${id}" 
                                        ${canUpdate ? "checked" : "disabled"}>
                                    <span class="${!canUpdate ? 'text-gray-400 line-through' : ''}">
                                        ${name} (${currentStatus}) 
                                        ${currentStatus === "Validated" && !canUpdate ? '(Cannot update - Validated)' : ''}
                                        ${currentStatus === "Validated" && canUpdate ? `(Can correct for ${getCorrectionTimeRemaining(id)}s)` : ''}
                                    </span>
                                </label>
                                <div class="control-number-input mt-1 ml-6 ${multiSelect.value === 'Validated' ? '' : 'hidden'}">
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                        Control Number:
                                    </label>
                                    <input type="text" 
                                        class="control-number w-full p-1 text-xs border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Enter control number"
                                        ${!canUpdate ? 'disabled' : ''}>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Enter control number (any characters allowed)
                                    </p>
                                </div>
                            </div>
                            `);
                    });

                    // Show modal if there are eligible seniors OR if there are only Validated seniors (to show they can't be updated)
                    if (hasEligibleSeniors || hasValidatedSeniors) {
                        multiSelect.disabled = false;
                        multiSelect.classList.remove("opacity-60", "cursor-not-allowed");

                        multiModal.classList.remove("hidden");
                        setTimeout(() => {
                            multiBox.classList.remove("scale-95", "opacity-0");
                            multiBox.classList.add("scale-100", "opacity-100");
                        }, 10);

                        // Show/hide control number inputs based on selected status
                        updateControlNumberVisibility();

                        // Start modal countdown updates for validated seniors
                        const modalCountdownInterval = setInterval(updateModalCountdowns, 1000);

                        // Store interval so we can clear it when modal closes
                        multiModal.dataset.countdownInterval = modalCountdownInterval;
                    } else {
                        showPopup("No eligible seniors selected for update.", "info");
                    }
                });
            }

            // Update control number input visibility based on selected status
            function updateControlNumberVisibility() {
                const controlNumberInputs = multiList.querySelectorAll('.control-number-input');
                const isValidation = multiSelect.value === 'Validated';

                controlNumberInputs.forEach(container => {
                    if (isValidation) {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                    }
                });
            }

            // Add event listener for status change
            if (multiSelect) {
                multiSelect.addEventListener('change', updateControlNumberVisibility);
            }

            if (cancelMulti) {
                cancelMulti.addEventListener("click", () => {
                    // Clear modal countdown interval
                    if (multiModal.dataset.countdownInterval) {
                        clearInterval(parseInt(multiModal.dataset.countdownInterval));
                        delete multiModal.dataset.countdownInterval;
                    }
                    multiModal.classList.add("hidden");
                });
            }

            if (confirmMulti) {
                confirmMulti.addEventListener("click", async () => {
                    // Clear modal countdown interval
                    if (multiModal.dataset.countdownInterval) {
                        clearInterval(parseInt(multiModal.dataset.countdownInterval));
                        delete multiModal.dataset.countdownInterval;
                    }

                    // Get currently checked applicants in the modal (only those that can be updated)
                    const selected = Array.from(document.querySelectorAll(".multiApplicantCheckbox:checked"))
                        .map(cb => {
                            const seniorId = cb.value;
                            const data = globalSelectedApplicants.get(seniorId);
                            return {
                                id: seniorId,
                                status: data?.status
                            };
                        })
                        .filter(({
                            id,
                            status
                        }) => canUpdateSeniorStatus(id, status))
                        .map(({
                            id
                        }) => id);

                    // Remove any unchecked applicants from global selection
                    Array.from(document.querySelectorAll(".multiApplicantCheckbox")).forEach(cb => {
                        if (!cb.checked) globalSelectedApplicants.delete(cb.value);
                    });

                    const status = multiSelect.value;

                    if (!status) return showPopup("Please select a pension status.", "error");
                    if (selected.length === 0) return showPopup("No eligible applicants selected for update.", "error");

                    // Collect control numbers if status is "Validated"
                    let controlNumbers = [];
                    if (status === "Validated") {
                        controlNumbers = selected.map(id => {
                            const container = document.querySelector(`.applicant-item[data-applicant-id="${id}"]`);
                            const input = container?.querySelector('.control-number');
                            return input ? input.value.trim() : '';
                        });

                        // Validate control numbers - only check if they are provided
                        const emptyControlNumbers = controlNumbers.filter(cn => !cn);
                        if (emptyControlNumbers.length > 0) {
                            return showPopup("Please enter control numbers for all selected applicants.", "error");
                        }

                        // Check for duplicates
                        const uniqueControlNumbers = new Set(controlNumbers);
                        if (uniqueControlNumbers.size !== controlNumbers.length) {
                            return showPopup("Duplicate control numbers are not allowed.", "error");
                        }
                    }

                    try {
                        // Prepare request data
                        const requestData = {
                            applicant_id: selected,
                            validation: status
                        };

                        // Add control numbers only for validation
                        if (status === "Validated") {
                            requestData.control_numbers = controlNumbers;
                        }

                        // Use relative path for the update request
                        const res = await fetch("/MSWDPALUAN_SYSTEM-MAIN/php/activelist/update_status.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(requestData)
                        });

                        const result = await res.json();
                        if (result.error) return showPopup(result.error, "error");

                        // Mark all updated seniors as recently updated (only if they become Validated)
                        if (status === "Validated") {
                            selected.forEach(id => {
                                markSeniorAsRecentlyUpdated(id);
                                // Start countdown for the table row
                                const statusCell = document.getElementById(`status-cell-${id}`);
                                if (statusCell) {
                                    startCountdown(id, statusCell);
                                }
                            });
                        }

                        showPopup(result.message + (status === "Validated" ? " You can make corrections within 1 minute if needed." : ""), "success");
                        multiModal.classList.add("hidden");

                        // Update table rows without refetching everything
                        selected.forEach(id => {
                            const rowCheckbox = document.querySelector(`.multiSelectCheckbox[value="${id}"]`);
                            if (rowCheckbox) {
                                const row = rowCheckbox.closest("tr");
                                const statusCell = row.querySelector("td:nth-child(11)");
                                if (status === "Validated") {
                                    // Countdown will be handled by startCountdown function
                                    statusCell.className = "text-green-600";
                                } else {
                                    statusCell.innerHTML = status;
                                    statusCell.className = status === "For Validation" ? "text-red-600" : "text-red-600";
                                }
                            }
                        });

                        // Clear selection and hide update button
                        window.globalSelectedApplicants.clear();
                        updateBulkActionVisibility();
                        updateSelectAllCheckbox();

                    } catch (err) {
                        showPopup("Error: " + err.message, "error");
                    }
                });
            }

            // ---------------- SELECT ALL FUNCTIONALITY ----------------
            const selectAllCheckbox = document.getElementById("selectAllCheckbox");

            // Function to update the state of the "Select All" checkbox
            function updateSelectAllCheckbox() {
                const checkboxes = tableBody.querySelectorAll(".multiSelectCheckbox");
                const checkedCheckboxes = tableBody.querySelectorAll(".multiSelectCheckbox:checked");

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

            // Select All checkbox functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener("change", function() {
                    const checkboxes = tableBody.querySelectorAll(".multiSelectCheckbox");

                    checkboxes.forEach(checkbox => {
                        const isChecked = this.checked;
                        checkbox.checked = isChecked;

                        // Update global selection
                        const seniorId = checkbox.value;
                        const seniorName = checkbox.dataset.name?.trim() || "Unknown";
                        const seniorStatus = checkbox.dataset.status || "";

                        if (isChecked) {
                            globalSelectedApplicants.set(seniorId, {
                                name: seniorName,
                                status: seniorStatus,
                                canUpdate: canUpdateSeniorStatus(seniorId, seniorStatus)
                            });
                        } else {
                            globalSelectedApplicants.delete(seniorId);
                        }
                    });

                    this.indeterminate = false;
                    updateBulkActionVisibility();
                });
            }

            // ---------------- CLEANUP ON PAGE UNLOAD ----------------
            window.addEventListener('beforeunload', () => {
                stopAllCountdowns();
            });

            // ---------------- INITIAL LOAD ----------------
            fetchBarangays();
            fetchSeniors();
        });

        // ---------------- SEND TO INACTIVE FUNCTIONALITY ----------------
        window.markInactive = async (id) => {
            const applicantId = id;
            console.log('🔄 markInactive called with ID:', applicantId);

            // Remove any existing modal first
            const existingModal = document.getElementById('inactiveModal');
            if (existingModal) {
                document.body.removeChild(existingModal);
            }

            // Get the senior's name from the table for the modal
            const row = document.querySelector(`tr:has(input[value="${applicantId}"])`);
            const fullName = row ? row.querySelector('td:nth-child(3)').textContent.trim() : 'Unknown';

            // Create and show modal directly
            showInactiveModal(applicantId, fullName);
        };

        function showInactiveModal(applicantId, fullName) {
            const inactiveModal = document.createElement('div');
            inactiveModal.id = 'inactiveModal';
            inactiveModal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 bg-opacity-50';
            inactiveModal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 scale-95 opacity-0 transition-all duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Send to Inactive</h2>
                            <button type="button" id="closeInactiveModal" class="text-gray-400 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Mark <span class="font-semibold">${fullName}</span> as inactive?
                        </p>
                        
                        <form id="inactiveForm" class="space-y-4">
                            <input type="hidden" name="applicant_id" value="${applicantId}">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Date of Inactivity *
                                </label>
                                <input type="date" name="date_of_inactive" required
                                    class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-yellow-500"
                                    value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Reason for Inactivity *
                                </label>
                                <input type="text" name="reason" required
                                    class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-yellow-500"
                                    placeholder="Enter reason for inactivity"
                                    maxlength="255">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Please provide a clear reason for marking this senior as inactive
                                </p>
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" id="cancelInactive"
                                    class="px-3 py-1 text-sm cursor-pointer font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors duration-200 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" id="submitInactive"
                                    class="px-3 py-1 text-sm cursor-pointer font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg transition-colors duration-200">
                                    Mark as Inactive
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            document.body.appendChild(inactiveModal);
            document.body.style.overflow = 'hidden';

            // Show modal with animation
            setTimeout(() => {
                const modalDiv = inactiveModal.querySelector('div');
                modalDiv.classList.remove('scale-95', 'opacity-0');
                modalDiv.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Initialize modal functionality
            initializeInactiveModal(inactiveModal, applicantId);
        }

        function initializeInactiveModal(modal, applicantId) {
            const form = modal.querySelector('#inactiveForm');
            const submitBtn = modal.querySelector('#submitInactive');
            const cancelBtn = modal.querySelector('#cancelInactive');
            const closeBtn = modal.querySelector('#closeInactiveModal');

            // Form submission handler
            const handleSubmit = async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                const data = {
                    applicant_id: formData.get('applicant_id'),
                    date_of_inactive: formData.get('date_of_inactive'),
                    reason: formData.get('reason').trim()
                };

                // Validate
                if (!data.reason) {
                    // Use alert as fallback if showPopup is not available
                    if (typeof showPopup === 'function') {
                        showPopup('Please enter a reason for inactivity.', 'error');
                    } else {
                        alert('Please enter a reason for inactivity.');
                    }
                    return;
                }

                if (!data.date_of_inactive) {
                    // Use alert as fallback if showPopup is not available
                    if (typeof showPopup === 'function') {
                        showPopup('Please select a date of inactivity.', 'error');
                    } else {
                        alert('Please select a date of inactivity.');
                    }
                    return;
                }

                try {
                    console.log('📤 Sending mark inactive request:', data);

                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/activelist/mark_inactive.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📥 Response received:', result);

                    if (!response.ok || result.error) {
                        throw new Error(result.error || 'Failed to mark as inactive');
                    }

                    // SUCCESS - Close modal and show popup
                    closeModal();

                    // Use alert as fallback if showPopup is not available
                    if (typeof showPopup === 'function') {
                        showPopup('Senior successfully marked as inactive!', 'success');
                    } else {
                        alert('Senior successfully marked as inactive!');
                    }

                    // Remove row from table immediately with animation
                    removeRowFromTable(applicantId);

                    // Refresh table data after a short delay
                    setTimeout(() => {
                        if (typeof window.fetchSeniors === 'function') {
                            window.fetchSeniors();
                        }
                    }, 1000);

                } catch (error) {
                    console.error('❌ Error:', error);
                    // Use alert as fallback if showPopup is not available
                    if (typeof showPopup === 'function') {
                        showPopup('Error: ' + error.message, 'error');
                    } else {
                        alert('Error: ' + error.message);
                    }
                }
            };

            // Cancel/close handlers
            const handleCancel = () => {
                closeModal();
            };

            const handleClose = () => {
                closeModal();
            };

            // Close modal when clicking outside
            const handleOutsideClick = (e) => {
                if (e.target === modal) closeModal();
            };

            // Close modal with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') closeModal();
            };

            // Add event listeners
            form.addEventListener('submit', handleSubmit);
            cancelBtn.addEventListener('click', handleCancel);
            closeBtn.addEventListener('click', handleClose);
            modal.addEventListener('click', handleOutsideClick);
            document.addEventListener('keydown', handleEscape);

            // Focus on reason input
            setTimeout(() => {
                const reasonInput = modal.querySelector('input[name="reason"]');
                if (reasonInput) reasonInput.focus();
            }, 100);

            function closeModal() {
                const modalDiv = modal.querySelector('div');
                modalDiv.classList.add('scale-95', 'opacity-0');

                // Clean up
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                    document.body.style.overflow = ''; // Restore scrolling

                    // Remove event listeners
                    document.removeEventListener('keydown', handleEscape);
                }, 200);
            }

            function removeRowFromTable(applicantId) {
                const row = document.querySelector(`tr:has(input[value="${applicantId}"])`);
                if (row) {
                    row.style.transition = 'all 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-100%)';
                    row.style.maxHeight = '0';
                    row.style.overflow = 'hidden';

                    setTimeout(() => {
                        if (row.parentNode) {
                            row.parentNode.removeChild(row);
                            updateRowNumbers();
                        }
                    }, 300);
                }
            }

            function updateRowNumbers() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const numberCell = row.querySelector('td:nth-child(2)');
                    if (numberCell) {
                        numberCell.textContent = index + 1;
                    }
                });
            }
        }

        // ---------------- SEND TO DECEASED FUNCTIONALITY ----------------
        window.markDeceased = (id, fullName) => {
            const applicantId = id;

            // Create modal for deceased form
            const deceasedModal = document.createElement('div');
            deceasedModal.id = 'deceasedModal';
            deceasedModal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 bg-opacity-50';
            deceasedModal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 scale-95 opacity-0 transition-all duration-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mark as Deceased</h2>
                    <button type="button" id="closeDeceasedModal" class="text-gray-400 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Mark <span class="font-semibold">${fullName}</span> as deceased?
                </p>
                
                <form id="deceasedForm" class="space-y-4">
                    <input type="hidden" name="applicant_id" value="${applicantId}">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date of Death *
                        </label>
                        <input type="date" name="date_of_death" required
                            class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-red-500"
                            value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" id="cancelDeceased"
                            class="px-3 py-1 text-sm cursor-pointer font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors duration-200 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        <button type="submit" id="submitDeceased"
                            class="px-3 py-1 text-sm cursor-pointer font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-200">
                            Confirm Deceased
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

            document.body.appendChild(deceasedModal);
            document.body.style.overflow = 'hidden';

            // Show modal with animation
            setTimeout(() => {
                const modalDiv = deceasedModal.querySelector('div');
                modalDiv.classList.remove('scale-95', 'opacity-0');
                modalDiv.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Initialize modal functionality
            initializeDeceasedModal(deceasedModal, applicantId, fullName);
        };

        function initializeDeceasedModal(modal, applicantId, fullName) {
            const form = modal.querySelector('#deceasedForm');
            const submitBtn = modal.querySelector('#submitDeceased');
            const cancelBtn = modal.querySelector('#cancelDeceased');
            const closeBtn = modal.querySelector('#closeDeceasedModal');

            // Form submission handler
            const handleSubmit = async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                const data = {
                    applicant_id: formData.get('applicant_id'),
                    date_of_death: formData.get('date_of_death')
                };

                // Validate
                if (!data.date_of_death) {
                    if (typeof showPopup === 'function') {
                        showPopup('Please select a date of death.', 'error');
                    } else {
                        alert('Please select a date of death.');
                    }
                    return;
                }

                try {
                    console.log('📤 Sending mark deceased request:', data);

                    const response = await fetch('/MSWDPALUAN_SYSTEM-MAIN/php/activelist/mark_deceased.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📥 Response received:', result);

                    if (!response.ok || result.error) {
                        throw new Error(result.error || 'Failed to mark as deceased');
                    }

                    // SUCCESS - Close modal and show popup
                    closeModal();

                    if (typeof showPopup === 'function') {
                        showPopup('Senior successfully marked as deceased!', 'success');
                    } else {
                        alert('Senior successfully marked as deceased!');
                    }

                    // Remove row from table immediately with animation
                    removeRowFromTable(applicantId);

                    // Refresh table data after a short delay
                    setTimeout(() => {
                        if (typeof window.fetchSeniors === 'function') {
                            window.fetchSeniors();
                        }
                    }, 1000);

                } catch (error) {
                    console.error('❌ Error:', error);
                    if (typeof showPopup === 'function') {
                        showPopup('Error: ' + error.message, 'error');
                    } else {
                        alert('Error: ' + error.message);
                    }

                    // Re-enable submit button on error
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirm Deceased';
                }
            };

            // Cancel/close handlers
            const handleCancel = () => {
                closeModal();
            };

            const handleClose = () => {
                closeModal();
            };

            // Close modal when clicking outside
            const handleOutsideClick = (e) => {
                if (e.target === modal) closeModal();
            };

            // Close modal with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') closeModal();
            };

            // Add event listeners
            form.addEventListener('submit', handleSubmit);
            cancelBtn.addEventListener('click', handleCancel);
            closeBtn.addEventListener('click', handleClose);
            modal.addEventListener('click', handleOutsideClick);
            document.addEventListener('keydown', handleEscape);

            // Focus on date input
            setTimeout(() => {
                const dateInput = modal.querySelector('input[name="date_of_death"]');
                if (dateInput) dateInput.focus();
            }, 100);

            function closeModal() {
                const modalDiv = modal.querySelector('div');
                modalDiv.classList.add('scale-95', 'opacity-0');

                // Clean up
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                    document.body.style.overflow = ''; // Restore scrolling

                    // Remove event listeners
                    document.removeEventListener('keydown', handleEscape);
                }, 200);
            }

            function removeRowFromTable(applicantId) {
                const row = document.querySelector(`tr:has(input[value="${applicantId}"])`);
                if (row) {
                    row.style.transition = 'all 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-100%)';
                    row.style.maxHeight = '0';
                    row.style.overflow = 'hidden';

                    setTimeout(() => {
                        if (row.parentNode) {
                            row.parentNode.removeChild(row);
                            updateRowNumbers();
                        }
                    }, 300);
                }
            }

            function updateRowNumbers() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const numberCell = row.querySelector('td:nth-child(2)');
                    if (numberCell) {
                        numberCell.textContent = index + 1;
                    }
                });
            }
        }
    </script>
</body>

</html>