<?php
// quick_sms_test.php - Super simple SMS test
ob_start(); // Buffer all output

header('Content-Type: application/json');

// Simple database check
try {
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    
    if ($conn->connect_error) {
        throw new Exception("Database: " . $conn->connect_error);
    }
    
    $result = $conn->query("SELECT api_key, demo_mode, is_active FROM sms_settings LIMIT 1");
    
    if (!$result || $result->num_rows === 0) {
        $settings = [
            'api_key' => '',
            'demo_mode' => 0,
            'is_active' => 1
        ];
    } else {
        $settings = $result->fetch_assoc();
    }
    
    $conn->close();
    
    $response = [
        'success' => true,
        'mode' => $settings['demo_mode'] ? 'DEMO' : 'REAL',
        'demo_mode' => (bool)$settings['demo_mode'],
        'is_active' => (bool)$settings['is_active'],
        'has_api_key' => !empty($settings['api_key']),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>