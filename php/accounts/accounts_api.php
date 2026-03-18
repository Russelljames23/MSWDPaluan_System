<?php
// php/accounts/accounts_api.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

ob_start();

// Set JSON headers
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$base_dir = dirname(__FILE__);

// Log request for debugging
error_log("=== API Request ===");
error_log("Method: " . $method);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

try {
    // Include database
    if (!file_exists($base_dir . '/database.php')) {
        throw new Exception('database.php not found');
    }

    require_once $base_dir . '/database.php';

    // Route requests
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}

function handleGetRequest()
{
    $action = $_GET['action'] ?? '';

    if ($action === 'get_accounts') {
        getAccounts();
    } else {
        testAPI();
    }
}

function handlePostRequest()
{
    try {
        $rawData = file_get_contents("php://input");
        error_log("Raw POST data length: " . strlen($rawData));

        if (empty($rawData)) {
            throw new Exception('No data received');
        }

        $data = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            throw new Exception('Invalid JSON data');
        }

        error_log("Decoded POST data: " . print_r($data, true));

        createAccount($data);
    } catch (Exception $e) {
        error_log("POST Error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

function handleDeleteRequest()
{
    try {
        // For DELETE, we can get ID from query string or body
        $accountId = null;

        // Try to get from query string first
        if (isset($_GET['id'])) {
            $accountId = (int)$_GET['id'];
        } else {
            // Try to get from body
            $rawData = file_get_contents("php://input");
            if (!empty($rawData)) {
                $data = json_decode($rawData, true);
                if (isset($data['id'])) {
                    $accountId = (int)$data['id'];
                }
            }
        }

        if (!$accountId) {
            throw new Exception('Account ID is required for deletion');
        }

        deleteAccount($accountId);
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

function testAPI()
{
    sendResponse([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function getAccounts()
{
    try {
        $database = new Database();
        $db = $database->getConnection();

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
                DATE_FORMAT(created_at, '%m/%d/%Y') as created_date,
                DATE_FORMAT(updated_at, '%m/%d/%Y') as updated_date
            FROM users 
            WHERE status = 'active'
            ORDER BY lastname, firstname
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $accounts = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $birthdate = '';
            if (!empty($row['birthdate']) && $row['birthdate'] != '0000-00-00') {
                try {
                    $birthdate = date('m/d/Y', strtotime($row['birthdate']));
                } catch (Exception $e) {
                    $birthdate = $row['birthdate'];
                }
            }

            $fullname = trim($row['lastname'] . ', ' . $row['firstname']);
            if (!empty($row['middlename'])) {
                $fullname .= ' ' . substr($row['middlename'], 0, 1) . '.';
            }

            $accounts[] = [
                'id' => (int)$row['id'],
                'lastname' => $row['lastname'] ?? '',
                'firstname' => $row['firstname'] ?? '',
                'middlename' => $row['middlename'] ?? '',
                'fullname' => $fullname,
                'birthdate' => $birthdate,
                'gender' => $row['gender'] ?? '',
                'email' => $row['email'] ?? '',
                'contact_no' => $row['contact_no'] ?? '',
                'address' => $row['address'] ?? '',
                'username' => $row['username'] ?? '',
                'user_type' => $row['user_type'] ?? 'Staff',
                'status' => $row['status'] ?? 'active',
                'created_date' => $row['created_date'] ?? '',
                'updated_date' => $row['updated_date'] ?? ''
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
            'message' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
}

function createAccount($data)
{
    try {
        // Validate required fields
        $required = ['lastname', 'firstname', 'email', 'username', 'password', 'user_type'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception("Missing required fields: " . implode(', ', $missing));
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        if (strlen($data['password']) < 6) {
            throw new Exception("Password must be at least 6 characters");
        }

        $database = new Database();
        $db = $database->getConnection();

        // Include User class
        $base_dir = dirname(__FILE__);
        if (!file_exists($base_dir . '/User.php')) {
            throw new Exception('User.php not found');
        }
        require_once $base_dir . '/User.php';

        $user = new User($db);

        // Check if username exists
        $user->username = $data['username'];
        if ($user->usernameExists()) {
            throw new Exception("Username already exists");
        }

        // Check if email exists
        $user->email = $data['email'];
        if ($user->emailExists()) {
            throw new Exception("Email already exists");
        }

        // Set user properties
        $user->lastname = $data['lastname'] ?? '';
        $user->firstname = $data['firstname'] ?? '';
        $user->middlename = $data['middlename'] ?? '';
        $user->birthdate = !empty($data['birthdate']) ? $data['birthdate'] : null;
        $user->gender = $data['gender'] ?? '';
        $user->email = $data['email'];
        $user->contact_no = $data['contact_no'] ?? '';
        $user->address = $data['address'] ?? '';
        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->user_type = $data['user_type'];
        $user->created_by = isset($data['created_by']) && !empty($data['created_by']) ? (int)$data['created_by'] : null;

        // Create the user
        $result = $user->create();

        if ($result === true) {
            $userId = $user->id ?? $db->lastInsertId();

            $response = [
                'success' => true,
                'message' => 'Account created successfully',
                'user_id' => $userId
            ];

            // Try to send email
            try {
                require_once $base_dir . '/EmailSender.php';
                $emailSender = new EmailSender();

                $emailSent = $emailSender->sendAccountCredentials(
                    $data['email'],
                    $data['firstname'] . ' ' . $data['lastname'],
                    $data['username'],
                    $data['password'],
                    $data['user_type']
                );

                $response['email_sent'] = $emailSent;

                if ($emailSent) {
                    $response['message'] .= ' and credentials sent to email';
                }
            } catch (Exception $e) {
                error_log("Email sending failed: " . $e->getMessage());
                $response['email_sent'] = false;
                $response['email_error'] = $e->getMessage();
            }

            sendResponse($response);
        } else {
            // Check if user was actually created despite returning false
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$data['username'], $data['email']]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                error_log("User was created but create() returned false. User ID: " . $existing['id']);
                sendResponse([
                    'success' => true,
                    'message' => 'Account created successfully (with warning)',
                    'user_id' => $existing['id'],
                    'warning' => 'User created but method returned false'
                ]);
            } else {
                throw new Exception("Failed to create account in database");
            }
        }
    } catch (Exception $e) {
        error_log("Error in createAccount: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

function deleteAccount($accountId)
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $base_dir = dirname(__FILE__);
        require_once $base_dir . '/User.php';

        $user = new User($db);
        $user->id = $accountId;

        // Check if account exists before deleting
        $checkStmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $checkStmt->execute([$accountId]);
        $account = $checkStmt->fetch();

        if (!$account) {
            throw new Exception("Account not found");
        }

        if ($user->delete()) {
            sendResponse([
                'success' => true,
                'message' => 'Account deleted successfully',
                'deleted_id' => $accountId,
                'deleted_username' => $account['username']
            ]);
        } else {
            throw new Exception("Failed to delete account");
        }
    } catch (Exception $e) {
        sendResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
