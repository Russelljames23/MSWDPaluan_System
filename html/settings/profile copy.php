<?php
require_once "../../php/login/admin_header.php";
$ctx = urlencode($_GET['session_context'] ?? session_id());

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mswd_seniors";

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

// Check if users table has profile_photo column, if not add it
if ($pdo && $user_id) {
    try {
        // Check if profile_photo column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_photo'");
        $stmt->execute();
        $column_exists = $stmt->rowCount() > 0;

        if (!$column_exists) {
            // Add profile_photo column
            $alter_sql = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER address";
            $pdo->exec($alter_sql);
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding profile_photo column: " . $e->getMessage());
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

// Handle profile photo upload
$photo_message = '';
$photo_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $pdo) {
    try {
        $upload_dir = "../../uploads/profile_photos/";

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['profile_photo'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $file['error']);
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed.');
        }

        // Validate file size (max 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $max_size) {
            throw new Exception('File size must be less than 2MB.');
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $destination = $upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save uploaded file.');
        }

        // Update database with new photo path
        $relative_path = "uploads/profile_photos/" . $unique_filename;
        $stmt = $pdo->prepare("UPDATE users SET profile_photo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$relative_path, $user_id]);

        // Update session and user data
        $user_data['profile_photo'] = $relative_path;
        $_SESSION['profile_photo'] = $relative_path;

        $photo_message = 'Profile photo updated successfully!';
        $photo_message_type = 'success';
    } catch (Exception $e) {
        $photo_message = $e->getMessage();
        $photo_message_type = 'error';
    }
}

// Handle profile update
$update_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $pdo) {
    try {
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $birthdate = $_POST['birthdate'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Basic validation
        if (empty($firstname) || empty($lastname) || empty($email)) {
            throw new Exception('First name, last name, and email are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Check if email exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already exists.');
        }

        // Password validation
        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters.');
            }
            if ($password !== $confirm_password) {
                throw new Exception('Passwords do not match.');
            }
        }

        // Prepare update query
        $update_fields = [
            'firstname' => $firstname,
            'middlename' => $middlename,
            'lastname' => $lastname,
            'birthdate' => $birthdate,
            'email' => $email,
            'contact_no' => $contact_no,
            'address' => $address
        ];

        // Add password if provided
        if (!empty($password)) {
            $update_fields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Build SQL query
        $set_clause = [];
        $params = [];
        foreach ($update_fields as $field => $value) {
            $set_clause[] = "$field = ?";
            $params[] = $value;
        }
        $params[] = $user_id;

        $sql = "UPDATE users SET " . implode(', ', $set_clause) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute($params)) {
            // Update session data
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['email'] = $email;
            $_SESSION['fullname'] = $lastname . ', ' . $firstname;

            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            $update_message = 'Profile updated successfully!';
            $message_type = 'success';
        } else {
            throw new Exception('Failed to update profile.');
        }
    } catch (Exception $e) {
        $update_message = $e->getMessage();
        $message_type = 'error';
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Profile</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

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

        .nav-list li a.active-link {
            color: #1d4ed8;
            font-weight: 600;
            border-color: #1d4ed8;
            background: #eff6ff;
        }

        .nav-list li a.active-link svg {
            color: #1d4ed8;
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

        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e5e7eb;
        }

        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
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
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50"
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
                        <img class="w-8 h-8 rounded-full"
                            src="https://spng.pngfind.com/pngs/s/378-3780189_member-icon-png-transparent-png.png"
                            alt="user photo" />
                    </button>
                    <!-- Dropdown menu -->
                    <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
                        id="dropdown">
                        <div class="py-3 px-4">
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                <?php
                                // Display fullname with fallback
                                if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                                    echo htmlspecialchars($_SESSION['fullname']);
                                } else if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                                    // Construct fullname from first and last name if available
                                    echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
                                } else {
                                    echo 'User';
                                }
                                ?>
                            </span>
                            <span class="block text-sm text-gray-900 truncate dark:text-white">
                                <?php
                                // Display user type with proper formatting
                                if (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
                                    echo htmlspecialchars($_SESSION['user_type']);
                                } else if (isset($_SESSION['role_name']) && !empty($_SESSION['role_name'])) {
                                    // Fallback to role_name if available
                                    echo htmlspecialchars($_SESSION['role_name']);
                                } else {
                                    echo 'User Type';
                                }
                                ?>
                            </span>
                        </div>
                        <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
                            <li>
                                <a href="../../php/login/logout.php"
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
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-blue hover:bg-blue-100 dark:hover:bg-blue-700 group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">

                                <!-- Top-left (taller) -->
                                <rect x="3" y="3" width="8" height="10" rx="1.5" />

                                <!-- Top-right (smaller) -->
                                <rect x="13" y="3" width="8" height="6" rx="1.5" />

                                <!-- Bottom-left (smaller) -->
                                <rect x="3" y="15" width="8" height="6" rx="1.5" />

                                <!-- Bottom-right (taller) -->
                                <rect x="13" y="11" width="8" height="10" rx="1.5" />

                            </svg>

                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-blue-100 dark:hover:bg-gray-700 dark:text-white group">
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
                                <a href="../SeniorList/activelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Active
                                    List</a>
                            </li>
                            <li>
                                <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Inactive
                                    List</a>
                            </li>
                            <li>
                                <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-blue-100 dark:text-white dark:hover:bg-gray-700">Deceased
                                    List</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
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
                        <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
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

        <!-- Main content -->
        <main class="p-4 md:ml-64 pt-20">
            <div class="flex flex-row justify-between gap-2">
                <!-- Settings Sidebar -->
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
                            <a id="button" class="cursor-pointer active-link">
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
                            <a href="sms.php?session_context=<?php echo $ctx; ?>" class="cursor-pointer">
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

                <!-- Profile Section -->
                <section id="profileSection" class="bg-gray-50 dark:bg-gray-900 w-full">
                    <div class="mx-auto max-w-screen-xl">
                        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg px-5">
                            <!-- Success/Error Messages -->
                            <?php if (!empty($photo_message)): ?>
                                <div class="mb-4 p-4 rounded-lg <?php echo $photo_message_type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'; ?>">
                                    <?php echo htmlspecialchars($photo_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($update_message)): ?>
                                <div class="mb-4 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'; ?>">
                                    <?php echo htmlspecialchars($update_message); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Profile Header -->
                            <div class="flex flex-col pb-10 border-b border-gray-200 dark:border-gray-700 md:flex-row justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                                <div class="flex flex-row gap-4 items-center">
                                    <div class="relative inline-block">
                                        <img id="profileImage" class="w-20 h-20 rounded-full ring-2 ring-gray-300 dark:ring-gray-500 object-cover"
                                            src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                                            alt="Profile avatar" />
                                        <div class="absolute -bottom-4 right-0 group">
                                            <button type="button" id="changePhotoBtn"
                                                class="relative bg-white text-gray-400 hover:text-gray-900 rounded-full cursor-pointer hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700 shadow-md">
                                                <svg class="w-5 h-5 text-blue-800 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="square"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 0 1 3-3h1m4-6a3 3 0 0 1 3-3h1m-4 18a3 3 0 0 0 3-3v-1m-9 3a3 3 0 0 1-3-3v-1m9-3a3 3 0 0 1 3 3v1m-9-3a3 3 0 0 0-3 3v1m9-9a3 3 0 0 0-3-3h-1m-6 3a3 3 0 0 1 3-3h1m-6 6a3 3 0 0 1 3 3v1m6-9a3 3 0 0 1 3 3v1m-9-6a3 3 0 0 0-3-3h-1" />
                                                </svg>
                                            </button>
                                            <span class="absolute bottom-10 right-1/2 translate-x-1/2 mb-1 hidden group-hover:block px-2 py-1 text-xs text-white bg-gray-700/80 rounded-md shadow-lg whitespace-nowrap">
                                                Change Photo
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-lg font-semibold dark:text-white">
                                            <?php echo htmlspecialchars($full_name ?: 'User Profile'); ?>
                                        </h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            <?php echo htmlspecialchars($user_data['email'] ?? 'No email'); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            User Type: <?php echo htmlspecialchars($user_data['user_type'] ?? 'N/A'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Form -->
                            <div class="flex flex-col md:flex-row items-center justify-center space-y-3 md:space-y-0 py-6">
                                <form method="POST" class="w-full max-w-2xl" id="profileForm" enctype="multipart/form-data">
                                    <div class="flex flex-col gap-4">
                                        <!-- Name Fields -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label for="firstname" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">First Name *</label>
                                                <input type="text" id="firstname" name="firstname" required
                                                    value="<?php echo htmlspecialchars($user_data['firstname'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                            <div>
                                                <label for="middlename" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Middle Name</label>
                                                <input type="text" id="middlename" name="middlename"
                                                    value="<?php echo htmlspecialchars($user_data['middlename'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                            <div>
                                                <label for="lastname" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Last Name *</label>
                                                <input type="text" id="lastname" name="lastname" required
                                                    value="<?php echo htmlspecialchars($user_data['lastname'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                        </div>

                                        <!-- Personal Info -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="birthdate" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Birthdate</label>
                                                <input type="date" id="birthdate" name="birthdate"
                                                    value="<?php echo htmlspecialchars($user_data['birthdate'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                            <div>
                                                <label for="gender" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Gender</label>
                                                <select id="gender" name="gender" disabled
                                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed">
                                                    <option><?php echo htmlspecialchars($user_data['gender'] ?? 'Not specified'); ?></option>
                                                </select>
                                                <p class="text-xs text-gray-500 mt-1">Gender cannot be changed</p>
                                            </div>
                                        </div>

                                        <!-- Contact Info -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="email" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Email *</label>
                                                <input type="email" id="email" name="email" required
                                                    value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                            <div>
                                                <label for="contact_no" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Contact Number</label>
                                                <input type="tel" id="contact_no" name="contact_no"
                                                    value="<?php echo htmlspecialchars($user_data['contact_no'] ?? ''); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div>
                                            <label for="address" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Address</label>
                                            <textarea id="address" name="address" rows="2"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                        </div>

                                        <!-- Password Section -->
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Change Password</h3>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="password" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">New Password</label>
                                                    <input type="password" id="password" name="password"
                                                        placeholder="Leave blank to keep current"
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                                                </div>
                                                <div>
                                                    <label for="confirm_password" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Confirm Password</label>
                                                    <input type="password" id="confirm_password" name="confirm_password"
                                                        placeholder="Confirm new password"
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex justify-end gap-3 pt-6">
                                            <button type="submit" name="update_profile"
                                                class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">
                                                Update Profile
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Photo Upload Modal - Tailwind version -->
    <div id="photoModal" class="fixed inset-0 bg-gray-900/50 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-lg font-semibold">Change Profile Photo</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="photoUploadForm" enctype="multipart/form-data" method="POST" class="p-6">
                <div class="mb-6">
                    <div class="w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden border-4 border-gray-200">
                        <img id="imagePreview" src="<?php echo htmlspecialchars($profile_photo_url); ?>" alt="Preview" class="w-full h-full object-cover">
                    </div>

                    <label class="block mb-2 text-sm font-medium text-gray-700" for="profile_photo">
                        Choose new photo
                    </label>
                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none"
                        id="profile_photo" name="profile_photo" type="file" accept="image/*">
                    <p class="mt-2 text-sm text-gray-500">
                        JPG, PNG, GIF or WebP (Max. 2MB)
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelUpload" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        Upload Photo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Simplified modal functions for Tailwind version
        function showModal() {
            document.getElementById('photoModal').classList.remove('hidden');
            document.getElementById('photoModal').classList.add('flex');
        }

        function hideModal() {
            document.getElementById('photoModal').classList.add('hidden');
            document.getElementById('photoModal').classList.remove('flex');
            // Reset file input
            document.getElementById('profile_photo').value = '';
            // Reset preview
            document.getElementById('imagePreview').src = document.getElementById('profileImage').src;
        }

        // Set up event listeners
        document.getElementById('changePhotoBtn').addEventListener('click', showModal);
        document.getElementById('closeModal').addEventListener('click', hideModal);
        document.getElementById('cancelUpload').addEventListener('click', hideModal);

        // Close modal when clicking outside
        document.getElementById('photoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('photoModal').classList.contains('hidden')) {
                hideModal();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        // Sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector(".sidebar");
            const closeBtn = document.querySelector("#btn");

            if (closeBtn) {
                closeBtn.addEventListener("click", () => {
                    sidebar.classList.toggle("open");
                });
            }

            // Password confirmation validation
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const profileForm = document.getElementById('profileForm');

            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const password = passwordField.value;
                    const confirmPassword = confirmPasswordField.value;

                    if (password && password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        confirmPasswordField.focus();
                        return false;
                    }

                    if (password && password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        passwordField.focus();
                        return false;
                    }

                    return true;
                });
            }

            // Initialize Flowbite dropdowns
            const dropdownButton = document.getElementById('user-menu-button');
            const dropdown = document.getElementById('dropdown');
            if (dropdownButton && dropdown) {
                dropdownButton.addEventListener('click', function() {
                    dropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!dropdownButton.contains(event.target) && !dropdown.contains(event.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }

            // Photo upload modal functionality
            const changePhotoBtn = document.getElementById('changePhotoBtn');
            const photoModal = document.getElementById('photoModal');
            const closeModalBtn = document.getElementById('closeModal');
            const cancelUploadBtn = document.getElementById('cancelUpload');
            const photoUploadForm = document.getElementById('photoUploadForm');
            const fileInput = document.getElementById('profile_photo');
            const imagePreview = document.getElementById('imagePreview');
            const profileImage = document.getElementById('profileImage');

            // Open modal
            if (changePhotoBtn) {
                changePhotoBtn.addEventListener('click', function() {
                    photoModal.classList.remove('hidden');
                });
            }

            // Close modal
            function closeModal() {
                photoModal.classList.add('hidden');
                fileInput.value = '';
                imagePreview.src = profileImage.src;
            }

            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', closeModal);
            }

            if (cancelUploadBtn) {
                cancelUploadBtn.addEventListener('click', closeModal);
            }

            // Close modal when clicking outside
            photoModal.addEventListener('click', function(event) {
                if (event.target === photoModal) {
                    closeModal();
                }
            });

            // Preview image before upload
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Handle photo upload form submission
            if (photoUploadForm) {
                photoUploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const file = fileInput.files[0];
                    if (!file) {
                        alert('Please select a photo to upload.');
                        return;
                    }

                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size must be less than 2MB.');
                        return;
                    }

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                        return;
                    }

                    // Create FormData for AJAX upload
                    const formData = new FormData(this);

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Uploading...';
                    submitBtn.disabled = true;

                    // Send AJAX request
                    fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            // Reload the page to show the updated photo
                            window.location.reload();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while uploading the photo.');
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                        });
                });
            }

            // Auto-hide success messages after 5 seconds
            const successMessages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            successMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>

</html>