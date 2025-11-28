<?php
// email_config.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Correct the require paths
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

function configureEmail()
{
    return [
        'method' => 'smtp',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'russelljamestadalan23@gmail.com',
        'smtp_password' => 'vlnk nthk uqiy epss',
        'smtp_secure' => 'tls'
    ];
}

function sendVerificationCodeWithFallback($email, $code, $name)
{
    $config = configureEmail();

    // Try to send actual email first
    $emailSent = sendEmailViaSMTP($email, $code, $name, $config);

    // If email fails, save to file as backup
    if (!$emailSent) {
        error_log("Email sending failed, saving to file instead");
        return saveEmailToFile($email, $code, $name, __DIR__ . '/email_logs/');
    }

    return $emailSent;
}

function sendEmailViaSMTP($email, $code, $name, $config)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port       = $config['smtp_port'];

        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';

        // Recipients
        $mail->setFrom($config['smtp_username'], 'MSWD System');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Login Verification Code - Bayan ng Paluan MSWD System';
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; color: #1a0933; margin-bottom: 20px; }
                .code { font-size: 32px; font-weight: bold; color: #667eea; text-align: center; margin: 20px 0; padding: 15px; background: #f7f7f7; border-radius: 5px; letter-spacing: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Login Verification Code</h2>
                </div>
                <p>Hello <strong>$name</strong>,</p>
                <p>You have requested to login to the Bayan ng Paluan MSWD System. Here is your verification code:</p>
                <div class='code'>$code</div>
                <p>This code will expire in <strong>10 minutes</strong>.</p>
                <p>If you did not request this code, please ignore this email.</p>
                <div class='footer'>
                    <p>Best regards,<br><strong>Bayan ng Paluan MSWD Team</strong></p>
                    <p><small>This is an automated message. Please do not reply to this email.</small></p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version for non-HTML email clients
        $mail->AltBody = "Login Verification Code\nHello $name,\nYour verification code is: $code\nThis code will expire in 10 minutes.\n\nBest regards,\nBayan ng Paluan MSWD Team";

        $mail->send();
        error_log("Email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function saveEmailToFile($email, $code, $name, $file_path)
{
    // Create directory if it doesn't exist
    if (!is_dir($file_path)) {
        mkdir($file_path, 0755, true);
    }

    $filename = $file_path . 'verification_' . date('Y-m-d_H-i-s') . '.html';
    $content = "
    ==================================
    VERIFICATION EMAIL (TEST MODE)
    ==================================
    To: $email
    Subject: Login Verification Code - Bayan ng Paluan MSWD System
    Time: " . date('Y-m-d H:i:s') . "
    ----------------------------------
    
    Hello $name,
    
    Your verification code is: $code
    
    This code will expire in 10 minutes.
    
    ==================================
    ";

    $result = file_put_contents($filename, $content);

    if ($result !== false) {
        error_log("Email saved to file: " . $filename);
        error_log("VERIFICATION CODE for $email: $code");
        return true;
    }

    return false;
}
