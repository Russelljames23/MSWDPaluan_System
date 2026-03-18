<?php
require_once "../../php/login/staff_header.php";

// Start session immediately
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// FORCE ADMIN CONTEXT
$_SESSION['page_context'] = 'staff';
$_SESSION['current_page'] = 'staff_generate_id.php';

// Restore original user type if coming from staff page
if (isset($_SESSION['original_user_info'])) {
    $_SESSION['user_type'] = $_SESSION['original_user_info']['user_type'] ?? 'Admin';
    unset($_SESSION['original_user_info']);
}

error_log("================================================");
error_log("🚨 ADMIN PAGE LOADED - FORCING ADMIN CONTEXT");
error_log("Page Context: " . ($_SESSION['page_context'] ?? 'none'));
error_log("User Type: " . ($_SESSION['user_type'] ?? 'none'));
error_log("================================================");

// Now include other files
require_once "../../php/context_manager.php";
require_once "../../php/db.php";
require_once "../../php/id_generation_functions.php";

// Initialize context manager
ContextManager::initialize();

// Database connection
$servername = "localhost";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";
$dbname = "u401132124_mswd_seniors";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// ==================== SIGNATORY MANAGEMENT ====================
// Use JSON file for signatories storage
$signatoriesFile = '../../data/signatories.json';

// Create data directory if it doesn't exist
if (!file_exists('../../data')) {
    mkdir('../../data', 0755, true);
}

// Initialize signatories JSON file if it doesn't exist
if (!file_exists($signatoriesFile)) {
    $defaultSignatories = [
        'osca_head' => [
            ['id' => 1, 'name' => 'EVELYN V. BELTRAN', 'status' => 'active'],
            ['id' => 2, 'name' => 'ROSALINA V. BARRALES', 'status' => 'active']
        ],
        'municipal_mayor' => [
            ['id' => 3, 'name' => 'MICHAEL D. DIAZ', 'status' => 'active'],
            ['id' => 4, 'name' => 'MERIAM E. LEYCANO-QUIJANO', 'status' => 'active']
        ]
    ];
    file_put_contents($signatoriesFile, json_encode($defaultSignatories, JSON_PRETTY_PRINT));
}

// Load signatories from JSON file
$signatories = json_decode(file_get_contents($signatoriesFile), true);

// If JSON decoding fails, use defaults
if (!$signatories) {
    $signatories = [
        'osca_head' => [
            ['id' => 1, 'name' => 'EVELYN V. BELTRAN', 'status' => 'active'],
            ['id' => 2, 'name' => 'ROSALINA V. BARRALES', 'status' => 'active']
        ],
        'municipal_mayor' => [
            ['id' => 3, 'name' => 'MICHAEL D. DIAZ', 'status' => 'active'],
            ['id' => 4, 'name' => 'MERIAM E. LEYCANO-QUIJANO', 'status' => 'active']
        ]
    ];
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

// Initialize variables
$seniors = [];
$barangays = [];
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;

// Check if database connection is working
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Safe fetch function
function safeFetchAll($stmt)
{
    $rows = [];
    if ($stmt) {
        try {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result !== false) {
                $rows = $result;
            }
        } catch (Exception $e) {
            // Silently continue
        }
    }
    return $rows;
}

// Safe single fetch function
function safeFetch($stmt)
{
    if (!$stmt) {
        return false;
    }

    try {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Count total seniors - using direct query approach
    $count_query = "SELECT COUNT(*) as total FROM applicants WHERE status = 'Active'";

    $total_count = 0;
    try {
        $count_stmt = $conn->query($count_query);
        if ($count_stmt) {
            $count_row = safeFetch($count_stmt);
            if ($count_row && isset($count_row['total'])) {
                $total_count = (int)$count_row['total'];
            }
        }
    } catch (Exception $e) {
        $total_count = 0;
    }

    $total_pages = $total_count > 0 ? ceil($total_count / $items_per_page) : 0;

    // Calculate offset
    $offset = ($current_page - 1) * $items_per_page;

    // Main query for seniors
    $query = "SELECT 
                a.applicant_id,
                a.last_name,
                a.first_name,
                a.middle_name,
                a.birth_date,
                a.gender,
                a.civil_status,
                a.validation,
                a.status
              FROM applicants a
              WHERE a.status = 'Active'
              ORDER BY a.last_name, a.first_name";

    // Add LIMIT and OFFSET only if we have results
    if ($total_count > 0) {
        $query .= " LIMIT " . intval($items_per_page) . " OFFSET " . intval($offset);
    }

    $seniors_raw = [];
    try {
        $stmt = $conn->query($query);
        if ($stmt) {
            $seniors_raw = safeFetchAll($stmt);
        }
    } catch (Exception $e) {
        $seniors_raw = [];
    }

    // Process each senior
    foreach ($seniors_raw as $senior) {
        // Get address
        $address = ['barangay' => 'N/A', 'municipality' => 'N/A', 'province' => 'N/A'];
        if (isset($senior['applicant_id'])) {
            try {
                $addr_query = "SELECT barangay, municipality, province FROM addresses WHERE applicant_id = " . intval($senior['applicant_id']);
                $addr_stmt = $conn->query($addr_query);
                if ($addr_stmt) {
                    $addr_data = safeFetch($addr_stmt);
                    if ($addr_data) {
                        $address = $addr_data;
                    }
                }
            } catch (Exception $e) {
                // Keep default address
            }
        }

        // Get registration details
        $registration = ['id_number' => 'N/A', 'date_of_registration' => 'N/A', 'local_control_number' => 'N/A'];
        if (isset($senior['applicant_id'])) {
            try {
                $reg_query = "SELECT id_number, date_of_registration, local_control_number 
                             FROM applicant_registration_details 
                             WHERE applicant_id = " . intval($senior['applicant_id']);
                $reg_stmt = $conn->query($reg_query);
                if ($reg_stmt) {
                    $reg_data = safeFetch($reg_stmt);
                    if ($reg_data) {
                        $registration = $reg_data;
                    }
                }
            } catch (Exception $e) {
                // Keep default registration
            }
        }

        // Calculate age
        $age = 'N/A';
        if (!empty($senior['birth_date']) && $senior['birth_date'] != '0000-00-00') {
            try {
                $birthDate = new DateTime($senior['birth_date']);
                $today = new DateTime();
                $age = $birthDate->diff($today)->y;
            } catch (Exception $e) {
                $age = 'N/A';
            }
        }

        // Format full name
        $full_name = '';
        if (isset($senior['last_name']) && isset($senior['first_name'])) {
            $full_name = strtoupper($senior['last_name'] . ', ' . $senior['first_name']);
            if (!empty($senior['middle_name'])) {
                $full_name .= ' ' . strtoupper($senior['middle_name']);
            }
        }

        $seniors[] = [
            'applicant_id' => $senior['applicant_id'] ?? 0,
            'full_name' => $full_name,
            'birth_date' => $senior['birth_date'] ?? 'N/A',
            'age' => $age,
            'gender' => $senior['gender'] ?? 'N/A',
            'civil_status' => $senior['civil_status'] ?? 'N/A',
            'barangay' => $address['barangay'],
            'municipality' => $address['municipality'],
            'province' => $address['province'],
            'validation' => $senior['validation'] ?? 'For Validation',
            'status' => $senior['status'] ?? 'Active',
            'id_number' => $registration['id_number'],
            'date_of_registration' => $registration['date_of_registration'],
            'local_control_number' => $registration['local_control_number']
        ];
    }
} catch (Exception $e) {
    // Continue with empty array
}

// Fetch barangays
$barangays = [];
try {
    $barangay_query = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
    $barangay_stmt = $conn->query($barangay_query);
    if ($barangay_stmt) {
        $barangays = safeFetchAll($barangay_stmt);
    }
} catch (Exception $e) {
    $barangays = [];
}

// Only use session_context if it exists
$ctx = isset($_GET['session_context']) ? urlencode($_GET['session_context']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Senior Citizen ID - MSWD Paluan</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <style>
        /* Enhanced logo styling for page display */
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

        /* Signatory modal styles */
        .signatory-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .signatory-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .signatory-modal-content.dark {
            background-color: #1f2937;
            border-color: #374151;
        }

        .signatory-list-item {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .signatory-list-item.dark {
            border-color: #374151;
            background-color: #374151;
        }

        .signatory-list-item:hover {
            background-color: #f9fafb;
        }

        .signatory-list-item.dark:hover {
            background-color: #4b5563;
        }

        .signatory-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-osca {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-osca.dark {
            background-color: #1e3a8a;
            color: #dbeafe;
        }

        .badge-mayor {
            background-color: #f0f9ff;
            color: #0369a1;
        }

        .badge-mayor.dark {
            background-color: #0c4a6e;
            color: #f0f9ff;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-active.dark {
            background-color: #14532d;
            color: #bbf7d0;
        }

        .status-inactive.dark {
            background-color: #7f1d1d;
            color: #fecaca;
        }

        /* Print styles remain the same */
        @media print {
            body * {
                visibility: hidden;
            }

            .print-page,
            .print-page * {
                visibility: visible;
                font-family: "Times New Roman", Times, serif !important;
            }

            .print-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
            }

            .id-front-page {
                page-break-after: always;
            }

            .benefits-page {
                page-break-after: always;
            }

            @page {
                size: landscape;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .benefits-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                grid-template-rows: repeat(3, 1fr) !important;
                gap: 0.1in !important;
                width: 13in !important;
                height: 8.5in !important;
                padding: 0.2in !important;
                box-sizing: border-box !important;
            }

            .benefits-notice {
                font-family: "Maiandra GD", "Times New Roman", Times, serif !important;
            }
        }

        /* ID card styles remain the same */
        .id-card {
            width: 3.17in;
            height: 2.14in;
            border: 1px solid #000;
            padding: 3px;
            font-family: "Times New Roman", Times, serif;
            background: white;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {

            /* Navigation */
            nav .flex-wrap {
                padding: 0 10px;
            }

            nav .mr-4 {
                margin-right: 10px;
            }

            nav span.text-2xl {
                font-size: 1.25rem;
            }

            nav img.h-10 {
                height: 32px;
            }

            /* Main content */
            main {
                padding: 1rem !important;
                margin-left: 0 !important;
            }

            .pt-20 {
                padding-top: 70px;
            }

            /* Sidebar */
            #drawer-navigation {
                width: 280px;
            }

            /* Table */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-container table {
                min-width: 1000px;
            }

            /* Filter section */
            .filter-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            /* Signatory modal */
            .signatory-modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 15px;
            }

            /* Preview modal */
            #print-preview-modal>div {
                padding: 10px;
            }

            #print-preview-modal .relative {
                width: 100%;
                margin: 0;
            }

            /* Buttons */
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .action-buttons button {
                width: 100%;
            }

            /* Pagination */
            .pagination-container {
                flex-direction: column;
                gap: 12px;
            }

            .pagination-buttons {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 4px;
            }

            /* Card content */
            .card-content {
                padding: 15px;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {

            /* Tablet adjustments */
            main {
                margin-left: 0 !important;
                padding: 1.5rem !important;
            }

            .pt-20 {
                padding-top: 80px;
            }

            /* Filter section */
            .filter-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 15px !important;
            }

            /* Table */
            .table-container {
                overflow-x: auto;
            }

            .table-container table {
                min-width: 1000px;
            }
        }

        @media (max-width: 640px) {

            /* Mobile small screens */
            nav span.text-2xl {
                font-size: 1rem;
            }

            .p-6 {
                padding: 1rem !important;
            }

            .text-2xl {
                font-size: 1.5rem;
            }

            .text-lg {
                font-size: 1.125rem;
            }

            /* Form elements */
            input,
            select,
            button {
                font-size: 16px !important;
                /* Prevents zoom on iOS */
            }
        }

        /* Touch improvements */
        @media (hover: none) and (pointer: coarse) {

            button,
            .senior-checkbox,
            select,
            .pagination-btn {
                min-height: 4;
                min-width: 4;
            }

            .table-container {
                -webkit-overflow-scrolling: touch;
            }

            /* Prevent text size adjustment */
            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }

        /* Dark mode improvements */
        @media (prefers-color-scheme: dark) {
            .signatory-modal-content:not(.dark) {
                background-color: #1f2937;
                color: #f9fafb;
            }
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }

            .print-page,
            .print-page * {
                visibility: visible;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
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
                        <a href="./staffindex.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="./staff_register.php?session_context=<?php echo $ctx; ?>"
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
                                <a href="./staff_activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-check-circle mr-2 text-sm"></i>Active List
                                </a>
                            </li>
                            <li>
                                <a href="./staff_inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                                </a>
                            </li>
                            <li>
                                <a href="./staff_deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="./staff_benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Benefits</span>
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="flex items-center p-2 text-base font-medium text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                            <i class="fas fa-id-card w-6 h-6 text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>
                            <span class="ml-3">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="./staff_report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="./staff_archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-archive w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Archived</span>
                        </a>
                    </li>
                    <li>
                        <a href="./staff_profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Signatory Management Modal -->
        <div id="signatoryModal" class="signatory-modal">
            <div class="signatory-modal-content dark:bg-gray-800 dark:border-gray-700">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Manage Signatories</h3>
                    <button onclick="closeSignatoryModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Add New Signatory Form -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add New Signatory</h4>
                    <form id="addSignatoryForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Position
                                </label>
                                <select name="position" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-600 dark:border-gray-500 dark:text-white" required>
                                    <option value="">Select Position</option>
                                    <option value="OSCA Head">OSCA Head</option>
                                    <option value="Municipal Mayor">Municipal Mayor</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Full Name
                                </label>
                                <input type="text" name="name" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Enter full name" required>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm">
                                <i class="fas fa-plus mr-2"></i>Add Signatory
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Signatories List -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Signatories</h4>
                    <div id="signatoriesList" class="space-y-3 max-h-96 overflow-y-auto">
                        <!-- Signatories will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <main class="p-4 md:ml-64 h-auto pt-20">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 card-content">
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generate Senior Citizen ID</h2>
                </div>

                <!-- Search and Filter Section -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="filter-grid flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search-senior" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Search Senior Citizen
                            </label>
                            <div class="relative">
                                <input type="text" id="search-senior"
                                    class="w-full p-2.5 pl-10 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Search by name, ID number, or barangay">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-barangay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Barangay
                            </label>
                            <select id="filter-barangay"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Barangays</option>
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay['barangay']); ?>">
                                        <?php echo htmlspecialchars($barangay['barangay']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-validation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Validation Status
                            </label>
                            <select id="filter-validation"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Status</option>
                                <option value="Validated">Validated</option>
                                <option value="For Validation">For Validation</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Senior Selection Table -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Seniors for ID Generation</h3>
                        <div class="w-full md:w-auto flex items-center space-x-3">
                            <button id="select-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Select All
                            </button>
                            <button id="deselect-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Deselect All
                            </button>
                            <span id="selected-count" class="text-sm text-gray-600 dark:text-gray-400">0 selected</span>
                        </div>
                    </div>

                    <div class="table-container overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 w-12">
                                        <input id="master-checkbox" type="checkbox" class="w-4 h-4 text-blue-600 bg-white border border-gray-400 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-white dark:border-gray-600">
                                    </th>
                                    <th scope="col" class="px-4 py-3">No.</th>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Birthdate</th>
                                    <th scope="col" class="px-4 py-3">Age</th>
                                    <th scope="col" class="px-4 py-3">Gender</th>
                                    <th scope="col" class="px-4 py-3">Barangay</th>
                                    <th scope="col" class="px-4 py-3">ID Number</th>
                                    <th scope="col" class="px-4 py-3">Date Issued</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    <th scope="col" class="px-4 py-3">ID Status</th>
                                </tr>
                            </thead>
                            <tbody id="seniors-table-body">
                                <?php if (empty($seniors)): ?>
                                    <tr>
                                        <td colspan="11" class="px-4 py-8  text-center text-gray-500 dark:text-gray-400">
                                            No active senior citizens found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($seniors as $index => $senior): ?>
                                        <?php
                                        $global_index = $offset + $index + 1;
                                        // Format the registration date for display
                                        $date_issued_display = 'N/A';
                                        if (!empty($senior['date_of_registration']) && $senior['date_of_registration'] != '0000-00-00') {
                                            try {
                                                $dateObj = new DateTime($senior['date_of_registration']);
                                                $date_issued_display = $dateObj->format('m/d/Y');
                                            } catch (Exception $e) {
                                                $date_issued_display = 'N/A';
                                            }
                                        }
                                        ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 senior-row"
                                            data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                            data-validation="<?php echo htmlspecialchars($senior['validation'] ?? ''); ?>">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" class="senior-checkbox w-4 h-4  text-blue-600 bg-white border border-gray-400 rounded focus:ring-blue-500"
                                                    data-id="<?php echo htmlspecialchars($senior['applicant_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($senior['full_name']); ?>"
                                                    data-birthdate="<?php echo htmlspecialchars($senior['birth_date'] ?? ''); ?>"
                                                    data-age="<?php echo htmlspecialchars($senior['age'] ?? ''); ?>"
                                                    data-gender="<?php echo htmlspecialchars($senior['gender'] ?? ''); ?>"
                                                    data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                                    data-municipality="<?php echo htmlspecialchars($senior['municipality'] ?? 'Paluan'); ?>"
                                                    data-province="<?php echo htmlspecialchars($senior['province'] ?? 'Occidental Mindoro'); ?>"
                                                    data-id-number="<?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?>"
                                                    data-date-issued="<?php echo htmlspecialchars($senior['date_of_registration'] ?? ''); ?>"
                                                    data-local-control="<?php echo htmlspecialchars($senior['local_control_number'] ?? ''); ?>">
                                            </td>
                                            <td class="px-4 py-3"><?php echo $global_index; ?></td>
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white w-[250px]">
                                                <?php echo htmlspecialchars($senior['full_name']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php
                                                if (!empty($senior['birth_date']) && $senior['birth_date'] != '0000-00-00') {
                                                    try {
                                                        $birthDate = new DateTime($senior['birth_date']);
                                                        echo htmlspecialchars($birthDate->format('m/d/Y'));
                                                    } catch (Exception $e) {
                                                        echo 'N/A';
                                                    }
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['age'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['gender'] ?? ''); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['barangay'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($date_issued_display); ?></td>
                                            <td class=" py-3 w-[200px]">
                                                <span class="px-2 py-1 text-xs rounded <?php echo ($senior['validation'] === 'Validated') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($senior['validation'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 w-[350px]">
                                                <?php
                                                // Check if ID has been printed for this senior
                                                $idStatus = checkIfIDPrinted($senior['applicant_id']);
                                                if ($idStatus && $idStatus['status'] === 'Printed' && $idStatus['is_active'] == 1):
                                                ?>
                                                    <span class="px-2 py-1 text-xs text-center w bg-green-100 text-green-800 rounded-full" title="Printed on <?php echo date('m/d/Y', strtotime($idStatus['print_date'])); ?>">
                                                        ✅ Printed
                                                    </span>
                                                <?php elseif ($idStatus && $idStatus['status'] === 'Reissued'): ?>
                                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                                        🔄 Reissued
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">
                                                        ⏳ Not Printed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container flex items-center justify-between p-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-700 dark:text-gray-400">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                    <span class="font-medium"><?php echo min($offset + $items_per_page, $total_count); ?></span> of
                                    <span class="font-medium"><?php echo $total_count; ?></span> seniors
                                </div>
                                <div class="pagination-buttons flex space-x-1">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=1&session_context=<?php echo $ctx; ?>" class="pagination-btn px-3 py-1.5 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                            First
                                        </a>
                                        <a href="?page=<?php echo $current_page - 1; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn px-3 py-1.5 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                            Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Show page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);

                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?page=<?php echo $i; ?>&session_context=<?php echo $ctx; ?>"
                                            class="pagination-btn px-3 py-1.5 text-sm border rounded <?php echo $i == $current_page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=<?php echo $current_page + 1; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn px-3 py-1.5 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                            Next
                                        </a>
                                        <a href="?page=<?php echo $total_pages; ?>&session_context=<?php echo $ctx; ?>" class="pagination-btn px-3 py-1.5 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                            Last
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ID Preview and Generation Controls -->
                <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                    <!-- Preview Controls -->
                    <div class="lg:col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ID Preview & Generation</h3>

                        <!-- Current Selection (same as before) -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">Selected for Generation</h4>
                            <div id="selected-list" class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>
                            </div>
                        </div>

                        <!-- Enhanced Signatory Selection with Management -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white">Signatory Selection</h4>
                                <button onclick="openSignatoryModal()" class="px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white rounded-lg">
                                    <i class="fas fa-cog mr-2"></i>Manage Signatories
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="osca-head" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                        OSCA HEAD
                                    </label>
                                    <select id="osca-head" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <?php foreach ($signatories['osca_head'] as $signatory): ?>
                                            <?php if ($signatory['status'] === 'active'): ?>
                                                <option value="<?php echo htmlspecialchars($signatory['name']); ?>">
                                                    <?php echo htmlspecialchars($signatory['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="municipal-mayor" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                        Municipal Mayor
                                    </label>
                                    <select id="municipal-mayor" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <?php foreach ($signatories['municipal_mayor'] as $signatory): ?>
                                            <?php if ($signatory['status'] === 'active'): ?>
                                                <option value="<?php echo htmlspecialchars($signatory['name']); ?>">
                                                    <?php echo htmlspecialchars($signatory['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Generation Options -->
                        <div class="pt-4 border-t dark:border-gray-700">
                            <div class="action-buttons flex flex-wrap gap-3">
                                <button id="preview-ids-btn"
                                    class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-blue-300 focus:outline-none inline-flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                    </svg>
                                    Preview IDs
                                </button>
                                <button id="print-ids-btn"
                                    class="px-5 py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-purple-300 focus:outline-none inline-flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                    </svg>
                                    Print IDs
                                </button>
                                <button id="clear-selection-btn"
                                    class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-gray-300 focus:outline-none inline-flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Print Preview Modal -->
            <div id="print-preview-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 bg-opacity-50">
                <div class="relative min-h-screen flex items-center justify-center p-4">
                    <div class="relative w-full max-w-6xl mx-auto bg-white rounded-lg shadow-lg">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-xl font-bold text-gray-900">ID Cards Print Preview</h3>
                            <button id="close-preview-btn" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Preview Content - Improved Container -->
                        <div class="p-4 overflow-x-auto">
                            <div id="preview-content" class="flex flex-col items-center justify-center min-w-max">
                                <!-- Preview will be generated here -->
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex flex-col sm:flex-row justify-between items-center p-4 border-t gap-4">
                            <div class="text-sm text-gray-600">
                                Showing: <span id="preview-count">0</span> IDs | Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                            </div>
                            <div class="flex space-x-2">
                                <button id="prev-page-btn" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Previous
                                </button>
                                <button id="next-page-btn" class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                                    Next
                                </button>
                                <button id="print-preview-btn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/staff_tailwind.config.js"></script>
    <script src="../../js/staff_theme.js"></script>
    <script>
        // ---------- THEME INITIALIZATION ----------
        (function() {
            const StaffTheme = {
                init: function() {
                    const savedTheme = localStorage.getItem('staff_theme');
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    let theme = 'light';
                    if (savedTheme) {
                        theme = savedTheme;
                    } else if (systemPrefersDark) {
                        theme = 'dark';
                    }

                    this.set(theme);
                    return theme;
                },

                set: function(theme) {
                    const root = document.documentElement;
                    const wasDark = root.classList.contains('dark');
                    const isDark = theme === 'dark';

                    if (isDark && !wasDark) {
                        root.classList.add('dark');
                        localStorage.setItem('staff_theme', 'dark');
                    } else if (!isDark && wasDark) {
                        root.classList.remove('dark');
                        localStorage.setItem('staff_theme', 'light');
                    }

                    window.dispatchEvent(new CustomEvent('staffThemeChanged'));
                }
            };

            StaffTheme.init();

            window.addEventListener('storage', function(e) {
                if (e.key === 'staff_theme') {
                    const theme = e.newValue;
                    const currentIsDark = document.documentElement.classList.contains('dark');
                    const newIsDark = theme === 'dark';

                    if ((newIsDark && !currentIsDark) || (!newIsDark && currentIsDark)) {
                        StaffTheme.set(theme);
                    }
                }
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('staff_theme')) {
                    StaffTheme.set(e.matches ? 'dark' : 'light');
                }
            });
        })();
    </script>
    <script>
        // ---------- RESPONSIVE ENHANCEMENTS ----------

        // Adjust table scroll on mobile
        function adjustTableScroll() {
            if (window.innerWidth < 768) {
                document.querySelectorAll('.table-container').forEach(container => {
                    if (container.scrollWidth > container.clientWidth) {
                        container.classList.add('overflow-x-scroll');
                    }
                });
            }
        }

        // Adjust modal for mobile
        function adjustModalForMobile() {
            const modal = document.getElementById('print-preview-modal');
            if (window.innerWidth < 768 && modal && !modal.classList.contains('hidden')) {
                modal.querySelector('.relative').classList.add('w-full', 'm-2');
            }
        }

        // Handle mobile menu
        function initMobileMenu() {
            const menuButton = document.querySelector('[data-drawer-toggle="drawer-navigation"]');
            const sidebar = document.getElementById('drawer-navigation');

            if (menuButton && sidebar) {
                menuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth < 768 && !sidebar.contains(event.target) &&
                        !menuButton.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                    }
                });
            }
        }

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                adjustTableScroll();
                adjustModalForMobile();
            }, 250);
        });

        // Initialize responsive features
        document.addEventListener('DOMContentLoaded', function() {
            adjustTableScroll();
            initMobileMenu();

            // Add touch improvements for mobile
            if ('ontouchstart' in window) {
                // Increase touch target sizes
                document.querySelectorAll('button, .senior-checkbox, .pagination-btn').forEach(el => {
                    el.style.minHeight = '44px';
                    el.style.minWidth = '44px';
                });

                // Prevent zoom on input focus for iOS
                document.querySelectorAll('input, select, textarea').forEach(el => {
                    el.addEventListener('focus', function() {
                        this.style.fontSize = '16px';
                    });
                });
            }
        });

        
    </script>
    <script>
        // Global variables - KEPT THE SAME
        let selectedSeniors = new Map();
        let currentPreviewPage = 1;
        let totalPreviewPages = 1;
        let allPreviewPages = [];
        let allSignatories = <?php echo json_encode($signatories); ?>;

        // Initialize when DOM is loaded - KEPT THE SAME
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved selections from localStorage
            loadSelectionsFromStorage();

            // Initialize checkboxes based on saved selections
            initializeCheckboxes();

            // Load signatories list
            loadSignatories();

            // Event listeners for buttons
            document.getElementById('select-all-btn').addEventListener('click', selectAllOnCurrentPage);
            document.getElementById('deselect-all-btn').addEventListener('click', deselectAll);
            document.getElementById('master-checkbox').addEventListener('change', toggleMasterCheckbox);
            document.getElementById('preview-ids-btn').addEventListener('click', previewIDs);
            document.getElementById('print-ids-btn').addEventListener('click', printIDs);
            document.getElementById('clear-selection-btn').addEventListener('click', clearAllSelections);
            document.getElementById('close-preview-btn').addEventListener('click', closePreview);
            document.getElementById('print-preview-btn').addEventListener('click', printPreview);
            document.getElementById('addSignatoryForm').addEventListener('submit', addSignatory);

            // Use event delegation for buttons in the modal
            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'prev-page-btn') {
                    prevPage();
                }

                if (e.target && e.target.id === 'next-page-btn') {
                    nextPage();
                }

                if (e.target && (e.target.closest('#prev-page-btn'))) {
                    prevPage();
                }

                if (e.target && (e.target.closest('#next-page-btn'))) {
                    nextPage();
                }

                // Handle edit signatory button
                if (e.target && e.target.closest('.edit-signatory-btn')) {
                    const signatoryId = e.target.closest('.edit-signatory-btn').dataset.id;
                    editSignatory(signatoryId);
                }

                // Handle toggle status button
                if (e.target && e.target.closest('.toggle-status-btn')) {
                    const button = e.target.closest('.toggle-status-btn');
                    const signatoryId = button.dataset.id;
                    const position = button.dataset.position;
                    toggleSignatoryStatus(signatoryId, position);
                }

                // Handle delete signatory button
                if (e.target && e.target.closest('.delete-signatory-btn')) {
                    const button = e.target.closest('.delete-signatory-btn');
                    const signatoryId = button.dataset.id;
                    const position = button.dataset.position;
                    deleteSignatory(signatoryId, position);
                }
            });

            // Search and filter event listeners
            document.getElementById('search-senior').addEventListener('input', filterTable);
            document.getElementById('filter-barangay').addEventListener('change', filterTable);
            document.getElementById('filter-validation').addEventListener('change', filterTable);

            // Add event listeners to checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('senior-checkbox')) {
                    handleCheckboxChange(e.target);
                }
            });

            // Save selections before page navigation
            document.addEventListener('click', function(e) {
                const paginationLink = e.target.closest('.pagination-btn, a[href*="page="]');
                if (paginationLink && !paginationLink.classList.contains('active')) {
                    saveSelectionsToStorage();
                }
            });

            // Initialize selected list
            updateSelectedList();
        });

        // ==================== SIGNATORY MANAGEMENT FUNCTIONS ====================
        // KEPT EXACTLY THE SAME

        function openSignatoryModal() {
            document.getElementById('signatoryModal').style.display = 'block';
            loadSignatories();
        }

        function closeSignatoryModal() {
            document.getElementById('signatoryModal').style.display = 'none';
        }

        function loadSignatories() {
            // For JSON file approach, we'll use AJAX to get fresh data
            fetch('../../php/manage_signatories.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allSignatories = data.signatories;
                        updateSignatoriesList();
                        updateSignatoryDropdowns();
                    } else {
                        showNotification('Error loading signatories: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading signatories:', error);
                    showNotification('Error loading signatories', 'error');
                });
        }

        function updateSignatoriesList() {
            const listContainer = document.getElementById('signatoriesList');
            const isDark = document.documentElement.classList.contains('dark');

            if (!allSignatories || (allSignatories.osca_head.length === 0 && allSignatories.municipal_mayor.length === 0)) {
                listContainer.innerHTML = '<p class="text-center text-gray-500 dark:text-gray-400">No signatories found</p>';
                return;
            }

            let html = '';

            // Combine all signatories into one array
            const allSignatoriesArray = [
                ...(allSignatories.osca_head || []).map(s => ({
                    ...s,
                    position: 'OSCA Head'
                })),
                ...(allSignatories.municipal_mayor || []).map(s => ({
                    ...s,
                    position: 'Municipal Mayor'
                }))
            ];

            allSignatoriesArray.forEach(signatory => {
                const badgeClass = signatory.position === 'OSCA Head' ? 'badge-osca' : 'badge-mayor';
                const statusClass = signatory.status === 'active' ? 'status-active' : 'status-inactive';

                html += `
                    <div class="signatory-list-item ${isDark ? 'dark' : ''}">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="signatory-badge ${badgeClass} ${isDark ? 'dark' : ''}">
                                    ${signatory.position}
                                </span>
                                <span class="status-badge ${statusClass} ${isDark ? 'dark' : ''}">
                                    ${signatory.status}
                                </span>
                            </div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                ${signatory.name}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="toggle-status-btn px-3 py-1 text-xs ${signatory.status === 'active' ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:hover:bg-yellow-800 dark:text-yellow-200' : 'bg-green-100 hover:bg-green-200 text-green-800 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200'} rounded"
                                data-id="${signatory.id}" data-position="${signatory.position}">
                                ${signatory.status === 'active' ? 'Deactivate' : 'Activate'}
                            </button>
                            <button class="delete-signatory-btn px-3 py-1 text-xs bg-red-100 hover:bg-red-200 text-red-800 dark:bg-red-900 dark:hover:bg-red-800 dark:text-red-200 rounded"
                                data-id="${signatory.id}" data-position="${signatory.position}">
                                Delete
                            </button>
                        </div>
                    </div>
                `;
            });

            listContainer.innerHTML = html;
        }

        function updateSignatoryDropdowns() {
            const oscaHeadSelect = document.getElementById('osca-head');
            const mayorSelect = document.getElementById('municipal-mayor');

            // Store current selections
            const currentOscaHead = oscaHeadSelect.value;
            const currentMayor = mayorSelect.value;

            // Clear existing options
            while (oscaHeadSelect.options.length > 0) oscaHeadSelect.remove(0);
            while (mayorSelect.options.length > 0) mayorSelect.remove(0);

            // Add OSCA Head options
            (allSignatories.osca_head || []).forEach(signatory => {
                if (signatory.status === 'active') {
                    const option = document.createElement('option');
                    option.value = signatory.name;
                    option.textContent = signatory.name;
                    oscaHeadSelect.appendChild(option);
                }
            });

            // Add Municipal Mayor options
            (allSignatories.municipal_mayor || []).forEach(signatory => {
                if (signatory.status === 'active') {
                    const option = document.createElement('option');
                    option.value = signatory.name;
                    option.textContent = signatory.name;
                    mayorSelect.appendChild(option);
                }
            });

            // Restore previous selections if they still exist
            if (currentOscaHead) {
                oscaHeadSelect.value = currentOscaHead;
            }
            if (currentMayor) {
                mayorSelect.value = currentMayor;
            }
        }

        function addSignatory(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = {
                action: 'add',
                position: formData.get('position'),
                name: formData.get('name')
            };

            fetch('../../php/manage_signatories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification('Signatory added successfully', 'success');
                        loadSignatories();
                        e.target.reset();
                    } else {
                        showNotification('Error adding signatory: ' + result.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error adding signatory:', error);
                    showNotification('Error adding signatory', 'error');
                });
        }

        function toggleSignatoryStatus(signatoryId, position) {
            if (!confirm('Are you sure you want to change the status of this signatory?')) {
                return;
            }

            const data = {
                action: 'toggle',
                id: signatoryId,
                position: position
            };

            fetch('../../php/manage_signatories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification('Signatory status updated', 'success');
                        loadSignatories();
                    } else {
                        showNotification('Error updating signatory: ' + result.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error toggling signatory status:', error);
                    showNotification('Error updating signatory', 'error');
                });
        }

        function deleteSignatory(signatoryId, position) {
            if (!confirm('Are you sure you want to delete this signatory? This action cannot be undone.')) {
                return;
            }

            const data = {
                action: 'delete',
                id: signatoryId,
                position: position
            };

            fetch('../../php/manage_signatories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification('Signatory deleted successfully', 'success');
                        loadSignatories();
                    } else {
                        showNotification('Error deleting signatory: ' + result.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting signatory:', error);
                    showNotification('Error deleting signatory', 'error');
                });
        }

        // ==================== EXISTING FUNCTIONS (ALL KEPT THE SAME) ====================

        // Save selections to localStorage
        function saveSelectionsToStorage() {
            const selections = {};
            selectedSeniors.forEach((value, key) => {
                selections[key] = value;
            });
            localStorage.setItem('selectedSeniors', JSON.stringify(selections));
        }

        // Load selections from localStorage
        function loadSelectionsFromStorage() {
            const saved = localStorage.getItem('selectedSeniors');
            if (saved) {
                try {
                    const selections = JSON.parse(saved);
                    selectedSeniors = new Map(Object.entries(selections));
                } catch (e) {
                    console.error('Error loading selections:', e);
                    selectedSeniors = new Map();
                }
            }
        }

        // Clear all selections from localStorage and memory
        function clearAllSelections() {
            if (confirm('Are you sure you want to clear all selected seniors?')) {
                selectedSeniors.clear();
                localStorage.removeItem('selectedSeniors');

                // Uncheck all checkboxes
                document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });

                updateSelectedList();
                updateMasterCheckbox();
                showNotification('All selections cleared successfully', 'success');
            }
        }

        // Initialize checkboxes based on saved selections
        function initializeCheckboxes() {
            document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                const id = checkbox.dataset.id;
                if (selectedSeniors.has(id)) {
                    checkbox.checked = true;
                }
            });
            updateMasterCheckbox();
        }

        // Handle checkbox change
        function handleCheckboxChange(checkbox) {
            const id = checkbox.dataset.id;
            const name = checkbox.dataset.name;

            if (checkbox.checked) {
                selectedSeniors.set(id, {
                    name: name,
                    birthdate: checkbox.dataset.birthdate,
                    age: checkbox.dataset.age,
                    gender: checkbox.dataset.gender,
                    barangay: checkbox.dataset.barangay,
                    municipality: checkbox.dataset.municipality || 'Paluan',
                    province: checkbox.dataset.province || 'Occidental Mindoro',
                    idNumber: checkbox.dataset.idNumber,
                    dateIssued: checkbox.dataset.dateIssued,
                    localControl: checkbox.dataset.localControl
                });
            } else {
                selectedSeniors.delete(id);
            }

            updateSelectedList();
            updateMasterCheckbox();
            saveSelectionsToStorage();
        }

        // Filter table based on search and filters
        function filterTable() {
            const search = document.getElementById('search-senior').value.toLowerCase();
            const barangay = document.getElementById('filter-barangay').value;
            const validation = document.getElementById('filter-validation').value;

            const rows = document.querySelectorAll('.senior-row');

            rows.forEach(row => {
                const name = row.querySelector('.senior-checkbox').dataset.name.toLowerCase();
                const rowBarangay = row.dataset.barangay;
                const rowValidation = row.dataset.validation;

                let show = true;

                // Apply search filter
                if (search && !name.includes(search)) {
                    show = false;
                }

                // Apply barangay filter
                if (barangay !== 'all' && rowBarangay !== barangay) {
                    show = false;
                }

                // Apply validation filter
                if (validation !== 'all' && rowValidation !== validation) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });

            updateMasterCheckbox();
        }

        // Update selected list display
        function updateSelectedList() {
            const listContainer = document.getElementById('selected-list');
            const countElement = document.getElementById('selected-count');

            countElement.textContent = `${selectedSeniors.size} selected`;

            if (selectedSeniors.size === 0) {
                listContainer.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>';
                return;
            }

            let html = '<div class="space-y-1 max-h-32 overflow-y-auto">';
            selectedSeniors.forEach((senior, id) => {
                html += `
                    <div class="flex justify-between items-center text-sm p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                        <span class="truncate">${senior.name}</span>
                        <button class="text-red-500 hover:text-red-700 ml-2" onclick="removeSelected('${id}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            listContainer.innerHTML = html;
        }

        // Remove selected senior
        function removeSelected(id) {
            selectedSeniors.delete(id);

            // Uncheck in table if visible
            const checkbox = document.querySelector(`.senior-checkbox[data-id="${id}"]`);
            if (checkbox) checkbox.checked = false;

            updateSelectedList();
            updateMasterCheckbox();
            saveSelectionsToStorage();
        }

        // Select all seniors on current page
        function selectAllOnCurrentPage() {
            const checkboxes = document.querySelectorAll('.senior-checkbox');
            let selectedCount = 0;

            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    if (!checkbox.checked) {
                        checkbox.checked = true;
                        const id = checkbox.dataset.id;
                        if (!selectedSeniors.has(id)) {
                            selectedSeniors.set(id, {
                                name: checkbox.dataset.name,
                                birthdate: checkbox.dataset.birthdate,
                                age: checkbox.dataset.age,
                                gender: checkbox.dataset.gender,
                                barangay: checkbox.dataset.barangay,
                                municipality: checkbox.dataset.municipality || 'Paluan',
                                province: checkbox.dataset.province || 'Occidental Mindoro',
                                idNumber: checkbox.dataset.idNumber,
                                dateIssued: checkbox.dataset.dateIssued,
                                localControl: checkbox.dataset.localControl
                            });
                            selectedCount++;
                        }
                    }
                }
            });

            if (selectedCount > 0) {
                showNotification(`Added ${selectedCount} seniors from current page to selection`, 'success');
            }

            updateSelectedList();
            updateMasterCheckbox();
            saveSelectionsToStorage();
        }

        // Deselect all seniors
        function deselectAll() {
            // Only deselect visible checkboxes on current page
            const checkboxes = document.querySelectorAll('.senior-checkbox:checked');
            let deselectedCount = 0;

            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    const id = checkbox.dataset.id;
                    if (selectedSeniors.has(id)) {
                        selectedSeniors.delete(id);
                        checkbox.checked = false;
                        deselectedCount++;
                    }
                }
            });

            if (deselectedCount > 0) {
                showNotification(`Removed ${deselectedCount} seniors from current page from selection`, 'success');
            }

            updateSelectedList();
            updateMasterCheckbox();
            saveSelectionsToStorage();
        }

        // Toggle master checkbox
        function toggleMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');

            if (masterCheckbox.checked) {
                selectAllOnCurrentPage();
            } else {
                deselectAll();
            }
        }

        // Update master checkbox state
        function updateMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');
            const visibleCheckboxes = document.querySelectorAll('.senior-checkbox:not([style*="display: none"])');
            const visibleChecked = document.querySelectorAll('.senior-checkbox:checked:not([style*="display: none"])');

            if (visibleChecked.length === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            } else if (visibleChecked.length === visibleCheckboxes.length) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            }
        }

        // Preview IDs (updated to use dynamic signatories)
        function previewIDs() {
            if (selectedSeniors.size === 0) {
                showNotification('Please select at least one senior citizen.', 'warning');
                return;
            }

            // Convert Map to Array
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            // Generate all preview pages with current signatories
            allPreviewPages = generateAllPreviewPages(seniorsArray);
            totalPreviewPages = allPreviewPages.length;
            currentPreviewPage = 1;

            // Display first page
            displayPreviewPage();

            // Update preview info
            document.getElementById('preview-count').textContent = seniorsArray.length;
            updatePageNavigation();

            // Show modal
            document.getElementById('print-preview-modal').classList.remove('hidden');
        }

        // Generate all preview pages (front and back) - updated to use current signatories
        function generateAllPreviewPages(seniorsArray) {
            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const pages = [];

            // Group seniors into pages of 9 IDs each
            for (let i = 0; i < seniorsArray.length; i += 9) {
                const pageSeniors = seniorsArray.slice(i, i + 9);

                // Create front page (ID cards)
                pages.push(generateFrontPage(pageSeniors));

                // Create back page (benefits) with current signatories
                pages.push(generateBackPage(oscaHead, municipalMayor));
            }

            return pages;
        }

        // Generate front page with ID cards (same as before)
        function generateFrontPage(seniors) {
            let html = `
                <div class="print-page id-front-page" style="width: 13in; height: 8.5in;">
                    <div class="print-grid">
            `;

            seniors.forEach(senior => {
                // Format birth date
                const dob = senior.birthdate ? formatDate(senior.birthdate) : 'N/A';

                // Use date_of_registration from database
                const dateIssued = senior.dateIssued ? formatDate(senior.dateIssued) : 'N/A';

                const idNumber = senior.idNumber && senior.idNumber !== 'N/A' ? senior.idNumber :
                    senior.localControl ? senior.localControl :
                    'PALUAN-' + senior.id.substring(0, 6);

                const genderCode = senior.gender === 'Male' ? 'M' : senior.gender === 'Female' ? 'F' : '';

                // Build full address
                let fullAddress = '';
                if (senior.barangay && senior.barangay !== 'N/A') {
                    fullAddress = `Brgy. ${senior.barangay.toUpperCase()}`;
                }
                if (senior.municipality && senior.municipality !== 'N/A') {
                    fullAddress += fullAddress ? ', ' + senior.municipality : senior.municipality;
                }
                if (senior.province && senior.province !== 'N/A') {
                    fullAddress += fullAddress ? ', ' + senior.province : senior.province;
                }

                html += `
                    <div class="id-card">
                        <!-- Republic Header -->
                        <div class="id-header" style="font-size: 6pt; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                            <img src="../img/MSWD_LOGO-removebg-preview.png" alt="PH Seal" class="w-[.51in] h-[.51in] rounded-full vertical-align-middle">
                            <div>
                                <div style="font-size: 7.5pt;">Republic of the Philippines</div>
                                <div style="font-size: 7.5pt;">Office for Senior Citizens Affairs (OSCA)</div>
                                <div style="font-size: 7.5pt;">Paluan, Occidental Mindoro</div>
                            </div>
                            <img src="../img/paluan.png" alt="Mindoro Seal" class="w-[.51in] h-[.51in] rounded-full vertical-align-middle">
                        </div>
                        
                        <!-- ID Content -->
                        <div class="id-content" style="font-size: 8pt;">
                            <div style="margin-bottom: 1px;" class="name flex flex-row gap-1">
                                <div style="font-weight: bold; font-size: 8pt;">Name:</div>
                                <div class="id-name" style="font-size: 8pt; font-weight: bold; margin-left: 5px;">${senior.name}</div>
                            </div>
                            <div class="3rdrow flex flex-row justify-between align-middle mt-1">
                                <div class="dsd flex flex-col text-align: left; w-[2.15in]">
                                    <div style="margin-bottom: 2px; " class="address flex flex-row">
                                        <div style="font-weight: bold; font-size: 8pt;">Address:</div>
                                        <div class="id-address" style="font-size: 8pt; font-weight: bold; margin-left: 5px;">${fullAddress || 'N/A'}</div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;">
                                        <div class="dob text-center">
                                            <div style="font-size: 8pt; font-weight: bold; text-decoration: underline;">${dob}</div>
                                            <div style="font-size: 8pt; font-weight: bold;">Date of Birth</div>
                                        </div>
                                        <div  class="gender text-center">
                                            <div style="font-size: 8pt; font-weight: bold; text-decoration: underline;">${genderCode}</div>
                                            <div style="font-size: 8pt; font-weight: bold;">Sex</div>
                                        </div>
                                        <div  class="dateissued text-center">
                                            <div style="font-size: 8pt; font-weight: bold; text-decoration: underline;">${dateIssued}</div>
                                            <div style="font-size: 8pt; font-weight: bold;">Date Issued</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="height:1in; width:1in; border: 1px solid #000;" class="idpicture"></div>
                            </div>
                            <div class="flex flex-row justify-between align-middle">
                                <div style="text-align: left;">
                                    <div style="border-bottom: 1px solid #000; width: 100%;" class="left-0"></div>
                                    <div style="font-size: 8pt; font-weight: bold;">Signature / Thumbmark</div>
                                </div>
                                <div style="text-align: right; margin-right:0.5in;">
                                    <div style="font-size: 8pt; font-weight: bold;" class="validity-number">
                                        I.D No. <span style="font-weight: bold; text-decoration: underline;" class="id-number">${idNumber}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Non-Transferable Notice -->
                        <div class="id-footer" style="font-size: 7pt; font-weight: bold; color: red; text-align: center;">
                            THIS CARD IS NON-TRANSFERABLE
                        </div>
                    </div>
                `;
            });

            // Fill empty spots with blank ID cards (to maintain 9 per page)
            for (let i = seniors.length; i < 9; i++) {
                html += '<div class="id-card"></div>';
            }

            html += `
                    </div>
                </div>
            `;

            return html;
        }


        // Generate back page with benefits (updated to use dynamic signatories)
        function generateBackPage(oscaHead, municipalMayor) {
            return `
                <div class="print-page benefits-page">
                    <div class="print-grid">
                        ${Array(9).fill().map(() => `
                        <div class="benefits-card">
                            <div class="benefits-header">
                                Benefits and Privileges under Republic Act No. 9994
                            </div>
                            
                            <div class="benefits-list">
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> Free medical/dental diagnostic & laboratory fees in all government facilities.</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 20% discount in purchase medicines</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 20% discount in Hotels, Restaurant, and Recreation Centers & Funeral Parlors.</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 20% discount on theatres, cinema houses and concert halls, etc.</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 20% discount in medical/ dental services, diagnostic & laboratory fees in private facilities.</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 20% discount in fare for domestic air, sea travel and public land transportation</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 5% discount in basic necessities and prime commodities</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 12% VAT - exemption on the purchase of goods & service which are entitled to the 20% discount</div>
                                <div><img src="../img/Screenshot 2025-12-19 130648.png" alt="" class="w-[2pt] h-[2pt] rounded-full vertical-align-middle"> 5% discount monthly utilization of water/electricity provided that the water and electricity meter bases are under the name of senior citizens</div>
                                
                                <div class="benefits-notice">
                                    Persons and Corporations violating RA 9994 shall be penalized. Only for the exclusive use of Senior Citizens; abuse of privileges is punishable by law.
                                </div>
                            </div>
                            
                            <div class="benefits-footer">
                                <div class="signatures-container">
                                    <div class="signature-item">
                                        <div class="signature-name">${oscaHead}</div>
                                        <div class="signature-title">OSCA HEAD</div>
                                    </div>
                                    <div class="signature-item">
                                        <div class="signature-name">${municipalMayor}</div>
                                        <div class="signature-title">Municipal Mayor</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Format date to MM/DD/YYYY
        function formatDate(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';

            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'N/A';

                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const year = date.getFullYear();

                return `${month}/${day}/${year}`;
            } catch (e) {
                return 'N/A';
            }
        }

        // Display current preview page
        function displayPreviewPage() {
            const previewContent = document.getElementById('preview-content');
            if (allPreviewPages[currentPreviewPage - 1]) {
                const container = document.createElement('div');
                container.className = 'flex flex-col items-center justify-center w-full';
                container.innerHTML = allPreviewPages[currentPreviewPage - 1];

                previewContent.innerHTML = '';
                previewContent.appendChild(container);
            }
        }

        // Update page navigation
        function updatePageNavigation() {
            document.getElementById('current-page').textContent = currentPreviewPage;
            document.getElementById('total-pages').textContent = totalPreviewPages;

            document.getElementById('prev-page-btn').style.display = currentPreviewPage > 1 ? 'block' : 'none';
            document.getElementById('next-page-btn').style.display = currentPreviewPage < totalPreviewPages ? 'block' : 'none';
        }

        // Previous page
        function prevPage() {
            if (currentPreviewPage > 1) {
                currentPreviewPage--;
                displayPreviewPage();
                updatePageNavigation();
            }
        }

        // Next page
        function nextPage() {
            if (currentPreviewPage < totalPreviewPages) {
                currentPreviewPage++;
                displayPreviewPage();
                updatePageNavigation();
            }
        }

        // Print IDs (direct print from browser)
        function printIDs() {
            if (selectedSeniors.size === 0) {
                showNotification('Please select at least one senior citizen.', 'warning');
                return;
            }

            // First show preview, then trigger print
            previewIDs();

            setTimeout(() => {
                document.getElementById('print-preview-btn').click();
            }, 500);
        }

        // Close preview modal
        function closePreview() {
            document.getElementById('print-preview-modal').classList.add('hidden');
        }

        // Print Preview - KEPT EXACTLY THE SAME
        function printPreview() {
            const printWindow = window.open('', '_blank');
            printWindow.document.title = 'Senior Citizen IDs - Print';

            let printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Senior Citizen IDs - Print</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
                    <style>
                        @page {
                            size: landscape;
                            margin: 0;
                        }
                        
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: "Times New Roman", Times, serif;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                            display: flex !important;
                            flex-direction: column !important;
                            justify-content: center !important;
                            align-items: center !important;
                            min-height: 100vh !important;
                            background: white !important;
                        }
                        
                        /* Container for centering all pages */
                        .print-container {
                            width: 13in !important;
                            height: 8.5in !important;
                            display: flex !important;
                            justify-content: center !important;
                            align-items: center !important;
                        }
                        
                        .print-page {
                            width: 13in !important;
                            height: 8.5in !important;
                            page-break-after: always !important;
                            display: flex !important;
                            justify-content: center !important;
                            align-items: center !important;
                        }
                        
                        /* Grid that's perfectly centered */
                        .print-grid {
                            display: grid !important;
                            grid-template-columns: repeat(3, 3.17in) !important;
                            grid-template-rows: repeat(3, 2.14in) !important;
                            width: 13in !important;
                            height: 8.5in !important;
                            padding: 0.2in !important;
                            box-sizing: border-box !important;
                            justify-items: center !important;
                            align-items: center !important;
                        }
                        
                        .id-card {
                            width: 3.20in !important;
                            height: 2.16in !important;
                            border: 1px solid #000 !important;
                            padding: 5px !important;
                            background: white !important;
                            position: relative !important;
                            overflow: hidden !important;
                            box-sizing: border-box !important;
                            font-family: "Times New Roman", Times, serif !important;
                        }
                        
                        /* ID Header - exactly matches generateFrontPage() */
                        .id-header {
                            display: flex !important;
                            justify-content: space-between !important;
                            align-items: center !important;
                            font-weight:bold !important;
                            text-align: center !important;
                            margin-bottom: 2px !important;
                            width: 100% !important;
                            font-size: 7.5pt !important;
                        }
                        
                        .id-header img {
                            width: 0.49in !important;
                            height: 0.49in !important;
                            object-fit: contain !important;
                            vertical-align: middle !important;
                            display: block !important;
                        }
                        
                        /* ID Content - exactly matches generateFrontPage() */
                        .id-content {
                            font-size: 8pt !important;
                        }
                        
                        .name {
                            display: flex !important;
                            flex-direction: row !important;
                            gap: 1px !important;
                            margin-bottom: 15px !important;
                        }
                        
                        .id-name{
                            font-size: 8pt !important;
                            font-weight: bold !important;
                            text-decoration: underline !important;
                        }
                        
                        .id-address{
                            font-size: 8pt !important;
                            font-weight: bold !important;
                            text-decoration: underline !important;
                        }
                        
                        .dob, .gender, .dateissued{
                            font-size: 8pt !important;
                            font-weight: bold !important;
                            margin-top: 5px !important;
                        }
                        .address {
                            display: flex !important;
                            flex-direction: row !important;
                            margin-bottom: 5px !important;
                        }
                        
                        /* 3rdrow class from generateFrontPage() */
                        .3rdrow {
                            display: flex !important;
                            flex-direction: row !important;
                            justify-content: space-between !important;
                            margin-top: 20px !important;
                        }
                        
                        /* dsd class from generateFrontPage() */
                        .dsd {
                            display: flex !important;
                            flex-direction: column !important;
                            justify-content: start !important;
                            height: 1in !important;
                            width: 2.15in !important;
                        }
                        
                        /* ID picture alignment */
                        .idpicture {
                            height: 1in !important;
                            width: 1in !important;
                            border: 1px solid #000 !important;
                        }

                        id-number {
                            font-weight: bold !important;
                            fontsize: 8pt !important;
                            text-decoration: underline !important;  
                        }
                        validity-number {
                            font-weight: bold !important;
                            fontsize: 8pt !important;
                            text-decoration: underline !important;  
                        }
                        
                        /* Grid for date sections */
                        [style*="display: grid"] {
                            display: grid !important;
                            grid-template-columns: repeat(3, 1fr) !important;
                            gap: 1px !important;
                        }
                        
                        .text-center {
                            text-align: center !important;
                        }
                        
                        /* ID name and address styling */
                        [style*="font-weight: bold"] {
                            font-weight: bold !important;
                        }
                        
                        [style*="font-size: 8pt"] {
                            font-size: 8pt !important;
                        }
                        
                        [style*="text-decoration: underline"] {
                            text-decoration: underline !important;
                        }
                        
                        [style*="margin-left: 5px"] {
                            margin-left: 5px !important;
                        }
                        
                        /* Signature section */
                        [style*="border-bottom: 1px solid #000"] {
                            border-bottom: 1px solid #000 !important;
                            width: 100% !important;
                        }
                        
                        [style*="text-align: left"] {
                            text-align: left !important;
                        }
                        
                        [style*="text-align: right"] {
                            text-align: right !important;
                        }
                        
                        [style*="margin-right:0.5in"] {
                            margin-right: 0.5in !important;
                        }
                        
                        .id-footer {
                            font-size: 7pt !important;
                            font-weight: bold !important;
                            color: red !important;
                            text-align: center !important;
                            margin-top: 2px !important;
                        }
                        
                        /* IMPROVED Benefits card styles - PERFECT VERTICAL ALIGNMENT */
                        .benefits-card {
                            width: 3.30in !important;
                            height: 2.23in !important;
                            border: 1px solid #000 !important;
                            padding: 12px 10px 8px 10px !important;
                            background: white !important;
                            position: relative !important;
                            overflow: hidden !important;
                            box-sizing: border-box !important;
                            display: flex !important;
                            flex-direction: column !important;
                        }
                        
                        .benefits-header {
                            text-align: center !important;
                            font-weight: bold !important;
                            margin-bottom: 6px !important;
                            font-family: "Times New Roman", Times, serif !important;
                            font-size: 6.5pt !important;
                            line-height: 1.1 !important;
                            text-transform: uppercase !important;
                        }
                        
                        .benefits-list {
                            font-size: 5pt !important;
                            font-family: "Times New Roman", Times, serif !important;
                            margin-bottom: 4px !important;
                        }
                        
                        /* FIXED: Perfect vertical alignment for image bullets */
                        .benefits-list div {
                            margin-bottom: 2px !important;
                            display: flex !important;
                            align-items: flex-start !important;
                            gap:15.5px;
                        }
                        
                        /* Image bullet styling - perfectly aligned */
                        .benefits-list div img {
                            width: 3pt !important;
                            height: 3pt !important;
                            margin-right: 4px !important;
                            margin-top: 0.5px !important;
                            flex-shrink: 0 !important;
                            display: inline-block !important;
                            vertical-align: top !important;
                        }
                        
                        /* Text content styling */
                        .benefits-list div span {
                            flex: 1 !important;
                            display: inline-block !important;
                            vertical-align: top !important;
                            line-height: 1.2 !important;
                        }
                        
                        .benefits-footer {
                            text-align: center !important;
                            font-family: "Times New Roman", Times, serif !important;
                            font-size: 5pt !important;
                            margin-top: 16px !important;
                        }
                        
                        .benefits-notice {
                            font-family: "Maiandra GD", "Times New Roman", Times, serif !important;
                            font-style: italic !important;
                            font-size: 6pt !important;
                            margin-top: 10px !important;
                            text-align: center !important;
                            line-height: 1.2 !important;
                            margin-bottom: 2px !important;
                            color: #ff0000 !important;
                        }
                        
                        .signatures-container {
                            display: flex !important;
                            justify-content: space-between !important;
                            align-items: flex-end !important;
                            margin-top: 3px !important;
                        }
                        
                        .signature-item {
                            text-align: center !important;
                            width: 48% !important;
                        }
                        
                        .signature-name {
                            font-weight: bold !important;
                            font-size: 7pt !important;
                            text-decoration: underline !important;
                            margin-bottom: 1px !important;
                            min-height: 8px !important;
                        }
                        
                        .signature-title {
                            font-size: 6.5pt !important;
                            font-weight: bold !important;
                        }
                        
                        /* Additional flex utilities */
                        .flex {
                            display: flex !important;
                        }
                        
                        .flex-row {
                            flex-direction: row !important;
                        }
                        
                        .flex-col {
                            flex-direction: column !important;
                        }
                        
                        .justify-between {
                            justify-content: space-between !important;
                        }
                        
                        .align-start {
                            align-items: flex-start !important;
                        }
                        
                        .align-middle {
                            align-items: center !important;
                        }
                        
                        .mt-1 {
                            margin-top: 1px !important;
                        }
                        
                        @media print {
                            
                            body {
                                margin: 0;
                                padding: 0;
                                font-family: "Times New Roman", Times, serif;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                                display: flex !important;
                                flex-direction: column !important;
                                justify-content: center !important;
                                align-items: center !important;
                                min-height: 100vh !important;
                                background: white !important;
                            }
                            
                            /* Container for centering all pages */
                            .print-container {
                                width: 13in !important;
                                height: 8.5in !important;
                                display: flex !important;
                                justify-content: center !important;
                                align-items: center !important;
                            }
                            
                            .print-page {
                                width: 13in !important;
                                height: 8.5in !important;
                                page-break-after: always !important;
                                display: flex !important;
                                justify-content: center !important;
                                align-items: center !important;
                            }
                            
                            /* Grid that's perfectly centered */
                            .print-grid {
                                display: grid !important;
                                grid-template-columns: repeat(3, 4in) !important;
                                grid-template-rows: repeat(3, 2.40in) !important;
                                width: 13in !important;
                                height: 8.5in !important;
                                padding: 0.2in !important;
                                box-sizing: border-box !important;
                                justify-items: center !important;
                                justify-content: center !important;
                                align-items: center !important;
                            }
                            
                            .id-card {
                                width: 3.35in !important;
                                height: 2.30in !important;
                                border: 1px solid #000 !important;
                                padding-top: 5px !important;
                                padding-bottom: 5px !important;
                                padding-left: 10px !important;
                                padding-right: 10px !important;
                                background: white !important;
                                position: relative !important;
                                overflow: hidden !important;
                                box-sizing: border-box !important;
                                font-family: "Times New Roman", Times, serif !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }

                            /* ID Header - exactly matches generateFrontPage() */
                            .id-header {
                                display: flex !important;
                                justify-content: space-between !important;
                                font-weight:bold !important;
                                text-align: center !important;
                                margin-bottom: 3px !important;
                                width: 100% !important;
                                font-size: 7.5pt !important;
                            }
                            
                            .id-header img {
                                width: 0.49in !important;
                                height: 0.49in !important;
                                object-fit: contain !important;
                                vertical-align: middle !important;
                                display: block !important;
                            }
                            
                            /* ID Content - exactly matches generateFrontPage() */
                            .id-content {
                                font-size: 8pt !important;
                            }
                            .name {
                                display: flex !important;
                                flex-direction: row !important;
                                gap: 1px !important;
                                
                            }
                            
                            /* Ensure 3rdrow and dsd align properly in print */
                            .3rdrow {
                                display: flex !important;
                                flex-direction: row !important;
                                gap: 2px !important;
                                justify-content: space-between !important;
                                align-items: flex-start !important;
                                margin-top: 40px !important;
                                height: 1.1in !important;
                            }
                            
                            .dsd {
                                display: flex !important;
                                flex-direction: column !important;
                                justify-content: start !important;
                                height: 1in !important;
                            }

                            /* ID picture alignment */
                            .idpicture {
                                height: 1.05in !important;
                                width: 1.1in !important;
                                border: 1px solid #000 !important;
                            }

                            .benefits-card {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            
                            .id-header img {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }

                            .id-name{
                                font-size: 8pt !important;
                                font-weight: bold !important;
                                text-decoration: underline !important;
                            }
                            
                            .id-address{
                                font-size: 8pt !important;
                                font-weight: bold !important;
                                text-decoration: underline !important;
                                margin-bottom: 5px !important;
                            }
                            .dob, .gender, .dateissued{
                                font-size: 8pt !important;
                                font-weight: bold !important;
                                margin-top: 5px !important;
                            }

                            id-number {
                                font-weight: bold !important;
                                fontsize: 8pt !important;
                                text-decoration: underline !important;  
                            }

                            validity-number {
                                font-weight: bold !important;
                                fontsize: 8pt !important;
                            }

                            .id-footer {
                                font-size: 7pt !important;
                                font-weight: bold !important;
                                color: red !important;
                                text-align: center !important;
                                margin-top: 2px !important;
                            }
                            /* IMPROVED Benefits card styles - PERFECT VERTICAL ALIGNMENT */

                            .benefits-card {
                                width: 3.35in !important;
                                height: 2.27in !important;
                                border: 1px solid #000 !important;
                                padding: 12px 10px 8px 10px !important;
                                background: white !important;
                                position: relative !important;
                                overflow: hidden !important;
                                box-sizing: border-box !important;
                                display: flex !important;
                                flex-direction: column !important;
                            }
                            /* Ensure consistent list alignment in print */
                            .benefits-list div {
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                                -webkit-column-break-inside: avoid !important;
                            }
                            
                            .benefits-list div img {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                        }
                    </style>
                </head>
                <body>
            `;

            // Add all pages
            allPreviewPages.forEach(page => {
                printHTML += page;
            });

            printHTML += `
                </body>
                </html>
            `;

            printWindow.document.write(printHTML);
            printWindow.document.close();
            printWindow.focus();

            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
                closePreview();
                logIDGenerationToDatabase();
            }, 1000);
        }

        /**
         * Detect user context from URL or session
         */
        function detectUserContext() {
            const url = window.location.href;
            if (url.includes('staff_generate_id.php')) {
                return 'staff';
            } else if (url.includes('generate_id.php')) {
                return 'admin';
            }
            return 'admin'; // default
        }

        /**
         * Log ID generation to database after successful print
         */
        function logIDGenerationToDatabase() {
            if (selectedSeniors.size === 0) {
                console.log('No seniors selected, skipping database log');
                return;
            }

            // Convert selected seniors to array with PROPER ID numbers
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => {
                // Generate a proper ID number if it's 'N/A'
                let idNumber = data.idNumber;
                let localControl = data.localControl;

                if (idNumber === 'N/A' || !idNumber) {
                    idNumber = 'PALUAN-' + Date.now().toString().slice(-6) + '-' + id;
                }

                if (localControl === 'N/A' || !localControl) {
                    localControl = '';
                }

                return {
                    id: id,
                    idNumber: idNumber,
                    localControl: localControl,
                    name: data.name
                };
            });

            // Prepare data for logging with context
            const logData = {
                seniors: seniorsArray,
                osca_head: document.getElementById('osca-head').value,
                municipal_mayor: document.getElementById('municipal-mayor').value,
                user_context: detectUserContext()
            };

            console.log('Logging ID generation for', seniorsArray.length, 'seniors as', logData.user_context);

            // Send AJAX request with context
            fetch('../../php/log_id_generation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify(logData)
                })
                .then(async response => {
                    const responseClone = response.clone();

                    try {
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.error || `HTTP ${response.status}: ${response.statusText}`);
                        }

                        return result;
                    } catch (jsonError) {
                        const text = await responseClone.text();
                        console.error('Raw response text:', text);

                        if (text.includes('<!DOCTYPE') || text.includes('<html') || text.includes('<br />')) {
                            throw new Error('Server returned HTML instead of JSON. Check PHP errors.');
                        }

                        throw new Error(`Invalid response: ${text.substring(0, 200)}`);
                    }
                })
                .then(result => {
                    if (result.success) {
                        console.log('✅ ID generation logged successfully as', result.user_context, 'Batch:', result.batch_number);
                        showNotification('✅ ID generation logged successfully! Batch: ' + result.batch_number, 'success');

                        // Clear selections after successful logging
                        selectedSeniors.clear();
                        localStorage.removeItem('selectedSeniors');
                        document.querySelectorAll('.senior-checkbox').forEach(cb => cb.checked = false);
                        updateSelectedList();
                        updateMasterCheckbox();
                    } else {
                        console.error('❌ Server reported failure:', result.error);
                        showNotification('❌ ID generation failed: ' + result.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('❌ Error logging ID generation:', error);
                    showNotification('❌ Error: ' + error.message, 'error');
                });
        }

        function verifyUserContext() {
            const currentUrl = window.location.href;
            const isStaffPage = currentUrl.includes('staff_generate_id.php');
            const isAdminPage = currentUrl.includes('generate_id.php');

            console.log("🔍 VERIFYING USER CONTEXT");
            console.log("Current URL:", currentUrl);
            console.log("Is Staff Page:", isStaffPage);
            console.log("Is Admin Page:", isAdminPage);

            if (isStaffPage) {
                console.log("🚨 THIS SHOULD BE STAFF CONTEXT");
                // Force staff context in localStorage
                localStorage.setItem('expected_context', 'staff');
            } else if (isAdminPage) {
                console.log("🚨 THIS SHOULD BE ADMIN CONTEXT");
                localStorage.setItem('expected_context', 'admin');
            }
        }

        // Call this when page loads
        document.addEventListener('DOMContentLoaded', function() {
            verifyUserContext();
        });


        /**
         * Show notification message
         */
        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900 dark:border-green-800 dark:text-green-200',
                error: 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900 dark:border-red-800 dark:text-red-200',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700 dark:bg-yellow-900 dark:border-yellow-800 dark:text-yellow-200',
                info: 'bg-blue-100 border-blue-400 text-blue-700 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200'
            };

            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };

            // Remove any existing notifications first
            document.querySelectorAll('.notification').forEach(el => el.remove());

            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 z-50 border-l-4 p-4 rounded-lg shadow-lg ${colors[type]} max-w-sm`;
            notification.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0 text-lg">
                        ${icons[type]}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex items-center justify-center h-8 w-8 hover:bg-gray-200 dark:hover:bg-gray-700" onclick="this.parentElement.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }


        /**
         * Check if a senior already has an ID (for UI indication)
         */
        async function checkIDStatus(applicantId) {
            try {
                const response = await fetch(`../../php/check_id_status.php?applicant_id=${applicantId}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error checking ID status:', error);
                return {
                    has_id: false
                };
            }
        }


        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('signatoryModal');
            if (event.target === modal) {
                closeSignatoryModal();
            }
        };
    </script>
</body>

</html>