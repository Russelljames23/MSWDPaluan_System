<?php
// send_sms_test.php - Updated for real API mode
session_start();
require_once __DIR__ . '/login/admin_header.php';
require_once __DIR__ . '/helpers/sms_helper.php';

// Set headers FIRST - prevent any output before this
header('Content-Type: application/json');

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// If no JSON input, check POST
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

// Check if this is a test
$is_test = isset($input['is_test']) ? $input['is_test'] : false;

// Get SMS settings
$smsSettings = getSMSSettings();

// Log the request for debugging
error_log("SMS Test Request: " . json_encode([
    'phone' => $input['phone_number'] ?? 'not set',
    'is_test' => $is_test,
    'demo_mode' => $smsSettings['demo_mode'] ?? false,
    'has_api_key' => !empty($smsSettings['api_key'])
]));

// If it's a test request, always return success (simulated)
if ($is_test) {
    $response = [
        'success' => true,
        'message' => 'Test SMS would be sent via Semaphore API',
        'status' => 'test_approved',
        'demo_mode' => $smsSettings['demo_mode'] ?? false,
        'is_test' => true,
        'api_key_set' => !empty($smsSettings['api_key']),
        'is_active' => $smsSettings['is_active'] ?? false,
        'note' => 'In real mode, SMS would be sent to: ' . ($input['phone_number'] ?? 'N/A')
    ];

    echo json_encode($response);
    exit;
}

// If we get here, it's a real SMS send request
if (!isset($input['phone_number']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing phone_number or message'
    ]);
    exit;
}

$phoneNumber = trim($input['phone_number']);
$message = trim($input['message']);

// Validate
if (empty($phoneNumber) || empty($message)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Phone number and message cannot be empty'
    ]);
    exit;
}

// Initialize SMS Gateway
$smsGateway = new SMSGateway($smsSettings);

// Send SMS
try {
    $result = $smsGateway->send($phoneNumber, $message);
    echo json_encode($result);
} catch (Exception $e) {
    // Handle any exceptions
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'error_type' => 'exception'
    ]);
}
