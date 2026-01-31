<?php
// Debug session
error_log("=== PAGE LOADED ===");
error_log("Page: " . basename(__FILE__));
error_log("Session ID: " . session_id());
error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'none'));
error_log("Staff user ID: " . ($_SESSION['staff_user_id'] ?? 'none'));
error_log("Admin user ID: " . ($_SESSION['admin_user_id'] ?? 'none'));
error_log("Full name: " . ($_SESSION['fullname'] ?? 'none'));
error_log("Username: " . ($_SESSION['username'] ?? 'none'));

require_once "../../php/login/staff_header.php";
require_once '../../php/login/staff_session_sync.php';

// Fix session handling for staff
if (isset($_GET['session_context']) && !empty($_GET['session_context'])) {
    $ctx = $_GET['session_context'];

    if (!isset($_SESSION['session_context'])) {
        $_SESSION['session_context'] = 'Staff';
    }

    if (!isset($_SESSION['user_id']) && isset($user_id) && $user_id > 0) {
        $_SESSION['user_id'] = $user_id;
    }

    if (!isset($_SESSION['fullname']) && !empty($full_name)) {
        $_SESSION['fullname'] = $full_name;
    }
}

$ctx = urlencode($_GET['session_context'] ?? session_id());

$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
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
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}

// Calculate minimum birthdate for 60 years old
$currentDate = new DateTime();
$minBirthDate = new DateTime();
$minBirthDate->modify('-60 years');
$minBirthDateFormatted = $minBirthDate->format('Y-m-d');
$maxBirthDateFormatted = $currentDate->format('Y-m-d');

// Generate unique ID number for staff
function generateStaffID()
{
    $prefix = "SC-STF";
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . "-" . $year . "-" . $random;
}

$generated_id = generateStaffID();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Register Senior Citizen - Staff Panel</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <style>
        /* Enhanced logo styling */
        .highlighted-logo {
            filter:
                brightness(1.3) contrast(1.2) saturate(1.5) drop-shadow(0 0 8px #3b82f6) drop-shadow(0 0 12px rgba(59, 130, 246, 0.7));
            border: 3px solid rgba(59, 130, 246, 0.4);
            border-radius: 12px;
            box-shadow:
                inset 0 0 10px rgba(255, 255, 255, 0.6),
                0 0 20px rgba(59, 130, 246, 0.5);
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

        /* Age validation styling */
        .age-error {
            border-color: #ef4444 !important;
        }

        .age-warning {
            border-color: #f59e0b !important;
        }

        .age-valid {
            border-color: #10b981 !important;
        }

        /* Character validation styling */
        .char-error {
            border-color: #ef4444 !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .mobile-padding {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .step-label {
                font-size: 12px;
            }

            .step-circle {
                width: 28px;
                height: 28px;
                font-size: 14px;
            }

            .step-line {
                margin: 0 5px;
            }
        }

        @media (max-width: 640px) {
            .step-label {
                display: none;
            }
        }

        /* Prevent zoom on focus for mobile devices */
        @media (max-width: 768px) {

            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }

        /* Touch-friendly buttons */
        @media (max-width: 768px) {

            button,
            input[type="button"],
            input[type="submit"],
            .btn {
                min-height: 44px;
                min-width: 44px;
            }
        }

        /* Auto-save indicator */
        .auto-save-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #3b82f6;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .auto-save-indicator.show {
            opacity: 1;
        }

        .auto-save-indicator.saving {
            background: #f59e0b;
        }

        .auto-save-indicator.saved {
            background: #10b981;
        }

        .auto-save-indicator.error {
            background: #ef4444;
        }

        /* Continue session banner */
        #continueSessionBanner {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .continue-session-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .continue-session-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-1px);
        }

        .restore-status {
            display: none;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }

        .restore-status.success {
            display: block;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .restore-status.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>

    <link rel="stylesheet" href="../css/popup.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .iti {
            width: 100%;
        }

        .iti__flag {
            background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags.png");
        }

        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            .iti__flag {
                background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags@2x.png");
            }
        }

        .error-border {
            border-color: #ef4444 !important;
        }

        .success-border {
            border-color: #10b981 !important;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .step-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .step-indicator::-webkit-scrollbar {
            display: none;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
            border: 2px solid #d1d5db;
            flex-shrink: 0;
        }

        .step-circle.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .step-circle.completed {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }

        .step-line {
            flex-grow: 1;
            height: 2px;
            background-color: #d1d5db;
            margin: 0 10px;
            min-width: 20px;
        }

        .step-label {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            white-space: nowrap;
        }

        .step-label.active {
            color: #3b82f6;
        }

        .step-label.completed {
            color: #10b981;
        }

        /* Age validation message */
        .age-validation-message {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
        }

        .age-validation-message.show {
            display: block;
        }

        .age-validation-message.error {
            color: #ef4444;
        }

        .age-validation-message.warning {
            color: #f59e0b;
        }

        .age-validation-message.success {
            color: #10b981;
        }

        /* Character validation message */
        .char-validation-message {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
        }

        .char-validation-message.show {
            display: block;
        }

        .char-validation-message.error {
            color: #ef4444;
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .container-padding {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        /* Date input improvements for mobile */
        @media (max-width: 768px) {
            .date-input-mobile {
                font-size: 16px !important;
                min-height: 44px;
            }

            .date-format-example {
                display: block !important;
                background: #eff6ff;
                border-left: 4px solid #3b82f6;
                padding: 6px 10px;
                margin-top: 4px;
                border-radius: 4px;
            }

            .dark .date-format-example {
                background: #1e293b;
                border-left-color: #60a5fa;
            }
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Auto-save Indicator -->
        <div id="autoSaveIndicator" class="auto-save-indicator">
            <i class="fas fa-spinner fa-spin mr-1"></i>
            <span>Saving...</span>
        </div>

        <!-- Mobile Header -->
        <div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                        aria-controls="drawer-navigation"
                        class="p-2 text-gray-600 rounded-lg cursor-pointer hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Toggle sidebar</span>
                    </button>
                    <a href="#" class="flex items-center justify-between mr-4">
                        <img src="../../img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                            alt="MSWD LOGO" />
                        <span class="self-center text-xl font-semibold whitespace-nowrap text-gray-900 dark:text-white">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <button type="button"
                        class="flex cursor-pointer w-8 h-8 text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="mobile-user-menu-button" aria-expanded="false" data-dropdown-toggle="mobile-dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-full h-full rounded-full object-cover"
                            src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                            alt="user photo" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Desktop Navigation -->
        <nav class="hidden md:block bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
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
                    <a href="#" class="flex items-center justify-between mr-4">
                        <img src="../../img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                            alt="MSWD LOGO" />
                        <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-900 dark:text-white">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 w-8 h-8 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-full h-full rounded-full object-cover"
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
                                    echo 'Staff User';
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

        <!-- Mobile Dropdown -->
        <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl fixed top-16 right-4"
            id="mobile-dropdown">
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
                        echo 'Staff User';
                    }
                    ?>
                </span>
            </div>
            <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="mobile-dropdown">
                <li>
                    <a href="/MSWDPALUAN_SYSTEM-MAIN/php/login/logout.php"
                        class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                        <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
            aria-label="Sidenav" id="drawer-navigation">
            <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">Staff Panel</p>
                <ul class="space-y-2">
                    <li>
                        <a href="staffindex.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <i class="fas fa-user-plus w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
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
                                <a href="staff_activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                                </a>
                            </li>
                            <li>
                                <a href="staff_inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                                </a>
                            </li>
                            <li>
                                <a href="staff_deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="staff_benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Benefits</span>
                        </a>
                    </li>
                    <li>
                        <a href="staff_generate_id.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="staff_report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="staff_profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="p-4 md:ml-64 h-auto pt-14 md:pt-20">
            <div class="max-w-6xl mx-auto container-padding">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 md:p-6 mb-6">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-white">Senior Citizen Registration</h1>
                            <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 mt-1">Fill out the application form below</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                Staff Panel
                            </span>
                            <button id="clearSessionBtn" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Clear Draft
                            </button>
                        </div>
                    </div>

                    <!-- Continue Session Banner -->
                    <div id="continueSessionBanner" class="hidden mb-4 p-3 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-history text-blue-600 dark:text-blue-300 mr-2"></i>
                                <span class="text-sm text-blue-800 dark:text-blue-200">You have unsaved data from a previous session.</span>
                            </div>
                            <button onclick="useRestoredData()" class="continue-session-btn text-xs">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="step-indicator mb-6 md:mb-8">
                        <div class="step-circle active dark:text-white">1</div>
                        <div class="step-label active hidden md:block">Basic Information</div>
                        <div class="step-line"></div>
                        <div class="step-circle dark:text-white">2</div>
                        <div class="step-label dark:text-white hidden md:block">Contact & Address</div>
                        <div class="step-line"></div>
                        <div class="step-circle dark:text-white">3</div>
                        <div class="step-label dark:text-white hidden md:block">Economic Status</div>
                        <div class="step-line"></div>
                        <div class="step-circle dark:text-white">4</div>
                        <div class="step-label dark:text-white hidden md:block">Health & Submit</div>
                    </div>

                    <form id="applicantForm" class="space-y-6 md:space-y-8">
                        <!-- Step 1: Basic Information -->
                        <div id="step1" class="form-step active">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Personal Information</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                    <div>
                                        <label for="lname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Last Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="lname" name="lname" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter last name"
                                            oninput="validateNameInput(this, 'lname')">
                                        <div id="lname_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, hyphens, apostrophes, ñ, and Ñ are allowed</p>
                                    </div>
                                    <div>
                                        <label for="fname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            First Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="fname" name="fname" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter first name"
                                            oninput="validateNameInput(this, 'fname')">
                                        <div id="fname_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, hyphens, apostrophes, dots, ñ, and Ñ are allowed</p>
                                    </div>
                                    <div>
                                        <label for="mname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Middle Name
                                        </label>
                                        <input type="text" id="mname" name="mname"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter middle name"
                                            oninput="validateNameInput(this, 'mname')">
                                        <div id="mname_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, hyphens, apostrophes, dots, ñ, and Ñ are allowed</p>
                                    </div>
                                    <div>
                                        <label for="suffix" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Suffix
                                        </label>
                                        <input type="text" id="suffix" name="suffix"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Jr., Sr., III, etc."
                                            oninput="validateSuffixInput(this)">
                                        <div id="suffix_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Roman numerals, Jr., Sr., II, III, etc.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                    <div>
                                        <label for="gender" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select id="gender" name="gender" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="b_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Birthdate <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date"
                                            id="b_date"
                                            name="b_date"
                                            required
                                            class="date-input-mobile bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="YYYY-MM-DD (e.g., 1950-05-15)"
                                            pattern="\d{4}-\d{2}-\d{2}"
                                            title="Enter date in format: YYYY-MM-DD"
                                            oninput="handleDateInput(this, 'birthdate')"
                                            onblur="validateDateFormat(this, 'birthdate')"
                                            maxlength="10">
                                        <div id="birthdate_validation" class="age-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Minimum age: 60 years old
                                        </p>
                                        <p class="date-format-example mt-1 text-xs text-blue-600 dark:text-blue-400 hidden">
                                            Example: 1950-05-15 (Year-Month-Day)
                                        </p>
                                    </div>
                                    <div>
                                        <label for="age" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Age <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="age" name="age" required readonly
                                            class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <div id="age_validation" class="age-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Calculated automatically</p>
                                    </div>
                                    <div>
                                        <label for="civil_status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Civil Status <span class="text-red-500">*</span>
                                        </label>
                                        <select id="civil_status" name="civil_status" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Status</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Separated">Separated</option>
                                            <option value="Divorced">Divorced</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                                    <div>
                                        <label for="citizenship" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Citizenship <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="citizenship" name="citizenship" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Filipino"
                                            oninput="validateCitizenshipInput(this)">
                                        <div id="citizenship_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters and spaces only</p>
                                    </div>
                                    <div>
                                        <label for="religion" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Religion <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="religion" name="religion" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Roman Catholic"
                                            oninput="validateReligionInput(this)">
                                        <div id="religion_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, and hyphens only</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                                    <div>
                                        <label for="birth_place" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Birthplace <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="birth_place" name="birth_place" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="City, Province"
                                            oninput="validatePlaceInput(this, 'birthplace')">
                                        <div id="birthplace_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, commas, and hyphens only</p>
                                    </div>
                                    <div>
                                        <label for="educational_attainment" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Educational Attainment <span class="text-red-500">*</span>
                                        </label>
                                        <select id="educational_attainment" name="educational_attainment" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Level</option>
                                            <option value="No Formal Education">No Formal Education</option>
                                            <option value="Elementary Level">Elementary Level</option>
                                            <option value="Elementary Graduate">Elementary Graduate</option>
                                            <option value="High School Level">High School Level</option>
                                            <option value="High School Graduate">High School Graduate</option>
                                            <option value="College Level">College Level</option>
                                            <option value="College Graduate">College Graduate</option>
                                            <option value="Post Graduate">Post Graduate</option>
                                            <option value="Vocational">Vocational</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="button" onclick="validateStep1AndNext()"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Contact & Address
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Contact & Address -->
                        <div id="step2" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Contact Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Contact Number
                                        </label>
                                        <input type="tel" id="contact_number" name="contact_number"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Enter phone number">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Format: +639XXXXXXXXX or 09XXXXXXXXX</p>
                                        <div id="contact_number_error" class="mt-1 text-xs text-red-600 hidden"></div>
                                    </div>
                                    <div>
                                        <label for="ip_group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            IP Group
                                        </label>
                                        <input type="text" id="ip_group" name="ip_group"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Indigenous People Group (if applicable)"
                                            oninput="validateIPGroupInput(this)">
                                        <div id="ipgroup_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters and spaces only</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Address Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 mb-4">
                                    <div>
                                        <label for="house_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            House Number
                                        </label>
                                        <input type="text" id="house_no" name="house_no"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., 123"
                                            oninput="validateHouseNoInput(this)">
                                        <div id="houseno_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Numbers, letters, and hyphens only</p>
                                    </div>
                                    <div>
                                        <label for="street" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Street
                                        </label>
                                        <input type="text" id="street" name="street"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., Main Street"
                                            oninput="validateStreetInput(this)">
                                        <div id="street_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, numbers, spaces, and hyphens only</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                                    <div>
                                        <label for="brgy" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Barangay <span class="text-red-500">*</span>
                                        </label>
                                        <select id="brgy" name="brgy" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Barangay</option>
                                            <option value="I - Mapalad">I - Mapalad</option>
                                            <option value="II - Handang Tumulong">II - Handang Tumulong</option>
                                            <option value="III - Silahis ng Pag-asa">III - Silahis ng Pag-asa</option>
                                            <option value="IV - Pag-asa ng Bayan">IV - Pag-asa ng Bayan</option>
                                            <option value="V - Bagong Silang">V - Bagong Silang</option>
                                            <option value="VI - San Jose">VI - San Jose</option>
                                            <option value="VII - Lumang Bayan">VII - Lumang Bayan</option>
                                            <option value="VIII - Marikit">VIII - Marikit</option>
                                            <option value="IX - Tubili">IX - Tubili</option>
                                            <option value="X - Alipaoy">X - Alipaoy</option>
                                            <option value="XI - Harison">XI - Harison</option>
                                            <option value="XII - Mananao">XII - Mananao</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="municipality" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Municipality <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="municipality" name="municipality" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            value="Paluan" readonly>
                                    </div>
                                    <div>
                                        <label for="province" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Province <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="province" name="province" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            value="Occidental Mindoro" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="living_arrangement" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Living Arrangement <span class="text-red-500">*</span>
                                </label>
                                <select id="living_arrangement" name="living_arrangement" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <option value="">Select Arrangement</option>
                                    <option value="Owned">Owned</option>
                                    <option value="Living alone">Living alone</option>
                                    <option value="Living with relatives">Living with relatives</option>
                                    <option value="Rent">Rent</option>
                                </select>
                            </div>

                            <div class="flex flex-col-reverse md:flex-row justify-between gap-3 pt-2">
                                <button type="button" onclick="prevStep(1)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(3)"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Economic Status
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Economic Status -->
                        <div id="step3" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Economic Status</h3>

                                <!-- Pensioner Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Pension Status</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Are you a pensioner?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="is_pensioner_yes" type="radio" value="1" name="is_pensioner"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="is_pensioner_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="is_pensioner_no" type="radio" value="0" name="is_pensioner" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="is_pensioner_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="pension_amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Monthly Pension Amount
                                            </label>
                                            <input type="number" id="pension_amount" name="pension_amount" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="0.00">
                                        </div>
                                        <div>
                                            <label for="pension_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Pension Source
                                            </label>
                                            <select id="pension_source" name="pension_source" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <option value="">Select Source</option>
                                                <option value="GSIS">GSIS</option>
                                                <option value="SSS">SSS</option>
                                                <option value="AFPSLAI">AFPSLAI</option>
                                                <option value="Private">Private</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Income Source Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Income Source</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Permanent Source of Income?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_permanent_income_yes" type="radio" value="1" name="has_permanent_income"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_permanent_income_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_permanent_income_no" type="radio" value="0" name="has_permanent_income" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_permanent_income_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="income_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Source of Income
                                            </label>
                                            <input type="text" id="income_source" name="income_source" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., Business, Farming, etc."
                                                oninput="validateIncomeSourceInput(this)">
                                            <div id="incomesource_validation" class="char-validation-message"></div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, and commas only</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Family Support Section -->
                                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-800 dark:text-white">Family Support</h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Regular Family Support?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_family_support_yes" type="radio" value="1" name="has_family_support"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_family_support_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_family_support_no" type="radio" value="0" name="has_family_support" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_family_support_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="support_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Type of Support
                                            </label>
                                            <input type="text" id="support_type" name="support_type" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., Cash, In-kind"
                                                oninput="validateSupportTypeInput(this)">
                                            <div id="supporttype_validation" class="char-validation-message"></div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, and commas only</p>
                                        </div>
                                        <div>
                                            <label for="support_cash" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Amount/Frequency
                                            </label>
                                            <input type="text" id="support_cash" name="support_cash" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="e.g., ₱5,000/month"
                                                oninput="validateSupportCashInput(this)">
                                            <div id="supportcash_validation" class="char-validation-message"></div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Numbers, currency symbols, letters, and slashes only</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse md:flex-row justify-between gap-3 pt-2">
                                <button type="button" onclick="prevStep(2)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(4)"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Next: Health & Submit
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Health & Submit -->
                        <div id="step4" class="form-step">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Health Condition</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="mb-4">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Existing Illness?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="has_existing_illness_yes" type="radio" value="1" name="has_existing_illness"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_existing_illness_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="has_existing_illness_no" type="radio" value="0" name="has_existing_illness" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="has_existing_illness_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="illness_details" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Illness Details
                                            </label>
                                            <input type="text" id="illness_details" name="illness_details" disabled
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="Specify illness if any"
                                                oninput="validateIllnessDetailsInput(this)">
                                            <div id="illnessdetails_validation" class="char-validation-message"></div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, spaces, commas, and hyphens only</p>
                                        </div>
                                    </div>

                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="mb-4">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Hospitalized in last 6 months?
                                            </label>
                                            <div class="flex space-x-4">
                                                <div class="flex items-center">
                                                    <input id="hospitalized_last6mos_yes" type="radio" value="1" name="hospitalized_last6mos"
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="hospitalized_last6mos_yes" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Yes</label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input id="hospitalized_last6mos_no" type="radio" value="0" name="hospitalized_last6mos" checked
                                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="hospitalized_last6mos_no" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Details -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Registration Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                                    <div>
                                        <label for="date_of_registration" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Date of Registration <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date"
                                            id="date_of_registration"
                                            name="date_of_registration"
                                            required
                                            class="date-input-mobile bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="YYYY-MM-DD (e.g., <?php echo date('Y-m-d'); ?>)"
                                            pattern="\d{4}-\d{2}-\d{2}"
                                            title="Enter date in format: YYYY-MM-DD"
                                            value="<?php echo date('Y-m-d'); ?>"
                                            oninput="handleDateInput(this, 'registration')"
                                            onblur="validateDateFormat(this, 'registration')"
                                            maxlength="10">
                                        <p class="date-format-example mt-1 text-xs text-blue-600 dark:text-blue-400 hidden">
                                            Example: <?php echo date('Y-m-d'); ?> (Year-Month-Day)
                                        </p>
                                    </div>
                                    <div>
                                        <label for="id_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            ID Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="id_number" name="id_number" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="e.g., <?php echo $generated_id; ?>"
                                            value="<?php echo $generated_id; ?>"
                                            oninput="validateIDNumberInput(this)">
                                        <div id="idnumber_validation" class="char-validation-message"></div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Letters, numbers, and hyphens only</p>
                                    </div>
                                    <div>
                                        <label for="local_control_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                            Local Control Number
                                        </label>
                                        <div class="flex">
                                            <input type="text" id="local_control_number" name="local_control_number" readonly
                                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                                value="Auto-generated">
                                            <button type="button" onclick="generateCustomLocalControlNumber()"
                                                class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 font-medium rounded-r-lg text-sm px-4 py-2.5 dark:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                                Custom
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Internal reference number</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden fields for staff tracking -->
                            <input type="hidden" id="staff_user_id" name="staff_user_id" value="<?php echo $_SESSION['staff_user_id'] ?? $_SESSION['user_id']; ?>">
                            <input type="hidden" id="staff_user_name" name="staff_user_name" value="<?php echo htmlspecialchars($full_name); ?>">
                            <input type="hidden" id="request_source" name="request_source" value="staff_register">

                            <div class="flex flex-col-reverse md:flex-row justify-between gap-3 pt-2">
                                <button type="button" onclick="prevStep(3)"
                                    class="text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                    Previous
                                </button>
                                <button type="button" onclick="validateAndSubmitForm()"
                                    class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                                    Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Popup Modal -->
    <div id="popupModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-4 md:p-6 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out"
            id="popupBox">
            <h2 id="popupTitle" class="text-lg md:text-xl font-semibold mb-3 text-gray-800"></h2>
            <p id="popupMessage" class="text-sm md:text-base text-gray-600 mb-4 md:mb-6 leading-relaxed"></p>
            <button id="popupCloseBtn"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] min-w-[44px]">
                OK
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/staff_tailwind.config.js"></script>
    <script src="../../js/staff_theme.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        // ========== DATA PERSISTENCE SYSTEM ==========
        const getSessionId = () => {
            let sessionId = localStorage.getItem('staff_register_session_id');
            if (!sessionId) {
                sessionId = 'staff_reg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('staff_register_session_id', sessionId);
            }
            return sessionId;
        };

        const hasMeaningfulData = (formData) => {
            if (!formData || typeof formData !== 'object') return false;

            const excludedFields = [
                'municipality', 'province', 'date_of_registration',
                'is_pensioner', 'has_permanent_income', 'has_family_support',
                'has_existing_illness', 'hospitalized_last6mos',
                'staff_user_id', 'staff_user_name', 'request_source'
            ];

            const meaningfulFields = Object.keys(formData).filter(key => {
                if (excludedFields.includes(key)) return false;
                const value = formData[key];
                return value &&
                    value.toString().trim() !== '' &&
                    value !== null &&
                    value !== undefined;
            });

            return meaningfulFields.length >= 3;
        };

        const collectFormDataForSave = () => {
            const form = document.getElementById('applicantForm');
            const formData = new FormData(form);
            const data = {};

            formData.forEach((value, key) => {
                if (value && value.toString().trim() !== '') {
                    data[key] = value.toString().trim();
                }
            });

            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            radioGroups.forEach(group => {
                const selected = form.querySelector(`input[name="${group}"]:checked`);
                if (selected) {
                    data[group] = selected.value;
                }
            });

            if (phoneInput) {
                const phoneNumber = phoneInput.getNumber();
                if (phoneNumber && phoneNumber.trim() !== '') {
                    data.contact_number = phoneNumber;
                }
            }

            console.log('Collected form data:', data);
            return data;
        };

        const saveFormData = () => {
            const sessionId = getSessionId();
            const formData = collectFormDataForSave();

            console.log('Saving form data:', formData);

            const saveData = {
                data: formData,
                currentStep: getCurrentStep(),
                timestamp: Date.now(),
                url: window.location.href
            };

            localStorage.setItem(`staff_register_draft_${sessionId}`, JSON.stringify(saveData));
            console.log('Data saved successfully');
            return true;
        };

        const loadSavedData = () => {
            const sessionId = getSessionId();
            const savedData = localStorage.getItem(`staff_register_draft_${sessionId}`);

            if (savedData) {
                try {
                    const parsedData = JSON.parse(savedData);
                    console.log('Loaded saved data:', parsedData);
                    return parsedData;
                } catch (e) {
                    console.error('Error parsing saved data:', e);
                    return null;
                }
            }
            return null;
        };

        const clearSavedData = () => {
            const sessionId = getSessionId();
            localStorage.removeItem(`staff_register_draft_${sessionId}`);
            localStorage.removeItem('staff_register_session_id');
            console.log('Cleared all saved data');
            document.getElementById('continueSessionBanner').classList.add('hidden');
        };

        const restoreFormData = (savedData) => {
            if (!savedData || !savedData.data) {
                console.log('No saved data to restore');
                return false;
            }

            console.log('Restoring form data:', savedData.data);

            const form = document.getElementById('applicantForm');
            let restoredFields = 0;

            try {
                Object.entries(savedData.data).forEach(([key, value]) => {
                    console.log(`Restoring ${key}:`, value);

                    if (key === 'contact_number' && phoneInput) {
                        phoneInput.setNumber(value || '');
                        restoredFields++;
                        console.log(`Phone restored: ${value}`);
                    } else if (key === 'is_pensioner' || key === 'has_permanent_income' ||
                        key === 'has_family_support' || key === 'has_existing_illness' ||
                        key === 'hospitalized_last6mos') {
                        const radio = form.querySelector(`input[name="${key}"][value="${value}"]`);
                        if (radio) {
                            radio.checked = true;
                            const event = new Event('change', {
                                bubbles: true
                            });
                            radio.dispatchEvent(event);
                            restoredFields++;
                            console.log(`Radio ${key} restored: ${value}`);
                        }
                    } else {
                        const element = form.querySelector(`[name="${key}"]`);
                        if (element) {
                            element.value = value;
                            restoredFields++;
                            console.log(`Field ${key} restored: ${value}`);

                            if (element.tagName === 'SELECT') {
                                const event = new Event('change', {
                                    bubbles: true
                                });
                                element.dispatchEvent(event);
                            } else {
                                const event = new Event('input', {
                                    bubbles: true
                                });
                                element.dispatchEvent(event);
                            }

                            if (key === 'b_date') {
                                setTimeout(() => {
                                    calculateAgeWithValidation();
                                }, 100);
                            }
                        }
                    }
                });

                if (savedData.currentStep && savedData.currentStep > 1) {
                    setTimeout(() => {
                        showStep(savedData.currentStep);
                        updateStepIndicators(savedData.currentStep);
                    }, 500);
                }

                console.log(`Restored ${restoredFields} fields`);
                return restoredFields > 0;

            } catch (error) {
                console.error('Error during form restoration:', error);
                return false;
            }
        };

        const checkForSavedData = () => {
            const savedData = loadSavedData();
            const hasData = savedData && savedData.data && Object.keys(savedData.data).length > 0;

            console.log('Check for saved data:', {
                savedDataExists: !!savedData,
                hasData: hasData,
                dataKeys: savedData?.data ? Object.keys(savedData.data) : []
            });

            if (hasData) {
                document.getElementById('continueSessionBanner').classList.remove('hidden');
                return true;
            } else {
                document.getElementById('continueSessionBanner').classList.add('hidden');
                return false;
            }
        };

        const useRestoredData = () => {
            const savedData = loadSavedData();
            if (!savedData) {
                showPopup('No saved data found.', 'error');
                return;
            }

            console.log('Attempting to restore data:', savedData);

            document.getElementById('continueSessionBanner').classList.add('hidden');

            const restoreStatus = document.createElement('div');
            restoreStatus.className = 'restore-status';
            restoreStatus.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Restoring data...';
            document.querySelector('.step-indicator').after(restoreStatus);

            setTimeout(() => {
                const restored = restoreFormData(savedData);

                if (restored) {
                    restoreStatus.className = 'restore-status success';
                    restoreStatus.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Data restored successfully!';
                    showPopup('Previous session data restored successfully!', 'success', false);
                } else {
                    restoreStatus.className = 'restore-status error';
                    restoreStatus.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Failed to restore data';
                    showPopup('Failed to restore previous session data.', 'error');
                    document.getElementById('continueSessionBanner').classList.remove('hidden');
                }

                setTimeout(() => {
                    restoreStatus.remove();
                }, 3000);

            }, 100);
        };

        const autoRestoreData = () => {
            const savedData = loadSavedData();
            if (savedData && savedData.data && Object.keys(savedData.data).length > 0) {
                console.log('Auto-restoring saved data on page load...');

                const oneDayAgo = Date.now() - (24 * 60 * 60 * 1000);
                if (savedData.timestamp && savedData.timestamp > oneDayAgo) {
                    const restored = restoreFormData(savedData);
                    if (restored) {
                        console.log('Auto-restore completed successfully');

                        const autoRestoreMsg = document.createElement('div');
                        autoRestoreMsg.className = 'restore-status success';
                        autoRestoreMsg.innerHTML = '<i class="fas fa-history mr-2"></i> Previous session data has been restored';
                        document.querySelector('.step-indicator').after(autoRestoreMsg);

                        setTimeout(() => {
                            autoRestoreMsg.remove();
                        }, 3000);

                        document.getElementById('continueSessionBanner').classList.add('hidden');
                    }
                } else {
                    console.log('Saved data is too old, clearing...');
                    clearSavedData();
                }
            }
        };

        const showAutoSaveIndicator = (status = 'saving') => {
            const indicator = document.getElementById('autoSaveIndicator');
            if (!indicator) return;

            const icon = indicator.querySelector('i');
            const text = indicator.querySelector('span');

            indicator.className = 'auto-save-indicator show ' + status;

            switch (status) {
                case 'saving':
                    icon.className = 'fas fa-spinner fa-spin mr-1';
                    text.textContent = 'Saving...';
                    break;
                case 'saved':
                    icon.className = 'fas fa-check mr-1';
                    text.textContent = 'Saved';
                    setTimeout(() => {
                        indicator.classList.remove('show');
                    }, 2000);
                    break;
                case 'cleared':
                    icon.className = 'fas fa-trash mr-1';
                    text.textContent = 'Draft cleared';
                    setTimeout(() => {
                        indicator.classList.remove('show');
                    }, 2000);
                    break;
                case 'error':
                    icon.className = 'fas fa-exclamation-triangle mr-1';
                    text.textContent = 'Save failed';
                    setTimeout(() => {
                        indicator.classList.remove('show');
                    }, 3000);
                    break;
            }
        };

        const getCurrentStep = () => {
            const activeStep = document.querySelector('.form-step.active');
            if (activeStep) {
                return parseInt(activeStep.id.replace('step', ''));
            }
            return 1;
        };

        const showStep = (step) => {
            document.querySelectorAll('.form-step').forEach(stepElement => {
                stepElement.classList.remove('active');
            });
            const stepElement = document.getElementById(`step${step}`);
            if (stepElement) {
                stepElement.classList.add('active');
            }
        };

        // ========== INITIALIZATION ==========

        let phoneInput;
        document.addEventListener("DOMContentLoaded", function() {
            console.log('DOM loaded, initializing form...');

            // Initialize phone input
            const phoneInputElement = document.querySelector("#contact_number");
            if (phoneInputElement) {
                phoneInput = window.intlTelInput(phoneInputElement, {
                    initialCountry: "ph",
                    separateDialCode: true,
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                });

                phoneInputElement.addEventListener('blur', validatePhoneNumber);
                phoneInputElement.addEventListener('input', function() {
                    this.classList.remove('error-border', 'success-border');
                    document.getElementById('contact_number_error').classList.add('hidden');
                });
            }

            // Age calculation
            const birthDateInput = document.querySelector('#b_date');
            const ageInput = document.querySelector('#age');
            if (birthDateInput && ageInput) {
                birthDateInput.addEventListener('change', calculateAgeWithValidation);
            }

            // Setup conditional fields
            setupConditionalFields();

            // Mobile dropdown handling
            const mobileUserButton = document.getElementById('mobile-user-menu-button');
            const mobileDropdown = document.getElementById('mobile-dropdown');
            if (mobileUserButton && mobileDropdown) {
                mobileUserButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    mobileDropdown.classList.toggle('hidden');
                });
                document.addEventListener('click', function(e) {
                    if (!mobileDropdown.contains(e.target) && !mobileUserButton.contains(e.target)) {
                        mobileDropdown.classList.add('hidden');
                    }
                });
            }

            // Set registration date to today
            const today = new Date().toISOString().split('T')[0];
            const regDateInput = document.querySelector('#date_of_registration');
            if (regDateInput) {
                regDateInput.value = today;
                validateRegistrationDate();
            }

            // Clear session button
            document.getElementById('clearSessionBtn').addEventListener('click', function() {
                if (confirm('Clear all unsaved form data?')) {
                    clearSavedData();
                    document.getElementById('applicantForm').reset();

                    // Reset ID number to generated value
                    document.getElementById('id_number').value = '<?php echo $generated_id; ?>';

                    showStep(1);
                    updateStepIndicators(1);
                    if (phoneInput) phoneInput.setNumber("");
                    setupConditionalFields();
                    calculateAgeWithValidation();
                    showPopup('Form cleared successfully!', 'success', true);
                }
            });

            // Setup auto-save
            setupAutoSave();

            // Check for saved data and auto-restore
            setTimeout(() => {
                console.log('Checking for saved data...');
                const hasSavedData = checkForSavedData();

                if (hasSavedData) {
                    autoRestoreData();
                }
            }, 500);

            // Save data before page unload
            window.addEventListener('beforeunload', function(e) {
                const formData = collectFormDataForSave();
                if (Object.keys(formData).length > 0) {
                    saveFormData();
                    console.log('Saved data before page unload');
                }
            });
        });

        // ========== AUTO-SAVE CONFIGURATION ==========

        let autoSaveTimeout;
        const setupAutoSave = () => {
            const form = document.getElementById('applicantForm');
            const debouncedSave = debounce(() => {
                saveFormData();
                showAutoSaveIndicator('saved');
            }, 1000);

            form.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('input', debouncedSave);
                element.addEventListener('change', debouncedSave);
            });

            const phoneElement = document.querySelector("#contact_number");
            if (phoneElement) {
                phoneElement.addEventListener('input', debouncedSave);
            }
        };

        const debounce = (func, wait) => {
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(autoSaveTimeout);
                    func(...args);
                };
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(later, wait);
            };
        };

        // ========== VALIDATION FUNCTIONS ==========
        // All validation functions from the admin side should be included here
        // I'm including the most important ones, but you should copy all validation
        // functions from the admin script

        function validateNameInput(input, fieldName) {
            const value = input.value.trim();
            const validationElement = document.getElementById(fieldName + '_validation');

            const validPattern = /^[A-Za-z\u00D1\u00F1\s\-'.]+$/;

            if (fieldName === 'mname' && value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if ((fieldName === 'fname' || fieldName === 'lname') && value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = 'This field is required';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Allowed: letters, spaces, hyphens (-), apostrophes (\'), dots (.), ñ, and Ñ';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validatePhoneNumber() {
            const phoneElement = document.querySelector("#contact_number");
            const errorElement = document.getElementById('contact_number_error');

            if (!phoneInput || !phoneElement) return true;

            const phoneNumber = phoneElement.value.trim();

            if (!phoneNumber) {
                phoneElement.classList.remove('error-border', 'success-border');
                errorElement.classList.add('hidden');
                return true;
            }

            const phPattern = /^(09|\+639)\d{9}$/;
            const cleanedNumber = phoneNumber.replace(/\D/g, '');

            if (!phPattern.test(phoneNumber) && cleanedNumber.length !== 11 && cleanedNumber.length !== 12) {
                phoneElement.classList.add('error-border');
                errorElement.textContent = "Please enter a valid Philippine phone number (09XXXXXXXXX or +639XXXXXXXXX)";
                errorElement.classList.remove('hidden');
                return false;
            }

            if (phoneInput.isValidNumber()) {
                phoneElement.classList.remove('error-border');
                phoneElement.classList.add('success-border');
                errorElement.classList.add('hidden');
                return true;
            } else {
                phoneElement.classList.add('error-border');
                errorElement.textContent = "Please enter a valid phone number";
                errorElement.classList.remove('hidden');
                return false;
            }
        }

        function validateAge() {
            const ageInput = document.querySelector('#age');
            const birthDateInput = document.querySelector('#b_date');
            const birthdateValidation = document.getElementById('birthdate_validation');
            const ageValidation = document.getElementById('age_validation');

            if (!birthDateInput.value) {
                return {
                    isValid: false,
                    message: 'Birthdate is required'
                };
            }

            const dateValue = birthDateInput.value;
            const ageValue = ageInput.value;

            if (!dateValue) {
                return {
                    isValid: false,
                    message: 'Please enter birthdate in YYYY-MM-DD format'
                };
            }

            const datePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
            if (!datePattern.test(dateValue)) {
                return {
                    isValid: false,
                    message: 'Invalid date format. Please use YYYY-MM-DD'
                };
            }

            const [, year, month, day] = dateValue.match(datePattern);
            const birthDate = new Date(year, month - 1, day);
            const today = new Date();

            if (birthDate.getFullYear() != year ||
                birthDate.getMonth() != month - 1 ||
                birthDate.getDate() != day) {
                return {
                    isValid: false,
                    message: 'Invalid date entered'
                };
            }

            if (birthDate > today) {
                return {
                    isValid: false,
                    message: 'Birthdate cannot be in the future'
                };
            }

            const age = parseInt(ageValue);

            if (isNaN(age)) {
                return {
                    isValid: false,
                    message: 'Invalid age calculation. Please check the date'
                };
            }

            if (age < 60) {
                return {
                    isValid: false,
                    message: `Applicant must be 60 years or older (currently ${age} years old)`
                };
            }

            return {
                isValid: true,
                message: `Age requirement satisfied (${age} years old)`
            };
        }

        function calculateAgeWithValidation() {
            console.log("Calculating age with validation...");

            const birthDateInput = document.querySelector('#b_date');
            const ageInput = document.querySelector('#age');
            const birthdateValidation = document.getElementById('birthdate_validation');
            const ageValidation = document.getElementById('age_validation');

            const dateValue = birthDateInput.value;

            if (!dateValue) {
                ageInput.value = '';
                birthdateValidation.textContent = '';
                birthdateValidation.className = 'age-validation-message';
                ageValidation.textContent = '';
                ageValidation.className = 'age-validation-message';
                birthDateInput.classList.remove('age-error', 'age-warning', 'age-valid', 'error-border', 'success-border');
                ageInput.classList.remove('age-error', 'age-warning', 'age-valid');
                console.log("No birthdate entered - clearing validation");
                return;
            }

            const datePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
            if (!datePattern.test(dateValue)) {
                ageInput.value = '';
                birthdateValidation.textContent = 'Invalid date format. Use YYYY-MM-DD';
                birthdateValidation.className = 'age-validation-message error show';
                ageValidation.textContent = 'Invalid date format';
                ageValidation.className = 'age-validation-message error show';
                birthDateInput.classList.add('age-error', 'error-border');
                birthDateInput.classList.remove('age-warning', 'age-valid', 'success-border');
                ageInput.classList.add('age-error');
                ageInput.classList.remove('age-warning', 'age-valid');
                console.log("Invalid date format");
                return;
            }

            const [, year, month, day] = dateValue.match(datePattern);
            const birthDate = new Date(year, month - 1, day);
            const today = new Date();

            if (birthDate.getFullYear() != year ||
                birthDate.getMonth() != month - 1 ||
                birthDate.getDate() != day) {
                ageInput.value = '';
                birthdateValidation.textContent = 'Invalid date';
                birthdateValidation.className = 'age-validation-message error show';
                ageValidation.textContent = 'Invalid date entered';
                ageValidation.className = 'age-validation-message error show';
                birthDateInput.classList.add('age-error', 'error-border');
                birthDateInput.classList.remove('age-warning', 'age-valid', 'success-border');
                ageInput.classList.add('age-error');
                ageInput.classList.remove('age-warning', 'age-valid');
                console.log("Invalid date components");
                return;
            }

            if (birthDate > today) {
                ageInput.value = '';
                birthdateValidation.textContent = 'Birthdate cannot be in the future';
                birthdateValidation.className = 'age-validation-message error show';
                ageValidation.textContent = 'Invalid future date';
                ageValidation.className = 'age-validation-message error show';
                birthDateInput.classList.add('age-error', 'error-border');
                birthDateInput.classList.remove('age-warning', 'age-valid', 'success-border');
                ageInput.classList.add('age-error');
                ageInput.classList.remove('age-warning', 'age-valid');
                console.log("Birthdate is in the future");
                return;
            }

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            ageInput.value = age >= 0 ? age : '';

            const sixtyYearsAgo = new Date();
            sixtyYearsAgo.setFullYear(sixtyYearsAgo.getFullYear() - 60);

            if (birthDate > sixtyYearsAgo) {
                const sixtyYearsAgoFormatted = sixtyYearsAgo.toISOString().split('T')[0];
                birthdateValidation.textContent = `Minimum age is 60 years (born before ${sixtyYearsAgoFormatted})`;
                birthdateValidation.className = 'age-validation-message error show';
                ageValidation.textContent = `Age must be 60 or above (currently ${age})`;
                ageValidation.className = 'age-validation-message error show';
                birthDateInput.classList.add('age-error', 'error-border');
                birthDateInput.classList.remove('age-warning', 'age-valid', 'success-border');
                ageInput.classList.add('age-error');
                ageInput.classList.remove('age-warning', 'age-valid');
                console.log(`Age ${age} is too young`);
            } else if (age >= 60) {
                birthdateValidation.textContent = `✓ Valid senior citizen age (${age} years old)`;
                birthdateValidation.className = 'age-validation-message success show';
                ageValidation.textContent = '✓ Age qualifies for senior citizen benefits';
                ageValidation.className = 'age-validation-message success show';
                birthDateInput.classList.remove('age-error', 'age-warning', 'error-border');
                birthDateInput.classList.add('age-valid', 'success-border');
                ageInput.classList.remove('age-error', 'age-warning');
                ageInput.classList.add('age-valid');
                console.log(`Age ${age} is valid`);
            }
        }

        // Add all other validation functions from the admin script here...
        // (validateStep1AndNext, validateStep3, nextStep, prevStep, etc.)

        // ========== FORM SUBMISSION ==========

        async function submitForm() {
            const submitBtn = document.querySelector('button[onclick="validateAndSubmitForm()"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';

            try {
                const formData = collectFormData();
                if (phoneInput) {
                    formData.contact_number = phoneInput.getNumber();
                }

                const response = await fetch('../../php/register/applicant.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    clearSavedData();
                    showPopup(`Application submitted successfully! ID: ${result.id_number || ''}`, 'success', true);
                } else {
                    showPopup(result.error || 'Submission failed. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Submission error:', error);
                showPopup('Network error. Please check your connection and try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }

        function collectFormData() {
            const form = document.getElementById("applicantForm");
            const formData = new FormData(form);
            const data = {};

            formData.forEach((value, key) => {
                if (value.trim() === '' && (key === 'mname' || key === 'suffix' || key === 'ip_group' ||
                        key === 'house_no' || key === 'street')) {
                    data[key] = null;
                } else if (value.trim() === '' && key === 'local_control_number') {
                    data[key] = null;
                } else {
                    data[key] = value.trim();
                }
            });

            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            radioGroups.forEach(group => {
                const selected = form.querySelector(`input[name="${group}"]:checked`);
                if (selected) {
                    data[group] = selected.value;
                } else {
                    data[group] = '0';
                }
            });

            data.date_of_registration = document.getElementById('date_of_registration').value;
            data.id_number = document.getElementById('id_number').value;

            const lcn = document.getElementById('local_control_number').value;
            if (!lcn || lcn === 'Auto-generated' || lcn.trim() === '') {
                data.local_control_number = null;
            } else {
                data.local_control_number = lcn.trim();
            }

            const conditionalFields = ['pension_amount', 'pension_source', 'income_source', 'support_type', 'support_cash', 'illness_details'];
            conditionalFields.forEach(field => {
                const input = document.getElementById(field);
                if (input && input.disabled && (!input.value || input.value.trim() === '')) {
                    data[field] = null;
                }
            });

            // Staff-specific fields
            data.staff_user_id = <?php echo json_encode($_SESSION['staff_user_id'] ?? $_SESSION['user_id'] ?? 0); ?>;
            data.staff_user_name = <?php echo json_encode($full_name); ?>;
            data.session_context = <?php echo json_encode($ctx ?? ''); ?>;
            data.request_source = 'staff_register';

            data.base_url = '/MSWDPALUAN_SYSTEM-MAIN/';

            return data;
        }

        // ========== POPUP FUNCTION ==========

        function showPopup(message, type = "info", resetForm = false) {
            const modal = document.getElementById("popupModal");
            const box = document.getElementById("popupBox");
            const title = document.getElementById("popupTitle");
            const msg = document.getElementById("popupMessage");
            const closeBtn = document.getElementById("popupCloseBtn");

            msg.textContent = message;

            if (type === "success") {
                title.textContent = "✅ Success";
                title.className = "text-lg md:text-xl font-semibold mb-3 text-green-600 dark:text-green-400";
                closeBtn.className = "px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all focus:outline-none focus:ring-2 focus:ring-green-400 min-h-[44px] min-w-[44px]";
            } else if (type === "error") {
                title.textContent = "⚠️ Error";
                title.className = "text-lg md:text-xl font-semibold mb-3 text-red-600";
                closeBtn.className = "px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-all focus:outline-none focus:ring-2 focus:ring-red-400 min-h-[44px] min-w-[44px]";
            } else if (type === "warning") {
                title.textContent = "⚠️ Warning";
                title.className = "text-lg md:text-xl font-semibold mb-3 text-yellow-600";
                closeBtn.className = "px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-all focus:outline-none focus:ring-2 focus:ring-yellow-400 min-h-[44px] min-w-[44px]";
            } else {
                title.textContent = "ℹ️ Information";
                title.className = "text-lg md:text-xl font-semibold mb-3 text-blue-600";
                closeBtn.className = "px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] min-w-[44px]";
            }

            modal.classList.remove("hidden");
            setTimeout(() => {
                box.classList.remove("scale-95", "opacity-0");
                box.classList.add("scale-100", "opacity-100");
            }, 10);

            closeBtn.onclick = () => {
                box.classList.add("scale-95", "opacity-0");
                setTimeout(() => {
                    modal.classList.add("hidden");

                    if (type === "success" && resetForm) {
                        document.getElementById("applicantForm").reset();

                        // Reset ID number to generated value
                        document.getElementById('id_number').value = '<?php echo $generated_id; ?>';

                        document.querySelectorAll('.form-step').forEach(step => {
                            step.classList.remove('active');
                        });
                        document.getElementById('step1').classList.add('active');
                        updateStepIndicators(1);

                        if (phoneInput) {
                            phoneInput.setNumber("");
                        }

                        setupConditionalFields();
                        calculateAgeWithValidation();

                        const birthDateInput = document.querySelector('#b_date');
                        const ageInput = document.querySelector('#age');
                        birthDateInput.classList.remove('age-error', 'age-warning', 'age-valid');
                        ageInput.classList.remove('age-error', 'age-warning', 'age-valid');

                        const birthdateValidation = document.getElementById('birthdate_validation');
                        const ageValidation = document.getElementById('age_validation');
                        birthdateValidation.textContent = '';
                        birthdateValidation.className = 'age-validation-message';
                        ageValidation.textContent = '';
                        ageValidation.className = 'age-validation-message';
                    }
                }, 200);
            };
        }

        // ========== ADDITIONAL VALIDATION FUNCTIONS ==========

        function validateSuffixInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('suffix_validation');

            // Allow Roman numerals, Jr., Sr., II, III, etc.
            const validPattern = /^[I|V|X|L|C|D|M]+$|^Jr\.?$|^Sr\.?$|^I+$/i;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Valid suffixes: Jr, Sr, I, II, III, IV, V, etc.';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateCitizenshipInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('citizenship_validation');

            // Allow letters and spaces only
            const validPattern = /^[A-Za-z\s]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = 'This field is required';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters and spaces are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateReligionInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('religion_validation');

            // Allow letters, spaces, and hyphens
            const validPattern = /^[A-Za-z\s\-]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = 'This field is required';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, spaces, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validatePlaceInput(input, fieldName) {
            const value = input.value.trim();
            const validationElement = document.getElementById(fieldName + '_validation');

            // Allow letters, spaces, commas, and hyphens for places
            const validPattern = /^[A-Za-z\s\-,]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = 'This field is required';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, spaces, commas, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateIPGroupInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('ipgroup_validation');

            // Allow letters and spaces only
            const validPattern = /^[A-Za-z\s]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters and spaces are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateHouseNoInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('houseno_validation');

            // Allow numbers, letters, and hyphens
            const validPattern = /^[A-Za-z0-9\-]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, numbers, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateStreetInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('street_validation');

            // Allow letters, numbers, spaces, and hyphens
            const validPattern = /^[A-Za-z0-9\s\-]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, numbers, spaces, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateIncomeSourceInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('incomesource_validation');

            // Allow letters, spaces, and commas
            const validPattern = /^[A-Za-z\s,]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, spaces, and commas are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateSupportTypeInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('supporttype_validation');

            // Allow letters, spaces, and commas
            const validPattern = /^[A-Za-z\s,]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, spaces, and commas are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateSupportCashInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('supportcash_validation');

            // Allow numbers, currency symbols, letters, slashes
            const validPattern = /^[₱$\d\s\/A-Za-z,.-]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only numbers, currency symbols, letters, spaces, commas, periods, and slashes are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateIllnessDetailsInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('illnessdetails_validation');

            // Allow letters, spaces, commas, and hyphens
            const validPattern = /^[A-Za-z\s\-,]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = '';
                validationElement.className = 'char-validation-message';
                return true;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, spaces, commas, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        function validateIDNumberInput(input) {
            const value = input.value.trim();
            const validationElement = document.getElementById('idnumber_validation');

            // Allow letters, numbers, and hyphens
            const validPattern = /^[A-Za-z0-9\-]+$/;

            if (value === '') {
                input.classList.remove('char-error');
                validationElement.textContent = 'This field is required';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            if (!validPattern.test(value)) {
                input.classList.add('char-error');
                validationElement.textContent = 'Only letters, numbers, and hyphens are allowed';
                validationElement.className = 'char-validation-message error show';
                return false;
            }

            input.classList.remove('char-error');
            validationElement.textContent = '';
            validationElement.className = 'char-validation-message';
            return true;
        }

        // ========== STEP NAVIGATION FUNCTIONS ==========

        function validateStep1AndNext() {
            console.log("Validating Step 1...");

            const nameFields = [{
                    id: 'lname',
                    name: 'Last Name',
                    required: true,
                    func: (input) => validateNameInput(input, 'lname')
                },
                {
                    id: 'fname',
                    name: 'First Name',
                    required: true,
                    func: (input) => validateNameInput(input, 'fname')
                },
                {
                    id: 'mname',
                    name: 'Middle Name',
                    required: false,
                    func: (input) => validateNameInput(input, 'mname')
                },
                {
                    id: 'suffix',
                    name: 'Suffix',
                    required: false,
                    func: validateSuffixInput
                },
                {
                    id: 'citizenship',
                    name: 'Citizenship',
                    required: true,
                    func: validateCitizenshipInput
                },
                {
                    id: 'religion',
                    name: 'Religion',
                    required: true,
                    func: validateReligionInput
                },
                {
                    id: 'birth_place',
                    name: 'Birthplace',
                    required: true,
                    func: (input) => validatePlaceInput(input, 'birthplace')
                }
            ];

            let allValid = true;
            let errorMessages = [];
            let firstInvalidField = null;

            // Step 1: Check REQUIRED fields only
            const step1Required = ['lname', 'fname', 'gender', 'b_date', 'age', 'civil_status', 'citizenship', 'religion', 'birth_place', 'educational_attainment'];

            step1Required.forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element && element.required && !element.disabled) {
                    const value = element.value.trim();

                    if (fieldId === 'age') return;

                    if (value === '') {
                        allValid = false;
                        const fieldName = element.previousElementSibling?.textContent?.replace('*', '').trim() ||
                            element.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                        errorMessages.push(`${fieldName} is required`);

                        if (!firstInvalidField) {
                            firstInvalidField = element;
                        }

                        element.classList.add('error-border');
                    } else {
                        element.classList.remove('error-border');
                    }
                }
            });

            // Special validation for birthdate
            const birthDateInput = document.querySelector('#b_date');
            const birthDateValue = birthDateInput ? birthDateInput.value.trim() : '';

            if (birthDateValue) {
                const ageValidation = validateAge();
                if (!ageValidation.isValid) {
                    allValid = false;
                    errorMessages.push(ageValidation.message);

                    if (!firstInvalidField && birthDateInput) {
                        firstInvalidField = birthDateInput;
                    }

                    birthDateInput.classList.add('age-error', 'error-border');
                } else {
                    birthDateInput.classList.remove('age-error', 'error-border');
                }
            } else if (birthDateInput) {
                allValid = false;
                errorMessages.push('Birthdate is required');
                if (!firstInvalidField) firstInvalidField = birthDateInput;
                birthDateInput.classList.add('error-border');
            }

            // Validate character inputs
            nameFields.forEach(field => {
                const input = document.getElementById(field.id);
                if (input && !input.disabled) {
                    const value = input.value.trim();

                    if (field.required) {
                        const isValid = field.func(input);
                        if (!isValid) {
                            allValid = false;

                            const validationElement = document.getElementById(field.id + '_validation');
                            const errorText = validationElement?.textContent || `Invalid ${field.name}`;

                            if (!errorMessages.some(msg => msg.includes(field.name))) {
                                errorMessages.push(`${field.name}: ${errorText}`);
                            }

                            if (!firstInvalidField) {
                                firstInvalidField = input;
                            }
                        } else {
                            input.classList.remove('char-error', 'error-border');
                        }
                    } else if (value !== '') {
                        const isValid = field.func(input);
                        if (!isValid) {
                            allValid = false;

                            const validationElement = document.getElementById(field.id + '_validation');
                            const errorText = validationElement?.textContent || `Invalid ${field.name}`;

                            if (!errorMessages.some(msg => msg.includes(field.name))) {
                                errorMessages.push(`${field.name}: ${errorText}`);
                            }

                            if (!firstInvalidField) {
                                firstInvalidField = input;
                            }
                        } else {
                            input.classList.remove('char-error', 'error-border');
                        }
                    } else {
                        input.classList.remove('char-error', 'error-border');
                        const validationElement = document.getElementById(field.id + '_validation');
                        if (validationElement) {
                            validationElement.textContent = '';
                            validationElement.className = 'char-validation-message';
                        }
                    }
                }
            });

            // Validate other Step 1 specific fields
            const gender = document.getElementById('gender');
            const civilStatus = document.getElementById('civil_status');
            const educationalAttainment = document.getElementById('educational_attainment');

            if (gender && gender.value === '') {
                allValid = false;
                errorMessages.push('Gender is required');
                if (!firstInvalidField) firstInvalidField = gender;
                gender.classList.add('error-border');
            } else if (gender) {
                gender.classList.remove('error-border');
            }

            if (civilStatus && civilStatus.value === '') {
                allValid = false;
                errorMessages.push('Civil Status is required');
                if (!firstInvalidField) firstInvalidField = civilStatus;
                civilStatus.classList.add('error-border');
            } else if (civilStatus) {
                civilStatus.classList.remove('error-border');
            }

            if (educationalAttainment && educationalAttainment.value === '') {
                allValid = false;
                errorMessages.push('Educational Attainment is required');
                if (!firstInvalidField) firstInvalidField = educationalAttainment;
                educationalAttainment.classList.add('error-border');
            } else if (educationalAttainment) {
                educationalAttainment.classList.remove('error-border');
            }

            // Show error message if validation failed
            if (!allValid) {
                const uniqueErrors = [...new Set(errorMessages)];

                const errorMessage = uniqueErrors.length <= 3 ?
                    uniqueErrors.join('\n') :
                    uniqueErrors.slice(0, 3).join('\n') + '\n...and ' + (uniqueErrors.length - 3) + ' more';

                showPopup(errorMessage, 'error');

                if (firstInvalidField) {
                    firstInvalidField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalidField.focus();
                }

                console.log("Step 1 validation failed:", uniqueErrors);
                return false;
            }

            console.log("Step 1 validation passed!");

            // If all validations pass, proceed to next step
            nextStep(2);
            return true;
        }

        function nextStep(step) {
            console.log(`Moving to step ${step}`);

            // Validate current step before proceeding
            const currentStep = step - 1;

            // Check if we're going from step 2 to 3
            if (currentStep === 2) {
                console.log("Validating Step 2 before moving to Step 3");

                // Step 2 validation
                const step2 = document.getElementById('step2');
                const requiredStep2 = step2.querySelectorAll('[required]:not([disabled])');
                let step2Valid = true;
                let step2Errors = [];

                requiredStep2.forEach(input => {
                    if (input.value.trim() === '') {
                        step2Valid = false;
                        const label = input.previousElementSibling?.textContent?.replace('*', '').trim() || input.name;
                        step2Errors.push(label);
                        input.classList.add('error-border');
                    } else {
                        input.classList.remove('error-border');
                    }
                });

                // Validate phone number for Step 2 ONLY IF PROVIDED
                const contactInput = document.querySelector('#contact_number');
                const contactValue = contactInput ? contactInput.value.trim() : '';

                if (contactValue && !validatePhoneNumber()) {
                    step2Valid = false;
                    step2Errors.push('Contact Number');
                }

                if (!step2Valid) {
                    showPopup(`Please complete: ${[...new Set(step2Errors)].join(', ')}`, 'error');

                    // Scroll to first error
                    const firstError = step2.querySelector('.error-border');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstError.focus();
                    }

                    console.log("Step 2 validation failed");
                    return;
                }

                console.log("Step 2 validation passed, proceeding to Step 3");
            }

            // Check if we're going from step 3 to 4
            if (currentStep === 3) {
                console.log("Validating Step 3 before moving to Step 4");
                if (!validateStep3()) {
                    console.log("Step 3 validation failed, not proceeding");
                    return;
                }
                console.log("Step 3 validation passed, proceeding to Step 4");
            }

            // Hide current step
            document.querySelectorAll('.form-step').forEach(stepElement => {
                stepElement.classList.remove('active');
            });

            // Show next step
            const nextStepElement = document.getElementById(`step${step}`);
            if (nextStepElement) {
                nextStepElement.classList.add('active');

                // Update step indicators
                updateStepIndicators(step);

                // Scroll to top on mobile
                if (window.innerWidth < 768) {
                    window.scrollTo({
                        top: document.querySelector('.step-indicator').offsetTop - 80,
                        behavior: 'smooth'
                    });
                }

                console.log(`Successfully moved to step ${step}`);
            } else {
                console.error(`Step ${step} element not found`);
            }
        }

        function validateStep3() {
            console.log("Validating Step 3...");

            // Step 3 has no required fields that need character validation
            // Only check if radio groups have selections
            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            let missingGroups = [];

            // Check if any radio group doesn't have a selection
            for (const group of radioGroups) {
                const selected = document.querySelectorAll(`input[name="${group}"]:checked`);
                console.log(`Group ${group}: selected count = ${selected.length}`);

                if (selected.length === 0) {
                    missingGroups.push(group.replace('_', ' '));
                }
            }

            // Check conditional fields only if their parent radio is "Yes"
            const conditionalFields = [{
                    id: 'pension_amount',
                    dependsOn: 'is_pensioner',
                    dependsOnValue: '1'
                },
                {
                    id: 'pension_source',
                    dependsOn: 'is_pensioner',
                    dependsOnValue: '1'
                },
                {
                    id: 'income_source',
                    dependsOn: 'has_permanent_income',
                    dependsOnValue: '1'
                },
                {
                    id: 'support_type',
                    dependsOn: 'has_family_support',
                    dependsOnValue: '1'
                },
                {
                    id: 'support_cash',
                    dependsOn: 'has_family_support',
                    dependsOnValue: '1'
                },
                {
                    id: 'illness_details',
                    dependsOn: 'has_existing_illness',
                    dependsOnValue: '1'
                }
            ];

            let missingConditionalFields = [];

            for (const field of conditionalFields) {
                const input = document.getElementById(field.id);
                const dependentRadio = document.querySelector(`input[name="${field.dependsOn}"]:checked`);

                // If radio is selected as "Yes" but the field is empty
                if (dependentRadio && dependentRadio.value === field.dependsOnValue &&
                    input && !input.disabled && input.value.trim() === '') {
                    missingConditionalFields.push(field.id.replace('_', ' '));

                    // Highlight the field
                    input.classList.add('error-border');
                } else if (input) {
                    // Remove error highlighting if not applicable
                    input.classList.remove('error-border');
                }
            }

            // If there are issues, show error and stop
            if (missingGroups.length > 0 || missingConditionalFields.length > 0) {
                let errorMessages = [];

                if (missingGroups.length > 0) {
                    errorMessages.push(`Please select: ${missingGroups.join(', ')}`);
                }

                if (missingConditionalFields.length > 0) {
                    errorMessages.push(`Please fill: ${missingConditionalFields.join(', ')} when "Yes" is selected`);
                }

                // Show the error
                showPopup(errorMessages.join('\n'), 'error');

                // Scroll to the first issue
                const firstRadioGroup = document.querySelector(`input[name="${missingGroups[0] || radioGroups[0]}"]`);
                if (firstRadioGroup) {
                    firstRadioGroup.closest('.p-4')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                console.log("Step 3 validation failed:", errorMessages);
                return false;
            }

            console.log("Step 3 validation passed");
            return true;
        }

        function prevStep(step) {
            // Hide current step
            document.querySelectorAll('.form-step').forEach(stepElement => {
                stepElement.classList.remove('active');
            });

            // Show previous step
            document.getElementById(`step${step}`).classList.add('active');

            // Update step indicators
            updateStepIndicators(step);

            // Scroll to top on mobile
            if (window.innerWidth < 768) {
                window.scrollTo({
                    top: document.querySelector('.step-indicator').offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        }

        function updateStepIndicators(currentStep) {
            const steps = document.querySelectorAll('.step-circle');
            const labels = document.querySelectorAll('.step-label');

            steps.forEach((circle, index) => {
                const stepNumber = index + 1;
                if (stepNumber < currentStep) {
                    circle.classList.remove('active');
                    circle.classList.add('completed');
                    if (labels[index]) {
                        labels[index].classList.remove('active');
                        labels[index].classList.add('completed');
                    }
                } else if (stepNumber === currentStep) {
                    circle.classList.add('active');
                    circle.classList.remove('completed');
                    if (labels[index]) {
                        labels[index].classList.add('active');
                        labels[index].classList.remove('completed');
                    }
                } else {
                    circle.classList.remove('active', 'completed');
                    if (labels[index]) {
                        labels[index].classList.remove('active', 'completed');
                    }
                }
            });
        }

        function validateStep(stepNumber) {
            // Don't validate Step 3 with the generic validator
            if (stepNumber === 3) {
                return validateStep3();
            }

            const step = document.getElementById(`step${stepNumber}`);
            let isValid = true;
            let firstError = null;
            let errorMessages = [];

            // Get all required inputs in this step
            const requiredInputs = step.querySelectorAll('[required]');

            requiredInputs.forEach(input => {
                // Skip disabled inputs
                if (input.disabled) return;

                const value = input.value.trim();
                const isEmpty = value === '';

                // Special validation for birthdate and age in step 1
                if (stepNumber === 1) {
                    if (input.id === 'b_date') {
                        const dateValue = input.value;
                        if (!dateValue) {
                            isValid = false;
                            if (!firstError) firstError = input;
                            errorMessages.push('Birthdate is required');
                            input.classList.add('error-border');
                            return;
                        }

                        // Validate age
                        const ageValidation = validateAge();
                        if (!ageValidation.isValid) {
                            isValid = false;
                            if (!firstError) firstError = input;
                            errorMessages.push(ageValidation.message);
                            input.classList.add('age-error');
                            return;
                        }
                    } else if (input.id === 'age') {
                        // Age is calculated automatically, just skip
                        return;
                    }
                }

                // Special validation for contact number in step 2
                if (stepNumber === 2 && input.id === 'contact_number') {
                    if (!validatePhoneNumber()) {
                        isValid = false;
                        if (!firstError) firstError = input;
                        errorMessages.push('Please enter a valid contact number');
                        input.classList.add('error-border');
                        return;
                    } else {
                        input.classList.remove('error-border');
                    }
                } else if (isEmpty) {
                    isValid = false;
                    if (!firstError) firstError = input;

                    // Get the field label for better error message
                    const label = input.previousElementSibling?.textContent?.replace('*', '').trim() || input.name;
                    errorMessages.push(`${label} is required`);

                    input.classList.add('error-border');
                } else {
                    input.classList.remove('error-border');
                }
            });

            // Scroll to first error if any
            if (!isValid && firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstError.focus();

                // Show combined error message
                const uniqueErrors = [...new Set(errorMessages)];
                showPopup(uniqueErrors.join('\n'), 'error');
            }

            return isValid;
        }

        function generateCustomLocalControlNumber() {
            const customNumber = prompt("Enter custom local control number:", "");
            const lcnInput = document.getElementById('local_control_number');

            if (customNumber && customNumber.trim() !== "") {
                lcnInput.value = customNumber.trim();
                lcnInput.readOnly = false;
                lcnInput.classList.remove('bg-gray-100');
                lcnInput.classList.add('bg-yellow-50', 'border-yellow-300');
            }
        }

        // ========== CONDITIONAL FIELD SETUP ==========

        function setupConditionalFields() {
            // Pensioner fields
            const pensionerRadios = document.querySelectorAll('input[name="is_pensioner"]');
            const pensionAmount = document.querySelector('#pension_amount');
            const pensionSource = document.querySelector('#pension_source');

            pensionerRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const isPensioner = this.value === '1';
                    pensionAmount.disabled = !isPensioner;
                    pensionSource.disabled = !isPensioner;

                    if (!isPensioner) {
                        pensionAmount.value = '';
                        pensionSource.value = '';
                    }
                });
            });

            // Permanent Income fields
            const incomeRadios = document.querySelectorAll('input[name="has_permanent_income"]');
            const incomeSource = document.querySelector('#income_source');

            incomeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIncome = this.value === '1';
                    incomeSource.disabled = !hasIncome;

                    if (!hasIncome) {
                        incomeSource.value = '';
                    }
                });
            });

            // Family Support fields
            const supportRadios = document.querySelectorAll('input[name="has_family_support"]');
            const supportType = document.querySelector('#support_type');
            const supportCash = document.querySelector('#support_cash');

            supportRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasSupport = this.value === '1';
                    supportType.disabled = !hasSupport;
                    supportCash.disabled = !hasSupport;

                    if (!hasSupport) {
                        supportType.value = '';
                        supportCash.value = '';
                    }
                });
            });

            // Illness fields
            const illnessRadios = document.querySelectorAll('input[name="has_existing_illness"]');
            const illnessDetails = document.querySelector('#illness_details');

            illnessRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const hasIllness = this.value === '1';
                    illnessDetails.disabled = !hasIllness;

                    if (!hasIllness) {
                        illnessDetails.value = '';
                    }
                });
            });
        }

        // ========== DATE HANDLING FUNCTIONS ==========

        function handleDateInput(input, type) {
            let value = input.value.replace(/[^\d-]/g, ''); // Only allow digits and hyphens

            // Auto-insert hyphens
            if (value.length >= 4 && value[4] !== '-') {
                value = value.substring(0, 4) + '-' + value.substring(4);
            }
            if (value.length >= 7 && value[7] !== '-') {
                value = value.substring(0, 7) + '-' + value.substring(7);
            }

            // Limit length
            if (value.length > 10) {
                value = value.substring(0, 10);
            }

            input.value = value;

            // Validate if complete date is entered
            if (value.length === 10) {
                validateDateFormat(input, type);
            }
        }

        function validateDateFormat(input, type) {
            const value = input.value.trim();

            if (!value) {
                input.classList.remove('success-border', 'error-border');
                return;
            }

            // Check format
            const datePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
            if (!datePattern.test(value)) {
                input.classList.add('error-border');
                input.classList.remove('success-border');
                return;
            }

            // Validate date components
            const [, year, month, day] = value.match(datePattern);
            const date = new Date(year, month - 1, day);

            // Check if date is valid
            if (date.getFullYear() != year ||
                date.getMonth() != month - 1 ||
                date.getDate() != day) {
                input.classList.add('error-border');
                input.classList.remove('success-border');
                return;
            }

            // Date is valid
            input.classList.add('success-border');
            input.classList.remove('error-border');

            // Trigger specific validations
            if (type === 'birthdate') {
                calculateAgeWithValidation();
            } else if (type === 'registration') {
                validateRegistrationDate();
            }
        }

        function validateRegistrationDate() {
            const regDateInput = document.querySelector('#date_of_registration');
            const value = regDateInput.value;

            if (!value) {
                regDateInput.classList.remove('error-border', 'success-border');
                return;
            }

            // Validate date format
            const datePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
            if (!datePattern.test(value)) {
                regDateInput.classList.add('error-border');
                regDateInput.classList.remove('success-border');
                return;
            }

            const [, year, month, day] = value.match(datePattern);
            const regDate = new Date(year, month - 1, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Check if date is valid
            if (regDate.getFullYear() != year ||
                regDate.getMonth() != month - 1 ||
                regDate.getDate() != day) {
                regDateInput.classList.add('error-border');
                regDateInput.classList.remove('success-border');
                return;
            }

            // Registration date cannot be in the future
            if (regDate > today) {
                regDateInput.classList.add('error-border');
                regDateInput.classList.remove('success-border');
                showPopup('Registration date cannot be in the future', 'error');
            } else {
                regDateInput.classList.add('success-border');
                regDateInput.classList.remove('error-border');
            }
        }

        // ========== FORM SUBMISSION VALIDATION ==========

        function validateAndSubmitForm() {
            // First validate required fields before checking character inputs
            const requiredFields = [
                'lname', 'fname', 'gender', 'b_date', 'age',
                'civil_status', 'citizenship', 'religion', 'birth_place',
                'educational_attainment', 'brgy', 'municipality', 'province',
                'living_arrangement', 'date_of_registration', 'id_number'
            ];

            let missingFields = [];
            let firstMissingField = null;

            // Check all required fields first
            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element && !element.disabled) {
                    const value = element.value.trim();
                    if (!value && element.required) {
                        missingFields.push(field.replace('_', ' '));
                        if (!firstMissingField) {
                            firstMissingField = element;
                        }
                    }
                }
            });

            // Special validation for radio groups
            const radioGroups = ['is_pensioner', 'has_permanent_income', 'has_family_support', 'has_existing_illness', 'hospitalized_last6mos'];
            radioGroups.forEach(group => {
                const radios = document.querySelectorAll(`input[name="${group}"]:checked`);
                if (radios.length === 0) {
                    missingFields.push(group.replace('_', ' '));
                }
            });

            // If there are missing fields, show error for those first
            if (missingFields.length > 0) {
                showPopup(`Please complete the following required fields: ${missingFields.join(', ')}`, 'error');

                // Highlight and focus on the first missing field
                if (firstMissingField) {
                    firstMissingField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstMissingField.focus();
                    firstMissingField.classList.add('error-border');
                }

                return;
            }

            // Then validate phone number ONLY IF PROVIDED
            const contactInput = document.querySelector('#contact_number');
            const contactValue = contactInput ? contactInput.value.trim() : '';

            // Only validate if phone number is provided (not empty)
            if (contactValue && !validatePhoneNumber()) {
                contactInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                contactInput.focus();
                return;
            }

            // Then validate age (but don't show error if other fields are missing)
            const ageValidation = validateAge();
            if (!ageValidation.isValid) {
                showPopup(ageValidation.message, 'error');
                const birthDateInput = document.querySelector('#b_date');
                birthDateInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                birthDateInput.focus();
                return;
            }

            // Finally validate character inputs - EXCLUDE optional fields when empty
            const fieldsToValidate = [{
                    id: 'lname',
                    func: (input) => validateNameInput(input, 'lname')
                },
                {
                    id: 'fname',
                    func: (input) => validateNameInput(input, 'fname')
                },
                {
                    id: 'mname',
                    func: (input) => validateNameInput(input, 'mname'),
                    optional: true
                },
                {
                    id: 'suffix',
                    func: validateSuffixInput,
                    optional: true
                },
                {
                    id: 'citizenship',
                    func: validateCitizenshipInput
                },
                {
                    id: 'religion',
                    func: validateReligionInput
                },
                {
                    id: 'birth_place',
                    func: (input) => validatePlaceInput(input, 'birthplace')
                },
                {
                    id: 'ip_group',
                    func: validateIPGroupInput,
                    optional: true
                },
                {
                    id: 'house_no',
                    func: validateHouseNoInput,
                    optional: true
                },
                {
                    id: 'street',
                    func: validateStreetInput,
                    optional: true
                },
                {
                    id: 'income_source',
                    func: validateIncomeSourceInput,
                    optional: true
                },
                {
                    id: 'support_type',
                    func: validateSupportTypeInput,
                    optional: true
                },
                {
                    id: 'support_cash',
                    func: validateSupportCashInput,
                    optional: true
                },
                {
                    id: 'illness_details',
                    func: validateIllnessDetailsInput,
                    optional: true
                },
                {
                    id: 'id_number',
                    func: validateIDNumberInput
                }
            ];

            let allValid = true;
            let firstInvalidField = null;

            fieldsToValidate.forEach(field => {
                const input = document.getElementById(field.id);
                if (input && !input.disabled) {
                    const value = input.value.trim();

                    // For optional fields, only validate if they have content
                    if (field.optional) {
                        if (value !== '') {
                            if (!field.func(input)) {
                                allValid = false;
                                if (!firstInvalidField) {
                                    firstInvalidField = input;
                                }
                            }
                        }
                        // Optional field is empty - that's fine, clear any errors
                        else if (value === '') {
                            input.classList.remove('char-error', 'error-border');
                            const validationElement = document.getElementById(field.id + '_validation');
                            if (validationElement) {
                                validationElement.textContent = '';
                                validationElement.className = 'char-validation-message';
                            }
                        }
                    }
                    // For required fields, always validate
                    else {
                        if (!field.func(input)) {
                            allValid = false;
                            if (!firstInvalidField) {
                                firstInvalidField = input;
                            }
                        }
                    }
                }
            });

            if (!allValid && firstInvalidField) {
                showPopup('Please correct the invalid characters in the form fields', 'error');
                firstInvalidField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstInvalidField.focus();
                return;
            }

            // If all validations pass, proceed to submit
            submitForm();
        }

        // ========== MOBILE DATE FORMAT EXAMPLES ==========

        // Show date format examples on mobile
        if ('ontouchstart' in window) {
            document.querySelectorAll('.date-format-example').forEach(example => {
                example.classList.remove('hidden');
            });
        }
    </script>
</body>

</html>