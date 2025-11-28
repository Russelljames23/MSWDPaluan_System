<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first to prevent any output before headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fix the include paths based on your file structure
$base_dir = dirname(__FILE__);
include_once $base_dir . '/database.php';
include_once $base_dir . '/User.php';
include_once $base_dir . '/EmailSender.php';
  
class AccountsHandler
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
            error_log("Initialization failed: " . $e->getMessage());
            $this->sendErrorResponse("Initialization failed: " . $e->getMessage());
        }
    }

    public function handleRequest()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut();
                    break;
                case 'DELETE':
                    $this->handleDelete();
                    break;
                default:
                    $this->sendErrorResponse("Method not allowed.", 405);
                    break;
            }
        } catch (Exception $e) {
            error_log("AccountsHandler Error: " . $e->getMessage());
            $this->sendErrorResponse("Server error: " . $e->getMessage());
        }
    }

    private function handleGet()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'get_accounts') {
            try {
                $stmt = $this->user->read();

                if ($stmt === false) {
                    $this->sendErrorResponse("Error reading accounts from database");
                    return;
                }

                $num = $stmt->rowCount();
                $accounts_arr = array();
                $accounts_arr["records"] = array();

                if ($num > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $account_item = array(
                            "id" => $row['id'],
                            "lastname" => $row['lastname'],
                            "firstname" => $row['firstname'],
                            "middlename" => $row['middlename'],
                            "fullname" => $row['lastname'] . ', ' . $row['firstname'] . ($row['middlename'] ? ' ' . $row['middlename'] : ''),
                            "birthdate" => $row['birthdate'] ? date('m/d/Y', strtotime($row['birthdate'])) : '',
                            "gender" => $row['gender'],
                            "email" => $row['email'],
                            "contact_no" => $row['contact_no'],
                            "address" => $row['address'],
                            "username" => $row['username'],
                            "user_type" => $row['user_type'],
                            "status" => $row['status'],
                            "created_by" => ($row['creator_fname'] && $row['creator_lname']) ? $row['creator_fname'] . ' ' . $row['creator_lname'] : 'System',
                            "created_at" => $row['created_at']
                        );
                        array_push($accounts_arr["records"], $account_item);
                    }
                }

                $this->sendSuccessResponse($accounts_arr);
            } catch (Exception $e) {
                error_log("Error in handleGet: " . $e->getMessage());
                $this->sendErrorResponse("Error fetching accounts: " . $e->getMessage());
            }
        } else {
            $this->sendErrorResponse("Invalid action parameter");
        }
    }

    private function handlePost()
    {
        // Get raw POST data
        $input = file_get_contents("php://input");

        if (empty($input)) {
            $this->sendErrorResponse("No data received");
            return;
        }

        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse("Invalid JSON data: " . json_last_error_msg());
            return;
        }

        // Validate required fields
        $required_fields = ['lastname', 'firstname', 'email', 'username', 'password', 'user_type'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $this->sendErrorResponse("Unable to create account. Required fields are missing: " . implode(', ', $missing_fields));
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendErrorResponse("Invalid email format.");
            return;
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
            $this->user->created_by = !empty($data['created_by']) ? $data['created_by'] : null;

            // Check if username already exists
            if ($this->user->usernameExists()) {
                $this->sendErrorResponse("Username already exists.");
                return;
            }

            // Check if email already exists
            if ($this->user->emailExists()) {
                $this->sendErrorResponse("Email already exists.");
                return;
            }

            // Create the user
            $createResult = $this->user->create();

            if ($createResult) {
                // Send email with credentials
                $emailSent = $this->sendCredentialsEmail(
                    $data['email'],
                    $data['firstname'] . ' ' . $data['lastname'],
                    $data['username'],
                    $data['password'],
                    $data['user_type']
                );

                $response = array(
                    "message" => "Account was created successfully.",
                    "email_sent" => $emailSent,
                    "email_message" => $emailSent ? "Credentials sent to " . $data['email'] : "Failed to send email to " . $data['email'],
                    "account_id" => $this->db->lastInsertId()
                );

                $this->sendSuccessResponse($response, 201);
            } else {
                $this->sendErrorResponse("Unable to create account. Database error occurred.");
            }
        } catch (Exception $e) {
            error_log("Error in handlePost: " . $e->getMessage());
            $this->sendErrorResponse("Error creating account: " . $e->getMessage());
        }
    }

    private function sendCredentialsEmail($toEmail, $toName, $username, $password, $userType)
    {
        try {
            // Test SMTP connection first
            if (!$this->emailSender->testConnection()) {
                error_log("SMTP connection test failed for: " . $toEmail);
                return false;
            }

            // Send the email
            $result = $this->emailSender->sendAccountCredentials($toEmail, $toName, $username, $password, $userType);
            
            if ($result) {
                error_log("Credentials email sent successfully to: " . $toEmail);
            } else {
                error_log("Failed to send credentials email to: " . $toEmail);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Exception in sendCredentialsEmail: " . $e->getMessage());
            return false;
        }
    }

    private function handlePut()
    {
        $input = file_get_contents("php://input");

        if (empty($input)) {
            $this->sendErrorResponse("No data received");
            return;
        }

        $data = json_decode($input, true);

        if (empty($data['id'])) {
            $this->sendErrorResponse("Unable to update account. ID is required.");
            return;
        }

        try {
            $this->user->id = $data['id'];
            $this->user->lastname = $data['lastname'] ?? '';
            $this->user->firstname = $data['firstname'] ?? '';
            $this->user->middlename = $data['middlename'] ?? '';
            $this->user->birthdate = !empty($data['birthdate']) ? date('Y-m-d', strtotime($data['birthdate'])) : null;
            $this->user->gender = $data['gender'] ?? 'Male';
            $this->user->email = $data['email'] ?? '';
            $this->user->contact_no = $data['contact_no'] ?? '';
            $this->user->address = $data['address'] ?? '';
            $this->user->username = $data['username'] ?? '';
            $this->user->user_type = $data['user_type'] ?? '';
            $this->user->status = $data['status'] ?? 'active';

            // Check for duplicate username/email excluding current user
            if ($this->user->usernameExists($data['id'])) {
                $this->sendErrorResponse("Username already exists.");
                return;
            }

            if ($this->user->emailExists($data['id'])) {
                $this->sendErrorResponse("Email already exists.");
                return;
            }

            if ($this->user->update()) {
                $this->sendSuccessResponse(array("message" => "Account was updated."));
            } else {
                $this->sendErrorResponse("Unable to update account.");
            }
        } catch (Exception $e) {
            error_log("Error in handlePut: " . $e->getMessage());
            $this->sendErrorResponse("Error updating account: " . $e->getMessage());
        }
    }

    private function handleDelete()
    {
        $input = file_get_contents("php://input");

        if (empty($input)) {
            $this->sendErrorResponse("No data received");
            return;
        }

        $data = json_decode($input, true);

        if (empty($data['id'])) {
            $this->sendErrorResponse("Unable to delete account. ID is required.");
            return;
        }

        try {
            $this->user->id = $data['id'];

            if ($this->user->delete()) {
                $this->sendSuccessResponse(array("message" => "Account was deleted."));
            } else {
                $this->sendErrorResponse("Unable to delete account.");
            }
        } catch (Exception $e) {
            error_log("Error in handleDelete: " . $e->getMessage());
            $this->sendErrorResponse("Error deleting account: " . $e->getMessage());
        }
    }

    private function sendSuccessResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function sendErrorResponse($message, $statusCode = 400)
    {
        http_response_code($statusCode);
        echo json_encode(array("message" => $message));
        exit;
    }
}

// Handle the request
try {
    $handler = new AccountsHandler();
    $handler->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
    exit;
}