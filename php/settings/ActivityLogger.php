<?php
// ActivityLogger.php
class ActivityLogger
{
    private $conn;

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
            // Get user from session
            session_start();

            $userId = $_SESSION['user_id'] ?? null;
            $userName = $this->getUserNameFromSession();

            // If no user ID, try to get from current session
            if (!$userId && isset($_SESSION['id'])) {
                $userId = $_SESSION['id'];
            }

            // Still no user? Could be a system activity
            if (!$userId) {
                error_log("No user ID found for activity: {$activityType}");
                return false;
            }

            // Get user IP address
            $ipAddress = $this->getClientIp();

            // Get user agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            // Prepare the query
            $query = "INSERT INTO activity_logs 
                     (user_id, activity_type, description, activity_details, ip_address, user_agent, created_at) 
                     VALUES (:user_id, :activity_type, :description, :activity_details, :ip_address, :user_agent, NOW())";

            $stmt = $this->conn->prepare($query);

            // Prepare details as JSON
            $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;

            $stmt->execute([
                ':user_id' => $userId,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':activity_details' => $detailsJson,
                ':ip_address' => $ipAddress,
                ':user_agent' => substr($userAgent, 0, 500) // Limit length
            ]);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user name from session
     */
    private function getUserNameFromSession()
    {
        if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
            return $_SESSION['fullname'];
        }

        if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
            return $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
        }

        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
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
     * Get user info from database
     */
    public function getUserInfo($userId)
    {
        try {
            $query = "SELECT id, firstname, lastname, username, user_type FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $userId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}
