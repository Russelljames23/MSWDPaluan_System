<?php
// check_sms_mode.php - Check SMS mode without session requirements
header('Content-Type: application/json');

// Don't require session for this simple check
define('BYPASS_SESSION', true);

// Check if SMS helper exists
$sms_helper_path = __DIR__ . '/helpers/sms_helper.php';
if (!file_exists($sms_helper_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'SMS helper file not found',
        'path' => $sms_helper_path
    ]);
    exit;
}

// Get database settings directly (bypass SMS helper if needed)
try {
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    
    if ($conn->connect_error) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'error' => $conn->connect_error
        ]);
        exit;
    }
    
    // Get SMS settings from database
    $result = $conn->query("SELECT api_key, demo_mode, is_active FROM sms_settings LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'api_key_set' => !empty($settings['api_key']),
            'demo_mode' => (bool)$settings['demo_mode'],
            'is_active' => (bool)$settings['is_active'],
            'mode' => $settings['demo_mode'] ? 'DEMO' : 'REAL',
            'note' => $settings['demo_mode'] ? 'SMS logged only' : 'Real SMS via Semaphore'
        ]);
    } else {
        $conn->close();
        echo json_encode([
            'success' => false,
            'message' => 'No SMS settings found in database'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>