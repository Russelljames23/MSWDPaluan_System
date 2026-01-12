<?php
// log_debug.php - Simple debug logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$input = json_decode(file_get_contents('php://input'), true);
$logMessage = date('Y-m-d H:i:s') . " - Client-side error: " . 
              ($input['error'] ?? 'Unknown error') . 
              " - Seniors count: " . ($input['seniorsCount'] ?? 0);

error_log($logMessage);

header('Content-Type: application/json');
echo json_encode(['logged' => true, 'message' => $logMessage]);