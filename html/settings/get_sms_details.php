<?php
// get_sms_details.php
require_once "../../php/login/admin_header.php";

header('Content-Type: application/json');

$sms_id = $_GET['id'] ?? 0;

if (!$sms_id) {
    echo json_encode(['error' => 'No SMS ID provided']);
    exit;
}

// Database connection
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT sl.*, u.username, u.user_type, u.profile_photo 
        FROM sms_logs sl 
        LEFT JOIN users u ON sl.user_id = u.id 
        WHERE sl.id = ?
    ");
    $stmt->execute([$sms_id]);
    $sms_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sms_data) {
        // Parse response_data if it's JSON
        if (!empty($sms_data['response_data'])) {
            $sms_data['response_data'] = json_decode($sms_data['response_data'], true);
        }
        
        // Get profile photo URL
        if (!empty($sms_data['profile_photo'])) {
            $sms_data['user_avatar'] = '../../' . $sms_data['profile_photo'];
            if (!file_exists($sms_data['user_avatar'])) {
                $sms_data['user_avatar'] = '';
            }
        }
        
        echo json_encode($sms_data);
    } else {
        echo json_encode(['error' => 'SMS log not found']);
    }
} catch (PDOException $e) {
    error_log("Error fetching SMS details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}