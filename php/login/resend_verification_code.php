<?php
if (isset($_GET['session_context'])) {
    session_name("PHPSESSID_" . $_GET['session_context']);
}
session_start();
header("Content-Type: application/json");

// Database configuration
$host = 'localhost';
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if pending login exists
    if (!isset($_SESSION['pending_login']) || !isset($_SESSION['verification_user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No pending verification found.']);
        exit;
    }

    try {
        // Get user email
        $stmt = $pdo->prepare("SELECT email, firstname FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['verification_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate new code
            $new_code = sprintf("%06d", mt_rand(1, 999999));

            // Update session
            $_SESSION['verification_code'] = $new_code;
            $_SESSION['verification_expires'] = time() + 600; // 10 minutes

            // Send email using the same email configuration
            require_once 'email_config.php';
            $email_sent = sendVerificationCodeWithFallback($user['email'], $new_code, $user['firstname']);

            if ($email_sent) {
                echo json_encode(['success' => true, 'message' => 'New verification code sent!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send verification code. Check email logs.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
        }
    } catch (PDOException $e) {
        error_log("Resend verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error resending code.']);
    }
}
