<?php
// session_helper.php
date_default_timezone_set('Asia/Manila');
class SessionHelper {
    
    /**
     * Initialize session safely
     */
    public static function safeStart($sessionContext = null) {
        if (session_status() === PHP_SESSION_NONE) {
            if ($sessionContext && preg_match('/^[a-zA-Z0-9-]+$/', $sessionContext)) {
                session_name('SESS_' . $sessionContext);
            }
            
            session_start();
            
            // Set session context
            if (!isset($_SESSION['session_context'])) {
                $_SESSION['session_context'] = $sessionContext ?: 'default';
            }
            
            // Initialize user data if not set
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = 0;
            }
        }
        
        return true;
    }
    
    /**
     * Get current user ID with fallback
     */
    public static function getUserId($conn = null) {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // If no user ID, try to get from database using username
        if (!$userId && $conn && isset($_SESSION['username'])) {
            try {
                $stmt = $conn->prepare(
                    "SELECT id FROM users WHERE username = ? AND status = 'active' LIMIT 1"
                );
                $stmt->execute([$_SESSION['username']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $userId = $user['id'];
                    $_SESSION['user_id'] = $userId;
                }
            } catch (Exception $e) {
                error_log("Error getting user ID: " . $e->getMessage());
            }
        }
        
        return $userId ?: 57; // Default to admin
    }
    
    /**
     * Get user name from session
     */
    public static function getUserName() {
        if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
            return $_SESSION['fullname'];
        }
        
        if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
            return trim($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
        }
        
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }
        
        return 'Unknown User';
    }
    
    /**
     * Check if user is staff
     */
    public static function isStaff() {
        return ($_SESSION['session_context'] ?? '') === 'staff' || 
               (isset($_SESSION['user_type']) && stripos($_SESSION['user_type'], 'staff') !== false);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return ($_SESSION['session_context'] ?? '') === 'admin' || 
               (isset($_SESSION['user_type']) && stripos($_SESSION['user_type'], 'admin') !== false);
    }
}