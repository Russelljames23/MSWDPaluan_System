<?php
// EmailSender.php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailSender
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer()
    {
        try {
            // Enable verbose debugging for troubleshooting
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF; // Change to DEBUG_SERVER for troubleshooting

            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'russelljamestadalan23@gmail.com';
            $this->mailer->Password = 'vlnk nthk uqiy epss';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';

            // Sender
            $this->mailer->setFrom('russelljamestadalan23@gmail.com', 'MSWD PALUAN System');
            $this->mailer->addReplyTo('russelljamestadalan23@gmail.com', 'MSWD PALUAN System');
        } catch (Exception $e) {
            error_log("PHPMailer Configuration Error: " . $e->getMessage());
            throw new Exception("Email configuration failed: " . $e->getMessage());
        }
    }

    public function sendAccountCredentials($toEmail, $toName, $username, $password, $userType)
    {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearAllRecipients();
            $this->mailer->clearCustomHeaders();
            $this->mailer->clearReplyTos();

            // Add recipient
            $this->mailer->addAddress($toEmail, $toName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your MSWD PALUAN Account Credentials';

            $htmlContent = $this->getEmailTemplate($toName, $username, $password, $userType);
            $plainContent = $this->getPlainTextTemplate($toName, $username, $password, $userType);

            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $plainContent;

            // Send email
            $sent = $this->mailer->send();

            if ($sent) {
                error_log("Email successfully sent to: " . $toEmail);
                return true;
            } else {
                error_log("Email sending failed to: " . $toEmail . " - Error: " . $this->mailer->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log("Exception sending email to {$toEmail}: " . $e->getMessage() . " - PHPMailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    public function testConnection()
    {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            error_log("SMTP Connection Test Failed: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate($name, $username, $password, $userType)
    {
        $systemUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://mswdo-paluan.online";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
                .header { background: #1d4ed8; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 20px; }
                .credentials { background: #f8f9fa; padding: 15px; border-left: 4px solid #1d4ed8; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MSWD PALUAN SYSTEM</h1>
                    <h2>Account Credentials</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>{$name}</strong>,</p>
                    
                    <p>Your account has been successfully created in the MSWD PALUAN System.</p>
                    
                    <div class='credentials'>
                        <p><strong>User Type:</strong> {$userType}</p>
                        <p><strong>Username:</strong> {$username}</p>
                        <p><strong>Password:</strong> {$password}</p>
                    </div>
                    
                    <p><strong>Important:</strong> Please change your password after first login and keep your credentials secure.</p>
                    
                    <p>You can access the system here: <a href='{$systemUrl}'>{$systemUrl}</a></p>
                    
                    <p>Best regards,<br><strong>MSWD PALUAN System Administrator</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPlainTextTemplate($name, $username, $password, $userType)
    {
        $systemUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/MSWDPALUAN_SYSTEM-MAIN/html/login.php";

        return "
MSWD PALUAN SYSTEM - Account Credentials

Dear {$name},

Your account has been successfully created in the MSWD PALUAN System.

Your Login Credentials:
- User Type: {$userType}
- Username: {$username}
- Password: {$password}

Important: Please change your password after first login and keep your credentials secure.

Access the system at: {$systemUrl}

Best regards,
MSWD PALUAN System Administrator

This is an automated message. Please do not reply to this email.
        ";
    }
}
