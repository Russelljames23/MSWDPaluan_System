<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../php/login/admin_header.php";
require_once "../../php/helpers/sms_helper.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ctx = urlencode($_GET['session_context'] ?? session_id());

// Get SMS settings
$smsSettings = getSMSSettings();

// Handle save settings
if (isset($_POST['save_settings'])) {
    $settings = [
        'provider' => $_POST['provider'] ?? 'semaphore',
        'api_key' => trim($_POST['api_key'] ?? ''),
        'sender_id' => 'SEMAPHORE',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'demo_mode' => isset($_POST['demo_mode']) ? 1 : 0
    ];

    if (saveSMSSettings($settings)) {
        $_SESSION['success'] = "SMS settings saved successfully!";
        $smsSettings = getSMSSettings();
    } else {
        $_SESSION['error'] = "Failed to save settings.";
    }

    header("Location: sms.php?session_context=" . $ctx);
    exit();
}

// Initialize variables
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

// Database connection
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
    if (isset($_POST['test_connection'])) {
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
        $message = trim($_POST['broadcast_message'] ?? '');
        $recipientType = $_POST['recipient_type'] ?? 'custom';

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
            $statusCondition = '';
            if ($recipientType === 'all_active') {
                $statusCondition = " AND status = 'Active'";
            }

            $query = "SELECT contact_number FROM applicants 
                     WHERE contact_number IS NOT NULL 
                     AND contact_number != '' 
                     AND contact_number != '0' 
                     AND LENGTH(contact_number) >= 10
                     $statusCondition";

            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['contact_number'])) {
                        $recipients[] = $row['contact_number'];
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

        // Send SMS
        $result = $smsGateway->sendBulk($recipients, $message);

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
        $testNumber = trim($_POST['test_number'] ?? '');

        if (empty($testNumber)) {
            $_SESSION['error'] = "Please enter a test phone number.";
        } else {
            $smsGateway = new SMSGateway($smsSettings);
            $demoMode = $smsSettings['demo_mode'] ?? false;

            $isValid = $smsGateway->validatePhoneNumber($testNumber);

            if (!$isValid) {
                $_SESSION['error'] = "Invalid phone number format. Use: 09XXXXXXXXX or +639XXXXXXXXX";
                $_SESSION['error'] .= "<br>You entered: " . htmlspecialchars($testNumber);

                $formatted = $smsGateway->formatNumberForSemaphore($testNumber);
                $_SESSION['error'] .= "<br>Formatted for API: " . htmlspecialchars($formatted);
            } elseif ($demoMode) {
                $_SESSION['success'] = "✅ DEMO MODE: Test SMS logged (no actual SMS sent).";
                $_SESSION['success'] .= "<br><small>Disable demo mode to send real test SMS.</small>";

                $log_query = "INSERT INTO sms_logs 
                            (phone_number, message, status, carrier, sms_type, user_id, created_at) 
                            VALUES (?, ?, 'demo_sent', 'semaphore', 'test', ?, NOW())";
                $stmt = $conn->prepare($log_query);
                $testMessage = 'Test SMS from MSWD System - If you receive this, SMS is working properly.';
                $stmt->bind_param("ssi", $testNumber, $testMessage, $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $testMessage = 'Test SMS from MSWD System - If you receive this, SMS is working properly.';
                $result = $smsGateway->send($testNumber, $testMessage);

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
                    $_SESSION['error'] .= "<br><small>Check API key and internet connection.</small>";
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
            $check_query = "SELECT id FROM sms_templates WHERE template_name = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $templateName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $update_query = "UPDATE sms_templates SET message = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $message, $row['id']);
            } else {
                $insert_query = "INSERT INTO sms_templates (template_name, message, is_active, created_at) VALUES (?, ?, 1, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ss", $templateName, $message);
            }

            if ($stmt->execute()) {
                $_SESSION['success'] = "Template saved successfully!";
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

// Pagination for SMS logs
$limit = 10; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of logs
$total_logs_query = "SELECT COUNT(*) as total FROM sms_logs WHERE status IS NOT NULL";
$total_result = $conn->query($total_logs_query);
$total_logs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $limit);

// Get SMS logs with pagination
$sms_logs = [];
$logs_query = "SELECT sl.id, sl.phone_number as recipient, sl.message, sl.status, sl.carrier, 
                      sl.sms_type, sl.created_at, sl.response_data,
                      u.username, u.user_type, u.profile_photo as user_avatar
               FROM sms_logs sl 
               LEFT JOIN users u ON sl.user_id = u.id 
               WHERE sl.status IS NOT NULL
               ORDER BY sl.created_at DESC 
               LIMIT $limit OFFSET $offset";
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

    $conn->query("CREATE TABLE IF NOT EXISTS sms_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_template_name (template_name)
    )");

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
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <style>
        /* Enhanced logo styling for page display */
        .highlighted-logo {
            filter: 
                brightness(1.3)      /* Make brighter */
                contrast(1.2)        /* Increase contrast */
                saturate(1.5)        /* Make colors more vibrant */
                drop-shadow(0 0 8px #3b82f6)  /* Blue glow */
                drop-shadow(0 0 12px rgba(59, 130, 246, 0.7));
            
            /* Optional border */
            border: 3px solid rgba(59, 130, 246, 0.4);
            border-radius: 12px;
            
            /* Inner glow effect */
            box-shadow: 
                inset 0 0 10px rgba(255, 255, 255, 0.6),
                0 0 20px rgba(59, 130, 246, 0.5);
            
            /* Animation for extra attention */
            animation: pulse-glow 2s infinite alternate;
        }
        
        @keyframes pulse-glow {
            from {
                box-shadow: 
                    inset 0 0 10px rgba(255, 255, 255, 0.6),
                    0 0 15px rgba(59, 130, 246, 0.5);
            }
            to {
                box-shadow: 
                    inset 0 0 15px rgba(255, 255, 255, 0.8),
                    0 0 25px rgba(59, 130, 246, 0.8);
            }
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

        * {
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* Improved sidebar styles */
        .sidebar {
            position: relative;
            border-radius: 10px;
            height: 100%;
            width: 78px;
            background: #fff;
            transition: all 0.4s ease;
            z-index: 10;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .dark .sidebar {
            background: #1f2937;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar.open {
            width: 200px;
        }

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

        .dark .nav-list li a,
        .dark .nav-list li button {
            background: #374151;
            border: 1px solid #4b5563;
            color: #e5e7eb;
        }

        .dark .nav-list li a:hover,
        .dark .nav-list li button:hover {
            background: #4b5563;
        }

        .links_name {
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 14px;
        }

        .sidebar.open .links_name {
            opacity: 1;
        }

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
            color: #e5e7eb;
        }

        .sidebar li:hover .tooltip {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        .sidebar.open li .tooltip {
            display: none;
        }

        .dark .links_name {
            color: #d1d5db;
        }

        .nav-list li #sms.active-link {
            color: #1d4ed8;
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li #sms.active-link svg {
            color: #1d4ed8;
        }

        .dark .nav-list li #sms.active-link {
            color: #60a5fa;
            border-color: #3b82f6;
            background: #1e40af;
        }

        .dark .nav-list li #sms.active-link svg {
            color: #60a5fa;
        }

        /* Improved status indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-indicator i {
            font-size: 0.75rem;
        }

        /* Card improvements */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .dark .card {
            background: #1f2937;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .dark .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        /* Table improvements */
        .table-container {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .dark .table-container {
            border-color: #374151;
        }

        /* Message counter */
        .char-counter {
            font-size: 0.75rem;
            transition: color 0.3s ease;
        }

        .char-counter.warning {
            color: #f59e0b;
        }

        .char-counter.danger {
            color: #ef4444;
            font-weight: 600;
        }

        /* Fixed status panel */
        .status-panel {
            position: fixed;
            top: 80px;
            right:0;
            z-index: 40;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Fixed Status Panel -->
    <div class="status-panel md:ml-64">
        <?php if ($tempSuccess): ?>
            <div class="mb-3 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900 dark:border-green-700 dark:text-green-200 shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $tempSuccess; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tempError): ?>
            <div class="mb-3 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg dark:bg-red-900 dark:border-red-700 dark:text-red-200 shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $tempError; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tempSmsWarning): ?>
            <div class="mb-3 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200 shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?php echo $tempSmsWarning; ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

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
                <a href="#" class="flex items-center justify-between mr-4">
                    <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
                        class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                        alt="MSWD LOGO" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD PALUAN</span>
                </a>
            </div>
            <div class="flex items-center lg:order-2">
                <button type="button"
                    class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full object-cover"
                        src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                        alt="user photo" />
                </button>
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
                                class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                            </a>
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
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
            <ul class="space-y-2">
                <li>
                    <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                        <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-user-plus w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Register</span>
                    </a>
                </li>
                <li>
                    <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                        class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        <i class="fas fa-list w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                        <li>
                            <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                            </a>
                        </li>
                        <li>
                            <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                            </a>
                        </li>
                        <li>
                            <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Benefits</span>
                    </a>
                </li>
                <li>
                    <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Report</span>
                    </a>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a href="../archived.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                        <i class="fas fa-archive w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                        <span class="ml-3">Archived</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                        <i class="fas fa-cog w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
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
                    <!-- System Status Card -->
                    <!-- <div class="card p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">SMS System Status</h2>
                                <p class="text-gray-600 dark:text-gray-300 mt-1"><?php echo $systemStatus['message'] ?? 'Unable to determine system status'; ?></p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="status-indicator 
                                    <?php echo ($systemStatus['status'] ?? '') === 'Operational' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                           (($systemStatus['status'] ?? '') === 'Demo Mode' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                           'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'); ?>">
                                    <?php if (($systemStatus['status'] ?? '') === 'Operational'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif (($systemStatus['status'] ?? '') === 'Demo Mode'): ?>
                                        <i class="fas fa-flask"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle"></i>
                                    <?php endif; ?>
                                    <?php echo $systemStatus['status'] ?? 'Unknown'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">API Status</div>
                                <div class="<?php echo ($systemStatus['api_configured'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <i class="fas <?php echo ($systemStatus['api_configured'] ?? false) ? 'fa-check' : 'fa-times'; ?> mr-1"></i>
                                    <?php echo ($systemStatus['api_configured'] ?? false) ? 'Configured' : 'Not Configured'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Demo Mode</div>
                                <div class="<?php echo ($systemStatus['demo_mode'] ?? false) ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'; ?>">
                                    <i class="fas <?php echo ($systemStatus['demo_mode'] ?? false) ? 'fa-flask' : 'fa-paper-plane'; ?> mr-1"></i>
                                    <?php echo ($systemStatus['demo_mode'] ?? false) ? 'Enabled' : 'Disabled'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Service</div>
                                <div class="<?php echo ($systemStatus['is_active'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <i class="fas <?php echo ($systemStatus['is_active'] ?? false) ? 'fa-play' : 'fa-stop'; ?> mr-1"></i>
                                    <?php echo ($systemStatus['is_active'] ?? false) ? 'Active' : 'Inactive'; ?>
                                </div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="font-semibold text-gray-900 dark:text-white">Provider</div>
                                <div class="text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-satellite-dish mr-1"></i>Semaphore API
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mb-6">
                        <!-- SMS Settings Card -->
                        <div class="card p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Semaphore API Settings</h2>
                            <form method="POST">
                                <div class="space-y-4">
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

                                    <!-- <div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="demo_mode" name="demo_mode" value="1"
                                                <?php echo (isset($smsSettings['demo_mode']) && $smsSettings['demo_mode']) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="demo_mode" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                Demo Mode (Log only, don't send)
                                            </label>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enable to test without sending real SMS</p>
                                    </div> -->

                                    <div>
                                        <input type="hidden" name="provider" value="semaphore">
                                    </div>

                                    <!-- <div>
                                        <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Semaphore API Key
                                        </label>
                                        <div class="relative">
                                            <input type="password" id="api_key" name="api_key"
                                                value="<?php echo htmlspecialchars($smsSettings['api_key'] ?? ''); ?>"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="Enter your Semaphore API key">
                                            <button type="button" onclick="toggleApiKeyVisibility()" class="absolute right-2.5 top-2.5 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                                <i class="fas fa-eye" id="apiKeyToggleIcon"></i>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Get your API key from <a href="https://semaphore.co" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">semaphore.co</a>
                                        </p>
                                    </div> -->

                                    <div class="pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
                                        <button type="submit" name="save_settings"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            <i class="fas fa-save mr-2"></i>Save Settings
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Test API Connection</h3>
                                
                                <?php if (!empty($smsSettings['api_key'])): ?>
                                    <form method="POST" class="mb-3">
                                        <button type="submit" name="test_connection"
                                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                            <i class="fas fa-plug mr-2"></i>Test API Connection
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
                                        <i class="fas fa-paper-plane mr-2"></i>Send Test SMS
                                    </button>
                                </form>
                            </div> -->
                            <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Send SMS</h2>
                            <form method="POST" id="sendSMSForm">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Send to
                                        </label>
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_custom" name="recipient_type" value="custom" checked
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_custom" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    <i class="fas fa-mobile-alt mr-1"></i>Custom Numbers
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_all" name="recipient_type" value="all"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_all" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    <i class="fas fa-users mr-1"></i>All Applicants
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" id="recipient_active" name="recipient_type" value="all_active"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                <label for="recipient_active" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    <i class="fas fa-user-check mr-1"></i>Active Applicants Only
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="customNumbersSection">
                                        <label for="custom_numbers" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Phone Numbers (comma-separated)
                                        </label>
                                        <textarea id="custom_numbers" name="custom_numbers" rows="2"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="09123456789, 09234567890"></textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple numbers with commas</p>
                                    </div>

                                    <div>
                                        <label for="broadcast_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Message
                                        </label>
                                        <textarea id="broadcast_message" name="broadcast_message" rows="4"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter your message here..."></textarea>
                                        <div class="mt-1 flex justify-between items-center">
                                            <span id="charCount" class="char-counter text-xs">0/160</span>
                                            <!-- <div class="text-xs text-gray-500 dark:text-gray-400">
                                                SMS Type:
                                                <select name="sms_type" class="text-xs border-none bg-transparent focus:ring-0">
                                                    <option value="broadcast">Broadcast</option>
                                                    <option value="announcement">Announcement</option>
                                                    <option value="reminder">Reminder</option>
                                                    <option value="notification">Notification</option>
                                                </select>
                                            </div> -->
                                        </div>
                                    </div>

                                    <div class="pt-4">
                                        <button type="submit" name="send_sms" id="sendSMSBtn"
                                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            <i class="fas fa-paper-plane mr-2"></i>Send SMS Messages
                                        </button>
                                    </div>
                                </div>
                            </form>
                            </div>
                        </div>

                        <!-- Send SMS Card -->
                        <!-- <div class="card p-6">
                        </div> -->
                    </div>

                    <!-- Message Templates Card -->
                    <div class="card p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Message Templates</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                                            <i class="fas fa-save mr-2"></i>Save Template
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Saved Templates</h3>
                                <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                                    <?php if (empty($templates)): ?>
                                        <div class="p-4 text-center bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <i class="fas fa-inbox text-2xl text-gray-400 dark:text-gray-500 mb-2"></i>
                                            <p class="text-gray-500 dark:text-gray-400">No templates saved yet.</p>
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
                                                            <i class="fas fa-clock mr-1"></i>Created: <?php echo date('M d, Y', strtotime($template['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-2 ml-4">
                                                        <button type="button"
                                                            onclick="useTemplate('<?php echo addslashes($template['message']); ?>')"
                                                            class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 rounded-lg transition">
                                                            <i class="fas fa-play mr-1"></i>Use
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
                    <div class="card overflow-hidden">
                        <div class="border-b dark:border-gray-700 p-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Logs</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Recent SMS message history</p>
                                </div>
                                <div class="mt-2 md:mt-0 text-sm text-gray-500 dark:text-gray-400">
                                    Showing <?php echo count($sms_logs); ?> of <?php echo $total_logs; ?> messages
                                </div>
                            </div>
                        </div>

                        <?php if (empty($sms_logs)): ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No SMS logs found</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Send your first SMS message to see logs here
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
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
                                                ?>
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <td class="px-6 py-4">
                                                        <div class="font-medium text-gray-900 dark:text-white">
                                                            <i class="fas fa-mobile-alt mr-2 text-gray-400"></i>
                                                            <?php echo htmlspecialchars($log['recipient'] ?? 'N/A'); ?>
                                                        </div>
                                                        <?php if ($log['carrier'] && $log['carrier'] !== 'demo'): ?>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                <?php echo htmlspecialchars(ucfirst($log['carrier'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($log['message'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars(substr($log['message'] ?? '', 0, 40)); ?>
                                                            <?php if (strlen($log['message'] ?? '') > 40): ?>...<?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <?php
                                                        $statusIcon = '';
                                                        $statusText = strtolower($log['status'] ?? '');
                                                        if (in_array($statusText, ['sent', 'delivered', 'success', 'queued'])) {
                                                            $statusIcon = 'fa-check-circle';
                                                        } elseif (in_array($statusText, ['failed', 'error', 'undelivered'])) {
                                                            $statusIcon = 'fa-times-circle';
                                                        } elseif (in_array($statusText, ['pending', 'processing', 'demo_sent'])) {
                                                            $statusIcon = 'fa-clock';
                                                        } else {
                                                            $statusIcon = 'fa-info-circle';
                                                        }
                                                        ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                            <i class="fas <?php echo $statusIcon; ?> mr-1.5"></i>
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
                                                                <i class="fas fa-clock mr-1 text-gray-400"></i>
                                                                <?php echo formatSMSTime($log['created_at'] ?? ''); ?>
                                                            </span>
                                                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                <?php echo isset($log['created_at']) ? date('M d, Y', strtotime($log['created_at'])) : 'N/A'; ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <button type="button" onclick="viewSMSDetails('<?php echo $log['id']; ?>')"
                                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition">
                                                            <i class="fas fa-eye mr-1.5"></i>View
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
                                    <div class="flex flex-1 justify-between sm:hidden">
                                        <?php if ($page > 1): ?>
                                            <a href="sms.php?session_context=<?php echo $ctx; ?>&page=<?php echo $page - 1; ?>"
                                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                                Previous
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($page < $total_pages): ?>
                                            <a href="sms.php?session_context=<?php echo $ctx; ?>&page=<?php echo $page + 1; ?>"
                                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                                Next
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                                                <span class="font-medium"><?php echo min($offset + $limit, $total_logs); ?></span> of 
                                                <span class="font-medium"><?php echo $total_logs; ?></span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                                <?php if ($page > 1): ?>
                                                    <a href="sms.php?session_context=<?php echo $ctx; ?>&page=<?php echo $page - 1; ?>"
                                                        class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 dark:ring-gray-600 dark:hover:bg-gray-700">
                                                        <span class="sr-only">Previous</span>
                                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                
                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                ?>
                                                    <a href="sms.php?session_context=<?php echo $ctx; ?>&page=<?php echo $i; ?>"
                                                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold 
                                                        <?php echo $i == $page 
                                                            ? 'z-10 bg-blue-600 text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600' 
                                                            : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-700'; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                <?php endfor; ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <a href="sms.php?session_context=<?php echo $ctx; ?>&page=<?php echo $page + 1; ?>"
                                                        class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 dark:ring-gray-600 dark:hover:bg-gray-700">
                                                        <span class="sr-only">Next</span>
                                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- SMS Details Modal -->
    <div id="smsDetailsModal" class="hidden fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]" id="smsModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
        // Toggle API key visibility
        function toggleApiKeyVisibility() {
            const apiKeyField = document.getElementById('api_key');
            const toggleIcon = document.getElementById('apiKeyToggleIcon');
            
            if (apiKeyField.type === 'password') {
                apiKeyField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                apiKeyField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Character counter
        const messageInput = document.getElementById('broadcast_message');
        const charCount = document.getElementById('charCount');

        if (messageInput && charCount) {
            messageInput.addEventListener('input', () => {
                const length = messageInput.value.length;
                charCount.textContent = `${length}/160`;
                charCount.className = 'char-counter text-xs';

                if (length > 160) {
                    charCount.classList.add('danger');
                } else if (length > 140) {
                    charCount.classList.add('warning');
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
                
                // Show success message
                showToast('Template loaded successfully!', 'success');
            }
        }

        // Phone number validation
        function validatePhoneNumber(number) {
            const clean = number.replace(/[^0-9+]/g, '');
            return /^(09|9|63|\+63)\d{9,12}$/.test(clean);
        }

        // Send SMS validation
        document.getElementById('sendSMSBtn')?.addEventListener('click', function(e) {
            const message = document.getElementById('broadcast_message').value;
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;

            if (!message.trim()) {
                e.preventDefault();
                showToast('Please enter a message', 'error');
                return;
            }

            if (recipientType === 'custom') {
                const numbersInput = document.getElementById('custom_numbers');
                const numbers = numbersInput.value.split(',').map(num => num.trim()).filter(num => num);

                if (numbers.length === 0) {
                    e.preventDefault();
                    showToast('Please enter at least one phone number', 'error');
                    return;
                }

                const invalidNumbers = [];
                numbers.forEach(num => {
                    if (!validatePhoneNumber(num)) {
                        invalidNumbers.push(num);
                    }
                });

                if (invalidNumbers.length > 0) {
                    e.preventDefault();
                    showToast(`Invalid phone numbers detected: ${invalidNumbers.length} invalid`, 'error');
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

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 
                        'fa-info-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize recipient type
            document.querySelector('input[name="recipient_type"][value="custom"]').dispatchEvent(new Event('change'));

            // Auto-fill API key if empty
            const apiKeyField = document.getElementById('api_key');
            if (apiKeyField && !apiKeyField.value) {
                apiKeyField.value = '11203b3c9a4bc430dd3a1b181ece8b6c';
            }

            // Add phone number validation as user types
            document.getElementById('custom_numbers')?.addEventListener('input', function(e) {
                const numbers = this.value.split(',').map(num => num.trim()).filter(num => num);
                const validNumbers = numbers.filter(num => validatePhoneNumber(num));

                if (numbers.length > 0) {
                    const existing = this.parentNode.querySelector('.number-validation');
                    if (existing) existing.remove();

                    const validCountEl = document.createElement('div');
                    validCountEl.className = 'number-validation text-xs mt-1';
                    validCountEl.innerHTML = `<i class="fas ${validNumbers.length === numbers.length ? 'fa-check text-green-600' : 'fa-exclamation-triangle text-yellow-600'} mr-1"></i>${validNumbers.length} of ${numbers.length} numbers are valid`;
                    this.parentNode.appendChild(validCountEl);
                }
            });
        });

        // SMS Details Modal
        function viewSMSDetails(smsId) {
            const modalContent = document.getElementById('smsModalContent');
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading SMS details...</p>
                    </div>
                </div>
            `;

            document.getElementById('smsDetailsModal').classList.remove('hidden');

            // Simulate loading and show details
            setTimeout(() => {
                modalContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-2"></i>
                                <p class="text-sm text-blue-800 dark:text-blue-200">SMS details are loaded from the database.</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMS ID</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">${smsId}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">Loaded from database record</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timestamp</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">${new Date().toLocaleString()}</p>
                            </div>
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

        // Theme toggle
        const themeToggle = document.getElementById('nav-theme-toggle');
        const themeLightIcon = document.getElementById('nav-theme-light-icon');
        const themeDarkIcon = document.getElementById('nav-theme-dark-icon');
        const themeText = document.getElementById('nav-theme-text');

        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const isDark = document.documentElement.classList.contains('dark');
                
                if (isDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                    themeLightIcon.classList.remove('hidden');
                    themeDarkIcon.classList.add('hidden');
                    themeText.textContent = 'Light Mode';
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                    themeLightIcon.classList.add('hidden');
                    themeDarkIcon.classList.remove('hidden');
                    themeText.textContent = 'Dark Mode';
                }
            });

            // Load saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                themeLightIcon.classList.add('hidden');
                themeDarkIcon.classList.remove('hidden');
                themeText.textContent = 'Dark Mode';
            } else {
                document.documentElement.classList.remove('dark');
                themeLightIcon.classList.remove('hidden');
                themeDarkIcon.classList.add('hidden');
                themeText.textContent = 'Light Mode';
            }
        }

        // Sidebar toggle
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('btn');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }
    </script>
</body>
</html>