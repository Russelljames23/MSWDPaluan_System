<?php
// ActivityLogger.php - Enhanced version
class ActivityLogger
{
    private $conn;
    private $defaultUserId = 57; // Admin user ID as fallback

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Log an activity with current session user
     */
    public function log($activityType, $description, $details = null)
    {
        try {
            error_log("=== ActivityLogger Started ===");

            // Get user ID using multiple fallback methods
            $userId = $this->resolveUserId();
            $userName = $this->getUserName();

            error_log("Resolved User ID: {$userId}, User Name: {$userName}");

            // Get user IP address
            $ipAddress = $this->getClientIp();

            // Get user agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $userAgent = str_replace("\0", '', $userAgent);

            // Prepare the query
            $query = "INSERT INTO activity_logs 
                     (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                     VALUES (:user_id, :activity_type, :description, :activity_details, :ip_address, :user_agent, NOW())";

            $stmt = $this->conn->prepare($query);

            // Prepare details as JSON
            $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

            $result = $stmt->execute([
                ':user_id' => $userId,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':activity_details' => $detailsJson,
                ':ip_address' => $ipAddress,
                ':user_agent' => substr($userAgent, 0, 500)
            ]);

            $lastId = $this->conn->lastInsertId();
            error_log("ActivityLogger: Log inserted successfully! ID: {$lastId}, Type: {$activityType}, User: {$userId}");

            return $lastId > 0;
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            error_log("ActivityLogger Trace: " . $e->getTraceAsString());

            // Try a simpler insert as fallback
            try {
                error_log("Trying fallback log method...");
                $fallbackStmt = $this->conn->prepare(
                    "INSERT INTO activity_logs (user_id, activity_type, description, created_at) 
                     VALUES (?, ?, ?, NOW())"
                );
                $fallbackStmt->execute([$userId ?? $this->defaultUserId, $activityType, $description]);
                error_log("Fallback log successful");
                return true;
            } catch (Exception $fallbackE) {
                error_log("Fallback logging also failed: " . $fallbackE->getMessage());
                return false;
            }
        }
    }

    /**
     * Resolve user ID from multiple sources
     */
    private function resolveUserId()
    {
        error_log("=== ActivityLogger: resolveUserId ===");

        // Log session for debugging
        error_log("Session data available: " . (!empty($_SESSION) ? 'YES' : 'NO'));
        if (!empty($_SESSION)) {
            error_log("Session context: " . ($_SESSION['session_context'] ?? 'none'));
            error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'none'));
            error_log("Session staff_user_id: " . ($_SESSION['staff_user_id'] ?? 'none'));
        }

        // Method 1: Check session context first
        if (isset($_SESSION['session_context'])) {
            $context = $_SESSION['session_context'];
            error_log("Found session context: " . $context);

            if ($context === 'staff') {
                // For staff, check in this order:
                $staffKeys = ['staff_user_id', 'user_id', 'id'];
                foreach ($staffKeys as $key) {
                    if (isset($_SESSION[$key]) && !empty($_SESSION[$key]) && $_SESSION[$key] > 0) {
                        $userId = $_SESSION[$key];
                        error_log("Using staff ID from session[{$key}]: " . $userId);

                        // Quick check if it's a staff user
                        if ($this->isStaffUser($userId)) {
                            return $userId;
                        }
                    }
                }
            } else if ($context === 'admin') {
                // For admin, check in this order:
                $adminKeys = ['admin_user_id', 'user_id', 'id'];
                foreach ($adminKeys as $key) {
                    if (isset($_SESSION[$key]) && !empty($_SESSION[$key]) && $_SESSION[$key] > 0) {
                        $userId = $_SESSION[$key];
                        error_log("Using admin ID from session[{$key}]: " . $userId);
                        return $userId;
                    }
                }
            }
        }

        // Method 2: Check direct user_id
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $userId = $_SESSION['user_id'];
            error_log("Using user_id from session: " . $userId);
            return $userId;
        }

        // Method 3: Check for staff context in URL
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUrl, '/staff/') !== false || strpos($currentUrl, 'staff_') !== false) {
            error_log("Detected staff URL, looking for staff user");
            $staffId = $this->findAnyStaffUser();
            if ($staffId) {
                error_log("Found staff user: " . $staffId);
                return $staffId;
            }
        }

        // Method 4: Default to Admin
        error_log("Defaulting to Admin ID: " . $this->defaultUserId);
        return $this->defaultUserId;
    }

    /**
     * Check if a user is a staff member
     */
    private function isStaffUser($userId)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT user_type FROM users WHERE id = ? AND status = 'active'"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userType = strtolower($user['user_type']);
                $isStaff = (
                    strpos($userType, 'staff') !== false ||
                    strpos($userType, 'data entry') !== false ||
                    strpos($userType, 'viewer') !== false
                );
                error_log("User {$userId} is staff: " . ($isStaff ? 'YES' : 'NO'));
                return $isStaff;
            }
        } catch (Exception $e) {
            error_log("Error checking if user is staff: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Find user ID by username
     */
    private function findUserIdByUsername($username)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id, user_type FROM users WHERE username = ? AND status = 'active' LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ? $user['id'] : null;
        } catch (Exception $e) {
            error_log("Error finding user ID by username: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find staff ID by username
     */
    private function findStaffIdByUsername($username)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id FROM users WHERE username = ? AND user_type LIKE '%Staff%' AND status = 'active' LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ? $user['id'] : null;
        } catch (Exception $e) {
            error_log("Error finding staff ID by username: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find any active staff user
     */
    private function findAnyStaffUser()
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id FROM users 
             WHERE (user_type LIKE '%Staff%' OR user_type LIKE '%Data Entry%' OR user_type LIKE '%Viewer%')
             AND status = 'active' 
             ORDER BY id ASC 
             LIMIT 1"
            );
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            return $staff ? $staff['id'] : null;
        } catch (Exception $e) {
            error_log("Error finding any staff user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify user exists and is active
     */
    private function verifyUserExists($userId)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id FROM users WHERE id = ? AND status = 'active' LIMIT 1"
            );
            $stmt->execute([$userId]);
            return (bool) $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error verifying user exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user name from available sources
     */
    private function getUserName()
    {
        // Check session first
        $possibleNameKeys = ['fullname', 'name', 'username'];
        foreach ($possibleNameKeys as $key) {
            if (isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
                return $_SESSION[$key];
            }
        }

        // Check firstname + lastname combination
        if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
            return trim($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
        }

        // Try to get from database using resolved user ID
        $userId = $this->resolveUserId();
        try {
            $stmt = $this->conn->prepare(
                "SELECT firstname, lastname, username FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (!empty($user['firstname']) && !empty($user['lastname'])) {
                    return $user['firstname'] . ' ' . $user['lastname'];
                } elseif (!empty($user['username'])) {
                    return $user['username'];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting user name from DB: " . $e->getMessage());
        }

        return 'Unknown User';
    }

    /**
     * Get client IP address
     */
    private function getClientIp()
    {
        $ipAddress = '';

        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ipAddress = $_SERVER[$header];
                break;
            }
        }

        // Handle multiple IPs
        if (strpos($ipAddress, ',') !== false) {
            $ips = explode(',', $ipAddress);
            $ipAddress = trim($ips[0]);
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : 'UNKNOWN';
    }

    /**
     * Get logs for a specific user
     */
    public function getUserLogs($userId, $limit = 50)
    {
        try {
            $query = "SELECT * FROM activity_logs 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC 
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log system activity (when no user is logged in)
     */
    public function logSystemActivity($activityType, $description, $details = null)
    {
        // Use system user ID (0 or -1) for system activities
        $systemUserId = 0;

        try {
            $query = "INSERT INTO activity_logs 
                     (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                     VALUES (:user_id, :activity_type, :description, :activity_details, :ip_address, :user_agent, NOW())";

            $stmt = $this->conn->prepare($query);

            $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'System';

            $stmt->execute([
                ':user_id' => $systemUserId,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':activity_details' => $detailsJson,
                ':ip_address' => $ipAddress,
                ':user_agent' => substr($userAgent, 0, 500)
            ]);

            return true;
        } catch (Exception $e) {
            error_log("System activity log error: " . $e->getMessage());
            return false;
        }
    }
}
