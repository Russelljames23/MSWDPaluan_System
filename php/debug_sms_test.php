<?php
// debug_sms_test.php - Fixed version
ob_start(); // Start output buffering

header('Content-Type: application/json');

// Test if we can include the files
$files_to_check = [
    __DIR__ . '/login/admin_header.php',
    __DIR__ . '/helpers/sms_helper.php'
];

$errors = [];

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        $errors[] = "File not found: " . basename($file);
    }
}

if (!empty($errors)) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'File errors',
        'errors' => $errors
    ]);
    exit;
}

// Now include the files
try {
    require_once __DIR__ . '/login/admin_header.php';
    require_once __DIR__ . '/helpers/sms_helper.php';
    
    // Test SMS Gateway
    $settings = getSMSSettings();
    $gateway = new SMSGateway($settings);
    
    ob_end_clean(); // Clear any output before JSON
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'settings' => [
            'api_key_set' => !empty($settings['api_key']),
            'api_key_preview' => !empty($settings['api_key']) ? substr($settings['api_key'], 0, 8) . '...' : 'NOT SET',
            'demo_mode' => $settings['demo_mode'] ?? false,
            'is_active' => $settings['is_active'] ?? false,
            'demo_mode_raw' => $settings['demo_mode'] ?? 'not set',
            'is_active_raw' => $settings['is_active'] ?? 'not set'
        ],
        'gateway_initialized' => true,
        'test_message' => 'SMS helper is working correctly'
    ]);
    
} catch (Exception $e) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Exception in SMS helper: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>