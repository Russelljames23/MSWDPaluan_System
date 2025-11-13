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
    <div class="antialiased bg-[#FAF9F6] dark:bg-gray-900">
        <!-- Navbar -->
        <nav class="bg-[#FAF9F6] border-b border-[#E2E0D5] px-4 py-2.5 fixed left-0 right-0 top-0 z-50 text-[#222222]">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex justify-start items-center">
                    <!-- Sidebar Toggle -->
                    <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                        aria-controls="drawer-navigation"
                        class="p-2 mr-2 text-[#444] rounded-lg cursor-pointer md:hidden hover:text-[#000] hover:bg-[#FFF4E5] focus:bg-[#FFF4E5] focus:ring-2 focus:ring-[#E68A00]">
                        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Toggle sidebar</span>
                    </button>

                    <!-- Logo -->
                    <a href="#" class="flex items-center justify-between mr-4">
                        <img src="../img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-[#E2E0D5] rounded-full py-1.5 px-1 bg-[#FFF]"
                            alt="MSWD LOGO" />
                        <span class="self-center text-2xl font-bold whitespace-nowrap text-[#222222]">MSWD PALUAN</span>
                    </a>

                    <!-- Search Bar -->
                    <form action="#" method="GET" class="hidden md:block md:pl-2">
                        <label for="topbar-search" class="sr-only">Search</label>
                        <div class="relative md:w-96">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-[#666]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                                    </path>
                                </svg>
                            </div>
                            <input type="text" id="topbar-search"
                                class="bg-[#FFF] border border-[#D9D6C3] text-[#222] text-base rounded-lg focus:ring-[#005A70] focus:border-[#005A70] block w-full pl-10 p-2.5 placeholder-[#888]"
                                placeholder="Search" />
                        </div>
                    </form>
                </div>

                <!-- User Profile -->
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 cursor-pointer text-sm bg-[#005A70] rounded-full md:mr-0 focus:ring-4 focus:ring-[#E68A00]"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-8 h-8 rounded-full"
                            src="https://spng.pngfind.com/pngs/s/378-3780189_member-icon-png-transparent-png.png"
                            alt="user photo" />
                    </button>

                    <!-- Dropdown -->
                    <div class="hidden z-50 my-4 w-56 text-base list-none bg-[#FFF] divide-y divide-[#E2E0D5] shadow rounded-xl"
                        id="dropdown">
                        <div class="py-3 px-4">
                            <span class="block text-sm font-semibold text-[#222]">Neil Sims</span>
                            <span class="block text-sm text-[#555] truncate">name@flowbite.com</span>
                        </div>
                        <ul class="py-1 text-[#333]" aria-labelledby="dropdown">
                            <li><a href="#" class="block py-2 px-4 text-sm hover:bg-[#FFF4E5] hover:text-[#005A70]">My
                                    profile</a></li>
                            <li><a href="#" class="block py-2 px-4 text-sm hover:bg-[#FFF4E5] hover:text-[#E68A00]">Sign
                                    out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-[#FAF9F6] border-r border-[#E2E0D5] md:translate-x-0"
            aria-label="Sidenav" id="drawer-navigation">
            <div class="overflow-y-auto py-5 px-3 h-full text-[#222222]">
                <p class="text-lg font-semibold mb-5">User Panel</p>
                <ul class="space-y-2">
                    <li><a href="#"
                            class="flex items-center p-2 text-lg font-medium text-[#005A70] bg-[#FFF4E5] rounded-lg">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="8" height="10" rx="1.5" />
                                <rect x="13" y="3" width="8" height="6" rx="1.5" />
                                <rect x="3" y="15" width="8" height="6" rx="1.5" />
                                <rect x="13" y="11" width="8" height="10" rx="1.5" />
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li><a href="./register.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                                    clip-rule="evenodd" />
                            </svg>Register
                        </a></li>
                    <li>
                        <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                            class="flex items-center cursor-pointer p-2 w-full text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                    d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5" />
                            </svg>
                            <span class="flex-1  text-left whitespace-nowrap">Master List</span>
                            <svg aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages" aria-hidden="true"
                                class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                            <li><a href="./SeniorList/seniorlist.php"
                                    class="flex items-center p-2 pl-11 font-medium text-base hover:bg-[#FFF4E5]">Senior
                                    List</a>
                            </li>
                            <li><a href="./SeniorList/activelist.php"
                                    class="flex items-center p-2 pl-11 font-medium text-base hover:bg-[#FFF4E5]">Active
                                    List</a>
                            </li>
                            <li><a href="./SeniorList/inactivelist.php"
                                    class="flex items-center p-2 pl-11 font-medium text-base hover:bg-[#FFF4E5]">Inactive
                                    List</a>
                            </li>
                            <li><a href="./SeniorList/deceasedlist.php"
                                    class="flex items-center p-2 pl-11 font-medium text-base hover:bg-[#FFF4E5]">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li><a href="./benefits.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="flex-shrink-0 w-6 h-6 text-[#005A70] mr-2 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M8 7V2.221a2 2 0 0 0-.5.365L3.586 6.5a2 2 0 0 0-.365.5H8Zm2 0V2h7a2 2 0 0 1 2 2v.126a5.087 5.087 0 0 0-4.74 1.368v.001l-6.642 6.642a3 3 0 0 0-.82 1.532l-.74 3.692a3 3 0 0 0 3.53 3.53l3.694-.738a3 3 0 0 0 1.532-.82L19 15.149V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                                    clip-rule="evenodd" />
                                <path fill-rule="evenodd"
                                    d="M17.447 8.08a1.087 1.087 0 0 1 1.187.238l.002.001a1.088 1.088 0 0 1 0 1.539l-.377.377-1.54-1.542.373-.374.002-.001c.1-.102.22-.182.353-.237Zm-2.143 2.027-4.644 4.644-.385 1.924 1.925-.385 4.644-4.642-1.54-1.54Zm2.56-4.11a3.087 3.087 0 0 0-2.187.909l-6.645 6.645a1 1 0 0 0-.274.51l-.739 3.693a1 1 0 0 0 1.177 1.176l3.693-.738a1 1 0 0 0 .51-.274l6.65-6.646a3.088 3.088 0 0 0-2.185-5.275Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Benefits
                        </a></li>
                    <li><a href="./generate_id.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="flex-shrink-0 w-6 h-6 text-[#005A70] mr-2 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Generate ID
                        </a></li>
                    <li><a href="./reports/report.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3" />
                            </svg>Report
                        </a></li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-[#E2E0D5]">
                    <li><a href="./archived.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 1 0 0 4h16a2 2 0 1 0 0-4H4Zm0 6h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8Zm10.707 5.707a1 1 0 0 0-1.414-1.414l-.293.293V12a1 1 0 1 0-2 0v2.586l-.293-.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l2-2Z"
                                    clip-rule="evenodd" />
                            </svg>Archived
                        </a></li>
                    <li><a href="./settings.php"
                            class="flex items-center p-2 text-lg font-medium text-[#222] rounded-lg hover:bg-[#FFF4E5] transition">
                            <svg class="w-6 h-6 text-[#005A70] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                    clip-rule="evenodd"></path>
                            </svg>Settings
                        </a></li>
                </ul>
            </div>
        </aside>

        <main class="p-4 md:ml-64 h-auto pt-20">
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
</body>

</html>