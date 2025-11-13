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
                        <img src="../img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50"
                            alt="MSWD LOGO" />
                        <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD
                            PALUAN</span>
                    </a>
                    <form action="#" method="GET" class="hidden md:block md:pl-2">
                        <label for="topbar-search" class="sr-only">Search</label>
                        <div class="relative md:w-64">
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
                            <span class="block text-sm text-gray-900 truncate dark:text-white"></span>
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
                        <a href="./index.html"
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
                        <a href="./register.html"
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
                        <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                            <li>
                                <a href="./SeniorList/seniorlist.html"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Senior
                                    List</a>
                            </li>
                            <li>
                                <a href="./SeniorList/activelist.html"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="./SeniorList/inactivelist.html"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="./SeniorList/deceasedlist.html"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="./benefits.html"
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
                        <a href="./generate_id.html"
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
                    </li>
                    <li>
                        <a href="./reports/report.html"
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
                        <a href="./archived.html"
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
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-blue-700 group"
                            style="color: blue;">
                            <svg style="color: blue;" aria-hidden="true"
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

        <!-- Main content -->
        <main class="p-4 md:ml-64  pt-20">
            <div class="flex flex-row justify-between gap-2">
                <!-- partial:index.partial.html -->
                <div class="sidebar">
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
                            <button id="button" class="cursor-pointer">
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-900" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="links_name">My Profile</span>
                            </button>
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
                            <button type="button" class="cursor-pointer" id="button1">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span class="links_name">Accounts</span>
                            </button>
                            <span class="tooltip">Accounts</span>
                        </li>
                        <li>
                            <button class="cursor-pointer" id="button2">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3M3.22302 14C4.13247 18.008 7.71683 21 12 21c4.9706 0 9-4.0294 9-9 0-4.97056-4.0294-9-9-9-3.72916 0-6.92858 2.26806-8.29409 5.5M7 9H3V5" />
                                </svg>

                                <span class="links_name">History Log</span>
                            </button>
                            <span class="tooltip">History Log</span>
                        </li>
                        <li>
                            <button class="cursor-pointer" id="button3">
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
                            </button>
                            <span class="tooltip">Activity Log</span>
                        </li>
                    </ul>
                </div>
                <!-- profile  -->
                <section id="profileSection" class="bg-gray-50 hidden dark:bg-gray-900 w-full">
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
                                                        d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z" />
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
                <!-- accounts  -->
                <section id="accountSection" class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5 w-full hidden">
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
                                        class="flex items-center justify-center text-white bg-blue-700 hover:bg-blue-800 
                                                         font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700">
                                        <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0
                                                       11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                        </svg>
                                        Add Accounts
                                    </button>

                                    <!-- Filter Dropdown -->
                                    <div class="relative">
                                        <button onclick="toggleDropdown('filterDropdown')" class="flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 
                                                  bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 
                                                  dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
                                                  dark:hover:bg-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 
                                                   01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 
                                                    2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 
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
                                    </div>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <div class="overflow-x-auto">
                                    <table id="deceasedTable"
                                        class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                <!-- history log -->
                <section id="historySection" class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5 w-full hidden">
                    <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg ">
                            <div class=" py-4">
                                <!-- Close Button (top-right) -->
                                <div class="absolute top-2 right-2 group w-fit h-fit">
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-900 cursor-pointer inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white "
                                        data-dismiss-target="#toast-default" aria-label="Close2">
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
                            <div
                                class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                                <h4 class="text-xl font-medium dark:text-white">History log</h4>
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
                                <div class="relative w-full md:w-auto">
                                    <button id="filterDropdownButton"
                                        class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                            class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Filter
                                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7">
                                            </path>
                                        </svg>
                                    </button>

                                    <!-- Dropdown menu -->
                                    <div id="filterDropdownMenu"
                                        class="hidden absolute z-10 mt-2 w-44 bg-white rounded-lg shadow dark:bg-gray-800">
                                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button data-filter="az"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">A-Z</button>
                                            </li>
                                            <li>
                                                <button data-filter="az"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">60-100</button>
                                            </li>
                                            <li>
                                                <button data-filter="newlyRegistered"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">Newly
                                                    Registered</button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <div class="overflow-x-auto">
                                <table id="deceasedTable"
                                    class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">Type</th>
                                            <th scope="col" class="px-4 py-3">Name</th>
                                            <th scope="col" class="px-4 py-3">Duration</th>
                                            <th scope="col" class="px-4 py-3">Last Seen(?)</th>
                                            <th scope="col" class="px-15 py-3">Login</th>
                                            <th scope="col" class="px-15 py-3">Logout</th>
                                            <th scope="col" class="px-4 py-3">Log Status(?)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-4 py-3">Admin1</td>
                                            <td class="px-4 py-3">Tadalan,RussellJames</td>
                                            <td class="px-4 py-3">24 mins</td>
                                            <td class="px-4 py-3">1 mins ago</td>
                                            <td class="px-4 py-3">October 11,2025 7:20am</td>
                                            <td class="px-4 py-3">October 11,2025 7:22am</td>
                                            <td class="px-4 py-3">Logout</td>

                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- activity log  -->
                <section id="activitySection" class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5 w-full hidden">
                    <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg ">
                            <div class=" py-4">
                                <!-- Close Button (top-right) -->
                                <div class="absolute top-2 right-2 group w-fit h-fit">
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-900 cursor-pointer inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white "
                                        data-dismiss-target="#toast-default" aria-label="Close3">
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
                            <div
                                class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                                <h4 class="text-xl font-medium dark:text-white">Activity log</h4>
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
                                <div class="relative w-full md:w-auto">
                                    <button id="activityFilterDropdownButton"
                                        class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                                        type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                            class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Filter
                                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7">
                                            </path>
                                        </svg>
                                    </button>

                                    <!-- Dropdown menu -->
                                    <div id="activityFilterDropdownMenu"
                                        class="hidden absolute z-10 mt-2 w-44 bg-white rounded-lg shadow dark:bg-gray-800">
                                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button data-filter="az"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">A-Z</button>
                                            </li>
                                            <li>
                                                <button data-filter="az"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">60-100</button>
                                            </li>
                                            <li>
                                                <button data-filter="newlyRegistered"
                                                    class="block w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">Newly
                                                    Registered</button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <div class="overflow-x-auto">
                                <table id="deceasedTable"
                                    class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">#</th>
                                            <th scope="col" class="px-4 py-3">Type</th>
                                            <th scope="col" class="px-25 py-3">Name</th>
                                            <th scope="col" class="px-10 py-3">Date and Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-4 py-3">1</td>
                                            <td class="px-4 py-3">Admin1</td>
                                            <td class="px-15 py-3">Tadalan,RussellJames</td>
                                            <td class="px-4 py-3">October 11,2025 7:20am</td>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
                                    Add Accounts
                                </h3>
                                <button type="button"
                                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                    data-modal-toggle="defaultModal">
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
                                        <label for="brand"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sex</label>
                                        <div
                                            class="flex flex-row justify-between items-center gap-5 bg-gray-50 border p-2.5 px-10 border-gray-300 text-gray-900 rounded-lg">
                                            <div class="flex items-center ">
                                                <input id="default-radio-1" type="radio" value="2" name="default-radio"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="default-radio-1"
                                                    class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Male</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input checked id="default-radio-2" type="radio" value="2"
                                                    name="default-radio"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="default-radio-2"
                                                    class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">female</label>
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
                                        <label for="price"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact
                                            no.</label>
                                        <input type="number" name="price" id="price"
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
                                        <label for="select-type"
                                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select
                                            Type</label>
                                        <select id="select-type"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option selected></option>
                                            <option value="Admin 1">Admin 1</option>
                                            <option value="Admin 2">Admin 2</option>
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
        const table = document.getElementById("deceasedTable");
        const dropdownButton = document.getElementById("filterDropdownButton");
        const dropdownMenu = document.getElementById("filterDropdownMenu");

        // Toggle dropdown visibility
        dropdownButton.addEventListener("click", () => {
            dropdownMenu.classList.toggle("hidden");
        });

        // Filter selection
        dropdownMenu.querySelectorAll("button").forEach(btn => {
            btn.addEventListener("click", () => {
                const filterType = btn.getAttribute("data-filter");
                const rows = Array.from(table.querySelectorAll("tbody tr"));

                if (filterType === "az") {
                    rows.sort((a, b) => {
                        const nameA = a.cells[1].textContent.toLowerCase();
                        const nameB = b.cells[1].textContent.toLowerCase();
                        return nameA.localeCompare(nameB);
                    });
                    dropdownButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                </svg>
                Filter: A-Z
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            `;
                } else if (filterType === "newlyRegistered") {
                    rows.sort((a, b) => {
                        const dateA = new Date(a.cells[6].textContent);
                        const dateB = new Date(b.cells[6].textContent);
                        return dateB - dateA; // newest first
                    });
                    dropdownButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                </svg>
                Filter: Newly Registered
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            `;
                }

                const tbody = table.querySelector("tbody");
                rows.forEach(row => tbody.appendChild(row));
                updateNumbers();

                dropdownMenu.classList.add("hidden"); // close menu after selection
            });
        });

        function updateNumbers() {
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach((row, index) => row.cells[0].textContent = index + 1);
        }

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add("hidden");
            }
        });
    </script>
</body>

</html>