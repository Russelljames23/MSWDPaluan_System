<?php
require_once "../../php/login/admin_header.php";
require_once "../../php/helpers/sms_helper.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ctx = urlencode($_GET['session_context'] ?? session_id());

// Get SMS settings - ADD DEBUGGING HERE
$smsSettings = getSMSSettings();
error_log("SMS Settings loaded: " . print_r($smsSettings, true));

// Initialize variables to prevent warnings
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$smsWarning = $_SESSION['sms_warning'] ?? '';
$templates = []; // Initialize empty array for templates
$systemStatus = null; // Initialize system status

// Store messages in temporary variables BEFORE clearing session
$tempSuccess = $success;
$tempError = $error;
$tempSmsWarning = $smsWarning;

// Clear session messages after storing in temp variables
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['sms_warning']);

// Database connection
$host = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Profile DB connection failed: " . $e->getMessage());
    $pdo = null;
}

// Fetch current user data
$user_id = $_SESSION['user_id'] ?? 0;
$user_data = [];

if ($user_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        $user_data = [];
    }
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

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get message templates
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY created_at DESC");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching templates: " . $e->getMessage());
        $templates = [];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Save SMS settings
        $settings = [
            'provider' => 'email_sms',
            'api_key' => trim($_POST['api_key'] ?? ''),
            'sender_id' => trim($_POST['sender_id'] ?? 'MSWDPALUAN'),
            'smtp_host' => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
            'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
            'smtp_user' => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
            'smtp_secure' => 'tls',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'demo_mode' => isset($_POST['demo_mode']) ? 1 : 0
        ];

        // Save settings
        if (saveSMSSettings($settings)) {
            $_SESSION['success'] = "SMS settings saved successfully!";
            $smsSettings = getSMSSettings(); // Refresh
        } else {
            $_SESSION['error'] = "Failed to save settings.";
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['send_sms'])) {
        // Send SMS
        $message = trim($_POST['broadcast_message'] ?? '');
        $recipientType = $_POST['recipient_type'] ?? 'custom';

        // Check if SMS is enabled
        if (!$smsSettings['is_active']) {
            $_SESSION['error'] = "SMS service is disabled. Please enable it in settings.";
        } elseif (empty($message)) {
            $_SESSION['error'] = "Please enter a message.";
        } else {
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

            // Validate and format numbers
            $validRecipients = [];
            foreach ($recipients as $number) {
                $cleanNumber = preg_replace('/[^0-9+]/', '', $number);

                // Accept various formats
                if (preg_match('/^(09|\+639|63|9)\d{9,10}$/', $cleanNumber)) {
                    // Format to 09XXXXXXXXX
                    if (preg_match('/^\+639(\d{9})$/', $cleanNumber, $matches)) {
                        $cleanNumber = '09' . $matches[1];
                    } elseif (preg_match('/^63(\d{10})$/', $cleanNumber, $matches)) {
                        $cleanNumber = '0' . $matches[1];
                    } elseif (preg_match('/^9(\d{9})$/', $cleanNumber)) {
                        $cleanNumber = '0' . $cleanNumber;
                    }

                    $validRecipients[] = $cleanNumber;
                }
            }

            // Remove duplicates
            $validRecipients = array_unique($validRecipients);

            if (empty($validRecipients)) {
                $_SESSION['error'] = "No valid Philippine mobile numbers found.<br>";
                $_SESSION['error'] .= "Found " . count($recipients) . " numbers, but none matched Philippine format.<br>";
                $_SESSION['error'] .= "Valid formats: 09XXXXXXXXX, +639XXXXXXXXXX, 9XXXXXXXXX";
            } else {
                // Send SMS
                $gateway = new SMSGateway($smsSettings);
                $results = $gateway->sendBulk($validRecipients, $message);

                // Show results
                if ($results['sent'] > 0) {
                    if ($results['demo_mode']) {
                        $_SESSION['success'] = "✅ DEMO MODE: Successfully logged {$results['sent']} SMS messages!<br>";
                        $_SESSION['success'] .= "<small>No actual SMS were sent. Disable demo mode to send real messages.</small>";
                    } else {
                        $_SESSION['success'] = "✅ Successfully processed {$results['sent']} SMS messages!";

                        // Add carrier info if available
                        $carrierCount = [];
                        foreach ($results['details'] as $detail) {
                            if (isset($detail['carrier']) && isset($detail['status']) && $detail['status'] === 'sent') {
                                $carrier = $detail['carrier'];
                                $carrierCount[$carrier] = ($carrierCount[$carrier] ?? 0) + 1;
                            }
                        }

                        if (!empty($carrierCount)) {
                            $_SESSION['success'] .= "<br><small>Carriers: ";
                            foreach ($carrierCount as $carrier => $count) {
                                $_SESSION['success'] .= "$carrier($count) ";
                            }
                            $_SESSION['success'] .= "</small>";
                        }
                    }

                    if ($results['failed'] > 0) {
                        $_SESSION['sms_warning'] = "Note: {$results['failed']} numbers failed to send.";
                    }
                    if ($results['invalid'] > 0) {
                        $_SESSION['sms_warning'] = ($_SESSION['sms_warning'] ?? '') . "<br>{$results['invalid']} invalid numbers skipped.";
                    }
                } else {
                    if ($results['demo_mode']) {
                        $_SESSION['error'] = "DEMO MODE: Messages logged but not sent.<br>";
                        $_SESSION['error'] .= "Valid recipients found: " . count($validRecipients) . "<br>";
                        $_SESSION['error'] .= "Disable demo mode to send real SMS.";
                    } else {
                        $_SESSION['error'] = "No messages could be sent.<br>";
                        $_SESSION['error'] .= "Valid recipients found: " . count($validRecipients) . "<br>";

                        // Show SMTP status
                        $smtpTest = $gateway->testSMTPConnection();
                        if (!$smtpTest['success']) {
                            $_SESSION['error'] .= "<br>SMTP Issue: " . $smtpTest['message'];
                        } else {
                            $_SESSION['error'] .= "<br>Check carrier email gateways. Some carriers may block emails.";
                        }
                    }
                }
            }
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['test_smtp'])) {
        $gateway = new SMSGateway($smsSettings);
        $testResult = $gateway->testSMTPConnection();

        if ($testResult['success']) {
            $_SESSION['success'] = "✅ " . $testResult['message'];
        } else {
            $_SESSION['error'] = "❌ " . $testResult['message'];
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }

    if (isset($_POST['save_template'])) {
        $templateName = trim($_POST['template_name'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($templateName) || empty($message)) {
            $_SESSION['error'] = "Please provide both template name and message.";
        } elseif ($pdo) {
            try {
                // Check if template with same name exists
                $stmt = $pdo->prepare("SELECT id FROM sms_templates WHERE template_name = ?");
                $stmt->execute([$templateName]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Update existing template
                    $stmt = $pdo->prepare("UPDATE sms_templates SET message = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$message, $existing['id']]);
                } else {
                    // Insert new template
                    $stmt = $pdo->prepare("INSERT INTO sms_templates (template_name, message, is_active, created_at) VALUES (?, ?, 1, NOW())");
                    $stmt->execute([$templateName, $message]);
                }

                $_SESSION['success'] = "Template saved successfully!";

                // Refresh templates
                $stmt = $pdo->prepare("SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY created_at DESC");
                $stmt->execute();
                $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error saving template: " . $e->getMessage());
                $_SESSION['error'] = "Failed to save template: " . $e->getMessage();
            }
        }

        header("Location: sms.php?session_context=" . $ctx);
        exit();
    }
}

// Get SMS logs
$sms_logs = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sl.id,
                sl.phone_number as recipient,
                sl.message,
                sl.status,
                sl.carrier,
                sl.sms_type,
                sl.created_at,
                u.username,
                u.user_type,
                u.profile_photo as user_avatar
            FROM sms_logs sl 
            LEFT JOIN users u ON sl.user_id = u.id 
            WHERE sl.status IS NOT NULL
            ORDER BY sl.created_at DESC 
            LIMIT 50
        ");
        $stmt->execute();
        $sms_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching SMS logs: " . $e->getMessage());
    }
}

// Calculate system status - FIXED VERSION
// First, ensure $smsSettings is an array
$smsSettings = $smsSettings ?? [];

// Always calculate system status, even with default values
$smtpConfigured = !empty($smsSettings['smtp_user']) && !empty($smsSettings['smtp_pass']);
$demoMode = $smsSettings['demo_mode'] ?? false;
$isActive = $smsSettings['is_active'] ?? false;

if (!$isActive) {
    $systemStatus = [
        'status' => 'Disabled',
        'message' => 'SMS service is disabled. Enable it to send messages.',
        'smtp_configured' => $smtpConfigured,
        'demo_mode' => $demoMode
    ];
} elseif ($demoMode) {
    $systemStatus = [
        'status' => 'Demo Mode',
        'message' => 'SMS are being logged but not sent. Disable demo mode to send real messages.',
        'smtp_configured' => $smtpConfigured,
        'demo_mode' => $demoMode
    ];
} elseif (!$smtpConfigured) {
    $systemStatus = [
        'status' => 'Not Configured',
        'message' => 'SMTP settings are not configured. Please set up your email credentials.',
        'smtp_configured' => false,
        'demo_mode' => $demoMode
    ];
} else {
    $systemStatus = [
        'status' => 'Operational',
        'message' => 'SMS service is ready to send messages.',
        'smtp_configured' => true,
        'demo_mode' => false
    ];
}

// Function to get status badge class
function getSMSStatusClass($status)
{
    $status = strtolower($status);

    if ($status === 'sent' || $status === 'delivered' || $status === 'success') {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } elseif ($status === 'failed' || $status === 'error' || $status === 'undelivered') {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } elseif ($status === 'pending' || $status === 'queued' || $status === 'processing') {
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
            <section id="smsSection" class="bg-gray-50 dark:bg-gray-900 w-full ">
                <div class="w-[900px] px-2">
                    <!-- Success Message -->
                    <?php if ($tempSuccess): ?>
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                            <?php echo $tempSuccess; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if ($tempError): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg dark:bg-red-900 dark:border-red-700 dark:text-red-200">
                            <?php echo $tempError; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Warning Message -->
                    <?php if ($tempSmsWarning): ?>
                        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200">
                            <?php echo $tempSmsWarning; ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-5">
                        <!-- SMS Settings Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">SMS Settings</h2>

                            <!-- ALWAYS SHOW SYSTEM STATUS - TEST -->
                            <div class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-medium">System Status: <?php echo $systemStatus['status'] ?? 'Unknown'; ?></p>
                                        <p class="text-sm mt-1"><?php echo $systemStatus['message'] ?? 'Unable to determine system status'; ?></p>
                                        <p class="text-xs mt-1 text-gray-600 dark:text-gray-300">
                                            SMS Settings: <?php echo !empty($smsSettings) ? 'Loaded' : 'Not loaded'; ?> |
                                            SMTP Configured: <?php echo ($systemStatus['smtp_configured'] ?? false) ? 'Yes' : 'No'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST">
                                <div class="space-y-4">

                                    <!-- Service Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            SMS Service Status
                                        </label>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                                <?php echo ($smsSettings['is_active'] ?? 1) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="is_active" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                Enable SMS Service
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Sender ID -->
                                    <div>
                                        <label for="sender_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Sender Name
                                        </label>
                                        <input type="text" id="sender_id" name="sender_id"
                                            value="<?php echo htmlspecialchars($smsSettings['sender_id'] ?? 'MSWDPALUAN'); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="MSWDPALUAN"
                                            maxlength="11">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Will appear as sender in SMS (max 11 characters)</p>
                                    </div>

                                    <input type="hidden" name="provider" value="email_sms">
                                    <input type="hidden" name="api_key" value="">

                                    <div class="grid grid-cols-1 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <button type="submit" name="save_settings"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            Save Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>


                        <!-- Message Templates Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Message Templates</h2>
                            <form method="POST" class="mb-4">
                                <div class="space-y-4">
                                    <div>
                                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Template Message
                                        </label>
                                        <textarea id="message" name="message" rows="3"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter template message..."></textarea>
                                    </div>

                                    <button type="submit" name="save_template"
                                        class="w-full text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900">
                                        Save Template
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Saved Templates</h3>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    <?php if (empty($templates)): ?>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm">No templates saved yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                                            <?php echo htmlspecialchars($template['template_name']); ?>
                                                            <?php if (!$template['is_active']): ?>
                                                                <span class="ml-2 text-xs text-red-500 dark:text-red-400">(Inactive)</span>
                                                            <?php endif; ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                            <?php echo htmlspecialchars(substr($template['message'], 0, 100)); ?>
                                                            <?php echo strlen($template['message']) > 100 ? '...' : ''; ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button type="button" onclick="useTemplate(<?php echo htmlspecialchars(json_encode($template['message'])); ?>)"
                                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                            Use
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mt-6">
                        <div class="border-b dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Logs</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Recent SMS message history</p>
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
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 sms-table">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Recipient</th>
                                            <th scope="col" class="px-6 py-3 hidden md:table-cell">User</th>
                                            <th scope="col" class="px-6 py-3">Message</th>
                                            <th scope="col" class="px-6 py-3 hidden lg:table-cell">Type</th>
                                            <th scope="col" class="px-6 py-3">Status</th>
                                            <th scope="col" class="px-6 py-3">Time</th>
                                            <th scope="col" class="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sms_logs as $log): ?>
                                            <?php
                                            $statusClass = getSMSStatusClass($log['status']);
                                            $pulseClass = (strtolower($log['status']) === 'sent' || strtolower($log['status']) === 'delivered') ? 'sms-status-sent' : '';
                                            ?>
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600" data-sms-id="<?php echo $log['id']; ?>">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                        <?php echo htmlspecialchars($log['recipient'] ?? 'N/A'); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 hidden md:table-cell">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 w-8 h-8">
                                                            <img class="w-8 h-8 rounded-full object-cover"
                                                                src="<?php echo htmlspecialchars($log['user_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($log['username'] ?? 'User') . '&background=random&color=fff&size=32'); ?>"
                                                                alt="<?php echo htmlspecialchars($log['username'] ?? 'User'); ?>">
                                                        </div>
                                                        <div class="ml-3">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                                <?php echo htmlspecialchars($log['username'] ?? 'User'); ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                <?php echo htmlspecialchars($log['user_type'] ?? 'User'); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="sms-preview" title="<?php echo htmlspecialchars($log['message'] ?? ''); ?>">
                                                        <?php
                                                        $message = $log['message'] ?? '';
                                                        if (strlen($message) > 50) {
                                                            echo htmlspecialchars(substr($message, 0, 50)) . '...';
                                                        } else {
                                                            echo htmlspecialchars($message);
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 hidden lg:table-cell">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                    <?php echo (($log['sms_type'] ?? 'outgoing') === 'outgoing') ?
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($log['sms_type'] ?? 'outgoing')); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full sms-status-badge <?php echo $statusClass . ' ' . $pulseClass; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($log['status'] ?? 'unknown')); ?>
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
                                                    <button type="button" onclick="viewSMSDetails(<?php echo $log['id']; ?>)"
                                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
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

    <!-- SMS Details Overlay -->
    <div id="smsDetailsOverlay" class="hidden fixed inset-0 bg-gray-900/50 bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 mb-2 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SMS Details</h3>
                <button id="closeSMSOverlay" class="text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 overflow-y-auto max-h-[calc(90vh-120px)]" id="smsOverlayContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
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
                    charCount.classList.add('text-gray-500');
                }
            });
            messageInput.dispatchEvent(new Event('input'));
        }

        // Toggle custom numbers input
        document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customNumbersInput = document.getElementById('custom_numbers');
                if (customNumbersInput) {
                    customNumbersInput.disabled = this.value !== 'custom';
                    if (this.value === 'custom') {
                        customNumbersInput.focus();
                    }
                }
            });
        });

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-yellow-100').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set up recipient type toggles
            const recipientRadios = document.querySelectorAll('input[name="recipient_type"]');
            if (recipientRadios.length > 0) {
                recipientRadios[0].dispatchEvent(new Event('change'));
            }

            // Show configuration guide if SMTP not set
            const smtpUser = document.querySelector('input[name="smtp_user"]');
            const smtpPass = document.querySelector('input[name="smtp_pass"]');

            if (smtpUser && smtpPass && (!smtpUser.value || !smtpPass.value)) {
                showConfigurationGuide();
            }
        });

        function showConfigurationGuide() {
            const guideHtml = `
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900 dark:border-blue-700">
                <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">📋 SMS Configuration Guide</h4>
                <ol class="text-sm text-blue-700 dark:text-blue-300 space-y-2 list-decimal list-inside">
                    <li><strong>For Gmail Users:</strong>
                        <ul class="ml-4 mt-1 list-disc">
                            <li>Enable 2-Factor Authentication in your Google Account</li>
                            <li>Generate an "App Password" from Google Security settings</li>
                            <li>Use the app password (16 characters) in SMTP Password field</li>
                        </ul>
                    </li>
                    <li><strong>For Other Email:</strong>
                        <ul class="ml-4 mt-1 list-disc">
                            <li>Check your email provider's SMTP settings</li>
                            <li>Use your email and password</li>
                            <li>Common ports: 587 (TLS) or 465 (SSL)</li>
                        </ul>
                    </li>
                    <li><strong>Test Mode:</strong>
                        <ul class="ml-4 mt-1 list-disc">
                            <li>Enable "Demo Mode" to test without sending real SMS</li>
                            <li>All SMS will be logged but not sent</li>
                            <li>Disable demo mode when ready to send real SMS</li>
                        </ul>
                    </li>
                </ol>
            </div>
        `;

            const settingsCard = document.querySelector('.bg-white.rounded-lg.shadow.p-6');
            if (settingsCard) {
                const form = settingsCard.querySelector('form');
                if (form) {
                    form.insertAdjacentHTML('beforeend', guideHtml);
                }
            }
        }

        // Use template function
        function useTemplate(message) {
            const messageInput = document.getElementById('broadcast_message');
            if (messageInput) {
                messageInput.value = message;
                messageInput.dispatchEvent(new Event('input'));
            }
        }

        // Theme Switcher
        document.addEventListener('DOMContentLoaded', function() {
            const navThemeToggle = document.getElementById('nav-theme-toggle');
            const navThemeLightIcon = document.getElementById('nav-theme-light-icon');
            const navThemeDarkIcon = document.getElementById('nav-theme-dark-icon');
            const navThemeText = document.getElementById('nav-theme-text');

            // Function to update theme icons based on current theme
            function updateThemeUI(isDarkMode) {
                if (isDarkMode) {
                    // If dark mode is active, show light icon (for switching to light mode)
                    if (navThemeLightIcon) navThemeLightIcon.classList.remove('hidden');
                    if (navThemeDarkIcon) navThemeDarkIcon.classList.add('hidden');
                    if (navThemeText) navThemeText.textContent = 'Light Mode';
                } else {
                    // If light mode is active, show dark icon (for switching to dark mode)
                    if (navThemeLightIcon) navThemeLightIcon.classList.add('hidden');
                    if (navThemeDarkIcon) navThemeDarkIcon.classList.remove('hidden');
                    if (navThemeText) navThemeText.textContent = 'Dark Mode';
                }
            }

            // Function to set theme
            function setTheme(theme) {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
                updateThemeUI(theme === 'dark');
            }

            // Initialize theme
            function initTheme() {
                // Check localStorage for saved theme
                const savedTheme = localStorage.getItem('theme');

                // Check for system preference
                const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                // Determine which theme to use
                let theme = 'light';
                if (savedTheme) {
                    theme = savedTheme;
                } else if (systemPrefersDark) {
                    theme = 'dark';
                }

                // Apply the theme
                setTheme(theme);

                // Update UI
                updateThemeUI(theme === 'dark');
            }

            // Initialize theme on page load
            initTheme();

            // Toggle theme when button is clicked
            if (navThemeToggle) {
                navThemeToggle.addEventListener('click', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    setTheme(isDark ? 'light' : 'dark');
                });
            }

            // Listen for theme changes from other tabs/windows
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    const theme = e.newValue;
                    setTheme(theme);
                }
            });

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                // Only apply system theme if no user preference is saved
                if (!localStorage.getItem('theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });

            // Optional: Add keyboard shortcut for theme toggle (Alt+T)
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 't') {
                    const isDark = document.documentElement.classList.contains('dark');
                    setTheme(isDark ? 'light' : 'dark');
                    e.preventDefault();
                }
            });
        });

        // Sidebar toggle
        let sidebar = document.querySelector(".sidebar");
        let closeBtn = document.querySelector("#btn");

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                sidebar.classList.toggle("open");
            });
        }
    </script>

    <script>
        // JavaScript functions for SMS page
        function viewSMSDetails(smsId) {
            // Show loading state
            document.getElementById('smsOverlayContent').innerHTML = `
            <div class="p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Loading SMS details...</p>
            </div>
        `;

            // Show overlay
            document.getElementById('smsDetailsOverlay').classList.remove('hidden');

            // Since get_sms_details.php doesn't exist, we'll fetch from current page with AJAX
            fetch(`sms.php?get_sms_details=${smsId}`)
                .then(response => response.text())
                .then(html => {
                    // Try to extract SMS details from the page
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Look for the SMS log row with this ID
                    const smsRow = doc.querySelector(`tr[data-sms-id="${smsId}"]`);

                    if (smsRow) {
                        // Extract data from the row
                        const recipient = smsRow.querySelector('td:first-child .font-medium')?.textContent || 'N/A';
                        const message = smsRow.querySelector('.sms-preview')?.title || 'No message content';
                        const status = smsRow.querySelector('.sms-status-badge')?.textContent.trim() || 'Unknown';
                        const smsType = smsRow.querySelector('td:nth-child(4) span')?.textContent.trim() || 'Outgoing';
                        const createdAt = smsRow.querySelector('td:nth-child(6) span:first-child')?.textContent || 'N/A';

                        const statusClass = getSMSStatusClassJS(status);
                        const pulseClass = (status.toLowerCase() === 'sent' || status.toLowerCase() === 'delivered') ? 'sms-status-sent' : '';

                        // Get user info
                        const username = smsRow.querySelector('td:nth-child(2) p.text-sm.font-medium')?.textContent || 'User';
                        const userType = smsRow.querySelector('td:nth-child(2) p.text-xs')?.textContent || 'User';
                        const userAvatar = smsRow.querySelector('td:nth-child(2) img')?.src || `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=random&color=fff&size=40`;

                        document.getElementById('smsOverlayContent').innerHTML = `
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipient</label>
                                        <p class="mt-1 text-gray-900 dark:text-white font-medium">${recipient}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date & Time</label>
                                        <p class="mt-1 text-gray-900 dark:text-white">${createdAt}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <p class="mt-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full sms-status-badge ${statusClass} ${pulseClass}">
                                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                        <p class="mt-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                ${(smsType.toLowerCase() === 'outgoing') ? 
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'}">
                                                ${smsType}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sender</label>
                                    <div class="mt-1 flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <img class="w-10 h-10 rounded-full object-cover mr-3" 
                                            src="${userAvatar}" 
                                            alt="${username}">
                                        <div>
                                            <p class="text-gray-900 dark:text-white font-medium">${username}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">${userType}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                                    <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <p class="text-gray-900 dark:text-white whitespace-pre-wrap">${message}</p>
                                    </div>
                                </div>
                            </div>
                            `;
                    } else {
                        // Fallback if we can't find the data
                        document.getElementById('smsOverlayContent').innerHTML = `
                            <div class="p-6 text-center">
                                <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Limited Details</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Full SMS details are not available. The SMS record exists but detailed information could not be loaded.
                                </p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching SMS details:', error);
                    document.getElementById('smsOverlayContent').innerHTML = `
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Error Loading Details</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Failed to load SMS details. Please try again.
                        </p>
                    </div>
                `;
                });
        }

        // Helper function for JavaScript status classification
        function getSMSStatusClassJS(status) {
            if (!status) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';

            const statusLower = status.toLowerCase();

            if (statusLower.includes('sent') || statusLower.includes('delivered') || statusLower.includes('success')) {
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            } else if (statusLower.includes('failed') || statusLower.includes('error') || statusLower.includes('undelivered')) {
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            } else if (statusLower.includes('pending') || statusLower.includes('queued') || statusLower.includes('processing')) {
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            } else if (statusLower.includes('cancelled') || statusLower.includes('expired')) {
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            } else {
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            }
        }

        // Close overlay
        document.getElementById('closeSMSOverlay').addEventListener('click', function() {
            document.getElementById('smsDetailsOverlay').classList.add('hidden');
        });

        // Close overlay when clicking outside
        document.getElementById('smsDetailsOverlay').addEventListener('click', function(e) {
            if (e.target.id === 'smsDetailsOverlay') {
                document.getElementById('smsDetailsOverlay').classList.add('hidden');
            }
        });

        // Close overlay with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('smsDetailsOverlay').classList.add('hidden');
            }
        });
    </script>
</body>

</html>