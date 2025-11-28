<?php
// check_session.php - Enhanced version with role isolation and session context
// if (isset($_GET['session_context'])) {
//     session_name("SESS_" . $_GET['session_context']);
// }
// session_start();

function checkAuth()
{
    // Check if user is logged in and session is valid
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Store the current URL for redirecting back after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /MSWDPALUAN_SYSTEM-MAIN/html/login.php');
        exit;
    }

    // Additional check for email verification
    if (!isset($_SESSION['is_verified']) || $_SESSION['is_verified'] !== true) {
        // Only destroy this user's session data
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['fullname']);
        unset($_SESSION['user_type']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['is_verified']);
        unset($_SESSION['session_context']);

        header('Location: /MSWDPALUAN_SYSTEM-MAIN/html/login.php?error=not_verified');
        exit;
    }

    // Session timeout (2 hours)
    $session_timeout = 2 * 60 * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        // Session expired - only clear current user's data
        $current_user_id = $_SESSION['user_id'] ?? null;
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['fullname']);
        unset($_SESSION['user_type']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['is_verified']);
        unset($_SESSION['session_context']);

        header('Location: /MSWDPALUAN_SYSTEM-MAIN/html/login.php?error=session_expired');
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

function isAdmin()
{
    return isset($_SESSION['user_type']) && isset($_SESSION['session_context']) &&
        $_SESSION['session_context'] === 'admin' &&
        (strpos($_SESSION['user_type'], 'Admin') === 0 ||
            $_SESSION['user_type'] === 'Super Admin' ||
            $_SESSION['user_type'] === 'Admin');
}

function isStaff()
{
    return isset($_SESSION['user_type']) && isset($_SESSION['session_context']) &&
        $_SESSION['session_context'] === 'staff' &&
        ($_SESSION['user_type'] === 'Staff' ||
            $_SESSION['user_type'] === 'Staff Manager' ||
            $_SESSION['user_type'] === 'Data Entry' ||
            $_SESSION['user_type'] === 'Viewer');
}

function getUserInfo()
{
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'fullname' => $_SESSION['fullname'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'session_context' => $_SESSION['session_context'] ?? null,
        'session_id' => session_id()
    ];
}

// NEW FUNCTION: Validate session context for current request
function validateSessionContext($expected_context) {
    if (!isset($_SESSION['session_context']) || $_SESSION['session_context'] !== $expected_context) {
        // Session context mismatch - this prevents admin from accessing staff pages and vice versa
        return false;
    }
    return true;
}

// Auto-check authentication if this file is included
checkAuth();
?>