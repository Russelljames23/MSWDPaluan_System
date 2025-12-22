<?php
// php/accounts/accounts_api.php - NO SPACES BEFORE THIS LINE!

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Set JSON headers
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

// Simple router based on action
$action = $_GET['action'] ?? '';

if ($action === 'get_accounts') {
    getAccounts();
} elseif ($action === 'test') {
    testAPI();
} else {
    sendResponse(['error' => 'Invalid action'], 400);
}

function testAPI()
{
    sendResponse([
        'status' => 'success',
        'message' => 'API is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'test'
    ]);
}

function getAccounts()
{
    try {
        // Include database connection
        $base_dir = dirname(__FILE__);

        // Check if files exist
        if (!file_exists($base_dir . '/database.php')) {
            throw new Exception('database.php not found');
        }

        include_once $base_dir . '/database.php';

        // Create database connection
        $database = new Database();
        $db = $database->getConnection();

        // Query to get all users
        $query = "
            SELECT 
                id,
                lastname,
                firstname,
                middlename,
                birthdate,
                gender,
                email,
                contact_no,
                address,
                username,
                user_type,
                status,
                created_at,
                updated_at
            FROM users 
            WHERE status = 'active'
            ORDER BY lastname, firstname
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $accounts = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $accounts[] = [
                'id' => (int)$row['id'],
                'lastname' => $row['lastname'] ?? '',
                'firstname' => $row['firstname'] ?? '',
                'middlename' => $row['middlename'] ?? '',
                'fullname' => trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . ($row['middlename'] ?? '')),
                'birthdate' => !empty($row['birthdate']) ? date('m/d/Y', strtotime($row['birthdate'])) : '',
                'gender' => $row['gender'] ?? '',
                'email' => $row['email'] ?? '',
                'contact_no' => $row['contact_no'] ?? '',
                'address' => $row['address'] ?? '',
                'username' => $row['username'] ?? '',
                'user_type' => $row['user_type'] ?? '',
                'status' => $row['status'] ?? '',
                'created_at' => $row['created_at'] ?? '',
                'updated_at' => $row['updated_at'] ?? ''
            ];
        }

        sendResponse([
            'success' => true,
            'count' => count($accounts),
            'records' => $accounts
        ]);
    } catch (Exception $e) {
        error_log("Error in getAccounts: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
}

function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// End of file - NO NEWLINE AFTER THIS
