<?php
// MSWDPALUAN_SYSTEM-MAIN/php/accounts/accounts.php

// Enable error reporting for debugging (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to browser
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Start output buffering to prevent stray output
ob_start();

// Set JSON headers FIRST
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Define base path
$base_dir = __DIR__;

// Include required files with proper error handling
try {
    // Check if files exist before including
    $database_file = $base_dir . '/database.php';
    $user_file = $base_dir . '/User.php';
    $email_sender_file = $base_dir . '/EmailSender.php';

    if (!file_exists($database_file)) {
        throw new Exception("Database file not found: " . $database_file);
    }

    if (!file_exists($user_file)) {
        throw new Exception("User file not found: " . $user_file);
    }

    if (!file_exists($email_sender_file)) {
        throw new Exception("EmailSender file not found: " . $email_sender_file);
    }

    require_once $database_file;
    require_once $user_file;
    require_once $email_sender_file;
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Failed to load required files: ' . $e->getMessage()
    ], 500);
}

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200)
{
    // Clear any previous output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Handle test request
if (isset($_GET['test'])) {
    sendJsonResponse([
        'status' => 'success',
        'message' => 'Accounts API is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'path' => __DIR__
    ]);
}

class AccountsAPIHandler
{
    private $database;
    private $db;
    private $user;
    private $emailSender;

    public function __construct()
    {
        try {
            $this->database = new Database();
            $this->db = $this->database->getConnection();
            $this->user = new User($this->db);
            $this->emailSender = new EmailSender();
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleRequest()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];

            // Get action from GET or POST
            $action = $_GET['action'] ?? '';

            switch ($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;

                case 'POST':
                    $this->handlePost($action);
                    break;

                case 'DELETE':
                    $this->handleDelete($action);
                    break;

                default:
                    sendJsonResponse([
                        'success' => false,
                        'error' => 'Method not allowed'
                    ], 405);
                    break;
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleGet($action)
    {
        if ($action === 'get_accounts') {
            $this->getAccounts();
        } else {
            sendJsonResponse([
                'success' => false,
                'error' => 'Invalid action for GET request'
            ], 400);
        }
    }

    private function handlePost($action)
    {
        // Get raw POST data
        $input = file_get_contents("php://input");

        if (empty($input)) {
            sendJsonResponse([
                'success' => false,
                'error' => 'No data received'
            ], 400);
        }

        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Invalid JSON data: ' . json_last_error_msg()
            ], 400);
        }

        // Check if this is a create action
        if ($action === 'create' || (isset($data['action']) && $data['action'] === 'create')) {
            $this->createAccount($data);
        } else {
            sendJsonResponse([
                'success' => false,
                'error' => 'Invalid action for POST request'
            ], 400);
        }
    }

    private function handleDelete($action)
    {
        $input = file_get_contents("php://input");

        if (empty($input)) {
            sendJsonResponse([
                'success' => false,
                'error' => 'No data received'
            ], 400);
        }

        $data = json_decode($input, true);

        if (empty($data['id'])) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Account ID is required'
            ], 400);
        }

        if ($action === 'delete' || (isset($data['action']) && $data['action'] === 'delete')) {
            $this->deleteAccount($data['id']);
        } else {
            sendJsonResponse([
                'success' => false,
                'error' => 'Invalid action for DELETE request'
            ], 400);
        }
    }

    private function getAccounts()
    {
        try {
            // Query to get all active users
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

            $stmt = $this->db->prepare($query);
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

            sendJsonResponse([
                'success' => true,
                'count' => count($accounts),
                'records' => $accounts
            ]);
        } catch (Exception $e) {
            error_log("Error in getAccounts: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createAccount($data)
    {
        // Validate required fields
        $required_fields = ['lastname', 'firstname', 'email', 'username', 'password', 'user_type'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Required fields are missing: ' . implode(', ', $missing_fields)
            ], 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Invalid email format'
            ], 400);
        }

        try {
            // Set user properties
            $this->user->lastname = $data['lastname'];
            $this->user->firstname = $data['firstname'];
            $this->user->middlename = $data['middlename'] ?? '';
            $this->user->birthdate = !empty($data['birthdate']) ? date('Y-m-d', strtotime($data['birthdate'])) : null;
            $this->user->gender = $data['gender'] ?? 'Male';
            $this->user->email = $data['email'];
            $this->user->contact_no = $data['contact_no'] ?? '';
            $this->user->address = $data['address'] ?? '';
            $this->user->username = $data['username'];
            $this->user->password = $data['password'];
            $this->user->user_type = $data['user_type'];
            $this->user->created_by = $data['created_by'] ?? null;

            // Check if username already exists
            if ($this->user->usernameExists()) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Username already exists'
                ], 400);
            }

            // Check if email already exists
            if ($this->user->emailExists()) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Email already exists'
                ], 400);
            }

            // Create the user
            if ($this->user->create()) {
                // Send email with credentials
                $emailSent = false;
                try {
                    $emailSent = $this->emailSender->sendAccountCredentials(
                        $data['email'],
                        $data['firstname'] . ' ' . $data['lastname'],
                        $data['username'],
                        $data['password'],
                        $data['user_type']
                    );
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $e->getMessage());
                }

                sendJsonResponse([
                    'success' => true,
                    'message' => 'Account created successfully',
                    'email_sent' => $emailSent,
                    'account_id' => $this->db->lastInsertId()
                ], 201);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Unable to create account. Database error occurred.'
                ], 500);
            }
        } catch (Exception $e) {
            error_log("Error in createAccount: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'error' => 'Error creating account: ' . $e->getMessage()
            ], 500);
        }
    }

    private function deleteAccount($accountId)
    {
        try {
            $this->user->id = $accountId;

            if ($this->user->delete()) {
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Account deleted successfully'
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Unable to delete account'
                ], 500);
            }
        } catch (Exception $e) {
            error_log("Error in deleteAccount: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'error' => 'Error deleting account: ' . $e->getMessage()
            ], 500);
        }
    }
}

// Instantiate and handle the request
try {
    $api = new AccountsAPIHandler();
    $api->handleRequest();
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Failed to initialize API handler: ' . $e->getMessage()
    ], 500);
}
