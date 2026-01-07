<?php
// combined_logs_backend.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database connection - adjust path as needed
$dbPath = __DIR__ . '/../../MSWDPALUAN_SYSTEM-MAIN/php/db.php'; // Adjusted path based on your structure
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../../../MSWDPALUAN_SYSTEM-MAIN/php/db.php';
}

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database configuration not found",
        "debug" => "Looking for: " . $dbPath
    ]);
    exit;
}

require_once $dbPath;

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed",
        "debug" => "Connection object is null"
    ]);
    exit;
}

// Function to calculate duration
function calculateDuration($start_time, $end_time) {
    if (!$end_time || $end_time === '0000-00-00 00:00:00' || strtotime($end_time) === false) {
        return "Ongoing";
    }

    try {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $interval = $start->diff($end);

        if ($interval->days > 0) {
            return $interval->format('%ad %hh %im');
        } elseif ($interval->h > 0) {
            return $interval->format('%hh %im');
        } elseif ($interval->i > 0) {
            return $interval->format('%im');
        } else {
            return $interval->format('%ss');
        }
    } catch (Exception $e) {
        return "N/A";
    }
}

// Function to format time ago
function timeAgo($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00' || strtotime($datetime) === false) {
        return "N/A";
    }

    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 604800) return floor($diff / 86400) . "d ago";
    if ($diff < 2592000) return floor($diff / 604800) . "w ago";
    if ($diff < 31536000) return floor($diff / 2592000) . "mo ago";
    return floor($diff / 31536000) . "y ago";
}

// Function to get activity icon
function getActivityIcon($activity_type) {
    $icons = [
        'login' => '🔑',
        'logout' => '🚪',
        'create' => '➕',
        'update' => '✏️',
        'delete' => '🗑️',
        'view' => '👁️',
        'export' => '📤',
        'import' => '📥',
        'register' => '📝',
        'archive' => '📦',
        'restore' => '🔄',
        'report' => '📊',
        'settings' => '⚙️',
        'profile' => '👤',
        'password' => '🔒'
    ];

    $key = strtolower($activity_type);
    foreach ($icons as $pattern => $icon) {
        if (strpos($key, $pattern) !== false) {
            return $icon;
        }
    }
    return '📋';
}

try {
    // Get parameters with default values
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $logType = isset($_GET['log_type']) ? $_GET['log_type'] : 'both';
    $dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
    $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Check if this is a detail request
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';

    if ($action === 'detail') {
        $logId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $logType = isset($_GET['type']) ? $_GET['type'] : 'session';

        if ($logType === 'session') {
            $query = "SELECT us.*, u.firstname, u.lastname, u.user_type 
                     FROM user_sessions us 
                     JOIN users u ON us.user_id = u.id 
                     WHERE us.id = :id";
        } else {
            $query = "SELECT al.*, u.firstname, u.lastname, u.user_type 
                     FROM activity_logs al 
                     JOIN users u ON al.user_id = u.id 
                     WHERE al.id = :id";
        }

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $logId, PDO::PARAM_INT);
        $stmt->execute();

        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($log) {
            echo json_encode([
                'success' => true,
                'data' => $log
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Log not found'
            ]);
        }
        exit;
    }

    // Validate sort field
    $allowedSortFields = ['timestamp', 'user_name', 'user_type', 'activity_type', 'log_type', 'duration_seconds'];
    if (!in_array($sort, $allowedSortFields)) $sort = 'timestamp';
    $order = $order === 'ASC' ? 'ASC' : 'DESC';

    // Build base queries with parameter placeholders
    $sessionQuery = "
        SELECT 
            'session' as log_type,
            us.id as log_id,
            us.user_id,
            CONCAT(u.lastname, ', ', u.firstname) as user_name,
            u.user_type,
            us.login_time as timestamp,
            'Session' as activity_type,
            CONCAT('Logged in from ', COALESCE(us.ip_address, 'Unknown')) as description,
            us.ip_address,
            us.logout_time,
            TIMESTAMPDIFF(SECOND, us.login_time, us.logout_time) as duration_seconds,
            us.login_type,
            NULL as details
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        WHERE 1=1
    ";

    $activityQuery = "
        SELECT 
            'activity' as log_type,
            al.id as log_id,
            al.user_id,
            CONCAT(u.lastname, ', ', u.firstname) as user_name,
            u.user_type,
            al.created_at as timestamp,
            al.activity_type,
            al.description,
            al.ip_address,
            NULL as logout_time,
            NULL as duration_seconds,
            NULL as login_type,
            al.user_agent as details
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        WHERE 1=1
    ";

    // Build conditions and parameters
    $sessionConditions = [];
    $activityConditions = [];
    $sessionParams = [];
    $activityParams = [];

    // Apply search filter
    if (!empty($search)) {
        $searchTerm = "%{$search}%";
        $sessionConditions[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.user_type LIKE ? OR us.ip_address LIKE ?)";
        $activityConditions[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.user_type LIKE ? OR al.ip_address LIKE ? OR al.activity_type LIKE ? OR al.description LIKE ?)";
        
        $sessionParams = array_merge($sessionParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $activityParams = array_merge($activityParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    // Apply user type filter
    if ($filter === 'admin') {
        $sessionConditions[] = "u.user_type = 'Admin'";
        $activityConditions[] = "u.user_type = 'Admin'";
    } elseif ($filter === 'staff') {
        $sessionConditions[] = "u.user_type = 'Staff'";
        $activityConditions[] = "u.user_type = 'Staff'";
    }

    // Apply date range filter
    if ($dateRange !== 'all') {
        switch ($dateRange) {
            case 'today':
                $sessionConditions[] = "DATE(us.login_time) = CURDATE()";
                $activityConditions[] = "DATE(al.created_at) = CURDATE()";
                break;
            case 'week':
                $sessionConditions[] = "us.login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $activityConditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sessionConditions[] = "us.login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $activityConditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $sessionConditions[] = "us.login_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                $activityConditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }
    }

    // Build WHERE clauses
    $sessionWhere = '';
    if (!empty($sessionConditions)) {
        $sessionWhere = ' AND ' . implode(' AND ', $sessionConditions);
    }
    
    $activityWhere = '';
    if (!empty($activityConditions)) {
        $activityWhere = ' AND ' . implode(' AND ', $activityConditions);
    }

    // Build queries array
    $queries = [];
    $params = [];
    $paramTypes = [];

    if ($logType === 'both' || $logType === 'session') {
        $queries[] = $sessionQuery . $sessionWhere;
        $params = array_merge($params, $sessionParams);
    }

    if ($logType === 'both' || $logType === 'activity') {
        $queries[] = $activityQuery . $activityWhere;
        $params = array_merge($params, $activityParams);
    }

    if (empty($queries)) {
        $queries[] = $sessionQuery;
    }

    // Build combined query
    $combinedQuery = "(" . implode(") UNION ALL (", $queries) . ")";

    // Count total items
    $countQuery = "SELECT COUNT(*) as total FROM ({$combinedQuery}) as combined";
    
    $stmtCount = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmtCount->execute($params);
    } else {
        $stmtCount->execute();
    }
    
    $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalItems = $totalResult['total'] ?? 0;

    // Main query with pagination - FIX: Use direct integers for LIMIT/OFFSET
    $mainQuery = "{$combinedQuery} ORDER BY {$sort} {$order} LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($mainQuery);
    
    // Bind parameters for search/filter
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
        $paramIndex++;
    }
    
    // IMPORTANT: Bind LIMIT and OFFSET as integers
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();

    $logs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format timestamp
        $timestamp = !empty($row['timestamp']) && $row['timestamp'] !== '0000-00-00 00:00:00'
            ? date('M j, Y g:i A', strtotime($row['timestamp']))
            : 'N/A';

        $timeAgo = timeAgo($row['timestamp']);

        // Format duration for sessions
        $duration = $row['log_type'] === 'session'
            ? calculateDuration($row['timestamp'], $row['logout_time'])
            : 'N/A';

        // Get icon
        $icon = getActivityIcon($row['activity_type']);

        // Determine status
        $status = $row['log_type'] === 'session'
            ? (!empty($row['logout_time']) && $row['logout_time'] !== '0000-00-00 00:00:00' ? 'Completed' : 'Active')
            : 'Completed';

        $logs[] = [
            'log_type' => $row['log_type'],
            'log_id' => $row['log_id'],
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'] ?? 'Unknown',
            'user_type' => $row['user_type'] ?? 'N/A',
            'timestamp' => $timestamp,
            'time_ago' => $timeAgo,
            'activity_type' => $row['activity_type'] ?? 'N/A',
            'description' => $row['description'] ?? '',
            'icon' => $icon,
            'ip_address' => $row['ip_address'] ?? 'N/A',
            'duration' => $duration,
            'status' => $status,
            'login_type' => $row['login_type'] ?? 'N/A',
            'details' => $row['details'] ?? '',
            'raw_timestamp' => $row['timestamp']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $logs,
        'total' => $totalItems,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalItems / $limit),
        'filters' => [
            'search' => $search,
            'filter' => $filter,
            'log_type' => $logType,
            'date_range' => $dateRange
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'debug' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    error_log("Database error in combined_logs_backend.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'debug' => $e->getMessage()
    ]);
    error_log("General error in combined_logs_backend.php: " . $e->getMessage());
}