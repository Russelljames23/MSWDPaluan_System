<?php
require_once "../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database configuration
$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
// Get statistics using the existing database connection
$stats = [];

// Total seniors
$query = "SELECT COUNT(*) as total FROM applicants";
$result = mysqli_query($conn, $query);
$stats['total'] = mysqli_fetch_assoc($result)['total'];

// Gender distribution
$query = "SELECT gender, COUNT(*) as count FROM applicants GROUP BY gender";
$result = mysqli_query($conn, $query);
$stats['gender'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['gender'][$row['gender']] = $row['count'];
}

// Calculate percentages for gender
$male_count = $stats['gender']['Male'] ?? 0;
$female_count = $stats['gender']['Female'] ?? 0;
$total_gender = $male_count + $female_count;

if ($total_gender > 0) {
    $male_percentage = round(($male_count / $total_gender) * 100, 1);
    $female_percentage = round(($female_count / $total_gender) * 100, 1);
} else {
    $male_percentage = 0;
    $female_percentage = 0;
}

// Status distribution
$query = "SELECT status, COUNT(*) as count FROM applicants GROUP BY status";
$result = mysqli_query($conn, $query);
$stats['status'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['status'][$row['status']] = $row['count'];
}

// Calculate percentages for status
$active_count = $stats['status']['Active'] ?? 0;
$inactive_count = $stats['status']['Inactive'] ?? 0;
$deceased_count = $stats['status']['Deceased'] ?? 0;

if ($stats['total'] > 0) {
    $active_percentage = round(($active_count / $stats['total']) * 100, 1);
    $inactive_percentage = round(($inactive_count / $stats['total']) * 100, 1);
    $deceased_percentage = round(($deceased_count / $stats['total']) * 100, 1);
} else {
    $active_percentage = 0;
    $inactive_percentage = 0;
    $deceased_percentage = 0;
}

// Validation status
$query = "SELECT validation, COUNT(*) as count FROM applicants GROUP BY validation";
$result = mysqli_query($conn, $query);
$stats['validation'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['validation'][$row['validation']] = $row['count'];
}

// Age distribution (grouped with specific ranges)
$query = "SELECT 
            CASE 
                WHEN current_age BETWEEN 60 AND 64 THEN '60-64'
                WHEN current_age BETWEEN 65 AND 69 THEN '65-69'
                WHEN current_age BETWEEN 70 AND 74 THEN '70-74'
                WHEN current_age BETWEEN 75 AND 79 THEN '75-79'
                WHEN current_age BETWEEN 80 AND 84 THEN '80-84'
                WHEN current_age BETWEEN 85 AND 89 THEN '85-89'
                WHEN current_age >= 90 THEN '90+'
                ELSE 'Under 60'
            END as age_group,
            COUNT(*) as count 
          FROM applicants 
          WHERE current_age IS NOT NULL
          GROUP BY age_group
          ORDER BY 
            CASE age_group
                WHEN '60-64' THEN 1
                WHEN '65-69' THEN 2
                WHEN '70-74' THEN 3
                WHEN '75-79' THEN 4
                WHEN '80-84' THEN 5
                WHEN '85-89' THEN 6
                WHEN '90+' THEN 7
                ELSE 8
            END";
$result = mysqli_query($conn, $query);
$stats['age_groups'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['age_groups'][$row['age_group']] = $row['count'];
}

// Barangay distribution (ALL barangays)
$query = "SELECT a.barangay, COUNT(*) as count 
          FROM addresses a 
          JOIN applicants ap ON a.applicant_id = ap.applicant_id
          WHERE a.barangay IS NOT NULL AND a.barangay != ''
          GROUP BY a.barangay 
          ORDER BY 
            CASE a.barangay
                WHEN 'I - Mapalad' THEN 1
                WHEN 'II - Handang Tumulong' THEN 2
                WHEN 'III - Silahis ng Pag-asa' THEN 3
                WHEN 'IV - Pag-asa ng Bayan' THEN 4
                WHEN 'V - Bagong Silang' THEN 5
                WHEN 'VI - San Jose' THEN 6
                WHEN 'VII - Lumang Bayan' THEN 7
                WHEN 'VIII - Marikit' THEN 8
                WHEN 'IX - Tubili' THEN 9
                WHEN 'X - Alipaoy' THEN 10
                WHEN 'XI - Harison' THEN 11
                WHEN 'XII - Mananao' THEN 12
                ELSE 13
            END";
$result = mysqli_query($conn, $query);
$stats['barangays'] = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stats['barangays'][$row['barangay']] = $row['count'];
}

// Ensure all 12 barangays are included, even with 0 count
$all_barangays = [
    'I - Mapalad' => 0,
    'II - Handang Tumulong' => 0,
    'III - Silahis ng Pag-asa' => 0,
    'IV - Pag-asa ng Bayan' => 0,
    'V - Bagong Silang' => 0,
    'VI - San Jose' => 0,
    'VII - Lumang Bayan' => 0,
    'VIII - Marikit' => 0,
    'IX - Tubili' => 0,
    'X - Alipaoy' => 0,
    'XI - Harison' => 0,
    'XII - Mananao' => 0
];

// Merge with actual data
foreach ($all_barangays as $barangay => $count) {
    if (isset($stats['barangays'][$barangay])) {
        $all_barangays[$barangay] = $stats['barangays'][$barangay];
    }
}

$stats['barangays'] = $all_barangays;

// Recent registrations (last 30 days)
$query = "SELECT COUNT(*) as count FROM applicants WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result = mysqli_query($conn, $query);
$stats['recent_registrations'] = mysqli_fetch_assoc($result)['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MSWD Paluan</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {
            'packages': ['corechart', 'bar']
        });
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // Status Distribution Pie Chart
            var statusData = google.visualization.arrayToDataTable([
                ['Status', 'Count'],
                ['Active', <?php echo $stats['status']['Active'] ?? 0; ?>],
                ['Inactive', <?php echo $stats['status']['Inactive'] ?? 0; ?>],
                ['Deceased', <?php echo $stats['status']['Deceased'] ?? 0; ?>]
            ]);

            var statusOptions = {
                // title: 'Status Distribution',
                pieHole: 0.4,
                colors: ['#34A853', '#FBBC05', '#EA4335'],
                backgroundColor: 'transparent',
                chartArea: {
                    width: '90%',
                    height: '80%'
                }
            };

            var statusChart = new google.visualization.PieChart(document.getElementById('status-chart'));
            statusChart.draw(statusData, statusOptions);

            // Validation Status Pie Chart
            var validationData = google.visualization.arrayToDataTable([
                ['Validation Status', 'Count'],
                ['Validated', <?php echo $stats['validation']['Validated'] ?? 0; ?>],
                ['For Validation', <?php echo $stats['validation']['For Validation'] ?? 0; ?>]
            ]);

            var validationOptions = {
                // title: 'Validation Status',
                pieHole: 0.4,
                colors: ['#34A853', '#EA4335'],
                backgroundColor: 'transparent',
                chartArea: {
                    width: '90%',
                    height: '80%'
                }
            };

            var validationChart = new google.visualization.PieChart(document.getElementById('validation-chart'));
            validationChart.draw(validationData, validationOptions);

            // Age Distribution 3D Pie Chart
            var ageData = new google.visualization.DataTable();
            ageData.addColumn('string', 'Age Group');
            ageData.addColumn('number', 'Count');

            <?php
            // Get age distribution with specific groups
            $query = "SELECT 
            CASE 
                WHEN current_age BETWEEN 60 AND 64 THEN '60-64'
                WHEN current_age BETWEEN 65 AND 69 THEN '65-69'
                WHEN current_age BETWEEN 70 AND 74 THEN '70-74'
                WHEN current_age BETWEEN 75 AND 79 THEN '75-79'
                WHEN current_age BETWEEN 80 AND 84 THEN '80-84'
                WHEN current_age BETWEEN 85 AND 89 THEN '85-89'
                WHEN current_age >= 90 THEN '90+'
                ELSE 'Under 60'
            END as age_group,
            COUNT(*) as count 
          FROM applicants 
          WHERE current_age IS NOT NULL
          GROUP BY age_group
          ORDER BY 
            CASE age_group
                WHEN '60-64' THEN 1
                WHEN '65-69' THEN 2
                WHEN '70-74' THEN 3
                WHEN '75-79' THEN 4
                WHEN '80-84' THEN 5
                WHEN '85-89' THEN 6
                WHEN '90+' THEN 7
                ELSE 8
            END";

            $result = mysqli_query($conn, $query);
            $age_groups = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $age_groups[$row['age_group']] = $row['count'];
            }

            // Define all age groups to ensure they appear in the chart even if count is 0
            $all_age_groups = [
                '60-64' => 0,
                '65-69' => 0,
                '70-74' => 0,
                '75-79' => 0,
                '80-84' => 0,
                '85-89' => 0,
                '90+' => 0
            ];

            // Merge with actual data
            foreach ($all_age_groups as $group => $value) {
                if (isset($age_groups[$group])) {
                    $all_age_groups[$group] = $age_groups[$group];
                }
            }

            // Remove groups with 0 count if you prefer, or keep them to show all categories
            $filtered_age_groups = array_filter($all_age_groups, function ($count) {
                return $count > 0;
            });

            if (!empty($filtered_age_groups)) {
                echo "ageData.addRows([\n";
                foreach ($filtered_age_groups as $group => $count) {
                    echo "['$group', $count],\n";
                }
                echo "]);";
            } else {
                echo "ageData.addRows([['No Data', 1]]);";
            }
            ?>

            var ageOptions = {
                // title: 'Age Distribution',
                is3D: true,
                backgroundColor: 'transparent',
                chartArea: {
                    width: '90%',
                    height: '80%'
                },
                colors: ['#4285F4', '#34A853', '#FBBC05', '#EA4335', '#FF6D01', '#46BDC6', '#8E44AD'],
                legend: {
                    position: 'labeled',
                    textStyle: {
                        fontSize: 12
                    }
                },
                pieSliceText: 'value',
                tooltip: {
                    showColorCode: true
                },
                slices: {
                    0: {
                        offset: 0.1
                    }, // Slight emphasis on first slice
                    6: {
                        offset: 0.1
                    } // Slight emphasis on last slice
                }
            };

            var ageChart = new google.visualization.PieChart(document.getElementById('age-chart'));
            ageChart.draw(ageData, ageOptions);

            // Barangay Distribution Bar Chart - Fixed Labels
            var barangayData = new google.visualization.DataTable();
            barangayData.addColumn('string', 'Barangay');
            barangayData.addColumn('number', 'Count');
            barangayData.addColumn({
                type: 'string',
                role: 'style'
            });

            <?php
            $barangayColors = ['#34A853', '#4285F4', '#FBBC05', '#EA4335', '#8E44AD', '#FF6D01', '#46BDC6', '#E91E63', '#795548', '#607D8B', '#9C27B0', '#009688'];

            if (!empty($stats['barangays'])) {
                echo "barangayData.addRows([\n";
                $i = 0;
                foreach ($stats['barangays'] as $barangay => $count) {
                    // Use abbreviated names for better visibility
                    $formattedBarangay = str_replace(
                        ['I - ', 'II - ', 'III - ', 'IV - ', 'V - ', 'VI - ', 'VII - ', 'VIII - ', 'IX - ', 'X - ', 'XI - ', 'XII - '],
                        ['I\n', 'II\n', 'III\n', 'IV\n', 'V\n', 'VI\n', 'VII\n', 'VIII\n', 'IX\n', 'X\n', 'XI\n', 'XII\n'],
                        $barangay
                    );
                    $color = $barangayColors[$i];
                    echo "['$formattedBarangay', $count, 'fill-color: $color; stroke-color: $color; fill-opacity: 0.8; stroke-width: 2'],\n";
                    $i++;
                }
                echo "]);";
            } else {
                echo "barangayData.addRows([['No Data', 0, 'fill-color: #CCCCCC']]);";
            }
            ?>

            var barangayOptions = {
                // title: 'Barangay Distribution',
                titleTextStyle: {
                    fontSize: 16,
                    bold: true
                },
                backgroundColor: 'transparent',
                chartArea: {
                    left: 100, // More space for y-axis labels
                    top: 80, // More space for title
                    width: '85%',
                    height: '65%',
                    backgroundColor: 'transparent'
                },
                hAxis: {
                    title: '',
                    textStyle: {
                        fontSize: 11,
                        color: '#4B5563',
                        bold: true
                    },
                    slantedText: true,
                    slantedTextAngle: 45,
                    showTextEvery: 1,
                    textPosition: 'out'
                },
                vAxis: {
                    title: 'Number of Seniors',
                    titleTextStyle: {
                        color: '#4B5563',
                        fontSize: 12,
                        bold: true
                    },
                    minValue: 0,
                    textStyle: {
                        color: '#4B5563',
                        fontSize: 11
                    },
                    gridlines: {
                        color: '#E5E7EB',
                        count: 6
                    },
                    minorGridlines: {
                        count: 0
                    }
                },
                legend: {
                    position: 'none'
                },
                bar: {
                    groupWidth: '60%'
                },
                tooltip: {
                    textStyle: {
                        fontSize: 12
                    }
                }
            };

            var barangayChart = new google.visualization.ColumnChart(document.getElementById('barangay-chart'));
            barangayChart.draw(barangayData, barangayOptions);
        }

        // Redraw charts on window resize
        window.addEventListener('resize', function() {
            drawCharts();
        });
    </script>
</head>

<body>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Navigation (same as original) -->
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

        <!-- Sidebar (same as original) -->
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
                        <a href="#" style="color: blue;"
                            class="flex items-center p-2 text-base font-medium text-blue-700 bg-blue-100 rounded-lg dark:text-blue  dark:hover:bg-blue-700 group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400  dark:group-hover:text-white">
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
                        <a href="register.php?session_context=<?php echo $ctx; ?>"

                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-gray-700 group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">
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
                            class="flex items-center p-2 cursor-pointer w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
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

        <!-- Main Content -->
        <main class="p-4 md:ml-64 h-auto pt-20">
            <!-- Overview Section -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Overview</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <!-- Total Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Seniors</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['total']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Male Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Male</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['gender']['Male'] ?? 0; ?></p>
                                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mt-1"><?php echo $male_percentage; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Female Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-pink-100 dark:bg-pink-900">
                                <svg class="w-6 h-6 text-pink-600 dark:text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14s7 0 9-4a8.986 8.986 0 01-9 4 8.986 8.986 0 01-9-4c2 4 9 4 9 4z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Female</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['gender']['Female'] ?? 0; ?></p>
                                    <p class="text-sm text-pink-600 dark:text-pink-400 font-medium mt-1"><?php echo $female_percentage; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Registrations Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Recent (30 days)</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['recent_registrations']; ?></p>
                                    <?php if ($stats['total'] > 0): ?>
                                        <?php $recent_percentage = round(($stats['recent_registrations'] / $stats['total']) * 100, 1); ?>
                                        <p class="text-sm text-green-600 dark:text-green-400 font-medium mt-1"><?php echo $recent_percentage; ?>% of total</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row for Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <!-- Active Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Seniors</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $active_count; ?></p>
                                    <p class="text-sm text-green-600 dark:text-green-400 font-medium mt-1"><?php echo $active_percentage; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Inactive Seniors</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $inactive_count; ?></p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400 font-medium mt-1"><?php echo $inactive_percentage; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deceased Seniors Card -->
                    <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Deceased Seniors</p>
                                <div class="flex flex-row gap-5">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $deceased_count; ?></p>
                                    <p class="text-sm text-red-600 dark:text-red-400 font-medium mt-1"><?php echo $deceased_percentage; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Age Distribution Chart -->
                <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Age Distribution</h3>
                    <div id="age-chart" style="height: 300px;"></div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status Distribution</h3>
                    <div id="status-chart" style="height: 300px;"></div>
                </div>

                <!-- Validation Status Chart -->
                <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Validation Status</h3>
                    <div id="validation-chart" style="height: 300px;"></div>
                </div>

                <!-- Barangay Distribution -->
                <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Barangays</h3>
                    <div id="barangay-chart" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Additional Stats Section -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="register.php?session_context=<?php echo $ctx; ?>" class="flex items-center p-3 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Register New Senior
                        </a>
                        <a href="./SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>" class="flex items-center p-3 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            View All Seniors
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>

</body>

</html>