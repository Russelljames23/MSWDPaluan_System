<?php
// logout.php - Improved version with better session isolation
if (isset($_GET['session_context'])) {
    session_name("SESS_" . $_GET['session_context']);
}
session_start();

// Include ActivityLogger for logging
require_once '../settings/ActivityLogger.php';

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['username'] ?? 'Unknown';
    $session_context = $_SESSION['session_context'] ?? 'unknown';

    // Database connection for logging
    $host = 'localhost';
    $dbname = 'u401132124_mswd_seniors';
    $username = 'u401132124_mswdopaluan';
    $password = 'Mswdo_PaluanSystem23';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Log logout activity
        $logger = new ActivityLogger($pdo, $user_id, $user_name);
        $logger->log('LOGOUT', 'User logged out of the system', [
            'session_id' => session_id(),
            'session_context' => $session_context,
            'logout_time' => date('Y-m-d H:i:s')
        ]);

        // Update logout time in user_sessions
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET logout_time = NOW() 
            WHERE user_id = ? AND session_id = ? AND logout_time IS NULL
        ");
        $stmt->execute([$user_id, session_id()]);
    } catch (PDOException $e) {
        error_log("Failed to update logout time: " . $e->getMessage());
        
        // Try to log the error anyway
        try {
            $logger = new ActivityLogger($pdo, $user_id, $user_name);
            $logger->log('ERROR', 'Logout database error', [
                'error_message' => $e->getMessage()
            ]);
        } catch (Exception $ex) {
            error_log("Failed to log logout error: " . $ex->getMessage());
        }
    }
}

// Only destroy current user's session data, not the entire session
$current_user_data = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'session_context' => $_SESSION['session_context'] ?? null
];

// Clear only this user's session data
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['fullname']);
unset($_SESSION['user_type']);
unset($_SESSION['role_id']);
unset($_SESSION['role_name']);
unset($_SESSION['permissions']);
unset($_SESSION['logged_in']);
unset($_SESSION['is_verified']);
unset($_SESSION['session_context']);
unset($_SESSION['last_activity']);
unset($_SESSION['login_time']);

// Also clear any pending verification data
if (isset($_SESSION['pending_login'])) {
    unset($_SESSION['pending_login']);
}
if (isset($_SESSION['verification_code'])) {
    unset($_SESSION['verification_code']);
}
if (isset($_SESSION['verification_expires'])) {
    unset($_SESSION['verification_expires']);
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Redirect to login page
header('Location: ../../index.php');
exit;