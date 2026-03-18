<?php
require_once "../../php/login/staff_header.php";

$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the session context from URL for navigation
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Define the complete barangay order with proper names
$barangayOrder = [
    'I' => ['code' => 'I', 'name' => 'MAPALAD', 'sort_order' => 1],
    'II' => ['code' => 'II', 'name' => 'HANDANG TIMULONG', 'sort_order' => 2],
    'III' => ['code' => 'III', 'name' => 'SILAHIS NG PAG ASA', 'sort_order' => 3],
    'IV' => ['code' => 'IV', 'name' => 'PAG ASA NG BAYAN', 'sort_order' => 4],
    'V' => ['code' => 'V', 'name' => 'BAGONG SILANG', 'sort_order' => 5],
    'VI' => ['code' => 'VI', 'name' => 'SAN ISIDRO', 'sort_order' => 6],
    'VII' => ['code' => 'VII', 'name' => 'SAN JOSE', 'sort_order' => 7],
    'VIII' => ['code' => 'VIII', 'name' => 'SANTO NIÑO', 'sort_order' => 8],
    'IX' => ['code' => 'IX', 'name' => 'SAN VICENTE', 'sort_order' => 9],
    'X' => ['code' => 'X', 'name' => 'SIERRA BULLONES', 'sort_order' => 10],
    'XI' => ['code' => 'XI', 'name' => 'SAN ANTONIO', 'sort_order' => 11],
    'XII' => ['code' => 'XII', 'name' => 'SANTA MARIA', 'sort_order' => 12]
];

// Fetch ALL seniors with "For Validation" status
$query = "SELECT 
            a.applicant_id,
            a.first_name,
            a.middle_name,
            a.last_name,
            a.birth_date,
            addr.barangay,
            a.validation
          FROM applicants a
          LEFT JOIN addresses addr ON a.applicant_id = addr.applicant_id
          WHERE a.validation = 'For Validation' 
          AND a.status = 'Active'
          ORDER BY a.last_name, a.first_name";

$stmt = $pdo->prepare($query);
$stmt->execute();
$seniors = $stmt->fetchAll();

// Process each senior to add sorting information
$processedSeniors = [];
foreach ($seniors as $senior) {
    $barangayCode = 'Unknown';
    $barangayName = 'Unknown Barangay';
    $sortOrder = 99; // Put unknown at the end

    $rawBarangay = trim($senior['barangay'] ?? '');

    if (!empty($rawBarangay)) {
        // Try to extract Roman numeral from various formats
        if (preg_match('/Brgy\.?\s*([IVXLCDM]+)/i', $rawBarangay, $matches)) {
            $romanNumeral = strtoupper($matches[1]);
            if (isset($barangayOrder[$romanNumeral])) {
                $barangayCode = $romanNumeral;
                $barangayName = $barangayOrder[$romanNumeral]['name'];
                $sortOrder = $barangayOrder[$romanNumeral]['sort_order'];
            }
        } elseif (preg_match('/^([IVXLCDM]+)/i', $rawBarangay, $matches)) {
            $romanNumeral = strtoupper($matches[1]);
            if (isset($barangayOrder[$romanNumeral])) {
                $barangayCode = $romanNumeral;
                $barangayName = $barangayOrder[$romanNumeral]['name'];
                $sortOrder = $barangayOrder[$romanNumeral]['sort_order'];
            }
        }
    }

    $senior['barangay_code'] = $barangayCode;
    $senior['barangay_name'] = $barangayName;
    $senior['sort_order'] = $sortOrder;
    $senior['display_address'] = ($barangayCode == 'Unknown') ? 'Address not specified' : $barangayCode . ' - ' . $barangayName;

    $processedSeniors[] = $senior;
}

// Sort seniors by barangay order, then by last name, first name
usort($processedSeniors, function ($a, $b) {
    if ($a['sort_order'] == $b['sort_order']) {
        // Same barangay, sort by last name, then first name
        if ($a['last_name'] == $b['last_name']) {
            return strcmp($a['first_name'], $b['first_name']);
        }
        return strcmp($a['last_name'], $b['last_name']);
    }
    return $a['sort_order'] - $b['sort_order'];
});

// Calculate total seniors count
$totalSeniors = count($processedSeniors);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For Validation List - Print Preview</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.25in;
            }

            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                font-size: 9pt;
                color: #000;
            }

            .no-print {
                display: none !important;
            }

            .container {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            .controls,
            .stats,
            .debug-info,
            .signature-section {
                display: none !important;
            }

            table {
                border-collapse: collapse;
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background-color: #fff;
            font-size: 9pt;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 10px;
        }

        .print-header {
            text-align: left;
            margin-bottom: 10px;
            /* border-bottom: 2px solid #000; */
            padding-bottom: 5px;
        }

        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 5px;
        }

        .header-logos img {
            height: 50px;
        }

        .print-header h1 {
            color: #000;
            margin: 2px 0;
            font-size: 14pt;
            font-weight: bold;
        }

        .print-header h2 {
            color: #000;
            margin: 2px 0 5px 0;
            font-size: 12pt;
            font-weight: bold;
            color: #d32f2f;
        }

        .print-header .date-info {
            color: #000;
            font-size: 8pt;
        }

        .controls {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f5f5f5;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 8pt;
        }

        .btn-print {
            background-color: #1976d2;
            color: white;
        }

        .btn-back {
            background-color: #757575;
            color: white;
        }

        .stats {
            background-color: #e3f2fd;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            border: 1px solid #bbdefb;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 10pt;
            font-weight: bold;
            color: #000;
        }

        .stat-label {
            font-size: 7pt;
            color: #666;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8pt;
            border: 1px solid #000;
        }

        .main-table th {
            background-color: #f2f2f2;
            color: #000;
            padding: 6px 3px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            vertical-align: middle;
        }

        .main-table td {
            padding: 5px 3px;
            border: 1px solid black;
            vertical-align: middle;
            text-align: center;
        }

        .main-table td:first-child {
            text-align: center;
            font-weight: bold;
            width: 30px;
        }

        .main-table td:nth-child(5) {
            text-align: center;
            width: 70px;
        }

        .main-table td:nth-child(6) {
            width: 120px;
        }

        .main-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
            border: 1px solid #ddd;
            margin: 15px 0;
        }

        .signature-section {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 8pt;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
            width: 180px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 25px 0 3px 0;
            width: 120px;
            display: inline-block;
        }

        .signature-name {
            font-weight: bold;
            font-size: 8pt;
        }

        .signature-title {
            font-size: 7pt;
            color: #666;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            color: #666;
            font-size: 7pt;
            padding-top: 8px;
            border-top: 1px solid #ccc;
        }

        .debug-info {
            background-color: #ffebee;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            border: 1px solid #ffcdd2;
            font-size: 8pt;
            display: none;
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 5px;
            }

            .header-logos {
                flex-direction: column;
                gap: 5px;
            }

            .header-logos img {
                height: 40px;
            }

            .controls {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Debug information (hidden by default) -->
        <div class="debug-info no-print">
            <strong>Debug Information:</strong><br>
            Total seniors fetched: <?php echo count($seniors); ?><br>
            Total processed seniors: <?php echo count($processedSeniors); ?><br>
            <?php if (count($processedSeniors) > 0): ?>
                <br><strong>Sample data (first 5):</strong><br>
                <?php for ($i = 0; $i < min(5, count($processedSeniors)); $i++): ?>
                    <?php echo ($i + 1) . '. ' . htmlspecialchars($processedSeniors[$i]['first_name'] . ' ' . $processedSeniors[$i]['last_name']) .
                        ' - Address: ' . htmlspecialchars($processedSeniors[$i]['display_address']) . '<br>'; ?>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

        <div class="print-header">
            <!-- <div class="header-logos">
                <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png" alt="MSWD Logo">
                <img src="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png" alt="Municipal Logo">
            </div> -->
            <!-- <h1>MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE - PALUAN</h1> -->
            <!-- <h2>LIST OF SENIORS FOR VALIDATION</h2> -->
            <div class="date-info">
                PALUAN FOR VALIDATION <?php echo date('Y'); ?>
            </div>
        </div>

        <div class="no-print controls">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print List
            </button>
            <button class="btn btn-back" id="backToActiveList">
                <i class="fas fa-arrow-left"></i> Back to Active List
            </button>
            <!-- <button class="btn" onclick="document.querySelector('.debug-info').style.display='block'"
                style="background-color: #ff9800; color: white;">
                <i class="fas fa-bug"></i> Show Debug
            </button> -->
        </div>

        <div class="stats no-print">
            <div class="stat-item">
                <div class="stat-value"><?php echo $totalSeniors; ?></div>
                <div class="stat-label">Total Seniors for Validation</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo date('F Y'); ?></div>
                <div class="stat-label">Reference Period</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">Page 1 of 1</div>
                <div class="stat-label">Document</div>
            </div>
        </div>

        <?php if (empty($processedSeniors)): ?>
            <div class="no-data">
                <i class="fas fa-check-circle" style="font-size: 36px; color: #27ae60; margin-bottom: 10px;"></i>
                <h3>No Seniors Pending Validation</h3>
                <p>All seniors have been validated.</p>
            </div>
        <?php else: ?>
            <!-- SINGLE TABLE FOR ALL SENIORS -->
            <table class="main-table">
                <thead>
                    <tr>
                        <th width="30">NO</th>
                        <th>FIRST NAME</th>
                        <th>MIDDLE NAME</th>
                        <th>LAST NAME</th>
                        <th width="70">BIRTHDAY</th>
                        <th>ADDRESS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    foreach ($processedSeniors as $senior):
                        // Format birthday as "04-Apr-65"
                        $birthday = 'N/A';
                        if (!empty($senior['birth_date']) && $senior['birth_date'] != '0000-00-00') {
                            try {
                                $birthDate = new DateTime($senior['birth_date']);
                                $birthday = $birthDate->format('d-M-y');
                            } catch (Exception $e) {
                                $birthday = 'Invalid Date';
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($senior['first_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($senior['middle_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($senior['last_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($birthday); ?></td>
                            <td><?php echo htmlspecialchars($senior['display_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- <div class="signature-section no-print">
                <div class="signature-row">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-name">MSWD OFFICER</div>
                        <div class="signature-title">Municipal Social Welfare & Development</div>
                    </div>

                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-name">MUNICIPAL MAYOR</div>
                        <div class="signature-title">Municipality of Paluan</div>
                    </div>
                </div>
            </div> -->
        <?php endif; ?>

        <!-- <div class="footer">
            <p>MSWD Paluan Senior Citizen Management System | Generated on: <?php echo date('F j, Y h:i A'); ?></p>
            <p>Total Records: <?php echo $totalSeniors; ?> | Page 1 of 1</p>
        </div> -->
    </div>

    <script>
        // Handle "Back to Active List" button click
        document.getElementById('backToActiveList').addEventListener('click', function() {
            // First, close this print preview window
            window.close();
            
            // Navigate back to activelist.php in the parent/opener window
            // Check if this window was opened by another window
            if (window.opener && !window.opener.closed) {
                // Refresh the parent window's activelist.php page
                window.opener.location.href = 'staff_activelist.php?session_context=<?php echo $ctx; ?>';
            } else {
                // If no opener exists, try to redirect in the current window
                window.location.href = 'staff_activelist.php?session_context=<?php echo $ctx; ?>';
            }
        });

        // Auto-print option
        window.addEventListener('load', function() {
            // Uncomment next line for auto-print
            // setTimeout(function() { window.print(); }, 1000);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P or Cmd+P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            // Escape key to go back
            if (e.key === 'Escape') {
                document.getElementById('backToActiveList').click();
            }
        });

        // Handle window close event
        window.addEventListener('beforeunload', function() {
            // Refresh the parent window if it exists
            if (window.opener && !window.opener.closed) {
                window.opener.location.reload();
            }
        });
    </script>
</body>

</html>