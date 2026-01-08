<?php
require_once "../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database configuration
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";
$pdo = null;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Fetch current user data - ADD THIS
$user_id = $_SESSION['user_id'] ?? 0;
$user_data = [];

if ($user_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        $user_data = [];
    }
}

// Prepare full name - ADD THIS
$full_name = '';
if (!empty($user_data['firstname']) && !empty($user_data['lastname'])) {
    $full_name = $user_data['firstname'] . ' ' . $user_data['lastname'];
    if (!empty($user_data['middlename'])) {
        $full_name = $user_data['firstname'] . ' ' . $user_data['middlename'] . ' ' . $user_data['lastname'];
    }
}

// Get profile photo URL - ADD THIS
$profile_photo_url = '';
if (!empty($user_data['profile_photo'])) {
    $profile_photo_url = '../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar if no profile photo - ADD THIS
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}
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

// Get monthly registration trend
$query = "SELECT 
            DATE_FORMAT(date_created, '%Y-%m') as month,
            COUNT(*) as count 
          FROM applicants 
          WHERE date_created >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(date_created, '%Y-%m')
          ORDER BY month DESC
          LIMIT 6";
$result = mysqli_query($conn, $query);
$monthly_trend = [];
while ($row = mysqli_fetch_assoc($result)) {
    $monthly_trend[$row['month']] = $row['count'];
}

// Get top 5 barangays for quick view
arsort($all_barangays);
$top_barangays = array_slice($all_barangays, 0, 5, true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MSWD Paluan</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations and styles */
        .stat-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .progress-ring {
            transition: stroke-dashoffset 1s ease;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Dark mode improvements */
        .dark .chart-tooltip {
            background-color: #374151 !important;
            color: #fff !important;
            border: 1px solid #4b5563 !important;
        }

        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .chart-container {
                min-height: 250px;
            }

            .sidebar-collapsed main {
                margin-left: 0;
            }
        }

        @media (max-width: 480px) {

            .grid-cols-4,
            .grid-cols-3 {
                grid-template-columns: 1fr;
            }
        }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        .dark .skeleton {
            background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }
    </style>
    <style>
        .chart-container {
            min-height: 400px;
            position: relative;
        }

        #status-chart,
        #validation-chart,
        #age-chart,
        #barangay-chart {
            width: 100%;
            height: 100%;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .age-group-modal>div {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .pulse {
            animation: pulse 1s ease-in-out;
        }

        /* Custom scrollbar for modal if needed */
        .age-group-modal>div {
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Dark mode adjustments for modal */
        .dark .age-group-modal>div {
            background-color: #1f2937;
            border-color: #374151;
        }
    </style>
    <script type="text/javascript">
        google.charts.load('current', {
            'packages': ['corechart', 'bar']
        });
        google.charts.setOnLoadCallback(initializeCharts);

        let charts = {};
        let isDarkMode = false;
        let originalBarangayData = null;
        let originalBarangayOptions = null;
        let ageChartData = null;

        function initializeCharts() {
            isDarkMode = document.documentElement.classList.contains('dark');
            drawCharts();

            // Debug logging
            console.log('Charts initialized');
        }

        function drawCharts() {
            const chartOptions = {
                backgroundColor: 'transparent',
                chartArea: {
                    width: '85%',
                    height: '75%'
                },
                legend: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    },
                    position: 'labeled'
                },
                tooltip: {
                    textStyle: {
                        color: isDarkMode ? '#fff' : '#374151',
                        fontSize: 12
                    },
                    showColorCode: true,
                    trigger: 'selection'
                },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            // Status Distribution Pie Chart
            try {
                var statusData = google.visualization.arrayToDataTable([
                    ['Status', 'Count'],
                    ['Active', <?php echo $stats['status']['Active'] ?? 0; ?>],
                    ['Inactive', <?php echo $stats['status']['Inactive'] ?? 0; ?>],
                    ['Deceased', <?php echo $stats['status']['Deceased'] ?? 0; ?>]
                ]);

                var statusOptions = {
                    ...chartOptions,
                    title: '',
                    pieHole: 0.4,
                    colors: ['#10B981', '#F59E0B', '#EF4444'],
                    pieSliceText: 'value',
                    sliceVisibilityThreshold: 0
                };

                charts.status = new google.visualization.PieChart(document.getElementById('status-chart'));
                charts.status.draw(statusData, statusOptions);

                google.visualization.events.addListener(charts.status, 'select', function() {
                    const selection = charts.status.getSelection();
                    if (selection.length > 0) {
                        const item = selection[0];
                        const status = ['Active', 'Inactive', 'Deceased'][item.row];
                        window.location.href = `./SeniorList/${status.toLowerCase()}list.php?session_context=<?php echo $ctx; ?>`;
                    }
                });
            } catch (error) {
                console.error('Error drawing status chart:', error);
            }

            // Validation Status Pie Chart - FIXED WITH MODAL
            try {
                var validationData = google.visualization.arrayToDataTable([
                    ['Validation Status', 'Count'],
                    ['Validated', <?php echo $validated_count = $stats['validation']['Validated'] ?? 0; ?>],
                    ['For Validation', <?php echo $pending_count = $stats['validation']['For Validation'] ?? 0; ?>]
                ]);

                var validationOptions = {
                    ...chartOptions,
                    title: '',
                    pieHole: 0.4,
                    colors: ['#10B981', '#EF4444'],
                    pieSliceText: 'percentage',
                    sliceVisibilityThreshold: 0
                };

                charts.validation = new google.visualization.PieChart(document.getElementById('validation-chart'));
                charts.validation.draw(validationData, validationOptions);

                // FIXED: Add modal for validation chart
                google.visualization.events.addListener(charts.validation, 'select', function() {
                    const selection = charts.validation.getSelection();
                    if (selection.length > 0) {
                        const item = selection[0];
                        const status = item.row === 0 ? 'Validated' : 'For Validation';
                        const count = validationData.getValue(item.row, 1);
                        showValidationDetails(status, count);
                    }
                });
            } catch (error) {
                console.error('Error drawing validation chart:', error);
            }

            // Age Distribution 3D Pie Chart
            try {
                ageChartData = new google.visualization.DataTable();
                ageChartData.addColumn('string', 'Age Group');
                ageChartData.addColumn('number', 'Count');

                <?php
                $age_groups = $stats['age_groups'] ?? [];
                $all_age_groups = [
                    '60-64' => 0,
                    '65-69' => 0,
                    '70-74' => 0,
                    '75-79' => 0,
                    '80-84' => 0,
                    '85-89' => 0,
                    '90+' => 0
                ];

                foreach ($all_age_groups as $group => $value) {
                    if (isset($age_groups[$group])) {
                        $all_age_groups[$group] = $age_groups[$group];
                    }
                }

                $filtered_age_groups = array_filter($all_age_groups, function ($count) {
                    return $count > 0;
                });

                if (!empty($filtered_age_groups)) {
                    echo "ageChartData.addRows([\n";
                    foreach ($filtered_age_groups as $group => $count) {
                        echo "['$group', $count],\n";
                    }
                    echo "]);";
                } else {
                    echo "ageChartData.addRows([['No Data', 1]]);";
                }
                ?>

                var ageOptions = {
                    ...chartOptions,
                    title: '',
                    is3D: true,
                    colors: ['#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#6B7280'],
                    pieSliceText: 'value',
                    tooltip: {
                        showColorCode: true,
                        text: 'percentage'
                    }
                };

                charts.age = new google.visualization.PieChart(document.getElementById('age-chart'));
                charts.age.draw(ageChartData, ageOptions);

                // TEST: Add simple click handler first
                google.visualization.events.addListener(charts.age, 'select', function() {
                    console.log('Age chart clicked!');

                    const selection = charts.age.getSelection();
                    console.log('Selection:', selection);

                    if (selection.length > 0) {
                        const item = selection[0];
                        const ageGroup = ageChartData.getValue(item.row, 0);
                        const count = ageChartData.getValue(item.row, 1);

                        console.log('Age Group:', ageGroup, 'Count:', count);

                        // Test with simple alert first
                        // alert(`Age Group: ${ageGroup}\nCount: ${count}`);

                        // If alert works, use modal
                        showAgeGroupDetails(ageGroup, count);
                    }
                });

            } catch (error) {
                console.error('Error drawing age chart:', error);
            }

            // Barangay Distribution Bar Chart
            try {
                var barangayData = new google.visualization.DataTable();
                barangayData.addColumn('string', 'Barangay');
                barangayData.addColumn('number', 'Count');

                <?php
                $barangayColors = [
                    '#3B82F6',
                    '#8B5CF6',
                    '#EC4899',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#6B7280',
                    '#84CC16',
                    '#F97316',
                    '#A855F7',
                    '#06B6D4',
                    '#71717A'
                ];

                if (!empty($stats['barangays'])) {
                    echo "barangayData.addRows([\n";
                    foreach ($stats['barangays'] as $barangay => $count) {
                        $formattedBarangay = preg_replace('/^[IVXLCDM]+ - /', '', $barangay);
                        echo "['$formattedBarangay', $count],\n";
                    }
                    echo "]);";
                } else {
                    echo "barangayData.addRows([['No Data', 0]]);";
                }
                ?>

                var barangayOptions = {
                    ...chartOptions,
                    title: '',
                    hAxis: {
                        title: '',
                        textStyle: {
                            fontSize: 11,
                            color: isDarkMode ? '#fff' : '#4B5563',
                            bold: false
                        },
                        slantedText: true,
                        slantedTextAngle: 45,
                        showTextEvery: 1
                    },
                    vAxis: {
                        title: 'Number of Seniors',
                        titleTextStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563',
                            fontSize: 12,
                            bold: true
                        },
                        minValue: 0,
                        textStyle: {
                            color: isDarkMode ? '#fff' : '#4B5563',
                            fontSize: 11
                        },
                        gridlines: {
                            color: isDarkMode ? '#4B5563' : '#E5E7EB',
                            count: 6
                        }
                    },
                    legend: {
                        position: 'none'
                    },
                    bar: {
                        groupWidth: '70%'
                    },
                    colors: <?php echo json_encode($barangayColors); ?>,
                    focusTarget: 'category'
                };

                charts.barangay = new google.visualization.ColumnChart(document.getElementById('barangay-chart'));
                charts.barangay.draw(barangayData, barangayOptions);

                originalBarangayData = barangayData;
                originalBarangayOptions = barangayOptions;

                google.visualization.events.addListener(charts.barangay, 'select', function() {
                    const selection = charts.barangay.getSelection();
                    if (selection.length > 0) {
                        const item = selection[0];
                        const barangays = <?php echo json_encode(array_keys($all_barangays)); ?>;
                        const barangay = barangays[item.row];

                        const filterSelect = document.getElementById('barangay-filter');
                        if (filterSelect) {
                            filterSelect.value = barangay;
                        }

                        filterBarangayChart(barangay);
                    }
                });

            } catch (error) {
                console.error('Error drawing barangay chart:', error);
            }
        }

        // NEW: Show validation details modal
        function showValidationDetails(status, count) {
            console.log('Validation details:', status, count);

            // Remove any existing modal
            const existingModal = document.querySelector('.validation-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Calculate percentages
            const totalValidation = <?php echo $validated_count + $pending_count; ?>;
            const percentage = totalValidation > 0 ? ((count / totalValidation) * 100).toFixed(1) : 0;
            const otherStatus = status === 'Validated' ? 'For Validation' : 'Validated';
            const otherCount = totalValidation - count;
            const otherPercentage = totalValidation > 0 ? ((otherCount / totalValidation) * 100).toFixed(1) : 0;

            // Create modal
            const modal = document.createElement('div');
            modal.className = 'validation-modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 bg-opacity-50';
            modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300" id="validation-modal-content">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Validation Status Details</h3>
                        <button onclick="closeModal('validation')" 
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="text-center p-4 ${status === 'Validated' ? 'bg-green-50 dark:bg-green-900/30' : 'bg-red-50 dark:bg-red-900/30'} rounded-lg">
                            <div class="text-4xl font-bold ${status === 'Validated' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'} mb-2">${status}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">${count}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Number of Seniors</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold ${status === 'Validated' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${percentage}%</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Percentage</div>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Comparison:</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">${status}:</span>
                                    <span class="font-medium">${count} (${percentage}%)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">${otherStatus}:</span>
                                    <span class="font-medium">${otherCount} (${otherPercentage}%)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Quick Actions:</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <button onclick="viewSeniorsByValidation('${status}')" 
                                        class="flex items-center justify-center px-4 py-3 text-sm font-medium text-white ${status === 'Validated' ? 'bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800' : 'bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800'} rounded-lg transition-all duration-200 hover:scale-[1.02]">
                                    <i class="fas fa-users mr-2"></i>View Seniors
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg flex justify-end space-x-3">
                    <button onclick="closeModal('validation')" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        `;

            document.body.appendChild(modal);

            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal('validation');
                }
            });

            // Add escape key to close
            const handleEsc = function(e) {
                if (e.key === 'Escape') {
                    closeModal('validation');
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }

        // Show age group details modal - SIMPLIFIED VERSION
        function showAgeGroupDetails(ageGroup, count) {
            console.log('Age group details:', ageGroup, count);

            // Remove any existing modal
            const existingModal = document.querySelector('.age-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Calculate percentage
            const totalSeniors = <?php echo $stats['total'] ?? 0; ?>;
            const percentage = totalSeniors > 0 ? ((count / totalSeniors) * 100).toFixed(1) : 0;

            // Create SIMPLE modal for testing
            const modal = document.createElement('div');
            modal.className = 'age-modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 bg-opacity-50';
            modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6" id="age-modal-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Age Group Details</h3>
                    <button onclick="closeModal('age')" 
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">${ageGroup}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Age Range</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">${count}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Seniors</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${percentage}%</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">of Total</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button onclick="viewSeniorsByAge('${ageGroup}')" 
                                class="w-full px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition-colors">
                            <i class="fas fa-users mr-2"></i>View Seniors in this Age Group
                        </button>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button onclick="closeModal('age')" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500">
                        Close
                    </button>
                </div>
            </div>
        `;

            document.body.appendChild(modal);

            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal('age');
                }
            });

            // Add escape key to close
            const handleEsc = function(e) {
                if (e.key === 'Escape') {
                    closeModal('age');
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }

        // Generic close modal function
        function closeModal(type) {
            const modal = document.querySelector(`.${type}-modal`);
            if (modal) {
                modal.remove();
            }
        }

        // View seniors by validation status
        function viewSeniorsByValidation(status) {
            const url = `./SeniorList/seniorlist.php?validation=${status.toLowerCase().replace(' ', '_')}&session_context=<?php echo $ctx; ?>&status=all`;
            showToast(`Loading ${status} seniors...`, 'info');
            closeModal('validation');

            setTimeout(() => {
                window.location.href = url;
            }, 500);
        }

        // View seniors by age group
        function viewSeniorsByAge(ageGroup) {
            let minAge, maxAge;
            if (ageGroup === '90+') {
                minAge = 90;
                maxAge = 120;
            } else {
                const ages = ageGroup.split('-').map(Number);
                minAge = ages[0];
                maxAge = ages[1];
            }

            const url = `./SeniorList/seniorlist.php?age_min=${minAge}&age_max=${maxAge}&filter=age&session_context=<?php echo $ctx; ?>`;
            showToast(`Loading seniors aged ${ageGroup}...`, 'info');
            closeModal('age');

            setTimeout(() => {
                window.location.href = url;
            }, 500);
        }

        // Generate validation report
        function generateValidationReport(status) {
            showToast(`Generating ${status} report...`, 'info');
            closeModal('validation');

            setTimeout(() => {
                showToast(`${status} report generated successfully!`, 'success');
            }, 1000);
        }

        // Reset age chart selection
        function resetAgeChart() {
            if (charts.age) {
                charts.age.setSelection([]);
                showToast('Age chart selection cleared', 'info');
                closeModal('age');
            }
        }

        // Filter by barangay
        function filterByBarangay(barangay) {
            if (barangay === 'all') {
                if (charts.barangay && originalBarangayData && originalBarangayOptions) {
                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    showToast('Showing all barangays', 'info');
                }
            } else {
                filterBarangayChart(barangay);
            }
        }

        // Filter barangay chart
        function filterBarangayChart(barangay) {
            if (!charts.barangay || !originalBarangayData || !originalBarangayOptions) {
                showToast('Chart data not available', 'error');
                return;
            }

            try {
                const filteredData = new google.visualization.DataTable();
                filteredData.addColumn('string', 'Barangay');
                filteredData.addColumn('number', 'Count');

                const rows = originalBarangayData.getNumberOfRows();
                let found = false;

                for (let i = 0; i < rows; i++) {
                    const rowBarangay = originalBarangayData.getValue(i, 0);
                    const fullBarangayName = getFullBarangayName(rowBarangay);

                    if (fullBarangayName === barangay) {
                        const count = originalBarangayData.getValue(i, 1);
                        filteredData.addRow([rowBarangay, count]);
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    showToast('No data found for selected barangay', 'warning');
                    const filterSelect = document.getElementById('barangay-filter');
                    if (filterSelect) {
                        filterSelect.value = 'all';
                    }
                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    return;
                }

                const filteredOptions = {
                    ...originalBarangayOptions,
                    hAxis: {
                        ...originalBarangayOptions.hAxis,
                        textStyle: {
                            ...originalBarangayOptions.hAxis.textStyle,
                            fontSize: 14
                        }
                    },
                    bar: {
                        groupWidth: '50%'
                    }
                };

                charts.barangay.draw(filteredData, filteredOptions);
                showToast(`Showing data for ${barangay}`, 'info');

            } catch (error) {
                console.error('Error filtering barangay chart:', error);
                showToast('Error filtering chart', 'error');

                if (charts.barangay && originalBarangayData && originalBarangayOptions) {
                    charts.barangay.draw(originalBarangayData, originalBarangayOptions);
                    const filterSelect = document.getElementById('barangay-filter');
                    if (filterSelect) {
                        filterSelect.value = 'all';
                    }
                }
            }
        }

        // Helper function to get full barangay name
        function getFullBarangayName(shortName) {
            const barangayMap = {
                'Mapalad': 'I - Mapalad',
                'Handang Tumulong': 'II - Handang Tumulong',
                'Silahis ng Pag-asa': 'III - Silahis ng Pag-asa',
                'Pag-asa ng Bayan': 'IV - Pag-asa ng Bayan',
                'Bagong Silang': 'V - Bagong Silang',
                'San Jose': 'VI - San Jose',
                'Lumang Bayan': 'VII - Lumang Bayan',
                'Marikit': 'VIII - Marikit',
                'Tubili': 'IX - Tubili',
                'Alipaoy': 'X - Alipaoy',
                'Harison': 'XI - Harison',
                'Mananao': 'XII - Mananao'
            };

            return barangayMap[shortName] || shortName;
        }

        // Simple toast function
        function showToast(message, type = 'info') {
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `custom-toast fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white transition-all duration-300 transform translate-x-full ${type === 'info' ? 'bg-blue-500' : type === 'success' ? 'bg-green-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-500'}`;
            toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'info' ? 'fa-info-circle' : type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'} mr-3"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Refresh dashboard data
        function refreshDashboard() {
            const refreshBtn = document.getElementById('refresh-btn');
            const originalHtml = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;

            showToast('Refreshing dashboard...', 'info');

            setTimeout(() => {
                drawCharts();
                showToast('Dashboard updated!', 'success');
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            }, 1000);
        }

        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('drawer-navigation');
            const mainContent = document.querySelector('main');
            sidebar.classList.toggle('-translate-x-full');
            mainContent.classList.toggle('md:ml-64');
            mainContent.classList.toggle('sidebar-collapsed');
        }

        // Redraw charts on window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                drawCharts();
            }, 250);
        });

        // Theme change detection
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    isDarkMode = document.documentElement.classList.contains('dark');
                    drawCharts();
                }
            });
        });
        observer.observe(document.documentElement, {
            attributes: true
        });

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, charts should initialize');

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    refreshDashboard();
                }
                if (e.key === 'Escape') {
                    closeModal('age');
                    closeModal('validation');
                }
            });
        });
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
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
                    <img class="w-8 h-8 rounded-full object-cover"
                        src="<?php echo htmlspecialchars($profile_photo_url); ?>"
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
                    <a href="#"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
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
                    <a href="generate_id.php?session_context=<?php echo $ctx; ?>"
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
    <main class="p-4 md:ml-64 h-auto pt-20 main-content transition-all duration-300">
        <!-- Header with Actions -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Dashboard Overview</h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden md:inline">
                    Last updated: <?php echo date('M j, Y H:i'); ?>
                </span>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="mb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Seniors Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in"
                    onclick="window.location.href='./SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>'"
                    style="cursor: pointer;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Seniors</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-users text-blue-600 dark:text-blue-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">All records</span>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">
                                <i class="fas fa-database mr-1"></i>Database
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Male/Female Split Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in"
                    style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gender Distribution</p>
                        <div class="flex space-x-2">
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded dark:bg-blue-900 dark:text-blue-300">
                                <i class="fas fa-male mr-1"></i><?php echo $male_percentage; ?>%
                            </span>
                            <span class="px-2 py-1 text-xs font-medium bg-pink-100 text-pink-800 rounded dark:bg-pink-900 dark:text-pink-300">
                                <i class="fas fa-female mr-1"></i><?php echo $female_percentage; ?>%
                            </span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Male</span>
                                <span class="font-medium text-gray-900 dark:text-white"><?php echo $male_count; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $male_percentage; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Female</span>
                                <span class="font-medium text-gray-900 dark:text-white"><?php echo $female_count; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-pink-600 h-2 rounded-full" style="width: <?php echo $female_percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Overview Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in"
                    style="animation-delay: 0.2s;">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Status Overview</p>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-gray-700 dark:text-gray-300">Active</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white"><?php echo $active_count; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <span class="text-gray-700 dark:text-gray-300">Inactive</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white"><?php echo $inactive_count; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                                <span class="text-gray-700 dark:text-gray-300">Deceased</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white"><?php echo $deceased_count; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Card -->
                <div class="stat-card bg-white rounded-xl shadow p-6 dark:bg-gray-800 fade-in"
                    style="animation-delay: 0.3s;">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Recent Activity</p>
                        <span class="text-xs text-green-600 dark:text-green-400 pulse">
                            <i class="fas fa-circle"></i> Live
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900 mr-3">
                                <i class="fas fa-user-plus text-blue-600 dark:text-blue-300"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo $stats['recent_registrations']; ?> new registrations
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Last 30 days</p>
                            </div>
                            <?php if ($stats['recent_registrations'] > 0): ?>
                                <span class="text-green-600 dark:text-green-400 text-sm font-medium">
                                    +<?php echo $stats['recent_registrations']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900 mr-3">
                                <i class="fas fa-chart-line text-green-600 dark:text-green-300"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Growth trend</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Monthly analysis</p>
                            </div>
                            <span class="text-blue-600 dark:text-blue-400 text-sm font-medium">
                                <i class="fas fa-arrow-up"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Age Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Age Distribution</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Senior citizens by age groups</p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="resetAgeChart()"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                            data-tooltip="Reset chart selection">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
                <div id="age-chart" style="height: 300px;"></div>
                <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-2"></i>Click on a segment to view details
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status Distribution</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active, Inactive, and Deceased status</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900 dark:text-green-300">
                            Active: <?php echo $active_count; ?>
                        </span>
                        <button onclick="window.location.href='./SeniorList/seniorlist.php?session_context=<?php echo $ctx; ?>'"
                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            data-tooltip="View all">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                    </div>
                </div>
                <div id="status-chart" style="height: 300px;"></div>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400"><?php echo $active_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Active</div>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400"><?php echo $inactive_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Inactive</div>
                    </div>
                    <div class="text-center p-2 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <div class="text-lg font-bold text-red-600 dark:text-red-400"><?php echo $deceased_percentage; ?>%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Deceased</div>
                    </div>
                </div>
            </div>

            <!-- Validation Status Chart -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Validation Status</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Document validation progress</p>
                    </div>
                    <?php
                    $validated_count = $stats['validation']['Validated'] ?? 0;
                    $pending_count = $stats['validation']['For Validation'] ?? 0;
                    $total_validation = $validated_count + $pending_count;
                    $validation_rate = $total_validation > 0 ? round(($validated_count / $total_validation) * 100, 1) : 0;
                    ?>
                    <div class="relative w-16 h-16">
                        <svg class="w-full h-full" viewBox="0 0 36 36">
                            <path class="text-gray-200 dark:text-gray-700" stroke-width="3" fill="none"
                                d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-green-600 dark:text-green-400" stroke-width="3" stroke-dasharray="<?php echo $validation_rate; ?>, 100" stroke-linecap="round" fill="none"
                                d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <text x="18" y="22" class="text-xs font-bold fill-current text-gray-900 dark:text-white" text-anchor="middle"><?php echo $validation_rate; ?>%</text>
                        </svg>
                    </div>
                </div>
                <div id="validation-chart" style="height: 250px;"></div>
            </div>

            <!-- Barangay Distribution -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800 chart-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Barangay Distribution</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Senior citizens per barangay</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="relative" data-tooltip="Filter by barangay">
                            <select id="barangay-filter" onchange="filterByBarangay(this.value)"
                                class="appearance-none bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="all">All Barangays</option>
                                <?php foreach ($all_barangays as $barangay => $count): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>">
                                        <?php echo htmlspecialchars($barangay); ?> (<?php echo $count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
                <div id="barangay-chart" style="height: 300px;"></div>
                <div class="mt-4">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top 5 Barangays:</div>
                    <div class="flex flex-wrap gap-2">
                        <?php $counter = 0; ?>
                        <?php foreach ($top_barangays as $barangay => $count): ?>
                            <?php if ($count > 0 && $counter < 5): ?>
                                <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                    <?php echo str_replace(['I - ', 'II - ', 'III - ', 'IV - ', 'V - ', 'VI - ', 'VII - ', 'VIII - ', 'IX - ', 'X - ', 'XI - ', 'XII - '], '', $barangay); ?>: <?php echo $count; ?>
                                </span>
                                <?php $counter++; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats and Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-800 mr-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <span>Register New Senior</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-800 mr-3">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <span>View Active Seniors</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-purple-700 bg-purple-50 rounded-lg hover:bg-purple-100 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-800 mr-3">
                                <i class="fas fa-gift"></i>
                            </div>
                            <span>Manage Benefits</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="./generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center justify-between p-4 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800 transition-all duration-200 hover:translate-x-2">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-800 mr-3">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <span>Generate ID</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-gray-700 dark:text-gray-300">Database Connection</span>
                        </div>
                        <span class="text-green-600 dark:text-green-400 text-sm font-medium">Active</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-gray-700 dark:text-gray-300">Server Status</span>
                        </div>
                        <span class="text-green-600 dark:text-green-400 text-sm font-medium">Online</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-gray-700 dark:text-gray-300">Storage Space</span>
                        </div>
                        <span class="text-green-600 dark:text-green-400 text-sm font-medium">85% Free</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-gray-700 dark:text-gray-300">Last Backup</span>
                        </div>
                        <span class="text-gray-600 dark:text-gray-400 text-sm">Today, 02:00 AM</span>
                    </div>
                </div>
            </div>

            <!-- Recent Updates -->
            <div class="bg-white rounded-xl shadow p-6 dark:bg-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Updates</h3>
                    <button onclick="refreshDashboard()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="border-l-4 border-blue-500 pl-4 py-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">New registration process implemented</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">2 hours ago</p>
                    </div>
                    <div class="border-l-4 border-green-500 pl-4 py-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Database optimization completed</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">1 day ago</p>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-4 py-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Report generation enhanced</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">3 days ago</p>
                    </div>
                    <div class="border-l-4 border-yellow-500 pl-4 py-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Mobile responsiveness improved</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">1 week ago</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Stats -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total']; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Records</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo $validated_count; ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Validated</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">12</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Barangays</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo date('Y'); ?></div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Current Year</div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>
    <script>
        // ---------- THEME INITIALIZATION (MUST BE OUTSIDE DOMContentLoaded) ----------
        // Initialize theme from localStorage or system preference
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            let theme = 'light';
            if (savedTheme) {
                theme = savedTheme;
            } else if (systemPrefersDark) {
                theme = 'dark';
            }

            setTheme(theme);
        }

        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }

        // Listen for theme changes from other pages
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const theme = e.newValue;
                setTheme(theme);
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Initialize theme on page load (BEFORE DOMContentLoaded)
        initTheme();
    </script>
    <script>
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip-target]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new Flowbite.Tooltip(tooltipTriggerEl);
        });

        // Add loading state for buttons
        document.querySelectorAll('a[href], button').forEach(element => {
            element.addEventListener('click', function(e) {
                if (this.getAttribute('href') || this.type === 'submit') {
                    this.classList.add('opacity-75', 'cursor-wait');
                    setTimeout(() => this.classList.remove('opacity-75', 'cursor-wait'), 1000);
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + R to refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshDashboard();
            }
            // Ctrl + P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            // Escape to clear selections
            if (e.key === 'Escape') {
                Object.values(charts).forEach(chart => chart.setSelection([]));
            }
        });

        // Auto-refresh every 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                refreshDashboard();
            }
        }, 300000); // 5 minutes
    </script>

</body>

</html>