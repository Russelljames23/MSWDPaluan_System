<?php
// staff_session_sync.php - Include this in all staff pages

function syncStaffSession($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // If we're in staff context but don't have staff_user_id set
    if ((isset($_SESSION['session_context']) && $_SESSION['session_context'] === 'staff') && 
        (!isset($_SESSION['staff_user_id']) || empty($_SESSION['staff_user_id']))) {
        
        // Try to get from user_id
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // Verify it's a staff user
            try {
                $stmt = $pdo->prepare(
                    "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
                );
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && stripos($user['user_type'], 'staff') !== false) {
                    $_SESSION['staff_user_id'] = $_SESSION['user_id'];
                    error_log("Synced staff_user_id: " . $_SESSION['staff_user_id']);
                }
            } catch (Exception $e) {
                error_log("Error syncing staff session: " . $e->getMessage());
            }
        }
    }
    
    // Ensure we have session_context set
    if (!isset($_SESSION['session_context'])) {
        // Check URL to determine context
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUrl, '/staff/') !== false || strpos($currentUrl, 'staff_') !== false) {
            $_SESSION['session_context'] = 'staff';
        } else {
            $_SESSION['session_context'] = 'admin';
        }
    }
}