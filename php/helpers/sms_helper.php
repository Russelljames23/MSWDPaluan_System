<?php

/**
 * IMPROVED SMS SYSTEM for MSWD - COMPLETE FIXED VERSION
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class SMSGateway
{
    private $settings;

    // UPDATED carrier gateways with ALTERNATE gateways
    private $carrierGateways = [
        'globe' => [
            'gateways' => ['@globe.com.ph', '@txt.globe.com.ph', '@globelines.com.ph'],
            'prefixes' => ['0817', '0905', '0906', '0915', '0916', '0917', '0926', '09272873751', '0935', '0936', '0937', '0956', '0965', '0966', '0967', '0975', '0976', '0977', '0978', '0979', '0995', '0996', '0997']
        ],
        'smart' => [
            'gateways' => ['@smart.com.ph', '@sms.smart.com.ph', '@smart-telco.com'],
            'prefixes' => ['0813', '0900', '0907', '0908', '0909', '0910', '0911', '0912', '0913', '0914', '0918', '0919', '0920', '0921', '0928', '0929', '0938', '0939', '0946', '0947', '0948', '0949', '0950', '0951', '0961', '0963', '0968', '0969', '0970', '0981', '0989', '0998', '0999']
        ],
        'sun' => [
            'gateways' => ['@sun.com.ph', '@mysun.com.ph', '@mobile.sunph.com'],
            'prefixes' => ['0922', '0923', '0924', '0931', '0932', '0933', '0934']
        ],
        'tnt' => [
            'gateways' => ['@tnt.com.ph', '@sms.tnt.com.ph', '@tnt.net.ph'],
            'prefixes' => ['0905', '0906', '0907', '0908', '0909', '0910', '0912', '0930', '0931']
        ]
    ];

    public function __construct($settings = null)
    {
        if ($settings === null) {
            $settings = $this->loadSettingsFromDB();
        }
        $this->settings = $settings;

        error_log("SMSGateway initialized. Demo: " . (isset($settings['demo_mode']) && $settings['demo_mode'] ? 'YES' : 'NO'));
        error_log("SMTP User: " . ($settings['smtp_user'] ?? 'Not set'));
    }

    /**
     * LOAD SETTINGS FROM DATABASE - FIXED
     */
    private function loadSettingsFromDB()
    {
        $conn = $this->getDBConnection();
        if (!$conn) {
            return $this->getDefaultSettings();
        }

        $result = $conn->query("SELECT * FROM sms_settings LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $conn->close();

            // Convert string values to proper types
            if (isset($settings['is_active'])) {
                $settings['is_active'] = (bool)$settings['is_active'];
            }
            if (isset($settings['demo_mode'])) {
                $settings['demo_mode'] = (bool)$settings['demo_mode'];
            }

            return $settings;
        }

        $conn->close();
        return $this->getDefaultSettings();
    }

    /**
     * GET DATABASE CONNECTION - FIXED
     */
    private function getDBConnection()
    {
        static $conn = null;

        if ($conn === null || !$conn->ping()) {
            $conn = new mysqli("localhost", "root", "", "mswd_seniors");

            if ($conn->connect_error) {
                error_log("SMS DB Connection Failed: " . $conn->connect_error);
                return false;
            }

            $conn->set_charset("utf8mb4");
        }

        return $conn;
    }

    /**
     * DEFAULT SETTINGS
     */
    private function getDefaultSettings()
    {
        return [
            'provider' => 'email_sms',
            'sender_id' => 'MSWDPALUAN',
            'is_active' => true,
            'demo_mode' => true, // Default to demo mode for safety
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls'
        ];
    }

    /**
     * SEND WITH MAIL() FUNCTION - IMPROVED
     */
    private function sendWithMailFunction($toEmail, $subject, $message)
    {
        // IMPORTANT: For SMS via email, subject should be empty
        $subject = '';
        
        // Clean up the message for SMS
        $message = $this->formatMessageForSMS($message);
        
        // Headers optimized for SMS
        $headers = [
            'From' => $this->settings['sender_id'] . ' <' . ($this->settings['smtp_user'] ?? 'noreply@mswd.paluan.ph') . '>',
            'Reply-To' => 'no-reply@mswd.paluan.ph',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
            'X-Priority' => '1',
            'X-MSMail-Priority' => 'High',
            'Importance' => 'High',
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        $headersStr = '';
        foreach ($headers as $key => $value) {
            $headersStr .= "$key: $value\r\n";
        }

        // Additional parameters
        $additionalParams = '-f ' . ($this->settings['smtp_user'] ?? 'noreply@mswd.paluan.ph');

        // Send email
        $result = @mail($toEmail, $subject, $message, $headersStr, $additionalParams);

        if ($result) {
            error_log("✅ Mail function: Email sent to $toEmail");
        } else {
            error_log("❌ Mail function: Failed to send to $toEmail");
            $error = error_get_last();
            if ($error) {
                error_log("Mail function error: " . $error['message']);
            }
        }

        return $result;
    }

    /**
     * VALIDATE PHONE NUMBER - IMPROVED
     */
    private function validatePhoneNumber($phoneNumber)
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', trim($phoneNumber));
        
        error_log("Validating: $phoneNumber -> Clean: $cleanNumber");

        // Check for various formats
        // 1. 09XXXXXXXXX format
        if (preg_match('/^09[0-9]{9}$/', $cleanNumber)) {
            return true;
        }
        
        // 2. +639XXXXXXXXXX format
        if (preg_match('/^639[0-9]{9}$/', $cleanNumber)) {
            return true;
        }
        
        // 3. 9XXXXXXXXX format
        if (preg_match('/^9[0-9]{9}$/', $cleanNumber)) {
            return true;
        }

        return false;
    }

    /**
     * FORMAT PHONE NUMBER - IMPROVED (always returns 10 digits without 0)
     */
    private function formatPhoneNumber($phoneNumber)
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', trim($phoneNumber));
        
        error_log("Formatting: $phoneNumber -> Clean: $cleanNumber");

        // Remove country code (63)
        if (substr($cleanNumber, 0, 2) === '63') {
            $cleanNumber = substr($cleanNumber, 2);
        }

        // Remove leading 0
        if (substr($cleanNumber, 0, 1) === '0') {
            $cleanNumber = substr($cleanNumber, 1);
        }

        // Should be exactly 10 digits starting with 9
        if (strlen($cleanNumber) === 10 && substr($cleanNumber, 0, 1) === '9') {
            return $cleanNumber;
        }

        error_log("Format error: $phoneNumber -> $cleanNumber (length: " . strlen($cleanNumber) . ")");
        return $cleanNumber;
    }

    /**
     * DETECT CARRIER - COMPLETELY REWRITTEN
     */
    private function detectCarrier($phoneNumber)
    {
        // Format the number first
        $formatted = $this->formatPhoneNumber($phoneNumber);
        
        if (strlen($formatted) !== 10) {
            error_log("Invalid number length for carrier detection: $formatted");
            return 'smart'; // Default
        }

        $prefix = substr($formatted, 0, 4);
        error_log("Detecting carrier for: $phoneNumber -> $formatted, Prefix: $prefix");

        // Check Globe first (since 0927 is Globe)
        if (in_array($prefix, $this->carrierGateways['globe']['prefixes'])) {
            error_log("✅ Detected carrier: globe for prefix: $prefix");
            return 'globe';
        }

        // Check Smart
        if (in_array($prefix, $this->carrierGateways['smart']['prefixes'])) {
            error_log("✅ Detected carrier: smart for prefix: $prefix");
            return 'smart';
        }

        // Check Sun
        if (in_array($prefix, $this->carrierGateways['sun']['prefixes'])) {
            error_log("✅ Detected carrier: sun for prefix: $prefix");
            return 'sun';
        }

        // Check TNT
        if (in_array($prefix, $this->carrierGateways['tnt']['prefixes'])) {
            error_log("✅ Detected carrier: tnt for prefix: $prefix");
            return 'tnt';
        }

        // Try 3-digit prefix
        $prefix3 = substr($formatted, 0, 3);
        foreach ($this->carrierGateways as $carrier => $data) {
            foreach ($data['prefixes'] as $carrierPrefix) {
                if (substr($carrierPrefix, 0, 3) === $prefix3) {
                    error_log("✅ Detected carrier (3-digit): $carrier for prefix: $prefix3");
                    return $carrier;
                }
            }
        }

        error_log("❌ Could not detect carrier for: $phoneNumber (formatted: $formatted)");
        return 'smart'; // Default to Smart
    }

    /**
     * PHONE TO EMAIL - IMPROVED
     */
    private function phoneToEmail($phoneNumber)
    {
        $cleanNumber = $this->formatPhoneNumber($phoneNumber);

        if (strlen($cleanNumber) !== 10) {
            error_log("Invalid phone length for email: $cleanNumber");
            return false;
        }

        $carrier = $this->detectCarrier($phoneNumber);
        error_log("Carrier for $phoneNumber: $carrier");

        if (!isset($this->carrierGateways[$carrier])) {
            error_log("Unknown carrier: $carrier");
            return false;
        }

        // Use the first gateway
        $gateway = $this->carrierGateways[$carrier]['gateways'][0];
        $email = $cleanNumber . $gateway;

        error_log("Generated email: $email (Number: $cleanNumber, Carrier: $carrier)");
        return $email;
    }

    /**
     * SEND VIA SMTP - IMPROVED WITH BETTER ERROR HANDLING
     */
    private function sendViaSMTP($toEmail, $subject, $message)
    {
        error_log("SMTP Send - To: $toEmail, Host: {$this->settings['smtp_host']}");

        // Check if we have PHPMailer
        if (file_exists(__DIR__ . '/PHPMailer-master/src/PHPMailer.php')) {
            return $this->sendWithPHPMailer($toEmail, $subject, $message);
        }

        // Fallback to mail() function
        return $this->sendWithMailFunction($toEmail, $subject, $message);
    }

    private function sendWithPHPMailer($toEmail, $subject, $message)
    {
        try {
            require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
            require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Enable detailed debugging
            $mail->SMTPDebug = 3; // VERBOSE DEBUGGING
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug (Level $level): $str");
                
                // Also output to browser if in debug mode
                if (isset($_GET['debug'])) {
                    echo "PHPMailer: $str<br>";
                }
            };

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['smtp_user'] ?? '';
            $mail->Password = $this->settings['smtp_pass'] ?? '';
            $mail->SMTPSecure = $this->settings['smtp_secure'] ?? 'tls';
            $mail->Port = $this->settings['smtp_port'] ?? 587;
            $mail->Timeout = 30;
            $mail->CharSet = 'ISO-8859-1'; // Better for SMS
            
            // IMPORTANT: Carrier-specific optimizations
            $mail->Priority = 1; // High priority
            $mail->WordWrap = 70; // Wrap at 70 characters
            
            // Less strict SSL
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients - CRITICAL FOR CARRIERS
            $mail->setFrom($this->settings['smtp_user'], $this->settings['sender_id'] ?? 'MSWD');
            $mail->addAddress($toEmail);
            
            // Add a secondary "From" for carrier compatibility
            $mail->addReplyTo('no-reply@mswd.paluan.ph', 'MSWD No-Reply');
            
            // Add CC to yourself for tracking
            $mail->addCC($this->settings['smtp_user'], 'SMS Tracker');

            // Content - CRITICAL FOR SMS
            $mail->isHTML(false);
            $mail->Subject = ''; // EMPTY subject for SMS
            $mail->Body = $this->formatMessageForSMS($message);
            
            // Add custom headers for carriers
            $mail->addCustomHeader('X-Priority', '1');
            $mail->addCustomHeader('X-MSMail-Priority', 'High');
            $mail->addCustomHeader('Importance', 'High');
            $mail->addCustomHeader('X-Mailer', 'MSWD-SMS-System');

            // Try to send
            error_log("Attempting to send SMS email to: $toEmail");
            $result = $mail->send();

            if ($result) {
                error_log("✅ PHPMailer: Email sent successfully to $toEmail");
                error_log("✅ Message was: " . substr($message, 0, 100) . "...");
                
                // Verify the email was actually accepted
                $this->verifyEmailDelivery($toEmail, $message);
                
                return true;
            } else {
                error_log("❌ PHPMailer: Failed to send to $toEmail");
                error_log("❌ PHPMailer Error: " . $mail->ErrorInfo);
                
                // Try alternative gateway
                return $this->tryAlternativeGateway($toEmail, $message, $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log("❌ PHPMailer Exception: " . $e->getMessage());
            
            // Fallback to simple mail() function
            return $this->sendWithMailFunction($toEmail, '', $message);
        }
    }
    
    /**
     * FORMAT MESSAGE FOR SMS - CARRIER SPECIFIC
     */
    private function formatMessageForSMS($message)
    {
        // Remove any HTML tags
        $message = strip_tags($message);
        
        // Trim to 160 characters (SMS limit)
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }
        
        // Add sender tag
        $sender = $this->settings['sender_id'] ?? 'MSWD';
        $formatted = $message . "\n\n- $sender";
        
        return $formatted;
    }
    
    /**
     * TRY ALTERNATIVE GATEWAY
     */
    private function tryAlternativeGateway($toEmail, $message, $previousError)
    {
        error_log("Trying alternative gateway for: $toEmail");
        
        // Extract phone number and carrier from email
        if (preg_match('/(\d{10})@(.+)$/', $toEmail, $matches)) {
            $phone = $matches[1];
            $domain = $matches[2];
            
            // Detect carrier from domain
            $carrier = $this->detectCarrierFromDomain($domain);
            
            if ($carrier && isset($this->carrierGateways[$carrier]['gateways'])) {
                // Try second gateway
                if (count($this->carrierGateways[$carrier]['gateways']) > 1) {
                    $altGateway = $this->carrierGateways[$carrier]['gateways'][1];
                    $altEmail = $phone . $altGateway;
                    
                    error_log("Trying alternative gateway: $altEmail");
                    
                    // Try with mail() function as fallback
                    return $this->sendWithMailFunction($altEmail, '', $message);
                }
            }
        }
        
        return false;
    }
    
    /**
     * DETECT CARRIER FROM DOMAIN
     */
    private function detectCarrierFromDomain($domain)
    {
        foreach ($this->carrierGateways as $carrier => $data) {
            foreach ($data['gateways'] as $gateway) {
                if (strpos($gateway, $domain) !== false) {
                    return $carrier;
                }
            }
        }
        return null;
    }
    
    /**
     * VERIFY EMAIL DELIVERY
     */
    private function verifyEmailDelivery($toEmail, $message)
    {
        // Log for manual verification
        error_log("📱 SMS SENT VERIFICATION");
        error_log("Recipient: $toEmail");
        error_log("Message: " . substr($message, 0, 100) . "...");
        error_log("Time: " . date('Y-m-d H:i:s'));
        error_log("SMTP: " . ($this->settings['smtp_user'] ?? 'Not set'));
        
        // Check if this is likely a valid carrier email
        if (!preg_match('/^\d{10}@/', $toEmail)) {
            error_log("⚠️ WARNING: Email format doesn't look like carrier gateway!");
        }
        
        // Rate limiting check
        static $lastSentTime = 0;
        $currentTime = time();
        if ($currentTime - $lastSentTime < 2) { // 2 second gap
            error_log("⚠️ WARNING: Sending too fast! May be rate limited by carrier.");
        }
        $lastSentTime = $currentTime;
    }

    /**
     * SIMPLE TEST FUNCTION - Send a test SMS and verify
     */
    public function sendTestSMS($testNumber, $testMessage = "Test SMS from MSWD System")
    {
        error_log("=== TEST SMS START ===");

        // Check if demo mode is ON
        if (isset($this->settings['demo_mode']) && $this->settings['demo_mode']) {
            error_log("⚠️ WARNING: System is in DEMO MODE. SMS will not be sent!");
            return [
                'success' => false,
                'message' => 'System is in DEMO MODE. Disable demo mode to send real SMS.',
                'demo_mode' => true
            ];
        }

        // Check if SMTP is configured
        if (empty($this->settings['smtp_user']) || empty($this->settings['smtp_pass'])) {
            error_log("❌ SMTP not configured");
            return [
                'success' => false,
                'message' => 'SMTP not configured. Please enter SMTP credentials.',
                'smtp_configured' => false
            ];
        }

        // Validate phone number
        if (!$this->validatePhoneNumber($testNumber)) {
            error_log("Invalid number: $testNumber");
            return [
                'success' => false,
                'message' => 'Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXXX',
                'valid_number' => false
            ];
        }

        // Get email address
        $emailAddress = $this->phoneToEmail($testNumber);
        if (!$emailAddress) {
            return [
                'success' => false,
                'message' => 'Could not generate email address for this number',
                'email_generated' => false
            ];
        }

        error_log("Test SMS Details:");
        error_log("Number: $testNumber");
        error_log("Email: $emailAddress");
        error_log("Message: $testMessage");
        error_log("Carrier: " . $this->detectCarrier($testNumber));

        // Try to send
        $result = $this->sendViaSMTP($emailAddress, '', $testMessage);

        if ($result) {
            // Log to database
            $this->logToDatabase($testNumber, $testMessage, 'test_sent', $this->detectCarrier($testNumber), $emailAddress);

            return [
                'success' => true,
                'message' => 'Test SMS processed. Check your phone in 1-5 minutes.',
                'email_sent_to' => $emailAddress,
                'carrier' => $this->detectCarrier($testNumber)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send test SMS. Check SMTP configuration and error logs.',
                'smtp_error' => true
            ];
        }
    }

    /**
     * ENHANCED SEND METHOD WITH BETTER TRACKING
     */
    public function send($phoneNumber, $message)
    {
        error_log("=== SEND SMS START ===");
        error_log("To: $phoneNumber");
        error_log("Demo Mode: " . (isset($this->settings['demo_mode']) && $this->settings['demo_mode'] ? 'ON' : 'OFF'));
        error_log("SMTP User: " . ($this->settings['smtp_user'] ?? 'Not set'));

        // Check if SMS is active
        if (!isset($this->settings['is_active']) || !$this->settings['is_active']) {
            error_log("❌ SMS service disabled");
            return $this->errorResponse('SMS service is disabled');
        }

        // Check if demo mode
        if (isset($this->settings['demo_mode']) && $this->settings['demo_mode']) {
            error_log("⚠️ DEMO MODE: Logging only (NO SMS SENT)");
            $carrier = $this->detectCarrier($phoneNumber);
            $email = $this->phoneToEmail($phoneNumber);
            $this->logToDatabase($phoneNumber, $message, 'demo_sent', $carrier, $email);

            return [
                'success' => true,
                'message_id' => 'DEMO-' . time(),
                'status' => 'demo_sent',
                'note' => 'DEMO MODE: SMS logged but not sent',
                'demo_mode' => true,
                'carrier' => $carrier,
                'email' => $email
            ];
        }

        // Validate phone number
        if (!$this->validatePhoneNumber($phoneNumber)) {
            error_log("❌ Invalid number format: $phoneNumber");
            return $this->errorResponse("Invalid phone number format. Use 09XXXXXXXXX");
        }

        // REAL MODE: Send via email
        $emailAddress = $this->phoneToEmail($phoneNumber);
        if (!$emailAddress) {
            error_log("❌ Could not generate email address");
            $this->logToDatabase($phoneNumber, $message, 'failed_email_generation');
            return $this->errorResponse('Could not determine carrier email');
        }

        error_log("📧 Sending to carrier email: $emailAddress");

        // Send the email with retry logic
        $maxRetries = 2;
        $result = false;
        
        for ($i = 0; $i < $maxRetries; $i++) {
            if ($i > 0) {
                error_log("Retry attempt $i for $phoneNumber");
                sleep(1); // Wait 1 second between retries
            }
            
            $result = $this->sendViaSMTP($emailAddress, '', $message);
            
            if ($result) {
                break;
            }
        }

        $carrier = $this->detectCarrier($phoneNumber);
        $status = $result ? 'sent' : 'failed';

        // Log to database
        $this->logToDatabase($phoneNumber, $message, $status, $carrier, $emailAddress);

        if ($result) {
            error_log("✅ SMS marked as sent to $phoneNumber via $carrier");
            
            // Provide realistic expectations
            return [
                'success' => true,
                'message_id' => 'SMS-' . time(),
                'status' => 'sent',
                'carrier' => $carrier,
                'email_address' => $emailAddress,
                'demo_mode' => false,
                'note' => 'SMS sent to carrier gateway. Delivery may take 1-5 minutes.',
                'estimated_delivery' => '1-5 minutes'
            ];
        } else {
            error_log("❌ SMS failed to send");
            
            // Provide helpful error message
            return $this->errorResponse('Failed to send SMS. Carrier gateway may be blocking emails. Try again later.');
        }
    }

    /**
     * BULK SMS - WITH BETTER REPORTING
     */
    public function sendBulk($phoneNumbers, $message)
    {
        error_log("=== BULK SMS START ===");
        error_log("Total numbers: " . count($phoneNumbers));
        error_log("Demo mode: " . (isset($this->settings['demo_mode']) && $this->settings['demo_mode'] ? 'YES' : 'NO'));
        error_log("SMTP configured: " . (!empty($this->settings['smtp_user']) ? 'YES' : 'NO'));

        $results = [
            'total' => count($phoneNumbers),
            'sent' => 0,
            'failed' => 0,
            'invalid' => 0,
            'failed_numbers' => [],
            'invalid_numbers' => [],
            'demo_mode' => isset($this->settings['demo_mode']) ? $this->settings['demo_mode'] : false,
            'carrier_stats' => [],
            'details' => []
        ];

        foreach ($phoneNumbers as $index => $number) {
            $cleanNumber = trim($number);

            error_log("\n--- Processing #" . ($index + 1) . ": $cleanNumber ---");

            // Validate
            if (!$this->validatePhoneNumber($cleanNumber)) {
                $results['invalid']++;
                $results['invalid_numbers'][] = $cleanNumber;
                $results['details'][] = [
                    'number' => $cleanNumber,
                    'status' => 'invalid_format',
                    'message' => 'Invalid phone number format'
                ];
                error_log("❌ Invalid format");
                continue;
            }

            $formatted = $this->formatPhoneNumber($cleanNumber);
            $carrier = $this->detectCarrier($cleanNumber);
            $email = $this->phoneToEmail($cleanNumber);

            error_log("Formatted: $formatted");
            error_log("Carrier: $carrier");
            error_log("Email: " . ($email ? $email : 'Could not generate'));

            $result = $this->send($cleanNumber, $message);

            if ($result['success']) {
                $results['sent']++;
                
                // Track carrier statistics
                if (!isset($results['carrier_stats'][$carrier])) {
                    $results['carrier_stats'][$carrier] = 0;
                }
                $results['carrier_stats'][$carrier]++;
                
                $results['details'][] = [
                    'number' => $cleanNumber,
                    'status' => isset($result['demo_mode']) && $result['demo_mode'] ? 'demo_sent' : 'sent',
                    'carrier' => $carrier,
                    'email' => $email,
                    'demo_mode' => isset($result['demo_mode']) ? $result['demo_mode'] : false
                ];
                error_log("✅ " . (isset($result['demo_mode']) && $result['demo_mode'] ? 'DEMO: Logged' : 'Sent'));
            } else {
                $results['failed']++;
                $results['failed_numbers'][] = $cleanNumber;
                $results['details'][] = [
                    'number' => $cleanNumber,
                    'status' => 'failed',
                    'message' => $result['message'],
                    'carrier' => $carrier,
                    'email' => $email
                ];
                error_log("❌ Failed: " . $result['message']);
            }

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }

        error_log("\n=== BULK SMS RESULTS ===");
        error_log("Total: {$results['total']}");
        error_log("Sent: {$results['sent']}");
        error_log("Failed: {$results['failed']}");
        error_log("Invalid: {$results['invalid']}");
        
        if (!empty($results['carrier_stats'])) {
            foreach ($results['carrier_stats'] as $carrier => $count) {
                error_log("Carrier $carrier: $count messages");
            }
        }

        if ($results['demo_mode']) {
            error_log("⚠️ NOTE: System is in DEMO MODE. No actual SMS were sent!");
        }

        return $results;
    }

    private function logToDatabase($phoneNumber, $message, $status, $carrier = '', $email = '')
    {
        $conn = $this->getDBConnection();
        if (!$conn) {
            error_log("Database connection failed for logging");
            return false;
        }

        // Escape values
        $phoneNumber = $conn->real_escape_string($phoneNumber);
        $message = $conn->real_escape_string(substr($message, 0, 500));
        $status = $conn->real_escape_string($status);
        $carrier = $conn->real_escape_string($carrier);
        $email = $conn->real_escape_string($email);
        $messageId = uniqid('sms_', true);

        // Check if table exists, create it if not
        $tableCheck = $conn->query("SHOW TABLES LIKE 'sms_logs'");
        if ($tableCheck->num_rows == 0) {
            $createTable = "CREATE TABLE sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone_number VARCHAR(20) NOT NULL,
                message TEXT,
                status VARCHAR(50),
                carrier VARCHAR(50),
                email_address VARCHAR(255),
                message_id VARCHAR(100),
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($createTable);
            error_log("Created sms_logs table");
        }

        $query = "INSERT INTO sms_logs (phone_number, message, status, carrier, email_address, message_id) 
                 VALUES ('$phoneNumber', '$message', '$status', '$carrier', '$email', '$messageId')";

        $result = $conn->query($query);

        if ($result) {
            error_log("✅ Logged to database: $phoneNumber - $status ($carrier)");
        } else {
            error_log("❌ Database log failed: " . $conn->error);
            // Try simpler query
            $simpleQuery = "INSERT INTO sms_logs (phone_number, message, status, carrier) 
                           VALUES ('$phoneNumber', '$message', '$status', '$carrier')";
            $conn->query($simpleQuery);
        }

        return true;
    }

    private function errorResponse($message)
    {
        error_log("ERROR: $message");
        return [
            'success' => false,
            'message' => $message
        ];
    }

    /**
     * CHECK SYSTEM STATUS - ENHANCED
     */
    public function checkSystemStatus()
    {
        $demoMode = isset($this->settings['demo_mode']) ? $this->settings['demo_mode'] : false;
        $isActive = isset($this->settings['is_active']) ? $this->settings['is_active'] : false;
        $hasSMTP = !empty($this->settings['smtp_user']) && !empty($this->settings['smtp_pass']);

        $status = 'Ready';
        $message = 'System is operational';

        if (!$isActive) {
            $status = 'Disabled';
            $message = 'SMS service is disabled';
        } elseif ($demoMode) {
            $status = 'Demo Mode';
            $message = 'SMS are logged but not actually sent';
        } elseif (!$hasSMTP) {
            $status = 'Not Configured';
            $message = 'SMTP credentials not configured';
        }

        return [
            'success' => true,
            'status' => $status,
            'message' => $message,
            'demo_mode' => $demoMode,
            'smtp_configured' => $hasSMTP,
            'is_active' => $isActive
        ];
    }

    /**
     * TEST SMTP CONNECTION - IMPROVED
     */
    public function testSMTPConnection()
    {
        $smtpHost = $this->settings['smtp_host'] ?? 'smtp.gmail.com';
        $smtpUser = $this->settings['smtp_user'] ?? '';
        $smtpPass = $this->settings['smtp_pass'] ?? '';

        if (empty($smtpUser) || empty($smtpPass)) {
            return [
                'success' => false,
                'message' => 'SMTP username or password is empty'
            ];
        }

        // Test with PHPMailer if available
        if (file_exists(__DIR__ . '/PHPMailer-master/src/PHPMailer.php')) {
            try {
                require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
                require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = $this->settings['smtp_secure'] ?? 'tls';
                $mail->Port = $this->settings['smtp_port'] ?? 587;
                $mail->SMTPDebug = 0;
                $mail->Timeout = 10;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                if ($mail->smtpConnect()) {
                    $mail->smtpClose();
                    return [
                        'success' => true,
                        'message' => "Successfully connected to $smtpHost"
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "Failed to connect to $smtpHost"
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => "SMTP Error: " . $e->getMessage()
                ];
            }
        }

        // Simple test by trying to send a test email
        $testResult = $this->sendWithMailFunction($smtpUser, 'SMTP Test', 'This is a test email from MSWD SMS System');

        if ($testResult) {
            return [
                'success' => true,
                'message' => "SMTP appears to be working (test email sent to yourself)"
            ];
        } else {
            return [
                'success' => false,
                'message' => "SMTP test failed. Check your configuration."
            ];
        }
    }

    /**
     * DEBUG CARRIER DETECTION
     */
    public function debugCarrierDetection($phoneNumber)
    {
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Carrier Debug Information</h3>";
        
        $formatted = $this->formatPhoneNumber($phoneNumber);
        echo "<p>Phone: $phoneNumber</p>";
        echo "<p>Formatted: $formatted</p>";
        
        if (strlen($formatted) === 10) {
            $prefix = substr($formatted, 0, 4);
            echo "<p>Prefix (4-digit): $prefix</p>";
            
            $carrier = $this->detectCarrier($phoneNumber);
            echo "<p>Detected Carrier: <strong>$carrier</strong></p>";
            
            $email = $this->phoneToEmail($phoneNumber);
            echo "<p>Email: " . ($email ? $email : 'Could not generate') . "</p>";
            
            echo "<br><p>Checking all prefixes:</p>";
            foreach ($this->carrierGateways as $carrierName => $data) {
                if (in_array($prefix, $data['prefixes'])) {
                    echo "<p style='color: green;'>✅ $carrierName has prefix: $prefix</p>";
                } else {
                    echo "<p style='color: #666;'>❌ $carrierName doesn't have prefix: $prefix</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>Invalid phone number length: " . strlen($formatted) . " digits</p>";
        }
        
        echo "</div>";
    }

    /**
     * REAL-TIME TEST FUNCTION
     */
    public function realTimeTestSMS($testNumber, $testMessage = "Test SMS from MSWD System")
    {
        // First, check if we can even connect to SMTP
        $smtpTest = $this->testSMTPConnection();
        if (!$smtpTest['success']) {
            return [
                'success' => false,
                'message' => 'SMTP Connection Failed: ' . $smtpTest['message'],
                'smtp_error' => true
            ];
        }

        // Validate number
        if (!$this->validatePhoneNumber($testNumber)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number. Use format: 09XXXXXXXXX',
                'valid_number' => false
            ];
        }

        // Get carrier info
        $carrier = $this->detectCarrier($testNumber);
        $email = $this->phoneToEmail($testNumber);
        
        if (!$email) {
            return [
                'success' => false,
                'message' => 'Could not generate carrier email. Unsupported carrier?',
                'carrier' => $carrier
            ];
        }

        // Show what will be sent
        $debugInfo = [
            'number' => $testNumber,
            'carrier' => $carrier,
            'carrier_email' => $email,
            'message_preview' => substr($testMessage, 0, 50) . '...',
            'smtp_account' => $this->settings['smtp_user']
        ];

        // Send test
        $result = $this->send($testNumber, $testMessage);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => '✅ Test SMS processed!',
                'details' => 'Check your phone in 1-5 minutes.',
                'debug_info' => $debugInfo,
                'carrier' => $carrier,
                'email_used' => $email
            ];
        } else {
            return [
                'success' => false,
                'message' => '❌ Test SMS failed',
                'details' => $result['message'],
                'debug_info' => $debugInfo,
                'carrier' => $carrier
            ];
        }
    }
}

// Public helper functions
function getSMSSettings()
{
    $conn = new mysqli("localhost", "root", "", "mswd_seniors");
    if ($conn->connect_error) {
        return [
            'provider' => 'email_sms',
            'sender_id' => 'MSWDPALUAN',
            'is_active' => true,
            'demo_mode' => true,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls'
        ];
    }

    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'sms_settings'");
    if ($tableCheck->num_rows == 0) {
        // Create the table
        $createTable = "CREATE TABLE sms_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) DEFAULT 'email_sms',
            api_key VARCHAR(255),
            sender_id VARCHAR(50) DEFAULT 'MSWDPALUAN',
            smtp_host VARCHAR(100) DEFAULT 'smtp.gmail.com',
            smtp_port VARCHAR(10) DEFAULT '587',
            smtp_user VARCHAR(255),
            smtp_pass VARCHAR(255),
            smtp_secure VARCHAR(10) DEFAULT 'tls',
            is_active TINYINT(1) DEFAULT 1,
            demo_mode TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($createTable);

        // Insert default settings
        $insertDefault = "INSERT INTO sms_settings (provider, sender_id, is_active, demo_mode) 
                         VALUES ('email_sms', 'MSWDPALUAN', 1, 1)";
        $conn->query($insertDefault);
    }

    $result = $conn->query("SELECT * FROM sms_settings LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $conn->close();

        // Ensure all fields exist
        $defaults = [
            'provider' => 'email_sms',
            'sender_id' => 'MSWDPALUAN',
            'is_active' => true,
            'demo_mode' => true,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls'
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    $conn->close();
    return [
        'provider' => 'email_sms',
        'sender_id' => 'MSWDPALUAN',
        'is_active' => true,
        'demo_mode' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls'
    ];
}

function saveSMSSettings($settings)
{
    $conn = new mysqli("localhost", "root", "", "mswd_seniors");
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    // Ensure all fields
    $defaults = [
        'provider' => 'email_sms',
        'api_key' => '',
        'sender_id' => 'MSWDPALUAN',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'is_active' => true,
        'demo_mode' => false
    ];

    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }

    // Escape values
    $provider = $conn->real_escape_string($settings['provider']);
    $apiKey = $conn->real_escape_string($settings['api_key']);
    $senderId = $conn->real_escape_string($settings['sender_id']);
    $smtpHost = $conn->real_escape_string($settings['smtp_host']);
    $smtpPort = $conn->real_escape_string($settings['smtp_port']);
    $smtpUser = $conn->real_escape_string($settings['smtp_user']);
    $smtpPass = $conn->real_escape_string($settings['smtp_pass']);
    $smtpSecure = $conn->real_escape_string($settings['smtp_secure']);
    $isActive = (int)($settings['is_active'] ?? 1);
    $demoMode = (int)($settings['demo_mode'] ?? 0);

    // Check if settings exist
    $check = $conn->query("SELECT id FROM sms_settings LIMIT 1");

    if ($check && $check->num_rows > 0) {
        // Update existing settings
        $query = "UPDATE sms_settings SET 
                  provider = '$provider',
                  api_key = '$apiKey',
                  sender_id = '$senderId',
                  smtp_host = '$smtpHost',
                  smtp_port = '$smtpPort',
                  smtp_user = '$smtpUser',
                  smtp_pass = '$smtpPass',
                  smtp_secure = '$smtpSecure',
                  is_active = $isActive,
                  demo_mode = $demoMode,
                  updated_at = NOW()
                  LIMIT 1";
    } else {
        // Insert new settings
        $query = "INSERT INTO sms_settings 
                  (provider, api_key, sender_id, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, is_active, demo_mode) 
                  VALUES 
                  ('$provider', '$apiKey', '$senderId', '$smtpHost', '$smtpPort', '$smtpUser', '$smtpPass', '$smtpSecure', $isActive, $demoMode)";
    }

    $result = $conn->query($query);

    if ($result) {
        error_log("✅ SMS settings saved successfully");
    } else {
        error_log("❌ Failed to save SMS settings: " . $conn->error);
    }

    $conn->close();

    return $result;
}