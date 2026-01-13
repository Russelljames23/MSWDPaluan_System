<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

$response = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'server_time' => date('Y-m-d H:i:s'),
    'session_status' => session_status()
];

echo json_encode($response, JSON_PRETTY_PRINT);