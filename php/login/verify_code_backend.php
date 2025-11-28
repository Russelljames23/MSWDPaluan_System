<?php
// verify_code_backend.php - session-isolated
$rawInput = file_get_contents('php://input');
$parsedInput = json_decode($rawInput, true) ?: [];
$session_context = $_GET['session_context'] ?? $parsedInput['session_context'] ?? null;


if ($session_context) {
    session_name('SESS_' . $session_context);
}
session_start();
header('Content-Type: application/json');


$host = 'localhost';
$dbname = 'mswd_seniors';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $parsedInput;
    $entered_code = trim($input['code'] ?? '');
    $session_context_from_input = isset($input['session_context']) ? trim($input['session_context']) : null;


    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_expires']) || !isset($_SESSION['pending_login'])) {
        echo json_encode(['success' => false, 'message' => 'Verification session expired. Please login again.']);
        exit;
    }


    if (
        $session_context_from_input && isset($_SESSION['pending_login']['session_context']) &&
        $_SESSION['pending_login']['session_context'] !== $session_context_from_input
    ) {
        echo json_encode(['success' => false, 'message' => 'Session context mismatch. Please login again.']);
        exit;
    }
    if (time() > $_SESSION['verification_expires']) {
        unset($_SESSION['verification_code']);
        unset($_SESSION['verification_expires']);
        unset($_SESSION['pending_login']);


        echo json_encode(['success' => false, 'message' => 'Verification code expired. Please login again.']);
        exit;
    }
    if ($entered_code === $_SESSION['verification_code']) {
        $user_data = $_SESSION['pending_login'];


        session_regenerate_id(true);


        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['fullname'] = $user_data['fullname'];
        $_SESSION['user_type'] = $user_data['user_type'];
        $_SESSION['role_id'] = $user_data['role_id'] ?? null;
        $_SESSION['role_name'] = $user_data['role_name'] ?? $user_data['user_type'];
        $_SESSION['permissions'] = $user_data['permissions'] ?? [];
        $_SESSION['logged_in'] = true;
        $_SESSION['is_verified'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        if ($user_data['login_type'] === 'Admin') {
            $_SESSION['session_context'] = 'admin';
        } else {
            $_SESSION['session_context'] = 'staff';
        }


        $_SESSION['browser_session_context'] = $session_context_from_input;


        logLoginActivity($pdo, $user_data['user_id'], $user_data['login_type']);


        unset($_SESSION['verification_code']);
        unset($_SESSION['verification_expires']);
        unset($_SESSION['pending_login']);
        echo json_encode([
            'success' => true,
            'message' => 'Verification successful!',
            'redirect_url' => getRedirectUrl($user_data['login_type'], $session_context_from_input)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
    }
}
function logLoginActivity($pdo, $user_id, $login_type)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, login_time, ip_address, user_agent, session_id, login_type) VALUES (?, NOW(), ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            session_id(),
            $login_type
        ]);
    } catch (Exception $e) {
        error_log("Failed to log login activity: " . $e->getMessage());
    }
}


function getRedirectUrl($login_type, $session_context)
{
    if ($login_type === 'Admin') {
        return "/MSWDPALUAN_SYSTEM-MAIN/html/index.php?session_context=" . urlencode($session_context);
    } else {
        return "/MSWDPALUAN_SYSTEM-MAIN/html/staff/staffindex.php?session_context=" . urlencode($session_context);
    }
}
