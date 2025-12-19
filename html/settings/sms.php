<?php
require_once "../../php/login/admin_header.php";
require_once "../../php/helpers/sms_helper.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ctx = urlencode($_GET['session_context'] ?? session_id());

// Get SMS settings
$smsSettings = getSMSSettings();
$host = "localhost";
$dbname = "mswd_seniors";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
                // In the send_sms POST handling, replace the success message with:
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

    // Add this in your POST handling section, before the if(isset($_POST['send_sms'])) block
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
}
// Add this after your POST handling section
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
// Get SMS logs
$smsLogs = [];
if (!$conn->connect_error) {
    $logsResult = $conn->query("SELECT * FROM sms_logs ORDER BY sent_at DESC LIMIT 50");
    while ($row = $logsResult->fetch_assoc()) {
        $smsLogs[] = $row;
    }
}

// Check system status
$systemStatus = null;
$gateway = new SMSGateway($smsSettings);
$systemStatus = $gateway->checkSystemStatus();

// Get session messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$smsWarning = $_SESSION['sms_warning'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['sms_warning']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Settings - MSWD Paluan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <style>
        /* Character counter */
        .char-counter {
            font-size: 12px;
            transition: color 0.3s;
        }

        .char-counter.warning {
            color: #f59e0b;
            font-weight: bold;
        }

        .char-counter.error {
            color: #ef4444;
            font-weight: bold;
        }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 50;
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Sidebar styles remain the same */
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
            background: rgba(221, 221, 221, 0.555);
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

        .sidebar li:hover .tooltip {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        .sidebar.open li .tooltip {
            display: none;
        }

        .home-section {
            margin-left: 78px;
            padding: 20px;
            transition: all 0.4s ease;
        }

        .sidebar.open~.home-section {
            margin-left: 200px;
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
    </style>
</head>

<body>
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
                    <span class="sr-only">Toggle sidebar</span>
                </button>
                <a href="#" class="flex items-center justify-between mr-4 ">
                    <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
                        class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50"
                        alt="MSWD LOGO" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD
                        PALUAN</span>
                </a>
            </div>
            <!-- UserProfile -->
            <div class="flex items-center lg:order-2">
                <!-- ... (same as accounts.php) ... -->
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
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/index.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-blue-700 group">
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
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/register.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <g transform="translate(24,0) scale(-1,1)">
                                <path fill-rule="evenodd"
                                    d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2v-16a2 2 0 0 1-2-2Z"
                                    clip-rule="evenodd" />
                            </g>
                        </svg>
                        <span class="ml-3">Register</span>
                    </a>
                </li>
                <li>
                    <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                        class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">
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
                            <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                List</a>
                        </li>
                        <li>
                            <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                List</a>
                        </li>
                        <li>
                            <a href="/MSWDPALUAN_SYSTEM-MAIN/html/SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                List</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/benefits.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
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
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/generate_id.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-3">Generate ID</span>
                    </a>
                </li>
                <li>
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/reports/report.php?session_context=<?php echo $ctx; ?>"
                        class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75  hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
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
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/html/archived.php?session_context=<?php echo $ctx; ?>"
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
                    <a href="#" style="color: blue;"
                        class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-blue bg-blue-100 hover:bg-blue-100 dark:hover:bg-blue-700 group">
                        <svg aria-hidden="true"
                            class="flex-shrink-0 w-6 h-6 text-blue-700 transition duration-75 dark:text-gray-400 group-hover:text-blue-700 dark:group-hover:text-white"
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
                            <svg class="w-6 h-6 text-gray-800 dark:text-gray-900" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="links_name">My Profile</span>
                        </a>
                        <span class="tooltip">My Profile</span>
                    </li>
                    <li>
                        <a href="theme.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M13 3a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0V3ZM6.343 4.929A1 1 0 0 0 4.93 6.343l1.414 1.414a1 1 0 0 0 1.414-1.414L6.343 4.929Zm12.728 1.414a1 1 0 0 0-1.414-1.414l-1.414 1.414a1 1 0 0 0 1.414 1.414l1.414-1.414ZM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm-9 4a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H3Zm16 0a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2ZM7.757 17.657a1 1 0 1 0-1.414-1.414l-1.414 1.414a1 1 0 1 0 1.414 1.414l1.414-1.414Zm9.9-1.414a1 1 0 0 0-1.414 1.414l1.414 1.414a1 1 0 0 0 1.414-1.414l-1.414-1.414ZM13 19a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0v-2Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="links_name">Theme</span>
                        </a>
                        <span class="tooltip">Theme</span>
                    </li>

                    <li>
                        <a href="accounts.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
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
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
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
                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
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
                <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                    <!-- Success Message -->
                    <?php if ($success): ?>
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Warning Message -->
                    <?php if ($smsWarning): ?>
                        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg">
                            <?php echo $smsWarning; ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- In the SMS Settings Card section, replace with: -->
                        <!-- SMS Settings Card -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">SMS Settings</h2>

                            <?php if ($systemStatus): ?>
                                <div class="mb-4 p-3 <?php echo $systemStatus['smtp_configured'] && !$systemStatus['demo_mode'] ? 'bg-green-100 border border-green-400 text-green-800' : 'bg-yellow-100 border border-yellow-400 text-yellow-800'; ?> rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <?php if ($systemStatus['smtp_configured'] && !$systemStatus['demo_mode']): ?>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            <?php else: ?>
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            <?php endif; ?>
                                        </svg>
                                        <div>
                                            <p class="font-medium">System Status: <?php echo $systemStatus['status']; ?></p>
                                            <p class="text-sm mt-1"><?php echo $systemStatus['message']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="space-y-4">
                                    <!-- Operation Mode -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Operation Mode
                                        </label>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="demo_mode" name="demo_mode" value="1"
                                                <?php echo ($smsSettings['demo_mode'] ?? 0) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="demo_mode" class="ms-2 text-sm font-medium text-gray-900">
                                                Demo Mode (Log only, don't send)
                                            </label>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            When checked: SMS are logged but not actually sent. Uncheck to send real SMS.
                                        </p>
                                    </div>

                                    <!-- Service Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            SMS Service Status
                                        </label>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                                <?php echo ($smsSettings['is_active'] ?? 1) ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="is_active" class="ms-2 text-sm font-medium text-gray-900">
                                                Enable SMS Service
                                            </label>
                                        </div>
                                    </div>

                                    <!-- SMTP Configuration -->
                                    <div class="border-t pt-4">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-3">SMTP Configuration</h3>
                                        <p class="text-sm text-gray-600 mb-3">
                                            Configure SMTP to send real SMS via email-to-SMS gateways
                                        </p>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-2">
                                                    SMTP Host
                                                </label>
                                                <input type="text" id="smtp_host" name="smtp_host"
                                                    value="<?php echo htmlspecialchars($smsSettings['smtp_host'] ?? 'smtp.gmail.com'); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                    placeholder="smtp.gmail.com">
                                            </div>

                                            <div>
                                                <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-2">
                                                    SMTP Port
                                                </label>
                                                <input type="text" id="smtp_port" name="smtp_port"
                                                    value="<?php echo htmlspecialchars($smsSettings['smtp_port'] ?? '587'); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                    placeholder="587">
                                            </div>

                                            <div>
                                                <label for="smtp_user" class="block text-sm font-medium text-gray-700 mb-2">
                                                    SMTP Username/Email
                                                </label>
                                                <input type="text" id="smtp_user" name="smtp_user"
                                                    value="<?php echo htmlspecialchars($smsSettings['smtp_user'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                    placeholder="your-email@gmail.com">
                                            </div>

                                            <div>
                                                <label for="smtp_pass" class="block text-sm font-medium text-gray-700 mb-2">
                                                    SMTP Password
                                                </label>
                                                <input type="password" id="smtp_pass" name="smtp_pass"
                                                    value="<?php echo htmlspecialchars($smsSettings['smtp_pass'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                    placeholder="••••••••">
                                            </div>
                                        </div>

                                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                            <p class="text-sm text-blue-800">
                                                <strong>Gmail Setup:</strong> Use "App Password" not regular password.
                                                Enable 2FA in Google, then generate app password.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Sender ID -->
                                    <div>
                                        <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Sender Name
                                        </label>
                                        <input type="text" id="sender_id" name="sender_id"
                                            value="<?php echo htmlspecialchars($smsSettings['sender_id'] ?? 'MSWDPALUAN'); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                            placeholder="MSWDPALUAN"
                                            maxlength="11">
                                        <p class="mt-1 text-xs text-gray-500">Will appear as sender in SMS (max 11 characters)</p>
                                    </div>

                                    <input type="hidden" name="provider" value="email_sms">
                                    <input type="hidden" name="api_key" value="">

                                    <div class="grid grid-cols-2 gap-3 pt-4 border-t">
                                        <button type="submit" name="save_settings"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                            Save Settings
                                        </button>

                                        <button type="button" onclick="testSMTPConnection()"
                                            class="text-gray-900 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                                            Test SMTP
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Send SMS Card -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Send SMS</h2>
                            <form method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Recipients
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <input type="radio" id="all_active" name="recipient_type" value="all_active" checked
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                                <label for="all_active" class="ms-2 text-sm font-medium text-gray-900">
                                                    All Active Seniors
                                                </label>
                                            </div>
                                            <div>
                                                <input type="radio" id="all_seniors" name="recipient_type" value="all_seniors"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                                <label for="all_seniors" class="ms-2 text-sm font-medium text-gray-900">
                                                    All Seniors
                                                </label>
                                            </div>
                                            <div class="col-span-2">
                                                <input type="radio" id="custom" name="recipient_type" value="custom"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                                <label for="custom" class="ms-2 text-sm font-medium text-gray-900">
                                                    Custom Numbers
                                                </label>
                                                <input type="text" id="custom_numbers" name="custom_numbers"
                                                    class="mt-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                    placeholder="Enter numbers separated by commas (09XXXXXXXXX)"
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="broadcast_message" class="block text-sm font-medium text-gray-700 mb-2">
                                            Message
                                        </label>
                                        <textarea id="broadcast_message" name="broadcast_message" rows="4"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                            placeholder="Enter your message here..."
                                            required></textarea>
                                        <div class="mt-1 flex justify-between">
                                            <span class="text-xs text-gray-500">Maximum 160 characters per SMS</span>
                                            <span id="charCount" class="text-xs text-gray-500">0/160</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="submit" name="send_sms"
                                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                            Send SMS
                                        </button>

                                        <button type="button" onclick="openTestModal()"
                                            class="text-gray-900 bg-yellow-300 hover:bg-yellow-400 focus:ring-4 focus:ring-yellow-200 font-medium rounded-lg text-sm px-5 py-2.5">
                                            Test SMS
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Message Templates Card -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Message Templates</h2>
                            <form method="POST" class="mb-4">
                                <div class="space-y-4">
                                    <div>
                                        <label for="template_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Template Name
                                        </label>
                                        <input type="text" id="template_name" name="template_name"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                            placeholder="e.g., Reminder, Announcement">
                                    </div>

                                    <div>
                                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                            Template Message
                                        </label>
                                        <textarea id="message" name="message" rows="3"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                            placeholder="Enter template message..."></textarea>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" id="template_active" name="template_active" checked
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="template_active" class="ms-2 text-sm font-medium text-gray-900">
                                            Active
                                        </label>
                                    </div>

                                    <button type="submit" name="save_template"
                                        class="w-full text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        Save Template
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Saved Templates</h3>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    <?php if (empty($templates)): ?>
                                        <p class="text-gray-500 text-sm">No templates saved yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <div class="p-3 bg-gray-50 rounded-lg">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($template['template_name']); ?>
                                                            <?php if (!$template['is_active']): ?>
                                                                <span class="ml-2 text-xs text-red-500">(Inactive)</span>
                                                            <?php endif; ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            <?php echo htmlspecialchars(substr($template['message'], 0, 100)); ?>
                                                            <?php echo strlen($template['message']) > 100 ? '...' : ''; ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button type="button" onclick="useTemplate(<?php echo htmlspecialchars(json_encode($template['message'])); ?>)"
                                                            class="text-blue-600 hover:text-blue-800 text-sm">
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

                        <!-- SMS Logs Card -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">SMS Logs</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3">Number</th>
                                            <th class="px-4 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($smsLogs)): ?>
                                            <tr>
                                                <td colspan="3" class="px-4 py-3 text-center text-gray-500">No logs yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($smsLogs as $log): ?>
                                                <tr class="border-b">
                                                    <td class="px-4 py-3">
                                                        <?php echo date('M d, H:i', strtotime($log['sent_at'])); ?>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <?php echo htmlspecialchars(substr($log['phone_number'] ?? 'N/A', 0, 15)); ?>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                            <?php echo (strpos($log['status'], 'sent') !== false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                            <?php echo htmlspecialchars($log['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Test SMS Modal -->
    <div id="testSmsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" onclick="closeTestModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Test SMS</h3>
                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="test_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Test Phone Number
                            </label>
                            <input type="text" id="test_number" name="test_number"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                placeholder="09XXXXXXXXX or +639XXXXXXXXXX" required>
                            <p class="mt-1 text-xs text-gray-500">Enter your own number to test</p>
                        </div>

                        <div>
                            <label for="test_message" class="block text-sm font-medium text-gray-700 mb-2">
                                Test Message
                            </label>
                            <textarea id="test_message" name="test_message" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                placeholder="Enter test message..." required>Test SMS from MSWD System</textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeTestModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" name="test_sms"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                Send Test
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>

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

        // Modal functions
        function openTestModal() {
            const modal = document.getElementById('testSmsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                // Focus on the first input
                setTimeout(() => {
                    const testNumberInput = document.getElementById('test_number');
                    if (testNumberInput) testNumberInput.focus();
                }, 100);
            }
        }

        function closeTestModal() {
            const modal = document.getElementById('testSmsModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('testSmsModal');
            if (modal && !modal.classList.contains('hidden')) {
                const modalContent = modal.querySelector('.relative');
                if (!modalContent.contains(event.target)) {
                    closeTestModal();
                }
            }
        });

        // Test SMTP Connection
        async function testSMTPConnection() {
            const smtpHost = document.querySelector('input[name="smtp_host"]').value.trim();
            const smtpUser = document.querySelector('input[name="smtp_user"]').value.trim();
            const smtpPass = document.querySelector('input[name="smtp_pass"]').value.trim();

            if (!smtpHost || !smtpUser || !smtpPass) {
                alert('Please fill in all SMTP fields first');
                return;
            }

            // Create or find result div
            let resultDiv = document.getElementById('testResult');
            if (!resultDiv) {
                resultDiv = document.createElement('div');
                resultDiv.id = 'testResult';
                resultDiv.className = 'mt-3';
                const form = document.querySelector('form');
                if (form) {
                    form.appendChild(resultDiv);
                }
            }

            resultDiv.innerHTML = '<p class="text-gray-600">Testing SMTP connection...</p>';

            try {
                const formData = new FormData();
                formData.append('smtp_host', smtpHost);
                formData.append('smtp_user', smtpUser);
                formData.append('smtp_pass', smtpPass);
                formData.append('test_smtp', '1');

                const response = await fetch('sms.php', {
                    method: 'POST',
                    body: formData
                });

                // Reload the page to see the result
                window.location.reload();

            } catch (error) {
                resultDiv.innerHTML = `
                <div class="p-3 bg-red-100 border border-red-400 rounded">
                    <p class="text-red-800 font-semibold">❌ Test Failed</p>
                    <p class="text-red-700 text-sm mt-1">${error.message}</p>
                </div>
            `;
            }
        }

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
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-semibold text-blue-800 mb-2">📋 SMS Configuration Guide</h4>
                <ol class="text-sm text-blue-700 space-y-2 list-decimal list-inside">
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
    </script>
</body>

</html>