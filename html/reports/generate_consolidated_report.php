<?php
// generate_consolidated_report.php - FIXED VERSION
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start session and check admin access
require_once '../../php/login/admin_header.php';

// Get filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$ctx = isset($_GET['session_context']) ? urlencode($_GET['session_context']) : urlencode(session_id());

// Validate parameters
if ($month !== null && ($month < 1 || $month > 12)) {
    $month = null;
}
if ($year !== null && ($year < 1900 || $year > date('Y') + 1)) {
    $year = null;
}

// Set display text
$displayText = 'All Time';
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

if ($year && $month) {
    $displayText = $monthNames[$month] . ' ' . $year;
} elseif ($year) {
    $displayText = 'Year ' . $year;
} elseif ($month) {
    $displayText = $monthNames[$month] . ' (All Years)';
}

// Try to fetch data from backend
$reportData = [];
$hasData = false;
$errorMessage = '';
$apiResult = null;

try {
    // Use relative path from current directory
    $backendFile = __DIR__ . '/../../php/reports/generate_consolidated_report_backend.php';

    // Alternative path check
    if (!file_exists($backendFile)) {
        // Try another possible path
        $backendFile = dirname(__DIR__) . '/../php/reports/generate_consolidated_report_backend.php';
    }

    if (file_exists($backendFile)) {
        // Create GET parameters array for the backend
        $backendParams = [];
        if ($year !== null) $backendParams['year'] = $year;
        if ($month !== null) $backendParams['month'] = $month;

        // Build query string
        $queryString = http_build_query($backendParams);

        // Use cURL to call the backend
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $backendUrl = $baseUrl . '/MSWDPALUAN_SYSTEM-MAIN/php/reports/generate_consolidated_report_backend.php';

        if ($queryString) {
            $backendUrl .= '?' . $queryString;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backendUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $jsonResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode === 200 && $jsonResponse) {
            $apiResult = json_decode($jsonResponse, true);

            if ($apiResult && isset($apiResult['success'])) {
                if ($apiResult['success'] && isset($apiResult['data'])) {
                    $reportData = $apiResult['data'];
                    $hasData = true;

                    // Update display text from backend if available
                    if (isset($apiResult['filters']['month_name']) && $apiResult['filters']['month_name'] && $apiResult['filters']['year']) {
                        $displayText = $apiResult['filters']['month_name'] . ' ' . $apiResult['filters']['year'];
                    }
                } else {
                    $errorMessage = $apiResult['message'] ?? 'Backend returned unsuccessful response';
                    error_log("Backend error: " . $errorMessage);
                }
            } else {
                $errorMessage = 'Invalid JSON response from backend';
                error_log($errorMessage . ": " . substr($jsonResponse, 0, 200));
            }
        } else {
            $errorMessage = "Backend request failed with HTTP code: $httpCode";
            error_log($errorMessage);
        }
    } else {
        $errorMessage = 'Backend file not found. Checked path: ' . $backendFile;
        error_log($errorMessage);
    }
} catch (Exception $e) {
    $errorMessage = 'Exception: ' . $e->getMessage();
    error_log("Error fetching report data: " . $e->getMessage());
}

// If no data from backend, create fallback data
if (!$hasData) {
    // Create empty data structure
    $reportData = [
        'part1' => [
            'data' => [],
            'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
        ],
        'part2' => ['data' => [], 'count' => 0],
        'part3' => [
            'data' => [],
            'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
        ],
        'part4' => [
            'data' => [],
            'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]
        ],
        'part5' => ['data' => [], 'count' => 0],
        'part6' => ['data' => [], 'count' => 0],
        'part7to9' => [
            'philhealth_count' => 0,
            'booklets_count' => 0,
            'activities' => []
        ],
        'benefits' => []
    ];

    // Create default benefits data
    $benefitTypes = [
        'OSCA ID (New)',
        'Social Pension',
        'LSP (SSS/GSIS)',
        'LSP Non Pensioners',
        'AICS',
        'Birthday Gift',
        'Milestone',
        'Bedridden SC',
        'Burial Assistance',
        'Medical Assistance Php.5,000.00',
        'Centenarian Awardee (Php.50,000.00)',
        'Medical Assistance Php.1,000.00',
        'Christmas Gift'
    ];

    foreach ($benefitTypes as $benefit) {
        $reportData['benefits'][$benefit] = [
            'male' => 0,
            'female' => 0,
            'total' => 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data of Senior Citizen <?php echo $year ?: date('Y'); ?></title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @media print {
            @page {
                size: 8.5in 13in;
                margin: 0.5in;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: "Times New Roman", serif;
                font-size: 12pt;
                line-height: 1.2;
            }

            .page-break {
                page-break-after: always;
            }

            .no-break {
                page-break-inside: avoid;
            }

            .print-page {
                min-height: 11.5in;
                position: relative;
            }

            .no-print {
                display: none !important;
            }

            .error-alert {
                display: none !important;
            }

            .success-alert {
                display: none !important;
            }
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.2;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .page-container {
            max-width: 8.5in;
            margin: 0 auto;
            background: white;
        }

        .print-page {
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            background: white;
        }

        .header-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .header-seal {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px;
        }

        .header-text {
            line-height: 1.1;
        }

        .header-text h4 {
            margin: 2px 0;
            font-size: 11pt;
            font-weight: normal;
        }

        .header-text h3 {
            margin: 5px 0 0 0;
            font-size: 12pt;
            font-weight: bold;
        }

        .subheader-text {
            margin-top: 20px;
            font-size: 12pt;
        }

        .report-title {
            text-align: left;
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .data-table td:first-child {
            text-align: left;
            padding-left: 8px;
        }

        .total-row {
            background-color: #d0d0d0 !important;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 40px;
            text-align: left;
            margin-left: 20px;
        }

        .signature-name {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 5px;
            margin-left: 30px;
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .error-alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px;
            border-radius: 5px;
            max-width: 300px;
        }

        .success-alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 10px;
            border-radius: 5px;
            max-width: 300px;
        }

        .debug-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #f3f4f6;
            padding: 5px;
            font-size: 10px;
            border: 1px solid #ccc;
            display: none;
        }
    </style>
</head>

<body>
    <?php if ($errorMessage): ?>
        <div class="error-alert no-print">
            <strong>⚠️ Error:</strong><br>
            <?php echo htmlspecialchars($errorMessage); ?>
            <br><br>
            <small>Using fallback data for display. Backend data not available.</small>
        </div>
    <?php elseif ($hasData && $apiResult): ?>
        <div class="success-alert no-print">
            <strong>✅ Data Loaded Successfully</strong><br>
            Showing data for: <?php echo $displayText; ?><br>
            Total Seniors: <?php echo number_format($reportData['part1']['totals']['overall'] ?? 0); ?>
        </div>
    <?php else: ?>
        <div class="error-alert no-print">
            <strong>⚠️ No Data Available</strong><br>
            Using fallback template for: <?php echo $displayText; ?>
        </div>
    <?php endif; ?>

    <div class="print-controls no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
            🖨️ Print Report
        </button>
        <button onclick="window.location.href='report.php?session_context=<?php echo $ctx; ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>'"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm ml-2">
            ← Back to Reports
        </button>
        <div class="mt-2 text-xs text-gray-600">
            Report Period: <?php echo $displayText; ?><br>
            Data Source: <?php echo $hasData ? 'Database' : 'Fallback Template'; ?><br>
            <?php if ($errorMessage): ?>
                <span class="text-red-500">⚠️ Error encountered</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-container">
        <!-- PAGE 1: Summary Table (Municipal Level) -->
        <div class="print-page page-break">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">

            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>

            <div class="report-title">
                DATA OF SENIOR CITIZEN <?php echo $year ?: date('Y'); ?>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 65%; text-align: left; padding-left: 10px;">SERVICES AVAILED</th>
                        <th style="width: 12%;">MALE</th>
                        <th style="width: 12%;">FEMALE</th>
                        <th style="width: 11%;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $benefitData = $reportData['benefits'] ?? [];
                    $benefitTypes = [
                        'OSCA ID (New)',
                        'Social Pension',
                        'LSP (SSS/GSIS)',
                        'LSP Non Pensioners',
                        'AICS',
                        'Birthday Gift',
                        'Milestone',
                        'Bedridden SC',
                        'Burial Assistance',
                        'Medical Assistance Php.5,000.00',
                        'Centenarian Awardee (Php.50,000.00)',
                        'Medical Assistance Php.1,000.00',
                        'Christmas Gift'
                    ];

                    $totalMale = 0;
                    $totalFemale = 0;
                    $totalOverall = 0;

                    foreach ($benefitTypes as $benefit) {
                        if (isset($benefitData[$benefit])) {
                            $benefitInfo = $benefitData[$benefit];
                            $maleCount = is_array($benefitInfo) ? ($benefitInfo['male'] ?? 0) : 0;
                            $femaleCount = is_array($benefitInfo) ? ($benefitInfo['female'] ?? 0) : 0;
                            $totalCount = is_array($benefitInfo) ? ($benefitInfo['total'] ?? 0) : $benefitInfo;
                        } else {
                            $maleCount = 0;
                            $femaleCount = 0;
                            $totalCount = 0;
                        }

                        $totalMale += $maleCount;
                        $totalFemale += $femaleCount;
                        $totalOverall += $totalCount;

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($benefit) . "</td>";
                        echo "<td>" . number_format($maleCount) . "</td>";
                        echo "<td>" . number_format($femaleCount) . "</td>";
                        echo "<td>" . number_format($totalCount) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                    <tr class="total-row">
                        <td>TOTAL NUMBER OF SENIOR CITIZENS SERVED</td>
                        <td><?php echo number_format($totalMale); ?></td>
                        <td><?php echo number_format($totalFemale); ?></td>
                        <td><?php echo number_format($totalOverall); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGE 2: Part I -->
        <div class="print-page page-break">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">
            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>
            <div class="report-title">
                I. Number of Registered Senior Citizens
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70%; text-align: left; padding-left: 10px;">Name of Barangay</th>
                        <th style="width: 10%;">Male</th>
                        <th style="width: 10%;">Female</th>
                        <th style="width: 10%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part1Data = $reportData['part1']['data'] ?? [];
                    $part1Totals = $reportData['part1']['totals'] ?? ['male' => 0, 'female' => 0, 'overall' => 0];

                    if (empty($part1Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No registered seniors found for " . $displayText . "</td></tr>";
                    } else {
                        foreach ($part1Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? 'Unknown') . "</td>";
                            echo "<td>" . number_format($row['male_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['female_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['total_count'] ?? 0) . "</td>";
                            echo "</tr>";
                        }

                        // Total row
                        echo "<tr class='total-row'>";
                        echo "<td>TOTAL</td>";
                        echo "<td>" . number_format($part1Totals['male']) . "</td>";
                        echo "<td>" . number_format($part1Totals['female']) . "</td>";
                        echo "<td>" . number_format($part1Totals['overall']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- PAGE 3: Part II -->
        <div class="print-page page-break">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">
            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>
            <div class="report-title">
                II. Number of Newly Registered Senior Citizens for the Month of <?php echo $month ? $monthNames[$month] : ''; ?>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">No.</th>
                        <th style="width: 40%; text-align: left; padding-left: 10px;">Name</th>
                        <th style="width: 15%;">Date of Birth</th>
                        <th style="width: 10%;">Age</th>
                        <th style="width: 10%;">Sex</th>
                        <th style="width: 15%;">Barangay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part2Data = $reportData['part2']['data'] ?? [];

                    if (empty($part2Data)) {
                        echo "<tr><td colspan='6' style='text-align: center;'>No newly registered seniors for " . ($month ? $monthNames[$month] . ' ' . $year : 'this period') . "</td></tr>";
                    } else {
                        foreach ($part2Data as $index => $row) {
                            echo "<tr>";
                            echo "<td>" . ($index + 1) . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                            echo "<td>" . ($row['date_of_birth'] ?? '') . "</td>";
                            echo "<td>" . ($row['age'] ?? '') . "</td>";
                            echo "<td>" . ($row['sex'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? '') . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- PAGE 4: Part III & IV -->
        <div class="print-page page-break">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">
            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>

            <div class="report-title">
                III. Number of Pensioners / Barangay
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70%; text-align: left; padding-left: 10px;">Name of Barangay</th>
                        <th style="width: 10%;">Male</th>
                        <th style="width: 10%;">Female</th>
                        <th style="width: 10%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part3Data = $reportData['part3']['data'] ?? [];
                    $part3Totals = $reportData['part3']['totals'] ?? ['male' => 0, 'female' => 0, 'overall' => 0];

                    if (empty($part3Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No pensioner data available</td></tr>";
                    } else {
                        foreach ($part3Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? 'Unknown') . "</td>";
                            echo "<td>" . number_format($row['male_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['female_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['total_count'] ?? 0) . "</td>";
                            echo "</tr>";
                        }

                        // Total row
                        echo "<tr class='total-row'>";
                        echo "<td>TOTAL</td>";
                        echo "<td>" . number_format($part3Totals['male']) . "</td>";
                        echo "<td>" . number_format($part3Totals['female']) . "</td>";
                        echo "<td>" . number_format($part3Totals['overall']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="report-title">
                IV. Number of Localized Pensioners
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70%; text-align: left; padding-left: 10px;">Name of Barangay</th>
                        <th style="width: 10%;">Male</th>
                        <th style="width: 10%;">Female</th>
                        <th style="width: 10%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part4Data = $reportData['part4']['data'] ?? [];
                    $part4Totals = $reportData['part4']['totals'] ?? ['male' => 0, 'female' => 0, 'overall' => 0];

                    if (empty($part4Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No localized pensioner data available</td></tr>";
                    } else {
                        foreach ($part4Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? 'Unknown') . "</td>";
                            echo "<td>" . number_format($row['male_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['female_count'] ?? 0) . "</td>";
                            echo "<td>" . number_format($row['total_count'] ?? 0) . "</td>";
                            echo "</tr>";
                        }

                        // Total row
                        echo "<tr class='total-row'>";
                        echo "<td>TOTAL</td>";
                        echo "<td>" . number_format($part4Totals['male']) . "</td>";
                        echo "<td>" . number_format($part4Totals['female']) . "</td>";
                        echo "<td>" . number_format($part4Totals['overall']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- PAGE 5: Part V & VI -->
        <div class="print-page page-break">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">
            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>

            <div class="report-title">
                V. List of Bedridden Senior Citizens
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">No.</th>
                        <th style="width: 60%; text-align: left; padding-left: 10px;">Name</th>
                        <th style="width: 10%;">Sex</th>
                        <th style="width: 20%;">Barangay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part5Data = $reportData['part5']['data'] ?? [];

                    if (empty($part5Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No bedridden seniors for this period</td></tr>";
                    } else {
                        foreach ($part5Data as $index => $row) {
                            echo "<tr>";
                            echo "<td>" . ($index + 1) . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                            echo "<td>" . ($row['sex'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? '') . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <div class="report-title">
                VI. List of Deceased Registered Senior Citizens
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">No.</th>
                        <th style="width: 50%; text-align: left; padding-left: 10px;">Name</th>
                        <th style="width: 20%;">Date of Birth</th>
                        <th style="width: 20%;">Barangay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part6Data = $reportData['part6']['data'] ?? [];

                    if (empty($part6Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No deceased seniors for this period</td></tr>";
                    } else {
                        foreach ($part6Data as $index => $row) {
                            echo "<tr>";
                            echo "<td>" . ($index + 1) . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                            echo "<td>" . ($row['date_of_birth'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay'] ?? '') . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- PAGE 6: Part VII-IX -->
        <div class="print-page">
            <div class="header-container">
                <div class="flex justify-center items-start mb-4">
                    <div class="mr-4">
                        <img src="../../img/paluan.png" alt="Municipal Seal" class="header-seal">
                    </div>
                    <div class="header-text">
                        <h4>Republic of the Philippines</h4>
                        <h4>PROVINCE OF OCCIDENTAL MINDORO</h4>
                        <h4>Municipality of Paluan</h4>
                        <h3>OFFICE OF THE SENIOR CITIZENS AFFAIRS (OSCA)</h3>
                    </div>
                </div>
            </div>
            <hr class="border-1">
            <div class="subheader-text">
                <h3>Monthly Report</h3>
                <h3>As of <?php echo $displayText; ?></h3>
            </div>

            <div class="report-title">
                VII. Number of Senior Citizens who registered to Philhealth for the Month
                <h3 class="text-center">(<?php echo $reportData['part7to9']['philhealth_count'] ?? 0; ?>)</h3>
            </div>

            <div class="report-title mt-20">
                VIII. Total number of release purchase Booklets
                <h3 class="text-center">(<?php echo $reportData['part7to9']['booklets_count'] ?? 0; ?>)</h3>
            </div>

            <div class="report-title mt-20">
                IX. Activities
                <ul class="space-y-1 text-body list-disc list-inside">
                    <?php
                    $activities = $reportData['part7to9']['activities'] ?? [];
                    if (empty($activities)) {
                        echo "<li>No activities recorded for this period</li>";
                    } else {
                        foreach ($activities as $activity) {
                            echo "<li>" . htmlspecialchars($activity) . "</li>";
                        }
                    }
                    ?>
                </ul>
            </div>

            <div class="signature-section">
                <h2>Prepared by:</h2>
                <div class="signature-name">
                    <h3>ROWENA V. IDIOMA</h3>
                    <h3>SC Official</h3>
                </div>
                <div class="signature-name">
                    <h3>EVELYN V. BELTRAN</h3>
                    <h3>OSCA HEAD</h3>
                </div>
            </div>
            <div class="signature-section">
                <h2>Noted by:</h2>
                <div class="signature-name">
                    <h3>EMILY E. ARCONADA, RSW</h3>
                    <h3>MSWDO</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="debug-info no-print">
        Year: <?php echo $year ?? 'null'; ?><br>
        Month: <?php echo $month ?? 'null'; ?><br>
        Has Data: <?php echo $hasData ? 'Yes' : 'No'; ?>
    </div>

    <script>
        window.onload = function() {
            console.log('Report loaded for:', '<?php echo $displayText; ?>');
            console.log('Total seniors:', <?php echo $reportData['part1']['totals']['overall'] ?? 0; ?>);
            console.log('Data source:', '<?php echo $hasData ? "Database" : "Fallback"; ?>');

            <?php if ($errorMessage): ?>
                console.error('Error:', '<?php echo addslashes($errorMessage); ?>');
            <?php endif; ?>

            // Debug info
            console.log('URL Parameters:', {
                year: '<?php echo $year; ?>',
                month: '<?php echo $month; ?>',
                session_context: '<?php echo $ctx; ?>'
            });
        };
    </script>
</body>

</html>