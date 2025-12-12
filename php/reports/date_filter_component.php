<?php
// date_filter_component.php - Complete solution without backend API

// Get current filter values from URL
$currentMonth = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
$currentYear = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;

// Month names for display
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

// Get selected display values
$selectedMonthName = 'All Months';
$selectedYearValue = 'All Years';

if ($currentMonth && isset($monthNames[$currentMonth])) {
    $selectedMonthName = $monthNames[$currentMonth];
}

if ($currentYear) {
    $selectedYearValue = $currentYear;
}

// Get current page name and session context
$currentPage = basename($_SERVER['PHP_SELF']);
$ctx = isset($_GET['session_context']) ? $_GET['session_context'] : '';
?>
<main class="p-4 ">
    <!-- Date Filter Component -->
    <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
        <!-- Month Filter -->
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
                    <span id="selectedMonth"><?php echo htmlspecialchars($selectedMonthName); ?></span>
                </span>
            </button>
            <div id="monthDropdown"
                class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200 max-h-60 overflow-y-auto"
                    aria-labelledby="monthDropdownButton">
                    <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('All Months', null)">All Months</a></li>
                    <?php foreach ($monthNames as $num => $name): ?>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectMonth('<?php echo $name; ?>', <?php echo $num; ?>)"><?php echo $name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Year Filter -->
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
                    <span id="selectedYear"><?php echo htmlspecialchars($selectedYearValue); ?></span>
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
                    <?php
                    // Generate last 10 years
                    $currentYearNum = date('Y');
                    for ($year = $currentYearNum; $year >= $currentYearNum - 10; $year--):
                    ?>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="selectYear(<?php echo $year; ?>, <?php echo $year; ?>)"><?php echo $year; ?></a></li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>

        <!-- Clear Filters Button -->
        <button type="button" onclick="clearFilters()"
            class="w-full sm:w-auto px-4 py-2 text-sm cursor-pointer font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
            Clear Filters
        </button>
    </div>
</main>


<script>
    // Global variables for date filtering
    let currentYear = <?php echo json_encode($currentYear); ?>;
    let currentMonth = <?php echo json_encode($currentMonth); ?>;
    let currentPage = '<?php echo $currentPage; ?>';
    let ctx = '<?php echo $ctx; ?>';

    // Month selection handler
    function selectMonth(monthName, monthNumber) {
        document.getElementById('selectedMonth').textContent = monthName;
        currentMonth = monthNumber;
        applyFilters();

        // Close dropdown
        const dropdown = document.getElementById('monthDropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }

    // Year selection handler
    function selectYear(yearName, yearNumber) {
        document.getElementById('selectedYear').textContent = yearName;
        currentYear = yearNumber;
        applyFilters();

        // Close dropdown
        const dropdown = document.getElementById('yearDropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }

    // Apply filters by reloading page with query parameters
    function applyFilters() {
        let url = currentPage;
        let params = [];

        if (ctx) {
            params.push('session_context=' + encodeURIComponent(ctx));
        }

        if (currentYear) {
            params.push('year=' + currentYear);
        }

        if (currentMonth) {
            params.push('month=' + currentMonth);
        }

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        window.location.href = url;
    }

    // Clear all filters
    function clearFilters() {
        let url = currentPage;

        if (ctx) {
            url += '?session_context=' + encodeURIComponent(ctx);
        }

        window.location.href = url;
    }

    // Helper function for navigation between report parts
    function navigateWithFilters(page) {
        let url = page;
        let params = [];

        if (ctx) {
            params.push('session_context=' + encodeURIComponent(ctx));
        }

        if (currentYear) {
            params.push('year=' + currentYear);
        }

        if (currentMonth) {
            params.push('month=' + currentMonth);
        }

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        window.location.href = url;
    }

    // Close dropdowns when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(event) {
            const monthDropdown = document.getElementById('monthDropdown');
            const yearDropdown = document.getElementById('yearDropdown');
            const monthButton = document.getElementById('monthDropdownButton');
            const yearButton = document.getElementById('yearDropdownButton');

            if (monthButton && !monthButton.contains(event.target) && monthDropdown && !monthDropdown.contains(event.target)) {
                monthDropdown.classList.add('hidden');
            }
            if (yearButton && !yearButton.contains(event.target) && yearDropdown && !yearDropdown.contains(event.target)) {
                yearDropdown.classList.add('hidden');
            }
        });
    });
</script>