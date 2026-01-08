<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../php/login/admin_header.php";
require_once "../../php/helpers/sms_helper.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Emergency fallback test
if (isset($_GET['direct_test'])) {
    $apiKey = '11203b3c9a4bc430dd3a1b181ece8b6c';
    $testNumber = '09272873751';
    $testMessage = 'Direct test from MSWD System';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $apiKey,
        'number' => '639272873751',
        'message' => $testMessage,
        'sender_name' => 'SEMAPHORE'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<pre>";
    echo "Direct API Test Result:\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    echo "</pre>";
    exit();
}
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Get SMS settings - USE THE FUNCTION FROM SMS_HELPER.PHP
$smsSettings = getSMSSettings();

// Also update the save_settings handler to fix sender name
if (isset($_POST['save_settings'])) {
    // Save SMS settings
    $settings = [
        'provider' => $_POST['provider'] ?? 'semaphore',
        'api_key' => trim($_POST['api_key'] ?? ''),
        'sender_id' => 'SEMAPHORE', // FIXED: Always use SEMAPHORE
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'demo_mode' => isset($_POST['demo_mode']) ? 1 : 0
    ];

    // Save settings - USE FUNCTION FROM SMS_HELPER.PHP
    if (saveSMSSettings($settings)) {
        $_SESSION['success'] = "SMS settings saved successfully!";
        $smsSettings = getSMSSettings(); // Refresh
    } else {
        $_SESSION['error'] = "Failed to save settings.";
    }

    header("Location: sms.php?session_context=" . $ctx);
    exit();
}

// Quick test - add this at the VERY TOP
if (isset($_GET['test'])) {
    // Get SMS settings first
    $testSettings = getSMSSettings();
    $testNumber = '09272873751'; // This should be 11 digits (09 + 9 digits)
    $smsGateway = new SMSGateway($testSettings);

    echo "<pre>";
    echo "Testing with: $testNumber\n";
    echo "Clean: " . preg_replace('/[^0-9+]/', '', $testNumber) . "\n";
    echo "Valid: " . ($smsGateway->validatePhoneNumber($testNumber) ? 'YES' : 'NO') . "\n";
    echo "Formatted: " . $smsGateway->formatNumberForSemaphore($testNumber) . "\n";

    // Test the validation directly
    $formatted = $smsGateway->formatNumberForSemaphore($testNumber);
    echo "Valid International: " . ($smsGateway->validateInternationalNumber($formatted) ? 'YES' : 'NO') . "\n";

    // Test actual sending
    $result = $smsGateway->send($testNumber, 'Test message');
    echo "\nSend Result:\n";
    print_r($result);
    echo "</pre>";
    exit();
}

// Initialize variables with defaults
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$smsWarning = $_SESSION['sms_warning'] ?? '';
$templates = [];
$systemStatus = null;

// Store messages in temporary variables
$tempSuccess = $success;
$tempError = $error;
$tempSmsWarning = $smsWarning;

// Clear session messages
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['sms_warning']);

// Database connection using mysqli (consistent with sms_helper.php)
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CREATE REQUIRED TABLES IF THEY DON'T EXIST
createRequiredTables($conn);

// Fetch current user data
$user_id = $_SESSION['user_id'] ?? 0;
$user_data = [];

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}

// Prepare full name
$full_name = '';
if (!empty($user_data['firstname']) && !empty($user_data['lastname'])) {
    $full_name = $user_data['firstname'] . ' ' . $user_data['lastname'];
    if (!empty($user_data['middlename'])) {
        $full_name = $user_data['firstname'] . ' ' . $user_data['middlename'] . ' ' . $user_data['lastname'];
    }
}

// Get profile photo URL
$profile_photo_url = '';
if (!empty($user_data['profile_photo'])) {
    $profile_photo_url = '../../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar if no profile photo
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name ?: 'User') . '&background=3b82f6&color=fff&size=128';
}

// Get message templates
$templates = [];
$template_query = "SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY created_at DESC";
$template_result = $conn->query($template_query);
if ($template_result && $template_result->num_rows > 0) {
    while ($row = $template_result->fetch_assoc()) {
        $templates[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // This is handled at the top already
    }

    if (isset($_POST['test_connection'])) {
        // Test API connection
        $smsGateway = new SMSGateway($smsSettings);
        $testResult = $smsGateway->testConnection();

        if ($testResult['success']) {
            $_SESSION['success'] = "✅ " . $testResult['message'] . "<br>" . $testResult['details'];
        } else {
            $_SESSION['error'] = "❌ " . $testResult['message'] . "<br>" . $testResult['details'];
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['send_sms'])) {
        // Send SMS
        $message = trim($_POST['broadcast_message'] ?? '');
        $recipientType = $_POST['recipient_type'] ?? 'custom';
        $sms_type = $_POST['sms_type'] ?? 'broadcast';

        // Check if SMS is enabled
        if (!isset($smsSettings['is_active']) || !$smsSettings['is_active']) {
            $_SESSION['error'] = "SMS service is disabled. Please enable it in settings.";
            header("Location: sms.php?session_context=" . $ctx);
            exit();
        }

        if (empty($message)) {
            $_SESSION['error'] = "Please enter a message.";
            header("Location: sms.php?session_context=" . $ctx);
            exit();
        }

        // Get recipients
        $recipients = [];

        if ($recipientType === 'custom') {
            $customNumbers = $_POST['custom_numbers'] ?? '';
            $numbers = explode(',', $customNumbers);
            foreach ($numbers as $number) {
                $cleanNumber = trim($number);
                if (!empty($cleanNumber)) {
                    $recipients[] = $cleanNumber;
                }
            }
        } else {
            // Get from database
            $statusCondition = '';
            if ($recipientType === 'all_active') {
                $statusCondition = " AND status = 'Active'";
            }

            $query = "SELECT contact_no FROM applicants 
             WHERE contact_no IS NOT NULL 
             AND contact_no != '' 
             AND contact_no != '0' 
             AND LENGTH(contact_no) >= 10
             $statusCondition";

            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['contact_no'])) {
                        $recipients[] = $row['contact_no'];
                    }
                }
            }
        }

        if (empty($recipients)) {
            $_SESSION['error'] = "No recipients found.";
            header("Location: sms.php?session_context=" . $ctx);
            exit();
        }

        // Initialize SMS Gateway
        $smsGateway = new SMSGateway($smsSettings);

        // DEBUG: Log recipients before sending
        error_log("Recipients before sending: " . print_r($recipients, true));

        // Send SMS
        $result = $smsGateway->sendBulk($recipients, $message);

        // DEBUG: Log result
        error_log("SendBulk result: " . print_r($result, true));

        // Process results
        if (isset($result['error'])) {
            $_SESSION['error'] = $result['error'];
        } else {
            $total = $result['total'];
            $sent = $result['sent'];
            $failed = $result['failed'];
            $invalid = $result['invalid'];
            $demoMode = $result['demo_mode'] ?? false;

            if ($demoMode) {
                $_SESSION['success'] = "✅ DEMO MODE: Successfully logged {$sent} SMS messages!";
                $_SESSION['success'] .= "<br><small>No actual SMS were sent. Disable demo mode to send real messages.</small>";

                if ($invalid > 0) {
                    $_SESSION['sms_warning'] = "Note: {$invalid} phone numbers were invalid.";
                }
            } else {
                if ($sent > 0) {
                    $_SESSION['success'] = "✅ Successfully queued {$sent} SMS messages for delivery via Semaphore API!";

                    // Add debugging info
                    $_SESSION['success'] .= "<br><small>Check SMS logs for delivery status.</small>";

                    if ($failed > 0) {
                        $_SESSION['sms_warning'] = "Note: {$failed} messages failed to send.";
                    }

                    if ($invalid > 0) {
                        $_SESSION['sms_warning'] = ($smsWarning ?? '') . ($failed > 0 ? "<br>" : "") . "Note: {$invalid} phone numbers were invalid.";
                    }
                } else {
                    $_SESSION['error'] = "Failed to send any messages.";

                    if ($failed > 0) {
                        $_SESSION['error'] .= " {$failed} messages failed.";
                    }

                    if ($invalid > 0) {
                        $_SESSION['error'] .= " {$invalid} phone numbers were invalid.";
                    }

                    // Add debugging help
                    $_SESSION['error'] .= "<br><small>Please check:";
                    $_SESSION['error'] .= "<br>1. Your Semaphore API key is correct";
                    $_SESSION['error'] .= "<br>2. You have sufficient credits";
                    $_SESSION['error'] .= "<br>3. Phone numbers are valid (09XXXXXXXXX format)";
                    $_SESSION['error'] .= "</small>";
                }
            }
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['test_api'])) {
        // Test Semaphore API - USE SMSGateway class
        $testNumber = trim($_POST['test_number'] ?? '');

        if (empty($testNumber)) {
            $_SESSION['error'] = "Please enter a test phone number.";
        } else {
            // Initialize SMS Gateway with current settings
            $smsGateway = new SMSGateway($smsSettings);

            // Check if demo mode is on
            $demoMode = $smsSettings['demo_mode'] ?? false;

            // Debug log
            error_log("=== TEST API DEBUG ===");
            error_log("Test number entered: $testNumber");
            error_log("Clean number: " . preg_replace('/[^0-9+]/', '', $testNumber));
            error_log("Demo mode: " . ($demoMode ? 'YES' : 'NO'));

            // Use SMSGateway validation method
            $isValid = $smsGateway->validatePhoneNumber($testNumber);
            error_log("SMSGateway validation result: " . ($isValid ? 'VALID' : 'INVALID'));

            if (!$isValid) {
                $_SESSION['error'] = "Invalid phone number format. Use: 09XXXXXXXXX or +639XXXXXXXXX";
                $_SESSION['error'] .= "<br>You entered: " . htmlspecialchars($testNumber);

                // Add debug info
                $formatted = $smsGateway->formatNumberForSemaphore($testNumber);
                $_SESSION['error'] .= "<br>Formatted for API: " . htmlspecialchars($formatted);
            } elseif ($demoMode) {
                $_SESSION['success'] = "✅ DEMO MODE: Test SMS logged (no actual SMS sent).";
                $_SESSION['success'] .= "<br><small>Disable demo mode to send real test SMS.</small>";

                // Log to database in demo mode
                $log_query = "INSERT INTO sms_logs 
                (phone_number, message, status, carrier, sms_type, user_id, created_at) 
                VALUES (?, ?, 'demo_sent', 'semaphore', 'test', ?, NOW())";
                $stmt = $conn->prepare($log_query);
                $testMessage = 'Test SMS from MSWD System - If you receive this, SMS is working properly.';
                $stmt->bind_param("ssi", $testNumber, $testMessage, $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Send real test SMS using SMSGateway
                $testMessage = 'Test SMS from MSWD System - If you receive this, SMS is working properly.';

                error_log("Sending test SMS to: $testNumber");

                // Get formatted number for debug
                $formatted = $smsGateway->formatNumberForSemaphore($testNumber);
                error_log("Formatted number for API: $formatted");

                // Send the SMS
                $result = $smsGateway->send($testNumber, $testMessage);

                error_log("Send result: " . print_r($result, true));

                if (isset($result['success']) && $result['success']) {
                    if ($result['status'] === 'queued' || $result['status'] === 'sent') {
                        $_SESSION['success'] = "✅ Test SMS sent successfully! Check the recipient's phone.";
                        if (isset($result['message_id'])) {
                            $_SESSION['success'] .= "<br><small>Message ID: " . $result['message_id'] . "</small>";
                        }
                        if (isset($result['formatted_number'])) {
                            $_SESSION['success'] .= "<br><small>Formatted: " . $result['formatted_number'] . "</small>";
                        }
                    } elseif (isset($result['status']) && $result['status'] === 'demo_sent') {
                        $_SESSION['success'] = "✅ DEMO MODE: Test SMS logged (no actual SMS sent).";
                    }
                } else {
                    $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown error';
                    $_SESSION['error'] = "Failed to send test SMS: " . $errorMsg;

                    // Add debug info
                    $_SESSION['error'] .= "<br><small>Check API key and internet connection.</small>";
                    $_SESSION['error'] .= "<br><small>Formatted number: " . $formatted . "</small>";
                }
            }
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['save_template'])) {
        $templateName = trim($_POST['template_name'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($templateName) || empty($message)) {
            $_SESSION['error'] = "Please provide both template name and message.";
        } else {
            // Check if template exists
            $check_query = "SELECT id FROM sms_templates WHERE template_name = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $templateName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing
                $row = $result->fetch_assoc();
                $update_query = "UPDATE sms_templates SET message = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $message, $row['id']);
            } else {
                // Insert new
                $insert_query = "INSERT INTO sms_templates (template_name, message, is_active, created_at) VALUES (?, ?, 1, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ss", $templateName, $message);
            }

            if ($stmt->execute()) {
                $_SESSION['success'] = "Template saved successfully!";

                // Refresh templates
                $template_result = $conn->query($template_query);
                $templates = [];
                if ($template_result && $template_result->num_rows > 0) {
                    while ($row = $template_result->fetch_assoc()) {
                        $templates[] = $row;
                    }
                }
            } else {
                $_SESSION['error'] = "Failed to save template: " . $conn->error;
            }
            $stmt->close();
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }
}

// Get SMS logs
$sms_logs = [];
$logs_query = "SELECT sl.id, sl.phone_number as recipient, sl.message, sl.status, sl.carrier, 
                      sl.sms_type, sl.created_at, sl.response_data,
                      u.username, u.user_type, u.profile_photo as user_avatar
               FROM sms_logs sl 
               LEFT JOIN users u ON sl.user_id = u.id 
               WHERE sl.status IS NOT NULL
               ORDER BY sl.created_at DESC 
               LIMIT 50";
$logs_result = $conn->query($logs_query);
if ($logs_result && $logs_result->num_rows > 0) {
    while ($row = $logs_result->fetch_assoc()) {
        $sms_logs[] = $row;
    }
}

// Calculate system status
$apiConfigured = !empty($smsSettings['api_key'] ?? '');
$demoMode = $smsSettings['demo_mode'] ?? false;
$isActive = $smsSettings['is_active'] ?? false;

if (!$isActive) {
    $systemStatus = [
        'status' => 'Disabled',
        'message' => 'SMS service is disabled. Enable it to send messages.',
        'api_configured' => $apiConfigured,
        'demo_mode' => $demoMode
    ];
} elseif ($demoMode) {
    $systemStatus = [
        'status' => 'Demo Mode',
        'message' => 'SMS are being logged but not sent. Disable demo mode to send real messages.',
        'api_configured' => $apiConfigured,
        'demo_mode' => $demoMode
    ];
} elseif (!$apiConfigured) {
    $systemStatus = [
        'status' => 'Not Configured',
        'message' => 'Semaphore API key is not configured. Please enter your API key.',
        'api_configured' => false,
        'demo_mode' => $demoMode
    ];
} else {
    $systemStatus = [
        'status' => 'Operational',
        'message' => 'SMS service is ready to send messages via Semaphore API.',
        'api_configured' => true,
        'demo_mode' => false
    ];
}

// Function to get status badge class
function getSMSStatusClass($status)
{
    $status = strtolower($status);

    if ($status === 'sent' || $status === 'delivered' || $status === 'success' || $status === 'queued') {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } elseif ($status === 'failed' || $status === 'error' || $status === 'undelivered') {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } elseif ($status === 'pending' || $status === 'processing' || $status === 'demo_sent') {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    } elseif ($status === 'cancelled' || $status === 'expired') {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    } else {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    }
}

// Function to format SMS timestamp
function formatSMSTime($timestamp)
{
    if (!$timestamp) return 'N/A';

    $date = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->days === 0) {
        return 'Today ' . $date->format('H:i');
    } elseif ($interval->days === 1) {
        return 'Yesterday ' . $date->format('H:i');
    } elseif ($interval->days < 7) {
        return $date->format('D H:i');
    } else {
        return $date->format('M d, Y H:i');
    }
}

// Function to create required tables
function createRequiredTables($conn)
{
    // Create sms_settings table WITHOUT api_url
    $conn->query("CREATE TABLE IF NOT EXISTS sms_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(50) DEFAULT 'semaphore',
        api_key VARCHAR(255),
        sender_id VARCHAR(50) DEFAULT 'SEMAPHORE',
        is_active TINYINT(1) DEFAULT 1,
        demo_mode TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create sms_templates table
    $conn->query("CREATE TABLE IF NOT EXISTS sms_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_template_name (template_name)
    )");

    // Create sms_logs table
    $conn->query("CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(50) NOT NULL,
        carrier VARCHAR(50),
        sms_type VARCHAR(50) DEFAULT 'outgoing',
        user_id INT DEFAULT NULL,
        username VARCHAR(100) DEFAULT NULL,
        user_type VARCHAR(50) DEFAULT NULL,
        response_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_phone_number (phone_number),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_user_id (user_id)
    )");

    // Insert default SMS settings if not exists
    $result = $conn->query("SELECT id FROM sms_settings LIMIT 1");
    if ($result->num_rows == 0) {
        $conn->query("INSERT INTO sms_settings (provider, sender_id, is_active, demo_mode) 
                     VALUES ('semaphore', 'SEMAPHORE', 1, 1)");
    }

    // Insert default templates if not exists
    $result = $conn->query("SELECT id FROM sms_templates LIMIT 1");
    if ($result->num_rows == 0) {
        $defaultTemplates = [
            ['Appointment Reminder', 'Good day! This is MSWD Paluan reminding you of your scheduled appointment tomorrow. Please be on time.'],
            ['Benefits Announcement', 'Important Announcement from MSWD Paluan: Please check our office for the latest updates regarding benefits distribution.'],
            ['Follow-up Reminder', 'Hello! This is MSWD Paluan following up on your recent application. Please visit our office for updates.'],
            ['Holiday Announcement', 'MSWD Paluan Announcement: Our office will be closed on [Date] for [Holiday]. Normal operations will resume on [Date].']
        ];

        foreach ($defaultTemplates as $template) {
            $stmt = $conn->prepare("INSERT INTO sms_templates (template_name, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $template[0], $template[1]);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Settings - MSWD Paluan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

        * {
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* Smooth theme icon transitions */
        #nav-theme-light-icon,
        #nav-theme-dark-icon {
            transition: opacity 0.3s ease;
        }

        /* Sidebar container */
        .sidebar {
            position: relative;
            border-radius: 10px;
            height: 100%;
            width: 78px;
            background: #fff;
            transition: all 0.4s ease;
            z-index: 40;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .dark .sidebar {
            background: #1f2937;
            /* gray-800 */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar.open {
            width: 200px;
        }

        /* Logo section + toggle button */
        .sidebar .logo-details {
            height: 60px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #ddd;
        }

        .dark .sidebar .logo-details {
            border-bottom: 1px solid #374151;
            /* gray-700 */
        }

        .logo-details #btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .logo-details #btn svg {
            width: 24px;
            height: 24px;
            transition: transform 0.4s ease;
            flex-shrink: 0;
            opacity: 1;
            visibility: visible;
        }

        .sidebar.open .logo-details #btn svg {
            transform: rotate(180deg);
        }

        /* Navigation list */
        .nav-list {
            list-style: none;
            padding: 15px;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .nav-list li {
            position: relative;
            display: flex;
            width: 100%;
        }

        .nav-list li a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 35px;
            width: 100%;
            padding: 0 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .nav-list li a:hover {
            background: #e4e9f7;
        }

        .nav-list li button {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 35px;
            width: 100%;
            padding: 0 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .nav-list li button:hover {
            background: #e4e9f7;
        }

        .nav-list svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 1;
            visibility: visible;
            transition: all 0.3s ease;
        }

        /* Dark mode styles for nav list */
        .dark .nav-list li a,
        .dark .nav-list li button {
            background: #374151;
            /* gray-700 */
            border: 1px solid #4b5563;
            /* gray-600 */
            color: #e5e7eb;
            /* gray-200 */
        }

        .dark .nav-list li a:hover,
        .dark .nav-list li button:hover {
            background: #4b5563;
            /* gray-600 */
        }

        /* Hide link text when collapsed, keep icons */
        .links_name {
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 14px;
        }

        .sidebar.open .links_name {
            opacity: 1;
        }

        /* Tooltip styling */
        .tooltip {
            position: absolute;
            top: 50%;
            left: 100%;
            transform: translateY(-50%);
            margin-left: 10px;
            background: rgba(221, 221, 221, 0.95);
            color: #000;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 2px 8px rgba(138, 138, 138, 0.15);
            backdrop-filter: blur(4px);
            z-index: 200;
        }

        .dark .tooltip {
            background: rgba(55, 65, 81, 0.95);
            /* gray-700 */
            color: #e5e7eb;
            /* gray-200 */
        }

        /* Show tooltip when hovering over an item */
        .sidebar li:hover .tooltip {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        /* Hide tooltips when sidebar is expanded */
        .sidebar.open li .tooltip {
            display: none;
        }

        .dark .links_name {
            color: #d1d5db;
        }

        /* Active link styling */
        .nav-list li #sms.active-link {
            color: #1d4ed8;
            /* Tailwind blue-700 */
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #sms.active-link svg {
            color: #1d4ed8;
        }

        .dark .nav-list li #sms.active-link {
            color: #60a5fa;
            /* blue-400 */
            border-color: #3b82f6;
            /* blue-500 */
            background: #1e40af;
            /* blue-800 */
        }

        .dark .nav-list li #sms.active-link svg {
            color: #60a5fa;
            /* blue-400 */
        }
    </style>
    <style>
        /* Add these styles to your existing CSS */
        .sms-status-badge {
            transition: all 0.2s ease;
        }

        .sms-status-badge:hover {
            transform: scale(1.05);
        }

        .sms-status-sent {
            position: relative;
        }

        .sms-status-sent::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 9999px;
            opacity: 0.5;
            animation: pulse 2s infinite;
            z-index: -1;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.5;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        /* SMS message preview */
        .sms-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Responsive table adjustments */
        @media (max-width: 768px) {

            .sms-table th:nth-child(4),
            .sms-table td:nth-child(4) {
                display: none;
            }
        }

        @media (max-width: 640px) {

            .sms-table th:nth-child(2),
            .sms-table td:nth-child(2),
            .sms-table th:nth-child(7),
            .sms-table td:nth-child(7) {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
        <div class="flex flex-wrap justify-between items-center">
            <div class="flex justify-start items-center">
                <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                    aria-controls="drawer-navigation"
                    class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <svg aria-hidden="true" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Toggle sidebar</span>
                </button>
                <a href="#" class="flex items-center justify-between mr-4 ">
                    <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
                        class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                        alt="MSWD LOGO" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD
                        PALUAN</span>
                </a>
                <form action="#" method="GET" class="hidden md:block md:pl-2">
                    <label for="topbar-search" class="sr-only">Search</label>
                    <div class="relative md:w-64 md:w-96">
                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                                </path>
                            </svg>
                        </div>
                        <input type="text" name="email" id="topbar-search"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Search" />
                    </div>
                </form>
            </div>
            <!-- UserProfile -->
            <div class="flex items-center lg:order-2">
                <button type="button" data-drawer-toggle="drawer-navigation" aria-controls="drawer-navigation"
                    class="p-2 mr-1 text-gray-500 rounded-lg md:hidden hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                    <span class="sr-only">Toggle search</span>
                    <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                        </path>
                    </svg>
                </button>
                <button type="button"
                    class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full object-cover"
                        src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                        alt="user photo" />
                </button>
                <!-- Dropdown menu -->
                <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
                    id="dropdown">
                    <div class="py-3 px-4">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                            <?php
                            if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                                echo htmlspecialchars($_SESSION['fullname']);
                            } else if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                                echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
                            } else {
                                echo 'User';
                            }
                            ?>
                        </span>
                        <span class="block text-sm text-gray-900 truncate dark:text-white">
                            <?php
                            if (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
                                echo htmlspecialchars($_SESSION['user_type']);
                            } else if (isset($_SESSION['role_name']) && !empty($_SESSION['role_name'])) {
                                echo htmlspecialchars($_SESSION['role_name']);
                            } else {
                                echo 'User Type';
                            }
                            ?>
                        </span>
                    </div>
                    <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">

                        <li>
                            <a href="/MSWDPALUAN_SYSTEM-MAIN/php/login/logout.php"
                                class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Sign
                                out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside
        class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
        aria-label="Sidenav" id="drawer-navigation">
        <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
            <form action="#" method="GET" class="md:hidden mb-2">
                <label for="sidebar-search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                            viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z">
                            </path>
                        </svg>
                    </div>
                    <input type="text" name="search" id="sidebar-search"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        placeholder="Search" />
                </div>
            </form>
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
            <ul class="space-y-2">
                <li>
                    <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="currentColor"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">
                            <rect x="3" y="3" width="8" height="10" rx="1.5" />
                            <rect x="13" y="3" width="8" height="6" rx="1.5" />
                            <rect x="3" y="15" width="8" height="6" rx="1.5" />
                            <rect x="13" y="11" width="8" height="10" rx="1.5" />
                        </svg>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <g transform="translate(24,0) scale(-1,1)">
                                <path fill-rule="evenodd"
                                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
                                    clip-rule="evenodd" />
                            </g>
                        </svg>
                        <span class="ml-3">Register</span>
                    </a>
                </li>
                <li>
                    <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                        class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        <svg aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                d="M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5" />
                        </svg>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                        <svg aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages" aria-hidden="true"
                            class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                        <li>
                            <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Active
                                List</a>
                        </li>
                        <li>
                            <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                List</a>
                        </li>
                        <li>
                            <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                List</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 7V2.221a2 2 0 0 0-.5.365L3.586 6.5a2 2 0 0 0-.365.5H8Zm2 0V2h7a2 2 0 0 1 2 2v.126a5.087 5.087 0 0 0-4.74 1.368v.001l-6.642 6.642a3 3 0 0 0-.82 1.532l-.74 3.692a3 3 0 0 0 3.53 3.53l3.694-.738a3 3 0 0 0 1.532-.82L19 15.149V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                                clip-rule="evenodd" />
                            <path fill-rule="evenodd"
                                d="M17.447 8.08a1.087 1.087 0 0 1 1.187.238l.002.001a1.088 1.088 0 0 1 0 1.539l-.377.377-1.54-1.542.373-.374.002-.001c.1-.102.22-.182.353-.237Zm-2.143 2.027-4.644 4.644-.385 1.924 1.925-.385 4.644-4.642-1.54-1.54Zm2.56-4.11a3.087 3.087 0 0 0-2.187.909l-6.645 6.645a1 1 0 0 0-.274.51l-.739 3.693a1 1 0 0 0 1.177 1.176l3.693-.738a1 1 0 0 0 .51-.274l6.65-6.646a3.088 3.088 0 0 0-2.185-5.275Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Benefits</span>
                    </a>
                </li>
                <li>
                    <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.20.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75  hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3" />
                        </svg>
                        <span class="ml-3">Report</span>
                    </a>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a href="../archived.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 1 0 0 4h16a2 2 0 1 0 0-4H4Zm0 6h16v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8Zm10.707 5.707a1 1 0 0 0-1.414-1.414l-.293.293V12a1 1 0 1 0-2 0v2.586l-.293-.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l2-2Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Archived</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                        <svg aria-hidden="true"
                            class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <main class="p-4 md:ml-64 pt-20">
        <div class="flex flex-row justify-between">
            <!-- Sidebar -->
            <div class="sidebar open">
                <div class="logo-details">
                    <button type="button" class="border" id="btn">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M6 6h8m-8 4h12M6 14h8m-8 4h12" />
                        </svg>
                    </button>
                </div>
                <ul class="nav-list">
                    <li>
                        <a href="profile.php?session_context=<?php echo $ctx; ?>" id="profile" class="cursor-pointer">
                            <svg class="w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.20.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="links_name">My Profile</span>
                        </a>
                        <span class="tooltip">My Profile</span>
                    </li>
                    <li>
                        <button type="button" id="nav-theme-toggle"
                            class="flex items-center w-full px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg id="nav-theme-light-icon" class="w-4 h-4 mr-2 hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                            </svg>
                            <svg id="nav-theme-dark-icon" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                            </svg>
                            <span id="nav-theme-text">Dark Mode</span>
                        </button>
                    </li>

                    <li>
                        <a href="accounts.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                            <svg class="w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M9 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Zm8-1a1 1 0 0 1 1-1h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 0 1-1-1Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="links_name">Accounts</span>
                        </a>
                        <span class="tooltip">Accounts</span>
                    </li>
                    <li>
                        <a href="#" id="sms" class="cursor-pointer active-link">
                            <svg class="w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M5 5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H5Zm2.5 5.5a1 1 0 1 0 0 2h9a1 1 0 1 0 0-2h-9Zm0 3a1 1 0 1 0 0 2h5a1 1 0 1 0 0-2h-5Z"
                                    clip-rule="evenodd" />
                                <path d="M8.707 4.293A1 1 0 0 0 8 4H6a1 1 0 0 0-1 1v1.382a1 1 0 0 0 .553.894l2.618 1.309a1 1 0 0 0 .894 0L12.447 7.276A1 1 0 0 0 13 6.382V5a1 1 0 0 0-1-1h-2a1 1 0 0 0-.707.293Z" />
                            </svg>
                            <span class="links_name">SMS Settings</span>
                        </a>
                        <span class="tooltip">SMS Settings</span>
                    </li>
                    <li>
                        <a href="systemlogs.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                            <svg class="w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3M3.22302 14C4.13247 18.008 7.71683 21 12 21c4.9706 0 9-4.0294 9-9 0-4.97056-4.0294-9-9-9-3.72916 0-6.92858 2.26806-8.29409 5.5M7 9H3V5" />
                            </svg>
                            <span class="links_name">System Logs</span>
                        </a>
                        <span class="tooltip">System Logs</span>
                    </li>
                </ul>
            </div>

            <!-- SMS Settings Content -->
            <section id="smsSection" class="bg-gray-50 dark:bg-gray-900 w-full">
                <div class="w-full px-4">
                    <!-- Messages -->
                    <?php if ($tempSuccess): ?>
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                            <?php echo $tempSuccess; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tempError): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                            <?php echo $tempError; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tempSmsWarning): ?>
                        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200">
                            <?php echo $tempSmsWarning; ?>
                        </div>
                    <?php endif; ?>

                    <!-- System Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">SMS System Status</h2>
                            <span class="px-3 py-1 rounded-full text-sm font-medium 
                                <?php echo ($systemStatus['status'] ?? '') === 'Operational' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : (($systemStatus['status'] ?? '') === 'Demo Mode' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'); ?>">
                                <?php echo $systemStatus['status'] ?? 'Unknown'; ?>
                            </span>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-2"><?php echo $systemStatus['message'] ?? 'Unable to determine system status'; ?></p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">API Status</div>
                                <div class="<?php echo ($systemStatus['api_configured'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo ($systemStatus['api_configured'] ?? false) ? 'Configured' : 'Not Configured'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Demo Mode</div>
                                <div class="<?php echo ($systemStatus['demo_mode'] ?? false) ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'; ?>">
                                    <?php echo ($systemStatus['demo_mode'] ?? false) ? 'Enabled' : 'Disabled'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Service</div>
                                <div class="<?php echo ($systemStatus['is_active'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo ($systemStatus['is_active'] ?? false) ? 'Active' : 'Inactive'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Provider</div>
                                <div class="text-blue-600 dark:text-blue-400">Semaphore API</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- SMS Settings Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Semaphore API Settings</h2>
                            <form method="POST">
                                <div class="space-y-4">
                                    <!-- Service Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            SMS Service Status
                                        </label>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                                <?php echo (isset($smsSettings['is_active']) && $smsSettings['is_active']) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="is_active" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                Enable SMS Service
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Demo Mode -->
                                    <div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="demo_mode" name="demo_mode" value="1"
                                                <?php echo (isset($smsSettings['demo_mode']) && $smsSettings['demo_mode']) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="demo_mode" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                Demo Mode (Log only, don't send)
                                            </label>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enable to test without sending real SMS</p>
                                    </div>

                                    <!-- Provider -->
                                    <div>
                                        <input type="hidden" name="provider" value="semaphore">
                                    </div>

                                    <!-- API Key -->
                                    <div>
                                        <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Semaphore API Key
                                        </label>
                                        <input type="text" id="api_key" name="api_key"
                                            value="<?php echo htmlspecialchars($smsSettings['api_key'] ?? ''); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter your Semaphore API key">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Get your API key from <a href="https://semaphore.co" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">semaphore.co</a>
                                        </p>
                                        <?php if (empty($smsSettings['api_key'] ?? '')): ?>
                                            <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 text-xs rounded">
                                                💡 <strong>Note:</strong> Your Semaphore API key is: <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">11203b3c9a4bc430dd3a1b181ece8b6c</code>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Sender ID -->
                                    <div>
                                        <label for="sender_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Sender Name (Fixed)
                                        </label>
                                        <input type="text" id="sender_id" name="sender_id" readonly
                                            value="SEMAPHORE"
                                            class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                            title="Sender name is fixed to 'SEMAPHORE' for your Semaphore account">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Your Semaphore account uses 'SEMAPHORE' as sender name (auto-converts to 'MFDELIVERY').
                                        </p>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <button type="submit" name="save_settings"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            Save Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <!-- Add direct test button -->
                            <div class="mt-3">
                                <form method="GET">
                                    <input type="hidden" name="test" value="1">
                                    <button type="submit"
                                        class="text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900">
                                        Run Diagnostic Test
                                    </button>
                                </form>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Runs a complete diagnostic of SMS system</p>
                            </div>
                            <!-- Test API Form -->
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Test API Connection</h3>

                                <?php
                                // Add this test button
                                if (!empty($smsSettings['api_key'])): ?>
                                    <form method="POST" class="mb-3">
                                        <button type="submit" name="test_connection"
                                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                            Test API Connection
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="space-y-3">
                                    <div>
                                        <label for="test_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Test Phone Number
                                        </label>
                                        <input type="text" id="test_number" name="test_number"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="09123456789"
                                            value="">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter a valid Philippine mobile number (09XXXXXXXXX)</p>
                                    </div>
                                    <button type="submit" name="test_api"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                        Send Test SMS
                                    </button>
                                </form>
                            </div>
                        </div>
                        <!-- In the debug panel, add: -->
                        <p><a href="sms.php?direct_test=1&session_context=<?php echo $ctx; ?>" class="text-blue-600 dark:text-blue-400">Test direct API call</a></p>
                        <!-- Send SMS Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Send SMS</h2>
                            <form method="POST" id="sendSMSForm">
                                <div class="space-y-4">
                                    <!-- Recipient Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Send to
                                        </label>
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_custom" name="recipient_type" value="custom" checked
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_custom" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    Custom Numbers
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_all" name="recipient_type" value="all"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_all" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    All Applicants
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_active" name="recipient_type" value="all_active"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_active" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    Active Applicants Only
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Custom Numbers -->
                                    <div id="customNumbersSection">
                                        <label for="custom_numbers" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Phone Numbers (comma-separated)
                                        </label>
                                        <textarea id="custom_numbers" name="custom_numbers" rows="2"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="09123456789, 09234567890"></textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple numbers with commas</p>
                                    </div>

                                    <!-- Message -->
                                    <div>
                                        <label for="broadcast_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Message
                                        </label>
                                        <textarea id="broadcast_message" name="broadcast_message" rows="3"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter your message here..."></textarea>
                                        <div class="mt-1 flex justify-between">
                                            <span id="charCount" class="text-xs text-gray-500 dark:text-gray-400">0/160</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">SMS Type:
                                                <select name="sms_type" class="text-xs border-none bg-transparent">
                                                    <option value="broadcast">Broadcast</option>
                                                    <option value="announcement">Announcement</option>
                                                    <option value="reminder">Reminder</option>
                                                    <option value="notification">Notification</option>
                                                </select>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Send Button -->
                                    <div class="pt-4">
                                        <button type="submit" name="send_sms" id="sendSMSBtn"
                                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            Send SMS Messages
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Quick Templates -->
                            <div class="mt-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Quick Templates</h3>
                                <div class="grid grid-cols-1 gap-2">
                                    <?php if (!empty($templates)): ?>
                                        <?php foreach ($templates as $template): ?>
                                            <button type="button" onclick="useTemplate('<?php echo addslashes($template['message']); ?>')"
                                                class="text-left p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                                <div class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($template['template_name']); ?></div>
                                                <div class="text-sm text-gray-600 dark:text-gray-300 truncate">
                                                    <?php echo htmlspecialchars(substr($template['message'], 0, 60)); ?>
                                                    <?php if (strlen($template['message']) > 60): ?>...<?php endif; ?>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No templates saved yet. Create some above!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Templates Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Message Templates</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Save Template Form -->
                            <div>
                                <form method="POST">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="template_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Template Name
                                            </label>
                                            <input type="text" id="template_name" name="template_name"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="Enter template name">
                                        </div>
                                        <div>
                                            <label for="template_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Template Message
                                            </label>
                                            <textarea id="template_message" name="message" rows="4"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="Enter template message..."></textarea>
                                        </div>
                                        <button type="submit" name="save_template"
                                            class="w-full text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900">
                                            Save Template
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Saved Templates List -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Saved Templates</h3>
                                <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                                    <?php if (empty($templates)): ?>
                                        <div class="p-4 text-center bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <p class="text-gray-500 dark:text-gray-400">No templates saved yet.</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Default templates have been created for you.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <div class="flex items-center mb-1">
                                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                                <?php echo htmlspecialchars($template['template_name']); ?>
                                                            </h4>
                                                            <?php if (!$template['is_active']): ?>
                                                                <span class="ml-2 text-xs px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded">Inactive</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                                            <?php echo htmlspecialchars(substr($template['message'], 0, 100)); ?>
                                                            <?php if (strlen($template['message']) > 100): ?>...<?php endif; ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Created: <?php echo date('M d, Y', strtotime($template['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-2 ml-4">
                                                        <button type="button"
                                                            onclick="useTemplate('<?php echo addslashes($template['message']); ?>')"
                                                            class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 rounded-lg transition">
                                                            Use
                                                        </button>
                                                        <button type="button"
                                                            onclick="useTemplateAndSend('<?php echo addslashes($template['message']); ?>')"
                                                            class="px-3 py-1.5 text-sm bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 rounded-lg transition">
                                                            Use & Send
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS Logs Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="border-b dark:border-gray-700 p-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Logs</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Recent SMS message history</p>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Showing last <?php echo count($sms_logs); ?> messages
                                </div>
                            </div>
                        </div>

                        <?php if (empty($sms_logs)): ?>
                            <div class="p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No SMS logs found</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Send your first SMS message to see logs here
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Recipient</th>
                                            <th scope="col" class="px-6 py-3">Message</th>
                                            <th scope="col" class="px-6 py-3">Status</th>
                                            <th scope="col" class="px-6 py-3">Type</th>
                                            <th scope="col" class="px-6 py-3">Time</th>
                                            <th scope="col" class="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sms_logs as $log): ?>
                                            <?php
                                            $statusClass = getSMSStatusClass($log['status']);
                                            $pulseClass = (strtolower($log['status']) === 'sent' || strtolower($log['status']) === 'queued') ? 'sms-status-sent' : '';
                                            ?>
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                        <?php echo htmlspecialchars($log['recipient'] ?? 'N/A'); ?>
                                                    </div>
                                                    <?php if ($log['carrier'] && $log['carrier'] !== 'demo'): ?>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            <?php echo htmlspecialchars(ucfirst($log['carrier'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($log['message'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars(substr($log['message'] ?? '', 0, 50)); ?>
                                                        <?php if (strlen($log['message'] ?? '') > 50): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusClass . ' ' . $pulseClass; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($log['status'] ?? 'unknown')); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                        <?php echo ($log['sms_type'] ?? 'broadcast') === 'test' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($log['sms_type'] ?? 'broadcast')); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex flex-col">
                                                        <span class="text-gray-900 dark:text-white">
                                                            <?php echo formatSMSTime($log['created_at'] ?? ''); ?>
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            <?php echo isset($log['created_at']) ? date('M d, Y', strtotime($log['created_at'])) : 'N/A'; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <button type="button" onclick="viewSMSDetails('<?php echo $log['id']; ?>')"
                                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <!-- Debug Panel (hidden by default) -->
    <details class="mt-6 bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            Debug Information
        </summary>
        <div class="mt-2 text-xs">
            <pre class="bg-gray-200 dark:bg-gray-900 p-3 rounded overflow-auto max-h-60"><?php
                                                                                            echo "API Key: " . (empty($smsSettings['api_key']) ? 'Not set' : substr($smsSettings['api_key'], 0, 10) . '...') . "\n";
                                                                                            echo "Sender ID: " . ($smsSettings['sender_id'] ?? 'MSWDPALUAN') . "\n";
                                                                                            echo "Demo Mode: " . (isset($smsSettings['demo_mode']) && $smsSettings['demo_mode'] ? 'Yes' : 'No') . "\n";
                                                                                            echo "Active: " . (isset($smsSettings['is_active']) && $smsSettings['is_active'] ? 'Yes' : 'No') . "\n";
                                                                                            echo "Provider: Semaphore\n";
                                                                                            echo "Templates: " . count($templates) . "\n";
                                                                                            echo "Logs: " . count($sms_logs) . "\n";
                                                                                            ?></pre>
        </div>
    </details>
    <!-- SMS Details Modal -->
    <div id="smsDetailsModal" class="hidden fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]" id="smsModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        // Simplified phone validation
        function validatePhoneNumber(number) {
            const clean = number.replace(/[^0-9+]/g, '');
            // Accept: 09XXXXXXXXX, 9XXXXXXXXX, 63XXXXXXXXX, +639XXXXXXXXX
            return /^(09|9|63|\+63)\d{9,12}$/.test(clean);
        }

        // Real-time validation
        document.getElementById('test_number')?.addEventListener('input', function(e) {
            const number = this.value.trim();
            if (!number) {
                this.classList.remove('border-red-500', 'border-green-500');
                return;
            }

            // Simple validation - just check if it looks like a Philippine number
            const clean = number.replace(/[^0-9]/g, '');
            const isValid = (clean.length >= 10 && clean.length <= 13) &&
                (clean.startsWith('9') || clean.startsWith('09') || clean.startsWith('63'));

            if (isValid) {
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            } else {
                this.classList.remove('border-green-500');
                this.classList.add('border-red-500');
            }
        });

        document.getElementById('custom_numbers')?.addEventListener('input', function(e) {
            const numbers = this.value.split(',').map(num => num.trim()).filter(num => num);
            let validCount = 0;

            numbers.forEach(num => {
                if (validatePhoneNumber(num)) {
                    validCount++;
                }
            });

            // Update validation display
            const existing = this.parentNode.querySelector('.number-validation');
            if (existing) existing.remove();

            if (numbers.length > 0) {
                const validCountEl = document.createElement('div');
                validCountEl.className = 'number-validation text-xs mt-1';
                validCountEl.textContent = `${validCount} of ${numbers.length} numbers are valid`;
                validCountEl.style.color = validCount === numbers.length ? 'green' : (validCount > 0 ? 'orange' : 'red');
                this.parentNode.appendChild(validCountEl);
            }
        });

        // Update the send button click handler
        document.getElementById('sendSMSBtn')?.addEventListener('click', function(e) {
            const message = document.getElementById('broadcast_message').value;
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;

            if (!message.trim()) {
                e.preventDefault();
                alert('Please enter a message');
                return;
            }

            if (recipientType === 'custom') {
                const numbersInput = document.getElementById('custom_numbers');
                const numbers = numbersInput.value.split(',').map(num => num.trim()).filter(num => num);

                if (numbers.length === 0) {
                    e.preventDefault();
                    alert('Please enter at least one phone number');
                    return;
                }

                // Validate each number
                const invalidNumbers = [];
                numbers.forEach(num => {
                    if (!validatePhoneNumber(num)) {
                        invalidNumbers.push(num);
                    }
                });

                if (invalidNumbers.length > 0) {
                    e.preventDefault();
                    alert(`The following phone numbers appear invalid:\n\n${invalidNumbers.join('\n')}\n\nPlease use format: 09XXXXXXXXX`);
                    return;
                }
            }

            const demoMode = <?php echo isset($smsSettings['demo_mode']) && $smsSettings['demo_mode'] ? 'true' : 'false'; ?>;
            const totalRecipients = recipientType === 'custom' ?
                document.getElementById('custom_numbers').value.split(',').filter(n => n.trim()).length :
                'multiple';

            if (demoMode) {
                if (!confirm(`⚠️ DEMO MODE ENABLED\n\n${totalRecipients} SMS will be logged but NOT sent.\n\nClick OK to continue in demo mode.`)) {
                    e.preventDefault();
                }
            } else {
                if (!confirm(`Are you sure you want to send ${totalRecipients} SMS?\n\nThis will use your Semaphore credits.`)) {
                    e.preventDefault();
                }
            }
        });
        // Character counter
        const messageInput = document.getElementById('broadcast_message');
        const charCount = document.getElementById('charCount');

        if (messageInput && charCount) {
            messageInput.addEventListener('input', () => {
                const length = messageInput.value.length;
                charCount.textContent = `${length}/160`;

                if (length > 160) {
                    charCount.classList.add('text-red-600', 'font-bold');
                    charCount.classList.remove('text-yellow-600');
                } else if (length > 140) {
                    charCount.classList.add('text-yellow-600');
                    charCount.classList.remove('text-red-600', 'font-bold');
                } else {
                    charCount.classList.remove('text-red-600', 'text-yellow-600', 'font-bold');
                    charCount.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            });
            messageInput.dispatchEvent(new Event('input'));
        }

        // Toggle custom numbers input
        document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customNumbersSection = document.getElementById('customNumbersSection');
                if (customNumbersSection) {
                    customNumbersSection.style.display = this.value === 'custom' ? 'block' : 'none';
                }
            });
        });

        // Use template function
        function useTemplate(message) {
            const messageInput = document.getElementById('broadcast_message');
            if (messageInput) {
                messageInput.value = message;
                messageInput.dispatchEvent(new Event('input'));
                messageInput.focus();
            }
        }

        // Use template and send immediately
        function useTemplateAndSend(message) {
            useTemplate(message);

            // Set recipient to custom and show numbers input
            document.getElementById('recipient_custom').checked = true;
            document.getElementById('customNumbersSection').style.display = 'block';
            document.getElementById('custom_numbers').focus();

            // Show confirmation
            alert('Template loaded. Please enter phone numbers and click "Send SMS Messages".');
        }

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-yellow-100').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);

        // SMS Details Modal
        function viewSMSDetails(smsId) {
            const modalContent = document.getElementById('smsModalContent');
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading SMS details...</p>
                    </div>
                </div>
            `;

            document.getElementById('smsDetailsModal').classList.remove('hidden');

            // Simple details display
            setTimeout(() => {
                modalContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">Note: Full SMS details feature is being implemented.</p>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">The SMS system is working! All messages are being logged to the database.</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">SMS ID: <span class="font-mono">${smsId}</span></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Check the SMS logs table above for message details.</p>
                        </div>
                    </div>
                `;
            }, 500);
        }

        // Close modal
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('smsDetailsModal').classList.add('hidden');
        });

        // Close modal when clicking outside
        document.getElementById('smsDetailsModal').addEventListener('click', function(e) {
            if (e.target.id === 'smsDetailsModal') {
                document.getElementById('smsDetailsModal').classList.add('hidden');
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('smsDetailsModal').classList.add('hidden');
            }
        });

        // Send SMS confirmation
        document.getElementById('sendSMSBtn').addEventListener('click', function(e) {
            const message = document.getElementById('broadcast_message').value;
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;

            if (recipientType === 'custom') {
                const numbers = document.getElementById('custom_numbers').value;
                if (!numbers.trim() && message.trim()) {
                    e.preventDefault();
                    alert('Please enter phone numbers for custom recipients.');
                    return;
                }
            }

            // Show confirmation for demo mode
            const demoMode = <?php echo isset($smsSettings['demo_mode']) && $smsSettings['demo_mode'] ? 'true' : 'false'; ?>;
            if (demoMode && message.trim()) {
                if (!confirm('⚠️ DEMO MODE ENABLED\nNo actual SMS will be sent. Messages will only be logged.\n\nContinue?')) {
                    e.preventDefault();
                }
            } else if (message.trim()) {
                // Ask for confirmation when sending real SMS
                if (!confirm('Are you sure you want to send this SMS? This will use your Semaphore credits.')) {
                    e.preventDefault();
                }
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize recipient type
            document.querySelector('input[name="recipient_type"][value="custom"]').dispatchEvent(new Event('change'));

            // Auto-fill API key if empty
            const apiKeyField = document.getElementById('api_key');
            if (apiKeyField && !apiKeyField.value) {
                // Pre-fill with the provided API key
                apiKeyField.value = '11203b3c9a4bc430dd3a1b181ece8b6c';
            }
        });

        // Add API key placeholder with the actual key
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill API key if empty
            const apiKeyField = document.getElementById('api_key');
            if (apiKeyField && !apiKeyField.value) {
                apiKeyField.value = '11203b3c9a4bc430dd3a1b181ece8b6c';
                apiKeyField.placeholder = 'Your Semaphore API key is pre-filled';
            }

            // Enhanced phone number validation
            document.getElementById('sendSMSBtn').addEventListener('click', function(e) {
                const message = document.getElementById('broadcast_message').value;
                const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;

                if (!message.trim()) {
                    e.preventDefault();
                    alert('Please enter a message');
                    return;
                }

                if (recipientType === 'custom') {
                    const numbers = document.getElementById('custom_numbers').value;
                    if (!numbers.trim()) {
                        e.preventDefault();
                        alert('Please enter phone numbers for custom recipients.');
                        return;
                    }
                }

                const demoMode = <?php echo isset($smsSettings['demo_mode']) && $smsSettings['demo_mode'] ? 'true' : 'false'; ?>;
                if (demoMode) {
                    if (!confirm('⚠️ DEMO MODE ENABLED\nNo actual SMS will be sent. Messages will only be logged.\n\nContinue?')) {
                        e.preventDefault();
                    }
                } else {
                    if (!confirm(`Are you sure you want to send this SMS?
            
Recipients: ${recipientType === 'custom' ? 'Custom numbers' : (recipientType === 'all_active' ? 'Active applicants' : 'All applicants')}
Message length: ${message.length} characters

This will use your Semaphore credits.`)) {
                        e.preventDefault();
                    }
                }
            });

            // Validate phone numbers as user types
            document.getElementById('custom_numbers')?.addEventListener('input', function(e) {
                const numbers = this.value.split(',').map(num => num.trim()).filter(num => num);
                const validNumbers = numbers.filter(num => /^(09|\+639|639|9)\d{9,10}$/.test(num.replace(/[^0-9+]/g, '')));

                if (numbers.length > 0) {
                    const validCount = document.createElement('div');
                    validCount.className = 'text-xs mt-1';
                    validCount.textContent = `${validNumbers.length} of ${numbers.length} numbers appear valid`;
                    validCount.style.color = validNumbers.length === numbers.length ? 'green' : 'orange';

                    const existing = this.parentNode.querySelector('.number-validation');
                    if (existing) existing.remove();
                    validCount.className += ' number-validation';
                    this.parentNode.appendChild(validCount);
                }
            });
        });
        // Add to your existing JavaScript in sms.php
        document.getElementById('test_number')?.addEventListener('blur', function(e) {
            const number = this.value.trim();
            const clean = number.replace(/[^0-9]/g, '');

            if (clean.length === 11 && clean.startsWith('09')) {
                // Valid 09XXXXXXXXX format
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            } else if (clean.length === 10 && clean.startsWith('9')) {
                // Valid 9XXXXXXXXX format
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            } else if (clean.length === 12 && (clean.startsWith('63') || clean.startsWith('639'))) {
                // Valid with country code
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            } else if (number) {
                // Invalid
                this.classList.remove('border-green-500');
                this.classList.add('border-red-500');
            } else {
                // Empty
                this.classList.remove('border-red-500', 'border-green-500');
            }
        });
    </script>
</body>

</html>
<?php
// Debug information
error_log("=== SMS DEBUG INFO ===");
error_log("Sender ID from settings: " . ($smsSettings['sender_id'] ?? 'NOT SET'));
error_log("Demo Mode: " . (isset($smsSettings['demo_mode']) && $smsSettings['demo_mode'] ? 'YES' : 'NO'));
error_log("API Key: " . (!empty($smsSettings['api_key']) ? 'SET' : 'NOT SET'));
?>