<?php
// context_manager.php - Force correct user context based on page
class ContextManager {
    
    /**
     * Initialize context based on current page
     * This should be called at the beginning of every page
     */
    public static function initialize() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        
        // FORCE context based on page URL
        if (strpos($script_name, 'staff_generate_id.php') !== false || 
            strpos($current_url, 'staff_generate_id.php') !== false) {
            
            // STAFF PAGE - Force staff context
            $_SESSION['page_context'] = 'staff';
            $_SESSION['current_page'] = 'staff_generate_id.php';
            
            // Store original user type if not already stored
            if (!isset($_SESSION['original_user_type']) && isset($_SESSION['user_type'])) {
                $_SESSION['original_user_type'] = $_SESSION['user_type'];
            }
            
            // Override user_type for this session (temporarily)
            $_SESSION['user_type'] = 'Staff';
            
            error_log("🚨 CONTEXT MANAGER: Forcing STAFF context for staff page");
            
        } elseif (strpos($script_name, 'generate_id.php') !== false || 
                  strpos($current_url, 'generate_id.php') !== false) {
            
            // ADMIN PAGE - Force admin context
            $_SESSION['page_context'] = 'admin';
            $_SESSION['current_page'] = 'generate_id.php';
            
            // Restore original user type if we were in staff mode
            if (isset($_SESSION['original_user_type'])) {
                $_SESSION['user_type'] = $_SESSION['original_user_type'];
                unset($_SESSION['original_user_type']);
            }
            
            error_log("🚨 CONTEXT MANAGER: Forcing ADMIN context for admin page");
        }
        
        // Log current context
        error_log("Context Manager - Page: " . ($_SESSION['current_page'] ?? 'unknown') . 
                  " | Context: " . ($_SESSION['page_context'] ?? 'unknown') . 
                  " | User Type: " . ($_SESSION['user_type'] ?? 'unknown'));
    }
    
    /**
     * Get current context for logging
     */
    public static function getContext() {
        return $_SESSION['page_context'] ?? 'admin';
    }
    
    /**
     * Check if we're in staff mode
     */
    public static function isStaffMode() {
        return self::getContext() === 'staff';
    }
    
    /**
     * Get user info with forced context
     */
    public static function getUserInfoForLogging($user_id = null) {
        global $conn;
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        $context = self::getContext();
        
        error_log("=== CONTEXT MANAGER GET USER INFO ===");
        error_log("Context: " . $context);
        error_log("Page: " . ($_SESSION['current_page'] ?? 'unknown'));
        
        $actualUserId = $user_id;
        
        // If no user_id provided, get from session
        if ($actualUserId === null || $actualUserId === 0) {
            $actualUserId = $_SESSION['user_id'] ?? 0;
        }
        
        // Default to admin if no user found
        if (!$actualUserId || $actualUserId === 0) {
            $actualUserId = 57; // Default admin
        }
        
        // Get user from database
        $user_info = null;
        try {
            $sql = "SELECT id, firstname, lastname, CONCAT(firstname, ' ', lastname) as fullname, 
                           user_type, username, status 
                    FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$actualUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // FORCE user type based on context, not database
                if ($context === 'staff') {
                    $user_type = 'Staff';
                    $display_type = 'Staff';
                } else {
                    // Use database type for admin context
                    $db_type = strtolower($user['user_type'] ?? '');
                    $user_type = ucfirst($db_type);
                    if (empty($user_type)) {
                        $user_type = 'Admin';
                    }
                    $display_type = $user_type;
                }
                
                $user_info = [
                    'id' => $user['id'],
                    'firstname' => $user['firstname'] ?? '',
                    'lastname' => $user['lastname'] ?? '',
                    'name' => $user['fullname'] ? trim($user['fullname']) : ($user['username'] ?? 'Unknown User'),
                    'username' => $user['username'] ?? '',
                    'user_type' => $user_type,
                    'display_type' => $display_type,
                    'status' => $user['status'] ?? 'Unknown',
                    'context' => $context,
                    'db_user_type' => $user['user_type'] ?? '',
                    'is_forced_context' => ($context === 'staff' && strpos(strtolower($user['user_type'] ?? ''), 'admin') !== false)
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching user: " . $e->getMessage());
        }
        
        // If no user found, create default
        if (!$user_info) {
            if ($context === 'staff') {
                $user_name = 'Staff User';
                $user_type = 'Staff';
            } else {
                $user_name = 'System Administrator';
                $user_type = 'Admin';
            }
            
            $user_info = [
                'id' => $actualUserId,
                'name' => $user_name,
                'user_type' => $user_type,
                'display_type' => $user_type,
                'context' => $context,
                'db_user_type' => $user_type,
                'is_forced_context' => false
            ];
        }
        
        error_log("Context Manager User Info: " . print_r($user_info, true));
        return $user_info;
    }
}
?>