<?php
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
            // Server settings
            $this->mailer->SMTPDebug = 0;
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'russelljamestadalan23@gmail.com'; // Fixed
            $this->mailer->Password = 'vlnk nthk uqiy epss'; // Use correct app password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            $this->mailer->CharSet = 'UTF-8';

            // Recipients
            $this->mailer->setFrom('russelljamestadalan23@gmail.com', 'MSWD PALUAN System'); // Fixed
            $this->mailer->addReplyTo('russelljamestadalan23@gmail.com', 'MSWD PALUAN System'); // Fixed
        } catch (Exception $e) {
            error_log("PHPMailer Configuration Error: " . $e->getMessage());
            throw new Exception("Email configuration failed: " . $e->getMessage());
        }
    }

    public function sendAccountCredentials($toEmail, $toName, $username, $password, $userType)
    {
        try {
            // Clear all recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

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
            $result = $this->mailer->send();

            if ($result) {
                error_log("Email sent successfully to: " . $toEmail);
                return true;
            } else {
                error_log("Email sending failed to: " . $toEmail . " - Error: " . $this->mailer->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            error_log("Email sending failed to {$toEmail}: " . $this->mailer->ErrorInfo);
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
        $systemUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/MSWDPaluan_System-main/html/login.php";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Arial', sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header { 
                    background: #1d4ed8; 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 24px; 
                    font-weight: bold;
                }
                .header h2 { 
                    margin: 10px 0 0 0; 
                    font-size: 18px; 
                    font-weight: normal;
                    opacity: 0.9;
                }
                .content { 
                    padding: 30px; 
                }
                .credentials { 
                    background: #f8f9fa; 
                    padding: 20px; 
                    border: 1px solid #e9ecef; 
                    border-radius: 8px; 
                    margin: 20px 0; 
                    border-left: 4px solid #1d4ed8;
                }
                .credential-item {
                    margin: 10px 0;
                    padding: 8px 0;
                }
                .credential-label {
                    font-weight: bold;
                    color: #495057;
                }
                .credential-value {
                    color: #1d4ed8;
                    font-weight: 600;
                }
                .security-notes {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 6px;
                    padding: 15px;
                    margin: 20px 0;
                }
                .security-notes ul {
                    margin: 10px 0;
                    padding-left: 20px;
                }
                .security-notes li {
                    margin: 5px 0;
                }
                .login-button {
                    display: inline-block;
                    background: #1d4ed8;
                    color: white;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: bold;
                    margin: 15px 0;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    font-size: 12px; 
                    color: #6c757d; 
                    border-top: 1px solid #e9ecef;
                    padding-top: 20px;
                }
                .warning {
                    color: #dc3545;
                    font-weight: bold;
                }
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
                    
                    <p>Your account has been successfully created in the MSWD PALUAN System. Below are your login credentials:</p>
                    
                    <div class='credentials'>
                        <div class='credential-item'>
                            <span class='credential-label'>User Type:</span>
                            <span class='credential-value'> {$userType}</span>
                        </div>
                        <div class='credential-item'>
                            <span class='credential-label'>Username:</span>
                            <span class='credential-value'> {$username}</span>
                        </div>
                        <div class='credential-item'>
                            <span class='credential-label'>Password:</span>
                            <span class='credential-value'> {$password}</span>
                        </div>
                    </div>
                    
                    <div class='security-notes'>
                        <p class='warning'>Important Security Notes:</p>
                        <ul>
                            <li>Keep your credentials confidential and secure</li>
                            <li>Change your password immediately after first login</li>
                            <li>Do not share your login details with anyone</li>
                            <li>Log out after each session, especially on shared computers</li>
                        </ul>
                    </div>
                    
                    <p>You can access the system using the link below:</p>
                    <a href='{$systemUrl}' class='login-button'>Access MSWD PALUAN System</a>
                    
                    <p>If you have any questions or encounter any issues, please contact the system administrator immediately.</p>
                    
                    <p>Best regards,<br><strong>MSWD PALUAN System Administrator</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from MSWD PALUAN System. Please do not reply to this email.</p>
                    <p>If you believe you received this email in error, please contact the system administrator.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPlainTextTemplate($name, $username, $password, $userType)
    {
        $systemUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/MSWDPaluan_System-main/html/login.php";

        return "
MSWD PALUAN SYSTEM - Account Credentials

Dear {$name},

Your account has been successfully created in the MSWD PALUAN System.

Your Login Credentials:
- User Type: {$userType}
- Username: {$username}
- Password: {$password}

Important Security Notes:
- Keep your credentials confidential and secure
- Change your password immediately after first login
- Do not share your login details with anyone
- Log out after each session, especially on shared computers

You can access the system at: {$systemUrl}

If you have any questions or encounter any issues, please contact the system administrator immediately.

Best regards,
MSWD PALUAN System Administrator

This is an automated message from MSWD PALUAN System. Please do not reply to this email.
If you believe you received this email in error, please contact the system administrator.
        ";
    }
}
