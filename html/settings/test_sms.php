<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../php/helpers/sms_helper.php";

echo "<h2>SMS Test Script</h2>";
echo "<pre>";

// Get SMS settings
$smsSettings = getSMSSettings();

echo "=== SMS Settings ===\n";
print_r($smsSettings);

echo "\n=== Testing API Key ===\n";
$smsGateway = new SMSGateway($smsSettings);
$testResult = $smsGateway->testConnection();
print_r($testResult);

if ($testResult['success']) {
    echo "\n=== Testing SMS Send ===\n";
    
    // Test phone number - use your own number for testing
    $testNumber = '09272873751'; // Replace with your actual number
    $testMessage = 'Test SMS from MSWD System - Please reply if received';
    
    echo "Test Number: $testNumber\n";
    
    // Check validation
    $cleanNumber = $smsGateway->cleanPhoneNumber($testNumber);
    echo "Clean Number: $cleanNumber\n";
    
    $isValid = $smsGateway->validatePhoneNumber($testNumber);
    echo "Valid Phone: " . ($isValid ? 'YES' : 'NO') . "\n";
    
    $formattedNumber = $smsGateway->formatNumberForSemaphore($testNumber);
    echo "Formatted for Semaphore: $formattedNumber\n";
    
    $isIntValid = $smsGateway->validateInternationalNumber($formattedNumber);
    echo "Valid International: " . ($isIntValid ? 'YES' : 'NO') . "\n";
    
    if ($isValid && $isIntValid) {
        echo "\n=== Sending Test SMS ===\n";
        $result = $smsGateway->send($testNumber, $testMessage);
        print_r($result);
        
        // Also try with different formatting
        echo "\n=== Alternative Test with 639 format ===\n";
        $altNumber = '639272873751'; // Try directly with 639 format
        echo "Trying with: $altNumber\n";
        $altResult = $smsGateway->send($altNumber, $testMessage);
        print_r($altResult);
    } else {
        echo "\n❌ Phone number validation failed!\n";
    }
} else {
    echo "\n❌ API Connection failed!\n";
}

echo "\n=== Debug Info ===\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "cURL Enabled: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";

echo "</pre>";
?>