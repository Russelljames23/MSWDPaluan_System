<?php
session_start();
require_once __DIR__ . '/login/admin_header.php';
require_once __DIR__ . '/helpers/sms_helper.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Validate required fields
$required = ['phone_number', 'message'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$phoneNumber = trim($input['phone_number']);
$message = trim($input['message']);
$userId = isset($input['user_id']) ? intval($input['user_id']) : ($_SESSION['user_id'] ?? 0);
$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;

// Get SMS settings
$smsSettings = getSMSSettings();

// Initialize SMS Gateway
$smsGateway = new SMSGateway($smsSettings);

// Send SMS
$result = $smsGateway->send($phoneNumber, $message);

// Log to birthday_sms_logs if available
try {
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    if (!$conn->connect_error) {
        // Create birthday_sms_logs table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS birthday_sms_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            applicant_id INT NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(50) NOT NULL,
            sms_type VARCHAR(50) DEFAULT 'birthday_greeting',
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_applicant_id (applicant_id),
            INDEX idx_created_at (created_at),
            INDEX idx_status (status)
        )";
        $conn->query($createTable);

        // Insert log
        $stmt = $conn->prepare("INSERT INTO birthday_sms_logs (applicant_id, phone_number, message, status, user_id) VALUES (?, ?, ?, ?, ?)");
        $status = isset($result['success']) && $result['success'] ? 'sent' : 'failed';
        $stmt->bind_param("isssi", $applicantId, $phoneNumber, $message, $status, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
} catch (Exception $e) {
    // Silently fail if logging fails
    error_log("Failed to log SMS: " . $e->getMessage());
}

echo json_encode($result);
