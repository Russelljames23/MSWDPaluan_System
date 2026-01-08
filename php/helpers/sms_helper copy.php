<?php

/**
 * SMS Helper for Semaphore API - IMPROVED VERSION
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

class SMSGateway
{
    private $settings;
    private $apiUrl = 'https://api.semaphore.co/api/v4/messages';
    private $conn = null; // Add this property

    public function __construct($settings = null)
    {
        if ($settings === null) {
            $settings = $this->loadSettingsFromDB();
        }
        $this->settings = $settings;

        error_log("=== SMSGateway Initialized ===");
        error_log("API Key: " . (empty($settings['api_key']) ? 'NOT SET' : substr($settings['api_key'], 0, 8) . '...'));
        error_log("Demo Mode: " . (isset($settings['demo_mode']) && $settings['demo_mode'] ? 'YES' : 'NO'));
        error_log("Active: " . (isset($settings['is_active']) && $settings['is_active'] ? 'YES' : 'NO'));
    }

    private function loadSettingsFromDB()
    {
        $conn = $this->getDBConnection();
        if (!$conn) {
            error_log("Failed to connect to database");
            return $this->getDefaultSettings();
        }

        // Get ONLY SMS-specific columns
        $result = $conn->query("SELECT 
        id,
        provider,
        api_key,
        sender_id,
        is_active,
        demo_mode,
        created_at,
        updated_at
        FROM sms_settings LIMIT 1");

        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();

            // Convert string values to proper types
            if (isset($settings['is_active'])) {
                $settings['is_active'] = (bool)$settings['is_active'];
            }
            if (isset($settings['demo_mode'])) {
                $settings['demo_mode'] = (bool)$settings['demo_mode'];
            }

            // Ensure sender_id is correct
            if (isset($settings['sender_id']) && $settings['sender_id'] !== 'Semaphore') {
                $settings['sender_id'] = 'Semaphore';
            }

            return $settings;
        }

        return $this->getDefaultSettings();
    }

    private function getDBConnection()
    {
        if ($this->conn === null) {
            // Create new connection
            $this->conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");

            if ($this->conn->connect_error) {
                error_log("SMS DB Connection Failed: " . $this->conn->connect_error);
                return false;
            }

            $this->conn->set_charset("utf8mb4");
        } elseif (!$this->conn->ping()) {
            // If connection exists but is dead, reconnect
            error_log("Database connection lost, reconnecting...");
            $this->conn->close();
            $this->conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");

            if ($this->conn->connect_error) {
                error_log("SMS DB Reconnection Failed: " . $this->conn->connect_error);
                return false;
            }

            $this->conn->set_charset("utf8mb4");
        }

        return $this->conn;
    }

    private function getDefaultSettings()
    {
        return [
            'provider' => 'semaphore',
            'api_key' => '11203b3c9a4bc430dd3a1b181ece8b6c',
            'sender_id' => 'SEMAPHORE', // ALL CAPS
            'is_active' => true,
            'demo_mode' => false
        ];
    }

    public function send($phoneNumber, $message)
    {
        $senderId = 'SEMAPHORE'; 
        error_log("\n=== SEND SMS VIA SEMAPHORE ===");
        error_log("Phone: $phoneNumber");
        error_log("Message: " . substr($message, 0, 50) . "...");
        error_log("API Key: " . (empty($this->settings['api_key']) ? 'NOT SET' : substr($this->settings['api_key'], 0, 8) . '...'));
        error_log("Demo Mode: " . (isset($this->settings['demo_mode']) && $this->settings['demo_mode'] ? 'YES' : 'NO'));

        // Check if SMS is active
        if (!isset($this->settings['is_active']) || !$this->settings['is_active']) {
            error_log("❌ SMS service disabled");
            return $this->errorResponse('SMS service is disabled');
        }

        // Validate phone number
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);
        if (!$this->validatePhoneNumber($cleanNumber)) {
            error_log("❌ Invalid number format: $phoneNumber (clean: $cleanNumber)");
            return $this->errorResponse("Invalid phone number format. Use: 09XXXXXXXXX, 9XXXXXXXXX, or +639XXXXXXXXX");
        }

        // Check if demo mode
        if (isset($this->settings['demo_mode']) && $this->settings['demo_mode']) {
            error_log("⚠️ DEMO MODE: Logging only (NO SMS SENT)");
            $this->logToDatabase($phoneNumber, $message, 'demo_sent', 'semaphore', 'demo');

            return [
                'success' => true,
                'status' => 'demo_sent',
                'note' => 'DEMO MODE: SMS logged but not sent',
                'demo_mode' => true
            ];
        }

        // REAL MODE: Send via Semaphore API
        $apiKey = $this->settings['api_key'] ?? '';

        if (empty($apiKey)) {
            error_log("❌ API Key not configured");
            return $this->errorResponse('API Key not configured');
        }

        // Format phone number for Semaphore (international format)
        $formattedNumber = $this->formatNumberForSemaphore($cleanNumber);
        $senderId = $this->settings['sender_id'] ?? 'SEMAPHORE';

        // IMPORTANT: Semaphore auto-corrects "SEMAPHORE" to "Semaphore" (case-sensitive)
        // Use exactly "Semaphore" or "SEMAPHORE" - both work
        if (strtoupper($senderId) === 'SEMAPHORE') {
            $senderId = 'Semaphore'; // Use the exact case that works
        }

        // Validate formatted number
        if (!$formattedNumber || !$this->validateInternationalNumber($formattedNumber)) {
            error_log("❌ Invalid formatted number: $formattedNumber");
            return $this->errorResponse(
                'Invalid Philippine mobile number. Use 09XXXXXXXXX (11 digits) or 9XXXXXXXXX (10 digits).'
            );
        }

        // Send via Semaphore API
        $postData = [
            'apikey' => $apiKey,
            'number' => $formattedNumber,
            'message' => $message,
            'sender_name' => 'SEMAPHORE' // Fixed: underscore and ALL CAPS
        ];

        error_log("Post Data (masked): " . json_encode(array_merge($postData, ['apikey' => substr($apiKey, 0, 8) . '...']), JSON_PRETTY_PRINT));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        error_log("=== SEMAPHORE API RESPONSE ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response: $response");
        if ($error) {
            error_log("CURL Error: $error");
        }

        $status = 'failed';
        $carrier = 'semaphore';
        $apiError = 'Unknown error';
        $messageId = null;
        $responseData = null;

        if ($response && $httpCode == 200) {
            $responseData = json_decode($response, true);

            if (is_array($responseData) && isset($responseData[0])) {
                if (isset($responseData[0]['status']) && ($responseData[0]['status'] == 'Queued' || $responseData[0]['status'] == 'Pending')) {
                    $status = 'queued';
                    $messageId = $responseData[0]['message_id'] ?? null;
                    error_log("✅ SMS queued successfully");
                    error_log("Message ID: " . ($messageId ?? 'N/A'));
                } elseif (isset($responseData[0]['error'])) {
                    $apiError = $responseData[0]['error'];
                    error_log("❌ API returned error: $apiError");
                } else {
                    $apiError = "Unexpected response format";
                    error_log("❌ API response format unexpected: " . print_r($responseData[0], true));
                }
            } else {
                $apiError = "Invalid API response";
                error_log("❌ Invalid API response format: $response");
            }
        } elseif ($httpCode == 401) {
            $apiError = 'Invalid API Key (Unauthorized)';
            error_log("❌ API Key rejected (HTTP 401)");
        } elseif ($httpCode == 402) {
            $apiError = 'Insufficient credits';
            error_log("❌ Insufficient credits (HTTP 402)");
        } elseif ($httpCode == 422) {
            $apiError = 'Invalid parameters (phone number or sender name)';
            error_log("❌ Validation error (HTTP 422)");
        } else {
            $apiError = "HTTP $httpCode: " . ($error ?: 'Unknown error');
            error_log("❌ API connection failed: HTTP $httpCode");
        }

        // Log to database
        $this->logToDatabase($phoneNumber, $message, $status, $carrier, $response);

        if ($status === 'queued') {
            return [
                'success' => true,
                'status' => 'queued',
                'message_id' => $messageId,
                'carrier' => $carrier,
                'demo_mode' => false,
                'note' => 'SMS queued for delivery via Semaphore API',
                'formatted_number' => $formattedNumber,
                'response' => $responseData ?? []
            ];
        } else {
            return $this->errorResponse("Failed to send SMS: $apiError");
        }
    }
    public function debugSend($phoneNumber, $message)
    {
        error_log("\n=== DEBUG SEND ===");

        $apiKey = $this->settings['api_key'] ?? '';
        $senderId = 'SEMAPHORE'; // Force ALL CAPS
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);
        $formattedNumber = $this->formatNumberForSemaphore($cleanNumber);

        error_log("API Key: " . substr($apiKey, 0, 8) . '...');
        error_log("Sender ID: $senderId");
        error_log("Formatted Number: $formattedNumber");

        // Show what would be sent
        $postData = [
            'apikey' => $apiKey,
            'number' => $formattedNumber,
            'message' => $message,
            'sendername' => $senderId  // CORRECT
        ];

        error_log("Post Data: " . json_encode($postData, JSON_PRETTY_PRINT));

        // Actually send
        return $this->send($phoneNumber, $message);
    }
    public function sendBulk($phoneNumbers, $message)
    {
        error_log("=== BULK SMS VIA SEMAPHORE ===");
        error_log("Total numbers: " . count($phoneNumbers));
        error_log("Demo mode: " . (isset($this->settings['demo_mode']) && $this->settings['demo_mode'] ? 'YES' : 'NO'));

        $results = [
            'total' => count($phoneNumbers),
            'sent' => 0,
            'failed' => 0,
            'invalid' => 0,
            'failed_numbers' => [],
            'invalid_numbers' => [],
            'demo_mode' => $this->settings['demo_mode'] ?? false,
            'details' => []
        ];

        // Check if SMS is active
        if (!isset($this->settings['is_active']) || !$this->settings['is_active']) {
            error_log("❌ SMS service disabled");
            return array_merge($results, [
                'error' => 'SMS service is disabled'
            ]);
        }

        // Check demo mode
        $demoMode = isset($this->settings['demo_mode']) && $this->settings['demo_mode'];

        if ($demoMode) {
            error_log("⚠️ DEMO MODE: Logging only (NO SMS SENT)");

            foreach ($phoneNumbers as $index => $number) {
                $cleanNumber = $this->cleanPhoneNumber(trim($number));

                if ($this->validatePhoneNumber($cleanNumber)) {
                    $this->logToDatabase($cleanNumber, $message, 'demo_sent', 'semaphore', 'demo');
                    $results['sent']++;
                    $results['details'][] = [
                        'number' => $cleanNumber,
                        'status' => 'demo_sent',
                        'message' => 'DEMO MODE: Logged'
                    ];
                    error_log("✅ DEMO: Logged $cleanNumber");
                } else {
                    $results['invalid']++;
                    $results['invalid_numbers'][] = $cleanNumber;
                    error_log("❌ Invalid number: $cleanNumber");
                }
            }

            return $results;
        }

        // REAL MODE: Send via Semaphore API
        $apiKey = $this->settings['api_key'] ?? '';

        if (empty($apiKey)) {
            error_log("❌ API Key not configured");
            return array_merge($results, [
                'error' => 'API Key not configured'
            ]);
        }

        $senderId = 'SEMAPHORE'; // Force ALL CAPS
        // Ensure proper sender name
        if (strtoupper($senderId) === 'SEMAPHORE') {
            $senderId = 'Semaphore'; // Use the exact case that works
        }
        $validNumbers = [];
        $invalidNumbers = [];

        // Filter valid numbers
        foreach ($phoneNumbers as $number) {
            $cleanNumber = $this->cleanPhoneNumber(trim($number));
            if ($this->validatePhoneNumber($cleanNumber)) {
                $formattedNumber = $this->formatNumberForSemaphore($cleanNumber);
                if ($formattedNumber && $this->validateInternationalNumber($formattedNumber)) {
                    $validNumbers[] = $formattedNumber;
                    $results['details'][] = [
                        'original' => $cleanNumber,
                        'formatted' => $formattedNumber,
                        'status' => 'valid'
                    ];
                } else {
                    $invalidNumbers[] = $cleanNumber;
                }
            } else {
                $invalidNumbers[] = $cleanNumber;
            }
        }

        $results['invalid'] = count($invalidNumbers);
        $results['invalid_numbers'] = $invalidNumbers;

        error_log("Valid numbers to send: " . count($validNumbers));
        error_log("Invalid numbers: " . count($invalidNumbers));

        if (empty($validNumbers)) {
            return $results;
        }

        // Send in batches (Semaphore supports up to 1000 numbers per request)
        $batches = array_chunk($validNumbers, 100);

        foreach ($batches as $batchIndex => $batch) {
            error_log("Processing batch " . ($batchIndex + 1) . " of " . count($batches));

            // Join numbers with commas
            $numbersString = implode(',', $batch);

            // Prepare POST data for Semaphore API v4
            $postData = [
                'apikey' => $apiKey,
                'number' => $numbersString,
                'message' => $message,
                'sender_name' => 'SEMAPHORE' // Fixed: underscore and ALL CAPS
            ];

            error_log("Sending to Semaphore API...");
            error_log("Message: " . substr($message, 0, 50) . "...");

            // Send via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            error_log("HTTP Response Code: $httpCode");
            error_log("API Response: " . $response);

            if ($curlError) {
                error_log("CURL Error: " . $curlError);
                foreach ($batch as $formattedNumber) {
                    $results['failed']++;
                    $results['failed_numbers'][] = $formattedNumber;
                    $this->logToDatabase($formattedNumber, $message, 'failed', 'semaphore', "CURL Error: $curlError");
                }
                continue;
            }

            if ($httpCode == 200) {
                $responseData = json_decode($response, true);

                if (is_array($responseData)) {
                    foreach ($responseData as $index => $result) {
                        $phoneNumber = $batch[$index] ?? '';
                        $originalNumber = $this->extractOriginalNumber($phoneNumber);

                        if (isset($result['status']) && ($result['status'] == 'Queued' || $result['status'] == 'Pending')) {
                            $results['sent']++;
                            $this->logToDatabase($originalNumber, $message, 'queued', 'semaphore', json_encode($result));
                            $results['details'][] = [
                                'number' => $originalNumber,
                                'status' => 'queued',
                                'message_id' => $result['message_id'] ?? ''
                            ];
                            error_log("✅ Queued: $originalNumber");
                        } else {
                            $results['failed']++;
                            $results['failed_numbers'][] = $originalNumber;
                            $errorMsg = $result['error'] ?? 'Unknown error';
                            $this->logToDatabase($originalNumber, $message, 'failed', 'semaphore', $errorMsg);
                            error_log("❌ Failed for $originalNumber: $errorMsg");
                        }
                    }
                } else {
                    error_log("❌ Invalid API response format");
                    foreach ($batch as $formattedNumber) {
                        $results['failed']++;
                        $originalNumber = $this->extractOriginalNumber($formattedNumber);
                        $results['failed_numbers'][] = $originalNumber;
                        $this->logToDatabase($originalNumber, $message, 'failed', 'semaphore', "Invalid API response");
                    }
                }
            } else {
                error_log("HTTP Error: $httpCode - Response: $response");
                foreach ($batch as $formattedNumber) {
                    $results['failed']++;
                    $originalNumber = $this->extractOriginalNumber($formattedNumber);
                    $results['failed_numbers'][] = $originalNumber;
                    $this->logToDatabase($originalNumber, $message, 'failed', 'semaphore', "HTTP $httpCode: $response");
                }
            }

            // Rate limiting - 1 second between batches
            if (count($batches) > 1 && $batchIndex < count($batches) - 1) {
                sleep(1);
            }
        }

        error_log("\n=== FINAL BULK SMS RESULTS ===");
        error_log("Total: {$results['total']}");
        error_log("Sent: {$results['sent']}");
        error_log("Failed: {$results['failed']}");
        error_log("Invalid: {$results['invalid']}");

        return $results;
    }

    private function cleanPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters except plus sign
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If it starts with +63, convert to 0 for easier processing
        if (strpos($cleaned, '+63') === 0) {
            $cleaned = '0' . substr($cleaned, 3);
        }

        return $cleaned;
    }

    public function validatePhoneNumber($phoneNumber)
    {
        $number = $this->cleanPhoneNumber($phoneNumber);

        // After cleaning, check various valid formats
        // 09XXXXXXXXX → 11 digits (most common)
        if (preg_match('/^09\d{9}$/', $number)) {
            return true;
        }

        // 9XXXXXXXXX → 10 digits
        if (preg_match('/^9\d{9}$/', $number)) {
            return true;
        }

        // 63XXXXXXXXX → 11 digits (country code without leading 0)
        if (preg_match('/^63\d{9}$/', $number)) {
            return true;
        }

        // 63XXXXXXXXXX → 12 digits (some older numbers)
        if (preg_match('/^63\d{10}$/', $number)) {
            return true;
        }

        // +639XXXXXXXXX → 13 characters with plus
        if (preg_match('/^\+63\d{10}$/', $number)) {
            return true;
        }

        return false;
    }

    public function validateInternationalNumber($phoneNumber)
    {
        $number = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Accept 63XXXXXXXXX (11 digits) or 63XXXXXXXXXX (12 digits)
        // Philippine numbers: 63 + 9 or 10 digits
        if (preg_match('/^63\d{9,10}$/', $number)) {
            error_log("Valid international number: $phoneNumber (clean: $number)");
            return true;
        }

        error_log("Invalid international number: $phoneNumber (clean: $number)");
        return false;
    }

    public function formatNumberForSemaphore($phoneNumber)
    {
        $number = $this->cleanPhoneNumber($phoneNumber);

        // 09XXXXXXXXX → convert to 639XXXXXXXXX
        if (preg_match('/^09(\d{9})$/', $number, $m)) {
            return '63' . $m[1];
        }

        // 9XXXXXXXXX → convert to 639XXXXXXXXX
        if (preg_match('/^9(\d{9})$/', $number, $m)) {
            return '63' . $m[1];
        }

        // 63XXXXXXXXX or 63XXXXXXXXXX → already correct
        if (preg_match('/^63\d{9,10}$/', $number)) {
            return $number;
        }

        // If it still has +, remove it
        if (strpos($number, '+') === 0) {
            $number = substr($number, 1);
        }

        return $number;
    }

    private function extractOriginalNumber($formattedNumber)
    {
        // Convert 639XXXXXXXXX back to 09XXXXXXXXX for display
        if (preg_match('/^63(\d{9})$/', $formattedNumber, $m)) {
            return '09' . $m[1];
        }
        return $formattedNumber;
    }

    private function logToDatabase($phoneNumber, $message, $status, $carrier = '', $response = '')
    {
        $conn = $this->getDBConnection();
        if (!$conn) {
            error_log("Database connection failed for logging");
            return false;
        }

        // Get current user ID from session
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'System');
        $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'System';

        // Escape values
        $phoneNumber = $conn->real_escape_string($phoneNumber);
        $message = $conn->real_escape_string(substr($message, 0, 500));
        $status = $conn->real_escape_string($status);
        $carrier = $conn->real_escape_string($carrier);
        $response = $conn->real_escape_string($response);
        $userId = intval($userId);
        $username = $conn->real_escape_string($username);
        $userType = $conn->real_escape_string($userType);
        $smsType = 'outgoing'; // Default

        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'sms_logs'");
        if ($tableCheck->num_rows == 0) {
            // Create table
            $createTable = "CREATE TABLE sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone_number VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(50) NOT NULL,
                carrier VARCHAR(50),
                sms_type VARCHAR(50) DEFAULT 'outgoing',
                user_id INT DEFAULT NULL,
                username VARCHAR(100) DEFAULT NULL,
                user_type VARCHAR(50) DEFAULT NULL,
                response_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_phone_number (phone_number),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            )";
            $conn->query($createTable);
        }

        $query = "INSERT INTO sms_logs 
                  (phone_number, message, status, carrier, sms_type, user_id, username, user_type, response_data, created_at) 
                  VALUES 
                  ('$phoneNumber', '$message', '$status', '$carrier', '$smsType', $userId, '$username', '$userType', '$response', NOW())";

        $result = $conn->query($query);

        if ($result) {
            error_log("✅ Logged to database: $phoneNumber - $status");
        } else {
            error_log("❌ Database log failed: " . $conn->error);
        }

        return $result;
    }

    private function errorResponse($message)
    {
        error_log("ERROR: $message");
        return [
            'success' => false,
            'message' => $message
        ];
    }

    public function testConnection()
    {
        $apiKey = $this->settings['api_key'] ?? '';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key is empty',
                'details' => 'Please configure your Semaphore API key'
            ];
        }

        // Test by getting account balance/info
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/account?apikey=" . urlencode($apiKey));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message' => 'Connection failed',
                'details' => 'cURL Error: ' . $curlError,
                'http_code' => $httpCode
            ];
        }

        if ($httpCode == 200) {
            $data = json_decode($response, true);

            if (isset($data['credit_balance'])) {
                return [
                    'success' => true,
                    'message' => 'API Connection Successful!',
                    'details' => 'Credit Balance: ' . $data['credit_balance'] . ' credits',
                    'data' => $data
                ];
            } else if (isset($data['message'])) {
                return [
                    'success' => false,
                    'message' => 'API Error',
                    'details' => $data['message'],
                    'data' => $data
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Unexpected response',
            'details' => 'HTTP Code: ' . $httpCode . ', Response: ' . substr($response, 0, 200),
            'http_code' => $httpCode
        ];
    }

    public function testSemaphoreAPI($testNumber = '09171234567')
    {
        $apiKey = $this->settings['api_key'] ?? '';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key not configured'
            ];
        }

        // Test account balance first
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/account?apikey=" . urlencode($apiKey));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("=== SEMAPHORE ACCOUNT TEST ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response: $response");

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $balance = $data['credit_balance'] ?? 0;
            $status = $data['status'] ?? 'unknown';

            return [
                'success' => true,
                'message' => "Semaphore API is working. Balance: ₱{$balance}, Status: {$status}",
                'data' => $data
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to connect to Semaphore API. HTTP Code: ' . $httpCode,
                'response' => $response
            ];
        }
    }

    // New method to test sending with better debugging
    public function debugSendTest($testNumber = '09171234567', $testMessage = 'Test SMS from MSWD System')
    {
        error_log("\n=== DEBUG SEND TEST ===");
        error_log("Test Number: $testNumber");
        error_log("API Key: " . substr($this->settings['api_key'] ?? '', 0, 8) . '...');

        // Test number formatting
        $cleanNumber = $this->cleanPhoneNumber($testNumber);
        error_log("Clean Number: $cleanNumber");

        $formatted = $this->formatNumberForSemaphore($cleanNumber);
        error_log("Formatted: $formatted");

        // Validate
        $valid = $this->validatePhoneNumber($testNumber);
        error_log("Valid Format: " . ($valid ? 'YES' : 'NO'));

        // Validate international
        $validInt = $this->validateInternationalNumber($formatted);
        error_log("Valid International: " . ($validInt ? 'YES' : 'NO'));

        // Test API connection
        $apiTest = $this->testSemaphoreAPI($testNumber);
        error_log("API Test: " . ($apiTest['success'] ? 'SUCCESS' : 'FAILED'));

        if ($apiTest['success']) {
            // Try to send actual SMS
            return $this->send($testNumber, $testMessage);
        } else {
            return $apiTest;
        }
    }
}

// Public helper functions
function getSMSSettings()
{
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    if ($conn->connect_error) {
        error_log("Failed to connect to database: " . $conn->connect_error);
        return [
            'provider' => 'semaphore',
            'api_key' => '11203b3c9a4bc430dd3a1b181ece8b6c',
            'sender_id' => 'Semaphore', // Updated
            'is_active' => true,
            'demo_mode' => false // Updated
        ];
    }

    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'sms_settings'");
    if ($tableCheck->num_rows == 0) {
        // Create the table WITHOUT api_url column
        $createTable = "CREATE TABLE sms_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) DEFAULT 'semaphore',
            api_key VARCHAR(255),
            sender_id VARCHAR(50) DEFAULT 'Semaphore', // Updated
            is_active TINYINT(1) DEFAULT 1,
            demo_mode TINYINT(1) DEFAULT 0, // Updated to 0 (real mode)
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($createTable);

        // Insert default settings with your API key
        $insertDefault = "INSERT INTO sms_settings (provider, api_key, sender_id, is_active, demo_mode) 
                         VALUES ('semaphore', '11203b3c9a4bc430dd3a1b181ece8b6c', 'Semaphore', 1, 0)";
        $conn->query($insertDefault);

        error_log("Created sms_settings table with default values");
    }

    $result = $conn->query("SELECT * FROM sms_settings LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $conn->close();

        // Ensure all fields exist
        $defaults = [
            'provider' => 'semaphore',
            'api_key' => '11203b3c9a4bc430dd3a1b181ece8b6c',
            'sender_id' => 'Semaphore',
            'is_active' => true,
            'demo_mode' => false
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        // Ensure sender_id is valid
        if (isset($settings['sender_id']) && $settings['sender_id'] === 'MSWDPALUAN') {
            $settings['sender_id'] = 'Semaphore';
        }

        return $settings;
    }

    $conn->close();
    return [
        'provider' => 'semaphore',
        'api_key' => '11203b3c9a4bc430dd3a1b181ece8b6c',
        'sender_id' => 'Semaphore', // Updated
        'is_active' => true,
        'demo_mode' => false // Updated
    ];
}

function saveSMSSettings($settings)
{
    $conn = new mysqli("localhost", "u401132124_mswdopaluan", "Mswdo_PaluanSystem23", "u401132124_mswd_seniors");
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    // Ensure all fields
    $defaults = [
        'provider' => 'semaphore',
        'api_key' => '11203b3c9a4bc430dd3a1b181ece8b6c',
        'sender_id' => 'MSWDPALUAN',
        'is_active' => true,
        'demo_mode' => true
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
    $isActive = (int)($settings['is_active'] ?? 1);
    $demoMode = (int)($settings['demo_mode'] ?? 1);

    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'sms_settings'");
    if ($tableCheck->num_rows == 0) {
        // Create table without api_url
        $createTable = "CREATE TABLE sms_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) DEFAULT 'semaphore',
            api_key VARCHAR(255),
            sender_id VARCHAR(50) DEFAULT 'MSWDPALUAN',
            is_active TINYINT(1) DEFAULT 1,
            demo_mode TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($createTable);

        error_log("Created sms_settings table");
    }

    // Check if settings exist
    $check = $conn->query("SELECT id FROM sms_settings LIMIT 1");

    if ($check && $check->num_rows > 0) {
        // Update existing settings
        $query = "UPDATE sms_settings SET 
                  provider = '$provider',
                  api_key = '$apiKey',
                  sender_id = '$senderId',
                  is_active = $isActive,
                  demo_mode = $demoMode,
                  updated_at = NOW()
                  LIMIT 1";
    } else {
        // Insert new settings
        $query = "INSERT INTO sms_settings 
                  (provider, api_key, sender_id, is_active, demo_mode) 
                  VALUES 
                  ('$provider', '$apiKey', '$senderId', $isActive, $demoMode)";
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

// New function to get detailed system status
function getDetailedSystemStatus()
{
    $settings = getSMSSettings();
    $gateway = new SMSGateway($settings);

    $status = [
        'settings' => $settings,
        'demo_mode' => $settings['demo_mode'] ?? false,
        'is_active' => $settings['is_active'] ?? false,
        'api_key_set' => !empty($settings['api_key']),
        'api_key_preview' => !empty($settings['api_key']) ? substr($settings['api_key'], 0, 8) . '...' : 'NOT SET',
        'tables_exist' => true,
        'test_result' => null
    ];

    // Test API connection if not in demo mode
    if (!$status['demo_mode'] && $status['api_key_set']) {
        $testResult = $gateway->testSemaphoreAPI();
        $status['test_result'] = $testResult;
        $status['api_working'] = $testResult['success'] ?? false;
    }

    return $status;
}
