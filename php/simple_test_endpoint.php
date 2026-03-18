<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'This is a simple test endpoint',
    'timestamp' => date('Y-m-d H:i:s'),
    'input_received' => file_get_contents('php://input')
]);
?>  