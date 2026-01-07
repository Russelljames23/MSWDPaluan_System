<?php
// login_backend.php - session-isolated
// Parse raw input to extract session_context (works for both JSON POST and GET)
$rawInput = file_get_contents('php://input');
$parsedInput = json_decode($rawInput, true) ?: [];
$session_context = $_GET['session_context'] ?? $parsedInput['session_context'] ?? null;

if ($session_context) {
    session_name('SESS_' . $session_context);
}
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$host = 'localhost';
$dbname = 'u401132124_mswd_seniors';
$username = 'u401132124_mswdopaluan';
$password = 'Mswdo_PaluanSystem23';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check if MySQL server is running.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $parsedInput; // already parsed above

    if (!isset($input['username']) || !isset($input['password']) || !isset($input['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $username = trim($input['username']);
    $password = trim($input['password']);
    $user_type = trim($input['user_type']);
    // Keep the session_context we parsed earlier

    try {
        $stmt = $pdo->prepare(
            "SELECT id, lastname, firstname, middlename, username, password, user_type, status, is_verified, email
            FROM users
            WHERE username = ? AND status = 'active'"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_verified']) {
                echo json_encode(['success' => false, 'message' => 'Please verify your email before logging in.']);
                exit;
            }

            $db_user_type = $user['user_type'];
            $isValidType = false;
            if ($user_type === 'Admin') {
                $isValidType = (strpos($db_user_type, 'Admin') === 0 || $db_user_type === 'Super Admin');
            } else if ($user_type === 'Staff') {
                $isValidType = ($db_user_type === 'Staff' || $db_user_type === 'Staff Manager' || $db_user_type === 'Data Entry' || $db_user_type === 'Viewer');
            }

            if (!$isValidType) {
                echo json_encode(['success' => false, 'message' => 'Invalid user type for this login']);
                exit;
            }

            if (empty($user['email'])) {
                error_log("User has no email address: " . $username);
                echo json_encode(['success' => false, 'message' => 'No email address found for your account. Please contact administrator.']);
                exit;
            }

            $verification_code = generateVerificationCode();
            $email_sent = sendVerificationCode($user['email'], $verification_code, $user['firstname']);

            if ($email_sent) {
                $_SESSION['verification_user_id'] = $user['id'];
                $_SESSION['verification_code'] = $verification_code;
                $_SESSION['verification_expires'] = time() + 600; // 10 minutes
                $_SESSION['pending_login'] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['firstname'] . ' ' . $user['lastname'],
                    'user_type' => $user['user_type'],
                    'login_type' => $user_type,
                    'session_context' => $session_context
                ];

                error_log("Verification code sent to: " . $user['email']);

                // Log login attempt
                require_once '../settings/ActivityLogger.php';
                $logger = new ActivityLogger($pdo, $user['id'], $user['username']);
                $logger->log('LOGIN', 'Login verification code sent', [
                    'email' => $user['email'],
                    'user_type' => $user['user_type'],
                    'verification_sent' => true
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Verification code sent to your email!',
                    'requires_verification' => true
                ]);
            } else {
                error_log("Failed to send email to: " . $user['email']);

                // Log failed email attempt
                require_once '../settings/ActivityLogger.php';
                $logger = new ActivityLogger($pdo, $user['id'], $user['username']);
                $logger->log('ERROR', 'Failed to send verification email', [
                    'email' => $user['email'],
                    'error_type' => 'email_delivery'
                ]);

                echo json_encode(['success' => false, 'message' => 'Failed to send verification code. Please try again or contact administrator.']);
            }
        } else {
            // Log failed login attempt
            require_once '../settings/ActivityLogger.php';
            $logger = new ActivityLogger($pdo, null, $username);
            $logger->log('LOGIN', 'Failed login attempt', [
                'username' => $username,
                'attempt_time' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);

            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function generateVerificationCode()
{
    return sprintf("%06d", mt_rand(1, 999999));
}

function sendVerificationCode($email, $code, $name)
{
    require_once 'email_config.php';
    return sendVerificationCodeWithFallback($email, $code, $name);
}
