<?php
// test_json.php - Simple JSON test
header('Content-Type: application/json');

// Test if we can include the files
$files_to_check = [
    __DIR__ . '/login/admin_header.php',
    __DIR__ . '/helpers/sms_helper.php'
];

$missing_files = [];
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        $missing_files[] = basename($file);
    }
}

if (!empty($missing_files)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing files: ' . implode(', ', $missing_files)
    ]);
    exit;
}

// Try to include the files
try {
    require_once __DIR__ . '/login/admin_header.php';
    require_once __DIR__ . '/helpers/sms_helper.php';
    
    // If we get here, files loaded successfully
    echo json_encode([
        'success' => true,
        'message' => 'Files loaded successfully',
        'session_active' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? 'not set'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading files: ' . $e->getMessage()
    ]);
}
?>