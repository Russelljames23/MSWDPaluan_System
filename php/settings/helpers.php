<?php
// helpers.php
require_once '../db.php';
require_once 'ActivityLogger.php';

/**
 * Get or create ActivityLogger instance
 */
function getActivityLogger()
{
    global $conn;
    static $logger = null;

    if ($logger === null) {
        $logger = new ActivityLogger($conn);
    }

    return $logger;
}

/**
 * Log activity from anywhere in the application
 */
function logActivity($activityType, $description, $details = [])
{
    // Check if session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verify we have a user ID in session
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
        error_log("Cannot log activity: No user ID in session");
        return false;
    }

    try {
        $logger = getActivityLogger();
        return $logger->log($activityType, $description, $details);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log with user verification
 */
function logActivityWithUser($activityType, $description, $details = [])
{
    // Check session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get user info
    $userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

    if (!$userId) {
        error_log("No user ID for activity: {$activityType}");
        return false;
    }

    // Add user info to details
    $details['logged_user_id'] = $userId;
    $details['logged_username'] = $_SESSION['username'] ?? 'unknown';
    $details['logged_fullname'] = $_SESSION['fullname'] ?? ($_SESSION['firstname'] . ' ' . $_SESSION['lastname'] ?? 'unknown');

    return logActivity($activityType, $description, $details);
}
