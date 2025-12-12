<?php
// report_backend.php - Simplified version without database
header('Content-Type: application/json');

// No database required - return static years
$action = $_GET['action'] ?? '';

if ($action === 'get_date_ranges') {
    // Generate last 10 years
    $currentYear = date('Y');
    $years = [];
    for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
        $years[] = $i;
    }
    
    // All months
    $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    
    echo json_encode([
        'success' => true,
        'years' => $years,
        'months' => $months,
        'message' => 'Using static date ranges'
    ]);
    
} elseif ($action === 'get_senior_counts') {
    // Sample data - you can replace this with actual database queries
    $year = isset($_GET['year']) ? intval($_GET['year']) : null;
    $month = isset($_GET['month']) ? intval($_GET['month']) : null;
    
    // Sample data structure
    $data = [
        ['barangay' => 'I - Mapalad', 'male' => 72, 'female' => 60, 'total' => 132],
        ['barangay' => 'II - Handang Tumulong', 'male' => 11, 'female' => 5, 'total' => 16],
        ['barangay' => 'III - Silahis ng Pag-asa', 'male' => 23, 'female' => 23, 'total' => 46],
        ['barangay' => 'IV - Pag-asa ng Bayan', 'male' => 109, 'female' => 100, 'total' => 209],
        ['barangay' => 'V - Bagong Silang', 'male' => 38, 'female' => 40, 'total' => 78],
        ['barangay' => 'VI - San Jose', 'male' => 35, 'female' => 50, 'total' => 85],
        ['barangay' => 'VII - Lumang Bayan', 'male' => 78, 'female' => 109, 'total' => 187],
        ['barangay' => 'VIII - Marikit', 'male' => 73, 'female' => 87, 'total' => 160],
        ['barangay' => 'IX - Tubili', 'male' => 15, 'female' => 28, 'total' => 43],
        ['barangay' => 'X - Alipaoy', 'male' => 23, 'female' => 54, 'total' => 77],
        ['barangay' => 'XI - Harrison', 'male' => 30, 'female' => 47, 'total' => 77],
        ['barangay' => 'XII - Mananao', 'male' => 104, 'female' => 93, 'total' => 197],
    ];
    
    // Calculate totals
    $maleTotal = array_sum(array_column($data, 'male'));
    $femaleTotal = array_sum(array_column($data, 'female'));
    $grandTotal = array_sum(array_column($data, 'total'));
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'totals' => [
            'male' => $maleTotal,
            'female' => $femaleTotal,
            'total' => $grandTotal
        ],
        'filters' => [
            'year' => $year,
            'month' => $month
        ]
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?>