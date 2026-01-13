<?php
// UserSessionManager.php
date_default_timezone_set('Asia/Manila');
class UserSessionManager
{
    private static $instance = null;
    private $sessionPrefix = '';
    
    private function __construct() {}
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize session for Admin or Staff
     */
    public function initSession($userType, $sessionContext = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            if ($sessionContext) {
                $sessionName = 'SESS_' . $sessionContext;
                session_name($sessionName);
            }
            
            @session_start();
        }
        
        // Set session context
        if ($userType === 'Admin') {
            $_SESSION['session_context'] = 'admin';
        } else {
            $_SESSION['session_context'] = 'staff';
        }
        
        // Store browser context if provided
        if ($sessionContext) {
            $_SESSION['browser_session_context'] = $sessionContext;
        }
        
        return session_id();
    }
    
    /**
     * Get current user type based on session
     */
    public function getCurrentUserType()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        return $_SESSION['session_context'] ?? $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Check if user is Admin
     */
    public function isAdmin()
    {
        $userType = $this->getCurrentUserType();
        return $userType === 'admin' || (isset($_SESSION['user_type']) && strpos($_SESSION['user_type'], 'Admin') !== false);
    }
    
    /**
     * Check if user is Staff
     */
    public function isStaff()
    {
        $userType = $this->getCurrentUserType();
        return $userType === 'staff' || (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Staff');
    }
    
    /**
     * Get user ID from session
     */
    public function getUserId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        $possibleIds = [
            $_SESSION['user_id'] ?? null,
            $_SESSION['id'] ?? null,
            $_SESSION['verification_user_id'] ?? null
        ];
        
        foreach ($possibleIds as $id) {
            if ($id && $id > 0) {
                return $id;
            }
        }
        
        return null;
    }
    
    /**
     * Get user name for display
     */
    public function getUserDisplayName()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
            return $_SESSION['fullname'];
        }
        
        if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
            $name = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
            if (isset($_SESSION['middlename']) && !empty($_SESSION['middlename'])) {
                $name = $_SESSION['firstname'] . ' ' . $_SESSION['middlename'] . ' ' . $_SESSION['lastname'];
            }
            return $name;
        }
        
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }
        
        return 'Unknown User';
    }
    
    /**
     * Clean up session
     */
    public function cleanup()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Remove verification session data
        unset($_SESSION['verification_code']);
        unset($_SESSION['verification_expires']);
        unset($_SESSION['pending_login']);
        unset($_SESSION['verification_user_id']);
        
        // Keep essential login data
        $essentialKeys = ['user_id', 'username', 'user_type', 'fullname', 'firstname', 'lastname', 'session_context', 'logged_in'];
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $essentialKeys)) {
                unset($_SESSION[$key]);
            }
        }
    }
}