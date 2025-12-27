<?php
// generate_consolidated_report.php
require_once "../../php/login/admin_header.php";

// Get filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

// Validate parameters
if ($month !== null && ($month < 1 || $month > 12)) {
    $month = null;
}
if ($year !== null && ($year < 1900 || $year > date('Y') + 1)) {
    $year = null;
}

// Fetch report data
$reportData = [];
$hasData = false;

try {
    // Use the existing backend
    $ch = curl_init();
    $url = '../reports/generate_consolidated_report_backend.php?' . http_build_query([
        'year' => $year,
        'month' => $month
    ]);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        if ($result['success']) {
            $reportData = $result['data'];
            $hasData = true;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching report data: " . $e->getMessage());
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

// If no data, show empty values
if (!$hasData) {
    $reportData = [
        'part1' => ['data' => [], 'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]],
        'part2' => ['data' => [], 'count' => 0],
        'part3' => ['data' => [], 'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]],
        'part4' => ['data' => [], 'totals' => ['male' => 0, 'female' => 0, 'overall' => 0]],
        'part5' => ['data' => [], 'count' => 0],
        'part6' => ['data' => [], 'count' => 0],
        'part7to9' => [
            'philhealth_count' => 0,
            'booklets_count' => 0,
            'activities' => []
        ],
        'benefits' => []
    ];
}

$ctx = urlencode($_GET['session_context'] ?? session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data of Senior Citizen <?php echo $year ?: date('Y'); ?></title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        /* Your existing CSS styles remain the same */
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

            .print-header {
                margin-bottom: 20px;
            }

            .print-table {
                margin-top: 20px;
                margin-bottom: 40px;
            }

            .no-print {
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
        }
    </style>
</head>

<body>
    <div class="print-controls no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
            🖨️ Print Report
        </button>
        <button onclick="window.location.href='report.php?session_context=<?php echo $ctx; ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>'"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm ml-2">
            ← Back to Reports
        </button>
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
                    $benefitData = $reportData['benefits'];
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
                        $maleCount = isset($benefitData[$benefit]['male']) ? $benefitData[$benefit]['male'] : 0;
                        $femaleCount = isset($benefitData[$benefit]['female']) ? $benefitData[$benefit]['female'] : 0;
                        $totalCount = isset($benefitData[$benefit]['total']) ? $benefitData[$benefit]['total'] : 0;

                        $totalMale += $maleCount;
                        $totalFemale += $femaleCount;
                        $totalOverall += $totalCount;

                        echo "<tr>";
                        echo "<td>$benefit</td>";
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
                        <th style="width: 15%;">Male</th>
                        <th style="width: 15%;">Female</th>
                        <th style="width: 15%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part1Data = $reportData['part1']['data'];
                    $part1Totals = $reportData['part1']['totals'];

                    if (empty($part1Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No data available</td></tr>";
                    } else {
                        foreach ($part1Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
                            echo "<td>" . number_format($row['male_count']) . "</td>";
                            echo "<td>" . number_format($row['female_count']) . "</td>";
                            echo "<td>" . number_format($row['total_count']) . "</td>";
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
                    $part2Data = $reportData['part2']['data'];

                    if (empty($part2Data)) {
                        echo "<tr><td colspan='6' style='text-align: center;'>No newly registered seniors for this period</td></tr>";
                    } else {
                        foreach ($part2Data as $row) {
                            echo "<tr>";
                            echo "<td>" . $row['number'] . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . $row['date_of_birth'] . "</td>";
                            echo "<td>" . $row['age'] . "</td>";
                            echo "<td>" . $row['sex'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
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
                        <th style="width: 15%;">Male</th>
                        <th style="width: 15%;">Female</th>
                        <th style="width: 15%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part3Data = $reportData['part3']['data'];
                    $part3Totals = $reportData['part3']['totals'];

                    if (empty($part3Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No pensioner data available</td></tr>";
                    } else {
                        foreach ($part3Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
                            echo "<td>" . number_format($row['male_count']) . "</td>";
                            echo "<td>" . number_format($row['female_count']) . "</td>";
                            echo "<td>" . number_format($row['total_count']) . "</td>";
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
                        <th style="width: 15%;">Male</th>
                        <th style="width: 15%;">Female</th>
                        <th style="width: 15%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $part4Data = $reportData['part4']['data'];
                    $part4Totals = $reportData['part4']['totals'];

                    if (empty($part4Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No localized pensioner data available</td></tr>";
                    } else {
                        foreach ($part4Data as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
                            echo "<td>" . number_format($row['male_count']) . "</td>";
                            echo "<td>" . number_format($row['female_count']) . "</td>";
                            echo "<td>" . number_format($row['total_count']) . "</td>";
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
                    $part5Data = $reportData['part5']['data'];

                    if (empty($part5Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No bedridden seniors for this period</td></tr>";
                    } else {
                        foreach ($part5Data as $row) {
                            echo "<tr>";
                            echo "<td>" . $row['number'] . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . $row['sex'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
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
                    $part6Data = $reportData['part6']['data'];

                    if (empty($part6Data)) {
                        echo "<tr><td colspan='4' style='text-align: center;'>No deceased seniors for this period</td></tr>";
                    } else {
                        foreach ($part6Data as $row) {
                            echo "<tr>";
                            echo "<td>" . $row['number'] . ".</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . $row['date_of_birth'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
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
                <h3 class="text-center">(<?php echo $reportData['part7to9']['philhealth_count']; ?>)</h3>
            </div>

            <div class="report-title mt-20">
                VIII. Total number of release purchase Booklets
                <h3 class="text-center">(<?php echo $reportData['part7to9']['booklets_count']; ?>)</h3>
            </div>

            <div class="report-title mt-20">
                IX. Activities
                <ul class="space-y-1 text-body list-disc list-inside">
                    <?php
                    $activities = $reportData['part7to9']['activities'];
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

    <script>
        // Auto-print option (optional)
        window.onload = function() {
            // You can enable auto-print if desired
            // setTimeout(() => window.print(), 1000);
        };
    </script>
</body>

</html>